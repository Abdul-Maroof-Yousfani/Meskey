<?php

use App\Models\Acl\{Company, Menu};
use App\Models\{BagType, Category, Master\ArrivalLocation, Master\ArrivalSubLocation, Master\Customer, Master\Stitching, Master\Tax, PaymentTerm, Procurement\Store\DebitNote, Procurement\Store\DebitNoteData, Procurement\Store\PurchaseBill, Procurement\Store\PurchaseBillData, Procurement\Store\PurchaseOrderData, Procurement\Store\PurchaseOrderReceiving, Procurement\Store\PurchaseReturnData, Product, Production\JobOrder\JobOrder, ReceiptVoucher, ReceiptVoucherItem, Sales\DeliveryChallan, Sales\DeliveryChallanData, Sales\DeliveryOrder, Sales\DeliveryOrderData, Sales\LoadingProgramItem, Sales\LoadingSlip, Sales\SaleReturnData, Sales\SalesInquiry, Sales\SalesInvoiceData, Sales\SalesOrder, Sales\SalesOrderData, User};
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Stock;
use App\Models\Master\Account\Transaction;
use App\Models\Master\Brands;
use App\Models\Master\CompanyLocation;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use App\Models\Master\Size;
use App\Models\Master\Supplier;
use App\Models\Master\Color;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Fluent;
use Carbon\Carbon;
const SLAB_TYPE_PERCENTAGE = 1;
const SLAB_TYPE_KG = 2;
const SLAB_TYPE_PRICE = 3;
const SLAB_TYPE_QTY = 4;

const SLAB_TYPES_CALCULATED_ON = [
    SLAB_TYPE_PERCENTAGE => '%',
    SLAB_TYPE_KG => 'Kg',
    SLAB_TYPE_PRICE => 'Rs.',
    SLAB_TYPE_QTY => 'Qty.'
];

if (!function_exists('canAccess')) {
    function canAccess($permission)
    {
        $user = auth()->user();

        if ($user->user_type === 'super-admin') {
            //For Developers
            return true;
        }

        $currentCompanyId = $user->current_company_id;

        $companyRolePermissions = $user->companies()->where('company_id', $currentCompanyId)->first();

        if (!$companyRolePermissions) {
            abort(403, 'No company role found for the current company.');
        }

        $roleId = $companyRolePermissions->pivot->role_id;

        $role = $user->roles()->where('id', $roleId)->first();

        if ($role) {
            $permissions = $role->permissions->pluck('name')->toArray();

            if ($permission && !in_array($permission, $permissions)) {
                return false;
                abort(403, 'User does not have the required permission for this company.');
            }

            return true;
        } else {
            return false;
        }
    }
}


if(!function_exists("getAllStitchings")) {
    function getAllStitchings() {
        return Stitching::select("id", "name")->where("status", "active")->get();
    }
}

if(!function_exists("getStitchingById")) {
    function getStitchingById($id) {
        return Stitching::where("id", $id)->where("status", "active")->first();
    }
}

if(!function_exists("getStitchingsByIds")) {
    function getStitchingsByIds($ids) {
        if (is_string($ids)) {
            $ids = array_filter(array_map('trim', explode(',', $ids)));
        }
        if (empty($ids)) {
            return collect();
        }
        return Stitching::whereIn("id", $ids)->where("status", "active")->get();
    }
}

if(!function_exists('getUserCompanyLocations')) {
    function getUserCurrentCompanyLocations() {
        $user = Auth::user();
        $currentCompanyId = $user->current_company_id;
        $userLocations = $user->companies->where('id', $currentCompanyId)->first()->pivot->locations;
        if (!$userLocations) {
            return [];
        }
        return json_decode($userLocations, true);
    }
}

if(!function_exists('getUserCurrentCompanyArrivalLocations')) {
    function getUserCurrentCompanyArrivalLocations() {
        $user = Auth::user();
        $currentCompanyId = $user->current_company_id;
        $userLocationsArrivals = $user->companies->where('id', $currentCompanyId)->first()->pivot->arrival_locations;
        if (!$userLocationsArrivals) {
            return [];
        }
        return json_decode($userLocationsArrivals, true);
    }
}

function numberToWords($number)
{
    $number = floatval($number);
    $whole = floor($number);
    $fraction = round(($number - $whole) * 100);

    $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
    $words = $formatter->format($whole) . ' Rupees';

    if ($fraction > 0) {
        $words .= ' and ' . $formatter->format($fraction) . ' Paise';
    }

    return ucfirst($words) . ' Only';
}

//GetCurrentSelectedCompany
if (!function_exists('getCurrentCompany')) {
    function getCurrentCompany()
    {
        $user = Auth::user();
        if (!$user || !$user->current_company_id) {
            return null;
        }
        return Company::find($user->current_company_id);
    }
}

if(!function_exists("createdBy")) {
    function createdBy($createdByUserId) {
        return User::select("id", "name")->find($createdByUserId);
    }
}


function get_category_name($id) {
    $category = Category::where("id", $id)->first();
    return $category->name;
}

//GetUser'sAllCompanies
if (!function_exists('getUserAllCompanies')) {
    function getUserAllCompanies($id)
    {
        $user = User::findorfail($id);
        if ($user->type == 'super-admin') {
            $companies = Company::where('status', 1)->get();
        } else {
            $companies = $user->companies()->get();
        }

        return $companies;
    }
}
//GetAllCompanies
if (!function_exists('getAllCompanies')) {
    function getAllCompanies()
    {
        return Company::where('status', 1)->get();
    }
}

if (!function_exists("isBag")) {
    function isBag($item_id)
    {
        $product = Product::select("is_bag")->find($item_id);
        return $product->is_bag;
    }
}

function bag_type_name($bag_type_id) {
    $bag_type = BagType::select("id", "name")->where("id", $bag_type_id)->first();

    return $bag_type?->name ?? '';
}

if(!function_exists("totalBillQuantityCreated")) {
    function totalBillQuantityCreated(int $purchase_order_receving_id, int $item_id) {
        $purchase_bill_ids = (PurchaseBill::where("purchase_order_receiving_id", $purchase_order_receving_id)->get())->pluck("id");
        $data = PurchaseBillData::select(
            DB::raw("SUM(qty) AS billed_quantity")
        )
            ->whereIn("purchase_bill_id", $purchase_bill_ids)
            ->where("item_id", $item_id)
            ->first();

        return $data->billed_quantity; 
    }
}


