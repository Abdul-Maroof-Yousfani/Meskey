<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use Illuminate\Support\Facades\Route;

Route::resource('payment-voucher', PaymentVoucherController::class);
Route::post('payment-voucher/generate-pv-number', [PaymentVoucherController::class, 'generatePvNumber'])->name('payment-voucher.generate-pv-number');
Route::get('payment-voucher/payment-requests/{purchaseOrderId}', [PaymentVoucherController::class, 'getPaymentRequests'])->name('payment-voucher.payment-requests');
Route::post('get-payment-voucher', [PaymentVoucherController::class, 'getList'])->name('get.payment-vouchers');
