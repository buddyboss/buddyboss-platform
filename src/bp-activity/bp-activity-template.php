<?php
/**
 * BuddyBoss Activity Template Functions.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the activity component slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_slug() {
	echo bp_get_activity_slug();
}
/**
 * Return the activity component slug.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string The activity component slug.
 */
function bp_get_activity_slug() {

	/**
	 * Filters the activity component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Activity component slug.
	 */
	return apply_filters( 'bp_get_activity_slug', buddypress()->activity->slug );
}

/**
 * Output the activity component root slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_root_slug() {
	echo bp_get_activity_root_slug();
}

/**
 * Return the activity component root slug.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string The activity component root slug.
 */
function bp_get_activity_root_slug() {

	/**
	 * Filters the activity component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $root_slug Activity component root slug.
	 */
	return apply_filters( 'bp_get_activity_root_slug', buddypress()->activity->root_slug );
}

/**
 * Output activity directory permalink.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_directory_permalink() {
	echo esc_url( bp_get_activity_directory_permalink() );
}

/**
 * Return activity directory permalink.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string Activity directory permalink.
 */
function bp_get_activity_directory_permalink() {

	/**
	 * Filters the activity directory permalink.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $url Permalink url for the activity directory.
	 */
	return apply_filters( 'bp_get_activity_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_activity_root_slug() ) );
}

/**
 * Initialize the activity loop.
 *
 * Based on the $args passed, bp_has_activities() populates the
 * $activities_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of activity items.
 *
 * @since BuddyPress 1.0.0
 * @since BuddyPress 2.4.0 Introduced the `$fields` parameter.
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param array|string $args {
 *     Arguments for limiting the contents of the activity loop. Most arguments
 *     are in the same format as {@link BP_Activity_Activity::get()}. However,
 *     because the format of the arguments accepted here differs in a number of
 *     ways, and because bp_has_activities() determines some default arguments in
 *     a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'user_id=4&display_comments=threaded').
 *
 *     @type int               $page             Which page of results to fetch. Using page=1 without per_page will result
 *                                               in no pagination. Default: 1.
 *     @type int|bool          $per_page         Number of results per page. Default: 20.
 *     @type string            $page_arg         String used as a query parameter in pagination links. Default: 'acpage'.
 *     @type int|bool          $max              Maximum number of results to return. Default: false (unlimited).
 *     @type string            $fields           Activity fields to retrieve. 'all' to fetch entire activity objects,
 *                                               'ids' to get only the activity IDs. Default 'all'.
 *     @type string|bool       $count_total      If true, an additional DB query is run to count the total activity items
 *                                               for the query. Default: false.
 *     @type string            $sort             'ASC' or 'DESC'. Default: 'DESC'.
 *     @type array|bool        $exclude          Array of activity IDs to exclude. Default: false.
 *     @type array|bool        $in               Array of IDs to limit query by (IN). 'in' is intended to be used in
 *                                               conjunction with other filter parameters. Default: false.
 *     @type array|bool        $include          Array of exact activity IDs to query. Providing an 'include' array will
 *                                               override all other filters passed in the argument array. When viewing the
 *                                               permalink page for a single activity item, this value defaults to the ID of
 *                                               that item. Otherwise the default is false.
 *     @type array             $meta_query       Limit by activitymeta by passing an array of meta_query conditions. See
 *                                               {@link WP_Meta_Query::queries} for a description of the syntax.
 *     @type array             $date_query       Limit by date by passing an array of date_query conditions. See first
 *                                               parameter of {@link WP_Date_Query::__construct()} for syntax.
 *     @type array             $filter_query     Advanced activity filtering.  See {@link BP_Activity_Query::__construct()}.
 *     @type string            $search_terms     Limit results by a search term. Default: false.
 *     @type string            $scope            Use a BuddyPress pre-built filter.
 *                                                 - 'just-me' retrieves items belonging only to a user; this is equivalent
 *                                                   to passing a 'user_id' argument.
 *                                                 - 'friends' retrieves items belonging to the friends of a user.
 *                                                 - 'groups' retrieves items belonging to groups to which a user belongs to.
 *                                                 - 'favorites' retrieves a user's favorited activity items.
 *                                                 - 'mentions' retrieves items where a user has received an @-mention.
 *                                               The default value of 'scope' is set to one of the above if that value
 *                                               appears in the appropriate place in the URL; eg, 'scope' will be 'groups'
 *                                               when visiting http://example.com/members/joe/activity/groups/. Otherwise
 *                                               defaults to false.
 *     @type int|array|bool    $user_id          The ID(s) of user(s) whose activity should be fetched. Pass a single ID or
 *                                               an array of IDs. When viewing a user profile page (but not that user's
 *                                               activity subpages, ie My Connections, My Groups, etc), 'user_id' defaults to
 *                                               the ID of the displayed user. Otherwise the default is false.
 *     @type string|array|bool $object           Filters by the `component` column in the database, which is generally the
 *                                               component ID in the case of BuddyPress components, or the plugin slug in
 *                                               the case of plugins. For example, 'groups' will limit results to those that
 *                                               are associated with the BP Groups component. Accepts a single component
 *                                               string, or an array of multiple components. Defaults to 'groups' when
 *                                               viewing the page of a single group, the My Groups activity filter, or the
 *                                               Activity > Groups filter of a user profile. Otherwise defaults to false.
 *     @type string|array|bool $action           Filters by the `type` column in the database, which is a string
 *                                               categorizing the activity item (eg, 'new_blog_post', 'created_group').
 *                                               Accepts a comma-delimited string or an array of types. Default: false.
 *     @type int|array|bool    $primary_id       Filters by the `item_id` column in the database. The meaning of
 *                                               'primary_id' differs between components/types; for example, in the case of
 *                                               'created_group', 'primary_id' is the ID of the group. Accepts a single ID,
 *                                               or an array of multiple IDs. When viewing a single group, defaults to the
 *                                               current group ID. When viewing a user's Groups stream page, defaults to the
 *                                               IDs of the user's groups. Otherwise defaults to false.
 *     @type int|array|bool    $secondary_id     Filters by the `secondary_item_id` column in the database. The meaning of
 *                                               'secondary_id' differs between components/types. Accepts a single ID, or an
 *                                               array of multiple IDs. Defaults to false.
 *     @type int               $offset           Return only activity items with an ID greater than or equal to this one.
 *                                               Note that providing an offset will disable pagination. Default: false.
 *     @type string|bool       $display_comments How to handle activity comments. Possible values:
 *                                                 - 'threaded' - comments appear in a threaded tree, under their parent
 *                                                   items.
 *                                                 - 'stream' - the activity feed is presented in a flat manner, with
 *                                                   comments sorted in chronological order alongside other activity items.
 *                                                 - false - don't fetch activity comments at all.
 *                                               Default: 'threaded'.
 *     @type bool              $show_hidden      Whether to show items marked hide_sitewide. Defaults to false, except in
 *                                               the following cases:
 *                                                 - User is viewing his own activity feed.
 *                                                 - User is viewing the activity feed of a non-public group of which he
 *                                                   is a member.
 *     @type string|bool       $spam             Spam status. 'ham_only', 'spam_only', or false to show all activity
 *                                               regardless of spam status. Default: 'ham_only'.
 *     @type bool              $populate_extras  Whether to pre-fetch the activity metadata for the queried items.
 *                                               Default: true.
 * }
 * @return bool Returns true when activities are found, otherwise false.
 */
function bp_has_activities( $args = '' ) {
	global $activities_template;

	$args = bp_parse_args( $args );

	// Get BuddyPress.
	$bp = buddypress();

	/*
	 * Smart Defaults.
	 */

	// User filtering.
	$user_id = bp_displayed_user_id()
		? bp_displayed_user_id()
		: false;

	// The default scope should recognize custom slugs.
	$scope = array_key_exists( bp_current_action(), (array) $bp->loaded_components )
		? $bp->loaded_components[ bp_current_action() ]
		: (
		( ! empty( bp_current_action() ) && ! is_numeric( bp_current_action() ) )
			? bp_current_action()
			: ( isset( $_REQUEST['scope'] ) ? $_REQUEST['scope'] : 'all' )
		);

	if ( bp_is_user_activity() || bp_is_activity_directory() ) {

		// Scope from the heartbeat passed from the filter dropdown.	
		$scope = ! empty( $args['scope'] ) ? $args['scope'] : $scope;
	}	

	$scope = bp_activity_default_scope( $scope );	

	// Group filtering.
	if ( bp_is_group() ) {
		$object          = $bp->groups->id;
		$args['privacy'] = ( isset( $args['privacy'] ) ? $args['privacy'] : array( 'public' ) );
		$primary_id      = bp_get_current_group_id();
		$show_hidden     = (bool) ( groups_is_user_member( bp_loggedin_user_id(), $primary_id ) || bp_current_user_can( 'bp_moderate' ) );
	} else {
		$object      = false;
		$primary_id  = false;
		$show_hidden = false;
	}

	// Support for permalinks on single item pages: /groups/my-group/activity/124/.
	$include = bp_is_current_action( bp_get_activity_slug() )
		? bp_action_variable( 0 )
		: false;

	$search_terms_default = false;
	$search_query_arg     = bp_core_get_component_search_query_arg( 'activity' );
	if ( ! empty( $_REQUEST[ $search_query_arg ] ) ) {
		$search_terms_default = stripslashes( $_REQUEST[ $search_query_arg ] );
	}

	/*
	 * Parse Args.
	 */

	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'display_comments'  => 'threaded',   // False for none, stream/threaded - show comments in the stream or threaded under items.
			'include'           => $include,     // Pass an activity_id or string of IDs comma-separated.
			'exclude'           => false,        // Pass an activity_id or string of IDs comma-separated.
			'in'                => false,        // Comma-separated list or array of activity IDs among which to search.
			'sort'              => 'DESC',       // Sort DESC or ASC.
			'page'              => 1,            // Which page to load.
			'per_page'          => 20,           // Number of items per page.
			'page_arg'          => 'acpage',     // See https://buddypress.trac.wordpress.org/ticket/3679.
			'max'               => false,        // Max number to return.
			'fields'            => 'all',
			'count_total'       => false,
			'show_hidden'       => $show_hidden, // Show activity items that are hidden site-wide?
			'spam'              => 'ham_only',   // Hide spammed items.

			// Scope - pre-built activity filters for a user (friends/groups/favorites/mentions).
			'scope'             => $scope,

			// Filtering.
			'user_id'           => $user_id,     // user_id to filter on.
			'object'            => $object,      // Object to filter on e.g. groups, profile, status, friends.
			'action'            => false,        // Action to filter on e.g. activity_update, profile_updated.
			'primary_id'        => $primary_id,  // Object ID to filter on e.g. a group_id or blog_id etc.
			'secondary_id'      => false,        // Secondary object ID to filter on e.g. a post_id.
			'offset'            => false,        // Return only items >= this ID.
			'since'             => false,        // Return only items recorded since this Y-m-d H:i:s date.
			'privacy'           => false,        // privacy to filter on - public, onlyme, loggedin, friends, media, document.

			'meta_query'        => false,        // Filter on activity meta. See WP_Meta_Query for format.
			'date_query'        => false,        // Filter by date. See first parameter of WP_Date_Query for format.
			'filter_query'      => false,        // Advanced filtering.  See BP_Activity_Query for format.

			// Pinned post.
			'pin_type'          => '',           // Show pinned post for type of feed - group, activity.

			// Searching.
			'search_terms'      => $search_terms_default,
			'update_meta_cache' => true,
			'status'            => bb_get_activity_published_status(),
		),
		'has_activities'
	);

	/*
	 * Smart Overrides.
	 */

	// Translate various values for 'display_comments'
	// This allows disabling comments via ?display_comments=0
	// or =none or =false. Final true is a strict type check. See #5029.
	if ( in_array( $r['display_comments'], array( 0, '0', 'none', 'false' ), true ) ) {
		$r['display_comments'] = false;
	}

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

	/**
	 * Filters whether BuddyPress should enable afilter support.
	 *
	 * Support for basic filters in earlier BP versions is disabled by default.
	 * To enable, put add_filter( 'bp_activity_enable_afilter_support', '__return_true' );
	 * into bp-custom.php or your theme's functions.php.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value True if BuddyPress should enable afilter support.
	 */
	if ( isset( $_GET['afilter'] ) && apply_filters( 'bp_activity_enable_afilter_support', false ) ) {
		$r['filter'] = array(
			'object' => $_GET['afilter'],
		);
	} elseif (
		( 
			'just-me' === $scope &&
			bp_is_activity_directory()
		) ||
		(
			! empty( $r['user_id'] ) ||
			! empty( $r['object'] ) ||
			! empty( $r['action'] ) ||
			! empty( $r['primary_id'] ) ||
			! empty( $r['secondary_id'] ) ||
			! empty( $r['offset'] ) ||
			! empty( $r['since'] )
		)
	) {
		if ( 'just-me' === $scope && bp_is_activity_directory() ) {
			$r['user_id'] = bp_loggedin_user_id();
		}
	
		$r['filter'] = array(
			'user_id'      => $r['user_id'],
			'object'       => $r['object'],
			'action'       => $r['action'],
			'primary_id'   => $r['primary_id'],
			'secondary_id' => $r['secondary_id'],
			'offset'       => $r['offset'],
			'since'        => $r['since'],
		);
	} else {
		$r['filter'] = false;
	}

	// If specific activity items have been requested, override the $hide_spam
	// argument. This prevents backpat errors with AJAX.
	if ( ! empty( $r['include'] ) && ( 'ham_only' === $r['spam'] ) ) {
		$r['spam'] = 'all';
	}

	// Pinned post.
	if ( empty( $r['pin_type'] ) ) {
		$r['pin_type'] = bb_activity_pin_type( $r );
	}

	/*
	 * Query
	 */

	$activities_template = new BP_Activity_Template( $r );

	/**
	 * Filters whether or not there are activity items to display.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param bool   $value               Whether or not there are activity items to display.
	 * @param string $activities_template Current activities template being used.
	 * @param array  $r                   Array of arguments passed into the BP_Activity_Template class.
	 */
	return apply_filters( 'bp_has_activities', $activities_template->has_activities(), $activities_template, $r );
}

/**
 * Determine if there are still activities left in the loop.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return bool Returns true when activities are found.
 */
function bp_activities() {
	global $activities_template;
	return $activities_template->user_activities();
}

/**
 * Get the current activity object in the loop.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return object The current activity within the loop.
 */
