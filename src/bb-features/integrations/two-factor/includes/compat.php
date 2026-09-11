<?php
/**
 * Two Factor plugin compatibility probe.
 *
 * Which plugin file this integration looks for, and the oldest release it
 * supports. Both are filterable so a site can point the integration at a
 * differently packaged copy.
 *
 * Nothing here loads the plugin, depends on it being active, or needs wp-admin
 * includes, so every helper is front-end safe.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Plugin file of the Two Factor plugin, relative to the plugins directory.
 *
 * Also the `required_plugin` value passed to BP_Integration.
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
	 * For sites that ship the plugin under a different folder name.
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
