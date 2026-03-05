<?php

namespace DevOps213\SSOauthenticated;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use DevOps213\SSOauthenticated\Console\InstallUserProfile;
use DevOps213\SSOauthenticated\Http\Middleware\SsoAuth;

class UserProfileServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 1️⃣ Load views from package
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'userprofile');

        // 2️⃣ Publish views for customization
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/userprofile'),
        ], 'userprofile-views');

        // 3️⃣ Publish JS assets
        $jsPath = __DIR__ . '/Assets/js';
        if (is_dir($jsPath)) {
            $this->publishes([
                $jsPath => public_path('vendor/userprofile/js'),
            ], 'userprofile-assets');
        }

        // 4️⃣ Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/sso.php' => config_path('sso.php'),
        ], 'userprofile-config');

        // 5️⃣ Register routes
        $this->registerRoutes();

        $this->app['router']->aliasMiddleware('sso.auth', SsoAuth::class);

        // 6️⃣ Register Blade components
        Blade::component('userprofile-layout-main', \DevOps213\SSOauthenticated\View\Components\Layout\Main::class);

        // 7️⃣ Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallUserProfile::class,
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
     * Register routes for the package.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'namespace' => 'DevOps213\SSOauthenticated\Http\Controllers',
            'middleware' => ['web'],
            'prefix' => 'userprofile',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }
}
