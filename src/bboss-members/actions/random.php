<?php
/**
 * Members: Random member action handler
 *
 * @package BuddyBoss\Members\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Redirect to a random member page when visiting a ?random-member URL.
 *
 * @since BuddyPress 1.0.0
 */
function bboss_core_get_random_member() {
	if ( ! isset( $_GET['random-member'] ) ) {
		return;
	}

	$user = bboss_core_get_users(
		array(
			'type'     => 'random',
			'per_page' => 1,
		)
	);
	bboss_core_redirect( bboss_core_get_user_domain( $user['users'][0]->id ) );
}
add_action( 'bp_actions', 'bp_core_get_random_member' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_get_random_member' ) && ! function_exists( 'bp_core_get_random_member' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_random_member()}.
	 *
	 * @deprecated Use bboss_core_get_random_member() instead.
	 */
	function bp_core_get_random_member() {
		return bboss_core_get_random_member();
	}
}
// @bbwt-com-end compat-layer
