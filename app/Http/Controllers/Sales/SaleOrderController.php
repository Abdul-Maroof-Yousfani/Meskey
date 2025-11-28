<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesOrder;
use Illuminate\Http\Request;

class SaleOrderController extends Controller
{
    public function index() {
        return view("management.sales.orders.index");
    }


    public function getList(Request $request) {
        $perPage = $request->get('per_page', 25);

    // Eager load the inquiry + all its items + related product
    $SalesOrders = SalesOrder::latest()
        ->paginate($perPage);

    
    $groupedData = [];

    foreach ($SalesOrders as $SaleOrder) {
        $so_no = $SaleOrder->reference_no;
        $items = $SaleOrder->sales_order_data;
        
        if ($items->isEmpty()) continue;

        $itemRows = [];
        foreach ($items as $itemData) {
            $itemRows[] = [
                'item_data' => $itemData,
            ];
        }

        $groupedData[] = [
                'sale_order' => $SaleOrder,
                'so_no' => $so_no,
                'created_by_id' => 1,
                'delivery_date' => $SaleOrder->delivery_date,
                'id' => $SaleOrder->id,
                'customer_id' => $SaleOrder->customer_id,
                'status' => $SaleOrder->status,
                'created_at' => $SaleOrder->created_at,
                'customer' => $SaleOrder->customer,
                'rowspan' => count($itemRows),
                'items' => $itemRows,
            ];
        }
    return view('management.sales.orders.getList', [
        'SalesOrders' => $SalesOrders,           // for pagination
        'groupedSalesOrders' => $groupedData,  // our grouped data
    ]);
    }
}
