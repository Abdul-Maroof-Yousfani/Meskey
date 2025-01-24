<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class SetTimezone
{
    public function handle($request, Closure $next)
    {
        // Get the authenticated user or business (example based on your use case)
        $business = auth()->user()->business ?? null;

        if ($business && $business->country) {
            // Get timezone from the related country
            $timezones = json_decode($business->country->timezones, true);
            $timezone = $timezones[0]['zoneName'] ?? null;

            // Set application timezone
            if ($timezone) {
                Config::set('app.timezone', $timezone);
                date_default_timezone_set($timezone); // Set PHP's timezone
            }
        }

        return $next($request);
    }
}
