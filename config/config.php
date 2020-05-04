<?php
return [
    'client_id' => env('SSO_CLIENT_ID', 'client id'), // ALICE, TELECONSULT, MYHOME, REWARDS, MARKETPLACE, MENTAL_WELLNESS, LIFESTYLE_CARE

    // Key and iv to encode data - base64 encode, use to call private API to hash data
    'client_key' => env('SSO_CLIENT_KEY', 'secret key'),

    // URL of sso server
    'sso_server_url' => env('SSO_SERVER_URL', 'http://account.zukitek.test'),

    'sso_server_api_url' => env('SSO_SERVER_API_URL', 'http://account.zukitek.test'),

    // Client will redirect this url to login
    'sso_login_url' => '/auth/checking',

    // Client will redirect this url to logout
    'sso_logout_url' => '/auth/logout',

    'sso_api' => [
        // URL api to get sso user info by token /api/me?token=asdasd...
        'me' => '/api/auth/me',
        // Make payment
        'make_payment'  => [
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
        'domain' => env('SSO_COOKIE_DOMAIN', null),
        'secure' => env('SSO_COOKIE_SECURE', false),
        'http_only' => env('SSO_COOKIE_HTTP_ONLY', false),
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

        // SSO server will call back and remove token from iframe
        'auth/remove-access-frame' => [
            'method' => 'get',
            'middleware' => [],
            'name' => 'sso.token.remove',
            'namespace' => '\Zukitek\Sso\Controllers',
            'action' => 'ZukiSsoController@removeToken'
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
