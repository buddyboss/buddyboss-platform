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
 * @since BuddyBoss 1.4.0
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
 * @since BuddyBoss 1.4.0
 *
 * @return object The current moderation within the loop.
 * @global object $moderation_template {@link BP_Moderation_Template}
 *
 */
function bp_the_moderation() {
	global $moderation_template;

	return $moderation_template->the_moderation();
}
