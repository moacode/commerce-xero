<?php

namespace thejoshsmith\commerce\xero\models;

use craft\base\Model;

/**
 * Settings Model
 * Defines the plugin settings stored in project config
 */
class Settings extends Model
{
    public string $xeroClientId = '';
    public string $xeroClientSecret = '';

    /**
     * Defines validation rules for the above settings
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            [['xeroClientId', 'xeroClientSecret'], 'required'],
            [['xeroClientId', 'xeroClientSecret'], 'string'],
        ];
    }
}
