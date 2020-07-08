<?php

namespace Zukitek\Sso\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class SsoAuthWebMiddleware
{
    /**
     * Web will use cookie to check auth
     *
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector|mixed
     */
    public function handle($request, Closure $next, $applications = null)
    {
        $sso = config('sso');

        // Get token from request or cookie
        $token = $request->has('_sso_token') ? $request->input('_sso_token') : get_cookie($sso['auth_keys']['access_token']);

        if ($token && in_array($token, ['deleted'])) {
            $token = null;
        }

        $errorMessage = 'Please login to continue';
        $errorCode = 401;
        $httpError = 401;

        if ($token) {
            $fnGetSsoUser = function () use ($sso, $token, &$errorMessage, &$errorCode, &$httpError) {
                try {
                    $client = new \GuzzleHttp\Client();
                    $path = "{$sso['sso_api']['me']}?token={$token}";
                    $urlApi = sso_url($path);
                    $response = $client->request('GET', $urlApi);
                    $body = json_decode($response->getBody(), true);
                    return $body['data']['user'];
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
                    return null;
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    $errorCode = 400;
                    return null;
                }
            };
            if (isset($sso['cache_time_life'])) {
                $md5 = md5($token);
                $keyCache = date('ymdh') . "sso_{$md5}";
                $ssoUser = \Illuminate\Support\Facades\Cache::remember($keyCache, $sso['cache_time_life'], $fnGetSsoUser);
                if (!$ssoUser) {
                    \Illuminate\Support\Facades\Cache::forget($keyCache);
                }
            } else {
                $ssoUser = $fnGetSsoUser();
            }
            if ($ssoUser) {
                try {
                    // create user account
                    $modelUser = $sso['model']['user'];
                    $canAccess = false;
                    if ($applications) {
                        $arrApplications = explode('+', $applications);
                        foreach ($ssoUser['applications'] as $application) {
                            if (in_array($application['code'], $arrApplications)) {
                                $canAccess = true;
                                break;
                            }
                        }
                        if (!$canAccess) {
                            throw new \Exception('Limit permissions, please contact admin to continue.');
                        }
                    }
                    $email = $ssoUser['email'];
                    $email = strtolower($email);
                    $user = $modelUser::where('email', $email)->first();
                    if ($user) {
                        $callback = isset($sso['callbacks']['sync_user_local']) ? $sso['callbacks']['sync_user_local'] : [];
                        $user->syncDataLocal($ssoUser, $callback);
                        Auth::guard('request')->setUser($user);
                        return $next($request);
                    } else {
                        if ($sso['sync_user_local']) {
                            $callback = $sso['callbacks']['create_user_client'];
                            $callbackClass = $callback[0];
                            $callbackMethod = $callback[1];
                            $newUser = $callbackClass::$callbackMethod($ssoUser);
                            Auth::guard('request')->setUser($newUser);
                            return $next($request);
                        } else {
                            $errorMessage = 'Please register this service to access';
                            $errorCode = 403;
                            $httpError = 403;
                        }
                    }
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    $errorCode = 400;
                    $httpError = 400;
                }
            }
        }

        if ($request->expectsJson()) {
            $response = [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $errorCode,
                'errors' => [],
            ];
            return response()->json($response, $httpError);
        } else {
            if ($errorCode === 423) {
                return redirect()->route('sso.errors', ['code' => 423]);
            } else if ($errorCode === 403 && $sso['url_to_register_account']) {
                return redirect($sso['url_to_register_account']);
            } else if ($errorCode === 401) {
                return redirect()->route('sso.login');
            } else {
                return redirect()->route('sso.errors', ['code' => 400, 'errorMessage' => $errorMessage]);
            }
        }
    }
}
