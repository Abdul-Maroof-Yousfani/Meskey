<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\ArrivalCompulsoryQcParam;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Miller;
use App\Models\Master\ProductSlabType;
use App\Models\Product;
use Illuminate\Http\Request;

class QcAnalysisReportController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $millers = Miller::all();
        $product_slab_types = ProductSlabType::get();
        $arrival_compulsory_qc_params = ArrivalCompulsoryQcParam::get();
        $locations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        return view('management.reports.arrival.qc-analysis.index', compact('commodities', 'millers', 'locations', 'product_slab_types', 'arrival_compulsory_qc_params'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $product_slab_types = ProductSlabType::get();
        $arrival_compulsory_qc_params = ArrivalCompulsoryQcParam::get();

        $tickets = ArrivalTicket::select('arrival_tickets.*')
            ->with([
                'creator',
                'qcProduct',
                'product',
                'location',
                'miller',
                'saudaType',
                'accountsOf',
                'unloadingLocation',
                'arrivalSamplingRequests',
                'lastInitialSampling' => function ($q) {
                    $q->with([
                        'takenByUser',
                        'approvedByUser',
                        'doneByUser',
                        'slabResults.slabType',
                        'compulsoryResults.qcParam'
                    ]);
                },
            ])
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

        return view('management.reports.arrival.qc-analysis.getQcAnalysis', compact('tickets', 'product_slab_types', 'arrival_compulsory_qc_params'));
    }
}
