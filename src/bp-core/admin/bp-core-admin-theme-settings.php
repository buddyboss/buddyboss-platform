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
	function buddyboss_theme_sudharo_tapas() {
		if ( ! function_exists( 'buddyboss_theme' ) ) {
			return;
		}

		if ( is_multisite() ) {
			$saved_licenses = get_site_option( 'bboss_updater_saved_licenses' );
		} else {
			$saved_licenses = get_option( 'bboss_updater_saved_licenses' );
		}
		$license_is_there          = false;
		$license_is_there_inactive = false;
		if ( ! empty( $saved_licenses ) ) {
			foreach ( $saved_licenses as $package_id => $license_details ) {
				if ( ! empty( $license_details['license_key'] ) && ! empty( $license_details['product_keys'] ) && is_array( $license_details['product_keys'] ) && in_array( 'BB_THEME', $license_details['product_keys'] ) ) {
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
	function buddyboss_theme_update_transient_update_themes( $transient ) {
		buddyboss_theme_sudharo_tapas();

		if ( function_exists( 'buddyboss_theme' ) && function_exists( 'buddyboss_theme_get_theme_sudharo' ) && buddyboss_theme_get_theme_sudharo() ) {
			$theme_object                                                = array();
			$theme_object['package']                                     = 'http://update.buddyboss.com/wp-content/uploads/2020/04/5e8bd5047cdfd-13069dcf6fa99e0cfda19a1ae6afcf3f1916a2f7-buddyboss-theme.zip';
			$theme_object['new_version']                                 = '1.4.1';
			$theme_object['url']                                         = 'https://www.buddyboss.com/';
			$transient->response[ basename( get_template_directory() ) ] = $theme_object;
		}

		return $transient;
	}

	add_filter( 'pre_set_site_transient_update_themes', 'buddyboss_theme_update_transient_update_themes' );
	add_filter( 'site_transient_update_themes', 'buddyboss_theme_update_transient_update_themes', 99999 );
}

if ( ! function_exists( 'buddyboss_theme_get_theme_sudharo' ) ) {
	function buddyboss_theme_get_theme_sudharo() {
//		$whitelist_addr = array(
//			'127.0.0.1',
//			'::1'
//		);
//
//		if ( in_array( $_SERVER['REMOTE_ADDR'], $whitelist_addr ) ) {
//			return false;
//		}
//
//		$whitelist_domain = array(
//			'.test',
//			'.dev',
//			'staging.',
//		);
//
//		$return = true;
//		foreach ( $whitelist_domain as $domain ) {
//			if ( false !== strpos( $domain, $_SERVER['SERVER_NAME'] ) ) {
//				$return = false;
//			}
//		}
//
//		if ( $return ) {
//			return false;
//		}

		if ( is_multisite() ) {
			$value = get_site_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
		} else {
			$value = get_option( 'be5f330bbd49d6160ff4658ac3d219ee' );
		}
		if ( ! empty( $value ) ) {
			return true;
		}
		return false;
	}
}

