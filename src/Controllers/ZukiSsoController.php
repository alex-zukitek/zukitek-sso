<?php

namespace Zukitek\Sso\Controllers;

use  App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Zukitek\Sso\SsoData;

class ZukiSsoController extends Controller
{
    protected $_sso;

    public function __construct()
    {
        $this->_sso = config('sso');
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $app = isset($this->_sso['web_application_code']) ? $this->_sso['web_application_code'] : '';
        if ($request->has('rm')) {
            rm_web_token();
        }
        $path = "{$this->_sso['sso_login_url']}?app={$app}&client={$this->_sso['client_id']}&continue=" . urldecode($this->_sso['redirect_login_success']);
        return redirect(sso_url($path));
    }

    public function logout(Request $request)
    {
        rm_web_token(['access_token']);
        $app = isset($this->_sso['web_application_code']) ? $this->_sso['web_application_code'] : '';
        $path = "{$this->_sso['sso_logout_url']}?app={$app}&client={$this->_sso['client_id']}&continue=" . urldecode($this->_sso['redirect_logout_success']);
        return redirect(sso_url($path));
    }

    public function saveToken(Request $request)
    {
        $input = $request->only(['t', 'e']);
        if (isset($input['t']) && isset($input['e'])) {
            $token = $input['t'];
            $expires = $input['e'];
            $cookieInfo = $this->_sso['cookie_info'];
            foreach ($this->_sso['auth_keys'] as $key => $value) {
                $httpOnly = $cookieInfo['http_only'];
                if ($key === 'access_token') {
                    $key = $value;
                    $value = $token;
                } else {
                    $httpOnly = false;
                }
                setcookie(
                    $key,
                    $value,
                    [
                        'expires' => $expires,
                        'path' => $cookieInfo['path'],
                        'domain' => $cookieInfo['domain'],
                        'secure' => $cookieInfo['secure'],
                        'httponly' => $httpOnly,
                        'samesite' => $cookieInfo['same_site'],
                    ]
                );
            }
            if ($request->has('continue')) {
                return redirect(urldecode($request->get('continue')));
            }
            return response()->json(['status' => true], 200);
        }
        abort(404);
    }

    public function isCookieSaved(Request $request)
    {
        $time = $request->get('time');
        if (!$time || (int)$time < (time() - 120)) {
            abort(404);
        }
        $check = get_cookie($this->_sso['auth_keys']['access_token']);
        if (!$check) {
            return response()->json(['status' => false], 400);
        }
        return response()->json(['status' => true], 200);
    }

    public function removeToken(Request $request)
    {
        try {
            $token = $request->get('t');
            if ($token && isset($this->_sso['cache_time_life'])) {
                $keyCache = SsoData::getCacheKey($token);
                \Illuminate\Support\Facades\Cache::forget($keyCache);
                rm_web_token();
            }
            return response()->json(['status' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function token(Request $request)
    {
        $accessToken = get_cookie($this->_sso['auth_keys']['access_token']);
        return response()->json([
            'data' => [
                'access_token' => $accessToken
            ]
        ], 200);
    }
}
