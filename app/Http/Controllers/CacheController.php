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
}
