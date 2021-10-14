<?php
/**
 * Groups: Single group "Members > All Members" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Handle the display of a group's members/all-members page.
 *
 * @since BuddyBoss 1.0.0
 */
function groups_screen_group_members_all_members() {

	// should also not accessible if members slug is removed
	$members_menu_removed = true;
	foreach ( bp_get_nav_menu_items('groups') as $nav_item ) {
		if( 'members' === $nav_item->css_id ) {
			$members_menu_removed = false;
		}
	}

	if ( 'all-members' !== bp_get_group_current_members_tab() || $members_menu_removed ) {
		return false;
	}

	$bp = buddypress();

	/**
	 * Fires before the loading of the group members/all-members page template.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id ID of the group that is being displayed.
	 */
	do_action( 'groups_screen_group_members_all_members', $bp->groups->current_group->id );

	/**
	 * Filters the template to load for a group's members/all-members page.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Path to a group's members/all-members template.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_members', 'groups/single/home' ) );
}
add_action( 'bp_screens', 'groups_screen_group_members_all_members' );
