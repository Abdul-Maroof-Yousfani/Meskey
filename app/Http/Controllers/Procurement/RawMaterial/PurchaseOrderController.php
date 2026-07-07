<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Http\Requests\ArrivalPurchaseOrderRequest;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlab;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Product;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\Supplier;
use App\Models\SaudaType;
use App\Models\TruckSizeRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $companyLocations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
        //     return $q->where('id', auth()->user()->company_location_id);
        // })->get();
        $companyLocations = CompanyLocation::whereIn('id', getUserCurrentCompanyLocations())->get();
        return view('management.procurement.raw_material.purchase_order.index', compact('companyLocations'));
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $arrivalPurchaseOrder = ArrivalPurchaseOrder::with([
            'stockInTransitTickets',
            'rejectedArrivalTickets',
        ])->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            return $q->where(function ($sq) use ($searchTerm) {
                $sq->orWhereHas('supplier', function ($supplierQ) use ($searchTerm) {
                    $supplierQ->where('name', 'like', $searchTerm);
                })
                    ->orWhereHas('location', function ($locationQ) use ($searchTerm) {
                        $locationQ->where('name', 'like', $searchTerm);
                    })
                    ->orWhereHas('qcProduct', function ($qcProduct) use ($searchTerm) {
                        $qcProduct->where('name', 'like', $searchTerm);
                    })
                    ->orWhere('broker_one_name', 'like', $searchTerm)
                    ->orWhere('broker_two_name', 'like', $searchTerm)
                    ->orWhere('broker_three_name', 'like', $searchTerm)
                    ->orWhere('delivery_date', 'like', $searchTerm)
                    ->orWhere('delivery_address', 'like', $searchTerm)
                    ->orWhere('contract_date', 'like', $searchTerm)
                    ->orWhere('line_type', 'like', $searchTerm)
                    ->orWhere('bag_weight', 'like', $searchTerm)
                    ->orWhere('no_of_trucks', 'like', $searchTerm)
                    ->orWhere('weighbridge_from', 'like', $searchTerm)
                    ->orWhere('truck_no', 'like', $searchTerm)
                    ->orWhere('rate_per_kg', 'like', $searchTerm);
            });
        })
            ->when($request->filled('sauda_type_id_f'), function ($q) use ($request) {
                return $q->where('sauda_type_id', $request->sauda_type_id_f);
            })
            ->when($request->filled('company_location_id_f'), function ($q) use ($request) {
                return $q->where('company_location_id', $request->company_location_id_f);
            })
            ->when($request->filled('supplier_id_f'), function ($q) use ($request) {
                return $q->where('supplier_id', $request->supplier_id_f);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('contract_date', '>=', $startDate)
                    ->whereDate('contract_date', '<=', $endDate);
            })
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereIn('company_location_id', getUserCurrentCompanyLocations());
            })
            ->when(!auth()->user()->can("procurement-raw-purchase-approval") && auth()->user()->parent_user_id != null && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('created_by', auth()->user()->id);
            })
            ->when(auth()->user()->can("procurement-raw-purchase-approval") && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where("decision_of_id", auth()->user()->parent_user_id);
            })
            ->when(auth()->user()->parent_user_id == null && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('decision_of_id', auth()->user()->id);
            })
            ->where('purchase_type', 'regular')
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.procurement.raw_material.purchase_order.getList', compact('arrivalPurchaseOrder'));
    }

    public function markAsCompleted(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:arrival_purchase_orders,id'
        ]);

        try {
            $purchaseOrder = ArrivalPurchaseOrder::findOrFail($request->id);

            if ($purchaseOrder->remaining_quantity > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot mark as completed. There is still remaining quantity to be delivered.'
                ]);
            }

            $purchaseOrder->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contract marked as completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark contract as completed: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();
        $data['brokers'] = Broker::all();
        // $data['companyLocations'] = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
        //     return $q->where('id', auth()->user()->company_location_id);
        // })->get();
        $data['companyLocations'] = CompanyLocation::whereIn('id', getUserCurrentCompanyLocations())->get();
        $authUser = auth()->user();
        $locationId = $authUser->companyLocation?->id ?? '1';
        $locationId = (string) $locationId;

        $data['suppliers'] = Supplier::whereJsonContains('company_location_ids', $locationId)->get();

        return view('management.procurement.raw_material.purchase_order.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalPurchaseOrderRequest $request)
    {
        $data = $request->validated();
        $data = $request->all();
        $arrivalPurchaseOrder = null;

        if ($data['max_quantity'] < $data['min_quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Max quantity cannot be less than min quantity'
            ], 422);
        }

        $data['contract_no'] = self::getContractNumber($request, $request->company_location_id, $request->contract_date);

        DB::transaction(function () use ($data, $request) {
            $arrivalPOData = collect($data)->except(['slabs', 'quantity_range', 'truck_size_range'])->toArray();

            $arrivalPOData['is_replacement'] = $request->is_replacement == '1';
            $arrivalPOData['contract_status'] = $request->contract_status;
            $arrivalPOData["am_approval_status"] = "pending";

            if (isset($data['truck_size_range'])) {
                $arrivalPOData['truck_size_range_id'] = $data['truck_size_range'];
            }

            if ($request->broker_one_id ?? false) {
                $b1 = Broker::findOrFail($request->broker_one_id);
                $arrivalPOData['broker_one_name'] = $b1->name ?? NULL;
            }

            if ($request->broker_two_id ?? false) {
                $b2 = Broker::findOrFail($request->broker_two_id);
                $arrivalPOData['broker_two_name'] = $b2->name ?? NULL;
            }

            if ($request->broker_three_id ?? false) {
                $b3 = Broker::findOrFail($request->broker_three_id);
                $arrivalPOData['broker_three_name'] = $b3->name ?? NULL;
            }

            $arrivalPOData['created_by'] = auth()->user()->id;
            $arrivalPOData['decision_of_id'] = auth()->user()->parent_user_id == null ? auth()->user()->id : auth()->user()->parent_user_id;

            $arrivalPurchaseOrder = ArrivalPurchaseOrder::create($arrivalPOData);

            if (isset($data['slabs']) && count($data['slabs']) > 0) {
                foreach ($data['slabs'] as $slabId => $range) {
                    ProductSlabForRmPo::create([
                        'arrival_purchase_order_id' => $arrivalPurchaseOrder->id,
                        'slab_id' => $slabId,
                        'company_id' => $data['company_id'],
                        'product_id' => $data['product_id'],
                        'product_slab_type_id' => $range['product_slab_type_id'],
                        'from' => $range['from'],
                        'to' => $range['to'],
                        'deduction_type' => $range['deduction_type'],
                        'deduction_value' => null,
                        'status' => 'active',
                    ]);
                }
            }
        });

        return response()->json([
            'success' => 'Purchase Order Created Successfully.',
            'data' => $arrivalPurchaseOrder
        ], 201);
    }

    public function view($id)
    {
        $data['arrivalPurchaseOrder'] = ArrivalPurchaseOrder::findOrFail($id);
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();
        $data['brokers'] = Broker::all();
        $po = $data['arrivalPurchaseOrder'];
        $data['ticketcounts'] = $po->arrivalTickets()->count() ?? 0;

        $getSlabs = ProductSlabForRmPo::with('slabType')
            ->where('product_id', $data['arrivalPurchaseOrder']->product_id)
            ->where('company_id', $data['arrivalPurchaseOrder']->company_id)
            ->where('arrival_purchase_order_id', $id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy(function ($item) {
                    return (float) $item->from;
                })->first();
            })
            ->values()
            ->map(function ($item) {
                $item['slab_type_name'] = $item->slabType->name ?? null;
                $item['id'] = $item->slab_id ?? null;
                return $item;
            });

        if (!count($getSlabs)) {
            $ids = [
                'product_id' => $data['arrivalPurchaseOrder']->product_id,
                'company_id' => $data['arrivalPurchaseOrder']->company_id
            ];
            $data['slabsHtml'] = $this->getMainSlabByProduct(request(), $ids, true);
        } else {
            $data['slabsHtml'] = view('management.procurement.raw_material.purchase_order.slab-form', ['slabs' => $getSlabs, 'success' => '.'])->render();
        }

        return view('management.procurement.raw_material.purchase_order.view', $data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $arrivalPurchaseOrder = ArrivalPurchaseOrder::findOrFail($id);


        $data['arrivalPurchaseOrder'] = $arrivalPurchaseOrder;
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::where('product_type', 'raw_material')->get();
        $data['brokers'] = Broker::all();
        $po = $data['arrivalPurchaseOrder'];
        $data['ticketcounts'] = $po->arrivalTickets()->count() ?? 0;
        $getSlabs = ProductSlabForRmPo::with('slabType')
            ->where('product_id', $data['arrivalPurchaseOrder']->product_id)
            ->where('company_id', $data['arrivalPurchaseOrder']->company_id)
            ->where('arrival_purchase_order_id', $id)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(function ($group) {
                return $group->sortBy(function ($item) {
                    return (float) $item->from;
                })->first();
            })
            ->values()
            ->map(function ($item) {
                $item['slab_type_name'] = $item->slabType->name ?? null;
                $item['id'] = $item->slab_id ?? null;
                return $item;
            });

        if (!count($getSlabs)) {
            $ids = [
                'product_id' => $data['arrivalPurchaseOrder']->product_id,
                'company_id' => $data['arrivalPurchaseOrder']->company_id
            ];

            $data['slabsHtml'] = $this->getMainSlabByProduct(request(), $ids, true);
        } else {
            $data['slabsHtml'] = view('management.procurement.raw_material.purchase_order.slab-form', ['slabs' => $getSlabs, 'success' => '.'])->render();
        }

        return view('management.procurement.raw_material.purchase_order.edit', $data);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalPurchaseOrderRequest $request, $id)
    {
        $arrivalPurchaseOrder = ArrivalPurchaseOrder::findOrFail($id);
        if ($arrivalPurchaseOrder->am_approval_status == "approved" || $arrivalPurchaseOrder->am_approval_status == 'rejected') {
            return response()->json([
                "success" => false,
                "message" => "Purchase Order Already Approved or Rejected."
            ], 400);
        }





        $data = $request->validated();
        $data = $request->all();
        // dd($data);

        DB::transaction(function () use ($data, $arrivalPurchaseOrder) {
            $updateData = [
                'sauda_type_id' => $data['sauda_type_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'division_id' => $data['division_id'] ?? null,
                'supplier_commission' => $data['supplier_commission'] ?? null,
                'broker_one_id' => $data['broker_one_id'] ?? null,
                'broker_one_commission' => $data['broker_one_commission'] ?? 0,
                'broker_two_id' => $data['broker_two_id'] ?? null,
                'broker_two_commission' => $data['broker_two_commission'] ?? 0,
                'broker_three_id' => $data['broker_three_id'] ?? null,
                'broker_three_commission' => $data['broker_three_commission'] ?? 0,
                'product_id' => $data['product_id'] ?? null,
                'line_type' => $data['line_type'] ?? null,
                'bag_weight' => $data['bag_weight'] ?? null,
                'bag_rate' => $data['bag_rate'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'credit_days' => $data['credit_days'] ?? null,
                'rate_per_kg' => $data['rate_per_kg'] ?? null,
                'rate_per_mound' => $data['rate_per_mound'] ?? null,
                'rate_per_100kg' => $data['rate_per_100kg'] ?? null,
                'calculation_type' => $data['calculation_type'] ?? null,
                'is_replacement' => ($data['is_replacement'] ?? '') == '1',
                'weighbridge_from' => $data['weighbridge_from'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'min_quantity' => $data['min_quantity'] ?? null,
                'max_quantity' => $data['max_quantity'] ?? null,
                'min_bags' => $data['min_bags'] ?? null,
                'max_bags' => $data['max_bags'] ?? null,
                'contract_status' => $data['contract_status'] ?? null,
                'status' => $data['contract_status'] == 'close-contract-due-to-market-down' || $data['contract_status'] == 'close-with-market-rate-penalty' ? 'cancelled' : $arrivalPurchaseOrder->status,
                "am_approval_status" => "pending",
                "am_change_made" => 1,
                'remarks' => $data['remarks'] ?? null,
                'defaulter' => $data['defaulter'] ?? 0,
            ];

            if ($data['calculation_type'] == 'trucks') {
                $updateData['truck_size_range_id'] = $data['truck_size_range'] ?? null;
                $updateData['no_of_trucks'] = $data['no_of_trucks'] ?? null;
                $updateData['total_quantity'] = null ?? null;
            } else {
                $updateData['truck_size_range_id'] = null;
                $updateData['no_of_trucks'] = null;
                $updateData['total_quantity'] = $data['total_quantity'] ?? null;
            }

            $updateData['min_quantity'] = $data['min_quantity'] ?? null;
            $updateData['max_quantity'] = $data['max_quantity'] ?? null;
            $updateData['min_bags'] = $data['min_bags'] ?? null;
            $updateData['max_bags'] = $data['max_bags'] ?? null;

            $arrivalPurchaseOrder->update($updateData);

            ProductSlabForRmPo::where('arrival_purchase_order_id', $arrivalPurchaseOrder->id)->delete();
            $defaulter = ArrivalPurchaseOrder::where('supplier_id', $data['supplier_id'])->where('defaulter', 1)->get();

            if (count($defaulter) > 0) {
                Supplier::findOrFail($data['supplier_id'])->update([
                    'defaulter' => 1,
                ]);
            } else {
                Supplier::findOrFail($data['supplier_id'])->update([
                    'defaulter' => 0,
                ]);
            }



            if (isset($data['slabs']) && count($data['slabs']) > 0) {
                foreach ($data['slabs'] as $slabId => $range) {
                    ProductSlabForRmPo::create([
                        'arrival_purchase_order_id' => $arrivalPurchaseOrder->id,
                        'slab_id' => $slabId,
                        'company_id' => $data['company_id'],
                        'product_id' => $data['product_id'],
                        'product_slab_type_id' => $range['product_slab_type_id'],
                        'from' => $range['from'],
                        'to' => $range['to'],
                        'deduction_type' => $range['deduction_type'],
                        'deduction_value' => null,
                        'status' => 'active',
                    ]);
                }
            }
        });

        return response()->json([
            'success' => 'Purchase Order Updated Successfully.',
            'data' => $arrivalPurchaseOrder
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $arrival_location = ArrivalPurchaseOrder::findOrFail($id);
        if ($arrival_location->am_approval_status == "approved" || $arrival_location->am_approval_status == 'rejected') {
            return response()->json([
                "success" => false,
                "message" => "Purchase Order Already Approved or Rejected."
            ], 400);
        }

        $arrival_location->delete();
        return response()->json(['success' => 'Purchase Order deleted successfully.'], 200);
    }

    public function getMainSlabByProduct(Request $request, $ids = [], $isView = false)
    {
        $productId = $isView ? Arr::get($ids, 'product_id') : $request->product_id;
        $companyId = $isView ? Arr::get($ids, 'company_id') : $request->company_id;

        $slabs = ProductSlab::with('slabType')
            ->where('product_id', $productId)
            ->where('company_id', $companyId)
            ->where('is_purchase_field', 1)
            ->get()
            ->groupBy('product_slab_type_id')
            ->map(fn($group) => $group->sortBy(fn($item) => (float) $item->from)->first())
            ->values()
            ->map(function ($item) {
                $item['slab_type_name'] = $item->slabType->name ?? null;
                return $item;
            });

        $html = view('management.procurement.raw_material.purchase_order.slab-form', [
            'slabs' => $slabs,
            'success' => '.'
        ])->render();

        return $isView ? $html : response()->json(['html' => $html, 'success' => '.'], 200);
    }

    public function getContractNumber(Request $request, $locationId = null, $contractDate = null)
    {
        $location = CompanyLocation::find($locationId ?? $request->location_id);
        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = $location->code . '-' . Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = ArrivalPurchaseOrder::where('contract_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $locationCode = $location->code ?? 'LOC';
        $datePart = Carbon::parse($date)->format('Y-m-d');

        if ($latestContract) {
            $parts = explode('-', $latestContract->contract_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $contractNo = $locationCode . '-' . $datePart . '-' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        if (!$locationId && !$contractDate) {
            return response()->json([
                'success' => true,
                'contract_no' => $contractNo
            ]);
        }

        return $contractNo;
    }

    public function getSuppliersByLocation(Request $request)
    {
        $request->validate([
            'location_id' => 'required|exists:company_locations,id'
        ]);

        $locationId = (string) $request->location_id;
        $suppliers = Supplier::whereJsonContains('company_location_ids', $locationId)->get();

        $suppliers = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->company_name
            ];
        });

        return response()->json([
            'success' => true,
            'suppliers' => $suppliers
        ]);
    }





    /**
     * Export purchase orders to CSV with auto-download
     */
    public function exportCsv(Request $request)
    {
        try {
            $purchaseOrders = $this->getFilteredDataForExport($request);

            if ($purchaseOrders->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found to export with the applied filters.'
                ]);
            }

            // Generate CSV content
            $csvContent = $this->generateCsvContent($purchaseOrders);

            // Generate filename
            // $filename = 'purchase-orders-' . date('Y-m-d') . '.csv';

            $filename = $this->generateFilename($request);


            // Return as downloadable CSV
            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export data: ' . $e->getMessage()
            ]);
        }
    }


    private function generateFilename($request)
    {
        $filename = 'purchase-orders';
        $parts = [];

        // Date range
        if ($request->filled('daterange')) {
            $dates = explode(' - ', $request->daterange);
            if (count($dates) == 2) {
                try {
                    $start = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                    $end = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                    $parts[] = $start . '_to_' . $end;
                } catch (\Exception $e) {
                    $parts[] = date('Y-m-d');
                }
            }
        } else {
            $parts[] = date('Y-m-d');
        }

        // Location
        if ($request->filled('company_location_id_f')) {
            $location = CompanyLocation::find($request->company_location_id_f);
            if ($location) {
                $parts[] = preg_replace('/[^a-zA-Z0-9]/', '_', $location->name);
            }
        }

        // Supplier
        if ($request->filled('supplier_id_f')) {
            $supplier = Supplier::find($request->supplier_id_f);
            if ($supplier) {
                $name = $supplier->company_name ?? $supplier->name;
                $parts[] = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
            }
        }

        // Sauda Type
        if ($request->filled('sauda_type_id_f')) {
            $saudaType = SaudaType::find($request->sauda_type_id_f);
            if ($saudaType) {
                $parts[] = preg_replace('/[^a-zA-Z0-9]/', '_', $saudaType->name);
            }
        }

        // Search term
        if ($request->filled('search')) {
            $search = preg_replace('/[^a-zA-Z0-9]/', '_', substr(trim($request->search), 0, 15));
            if (!empty($search)) {
                $parts[] = 'search_' . $search;
            }
        }

        // Add timestamp
        $parts[] = date('His');

        // Build filename
        $filename = $filename . '_' . implode('_', array_map(fn($part) => strtolower($part), $parts));

        // Clean up any double underscores
        $filename = preg_replace('/_+/', '_', $filename);

        return $filename . '.csv';
    }

    private function generateCsvContent($purchaseOrders)
    {
        $output = fopen('php://temp', 'r+');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        $headers = [
            'Contract #',
            'Commodity',
            'Supplier Name',
            'Broker',
            'Decision Of',
            'Rate (KG)',
            // 'Rate (Mound)',
            // 'Rate (100KG)',
            'Expiry Date',
            'Sauda Type',
            'Replacement',
            'Remarks',
            'No of Trucks',
            'Ordered QTY (Min)',
            'Ordered QTY (Max)',
            'Arrived Trucks',
            'Arrived Net Weight',
            'Balance Trucks',
            'Balance Quantity (Min)',
            'Balance Quantity (Max)',
            'Stock in Transit Trucks',
            'Rejected Trucks',
            'Contract Status',
            'Approval Status',
            'Created By',
            'Created At',
            'Location'
        ];
        fputcsv($output, $headers);

        foreach ($purchaseOrders as $row) {
            fputcsv($output, $this->mapPurchaseOrderData($row));
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);
        return $content;
    }

    private function getFilteredDataForExport(Request $request)
    {
        $query = ArrivalPurchaseOrder::with([
            'stockInTransitTickets',
            'rejectedArrivalTickets',
            'approvedArrivalTickets',
            'product',
            'supplier',
            'decisionOfUser',
            'createdByUser',
            'location',
            'saudaType'
        ]);

        // Apply filters
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($sq) use ($searchTerm) {
                $sq->orWhereHas('supplier', fn($q) => $q->where('name', 'like', $searchTerm))
                    ->orWhereHas('location', fn($q) => $q->where('name', 'like', $searchTerm))
                    ->orWhereHas('product', fn($q) => $q->where('name', 'like', $searchTerm))
                    ->orWhere('broker_one_name', 'like', $searchTerm)
                    ->orWhere('broker_two_name', 'like', $searchTerm)
                    ->orWhere('broker_three_name', 'like', $searchTerm)
                    ->orWhere('contract_no', 'like', $searchTerm);
            });
        }

        if ($request->filled('sauda_type_id_f')) {
            $query->where('sauda_type_id', $request->sauda_type_id_f);
        }

        if ($request->filled('company_location_id_f')) {
            $query->where('company_location_id', $request->company_location_id_f);
        }

        if ($request->filled('supplier_id_f')) {
            $query->where('supplier_id', $request->supplier_id_f);
        }

        if ($request->filled('daterange')) {
            $dates = explode(' - ', $request->daterange);
            if (count($dates) == 2) {
                $startDate = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                $query->whereDate('contract_date', '>=', $startDate)
                    ->whereDate('contract_date', '<=', $endDate);
            }
        }

        // User permissions
        if (auth()->user()->user_type != 'super-admin') {
            $query->whereIn('company_location_id', getUserCurrentCompanyLocations());
        }

        if (
            !auth()->user()->can("procurement-raw-purchase-approval") &&
            auth()->user()->parent_user_id != null &&
            auth()->user()->user_type != 'super-admin'
        ) {
            $query->where('created_by', auth()->user()->id);
        }

        if (
            auth()->user()->can("procurement-raw-purchase-approval") &&
            auth()->user()->user_type != 'super-admin'
        ) {
            $query->where("decision_of_id", auth()->user()->parent_user_id);
        }

        if (
            auth()->user()->parent_user_id == null &&
            auth()->user()->user_type != 'super-admin'
        ) {
            $query->where('decision_of_id', auth()->user()->id);
        }

        $query->where('purchase_type', 'regular')->latest();
        return $query->get();
    }

    private function mapPurchaseOrderData($row)
    {
        $arrivedTrucks = $row->approvedArrivalTickets()->sum('closing_trucks_qty');
        $rejectedTrucks = $row->rejectedArrivalTickets()->sum('closing_trucks_qty');
        $rejectedHalfTrucks = $row->rejectedHalfArrivalTickets->count() != 0 ? $row->rejectedHalfArrivalTickets->count() / 2 : 0;
        $totalRejectedTrucks = $rejectedTrucks + $rejectedHalfTrucks;
        $inTransitTrucks = $row->stockInTransitTickets->count();
        $orderedTrucks = $row->no_of_trucks ?? 0;

        $balanceTrucks = $row->is_replacement == 1
            ? $orderedTrucks - $arrivedTrucks - $inTransitTrucks
            : $orderedTrucks - $arrivedTrucks - $inTransitTrucks - $totalRejectedTrucks;

        return [
            '#' . $row->contract_no,
            $row->product->name ?? 'N/A',
            $row->purchase_type == 'gate_buying' ? ($row->supplier_name ?? 'N/A') : ($row->supplier->name ?? 'N/A'),
            $row->broker_one_name ?? ($row->broker_two_name ?? ($row->broker_three_name ?? 'N/A')),
            $row->decisionOfUser->name ?? 'N/A',
            $row->rate_per_kg ?? 'N/A',
            // $row->rate_per_mound ?? 'N/A',
            // $row->rate_per_100kg ?? 'N/A',
            $row->delivery_date ? Carbon::parse($row->delivery_date)->format('d-m-Y') : 'N/A',
            $row->saudaType->name ?? '',
            $row->is_replacement == 1 ? 'Yes' : 'No',
            $row->remarks ?? 'N/A',
            $row->calculation_type == 'trucks' ? ($row->no_of_trucks ?? 0) : 'N/A',
            isset($row->min_quantity) ? intval($row->min_quantity) : '-',
            isset($row->max_quantity) ? intval($row->max_quantity) : '-',
            $arrivedTrucks,
            $row->totalArrivedNetWeight->total_arrived_net_weight ?? 0,
            $row->calculation_type == 'trucks' ? $balanceTrucks : 'N/A',
            (($row->min_quantity ?? 0) - ($row->totalArrivedNetWeight->total_arrived_net_weight ?? 0) ?? '-'),
            (($row->max_quantity ?? 0) - ($row->totalArrivedNetWeight->total_arrived_net_weight ?? 0) ?? '-'),
            $inTransitTrucks,
            $totalRejectedTrucks,
            $row->status == 'completed' ? 'Closed' : 'Pending',
            ucfirst($row->am_approval_status ?? 'pending'),
            $row->createdByUser->name ?? 'N/A',
            $row->created_at ? $row->created_at->format('d-m-Y H:i:s') : 'N/A',
            $row->location->name ?? 'N/A'
        ];
    }


}