if(!function_exists("arrival_name_by_id")) {
    function arrival_name_by_id($arrival_id) {
        if (str_contains($arrival_id, ',')) {
            // Handle comma-separated values
            $ids = explode(',', $arrival_id);
            $arrivals = ArrivalLocation::whereIn('id', $ids)->pluck('name')->toArray();
            return implode(', ', $arrivals);
        } else {
            // Handle single value
            $arrival = ArrivalLocation::find($arrival_id);
            return $arrival ? $arrival->name : '';
        }
    }
}
if(!function_exists("sub_arrival_name_by_id")) {
    function sub_arrival_name_by_id($sub_arrival_id) {
        if (str_contains($sub_arrival_id, ',')) {
            // Handle comma-separated values
            $ids = explode(',', $sub_arrival_id);
            $sub_arrivals = ArrivalSubLocation::whereIn('id', $ids)->pluck('name')->toArray();
            return implode(', ', $sub_arrivals);
        } else {
            // Handle single value
            $sub_arrival = ArrivalSubLocation::find($sub_arrival_id);
            return $sub_arrival ? $sub_arrival->name : '';
        }
    }
}

// if(!function_exists("get_customer_name")) {
//     function get_customer_name($customer_id) {
//         $customer = Customer::select("id", "name")->find($customer_id);
//         return $customer;
//     }
// }

if(!function_exists("numberToOrdinalWord")) {
    function numberToOrdinalWord(int $number): string
{
    $ordinals = [
        1 => 'first',
        2 => 'second',
        3 => 'third',
        4 => 'fourth',
        5 => 'fifth',
        6 => 'sixth',
        7 => 'seventh',
        8 => 'eighth',
        9 => 'ninth',
        10 => 'tenth',
        11 => 'eleventh',
        12 => 'twelfth',
        13 => 'thirteenth',
        14 => 'fourteenth',
        15 => 'fifteenth',
        16 => 'sixteenth',
        17 => 'seventeenth',
        18 => 'eighteenth',
        19 => 'nineteenth',
        20 => 'twentieth',
    ];

    if (isset($ordinals[$number])) {
        return $ordinals[$number];
    }

    $tens = [
        20 => 'twenty',
        30 => 'thirty',
        40 => 'forty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
    ];

    $ten = intdiv($number, 10) * 10;
    $unit = $number % 10;

    if ($unit === 0) {
        return $tens[$ten] . 'ieth'; // twentieth, thirtieth
    }

    return $tens[$ten] . '-' . $ordinals[$unit];
}
}

if(!function_exists("getLoadingProgramBalance")) {
    function getLoadingProgramBalance($delivery_order_id) {
        $delivery_order = DeliveryOrder::find($delivery_order_id);
        $total_qty = $delivery_order->delivery_order_data()->sum("qty");
        $used_qty = LoadingProgramItem::where("delivery_order_id", $delivery_order_id)->sum("qty");

        $remaining_qty = $total_qty - $used_qty;

        return $remaining_qty;
    }
}

if(!function_exists("getTaxById")) {
    function getTaxPercentageById($tax_id) {
        $tax = Tax::find($tax_id);
        return $tax->percentage ?? 0;
    }
}

if (!function_exists("getItem")) {
    function getItem($item_id)
    {
        $product = Product::find($item_id);
        return $product;
    }
}


if (!function_exists("getAllItems")) {
    function getAllItems()
    {
        return Product::all();
    }
}



if (!function_exists("getAllColors")) {
    function getAllColors()
    {
        return Color::where('status', 1)->get();
    }
}

if (!function_exists("getColorById")) {
    function getColorById($id)
    {
        return Color::where("id", $id)->where('status', 1)->first();
    }
}

if (!function_exists("getAllSizes")) {
    function getAllSizes()
    {
        return Size::where('status', 1)->get();
    }
}

if (!function_exists("getAllBrands")) {
    function getAllBrands()
    {
        return Brands::where('status', 'active')->get();
    }
}

if (!function_exists("getBrandById")) {
    function getBrandById($id)
    {
        return Brands::where("id", $id)->where('status', 'active')->first();
    }
}


if (!function_exists("getSizeById")) {
    function getSizeById($id)
    {
        return Size::where("id", $id)->where('status', 1)->first();
    }
}

if (!function_exists('image_path')) {
    function image_path($path)
    {
        // Path to the placeholder image
        $placeholder = asset('management/placeholder.png');

        // Check if the given path is null or the file doesn't exist
        if (empty($path) || !File::exists(public_path($path))) {
            return $placeholder;
        }

        // Return the asset path if the file exists
        return asset($path);
    }
}

if(!function_exists("job_orders")) {
    function job_orders() {
        return JobOrder::all();
    }
}

if (!function_exists('getMenu')) {

    function getMenu()
    {
        $menus = Menu::leftJoin('permissions', 'permissions.id', 'menus.permission_id')
            ->where('menus.parent_id', 2000000)
            ->latest()
            ->select('permissions.name as permission_name', 'menus.*')
            ->get();
        return $menus;
    }
}

if (!function_exists('generateUniqueNumber')) {
    function generateUniqueNumber($tableName, $prefix = null, $company_id = null, $uniqueColumn = 'unique_no', $useCompanyId = true)
    {
        // If company_id is null, use the authenticated user's current company ID
        if (is_null($company_id) && $useCompanyId) {
            $company_id = auth()->user()->current_company_id;
        }

        // Get the latest record from the table
        $latestRecord = DB::table($tableName)
            ->when($company_id, function ($query) use ($company_id) {
                return $query->where('company_id', $company_id);
            })
            ->orderBy($uniqueColumn, 'desc')
            ->first();

        // Extract the last unique number
        if ($prefix) {
            $lastUniqueNo = $latestRecord ? intval(substr($latestRecord->{$uniqueColumn}, strlen($prefix))) : 0;
        } else {
            $lastUniqueNo = $latestRecord ? intval($latestRecord->{$uniqueColumn}) : 0;
        }

        // Generate the new unique number
        $newUniqueNo = $lastUniqueNo + 1;

        // Format the unique number with prefix (if prefix is not null)
        if ($prefix) {
            $formattedUniqueNo = $prefix . str_pad($newUniqueNo, 6, '0', STR_PAD_LEFT);
        } else {
            $formattedUniqueNo = str_pad($newUniqueNo, 6, '0', STR_PAD_LEFT);
        }

        return $formattedUniqueNo;
    }
}

