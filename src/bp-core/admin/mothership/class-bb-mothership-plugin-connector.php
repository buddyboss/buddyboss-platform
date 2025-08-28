<?php

use GroundLevel\Mothership\AbstractPluginConnection;

/**
 * Provides an interface for connecting the BuddyBoss Platform plugin to Mothership packages data
 */
class BB_Mothership_Plugin_Connector extends AbstractPluginConnection {

	/**
	 * Plugin properties
	 */
	protected string $pluginId      = '';
	protected string $pluginName    = '';
	protected string $pluginVersion = '';
	protected string $pluginFile    = '';
	protected string $pluginDir     = '';
	protected string $pluginUrl     = '';

	/**
	 * Constructor for the BB_Mothership_Plugin_Connector class.
	 */
	public function __construct() {
		// Set actual values.
		$this->pluginId      = 'buddyboss-platform';
		$this->pluginName    = 'BuddyBoss Platform';
		$this->pluginVersion = buddypress()->version;
		$this->pluginFile    = plugin_basename( buddypress()->file );
		$this->pluginDir     = plugin_dir_path( buddypress()->file );
		$this->pluginUrl     = plugin_dir_url( buddypress()->file );

		// Define the mothership API base URL.
		if ( ! defined( 'BB_PLATFORM_MOTHERSHIP_API_BASE_URL' ) ) {
			define( 'BB_PLATFORM_MOTHERSHIP_API_BASE_URL', 'https://mothership.caseproof.com/api/v1/' );
		}
	}

	/**
	 * Get the plugin's unique identifier.
	 *
	 * @return string
	 */
	public function getPluginId(): string {
		return $this->pluginId;
	}

	/**
	 * Get the plugin's name.
	 *
	 * @return string
	 */
	public function getPluginName(): string {
		return $this->pluginName;
	}

	/**
	 * Get the plugin's version.
	 *
	 * @return string
	 */
	public function getPluginVersion(): string {
		return $this->pluginVersion;
	}

	/**
	 * Get the plugin's main file path.
	 *
	 * @return string
	 */
	public function getPluginFile(): string {
		return $this->pluginFile;
	}

	/**
	 * Get the plugin's directory path.
	 *
	 * @return string
	 */
	public function getPluginDir(): string {
		return $this->pluginDir;
	}

	/**
	 * Get the plugin's URL.
	 *
	 * @return string
	 */
	public function getPluginUrl(): string {
		return $this->pluginUrl;
	}

	/**
	 * Get the mothership API base URL.
	 *
	 * @return string
	 */
	public function getMothershipApiBaseUrl(): string {
		return BB_PLATFORM_MOTHERSHIP_API_BASE_URL;
	}

	/**
	 * Get the license activation status.
	 *
	 * @return bool
	 */
	public function getLicenseActivationStatus(): bool {
		return (bool) get_option( 'bb_platform_license_activated', false );
	}

	/**
	 * Update the license activation status.
	 *
	 * @param bool $status The activation status.
	 * @return void
	 */
	public function updateLicenseActivationStatus( bool $status ): void {
		update_option( 'bb_platform_license_activated', $status );
	}

	/**
	 * Get the license key.
	 *
	 * @return string
	 */
	public function getLicenseKey(): string {
		return get_option( 'bb_platform_license_key', '' );
	}

	/**
	 * Update the license key.
	 *
	 * @param string $licenseKey The license key.
	 * @return void
	 */
	public function updateLicenseKey( string $licenseKey ): void {
		update_option( 'bb_platform_license_key', $licenseKey );
	}
}
