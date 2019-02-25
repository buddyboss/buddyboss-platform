<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function buddyboss_media_update_album( $args='' ){
	global $wpdb;

	$defaults = array(
		'id'		    => false,//passing album id will update the ablum
		'title'			=> '',
		'description'	=> '',
		'group_id'		=> NULL,
		'privacy'		=> 'public',
		'user_id'		=> bp_loggedin_user_id(),
		'date_created'	=> bp_core_current_time(),
	);

	$args = wp_parse_args($args, $defaults);

	if( $args['id'] ){
		return $wpdb->update(
			$wpdb->prefix . 'buddyboss_media_albums',
			array(
				'title'			=> $args['title'],
				'description'	=> $args['description'],
				'privacy'		=> $args['privacy'],
			),
			array( 'id' => $args['id'] ),
			array(
				'%s',
				'%s',
				'%s',
			),
			array( '%d' )
		);

	} else {
		$wpdb->insert(
			$wpdb->prefix . 'buddyboss_media_albums',
			array(
				'user_id'		=> $args['user_id'],
				'group_id'		=> $args['group_id'],
				'title'			=> $args['title'],
				'description'	=> $args['description'],
				'privacy'		=> $args['privacy'],
				'date_created'	=> $args['date_created'],
				'total_items'	=> 0,
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			)
		);
		return $wpdb->insert_id;
	}
}

/**
 * Delete a given album.
 * Deleting an album moves all photos under it into global uploads/uncategorized.
 *
 *
 * @param int $album_id Album to delete
 * @return boolean true if deleted successfuly, false otherwise
 */
function buddyboss_media_delete_album( $album_id ) {
	global $wpdb, $bp;
	//is user allowed to delete this album?
	$is_allowed = false;

	//IS it group album delete?
	if ( bbm_is_group_media_screen('albums') ) {

		//Check user can delete album
		$is_allowed = bbm_groups_user_can_delete_albums( $album_id );
	} else {

		$album_author = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}buddyboss_media_albums WHERE id=%d", $album_id ) );

		if( bp_loggedin_user_id() == $album_author
			|| current_user_can( 'manage_options' ) ) {
			$is_allowed = true;
		}
	}

	if( !$is_allowed )
		return false;

	//delete record from albums table
	$wpdb->delete(
			$wpdb->prefix . 'buddyboss_media_albums',
			array(
				'id'	=> $album_id,
			),
			array( '%d' )
		);

	//delete records from activity meta
	$wpdb->delete(
			$bp->activity->table_name_meta,
			array(
				'meta_key'		=> 'buddyboss_media_album_id',
				'meta_value'	=> $album_id
			),
			array( '%s', '%d' )
		);

	return true;
}

/**
 * Check if a single album is being displayed.
 *
 * @return boolean
 */
function buddyboss_media_is_single_album(){
	$is_single_album = false;

	if( ( bp_is_current_component( buddyboss_media_component_slug() ) && bp_is_current_action('albums') ) || bbm_is_group_media_screen('albums') ) {
		if( bp_action_variable(0) || bp_action_variable(1) ){
			$is_single_album = true;
		} else if( isset( $_GET['album'] ) && !empty( $_GET['album'] ) ){
			if( 'new'!=$_GET['album'] && (int)$_GET['album'] > 0 ){
				$is_single_album = true;
			}
		}
	}

	return $is_single_album;
}

/**
 * return the id of single album being viewed
 */
function buddyboss_media_single_album_id(){
	$album_id = false;
	if( buddyboss_media_is_single_album() ){

		if ( bbm_is_group_media_screen('albums') ) {
			$album_id = bp_action_variable(1);
		} else if( bp_action_variable(0) ) {
			$album_id = bp_action_variable(0);
		} else if( isset( $_GET['album'] ) && !empty( $_GET['album'] ) ) {
			$album_id = $_GET['album'];
		}
	}

	return $album_id;
}

/**
 * Hook to load only one album in albums loop.
 *
 * @param array $args
 * @return array
 */
