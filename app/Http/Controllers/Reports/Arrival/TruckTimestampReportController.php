<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Miller;
use App\Models\Product;
use App\Models\Arrival\ArrivalTicket;
use Illuminate\Http\Request;

class TruckTimestampReportController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $millers = Miller::all();
        $locations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        return view('management.reports.arrival.truck-timestamp.index', compact('commodities', 'millers', 'locations'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $tickets = ArrivalTicket::select('arrival_tickets.*', 'grn_numbers.unique_no as grn_unique_no')
            ->leftJoin('arrival_slips', 'arrival_tickets.id', '=', 'arrival_slips.arrival_ticket_id')
            ->leftJoin('grn_numbers', function ($join) {
                $join->on('arrival_slips.id', '=', 'grn_numbers.model_id')
                    ->where('grn_numbers.model_type', 'arrival-slip');
            })
            ->with([
                'creator',
                'decisionBy',
                'location',
                'station',
                'miller',
                'saudaType',
                'accountsOf',
                'broker',
                'purchaseOrder',
                'ticketVerifiedBy',
                'firstWeighbridge.createdBy',
                'secondWeighbridge.createdBy',
                'unloadingLocation.createdBy',
                'arrivalSlip.creator',
                'approvals.creator',
                'arrivalSamplingRequests' => function ($q) {
                    $q->with(['takenByUser', 'approvedByUser', 'doneByUser'])->orderBy('id', 'asc');
                },
                'initialSampling' => function ($q) {
                    $q->where('sampling_type', 'initial')
                        ->whereIn('approved_status', ['approved', 'rejected'])
                        ->with(['takenByUser', 'approvedByUser'])
                        ->latest();
                },
            ])
            ->when($request->filled('grn_no'), function ($q) use ($request) {
                return $q->where('grn_numbers.unique_no', 'like', '%' . $request->grn_no . '%');
            })
            ->when($request->filled('truck_no'), function ($q) use ($request) {
                return $q->where('arrival_tickets.truck_no', 'like', '%' . $request->truck_no . '%');
            })
            ->when($request->filled('bilty_no'), function ($q) use ($request) {
                return $q->where('arrival_tickets.bilty_no', 'like', '%' . $request->bilty_no . '%');
            })
            ->when($request->filled('arrival_ticket_no'), function ($q) use ($request) {
                return $q->where('arrival_tickets.unique_no', 'like', '%' . $request->arrival_ticket_no . '%');
            })
            ->when($request->filled('commodity_id'), function ($q) use ($request) {
                return $q->where(function ($subQuery) use ($request) {
                    $subQuery->whereHas('qcProduct', function ($query) use ($request) {
                        $query->whereIn('id', (array)$request->commodity_id);
                    })->orWhereHas('product', function ($query) use ($request) {
                        $query->whereIn('id', (array)$request->commodity_id);
                    });
                });
            })
            ->when($request->filled('miller_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.miller_id', $request->miller_id);
            })
            ->when($request->filled('sauda_type_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.sauda_type_id', $request->sauda_type_id);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereIn('arrival_tickets.location_id', (array)$request->company_location_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                if (count($dates) == 2) {
                    $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                    $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                    return $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                        ->whereDate('arrival_tickets.created_at', '<=', $endDate);
                }
            })
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations());
            })
            ->orderBy('arrival_tickets.created_at', 'asc')
            ->get();

        return view('management.reports.arrival.truck-timestamp.getTruckTimestamp', compact('tickets'));
    }
}
