<?php

use App\Models\Acl\{Company, Menu};
use App\Models\{Category, Product, User};
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

if (!function_exists("getItem")) {
    function getItem($item_id)
    {
        $product = Product::find($item_id);
        return $product;
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

function get_locations()
{
    $CompanyLocation = CompanyLocation::all();

    return $CompanyLocation;
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
            $validVoucherTypes = ['grn', 'gdn', 'sale_return', 'purchase_return'];
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
            ->where('applied_deduction', '>', 0)
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