<?php
/**
 * BuddyBoss Media Template Functions.
 *
 * @package BuddyBoss\Media\Templates
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the media component slug.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_slug() {
	echo bp_get_media_slug();
}
/**
 * Return the media component slug.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_get_media_slug() {

	/**
	 * Filters the media component slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $slug Media component slug.
	 */
	return apply_filters( 'bp_get_media_slug', buddypress()->media->slug );
}

/**
 * Output the media component root slug.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_root_slug() {
	echo bp_get_media_root_slug();
}
/**
 * Return the media component root slug.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_get_media_root_slug() {

	/**
	 * Filters the Media component root slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $slug Media component root slug.
	 */
	return apply_filters( 'bp_get_media_root_slug', buddypress()->media->root_slug );
}

/**
 * Initialize the media loop.
 *
 * Based on the $args passed, bp_has_media() populates the
 * $media_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of media items.
 *
 * @since BuddyBoss 1.0.0

 * @global object $media_template {@link BP_Media_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the media loop. Most arguments
 *     are in the same format as {@link BP_Media::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_media() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'user_id=4&fields=all').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           Media fields to retrieve. 'all' to fetch entire media objects,
 *                                               'ids' to get only the media IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total media items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of media IDs to exclude. Default: false.
 *     @type array|bool        $include          Array of exact media IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single media item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 *     @type string            $scope            Use a BuddyPress pre-built filter.
 *                                                 - 'friends' retrieves items belonging to the friends of a user.
 *                                                 - 'groups' retrieves items belonging to groups to which a user belongs to.
 *                                               defaults to false.
 *     @type int|array|bool    $user_id          The ID(s) of user(s) whose media should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a user profile page, 'user_id' defaults to
 *                                               the ID of the displayed user. Otherwise the default is false.
 *     @type int|array|bool    $album_id         The ID(s) of album(s) whose media should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a single album page, 'album_id' defaults to
 *                                               the ID of the displayed album. Otherwise the default is false.
 *     @type int|array|bool    $group_id         The ID(s) of group(s) whose media should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a single group page, 'group_id' defaults to
 *                                               the ID of the displayed group. Otherwise the default is false.
 *     @type array             $privacy          Limit results by privacy. Default: public | grouponly.
 * }
 * @return bool Returns true when media found, otherwise false.
 */
function bp_has_media( $args = '' ) {
	global $media_template;

	$args = bp_parse_args( $args );

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id()
		? bp_displayed_user_id()
		: false;

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'media' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	// Album filtering.
	if ( ! isset( $args['album_id'] ) ) {
		$album_id = bp_is_single_album() ? (int) bp_action_variable( 0 ) : false;
	} else {
		$album_id = ( isset( $args['album_id'] ) ? $args['album_id'] : false );
	}

	if ( $album_id && ( bp_is_profile_albums_support_enabled() || bp_is_group_albums_support_enabled() ) && ( bp_is_active( 'video' ) && ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() ) ) ) {
		$args['video'] = true;
	}

	$group_id = false;
	$privacy  = false;
	if (
		bp_is_active( 'groups' ) &&
		bp_is_group() &&
		(
			! isset( $_GET['action'] ) ||
			'bp_search_ajax' !== $_GET['action']
		)
	) {
		$group_id = bp_get_current_group_id();
		$privacy  = array( 'grouponly' );
		if ( bp_is_active( 'forums' ) && ( bbp_is_forum_edit() || bbp_is_topic_edit() || bbp_is_reply_edit() ) ) {
			$privacy = false;
		}
		$user_id = false;
	}

	// The default scope should recognize custom slugs.
	$scope = ( isset( $_REQUEST['scope'] ) && ! empty( $_REQUEST['scope'] ) ? $_REQUEST['scope'] : 'all' );
	$scope = ( isset( $args['scope'] ) && ! empty( $args['scope'] ) ? $args['scope'] : $scope );

	$scope = bp_media_default_scope( trim( $scope ) );

	if ( isset( $args ) && isset( $args['scope'] ) ) {
		unset( $args['scope'] );
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'include'      => false,           // Pass an media_id or string of IDs comma-separated.
			'exclude'      => false,           // Pass an activity_id or string of IDs comma-separated.
			'sort'         => 'DESC',          // Sort DESC or ASC.
			'order_by'     => false,           // Order by. Default: date_created.
			'page'         => 1,               // Which page to load.
			'per_page'     => 20,              // Number of items per page.
			'page_arg'     => 'acpage',        // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'          => false,           // Max number to return.
			'fields'       => 'all',
			'count_total'  => false,

			// Scope - pre-built media filters for a user (friends/groups).
			'scope'        => $scope,

			// Filtering.
			'user_id'      => $user_id,        // user_id to filter on.
			'album_id'     => $album_id,       // album_id to filter on.
			'group_id'     => $group_id,       // group_id to filter on.
			'privacy'      => $privacy,        // privacy to filter on - public, onlyme, loggedin, friends, grouponly, message.
			'video'        => false,            // Whether to include videos.

		// Searching.
			'search_terms' => $search_terms_default,
		),
		'has_media'
	);

	// Search terms.
	if ( ! empty( $_REQUEST['s'] ) && empty( $r['search_terms'] ) ) {
		$r['search_terms'] = $_REQUEST['s'];
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */
	$media_template = new BP_Media_Template( $r );

	/**
	 * Filters whether or not there are media items to display.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool   $value               Whether or not there are media items to display.
	 * @param string $media_template      Current media template being used.
	 * @param array  $r                   Array of arguments passed into the BP_Media_Template class.
	 */
	return apply_filters( 'bp_has_media', $media_template->has_media(), $media_template, $r );
}

