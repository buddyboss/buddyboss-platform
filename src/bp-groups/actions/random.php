<?php
/**
 * Groups: Random group action handler
 *
 * @package BuddyBoss\Group\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Catch requests for a random group page (example.com/groups/?random-group) and redirect.
 *
 * @since BuddyPress 1.2.0
 */
function bboss_groups_action_redirect_to_random_group() {

	if ( bboss_is_groups_component() && isset( $_GET['random-group'] ) ) {
		$group = BBoss_Groups_Group::get_random( 1, 1 );

		bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . $group['groups'][0]->slug ) );
	}
}
add_action( 'bp_actions', 'bboss_groups_action_redirect_to_random_group' );
