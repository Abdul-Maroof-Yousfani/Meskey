<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Export\ExportOrder;
use App\Models\Export\ExportDeliveryOrder as DeliveryOrder;
use App\Models\Export\ExportLoadingProgram as LoadingProgram;
use App\Models\Sales\LoadingProgramItem;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Master\Brands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class ExportLoadingProgramController extends Controller
{
    public function index()
    {
        return view('management.export.loading-program.index');
    }

    public function getList(Request $request)
    {
        $loadingPrograms = LoadingProgram::with([
                'exportOrder',
                'deliveryOrder',
                'deliveryOrders',
                'createdBy',
                'loadingProgramItems.arrivalLocation',
                'loadingProgramItems.subArrivalLocation',
                'loadingProgramItems.brand',
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereHas('exportOrder', function ($query) use ($searchTerm) {
                        $query->where('voucher_no', 'like', $searchTerm)
                            ->orWhere('contract_no', 'like', $searchTerm);
                    })->orWhereHas('deliveryOrder', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('deliveryOrders', function ($query) use ($searchTerm) {
                        $query->where('reference_no', 'like', $searchTerm);
                    })->orWhereHas('loadingProgramItems', function ($query) use ($searchTerm) {
                        $query->where('transaction_number', 'like', $searchTerm)
                              ->orWhere('truck_number', 'like', $searchTerm);
                    })->orWhere('id', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.loading-program.getList', compact('loadingPrograms'));
    }

    public function create()
    {
        return view('management.export.loading-program.create', [
            'Brands' => Brands::where('status', 1)->get(),
            'Transporters' => \App\Models\Master\Transporter::where('status', 'active')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validationRules = [
            'main_company_location_id' => 'required|exists:model_location,id',
            'export_order_id' => 'required|array|min:1',
            'export_order_id.*' => 'exists:export_orders,id',
            'delivery_order_id' => 'required|array|min:1',
            'delivery_order_id.*' => 'exists:delivery_order,id',
            'loading_program_items' => 'required|array|min:1',
            'loading_program_items.*.truck_number' => 'required|string|distinct',
            'loading_program_items.*.brand_id' => 'nullable|exists:brands,id',
            'loading_program_items.*.arrival_location_id' => 'required|exists:arrival_locations,id',
            'loading_program_items.*.sub_arrival_location_id' => 'required|exists:arrival_sub_locations,id',
            'remark' => 'nullable|string'
        ];

        foreach ($request->loading_program_items ?? [] as $index => $itemData) {
            $validationRules["loading_program_items.$index.delivery_order_id"] = 'required|array|min:1';
            $validationRules["loading_program_items.$index.delivery_order_id.*"] = 'exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exportOrders = ExportOrder::whereIn('id', $request->export_order_id)->get();
        $exportOrder = $exportOrders->first();
        $deliveryOrders = DeliveryOrder::whereIn('id', $request->delivery_order_id)->get();

        $companyLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->location_id))->filter()->unique()->toArray();
        $arrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->arrival_location_id))->filter()->unique()->toArray();
        $subArrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->sub_arrival_location_id))->filter()->unique()->toArray();

        DB::beginTransaction();
        try {
            $loadingProgram = LoadingProgram::create([
                'company_id' => $exportOrder->company_id,
                'export_order_id' => $exportOrder->id,
                'delivery_order_id' => $request->delivery_order_id[0] ?? null,
                'company_locations' => $companyLocationIds,
                'company_location_id' => $request->main_company_location_id,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark,
                'created_by' => auth()->user()->id,
            ]);

            // Sync main relationships
            $loadingProgram->exportOrders()->sync($request->export_order_id);
            if ($request->delivery_order_id) {
                $loadingProgram->deliveryOrders()->sync($request->delivery_order_id);
            }

            foreach ($request->loading_program_items as $index => $itemData) {
                $selectedDoIds = $itemData['delivery_order_id'] ?? [];
                $selectedEoIds = $itemData['export_order_id'] ?? [];

                foreach ($selectedDoIds as $do_id) {
                    $swbBalance = get_second_weighbridge_balance_by_delivery_order($do_id);
                    $balance = $swbBalance;
                    $qty = $itemData['qty'] ?? 0;

                    if ($balance < $qty) {
                        DB::rollBack();
                        return response()->json([
                            'errors' => ["loading_program_items.$index.qty" => ["Your available balance (taking Second Weighbridge into account) for DO $do_id is $balance, you can not exceed that balance."]]
                        ], 422);
                    }
                }

                $loadingProgramItem = LoadingProgramItem::create([
                    'loading_program_id' => $loadingProgram->id,
                    'transaction_number' => $this->getNumber($request),
                    'truck_number' => $itemData['truck_number'],
                    'container_number' => $itemData['container_number'] ?? null,
                    'packing' => $itemData['packing'] ?? null,
                    'brand_id' => $itemData['brand_id'] ?? null,
                    'arrival_location_id' => $itemData['arrival_location_id'],
                    'sub_arrival_location_id' => $itemData['sub_arrival_location_id'],
                    'driver_name' => $itemData['driver_name'] ?? null,
                    'contact_details' => $itemData['contact_details'] ?? null,
                    'transporter_id' => $itemData['transporter_id'] ?? null,
                    'qty' => $itemData['qty'] ?? 0,
                    'delivery_order_id' => $selectedDoIds[0] ?? null,
                ]);

                if (!empty($selectedEoIds)) {
                    $loadingProgramItem->exportOrders()->sync($selectedEoIds);
                } else {
                    // Default to main export order if row-level not selected
                    $loadingProgramItem->exportOrders()->sync($request->export_order_id);
                }
                
                if (!empty($selectedDoIds)) {
                    $loadingProgramItem->deliveryOrders()->sync($selectedDoIds);
                }
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Export Loading Program created successfully.', 'data' => $loadingProgram], 201);
    }

    public function show($id)
    {
        $loadingProgram = LoadingProgram::with([
            'loadingProgramItems.arrivalLocation',
            'loadingProgramItems.subArrivalLocation',
            'loadingProgramItems.brand',
            'loadingProgramItems.transporter',
            'loadingProgramItems.deliveryOrders',
            'exportOrder',
            'exportOrders.packingItems',
            'deliveryOrder',
            'deliveryOrders.exportPackingItems',
        ])->findOrFail($id);

        return view('management.export.loading-program.show', compact('loadingProgram'));
    }

    public function edit($id)
    {
        $loadingProgram = LoadingProgram::with([
            'loadingProgramItems.arrivalLocation',
            'loadingProgramItems.subArrivalLocation',
            'loadingProgramItems.transporter',
            'loadingProgramItems.exportOrders',
            'loadingProgramItems.deliveryOrders',
            'exportOrder',
            'exportOrders',
            'deliveryOrder',
            'deliveryOrders',
        ])->findOrFail($id);

        $selectedExportOrderIds = $loadingProgram->exportOrders->pluck('id')->toArray();
        $companyLocationIds = is_array($loadingProgram->company_locations)
            ? array_filter($loadingProgram->company_locations)
            : array_filter(explode(',', (string) $loadingProgram->company_locations));

        $ExportOrders = ExportOrder::query()
            ->with(['deliveryOrders' => function ($q) {
                $q->where('am_approval_status', 'approved');
            }])
            ->where(function ($query) use ($selectedExportOrderIds, $companyLocationIds) {
                if (!empty($selectedExportOrderIds)) {
                    $query->whereIn('id', $selectedExportOrderIds);
                }

                $query->orWhereHas('deliveryOrders', function ($q) use ($companyLocationIds) {
                    $q->where('am_approval_status', 'approved');

                    if (!empty($companyLocationIds)) {
                        $q->where(function ($locationQuery) use ($companyLocationIds) {
                            foreach ($companyLocationIds as $locationId) {
                                $locationQuery->orWhereRaw("FIND_IN_SET(?, location_id)", [$locationId]);
                            }
                        });
                    }
                });
            })
            ->get();

        $companyLocations = [];
        $arrivalLocations = [];
        $subArrivalLocations = [];

        $allDeliveryOrders = $loadingProgram->deliveryOrders;

        if ($allDeliveryOrders->count() > 0) {
            $deliveryOrderCompanyLocationIds = $allDeliveryOrders->flatMap(fn($do) => explode(',', $do->location_id))->filter()->unique()->toArray();
            $companyLocations = \App\Models\Master\CompanyLocation::whereIn('id', $deliveryOrderCompanyLocationIds)->get()->map(function ($location) {
                return ['id' => $location->id, 'text' => $location->name];
            })->toArray();

            $arrivalLocationIds = $allDeliveryOrders->flatMap(fn($do) => explode(',', $do->arrival_location_id))->filter()->unique()->toArray();
            $arrivalLocations = ArrivalLocation::whereIn('id', $arrivalLocationIds)->get()->map(function ($factory) {
                return ['id' => $factory->id, 'text' => $factory->name];
            })->toArray();

            $subArrivalLocationIds = $allDeliveryOrders->flatMap(fn($do) => explode(',', $do->sub_arrival_location_id))->filter()->unique()->toArray();
            $subArrivalLocations = ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get()->map(function ($section) {
                return ['id' => $section->id, 'text' => $section->name ?? 'N/A'];
            })->toArray();
        }

        $locations = [1 => $arrivalLocations, 2 => $subArrivalLocations];
        $loadingProgramDos = $loadingProgram->loadingProgramItems->pluck('delivery_order_id')->unique()->toArray();

        return view('management.export.loading-program.edit', compact(
            'loadingProgram',
            'ExportOrders',
            'companyLocations',
            'arrivalLocations',
            'subArrivalLocations',
            'locations',
            'loadingProgramDos'
        ))->with([
            'Brands' => \App\Models\Master\Brands::where('status', 1)->get(),
            'Transporters' => \App\Models\Master\Transporter::where('status', 'active')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $validationRules = [
            'main_company_location_id' => 'required|exists:model_location,id',
            'export_order_id' => 'required|array|min:1',
            'export_order_id.*' => 'exists:export_orders,id',
            'delivery_order_id' => 'required|array|min:1',
            'delivery_order_id.*' => 'exists:delivery_order,id',
            'loading_program_items' => 'required|array|min:1',
            'loading_program_items.*.truck_number' => 'required|string|distinct',
            'loading_program_items.*.brand_id' => 'nullable|exists:brands,id',
            'loading_program_items.*.arrival_location_id' => 'required|exists:arrival_locations,id',
            'loading_program_items.*.sub_arrival_location_id' => 'required|exists:arrival_sub_locations,id',
            'remark' => 'nullable|string'
        ];

        foreach ($request->loading_program_items ?? [] as $index => $itemData) {
            $validationRules["loading_program_items.$index.delivery_order_id"] = 'required|array|min:1';
            $validationRules["loading_program_items.$index.delivery_order_id.*"] = 'exists:delivery_order,id';
        }

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $loadingProgram = LoadingProgram::findOrFail($id);
        $exportOrders = ExportOrder::whereIn('id', $request->export_order_id)->get();
        $exportOrder = $exportOrders->first();
        $deliveryOrders = DeliveryOrder::whereIn('id', $request->delivery_order_id)->get();

        $companyLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->location_id))->filter()->unique()->toArray();
        $arrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->arrival_location_id))->filter()->unique()->toArray();
        $subArrivalLocationIds = $deliveryOrders->flatMap(fn($do) => explode(',', $do->sub_arrival_location_id))->filter()->unique()->toArray();

        DB::beginTransaction();
        try {
            $loadingProgram->update([
                'export_order_id' => $exportOrder->id,
                'delivery_order_id' => $request->delivery_order_id[0] ?? null,
                'company_locations' => $companyLocationIds,
                'company_location_id' => $request->main_company_location_id,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'remark' => $request->remark,
            ]);

            $loadingProgram->exportOrders()->sync($request->export_order_id);
            $loadingProgram->deliveryOrders()->sync($request->delivery_order_id);

            $loadingProgram->loadingProgramItems()->whereDoesntHave('firstWeighbridge')->delete();

            foreach ($request->loading_program_items as $index => $itemData) {
                $selectedDoIds = $itemData['delivery_order_id'] ?? [];

                foreach ($selectedDoIds as $do_id) {
                    $swbBalance = get_second_weighbridge_balance_by_delivery_order($do_id);
                    $balance = $swbBalance;
                    $qty = $itemData['qty'] ?? 0;
                    if ($balance < $qty) {
                        DB::rollBack();
                        return response()->json([
                            'errors' => ["loading_program_items.$index.qty" => ["Your available balance (taking Second Weighbridge into account) for DO $do_id is $balance, you can not exceed that balance."]]
                        ], 422);
                    }
                }

                $loadingProgramItem = LoadingProgramItem::create([
                    'loading_program_id' => $loadingProgram->id,
                    'transaction_number' => $itemData['transaction_number'] ?? $this->getNumber($request),
                    'truck_number' => $itemData['truck_number'],
                    'container_number' => $itemData['container_number'] ?? null,
                    'packing' => $itemData['packing'] ?? null,
                    'brand_id' => $itemData['brand_id'] ?? null,
                    'arrival_location_id' => $itemData['arrival_location_id'],
                    'sub_arrival_location_id' => $itemData['sub_arrival_location_id'],
                    'driver_name' => $itemData['driver_name'] ?? null,
                    'contact_details' => $itemData['contact_details'] ?? null,
                    'transporter_id' => $itemData['transporter_id'] ?? null,
                    'qty' => $itemData['qty'] ?? 0,
                    'delivery_order_id' => $selectedDoIds[0] ?? null,
                ]);

                if (!empty($itemData['export_order_id'])) {
                    $loadingProgramItem->exportOrders()->sync($itemData['export_order_id']);
                } else {
                    $loadingProgramItem->exportOrders()->sync($request->export_order_id);
                }
                
                if (!empty($selectedDoIds)) {
                    $loadingProgramItem->deliveryOrders()->sync($selectedDoIds);
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Export Loading Program updated successfully.', 'data' => $loadingProgram], 200);
    }

    public function destroy($id)
    {
        $loadingProgram = LoadingProgram::findOrFail($id);
        $loadingProgram->delete();
        return response()->json(['success' => 'Export Loading Program deleted successfully.'], 200);
    }

    public function fetchExportOrdersByLocation(Request $request)
    {
        $location_id = $request->location_id;

        $exportOrderIds = DeliveryOrder::where('type', 'export_order')
            ->where('am_approval_status', 'approved')
            ->when($location_id, function ($q) use ($location_id) {
                return $q->whereRaw("FIND_IN_SET(?, location_id)", [$location_id]);
            })
            ->pluck('export_order_id')
            ->unique()
            ->filter()
            ->toArray();

        $exportOrders = ExportOrder::whereIn('id', $exportOrderIds)
            ->get()
            ->map(function($eo) {
                return [
                    'id' => $eo->id,
                    'reference_no' => $eo->voucher_no ?? $eo->contract_no ?? 'EO-' . $eo->id
                ];
            });

        return response()->json([
            'success' => true,
            'export_orders' => $exportOrders,
        ]);
    }

    public function getExportOrderRelatedData(Request $request)
    {
        $export_order_ids = is_array($request->export_order_id) ? $request->export_order_id : [$request->export_order_id];
        $company_location_id = $request->company_location_id;

        $ExportOrders = ExportOrder::with(['packingItems', 'deliveryOrders'])
            ->whereIn('id', $export_order_ids)
            ->get();

        $DeliveryOrders = DeliveryOrder::where('type', 'export_order')
            ->whereIn('export_order_id', $export_order_ids)
            ->where('am_approval_status', 'approved')
            ->when($company_location_id, function ($q) use ($company_location_id) {
                return $q->whereRaw("FIND_IN_SET(?, location_id)", [$company_location_id]);
            })
            ->with(['exportPackingItems', 'saleSecondWeighbridge'])
            ->get();

        $lpId = $request->loading_program_id;
        $linkedDoIds = [];
        if ($lpId) {
            $linkedDoIds = LoadingProgramItem::where('loading_program_id', $lpId)->pluck('delivery_order_id')->unique()->toArray();
        }

        $DeliveryOrders = $DeliveryOrders->reject(function ($deliveryOrder) use ($linkedDoIds) {
            if (in_array($deliveryOrder->id, $linkedDoIds)) {
                return false;
            }
            return get_second_weighbridge_balance_by_delivery_order($deliveryOrder->id) <= 0;
        });
        $html = view('management.export.loading-program.getExportOrderRelatedData', compact('ExportOrders', 'DeliveryOrders'))->render();

        $firstEO = $ExportOrders->first();
        $firstPacking = $firstEO?->packingItems->first();
        $firstDO = $DeliveryOrders->first();

        $exportOrderData = [
            'packing' => $firstPacking->bag_size ?? null,
            'brand_id' => $firstPacking->brand_id ?? null,
            'brand_name' => $firstPacking->brand?->name ?? null,
            'arrival_location_id' => $firstDO?->arrival_location_id,
            'sub_arrival_location_id' => $firstDO?->sub_arrival_location_id,
            'company_location_id' => $firstDO?->location_id,
        ];

        return response()->json([
            'success' => true,
            'html' => $html,
            'delivery_orders' => $DeliveryOrders,
            'export_orders' => $ExportOrders, // Added full EO data
            'export_order_data' => $exportOrderData,
            'transporters_map' => [],
        ]);
    }

    public function getDeliveryOrdersByExportOrder(Request $request)
    {
        $export_order_ids = is_array($request->export_order_id) ? $request->export_order_id : [$request->export_order_id];
        $company_location_id = $request->company_location_id;

        $deliveryOrders = DeliveryOrder::where('type', 'export_order')
            ->whereIn('export_order_id', $export_order_ids)
            ->where('am_approval_status', 'approved')
            ->when($company_location_id, function ($q) use ($company_location_id) {
                return $q->whereRaw("FIND_IN_SET(?, location_id)", [$company_location_id]);
            })
            ->with('exportPackingItems')
            ->select('id', 'reference_no', 'export_order_id', 'location_id', 'arrival_location_id', 'sub_arrival_location_id', 'am_approval_status')
            ->get();

        $lpId = $request->loading_program_id;
        $linkedDoIds = [];
        if ($lpId) {
            $linkedDoIds = LoadingProgramItem::where('loading_program_id', $lpId)->pluck('delivery_order_id')->unique()->toArray();
        }

        $deliveryOrders = $deliveryOrders->reject(function ($deliveryOrder) use ($linkedDoIds) {
            if (in_array($deliveryOrder->id, $linkedDoIds)) {
                return false;
            }
            return get_second_weighbridge_balance_by_delivery_order($deliveryOrder->id) <= 0;
        });

        $deliveryOrders = $deliveryOrders->map(function ($deliveryOrder) {
            $locationIds = explode(',', $deliveryOrder->location_id);
            $locationNames = \App\Models\Master\CompanyLocation::whereIn('id', $locationIds)->pluck('name')->toArray();
            $locationNameStr = implode(', ', $locationNames);
            $deliveryOrder->reference_no = $deliveryOrder->reference_no . ' - ' . ($locationNameStr ?: 'N/A');
            return $deliveryOrder;
        });

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders->values(),
        ]);
    }

    public function getDeliveryOrdersByExportOrderEdit(Request $request)
    {
        $export_order_ids = is_array($request->export_order_id) ? $request->export_order_id : [$request->export_order_id];
        $company_location_id = $request->company_location_id;

        $deliveryOrders = DeliveryOrder::where('type', 'export_order')
            ->whereIn('export_order_id', $export_order_ids)
            ->where('am_approval_status', 'approved')
            ->when($company_location_id, function ($q) use ($company_location_id) {
                return $q->whereRaw("FIND_IN_SET(?, location_id)", [$company_location_id]);
            })
            ->with(['exportPackingItems', 'saleSecondWeighbridge'])
            ->get();

        $lpId = $request->loading_program_id;
        $linkedDoIds = [];
        if ($lpId) {
            $linkedDoIds = LoadingProgramItem::where('loading_program_id', $lpId)
                ->with('deliveryOrders:id')
                ->get()
                ->flatMap(function ($item) {
                    $ids = $item->deliveryOrders->pluck('id')->toArray();
                    if (empty($ids) && $item->delivery_order_id) {
                        $ids = [$item->delivery_order_id];
                    }
                    return $ids;
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        $deliveryOrders = $deliveryOrders->reject(function ($deliveryOrder) use ($linkedDoIds) {
            if (in_array($deliveryOrder->id, $linkedDoIds)) {
                return false;
            }

            return get_second_weighbridge_balance_by_delivery_order($deliveryOrder->id) <= 0;
        });

        $deliveryOrders = $deliveryOrders->map(function ($deliveryOrder) {
            $locationIds = explode(',', $deliveryOrder->location_id);
            $locationNames = \App\Models\Master\CompanyLocation::whereIn('id', $locationIds)->pluck('name')->toArray();
            $locationNameStr = implode(', ', $locationNames);
            $deliveryOrder->reference_no = $deliveryOrder->reference_no . ' - ' . ($locationNameStr ?: 'N/A');
            return $deliveryOrder;
        });

        return response()->json([
            'success' => true,
            'delivery_orders' => $deliveryOrders,
        ]);
    }

    public function getNumber(Request $request)
    {
        $date = now()->format('Y-m-d');
        $prefix = $date;

        $latestContract = LoadingProgramItem::select('id', 'transaction_number')
            ->where('transaction_number', 'like', "$prefix-%")
            ->get();
        $latestContract = !$latestContract->count() ? null : $latestContract[$latestContract->count() - 1];

        if ($latestContract) {
            $parts = explode('-', $latestContract->transaction_number);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $date.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}
