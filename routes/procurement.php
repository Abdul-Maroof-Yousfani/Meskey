<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Arrival\{
    TicketController
};


Route::resource('ticket', TicketController::class);
Route::post('/get-ticket', [TicketController::class, 'getList'])->name('get.ticket');