function bp_the_activity() {
	global $activities_template;
	return $activities_template->the_activity();
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyPress 2.1.0
 */
function bp_activity_load_more_link() {
	echo esc_url( bp_get_activity_load_more_link() );
}
/**
 * Get the URL for the Load More link.
 *
 * @since BuddyPress 2.1.0
 *
 * @return string $link
 */
function bp_get_activity_load_more_link() {
	global $activities_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $activities_template->pag_arg, $activities_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $activities_template The activity template loop global.
	 */
	return apply_filters( 'bp_get_activity_load_more_link', $link, $url, $activities_template );
}

/**
 * Output the activity pagination count.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 */
function bp_activity_pagination_count() {
	echo bp_get_activity_pagination_count();
}

/**
 * Return the activity pagination count.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The pagination text.
 */
function bp_get_activity_pagination_count() {
	global $activities_template;

	$start_num = intval( ( $activities_template->pag_page - 1 ) * $activities_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $activities_template->pag_num - 1 ) > $activities_template->total_activity_count ) ? $activities_template->total_activity_count : $start_num + ( $activities_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $activities_template->total_activity_count );

	$message = sprintf( _n( 'Viewing 1 item', 'Viewing %1$s - %2$s of %3$s items', $activities_template->total_activity_count, 'buddyboss' ), $from_num, $to_num, $total );

	return $message;
}

/**
 * Output the activity pagination links.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_pagination_links() {
	echo bp_get_activity_pagination_links();
}

/**
 * Return the activity pagination links.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The pagination links.
 */
function bp_get_activity_pagination_links() {
	global $activities_template;

	/**
	 * Filters the activity pagination link output.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $pag_links Output for the activity pagination links.
	 */
	return apply_filters( 'bp_get_activity_pagination_links', $activities_template->pag_links );
}

/**
 * Return true when there are more activity items to be shown than currently appear.
 *
 * @since BuddyPress 1.5.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return bool $has_more_items True if more items, false if not.
 */
