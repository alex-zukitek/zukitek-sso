<?php

namespace Zukitek\Sso\Providers;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Zukitek\Sso\Middleware\CrossDomainMiddleware;
use Zukitek\Sso\Middleware\SsoAuthApiMiddleware;
use Zukitek\Sso\Middleware\SsoAuthWebMiddleware;

abstract class ServiceProvider extends IlluminateServiceProvider
{
    protected $middlewareAliases = [
        'sso.auth.web' => SsoAuthWebMiddleware::class,
        'sso.auth.api' => SsoAuthApiMiddleware::class,
        'cross.domain' => CrossDomainMiddleware::class,
    ];

    abstract public function boot();

    public function register()
    {
    }
}
