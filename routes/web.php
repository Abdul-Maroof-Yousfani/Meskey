<?php

use App\Http\Controllers\Arrival\ArrivalSlipController;
use App\Http\Controllers\Master\ArrivalLocationController;
use App\Http\Controllers\Master\ProductSlabController;
use App\Models\Master\Customer;
use App\Models\Procurement\Store\PurchaseBill;
use App\Models\Procurement\Store\PurchaseBillData;
use App\Models\Procurement\Store\PurchaseOrder;
use App\Models\Procurement\Store\PurchaseOrderData;
use App\Models\Procurement\Store\PurchaseOrderReceiving;
use App\Models\Procurement\Store\PurchaseOrderReceivingData;
use App\Models\Procurement\Store\PurchaseQuotation;
use App\Models\Procurement\Store\PurchaseQuotationData;
use App\Models\Procurement\Store\PurchaseRequest;
use App\Models\Procurement\Store\PurchaseRequestData;
use App\Models\Procurement\Store\QC;
use App\Models\Procurement\Store\QCBags;
use App\Models\Production\JobOrder\JobOrder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Http\Controllers\Acl\{CompanyController, MenuController, UserController, RoleController};
use App\Http\Controllers\ApprovalsModule\ApprovalController;
use App\Http\Controllers\Arrival\ArrivalCustomSamplingController;
use App\Http\Controllers\Procurement\RawMaterial\PaymentRequestController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Reports\{
    TransactionController
};

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

Route::get("add-customer", function() {
    Customer::create([
        "name" => "Hashim",
        "company_id" => 1,
        "status" => "active"
    ]);
});


Route::get("add-permission", function() {
    Permission::create([
        "parent_id" =>  78,
        'name' => 'procurement-gate-buying',
        'guard_name' => 'web'
    ]);
    Permission::create([
        "parent_id" =>  78,
        'name' => 'procurement-purchase-sampling',
        'guard_name' => 'web'
    ]);
});

Route::get('/delete-migration/{filename}', function ($filename) {

    $record = DB::table('migrations')
        ->where('migration', 'like', "%{$filename}%")
        ->first();

    if (! $record) {
        return [
            'status' => 'not_found',
            'message' => 'No matching migration found. It has NOT been executed yet.',
        ];
    }

    // Delete the record
    DB::table('migrations')
        ->where('id', $record->id)
        ->delete();

    return [
        'status' => 'deleted',
        'message' => "Migration '{$record->migration}' deleted from migrations table.",
        'deleted_record' => $record,
    ];
});


Route::get("/table-names", function() {
   $tables = DB::select('SHOW TABLES');

dd($tables);
});

Route::get("/restore-db", function() {

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    $purchase_request = PurchaseRequest::query()->delete();
    $purchase_request_data = PurchaseRequestData::query()->delete();
    $purchase_quotation = PurchaseQuotation::query()->delete();
    $purchase_receive = PurchaseOrderReceiving::query()->delete();
    $purchase_bill = PurchaseBill::query()->delete();

    $purchase_quotation_data = PurchaseQuotationData::query()->delete();
    $purchase_order = PurchaseOrder::query()->delete();
    $purchase_order_data = PurchaseOrderData::query()->delete();
    $purchase_receive_data = PurchaseOrderReceivingData::query()->delete();
    $qc = QC::query()->delete();
    $qc_bags = QCBags::query()->delete();
    $purchase_bill_data = PurchaseBillData::query()->delete();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    dd("All data deleted");

    // $purchaseQuotation = PurchaseQuotation::all();
    // dd($purchaseQuotation);

});

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

Route::group(['middleware' => ['auth']], function () {
    Route::resource('transactions/report', TransactionController::class);
    Route::post('/get-transactions-report', [TransactionController::class, 'getTransactionsReport'])->name('get.transactions-report');


    Route::get('/generate-unique-no', [\App\Http\Controllers\Common\UniversalNumberController::class, 'generate']);
});



Route::group(['middleware' => ['auth', 'check.company']], function () {
    Route::prefix('approval')->group(function () {
        Route::post('/approve/{modelType}/{id}', [ApprovalController::class, 'approve'])
            ->middleware(['auth', 'approval.permission'])
            ->name('approval.approve');

        Route::post('/reject/{modelType}/{id}', [ApprovalController::class, 'reject'])
            ->middleware(['auth', 'approval.permission'])
            ->name('approval.reject');

        Route::post('/bulk_quotation_approval/{modelType}/{id}', [ApprovalController::class, 'bulk_quotation_approval'])
            ->middleware(['auth', 'approval.permission'])
            ->name('approval.bulk_quotation_approval');
    });

    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/dashboard/list-data', [App\Http\Controllers\HomeController::class, 'getListData'])->name('dashboard.list-data');

    Route::get('/dynamic-fetch-data', [App\Http\Controllers\HomeController::class, 'dynamicFetchData'])->name('dynamic-fetch-data');
    Route::get('/dynamic-dependent-fetch-data', [App\Http\Controllers\HomeController::class, 'dynamicDependentFetchData'])->name('dynamic-dependent-fetch-data');
    Route::get('/dynamic-dependent-fetch-data-all', [App\Http\Controllers\HomeController::class, 'dynamicDependentFetchDataAll'])->name('dynamic-dependent-fetch-data-all');
    Route::post('/set-layout-cookie', function (Illuminate\Http\Request $request) {
        $layout = $request->input('layout', 'light');
        return response()
            ->json(['message' => 'Cookie set'])
            ->cookie('layout', $layout, 60 * 24 * 30);
    });

    Route::get('getSlabsByProduct', [ProductSlabController::class, 'getSlabsByProduct'])->name('getSlabsByProduct');
    Route::get('getSlabsByPaymentRequestParams', [PaymentRequestController::class, 'getSlabsByPaymentRequestParams'])->name('getSlabsByPaymentRequestParams');
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
