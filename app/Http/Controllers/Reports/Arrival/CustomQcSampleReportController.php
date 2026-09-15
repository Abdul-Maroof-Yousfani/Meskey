<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlabType;
use App\Models\Master\Station;
use App\Models\Master\Supplier;
use App\Models\Product;
use App\Models\PurchaseTicket;
use Illuminate\Http\Request;

class CustomQcSampleReportController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $suppliers = Supplier::all();
        $stations = Station::all();
        $product_slab_types = ProductSlabType::get();
        $arrival_compulsory_qc_params = ArrivalCompulsoryQcParam::get();
        $locations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        return view('management.reports.arrival.custom-qc-sample.index', compact(
            'commodities',
            'suppliers',
            'stations',
            'locations',
            'product_slab_types',
            'arrival_compulsory_qc_params'
        ));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $product_slab_types = ProductSlabType::get();
        $arrival_compulsory_qc_params = ArrivalCompulsoryQcParam::get();

        $tickets = PurchaseTicket::select('purchase_tickets.*')
            ->with([
                'product',
                'qcProduct',
                'purchaseOrder.supplier',
                'purchaseOrder.location',
                'purchaseOrder.decisionOfUser',
                'purchaseFreight.station',
                'lastInitialSampling' => function ($q) {
                    $q->with([
                        'takenByUser',
                        'doneByUser',
                        'approvedByUser',
                        'slabResults.slabType',
                        'compulsoryResults.qcParam'
                    ]);
                },
                'purchaseSamplingRequests' => function ($q) {
                    $q->with([
                        'takenByUser',
                        'doneByUser',
                        'approvedByUser',
                        'slabResults.slabType',
                        'compulsoryResults.qcParam'
                    ])->latest();
                }
            ])
            ->when($request->filled('ticket_no'), function ($q) use ($request) {
                return $q->where('purchase_tickets.unique_no', 'like', '%' . $request->ticket_no . '%');
            })
            ->when($request->filled('commodity_id'), function ($q) use ($request) {
                return $q->where(function ($subQuery) use ($request) {
                    $subQuery->whereIn('purchase_tickets.product_id', (array)$request->commodity_id)
                        ->orWhereIn('purchase_tickets.qc_product', (array)$request->commodity_id);
                });
            })
            ->when($request->filled('supplier_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($sq) use ($request) {
                    $sq->where('supplier_id', $request->supplier_id);
                });
            })
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseOrder', function ($sq) use ($request) {
                    $sq->whereIn('company_location_id', (array)$request->company_location_id);
                });
            })
            ->when($request->filled('station_id'), function ($q) use ($request) {
                return $q->whereHas('purchaseFreight', function ($sq) use ($request) {
                    $sq->where('station_id', $request->station_id);
                });
            })
            ->when($request->filled('daterange'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                if (count($dates) == 2) {
                    $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                    $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                    return $q->whereDate('purchase_tickets.created_at', '>=', $startDate)
                        ->whereDate('purchase_tickets.created_at', '<=', $endDate);
                }
            })
            ->when(auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->where(function ($sq) {
                    $sq->whereHas('purchaseOrder', function ($poQuery) {
                        $poQuery->whereIn('company_location_id', getUserCurrentCompanyLocations());
                    })->orWhereNull('purchase_order_id');
                });
            })
            ->orderBy('purchase_tickets.created_at', 'desc')
            ->get();

        return view('management.reports.arrival.custom-qc-sample.getCustomQcSample', compact(
            'tickets',
            'product_slab_types',
            'arrival_compulsory_qc_params'
        ));
    }
}