/**
 * Determine if there are still media left in the loop.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return bool Returns true when media are found.
 */
function bp_media() {
	global $media_template;
	return $media_template->user_medias();
}

/**
 * Get the current media object in the loop.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return object The current media within the loop.
 */
function bp_the_media() {
	global $media_template;
	return $media_template->the_media();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyPress 2.1.0
 */
function bp_media_load_more_link() {
	echo esc_url( bp_get_media_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since BuddyPress 2.1.0
 *
 * @return string $link
 */
function bp_get_media_load_more_link() {
	global $media_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $media_template->pag_arg, $media_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $media_template The media template loop global.
	 */
	return apply_filters( 'bp_get_media_load_more_link', $link, $url, $media_template );
}

/**
 * Output the media pagination count.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 */
function bp_media_pagination_count() {
	echo bp_get_media_pagination_count();
}

/**
 * Return the media pagination count.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The pagination text.
 */
function bp_get_media_pagination_count() {
	global $media_template;

	$start_num = intval( ( $media_template->pag_page - 1 ) * $media_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $media_template->pag_num - 1 ) > $media_template->total_media_count ) ? $media_template->total_media_count : $start_num + ( $media_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $media_template->total_media_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $media_template->total_media_count, 'buddyboss' ), $from_num, $to_num, $total );

	return $message;
}

/**
 * Output the media pagination links.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_pagination_links() {
	echo bp_get_media_pagination_links();
}

/**
 * Return the media pagination links.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The pagination links.
 */
function bp_get_media_pagination_links() {
	global $media_template;

	/**
	 * Filters the media pagination link output.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $pag_links Output for the media pagination links.
	 */
	return apply_filters( 'bp_get_media_pagination_links', $media_template->pag_links );
}

/**
 * Return true when there are more media items to be shown than currently appear.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_media_has_more_items() {
	global $media_template;

	if ( ! empty( $media_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $media_template->pag_page ) ) {
			$remaining_pages = floor( ( $media_template->total_media_count - 1 ) / ( $media_template->pag_num * $media_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more media items to display.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $has_more_items Whether or not there are more media items to display.
	 */
	return apply_filters( 'bp_media_has_more_items', $has_more_items );
}

/**
 * Output the media count.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_count() {
	echo bp_get_media_count();
}

/**
 * Return the media count.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media count.
 */
function bp_get_media_count() {
	global $media_template;

	/**
	 * Filters the media count for the media template.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $media_count The count for total media.
	 */
	return apply_filters( 'bp_get_media_count', (int) $media_template->media_count );
}

/**
 * Output the number of media per page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_per_page() {
	echo bp_get_media_per_page();
}

/**
 * Return the number of media per page.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media per page.
 */
