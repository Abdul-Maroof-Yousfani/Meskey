<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\BagType;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\SaudaType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BagWiseArrivalReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:bag-arrival-report');
    }

    public function index()
    {
        $bagTypes = BagType::all();
        $commodities = Product::all();
        $saudaTypes = SaudaType::all();
        $suppliers = Supplier::all();
        $locations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        return view('management.reports.arrival.bag-wise.index', compact('bagTypes', 'commodities', 'saudaTypes', 'suppliers', 'locations'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $data = ArrivalTicket::join('arrival_approves', 'arrival_tickets.id', '=', 'arrival_approves.arrival_ticket_id')
            ->leftJoin('bag_types', 'arrival_approves.bag_type_id', '=', 'bag_types.id')
            ->select(
                'arrival_approves.bag_type_id',
                DB::raw('COALESCE(bag_types.name, "N/A") as bag_name'),
                DB::raw('COUNT(DISTINCT arrival_tickets.id) as total_tickets'),
                DB::raw('SUM(COALESCE(CAST(arrival_approves.filling_bags_no AS UNSIGNED), 0)) as total_filled_bags')
            )
            ->whereIn('arrival_tickets.document_approval_status', ['fully_approved', 'half_approved'])
            ->where('arrival_tickets.second_weighbridge_status', 'completed')
            ->when($request->filled('bag_type_id'), function ($q) use ($request) {
                return $q->whereIn('arrival_approves.bag_type_id', (array)$request->bag_type_id);
            })
            ->when($request->filled('commodity_id'), function ($q) use ($request) {
                return $q->whereIn('arrival_tickets.product_id', (array)$request->commodity_id);
            })
            ->when($request->filled('sauda_type_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.sauda_type_id', $request->sauda_type_id);
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.accounts_of_id', $request->supplier_id);
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereIn('arrival_tickets.location_id', (array)$request->company_location_id);
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
            ->groupBy('arrival_approves.bag_type_id', 'bag_types.name')
            ->orderByDesc('total_filled_bags')
            ->get();

        return view('management.reports.arrival.bag-wise.getList', compact('data'));
    }
}
