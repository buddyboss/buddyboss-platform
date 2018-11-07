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

/**
 * Set up activity arguments for use with the 'following' scope.
 *
 * For details on the syntax, see {@link BP_Activity_Query}.
 *
 * Only applicable to BuddyPress 2.2+.  Older BP installs uses the code
 * available in /backpat/activity-scope.php.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 *
 * @return array
 */
function bp_users_filter_activity_following_scope( $retval = array(), $filter = array() ) {
	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Determine following of user.
	$following_ids = bp_get_following( array(
		'user_id' => $user_id,
	) );
	if ( empty( $following_ids ) ) {
		$following_ids = array( 0 );
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'user_id',
			'compare' => 'IN',
			'value'   => (array) $following_ids,
		),

		// we should only be able to view sitewide activity content for those the user
		// is following.
		array(
			'column' => 'hide_sitewide',
			'value'  => 0,
		),

		// overrides.
		'override' => array(
			'filter'      => array(
				'user_id' => 0,
			),
			'show_hidden' => true,
		),
	);

	return $retval;
}

add_filter( 'bp_activity_set_following_scope_args', 'bp_users_filter_activity_following_scope', 10, 2 );