function bp_get_media_per_page() {
	global $media_template;

	/**
	 * Filters the media posts per page value.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_media_per_page', (int) $media_template->pag_num );
}

/**
 * Output the media ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_id() {
	echo bp_get_media_id();
}

/**
 * Return the media ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media ID.
 */
function bp_get_media_id() {
	global $media_template;

	/**
	 * Filters the media ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media ID.
	 */
	return apply_filters( 'bp_get_media_id', $media_template->media->id );
}

/**
 * Output the media blog id.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_blog_id() {
	echo bp_get_media_blog_id();
}

/**
 * Return the media blog ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media blog ID.
 */
function bp_get_media_blog_id() {
	global $media_template;

	/**
	 * Filters the media ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media blog ID.
	 */
	return apply_filters( 'bp_get_media_blog_id', $media_template->media->blog_id );
}

/**
 * Output the media user ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_user_id() {
	echo bp_get_media_user_id();
}

/**
 * Return the media user ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media user ID.
 */
function bp_get_media_user_id() {
	global $media_template;

	/**
	 * Filters the media ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media user ID.
	 */
	return apply_filters( 'bp_get_media_user_id', $media_template->media->user_id );
}

/**
 * Output the Media author name.
 *
 * @since BuddyBoss 1.5.3
 */
function bp_media_author() {
	echo bp_get_media_author();
}

/**
 * Return the Media author name.
 *
 * @since BuddyBoss 1.5.3
 *
 * @global object $media_template {@link \BP_Media_Template}
 *
 * @return int The Media author name.
 */
function bp_get_media_author() {
	global $media_template;

	if ( isset( $media_template ) && isset( $media_template->media ) && isset( $media_template->media->user_id ) ) {
		$author = bp_core_get_user_displayname( $media_template->media->user_id );
	}

	/**
	 * Filters the Media author name being displayed.
	 *
	 * @since BuddyBoss 1.5.3
	 *
	 * @param int $id The Media author id.
	 */
	return apply_filters( 'bp_get_media_author', $author );
}

/**
 * Output the media attachment ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_attachment_id() {
	echo bp_get_media_attachment_id();
}

/**
 * Return the media attachment ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media attachment ID.
 */
function bp_get_media_attachment_id() {
	global $media_template;

	/**
	 * Filters the media ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media attachment ID.
	 */
	return apply_filters( 'bp_get_media_attachment_id', $media_template->media->attachment_id );
}

/**
 * Output the media title.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_title() {
	echo bp_get_media_title();
}

/**
 * Return the media title.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media title.
 */
function bp_get_media_title() {
	global $media_template;

	/**
	 * Filters the media title being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media title.
	 */
	return apply_filters( 'bp_get_media_title', $media_template->media->title );
}

/**
 * Determine if the current user can delete an media item.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int|BP_Media $media BP_Media object or ID of the media
 * @return bool True if can delete, false otherwise.
 */
function bp_media_user_can_delete( $media = false ) {

	// Assume the user cannot delete the media item.
	$can_delete = false;

	if ( empty( $media ) ) {
		return $can_delete;
	}

	if ( ! is_object( $media ) ) {
		$media = new BP_Media( $media );
	}

	if ( empty( $media ) ) {
		return $can_delete;
	}

	// Only logged in users can delete media.
	if ( is_user_logged_in() ) {

		// Community moderators can always delete media (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_delete = true;
		}

		// Users are allowed to delete their own media.
		if ( isset( $media->user_id ) && ( $media->user_id === bp_loggedin_user_id() ) ) {
			$can_delete = true;
		}

		if ( bp_is_active( 'groups' ) && $media->group_id > 0 ) {
			$manage   = groups_can_user_manage_media( bp_loggedin_user_id(), $media->group_id );
			$is_admin = groups_is_user_admin( bp_loggedin_user_id(), $media->group_id );
			$is_mod   = groups_is_user_mod( bp_loggedin_user_id(), $media->group_id );
			if ( $manage ) {
				$can_delete = true;
			} elseif ( $is_mod || $is_admin ) {
				$can_delete = true;
			}
		}
	}

	/**
	 * Filters whether the current user can delete an media item.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param bool   $can_delete Whether the user can delete the item.
	 * @param object $media   Current media item object.
	 */
	return (bool) apply_filters( 'bp_media_user_can_delete', $can_delete, $media );
}

/**
 * Output the media album ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_album_id() {
	echo bp_get_media_album_id();
}

/**
 * Return the media album ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media album ID.
 */
function bp_get_media_album_id() {
	global $media_template;

	/**
	 * Filters the media album ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media album ID.
	 */
	return apply_filters( 'bp_get_media_album_id', $media_template->media->album_id );
}

/**
 * Output the media group ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_media_group_id() {
	echo bp_get_media_group_id();
}

/**
 * Return the media group ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media group ID.
 */
function bp_get_media_group_id() {
	global $media_template;

	/**
	 * Filters the media group ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The media group ID.
	 */
	return apply_filters( 'bp_get_media_group_id', $media_template->media->group_id );
}

