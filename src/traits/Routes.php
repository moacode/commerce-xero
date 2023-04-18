<?php
/**
 * Routes Trait
 *
 * Registers custom control panel routes
 *
 * PHP version 7.4
 *
 * @category  Traits
 * @package   CraftCommerceXero
 * @author    Josh Smith <by@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

namespace thejoshsmith\commerce\xero\traits;

use thejoshsmith\commerce\xero\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * Routes Trait
 *
 * @category Traits
 * @package  CraftCommerceXero
 * @author   Josh Smith <by@joshthe.dev>
 * @license  Proprietary https://github.com/thejoshsmith/commerce-xero/blob/master/LICENSE.md
 * @link     https://joshthe.dev
 * @since    1.0.0
 */
trait Routes
{
    // Private Methods
    // =========================================================================

    /**
     * Registers custom CP routes
     *
     * @return void
     */
    private function _registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
                $event->rules['xero'] = ['template' => Plugin::HANDLE . '/index'];
                $event->rules['xero/organisation'] = Plugin::HANDLE . '/organisation/index';
                $event->rules['xero/connections/update'] = Plugin::HANDLE . '/connections/update';
                $event->rules['xero/connections/disconnect'] = Plugin::HANDLE . '/connections/disconnect';
                $event->rules['xero/settings'] = Plugin::HANDLE . '/settings/edit';
                $event->rules[Plugin::XERO_OAUTH_CALLBACK_ROUTE] = Plugin::HANDLE . '/auth/index';
            }
        );
    }
}
