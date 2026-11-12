<?php
/**
 * Groups: Single group "Members" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of a group's Members page.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_screen_group_members() {

	if ( ! bboss_is_single_item() || ! bboss_is_current_action( 'members' ) ) {
		return false;
	}

	if ( bboss_action_variables() ) {
		return false;
	}

	$bp = bboss();

	// Refresh the group member count meta.
	bboss_groups_update_groupmeta( $bp->groups->current_group->id, 'total_member_count', bboss_groups_get_total_member_count( $bp->groups->current_group->id ) );

	/**
	 * Fires before the loading of a group's Members page.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $id ID of the group whose members are being displayed.
	 */
	do_action( 'groups_screen_group_members', $bp->groups->current_group->id );

	bboss_core_redirect( bboss_get_group_permalink( bboss_groups_get_current_group() ) . 'members/all-members/' );
}
