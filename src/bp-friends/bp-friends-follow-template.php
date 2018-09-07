<?php
/**
 * BP Follow Template Tags
 *
 * @package BP-Follow
 * @subpackage Template
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output a comma-separated list of user_ids for a given user's followers.
 *
 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_get_follower_ids() Returns comma-seperated string of user IDs on success. Integer zero on failure.
 */
function bp_follower_ids( $args = '' ) {
	echo bp_get_follower_ids( $args );
}
/**
 * Returns a comma separated list of user_ids for a given user's followers.
 *
 * This can then be passed directly into the members loop querystring.
 * On failure, returns an integer of zero. Needed when used in a members loop to prevent SQL errors.
 *
 * Arguments include:
 * 	'user_id' - The user ID you want to check for followers
 *
 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @return Mixed Comma-seperated string of user IDs on success. Integer zero on failure.
 */
function bp_get_follower_ids( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'user_id' => bp_displayed_user_id()
	) );

	$ids = implode( ',', (array) bp_follow_get_followers( array( 'user_id' => $r['user_id'] ) ) );

	$ids = empty( $ids ) ? 0 : $ids;

	return apply_filters( 'bp_get_follower_ids', $ids, $r['user_id'] );
}

/**
 * Output a comma-separated list of user_ids for a given user's following.
 *
 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @uses bp_get_following_ids() Returns comma-seperated string of user IDs on success. Integer zero on failure.
 */
function bp_following_ids( $args = '' ) {
	echo bp_get_following_ids( $args );
}
/**
 * Returns a comma separated list of user_ids for a given user's following.
 *
 * This can then be passed directly into the members loop querystring.
 * On failure, returns an integer of zero. Needed when used in a members loop to prevent SQL errors.
 *
 * Arguments include:
 * 	'user_id' - The user ID you want to check for a following
 *
 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @return Mixed Comma-seperated string of user IDs on success. Integer zero on failure.
 */
function bp_get_following_ids( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'user_id' => bp_displayed_user_id()
	) );

	$ids = implode( ',', (array)bp_follow_get_following( array( 'user_id' => $r['user_id'] ) ) );

	$ids = empty( $ids ) ? 0 : $ids;

	return apply_filters( 'bp_get_following_ids', $ids, $r['user_id'] );
}

/**
 * Output the Follow button.
 *
 * @since BuddyPress 3.1.1
 *
 * @see bp_follow_add_follow_button() for information on arguments.
 *
 * @param mixed $args See {@link bp_follow_get_add_follow_button()}.
 */
function bp_follow_add_follow_button( $args = '' ) {
	echo bp_follow_get_add_follow_button( $args );
}
/**
 * Returns a follow / unfollow button for a given user depending on the follower status.
 *
 * Checks to see if the follower is already following the leader.  If is following, returns
 * "Stop following" button; if not following, returns "Follow" button.
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to follow.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return mixed String of the button on success.  Boolean false on failure.
 * @uses bp_get_button() Renders a button using the BP Button API
 * @since 3.1.1
 */
function bp_follow_get_add_follow_button( $args = '' ) {

	$r = wp_parse_args( $args, array(
		'leader_id'     => bp_displayed_user_id(),
		'follower_id'   => bp_loggedin_user_id(),
	) );

	if ( ! $r['leader_id'] || ! $r['follower_id'] )
		return false;

	$is_following = bp_follow_is_following( array(
		'leader_id'   => $r['leader_id'],
		'follower_id' => $r['follower_id']
	) );

	if ( ! $is_following ) {
		$button = array(
			'id'                => 'member_follow',
			'component'         => 'friends',
			'must_be_logged_in' => true,
			'block_self'        => true,
			'wrapper_class'     => 'follow-button not_following',
			'wrapper_id'        => 'follow-button-' . $r['leader_id'],
			'link_href'         => wp_nonce_url( bp_loggedin_user_domain() . bp_get_friends_slug() . '/start-following/' . $r['leader_id'] . '/', 'friends_follow' ),
			'link_text'         => __( 'Follow', 'buddyboss' ),
			'link_id'           => 'follow-' . $r['leader_id'],
			'link_rel'          => 'start',
			'link_class'        => 'follow-button not_following start'
		);
	} else {
		$button = array(
			'id'                => 'member_follow',
			'component'         => 'friends',
			'must_be_logged_in' => true,
			'block_self'        => true,
			'wrapper_class'     => 'follow-button following',
			'wrapper_id'        => 'follow-button-' . $r['leader_id'],
			'link_href'         => wp_nonce_url( bp_loggedin_user_domain() . bp_get_friends_slug() . '/stop-following/' . $r['leader_id'] . '/', 'friends_unfollow' ),
			'link_text'         => __( 'Following', 'buddyboss' ),
			'link_id'           => 'follow-' . $r['leader_id'],
			'link_rel'          => 'stop',
			'link_class'        => 'follow-button following stop'
		);
	}

	/**
	 * Filters the HTML for the follow button.
	 *
	 * @since BuddyPress 3.1.1
	 *
	 * @param string $button HTML markup for follow button.
	 */
	return bp_get_button( apply_filters( 'bp_follow_get_add_follow_button', $button ) );
}
