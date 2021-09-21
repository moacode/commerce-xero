<?php

namespace thejoshsmith\commerce\xero\models;

use craft\base\Model;
use thejoshsmith\commerce\xero\records\Connection;

class OrganisationSettings extends Model
{
    public $createPayments;
    public $updateInventory;
    public $accountSales;
    public $accountReceivable;
    public $accountShipping;
    public $accountRounding;
    public $accountDiscounts;
    public $accountAdditionalFees;
    public $accountLineItemTax;
    public $accountInvoiceStatus;

    /**
     * Returns a new model from the passed connection object
     *
     * @param Connection $connection A connection object
     *
     * @return self
     */
    public static function fromConnection(Connection $connection): self
    {
        $settings = json_decode($connection->settings);

        $organisationSettings = new static();

        $organisationSettings->createPayments = $settings->createPayments ?? null;
        $organisationSettings->updateInventory = $settings->updateInventory ?? null;
        $organisationSettings->accountSales = $settings->accountSales ?? null;
        $organisationSettings->accountReceivable = $settings->accountReceivable ?? null;
        $organisationSettings->accountShipping = $settings->accountShipping ?? null;
        $organisationSettings->accountRounding = $settings->accountRounding ?? null;
        $organisationSettings->accountDiscounts = $settings->accountDiscounts ?? null;
        $organisationSettings->accountAdditionalFees = $settings->accountAdditionalFees ?? null;
        $organisationSettings->accountLineItemTax = $settings->accountLineItemTax ?? 'Exclusive';
        $organisationSettings->accountInvoiceStatus = $settings->accountInvoiceStatus ?? 'AUTHORISED';

        return $organisationSettings;
    }

    public function rules()
    {
        parent::rules();

        return [
            [['accountSales', 'accountReceivable', 'accountShipping', 'accountRounding'], 'required'],
            [['accountSales', 'accountReceivable', 'accountShipping', 'accountRounding', 'accountDiscounts', 'accountAdditionalFees'], 'integer'],
            [['accountLineItemTax', 'accountInvoiceStatus'], 'string'],
            [['createPayments', 'updateInventory'], 'boolean']
        ];
    }
}
