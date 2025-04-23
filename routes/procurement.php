<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Procurement\RawMaterial\{
    PurchaseRequestController
};


Route::prefix('raw-material')->name('raw-material.')->group(function () {
    Route::resource('purchase-request', PurchaseRequestController::class);
    Route::post('get-purchase-request', [PurchaseRequestController::class, 'getList'])->name('get.purchase-request');

});
