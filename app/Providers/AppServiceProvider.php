<?php

namespace App\Providers;

use App\Http\Requests\Master\AccountRequest;
use Illuminate\Support\ServiceProvider;
use App\Models\Acl\{Company};
use App\Models\{User, Product, Arrival\ArrivalTicket};
use App\Models\Arrival\Freight;
use App\Models\Procurement\PurchaseFreight;
use App\Observers\{UserObserver, CompanyObserver, ProductObserver, ArrivalTicketObserver, FreightObserver, PurchaseFreightObserver};
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
     * 
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Company::observe(CompanyObserver::class);
        User::observe(UserObserver::class);
        Product::observe(ProductObserver::class);
        Freight::observe(FreightObserver::class);
        PurchaseFreight::observe(PurchaseFreightObserver::class);
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


        view()->composer('*', function ($view) {
            try {
                $controllerRequestMap = [
                    'AccountController' => \App\Http\Requests\Master\AccountRequest::class,
                    'InitialSamplingController' => \App\Http\Requests\Arrival\ArrivalSamplingResultRequest::class,
                    'ArrivalLocationController' => \App\Http\Requests\Master\ArrivalLocationRequest::class,
                    'ArrivalLocationTransferController' => \App\Http\Requests\Master\ArrivalLocationTransferRequest::class,
                    'BrokerController' => \App\Http\Requests\Master\BrokerRequest::class,
                    'CategoryController' => \App\Http\Requests\Category\CategoryRequest::class,
                    'CompanyController' => \App\Http\Requests\Company\StoreCompanyRequest::class,
                    'CompanyLocationController' => \App\Http\Requests\Master\CompanyLocationRequest::class,
                    'ProductController' => \App\Http\Requests\Product\StoreProductRequest::class,
                    'ProductSlabController' => \App\Http\Requests\Master\ProductSlabRequest::class,
                    'ProductSlabTypeController' => \App\Http\Requests\Master\ProductSlabTypeRequest::class,
                    'StationController' => \App\Http\Requests\Master\StationRequest::class,
                    'SupplierController' => \App\Http\Requests\Master\SupplierRequest::class,
                    'TruckTypeController' => \App\Http\Requests\Master\TruckTypeRequest::class,
                    'FirstWeighbridgeController' => \App\Http\Requests\Master\FirstWeighbridgeRequest::class,
                    'UnitOfMeasureController' => \App\Http\Requests\Master\UnitOfMeasureRequest::class,
                    'UserController' => \App\Http\Requests\User\UserStoreRequest::class,
                    'RoleController' => \App\Http\Requests\Role\StoreRoleRequest::class,
                    'RegionController' => \App\Http\Requests\Region\StoreRegionRequest::class,
                    'TicketController' => \App\Http\Requests\Arrival\ArrivalTicketRequest::class,
                    'InitialSamplingController' => \App\Http\Requests\Arrival\ArrivalInitialSamplingResultRequest::class,
                    'FreightController' => \App\Http\Requests\Arrival\FreightRequest::class,
                    'AddCustomerController' => \App\Http\Requests\Customer\AddCustomerRequest::class,
                    'DeleteCustomerController' => \App\Http\Requests\Customer\DeleteCustomerRequest::class,
                    'UpdateCustomerController' => \App\Http\Requests\Customer\UpdateCustomerRequest::class,
                    'AddExpenseController' => \App\Http\Requests\Expense\AddExpenseRequest::class,
                    'UpdateExpenseController' => \App\Http\Requests\Expense\UpdateExpenseRequest::class,
                    'GateBuyingController' => \App\Http\Requests\GateBuyingRequest::class,
                    'PurchaseOrderController' => \App\Http\Requests\ArrivalPurchaseOrderRequest::class,
                    'IndicativePriceController' => \App\Http\Requests\Master\IndicativePriceRequest::class,
                    'PurchaseOrderController' => \App\Http\Requests\ArrivalPurchaseOrderRequest::class,
                    'PurchaseFreightController' => \App\Http\Requests\Procurement\PurchaseFreightRequest::class,
                    'TicketContractController' => \App\Http\Requests\Procurement\TicketContractRequest::class,
                ];

                $routeAction = request()->route()?->getAction();
                $controllerClass = $routeAction['controller'] ?? null;

                if ($controllerClass) {
                    [$controllerClassFull, $method] = explode('@', $controllerClass);
                    $controllerName = class_basename($controllerClassFull);

                    if (isset($controllerRequestMap[$controllerName])) {
                        $requestClass = $controllerRequestMap[$controllerName];

                        if (class_exists($requestClass)) {
                            $rules = (new $requestClass())->rules();
                            $requiredFields = array_keys(array_filter($rules, function ($rule) {
                                if (is_array($rule)) {
                                    return in_array('required', $rule);
                                }
                                return str_contains($rule, 'required');
                            }));

                            $view->with('requiredFields', $requiredFields);
                            return;
                        }
                    }
                }

                $view->with('requiredFields', []);
            } catch (\Exception $e) {
                $view->with('requiredFields', []);
            }
        });
    }
}
