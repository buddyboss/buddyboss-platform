<?php
/**
 * Groups: Single group "Manage" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handle the display of a group's Admin pages.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_groups_screen_group_admin() {
	if ( ! bboss_is_groups_component() || ! bboss_is_current_action( 'admin' ) ) {
		return false;
	}

	if ( bboss_action_variables() ) {
		return false;
	}

	bboss_core_redirect( bboss_get_group_permalink( bboss_groups_get_current_group() ) . 'admin/edit-details/' );
}
