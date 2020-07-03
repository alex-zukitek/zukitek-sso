<?php

namespace Zukitek\Sso\Controllers\Secure;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ZukiController extends Controller
{
    protected $_sso;

    public function __construct()
    {
        $this->_sso = config('sso');
    }

    public function syncUser(Request $request)
    {
        $data = $request->get('data');
        if (!$data) {
            return abort(404);
        }
        try {
            $input = sso_decrypt($data, $this->_sso['client_key']);
            $localUser = $this->_sso['model']['user']::where('email', $input['email'])->first();
            if ($localUser) {
                $callback = isset($this->_sso['callbacks']['sync_user_local']) ? $this->_sso['callbacks']['sync_user_local'] : [];
                $localUser->syncDataLocal($input, $callback);
                \Log::debug("{$localUser->email} Sync success");
            }
        } catch (\Exception $e) {
            \Log::debug("{$localUser->email} Sync error {$e->getMessage()}");
        }
    }
}
