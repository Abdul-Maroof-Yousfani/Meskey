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
use App\Models\SaudaType;

class WeightDifferenceController extends Controller
{
    public function index()
    {
        $commodities = Product::all();
        $suppliers = Supplier::all();
        $stations = Station::all();
        $product_slab_types = ProductSlabType::getForArrivalReport(null, null, 'purchase');
        $arrival_compulsory_qc_params = ArrivalCompulsoryQcParam::get();
        $locations = CompanyLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();
        $warehouses = ArrivalSubLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('company_location_id', getUserCurrentCompanyLocations());
        })->get();
        return view('management.reports.arrival.weight-difference.index', compact('commodities', 'suppliers', 'stations', 'product_slab_types', 'arrival_compulsory_qc_params', 'warehouses', 'locations'));
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
            $statuses = array_filter((array)$request->status_id);
            if (empty($statuses)) {
                return;
            }

            $pohanchId = SaudaType::where('name', 'like', '%pohanch%')->orWhere('name', 'like', '%pounch%')->value('id') ?? 1;
            $thaddaId = SaudaType::where('name', 'like', '%thadda%')->value('id') ?? 2;

            $hasOk = in_array('ok', $statuses) || in_array('fully_approved', $statuses);
            $hasTs = in_array('ts', $statuses);
            $hasHalfApproved = in_array('half_approved', $statuses);
            $hasRejected = in_array('rejected', $statuses);

            return $q->where(function ($statusQ) use ($hasOk, $hasTs, $hasHalfApproved, $hasRejected, $pohanchId, $thaddaId) {
                if ($hasOk && $hasTs) {
                    $statusQ->where(function ($sub) use ($pohanchId, $thaddaId) {
                        $sub->where('arrival_tickets.document_approval_status', 'fully_approved')
                            ->where(function ($typeSub) use ($pohanchId, $thaddaId) {
                                $typeSub->whereIn('arrival_tickets.sauda_type_id', [$pohanchId, $thaddaId])
                                    ->orWhere(function ($poSub) use ($pohanchId, $thaddaId) {
                                        $poSub->whereNull('arrival_tickets.sauda_type_id')
                                            ->whereHas('purchaseOrder', fn($poQ) => $poQ->whereIn('sauda_type_id', [$pohanchId, $thaddaId]));
                                    });
                            });
                    });
                } elseif ($hasOk) {
                    $statusQ->where(function ($sub) use ($thaddaId) {
                        $sub->where('arrival_tickets.document_approval_status', 'fully_approved')
                            ->where(function ($typeSub) use ($thaddaId) {
                                $typeSub->where('arrival_tickets.sauda_type_id', $thaddaId)
                                    ->orWhere(function ($poSub) use ($thaddaId) {
                                        $poSub->whereNull('arrival_tickets.sauda_type_id')
                                            ->whereHas('purchaseOrder', fn($poQ) => $poQ->where('sauda_type_id', $pohanchId));
                                    });
                            });
                    });
                } elseif ($hasTs) {
                    $statusQ->where(function ($sub) use ($pohanchId) {
                        $sub->where('arrival_tickets.document_approval_status', 'fully_approved')
                            ->where(function ($typeSub) use ($pohanchId) {
                                $typeSub->where('arrival_tickets.sauda_type_id', $pohanchId)
                                    ->orWhere(function ($poSub) use ($pohanchId) {
                                        $poSub->whereNull('arrival_tickets.sauda_type_id')
                                            ->whereHas('purchaseOrder', fn($poQ) => $poQ->where('sauda_type_id', $thaddaId));
                                    });
                            });
                    });
                }

                if ($hasHalfApproved) {
                    if ($hasOk || $hasTs) {
                        $statusQ->orWhere('arrival_tickets.document_approval_status', 'half_approved');
                    } else {
                        $statusQ->where('arrival_tickets.document_approval_status', 'half_approved');
                    }
                }

                if ($hasRejected) {
                    if ($hasOk || $hasTs || $hasHalfApproved) {
                        $statusQ->orWhereNull('arrival_tickets.document_approval_status');
                    } else {
                        $statusQ->whereNull('arrival_tickets.document_approval_status');
                    }
                }
            });
        })
        ->when($request->company_location_id, function ($q) use ($request) {
            $locIds = array_filter((array)$request->company_location_id);
            if (!empty($locIds) && !in_array('all', $locIds)) {
                return $q->whereIn('arrival_tickets.location_id', $locIds);
            }
        })
        ->when($request->warehouse_id, function ($q) use ($request) {
            $whIds = array_filter((array)$request->warehouse_id);
            if (!empty($whIds) && !in_array('all', $whIds)) {
                return $q->whereHas('approvals', function ($sq) use ($whIds) {
                    $sq->whereIn('gala_id', $whIds);
                });
            }
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