function buddyboss_media_query_single_album( $args ){
	if( buddyboss_media_is_single_album() ){
		$args['include'] = array( buddyboss_media_single_album_id() );
	}
	return $args;
}

/**
 * Filter activity stream to display uploads from current album only
 * @param type $query_string
 * @param type $object
 * @return type
 */
function buddyboss_media_activity_filter_album( $query_string = '', $object = '' ){
	if( $object != 'activity' )
        return $query_string;

	$album_id = bp_action_variable(0);
	//@todo incomplete

	return $query_string;
}

/**
 * Save album id in activity meta if the post is done from a single albums page.
 *
 * @param type $activity
 * @param type $attachment_id
 * @param type $action
 */
function buddyboss_media_activity_add_album_id( $activity, $attachment_ids, $action ){
	if( buddyboss_media_is_single_album() ){
		$album_id = buddyboss_media_single_album_id();
		bp_activity_update_meta( $activity->id, 'buddyboss_media_album_id', $album_id );
		//also update count of photos in album
		global $wpdb;
		$existing_count = $wpdb->get_var( $wpdb->prepare( "SELECT total_items FROM {$wpdb->prefix}buddyboss_media_albums WHERE id=%d", $album_id ) );
		if( !$existing_count || is_wp_error($existing_count)){
			$existing_count = 0;
		}

		//now that bulk upload is enabled, one can upload more than one photo at once.
		//increase existing count by number of photos uploaded
		$existing_count += count( $attachment_ids );

		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}buddyboss_media_albums SET total_items=%d WHERE id=%d", $existing_count, $album_id ) );
		//Update buddyboss_media table
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}buddyboss_media SET album_id=%d WHERE activity_id=%d", $album_id, $activity->id ) );
	}
}
add_action( 'buddyboss_media_photo_posted', 'buddyboss_media_activity_add_album_id', 10, 3 );

/**
 * Update photos count in albums when activity(photos) are deleted.
 *
 * @since 2.0.4
 * @param mixed $args
 */
function buddyboss_media_adjust_album_photos_count( $args ){
	$activity_ids = (array)$args['id'];
	if( empty( $activity_ids ) ){
		return;
	}

	/**
	 * get album ids for these activities and record how much should be deducted from album count of all those albums
	 */
	$albums_count_decrease = array();
	global $wpdb, $bp;
	foreach( $activity_ids as $activity_id ){
		$album_id = (int)bp_activity_get_meta( $activity_id, 'buddyboss_media_album_id' );
		if( $album_id ){
			$count = isset( $albums_count_decrease[$album_id] ) ? (int) $albums_count_decrease[$album_id] : 0;

			//how many photos were there in activity?
			$attachment_ids = bp_activity_get_meta( $activity_id, 'buddyboss_media_aid' );
			$attachment_ids = maybe_unserialize( $attachment_ids );

			if( !empty( $attachment_ids ) ){
				$count += count( $attachment_ids );
				$albums_count_decrease[$album_id] = $count;
			}
		}
	}

	if( empty( $albums_count_decrease ) ){
		return;
	}

	$updatable_albums = array();
	foreach( $albums_count_decrease as $album_id=>$total_items ){
		$updatable_albums[] = $album_id;
	}

	$album_photos_count = array();
	/**
	 * fetch current photos count for each of those albums
	 */
	$existing_records = $wpdb->get_results( "SELECT id, total_items FROM {$wpdb->prefix}buddyboss_media_albums WHERE id IN (". implode( ',', $updatable_albums ) .")" );
	if( empty( $existing_records ) ){
		return;
	}

	foreach( $existing_records as $existing_record ){
		$existing_count = $existing_record->total_items;
		$updated_count = isset( $albums_count_decrease[$existing_record->id] ) ? $existing_count - $albums_count_decrease[$existing_record->id] : $existing_count;
		if( $updated_count<0 )
			$updated_count = 0;

		$album_photos_count[$existing_record->id] = $updated_count;
	}

	//now update album count for each album
	foreach( $album_photos_count as $album_id=>$total_items ){
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}buddyboss_media_albums SET total_items=%d WHERE id=%d", $total_items, $album_id ) );
	}
}
add_action( 'bp_before_activity_delete', 'buddyboss_media_adjust_album_photos_count' );

