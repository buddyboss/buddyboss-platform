<?php
/**
 * BuddyBoss Admin Settings - Login & Registration Feature Registration.
 *
 * Registers the Login & Registration feature in the Feature Registry and loads
 * all Registration settings (side panels, sections, fields).
 *
 * Architecture: required => true (same as Members).
 * The feature card toggle is always ON / greyed out.
 * "Enable Registration" is a field inside Panel 1 (not the card toggle).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Login & Registration feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_register_registration_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'registration',
		array(
			'label'              => __( 'Login & Registration', 'buddyboss' ),
			'description'        => __( 'Manage member registration, login redirects, account settings, and registration restrictions.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-plus',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'required'           => true,
			'is_active_callback' => function () {
				return true;
			},
			'settings_route'     => '/settings/registration',
			'order'              => 25,
		)
	);

	// Load settings sub-files.
	require_once __DIR__ . '/settings/registration/callbacks.php';
	require_once __DIR__ . '/settings/registration/settings-registration.php';
	require_once __DIR__ . '/settings/registration/settings-login-redirects.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Registration (default).
	bb_register_side_panel(
		'registration',
		'registration',
		array(
			'title'      => __( 'Registration', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-user-plus',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62793,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Login Redirects.
	bb_register_side_panel(
		'registration',
		'login_redirects',
		array(
			'title'    => __( 'Login Redirects', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-arrow-bend-up-right',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62793,
					),
					'admin.php'
				)
			),
			'order'    => 20,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Registration.
	bb_registration_register_panel_fields();

	// Panel 2: Login Redirects.
	bb_registration_register_login_redirects_panel_fields();

	/**
	 * Fires after all Registration settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_registration_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_registration_feature', 16 );
