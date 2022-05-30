<?php
/**
 * BuddyBoss Video Template Functions.
 *
 * @package BuddyBoss\Video\Templates
 * @since   BuddyBoss 1.5.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the video component slug.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_slug() {
	echo esc_html( bp_get_video_slug() );
}

/**
 * Return the video component slug.
 *
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_slug() {

	/**
	 * Filters the video component slug.
	 *
	 * @param string $slug Video component slug.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_slug', buddypress()->video->slug );
}

/**
 * Output the video component root slug.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_root_slug() {
	echo esc_html( bp_get_video_root_slug() );
}

/**
 * Return the video component root slug.
 *
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_root_slug() {

	/**
	 * Filters the Video component root slug.
	 *
	 * @param string $slug Video component root slug.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_root_slug', buddypress()->video->root_slug );
}

/**
 * Initialize the video loop.
 *
 * Based on the $args passed, bp_has_video() populates the
 * $video_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of video items.
 *
 * @param array|string $args           {
 *                                     Arguments for limiting the contents of the video loop. Most arguments
 *                                     are in the same format as {@link BP_Video::get()}. However,
 *                                     because the format of the arguments accepted here differs in a number of
 *                                     ways, and because bp_has_video() determines some default arguments in
 *                                     a dynamic fashion, we list all accepted arguments here as well.
 *
 *   Arguments can be passed as an associative array, or as a URL querystring
 *   (eg, 'user_id=4&fields=all').
 *
 * @type int            $page           Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 * @type int|bool       $per_page       Number of results per page. Default: 20.
 * @type string         $page_arg       String used as a query parameter in pagination links. Default: 'acpage'.
 * @type int|bool       $max            Maximum number of results to return. Default: false (unlimited).
 * @type string         $fields         Video fields to retrieve. 'all' to fetch entire video objects,
 *                                               'ids' to get only the video IDs. Default 'all'.
 * @type string|bool    $count_total    If true, an additional DB query is run to count the total video items
 *                                               for the query. Default: false.
 * @type string         $sort           'ASC' or 'DESC'. Default: 'DESC'.
 * @type array|bool     $exclude        Array of video IDs to exclude. Default: false.
 * @type array|bool     $include        Array of exact video IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing
 *                                               the permalink page for a single video item, this value defaults to the
 *                                               ID of that item. Otherwise the default is false.
 * @type string         $search_terms   Limit results by a search term. Default: false.
 * @type string         $scope          Use a BuddyPress pre-built filter.
 *                                                 - 'friends' retrieves items belonging to the friends of a user.
 *                                                 - 'groups' retrieves items belonging to groups to which a user
 *                                                 belongs to. defaults to false.
 * @type int|array|bool $user_id        The ID(s) of user(s) whose video should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a user profile page, 'user_id' defaults
 *                                               to the ID of the displayed user. Otherwise the default is false.
 * @type int|array|bool $album_id       The ID(s) of album(s) whose video should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a single album page, 'album_id' defaults
 *                                               to the ID of the displayed album. Otherwise the default is false.
 * @type int|array|bool $group_id       The ID(s) of group(s) whose video should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a single group page, 'group_id' defaults
 *                                               to the ID of the displayed group. Otherwise the default is false.
 * @type array          $privacy        Limit results by privacy. Default: public | grouponly.
 * }
 * @return bool Returns true when video found, otherwise false.
 * @since BuddyBoss 1.7.0
 * @global object       $video_template {@link BP_Video_Template}
 */
