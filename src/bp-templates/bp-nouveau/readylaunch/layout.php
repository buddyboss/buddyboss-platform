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

$is_ld_archive    = false;
$is_ld_assignment = false;
if ( function_exists( 'learndash_get_post_type_slug' ) ) {
	$is_ld_archive    = is_post_type_archive( learndash_get_post_type_slug( 'course' ) );
	$is_ld_assignment = is_singular( learndash_get_post_type_slug( 'assignment' ) );
}

/**
 * Fires before the layout.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bb_rl_layout_before' );

if ( $readylaunch_instance->bb_rl_is_memberpress_courses_page() ) {
	$bb_rl_meprlms_template = BB_Readylaunch_Memberpress_Courses_Integration::bb_rl_meprlms_get_template();
	if ( $bb_rl_meprlms_template ) {
		load_template( $bb_rl_meprlms_template );
	}
} elseif ( have_posts() ) {

	/**
	 * Fires before the loop starts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_rl_layout_before_loop' );

		/* Start the Loop */
	while ( have_posts() ) :
		the_post();

		if ( $is_ld_archive ) {
			bp_get_template_part( 'learndash/ld30/course-loop' );
		} elseif ( $is_ld_assignment ) {
			bp_get_template_part( 'learndash/ld30/assignment' );
		} else {
			the_content();
		}

	endwhile;

	/**
	 * Fires after the loop ends.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_rl_layout_after_loop' );
} else {

	/**
	 * Fires when no posts are found.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_rl_layout_no_posts' );
}

/**
 * Fires after the layout.
 *
 * @since BuddyBoss [BBVERSION]
 */
do_action( 'bb_rl_layout_after' );

bp_get_template_part( 'footer/readylaunch-footer' );
