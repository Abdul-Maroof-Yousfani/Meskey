<?php

use App\Http\Controllers\IndicativePriceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Procurement\RawMaterial\{
    GateBuyingController,
    PaymentRequestController,
    PurchaseFreightController,
    PurchaseRequestController,
    PurchaseOrderController,
    PurchaseSamplingController,
    PurchaseSamplingMonitoringController,
    PurchaseSamplingRequestController,
    TicketContractController
};

Route::prefix('raw-material')->name('raw-material.')->group(function () {
    Route::resource('purchase-request', PurchaseRequestController::class);
    Route::post('get-purchase-request', [PurchaseRequestController::class, 'getList'])->name('get.purchase-request');

    Route::resource('gate-buying', GateBuyingController::class);
    Route::post('get-gate-buying', [GateBuyingController::class, 'getList'])->name('get.gate-buying');
    Route::get('/getGateBuyingMainSlabByProduct', [GateBuyingController::class, 'getMainSlabByProduct'])->name('getGateBuyingMainSlabByProduct');

    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::post('get-purchase-order', [PurchaseOrderController::class, 'getList'])->name('get.purchase-order');
    Route::post('purchase-order/mark-completed', [PurchaseOrderController::class, 'markAsCompleted'])->name('purchase-order.mark-completed');
    Route::get('/getMainSlabByProduct', [PurchaseOrderController::class, 'getMainSlabByProduct'])->name('getMainSlabByProduct');
    Route::post('/generate-contract-number', [PurchaseOrderController::class, 'getContractNumber'])->name('generate.contract.number');

    Route::resource('purchase-sampling-request', PurchaseSamplingRequestController::class);
    Route::post('get-purchase-sampling-request', [PurchaseSamplingRequestController::class, 'getList'])->name('get.purchase-sampling-request');

    Route::resource('purchase-sampling', PurchaseSamplingController::class);
    Route::resource('purchase-resampling', PurchaseSamplingController::class);
    Route::post('get-purchase-sampling', [PurchaseSamplingController::class, 'getList'])->name('get.purchase-sampling');
    Route::post('get-purchase-resampling', [PurchaseSamplingController::class, 'getList'])->name('get.purchase-resampling');

    Route::resource('sampling-monitoring',  PurchaseSamplingMonitoringController::class);
    Route::post('/get-sampling-monitoring', [PurchaseSamplingMonitoringController::class, 'getList'])->name('get.sampling-monitoring');

    Route::resource('freight', PurchaseFreightController::class);
    Route::post('/get-freight', [PurchaseFreightController::class, 'getList'])->name('get.freight');

    Route::get('/ticket-contracts/search-contracts', [TicketContractController::class, 'searchContracts'])->name('ticket-contracts.search-contracts');
    Route::resource('ticket-contracts', TicketContractController::class);
    Route::post('/get-ticket-contracts', [TicketContractController::class, 'getList'])->name('get.ticket-contracts');

    Route::resource('payment-request', PaymentRequestController::class);
    Route::post('/get-payment-request', [PaymentRequestController::class, 'getList'])->name('get.payment-request');

    Route::get('/get-freight-form', [PurchaseFreightController::class, 'getFreightForm'])->name('freight.getFreightForm');
});

// Route::resource('indicative-prices', IndicativePriceController::class)->except(['create', 'show', 'edit']);
// Route::post('/get-indicative-prices', [IndicativePriceController::class, 'getList'])->name('get.indicative-prices');

// Route::prefix('indicative-prices')->name('indicative-prices.')->group(function () {
//     Route::get('/reports', [IndicativePriceController::class, 'reportsView'])->name('reports');
//     Route::post('/reports-list', [IndicativePriceController::class, 'reports'])->name('reports.get-list');
// });

Route::prefix('indicative-prices')->group(function () {
    Route::get('/', [IndicativePriceController::class, 'index'])->name('indicative-prices.index');
    Route::get('/get-list', [IndicativePriceController::class, 'getList'])->name('get.indicative-prices');
    Route::post('/', [IndicativePriceController::class, 'store'])->name('indicative-prices.store');
    Route::put('/{id}', [IndicativePriceController::class, 'update'])->name('indicative-prices.update');
    Route::delete('/{id}', [IndicativePriceController::class, 'destroy'])->name('indicative-prices.destroy');
    Route::get('/reports', [IndicativePriceController::class, 'reportsView'])->name('indicative-prices.reports');
    Route::post('/reports-list', [IndicativePriceController::class, 'reports'])->name('indicative-prices.reports.get-list');
});
