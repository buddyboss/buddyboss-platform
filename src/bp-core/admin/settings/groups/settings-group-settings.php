<?php
/**
 * BuddyBoss Admin Settings - Group Settings Panel.
 *
 * Registers sections and fields for the Group Settings side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Group Settings panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_groups_register_settings_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Group Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_settings',
		'group_settings',
		array(
			'title'       => __( 'Group Settings', 'buddyboss-platform' ),
			'description' => '',
			'order'       => 10,
			'help_url'    => '636124',
		)
	);

	// FIELD: Group Creation.
	// `invert_value`: Legacy DB stores 1 = restricted. Toggle shows "Enable", so invert for display.
	// Raw DB value preserved for backward compatibility with `bp_restrict_group_creation()`.
	// Round-trip: toggle ON (Enable) → DB stores 0; toggle OFF (Disable) → DB stores 1.
	bb_register_feature_field(
		'groups',
		'group_settings',
		'group_settings',
		array(
			'name'              => 'bp_restrict_group_creation',
			'label'             => __( 'Group Creation', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable social group creation by all members', 'buddyboss-platform' ),
			'help_text'         => sprintf(
				/* translators: %s: Access Controls link. */
				__( 'Administrators can always create groups, regardless of this setting. You can configure who can create groups in %s.', 'buddyboss-platform' ),
				'<a href="' . esc_url( bb_get_feature_settings_url( 'groups', 'access_controls' ) ) . '">' . __( 'Access Controls', 'buddyboss-platform' ) . '</a>'
			),
			'default'           => bp_restrict_group_creation(),
			'sanitize_callback' => 'absint',
			'invert_value'      => true,
			'order'             => 10,
		)
	);

	// FIELD: Group Messages (conditional on messages active).
	// Note: Despite the option name "bp-disable-group-messages", the legacy UI treats
	// value 1 as "Allow group messages" (checked). No inversion is needed.
	if ( bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'groups',
			'group_settings',
			'group_settings',
			array(
				'name'              => 'bp-disable-group-messages',
				'label'             => __( 'Group Messages', 'buddyboss-platform' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow for sending group messages to group members', 'buddyboss-platform' ),
				'default'           => bp_disable_group_messages(),
				'sanitize_callback' => 'absint',
				'order'             => 30,
			)
		);
	}

	/**
	 * Fires after Group Settings section fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_groups_settings_after_settings_fields' );

	// -------------------------------------------------------------------------
	// SECTION: Subgroups
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'title'       => __( 'Subgroups', 'buddyboss-platform' ),
			'description' => '',
			'order'       => 20,
			'help_url'    => '636126',
		)
	);

	// FIELD: Hierarchies.
	bb_register_feature_field(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'name'              => 'bp-enable-group-hierarchies',
			'label'             => __( 'Hierarchies', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow groups to have subgroups', 'buddyboss-platform' ),
			'default'           => bp_enable_group_hierarchies(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Hide Subgroups (depends on hierarchies).
	bb_register_feature_field(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'name'              => 'bp-enable-group-hide-subgroups',
			'label'             => __( 'Hide Subgroups', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Hide subgroups from Groups Directory & Group Type Shortcode', 'buddyboss-platform' ),
			'default'           => bp_enable_group_hide_subgroups(),
			'sanitize_callback' => 'absint',
			'conditional'       => array(
				'field' => 'bp-enable-group-hierarchies',
				'value' => true,
			),
			'order'             => 20,
		)
	);

	// FIELD: Restrict Invitations (depends on hierarchies).
	bb_register_feature_field(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'name'              => 'bp-enable-group-restrict-invites',
			'label'             => __( 'Restrict Invitations', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Restrict subgroup invites to members of the parent group', 'buddyboss-platform' ),
			'help_text'         => __( 'Members must first be a member of the parent group prior to being invited to a subgroup', 'buddyboss-platform' ),
			'default'           => bp_enable_group_restrict_invites(),
			'sanitize_callback' => 'absint',
			'confirm_message'   => __( 'By enabling this option members that are already part of sub-groups and not the parent groups will automatically be removed from all sub-groups.', 'buddyboss-platform' ),
			'conditional'       => array(
				'field' => 'bp-enable-group-hierarchies',
				'value' => true,
			),
			'order'             => 30,
		)
	);

	/**
	 * Fires after Subgroups section fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_groups_settings_after_subgroups_fields' );
}
