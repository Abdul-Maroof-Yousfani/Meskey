<?php

namespace App\Http\Controllers\Procurement\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Master\CompanyLocation;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Sales\JobOrder;
use Illuminate\Http\Request;

class QcController extends Controller
{
    public function getForm($id) {
          $categories = Category::select('id', 'name')->where('category_type', 'general_items')->get();
        $locations = CompanyLocation::select('id', 'name')->get();
        $job_orders = JobOrder::select('id', 'name')->get();

        $purchaseOrderReceiving = PurchaseOrderReceiving::with([
            'purchaseOrderReceivingData',
            'purchaseOrderReceivingData.category',
            'purchaseOrderReceivingData.item',
            'purchaseOrderReceivingData.supplier'
        ])->findOrFail($id);


        $purchaseOrderReceivingData = PurchaseOrderReceivingData::with("purchase_order_data", "purchase_order_data.purchase_request_data")->where('purchase_order_receiving_id', $id)
            ->when(
                $purchaseOrderReceiving->am_approval_status === 'approved',
                function ($query) {
                    $query->where('am_approval_status', 'approved');
                }
            )
            ->get();
        
        $purchaseOrder = PurchaseOrder::select('id', 'purchase_order_no')->get();
        return view("management.procurement.store.purchase_order_receiving.qc", [
            'purchaseOrderReceiving' => $purchaseOrderReceiving,
            'categories' => $categories,
            'locations' => $locations,
            'job_orders' => $job_orders,
            'purchaseOrder' => $purchaseOrder,
            'purchaseOrderReceivingData' => $purchaseOrderReceivingData,
            'data1' => $purchaseOrderReceiving,
        ]);
    } 
}
