<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Acl\{CompanyController, MenuController, UserController, RoleController, UserTestController};


Route::resource('roles', RoleController::class);
Route::post('/get-roles', [RoleController::class, 'getTable'])->name('get.roles');
Route::get('/export-roles', [RoleController::class, 'exportToExcel'])->name('export-roles');
Route::get('/get-arrival-locations/{companyLocationId}', [UserController::class, 'getArrivalLocations']);

// Route::resource('users', UserController::class);
// Route::post('/get-users', [UserController::class, 'getTable'])->name('get.users');
Route::get('/export-users', [UserController::class, 'exportToExcel'])->name('export-users');
Route::get('/check-username', [UserController::class, 'checkUsernameAvailability']);

Route::resource('company', CompanyController::class);
Route::post('/get-company', [CompanyController::class, 'getList'])->name('get.company');

Route::resource('menu', MenuController::class);
Route::post('/get-menu', [MenuController::class, 'getList'])->name('get.menu');


Route::resource('users-test', UserTestController::class);
Route::post('/get-users-test', [UserTestController::class, 'getTable'])->name('get.users.test');
Route::get('/export-users-test', [UserTestController::class, 'exportToExcel'])->name('export-users.test');
Route::get('/check-username-test', [UserTestController::class, 'checkUsernameAvailability']);
Route::get('/get-company-locations/{companyId}', [UserTestController::class, 'getCompanyLocations']);
Route::get('/get-arrival-locations/{companyLocationId}', [UserTestController::class, 'getArrivalLocations']);
// store
Route::get('users/{id}/assign-details', [UserTestController::class, 'assignDetails'])->name('users-test.assign');
Route::post('users/{id}/assign-details', [UserTestController::class, 'saveAssignDetails'])->name('users-test.assign.save');
// edit
Route::get('users/{userId}/edit-assign-details/{companyId}/{roleId}', [UserTestController::class, 'editAssignDetails'])->name('users-test.edit-assign');
Route::post('users/{userId}/edit-assign-details/{companyId}/{roleId}', [UserTestController::class, 'updateAssignDetails'])->name('users-test.assign.update');
Route::delete('users/{userId}/delete-assign-details/{companyId}/{roleId}', [UserTestController::class, 'deleteAssignDetails'])->name('users-test.delete-assign');
