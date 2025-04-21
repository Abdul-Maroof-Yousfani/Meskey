<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Arrival\{
    TicketController,
    InitialSamplingController,
    SamplingMonitoringController,
    InnersamplingController,
    InnersampleRequestController,
    ArrivalLocationTransferController,
    ArrivalApproveController,
    SecondWeighbridgeController,
    ArrivalSlipController,
    FirstWeighbridgeController
};


Route::resource('ticket', TicketController::class);
Route::post('/get-ticket', [TicketController::class, 'getList'])->name('get.ticket');

Route::resource('initialsampling', InitialSamplingController::class);
Route::post('/get-initialsampling', [InitialSamplingController::class, 'getList'])->name('get.initialsampling');
Route::post('/initial-sampling/update-status', [InitialSamplingController::class, 'updateStatus'])->name('initialsampling.updateStatus');



Route::resource('sampling-monitoring', SamplingMonitoringController::class);
Route::post('/get-sampling-monitoring', [SamplingMonitoringController::class, 'getList'])->name('get.sampling-monitoring');

Route::resource('location-transfer', ArrivalLocationTransferController::class);
Route::post('/get-location-transfer', [ArrivalLocationTransferController::class, 'getList'])->name('get.location-transfer');

Route::resource('inner-sampling-request', InnersampleRequestController::class);
Route::post('/get-inner-sampling-request', [InnersampleRequestController::class, 'getList'])->name('get.inner-sampling-request');

Route::resource('inner-sampling', InnersamplingController::class);
Route::post('/get-inner-sampling', [InnersamplingController::class, 'getList'])->name('get.inner-sampling');

Route::resource('arrival-approve', ArrivalApproveController::class);
Route::post('/get-arrival-approve', [ArrivalApproveController::class, 'getList'])->name('get.arrival-approve');


Route::resource('first-weighbridge', FirstWeighbridgeController::class);
Route::post('/get-first-weighbridge', [FirstWeighbridgeController::class, 'getList'])->name('get.first-weighbridge');
Route::get('/getFirstWeighbridgeRelatedData', [FirstWeighbridgeController::class, 'getFirstWeighbridgeRelatedData'])->name('getFirstWeighbridgeRelatedData');



Route::resource('second-weighbridge', SecondWeighbridgeController::class);
Route::post('/get-second-weighbridge', [SecondWeighbridgeController::class, 'getList'])->name('get.second-weighbridge');
Route::get('/getSecondWeighbridgeRelatedData', [SecondWeighbridgeController::class, 'getSecondWeighbridgeRelatedData'])->name('getSecondWeighbridgeRelatedData');

Route::resource('arrival-slip', ArrivalSlipController::class);
Route::post('/get-arrival-slip', [ArrivalSlipController::class, 'getList'])->name('get.arrival-slip');
