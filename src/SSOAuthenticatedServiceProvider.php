<?php

namespace DevOps213\SSOauthenticated;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use DevOps213\SSOauthenticated\Console\InstallSSOAuthenticated;
use DevOps213\SSOauthenticated\Http\Middleware\SsoAuth;

class SSOAuthenticatedServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load views with namespace "ssoauth"
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'ssoauth');

        // Publish views
        $this->publishes([
            __DIR__ . '/Resources/views' => resource_path('views/vendor/ssoauth'),
        ], 'ssoauth-views');

        // Publish JS assets
        $jsPath = __DIR__ . '/assets/js';
        if (is_dir($jsPath)) {
            $this->publishes([
                $jsPath => public_path('ssoauth/js'),
            ], 'ssoauth-assets');
        }

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/sso.php' => config_path('sso.php'),
        ], 'ssoauth-config');

        // Register package routes
        $this->registerRoutes();

        // Register middleware
        $this->app['router']->aliasMiddleware('sso.auth', SsoAuth::class);

        // Register Blade component
        Blade::component('ssoauth-layout-main', \DevOps213\SSOauthenticated\View\Components\Layout\Main::class);

        // Register install command
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallSSOAuthenticated::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sso.php', 'sso');
    }

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