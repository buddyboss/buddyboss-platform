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

	$elements[] = array(
		'element_name'  => 'profile-type',
		'element_label' => esc_html__( 'Profile Type', 'buddyboss' ),
		'element_class' => bp_member_type_enable_disable() && bp_member_type_display_on_profile() ? '' : 'bp-hide'
	);

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

	$activity_follow_active = function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active();

	$elements[] = array(
		'element_name'  => 'followers',
		'element_label' => esc_html__( 'Followers', 'buddyboss' ),
		'element_class' => $activity_follow_active ? '' : 'bp-hide',
	);

	$elements[] = array(
		'element_name'  => 'following',
		'element_label' => esc_html__( 'Following', 'buddyboss' ),
		'element_class' => $activity_follow_active ? '' : 'bp-hide',
	);

	$elements[] = array(
		'element_name'  => 'social-networks',
		'element_label' => esc_html__( 'Social Networks', 'buddyboss' ),
		'element_class' => ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) && function_exists( 'bb_enabled_member_social_networks' ) && bb_enabled_member_social_networks() ) ? '' : 'bp-hide',
	);

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

	$elements[] = array(
		'element_name'  => 'profile-type',
		'element_label' => esc_html__( 'Profile Type', 'buddyboss' ),
		'element_class' => bp_member_type_enable_disable() && bp_member_type_display_on_profile() ? '' : 'bp-hide',
	);

	$elements[] = array(
		'element_name'  => 'followers',
		'element_label' => esc_html__( 'Followers', 'buddyboss' ),
		'element_class' => ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) ? '' : 'bp-hide',
	);

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

	$profile_actions[] = array(
		'element_name'  => 'follow',
		'element_label' => esc_html__( 'Follow', 'buddyboss' ),
		'element_class' => ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) ? '' : 'bp-hide',
	);

	$profile_actions[] = array(
		'element_name'  => 'connect',
		'element_label' => esc_html__( 'Connect', 'buddyboss' ),
		'element_class' => function_exists( 'bp_is_active' ) && bp_is_active( 'friends' ) ? '' : 'bp-hide',
	);

	$bp_force_friendship_to_message = bp_force_friendship_to_message();

	$profile_actions[] = array(
		'element_name'  => 'message',
		'element_label' => esc_html__( 'Send Message', 'buddyboss' ),
		'element_class' => (
			bp_is_active( 'messages' ) &&
			(
				! $bp_force_friendship_to_message ||
				( $bp_force_friendship_to_message && bp_is_active( 'friends' ) )
			)
		) ? '' : 'bp-hide',
	);

	/**
	 * Profile actions for member directory.
	 *
	 * @since BuddyBoss 1.9.1
	 *
	 * @param $profile_actions array List of profile actions for member directory.
	 */
	return apply_filters( 'bb_get_member_directory_profile_actions', $profile_actions );
}
