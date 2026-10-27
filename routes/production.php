<?php

use App\Http\Controllers\Finance\PaymentVoucherController;
use App\Http\Controllers\Production\JobOrderController;
use App\Http\Controllers\Production\JobOrderRawMaterialQcController;
use App\Http\Controllers\Production\MachinePlanSettingController;
use App\Http\Controllers\Production\ProductionInputAnalysisController;
use App\Http\Controllers\Production\ProductionOutputAnalysisController;
use App\Http\Controllers\Production\ProductionQualityCheckController;
use App\Http\Controllers\Production\ProductionVoucherController;
use App\Models\Production\JobOrder\JobOrderRawMaterialQc;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Production\PlantBreakdownController;
use App\Http\Controllers\Production\BagRequestController;
use App\Http\Controllers\Production\ProductionDryingController;
use App\Http\Controllers\Production\ProductionSteamParboilingController;
use App\Http\Controllers\Production\ProductionAnalysisRequestController;
use App\Http\Controllers\Production\ProductionMachineAnalysisController;
use App\Http\Controllers\Production\JobOrderRequestController;

Route::get('job-order-requests/get-number', [JobOrderRequestController::class, 'getNumber'])->name('job-order-requests.get-number');
Route::get('job-order-requests/get-details/{id}', [JobOrderRequestController::class, 'getRequestDetails'])->name('job-order-requests.get-details');
Route::resource('job-order-requests', JobOrderRequestController::class);
Route::post('get-job-order-requests', [JobOrderRequestController::class, 'getList'])->name('get.job_order_requests');
Route::get('job-orders/get-request-details/{id}', [JobOrderRequestController::class, 'getRequestDetails'])->name('job-orders.get-request-details');

Route::get('job-orders/get-number', [JobOrderController::class, 'getNumber'])->name('job-orders.get-number');
Route::resource('job-orders', JobOrderController::class);
Route::get('job-orders/{id}/print', [JobOrderController::class, 'printJobOrder'])->name('job-orders.print');
Route::post('get-job-orders', [JobOrderController::class, 'getList'])->name('get.job_orders');
Route::get('get-product-specs/{productId}', [JobOrderController::class, 'getProductSpecs'])->name('get.product_specs');
Route::get('get-export-order-details/{id}', [JobOrderController::class, 'getExportOrderDetails'])->name('get.export_order_details');
Route::get('get-arrival-locations', [JobOrderController::class, 'getArrivalLocations'])->name('job-orders.get-arrival-locations');

Route::resource("bag-requests", BagRequestController::class);
Route::post("get/bag-request", [BagRequestController::class, "getList"])->name("get.bag-requests");
Route::get("bag-request/getNumber", [BagRequestController::class, "getNumber"])->name("bag-requests.get-number");
Route::get("bag-request/getArrivalLocation", [BagRequestController::class, "getArrivalLocations"])->name("bag-requests.get-arrival-locations");
Route::get("bag-request/getGalas", [BagRequestController::class, "getGalas"])->name("bag-requests.get-galas");
Route::get("bag-request/getJobOrders", [BagRequestController::class, "getJobOrders"])->name("bag-requests.get-job-orders");
Route::get("bag-request/getJobOrderItems", [BagRequestController::class, "getJobOrderItems"])->name("get.job-order-items");


Route::get('job-order-rm-qc/get-number', [JobOrderRawMaterialQcController::class, 'getNumber'])->name('job-order-rm-qc.get-number');
Route::get('job-order-rm-qc/get-job-orders-by-date', [JobOrderRawMaterialQcController::class, 'getJobOrdersByDate'])->name('job-order-rm-qc.get-job-orders-by-date');
Route::resource('job-order-rm-qc', JobOrderRawMaterialQcController::class);
Route::post('get-job-order-rm-qc', [JobOrderRawMaterialQcController::class, 'getList'])->name('get.job_order_rm_qc');
Route::post('get-job-order-detail-for-rm-qc', [JobOrderRawMaterialQcController::class, 'getJobOrderDetails'])->name('get.job_order_details');
Route::get('load-qc-commodities-tables', [JobOrderRawMaterialQcController::class, 'loadQcCommoditiesTables'])->name('load_qc_commodities_tables');

