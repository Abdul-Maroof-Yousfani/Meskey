<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

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
    Route::post('/set-layout-cookie', function (Illuminate\Http\Request $request) {
        $layout = $request->input('layout', 'light');
        return response()
            ->json(['message' => 'Cookie set'])
            ->cookie('layout', $layout, 60 * 24 * 30); // 30 days
    });
});

Route::get('/migrate-refresh', function () {
    // Rollback migrations
    Artisan::call('migrate:fresh');

    Artisan::call('db:seed', ['--class' => 'PermissionTableSeeder']);
    Artisan::call('db:seed', ['--class' => 'CreateAdminUserSeeder']);

    return 'Migrations rolled back and seeders executed successfully.';
});
Route::get('/menu', function () {

Menu::create('Main Menu', function ($menu) {
    $menu->add('Home', ['url' => '/']);
    $menu->add('About Us', ['url' => '/about']);
    $menu->add('Contact', ['url' => '/contact']);
});
    return view('management.acl.menu.index');
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
