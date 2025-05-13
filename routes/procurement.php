<?php

use App\Http\Controllers\IndicativePriceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Procurement\RawMaterial\{
    GateBuyingController,
    PurchaseRequestController,
    PurchaseOrderController,
    PurchaseSamplingRequestController
};

Route::prefix('raw-material')->name('raw-material.')->group(function () {
    Route::resource('purchase-request', PurchaseRequestController::class);
    Route::post('get-purchase-request', [PurchaseRequestController::class, 'getList'])->name('get.purchase-request');

    Route::resource('gate-buying', GateBuyingController::class);
    Route::post('get-gate-buying', [GateBuyingController::class, 'getList'])->name('get.gate-buying');
    Route::get('/getGateBuyingMainSlabByProduct', [GateBuyingController::class, 'getMainSlabByProduct'])->name('getGateBuyingMainSlabByProduct');

    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::post('get-purchase-order', [PurchaseOrderController::class, 'getList'])->name('get.purchase-order');
    Route::get('/getMainSlabByProduct', [PurchaseOrderController::class, 'getMainSlabByProduct'])->name('getMainSlabByProduct');
    Route::post('/generate-contract-number', [PurchaseOrderController::class, 'getContractNumber'])->name('generate.contract.number');



      Route::resource('purchase-sampling-request', PurchaseSamplingRequestController::class);
    Route::post('get-purchase-sampling-request', [PurchaseSamplingRequestController::class, 'getList'])->name('get.purchase-sampling-request');
});

Route::resource('indicative-prices', IndicativePriceController::class)->except(['create', 'show', 'edit']);
Route::prefix('indicative-prices')->name('indicative-prices.')->group(function () {
    Route::get('/reports', [IndicativePriceController::class, 'reportsView'])->name('reports');
    Route::post('/reports-list', [IndicativePriceController::class, 'reports'])->name('reports.get-list');
});
