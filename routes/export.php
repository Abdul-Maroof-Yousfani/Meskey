<?php

use App\Http\Controllers\Export\BankController;
use App\Http\Controllers\Export\BillOfLadingController;
use App\Http\Controllers\Export\CommercialInvoiceController;
use App\Http\Controllers\Export\PackingListController;
use App\Http\Controllers\Export\ShipmentAdviseController;
use App\Http\Controllers\Export\CurrencyController;
use App\Http\Controllers\Export\ExportOrderController;
use App\Http\Controllers\Export\ExportSodaFieldController;
use App\Http\Controllers\Export\IncoTermController;
use App\Http\Controllers\Export\ModeOfTermController;
use App\Http\Controllers\Export\ShipmentCompanyController;
use App\Http\Controllers\Export\GaftaController;
use App\Http\Controllers\Export\ShipmentCountryController;
use App\Http\Controllers\Export\WorkingDayController;
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
use App\Http\Controllers\Export\ExportOuterItemController;
use App\Http\Controllers\Export\DocumentListController;
use App\Http\Controllers\Export\ExportOrderAddendumController;
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

// shipment company
Route::resource('shipment-company', ShipmentCompanyController::class);
Route::post('/get-shipment-company', [ShipmentCompanyController::class, 'getTable'])->name('get.shipment-company');

// gafta
Route::resource('gafta', GaftaController::class);
Route::post('/get-gafta', [GaftaController::class, 'getTable'])->name('get.gafta');

// shipment country
Route::resource('shipment-country', ShipmentCountryController::class);
Route::post('/get-shipment-country', [ShipmentCountryController::class, 'getTable'])->name('get.shipment-country');

// working days
Route::resource('working-days', WorkingDayController::class);
Route::post('/get-working-days', [WorkingDayController::class, 'getTable'])->name('get.working-days');

// export order
Route::resource('export-order', ExportOrderController::class);
Route::post('/get-export-order', [ExportOrderController::class, 'getExportOrderTable'])->name('get.export-order');
Route::get('/export-order/{id}/print', [ExportOrderController::class, 'print'])->name('export-order.print');

// c freight
Route::resource('c-freight', \App\Http\Controllers\Export\CFreightController::class);
Route::post('/get-c-freight', [\App\Http\Controllers\Export\CFreightController::class, 'getList'])->name('get.c-freight');
Route::get('/c-freight/get-export-order-details/{id}', [\App\Http\Controllers\Export\CFreightController::class, 'getExportOrderDetails'])->name('c-freight.get-export-order-details');
Route::post('/c-freight/{id}/add-rate', [\App\Http\Controllers\Export\CFreightController::class, 'addRate'])->name('c-freight.add-rate');
Route::post('/c-freight/{id}/approve-rate', [\App\Http\Controllers\Export\CFreightController::class, 'approveRate'])->name('c-freight.approve-rate');
Route::post('/c-freight/delete-rate/{id}', [\App\Http\Controllers\Export\CFreightController::class, 'deleteRate'])->name('c-freight.delete-rate');
Route::get('/c-freight/edit-request/{id}', [\App\Http\Controllers\Export\CFreightController::class, 'editRequest'])->name('c-freight.edit-request');
Route::get('/c-freight/show-booking/{id}', [\App\Http\Controllers\Export\CFreightController::class, 'showBooking'])->name('c-freight.show-booking');

// export delivery order
Route::resource('export-delivery-order', ExportDeliveryOrderController::class);
Route::post('/get-export-delivery-order', [ExportDeliveryOrderController::class, 'getExportDeliveryOrderTable'])->name('get.export-delivery-order');
Route::get('/get-export-order-details/{id}', [ExportDeliveryOrderController::class, 'getExportOrderDetails'])->name('export.get-export-order-details');
Route::get('/get-orders-by-buyer/{buyerId}', [ExportDeliveryOrderController::class, 'getOrdersByBuyer'])->name('export.get-orders-by-buyer');
Route::get('/get-arrival-locations', [ExportDeliveryOrderController::class, 'getArrivalLocations'])->name('export.get-arrival-locations');
Route::get('/get-sub-arrival-locations', [ExportDeliveryOrderController::class, 'getSubArrivalLocations'])->name('export.get-sub-arrival-locations');
Route::get('/export-order/get-quotation-details/{id}', [ExportOrderController::class, 'getQuotationDetails'])->name('export-order.get-quotation-details');

