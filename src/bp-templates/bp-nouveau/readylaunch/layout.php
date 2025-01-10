<?php
/**
 * The layout for templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

bp_get_template_part( 'readylaunch-header' );

if ( have_posts() ) :
	/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
	endwhile;
endif;

bp_get_template_part( 'readylaunch-footer' );
