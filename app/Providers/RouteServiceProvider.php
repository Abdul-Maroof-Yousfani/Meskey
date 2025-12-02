<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
            // Arrival routes
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('arrival')
                ->group(base_path('routes/arrival.php'));
            // ACL routes
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('acl')
                ->group(base_path('routes/acl.php'));
            // Master routes
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('master')
                ->group(base_path('routes/master.php'));
            //procurement
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('procurement')
                ->group(base_path('routes/procurement.php'));

            //procurement
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('sales')
                ->group(base_path('routes/sales.php'));
            
            //finance
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('finance')
                ->group(base_path('routes/finance.php'));
            //production
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('production')
                ->group(base_path('routes/production.php'));
            //reports
            Route::middleware(['auth', 'web', 'check.company'])
                ->prefix('reports')
                ->group(base_path('routes/reports.php'));
            // Web routes
            Route::middleware('web')
                ->group(function () {
                    // Default web routes
                    require base_path('routes/web.php');
                    //  require base_path('routes/acl.php');
                    //require base_path('routes/master.php');
                    // Load custom modular routes
                    //   Route::middleware(['auth', 'check.company'])
                    //   ->group(function () {
                    //    require base_path('routes/acl.php');
                    // require base_path('routes/dashboard.php');
                    //    });
    
                    // Route::middleware(['auth'])
                    //    ->group(function () {
                    // require base_path('routes/profile.php');
                    //   });
                });
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
