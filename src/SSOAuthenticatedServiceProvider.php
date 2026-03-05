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
        // 1️⃣ Load views from package
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ssoauth');

        // 2️⃣ Publish views for customization
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/ssoauth'),
        ], 'ssoauth-views');

        // 3️⃣ Publish JS assets if exist
        $jsPath = __DIR__ . '/../Assets/js';
        if (is_dir($jsPath)) {
            $this->publishes([
                $jsPath => public_path('ssoauth/js'),
            ], 'ssoauth-assets');
        }

        // 4️⃣ Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/sso.php' => config_path('sso.php'),
        ], 'ssoauth-config');

        $this->publishes([
            __DIR__ . '/../routes/web.php' => base_path('routes/ssoauthenticated.php'),
        ], 'sso-routes');

        // 5️⃣ Register routes
        $this->registerRoutes();

        // 6️⃣ Register middleware alias
        $this->app['router']->aliasMiddleware('sso.auth', SsoAuth::class);

        // 7️⃣ Register Blade components
        Blade::component('ssoauth-layout-main', \DevOps213\SSOauthenticated\View\Components\Layout\Main::class);

        // 8️⃣ Register console commands
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
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../config/sso.php', 'sso');
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'namespace' => 'DevOps213\SSOauthenticated\Http\Controllers',
            'middleware' => ['web'],
            'prefix' => 'sso', // easier prefix than ssoauth
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }
}
