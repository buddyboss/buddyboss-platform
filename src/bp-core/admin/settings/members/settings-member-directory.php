<?php
/**
 * BuddyBoss Admin Settings - Member Directory Panel.
 *
 * Registers sections and fields for the Member Directory side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Member Directory panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_members_register_member_directory_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Member Directory
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'member_directory',
		'member_directory',
		array(
			'title'       => __( 'Member Directory', 'buddyboss-platform' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Enabled View(s).
	bb_register_feature_field(
		'members',
		'member_directory',
		'member_directory',
		array(
			'name'              => 'bp-profile-layout-format',
			'label'             => __( 'Enabled View(s)', 'buddyboss-platform' ),
			'type'              => 'select',
			'description'       => __( 'Display member directories in grid view, list view, or allow toggling between both views.', 'buddyboss-platform' ),
			'default'           => bp_get_option( 'bp-profile-layout-format', 'list_grid' ),
			'sanitize_callback' => 'bb_members_sanitize_layout_format',
			'options'           => array(
				array(
					'label' => __( 'Grid and List', 'buddyboss-platform' ),
					'value' => 'list_grid',
				),
				array(
					'label' => __( 'Grid', 'buddyboss-platform' ),
					'value' => 'grid',
				),
				array(
					'label' => __( 'List', 'buddyboss-platform' ),
					'value' => 'list',
				),
			),
			'order'             => 10,
		)
	);

	// FIELD: Default View (conditional — only when both grid and list are enabled).
	bb_register_feature_field(
		'members',
		'member_directory',
		'member_directory',
		array(
			'name'              => 'bp-profile-layout-default-format',
			'label'             => __( 'Default View', 'buddyboss-platform' ),
			'type'              => 'radio',
			'description'       => '',
			'default'           => bp_profile_layout_default_format( 'grid' ),
			'sanitize_callback' => 'bb_members_sanitize_default_format',
			'options'           => array(
				array(
					'label' => __( 'Grid', 'buddyboss-platform' ),
					'value' => 'grid',
				),
				array(
					'label' => __( 'List', 'buddyboss-platform' ),
					'value' => 'list',
				),
			),
			'conditional'       => array(
				'field' => 'bp-profile-layout-format',
				'value' => 'list_grid',
			),
			'order'             => 20,
		)
	);

	// FIELD: Elements (Pro only, toggle_list).
	// Static options hardcoded here so the field renders with PRO badges when Pro is disabled.
	// When Pro is active, Pro's enrichment filter overrides options with dynamic bp-hide states.
	bb_register_feature_field(
		'members',
		'member_directory',
		'member_directory',
		array(
			'name'              => 'bb-member-directory-elements',
			'label'             => __( 'Elements', 'buddyboss-platform' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select which elements to show in your member directories.', 'buddyboss-platform' ),
			'default'           => array(),
			'sanitize_callback' => 'bb_members_sanitize_toggle_list',
			'options'           => array(
				array(
					'label' => __( 'Online Status', 'buddyboss-platform' ),
					'value' => 'online-status',
				),
				array(
					'label' => __( 'Profile Type', 'buddyboss-platform' ),
					'value' => 'profile-type',
				),
				array(
					'label' => __( 'Followers', 'buddyboss-platform' ),
					'value' => 'followers',
				),
				array(
					'label' => __( 'Last Active', 'buddyboss-platform' ),
					'value' => 'last-active',
				),
				array(
					'label' => __( 'Joined Date', 'buddyboss-platform' ),
					'value' => 'joined-date',
				),
			),
			'pro_only'          => true,
			'order'             => 30,
		)
	);

	// FIELD: Profile Actions (Pro only, toggle_list).
	// Static options hardcoded here so the field renders with PRO badges when Pro is disabled.
	// When Pro is active, Pro's enrichment filter overrides options with dynamic bp-hide states.
	bb_register_feature_field(
		'members',
		'member_directory',
		'member_directory',
		array(
			'name'              => 'bb-member-profile-actions',
			'label'             => __( 'Profile Actions', 'buddyboss-platform' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select which profile actions to enable in your member directories.', 'buddyboss-platform' ),
			'default'           => array(),
			'sanitize_callback' => 'bb_members_sanitize_toggle_list',
			'options'           => array(
				array(
					'label' => __( 'Follow', 'buddyboss-platform' ),
					'value' => 'follow',
				),
				array(
					'label' => __( 'Connect', 'buddyboss-platform' ),
					'value' => 'connect',
				),
				array(
					'label' => __( 'Send Message', 'buddyboss-platform' ),
					'value' => 'message',
				),
			),
			'pro_only'          => true,
			'order'             => 40,
		)
	);

	// FIELD: Primary Action (Pro only, select).
	// Static options hardcoded here so the field renders with PRO badges when Pro is disabled.
	// When Pro is active, Pro's enrichment filter overrides options with dynamic values.
	bb_register_feature_field(
		'members',
		'member_directory',
		'member_directory',
		array(
			'name'              => 'bb-member-profile-primary-action',
			'label'             => __( 'Primary Action', 'buddyboss-platform' ),
			'type'              => 'select',
			'description'       => __( 'Select which profile action to show as a primary button. The remaining enabled profile actions will be shown as secondary buttons underneath.', 'buddyboss-platform' ),
			'default'           => function_exists( 'bb_get_member_directory_primary_action' ) ? bb_get_member_directory_primary_action() : '',
			'sanitize_callback' => 'sanitize_key',
			'options'           => array(
				array(
					'label' => __( 'None', 'buddyboss-platform' ),
					'value' => '',
				),
				array(
					'label' => __( 'Follow', 'buddyboss-platform' ),
					'value' => 'follow',
				),
				array(
					'label' => __( 'Connect', 'buddyboss-platform' ),
					'value' => 'connect',
				),
				array(
					'label' => __( 'Send Message', 'buddyboss-platform' ),
					'value' => 'message',
				),
			),
			'pro_only'          => true,
			'order'             => 50,
		)
	);

	/**
	 * Fires after Member Directory section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_members_settings_after_directory_fields' );
}
