<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\ReceivingRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogService;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;

class ReceivingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(!canAccess('receiving-request-list') && !auth()->user()->can('receiving-request-list'), 403);

        $deliveryChallans = ReceivingRequest::whereHas('deliveryChallan', function ($q) {
                $q->where('sauda_type', 'pohanch');
            })
            ->select('id', 'dc_no')
            ->distinct()
            ->get();
        return view('management.sales.receiving-request.index', compact('deliveryChallans'));
    }

    /**
     * Get list of receiving requests.
     */
    public function getList(Request $request)
    {
        abort_if(!canAccess('receiving-request-list') && !auth()->user()->can('receiving-request-list'), 403);

        $perPage = $request->get('per_page', 25);

        $receivingRequests = ReceivingRequest::with(['deliveryChallan.customer', 'deliveryChallan.delivery_order', 'items.product'])
            ->whereHas('deliveryChallan', function ($q) {
                $q->where('sauda_type', 'pohanch');
            })
            ->when($request->filled('dc_id_for_filter') && $request->dc_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('id', $request->dc_id_for_filter);
            })
            ->when($request->filled('dc_date_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->dc_date_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('dc_date', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
                }
            })
            ->when($request->filled('created_at_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->created_at_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('created_at', [trim($dates[0]) . ' 00:00:00', trim($dates[1]) . ' 23:59:59']);
                }
            })
            ->when($request->filled('status_for_filter') && $request->status_for_filter != 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status_for_filter);
            })
            ->when($request->filled('search_for_filter'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search_for_filter) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`dc_no`) LIKE ?', [$searchTerm])
                      ->orWhereRaw('LOWER(`truck_number`) LIKE ?', [$searchTerm]);
                });
            })
            ->where(function($q) {
                $q->where('am_approval_status', '!=', 'draft')
                  ->orWhereNull('am_approval_status')
                  ->orWhere(function($sq) {
                      $sq->where('am_approval_status', 'draft')
                         ->where('created_by_id', auth()->user()->id ?? null);
                  });
            })
            ->latest()
            ->paginate($perPage);

        return view('management.sales.receiving-request.getList', compact('receivingRequests'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        abort_if(!canAccess('receiving-request-create') && !auth()->user()->can('receiving-request-create'), 403);

        $receivingRequest = ReceivingRequest::with(['deliveryChallan.delivery_challan_data', 'items.product', 'weighbridges'])->findOrFail($id);
        
        $transporters = \App\Models\Master\Transporter::all();
        $labours = \App\Models\Master\Vendor::all();
        return view('management.sales.receiving-request.edit', compact('receivingRequest', 'transporters', 'labours'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        abort_if(!canAccess('receiving-request-create') && !auth()->user()->can('receiving-request-create'), 403);

        DB::beginTransaction();
        try {
            $receivingRequest = ReceivingRequest::findOrFail($id);
            $oldAuditValues = $this->buildReceivingRequestAuditValues($receivingRequest);


            if(in_array(strtolower($receivingRequest->am_approval_status ?? ''), ['approved', 'rejected'])) {
                return response()->json([
                    'error' => "Receiving Request has been {$receivingRequest->am_approval_status} and cannot be updated.",
                    'message' => "This Receiving Request has already been {$receivingRequest->am_approval_status}. Further updates are not allowed from any screen."
                ], 422);
            }

            // Calculate total labour amount
            $totalLabourAmount = 0;
            if ($request->has('items')) {
                foreach ($request->items as $itemId => $itemData) {
                    $item = ReceivingRequestItem::find($itemId);
                    if ($item) {
                        $bags = floatval($item->deliveryChallanData?->no_of_bags ?? 0);
                        $rate = floatval($itemData['unloading_labour_rate'] ?? 0);
                        $totalLabourAmount += ($bags * $rate);
                    }
                }
            }

            $dispatchWeight = floatval($receivingRequest->items->sum('dispatch_weight'));
            $arrivedWeight = floatval($request->arrived_weight ?? 0);
            $exemptedWeight = max(0, floatval($request->exempted_weight ?? 0));

            if ($dispatchWeight > $arrivedWeight) {
                // Shortage / Loss case: exempt up to shortage
                $shortWeight = $dispatchWeight - $arrivedWeight;
                $exemptedWeight = min($exemptedWeight, $shortWeight);
            } elseif ($arrivedWeight > $dispatchWeight) {
                // Excess / Gain case: exempt up to excess gain
                $excessWeight = $arrivedWeight - $dispatchWeight;
                $exemptedWeight = min($exemptedWeight, $excessWeight);
            } else {
                $exemptedWeight = 0;
            }
            $paymentWeight = $arrivedWeight;

            // Update main receiving request
            $receivingRequest->update([
                'labour' => $request->labour,
                'transporter' => $request->transporter,
                'labour_amount' => $receivingRequest->deliveryChallan?->labour_amount ?? $receivingRequest->labour_amount,
                'transporter_amount' => $request->transporter_amount ?? 0,
                'transporter_deduction' => $request->transporter_deduction ?? 0,
                'arrived_date' => $request->arrived_date,
                'arrived_weight' => $arrivedWeight,
                'exempted_weight' => $exemptedWeight,
                'payment_weight' => $paymentWeight,
                'unloading_paid_by' => $request->unloading_paid_by,
                'weighbridge_paid_by' => $request->weighbridge_paid_by,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);

            // Sync weighbridges
            $receivingRequest->weighbridges()->delete();
            $totalWeighbridgeAmount = 0;
            if ($request->has('weighbridges')) {
                foreach ($request->weighbridges as $wb) {
                    if (!empty($wb['name'])) {
                        $amt = floatval($wb['amount'] ?? 0);
                        $receivingRequest->weighbridges()->create([
                            'name' => $wb['name'],
                            'amount' => $amt
                        ]);
                        $totalWeighbridgeAmount += $amt;
                    }
                }
            }
            
            // Now update the amount in ReceivingRequest itself too
            $receivingRequest->update([
                'weighbridge_amount' => $totalWeighbridgeAmount
            ]);

            $receivingRequest->deliveryChallan()->update([
                "labour" => $request->labour,
                "transporter" => $request->transporter,
                "transporter_amount" => $request->transporter_amount ?? 0,
                "weighbridge-amount" => $totalWeighbridgeAmount
            ]);

            // Update items
            if ($request->has('items')) {
                foreach ($request->items as $itemId => $itemData) {
                    $item = ReceivingRequestItem::find($itemId);
                    if ($item) {
                        $item->update([
                            'unloading_labour_rate' => $itemData['unloading_labour_rate'] ?? 0,
                        ]);
                    }
                }
            }

            $receivingRequest->refresh();
            $newAuditValues = $this->buildReceivingRequestAuditValues($receivingRequest);

            $changedOld = [];
            $changedNew = [];
            foreach ($newAuditValues as $k => $v) {
                $o = $oldAuditValues[$k] ?? null;
                if ($o != $v) {
                    $changedOld[$k] = $o;
                    $changedNew[$k] = $v;
                }
            }

            if (!empty($changedOld) || !empty($changedNew)) {
                AuditLogService::log(
                    $receivingRequest,
                    'receiving_request_updated',
                    "Receiving Request #{$receivingRequest->dc_no} updated.",
                    $changedOld,
                    $changedNew
                );
            }

            DB::commit();
            return response()->json(['data' => 'Receiving Request has been updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function view(int $id)
    {
        abort_if(!canAccess('receiving-request-list') && !auth()->user()->can('receiving-request-list'), 403);

        $receivingRequest = ReceivingRequest::with(['deliveryChallan.delivery_challan_data', 'items.product', 'weighbridges'])->findOrFail($id);
        
        $transporters = \App\Models\Master\Transporter::all();
        $labours = \App\Models\Master\Vendor::all();
        return view('management.sales.receiving-request.view', compact('receivingRequest', 'transporters', 'labours'));
    }

    /**
     * Build standard audit log snapshot for a receiving request.
     */
    public function buildReceivingRequestAuditValues(ReceivingRequest $receivingRequest): array
    {
        $receivingRequest->loadMissing(['deliveryChallan.customer', 'items.product', 'weighbridges']);

        $labourName = $receivingRequest->labour ? \App\Models\Master\Vendor::find($receivingRequest->labour)?->name : null;
        $transporterName = $receivingRequest->transporter ? \App\Models\Master\Transporter::find($receivingRequest->transporter)?->name : null;

        $wbList = [];
        foreach ($receivingRequest->weighbridges as $wb) {
            $wbList[] = $wb->name . ' (Rs. ' . number_format((float)$wb->amount, 2) . ')';
        }

        $itemProducts = [];
        $dispatchWeights = [];
        $receivingWeights = [];
        $diffWeights = [];
        $unloadingRates = [];
        $itemBags = [];
        $totalDispatch = 0;
        $totalReceiving = 0;
        $totalBags = 0;

        foreach ($receivingRequest->items as $item) {
            $itemProducts[] = $item->product?->name ?? ($item->item_name ?: '-');
            $dispatchWeights[] = (float) $item->dispatch_weight;
            $receivingWeights[] = (float) $item->receiving_weight;
            $diffWeights[] = (float) $item->difference_weight;
            $unloadingRates[] = (float) $item->unloading_labour_rate;
            $bags = (int) ($item->deliveryChallanData?->no_of_bags ?: ($item->no_of_bags ?: 0));
            $itemBags[] = $bags;
            $totalDispatch += (float) $item->dispatch_weight;
            $totalReceiving += (float) $item->receiving_weight;
            $totalBags += $bags;
        }

        return [
            'dc_no'                      => $receivingRequest->dc_no,
            'dc_date'                    => $receivingRequest->dc_date ? Carbon::parse($receivingRequest->dc_date)->format('Y-m-d') : null,
            'customer'                   => $receivingRequest->deliveryChallan?->customer?->name,
            'truck_number'               => $receivingRequest->truck_number,
            'bilty'                      => $receivingRequest->bilty,
            'arrived_date'               => $receivingRequest->arrived_date ? Carbon::parse($receivingRequest->arrived_date)->format('Y-m-d') : null,
            'arrived_weight'             => (float) ($receivingRequest->arrived_weight ?? 0),
            'exempted_weight'            => (float) ($receivingRequest->exempted_weight ?? 0),
            'payment_weight'             => (float) ($receivingRequest->payment_weight ?? 0),
            'labour'                     => $labourName,
            'labour_amount'              => (float) ($receivingRequest->labour_amount ?? 0),
            'unloading_paid_by'          => $receivingRequest->unloading_paid_by ? ucfirst($receivingRequest->unloading_paid_by) : null,
            'transporter'                => $transporterName,
            'transporter_amount'         => (float) ($receivingRequest->transporter_amount ?? 0),
            'transporter_deduction'      => (float) ($receivingRequest->transporter_deduction ?? 0),
            'demurrage_detention_amount' => (float) ($receivingRequest->demurrage_detention_amount ?? 0),
            'weighbridge_amount'         => (float) ($receivingRequest->weighbridge_amount ?? 0),
            'weighbridge_paid_by'        => $receivingRequest->weighbridge_paid_by ? ucfirst($receivingRequest->weighbridge_paid_by) : null,
            'weighbridges'               => !empty($wbList) ? implode(', ', $wbList) : null,
            'am_approval_status'         => $receivingRequest->am_approval_status,
            'items_product'              => !empty($itemProducts) ? implode(', ', $itemProducts) : null,
            'items_dispatch_weight'      => !empty($dispatchWeights) ? implode(', ', $dispatchWeights) : null,
            'items_receiving_weight'     => !empty($receivingWeights) ? implode(', ', $receivingWeights) : null,
            'items_difference_weight'    => !empty($diffWeights) ? implode(', ', $diffWeights) : null,
            'items_unloading_rate'       => !empty($unloadingRates) ? implode(', ', $unloadingRates) : null,
            'items_bags'                 => !empty($itemBags) ? implode(', ', $itemBags) : null,
            'total_dispatch_weight'      => (float) $totalDispatch,
            'total_receiving_weight'     => (float) $totalReceiving,
            'total_bags'                 => (int) $totalBags,
            'items_count'                => $receivingRequest->items->count(),
        ];
    }

    /**
     * Get audit logs for a receiving request.
     */
    public function getAuditLogs(Request $request, $id)
    {
        $receivingRequest = ReceivingRequest::with(['deliveryChallan.customer', 'items.product', 'weighbridges'])->findOrFail($id);

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($receivingRequest) {
                $q->where(function ($sq) use ($receivingRequest) {
                    $sq->whereIn('model_type', [ReceivingRequest::class, 'ReceivingRequest', 'App\Models\Sales\ReceivingRequest'])
                        ->where('model_id', $receivingRequest->id);
                })
                ->orWhere('old_values', 'like', '%"receiving_request_id":' . $receivingRequest->id . '%')
                ->orWhere('old_values', 'like', '%"receiving_request_id":"' . $receivingRequest->id . '"%')
                ->orWhere('new_values', 'like', '%"receiving_request_id":' . $receivingRequest->id . '%')
                ->orWhere('new_values', 'like', '%"receiving_request_id":"' . $receivingRequest->id . '"%');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $hasCreateLog = $logs->contains(function ($l) {
            return str_contains($l->action, 'create') || str_contains($l->action, 'store');
        });

        if (!$hasCreateLog) {
            $virtualValues = $this->buildReceivingRequestAuditValues($receivingRequest);
            $virtualCreateLog = new AuditLog([
                'action' => 'receiving_request_created',
                'description' => "Receiving Request #{$receivingRequest->dc_no} created.",
                'user_id' => $receivingRequest->created_by_id,
                'created_at' => $receivingRequest->created_at,
                'old_values' => null,
                'new_values' => $virtualValues,
            ]);
            $creator = User::find($receivingRequest->created_by_id);
            $virtualCreateLog->setRelation('user', $creator);
            $logs->push($virtualCreateLog);
        }

        $fieldLabels = [
            'dc_no'                      => 'DC No',
            'dc_date'                    => 'DC Date',
            'customer'                   => 'Customer',
            'truck_number'               => 'Truck Number',
            'bilty'                      => 'Bilty Number',
            'arrived_date'               => 'Arrived Date',
            'arrived_weight'             => 'Arrived Weight (Kgs)',
            'exempted_weight'            => 'Exempted Weight (Kgs)',
            'payment_weight'             => 'Payment Weight (Kgs)',
            'labour'                     => 'Labour Contractor',
            'labour_amount'              => 'Labour Amount',
            'unloading_paid_by'          => 'Unloading Paid By',
            'transporter'                => 'Transporter',
            'transporter_amount'         => 'Transporter Amount',
            'transporter_deduction'      => 'Transporter Deduction',
            'demurrage_detention_amount' => 'Demurrage / Detention Amount',
            'weighbridge_amount'         => 'Weighbridge Amount',
            'weighbridge_paid_by'        => 'Weighbridge Paid By',
            'weighbridges'               => 'Weighbridges',
            'am_approval_status'         => 'Approval Status',
            'items_product'              => 'Products',
            'items_dispatch_weight'      => 'Dispatch Weights (Kgs)',
            'items_receiving_weight'     => 'Receiving Weights (Kgs)',
            'items_difference_weight'    => 'Difference Weights (Kgs)',
            'items_unloading_rate'       => 'Unloading Labour Rates',
            'items_bags'                 => 'No of Bags',
            'total_dispatch_weight'      => 'Total Dispatch Weight (Kgs)',
            'total_receiving_weight'     => 'Total Receiving Weight (Kgs)',
            'total_bags'                 => 'Total Bags',
            'items_count'                => 'Items Count',
        ];

        $relationCache = [];
        $resolveRelationValue = function ($key, $val) use (&$relationCache) {
            if ($val === null || $val === '') {
                return null;
            }
            if (is_array($val)) {
                return json_encode($val);
            }

            if ($key === 'transporter' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "transporter_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $t = \App\Models\Master\Transporter::find($val);
                $res = $t ? $t->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'labour' && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "labour_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $v = \App\Models\Master\Vendor::find($val);
                $res = $v ? $v->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if ($key === 'am_approval_status') {
                return ucfirst($val);
            }

            if (in_array($key, ['labour_amount', 'transporter_amount', 'transporter_deduction', 'demurrage_detention_amount', 'weighbridge_amount']) && is_numeric($val)) {
                return 'Rs. ' . number_format((float) $val, 2);
            }

            if (in_array($key, ['arrived_weight', 'exempted_weight', 'payment_weight', 'total_dispatch_weight', 'total_receiving_weight']) && is_numeric($val)) {
                return number_format((float) $val, 2) . ' Kgs';
            }

            return $val;
        };

        $flattenArray = function ($arr, $prefix = '') use (&$flattenArray) {
            $result = [];
            if (!is_array($arr)) return $result;
            foreach ($arr as $key => $value) {
                $newKey = $prefix === '' ? $key : "{$prefix}.{$key}";
                if (is_array($value) && !empty($value) && !isset($value[0])) {
                    $result = array_merge($result, $flattenArray($value, $newKey));
                } else {
                    $result[$newKey] = $value;
                }
            }
            return $result;
        };

        $filteredLogs = [];
        $skipKeys = ['id', 'deleted_at', 'created_at', 'updated_at', 'company_id', 'am_change_made'];

        foreach ($logs as $log) {
            $rawOld = is_array($log->old_values) ? $log->old_values : (!empty($log->old_values) ? json_decode($log->old_values, true) : []);
            if (is_string($rawOld)) $rawOld = json_decode($rawOld, true);

            $rawNew = is_array($log->new_values) ? $log->new_values : (!empty($log->new_values) ? json_decode($log->new_values, true) : []);
            if (is_string($rawNew)) $rawNew = json_decode($rawNew, true);

            $old = $flattenArray($rawOld);
            $new = $flattenArray($rawNew);

            $parsedChanges = [];
            $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));

            foreach ($allKeys as $key) {
                if (in_array($key, $skipKeys)) continue;

                $oldVal = $old[$key] ?? null;
                $newVal = $new[$key] ?? null;

                if ($oldVal != $newVal) {
                    // Avoid duplicate rows for single item where totals equal item values
                    if ($key === 'total_bags' && isset($new['items_bags']) && (string) $new['items_bags'] === (string) $newVal && (!isset($old['items_bags']) || (string) $old['items_bags'] === (string) $oldVal)) {
                        continue;
                    }
                    if ($key === 'total_dispatch_weight' && isset($new['items_dispatch_weight']) && (float) $new['items_dispatch_weight'] === (float) $newVal && (!isset($old['items_dispatch_weight']) || (float) $old['items_dispatch_weight'] === (float) $oldVal)) {
                        continue;
                    }
                    if ($key === 'total_receiving_weight' && isset($new['items_receiving_weight']) && (float) $new['items_receiving_weight'] === (float) $newVal && (!isset($old['items_receiving_weight']) || (float) $old['items_receiving_weight'] === (float) $oldVal)) {
                        continue;
                    }

                    $oldResolved = $resolveRelationValue($key, $oldVal);
                    $newResolved = $resolveRelationValue($key, $newVal);

                    $parsedChanges[] = [
                        'key'     => $key,
                        'label'   => $fieldLabels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                        'old'     => is_array($oldResolved) ? json_encode($oldResolved) : $oldResolved,
                        'new'     => is_array($newResolved) ? json_encode($newResolved) : $newResolved,
                        'raw_old' => $oldVal,
                        'raw_new' => $newVal,
                    ];
                }
            }

            if (count($parsedChanges) > 1) {
                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => !in_array($c['key'], ['updated_at', 'created_at'])));
            }

            $log->parsed_changes = $parsedChanges;
            $filteredLogs[] = $log;
        }

        $logs = $filteredLogs;
        $title = "Audit Logs: Receiving Request #{$receivingRequest->dc_no}";

        return view('management.sales.receiving-request.partials.audit_logs_content', compact('receivingRequest', 'logs', 'title'));
    }
}
