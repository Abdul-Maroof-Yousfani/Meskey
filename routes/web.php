<?php

use App\Http\Controllers\Arrival\ArrivalSlipController;
use App\Http\Controllers\Master\ArrivalLocationController;
use App\Http\Controllers\Master\ProductSlabController;
use App\Models\Category;
use App\Models\JournalVoucher;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Account\TransactionVoucherType;
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
use App\Models\Procurement\Store\PurchaseBagQC;
use App\Models\Procurement\Store\PurchaseReturn;
use App\Models\Procurement\Store\PurchaseReturnData;
use App\Models\Procurement\Store\QCItems;
use App\Models\Product;
use App\Models\Production\JobOrder\JobOrder;
use App\Models\ReceiptVoucher;
use App\Models\Sales\DeliveryChallan;

use App\Models\ArrivalPurchaseOrder;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\FirstWeighbridge;
use App\Models\Sales\LoadingProgram;
use App\Models\Sales\LoadingSlip;
use App\Models\Sales\ReceivingRequest;
use App\Models\Sales\SaleReturnData;
use App\Models\Sales\SalesInquiry;
use App\Models\Sales\SalesInvoice;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SalesQc;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SecondWeighbridge;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Models\Production\JobOrder\JobOrderPackingItem;
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

Route::get("/receipt-vouchers/delete", function() {
    $receipt_voucher = ReceiptVoucher::query()->delete();
});

Route::get("testing-endpoint", function() {
    $job_order = JobOrderPackingItem::query()->where("id", 48)->first();
    dd($job_order);
});

Route::get("get-all-vouchers", function() {
    dd(TransactionVoucherType::all());
});

Route::get("/teste", function() {
    $sales_order = SalesOrder::query()->where("reference_no", "SO-2026-04-16-002")->get();
});

Route::get("voucher-types", function() {

    $transaction = TransactionVoucherType::create([
        "name" => "Purchase Return",
        "code" => "PR",
        "status" => "active"
    ]);

    $transaction->id = 6;
    $transaction->save();

    $transaction = TransactionVoucherType::create([
        "name" => "Debit Note",
        "code" => "DN",
        "status" => "active"
    ]);

    $transaction->id = 7;
    $transaction->save();

    // $transaction = TransactionVoucherType::where("name", "Goods Receiving Note")->first();
    // $transaction->id = 8;
    // $transaction->save();
    return;

    $newTransaction = TransactionVoucherType::create([
        "name" => "Goods Receiving Note",
        "code" => "GRN",
        "status" => "active",
        "id" => 8
    ]);

    $transaction = TransactionVoucherType::where("name", "QC")->first();
    $transaction->id = 9;
    $transaction->save();


    $transaction = TransactionVoucherType::where("name", "Sale Return")->first();
    $transaction->id = 10;
    $transaction->save();
});

Route::get("create-accounts", function() {
    $products = Product::whereNull("account_id")->get();
    foreach($products as $product) {
        $account = Account::create(getParamsForAccountCreationByPath(1, $product->name, '1-2', 'Inventory'));
        $product->account_id = $account->id;
        $product->save();
    }
});

Route::get("change-type", function() {
    $category = Category::where("name", "Bags")->first();
    $category->update([
        "category_type" => "general_items",
        "is_protected" => "yes"
    ]);

    $category = Category::where("name", "Store & Spare")->first();
    $category->update([
        "category_type" => "general_items",
        "is_protected" => "yes"
    ]);

});

Route::get("/procurement/delete-data", function() {
    
Schema::disableForeignKeyConstraints();
    PurchaseRequest::query()->delete();
    PurchaseRequestData::query()->delete();

    PurchaseQuotation::query()->delete();
    PurchaseQuotationData::query()->delete();
    
    PurchaseOrder::query()->delete();
    PurchaseOrderData::query()->delete();
    
    PurchaseOrderReceiving::query()->delete();
    PurchaseOrderReceivingData::query()->delete();
    
    PurchaseBagQC::query()->delete();
    
    PurchaseBillData::query()->delete();
    PurchaseBill::query()->delete();
    
    PurchaseReturnData::query()->delete();
    PurchaseReturn::query()->delete();
    Schema::enableForeignKeyConstraints();
});

Route::get("uom-fill", function() {
    $products = Product::whereNull("unit_of_measure_id")->update([
        "unit_of_measure_id" => 1
    ]);
});

Route::get("update-customer", function() {
    Customer::where("name", "Meskey")->update([
        'account_id' => 111
    ]);

    Customer::where("name", "123")->update([
        'account_id' => 113
    ]);

});


Route::get("checking-data", function() {
    SalesInquiry::query()->delete();
    SalesOrder::query()->delete();
    DeliveryOrder::query()->delete();
    LoadingProgram::query()->delete();
    FirstWeighbridge::query()->delete();
    SalesQc::query()->delete();
    LoadingSlip::query()->delete();
    SecondWeighbridge::query()->delete();
    DeliveryChallan::query()->delete();
    ReceivingRequest::query()->delete();
    SalesInvoice::query()->delete();
    SalesReturn::query()->delete();
    ReceiptVoucher::query()->delete();
    JournalVoucher::query()->delete();

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

Route::get("testing-data", function() {
    $suppliers = \App\Models\Master\Supplier::where("owner_mobile_no", "LIKE", "%-%")->get();
    foreach($suppliers as $supplier) {
        $cleaned = str_replace("-", "", $supplier->owner_mobile_no);
        $supplier->update([
            "owner_mobile_no" => str_replace("-", "", $supplier->owner_mobile_no)
        ]);
    }
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
    $qc = PurchaseBagQC::query()->delete();
    $qc_bags = QCItems::query()->delete();
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

        Route::post('/bulk_purchase_request_approval/{modelType}/{id}', [ApprovalController::class, 'bulk_purchase_request_approval'])
            ->middleware(['auth', 'approval.permission'])
            ->name('approval.bulk_purchase_request_approval');
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
            // $user->current_company_id = null;
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

Route::get('/clear-all-cache', function () {
    $commands = [
        'cache:clear',
        'config:clear',
        'route:clear',
        'view:clear',
    ];

    $output = [];

    foreach ($commands as $command) {
        $result = Artisan::call($command);
        $output[] = "✓ {$command} executed successfully.";
    }

    return response()->json([
        'status'  => 'success',
        'message' => 'Multiple caches cleared successfully.',
        'details' => $output
    ]);
});

Route::get("arrival-po", function() {
    $purchase_orders = ArrivalPurchaseOrder::all();
    foreach($purchase_orders as $purchase_order) {
        $purchase_order->am_approval_status = "approved";
        $purchase_order->save();
    }
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
