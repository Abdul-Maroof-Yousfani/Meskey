<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Master\{
    AccountController,
    RegionController,
    CategoryController,
    UnitOfMeasureController,
    ProductController,
    ProductSlabController,
    SupplierController,
    BrokerController,
    ProductSlabTypeController,
    ArrivalLocationController,
    TruckTypeController,
    StationController,
    CompanyLocationController
};


//Route::resource('regions', RegionController::class);
//Route::post('/get-regions', [RegionController::class, 'getList'])->name('get.regions');


Route::resource('category', CategoryController::class);
Route::post('/get-category', [CategoryController::class, 'getList'])->name('get.category');

Route::resource('unit_of_measure', UnitOfMeasureController::class);
Route::post('/get-unit_of_measure', [UnitOfMeasureController::class, 'getList'])->name('get.unit_of_measure');


Route::resource('product', ProductController::class);
Route::post('/get-product', [ProductController::class, 'getList'])->name('get.product');


Route::resource('supplier', SupplierController::class);
Route::post('/get-supplier', [SupplierController::class, 'getList'])->name('get.supplier');

Route::resource('broker', BrokerController::class);
Route::post('/get-broker', [BrokerController::class, 'getList'])->name('get.broker');

Route::resource('product-slab-type', ProductSlabTypeController::class);
Route::post('/get-product-slab-type', [ProductSlabTypeController::class, 'getList'])->name('get.product-slab-type');

Route::resource('product-slab', ProductSlabController::class);
Route::post('/get-product-slab', [ProductSlabController::class, 'getList'])->name('get.product-slab');

Route::resource('company-location', CompanyLocationController::class);
Route::post('/get-company-location', [CompanyLocationController::class, 'getList'])->name('get.company-location');

Route::resource('arrival-location', ArrivalLocationController::class);
Route::post('/get-arrival-location', [ArrivalLocationController::class, 'getList'])->name('get.arrival-location');

Route::resource('truck-type', TruckTypeController::class);
Route::post('/get-truck-type', [TruckTypeController::class, 'getList'])->name('get.truck-type');

Route::resource('station', StationController::class);
Route::post('/get-station', [StationController::class, 'getList'])->name('get.station');

Route::resource('account', AccountController::class);
Route::post('/get-account', [AccountController::class, 'getList'])->name('get.account');
