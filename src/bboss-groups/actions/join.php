<?php
/**
 * Groups: Join action
 *
 * @package BuddyBoss\Group\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch and process "Join Group" button clicks.
 *
 * @since BuddyPress 1.0.0
 *
 * @return bool
 */
function bboss_groups_action_join_group() {

	if ( ! bboss_is_single_item() || ! bboss_is_groups_component() || ! bboss_is_current_action( 'join' ) ) {
		return false;
	}

	// Nonce check.
	if ( ! check_admin_referer( 'groups_join_group' ) ) {
		return false;
	}

	$bp = bboss();

	// Skip if banned or already a member.
	if ( ! bboss_groups_is_user_member( bboss_loggedin_user_id(), $bp->groups->current_group->id ) && ! bboss_groups_is_user_banned( bboss_loggedin_user_id(), $bp->groups->current_group->id ) ) {

		// User wants to join a group that requires an invitation to join.
		if ( ! bboss_current_user_can( 'groups_join_group', array( 'group_id' => $bp->groups->current_group->id ) ) ) {
			if ( ! bboss_groups_check_user_has_invite( bboss_loggedin_user_id(), $bp->groups->current_group->id ) ) {
				bboss_core_add_message( __( 'There was an error joining the group.', 'buddyboss' ), 'error' );
				bboss_core_redirect( bboss_get_group_permalink( $bp->groups->current_group ) );
			}
		}

		// User wants to join any group.
		if ( ! bboss_groups_join_group( $bp->groups->current_group->id ) ) {
			bboss_core_add_message( __( 'There was an error joining the group.', 'buddyboss' ), 'error' );
		} else {
			bboss_core_add_message( __( 'You joined the group!', 'buddyboss' ) );
		}

		bboss_core_redirect( bboss_get_group_permalink( $bp->groups->current_group ) );
	}

	/**
	 * Filters the template to load for the single group screen.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Path to the single group template to load.
	 */
	bboss_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );
}
add_action( 'bp_actions', 'groups_action_join_group' );
