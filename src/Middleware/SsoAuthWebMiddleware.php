<?php

namespace Zukitek\Sso\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Zukitek\Sso\SsoData;

class SsoAuthWebMiddleware
{
    /**
     * Web will use cookie to check auth
     *
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector|mixed
     */
    public function handle($request, Closure $next, $applications = null)
    {
        $sso = config('sso');
        $errorMessage = 'Please login to continue';
        $errorCode = 401;
        $httpError = 401;
        try {
            $ssoUser = SsoData::user();
            if ($ssoUser) {
                if (SsoData::localUser()) {
                    $canAccess = false;
                    if ($applications) {
                        $arrApplications = explode('+', $applications);
                        foreach ($ssoUser['applications'] as $application) {
                            if (in_array($application['code'], $arrApplications)) {
                                $canAccess = true;
                                break;
                            }
                        }
                        if (!$canAccess) {
                            throw new \Exception('Limit permissions, please contact admin to continue.');
                        }
                    }
                    $callback = isset($sso['callbacks']['sync_user_local']) ? $sso['callbacks']['sync_user_local'] : [];
                    SsoData::localUser()->syncDataLocal($ssoUser, $callback);
                    return $next($request);
                } else {
                    if ($sso['sync_user_local']) {
                        $callback = $sso['callbacks']['create_user_client'];
                        $callbackClass = $callback[0];
                        $callbackMethod = $callback[1];
                        $newUser = $callbackClass::$callbackMethod($ssoUser);
                        SsoData::setLocalUser($newUser) ;
                        return $next($request);
                    } else {
                        $errorMessage = 'Please register this service to access';
                        $errorCode = 403;
                        $httpError = 403;
                    }
                }
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $errorCode = 400;
            $httpError = 400;
        }
        SsoData::destroy();

        if ($request->expectsJson()) {
            $response = [
                'success' => false,
                'message' => $errorMessage,
                'status_code' => $errorCode,
                'errors' => [],
            ];
            return response()->json($response, $httpError);
        } else {
            if ($errorCode === 423) {
                return redirect()->route('sso.errors', ['code' => 423]);
            } else if ($errorCode === 403 && $sso['url_to_register_account']) {
                return redirect($sso['url_to_register_account']);
            } else if ($errorCode === 401) {
                return redirect()->route('sso.login');
            } else {
                return redirect()->route('sso.errors', ['code' => 400, 'errorMessage' => $errorMessage]);
            }
        }
    }
}
