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
 *
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
 *     @type int               $offset           Return only media items with an ID greater than or equal to this one.
 *                                               Note that providing an offset will disable pagination. Default: false.
 * }
 * @return bool Returns true when media found, otherwise false.
 */
function bp_has_media( $args = '' ) {
	global $media_template;

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id()
		? bp_displayed_user_id()
		: false;

	$search_terms_default = false;
	$search_query_arg = bp_core_get_component_search_query_arg( 'media' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args( $args, array(
		'include'           => false,     // Pass an activity_id or string of IDs comma-separated.
		'exclude'           => false,        // Pass an activity_id or string of IDs comma-separated.
		'sort'              => 'DESC',       // Sort DESC or ASC.
		'page'              => 1,            // Which page to load.
		'per_page'          => 20,           // Number of items per page.
		'page_arg'          => 'acpage',     // See https://buddypress.trac.wordpress.org/ticket/3679.
		'max'               => false,        // Max number to return.
		'fields'            => 'all',
		'count_total'       => false,

		// Filtering
		'user_id'           => $user_id,     // user_id to filter on.
		'offset'            => false,        // Return only items >= this ID.
		'since'             => false,        // Return only items recorded since this Y-m-d H:i:s date.

		// Searching.
		'search_terms'      => $search_terms_default,
		'update_meta_cache' => true,
	), 'has_media' );

	/*
	 * Smart Overrides.
	 */

	// Ignore pagination if an offset is passed.
	if ( ! empty( $r['offset'] ) ) {
		$r['page'] = 0;
	}

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
 *
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

	if ( ! empty( $media_template->has_more_items )  ) {
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
 *
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
 *
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
 *
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
 *
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
 *
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
 * Output the media attachment ID.
 *
 * @since BuddyBoss 1.0.0
 *
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
 *
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
 * Output the media album ID.
 *
 * @since BuddyBoss 1.0.0
 *
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
 * Output the media activity ID.
 *
 * @since BuddyBoss 1.0.0
 *
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
 *
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
 * Output the media date created.
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_media_attachment_image() {
	echo bp_get_media_attachment_image();
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
function bp_get_media_attachment_image() {
	global $media_template;

	/**
	 * Filters the media date created being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string The date created.
	 */
	return apply_filters( 'bp_get_media_attachment_image', $media_template->media->attachment_data->thumb );
}

//****************************** Media Albums *********************************//

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
 *     @type int               $offset           Return only media items with an ID greater than or equal to this one.
 *                                               Note that providing an offset will disable pagination. Default: false.
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
	$search_query_arg = bp_core_get_component_search_query_arg( 'media' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args( $args, array(
		'include'           => false,     // Pass an activity_id or string of IDs comma-separated.
		'exclude'           => false,        // Pass an activity_id or string of IDs comma-separated.
		'sort'              => 'DESC',       // Sort DESC or ASC.
		'page'              => 1,            // Which page to load.
		'per_page'          => 20,           // Number of items per page.
		'page_arg'          => 'acpage',     // See https://buddypress.trac.wordpress.org/ticket/3679.
		'max'               => false,        // Max number to return.
		'fields'            => 'all',
		'count_total'       => false,

		// Filtering
		'user_id'           => $user_id,     // user_id to filter on.
		'offset'            => false,        // Return only items >= this ID.
		'since'             => false,        // Return only items recorded since this Y-m-d H:i:s date.

		// Searching.
		'search_terms'      => $search_terms_default,
		'update_meta_cache' => true,
	), 'has_media' );

	/*
	 * Smart Overrides.
	 */

	// Ignore pagination if an offset is passed.
	if ( ! empty( $r['offset'] ) ) {
		$r['page'] = 0;
	}

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
 *
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

	if ( ! empty( $media_album_template->has_more_items )  ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $media_album_template->pag_page ) ) {
			$remaining_pages = floor( ( $media_album_template->total_media_count - 1 ) / ( $media_album_template->pag_num * $media_album_template->pag_page ) );
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
 *
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
 *
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
 *
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
 * Output the media album ID.
 *
 * @since BuddyBoss 1.0.0
 *
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
 * Output the media album ID.
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_album_description() {
	echo bp_get_album_description();
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
function bp_get_album_description() {
	global $media_album_template;

	/**
	 * Filters the album description being displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int $id The media album description.
	 */
	return apply_filters( 'bp_get_album_description', $media_album_template->album->description );
}