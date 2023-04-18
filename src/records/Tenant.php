<?php

namespace thejoshsmith\commerce\xero\records;

use craft\db\ActiveRecord;

/**
 * Active Record class for saving Xero Tenants
 *
 * @author Josh Smith <by@joshthe.dev>
 */
class Tenant extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%xero_tenants}}';
    }

    public function rules(): array
    {
        return [
            [['id', 'tenantId', 'tenantType', 'tenantName'], 'safe'],
        ];
    }
}
