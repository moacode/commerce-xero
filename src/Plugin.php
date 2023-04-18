<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2/3 plugin
 *
 * @author Myles Beardsmore <mediabeastnz@gmail.com>
 * @author Josh Smith <by@joshthe.dev>
 *
 * @copyright 2019 Myles Derham
 * @link      https://www.mylesderham.dev/
 */

namespace thejoshsmith\commerce\xero;

use Craft;
use craft\web\View;
use yii\base\Event;
use yii\base\Exception;
use craft\web\UrlManager;
use craft\helpers\UrlHelper;

use craft\events\TemplateEvent;
use craft\commerce\elements\Order;
use craft\base\Plugin as CraftPlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\twig\variables\CraftVariable;
use thejoshsmith\commerce\xero\traits\Routes;
use thejoshsmith\commerce\xero\traits\Services;
use thejoshsmith\commerce\xero\events\OAuthEvent;

use thejoshsmith\commerce\xero\jobs\SendToXeroJob;
use thejoshsmith\commerce\xero\controllers\AuthController;
use thejoshsmith\commerce\xero\web\assets\SendToXeroAsset;
use thejoshsmith\commerce\xero\web\twig\CraftVariableBehavior;
use thejoshsmith\commerce\xero\models\Settings as SettingsModel;

/**
 * Class Xero
 *
 * @package Xero
 * @author  Myles Derham <mediabeastnz@gmail.com>
 */
class Plugin extends CraftPlugin
{
    // Constants
    // =========================================================================

    /**
     * The plugin handle
     */
    const HANDLE = 'commerce-xero';

    /**
     * The default Xero OAuth callback route
     * used when redirecting back to Craft
     */
    const XERO_OAUTH_CALLBACK_ROUTE = 'xero/auth';

    /**
     * The default set of Xero OAuth grant permissions
     * the plugin will request from Xero
     */
    const XERO_OAUTH_SCOPES = 'openid email profile offline_access accounting.transactions accounting.settings accounting.contacts';

    // Static Properties
    // =========================================================================

    /**
     * @var Plugin
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    public string $schemaVersion = '1.2.0';

    // Traits
    // =========================================================================

    use Routes;
    use Services;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->isInstalled) {
            return;
        }

        // Bootstrap the plugin
        $this->_setPluginComponents();
        $this->_setDependencies();
        $this->_registerCpRoutes();
        $this->_registerVariables();
        $this->_registerPluginEvents();

        // Only register Xero events if we have an active connection
        if ($this->getXeroApi()->isConnected()) {
            $this->_registerXeroEvents();
        }

        Craft::info(
            self::t(
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * @param  $message
     * @param  array $params
     * @param  null  $language
     * @return string
     * @see    Craft::t()
     */
    public static function t($message, $params = [], $language = null)
    {
        return Craft::t(self::HANDLE, $message, $params, $language);
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): array
    {
        $ret = parent::getCpNavItem();

        $ret['label'] = self::t('Xero');
        $ret['url'] = 'xero';

        if (Craft::$app->getUser()->checkPermission('accessPlugin-xero')) {
            $ret['subnav']['organisation'] = [
                'label' => self::t('Organisation'),
                'url' => 'xero/organisation'
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $ret['subnav']['settings'] = [
            'label' => self::t('Settings'),
            'url' => 'xero/settings'
            ];
        }

        return $ret;
    }

    public function withDecimals($places = 4, $number)
    {
        return number_format((float)$number, $places, '.', '');
    }

    public function getSettingsResponse(): mixed
    {
        Craft::$app->controller->redirect(UrlHelper::cpUrl('xero/settings'));
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the Settings Model for this plugin
     *
     * @return craft\base\Model|null
     */
    protected function createSettingsModel(): ?craft\base\Model
    {
        return new SettingsModel();
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers plugin events
     *
     * @return void
     */
    private function _registerPluginEvents()
    {
        // Disconnect other current connections each time the user connects other tenants
        Event::on(
            AuthController::class, AuthController::EVENT_AFTER_SAVE_OAUTH, function (OAuthEvent $event) {
                try {
                    $this->getXeroConnections()->handleAfterSaveOAuthEvent($event);
                } catch (Exception $e) {
                    Craft::error($e->getMessage(), __METHOD__);
                }
            }
        );
    }

    /**
     * Registers Xero events
     *
     * @return void
     */
    private function _registerXeroEvents()
    {
        // Registers a CP URL used to send an order to Xero
        Event::on(
            UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
                $event->rules['sendordertoxero'] = Plugin::HANDLE . '/base/send-order-to-xero';
            }
        );

        // Loads additional JS into the commerce order edit screen
        Event::on(
            View::class, View::EVENT_BEFORE_RENDER_TEMPLATE, function (TemplateEvent $event) {

                $view = Craft::$app->getView();

                // only run for CP requests
                if ($view->getTemplateMode() !== $view::TEMPLATE_MODE_CP ) {
                    return false;
                }

                // Only run on the entries edit template
                switch ($event->template) {
                case 'commerce/orders/_edit':

                    if ($event->variables['order']->isCompleted) {

                        if ($this->api->getInvoiceFromOrder($event->variables['order'])) {
                            $js = trim('var sentToXero = true');
                        } else {
                            $js = trim('var sentToXero = false');
                        }

                        if ($js) {
                            $view->registerJs($js, View::POS_END);
                        }
                        $view->registerAssetBundle(SendToXeroAsset::class);

                    }

                    break;
                }
            }
        );

        // Send completed and paid orders off to Xero (30 second delay)
        Event::on(
            Order::class, Order::EVENT_AFTER_ORDER_PAID, function (Event $e) {
                Craft::$app->queue->delay(30)->push(
                    new SendToXeroJob(
                        [
                        'orderID' => $e->sender->id
                        ]
                    )
                );
            }
        );
    }

    /**
     * Register this plugin's template variable.
     */
    private function _registerVariables()
    {
        Event::on(
            CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
                $variable = $event->sender;
                $variable->attachBehavior(
                    self::HANDLE, CraftVariableBehavior::class
                );
            }
        );
    }
}
