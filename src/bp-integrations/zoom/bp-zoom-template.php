<?php
/**
 * BuddyBoss Zoom Template Functions.
 *
 * @package BuddyBoss\Zoom\Templates
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize the meeting loop.
 *
 * Based on the $args passed, bp_has_meeting() populates the
 * $meeting_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of meeting items.
 *
 * @since BuddyBoss 1.2.10

 * @global object $meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the meeting loop. Most arguments
 *     are in the same format as {@link BP_Group_Zoom_Meeting::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_zoom_meetings() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'group_id=4&fields=all').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           meeting fields to retrieve. 'all' to fetch entire meeting objects,
 *                                               'ids' to get only the meeting IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total meeting items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of meeting IDs to exclude. Default: false.
 *     @type array|bool        $include          Array of exact meeting IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single meeting item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 * }
 * @return bool Returns true when meetings found, otherwise false.
 */
function bp_has_zoom_meetings( $args = '' ) {
	global $zoom_meeting_template;

	/*
	 * Smart Defaults.
	 */

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'meeting' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	$group_id = false;
	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$group_id = bp_get_current_group_id();
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'include'      => false,           // Pass an meeting_id or string of IDs comma-separated.
			'exclude'      => false,           // Pass an activity_id or string of IDs comma-separated.
			'sort'         => 'DESC',          // Sort DESC or ASC.
			'order_by'     => false,           // Order by. Default: id
			'page'         => 1,               // Which page to load.
			'per_page'     => 20,              // Number of items per page.
			'page_arg'     => 'acpage',        // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'          => false,           // Max number to return.
			'fields'       => 'all',
			'count_total'  => false,

			// Filtering
			'group_id'     => $group_id,       // group_id to filter on.

			// Searching.
			'search_terms' => $search_terms_default,
		),
		'has_meeting'
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

	$zoom_meeting_template = new BP_Group_Zoom_Meeting_Template( $r );

	/**
	 * Filters whether or not there are meeting items to display.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param bool   $value               Whether or not there are meeting items to display.
	 * @param string $zoom_meeting_template      Current meeting template being used.
	 * @param array  $r                   Array of arguments passed into the BP_Group_Zoom_Meeting_Template class.
	 */
	return apply_filters( 'bp_has_zoom_meetings', $zoom_meeting_template->has_meeting(), $zoom_meeting_template, $r );
}

/**
 * Determine if there are still meeting left in the loop.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return bool Returns true when meeting are found.
 */
function bp_zoom_meeting() {
	global $zoom_meeting_template;
	return $zoom_meeting_template->user_meetings();
}

/**
 * Get the current meeting object in the loop.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return object The current meeting within the loop.
 */
function bp_the_zoom_meeting() {
	global $zoom_meeting_template;
	return $zoom_meeting_template->the_meeting();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_load_more_link() {
	echo esc_url( bp_get_zoom_meeting_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since BuddyBoss 1.2.10
 *
 * @return string $link
 */
function bp_get_zoom_meeting_load_more_link() {
	global $zoom_meeting_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $zoom_meeting_template->pag_arg, $zoom_meeting_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $zoom_meeting_template The meeting template loop global.
	 */
	return apply_filters( 'bp_get_zoom_meeting_load_more_link', $link, $url, $zoom_meeting_template );
}

/**
 * Output the meeting pagination count.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 */
function bp_zoom_meeting_pagination_count() {
	echo bp_get_zoom_meeting_pagination_count();
}

/**
 * Return the meeting pagination count.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return string The pagination text.
 */
function bp_get_zoom_meeting_pagination_count() {
	global $zoom_meeting_template;

	$start_num = intval( ( $zoom_meeting_template->pag_page - 1 ) * $zoom_meeting_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $zoom_meeting_template->pag_num - 1 ) > $zoom_meeting_template->total_meeting_count ) ? $zoom_meeting_template->total_meeting_count : $start_num + ( $zoom_meeting_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $zoom_meeting_template->total_meeting_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $zoom_meeting_template->total_meeting_count, 'buddyboss' ), $from_num, $to_num, $total );

	return $message;
}

/**
 * Output the meeting pagination links.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_pagination_links() {
	echo bp_get_zoom_meeting_pagination_links();
}

/**
 * Return the meeting pagination links.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return string The pagination links.
 */
function bp_get_zoom_meeting_pagination_links() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting pagination link output.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param string $pag_links Output for the meeting pagination links.
	 */
	return apply_filters( 'bp_get_zoom_meeting_pagination_links', $zoom_meeting_template->pag_links );
}

/**
 * Return true when there are more meeting items to be shown than currently appear.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_zoom_meeting_has_more_items() {
	global $zoom_meeting_template;

	if ( ! empty( $zoom_meeting_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $zoom_meeting_template->pag_page ) ) {
			$remaining_pages = floor( ( $zoom_meeting_template->total_meeting_count - 1 ) / ( $zoom_meeting_template->pag_num * $zoom_meeting_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more meeting items to display.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param bool $has_more_items Whether or not there are more meeting items to display.
	 */
	return apply_filters( 'bp_zoom_meeting_has_more_items', $has_more_items );
}