function bp_activity_has_more_items() {
	global $activities_template;

	if ( ! empty( $activities_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $activities_template->pag_page ) ) {
			$remaining_pages = floor( ( $activities_template->total_activity_count - 1 ) / ( $activities_template->pag_num * $activities_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more activity items to display.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $has_more_items Whether or not there are more activity items to display.
	 */
	return apply_filters( 'bp_activity_has_more_items', $has_more_items );
}

/**
 * Output the activity count.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_count() {
	echo bp_get_activity_count();
}

/**
 * Return the activity count.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int The activity count.
 */
function bp_get_activity_count() {
	global $activities_template;

	/**
	 * Filters the activity count for the activity template.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $activity_count The count for total activity.
	 */
	return apply_filters( 'bp_get_activity_count', (int) $activities_template->activity_count );
}

/**
 * Output the number of activities per page.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_per_page() {
	echo bp_get_activity_per_page();
}

/**
 * Return the number of activities per page.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int The activities per page.
 */
function bp_get_activity_per_page() {
	global $activities_template;

	/**
	 * Filters the activity posts per page value.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $pag_num How many post should be displayed for pagination.
	 */
	return apply_filters( 'bp_get_activity_per_page', (int) $activities_template->pag_num );
}

/**
 * Output the activities title.
 *
 * @since BuddyPress 1.0.0
 *
 * @todo Deprecate.
 */
function bp_activities_title() {
	echo bp_get_activities_title();
}

/**
 * Return the activities title.
 *
 * @since BuddyPress 1.0.0
 *
 * @global string $bp_activity_title
 * @todo Deprecate.
 *
 * @return string The activities title.
 */
function bp_get_activities_title() {
	global $bp_activity_title;

	/**
	 * Filters the activities title for the activity template.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $bp_activity_title The title to be displayed.
	 */
	return apply_filters( 'bp_get_activities_title', $bp_activity_title );
}

/**
 * Output text when there is no activity
 *
 * @since BuddyPress 1.0.0
 *
 * @todo Deprecate.
 */
function bp_activities_no_activity() {
	echo bp_get_activities_no_activity();
}

/**
 * Get text when there is no activity
 *
 * @since BuddyPress 1.0.0
 *
 * @global string $bp_activity_no_activity
 * @todo Deprecate.
 *
 * @return string
 */
function bp_get_activities_no_activity() {
	global $bp_activity_no_activity;

	/**
	 * Filters the text used when there is no activity to display.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $bp_activity_no_activity Text to display for no activity.
	 */
	return apply_filters( 'bp_get_activities_no_activity', $bp_activity_no_activity );
}

/**
 * Output the activity ID.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_id() {
	echo bp_get_activity_id();
}

/**
 * Return the activity ID.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int The activity ID.
 */
function bp_get_activity_id() {
	global $activities_template;

	$activity_id = 0;
	if ( isset( $activities_template ) && isset( $activities_template->activity ) && isset( $activities_template->activity->id ) ) {
		$activity_id = $activities_template->activity->id;
	}

	/**
	 * Filters the activity ID being displayed.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $id The activity ID.
	 */
	return apply_filters( 'bp_get_activity_id', $activity_id );
}

/**
 * Output the activity item ID.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_item_id() {
	echo bp_get_activity_item_id();
}

/**
 * Return the activity item ID.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int The activity item ID.
 */
function bp_get_activity_item_id() {
	global $activities_template;

	/**
	 * Filters the activity item ID being displayed.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $item_id The activity item ID.
	 */
	return apply_filters( 'bp_get_activity_item_id', $activities_template->activity->item_id );
}

/**
 * Output the activity secondary item ID.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_secondary_item_id() {
	echo bp_get_activity_secondary_item_id();
}

/**
 * Return the activity secondary item ID.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int The activity secondary item ID.
 */
function bp_get_activity_secondary_item_id() {
	global $activities_template;

	/**
	 * Filters the activity secondary item ID being displayed.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $secondary_item_id The activity secondary item ID.
	 */
	return apply_filters( 'bp_get_activity_secondary_item_id', $activities_template->activity->secondary_item_id );
}

/**
 * Output the date the activity was recorded.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_date_recorded() {
	echo bp_get_activity_date_recorded();
}

/**
 * Output the date the activity was updated.
 *
 * @since BuddyBoss 2.8.20
 */
function bb_activity_date_updated() {
	echo bb_get_activity_date_updated();
}

/**
 * Return the date the activity was recorded.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The date the activity was recorded.
 */
function bp_get_activity_date_recorded() {
	global $activities_template;

	/**
	 * Filters the date the activity was recorded.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $date_recorded The activity's date.
	 */
	return apply_filters( 'bp_get_activity_date_recorded', $activities_template->activity->date_recorded );
}

/**
 * Return the date the activity was updated.
 *
 * @since BuddyBoss 2.8.20
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The date the activity was updated.
 */
function bb_get_activity_date_updated() {
	global $activities_template;

	/**
	 * Filters the date the activity was updated.
	 *
	 * @since BuddyBoss 2.8.20
	 *
	 * @param int $date_updated The activity's date.
	 */
	return apply_filters( 'bb_get_activity_date_updated', $activities_template->activity->date_updated );
}

/**
 * Output the display name of the member who posted the activity.
 *
 * @since BuddyPress 2.1.0
 */
function bp_activity_member_display_name() {
	echo bp_get_activity_member_display_name();
}

/**
 * Return the display name of the member who posted the activity.
 *
 * @since BuddyPress 2.1.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The date the activity was recorded.
 */
function bp_get_activity_member_display_name() {
	global $activities_template;

	$retval = isset( $activities_template->activity->display_name )
		? $activities_template->activity->display_name
		: '';

	/**
	 * Filters the display name of the member who posted the activity.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param int $retval Display name for the member who posted.
	 */
	return apply_filters( 'bp_get_activity_member_display_name', $retval );
}
add_filter( 'bp_get_activity_member_display_name', 'wp_filter_kses' );
add_filter( 'bp_get_activity_member_display_name', 'stripslashes' );
add_filter( 'bp_get_activity_member_display_name', 'strip_tags' );
add_filter( 'bp_get_activity_member_display_name', 'esc_html' );

/**
 * Output the activity object name.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_object_name() {
	echo bp_get_activity_object_name();
}

/**
 * Return the activity object name.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity object name.
 */
function bp_get_activity_object_name() {
	global $activities_template;

	/**
	 * Filters the activity object name.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $activity_component The activity object name.
	 */
	return apply_filters( 'bp_get_activity_object_name', $activities_template->activity->component );
}

/**
 * Output the activity type.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_type() {
	echo bp_get_activity_type();
}

/**
 * Return the activity type.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity type.
 */
function bp_get_activity_type() {
	global $activities_template;

	/**
	 * Filters the activity type.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $activity_type The activity type.
	 */
	return apply_filters( 'bp_get_activity_type', $activities_template->activity->type );
}

/**
 * Output the activity action name.
 *
 * Just a wrapper for bp_activity_type().
 *
 * @since BuddyPress 1.2.0
 * @deprecated 1.5.0
 *
 * @todo Properly deprecate in favor of bp_activity_type() and
 *       remove redundant echo
 */
function bp_activity_action_name() {
	echo bp_activity_type(); }

/**
 * Return the activity type.
 *
 * Just a wrapper for bp_get_activity_type().
 *
 * @since BuddyPress 1.2.0
 * @deprecated 1.5.0
 *
 * @todo Properly deprecate in favor of bp_get_activity_type().
 *
 * @return string The activity type.
 */
function bp_get_activity_action_name() {
	return bp_get_activity_type(); }

/**
 * Output the activity user ID.
 *
 * @since BuddyPress 1.1.0
 */
function bp_activity_user_id() {
	echo bp_get_activity_user_id();
}

/**
 * Return the activity user ID.
 *
 * @since BuddyPress 1.1.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int The activity user ID.
 */
function bp_get_activity_user_id() {
	global $activities_template;

	/**
	 * Filters the activity user ID.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param int $user_id The activity user ID.
	 */
	return apply_filters( 'bp_get_activity_user_id', $activities_template->activity->user_id );
}

/**
 * Output the activity user link.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_user_link() {
	echo bp_get_activity_user_link();
}

/**
 * Return the activity user link.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string $link The activity user link.
 */
function bp_get_activity_user_link() {
	global $activities_template;

	if ( empty( $activities_template->activity->user_id ) || empty( $activities_template->activity->user_nicename ) || empty( $activities_template->activity->user_login ) ) {
		$link = $activities_template->activity->primary_link;
	} else {
		$link = bp_core_get_user_domain( $activities_template->activity->user_id, $activities_template->activity->user_nicename, $activities_template->activity->user_login );
	}

	/**
	 * Filters the activity user link.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $link The activity user link.
	 */
	return apply_filters( 'bp_get_activity_user_link', $link );
}

/**
 * Output the avatar of the user that performed the action.
 *
 * @since BuddyPress 1.1.0
 *
 * @see bp_get_activity_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_activity_avatar()} for description.
 */
function bp_activity_avatar( $args = '' ) {
	echo bp_get_activity_avatar( $args );
}
/**
 * Return the avatar of the user that performed the action.
 *
 * @since BuddyPress 1.1.0
 *
 * @see bp_core_fetch_avatar() For a description of the arguments.
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param array|string $args  {
 *     Arguments are listed here with an explanation of their defaults.
 *     For more information about the arguments, see
 *     {@link bp_core_fetch_avatar()}.
 *     @type string      $alt     Default: 'Profile picture of [user name]' if
 *                                activity user name is available, otherwise 'Profile picture'.
 *     @type string      $class   Default: 'avatar'.
 *     @type string|bool $email   Default: Email of the activity's
 *                                associated user, if available. Otherwise false.
 *     @type string      $type    Default: 'full' when viewing a single activity
 *                                permalink page, otherwise 'thumb'.
 *     @type int|bool    $user_id Default: ID of the activity's user.
 * }
 * @return string User avatar string.
 */
function bp_get_activity_avatar( $args = '' ) {
	global $activities_template;

	$bp = buddypress();

	// On activity permalink pages, default to the full-size avatar.
	$type_default = bp_is_single_activity() ? 'full' : 'thumb';

	// Within the activity comment loop, the current activity should be set
	// to current_comment. Otherwise, just use activity.
	$current_activity_item = isset( $activities_template->activity->current_comment ) ? $activities_template->activity->current_comment : $activities_template->activity;

	// Activity user display name.
	$dn_default = isset( $current_activity_item->display_name ) ? $current_activity_item->display_name : '';

	// Prepend some descriptive text to alt.
	$alt_default = ! empty( $dn_default ) ? sprintf( __( 'Profile photo of %s', 'buddyboss' ), $dn_default ) : __( 'Profile photo', 'buddyboss' );

	$defaults = array(
		'alt'     => $alt_default,
		'class'   => 'avatar',
		'email'   => false,
		'type'    => $type_default,
		'user_id' => false,
	);

	$r = bp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	if ( ! isset( $height ) && ! isset( $width ) ) {

		// Backpat.
		if ( isset( $bp->avatar->full->height ) || isset( $bp->avatar->thumb->height ) ) {
			$height = ( 'full' === $type ) ? $bp->avatar->full->height : $bp->avatar->thumb->height;
		} else {
			$height = 20;
		}

		// Backpat.
		if ( isset( $bp->avatar->full->width ) || isset( $bp->avatar->thumb->width ) ) {
			$width = ( 'full' === $type ) ? $bp->avatar->full->width : $bp->avatar->thumb->width;
		} else {
			$width = 20;
		}
	}

	/**
	 * Filters the activity avatar object based on current activity item component.
	 *
	 * This is a variable filter dependent on the component used.
	 * Possible hooks are bp_get_activity_avatar_object_blog,
	 * bp_get_activity_avatar_object_group, and bp_get_activity_avatar_object_user.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $component Component being displayed.
	 */
	$object  = apply_filters( 'bp_get_activity_avatar_object_' . $current_activity_item->component, 'user' );
	$item_id = ! empty( $user_id ) ? $user_id : $current_activity_item->user_id;

	/**
	 * Filters the activity avatar item ID.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param int $item_id Item ID for the activity avatar.
	 */
	$item_id = apply_filters( 'bp_get_activity_avatar_item_id', $item_id );

	// If this is a user object pass the users' email address for Gravatar so we don't have to prefetch it.
	if ( 'user' === $object && empty( $user_id ) && empty( $email ) && isset( $current_activity_item->user_email ) ) {
		$email = $current_activity_item->user_email;
	}

	/**
	 * Filters the value returned by bp_core_fetch_avatar.
	 *
	 * @since BuddyPress 1.1.3
	 *
	 * @param array $value HTML image element containing the activity avatar.
	 */
	return apply_filters(
		'bp_get_activity_avatar',
		bp_core_fetch_avatar(
			array(
				'item_id' => $item_id,
				'object'  => $object,
				'type'    => $type,
				'alt'     => $alt,
				'class'   => $class,
				'width'   => $width,
				'height'  => $height,
				'email'   => $email,
			)
		)
	);
}

/**
 * Output the avatar of the object that action was performed on.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_get_activity_secondary_avatar() for description of arguments.
 *
 * @param array|string $args See {@link bp_get_activity_secondary_avatar} for description.
 */
function bp_activity_secondary_avatar( $args = '' ) {
	echo bp_get_activity_secondary_avatar( $args );
}

/**
 * Return the avatar of the object that action was performed on.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_core_fetch_avatar() for description of arguments.
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param array|string $args  {
 *     For a complete description of arguments, see {@link bp_core_fetch_avatar()}.
 *     @type string      $alt     Default value varies based on current activity
 *                                item component.
 *     @type string      $type    Default: 'full' when viewing a single activity
 *                                permalink page, otherwise 'thumb'.
 *     @type string      $class   Default: 'avatar'.
 *     @type string|bool $email   Default: email of the activity's user.
 *     @type int|bool    $user_id Default: ID of the activity's user.
 * }
 * @return string The secondary avatar.
 */
function bp_get_activity_secondary_avatar( $args = '' ) {
	global $activities_template;

	$r = bp_parse_args(
		$args,
		array(
			'alt'        => '',
			'type'       => 'thumb',
			'width'      => 20,
			'height'     => 20,
			'class'      => 'avatar',
			'link_class' => '',
			'linked'     => true,
			'email'      => false,
		)
	);
	extract( $r, EXTR_SKIP );

	$attribute = '';

	// Set item_id and object (default to user).
	switch ( $activities_template->activity->component ) {
		case 'groups':
			if ( bp_disable_group_avatar_uploads() ) {
				return false;
			}

			$object  = 'group';
			$item_id = $activities_template->activity->item_id;
			$link    = '';
			$name    = '';

			// Only if groups is active.
			if ( bp_is_active( 'groups' ) ) {
				$group = groups_get_group( $item_id );
				$link  = bp_get_group_permalink( $group );
				$name  = $group->name;
			}

			if ( ! empty( $group->id ) ) {
				$attribute = 'data-bb-hp-group="' . esc_attr( $group->id ) . '"';
			}

			if ( empty( $alt ) ) {
				$alt = __( 'Group logo', 'buddyboss' );

				if ( ! empty( $name ) ) {
					$alt = sprintf( __( 'Group logo of %s', 'buddyboss' ), $name );
				}
			}

			break;
		case 'blogs':
			$object  = 'blog';
			$item_id = $activities_template->activity->item_id;
			$link    = home_url();

			if ( empty( $alt ) ) {
				$alt = sprintf( __( 'Profile photo of the author of the site %s', 'buddyboss' ), get_blog_option( $item_id, 'blogname' ) );
			}

			break;
		case 'friends':
			$object  = 'user';
			$item_id = $activities_template->activity->secondary_item_id;
			$link    = bp_core_get_userlink( $item_id, false, true );

			if ( empty( $alt ) ) {
				$alt = sprintf( __( 'Profile photo of %s', 'buddyboss' ), bp_core_get_user_displayname( $activities_template->activity->secondary_item_id ) );
			}

			if ( ! empty( $item_id ) ) {
				$attribute = 'data-bb-hp-profile="' . esc_attr( $item_id ) . '"';
			}

			break;
		default:
			$object  = 'user';
			$item_id = $activities_template->activity->user_id;
			$email   = $activities_template->activity->user_email;
			$link    = bp_core_get_userlink( $item_id, false, true );

			if ( ! empty( $item_id ) ) {
				$attribute = 'data-bb-hp-profile="' . esc_attr( $item_id ) . '"';
			}

			if ( empty( $alt ) ) {
				$alt = sprintf( __( 'Profile photo of %s', 'buddyboss' ), $activities_template->activity->display_name );
			}

			break;
	}

	/**
	 * Filters the activity secondary avatar object based on current activity item component.
	 *
	 * This is a variable filter dependent on the component used. Possible hooks are
	 * bp_get_activity_secondary_avatar_object_blog, bp_get_activity_secondary_avatar_object_group,
	 * and bp_get_activity_secondary_avatar_object_user.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param string $object Component being displayed.
	 */
	$object = apply_filters( 'bp_get_activity_secondary_avatar_object_' . $activities_template->activity->component, $object );

	/**
	 * Filters the activity secondary avatar item ID.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param int $item_id ID for the secondary avatar item.
	 */
	$item_id = apply_filters( 'bp_get_activity_secondary_avatar_item_id', $item_id );

	// If we have no item_id or object, there is no avatar to display.
	if ( empty( $item_id ) || empty( $object ) ) {
		return false;
	}

	// Get the avatar.
	$avatar = bp_core_fetch_avatar(
		array(
			'item_id' => $item_id,
			'object'  => $object,
			'type'    => $type,
			'alt'     => $alt,
			'class'   => $class,
			'width'   => $width,
			'height'  => $height,
			'email'   => $email,
		)
	);

	if ( ! empty( $linked ) ) {

		/**
		 * Filters the secondary avatar link for current activity.
		 *
		 * @since BuddyPress 1.7.0
		 *
		 * @param string $link      Link to wrap the avatar image in.
		 * @param string $component Activity component being acted on.
		 */
		$link = apply_filters( 'bp_get_activity_secondary_avatar_link', $link, $activities_template->activity->component );

		/**
		 * Filters the determined avatar for the secondary activity item.
		 *
		 * @since BuddyPress 1.2.10
		 *
		 * @param string $avatar Formatted HTML <img> element, or raw avatar URL.
		 */
		$avatar = apply_filters( 'bp_get_activity_secondary_avatar', $avatar );

		return sprintf(
			'<a href="%s" class="%s" %s>%s</a>',
			$link,
			$link_class,
			$attribute,
			$avatar
		);
	}

	/** This filter is documented in bp-activity/bp-activity-template.php */
	return apply_filters( 'bp_get_activity_secondary_avatar', $avatar );
}

/**
 * Output the activity action.
 *
 * @since BuddyPress 1.2.0
 *
 * @param array $args See bp_get_activity_action().
 */
function bp_activity_action( $args = array() ) {
	echo bp_get_activity_action( $args );
}

/**
 * Return the activity action.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param array $args {
 *     @type bool $no_timestamp Whether to exclude the timestamp.
 * }
 *
 * @return string The activity action.
 */
function bp_get_activity_action( $args = array() ) {
	global $activities_template;

	$r = bp_parse_args(
		$args,
		array(
			'no_timestamp' => false,
		)
	);

	/**
	 * Filters the activity action before the action is inserted as meta.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param array $value Array containing the current action, the current activity, and the $args array passed into the function.
	 */
	$action = apply_filters_ref_array(
		'bp_get_activity_action_pre_meta',
		array(
			$activities_template->activity->action,
			&$activities_template->activity,
			$r,
		)
	);

	// Prepend the activity action meta (link, time since, etc...).
	if ( ! empty( $action ) && empty( $r['no_timestamp'] ) ) {
		$action = bp_insert_activity_meta( $action );
	}

	/**
	 * Filters the activity action after the action has been inserted as meta.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $value Array containing the current action, the current activity, and the $args array passed into the function.
	 */
	return apply_filters_ref_array(
		'bp_get_activity_action',
		array(
			$action,
			&$activities_template->activity,
			$r,
		)
	);
}

/**
 * Output the activity content body.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_content_body() {
	echo bp_get_activity_content_body();
}

/**
 * Return the activity content body.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity content body.
 */
function bp_get_activity_content_body() {
	global $activities_template;

	// Backwards compatibility if action is not being used.
	if ( empty( $activities_template->activity->action ) && ! empty( $activities_template->activity->content ) ) {
		$activities_template->activity->content = bp_insert_activity_meta( $activities_template->activity->content );
	}

	// scrape off activity content if it contain empty characters only
	if ( in_array( $activities_template->activity->content, array( '&nbsp;', '&#8203;' ) ) ) {
		$activities_template->activity->content = '';
	}

	/**
	 * Filters the activity content body.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $content  Content body.
	 * @param object $activity Activity object. Passed by reference.
	 */
	return apply_filters_ref_array( 'bp_get_activity_content_body', array( $activities_template->activity->content, &$activities_template->activity ) );
}

/**
 * Does the activity have content?
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return bool True if activity has content, false otherwise.
 */
function bp_activity_has_content() {
	global $activities_template;

	if ( ! empty( $activities_template->activity->content ) ) {
		return true;
	}

	return false;
}

/**
 * Output the activity content.
 *
 * @since BuddyPress 1.0.0
 * @deprecated 1.5.0
 *
 * @todo properly deprecate this function.
 */
function bp_activity_content() {
	echo bp_get_activity_content();
}

/**
 * Return the activity content.
 *
 * @since BuddyPress 1.0.0
 * @deprecated 1.5.0
 *
 * @todo properly deprecate this function.
 *
 * @return string The activity content.
 */
function bp_get_activity_content() {

	global $activities_template;

	/**
	 * If you want to filter activity update content, please use
	 * the filter 'bp_get_activity_content_body'.
	 *
	 * This function is mainly for backwards compatibility.
	 */
	$content = bp_get_activity_action() . ' ' . bp_get_activity_content_body();
	// return apply_filters( 'bp_get_activity_content', $content );
	return apply_filters_ref_array( 'bp_get_activity_content', array( $content, &$activities_template->activity ) );
}

/**
 * Output the activity privacy.
 *
 * @since BuddyBoss 1.2.0
 */
function bp_activity_privacy() {
	echo bp_get_activity_privacy();
}

/**
 * Return the activity privacy.
 *
 * @since BuddyBoss 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity privacy.
 */
function bp_get_activity_privacy() {
	global $activities_template;

	/**
	 * Filters the activity privacy.
	 *
	 * @since BuddyBoss1.2.0
	 *
	 * @param string $privacy  Content body.
	 * @param object $activity Activity object. Passed by reference.
	 */
	return apply_filters_ref_array( 'bp_get_activity_privacy', array( $activities_template->activity->privacy, &$activities_template->activity ) );
}

/**
 * Attach metadata about an activity item to the activity content.
 *
 * This metadata includes the time since the item was posted (which will appear
 * as a link to the item's permalink).
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param string $content The activity content.
 * @return string The activity content with the metadata string attached.
 */
function bp_insert_activity_meta( $content = '' ) {
	global $activities_template;

	// Strip any legacy time since placeholders from BP 1.0-1.1.
	$new_content = str_replace( '<span class="time-since">%s</span>', '', $content );

	// Get the time since this activity was recorded.
	$date_recorded = bp_core_time_since( $activities_template->activity->date_recorded );

	$time_since = '';
	// Remove time since from single activity page.
	if ( ! bp_is_single_activity() ) {
		// Set up 'time-since' <span>.
		$time_since = sprintf(
			'<span class="time-since" data-livestamp="%1$s">%2$s</span>',
			bp_core_get_iso8601_date( $activities_template->activity->date_recorded ),
			$date_recorded
		);
	}

	/**
	 * Filters the activity item time since markup.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $value Array containing the time since markup and the current activity component.
	 */
	$time_since = apply_filters_ref_array(
		'bp_activity_time_since',
		array(
			$time_since,
			&$activities_template->activity,
		)
	);

	// Insert the permalink.
	if ( ! bp_is_single_activity() ) {

		// Setup variables for activity meta.
		$activity_permalink = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );
		$activity_meta      = sprintf(
			'%1$s <a href="%2$s" class="view activity-time-since">%3$s</a>',
			$new_content,
			$activity_permalink,
			$time_since
		);

		/**
		 * Filters the activity permalink to be added to the activity content.
		 *
		 * @since BuddyPress 1.2.0
		 *
		 * @param array $value Array containing the html markup for the activity permalink, after being parsed by
		 *                     sprintf and current activity component.
		 */
		$new_content = apply_filters_ref_array(
			'bp_activity_permalink',
			array(
				$activity_meta,
				&$activities_template->activity,
			)
		);
	} else {
		$new_content .= str_pad( $time_since, strlen( $time_since ) + 2, ' ', STR_PAD_BOTH );
	}

	/**
	 * Filters the activity content after activity metadata has been attached.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $content Activity content with the activity metadata added.
	 */
	return apply_filters( 'bp_insert_activity_meta', $new_content, $content );
}

/**
 * Determine if the current user can delete an activity item.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param false|BP_Activity_Activity $activity Optional. Falls back on the current item in the loop.
 * @return bool True if can delete, false otherwise.
 */
function bp_activity_user_can_delete( $activity = false ) {
	global $activities_template;

	// Try to use current activity if none was passed.
	if ( empty( $activity ) && ! empty( $activities_template->activity ) ) {
		$activity = $activities_template->activity;
	}

	// If current_comment is set, we'll use that in place of the main activity.
	if ( isset( $activity->current_comment ) ) {
		$activity = $activity->current_comment;
	}

	// Assume the user cannot delete the activity item.
	$can_delete = false;

	// Only logged in users can delete activity.
	if ( is_user_logged_in() ) {

		// Community moderators can always delete activity (at least for now).
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$can_delete = true;
		}

		// Users are allowed to delete their own activity. This is actually
		// quite powerful, because doing so also deletes all comments to that
		// activity item. We should revisit this eventually.
		if ( isset( $activity->user_id ) && ( $activity->user_id === bp_loggedin_user_id() ) ) {
			$can_delete = true;
		}

		// Viewing a single item, and this user is an admin of that item.
		if ( 'groups' !== $activity->component && bp_is_single_item() && bp_is_item_admin() ) {
			$can_delete = true;
		}
	}

	/**
	 * Filters whether the current user can delete an activity item.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool   $can_delete Whether the user can delete the item.
	 * @param object $activity   Current activity item object.
	 */
	return (bool) apply_filters( 'bp_activity_user_can_delete', $can_delete, $activity );
}

/**
 * Determine if the current user can edit an activity item.
 *
 * @param false|BP_Activity_Activity $activity            Optional. Falls back on the current item in the loop.
 * @param bool                       $privacy_edit        Optional. True if editing privacy.
 *
 * @return bool True if can edit, false otherwise.
 * @global object                    $activities_template {@link BP_Activity_Template}
 *
 * @since BuddyBoss 1.2.0
 */
function bp_activity_user_can_edit( $activity = false, $privacy_edit = false ) {
	global $activities_template;

	// Try to use current activity if none was passed.
	if ( empty( $activity ) && ! empty( $activities_template->activity ) ) {
		$activity = $activities_template->activity;
	}

	// If current_comment is set, we'll use that in place of the main activity.
	if ( isset( $activity->current_comment ) ) {
		$activity = $activity->current_comment;
	}

	// Assume the user cannot edit the activity item.
	$can_edit = false;

	// Only logged in users can edit activity and Activity must be of type 'activity_update', 'activity_comment'
	if ( is_user_logged_in() && in_array( $activity->type, array( 'activity_update', 'activity_comment' ) ) ) {

		// Users are allowed to edit their own activity.
		if ( isset( $activity->user_id ) && ( bp_loggedin_user_id() === $activity->user_id ) ) {
			$can_edit = true;
		}

		// Viewing a single item, and this user is an admin of that item.
		if ( bp_is_single_item() && bp_is_item_admin() ) {
			$can_edit = true;
		}
	}

	if ( $can_edit && ! $privacy_edit ) {

		// Check activity edit time expiration.
		$activity_edit_time        = (int) bp_get_activity_edit_time(); // for 10 minutes, 600
		$bp_dd_get_time            = bp_core_current_time( true, 'timestamp' );
		$activity_edit_expire_time = strtotime( $activity->date_recorded ) + $activity_edit_time;

		// Checking if expire time still greater than current time.
		if ( - 1 !== $activity_edit_time && $activity_edit_expire_time <= $bp_dd_get_time ) {
			$can_edit = false;
		}
	}

	/**
	 * Filters whether the current user can edit an activity item.
	 *
	 * @param bool   $can_edit Whether the user can edit the item.
	 * @param object $activity Current activity item object.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	return (bool) apply_filters( 'bp_activity_user_can_edit', $can_edit, $activity );
}

/**
 * Output the activity parent content.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_get_activity_parent_content() for a description of arguments.
 *
 * @param array|string $args See {@link bp_get_activity_parent_content} for description.
 */
function bp_activity_parent_content( $args = '' ) {
	echo bp_get_activity_parent_content( $args );
}

/**
 * Return the activity content.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param string $args Unused. Left over from an earlier implementation.
 * @return mixed False on failure, otherwise the activity parent content.
 */
function bp_get_activity_parent_content( $args = '' ) {
	global $activities_template;

	// Bail if no activity on no item ID.
	if ( empty( $activities_template->activity ) || empty( $activities_template->activity->item_id ) ) {
		return false;
	}

	// Get the ID of the parent activity content.
	$parent_id = $activities_template->activity->item_id;

	// Bail if no parent content.
	if ( empty( $activities_template->activity_parents[ $parent_id ] ) ) {
		return false;
	}

	// Bail if no action.
	if ( empty( $activities_template->activity_parents[ $parent_id ]->action ) ) {
		return false;
	}

	// Content always includes action.
	$content = $activities_template->activity_parents[ $parent_id ]->action;

	// Maybe append activity content, if it exists.
	if ( ! empty( $activities_template->activity_parents[ $parent_id ]->content ) ) {
		$content .= ' ' . $activities_template->activity_parents[ $parent_id ]->content;
	}

	// Remove the time since content for backwards compatibility.
	$content = str_replace( '<span class="time-since">%s</span>', '', $content );

	// Remove images.
	$content = preg_replace( '/<img[^>]*>/Ui', '', $content );

	/**
	 * Filters the activity parent content.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $content Content set to be displayed as parent content.
	 */
	return apply_filters( 'bp_get_activity_parent_content', $content );
}

/**
 * Output the parent activity's user ID.
 *
 * @since BuddyPress 1.7.0
 */
function bp_activity_parent_user_id() {
	echo bp_get_activity_parent_user_id();
}

/**
 * Return the parent activity's user ID.
 *
 * @since BuddyPress 1.7.0
 *
 * @global BP_Activity_Template $activities_template
 *
 * @return bool|int False if parent activity can't be found, otherwise
 *                  the parent activity's user ID.
 */
function bp_get_activity_parent_user_id() {
	global $activities_template;

	// Bail if no activity on no item ID.
	if ( empty( $activities_template->activity ) || empty( $activities_template->activity->item_id ) ) {
		return false;
	}

	// Get the ID of the parent activity content.
	$parent_id = $activities_template->activity->item_id;

	// Bail if no parent item.
	if ( empty( $activities_template->activity_parents[ $parent_id ] ) ) {
		return false;
	}

	// Bail if no parent user ID.
	if ( empty( $activities_template->activity_parents[ $parent_id ]->user_id ) ) {
		return false;
	}

	$retval = $activities_template->activity_parents[ $parent_id ]->user_id;

	/**
	 * Filters the activity parent item's user ID.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param int $retval ID for the activity parent's user.
	 */
	return (int) apply_filters( 'bp_get_activity_parent_user_id', $retval );
}

/**
 * Output whether or not the current activity is in a current user's favorites.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_is_favorite() {
	echo bp_get_activity_is_favorite();
}

/**
 * Return whether the current activity is in a current user's favorites.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return bool True if user favorite, false otherwise.
 */
function bp_get_activity_is_favorite() {
	global $activities_template;

	/**
	 * Filters whether the current activity item is in the current user's favorites.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param bool $value Whether or not the current activity item is in the current user's favorites.
	 */
	return (bool) apply_filters( 'bp_get_activity_is_favorite', in_array( $activities_template->activity->id, (array) $activities_template->my_favs ) );
}

/**
 * Output the comment markup for an activity item.
 *
 * @since BuddyPress 1.2.0
 *
 * @todo deprecate $args param
 *
 * @param array|string $args See {@link bp_activity_get_comments} for description.
 */
function bp_activity_comments( $args = '' ) {
	echo bp_activity_get_comments( $args );
}

/**
 * Get the comment markup for an activity item.
 *
 * @since BuddyPress 1.2.0
 *
 * @todo deprecate $args param
 * @todo Given that checks for children already happen in bp_activity_recurse_comments(),
 *       this function can probably be streamlined or removed.
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param string $args Unused. Left over from an earlier implementation.
 * @return bool
 */
function bp_activity_get_comments( $args = '' ) {
	global $activities_template;

	if ( in_array( $activities_template->activity->component, array( 'blogs' ), true ) && ! bp_activity_can_comment() ) {
		return false;
	}

	if ( empty( $activities_template->activity->all_child_count ) ) {
		return false;
	}

	$args = array(
		'limit_comments'     => bp_is_single_activity() ? false : true,
		'comment_load_limit' => bb_get_activity_comment_visibility(),
	);
	bp_activity_recurse_comments( $activities_template->activity, $args );
}

/**
 * Loops through a level of activity comments and loads the template for each.
 * Note: The recursion itself used to happen entirely in this function. Now it is
 * split between here and the comment.php template.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyBoss 2.5.80
 * Added new param as args to pass some arguments to the function.
 *
 * @global object $activities_template    {@link BP_Activity_Template}
 *
 * @param object $comment The activity object currently being recursed.
 * @param array  $args {
 * Array of arguments.
 *
 * @type bool   $limit_comments         Limit comments loading or not Default: false.
 * @type int    $comment_load_limit     The number of comments to load. Default: 0.
 * @type int    $parent_comment_id      The ID of the parent comment.
 * @type int    $main_activity_id       The ID of the main activity.
 * @type bool   $is_ajax_load_more      Whether the comments are being loaded via AJAX.
 * @type string $last_comment_timestamp The timestamp of the last comment. Default: empty string.
 * }
 *
 * @return bool|string
 */
function bp_activity_recurse_comments( $comment, $args = array() ) {
	global $activities_template;

	$r = bp_parse_args(
		$args,
		array(
			'limit_comments'         => false,
			'comment_load_limit'     => 0,
			'parent_comment_id'      => 0,
			'main_activity_id'       => 0,
			'is_ajax_load_more'      => false,
			'last_comment_timestamp' => '',
			'last_comment_id'        => 0,
			'comment_order_by'       => apply_filters( 'bb_activity_recurse_comments_order_by', 'ASC' ),
		),
		'bb_activity_recurse_comments'
	);

	if ( empty( $comment ) ) {
		return false;
	}

	if ( empty( $comment->all_child_count ) ) {
		return false;
	}

	/**
	 * Filters the opening tag for the template that lists activity comments.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Opening tag for the HTML markup to use.
	 */
	if ( ! $r['is_ajax_load_more'] ) {
		if (
			false !== $r['limit_comments'] &&
			0 !== $r['comment_load_limit'] &&
			(
				$comment->top_level_count > $r['comment_load_limit']
			)
		) {
			echo '<a href="javascript:void(0);" class="view-more-comments">' . esc_html__( 'View more comments', 'buddyboss' ) . '</a>';
		}

		/**
		 * Filters the start of nested comment list in the activity.
		 *
		 * @since BuddyBoss 2.5.80
		 *
		 * @param string $output The HTML output of the start of the UL element.
		 */
		echo apply_filters( 'bb_activity_recurse_comments_start_ul', "<ul data-activity_id={$activities_template->activity->id} data-parent_comment_id={$comment->id}>" );
	}

	$comment_loaded_count = 0;
	foreach ( (array) $comment->children as $comment_child ) {

		if (
			false !== $r['limit_comments'] &&
			(
				$comment_loaded_count === $r['comment_load_limit']
			)
		) {
			if ( ! empty( $r['parent_comment_id'] ) && $r['main_activity_id'] !== $r['parent_comment_id'] ) {
				$view_more_text = __( 'View more replies', 'buddyboss' );
				$view_more_icon = "<i class='bb-icon-l bb-icon-corner-right'></i>";
			} else {
				$view_more_text = __( 'View more comments', 'buddyboss' );
				$view_more_icon = '';
			}

			$hidden_class = '';
			if ( ! $r['is_ajax_load_more'] ) {
				$hidden_class = 'acomments-view-more--hide';
			}

			echo '<li class="acomments-view-more acomments-view-more--root ' . esc_attr( $hidden_class ) . '">' . $view_more_icon . esc_html( $view_more_text ) . '</li>';
			break;
		}
		// Put the comment into the global so it's available to filters.
		$activities_template->activity->current_comment = $comment_child;

		$template = bp_locate_template( 'activity/comment.php', false, false );

		$comment_template_args = array( 'limit_comments' => $r['limit_comments'] );
		if (
			false !== $r['limit_comments'] &&
			(
				$comment->id === $comment_child->secondary_item_id ||
				$comment->item_id === $comment->secondary_item_id ||
				in_array( $comment->component, array( 'groups', 'blogs' ), true )
			)
		) {
			// First level comments.
			$comment_template_args['show_replies'] = false;
		}

		load_template( $template, false, $comment_template_args );

		unset( $activities_template->activity->current_comment );

		++$comment_loaded_count;
	}

	if ( ! $r['is_ajax_load_more'] ) {

		/**
		 * Filters the closing tag for the template that list activity comments.
		 *
		 * @since BuddyPress  1.6.0
		 *
		 * @param string $value Closing tag for the HTML markup to use.
		 */
		echo apply_filters( 'bp_activity_recurse_comments_end_ul', '</ul>' );
	}
}

/**
 * Utility function that returns the comment currently being recursed.
 *
 * @since BuddyPress 1.5.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return object|bool $current_comment The activity comment currently being
 *                                      displayed. False on failure.
 */
function bp_activity_current_comment() {
	global $activities_template;

	$current_comment = ! empty( $activities_template->activity->current_comment )
		? $activities_template->activity->current_comment
		: false;

	/**
	 * Filters the current comment being recursed.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param object|bool $current_comment The activity comment currently being displayed. False on failure.
	 */
	return apply_filters( 'bp_activity_current_comment', $current_comment );
}


/**
 * Output the ID of the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_comment_id() {
	echo bp_get_activity_comment_id();
}

/**
 * Return the ID of the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int|bool $comment_id The ID of the activity comment currently
 *                              being displayed, false if none is found.
 */
function bp_get_activity_comment_id() {
	global $activities_template;

	$comment_id = isset( $activities_template->activity->current_comment->id ) ? $activities_template->activity->current_comment->id : false;

	/**
	 * Filters the ID of the activity comment currently being displayed.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int|bool $comment_id ID for the comment currently being displayed.
	 */
	return apply_filters( 'bp_activity_comment_id', $comment_id );
}

/**
 * Output the ID of the author of the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_comment_user_id() {
	echo bp_get_activity_comment_user_id();
}

/**
 * Return the ID of the author of the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return int|bool $user_id The user_id of the author of the displayed
 *                           activity comment. False on failure.
 */
function bp_get_activity_comment_user_id() {
	global $activities_template;

	$user_id = isset( $activities_template->activity->current_comment->user_id ) ? $activities_template->activity->current_comment->user_id : false;

	/**
	 * Filters the ID of the author of the activity comment currently being displayed.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int|bool $user_id ID for the author of the comment currently being displayed.
	 */
	return apply_filters( 'bp_activity_comment_user_id', $user_id );
}

/**
 * Output the author link for the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_comment_user_link() {
	echo bp_get_activity_comment_user_link();
}

/**
 * Return the author link for the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string $user_link The URL of the activity comment author's profile.
 */
function bp_get_activity_comment_user_link() {
	$user_link = bp_core_get_user_domain( bp_get_activity_comment_user_id() );

	/**
	 * Filters the author link for the activity comment currently being displayed.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $user_link Link for the author of the activity comment currently being displayed.
	 */
	return apply_filters( 'bp_activity_comment_user_link', $user_link );
}

/**
 * Output the author name for the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_comment_name() {
	echo bp_get_activity_comment_name();
}

/**
 * Return the author name for the activity comment currently being displayed.
 *
 * The use of the 'bp_acomment_name' filter is deprecated. Please use
 * 'bp_activity_comment_name'.
 *
 * @since BuddyPress 1.5.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string $name The full name of the activity comment author.
 */
function bp_get_activity_comment_name() {
	global $activities_template;

	if ( isset( $activities_template->activity->current_comment->user_fullname ) ) {

		$name = apply_filters( 'bp_acomment_name', $activities_template->activity->current_comment->user_fullname, $activities_template->activity->current_comment );  // Backward compatibility.
	} else {
		$name = $activities_template->activity->current_comment->display_name;
	}

	/**
	 * Filters the name of the author for the activity comment.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $name Name to be displayed with the activity comment.
	 */
	return apply_filters( 'bp_activity_comment_name', $name );
}

/**
 * Output the formatted date_recorded of the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_comment_date_recorded() {
	echo bp_get_activity_comment_date_recorded();
}

/**
 * Return the formatted date_recorded for the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string|bool $date_recorded Time since the activity was recorded,
 *                                    in the form "%s ago". False on failure.
 */
function bp_get_activity_comment_date_recorded() {

	/**
	 * Filters the recorded date of the activity comment currently being displayed.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @since BuddyBoss 2.5.80
	 * Updated param.
	 *
	 * @param string|bool string|bool $date_recorded Time since the activity was recorded,
	 *                    in the form "%s ago". False on failure.
	 */
	return apply_filters( 'bp_activity_comment_date_recorded', bp_get_activity_comment_date_recorded_raw() );
}

/**
 * Output the date_recorded of the activity comment currently being displayed.
 *
 * @since BuddyPress 2.3.0
 */
function bp_activity_comment_date_recorded_raw() {
	echo bp_get_activity_comment_date_recorded_raw();
}

/**
 * Return the date_recorded for the activity comment currently being displayed.
 *
 * @since BuddyPress 2.3.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string|bool $date_recorded Time since the activity was recorded,
 *                                    in the form "%s ago". False on failure.
 */
function bp_get_activity_comment_date_recorded_raw() {
	global $activities_template;

	/**
	 * Filters the raw recorded date of the activity comment currently being displayed.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param string|bool Raw date for the activity comment currently being displayed.
	 */
	return apply_filters( 'bp_activity_comment_date_recorded', $activities_template->activity->current_comment->date_recorded );
}

/**
 * Output the 'delete' URL for the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_comment_delete_link() {
	echo bp_get_activity_comment_delete_link();
}

/**
 * Gets the 'delete' URL for the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 *
 * @return string $link The nonced URL for deleting the current
 *                      activity comment.
 */
function bp_get_activity_comment_delete_link() {
	$link = wp_nonce_url( trailingslashit( bp_get_activity_directory_permalink() . 'delete/' . bp_get_activity_comment_id() ) . '?cid=' . bp_get_activity_comment_id(), 'bp_activity_delete_link' );

	/**
	 * Filters the link used for deleting the activity comment currently being displayed.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $link Link to use for deleting the currently displayed activity comment.
	 */
	return apply_filters( 'bp_activity_comment_delete_link', $link );
}

/**
 * Output the content of the activity comment currently being displayed.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_comment_content() {
	echo bp_get_activity_comment_content();
}

/**
 * Return the content of the activity comment currently being displayed.
 *
 * The content is run through two filters. 'bp_get_activity_content'
 * will apply all filters applied to activity items in general. Use
 * 'bp_activity_comment_content' to modify the content of activity
 * comments only.
 *
 * @since BuddyPress 1.5.0
 * @since BuddyBoss 2.4.40 Added $activity_comment_id parameter to get activity comment content.
 *
 * @param int $activity_comment_id Activity comment ID.
 *
 * @return string $content The content of the current activity comment.
 * @global object $activities_template {@link BP_Activity_Template}
 */
function bp_get_activity_comment_content( $activity_comment_id = 0 ) {
	global $activities_template;

	$activity_comment = new stdClass();
	if ( ! empty( $activity_comment_id ) ) {
		$activity_comment = new BP_Activity_Activity( $activity_comment_id );
	} elseif ( ! empty( $activities_template->activity->current_comment ) ) {
		$activity_comment = $activities_template->activity->current_comment;
	}

	// check activity comment exists.
	if ( empty( $activity_comment->id ) ) {
		return '';
	}

	// scrape off activity content if it contain empty characters only
	if ( in_array( $activity_comment->content, array( '&nbsp;', '&#8203;' ) ) ) {
		$activity_comment->content = '';
	}

	/** This filter is documented in bp-activity/bp-activity-template.php */
	$content = apply_filters_ref_array( 'bp_get_activity_content', array( $activity_comment->content, &$activity_comment ) );

	/**
	 * Filters the content of the current activity comment.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 3.0.0 Added $context parameter to disambiguate from bp_get_activity_comment_content().
	 *
	 * @param string $content The content of the current activity comment.
	 * @param string $context This filter's context ("get").
	 */
	return apply_filters( 'bp_activity_comment_content', $content, 'get' );
}

/**
 * Output the activity comment count.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_comment_count() {
	echo bp_activity_get_comment_count();
}

/**
 * Return the comment count of an activity item.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @param array|null $deprecated Deprecated.
 * @return int $count The activity comment count.
 */
function bp_activity_get_comment_count( $deprecated = null ) {
	global $activities_template;

	// Deprecated notice about $args.
	if ( ! empty( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '1.2', sprintf( __( '%1$s no longer accepts arguments. See the inline documentation at %2$s for more details.', 'buddyboss' ), __FUNCTION__, __FILE__ ) );
	}

	$count = $activities_template->activity->all_child_count ?? 0;

	/**
	 * Filters the activity comment count.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $count The activity comment count.
	 */
	return apply_filters( 'bp_activity_get_comment_count', (int) $count );
}

/**
 * Return the total number of comments to the current comment.
 *
 * This function recursively adds the total number of comments each
 * activity child has, and returns them.
 *
 * @since BuddyPress 1.2.0
 *
 * @param object $comment Activity comment object.
 * @param int    $count The current iteration count.
 * @return int $count The activity comment count.
 */
function bp_activity_recurse_comment_count( $comment, $count = 0 ) {

	// Copy the count.
	$new_count = $count;

	// Loop through children and recursively count comments.
	if ( ! empty( $comment->children ) ) {
		foreach ( (array) $comment->children as $comment ) {
			++$new_count;
			$new_count = bp_activity_recurse_comment_count( $comment, $new_count );
		}
	}

	/**
	 * Filters the total number of comments for the current comment.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param int    $new_count New total count for the current comment.
	 * @param object $comment   Activity comment object.
	 * @param int    $count     Current iteration count for the current comment.
	 */
	return apply_filters( 'bp_activity_recurse_comment_count', $new_count, $comment, $count );
}

/**
 * Output the depth of the current activity comment.
 *
 * @since BuddyPress 2.0.0
 * @since BuddyPress 2.8.0 Added $comment as a parameter.
 *
 * @param object|int $comment Object of the activity comment or activity comment ID. Usually unnecessary
 *                            when used in activity comment loop.
 */
function bp_activity_comment_depth( $comment = 0 ) {
	echo bp_activity_get_comment_depth( $comment );
}

/**
 * Return the current activity comment depth.
 *
 * @since BuddyPress 2.0.0
 * @since BuddyPress 2.8.0 Added $comment as a parameter.
 *
 * @param  object|int $comment Object of the activity comment or activity comment ID. Usually unnecessary
 *                             when used in activity comment loop.
 * @return int
 */
function bp_activity_get_comment_depth( $comment = 0 ) {
	$depth = 0;

	// Activity comment loop takes precedence.
	if ( isset( $GLOBALS['activities_template']->activity->current_comment->depth ) ) {
		$depth = $GLOBALS['activities_template']->activity->current_comment->depth;

		// Get depth for activity comment manually.
	} elseif ( ! empty( $comment ) ) {
		// We passed an activity ID, so fetch the activity object.
		if ( is_int( $comment ) ) {
			$comment = new BP_Activity_Activity( $comment );
		}

		// Recurse through activity tree to find the depth.
		if ( is_object( $comment ) && isset( $comment->type ) && 'activity_comment' === $comment->type ) {
			// Fetch the entire root comment tree... ugh.
			$comments = BP_Activity_Activity::get_activity_comments( $comment->item_id, 1, constant( 'PHP_INT_MAX' ) );

			// Recursively find our comment object from the comment tree.
			$iterator  = new RecursiveArrayIterator( $comments );
			$recursive = new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
			foreach ( $recursive as $cid => $cobj ) {
				// Skip items that are not a comment object.
				if ( ! is_numeric( $cid ) || ! is_object( $cobj ) ) {
					continue;
				}

				// We found the activity comment! Set the depth.
				if ( $cid === $comment->id && isset( $cobj->depth ) ) {
					$depth = $cobj->depth;
					break;
				}
			}
		}
	}

	/**
	 * Filters the comment depth of the current activity comment.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param int $depth Depth for the current activity comment.
	 */
	return apply_filters( 'bp_activity_get_comment_depth', $depth );
}

/**
 * Output the activity comment link.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_comment_link() {
	echo bp_get_activity_comment_link();
}

/**
 * Return the activity comment link.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity comment link.
 */
function bp_get_activity_comment_link() {
	global $activities_template;

	/**
	 * Filters the comment link for the current activity comment.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Constructed URL parameters with activity IDs.
	 */
	return apply_filters( 'bp_get_activity_comment_link', '?ac=' . $activities_template->activity->id . '/#ac-form-' . $activities_template->activity->id );
}

/**
 * Output the activity comment form no JavaScript display CSS.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_comment_form_nojs_display() {
	echo bp_get_activity_comment_form_nojs_display();
}

/**
 * Return the activity comment form no JavaScript display CSS.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string|false The activity comment form no JavaScript
 *                      display CSS. False on failure.
 */
function bp_get_activity_comment_form_nojs_display() {
	global $activities_template;

	if ( isset( $_GET['ac'] ) && ( $_GET['ac'] === ( $activities_template->activity->id . '/' ) ) ) {
		return 'style="display: block"';
	}

	return false;
}

/**
 * Output the activity comment form action.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_comment_form_action() {
	echo bp_get_activity_comment_form_action();
}

/**
 * Return the activity comment form action.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string The activity comment form action.
 */
function bp_get_activity_comment_form_action() {

	/**
	 * Filters the activity comment form action URL.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value URL to use in the comment form's action attribute.
	 */
	return apply_filters( 'bp_get_activity_comment_form_action', home_url( bp_get_activity_root_slug() . '/reply/' ) );
}

/**
 * Output the activity permalink ID.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_permalink_id() {
	echo bp_get_activity_permalink_id();
}

/**
 * Return the activity permalink ID.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string The activity permalink ID.
 */
function bp_get_activity_permalink_id() {

	/**
	 * Filters the activity action permalink ID.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Current action for the activity item.
	 */
	return apply_filters( 'bp_get_activity_permalink_id', bp_current_action() );
}

/**
 * Output the activity thread permalink.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_thread_permalink() {
	echo esc_url( bp_get_activity_thread_permalink() );
}

/**
 * Return the activity thread permalink.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string $link The activity thread permalink.
 */
function bp_get_activity_thread_permalink() {
	global $activities_template;

	$link = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );

	/**
	 * Filters the activity thread permalink.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $link The activity thread permalink.
	 */
	return apply_filters( 'bp_get_activity_thread_permalink', $link );
}

/**
 * Output the activity comment permalink.
 *
 * @since BuddyPress 1.8.0
 */
function bp_activity_comment_permalink() {
	echo esc_url( bp_get_activity_comment_permalink() );
}
/**
 * Return the activity comment permalink.
 *
 * @since BuddyPress 1.8.0
 *
 * @return string $link The activity comment permalink.
 */
function bp_get_activity_comment_permalink() {
	global $activities_template;

	$link = bp_activity_get_permalink( $activities_template->activity->id, $activities_template->activity );

	// Used for filter below.
	$comment_id = isset( $activities_template->activity->current_comment->id )
		? $activities_template->activity->current_comment->id
		: 0;

	/**
	 * Filters the activity comment permalink.
	 *
	 * @since BuddyPress 1.8.0
	 *
	 * @param string $link       Activity comment permalink.
	 * @param int    $comment_id ID for the current activity comment.
	 */
	return apply_filters( 'bp_get_activity_comment_permalink', $link, $comment_id );
}

/**
 * Output the activity favorite link.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_favorite_link() {
	echo bp_get_activity_favorite_link();
}

/**
 * Return the activity favorite link.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $activity_id The activity ID.
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity favorite link.
 */
function bp_get_activity_favorite_link( $activity_id = 0 ) {

	if ( empty( $activity_id ) ) {
		global $activities_template;

		$activity_id = $activities_template->activity->id;
	}

	/**
	 * Filters the activity favorite link.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Constructed link for favoriting the activity comment.
	 */
	return apply_filters( 'bp_get_activity_favorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/favorite/' . $activity_id . '/' ), 'mark_favorite' ) );
}

