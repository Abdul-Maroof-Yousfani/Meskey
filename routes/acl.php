<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Acl\RoleController;
use App\Http\Controllers\Acl\UserController;
use App\Http\Controllers\Acl\CompanyController;
use App\Http\Controllers\Acl\MenuController;

Route::group(['middleware' => ['auth', 'check.company']], function () {
    Route::prefix('acl')->group(function () {
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
    });
});

Route::group(['middleware' => ['auth']], function () {
    Route::get('profile-settings', [UserController::class, 'profileSetting'])->name('profile-settings.index');
    Route::put('profile-settings/{id}', [UserController::class, 'profileSettingUpdate'])->name('profile-settings');
    Route::put('updatePassword/{id}', [UserController::class, 'updatePassword'])->name('updatePassword');
    Route::get('select-company', [CompanyController::class, 'selectCompany'])
        ->name('select.company')
        ->middleware('auth');

    Route::get('select-company/{key}', [CompanyController::class, 'selectCompany'])
        ->name('select.company')
        ->middleware('auth');
    //Logout
    Route::post('logouts', function (Request $request) {
        $user = Auth::user();
        if ($user) {
            $user->current_company_id = null;
            $user->save();
        }
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logouts');
});
