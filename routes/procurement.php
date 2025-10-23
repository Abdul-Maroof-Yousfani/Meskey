<?php

use App\Http\Controllers\IndicativePriceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Procurement\RawMaterial\{
    GateBuyingPaymentRequestController,
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
    Route::get('/get-suppliers-by-location-for-gate-buying', [GateBuyingController::class, 'getSuppliersByLocation'])->name('get.suppliers.by.location_for_gate_buying');


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

    Route::get('purchase-order-payment-request-approval', [PurchaseOrderPaymentRequestController::class, 'index'])->name('purchase-order-payment-request-approval.index');
    Route::get('purchase-order-payment-request-approval/create', [PurchaseOrderPaymentRequestController::class, 'create'])->name('purchase-order-payment-request-approval.create');
    Route::post('purchase-order-payment-request-approval', [PurchaseOrderPaymentRequestController::class, 'requestStore'])->name('purchase-order-payment-request-approval.store');
    Route::get('purchase-order-payment-request-approval/{id}', [PurchaseOrderPaymentRequestController::class, 'show'])->name('purchase-order-payment-request-approval.show');
    Route::get('purchase-order-payment-request-approval/{id}/edit', [PurchaseOrderPaymentRequestController::class, 'getApprovalView'])->name('purchase-order-payment-request-approval.edit');
    Route::put('purchase-order-payment-request-approval/{id}', [PurchaseOrderPaymentRequestController::class, 'update'])->name('purchase-order-payment-request-approval.update');
    Route::delete('purchase-order-payment-request-approval/{id}', [PurchaseOrderPaymentRequestController::class, 'destroy'])->name('purchase-order-payment-request-approval.destroy');

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
    Route::prefix('gate-buy')->group(function () {
        Route::resource('payment-request', GateBuyingPaymentRequestController::class)->names('gate-buy.payment-request');
        Route::post('/get-payment-request', [GateBuyingPaymentRequestController::class, 'getList'])->name('gate-buy.get.payment-request');

       // Route::resource('payment-request-approval', PaymentRequestApprovalController::class)->names('gate-buying.payment-request-approval');
       // Route::post('/get-payment-request-approval', [PaymentRequestApprovalController::class, 'getList'])->name('gate-buying.get.payment-request-approval');
       // Route::post('/approve', [PaymentRequestApprovalController::class, 'approve'])->name('gate-buying.payment-request-approval.approve');

       // Route::get('/get-freight-form', [PurchaseFreightController::class, 'getFreightForm'])->name('gate-buying.freight.getFreightForm');
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
    Route::get('purchase-quotation/get_quotation_item', [PurchaseQuotationController::class, 'get_quotation_item'])->name('purchase-quotation.get_quotation_item');
    Route::get('purchase-quotation-approvals/{id}', [PurchaseQuotationController::class, 'manageApprovals'])->name('purchase-quotation.approvals');
    Route::get('get-unique-number-quotation/{locationId}/{contractDate}', [PurchaseQuotationController::class, 'getNumber'])->name('get-unique-number-quotation');
    Route::post('purchase-quotation/comparison', [PurchaseQuotationController::class, 'get_comparison'])->name('purchase-quotation.comparison');
    Route::get('purchase-quotation/comparison-list', [PurchaseQuotationController::class, 'comparison_list'])->name('purchase-quotation.comparison-list');
    Route::get('purchase-quotation/comparison-approvals/{id}', [PurchaseQuotationController::class, 'manageComparisonApprovals'])->name('purchase-quotation.comparison-approvals');
    Route::get('purchase-quotation/comparison-approvals-view/{id}', [PurchaseQuotationController::class, 'manageComparisonApprovalsView'])->name('purchase-quotation.comparison-approvals-view');

    Route::resource('purchase-order', StorePurchaseOrderController::class)->except(['show']);
    Route::post('get-purchase-order', [StorePurchaseOrderController::class, 'getList'])->name('get.purchase-order');
    Route::get('purchase-order/approve-item', [StorePurchaseOrderController::class, 'approve_item'])->name('purchase-order.approve-item');
    Route::get('purchase-order-approvals/{id}', [StorePurchaseOrderController::class, 'manageApprovals'])->name('purchase-order.approvals');
    Route::get('purchase-order/get_order_item', [StorePurchaseOrderController::class, 'get_order_item'])->name('purchase-order.get_order_item');
    Route::get('get-unique-number-order/{locationId}/{contractDate}', [StorePurchaseOrderController::class, 'getNumber'])->name('get-unique-number-order');

    Route::resource('purchase-order-receiving', PurchaseOrderReceivingController::class)->except(['show']);
    Route::post('get-purchase-order-receiving', [PurchaseOrderReceivingController::class, 'getList'])->name('get.purchase-order-receiving');
    Route::get('purchase-order-receiving/approve-item', [PurchaseOrderReceivingController::class, 'approve_item'])->name('purchase-order-receiving.approve-item');
    Route::get('purchase-order-receiving-approvals/{id}', [PurchaseOrderReceivingController::class, 'manageApprovals'])->name('purchase-order-receiving.approvals');
    Route::get('purchase-order-receiving/get_order_receiving_item', [PurchaseOrderReceivingController::class, 'get_order_item'])->name('purchase-order-receiving.get_order_receiving_item');
    Route::get('get-unique-number-order-receiving/{locationId}/{contractDate}', [PurchaseOrderReceivingController::class, 'getNumber'])->name('get-unique-number-order-receiving');


    Route::resource('purchase-order-payment-request', PurchaseOrderPaymentRequestController::class)->except(['show']);
    Route::post('get-purchase-order-payment-request', [PurchaseOrderPaymentRequestController::class, 'getList'])->name('get.purchase-order-payment-request');
    Route::get('purchase-order-payment-request/approve-item', [PurchaseOrderPaymentRequestController::class, 'approve_item'])->name('purchase-order-payment-request.approve-item');
    Route::get('purchase-order-payment-request/get-paid-amount', [PurchaseOrderPaymentRequestController::class, 'getPaidAmount'])->name('purchase-order-payment-request.get-paid-amount');

    Route::get('purchase-order-payment-request/get-sources', [PurchaseOrderPaymentRequestController::class, 'getSources'])->name('purchase-order-payment-request.get-sources');
    Route::post('purchase-order-payment-request/{id}/approve', [PurchaseOrderPaymentRequestController::class, 'approve'])->name('purchase-order-payment-request.approve');

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
