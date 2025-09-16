<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\Procurement\Store\PaymentCalculationController;
use App\Helpers\ApiResponse;
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

Route::prefix('v1/auth')->middleware(['api', 'throttle:60,1'])->group(function () {
    Route::put('login', [LoginController::class, 'login']);
    Route::post('reset-password', [LoginController::class, 'resetPassword']);
    Route::get('validate-token', [LoginController::class, 'validateToken'])->middleware('auth:sanctum');
    Route::get('logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware(['auth:sanctum', 'api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('v1/payment')->middleware('throttle:60,1')->group(function () {
        Route::get('/calculate', [PaymentCalculationController::class, 'calculatePayment']);
        Route::get('/ticket/{ticketId}/sauda/{saudaType}', [PaymentCalculationController::class, 'calculateTicketPaymentWithSauda']);
        Route::get('/ticket/{ticketId}', [PaymentCalculationController::class, 'calculateTicketPayment']);
        Route::get('/ticket/{ticketId}/summary', [PaymentCalculationController::class, 'getPaymentSummary']);
        Route::post('/calculate-bulk', [PaymentCalculationController::class, 'calculateMultiplePayments']);
    });
});

Route::fallback(function () {
    return ApiResponse::error('Endpoint not found', 404, [
        'success' => false,
        'message' => 'Endpoint not found'
    ]);
});