function generateUniqueNumberByDate($tableName, $prefix = null, $company_id = null, $uniqueColumn = 'unique_no', $useCompanyId = true)
{
    if (is_null($company_id) && $useCompanyId) {
        $company_id = auth()->user()->current_company_id;
    }

    $latestRecord = DB::table($tableName)
        ->when($company_id, function ($query) use ($company_id) {
            return $query->where('company_id', $company_id);
        })
        ->when($prefix, function ($query) use ($prefix, $uniqueColumn) {
            return $query->where($uniqueColumn, 'like', $prefix . '%');
        })
        ->orderBy($uniqueColumn, 'desc')
        ->first();

    $lastUniqueNo = $latestRecord ? intval(substr($latestRecord->{$uniqueColumn}, strlen($prefix))) : 0;
    $newUniqueNo = $lastUniqueNo + 1;

    return $prefix
        ? $prefix . str_pad($newUniqueNo, 6, '0', STR_PAD_LEFT)
        : str_pad($newUniqueNo, 6, '0', STR_PAD_LEFT);
}

function generateLocationBasedCode($tableName, $locationCode = 'KHI', $company_id = null, $uniqueColumn = 'unique_no')
{
    if (is_null($company_id)) {
        // $company_id = auth()->user()->current_company_id;
    }

    $month = date('m');
    $year = date('Y');

    $searchPattern = $locationCode . '-' . $month . '-' . $year . '-%';

    $latestRecord = DB::table($tableName)
        ->when($company_id, function ($query) use ($company_id) {
            return $query->where('company_id', $company_id);
        })
        ->where($uniqueColumn, 'like', $searchPattern)
        ->orderBy($uniqueColumn, 'desc')
        ->first();

    $lastNumber = $latestRecord
        ? intval(substr($latestRecord->{$uniqueColumn}, -5))
        : 0;

    $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

    return $locationCode . '-' . $month . '-' . $year . '-' . $newNumber;
}

function generateCode($tableName, $prefix, $company_id = null, $uniqueColumn = 'unique_no')
{
    if (is_null($company_id)) {
        // $company_id = auth()->user()->current_company_id;
    }

    $month = date('m');
    $year = date('Y');

    $searchPattern = $prefix . '-' . $month . '-' . $year . '-%';

    $latestRecord = DB::table($tableName)
        ->when($company_id, function ($query) use ($company_id) {
            return $query->where('company_id', $company_id);
        })
        ->where($uniqueColumn, 'like', $searchPattern)
        ->orderBy($uniqueColumn, 'desc')
        ->first();

    $lastNumber = $latestRecord
        ? intval(substr($latestRecord->{$uniqueColumn}, -5))
        : 0;

    $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

    return $prefix . '-' . $month . '-' . $year . '-' . $newNumber;
}

function generateTicketNoWithDateFormat($tableName, $locationCode = 'LOC', $company_id = null, $uniqueColumn = 'unique_no')
{
    if (is_null($company_id)) {
        $company_id = auth()->user()->current_company_id;
    }

    $datePart = date('m-d-Y');

    $latestRecord = DB::table($tableName)
        ->when($company_id, function ($query) use ($company_id) {
            return $query->where('company_id', $company_id);
        })
        ->where($uniqueColumn, 'like', $locationCode . '-%-' . $datePart)
        ->orderBy($uniqueColumn, 'desc')
        ->first();

    $lastNumber = $latestRecord
        ? intval(substr($latestRecord->{$uniqueColumn}, strlen($locationCode) + 1, 3))
        : 0;

    $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

    return $locationCode . '-' . $newNumber . '-' . $datePart;
}


function generateUniversalUniqueNo($tableName, $options = [])
{
    $prefix = $options['prefix'] ?? null;
    $locationCode = $options['location'] ?? null;
    $company_id = $options['company_id'] ?? auth()->user()->current_company_id;
    $uniqueColumn = $options['column'] ?? 'unique_no';

    $withDate = $options['with_date'] ?? false;
    $customDate = $options['custom_date'] ?? null;
    $dateFormat = $options['date_format'] ?? 'm-d-Y';

    $padLength = $options['pad'] ?? 4;
    $separator = $options['separator'] ?? '-';

    $serialAtEnd = $options['serial_at_end'] ?? false;

    // -------------------------------
    // DATE
    // -------------------------------
    if ($withDate) {
        $datePart = $customDate ? date($dateFormat, strtotime($customDate)) : date($dateFormat);
    } else {
        $datePart = null;
    }

    // ----------------------------------
    // LIKE PATTERN
    // ----------------------------------
    $patternParts = [];

    if ($prefix)
        $patternParts[] = $prefix;
    if ($locationCode)
        $patternParts[] = $locationCode;
    if ($withDate)
        $patternParts[] = $datePart;

    // SERIAL POSITION
    if ($serialAtEnd == false) {
        // SERIAL COMES **BEFORE** DATE
        array_splice($patternParts, (count($patternParts) - ($withDate ? 1 : 0)), 0, '%');
    } else {
        // SERIAL COMES **AT END**
        $patternParts[] = '%';
    }

    $pattern = implode($separator, $patternParts);

    // ----------------------------------
    // GET LAST RECORD
    // ----------------------------------
    $latestRecord = DB::table($tableName)
        ->when($company_id, fn($q) => $q->where('company_id', $company_id))
        ->where($uniqueColumn, 'like', $pattern)
        ->orderBy($uniqueColumn, 'desc')
        ->first();

    // ----------------------------------
    // LAST NUMBER
    // ----------------------------------
    $lastNumber = 0;

    if ($latestRecord) {
        preg_match('/(\d+)$/', $latestRecord->{$uniqueColumn}, $m);
        $lastNumber = isset($m[1]) ? intval($m[1]) : 0;
    }
    
    $newNumber = str_pad($lastNumber + 1, $padLength, '0', STR_PAD_LEFT);

    // ----------------------------------
    // FINAL BUILD (SERIAL POSITION FIXED)
    // ----------------------------------
    $final = [];

    if ($serialAtEnd == false) {
        // SERIAL comes BEFORE date
        if ($prefix)
            $final[] = $prefix;
        if ($locationCode)
            $final[] = $locationCode;
        $final[] = $newNumber;
        if ($withDate)
            $final[] = $datePart;
    } else {
        // SERIAL comes at END
        if ($prefix)
            $final[] = $prefix;
        if ($locationCode)
            $final[] = $locationCode;
        if ($withDate)
            $final[] = $datePart;
        $final[] = $newNumber;
    }

    return implode($separator, $final);
}



if (!function_exists('formatEnumValue')) {
    function formatEnumValue($value)
    {
        return ucwords(
            str_replace('_', ' ', str_replace('-', ' ', $value))
        );
    }
}

function convertToBoolean($value)
{
    return ($value == 'on') ? 1 : 0;
}

