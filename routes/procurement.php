<?php

use App\Http\Controllers\IndicativePriceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Procurement\RawMaterial\{
    PurchaseRequestController,
    PurchaseOrderController,
    PurchaseSamplingRequestController
};


Route::prefix('raw-material')->name('raw-material.')->group(function () {
    Route::resource('purchase-request', PurchaseRequestController::class);
    Route::post('get-purchase-request', [PurchaseRequestController::class, 'getList'])->name('get.purchase-request');

    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::post('get-purchase-order', [PurchaseOrderController::class, 'getList'])->name('get.purchase-order');
    Route::get('/getMainSlabByProduct', [PurchaseOrderController::class, 'getMainSlabByProduct'])->name('getMainSlabByProduct');
    Route::post('/generate-contract-number', [PurchaseOrderController::class, 'getContractNumber'])->name('generate.contract.number');



      Route::resource('purchase-sampling-request', PurchaseSamplingRequestController::class);
    Route::post('get-purchase-sampling-request', [PurchaseSamplingRequestController::class, 'getList'])->name('get.purchase-sampling-request');
});

Route::prefix('indicative-prices')->group(function () {
    Route::get('/', [IndicativePriceController::class, 'index'])->name('indicative-prices.index');
    Route::get('/get-list', [IndicativePriceController::class, 'getList'])->name('get.indicative-prices');
    Route::post('/', [IndicativePriceController::class, 'store'])->name('indicative-prices.store');
    Route::put('/{id}', [IndicativePriceController::class, 'update'])->name('indicative-prices.update');
    Route::delete('/{id}', [IndicativePriceController::class, 'destroy'])->name('indicative-prices.destroy');
    Route::get('/reports', [IndicativePriceController::class, 'reportsView'])->name('indicative-prices.reports');
    Route::post('/reports-list', [IndicativePriceController::class, 'reports'])->name('indicative-prices.reports.get-list');
});
