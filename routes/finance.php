<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-voucher')->group(function () {
    Route::get('approvals/{id}', [PaymentVoucherController::class, 'manageApprovals'])->name('payment-voucher.approvals');
    Route::post('generate-pv-number', [PaymentVoucherController::class, 'generatePvNumber'])->name('payment-voucher.generate-pv-number');
    Route::get('payment-requests/{purchaseOrderId}', [PaymentVoucherController::class, 'getPaymentRequests'])->name('payment-voucher.payment-requests');
});

Route::resource('payment-voucher', PaymentVoucherController::class);
Route::post('get-payment-voucher', [PaymentVoucherController::class, 'getList'])->name('get.payment-vouchers');
