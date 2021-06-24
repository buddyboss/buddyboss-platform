<?php
/**
 * BuddyBoss Moderation Template Functions.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the moderation component slug.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_slug() {
	echo esc_html( bp_get_moderation_slug() );
}

/**
 * Return the moderation component slug.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return string The moderation component slug.
 */
function bp_get_moderation_slug() {

	/**
	 * Filters the moderation component slug.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $slug Activity component slug.
	 */
	return apply_filters( 'bp_get_moderation_slug', buddypress()->moderation->slug );
}

/**
 * Output the moderation component root slug.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_root_slug() {
	echo esc_html( bp_get_moderation_root_slug() );
}

/**
 * Return the moderation component root slug.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return string The moderation component root slug.
 */
function bp_get_moderation_root_slug() {

	/**
	 * Filters the moderation component root slug.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $root_slug Activity component root slug.
	 */
	return apply_filters( 'bp_get_moderation_root_slug', buddypress()->moderation->root_slug );
}

/**
 *
 * Initialize the moderation loop.
 *
 * Based on the $args passed, bp_has_moderation() populates the
 * $moderation_template global, enabling the use of BuddyPress templates and
 * template functions to display a list of moderation items.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array|string $args                {
 *                                          Arguments for limiting the contents of the moderation loop. Most arguments
 *                                          are in the same format as {@link BP_Moderation::get()}. However,
 *                                          because the format of the arguments accepted here differs in a number of
 *                                          ways, and because bp_has_moderation() determines some default arguments in
 *                                          a dynamic fashion, we list all accepted arguments here as well.
 *
 *     Arguments can be passed as an associative array, or as a URL querystring
 *     (eg, 'user_id=4&fields=all').
 *
 * @type int           $page                Which page of results to fetch. Using page=1 without per_page will result
 *                                           in no pagination. Default: 1.
 * @type int|bool      $per_page            Number of results per page. Default: 25.
 * @type int|bool      $max                 Maximum number of results to return. Default: false (unlimited).
 * @type string        $fields              Moderation fields to return. Pass 'ids' to get only the moderation IDs.
 *                                           'all' returns full moderation objects.
 * @type string        $user_id             Array of user to filter out moderation report.
 * @type string        $sort                ASC or DESC. Default: 'DESC'.
 * @type string        $order_by            Column to order results by.
 * @type array         $exclude             Array of moderation report IDs to exclude. Default: false.
 * @type array         $in                  Array of ids to limit query by (IN). Default: false.
 * @type array         $exclude_types       Array of moderation item type to exclude. Default: false.
 * @type array         $in_types            Array of item type to limit query by (IN). Default: false.
 * @type array         $meta_query          Array of meta_query conditions. See WP_Meta_Query::queries.
 * @type array         $date_query          Array of date_query conditions. See first parameter of
 *                                           WP_Date_Query::__construct().
 * @type array         $filter_query        Array of advanced query conditions. See BP_Moderation_Query::__construct().
 * @type bool          $display_reporters   Whether to include moderation reported users. Default: false.
 * @type bool          $update_meta_cache   Whether to pre-fetch metadata for queried moderation items. Default: true.
 * @type string|bool   $count_total         If true, an additional DB query is run to count the total moderation items
 *                                           for the query. Default: false.
 * }
 * @return bool Returns true when moderation found, otherwise false.
 * @global object      $moderation_template {@link BP_Moderation_Template}
 */
function bp_has_moderation( $args = '' ) {
	global $moderation_template;

	$args = bp_parse_args( $args );

	if ( bp_is_my_profile() && 'blocked-members' === bp_current_action() ) {
		$args['in_types'] = array( 'user' );
		$args['user_id']  = bp_loggedin_user_id();
	}

	/*
	 * Parse Args.
	 */
	// Note: any params used for filtering can be a single value, or multiple
	// values comma separated.
	$r = bp_parse_args(
		$args,
		array(
			'page'              => 1,               // The current page.
			'per_page'          => 20,              // Moderation items per page.
			'user_id'           => false,           // filter by user id.
			'max'               => false,           // Max number of items to return.
			'fields'            => 'all',           // Fields to include.
			'sort'              => 'DESC',          // ASC or DESC.
			'order_by'          => 'last_updated', // Column to order by.
			'exclude'           => false,           // Array of ids to exclude.
			'in'                => false,           // Array of ids to limit query by (IN).
			'exclude_types'     => false,           // Array of type to exclude.
			'in_types'          => false,           // Array of type to limit query by (IN).
			// phpcs:ignore
			'meta_query'        => false,           // Filter by moderationmeta.
			'date_query'        => false,           // Filter by date.
			'filter_query'      => false,           // Advanced filtering - see BP_Moderation_Query.
			// phpcs:ignore
			'filter'            => false,           // See self::get_filter_sql().
			'display_reporters' => false,           // Whether or not to fetch user data.
			'update_meta_cache' => true,            // Whether or not to update meta cache.
			'count_total'       => true,           // Whether or not to use count_total.
		),
		'has_moderation'
	);

	/*
	 * Query
	 */
	$moderation_template = new BP_Moderation_Template( $r );

	/**
	 * Filters whether or not there are moderation items to display.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $value               Whether or not there are moderation items to display.
	 * @param string $moderation_template Current moderation template being used.
	 * @param array  $r                   Array of moderations passed into the BP_Moderation_Template class.
	 */
	return apply_filters( 'bp_has_moderation', $moderation_template->has_moderation(), $moderation_template, $r );
}

