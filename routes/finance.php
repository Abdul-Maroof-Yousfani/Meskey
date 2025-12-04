<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use App\Http\Controllers\Finance\JournalVoucherController;
use App\Http\Controllers\Finance\ReceiptVoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-voucher')->group(function () {
    Route::get('approvals/{id}', [PaymentVoucherController::class, 'manageApprovals'])->name('payment-voucher.approvals');
    Route::post('generate-pv-number', [PaymentVoucherController::class, 'generatePvNumber'])->name('payment-voucher.generate-pv-number');
    Route::get('payment-requests/{purchaseOrderId}', [PaymentVoucherController::class, 'getPaymentRequests'])->name('payment-voucher.payment-requests');
    Route::get('account-payment-requests/{accountId}', [PaymentVoucherController::class, 'getAccountPaymentRequests'])->name('payment-voucher.account-payment-requests');
});

Route::resource('payment-voucher', PaymentVoucherController::class);
Route::post('get-payment-voucher', [PaymentVoucherController::class, 'getList'])->name('get.payment-vouchers');

Route::resource('receipt-voucher', ReceiptVoucherController::class);
Route::post('get-receipt-voucher', [ReceiptVoucherController::class, 'getList'])->name('get.receipt-vouchers');

Route::prefix('journal-voucher')->group(function () {
    Route::post('generate-jv-number', [JournalVoucherController::class, 'generateJvNumber'])->name('journal-voucher.generate-jv-number');
    Route::post('{id}/approve', [JournalVoucherController::class, 'approve'])->name('journal-voucher.approve');
    Route::post('{id}/reject', [JournalVoucherController::class, 'reject'])->name('journal-voucher.reject');
});

Route::resource('journal-voucher', JournalVoucherController::class);
Route::post('get-journal-voucher', [JournalVoucherController::class, 'getList'])->name('get.journal-vouchers');
