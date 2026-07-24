<?php
/**
 * BuddyBoss Admin Settings - Profile Headers Panel.
 *
 * Registers sections and fields for the Profile Headers side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Headers panel sections and fields.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_members_register_profile_headers_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Headers
	//
	// All fields in this section are pro_only (Header Style image_radio +
	// Elements toggle_list). Mirror the Member Access Controls pattern
	// (`groups/settings-access-control.php`) and surface a section-level
	// "UPGRADE PRO" badge in the section header so the gated state is
	// visible at the section level, not only per-row.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'title'       => __( 'Profile Headers', 'buddyboss-platform' ),
			'description' => '',
			'order'       => 10,
			'pro_notice'  => bb_admin_settings_get_pro_notice(
				array(
					'type'    => 'profile_headers',
					'context' => 'section',
				)
			),
		)
	);

	// FIELD: Header Style (Pro only, image_radio).
	bb_register_feature_field(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'name'              => 'bb-profile-headers-layout-style',
			'label'             => __( 'Header Style', 'buddyboss-platform' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select the style of your profile headers. Profile cover images will only be displayed if they are enabled.', 'buddyboss-platform' ),
			'default'           => function_exists( 'bb_get_profile_header_style' ) ? bb_get_profile_header_style() : 'left',
			'sanitize_callback' => 'bb_members_sanitize_header_style',
			'options'           => array(
				array(
					'label' => is_rtl() ? __( 'Right', 'buddyboss-platform' ) : __( 'Left', 'buddyboss-platform' ),
					'value' => 'left',
					'image' => 'header-left-profile',
				),
				array(
					'label' => __( 'Centered', 'buddyboss-platform' ),
					'value' => 'centered',
					'image' => 'header-centered-profile',
				),
			),
			'pro_only'          => true,
			'order'             => 10,
		)
	);

	// FIELD: Elements (Pro only, toggle_list).
	// Static options hardcoded here so the field renders with PRO badges when Pro is disabled.
	// When Pro is active, Pro's enrichment filter overrides options with dynamic bp-hide states.
	bb_register_feature_field(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'name'              => 'bb-profile-headers-layout-elements',
			'label'             => __( 'Elements', 'buddyboss-platform' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select which elements to show in your profile headers.', 'buddyboss-platform' ),
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
					'label' => __( 'Member Handle', 'buddyboss-platform' ),
					'value' => 'member-handle',
				),
				array(
					'label' => __( 'Joined Date', 'buddyboss-platform' ),
					'value' => 'joined-date',
				),
				array(
					'label' => __( 'Last Active', 'buddyboss-platform' ),
					'value' => 'last-active',
				),
				array(
					'label' => __( 'Followers', 'buddyboss-platform' ),
					'value' => 'followers',
				),
				array(
					'label' => __( 'Following', 'buddyboss-platform' ),
					'value' => 'following',
				),
				array(
					'label' => __( 'Social Networks', 'buddyboss-platform' ),
					'value' => 'social-networks',
				),
			),
			'pro_only'          => true,
			'order'             => 20,
		)
	);

	/**
	 * Fires after Profile Headers section fields are registered.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	do_action( 'bb_members_settings_after_headers_fields' );
}
