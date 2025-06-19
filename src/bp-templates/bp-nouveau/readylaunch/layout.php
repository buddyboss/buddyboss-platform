<?php
/**
 * The layout for templates.
 *
 * This template handles the main layout structure for ReadyLaunch theme pages.
 * It determines whether to load LearnDash integration or standard page content.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'header/readylaunch-header' );

$readylaunch_instance = bb_load_readylaunch();

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
