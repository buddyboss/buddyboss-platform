<?php
/**
 * BuddyBoss Admin Settings - Group Headers Panel.
 *
 * Registers sections and fields for the Group Headers side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Group Headers panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_groups_register_headers_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Group Headers
	//
	// All fields in this section are pro_only. Mirror the Member Access
	// Controls / Profile Headers pattern and surface a section-level
	// "UPGRADE PRO" badge in the section header so the gated state is
	// visible at the section level, not only per-row.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'group_headers',
		'group_headers',
		array(
			'title'       => __( 'Group Headers', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
			'help_url'    => '636138',
			'pro_notice'  => bb_admin_settings_get_pro_notice( 'group_headers', 'section' ),
		)
	);

	// FIELD: Header Style (Pro only, image_radio).
	bb_register_feature_field(
		'groups',
		'group_headers',
		'group_headers',
		array(
			'name'              => 'bb-group-header-style',
			'label'             => __( 'Header Style', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select the style of your group header. Group avatars and cover images will only be displayed if they are enabled. This setting does not apply to the App style.', 'buddyboss' ),
			'default'           => function_exists( 'bb_get_group_header_style' ) ? bb_get_group_header_style() : 'left',
			'sanitize_callback' => 'bb_groups_sanitize_header_style',
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
			'order'             => 10,
		)
	);

	// FIELD: Elements (Pro only, toggle_list).
	bb_register_feature_field(
		'groups',
		'group_headers',
		'group_headers',
		array(
			'name'              => 'bb-group-headers-elements',
			'label'             => __( 'Elements', 'buddyboss' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select which elements to show in your group headers.', 'buddyboss' ),
			'default'           => array(),
			'sanitize_callback' => 'bb_groups_sanitize_toggle_list',
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
			'pro_only'          => true,
			'order'             => 20,
		)
	);

	/**
	 * Fires after Group Headers section fields are registered.
	 * Allows third-party extensions to add more fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_settings_after_headers_fields' );
}