Route::get('production-voucher/get-number', [ProductionVoucherController::class, 'getNumber'])->name('production-voucher.get-number');
Route::resource('production-voucher', ProductionVoucherController::class);
Route::post('get-production-voucher', [ProductionVoucherController::class, 'getList'])->name('get.production-voucher');
Route::get('production-voucher/{id}/analysis-qc-view', [ProductionVoucherController::class, 'getAnalysisQcView'])->name('production-voucher.analysis-qc-view');
Route::post('production-voucher-get-job-orders-by-location', [ProductionVoucherController::class, 'getJobOrdersByLocation'])->name('production-voucher.get-job-orders-by-location');
Route::post('production-voucher-get-commodities-by-location', [ProductionVoucherController::class, 'getCommoditiesByLocation'])->name('production-voucher.get-commodities-by-location');
Route::post('production-voucher-get-parent-product', [ProductionVoucherController::class, 'getParentProduct'])->name('production-voucher.get-parent-product');
Route::post('production-voucher-get-plants-by-location', [ProductionVoucherController::class, 'getPlantsByLocation'])->name('production-voucher.get-plants-by-location');
Route::post('production-voucher-get-sublocations-by-location', [ProductionVoucherController::class, 'getSublocationsByLocation'])->name('production-voucher.get-sublocations-by-location');
Route::post('production-voucher-get-packing-items', [ProductionVoucherController::class, 'getPackingItemsByJobOrder'])->name('production-voucher.get-packing-items');
Route::post('production-voucher-get-packing-items-with-produced', [ProductionVoucherController::class, 'getPackingItemsWithProduced'])->name('production-voucher.get-packing-items-with-produced');
Route::post('production-voucher-get-brands-by-job-orders', [ProductionVoucherController::class, 'getBrandsByJobOrders'])->name('production-voucher.get-brands-by-job-orders');
Route::post('production-voucher-get-machines-by-plant', [ProductionVoucherController::class, 'getMachinesByPlant'])->name('production-voucher.get-machines-by-plant');
Route::get('production-voucher/{id}/input-form', [ProductionVoucherController::class, 'getInputForm'])->name('production-voucher.input.form');
Route::get('production-voucher/{id}/input-form/{inputId}', [ProductionVoucherController::class, 'getInputForm'])->name('production-voucher.input.edit-form');
Route::get('production-voucher/{id}/output-form', [ProductionVoucherController::class, 'getOutputForm'])->name('production-voucher.output.form');
Route::get('production-voucher/{id}/output-form/{outputId}', [ProductionVoucherController::class, 'getOutputForm'])->name('production-voucher.output.edit-form');
Route::post('production-voucher/{id}/input', [ProductionVoucherController::class, 'storeInput'])->name('production-voucher.input.store');
Route::put('production-voucher/{id}/input/{inputId}', [ProductionVoucherController::class, 'updateInput'])->name('production-voucher.input.update');
Route::delete('production-voucher/{id}/input/{inputId}', [ProductionVoucherController::class, 'destroyInput'])->name('production-voucher.input.destroy');
Route::post('production-voucher/{id}/output', [ProductionVoucherController::class, 'storeOutput'])->name('production-voucher.output.store');
Route::put('production-voucher/{id}/output/{outputId}', [ProductionVoucherController::class, 'updateOutput'])->name('production-voucher.output.update');
Route::delete('production-voucher/{id}/output/{outputId}', [ProductionVoucherController::class, 'destroyOutput'])->name('production-voucher.output.destroy');
Route::post('get-production-voucher-inputs/{id}', [ProductionVoucherController::class, 'getInputsList'])->name('get.production-voucher-inputs');
Route::post('get-production-voucher-outputs/{id}', [ProductionVoucherController::class, 'getOutputsList'])->name('get.production-voucher-outputs');
Route::get('production-voucher/{id}/slot-form', [ProductionVoucherController::class, 'getSlotForm'])->name('production-voucher.slot.form');
Route::get('production-voucher/{id}/slot-form/{slotId}', [ProductionVoucherController::class, 'getSlotForm'])->name('production-voucher.slot.edit-form');
Route::post('production-voucher/{id}/slot', [ProductionVoucherController::class, 'storeSlot'])->name('production-voucher.slot.store');
Route::put('production-voucher/{id}/slot/{slotId}', [ProductionVoucherController::class, 'updateSlot'])->name('production-voucher.slot.update');
Route::delete('production-voucher/{id}/slot/{slotId}', [ProductionVoucherController::class, 'destroySlot'])->name('production-voucher.slot.destroy');
Route::post('get-production-voucher-slots/{id}', [ProductionVoucherController::class, 'getSlotsList'])->name('get.production-voucher-slots');
Route::post('production-voucher-get-by-product-table', [ProductionVoucherController::class, 'getByProductTable'])->name('production-voucher.get-by-product-table');
Route::post('production-voucher-get-head-products-data', [ProductionVoucherController::class, 'getHeadProductsData'])->name('production-voucher.get-head-products-data');
Route::post('production-voucher-get-b2-products-data', [ProductionVoucherController::class, 'getB2ProductsData'])->name('production-voucher.get-b2-products-data');
Route::post('production-voucher-get-costing-preview', [ProductionVoucherController::class, 'getCostingPreview'])->name('production-voucher.get-costing-preview');
Route::post('production-voucher-get-job-orders-for-output-product', [ProductionVoucherController::class, 'getJobOrdersForOutputProduct'])->name('production-voucher.get-job-orders-for-output-product');
// Production Slots Routes
use App\Http\Controllers\Production\ProductionSlotController;
Route::resource('production-slot', ProductionSlotController::class);
Route::post('get-production-slot', [ProductionSlotController::class, 'getList'])->name('get.production-slot');
Route::post('production-slot/{slotId}/break', [ProductionSlotController::class, 'storeBreak'])->name('production-slot.break.store');
Route::put('production-slot/{slotId}/break/{breakId}', [ProductionSlotController::class, 'updateBreak'])->name('production-slot.break.update');
Route::delete('production-slot/{slotId}/break/{breakId}', [ProductionSlotController::class, 'destroyBreak'])->name('production-slot.break.destroy');

