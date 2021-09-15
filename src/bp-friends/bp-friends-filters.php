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

	if ( $user_id !== bp_loggedin_user_id() ) {
		array_push( $friends, bp_loggedin_user_id() );
	}

	if ( ! bp_is_profile_media_support_enabled() ) {
		$friends = array( 0 );
	}

	$retval = array(
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
	);

	if ( ! bp_is_profile_albums_support_enabled() ) {
		$retval[] = array(
			'column'  => 'album_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		$retval[] = array(
			'column'  => 'title',
			'compare' => 'LIKE',
			'value'   => $filter['search_terms'],
		);
	}

	return $retval;
}
add_filter( 'bp_media_set_friends_scope_args', 'bp_friends_filter_media_scope', 10, 2 );

/**
 * Set up video arguments for use with the 'friends' scope.
 *
 * For details on the syntax, see {@link BP_Video_Query}.
 *
 * @since BuddyBoss 1.5.7
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_friends_filter_video_scope( $retval = array(), $filter = array() ) {

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

	if ( bp_loggedin_user_id() !== (int) $user_id ) {
		array_push( $friends, bp_loggedin_user_id() );
	}

	if ( ! bp_is_profile_video_support_enabled() ) {
		$friends = array( 0 );
	}

	$retval = array(
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
	);

	if ( ! bp_is_profile_albums_support_enabled() ) {
		$retval[] = array(
			'column'  => 'album_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		$retval[] = array(
			'column'  => 'title',
			'compare' => 'LIKE',
			'value'   => $filter['search_terms'],
		);
	}

	return $retval;
}
add_filter( 'bp_video_set_friends_scope_args', 'bp_friends_filter_video_scope', 10, 2 );

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

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$folder_id = 0;
	$folders   = array();
	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	// Determine friends of user.
	$friends = friends_get_friend_user_ids( $user_id );
	if ( empty( $friends ) ) {
		$friends = array( 0 );
	}

	if ( $user_id !== bp_loggedin_user_id() ) {
		array_push( $friends, bp_loggedin_user_id() );
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$folder_ids           = array();
			$user_root_folder_ids = bp_document_get_folder_children( (int) $folder_id );
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}
			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'parent',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		$folders = array(
			'column' => 'folder_id',
			'value'  => 0,
		);
	}

	if ( ! bp_is_profile_document_support_enabled() ) {
		$friends = array( 0 );
	}

	$args = array(
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
		$folders,
	);

	return $args;
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

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = (int) $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$folder_id = 0;
	$folders   = array();
	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	// Determine friends of user.
	$friends = friends_get_friend_user_ids( $user_id );
	if ( empty( $friends ) ) {
		$friends = array( 0 );
	}

	if ( $user_id !== bp_loggedin_user_id() ) {
		array_push( $friends, bp_loggedin_user_id() );
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$folder_ids           = array();
			$user_root_folder_ids = bp_document_get_folder_children( (int) $folder_id );
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}
			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'parent',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		$folders = array(
			'column' => 'parent',
			'value'  => 0,
		);
	}

	if ( ! bp_is_profile_document_support_enabled() ) {
		$friends = array( 0 );
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'user_id',
			'compare' => 'IN',
			'value'   => (array) $friends,
		),
		array(
			'column'  => 'privacy',
			'compare' => '=',
			'value'   => 'friends',
		),
		$folders,
	);

	return $args;
}
add_filter( 'bp_document_set_folder_friends_scope_args', 'bp_friends_filter_folder_scope', 10, 2 );
