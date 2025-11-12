<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Helpers\ApiResponse;
use App\Http\Controllers\API\Arrival\ArrivalApproveController;
use App\Http\Controllers\API\Arrival\InnerSampleRequestController;
use App\Http\Controllers\API\MasterController;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\Procurement\Store\PaymentCalculationController;

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

Route::prefix('v1')->middleware(['api', 'throttle:60,1'])->group(function () {
    Route::prefix('auth')->controller(LoginController::class)->group(function () {
        Route::put('login', 'login');
        Route::post('reset-password', 'resetPassword');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->controller(LoginController::class)->group(function () {
            Route::get('validate-token', 'validateToken');
            Route::get('logout', 'logout');
        });

        Route::prefix('arrival')->group(function () {
            Route::prefix('inner-sampling-request')->controller(InnerSampleRequestController::class)->group(function () {
                Route::post('store', 'store');
                Route::get('available-tickets', 'getAvailableTickets');
            });

            Route::prefix('approve')->controller(ArrivalApproveController::class)->group(function () {
                Route::get('available-tickets', 'getAvailableTickets');
                Route::post('store', 'store');
            });
            Route::get('available-tickets-with-status', [ArrivalApproveController::class, 'getAvailableTicketsInnerSamplingStatus']);

        });

        Route::prefix('master')->controller(MasterController::class)->group(function () {
            Route::get('bag-types', 'getBagTypes');
            Route::get('bag-conditions', 'getBagConditions');
            Route::get('bag-packings', 'getBagPackings');
            Route::get('gala', 'getGala');
        });

        // Route::get('user', fn(Request $r) => $r->user());

        Route::prefix('payment')->controller(PaymentCalculationController::class)->group(function () {
            Route::get('calculate', 'calculatePayment');
            Route::get('ticket/{ticketId}/sauda/{saudaType}', 'calculateTicketPaymentWithSauda');
            Route::get('ticket/{ticketId}', 'calculateTicketPayment');
            Route::get('ticket/{ticketId}/summary', 'getPaymentSummary');
            Route::post('calculate-bulk', 'calculateMultiplePayments');
        });
    });
});

Route::fallback(fn() => ApiResponse::error('Endpoint not found', 404, [
    'success' => false,
    'message' => 'Endpoint not found'
]));
