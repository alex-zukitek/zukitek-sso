<?php

namespace Zukitek\Sso;

use Illuminate\Support\Facades\Log;

class SsoData
{
    private static $_data = false;

    private static $_localUser = false;

    private static function _getUser()
    {
        $ssoUser = null;
        try {
            $sso = config('sso');
            $request = app('Illuminate\Http\Request');
            $platform = $request->header('X-Platform-Request');

            // Get token from request or cookie
            $token = $request->input('_sso_token', $request->header('Authorization'));
            if (!$token && $platform !== 'app') {
                $token = get_cookie($sso['auth_keys']['access_token']);
            } else {
                $token = str_replace('Bearer ', '', $token);
            }
            if ($token && in_array($token, ['deleted'])) {
                $token = null;
            }
            if ($token) {
                $fnGetSsoUser = function () use ($sso, $token) {
                    try {
                        $client = new \GuzzleHttp\Client();
                        $path = "{$sso['sso_api']['me']}?token={$token}";
                        $urlApi = sso_url($path);
                        $response = $client->request('GET', $urlApi);
                        $body = json_decode($response->getBody(), true);
                        return $body['data']['user'];
                    } catch (\GuzzleHttp\Exception\BadResponseException $e) {
                        Log::debug('$fnGetSsoUser 1: ' . $e->getMessage());
                        return null;
                    } catch (\Exception $e) {
                        Log::debug('$fnGetSsoUser 2: ' . $e->getMessage());
                        return null;
                    }
                };
                if (isset($sso['cache_time_life'])) {
                    $md5 = md5($token);
                    $keyCache = date('ymd') . "sso_{$md5}";
                    $ssoUser = \Illuminate\Support\Facades\Cache::remember($keyCache, $sso['cache_time_life'], $fnGetSsoUser);
                    if (!$ssoUser) {
                        \Illuminate\Support\Facades\Cache::forget($keyCache);
                    }
                } else {
                    $ssoUser = $fnGetSsoUser();
                }
            }
        } catch (\Exception $e) {
            Log::debug('_getUser :' . $e->getMessage());
            $ssoUser = null;
        }
        return $ssoUser;
    }

    public static function setUser($data)
    {
        self::$_data = $data;
    }

    public static function user()
    {
        if (self::$_data === false) {
            self::$_data = self::_getUser();
        }
        return self::$_data;
    }

    public static function check()
    {
        return !is_null(self::user());
    }

    public static function id()
    {
        return self::user()['id'] ?? null;
    }

    public static function localUser()
    {
        if (self::$_localUser === false) {
            $ssoUser = self::user();
            if ($ssoUser) {
                $modelUser = config('sso.model.user');
                $email = $ssoUser['email'];
                $email = strtolower($email);
                $cacheTimeLife = config('sso.cache_time_life');
                if (isset($cacheTimeLife)) {
                    $keyCache = "{$email}.{$ssoUser['id']}";
                    $user = \Illuminate\Support\Facades\Cache::remember($keyCache, $cacheTimeLife, function () use ($modelUser, $email) {
                        return $modelUser::where('email', $email)->first();
                    });
                    if (!$user) {
                        \Illuminate\Support\Facades\Cache::forget($keyCache);
                    }
                } else {
                    $user = $modelUser::where('email', $email)->first();
                }
                self::$_localUser = $user;
            } else {
                self::$_localUser = null;
            }
        }
        return self::$_localUser;
    }

    public static function setLocalUser($localUser)
    {
        self::$_localUser = $localUser;
    }

    public static function destroy()
    {
        self::$_data = null;
        self::$_localUser = null;
    }
}
