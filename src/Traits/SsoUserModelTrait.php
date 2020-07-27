<?php

namespace Zukitek\Sso\Traits;

use Zukitek\Sso\SsoData;

trait SsoUserModelTrait
{
    /**
     * @var array $ssoConfig = []
     */
//    protected $ssoConfig = [
//        'syncColumnsLocal' => [], // [ first_name, last_name, full_name, [ "local" => "local_country", "target" => "sso_country"] ]
//        'exceptSsoColumns' => [], // full_name, last_name ....
//    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        self::addDefaultAttr();
    }

    protected function addDefaultAttr()
    {
        if (!isset($this->casts['sso_user'])) {
            $this->casts['sso_user'] = 'array';
        }
    }

    /**
     * Disable to not sync to SSO
     */
    protected function _beforeSyncSsoData()
    {
        if (isset($this->hasSyncedData)) {
            $this->hasSyncedData = true;
        }
    }

    public function syncDataLocal(array $ssoUser, array $callback = [])
    {
        if (method_exists(self::class, 'beforeSyncSsoData')) {
            $this->beforeSyncSsoData();
        } else {
            $this->_beforeSyncSsoData();
        }
        $saving = false;
        if (!isset($this->attributes['sso_id'])) {
            $saving = true;
            $this->sso_id = $ssoUser['id'];
        }
        if (
            empty($this->sso_user) ||
            (!empty($this->sso_user) && is_array($this->sso_user) && $this->sso_user['updated_at'] !== $ssoUser['updated_at'])
        ) {
            if (isset($this->sso_user['updated_at']) && isset($ssoUser['updated_at'])) {
                if (strtotime($this->sso_user['updated_at']) >= strtotime($ssoUser['updated_at'])) {
                    return false;
                }
            }
            $saving = true;
            // Sync data at local
            if (isset($this->ssoConfig['syncColumnsLocal'])) {
                foreach ($this->ssoConfig['syncColumnsLocal'] as $column) {
                    if (is_string($column)) {
                        if (key_exists($column, $ssoUser)) {
                            $this->$column = $ssoUser[$column];
                        }
                    } else {
                        if (key_exists($column['target'], $ssoUser)) {
                            $this->$column['local'] = $ssoUser[$column['target']];
                        }
                    }
                }
            }
            // Remove key
            if (isset($this->ssoConfig['exceptSsoColumns'])) {
                foreach ($this->ssoConfig['exceptSsoColumns'] as $column) {
                    if (key_exists($column, $ssoUser)) {
                        unset($ssoUser[$column]);
                    }
                }
            }
            $this->sso_user = $ssoUser;
        }
        if ($saving) {
            // remove key cache for SSO data
            $cacheLocal = SsoData::getCacheKeyLocal($ssoUser);
            \Illuminate\Support\Facades\Cache::forget($cacheLocal);

            $this->timestamps = false;
            $this->save();
            if (!empty($callback) && is_array($callback)) {
                $callbackClass = $callback[0];
                $callbackMethod = $callback[1];
                $callbackClass::$callbackMethod($this, $ssoUser); // local $user and SSO User
            }
        }
    }

    public function _sso($key)
    {
        return isset($this->sso_user[$key]) ? $this->sso_user[$key] : null;
    }
}
