<?php
/**
 * BuddyBoss Admin Settings - Notification Types Panel.
 *
 * Registers sections and fields for the Notification Types side panel.
 * This includes the dynamic notification types table.
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
 *
 * @return void
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
				'name'              => '_bb_notification_types_legacy_notice',
				'label'             => '',
				'type'              => 'notice',
				'description'       => __( 'Notification Types are not supported when using the legacy notifications system.', 'buddyboss' ),
				'sanitize_callback' => '__return_empty_string',
				'order'             => 10,
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
			'sanitize_callback' => 'bb_notifications_sanitize_types',
			'full_width'        => true,
			'order'             => 10,
		)
	);

	// FIELD: Developer Tutorial Notice (inside the same section, below notification types).
	bb_register_feature_field(
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
				__( 'You can register your own notification types by following the steps in %s. Once registered, they\'ll be configurable in the options above.', 'buddyboss' ),
				'<a href="' . esc_url( 'https://www.buddyboss.com/resources/dev-docs/app-development/extending-the-buddyboss-app-plugin/migrating-custom-notifications-to-modern-notifications-api/' ) . '" target="_blank" rel="noopener noreferrer">' . __( 'this tutorial', 'buddyboss' ) . '</a>'
			),
			'sanitize_callback' => '__return_empty_string',
			'full_width'        => true,
			'order'             => 20,
		)
	);

	/**
	 * Fires after Notification Types section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_notifications_types_after_settings_fields' );
}
