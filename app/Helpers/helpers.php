<?php

use App\Models\Acl\{Company, Menu};
use App\Models\{Category, Product, User};
use App\Models\Arrival\ArrivalSamplingRequest;
use App\Models\Arrival\ArrivalSamplingResult;
use App\Models\Arrival\ArrivalSamplingResultForCompulsury;
use App\Models\Master\Account\Account;
use App\Models\Master\Account\Transaction;
use App\Models\Master\ProductSlab;
use App\Models\Master\ProductSlabForRmPo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Fluent;

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

function getParamsForAccountCreation($companyId, $accName, $pAccName, $isOperational = null)
{
    $account = Account::where('name', $pAccName)->first();

    return ['name' => $accName, 'company_id' => $companyId, 'account_type' => $account->account_type ?? 'debit', 'is_operational' => $isOperational ?? $account->is_operational ?? 'yes', 'parent_id' => $account->id ?? NULL];
}


function get_product_by_category($id)
{
    $Product = Product::where('category_id', $id)->get();

    return $Product;
}

function get_uom($id)
{
    $Product = Product::find($id);

    $name = optional($Product->unitOfMeasure)->name;
    return $name;
}

function getUserParams($param)
{
    if (!auth()->check()) return is_array($param) ? [] : null;

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
            // Validate type
            if (!in_array(strtolower($type), ['debit', 'credit'])) {
                throw new \InvalidArgumentException("Transaction type must be either 'debit' or 'credit'");
            }

            // Validate is_opening_balance
            if (!in_array(strtolower($isOpening), ['yes', 'no'])) {
                throw new \InvalidArgumentException("is_opening_balance must be either 'yes' or 'no'");
            }


            $account = Account::findOrFail($accountId);
            $accountUniqueNo = $account->unique_no; // Assuming the column is named 'unique_no'


            // Merge additional data with default values
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
            \Log::error('Failed to create transaction: ' . $e->getMessage());
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
