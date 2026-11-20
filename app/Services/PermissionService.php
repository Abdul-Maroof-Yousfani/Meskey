<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Caches the permission lookups behind canAccess() and the check.company middleware.
 *
 * Before: every canAccess() call ran 3 queries (company pivot, role, permissions).
 * Now:    the first request runs them once, later requests read from the cache,
 *         and repeated calls within one request are answered from memory.
 *
 * Invalidation: every key carries a global ACL version. Any ACL change
 * (role permissions, user role/company assignment, user/role/permission delete)
 * must call PermissionService::flush(), which bumps the version so all old keys
 * are ignored and expire on their own TTL.
 */
class PermissionService
{
    private const TTL = 86400; // 24 hours
    private const VERSION_KEY = 'meskey:acl:version';

    /** Per-request memory: "userId:companyId" => resolved access data */
    private static array $memory = [];

    private static ?int $version = null;

    /**
     * Resolve the user's role and permissions for their current company.
     *
     * Returns:
     *   null                                      → no role assigned in this company
     *   ['role_id' => int, 'permissions' => null] → pivot role is not assigned to the user
     *   ['role_id' => int, 'permissions' => [...]]
     */
    public static function resolve(User $user): ?array
    {
        $companyId = (int) $user->current_company_id;
        $memoKey = $user->id . ':' . $companyId;

        if (array_key_exists($memoKey, self::$memory)) {
            return self::$memory[$memoKey];
        }

        $version = self::version();

        $role = Cache::remember(
            "meskey:acl:v{$version}:user:{$user->id}:company:{$companyId}",
            self::TTL,
            function () use ($user, $companyId) {
                $roleId = DB::table('company_user_role')
                    ->where('user_id', $user->id)
                    ->where('company_id', $companyId)
                    ->value('role_id');

                if ($roleId === null) {
                    return ['found' => false];
                }

                // Same rule as before: the pivot role must also be assigned to the user.
                $assigned = $user->roles()->where('id', $roleId)->exists();

                return ['found' => true, 'role_id' => (int) $roleId, 'assigned' => $assigned];
            }
        );

        if (!$role['found']) {
            return self::$memory[$memoKey] = null;
        }

        $permissions = null;
        if ($role['assigned']) {
            $permissions = Cache::remember(
                "meskey:acl:v{$version}:role:{$role['role_id']}:permissions",
                self::TTL,
                fn () => DB::table('role_has_permissions')
                    ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                    ->where('role_has_permissions.role_id', $role['role_id'])
                    ->pluck('permissions.name')
                    ->all()
            );
        }

        return self::$memory[$memoKey] = [
            'role_id' => $role['role_id'],
            'permissions' => $permissions,
        ];
    }

    /**
     * Same behaviour as the old canAccess() helper.
     */
    public static function canAccess(?string $permission): bool
    {
        $user = auth()->user();

        if ($user->user_type === 'super-admin') {
            return true;
        }

        $access = self::resolve($user);

        if ($access === null) {
            abort(403, 'No company role found for the current company.');
        }

        if ($access['permissions'] === null) {
            return false;
        }

        if ($permission && !in_array($permission, $access['permissions'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Invalidate all cached ACL data. Call after any role, permission or
     * user-assignment change.
     */
    public static function flush(): void
    {
        $next = (int) Cache::get(self::VERSION_KEY, 1) + 1;
        Cache::forever(self::VERSION_KEY, $next);

        self::$version = $next;
        self::$memory = [];
    }

    private static function version(): int
    {
        return self::$version ??= (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }
}
