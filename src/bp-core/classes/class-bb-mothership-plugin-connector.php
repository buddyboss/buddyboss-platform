<?php

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;

/**
 * Provides an interface for connecting the plugin to Mothership packages data
 */
class BB_Mothership_Plugin_Connector extends AbstractPluginConnection
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->pluginId     = 'bb-platform-free';
		$this->pluginPrefix = 'bb-platform-free';
	}

	/**
	 * Gets the license activation status option.
	 *
	 * @return boolean The license activation status.
	 */
	public function getLicenseActivationStatus(): bool
	{
		return true;
	}

	/**
	 * Updates the license activation status option.
	 *
	 * @param boolean $status The status to update.
	 */
	public function updateLicenseActivationStatus(bool $status): void
	{
		update_option('bb_activated', $status);
	}

	/**
	 * Gets the license key option.
	 *
	 * @return string The license key.
	 */
	public function getLicenseKey(): string
	{
		return '';
	}

	/**
	 * Updates the license key option.
	 *
	 * @param string $licenseKey The license key to update.
	 */
	public function updateLicenseKey(string $licenseKey): void
	{
		// Nothing to do here.
	}

	/**
	 * Gets the domain option.
	 *
	 * @return string The domain.
	 */
	public function getDomain(): string
	{
		return preg_replace('#^https?://(www\.)?([^\?\/]*)#', '$2', get_option('home'));
	}
}
