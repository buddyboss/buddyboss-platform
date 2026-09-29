<?php

declare (strict_types=1);
namespace BuddyBossPlatform\GroundLevel\Mothership;

/**
 * Per-plugin configuration for Mothership. Plugins extend this class and override
 * the defaults to customize how the plugin connects to Mothership.
 *
 * The `resolve*()` methods are called by {@see Credentials} and should not be called
 * directly by consumer code - use the matching `Credentials` getter instead.
 *
 * Two auth methods are supported, chosen at request time:
 * 1. License Key + Domain - used when the license key is set.
 * 2. Email + API Token - used when the license key is not set.
 *
 * @property string $pluginId     The ID of the plugin using this component.
 * @property string $pluginPrefix The prefix of the plugin using this component.
 * @property string $productId    The ID of the product using this component.
 * @property string $pluginFile   The plugin basename relative to the plugins directory.
 */
abstract class AbstractPluginConnection
{
    public const AUTOMATIC_UPDATE_ALL = 'all';
    public const AUTOMATIC_UPDATE_MINOR = 'minor';
    public const AUTOMATIC_UPDATE_NONE = 'none';
    /**
     * The ID of the plugin using this component.
     *
     * @var string
     */
    protected string $pluginId;
    /**
     * Used to set the constants for the plugin's license key, domain, email, and API token for local development.
     *
     * A trailing underscore is automatically added to the prefix if it is not already present.
     *
     * @var string
     */
    protected string $pluginPrefix;
    /**
     * Used to connect to the Mothership API.
     *
     * @var string
     */
    protected string $productId = '';
    /**
     * The plugin basename relative to the plugins directory.
     *
     * For example: 'ground-level/ground-level.php'. This is the same format returned
     * by WordPress's `plugin_basename()` function.
     *
     * @var string
     */
    protected string $pluginFile = '';
    /**
     * Magic method to get the property of the class.
     *
     * @param  string $name The name of the property.
     * @return mixed|null The value of the property or null if the property does not exist.
     */
    public function __get(string $name)
    {
        if (\property_exists($this, $name)) {
            return $this->{$name};
        }
        return null;
    }
    /**
     * Gets the license activation status.
     *
     * @return boolean
     */
    public function getLicenseActivationStatus() : bool
    {
        return (bool) get_option($this->pluginId . '_license_active', \false);
    }
    /**
     * Sets the license activation status.
     *
     * @param  boolean $status The new status of the license activation.
     * @return boolean Whether the license activation status was updated successfully.
     */
    public function setLicenseActivationStatus(bool $status) : bool
    {
        return update_option($this->pluginId . '_license_active', $status);
    }
    /**
     * Whether to include prerelease versions (alpha, beta, custom or release_candidate) when checking for updates.
     *
     * @return boolean
     */
    public function allowPrereleaseVersions() : bool
    {
        /**
         * Filters whether prerelease versions are included when checking for updates.
         *
         * @param bool $allow Whether to allow prerelease versions. Default false.
         */
        return (bool) apply_filters("{$this->pluginId}_allow_prerelease_versions", \false);
    }
    /**
     * Controls which updates are applied automatically during WordPress background updates.
     *
     * Possible values:
     * - {@see self::AUTOMATIC_UPDATE_ALL}   - auto-update for all versions including major bumps.
     * - {@see self::AUTOMATIC_UPDATE_MINOR} - auto-update for minor and patch only, blocks major bumps.
     * - {@see self::AUTOMATIC_UPDATE_NONE}  - never auto-update.
     *
     * @return string
     */
    public function automaticUpdates() : string
    {
        /**
         * Filters the automatic update level for the plugin.
         *
         * @param string $level The automatic update level. Default self::AUTOMATIC_UPDATE_MINOR.
         */
        return (string) apply_filters("{$this->pluginId}_automatic_updates", self::AUTOMATIC_UPDATE_MINOR);
    }
    /**
     * Gets the account URL.
     *
     * @return string The account URL.
     */
    public function getAccountUrl() : string
    {
        return '';
    }
    /**
     * Resolves the license key from storage.
     *
     * IMPORTANT: Called by {@see Credentials}. Consumer code should use
     * {@see Credentials::getLicenseKey()} to read the license key.
     *
     * @internal
     *
     * @return string The license key.
     */
    public function resolveLicenseKey() : string
    {
        return (string) get_option($this->pluginId . '_license_key', '');
    }
    /**
     * Stores the license key.
     *
     * IMPORTANT: Called by {@see Credentials}. Consumer code should use
     * {@see Credentials::setLicenseKey()} to write the license key.
     *
     * @internal
     *
     * @param  string $licenseKey The license key.
     * @return boolean Whether the license key was stored successfully.
     */
    public function storeLicenseKey(string $licenseKey) : bool
    {
        return update_option($this->pluginId . '_license_key', $licenseKey);
    }
    /**
     * Resolves the activation domain from storage.
     *
     * IMPORTANT: Called by {@see Credentials}. Consumer code should use
     * {@see Credentials::getDomain()} to read the activation domain.
     *
     * @internal
     *
     * @return string The activation domain.
     */
    public function resolveDomain() : string
    {
        return \parse_url(get_home_url(), \PHP_URL_HOST);
    }
    /**
     * Resolves the email from storage.
     *
     * IMPORTANT: Called by {@see Credentials}. Consumer code should use
     * {@see Credentials::getEmail()} to read the email.
     *
     * @internal
     *
     * @return string The email.
     */
    public function resolveEmail() : string
    {
        return '';
    }
    /**
     * Resolves the API token from storage.
     *
     * IMPORTANT: Called by {@see Credentials}. Consumer code should use
     * {@see Credentials::getApiToken()} to read the API token.
     *
     * @internal
     *
     * @return string The API token.
     */
    public function resolveApiToken() : string
    {
        return '';
    }
}
