<?php
use App\Http\Controllers\Reports\Arrival\{
    ArrivalReportController,
    TruckDetailReportController,
    TruckDetailGateReportController,
    TruckTimestampReportController,
    WeighbridgeSampleMoneyReportController,
    QcAnalysisReportController,
    ProductWiseArrivalReportController,
    BagWiseArrivalReportController,
    StationWiseQCAnalysisReportController,
    CustomQcSampleReportController,
    TruckSummaryReportController
};
use Illuminate\Support\Facades\Route;


Route::prefix('arrival')->group(function () {
  Route::resource('arrival-history', ArrivalReportController::class);
  Route::post('/get-arrival-history', [ArrivalReportController::class, 'getArrivalReport'])->name('reports.arrival.get.arrival-history');
  Route::resource('truck-detail', TruckDetailReportController::class);
  Route::post('/get-truck-detail', [TruckDetailReportController::class, 'getList'])->name('reports.arrival.get.truck-detail');
  Route::resource('truck-detail-gate', TruckDetailGateReportController::class);
  Route::post('/get-truck-detail-gate', [TruckDetailGateReportController::class, 'getList'])->name('reports.arrival.get.truck-detail-gate');
  Route::resource('truck-summary', TruckSummaryReportController::class);
  Route::post('/get-truck-summary', [TruckSummaryReportController::class, 'getList'])->name('reports.arrival.get.truck-summary');

  Route::resource('truck-timestamp', TruckTimestampReportController::class);
  Route::post('/get-truck-timestamp', [TruckTimestampReportController::class, 'getList'])->name('reports.arrival.get.truck-timestamp');
  Route::resource('weighbridge-sample-money', WeighbridgeSampleMoneyReportController::class);
  Route::post('/get-weighbridge-sample-money', [WeighbridgeSampleMoneyReportController::class, 'getList'])->name('reports.arrival.get.weighbridge-sample-money');
  Route::resource('qc-analysis', QcAnalysisReportController::class);
  Route::post('/get-qc-analysis', [QcAnalysisReportController::class, 'getList'])->name('reports.arrival.get.qc-analysis');
  Route::resource('product-wise', ProductWiseArrivalReportController::class);
  Route::post('/get-product-wise', [ProductWiseArrivalReportController::class, 'getList'])->name('reports.arrival.get.product-wise');
  Route::resource('bag-wise', BagWiseArrivalReportController::class);
  Route::post('/get-bag-wise', [BagWiseArrivalReportController::class, 'getList'])->name('reports.arrival.get.bag-wise');
  Route::resource('station-wise-qc-analysis', StationWiseQCAnalysisReportController::class);
  Route::post('/get-station-wise-qc-analysis', [StationWiseQCAnalysisReportController::class, 'getList'])->name('reports.arrival.get.station-wise-qc-analysis');
  Route::resource('custom-qc-sample', CustomQcSampleReportController::class);
  Route::post('/get-custom-qc-sample', [CustomQcSampleReportController::class, 'getList'])->name('reports.arrival.get.custom-qc-sample');
});



