<?php

use App\Http\Controllers\Reports\ArrivalReportController;
use Illuminate\Support\Facades\Route;


Route::prefix('arrival')->group(function () {
  Route::resource('arrival-history', ArrivalReportController::class);
  Route::post('/get-arrival-history', [ArrivalReportController::class, 'getArrivalReport'])->name('reports.arrival.get.arrival-history');
});


