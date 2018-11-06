<?php
/**
 * BuddyPress Follow Filters.
 *
 * @package BuddyBoss
 * @subpackage FollowFilters
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Filter the members loop on a follow page.
 *
 * This is done so we can return the users that:
 *   - the current user is following (on a user page or member directory); or
 *   - are following the displayed user on the displayed user's followers page
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array|string $qs The querystring for the BP loop.
 * @param str $object The current object for the querystring.
 *
 * @return array|string Modified querystring
 */
function bp_add_member_follow_scope_filter( $qs, $object ) {
	// not on the members object? stop now!
	if ( 'members' !== $object ) {
		return $qs;
	}

	// members directory
	if ( ! bp_is_user() && bp_is_members_directory() ) {
		$qs_args = wp_parse_args( $qs );
		// check if members scope is following before manipulating.
		if ( isset( $qs_args['scope'] ) && 'following' === $qs_args['scope'] ) {
			$qs .= '&include=' . bp_get_following_ids( array(
					'user_id' => bp_loggedin_user_id(),
				) );
		}
	}

	return $qs;
}

add_filter( 'bp_ajax_querystring', 'bp_add_member_follow_scope_filter', 20, 2 );