// export loading program (Request Stage 1)
Route::resource('export-loading-program', ExportLoadingProgramController::class);
Route::post('/get-export-loading-program', [ExportLoadingProgramController::class, 'getList'])->name('get.export-loading-program');
Route::get('/fetch-export-orders-by-location', [ExportLoadingProgramController::class, 'fetchExportOrdersByLocation'])->name('fetch.export.orders.by.location');
Route::get('/get-export-order-related-data', [ExportLoadingProgramController::class, 'getExportOrderRelatedData'])->name('get.export-order.related.data');
Route::get('/get-delivery-orders-by-export-order-loading', [ExportLoadingProgramController::class, 'getDeliveryOrdersByExportOrder'])->name('get.delivery-orders.by.export-order.loading');
Route::get('/get-delivery-orders-by-export-order-loading-edit', [ExportLoadingProgramController::class, 'getDeliveryOrdersByExportOrderEdit'])->name('get.delivery-orders.by.export-order.loading.edit');

// export loading program (Completion Stage 2)
Route::get('/export-loading-program-complete-show/{id}', [ExportLoadingProgramController::class, 'completeShow'])->name('export-loading-program-complete.show');
Route::get('/export-loading-program-complete', [ExportLoadingProgramController::class, 'completeIndex'])->name('export-loading-program-complete.index');
Route::post('/get-export-loading-program-complete', [ExportLoadingProgramController::class, 'getCompleteList'])->name('get.export-loading-program-complete');
Route::get('/export-loading-program-complete/{id}/edit', [ExportLoadingProgramController::class, 'completeEdit'])->name('export-loading-program-complete.edit');
Route::put('/export-loading-program-complete/{id}', [ExportLoadingProgramController::class, 'completeUpdate'])->name('export-loading-program-complete.update');

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

// export outer items
Route::resource('export-outer-item', ExportOuterItemController::class);
Route::post('/get-export-outer-item', [ExportOuterItemController::class, 'getList'])->name('get.export-outer-item');
Route::get('/get-export-outer-item-ticket-data/{id}', [ExportOuterItemController::class, 'getTicketData'])->name('export.getOuterItemTicketData');

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
Route::get('/export-delivery-challan/daily-dispatch', [ExportDeliveryChallanController::class, 'dailyDispatch'])->name('export-delivery-challan.daily-dispatch');
Route::post('/export-delivery-challan/daily-dispatch/filters', [ExportDeliveryChallanController::class, 'getDailyDispatchFilters'])->name('export-delivery-challan.daily-dispatch.filters');
Route::post('/export-delivery-challan/daily-dispatch/report', [ExportDeliveryChallanController::class, 'generateDailyDispatchReport'])->name('export-delivery-challan.daily-dispatch.report');
Route::resource('export-delivery-challan', ExportDeliveryChallanController::class);
Route::post('/get-export-delivery-challan', [ExportDeliveryChallanController::class, 'getList'])->name('get.export-delivery-challan.list');
Route::get('/export-delivery-challan/{delivery_challan}/view', [ExportDeliveryChallanController::class, 'view'])->name('export-delivery-challan.view');
Route::get('/get/export/dc-no', [ExportDeliveryChallanController::class, 'getNumber'])->name('get.export-delivery-challan.getNumber');
Route::get('/export-delivery-challan/get-do-against-customer', [ExportDeliveryChallanController::class, 'get_delivery_orders'])->name('get.export-delivery-challan.get-do');
Route::get('/get-export-delivery-challan-ticket-items', [ExportDeliveryChallanController::class, 'getItemsByTickets'])->name('export-delivery-challan.get-ticket-items');
Route::get('/get-export-delivery-challan-tickets', [ExportDeliveryChallanController::class, 'getTickets'])->name('export-delivery-challan.get-tickets');
Route::get('/get-export-tickets-with-dispatch-qc', [ExportDeliveryChallanController::class, 'getTicketsWithDispatchQc'])->name('export-delivery-challan.get-tickets-with-dispatch-qc');
Route::get('/get-export-ticket-data-for-dc', [ExportDeliveryChallanController::class, 'getTicketDataForDC'])->name('export-delivery-challan.get-ticket-data');
Route::get('/get-export-delivery-challan-labours', [ExportDeliveryChallanController::class, 'getLaboursByLocations'])->name('export-delivery-challan.get-labours');


