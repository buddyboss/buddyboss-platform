<?php
/**
 * BuddyBoss Performance Filters.
 *
 * @package BuddyBoss\Performance\Filter
 * @since BuddyBoss 1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', 'bp_performance_setup_and_load_mu_plugin_file' );

/**
 * Admin init hook to check the mu plugin file is loaded or not.
 */
function bp_performance_setup_and_load_mu_plugin_file() {
	global $bp;
	if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-api-caching-mu.php' ) ) {

		$bp_mu_plugin_file_path = $bp->plugin_dir . '/bp-performance/mu-plugins/buddyboss-api-caching-mu.php';

		// Try to automatically install MU plugin.
		if ( wp_is_writable( WPMU_PLUGIN_DIR ) ) {
			if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			}
			$bp->plugin_dir;
			$wp_files_system = new \WP_Filesystem_Direct( array() );
			$wp_files_system->copy( $bp_mu_plugin_file_path, WPMU_PLUGIN_DIR . '/buddyboss-api-caching-mu.php' );
		}

		// If installing MU plugin fails, display warning and download link in WP admin.
		if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-api-caching-mu.php' ) ) {
			bp_performance_sitewide_notice();
		}

		$purge_nonce = filter_input( INPUT_GET, 'download_mu_file', FILTER_SANITIZE_STRING );
		if ( wp_verify_nonce( $purge_nonce, 'bp_performance_mu_download' ) ) {
			if ( file_exists( $bp_mu_plugin_file_path ) ) {
				header( 'Content-Type: application/force-download' );
				header( 'Content-Disposition: attachment; filename="' . basename( $bp_mu_plugin_file_path ) . '"' );
				header( 'Expires: 0' );
				header( 'Cache-Control: must-revalidate' );
				header( 'Pragma: public' );
				header( 'Content-Length: ' . filesize( $bp_mu_plugin_file_path ) );
				flush();
				readfile( $bp_mu_plugin_file_path );
				die();
			}
		}
	}
}

/**
 * Added Admin notice while file does not exists.
 */
function bp_performance_sitewide_notice() {
	$bp_performance_download_nonce = wp_create_nonce( 'bp_performance_mu_download' );

	$notice = sprintf(
		'%1$s <a href="%2$s">%3$s</a>. <br /><strong><a href="%4$s">%5$s</a></strong> %6$s',
		__( 'To enable caching you need to install the "BuddyBoss API Caching" plugin in your', 'buddyboss' ),
		'https://wordpress.org/support/article/must-use-plugins/',
		__( 'must-use plugins', 'buddyboss' ),
		admin_url( 'admin.php?page=bp-settings&tab=bp-performance&download_mu_file=' . $bp_performance_download_nonce ),
		__( 'Download the plugin', 'buddyboss' ),
		__( 'and then upload the plugin manually into the "/wp-content/mu-plugins/" directory on your server.', 'buddyboss' )
	);

	bp_core_add_admin_notice( $notice, 'error' );
}
