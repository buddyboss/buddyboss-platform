<?php
/**
 * BuddyPress Members Admin
 *
 * @package BuddyBoss\Members\Admin
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load the BP Members admin.
add_action( 'bp_init', array( 'BP_Members_Admin', 'register_members_admin' ) );

/**
 * Get element list for profile headers.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array List of profile header elements.
 */
function bb_pro_get_profile_header_elements() {
	$elements = array();

	$elements[] = array(
		'element_name'  => 'online-status',
		'element_label' => esc_html__( 'Online Status', 'buddyboss' ),
	);

	if ( bp_member_type_enable_disable() ) {
		$elements[] = array(
			'element_name'  => 'profile-type',
			'element_label' => esc_html__( 'Profile Type', 'buddyboss' ),
		);
	}

	$elements[] = array(
		'element_name'  => 'member-handle',
		'element_label' => esc_html__( 'Member Handle', 'buddyboss' ),
	);

	$elements[] = array(
		'element_name'  => 'joined-date',
		'element_label' => esc_html__( 'Joined Date', 'buddyboss' ),
	);

	$elements[] = array(
		'element_name'  => 'last-active',
		'element_label' => esc_html__( 'Last Active', 'buddyboss' ),
	);

	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
		$elements[] = array(
			'element_name'  => 'followers',
			'element_label' => esc_html__( 'Followers', 'buddyboss' ),
		);

		$elements[] = array(
			'element_name'  => 'following',
			'element_label' => esc_html__( 'Following', 'buddyboss' ),
		);
	}

	/**
	 * Profile headers elements.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param $elements array List of profile header elements.
	 */
	return apply_filters( 'bb_pro_get_profile_header_elements', $elements );
}
