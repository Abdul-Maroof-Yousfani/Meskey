<?php

use App\Http\Controllers\Procurement\Store\PaymentCalculationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/payment')->middleware(['api', 'throttle:60,1'])->group(function () {
    Route::get('/calculate', [PaymentCalculationController::class, 'calculatePayment']);
    Route::get('/ticket/{ticketId}/sauda/{saudaType}', [PaymentCalculationController::class, 'calculateTicketPaymentWithSauda']);
    Route::get('/ticket/{ticketId}', [PaymentCalculationController::class, 'calculateTicketPayment']);
    Route::get('/ticket/{ticketId}/summary', [PaymentCalculationController::class, 'getPaymentSummary']);
    Route::post('/calculate-bulk', [PaymentCalculationController::class, 'calculateMultiplePayments']);
});