//add_filter( 'bp_activity_paged_activities_sql', 'buddyboss_media_activity_filter_sql_non_album_only', 10, 2 );
function buddyboss_media_activity_filter_sql_non_album_only( $activity_ids_sql, $r ){
	if( buddyboss_media_is_media_listing() && !buddyboss_media_is_single_album() ){
		if( isset( $r['include'] ) && !empty( $r['include'] ) )
			return $activity_ids_sql;
		//add the JOIN clause
		$pos = strpos( $activity_ids_sql, "WHERE a.is_spam = 0" );
		if( $pos!==false ){
			$join_clause = sprintf( " LEFT JOIN %s AS bbamt ON (a.id = bbamt.activity_id AND bbamt.meta_key = 'buddyboss_media_album_id') ", buddypress()->activity->table_name_meta );
			$activity_ids_sql = str_replace( "WHERE a.is_spam = 0", $join_clause . "WHERE a.is_spam = 0", $activity_ids_sql );

			//add WHERE condition
			$where_condition = " AND bbamt.activity_id IS NULL ";
			$activity_ids_sql = str_replace( "ORDER BY", $where_condition . "ORDER BY", $activity_ids_sql);
		}
	}
	return $activity_ids_sql;
}

add_filter( 'bp_activity_paged_activities_sql', 'buddyboss_media_activity_filter_accessible_albums_only', 10, 2 );
/**
 * Filter global media page listing, or 'uploads' listing on user profile(not for a single album).
 * Display photos :
 *	 - that are not associated with any album.
 *   - that are associated with albums which is accessible to current user, based on privacy setting of album.
 *
 * @param string $activity_ids_sql
 * @param mixed $r
 *
 * @since 2.0.3
 *
 * @return string updated sql query
 */
function buddyboss_media_activity_filter_accessible_albums_only( $activity_ids_sql, $r ){
	if( buddyboss_media_is_media_listing() && !buddyboss_media_is_single_album() ){
		if( isset( $r['include'] ) && !empty( $r['include'] ) )
			return $activity_ids_sql;

		//add the JOIN clause
		$pos = strpos( $activity_ids_sql, "WHERE a.is_spam = 0" );
		if( $pos!==false ){
			$join_clause = sprintf( " LEFT JOIN %s AS bbamt ON (a.id = bbamt.activity_id AND bbamt.meta_key = 'buddyboss_media_album_id') ", buddypress()->activity->table_name_meta );
			$activity_ids_sql = str_replace( "WHERE a.is_spam = 0", $join_clause . "WHERE a.is_spam = 0", $activity_ids_sql );

			//get all accessible albums
			$accessible_album_ids = buddyboss_media_get_accessible_albums();

			$filters = array();

			//1. photo should not be associated with any album
			$filters[] = "bbamt.activity_id IS NULL";
			if( !empty( $accessible_album_ids ) ){
				//1. or if it is, that album should be accessible to current user
				$filters[] = "bbamt.meta_value IN ( " . implode( ',', $accessible_album_ids ) . ")";
				/* @todo: is it a performance isssue if there are 1000's of albums ? */
			}

			//add WHERE condition
			$where_condition = " AND (". implode( ' OR ', $filters ) .") ";
			$activity_ids_sql = str_replace( "ORDER BY", $where_condition . "ORDER BY", $activity_ids_sql);
		}
	}
	return $activity_ids_sql;
}

/**
 * Get albums ids which are accessible to given user.
 *
 * @since 2.0.3
 * @global type $wpdb
 *
 * @param int $user_id Default Loggedin User Id. If no user is supplied, and no user is loggedin, all non-public albums are returned.
 *
 * @return array
 */
