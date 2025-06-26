<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Arrival\ArrivalSamplingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard', ['only' => ['index']]);
        $this->middleware('auth');
    }

    private function getArrivalDashboardData($fromDate, $toDate, $companyId)
    {
        $dateRange = [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()];

        $newTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('first_qc_status', 'pending')
            ->whereBetween('created_at', $dateRange)
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
            ->whereBetween('created_at', $dateRange)
            ->count();

        $rejectedTickets = ArrivalTicket::where('company_id', $companyId)
            ->where('first_qc_status', 'rejected')
            ->where('bilty_return_confirmation', 0)
            ->whereBetween('created_at', $dateRange)
            ->count();

        $firstWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->where('location_transfer_status', 'transfered')
            ->where('first_weighbridge_status', 'pending')
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
            ->whereBetween('created_at', $dateRange)
            ->count();

        $secondWeighbridgePending = ArrivalTicket::where('company_id', $companyId)
            ->whereIn('document_approval_status', ['half_approved', 'fully_approved'])
            ->where('second_weighbridge_status', 'pending')
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
            ->whereNull('freight_status')
            ->whereBetween('created_at', $dateRange)
            ->count();

        return [
            'new_tickets' => $newTickets,
            'initial_sampling_done' => $initialSamplingDone,
            'resampling_required' => $resamplingRequired,
            'location_transfer_pending' => $locationTransferPending,
            'rejected_tickets' => $rejectedTickets,
            'first_weighbridge_pending' => $firstWeighbridgePending,
            'inner_sampling_requested' => $innerSamplingRequested,
            'inner_sampling_pending_approval' => $innerSamplingPendingApproval,
            'decision_on_average_enabled' => $decisionOnAverageEnabled,
            'freight_ready' => $freightReady,
            'half_full_approve_pending' => $halfFullApprovePending,
            'second_weighbridge_pending' => $secondWeighbridgePending,
        ];
    }

    public function getListData(Request $request)
    {
        $type = $request->get('type');
        $fromDate = $request->get('from_date', Carbon::today()->format('Y-m-d'));
        $fromDate = $request->get('from_date', Carbon::now()->subYear()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::today()->format('Y-m-d'));
        $dateRange = [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()];

        $data = [];
        $title = '';

        switch ($type) {
            case 'new_tickets':
                $title = 'New Tickets (Pending Initial Sampling)';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('first_qc_status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    ->latest()
                    ->paginate(25);
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
                    ->latest()
                    ->paginate(25);
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
                    ->paginate(25);
                break;

            case 'location_transfer_pending':
                $title = 'Location Transfer Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('location_transfer_status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    ->latest()
                    ->paginate(25);
                break;

            case 'rejected_tickets':
                $title = 'Rejected Tickets (Bilty Return Pending)';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('first_qc_status', 'rejected')
                    ->where('bilty_return_confirmation', 0)
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    ->latest()
                    ->paginate(25);
                break;

            case 'first_weighbridge_pending':
                $title = 'First Weighbridge Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('location_transfer_status', 'transfered')
                    ->where('first_weighbridge_status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf', 'unloadingLocation'])
                    ->latest()
                    ->paginate(25);
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
                    ->paginate(25);
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
                    ->paginate(25);
                break;

            case 'decision_on_average_enabled':
                $title = 'Decision on Average Enabled';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('decision_making', 1)
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf'])
                    ->latest()
                    ->paginate(25);
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
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf', 'firstWeighbridge'])
                    ->latest()
                    ->paginate(25);
                break;

            case 'second_weighbridge_pending':
                $title = 'Second Weighbridge Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->whereIn('document_approval_status', ['half_approved', 'fully_approved'])
                    ->where('second_weighbridge_status', 'pending')
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf', 'approvals'])
                    ->latest()
                    ->paginate(25);
                break;

            case 'freight_ready':
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('second_weighbridge_status', 'completed')
                    ->whereNull('freight_status')
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf', 'secondWeighbridge'])
                    ->latest()
                    ->paginate(25);
                break;

            case 'freight_pending':
                $title = 'Freight Pending';
                $data = ArrivalTicket::where('company_id', $request->company_id)
                    ->where('second_weighbridge_status', 'completed')
                    ->whereNull('freight_status')
                    ->where('decision_making', 0)
                    ->whereBetween('created_at', $dateRange)
                    ->with(['product', 'station', 'accountsOf', 'secondWeighbridge'])
                    ->latest()
                    ->paginate(25);
                break;
        }

        // return response()->json([
        //     'title' => $title,
        //     'data' => $data,
        //     'type' => $type
        // ]);

        return view('management.dashboard.snippets.list_data', compact('data', 'type'));
    }

    public function index(Request $request)
    {
        $module = $request->get('module', 'arrival');
        $fromDate = $request->get('from_date', Carbon::today()->format('Y-m-d'));
        $fromDate = $request->get('from_date', Carbon::now()->subYear()->format('Y-m-d'));
        $toDate = $request->get('to_date', Carbon::today()->format('Y-m-d'));

        $data = [];

        if ($module === 'arrival') {
            $data = $this->getArrivalDashboardData($fromDate, $toDate, $request->company_id);
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
}
