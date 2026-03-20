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

	// Build page options for select fields: Default, Custom URL, + published pages.
	$page_options = array(
		array(
			'value' => '',
			'label' => __( 'Default', 'buddyboss' ),
		),
		array(
			'value' => '0',
			'label' => __( 'Custom URL', 'buddyboss' ),
		),
	);

	// Merge published pages (bb_get_published_pages(true) returns [{value, label}] format).
	// Guard with function_exists — bp-core-admin-settings.php may not be loaded yet
	// during early feature registration.
	if ( function_exists( 'bb_get_published_pages' ) ) {
		$published_pages = bb_get_published_pages( true );
		if ( ! empty( $published_pages ) ) {
			$page_options = array_merge( $page_options, $published_pages );
		}
	}

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
			'type'              => 'select',
			'default'           => '',
			'sanitize_callback' => 'bb_registration_sanitize_redirection',
			'options'           => $page_options,
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
			'label'             => __( 'Custom Login URL', 'buddyboss' ),
			'type'              => 'text',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'conditional'       => array(
				'field'  => 'bb-login-redirection',
				'value'  => '0',
				'action' => 'show',
			),
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
			'type'              => 'select',
			'default'           => '',
			'sanitize_callback' => 'bb_registration_sanitize_redirection',
			'options'           => $page_options,
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
			'label'             => __( 'Custom Logout URL', 'buddyboss' ),
			'type'              => 'text',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'conditional'       => array(
				'field'  => 'bb-logout-redirection',
				'value'  => '0',
				'action' => 'show',
			),
			'order'             => 25,
		)
	);

	// =========================================================================
	// SECTION 2: Profile Type Redirects — profile_type_redirects
	// Custom field type rendered by a dedicated React component + AJAX endpoint.
	// Deferred to Phase 2 along with Registration Form panel.
	// =========================================================================

	/**
	 * Fires after Login Redirects panel fields are registered.
	 * Allows third-party extensions to add more sections or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_registration_after_login_redirects_settings_fields' );
}
