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
add_action( 'bp_actions', 'bboss_core_get_random_member' );
