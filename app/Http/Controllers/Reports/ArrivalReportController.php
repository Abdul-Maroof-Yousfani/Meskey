<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Master\Account\Account;
use App\Models\Master\Miller;
use App\Models\Product;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\Account\Transaction;
use DB;
use Illuminate\Http\Request;

class ArrivalReportController extends Controller
{
    public function index()
    {

        $commodities = Product::all();
        $millers = Miller::all();

        return view('management.reports.arrival.arrival-history.index', compact('commodities', 'millers'));
    }

    public function getArrivalReport(Request $request)
    {
        $isOnlyVerified = str()->contains($request->route()->getName(), 'verified');
        $tickets = ArrivalTicket::select('arrival_tickets.*', 'grn_numbers.unique_no as grn_unique_no')
            ->leftJoin('arrival_slips', 'arrival_tickets.id', '=', 'arrival_slips.arrival_ticket_id')
            ->leftJoin('grn_numbers', function ($join) {
                $join->on('arrival_slips.id', '=', 'grn_numbers.model_id')
                    ->where('grn_numbers.model_type', 'arrival-slip');
            })
            ->where('is_ticket_verified', '=', $isOnlyVerified ? 1 : 0)
            ->where(function ($query) {
                $query->where('arrival_tickets.freight_status', 'completed')
                    ->orWhere('arrival_tickets.first_qc_status', 'rejected');
            })
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
                        $query->where('id', $request->commodity_id);
                    })
                        ->orWhereHas('product', function ($query) use ($request) {
                            $query->where('id', $request->commodity_id);
                        });
                });
            })
            ->when($request->filled('miller_id'), function ($q) use ($request) {
                return $q->whereHas('miller', function ($query) use ($request) {
                    $query->where('id', $request->miller_id);
                });
            })
            ->when($request->filled('sauda_type_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.sauda_type_id', $request->sauda_type_id);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.location_id', $request->company_location_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                return $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                    ->whereDate('arrival_tickets.created_at', '<=', $endDate);
            })
            ->orderBy('arrival_tickets.created_at', 'desc')
            ->paginate(request('per_page', 25));

        return view('management.reports.arrival.arrival-history.getArrivalReport', compact('tickets'));
    }



}
