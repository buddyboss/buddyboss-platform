<?php
/**
 * BuddyBoss Admin Settings 2.0 - Groups Feature Registration
 *
 * Registers Groups feature with the new hierarchy:
 * Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Groups feature in Feature Registry.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_admin_settings_2_0_register_groups_feature() {
	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================
	bb_register_feature(
		'groups',
		array(
			'label'              => __( 'Groups', 'buddyboss' ),
			'description'        => __( 'Allow members to create and join social groups.', 'buddyboss' ),
			'icon'               => 'dashicons-groups',
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function() {
				return bp_is_active( 'groups' );
			},
			'settings_route'     => '/settings/groups',
			'order'              => 20,
		)
	);

	// =========================================================================
	// SIDE PANEL: GROUP SETTINGS
	// =========================================================================
	bb_register_side_panel(
		'groups',
		'group_settings',
		array(
			'title'      => __( 'Group Settings', 'buddyboss' ),
			'icon'       => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-admin-settings',
			),
			'help_url'   => 'https://www.buddyboss.com/resources/docs/components/groups/group-settings/',
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Section: Group Settings
	bb_register_feature_section(
		'groups',
		'group_settings',
		'main',
		array(
			'title'       => __( 'Group Settings', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Group Creation
	bb_register_feature_field(
		'groups',
		'group_settings',
		'main',
		array(
			'name'              => 'bp_restrict_group_creation',
			'label'             => __( 'Group Creation', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Restrict group creation to administrators only.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp_restrict_group_creation', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Group Messages (conditional)
	if ( bp_is_active( 'groups' ) && bp_is_active( 'messages' ) ) {
		bb_register_feature_field(
			'groups',
			'group_settings',
			'main',
			array(
				'name'              => 'bp-disable-group-messages',
				'label'             => __( 'Group Messages', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow group members to send messages to all group members.', 'buddyboss' ),
				'default'           => bp_get_option( 'bp-disable-group-messages', 0 ),
				'sanitize_callback' => 'intval',
				'order'             => 20,
			)
		);
	}

	// Field: Subscriptions (conditional)
	if ( bp_is_active( 'notifications' ) && ( bp_is_active( 'activity' ) || bp_is_active( 'forums' ) ) ) {
		bb_register_feature_field(
			'groups',
			'group_settings',
			'main',
			array(
				'name'              => 'bb_enable_group_subscriptions',
				'label'             => __( 'Subscriptions', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow members to subscribe to group updates.', 'buddyboss' ),
				'default'           => bp_get_option( 'bb_enable_group_subscriptions', 0 ),
				'sanitize_callback' => 'intval',
				'order'             => 30,
			)
		);
	}

	// =========================================================================
	// SIDE PANEL: GROUP IMAGES
	// =========================================================================
	bb_register_side_panel(
		'groups',
		'group_images',
		array(
			'title'    => __( 'Group Images', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-format-image',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/groups/group-images/',
			'order'    => 20,
		)
	);

	// Section: Group Images
	bb_register_feature_section(
		'groups',
		'group_images',
		'main',
		array(
			'title'       => __( 'Group Images', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Group Avatars
	bb_register_feature_field(
		'groups',
		'group_images',
		'main',
		array(
			'name'              => 'bp-disable-group-avatar-uploads',
			'label'             => __( 'Group Avatars', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow group organizers to upload custom avatars.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-disable-group-avatar-uploads', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Default Group Avatar
	bb_register_feature_field(
		'groups',
		'group_images',
		'main',
		array(
			'name'              => 'bp-default-group-avatar-type',
			'label'             => __( 'Default Group Avatar', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Select the default avatar style for groups.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-default-group-avatar-type', 'buddyboss' ),
			'options'           => array(
				array( 'label' => __( 'BuddyBoss', 'buddyboss' ), 'value' => 'buddyboss' ),
				array( 'label' => __( 'Custom', 'buddyboss' ), 'value' => 'custom' ),
				array( 'label' => __( 'Group Name', 'buddyboss' ), 'value' => 'group-name' ),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 20,
		)
	);

	// Field: Group Cover Images (conditional)
	if ( bp_is_active( 'groups', 'cover_image' ) ) {
		bb_register_feature_field(
			'groups',
			'group_images',
			'main',
			array(
				'name'              => 'bp-disable-group-cover-image-uploads',
				'label'             => __( 'Group Cover Images', 'buddyboss' ),
				'type'              => 'toggle',
				'description'       => __( 'Allow group organizers to upload cover images.', 'buddyboss' ),
				'default'           => bp_get_option( 'bp-disable-group-cover-image-uploads', 0 ),
				'sanitize_callback' => 'intval',
				'order'             => 30,
			)
		);

		bb_register_feature_field(
			'groups',
			'group_images',
			'main',
			array(
				'name'              => 'bp-default-group-cover-type',
				'label'             => __( 'Default Group Cover Image', 'buddyboss' ),
				'type'              => 'select',
				'description'       => __( 'Select the default cover image style for groups.', 'buddyboss' ),
				'default'           => bp_get_option( 'bp-default-group-cover-type', 'buddyboss' ),
				'options'           => array(
					array( 'label' => __( 'BuddyBoss', 'buddyboss' ), 'value' => 'buddyboss' ),
					array( 'label' => __( 'Custom', 'buddyboss' ), 'value' => 'custom' ),
				),
				'sanitize_callback' => 'sanitize_text_field',
				'order'             => 40,
			)
		);
	}

	// =========================================================================
	// SIDE PANEL: GROUP HEADERS
	// =========================================================================
	bb_register_side_panel(
		'groups',
		'group_headers',
		array(
			'title'    => __( 'Group Headers', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-welcome-widgets-menus',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/groups/group-headers/',
			'order'    => 30,
		)
	);

	// Section: Group Headers
	bb_register_feature_section(
		'groups',
		'group_headers',
		'main',
		array(
			'title'       => __( 'Group Headers', 'buddyboss' ),
			'description' => __( 'Configure the group header display options.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	// =========================================================================
	// SIDE PANEL: GROUP DIRECTORIES
	// =========================================================================
	bb_register_side_panel(
		'groups',
		'group_directories',
		array(
			'title'    => __( 'Group Directories', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-list-view',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/groups/group-directories/',
			'order'    => 40,
		)
	);

	// Section: Group Directories
	bb_register_feature_section(
		'groups',
		'group_directories',
		'main',
		array(
			'title'       => __( 'Group Directories', 'buddyboss' ),
			'description' => __( 'Configure group directory display settings.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	// =========================================================================
	// SIDE PANEL: GROUP HIERARCHIES
	// =========================================================================
	bb_register_side_panel(
		'groups',
		'group_hierarchies',
		array(
			'title'    => __( 'Group Hierarchies', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-networking',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/groups/group-hierarchies/',
			'order'    => 50,
		)
	);

	// Section: Group Hierarchies
	bb_register_feature_section(
		'groups',
		'group_hierarchies',
		'main',
		array(
			'title'       => __( 'Group Hierarchies', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Enable Hierarchies
	bb_register_feature_field(
		'groups',
		'group_hierarchies',
		'main',
		array(
			'name'              => 'bp-enable-group-hierarchies',
			'label'             => __( 'Hierarchies', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow groups to have parent-child relationships.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-enable-group-hierarchies', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Hide Subgroups
	bb_register_feature_field(
		'groups',
		'group_hierarchies',
		'main',
		array(
			'name'              => 'bp-enable-group-hide-subgroups',
			'label'             => __( 'Hide Subgroups', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Hide subgroups from the main group directory.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-enable-group-hide-subgroups', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 20,
		)
	);

	// Field: Restrict Invitations
	bb_register_feature_field(
		'groups',
		'group_hierarchies',
		'main',
		array(
			'name'              => 'bp-enable-group-restrict-invites',
			'label'             => __( 'Restrict Invitations', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Restrict invitations to subgroups to parent group members only.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-enable-group-restrict-invites', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 30,
		)
	);

	// =========================================================================
	// NAVIGATION ITEMS
	// =========================================================================
	bb_register_feature_nav_item(
		'groups',
		array(
			'id'    => 'all_groups',
			'label' => __( 'All Groups', 'buddyboss' ),
			'route' => '/groups/all',
			'icon'  => 'dashicons-list-view',
			'order' => 100,
		)
	);

	bb_register_feature_nav_item(
		'groups',
		array(
			'id'    => 'group_types',
			'label' => __( 'Group Types', 'buddyboss' ),
			'route' => '/groups/types',
			'icon'  => 'dashicons-tag',
			'order' => 110,
		)
	);

	bb_register_feature_nav_item(
		'groups',
		array(
			'id'    => 'group_navigation',
			'label' => __( 'Group Navigation', 'buddyboss' ),
			'route' => '/groups/navigation',
			'icon'  => 'dashicons-menu',
			'order' => 120,
		)
	);
}
add_action( 'bb_register_features', 'bb_admin_settings_2_0_register_groups_feature', 10 );
