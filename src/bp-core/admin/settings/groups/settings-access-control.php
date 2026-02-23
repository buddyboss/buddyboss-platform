<?php
/**
 * BuddyBoss Admin Settings - Groups Access Controls.
 *
 * Registers the Access Controls side panel, section, and fields for the
 * Groups feature in the Settings 2.0 registry.
 *
 * All access-control logic lives in this file so it can be easily
 * extracted to Pro in the future. Pro populates the actual data
 * (types, options) via PHP filters.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Access Controls side panel, section, and fields for Groups.
 *
 * Called from bb-admin-settings-groups.php after all other panels are
 * registered. Fires a hook so Pro (or third-party) can register
 * additional fields in the same panel.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_groups_register_access_control_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Member Access Controls.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'title' => __( 'Member Access Controls', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Create Groups access control.
	bb_register_feature_field(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'name'              => 'bb-access-control-create-groups',
			'label'             => __( 'Create Groups', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can create groups based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 10,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	// FIELD: Join Groups access control.
	bb_register_feature_field(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'name'              => 'bb-access-control-join-groups',
			'label'             => __( 'Join Groups', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can join public groups or request to join private groups based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 20,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	// FIELD: Admin notice (displayed once at the end of the section).
	bb_register_feature_field(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'name'        => 'bb-groups-access-control-notice',
			'label'       => '',
			'type'        => 'notice',
			'description' => __( 'These settings do not apply to administrators.', 'buddyboss' ),
			'notice_type' => 'info',
			'order'       => 100,
		)
	);

	/**
	 * Fires after the core Groups access-control fields are registered.
	 *
	 * Pro or third-party plugins can hook here to register additional
	 * access-control fields in the same side panel.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_access_control_after_register_fields' );
}