/**
 * Determine if there are still moderation left in the loop.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return bool Returns true when moderation are found.
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_moderation() {
	global $moderation_template;

	return $moderation_template->user_moderations();
}


/**
 * Get the current moderation object in the loop.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return object The current moderation within the loop.
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_the_moderation() {
	global $moderation_template;

	return $moderation_template->the_moderation();
}

/**
 * Return the moderation ID.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return int The moderation ID.
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_get_moderation_id() {
	global $moderation_template;

	/**
	 * Filters the moderation ID being displayed.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $id The moderation ID.
	 */

	if ( isset( $moderation_template->moderation->id ) ) {
		$moderation_id = $moderation_template->moderation->id;
	}

	return apply_filters( 'bp_get_moderation_id', $moderation_id );
}

/**
 * Return the moderation item ID.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return int The moderation ID.
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_get_moderation_item_id() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $item_id The moderation item ID.
	 */

	if ( isset( $moderation_template->moderation->item_id ) ) {
		$moderation_item_id = $moderation_template->moderation->item_id;
	}

	return apply_filters( 'bp_get_moderation_item_id', $moderation_item_id );
}

/**
 * Return the moderation item type.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return string The moderation type.
 *
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_get_moderation_item_type() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $item_type The moderation item ID.
	 */

	if ( isset( $moderation_template->moderation->item_type ) ) {
		$moderation_item_type = $moderation_template->moderation->item_type;
	}

	return apply_filters( 'bp_get_moderation_item_type', $moderation_item_type );
}

/**
 * Return the moderation hide site wide or not.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return int hide/show.
 *
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_get_moderation_hide_site_wide() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $hide_sitewide The moderation hide site wide.
	 */

	if ( isset( $moderation_template->moderation->hide_sitewide ) ) {
		$moderation_hide_site_wide = $moderation_template->moderation->hide_sitewide;
	}

	return apply_filters( 'bp_get_moderation_hide_site_wide', $moderation_hide_site_wide );
}

/**
 * Return the moderation last updated.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return string moderation late updated.
 *
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_get_moderation_last_updated() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $last_updated The moderation last updated.
	 */

	if ( isset( $moderation_template->moderation->last_updated ) ) {
		$moderation_reported_date = $moderation_template->moderation->last_updated;
	}

	return apply_filters(
		'bp_get_moderation_last_updated',
		date_i18n(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			strtotime( $moderation_reported_date )
		)
	);
}

/**
 * Return the moderation reported category.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return string moderation reported category.
 *
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_get_moderation_reported_category() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $reported_category The moderation reported category.
	 */

	if ( isset( $moderation_template->moderation->reporters[0]->category_id ) ) {
		$term_data                    = get_term( $moderation_template->moderation->reporters[0]->category_id );
		$moderation_reported_category = ( ! is_wp_error( $term_data->name ) && ! empty( $term_data->name ) ) ? $term_data->name : '';
	}

	return apply_filters( 'bp_get_moderation_reported_category', $moderation_reported_category );
}

/**
 * Return true when there are more moderation items to be shown than currently appear.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return bool $has_more_items True if more items, false if not.
 * @global object $moderation_template {@link BP_Moderation_Template}
 */
function bp_moderation_has_more_items() {
	global $moderation_template;

	if ( ! empty( $moderation_template->has_more_items ) ) {
		$has_more_items = true;
	} else {
		$remaining_pages = 0;

		if ( ! empty( $moderation_template->pag_page ) ) {
			$remaining_pages = floor( ( $moderation_template->total_moderation_count - 1 ) / ( $moderation_template->pag_num * $moderation_template->pag_page ) );
		}

		$has_more_items = (int) $remaining_pages > 0;
	}

	/**
	 * Filters whether there are more moderation items to display.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool $has_more_items Whether or not there are more moderation items to display.
	 */
	return apply_filters( 'bp_moderation_has_more_items', $has_more_items );
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_load_more_link() {
	echo esc_url( bp_get_moderation_load_more_link() );
}

/**
 * Get the URL for the Load More link.
 *
 * @since BuddyBoss 1.5.6
 *
 * @return string $link
 */
function bp_get_moderation_load_more_link() {
	global $moderation_template;

	$url  = bp_get_requested_url();
	$link = add_query_arg( $moderation_template->pag_arg, $moderation_template->pag_page + 1, $url );

	/**
	 * Filters the Load More link URL.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $moderation_template The moderation template loop global.
	 */
	return apply_filters( 'bp_get_moderation_load_more_link', $link, $url, $moderation_template );
}
