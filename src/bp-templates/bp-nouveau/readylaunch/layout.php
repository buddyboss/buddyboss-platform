<?php
/**
 * The layout for templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'header/readylaunch-header' );

$courses_integration = bp_get_option( 'bb_rl_enabled_pages' )['courses'] ?? false;
if ( $courses_integration && BB_Readylaunch::instance()->bb_rl_is_learndash_page() ) {
	BB_Readylaunch::instance()->bb_rl_courses_integration_page();
} elseif ( have_posts() ) {
		/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
		endwhile;
}

bp_get_template_part( 'footer/readylaunch-footer' );
