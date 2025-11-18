<?php

namespace App\Http\Controllers;

use App\Models\Procurement\Store\PurchaseOrderData;
use Illuminate\Http\Request;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSamplingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ArrivalDashboardService;
class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index']]);
        $this->middleware('auth');
    }

    private function getArrivalDashboardDatabk($fromDate, $toDate, $companyId)
    {
        $authUser = auth()->user();
        $dateRange = [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()];

        $totalTickets = ArrivalTicket::where('company_id', $companyId)
            // ->whereIn('first_qc_status', ['pending', 'resampling'])
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $newTickets = ArrivalTicket::where('company_id', $companyId)
            ->whereIn('first_qc_status', ['pending', 'resampling'])
            ->whereBetween('created_at', $dateRange)
            ->count();


        $initialSamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })

            //Superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereHas('arrivalTicket', function ($sq) {
                    $sq->where('location_id', auth()->user()->company_location_id);
                });
            })
            ->where('sampling_type', 'initial')
            ->where('is_done', 'no')
            ->count();



        $initialSamplingDone = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            ->where('sampling_type', 'initial')
            ->where('is_done', 'yes')
            ->where('approved_status', 'pending')
            ->count();

        $resamplingRequired = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            ->where('sampling_type', 'initial')
            ->where('is_re_sampling', 'yes')
            ->where('is_done', 'no')
            ->count();

        $locationTransferPending = ArrivalTicket::where('company_id', $companyId)
            ->where('location_transfer_status', 'pending')
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $rejectedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('first_qc_status', 'rejected')
            ->where('bilty_return_confirmation', 0)
            ->whereBetween('created_at', $dateRange)
            ->count();

        $completedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('arrival_slip_status', 'generated')
            ->whereBetween('created_at', $dateRange)
            // Superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->when(
                $authUser->user_type != 'super-admin' && $authUser->arrival_location_id,
                function ($query) use ($authUser) {
                    return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                        $q->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                }
            )
            ->count();

        $firstWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->where('location_transfer_status', 'transfered')
            ->where('first_weighbridge_status', 'pending')
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->when(
                auth()->user()->user_type != 'super-admin' && auth()->user()->arrival_location_id,
                function ($query) use ($authUser) {
                    return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                        $q->where('arrival_location_id', $authUser->arrival_location_id);
                    });
                }
            )
            ->whereBetween('created_at', $dateRange)
            ->count();

        $innerSamplingRequested = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            ->where('sampling_type', 'inner')
            ->where('is_done', 'no')
            ->count();

        $innerSamplingPendingApproval = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($companyId, $dateRange) {
            $q->where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange);
        })
            ->where('sampling_type', 'inner')
            ->where('is_done', 'yes')
            ->where('approved_status', 'pending')
            ->count();

        $decisionOnAverageEnabled = ArrivalTicket::where('company_id', $companyId)
            ->where('decision_making', 1)
            ->whereBetween('created_at', $dateRange)
            ->count();

        $halfFullApprovePending = ArrivalTicket::where('company_id', $companyId)
            ->where('first_weighbridge_status', 'completed')
            ->whereNull('document_approval_status')
            ->whereDoesntHave('arrivalSamplingRequests', function ($q) {
                $q->where('sampling_type', 'inner')
                    ->where('approved_status', 'pending');
            })
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $secondWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->whereIn('document_approval_status', ['half_approved', 'fully_approved'])
            ->where('second_weighbridge_status', 'pending')
            //superadmin
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where('location_id', auth()->user()->company_location_id);
            })
            ->whereBetween('created_at', $dateRange)
            ->count();

        $freightPending = ArrivalTicket::where('company_id', $companyId)
            ->where('second_weighbridge_status', 'completed')
            ->whereNull('freight_status')
            ->where('decision_making', 0)
            ->whereBetween('created_at', $dateRange)
            ->count();

        $freightReady = ArrivalTicket::where('company_id', $companyId)
            ->where('second_weighbridge_status', 'completed')
            ->where('freight_status', 'pending')
            ->whereBetween('created_at', $dateRange)
            ->count();

        return [
            'total_tickets' => $totalTickets,
            'new_tickets' => $newTickets,
            'initial_sampling_requested' => $initialSamplingRequested,
            'initial_sampling_done' => $initialSamplingDone,
            'resampling_required' => $resamplingRequired,
            'location_transfer_pending' => $locationTransferPending,
            'rejected_tickets' => $rejectedTickets,
            'completed_tickets' => $completedTickets,
            'first_weighbridge_pending' => $firstWeighbridgePending,
            'inner_sampling_requested' => $innerSamplingRequested,
            'inner_sampling_pending_approval' => $innerSamplingPendingApproval,
            'decision_on_average_enabled' => $decisionOnAverageEnabled,
            'freight_ready' => $freightReady,
            'half_full_approve_pending' => $halfFullApprovePending,
            'second_weighbridge_pending' => $secondWeighbridgePending,
        ];
    }

    public function getArrivalDashboardDatadbk(Request $request, ArrivalDashboardService $service)
    {
        return $service->getArrivalDashboardData(
            $request->from_date,
            $request->to_date,
            $request->company_id
        );
    }

    public function getListData(Request $request)
    {


        $authUser = auth()->user();
        $type = $request->get('type');
        $fromDate = $request->get('from_date', Carbon::today()->format('Y-m-d'));
        // $fromDate = $request->get('from_date', Carbon::now()->subYear()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::today()->format('Y-m-d'));
        $dateRange = [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()];

        $data = [];
        $title = '';

        switch ($type) {
            case 'total_tickets':
                $title = 'Total Tickets';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    // ->whereIn('first_qc_status', ['pending', 'resampling'])
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'new_tickets':
                $title = 'New Tickets (Pending Initial Sampling)';
                $data = ArrivalTicket::where('company_id', $request->company_id)

                    ->whereIn('first_qc_status', ['pending', 'resampling'])
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    // Superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->when(
                        $authUser->user_type != 'super-admin' && $authUser->arrival_location_id,
                        function ($query) use ($authUser) {
                            return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                                $q->where('arrival_location_id', $authUser->arrival_location_id);
                            });
                        }
                    )
                    ->latest()
                    ->paginate(1000);
                break;
            case 'initial_sampling_requested':
                $title = 'Initial Sampling Requested (Not Done)';
                $data = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($request, $dateRange) {
                    $q->where('company_id', $request->company_id)
                        ->whereBetween('created_at', $dateRange);
                })
                    ->where('sampling_type', 'initial')
                    ->where('is_done', 'no')
                    ->with(['arrivalTicket.product', 'arrivalTicket.station'])
                    //Superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->whereHas('arrivalTicket', function ($sq) {
                            $sq->where('location_id', auth()->user()->company_location_id);
                        });
                    })


                    ->latest()
                    ->paginate(1000);
                break;

            case 'completed_tickets':
                $title = 'Completed Tickets (Arrival Slip Generated)';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('arrival_slip_status', 'generated')
                    ->with(['product', 'station', 'accountsOf'])
                    // Superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->when(
                        $authUser->user_type != 'super-admin' && $authUser->arrival_location_id,
                        function ($query) use ($authUser) {
                            return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                                $q->where('arrival_location_id', $authUser->arrival_location_id);
                            });
                        }
                    )
                    ->latest()
                    ->paginate(1000);
                break;

            case 'initial_sampling_done':
                $title = 'Initial Sampling Done (Pending Approval)';
                $data = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($request, $dateRange) {
                    $q->where('company_id', $request->company_id)
                        ->whereBetween('created_at', $dateRange);
                })
                    ->where('sampling_type', 'initial')
                    ->where('is_done', 'yes')
                    ->where('approved_status', 'pending')
                    ->with(['arrivalTicket.product', 'arrivalTicket.station'])
                    //Superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->whereHas('arrivalTicket', function ($sq) {
                            $sq->where('location_id', auth()->user()->company_location_id);
                        });
                    })
                    ->latest()
                    ->paginate(1000);
                break;

            case 'resampling_required':
                $title = 'Resampling Required';
                $data = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($request, $dateRange) {
                    $q->where('company_id', $request->company_id)
                        ->whereBetween('created_at', $dateRange);
                })
                    ->where('sampling_type', 'initial')
                    ->where('is_re_sampling', 'yes')
                    ->where('is_done', 'no')
                    ->with(['arrivalTicket.product', 'arrivalTicket.station'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'location_transfer_pending':
                $title = 'Location Transfer Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('location_transfer_status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    //superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->latest()
                    ->paginate(1000);
                break;

            case 'rejected_tickets':
                $title = 'Rejected Tickets (Bilty Return Pending)';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('first_qc_status', 'rejected')
                    ->where('bilty_return_confirmation', 0)
                    ->whereBetween('created_at', $dateRange)
                    //superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->with(['product', 'station', 'accountsOf'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'first_weighbridge_pending':
                $title = 'First Weighbridge Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('location_transfer_status', 'transfered')
                    ->where('first_weighbridge_status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    //superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })

                    ->when(
                        $authUser->user_type != 'super-admin' && $authUser->arrival_location_id,
                        function ($query) use ($authUser) {
                            return $query->whereHas('unloadingLocation', function ($q) use ($authUser) {
                                $q->where('arrival_location_id', $authUser->arrival_location_id);
                            });
                        }
                    )
                    ->with(['product', 'station', 'accountsOf', 'unloadingLocation'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'inner_sampling_requested':
                $title = 'Inner Sampling Requested (Not Done)';
                $data = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($request, $dateRange) {
                    $q->where('company_id', $request->company_id)
                        ->whereBetween('created_at', $dateRange);
                })
                    ->where('sampling_type', 'inner')
                    ->where('is_done', 'no')
                    ->with(['arrivalTicket.product', 'arrivalTicket.station'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'inner_sampling_pending_approval':
                $title = 'Inner Sampling Pending Approval';
                $data = ArrivalSamplingRequest::whereHas('arrivalTicket', function ($q) use ($request, $dateRange) {
                    $q->where('company_id', $request->company_id)
                        ->whereBetween('created_at', $dateRange);
                })
                    ->where('sampling_type', 'inner')
                    ->where('is_done', 'yes')
                    ->where('approved_status', 'pending')
                    ->with(['arrivalTicket.product', 'arrivalTicket.station'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'decision_on_average_enabled':
                $title = 'Decision on Average Enabled';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('decision_making', 1)
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'half_full_approve_pending':
                $title = 'Half/Full Approve Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('first_weighbridge_status', 'completed')
                    ->whereNull('document_approval_status')
                    ->whereDoesntHave('arrivalSamplingRequests', function ($q) {
                        $q->where('sampling_type', 'inner')
                            ->where('approved_status', 'pending');
                    })
                    //superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf', 'firstWeighbridge'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'second_weighbridge_pending':
                $title = 'Second Weighbridge Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->whereIn('document_approval_status', ['half_approved', 'fully_approved'])
                    ->where('second_weighbridge_status', 'pending')
                    //superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf', 'approvals'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'freight_ready':
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('second_weighbridge_status', 'completed')
                    ->where('freight_status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    //superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->with(['product', 'station', 'accountsOf', 'secondWeighbridge'])
                    ->latest()
                    ->paginate(1000);
                break;

            case 'freight_pending':
                $title = 'Freight Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('second_weighbridge_status', 'completed')
                    ->where('freight_status', 'pending')
                    ->where('decision_making', 0)
                    ->whereBetween('created_at', $dateRange)
                    //superadmin
                    ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                        return $q->where('location_id', auth()->user()->company_location_id);
                    })
                    ->with(['product', 'station', 'accountsOf', 'secondWeighbridge'])
                    ->latest()
                    ->paginate(1000);
                break;
        }

        // return response()->json([
        //     'title' => $title,
        //     'data' => $data,
        //     'type' => $type
        // ]);

        return view('management.dashboard.snippets.list_data', compact('data', 'type'));
    }

    public function index(Request $request, ArrivalDashboardService $service)
    {
        $module = $request->get('module', 'arrival');
        $fromDate = $request->get('from_date', Carbon::today()->format('Y-m-d'));
        // $fromDate = $request->get('from_date', Carbon::now()->subYear()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::today()->format('Y-m-d'));

        $data = [];

        if ($module === 'arrival') {
            $data = $service->getArrivalDashboardData(
                $request->from_date,
                $request->to_date,
                $request->company_id
            );
           // $data = $this->getArrivalDashboardData($fromDate, $toDate, $request->company_id);
        }

        return view('management.dashboard.index', compact('data', 'module', 'fromDate', 'toDate'));
    }

    public function getCitiesByState(Request $request)
    {
        $countryId = $request->input('state_id');
        $cities = Cities::where('state_id', $countryId)->get();
        return response()->json($cities);
    }

    public function getStatesByCountry(Request $request)
    {
        $countryId = $request->input('country_id');
        $cities = States::where('country_id', $countryId)->get();
        return response()->json($cities);
    }
    public function dynamicFetchData(Request $request)
    {
        $search = $request->input('search');
        $tableName = $request->input('table');
        $columnName = $request->input('column');
        $idColumn = $request->input('idColumn', 'id');
        $enableTags = $request->input('enableTags', false);

        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName) || !Schema::hasColumn($tableName, $idColumn)) {
            return response()->json(['error' => 'Invalid table or column'], 400);
        }

        $query = DB::table($tableName);

        if (Schema::hasColumn($tableName, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($search) {
            $query->where($columnName, 'like', '%' . $search . '%');
        }

        $data = $query->limit(50)->get();

        $results = [];
        foreach ($data as $item) {
            $results[] = [
                'id' => $item->$idColumn,
                'text' => $item->$columnName
            ];
        }

        if (count($results) === 0 && $enableTags == "true") {
            $results[] = [
                'id' => $search,
                'text' => $search,
                'newTag' => true
            ];
        }

        return response()->json(['items' => $results]);
    }

    public function dynamicDependentFetchData(Request $request)
    {
        $search = $request->input('search');
        $tableName = $request->input('table');
        $columnName = $request->input('column');
        $idColumn = $request->input('idColumn', 'id');
        $enableTags = $request->input('enableTags', false);
        $targetTable = $request->input('targetTable');
        $targetColumn = $request->input('targetColumn');
        $fetchMode = $request->input('fetchMode', 'source');
        $query = DB::table($tableName);

        // If fetching target data
        if ($fetchMode === 'target') {
            $sourceId = $request->input('sourceId');
            $purchaseRequestId = $request->input('purchase_request_id');

            if (!$targetTable || !$targetColumn) {
                return response()->json(['error' => 'Target table and column required'], 400);
            }
            if ($purchaseRequestId && Schema::hasColumn($targetTable, 'am_approval_status')) {
                $query = DB::table(table: $targetTable)->whereIn("{$targetTable}.am_approval_status", ['approved', 'partial_approved']);
            }
            if (Schema::hasColumn($targetTable, 'deleted_at')) {
                $query->whereNull("{$targetTable}.deleted_at");
            }

            if ($purchaseRequestId && Schema::hasColumn($targetTable, 'purchase_request_id')) {
                $query->where("{$targetTable}.purchase_request_id", $purchaseRequestId);
            }

       

            if ($search) {
                $query->where("{$targetTable}.name", 'like', '%' . $search . '%');
            }

           if ($sourceId) {
    $query->where(function ($q) use ($targetTable, $targetColumn, $sourceId) {
        $q->where("{$targetTable}.{$targetColumn}", $sourceId)
            ->orWhereRaw("FIND_IN_SET(?, {$targetTable}.{$targetColumn}) > 0", [$sourceId])
            ->orWhereJsonContains("{$targetTable}.{$targetColumn}", $sourceId)
            ->orWhereJsonContains("{$targetTable}.{$targetColumn}", (string) $sourceId);
    });
}

            $displayColumn = Schema::hasColumn($targetTable ?? $tableName, 'name')
                ? 'name'
                : (Schema::hasColumn($targetTable ?? $tableName, 'purchase_quotation_no')
                    ? 'purchase_quotation_no'
                    : "{$columnName}");

            if($purchaseRequestId && Schema::hasColumn($targetTable, 'purchase_request_id')) {
                $data = $query->join("purchase_quotation_data", "purchase_quotation_data.purchase_quotation_id", "=", "purchase_quotations.id");
            }
            $data = $query->select(['purchase_quotations.id', "$displayColumn as text", "purchase_quotation_data.qty", "purchase_quotation_data.id as purchase_quotation_data_id"])->limit(50)->get();
        
            dd($data);
            if ($purchaseRequestId && Schema::hasColumn($targetTable, 'purchase_request_id')) {
                $data = $data->reject(function ($datum)  {
                    $purchaseOrderData = PurchaseOrderData::where("purchase_quotation_data_id", $datum->purchase_quotation_data_id)->get();
                    $totalOrdered = $purchaseOrderData->sum("qty");
                    $remainingQty = $datum->qty - $totalOrdered;
                    return $remainingQty <= 0;
                });
            }

            return response()->json(['items' => $data]);
        }
        // Original source table fetch logic
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName) || !Schema::hasColumn($tableName, $idColumn)) {
            return response()->json(['error' => 'Invalid table or column'], 400);
        }


        if (Schema::hasColumn($tableName, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($search) {
            $query->where($columnName, 'like', '%' . $search . '%');
        }

        $data = $query->select(["$tableName.$idColumn", "$tableName.$columnName as text"])->limit(50)->get();

        $results = [];
        foreach ($data as $item) {
            $results[] = [
                'id' => $item->$idColumn,
                'text' => $item->text
            ];
        }

        if (count($results) === 0 && $enableTags == "true") {
            $results[] = [
                'id' => $search,
                'text' => $search,
                'newTag' => true
            ];
        }

        return response()->json(['items' => $results]);
    }


     public function dynamicDependentFetchDataAll(Request $request)
    {
        $search = $request->input('search');
        $tableName = $request->input('table');
        $columnName = $request->input('column');
        $idColumn = $request->input('idColumn', 'id');
        $enableTags = $request->input('enableTags', false);
        $targetTable = $request->input('targetTable');
        $targetColumn = $request->input('targetColumn');
        $fetchMode = $request->input('fetchMode', 'source');
        $query = DB::table($tableName);

        // If fetching target data
        if ($fetchMode === 'target') {
            $sourceId = $request->input('sourceId');
            $purchaseRequestId = $request->input('purchase_request_id');

            if (!$targetTable || !$targetColumn) {
                return response()->json(['error' => 'Target table and column required'], 400);
            }
            if ($purchaseRequestId && Schema::hasColumn($targetTable, 'am_approval_status')) {
                $query = DB::table($targetTable);
            }
            if (Schema::hasColumn($targetTable, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if ($purchaseRequestId && Schema::hasColumn($targetTable, 'purchase_request_id')) {
                $query->where('purchase_request_id', $purchaseRequestId);
            }

            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }

            if ($sourceId) {
                $query->where(function ($q) use ($targetColumn, $sourceId) {
                    $q->where($targetColumn, $sourceId)
                        ->orWhereRaw("FIND_IN_SET(?, $targetColumn) > 0", [$sourceId])
                        ->orWhereJsonContains($targetColumn, $sourceId)
                        ->orWhereJsonContains($targetColumn, (string) $sourceId);
                });
            }

            $displayColumn = Schema::hasColumn($targetTable ?? $tableName, 'name')
                ? 'name'
                : (Schema::hasColumn($targetTable ?? $tableName, 'purchase_quotation_no')
                    ? 'purchase_quotation_no'
                    : $columnName);

            $data = $query->select(['id', "$displayColumn as text"])->limit(50)->get();

            return response()->json(['items' => $data]);
        }

        // Original source table fetch logic
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, $columnName) || !Schema::hasColumn($tableName, $idColumn)) {
            return response()->json(['error' => 'Invalid table or column'], 400);
        }


        if (Schema::hasColumn($tableName, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($search) {
            $query->where($columnName, 'like', '%' . $search . '%');
        }

        $data = $query->select(["$tableName.$idColumn", "$tableName.$columnName as text"])->limit(50)->get();

        $results = [];
        foreach ($data as $item) {
            $results[] = [
                'id' => $item->$idColumn,
                'text' => $item->text
            ];
        }

        if (count($results) === 0 && $enableTags == "true") {
            $results[] = [
                'id' => $search,
                'text' => $search,
                'newTag' => true
            ];
        }

        return response()->json(['items' => $results]);
    }
}