function buddyboss_media_get_accessible_albums( $user_id='' ){
	if( !$user_id )
		$user_id = bp_loggedin_user_id();

	global $wpdb;
	$albums_sql = "SELECT al.id FROM " . $wpdb->prefix . "buddyboss_media_albums al ";

	$where = array();

	$privacy_protected = array( 'private', 'friends', 'members' );
	if( is_user_logged_in() ){
		//since user is logged in, we can display albums set to 'members' as privacy level
		unset( $privacy_protected[array_search( 'members', $privacy_protected)]);
	}
	$where[] = "al.privacy NOT IN ( '" . implode( "','", $privacy_protected ) . "') ";

	if( is_user_logged_in() ){
		//or user's own private albums
		$where[] = "( al.privacy ='private' AND al.user_id={$user_id} )";

		if( bp_is_active('friends') ){
			//or 'friends only' albums of users who are my friend
			$my_friend_ids = BP_Friends_Friendship::get_friend_user_ids( $user_id );
			if( !empty( $my_friend_ids ) ){
				$my_friend_ids_csv = implode( ",", $my_friend_ids );
				$where[] = "( al.privacy='friends' AND al.user_id IN ( {$my_friend_ids_csv} ) )";
			}
		}
	}

	$albums_sql .= " WHERE " . implode( " OR ", $where );

	return $wpdb->get_col( $albums_sql );
}

/**
 * Filter activity stream to display only those photos which are not associated with any album.
 *
 * @param type $query_string
 * @param type $object
 * @return type
 */
function buddyboss_media_activity_media_non_album_only( $query_string = '', $object = '' ) {
    if( $object != 'activity' )
        return $query_string;

	if( buddyboss_media_is_single_album() )
		return $query_string;

	/**
	 * Are we on 'all media' page or 'photos' section of user profile ?
	 * If so, restrict activity stream to only media uploads
	 */
	if( buddyboss_media_is_media_listing() ){

		$args = wp_parse_args( $query_string, array( ) );

		if( !isset( $args['meta_query'] ) || !is_array( $args['meta_query'] ) )
			$args['meta_query'] = array();
		/**
		* @todo:
		*		currently global media page shows all media uploads not associated with any album.
		*		Ideally it should also show photos uploaded in 'public' (and 'members only' if user is loggedin) albums.
		*		But this involves
		*			1. Either, a set of dirty manipulations of activity query sql
		*			2. Or, fetching and passing all public album activity ids into activity query
		*				but that can be messy if there are thousands of uploaded images
		*
		*		buddyboss_media_activity_media_public_only_unfinished is a potential solution
		*
		* UNDECIDED FOR NOW
		*/
		$args['meta_query'][] = array(
			'key'     => 'buddyboss_media_album_id',
			'value'   => '10',//random, not really required
			'compare' => 'NOT EXISTS',
		);

		$query_string = empty( $args ) ? $query_string : $args;
	}

	return $query_string;
}
//add_filter( 'bp_ajax_querystring', 'buddyboss_media_activity_media_non_album_only', 13, 2 );

/**
 * Filter activities to show photos from public albums only when we are on global media page.
 * @global type $wpdb
 * @param array $where_conditions
 * @return array
 */
function buddyboss_media_activity_media_public_only_unfinished( $where_conditions ){

	if( buddyboss_media()->option('all-media-page') && is_page( buddyboss_media()->option('all-media-page') ) ){
		$public_album_ids = array();
		global $wpdb;

		$privacy = array( 'public' );
		if( is_user_logged_in() ){
			$privacy[] = 'members';
		}
		$privacy = implode( "','", $privacy );

		$public_album_ids = $wpdb->get_col( "SELECT id from {$wpdb->prefix}buddyboss_media_albums WHERE privacy IN ( '{$privacy}' )" );
		if( !$public_album_ids || is_wp_error( $public_album_ids ) )
			$public_album_ids = array(0);//dont display any images !!

		//$where = "( {$wpdb->base_prefix}bp_activity_meta.meta_key='buddyboss_media_album_id' AND"
		$args['meta_query'][] = array(
			'key'     => 'buddyboss_media_album_id',
			'value'   => $public_album_ids,
			'compare' => 'IN',
		);
	}
	return $where_conditions;
}
//add_filter( 'bp_activity_get_where_conditions', 'buddyboss_media_activity_media_public_only' );

