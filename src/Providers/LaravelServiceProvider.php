<?php

namespace Zukitek\Sso\Providers;

use Illuminate\Support\Facades\Auth;

class LaravelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::viaRequest('request', function ($request) {
            return null;
        });

        $path = realpath(__DIR__ . '/../../config/config.php');

        $this->publishes([$path => config_path('sso.php')], 'config');

        $this->mergeConfigFrom($path, 'sso');

        $this->aliasMiddleware();

        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        $this->loadViewsFrom(__DIR__ . '/../views', 'sso');

        $this->publishes([
            __DIR__ . '/../views' => resource_path('views/vendor/sso'),
        ]);
    }

    protected function aliasMiddleware()
    {
        $router = $this->app['router'];

        $method = method_exists($router, 'aliasMiddleware') ? 'aliasMiddleware' : 'middleware';

        foreach ($this->middlewareAliases as $alias => $middleware) {
            $router->$method($alias, $middleware);
        }
    }
}
