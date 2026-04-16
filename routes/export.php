<?php

use App\Http\Controllers\Export\BankController;
use App\Http\Controllers\Export\CommercialInvoiceController;
use App\Http\Controllers\Export\CurrencyController;
use App\Http\Controllers\Export\ExportOrderController;
use App\Http\Controllers\Export\ExportSodaFieldController;
use App\Http\Controllers\Export\IncoTermController;
use App\Http\Controllers\Export\ModeOfTermController;
use App\Http\Controllers\Export\ModeOfTransportController;
use App\Http\Controllers\Export\ExportLoadingProgramController;
use App\Http\Controllers\Export\ProformaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Export\QuotationController;
use App\Http\Controllers\Export\ExportDeliveryOrderController;
use App\Http\Controllers\Export\ExportFirstWeighBridgeController;
use App\Http\Controllers\Export\ExportLoadingSlipController;
use App\Http\Controllers\Export\ExportQcController;
use App\Http\Controllers\Export\ExportDispatchQcController;
use App\Http\Controllers\Export\ExportDeliveryChallanController;
use App\Http\Controllers\Export\ExportSecondWeighBridgeController;

// mode of terms
Route::resource('modeofterms', ModeOfTermController::class);
Route::post('/get-modes', [ModeOfTermController::class, 'getTable'])->name('get.modes');

// mode of transport
Route::resource('modeoftransport', ModeOfTransportController::class);
Route::post('/get-transport-modes', [ModeOfTransportController::class, 'getTable'])->name('get.transport.modes');

// currency
Route::resource('currency', CurrencyController::class);
Route::post('/get-currency', [CurrencyController::class, 'getCurrencyTable'])->name('get.currency');

// incoterm
Route::resource('incoterm', IncoTermController::class);
Route::post('/get-incoterm', [IncoTermController::class, 'getIncotermTable'])->name('get.incoterm');

// bank
Route::resource('bank', BankController::class);
Route::post('/get-bank', [BankController::class, 'getBankTable'])->name('get.bank');

// export order
Route::resource('export-order', ExportOrderController::class);
Route::post('/get-export-order', [ExportOrderController::class, 'getExportOrderTable'])->name('get.export-order');

// export delivery order
Route::resource('export-delivery-order', ExportDeliveryOrderController::class);
Route::post('/get-export-delivery-order', [ExportDeliveryOrderController::class, 'getExportDeliveryOrderTable'])->name('get.export-delivery-order');
Route::get('/get-export-order-details/{id}', [ExportDeliveryOrderController::class, 'getExportOrderDetails'])->name('export.get-export-order-details');
Route::get('/get-orders-by-buyer/{buyerId}', [ExportDeliveryOrderController::class, 'getOrdersByBuyer'])->name('export.get-orders-by-buyer');
Route::get('/get-arrival-locations', [ExportDeliveryOrderController::class, 'getArrivalLocations'])->name('export.get-arrival-locations');
Route::get('/get-sub-arrival-locations', [ExportDeliveryOrderController::class, 'getSubArrivalLocations'])->name('export.get-sub-arrival-locations');
Route::get('/export-order/get-quotation-details/{id}', [ExportOrderController::class, 'getQuotationDetails'])->name('export-order.get-quotation-details');

// export loading program
Route::resource('export-loading-program', ExportLoadingProgramController::class);
Route::post('/get-export-loading-program', [ExportLoadingProgramController::class, 'getList'])->name('get.export-loading-program');
Route::get('/fetch-export-orders-by-location', [ExportLoadingProgramController::class, 'fetchExportOrdersByLocation'])->name('fetch.export.orders.by.location');
Route::get('/get-export-order-related-data', [ExportLoadingProgramController::class, 'getExportOrderRelatedData'])->name('get.export-order.related.data');
Route::get('/get-delivery-orders-by-export-order-loading', [ExportLoadingProgramController::class, 'getDeliveryOrdersByExportOrder'])->name('get.delivery-orders.by.export-order.loading');
Route::get('/get-delivery-orders-by-export-order-loading-edit', [ExportLoadingProgramController::class, 'getDeliveryOrdersByExportOrderEdit'])->name('get.delivery-orders.by.export-order.loading.edit');

