<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportDeliveryOrder;
use App\Models\Export\ExportFirstWeighbridge;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\ArrivalTruckType;
use App\Models\Master\WeighbridgeAmount;
use App\Models\Sales\LoadingProgramItem;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class ExportFirstWeighBridgeController extends Controller
{
    public function index()
    {
        return view('management.export.first-weighbridge.index');
    }

    public function getList(Request $request)
    {
        $FirstWeighbridges = ExportFirstWeighbridge::with([
            'loadingProgramItem.deliveryOrders.customer',
            'loadingProgramItem.deliveryOrders.exportOrder.product',
            'loadingProgramItem.exportOrders.product',
            'loadingProgramItem',
        ])
            ->whereHas('loadingProgramItem.loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('loadingProgramItem', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                            ->orWhere('truck_number', 'like', $searchTerm);
                    })->orWhereHas('loadingProgramItem.deliveryOrders', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('loadingProgramItem.exportOrders', function ($query) use ($searchTerm) {
                        $query->where('voucher_no', 'like', $searchTerm)
                            ->orWhere('contract_no', 'like', $searchTerm);
                    });
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.first-weighbridge.getList', compact('FirstWeighbridges'));
    }

    public function create()
    {
        $data = [
            'ArrivalTruckTypes' => ArrivalTruckType::where('status', 'active')->get(),
            'Tickets' => LoadingProgramItem::whereHas('loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })
                ->whereHas('exportQc', function ($query) {
                    $query->where('status', 'accept')
                        ->orWhere(function ($approvalQuery) {
                            $approvalQuery->where('status', 'reject')
                                ->where('am_approval_status', 'rejected');
                        });
                })
                ->whereDoesntHave('exportFirstWeighbridge')
                ->with($this->ticketRelations())
                ->get(),
        ];

        return view('management.export.first-weighbridge.create', $data);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'loading_program_item_id' => 'required|exists:loading_program_items,id',
                'first_weight' => 'required|numeric',
                'truck_type_id' => 'required|exists:arrival_truck_types,id',
                'remark' => 'nullable|string',
                'weighbridge_amount' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })->with($this->ticketRelations())->findOrFail($request->loading_program_item_id);

            $existingFirstWeighbridge = ExportFirstWeighbridge::where('loading_program_item_id', $request->loading_program_item_id)->first();
            if ($existingFirstWeighbridge) {
                return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a first weighbridge.']], 422);
            }

            $deliveryOrders = $this->resolveDeliveryOrders($loadingProgramItem);

            $payload = $request->except('delivery_order_id');
            $payload['created_by'] = auth()->user()->id;
            $payload['company_id'] = $request->company_id;

            $companyLocationId = $this->resolveCompanyLocationId($loadingProgramItem, $deliveryOrders);

            if ($companyLocationId) {
                $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $payload['truck_type_id'])
                    ->where('company_location_id', $companyLocationId)
                    ->first();

                if (!$weighbridgeAmount) {
                    return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
                }

                $payload['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;
            } else {
                return response()->json(['errors' => ['truck_type_id' => 'Company location not found to fetch weighbridge amount.']], 422);
            }

            $firstWeighbridge = ExportFirstWeighbridge::create($payload);

            DB::commit();

            return response()->json(['success' => 'Export First Weighbridge created successfully.', 'data' => $firstWeighbridge], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $data['FirstWeighbridge'] = ExportFirstWeighbridge::with([
            'loadingProgramItem' => fn($query) => $query->with($this->ticketRelations()),
        ])->findOrFail($id);

        $data['ArrivalTruckTypes'] = ArrivalTruckType::where('status', 'active')->get();
        $ticketData = $this->buildTicketData($data['FirstWeighbridge']->loadingProgramItem);
        $data['DeliveryOrders'] = $ticketData['delivery_orders'];
        $data['ExportOrders'] = $ticketData['export_orders'];
        $data['factoryNames'] = $ticketData['factory_names'];
        $data['galaNames'] = $ticketData['gala_names'];

        return view('management.export.first-weighbridge.edit', $data);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'loading_program_item_id' => 'required|exists:loading_program_items,id',
                'first_weight' => 'required|numeric',
                'truck_type_id' => 'required|exists:arrival_truck_types,id',
                'remark' => 'nullable|string',
                'weighbridge_amount' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $firstWeighbridge = ExportFirstWeighbridge::lockForUpdate()->find($id);

            if (!$firstWeighbridge) {
                DB::rollBack();
                return response()->json([
                    'errors' => ['first_weighbridge' => 'Record already deleted or not found.']
                ], 404);
            }

            $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })->with($this->ticketRelations())->findOrFail($request->loading_program_item_id);

            $existingFirstWeighbridge = ExportFirstWeighbridge::where('loading_program_item_id', $request->loading_program_item_id)
                ->where('id', '!=', $id)
                ->first();
            if ($existingFirstWeighbridge) {
                return response()->json(['errors' => ['loading_program_item_id' => 'This ticket already has a first weighbridge.']], 422);
            }

            $firstWeighbridge = ExportFirstWeighbridge::findOrFail($id);
            $deliveryOrders = $this->resolveDeliveryOrders($loadingProgramItem);
            $payload = $request->except('delivery_order_id');
            $payload['company_id'] = $request->company_id;
            $companyLocationId = $this->resolveCompanyLocationId($loadingProgramItem, $deliveryOrders);

            if ($companyLocationId) {
                $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $payload['truck_type_id'])
                    ->where('company_location_id', $companyLocationId)
                    ->first();

                if (!$weighbridgeAmount) {
                    return response()->json(['errors' => ['truck_type_id' => 'Weighbridge amount not found for selected truck type and arrival location.']], 422);
                }

                $payload['weighbridge_amount'] = $weighbridgeAmount->weighbridge_amount;
            } else {
                return response()->json(['errors' => ['truck_type_id' => 'Company location not found to fetch weighbridge amount.']], 422);
            }

            $firstWeighbridge->update($payload);

            DB::commit();

            return response()->json(['success' => 'Export First Weighbridge updated successfully.', 'data' => $firstWeighbridge], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $firstWeighbridge = ExportFirstWeighbridge::lockForUpdate()->find($id);

            if (!$firstWeighbridge) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Record already deleted or not found.'
                ], 404);
            }

            $firstWeighbridge->delete();

            DB::commit();

            return response()->json([
                'success' => 'Export First Weighbridge deleted successfully.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Something went wrong.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFirstWeighbridgeRelatedData(Request $request)
    {
        $LoadingProgramItem = LoadingProgramItem::with($this->ticketRelations())
            ->whereHas('loadingProgram', function ($query) {
                $query->where('type', 'export_order');
            })
            ->findOrFail($request->loading_program_item_id);

        $ticketData = $this->buildTicketData($LoadingProgramItem);
        $DeliveryOrders = $ticketData['delivery_orders'];
        $ExportOrders = $ticketData['export_orders'];
        $factoryNames = $ticketData['factory_names'];
        $galaNames = $ticketData['gala_names'];
        $ArrivalTruckTypes = ArrivalTruckType::where('status', 'active')->get();

        $html = view('management.export.first-weighbridge.getFirstWeighbridgeRelatedData', compact('DeliveryOrders', 'ExportOrders', 'ArrivalTruckTypes', 'LoadingProgramItem', 'factoryNames', 'galaNames'))
            ->with('FirstWeighbridge', null)
            ->render();

        return response()->json(['success' => true, 'html' => $html]);
    }

    public function getWeighbridgeAmount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'truck_type_id' => 'required|exists:arrival_truck_types,id',
            'loading_program_item_id' => 'required|exists:loading_program_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgramItem = LoadingProgramItem::whereHas('loadingProgram', function ($query) {
            $query->where('type', 'export_order');
        })
            ->with($this->ticketRelations())
            ->findOrFail($request->loading_program_item_id);

        $deliveryOrders = $this->resolveDeliveryOrders($loadingProgramItem);
        $companyLocationId = $this->resolveCompanyLocationId($loadingProgramItem, $deliveryOrders);

        if (!$companyLocationId) {
            return response()->json([
                'success' => false,
                'message' => 'Company location not found to fetch weighbridge amount.',
            ]);
        }

        $weighbridgeAmount = WeighbridgeAmount::where('truck_type_id', $request->truck_type_id)
            ->where('company_location_id', $companyLocationId)
            ->first();

        if ($weighbridgeAmount) {
            return response()->json([
                'success' => true,
                'weighbridge_amount' => $weighbridgeAmount->weighbridge_amount,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Weighbridge amount not found for selected truck type and arrival location.',
        ]);
    }

    private function ticketRelations(): array
    {
        return [
            'exportLoadingProgram.deliveryOrder.customer',
            'exportLoadingProgram.deliveryOrder.exportOrder.product',
            'exportLoadingProgram.deliveryOrder.exportPackingItems',
            'exportLoadingProgram.deliveryOrder.locations',
            'exportLoadingProgram.deliveryOrder.locations.companyLocation',
            'exportLoadingProgram.deliveryOrders.customer',
            'exportLoadingProgram.deliveryOrders.exportOrder.product',
            'exportLoadingProgram.deliveryOrders.exportPackingItems',
            'exportLoadingProgram.deliveryOrders.locations',
            'exportLoadingProgram.deliveryOrders.locations.companyLocation',
            'exportLoadingProgram.exportOrder.product',
            'exportLoadingProgram.exportOrders.product',
            'deliveryOrders.customer',
            'deliveryOrders.exportOrder.product',
            'deliveryOrders.exportPackingItems',
            'deliveryOrders.locations',
            'deliveryOrders.locations.companyLocation',
            'exportOrders.product',
        ];
    }

    private function resolveDeliveryOrders(LoadingProgramItem $item): Collection
    {
        $deliveryOrderIds = collect();

        $linkedDOs = $item->exportLoadingProgram?->deliveryOrders?->where('am_approval_status', 'approved') ?? collect();
        if ($linkedDOs->isNotEmpty()) {
            $deliveryOrderIds = $deliveryOrderIds->merge($linkedDOs->pluck('id'));
        }

        if ($item->exportLoadingProgram?->deliveryOrder && $item->exportLoadingProgram->deliveryOrder->am_approval_status === 'approved') {
            $deliveryOrderIds->push($item->exportLoadingProgram->deliveryOrder->id);
        }

        $ticketDOs = $item->deliveryOrders->where('type', 'export_order')->where('am_approval_status', 'approved');
        if ($ticketDOs->isNotEmpty()) {
            $deliveryOrderIds = $deliveryOrderIds->merge($ticketDOs->pluck('id'));
        }

        $deliveryOrderIds = $deliveryOrderIds->filter()->unique()->values();

        if ($deliveryOrderIds->isEmpty()) {
            return collect();
        }

        return ExportDeliveryOrder::with([
            'customer',
            'exportOrder.product',
            'exportPackingItems',
            'locations',
            'locations.companyLocation',
        ])->whereIn('id', $deliveryOrderIds)->get();
    }

    private function buildTicketData(LoadingProgramItem $item): array
    {
        $deliveryOrders = $this->resolveDeliveryOrders($item);
        $exportOrders = $item->exportOrders
            ->where('am_approval_status', 'approved')
            ->values();

        if ($exportOrders->isEmpty() && $item->exportLoadingProgram?->exportOrders?->isNotEmpty()) {
            $exportOrders = $item->exportLoadingProgram->exportOrders
                ->where('am_approval_status', 'approved')
                ->values();
        }

        if (
            $exportOrders->isEmpty()
            && $item->exportLoadingProgram?->exportOrder
            && $item->exportLoadingProgram->exportOrder->am_approval_status === 'approved'
        ) {
            $exportOrders = collect([$item->exportLoadingProgram->exportOrder]);
        }

        return [
            'delivery_orders' => $deliveryOrders,
            'export_orders' => $exportOrders,
            'factory_names' => $this->getCombinedLocationNames($deliveryOrders, 'arrival_location_ids', ArrivalLocation::class),
            'gala_names' => $this->getCombinedLocationNames($deliveryOrders, 'sub_arrival_location_ids', ArrivalSubLocation::class),
        ];
    }

    private function resolveCompanyLocationId(LoadingProgramItem $item, Collection $deliveryOrders): ?int
    {
        $companyLocationId = $deliveryOrders->flatMap(function ($deliveryOrder) {
            return collect($deliveryOrder->locations ?? [])->pluck('company_location_id');
        })->filter()->first();

        if ($companyLocationId) {
            return (int) $companyLocationId;
        }

        $companyLocationIds = $item->exportLoadingProgram?->company_locations
            ?? $item->loadingProgram?->company_locations
            ?? [];

        return is_array($companyLocationIds) ? ($companyLocationIds[0] ?? null) : $companyLocationIds;
    }

    private function getCombinedLocationNames(Collection $deliveryOrders, string $column, string $modelClass): array
    {
        $ids = $deliveryOrders->flatMap(function ($deliveryOrder) use ($column) {
            return collect($deliveryOrder->locations ?? [])->flatMap(function ($location) use ($column) {
                return explode(',', (string) ($location->{$column} ?? ''));
            });
        })->map(fn($id) => trim($id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        return $modelClass::whereIn('id', $ids)->pluck('name')->toArray();
    }
}
