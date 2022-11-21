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
 * @since BuddyBoss 1.9.1
 *
 * @return array List of profile header elements.
 */
function bb_get_profile_header_elements() {
	$elements = array();

	$elements[] = array(
		'element_name'  => 'online-status',
		'element_label' => esc_html__( 'Online Status', 'buddyboss' ),
	);

	if ( bp_member_type_enable_disable() && bp_member_type_display_on_profile() ) {
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

	if ( function_exists( 'bb_enabled_member_social_networks' ) && bb_enabled_member_social_networks() ) {
		$elements[] = array(
			'element_name'  => 'social-networks',
			'element_label' => esc_html__( 'Social Networks', 'buddyboss' ),
		);
	}

	/**
	 * Profile headers elements.
	 *
	 * @since BuddyBoss 1.9.1
	 *
	 * @param $elements array List of profile header elements.
	 */
	return apply_filters( 'bb_get_profile_header_elements', $elements );
}

/**
 * Get element list for member directory.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return array List of elements for member directory.
 */
function bb_get_member_directory_elements() {
	$elements = array();

	$elements[] = array(
		'element_name'  => 'online-status',
		'element_label' => esc_html__( 'Online Status', 'buddyboss' ),
	);

	if ( bp_member_type_enable_disable() && bp_member_type_display_on_profile() ) {
		$elements[] = array(
			'element_name'  => 'profile-type',
			'element_label' => esc_html__( 'Profile Type', 'buddyboss' ),
		);
	}

	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
		$elements[] = array(
			'element_name'  => 'followers',
			'element_label' => esc_html__( 'Followers', 'buddyboss' ),
		);
	}

	$elements[] = array(
		'element_name'  => 'last-active',
		'element_label' => esc_html__( 'Last Active', 'buddyboss' ),
	);

	$elements[] = array(
		'element_name'  => 'joined-date',
		'element_label' => esc_html__( 'Joined Date', 'buddyboss' ),
	);

	/**
	 * Member directory elements.
	 *
	 * @since BuddyBoss 1.9.1
	 *
	 * @param $elements array List of member directory elements.
	 */
	return apply_filters( 'bb_get_member_directory_elements', $elements );
}

/**
 * Get profile actions for member directory.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return array List of profile actions for member directory.
 */
function bb_get_member_directory_profile_actions() {
	$profile_actions = array();

	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
		$profile_actions[] = array(
			'element_name'  => 'follow',
			'element_label' => esc_html__( 'Follow', 'buddyboss' ),
		);
	}

	if ( function_exists( 'bp_is_active' ) && bp_is_active( 'friends' ) ) {
		$profile_actions[] = array(
			'element_name'  => 'connect',
			'element_label' => esc_html__( 'Connect', 'buddyboss' ),
		);
	}

	$bp_force_friendship_to_message = bp_force_friendship_to_message();

	if ( bp_is_active( 'messages' ) && ( ! $bp_force_friendship_to_message || ( $bp_force_friendship_to_message && bp_is_active( 'friends' ) ) )
	) {
		$profile_actions[] = array(
			'element_name'  => 'message',
			'element_label' => esc_html__( 'Send Message', 'buddyboss' ),
		);
	}

	/**
	 * Profile actions for member directory.
	 *
	 * @since BuddyBoss 1.9.1
	 *
	 * @param $profile_actions array List of profile actions for member directory.
	 */
	return apply_filters( 'bb_get_member_directory_profile_actions', $profile_actions );
}
