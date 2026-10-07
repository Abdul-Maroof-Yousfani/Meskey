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

class WeightDifferenceController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $suppliers = Supplier::all();
        $stations = Station::all();
        $product_slab_types = ProductSlabType::getForArrivalReport(null, null, 'purchase');
        $arrival_compulsory_qc_params = ArrivalCompulsoryQcParam::get();
        $warehouses = ArrivalSubLocation::get();
        return view('management.reports.arrival.weight-difference.index', compact('commodities', 'suppliers', 'stations', 'product_slab_types', 'arrival_compulsory_qc_params', 'warehouses'));
    }

    public function getList(Request $request)
    {
        $arrival_data = ArrivalTicket::select(
            'qc_product',
            DB::raw('SUM(net_weight) as total_net_weight'),
            DB::raw('SUM(arrived_net_weight) as total_loading_weight')
        )
        ->where('qc_product', "!=", NULL)
        ->when($request->station_id, function ($q) use ($request) {
            return $q->where('station_id', $request->station_id);
        })
        ->when($request->commodity_id, function ($q) use ($request) {
            return $q->whereIn('qc_product', $request->commodity_id);
        })
        ->when($request->status_id, function ($q) use ($request) {
            if ($request->status_id == "fully_approved") {
                return $q->where('document_approval_status', 'fully_approved');
            } elseif ($request->status_id == "half_approved") {
                return $q->where('document_approval_status', 'half_approved');
            } elseif ($request->status_id == "rejected" || $request->status_id == "") {
                return $q->where('document_approval_status', NULL);
            }
        })
        ->when($request->warehouse_id, function ($q) use ($request) {
            return $q->whereHas('approvals', function ($sq) use ($request) {
                $sq->whereIn('gala_id', $request->warehouse_id);
            });
        })
        ->when($request->daterange, function ($q) use ($request) {
            $dates = explode(' - ', $request->daterange);
            if (count($dates) == 2) {
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                return $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            }
        })
        ->groupBy('qc_product')
        ->get();

        return view('management.reports.arrival.weight-difference.get_weight_diff', compact('arrival_data'));
    }
}
