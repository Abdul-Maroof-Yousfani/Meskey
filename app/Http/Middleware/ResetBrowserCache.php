<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Super admin "Reset for all users": once the global browser-cache version is bumped,
 * each browser gets a Clear-Site-Data: "cache" header on its next page load, one time,
 * and a cookie records that it is up to date.
 */
class ResetBrowserCache
{
    private const COOKIE = 'browser_cache_version';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $version = browser_cache_version();

        // Never reset yet, or not a normal page load (AJAX, JSON, downloads)
        if ($version === 0 || $request->ajax() || $request->expectsJson() || !$request->isMethod('GET')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        if ($contentType !== '' && !str_contains($contentType, 'text/html')) {
            return $response;
        }

        if ((int) $request->cookie(self::COOKIE) === $version) {
            return $response;
        }

        $response->headers->set('Clear-Site-Data', '"cache"');
        $response->headers->setCookie(cookie()->forever(self::COOKIE, (string) $version));

        return $response;
    }
}
