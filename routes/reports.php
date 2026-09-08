<?php

use App\Http\Controllers\Reports\Arrival\{ArrivalReportController, TruckDetailReportController, TruckTimestampReportController, WeighbridgeSampleMoneyReportController, QcAnalysisReportController};
use Illuminate\Support\Facades\Route;


Route::prefix('arrival')->group(function () {
  Route::resource('arrival-history', ArrivalReportController::class);
  Route::post('/get-arrival-history', [ArrivalReportController::class, 'getArrivalReport'])->name('reports.arrival.get.arrival-history');
  Route::resource('truck-detail', TruckDetailReportController::class);
  Route::post('/get-truck-detail', [TruckDetailReportController::class, 'getList'])->name('reports.arrival.get.truck-detail');
  Route::resource('truck-timestamp', TruckTimestampReportController::class);
  Route::post('/get-truck-timestamp', [TruckTimestampReportController::class, 'getList'])->name('reports.arrival.get.truck-timestamp');
  Route::resource('weighbridge-sample-money', WeighbridgeSampleMoneyReportController::class);
  Route::post('/get-weighbridge-sample-money', [WeighbridgeSampleMoneyReportController::class, 'getList'])->name('reports.arrival.get.weighbridge-sample-money');
  Route::resource('qc-analysis', QcAnalysisReportController::class);
  Route::post('/get-qc-analysis', [QcAnalysisReportController::class, 'getList'])->name('reports.arrival.get.qc-analysis');
});


