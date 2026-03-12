<?php
/**
 * BuddyBoss Admin Settings - Notification Types Panel.
 *
 * Registers sections and fields for the Notification Types side panel.
 * This includes the dynamic notification types table and messaging notification fields.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Notification Types panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_notifications_register_types_panel_fields() {

	// Only show notification types when using modern notification system.
	if ( bb_enabled_legacy_email_preference() ) {

		// -------------------------------------------------------------------------
		// SECTION: Legacy Notice
		// -------------------------------------------------------------------------
		bb_register_feature_section(
			'notifications',
			'notification_types',
			'notification_types_legacy',
			array(
				'title' => __( 'Notification Types', 'buddyboss' ),
				'order' => 10,
			)
		);

		bb_register_feature_field(
			'notifications',
			'notification_types',
			'notification_types_legacy',
			array(
				'name'    => '_bb_notification_types_legacy_notice',
				'label'   => '',
				'type'    => 'notice',
				'default' => __( 'Notification Types are not supported when using the legacy notifications system.', 'buddyboss' ),
				'order'   => 10,
			)
		);

		return;
	}

	// -------------------------------------------------------------------------
	// SECTION: Notification Types
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'notifications',
		'notification_types',
		'notification_types',
		array(
			'title'       => __( 'Notification Types', 'buddyboss' ),
			'description' => __( 'Choose which notifications are sent for site actions. Disabled notifications won\'t be generated. Members can manage them in Notification Preferences (email, web, app).', 'buddyboss' ),
			'order'       => 10,
		)
	);

	// FIELD: Notification Types (custom field type for React rendering).
	// Note: notification_groups data is built lazily in the AJAX handler
	// (BB_Admin_Settings_Ajax::bb_build_notification_groups) because
	// bb_register_notification_preferences() depends on component hooks
	// that haven't fired yet at bb_register_features time.
	$enabled_notification = bp_get_option( 'bb_enabled_notification', array() );

	bb_register_feature_field(
		'notifications',
		'notification_types',
		'notification_types',
		array(
			'name'              => 'bb_enabled_notification',
			'label'             => '',
			'type'              => 'notification_types',
			'default'           => $enabled_notification,
			'sanitize_callback' => 'bb_notifications_sanitize_types_noop',
			'full_width'        => true,
			'order'             => 10,
		)
	);

	/**
	 * Fires after Notification Types section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_notifications_types_after_settings_fields' );

	// -------------------------------------------------------------------------
	// SECTION: Messaging Notifications (conditional)
	// -------------------------------------------------------------------------
	if ( bp_is_active( 'messages' ) ) {

		bb_register_feature_section(
			'notifications',
			'notification_types',
			'messaging_notifications',
			array(
				'title'       => __( 'Messaging Notifications', 'buddyboss' ),
				'description' => '',
				'order'       => 20,
			)
		);

		// Show warning notice if pusher is enabled with specific conditions.
		if (
			! bb_hide_messages_from_notification_enabled() &&
			! bb_delay_email_notifications_enabled() &&
			function_exists( 'bb_pusher_is_enabled' ) &&
			bb_pusher_is_enabled() &&
			function_exists( 'bb_pusher_is_feature_enabled' ) &&
			true === bb_pusher_is_feature_enabled( 'live-messaging' )
		) {
			bb_register_feature_field(
				'notifications',
				'notification_types',
				'messaging_notifications',
				array(
					'name'    => '_bb_messaging_notification_warning',
					'label'   => '',
					'type'    => 'notice',
					'default' => sprintf(
						/* translators: %s: Live Messages link. */
						__( 'When using %s, we recommend enabling these settings to ensure the optimal experience for your members.', 'buddyboss' ),
						'<a href="' . esc_url(
							add_query_arg(
								array(
									'page' => 'bp-integrations',
									'tab'  => 'bb-pusher',
								),
								admin_url( 'admin.php' )
							)
						) . '">' . __( 'Live Messages', 'buddyboss' ) . '</a>'
					),
					'order'   => 10,
				)
			);
		}

		// FIELD: Hide From Notifications.
		bb_register_feature_field(
			'notifications',
			'notification_types',
			'messaging_notifications',
			array(
				'name'              => 'hide_message_notification',
				'label'             => __( 'Hide From Notifications', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Hide messages from notifications', 'buddyboss' ),
				'help_text'         => __( 'When enabled, notifications for group and private messages will not show in a member\'s list of notifications or be included in the count of unread notifications. However, notifications will still be sent externally (via email, web and/or app) and shown in a member\'s list of messages, as well as the count of unread messages.', 'buddyboss' ),
				'default'           => bb_hide_messages_from_notification_enabled(),
				'sanitize_callback' => 'intval',
				'order'             => 20,
			)
		);

		// Build delay time options.
		$delay_time_options = array();
		if ( function_exists( 'bb_notification_get_digest_cron_times' ) ) {
			foreach ( bb_notification_get_digest_cron_times() as $time ) {
				$delay_time_options[] = array(
					'label' => $time['label'],
					'value' => (int) $time['value'],
				);
			}
		}

		// FIELD: Delay Email Notifications.
		bb_register_feature_field(
			'notifications',
			'notification_types',
			'messaging_notifications',
			array(
				'name'                 => 'delay_email_notification',
				'label'                => __( 'Delay Email Notifications', 'buddyboss' ),
				'type'                 => 'toggle',
				// translators: %s: Delay time select control.
				'description'          => __( 'Delay email notifications for new messages', 'buddyboss' ),
				'help_text'            => __( 'When enabled, email notifications for new group and private messages will be delayed to allow time for members to read them on your site. After the delay, the emails will only be sent if the messages are still unread. If there are multiple unread messages in a conversation at the time of sending, they will be combined into a single email notification.', 'buddyboss' ),
				'default'              => bb_delay_email_notifications_enabled(),
				'sanitize_callback'    => 'intval',
				'description_controls' => array(
					array(
						'type'              => 'select',
						'name'              => 'time_delay_email_notification',
						'default'           => bb_get_delay_email_notifications_time(),
						'options'           => $delay_time_options,
						'sanitize_callback' => 'bb_notifications_sanitize_delay_time',
					),
				),
				'order'                => 30,
			)
		);

		/**
		 * Fires after Messaging Notifications section fields are registered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_notifications_messaging_after_settings_fields' );
	}
}