/**
 * Output the media activity ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_activity_id() {
	echo bp_get_media_activity_id();
}

/**
 * Return the media activity ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media activity ID.
 */
function bp_get_media_activity_id() {
	global $media_template;

	/**
	 * Filters the media activity ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media activity ID.
	 */
	return apply_filters( 'bp_get_media_activity_id', $media_template->media->activity_id );
}

/**
 * Output the media date created.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_date_created() {
	echo bp_get_media_date_created();
}

/**
 * Return the media date created.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media date created.
 */
function bp_get_media_date_created() {
	global $media_template;

	/**
	 * Filters the media date created being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string The date created.
	 */
	return apply_filters( 'bp_get_media_date_created', $media_template->media->date_created );
}

/**
 * Output the media attachment thumbnail.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_attachment_image_thumbnail() {
	echo bp_get_media_attachment_image_thumbnail();
}

/**
 * Return the media attachment thumbnail.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media attachment thumbnail url.
 */
function bp_get_media_attachment_image_thumbnail() {
	global $media_template;

	/**
	 * Filters the media thumbnail being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string The media thumbnail.
	 */
	return apply_filters( 'bp_get_media_attachment_image', $media_template->media->attachment_data->thumb );
}

/**
 * Output the media attachment activity thumbnail.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_attachment_image_activity_thumbnail() {
	echo bp_get_media_attachment_image_activity_thumbnail();
}

/**
 * Return the media attachment activity thumbnail.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media attachment thumbnail url.
 */
function bp_get_media_attachment_image_activity_thumbnail() {
	global $media_template;

	/**
	 * Filters the media activity thumbnail being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string The media activity thumbnail.
	 */
	return apply_filters( 'bp_get_media_attachment_image', $media_template->media->attachment_data->activity_thumb );
}

/**
 * Output the media attachment.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_attachment_image() {
	echo esc_url( bp_get_media_attachment_image() );
}

/**
 * Return the media attachment.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media attachment url.
 */
function bp_get_media_attachment_image() {
	global $media_template;

	/**
	 * Filters the media image being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string The full image.
	 */
	return apply_filters( 'bp_get_media_attachment_image', $media_template->media->attachment_data->full );
}

/**
 * Output media directory permalink.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_directory_permalink() {
	echo esc_url( bp_get_media_directory_permalink() );
}
/**
 * Return media directory permalink.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_get_media_directory_permalink() {

	/**
	 * Filters the media directory permalink.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Media directory permalink.
	 */
	return apply_filters( 'bp_get_media_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_media_root_slug() ) );
}

/**
 * Output the media privacy.
 *
 * @since BuddyBoss 1.2.3
 */
function bp_media_privacy() {
	echo bp_get_media_privacy();
}

/**
 * Return the media privacy.
 *
 * @since BuddyBoss 1.2.3
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media privacy.
 */
function bp_get_media_privacy() {
	global $media_template;

	/**
	 * Filters the media privacy being displayed.
	 *
	 * @since BuddyBoss 1.2.3
	 *
	 * @param string $id The media privacy.
	 */
	return apply_filters( 'bp_get_media_privacy', $media_template->media->privacy );
}

/**
 * Output the media visibility.
 *
 * @since BuddyBoss 1.2.3
 */
function bp_media_visibility() {
	echo bp_get_media_visibility();
}

/**
 * Return the media visibility.
 *
 * @since BuddyBoss 1.2.3
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media visibility.
 */
function bp_get_media_visibility() {
	global $media_template;

	/**
	 * Filters the media privacy being displayed.
	 *
	 * @since BuddyBoss 1.2.3
	 *
	 * @param string $id The media privacy.
	 */
	return apply_filters( 'bp_get_media_visibility', $media_template->media->visibility );
}

/**
 * Output the media parent activity id.
 *
 * @since BuddyBoss 1.2.0
 */
function bp_media_parent_activity_id() {
	echo bp_get_media_parent_activity_id();
}

/**
 * Return the media parent activity id.
 *
 * @since BuddyBoss 1.2.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return int The media parent activity id.
 */
function bp_get_media_parent_activity_id() {
	global $media_template;

	/**
	 * Filters the media parent activity id.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param int $id The media parent activity id.
	 */
	return apply_filters( 'bp_get_media_privacy', get_post_meta( $media_template->media->attachment_id, 'bp_media_parent_activity_id', true ) );
}

/**
 * Return the media link.
 *
 * @since BuddyBoss 1.5.3
 *
 * @return string The media link.
 * @global object $media_template {@link \BP_Media_Template}
 */