// export first weighbridge
Route::resource('export-first-weighbridge', ExportFirstWeighBridgeController::class);
Route::post('/get-export-first-weighbridge', [ExportFirstWeighBridgeController::class, 'getList'])->name('get.export-first-weighbridge');
Route::get('/get-export-first-weighbridge-related-data', [ExportFirstWeighBridgeController::class, 'getFirstWeighbridgeRelatedData'])->name('export.getFirstWeighbridgeRelatedData');
Route::get('/get-export-weighbridge-amount', [ExportFirstWeighBridgeController::class, 'getWeighbridgeAmount'])->name('export.getWeighbridgeAmount');

// export qc
Route::resource('export-qc', ExportQcController::class);
Route::post('/get-export-qc', [ExportQcController::class, 'getList'])->name('get.export-qc');
Route::get('/get-ticket-related-data-for-qc', [ExportQcController::class, 'getTicketRelatedData'])->name('export.getTicketRelatedDataForQc');

// export loading slip
Route::resource('export-loading-slip', ExportLoadingSlipController::class);
Route::post('/get-export-loading-slip', [ExportLoadingSlipController::class, 'getList'])->name('get.export-loading-slip');
Route::get('/get-export-loading-slip-ticket-data', [ExportLoadingSlipController::class, 'getTicketRelatedData'])->name('export.getLoadingSlipTicketData');

// export dispatch qc
Route::resource('export-dispatch-qc', ExportDispatchQcController::class);
Route::post('/get-export-dispatch-qc', [ExportDispatchQcController::class, 'getList'])->name('get.export-dispatch-qc');
Route::get('/get-export-dispatch-qc-ticket-data', [ExportDispatchQcController::class, 'getTicketRelatedData'])->name('export.getDispatchQcTicketData');
Route::get('/export-dispatch-qc/{id}/gate-out', [ExportDispatchQcController::class, 'get_gate_out'])->name('export.get.dispatch-qc.gate-out');

// export second weighbridge
Route::resource('export-second-weighbridge', ExportSecondWeighBridgeController::class);
Route::post('/get-export-second-weighbridge', [ExportSecondWeighBridgeController::class, 'getList'])->name('get.export-second-weighbridge');
Route::get('/get-export-second-weighbridge-related-data', [ExportSecondWeighBridgeController::class, 'getSecondWeighbridgeRelatedData'])->name('export.getSecondWeighbridgeRelatedData');
Route::get('/get-delivery-orders-by-export-order-second', [ExportSecondWeighBridgeController::class, 'getDeliveryOrdersByExportOrder'])->name('getDeliveryOrdersByExportOrderSecond');
Route::get('/get-export-delivery-order-balance-against-second-weighbridge', [ExportSecondWeighBridgeController::class, 'getBalanceAgainstSecondWeighbridge'])->name('export.balance-against-second-weighbridge');

// export delivery challan
Route::resource('export-delivery-challan', ExportDeliveryChallanController::class);
Route::post('/get-export-delivery-challan', [ExportDeliveryChallanController::class, 'getList'])->name('get.export-delivery-challan.list');
Route::get('/export-delivery-challan/{delivery_challan}/view', [ExportDeliveryChallanController::class, 'view'])->name('export-delivery-challan.view');
Route::get('/get/export/dc-no', [ExportDeliveryChallanController::class, 'getNumber'])->name('get.export-delivery-challan.getNumber');
Route::get('/export-delivery-challan/get-do-against-customer', [ExportDeliveryChallanController::class, 'get_delivery_orders'])->name('get.export-delivery-challan.get-do');
Route::get('/get-export-delivery-challan-ticket-items', [ExportDeliveryChallanController::class, 'getItemsByTickets'])->name('export-delivery-challan.get-ticket-items');
Route::get('/get-export-delivery-challan-tickets', [ExportDeliveryChallanController::class, 'getTickets'])->name('export-delivery-challan.get-tickets');
Route::get('/get-export-tickets-with-dispatch-qc', [ExportDeliveryChallanController::class, 'getTicketsWithDispatchQc'])->name('export-delivery-challan.get-tickets-with-dispatch-qc');
Route::get('/get-export-ticket-data-for-dc', [ExportDeliveryChallanController::class, 'getTicketDataForDC'])->name('export-delivery-challan.get-ticket-data');

