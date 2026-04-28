<?php
/**
 * BuddyBoss Admin Settings - Login Redirects Panel.
 *
 * Registers sections and fields for the Login Redirects side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Login Redirects panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_registration_register_login_redirects_panel_fields() {

	$feature_id = 'registration';
	$panel_id   = 'login_redirects';

	// =========================================================================
	// SECTION 1: Global Redirects — global_redirects
	// =========================================================================

	bb_register_feature_section(
		$feature_id,
		$panel_id,
		'global_redirects',
		array(
			'title'       => __( 'Global Redirects', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field 10: After Login.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'global_redirects',
		array(
			'name'              => 'bb-login-redirection',
			'label'             => __( 'After Login', 'buddyboss' ),
			'description'       => __( 'Select a page or external link to redirect your members to after they login.', 'buddyboss' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_search_published_pages',
			'default'           => '',
			'sanitize_callback' => 'bb_registration_sanitize_redirection',
			'placeholder'       => __( 'Default', 'buddyboss' ),
			'group'             => 'login_redirect',
			'order'             => 10,
		)
	);

	// Field 10a: Custom Login URL (conditional: when Custom URL selected).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'global_redirects',
		array(
			'name'              => 'bb-custom-login-redirection',
			'label'             => '',
			'type'              => 'text',
			'default'           => '',
			'sanitize_callback' => 'bb_registration_sanitize_redirect_url',
			'placeholder'       => __( 'Enter custom URL', 'buddyboss' ),
			'conditional'       => array(
				'field'  => 'bb-login-redirection',
				'value'  => '0',
				'action' => 'show',
			),
			'group'             => 'login_redirect',
			'order'             => 15,
		)
	);

	// Field 11: After Logout.
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'global_redirects',
		array(
			'name'              => 'bb-logout-redirection',
			'label'             => __( 'After Logout', 'buddyboss' ),
			'description'       => __( 'Select a page or external link to redirect your members to after they logout.', 'buddyboss' ),
			'type'              => 'async_select',
			'async_action'      => 'bb_admin_search_published_pages',
			'default'           => '',
			'sanitize_callback' => 'bb_registration_sanitize_redirection',
			'placeholder'       => __( 'Default', 'buddyboss' ),
			'group'             => 'logout_redirect',
			'order'             => 20,
		)
	);

	// Field 11a: Custom Logout URL (conditional: when Custom URL selected).
	bb_register_feature_field(
		$feature_id,
		$panel_id,
		'global_redirects',
		array(
			'name'              => 'bb-custom-logout-redirection',
			'label'             => '',
			'type'              => 'text',
			'default'           => '',
			'sanitize_callback' => 'bb_registration_sanitize_redirect_url',
			'placeholder'       => __( 'Enter custom URL', 'buddyboss' ),
			'conditional'       => array(
				'field'  => 'bb-logout-redirection',
				'value'  => '0',
				'action' => 'show',
			),
			'group'             => 'logout_redirect',
			'order'             => 25,
		)
	);

	// =========================================================================
	// SECTION 2: Profile Type Redirects — profile_type_redirects
	// Renders a paginated list of profile types with per-type login/logout
	// redirect dropdowns. Only visible when xProfile component is active
	// and member types exist.
	// =========================================================================

	if ( bp_is_active( 'xprofile' ) && function_exists( 'bp_get_member_types' ) ) {
		bb_register_feature_section(
			$feature_id,
			$panel_id,
			'profile_type_redirects',
			array(
				'title'       => __( 'Profile Type Redirects', 'buddyboss' ),
				'description' => __( 'Choose a page or external link where each profile type will be redirected after login or logout.', 'buddyboss' ),
				'order'       => 20,
				// The controlling toggle (bp-member-type-enable-disable) lives in the
				// Members feature, not Registration. Its live value is mirrored to
				// window.bbAdminData.isProfileTypesEnabled at page load and refreshed
				// from save responses, so the React layer can hide this whole section
				// (header + field) the moment Profile Types is toggled off — without a
				// reload and without leaking cross-feature settings into Registration's
				// settings map. The `source: 'bbAdminData'` shape is read by
				// evaluateConditional() in src/js/admin/settings/utils/conditional.js.
				'conditional' => array(
					'source' => 'bbAdminData',
					'field'  => 'isProfileTypesEnabled',
					'value'  => true,
					'action' => 'hide',
				),
			)
		);

		// This field saves per-type redirects via bb_admin_update_member_type AJAX,
		// not the main settings save handler. sanitize_callback returns empty array
		// to prevent accidental data persistence through the main save flow.
		bb_register_feature_field(
			$feature_id,
			$panel_id,
			'profile_type_redirects',
			array(
				'name'              => '_bb-profile-type-redirects',
				'label'             => '',
				'type'              => 'profile_type_redirects',
				'default'           => array(),
				'sanitize_callback' => '__return_empty_array',
				'full_width'        => true,
				'order'             => 10,
			)
		);
	}

	/**
	 * Fires after Login Redirects panel fields are registered.
	 * Allows third-party extensions to add more sections or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_registration_after_login_redirects_settings_fields' );
}
