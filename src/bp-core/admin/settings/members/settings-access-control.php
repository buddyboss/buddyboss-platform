<?php
/**
 * BuddyBoss Admin Settings - Members Access Controls.
 *
 * Registers the Access Controls section and field for the Members feature
 * (Member Connection panel) in the Settings 2.0 registry.
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
 * Register Access Controls section and field for Members (Connection Access).
 *
 * Called from bb-admin-settings-members.php after Member Connection panel
 * fields are registered. Fires a hook so Pro (or third-party) can register
 * additional fields in the same section.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_members_register_access_control_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Connection Access
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'member_connection',
		'connection_access',
		array(
			'title'       => __( 'Connection Access', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
			'help_url'    => '636002',
			'conditional' => array(
				'field' => 'bb_enable_member_connections',
				'value' => true,
			),
			'pro_notice'  => bb_admin_settings_get_pro_notice(
				array(
					'type'    => 'access_controls',
					'context' => 'section',
				)
			),
		)
	);

	// FIELD: Connection Request access control.
	bb_register_feature_field(
		'members',
		'member_connection',
		'connection_access',
		array(
			'name'               => 'bb-access-control-friends',
			'label'              => __( 'Connection Request', 'buddyboss' ),
			'type'               => 'access_control',
			'description'        => __( 'Select which members can send connection requests to others based on:', 'buddyboss' ),
			'threaded'           => true,
			'threaded_sub_label' => __( 'can send connection request to', 'buddyboss' ),
			'default'            => '',
			'pro_only'           => true,
			'order'              => 10,
			'sanitize_callback'  => function_exists( 'bb_sanitize_access_control_field' ) ? 'bb_sanitize_access_control_field' : 'bb_sanitize_access_control_fallback',
			'conditional'        => array(
				'field' => 'bb_enable_member_connections',
				'value' => true,
			),
		)
	);

	// FIELD: Admin notice (displayed once at the end of the section).
	bb_register_feature_field(
		'members',
		'member_connection',
		'connection_access',
		array(
			'name'              => 'bb-connection-access-control-notice',
			'label'             => '',
			'type'              => 'notice',
			'description'       => __( 'These settings do not apply to administrators or group messages.', 'buddyboss' ),
			'notice_type'       => 'info',
			'sanitize_callback' => '__return_empty_string',
			'order'             => 100,
			'conditional'       => array(
				'field' => 'bb_enable_member_connections',
				'value' => true,
			),
		)
	);

	/**
	 * Fires after the core Members access-control fields are registered.
	 *
	 * Pro or third-party plugins can hook here to register additional
	 * access-control fields in the same section.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_members_access_control_after_register_fields' );
}
