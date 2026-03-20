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
 * Always registered regardless of friends component state.
 * The panel contains a toggle that controls friends component activation.
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

	// FIELD: Enable Member Connections (syncs friends component).
	bb_register_feature_field(
		'members',
		'member_connection',
		'connection_settings',
		array(
			'name'              => 'bb_enable_member_connections',
			'label'             => __( 'Member Connections', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow your members to connect with each other', 'buddyboss' ),
			'default'           => absint( bp_is_active( 'friends' ) ),
			'sanitize_callback' => 'absint',
			'order'             => 5,
		)
	);

	// FIELD: Messaging (require connection to message).
	// Always registered so it appears in search index; visibility controlled
	// by conditionals — React hides it when Messages component is off.
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
			'default'           => bp_is_active( 'messages' ) ? absint( bp_get_option( 'bp-force-friendship-to-message', 0 ) ) : 0,
			'sanitize_callback' => 'absint',
			'order'             => 10,
			'disabled'          => ! bp_is_active( 'messages' ),
			'conditional'       => array(
				'field' => 'bb_enable_member_connections',
				'value' => true,
			),
		)
	);

	// FIELD: Auto Follow (auto-follow on connection).
	// Always registered so it appears in search index; disabled when
	// Activity component or Follow feature is off.
	$auto_follow_available = bp_is_active( 'activity' ) && bp_is_activity_follow_active();

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
			'default'           => $auto_follow_available ? absint( bp_get_option( 'bb_enable_friends_auto_follow', 0 ) ) : 0,
			'sanitize_callback' => 'absint',
			'order'             => 20,
			'disabled'          => ! $auto_follow_available,
			'conditional'       => array(
				'field' => 'bb_enable_member_connections',
				'value' => true,
			),
		)
	);

	/**
	 * Fires after Connection Settings section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_connection_fields' );
}
