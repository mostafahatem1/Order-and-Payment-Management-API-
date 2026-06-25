<?php

namespace Modules\Orders\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OrdersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__.'/../../config/config.php';

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'orders');
        }
    }

    public function boot(): void
    {
        $this->registerRoutes();

        $this->loadMigrationsFrom(__DIR__.'/../../Database/Migrations');
    }

    private function registerRoutes(): void
    {
        $routesPath = __DIR__.'/../../routes/api.php';

        if (is_file($routesPath)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($routesPath);
        }
    }
}
