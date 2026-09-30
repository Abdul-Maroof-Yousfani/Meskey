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
        $locations = CompanyLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
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

        $dateFilter = $request->input('date_filter', 'last_7_days');
        $startDate = null;
        $endDate = null;
        $dateLabel = '';

        if ($dateFilter === 'today') {
            $startDate = Carbon::today()->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
            $dateLabel = 'Today (' . Carbon::today()->format('d M Y') . ')';
        } elseif ($dateFilter === 'last_7_days') {
            $startDate = Carbon::today()->subDays(6)->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
            $dateLabel = 'Last 7 Days (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')';
        } elseif ($dateFilter === 'last_30_days') {
            $startDate = Carbon::today()->subDays(29)->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
            $dateLabel = 'Last 30 Days (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')';
        } elseif ($dateFilter === 'custom' || $request->filled('daterange')) {
            if ($request->filled('daterange')) {
                $dates = explode(' - ', $request->daterange);
                if (count($dates) == 2) {
                    try {
                        $startDate = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                        $endDate = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                    } catch (\Exception $e) {
                        try {
                            $startDate = Carbon::parse(trim($dates[0]))->format('Y-m-d');
                            $endDate = Carbon::parse(trim($dates[1]))->format('Y-m-d');
                        } catch (\Exception $e2) {
                            $startDate = Carbon::today()->subDays(29)->format('Y-m-d');
                            $endDate = Carbon::today()->format('Y-m-d');
                        }
                    }
                }
            }
            if (!$startDate) {
                $startDate = Carbon::today()->subDays(29)->format('Y-m-d');
                $endDate = Carbon::today()->format('Y-m-d');
            }
            $dateLabel = ($startDate === $endDate)
                ? Carbon::parse($startDate)->format('d M Y')
                : Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');
        } elseif ($dateFilter === 'all') {
            $startDate = null;
            $endDate = null;
            $dateLabel = 'All Dates';
        } else {
            $startDate = Carbon::today()->subDays(6)->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
            $dateLabel = 'Last 7 Days (' . Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y') . ')';
        }

        $data = ArrivalTicket::leftJoin('products', DB::raw('COALESCE(arrival_tickets.qc_product, arrival_tickets.product_id)'), '=', 'products.id')
            ->select(
                DB::raw('COALESCE(products.name, "Unknown Commodity") as commodity_name'),
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
                $commodityIds = array_filter((array)$request->commodity_id);
                if (!empty($commodityIds)) {
                    return $q->whereIn(DB::raw('COALESCE(arrival_tickets.qc_product, arrival_tickets.product_id)'), $commodityIds);
                }
            })
            ->when($request->filled('miller_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.miller_id', $request->miller_id);
            })
            ->when($request->filled('sauda_type_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.sauda_type_id', $request->sauda_type_id);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                $locationIds = array_filter((array)$request->company_location_id);
                if (!empty($locationIds)) {
                    return $q->whereIn('arrival_tickets.location_id', $locationIds);
                }
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.accounts_of_id', $request->supplier_id);
            })
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                    ->whereDate('arrival_tickets.created_at', '<=', $endDate);
            })
            ->when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations());
            })
            ->groupBy(DB::raw('COALESCE(products.name, "Unknown Commodity")'))
            ->orderByDesc(DB::raw('COUNT(arrival_tickets.id)'))
            ->get();

        foreach ($data as $row) {
            $row->summary_date = $dateLabel;
        }

        return view('management.reports.arrival.truck-summary.getTruckSummary', compact('data', 'dateLabel'));
    }
}
