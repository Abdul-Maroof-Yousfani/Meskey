<?php

use App\Http\Controllers\Export\BankController;
use App\Http\Controllers\Export\CurrencyController;
use App\Http\Controllers\Export\IncoTermController;
use App\Http\Controllers\Export\ModeOfTermController;
use App\Http\Controllers\Export\ModeOfTransportController;
use Illuminate\Support\Facades\Route;

// mode of terms 
Route::resource('modeofterms', ModeOfTermController::class);
Route::post('/get-modes', [ModeOfTermController::class, 'getTable'])->name('get.modes');

// mode of transport 
Route::resource('modeoftransport', ModeOfTransportController::class);
Route::post('/get-transport-modes', [ModeOfTransportController::class, 'getTable'])->name('get.transport.modes');

// currency
Route::resource('currency', CurrencyController::class);
Route::post('/get-currency', [CurrencyController::class, 'getCurrencyTable'])->name('get.currency');

// incoterm
Route::resource('incoterm', IncoTermController::class);
Route::post('/get-incoterm', [IncoTermController::class, 'getIncotermTable'])->name('get.incoterm');

// bank
Route::resource('bank', BankController::class);
Route::post('/get-bank', [BankController::class, 'getBankTable'])->name('get.bank');