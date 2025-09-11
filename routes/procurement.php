<?php

use App\Http\Controllers\IndicativePriceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Procurement\RawMaterial\{
    AdvancePaymentRequestApprovalController,
    AdvancePaymentRequestController,
    DoubtTruckController,
    FreightRequestController,
    GateBuyingController,
    PaymentRequestApprovalController,
    PaymentRequestController,
    PurchaseFreightController,
    PurchaseRequestController,
    PurchaseOrderController,
    PurchaseSamplingController,
    PurchaseSamplingMonitoringController,
    PurchaseSamplingRequestController,
    SITVehicleController,
    TicketContractController,
    TicketPaymentRequestController
};
use App\Http\Controllers\Procurement\Store\{
    PurchaseOrderController as StorePurchaseOrderController,
    PurchaseOrderPaymentRequestController,
    PurchaseOrderReceivingController,
    PurchaseQuotationController,
    PurchaseRequestController as StorePurchaseRequestController,
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
    Route::get('/get-suppliers-by-location', [PurchaseOrderController::class, 'getSuppliersByLocation'])->name('get.suppliers.by.location');

    Route::get('create-purchase-sampling-request-ind', [PurchaseSamplingRequestController::class, 'createRequest'])->name('purchase-sampling-request.createReq');
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

    Route::resource('sit-vehicle', SITVehicleController::class);
    Route::post('/get-sit-vehicle', [SITVehicleController::class, 'getList'])->name('get.sit-vehicle');

    Route::get('/ticket-contracts/search-contracts', [TicketContractController::class, 'searchContracts'])->name('ticket-contracts.search-contracts');
    Route::resource('ticket-contracts', TicketContractController::class);
    Route::post('/get-ticket-contracts', [TicketContractController::class, 'getList'])->name('get.ticket-contracts');

    Route::resource('doubt-trucks', DoubtTruckController::class);
    Route::post('/get-doubt-trucks', [DoubtTruckController::class, 'getList'])->name('get.doubt-trucks');

    Route::resource('verified-contracts', TicketContractController::class);
    Route::post('/get-verified-contracts', [TicketContractController::class, 'getList'])->name('get.verified-contracts');

    Route::resource('payment-request', PaymentRequestController::class);
    Route::post('/get-payment-request', [PaymentRequestController::class, 'getList'])->name('get.payment-request');

    Route::resource('freight-request', FreightRequestController::class);
    Route::post('/get-freight-request', [FreightRequestController::class, 'getList'])->name('get.freight-request');
    Route::get('freight-request/view/{id}', [FreightRequestController::class, 'view'])->name('freight-request.view');

    Route::resource('advance-payment-request', AdvancePaymentRequestController::class);
    Route::post('/get-advance-payment-request', [AdvancePaymentRequestController::class, 'getList'])->name('get.advance-payment-request');

    Route::resource('payment-request-approval', PaymentRequestApprovalController::class);
    Route::resource('advance-payment-request-approval', AdvancePaymentRequestApprovalController::class);
    Route::post('/get-payment-request-approval', [PaymentRequestApprovalController::class, 'getList'])->name('get.payment-request-approval');
    Route::post('/approve', [PaymentRequestApprovalController::class, 'approve'])->name('payment-request-approval.approve');

    Route::get('/get-freight-form', [PurchaseFreightController::class, 'getFreightForm'])->name('freight.getFreightForm');

    Route::prefix('ticket')->group(function () {
        Route::resource('payment-request', TicketPaymentRequestController::class)->names('ticket.payment-request');
        Route::post('/get-payment-request', [TicketPaymentRequestController::class, 'getList'])->name('ticket.get.payment-request');

        Route::resource('payment-request-approval', PaymentRequestApprovalController::class)->names('ticket.payment-request-approval');
        Route::post('/get-payment-request-approval', [PaymentRequestApprovalController::class, 'getList'])->name('ticket.get.payment-request-approval');
        Route::post('/approve', [PaymentRequestApprovalController::class, 'approve'])->name('ticket.payment-request-approval.approve');

        Route::get('/get-freight-form', [PurchaseFreightController::class, 'getFreightForm'])->name('ticket.freight.getFreightForm');
    });
});

Route::prefix('store')->name('store.')->group(function () {
    Route::resource('purchase-request', StorePurchaseRequestController::class);
    Route::post('get-purchase-request', [StorePurchaseRequestController::class, 'getList'])->name('get.purchase-request');
    Route::get('purchase-request-approvals/{id}', [StorePurchaseRequestController::class, 'manageApprovals'])->name('purchase-request.approvals');
    Route::get('get-unique-number/{locationId}/{contractDate}', [StorePurchaseRequestController::class, 'getNumber'])->name('get-unique-umber');
    Route::get('purchase-request-approve/{id}', [StorePurchaseRequestController::class, 'approve'])->name('purchase-request.approve');

    Route::resource('purchase-quotation', PurchaseQuotationController::class)->except(['show']);
    Route::post('get-purchase-quotation', [PurchaseQuotationController::class, 'getList'])->name('get.purchase-quotation');
    Route::get('purchase-quotation/approve-item', [PurchaseQuotationController::class, 'approve_item'])->name('purchase-quotation.approve-item');
    Route::get('purchase-quotation-approvals/{id}', [PurchaseQuotationController::class, 'manageApprovals'])->name('purchase-quotation.approvals');

    Route::resource('purchase-order', StorePurchaseOrderController::class)->except(['show']);
    Route::post('get-purchase-order', [StorePurchaseOrderController::class, 'getList'])->name('get.purchase-order');
    Route::get('purchase-order/approve-item', [StorePurchaseOrderController::class, 'approve_item'])->name('purchase-order.approve-item');

    Route::resource('purchase-order-payment-request', PurchaseOrderPaymentRequestController::class)->except(['show']);
    Route::post('get-purchase-order-payment-request', [PurchaseOrderPaymentRequestController::class, 'getList'])->name('get.purchase-order-payment-request');
    Route::get('purchase-order-payment-request/approve-item', [PurchaseOrderPaymentRequestController::class, 'approve_item'])->name('purchase-order-payment-request.approve-item');

    Route::resource('purchase-order-receiving', PurchaseOrderReceivingController::class);
    Route::post('get-purchase-order-receiving', [PurchaseOrderReceivingController::class, 'getList'])->name('get.purchase-order-receiving');
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
