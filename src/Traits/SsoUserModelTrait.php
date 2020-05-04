<?php

namespace Zukitek\Sso\Traits;

trait SsoUserModelTrait
{
    protected $ssoFillable = [
        'id', 'email', 'first_name', 'last_name', 'full_name',
        'gender', 'birthday', 'mobile_code', 'mobile', 'address',
        'avatar', 'avatar_source', 'country', 'postal_code', 'default_payment',
        'services', 'status', 'updated_at',
    ];

    protected $ssoHidden = ['id', 'email', 'status', 'created_at', 'updated_at'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        self::addDefaultAttr();
    }

    public function __isset($key)
    {
        return $this->isSsoAttribute($key) || parent::__isset($key);
    }

    public static function bootSsoUserModelTrait(): void
    {

    }

    protected function addDefaultAttr()
    {
        if (!in_array('sso_user', $this->fillable)) {
            array_push($this->fillable, 'sso_user');
        }

        if (!isset($this->casts['sso_user'])) {
            $this->casts['sso_user'] = 'array';
        }
    }

    public function syncDataLocal(array $ssoUser)
    {
        if (
            empty($this->sso_user) ||
            (!empty($this->sso_user) && is_array($this->sso_user) && $this->sso_user['updated_at'] !== $ssoUser['updated_at'])
        ) {
            $arr = [];
            foreach ($this->ssoFillable as $key) {
                $arr[$key] = !empty($ssoUser[$key]) ? $ssoUser[$key] : null;
            }
            $this->sso_user = $arr;
            $this->save();
        }
    }

    public function getAttribute($key)
    {
        if (!in_array($key, $this->ssoHidden) && $this->isSsoAttribute($key)) {
            $ssoData = $this->sso_user;
            return is_array($ssoData) && isset($ssoData[$key]) ? $ssoData[$key] : null;
        }
        return parent::getAttribute($key);
    }

    public function getSsoIdAttribute()
    {
        $ssoData = $this->sso_user;
        return isset($ssoData['id']) ? str_pad($ssoData['id'], 12, '0', STR_PAD_LEFT) : null;
    }

    protected function isSsoAttribute(string $key): bool
    {
        return in_array($key, $this->ssoFillable);
    }
}
