<?php

namespace Modules\Users\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__.'/../../config/config.php';

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'users');
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
