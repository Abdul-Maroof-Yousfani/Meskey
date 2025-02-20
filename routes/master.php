<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Master\{
    RegionController, CategoryController, UnitOfMeasureController,
    ProductController, ProductSlabController, SupplierController,
    BrokerController, ProductSlabTypeController
    };


Route::resource('regions', RegionController::class);
Route::post('/get-regions', [RegionController::class, 'getList'])->name('get.regions');


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

