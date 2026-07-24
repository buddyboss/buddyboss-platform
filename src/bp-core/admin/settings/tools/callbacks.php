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
function bb_tools_mothership_available() {
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
function bb_tools_is_license_active() {
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
function bb_tools_get_license_url() {
	return admin_url( 'admin.php?page=buddyboss-license' );
}

/**
 * AJAX: Report installed/active state of the buddyboss-tools plugin.
 *
 * The Activation Required CTA component calls this on mount to decide
 * whether the action button shows "Install Now", "Activate Now", or
 * "Activate License" (when the addon is not installed and no valid license
 * is present to install it with).
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bb_tools_ajax_check_plugin_state() {
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
	if ( bb_tools_mothership_available() && ! bb_tools_is_license_active() ) {
		wp_send_json_success(
			array(
				'state'       => 'needs-license',
				'license_url' => bb_tools_get_license_url(),
			)
		);
	}

	wp_send_json_success( array( 'state' => 'not-installed' ) );
}
add_action( 'wp_ajax_bb_tools_check_plugin_state', 'bb_tools_ajax_check_plugin_state' );

/**
 * AJAX: Activate the buddyboss-tools plugin.
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bb_tools_ajax_activate_plugin() {
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
add_action( 'wp_ajax_bb_tools_activate_plugin', 'bb_tools_ajax_activate_plugin' );

/**
 * AJAX: Install the buddyboss-tools plugin via the WP plugin installer.
 *
 * buddyboss-tools is a licensed BuddyBoss addon served from the BuddyBoss
 * add-on server, not wordpress.org. When the Mothership license layer is
 * present this handler requires an active license and resolves the authorized
 * package URL from the add-ons API. Without a valid license it returns a
 * license-activation error. On installs without the Mothership layer it falls
 * back to the generic plugin-install API (wordpress.org or a runtime
 * `plugins_api` filter).
 *
 * @since BuddyBoss 3.1.0
 *
 * @return void
 */
function bb_tools_ajax_install_plugin() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss-platform' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

	$download_url = '';

	if ( bb_tools_mothership_available() ) {

		// License-gated path: the addon is not on wordpress.org, so a valid,
		// activated license is required to fetch it from the add-on server.
		if ( ! bb_tools_is_license_active() ) {
			wp_send_json_error(
				array(
					'message'     => __( 'Please activate your BuddyBoss license to install this add-on.', 'buddyboss-platform' ),
					'license_url' => bb_tools_get_license_url(),
				)
			);
		}

		$product = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( 'buddyboss-tools' );

		if ( empty( $product ) || empty( $product->_embedded->{'version-latest'}->url ) ) {
			wp_send_json_error(
				array(
					'message'     => __( 'The BuddyBoss Tools add-on is not available under your current license.', 'buddyboss-platform' ),
					'license_url' => bb_tools_get_license_url(),
				)
			);
		}

		$download_url = $product->_embedded->{'version-latest'}->url;
	} else {

		// Fallback for environments without the Mothership license layer.
		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => 'buddyboss-tools',
				'fields' => array( 'sections' => false ),
			)
		);

		if ( is_wp_error( $api ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces plugin-api lookup failures.
				error_log( sprintf( 'BB Tools: plugins_api(buddyboss-tools) failed: %s', $api->get_error_message() ) );
			}
			wp_send_json_error(
				array(
					'message' => __( 'Could not look up plugin information. Please try again.', 'buddyboss-platform' ),
					'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $api->get_error_message() : '',
				)
			);
		}

		$download_url = $api->download_link;
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $download_url );

	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces installer failures.
			error_log( sprintf( 'BB Tools: Plugin_Upgrader->install(buddyboss-tools) failed: %s', $result->get_error_message() ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installation failed. Please try again.', 'buddyboss-platform' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $result->get_error_message() : '',
			)
		);
	}

	$activate = activate_plugin( 'buddyboss-tools/buddyboss-tools.php' );
	if ( is_wp_error( $activate ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces post-install activation failures.
			error_log( sprintf( 'BB Tools: activate_plugin(buddyboss-tools) failed after install: %s', $activate->get_error_message() ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installed but activation failed. Please try again.', 'buddyboss-platform' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $activate->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_tools_install_plugin', 'bb_tools_ajax_install_plugin' );
