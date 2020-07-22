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
        $expires = time() - 3600;
        $cookieInfo = config('sso.cookie_info');
        $authKeys = config('sso.auth_keys');
        foreach ($authKeys as $key => $name) {
            if ($key === 'access_token') {
                $key = $name;
            }
            setcookie(
                $key,
                '',
                [
                    'expires' => $expires,
                    'path' => $cookieInfo['path'],
                    'domain' => $cookieInfo['domain'],
                    'secure' => $cookieInfo['secure'],
                    'httponly' => $cookieInfo['http_only'],
                    'samesite' => 'None',
                ]
            );
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

if (!function_exists('sso_encrypt')) {
    function sso_encrypt(array $data, $secretKey, $encryptMethod = 'AES-256-CBC')
    {
        // hash
        $key = hash('sha256', $secretKey);
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $ivlen = openssl_cipher_iv_length($encryptMethod);
        $iv = substr($secretKey, 0, $ivlen);
        $data['_encrypted_at'] = time();
        $data['_token'] = uniqid(microtime(true), true);
        uksort($data, function () {
            return rand(0, 1);
        });
        $string = json_encode($data);
        $output = openssl_encrypt($string, $encryptMethod, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }
}

if (!function_exists('sso_decrypt')) {
    function sso_decrypt(string $data, $secretKey, $encryptMethod = 'AES-256-CBC')
    {
        // hash
        $key = hash('sha256', $secretKey);
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $ivlen = openssl_cipher_iv_length($encryptMethod);
        $iv = substr($secretKey, 0, $ivlen);
        $output = openssl_decrypt(base64_decode($data), $encryptMethod, $key, 0, $iv);
        $output = json_decode($output, true);
        return $output;
    }
}