function bp_get_media_link() {
	global $media_template;

	if ( ! empty( $media_template->media->group_id ) ) {
		$group = buddypress()->groups->current_group;
		if ( ! isset( $group->id ) || $group->id !== $media_template->media->group_id ) {
			$group = groups_get_group( $media_template->media->group_id );
		}
		$group_link = bp_get_group_permalink( $group );
		$url        = trailingslashit( $group_link . bp_get_media_slug() );
	} else {
		$url = trailingslashit( bp_core_get_user_domain( bp_get_media_user_id() ) . bp_get_media_slug() );
	}

	/**
	 * Filters the media link being displayed.
	 *
	 * @since BuddyBoss 1.5.3
	 *
	 * @param int $id The media link.
	 */
	return apply_filters( 'bp_get_media_link', $url );
}

// ****************************** Media Albums *********************************//

/**
 * Initialize the album loop.
 *
 * Based on the $args passed, bp_has_albums() populates the
 * $media_album_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of media album items.
 *
 * @since BuddyBoss 1.0.0

 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the media loop. Most arguments
 *     are in the same format as {@link BP_Media_Album::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_media() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'author_id=4&privacy=public').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           Activity fields to retrieve. 'all' to fetch entire media objects,
 *                                               'ids' to get only the media IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total media items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of media IDs to exclude. Default: false.
 *     @type array|bool        $include          Array of exact media IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single media item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 *     @type int|array|bool    $user_id          The ID(s) of user(s) whose media should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a user profile page, 'user_id' defaults to
 *                                               the ID of the displayed user. Otherwise the default is false.
 *     @type int|array|bool    $group_id         The ID(s) of group(s) whose media should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a group page, 'group_id' defaults to
 *                                               the ID of the displayed group. Otherwise the default is false.
 *     @type array             $privacy          Limit results by a privacy. Default: public | grouponly.
 * }
 * @return bool Returns true when media found, otherwise false.
 */
function bp_has_albums( $args = '' ) {
	global $media_album_template;

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id()
		? bp_displayed_user_id()
		: false;

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'album' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	$privacy = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
		if ( bp_is_active( 'friends' ) ) {

			// get the login user id.
			$current_user_id = get_current_user_id();

			// check if the login user is friends of the display user.
			$is_friend = friends_check_friendship( $current_user_id, $user_id );

			/**
			 * check if the login user is friends of the display user
			 * OR check if the login user and the display user is the same
			 */
			if ( $is_friend || ! empty( $current_user_id ) && $current_user_id == $user_id ) {
				$privacy[] = 'friends';
			}
		}

		if ( bp_is_my_profile() ) {
			$privacy[] = 'onlyme';
		}
	}

	$group_id = false;
	if ( bp_is_group() ) {
		$group_id = bp_get_current_group_id();
		$user_id  = false;
		$privacy  = array( 'grouponly' );
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'include'      => false,        // Pass an album_id or string of IDs comma-separated.
			'exclude'      => false,        // Pass an activity_id or string of IDs comma-separated.
			'sort'         => 'DESC',       // Sort DESC or ASC.
			'page'         => 1,            // Which page to load.
			'per_page'     => 20,           // Number of items per page.
			'page_arg'     => 'acpage',     // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'          => false,        // Max number to return.
			'fields'       => 'all',
			'count_total'  => false,

			// Filtering.
			'user_id'      => $user_id,     // user_id to filter on.
			'group_id'     => $group_id,    // group_id to filter on.
			'privacy'      => $privacy,     // privacy to filter on - public, onlyme, loggedin, friends, grouponly.

		// Searching.
			'search_terms' => $search_terms_default,
		),
		'has_albums'
	);

	/*
	 * Smart Overrides.
	 */

	// Search terms.
	if ( ! empty( $_REQUEST['s'] ) && empty( $r['search_terms'] ) ) {
		$r['search_terms'] = $_REQUEST['s'];
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */

	$media_album_template = new BP_Media_Album_Template( $r );

	/**
	 * Filters whether or not there are media albums to display.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool   $value                     Whether or not there are media items to display.
	 * @param string $media_album_template      Current media album template being used.
	 * @param array  $r                         Array of arguments passed into the BP_Media_Album_Template class.
	 */
	return apply_filters( 'bp_has_album', $media_album_template->has_albums(), $media_album_template, $r );
}

/**
 * Determine if there are still album left in the loop.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return bool Returns true when media are found.
 */
