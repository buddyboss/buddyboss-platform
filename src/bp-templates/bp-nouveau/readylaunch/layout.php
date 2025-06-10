<?php
/**
 * The layout for templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'header/readylaunch-header' );

$readylaunch_instance = BB_Readylaunch::instance();
if (
	$readylaunch_instance->bb_rl_is_learndash_page() &&
	$readylaunch_instance->bb_rl_is_page_enabled_for_integration( 'courses' )
) {
	$readylaunch_instance->bb_rl_courses_integration_page();
} elseif ( $readylaunch_instance->bb_rl_is_memberpress_courses_page() ) {
	$bb_rl_meprlms_template = BB_Readylaunch_Memberpress_Courses_Integration::bb_rl_meprlms_get_template();
	if ( $bb_rl_meprlms_template ) {
		load_template( $bb_rl_meprlms_template );
	}
} elseif ( have_posts() ) {
		/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
		endwhile;
}

bp_get_template_part( 'footer/readylaunch-footer' );
