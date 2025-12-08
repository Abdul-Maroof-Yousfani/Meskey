<?php 

use App\Http\Controllers\Sales\DeliveryChallanController;
use App\Http\Controllers\Sales\DeliveryOrderController;
use App\Http\Controllers\Sales\SaleOrderController;
use App\Http\Controllers\Sales\SalesInquiryController;


Route::name("sales.")->group(function () {
    Route::resource("sales-inquiry", SalesInquiryController::class);
    Route::post("get-sales-inquiry", [SalesInquiryController::class, "getList"])->name("get.sales-inquiry.list");
    Route::get("sales-inquiry/{sales_inquiry}/view", [SalesInquiryController::class, "view"])->name("sales-inquiry.view");
    Route::get("/get/sales-number",  [SalesInquiryController::class, "getNumber"])->name("get.sales-number");
    
    Route::get("/get-sale-inquiry-data-items", [SaleOrderController::class, "get_inquiry_data"])->name("get-sale-inquiry-data");
    Route::get("/get-sale-inquiries-against-customer", [SaleOrderController::class, "get_inquiries"])->name("get-sale-inquiries-against-customer");
    Route::resource("sale-order", SaleOrderController::class);
    Route::get("/sales-order/{id}/view", [SaleOrderController::class, "view"])->name("sale-order.view");
    Route::post("get-sale-orders", [SaleOrderController::class, "getList"])->name("get.sales-order.list");
    Route::get("/get/so-no", [SaleOrderController::class, "getNumber"])->name("get.sales-order.getnumber");

    Route::resource("delivery-order", DeliveryOrderController::class);
    Route::post("get-delivery-order", [DeliveryOrderController::class, "getList"])->name("get.delivery-order.list");
    Route::get("/get/Do-no", [DeliveryOrderController::class, "getNumber"])->name("get.delivery-order.getnumber");
    Route::get("/get-so-against-customer", [DeliveryOrderController::class, "getSo"])->name("get.delivery-order.getSoAgainstCustomer");
    Route::get("/get-so-items-against-so", [DeliveryOrderController::class, "get_so_items"])->name("get.delivery-order.getSoItems");
    Route::get("/get-rv-against-so-no", [DeliveryOrderController::class, "get_receipt_vouchers"])->name("get.delivery-order.getRvAgainstSo");
    Route::get("/get-so-details", [DeliveryOrderController::class, "getDetails"])->name("get.delivery-order.details");
    Route::get("/delivery-order/{id}/view", [DeliveryOrderController::class, "view"])->name("get.delivery-order.view");
    Route::get("/get-arrival-locations-against-company-location", [DeliveryOrderController::class, "get_arrivals"])->name("get.arrival-locations");
    Route::get("/get-storage-locations-against-arrival-location", [DeliveryOrderController::class, "get_storages"])->name("get.storage-locations");

    Route::resource("delivery-challan", DeliveryChallanController::class);
    Route::post("get-delivery-challan", [DeliveryChallanController::class, "getList"])->name("get.delivery-challan.list");
    Route::get("/get/dc-no", [DeliveryChallanController::class, "getNumber"])->name("get.delivery-challan.getNumber");
    Route::get("get-do-against-customer", [DeliveryChallanController::class, "get_delivery_orders"])->name("get.delivery-challan.get-do");
    Route::get("/get-delivery-order-items",  [DeliveryChallanController::class, "getItems"])->name("get.delivery-challan.get-items");
    Route::get("/delivery-challan/{delivery_challan}/view", [DeliveryChallanController::class, "view"])->name("get.delivery-challan.view");
    

});