/**
 * Determine if the current user can move an activity media item.
 *
 * @param object $activity Optional. Falls back on the current item in the loop.
 * @return bool True if can move, false otherwise.
 */
function buddyboss_media_user_can_move_media( $activity = false ) {
	global $activities_template, $bp, $current_user, $wpdb;

	// Try to use current activity if none was passed
	if ( empty( $activity ) && ! empty( $activities_template->activity ) ) {
		$activity = $activities_template->activity;
	}

	// Assume the user cannot delete the activity item
	$can_move = false;

	// Only logged in users can delete activity
	if ( is_user_logged_in() ) {
		//first lets check if it is a buddyboss-media upload activity or not
		//presence of this activity meta ensures that current acrivity is indeed a buddyboss-media upload
        if( buddyboss_media_compat_get_meta( $activity->id, 'activity.action_keys' ) ){
			if ( isset( $activity->user_id ) && ( (int) $activity->user_id === bp_loggedin_user_id() ) ) {
				$can_move = true;
			}
		}

        //now lets check for single group media entries
		if ( $can_move && ! empty( $bp->groups->current_group->id ) ) {
            //any user who is a member of current
			$can_move = groups_is_user_member( $current_user->ID, (int)$bp->groups->current_group->id );
		}
	}

	return (bool) apply_filters( 'buddyboss_media_user_can_move_media', $can_move, $activity );
}

/**
 *  Check whether a current user can access the album
 * @return bool
 */
function buddyboss_media_user_can_access_album() {
	global $buddyboss_media_albums_template, $wpdb;

	$group_id  = $buddyboss_media_albums_template->album->group_id;

	//Always return true if this is not a group album
	if ( ! $group_id ) {
		return true;
	}

	//Retrieve group status -> public, private, hidden
	$sql            = $wpdb->prepare("SELECT status FROM {$wpdb->base_prefix}bp_groups WHERE id = %d", $group_id );
	$group_status   = $wpdb->get_var( $sql );
	$is_allowed     = false;

	if ( 'public' == $group_status ) {
		$is_allowed = true;
	} else {

		// Check whether a user is a member of a given group if group is not public
		$user_id = get_current_user_id();
		if ( groups_is_user_member( $user_id, $group_id ) ) {
			$is_allowed = true;
		}
	}

	return $is_allowed;
}

