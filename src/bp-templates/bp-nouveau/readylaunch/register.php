<?php
/**
 * The layout for register templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

wp_head();

if ( have_posts() ) :
	/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		the_content();
	endwhile;
endif;

wp_footer();