/**
 * Output the activity unfavorite link.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_unfavorite_link() {
	echo bp_get_activity_unfavorite_link();
}

/**
 * Return the activity unfavorite link.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $activity_id The activity ID.
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity unfavorite link.
 */
function bp_get_activity_unfavorite_link( $activity_id = 0 ) {

	if ( empty( $activity_id ) ) {
		global $activities_template;

		$activity_id = $activities_template->activity->id;
	}

	/**
	 * Filters the activity unfavorite link.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value Constructed link for unfavoriting the activity comment.
	 */
	return apply_filters( 'bp_get_activity_unfavorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/unfavorite/' . $activity_id . '/' ), 'unmark_favorite' ) );
}

/**
 * Output the activity CSS class.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_css_class() {
	echo bp_get_activity_css_class();
}

/**
 * Return the current activity item's CSS class.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity item's CSS class.
 */
function bp_get_activity_css_class() {
	global $activities_template;

	/**
	 * Filters the available mini activity actions available as CSS classes.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $value Array of classes used to determine classes applied to HTML element.
	 */
	$mini_activity_actions = apply_filters(
		'bp_activity_mini_activity_types',
		array(
			'friendship_accepted',
			'friendship_created',
			'new_blog',
			'joined_group',
			'created_group',
			'new_member',
		)
	);

	$class = ' activity-item';

	if ( in_array( $activities_template->activity->type, (array) $mini_activity_actions ) || empty( $activities_template->activity->content ) ) {
		$class .= ' mini';
	}

	if ( bp_activity_get_comment_count() && bp_activity_can_comment() ) {
		$class .= ' has-comments';
	}

	if ( bb_is_close_activity_comments_enabled() && bb_is_activity_comments_closed( $activities_template->activity->id ) ) {
		$class .= ' bb-closed-comments';
	}

	$activity_metas    = bb_activity_get_metadata( bp_get_activity_id() );
	$link_embed        = $activity_metas['_link_embed'][0] ?? '';
	$link_preview_data = ! empty( $activity_metas['_link_preview_data'][0] ) ? maybe_unserialize( $activity_metas['_link_preview_data'][0] ) : array();

	if (
		! empty( $link_embed ) ||
		! empty( $link_preview_data )
	) {
		$class .= ' wp-link-embed';
	}

	if ( ! empty( $activities_template->pinned_id ) && bp_get_activity_id() === (int) $activities_template->pinned_id ) {
		$class .= ' bb-pinned';
	}

	if ( bb_user_has_mute_notification( bp_get_activity_id(), bp_loggedin_user_id() ) ) {
		$class .= ' bb-muted';
	}

	if ( 'groups' === $activities_template->activity->component ) {
		$class .= ' group-' . $activities_template->activity->item_id;
	}

	/**
	 * Filters the determined classes to add to the HTML element.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value Classes to be added to the HTML element.
	 */
	return apply_filters( 'bp_get_activity_css_class', $activities_template->activity->component . ' ' . $activities_template->activity->type . $class );
}

