# ZUKITEK SSO

ZUKITEK SSO is a **SSO authentication** library providing an easier and expressive way to login, lougout for ZUKITEK APPs. The package includes ServiceProviders and Facades for easy **Laravel**, **Lumen** integration.

## Requirements

- PHP >=7.1.*
- OPENSSL

## Supported Laravel and lumen version

- Laravel >= 5.6
- Lumen >= 6.0

## Getting started

- [Install package] composer require zukitek/sso
- Laravel: Autoload Package Discovery
- Lumen: 
```php 
// Add this line into bootstrap/app.php
$app->register(\Zukitek\Sso\Providers\LumenServiceProvider::class);
```
- Add trait to model User: Zukitek\Sso\Traits\SsoUserModelTrait

- [Add column sso_user to table users] php artisan migrate

- Add guard "request" to config/auth.php
```php 
  'guards' => [
      ...
      'request' => [
          'driver' => 'request',
      ],
  ],
```

- Use Middleware to verify user in web: 
```php 
  'sso.auth.web'  =>  Zukitek\Sso\Middleware\SsoAuthWebMiddleware,
  'sso.auth.api'  =>  Zukitek\Sso\Middleware\SsoAuthApiMiddleware,
```

- Get auth user
```php 
  $user = Auth::guard('request')->getUser();
```


## custom config (config/sso.php)
```php
<?php
return [
    'client_id' => env('SSO_CLIENT_ID', 'client id'), // ALICE, EUDA, MYHOME

    // Key and iv to encode data - base64 encode, use to call private API to hash data
    'client_key' => env('SSO_CLIENT_KEY', 'secret key'),

    // URL of sso server
    'sso_server_url' => env('SSO_SERVER_URL', 'http://account.zukitek.test'),

    'sso_server_api_url' => env('SSO_SERVER_API_URL', 'http://account.zukitek.test'),

    // Client will redirect this url to login
    'sso_login_url' => '/auth/checking',

    // Client will redirect this url to logout
    'sso_logout_url' => '/auth/logout',

    'web_application_code' => env('SSO_WEB_APPLICATION_CODE', 'WEB_APPLICATION_CODE'), // EUDA_ADMIN_WEB, MYHOME_ADMIN_WEB,

    'cache_time_life' => env('SSO_CACHE_TIME_LIFE', 5 * 60), // From laravel v >= 5.8 , it is seconds, before it is minutes

    'sso_api' => [
        // URL api to get sso user info by token /api/me?token=asdasd...
        'me' => '/api/auth/me',
        // Make payment
        'make_payment' => [
            'method' => 'post',
            'path' => '/api/payment'
        ],
    ],

    'sso_secure_api' => [
        'auth_register' => [
            'method' => 'post',
            'path' => '/api/secure/auth/register'
        ],
        'user_show' => [
            'method' => 'get',
            'path' => '/api/secure/user/:ssoId'
        ],
        'user_update' => [
            'method' => 'put',
            'path' => '/api/secure/user/:ssoId'
        ],
        'user_destroy' => [
            'method' => 'delete',
            'path' => '/api/secure/user/:ssoId'
        ],
        'push_notifications' => [
            'method' => 'post',
            'path' => '/api/secure/notifications'
        ],
    ],

    // After SSO auth, server SSO will redirect to this url
    'redirect_login_success' => env('SSO_LOGIN_REDIRECT_BACK', 'http://abc.zukitek.test'),

    // After logout SSO auth, server SSO will redirect to this url
    'redirect_logout_success' => env('SSO_LOGOUT_REDIRECT_BACK', 'http://account.zukitek.test'),

    'auth_keys' => [
        'access_token' => env('SSO_ACCESS_NAME', 'access_token'), // cookie token will save by name and secure
        // You can set more cookie name and value
        'logged_in' => 'true',  // cookie token will save by loggedIn and and value true and not secure
    ],

    'cookie_info' => [
        'path' => env('SSO_COOKIE_PATH', '/'),
        'domain' => env('SSO_COOKIE_DOMAIN'),
        'secure' => env('SSO_COOKIE_SECURE', false),
        'http_only' => env('SSO_COOKIE_HTTP_ONLY', false),
        'same_site' => env('SSO_COOKIE_SAME_SITE', 'Lax'), // None, Lax, Strict
    ],

    'routes' => [
        // Can custom route path, name
        'sso/login' => [
            'method' => 'get',
            'middleware' => [],
            'name' => 'sso.login',
            'namespace' => '\Zukitek\Sso\Controllers',
            'action' => 'ZukiSsoController@login',
        ],

        'sso/logout' => [
            'method' => 'get',
            'middleware' => [],
            'name' => 'sso.logout',
            'namespace' => '\Zukitek\Sso\Controllers',
            'action' => 'ZukiSsoController@logout'
        ],

        /**
         * Can not custom this info
         */
        // SSO server will call back and save access_token
        'auth/set-access-frame' => [
            'method' => 'get',
            'middleware' => [],
            'name' => 'sso.token.save',
            'namespace' => '\Zukitek\Sso\Controllers',
            'action' => 'ZukiSsoController@saveToken'
        ],

        'auth/cookie-checking' => [
            'method' => 'get',
            'middleware' => ['cross.domain'],
            'name' => 'sso.cookie.checking',
            'namespace' => '\Zukitek\Sso\Controllers',
            'action' => 'ZukiSsoController@isCookieSaved'
        ],

        // SSO server will call back and remove token from iframe
        'auth/remove-access-frame' => [
            'method' => 'get',
            'middleware' => [],
            'name' => 'sso.token.remove',
            'namespace' => '\Zukitek\Sso\Controllers',
            'action' => 'ZukiSsoController@removeToken'
        ],

        // SSO server will call back to sync User
        'sso/secure/sync-user' => [
            'method' => 'get',
            'middleware' => [],
            'name' => 'sso.secure.sync',
            'namespace' => '\Zukitek\Sso\Controllers\Secure',
            'action' => 'ZukiController@syncUser'
        ],
    ],

    'model' => [
        'user' => \App\Models\User::class
    ],

    // if not found user local, middleware will create user local or no
    'sync_user_local' => false,

    // URL to view register account form
    'url_to_register_account' => env('REGISTER_URL'),

    'callbacks' => [
        // if sync_user_local = true => will call this function to create user at client, call method on on Zukitek\Sso\Middleware
        'create_user_client' => [], // [Namespace\Class, staticMethodName] with param $ssoUserData
        // callback after sync data from sso and client
        'sync_user_local' => [],
    ]
];

```

## Contributing
Contributions to the ZUKITEK SSO library are welcome. Please note the following guidelines before submitting your pull request.

- Follow [PSR-2](http://www.php-fig.org/psr/psr-2/) coding standards.

## Contact
dzung.nguyen@zukitek.com

## License
ZUKITEK SSO is licensed under the [MIT License](http://opensource.org/licenses/MIT) and private.

Copyright 2020 - Zukitek LTD company
