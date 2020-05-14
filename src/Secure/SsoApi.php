<?php

namespace Zukitek\Sso\Secure;

use Illuminate\Support\Facades\Auth;

class SsoApi
{
    private $ssoApiUrl;
    private $clientId;
    private $clientKey;
    private $body;
    private $author;
    private $encryptMethod = 'AES-256-CBC';
    private static $instance;

    public function __construct()
    {
        $this->ssoApiUrl = config('sso.sso_server_api_url');
        $this->clientId = config('sso.client_id');
        $this->clientKey = config('sso.client_key');
        $this->body = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Client-Request' => $this->clientId,
            ],
        ];
        $user = Auth::guard('request')->user();
        $this->author = $user ? $user->email : null;
    }

    public function __call($name, $arguments)
    {
        $name = '_' . $name;
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }
        throw new \Exception('Method not found');
    }

    public static function __callStatic($name, $arguments)
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        $name = '_' . $name;
        if (method_exists(self::$instance, $name)) {
            return self::$instance->$name(...$arguments);
        }
        throw new \Exception('Method not found');
    }

    public function _request($apiPath, $method = 'GET', array $params = [])
    {
        $path = ltrim($apiPath, '/');
        $method = strtoupper($method);
        $params['author'] = $this->author;
        $data = $this->_encrypt($params);
        $body = $this->body;
        switch ($method) {
            case 'GET':
                $body['query'] = [
                    'data' => $data, // request data
                ];
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $body['json'] = [
                    'data' => $data, // request data
                ];
                break;
            default:
                throw new \Exception('HTTP method invalid');
        }
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request($method, "{$this->ssoApiUrl}/{$path}", $body);
            $body = json_decode($response->getBody(), true);
            return $body;
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status_code' => 400,
                'errors' => [],
            ];
        }
    }

    public function _encrypt(array $data)
    {
        // hash
        $key = hash('sha256', $this->clientKey);
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $ivlen = openssl_cipher_iv_length($this->encryptMethod);
        $iv = substr($this->clientKey, 0, $ivlen);
        $data['encrypted_at'] = time();
        $data['_token'] = uniqid(microtime(true), true);
        uksort($data, function () {
            return rand(0, 1);
        });
        $string = json_encode($data);
        $output = openssl_encrypt($string, $this->encryptMethod, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }

    public function _decrypt(string $data)
    {
        // hash
        $key = hash('sha256', $this->clientKey);
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $ivlen = openssl_cipher_iv_length($this->encryptMethod);
        $iv = substr($this->clientKey, 0, $ivlen);
        $output = openssl_decrypt(base64_decode($data), $this->encryptMethod, $key, 0, $iv);
        $output = json_decode($output);
        return $output;
    }
}
