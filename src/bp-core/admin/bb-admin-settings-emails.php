<?php
/**
 * BuddyBoss Admin Settings - Emails Feature Registration.
 *
 * Registers the Emails feature in the Feature Registry. This is a hidden
 * feature (no card in the features grid) that provides the Email Templates
 * list screen accessible via the "Emails" admin submenu.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Emails feature and side panels in Feature Registry.
 *
 * @since BuddyBoss 3.0.0
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

	// Load meta field registrations for the email template edit modal.
	require_once __DIR__ . '/settings/emails/meta-fields.php';
	require_once __DIR__ . '/settings/emails/legacy-meta-bridge.php';

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

// The Email Digest ships in the BuddyBoss Addons plugin. This registers the stand-in panel
// for sites where that plugin is absent, inactive, or licensed on a plan that does not
// include it, and stands itself down whenever the real panel is registered.
//
// Required at FILE scope, not from inside the feature callback above. That callback runs at
// `bb_register_features` priority 25 — the SAME priority as the Notifications feature, which
// is what fires `bb_notifications_after_register_settings_fields`, the hook the placeholder's
// signpost listens on. Hooking from inside the callback therefore only worked while this file
// happened to be required before bb-admin-settings-notifications.php (same priority resolves
// by registration order), so reordering two `require_once` lines in bb-admin-settings-init.php
// would have silently dropped the Email Digest entry from the Notifications tab on exactly the
// sites the placeholder exists for. At file scope the listener is registered long before
// `bb_register_features` fires at all, and the order no longer matters.
require_once __DIR__ . '/settings/emails/email-digest-placeholder.php';