function bp_album() {
	global $media_album_template;
	return $media_album_template->user_albums();
}

/**
 * Get the current album object in the loop.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return object The current media within the loop.
 */
function bp_the_album() {
	global $media_album_template;
	return $media_album_template->the_album();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_load_more_link() {
	echo esc_url( bp_get_album_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string $link
 */
function bp_get_album_load_more_link() {
	global $media_album_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $media_album_template->pag_arg, $media_album_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $link                  The "Load More" link URL with appropriate query args.
	 * @param string $url                   The original URL.
	 * @param object $media_album_template  The media album template loop global.
	 */
	return apply_filters( 'bp_get_album_load_more_link', $link, $url, $media_album_template );
}

/**
 * Output the album pagination count.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 */
function bp_album_pagination_count() {
	echo bp_get_album_pagination_count();
}

/**
 * Return the album pagination count.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return string The pagination text.
 */
function bp_get_album_pagination_count() {
	global $media_album_template;

	$start_num = intval( ( $media_album_template->pag_page - 1 ) * $media_album_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $media_album_template->pag_num - 1 ) > $media_album_template->total_album_count ) ? $media_album_template->total_album_count : $start_num + ( $media_album_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $media_album_template->total_album_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $media_album_template->total_media_count, 'buddyboss' ), $from_num, $to_num, $total );

	return $message;
}

/**
 * Output the album pagination links.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_pagination_links() {
	echo bp_get_album_pagination_links();
}

/**
 * Return the album pagination links.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return string The pagination links.
 */
function bp_get_album_pagination_links() {
	global $media_album_template;

	/**
	 * Filters the album pagination link output.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $pag_links Output for the media album pagination links.
	 */
	return apply_filters( 'bp_get_album_pagination_links', $media_album_template->pag_links );
}

/**
 * Return true when there are more album items to be shown than currently appear.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_album_has_more_items() {
	global $media_album_template;

	if ( ! empty( $media_album_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $media_album_template->pag_page ) ) {
			$remaining_pages = floor( ( $media_album_template->total_album_count - 1 ) / ( $media_album_template->pag_num * $media_album_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more album items to display.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $has_more_items Whether or not there are more album items to display.
	 */
	return apply_filters( 'bp_album_has_more_items', $has_more_items );
}

/**
 * Output the album count.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_count() {
	echo bp_get_album_count();
}

/**
 * Return the album count.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return int The album count.
 */
function bp_get_album_count() {
	global $media_album_template;

	/**
	 * Filters the album count for the media album template.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $album_count The count for total album.
	 */
	return apply_filters( 'bp_get_album_count', (int) $media_album_template->album_count );
}

/**
 * Output the number of media album per page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_per_page() {
	echo bp_get_album_per_page();
}

/**
 * Return the number of media album per page.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return int The media album per page.
 */
