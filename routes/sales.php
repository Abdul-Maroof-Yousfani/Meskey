<?php 

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
});