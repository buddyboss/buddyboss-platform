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
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * AJAX: Report installed/active state of the buddyboss-tools plugin.
 *
 * The Activation Required CTA component calls this on mount to decide
 * whether the action button shows "Install Now" or "Activate Now".
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_tools_ajax_check_plugin_state() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
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

	wp_send_json_success( array( 'state' => $installed ? 'installed' : 'not-installed' ) );
}
add_action( 'wp_ajax_bb_tools_check_plugin_state', 'bb_tools_ajax_check_plugin_state' );

/**
 * AJAX: Activate the buddyboss-tools plugin.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_tools_ajax_activate_plugin() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
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
				'message' => __( 'Plugin activation failed. Please try again.', 'buddyboss' ),
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
 * Requires the plugin to be available from the configured update source
 * (wordpress.org or the BuddyBoss mothership). On dev installs without a
 * configured source, this call will fail and the user must install the
 * plugin manually.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_tools_ajax_install_plugin() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

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
				'message' => __( 'Could not look up plugin information. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $api->get_error_message() : '',
			)
		);
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $api->download_link );

	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Gated on WP_DEBUG; surfaces installer failures.
			error_log( sprintf( 'BB Tools: Plugin_Upgrader->install(buddyboss-tools) failed: %s', $result->get_error_message() ) );
		}
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installation failed. Please try again.', 'buddyboss' ),
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
				'message' => __( 'Plugin installed but activation failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $activate->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_tools_install_plugin', 'bb_tools_ajax_install_plugin' );