function bp_get_album_per_page() {
	global $media_album_template;

	/**
	 * Filters the media album posts per page value.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_album_per_page', (int) $media_album_template->pag_num );
}

/**
 * Output the media album ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_id() {
	echo bp_get_album_id();
}

/**
 * Return the album ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return int The media album ID.
 */
function bp_get_album_id() {
	global $media_album_template;

	if ( empty( $media_album_template ) ) {
		return;
	}

	/**
	 * Filters the media ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media album ID.
	 */
	return apply_filters( 'bp_get_album_id', $media_album_template->album->id );
}

/**
 * Output the media album title.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_title() {
	echo bp_get_album_title();
}

/**
 * Return the album title.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return string The media album title.
 */
function bp_get_album_title() {
	global $media_album_template;

	/**
	 * Filters the album title being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media album title.
	 */
	return apply_filters( 'bp_get_album_title', $media_album_template->album->title );
}

/**
 * Return the album privacy.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return string The media album privacy.
 */
function bp_get_album_privacy() {
	global $media_album_template;

	/**
	 * Filters the album privacy being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media album privacy.
	 */
	return apply_filters( 'bp_get_album_privacy', $media_album_template->album->privacy );
}

/**
 * Output the media album ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_link() {
	echo bp_get_album_link();
}

/**
 * Return the album description.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return string The media album description.
 */
function bp_get_album_link() {
	global $media_album_template;

	if ( ! empty( $media_album_template->album->group_id ) ) {
		$group = buddypress()->groups->current_group;
		if ( ! isset( $group->id ) || $group->id !== $media_album_template->album->group_id ) {
			$group = groups_get_group( $media_album_template->album->group_id );
		}
		$group_link = bp_get_group_permalink( $group );
		$url        = trailingslashit( $group_link . 'albums/' . bp_get_album_id() );
	} else {
		$url = trailingslashit( bp_core_get_user_domain( bp_get_album_user_id() ) . bp_get_media_slug() . '/albums/' . bp_get_album_id() );
	}

	/**
	 * Filters the album description being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media album description.
	 */
	return apply_filters( 'bp_get_album_link', $url );
}

/**
 * Determine if the current user can delete an album item.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param int|BP_Media_Album $album BP_Media_Album object or ID of the album
 * @return bool True if can delete, false otherwise.
 */
function bp_album_user_can_delete( $album = false ) {

	// Assume the user cannot delete the album item.
	$can_delete = false;

	if ( empty( $album ) ) {
		return $can_delete;
	}

	if ( ! is_object( $album ) ) {
		$album = new BP_Media_Album( $album );
	}

	if ( empty( $album ) ) {
		return $can_delete;
	}

	// Only logged in users can delete album.
	if ( is_user_logged_in() ) {

		// Groups albums have their own access.
		if ( ! empty( $album->group_id ) && groups_can_user_manage_albums( bp_loggedin_user_id(), $album->group_id ) ) {
			$can_delete = true;

			// Users are allowed to delete their own album.
		} elseif ( isset( $album->user_id ) && bp_loggedin_user_id() === $album->user_id ) {
			$can_delete = true;
		}

		// Community moderators can always delete album (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_delete = true;
		}
	}

	/**
	 * Filters whether the current user can delete an album item.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param bool   $can_delete Whether the user can delete the item.
	 * @param object $album   Current album item object.
	 */
	return (bool) apply_filters( 'bp_album_user_can_delete', $can_delete, $album );
}

/**
 * Output the album user ID.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_album_user_id() {
	echo bp_get_album_user_id();
}

/**
 * Return the album user ID.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global object $media_album_template {@link \BP_Media_Album_Template}
 *
 * @return int The album user ID.
 */
function bp_get_album_user_id() {
	global $media_album_template;

	/**
	 * Filters the album ID being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The album user ID.
	 */
	return apply_filters( 'bp_get_album_user_id', $media_album_template->album->user_id );
}

/**
 * Output the album author name.
 *
 * @since BuddyBoss 1.5.3
 */
function bp_album_author() {
	echo bp_get_album_author();
}

/**
 * Return the album author name.
 *
 * @since BuddyBoss 1.5.3
 *
 * @global object $media_album_template {@link \BP_Media_Album_Template}
 *
 * @return int The album author name.
 */
function bp_get_album_author() {
	global $media_album_template;

	if ( isset( $media_album_template ) && isset( $media_album_template->album ) && isset( $media_album_template->album->user_id ) ) {
		$author = bp_core_get_user_displayname( $media_album_template->album->user_id );
	}

	/**
	 * Filters the album author name being displayed.
	 *
	 * @since BuddyBoss 1.5.3
	 *
	 * @param int $id The album author id.
	 */
	return apply_filters( 'bp_get_album_author', $author );
}

/**
 * Output the album group ID.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_album_group_id() {
	echo bp_get_album_group_id();
}

/**
 * Return the album group ID.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return int The album group ID.
 */
function bp_get_album_group_id() {
	global $media_album_template;

	/**
	 * Filters the album group ID being displayed.
	 *
	 * @since BuddyBoss 1.2.5
	 *
	 * @param int $id The album group ID.
	 */
	return apply_filters( 'bp_get_album_group_id', $media_album_template->album->group_id );
}

/**
 * Output the album privacy.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_album_privacy() {
	echo bp_get_album_privacy();
}

/**
 * Output the album visibility.
 *
 * @since BuddyBoss 1.2.5
 */
function bp_album_visibility() {
	echo bp_get_album_visibility();
}

/**
 * Return the album visibility.
 *
 * @since BuddyBoss 1.2.5
 *
 * @global object $media_album_template {@link BP_Media_Album_Template}
 *
 * @return string The media album visibility.
 */
function bp_get_album_visibility() {
	global $media_album_template;

	/**
	 * Filters the album visibility being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media album visibility.
	 */
	return apply_filters( 'bp_get_album_visibility', $media_album_template->album->visibility );
}

/**
 * Determine if the current user can edit an media item.
 *
 * @param bool $media BP_Media object or ID of the media.
 *
 * @return bool True if can edit, false otherwise.
 * @since BuddyBoss 1.5.6
 */