function buddyboss_media_ajax_move_media(){
	global $wpdb, $bp, $current_user;

	check_ajax_referer( 'bboss_media_move_media', 'bboss_media_move_media_nonce' );

	$retval = array(
		'status'	=> false,
		'message'	=> __( 'Something went wrong!', 'buddyboss-media' ),
	);

	$new_album_id = isset( $_POST['buddyboss_media_move_media_albums'] ) ? $_POST['buddyboss_media_move_media_albums'] : '';
	$activity_id = isset( $_POST['activity_id'] ) ? $_POST['activity_id'] : '';

	if( empty( $activity_id ) ){
		die( json_encode( $retval ) );
	}

	//only group member can move media to the albums
	if ( ! empty( $bp->groups->current_group->id ) ) {

		//make sure user is the group member
		$is_group_member = groups_is_user_member( $current_user->ID, $bp->groups->current_group->id );
		if ( false === $is_group_member ) {
			$retval['message'] = __( 'Access denied!', 'buddyboss-media' );
			die( json_encode( $retval ) );
		}

	//only activity author can move media to the albums
	} else {

		//make sure user is author of activity as well as album
		$activity_author = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$bp->activity->table_name} WHERE id=%d", $activity_id ) );
		if( bp_loggedin_user_id()!=$activity_author ){
			$retval['message'] = __( 'Access denied!', 'buddyboss-media' );
			die( json_encode( $retval ) );
		}

		//make sure user is author of album
		if ( $new_album_id ) {
			$album_author = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}buddyboss_media_albums WHERE id=%d", $new_album_id ) );
			if( bp_loggedin_user_id()!=$album_author ){
				$retval['message'] = __( 'Access denied!', 'buddyboss-media' );
				die( json_encode( $retval ) );
			}
		}
	}


	//everything is validated. Proceed
	/**
	 * 1. decrease total items count of old album by 1
	 * 2. if new album selected
	 *		 - assign photo to new album
	 *		 - increase total items count of new album
	 *    else
	 *		 - remove activity meta
	 */
	$old_album_id = (int) bp_activity_get_meta( $activity_id, 'buddyboss_media_album_id', true );
	if( $old_album_id ){
		$items_count = (int)$wpdb->get_var( $wpdb->prepare( "SELECT total_items FROM {$wpdb->prefix}buddyboss_media_albums WHERE id=%d", $old_album_id ) );
		if( $items_count ){
			$items_count--;
			$wpdb->update(
				$wpdb->prefix . 'buddyboss_media_albums',
				array(
					'total_items' => $items_count,
				),
				array( 'id' => $old_album_id ),
				array(
					'%d'
				),
				array( '%d' )
			);
		}
	}

	if( $new_album_id ){
		bp_activity_update_meta( $activity_id, 'buddyboss_media_album_id', $new_album_id );
		$items_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT total_items FROM {$wpdb->prefix}buddyboss_media_albums WHERE id = %d", $new_album_id ) );
		$photo_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$wpdb->prefix}buddyboss_media WHERE activity_id = %d", $activity_id ) );

		// Generate new album photos count
		$items_count = $items_count + $photo_count;

		$wpdb->update(
			$wpdb->prefix . 'buddyboss_media_albums',
			array(
				'total_items' => $items_count,
			),
			array( 'id' => $new_album_id ),
			array(
				'%d'
			),
			array( '%d' )
		);

		//Update album id in buddyboss_media table
		$wpdb->update(
			$wpdb->prefix . 'buddyboss_media',
			array(
				'album_id' => $new_album_id,
			),
			array( 'activity_id' => $activity_id ),
			array(
				'%d'
			),
			array( '%d' )
		);

	} else {
		bp_activity_delete_meta( $activity_id, 'buddyboss_media_album_id' );

		//Null album id in buddyboss_media table
		$wpdb->update(
			$wpdb->prefix . 'buddyboss_media',
			array(
				'album_id' => NULL,
			),
			array( 'activity_id' => $activity_id ),
			array(
				'%d'
			),
			array( '%d' )
		);

	}

	$retval = array(
		'status'	=> true,
		'message'	=> __( 'Your changes saved!.', 'buddyboss-media' ),
	);
	die( json_encode( $retval ) );
}
add_action( 'wp_ajax_buddyboss_media_move_media', 'buddyboss_media_ajax_move_media' );

/**
 * Filter the canonical redirect URL for Global Photos Page
 * @param $redirect
 * @return bool
 */
function buddyboss_media_disable_canonical_front_page( $redirect ) {

	if ( is_page() && $front_page = buddyboss_media()->option('all-media-page') ) { //Check we are on global photos page
		if ( null != $front_page && is_page( $front_page ) )
			$redirect = false;
	}

	return $redirect;
}

add_filter( 'redirect_canonical', 'buddyboss_media_disable_canonical_front_page' );

add_action( 'wp', 'buddyboss_media_set_global_page_cookie' );

/**
 * Set `bp-bboss-is-media-page` cookie value `yes` when Global Photos page load
 * It's only required for nouveau tp
 */
function buddyboss_media_set_global_page_cookie() {

	if ( buddyboss_media()->option('all-media-page')
		&& is_page( buddyboss_media()->option('all-media-page') ) ) {
		@setcookie( 'bp-bboss-is-media-page', 'yes', 0, SITECOOKIEPATH, null );
	} else {
		@setcookie( 'bp-bboss-is-media-page', null, -1, SITECOOKIEPATH, null  );
	}
}