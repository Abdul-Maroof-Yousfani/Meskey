<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\Master\CompanyLocation;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ArrivalDashboardService;

class GalaQcReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.company:gala-qc-analysis-report');
    }
    public function index()
    {
        $commodities = Product::where('status', 'active')->orWhereNull('status')->orderBy('name')->get();
        if ($commodities->isEmpty()) {
            $commodities = Product::orderBy('name')->get();
        }

        $locations = CompanyLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        $product_slab_types = collect();

        return view('management.reports.arrival.gala-qc.index', compact('commodities', 'locations', 'product_slab_types'));
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

        $dateFilter = $request->input('date_filter', 'last_7_days');
        $startDate = null;
        $endDate = null;

        if ($dateFilter === 'today') {
            $startDate = Carbon::today()->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        } elseif ($dateFilter === 'last_7_days') {
            $startDate = Carbon::today()->subDays(6)->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        } elseif ($dateFilter === 'last_30_days') {
            $startDate = Carbon::today()->subDays(29)->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
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
        } elseif ($dateFilter === 'all') {
            $startDate = null;
            $endDate = null;
        } else {
            $startDate = Carbon::today()->subDays(6)->format('Y-m-d');
            $endDate = Carbon::today()->format('Y-m-d');
        }

        $query = ArrivalTicket::select('arrival_tickets.*')
            ->with([
                'approvals.gala',
                'innerSampling.slabResults.slabType',
                'latestInnerSampling.slabResults.slabType',
                'lastInitialSampling.slabResults.slabType',
                'initialSampling.slabResults.slabType',
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
            // Location filter (replacing Warehouse filter as instructed)
            ->when($request->filled('company_location_id'), function ($q) use ($request) {
                $locationIds = array_filter((array) $request->company_location_id);
                if (!empty($locationIds) && !in_array('all', $locationIds)) {
                    $q->whereIn('arrival_tickets.location_id', $locationIds);
                }
            })
            // Date filter
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereDate('arrival_tickets.created_at', '>=', $startDate)
                    ->whereDate('arrival_tickets.created_at', '<=', $endDate);
            })
            // Year filter fallback
            ->when($request->filled('year') && $request->year !== 'all', function ($q) use ($request) {
                $q->whereYear('arrival_tickets.created_at', $request->year);
            })
            // Month filter fallback
            ->when($request->filled('month') && $request->month !== 'all', function ($q) use ($request) {
                $q->whereMonth('arrival_tickets.created_at', $request->month);
            })
            // Status filter (Multiple items / single item support)
            ->when($request->filled('status_id'), function ($q) use ($request) {
                $statuses = array_filter((array) $request->status_id);
                if (!empty($statuses) && !in_array('all', $statuses)) {
                    $q->where(function ($sub) use ($statuses) {
                        $hasClause = false;
                        if (in_array('fully_approved', $statuses)) {
                            $sub->where('arrival_tickets.document_approval_status', 'fully_approved');
                            $hasClause = true;
                        }
                        if (in_array('half_approved', $statuses)) {
                            if ($hasClause) {
                                $sub->orWhere('arrival_tickets.document_approval_status', 'half_approved');
                            } else {
                                $sub->where('arrival_tickets.document_approval_status', 'half_approved');
                                $hasClause = true;
                            }
                        }
                        if (in_array('rejected', $statuses)) {
                            $rejCallback = function ($sq) {
                                $sq->where('arrival_tickets.first_qc_status', 'rejected')
                                    ->orWhere('arrival_tickets.status', 'Reject Full')
                                    ->orWhere('arrival_tickets.status', 'rejected');
                            };
                            if ($hasClause) {
                                $sub->orWhere($rejCallback);
                            } else {
                                $sub->where($rejCallback);
                            }
                        }
                    });
                }
            })
            ->when($isOutSourcePurchaser, function ($q) use ($decisionUserIds) {
                return $q->whereIn('arrival_tickets.decision_id', $decisionUserIds);
            })
            // User location restriction
            ->when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
                $q->whereIn('arrival_tickets.location_id', getUserCurrentCompanyLocations());
            });

        $tickets = $query->orderBy('arrival_tickets.created_at', 'asc')->get();

        // Helper to extract the 6 QC parameters for a ticket
        $extractQcValues = function ($ticket) {
            $innerSampling = $ticket->innerSampling ?: $ticket->latestInnerSampling;
            $initialSampling = $ticket->lastInitialSampling ?: $ticket->initialSampling;

            $findSlab = function ($sampling, $slabIds, $nameKeywords, $excludeKeywords = []) {
                if (!$sampling || !$sampling->slabResults) {
                    return null;
                }
                foreach ($sampling->slabResults as $sr) {
                    if (!$sr->slabType) {
                        continue;
                    }
                    $name = strtolower(trim($sr->slabType->name));
                    $id = (int) $sr->product_slab_type_id;

                    $excluded = false;
                    foreach ($excludeKeywords as $ex) {
                        if (stripos($name, strtolower($ex)) !== false) {
                            $excluded = true;
                            break;
                        }
                    }
                    if ($excluded) {
                        continue;
                    }

                    if (in_array($id, (array) $slabIds)) {
                        $val = $sr->checklist_value;
                        if ($val !== null && $val !== '' && is_numeric($val)) {
                            return (float) $val;
                        }
                    }

                    foreach ($nameKeywords as $kw) {
                        if (stripos($name, strtolower($kw)) !== false) {
                            $val = $sr->checklist_value;
                            if ($val !== null && $val !== '' && is_numeric($val)) {
                                return (float) $val;
                            }
                        }
                    }
                }
                return null;
            };

            // 1. Inner Broken (Slab 1, priority inner, fallback initial, exclude B3)
            $innerBroken = $findSlab($innerSampling, [1], ['broken'], ['broken b3', 'b3'])
                ?? $findSlab($initialSampling, [1], ['broken'], ['broken b3', 'b3']);

            // 2. Inner Moisture (Slab 2, priority inner, fallback initial)
            $innerMoisture = $findSlab($innerSampling, [2], ['moisture'])
                ?? $findSlab($initialSampling, [2], ['moisture']);

            // 3. Damage Creamish / grey / Tip (Slab 4, exclude B2, grain, water)
            $damageCreamish = $findSlab($innerSampling, [4], ['damage'], ['damage b2', 'b2', 'grain', 'water'])
                ?? $findSlab($initialSampling, [4], ['damage'], ['damage b2', 'b2', 'grain', 'water']);

            // 4. Damage Yellow (Slab 10 'Damage B2', or 'yellow')
            $damageYellow = $findSlab($innerSampling, [10], ['damage b2', 'yellow'])
                ?? $findSlab($initialSampling, [10], ['damage b2', 'yellow']);

            // 5. Under Milled (Slab 5)
            $underMilled = $findSlab($innerSampling, [5], ['under milled', 'undermilled'])
                ?? $findSlab($initialSampling, [5], ['under milled', 'undermilled']);

            // 6. Chalky (Slab 3)
            $chalky = $findSlab($innerSampling, [3], ['chalky'])
                ?? $findSlab($initialSampling, [3], ['chalky']);

            return [
                'inner_broken' => $innerBroken,
                'inner_moisture' => $innerMoisture,
                'damage_creamish' => $damageCreamish,
                'damage_yellow' => $damageYellow,
                'under_milled' => $underMilled,
                'chalky' => $chalky,
            ];
        };

        // Cache QC values and arrived net weight on each ticket
        foreach ($tickets as $t) {
            $t->extracted_qc = $extractQcValues($t);
            $t->calculated_weight = (float) ($t->arrived_net_weight ?: ($t->net_weight ?: 0));
        }

        // Group tickets by Gala
        $grouped = $tickets->groupBy(function ($ticket) {
            return $ticket->approvals?->gala?->name ?? ($ticket->approvals?->gala_name ?? 'Unknown Gala');
        });

        $qcKeys = ['inner_broken', 'inner_moisture', 'damage_creamish', 'damage_yellow', 'under_milled', 'chalky'];
        $galaData = [];

        $allWeighted = array_fill_keys($qcKeys, []);
        $allWeights = array_fill_keys($qcKeys, []);
        $allRaws = array_fill_keys($qcKeys, []);
        $totalNetWeight = 0;

        foreach ($grouped as $galaName => $items) {
            $galaWeight = (float) $items->sum('calculated_weight');
            $totalNetWeight += $galaWeight;

            $galaAverages = [];
            foreach ($qcKeys as $key) {
                $weightedVals = [];
                $weights = [];
                $rawVals = [];

                foreach ($items as $t) {
                    $w = $t->calculated_weight;
                    $v = $t->extracted_qc[$key] ?? null;

                    if ($v !== null && $v > 0) {
                        $rawVals[] = $v;
                        $weightedVals[] = $v * $w;
                        $weights[] = $w;

                        $allRaws[$key][] = $v;
                        $allWeighted[$key][] = $v * $w;
                        $allWeights[$key][] = $w;
                    }
                }

                $sumW = array_sum($weights);
                if ($sumW > 0) {
                    $galaAverages[$key] = array_sum($weightedVals) / $sumW;
                } elseif (count($rawVals) > 0) {
                    $galaAverages[$key] = array_sum($rawVals) / count($rawVals);
                } else {
                    $galaAverages[$key] = 0;
                }
            }

            $galaData[] = [
                'gala_name' => $galaName,
                'net_weight' => $galaWeight,
                'averages' => $galaAverages,
                'count_tickets' => $items->count(),
            ];
        }

        // Natural sort by Gala name
        usort($galaData, function ($a, $b) {
            return strnatcasecmp($a['gala_name'], $b['gala_name']);
        });

        // Overall Totals / Averages row
        $overallAverages = [];
        foreach ($qcKeys as $key) {
            $sumW = array_sum($allWeights[$key]);
            if ($sumW > 0) {
                $overallAverages[$key] = array_sum($allWeighted[$key]) / $sumW;
            } elseif (count($allRaws[$key]) > 0) {
                $overallAverages[$key] = array_sum($allRaws[$key]) / count($allRaws[$key]);
            } else {
                $overallAverages[$key] = 0;
            }
        }

        $product_slab_types = collect();
        $overallSlabAverages = [];

        return view('management.reports.arrival.gala-qc.get_gala_qc', compact(
            'galaData',
            'totalNetWeight',
            'overallAverages',
            'product_slab_types',
            'overallSlabAverages'
        ));
    }
}
