<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Product;
use App\Models\SaudaType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductWiseArrivalReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:product-arrival-report');
    }

    public function index()
    {
        $commodities = Product::all();
        $saudaTypes = SaudaType::all();

        return view('management.reports.arrival.product-wise.index', compact('commodities', 'saudaTypes'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $data = ArrivalTicket::select(
            'arrival_tickets.product_id',
            DB::raw('SUM(arrival_tickets.arrived_net_weight) as total_arrival_qty'),
            DB::raw('COUNT(arrival_tickets.id) as total_tickets')
        )
            ->with('product')
            ->whereIn('arrival_tickets.document_approval_status', ['fully_approved', 'half_approved'])
            ->where('arrival_tickets.second_weighbridge_status', 'completed')
            ->when($request->filled('commodity_id'), function ($q) use ($request) {
                return $q->whereIn('arrival_tickets.product_id', (array)$request->commodity_id);
            })
            ->when($request->filled('sauda_type_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.sauda_type_id', $request->sauda_type_id);
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
            ->groupBy('arrival_tickets.product_id')
            ->orderByDesc('total_arrival_qty')
            ->get();

        return view('management.reports.arrival.product-wise.getList', compact('data'));
    }
}
