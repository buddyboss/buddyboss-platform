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
 * @since BuddyBoss [BBVERSION]
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

		$new_content = preg_replace('/\/\*\*[\s\S]*?\*\//', '', file_get_contents( bp_get_template_part( 'search/blocks/search' ) ) );
		$new_content = str_replace( array( '<?php', '?>' ), '', $new_content );

		// Reset the template query array.
		$query_result     = array();
		$theme            = get_stylesheet();
		$slug             = current( $query['slug__in'] );
		$template_content = $new_content;
		$blocks           = parse_blocks( $template_content );

		$prepare_template                 = new WP_Block_Template();
		$prepare_template->id             = $theme . '//' . $slug;
		$prepare_template->theme          = $theme;
		$prepare_template->slug           = $slug;
		$prepare_template->content        = traverse_and_serialize_blocks( $blocks );
		$prepare_template->source         = 'buddyboss';
		$prepare_template->type           = $template_type;
		$prepare_template->title          = ! empty( $template_file['title'] ) ? $template_file['title'] : $slug;
		$prepare_template->status         = 'publish';
		$prepare_template->has_theme_file = false;
		$prepare_template->is_custom      = true;
		$prepare_template->modified       = null;


		// Get block templates.
		$template_files = _get_block_templates_files( $template_type, $query );

		// Replace all templates to buddyboss templates for search.
		if ( ! empty( $template_files ) ) {
			foreach ( $template_files as $template_file ) {
				$data = _build_block_template_result_from_file( $template_file, $template_type );

				// Set template to query results.
				if ( $template_file['slug'] === 'search' || $template_file['slug'] === 'index' ) {
					$query_result[] = $prepare_template;
				} else {
					$query_result[] = $data;
				}
			}
		} else {
			// Set template to query results.
			$query_result[] = $prepare_template;
		}
	}

	return $query_result;
}
add_filter( 'get_block_templates', 'bb_search_set_block_template_content', 10, 3 );
