<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Arrival\{TicketController,InitialSamplingController,SamplingMonitoringController};


Route::resource('ticket', TicketController::class);
Route::post('/get-ticket', [TicketController::class, 'getList'])->name('get.ticket');

Route::resource('initialsampling', InitialSamplingController::class);
Route::post('/get-initialsampling', [InitialSamplingController::class, 'getList'])->name('get.initialsampling');



Route::resource('sampling-monitoring', SamplingMonitoringController::class);
Route::post('/get-sampling-monitoring', [SamplingMonitoringController::class, 'getList'])->name('get.sampling-monitoring');



