<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\CompanyLocation;
use App\Models\Master\Miller;
use App\Models\Master\ProductSlabType;
use App\Models\Master\Station;
use App\Models\Product;
use Illuminate\Http\Request;

class StationWiseQCAnalysisReportController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $millers = Miller::all();
        $stations = Station::all();
        $product_slab_types = ProductSlabType::get();
        $locations = CompanyLocation::when(auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        return view('management.reports.arrival.station-wise-qc-analysis.index', compact('commodities', 'millers', 'stations', 'locations', 'product_slab_types'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $product_slab_types = ProductSlabType::get();

        $tickets = ArrivalTicket::select('arrival_tickets.*')
            ->with([
                'station',
                'qcProduct',
                'product',
                'location',
                'miller',
                'saudaType',
                'accountsOf',
                'lastInitialSampling' => function ($q) {
                    $q->with([
                        'slabResults.slabType'
                    ]);
                },
            ])
            ->when($request->filled('station_id'), function ($q) use ($request) {
                return $q->where('arrival_tickets.station_id', $request->station_id);
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

        // Group tickets by station
        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->station?->name ?? ($ticket->station_name ?? 'Unknown Station');
        })->sortKeys();

        $stationData = [];
        $overallSlabPieceQty = [];
        $overallSlabQty = [];
        foreach ($product_slab_types as $slab) {
            $overallSlabPieceQty[$slab->id] = 0;
            $overallSlabQty[$slab->id] = 0;
        }

        foreach ($grouped as $stationName => $stationTickets) {
            $totalTrucks = $stationTickets->count();
            $kgReceived = $stationTickets->sum(function ($t) {
                return (float) ($t->arrived_net_weight ?: ($t->net_weight ?: (($t->first_weight && $t->second_weight) ? ($t->first_weight - $t->second_weight) : ($t->loading_weight ?: 0))));
            });

            // Calculate for each slab type across this station's tickets:
            // Piece Quantity = Slab Checklist Value * Ticket Quantity
            // Station Slab Value = Total Piece Quantity / Total Quantity
            $slabAverages = [];
            foreach ($product_slab_types as $slab) {
                $totalPieceQty = 0;
                $totalQty = 0;

                foreach ($stationTickets as $t) {
                    $qty = (float) ($t->arrived_net_weight ?: ($t->net_weight ?: (($t->first_weight && $t->second_weight) ? ($t->first_weight - $t->second_weight) : ($t->loading_weight ?: ($t->bags ?: 0)))));

                    if ($t->lastInitialSampling && $t->lastInitialSampling->slabResults) {
                        foreach ($t->lastInitialSampling->slabResults as $res) {
                            if ($res->product_slab_type_id == $slab->id && $res->checklist_value !== null && $res->checklist_value !== '') {
                                $slabValue = (float) $res->checklist_value;
                                $effectiveQty = $qty > 0 ? $qty : 1;

                                $pieceQuantity = $slabValue * $effectiveQty;
                                $totalPieceQty += $pieceQuantity;
                                $totalQty += $effectiveQty;

                                $overallSlabPieceQty[$slab->id] += $pieceQuantity;
                                $overallSlabQty[$slab->id] += $effectiveQty;
                            }
                        }
                    }
                }

                // Station slab value = Total Piece Qty / Total Qty
                $stationSlabValue = $totalQty > 0 ? ($totalPieceQty / $totalQty) : 0;
                $slabAverages[$slab->id] = round($stationSlabValue, 1);
            }

            $stationData[] = [
                'station' => $stationName,
                'total_trucks' => $totalTrucks,
                'kg_received' => $kgReceived,
                'slab_averages' => $slabAverages,
            ];
        }

        // Main Totals: Overall Piece Quantity / Overall Quantity
        $overallSlabAverages = [];
        foreach ($product_slab_types as $slab) {
            $overallSlabAverages[$slab->id] = $overallSlabQty[$slab->id] > 0
                ? round($overallSlabPieceQty[$slab->id] / $overallSlabQty[$slab->id], 1)
                : 0;
        }

        // $grouped = $tickets->groupBy(function ($ticket) {
        //     return $ticket->station?->name ?? ($ticket->station_name ?? 'Unknown Station');
        // })->sortKeys();

        // $stationData = [];
        // foreach ($grouped as $stationName => $stationTickets) {
        //     $totalTrucks = $stationTickets->count();
        //     $kgReceived = $stationTickets->sum(function ($t) {
        //         return (float) ($t->arrived_net_weight ?: ($t->net_weight ?: (($t->first_weight && $t->second_weight) ? ($t->first_weight - $t->second_weight) : 0)));
        //     });

        //     // Calculate average for each slab type
        //     $slabAverages = [];
        //     foreach ($product_slab_types as $slab) {
        //         $values = [];
        //         foreach ($stationTickets as $t) {
        //             if ($t->lastInitialSampling && $t->lastInitialSampling->slabResults) {
        //                 foreach ($t->lastInitialSampling->slabResults as $res) {
        //                     if ($res->product_slab_type_id == $slab->id && $res->checklist_value !== null && $res->checklist_value !== '') {
        //                         $values[] = (float) $res->checklist_value;
        //                     }
        //                 }
        //             }
        //         }
        //         $avg = count($values) > 0 ? (array_sum($values) / count($values)) : 0;
        //         $slabAverages[$slab->id] = round($avg, 1);
        //     }

        //     $stationData[] = [
        //         'station' => $stationName,
        //         'total_trucks' => $totalTrucks,
        //         'kg_received' => $kgReceived,
        //         'slab_averages' => $slabAverages,
        //     ];
        // }
        return view('management.reports.arrival.station-wise-qc-analysis.getStationWiseQCAnalysis', compact('stationData', 'product_slab_types', 'overallSlabAverages'));
    }
}
