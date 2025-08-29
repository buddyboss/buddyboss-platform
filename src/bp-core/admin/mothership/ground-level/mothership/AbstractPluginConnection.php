<?php

declare(strict_types=1);

namespace GroundLevel\Mothership;

use GroundLevel\Container\Service as BaseService;

/**
 * This class is used to serve as a contract to set how the plugin gets/sets data (license key, domain, email, api token) used to connect to the mothership.
 *
 * Two authentication methods are supported:
 * 1. License Key and Domain.
 * 2. Email and API Token.
 *
 * The License Key and Domain method is the default and is used to authenticate the plugin.
 */
abstract class AbstractPluginConnection extends BaseService
{
    /**
     * The ID of the plugin using this component.
     *
     * @var string
     */
    protected string $pluginId;

    /**
     * Used to set the constants for the plugin's license key, domain, email, and API token for local development.
     *
     * @var string
     */
    protected string $pluginPrefix = '';

    /**
     * The name of the constant to use for the plugin's license key.
     *
     * @var string
     */
    protected string $nameForConstantLicenseKey = '';

    /**
     * The name of the constant to use for the plugin's activation domain.
     *
     * @var string
     */
    protected string $nameForConstantDomain = '';

    /**
     * The name of the constant to use for the plugin's email.
     *
     * @var string
     */
    protected string $nameForConstantEmail = '';

    /**
     * The name of the constant to use for the plugin's API token.
     *
     * @var string
     */
    protected string $nameForConstantApiToken = '';

    /**
     * Magic method to get the property of the class.
     *
     * @param  string $name The name of the property.
     * @return mixed|null The value of the property or null if the property does not exist.
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * Gets the license activation status.
     *
     * @return boolean
     */
    abstract public function getLicenseActivationStatus(): bool;

    /**
     * Updates the license activation status.
     *
     * @param  boolean $status The new status of the license activation.
     * @return void
     */
    abstract public function updateLicenseActivationStatus(bool $status): void;

    /**
     * Gets the License Key.
     *
     * @return string The License Key.
     */
    abstract public function getLicenseKey(): string;

    /**
     * Updates the License Key.
     *
     * @param  string $licenseKey The License Key.
     * @return void
     */
    abstract public function updateLicenseKey(string $licenseKey): void;

    /**
     * Gets the Domain.
     *
     * @return string The Domain.
     */
    public function getDomain(): string
    {
        return parse_url(get_home_url(), PHP_URL_HOST);
    }

    /**
     * Gets the Email.
     *
     * @return string The Email.
     */
    public function getEmail(): string
    {
        return '';
    }

    /**
     * Gets the API Token.
     *
     * @return string The API Token.
     */
    public function getApiToken(): string
    {
        return '';
    }
}
