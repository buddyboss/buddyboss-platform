<?php
/**
 * BuddyBoss Admin Settings - Group Images Panel.
 *
 * Registers sections and fields for the Group Images side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Group Images panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_groups_register_images_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Group Avatar
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_images',
		'group_avatar',
		array(
			'title'       => __( 'Group Avatar', 'buddyboss-platform' ),
			'description' => '',
			'order'       => 10,
			'help_url'    => '636128',
		)
	);

	// FIELD: Group Avatars.
	// `invert_value`: Legacy DB stores 1 = disabled. Toggle shows "Enable", so invert for display.
	// Raw DB value preserved for backward compatibility with `bp_disable_group_avatar_uploads()`.
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_avatar',
		array(
			'name'              => 'bp-disable-group-avatar-uploads',
			'label'             => __( 'Group Avatars', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable avatars for groups', 'buddyboss-platform' ),
			'help_text'         => __( 'When enabled, group organizers will be able to upload avatars in the group\'s settings.', 'buddyboss-platform' ),
			'default'           => bp_disable_group_avatar_uploads(),
			'sanitize_callback' => 'absint',
			'invert_value'      => true,
			'order'             => 10,
		)
	);

	// FIELD: Default Group Avatar (image_radio, depends on avatars enabled).
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_avatar',
		array(
			'name'              => 'bp-default-group-avatar-type',
			'label'             => __( 'Default Group Avatar', 'buddyboss-platform' ),
			'type'              => 'image_radio',
			'description'       => empty( _wp_image_editor_choose() )
				? sprintf(
					/* translators: %s: Imagick link. */
					__( 'Note: Your server needs %s installed to use the "Group Name" option.', 'buddyboss-platform' ),
					'<a href="https://github.com/ImageMagick/ImageMagick" target="_blank">Imagick</a>'
				)
				: '',
			'default'           => bb_get_default_group_avatar_type(),
			'sanitize_callback' => 'bb_groups_sanitize_avatar_type',
			'options'           => array(
				array(
					'label' => __( 'BuddyBoss', 'buddyboss-platform' ),
					'value' => 'buddyboss',
					'image' => 'avatar-buddyboss',
				),
				array(
					'label' => __( 'Group Name', 'buddyboss-platform' ),
					'value' => 'group-name',
					'image' => 'avatar-name',
				),
				array(
					'label' => __( 'Custom', 'buddyboss-platform' ),
					'value' => 'custom',
					'image' => 'avatar-custom',
				),
			),
			'upload_config'     => array(
				'type'        => 'avatar',
				'object'      => 'group',
				'item_id'     => 0,
				'item_type'   => 'default',
				'url_getter'  => 'bb_get_default_custom_upload_group_avatar',
				'label'       => __( 'Upload Custom Avatar', 'buddyboss-platform' ),
				/* translators: 1: avatar width in pixels, 2: avatar height in pixels. */
				'help_text'   => '', // Injected at AJAX time via bb_groups_enrich_avatar_upload_help_text() — avatar dimensions not available at registration.
				'conditional' => array(
					'value' => 'custom',
				),
			),
			'conditional'       => array(
				'field' => 'bp-disable-group-avatar-uploads',
				'value' => false,
			),
			'order'             => 20,
		)
	);

	/**
	 * Fires after Group Avatar section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_groups_settings_after_avatar_fields' );

	// -------------------------------------------------------------------------
	// SECTION: Group Cover Image
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_images',
		'group_cover',
		array(
			'title'       => __( 'Group Cover Image', 'buddyboss-platform' ),
			'description' => '',
			'order'       => 20,
			'help_url'    => '636133',
		)
	);

	// FIELD: Group Cover Image.
	// `invert_value`: Legacy DB stores 1 = disabled. Toggle shows "Enable", so invert for display.
	// Raw DB value preserved for backward compatibility with `bp_disable_group_cover_image_uploads()`.
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover',
		array(
			'name'              => 'bp-disable-group-cover-image-uploads',
			'label'             => __( 'Group Cover Image', 'buddyboss-platform' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable cover images for groups', 'buddyboss-platform' ),
			'help_text'         => __( 'When enabled, group organizers will be able to upload cover images in the group\'s settings.', 'buddyboss-platform' ),
			'default'           => bp_disable_group_cover_image_uploads(),
			'sanitize_callback' => 'absint',
			'invert_value'      => true,
			'order'             => 10,
		)
	);

	// FIELD: Default Group Cover Image (image_radio, depends on cover enabled).
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover',
		array(
			'name'              => 'bp-default-group-cover-type',
			'label'             => __( 'Default Group Cover Image', 'buddyboss-platform' ),
			'type'              => 'image_radio',
			'description'       => '',
			'default'           => bb_get_default_group_cover_type(),
			'sanitize_callback' => 'bb_groups_sanitize_cover_type',
			'options'           => array(
				array(
					'label' => __( 'BuddyBoss', 'buddyboss-platform' ),
					'value' => 'buddyboss',
					'image' => 'cover-buddyboss',
				),
				array(
					'label' => __( 'None', 'buddyboss-platform' ),
					'value' => 'none',
					'image' => 'cover-none',
				),
				array(
					'label' => __( 'Custom', 'buddyboss-platform' ),
					'value' => 'custom',
					'image' => 'cover-custom',
				),
			),
			'upload_config'     => array(
				'type'        => 'cover',
				'object'      => 'group',
				'item_id'     => 0,
				'item_type'   => 'default',
				'url_getter'  => 'bb_get_default_custom_upload_group_cover',
				'label'       => __( 'Upload Custom Cover', 'buddyboss-platform' ),
				'help_text'   => '', // help_text + dimensions both injected at AJAX time via bb_groups_enrich_cover_upload_help_text() — theme compat is not loaded at registration time and bb_attachments_get_default_custom_cover_image_dimensions() would fatal.
				'conditional' => array(
					'value' => 'custom',
				),
			),
			'conditional'       => array(
				'field' => 'bp-disable-group-cover-image-uploads',
				'value' => false,
			),
			'order'             => 20,
		)
	);

	// FIELD: Cover Image Width (Pro only, select, grouped with height).
	// Pro saves this value to an additional option for backward compatibility with older Pro versions.
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover',
		array(
			'name'              => 'bb-cover-group-width',
			'label'             => __( 'Cover Image Sizes', 'buddyboss-platform' ),
			'type'              => 'select',
			'description'       => '',
			'default'           => bb_get_group_cover_image_width(),
			'sanitize_callback' => 'bb_groups_sanitize_cover_width',
			'pro_only'          => true,
			'options'           => array(
				array(
					'label' => __( 'Default', 'buddyboss-platform' ),
					'value' => 'default',
				),
				array(
					'label' => __( 'Full Width', 'buddyboss-platform' ),
					'value' => 'full',
				),
			),
			'conditional'       => array(
				'field' => 'bp-disable-group-cover-image-uploads',
				'value' => false,
			),
			'group'             => array(
				'key'   => 'cover_image_sizes',
				'label' => __( 'Width', 'buddyboss-platform' ),
			),
			'order'             => 30,
		)
	);

	// FIELD: Cover Image Height (Pro only, select, grouped with width).
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover',
		array(
			'name'              => 'bb-cover-group-height',
			'label'             => '',
			'type'              => 'select',
			'description'       => __( 'Changing the size of your cover images will reposition those already uploaded by members.', 'buddyboss-platform' ),
			'default'           => bb_get_group_cover_image_height(),
			'sanitize_callback' => 'bb_groups_sanitize_cover_height',
			'pro_only'          => true,
			'options'           => array(
				array(
					'label' => __( 'Small', 'buddyboss-platform' ),
					'value' => 'small',
				),
				array(
					'label' => __( 'Large', 'buddyboss-platform' ),
					'value' => 'large',
				),
			),
			'conditional'       => array(
				'field' => 'bp-disable-group-cover-image-uploads',
				'value' => false,
			),
			'group'             => array(
				'key'   => 'cover_image_sizes',
				'label' => __( 'Height', 'buddyboss-platform' ),
			),
			'order'             => 40,
		)
	);

	/**
	 * Fires after Group Cover Image section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_groups_settings_after_images_fields' );
}
