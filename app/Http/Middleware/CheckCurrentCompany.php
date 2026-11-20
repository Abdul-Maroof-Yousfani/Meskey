<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;

class CheckCurrentCompany
{
    public function handle(Request $request, Closure $next, $permission = null)
    {

        $user = auth()->user();
        //If User has only one country assigned then skip the selection process
        if (!$user->current_company_id && count($user->companies) == 1) {
            $user->update(['current_company_id' => $user->companies()->first()->id]);
            $request->merge([
                'company_id' => $user->current_company_id,
            ]);
        }
        // Ensure the user has a current company
        if (!$user->current_company_id) {
            return redirect('select-company');
        }

        // Allow super-admin to bypass company and permission checks
        if ($user->user_type === 'super-admin') {
            $request->merge([
                'company_id' => $user->current_company_id,
            ]);
            return $next($request);
        }

        $currentCompanyId = $user->current_company_id;

        // Fetch the user's role and permissions for the current company (cached)
        $access = PermissionService::resolve($user);

        if ($access === null) {
            abort(403, 'No company role found for the current company.');
        }

        if ($access['permissions'] === null) {
            abort(403, 'User role not found for this company.');
        }

        $roleId = $access['role_id'];

        // Check if the role has the required permission
        $permissions = $access['permissions'];
        if ($permission && !in_array($permission, $permissions)) {
            abort(403, 'User does not have the required permission for this company.');
        }

        // Merge data into the request
        $request->merge([
            'company_id' => $currentCompanyId,
            'role_id' => $roleId,
            'permissions' => $permissions,
        ]);

        return $next($request);
    }
}
