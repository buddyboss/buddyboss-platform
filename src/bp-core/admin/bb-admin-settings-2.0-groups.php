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
 * Get WordPress roles as options array for checkbox/select fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return array Array of role options with 'label' and 'value' keys.
 */
function bb_get_wp_roles_options() {
	$roles   = wp_roles();
	$options = array();

	foreach ( $roles->roles as $role_slug => $role_data ) {
		$options[] = array(
			'label' => $role_data['name'],
			'value' => $role_slug,
		);
	}

	return $options;
}

/**
 * Sanitize toggle list field value.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 * @return array Sanitized array of toggle values.
 */
function bb_sanitize_toggle_list( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();
	foreach ( $value as $key => $val ) {
		$sanitized[ sanitize_key( $key ) ] = absint( $val );
	}

	return $sanitized;
}

/**
 * Sanitize toggle list array field value.
 *
 * Converts a toggle list (key => 0/1) to an array of enabled values.
 * This is used when the option stores an array of enabled item keys.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $value The value to sanitize.
 * @return array Array of enabled values.
 */
function bb_sanitize_toggle_list_array( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$enabled = array();
	foreach ( $value as $key => $val ) {
		if ( ! empty( $val ) ) {
			$enabled[] = sanitize_key( $key );
		}
	}

	return $enabled;
}

/**
 * Sanitize array input.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $input Input to sanitize.
 * @return array Sanitized array.
 */
function bb_sanitize_array( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}
	return array_map( 'sanitize_text_field', $input );
}

/**
 * Sanitize dimensions input (width/height).
 *
 * @since BuddyBoss 3.0.0
 *
 * @param mixed $input Input to sanitize.
 * @return array Sanitized dimensions.
 */
