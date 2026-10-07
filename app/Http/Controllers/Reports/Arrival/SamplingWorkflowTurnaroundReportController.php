<?php

namespace App\Http\Controllers\Reports\Arrival;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Master\Station;
use App\Models\Master\ArrivalSubLocation;
use App\Models\Product;
use App\Models\Arrival\ArrivalTicket;

class SamplingWorkflowTurnaroundReportController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $stations = Station::all();
        $warehouses = ArrivalSubLocation::get();

        return view('management.reports.arrival.sampling-workflow-turnaround.index', compact('commodities', 'stations', 'warehouses'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $tickets = ArrivalTicket::select('arrival_tickets.*')
            ->with([
                'creator',
                'decisionBy',
                'firstWeighbridge',
                'secondWeighbridge',
                'unloadingLocation',
                'arrivalSlip',
                'approvals',
                'arrivalSamplingRequests' => function ($q) {
                    $q->orderBy('id', 'asc');
                },
                'lastInitialSampling',
            ])
            ->when($request->filled('station_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.station_id', $request->station_id);
            })
            ->when($request->filled('commodity_id') && !in_array('all', (array)$request->commodity_id), function ($q) use ($request) {
                return $q->where(function ($subQuery) use ($request) {
                    $subQuery->whereIn('arrival_tickets.qc_product', (array)$request->commodity_id)
                        ->orWhereIn('arrival_tickets.product_id', (array)$request->commodity_id);
                });
            })
            ->when($request->filled('status_id'), function ($q) use ($request) {
                if ($request->status_id == 'half_approved') {
                    return $q->where(function ($sq) {
                        $sq->where('arrival_tickets.document_approval_status', 'half_approved')
                            ->orWhere('arrival_tickets.status', 'Reject Half')
                            ->orWhereHas('approvals', function ($aq) {
                                $aq->where('bag_packing_approval', 'Half Approved')
                                    ->orWhere('total_rejection', '>', 0);
                            });
                    });
                } elseif ($request->status_id == 'rejected') {
                    return $q->where(function ($sq) {
                        $sq->where('arrival_tickets.first_qc_status', 'rejected')
                            ->orWhere('arrival_tickets.status', 'Reject Full');
                    });
                } elseif ($request->status_id == 'fully_approved') {
                    return $q->where(function ($sq) {
                        $sq->where('arrival_tickets.document_approval_status', 'fully_approved')
                            ->orWhere('arrival_tickets.first_qc_status', 'approved')
                            ->orWhere('arrival_tickets.status', 'completed');
                    })->where('arrival_tickets.first_qc_status', '!=', 'rejected')
                      ->where('arrival_tickets.status', '!=', 'Reject Full')
                      ->where('arrival_tickets.status', '!=', 'Reject Half');
                }
            })
            ->when($request->filled('warehouse_id'), function ($q) use ($request) {
                return $q->whereHas('approvals', function ($sq) use ($request) {
                    $sq->whereIn('gala_id', (array)$request->warehouse_id);
                });
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
            ->when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations());
            })
            ->orderBy('arrival_tickets.created_at', 'asc')
            ->get();

        return view('management.reports.arrival.sampling-workflow-turnaround.get_sampling_workflow_turnaround', compact('tickets'));
    }
}
