<?php
$routes = config('sso.routes');

foreach ($routes as $path => $value) {
    Route::group([
        'namespace' => $value['namespace'],
        'middleware' => $value['middleware'],
    ], function ($router) use ($path, $value) {
        $router->{$value['method']}($path, [
            'as' => $value['name'], 'uses' => $value['action']
        ]);
    });
    Route::get('/sso/errors/{code}', [
        'as' => 'sso.errors', 'uses' => function($code) {
            return view("sso::error_{$code}");
        }
    ]);
    if (config('app.env') !== 'production') {
        // TEST CALL API PRIVATE
        Route::get('/sso/secure-api-test', function () {
            $params = [
                'input1' => '111111',
                'input2' => '222222',
                'input3' => '333333',
                'input4' => '444444',
            ];
            $response = \Zukitek\Sso\Secure\SsoApi::request('api/secure/test', 'GET', $params);
            dd($response);
        });
    }
}
