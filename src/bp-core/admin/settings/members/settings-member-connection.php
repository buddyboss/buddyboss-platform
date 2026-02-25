<?php
/**
 * BuddyBoss Admin Settings - Member Connection Panel.
 *
 * Registers sections and fields for the Member Connection side panel.
 * This absorbs the legacy Connections tab (BP_Admin_Setting_Friends)
 * into the Members feature.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Member Connection panel sections and fields.
 *
 * Only called when bp_is_active( 'friends' ) — guarded in the main feature file.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_members_register_member_connection_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Connection Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'member_connection',
		'connection_settings',
		array(
			'title'       => __( 'Connection Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Member Connections (enable/disable member connections globally).
	bb_register_feature_field(
		'members',
		'member_connection',
		'connection_settings',
		array(
			'name'              => 'bb_enable_member_connections',
			'label'             => __( 'Member Connections', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow your members connect with each other', 'buddyboss' ),
			'default'           => (bool) bp_get_option( 'bb_enable_member_connections', true ),
			'sanitize_callback' => 'intval',
			'order'             => 5,
		)
	);

	// FIELD: Messaging (require connection to message).
	// Only show when Messages component is active.
	if ( bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'members',
			'member_connection',
			'connection_settings',
			array(
				'name'              => 'bp-force-friendship-to-message',
				'label'             => __( 'Messaging', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Require members to be connected before they can message each other', 'buddyboss' ),
				'help_text'         => __( 'This setting does not apply to administrators.', 'buddyboss' ),
				'default'           => bp_force_friendship_to_message(),
				'sanitize_callback' => 'bb_members_sanitize_force_friendship_to_message',
				'order'             => 10,
			)
		);
	}

	// FIELD: Auto Follow (auto-follow on connection).
	// Only show when Activity + Follow is active.
	if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
		bb_register_feature_field(
			'members',
			'member_connection',
			'connection_settings',
			array(
				'name'              => 'bb_enable_friends_auto_follow',
				'label'             => __( 'Auto Follow', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Automatically have members follow a member they connect with', 'buddyboss' ),
				'help_text'         => __( 'Requires member connections to be enabled.', 'buddyboss' ),
				'default'           => (bool) bp_get_option( 'bb_enable_friends_auto_follow', false ),
				'sanitize_callback' => 'intval',
				'order'             => 20,
			)
		);
	}

	/**
	 * Fires after Connection Settings section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_connection_fields' );
}
