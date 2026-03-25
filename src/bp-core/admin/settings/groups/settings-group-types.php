<?php
/**
 * BuddyBoss Admin Settings - Group Types Panel.
 *
 * Registers sections and fields for the Group Types side panel.
 * This panel uses a custom React screen (GroupTypeScreen.js), but
 * fields are registered here so descriptions come from PHP rather
 * than being hardcoded in JavaScript.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Group Types panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_groups_register_group_types_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Group Type Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_types',
		'group_type_settings',
		array(
			'title'       => __( 'Group Type Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Group Types toggle.
	// Legacy option name implies "disable" but value 1 means enabled — do not invert.
	bb_register_feature_field(
		'groups',
		'group_types',
		'group_type_settings',
		array(
			'name'              => 'bp-disable-group-type-creation',
			'label'             => __( 'Group Types', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable group types', 'buddyboss' ),
			'help_text'         => __( 'When enabled, group types allow you to better organize groups.', 'buddyboss' ),
			'default'           => (int) bp_disable_group_type_creation(),
			'sanitize_callback' => 'absint',
			'order'             => 10,
		)
	);

	// FIELD: Auto Membership Approval toggle.
	bb_register_feature_field(
		'groups',
		'group_types',
		'group_type_settings',
		array(
			'name'              => 'bp-enable-group-auto-join',
			'label'             => __( 'Auto Membership Approval', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow selected profile types to automatically join groups', 'buddyboss' ),
			'help_text'         => __( 'When a member requests to join a group their membership is automatically accepted.', 'buddyboss' ),
			'default'           => (int) bp_enable_group_auto_join(),
			'sanitize_callback' => 'absint',
			'order'             => 20,
		)
	);

	/**
	 * Fires after Group Types section fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_settings_after_group_types_fields' );
}
