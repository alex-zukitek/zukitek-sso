<?php

namespace Zukitek\Sso;

use Illuminate\Support\Facades\Log;

class SsoData
{
    private static $_data = false;

    private static $_cacheKey;

    private static $_cacheKeyLocal;

    private static $_localUser = false;

    private static function _getUser($callFrom = null)
    {
        $ssoUser = null;
        try {
            $sso = config('sso');
            $request = app('Illuminate\Http\Request');
            $platform = $request->header('X-Platform-Request');
            $device = $request->header('X-Device-Request');

            if ($callFrom === 'web') {
                $token = $request->input('_sso_token', get_cookie($sso['auth_keys']['access_token']));
            } elseif ($callFrom === 'api') {
                // Get token from request or cookie
                $token = $request->input('_sso_token', $request->header('Authorization'));
                if ($token) {
                    $token = str_replace('Bearer ', '', $token);
                }
            } else {
                // Get token from request or cookie
                $token = $request->input('_sso_token', $request->header('Authorization'));
                if ($token) {
                    $token = str_replace('Bearer ', '', $token);
                } else {
                    $token = get_cookie($sso['auth_keys']['access_token']);
                }
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
                    $keyCache = self::getCacheKey($token, $device);
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

    public static function user($callFrom = null)
    {
        if (self::$_data === false) {
            self::$_data = self::_getUser($callFrom);
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
                    $cacheLocal = self::getCacheKeyLocal($ssoUser);
                    $user = \Illuminate\Support\Facades\Cache::remember($cacheLocal, $cacheTimeLife, function () use ($modelUser, $email) {
                        return $modelUser::where('email', $email)->first();
                    });
                    if (!$user) {
                        \Illuminate\Support\Facades\Cache::forget($cacheLocal);
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

    public static function getCacheKey($token, $deviceID = null)
    {
        if (!self::$_cacheKey) {
            $md5 = md5($token);
            $deviceID = $deviceID ? substr($deviceID, 0, 20) : '';
            self::$_cacheKey = "{$deviceID}_{$md5}";
        }
        return self::$_cacheKey;
    }

    public static function getCacheKeyLocal(array $ssoUser)
    {
        if (!self::$_cacheKeyLocal) {
            self::$_cacheKeyLocal = "{$ssoUser['email']}.{$ssoUser['id']}";
        }
        return self::$_cacheKeyLocal;
    }
}