/**
 * Output the activity comment CSS class.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_activity_comment_css_class() {
	echo bp_get_activity_comment_css_class();
}

/**
 * Return the current activity comment's CSS class.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string The activity comment's CSS class.
 */
function bp_get_activity_comment_css_class() {

	$class = ' comment-item activity-comment';

	// Fall back on current comment in activity loop.
	$comment_depth = bp_activity_get_comment_depth();

	// Threading is turned on, so check the depth.
	if ( 'blogs' === bp_get_activity_object_name() ) {
		if ( get_option( 'thread_comments' ) ) {
			$class .= ( $comment_depth > get_option( 'thread_comments_depth' ) ) ? ' detached-comment-item' : '';
		}
	} elseif ( bb_is_activity_comment_threading_enabled() ) {
		$class .= ( $comment_depth > bb_get_activity_comment_threading_depth() ) ? ' detached-comment-item' : '';
	}

	// Has children's.
	$comments_count = BP_Activity_Activity::bb_get_all_activity_comment_children_count(
		array(
			'spam'     => 'ham_only',
			'activity' => bp_activity_current_comment(),
		)
	);
	if ( ! empty( $comments_count['all_child_count'] ) > 0 ) {
		$class .= ' has-child-comments';
	}

	return apply_filters( 'bp_get_activity_comment_css_class', $class );
}

