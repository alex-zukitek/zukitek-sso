<?php

namespace Zukitek\Sso;

use Illuminate\Support\Facades\Request;

/**
 * Connect with sso api
 */
class ServiceRequest
{
    protected $ssoApiUrl;
    protected $body;
    protected $clientId;
    protected static $instance;

    public function __construct()
    {
        $this->ssoApiUrl = config('sso.sso_server_api_url');
        $this->clientId = config('sso.client_id');
        $authKeyToken = config('sso.auth_keys.access_token');
        $this->body = [
            'headers' => [
                'Accept' => 'application/json',
                'X-Client-Request' => $this->clientId,
                'Authorization' => (Request::header('Authorization') ?? ('Bearer ' . get_cookie($authKeyToken)))
            ],
        ];
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

    public function _send($apiPath, $method = 'GET', array $params = [])
    {
        $path = ltrim($apiPath, '/');
        $method = strtoupper($method);
        $body = $this->body;
        switch ($method) {
            case 'GET':
                $body['query'] = $params;
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $body['json'] = $params;
                break;
            default:
                throw new \Exception('HTTP method invalid', 400);
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

    public function _createTransaction(array $params)
    {
        if (!isset($params['client'])) {
            $params['client'] = $this->clientId;
        }
        return $this->_send('/api/transactions', 'POST', $params);
    }
}