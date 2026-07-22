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

/**
 * Register the member profile "Blogs" navigation item.
 *
 * Slug is `blog` (singular) — `blogs` is reserved by the BuddyPress
 * multisite Sites component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blog_setup_profile_nav() {
	/**
	 * Whether the member profile Blogs tab renders.
	 *
	 * Off by default — consumers opt in: the Member Blogging add-on when
	 * member blogging is enabled, Platform Pro when bookmarking is enabled.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $enabled Whether to register the Blogs profile nav.
	 */
	if ( ! apply_filters( 'bb_blog_profile_nav_enabled', false ) ) {
		return;
	}

	bp_core_new_nav_item(
		array(
			'name'                    => __( 'Blogs', 'buddyboss' ),
			'slug'                    => 'blog',
			'position'                => 90,
			'screen_function'         => 'bb_blog_screen_member_posts',
			'default_subnav_slug'     => apply_filters( 'bb_blog_profile_default_subnav', 'blog' ),
			'show_for_displayed_user' => true,
		)
	);
}
add_action( 'bp_setup_nav', 'bb_blog_setup_profile_nav', 100 );

/**
 * Screen handler for the member "Blogs" tab.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blog_screen_member_posts() {
	add_action( 'bp_template_content', 'bb_blog_member_posts_content' );

	/**
	 * Filter the template loaded for the member Blogs screen.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $template Template name.
	 */
	bp_core_load_template( apply_filters( 'bb_blog_screen_member_posts_template', 'members/single/home' ) );
}

/**
 * Output the member "Blogs" tab content.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blog_member_posts_content() {
	bp_get_template_part( 'members/single/blog' );
}

/**
 * Enqueue the member profile Blogs tab base stylesheet.
 *
 * Loads the self-contained structural styles for the standard (non-ReadyLaunch)
 * template pack. Themes may skin the same `.bb-member-blog*` selectors on top.
 * ReadyLaunch ships its own blog stylesheet, so this bails in RL mode.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_blog_enqueue_member_blog_assets() {
	if ( ! function_exists( 'bp_is_user' ) || ! bp_is_user() || ! bp_is_current_component( 'blog' ) ) {
		return;
	}

	// ReadyLaunch enqueues its own blog stylesheet.
	if ( function_exists( 'bb_is_readylaunch_enabled' ) && bb_is_readylaunch_enabled() ) {
		return;
	}

	wp_enqueue_style(
		'bb-member-blog',
		buddypress()->plugin_url . 'bp-templates/bp-nouveau/buddypress/css/member-blog.css',
		array(),
		bp_get_version()
	);
}
add_action( 'bp_enqueue_scripts', 'bb_blog_enqueue_member_blog_assets' );
