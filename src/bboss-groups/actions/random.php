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

	if ( bboss_is_groups_component() && isset( $_GET['random-group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only request; only redirects to a random group.
		$group = BBoss_Groups_Group::get_random( 1, 1 );

		bboss_core_redirect( trailingslashit( bboss_get_groups_directory_permalink() . $group['groups'][0]->slug ) );
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_action_redirect_to_random_group' ) && ! function_exists( 'groups_action_redirect_to_random_group' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_action_redirect_to_random_group()}.
	 *
	 * @deprecated Use bboss_groups_action_redirect_to_random_group() instead.
	 */
	function groups_action_redirect_to_random_group() {
		return bboss_groups_action_redirect_to_random_group();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_actions', 'groups_action_redirect_to_random_group' );
