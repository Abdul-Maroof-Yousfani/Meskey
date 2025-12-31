<?php 

use App\Http\Controllers\Sales\DeliveryChallanController;
use App\Http\Controllers\Sales\DeliveryOrderController;
use App\Http\Controllers\Sales\FirstWeighBridgeController;
use App\Http\Controllers\Sales\SecondWeighBridgeController;
use App\Http\Controllers\Sales\ReceivingRequestController;
use App\Http\Controllers\Sales\SaleOrderController;
use App\Http\Controllers\Sales\SalesInquiryController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesReturnController;


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


    Route::resource("first-weighbridge", FirstWeighBridgeController::class);
    Route::post("get-first-weighbridge", [FirstWeighBridgeController::class, "getList"])->name("get.first-weighbridge");
    Route::get('/get-first-weighbridge-related-data', [FirstWeighBridgeController::class, 'getFirstWeighbridgeRelatedData'])->name('getFirstWeighbridgeRelatedData');
    Route::get('/get-delivery-orders-by-sale-order', [FirstWeighBridgeController::class, 'getDeliveryOrdersBySaleOrder'])->name('getDeliveryOrdersBySaleOrder');
    Route::get('/get-weighbridge-amount', [FirstWeighBridgeController::class, 'getWeighbridgeAmount'])->name('getWeighbridgeAmount');

    Route::resource("second-weighbridge", SecondWeighBridgeController::class);
    Route::post("get-second-weighbridge", [SecondWeighBridgeController::class, "getList"])->name("get.second-weighbridge");
    Route::get('/get-second-weighbridge-related-data', [SecondWeighBridgeController::class, 'getSecondWeighbridgeRelatedData'])->name('getSecondWeighbridgeRelatedData');
    Route::get('/get-second-weighbridge-amount', [SecondWeighBridgeController::class, 'getWeighbridgeAmount'])->name('getSecondWeighbridgeAmount');
    Route::get('/get-delivery-orders-by-sale-order-second', [SecondWeighBridgeController::class, 'getDeliveryOrdersBySaleOrder'])->name('getDeliveryOrdersBySaleOrderSecond');

    Route::resource("loading-program", \App\Http\Controllers\Sales\LoadingProgramController::class);
    Route::post("get-loading-program", [\App\Http\Controllers\Sales\LoadingProgramController::class, "getList"])->name("get.loading-program");
    Route::get('/get-sale-order-related-data', [\App\Http\Controllers\Sales\LoadingProgramController::class, 'getSaleOrderRelatedData'])->name('getSaleOrderRelatedData');
    Route::get('/get-delivery-orders-by-sale-order-loading', [\App\Http\Controllers\Sales\LoadingProgramController::class, 'getDeliveryOrdersBySaleOrder'])->name('getDeliveryOrdersBySaleOrderLoading');
    Route::get('/get-delivery-orders-by-sale-order-loading-edit', [\App\Http\Controllers\Sales\LoadingProgramController::class, 'getDeliveryOrdersBySaleOrderEdit'])->name('getDeliveryOrdersBySaleOrderLoadingEdit');
    // Receiving Request Routes
    Route::resource("receiving-request", ReceivingRequestController::class)->only(['index', 'edit', 'update']);
    Route::post("get-receiving-request", [ReceivingRequestController::class, "getList"])->name("get.receiving-request.list");
    Route::get("/receiving-request/{id}/view", [ReceivingRequestController::class, "view"])->name("receiving-request.view");

    // Sales Invoice Routes
    Route::resource("sales-invoice", SalesInvoiceController::class);
    Route::post("get-sales-invoice", [SalesInvoiceController::class, "getList"])->name("get.sales-invoice.list");
    Route::get("/get/si-no", [SalesInvoiceController::class, "getNumber"])->name("get.sales-invoice.getNumber");
    Route::get("get-dc-for-invoice", [SalesInvoiceController::class, "get_delivery_challans"])->name("get.sales-invoice.get-dc");
    Route::get("/get-sales-invoice-items", [SalesInvoiceController::class, "getItems"])->name("get.sales-invoice.get-items");
    Route::get("/sales-invoice/{sales_invoice}/view", [SalesInvoiceController::class, "view"])->name("get.sales-invoice.view");

    Route::resource("sales-return", SalesReturnController::class);
    Route::get("/get/sale-invoices", [SalesReturnController::class, "get_sale_invoices"])->name("get.invoice-numbers");
    Route::get("/get/sale-invoice-items", [SalesReturnController::class, "getitems"])->name("get.invoice-items");
    Route::get("/get/sr-no", [SalesReturnController::class, "getNumber"])->name("get.sales-return.getNumber");
    Route::post("get-sale-returns", [SalesReturnController::class, "getList"])->name("get.sales-return.list");
    Route::get("sales-return/{id}/view", [SalesReturnController::class, "view"])->name("sales-return.view");

    Route::resource("sales-qc", \App\Http\Controllers\Sales\SalesQcController::class);
    Route::post("get-sales-qc", [\App\Http\Controllers\Sales\SalesQcController::class, "getList"])->name("get.sales-qc");
    Route::get('/get-ticket-related-data', [\App\Http\Controllers\Sales\SalesQcController::class, 'getTicketRelatedData'])->name('getTicketRelatedData');

});