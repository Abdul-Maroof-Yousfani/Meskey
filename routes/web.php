<?php

use App\Http\Controllers\Arrival\ArrivalSlipController;
use App\Http\Controllers\Arrival\InitialSamplingController;
use App\Http\Controllers\Master\ArrivalLocationController;
use App\Http\Controllers\Master\ProductSlabController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Http\Controllers\Acl\{CompanyController, MenuController, UserController, RoleController};
use App\Http\Controllers\Arrival\ArrivalCustomSamplingController;
use App\Http\Controllers\FrontHomeController;
use App\Http\Controllers\HomeController;

use Harimayco\Menu\Facades\Menu;


Auth::routes();
Route::get('adminpanel', function () {
    return redirect('/dashboard');
});
Route::get('/', function () {
    return redirect('/dashboard');
});
Route::fallback(function () {
    return view('404');
});

Route::group(['middleware' => ['auth', 'check.company']], function () {
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dynamic-fetch-data', [App\Http\Controllers\HomeController::class, 'dynamicFetchData'])->name('dynamic-fetch-data');
    Route::post('/set-layout-cookie', function (Illuminate\Http\Request $request) {
        $layout = $request->input('layout', 'light');
        return response()
            ->json(['message' => 'Cookie set'])
            ->cookie('layout', $layout, 60 * 24 * 30);
    });


        Route::get('getSlabsByProduct', [ProductSlabController::class, 'getSlabsByProduct'])->name('getSlabsByProduct');
    Route::get('getInitialSamplingResultByTicketId', [ArrivalLocationController::class, 'getInitialSamplingResultByTicketId'])->name('getInitialSamplingResultByTicketId');
    Route::get('getTicketDataForArrival', [ArrivalSlipController::class, 'getTicketDataForArrival'])->name('getTicketDataForArrival');
});


Route::group(['middleware' => ['auth']], function () {
    Route::resource('arrival-custom-sampling', ArrivalCustomSamplingController::class);

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






Route::get('/migrate-refresh', function () {
    // Rollback migrations
    Artisan::call('migrate:fresh');

    Artisan::call('db:seed', ['--class' => 'PermissionTableSeeder']);
    Artisan::call('db:seed', ['--class' => 'CreateAdminUserSeeder']);

    return 'Migrations rolled back and seeders executed successfully.';
});


Route::get('/migrate-specific/{id}', function ($id) {
    // Run a specific migration
    $migrationPath = 'database/migrations/' . $id;
    Artisan::call('migrate', [
        '--path' => $migrationPath,
    ]);

    return 'Migration executed successfully.';
});

Route::get('/seeder-specific/{id}', function ($id) {
    // You can also run seeders if needed
    Artisan::call('db:seed', ['--class' => $id]);

    return 'Migration executed successfully.';
});
