<?php
/**
 * The layout for templates.
 *
 * This template handles the main layout structure for ReadyLaunch theme pages.
 * It determines whether to load BuddyPress integration or standard page content.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'header/readylaunch-header' );

$readylaunch_instance = bb_load_readylaunch();

if ( have_posts() ) {
	/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
	endwhile;
}

bp_get_template_part( 'footer/readylaunch-footer' );