/**
 * Output the activity delete link.
 *
 * @since BuddyPress 1.1.0
 */
function bp_activity_delete_link() {
	echo bp_get_activity_delete_link();
}

/**
 * Return the activity delete link.
 *
 * @since BuddyPress 1.1.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string $link Activity delete link. Contains $redirect_to arg
 *                      if on single activity page.
 */
function bp_get_activity_delete_link() {

	$url   = bp_get_activity_delete_url();
	$class = 'delete-activity';

	// Determine if we're on a single activity page, and customize accordingly.
	if ( bp_is_activity_component() && is_numeric( bp_current_action() ) ) {
		$class = 'delete-activity-single';
	}

	$link = '<a href="' . esc_url( $url ) . '" class="button item-button bp-secondary-action ' . $class . ' confirm" rel="nofollow">' . __( 'Delete', 'buddyboss' ) . '</a>';

	/**
	 * Filters the activity delete link.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $link Activity delete HTML link.
	 */
	return apply_filters( 'bp_get_activity_delete_link', $link );
}

/**
 * Output the URL to delete a single activity feed item.
 *
 * @since BuddyPress 2.1.0
 */
function bp_activity_delete_url() {
	echo esc_url( bp_get_activity_delete_url() );
}

/**
 * Return the URL to delete a single activity item.
 *
 * @since BuddyPress 2.1.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string $link Activity delete link. Contains $redirect_to arg
 *                      if on single activity page.
 */
function bp_get_activity_delete_url() {
	global $activities_template;

	$url = trailingslashit( bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/delete/' . $activities_template->activity->id );

	// Determine if we're on a single activity page, and customize accordingly.
	if ( bp_is_activity_component() && is_numeric( bp_current_action() ) ) {
		$url = add_query_arg( array( 'redirect_to' => wp_get_referer() ), $url );
	}

	$url = wp_nonce_url( $url, 'bp_activity_delete_link' );

	/**
	 * Filters the activity delete URL.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $url Activity delete URL.
	 */
	return apply_filters( 'bp_get_activity_delete_url', $url );
}

/**
 * Output the activity latest update link.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_get_activity_latest_update() for description of parameters.
 *
 * @param int $user_id See {@link bp_get_activity_latest_update()} for description.
 */
function bp_activity_latest_update( $user_id = 0 ) {
	echo bp_get_activity_latest_update( $user_id );
}

/**
 * Return the activity latest update link.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id If empty, will fall back on displayed user.
 * @return string|bool $latest_update The activity latest update link.
 *                                    False on failure.
 */
function bp_get_activity_latest_update( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	if ( bp_is_user_inactive( $user_id ) ) {
		return false;
	}

	if ( ! $update = bp_get_user_meta( $user_id, 'bp_latest_update', true ) ) {
		return false;
	}

	/**
	 * Filters the latest update excerpt.
	 *
	 * @since BuddyPress 1.2.10
	 * @since BuddyPress 2.6.0  Added the `$user_id` parameter.
	 *
	 * @param string $value   The excerpt for the latest update.
	 * @param int    $user_id ID of the queried user.
	 */
	$latest_update = apply_filters( 'bp_get_activity_latest_update_excerpt', trim( strip_tags( bp_create_excerpt( $update['content'], bp_activity_get_excerpt_length() ) ) ), $user_id );

	$latest_update = sprintf(
		'%s <a href="%s">%s</a>',
		$latest_update,
		esc_url_raw( bp_activity_get_permalink( $update['id'] ) ),
		esc_attr__( 'View', 'buddyboss' )
	);

	/**
	 * Filters the latest update excerpt with view link appended to the end.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$user_id` parameter.
	 *
	 * @param string $latest_update The latest update with "view" link appended to it.
	 * @param int    $user_id       ID of the queried user.
	 */
	return apply_filters( 'bp_get_activity_latest_update', $latest_update, $user_id );
}

/**
 * Output the activity filter links.
 *
 * @since BuddyPress 1.1.0
 *
 * @see bp_get_activity_filter_links() for description of parameters.
 *
 * @param array|bool $args See {@link bp_get_activity_filter_links()} for description.
 */
function bp_activity_filter_links( $args = false ) {
	echo bp_get_activity_filter_links( $args );
}

/**
 * Return the activity filter links.
 *
 * @since BuddyPress 1.1.0
 *
 * @param array|bool $args {
 *     @type string $style The type of markup to use for the links.
 *                         'list', 'paragraph', or 'span'. Default: 'list'.
 * }
 * @return string|bool $component_links The activity filter links.
 *         False on failure.
 */
function bp_get_activity_filter_links( $args = false ) {

	$r = bp_parse_args(
		$args,
		array(
			'style' => 'list',
		)
	);

	// Define local variable.
	$component_links = array();

	// Fetch the names of components that have activity recorded in the DB.
	$components = BP_Activity_Activity::get_recorded_components();

	if ( empty( $components ) ) {
		return false;
	}

	foreach ( (array) $components as $component ) {

		// Skip the activity comment filter.
		if ( 'activity' === $component ) {
			continue;
		}

		if ( isset( $_GET['afilter'] ) && $component === $_GET['afilter'] ) {
			$selected = ' class="selected"';
		} else {
			$selected = '';
		}

		$component = esc_attr( $component );

		switch ( $r['style'] ) {
			case 'list':
				$tag    = 'li';
				$before = '<li id="afilter-' . $component . '"' . $selected . '>';
				$after  = '</li>';
				break;
			case 'paragraph':
				$tag    = 'p';
				$before = '<p id="afilter-' . $component . '"' . $selected . '>';
				$after  = '</p>';
				break;
			case 'span':
				$tag    = 'span';
				$before = '<span id="afilter-' . $component . '"' . $selected . '>';
				$after  = '</span>';
				break;
		}

		$link = add_query_arg( 'afilter', $component );
		$link = remove_query_arg( 'acpage', $link );

		/**
		 * Filters the activity filter link URL for the current activity component.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $link      The URL for the current component.
		 * @param string $component The current component getting links constructed for.
		 */
		$link = apply_filters( 'bp_get_activity_filter_link_href', $link, $component );

		$component_links[] = $before . '<a href="' . esc_url( $link ) . '">' . ucwords( $component ) . '</a>' . $after;
	}

	$link = remove_query_arg( 'afilter', $link );

	if ( isset( $_GET['afilter'] ) ) {
		$component_links[] = '<' . $tag . ' id="afilter-clear"><a href="' . esc_url( $link ) . '">' . __( 'Clear Filter', 'buddyboss' ) . '</a></' . $tag . '>';
	}

	/**
	 * Filters all of the constructed filter links.
	 *
	 * @since BuddyPress 1.1.0
	 * @since BuddyPress 2.6.0 Added the `$r` parameter.
	 *
	 * @param string $value All of the links to be displayed to the user.
	 * @param array  $r     Array of parsed arguments.
	 */
	return apply_filters( 'bp_get_activity_filter_links', implode( "\n", $component_links ), $r );
}

/**
 * Determine if a comment can be made on an activity item.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return bool $can_comment True if item can receive comments.
 */
function bp_activity_can_comment() {
	global $activities_template;
	$bp = buddypress();

	// Determine ability to comment based on activity type name.
	$activity_type = bp_get_activity_type();

	// Get the 'comment-reply' support for the current activity type.
	$can_comment = bp_activity_type_supports( $activity_type, 'comment-reply' );

	// Neutralize activity_comment.
	if ( 'activity_comment' === $activity_type ) {
		$can_comment = false;
	}

	/**
	 * Filters whether a comment can be made on an activity item.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.5.0 Use $activity_type instead of $activity_name for the second parameter.
	 *
	 * @param bool   $can_comment     Status on if activity can be commented on.
	 * @param string $activity_type   Current activity type being checked on.
	 */
	return apply_filters( 'bp_activity_can_comment', $can_comment, $activity_type );
}

/**
 * Determine whether a comment can be made on an activity reply item.
 *
 * @since BuddyPress 1.5.0
 *
 * @param  bool|object $comment     Activity comment.
 * @return bool        $can_comment True if comment can receive comments,
 *                                  otherwise false.
 */
function bp_activity_can_comment_reply( $comment = false ) {

	// Assume activity can be commented on.
	$can_comment = true;

	// Check that comment exists.
	if ( empty( $comment ) ) {
		$comment = bp_activity_current_comment();
	}

	/**
	 * Filters whether a comment can be made on an activity reply item.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool   $can_comment Status on if activity reply can be commented on.
	 * @param object $comment     Current comment object being checked on.
	 */
	return (bool) apply_filters( 'bp_activity_can_comment_reply', $can_comment, $comment );
}

/**
 * Determine whether favorites are allowed.
 *
 * Defaults to true, but can be modified by plugins.
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool True if comment can receive comments.
 */
function bp_activity_can_favorite() {

	$retval = is_user_logged_in() && bb_is_reaction_activity_posts_enabled();

	/**
	 * Filters whether users can favorite activity items.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param bool $value Whether favoriting is enabled.
	 */
	return apply_filters( 'bp_activity_can_favorite', $retval );
}

/**
 * Output the total favorite count for a specified user.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyBoss 2.5.20 Added `$activity_type` property to indicate whether the current ID is activity or comment.
 *
 * @param int    $user_id       See {@link bp_get_total_favorite_count_for_user()}.
 * @param string $activity_type Activity type.
 *
 * @see   bp_get_total_favorite_count_for_user() for description of parameters.
 */
function bp_total_favorite_count_for_user( $user_id = 0, $activity_type = 'activity' ) {
	echo bp_get_total_favorite_count_for_user( $user_id, $activity_type );
}

/**
 * Return the total favorite count for a specified user.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyBoss 2.5.20 Added `$activity_type` property to indicate whether the current ID is activity or comment.
 *
 * @param int    $user_id       ID of user being queried. Default: displayed user ID.
 * @param string $activity_type Activity type.
 *
 * @return int The total favorite count for the specified user.
 */
function bp_get_total_favorite_count_for_user( $user_id = 0, $activity_type = 'activity' ) {
	$retval = false;

	if ( bp_activity_can_favorite() ) {
		// Default to displayed user if none is passed.
		$user_id = empty( $user_id )
			? bp_displayed_user_id()
			: $user_id;

		// Get user meta if user ID exists.
		if ( ! empty( $user_id ) ) {
			$retval = bp_activity_total_favorites_for_user( $user_id, $activity_type );
		}
	}

	/**
	 * Filters the total favorite count for a user.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$user_id` parameter.
	 * @since BuddyBoss 2.5.20 Added `$activity_type` property to indicate whether the current ID is activity or comment.
	 *
	 * @param int|bool $retval        Total favorite count for a user. False on no favorites.
	 * @param int      $user_id       ID of the queried user.
	 * @param string   $activity_type Activity type.
	 */
	return apply_filters( 'bp_get_total_favorite_count_for_user', $retval, $user_id, $activity_type );
}

/**
 * Output the total mention count for a specified user.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_get_total_mention_count_for_user() for description of parameters.
 *
 * @param int $user_id See {@link bp_get_total_mention_count_for_user()}.
 */
function bp_total_mention_count_for_user( $user_id = 0 ) {
	echo bp_get_total_mention_count_for_user( $user_id );
}

/**
 * Return the total mention count for a specified user.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id ID of user being queried. Default: displayed user ID.
 * @return int The total mention count for the specified user.
 */
function bp_get_total_mention_count_for_user( $user_id = 0 ) {

	// Default to displayed user if none is passed.
	$user_id = empty( $user_id )
		? bp_displayed_user_id()
		: $user_id;

	// Get user meta if user ID exists.
	$retval = ! empty( $user_id )
		? bp_get_user_meta( $user_id, 'bp_new_mention_count', true )
		: false;

	/**
	 * Filters the total mention count for a user.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$user_id` parameter.
	 *
	 * @param int|bool $retval  Total mention count for a user. False on no mentions.
	 * @param int      $user_id ID of the queried user.
	 */
	return apply_filters( 'bp_get_total_mention_count_for_user', $retval, $user_id );
}

/**
 * Output the public message link for displayed user.
 *
 * @since BuddyPress 1.2.0
 */
function bp_send_public_message_link() {
	echo esc_url( bp_get_send_public_message_link() );
}

/**
 * Return the public message link for the displayed user.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string The public message link for the displayed user.
 */
function bp_get_send_public_message_link() {

	// No link if not logged in, not looking at someone else's profile.
	if ( ! is_user_logged_in() || ! bp_is_user() || bp_is_my_profile() ) {
		$retval = '';
	} else {
		$args   = array( 'r' => bp_get_displayed_user_mentionname() );
		$url    = add_query_arg( $args, bp_get_activity_directory_permalink() );
		$retval = wp_nonce_url( $url );
	}

	/**
	 * Filters the public message link for the displayed user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $retval The URL for the public message link.
	 */
	return apply_filters( 'bp_get_send_public_message_link', $retval );
}

/**
 * Recurse through all activity comments and return the activity comment IDs.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $activity Array of activities generated from {@link bp_activity_get()}.
 * @param array $activity_ids Used for recursion purposes in this function.
 * @return array
 */
function bp_activity_recurse_comments_activity_ids( $activity = array(), $activity_ids = array() ) {
	if ( is_array( $activity ) && ! empty( $activity['activities'] ) ) {
		$activity = $activity['activities'][0];
	}

	if ( ! empty( $activity->children ) ) {
		foreach ( $activity->children as $child ) {
			$activity_ids[] = $child->id;

			if ( ! empty( $child->children ) ) {
				$activity_ids = bp_activity_recurse_comments_activity_ids( $child, $activity_ids );
			}
		}
	}

	return $activity_ids;
}

/**
 * Output the mentioned user display name.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_get_mentioned_user_display_name() for description of parameters.
 *
 * @param int|string|bool $user_id_or_username See {@link bp_get_mentioned_user_display_name()}.
 */
function bp_mentioned_user_display_name( $user_id_or_username = false ) {
	echo bp_get_mentioned_user_display_name( $user_id_or_username );
}

/**
 * Returns the mentioned user display name.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int|string|bool $user_id_or_username User ID or username.
 * @return string The mentioned user's display name.
 */
function bp_get_mentioned_user_display_name( $user_id_or_username = false ) {

	// Get user display name.
	$name = bp_core_get_user_displayname( $user_id_or_username );

	// If user somehow has no name, return this really lame string.
	if ( empty( $name ) ) {
		$name = __( 'a user', 'buddyboss' );
	}

	/**
	 * Filters the mentioned user display name.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string     $name                Display name for the mentioned user.
	 * @param int|string $user_id_or_username User ID or username use for query.
	 */
	return apply_filters( 'bp_get_mentioned_user_display_name', $name, $user_id_or_username );
}

/**
 * Output the activity post form action.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_post_form_action() {
	echo bp_get_activity_post_form_action();
}

/**
 * Return the activity post form action.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string The activity post form action.
 */
function bp_get_activity_post_form_action() {

	/**
	 * Filters the action url used for the activity post form.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $value URL to be used for the activity post form.
	 */
	return apply_filters( 'bp_get_activity_post_form_action', home_url( bp_get_activity_root_slug() . '/post/' ) );
}

/**
 * Echo a list of linked avatars of users who have commented on the current activity item.
 *
 * Use this function to easily output activity comment authors' avatars.
 *
 * Avatars are wrapped in <li> elements, but you've got to provide your own
 * <ul> or <ol> wrapper markup.
 *
 * @since BuddyPress 1.7.0
 *
 * @see bp_core_fetch_avatar() for a description of arguments.
 *
 * @param array $args See {@link bp_core_fetch_avatar()}.
 */
function bp_activity_comments_user_avatars( $args = array() ) {

	$r = bp_parse_args(
		$args,
		array(
			'height' => false,
			'html'   => true,
			'type'   => 'thumb',
			'width'  => false,
		)
	);

	// Get the user IDs of everyone who has left a comment to the current activity item.
	$user_ids = bp_activity_get_comments_user_ids();
	$output   = array();
	$retval   = '';

	if ( ! empty( $user_ids ) ) {
		foreach ( (array) $user_ids as $user_id ) {

			// Skip an empty user ID.
			if ( empty( $user_id ) ) {
				continue;
			}

			// Get profile link for this user.
			$profile_link = bp_core_get_user_domain( $user_id );

			// Get avatar for this user.
			$image_html = bp_core_fetch_avatar(
				array(
					'item_id' => $user_id,
					'height'  => $r['height'],
					'html'    => $r['html'],
					'type'    => $r['type'],
					'width'   => $r['width'],
				)
			);

			// If user has link & avatar, add them to the output array.
			if ( ! empty( $profile_link ) && ! empty( $image_html ) ) {
				$output[] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $profile_link ), $image_html );
			}
		}

		// If output array is not empty, wrap everything in some list items.
		if ( ! empty( $output ) ) {
			$retval = '<li>' . implode( '</li><li>', $output ) . '</li>';
		}
	}

	/**
	 * Filters the list of linked avatars for users who have commented on the current activity item.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $retval HTML markup for the list of avatars.
	 * @param array  $r      Array of arguments used for each avatar.
	 * @param array  $output Array of each avatar found, before imploded into single string.
	 */
	echo apply_filters( 'bp_activity_comments_user_avatars', $retval, $r, $output );
}

