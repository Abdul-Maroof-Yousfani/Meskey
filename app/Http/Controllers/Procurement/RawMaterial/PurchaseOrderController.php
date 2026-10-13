<?php

namespace App\Http\Controllers\Procurement\RawMaterial;


use App\Http\Controllers\Controller;
use App\Http\Requests\ArrivalPurchaseOrderRequest;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\Broker;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlab;
use App\Models\User;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Product;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\Supplier;
use App\Models\SaudaType;
use App\Models\TruckSizeRange;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $authUserCompany = $request->company_id;

        $commodities = Product::all();
        $decisionofUsers = User::role('Purchaser')
            ->where('parent_user_id', null)
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();
        // $companyLocations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
        //     return $q->where('id', auth()->user()->company_location_id);
        // })->get();
        $companyLocations = CompanyLocation::whereIn('id', getUserCurrentCompanyLocations())->get();
        return view('management.procurement.raw_material.purchase_order.index', compact('companyLocations', 'commodities', 'decisionofUsers'));
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
                    ->orWhereHas('product', function ($product) use ($searchTerm) {
                        $product->where('name', 'like', $searchTerm);
                    })
                    ->orWhere('contract_no', 'like', $searchTerm)
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
            ->when($request->filled('decision_of_f'), function ($q) use ($request) {
                return $q->where('decision_of_id', $request->decision_of_f);
            })
            ->when($request->filled('commodity_id_f'), function ($q) use ($request) {
                return $q->where('product_id', $request->commodity_id_f);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('contract_date', '>=', $startDate)
                    ->whereDate('contract_date', '<=', $endDate);
            })
            ->when($request->filled('daterange2'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange2);
                $deliveryStartDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $deliveryEndDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('delivery_date', '>=', $deliveryStartDate)
                    ->whereDate('delivery_date', '<=', $deliveryEndDate);
            })
            ->when(true, function ($q) use ($request) {
                $contractStatusFilter = $request->get('contract_status_f', 'pending');

                if ($contractStatusFilter === 'all' || $contractStatusFilter === '') {
                    return $q;
                } elseif ($contractStatusFilter === 'closed') {
                    return $q->where(function ($sq) {
                        $sq->where(function ($csq) {
                            $csq->whereIn('status', ['completed', 'cancelled']);
                            // ->orWhereIn('contract_status', ['close-contract-due-to-market-down', 'close-with-market-rate-penalty']);
                        })->where(function ($rsq) {
                            $rsq->whereNull('contract_status')
                                ->orWhere('contract_status', '!=', 'reopen-contract-closed-by-mistake');
                        });
                    });
                } else {
                    return $q->where(function ($sq) {
                        $sq->where(function ($ssq) {
                            $ssq->whereIn('status', ['draft', 'confirmed']);
                            // ->orWhere('contract_status', 'reopen-contract-closed-by-mistake');
                        })->where(function ($csq) {
                            $csq->whereNull('contract_status')
                                ->orWhereNotIn('contract_status', ['close-contract-due-to-market-down', 'close-with-market-rate-penalty']);
                        });
                    });
                }
            })
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereIn('company_location_id', getUserCurrentCompanyLocations());
            })
            ->when(!auth()->user()->can("procurement-raw-list-all-purchase-order") && !auth()->user()->can("procurement-raw-purchase-approval") && auth()->user()->parent_user_id != null && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('created_by', auth()->user()->id);
            })
            ->when(auth()->user()->can("procurement-raw-purchase-approval") && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where("decision_of_id", auth()->user()->parent_user_id);
            })
            ->when(!auth()->user()->can("procurement-raw-list-all-purchase-order") && auth()->user()->parent_user_id == null && auth()->user()->user_type != 'super-admin', function ($q) {
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

            $oldStatus = $purchaseOrder->status;
            $purchaseOrder->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            AuditLogService::log(
                $purchaseOrder,
                'purchase_order_completed',
                "Purchase Order #{$purchaseOrder->contract_no} marked as completed.",
                ['status' => $oldStatus],
                ['status' => 'completed', 'completed_at' => now()->toDateTimeString()]
            );

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
        $data['products'] = Product::whereIn('product_type', ['raw_material', 'finish_good'])->get();
        $data['brokers'] = Broker::all();
        // $data['companyLocations'] = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
        //     return $q->where('id', auth()->user()->company_location_id);
        // })->get();
        $data['companyLocations'] = CompanyLocation::whereIn('id', getUserCurrentCompanyLocations())->get();
        $authUser = auth()->user();
        $locationId = $authUser->companyLocation?->id ?? '1';
        $locationId = (string) $locationId;

        $data['suppliers'] = Supplier::whereJsonContains('company_location_ids', $locationId)->where('status', 'active')->get();

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

        DB::transaction(function () use ($data, $request, &$arrivalPurchaseOrder) {
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

        if ($arrivalPurchaseOrder) {
            AuditLogService::log(
                $arrivalPurchaseOrder,
                'purchase_order_created',
                "Purchase Order #{$arrivalPurchaseOrder->contract_no} created.",
                null,
                $arrivalPurchaseOrder->getAttributes()
            );
        }

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
        $data['isClosed'] = $this->isContractClosed($arrivalPurchaseOrder);
        $data['balanceTrucks'] = $this->calculateBalanceTrucks($arrivalPurchaseOrder);
        $data['balanceQuantity'] = $this->calculateBalanceQuantity($arrivalPurchaseOrder);
        $data['bagPackings'] = [];
        $data['truckSizeRanges'] = TruckSizeRange::where('status', 'active')->get();
        $data['products'] = Product::whereIn('product_type', ['raw_material', 'finish_good'])->get();
        $locationId = (string) $data['arrivalPurchaseOrder']->company_location_id;
        $data['suppliers'] = Supplier::whereJsonContains('company_location_ids', $locationId)->get();
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

        $data = $request->validated();
        $data = $request->all();

        if (isset($data['contract_status']) && in_array($data['contract_status'], ['reopen-contract-closed-by-mistake', 'reopen', 'open'])) {
            if ($arrivalPurchaseOrder->calculation_type == 'trucks') {
                $balanceTrucks = $this->calculateBalanceTrucks($arrivalPurchaseOrder);
                if ($balanceTrucks <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Remaining balance trucks is 0. Contract cannot be reopened.'
                    ], 422);
                }
            } else {
                $balanceQuantity = $this->calculateBalanceQuantity($arrivalPurchaseOrder);
                if ($balanceQuantity <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Remaining balance quantity is 0. Contract cannot be reopened.'
                    ], 422);
                }
            }
        }

        if ($arrivalPurchaseOrder->am_approval_status == "approved" || $arrivalPurchaseOrder->am_approval_status == 'rejected') {

            $oldDeliveryDate = $arrivalPurchaseOrder->delivery_date ? \Carbon\Carbon::parse($arrivalPurchaseOrder->delivery_date)->format('Y-m-d') : null;
            $newDeliveryDate = !empty($data['delivery_date']) ? \Carbon\Carbon::parse($data['delivery_date'])->format('Y-m-d') : null;
            $deliveryDateChanged = $oldDeliveryDate != $newDeliveryDate;

            $contractStatusChanged = ($data['contract_status'] ?? null) != $arrivalPurchaseOrder->contract_status;
            $remarksChanged = ($data['remarks'] ?? null) != $arrivalPurchaseOrder->remarks;
            $defaulterChanged = (isset($data['defaulter']) ? (int) $data['defaulter'] : 0) != (int) $arrivalPurchaseOrder->defaulter;

            if (!$deliveryDateChanged && !$contractStatusChanged && !$remarksChanged && !$defaulterChanged) {
                return response()->json([
                    "success" => false,
                    "message" => "Purchase Order Already Approved or Rejected."
                ], 400);
            }

            $oldAttributes = $arrivalPurchaseOrder->getOriginal();
            DB::transaction(function () use ($data, $arrivalPurchaseOrder) {
                $isReopen = isset($data['contract_status']) && in_array($data['contract_status'], ['reopen-contract-closed-by-mistake', 'reopen', 'open']);
                $isCloseContract = isset($data['contract_status']) && ($data['contract_status'] == 'close-contract-due-to-market-down' || $data['contract_status'] == 'close-with-market-rate-penalty');
                $newStatus = $isReopen ? 'draft' : ($isCloseContract ? 'cancelled' : $arrivalPurchaseOrder->status);

                $updateData = [
                    'delivery_date' => $data['delivery_date'] ?? null,
                    'contract_status' => $isReopen ? 'reopen-contract-closed-by-mistake' : ($data['contract_status'] ?? null),
                    'status' => $newStatus,
                    'remarks' => $data['remarks'] ?? null,
                    'defaulter' => $data['defaulter'] ?? 0,
                ];

                $arrivalPurchaseOrder->update($updateData);

                $defaulter = ArrivalPurchaseOrder::where('supplier_id', $arrivalPurchaseOrder->supplier_id)->where('defaulter', 1)->get();
                if (count($defaulter) > 0) {
                    Supplier::findOrFail($arrivalPurchaseOrder->supplier_id)->update(['defaulter' => 1]);
                } else {
                    Supplier::findOrFail($arrivalPurchaseOrder->supplier_id)->update(['defaulter' => 0]);
                }
            });

            $newAttributes = $arrivalPurchaseOrder->getChanges();
            $changedOld = array_intersect_key($oldAttributes, $newAttributes);
            AuditLogService::log(
                $arrivalPurchaseOrder,
                'purchase_order_updated',
                "Purchase Order #{$arrivalPurchaseOrder->contract_no} updated.",
                $changedOld,
                $newAttributes
            );

            return response()->json([
                'success' => 'Purchase Order Specific Fields Updated Successfully.',
                'data' => $arrivalPurchaseOrder
            ], 200);
        }

        $oldAttributes = $arrivalPurchaseOrder->getOriginal();
        DB::transaction(function () use ($data, $arrivalPurchaseOrder) {
            $isReopen = isset($data['contract_status']) && in_array($data['contract_status'], ['reopen-contract-closed-by-mistake', 'reopen', 'open']);
            $isCloseContract = isset($data['contract_status']) && ($data['contract_status'] == 'close-contract-due-to-market-down' || $data['contract_status'] == 'close-with-market-rate-penalty');
            $newStatus = $isReopen ? 'draft' : ($isCloseContract ? 'cancelled' : $arrivalPurchaseOrder->status);

            $updateData = [
                'sauda_type_id' => $data['sauda_type_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'division_id' => $data['division_id'] ?? null,
                'supplier_commission' => $data['supplier_commission'] ?? null,
                'broker_one_id' => $data['broker_one_id'] ?? null,
                'broker_one_commission' => $data['broker_one_commission'] ?? null,
                'broker_two_id' => $data['broker_two_id'] ?? null,
                'broker_two_commission' => $data['broker_two_commission'] ?? null,
                'broker_three_id' => $data['broker_three_id'] ?? null,
                'broker_three_commission' => $data['broker_three_commission'] ?? null,
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
                'contract_status' => $isReopen ? 'reopen-contract-closed-by-mistake' : ($data['contract_status'] ?? null),
                'status' => $newStatus,
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

        $newAttributes = $arrivalPurchaseOrder->getChanges();
        $changedOld = array_intersect_key($oldAttributes, $newAttributes);
        AuditLogService::log(
            $arrivalPurchaseOrder,
            'purchase_order_updated',
            "Purchase Order #{$arrivalPurchaseOrder->contract_no} updated.",
            $changedOld,
            $newAttributes
        );

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

        AuditLogService::log(
            $arrival_location,
            'purchase_order_deleted',
            "Purchase Order #{$arrival_location->contract_no} deleted.",
            $arrival_location->getAttributes(),
            null
        );

        $arrival_location->delete();
        return response()->json(['success' => 'Purchase Order deleted successfully.'], 200);
    }

    /**
     * Get audit logs for a purchase order.
     */
    public function getAuditLogs(Request $request, $id)
    {
        $purchaseOrder = ArrivalPurchaseOrder::with(['product', 'supplier', 'location', 'saudaType', 'createdByUser'])->findOrFail($id);

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($purchaseOrder) {
                $q->where(function ($sq) use ($purchaseOrder) {
                    $sq->whereIn('model_type', [ArrivalPurchaseOrder::class, 'ArrivalPurchaseOrder'])
                        ->where('model_id', $purchaseOrder->id);
                })
                ->orWhere('old_values', 'like', '%"arrival_purchase_order_id":' . $purchaseOrder->id . '%')
                ->orWhere('old_values', 'like', '%"arrival_purchase_order_id":"' . $purchaseOrder->id . '"%')
                ->orWhere('new_values', 'like', '%"arrival_purchase_order_id":' . $purchaseOrder->id . '%')
                ->orWhere('new_values', 'like', '%"arrival_purchase_order_id":"' . $purchaseOrder->id . '"%');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Synthesize an initial creation record if none exists yet for an older PO
        $hasCreateLog = $logs->contains(function ($l) {
            return str_contains($l->action, 'create') || str_contains($l->action, 'store') || empty($l->old_values);
        });

        if (!$hasCreateLog) {
            $virtualCreateLog = new AuditLog([
                'action' => 'purchase_order_created',
                'description' => "Purchase Order #{$purchaseOrder->contract_no} created.",
                'user_id' => $purchaseOrder->created_by,
                'created_at' => $purchaseOrder->created_at,
                'old_values' => null,
                'new_values' => $purchaseOrder->getAttributes(),
            ]);
            $virtualCreateLog->setRelation('user', $purchaseOrder->createdByUser);
            $logs->push($virtualCreateLog);
        }

        $fieldLabels = [
            'contract_no'                   => 'Contract #',
            'contract_date'                 => 'Contract Date',
            'delivery_date'                 => 'Delivery Date',
            'company_location_id'           => 'Location',
            'supplier_id'                   => 'Supplier',
            'supplier_name'                 => 'Supplier Name',
            'purchaser_name'                => 'Purchaser Name',
            'decision_of_id'                => 'Decision Of',
            'created_by'                    => 'Created By',
            'product_id'                    => 'Commodity / Product',
            'qc_product'                    => 'QC Product',
            'sauda_type_id'                 => 'Sauda Type',
            'division_id'                   => 'Division',
            'broker_one_id'                 => 'Broker 1',
            'broker_one_name'               => 'Broker 1 Name',
            'broker_one_commission'         => 'Broker 1 Commission',
            'broker_one_calculation_type'   => 'Broker 1 Calc Type',
            'broker_two_id'                 => 'Broker 2',
            'broker_two_name'               => 'Broker 2 Name',
            'broker_two_commission'         => 'Broker 2 Commission',
            'broker_two_calculation_type'   => 'Broker 2 Calc Type',
            'broker_three_id'               => 'Broker 3',
            'broker_three_name'             => 'Broker 3 Name',
            'broker_three_commission'       => 'Broker 3 Commission',
            'broker_three_calculation_type' => 'Broker 3 Calc Type',
            'rate_per_kg'                   => 'Rate (per Kg)',
            'rate_per_mound'                => 'Rate (per Maund)',
            'rate_per_100kg'                => 'Rate (per 100Kg)',
            'line_type'                     => 'Line Type',
            'bag_weight'                    => 'Bag Weight',
            'bag_rate'                      => 'Bag Rate',
            'credit_days'                   => 'Credit Days',
            'calculation_type'              => 'Calculation Type',
            'no_of_trucks'                  => 'No of Trucks',
            'truck_size_range_id'           => 'Truck Size Range',
            'total_quantity'                => 'Total Quantity',
            'min_quantity'                  => 'Min Quantity',
            'max_quantity'                  => 'Max Quantity',
            'min_bags'                      => 'Min Bags',
            'max_bags'                      => 'Max Bags',
            'is_replacement'                => 'Replacement',
            'weighbridge_from'              => 'Weighbridge From',
            'delivery_address'              => 'Delivery Address',
            'remarks'                       => 'Remarks',
            'status'                        => 'Status',
            'contract_status'               => 'Contract Status',
            'am_approval_status'            => 'Approval Status',
            'defaulter'                     => 'Defaulter',
            'purchase_type'                 => 'Purchase Type',
            'payment_term'                  => 'Payment Term',
            'cnic_no'                       => 'CNIC No',
            'mobile_no'                     => 'Mobile No',
            'contact_person_name'           => 'Contact Person',
            'moisture'                      => 'Moisture',
            'chalky'                        => 'Chalky',
            'mixing'                        => 'Mixing',
            'red_rice'                      => 'Red Rice',
            'other_params'                  => 'Other Params',
            'truck_no'                      => 'Truck No',
            'account_of'                    => 'Account Of',
            'supplier_commission'           => 'Supplier Commission',
        ];

        $relationCache = [];
        $resolveRelationValue = function ($key, $val) use (&$relationCache) {
            if ($val === null || $val === '') {
                return null;
            }
            if (is_array($val)) {
                return json_encode($val);
            }

            $lookupMap = [
                'company_location_id' => [CompanyLocation::class, 'name'],
                'supplier_id'         => [Supplier::class, 'name'],
                'decision_of_id'      => [User::class, 'name'],
                'created_by'          => [User::class, 'name'],
                'product_id'          => [Product::class, 'name'],
                'sauda_type_id'       => [SaudaType::class, 'name'],
                'division_id'         => [\App\Models\Master\Division::class, 'name'],
                'broker_one_id'       => [Broker::class, 'name'],
                'broker_two_id'       => [Broker::class, 'name'],
                'broker_three_id'     => [Broker::class, 'name'],
                'truck_size_range_id' => [TruckSizeRange::class, 'name'],
            ];

            if (isset($lookupMap[$key]) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                [$modelClass, $attr] = $lookupMap[$key];
                $cacheKey = $modelClass . '_' . $val;
                if (!array_key_exists($cacheKey, $relationCache)) {
                    try {
                        $record = $modelClass::find($val);
                        $relationCache[$cacheKey] = $record ? ($record->{$attr} ?? null) : null;
                    } catch (\Throwable $e) {
                        $relationCache[$cacheKey] = null;
                    }
                }
                if (!empty($relationCache[$cacheKey])) {
                    return $relationCache[$cacheKey];
                }
            }

            if (in_array($key, ['is_replacement', 'defaulter'])) {
                return ($val == 1 || $val === true || $val === '1') ? 'Yes' : 'No';
            }

            if (in_array($key, ['status', 'contract_status', 'am_approval_status', 'purchase_type', 'calculation_type']) && is_string($val)) {
                return ucwords(str_replace(['_', '-'], ' ', $val));
            }

            return $val;
        };

        $flattenArray = function ($data) use (&$flattenArray) {
            $result = [];
            if (!is_array($data)) return $result;
            foreach ($data as $key => $val) {
                if (is_array($val) && !empty($val)) {
                    $isAssoc = array_keys($val) !== range(0, count($val) - 1);
                    if ($isAssoc) {
                        $nested = $flattenArray($val);
                        foreach ($nested as $nKey => $nVal) {
                            $result[$nKey] = $nVal;
                        }
                    } else {
                        $result[$key] = json_encode($val);
                    }
                } else {
                    $result[$key] = $val;
                }
            }
            return $result;
        };

        $filteredLogs = [];
        $skipKeys = ['id', 'company_id', 'deleted_at', 'ref_no', 'am_change_made'];

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
                    $oldResolved = $resolveRelationValue($key, $oldVal);
                    $newResolved = $resolveRelationValue($key, $newVal);

                    $parsedChanges[] = [
                        'key'     => $key,
                        'label'   => $fieldLabels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key)),
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
        $title = "Purchase Order Audit Logs";
        $subtitle = 'Contract #' . ($purchaseOrder->contract_no ?? 'N/A');
        $emptyMessage = 'No changes or updates have been recorded for this purchase order yet.';

        return view('components.audit-logs', compact('logs', 'title', 'subtitle', 'emptyMessage'));
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
        $suppliers = Supplier::whereJsonContains('company_location_ids', $locationId)->where('status', 'active')->get();

        $suppliers = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->company_name,
                'owner_name' => $supplier->owner_name,
                'email' => $supplier->email,
                'phone' => $supplier->owner_mobile_no,
                'cnic' => $supplier->owner_cnic_no,
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
            'Date',
            'Time',
            'Commodity',
            'Supplier Name',
            'Broker',
            'Decision Of',
            'No of Trucks',
            'Qty in Kgs',

            // 'Rate (Mound)',
            // 'Rate (100KG)',
            'Bags',
            'Rate (KG)',
            'Thadda Rate',
            'Last Delivery Date',
            'Payment Terms',
            'Other Condition',
            'Place Of Delivery',
            // 'Sauda Type',
            // 'Replacement',
            // 'Remarks',
            // 'No of Trucks',
            // 'Ordered QTY (Min)',
            // 'Ordered QTY (Max)',
            // 'Arrived Trucks',
            // 'Arrived Net Weight',
            // 'Balance Trucks',
            // 'Balance Quantity (Min)',
            // 'Balance Quantity (Max)',
            // 'Stock in Transit Trucks',
            // 'Rejected Trucks',
            // 'Contract Status',
            // 'Approval Status',
            // 'Created By',
            // 'Created At',
            // 'Location'
        ];
        fputcsv($output, $headers);

        foreach ($purchaseOrders as $row) {
            // dd($this->mapPurchaseOrderData($row));
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

        $contractStatusFilter = $request->get('contract_status_f', 'pending');

        if ($contractStatusFilter === 'all' || $contractStatusFilter === '') {
            // No filter applied - show all
        } elseif ($contractStatusFilter === 'closed') {
            $query->where(function ($q) {
                $q->where(function ($csq) {
                    $csq->whereIn('status', ['completed', 'cancelled'])
                        ->orWhereIn('contract_status', ['close-contract-due-to-market-down', 'close-with-market-rate-penalty']);
                })->where(function ($rsq) {
                    $rsq->whereNull('contract_status')
                        ->orWhere('contract_status', '!=', 'reopen-contract-closed-by-mistake');
                });
            });
        } else {
            $query->where(function ($q) {
                $q->where(function ($ssq) {
                    $ssq->whereIn('status', ['draft', 'confirmed'])
                        ->orWhere('contract_status', 'reopen-contract-closed-by-mistake');
                })->where(function ($csq) {
                    $csq->whereNull('contract_status')
                        ->orWhereNotIn('contract_status', ['close-contract-due-to-market-down', 'close-with-market-rate-penalty']);
                });
            });
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
            !auth()->user()->can("procurement-raw-list-all-purchase-order") &&
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

        $brokerDetails = '';
        if (isset($row->broker_one_id)) {
            $brokerDetails = 'Broker 1: ' . ($row->broker_one_name ?? 'N/A') . ' (' . ($row->broker_one_commission ?? null) . '%)';
        }
        if (isset($row->broker_two_id)) {
            $brokerDetails .= ($brokerDetails ? "\n" : '') . 'Broker 2: ' . ($row->brokerTwo->name ?? 'N/A') . ' (' . ($row->brokerTwo->broker_one_commission ?? null) . '%)';
        }
        if (isset($row->broker_three_id)) {
            $brokerDetails .= ($brokerDetails ? "\n" : '') . 'Broker 3: ' . ($row->brokerThree->name ?? 'N/A') . ' (' . ($row->brokerThree->broker_one_commission ?? null) . '%)';
        }

        // If no broker
        if (empty($brokerDetails)) {
            $brokerDetails = 'No Broker';
        }


        return [
            '#' . $row->contract_no,
            $row->created_at->format('Y-m-d'),
            $row->created_at->format('H:i:s'),
            $row->product->name ?? 'N/A',
            $row->purchase_type == 'gate_buying' ? ($row->supplier_name ?? 'N/A') : ($row->supplier->name ?? 'N/A'),
            $row->broker_one_name ?? ($row->broker_two_name ?? ($row->broker_three_name ?? 'N/A')),
            $row->decisionOfUser->name ?? 'N/A',
            $row->calculation_type == 'trucks' ? ($row->no_of_trucks ?? 0) : 'N/A',
            (isset($row->min_quantity) ? intval($row->min_quantity) : '-') . '-' . (isset($row->max_quantity) ? intval($row->max_quantity) : '-'),
            (isset($row->min_quantity) && isset($row->product->bag_weight_for_purchasing) && $row->product->bag_weight_for_purchasing > 0 ? intval($row->min_quantity / $row->product->bag_weight_for_purchasing) : '-') . '-' . (isset($row->max_quantity) && isset($row->product->bag_weight_for_purchasing) && $row->product->bag_weight_for_purchasing > 0 ? intval($row->max_quantity / $row->product->bag_weight_for_purchasing) : '-'),
            $row->rate_per_kg ?? 'N/A',
            '',
            $row->delivery_date ? Carbon::parse($row->delivery_date)->format('d-M-Y') : 'N/A',
            $row->credit_days ?? 'N/A',
            // $row->rate_per_mound ?? 'N/A',
            // $row->rate_per_100kg ?? 'N/A',
            'Sauda Type: ' . ($row->saudaType->name ?? '') . "\n" .
            ucfirst($row->calculation_type) . ' Wise' . "\n" .
            'Replacement: ' . ($row->is_replacement == 1 ? 'Yes' : 'No') . "\n\n" .
            'COMMISSIONS:' . "\n" .
            'Supplier Commission: ' . ($row->supplier_commission ?? 0) . "\n" .
            $brokerDetails . "\n\n" .
            'REMARKS:' . "\n" .
            ($row->remarks ?? ''),
            $row->delivery_address ?? 'N/A',

            // $row->calculation_type == 'trucks' ? ($row->no_of_trucks ?? 0) : 'N/A',
            // isset($row->min_quantity) ? intval($row->min_quantity) : '-',
            // isset($row->max_quantity) ? intval($row->max_quantity) : '-',
            // $arrivedTrucks,
            // $row->totalArrivedNetWeight->total_arrived_net_weight ?? 0,
            // $row->calculation_type == 'trucks' ? $balanceTrucks : 'N/A',
            // (($row->min_quantity ?? 0) - ($row->totalArrivedNetWeight->total_arrived_net_weight ?? 0) ?? '-'),
            // (($row->max_quantity ?? 0) - ($row->totalArrivedNetWeight->total_arrived_net_weight ?? 0) ?? '-'),
            // $inTransitTrucks,
            // $totalRejectedTrucks,
            // $row->status == 'completed' ? 'Closed' : 'Pending',
            // ucfirst($row->am_approval_status ?? 'pending'),
            // $row->createdByUser->name ?? 'N/A',
            // $row->created_at ? $row->created_at->format('d-m-Y H:i:s') : 'N/A',
            // $row->location->name ?? 'N/A'
        ];
    }

    private function calculateBalanceTrucks(ArrivalPurchaseOrder $arrivalPurchaseOrder)
    {
        $arrivedTrucks = (float) $arrivalPurchaseOrder->approvedArrivalTickets()->sum('closing_trucks_qty');
        $rejectedTrucks = (float) $arrivalPurchaseOrder->rejectedArrivalTickets()->sum('closing_trucks_qty');
        $rejectedHalfTrucks = $arrivalPurchaseOrder->rejectedHalfArrivalTickets->count() != 0
            ? $arrivalPurchaseOrder->rejectedHalfArrivalTickets->count() / 2
            : 0;
        $totalRejectedTrucks = $rejectedTrucks + $rejectedHalfTrucks;
        $inTransitTrucks = (float) $arrivalPurchaseOrder->stockInTransitTickets->count();
        $orderedTrucks = (float) ($arrivalPurchaseOrder->no_of_trucks ?? 0);

        if ($arrivalPurchaseOrder->is_replacement == 1) {
            return $orderedTrucks - $arrivedTrucks - $inTransitTrucks;
        } else {
            return $orderedTrucks - $arrivedTrucks - $inTransitTrucks - $totalRejectedTrucks;
        }
    }

    private function calculateBalanceQuantity(ArrivalPurchaseOrder $arrivalPurchaseOrder)
    {
        $totalArrivedNetWeight = (float) ($arrivalPurchaseOrder->totalArrivedNetWeight->total_arrived_net_weight ?? 0);
        $maxQuantity = (float) ($arrivalPurchaseOrder->max_quantity ?? 0);
        return $maxQuantity - $totalArrivedNetWeight;
    }

    private function isContractClosed(ArrivalPurchaseOrder $arrivalPurchaseOrder)
    {
        if ($arrivalPurchaseOrder->contract_status === 'reopen-contract-closed-by-mistake') {
            return false;
        }

        return $arrivalPurchaseOrder->status == 'completed'
            || $arrivalPurchaseOrder->status == 'cancelled'
            || in_array($arrivalPurchaseOrder->contract_status, ['close-contract-due-to-market-down', 'close-with-market-rate-penalty']);
    }

}
