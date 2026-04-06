<?php

use App\Http\Controllers\Export\BankController;
use App\Http\Controllers\Export\CommercialInvoiceController;
use App\Http\Controllers\Export\CurrencyController;
use App\Http\Controllers\Export\ExportOrderController;
use App\Http\Controllers\Export\ExportSodaFieldController;
use App\Http\Controllers\Export\IncoTermController;
use App\Http\Controllers\Export\ModeOfTermController;
use App\Http\Controllers\Export\ModeOfTransportController;
use App\Http\Controllers\Export\ProformaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Export\QuotationController;
use App\Http\Controllers\Export\ExportDeliveryOrderController;

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
Route::get('/export-order/get-quotation-details/{id}', [ExportOrderController::class, 'getQuotationDetails'])->name('export-order.get-quotation-details');

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