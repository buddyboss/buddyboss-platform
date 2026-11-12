<?php
/**
 * BuddyBoss Admin Settings - Notification Types Panel.
 *
 * Registers sections and fields for the Notification Types side panel.
 * This includes the dynamic notification types table.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Notification Types panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return void
 */
function bboss_notifications_register_types_panel_fields() {

	// Only show notification types when using modern notification system.
	if ( function_exists( 'bboss_enabled_legacy_email_preference' ) && bboss_enabled_legacy_email_preference() ) {

		// -------------------------------------------------------------------------
		// SECTION: Legacy Notice
		// -------------------------------------------------------------------------
		bboss_register_feature_section(
			'notifications',
			'notification_types',
			'notification_types_legacy',
			array(
				'title'    => __( 'Notification Types', 'buddyboss-platform' ),
				'order'    => 10,
				'help_url' => '636146',
			)
		);

		bboss_register_feature_field(
			'notifications',
			'notification_types',
			'notification_types_legacy',
			array(
				'name'              => '_bb_notification_types_legacy_notice',
				'label'             => '',
				'type'              => 'notice',
				'description'       => __( 'Notification Types are not supported when using the legacy notifications system.', 'buddyboss-platform' ),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 10,
			)
		);

		return;
	}

	// -------------------------------------------------------------------------
	// SECTION: Notification Types
	// -------------------------------------------------------------------------
	bboss_register_feature_section(
		'notifications',
		'notification_types',
		'notification_types',
		array(
			'title'       => __( 'Notification Types', 'buddyboss-platform' ),
			'description' => __( 'Choose which notifications are sent for site actions. Disabled notifications won\'t be generated. Members can manage them in Notification Preferences (email, web, app).', 'buddyboss-platform' ),
			'order'       => 10,
			'help_url'    => '636146',
		)
	);

	// FIELD: Notification Types (custom field type for React rendering).
	// Note: notification_groups data is built lazily in the AJAX handler
	// (BB_Admin_Settings_Ajax::bb_build_notification_groups) because
	// bb_register_notification_preferences() depends on component hooks
	// that haven't fired yet at bb_register_features time.
	$enabled_notification = bboss_get_option( 'bb_enabled_notification', array() );

	bboss_register_feature_field(
		'notifications',
		'notification_types',
		'notification_types',
		array(
			'name'              => 'bb_enabled_notification',
			'label'             => '',
			'type'              => 'notification_types',
			'default'           => $enabled_notification,
			'sanitize_callback' => 'bboss_notifications_sanitize_types',
			'full_width'        => true,
			'order'             => 10,
		)
	);

	// FIELD: Developer Tutorial Notice (inside the same section, below notification types).
	bboss_register_feature_field(
		'notifications',
		'notification_types',
		'notification_types',
		array(
			'name'              => '_bb_notification_types_tutorial_notice',
			'label'             => '',
			'type'              => 'notice',
			'notice_type'       => 'plain',
			'description'       => sprintf(
				/* translators: %s: Tutorial link. */
				__( 'You can register your own notification types by following the steps in %s. Once registered, they\'ll be configurable in the options above.', 'buddyboss-platform' ),
				'<a href="' . esc_url( 'https://www.buddyboss.com/resources/dev-docs/app-development/extending-the-buddyboss-app-plugin/migrating-custom-notifications-to-modern-notifications-api/' ) . '" target="_blank" rel="noopener noreferrer">' . __( 'this tutorial', 'buddyboss-platform' ) . '</a>'
			),
			'sanitize_callback' => '__return_empty_string',
			'full_width'        => true,
			'order'             => 20,
		)
	);

	/**
	 * Fires after Notification Types section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_notifications_types_after_settings_fields' );
}
