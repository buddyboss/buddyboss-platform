<?php
/**
 * Two Factor plugin compatibility probe.
 *
 * Low-level detection of the Two Factor plugin: where it lives, which version is
 * present, and the minimum version this integration can work with. The public API
 * built on top of these helpers lives in bb-two-factor-functions.php.
 *
 * Nothing here loads the plugin or depends on it being active, and nothing here
 * requires wp-admin includes, so every helper is safe to call on the front end.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin file of the Two Factor plugin, relative to the plugins directory.
 *
 * Matches the `required_plugin` value the integration passes to BP_Integration,
 * so both sides agree on what "the plugin" means.
 *
 * @since BuddyBoss [BBVERSION]
 */
if ( ! defined( 'BB_TWO_FACTOR_PLUGIN_BASENAME' ) ) {
	define( 'BB_TWO_FACTOR_PLUGIN_BASENAME', 'two-factor/two-factor.php' );
}

/**
 * Oldest Two Factor release this integration supports.
 *
 * 0.16.0 is the floor because the frontend save path depends on
 * `Two_Factor_Core::action_user_profile_update_errors()`, which is public as of
 * that release, and on the `two_factor_login_backup_links` filter introduced
 * alongside it.
 *
 * @since BuddyBoss [BBVERSION]
 */
if ( ! defined( 'BB_TWO_FACTOR_MIN_VERSION' ) ) {
	define( 'BB_TWO_FACTOR_MIN_VERSION', '0.16.0' );
}

/**
 * Get the Two Factor plugin file, relative to the plugins directory.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Plugin basename, e.g. 'two-factor/two-factor.php'.
 */
function bb_two_factor_plugin_basename() {

	/**
	 * Filters the Two Factor plugin file this integration looks for.
	 *
	 * Lets a site that ships the plugin under a different folder name keep the
	 * integration working.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $basename Plugin basename.
	 */
	return (string) apply_filters( 'bb_two_factor_plugin_basename', BB_TWO_FACTOR_PLUGIN_BASENAME );
}

/**
 * Get the minimum Two Factor version this integration supports.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Version string.
 */
function bb_two_factor_min_plugin_version() {

	/**
	 * Filters the minimum supported Two Factor version.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $version Minimum supported version.
	 */
	return (string) apply_filters( 'bb_two_factor_min_plugin_version', BB_TWO_FACTOR_MIN_VERSION );
}

/**
 * Get the absolute path to the Two Factor plugin file.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Absolute path. Not guaranteed to exist.
 */
function bb_two_factor_plugin_file() {
	return trailingslashit( WP_PLUGIN_DIR ) . bb_two_factor_plugin_basename();
}

/**
 * Whether the Two Factor plugin files are present, active or not.
 *
 * Deliberately a file check rather than `get_plugins()`, which lives in
 * wp-admin/includes/plugin.php and is not available on the front end.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the plugin file exists on disk.
 */
function bb_two_factor_plugin_is_installed() {
	static $installed = null;

	if ( null === $installed ) {
		$installed = file_exists( bb_two_factor_plugin_file() );
	}

	return $installed;
}

/**
 * Read the version from the Two Factor plugin header.
 *
 * Used when the plugin is installed but not active, so its `TWO_FACTOR_VERSION`
 * constant is not defined. `get_file_data()` is core, front-end safe, and reads
 * only the first 8KB of the file.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Version string, or an empty string when it cannot be read.
 */
function bb_two_factor_plugin_header_version() {
	static $version = null;

	if ( null !== $version ) {
		return $version;
	}

	if ( ! bb_two_factor_plugin_is_installed() ) {
		$version = '';

		return $version;
	}

	$data    = get_file_data( bb_two_factor_plugin_file(), array( 'Version' => 'Version' ), 'plugin' );
	$version = isset( $data['Version'] ) ? (string) $data['Version'] : '';

	return $version;
}
