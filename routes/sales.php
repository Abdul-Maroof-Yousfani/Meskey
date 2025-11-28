<?php 

use App\Http\Controllers\Sales\SaleOrderController;
use App\Http\Controllers\Sales\SalesInquiryController;


Route::name("sales.")->group(function () {
    Route::resource("sales-inquiry", SalesInquiryController::class);
    Route::post("get-sales-inquiry", [SalesInquiryController::class, "getList"])->name("get.sales-inquiry.list");
    Route::get("sales-inquiry/{sales_inquiry}/view", [SalesInquiryController::class, "view"])->name("sales-inquiry.view");
    Route::get("/get/sales-number",  [SalesInquiryController::class, "getNumber"])->name("get.sales-number");

    Route::resource("sale-order", SaleOrderController::class);
    Route::post("get-sale-orders", [SaleOrderController::class, "getList"])->name("get.sales-order.list");
});