/**
 * Return the IDs of every user who's left a comment on the current activity item.
 *
 * @since BuddyPress 1.7.0
 *
 * @return bool|array An array of IDs, or false if none are found.
 */
function bp_activity_get_comments_user_ids() {
	global $activities_template;

	$user_ids = ! empty( $activities_template->activity->children )
		? (array) bp_activity_recurse_comments_user_ids( $activities_template->activity->children )
		: array();

	/**
	 * Filters the list of user IDs for the current activity item.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $value Array of unique user IDs for the current activity item.
	 */
	return apply_filters( 'bp_activity_get_comments_user_ids', array_unique( $user_ids ) );
}

/**
 * Recurse through all activity comments and collect the IDs of the users who wrote them.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $comments Array of {@link BP_Activity_Activity} items.
 * @return array Array of user IDs.
 */
function bp_activity_recurse_comments_user_ids( array $comments = array() ) {

	// Default user ID's array.
	$user_ids = array();

	// Loop through comments and try to get user ID's.
	if ( ! empty( $comments ) ) {
		foreach ( $comments as $comment ) {

			// If a user is a spammer, their activity items will have been
			// automatically marked as spam. Skip these.
			if ( ! empty( $comment->is_spam ) ) {
				continue;
			}

			// Add user ID to array.
			$user_ids[] = $comment->user_id;

			// Check for commentception.
			if ( ! empty( $comment->children ) ) {
				$user_ids = array_merge( $user_ids, bp_activity_recurse_comments_user_ids( $comment->children ) );
			}
		}
	}

	/**
	 * Filters the list of user IDs for the current activity comment item.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param array $user_ids Array of user IDs for the current activity comment item.
	 * @param array $comments Array of comments being checked for user IDs.
	 */
	return apply_filters( 'bp_activity_recurse_comments_user_ids', $user_ids, $comments );
}

/**
 * Output the mentionname for the displayed user.
 *
 * @since BuddyPress 1.9.0
 */
function bp_displayed_user_mentionname() {
	echo bp_get_displayed_user_mentionname();
}

/**
 * Get the mentionname for the displayed user.
 *
 * @since BuddyPress 1.9.0
 *
 * @return string Mentionname for the displayed user, if available.
 */
function bp_get_displayed_user_mentionname() {

	/**
	 * Filters the mentionname for the displayed user.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param string $value The mentionanme for the displayed user.
	 */
	return apply_filters( 'bp_get_displayed_user_mentionname', bp_activity_get_user_mentionname( bp_displayed_user_id() ) );
}

/**
 * Echo a list of all registered activity types for use in dropdowns or checkbox lists.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string       $output Optional. Either 'select' or 'checkbox'. Default: 'select'.
 * @param array|string $args {
 *     Optional extra arguments.
 *     @type string       $checkbox_name When returning checkboxes, sets the 'name'
 *                                       attribute.
 *     @type array|string $selected      A list of types that should be checked/
 *                                       selected.
 * }
 */
function bp_activity_types_list( $output = 'select', $args = '' ) {

	$args = bp_parse_args(
		$args,
		array(
			'checkbox_name' => 'bp_activity_types',
			'selected'      => array(),
		)
	);

	$activities = bp_activity_get_types();
	natsort( $activities );

	// Loop through the activity types and output markup.
	foreach ( $activities as $type => $description ) {

		// See if we need to preselect the current type.
		$checked  = checked( true, in_array( $type, (array) $args['selected'] ), false );
		$selected = selected( true, in_array( $type, (array) $args['selected'] ), false );

		// Switch output based on the element.
		switch ( $output ) {
			case 'select':
				printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $type ), $selected, esc_html( $description ) );
				break;
			case 'checkbox':
				printf( '<label style="" for="%1$s[]">%2$s<input type="checkbox" id="%1$s[]" name="%1$s[]" value="%3$s" %4$s/></label>', esc_attr( $args['checkbox_name'] ), esc_html( $description ), esc_attr( $args['checkbox_name'] ), esc_attr( $args['checkbox_name'] ), esc_attr( $type ), $checked );
				break;
		}

		/**
		 * Fires at the end of the listing of activity types.
		 *
		 * This is a variable action hook. The actual hook to use will depend on the output type specified.
		 * Two default hooks are bp_activity_types_list_select and bp_activity_types_list_checkbox.
		 *
		 * @since BuddyPress 1.7.0
		 *
		 * @param array  $args        Array of arguments passed into function.
		 * @param string $type        Activity type being rendered in the output.
		 * @param string $description Description of the activity type being rendered.
		 */
		do_action( 'bp_activity_types_list_' . $output, $args, $type, $description );
	}

	// Backpat with BP-Default for dropdown boxes only.
	if ( 'select' === $output ) {
		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bp_activity_filter_options' );
	}
}


/* RSS Feed Template Tags ****************************************************/

/**
 * Output the sitewide activity feed link.
 *
 * @since BuddyPress 1.0.0
 */
function bp_sitewide_activity_feed_link() {
	echo bp_get_sitewide_activity_feed_link();
}

/**
 * Returns the sitewide activity feed link.
 *
 * @since BuddyPress 1.0.0
 *
 * @return string The sitewide activity feed link.
 */
function bp_get_sitewide_activity_feed_link() {

	/**
	 * Filters the sidewide activity feed link.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $value The feed link for sitewide activity.
	 */
	return apply_filters( 'bp_get_sitewide_activity_feed_link', bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/feed/' );
}

/**
 * Output the member activity feed link.
 *
 * @since BuddyPress 1.2.0
 */
function bp_member_activity_feed_link() {
	echo bp_get_member_activity_feed_link();
}

/**
 * Output the member activity feed link.
 *
 * @since BuddyPress 1.0.0
 * @deprecated 1.2.0
 *
 * @todo properly deprecate in favor of bp_member_activity_feed_link().
 */
function bp_activities_member_rss_link() {
	echo bp_get_member_activity_feed_link();
}

/**
 * Return the member activity feed link.
 *
 * @since BuddyPress 1.2.0
 *
 * @return string $link The member activity feed link.
 */
function bp_get_member_activity_feed_link() {

	// Single member activity feed link.
	if ( bp_is_profile_component() || bp_is_current_action( 'just-me' ) ) {
		$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/feed/';

		// Friend feed link.
	} elseif ( bp_is_active( 'friends' ) && bp_is_current_action( bp_get_friends_slug() ) ) {
		$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_friends_slug() . '/feed/';

		// Group feed link.
	} elseif ( bp_is_active( 'groups' ) && bp_is_current_action( bp_get_groups_slug() ) ) {
		$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() . '/feed/';

		// Favorites activity feed link.
	} elseif ( 'favorites' === bp_current_action() ) {
		$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/favorites/feed/';

		// Mentions activity feed link.
	} elseif ( ( 'mentions' === bp_current_action() ) && bp_activity_do_mentions() ) {
		$link = bp_displayed_user_domain() . bp_get_activity_slug() . '/mentions/feed/';

		// No feed link.
	} else {
		$link = '';
	}

	/**
	 * Filters the member activity feed link.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $link URL for the member activity feed.
	 */
	return apply_filters( 'bp_get_activities_member_rss_link', $link );
}

/**
 * Return the member activity feed link.
 *
 * @since BuddyPress 1.0.0
 * @deprecated 1.2.0
 *
 * @todo properly deprecate in favor of bp_get_member_activity_feed_link().
 *
 * @return string The member activity feed link.
 */
function bp_get_activities_member_rss_link() {
	return bp_get_member_activity_feed_link(); }


/** Template tags for RSS feed output ****************************************/

/**
 * Outputs the activity feed item guid.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_feed_item_guid() {
	echo bp_get_activity_feed_item_guid();
}

/**
 * Returns the activity feed item guid.
 *
 * @since BuddyPress 1.2.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity feed item guid.
 */
function bp_get_activity_feed_item_guid() {
	global $activities_template;

	/**
	 * Filters the activity feed item guid.
	 *
	 * @since BuddyPress 1.1.3
	 *
	 * @param string $value Calculated md5 value for the activity feed item.
	 */
	return apply_filters( 'bp_get_activity_feed_item_guid', md5( $activities_template->activity->date_recorded . '-' . $activities_template->activity->content ) );
}

/**
 * Output the activity feed item title.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_feed_item_title() {
	echo bp_get_activity_feed_item_title();
}

/**
 * Return the activity feed item title.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string $title The activity feed item title.
 */
function bp_get_activity_feed_item_title() {
	global $activities_template;

	if ( ! empty( $activities_template->activity->action ) ) {
		$content = $activities_template->activity->action;
	} else {
		$content = $activities_template->activity->content;
	}

	$content = explode( '<span', $content );
	$title   = strip_tags( ent2ncr( trim( convert_chars( $content[0] ) ) ) );

	if ( ':' === substr( $title, -1 ) ) {
		$title = substr( $title, 0, -1 );
	}

	if ( 'activity_update' === $activities_template->activity->type ) {
		$title .= ': ' . strip_tags( ent2ncr( trim( convert_chars( bp_create_excerpt( $activities_template->activity->content, 70, array( 'ending' => ' [&#133;]' ) ) ) ) ) );
	}

	/**
	 * Filters the activity feed item title.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $title The title for the activity feed item.
	 */
	return apply_filters( 'bp_get_activity_feed_item_title', $title );
}

/**
 * Output the activity feed item link.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_feed_item_link() {
	echo bp_get_activity_feed_item_link();
}

/**
 * Return the activity feed item link.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity feed item link.
 */
function bp_get_activity_feed_item_link() {
	global $activities_template;

	$retval = ! empty( $activities_template->activity->primary_link )
		? $activities_template->activity->primary_link
		: '';

	/**
	 * Filters the activity feed item link.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $retval The URL for the activity feed item.
	 */
	return apply_filters( 'bp_get_activity_feed_item_link', $retval );
}

/**
 * Output the activity feed item date.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_feed_item_date() {
	echo bp_get_activity_feed_item_date();
}

/**
 * Return the activity feed item date.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity feed item date.
 */
function bp_get_activity_feed_item_date() {
	global $activities_template;

	$retval = ! empty( $activities_template->activity->date_recorded )
		? $activities_template->activity->date_recorded
		: '';

	/**
	 * Filters the activity feed item date.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $retval The date for the activity feed item.
	 */
	return apply_filters( 'bp_get_activity_feed_item_date', $retval );
}

/**
 * Output the activity feed item description.
 *
 * @since BuddyPress 1.0.0
 */
function bp_activity_feed_item_description() {
	echo bp_get_activity_feed_item_description();
}

/**
 * Return the activity feed item description.
 *
 * @since BuddyPress 1.0.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity feed item description.
 */
function bp_get_activity_feed_item_description() {
	global $activities_template;

	// Get the content, if exists.
	$content = ! empty( $activities_template->activity->content )
		? $activities_template->activity->content
		: '';

	// Perform a few string conversions on the content, if it's not empty.
	if ( ! empty( $content ) ) {
		$content = ent2ncr( convert_chars( str_replace( '%s', '', $content ) ) );
	}

	/**
	 * Filters the activity feed item description.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string $content The description for the activity feed item.
	 */
	return apply_filters( 'bp_get_activity_feed_item_description', $content );
}

/**
 * Template tag so we can hook activity feed to <head>.
 *
 * @since BuddyPress 1.5.0
 */
function bp_activity_sitewide_feed() {
	?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ); ?> | <?php _e( 'Site Wide Activity RSS Feed', 'buddyboss' ); ?>" href="<?php bp_sitewide_activity_feed_link(); ?>" />

	<?php
}
add_action( 'bp_head', 'bp_activity_sitewide_feed' );