// bill of lading
Route::resource('bill-of-lading', BillOfLadingController::class);
Route::post('/get-bill-of-lading', [BillOfLadingController::class, 'getList'])->name('get.bill-of-lading');
Route::get('/get-bill-of-lading-related-data', [BillOfLadingController::class, 'getRelatedData'])->name('get.bill-of-lading.related.data');
Route::get('/get/export/bill-of-lading-no', [BillOfLadingController::class, 'getNumber'])->name('get.bill-of-lading.getNumber');
Route::get('/get-bill-of-lading-form-es', [BillOfLadingController::class, 'getFormEsByExportOrder'])->name('get.bill-of-lading.form-es');
Route::get('/get-bill-of-lading-delivery-challans', [BillOfLadingController::class, 'getDeliveryChallansByFormEs'])->name('get.bill-of-lading.delivery-challans');
Route::get('/get-form-e-usage-details/{id}', [ExportDeliveryOrderController::class, 'getFormEUsage'])->name('export-delivery-order.form-e-usage');

// export form-e
Route::resource('export-form-e', App\Http\Controllers\Export\ExportFormEController::class);
Route::post('/get-export-form-e', [App\Http\Controllers\Export\ExportFormEController::class, 'getExportFormETable'])->name('get.export-form-e');
Route::get('/get-export-order-details-form-e/{id}', [App\Http\Controllers\Export\ExportFormEController::class, 'getExportOrderDetails'])->name('export.get-export-order-details-form-e');
Route::get('/get-orders-by-buyer-form-e/{buyerId}', [App\Http\Controllers\Export\ExportFormEController::class, 'getOrdersByBuyer'])->name('export.get-orders-by-buyer-form-e');
Route::get('/get-form-es-by-order/{orderId}', [App\Http\Controllers\Export\ExportFormEController::class, 'getFormEsByOrder'])->name('export.get-form-es-by-order');
Route::get('/get-job-orders-by-order-form-e/{orderId}', [App\Http\Controllers\Export\ExportFormEController::class, 'getJobOrdersByOrder'])->name('export.get-job-orders-by-order-form-e');


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
Route::get('/export-order/customer-consignees/{customerId}', [ExportOrderController::class, 'getCustomerConsignees'])->name('export-order.customer-consignees');
Route::get('/export-order/company-banks/{companyId}', [ExportOrderController::class, 'getCompanyBanks'])->name('export-order.company-banks');

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
Route::get('/get/export/commercial-invoice-no', [CommercialInvoiceController::class, 'getNumber'])->name('get.commercial-invoice.getNumber');
Route::get('/get-commercial-invoice-bills', [CommercialInvoiceController::class, 'getBillOfLadingsByExportOrder'])->name('get.commercial-invoice.bills');
Route::get('/get-commercial-invoice-related-data', [CommercialInvoiceController::class, 'getRelatedData'])->name('get.commercial-invoice.related.data');

// packing list
Route::resource('packing-list', PackingListController::class);
Route::post('/get-packing-list', [PackingListController::class, 'getPackingListTable'])->name('get.packing-list');
Route::get('/get-packing-list-related-data', [PackingListController::class, 'getRelatedData'])->name('get.packing-list.related.data');
Route::get('/get-packing-list-commercial-invoices', [PackingListController::class, 'getCommercialInvoicesByExportOrder'])->name('get.packing-list.commercial-invoices');
Route::get('/packing-list/{id}/container-list', [PackingListController::class, 'containerList'])->name('packing-list.container-list');

// shipment advise
Route::resource('shipment-advise', ShipmentAdviseController::class);
Route::post('/get-shipment-advise', [ShipmentAdviseController::class, 'getList'])->name('get.shipment-advise');
Route::get('/get-shipment-advise-related-data', [ShipmentAdviseController::class, 'getRelatedData'])->name('get.shipment-advise.related.data');
Route::get('/get-shipment-advise-packing-lists', [ShipmentAdviseController::class, 'getPackingListsByExportOrder'])->name('get.shipment-advise.packing-lists');

// export soda field 
Route::resource('export-soda-field', ExportSodaFieldController::class);
Route::post('/get-export-soda-field', [ExportSodaFieldController::class, 'getExportSodaFieldTable'])->name('get.export-soda-field');
Route::post('/export-soda-field/update-status/{id}', [ExportSodaFieldController::class, 'updateStatus'])->name('export-soda-field.update-status');

// document list
Route::resource('document-list', DocumentListController::class);
Route::post('/get-document-list', [DocumentListController::class, 'getList'])->name('get.document-list');

// export order addendum
Route::resource('export-order-addendum', ExportOrderAddendumController::class);
Route::post('/get-export-order-addendum', [ExportOrderAddendumController::class, 'getList'])->name('export-order-addendum.getList');
