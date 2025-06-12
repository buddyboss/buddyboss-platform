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
} elseif ( bp_is_active( 'forums' ) && $readylaunch_instance->bb_is_readylaunch_forums() ) {
	$readylaunch_instance->bb_rl_forums_integration_page();
} elseif ( have_posts() ) {
	/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
	endwhile;
}

bp_get_template_part( 'footer/readylaunch-footer' );
