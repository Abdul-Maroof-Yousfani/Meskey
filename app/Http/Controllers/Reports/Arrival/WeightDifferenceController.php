<?php

namespace App\Http\Controllers\Reports\Arrival;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlabType;
use App\Models\Master\Station;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\Arrival\ArrivalTicket;
use Illuminate\Support\Facades\DB;
use App\Models\Master\ArrivalSubLocation;
use App\Services\ArrivalDashboardService;
use Carbon\Carbon;

class WeightDifferenceController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $suppliers = Supplier::all();
        $stations = Station::all();
        $product_slab_types = ProductSlabType::getForArrivalReport(null, null, 'purchase');
        $arrival_compulsory_qc_params = ArrivalCompulsoryQcParam::get();
        $warehouses = ArrivalSubLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('company_location_id', getUserCurrentCompanyLocations());
        })->get();
        return view('management.reports.arrival.weight-difference.index', compact('commodities', 'suppliers', 'stations', 'product_slab_types', 'arrival_compulsory_qc_params', 'warehouses'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $authUser = auth()->user();
        $isSuperAdmin = $authUser && $authUser->user_type === 'super-admin';
        $isOutSourcePurchaser = false;
        $decisionUserIds = [];
        if (!$isSuperAdmin && $authUser) {
            [$isOutSourcePurchaser, $decisionUserIds] = app(ArrivalDashboardService::class)
                ->resolveOutSourcePurchaserContext($authUser, $authUser->current_company_id);
        }

        $arrival_data = ArrivalTicket::select(
            'arrival_tickets.qc_product',
            DB::raw('SUM(arrival_tickets.net_weight) as total_net_weight'),
            DB::raw('SUM(arrival_tickets.arrived_net_weight) as total_loading_weight')
        )
        ->with('qcProduct')
        ->whereNotNull('arrival_tickets.qc_product')
        ->when($request->station_id, function ($q) use ($request) {
            return $q->where('arrival_tickets.station_id', $request->station_id);
        })
        ->when($request->commodity_id, function ($q) use ($request) {
            return $q->whereIn('arrival_tickets.qc_product', (array)$request->commodity_id);
        })
        ->when($request->status_id, function ($q) use ($request) {
            if ($request->status_id == "fully_approved") {
                return $q->where('arrival_tickets.document_approval_status', 'fully_approved');
            } elseif ($request->status_id == "half_approved") {
                return $q->where('arrival_tickets.document_approval_status', 'half_approved');
            } elseif ($request->status_id == "rejected" || $request->status_id == "") {
                return $q->whereNull('arrival_tickets.document_approval_status');
            }
        })
        ->when($request->warehouse_id, function ($q) use ($request) {
            return $q->whereHas('approvals', function ($sq) use ($request) {
                $sq->whereIn('gala_id', (array)$request->warehouse_id);
            });
        })
        ->when($request->daterange, function ($q) use ($request) {
            $dates = explode(' - ', $request->daterange);
            if (count($dates) == 2) {
                $startDate = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                return $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                    ->whereDate('arrival_tickets.created_at', '<=', $endDate);
            }
        })
        ->where('arrival_tickets.first_qc_status', "!=", "rejected")
        ->when($isOutSourcePurchaser, function ($q) use ($decisionUserIds) {
            return $q->whereIn('arrival_tickets.decision_id', $decisionUserIds);
        })
        ->when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations());
        })
        ->groupBy('arrival_tickets.qc_product')
        ->get();

        return view('management.reports.arrival.weight-difference.get_weight_diff', compact('arrival_data'));
    }
}
