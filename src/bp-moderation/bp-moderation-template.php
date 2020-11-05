<?php
/**
 * BuddyBoss Moderation Template Functions.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the moderation component slug.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_slug() {
	echo esc_html( bp_get_moderation_slug() );
}

/**
 * Return the moderation component slug.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return string The moderation component slug.
 */
function bp_get_moderation_slug() {

	/**
	 * Filters the moderation component slug.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $slug Activity component slug.
	 */
	return apply_filters( 'bp_get_moderation_slug', buddypress()->moderation->slug );
}

/**
 * Output the moderation component root slug.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_root_slug() {
	echo esc_html( bp_get_moderation_root_slug() );
}

/**
 * Return the moderation component root slug.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return string The moderation component root slug.
 */
function bp_get_moderation_root_slug() {

	/**
	 * Filters the moderation component root slug.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $root_slug Activity component root slug.
	 */
	return apply_filters( 'bp_get_moderation_root_slug', buddypress()->moderation->root_slug );
}

function bp_has_moderation( $args = '' ) {
	global $moderation_template;

	$args = bp_parse_args( $args );

	if ( bp_is_user_moderation() && 'reported-content' === bp_current_action() ) {
		$args['exclude_types'] = array( 'user' );
	} elseif ( bp_is_user_moderation() && 'blocked-members' === bp_current_action() ) {
		$args['in_types'] = array( 'user' );
	}

	if ( bp_is_user_moderation() ) {
		$args['user_id'] = bbp_get_displayed_user_id();
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
	 * @since BuddyBoss 1.5.4
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
 * @since BuddyBoss 1.5.4
 *
 * @return bool Returns true when moderation are found.
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_moderation() {
	global $moderation_template;

	return $moderation_template->user_moderations();
}


/**
 * Get the current moderation object in the loop.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return object The current moderation within the loop.
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_the_moderation() {
	global $moderation_template;

	return $moderation_template->the_moderation();
}

/**
 * Return the moderation ID.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return int The moderation ID.
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_get_moderation_id() {
	global $moderation_template;

	/**
	 * Filters the moderation ID being displayed.
	 *
	 * @since BuddyBoss 1.5.4
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
 * @since BuddyBoss 1.5.4
 *
 * @return int The moderation ID.
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_get_moderation_item_id() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.4
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
 * @since BuddyBoss 1.5.4
 *
 * @return string The moderation type.
 *
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_get_moderation_item_type() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.4
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
 * @since BuddyBoss 1.5.4
 *
 * @return int hide/show.
 *
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_get_moderation_hide_site_wide() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.4
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
 * @since BuddyBoss 1.5.4
 *
 * @return string moderation late updated.
 *
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_get_moderation_last_updated() {
	global $moderation_template;

	/**
	 * Filters the moderation item ID being displayed.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $last_updated The moderation last updated.
	 */

	if ( isset( $moderation_template->moderation->last_updated ) ) {
		$moderation_hide_site_wide = $moderation_template->moderation->last_updated;
	}

	return apply_filters( 'bp_get_moderation_last_updated', $moderation_hide_site_wide );
}

/**
 * Return true when there are more moderation items to be shown than currently appear.
 *
 * @since BuddyBoss 1.5.4
 *
 * @return bool $has_more_items True if more items, false if not.
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
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
	 * @since BuddyBoss 1.5.4
	 *
	 * @param bool $has_more_items Whether or not there are more moderation items to display.
	 */
	return apply_filters( 'bp_moderation_has_more_items', $has_more_items );
}

/**
 * Output the URL for the Load More link.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_load_more_link() {
	echo esc_url( bp_get_moderation_load_more_link() );
}

/**
 * Get the URL for the Load More link.
 *
 * @since BuddyBoss 1.5.4
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
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $link                The "Load More" link URL with appropriate query args.
	 * @param string $url                 The original URL.
	 * @param object $moderation_template The moderation template loop global.
	 */
	return apply_filters( 'bp_get_moderation_load_more_link', $link, $url, $moderation_template );
}