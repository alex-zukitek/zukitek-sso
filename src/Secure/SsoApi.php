<?php

namespace Zukitek\Sso\Secure;

use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use Exception;

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
        throw new Exception('Method not found');
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
        throw new Exception('Method not found');
    }

    public function _request($apiPath, $method = 'GET', array $params = [])
    {
        $path = ltrim($apiPath, '/');
        $method = strtoupper($method);
        $params['author'] = $this->author;
        $data = $this->_encrypt($params);
        $requestBody = $this->body;
        switch ($method) {
            case 'GET':
                $requestBody['query'] = [
                    'data' => $data, // request data
                ];
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
            $requestBody['json'] = [
                    'data' => $data, // request data
                ];
                break;
            default:
                throw new Exception('HTTP method invalid');
        }
        $client = new Client();
        try {
            $response = $client->request($method, "{$this->ssoApiUrl}/{$path}", $requestBody);
            $body = json_decode($response->getBody(), true);
            return $body;
        } catch (\GuzzleHttpException\BadResponseException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        } catch (Exception $e) {
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
        return sso_encrypt($data, $this->clientKey, $this->encryptMethod);
    }

    public function _decrypt(string $data)
    {
        return sso_decrypt($data, $this->clientKey, $this->encryptMethod);
    }
}
