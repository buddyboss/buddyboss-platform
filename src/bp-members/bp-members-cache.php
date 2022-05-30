<?php
/**
 * Caching functions specific to BuddyPress Members.
 *
 * @package BuddyBoss\Members\Cache
 * @since BuddyPress 2.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Pre-fetch profile type data when initializing a Members loop.
 *
 * @since BuddyPress 2.2.0
 *
 * @param BP_User_Query $bp_user_query BP_User_Query object.
 */
function bp_members_prefetch_member_type( BP_User_Query $bp_user_query ) {
	$uncached_member_ids = bp_get_non_cached_ids( $bp_user_query->user_ids, 'bp_member_member_type' );

	$member_types = bp_get_object_terms(
		$uncached_member_ids,
		bp_get_member_type_tax_name(),
		array(
			'fields' => 'all_with_object_id',
		)
	);

	// Rekey by user ID.
	$keyed_member_types = array();
	foreach ( $member_types as $member_type ) {
		if ( ! isset( $keyed_member_types[ $member_type->object_id ] ) ) {
			$keyed_member_types[ $member_type->object_id ] = array();
		}

		if ( bp_get_member_type_object( $member_type->name ) ) {
			$keyed_member_types[ $member_type->object_id ][] = $member_type->name;
		}
	}

	$cached_member_ids = array();
	foreach ( $keyed_member_types as $user_id => $user_member_types ) {
		wp_cache_set( $user_id, $user_member_types, 'bp_member_member_type' );
		$cached_member_ids[] = $user_id;
	}

	// Cache an empty value for users with no type.
	foreach ( array_diff( $uncached_member_ids, $cached_member_ids ) as $no_type_id ) {
		wp_cache_set( $no_type_id, '', 'bp_member_member_type' );
	}
}
add_action( 'bp_user_query_populate_extras', 'bp_members_prefetch_member_type' );

/**
 * Clear the member_type cache for a user.
 *
 * Called when the user is deleted or marked as spam.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int $user_id ID of the deleted user.
 */
function bp_members_clear_member_type_cache( $user_id ) {
	wp_cache_delete( $user_id, 'bp_member_member_type' );
	wp_cache_delete( 'bp_get_all_posts_for_user_' . $user_id, 'bp_member' );
	wp_cache_delete( 'bp_check_user_status_' . $user_id, 'bp_member' );
	// Use below code for bp_member_type_by_type( $type_id ) function.
	$member_type = function_exists( 'bp_get_member_type' ) ? bp_get_member_type( $user_id ) : '';
	if ( ! empty( $member_type ) ) {
		$get_term = get_term_by( 'slug', $member_type, 'bp_member_type' );
		if ( ! empty( $get_term ) ) {
			$cache_key = 'bp_member_type_by_type_' . $get_term->term_id;
			wp_cache_delete( $cache_key, 'bp_member_member_type' );
		}
	}
}
add_action( 'wpmu_delete_user', 'bp_members_clear_member_type_cache' );
add_action( 'delete_user', 'bp_members_clear_member_type_cache' );
add_action( 'profile_update', 'bp_members_clear_member_type_cache' );

/**
 * Invalidate activity caches when a user's last_activity value is changed.
 *
 * @since BuddyPress 2.7.0
 *
 * @return bool True on success, false on failure.
 */
function bp_members_reset_activity_cache_incrementor() {
	return bp_core_reset_incrementor( 'bp_activity_with_last_activity' );
}
add_action( 'bp_core_user_updated_last_activity', 'bp_members_reset_activity_cache_incrementor' );

/**
 * Clear the member_type cache when member type post is updated.
 *
 * @since BuddyBoss 1.9.0
 *
 * @param int $post_id post ID.
 */
function bb_members_clear_member_type_cache_on_update( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$post = get_post( $post_id );

	if ( bp_get_member_type_post_type() !== $post->post_type ) {
		return;
	}

	if ( ! isset( $_POST['_bp-member-type-nonce'] ) ) {
		return;
	}

	// verify nonce.
	if ( ! wp_verify_nonce( $_POST['_bp-member-type-nonce'], 'bp-member-type-edit-member-type' ) ) {
		return;
	}

	// clear cache when updated.
	wp_cache_delete( 'bp_get_removed_member_types', 'bp_member_member_type' );
	wp_cache_delete( 'bp_get_all_member_types_posts', 'bp_member_member_type' );
	wp_cache_delete( 'bp_get_hidden_member_types_cache', 'bp_member_member_type' ); // Use with this function bp_get_hidden_member_types
	// Use for label type background and text color.
	wp_cache_delete( 'bb-member-type-label-css', 'bp_member_member_type' );
	$bp_member_type_key = get_post_meta( $post_id, '_bp_member_type_key', true );
	if ( ! empty( $bp_member_type_key ) ) {
		wp_cache_delete( 'bb-member-type-label-color-' . $bp_member_type_key, 'bp_member_member_type' );
	}
}

add_action( 'save_post', 'bb_members_clear_member_type_cache_on_update' );

/**
 * Clear the member_type cache when member type post is deleted.
 *
 * @since BuddyBoss 1.9.0
 *
 * @param int $post_id post ID.
 */
function bb_members_clear_member_type_cache_before_delete( $post_id ) {
	global $wpdb;

	$post = get_post( $post_id );

	// Return if post is not 'bp-member-type' type.
	if ( bp_get_member_type_post_type() !== $post->post_type ) {
		return;
	}

	// clear cache when deleted.
	wp_cache_delete( 'bp_get_removed_member_types', 'bp_member_member_type' );
	wp_cache_delete( 'bp_get_all_member_types_posts', 'bp_member_member_type' );
	// Use for label type background and text color.
	wp_cache_delete( 'bb-member-type-label-css', 'bp_member_member_type' );
	$bp_member_type_key = get_post_meta( $post_id, '_bp_member_type_key', true );
	if ( ! empty( $bp_member_type_key ) ) {
		wp_cache_delete( 'bb-member-type-label-color-' . $bp_member_type_key, 'bp_member_member_type' );
	}
}

add_action( 'before_delete_post', 'bb_members_clear_member_type_cache_before_delete' );
