<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use App\Http\Controllers\Production\JobOrderController;
use App\Http\Controllers\Production\JobOrderRawMaterialQcController;
use App\Models\Production\JobOrder\JobOrderRawMaterialQc;
use Illuminate\Support\Facades\Route;




Route::resource('job-orders', JobOrderController::class);
Route::post('get-job-orders', [JobOrderController::class, 'getList'])->name('get.job_orders');
Route::get('get-product-specs/{productId}', [JobOrderController::class, 'getProductSpecs'])->name('get.product_specs');

Route::resource('job-order-rm-qc', JobOrderRawMaterialQcController::class);
Route::post('get-job-order-rm-qc', [JobOrderRawMaterialQcController::class, 'getList'])->name('get.job_order_rm_qc');
// Route::get('get-product-specs/{productId}', [JobOrderController::class, 'getProductSpecs'])->name('get.product_specs');
// Route::get('get-job-order-details/{jobOrderId}', [JobOrderRawMaterialQcController::class, 'getJobOrderDetails'])->name('get.job_order_details');
Route::post('get-job-order-detail-for-rm-qc', [JobOrderRawMaterialQcController::class, 'getJobOrderDetails'])->name('get.job_order_details');
Route::get('load-qc-commodities-tables', [JobOrderRawMaterialQcController::class, 'loadQcCommoditiesTables'])->name('load_qc_commodities_tables');