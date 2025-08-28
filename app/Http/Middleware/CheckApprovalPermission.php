<?php

namespace App\Http\Middleware;

use App\Models\ApprovalsModule\ApprovalModule;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckApprovalPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403);
        }

        $approvalModule = ApprovalModule::findOrFail($request->mc);
        $id = $request->id ?? '';
        $modelClass = $approvalModule->model_class ?? '';

        if (!class_exists($modelClass)) {
            abort(404);
        }

        $module = ApprovalModule::where('model_class', $modelClass)->first();

        if (!$module) {
            abort(403, 'No approval module configured for this model');
        }

        $userRoleIds = $user->roles->pluck('id')->toArray();
        $requiredRoles = $module->roles->pluck('role_id')->toArray();

        if (empty(array_intersect($userRoleIds, $requiredRoles))) {
            abort(403, 'You do not have permission to approve this');
        }

        return $next($request);
    }
}
