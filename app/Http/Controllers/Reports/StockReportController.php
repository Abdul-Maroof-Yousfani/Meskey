<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Master\Account\Stock;
use App\Models\Product;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ArrivalLocation;
use App\Models\Master\Brands;
use App\Models\BagPacking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockReportController extends Controller
{
    /**
     * Display the Stock Report filter interface.
     */
    public function index(Request $request)
    {
        $products = Product::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'unique_no']);

        $locations = CompanyLocation::when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
            return $q->whereIn('id', getUserCurrentCompanyLocations());
        })->orderBy('name')->get(['id', 'name', 'code']);

        // Factories / Plots (e.g. A-43, A-44, A-45 inside Port Qasim)
        $factories = ArrivalLocation::with('companyLocation')
            ->where('status', 'active')
            ->when(auth()->check() && auth()->user()->user_type != 'super-admin', function ($q) {
                return $q->whereIn('company_location_id', getUserCurrentCompanyLocations());
            })
            ->orderBy('company_location_id')
            ->orderBy('name')
            ->get(['id', 'name', 'company_location_id']);

        // Brands (e.g. Meskay, Jazaa Foods, Sun Rice, Local etc.)
        $brands = Brands::orderBy('name')->get(['id', 'name']);

        $voucherTypes = [
            'grn'              => 'GRN (Goods Received Note)',
            'delivery_challan' => 'Delivery Challan',
            'qc'               => 'QC (Quality Control)',
            'purchase_return'  => 'Purchase Return',
            'sale_return'      => 'Sale Return',
            'gdn'              => 'GDN (Goods Despatch Note)',
        ];

        $movementTypes = [
            'stock-in'  => 'Stock-In (Inward)',
            'stock-out' => 'Stock-Out (Outward)',
        ];

        $bagPackings = BagPacking::orderBy('name')->get(['id', 'name']);

        $factoriesJson = $factories->map(function ($f) {
            return [
                'id'            => (string) $f->id,
                'name'          => $f->name,
                'location_id'   => (string) $f->company_location_id,
                'location_name' => $f->companyLocation->name ?? '',
            ];
        });

        return view('management.reports.stock.index', compact(
            'products',
            'locations',
            'factories',
            'factoriesJson',
            'brands',
            'voucherTypes',
            'movementTypes',
            'bagPackings'
        ));
    }

    /**
     * Fetch filtered stock report data.
     */
    public function getList(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $query = Stock::with(['product', 'companyLocation', 'bagPacking', 'brand', 'factory']);

        // Company location restriction for non-super admins
        if (auth()->check() && auth()->user()->user_type != 'super-admin') {
            $userLocs = getUserCurrentCompanyLocations();
            $query->where(function ($q) use ($userLocs) {
                $q->whereIn('company_location_id', $userLocs)
                  ->orWhereNull('company_location_id');
            });
        }

        // Warehouse / City Location Filter
        if ($request->filled('company_location_id')) {
            $locIds = (array) $request->company_location_id;
            $query->whereIn('company_location_id', $locIds);
        }

        // Factory / Plot Filter (arrival_id in stocks table, e.g. A-43, A-44, A-45)
        if ($request->filled('factory_id')) {
            $factoryIds = (array) $request->factory_id;
            $query->whereIn('arrival_id', $factoryIds);
        }

        // Brand Filter
        if ($request->filled('brand_id')) {
            $brandIds = (array) $request->brand_id;
            $query->whereIn('brand_id', $brandIds);
        }

        // Product Filter
        if ($request->filled('product_id')) {
            $productIds = (array) $request->product_id;
            $query->whereIn('product_id', $productIds);
        }

        // Voucher Type Filter
        if ($request->filled('voucher_type')) {
            $query->where('voucher_type', $request->voucher_type);
        }

        // Movement Type Filter (stock-in / stock-out)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Bag Packing Filter
        if ($request->filled('bag_packing_id')) {
            $query->where('bag_packing_id', $request->bag_packing_id);
        }

        // Search Keyword (voucher_no or narration)
        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);
            $query->where(function ($q) use ($keyword) {
                $q->where('voucher_no', 'like', "%{$keyword}%")
                  ->orWhere('narration', 'like', "%{$keyword}%");
            });
        }

        // Date Range Filter
        $dateLabel = 'All Time';
        if ($request->filled('daterange')) {
            $dates = explode(' - ', $request->daterange);
            if (count($dates) == 2) {
                try {
                    $startDate = Carbon::createFromFormat('m/d/Y', trim($dates[0]))->startOfDay();
                    $endDate   = Carbon::createFromFormat('m/d/Y', trim($dates[1]))->endOfDay();
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                    $dateLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
                } catch (\Exception $e) {
                    try {
                        $startDate = Carbon::parse(trim($dates[0]))->startOfDay();
                        $endDate   = Carbon::parse(trim($dates[1]))->endOfDay();
                        $query->whereBetween('created_at', [$startDate, $endDate]);
                        $dateLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
                    } catch (\Exception $e2) {
                        // ignore bad date parse
                    }
                }
            }
        }

        // Fetch all matching records
        $stocks = (clone $query)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Calculate KPI Metrics
        $totalInQty = 0;
        $totalOutQty = 0;
        $totalInValue = 0;
        $totalOutValue = 0;

        $productSummary = [];
        $locationSummary = [];
        $factorySummary = [];

        foreach ($stocks as $item) {
            $qty = (float) ($item->qty ?? 0);
            $price = (float) ($item->price ?? 0);
            $isStockIn = ($item->type === 'stock-in');

            if ($isStockIn) {
                $totalInQty += $qty;
                $totalInValue += $price;
            } else {
                $totalOutQty += $qty;
                $totalOutValue += $price;
            }

            // Group by Product
            $pId = $item->product_id ?? 0;
            if (!isset($productSummary[$pId])) {
                $productSummary[$pId] = [
                    'product_id'      => $pId,
                    'product_code'    => $item->product->unique_no ?? 'N/A',
                    'product_name'    => $item->product->name ?? 'Unknown Product',
                    'total_in_qty'    => 0,
                    'total_out_qty'   => 0,
                    'closing_balance' => 0,
                    'total_price'     => 0,
                    'tx_count'        => 0,
                ];
            }
            if ($isStockIn) {
                $productSummary[$pId]['total_in_qty'] += $qty;
                $productSummary[$pId]['total_price'] += $price;
            } else {
                $productSummary[$pId]['total_out_qty'] += $qty;
            }
            $productSummary[$pId]['closing_balance'] = $productSummary[$pId]['total_in_qty'] - $productSummary[$pId]['total_out_qty'];
            $productSummary[$pId]['tx_count']++;

            // Group by Location (City / Warehouse)
            $locId = $item->company_location_id ?? 0;
            $locName = $item->companyLocation->name ?? ($locId ? "Location #{$locId}" : 'Unassigned / Central');
            if (!isset($locationSummary[$locId])) {
                $locationSummary[$locId] = [
                    'location_id'     => $locId,
                    'location_name'   => $locName,
                    'location_code'   => $item->companyLocation->code ?? '',
                    'total_in_qty'    => 0,
                    'total_out_qty'   => 0,
                    'closing_balance' => 0,
                    'tx_count'        => 0,
                ];
            }
            if ($isStockIn) {
                $locationSummary[$locId]['total_in_qty'] += $qty;
            } else {
                $locationSummary[$locId]['total_out_qty'] += $qty;
            }
            $locationSummary[$locId]['closing_balance'] = $locationSummary[$locId]['total_in_qty'] - $locationSummary[$locId]['total_out_qty'];
            $locationSummary[$locId]['tx_count']++;

            // Group by Factory / Plot (arrival_id, e.g. A-43, A-44, A-45)
            $fId = $item->arrival_id ?? 0;
            $fName = $item->factory->name ?? ($fId ? "Factory #{$fId}" : 'General / Unspecified');
            if (!isset($factorySummary[$fId])) {
                $factorySummary[$fId] = [
                    'factory_id'      => $fId,
                    'factory_name'    => $fName,
                    'location_name'   => $locName,
                    'total_in_qty'    => 0,
                    'total_out_qty'   => 0,
                    'closing_balance' => 0,
                    'tx_count'        => 0,
                ];
            }
            if ($isStockIn) {
                $factorySummary[$fId]['total_in_qty'] += $qty;
            } else {
                $factorySummary[$fId]['total_out_qty'] += $qty;
            }
            $factorySummary[$fId]['closing_balance'] = $factorySummary[$fId]['total_in_qty'] - $factorySummary[$fId]['total_out_qty'];
            $factorySummary[$fId]['tx_count']++;
        }

        // Sort summaries desc by closing balance
        uasort($productSummary, function ($a, $b) {
            return $b['closing_balance'] <=> $a['closing_balance'];
        });

        uasort($locationSummary, function ($a, $b) {
            return $b['closing_balance'] <=> $a['closing_balance'];
        });

        uasort($factorySummary, function ($a, $b) {
            return $b['closing_balance'] <=> $a['closing_balance'];
        });

        $netBalanceQty = $totalInQty - $totalOutQty;
        $avgInRate = $totalInQty > 0 ? ($totalInValue / $totalInQty) : 0;
        $totalTransactions = count($stocks);

        return view('management.reports.stock.getList', compact(
            'stocks',
            'totalInQty',
            'totalOutQty',
            'netBalanceQty',
            'totalInValue',
            'totalOutValue',
            'avgInRate',
            'totalTransactions',
            'productSummary',
            'locationSummary',
            'factorySummary',
            'dateLabel'
        ));
    }
}
