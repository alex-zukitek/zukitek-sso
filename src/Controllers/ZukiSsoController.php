<?php

namespace Zukitek\Sso\Controllers;

use  App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ZukiSsoController extends Controller
{
    protected $_sso;

    public function __construct()
    {
        $this->_sso = config('sso');
    }

    public function login(Request $request)
    {
        $app = isset($this->_sso['web_application_code']) ? $this->_sso['web_application_code'] : '';
        $this->removeToken($request);
        $path = "{$this->_sso['sso_login_url']}?app={$app}&client={$this->_sso['client_id']}&continue=" . urldecode($this->_sso['redirect_login_success']);
        return redirect(sso_url($path));
    }

    public function logout(Request $request)
    {
        $this->removeToken($request);
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
                if ($key === 'access_token') {
                    $key = $value;
                    $value = $token;
                }
                setcookie(
                    $key,
                    $value,
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
                $md5 = md5($token);
                $keyCache = date('ymd') . "sso_{$md5}";
                \Illuminate\Support\Facades\Cache::forget($keyCache);
            }
            rm_web_token();
            return response()->json(['status' => true], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
