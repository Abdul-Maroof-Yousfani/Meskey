<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportLoadingSlip;
use App\Models\Export\ExportSecondWeighbridge;
use App\Models\Sales\SecondWeighbridgeItem;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExportSecondWeighBridgeController extends Controller
{
    public function index()
    {
        return view('management.export.second-weighbridge.index');
    }

    public function getList(Request $request)
    {
        $SecondWeighbridges = ExportSecondWeighbridge::with([
            'loadingSlip.loadingProgramItem',
            'truckType',
        ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('loadingSlip.loadingProgramItem', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                            ->orWhere('truck_number', 'like', $searchTerm);
                    });
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.second-weighbridge.getList', compact('SecondWeighbridges'));
    }

    public function create()
    {
        $LoadingSlips = ExportLoadingSlip::whereDoesntHave('secondWeighbridge')
            ->whereHas('loadingProgramItem.exportDispatchQcs', function ($query) {
                $query->where('status', 'accept');
            })
            ->with([
                'loadingProgramItem',
                'loadingProgramItem.exportLoadingProgram.deliveryOrder.exportOrder',
                'loadingProgramItem.exportLoadingProgram.deliveryOrders.exportOrder',
                'loadingProgramItem.exportOrders',
                'loadingProgramItem.deliveryOrders.exportOrder',
            ])
            ->get();

        return view('management.export.second-weighbridge.create', compact('LoadingSlips'));
    }

    public function store(Request $request)
    {
        $loadingSlip = ExportLoadingSlip::with([
            'loadingProgramItem.exportFirstWeighbridge',
            'loadingProgramItem.deliveryOrders.exportPackingItems',
            'loadingProgramItem.exportOrders',
            'loadingProgramItem.exportLoadingProgram.exportOrder',
            'loadingProgramItem.exportLoadingProgram.exportOrders',
        ])->find($request->loading_slip_id);

        if (!$loadingSlip) {
            return response()->json(['errors' => ['loading_slip_id' => 'Loading slip not found.']], 422);
        }

        $validationRules = [
            'loading_slip_id' => 'required|exists:loading_slips,id',
            'second_weight' => 'required|numeric',
            'remark' => 'nullable|string',
        ];

        if (!$loadingSlip->delivery_order_id) {
            $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $firstWeighbridge = $loadingSlip->loadingProgramItem->exportFirstWeighbridge;
        if (!$firstWeighbridge) {
            return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
        }

        $first_weight = $firstWeighbridge->first_weight;
        $second_weight = $request->second_weight;
        $net_weight = $second_weight - $first_weight;

        if ($second_weight < $first_weight) {
            return response()->json('Second Weight can not be less than First Weight', 422);
        }

        $balance = get_second_weighbridge_balance_kg($loadingSlip);
        if ($net_weight > $balance) {
            return response()->json('Your total remaining net weight balance for all associated DOs on this ticket is: ' . number_format($balance, 2), 422);
        }

        if (!$loadingSlip->delivery_order_id && $request->delivery_order_id) {
            $loadingSlip->update(['delivery_order_id' => $request->delivery_order_id]);
            if ($loadingSlip->loadingProgramItem->exportQc) {
                $loadingSlip->loadingProgramItem->exportQc->update(['delivery_order_id' => $request->delivery_order_id]);
            }
            if ($loadingSlip->loadingProgramItem->exportFirstWeighbridge) {
                $loadingSlip->loadingProgramItem->exportFirstWeighbridge->update(['delivery_order_id' => $request->delivery_order_id]);
            }
        }

        $requestData = $request->all();
        $requestData['created_by'] = auth()->user()->id;
        $requestData['first_weight'] = $first_weight;
        $requestData['net_weight'] = $net_weight;
        $requestData['balance_kg'] = $balance - $net_weight;
        $requestData['delivery_order_id'] = $loadingSlip->delivery_order_id ?: $request->delivery_order_id;

        $secondWeighbridge = ExportSecondWeighbridge::create($requestData);

        $remainingWeight = $net_weight;
        $deliveryOrders = $this->resolveDeliveryOrders($loadingSlip->loadingProgramItem);

        foreach ($deliveryOrders as $do) {
            if ($remainingWeight <= 0) {
                break;
            }

            $doBalance = get_second_weighbridge_balance_by_delivery_order_kg($do->id);
            if ($doBalance > 0) {
                $deduct = min($doBalance, $remainingWeight);
                SecondWeighbridgeItem::create([
                    'second_weighbridge_id' => $secondWeighbridge->id,
                    'delivery_order_id' => $do->id,
                    'net_weight' => $deduct,
                ]);
                $remainingWeight -= $deduct;
            }
        }

        return response()->json(['success' => 'Export Second Weighbridge created successfully.', 'data' => $secondWeighbridge], 201);
    }

    public function edit($id)
    {
        $data['SecondWeighbridge'] = ExportSecondWeighbridge::with([
            'loadingSlip.loadingProgramItem.deliveryOrders.customer',
            'loadingSlip.loadingProgramItem.deliveryOrders.exportPackingItems',
            'loadingSlip.loadingProgramItem.deliveryOrders.exportOrder',
            'loadingSlip.loadingProgramItem.deliveryOrders.arrivalLocation',
            'loadingSlip.loadingProgramItem.deliveryOrders.subArrivalLocation',
            'loadingSlip.loadingProgramItem.exportLoadingProgram.deliveryOrders.customer',
            'loadingSlip.loadingProgramItem.exportLoadingProgram.deliveryOrders.exportPackingItems',
            'loadingSlip.loadingProgramItem.exportLoadingProgram.deliveryOrder.customer',
            'loadingSlip.loadingProgramItem.exportLoadingProgram.deliveryOrder.exportPackingItems',
            'loadingSlip.loadingProgramItem.exportOrders.product',
            'loadingSlip.loadingProgramItem.exportOrders.buyer',
        ])->findOrFail($id);

        $data['LoadingSlips'] = ExportLoadingSlip::where(function ($q) use ($data) {
            $q->whereDoesntHave('secondWeighbridge')
                ->whereHas('loadingProgramItem.exportDispatchQcs', function ($query) {
                    $query->where('status', 'accept');
                });
        })
            ->orWhere('id', $data['SecondWeighbridge']->loading_slip_id)
            ->with([
                'loadingProgramItem.deliveryOrders.customer',
                'loadingProgramItem.deliveryOrders.exportPackingItems',
                'loadingProgramItem.deliveryOrders.exportOrder',
                'loadingProgramItem.deliveryOrders.arrivalLocation',
                'loadingProgramItem.deliveryOrders.subArrivalLocation',
                'loadingProgramItem.exportOrders.product',
                'loadingProgramItem.exportOrders.buyer',
            ])
            ->get();

        $loadingSlip = $data['SecondWeighbridge']->loadingSlip;
        $data['needsDeliveryOrder'] = !$loadingSlip->delivery_order_id;
        $data['deliveryOrders'] = collect();

        if ($data['needsDeliveryOrder']) {
            $exportOrderIds = $loadingSlip->loadingProgramItem->exportOrders
                ->where('am_approval_status', 'approved')
                ->pluck('id')
                ->toArray();
            if (empty($exportOrderIds) && $loadingSlip->loadingProgramItem->exportLoadingProgram?->exportOrders?->isNotEmpty()) {
                $exportOrderIds = $loadingSlip->loadingProgramItem->exportLoadingProgram->exportOrders
                    ->where('am_approval_status', 'approved')
                    ->pluck('id')
                    ->toArray();
            }
            if (
                empty($exportOrderIds)
                && $loadingSlip->loadingProgramItem->exportLoadingProgram?->exportOrder
                && $loadingSlip->loadingProgramItem->exportLoadingProgram->exportOrder->am_approval_status === 'approved'
            ) {
                $exportOrderIds = [$loadingSlip->loadingProgramItem->exportLoadingProgram->exportOrder->id];
            }

            if (!empty($exportOrderIds)) {
                $data['deliveryOrders'] = ExportDeliveryOrder::whereIn('export_order_id', $exportOrderIds)
                    ->where('am_approval_status', 'approved')
                    ->with('customer')
                    ->get();
            }
        }
        
        $data['all_delivery_orders'] = $this->resolveDeliveryOrders($loadingSlip->loadingProgramItem);

        return view('management.export.second-weighbridge.edit', $data);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $secondWeighbridge = ExportSecondWeighbridge::lockForUpdate()->find($id);

            if (!$secondWeighbridge) {
                DB::rollBack();
                return response()->json([
                    'errors' => 'Export Second Weighbridge already deleted or not found.'
                ], 404);
            }

            $loadingSlip = ExportLoadingSlip::with('loadingProgramItem.exportFirstWeighbridge')->find($request->loading_slip_id);
            if (!$loadingSlip) {
                DB::rollBack();
                return response()->json(['errors' => ['loading_slip_id' => 'Loading slip not found.']], 422);
            }

            $validationRules = [
                'loading_slip_id' => 'required|exists:loading_slips,id',
                'second_weight' => 'required|numeric',
                'remark' => 'nullable|string',
            ];

            if (!$loadingSlip->delivery_order_id) {
                $validationRules['delivery_order_id'] = 'required|exists:delivery_order,id';
            }

            $validator = Validator::make($request->all(), $validationRules);
            if ($validator->fails()) {
                DB::rollBack();
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $firstWeighbridge = $loadingSlip->loadingProgramItem->exportFirstWeighbridge;
            if (!$firstWeighbridge) {
                DB::rollBack();
                return response()->json(['errors' => ['loading_slip_id' => 'First weighbridge not found for this loading slip.']], 422);
            }

            $first_weight = $firstWeighbridge->first_weight;
            $second_weight = $request->second_weight;
            $net_weight = $second_weight - $first_weight;

            if ($second_weight < $first_weight) {
                DB::rollBack();
                return response()->json('Second Weight can not be less than First Weight', 422);
            }

            $current_balance = get_second_weighbridge_balance_kg($loadingSlip);
            $available_balance = $current_balance + $secondWeighbridge->net_weight;

            if ($net_weight > $available_balance) {
                DB::rollBack();
                return response()->json('Your total remaining net weight balance for all associated DOs on this ticket is: ' . number_format($available_balance, 2), 422);
            }

            if (!$loadingSlip->delivery_order_id && $request->delivery_order_id) {
                $loadingSlip->update(['delivery_order_id' => $request->delivery_order_id]);
            }

            $updateData = $request->all();
            $updateData['first_weight'] = $first_weight;
            $updateData['net_weight'] = $net_weight;
            $updateData['balance_kg'] = $available_balance - $net_weight;

            $secondWeighbridge->update($updateData);

            $secondWeighbridge->items()->delete();
            $remainingWeight = $net_weight;
            $deliveryOrders = $this->resolveDeliveryOrders($loadingSlip->loadingProgramItem);

            foreach ($deliveryOrders as $do) {
                if ($remainingWeight <= 0) {
                    break;
                }

                $doBalance = get_second_weighbridge_balance_by_delivery_order_kg($do->id);
                if ($doBalance > 0) {
                    $deduct = min($doBalance, $remainingWeight);
                    SecondWeighbridgeItem::create([
                        'second_weighbridge_id' => $secondWeighbridge->id,
                        'delivery_order_id' => $do->id,
                        'net_weight' => $deduct,
                    ]);
                    $remainingWeight -= $deduct;
                }
            }

            DB::commit();

            return response()->json(['success' => 'Export Second Weighbridge updated successfully.', 'data' => $secondWeighbridge], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $secondWeighbridge = ExportSecondWeighbridge::lockForUpdate()->find($id);

            if (!$secondWeighbridge) {
                DB::rollBack();
                return response()->json([
                    'success' => 'Export Second Weighbridge already deleted or not found.'
                ], 404);
            }

            $secondWeighbridge->items()->delete();
            $secondWeighbridge->delete();

            DB::commit();

            return response()->json([
                'success' => 'Export Second Weighbridge deleted successfully.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSecondWeighbridgeRelatedData(Request $request)
    {
        $LoadingSlip = ExportLoadingSlip::with([
            'loadingProgramItem.exportLoadingProgram.deliveryOrder.customer',
            'loadingProgramItem.exportLoadingProgram.deliveryOrder.exportOrder',
            'loadingProgramItem.exportLoadingProgram.deliveryOrder.exportPackingItems',
            'loadingProgramItem.exportLoadingProgram.deliveryOrders.customer',
            'loadingProgramItem.exportLoadingProgram.deliveryOrders.exportPackingItems',
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.exportPackingItems',
            'loadingProgramItem.deliveryOrders.arrivalLocation',
            'loadingProgramItem.deliveryOrders.subArrivalLocation',
            'loadingProgramItem.exportOrders.product',
            'loadingProgramItem.exportOrders.buyer',
            'loadingProgramItem.exportLoadingProgram.exportOrder.product',
            'loadingProgramItem.exportLoadingProgram.exportOrder.buyer',
            'loadingProgramItem.exportLoadingProgram.exportOrders.product',
            'loadingProgramItem.exportLoadingProgram.exportOrders.buyer',
            'loadingProgramItem.exportFirstWeighbridge',
            'deliveryOrder',
        ])->findOrFail($request->loading_slip_id);

        $needsDeliveryOrder = !$LoadingSlip->delivery_order_id;
        $deliveryOrders = collect();

        if ($needsDeliveryOrder) {
            $exportOrderIds = $LoadingSlip->loadingProgramItem->exportOrders
                ->where('am_approval_status', 'approved')
                ->pluck('id')
                ->toArray();
            if (empty($exportOrderIds) && $LoadingSlip->loadingProgramItem->exportLoadingProgram?->exportOrders?->isNotEmpty()) {
                $exportOrderIds = $LoadingSlip->loadingProgramItem->exportLoadingProgram->exportOrders
                    ->where('am_approval_status', 'approved')
                    ->pluck('id')
                    ->toArray();
            }
            if (
                empty($exportOrderIds)
                && $LoadingSlip->loadingProgramItem->exportLoadingProgram?->exportOrder
                && $LoadingSlip->loadingProgramItem->exportLoadingProgram->exportOrder->am_approval_status === 'approved'
            ) {
                $exportOrderIds = [$LoadingSlip->loadingProgramItem->exportLoadingProgram->exportOrder->id];
            }

            if (!empty($exportOrderIds)) {
                $deliveryOrders = ExportDeliveryOrder::whereIn('export_order_id', $exportOrderIds)
                    ->where('am_approval_status', 'approved')
                    ->with('customer')
                    ->get();
            }
        }

        $html = view('management.export.second-weighbridge.getSecondWeighbridgeRelatedData', compact('LoadingSlip', 'needsDeliveryOrder', 'deliveryOrders'))->with('SecondWeighbridge', null)->render();

        return response()->json(['success' => true, 'html' => $html, 'needsDeliveryOrder' => $needsDeliveryOrder]);
    }

    public function getDeliveryOrdersByExportOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'export_order_id' => 'required|exists:export_orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $deliveryOrders = ExportDeliveryOrder::where('export_order_id', $request->export_order_id)
            ->where('am_approval_status', 'approved')
            ->with('customer')
            ->get();

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders,
        ]);
    }

    public function getBalanceAgainstSecondWeighbridge(Request $request)
    {
        return get_second_weighbridge_balance_by_delivery_order_kg($request->delivery_order_id);
    }

    private function resolveDeliveryOrders($item): \Illuminate\Support\Collection
    {
        if (!$item) {
            return collect();
        }

        $deliveryOrders = collect();

        // Get DOs from ticket
        $ticketDOs = $item->deliveryOrders()->withoutGlobalScopes()->get();
        if ($ticketDOs->isNotEmpty()) {
            $deliveryOrders = $deliveryOrders->merge($ticketDOs);
        }

        // Get DOs from LP
        if ($item->exportLoadingProgram) {
            $lpDOs = $item->exportLoadingProgram->deliveryOrders()->withoutGlobalScopes()->get();
            if ($lpDOs->isNotEmpty()) {
                $deliveryOrders = $deliveryOrders->merge($lpDOs);
            }
            if ($item->exportLoadingProgram->deliveryOrder) {
                $deliveryOrders->push($item->exportLoadingProgram->deliveryOrder);
            }
        }

        return $deliveryOrders->filter()
            ->where('type', 'export_order')
            ->unique('id')
            ->sortBy('id')
            ->values();
    }
}
