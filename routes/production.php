<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use App\Http\Controllers\Production\JobOrderController;
use Illuminate\Support\Facades\Route;




Route::resource('job-orders', JobOrderController::class);
Route::post('get-job-orders', [JobOrderController::class, 'getList'])->name('get.job_orders');
Route::get('get-product-specs/{productId}', [JobOrderController::class, 'getProductSpecs'])->name('get.product_specs');