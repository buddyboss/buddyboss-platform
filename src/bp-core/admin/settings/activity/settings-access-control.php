<?php
/**
 * BuddyBoss Admin Settings - Activity Access Controls.
 *
 * Registers the Access Controls side panel, section, and field for the
 * Activity feature in the Settings 2.0 registry.
 *
 * All access-control logic lives in this file so it can be easily
 * extracted to Pro in the future. Pro populates the actual data
 * (types, options) via PHP filters.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Access Controls side panel, section, and field for Activity.
 *
 * Called from bb-admin-settings-activity.php after all other panels are
 * registered. Fires a hook so Pro (or third-party) can register
 * additional fields in the same panel.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_activity_register_access_control_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Member Access Controls.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'access_controls',
		'member_access_controls',
		array(
			'title'      => __( 'Member Access Controls', 'buddyboss' ),
			'order'      => 10,
			'help_url'   => '638203',
			'pro_notice' => bb_admin_settings_get_pro_notice(
				array(
					'type'    => 'access_controls',
					'context' => 'section',
				)
			),
		)
	);

	// FIELD: Activity Posts access control.
	bb_register_feature_field(
		'activity',
		'access_controls',
		'member_access_controls',
		array(
			'name'              => 'bb-access-control-create-activity',
			'label'             => __( 'Activity Posts', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can create activity posts based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 10,
			'sanitize_callback' => function_exists( 'bb_sanitize_access_control_field' ) ? 'bb_sanitize_access_control_field' : 'bb_sanitize_access_control_fallback',
		)
	);

	// FIELD: Admin notice (displayed once at the end of the section).
	bb_register_feature_field(
		'activity',
		'access_controls',
		'member_access_controls',
		array(
			'name'        => 'bb-activity-access-control-notice',
			'label'       => '',
			'type'        => 'notice',
			'description' => __( 'These settings do not apply to administrators.', 'buddyboss' ),
			'notice_type' => 'info',
			'order'       => 100,
		)
	);

	/**
	 * Fires after the core Activity access-control fields are registered.
	 *
	 * Pro or third-party plugins can hook here to register additional
	 * access-control fields in the same side panel.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_activity_access_control_after_register_fields' );
}

