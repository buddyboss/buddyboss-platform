<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use GroundLevel\Mothership\AbstractPluginConnection;

/**
 * Plugin Connector class for BuddyBoss Platform.
 *
 * This class follows the GroundLevel AbstractPluginConnection pattern
 * for managing plugin-specific data and API connections.
 */
class BB_Plugin_Connector extends AbstractPluginConnection
{
    /**
     * Constructor for the BB_Plugin_Connector class.
     */
    public function __construct()
    {
        $this->pluginId     = 'bb-lifetime-deal-10-sites';
        $this->pluginPrefix = 'buddyboss';
	}

    /**
     * Gets the license activation status option.
     *
     * @return boolean The license activation status.
     */
    public function getLicenseActivationStatus(): bool
    {
        return (bool) get_option('bb_lifetime_deal_10_sites_license_activation_status') ?? false;
    }

    /**
     * Updates the license activation status option.
     *
     * @param boolean $status The status to update.
     */
    public function updateLicenseActivationStatus(bool $status): void
    {
        update_option('bb_lifetime_deal_10_sites_license_activation_status', $status);
    }

    /**
     * Gets the license key option.
     *
     * @return string The license key.
     */
    public function getLicenseKey(): string
    {
        return (string)get_option('bb_lifetime_deal_10_sites_license_key') ?? '';
    }

    /**
     * Updates the license key option.
     *
     * @param string $licenseKey The license key to update.
     */
    public function updateLicenseKey(string $licenseKey): void
    {
        update_option('bb_lifetime_deal_10_sites_license_key', $licenseKey);
    }

    /**
     * Gets the domain option.
     *
     * @return string The domain.
     */
    public function getDomain(): string
    {
        return wp_parse_url(get_home_url(), PHP_URL_HOST);
    }

    /**
     * Debug method to get current status.
     *
     * @return array Debug information.
     */
    public function getDebugInfo(): array
    {
        return [
            'plugin_id' => $this->pluginId,
            'plugin_prefix' => $this->pluginPrefix,
            'license_key' => $this->getLicenseKey(),
            'license_activated' => $this->getLicenseActivationStatus(),
            'domain' => $this->getDomain(),
            'api_base_url' => defined(strtoupper($this->pluginId . '_MOTHERSHIP_API_BASE_URL')) 
                ? constant(strtoupper($this->pluginId . '_MOTHERSHIP_API_BASE_URL'))
                : 'https://licenses.caseproof.com/api/v1/',
        ];
    }
}
