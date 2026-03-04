<?php
/**
 * BuddyBoss Admin Settings - Messages Feature Registration.
 *
 * Registers the Messages feature in the Feature Registry and loads
 * all Messages settings (side panels, sections, fields).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Messages feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_messages_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'messages',
		array(
			'label'              => __( 'Private Messaging', 'buddyboss' ),
			'description'        => __( 'Allow members to send private messages to an individual or to a group.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-chats-circle',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'messages' );
			},
			'settings_route'     => '/settings/messages',
			'order'              => 50,
		)
	);

	// When messages is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on message functions that aren't loaded.
	if ( ! bp_is_active( 'messages' ) ) {
		return;
	}

	// Load settings sub-files only when messages is active.
	require_once __DIR__ . '/settings/messages/callbacks.php';
	require_once __DIR__ . '/settings/messages/settings-access-control.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Messaging Notifications (default).
	bb_register_side_panel(
		'messages',
		'messaging_notifications',
		array(
			'title'      => __( 'Messaging Notifications', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-bell-ringing',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 125952,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Access Controls.
	bb_register_side_panel(
		'messages',
		'access_controls',
		array(
			'title' => __( 'Access Controls', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-lock-simple',
			),
			'order' => 20,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// -------------------------------------------------------------------------
	// SECTION: MESSAGING NOTIFICATIONS
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'messages',
		'messaging_notifications',
		'messaging_notifications',
		array(
			'title'       => __( 'Messaging Notifications', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Hide From Notifications (Toggle).
	// Only register when notifications component is active — the toggle hides
	// messages from the notification list, which doesn't exist without it.
	if ( bp_is_active( 'notifications' ) ) {
		bb_register_feature_field(
			'messages',
			'messaging_notifications',
			'messaging_notifications',
			array(
				'name'              => 'hide_message_notification',
				'label'             => __( 'Hide From Notifications', 'buddyboss' ),
				'type'              => 'toggle',
				'toggle_label'      => __( 'Hide messages from notifications', 'buddyboss' ),
				'description'       => __( 'When enabled, notifications for group and private messages will not appear in a member\'s notification list or count toward unread notifications. However, they will still be sent externally (email, web, or app) and shown in the member\'s message list, including the unread message count.', 'buddyboss' ),
				'default'           => (bool) bp_get_option( 'hide_message_notification', 1 ),
				'sanitize_callback' => 'absint',
				'order'             => 10,
			)
		);
	}

	// FIELD: Delay Email Notifications (Toggle).
	bb_register_feature_field(
		'messages',
		'messaging_notifications',
		'messaging_notifications',
		array(
			'name'              => 'delay_email_notification',
			'label'             => __( 'Delay Email Notifications', 'buddyboss' ),
			'type'              => 'toggle',
			'toggle_label'      => __( 'Delay email notifications for new messages', 'buddyboss' ),
			'description'       => __( 'When enabled, email notifications for new group and private messages will be delayed, giving members time to read them on your site. After the delay, emails are sent only if the messages remain unread. Multiple unread messages in the same conversation will be combined into a single email notification.', 'buddyboss' ),
			'default'           => (bool) bp_get_option( 'delay_email_notification', 1 ),
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	// FIELD: Delay Message Notifications (Select - child of delay toggle).
	$delay_times   = bb_notification_get_digest_cron_times();
	$delay_options = array();

	foreach ( $delay_times as $time ) {
		$delay_options[] = array(
			'label' => $time['label'],
			'value' => (string) $time['value'],
		);
	}

	bb_register_feature_field(
		'messages',
		'messaging_notifications',
		'messaging_notifications',
		array(
			'name'              => 'time_delay_email_notification',
			'label'             => __( 'Delay Message Notifications', 'buddyboss' ),
			'type'              => 'select',
			'description'       => '',
			'options'           => $delay_options,
			'default'           => (string) bp_get_option( 'time_delay_email_notification', 15 ),
			'sanitize_callback' => 'bb_messages_sanitize_delay_time',
			'order'             => 30,
			'parent_field'      => 'delay_email_notification',
		)
	);

	// Panel 2: Access Controls.
	bb_messages_register_access_control_fields();

	/**
	 * Fires after all Messages settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_messages_after_register_settings_fields' );

}

add_action( 'bb_register_features', 'bb_admin_settings_register_messages_feature', 20 );
