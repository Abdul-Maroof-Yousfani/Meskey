<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Country;
use Carbon\Carbon;

class DashboardController extends Controller {

    public function getCounts() {

        $businessId = auth()->user()->business->id;

        $orderCount = Order::where('business_id', $businessId)
        ->whereDate('created_at', today()) // Use today's date
        ->count();
    
        $orderTotalAmount = Order::where('business_id', $businessId)
        ->whereDate('created_at', today()) // Use today's date
        ->sum('total');
    
        $saleCount = Order::where('business_id', $businessId)
        ->where('is_paid', 1)
        ->whereDate('paid_at', today()) // Use today's date
        ->count();
    
        $saleTotalAmount = Order::where('business_id', $businessId)
        ->where('is_paid', 1)
        ->whereDate('paid_at', today()) // Use today's date
        ->sum('total');
    
        $totalSales = Order::where('business_id', $businessId)
        ->where('is_paid', 1)
        ->whereMonth('paid_at', now()->month) // Filter by current month
        ->whereYear('paid_at', now()->year)   // Filter by current year
        ->sum('total'); // Sum the total sales
    
        // Get the number of days in the current month
        $daysInMonth = now()->daysInMonth;
        
        // Calculate the average sale per day for the current month
        $avgSalePerDay = $daysInMonth > 0 ? round($totalSales / $daysInMonth, 3) : 0;
    
        $monthDay = today()->day; // Getting the day of the month
    
        return response()->json([
            'counts' => [
                'order' => [
                    'count' => $orderCount,
                    'amount' => $orderTotalAmount
                ],
                'sale' => [
                    'count' => $saleCount,
                    'amount' => $saleTotalAmount
                ],
                'ave_sale' => [
                    'day' => $monthDay,
                    'sale' => $avgSalePerDay
                ],
                'expense' => [
                    'count' => 00,
                    'amount' => 0.000,
                ]
            ],
        ]);
    }

    private function getGraphDatabk($businessId, $start, $end)
    {
        return Order::where('business_id', $businessId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw(
                "
            COUNT(*) as total_orders_count,
            SUM(total) as total_orders_amount,
            SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_orders_count,
            SUM(CASE WHEN is_paid = 1 THEN total ELSE 0 END) as paid_orders_amount",
            )
            ->first();
    }
    private function getGraphData($businessId, $type = 'daily')
    {
        $businessStartDate = Order::where('business_id', $businessId)->min('created_at');
        $currentDate = now();
        $data = [];

        if ($businessStartDate) {
            $startDate = Carbon::parse($businessStartDate)->startOfDay(); // Start from the earliest order date

            while ($startDate->lessThanOrEqualTo($currentDate)) {
                // Clone `$startDate` to avoid unintended reference modifications
                $start = $startDate->clone();

                // Define the end date based on the selected type
                switch ($type) {
                    case 'weekly':
                        $end = $start->clone()->endOfWeek();
                        break;
                    case 'monthly':
                        $end = $start->clone()->endOfMonth();
                        break;
                    case 'daily':
                    default:
                        $end = $start->clone()->endOfDay();
                        break;
                }

                // Fetch the query result for this period
                $result = Order::where('business_id', $businessId)
                    ->whereBetween('created_at', [$start, $end])
                    ->selectRaw(
                        "
                    COUNT(*) as total_orders_count,
                    SUM(total) as total_orders_amount,
                    SUM(CASE WHEN is_paid = 1 THEN 1 ELSE 0 END) as paid_orders_count,
                    SUM(CASE WHEN is_paid = 1 THEN total ELSE 0 END) as paid_orders_amount
                ",
                    )
                    ->first();

                // Format start_date and end_date for monthly case
                $startDateFormatted =
                    $type === 'monthly'
                        ? $start->format('F Y') // Format as "January 2024"
                        : $start->toDateString();

                $endDateFormatted =
                    $type === 'monthly'
                        ? $start->format('F Y') // Same as start for monthly
                        : $end->toDateString();

                // Append data for this period to the array
                $data[] = [
                    'start_date' => $startDateFormatted,
                    'end_date' => $endDateFormatted,
                    'total_orders_count' => $result->total_orders_count ?? 0,
                    'total_orders_amount' => $result->total_orders_amount ?? 0.0,
                    'paid_orders_count' => $result->paid_orders_count ?? 0,
                    'paid_orders_amount' => $result->paid_orders_amount ?? 0.0,
                ];

                // Move `$startDate` to the next period based on the type
                switch ($type) {
                    case 'weekly':
                        $startDate = $start->clone()->addWeek()->startOfWeek();
                        break;
                    case 'monthly':
                        $startDate = $start->clone()->addMonth()->startOfMonth();
                        break;
                    case 'daily':
                    default:
                        $startDate = $start->clone()->addDay();
                        break;
                }
            }
        }

        return $data;
    }

    public function getCountries(Request $request)
    {
        $customers = Country::select('id', 'name', 'phonecode', 'currency_symbol', 'currency_name', 'currency')
            ->when(
                $request->search,
                fn($query) => $query->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%');
                }),
            )
            ->get();

        return response()->json(
            [
                'data' => $customers,
            ],
            200,
        );
    }
}
