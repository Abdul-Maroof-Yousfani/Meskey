<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Master\{RegionController,CategoryController};

Route::group(['middleware' => ['auth', 'check.company']], function () {
    Route::prefix('master')->group(function () {
        Route::resource('regions', RegionController::class);
        Route::post('/get-regions', [RegionController::class, 'getList'])->name('get.regions');


        Route::resource('category', CategoryController::class);
        Route::post('/get-category', [CategoryController::class, 'getList'])->name('get.category');
    });
});

