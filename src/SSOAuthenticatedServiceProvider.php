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
        // 1️⃣ Load views from vendor package
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ssoauth');

        // 2️⃣ Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/ssoauth'),
        ], 'ssoauth-views');

        // 3️⃣ Publish JS assets
        $jsPath = __DIR__ . '/Assets/js';
        if (is_dir($jsPath)) {
            $this->publishes([
                $jsPath => public_path('ssoauth/js'),
            ], 'ssoauth-assets');
        }

        // 4️⃣ Publish config
        $this->publishes([
            __DIR__ . '/../config/sso.php' => config_path('sso.php'),
        ], 'ssoauth-config');

        // 5️⃣ Publish routes
        $this->publishes([
            __DIR__ . '/../routes/web.php' => base_path('routes/ssoauthenticated.php'),
        ], 'ssoauth-routes');

        // 6️⃣ Register routes
        $this->registerRoutes();

        // 7️⃣ Register middleware alias
        $this->app['router']->aliasMiddleware('sso.auth', SsoAuth::class);

        // 8️⃣ Register Blade component
        Blade::component('ssoauth-layout-main', \DevOps213\SSOauthenticated\View\Components\Layout\Main::class);

        // 9️⃣ Register console commands
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
        // Check if we should use published routes or package routes
        $publishedRoutesPath = base_path('routes/ssoauthenticated.php');

        if (file_exists($publishedRoutesPath)) {
            // If published routes exist, load them from the application routes directory
            Route::group([
                'namespace' => 'DevOps213\SSOauthenticated\Http\Controllers',
                'middleware' => ['web'],
                'prefix' => 'sso',
            ], function () {
                require base_path('routes/ssoauthenticated.php');
            });
        } else {
            // Otherwise load from package
            Route::group([
                'namespace' => 'DevOps213\SSOauthenticated\Http\Controllers',
                'middleware' => ['web'],
                'prefix' => 'sso',
            ], function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }
    }

}