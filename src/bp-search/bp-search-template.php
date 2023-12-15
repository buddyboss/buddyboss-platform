<?php
/**
 * BuddyBoss Search Template.
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'the_content', 'bp_search_search_page_content', 9 );
/**
 * BuddyBoss Search page content.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_search_page_content( $content ) {
	/**
	 * Reportedly, on some installations, the remove_filter call below, doesn't work and this filter is called over and over again.
	 * Possibly due to some other plugin/theme.
	 *
	 * Lets add another precautionary measure, a global flag.
	 *
	 * @since BuddyPress 1.1.3
	 */
	global $bpgs_main_content_filter_has_run;

	if ( bp_search_is_search() && 'yes' != $bpgs_main_content_filter_has_run ) {
		if (
			function_exists( 'wp_is_block_theme' ) &&
			wp_is_block_theme()
		) {
			$content = '';
		}

		remove_filter( 'the_content', 'bp_search_search_page_content', 9 );
		remove_filter( 'the_content', 'wpautop' );
		$bpgs_main_content_filter_has_run = 'yes';
		// setup search resutls and all..
		BP_Search::instance()->prepare_search_page();
		ob_start();
		bp_get_template_part( 'search/results-page' );
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Loads BuddyBoss Search template.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_load_template( $template, $variation = false ) {
	$file = $template;

	if ( $variation ) {
		$file .= '-' . $variation;
	}

	bp_get_template_part( 'search/' . $file );
}

/**
 * BuddyBoss Search page content.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_buffer_template_part( $template, $variation = '', $echo = true ) {
	ob_start();

	bp_search_load_template( $template, $variation );
	// Get the output buffer contents
	$output = ob_get_clean();

	// Echo or return the output buffer contents
	if ( true === $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

/**
 * Output BuddyBoss Search subnavigation tabs.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_filters() {
	BP_Search::instance()->print_tabs();
}

/**
 * Output BuddyBoss Search results for current subnavigation selection.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_search_results() {
	BP_Search::instance()->print_results();
}

/**
 * Filters the array of queried block templates array after they've been fetched.
 *
 * @since BuddyBoss 2.4.90
 *
 * @param WP_Block_Template[] $query_result Array of found block templates.
 * @param array               $query        {
 *                                          Arguments to retrieve templates. All arguments are optional.
 *
 * @type string[]             $slug__in     List of slugs to include.
 * @type int                  $wp_id        Post ID of customized template.
 * @type string               $area         A 'wp_template_part_area' taxonomy value to filter by (for 'wp_template_part' template type only).
 * @type string               $post_type    Post type to get the templates for.
 *                                          }
 *
 * @param string              $template_type wp_template or wp_template_part.
 *
 * return WP_Block_Template[] $query_result
 */
function bb_search_set_block_template_content( $query_result, $query, $template_type ) {
	// Check if the current page is buddyboss search page.
	if (
		bp_search_is_search() &&
		function_exists( 'wp_is_block_theme' ) &&
		wp_is_block_theme() &&
		in_array( 'search', $query['slug__in'], true )
	) {
		add_filter( 'bp_locate_template_and_load', '__return_false' );

		// Reset the template query array.
		$query_result = array();
		$slug         = current( $query['slug__in'] );

		$template_file    = array(
			'path' => bp_get_template_part( 'search/blocks/search' ),
			'slug' => $slug,
		);
		$prepare_template = _build_block_template_result_from_file( $template_file, $template_type );

		// Set template to query results.
		$query_result[] = $prepare_template;

		remove_filter( 'bp_locate_template_and_load', '__return_false' );
	}

	return $query_result;
}
add_filter( 'get_block_templates', 'bb_search_set_block_template_content', 10, 3 );

/**
 * Force search non-empty for the block theme.
 *
 * @since BuddyBoss 2.4.90
 *
 * @param string   $search   Search SQL for WHERE clause.
 * @param WP_Query $wp_query The current WP_Query object.
 *
 * @return mixed|string
 */
function bb_search_posts_search( $search, $wp_query ) {
	// Check if this is the main search query
	if ( is_admin() || ! $wp_query->is_main_query() ) {
		return $search;
	}

	if (
		bp_search_is_search() &&
		function_exists( 'wp_is_block_theme' ) &&
		wp_is_block_theme()
	) {
		$posts = get_posts( array(
			'numberposts' => 1,
			'fields'      => 'ids',
			'post_type'   => array(
				'page',
				'post'
			)
		) );

		if ( ! empty( $posts ) ) {
			$post_id = current( $posts );
			global $wpdb;
			$search .= " OR {$wpdb->posts}.ID = {$post_id}";
		}
	}

	return $search;
}

add_filter( 'posts_search', 'bb_search_posts_search', 10, 2 );
