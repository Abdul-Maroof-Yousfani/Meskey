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
Route::post('production-voucher-get-commodities-by-location', [ProductionVoucherController::class, 'getCommoditiesByLocation'])->name('production-voucher.get-commodities-by-location');
Route::post('production-voucher-get-packing-items', [ProductionVoucherController::class, 'getPackingItemsByJobOrder'])->name('production-voucher.get-packing-items');
Route::post('production-voucher-get-packing-items-with-produced', [ProductionVoucherController::class, 'getPackingItemsWithProduced'])->name('production-voucher.get-packing-items-with-produced');
Route::post('production-voucher-get-brands-by-job-orders', [ProductionVoucherController::class, 'getBrandsByJobOrders'])->name('production-voucher.get-brands-by-job-orders');
Route::get('production-voucher/{id}/input-form', [ProductionVoucherController::class, 'getInputForm'])->name('production-voucher.input.form');
Route::get('production-voucher/{id}/input-form/{inputId}', [ProductionVoucherController::class, 'getInputForm'])->name('production-voucher.input.edit-form');
Route::get('production-voucher/{id}/output-form', [ProductionVoucherController::class, 'getOutputForm'])->name('production-voucher.output.form');
Route::get('production-voucher/{id}/output-form/{outputId}', [ProductionVoucherController::class, 'getOutputForm'])->name('production-voucher.output.edit-form');
Route::post('production-voucher/{id}/input', [ProductionVoucherController::class, 'storeInput'])->name('production-voucher.input.store');
Route::put('production-voucher/{id}/input/{inputId}', [ProductionVoucherController::class, 'updateInput'])->name('production-voucher.input.update');
Route::delete('production-voucher/{id}/input/{inputId}', [ProductionVoucherController::class, 'destroyInput'])->name('production-voucher.input.destroy');
Route::post('production-voucher/{id}/output', [ProductionVoucherController::class, 'storeOutput'])->name('production-voucher.output.store');
Route::put('production-voucher/{id}/output/{outputId}', [ProductionVoucherController::class, 'updateOutput'])->name('production-voucher.output.update');
Route::delete('production-voucher/{id}/output/{outputId}', [ProductionVoucherController::class, 'destroyOutput'])->name('production-voucher.output.destroy');
Route::post('get-production-voucher-inputs/{id}', [ProductionVoucherController::class, 'getInputsList'])->name('get.production-voucher-inputs');
Route::post('get-production-voucher-outputs/{id}', [ProductionVoucherController::class, 'getOutputsList'])->name('get.production-voucher-outputs');
Route::get('production-voucher/{id}/slot-form', [ProductionVoucherController::class, 'getSlotForm'])->name('production-voucher.slot.form');
Route::get('production-voucher/{id}/slot-form/{slotId}', [ProductionVoucherController::class, 'getSlotForm'])->name('production-voucher.slot.edit-form');
Route::post('production-voucher/{id}/slot', [ProductionVoucherController::class, 'storeSlot'])->name('production-voucher.slot.store');
Route::put('production-voucher/{id}/slot/{slotId}', [ProductionVoucherController::class, 'updateSlot'])->name('production-voucher.slot.update');
Route::delete('production-voucher/{id}/slot/{slotId}', [ProductionVoucherController::class, 'destroySlot'])->name('production-voucher.slot.destroy');
Route::post('get-production-voucher-slots/{id}', [ProductionVoucherController::class, 'getSlotsList'])->name('get.production-voucher-slots');

// Production Slots Routes
use App\Http\Controllers\Production\ProductionSlotController;
Route::resource('production-slot', ProductionSlotController::class);
Route::post('get-production-slot', [ProductionSlotController::class, 'getList'])->name('get.production-slot');
Route::post('production-slot/{slotId}/break', [ProductionSlotController::class, 'storeBreak'])->name('production-slot.break.store');
Route::put('production-slot/{slotId}/break/{breakId}', [ProductionSlotController::class, 'updateBreak'])->name('production-slot.break.update');
Route::delete('production-slot/{slotId}/break/{breakId}', [ProductionSlotController::class, 'destroyBreak'])->name('production-slot.break.destroy');