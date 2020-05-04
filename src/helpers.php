<?php
if (!function_exists('sso_url')) {
    function sso_url($path = '')
    {
        $ssoUrl = config('sso.sso_server_url');
        return $ssoUrl . $path;
    }
}

if (!function_exists('get_cookie')) {
    function get_cookie($name, $default = null)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
    }
}

if (!function_exists('rm_web_token')) {
    function rm_web_token()
    {
        $cookieInfo = config('sso.cookie_info');
        $authKeys = config('sso.auth_keys');
        foreach ($authKeys as $key => $name) {
            if ($key === 'access_token') {
                setcookie($name, '', 0, $cookieInfo['path'], $cookieInfo['domain'], $cookieInfo['secure'], $cookieInfo['http_only']);
            } else {
                setcookie($key, '', 0, $cookieInfo['path'], $cookieInfo['domain'], false, false);
            }
        }
    }
}

if (!function_exists('prepare_url')) {
    function prepare_url($url, array $params = [])
    {
        foreach ($params as $key => $value) {
            $url = str_replace(":{$key}", $value, $url);
        }
        return $url;
    }
}