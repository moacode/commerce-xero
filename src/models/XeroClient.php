<?php

namespace thejoshsmith\commerce\xero\models;

use XeroPHP\Application;
use craft\base\Component;
use Exception;
use thejoshsmith\commerce\xero\factories\XeroClient as FactoriesXeroClient;
use thejoshsmith\commerce\xero\models\OrganisationSettings;
use thejoshsmith\commerce\xero\records\Connection;
use thejoshsmith\commerce\xero\records\Credential;
use thejoshsmith\commerce\xero\records\ResourceOwner;
use thejoshsmith\commerce\xero\records\Tenant;

/**
 * Data model that wraps plugin records and the Xero Application
 *
 * @author Josh Smith <by@joshthe.dev>
 * @since  1.0.0
 */
class XeroClient extends Component
{
    private $_application;
    private $_connection;
    private $_credential;
    private $_resourceOwner;
    private $_tenant;
    private $_orgSettings;

    /**
     * Constructor
     *
     * @param Application          $application   Configured Xero Application
     * @param Connection           $connection    Connection Record
     * @param Credential           $credential    Credential Record
     * @param ResourceOwner        $resourceOwner Resource Owner Record
     * @param Tenant               $tenant        Tenant Record
     * @param OrganisationSettings $orgSettings   Organisation Settings Model
     * @param array                $config        An array of config settings
     */
    public function __construct(
        Application $application,
        Connection $connection,
        Credential $credential,
        ResourceOwner $resourceOwner = null,
        Tenant $tenant = null,
        OrganisationSettings $orgSettings = null,
        array $config = []
    ) {
        $this->_application = $application;
        $this->_connection = $connection;
        $this->_credential = $credential ?? $connection->getCredential()->one();
        $this->_resourceOwner = $resourceOwner ?? $connection->getResourceOwner()->one();
        $this->_tenant = $tenant ?? $connection->getTenant()->one();
        $this->_orgSettings = $orgSettings ?? OrganisationSettings::fromConnection($connection);

        parent::__construct($config);
    }

    public function getApplication(): Application
    {
        return $this->_application;
    }

    public function setApplication(Application $application): void
    {
        $this->_application = $application;
    }

    public function getConnection(): Connection
    {
        return $this->_connection;
    }

    public function getCredential(): Credential
    {
        return $this->_credential;
    }

    public function getResourceOwner(): ResourceOwner
    {
        return $this->_resourceOwner;
    }

    public function getTenant(): Tenant
    {
        return $this->_tenant;
    }

    public function getOrgSettings(): OrganisationSettings
    {
        return $this->_orgSettings;
    }

    public function getCacheKey(string $key): string
    {
        return "$key-{$this->getTenant()->tenantId}";
    }

    public function refreshAccessToken()
    {
        $tenant = $this->getTenant();
        $credential = $this->getCredential();

        $credential->refreshAccessToken();
        $this->setApplication(
            FactoriesXeroClient::buildApplication($credential->accessToken, $tenant->tenantId)
        );
    }

    public function hasAccessTokenExpired(): bool
    {
        return $this->getCredential()->isExpired();
    }
}
