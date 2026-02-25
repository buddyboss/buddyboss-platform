<?php
/**
 * BuddyBoss Admin Settings - Profile Headers Panel.
 *
 * Registers sections and fields for the Profile Headers side panel.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Profile Headers panel sections and fields.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_members_register_profile_headers_panel_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Profile Headers
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'title'       => __( 'Profile Headers', 'buddyboss' ),
			'description' => '',
			'order'       => 10,
		)
	);

	// FIELD: Header Style (Pro only, image_radio).
	bb_register_feature_field(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'name'              => 'bb-profile-headers-layout-style',
			'label'             => __( 'Header Style', 'buddyboss' ),
			'type'              => 'image_radio',
			'description'       => __( 'Select the style of your profile headers. Profile cover images will only be displayed if they are enabled.', 'buddyboss' ),
			'default'           => function_exists( 'bb_get_profile_header_style' ) ? bb_get_profile_header_style() : 'left',
			'sanitize_callback' => 'bb_members_sanitize_header_style',
			'options'           => array(
				array(
					'label' => is_rtl() ? __( 'Right', 'buddyboss' ) : __( 'Left', 'buddyboss' ),
					'value' => 'left',
					'image' => 'header-left-profile',
				),
				array(
					'label' => __( 'Centered', 'buddyboss' ),
					'value' => 'centered',
					'image' => 'header-centered-profile',
				),
			),
			'pro_only'          => true,
			'order'             => 10,
		)
	);

	// Build profile header element options from the existing function.
	$header_elements = function_exists( 'bb_get_profile_header_elements' ) ? bb_get_profile_header_elements() : array();
	$element_options = array();

	foreach ( $header_elements as $element ) {
		$option = array(
			'label' => $element['element_label'],
			'value' => $element['element_name'],
		);

		// Disable elements that depend on inactive features.
		if ( ! empty( $element['element_class'] ) && false !== strpos( $element['element_class'], 'bp-hide' ) ) {
			$option['disabled'] = true;
		}

		$element_options[] = $option;
	}

	// FIELD: Elements (Pro only, toggle_list).
	// Default is empty array; Pro's bb_enrich_members_field_data() filter loads
	// real values from 'bb-pro-profile-headers-layout-elements' at AJAX time.
	bb_register_feature_field(
		'members',
		'profile_headers',
		'profile_headers',
		array(
			'name'              => 'bb-profile-headers-layout-elements',
			'label'             => __( 'Elements', 'buddyboss' ),
			'type'              => 'toggle_list',
			'description'       => __( 'Select which elements to show in your profile headers.', 'buddyboss' ),
			'default'           => array(),
			'sanitize_callback' => 'bb_members_sanitize_toggle_list',
			'options'           => $element_options,
			'pro_only'          => true,
			'order'             => 20,
		)
	);

	/**
	 * Fires after Profile Headers section fields are registered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_members_settings_after_headers_fields' );
}
