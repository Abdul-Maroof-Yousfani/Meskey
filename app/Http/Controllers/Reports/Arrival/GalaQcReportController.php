<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlabType;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GalaQcReportController extends Controller
{
    public function index()
    {
        $commodities = Product::where('status', 'active')->orWhereNull('status')->orderBy('name')->get();
        if ($commodities->isEmpty()) {
            $commodities = Product::orderBy('name')->get();
        }

        $locations = CompanyLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        $product_slab_types = ProductSlabType::getForArrivalReport();

        // Get distinct years from arrival tickets, with fallback to current & previous years
        $dbYears = ArrivalTicket::selectRaw('DISTINCT YEAR(created_at) as yr')
            ->whereNotNull('created_at')
            ->orderBy('yr', 'desc')
            ->pluck('yr')
            ->filter()
            ->toArray();

        $currentYear = (int) Carbon::now()->year;
        $years = array_unique(array_merge($dbYears, [$currentYear, $currentYear - 1, $currentYear - 2]));
        rsort($years);

        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return view('management.reports.arrival.gala-qc.index', compact('commodities', 'locations', 'years', 'months', 'product_slab_types'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $query = ArrivalTicket::select('arrival_tickets.*')
            ->with([
                'approvals.gala',
                'latestInnerSampling.slabResults.slabType',
                'lastInitialSampling.slabResults.slabType',
                'qcProduct',
                'product',
            ])
            // Only tickets that have Gala assigned
            ->whereHas('approvals', function ($q) {
                $q->whereNotNull('gala_id')->orWhereNotNull('gala_name');
            })
            // Commodity filter
            ->when($request->filled('commodity_id'), function ($q) use ($request) {
                $commodityIds = array_filter((array) $request->commodity_id);
                if (!empty($commodityIds) && !in_array('all', $commodityIds)) {
                    $q->where(function ($sub) use ($commodityIds) {
                        $sub->whereIn('arrival_tickets.product_id', $commodityIds)
                            ->orWhereIn('arrival_tickets.qc_product', $commodityIds);
                    });
                }
            })
            // Location filter
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                $locationIds = array_filter((array) $request->company_location_id);
                if (!empty($locationIds)) {
                    $q->whereIn('arrival_tickets.location_id', $locationIds);
                }
            })
            // Year filter
            ->when($request->filled('year') && $request->year !== 'all', function ($q) use ($request) {
                $q->whereYear('arrival_tickets.created_at', $request->year);
            })
            // Month filter
            ->when($request->filled('month') && $request->month !== 'all', function ($q) use ($request) {
                $q->whereMonth('arrival_tickets.created_at', $request->month);
            })
            // Date Range filter
            ->when($request->filled('daterange') && (!$request->filled('year') || $request->year === 'all') && (!$request->filled('month') || $request->month === 'all'), function ($q) use ($request) {
                $dates = explode(' - ', $request->daterange);
                if (count($dates) == 2) {
                    $startDate = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->format('Y-m-d');
                    $endDate = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->format('Y-m-d');
                    $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                        ->whereDate('arrival_tickets.created_at', '<=', $endDate);
                }
            })
            // Status filter
            ->when($request->filled('status_id') && $request->status_id !== 'all', function ($q) use ($request) {
                if ($request->status_id === 'fully_approved') {
                    $q->where('arrival_tickets.document_approval_status', 'fully_approved');
                } elseif ($request->status_id === 'half_approved') {
                    $q->where('arrival_tickets.document_approval_status', 'half_approved');
                } elseif ($request->status_id === 'rejected') {
                    $q->where(function ($sq) {
                        $sq->where('arrival_tickets.first_qc_status', 'rejected')
                            ->orWhere('arrival_tickets.status', 'Reject Full')
                            ->orWhere('arrival_tickets.status', 'rejected');
                    });
                }
            })
            // User location restriction
            ->when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
                $q->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations());
            });

        $tickets = $query->orderBy('arrival_tickets.created_at', 'asc')->get();

        // Get dynamic ProductSlabTypes
        $commodityIds = $tickets->pluck('qc_product')
            ->merge($tickets->pluck('product_id'))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($commodityIds) && $request->filled('commodity_id')) {
            $commodityIds = array_filter((array) $request->commodity_id);
            $commodityIds = array_values(array_diff($commodityIds, ['all']));
        }

        $product_slab_types = ProductSlabType::getForCommodities($commodityIds);
        if ($product_slab_types->isEmpty()) {
            $product_slab_types = ProductSlabType::getForArrivalReport($request->company_location_id, $commodityIds);
            if ($product_slab_types->isEmpty()) {
                $product_slab_types = ProductSlabType::ordered()->get();
            }
        }

        // Group tickets by Gala
        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->approvals?->gala?->name ?? ($ticket->approvals?->gala_name ?? 'Unknown Gala');
        })->sortKeys();

        $galaData = [];
        $totalNetWeight = (float) $tickets->sum(function ($t) {
            return (float) ($t->arrived_net_weight ?: 0);
        });

        foreach ($grouped as $galaName => $items) {
            // Net Weight strictly from arrived_net_weight as requested
            $netWeight = (float) $items->sum(function ($t) {
                return (float) ($t->arrived_net_weight ?: 0);
            });

            $slabAverages = [];
            foreach ($product_slab_types as $slab) {
                $weightedValues = [];
                $testedWeights = [];
                $rawValues = [];

                foreach ($items as $t) {
                    $tWeight = (float) ($t->arrived_net_weight ?: 0);
                    $sampling = $t->latestInnerSampling ?: $t->lastInitialSampling;
                    if ($sampling && $sampling->slabResults) {
                        foreach ($sampling->slabResults as $sr) {
                            if ($sr->product_slab_type_id == $slab->id && $sr->checklist_value !== null && $sr->checklist_value !== '') {
                                $val = (float) $sr->checklist_value;
                                if ($val > 0) {
                                    $rawValues[] = $val;
                                    $weightedValues[] = $val * $tWeight;
                                    $testedWeights[] = $tWeight;
                                }
                                break;
                            }
                        }
                    }
                }

                $sumWeight = array_sum($testedWeights);
                if ($sumWeight > 0) {
                    $slabAverages[$slab->id] = array_sum($weightedValues) / $sumWeight;
                } elseif (count($rawValues) > 0) {
                    $slabAverages[$slab->id] = array_sum($rawValues) / count($rawValues);
                } else {
                    $slabAverages[$slab->id] = 0;
                }
            }

            $galaData[] = [
                'gala_name' => $galaName,
                'net_weight' => $netWeight,
                'slab_averages' => $slabAverages,
                'count_tickets' => $items->count(),
            ];
        }

        // Sort rows by Net Weight descending
        usort($galaData, function ($a, $b) {
            return $b['net_weight'] <=> $a['net_weight'];
        });

        // Calculate Overall Totals for all slabs
        $overallSlabAverages = [];
        foreach ($product_slab_types as $slab) {
            $allWeightedValues = [];
            $allTestedWeights = [];
            $allRawValues = [];

            foreach ($tickets as $t) {
                $tWeight = (float) ($t->arrived_net_weight ?: 0);
                $sampling = $t->latestInnerSampling ?: $t->lastInitialSampling;
                if ($sampling && $sampling->slabResults) {
                    foreach ($sampling->slabResults as $sr) {
                        if ($sr->product_slab_type_id == $slab->id && $sr->checklist_value !== null && $sr->checklist_value !== '') {
                            $val = (float) $sr->checklist_value;
                            if ($val > 0) {
                                $allRawValues[] = $val;
                                $allWeightedValues[] = $val * $tWeight;
                                $allTestedWeights[] = $tWeight;
                            }
                            break;
                        }
                    }
                }
            }

            $sumWeight = array_sum($allTestedWeights);
            if ($sumWeight > 0) {
                $overallSlabAverages[$slab->id] = array_sum($allWeightedValues) / $sumWeight;
            } elseif (count($allRawValues) > 0) {
                $overallSlabAverages[$slab->id] = array_sum($allRawValues) / count($allRawValues);
            } else {
                $overallSlabAverages[$slab->id] = 0;
            }
        }

        return view('management.reports.arrival.gala-qc.get_gala_qc', compact(
            'galaData',
            'product_slab_types',
            'totalNetWeight',
            'overallSlabAverages'
        ));
    }
}
