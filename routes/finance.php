<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use App\Http\Controllers\Finance\JournalVoucherController;
use App\Http\Controllers\Finance\ReceiptVoucherController;
use App\Models\ReceiptVoucher;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-voucher')->group(function () {
    Route::get('approvals/{id}', [PaymentVoucherController::class, 'manageApprovals'])->name('payment-voucher.approvals');
    Route::post('generate-pv-number', [PaymentVoucherController::class, 'generatePvNumber'])->name('payment-voucher.generate-pv-number');
    Route::get('payment-requests/{purchaseOrderId}', [PaymentVoucherController::class, 'getPaymentRequests'])->name('payment-voucher.payment-requests');
    Route::get('account-payment-requests/{accountId}', [PaymentVoucherController::class, 'getAccountPaymentRequests'])->name('payment-voucher.account-payment-requests');
    Route::get('purchase-bills/{supplierId}', [PaymentVoucherController::class, 'getPurchaseBills'])->name('payment-voucher.purchase-bills');
});

Route::resource('payment-voucher', PaymentVoucherController::class);

Route::post("payment-voucher/bill/create", [PaymentVoucherController::class, "storeBill"])->name("payment-voucher.bill-store");
Route::get("bill-payment-voucher/{id}/edit", [PaymentVoucherController::class, "editPaymentVoucher"])->name("payment-voucher.bill-edit");
Route::put("bill-payment-voucher/{id}/edit", [PaymentVoucherController::class, "updateBillPaymentVoucher"])->name("payment-voucher.bill-update");
Route::post('get-payment-voucher', [PaymentVoucherController::class, 'getList'])->name('get.payment-vouchers');

// Direct Payment Voucher
Route::get("/direct-payment-voucher", [PaymentVoucherController::class, "directPaymentVoucher"])->name("direct.payment-voucher");
Route::post("/direct-payment-voucher/store", [PaymentVoucherController::class, "direct_payment_voucher_store"])->name("direct.payment-voucher.store");
Route::get("/bill-payment-voucher", [PaymentVoucherController::class, "billPaymentVoucher"])->name("bill.payment-voucher");
// In routes/web.php or your finance routes file
Route::post('/payment-voucher/generate-pv-number', [PaymentVoucherController::class, 'generatePvNumber'])
    ->name('payment-voucher.generate-pv-number');

Route::get("/direct-payment-voucher/{payment_voucher}/edit", [PaymentVoucherController::class, "edit_direct"])->name("direct.payment-voucher.edit");
Route::put("/direct-payment-voucher/{payment_voucher}", [PaymentVoucherController::class, "update_direct"])->name("direct.payment-voucher.update");

Route::prefix('receipt-voucher')->group(function () {
    Route::post('generate-rv-number', [ReceiptVoucherController::class, 'generateRvNumber'])->name('receipt-voucher.generate-rv-number');
    Route::post('reference-details', [ReceiptVoucherController::class, 'getReferenceDetails'])->name('receipt-voucher.reference-details');
    Route::get("/get/documents-for-rv", [ReceiptVoucherController::class, "getDocumentsForRv"])->name("receipt.voucher.get-documents");
    Route::get('generate-advance-number', [ReceiptVoucherController::class, 'generateAdvanceNumber'])->name('receipt-voucher.generate-advance-number');
    Route::post('get-customer-advances', [ReceiptVoucherController::class, 'getCustomerAdvances'])->name('receipt-voucher.get-customer-advances');
});
Route::resource('receipt-voucher', ReceiptVoucherController::class);
Route::get("/direct-receipt-voucher", [ReceiptVoucherController::class, "directReceiptVoucher"])->name("direct.receipt-voucher");
Route::post("/direct-receipt-voucher/store", [ReceiptVoucherController::class, "direct_receipt_voucher"])->name("direct.receipt-voucher.store");
Route::post('get-receipt-voucher', [ReceiptVoucherController::class, 'getList'])->name('get.receipt-vouchers');
Route::post("/receipt-voucher/get-row", [ReceiptVoucherController::class, "getitems"])->name("receipt-voucher.get.rows");
// Add these routes to your web.php or relevant route file
Route::get("/direct-receipt-voucher/{receipt_voucher}/edit", [ReceiptVoucherController::class, "edit_direct"])->name("direct.receipt-voucher.edit");
Route::put("/direct-receipt-voucher/{receipt_voucher}", [ReceiptVoucherController::class, "update_direct"])->name("direct.receipt-voucher.update");

Route::prefix('journal-voucher')->group(function () {
    Route::post('generate-jv-number', [JournalVoucherController::class, 'generateJvNumber'])->name('journal-voucher.generate-jv-number');
    Route::post('{id}/approve', [JournalVoucherController::class, 'approve'])->name('journal-voucher.approve');
    Route::post('{id}/reject', [JournalVoucherController::class, 'reject'])->name('journal-voucher.reject');
});

Route::resource('journal-voucher', JournalVoucherController::class);
Route::post('get-journal-voucher', [JournalVoucherController::class, 'getList'])->name('get.journal-vouchers');

