<?php

use App\Models\Acl\{Company, Menu};
use App\Models\{User};
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
    function generateUniqueNumber($tableName, $prefix = null, $company_id = null, $uniqueColumn = 'unique_no')
    {
        // If company_id is null, use the authenticated user's current company ID
        if (is_null($company_id)) {
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

function generateUniqueNumberByDate($tableName, $prefix = null, $company_id = null, $uniqueColumn = 'unique_no')
{
    if (is_null($company_id)) {
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

if (!function_exists('formatEnumValue')) {
    function formatEnumValue($value)
    {
        return ucwords(str_replace('_', ' ', $value));
    }
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

if (!function_exists('getDeductionSuggestion')) {
    function getDeductionSuggestion($productSlabTypeId, $productId, $inspectionResult)
    {
        // $productSlabTypeId = 2;
        // $productId = 3;
        // $inspectionResult = 5;

        // Get ALL slabs for this product/type combination (without range filtering)
        $slabs = \App\Models\Master\ProductSlab::where('product_slab_type_id', $productSlabTypeId)
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->orderBy('from', 'asc')
            ->get();

        $deductionValue = 0;
        $inspectionResult = (float) ($inspectionResult ?? 0);
        // dd($slabs);
        foreach ($slabs as $slab) {
            $from = (float) $slab->from;
            $to = $slab->to !== null ? (float) $slab->to : null;
            $isTiered = (int) $slab->is_tiered;
            $deductionVal = (float) $slab->deduction_value;

            // Check if value is >= slab's from value (like in JS)
            if ($inspectionResult >= $from) {
                if ($isTiered === 1) {
                    $applicableAmount = 0;

                    // Calculate applicable amount for tiered slab
                    if ($to === null || $inspectionResult >= $to) {
                        // Full slab range applies
                        $applicableAmount = $to - $from;
                    } else {
                        // Partial slab range applies (difference between input and from)
                        $applicableAmount = $inspectionResult - $from;
                    }

                    $deductionValue += $deductionVal * $applicableAmount;
                } else {
                    // Fixed deduction (non-tiered)
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