// Plant Breakdown Routes
Route::resource('plant-breakdown', PlantBreakdownController::class);
Route::post('get-plant-breakdown', [PlantBreakdownController::class, 'getList'])->name('get.plant-breakdown');

// Machine Plan Setting Routes
Route::resource('machine-plan-setting', MachinePlanSettingController::class);
Route::post('get-machine-plan-setting', [MachinePlanSettingController::class, 'getList'])->name('get.machine-plan-setting');
Route::get('/machines-by-plant', [MachinePlanSettingController::class, 'getMachinesByPlant'])->name('getMachinesByPlant');
Route::get('/vouchers-by-date', [MachinePlanSettingController::class, 'getProductionVouchersByDate'])->name('getProductionVouchersByDate');
Route::get('/breakdowns-by-plant-date', [MachinePlanSettingController::class, 'getBreakdownsByPlantAndDate'])->name('getBreakdownsByPlantAndDate');

// Production Quality Check Routes
Route::resource('production-quality-check', ProductionQualityCheckController::class);
Route::post('get-production-quality-check', [ProductionQualityCheckController::class, 'getList'])->name('get.production-quality-check');

Route::get('production-input-analysis/check-fixed-product', [ProductionInputAnalysisController::class, 'checkFixedProduct'])->name('production-input-analysis.check-fixed-product');
Route::get('production-input-analysis/get-slabs-by-product', [ProductionInputAnalysisController::class, 'getSlabsByProduct'])->name('production-input-analysis.get-slabs');
Route::resource("production-input-analysis", ProductionInputAnalysisController::class);
Route::post('get-production-input-analysis', [ProductionInputAnalysisController::class, 'getList'])->name('get.production-input-analysis');

