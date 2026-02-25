<?php
/**
 * BuddyBoss Admin Settings - Profile Image Panel.
 *
 * Registers sections and fields for the Profile Image side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Image panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_members_register_profile_image_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Avatar
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_image',
		'profile_avatar',
		array(
			'title'       => __( 'Profile Avatars', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Profile Avatar Type (BuddyBoss or WordPress).
	bb_register_feature_field(
		'members',
		'profile_image',
		'profile_avatar',
		array(
			'name'              => 'bp-profile-avatar-type',
			'label'             => __( 'Profile Avatars', 'buddyboss' ),
			'type'              => 'radio',
			'description'       => sprintf(
				/* translators: %s: Discussion settings link. */
				__( 'Select whether to use the BuddyBoss or WordPress avatar systems. You can manage WordPress avatars in the <a href="%s">Discussion</a> settings.', 'buddyboss' ),
				esc_url( admin_url( 'options-discussion.php' ) )
			),
			'default'           => bb_get_profile_avatar_type(),
			'sanitize_callback' => 'bb_members_sanitize_avatar_type',
			'options'           => array(
				array(
					'label' => __( 'WordPress', 'buddyboss' ),
					'value' => 'WordPress',
				),
				array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'value' => 'BuddyBoss',
				),
			),
			'order'             => 10,
		)
	);

	// FIELD: Upload Avatars (toggle, inverted — DB stores 1=disabled).
	// Grouped with Gravatars under a shared "Avatars" label.
	bb_register_feature_field(
		'members',
		'profile_image',
		'profile_avatar',
		array(
			'name'              => 'bp-disable-avatar-uploads',
			'label'             => __( 'Avatars', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Allow members to upload a profile avatar', 'buddyboss' ),
			'default'           => bp_disable_avatar_uploads(),
			'sanitize_callback' => 'intval',
			'invert_value'      => true,
			'conditional'       => array(
				'field' => 'bp-profile-avatar-type',
				'value' => 'BuddyBoss',
			),
			'group'             => 'avatar_settings_group',
			'order'             => 20,
		)
	);

	// FIELD: Enable Gravatars (sub-toggle, grouped with Upload Avatars).
	bb_register_feature_field(
		'members',
		'profile_image',
		'profile_avatar',
		array(
			'name'              => 'bp-enable-profile-gravatar',
			'label'             => '',
			'type'              => 'toggle',
			'description'       => __( 'Allow members to use Gravatars for profile avatars', 'buddyboss' ),
			'default'           => bp_enable_profile_gravatar(),
			'sanitize_callback' => 'intval',
			'conditional'       => array(
				'field' => 'bp-profile-avatar-type',
				'value' => 'BuddyBoss',
			),
			'group'             => 'avatar_settings_group',
			'order'             => 25,
		)
	);

	// FIELD: Default Profile Avatar (image_radio, depends on avatar type = BuddyBoss).
	bb_register_feature_field(
		'members',
		'profile_image',
		'profile_avatar',
		array(
			'name'              => 'bp-default-profile-avatar-type',
			'label'             => __( 'Default Profile Avatar', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => empty( _wp_image_editor_choose() )
				? sprintf(
					/* translators: 1: Profile settings link, 2: Imagick link. */
					__( 'Display name shows as one or two initials based on your %1$s. Select a default image for members without a profile avatar. Note: Your server needs %2$s installed to use the "Display Name" option.', 'buddyboss' ),
					'<a href="' . esc_url( bb_get_feature_settings_url( 'members', 'profile_name' ) ) . '">' . __( 'profile settings', 'buddyboss' ) . '</a>',
					'<a href="https://github.com/ImageMagick/ImageMagick" target="_blank">Imagick</a>'
				)
				: sprintf(
					/* translators: %s: Profile settings link. */
					__( 'Display name shows as one or two initials based on your %s. Select a default image for members without a profile avatar.', 'buddyboss' ),
					'<a href="' . esc_url( bb_get_feature_settings_url( 'members', 'profile_name' ) ) . '">' . __( 'profile settings', 'buddyboss' ) . '</a>'
				),
			'default'           => ( 'legacy' === bb_get_default_profile_avatar_type() ) ? 'buddyboss' : bb_get_default_profile_avatar_type(),
			'sanitize_callback' => 'bb_members_sanitize_default_avatar_type',
			'options'           => array(
				array(
					'label' => __( 'BuddyBoss', 'buddyboss' ),
					'value' => 'buddyboss',
					'image' => 'avatar-buddyboss',
				),
				array(
					'label' => __( 'Display Name', 'buddyboss' ),
					'value' => 'display-name',
					'image' => 'avatar-name',
				),
				array(
					'label' => __( 'Custom', 'buddyboss' ),
					'value' => 'custom',
					'image' => 'avatar-custom',
				),
			),
			'upload_config'     => array(
				'type'        => 'avatar',
				'object'      => 'user',
				'item_id'     => 0,
				'item_type'   => 'default',
				'url_getter'  => 'bb_get_default_custom_upload_profile_avatar',
				'label'       => __( 'Upload Custom Avatar', 'buddyboss' ),
				'help_text'   => '', // Injected at AJAX time via bb_members_enrich_avatar_upload_help_text() — avatar dimensions not available at registration.
				'conditional' => array(
					'value' => 'custom',
				),
			),
			'conditional'       => array(
				'field' => 'bp-profile-avatar-type',
				'value' => 'BuddyBoss',
			),
			'order'             => 40,
		)
	);

	/**
	 * Fires after Profile Avatar section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_avatar_fields' );

	// Cover image section is only available when cover image uploads are not globally disabled.
	// Note: Cannot use bp_is_active( 'xprofile', 'cover_image' ) here because the theme compat
	// sub-feature registration (bp_set_theme_compat_feature) fires at bp_loaded priority 12,
	// but feature settings registration fires at priority 5 — so bp_is_active() would always
	// return false. Instead, check the underlying option directly.
	if ( (bool) bp_get_option( 'bp-disable-cover-image-uploads', false ) ) {
		return;
	}

	// -------------------------------------------------------------------------
	// SECTION: Profile Covers
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_image',
		'profile_cover',
		array(
			'title'       => __( 'Profile Covers', 'buddyboss' ),
			'description' => '',
			'order'       => 20,
		)
	);

	// FIELD: Profile Cover Images (toggle, inverted — DB stores 1=disabled).
	bb_register_feature_field(
		'members',
		'profile_image',
		'profile_cover',
		array(
			'name'              => 'bp-disable-cover-image-uploads',
			'label'             => __( 'Profile Cover Images', 'buddyboss' ),
			'type'              => 'toggle',
			'description'       => __( 'Enable cover images for member profiles', 'buddyboss' ),
			'help_text'         => __( 'When enabled, members will be able to upload cover images in their profile settings.', 'buddyboss' ),
			'default'           => bp_disable_cover_image_uploads(),
			'sanitize_callback' => 'intval',
			'invert_value'      => true,
			'order'             => 10,
		)
	);

	// FIELD: Cover Image Sizes (Pro only, child_render with width/height selects).
	bb_register_feature_field(
		'members',
		'profile_image',
		'profile_cover',
		array(
			'name'        => 'bb-default-profile-cover-size',
			'label'       => __( 'Cover Image Sizes', 'buddyboss' ),
			'type'        => 'child_render',
			'description' => __( 'Changing the cover image size will reposition existing member images.', 'buddyboss' ),
			'pro_only'    => true,
			'conditional' => array(
				'field' => 'bp-disable-cover-image-uploads',
				'value' => false,
			),
			'order'       => 20,
			'fields'      => array(
				array(
					'name'              => 'bb-pro-cover-profile-width',
					'label'             => __( 'Width', 'buddyboss' ),
					'type'              => 'select',
					'default'           => function_exists( 'bb_get_pro_cover_profile_width' ) ? bb_get_pro_cover_profile_width() : 'default',
					'sanitize_callback' => 'bb_members_sanitize_cover_width',
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
					'name'              => 'bb-pro-cover-profile-height',
					'label'             => __( 'Height', 'buddyboss' ),
					'type'              => 'select',
					'default'           => function_exists( 'bb_get_pro_cover_profile_height' ) ? bb_get_pro_cover_profile_height() : 'small',
					'sanitize_callback' => 'bb_members_sanitize_cover_height',
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

	// FIELD: Default Profile Cover Image (image_radio, depends on cover enabled).
	bb_register_feature_field(
		'members',
		'profile_image',
		'profile_cover',
		array(
			'name'              => 'bp-default-profile-cover-type',
			'label'             => __( 'Default Profile Cover Image', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select which image should be used for members who haven\'t uploaded a profile cover image.', 'buddyboss' ),
			'default'           => bb_get_default_profile_cover_type(),
			'sanitize_callback' => 'bb_members_sanitize_default_cover_type',
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
			'upload_config'     => array(
				'type'        => 'cover',
				'object'      => 'user',
				'item_id'     => 0,
				'item_type'   => 'default',
				'url_getter'  => 'bb_get_default_custom_upload_profile_cover',
				'label'       => __( 'Upload Custom Cover', 'buddyboss' ),
				'help_text'   => '', // Injected at AJAX time via bb_members_enrich_cover_upload_help_text() — theme compat not available at registration.
				'conditional' => array(
					'value' => 'custom',
				),
			),
			'conditional'       => array(
				'field' => 'bp-disable-cover-image-uploads',
				'value' => false,
			),
			'order'             => 30,
		)
	);

	/**
	 * Fires after Profile Cover Image section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_image_fields' );
}
