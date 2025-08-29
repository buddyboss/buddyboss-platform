<?php

declare(strict_types=1);

namespace GroundLevel\Mothership;

use GroundLevel\Container\Container;
use GroundLevel\Mothership\Api\Request;
use GroundLevel\Container\Service as BaseService;
use GroundLevel\Mothership\Manager\AddonsManager;
use GroundLevel\Mothership\Manager\LicenseManager;
use GroundLevel\Mothership\AbstractPluginConnection;

/**
 * This class is responsible for setting the variables/methods used by the plugin to interact with the mothership.
 * See AbstractPluginConnection for what methods and variables are needed to be set.
 */
class Service extends BaseService
{
    /**
     * The Service ID.
     */
    public const ID = 'GRDLVL.MOTHERSHIP';

    /**
     * The parameter key for the Mothership prefix.
     */
    public const PREFIX = 'GRDLVL.MOTHERSHIP.PREFIX';

    /**
     * The Service ID for the plugin connection.
     */
    public const CONNECTION_PLUGIN_SERVICE_ID = 'GRDLVL.MOTHERSHIP.CONNECTION_PLUGIN';

    /**
     * The base URL for the API.
     *
     * @var string
     */
    protected static string $apiBaseUrl = 'https://licenses.caseproof.com/api/v1/';

    /**
     * The proxy license key used in the Email/Token strategy.
     * This will be used as the user's temporary license key and supplied in the X-Proxy-License-Key header.
     *
     * @var string
     */
    protected static string $proxyLicenseKey = '';

    /**
     * The basename for the license key used in the License key authentication strategy.
     */
    public const LICENSE_KEY_BASENAME = 'license_key';

    /**
     * The basename for the domain used in the License key authentication strategy.
     */
    public const DOMAIN_BASENAME = 'domain';

    /**
     * The basename for the email used in the Email/Token authentication strategy.
     */
    public const EMAIL_BASENAME = 'email';

    /**
     * The basename for the API token used in the Token authentication strategy.
     */
    public const API_TOKEN_BASENAME = 'api_token';

    /**
     * The plugin object of the plugin using this component.
     *
     * @var AbstractPluginConnection
     */
    private AbstractPluginConnection $plugin;

    /**
     * Service constructor.
     *
     * @param Container                $container The container.
     * @param AbstractPluginConnection $plugin    The plugin object.
     */
    public function __construct(Container $container, AbstractPluginConnection $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Loads the dependencies for the Mothership Component.
     *
     * @param  Container $container The container.
     * @return void
     */
    public function load(Container $container): void
    {
        $container->addService(
            self::CONNECTION_PLUGIN_SERVICE_ID,
            function () {
                return $this->plugin;
            }
        );

        // Set the static containers for the other classes needed by the Mothership Component.
        Credentials::setContainer($container);
        AddonsManager::setContainer($container);
        LicenseManager::setContainer($container);
        Request::setContainer($container);

        // Schedule the license manager events.
        LicenseManager::scheduleEvents($this->plugin->pluginId);
    }

    /**
     * Get the API base URL.
     * User can also set the API base URL by defining the constant in the plugin.
     *
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        if (defined(strtoupper($this->plugin->pluginId) . '_MOTHERSHIP_API_BASE_URL')) {
            return constant(strtoupper($this->plugin->pluginId) . '_MOTHERSHIP_API_BASE_URL');
        }

        return self::$apiBaseUrl;
    }

    /**
     * Gets the proxy license key.
     *
     * @return string
     */
    public static function getProxyLicenseKey(): string
    {
        return self::$proxyLicenseKey;
    }

    /**
     * Set the proxy license key.
     * This will be used as the user's temporary license key in the Email/Token authentication strategy.
     * It will be supplied in the X-Proxy-License-Key header.
     *
     * @param  string $proxyLicenseKey The proxy license key.
     * @return void
     */
    public static function setProxyLicenseKey(string $proxyLicenseKey): void
    {
        self::$proxyLicenseKey = $proxyLicenseKey;
    }
}
