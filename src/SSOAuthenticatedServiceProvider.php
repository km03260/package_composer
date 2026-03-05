<?php

namespace DevOps213\SSOauthenticated;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use DevOps213\SSOauthenticated\Console\InstallSSOAuthenticated;
use DevOps213\SSOauthenticated\Http\Middleware\SsoAuth;

class SSOAuthenticatedServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 1️⃣ Load views with namespace "ssoauth"
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'ssoauth');

        // 2️⃣ Publish views for customization
        $this->publishes([
            __DIR__ . '/resources/views' => resource_path('views/vendor/ssoauth'),
        ], 'ssoauth-views');

        // 3️⃣ Publish JS assets
        $jsPath = __DIR__ . '/assets/js';
        if (is_dir($jsPath)) {
            $this->publishes([
                $jsPath => public_path('vendor/ssoauth/js'),
            ], 'ssoauth-assets');
        }

        // 4️⃣ Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/sso.php' => config_path('sso.php'),
        ], 'ssoauth-config');

        // 5️⃣ Register package routes
        $this->registerRoutes();

        // 6️⃣ Register middleware alias
        $this->app['router']->aliasMiddleware('sso.auth', SsoAuth::class);

        // 7️⃣ Register Blade component for layout
        Blade::component('ssoauth-layout-main', \DevOps213\SSOauthenticated\View\Components\Layout\Main::class);

        // 8️⃣ Register install console command
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallSSOAuthenticated::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge default package config
        $this->mergeConfigFrom(__DIR__ . '/../config/sso.php', 'sso');
    }

    /**
     * Register routes for the package.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'namespace' => 'DevOps213\SSOauthenticated\Http\Controllers',
            'middleware' => ['web'],
            'prefix' => 'sso',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }
}