/**
 * Display available filters depending on the scope.
 *
 * @since BuddyPress 2.1.0
 *
 * @param string $context The current context. 'activity', 'member',
 *                        'member_groups', 'group'.
 */
function bp_activity_show_filters( $context = '' ) {
	echo bp_get_activity_show_filters( $context );
}

/**
 * Get available filters depending on the scope.
 *
 * @since BuddyPress 2.1.0
 *
 * @param string $context The current context. 'activity', 'member',
 *                        'member_groups', 'group'.
 *
 * @return string HTML for <option> values.
 */
function bp_get_activity_show_filters( $context = '' ) {
	$filters = array();
	$actions = bp_activity_get_actions_for_context( $context );
	foreach ( $actions as $action ) {
		// Connections activity collapses two filters into one.
		if ( in_array( $action['key'], array( 'friendship_accepted', 'friendship_created' ) ) ) {
			$action['key'] = 'friendship_accepted,friendship_created';
		}

		$filters[ $action['key'] ] = $action['label'];
	}

	/**
	 * Filters the options available in the activity filter dropdown.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array  $filters Array of filter options for the given context, in the following format: $option_value => $option_name.
	 * @param string $context Context for the filter. 'activity', 'member', 'member_groups', 'group'.
	 */
	$filters = apply_filters( 'bp_get_activity_show_filters_options', $filters, $context );

	// Build the options output.
	$output = '';

	if ( ! empty( $filters ) ) {
		foreach ( $filters as $value => $filter ) {
			$output .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $filter ) . '</option>' . "\n";
		}
	}

	/**
	 * Filters the HTML markup result for the activity filter dropdown.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param string $output  HTML output for the activity filter dropdown.
	 * @param array  $filters Array of filter options for the given context, in the following format: $option_value => $option_name.
	 * @param string $context Context for the filter. 'activity', 'member', 'member_groups', 'group'.
	 */
	return apply_filters( 'bp_get_activity_show_filters', $output, $filters, $context );
}

/**
 * Output the follow component slug.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_follow_slug() {
	echo bp_get_follow_slug();
}

/**
 * Return the follow component slug.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_get_follow_slug() {

	/**
	 * Filters the follow component slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Follow component slug.
	 */
	return apply_filters( 'bp_get_follow_slug', BP_FOLLOW_SLUG );
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
 *  'user_id' - The user ID you want to check for followers
 *
 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @return Mixed Comma-seperated string of user IDs on success. Integer zero on failure.
 */
function bp_get_follower_ids( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'user_id'  => bp_displayed_user_id(),
			'page'     => false,
			'per_page' => false,
		)
	);

	$ids = implode( ',', (array) bp_get_followers( $r ) );

	$ids = empty( $ids ) ? 0 : $ids;

	return apply_filters( 'bp_get_follower_ids', $ids, $r['user_id'], $r );
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
 *  'user_id' - The user ID you want to check for a following
 *
 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string
 * @global $bp The global BuddyPress settings variable created in bp_core_setup_globals()
 * @return Mixed Comma-seperated string of user IDs on success. Integer zero on failure.
 */
function bp_get_following_ids( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'user_id'  => bp_displayed_user_id(),
			'page'     => false,
			'per_page' => false,
		)
	);

	$ids = implode( ',', (array) bp_get_following( $r ) );

	$ids = empty( $ids ) ? 0 : $ids;

	return apply_filters( 'bp_get_following_ids', $ids, $r['user_id'], $r );
}

/**
 * Output the Follow button.
 *
 * @since BuddyBoss 1.0.0
 *
 * @see bp_add_follow_button() for information on arguments.
 *
 * @param int   $leader_id The user ID of the person we want to follow.
 * @param int   $follower_id The user ID initiating the follow request.
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
 * @param int   $leader_id The user ID of the person we want to follow.
 * @param int   $follower_id The user ID initiating the follow request.
 * @param array $button_args See BP_Button class for more information.
 *
 * @return mixed String of the button on success.  Boolean false on failure.
 * @uses bp_get_button() Renders a button using the BP Button API
 * @since BuddyBoss 1.0.0
 */
function bp_get_add_follow_button( $leader_id = false, $follower_id = false, $button_args = array() ) {

	if ( ! $leader_id || ! $follower_id ) {
		return false;
	}

	$is_following = bp_is_following(
		array(
			'leader_id'   => $leader_id,
			'follower_id' => $follower_id,
		)
	);

	$button_args = bp_parse_args( $button_args, get_class_vars( 'BP_Button' ) );

	if ( $is_following ) {
		$button = bp_parse_args(
			array(
				'id'                => 'member_follow',
				'component'         => 'members',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_class'     => 'follow-button following',
				'wrapper_id'        => 'follow-button-' . $leader_id,
				'link_href'         => wp_nonce_url( bp_loggedin_user_domain() . bp_get_follow_slug() . '/stop-following/' . $leader_id . '/', 'follow_unfollow' ),
				'link_text'         => esc_html__( 'Following', 'buddyboss' ),
				'link_id'           => 'follow-' . $leader_id,
				'link_rel'          => 'stop',
				'link_class'        => 'follow-button following stop bp-toggle-action-button',
				'data-balloon'      => esc_html__( 'Stop Following', 'buddyboss' ),
				'button_attr'       => array(
					'data-bp-nonce'        => wp_nonce_url( bp_loggedin_user_domain() . bp_get_follow_slug() . '/stop-following/' . $leader_id . '/', 'follow_unfollow' ),
					'hover_type'           => $button_args['button_attr']['hover_type'] ?? false,
					'data-title'           => esc_html__( 'Unfollow', 'buddyboss' ),
					'data-title-displayed' => esc_html__( 'Following', 'buddyboss' ),
				),
			),
			$button_args
		);
	} else {
		$button = bp_parse_args(
			array(
				'id'                => 'member_follow',
				'component'         => 'members',
				'must_be_logged_in' => true,
				'block_self'        => true,
				'wrapper_class'     => 'follow-button not_following',
				'wrapper_id'        => 'follow-button-' . $leader_id,
				'link_href'         => wp_nonce_url( bp_loggedin_user_domain() . bp_get_follow_slug() . '/start-following/' . $leader_id . '/', 'follow_follow' ),
				'link_text'         => esc_html__( 'Follow', 'buddyboss' ),
				'link_id'           => 'follow-' . $leader_id,
				'link_rel'          => 'start',
				'link_class'        => 'follow-button not_following start',
				'button_attr'       => array(
					'data-bp-nonce'        => wp_nonce_url( bp_loggedin_user_domain() . bp_get_follow_slug() . '/start-following/' . $leader_id . '/', 'follow_follow' ),
					'hover_type'           => $button_args['button_attr']['hover_type'] ?? false,
					'data-title'           => '',
					'data-title-displayed' => '',
				),
			),
			$button_args
		);
	}

	/**
	 * Filters the HTML for the follow button.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $button HTML markup for follow button.
	 */
	return bp_get_button( apply_filters( 'bp_get_add_follow_button', $button ) );
}

/**
 * Output the activity entry CSS class.
 *
 * @since BuddyBoss 1.5.0
 */
function bp_activity_entry_css_class() {
	echo bp_get_activity_entry_css_class();
}

/**
 * Return the current activity entry item's CSS class.
 *
 * @since BuddyBoss 1.5.0
 *
 * @global object $activities_template {@link BP_Activity_Template}
 *
 * @return string The activity entry item's CSS class.
 */
function bp_get_activity_entry_css_class() {
	global $activities_template;

	$class = '';

	/**
	 * Filters the determined classes to add to the HTML element.
	 *
	 * @since BuddyBoss 1.5.0
	 *
	 * @param string $value Classes to be added to the HTML element.
	 */
	return apply_filters( 'bp_get_activity_entry_css_class', $class );
}

/**
 * Determine if the current user can edit an activity comment item.
 *
 * @since BuddyBoss 2.4.40
 *
 * @param false|BP_Activity_Activity $activity_comment Optional. Falls back on the current item in the loop.
 * @param bool                       $privacy_edit     Optional. True if editing privacy.
 *
 * @return bool True if can edit, false otherwise.
 */
function bb_activity_comment_user_can_edit( $activity_comment = false, $privacy_edit = false ) {

	// Try to use current activity comment if none was passed.
	if ( empty( $activity_comment ) ) {
		$activity_comment = bp_activity_current_comment();
	}

	// Assume the user cannot edit the activity item.
	$can_edit = false;

	// Only logged in users can edit activity comment and Activity must be of type 'activity_update', 'activity_comment'.
	if (
		is_user_logged_in() &&
		in_array( $activity_comment->type, array( 'activity_update', 'activity_comment' ), true )
	) {

		// Users are allowed to edit their own activity.
		if ( isset( $activity_comment->user_id ) && ( bp_loggedin_user_id() === $activity_comment->user_id ) ) {
			$can_edit = true;
		}

		// Viewing a single item, and this user is an admin of that item.
		if ( bp_is_single_item() && bp_is_item_admin() ) {
			$can_edit = true;
		}
	}

	if ( $can_edit && ! $privacy_edit ) {

		// Check activity comment edit time expiration.
		$activity_comment_edit_time        = (int) bb_get_activity_comment_edit_time(); // for 10 minutes, 600.
		$bp_dd_get_time                    = bp_core_current_time( true, 'timestamp' );
		$activity_comment_edit_expire_time = strtotime( $activity_comment->date_recorded ) + $activity_comment_edit_time;

		// Checking if expire time still greater than current time.
		if ( - 1 !== $activity_comment_edit_time && $activity_comment_edit_expire_time <= $bp_dd_get_time ) {
			$can_edit = false;
		}
	}

	/**
	 * Filters whether the current user can edit an activity comment item.
	 *
	 * @since BuddyBoss 2.4.40
	 *
	 * @param bool   $can_edit         Whether the user can edit the item.
	 * @param object $activity_comment Current activity item object.
	 */
	return (bool) apply_filters( 'bb_activity_comment_user_can_edit', $can_edit, $activity_comment );
}

/**
 * Determine whether favorites are allowed.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return bool
 */
function bb_activity_comment_can_favorite() {

	$retval = is_user_logged_in() && bb_is_reaction_activity_comments_enabled();

	/**
	 * Filters whether users can favorite activity comment items.
	 *
	 * @since BuddyBoss 2.5.20
	 *
	 * @param bool $value Whether comment favoriting is enabled.
	 */
	return (bool) apply_filters( 'bb_activity_comment_can_favorite', $retval );
}

/**
 * Return whether the current activity comment is in a current user's favorites.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return bool
 *
 * @global object $activities_template {@link BP_Activity_Template}
 */
function bb_get_activity_comment_is_favorite() {
	/**
	 * Filters whether the current activity comment item is in the current user's favorites.
	 *
	 * @since BuddyBoss 2.5.20
	 *
	 * @param bool $value Whether the current activity item is in the current user's favorites.
	 */
	return (bool) apply_filters( 'bb_get_activity_comment_is_favorite', bb_activity_is_item_favorite( bp_get_activity_comment_id(), 'activity_comment' ) );
}

/**
 * Return the activity comment favorite link.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int $activity_comment_id The activity comment ID.
 *
 * @return string The activity favorite link.
 *
 * @global object $activities_template {@link BP_Activity_Template}
 */
function bb_get_activity_comment_favorite_link( $activity_comment_id = 0 ) {

	if ( empty( $activity_comment_id ) ) {
		$activity_comment_id = bp_get_activity_comment_id();
	}

	/**
	 * Filters the activity comment favorite link.
	 *
	 * @since BuddyBoss 2.5.20
	 *
	 * @param string $value Constructed link for favoriting the activity comment.
	 */
	return apply_filters( 'bb_get_activity_comment_favorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/favorite/' . $activity_comment_id . '/' ), 'mark_favorite' ) );
}

/**
 * Return the activity comment unfavorite link.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int $activity_comment_id The activity comment ID.
 *
 * @return string The activity comment unfavorite link.
 *
 * @global object $activities_template {@link BP_Activity_Template}
 */
function bb_get_activity_comment_unfavorite_link( $activity_comment_id = 0 ) {

	if ( empty( $activity_comment_id ) ) {
		$activity_comment_id = bp_get_activity_comment_id();
	}
	/**
	 * Filters the activity comment unfavorite link.
	 *
	 * @since BuddyBoss 2.5.20
	 *
	 * @param string $value Constructed link for unfavoriting the activity comment.
	 */
	return apply_filters( 'bb_get_activity_comment_unfavorite_link', wp_nonce_url( home_url( bp_get_activity_root_slug() . '/unfavorite/' . $activity_comment_id . '/' ), 'unmark_favorite' ) );
}
