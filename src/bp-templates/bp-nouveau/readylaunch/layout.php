<?php
/**
 * The layout for templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'header/readylaunch-header' );

if ( have_posts() ) :
	/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
	endwhile;
endif;


bp_get_template_part( 'footer/readylaunch-footer' );
