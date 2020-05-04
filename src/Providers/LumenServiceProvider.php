<?php
namespace Zukitek\Sso\Providers;

use Illuminate\Support\Facades\Auth;

class LumenServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../migrations');
    }

    public function register()
    {
        Auth::viaRequest('request', function ($request) {
            return null;
        });

        $this->app->configure('sso');

        $path = realpath(__DIR__.'/../../config/config.php');

        $this->mergeConfigFrom($path, 'sso');

        $this->app->routeMiddleware($this->middlewareAliases);

        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        $this->loadViewsFrom(__DIR__.'/../views', 'sso');

        parent::register();
    }
}
