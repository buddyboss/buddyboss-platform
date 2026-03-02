<?php
/**
 * The layout for templates.
 *
 * This template handles the main layout structure for ReadyLaunch theme pages.
 * It determines whether to load LearnDash integration or standard page content.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'header/readylaunch-header' );

$readylaunch_instance = BB_Readylaunch::instance();

/**
 * Fires before the layout.
 *
 * @since BuddyBoss 2.9.00
 */
do_action( 'bb_rl_layout_before' );

if ( have_posts() ) {

	/**
	 * Fires before the loop starts.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	do_action( 'bb_rl_layout_before_loop' );

		/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		do_action( 'bb_rl_get_template_part_content' );

	endwhile;

	/**
	 * Fires after the loop ends.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	do_action( 'bb_rl_layout_after_loop' );
} else {

	/**
	 * Fires when no posts are found.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	do_action( 'bb_rl_layout_no_posts' );
}

/**
 * Fires after the layout.
 *
 * @since BuddyBoss 2.9.00
 */
do_action( 'bb_rl_layout_after' );

bp_get_template_part( 'footer/readylaunch-footer' );
