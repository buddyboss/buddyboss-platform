<?php
/**
 * BuddyBoss Admin Settings — Tools panel callbacks + AJAX handlers.
 *
 * Repair Platform itself is action-driven — no field values to save server-side.
 * The React UI dispatches each repair item through the existing locked-BC AJAX
 * endpoints (`bp_admin_repair_tools_wrapper_function` and `bp_admin_forum_repair_tools_wrapper_function`)
 * which both live in Platform unchanged.
 *
 * This file owns the Install/Activate AJAX handlers powering the Activation
 * Required CTA on the Sample Data and Migration Tools panels.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the BuddyBoss Mothership license layer is available.
 *
 * The buddyboss-tools addon is a licensed BuddyBoss product served from the
 * BuddyBoss add-on server (not wordpress.org). When these Mothership helper
 * classes are present, license status and the authorized download URL can be
 * resolved; otherwise the install path falls back to the generic plugin API.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return bool True when both Mothership helper classes are loaded.
 */
function bboss_tools_mothership_available() {
	return class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' )
		&& class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' );
}

/**
 * Whether the BuddyBoss license is currently activated.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return bool True when the Mothership connector reports an active license.
 */
function bboss_tools_is_license_active() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		return false;
	}

	$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();

	return (bool) $connector->getLicenseActivationStatus();
}

/**
 * Admin URL of the BuddyBoss license activation page.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return string Admin URL for the license activation screen.
 */
function bboss_tools_get_license_url() {
	// Empty in builds without the BuddyBoss licence screen (the wordpress.org edition).
	$url = '';

	return $url;
}

/**
 * AJAX: Report installed/active state of the buddyboss-tools plugin.
 *
 * The Activation Required CTA component calls this on mount to decide
 * whether the action button shows "Install Now", "Activate Now", or
 * "Activate License" (when the addon is not installed and no valid license
 * is present to install it with). Builds without the in-dashboard installer
 * report `download` with a `download_url` instead of `not-installed`.
 *
 * @since BuddyBoss 3.1.0
 * @since BuddyBoss [BBVERSION] Reports the `download` state when the installer is not in the build.
 *
 * @return void
 */
function bboss_tools_ajax_check_plugin_state() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss-platform' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$slug = 'buddyboss-tools/buddyboss-tools.php';
	if ( is_plugin_active( $slug ) ) {
		wp_send_json_success( array( 'state' => 'active' ) );
	}

	$all       = function_exists( 'get_plugins' ) ? get_plugins() : array();
	$installed = isset( $all[ $slug ] );

	if ( $installed ) {
		wp_send_json_success( array( 'state' => 'installed' ) );
	}

	// Not installed: if the Mothership license layer is present but the license
	// is inactive, the addon cannot be pulled from the BuddyBoss add-on server —
	// surface a license-activation CTA instead of a dead "Install Now" button.
	if ( bboss_tools_mothership_available() && ! bboss_tools_is_license_active() ) {
		wp_send_json_success(
			array(
				'state'       => 'needs-license',
				'license_url' => bboss_tools_get_license_url(),
			)
		);
	}

	// Builds without the in-dashboard installer (the wordpress.org edition) reach this
	// instead: the addon is downloaded from the BuddyBoss account and uploaded by hand.
	// Builds without upsell links send no account URL, and the screen shows no button.
	$download_url = '';
	wp_send_json_success(
		array(
			'state'        => 'download',
			'download_url' => $download_url,
		)
	);
}
add_action( 'wp_ajax_bb_tools_check_plugin_state', 'bboss_tools_ajax_check_plugin_state' );

/**
 * AJAX: Activate the buddyboss-tools plugin.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bboss_tools_ajax_activate_plugin() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss-platform' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$result = activate_plugin( 'buddyboss-tools/buddyboss-tools.php' );

	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces activation failures.
			error_log( sprintf( 'BB Tools: failed to activate buddyboss-tools plugin: %s', $result->get_error_message() ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin activation failed. Please try again.', 'buddyboss-platform' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $result->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_tools_activate_plugin', 'bboss_tools_ajax_activate_plugin' );
