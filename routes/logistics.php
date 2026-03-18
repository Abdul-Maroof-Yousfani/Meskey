<?php 

use App\Http\Controllers\Sales\DeliveryChallanController;
use App\Http\Controllers\Sales\DeliveryOrderController;
use App\Http\Controllers\Sales\FirstWeighBridgeController;
use App\Http\Controllers\Sales\LoadingProgramController;
use App\Http\Controllers\Sales\SecondWeighBridgeController;
use App\Http\Controllers\Sales\ReceivingRequestController;
use App\Http\Controllers\Sales\SaleOrderController;
use App\Http\Controllers\Sales\SalesInquiryController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesReturnController;


Route::name("logistics.")->group(function () {
    Route::resource("logistics", \App\Http\Controllers\Sales\LogisticsController::class);
    Route::post("get-logistics", [\App\Http\Controllers\Sales\LogisticsController::class, "getList"])->name("get.logistics.list");
    Route::get("logistics/get-order-details/{id}", [\App\Http\Controllers\Sales\LogisticsController::class, "getOrderDetails"])->name("logistics.getOrderDetails");
});
