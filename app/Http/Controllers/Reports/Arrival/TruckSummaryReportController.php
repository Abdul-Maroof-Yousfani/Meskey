<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Miller;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\SaudaType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TruckSummaryReportController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $millers = Miller::all();
        $suppliers = Supplier::all();
        $saudaTypes = SaudaType::all();
        $locations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        return view('management.reports.arrival.truck-summary.index', compact(
            'commodities',
            'millers',
            'suppliers',
            'saudaTypes',
            'locations'
        ));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $data = ArrivalTicket::select(
            DB::raw('DATE(arrival_tickets.created_at) as summary_date'),
            DB::raw('COUNT(arrival_tickets.id) as truck_arrived'),
            DB::raw("COUNT(CASE WHEN arrival_tickets.arrival_slip_status = 'generated' AND arrival_tickets.document_approval_status = 'fully_approved' THEN 1 END) as fully_approved"),
            DB::raw("COUNT(CASE WHEN arrival_tickets.arrival_slip_status = 'generated' THEN 1 END) as total_unloaded"),
            DB::raw("COUNT(CASE WHEN arrival_tickets.document_approval_status = 'half_approved' THEN 1 END) as half_rejected"),
            DB::raw("COUNT(CASE WHEN arrival_tickets.first_qc_status = 'rejected' OR arrival_tickets.status = 'Reject Full' OR arrival_tickets.status = 'rejected' THEN 1 END) as full_rejected"),

            DB::raw("COUNT(CASE WHEN (arrival_tickets.arrival_slip_status != 'generated' OR arrival_tickets.arrival_slip_status IS NULL) AND
                (arrival_tickets.document_approval_status = 'pending' OR arrival_tickets.document_approval_status IS NULL) AND
                (arrival_tickets.first_qc_status != 'rejected') THEN 1 END) as in_process")
        )
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
                    $startDate = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                    $endDate = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                    return $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                        ->whereDate('arrival_tickets.created_at', '<=', $endDate);
                }
            })
            ->when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations());
            })
            ->groupBy(DB::raw('DATE(arrival_tickets.created_at)'))
            ->orderBy(DB::raw('DATE(arrival_tickets.created_at)'), 'desc')
            ->get();

        return view('management.reports.arrival.truck-summary.getTruckSummary', compact('data'));
    }
}
