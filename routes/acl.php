<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Acl\{CompanyController, MenuController, UserController, RoleController};


Route::resource('roles', RoleController::class);
Route::post('/get-roles', [RoleController::class, 'getTable'])->name('get.roles');
Route::get('/export-roles', [RoleController::class, 'exportToExcel'])->name('export-roles');

Route::resource('users', UserController::class);
Route::post('/get-users', [UserController::class, 'getTable'])->name('get.users');
Route::get('/export-users', [UserController::class, 'exportToExcel'])->name('export-users');

Route::resource('company', CompanyController::class);
Route::post('/get-company', [CompanyController::class, 'getList'])->name('get.company');

Route::resource('menu', MenuController::class);
Route::post('/get-menu', [MenuController::class, 'getList'])->name('get.menu');