// export form-e
Route::resource('export-form-e', App\Http\Controllers\Export\ExportFormEController::class);
Route::post('/get-export-form-e', [App\Http\Controllers\Export\ExportFormEController::class, 'getExportFormETable'])->name('get.export-form-e');
Route::get('/get-export-order-details-form-e/{id}', [App\Http\Controllers\Export\ExportFormEController::class, 'getExportOrderDetails'])->name('export.get-export-order-details-form-e');
Route::get('/get-orders-by-buyer-form-e/{buyerId}', [App\Http\Controllers\Export\ExportFormEController::class, 'getOrdersByBuyer'])->name('export.get-orders-by-buyer-form-e');
Route::get('/get-form-es-by-order/{orderId}', [App\Http\Controllers\Export\ExportFormEController::class, 'getFormEsByOrder'])->name('export.get-form-es-by-order');

// quotation
Route::resource('quotation', QuotationController::class);
Route::post('/get-quotation', [QuotationController::class, 'getQuotationTable'])->name('get.quotation');
Route::get('get-product-specs-quotation/{productId}', [QuotationController::class, 'getProductSpecs'])->name('get.product_specs.quotation');
Route::get('get-buyer-details-quotation/{id}', [QuotationController::class, 'getBuyerDetails'])->name('get.buyer_details.quotation');
Route::get('get-sauda-details/{id}', [QuotationController::class, 'getSaudaDetails'])->name('quotation.get-sauda-details');

Route::get('/get-bank-details/{id}', function ($id) {
    return \App\Models\Export\Bank::findOrFail($id);
});

Route::get('get-product-specs/{productId}', [ExportOrderController::class, 'getProductSpecs'])->name('get.product_specs.export');
Route::post('/get-arrival-locations', [ExportOrderController::class, 'getArrivalLocationsByCompanyLocations']);
Route::post('/get-arrival-sub-locations', [ExportOrderController::class, 'getArrivalSubLocationsByArrivalLocations']);
Route::get('/export-order/customer-banks/{customerId}', [ExportOrderController::class, 'getCustomerBanks'])->name('export-order.customer-banks');

// proforma
Route::resource('proforma', ProformaController::class)->except(['create', 'store']);
Route::post('/get-proforma', [ProformaController::class, 'getProformaTable'])->name('get.proforma');
Route::get('/select-export-order', [ProformaController::class, 'selectExportOrder'])->name('proforma.select.export-order');
Route::get('/proforma/create/{exportOrderId}', [ProformaController::class, 'create'])->name('proforma.create');
Route::post('/proforma/create/{exportOrderId}', [ProformaController::class, 'store'])->name('proforma.store');
Route::get('/proforma/print/{id}', [ProformaController::class, 'print'])->name('proforma.print');

// commerical invoice
Route::resource('commercial-invoice', CommercialInvoiceController::class);
Route::post('/get-commercial-invoice', [CommercialInvoiceController::class, 'getCommercialInvoiceTable'])->name('get.commercial-invoice');

// export soda field 
Route::resource('export-soda-field', ExportSodaFieldController::class);
Route::post('/get-export-soda-field', [ExportSodaFieldController::class, 'getExportSodaFieldTable'])->name('get.export-soda-field');
