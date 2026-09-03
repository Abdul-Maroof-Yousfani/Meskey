<?php

use App\Http\Controllers\Reports\Arrival\{ArrivalReportController, TruckDetailReportController};
use Illuminate\Support\Facades\Route;


Route::prefix('arrival')->group(function () {
  Route::resource('arrival-history', ArrivalReportController::class);
  Route::post('/get-arrival-history', [ArrivalReportController::class, 'getArrivalReport'])->name('reports.arrival.get.arrival-history');
  Route::resource('truck-detail', TruckDetailReportController::class);
  Route::post('/get-truck-detail', [TruckDetailReportController::class, 'getList'])->name('reports.arrival.get.truck-detail');
});


