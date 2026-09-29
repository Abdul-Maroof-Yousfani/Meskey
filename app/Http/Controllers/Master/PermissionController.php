<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PermissionRequest;
use App\Models\Master\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parents = Permission::getHierarchicalOptions();
        return view('management.master.permissions.index', compact('parents'));
    }

    /**
     * Get list of permissions via AJAX.
     */
    public function getList(Request $request)
    {
        $permissions = Permission::with(['parent', 'roles'])
            ->withCount(['children', 'roles'])
            ->search($request->input('search'))
            ->when($request->filled('parent_id'), function ($q) use ($request) {
                if ($request->parent_id === 'root') {
                    return $q->whereNull('parent_id');
                }
                return $q->where('parent_id', $request->parent_id);
            })
            ->when($request->filled('guard_name'), function ($q) use ($request) {
                return $q->where('guard_name', $request->guard_name);
            })
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 25));

        return view('management.master.permissions.getList', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parents = Permission::getHierarchicalOptions();
        return view('management.master.permissions.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        // Invalidate Spatie permission cache
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => 'Permission created successfully.',
            'data' => $permission
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        $parents = Permission::getHierarchicalOptions($id);

        return view('management.master.permissions.edit', compact('permission', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionRequest $request, $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->update($request->validated());

        // Invalidate Spatie permission cache
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => 'Permission updated successfully.',
            'data' => $permission
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        // Prevent deletion if child permissions exist
        if ($permission->children()->exists()) {
            return response()->json([
                'catchError' => 'Cannot delete permission "' . $permission->name . '" because it has ' . $permission->children()->count() . ' child permission(s). Please delete or reassign child permissions first.'
            ], 200);
        }

        $permission->delete();

        // Invalidate Spatie permission cache
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        return response()->json([
            'success' => 'Permission deleted successfully.'
        ], 200);
    }
}
