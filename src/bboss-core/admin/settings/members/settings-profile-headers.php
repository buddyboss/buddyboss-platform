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
function bboss_members_register_profile_headers_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Headers
	//
	// All fields in this section are pro_only (Header Style image_radio +
	// Elements toggle_list). Mirror the Member Access Controls pattern
	// (`groups/settings-access-control.php`) and surface a section-level
	// "UPGRADE LAUNCH" badge in the section header so the gated state is
	// visible at the section level, not only per-row.
	// -------------------------------------------------------------------------
	bboss_register_feature_section(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'title'       => __( 'Profile Headers', 'buddyboss-community-social-network-groups-forums-messaging' ),
			'description' => '',
			'order'       => 10,
			'pro_notice'  => bboss_admin_settings_get_pro_notice(
				array(
					'type'    => 'profile_headers',
					'context' => 'section',
				)
			),
		)
	);

	// FIELD: Header Style (Pro only, image_radio).
	bboss_register_feature_field(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'name'              => 'bb-profile-headers-layout-style',
			'label'             => __( 'Header Style', 'buddyboss-community-social-network-groups-forums-messaging' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select the style of your profile headers. Profile cover images will only be displayed if they are enabled.', 'buddyboss-community-social-network-groups-forums-messaging' ),
			'default'           => function_exists( 'bb_get_profile_header_style' ) ? bb_get_profile_header_style() : 'left',
			'sanitize_callback' => 'bboss_members_sanitize_header_style',
			'options'           => array(
				array(
					'label' => is_rtl() ? __( 'Right', 'buddyboss-community-social-network-groups-forums-messaging' ) : __( 'Left', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'left',
					'image' => 'header-left-profile',
				),
				array(
					'label' => __( 'Centered', 'buddyboss-community-social-network-groups-forums-messaging' ),
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
	bboss_register_feature_field(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'name'              => 'bb-profile-headers-layout-elements',
			'label'             => __( 'Elements', 'buddyboss-community-social-network-groups-forums-messaging' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select which elements to show in your profile headers.', 'buddyboss-community-social-network-groups-forums-messaging' ),
			'default'           => array(),
			'sanitize_callback' => 'bboss_members_sanitize_toggle_list',
			'options'           => array(
				array(
					'label' => __( 'Online Status', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'online-status',
				),
				array(
					'label' => __( 'Profile Type', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'profile-type',
				),
				array(
					'label' => __( 'Member Handle', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'member-handle',
				),
				array(
					'label' => __( 'Joined Date', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'joined-date',
				),
				array(
					'label' => __( 'Last Active', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'last-active',
				),
				array(
					'label' => __( 'Followers', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'followers',
				),
				array(
					'label' => __( 'Following', 'buddyboss-community-social-network-groups-forums-messaging' ),
					'value' => 'following',
				),
				array(
					'label' => __( 'Social Networks', 'buddyboss-community-social-network-groups-forums-messaging' ),
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
