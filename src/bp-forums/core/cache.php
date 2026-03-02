<?php

/**
 * Forums Cache Helpers
 *
 * Helper functions used to communicate with WordPress's various caches. Many
 * of these functions are used to work around specific WordPress nuances. They
 * are subject to changes, tweaking, and will need iteration as performance
 * improvements are made to WordPress core.
 *
 * @package BuddyBoss\Cache
 * @since bbPress (r4011)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Helpers *******************************************************************/

/**
 * Skip invalidation of child post content when editing a parent.
 *
 * This prevents invalidating caches for topics and replies when editing a forum
 * or a topic. Without this in place, WordPress will attempt to invalidate all
 * child posts whenever a parent post is modified. This can cause thousands of
 * cache invalidations to occur on a single edit, which is no good for anyone.
 *
 * @since bbPress (r4011)
 */
class BBP_Skip_Children {

	/**
	 * @var int Post ID being updated
	 */
	private $updating_post = 0;

	/**
	 * @var bool The original value of $_wp_suspend_cache_invalidation global
	 */
	private $original_cache_invalidation = false;

	/** Methods ***************************************************************/

	/**
	 * Hook into the 'pre_post_update' action.
	 *
	 * @since bbPress (r4011)
	 */
	public function __construct() {
		add_action( 'pre_post_update', array( $this, 'pre_post_update' ) );
	}

	/**
	 * Only clean post caches for main Forums posts.
	 *
	 * Check that the post being updated is a Forums post type, saves the
	 * post ID to be used later, and adds an action to 'clean_post_cache' that
	 * prevents child post caches from being cleared.
	 *
	 * @since bbPress (r4011)
	 *
	 * @param int $post_id The post ID being updated
	 * @return If invalid post data
	 */
	public function pre_post_update( $post_id = 0 ) {

		// Bail if post ID is not a Forums post type
		if ( empty( $post_id ) || ! bbp_is_custom_post_type( $post_id ) ) {
			return;
		}

		// Store the $post_id
		$this->updating_post = $post_id;

		// Skip related post cache invalidation. This prevents invalidating the
		// caches of the child posts when there is no reason to do so.
		add_action( 'clean_post_cache', array( $this, 'skip_related_posts' ) );
	}

	/**
	 * Skip cache invalidation of related posts if the post ID being invalidated
	 * is not the one that was just updated.
	 *
	 * @since bbPress (r4011)
	 *
	 * @param int $post_id The post ID of the cache being invalidated
	 * @return If invalid post data
	 */
	public function skip_related_posts( $post_id = 0 ) {

		// Bail if this post is not the current Forums post
		if ( empty( $post_id ) || ( $this->updating_post !== $post_id ) ) {
			return;
		}

		// Stash the current cache invalidation value in a variable, so we can
		// restore back to it nicely in the future.
		global $_wp_suspend_cache_invalidation;

		$this->original_cache_invalidation = $_wp_suspend_cache_invalidation;

		// Turn off cache invalidation
		wp_suspend_cache_invalidation( true );

		// Restore cache invalidation
		add_action( 'wp_insert_post', array( $this, 'restore_cache_invalidation' ) );
	}

	/**
	 * Restore the cache invalidation to its previous value.
	 *
	 * @since bbPress (r4011)
	 * @uses wp_suspend_cache_invalidation()
	 */
	public function restore_cache_invalidation() {
		wp_suspend_cache_invalidation( $this->original_cache_invalidation );
	}
}
new BBP_Skip_Children();

/** General *******************************************************************/

/**
 * Will clean a post in the cache.
 *
 * Will call to clean the term object cache associated with the post ID.
 *
 * @since 2.1.0 bbPress (r4040)
 * @since 2.6.0 bbPress (r6053) Introduced the `$post_id` parameter.
 *
 * @param int     $post_id The post id.
 * @param WP_Post $post    The WP_Post object.
 */
function bbp_clean_post_cache( $post_id = null, $post = null ) {

	// Bail if no post.
	if ( empty( $post ) ) {
		$post = get_post( $post_id );
		if ( empty( $post ) ) {
			return;
		}
	}

	// Child query types to clean.
	$post_types = array(
		bbp_get_topic_post_type(),
		bbp_get_forum_post_type(),
		bbp_get_reply_post_type(),
	);

	// Bail if not a bbPress post type.
	if ( ! in_array( $post->post_type, $post_types, true ) ) {
		return;
	}

	wp_cache_delete( $post->ID, 'posts' );
	wp_cache_delete( $post->ID, 'post_meta' );

	clean_object_term_cache( $post->ID, $post->post_type );

	do_action( 'bbp_clean_post_cache', $post->ID, $post );

	// Loop through query types and clean caches.
	foreach ( $post_types as $post_type ) {
		wp_cache_delete( 'bbp_get_forum_' . $post->ID . '_reply_id', 'bbpress_posts' );
		wp_cache_delete( 'bbp_parent_' . $post->ID . '_type_' . $post_type . '_child_last_id', 'bbpress_posts' );
		wp_cache_delete( 'bbp_parent_' . $post->ID . '_type_' . $post_type . '_child_count', 'bbpress_posts' );
		wp_cache_delete( 'bbp_parent_all_' . $post->ID . '_type_' . $post_type . '_child_ids', 'bbpress_posts' );
		wp_cache_delete( 'bb_parent_all_' . $post->ID . '_type_' . $post_type . '_parent_ids', 'bbpress_posts' );
	}

	// Invalidate parent caches.
	if ( ! empty( $post->post_parent ) ) {
		clean_post_cache( $post->post_parent );

		// Only bump `last_changed` when forum-root is reached.
	} else {
		wp_cache_set( 'last_changed', microtime(), 'bbpress_posts' );
	}
}
