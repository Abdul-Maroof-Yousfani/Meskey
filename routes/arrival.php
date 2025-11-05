<?php

use App\Http\Controllers\MasterControl\ArrivalMasterRevertController;
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
    FirstWeighbridgeController,
    FreightController
};
use App\Models\Master\CompanyLocation;

Route::resource('ticket', TicketController::class);
Route::post('/get-ticket', [TicketController::class, 'getList'])->name('get.ticket');
Route::put('/ticket/{ticket}/confirm-bilty-return', [TicketController::class, 'confirmBiltyReturn'])->name('ticket.confirm-bilty-return');
Route::get('/ticket-revert/{ticket}', [ArrivalMasterRevertController::class, 'arrivalRevert'])->name('ticket.arrival-revert');
Route::post('/ticket-revert/{ticket}', [ArrivalMasterRevertController::class, 'update'])->name('ticket.arrival-revert.update');
Route::get('/get-ticket-number/{locationId}', [TicketController::class, 'getTicketNumber']);

Route::resource('initialsampling', InitialSamplingController::class);
Route::resource('initial-resampling', InitialSamplingController::class);
Route::post('/get-initialsampling', [InitialSamplingController::class, 'getList'])->name('get.initialsampling');
Route::post('/get-initial-resampling', [InitialSamplingController::class, 'getList'])->name('get.initial-resampling');
Route::post('/initial-sampling/update-status', [InitialSamplingController::class, 'updateStatus'])->name('initialsampling.updateStatus');

Route::get('/get-contracts/{locationId}', [TicketController::class, 'getContractsByLocation']);
Route::get('/get-suppliers/{locationId}', [TicketController::class, 'getSuppliersByLocation']);

Route::resource('sampling-monitoring', SamplingMonitoringController::class);
Route::post('/get-sampling-monitoring', [SamplingMonitoringController::class, 'getList'])->name('get.sampling-monitoring');

Route::resource('location-transfer', ArrivalLocationTransferController::class);
Route::post('/get-location-transfer', [ArrivalLocationTransferController::class, 'getList'])->name('get.location-transfer');

Route::resource('inner-sampling-request', InnersampleRequestController::class);
Route::post('/get-inner-sampling-request', [InnersampleRequestController::class, 'getList'])->name('get.inner-sampling-request');

Route::resource('inner-sampling', InnersamplingController::class);
Route::post('/get-inner-sampling', [InnersamplingController::class, 'getList'])->name('get.inner-sampling');
Route::resource('inner-resampling', InnersamplingController::class);
Route::post('/get-inner-resampling', [InnersamplingController::class, 'getList'])->name('get.inner-resampling');

Route::resource('arrival-approve', ArrivalApproveController::class);
Route::post('/get-arrival-approve', [ArrivalApproveController::class, 'getList'])->name('get.arrival-approve');

Route::resource('first-weighbridge', FirstWeighbridgeController::class);
Route::post('/get-first-weighbridge', [FirstWeighbridgeController::class, 'getList'])->name('get.first-weighbridge');
Route::get('/getFirstWeighbridgeRelatedData', [FirstWeighbridgeController::class, 'getFirstWeighbridgeRelatedData'])->name('getFirstWeighbridgeRelatedData');



Route::resource('second-weighbridge', SecondWeighbridgeController::class);
Route::post('/get-second-weighbridge', [SecondWeighbridgeController::class, 'getList'])->name('get.second-weighbridge');
Route::get('/getSecondWeighbridgeRelatedData', [SecondWeighbridgeController::class, 'getSecondWeighbridgeRelatedData'])->name('getSecondWeighbridgeRelatedData');

Route::resource('freight', FreightController::class);
Route::post('/get-freight', [FreightController::class, 'getList'])->name('get.freight');
Route::get('/get-freight-form', [FreightController::class, 'getFreightForm'])->name('freight.getFreightForm');

// Route::group(['prefix' => 'freight', 'as' => 'freight.'], function () {
// Route::get('/', [FreightController::class, 'index'])->name('index');
// Route::get('/get-list', [FreightController::class, 'getList'])->name('get');
// Route::get('/create', [FreightController::class, 'create'])->name('create');
// Route::post('/store', [FreightController::class, 'store'])->name('store');
// Route::get('/edit/{id}', [FreightController::class, 'edit'])->name('edit');
// Route::put('/update/{freight}', [FreightController::class, 'update'])->name('update');
// Route::delete('/destroy/{freight}', [FreightController::class, 'destroy'])->name('destroy');
// });

Route::resource('arrival-slip', ArrivalSlipController::class);
Route::post('/get-arrival-slip', [ArrivalSlipController::class, 'getList'])->name('get.arrival-slip');
