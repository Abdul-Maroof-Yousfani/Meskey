<?php

namespace App\Http\Controllers\Reports\Arrival;

use App\Http\Controllers\Controller;
use App\Models\Arrival\ArrivalTicket;
use App\Models\BagPacking;
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
        $bagPackings = BagPacking::all();
        $bagTypes = BagType::all();
        $commodities = Product::all();
        $saudaTypes = SaudaType::all();
        $suppliers = Supplier::all();
        $locations = CompanyLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->get();

        return view('management.reports.arrival.bag-wise.index', compact('bagPackings', 'bagTypes', 'commodities', 'saudaTypes', 'suppliers', 'locations'));
    }

    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $query = ArrivalTicket::join('arrival_approves', 'arrival_tickets.id', '=', 'arrival_approves.arrival_ticket_id')
            ->leftJoin('bag_types', 'arrival_approves.bag_type_id', '=', 'bag_types.id')
            ->leftJoin('bag_packings', 'arrival_approves.bag_packing_id', '=', 'bag_packings.id')
            ->whereIn('arrival_tickets.document_approval_status', ['fully_approved', 'half_approved'])
            ->where('arrival_tickets.second_weighbridge_status', 'completed')
            ->when($request->filled('bag_type_id'), function ($q) use ($request) {
                return $q->whereIn('arrival_approves.bag_type_id', (array)$request->bag_type_id);
            })
            ->when($request->filled('bag_packing_id'), function ($q) use ($request) {
                return $q->whereIn('arrival_approves.bag_packing_id', (array)$request->bag_packing_id);
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
            });

        $data = (clone $query)->select(
            'arrival_approves.bag_type_id',
            DB::raw('COALESCE(bag_types.name, "N/A") as bag_type_name'),
            'arrival_approves.bag_packing_id',
            DB::raw('COALESCE(bag_packings.name, "Other") as bag_packing_name'),
            DB::raw('COUNT(DISTINCT arrival_tickets.id) as ticket_count'),
            DB::raw('SUM(COALESCE(CAST(arrival_approves.filling_bags_no AS UNSIGNED), 0)) as total_filled_bags'),
            DB::raw('SUM(COALESCE(arrival_approves.total_bags, 0)) as total_bags'),
            DB::raw('SUM(COALESCE(arrival_tickets.arrived_net_weight, arrival_tickets.net_weight, 0)) as total_net_weight')
        )
        ->groupBy('arrival_approves.bag_type_id', 'bag_types.name', 'arrival_approves.bag_packing_id', 'bag_packings.name')
        ->get();

        $ticketsPerType = (clone $query)->select(
            'arrival_approves.bag_type_id',
            DB::raw('COUNT(DISTINCT arrival_tickets.id) as distinct_tickets')
        )
        ->groupBy('arrival_approves.bag_type_id')
        ->pluck('distinct_tickets', 'bag_type_id')
        ->toArray();

        $groupedData = [];
        $grandTotalFilledBags = 0;
        // $grandTotalBags = 0;
        // $grandTotalNetWeight = 0;

        foreach ($data as $row) {
            $typeId = $row->bag_type_id ?? 0;
            if (!isset($groupedData[$typeId])) {
                $groupedData[$typeId] = [
                    'bag_type_name' => $row->bag_type_name,
                    'total_tickets' => $ticketsPerType[$typeId] ?? 0,
                    'subtotal_filled_bags' => 0,
                    // 'subtotal_bags' => 0,
                    // 'subtotal_net_weight' => 0,
                    'packings' => [],
                ];
            }

            $groupedData[$typeId]['packings'][] = [
                'packing_name' => $row->bag_packing_name,
                'ticket_count' => (int) $row->ticket_count,
                'total_filled_bags' => (int) $row->total_filled_bags,
                // 'total_bags' => (int) $row->total_bags,
                // 'total_net_weight' => (float) $row->total_net_weight,
            ];

            $groupedData[$typeId]['subtotal_filled_bags'] += (int) $row->total_filled_bags;
            // $groupedData[$typeId]['subtotal_bags'] += (int) $row->total_bags;
            // $groupedData[$typeId]['subtotal_net_weight'] += (float) $row->total_net_weight;

            $grandTotalFilledBags += (int) $row->total_filled_bags;
            // $grandTotalBags += (int) $row->total_bags;
            // $grandTotalNetWeight += (float) $row->total_net_weight;
        }

        foreach ($groupedData as &$group) {
            usort($group['packings'], function ($a, $b) {
                return $b['total_filled_bags'] <=> $a['total_filled_bags'];
            });
        }
        unset($group);

        uasort($groupedData, function ($a, $b) {
            return $b['subtotal_filled_bags'] <=> $a['subtotal_filled_bags'];
        });

        $grandTotalTickets = (clone $query)->count(DB::raw('DISTINCT arrival_tickets.id'));

        return view('management.reports.arrival.bag-wise.getList', compact(
            'groupedData',
            'grandTotalTickets',
            'grandTotalFilledBags'
            // 'grandTotalBags',
            // 'grandTotalNetWeight'
        ));
    }
}
