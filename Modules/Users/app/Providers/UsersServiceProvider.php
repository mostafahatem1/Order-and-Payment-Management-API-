<?php

namespace Modules\Users\Providers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Users\Models\User;
use Modules\Users\Support\ApiResponse;
use Tymon\JWTAuth\Exceptions\JWTException;

class UsersServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $configPath = __DIR__ . '/../../config/config.php';

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'users');
        }

        $this->registerAuthConfig();
    }

    public function boot(): void
    {
        $this->registerExceptionResponses();
        $this->registerRoutes();

        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');
    }

    private function registerRoutes(): void
    {
        $routesPath = __DIR__ . '/../../routes/api.php';

        if (is_file($routesPath)) {
            Route::middleware('api')
                ->prefix('api')
                ->group($routesPath);
        }
    }

    private function registerAuthConfig(): void
    {
        Config::set('auth.guards.api', [
            'driver' => 'jwt',
            'provider' => 'users',
        ]);

        Config::set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
    }

    private function registerExceptionResponses(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        $handler->renderable(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $this->unauthorizedResponse();
        });

        $handler->renderable(function (JWTException $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $this->unauthorizedResponse();
        });
    }

    private function unauthorizedResponse(): JsonResponse
    {
        return ApiResponse::error(
            'Unauthenticated.',
            [
                'token' => ['Invalid or missing authentication token.'],
            ],
            401
        );
    }
}
