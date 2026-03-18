<?php
/**
 * BuddyBoss Admin Settings - Emails Feature Registration.
 *
 * Registers the Emails feature in the Feature Registry. This is a hidden
 * feature (no card in the features grid) that provides the Email Templates
 * list screen accessible via the "Emails" admin submenu.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Emails feature and side panels in Feature Registry.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_emails_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'emails',
		array(
			'label'              => __( 'Emails', 'buddyboss' ),
			'description'        => __( 'Manage email templates sent by BuddyBoss.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-envelope-simple',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'hidden'             => true, // No card in the features grid.
			'required'           => true, // Cannot be deactivated.
			'is_active_callback' => '__return_true',
			'settings_route'     => '/settings/emails',
			'order'              => 200,
		)
	);

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel: Email Templates (list screen).
	bb_register_side_panel(
		'emails',
		'all_emails',
		array(
			'title'      => __( 'Emails', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list-bullets',
			),
			'order'      => 10,
			'is_default' => true,
		)
	);
}

add_action( 'bb_register_features', 'bb_admin_settings_register_emails_feature', 25 );
