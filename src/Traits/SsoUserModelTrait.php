<?php

namespace Zukitek\Sso\Traits;

trait SsoUserModelTrait
{
    /**
     * @var array $ssoFillable
     */
    protected $ssoFillable = [
        'id', 'email', 'first_name', 'last_name', 'full_name',
        'gender', 'birthday', 'mobile_code', 'mobile', 'address',
        'avatar', 'avatar_source', 'country', 'postal_code', 'default_payment',
        'services', 'status', 'updated_at',
    ];

    /**
     * @var array $syncColumnsLocal
     */
    // protected $syncColumnsLocal = []; // first_name, last_name, full_name, [ "local" => "local_country", "target" => "sso_country"]

    /**
     * @var array $ssoAttributesHidden
     */
    // protected $ssoAttributesHidden = []; // 'status', the data will not get in sso_user

    /**
     * @var array $ssoHidden
     */
    protected $ssoHidden = ['id', 'email', 'created_at', 'updated_at'];

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
        /*
        if (!in_array('sso_user', $this->fillable)) {
            array_push($this->fillable, 'sso_user');
        }

        if (!in_array('sso_id', $this->fillable)) {
            array_push($this->fillable, 'sso_id');
        }
        */
        if (!isset($this->casts['sso_user'])) {
            $this->casts['sso_user'] = 'array';
        }
    }

    public function syncDataLocal(array $ssoUser, array $callback = [])
    {
        $saving = false;
        if (
            empty($this->sso_user) ||
            (!empty($this->sso_user) && is_array($this->sso_user) && $this->sso_user['updated_at'] !== $ssoUser['updated_at'])
        ) {
            $syncColumnsLocal = isset($this->syncColumnsLocal) ? $this->syncColumnsLocal : [];
            $saving = true;
            $ssoUserLocal = [];
            foreach ($this->ssoFillable as $key) {
                $ssoUserLocal[$key] = isset($ssoUser[$key]) ? $ssoUser[$key] : null;
            }
            $this->sso_user = $ssoUserLocal;
            foreach ($syncColumnsLocal as $column) {
                if (is_string($column)) {
                    $this->$column = $ssoUserLocal[$column];
                } else {
                    $this->$column['local'] = $ssoUserLocal[$column['target']];
                }
            }
        }
        if (!isset($this->attributes['sso_id'])) {
            $saving = true;
            $this->sso_id = $ssoUser['id'];
        }
        if ($saving) {
            $this->timestamps = false;
            $this->save();
            if (!empty($callback) && is_array($callback)) {
                $callbackClass = $callback[0];
                $callbackMethod = $callback[1];
                $callbackClass::$callbackMethod($this, $ssoUser); // local $user and SSO User
            }
        }
    }

    public function getAttribute($key)
    {
        $ssoAttributesHidden = isset($this->ssoAttributesHidden) ? $this->ssoAttributesHidden : [];
        if (!in_array($key, $this->ssoHidden) && !in_array($key, $ssoAttributesHidden) && $this->isSsoAttribute($key)) {
            $ssoData = $this->sso_user;
            return is_array($ssoData) && isset($ssoData[$key]) ? $ssoData[$key] : null;
        }
        return parent::getAttribute($key);
    }

    public function getSsoIdAttribute($value)
    {
        if ($value === null) {
            $ssoData = $this->sso_user;
            return isset($ssoData['id']) ? $ssoData['id'] : 0;
        }
        return $value;
    }

    protected function isSsoAttribute(string $key): bool
    {
        return in_array($key, $this->ssoFillable);
    }
}
