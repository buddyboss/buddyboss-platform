<?php
/**
 * BuddyBoss Admin Settings - Group Directory Panel.
 *
 * Registers sections and fields for the Group Directory side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Group Directory panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_groups_register_directory_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Group Directory
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_directory',
		'group_directory',
		array(
			'title'       => __( 'Group Directory', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Enabled View(s).
	bb_register_feature_field(
		'groups',
		'group_directory',
		'group_directory',
		array(
			'name'              => 'bp-group-layout-format',
			'label'             => __( 'Enabled View(s)', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Display group directories in Grid View, List View, or allow toggling between both views.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-group-layout-format', 'grid' ),
			'sanitize_callback' => 'bb_groups_sanitize_layout_format',
			'options'           => array(
				array(
					'label' => __( 'Grid and List', 'buddyboss' ),
					'value' => 'list_grid',
				),
				array(
					'label' => __( 'Grid', 'buddyboss' ),
					'value' => 'grid',
				),
				array(
					'label' => __( 'List', 'buddyboss' ),
					'value' => 'list',
				),
			),
			'order'             => 10,
		)
	);

	// FIELD: Default View (conditional — only when both grid and list are enabled).
	bb_register_feature_field(
		'groups',
		'group_directory',
		'group_directory',
		array(
			'name'              => 'bp-group-layout-default-format',
			'label'             => __( 'Default View', 'buddyboss' ),
			'type'              => 'radio',
			'description'       => '',
			'default'           => bp_get_option( 'bp-group-layout-default-format', 'grid' ),
			'sanitize_callback' => 'bb_groups_sanitize_default_format',
			'options'           => array(
				array(
					'label' => __( 'Grid', 'buddyboss' ),
					'value' => 'grid',
				),
				array(
					'label' => __( 'List', 'buddyboss' ),
					'value' => 'list',
				),
			),
			'conditional'       => array(
				'field' => 'bp-group-layout-format',
				'value' => 'list_grid',
			),
			'order'             => 20,
		)
	);

	// FIELD: Grid Style (Pro only, image_radio).
	// Visible when layout includes grid view (list_grid or grid), hidden for list-only.
	bb_register_feature_field(
		'groups',
		'group_directory',
		'group_directory',
		array(
			'name'              => 'bb-group-directory-layout-grid-style',
			'label'             => __( 'Grid Style', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select the grid layout style. Group avatars and cover images appear only if enabled.', 'buddyboss' ),
			'default'           => function_exists( 'bb_get_group_directory_grid_style' ) ? bb_get_group_directory_grid_style() : 'left',
			'sanitize_callback' => 'bb_groups_sanitize_grid_style',
			'options'           => array(
				array(
					'label' => is_rtl() ? __( 'Right', 'buddyboss' ) : __( 'Left', 'buddyboss' ),
					'value' => 'left',
					'image' => 'header-left-group',
				),
				array(
					'label' => __( 'Centered', 'buddyboss' ),
					'value' => 'centered',
					'image' => 'header-centered-group',
				),
			),
			'pro_only'          => true,
			'conditional'       => array(
				'field' => 'bp-group-layout-format',
				'value' => array( 'list_grid', 'grid' ),
			),
			'order'             => 30,
		)
	);

	// FIELD: Elements (Pro only, toggle_list).
	bb_register_feature_field(
		'groups',
		'group_directory',
		'group_directory',
		array(
			'name'              => 'bb-group-directory-layout-elements',
			'label'             => __( 'Elements', 'buddyboss' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select which elements show in your group directories. Cover images will only display in grid view and group descriptions will only display in list view.', 'buddyboss' ),
			'default'           => array(),
			'sanitize_callback' => 'bb_groups_sanitize_toggle_list',
			'options'           => array(
				array(
					'label' => __( 'Cover Images', 'buddyboss' ),
					'value' => 'cover-images',
				),
				array(
					'label' => __( 'Avatars', 'buddyboss' ),
					'value' => 'avatars',
				),
				array(
					'label' => __( 'Group Privacy', 'buddyboss' ),
					'value' => 'group-privacy',
				),
				array(
					'label' => __( 'Group Type', 'buddyboss' ),
					'value' => 'group-type',
				),
				array(
					'label' => __( 'Last Activity', 'buddyboss' ),
					'value' => 'last-activity',
				),
				array(
					'label' => __( 'Members', 'buddyboss' ),
					'value' => 'members',
				),
				array(
					'label' => __( 'Group Descriptions', 'buddyboss' ),
					'value' => 'group-descriptions',
				),
				array(
					'label' => __( 'Join Buttons', 'buddyboss' ),
					'value' => 'join-buttons',
				),
			),
			'pro_only'          => true,
			'order'             => 40,
		)
	);

	/**
	 * Fires after Group Directory section fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_settings_after_directory_fields' );
}
