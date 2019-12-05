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
