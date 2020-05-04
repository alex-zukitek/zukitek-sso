<?php

namespace Zukitek\Sso\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SsoAuthApiMiddleware
{
    protected $config;

    /**
     * Web will use cookie to check auth
     *
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector|mixed
     */

    public function handle($request, Closure $next)
    {
        $sso = config('sso');
        $token = $request->header('Authorization');
        $platform = $request->header('X-Platform-Request');

        if ($token) {
            $token = str_replace('Bearer ', '', $token);
        }
        if (empty($token) && $platform === 'web') {
            $token = get_cookie($sso['auth_keys']['access_token']);
        }
        if ($token && in_array($token, ['deleted'])) {
            $token = null;
        }
        $errorMessage = 'Please login to continue';
        $errorCode = 401;
        $httpError = 401;

        if ($token) {
            try {
                // create user account
                $modelUser = $sso['model']['user'];
                $client = new \GuzzleHttp\Client();
                $path = "{$sso['sso_api']['me']}?token={$token}";
                $urlApi = sso_url($path);
                $response = $client->request('GET', $urlApi);
                $body = json_decode($response->getBody(), true);
                $ssoUser = $body['data']['user'];
                $email = $ssoUser['email'];
                $email = strtolower($email);
                $user = $modelUser::where('email', $email)->first();
                if ($user) {
                    $user->syncDataLocal($ssoUser);
                    if (!empty($sso['callbacks']['sync_user_local'])) {
                        call_user_func(
                            $sso['callbacks']['sync_user_local'],
                            $user
                        );
                    }
                    Auth::guard('request')->setUser($user);
                    return $next($request);
                } else {
                    if (empty($ssoUser->services[$sso['client_id']])) {
                        $errorMessage = 'This service unavailable for your account';
                        $errorCode = 423;
                        $httpError = 423;
                    } else {
                        if ($sso['sync_user_local']) {
                            $newUser = call_user_func(
                                $sso['callbacks']['create_user_client'],
                                $ssoUser
                            );
                            Auth::guard('request')->setUser($newUser);
                            return $next($request);
                        } else {
                            $errorMessage = 'Please register this service to access';
                            $errorCode = 403;
                            $httpError = 403;
                        }
                    }
                }
            } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                $httpError = $e->getResponse()->getStatusCode();
                if ($httpError === 401) {
                    $response = json_decode($e->getResponse()->getBody()->getContents(), true);
                    $errorMessage = $response['message'];
                    $errorCode = $response['status_code'];
                } else {
                    $errorMessage = $e->getMessage();
                    $errorCode = $httpError;
                }
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $errorCode = 400;
                $httpError = 400;
            }
        }

        $response = [
            'success' => false,
            'message' => $errorMessage,
            'status_code' => $errorCode,
            'errors' => [],
        ];
        return response()->json($response, $httpError);
    }
}
