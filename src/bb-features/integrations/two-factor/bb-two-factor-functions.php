<?php
/**
 * Two-Factor integration public API.
 *
 * Every read of the Two Factor plugin goes through the bb_two_factor_*() wrappers in
 * this file. Three of the plugin's own methods reach a wp_die() for a member whose
 * configured providers no longer resolve, so no direct Two_Factor_Core:: status call
 * may appear outside this file.
 *
 * This step adds the state helpers. The per-user wrappers, the screen-data builder
 * and the renderer follow in later steps.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Whether the Two Factor plugin is active.
 *
 * Mirrors BP_Integration::is_activated() rather than calling is_plugin_active(),
 * which lives in wp-admin/includes/plugin.php: the option check works on the front
 * end and covers network activation on multisite.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the plugin is active for this site.
 */
function bb_two_factor_plugin_is_active() {
	$basename = bb_two_factor_plugin_basename();
	$plugins  = (array) get_option( 'active_plugins', array() );

	if ( in_array( $basename, $plugins, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );

		return isset( $network_plugins[ $basename ] );
	}

	return false;
}

/**
 * Get the version of the Two Factor plugin that is present.
 *
 * Prefers the plugin's own constant, which exists only once its main file has run,
 * and falls back to the plugin header so an installed-but-inactive copy still
 * reports a version.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string Version string, or an empty string when the plugin is absent.
 */
function bb_two_factor_plugin_version() {
	if ( defined( 'TWO_FACTOR_VERSION' ) ) {
		return (string) TWO_FACTOR_VERSION;
	}

	return bb_two_factor_plugin_header_version();
}

/**
 * Whether the Two Factor plugin is active, loaded and new enough to build on.
 *
 * The class check matters: the option can say "active" before the plugin's code has
 * run, and every later step calls into Two_Factor_Core.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the integration can safely use the plugin.
 */
function bb_two_factor_is_supported() {
	if ( ! bb_two_factor_plugin_is_active() || ! class_exists( 'Two_Factor_Core' ) ) {
		return false;
	}

	$version = bb_two_factor_plugin_version();

	if ( '' === $version ) {
		return false;
	}

	return version_compare( $version, bb_two_factor_min_plugin_version(), '>=' );
}

/**
 * Resolve the state of the Two Factor plugin, for the admin panel to report.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string One of 'active', 'unsupported_version', 'installed_inactive',
 *                'not_installed'.
 */
function bb_two_factor_get_plugin_state() {
	static $state = null;

	if ( null === $state ) {
		if ( ! bb_two_factor_plugin_is_installed() ) {
			$state = 'not_installed';
		} elseif ( ! bb_two_factor_plugin_is_active() ) {
			$state = 'installed_inactive';
		} elseif ( ! bb_two_factor_is_supported() ) {
			$state = 'unsupported_version';
		} else {
			$state = 'active';
		}
	}

	/**
	 * Filters the resolved Two Factor plugin state.
	 *
	 * Useful for exercising each admin empty state without installing or
	 * downgrading the plugin.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $state One of 'active', 'unsupported_version',
	 *                      'installed_inactive', 'not_installed'.
	 */
	return (string) apply_filters( 'bb_two_factor_plugin_state', $state );
}

/**
 * Whether the Two-Factor feature is switched on in the admin.
 *
 * Reads the `bb-active-features` option directly rather than going through the
 * Feature Registry: this runs at bp_include, before feature discovery, where
 * BB_Feature_Registry::bb_is_feature_active() returns false for a feature it has
 * not registered yet. An absent key means **on**, matching reCAPTCHA, Reactions and
 * the Integration Bridge's own default.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the feature is enabled.
 */
function bb_two_factor_feature_is_on() {
	$active_features = bp_get_option( 'bb-active-features', array() );

	if ( ! is_array( $active_features ) || ! array_key_exists( 'two-factor', $active_features ) ) {
		$is_on = true;
	} else {
		$is_on = ! empty( $active_features['two-factor'] );
	}

	/**
	 * Filters whether the Two-Factor feature is enabled.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $is_on Whether the feature is enabled.
	 */
	return (bool) apply_filters( 'bb_two_factor_is_enabled', $is_on );
}

/**
 * The guard every consumer of this integration uses.
 *
 * True only when the plugin is usable and the feature is switched on. Never use
 * bp_is_active( 'two-factor' ) or bb_add_action_if_active() for this: both read
 * `bp-active-components`, which the Integration Bridge writes only after the first
 * admin toggle, so a fresh install would read as off.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the integration should do anything at all.
 */
function bb_two_factor_is_active() {
	return bb_two_factor_is_supported() && bb_two_factor_feature_is_on();
}
