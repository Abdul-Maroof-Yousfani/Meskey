<?php

use App\Http\Controllers\ApprovalsModule\ApprovalModuleController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Master\{
    ColorController,
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
    ArrivalSubLocationController,
    PlantController,
    ProductionMachineController,
    TruckTypeController,
    StationController,
    CompanyLocationController,
    DivisionController,
    QcReliefController,
    TruckSizeRangeController,
    VendorsController,
    FumigationCompanyController,
    InspectionCompanyController,
    BrandsController,
    SizeController
};


//Route::resource('regions', RegionController::class);
//Route::post('/get-regions', [RegionController::class, 'getList'])->name('get.regions');


Route::resource("color", ColorController::class);
Route::post("/get-colors", [ColorController::class, "getList"])->name("get.colors");

Route::resource("size", SizeController::class);
Route::post("/get-sizes", [SizeController::class, "getList"])->name("get.sizes");

Route::resource('category', CategoryController::class);
Route::post('/get-category', [CategoryController::class, 'getList'])->name('get.category');
Route::get('/get-categories', [CategoryController::class, 'getCategories'])->name('get.categories');

Route::resource('unit_of_measure', UnitOfMeasureController::class);
Route::post('/get-unit_of_measure', [UnitOfMeasureController::class, 'getList'])->name('get.unit_of_measure');

Route::resource('truck-size-ranges', TruckSizeRangeController::class);
Route::post('get-truck-size-ranges', [TruckSizeRangeController::class, 'getList'])->name('get.truck_size_ranges');

Route::resource('approval-modules', ApprovalModuleController::class);
Route::post('/get-approval-modules', [ApprovalModuleController::class, 'getList'])->name('get.approval-modules');

Route::resource('product', ProductController::class);
Route::post('/get-product', [ProductController::class, 'getList'])->name('get.product');
Route::get('/get-items', [ProductController::class, 'getItems'])->name('get.items');

Route::resource('supplier', SupplierController::class);
Route::post('/get-supplier', [SupplierController::class, 'getList'])->name('get.supplier');

Route::resource('vendor', VendorsController::class);
Route::post('/get-vendor', [VendorsController::class, 'getList'])->name('get.vendor');

Route::resource('division', DivisionController::class);
Route::post('/get-division', [DivisionController::class, 'getList'])->name('get.division');

Route::resource('broker', BrokerController::class);
Route::post('/get-broker', [BrokerController::class, 'getList'])->name('get.broker');

Route::resource('product-slab-type', ProductSlabTypeController::class);
Route::post('/get-product-slab-type', [ProductSlabTypeController::class, 'getList'])->name('get.product-slab-type');

Route::resource('product-slab', ProductSlabController::class);
Route::post('/get-product-slab', [ProductSlabController::class, 'getList'])->name('get.product-slab');
Route::post('/product-slab/store-multiple', [ProductSlabController::class, 'storeMultiple'])->name('product-slab.store-multiple');
Route::delete('/product-slab/destroy-multiple/{productId}', [ProductSlabController::class, 'destroyMultiple'])->name('product-slab.destroy-multiple');
Route::put('/product-slab/update-multiple/{productId}', [ProductSlabController::class, 'updateMultiple'])->name('product-slab.update-multiple');

Route::resource('company-location', CompanyLocationController::class);
Route::post('/get-company-location', [CompanyLocationController::class, 'getList'])->name('get.company-location');

Route::resource('arrival-location', ArrivalLocationController::class);
Route::post('/get-arrival-location', [ArrivalLocationController::class, 'getList'])->name('get.arrival-location');

Route::resource('arrival-sub-location', ArrivalSubLocationController::class);
Route::post('/get-arrival-sub-location', [ArrivalSubLocationController::class, 'getList'])->name('get.arrival-sub-location');

Route::resource('plant', PlantController::class);
Route::post('/get-plant', [PlantController::class, 'getList'])->name('get.plant');

Route::resource('production-machine', ProductionMachineController::class);
Route::post('/get-production-machine', [ProductionMachineController::class, 'getList'])->name('get.production-machine');

Route::resource('truck-type', TruckTypeController::class);
Route::post('/get-truck-type', [TruckTypeController::class, 'getList'])->name('get.truck-type');

Route::resource('station', StationController::class);
Route::post('/get-station', [StationController::class, 'getList'])->name('get.station');

Route::resource('fumigation-company', FumigationCompanyController::class);
Route::post('/get-fumigation-company', [FumigationCompanyController::class, 'getList'])->name('get.fumigation-company');

Route::resource('inspection-company', InspectionCompanyController::class);
Route::post('/get-inspection-company', [InspectionCompanyController::class, 'getList'])->name('get.inspection-company');

Route::resource('brands', BrandsController::class);
Route::post('/get-brands', [BrandsController::class, 'getList'])->name('get.brands');

Route::resource('account', AccountController::class);
Route::post('/get-account', [AccountController::class, 'getList'])->name('get.account');

Route::prefix('qc-relief')->group(function () {
    Route::get('/', [QcReliefController::class, 'index'])->name('qc-relief.index');
    Route::get('/get-parameters', [QcReliefController::class, 'getParameters'])->name('qc-relief.get-parameters');
    Route::post('/save-parameters', [QcReliefController::class, 'saveParameters'])->name('qc-relief.save-parameters');
});