Route::get('production-machine-analysis/check-fixed-product', [ProductionMachineAnalysisController::class, 'checkFixedProduct'])->name('production-machine-analysis.check-fixed-product');
Route::get('production-machine-analysis/get-slabs-by-product', [ProductionMachineAnalysisController::class, 'getSlabsByProduct'])->name('production-machine-analysis.get-slabs');
Route::get('production-machine-analysis/get-arrival-locations/{id}', [ProductionMachineAnalysisController::class, 'getArrivalLocationsByCompanyLocation'])->name('production-machine-analysis.get-arrival-locations');
Route::get('production-machine-analysis/get-plants/{companyId}/{arrivalId}', [ProductionMachineAnalysisController::class, 'getPlantsByArrivalLocation'])->name('production-machine-analysis.get-plants');
Route::get('production-machine-analysis/get-machines/{arrivalId}/{plantId}', [ProductionMachineAnalysisController::class, 'getMachinesByPlant'])->name('production-machine-analysis.get-machines');
Route::resource("production-machine-analysis", ProductionMachineAnalysisController::class);
Route::post('get-production-machine-analysis', [ProductionMachineAnalysisController::class, 'getList'])->name('get.production-machine-analysis');

Route::get('production-output-analysis/check-fixed-product', [ProductionOutputAnalysisController::class, 'checkFixedProduct'])->name('production-output-analysis.check-fixed-product');
Route::resource("production-output-analysis", ProductionOutputAnalysisController::class);
Route::post('get-production-output-analysis', [ProductionOutputAnalysisController::class, 'getList'])->name('get.production-output-analysis');
Route::get('get-slabs-by-product', [ProductionOutputAnalysisController::class, 'getSlabsByProduct'])->name('production-output-analysis.get-slabs');

Route::get('production-drying-job-order/get-recipes/{productId}', [ProductionDryingController::class, 'getRecipesByProduct'])->name('production-drying-job-order.get-recipes');
Route::get('production-drying-job-order/get-recipe-details/{recipeId}', [ProductionDryingController::class, 'getRecipeDetails'])->name('production-drying-job-order.get-recipe-details');
Route::get('production-drying-job-order/get-number', [ProductionDryingController::class, 'getNumber'])->name('production-drying-job-order.get-number');
Route::resource("production-drying-job-order",ProductionDryingController::class);
Route::post('get-production-drying-job-order', [ProductionDryingController::class, 'getList'])->name('get.production-drying-job-order');

Route::resource("production-steam-parboiling-job-order", ProductionSteamParboilingController::class)->parameters([
    'production-steam-parboiling-job-order' => 'order'
]);
Route::post('get-production-steam-parboiling-job-order', [ProductionSteamParboilingController::class, 'getList'])->name('get.production-steam-parboiling-job-order');

Route::get('production-analysis-request/get-number', [ProductionAnalysisRequestController::class, 'getNumber'])->name('production-analysis-request.get-number');
Route::get('production-analysis-request/get-arrival-locations/{id}', [ProductionAnalysisRequestController::class, 'getArrivalLocationsByCompanyLocation'])->name('production-analysis-request.get-arrival-locations');
Route::get('production-analysis-request/get-plants/{companyId}/{arrivalId}', [ProductionAnalysisRequestController::class, 'getPlantsByArrivalLocation'])->name('production-analysis-request.get-plants');
Route::get('production-analysis-request/get-job-orders/{companyLocationId}', [ProductionAnalysisRequestController::class, 'getJobOrdersByCompanyLocation'])->name('production-analysis-request.get-job-orders');
Route::post('get-production-analysis-request', [ProductionAnalysisRequestController::class, 'getList'])->name('get.production-analysis-request');
Route::resource('production-analysis-request', ProductionAnalysisRequestController::class);