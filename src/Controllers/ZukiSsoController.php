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

    public function login()
    {
        $app = isset($this->_sso['web_application_code']) ? $this->_sso['web_application_code'] : '';
        $this->removeToken();
        $path = "{$this->_sso['sso_login_url']}?app={$app}&client={$this->_sso['client_id']}&continue=" . urldecode($this->_sso['redirect_login_success']);
        return redirect(sso_url($path));
    }

    public function logout()
    {
        $this->removeToken();
        $path = "{$this->_sso['sso_logout_url']}?client={$this->_sso['client_id']}&continue=" . urldecode($this->_sso['redirect_logout_success']);
        return redirect(sso_url($path));
    }

    public function saveToken(Request $request)
    {
        $input = $request->only(['t', 'e']);
        if (!empty($input['t']) && !empty($input['e'])) {
            $token = base64_decode($input['t']);
            $expires = (int)base64_decode($input['e']);
            $cookieInfo = $this->_sso['cookie_info'];
            foreach ($this->_sso['auth_keys'] as $key => $name) {
                if ($key === 'access_token') {
                    setcookie($name, $token, time() + $expires, $cookieInfo['path'], $cookieInfo['domain'], $cookieInfo['secure'], $cookieInfo['http_only']);
                } else {
                    setcookie($key, $name, time() + $expires, $cookieInfo['path'], $cookieInfo['domain'], false, false);
                }
            }
        }
    }

    public function removeToken()
    {
        rm_web_token();
    }
}
