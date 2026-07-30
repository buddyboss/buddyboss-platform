<?php
/**
 * BuddyBoss Admin Settings - Custom Group Tabs Panel.
 *
 * Registers the section and field for the Custom Group Tabs side panel under Social
 * Groups. Platform ships only the placeholder: it registers the `bb_group_tabs`
 * custom field type, and BuddyBoss Platform Pro renders the management UI on the
 * `bb_admin_settings_custom_field` filter. When Pro is inactive, the Activation
 * Required CTA is shown as a fallback.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Platform Pro plugin file, targeted by the activation CTA endpoints.
 *
 * @todo Move to a shared Pro-addon activation helper reused across features.
 *
 * @since BuddyBoss [BBVERSION]
 */
const BB_GROUP_TABS_PRO_PLUGIN = 'buddyboss-platform-pro/buddyboss-platform-pro.php';

/**
 * Register Custom Group Tabs panel section and field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_groups_register_custom_group_tabs_panel_fields() {

	// SECTION: Custom Group Tabs.
	bb_register_feature_section(
		'groups',
		'custom_group_tabs',
		'custom_group_tabs',
		array(
			'title'       => __( 'Custom Group Tabs', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Custom Group Tabs.
	bb_register_feature_field(
		'groups',
		'custom_group_tabs',
		'custom_group_tabs',
		array(
			'name'       => 'bb_group_tabs',
			'label'      => '',
			'type'       => 'bb_group_tabs',
			'full_width' => true,
			'order'      => 10,
		)
	);

	/**
	 * Fires after Custom Group Tabs section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_settings_after_custom_group_tabs_fields' );
}

/**
 * Report the installed/active state of BuddyBoss Platform Pro.
 *
 * @todo Move to a shared Pro-addon activation helper reused across features.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_group_tabs_addon_check_state() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( BB_GROUP_TABS_PRO_PLUGIN ) ) {
		wp_send_json_success( array( 'state' => 'active' ) );
	}

	$installed = function_exists( 'get_plugins' ) ? get_plugins() : array();

	// Activating a plugin already on disk needs only activate_plugins, so the
	// installed state is resolved before the license gate below.
	if ( isset( $installed[ BB_GROUP_TABS_PRO_PLUGIN ] ) ) {
		wp_send_json_success( array( 'state' => 'installed' ) );
	}

	// Not installed: if the Mothership license layer is present but the license is
	// inactive, Pro cannot be pulled from the BuddyBoss add-on server — surface a
	// license-activation CTA instead of a dead "Install Now" button.
	if ( bb_group_tabs_addon_mothership_available() && ! bb_group_tabs_addon_is_license_active() ) {
		wp_send_json_success(
			array(
				'state'       => 'needs-license',
				'license_url' => bb_group_tabs_addon_license_url(),
			)
		);
	}

	wp_send_json_success( array( 'state' => 'not-installed' ) );
}
add_action( 'wp_ajax_bb_group_tabs_addon_check_state', 'bb_group_tabs_addon_check_state' );

/**
 * Activate the BuddyBoss Platform Pro plugin.
 *
 * @todo Move to a shared Pro-addon activation helper reused across features.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_group_tabs_addon_activate() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$activated = activate_plugin( BB_GROUP_TABS_PRO_PLUGIN );

	if ( is_wp_error( $activated ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Plugin activation failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $activated->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_group_tabs_addon_activate', 'bb_group_tabs_addon_activate' );

/**
 * Install and activate BuddyBoss Platform Pro from the BuddyBoss add-on server.
 *
 * @todo Move to a shared Pro-addon activation helper reused across features.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_group_tabs_addon_install() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

	if ( ! bb_group_tabs_addon_mothership_available() ) {
		wp_send_json_error(
			array(
				'message' => __( 'Please install BuddyBoss Platform Pro manually from your BuddyBoss account.', 'buddyboss' ),
			)
		);
	}

	if ( ! bb_group_tabs_addon_is_license_active() ) {
		wp_send_json_error(
			array(
				'message'     => __( 'Please activate your BuddyBoss license to install BuddyBoss Platform Pro.', 'buddyboss' ),
				'license_url' => bb_group_tabs_addon_license_url(),
			)
		);
	}

	$product = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( 'buddyboss-platform-pro' );

	if ( empty( $product ) || empty( $product->_embedded->{'version-latest'}->url ) ) {
		wp_send_json_error(
			array(
				'message'     => __( 'BuddyBoss Platform Pro is not available under your current license.', 'buddyboss' ),
				'license_url' => bb_group_tabs_addon_license_url(),
			)
		);
	}

	$upgrader  = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
	$installed = $upgrader->install( $product->_embedded->{'version-latest'}->url );

	if ( is_wp_error( $installed ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installation failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $installed->get_error_message() : '',
			)
		);
	}

	$activated = activate_plugin( BB_GROUP_TABS_PRO_PLUGIN );

	if ( is_wp_error( $activated ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installed but activation failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $activated->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_group_tabs_addon_install', 'bb_group_tabs_addon_install' );

/**
 * Whether the BuddyBoss Mothership license layer is available.
 *
 * @todo Move to a shared Pro-addon activation helper reused across features.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_group_tabs_addon_mothership_available() {
	return class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' )
		&& class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' );
}

/**
 * Whether the BuddyBoss license is currently activated.
 *
 * @todo Move to a shared Pro-addon activation helper reused across features.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_group_tabs_addon_is_license_active() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		return false;
	}

	$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();

	return (bool) $connector->getLicenseActivationStatus();
}

/**
 * Admin URL of the BuddyBoss license activation page.
 *
 * @todo Move to a shared Pro-addon activation helper reused across features.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string
 */
function bb_group_tabs_addon_license_url() {
	return admin_url( 'admin.php?page=buddyboss-license' );
}
