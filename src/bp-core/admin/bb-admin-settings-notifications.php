<?php
/**
 * BuddyBoss Admin Settings - Notifications Feature Registration.
 *
 * Registers the Notifications feature in the Feature Registry and loads
 * all Notifications settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Notifications feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_admin_settings_register_notifications_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'notifications',
		array(
			'label'              => __( 'Notifications', 'buddyboss' ),
			'description'        => __( 'Notify members of relevant activity with a toolbar bubble or email, and let them customize their notification settings.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-bell-simple',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'notifications' );
			},
			'settings_route'     => '/settings/notifications',
			'order'              => 50,
		)
	);

	// When notifications is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on notification functions that aren't loaded.
	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	// Load settings sub-files only when notifications is active to avoid parsing
	// callbacks and field registrations when the feature is off.
	require_once __DIR__ . '/settings/notifications/callbacks.php';
	require_once __DIR__ . '/settings/notifications/settings-on-screen.php';
	require_once __DIR__ . '/settings/notifications/settings-notification-types.php';
	require_once __DIR__ . '/settings/notifications/settings-web-push.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Notification Types (default).
	bb_register_side_panel(
		'notifications',
		'notification_types',
		array(
			'title'      => __( 'Notification Types', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-bell-simple',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125369,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: On-screen Notifications.
	bb_register_side_panel(
		'notifications',
		'on_screen_notifications',
		array(
			'title'    => __( 'On-screen Notifications', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-monitor',
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

	// Side Panel 3: Web Push Notifications.
	bb_register_side_panel(
		'notifications',
		'web_push_notifications',
		array(
			'title'    => __( 'Web Push Notifications', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-paper-plane-tilt',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125638,
					),
					'admin.php'
				)
			),
			'order'    => 30,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Notification Types.
	bb_notifications_register_types_panel_fields();

	// Panel 2: On-screen Notifications.
	bb_notifications_register_on_screen_panel_fields();

	// Panel 3: Web Push Notifications.
	bb_notifications_register_web_push_panel_fields();

	/**
	 * Fires after all Notifications settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_notifications_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_notifications_feature', 25 );
