<?php
/**
 * BuddyBoss Admin Settings - Custom Profile Tabs Panel.
 *
 * Registers the section and field for the Custom Profile Tabs side panel under Member
 * Profiles. Platform ships only the placeholder: it registers the
 * `bb_profile_tabs` custom field type, and BuddyBoss Platform Pro renders the
 * management UI on the `bb_admin_settings_custom_field` filter. When Pro is
 * inactive, the Activation Required CTA is shown as a fallback.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Custom Profile Tabs panel section and field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_members_register_custom_profile_tabs_panel_fields() {

	// SECTION: Custom Profile Tabs.
	bb_register_feature_section(
		'members',
		'custom_profile_tabs',
		'custom_profile_tabs',
		array(
			'title'       => __( 'Custom Profile Tabs', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Custom Profile Tabs.
	bb_register_feature_field(
		'members',
		'custom_profile_tabs',
		'custom_profile_tabs',
		array(
			'name'       => 'bb_profile_tabs',
			'label'      => '',
			'type'       => 'bb_profile_tabs',
			'full_width' => true,
			'order'      => 10,
		)
	);

	/**
	 * Fires after Custom Profile Tabs section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_custom_profile_tabs_fields' );
}

/**
 * Main plugin file of the BuddyBoss Platform Pro addon.
 *
 * Pro renders the Custom Profile Tabs management UI; when it is inactive the
 * panel shows the Activation Required CTA, whose Install/Activate actions target
 * this plugin.
 *
 * @since BuddyBoss [BBVERSION]
 */
const BB_PROFILE_TABS_PRO_PLUGIN = 'buddyboss-platform-pro/buddyboss-platform-pro.php';

/**
 * AJAX: Report the installed/active state of BuddyBoss Platform Pro.
 *
 * The Activation Required CTA on the Custom Profile Tabs panel calls this on
 * mount to decide whether the action button reads "Install Now", "Activate Now",
 * or "Activate License" (when Pro is not installed and no valid license is
 * present to fetch it with).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_pro_addon_check_state() {
	if ( ! bp_current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( is_plugin_active( BB_PROFILE_TABS_PRO_PLUGIN ) ) {
		wp_send_json_success( array( 'state' => 'active' ) );
	}

	// The feature needs an active license, so license state takes precedence over
	// the installed / not-installed state.
	if ( bb_admin_pro_addon_mothership_available() && ! bb_admin_pro_addon_is_license_active() ) {
		wp_send_json_success(
			array(
				'state'       => 'needs-license',
				'license_url' => bb_admin_pro_addon_license_url(),
			)
		);
	}

	$all = function_exists( 'get_plugins' ) ? get_plugins() : array();

	if ( isset( $all[ BB_PROFILE_TABS_PRO_PLUGIN ] ) ) {
		wp_send_json_success( array( 'state' => 'installed' ) );
	}

	wp_send_json_success( array( 'state' => 'not-installed' ) );
}
add_action( 'wp_ajax_bb_admin_pro_addon_check_state', 'bb_admin_pro_addon_check_state' );

/**
 * AJAX: Activate the BuddyBoss Platform Pro plugin.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_pro_addon_activate() {
	if ( ! bp_current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	if ( ! function_exists( 'activate_plugin' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$result = activate_plugin( BB_PROFILE_TABS_PRO_PLUGIN );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Plugin activation failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $result->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_admin_pro_addon_activate', 'bb_admin_pro_addon_activate' );

/**
 * AJAX: Install (and activate) BuddyBoss Platform Pro from the add-on server.
 *
 * Pro is served from the BuddyBoss add-on server, not wordpress.org, so this
 * resolves the authorized package URL from the add-ons API (which requires an
 * active license) and installs it via the WP plugin installer.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_pro_addon_install() {
	if ( ! bp_current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'buddyboss' ) ), 403 );
	}
	check_ajax_referer( 'bb_admin_settings', '_ajax_nonce' );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

	if ( ! bb_admin_pro_addon_mothership_available() ) {
		wp_send_json_error(
			array(
				'message' => __( 'Please install BuddyBoss Platform Pro manually from your BuddyBoss account.', 'buddyboss' ),
			)
		);
	}

	// Fetching from the add-on server requires an active license.
	if ( ! bb_admin_pro_addon_is_license_active() ) {
		wp_send_json_error(
			array(
				'message'     => __( 'Please activate your BuddyBoss license to install BuddyBoss Platform Pro.', 'buddyboss' ),
				'license_url' => bb_admin_pro_addon_license_url(),
			)
		);
	}

	$product = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( 'buddyboss-platform-pro' );

	if ( empty( $product ) || empty( $product->_embedded->{'version-latest'}->url ) ) {
		wp_send_json_error(
			array(
				'message'     => __( 'BuddyBoss Platform Pro is not available under your current license.', 'buddyboss' ),
				'license_url' => bb_admin_pro_addon_license_url(),
			)
		);
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $product->_embedded->{'version-latest'}->url );

	if ( is_wp_error( $result ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installation failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $result->get_error_message() : '',
			)
		);
	}

	$activate = activate_plugin( BB_PROFILE_TABS_PRO_PLUGIN );
	if ( is_wp_error( $activate ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Plugin installed but activation failed. Please try again.', 'buddyboss' ),
				'detail'  => defined( 'WP_DEBUG' ) && WP_DEBUG ? $activate->get_error_message() : '',
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_bb_admin_pro_addon_install', 'bb_admin_pro_addon_install' );

/**
 * Whether the BuddyBoss Mothership license layer is available.
 *
 * Its helper classes ship with Platform and resolve license status and the
 * authorized add-on download URL. Absent on installs without the license layer.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_admin_pro_addon_mothership_available() {
	return class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' )
		&& class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' );
}

/**
 * Whether the BuddyBoss license is currently activated.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_admin_pro_addon_is_license_active() {
	if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
		return false;
	}

	$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();

	return (bool) $connector->getLicenseActivationStatus();
}

/**
 * Admin URL of the BuddyBoss license activation page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string
 */
function bb_admin_pro_addon_license_url() {
	return admin_url( 'admin.php?page=buddyboss-license' );
}
