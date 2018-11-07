<?php
/**
 * BuddyBoss Follow Template Functions.
 *
 * @package BuddyBoss
 * @subpackage FollowTemplate
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the follow component slug.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_follow_slug() {
	echo bp_get_follow_slug();
}
/**
 * Return the follow component slug.
 *
 * @since BuddyBoss 3.1.1
 *
 * @return string
 */
function bp_get_follow_slug() {

	/**
	 * Filters the follow component slug.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $value Follow component slug.
	 */
	return apply_filters( 'bp_get_follow_slug', buddypress()->follow->slug );
}

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

	$ids = implode( ',', (array) bp_get_followers( array( 'user_id' => $r['user_id'] ) ) );

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

	$ids = implode( ',', (array) bp_get_following( array( 'user_id' => $r['user_id'] ) ) );

	$ids = empty( $ids ) ? 0 : $ids;

	return apply_filters( 'bp_get_following_ids', $ids, $r['user_id'] );
}

/**
 * Output the Follow button.
 *
 * @since BuddyBoss 3.1.1
 *
 * @see bp_add_follow_button() for information on arguments.
 *
 * @param int $leader_id The user ID of the person we want to follow.
 * @param int $follower_id The user ID initiating the follow request.
 * @param array $button_args See BP_Button class for more information.
 */
function bp_add_follow_button( $leader_id = false, $follower_id = false, $button_args = array() ) {
	echo bp_get_add_follow_button( $leader_id, $follower_id, $button_args );
}
/**
 * Returns a follow / unfollow button for a given user depending on the follower status.
 *
 * Checks to see if the follower is already following the leader.  If is following, returns
 * "Stop following" button; if not following, returns "Follow" button.
 *
 * @param int $leader_id The user ID of the person we want to follow.
 * @param int $follower_id The user ID initiating the follow request.
 * @param array $button_args See BP_Button class for more information.
 *
 * @return mixed String of the button on success.  Boolean false on failure.
 * @uses bp_get_button() Renders a button using the BP Button API
 * @since Buddyboss 3.1.1
 */
function bp_get_add_follow_button( $leader_id = false, $follower_id = false, $button_args = array() ) {

	if ( ! $leader_id || ! $follower_id )
		return false;

	$is_following = bp_is_following( array(
		'leader_id'   => $leader_id,
		'follower_id' => $follower_id
	) );

	$button_args = wp_parse_args( $button_args, get_class_vars( 'BP_Button' ) );

	if ( $is_following ) {
		$button = wp_parse_args(
			array(
				'id'                => 'member_follow',
				'component'         => 'follow',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_class'     => 'follow-button following',
				'wrapper_id'        => 'follow-button-' . $leader_id,
				'link_href'         => wp_nonce_url( bp_loggedin_user_domain() . bp_get_follow_slug() . '/stop-following/' . $leader_id . '/', 'follow_unfollow' ),
				'link_text'         => __( 'Following', 'buddyboss' ),
				'link_id'           => 'follow-' . $leader_id,
				'link_rel'          => 'stop',
				'link_class'        => 'follow-button following stop bp-toggle-action-button',
				'button_attr'       => array(
					'data-title'           => __( 'Unfollow', 'buddyboss' ),
					'data-title-displayed' => __( 'Following', 'buddyboss' )
				)
			)
			, $button_args );
	} else {
		$button = wp_parse_args(
			array(
				'id'                => 'member_follow',
				'component'         => 'follow',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_class'     => 'follow-button not_following',
				'wrapper_id'        => 'follow-button-' . $leader_id,
				'link_href'         => wp_nonce_url( bp_loggedin_user_domain() . bp_get_follow_slug() . '/start-following/' . $leader_id . '/', 'follow_follow' ),
				'link_text'         => __( 'Follow', 'buddyboss' ),
				'link_id'           => 'follow-' . $leader_id,
				'link_rel'          => 'start',
				'link_class'        => 'follow-button not_following start'
			)
			, $button_args );
	}

	/**
	 * Filters the HTML for the follow button.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $button HTML markup for follow button.
	 */
	return bp_get_button( apply_filters( 'bp_get_add_follow_button', $button ) );
}
