<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Models\Acl\{Company};
use App\Models\{User, Product, Arrival\ArrivalTicket};
use App\Models\Arrival\Freight;
use App\Observers\{UserObserver, CompanyObserver, ProductObserver, ArrivalTicketObserver, FreightObserver};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Company::observe(CompanyObserver::class);
        User::observe(UserObserver::class);
        Product::observe(ProductObserver::class);
        Freight::observe(FreightObserver::class);
        ArrivalTicket::observe(ArrivalTicketObserver::class);


        // Register custom Blade directive
        Blade::directive('canAccess', function ($expression) {
            return "<?php if (canAccess($expression)): ?>";
        });

        Blade::directive('endcanAccess', function () {
            return "<?php endif; ?>";
        });


        Blade::directive('routerLink', function ($routeUrl) {
            return "<?php echo 'href=\"' . $routeUrl . '\" onclick=\"loadPageContent(\\\".'. $routeUrl .'.\\\")\"'; ?>";
        });
    }
}
