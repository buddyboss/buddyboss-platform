<?php
/**
 * BuddyBoss Media bbPress functions
 *
 * Functions for handling media in bbPress.
 *
 * @author        BuddyBoss
 * @category    Core
 * @package    BuddyBoss/Functions
 */

if ( ! function_exists( 'bbm_is_bbpress' ) ) {
	/**
	 * Check if the current page is forum, topic or other bbPress page.
	 *
	 * @return bool true if the current page is the forum related
	 */
	function bbm_is_bbpress() {

		$is = false;
		if(  bbm_has_bbpress() && ( in_array( get_post_type(), array( 'topic' )  )
				||  bp_is_group_forum_topic()
				||  bbp_is_single_forum()
				|| 	bbp_is_reply_edit()
				|| 	bp_is_current_action('forum') ) ) {
			$is = true;
		}

		return apply_filters( 'bbm_is_bbpress', $is );
	}
}

if ( ! function_exists( 'bbm_has_bbpress' ) ) {
	function bbm_has_bbpress() {
		if ( function_exists( 'bbp_version' ) ) {
			$version = bbp_get_version();
			$version = intval( substr( str_replace( '.', '', $version ), 0, 2 ) );

			return $version > 22;
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'bbm_is_user_admin' ) ) {
	/**
	 * Checks to see if the currently logged user is administrator.
	 *
	 * @return bool is user administrator or not
	 */
	function bbm_is_user_admin() {
		global $current_user;

		if ( is_array( $current_user->roles ) ) {
			return in_array( 'administrator', $current_user->roles );
		} else {
			return false;
		}
	}
}

if ( ! function_exists( 'bbm_is_user_moderator' ) ) {
	/**
	 * Checks to see if the currently logged user is moderator.
	 *
	 * @return bool is user moderator or not
	 */
	function bbm_is_user_moderator() {
		global $current_user;

		if ( is_array( $current_user->roles ) ) {
			return in_array( 'bbp_moderator', $current_user->roles );
		} else {
			return false;
		}
	}
}