/**
 * Output the meeting count.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_count() {
	echo bp_get_zoom_meeting_count();
}

/**
 * Return the meeting count.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting count.
 */
function bp_get_zoom_meeting_count() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting count for the meeting template.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $meeting_count The count for total meeting.
	 */
	return apply_filters( 'bp_get_zoom_meeting_count', (int) $zoom_meeting_template->meeting_count );
}

/**
 * Output the number of meeting per page.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_per_page() {
	echo bp_get_zoom_meeting_per_page();
}

/**
 * Return the number of meeting per page.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting per page.
 */
function bp_get_zoom_meeting_per_page() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting posts per page value.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_zoom_meeting_per_page', (int) $zoom_meeting_template->pag_num );
}

/**
 * Output the meeting ID.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_id() {
	echo bp_get_zoom_meeting_id();
}

/**
 * Return the meeting ID.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting ID.
 */
function bp_get_zoom_meeting_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting ID being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting group ID.
	 */
	return apply_filters( 'bp_get_zoom_meeting_id', $zoom_meeting_template->meeting->id );
}

/**
 * Output the meeting ID.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_group_id() {
	echo bp_get_zoom_meeting_group_id();
}

/**
 * Return the meeting ID.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting group ID.
 */
function bp_get_zoom_meeting_group_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting group ID being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting group ID.
	 */
	return apply_filters( 'bp_get_zoom_meeting_group_id', $zoom_meeting_template->meeting->group_id );
}

/**
 * Output the meeting title.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_title() {
	echo bp_get_zoom_meeting_title();
}

/**
 * Return the meeting title.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting title.
 */
function bp_get_zoom_meeting_title() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting title being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting title.
	 */
	return apply_filters( 'bp_get_zoom_meeting_title', $zoom_meeting_template->meeting->title );
}

/**
 * Output the meeting start date.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_start_date() {
	echo bp_get_zoom_meeting_start_date();
}

/**
 * Return the meeting start date.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting start date.
 */
function bp_get_zoom_meeting_start_date() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting start date being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting start date.
	 */
	return apply_filters( 'bp_get_zoom_meeting_start_date', $zoom_meeting_template->meeting->start_date );
}

/**
 * Output the meeting timezone.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_timezone() {
	echo bp_get_zoom_meeting_timezone();
}

/**
 * Return the meeting timezone.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting timezone.
 */
function bp_get_zoom_meeting_timezone() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting timezone being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting timezone.
	 */
	return apply_filters( 'bp_get_zoom_meeting_timezone', $zoom_meeting_template->meeting->timezone );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_duration() {
	echo bp_get_zoom_meeting_duration();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_duration() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_duration', $zoom_meeting_template->meeting->duration );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_join_before_host() {
	echo bp_get_zoom_meeting_join_before_host();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_join_before_host() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_join_before_host', $zoom_meeting_template->meeting->join_before_host );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_host_video() {
	echo bp_get_zoom_meeting_host_video();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_host_video() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_host_video', $zoom_meeting_template->meeting->host_video );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_participants_video() {
	echo bp_get_zoom_meeting_participants_video();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_participants_video() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_participants_video', $zoom_meeting_template->meeting->participants_video );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_mute_participants() {
	echo bp_get_zoom_meeting_mute_participants();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_mute_participants() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_mute_participants', $zoom_meeting_template->meeting->mute_participants );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_auto_recording() {
	echo bp_get_zoom_meeting_auto_recording();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_auto_recording() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_auto_recording', $zoom_meeting_template->meeting->auto_recording );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_alternative_host_ids() {
	echo bp_get_zoom_meeting_alternative_host_ids();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_alternative_host_ids() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_alternative_host_ids', $zoom_meeting_template->meeting->alternative_host_ids );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_zoom_details() {
	echo bp_get_zoom_meeting_zoom_details();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_zoom_details() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_details', $zoom_meeting_template->meeting->zoom_details );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_zoom_start_url() {
	echo bp_get_zoom_meeting_zoom_start_url();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_zoom_start_url() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_start_url', $zoom_meeting_template->meeting->zoom_start_url );
}

/**
 * Output the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_meeting_zoom_meeting_id() {
	echo bp_get_zoom_meeting_zoom_meeting_id();
}

/**
 * Return the meeting duration.
 *
 * @since BuddyBoss 1.2.10
 *
 * @global object $zoom_meeting_template {@link BP_Group_Zoom_Meeting_Template}
 *
 * @return int The meeting duration.
 */
function bp_get_zoom_meeting_zoom_meeting_id() {
	global $zoom_meeting_template;

	/**
	 * Filters the meeting duration being displayed.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @param int $id The meeting duration.
	 */
	return apply_filters( 'bp_get_zoom_meeting_zoom_meeting_id', $zoom_meeting_template->meeting->zoom_meeting_id );
}