function bp_has_video( $args = '' ) {
	global $video_template;

	$args = bp_parse_args( $args );

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id()
		? bp_displayed_user_id()
		: false;

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'video' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) { // phpcs:ignore
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] ); // phpcs:ignore
	}

	// Album filtering.
	if ( ! isset( $args['album_id'] ) ) {
		$album_id = bp_is_single_album() ? (int) bp_action_variable( 0 ) : false;
	} else {
		$album_id = ( isset( $args['album_id'] ) ? $args['album_id'] : false );
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
		$privacy  = array( 'grouponly', 'forums', 'comment' );
		if ( bp_is_active( 'forums' ) && ( bbp_is_forum_edit() || bbp_is_topic_edit() || bbp_is_reply_edit() ) ) {
			$privacy = false;
		}
		$user_id = false;
	}

	// The default scope should recognize custom slugs.
	$scope = ( isset( $_REQUEST['scope'] ) && ! empty( $_REQUEST['scope'] ) ? $_REQUEST['scope'] : 'all' ); // phpcs:ignore
	$scope = ( isset( $args['scope'] ) && ! empty( $args['scope'] ) ? $args['scope'] : $scope );

	$scope = bp_video_default_scope( trim( $scope ) );

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
			'include'      => false,    // Pass an video_id or string of IDs comma-separated.
			'exclude'      => false,    // Pass an activity_id or string of IDs comma-separated.
			'sort'         => 'DESC',   // Sort DESC or ASC.
			'order_by'     => false,    // Order by. Default: date_created.
			'page'         => 1,        // Which page to load.
			'per_page'     => 20,       // Number of items per page.
			'page_arg'     => 'acpage', // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'          => false,    // Max number to return.
			'fields'       => 'all',
			'count_total'  => false,

			// Scope - pre-built video filters for a user (friends/groups).
			'scope'        => $scope,

			// Filtering.
			'user_id'      => $user_id,   // user_id to filter on.
			'album_id'     => $album_id,  // album_id to filter on.
			'group_id'     => $group_id,  // group_id to filter on.
			'privacy'      => $privacy,   // privacy to filter on - public, onlyme, loggedin, friends, grouponly, message.

			// Searching.
			'search_terms' => $search_terms_default,
		),
		'has_video'
	);

	// Search terms.
	if ( ! empty( $_REQUEST['s'] ) && empty( $r['search_terms'] ) ) { // phpcs:ignore
		$r['search_terms'] = $_REQUEST['s']; // phpcs:ignore
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */

	$video_template = new BP_Video_Template( $r );

	/**
	 * Filters whether or not there are video items to display.
	 *
	 * @param bool   $value          Whether or not there are video items to display.
	 * @param string $video_template Current video template being used.
	 * @param array  $r              Array of arguments passed into the BP_Video_Template class.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_has_video', $video_template->has_video(), $video_template, $r );
}

/**
 * Determine if there are still video left in the loop.
 *
 * @return bool Returns true when video are found.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video() {
	global $video_template;

	return $video_template->user_videos();
}

/**
 * Get the current video object in the loop.
 *
 * @return object The current video within the loop.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_the_video() {
	global $video_template;

	return $video_template->the_video();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_load_more_link() {
	echo esc_url( bp_get_video_load_more_link() );
}

/**
 * Get the URL for the Load More link.
 *
 * @return string $link
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_load_more_link() {
	global $video_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $video_template->pag_arg, $video_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @param string $link           The "Load More" link URL with appropriate query args.
	 * @param string $url            The original URL.
	 * @param object $video_template The video template loop global.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_load_more_link', $link, $url, $video_template );
}

/**
 * Output the video pagination count.
 *
 * @since BuddyBoss 1.7.0
 *
 * @global object $video_template {@link BP_Video_Template}
 */
function bp_video_pagination_count() {
	echo bp_get_video_pagination_count(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video pagination count.
 *
 * @return string The pagination text.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_pagination_count() {
	global $video_template;

	$start_num = intval( ( $video_template->pag_page - 1 ) * $video_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $video_template->pag_num - 1 ) > $video_template->total_video_count ) ? $video_template->total_video_count : $start_num + ( $video_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $video_template->total_video_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $video_template->total_video_count, 'buddyboss' ), $from_num, $to_num, $total ); // phpcs:ignore

	return $message;
}

/**
 * Output the video pagination links.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_pagination_links() {
	echo bp_get_video_pagination_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video pagination links.
 *
 * @return string The pagination links.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_pagination_links() {
	global $video_template;

	/**
	 * Filters the video pagination link output.
	 *
	 * @param string $pag_links Output for the video pagination links.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_pagination_links', $video_template->pag_links );
}

/**
 * Return true when there are more video items to be shown than currently appear.
 *
 * @return bool $has_more_items True if more items, false if not.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_has_more_items() {
	global $video_template;

	if ( ! empty( $video_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $video_template->pag_page ) ) {
			$remaining_pages = floor( ( $video_template->total_video_count - 1 ) / ( $video_template->pag_num * $video_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more video items to display.
	 *
	 * @param bool $has_more_items Whether or not there are more video items to display.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_has_more_items', $has_more_items );
}

/**
 * Output the video count.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_count() {
	echo bp_get_video_count(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video count.
 *
 * @return int The video count.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_count() {
	global $video_template;

	/**
	 * Filters the video count for the video template.
	 *
	 * @param int $video_count The count for total video.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_count', (int) $video_template->video_count );
}

/**
 * Output the number of video per page.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_per_page() {
	echo bp_get_video_per_page(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the number of video per page.
 *
 * @return int The video per page.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_per_page() {
	global $video_template;

	/**
	 * Filters the video posts per page value.
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_per_page', (int) $video_template->pag_num );
}

/**
 * Output the video ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_id() {
	echo bp_get_video_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video ID.
 *
 * @return int The video ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_id() {
	global $video_template;

	/**
	 * Filters the video ID being displayed.
	 *
	 * @param int $id The video ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_id', $video_template->video->id );
}

/**
 * Output the video blog id.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_blog_id() {
	echo bp_get_video_blog_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video blog ID.
 *
 * @return int The video blog ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_blog_id() {
	global $video_template;

	/**
	 * Filters the video ID being displayed.
	 *
	 * @param int $id The video blog ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_blog_id', $video_template->video->blog_id );
}

/**
 * Output the video user ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_user_id() {
	echo bp_get_video_user_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video user ID.
 *
 * @return int The video user ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_user_id() {
	global $video_template;

	/**
	 * Filters the video ID being displayed.
	 *
	 * @param int $id The video user ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_user_id', $video_template->video->user_id );
}

/**
 * Output the video attachment ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_attachment_id() {
	echo bp_get_video_attachment_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video attachment ID.
 *
 * @return int The video attachment ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_attachment_id() {
	global $video_template;

	/**
	 * Filters the video ID being displayed.
	 *
	 * @param int $id The video attachment ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_attachment_id', $video_template->video->attachment_id );
}

/**
 * Output the video url.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_link() {
	echo esc_url( bp_get_video_link() );
}

/**
 * Return the video attachment ID.
 *
 * @return int The video attachment ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_link() {
	global $video_template;

	$url = $video_template->video->video_link;

	/**
	 * Filters the video ID being displayed.
	 *
	 * @param string $url The video url ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_link', $url );
}

/**
 * Output the video attachment ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_type() {
	echo esc_html( bp_get_video_type() );
}

/**
 * Return the video attachment ID.
 *
 * @return int The video attachment ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_type() {
	global $video_template;

	/**
	 * Filters the video ID being displayed.
	 *
	 * @param int $id The video attachment ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_type', $video_template->video->attachment_data->meta->mime_type );
}

/**
 * Output the video title.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_title() {
	echo esc_html( bp_get_video_title() );
}

/**
 * Return the video title.
 *
 * @return int The video title.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_title() {
	global $video_template;

	/**
	 * Filters the video title being displayed.
	 *
	 * @param int $id The video title.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_title', $video_template->video->title );
}

/**
 * Determine if the current user can delete an video item.
 *
 * @param int|BP_Video $video BP_Video object or ID of the video.
 *
 * @return bool True if can delete, false otherwise.
 * @since BuddyBoss 1.7.0
 */
function bp_video_user_can_delete( $video = false ) {

	// Assume the user cannot delete the video item.
	$can_delete = false;

	if ( empty( $video ) ) {
		return $can_delete;
	}

	if ( ! is_object( $video ) ) {
		$video = new BP_Video( $video );
	}

	if ( empty( $video ) ) {
		return $can_delete;
	}

	// Only logged in users can delete video.
	if ( is_user_logged_in() ) {

		// Community moderators can always delete video (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_delete = true;
		}

		// Users are allowed to delete their own video.
		if ( isset( $video->user_id ) && ( bp_loggedin_user_id() === $video->user_id ) ) {
			$can_delete = true;
		}

		if ( bp_is_active( 'groups' ) && $video->group_id > 0 ) {
			$manage   = groups_can_user_manage_video( bp_loggedin_user_id(), $video->group_id );
			$is_admin = groups_is_user_admin( bp_loggedin_user_id(), $video->group_id );
			$is_mod   = groups_is_user_mod( bp_loggedin_user_id(), $video->group_id );
			if ( $manage ) {
				$can_delete = true;
			} elseif ( $is_mod || $is_admin ) {
				$can_delete = true;
			}
		}
	}

	/**
	 * Filters whether the current user can delete an video item.
	 *
	 * @param bool   $can_delete Whether the user can delete the item.
	 * @param object $video      Current video item object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_video_user_can_delete', $can_delete, $video );
}

/**
 * Output the video album ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_id() {
	echo bp_get_video_album_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video album ID.
 *
 * @return int The video album ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_id() {
	global $video_template;

	/**
	 * Filters the video album ID being displayed.
	 *
	 * @param int $id The video album ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_album_id', $video_template->video->album_id );
}

/**
 * Output the video group ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_group_id() {
	echo bp_get_video_group_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video group ID.
 *
 * @return int The video group ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_group_id() {
	global $video_template;

	/**
	 * Filters the video group ID being displayed.
	 *
	 * @param int $id The video group ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_group_id', $video_template->video->group_id );
}

/**
 * Output the video activity ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_activity_id() {
	echo bp_get_video_activity_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video activity ID.
 *
 * @return int The video activity ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_activity_id() {
	global $video_template;

	/**
	 * Filters the video activity ID being displayed.
	 *
	 * @param int $id The video activity ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_activity_id', $video_template->video->activity_id );
}

/**
 * Output the video date created.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_date_created() {
	echo esc_html( bp_get_video_date_created() );
}

/**
 * Return the video date created.
 *
 * @return string The video date created.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_date_created() {
	global $video_template;

	/**
	 * Filters the video date created being displayed.
	 *
	 * @param string The date created.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_date_created', $video_template->video->date_created );
}

/**
 * Output the video attachment thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_attachment_image_thumbnail() {
	echo esc_url( bp_get_video_attachment_image_thumbnail() );
}

/**
 * Return the video attachment thumbnail.
 *
 * @return string The video attachment thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_attachment_image_thumbnail() {
	global $video_template;

	/**
	 * Filters the video thumbnail being displayed.
	 *
	 * @param string The video thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_attachment_image', $video_template->video->attachment_data->thumb );
}

/**
 * Output the video attachment activity thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_attachment_image_activity_thumbnail() {
	echo esc_url( bp_get_video_attachment_image_activity_thumbnail() );
}

/**
 * Return the video attachment activity thumbnail.
 *
 * @return string The video attachment thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_attachment_image_activity_thumbnail() {
	global $video_template;

	/**
	 * Filters the video activity thumbnail being displayed.
	 *
	 * @param string The video activity thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_attachment_image', $video_template->video->attachment_data->activity_thumb );
}

/**
 * Output the video attachment.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_attachment_image() {
	echo esc_url( bp_get_video_attachment_image() );
}

/**
 * Return the video attachment.
 *
 * @return string The video attachment url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_attachment_image() {
	global $video_template;

	/**
	 * Filters the video image being displayed.
	 *
	 * @param string The full image.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_attachment_image', $video_template->video->attachment_data->thumb );
}

/**
 * Output video directory permalink.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_directory_permalink() {
	echo esc_url( bp_get_video_directory_permalink() );
}

/**
 * Return video directory permalink.
 *
 * @return string
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_directory_permalink() {

	/**
	 * Filters the video directory permalink.
	 *
	 * @param string $value Video directory permalink.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_video_root_slug() ) );
}

/**
 * Output the video privacy.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_privacy() {
	echo esc_html( bp_get_video_privacy() );
}

/**
 * Return the video privacy.
 *
 * @return string The video privacy.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_privacy() {
	global $video_template;

	/**
	 * Filters the video privacy being displayed.
	 *
	 * @param string $id The video privacy.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_privacy', $video_template->video->privacy );
}

/**
 * Output the video parent activity id.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_parent_activity_id() {
	echo bp_get_video_parent_activity_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video parent activity id.
 *
 * @return int The video parent activity id.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_parent_activity_id() {
	global $video_template;

	/**
	 * Filters the video parent activity id.
	 *
	 * @param int $id The video parent activity id.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_privacy', get_post_meta( $video_template->video->attachment_id, 'bp_video_parent_activity_id', true ) );
}

// ****************************** Video Albums *********************************//

/**
 * Initialize the album loop.
 *
 * Based on the $args passed, bp_has_video_albums() populates the
 * $video_album_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of video album items.
 *
 * @param array|string $args                 {
 *                                           Arguments for limiting the contents of the video loop. Most arguments
 *                                           are in the same format as {@link BP_Video_Album::get()}. However,
 *                                           because the format of the arguments accepted here differs in a number of
 *                                           ways, and because bp_has_video() determines some default arguments in
 *                                           a dynamic fashion, we list all accepted arguments here as well.
 *
 *   Arguments can be passed as an associative array, or as a URL querystring
 *   (eg, 'author_id=4&privacy=public').
 *
 * @type int            $page                 Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 * @type int|bool       $per_page             Number of results per page. Default: 20.
 * @type string         $page_arg             String used as a query parameter in pagination links. Default: 'acpage'.
 * @type int|bool       $max                  Maximum number of results to return. Default: false (unlimited).
 * @type string         $fields               Activity fields to retrieve. 'all' to fetch entire video objects,
 *                                               'ids' to get only the video IDs. Default 'all'.
 * @type string|bool    $count_total          If true, an additional DB query is run to count the total video items
 *                                               for the query. Default: false.
 * @type string         $sort                 'ASC' or 'DESC'. Default: 'DESC'.
 * @type array|bool     $exclude              Array of video IDs to exclude. Default: false.
 * @type array|bool     $include              Array of exact video IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing
 *                                               the permalink page for a single video item, this value defaults to the
 *                                               ID of that item. Otherwise the default is false.
 * @type string         $search_terms         Limit results by a search term. Default: false.
 * @type int|array|bool $user_id              The ID(s) of user(s) whose video should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a user profile page, 'user_id' defaults
 *                                               to the ID of the displayed user. Otherwise the default is false.
 * @type int|array|bool $group_id             The ID(s) of group(s) whose video should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a group page, 'group_id' defaults to
 *                                               the ID of the displayed group. Otherwise the default is false.
 * @type array          $privacy              Limit results by a privacy. Default: public | grouponly.
 * }
 * @return bool Returns true when video found, otherwise false.
 * @since BuddyBoss 1.7.0
 * @global object       $video_album_template {@link BP_Video_Album_Template}
 */
function bp_has_video_albums( $args = '' ) {
	global $video_album_template;

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id()
		? bp_displayed_user_id()
		: false;

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'album' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) { // phpcs:ignore
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] ); // phpcs:ignore
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
			 * Check if the login user is friends of the display user,
			 * OR check if the login user and the display user is the same
			 */
			if ( $is_friend || ! empty( $current_user_id ) && $current_user_id === $user_id ) {
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
		'has_video_albums'
	);

	/*
	 * Smart Overrides.
	 */

	// Search terms.
	if ( ! empty( $_REQUEST['s'] ) && empty( $r['search_terms'] ) ) { // phpcs:ignore
		$r['search_terms'] = $_REQUEST['s']; // phpcs:ignore
	}

	// Do not exceed the maximum per page.
	if ( ! empty( $r['max'] ) && ( (int) $r['per_page'] > (int) $r['max'] ) ) {
		$r['per_page'] = $r['max'];
	}

	/*
	 * Query
	 */

	$video_album_template = new BP_Video_Album_Template( $r );

	/**
	 * Filters whether or not there are video albums to display.
	 *
	 * @param bool   $value                Whether or not there are video items to display.
	 * @param string $video_album_template Current video album template being used.
	 * @param array  $r                    Array of arguments passed into the BP_Video_Album_Template class.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_has_video_album', $video_album_template->has_albums(), $video_album_template, $r );
}

/**
 * Determine if there are still album left in the loop.
 *
 * @return bool Returns true when video are found.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album() {
	global $video_album_template;

	return $video_album_template->user_albums();
}

/**
 * Get the current album object in the loop.
 *
 * @return object The current video within the loop.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_the_video_album() {
	global $video_album_template;

	return $video_album_template->the_album();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_load_more_link() {
	echo esc_url( bp_get_video_album_load_more_link() );
}

/**
 * Get the URL for the Load More link.
 *
 * @return string $link
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_load_more_link() {
	global $video_album_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $video_album_template->pag_arg, $video_album_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @param string $link                 The "Load More" link URL with appropriate query args.
	 * @param string $url                  The original URL.
	 * @param object $video_album_template The video album template loop global.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_album_load_more_link', $link, $url, $video_album_template );
}

/**
 * Output the album pagination count.
 *
 * @since BuddyBoss 1.7.0
 *
 * @global object $video_album_template {@link BP_Video_Album_Template}
 */
