<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\DeliveryChallanRequest;
use App\Models\Master\Customer;
use App\Models\Master\PayType;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\ArrivalSubLocation;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Sales\DeliveryChallan;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\LoadingProgram;
use App\Models\Sales\LoadingProgramItem;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\ReceivingRequestItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Master\Vendor;
use App\Models\ApprovalsModule\ApprovalModuleRole;
use App\Services\AuditLogService;
use App\Models\AuditLog;
use App\Models\User;

class DeliveryChallanController extends Controller
{
    private function getUserArrivalLocations()
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return [];
        }
        $locations = getUserCurrentCompanyArrivalLocations();
        if (empty($locations) && $authUser->arrival_location_id) {
            $locations = [$authUser->arrival_location_id];
        }
        return $locations ?? [];
    }

    private function canUserAccessDeliveryChallan(DeliveryChallan $delivery_challan): bool
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return false;
        }
        if ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin')) {
            return true;
        }

        $userRoleIds = (new DeliveryChallan)->getUserRoleIdsForApproval($authUser);
        if (!empty($userRoleIds)) {
            $dcModule = (new DeliveryChallan)->getApprovalModule();
            $moduleId = $dcModule ? $dcModule->id : 18;
            if (ApprovalModuleRole::where('module_id', $moduleId)->whereIn('role_id', $userRoleIds)->exists()) {
                return true;
            }
        }

        if ($delivery_challan->created_by_id == $authUser->id) {
            return true;
        }

        if ($delivery_challan->delivery_order()->whereHas('salesOrder', function ($q) use ($authUser) {
            $q->where('created_by', $authUser->id);
        })->exists()) {
            return true;
        }

        $userLocations = $this->getUserArrivalLocations();
        if (empty($userLocations)) {
            return false;
        }

        $dcArrivals = !empty($delivery_challan->arrival_id)
            ? array_map('trim', explode(',', (string)$delivery_challan->arrival_id))
            : [];

        $ticketArrivals = $delivery_challan->delivery_challan_data
            ->pluck('loadingProgramItem.arrival_location_id')
            ->filter()
            ->map(fn($id) => (string)$id)
            ->unique()
            ->toArray();

        $allDcLocations = array_unique(array_merge($dcArrivals, $ticketArrivals));

        if (empty($allDcLocations)) {
            return true;
        }

        return !empty(array_intersect($allDcLocations, array_map('strval', $userLocations)));
    }

    public function index() {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $userRoleIds = (new DeliveryChallan)->getUserRoleIdsForApproval($authUser);
        $isApprover = false;
        if (!$isSuperAdmin && !empty($userRoleIds)) {
            $dcModule = (new DeliveryChallan)->getApprovalModule();
            $moduleId = $dcModule ? $dcModule->id : 18;
            $isApprover = ApprovalModuleRole::where('module_id', $moduleId)
                ->whereIn('role_id', $userRoleIds)
                ->exists();
        }
        $locations = $this->getUserArrivalLocations();

        $dcQuery = DeliveryChallan::query();
        if (!$isSuperAdmin && !$isApprover) {
            $dcQuery->where(function($q) use ($locations, $authUser) {
                if (!empty($locations)) {
                    foreach ($locations as $locId) {
                        $q->orWhereRaw("FIND_IN_SET(?, arrival_id)", [$locId]);
                    }
                    $q->orWhereHas('delivery_challan_data.loadingProgramItem', function($sq) use ($locations) {
                        $sq->whereIn('arrival_location_id', $locations);
                    });
                }
                $q->orWhere('created_by_id', $authUser->id)
                  ->orWhereHas('delivery_order.salesOrder', function ($soQ) use ($authUser) {
                      $soQ->where('created_by', $authUser->id);
                  });
            });
        }

        // Only get customers that have delivery challan records
        $customerIds = (clone $dcQuery)->distinct()->pluck('customer_id')->filter();
        $customers = Customer::whereIn('id', $customerIds)->get();

        // Only get items that have delivery challan data records attached to an existing DC
        $itemIds = \App\Models\Sales\DeliveryChallanData::whereIn('delivery_challan_id', function($query) use ($isSuperAdmin, $isApprover, $locations) {
                $q = $query->select('id')->from('delivery_challans');
                if (!$isSuperAdmin && !$isApprover) {
                    $q->where(function($sq) use ($locations) {
                        foreach ($locations as $locId) {
                            $sq->orWhereRaw("FIND_IN_SET(?, arrival_id)", [$locId]);
                        }
                    });
                }
            })
            ->distinct()
            ->pluck('item_id')
            ->filter();
        $items = Product::whereIn('id', $itemIds)->get();

        // Only get delivery orders that are linked to existing delivery challans
        $doIds = DB::table('delivery_challan_delivery_order')
            ->whereIn('delivery_challan_id', function($query) use ($isSuperAdmin, $isApprover, $locations) {
                $q = $query->select('id')->from('delivery_challans');
                if (!$isSuperAdmin && !$isApprover) {
                    $q->where(function($sq) use ($locations) {
                        foreach ($locations as $locId) {
                            $sq->orWhereRaw("FIND_IN_SET(?, arrival_id)", [$locId]);
                        }
                    });
                }
            })
            ->distinct()
            ->pluck('delivery_order_id')
            ->filter();
        $deliveryOrders = DeliveryOrder::whereIn('id', $doIds)->select('id', 'reference_no')->get();

        return view('management.sales.delivery-challan.index', compact('customers', 'items', 'deliveryOrders'));
    }

    public function create() {
        abort_if(!canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();
        $delivery_orders = DeliveryOrder::select("delivery_order.id", "delivery_order.reference_no")
            ->where('delivery_order.do_status', 'active')
            ->whereDoesntHaveClosedSaleOrder()
            ->join('loading_programs', 'delivery_order.id', '=', 'loading_programs.delivery_order_id')
            ->join('loading_program_items', 'loading_programs.id', '=', 'loading_program_items.loading_program_id')
            ->join('loading_slips', 'loading_program_items.id', '=', 'loading_slips.loading_program_item_id')
            ->join('sales_second_weighbridges', 'loading_slips.id', '=', 'sales_second_weighbridges.loading_slip_id')
            ->when(!$isSuperAdmin, function($q) use ($locations) {
                $q->whereIn('loading_program_items.arrival_location_id', $locations);
            })
            ->distinct()
            ->get();
        $labours = Vendor::all();

        $transporters = \App\Models\Master\Transporter::all();
        return view("management.sales.delivery-challan.create", compact("customers", "delivery_orders", "transporters", "labours"));
    }

    public function store(DeliveryChallanRequest $request) {
        abort_if(!canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        DB::beginTransaction();
        $do_id = $request->delivery_order_id;

        // delivery order's delivery date should not be greater than date
        $delivery_order = DeliveryOrder::find($do_id);
        if ($delivery_order && $delivery_order->isClosed()) {
            return response()->json([
                'error' => 'The Sale Order or Delivery Order for this Delivery Challan has been closed. Operations are locked.',
                'message' => 'The Sale Order or Delivery Order for this Delivery Challan has been closed. Operations are locked.'
            ], 422);
        }

        if (is_array($request->ticket_id)) {
            foreach ($request->ticket_id as $tId) {
                if ($tId) {
                    $ticketItem = LoadingProgramItem::with('loadingSlip.secondWeighbridge')->find($tId);
                    if ($ticketItem && $ticketItem->hasClosedSaleOrder()) {
                        return response()->json([
                            'error' => 'The Sale Order linked to ticket ' . ($ticketItem->transaction_number ?? $tId) . ' has been closed. Operations are locked.',
                            'message' => 'The Sale Order linked to ticket ' . ($ticketItem->transaction_number ?? $tId) . ' has been closed. Operations are locked.'
                        ], 422);
                    }
                    if (!$isSuperAdmin && $ticketItem && !in_array($ticketItem->arrival_location_id, $locations)) {
                        return response()->json([
                            'error' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not belong to your assigned arrival location.',
                            'message' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not belong to your assigned arrival location.'
                        ], 422);
                    }
                    if (!$ticketItem || !$ticketItem->loadingSlip || !$ticketItem->loadingSlip->secondWeighbridge) {
                        return response()->json([
                            'error' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not have a completed Second Weighbridge.',
                            'message' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not have a completed Second Weighbridge.'
                        ], 422);
                    }
                }
            }
        }

        if (!$isSuperAdmin && $request->arrival_location_csv) {
            $reqArrivalIds = array_filter(explode(',', $request->arrival_location_csv));
            foreach ($reqArrivalIds as $arrId) {
                if (!in_array(trim($arrId), $locations)) {
                    return response()->json([
                        'error' => 'You are not authorized to create a Delivery Challan for this factory location.',
                        'message' => 'You are not authorized to create a Delivery Challan for this factory location.'
                    ], 422);
                }
            }
        }
        // if(strtotime($delivery_order->dispatch_date) <= strtotime($request->date)) {
        //     return response()->json("Selected Delivery order is expired. Please select a different Delivery order", 422);
        // }
        
        
        try {
            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;
          
            // Calculate total qty
            $total_qty = 0;
            if(is_array($request->qty)) {
                $total_qty = array_sum($request->qty);
            }

            // Calculate total bags directly from Loading Slip
            $loading_slip_bags = 0;
            if (is_array($request->ticket_id)) {
                $unique_ticket_ids = array_unique(array_filter($request->ticket_id));
                foreach ($unique_ticket_ids as $tId) {
                    $ls = \App\Models\Sales\LoadingSlip::where('loading_program_item_id', $tId)->first();
                    if ($ls && $ls->no_of_bags > 0) {
                        $loading_slip_bags += (float)$ls->no_of_bags;
                    }
                }
            }

            $total_bags = $loading_slip_bags;
            if ($total_bags <= 0) {
                if (is_array($request->no_of_bags)) {
                    $total_bags = array_sum($request->no_of_bags);
                } elseif (is_numeric($request->no_of_bags)) {
                    $total_bags = (float)$request->no_of_bags;
                }
            }

            // Auto calculate labour rate and amount based on matched rules
            $labour_rate = ($request->labour_rate === 'N/A' || $request->labour_rate === null) ? 0 : (float)$request->labour_rate;
            $labour_amount = $request->labour_amount ? (float)$request->labour_amount : 0;

            if ($request->labour) {
                $arrival_id = explode(',', $arrival_location_csv)[0] ?? null;
                $first_item_id = $request->item_id[0] ?? null;
                $first_bag_size = $request->bag_size[0] ?? null;
                
                if ($first_item_id && $first_bag_size && $arrival_id) {
                    $product = Product::find($first_item_id);
                    $category_id = $product ? $product->category_id : null;
                    
                    $clean_packing = is_numeric($first_bag_size) ? $first_bag_size : trim(explode(',', (string)$first_bag_size)[0]);
                    
                    $bag_packing = \App\Models\BagPacking::select("id")
                        ->where(function($q) use ($clean_packing) {
                            $q->where("name", $clean_packing . " kg")
                              ->orWhere("name", $clean_packing . "KG")
                              ->orWhere("name", "like", $clean_packing . "%");
                        })
                        ->where(function($q) {
                            $q->where("status", 1)->orWhere("status", 'active');
                        })
                        ->first();
                    
                    if ($category_id && $bag_packing) {
                        $rateObj = \App\Models\Master\LabourRate::where("category_id", $category_id)
                            ->where("factory_id", $arrival_id)
                            ->where("bag_packing_id", $bag_packing->id)
                            ->where(function($q) {
                                $q->where("status", 1)->orWhere("status", 'active');
                            })
                            ->first();
                            
                        if ($rateObj) {
                            $labour_rate = $rateObj->rate;
                        }
                    }
                }
                
                if ($labour_rate > 0) {
                    $labour_amount = $total_bags * $labour_rate;
                }
            }

            // Auto calculate transporter amount based on Logistics
            $transporter_amount = $request->transporter_amount ? (float)$request->transporter_amount : 0;
            
            if ($request->transporter) {
                $transporter_rate = 0;
                $transporter_rate_type = '';
                $first_do_id = explode(',', (string)$do_id)[0] ?? null;
                $delivery_order = $first_do_id ? DeliveryOrder::find($first_do_id) : null;
                
                if ($delivery_order && $delivery_order->so_id) {
                    $logistics = \App\Models\Sales\Logistics::where('type', 'sale_order')
                        ->where('sale_order_id', $delivery_order->so_id)
                        ->first();
                        
                    if ($logistics) {
                        $logisticsItem = \App\Models\Sales\LogisticsItem::where('logistics_id', $logistics->id)
                            ->where('transporter_id', $request->transporter)
                            ->first();
                            
                        if ($logisticsItem) {
                            $transporter_rate = (float)$logisticsItem->rate;
                            $transporter_rate_type = strtolower(trim($logisticsItem?->rate_type ?? ''));
                        }
                    }
                }
                if ($transporter_rate > 0) {
                    if ($transporter_rate_type == 'per truck') {
                        $transporter_amount = $transporter_rate;
                    } elseif ($transporter_rate_type == 'per mt') {
                        // Per MT: rate * (total_qty / 1000)
                        $transporter_amount = $transporter_rate * ($total_qty / 1000);
                    } else {
                        // Per KG or other: rate * total_qty
                        $transporter_amount = $total_qty * $transporter_rate;
                    }
                }
            }

            $delivery_challan = DeliveryChallan::create([
                "customer_id" => $request->customer_id,
                "reference_number" => self::getNumber($request, null, $request->date),
                "location_id" => $request->locations[0],
                "arrival_id" => $arrival_location_csv,
                "section_id" => $storage_location_csv,
                // 'subarrival_id' => $request->storage_id,
                "dispatch_date" => $request->date,
                "dc_no" => $request->dc_no,
                "sauda_type" => $request->sauda_type,
                "is_bardana" => $request->has('is_bardana') ? 1 : 0,
                "labour_status" => $request->labour_status ?? 'paid',
                "company_id" => $request->company_id,
                "labour" => $request->labour,
                "labour_amount" => $labour_amount,
                "transporter" => $request->transporter,
                "transporter_amount" => $transporter_amount,
                "inhouse-weighbridge" => $request->weighbridge,
                "weighbridge-amount" => $request->weighbridge_amount,
                "remarks" => $request->remarks,
                'labour_rate' => $labour_rate,
                "created_by_id" => auth()->user()?->id ?? auth()->id() ?? 1,
            ]);

            
            

            $do_ids = explode(',', $do_id);
            $syncData = [];

            foreach ($do_ids as $id) {
                if (empty($id)) continue;
                
                // Calculate total qty for this DO from the grid items
                $total_qty_for_do = 0;
                if ($request->has('do_data_id')) {
                    foreach ($request->do_data_id as $index => $do_data_id) {
                        $do_data = \App\Models\Sales\DeliveryOrderData::find($do_data_id);
                        if ($do_data && $do_data->delivery_order_id == $id) {
                            $total_qty_for_do += $request->qty[$index];
                        }
                    }
                } else {
                    $total_qty_for_do = $request->qty[0] ?? 0;
                }

                $syncData[$id] = [
                    'qty' => $total_qty_for_do,
                ];
            }

            $delivery_challan->delivery_order()->sync($syncData);

            // Store delivery challan data items
            $createdItems = [];
            foreach($request->item_id as $index => $item) {


                // if($request->no_of_bags[$index] > $balance) {
                //     return response()->json("Total balance is $balance. you can not exceed this balance", 422);
                // }

                $ticket_id = $request->ticket_id[$index] ?? null;
                $bags = $request->no_of_bags[$index] ?? 0;
                if ((!$bags || $bags <= 0) && $ticket_id) {
                    $loadingSlip = \App\Models\Sales\LoadingSlip::where('loading_program_item_id', $ticket_id)->first();
                    if ($loadingSlip && $loadingSlip->no_of_bags > 0) {
                        $bags = $loadingSlip->no_of_bags;
                    }
                }

                $isBardana = $request->has('is_bardana') && $request->is_bardana;
                $bagWeight = 0;
                $totalBagWeight = 0;
                $billedQty = $request->qty[$index];

                if ($isBardana) {
                    $bagWeight = isset($request->bag_weight[$index]) ? floatval($request->bag_weight[$index]) : 0;
                    $totalBagWeight = $bags * $bagWeight;
                    $billedQty = max(0, floatval($request->qty[$index]) - $totalBagWeight);
                }

                $dcData = $delivery_challan->delivery_challan_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "brand_id" => $request->brand_id[$index],
                    "no_of_bags" => $bags,
                    "bag_weight" => $bagWeight,
                    "total_bag_weight" => $totalBagWeight,
                    "billed_qty" => $billedQty,
                    "bag_size" => $request->bag_size[$index],
                    "description" => $request->desc[$index] ?? "",
                    "truck_no" => $request->truck_no[$index],
                    "container_number" => $request->container_number[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index],
                    "ticket_id" => $request->ticket_id[$index]
                ]);
                $createdItems[] = $dcData;
            }

            $delivery_challan->refresh();
            $newValues = $this->buildDeliveryChallanAuditValues($delivery_challan);
            AuditLogService::log(
                $delivery_challan,
                'delivery_challan_created',
                "Delivery Challan #{$delivery_challan->dc_no} created.",
                null,
                $newValues
            );

            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(["Delivery Challan has been created"]);
    }

    public function destroy(DeliveryChallan $delivery_challan) {
        abort_if(!canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        if (!$this->canUserAccessDeliveryChallan($delivery_challan)) {
            return response()->json([
                'error' => 'You are not authorized to delete Delivery Challan for this location.',
                'message' => 'You are not authorized to delete Delivery Challan for this location.'
            ], 422);
        }

        if(in_array(strtolower($delivery_challan->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Delivery Challan has been {$delivery_challan->am_approval_status} and cannot be deleted.",
                'message' => "This Delivery Challan has already been {$delivery_challan->am_approval_status}. Changes or deletion are no longer allowed."
            ], 422);
        }
        $deletedValues = $this->buildDeliveryChallanAuditValues($delivery_challan);
        AuditLogService::log(
            $delivery_challan,
            'delivery_challan_deleted',
            "Delivery Challan #{$delivery_challan->dc_no} deleted.",
            $deletedValues,
            null
        );

        $delivery_challan->receivingRequest()->delete();
        $delivery_challan->delete();

        return response()->json(["message" => "Delivery Challan has been deleted!"]);
    }

    public function update(DeliveryChallanRequest $request, DeliveryChallan $delivery_challan) {
        abort_if(!canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        if (!$this->canUserAccessDeliveryChallan($delivery_challan)) {
            return response()->json([
                'error' => 'You are not authorized to update Delivery Challan for this location.',
                'message' => 'You are not authorized to update Delivery Challan for this location.'
            ], 422);
        }

        $oldAuditValues = $this->buildDeliveryChallanAuditValues($delivery_challan);
        DB::beginTransaction();
        $do_id = $request->delivery_order_id;


        // delivery order's delivery date should not be greater than date

        $delivery_order = DeliveryOrder::find($do_id);
        if ($delivery_order && $delivery_order->isClosed()) {
            return response()->json([
                'error' => 'The Sale Order or Delivery Order for this Delivery Challan has been closed. Operations are locked.',
                'message' => 'The Sale Order or Delivery Order for this Delivery Challan has been closed. Operations are locked.'
            ], 422);
        }

        if (is_array($request->ticket_id)) {
            foreach ($request->ticket_id as $tId) {
                if ($tId) {
                    $ticketItem = LoadingProgramItem::with('loadingSlip.secondWeighbridge')->find($tId);
                    if ($ticketItem && $ticketItem->hasClosedSaleOrder()) {
                        return response()->json([
                            'error' => 'The Sale Order linked to ticket ' . ($ticketItem->transaction_number ?? $tId) . ' has been closed. Operations are locked.',
                            'message' => 'The Sale Order linked to ticket ' . ($ticketItem->transaction_number ?? $tId) . ' has been closed. Operations are locked.'
                        ], 422);
                    }
                    if (!$isSuperAdmin && $ticketItem && !in_array($ticketItem->arrival_location_id, $locations)) {
                        return response()->json([
                            'error' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not belong to your assigned arrival location.',
                            'message' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not belong to your assigned arrival location.'
                        ], 422);
                    }
                    if (!$ticketItem || !$ticketItem->loadingSlip || !$ticketItem->loadingSlip->secondWeighbridge) {
                        return response()->json([
                            'error' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not have a completed Second Weighbridge.',
                            'message' => 'Ticket ' . ($ticketItem->transaction_number ?? $tId) . ' does not have a completed Second Weighbridge.'
                        ], 422);
                    }
                }
            }
        }

        if (!$isSuperAdmin && $request->arrival_location_csv) {
            $reqArrivalIds = array_filter(explode(',', $request->arrival_location_csv));
            foreach ($reqArrivalIds as $arrId) {
                if (!in_array(trim($arrId), $locations)) {
                    return response()->json([
                        'error' => 'You are not authorized to update a Delivery Challan for this factory location.',
                        'message' => 'You are not authorized to update a Delivery Challan for this factory location.'
                    ], 422);
                }
            }
        }

        if(in_array(strtolower($delivery_challan->am_approval_status ?? ''), ['approved', 'rejected'])) {
            return response()->json([
                'error' => "Delivery Challan has been {$delivery_challan->am_approval_status} and cannot be updated.",
                'message' => "This Delivery Challan has already been {$delivery_challan->am_approval_status}. Further updates are not allowed from any screen."
            ], 422);
        }

        try {

            $arrival_location_csv = $request->arrival_location_csv;
            $storage_location_csv = $request->storage_location_csv;

            // Calculate total qty
            $total_qty = 0;
            if(is_array($request->qty)) {
                $total_qty = array_sum($request->qty);
            }

            // Calculate total bags directly from Loading Slip
            $loading_slip_bags = 0;
            if (is_array($request->ticket_id)) {
                $unique_ticket_ids = array_unique(array_filter($request->ticket_id));
                foreach ($unique_ticket_ids as $tId) {
                    $ls = \App\Models\Sales\LoadingSlip::where('loading_program_item_id', $tId)->first();
                    if ($ls && $ls->no_of_bags > 0) {
                        $loading_slip_bags += (float)$ls->no_of_bags;
                    }
                }
            }

            $total_bags = $loading_slip_bags;
            if ($total_bags <= 0) {
                if (is_array($request->no_of_bags)) {
                    $total_bags = array_sum($request->no_of_bags);
                } elseif (is_numeric($request->no_of_bags)) {
                    $total_bags = (float)$request->no_of_bags;
                } elseif ($delivery_challan && $delivery_challan->delivery_challan_data) {
                    $total_bags = $delivery_challan->delivery_challan_data->sum('no_of_bags');
                }
            }

            // Auto calculate labour rate and amount based on matched rules
            $labour_rate = ($request->labour_rate === 'N/A' || $request->labour_rate === null) ? 0 : (float)$request->labour_rate;
            $labour_amount = $request->labour_amount ? (float)$request->labour_amount : 0;

            if ($request->labour) {
                $arrival_id = explode(',', $arrival_location_csv)[0] ?? null;
                $first_item_id = $request->item_id[0] ?? null;
                $first_bag_size = $request->bag_size[0] ?? null;

                if ($first_item_id && $first_bag_size && $arrival_id) {
                    $product = Product::find($first_item_id);
                    $category_id = $product ? $product->category_id : null;
                    
                    $clean_packing = is_numeric($first_bag_size) ? $first_bag_size : trim(explode(',', (string)$first_bag_size)[0]);
                    
                    $bag_packing = \App\Models\BagPacking::select("id")
                        ->where(function($q) use ($clean_packing) {
                            $q->where("name", $clean_packing . " kg")
                              ->orWhere("name", $clean_packing . "KG")
                              ->orWhere("name", "like", $clean_packing . "%");
                        })
                        ->where(function($q) {
                            $q->where("status", 1)->orWhere("status", 'active');
                        })
                        ->first();
                    
                    if ($category_id && $bag_packing) {
                        $rateObj = \App\Models\Master\LabourRate::where("category_id", $category_id)
                            ->where("factory_id", $arrival_id)
                            ->where("bag_packing_id", $bag_packing->id)
                            ->where(function($q) {
                                $q->where("status", 1)->orWhere("status", 'active');
                            })
                            ->first();
                            
                        if ($rateObj) {
                            $labour_rate = $rateObj->rate;
                        }
                    }
                }
                
                if ($labour_rate > 0) {
                    $labour_amount = $total_bags * $labour_rate;
                }
            }

            // Auto calculate transporter amount based on Logistics
            $transporter_amount = $request->transporter_amount ? (float)$request->transporter_amount : 0;
            
            if ($request->transporter) {
                $transporter_rate = 0;
                $transporter_rate_type = '';
                $first_do_id = explode(',', (string)$do_id)[0] ?? null;
                $delivery_order = $first_do_id ? DeliveryOrder::find($first_do_id) : null;
                
                if ($delivery_order && $delivery_order->so_id) {
                    $logistics = \App\Models\Sales\Logistics::where('type', 'sale_order')
                        ->where('sale_order_id', $delivery_order->so_id)
                        ->first();
                        
                    if ($logistics) {
                        $logisticsItem = \App\Models\Sales\LogisticsItem::where('logistics_id', $logistics->id)
                            ->where('transporter_id', $request->transporter)
                            ->first();
                            
                        if ($logisticsItem) {
                            $transporter_rate = (float)$logisticsItem->rate;
                            $transporter_rate_type = strtolower(trim($logisticsItem?->rate_type ?? ''));
                        }
                    }
                }
                
                if ($transporter_rate > 0) {
                    if ($transporter_rate_type == 'per truck') {
                        $transporter_amount = $transporter_rate;
                    } elseif ($transporter_rate_type == 'per mt') {
                        // Per MT: rate * (total_qty / 1000)
                        $transporter_amount = $transporter_rate * ($total_qty / 1000);
                    } else {
                        // Per KG or other: rate * total_qty
                        $transporter_amount = $total_qty * $transporter_rate;
                    }
                }
            }

            $delivery_challan->update([
                "customer_id" => $request->customer_id,
                "reference_number" => $request->reference_number,
                // "location_id" => $request->locations,
                // "arrival_id" => $request->arrival_locations,
                "dispatch_date" => $request->date,
                "dc_no" => $request->dc_no,
                "sauda_type" => $request->sauda_type,
                "is_bardana" => $request->has('is_bardana') ? 1 : 0,
                "labour_status" => $request->labour_status ?? $delivery_challan->labour_status,
                "company_id" => $request->company_id,
                "labour" => $request->labour,
                "labour_amount" => $labour_amount,
                "transporter" => $request->transporter,
                "transporter_amount" => $transporter_amount,
                "inhouse-weighbridge" => $request->weighbridge,
                "weighbridge-amount" => $request->weighbridge_amount,
                "remarks" => $request->remarks,
                "arrival_id" => $arrival_location_csv,
                "section_id" => $storage_location_csv,
                'labour_rate' => $labour_rate,
                "created_by_id" => auth()->user()->id,
                "am_approval_status" => "pending",
                "am_change_made" => 1
            ]);

            $do_ids = explode(',', $do_id);
            $syncData = [];

            foreach ($do_ids as $id) {
                if (empty($id)) continue;
                
                // Calculate total qty for this DO from the grid items
                $total_qty_for_do = 0;
                if ($request->has('do_data_id')) {
                    foreach ($request->do_data_id as $index => $do_data_id) {
                        $do_data = \App\Models\Sales\DeliveryOrderData::find($do_data_id);
                        if ($do_data && $do_data->delivery_order_id == $id) {
                            $total_qty_for_do += $request->qty[$index];
                        }
                    }
                } else {
                    $total_qty_for_do = $request->qty[0] ?? 0;
                }

                $syncData[$id] = [
                    'qty' => $total_qty_for_do,
                ];
            }

            $delivery_challan->delivery_order()->sync($syncData);
            $delivery_challan->delivery_challan_data()->delete();

            $createdItems = [];
            foreach($request->item_id as $index => $item) {
                $ticket_id = $request->ticket_id[$index] ?? null;
                $bags = $request->no_of_bags[$index] ?? 0;
                if ((!$bags || $bags <= 0) && $ticket_id) {
                    $loadingSlip = \App\Models\Sales\LoadingSlip::where('loading_program_item_id', $ticket_id)->first();
                    if ($loadingSlip && $loadingSlip->no_of_bags > 0) {
                        $bags = $loadingSlip->no_of_bags;
                    }
                }

                $isBardana = $request->has('is_bardana') && $request->is_bardana;
                $bagWeight = 0;
                $totalBagWeight = 0;
                $billedQty = $request->qty[$index];

                if ($isBardana) {
                    $bagWeight = isset($request->bag_weight[$index]) ? floatval($request->bag_weight[$index]) : 0;
                    $totalBagWeight = $bags * $bagWeight;
                    $billedQty = max(0, floatval($request->qty[$index]) - $totalBagWeight);
                }

                $dcData = $delivery_challan->delivery_challan_data()->create([
                    "item_id" => $request->item_id[$index],
                    "qty" => $request->qty[$index],
                    "rate" => $request->rate[$index],
                    "brand_id" => $request->brand_id[$index],
                    "no_of_bags" => $bags,
                    "bag_weight" => $bagWeight,
                    "total_bag_weight" => $totalBagWeight,
                    "billed_qty" => $billedQty,
                    "bag_size" => $request->bag_size[$index],
                    "description" => $request->desc[$index] ?? "",
                    "truck_no" => $request->truck_no[$index],
                    "container_number" => $request->container_number[$index],
                    "ticket_id" => $request->ticket_id[$index],
                    "do_data_id" => $request->do_data_id[$index],
                    "bag_type" => $request->bag_type[$index]
                ]);
                $createdItems[] = $dcData;
            }

            $delivery_challan->refresh();
            $newAuditValues = $this->buildDeliveryChallanAuditValues($delivery_challan);

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
                    $delivery_challan,
                    'delivery_challan_updated',
                    "Delivery Challan #{$delivery_challan->dc_no} updated.",
                    $changedOld,
                    $changedNew
                );
            }

            DB::commit();
        } catch(\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(["Delivery Challan has been created"]);

    }

    public function edit(DeliveryChallan $delivery_challan) {
        abort_if(!canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        abort_if(!$this->canUserAccessDeliveryChallan($delivery_challan), 403);
        $delivery_challan->load("delivery_order.delivery_order_data", "delivery_challan_data");
        $customers = Customer::all();
        $delivery_orders = $delivery_challan->delivery_order;
        $locationIds = $delivery_orders->pluck('location_id')->filter()->unique();

        $arrivalLocationIds = $delivery_orders->pluck('arrival_location_id')->filter()->unique();
        
        $sectionIds = $delivery_orders->pluck('sub_arrival_location_id')->filter()->unique();

        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = ArrivalLocation::whereIn('id', explode(",", $delivery_challan->arrival_id))->get();
        $sections = ArrivalSubLocation::whereIn('id', explode(",", $delivery_challan->section_id))->get();

        $initial_transporter_rate = 0;
        $initial_transporter_rate_type = '';
        if ($delivery_challan->transporter) {
            $first_do = $delivery_challan->delivery_order->first();
            if ($first_do && $first_do->so_id) {
                $logistics = \App\Models\Sales\Logistics::where('type', 'sale_order')
                    ->where('sale_order_id', $first_do->so_id)
                    ->first();
                if ($logistics) {
                    $logisticsItem = \App\Models\Sales\LogisticsItem::where('logistics_id', $logistics->id)
                        ->where('transporter_id', $delivery_challan->transporter)
                        ->first();
                    if ($logisticsItem) {
                        $initial_transporter_rate = (float)$logisticsItem->rate;
                        $initial_transporter_rate_type = strtolower(trim($logisticsItem?->rate_type ?? ''));
                    }
                }
            }
        }

        return view("management.sales.delivery-challan.edit", [
            "customers" => $customers,
            "delivery_orders" => $delivery_orders,
            "delivery_challan" => $delivery_challan,
            "locations" => $locations,
            "arrivalLocations" => $arrivalLocations,
            "sections" => $sections,
            "locationIds" => $locationIds,
            "arrivalLocationIds" => $arrivalLocationIds,
            "sectionIds" => $sectionIds,
            "transporters" => \App\Models\Master\Transporter::all(),
            "initial_transporter_rate" => $initial_transporter_rate,
            "initial_transporter_rate_type" => $initial_transporter_rate_type,
        ]);
    }

    public function view(DeliveryChallan $delivery_challan) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list'), 403);
        abort_if(!$this->canUserAccessDeliveryChallan($delivery_challan), 403);
        $delivery_challan->load("delivery_order.delivery_order_data");
        $payment_terms = PaymentTerm::all();
        $customers = Customer::all();
        $items = Product::all();
        $pay_types = PayType::select('name', 'id')->where('status', 'active')->get();

        $delivery_orders = $delivery_challan->delivery_order;

        $locationIds = $delivery_orders->pluck('location_id')->filter()->unique();


        $arrivalLocationIds = $delivery_orders->pluck('arrival_location_id')->filter()->unique();
        
        $sectionIds = $delivery_orders->pluck('sub_arrival_location_id')->filter()->unique();

        $locations = CompanyLocation::whereIn('id', $locationIds)->get();
        $arrivalLocations = ArrivalLocation::whereIn('id', explode(",", $delivery_challan->arrival_id))->get();
        $sections = ArrivalSubLocation::whereIn('id', explode(",", $delivery_challan->section_id))->get();

        return view("management.sales.delivery-challan.view", [
            "customers" => $customers,
            "delivery_orders" => $delivery_orders,
            "delivery_challan" => $delivery_challan,
            "locations" => $locations,
            "arrivalLocations" => $arrivalLocations,
            "sections" => $sections,
            "locationIds" => $locationIds,
            "arrivalLocationIds" => $arrivalLocationIds,
            "sectionIds" => $sectionIds,
            "transporters" => \App\Models\Master\Transporter::all(),
        ]);
    }

    public function getList(Request $request) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $userRoleIds = (new DeliveryChallan)->getUserRoleIdsForApproval($authUser);
        $isApprover = false;
        if (!$isSuperAdmin && !empty($userRoleIds)) {
            $dcModule = (new DeliveryChallan)->getApprovalModule();
            $moduleId = $dcModule ? $dcModule->id : 18;
            $isApprover = ApprovalModuleRole::where('module_id', $moduleId)
                ->whereIn('role_id', $userRoleIds)
                ->exists();
        }
        $locations = $this->getUserArrivalLocations();

        $perPage = $request->get('per_page', 25);

        // Eager load the inquiry + all its items + related product
        $delivery_challans = DeliveryChallan::with(['delivery_order', 'delivery_challan_data.loadingProgramItem.acceptedDispatchQc'])
            ->when(!$isSuperAdmin && !$isApprover, function ($query) use ($locations, $authUser) {
                $query->where(function ($q) use ($locations, $authUser) {
                    if (!empty($locations)) {
                        foreach ($locations as $locId) {
                            $q->orWhereRaw("FIND_IN_SET(?, arrival_id)", [$locId]);
                        }
                        $q->orWhereHas('delivery_challan_data.loadingProgramItem', function ($sq) use ($locations) {
                            $sq->whereIn('arrival_location_id', $locations);
                        });
                    }
                    $q->orWhereHas('delivery_order.salesOrder', function ($soQ) use ($authUser) {
                        $soQ->where('created_by', $authUser->id);
                    });
                });
            })
            // Filter by DO No
            ->when($request->filled('do_id_for_filter') && $request->do_id_for_filter != 'all', function ($q) use ($request) {
                $q->whereHas('delivery_order', function ($sq) use ($request) {
                    $sq->where('delivery_order_id', $request->do_id_for_filter);
                });
            })
            // Filter by Customer
            ->when($request->filled('customer_id_for_filter') && $request->customer_id_for_filter != 'all', function ($q) use ($request) {
                $q->where('customer_id', $request->customer_id_for_filter);
            })
            // Filter by Item (through delivery_challan_data relationship)
            ->when($request->filled('item_id_for_filter') && $request->item_id_for_filter != 'all', function ($q) use ($request) {
                $q->whereHas('delivery_challan_data', function ($sq) use ($request) {
                    $sq->where('item_id', $request->item_id_for_filter);
                });
            })
            // Filter by Date Range
            ->when($request->filled('date_range_for_filter'), function ($q) use ($request) {
                $dates = explode(' - ', $request->date_range_for_filter);
                if (count($dates) == 2) {
                    $q->whereBetween('dispatch_date', [trim($dates[0]), trim($dates[1])]);
                }
            })
            // Filter by Status
            ->when($request->filled('status_for_filter') && $request->status_for_filter != 'all', function ($q) use ($request) {
                $q->where('am_approval_status', $request->status_for_filter);
            })
            // Custom Search
            ->when($request->filled('search_for_filter'), function ($q) use ($request) {
                $searchTerm = '%' . strtolower($request->search_for_filter) . '%';
                return $q->where(function ($sq) use ($searchTerm) {
                    $sq->whereRaw('LOWER(`dc_no`) LIKE ?', [$searchTerm])
                       ->orWhereHas('delivery_challan_data', function ($q) use ($searchTerm) {
                            $q->whereRaw('CAST(`qty` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`rate` AS CHAR) LIKE ?', [$searchTerm])
                                ->orWhereRaw('CAST(`qty` * `rate` AS CHAR) LIKE ?', [$searchTerm]);
                        });
                });
            })
            // Filter by Truck No
            ->when($request->filled('truck_no_for_filter'), function ($q) use ($request) {
                $truckNo = '%' . strtolower(trim($request->truck_no_for_filter)) . '%';
                $q->whereHas('delivery_challan_data', function ($sq) use ($truckNo) {
                    $sq->whereRaw('LOWER(truck_no) LIKE ?', [$truckNo]);
                });
            })
            ->when(!$isSuperAdmin && !$isApprover, function ($q) use ($authUser) {
                return $q->where(function ($subQ) use ($authUser) {
                    $subQ->where('created_by_id', $authUser->id)
                        ->orWhereHas('delivery_order.salesOrder', function ($soQ) use ($authUser) {
                            $soQ->where('created_by', $authUser->id);
                        });
                });
            })
            ->latest()
            ->paginate($perPage);

        // Map arrival location names for factories
        $allArrivalIds = [];
        foreach ($delivery_challans as $dc) {
            if (!empty($dc->arrival_id)) {
                $allArrivalIds = array_merge($allArrivalIds, explode(',', (string)$dc->arrival_id));
            } else {
                foreach ($dc->delivery_order as $do) {
                    if (!empty($do->arrival_location_id)) {
                        $allArrivalIds = array_merge($allArrivalIds, explode(',', (string)$do->arrival_location_id));
                    }
                }
            }
        }
        $allArrivalIds = array_values(array_filter(array_unique(array_map('trim', $allArrivalIds))));
        $arrivalLocationMap = !empty($allArrivalIds)
            ? ArrivalLocation::whereIn('id', $allArrivalIds)->pluck('name', 'id')->toArray()
            : [];

        $groupedData = [];

        foreach ($delivery_challans as $delivery_challan) {
            $so_no = $delivery_challan->dc_no;
            $items = $delivery_challan->delivery_challan_data;

            if ($items->isEmpty()) {
                continue;
            }

            $dcArrivalIds = !empty($delivery_challan->arrival_id)
                ? explode(',', (string)$delivery_challan->arrival_id)
                : $delivery_challan->delivery_order->pluck('arrival_location_id')->flatMap(fn($id) => explode(',', (string)$id))->filter()->unique()->toArray();

            $factoryNamesList = [];
            foreach ($dcArrivalIds as $aId) {
                $aId = trim((string)$aId);
                if (isset($arrivalLocationMap[$aId])) {
                    $factoryNamesList[] = $arrivalLocationMap[$aId];
                }
            }
            $factoryName = !empty($factoryNamesList) ? implode(', ', $factoryNamesList) : 'N/A';

            $itemRows = [];
            foreach ($items as $itemData) {
                $itemRows[] = [
                    'item_data'      => $itemData,
                    'item'           => $itemData->item ?? (object)['unitOfMeasure' => null],
                    'accepted_qc_id' => $itemData->loadingProgramItem->acceptedDispatchQc->id ?? null,
                    'truck_no'       => $itemData->truck_no ?? null,
                ];
            }

            $groupedData[] = [
                'sale_order' => $delivery_challan,
                'so_no' => $so_no,
                'created_by_id' => $delivery_challan->created_by_id,
                'delivery_date' => $delivery_challan->delivery_date,
                'id' => $delivery_challan->id,
                'customer_id' => $delivery_challan->customer_id,
                'factory_name' => $factoryName,
                'status' => $delivery_challan->am_approval_status,
                'created_at' => $delivery_challan->created_at,
                'customer' => $delivery_challan->customer,
                'rowspan' => count($itemRows),
                'items' => $itemRows,
                'is_bardana' => (bool)$delivery_challan->is_bardana,
             ];
        }

        return view('management.sales.delivery-challan.getList', [
            'DeliveryChallans' => $delivery_challans,           // for pagination
            'groupedDeliveryChallans' => $groupedData,  // our grouped data
        ]);
    }

    public function getNumber(Request $request, $locationId = null, $contractDate = null)
    {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list') && !canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);

        $date = Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $prefix = 'DC-'.Carbon::parse($contractDate ?? $request->contract_date)->format('Y-m-d');

        $latestContract = DeliveryChallan::where('dc_no', 'like', "$prefix-%")
            ->latest()
            ->first();

        $datePart = Carbon::parse($date)->format('Y-m-d');
        

        if ($latestContract) {
            $parts = explode('-', $latestContract->dc_no);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $dc_no = 'DC-'.$datePart.'-'.str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        
        if (! $locationId && ! $contractDate) {
            return response()->json([
                'success' => true,
                'dc_no' => $dc_no,
            ]);
        }

        return $dc_no;
    }

    public function get_delivery_orders(Request $request) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list') && !canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        $customer_id = $request->customer_id;

        if (!$customer_id) {
            return [];
        }

        $delivery_orders = DeliveryOrder::with("delivery_order_data")
            ->where("customer_id", $customer_id)
            ->where("am_approval_status", "approved")
            ->whereDoesntHaveClosedSaleOrder()
            ->where(function ($q) use ($request) {
                $q->where('do_status', 'active');
                if ($request->delivery_challan_id) {
                    $q->orWhereHas('delivery_challans', function ($sq) use ($request) {
                        $sq->where('delivery_challans.id', $request->delivery_challan_id);
                    });
                }
            })
            ->whereHas('loadingPrograms.loadingProgramItems', function($query) use ($isSuperAdmin, $locations) {
                // Ticket must have a loading slip with second weighbridge
                $query->whereHas('loadingSlip.secondWeighbridge')
                    // AND ticket must NOT be used in any delivery challan
                    ->whereDoesntHave('delivery_challan_data')
                    ->when(!$isSuperAdmin, function ($q) use ($locations) {
                        $q->whereIn('arrival_location_id', $locations);
                    });
            })
            ->get();

       
        $data = [];

        foreach($delivery_orders as $delivery_order) {
            // Get arrival location names for comma-separated IDs
            $arrivalNames = [];
            if ($delivery_order->arrival_location_id) {
                $arrivalIds = explode(',', $delivery_order->arrival_location_id);
                $arrivalNames = ArrivalLocation::whereIn('id', $arrivalIds)->pluck('name', 'id')->toArray();
            }

            // Get section names for comma-separated IDs
            $sectionNames = [];
            if ($delivery_order->sub_arrival_location_id) {
                $sectionIds = explode(',', $delivery_order->sub_arrival_location_id);
                $sectionNames = ArrivalSubLocation::whereIn('id', $sectionIds)->pluck('name', 'id')->toArray();
            }

            $data[] = [
                "id" => $delivery_order->id,
                "text" => $delivery_order->reference_no,
                "location_id" => $delivery_order->location_id,
                "arrival_location_id" => $delivery_order->arrival_location_id,
                "sub_arrival_location_id" => $delivery_order->sub_arrival_location_id,
                "location_name" => get_location_name_by_id($delivery_order->location_id),
                "arrival_names" => $arrivalNames, // Array of id => name
                "section_names" => $sectionNames, // Array of id => name
            ];
        }

        return $data;
    }

    public function getItems(Request $request) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list') && !canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $delivery_order_ids = $request->delivery_order_ids;
        $delivery_orders = DeliveryOrder::with("delivery_order_data")->whereIn("id", $delivery_order_ids)->get();
        $items = Product::select("id", "name")->get();

        $delivery_orders = $delivery_orders->map(function($delivery_order) {
            $delivery_challan = $delivery_order->delivery_challans;
            $spent = $delivery_challan->sum("pivot.qty");
            $delivery_order->spent = $spent;
            return $delivery_order;
        });


        // return view("management.sales.delivery-challan.getItem", compact("delivery_orders", "items"));
    }


    public function getItemsByTickets(Request $request) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list') && !canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        $ticket_id = $request->ticket_id;
        $loading_programs = LoadingProgramItem::with([
            "loadingProgram.deliveryOrder.delivery_order_data",
            "loadingSlip.secondWeighbridge"
        ])
        ->when(!$isSuperAdmin, function ($q) use ($locations) {
            $q->whereIn('arrival_location_id', $locations);
        })
        ->where("id", $ticket_id)->get();
        $items = Product::select("id", "name")->get();

        return view("management.sales.delivery-challan.getItem", compact("loading_programs", "items"));
    }

    public function getTickets(Request $request) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list') && !canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        $delivery_order_ids = $request->delivery_order_ids;
        $delivery_challan_id = $request->delivery_challan_id; // For edit mode - include tickets from this DC

        if (empty($delivery_order_ids)) {
            return response()->json(['tickets' => []]);
        }

        // Get tickets (loading program items) that belong to selected DOs and have second weighbridges
        // First get all loading program items that belong to selected DOs
        $query = LoadingProgramItem::with([
                'loadingProgram.deliveryOrder',
                'dispatchQc'
            ])
            ->whereDoesntHaveClosedSaleOrder()
            ->whereHas("dispatchQc")
            ->whereHas('loadingProgram', function($q) use ($delivery_order_ids) {
                $q->whereIn('delivery_order_id', $delivery_order_ids);
            })
            ->when(!$isSuperAdmin, function ($q) use ($locations) {
                $q->whereIn('arrival_location_id', $locations);
            });

        // Exclude tickets that are already used in other delivery challans (but include tickets from current DC being edited)
        if ($delivery_challan_id) {
            $query->where(function($q) use ($delivery_challan_id) {
                $q->whereDoesntHave("delivery_challan_data")
                  ->orWhereHas("delivery_challan_data", function($subQ) use ($delivery_challan_id) {
                      $subQ->where("delivery_challan_id", $delivery_challan_id);
                  });
            });
        } else {
            $query->whereDoesntHave("delivery_challan_data");
        }

        $allTickets = $query->get();

        // Filter to only include tickets that have second weighbridges
        $tickets = $allTickets->filter(function($ticket) {
            return $ticket->loadingSlip && $ticket->loadingSlip->secondWeighbridge;
        })->map(function($ticket) {
            return [
                'id' => $ticket->id,
                'text' => $ticket->transaction_number . ' -- ' . $ticket->truck_number
            ];
        });

        // Debug: Log the count and details
        \Log::info('Delivery Challan getTickets called', [
            'delivery_order_ids' => $delivery_order_ids,
            'delivery_challan_id' => $delivery_challan_id,
            'all_tickets_found' => $allTickets->count(),
            'filtered_tickets_with_second_weighbridge' => $tickets->count(),
            'tickets' => $tickets->toArray()
        ]);

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Get tickets with accepted Dispatch QC for initial selection in Delivery Challan
     */
    public function getTicketsWithDispatchQc(Request $request) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list') && !canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        $delivery_challan_id = $request->delivery_challan_id;

        $query = LoadingProgramItem::with([
                'loadingProgram.deliveryOrder.customer',
                'loadingProgram.deliveryOrder',
                'loadingProgram.saleOrder',
                'dispatchQc',
                'arrivalLocation',
                'subArrivalLocation',
                'loadingSlip.secondWeighbridge'
            ])
            ->whereDoesntHaveClosedSaleOrder()
            ->whereHas("loadingSlip.secondWeighbridge")
            ->when(!$isSuperAdmin, function ($q) use ($locations) {
                $q->whereIn('arrival_location_id', $locations);
            });

        if ($delivery_challan_id) {
            $query->where(function($q) use ($delivery_challan_id) {
                $q->whereDoesntHave('delivery_challan_data')
                  ->orWhereHas('delivery_challan_data', function($subQ) use ($delivery_challan_id) {
                      $subQ->where('delivery_challan_id', $delivery_challan_id);
                  });
            });
        } else {
            $query->whereDoesntHave('delivery_challan_data');
        }

        $tickets = $query->get()
            ->map(function($ticket) {
                return [
                    'id' => $ticket->id,
                    'text' => $ticket->transaction_number . ' -- ' . $ticket->truck_number,
                    'transaction_number' => $ticket->transaction_number,
                    'truck_number' => $ticket->truck_number
                ];
            });

        return response()->json(['tickets' => $tickets]);
    }

    /**
     * Get ticket data for auto-filling Delivery Challan form
     */
    public function getTicketDataForDC(Request $request) {
        abort_if(!canAccess('sales-delivery-challan-list') && !auth()->user()->can('sales-delivery-challan-list') && !canAccess('sales-delivery-challan-create') && !auth()->user()->can('sales-delivery-challan-create'), 403);
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->user_type === 'super-admin' || $authUser->hasRole('Admin') || $authUser->hasRole('admin'));
        $locations = $this->getUserArrivalLocations();

        $ticket_id = $request->ticket_id;

        if (!$ticket_id) {
            return response()->json(['error' => 'No ticket selected'], 400);
        }

        $ticket = LoadingProgramItem::with([
            'loadingProgram.deliveryOrder.customer',
            'loadingProgram.deliveryOrder',
            'loadingProgram.saleOrder',
            'loadingProgram',
            'dispatchQc',
            'arrivalLocation',
            'subArrivalLocation',
            'firstWeighbridge.truckType',
            'loadingSlip.secondWeighbridge',
            'transporter'
        ])->findOrFail($ticket_id);

        if (!$isSuperAdmin && !in_array($ticket->arrival_location_id, $locations)) {
            return response()->json(['error' => 'Unauthorized location for this ticket.'], 403);
        }

        if ($ticket->hasClosedSaleOrder()) {
            return response()->json([
                'error' => 'The Sale Order linked to this ticket has been closed. Operations are locked.',
                'message' => 'The Sale Order linked to this ticket has been closed. Operations are locked.'
            ], 422);
        }

        $loadingSlip = \App\Models\Sales\LoadingSlip::where("loading_program_item_id", $ticket_id)->first();

        if (!$loadingSlip) {
            return response()->json(['error' => 'Loading slip not found for this ticket'], 404);
        }

        $deliveryOrder = $loadingSlip->deliveryOrder;
        if (!$deliveryOrder) {
            return response()->json(['error' => 'Delivery order not found for this loading slip'], 404);
        }

        $loadingProgram = $ticket->loadingProgram;
        if (!$loadingProgram) {
            return response()->json(['error' => 'Loading program not found for this ticket'], 404);
        }
        
        // Get location names from loading program (for company locations)
        $companyLocationIds = $loadingProgram->company_locations ?? [];

        // Get location names
        $companyLocations = [];
        if (!empty($companyLocationIds)) {
            $companyLocations = CompanyLocation::whereIn('id', $companyLocationIds)
                ->get()
                ->map(fn($loc) => ['id' => $loc->id, 'text' => $loc->name])
                ->toArray();
        }

        

        // Use the ticket's own arrival location (Factory) and sub arrival location (Gala)
        $arrivalLocations = [];
        $arrivalLocationIds = [];
        if ($ticket->arrival_location_id) {
            $arrivalLocationIds = [$ticket->arrival_location_id];
            $arrivalLoc = $ticket->arrivalLocation;
            if ($arrivalLoc) {
                $arrivalLocations = [['id' => $arrivalLoc->id, 'text' => $arrivalLoc->name]];
            }
        }

        $packing = $ticket->packing;
        // Handle comma-separated values to find a single numeric packing for bag lookup
        $clean_packing = is_numeric($packing) ? $packing : trim(explode(',', (string)$packing)[0]);

        $bag_packing = \App\Models\BagPacking::select("id")
                                    ->where(function($q) use ($clean_packing) {
                                        $q->where("name", $clean_packing . " kg")
                                          ->orWhere("name", $clean_packing . "KG")
                                          ->orWhere("name", "like", $clean_packing . "%");
                                    })
                                    ->where(function($q) {
                                        $q->where("status", 1)->orWhere("status", 'active');
                                    })
                                    ->first();

        $arrival_location_id = $ticket->arrival_location_id;
        $delivery_order_id = $ticket->delivery_order_id ?? ($deliveryOrder ? $deliveryOrder->id : null);
        $delivery_order = $delivery_order_id ? DeliveryOrder::find($delivery_order_id) : $deliveryOrder;
        
        $category_id = null;
        if ($delivery_order && $delivery_order->delivery_order_data->isNotEmpty()) {
            $product = Product::find($delivery_order->delivery_order_data[0]->item_id);
            $category_id = $product ? $product->category_id : null;
        }

        $labour_rate = null;
        if ($category_id && $arrival_location_id && $bag_packing) {
            $labour_rate = \App\Models\Master\LabourRate::select("id", "rate")
                                        ->where("category_id", $category_id)
                                        ->where("factory_id", $arrival_location_id)
                                        ->where("bag_packing_id", $bag_packing->id)
                                        ->where(function($q) {
                                            $q->where("status", 1)->orWhere("status", 'active');
                                        })
                                        ->first();
        }
       
        $subArrivalLocations = [];
        $subArrivalLocationIds = [];
        if ($ticket->sub_arrival_location_id) {
            $subArrivalLocationIds = [$ticket->sub_arrival_location_id];
            $subArrivalLoc = $ticket->subArrivalLocation;
            if ($subArrivalLoc) {
                $subArrivalLocations = [['id' => $subArrivalLoc->id, 'text' => $subArrivalLoc->name]];
            }
        }


        // Get loading slip labour
        $loadingSlipLabour = $loadingSlip?->labour ?? null;

        $deliveryOrders = collect();
        if ($ticket->deliveryOrders->isNotEmpty()) {
            $deliveryOrders = $ticket->deliveryOrders;
        } elseif ($deliveryOrder) {
            $deliveryOrders->push($deliveryOrder);
        }

        $dos = [];
        foreach ($deliveryOrders as $d) {
            $dos[] = [
                'id' => $d->id,
                'reference_no' => $d->reference_no,
                'sauda_type' => strtolower($d->sauda_type ?? ''),
                'remarks' => $d->remarks ?? '',
                'transporter_used' => strtolower($d->salesOrder->transporter_used ?? ''),
            ];
        }

        // Get transporter rate from Logistics
        $transporter_rate = 0;
        $transporter_rate_type = '';
        if ($deliveryOrder && $deliveryOrder->so_id && $ticket->transporter_id) {
            $logistics = \App\Models\Sales\Logistics::where('type', 'sale_order')
                ->where('sale_order_id', $deliveryOrder->so_id)
                ->first();
            if ($logistics) {
                $logisticsItem = \App\Models\Sales\LogisticsItem::where('logistics_id', $logistics->id)
                    ->where('transporter_id', $ticket->transporter_id)
                    ->first();
                if ($logisticsItem) {
                    $transporter_rate = (float)$logisticsItem->rate;
                    $transporter_rate_type = strtolower(trim($logisticsItem?->rate_type ?? ''));
                }
            }
        }

        $data = [
            'success' => true,
            'rate' => $labour_rate ? $labour_rate->rate : "N/A",
            'ticket' => [
                'id' => $ticket->id,
                'transaction_number' => $ticket->transaction_number,
                'truck_number' => $ticket->truck_number,
            ],
            'delivery_orders' => $dos,
            'delivery_order' => [
                'id' => $deliveryOrder->id,
                'reference_no' => $deliveryOrder->reference_no,
                'sauda_type' => strtolower($deliveryOrder->sauda_type ?? ''),
                'remarks' => $deliveryOrder->remarks ?? '',
                'transporter_used' => strtolower($deliveryOrder->salesOrder->transporter_used ?? ''),
            ],
            'customer' => [
                'id' => $deliveryOrder->customer->id ?? null,
                'name' => $deliveryOrder->customer->name ?? 'N/A',
            ],
            'locations' => [
                'company_locations' => $companyLocations,
                'company_location_ids' => $companyLocationIds,
                'arrival_locations' => $arrivalLocations,
                'arrival_location_ids' => $arrivalLocationIds,
                'sub_arrival_locations' => $subArrivalLocations,
                'sub_arrival_location_ids' => $subArrivalLocationIds,
            ],
            'loading_slip_labour' => $loadingSlipLabour,
            'is_bardana' => (bool) (
                (!empty($ticket->loadingProgram?->saleOrder?->is_bardana))
                || (!empty($deliveryOrder?->salesOrder?->is_bardana))
                || (!empty($ticket->loadingProgram?->deliveryOrder?->salesOrder?->is_bardana))
            ),
            'transporter' => [
                'id' => $ticket->transporter_id,
                'name' => $ticket->transporter->name ?? 'N/A',
                'rate' => $transporter_rate,
                'rate_type' => $transporter_rate_type,
            ],
            'weighbridge' => [
                'amount' => $ticket->firstWeighbridge?->weighbridge_amount ? (float)$ticket->firstWeighbridge->weighbridge_amount : 0,
                'truck_type_id' => $ticket->firstWeighbridge?->truck_type_id,
                'truck_type_name' => $ticket->firstWeighbridge?->truckType?->name ?? 'N/A',
                'first_weight' => $ticket->firstWeighbridge?->first_weight ?? null,
                'first_weighbridge_location_id' => $ticket->first_weighbridge_location_id ?? $ticket->arrival_location_id,
            ],
            'weighbridge_amount' => $ticket->firstWeighbridge?->weighbridge_amount ? (float)$ticket->firstWeighbridge->weighbridge_amount : 0,
        ];

        return response()->json($data);
    }

    /**
     * Build standard audit log snapshot for a delivery challan.
     */
    public function buildDeliveryChallanAuditValues(DeliveryChallan $deliveryChallan): array
    {
        $deliveryChallan->loadMissing(['customer', 'delivery_order', 'delivery_challan_data.product', 'delivery_challan_data.loadingProgramItem']);

        $factoryNames = [];
        if ($deliveryChallan->arrival_id) {
            $fIds = array_filter(explode(',', $deliveryChallan->arrival_id));
            if (!empty($fIds)) {
                $factoryNames = \App\Models\Master\ArrivalLocation::whereIn('id', $fIds)->pluck('name')->toArray();
            }
        }

        $sectionNames = [];
        if ($deliveryChallan->section_id) {
            $sIds = array_filter(explode(',', $deliveryChallan->section_id));
            if (!empty($sIds)) {
                $sectionNames = \App\Models\Master\ArrivalSubLocation::whereIn('id', $sIds)->pluck('name')->toArray();
            }
        }

        $companyLoc = $deliveryChallan->location_id ? \App\Models\Master\CompanyLocation::find($deliveryChallan->location_id)?->name : null;
        $labourVendor = $deliveryChallan->labour ? \App\Models\Master\Vendor::find($deliveryChallan->labour)?->name : null;
        $transporterName = $deliveryChallan->transporter ? \App\Models\Master\Transporter::find($deliveryChallan->transporter)?->name : null;

        $doList = $deliveryChallan->delivery_order->pluck('reference_no')->filter()->toArray();

        $itemProducts = [];
        $itemQtys = [];
        $itemRates = [];
        $itemBags = [];
        $itemBagWeights = [];
        $itemTotalBagWeights = [];
        $itemBilledQtys = [];
        $itemBagSizes = [];
        $itemBagTypes = [];
        $itemBrands = [];
        $itemTrucks = [];
        $itemContainers = [];
        $itemTickets = [];
        $totalQty = 0;
        $totalBilledQty = 0;
        $totalBags = 0;

        foreach ($deliveryChallan->delivery_challan_data as $row) {
            $prodName = $row->product?->name ?? ($row->item_id ? \App\Models\Product::find($row->item_id)?->name : '-');
            $itemProducts[] = $prodName;
            $itemQtys[] = (float) $row->qty;
            $itemRates[] = (float) $row->rate;
            $itemBags[] = (int) $row->no_of_bags;
            $itemBagWeights[] = (float) $row->bag_weight;
            $itemTotalBagWeights[] = (float) $row->total_bag_weight;
            $itemBilledQtys[] = (float) $row->billed_qty;
            $itemBagSizes[] = $row->bag_size ?? '-';
            
            $bagTypeName = $row->bag_type ? (\App\Models\BagType::find($row->bag_type)?->name ?? $row->bag_type) : '-';
            $itemBagTypes[] = $bagTypeName;

            $brandName = $row->brand_id ? (getBrandById($row->brand_id)?->name ?? $row->brand_id) : '-';
            $itemBrands[] = $brandName;

            $itemTrucks[] = $row->truck_no ?? '-';
            $itemContainers[] = $row->container_number ?? ($row->loadingProgramItem?->container_number ?? '-');

            $ticketNo = $row->loadingProgramItem?->transaction_number ?? ($row->ticket_id ? \App\Models\Sales\LoadingProgramItem::find($row->ticket_id)?->transaction_number : '-');
            $itemTickets[] = $ticketNo ?? '-';

            $totalQty += (float) $row->qty;
            $totalBilledQty += (float) $row->billed_qty;
            $totalBags += (int) $row->no_of_bags;
        }

        return [
            'dc_no'                  => $deliveryChallan->dc_no,
            'reference_number'       => $deliveryChallan->reference_number,
            'dispatch_date'          => $deliveryChallan->dispatch_date ? Carbon::parse($deliveryChallan->dispatch_date)->format('Y-m-d') : null,
            'customer'               => $deliveryChallan->customer?->name,
            'delivery_orders'        => !empty($doList) ? implode(', ', $doList) : null,
            'sauda_type'             => $deliveryChallan->sauda_type,
            'is_bardana'             => $deliveryChallan->is_bardana ? 'Yes' : 'No',
            'location'               => $companyLoc,
            'factory'                => !empty($factoryNames) ? implode(', ', $factoryNames) : null,
            'section'                => !empty($sectionNames) ? implode(', ', $sectionNames) : null,
            'labour'                 => $labourVendor,
            'labour_status'          => $deliveryChallan->labour_status,
            'labour_rate'            => (float) ($deliveryChallan->labour_rate ?? 0),
            'labour_amount'          => (float) ($deliveryChallan->labour_amount ?? 0),
            'transporter'            => $transporterName,
            'transporter_amount'     => (float) ($deliveryChallan->transporter_amount ?? 0),
            'inhouse_weighbridge'    => $deliveryChallan->getAttribute('inhouse-weighbridge'),
            'weighbridge_amount'     => (float) ($deliveryChallan->getAttribute('weighbridge-amount') ?? 0),
            'am_approval_status'     => $deliveryChallan->am_approval_status,
            'remarks'                => $deliveryChallan->remarks,
            'items_product'          => !empty($itemProducts) ? implode(', ', $itemProducts) : null,
            'items_qty'              => !empty($itemQtys) ? implode(', ', $itemQtys) : null,
            'items_rate'             => !empty($itemRates) ? implode(', ', $itemRates) : null,
            'items_bags'             => !empty($itemBags) ? implode(', ', $itemBags) : null,
            'items_bag_weight'       => !empty($itemBagWeights) ? implode(', ', $itemBagWeights) : null,
            'items_total_bag_weight' => !empty($itemTotalBagWeights) ? implode(', ', $itemTotalBagWeights) : null,
            'items_billed_qty'       => !empty($itemBilledQtys) ? implode(', ', $itemBilledQtys) : null,
            'items_bag_size'         => !empty($itemBagSizes) ? implode(', ', $itemBagSizes) : null,
            'items_bag_type'         => !empty($itemBagTypes) ? implode(', ', $itemBagTypes) : null,
            'items_brand'            => !empty($itemBrands) ? implode(', ', $itemBrands) : null,
            'items_truck_no'         => !empty($itemTrucks) ? implode(', ', $itemTrucks) : null,
            'items_container'        => !empty($itemContainers) ? implode(', ', $itemContainers) : null,
            'items_ticket'           => !empty($itemTickets) ? implode(', ', $itemTickets) : null,
            'total_qty'              => (float) $totalQty,
            'total_billed_qty'       => (float) $totalBilledQty,
            'total_bags'             => (int) $totalBags,
            'items_count'            => $deliveryChallan->delivery_challan_data->count(),
        ];
    }

    /**
     * Get audit logs for a delivery challan.
     */
    public function getAuditLogs(Request $request, $id)
    {
        $deliveryChallan = DeliveryChallan::with(['customer', 'delivery_order'])->findOrFail($id);

        $logs = AuditLog::with('user')
            ->where(function ($q) use ($deliveryChallan) {
                $q->where(function ($sq) use ($deliveryChallan) {
                    $sq->whereIn('model_type', [DeliveryChallan::class, 'DeliveryChallan', 'App\Models\Sales\DeliveryChallan'])
                        ->where('model_id', $deliveryChallan->id);
                })
                ->orWhere('old_values', 'like', '%"delivery_challan_id":' . $deliveryChallan->id . '%')
                ->orWhere('old_values', 'like', '%"delivery_challan_id":"' . $deliveryChallan->id . '"%')
                ->orWhere('new_values', 'like', '%"delivery_challan_id":' . $deliveryChallan->id . '%')
                ->orWhere('new_values', 'like', '%"delivery_challan_id":"' . $deliveryChallan->id . '"%');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $hasCreateLog = $logs->contains(function ($l) {
            return str_contains($l->action, 'create') || str_contains($l->action, 'store');
        });

        if (!$hasCreateLog) {
            $virtualValues = $this->buildDeliveryChallanAuditValues($deliveryChallan);
            $virtualCreateLog = new AuditLog([
                'action' => 'delivery_challan_created',
                'description' => "Delivery Challan #{$deliveryChallan->dc_no} created.",
                'user_id' => $deliveryChallan->created_by_id,
                'created_at' => $deliveryChallan->created_at,
                'old_values' => null,
                'new_values' => $virtualValues,
            ]);
            $creator = User::find($deliveryChallan->created_by_id);
            $virtualCreateLog->setRelation('user', $creator);
            $logs->push($virtualCreateLog);
        }

        $fieldLabels = [
            'dc_no'                  => 'DC No',
            'reference_number'       => 'Reference No',
            'dispatch_date'          => 'Dispatch Date',
            'customer'               => 'Customer',
            'customer_id'            => 'Customer',
            'delivery_orders'        => 'Linked Delivery Orders',
            'sauda_type'             => 'Sauda Type',
            'is_bardana'             => 'Bardana Deduction Applied',
            'location'               => 'Company Location',
            'location_id'            => 'Company Location',
            'factory'                => 'Factory / Arrival Location',
            'arrival_id'             => 'Factory / Arrival Location',
            'section'                => 'Section / Storage Location',
            'section_id'             => 'Section / Storage Location',
            'labour'                 => 'Labour Vendor',
            'labour_status'          => 'Labour Status',
            'labour_rate'            => 'Labour Rate',
            'labour_amount'          => 'Labour Amount',
            'transporter'            => 'Transporter',
            'transporter_id'         => 'Transporter',
            'transporter_amount'     => 'Transporter Amount',
            'inhouse_weighbridge'    => 'Weighbridge',
            'inhouse-weighbridge'    => 'Weighbridge',
            'weighbridge_amount'     => 'Weighbridge Amount',
            'weighbridge-amount'     => 'Weighbridge Amount',
            'am_approval_status'     => 'Approval Status',
            'remarks'                => 'Remarks',
            'items_product'          => 'Products',
            'items_qty'              => 'Quantities (Kgs)',
            'items_rate'             => 'Rates',
            'items_bags'             => 'No of Bags',
            'items_bag_weight'       => 'Bag Weights',
            'items_total_bag_weight' => 'Total Bag Weights',
            'items_billed_qty'       => 'Billed Quantities (Kgs)',
            'items_bag_size'         => 'Bag Sizes',
            'items_bag_type'         => 'Bag Types',
            'items_brand'            => 'Brands',
            'items_truck_no'         => 'Truck Numbers',
            'items_container'        => 'Container Numbers',
            'items_ticket'           => 'Tickets',
            'total_qty'              => 'Total Qty (Kgs)',
            'total_billed_qty'       => 'Total Billed Qty (Kgs)',
            'total_bags'             => 'Total Bags',
            'items_count'            => 'Items Count',
        ];

        $relationCache = [];
        $resolveRelationValue = function ($key, $val) use (&$relationCache) {
            if ($val === null || $val === '') {
                return null;
            }
            if (is_array($val)) {
                return json_encode($val);
            }

            if (in_array($key, ['customer_id', 'customer']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
                $cacheKey = "customer_{$val}";
                if (isset($relationCache[$cacheKey])) return $relationCache[$cacheKey];
                $c = Customer::find($val);
                $res = $c ? $c->name : $val;
                $relationCache[$cacheKey] = $res;
                return $res;
            }

            if (in_array($key, ['transporter', 'transporter_id']) && (is_numeric($val) || (is_string($val) && ctype_digit((string) $val)))) {
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

            if ($key === 'is_bardana') {
                return ($val == 1 || $val === '1' || $val === 'Yes' || $val === true) ? 'Yes' : 'No';
            }

            if (in_array($key, ['labour_amount', 'transporter_amount', 'weighbridge_amount']) && is_numeric($val)) {
                return 'Rs. ' . number_format((float) $val, 2);
            }

            if (in_array($key, ['total_qty', 'total_billed_qty']) && is_numeric($val)) {
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
                    // If single item and total_bags is identical to items_bags, omit duplicate total_bags row
                    if ($key === 'total_bags' && isset($new['items_bags']) && (string) $new['items_bags'] === (string) $newVal && (!isset($old['items_bags']) || (string) $old['items_bags'] === (string) $oldVal)) {
                        continue;
                    }
                    // If single item and total_billed_qty is identical to items_billed_qty, omit duplicate
                    if ($key === 'total_billed_qty' && isset($new['items_billed_qty']) && (float) $new['items_billed_qty'] === (float) $newVal && (!isset($old['items_billed_qty']) || (float) $old['items_billed_qty'] === (float) $oldVal)) {
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
        $title = "Audit Logs: Delivery Challan #" . ($deliveryChallan->dc_no ?? $deliveryChallan->reference_number);

        return view('management.sales.delivery-challan.partials.audit_logs_content', compact('deliveryChallan', 'logs', 'title'));
    }
}
