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
                    })->orWhere('id', 'like', $searchTerm)
                    ->orWhere('vessel_name', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.loading-program.getList', compact('loadingPrograms'));
    }

    public function create()
    {
        return view('management.export.loading-program.create');
    }

    public function store(Request $request)
    {
        $validationRules = [
            'main_company_location_id' => 'required|exists:model_location,id',
            'export_order_id' => 'required|array|min:1',
            'export_order_id.*' => 'exists:export_orders,id',
            'delivery_order_id' => 'required|array|min:1',
            'delivery_order_id.*' => 'exists:delivery_order,id',
            'vessel_name' => 'required|string',
            'remark' => 'nullable|string'
        ];

        $validator = Validator::make($request->all(), $validationRules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exportOrders = ExportOrder::where('am_approval_status', 'approved')->whereIn('id', $request->export_order_id)->get();
        $exportOrder = $exportOrders->first();
        $deliveryOrders = DeliveryOrder::with('locations')->whereIn('id', $request->delivery_order_id)->get();

        $companyLocationIds = $deliveryOrders->flatMap(function($do) {
            return $do->locations->pluck('company_location_id');
        })->filter()->unique()->toArray();

        $arrivalLocationIds = $deliveryOrders->flatMap(function($do) {
            return $do->locations->flatMap(function($loc) {
                return explode(',', $loc->arrival_location_ids);
            });
        })->filter()->unique()->toArray();

        $subArrivalLocationIds = $deliveryOrders->flatMap(function($do) {
            return $do->locations->flatMap(function($loc) {
                return explode(',', $loc->sub_arrival_location_ids);
            });
        })->filter()->unique()->toArray();

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
                'vessel_name' => $request->vessel_name,
                'remark' => $request->remark,
                'status' => 'pending',
                'created_by' => auth()->user()->id,
            ]);

            // Sync main relationships
            $loadingProgram->exportOrders()->sync($request->export_order_id);
            if ($request->delivery_order_id) {
                $loadingProgram->deliveryOrders()->sync($request->delivery_order_id);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Export Loading Program Request created successfully.', 'data' => $loadingProgram], 201);
    }

    public function show($id)
    {
        $loadingProgram = LoadingProgram::with([
            'loadingProgramItems.transporter',
            'exportOrders.buyer',
            'exportOrders.product',
            'exportOrders.packingItems',
            'deliveryOrders.exportPackingItems',
        ])->findOrFail($id);

        if ($loadingProgram->status == 'completed') {
            return view('management.export.loading-program.complete.show', compact('loadingProgram'));
        }

        return view('management.export.loading-program.show', compact('loadingProgram'));
    }

    public function completeShow($id)
    {
        $loadingProgram = LoadingProgram::with([
            'loadingProgramItems.transporter',
            'exportOrders.buyer',
            'exportOrders.product',
            'exportOrders.packingItems',
            'deliveryOrders.exportPackingItems',
        ])->findOrFail($id);

        return view('management.export.loading-program.complete.show', compact('loadingProgram'));
    }

    public function edit($id)
    {
        $loadingProgram = LoadingProgram::with([
            'exportOrders',
            'deliveryOrders.locations.companyLocation',
        ])->findOrFail($id);

        $selectedExportOrderIds = $loadingProgram->exportOrders->pluck('id')->toArray();
        $companyLocationIds = is_array($loadingProgram->company_locations)
            ? array_filter($loadingProgram->company_locations)
            : array_filter(explode(',', (string) $loadingProgram->company_locations));

        $ExportOrders = ExportOrder::query()
            ->with([
                'deliveryOrders' => function ($q) {
                    $q->where('am_approval_status', 'approved');
                }
            ])
            ->where('am_approval_status', 'approved')
            ->get();

        $arrivalLocationIds = $loadingProgram->deliveryOrders->flatMap(function($do) {
            return $do->locations->flatMap(function($loc) {
                return explode(',', $loc->arrival_location_ids);
            });
        })->filter()->unique()->toArray();

        $subArrivalLocationIds = $loadingProgram->deliveryOrders->flatMap(function($do) {
            return $do->locations->flatMap(function($loc) {
                return explode(',', $loc->sub_arrival_location_ids);
            });
        })->filter()->unique()->toArray();

        $arrivalLocations = ArrivalLocation::whereIn('id', $arrivalLocationIds)->get()->map(fn($l) => ['id' => $l->id, 'text' => $l->name]);
        $subArrivalLocations = ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get()->map(fn($l) => ['id' => $l->id, 'text' => $l->name]);

        return view('management.export.loading-program.edit', compact(
            'loadingProgram',
            'ExportOrders',
            'arrivalLocations',
            'subArrivalLocations'
        ));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $loadingProgram = LoadingProgram::lockForUpdate()->find($id);

            if (!$loadingProgram) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Loading Program already deleted or not found.'], 404);
            }

            $validationRules = [
                'main_company_location_id' => 'required|exists:model_location,id',
                'export_order_id' => 'required|array|min:1',
                'export_order_id.*' => 'exists:export_orders,id',
                'delivery_order_id' => 'required|array|min:1',
                'delivery_order_id.*' => 'exists:delivery_order,id',
                'vessel_name' => 'required|string',
                'remark' => 'nullable|string'
            ];

            $validator = Validator::make($request->all(), $validationRules);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $exportOrders = ExportOrder::where('am_approval_status', 'approved')->whereIn('id', $request->export_order_id)->get();
            $exportOrder = $exportOrders->first();
            $deliveryOrders = DeliveryOrder::with('locations')->whereIn('id', $request->delivery_order_id)->get();

            $companyLocationIds = $deliveryOrders->flatMap(function($do) {
                return $do->locations->pluck('company_location_id');
            })->filter()->unique()->toArray();

            $arrivalLocationIds = $deliveryOrders->flatMap(function($do) {
                return $do->locations->flatMap(function($loc) {
                    return explode(',', $loc->arrival_location_ids);
                });
            })->filter()->unique()->toArray();

            $subArrivalLocationIds = $deliveryOrders->flatMap(function($do) {
                return $do->locations->flatMap(function($loc) {
                    return explode(',', $loc->sub_arrival_location_ids);
                });
            })->filter()->unique()->toArray();

            $loadingProgram->update([
                'export_order_id' => $exportOrder->id,
                'delivery_order_id' => $request->delivery_order_id[0] ?? null,
                'company_locations' => $companyLocationIds,
                'company_location_id' => $request->main_company_location_id,
                'arrival_locations' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocationIds,
                'vessel_name' => $request->vessel_name,
                'remark' => $request->remark,
            ]);

            $loadingProgram->exportOrders()->sync($request->export_order_id);
            $loadingProgram->deliveryOrders()->sync($request->delivery_order_id);

            DB::commit();

            return response()->json([
                'success' => 'Export Loading Program Request updated successfully.',
                'data' => $loadingProgram
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Stage 2: Completion Methods
    public function completeIndex()
    {
        return view('management.export.loading-program.complete.index');
    }

    public function getCompleteList(Request $request)
    {
        $loadingPrograms = LoadingProgram::with([
            'exportOrder',
            'deliveryOrder',
            'deliveryOrders',
            'createdBy',
        ])
            // ->where('status', 'pending')
            ->whereNotNull('status')
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
                    })->orWhere('id', 'like', $searchTerm)
                    ->orWhere('vessel_name', 'like', $searchTerm);
                });
            })
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.export.loading-program.complete.getList', compact('loadingPrograms'));
    }

    public function completeEdit($id)
    {
        $loadingProgram = LoadingProgram::with([
            'exportOrders',
            'deliveryOrders.locations.companyLocation',
        ])->findOrFail($id);

        $ExportOrders = $loadingProgram->exportOrders;

        $arrivalLocationIds = $loadingProgram->deliveryOrders->flatMap(function($do) {
            return $do->locations->flatMap(function($loc) {
                return explode(',', $loc->arrival_location_ids);
            });
        })->filter()->unique()->toArray();

        $subArrivalLocationIds = $loadingProgram->deliveryOrders->flatMap(function($do) {
            return $do->locations->flatMap(function($loc) {
                return explode(',', $loc->sub_arrival_location_ids);
            });
        })->filter()->unique()->toArray();

        $arrivalLocations = ArrivalLocation::whereIn('id', $arrivalLocationIds)->get()->map(fn($l) => ['id' => $l->id, 'text' => $l->name]);
        $subArrivalLocations = ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get()->map(fn($l) => ['id' => $l->id, 'text' => $l->name]);

        return view('management.export.loading-program.complete.edit', [
            'loadingProgram' => $loadingProgram,
            'ExportOrders' => $ExportOrders,
            'Transporters' => \App\Models\Master\Transporter::where('status', 'active')->get(),
            'arrivalLocations' => $arrivalLocations,
            'subArrivalLocations' => $subArrivalLocations,
        ]);
    }

    public function completeUpdate(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $loadingProgram = LoadingProgram::lockForUpdate()->findOrFail($id);

            $validationRules = [
                'loading_program_items' => 'required|array|min:1',
                'loading_program_items.*.truck_number' => 'required|string',
            ];

            $validator = Validator::make($request->all(), $validationRules);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $submittedItemIds = collect($request->loading_program_items)->pluck('id')->filter()->toArray();
            
            // 1. Delete items not in request
            $loadingProgram->loadingProgramItems()->whereNotIn('id', $submittedItemIds)->delete();

            $baseNumber = $this->getNumber($request);
            $parts = explode('-', $baseNumber);
            $lastNum = (int) end($parts);
            $prefix = implode('-', array_slice($parts, 0, -1));

            // Get current max transaction number to avoid duplicates
            $maxTransactionNumber = $loadingProgram->loadingProgramItems()->max('transaction_number');
            if ($maxTransactionNumber) {
                $maxParts = explode('-', $maxTransactionNumber);
                $lastNum = max($lastNum, (int) end($maxParts) + 1);
            }

            foreach ($request->loading_program_items as $index => $itemData) {
                $itemPayload = [
                    'loading_program_id' => $loadingProgram->id,
                    'truck_number' => $itemData['truck_number'],
                    'container_number' => $itemData['container_number'] ?? null,
                    'driver_name' => $itemData['driver_name'] ?? null,
                    'contact_details' => $itemData['contact_details'] ?? null,
                    'transporter_id' => $itemData['transporter_id'] ?? null,
                    'berth_no' => $itemData['berth_no'] ?? null,
                    's_bill_no' => $itemData['s_bill_no'] ?? null,
                    'qty' => 0,
                    'delivery_order_id' => $loadingProgram->delivery_order_id,
                ];

                if (!empty($itemData['id'])) {
                    // Update existing
                    LoadingProgramItem::where('id', $itemData['id'])->update($itemPayload);
                } else {
                    // Create new
                    $itemPayload['transaction_number'] = $prefix . '-' . str_pad($lastNum++, 3, '0', STR_PAD_LEFT);
                    LoadingProgramItem::create($itemPayload);
                }
            }

            $loadingProgram->update(['status' => 'completed']);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Loading Program completed successfully.'], 200);
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $loadingProgram = LoadingProgram::lockForUpdate()->find($id);

            if (!$loadingProgram) {
                DB::rollBack();

                return response()->json([
                    'success' => 'Loading Program already deleted or not found.',
                ], 404);
            }

            $loadingProgram->delete();

            DB::commit();

            return response()->json([
                'success' => 'Export Loading Program deleted successfully.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => 'Failed to delete Loading Program',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetchExportOrdersByLocation(Request $request)
    {
        $location_id = $request->location_id;

        $exportOrderIds = DeliveryOrder::where('type', 'export_order')
            ->where('am_approval_status', 'approved')
            ->when($location_id, function ($q) use ($location_id) {
                return $q->whereHas('locations', function($locQ) use ($location_id) {
                    $locQ->where('company_location_id', $location_id);
                });
            })
            ->pluck('export_order_id')
            ->unique()
            ->filter()
            ->toArray();

        $exportOrders = ExportOrder::where('am_approval_status', 'approved')
            ->whereIn('id', $exportOrderIds)
            ->get()
            ->map(function ($eo) {
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
            ->where('am_approval_status', 'approved')
            ->whereIn('id', $export_order_ids)
            ->get();

        $DeliveryOrders = DeliveryOrder::where('type', 'export_order')
            ->whereIn('export_order_id', $export_order_ids)
            ->where('am_approval_status', 'approved')
            ->when($company_location_id, function ($q) use ($company_location_id) {
                return $q->whereHas('locations', function($locQ) use ($company_location_id) {
                    $locQ->where('company_location_id', $company_location_id);
                });
            })
            ->with(['exportPackingItems', 'saleSecondWeighbridge', 'locations'])
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
                return $q->whereHas('locations', function($locQ) use ($company_location_id) {
                    $locQ->where('company_location_id', $company_location_id);
                });
            })
            ->with(['exportPackingItems', 'locations'])
            ->select('id', 'reference_no', 'export_order_id', 'am_approval_status')
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
            $locationNames = $deliveryOrder->locations->pluck('companyLocation.name')->filter()->toArray();
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
                return $q->whereHas('locations', function($locQ) use ($company_location_id) {
                    $locQ->where('company_location_id', $company_location_id);
                });
            })
            ->with(['exportPackingItems', 'saleSecondWeighbridge', 'locations'])
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

            if ($deliveryOrder->locations) {
                foreach ($deliveryOrder->locations as $loc) {
                    $aIds = explode(',', $loc->arrival_location_ids);
                    $loc->arrival_locations = \App\Models\Master\ArrivalLocation::whereIn('id', array_filter($aIds))->get(['id', 'name']);
                    
                    $sIds = explode(',', $loc->sub_arrival_location_ids);
                    $loc->sub_arrival_locations = \App\Models\Master\ArrivalSubLocation::whereIn('id', array_filter($sIds))->get(['id', 'name']);
                }
            }
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

        return $date . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }
}
