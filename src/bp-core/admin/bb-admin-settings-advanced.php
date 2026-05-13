<?php
/**
 * BuddyBoss Admin Settings - Advanced Feature Registration.
 *
 * Registers the Advanced feature in the Feature Registry and loads
 * all Advanced settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the Advanced feature and its settings.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_register_advanced_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'advanced',
		array(
			'label'              => __( 'Advanced', 'buddyboss' ),
			'description'        => __( 'Find your site extended performance and privacy settings.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear-six',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'required'           => true,
			'is_active_callback' => '__return_true',
			'settings_route'     => '/settings/advanced',
			'order'              => 150,
		)
	);

	// Load settings sub-files.
	require_once __DIR__ . '/settings/advanced/callbacks.php';
	require_once __DIR__ . '/settings/advanced/settings-general.php';
	require_once __DIR__ . '/settings/advanced/settings-privacy.php';
	require_once __DIR__ . '/settings/advanced/settings-telemetry.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: General (default).
	bb_register_side_panel(
		'advanced',
		'general',
		array(
			'title'      => __( 'General', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gear',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 127427,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Privacy.
	bb_register_side_panel(
		'advanced',
		'privacy',
		array(
			'title'    => __( 'Privacy', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-lock-simple',
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

	// Side Panel 3: Telemetry (free users only).
	if ( ! function_exists( 'bb_platform_pro' ) ) {
		bb_register_side_panel(
			'advanced',
			'telemetry',
			array(
				'title'    => __( 'Telemetry', 'buddyboss' ),
				'icon'     => array(
					'type'  => 'font',
					'class' => 'bb-icons-rl bb-icons-rl-cylinder',
				),
				'help_url' => 'https://www.buddyboss.com/usage-tracking/?utm_source=product&utm_medium=platform&utm_campaign=telemetry',
				'order'    => 30,
			)
		);
	}

	// =========================================================================
	// REGISTER FIELDS (delegated to sub-files)
	// =========================================================================

	bb_advanced_register_general_fields();
	bb_advanced_register_privacy_fields();

	// Telemetry fields only for free users.
	if ( ! function_exists( 'bb_platform_pro' ) ) {
		bb_advanced_register_telemetry_fields();
	}

	/**
	 * Fires after all Advanced settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_advanced_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_advanced_feature', 25 );