// if (!function_exists('getDeductionSuggestion')) {
//     function getDeductionSuggestion($productSlabTypeId, $productId, $inspectionResult)
//     {
//         //dd($productSlabTypeId, $productId, $inspectionResult);
//         return \App\Models\Master\ProductSlab::where('product_slab_type_id', $productSlabTypeId)
//             ->where('product_id', $productId)
//             ->where('from', '<=', $inspectionResult ?? 0)
//             ->where('to', '>=', $inspectionResult ?? 0)
//             ->where('status', 'active') // Assuming active slabs have status = 1
//             ->select('deduction_type', 'deduction_value')
//             ->first();
//     }
// }

function getParamsForAccountCreation($companyId, $accName, $pAccName, $isOperational = 'yes')
{
    $account = Account::where('name', $pAccName)->first();

    return ['name' => $accName, 'company_id' => $companyId, 'account_type' => $account->account_type ?? 'debit', 'table_name' => $pAccName, 'is_operational' => $isOperational ?? 'yes', 'parent_id' => $account->id ?? NULL, 'request_account_id' => 0];
}

function getParamsForAccountCreationByPath($companyId, $accName, $path, $referenceTableName, $isOperational = 'yes')
{
    $account = Account::where('hierarchy_path', $path)->first();

    return ['name' => $accName, 'company_id' => $companyId, 'account_type' => $account->account_type ?? 'debit', 'table_name' => $referenceTableName, 'is_operational' => $isOperational ?? 'yes', 'parent_id' => $account->id ?? NULL, 'request_account_id' => 0];
}


function get_product_by_category($id)
{
    $Product = Product::with('unitOfMeasure')->where('category_id', $id)->get();

    return $Product;
}

function get_product_by_id($id) {
    $product = Product::with("unitOfMeasure")->where("id", $id)->get();
    return $product;
}

function get_customer_name($customer_id) {
    $customer = Customer::where("id", $customer_id)->first();
    return $customer?->name ?? "";
}

function get_inquiry_reference_number($inquiry_id) {
    return SalesInquiry::find($inquiry_id)->value("inquiry_no");
}

function get_payment_term($payment_term_id) {
    return PaymentTerm::select("id", "desc")->where('status', 'active')->where("id", $payment_term_id)->first();
}

function get_locations()
{
    $CompanyLocation = CompanyLocation::all();

    return $CompanyLocation;
}

function get_so_locations($so_id) {
    $saleOrder = SalesOrder::find($so_id);
    $locations = $saleOrder->locations->pluck('location_id')->toArray();
    $companyLocations = CompanyLocation::whereIn('id', $locations)->get();
    return $companyLocations;
}

function get_arrival_locations() {
    $ArrivalLocations = ArrivalLocation::all();
    return $ArrivalLocations;
}

function get_sub_arrival_locations() {
    return ArrivalSubLocation::all();
}


function get_arrivals_by($location_id) {
    $arrivals = ArrivalLocation::where("company_location_id", $location_id)->get();
    return $arrivals;
}


function get_arrival($arrival_id) {
    $arrival = ArrivalLocation::find($arrival_id);
    return $arrival;
}

function get_sub_arrivals_by($arrival_id) {
    $sub_arrival = ArrivalSubLocation::where("arrival_location_id", $arrival_id)->get();
    return $sub_arrival;
}

function get_sub_arrivals_by_multiple($arrival_ids) {
    if (empty($arrival_ids)) return collect();
    $sub_arrivals = ArrivalSubLocation::whereIn("arrival_location_id", $arrival_ids)->get();
    return $sub_arrivals;
}

function get_location_name_by_id($company_location_id) {
    return CompanyLocation::where("id", $company_location_id)->value("name");
}


function get_location_id_by_name($location_id) {
    $location = CompanyLocation::find($location_id);
    return $location->id;
}



function get_arrival_name_by_id($arrival_id) {
    return ArrivalLocation::where("id", $arrival_id)->value("name");
}


function get_storage_name_by_id($storage_id) {
    return ArrivalSubLocation::where("id", $storage_id)->value("name");
}

