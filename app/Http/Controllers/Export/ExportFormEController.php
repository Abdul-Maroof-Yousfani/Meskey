<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportFormE;
use App\Models\Export\ExportOrder;
use App\Models\Master\Customer;
use App\Models\Production\JobOrder\JobOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Database\QueryException;

class ExportFormEController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $form_es = ExportFormE::orderBy('id', 'ASC')->paginate(0);
        } catch (QueryException $e) {
            $form_es = collect();
        }

        return view('management.export.form-e.index', compact('form_es'))->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function getExportFormETable(Request $request)
    {
        try {
            $form_es = ExportFormE::with(['exportOrder', 'buyer', 'jobOrder'])
                ->when($request->filled('search'), function ($q) use ($request) {
                    $searchTerm = '%' . $request->search . '%';
                    return $q->whereHas('exportOrder', function ($sq) use ($searchTerm) {
                        $sq->where('voucher_no', 'like', $searchTerm)
                            ->orWhere('contract_no', 'like', $searchTerm);
                    });
                })
                ->latest()
                ->paginate(request('per_page', 25));
        } catch (QueryException $e) {
            $form_es = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25);
        }

        return view('management.export.form-e.getList', compact('form_es'));
    }

    public function create(): View
    {
        $buyers = collect();
        $export_orders = collect();
        $products = $bagTypes = $bagPackings = $brands = $bagColors = $users = $banks = $brokers = $incoterms = $modeofterms = $modeoftransport = $countries = $ports = $hscodes = $currencies = $exportSodas = $quotations = collect();

        try {
            $buyers = Customer::get();
            $job_orders = collect(); // Initially empty, will be filled via AJAX when Export Order is selected
        } catch (QueryException $e) {
        }

        return view('management.export.form-e.create', compact(
            'buyers',
            'job_orders',
            'export_orders',
            'products',
            'bagTypes',
            'bagPackings',
            'brands',
            'bagColors',
            'users',
            'banks',
            'brokers',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'hscodes',
            'currencies',
            'exportSodas',
            'quotations'
        ));
    }

    public function getExportOrderDetails($id)
    {
        try {
            $exportOrder = ExportOrder::where('am_approval_status', 'approved')
                ->whereNotIn('id', function($q) {
                    $q->select('export_order_id')->from('export_order_addendums');
                })
                ->with([
                'product',
                'packingItems.bagType',
                'packingItems.bagPacking',
                'packingItems.brand',
                'packingItems.bagColor',
                'broker',
                'currency',
                'incoterm',
                'originCountry',
                'portOfDischarge',
                'portOfLoading',
                'hsCode',
                'modeOfTerm',
                'modeOfTransport'
            ])->findOrFail($id);

            // Calculate total quantity of all packing items
            $totalQuantity = 0;
            foreach ($exportOrder->packingItems as $item) {
                // summing up metric_tons
                $totalQuantity += (float) ($item->metric_tons ?? 0);
            }

            // Get total used quantity from existing Form-E for this export order
            $usedQuantity = 0;
            try {
                $usedQuantity = ExportFormE::where('export_order_id', $id)->sum('input_quantity');
            } catch (\Exception $e) {
                // Table might not exist yet
                $usedQuantity = 0;
            }

            $remainingQuantity = $totalQuantity - $usedQuantity;
            if ($remainingQuantity < 0)
                $remainingQuantity = 0;

            // Upper limit for the input box: total + positive tolerance - used (same rule as store)
            $positiveTolerance = (float) ($exportOrder->positive_tolerance ?? 0);
            $maxRemainingAllowed = max(0, $totalQuantity + ($totalQuantity * ($positiveTolerance / 100)) - $usedQuantity);

            return response()->json([
                'success' => true,
                'data' => $exportOrder,
                'total_quantity' => $totalQuantity,
                'remaining_quantity' => $remainingQuantity,
                'max_remaining_allowed' => round($maxRemainingAllowed, 2),
                'used_quantity' => $usedQuantity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'export_order_id' => 'required|exists:export_orders,id',
            'buyer_id' => 'required|exists:customers,id',
            'job_order_id' => 'required|exists:job_orders,id',
            'form_e_no' => 'required|string|unique:export_form_es,form_e_no',
            'form_e_date' => 'required|date',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'input_quantity' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();

        try {
            $exportOrder = ExportOrder::where('am_approval_status', 'approved')
                ->whereNotIn('id', function($q) {
                    $q->select('export_order_id')->from('export_order_addendums');
                })
                ->with([
                'product',
                'packingItems.bagType',
                'packingItems.bagPacking',
                'packingItems.brand',
                'packingItems.bagColor',
                'broker',
                'currency',
                'incoterm',
                'originCountry',
                'portOfDischarge',
                'portOfLoading',
                'hsCode',
                'modeOfTerm',
                'modeOfTransport'
            ])->findOrFail($request->export_order_id);

            // Re-calculate to ensure it does not exceed remaining quantity
            $totalQuantity = 0;
            foreach ($exportOrder->packingItems as $item) {
                $totalQuantity += (float) ($item->metric_tons ?? 0);
            }

            $posTol = (float) ($exportOrder->positive_tolerance ?? 0);
            $negTol = (float) ($exportOrder->negative_tolerance ?? 0);

            $maxAllowed = $totalQuantity + ($totalQuantity * ($posTol / 100));
            $minAllowed = $totalQuantity - ($totalQuantity * ($negTol / 100));

            $usedQuantity = 0;
            try {
                $usedQuantity = (float) ExportFormE::where('export_order_id', $exportOrder->id)->sum('input_quantity');
            } catch (\Exception $e) {
                $usedQuantity = 0;
            }

            $inputQty = (float) $request->input_quantity;
            $totalWithCurrent = $usedQuantity + $inputQty;

            // Form-E quantity cannot exceed what is left on the selected job order
            $jobOrderError = $this->jobOrderCapacityError($request->job_order_id, $inputQty);
            if ($jobOrderError) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'error' => $jobOrderError,
                ], 422);
            }

            // If the export order was already closed prior to this session, prevent creating new Form-E
            if (!empty($exportOrder->close_order_reason)) {
                $hasClosingCache = cache()->has("closing_export_order_{$exportOrder->id}");
                $alreadySavedClosedFormE = ExportFormE::where('export_order_id', $exportOrder->id)
                    ->where('created_at', '>=', $exportOrder->closed_at ?? $exportOrder->updated_at)
                    ->exists();

                if ($alreadySavedClosedFormE && !$hasClosingCache) {
                    return response()->json([
                        'success' => false,
                        'error' => 'This Export Order is already closed. No further Form-E can be created for it.',
                    ], 422);
                }
            }

            // Input quantity cannot exceed remaining allowed quantity (total + positive tolerance - used)
            $maxRemainingAllowed = $maxAllowed - $usedQuantity;
            if ($inputQty > ($maxRemainingAllowed + 0.0001)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Input quantity cannot exceed remaining allowed quantity (' . number_format($maxRemainingAllowed, 2) . ' MT).',
                ], 422);
            }

            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('export/form_e', 'public');
            }

            $exportOrder->refresh();
            $isClosingFormE = !empty($exportOrder->close_order_reason);

            $exportSnapshot = $exportOrder->toArray();
            $exportSnapshot['is_closing_form_e'] = $isClosingFormE;

            $formE = ExportFormE::create([
                'export_order_id' => $request->export_order_id,
                'buyer_id' => $request->buyer_id,
                'job_order_id' => $request->job_order_id,
                'form_e_no' => $request->form_e_no,
                'form_e_date' => $request->form_e_date,
                'attachment' => $attachmentPath,
                'total_quantity' => $totalQuantity,
                'remaining_quantity' => max(0, $totalQuantity - $totalWithCurrent),
                'input_quantity' => $request->input_quantity,
                'export_snapshot' => $exportSnapshot,
                'created_by' => auth()->user() ? auth()->user()->id : null,
            ]);

            cache()->forget("closing_export_order_{$exportOrder->id}");

            DB::commit();

            return response()->json([
                'success' => 'Export Form-E created successfully',
                'data' => $formE,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): View
    {
        try {
            $formE = ExportFormE::with(['exportOrder', 'buyer', 'jobOrder'])->findOrFail($id);
            $exportOrderData = $formE->export_snapshot;
        } catch (\Exception $e) {
            $formE = new ExportFormE();
            $exportOrderData = [];
        }

        return view('management.export.form-e.show', compact('formE', 'exportOrderData'));
    }

    public function edit($id): View
    {
        $products = $bagTypes = $bagPackings = $brands = $bagColors = $users = $banks = $brokers = $incoterms = $modeofterms = $modeoftransport = $countries = $ports = $hscodes = $currencies = $exportSodas = $quotations = collect();

        try {
            $formE = ExportFormE::findOrFail($id);
            $buyers = Customer::get();
            $job_orders = $this->jobOrdersWithBalance($formE->export_order_id, $formE->id, $formE->job_order_id);
            $export_orders = ExportOrder::where('am_approval_status', 'approved')
                ->whereNotIn('id', function($q) {
                    $q->select('export_order_id')->from('export_order_addendums');
                })
                ->latest()->get();
        } catch (QueryException $e) {
            $formE = new ExportFormE();
            $buyers = collect();
            $job_orders = collect();
            $export_orders = collect();
        }

        return view('management.export.form-e.edit', compact(
            'formE',
            'buyers',
            'job_orders',
            'export_orders',
            'products',
            'bagTypes',
            'bagPackings',
            'brands',
            'bagColors',
            'users',
            'banks',
            'brokers',
            'incoterms',
            'modeofterms',
            'modeoftransport',
            'countries',
            'ports',
            'hscodes',
            'currencies',
            'exportSodas',
            'quotations'
        ));
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $formE = ExportFormE::lockForUpdate()->find($id);

            if (!$formE) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Form-E already deleted or not found.',
                ], 404);
            }

            $request->validate([
                'form_e_no' => 'required|string|unique:export_form_es,form_e_no,' . $id,
                'form_e_date' => 'required|date',
                'input_quantity' => 'required|numeric|min:0.01',
                'job_order_id' => 'nullable|exists:job_orders,id',
            ]);

            // Form-E quantity cannot exceed what is left on the job order (this Form-E's own quantity excluded)
            $jobOrderError = $this->jobOrderCapacityError(
                $request->has('job_order_id') ? $request->job_order_id : $formE->job_order_id,
                (float) ($request->input_quantity ?? $formE->input_quantity),
                $formE->id
            );
            if ($jobOrderError) {
                DB::rollBack();

                return response()->json([
                    'success' => $jobOrderError,
                    'error' => $jobOrderError,
                ], 422);
            }

            // Re-calculate quantities if they update input_quantity. 
            // The requirement only says to create multi Form-E but not exceed. 
            // If they update, we should check again.

            if ($request->has('input_quantity')) {
                $exportOrder = ExportOrder::where('am_approval_status', 'approved')
                    ->whereNotIn('id', function($q) {
                        $q->select('export_order_id')->from('export_order_addendums');
                    })
                    ->with(['packingItems'])->findOrFail($formE->export_order_id);
                $totalQuantity = 0;
                foreach ($exportOrder->packingItems as $item) {
                    $totalQuantity += (float) ($item->metric_tons ?? 0);
                }

                // Used qty excluding this formE instance
                $usedQuantity = 0;
                try {
                    $usedQuantity = ExportFormE::where('export_order_id', $exportOrder->id)
                        ->where('id', '!=', $id)
                        ->sum('input_quantity');
                } catch (\Exception $e) {
                    $usedQuantity = 0;
                }

                $remainingQuantity = $totalQuantity - $usedQuantity;

                // Same rule as store: positive tolerance is allowed on top of the remaining quantity
                $positiveTolerance = (float) ($exportOrder->positive_tolerance ?? 0);
                $maxRemainingAllowed = $remainingQuantity + ($totalQuantity * ($positiveTolerance / 100));

                if ($request->input_quantity > $maxRemainingAllowed + 0.0001) {
                    DB::rollBack();

                    return response()->json([
                        'success' => 'Input quantity cannot exceed remaining allowed quantity (' . number_format($maxRemainingAllowed, 2) . ' MT).',
                    ], 422);
                }

                $data = $request->only(['input_quantity', 'buyer_id', 'job_order_id', 'form_e_no', 'form_e_date']);
                if ($request->hasFile('attachment')) {
                    if ($formE->attachment) {
                        Storage::disk('public')->delete($formE->attachment);
                    }
                    $data['attachment'] = $request->file('attachment')->store('export/form_e', 'public');
                }

                $formE->update(array_merge($data, [
                    'remaining_quantity' => max(0, $remainingQuantity - $request->input_quantity),
                ]));
            } else {
                $data = $request->only(['buyer_id', 'job_order_id', 'form_e_no', 'form_e_date']);
                if ($request->hasFile('attachment')) {
                    if ($formE->attachment) {
                        Storage::disk('public')->delete($formE->attachment);
                    }
                    $data['attachment'] = $request->file('attachment')->store('export/form_e', 'public');
                }
                $formE->update($data);
            }

            DB::commit();

            return response()->json([
                'success' => 'Export Form-E updated successfully',
                'data' => $formE
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $formE = ExportFormE::lockForUpdate()->find($id);

            if (!$formE) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Form-E already deleted or not found.',
                ], 404);
            }

            $exportOrder = null;
            if ($formE->export_order_id) {
                $exportOrder = ExportOrder::lockForUpdate()->find($formE->export_order_id);
            }

            // Only reset export order close columns if THIS specific Form-E was the closing Form-E
            if ($exportOrder && !empty($exportOrder->close_order_reason)) {
                $snapshot = is_array($formE->export_snapshot)
                    ? $formE->export_snapshot
                    : json_decode($formE->export_snapshot ?? '[]', true);

                $isClosingFormE = false;
                if (!empty($snapshot['is_closing_form_e'])) {
                    $isClosingFormE = true;
                } elseif (!empty($snapshot['close_order_reason'])) {
                    $isClosingFormE = true;
                }

                if ($isClosingFormE) {
                    $exportOrder->update([
                        'close_order_reason' => null,
                        'closed_by' => null,
                        'closed_at' => null,
                    ]);
                }
            }

            if ($formE->attachment) {
                Storage::disk('public')->delete($formE->attachment);
            }

            $orderId = $formE->export_order_id;

            $formE->delete();

            if ($orderId) {
                cache()->forget("closing_export_order_{$orderId}");
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Export Form-E deleted successfully.'
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Form-E'
            ], 500);
        }
    }

    public function getOrdersByBuyer($buyer_id)
    {
        try {
            $export_orders = ExportOrder::with(['packingItems'])
                ->where('id', '>', 0)
                ->where('cancel','!=',1)
                ->where('buyer_id', $buyer_id)
                ->where('am_approval_status', 'approved')
                ->whereNotIn('id', function($q) {
                    $q->select('export_order_id')->from('export_order_addendums');
                })
                ->whereNull('close_order_reason')
                ->latest()->get();

            $filtered_orders = $export_orders->filter(function ($order) {
                if (!empty($order->close_order_reason)) {
                    return false;
                }

                // Total quantity of all packing items
                $totalQuantity = 0;
                foreach ($order->packingItems as $item) {
                    $totalQuantity += (float) ($item->metric_tons ?? 0);
                }

                // Sum of input_quantity from existing Form-E
                $usedQuantity = 0;
                try {
                    $usedQuantity = ExportFormE::where('export_order_id', $order->id)->sum('input_quantity');
                } catch (\Exception $e) {
                    $usedQuantity = 0;
                }

                return ($totalQuantity - $usedQuantity) > 0.001; // Filter out if fully used
            })->values();

            return response()->json([
                'success' => true,
                'data' => $filtered_orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFormEsByOrder($order_id)
    {
        try {
            $formEs = ExportFormE::where('export_order_id', $order_id)->latest()->get();

            // MT already used per Form-E by delivery orders that are not cancelled
            $consumedByFormE = \App\Models\Export\ExportDeliveryOrder::with('exportPackingItems')
                ->whereIn('export_form_e_id', $formEs->pluck('id'))
                ->notCancelled()
                ->get()
                ->groupBy('export_form_e_id')
                ->map(fn($dos) => $dos->sum(fn($do) => $do->exportPackingItems->sum('metric_tons')));

            // Only Form-Es that still have capacity left
            $formEs = $formEs
                ->map(function ($formE) use ($consumedByFormE) {
                    $consumed = (float) ($consumedByFormE[$formE->id] ?? 0);
                    $formE->consumed_mt = round($consumed, 2);
                    $formE->remaining_mt = round(max(0, (float) $formE->input_quantity - $consumed), 2);
                    return $formE;
                })
                ->filter(fn($formE) => $formE->remaining_mt > 0.001)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $formEs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getJobOrdersByOrder($order_id)
    {
        try {
            // Only job orders that still have capacity left
            $jobOrders = $this->jobOrdersWithBalance($order_id);
            return response()->json([
                'success' => true,
                'data' => $jobOrders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Max MT a job order can take in Form-E: its capacity plus the export order's positive tolerance.
     */
    private function jobOrderAllowedCapacity(float $capacity, $exportOrder): float
    {
        $positiveTolerance = (float) ($exportOrder->positive_tolerance ?? 0);

        return $capacity + ($capacity * ($positiveTolerance / 100));
    }

    /**
     * Job orders of an export order with their capacity, used and remaining MT (remaining includes positive tolerance).
     * Fully used job orders are left out, except $keepJobOrderId (the one already selected on an edit).
     */
    private function jobOrdersWithBalance($exportOrderId, $exceptFormEId = null, $keepJobOrderId = null)
    {
        $exportOrder = ExportOrder::find($exportOrderId);

        $jobOrders = JobOrder::with('packingItems')
            ->where('export_order_id', $exportOrderId)
            ->latest()
            ->get();

        $usedByJobOrder = ExportFormE::whereIn('job_order_id', $jobOrders->pluck('id'))
            ->when($exceptFormEId, fn($q) => $q->where('id', '!=', $exceptFormEId))
            ->groupBy('job_order_id')
            ->selectRaw('job_order_id, SUM(input_quantity) as used_quantity')
            ->pluck('used_quantity', 'job_order_id');

        return $jobOrders
            ->map(function ($jobOrder) use ($usedByJobOrder, $exportOrder) {
                $capacity = (float) $jobOrder->packingItems->sum('metric_tons');
                $allowed = $this->jobOrderAllowedCapacity($capacity, $exportOrder);
                $used = (float) ($usedByJobOrder[$jobOrder->id] ?? 0);

                $jobOrder->capacity_mt = round($capacity, 2);
                $jobOrder->allowed_mt = round($allowed, 2);
                $jobOrder->used_mt = round($used, 2);
                $jobOrder->remaining_mt = round(max(0, $allowed - $used), 2);
                $jobOrder->unsetRelation('packingItems');

                return $jobOrder;
            })
            ->filter(fn($jobOrder) => $jobOrder->remaining_mt > 0 || $jobOrder->id == $keepJobOrderId)
            ->values();
    }

    /**
     * Returns an error message when the quantity is more than the job order has left, otherwise null.
     */
    private function jobOrderCapacityError($jobOrderId, float $inputQty, $exceptFormEId = null): ?string
    {
        if (!$jobOrderId) {
            return null;
        }

        // Lock the job order so two Form-Es saved at the same time cannot both pass the check
        $jobOrder = JobOrder::with(['packingItems', 'exportOrder'])->lockForUpdate()->find($jobOrderId);
        if (!$jobOrder) {
            return null;
        }

        $capacity = (float) $jobOrder->packingItems->sum('metric_tons');
        $allowed = $this->jobOrderAllowedCapacity($capacity, $jobOrder->exportOrder);
        $used = (float) ExportFormE::where('job_order_id', $jobOrderId)
            ->when($exceptFormEId, fn($q) => $q->where('id', '!=', $exceptFormEId))
            ->sum('input_quantity');
        $remaining = max(0, $allowed - $used);

        if ($inputQty > $remaining + 0.0001) {
            $positiveTolerance = (float) ($jobOrder->exportOrder->positive_tolerance ?? 0);

            return 'Job Order ' . $jobOrder->job_order_no . ' capacity is ' . number_format($capacity, 2)
                . ' MT (' . number_format($allowed, 2) . ' MT with +' . rtrim(rtrim(number_format($positiveTolerance, 2), '0'), '.') . '% tolerance) and '
                . number_format($used, 2) . ' MT is already used in Form-E. Input quantity cannot exceed '
                . number_format($remaining, 2) . ' MT.';
        }

        return null;
    }

    public function getTollrangeById(Request $request)
    {
        $order_id = $request->order_id;
        $qty = $request->qty;

        $exportOrder = ExportOrder::with('packingItems')->findOrFail($order_id);

        $totalMetricTon = (float) $exportOrder->packingItems->sum('metric_tons');

        $positive_tolleranfe_qty = $totalMetricTon * (($exportOrder->positive_tolerance ?? 0) / 100);
        $negative_tolleranfe_qty = $totalMetricTon * (($exportOrder->negative_tolerance ?? 0) / 100);

        $maxAllowed = $positive_tolleranfe_qty + $totalMetricTon;
        $minAllowed = $totalMetricTon - $negative_tolleranfe_qty;

        $usedQuantity = (float) ExportFormE::where('export_order_id', $order_id)->sum('input_quantity');
        $minRemaining = max(0, $minAllowed - $usedQuantity);
        $maxRemaining = max(0, $maxAllowed - $usedQuantity);

        return [
            $maxRemaining,
            $minRemaining,
            'min' => $minRemaining,
            'max' => $maxRemaining,
            'min_remaining' => $minRemaining,
            'max_remaining' => $maxRemaining,
            'overall_min' => $minAllowed,
            'overall_max' => $maxAllowed,
            'used_quantity' => $usedQuantity
        ];
    }

    public function closeExportOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:export_orders,id',
            'reason' => 'required|string',
            'input_quantity' => 'required|numeric|min:0.01',
        ]);

        $exportOrder = ExportOrder::with('packingItems')->findOrFail($request->order_id);

        $totalMetricTon = (float) $exportOrder->packingItems->sum('metric_tons');
        $posTol = (float) ($exportOrder->positive_tolerance ?? 0);
        $negTol = (float) ($exportOrder->negative_tolerance ?? 0);

        $maxAllowed = $totalMetricTon + ($totalMetricTon * ($posTol / 100));
        $minAllowed = $totalMetricTon - ($totalMetricTon * ($negTol / 100));

        $inputQty = (float) $request->input_quantity;
        $usedQuantity = (float) ExportFormE::where('export_order_id', $exportOrder->id)->sum('input_quantity');

        // Remaining tolerance range for current input quantity:
        $minRemaining = max(0, $minAllowed - $usedQuantity);
        $maxRemaining = max(0, $maxAllowed - $usedQuantity);

        if ($inputQty < $minRemaining || $inputQty > $maxRemaining) {
            return response()->json([
                'success' => false,
                'message' => "Input quantity ({$inputQty} MT) does not fall within the allowed closing tolerance range (" . number_format($minRemaining, 2) . " MT - " . number_format($maxRemaining, 2) . " MT).",
            ], 422);
        }

        $exportOrder->update([
            'close_order_reason' => $request->reason,
            'closed_by' => auth()->user()->id,
            'closed_at' => now()->toDateString(),
        ]);

        // Cache marker for currently closing order session
        cache()->put("closing_export_order_{$exportOrder->id}", auth()->user()->id ?? 1, now()->addMinutes(15));

        return response()->json([
            'success' => true,
            'message' => 'Export Order #' . $exportOrder->voucher_no . ' closed successfully.',
        ]);
    }
}