function bp_video_album_pagination_count() {
	echo bp_get_video_album_pagination_count(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the album pagination count.
 *
 * @return string The pagination text.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_pagination_count() {
	global $video_album_template;

	$start_num = intval( ( $video_album_template->pag_page - 1 ) * $video_album_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $video_album_template->pag_num - 1 ) > $video_album_template->total_album_count ) ? $video_album_template->total_album_count : $start_num + ( $video_album_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $video_album_template->total_album_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $video_album_template->total_video_count, 'buddyboss' ), $from_num, $to_num, $total ); // phpcs:ignore

	return $message;
}

/**
 * Output the album pagination links.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_pagination_links() {
	echo bp_get_video_album_pagination_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the album pagination links.
 *
 * @return string The pagination links.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_pagination_links() {
	global $video_album_template;

	/**
	 * Filters the album pagination link output.
	 *
	 * @param string $pag_links Output for the video album pagination links.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_album_pagination_links', $video_album_template->pag_links );
}

/**
 * Return true when there are more album items to be shown than currently appear.
 *
 * @return bool $has_more_items True if more items, false if not.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_has_more_items() {
	global $video_album_template;

	if ( ! empty( $video_album_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $video_album_template->pag_page ) ) {
			$remaining_pages = floor( ( $video_album_template->total_album_count - 1 ) / ( $video_album_template->pag_num * $video_album_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more album items to display.
	 *
	 * @param bool $has_more_items Whether or not there are more album items to display.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_video_album_has_more_items', $has_more_items );
}

/**
 * Output the album count.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_count() {
	echo bp_get_video_album_count(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the album count.
 *
 * @return int The album count.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_count() {
	global $video_album_template;

	/**
	 * Filters the album count for the video album template.
	 *
	 * @param int $album_count The count for total album.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_album_count', (int) $video_album_template->album_count );
}

/**
 * Output the number of video album per page.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_per_page() {
	echo bp_get_video_album_per_page(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the number of video album per page.
 *
 * @return int The video album per page.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_per_page() {
	global $video_album_template;

	/**
	 * Filters the video album posts per page value.
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_album_per_page', (int) $video_album_template->pag_num );
}

/**
 * Output the video album title.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_title() {
	echo esc_html( bp_get_video_album_title() );
}

/**
 * Return the album title.
 *
 * @return string The video album title.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_title() {
	global $video_album_template;

	/**
	 * Filters the album title being displayed.
	 *
	 * @param int $id The video album title.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_album_title', $video_album_template->album->title );
}

/**
 * Return the album privacy.
 *
 * @return string The video album privacy.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_privacy() {
	global $video_album_template;

	/**
	 * Filters the album privacy being displayed.
	 *
	 * @param int $id The video album privacy.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_album_privacy', $video_album_template->album->privacy );
}

/**
 * Output the video album ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_link() {
	echo esc_url( bp_get_video_album_link() );
}

/**
 * Return the album description.
 *
 * @return string The video album description.
 * @global object $video_album_template {@link BP_Video_Album_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_link() {
	global $video_album_template;

	if ( bp_is_group() && ! empty( $video_album_template->album->group_id ) ) {
		$group_link = bp_get_group_permalink( buddypress()->groups->current_group );
		$url        = trailingslashit( $group_link . '/albums/' . bp_get_video_album_id() );
	} else {
		$url = trailingslashit( bp_displayed_user_domain() . bp_get_media_slug() . '/albums/' . bp_get_video_album_id() );
	}

	/**
	 * Filters the album description being displayed.
	 *
	 * @param int $id The video album description.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_album_link', $url );
}

/**
 * Determine if the current user can delete an album item.
 *
 * @param int|BP_Video_Album $album BP_Video_Album object or ID of the album.
 *
 * @return bool True if can delete, false otherwise.
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_user_can_delete( $album = false ) {

	// Assume the user cannot delete the album item.
	$can_delete = false;

	if ( empty( $album ) ) {
		return $can_delete;
	}

	if ( ! is_object( $album ) ) {
		$album = new BP_Video_Album( $album );
	}

	if ( empty( $album ) ) {
		return $can_delete;
	}

	// Only logged in users can delete album.
	if ( is_user_logged_in() ) {

		// Groups albums have their own access.
		if ( ! empty( $album->group_id ) && groups_can_user_manage_video_albums( bp_loggedin_user_id(), $album->group_id ) ) {
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
	 * @param bool   $can_delete Whether the user can delete the item.
	 * @param object $album      Current album item object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_video_album_user_can_delete', $can_delete, $album );
}

/**
 * Determine if the current user can edit an video item.
 *
 * @param bool $video BP_Video object or ID of the video.
 *
 * @return bool True if can edit, false otherwise.
 * @since BuddyBoss 1.7.0
 */
