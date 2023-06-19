<?php
/**
 * BuddyPress Search Filters.
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'template_include', 'bp_search_override_wp_native_results', 999 ); // don't leave any chance!.

/**
 * Force native wp search section to load page template so we can hook stuff into it.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param mixed $template
 *
 * @return mixed
 **/
function bp_search_override_wp_native_results( $template ) {

	if ( bp_search_is_search() ) { // if search page.

		$live_template = locate_template(
			array(
				'buddyboss-global-search.php',
				'page.php',
				'single.php',
				'index.php',
			)
		);

		if ( '' != $live_template ) {
			return $live_template;
		}
	}

	return $template;
}


add_filter( 'template_include', 'bp_search_result_page_dummy_post_load', 999 ); // don't leave any chance!.
/**
 * Load dummy post for wp native search result. magic starts here.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param mixed $template
 *
 * @return mixed
 **/
function bp_search_result_page_dummy_post_load( $template ) {
	global $wp_query;

	if ( ! bp_search_is_search() ) { // cancel if not search page.
		return $template;
	}

	$dummy = array(
		'ID'                    => 0,
		'post_status'           => 'public',
		'post_author'           => 0,
		'post_parent'           => 0,
		'post_type'             => 'page',
		'post_date'             => 0,
		'post_date_gmt'         => 0,
		'post_modified'         => 0,
		'post_modified_gmt'     => 0,
		'post_content'          => '',
		'post_title'            => '',
		'post_excerpt'          => '',
		'post_content_filtered' => '',
		'post_mime_type'        => '',
		'post_password'         => '',
		'post_name'             => '',
		'guid'                  => '',
		'menu_order'            => 0,
		'pinged'                => '',
		'to_ping'               => '',
		'ping_status'           => '',
		'comment_status'        => 'closed',
		'comment_count'         => 0,
		'filter'                => 'raw',
		'is_404'                => false,
		'is_page'               => false,
		'is_single'             => false,
		'is_archive'            => false,
		'is_tax'                => false,
		'is_search'             => true,
	);
	// Set the $post global
	$post = new WP_Post( (object) $dummy );

	// Copy the new post global into the main $wp_query
	$wp_query->post          = $post;
	$wp_query->posts         = array( $post );
	$wp_query->post_count    = 1;
	$wp_query->max_num_pages = 0;

	return $template;
}


add_filter( 'pre_get_posts', 'bp_search_clear_native_search_query' );
/**
 * Force native wp search page not to look any data into db to save query and performance
 *
 * @since BuddyBoss 1.0.0
 *
 * @param mixed $query
 *
 * @return mixed
 **/
function bp_search_clear_native_search_query( $query ) {

	if ( bp_search_is_search() ) {

		remove_filter( 'pre_get_posts', 'bp_search_clear_native_search_query' ); // only do first time

	}

	return $query;
}

/**
 * Before searching groups parse type to be blank.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param mixed $args
 *
 * @return mixed
 **/
function bp_search_filter_bp_before_has_groups_parse_args( $args ) {

	if ( wp_doing_ajax() && isset( $_GET['action'] ) && $_GET['action'] === 'bp_search_ajax' ) {
		$args['type'] = '';
	}

	return $args;
}

add_filter( 'bp_before_has_groups_parse_args', 'bp_search_filter_bp_before_has_groups_parse_args', 10, 1 );
