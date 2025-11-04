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
use App\Models\Master\{Station,ArrivalSubLocation};
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\SaudaType;
use App\Models\User;
use App\Models\{BagType,BagCondition,BagPacking};
use App\Models\Master\ArrivalLocation;
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
        $tickets = ArrivalTicket::when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->search . '%';
            $q->where(function ($sq) use ($searchTerm) {
                $sq->where('unique_no', 'like', $searchTerm)
                    // ->orWhere('supplier_name', 'like', $searchTerm)
                    ->orWhere('truck_no', 'like', $searchTerm)
                    ->orWhere('bilty_no', 'like', $searchTerm);
            });
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
            // Yahan naya condition add karen
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
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

        $companyLocations = $isSuperAdmin
            ? CompanyLocation::all()
            : collect([$userLocation])->filter();

        $arrivalPurchaseOrders = ArrivalPurchaseOrder::with(['product', 'supplier', 'saudaType'])
            ->where('purchase_type', 'regular')
            ->when(!$isSuperAdmin, function ($q) use ($userLocation) {
                $q->where('company_location_id', $userLocation?->id);
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
            // ->where('status', 'active')
            ->get();

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
            $requestData['location_id'] = $authUser->company_location_id;
        } else {
            $requestData['location_id'] = $request->company_location_id;
        }

        $locationCode = CompanyLocation::find($request->company_location_id)->code ?? 'KHI';
        $uniqueNo = generateTicketNoWithDateFormat('arrival_tickets', $locationCode);

        $requestData['unique_no'] = $uniqueNo;

        if (!empty($requestData['accounts_of'])) {
            $supplier = Supplier::where('name', $requestData['accounts_of'])->first();
            $requestData['accounts_of_id'] = $supplier ? $supplier->id : null;
            $requestData['accounts_of_name'] = $requestData['accounts_of'];
        }

        if (!empty($requestData['station'])) {
            $station = Station::where('name', $requestData['station'])->first();
            $requestData['station_id'] = $station ? $station->id : null;
            $requestData['station_name'] = $requestData['station'];
        }

        if (!empty($requestData['broker_name'])) {
            $broker = Supplier::where('name', $requestData['broker_name'])->first();
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

        $arrivalTicket = ArrivalTicket::create($requestData);

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
        $authUserCompany = $request->company_id;
        $isSuperAdmin = getUserParams('user_type') == 'super-admin';
        $userLocation = getUserParams('company_location_id');

        $arrivalPurchaseOrders = ArrivalPurchaseOrder::with(['product', 'supplier', 'saudaType'])
            // ->where('purchase_type', 'regular')
            ->when(!$isSuperAdmin, function ($q) use ($userLocation) {
                $q->where('company_location_id', $userLocation);
            })
            ->orderByDesc('id')
            ->get();

        $accountsOf = User::role('Purchaser')
            ->whereHas('companies', function ($q) use ($authUserCompany) {
                $q->where('companies.id', $authUserCompany);
            })
            ->get();

        $suppliers = Supplier::where('status', 'active')->get();
        $products = Product::where('status', 'active')->get();

        $arrivalTicket = ArrivalTicket::findOrFail($id);
        return view('management.arrival.ticket.edit', compact('arrivalTicket', 'accountsOf', 'arrivalPurchaseOrders', 'suppliers', 'products'));
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

        $data = $request->validated();
        $request['accounts_of_id'] = $request['accounts_of'] ?? NULL;
        $request['truck_type_id'] = $request['arrival_truck_type_id'] ?? NULL;
        $arrivalTicket->update($request->all());

        return response()->json(['success' => 'Arrival Ticket updated successfully.', 'data' => $arrivalTicket], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ArrivalTicket $arrivalTicket): JsonResponse
    {
        $arrivalTicket->delete();
        return response()->json(['success' => 'Arrival Ticket deleted successfully.'], 200);
    }

    public function confirmBiltyReturn(ArrivalTicket $ticket)
    {
        try {
            $updateData = [
                'bilty_return_confirmation' => 1,
                'bilty_return_reason' => request('bilty_return_reason')
            ];

            $ticket->update($updateData);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
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
}
