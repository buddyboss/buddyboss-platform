<?php
/**
 * BuddyBoss Admin Settings - Group Images Panel.
 *
 * Registers sections and fields for the Group Images side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Group Images panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
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
			'title'       => __( 'Group Avatar', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Group Avatars (inverted — legacy stores "disable" but we display "enable").
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_avatar',
		array(
			'name'              => 'bp-disable-group-avatar-uploads',
			'label'             => __( 'Group Avatars', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow group organizers to upload an avatar', 'buddyboss' ),
			'default'           => bp_disable_group_avatar_uploads(),
			'sanitize_callback' => 'intval',
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
			'label'             => __( 'Default Group Avatar', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => '',
			'default'           => bb_get_default_group_avatar_type(),
			'sanitize_callback' => 'bb_groups_sanitize_avatar_type',
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
			'parent_field'      => 'bp-disable-group-avatar-uploads',
			'parent_value'      => false,
			'order'             => 20,
		)
	);

	/**
	 * Fires after Group Avatar section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
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
			'title'       => __( 'Group Cover Image', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		)
	);

	// FIELD: Group Cover Images (inverted — legacy stores "disable").
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover',
		array(
			'name'              => 'bp-disable-group-cover-image-uploads',
			'label'             => __( 'Group Cover Images', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow group organizers to upload a cover image', 'buddyboss' ),
			'default'           => bp_disable_group_cover_image_uploads(),
			'sanitize_callback' => 'intval',
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
			'label'             => __( 'Default Group Cover Image', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => '',
			'default'           => bb_get_default_group_cover_type(),
			'sanitize_callback' => 'bb_groups_sanitize_cover_type',
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
			'parent_field'      => 'bp-disable-group-cover-image-uploads',
			'parent_value'      => false,
			'order'             => 20,
		)
	);

	// FIELD: Cover Image Sizes (Pro only, child_render with width/height selects).
	bb_register_feature_field(
		'groups',
		'group_images',
		'group_cover',
		array(
			'name'         => 'bb-default-group-cover-size',
			'label'        => __( 'Cover Image Sizes', 'buddyboss' ),
			'type'         => 'child_render',
			'description'  => '',
			'pro_only'     => true,
			'parent_field' => 'bp-disable-group-cover-image-uploads',
			'parent_value' => false,
			'order'        => 30,
			'children'     => array(
				array(
					'name'              => 'bb-pro-cover-group-width',
					'label'             => __( 'Width', 'buddyboss' ),
					'type'              => 'select',
					'default'           => function_exists( 'bb_get_pro_cover_group_width' ) ? bb_get_pro_cover_group_width() : 'default',
					'sanitize_callback' => 'sanitize_text_field',
					'options'           => array(
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
					'name'              => 'bb-pro-cover-group-height',
					'label'             => __( 'Height', 'buddyboss' ),
					'type'              => 'select',
					'default'           => function_exists( 'bb_get_pro_cover_group_height' ) ? bb_get_pro_cover_group_height() : 'small',
					'sanitize_callback' => 'sanitize_text_field',
					'options'           => array(
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
		)
	);

	/**
	 * Fires after Group Cover Image section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_settings_after_images_fields' );
}