function bp_media_user_can_edit( $media = false ) {

	// Assume the user cannot edit the document item.
	$can_edit = false;

	if ( empty( $media ) ) {
		return $can_edit;
	}

	if ( ! is_object( $media ) ) {
		$media = new BP_Media( $media );
	}

	if ( empty( $media ) ) {
		return $can_edit;
	}

	// Only logged in users can edit media.
	if ( is_user_logged_in() ) {

		// Community moderators can always edit media (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_edit = true;
		}

		// Users are allowed to edit their own media.
		if ( isset( $media->user_id ) && ( bp_loggedin_user_id() === $media->user_id ) ) {
			$can_edit = true;
		}

		if ( bp_is_active( 'groups' ) && $media->group_id > 0 ) {
			$manage   = groups_can_user_manage_media( bp_loggedin_user_id(), $media->group_id );
			$status   = bp_group_get_media_status( $media->group_id );
			$is_admin = groups_is_user_admin( bp_loggedin_user_id(), $media->group_id );
			$is_mod   = groups_is_user_mod( bp_loggedin_user_id(), $media->group_id );

			if ( $manage ) {
				if ( bp_loggedin_user_id() === $media->user_id ) {
					$can_edit = true;
				} elseif ( 'members' === $status && ( $is_mod || $is_admin ) ) {
					$can_edit = true;
				} elseif ( 'mods' === $status && ( $is_mod || $is_admin ) ) {
					$can_edit = true;
				} elseif ( 'admins' === $status && $is_admin ) {
					$can_edit = true;
				}
			}
		}
	}

	/**
	 * Filters whether the current user can edit an media item.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $can_edit Whether the user can edit the item.
	 * @param object $media   Current media item object.
	 */
	return (bool) apply_filters( 'bp_media_user_can_edit', $can_edit, $media );
}

/**
 * Determine if the current user can edit an album item.
 *
 * @param bool $album BP_Media_Album object or ID of the album.
 *
 * @return bool True if can edit, false otherwise.
 * @since BuddyBoss 1.5.6
 */
function bp_album_user_can_edit( $album = false ) {

	// Assume the user cannot edit the album item.
	$can_edit = false;

	if ( empty( $album ) ) {
		return $can_edit;
	}

	if ( ! is_object( $album ) ) {
		$album = new BP_Media_Album( $album );
	}

	if ( empty( $album ) ) {
		return $can_edit;
	}

	// Only logged in users can edit folder.
	if ( is_user_logged_in() ) {

		// Users are allowed to edit their own album.
		if ( isset( $album->user_id ) && bp_loggedin_user_id() === $album->user_id ) {
			$can_edit = true;
			// Community moderators can always edit album (at least for now).
		} elseif ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_edit = true;
			// Groups medias have their own access.
		} elseif ( ! empty( $album->group_id ) && groups_can_user_manage_media( bp_loggedin_user_id(), $album->group_id ) ) {
			$can_edit = true;
		}
	}

	/**
	 * Filters whether the current user can edit an album item.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $can_edit Whether the user can edit the item.
	 * @param object $album   Current album item object.
	 */
	return (bool) apply_filters( 'bp_album_user_can_edit', $can_edit, $album );
}

/**
 * Output the media photos/directory thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_media_photos_directory_image_thumbnail() {
	echo bb_get_media_photos_directory_image_thumbnail();
}

/**
 * Return the media photos/directory thumbnail.
 *
 * @since BuddyBoss 1.7.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media photos/directory thumbnail url.
 */
function bb_get_media_photos_directory_image_thumbnail() {
	global $media_template;

	/**
	 * Filters the media photos/directory being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string The media photos/directory thumbnail.
	 */
	return apply_filters( 'bb_get_media_photos_directory_image_thumbnail', $media_template->media->attachment_data->media_photos_directory_page );
}

/**
 * Output the media photos/directory thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bb_media_photos_theatre_popup_image() {
	echo bb_get_media_photos_theatre_popup_image();
}

/**
 * Return the media theatre popup thumbnail.
 *
 * @since BuddyBoss 1.7.0
 *
 * @global object $media_template {@link BP_Media_Template}
 *
 * @return string The media theatre popup thumbnail url.
 */
function bb_get_media_photos_theatre_popup_image() {
	global $media_template;

	/**
	 * Filters the media theatre popup being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string The media theatre popup thumbnail.
	 */
	return apply_filters( 'bb_get_media_photos_theatre_popup_image', $media_template->media->attachment_data->media_theatre_popup );
}
