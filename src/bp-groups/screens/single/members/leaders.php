<?php
/**
 * Groups: Single group "Members > Leaders" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Handle the display of a group's members/leaders page.
 *
 * @since BuddyBoss 1.0.0
 */
function groups_screen_group_members_leaders() {

	if ( 'leaders' != bp_get_group_current_members_tab() ) {
		return false;
	}

	$bp = buddypress();

	/**
	 * Fires before the loading of the group members/leaders page template.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_members_leaders', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's members/leaders page.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Path to a group's members/leaders template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_members', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_members_leaders' );
