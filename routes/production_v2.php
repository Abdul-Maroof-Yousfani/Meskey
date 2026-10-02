<?php

use App\Http\Controllers\Production\v2\JobOrderV2Controller;
use Illuminate\Support\Facades\Route;

Route::name('production.')->prefix('v2')->group(function () {
    Route::resource('job-orders', JobOrderV2Controller::class);
    Route::post('get-job-orders', [JobOrderV2Controller::class, 'getList'])->name('job-orders.getList');
    Route::get('get-job-order-number', [JobOrderV2Controller::class, 'getNumber'])->name('job-orders.get-number');
    Route::get('get-phases-by-location/{locationId}', [JobOrderV2Controller::class, 'getPhasesByLocation'])->name('job-orders.get-phases');
});
