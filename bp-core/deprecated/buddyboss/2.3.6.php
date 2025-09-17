<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.3.60
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the default items to search though, if nothing has been selected in settings.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param mixed $value
 *
 * @return mixed
 */
function bb_global_search_default_items_to_search( $value ) {
	_deprecated_function( __FUNCTION__, '2.3.6' );
	if ( empty( $value ) ) {
		/**
		 * Setting > what to search?
		 * If admin has not selected anything yet( right after activating the plugin maybe),
		 * lets make sure search results do return someting at least.
		 * So, by default, we'll search though blog posts and members.
		 */
		$value = array( 'posts', 'pages', 'members' );
	}

	/*
	 * If member search is turned on, but none of wp_user table fields or xprofile fields are selected,
	 * we'll force username and nicename fields
	 */
	if ( in_array( 'members', $value ) ) {
		// Is any wp_user table colum or xprofile field selected?
		$field_selected = false;
		foreach ( $value as $item_to_search ) {
			if ( strpos( $item_to_search, 'member_field_' ) === 0 || strpos( $item_to_search, 'xprofile_field_' ) === 0 ) {
				$field_selected = true;
				break;
			}
		}

		// if not, lets add username and nicename to default items to search
		if ( ! $field_selected ) {
			$value[] = 'member_field_user_login';
			$value[] = 'member_field_user_nicename';
		}
	}

	return $value;
}

/**
 * Remove 'messages' and 'notifications' from search, if user is not logged In
 *
 * @since BuddyBoss 1.0.0
 *
 * @param mixed $search_types
 *
 * @return mixed
 */
function bp_search_remove_search_types_for_guests( $search_types ) {
	_deprecated_function( __FUNCTION__, '2.3.6' );
	if ( ! is_admin() && ! empty( $search_types ) && ! is_user_logged_in() ) {
		$items_to_remove       = array( 'messages', 'notifications' );
		$filtered_search_types = array();
		foreach ( $search_types as $search_type ) {
			if ( ! in_array( $search_type, $items_to_remove ) ) {
				$filtered_search_types[] = $search_type;
			}
		}

		$search_types = $filtered_search_types;
	}

	return $search_types;
}
