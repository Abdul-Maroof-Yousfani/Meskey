<?php

namespace App\Http\Controllers\ApprovalsModule;

use App\Models\ApprovalsModule\ApprovalModule;
use App\Models\ApprovalsModule\ApprovalModuleRole;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class ApprovalModuleController extends Controller
{
    public function index()
    {
        $modules = ApprovalModule::with('roles.role')->latest()->paginate(10);
        return view('management.master.approval_modules.index', compact('modules'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('management.master.approval_modules.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:approval_modules',
            'slug' => 'required|string|max:255|unique:approval_modules',
            'model_class' => 'nullable|string',
            'requires_sequential_approval' => 'boolean',
            'roles' => 'required|array',
        ]);

        // Custom validation for roles
        $validator = Validator::make($request->all(), []);

        foreach ($request->roles as $roleId => $roleData) {
            if (isset($roleData['id'])) {
                $validator->sometimes("roles.$roleId.count", 'required|integer|min:1', function () use ($roleData) {
                    return isset($roleData['id']);
                });

                $validator->sometimes("roles.$roleId.order", 'required|integer|min:0', function () use ($roleData) {
                    return isset($roleData['id']);
                });

                $validator->sometimes("roles.$roleId.id", 'exists:roles,id', function () use ($roleData) {
                    return isset($roleData['id']);
                });
            }
        }

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request) {
            $module = ApprovalModule::create([
                'name' => $request->name,
                'slug' => $request->slug,
                'model_class' => $request->model_class,
                'requires_sequential_approval' => $request->requires_sequential_approval ?? false,
            ]);

            // Only process checked roles
            foreach ($request->roles as $role) {
                if (isset($role['id']) && isset($role['count'])) {
                    ApprovalModuleRole::create([
                        'module_id' => $module->id,
                        'role_id' => $role['id'],
                        'approval_count' => $role['count'],
                        'approval_order' => $role['order'],
                    ]);
                }
            }
        });

        return redirect()->route('approval-modules.index')
            ->with('success', 'Approval module created successfully.');
    }

    public function update(Request $request, ApprovalModule $approvalModule)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('approval_modules')->ignore($approvalModule->id),
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('approval_modules')->ignore($approvalModule->id),
            ],
            'model_class' => 'nullable|string',
            'requires_sequential_approval' => 'boolean',
            'roles' => 'required|array',
        ]);

        $validator = Validator::make($request->all(), []);

        foreach ($request->roles as $roleId => $roleData) {
            if (isset($roleData['id'])) {
                $validator->sometimes("roles.$roleId.count", 'required|integer|min:1', function () use ($roleData) {
                    return isset($roleData['id']);
                });

                $validator->sometimes("roles.$roleId.order", 'required|integer|min:0', function () use ($roleData) {
                    return isset($roleData['id']);
                });

                $validator->sometimes("roles.$roleId.id", 'exists:roles,id', function () use ($roleData) {
                    return isset($roleData['id']);
                });
            }
        }

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::transaction(function () use ($request, $approvalModule) {
            $approvalModule->update([
                'name' => $request->name,
                'slug' => $request->slug,
                'model_class' => $request->model_class,
                'requires_sequential_approval' => $request->requires_sequential_approval ?? false,
            ]);

            // Filter only checked roles (those with id and count)
            $checkedRoles = array_filter($request->roles, function ($role) {
                return isset($role['id']) && isset($role['count']);
            });

            // Delete existing roles not in the new list
            $newRoleIds = collect($checkedRoles)->pluck('id')->toArray();
            ApprovalModuleRole::where('module_id', $approvalModule->id)
                ->whereNotIn('role_id', $newRoleIds)
                ->delete();

            // Update or create roles
            foreach ($checkedRoles as $role) {
                ApprovalModuleRole::updateOrCreate(
                    [
                        'module_id' => $approvalModule->id,
                        'role_id' => $role['id'],
                    ],
                    [
                        'approval_count' => $role['count'],
                        'approval_order' => $role['order'],
                    ]
                );
            }
        });

        return redirect()->route('approval-modules.index')
            ->with('success', 'Approval module updated successfully.');
    }

    public function show(ApprovalModule $approvalModule)
    {
        return view('management.master.approval_modules.show', compact('approvalModule'));
    }

    public function edit(ApprovalModule $approvalModule)
    {
        $roles = Role::all();
        $selectedRoles = $approvalModule->roles->pluck('role_id')->toArray();
        return view('management.master.approval_modules.edit', compact('approvalModule', 'roles', 'selectedRoles'));
    }

    public function destroy(ApprovalModule $approvalModule)
    {
        DB::transaction(function () use ($approvalModule) {
            $approvalModule->roles()->delete();
            $approvalModule->delete();
        });

        return redirect()->route('approval-modules.index')
            ->with('success', 'Approval module deleted successfully.');
    }
}
