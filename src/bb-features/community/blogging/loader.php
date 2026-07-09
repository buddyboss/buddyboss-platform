<?php
/**
 * Blogs feature runtime loader.
 *
 * Loaded via the feature registry php_loader only when the `blogging`
 * feature is active.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Blogging
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/bb-blogging-functions.php';

add_filter( 'the_content', 'bb_blog_append_post_footer_sections', 20 );

/**
 * Whether ReadyLaunch should take over blog pages.
 *
 * Reads the enabled-pages option directly instead of calling
 * BB_Readylaunch::instance() — instantiating the singleton from inside the
 * `bb_is_readylaunch_enabled_for_page` filter causes infinite constructor
 * recursion, because the singleton is only assigned after the constructor
 * (which applies that filter) returns.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool
 */
function bb_blog_rl_is_enabled() {
	$enabled_pages = bp_get_option( 'bb_rl_enabled_pages', array() );

	return ! empty( $enabled_pages['blog'] );
}

/**
 * Route blog URLs into ReadyLaunch when the Blog template page is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $retval Whether ReadyLaunch is enabled for the current page.
 *
 * @return bool
 */
function bb_blog_readylaunch_enabled_for_page( $retval ) {
	if ( bb_blog_rl_is_enabled() && bb_blog_is_blog_context() ) {
		return true;
	}

	return $retval;
}
add_filter( 'bb_is_readylaunch_enabled_for_page', 'bb_blog_readylaunch_enabled_for_page' );

/**
 * Open the blog archive grid and render the archive header.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blog_rl_archive_open() {
	if ( is_singular() || ! bb_blog_is_blog_context() ) {
		return;
	}

	bp_get_template_part( 'blog/archive-header' );
	echo '<div class="bb-rl-blog-grid">';
}
add_action( 'bb_rl_layout_before_loop', 'bb_blog_rl_archive_open' );

/**
 * Close the blog archive grid and render pagination.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blog_rl_archive_close() {
	if ( is_singular() || ! bb_blog_is_blog_context() ) {
		return;
	}

	echo '</div>';

	the_posts_pagination(
		array(
			'mid_size'  => 2,
			'prev_text' => esc_html__( 'Previous', 'buddyboss' ),
			'next_text' => esc_html__( 'Next', 'buddyboss' ),
		)
	);
}
add_action( 'bb_rl_layout_after_loop', 'bb_blog_rl_archive_close' );