function delivery_order_balance($sale_order_data_id) {
    $data = DeliveryOrderData::where("so_data_id", $sale_order_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    $able_to_spend = (SalesOrderData::where("id", $sale_order_data_id)->first())->no_of_bags;
    $balance = (int)$able_to_spend - (int)$spent;

    return $balance;
}

function get_second_weighbridge_balance(LoadingSlip $loadingSlip, $delivery_order_id = null) {
    
    $overall_quantities = $loadingSlip->deliveryOrder->delivery_order_data->sum("qty");
    $spent_quantities = $loadingSlip->deliveryOrder->saleSecondWeighbridge->sum("net_weight");
    $remaining_quantities = $overall_quantities - $spent_quantities;

    return $remaining_quantities;

}

function get_second_weighbridge_balance_by_delivery_order($delivery_order_id) {

    $delivery_order = DeliveryOrder::find($delivery_order_id);
    $overall_quantities = $delivery_order->delivery_order_data->sum("qty");
    $spent_quantities = $delivery_order->saleSecondWeighbridge->sum("net_weight");
    $remaining_quantities = $overall_quantities - $spent_quantities;

    return $remaining_quantities;

}


function receipt_voucher_balance($reference_id, $type = "sale_order") {
    $data = ReceiptVoucherItem::where("reference_id", $reference_id)->get();
    $total_spent = $data->sum(callback: "amount");
    if($type == "sale_order") {
        $total_overall_amount = SalesOrderData::where('sale_order_id', $reference_id)
                                                ->selectRaw('SUM(qty * rate) as total')
                                                ->value('total');
    } else {
        $total_overall_amount = SalesInvoiceData::where('sales_invoice_id', $reference_id)
                                                ->selectRaw('SUM(net_amount) as total')
                                                ->value('total');
    }
    
    $balance = $total_overall_amount - $total_spent;

    return $balance;
}

function delivery_order_bags_used($sale_order_data_id) {
    $data = DeliveryOrderData::where("so_data_id", $sale_order_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    return $spent;
}


function delivery_challan_bags_used($delivery_order_data_id) {
    $data = DeliveryChallanData::where("do_data_id", $delivery_order_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    return $spent;
}

function delivery_challan_balance($delivery_order_data_id) {
    $data = DeliveryChallanData::where("do_data_id", $delivery_order_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    $able_to_spend = (DeliveryOrderData::where("id", $delivery_order_data_id)->first())->no_of_bags;
    $balance = (int)$able_to_spend - (int)$spent;

    return $balance;
}


function sales_invoice_balance($delivery_challan_data_id) {
    $data = SalesInvoiceData::where("dc_data_id", $delivery_challan_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    $able_to_spend = (DeliveryChallanData::where("id", $delivery_challan_data_id)->first())->no_of_bags;
    $balance = (int)$able_to_spend - (int)$spent;

    return $balance;
}

function sale_return_balance($sale_invoice_data_id) {
    $data = SaleReturnData::where("sale_invoice_data_id", $sale_invoice_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    $able_to_spend = (SalesInvoiceData::where("id", $sale_invoice_data_id)->first())->no_of_bags;
    $balance = (int)$able_to_spend - (int)$spent;

    return $balance;
}

function sale_return_bags_used($sale_invoice_data_id) {
    $data = SaleReturnData::where("sale_invoice_data_id", $sale_invoice_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    return $spent;
}

function sales_invoice_bags_used($delivery_challan_data_id) {
    $data = SalesInvoiceData::where("dc_data_id", $delivery_challan_data_id)->get();
    
    $spent = $data->sum("no_of_bags");
    return $spent;
}


function get_supplier()
{
    $Supplier = Supplier::whereType('store_supplier')->get();

    return $Supplier;
}

function get_uom($id)
{
    $Product = Product::find($id);

    $name = optional($Product->unitOfMeasure)->name;
    return $name;
}

function applyTax($number, $percentage) {
    // Ensure both inputs are numbers
    $number = floatval($number);
    $percentage = floatval($percentage);

    // Calculate deduction
    $deduction = ($percentage / 100) * $number;

    // Subtract the deduction from the original number
    $result = $number - $deduction;

    return $result;
}

function getUserParams($param)
{
    if (!auth()->check())
        return is_array($param) ? [] : null;

    $user = auth()->user();

    if (is_array($param)) {
        $result = [];
        foreach ($param as $key) {
            $result[$key] = $user->{$key} ?? null;
        }
        return $result;
    }

    return $user->{$param} ?? null;
}

if (!function_exists('getDeductionSuggestion')) {
    function getDeductionSuggestion($productSlabTypeId, $productId, $inspectionResult, $purchaseOrderID = null)
    {
        $slabs = ProductSlab::where('product_slab_type_id', $productSlabTypeId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->orderBy('from', 'asc')
            ->get();

        if ($purchaseOrderID !== null) {
            $rmPoSlabs = ProductSlabForRmPo::where('arrival_purchase_order_id', $purchaseOrderID)
                ->where('product_id', $productId)
                ->where('product_slab_type_id', $productSlabTypeId)
                ->where('status', 'active')
                ->orderBy('from', 'asc')
                ->get();

            foreach ($rmPoSlabs as $rmPoSlab) {
                $newSlabs = collect();

                foreach ($slabs as $slab) {
                    if ($slab->to < $rmPoSlab->from || $slab->from > $rmPoSlab->to) {
                        $newSlabs->push($slab);
                        continue;
                    }

                    if ($rmPoSlab->from <= $slab->from && ($rmPoSlab->to === null || $rmPoSlab->to >= $slab->to)) {
                        $newSlabs->push((object) [
                            'from' => $slab->from,
                            'to' => $slab->to,
                            'is_tiered' => $slab->is_tiered,
                            'deduction_value' => 0,
                            'deduction_type' => $slab->deduction_type,
                        ]);
                    } else {
                        if ($slab->from < $rmPoSlab->from) {
                            $newSlabs->push((object) [
                                'from' => $slab->from,
                                'to' => $rmPoSlab->from - 1,
                                'is_tiered' => $slab->is_tiered,
                                'deduction_value' => $slab->deduction_value,
                                'deduction_type' => $slab->deduction_type,
                            ]);
                        }

                        $newSlabs->push((object) [
                            'from' => max($slab->from, $rmPoSlab->from),
                            'to' => min($slab->to ?? PHP_FLOAT_MAX, $rmPoSlab->to ?? PHP_FLOAT_MAX),
                            'is_tiered' => $slab->is_tiered,
                            'deduction_value' => 0,
                            'deduction_type' => $slab->deduction_type,
                        ]);

                        if (($slab->to === null || $slab->to > $rmPoSlab->to) && ($rmPoSlab->to !== null)) {
                            $newSlabs->push((object) [
                                'from' => $rmPoSlab->to + 1,
                                'to' => $slab->to,
                                'is_tiered' => $slab->is_tiered,
                                'deduction_value' => $slab->deduction_value,
                                'deduction_type' => $slab->deduction_type,
                            ]);
                        }
                    }
                }

                $slabs = $newSlabs;
            }
        }

        $deductionValue = 0;
        $inspectionResult = (float) ($inspectionResult ?? 0);

        foreach ($slabs as $slab) {
            $from = (float) $slab->from;
            $to = $slab->to !== null ? (float) $slab->to : null;
            $isTiered = (int) ($slab->is_tiered ?? 0);
            $deductionVal = (float) $slab->deduction_value;

            if ($inspectionResult >= $from) {
                if ($isTiered === 1) {
                    $applicableAmount = 0;

                    if ($to === null || $inspectionResult >= $to) {
                        $applicableAmount = $to - $from + 1;
                    } else {
                        $applicableAmount = $inspectionResult - $from + 1;
                    }

                    $deductionValue += $deductionVal * $applicableAmount;
                } else {
                    $deductionValue += $deductionVal;
                }
            }
        }

        return $deductionValue > 0 ? new Fluent([
            'deduction_type' => $slabs->first()->deduction_type ?? null,
            'deduction_value' => $deductionValue
        ]) : null;
    }
}

if (!function_exists('getTableData')) {
    function getTableData($table, $columns = ['*'])
    {
        return DB::table($table)->select($columns)->get();
    }
}

if (!function_exists('checkIfNameExists')) {
    function checkIfNameExists(string $name)
    {
        $validNames = ['Unloading Instructions', 'QC Advice', 'QC Remarks', 'Yeild (%)', 'Yield (%)'];
        return in_array($name, $validNames);
    }
}

if (!function_exists('createTransaction')) {
    /**
     * Create a new transaction
     *
     * @param float $amount
     * @param int $accountId
     * @param int $voucherTypeId
     * @param string $voucherNo
     * @param string $type (debit/credit)
     * @param string $isOpening (yes/no)
     * @param array $additionalData [optional] Additional data for the transaction
     * @throws \Exception
     */
    function createTransaction(
        float $amount,
        int $accountId,
        int $voucherTypeId,
        string $voucherNo,
        string $type = 'debit',
        string $isOpening = 'no',
        array $additionalData = []
    ) {
        try {
            if (!in_array(strtolower($type), ['debit', 'credit'])) {
                throw new \InvalidArgumentException("Transaction type must be either 'debit' or 'credit'");
            }

            if (!in_array(strtolower($isOpening), ['yes', 'no'])) {
                throw new \InvalidArgumentException("is_opening_balance must be either 'yes' or 'no'");
            }

            $account = Account::findOrFail($accountId);
            $accountUniqueNo = $account->unique_no;

            $transactionData = array_merge([
                'company_id' => auth()->user()->current_company_id ?? null,
                'voucher_date' => now()->format('Y-m-d'),
                'amount' => $amount,
                'account_id' => $accountId,
                'account_unique_no' => $accountUniqueNo,
                'transaction_voucher_type_id' => $voucherTypeId,
                'voucher_no' => $voucherNo,
                'type' => $type,
                'is_opening_balance' => $isOpening,
                'status' => 'active',
                'created_by' => auth()->user()->id,
                'payment_against' => null,
                'against_reference_no' => null,
            ], $additionalData);

            // Create and return the transaction
            return Transaction::create($transactionData);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Failed to create transaction: ' . $e->getMessage());
            throw $e;
        }
    }
}

if (!function_exists('createStockTransaction')) {
    /**
     * Create a new stock transaction
     *
     * @param int $productId
     * @param string $voucherType (grn/gdn/sale_return/purchase_return)
     * @param string $voucherNo
     * @param float $qty
     * @param string $type (stock-in/stock-out)
     * @param float|null $price [optional]
     * @param float|null $avgPricePerKg [optional]
     * @param string|null $narration [optional]
     * @param array $additionalData [optional] Additional data for the stock transaction
     * @throws \Exception
     */
    function createStockTransaction(
        int $productId,
        string $voucherType,
        string $voucherNo,
        float $qty,
        string $type = 'stock-in',
        ?float $price = null,
        ?float $avgPricePerKg = null,
        ?string $narration = null,
        array $additionalData = []
    ) {
        try {
            $validVoucherTypes = ['grn', 'gdn', 'sale_return', 'purchase_return', 'delivery_challan'];
            if (!in_array(strtolower($voucherType), $validVoucherTypes)) {
                throw new \InvalidArgumentException(
                    "Voucher type must be one of: " . implode(', ', $validVoucherTypes)
                );
            }

            if (!in_array(strtolower($type), ['stock-in', 'stock-out'])) {
                throw new \InvalidArgumentException("Stock type must be either 'stock-in' or 'stock-out'");
            }

            if ($qty <= 0) {
                throw new \InvalidArgumentException("Quantity must be greater than 0");
            }

            $stockData = array_merge([
                'product_id' => $productId,
                'voucher_type' => $voucherType,
                'voucher_no' => $voucherNo,
                'qty' => $qty,
                'type' => $type,
                'price' => $price,
                'avg_price_per_kg' => $avgPricePerKg,
                'narration' => $narration,
                'created_at' => now(),
                'updated_at' => now(),
            ], $additionalData);

            return Stock::create($stockData);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create stock transaction: ' . $e->getMessage());
            throw $e;
        }
    }
}

if(!function_exists("get_grn")) {
    function get_grn($grn_id) {
        return PurchaseOrderReceiving::where("id", $grn_id)->value("purchase_order_receiving_no");
    }
}

if (!function_exists('getTicketDeductions')) {
    function getTicketDeductions($ticket)
    {
        $result = [
            'is_lumpsum' => false,
            'lumpsum_deduction' => 0,
            'lumpsum_deduction_kgs' => 0,
            'deductions' => [],
            'total_deduction' => 0,
            'total_deduction_kgs' => 0,
        ];

        if ($ticket->is_lumpsum_deduction && $ticket->lumpsum_deduction > 0) {
            $result['is_lumpsum'] = true;
            $result['lumpsum_deduction'] = $ticket->lumpsum_deduction;
            $result['lumpsum_deduction_kgs'] = $ticket->lumpsum_deduction_kgs;
            $result['total_deduction'] = $ticket->lumpsum_deduction;
            $result['total_deduction_kgs'] = $ticket->lumpsum_deduction_kgs;

            return $result;
        }

        $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
            ->whereIn('approved_status', ['approved', 'rejected'])
            ->latest()
            ->first();

        if (!$samplingRequest) {
            return $result;
        }

        if ($samplingRequest->is_lumpsum_deduction && $samplingRequest->lumpsum_deduction > 0) {
            $result['is_lumpsum'] = true;
            $result['lumpsum_deduction'] = $samplingRequest->lumpsum_deduction;
            $result['lumpsum_deduction_kgs'] = $samplingRequest->lumpsum_deduction_kgs;
            $result['total_deduction'] = $samplingRequest->lumpsum_deduction;
            $result['total_deduction_kgs'] = $samplingRequest->lumpsum_deduction_kgs;

            return $result;
        }

        $compulsoryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)
            ->where('applied_deduction', '>', 0)
            ->get();

        foreach ($compulsoryResults as $compulsory) {
            $result['deductions'][] = [
                'type' => 'compulsory',
                'name' => $compulsory->qcParam->name ?? 'N/A',
                'deduction' => $compulsory->applied_deduction,
                'unit' => 'Rs.',
            ];
            $result['total_deduction'] += $compulsory->applied_deduction;
        }

        $slabResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)
            ->where('applied_deduction', '>', 0)
            ->get();

        foreach ($slabResults as $slab) {
            $result['deductions'][] = [
                'type' => 'slab',
                'name' => $slab->slabType->name ?? 'N/A',
                'slabType_id' => $slab->slabType->id ?? 'N/A',
                'deduction' => $slab->applied_deduction,
                'unit' => SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1],
            ];
            $result['total_deduction'] += $slab->applied_deduction;
        }

        if ($ticket->net_weight && $result['total_deduction'] > 0) {
            $result['total_deduction_kgs'] = ($ticket->net_weight * $result['total_deduction']) / 100;
        }

        return $result;
    }
}



if (!function_exists('SlabTypeWisegetTicketDeductions')) {
    function SlabTypeWisegetTicketDeductions($ticket, $type = null)
    {

        $result = [
            'is_lumpsum' => false,
            'lumpsum_deduction' => 0,
            'lumpsum_deduction_kgs' => 0,
            'deductions' => [],
            'total_deduction' => 0,
            'total_deduction_kgs' => 0,
        ];

        if ($ticket->is_lumpsum_deduction && $ticket->lumpsum_deduction > 0) {
            $result['is_lumpsum'] = true;
            $result['lumpsum_deduction'] = $ticket->lumpsum_deduction;
            $result['lumpsum_deduction_kgs'] = $ticket->lumpsum_deduction_kgs;
            $result['total_deduction'] = $ticket->lumpsum_deduction;
            $result['total_deduction_kgs'] = $ticket->lumpsum_deduction_kgs;

            // return $result;
        }

        if ($type == null) {

            $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();
        } else {
            $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                ->where('sampling_type', $type)
                //   ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();
        }

        if (!$samplingRequest) {
            return $result;
        }

        if ($samplingRequest->is_lumpsum_deduction && $samplingRequest->lumpsum_deduction > 0) {
            $result['is_lumpsum'] = true;
            $result['lumpsum_deduction'] = $samplingRequest->lumpsum_deduction;
            $result['lumpsum_deduction_kgs'] = $samplingRequest->lumpsum_deduction_kgs;
            $result['total_deduction'] = $samplingRequest->lumpsum_deduction;
            $result['total_deduction_kgs'] = $samplingRequest->lumpsum_deduction_kgs;

            // return $result;
        }

        $compulsoryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)
            // ->where('applied_deduction', '>', 0)
            ->get();

        foreach ($compulsoryResults as $compulsory) {
            $result['compulsory_deductions'][$compulsory->qcParam->id] = [
                'type' => 'compulsory',
                'name' => $compulsory->qcParam->name ?? 'N/A',
                'deduction' => $compulsory->applied_deduction,
                'checklist_value' => $compulsory->compulsory_checklist_value,
                'unit' => 'Rs.',
            ];
            $result['total_deduction'] += $compulsory->applied_deduction;
        }

        $slabResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)
            // ->where('applied_deduction', '>', 0)
            ->get();

        foreach ($slabResults as $slab) {
            $result['deductions'][$slab->slabType->id] = [
                'type' => 'slab',
                'name' => $slab->slabType->name ?? 'N/A',
                'slabType_id' => $slab->slabType->id ?? 'N/A',
                'slabType_qc_symbol' => $slab->slabType->qc_symbol ?? 'N/A',
                'deduction' => $slab->applied_deduction,
                'checklist_value' => $slab->checklist_value ?? 'N/A',
                'unit' => SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1],
            ];
            $result['total_deduction'] += $slab->applied_deduction;
        }

        if ($ticket->net_weight && $result['total_deduction'] > 0) {
            $result['total_deduction_kgs'] = ($ticket->net_weight * $result['total_deduction']) / 100;
        }

        return $result;
    }
}
if (!function_exists('SlabTypeWisegetTicketQcResults')) {
    function SlabTypeWisegetTicketQcResults($ticket, $type = null)
    {

        $result = [
            'is_lumpsum' => false,
            'lumpsum_deduction' => 0,
            'lumpsum_deduction_kgs' => 0,
            'deductions' => [],
            'total_deduction' => 0,
            'total_deduction_kgs' => 0,
        ];

        if ($ticket->is_lumpsum_deduction && $ticket->lumpsum_deduction > 0) {
            $result['is_lumpsum'] = true;
            $result['lumpsum_deduction'] = $ticket->lumpsum_deduction;
            $result['lumpsum_deduction_kgs'] = $ticket->lumpsum_deduction_kgs;
            $result['total_deduction'] = $ticket->lumpsum_deduction;
            $result['total_deduction_kgs'] = $ticket->lumpsum_deduction_kgs;

            return $result;
        }

        if ($type == null) {

            $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();
        } else {
            $samplingRequest = ArrivalSamplingRequest::where('arrival_ticket_id', $ticket->id)
                ->where('sampling_type', $type)
                //   ->whereIn('approved_status', ['approved', 'rejected'])
                ->latest()
                ->first();
        }

        if (!$samplingRequest) {
            return $result;
        }

        if ($samplingRequest->is_lumpsum_deduction && $samplingRequest->lumpsum_deduction > 0) {
            $result['is_lumpsum'] = true;
            $result['lumpsum_deduction'] = $samplingRequest->lumpsum_deduction;
            $result['lumpsum_deduction_kgs'] = $samplingRequest->lumpsum_deduction_kgs;
            $result['total_deduction'] = $samplingRequest->lumpsum_deduction;
            $result['total_deduction_kgs'] = $samplingRequest->lumpsum_deduction_kgs;

            return $result;
        }

        $compulsoryResults = ArrivalSamplingResultForCompulsury::where('arrival_sampling_request_id', $samplingRequest->id)
            // ->where('applied_deduction', '>', 0)
            ->get();

        foreach ($compulsoryResults as $compulsory) {
            $result['compulsory_deductions'][$compulsory->qcParam->id] = [
                'type' => 'compulsory',
                'name' => $compulsory->qcParam->name ?? 'N/A',
                'deduction' => $compulsory->applied_deduction,
                'checklist_value' => $compulsory->compulsory_checklist_value,
                'unit' => 'Rs.',
            ];
            $result['total_deduction'] += $compulsory->applied_deduction;
        }

        $slabResults = ArrivalSamplingResult::where('arrival_sampling_request_id', $samplingRequest->id)
            // ->where('applied_deduction', '>', 0)
            ->get();

        foreach ($slabResults as $slab) {
            $result['deductions'][$slab->slabType->id] = [
                'type' => 'slab',
                'name' => $slab->slabType->name ?? 'N/A',
                'slabType_id' => $slab->slabType->id ?? 'N/A',
                'slabType_qc_symbol' => $slab->slabType->qc_symbol ?? 'N/A',
                'deduction' => $slab->applied_deduction,
                'checklist_value' => $slab->checklist_value ?? 'N/A',
                'unit' => SLAB_TYPES_CALCULATED_ON[$slab->slabType->calculation_base_type ?? 1],
            ];
            $result['total_deduction'] += $slab->applied_deduction;
        }

        if ($ticket->net_weight && $result['total_deduction'] > 0) {
            $result['total_deduction_kgs'] = ($ticket->net_weight * $result['total_deduction']) / 100;
        }

        return $result;
    }
}



function formatDeductionsAsString(array $deductionsData): string
{
    $result = [];

    if ($deductionsData['is_lumpsum']) {
        // $result[] = "Lumpsum Deduction: " . number_format($deductionsData['lumpsum_deduction'], 2);
        // $result[] = "Lumpsum KGs Deduction: " . number_format($deductionsData['lumpsum_deduction_kgs'], 2);
        $result[] = "Lumpsum Deduction: " . number_format($deductionsData['lumpsum_deduction'], 2) . "Rs , " . number_format($deductionsData['lumpsum_deduction_kgs'], 2) . "KGs.";
    } else {
        foreach ($deductionsData['deductions'] as $deduction) {
            $unit = $deduction['unit'];
            $result[] = "{$deduction['name']}: " . number_format($deduction['deduction'], 2) . $unit;
        }
    }

    return implode(', ', $result);
}


if (!function_exists('dateFormatHtml')) {
    /**
     * Return full HTML (p tag with date / date+time)
     *
     * @param  mixed  $date
     * @param  string $type 'datetime' | 'dateonly'
     * @return string|null
     */
    function dateFormatHtml($date, $type = 'datetime')
    {
        if (!$date) {
            return null;
        }

        try {
            $carbon = Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }

        $dateOnly = $carbon->format('Y-m-d');
        $timeOnly = $carbon->format('h:i A');

        if ($type === 'dateonly') {
            return '<p class="m-0">' . $dateOnly . '</p>';
        }

        if ($type === 'datetime') {
            return '<p class="m-0">' . $dateOnly .
                '  <small class="text-timestamp"> / ' . $timeOnly . '</small></p>';
        }

        return null; // agar type match na kare
    }
}


if (!function_exists('getAccountDetailsByHierarchyPath')) {
    /**
     * Return account details by hierarchy path
     *
     * @param  string $hierarchyPath
     * @return Account|null
     */
    function getAccountDetailsByHierarchyPath($hierarchyPath)
    {
        $account = Account::where('hierarchy_path', $hierarchyPath)->first();
        return $account;
    }
}

if (!function_exists('getUserMissingInfoAlert')) {
    function getUserMissingInfoAlert()
    {
        $user = Auth::user();

        if (!$user || $user->user_type === 'super-admin') {
            return '';
        }

        $missing = array_filter([
            'Company Location' => !$user->company_location_id,
            'Arrival Location' => !$user->arrival_location_id
        ]);

        if (empty($missing)) {
            return '';
        }

        $missingText = implode(', ', array_keys($missing));

        return "<div class=\"alert bg-light-danger\">
    <i class=\"mr-1 fa fa-exclamation-triangle\"></i>
    <strong>Action Required:</strong> Some required details are missing.<br>
    Please contact your administrator to assign the following:
    <strong>{$missingText}</strong>.
</div>";
    }
}

if(!function_exists("getArrivalLocationsOfCompany")) {
    function getArrivalLocationsOfCompany($company_id) {
        $arrival_locations = ArrivalLocation::where("company_location_id", $company_id)->get();
        return $arrival_locations;
    }
}


if(!function_exists("getByProductsById")) {
    function getByProductsById($by_product_id) {

        $byProductId = $by_product_id;
        $byProduct = $byProductId ? Product::find($byProductId) : null;

        // Filter products based on parent_id logic
        $productsQuery = Product::where('status', 1);

        if ($byProduct) {
            if ($byProduct->parent_id) {
                // Head product has a parent - show all products with same parent_id (including head product if it's a child)
                $productsQuery->where(function ($q) use ($byProduct) {
                    $q->where('parent_id', $byProduct->parent_id)
                        ->orWhere('id', $byProduct->parent_id); // Include parent itself
                });
            } else {
                // Head product is itself a parent (parent_id is null) - show all its children + itself
                $productsQuery->where(function ($q) use ($byProductId) {
                    $q->where('parent_id', $byProductId)
                        ->orWhere('id', $byProductId); // Include head product itself
                });
            }
        }

        $byProducts = $productsQuery->orderBy('name')->get();

        
        return $byProducts;
    }
}
if(!function_exists("getDebitNoteBalance")) {
    function getDebitNoteBalance($purchase_bill_data_id, $exclude_debit_note_id = null) {
        $query = DebitNoteData::where('purchase_bill_data_id', $purchase_bill_data_id);

        if ($exclude_debit_note_id) {
            $query->where('debit_note_id', '!=', $exclude_debit_note_id);
        }

        $previousDebitNoteQty = $query->sum('debit_note_quantity');

        $billData = PurchaseBillData::find($purchase_bill_data_id);

        if (!$billData) {
            return 0;
        }

        return max(0, $billData->qty - $previousDebitNoteQty);
    }
}


if(!function_exists('getAvailableReturnBalance')) {
    function getAvailableReturnBalance($purchase_bill_data_id, $exclude_return_id = null) {
        // Get debit note balance (remaining quantity after debit notes)
        $debitNoteBalance = getDebitNoteBalance($purchase_bill_data_id);

        // Get purchase return quantities (excluding current return if editing)
        $returnQuery = PurchaseReturnData::where('purchase_bill_data_id', $purchase_bill_data_id);

        if ($exclude_return_id) {
            $returnQuery->where('purchase_return_id', '!=', $exclude_return_id);
        }

        $returnedQty = $returnQuery->sum('quantity');

        // Available balance = debit note balance - already returned quantity
        return max(0, $debitNoteBalance - $returnedQty);
    }
}

if(!function_exists('purchaseBillDistribution')) {
    function purchaseBillDistribution($purchase_bill_data_id) {
        $purchase_bill_data = PurchaseBillData::find($purchase_bill_data_id);
        $debit_note = DebitNoteData::where('purchase_bill_data_id', $purchase_bill_data_id)->sum('debit_note_quantity');
        $purchase_return = PurchaseReturnData::where('purchase_bill_data_id', $purchase_bill_data_id)->sum('quantity');
        return $purchase_bill_data->qty - $debit_note - $purchase_return;
    }
}

if(!function_exists("getPODataQty")) {
    function getPODataQty($po_data_id) {
        $po_data = PurchaseOrderData::find($po_data_id);
        return $po_data->qty;
    }
}


if(!function_exists("getStockByGrnDataId")) {
    function getStockByGrnDataId($po_data_id) {
        $stock_in = Stock::where("parent_id", $po_data_id)
                            ->where("voucher_type", "grn")
                            ->where("type", "stock-in")
                            ->sum("qty");
        
        $stock_out = Stock::where("parent_id", $po_data_id)
                            ->where("voucher_type", "qc")
                            ->where("type", "stock-out")
                            ->sum("qty");

        return $stock_in - $stock_out;
    }
}

if(!function_exists("getSaleOrderLocation")) {
    function getSaleOrderLocation($so_id) {
        $sale_order = SalesOrder::find($so_id);
        return $sale_order->locations;
    }
}

if(!function_exists("getSaleOrderSubArrival")) {
    function getSaleOrderSubArrival($so_id) {
        $sale_order = SalesOrder::find($so_id);
        return $sale_order->sections;
    }
}

if(!function_exists("subArrivalLocationId")) {
    function subArrivalLocationId($subarrival_location_id) {
        $sub_arrival = ArrivalSubLocation::with("arrivalLocation")->find($subarrival_location_id);
        return $sub_arrival;
    }
}

if(!function_exists("getLocation")) {
    function getLocation($location_id) {
        $company_location = CompanyLocation::find($location_id);
        return $company_location;
    }
}

if(!function_exists("getArrivalLocations")) {
    function getArrivalLocations($sub_arrival_id) {
        return ArrivalLocation::find($sub_arrival_id);
    }
}


