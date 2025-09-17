<?php
/**
 * Groups: Single group "Members" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the display of a group's Members page.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_group_members() {

	if ( ! bp_is_single_item() || ! bp_is_current_action( 'members' ) ) {
		return false;
	}

	if ( bp_action_variables() ) {
		return false;
	}

	$bp = buddypress();

	// Refresh the group member count meta.
	groups_update_groupmeta( $bp->groups->current_group->id, 'total_member_count', groups_get_total_member_count( $bp->groups->current_group->id ) );

	/**
	 * Fires before the loading of a group's Members page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $id ID of the group whose members are being displayed.
	 */
	do_action( 'groups_screen_group_members', $bp->groups->current_group->id );

	bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'members/all-members/' );
}
