<?php
/**
 * The layout for templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_get_template_part( 'header/readylaunch-header' );

// Check if we're on a LearnDash archive page
$is_learndash_archive = false;
if (class_exists('BB_Readylaunch') && method_exists(BB_Readylaunch::instance(), 'bb_is_learndash_page')) {
    $is_learndash_archive = BB_Readylaunch::instance()->bb_is_learndash_page() && 
                          (is_post_type_archive('sfwd-courses') || strpos($_SERVER['REQUEST_URI'], '/courses/') !== false);
}

if ( have_posts() ) :
	if ($is_learndash_archive) {
	    // For LearnDash archive pages, only process the first post
	    the_post();
	    the_content();
	} else {
	    // For non-LearnDash pages, process all posts normally
	    /* Start the Loop */
	    while ( have_posts() ) :
		    the_post();
		    the_content();
	    endwhile;
	}
endif;

bp_get_template_part( 'footer/readylaunch-footer' );
