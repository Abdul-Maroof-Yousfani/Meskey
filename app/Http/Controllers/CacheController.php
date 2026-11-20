<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CacheController extends Controller
{
    /**
     * "Clear Cache" button in the header.
     *
     * Every user:  this browser's cache for the site (CSS/JS/images) and the
     *              cached permissions, so changed rights show up straight away.
     * Super admin: also the whole server cache and compiled Blade views.
     */
    public function clear(): JsonResponse
    {
        $isSuperAdmin = auth()->user()->user_type === 'super-admin';

        if ($isSuperAdmin) {
            Cache::flush();
            Artisan::call('view:clear');
        } else {
            PermissionService::flush();
        }

        return response()
            ->json([
                'success' => $isSuperAdmin
                    ? 'Browser and server cache cleared.'
                    : 'Browser cache cleared.',
            ])
            // Tells the browser to drop its HTTP cache for this site (not cookies, so no logout)
            ->header('Clear-Site-Data', '"cache"');
    }

    /**
     * Super admin "Reset for all users".
     *
     * Bumps the global browser-cache version: every CSS/JS URL from versioned_asset()
     * changes, and each user's browser gets Clear-Site-Data once on its next page
     * load (see ResetBrowserCache middleware). Also clears the server cache.
     */
    public function resetAll(): JsonResponse
    {
        abort_unless(auth()->user()->user_type === 'super-admin', 403);

        $file = storage_path('app/browser_cache_version');
        $current = is_file($file) ? (int) trim((string) file_get_contents($file)) : 0;
        file_put_contents($file, (string) max($current + 1, time()), LOCK_EX);

        Cache::flush();
        Artisan::call('view:clear');

        return response()
            ->json(['success' => 'Cache reset for all users. Each user gets fresh files on their next page load.'])
            ->header('Clear-Site-Data', '"cache"');
    }
}
