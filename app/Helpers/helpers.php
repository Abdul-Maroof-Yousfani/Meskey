<?php

use App\Models\Acl\{Company,Menu};
use App\Models\{User};

use Illuminate\Support\Facades\Auth;

//UserCanAccess
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
        $menus = Menu::leftJoin('permissions','permissions.id','menus.permission_id')
        ->where('menus.parent_id',2000000)
            ->latest()
            ->select('permissions.name as permission_name','menus.*')
            ->get();
         return $menus;
    }

}

if (!function_exists('generateUniqueNumber')) {
    function generateUniqueNumber($prefix = null, $tableName, $company_id = null, $uniqueColumn = 'unique_no') {
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
if (!function_exists('getDeductionSuggestion')) {
    function getDeductionSuggestion($productSlabTypeId, $productId, $inspectionResult) {
        //dd($productSlabTypeId, $productId, $inspectionResult);
        return \App\Models\Master\ProductSlab::where('product_slab_type_id', $productSlabTypeId)
            ->where('product_id', $productId)
            ->where('from', '<=', $inspectionResult)
            ->where('to', '>=', $inspectionResult)
            ->where('status', 1) // Assuming active slabs have status = 1
            ->select('deduction_type', 'deduction_value')
            ->first();
    }
}
if (!function_exists('getTableData')) {
    function getTableData($table, $columns = ['*']) {
        return DB::table($table)->select($columns)->get();
    }
}






