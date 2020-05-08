<?php
/**
 * BuddyPress Friend Filters.
 *
 * @package BuddyBoss\Connections\Filters
 * @since BuddyPress 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Filter BP_User_Query::populate_extras to add confirmed friendship status.
 *
 * Each member in the user query is checked for confirmed friendship status
 * against the logged-in user.
 *
 * @since BuddyPress 1.7.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param BP_User_Query $user_query   The BP_User_Query object.
 * @param string        $user_ids_sql Comma-separated list of user IDs to fetch extra
 *                                    data for, as determined by BP_User_Query.
 */
function bp_friends_filter_user_query_populate_extras( BP_User_Query $user_query, $user_ids_sql ) {
	global $wpdb;

	// Stop if user isn't logged in.
	if ( ! $user_id = bp_loggedin_user_id() ) {
		return;
	}

	$maybe_friend_ids = wp_parse_id_list( $user_ids_sql );

	// Bulk prepare the friendship cache.
	BP_Friends_Friendship::update_bp_friends_cache( $user_id, $maybe_friend_ids );

	foreach ( $maybe_friend_ids as $friend_id ) {
		$status = BP_Friends_Friendship::check_is_friend( $user_id, $friend_id );
		$user_query->results[ $friend_id ]->friendship_status = $status;
		if ( 'is_friend' == $status ) {
			$user_query->results[ $friend_id ]->is_friend = 1;
		}
	}

}
add_filter( 'bp_user_query_populate_extras', 'bp_friends_filter_user_query_populate_extras', 4, 2 );

/**
 * Set up media arguments for use with the 'friends' scope.
 *
 * For details on the syntax, see {@link BP_Media_Query}.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_friends_filter_media_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Determine friends of user.
	$friends = friends_get_friend_user_ids( $user_id );
	if ( empty( $friends ) ) {
		$friends = array( 0 );
	}

	$retval = array(
		'relation' => 'OR',
		array(
			'relation' => 'AND',
			array(
				'column'  => 'user_id',
				'compare' => 'IN',
				'value'   => (array) $friends,
			),
			array(
				'column' => 'privacy',
				'value'  => 'friends',
			),
		),
	);

	return $retval;
}
add_filter( 'bp_media_set_friends_scope_args', 'bp_friends_filter_media_scope', 10, 2 );

/**
 * Set up media arguments for use with the 'friends' scope.
 *
 * For details on the syntax, see {@link BP_Media_Query}.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_friends_filter_document_scope( $retval = array(), $filter = array() ) {

	if ( ! bp_is_profile_document_support_enabled() ) {
		return $retval;
	}

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Determine friends of user.
	$friends = friends_get_friend_user_ids( $user_id );
	if ( empty( $friends ) ) {
		$friends = array( 0 );
	}
	array_push( $friends, bp_loggedin_user_id() );

	$retval = array(
		'relation' => 'OR',
		array(
			'relation' => 'AND',
			array(
				'column'  => 'user_id',
				'compare' => 'IN',
				'value'   => (array) $friends,
			),
			array(
				'column' => 'privacy',
				'value'  => 'friends',
			),
			array(
				'column' => 'album_id',
				'value'  => 0,
			),
		),
	);

	if ( ! empty( $filter['search_terms'] ) ) {
		$retval[] = array(
			'relation' => 'OR',
			array(
				'column'  => 'title',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'extension',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'file_name',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'caption',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'description',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
		);
	}

	return $retval;
}
add_filter( 'bp_document_set_document_friends_scope_args', 'bp_friends_filter_document_scope', 10, 2 );

/**
 * Set up media arguments for use with the 'friends' scope.
 *
 * For details on the syntax, see {@link BP_Media_Query}.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_friends_filter_folder_scope( $retval = array(), $filter = array() ) {

	if ( ! bp_is_profile_document_support_enabled() ) {
		return $retval;
	}

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = (int) $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Determine friends of user.
	$friends = friends_get_friend_user_ids( $user_id );
	if ( empty( $friends ) ) {
		$friends = array( 0 );
	}
	array_push( $friends, bp_loggedin_user_id() );

	$retval = array(
		'relation' => 'OR',
		array(
			'relation' => 'AND',
			array(
				'column'  => 'user_id',
				'compare' => 'IN',
				'value'   => (array) $friends,
			),
			array(
				'column' => 'privacy',
				'value'  => 'friends',
			),
			array(
				'column' => 'parent',
				'value'  => 0,
			),
		),
	);

	if ( ! empty( $filter['search_terms'] ) ) {
		$retval[] = array(
			'column'  => 'title',
			'compare' => 'LIKE',
			'value'   => $filter['search_terms'],
		);
	}

	return $retval;
}
add_filter( 'bp_document_set_folder_friends_scope_args', 'bp_friends_filter_folder_scope', 10, 2 );
