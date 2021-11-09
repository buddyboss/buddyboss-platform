<?php
/**
 * BuddyBoss Theme Settings.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.2.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'buddyboss_theme_sudharo_tapas' ) ) {
	/**
	 * Theme sudho tapas.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	function buddyboss_theme_sudharo_tapas() {
		if ( ! function_exists( 'buddyboss_theme' ) ) {
			return;
		}
		$saved_licenses = get_option( 'bboss_updater_saved_licenses' );
		if ( is_multisite() ) {
			$saved_site_licenses = get_site_option( 'bboss_updater_saved_licenses' );
			if ( ! empty( $saved_site_licenses ) ) {
				$saved_licenses = $saved_site_licenses;
			}
		}
		$license_is_there          = false;
		$license_is_there_inactive = false;
		if ( ! empty( $saved_licenses ) ) {
			foreach ( $saved_licenses as $package_id => $license_details ) {
				if ( ! empty( $license_details['license_key'] ) && ! empty( $license_details['product_keys'] ) && is_array( $license_details['product_keys'] ) && in_array( 'BB_THEME', $license_details['product_keys'], true ) ) {
					$license_is_there = true;
					if ( isset( $license_details['is_active'] ) && false === $license_details['is_active'] ) {
						$license_is_there_inactive = true;
					}
				}
			}
		}
		if ( ! $license_is_there && ! $license_is_there_inactive ) {
			if ( is_multisite() ) {
				update_site_option( 'be5f330bbd49d6160ff4658ac3d219ee', '1' );
			} else {
				update_option( 'be5f330bbd49d6160ff4658ac3d219ee', '1' );
			}
		} else {
			if ( is_multisite() ) {
				delete_site_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
			} else {
				delete_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
			}
		}
	}

	add_action( 'admin_init', 'buddyboss_theme_sudharo_tapas', 999999 );
	add_action( 'after_switch_theme', 'buddyboss_theme_sudharo_tapas' );
}

if ( ! function_exists( 'buddyboss_theme_update_transient_update_themes' ) ) {
	/**
	 * Theme sudho tapas.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	function buddyboss_theme_update_transient_update_themes( $transient ) {
		buddyboss_theme_sudharo_tapas();
		return $transient;
	}

	add_filter( 'pre_set_site_transient_update_themes', 'buddyboss_theme_update_transient_update_themes' );
	add_filter( 'site_transient_update_themes', 'buddyboss_theme_update_transient_update_themes', 99999 );
}

if ( ! function_exists( 'buddyboss_theme_get_theme_sudharo' ) ) {
	/**
	 * Theme sudho tapas.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	function buddyboss_theme_get_theme_sudharo() {
		$whitelist_domain = array(
			'.test',
			'.dev',
			'staging.',
			'localhost',
			'.local',
		);

		foreach ( $whitelist_domain as $domain ) {
			if ( false !== strpos( $_SERVER['SERVER_NAME'], $domain ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				return false;
			}
		}

		$value = get_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
		if ( is_multisite() ) {
			$value = get_site_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
		}
		if ( ! empty( $value ) ) {
			return true;
		}
		return false;
	}
}