function bp_video_user_can_edit( $video = false ) {

	// Assume the user cannot edit the document item.
	$can_edit = false;

	if ( empty( $video ) ) {
		return $can_edit;
	}

	if ( ! is_object( $video ) ) {
		$video = new BP_Video( $video );
	}

	if ( empty( $video ) ) {
		return $can_edit;
	}

	// Only logged in users can edit media.
	if ( is_user_logged_in() ) {

		// Community moderators can always edit media (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_edit = true;
		}

		// Users are allowed to edit their own media.
		if ( isset( $video->user_id ) && ( bp_loggedin_user_id() === $video->user_id ) ) {
			$can_edit = true;
		}

		if ( bp_is_active( 'groups' ) && $video->group_id > 0 ) {

			$manage   = groups_can_user_manage_video( bp_loggedin_user_id(), $video->group_id );
			$status   = bp_group_get_video_status( $video->group_id );
			$is_admin = groups_is_user_admin( bp_loggedin_user_id(), $video->group_id );
			$is_mod   = groups_is_user_mod( bp_loggedin_user_id(), $video->group_id );

			if ( $manage ) {
				if ( bp_loggedin_user_id() === $video->user_id ) {
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
	 * Filters whether the current user can edit an video item.
	 *
	 * @param bool   $can_edit Whether the user can edit the item.
	 * @param object $video    Current video item object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_media_user_can_edit', $can_edit, $video );
}

/**
 * Determine if the current user can edit an album item.
 *
 * @param int|BP_Video_Album $album BP_Video_Album object or ID of the album.
 *
 * @return bool True if can edit, false otherwise.
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_user_can_edit( $album = false ) {

	// Assume the user cannot edit the album item.
	$can_edit = false;

	if ( empty( $album ) ) {
		return $can_edit;
	}

	if ( ! is_object( $album ) ) {
		$album = new BP_Video_Album( $album );
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
		} elseif ( ! empty( $album->group_id ) && groups_can_user_manage_video( bp_loggedin_user_id(), $album->group_id ) ) {
			$can_edit = true;
		}
	}

	/**
	 * Filters whether the current user can edit an album item.
	 *
	 * @param bool   $can_edit Whether the user can edit the item.
	 * @param object $album    Current album item object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_video_album_user_can_edit', $can_edit, $album );
}

/**
 * Output the video length.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_length() {
	echo bp_get_video_length(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video length.
 *
 * @return int The video attachment ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_length() {
	global $video_template;

	/**
	 * Filters the video length being displayed.
	 *
	 * @param string {$video_template->video->attachment_data->meta->length_formatted} The video length.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_length', $video_template->video->attachment_data->meta->length_formatted );
}

/**
 * Output the video thumbnail ID.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_thumbnail_id() {
	echo bp_get_video_thumbnail_id(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the video thumbnail ID.
 *
 * @return int The video thumbnail ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_thumbnail_id() {
	global $video_template;

	$thumbnail_id = get_post_meta( $video_template->video->attachment_id, 'bp_video_preview_thumbnail_id', true );

	/**
	 * Filters the video thumbnail ID being displayed.
	 *
	 * @param int $id The video thumbnail ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_thumbnail_id', $thumbnail_id );
}

/**
 * Output the auto generated video thumbnail IDs comma separated.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_auto_generated_thumbnail_ids() {
	echo bp_get_video_auto_generated_thumbnail_ids(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Return the auto generated video thumbnail IDs comma separated.
 *
 * @return string|false The auto generated video thumbnail IDs comma separated.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_auto_generated_thumbnail_ids() {
	global $video_template;

	$thumbnail_ids = get_post_meta( $video_template->video->attachment_id, 'video_preview_thumbnails', true );
	$thumbnail_ids = isset( $thumbnail_ids['default_images'] ) && ! empty( $thumbnail_ids['default_images'] ) ? $thumbnail_ids['default_images'] : '';

	/**
	 * Filters the auto generated video thumbnail IDs comma separated.
	 *
	 * @param string|false $thumbnail_ids The auto generated video thumbnail IDs comma separated.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_auto_generated_thumbnail_ids', $thumbnail_ids );
}

/**
 * Output the video visibility.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_visibility() {
	echo esc_html( bp_get_video_visibility() );
}

/**
 * Return the video visibility.
 *
 * @since BuddyBoss 1.7.0
 *
 * @global object $media_template {@link BP_Video_Template}
 *
 * @return string The video visibility.
 */
function bp_get_video_visibility() {
	global $video_template;

	/**
	 * Filters the video privacy being displayed.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $id The video privacy.
	 */
	return apply_filters( 'bp_get_video_visibility', $video_template->video->visibility );
}

/**
 * Output the Video author name.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_author() {
	echo esc_html( bp_get_video_author() );
}

/**
 * Return the Video author name.
 *
 * @since BuddyBoss 1.7.0
 *
 * @global object $video_template {@link \BP_Video_Template}
 *
 * @return int The Media author name.
 */
function bp_get_video_author() {
	global $video_template;

	if ( isset( $video_template ) && isset( $video_template->video ) && isset( $video_template->video->user_id ) ) {
		$author = bp_core_get_user_displayname( $video_template->video->user_id );
	}

	/**
	 * Filters the Video author name being displayed.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $id The Video author id.
	 */
	return apply_filters( 'bp_get_video_author', $author );
}

/**
 * Output the video url.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_videos_link() {
	echo esc_url( bp_get_videos_link() );
}

/**
 * Return the video attachment ID.
 *
 * @return int The video attachment ID.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_videos_link() {
	global $video_template;

	if ( ! empty( $video_template->video->group_id ) ) {
		$group = buddypress()->groups->current_group;
		if ( ! isset( $group->id ) || $group->id !== $video_template->video->group_id ) {
			$group = groups_get_group( $video_template->video->group_id );
		}
		$group_link = bp_get_group_permalink( $group );
		$url        = trailingslashit( $group_link . bp_get_video_slug() );
	} else {
		$url = trailingslashit( bp_core_get_user_domain( bp_get_video_user_id() ) . bp_get_video_slug() );
	}

	/**
	 * Filters the video ID being displayed.
	 *
	 * @param string $url The video url ID.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_videos_link', $url );
}

/**
 * Output the video attachment thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_user_profile_thumb() {
	echo esc_url( bp_get_video_user_profile_thumb() );
}

/**
 * Return the video attachment thumbnail.
 *
 * @return string The video attachment thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_user_profile_thumb() {
	global $video_template;

	/**
	 * Filters the video thumbnail being displayed.
	 *
	 * @param string The video thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_user_profile_thumb', $video_template->video->attachment_data->video_user_profile_thumb );
}

/**
 * Output the video attachment thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_directory_page_thumb() {
	echo esc_url( bp_get_video_directory_page_thumb() );
}

/**
 * Return the video attachment thumbnail.
 *
 * @return string The video attachment thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_directory_page_thumb() {
	global $video_template;

	/**
	 * Filters the video thumbnail being displayed.
	 *
	 * @param string The video thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_directory_page_thumb', $video_template->video->attachment_data->video_directory_page_thumb );
}

/**
 * Output the video attachment thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_album_cover_thumb() {
	echo esc_url( bp_get_video_album_cover_thumb() );
}

/**
 * Return the video attachment thumbnail.
 *
 * @return string The video attachment thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_album_cover_thumb() {
	global $video_template;

	/**
	 * Filters the video thumbnail being displayed.
	 *
	 * @param string The video thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_album_cover_thumb', $video_template->video->attachment_data->video_album_cover_thumb );
}

/**
 * Output the video attachment thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_add_thumbnail_thumb() {
	echo esc_url( bp_get_video_add_thumbnail_thumb() );
}

/**
 * Return the video attachment thumbnail.
 *
 * @return string The video attachment thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_add_thumbnail_thumb() {
	global $video_template;

	/**
	 * Filters the video thumbnail being displayed.
	 *
	 * @param string The video thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_add_thumbnail_thumb', $video_template->video->attachment_data->video_add_thumbnail_thumb );
}

/**
 * Output the video popup thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_popup_thumb() {
	echo esc_url( bp_get_video_popup_thumb() );
}

/**
 * Return the video popup thumbnail.
 *
 * @return string The video popup thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_popup_thumb() {
	global $video_template;

	/**
	 * Filters the video popup thumbnail being displayed.
	 *
	 * @param string The video popup thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_popup_thumb', $video_template->video->attachment_data->video_popup_thumb );
}

/**
 * Output the video activity thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_activity_thumb() {
	echo esc_url( bp_get_video_activity_thumb() );
}

/**
 * Return the video activity thumbnail.
 *
 * @return string The video attachment thumbnail url.
 * @global object $video_template {@link BP_Video_Template}
 *
 * @since BuddyBoss 1.7.0
 */
function bp_get_video_activity_thumb() {
	global $video_template;

	/**
	 * Filters the video acrtivity being displayed.
	 *
	 * @param string The video activity thumbnail.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_get_video_activity_thumb', $video_template->video->attachment_data->video_activity_thumb );
}
