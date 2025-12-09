<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use App\Http\Controllers\Production\JobOrderController;
use App\Http\Controllers\Production\JobOrderRawMaterialQcController;
use App\Http\Controllers\Production\ProductionVoucherController;
use App\Models\Production\JobOrder\JobOrderRawMaterialQc;
use Illuminate\Support\Facades\Route;




Route::resource('job-orders', JobOrderController::class);
Route::post('get-job-orders', [JobOrderController::class, 'getList'])->name('get.job_orders');
Route::get('get-product-specs/{productId}', [JobOrderController::class, 'getProductSpecs'])->name('get.product_specs');

Route::resource('job-order-rm-qc', JobOrderRawMaterialQcController::class);
Route::post('get-job-order-rm-qc', [JobOrderRawMaterialQcController::class, 'getList'])->name('get.job_order_rm_qc');
Route::post('get-job-order-detail-for-rm-qc', [JobOrderRawMaterialQcController::class, 'getJobOrderDetails'])->name('get.job_order_details');
Route::get('load-qc-commodities-tables', [JobOrderRawMaterialQcController::class, 'loadQcCommoditiesTables'])->name('load_qc_commodities_tables');


Route::resource('production-voucher', ProductionVoucherController::class);
Route::post('get-production-voucher', [ProductionVoucherController::class, 'getList'])->name('get.production-voucher');
Route::post('production-voucher-get-job-orders-by-location', [ProductionVoucherController::class, 'getJobOrdersByLocation'])->name('production-voucher.get-job-orders-by-location');
Route::get('production-voucher/{id}/input-form', [ProductionVoucherController::class, 'getInputForm'])->name('production-voucher.input.form');
Route::get('production-voucher/{id}/output-form', [ProductionVoucherController::class, 'getOutputForm'])->name('production-voucher.output.form');
Route::post('production-voucher/{id}/input', [ProductionVoucherController::class, 'storeInput'])->name('production-voucher.input.store');
Route::put('production-voucher/{id}/input/{inputId}', [ProductionVoucherController::class, 'updateInput'])->name('production-voucher.input.update');
Route::delete('production-voucher/{id}/input/{inputId}', [ProductionVoucherController::class, 'destroyInput'])->name('production-voucher.input.destroy');
Route::post('production-voucher/{id}/output', [ProductionVoucherController::class, 'storeOutput'])->name('production-voucher.output.store');
Route::put('production-voucher/{id}/output/{outputId}', [ProductionVoucherController::class, 'updateOutput'])->name('production-voucher.output.update');
Route::delete('production-voucher/{id}/output/{outputId}', [ProductionVoucherController::class, 'destroyOutput'])->name('production-voucher.output.destroy');