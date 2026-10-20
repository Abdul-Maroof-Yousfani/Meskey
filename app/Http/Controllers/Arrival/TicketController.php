<?php

namespace App\Http\Controllers\Arrival;

use App\Helpers\TruckNumberValidator;
use App\Http\Controllers\Controller;
use App\Http\Requests\Arrival\ArrivalTicketRequest;
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Arrival\ArrivalTicket;
use App\Models\ArrivalPurchaseOrder;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Miller;
use App\Models\Master\ProductSlab;
use App\Models\Master\{Station, ArrivalSubLocation};
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\SaudaType;
use App\Models\User;
use App\Models\{BagType, BagCondition, BagPacking};
use App\Models\Master\ArrivalLocation;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    function __construct()
    {
        $this->middleware('check.company:arrival-ticket', ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('management.arrival.ticket.index');
    }

    /**
     * Get list of categories.
     */
    public function getList(Request $request)
    {
        $tickets = ArrivalTicket::with(['product', 'miller', 'station'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $searchTerm = '%' . $request->search . '%';
                $q->where(function ($sq) use ($searchTerm) {
                    $sq->where('unique_no', 'like', $searchTerm)
                        ->orWhere('truck_no', 'like', $searchTerm)
                        ->orWhere('bilty_no', 'like', $searchTerm)
                        ->orWhere('station_name', 'like', $searchTerm)
                        ->orWhereHas('station', function ($stationQ) use ($searchTerm) {
                            $stationQ->where('name', 'like', $searchTerm);
                        });
                });
            })
            ->when($request->filled('unique_no_filter'), function ($q) use ($request) {
                $q->where('unique_no', 'like', '%' . $request->unique_no_filter . '%');
            })
            ->when($request->filled('product_id'), function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            })
            ->when($request->filled('acc_of'), function ($q) use ($request) {
                $q->where('accounts_of_name', 'like', '%' . $request->acc_of . '%');
            })
            ->when($request->filled('miller_id'), function ($q) use ($request) {
                $q->where('miller_id', $request->miller_id);
            })
            ->when($request->filled('truck_no'), function ($q) use ($request) {
                $q->where('truck_no', 'like', '%' . $request->truck_no . '%');
            })
            ->when($request->filled('bilty_no'), function ($q) use ($request) {
                $q->where('bilty_no', 'like', '%' . $request->bilty_no . '%');
            })
            ->when($request->filled('station_id'), function ($q) use ($request) {
                $q->where(function ($sq) use ($request) {
                    $sq->where('station_id', $request->station_id)
                        ->orWhere('station_name', 'like', '%' . $request->station_id . '%')
                        ->orWhereHas('station', function ($stationQ) use ($request) {
                            $stationQ->where('name', 'like', '%' . $request->station_id . '%');
                        });
                });
            })
            ->when($request->filled('station'), function ($q) use ($request) {
                $q->where(function ($sq) use ($request) {
                    $sq->where('station_id', $request->station)
                        ->orWhere('station_name', 'like', '%' . $request->station . '%')
                        ->orWhereHas('station', function ($stationQ) use ($request) {
                            $stationQ->where('name', 'like', '%' . $request->station . '%');
                        });
                });
            })
            ->when($request->filled('qc_status'), function ($q) use ($request) {
                $q->where('first_qc_status', $request->qc_status);
            })
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->to_date);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('location_id', $request->company_location_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');

                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->whereIn('location_id', getUserCurrentCompanyLocations())
            ->latest()
            ->paginate($request->get('per_page', 25));

        return view('management.arrival.ticket.getList', compact('tickets'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';
        $userLocation = $authUser->companyLocation ?? null;
        $authUserCompany = $request->company_id;

        // $companyLocations = $isSuperAdmin
        //     ? CompanyLocation::all()
        //     : collect([$userLocation])->filter();

        $companyLocations = CompanyLocation::whereIn('id', getUserCurrentCompanyLocations())->get();

        $arrivalPurchaseOrders = ArrivalPurchaseOrder::with(['product', 'supplier', 'saudaType'])
            ->where('purchase_type', 'regular')
            ->where("am_approval_status", "approved")
            ->when(!$isSuperAdmin, function ($q) use ($userLocation) {
                $q->where('company_location_id', $userLocation?->id);
            })
            ->whereIn('company_location_id', getUserCurrentCompanyLocations())
            ->orderByDesc('id')
            ->get();

        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        $accountsOf = User::role('Purchaser')
            ->where('parent_user_id', null)
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        return view('management.arrival.ticket.create', [
            'accountsOf' => $accountsOf,
            'arrivalPurchaseOrders' => $arrivalPurchaseOrders,
            'suppliers' => $suppliers,
            'products' => $products,
            'companyLocations' => $companyLocations,
            'isSuperAdmin' => $isSuperAdmin
        ]);
    }

    public function getTicketNumber($locationId)
    {
        $location = CompanyLocation::find($locationId);
        $code = $location->code ?? 'KHI';
        $ticketNo = generateTicketNoWithDateFormat('arrival_tickets', $code);

        return response()->json([
            'ticket_no' => $ticketNo
        ]);
    }

    public function getContractsByLocation($locationId)
    {
        // Contracts filtering: for gate_buying, skip those with tickets; for regular, include all
        $contracts = ArrivalPurchaseOrder::with(['product', 'supplier', 'saudaType'])
            ->where('company_location_id', $locationId)
            ->where("am_approval_status", "approved")
            ->where("status", '!=', "completed")
            ->where(function ($q) {
                $q->where('purchase_type', 'regular')
                    ->orWhere(function ($q2) {
                        $q2->where('purchase_type', 'gate_buying')
                            ->whereDoesntHave('arrivalTickets');
                    });
            })
            ->get();


        return response()->json([
            'contracts' => $contracts
        ]);
    }

    public function getSuppliersByLocation($locationId)
    {
        $suppliers = Supplier::whereJsonContains('company_location_ids', $locationId)
            ->where('status', 'active')
            ->get();

        $suppliers = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->company_name
            ];
        });

        return response()->json([
            'suppliers' => $suppliers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArrivalTicketRequest $request)
    {
        $requestData = $request->validated();

        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        if (!$isSuperAdmin) {
            $requestData['location_id'] = $authUser->company_location_id ?: $request->company_location_id;
        } else {
            $requestData['location_id'] = $request->company_location_id;
        }

        $locationCode = CompanyLocation::find($request->company_location_id)->code ?? 'KHI';
        $uniqueNo = generateTicketNoWithDateFormat('arrival_tickets', $locationCode);

        $requestData['unique_no'] = $uniqueNo;

        if (!empty($requestData['accounts_of'])) {
            $supplier = Supplier::where('company_name', $requestData['accounts_of'])->first();
            $requestData['accounts_of_id'] = $supplier ? $supplier->id : null;
            $requestData['accounts_of_name'] = $requestData['accounts_of'];
        }

        // if (!empty($requestData['station'])) {
        //     $station = Station::where('name', $requestData['station'])->first();
        //     $requestData['station_id'] = $station ? $station->id : null;
        //     $requestData['station_name'] = $requestData['station'];
        // }

        if (!empty($requestData['station'])) {
            $station = Station::firstOrCreate(
                [
                    'name' => $requestData['station'],
                    'company_id' => $requestData['company_id'] ?? null,
                ]
            );

            $requestData['station_id'] = $station->id;
            $requestData['station_name'] = $station->name;
        }


        if (!empty($requestData['broker_name'])) {
            $broker = Supplier::where('company_name', $requestData['broker_name'])->first();
            $requestData['broker_id'] = $broker ? $broker->id : null;
        }

        if (!empty($requestData['miller_name'])) {
            $miller = Miller::where('name', $requestData['miller_name'])->first();
            if (!$miller) {
                $miller = Miller::create(['name' => $requestData['miller_name']]);
            }
            $requestData['miller_id'] = $miller->id;
        }

        $requestData['first_qc_status'] = 'pending';
        $requestData['closing_trucks_qty'] = 1;
        $requestData['truck_type_id'] = $requestData['arrival_truck_type_id'] ?? null;
        $requestData['sauda_type_id'] = $request->sauda_type_id ?? null;

        $isLock = $request->boolean('is_arrival_lock');
        if ($isLock) {
            $requestData['is_arrival_lock'] = 1;
            $requestData['locked_at'] = now();
            $requestData['unlocked_at'] = null;
        } else {
            $requestData['is_arrival_lock'] = null;
            $requestData['locked_at'] = null;
            $requestData['unlocked_at'] = null;
        }

        $arrivalTicket = ArrivalTicket::create($requestData);

        AuditLogService::log(
            $arrivalTicket,
            $isLock ? 'arrival_ticket_created_locked' : 'arrival_ticket_created',
            "Arrival Ticket #{$arrivalTicket->unique_no} created" . ($isLock ? ' with arrival lock.' : '.'),
            null,
            $arrivalTicket->toArray()
        );

        return response()->json([
            'success' => 'Arrival Ticket created successfully.',
            'data' => $arrivalTicket
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';
        $userLocation = $authUser->companyLocation ?? null;
        $authUserCompany = $request->company_id;

        $arrivalTicket = ArrivalTicket::with([
            'location',
            'product',
            'miller',
            'broker',
            'decisionBy',
            'accountsOf',
            'station',
            'truckType',
            'purchaseOrder.saudaType',
            'saudaType'
        ])->findOrFail($id);

        $companyLocations = CompanyLocation::whereIn('id', getUserCurrentCompanyLocations())->get();
        if ($arrivalTicket->location_id && !$companyLocations->contains('id', $arrivalTicket->location_id)) {
            $ticketLocation = CompanyLocation::find($arrivalTicket->location_id);
            if ($ticketLocation) {
                $companyLocations->push($ticketLocation);
            }
        }

        $targetLocationId = $arrivalTicket->location_id ?? $userLocation?->id;
        $arrivalPurchaseOrders = ArrivalPurchaseOrder::with(['product', 'supplier', 'saudaType'])
            ->where("am_approval_status", "approved")
            ->where(function ($q) use ($targetLocationId, $arrivalTicket) {
                if ($targetLocationId) {
                    $q->where('company_location_id', $targetLocationId);
                }
                if ($arrivalTicket->arrival_purchase_order_id) {
                    $q->orWhere('id', $arrivalTicket->arrival_purchase_order_id);
                }
            })
            ->orderByDesc('id')
            ->get();

        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        $accountsOf = User::role('Purchaser')
            ->where('parent_user_id', null)
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        return view('management.arrival.ticket.edit', [
            'arrivalTicket' => $arrivalTicket,
            'accountsOf' => $accountsOf,
            'arrivalPurchaseOrders' => $arrivalPurchaseOrders,
            'suppliers' => $suppliers,
            'products' => $products,
            'companyLocations' => $companyLocations,
            'isSuperAdmin' => $isSuperAdmin
        ]);
    }

    public function show(Request $request, $id)
    {
        $authUserCompany = $request->company_id;
        $source = $request->source ?? false;

        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        $arrivalTicket = ArrivalTicket::findOrFail($id);

        $latestRequestIds = ArrivalSamplingRequest::selectRaw('MAX(id) as id')
            ->where('is_done', 'yes')
            ->groupBy('arrival_ticket_id')
            ->pluck('id');

        $arrivalSamplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $arrivalTicket->id)
            ->whereIn('id', $latestRequestIds)
            ->where(function ($q) {
                $q->where('approved_status', '!=', 'pending')
                    ->orWhere(function ($q) {
                        $q->where('decision_making', 1);
                    });
            })
            ->latest()
            ->first();

        $slabs = collect();
        $productSlabCalculations = null;
        $results = collect();
        $Compulsuryresults = collect();
        $arrivalPurchaseOrders = collect();
        $sampleTakenByUsers = collect();
        $saudaTypes = collect();
        $allInitialRequests = collect();
        $allInnerRequests = collect();
        $initialRequestsData = [];
        $innerRequestsData = [];

        if ($arrivalSamplingRequest) {
            $slabs = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)
                ->get()
                ->groupBy('product_slab_type_id')
                ->map(function ($group) {
                    return $group->sortBy('from')->first();
                });

            if ($arrivalSamplingRequest->arrival_product_id) {
                $productSlabCalculations = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)->get();
            }

            $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $arrivalSamplingRequest->id)->get();
            foreach ($results as $result) {
                $matchingSlabs = [];
                if ($productSlabCalculations) {
                    $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                        ->values()
                        ->all();
                }
                $result->matching_slabs = $matchingSlabs;
            }

            $results->map(function ($item) use ($slabs) {
                $slab = $slabs->get($item->product_slab_type_id);
                $item->max_range = $slab ? $slab->to : null;
                return $item;
            });

            $Compulsuryresults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $arrivalSamplingRequest->id)->get();

            $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('product_id', $arrivalSamplingRequest->arrivalTicket->product_id)->get();
            $sampleTakenByUsers = User::all();
            $authUserCompany = $request->company_id;
            $saudaTypes = SaudaType::all();

            $allInitialRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')
                ->where('arrival_ticket_id', $arrivalTicket->id)
                ->where('approved_status', '!=', 'pending')
                ->orderBy('created_at', 'asc')
                ->get();

            $allInnerRequests = ArrivalSamplingRequest::where('sampling_type', 'inner')
                ->where('arrival_ticket_id', $arrivalTicket->id)
                ->where('approved_status', '!=', 'pending')
                ->where('id', '!=', $arrivalSamplingRequest->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($allInitialRequests as $initialReq) {
                $initialResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialReq->id)->get();
                $initialCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialReq->id)->get();

                $initialResults->map(function ($item) use ($slabs) {
                    $slab = $slabs->get($item->product_slab_type_id);
                    $item->max_range = $slab ? $slab->to : null;
                    return $item;
                });

                $initialRequestsData[] = [
                    'request' => $initialReq,
                    'results' => $initialResults,
                    'compulsuryResults' => $initialCompulsuryResults
                ];
            }

            foreach ($allInnerRequests as $innerReq) {
                $innerResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $innerReq->id)->get();
                $innerCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $innerReq->id)->get();

                $innerResults->map(function ($item) use ($slabs) {
                    $slab = $slabs->get($item->product_slab_type_id);
                    $item->max_range = $slab ? $slab->to : null;
                    return $item;
                });

                $innerRequestsData[] = [
                    'request' => $innerReq,
                    'results' => $innerResults,
                    'compulsuryResults' => $innerCompulsuryResults
                ];
            }
        }

        $layout = !isset($source) || $source != 'contract' ? 'management.layouts.master' : 'management.layouts.master_blank';

        return view('management.arrival.ticket.show', compact(
            'arrivalTicket',
            'accountsOf',
            'source',
            'layout',
            'innerRequestsData',
            'arrivalSamplingRequest',
            'initialRequestsData',
            'results',
            'Compulsuryresults',
            'accountsOf',
            'accountsOf',
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArrivalTicketRequest $request, $id)
    {
        $arrivalTicket = ArrivalTicket::findOrFail($id);

        $oldAttributes = $arrivalTicket->getAttributes();
        $wasLocked = (bool) $arrivalTicket->is_arrival_lock;

        $requestData = $request->validated();

        $authUser = auth()->user();
        $isSuperAdmin = $authUser->user_type === 'super-admin';

        if ($request->filled('company_location_id')) {
            $requestData['location_id'] = $request->company_location_id;
        }

        // Do not overwrite unique_no on update
        unset($requestData['unique_no']);

        if (!empty($requestData['accounts_of'])) {
            $supplier = Supplier::where('company_name', $requestData['accounts_of'])->first();
            $requestData['accounts_of_id'] = $supplier ? $supplier->id : null;
            $requestData['accounts_of_name'] = $requestData['accounts_of'];
        } else {
            $requestData['accounts_of_id'] = null;
            $requestData['accounts_of_name'] = null;
        }

        if (!empty($requestData['station'])) {
            $station = Station::firstOrCreate(
                [
                    'name' => $requestData['station'],
                    'company_id' => $requestData['company_id'] ?? null,
                ]
            );

            $requestData['station_id'] = $station->id;
            $requestData['station_name'] = $station->name;
        } else {
            $requestData['station_id'] = null;
            $requestData['station_name'] = null;
        }

        if (!empty($requestData['broker_name'])) {
            $broker = Supplier::where('company_name', $requestData['broker_name'])->first();
            $requestData['broker_id'] = $broker ? $broker->id : null;
        } else {
            $requestData['broker_id'] = null;
        }

        if (!empty($requestData['miller_name'])) {
            $miller = Miller::where('name', $requestData['miller_name'])->first();
            if (!$miller) {
                $miller = Miller::create(['name' => $requestData['miller_name']]);
            }
            $requestData['miller_id'] = $miller->id;
        } else {
            $requestData['miller_id'] = null;
        }

        $requestData['truck_type_id'] = $requestData['arrival_truck_type_id'] ?? null;

        if (!empty($requestData['arrival_purchase_order_id'])) {
            if ($request->filled('sauda_type_id')) {
                $requestData['sauda_type_id'] = $request->sauda_type_id;
            } else {
                $po = ArrivalPurchaseOrder::find($requestData['arrival_purchase_order_id']);
                $requestData['sauda_type_id'] = $po?->sauda_type_id;
            }
        } else {
            $requestData['sauda_type_id'] = null;
        }

        $requestData['loading_date'] = !empty($requestData['loading_date']) ? $requestData['loading_date'] : null;

        $isLock = $request->boolean('is_arrival_lock');
        $isUnlocked = false;
        if ($arrivalTicket->is_arrival_lock && !$isLock) {
            // Unlocked on edit: record unlocked_at, clear lock flags
            $requestData['is_arrival_lock'] = null;
            $requestData['locked_at'] = null;
            $requestData['unlocked_at'] = now();
            $isUnlocked = true;
        } elseif ($isLock) {
            $requestData['is_arrival_lock'] = 1;
            $requestData['locked_at'] = $arrivalTicket->locked_at ?? now();
            $requestData['unlocked_at'] = null;
        }

        $arrivalTicket->update($requestData);

        $changes = $arrivalTicket->getChanges();
        unset($changes['updated_at']);

        $changedOld = !empty($changes) ? array_intersect_key($oldAttributes, $changes) : null;
        $changedNew = !empty($changes) ? $changes : null;

        if ($isUnlocked) {
            $action = 'arrival_ticket_unlocked';
            $description = "Arrival Ticket #{$arrivalTicket->unique_no} unlocked and updated.";
        } elseif (!$wasLocked && $isLock) {
            $action = 'arrival_ticket_locked';
            $description = "Arrival Ticket #{$arrivalTicket->unique_no} locked and updated.";
        } else {
            $action = 'arrival_ticket_updated';
            $description = "Arrival Ticket #{$arrivalTicket->unique_no} updated.";
        }

        AuditLogService::log(
            $arrivalTicket,
            $action,
            $description,
            $changedOld,
            $changedNew
        );

        return response()->json([
            'success' => 'Arrival Ticket updated successfully.',
            'data' => $arrivalTicket
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $arrivalTicket): JsonResponse
    {
        AuditLogService::log(
            $arrivalTicket,
            'arrival_ticket_deleted',
            "Arrival Ticket #{$arrivalTicket->unique_no} deleted.",
            $arrivalTicket->toArray(),
            null
        );

        $arrivalTicket->delete();
        return response()->json(['success' => 'Arrival Ticket deleted successfully.'], 200);
    }

    public function confirmBiltyReturn(ArrivalTicket $ticket)
    {
        // dd(request()->all());
        try {
            $oldValues = [
                'bilty_return_confirmation' => $ticket->bilty_return_confirmation,
                'bilty_return_reason' => $ticket->bilty_return_reason,
            ];

            $updateData = [
                'bilty_return_confirmation' => 1,
                'bilty_return_reason' => request('bilty_return_reason'),
                // 'weightslip_attachment' => request('weightslip_attachment'),
                // 'other_attachment' => request('other_attachment')
            ];

            $ticket->update($updateData);

            AuditLogService::log(
                $ticket,
                'arrival_ticket_bilty_return_confirmed',
                "Bilty return confirmed for Arrival Ticket #{$ticket->unique_no}. Reason: " . request('bilty_return_reason'),
                $oldValues,
                $updateData
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getBiltyAttachments($id)
    {
        $ticket = ArrivalTicket::findOrFail($id);
        return view('management.arrival.ticket.bilty-attachments', compact('ticket'));
    }



    public function arrivalRevert(Request $request, $id)
    {
        $authUserCompany = $request->company_id;
        $source = $request->source ?? false;



        $bagTypes = BagType::all();
        $bagConditions = BagCondition::all();
        $bagPackings = BagPacking::all();
        $arrivalSubLocations = ArrivalSubLocation::where('status', 'Active')->get();

        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        $arrivalTicket = ArrivalTicket::findOrFail($id);


        $ArrivalLocations = ArrivalLocation::where('status', 'active')
            ->where('company_location_id', $arrivalTicket->location_id)

            ->get();


        $latestRequestIds = ArrivalSamplingRequest::selectRaw('MAX(id) as id')
            ->where('is_done', 'yes')
            ->groupBy('arrival_ticket_id')
            ->pluck('id');

        $arrivalSamplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $arrivalTicket->id)
            ->whereIn('id', $latestRequestIds)
            ->where(function ($q) {
                $q->where('approved_status', '!=', 'pending')
                    ->orWhere(function ($q) {
                        $q->where('decision_making', 1);
                    });
            })
            ->latest()
            ->first();

        $slabs = collect();
        $productSlabCalculations = null;
        $results = collect();
        $Compulsuryresults = collect();
        $arrivalPurchaseOrders = collect();
        $sampleTakenByUsers = collect();
        $saudaTypes = collect();
        $allInitialRequests = collect();
        $allInnerRequests = collect();
        $initialRequestsData = [];
        $innerRequestsData = [];

        if ($arrivalSamplingRequest) {
            $slabs = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)
                ->get()
                ->groupBy('product_slab_type_id')
                ->map(function ($group) {
                    return $group->sortBy('from')->first();
                });

            if ($arrivalSamplingRequest->arrival_product_id) {
                $productSlabCalculations = ProductSlab::where('product_id', $arrivalSamplingRequest->arrival_product_id)->get();
            }

            $results = ArrivalSamplingResult::where('arrival_sampling_request_id', $arrivalSamplingRequest->id)->get();
            foreach ($results as $result) {
                $matchingSlabs = [];
                if ($productSlabCalculations) {
                    $matchingSlabs = $productSlabCalculations->where('product_slab_type_id', $result->product_slab_type_id)
                        ->values()
                        ->all();
                }
                $result->matching_slabs = $matchingSlabs;
            }

            $results->map(function ($item) use ($slabs) {
                $slab = $slabs->get($item->product_slab_type_id);
                $item->max_range = $slab ? $slab->to : null;
                return $item;
            });

            $Compulsuryresults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $arrivalSamplingRequest->id)->get();

            $arrivalPurchaseOrders = ArrivalPurchaseOrder::where('product_id', $arrivalSamplingRequest->arrivalTicket->product_id)->get();
            $sampleTakenByUsers = User::all();
            $authUserCompany = $request->company_id;
            $saudaTypes = SaudaType::all();

            $allInitialRequests = ArrivalSamplingRequest::where('sampling_type', 'initial')
                ->where('arrival_ticket_id', $arrivalTicket->id)
                ->where('approved_status', '!=', 'pending')
                ->orderBy('created_at', 'asc')
                ->get();

            $allInnerRequests = ArrivalSamplingRequest::where('sampling_type', 'inner')
                ->where('arrival_ticket_id', $arrivalTicket->id)
                ->where('approved_status', '!=', 'pending')
                ->where('id', '!=', $arrivalSamplingRequest->id)
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($allInitialRequests as $initialReq) {
                $initialResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $initialReq->id)->get();
                $initialCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $initialReq->id)->get();

                $initialResults->map(function ($item) use ($slabs) {
                    $slab = $slabs->get($item->product_slab_type_id);
                    $item->max_range = $slab ? $slab->to : null;
                    return $item;
                });

                $initialRequestsData[] = [
                    'request' => $initialReq,
                    'results' => $initialResults,
                    'compulsuryResults' => $initialCompulsuryResults
                ];
            }

            foreach ($allInnerRequests as $innerReq) {
                $innerResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $innerReq->id)->get();
                $innerCompulsuryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $innerReq->id)->get();

                $innerResults->map(function ($item) use ($slabs) {
                    $slab = $slabs->get($item->product_slab_type_id);
                    $item->max_range = $slab ? $slab->to : null;
                    return $item;
                });

                $innerRequestsData[] = [
                    'request' => $innerReq,
                    'results' => $innerResults,
                    'compulsuryResults' => $innerCompulsuryResults
                ];
            }
        }

        $layout = !isset($source) || $source != 'contract' ? 'management.layouts.master' : 'management.layouts.master_blank';

        return view('management.arrival.ticket.arrival-revert', compact(
            'arrivalTicket',
            'accountsOf',
            'source',
            'layout',
            'innerRequestsData',
            'arrivalSamplingRequest',
            'initialRequestsData',
            'results',
            'Compulsuryresults',
            'accountsOf',
            'accountsOf',
            'ArrivalLocations',
            'arrivalSubLocations',
            'bagTypes',
            'bagConditions',
            'bagPackings',
        ));
    }

    /**
     * Get audit logs specifically for ticket revert views (guaranteed to exclude initial creation logs).
     */
    public function getRevertAuditLogs(Request $request, $id)
    {
        return $this->getAuditLogs($request, $id);
    }

    /**
     * Get audit logs for a ticket, optionally filtered by section.
     */
    public function getAuditLogs(Request $request, $id)
    {
        $ticket = ArrivalTicket::withTrashed()->with([
            'unloadingLocation',
            'firstWeighbridge',
            'secondWeighbridge',
            'approvals',
            'freight',
            'arrivalSamplingRequests'
        ])->findOrFail($id);

        $section = $request->get('section', 'ticket_detail');
        $excludeCreate = $request->boolean('exclude_create', false);

        // Always show create logs for sub-sections (location transfer, first/second weighbridge, approvals, freight, all)
        if (in_array($section, ['location_transfer', 'first_weighbridge', 'half_full_approved', 'second_weighbridge', 'freight', 'all'])) {
            $excludeCreate = false;
        }

        $getIdsForModel = function ($modelClass, $foreignKey, $ticketId) {
            if (!class_exists($modelClass)) return [];
            try {
                $query = in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))
                    ? $modelClass::withTrashed()
                    : $modelClass::query();
                return $query->where($foreignKey, $ticketId)->pluck('id')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        };

        $unloadingIds = $getIdsForModel(\App\Models\Arrival\ArrivalLocationTransfer::class, 'arrival_ticket_id', $ticket->id);
        if ($ticket->unloadingLocation?->id && !in_array($ticket->unloadingLocation->id, $unloadingIds)) {
            $unloadingIds[] = $ticket->unloadingLocation->id;
        }

        $firstWbIds = $getIdsForModel(\App\Models\FirstWeighbridge::class, 'arrival_ticket_id', $ticket->id);
        if ($ticket->firstWeighbridge?->id && !in_array($ticket->firstWeighbridge->id, $firstWbIds)) {
            $firstWbIds[] = $ticket->firstWeighbridge->id;
        }

        $secondWbIds = $getIdsForModel(\App\Models\Arrival\SecondWeighbridge::class, 'arrival_ticket_id', $ticket->id);
        if ($ticket->secondWeighbridge?->id && !in_array($ticket->secondWeighbridge->id, $secondWbIds)) {
            $secondWbIds[] = $ticket->secondWeighbridge->id;
        }

        $approvalIds = $getIdsForModel(\App\Models\ArrivalApprove::class, 'arrival_ticket_id', $ticket->id);
        if ($ticket->approvals?->id && !in_array($ticket->approvals->id, $approvalIds)) {
            $approvalIds[] = $ticket->approvals->id;
        }

        $freightIds = $getIdsForModel(\App\Models\Arrival\Freight::class, 'arrival_ticket_id', $ticket->id);
        if ($ticket->freight?->id && !in_array($ticket->freight->id, $freightIds)) {
            $freightIds[] = $ticket->freight->id;
        }
        $samplingIds = $ticket->arrivalSamplingRequests->pluck('id')->toArray();

        $query = \App\Models\AuditLog::with('user');

        if ($section === 'all') {
            $query->where(function ($q) use ($ticket, $unloadingIds, $firstWbIds, $secondWbIds, $approvalIds, $freightIds, $samplingIds) {
                $q->where(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                        ->where('model_id', $ticket->id);
                });
                if (!empty($unloadingIds)) {
                    $q->orWhere(function ($sq) use ($unloadingIds) {
                        $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalLocationTransfer::class, 'ArrivalLocationTransfer'])
                            ->whereIn('model_id', $unloadingIds);
                    });
                }
                if (!empty($firstWbIds)) {
                    $q->orWhere(function ($sq) use ($firstWbIds) {
                        $sq->whereIn('model_type', [\App\Models\FirstWeighbridge::class, 'FirstWeighbridge', \App\Models\Arrival\FirstWeighbridge::class, 'App\Models\Arrival\FirstWeighbridge'])
                            ->whereIn('model_id', $firstWbIds);
                    });
                }
                if (!empty($secondWbIds)) {
                    $q->orWhere(function ($sq) use ($secondWbIds) {
                        $sq->whereIn('model_type', [\App\Models\Arrival\SecondWeighbridge::class, 'App\Models\Arrival\SecondWeighbridge', \App\Models\SecondWeighbridge::class, 'SecondWeighbridge', \App\Models\ArrivalWeighbridge::class, 'ArrivalWeighbridge'])
                            ->whereIn('model_id', $secondWbIds);
                    });
                }
                if (!empty($approvalIds)) {
                    $q->orWhere(function ($sq) use ($approvalIds) {
                        $sq->whereIn('model_type', [\App\Models\ArrivalApprove::class, 'ArrivalApprove'])
                            ->whereIn('model_id', $approvalIds);
                    });
                }
                if (!empty($freightIds)) {
                    $q->orWhere(function ($sq) use ($freightIds) {
                        $sq->whereIn('model_type', [\App\Models\Arrival\Freight::class, 'Freight'])
                            ->whereIn('model_id', $freightIds);
                    });
                }
                if (!empty($samplingIds)) {
                    $q->orWhere(function ($sq) use ($samplingIds) {
                        $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalSamplingRequest::class, 'ArrivalSamplingRequest'])
                            ->whereIn('model_id', $samplingIds);
                    });
                }
                $q->orWhere('old_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                    ->orWhere('old_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%')
                    ->orWhere('new_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                    ->orWhere('new_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%');
            });
        } elseif ($section === 'location_transfer') {
            $query->where(function ($q) use ($ticket, $unloadingIds) {
                if (!empty($unloadingIds)) {
                    $q->where(function ($sq) use ($unloadingIds) {
                        $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalLocationTransfer::class, 'ArrivalLocationTransfer'])
                            ->whereIn('model_id', $unloadingIds);
                    });
                }
                $q->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                        ->where('model_id', $ticket->id)
                        ->where(function ($ssq) {
                            $ssq->where('action', 'location_transfer_update')
                                ->orWhere('action', 'location_transfer_revert')
                                ->orWhere('action', 'like', '%location_transfer%')
                                ->orWhere('description', 'like', '%location transfer%')
                                ->orWhere('description', 'like', '%unloading location%');
                        })
                        ->whereNotIn('action', [
                            'first_weighbridge_revert',
                            'second_weighbridge_revert',
                            'half_full_approval_revert',
                            'freight_revert',
                        ]);
                })->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalLocationTransfer::class, 'ArrivalLocationTransfer'])
                        ->where(function ($ssq) use ($ticket) {
                            $ssq->where('old_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('old_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%');
                        });
                });
            });
        } elseif ($section === 'first_weighbridge') {
            $query->where(function ($q) use ($ticket, $firstWbIds) {
                if (!empty($firstWbIds)) {
                    $q->where(function ($sq) use ($firstWbIds) {
                        $sq->whereIn('model_type', [\App\Models\FirstWeighbridge::class, 'FirstWeighbridge'])
                            ->whereIn('model_id', $firstWbIds);
                    });
                }
                $q->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                        ->where('model_id', $ticket->id)
                        ->where(function ($ssq) {
                            $ssq->where('action', 'first_weighbridge_update')
                                ->orWhere('action', 'first_weighbridge_revert')
                                ->orWhere('action', 'like', '%first_weighbridge%')
                                ->orWhere('description', 'like', '%first weighbridge%');
                        })
                        ->whereNotIn('action', [
                            'location_transfer_revert',
                            'location_transfer_update',
                            'second_weighbridge_revert',
                            'half_full_approval_revert',
                            'freight_revert',
                        ]);
                })->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\FirstWeighbridge::class, 'FirstWeighbridge'])
                        ->where(function ($ssq) use ($ticket) {
                            $ssq->where('old_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('old_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%');
                        });
                });
            });
        } elseif ($section === 'second_weighbridge') {
            $query->where(function ($q) use ($ticket, $secondWbIds) {
                if (!empty($secondWbIds)) {
                    $q->where(function ($sq) use ($secondWbIds) {
                        $sq->whereIn('model_type', [\App\Models\Arrival\SecondWeighbridge::class, 'App\Models\Arrival\SecondWeighbridge', \App\Models\SecondWeighbridge::class, 'SecondWeighbridge', \App\Models\ArrivalWeighbridge::class, 'ArrivalWeighbridge'])
                            ->whereIn('model_id', $secondWbIds);
                    });
                }
                $q->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                        ->where('model_id', $ticket->id)
                        ->where(function ($ssq) {
                            $ssq->where('action', 'second_weighbridge_update')
                                ->orWhere('action', 'second_weighbridge_revert')
                                ->orWhere('action', 'like', '%second_weighbridge%')
                                ->orWhere('description', 'like', '%second weighbridge%');
                        })
                        ->whereNotIn('action', [
                            'location_transfer_revert',
                            'first_weighbridge_revert',
                            'half_full_approval_revert',
                            'freight_revert',
                        ]);
                })->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\SecondWeighbridge::class, 'App\Models\Arrival\SecondWeighbridge', \App\Models\SecondWeighbridge::class, 'SecondWeighbridge', \App\Models\ArrivalWeighbridge::class, 'ArrivalWeighbridge'])
                        ->where(function ($ssq) use ($ticket) {
                            $ssq->where('old_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('old_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%');
                        });
                });
            });
        } elseif ($section === 'half_full_approved') {
            $query->where(function ($q) use ($ticket, $approvalIds) {
                if (!empty($approvalIds)) {
                    $q->where(function ($sq) use ($approvalIds) {
                        $sq->whereIn('model_type', [\App\Models\ArrivalApprove::class, 'ArrivalApprove'])
                            ->whereIn('model_id', $approvalIds);
                    });
                }
                $q->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                        ->where('model_id', $ticket->id)
                        ->where(function ($ssq) {
                            $ssq->where('action', 'half_full_approval_revert')
                                ->orWhere('action', 'like', '%half_full%')
                                ->orWhere('action', 'like', '%approval%')
                                ->orWhere('description', 'like', '%approval%');
                        })
                        ->whereNotIn('action', [
                            'location_transfer_revert',
                            'first_weighbridge_revert',
                            'second_weighbridge_revert',
                            'freight_revert',
                        ]);
                })->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\ArrivalApprove::class, 'ArrivalApprove'])
                        ->where(function ($ssq) use ($ticket) {
                            $ssq->where('old_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('old_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%');
                        });
                });
            });
        } elseif ($section === 'freight') {
            $query->where(function ($q) use ($ticket, $freightIds) {
                if (!empty($freightIds)) {
                    $q->where(function ($sq) use ($freightIds) {
                        $sq->whereIn('model_type', [\App\Models\Arrival\Freight::class, 'Freight'])
                            ->whereIn('model_id', $freightIds);
                    });
                }
                $q->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                        ->where('model_id', $ticket->id)
                        ->where(function ($ssq) {
                            $ssq->where('action', 'freight_revert')
                                ->orWhere('action', 'like', '%freight%')
                                ->orWhere('description', 'like', '%freight%');
                        })
                        ->whereNotIn('action', [
                            'location_transfer_revert',
                            'first_weighbridge_revert',
                            'second_weighbridge_revert',
                            'half_full_approval_revert',
                        ]);
                })->orWhere(function ($sq) use ($ticket) {
                    $sq->whereIn('model_type', [\App\Models\Arrival\Freight::class, 'Freight'])
                        ->where(function ($ssq) use ($ticket) {
                            $ssq->where('old_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('old_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":' . $ticket->id . '%')
                                ->orWhere('new_values', 'like', '%"arrival_ticket_id":"' . $ticket->id . '"%');
                        });
                });
            });
        } elseif ($section === 'loading_weight') {
            $query->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                ->where('model_id', $ticket->id)
                ->where(function ($q) {
                    $q->where('action', 'like', '%loading_weight%')
                        ->orWhere('description', 'like', '%loading weight%')
                        ->orWhere('old_values', 'like', '%"first_weight"%')
                        ->orWhere('old_values', 'like', '%"second_weight"%')
                        ->orWhere('old_values', 'like', '%"net_weight"%')
                        ->orWhere('old_values', 'like', '%"loading_weight"%')
                        ->orWhere('new_values', 'like', '%"first_weight"%')
                        ->orWhere('new_values', 'like', '%"second_weight"%')
                        ->orWhere('new_values', 'like', '%"net_weight"%')
                        ->orWhere('new_values', 'like', '%"loading_weight"%');
                });
        } elseif ($section === 'ticket_detail') {
            $query->whereIn('model_type', [\App\Models\Arrival\ArrivalTicket::class, 'ArrivalTicket'])
                ->where('model_id', $ticket->id)
                ->where(function ($q) {
                    $q->where('action', 'ticket_master_update')
                        ->orWhere('action', 'arrival_ticket_updated')
                        ->orWhere('action', 'arrival_ticket_unlocked')
                        ->orWhere('action', 'arrival_ticket_locked')
                        ->orWhere('action', 'arrival_ticket_created')
                        ->orWhere('action', 'arrival_ticket_created_locked')
                        ->orWhere('action', 'ticket_update')
                        ->orWhere('action', 'master_update')
                        ->orWhere('action', 'complete_ticket_revert')
                        ->orWhere('action', 'like', '%create%')
                        ->orWhere(function ($sq) {
                            $sq->where('action', 'like', '%ticket%')
                                ->where('action', 'not like', '%weight%')
                                ->where('action', 'not like', '%weighbridge%')
                                ->where('action', 'not like', '%location%')
                                ->where('action', 'not like', '%approval%')
                                ->where('action', 'not like', '%freight%');
                        });
                })
                ->whereNotIn('action', [
                    'location_transfer_update',
                    'location_transfer_revert',
                    'first_weighbridge_update',
                    'first_weighbridge_revert',
                    'second_weighbridge_update',
                    'second_weighbridge_revert',
                    'half_full_approval_update',
                    'half_full_approve_revert',
                    'freight_revert',
                    'freight_update',
                    'qc_request_revert',
                ]);
        }

        if ($excludeCreate) {
            $query->where('action', 'not like', '%create%')
                ->where('action', 'not like', '%store%');
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $fieldLabels = [
            'company_id' => 'Company',
            'location_id' => 'Location',
            'company_location_id' => 'Company Location',
            'arrival_location_id' => 'Arrival Warehouse / Location',
            'location_transfer_status' => 'Location Transfer Status',
            'first_weight' => 'First Weight',
            'second_weight' => 'Second Weight',
            'net_weight' => 'Net Weight',
            'arrived_net_weight' => 'Arrived Net Weight',
            'loading_weight' => 'Loading Weight',
            'truck_no' => 'Truck No',
            'bilty_no' => 'Bilty No',
            'bags' => 'Bags',
            'bag_weight' => 'Bag Weight',
            'sample_money' => 'Sample Money',
            'sample_money_type' => 'Sample Money Type',
            'gala_id' => 'Gala',
            'gala_name' => 'Gala Name',
            'location_type_id' => 'Location Type',
            'filling_bags_no' => 'Filling Bags No',
            'bag_type_id' => 'Bag Type',
            'bag_condition_id' => 'Bag Condition',
            'bag_packing_id' => 'Bag Packing',
            'total_bags' => 'Total Bags',
            'total_rejection' => 'Total Rejection',
            'total_receivings' => 'Total Receivings',
            'amanat' => 'Amanat',
            'document_approval_status' => 'Document Approval Status',
            'first_qc_status' => 'First QC Status',
            'second_qc_status' => 'Second QC Status',
            'freight_status' => 'Freight Status',
            'arrival_slip_status' => 'Arrival Slip Status',
            'weight' => 'Weight',
            'station' => 'Station',
            'station_id' => 'Station',
            'station_name' => 'Station Name',
            'miller_name' => 'Miller Name',
            'miller_id' => 'Miller',
            'product_id' => 'Commodity / Product',
            'qc_product' => 'QC Product',
            'arrival_product_id' => 'Arrival Product',
            'accounts_of' => 'Accounts Of',
            'accounts_of_id' => 'Accounts Of',
            'accounts_of_name' => 'Accounts Of Name',
            'supplier_id' => 'Supplier',
            'broker_id' => 'Broker',
            'broker_name' => 'Broker Name',
            'remarks' => 'Remarks',
            'remark' => 'Remarks',
            'loading_date' => 'Loading Date',
            'is_arrival_lock' => 'Arrival Lock Status',
            'locked_at' => 'Locked At',
            'unlocked_at' => 'Unlocked At',
            'bilty_return_confirmation' => 'Bilty Return Confirmation',
            'bilty_return_reason' => 'Bilty Return Reason',
            'decision_id' => 'Decision Maker',
            'decision_making' => 'Decision Making',
            'decision_making_time' => 'Decision Making Time',
            'is_lumpsum_deduction' => 'Lump Sum Deduction Active',
            'lumpsum_deduction' => 'Lump Sum Deduction (Rs)',
            'lumpsum_deduction_kgs' => 'Lump Sum Deduction (Kgs)',
            'lumpsum_deduction_maund' => 'Lump Sum Deduction (Maund)',
            'lumpsum_deduction_kgs_maund' => 'Lump Sum Deduction (Kgs/Maund)',
            'arrival_purchase_order_id' => 'Purchase Order / Contract',
            'truck_type_id' => 'Truck Type',
            'arrival_truck_type_id' => 'Truck Type',
            'driver_name' => 'Driver Name',
            'driver_phone' => 'Driver Phone',
            'contract_no' => 'Contract No',
            'sauda_type' => 'Sauda Type',
            'sauda_type_id' => 'Sauda Type',
            'is_ticket_verified' => 'Ticket Verified',
            'ticket_verified_by' => 'Verified By',
            'verified_at' => 'Verified At',
            'verified_by' => 'Verified By',
            'verify_at' => 'Verified At',
            'creator_id' => 'Created By',
            'created_by' => 'Created By',
            'done_by' => 'Done By',
            'sample_taken_by' => 'Sample Taken By',
            'approved_by' => 'Approved By',
            'freight_paid_by_supplier' => 'Freight Paid By Supplier',
            'closing_trucks_qty' => 'Closing Trucks Qty',
            'gross_freight_amount' => 'Gross Freight Amount',
            'labor_amount' => 'Labor Amount',
            'unpaid_labor_charges' => 'Unpaid Labor Charges',
            'other_labour_charges' => 'Other Labour Charges',
            'karachi_kanta_charges' => 'Kanta Charges',
        ];

        // Cache for relational lookups across logs
        $relationCache = [];
        $resolveRelationValue = function ($key, $val) use (&$relationCache) {
            if ($val === null || $val === '') {
                return null;
            }
            if (is_array($val)) {
                return json_encode($val);
            }

            $lookupMap = [
                'company_id'                => [\App\Models\ACL\Company::class, 'name'],
                'company_location_id'       => [\App\Models\Master\CompanyLocation::class, 'name'],
                'location_id'               => [\App\Models\Master\CompanyLocation::class, 'name'],
                'product_id'                => [\App\Models\Product::class, 'name'],
                'qc_product'                => [\App\Models\Product::class, 'name'],
                'arrival_product_id'        => [\App\Models\Product::class, 'name'],
                'miller_id'                 => [\App\Models\Master\Miller::class, 'name'],
                'accounts_of_id'            => [\App\Models\Master\Supplier::class, 'name'],
                'accounts_of'               => [\App\Models\Master\Supplier::class, 'name'],
                'supplier_id'               => [\App\Models\Master\Supplier::class, 'name'],
                'broker_id'                 => [\App\Models\Master\Supplier::class, 'name'],
                'broker_one_id'             => [\App\Models\Master\Supplier::class, 'name'],
                'broker_two_id'             => [\App\Models\Master\Supplier::class, 'name'],
                'broker_three_id'           => [\App\Models\Master\Supplier::class, 'name'],
                'station_id'                => [\App\Models\Master\Station::class, 'name'],
                'station'                   => [\App\Models\Master\Station::class, 'name'],
                'sauda_type_id'             => [\App\Models\SaudaType::class, 'name'],
                'truck_type_id'             => [\App\Models\Master\ArrivalTruckType::class, 'name'],
                'arrival_truck_type_id'     => [\App\Models\Master\ArrivalTruckType::class, 'name'],
                'arrival_location_id'       => [\App\Models\Master\ArrivalLocation::class, 'name'],
                'gala_id'                   => [\App\Models\Master\ArrivalSubLocation::class, 'name'],
                'location_type_id'          => [\App\Models\Master\LocationType::class, 'name'],
                'bag_type_id'               => [\App\Models\BagType::class, 'name'],
                'bag_condition_id'          => [\App\Models\BagCondition::class, 'name'],
                'bag_packing_id'            => [\App\Models\BagPacking::class, 'name'],
                'arrival_purchase_order_id' => [\App\Models\ArrivalPurchaseOrder::class, 'contract_no'],
                'decision_id'               => [\App\Models\User::class, 'name'],
                'ticket_verified_by'        => [\App\Models\User::class, 'name'],
                'verified_by'               => [\App\Models\User::class, 'name'],
                'creator_id'                => [\App\Models\User::class, 'name'],
                'created_by'                => [\App\Models\User::class, 'name'],
                'done_by'                   => [\App\Models\User::class, 'name'],
                'sample_taken_by'           => [\App\Models\User::class, 'name'],
                'approved_by'               => [\App\Models\User::class, 'name'],
                'user_id'                   => [\App\Models\User::class, 'name'],
                'division_id'               => [\App\Models\Master\Division::class, 'name'],
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

            // Boolean / lock fields formatting
            if ($key === 'is_arrival_lock') {
                return ($val == 1 || $val === true || $val === '1') ? 'Locked' : 'Unlocked';
            }
            if (in_array($key, [
                'is_lumpsum_deduction',
                'is_ticket_verified',
                'freight_paid_by_supplier',
                'is_re_sampling',
                'is_done',
                'is_request_by_purchaser',
                'is_auto_approved',
                'is_resampling_made'
            ])) {
                return ($val == 1 || $val === true || $val === '1') ? 'Yes' : 'No';
            }
            if ($key === 'bilty_return_confirmation') {
                return ($val == 1 || $val === true || $val === '1') ? 'Confirmed' : 'Not Confirmed';
            }

            // Status fields formatting
            if (in_array($key, [
                'document_approval_status',
                'first_qc_status',
                'second_qc_status',
                'arrival_slip_status',
                'freight_status',
                'location_transfer_status',
                'status'
            ]) && is_string($val)) {
                return ucwords(str_replace('_', ' ', $val));
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
        foreach ($logs as $log) {
            if ($excludeCreate) {
                $isCreate = str_contains($log->action, 'create') || str_contains($log->action, 'store') || empty($log->old_values);
                if ($isCreate) {
                    continue;
                }
            }
            $rawOld = is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true);
            if (is_string($rawOld)) $rawOld = json_decode($rawOld, true);

            $rawNew = is_array($log->new_values) ? $log->new_values : json_decode($log->new_values, true);
            if (is_string($rawNew)) $rawNew = json_decode($rawNew, true);

            $old = $flattenArray($rawOld);
            $new = $flattenArray($rawNew);

            $parsedChanges = [];
            $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));

            $skipKeys = ['id', 'arrival_ticket_id', 'deleted_at', 'creator_id', 'company_id', 'created_by', 'updated_by'];

            foreach ($allKeys as $key) {
                $baseKey = str_contains($key, '.') ? substr(strrchr($key, '.'), 1) : $key;
                if (in_array($key, $skipKeys) || in_array($baseKey, $skipKeys)) continue;

                $oldVal = $old[$key] ?? null;
                $newVal = $new[$key] ?? null;

                if ($oldVal != $newVal) {
                    $oldResolved = $resolveRelationValue($baseKey, $oldVal);
                    $newResolved = $resolveRelationValue($baseKey, $newVal);

                    $label = $fieldLabels[$key] ?? $fieldLabels[$baseKey] ?? ucwords(str_replace(['_', '.'], ' ', $baseKey));

                    $parsedChanges[] = [
                        'key'      => $key,
                        'base_key' => $baseKey,
                        'label'    => $label,
                        'old'      => is_array($oldResolved) ? json_encode($oldResolved) : $oldResolved,
                        'new'      => is_array($newResolved) ? json_encode($newResolved) : $newResolved,
                        'raw_old'  => $oldVal,
                        'raw_new'  => $newVal,
                    ];
                }
            }

            if (count($parsedChanges) > 1) {
                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => !in_array($c['key'], ['updated_at', 'created_at']) && !in_array($c['base_key'] ?? '', ['updated_at', 'created_at'])));
            }

            // Deduplicate redundant id + name pairs (e.g. broker_id + broker_name, accounts_of_id + accounts_of_name)
            $redundantPairs = [
                'broker_id' => 'broker_name',
                'broker_one_id' => 'broker_one_name',
                'broker_two_id' => 'broker_two_name',
                'broker_three_id' => 'broker_three_name',
                'accounts_of_id' => 'accounts_of_name',
                'accounts_of' => 'accounts_of_name',
                'supplier_id' => 'supplier_name',
                'station_id' => 'station_name',
                'station' => 'station_name',
                'miller_id' => 'miller_name',
                'gala_id' => 'gala_name',
            ];
            foreach ($redundantPairs as $idKey => $nameKey) {
                $idItem = null;
                $nameItem = null;
                foreach ($parsedChanges as $c) {
                    $cBase = $c['base_key'] ?? $c['key'];
                    if ($cBase === $idKey) $idItem = $c;
                    if ($cBase === $nameKey) $nameItem = $c;
                }
                if ($idItem && $nameItem) {
                    if (!empty($nameItem['old']) || !empty($nameItem['new'])) {
                        foreach ($parsedChanges as &$c) {
                            $cBase = $c['base_key'] ?? $c['key'];
                            if ($cBase === $idKey) {
                                if (!empty($nameItem['old'])) $c['old'] = $nameItem['old'];
                                if (!empty($nameItem['new'])) $c['new'] = $nameItem['new'];
                                break;
                            }
                        }
                        unset($c);
                    }
                    $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => ($c['base_key'] ?? $c['key']) !== $nameKey));
                }
            }

            if ($section === 'loading_weight') {
                $weightKeys = ['first_weight', 'second_weight', 'net_weight', 'loading_weight'];
                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => in_array($c['base_key'] ?? $c['key'], $weightKeys)));
                if (empty($parsedChanges)) {
                    continue;
                }
            } elseif ($section === 'location_transfer') {
                $locKeys = ['arrival_location_id', 'location_transfer_status', 'remarks', 'remark'];
                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => in_array($c['base_key'] ?? $c['key'], $locKeys)));
                if (empty($parsedChanges)) {
                    continue;
                }
            } elseif ($section === 'first_weighbridge') {
                $wbKeys = ['weight', 'arrival_first_weight', 'first_weighbridge_status', 'document_approval_status', 'remarks', 'remark'];
                $parsedChanges = array_values(array_filter($parsedChanges, function ($c) use ($wbKeys) {
                    $k = $c['base_key'] ?? $c['key'];
                    if (!in_array($k, $wbKeys)) {
                        return false;
                    }
                    if ($k === 'first_weighbridge_status') {
                        if (($c['old'] === null || $c['old'] === '' || $c['old'] === 'null') && $c['new'] === 'pending') {
                            return false;
                        }
                        if ($c['old'] === 'pending' && ($c['new'] === null || $c['new'] === '' || $c['new'] === 'null')) {
                            return false;
                        }
                    }
                    return true;
                }));
                if (empty($parsedChanges)) {
                    continue;
                }
            } elseif ($section === 'second_weighbridge') {
                $wb2Keys = ['weight', 'arrival_second_weight', 'second_weighbridge_status', 'arrived_net_weight', 'remarks', 'remark'];
                $parsedChanges = array_values(array_filter($parsedChanges, function ($c) use ($wb2Keys) {
                    $k = $c['base_key'] ?? $c['key'];
                    if (!in_array($k, $wb2Keys)) {
                        return false;
                    }
                    if ($k === 'second_weighbridge_status') {
                        if (($c['old'] === null || $c['old'] === '' || $c['old'] === 'null') && $c['new'] === 'pending') {
                            return false;
                        }
                        if ($c['old'] === 'pending' && ($c['new'] === null || $c['new'] === '' || $c['new'] === 'null')) {
                            return false;
                        }
                    }
                    return true;
                }));
                if (empty($parsedChanges)) {
                    continue;
                }
            } elseif ($section === 'half_full_approved') {
                $approvalKeys = [
                    'gala_id', 'gala_name', 'location_type_id', 'bag_type_id', 'bag_condition_id',
                    'bag_packing_id', 'bag_packing_approval', 'filling_bags_no', 'total_bags',
                    'total_rejection', 'total_receivings', 'amanat', 'truck_no', 'document_approval_status',
                    'remarks', 'remark'
                ];
                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => in_array($c['base_key'] ?? $c['key'], $approvalKeys)));
                if (empty($parsedChanges)) {
                    continue;
                }
            } elseif ($section === 'freight') {
                $freightKeys = [
                    'loaded_weight', 'arrived_weight', 'difference', 'exempted_weight', 'net_shortage',
                    'shortage_weight_freight_deduction', 'freight_per_ton', 'kanta_golarchi_charges',
                    'karachi_kanta_charges', 'other_labour_charges', 'other_deduction', 'unpaid_labor_charges',
                    'freight_written_on_bilty', 'gross_freight_amount', 'net_freight', 'bilty_document',
                    'loading_weight_document', 'status', 'unique_no', 'freight_status', 'arrival_slip_status',
                    'remarks', 'remark'
                ];
                $parsedChanges = array_values(array_filter($parsedChanges, function ($c) use ($freightKeys) {
                    $k = $c['base_key'] ?? $c['key'];
                    if (!in_array($k, $freightKeys)) {
                        return false;
                    }
                    if ($k === 'freight_status') {
                        if (($c['old'] === null || $c['old'] === '' || $c['old'] === 'null') && $c['new'] === 'pending') {
                            return false;
                        }
                        if ($c['old'] === 'pending' && ($c['new'] === null || $c['new'] === '' || $c['new'] === 'null')) {
                            return false;
                        }
                    }
                    return true;
                }));
                if (empty($parsedChanges)) {
                    continue;
                }
            } elseif ($section === 'ticket_detail') {
                $isCreate = str_contains($log->action, 'create') || str_contains($log->action, 'store');
                if ($excludeCreate && $isCreate) {
                    continue;
                }

                $ticketDetailKeys = [
                    'is_arrival_lock',
                    'locked_at',
                    'unlocked_at',
                    'location_id',
                    'company_location_id',
                    'arrival_purchase_order_id',
                    'contract_no',
                    'product_id',
                    'qc_product',
                    'arrival_product_id',
                    'miller_name',
                    'miller_id',
                    'broker_id',
                    'broker_name',
                    'broker_one_id',
                    'broker_two_id',
                    'broker_three_id',
                    'decision_id',
                    'decision_making',
                    'decision_making_time',
                    'accounts_of',
                    'accounts_of_id',
                    'accounts_of_name',
                    'supplier_id',
                    'station',
                    'station_id',
                    'station_name',
                    'truck_no',
                    'bilty_no',
                    'truck_type_id',
                    'arrival_truck_type_id',
                    'sample_money_type',
                    'sample_money',
                    'bags',
                    'loading_date',
                    'remarks',
                    'remark',
                    'sauda_type',
                    'sauda_type_id',
                    'driver_name',
                    'driver_phone',
                    'first_weight',
                    'second_weight',
                    'net_weight',
                    'loading_weight',
                    'arrived_net_weight',
                ];

                $parsedChanges = array_values(array_filter($parsedChanges, fn($c) => in_array($c['base_key'] ?? $c['key'], $ticketDetailKeys)));
                if (empty($parsedChanges)) {
                    continue;
                }
            }

            $log->parsed_changes = $parsedChanges;
            $filteredLogs[] = $log;
        }

        $logs = $filteredLogs;

        $sectionTitles = [
            'all' => 'All Changes',
            'ticket_detail' => 'Ticket & Loading Weight Detail',
            'loading_weight' => 'Loading Weight',
            'location_transfer' => 'Location Transfer',
            'first_weighbridge' => 'First Weighbridge Detail',
            'half_full_approved' => 'Half / Full Approved',
            'second_weighbridge' => 'Second Weighbridge Detail',
            'freight' => 'Arrival Freight',
        ];

        $title = $sectionTitles[$section] ?? 'Audit Logs';

        return view('management.arrival.ticket.partials.audit_logs_content', compact('ticket', 'section', 'logs', 'title'));
    }
}