function bb_sanitize_dimensions( $input ) {
	if ( ! is_array( $input ) ) {
		return array();
	}
	return array_map( 'intval', $input );
}

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
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-users-three',
			),
			'category'           => 'community',
			'license_tier'       => 'free',
			'is_active_callback' => function () {
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

	// -------------------------------------------------------------------------
	// Section: Group Settings
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_settings',
		'group_settings_main',
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
		'group_settings_main',
		array(
			'name'              => 'bp_restrict_group_creation',
			'label'             => __( 'Group Creation', 'buddyboss' ),
			'toggle_label'      => __( 'Enable social group creation by all members', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => sprintf(
				/* translators: %s: Access Controls link */
				__( 'Administrators can always create groups, regardless of this setting. You can configure who can create groups in %s.', 'buddyboss' ),
				'<a href="#/settings/groups/access_controls">' . __( 'Access Controls', 'buddyboss' ) . '</a>'
			),
			'default'           => bp_get_option( 'bp_restrict_group_creation', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Subscriptions
	bb_register_feature_field(
		'groups',
		'group_settings',
		'group_settings_main',
		array(
			'name'              => 'bb_enable_group_subscriptions',
			'label'             => __( 'Subscriptions', 'buddyboss' ),
			'toggle_label'      => __( 'Allow members to subscribe to groups', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'When a member is subscribed to a group, they can receive notifications of new activity posts and discussions created in the group.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb_enable_group_subscriptions', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 20,
		)
	);

	// Field: Group Messages
	bb_register_feature_field(
		'groups',
		'group_settings',
		'group_settings_main',
		array(
			'name'              => 'bp-disable-group-messages',
			'label'             => __( 'Group Messages', 'buddyboss' ),
			'toggle_label'      => __( 'Allow for sending group messages to group members', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => '',
			'default'           => bp_get_option( 'bp-disable-group-messages', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 30,
		)
	);

	// -------------------------------------------------------------------------
	// Section: Subgroups
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'title'       => __( 'Subgroups', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		)
	);

	// Field: Hierarchies
	bb_register_feature_field(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'name'              => 'bp-enable-group-hierarchies',
			'label'             => __( 'Hierarchies', 'buddyboss' ),
			'toggle_label'      => __( 'Allow groups to have subgroups', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => '',
			'default'           => bp_get_option( 'bp-enable-group-hierarchies', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 10,
		)
	);

	// Field: Hide Subgroups
	bb_register_feature_field(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'name'              => 'bp-enable-group-hide-subgroups',
			'label'             => __( 'Hide Subgroups', 'buddyboss' ),
			'toggle_label'      => __( 'Hide subgroups from Groups Directory & Group Type Shortcode', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => '',
			'default'           => bp_get_option( 'bp-enable-group-hide-subgroups', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 20,
		)
	);

	// Field: Restrict Invitations
	bb_register_feature_field(
		'groups',
		'group_settings',
		'subgroups',
		array(
			'name'              => 'bp-enable-group-restrict-invites',
			'label'             => __( 'Restrict Invitations', 'buddyboss' ),
			'toggle_label'      => __( 'Restrict subgroup invites to members of the parent group', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Members must first be a member of the parent group prior to being invited to a subgroup', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-enable-group-restrict-invites', 0 ),
			'sanitize_callback' => 'intval',
			'order'             => 30,
		)
	);

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

	// Section: Group Avatar
	bb_register_feature_section(
		'groups',
		'group_images',
		'group_avatar',
		array(
			'title'       => __( 'Group Avatar', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Group Avatars toggle
	// Note: Option name is "disable" but UI shows "enable", so we invert the value
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_avatar',
		array(
			'name'              => 'bp-disable-group-avatar-uploads',
			'label'             => __( 'Group Avatars', 'buddyboss' ),
			'toggle_label'      => __( 'Enable avatars for groups', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'When enabled, group organizers will be able to upload avatars in the group\'s settings.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-disable-group-avatar-uploads', false ),
			'sanitize_callback' => 'wp_validate_boolean',
			'invert_value'      => true, // Toggle ON = save false (not disabled), Toggle OFF = save true (disabled)
			'order'             => 10,
		)
	);

	// Field: Default Group Avatar (visual radio cards)
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_avatar',
		array(
			'name'              => 'bp-default-group-avatar-type',
			'label'             => __( 'Default Group Avatar', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => '',
			'default'           => bp_get_option( 'bp-default-group-avatar-type', 'buddyboss' ),
			'options'           => array(
				array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'value' => 'buddyboss',
					'image' => 'avatar-buddyboss',
				),
				array(
					'label' => __( 'Group Name', 'buddyboss' ),
					'value' => 'group-name',
					'image' => 'avatar-name',
				),
				array(
					'label' => __( 'Custom', 'buddyboss' ),
					'value' => 'custom',
					'image' => 'avatar-custom',
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 20,
		)
	);

	// -------------------------------------------------------------------------
	// Section: Group Cover Image
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_images',
		'group_cover_image',
		array(
			'title'       => __( 'Group Cover Image', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		)
	);

	// Field: Group Cover Image toggle
	// Note: Option name is "disable" but UI shows "enable", so we invert the value
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover_image',
		array(
			'name'              => 'bp-disable-group-cover-image-uploads',
			'label'             => __( 'Group Cover Image', 'buddyboss' ),
			'toggle_label'      => __( 'Enable cover images for groups', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'When enabled, group organizers will be able to upload cover images in the group\'s settings.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-disable-group-cover-image-uploads', false ),
			'sanitize_callback' => 'wp_validate_boolean',
			'invert_value'      => true, // Toggle ON = save false (not disabled), Toggle OFF = save true (disabled)
			'order'             => 10,
		)
	);

	// Field: Default Group Cover Image (visual radio cards)
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover_image',
		array(
			'name'              => 'bp-default-group-cover-type',
			'label'             => __( 'Default Group Cover Image', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => '',
			'default'           => bp_get_option( 'bp-default-group-cover-type', 'buddyboss' ),
			'options'           => array(
				array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'value' => 'buddyboss',
					'image' => 'cover-buddyboss',
				),
				array(
					'label' => __( 'None', 'buddyboss' ),
					'value' => 'none',
					'image' => 'cover-none',
				),
				array(
					'label' => __( 'Custom', 'buddyboss' ),
					'value' => 'custom',
					'image' => 'cover-custom',
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 20,
		)
	);

	// Field: Cover Image Sizes (Width and Height stacked)
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover_image',
		array(
			'name'              => 'bb-pro-cover-group-dimensions',
			'label'             => __( 'Cover Image Sizes', 'buddyboss' ),
			'type'              => 'child_render',
			'description'       => __( 'Changing the size of your cover images will reposition those already uploaded by members.', 'buddyboss' ),
			'fields'            => array(
				array(
					'name'    => 'bb-pro-cover-group-width',
					'label'   => __( 'Width', 'buddyboss' ),
					'type'    => 'select',
					'default' => bp_get_option( 'bb-pro-cover-group-width', 'default' ),
					'options' => array(
						array(
							'label' => __( 'Default', 'buddyboss' ),
							'value' => 'default',
						),
						array(
							'label' => __( 'Full Width', 'buddyboss' ),
							'value' => 'full',
						),
					),
				),
				array(
					'name'    => 'bb-pro-cover-group-height',
					'label'   => __( 'Height', 'buddyboss' ),
					'type'    => 'select',
					'default' => bp_get_option( 'bb-pro-cover-group-height', 'small' ),
					'options' => array(
						array(
							'label' => __( 'Small', 'buddyboss' ),
							'value' => 'small',
						),
						array(
							'label' => __( 'Large', 'buddyboss' ),
							'value' => 'large',
						),
					),
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 30,
		)
	);

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
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Header Style (visual radio cards)
	// Note: Uses 'bb-pro-group-single-page-header-style' to match PRO option name
	bb_register_feature_field(
		'groups',
		'group_headers',
		'main',
		array(
			'name'              => 'bb-pro-group-single-page-header-style',
			'label'             => __( 'Header Style', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select the style of your group header. Group avatars and cover images will only be displayed if they are enabled. This setting does not apply to the App style.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb-pro-group-single-page-header-style', 'left' ),
			'options'           => array(
				array(
					'label' => __( 'Left', 'buddyboss' ),
					'value' => 'left',
					'image' => 'header-left-group',
				),
				array(
					'label' => __( 'Centered', 'buddyboss' ),
					'value' => 'centered',
					'image' => 'header-centered-group',
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 10,
			'pro_only'          => true,
			'license_tier'      => 'pro',
		)
	);

	// Field: Elements (stored as array of enabled elements)
	// Note: Uses 'bb-pro-group-single-page-headers-elements' to match PRO option name
	// Default: all enabled - array( 'group-type', 'group-activity', 'group-description', 'group-organizers', 'group-privacy' )
	$default_elements      = array( 'group-type', 'group-activity', 'group-description', 'group-organizers', 'group-privacy' );
	$enabled_elements      = bp_get_option( 'bb-pro-group-single-page-headers-elements', $default_elements );
	$enabled_elements      = is_array( $enabled_elements ) ? $enabled_elements : $default_elements;
	$elements_toggle_value = array(
		'group-type'        => in_array( 'group-type', $enabled_elements, true ) ? 1 : 0,
		'group-activity'    => in_array( 'group-activity', $enabled_elements, true ) ? 1 : 0,
		'group-description' => in_array( 'group-description', $enabled_elements, true ) ? 1 : 0,
		'group-organizers'  => in_array( 'group-organizers', $enabled_elements, true ) ? 1 : 0,
		'group-privacy'     => in_array( 'group-privacy', $enabled_elements, true ) ? 1 : 0,
	);

	bb_register_feature_field(
		'groups',
		'group_headers',
		'main',
		array(
			'name'              => 'bb-pro-group-single-page-headers-elements',
			'label'             => __( 'Elements', 'buddyboss' ),
			'type'              => 'toggle_list_array', // Stored as array of enabled values
			'description'       => __( 'Select which elements to show in your group headers.', 'buddyboss' ),
			'default'           => $elements_toggle_value,
			'options'           => array(
				array(
					'label' => __( 'Group Type', 'buddyboss' ),
					'value' => 'group-type',
				),
				array(
					'label' => __( 'Last Activity', 'buddyboss' ),
					'value' => 'group-activity',
				),
				array(
					'label' => __( 'Group Description', 'buddyboss' ),
					'value' => 'group-description',
				),
				array(
					'label' => __( 'Group Organizers', 'buddyboss' ),
					'value' => 'group-organizers',
				),
				array(
					'label' => __( 'Group Privacy', 'buddyboss' ),
					'value' => 'group-privacy',
				),
			),
			'sanitize_callback' => 'bb_sanitize_toggle_list_array',
			'order'             => 20,
			'pro_only'          => true,
			'license_tier'      => 'pro',
		)
	);

	// =========================================================================
	// SIDE PANEL: GROUP DIRECTORY
	// =========================================================================
	bb_register_side_panel(
		'groups',
		'group_directory',
		array(
			'title'    => __( 'Group Directory', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-list-view',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/groups/group-directories/',
			'order'    => 40,
		)
	);

	// Section: Group Directory
	bb_register_feature_section(
		'groups',
		'group_directory',
		'main',
		array(
			'title'       => __( 'Group Directory', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// Field: Enabled View(s)
	bb_register_feature_field(
		'groups',
		'group_directory',
		'main',
		array(
			'name'              => 'bp-group-layout-format',
			'label'             => __( 'Enabled View(s)', 'buddyboss' ),
			'type'              => 'select',
			'description'       => __( 'Display group directories in Grid View, List View, or allow toggling between both views.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp-group-layout-format', 'list_grid' ),
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
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 10,
		)
	);

	// Field: Default View
	bb_register_feature_field(
		'groups',
		'group_directory',
		'main',
		array(
			'name'              => 'bp-group-layout-default-format',
			'label'             => __( 'Default View', 'buddyboss' ),
			'type'              => 'radio',
			'description'       => '',
			'default'           => bp_get_option( 'bp-group-layout-default-format', 'grid' ),
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
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 20,
		)
	);

	// Field: Grid Style (PRO) - Visual preview cards
	// Note: Uses 'bb-pro-group-directory-layout-grid-style' to match PRO option name
	bb_register_feature_field(
		'groups',
		'group_directory',
		'main',
		array(
			'name'              => 'bb-pro-group-directory-layout-grid-style',
			'label'             => __( 'Grid Style', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select the grid layout style. Group avatars and cover images appear only if enabled.', 'buddyboss' ),
			'default'           => bp_get_option( 'bb-pro-group-directory-layout-grid-style', 'left' ),
			'options'           => array(
				array(
					'label' => __( 'Left', 'buddyboss' ),
					'value' => 'left',
					'image' => 'header-left-group', // Uses PreviewIcons component in React
				),
				array(
					'label' => __( 'Centered', 'buddyboss' ),
					'value' => 'centered', // Must match old option value 'centered' (not 'center')
					'image' => 'header-centered-group', // Uses PreviewIcons component in React
				),
			),
			'sanitize_callback' => 'sanitize_text_field',
			'order'             => 30,
			'pro_only'          => true,
			'license_tier'      => 'pro',
		)
	);

	// Field: Elements (PRO) - Toggle list for directory elements
	// Note: Uses 'bb-pro-group-directory-layout-elements' to match PRO option name
	// Elements: cover-images, avatars, group-privacy, group-type, last-activity, members, group-descriptions, join-buttons
	$default_directory_elements = array( 'cover-images', 'avatars', 'group-privacy', 'group-type', 'last-activity', 'members', 'group-descriptions', 'join-buttons' );
	$stored_directory_elements  = bp_get_option( 'bb-pro-group-directory-layout-elements', $default_directory_elements );
	$stored_directory_elements  = is_array( $stored_directory_elements ) ? $stored_directory_elements : $default_directory_elements;

	bb_register_feature_field(
		'groups',
		'group_directory',
		'main',
		array(
			'name'              => 'bb-pro-group-directory-layout-elements',
			'label'             => __( 'Elements', 'buddyboss' ),
			'type'              => 'toggle_list_array',
			'description'       => __( 'Select which elements show in your group directories. Cover images will only display in grid view and group descriptions will only display in list view.', 'buddyboss' ),
			'default'           => array(
				'cover-images'       => in_array( 'cover-images', $stored_directory_elements, true ) ? 1 : 0,
				'avatars'            => in_array( 'avatars', $stored_directory_elements, true ) ? 1 : 0,
				'group-privacy'      => in_array( 'group-privacy', $stored_directory_elements, true ) ? 1 : 0,
				'group-type'         => in_array( 'group-type', $stored_directory_elements, true ) ? 1 : 0,
				'last-activity'      => in_array( 'last-activity', $stored_directory_elements, true ) ? 1 : 0,
				'join-buttons'       => in_array( 'join-buttons', $stored_directory_elements, true ) ? 1 : 0,
			),
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
					'label' => __( 'Join Buttons', 'buddyboss' ),
					'value' => 'join-buttons',
				),
			),
			'sanitize_callback' => 'bb_sanitize_toggle_list_array',
			'order'             => 40,
			'pro_only'          => true,
			'license_tier'      => 'pro',
		)
	);

	// =========================================================================
	// SIDE PANEL: ACCESS CONTROLS
	// =========================================================================
	bb_register_side_panel(
		'groups',
		'access_controls',
		array(
			'title'    => __( 'Access Controls', 'buddyboss' ),
			'icon'     => array(
				'type' => 'dashicon',
				'slug' => 'dashicons-lock',
			),
			'help_url' => 'https://www.buddyboss.com/resources/docs/components/groups/access-controls/',
			'order'    => 50,
		)
	);

	// Section: Access Controls
	bb_register_feature_section(
		'groups',
		'access_controls',
		'main',
		array(
			'title'       => __( 'Access Controls', 'buddyboss' ),
			'description' => __( 'Configure who can create and manage groups.', 'buddyboss' ),
			'order'       => 10,
		)
	);

	// Field: Group Creation Roles
	bb_register_feature_field(
		'groups',
		'access_controls',
		'main',
		array(
			'name'              => 'bp_group_creation_roles',
			'label'             => __( 'Group Creation', 'buddyboss' ),
			'type'              => 'checkbox_list',
			'description'       => __( 'Select which roles can create groups.', 'buddyboss' ),
			'default'           => bp_get_option( 'bp_group_creation_roles', array() ),
			'options'           => bb_get_wp_roles_options(),
			'sanitize_callback' => 'bb_sanitize_array',
			'order'             => 10,
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
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-grid-four',
			),
			'order' => 100,
		)
	);

	bb_register_feature_nav_item(
		'groups',
		array(
			'id'    => 'group_types',
			'label' => __( 'Group Types', 'buddyboss' ),
			'route' => '/groups/types',
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-tag',
			),
			'order' => 110,
		)
	);

	bb_register_feature_nav_item(
		'groups',
		array(
			'id'    => 'group_navigation',
			'label' => __( 'Group Navigation', 'buddyboss' ),
			'route' => '/groups/navigation',
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-list',
			),
			'order' => 120,
		)
	);
}
add_action( 'bb_register_features', 'bb_admin_settings_2_0_register_groups_feature', 10 );
