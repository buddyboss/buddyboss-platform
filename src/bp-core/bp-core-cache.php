<?php
/**
 * BuddyBoss Core Caching Functions.
 *
 * Caching functions handle the clearing of cached objects and pages on specific
 * actions throughout BuddyPress.
 *
 * @package BuddyBoss
 * @supackage Cache
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prune the WP Super Cache.
 *
 * When WP Super Cache is installed, this function will clear cached pages
 * so that success/error messages or time-sensitive content are not cached.
 *
 * @since BuddyPress 1.0.0
 *
 * @see prune_super_cache()
 *
 * @return int
 */
function bp_core_clear_cache() {
	global $cache_path;

	if ( function_exists( 'prune_super_cache' ) ) {

		/**
		 * Fires before the pruning of WP Super Cache.
		 *
		 * @since BuddyPress 1.0.0
		 */
		do_action( 'bp_core_clear_cache' );
		return prune_super_cache( $cache_path, true );
	}
}

/**
 * Clear all cached objects for a user, or those that a user is part of.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $user_id User ID to delete cache for.
 */
function bp_core_clear_user_object_cache( $user_id ) {
	wp_cache_delete( 'bp_user_' . $user_id, 'bp' );
}

/**
 * Clear member count caches and transients.
 *
 * @since BuddyPress 1.6.0
 */
function bp_core_clear_member_count_caches() {
	wp_cache_delete( 'bp_total_member_count', 'bp' );
	delete_transient( 'bp_active_member_count' );
}
add_action( 'bp_core_activated_user', 'bp_core_clear_member_count_caches' );
add_action( 'bp_core_process_spammer_status', 'bp_core_clear_member_count_caches' );
add_action( 'bp_core_deleted_account', 'bp_core_clear_member_count_caches' );
add_action( 'bp_first_activity_for_member', 'bp_core_clear_member_count_caches' );
add_action( 'deleted_user', 'bp_core_clear_member_count_caches' );

/**
 * Clear the directory_pages cache when one of the pages is updated.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $post_id ID of the page that was saved.
 */
function bp_core_clear_directory_pages_cache_page_edit( $post_id = 0 ) {

	// Bail if BP is not defined here.
	if ( ! buddypress() ) {
		return;
	}

	// Bail if not on the root blog
	if ( ! bp_is_root_blog() ) {
		return;
	}

	$page_ids = bp_core_get_directory_page_ids( 'all' );

	// Bail if post ID is not a directory page
	if ( ! in_array( $post_id, $page_ids ) ) {
		return;
	}

	$cache_key = 'directory_pages';

	if ( is_multisite() ) {
		$cache_key = $cache_key . '_' . get_current_blog_id();
	}

	wp_cache_delete( $cache_key, 'bp_pages' );
}
add_action( 'save_post', 'bp_core_clear_directory_pages_cache_page_edit' );

/**
 * Clear the directory_pages cache when the bp-pages option is updated.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $option Option name.
 */
function bp_core_clear_directory_pages_cache_settings_edit( $option ) {
	if ( 'bp-pages' === $option ) {

		$cache_key = 'directory_pages';

		if ( is_multisite() ) {
			$cache_key = $cache_key . '_' . get_current_blog_id();
		}

		wp_cache_delete( $cache_key, 'bp_pages' );
	}
}
add_action( 'update_option', 'bp_core_clear_directory_pages_cache_settings_edit' );

/**
 * Clear the root_blog_options cache when any of its options are updated.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $option Option name.
 */
function bp_core_clear_root_options_cache( $option ) {
	foreach ( array( 'add_option', 'add_site_option', 'update_option', 'update_site_option' ) as $action ) {
		remove_action( $action, 'bp_core_clear_root_options_cache' );
	}

	// Surrounding code prevents infinite loops on WP < 4.4.
	$keys = array_keys( bp_get_default_options() );

	foreach ( array( 'add_option', 'add_site_option', 'update_option', 'update_site_option' ) as $action ) {
		add_action( $action, 'bp_core_clear_root_options_cache' );
	}

	$keys = array_merge(
		$keys,
		array(
			'registration',
			'avatar_default',
			'tags_blog_id',
			'sitewide_tags_blog',
			'registration',
			'fileupload_mask',
		)
	);

	if ( in_array( $option, $keys ) ) {
		wp_cache_delete( 'root_blog_options', 'bp' );
	}
}
add_action( 'update_option', 'bp_core_clear_root_options_cache' );
add_action( 'update_site_option', 'bp_core_clear_root_options_cache' );
add_action( 'add_option', 'bp_core_clear_root_options_cache' );
add_action( 'add_site_option', 'bp_core_clear_root_options_cache' );

/**
 * Determine which items from a list do not have cached values.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array  $item_ids    ID list.
 * @param string $cache_group The cache group to check against.
 * @return array
 */
function bp_get_non_cached_ids( $item_ids, $cache_group ) {
	$uncached = array();

	foreach ( $item_ids as $item_id ) {
		$item_id = (int) $item_id;
		if ( false === wp_cache_get( $item_id, $cache_group ) ) {
			$uncached[] = $item_id;
		}
	}

	return $uncached;
}

/**
 * Update the metadata cache for the specified objects.
 *
 * Based on WordPress's {@link update_meta_cache()}, this function primes the
 * cache with metadata related to a set of objects. This is typically done when
 * querying for a loop of objects; pre-fetching metadata for each queried
 * object can lead to dramatic performance improvements when using metadata
 * in the context of template loops.
 *
 * @since BuddyPress 1.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param array $args {
 *     Array of arguments.
 *     @type array|string $object_ids       List of object IDs to fetch metadata for.
 *                                          Accepts an array or a comma-separated list of numeric IDs.
 *     @type string       $object_type      The type of object, eg 'groups' or 'activity'.
 *     @type string       $meta_table       The name of the metadata table being queried.
 *     @type string       $object_column    Optional. The name of the database column where IDs
 *                                          (those provided by $object_ids) are found. Eg, 'group_id'
 *                                          for the groups metadata tables. Default: $object_type . '_id'.
 *     @type string       $cache_key_prefix Optional. The prefix to use when creating
 *                                          cache key names. Default: the value of $meta_table.
 * }
 * @return false|array Metadata cache for the specified objects, or false on failure.
 */
function bp_update_meta_cache( $args = array() ) {
	global $wpdb;

	$defaults = array(
		'object_ids'       => array(), // Comma-separated list or array of item ids.
		'object_type'      => '',      // Canonical component id: groups, members, etc.
		'cache_group'      => '',      // Cache group.
		'meta_table'       => '',      // Name of the table containing the metadata.
		'object_column'    => '',      // DB column for the object ids (group_id, etc).
		'cache_key_prefix' => '',       // Prefix to use when creating cache key names. Eg 'bp_groups_groupmeta'.
	);
	$r        = bp_parse_args( $args, $defaults );
	extract( $r );

	if ( empty( $object_ids ) || empty( $object_type ) || empty( $meta_table ) || empty( $cache_group ) ) {
		return false;
	}

	if ( empty( $cache_key_prefix ) ) {
		$cache_key_prefix = $meta_table;
	}

	if ( empty( $object_column ) ) {
		$object_column = $object_type . '_id';
	}

	if ( ! $cache_group ) {
		return false;
	}

	$object_ids   = wp_parse_id_list( $object_ids );
	$uncached_ids = bp_get_non_cached_ids( $object_ids, $cache_group );

	$cache = array();

	// Get meta info.
	if ( ! empty( $uncached_ids ) ) {
		$id_list   = join( ',', wp_parse_id_list( $uncached_ids ) );
		$meta_list = $wpdb->get_results( esc_sql( "SELECT {$object_column}, meta_key, meta_value FROM {$meta_table} WHERE {$object_column} IN ({$id_list})" ), ARRAY_A );

		if ( ! empty( $meta_list ) ) {
			foreach ( $meta_list as $metarow ) {
				$mpid = intval( $metarow[ $object_column ] );
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];

				// Force subkeys to be array type.
				if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
					$cache[ $mpid ] = array();
				}
				if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
					$cache[ $mpid ][ $mkey ] = array();
				}

				// Add a value to the current pid/key.
				$cache[ $mpid ][ $mkey ][] = $mval;
			}
		}

		foreach ( $uncached_ids as $uncached_id ) {
			// Cache empty values as well.
			if ( ! isset( $cache[ $uncached_id ] ) ) {
				$cache[ $uncached_id ] = array();
			}

			wp_cache_set( $uncached_id, $cache[ $uncached_id ], $cache_group );
		}
	}

	return $cache;
}

/**
 * Gets a value that has been cached using an incremented key.
 *
 * A utility function for use by query methods like BP_Activity_Activity::get().
 *
 * @since BuddyPress 2.7.0
 * @see bp_core_set_incremented_cache()
 *
 * @param string $key   Unique key for the query. Usually a SQL string.
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return array|bool False if no cached values are found, otherwise an array of IDs.
 */
function bp_core_get_incremented_cache( $key, $group ) {
	$cache_key = bp_core_get_incremented_cache_key( $key, $group );
	return wp_cache_get( $cache_key, $group );
}

/**
 * Caches a value using an incremented key.
 *
 * An "incremented key" is a cache key that is hashed with a unique incrementor,
 * allowing for bulk invalidation.
 *
 * Use this method when caching data that should be invalidated whenever any
 * object of a given type is created, updated, or deleted. This usually means
 * data related to object queries, which can only reliably cached until the
 * underlying set of objects has been modified. See, eg, BP_Activity_Activity::get().
 *
 * @since BuddyPress 2.7.0
 *
 * @param string $key   Unique key for the query. Usually a SQL string.
 * @param string $group Cache group. Eg 'bp_activity'.
 * @param array  $ids   Array of IDs.
 * @return bool
 */
function bp_core_set_incremented_cache( $key, $group, $ids ) {
	$cache_key = bp_core_get_incremented_cache_key( $key, $group );
	return wp_cache_set( $cache_key, $ids, $group );
}

/**
 * Delete a value that has been cached using an incremented key.
 *
 * A utility function for use by query methods like BP_Activity_Activity::get().
 *
 * @since BuddyPress 3.0.0
 * @see bp_core_set_incremented_cache()
 *
 * @param string $key   Unique key for the query. Usually a SQL string.
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return bool True on successful removal, false on failure.
 */
function bp_core_delete_incremented_cache( $key, $group ) {
	$cache_key = bp_core_get_incremented_cache_key( $key, $group );
	return wp_cache_delete( $cache_key, $group );
}

/**
 * Gets the key to be used when caching a value using an incremented cache key.
 *
 * The $key is hashed with a component-specific incrementor, which is used to
 * invalidate multiple caches at once.

 * @since BuddyPress 2.7.0
 *
 * @param string $key   Unique key for the query. Usually a SQL string.
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return string
 */
function bp_core_get_incremented_cache_key( $key, $group ) {
	global $wpdb;
	$incrementor = bp_core_get_incrementor( $group );

	// Removes the placeholder escape strings from a query.
	$key       = $wpdb->remove_placeholder_escape( $key );
	$cache_key = md5( $key . $incrementor );

	return $cache_key;
}

/**
 * Gets a group-specific cache incrementor.
 *
 * The incrementor is paired with query identifiers (like SQL strings) to
 * create cache keys that can be invalidated en masse.
 *
 * If an incrementor does not yet exist for the given `$group`, one will
 * be created.
 *
 * @since BuddyPress 2.7.0
 *
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return string
 */
function bp_core_get_incrementor( $group ) {
	$incrementor = wp_cache_get( 'incrementor', $group );
	if ( ! $incrementor ) {
		$incrementor = microtime();
		wp_cache_set( 'incrementor', $incrementor, $group );
	}

	return $incrementor;
}

/**
 * Reset a group-specific cache incrementor.
 *
 * Call this function when all incrementor-based caches associated with a given
 * cache group should be invalidated.
 *
 * @since BuddyPress 2.7.0
 *
 * @param string $group Cache group. Eg 'bp_activity'.
 * @return bool True on success, false on failure.
 */
function bp_core_reset_incrementor( $group ) {
	return wp_cache_delete( 'incrementor', $group );
}

/**
 * Resets all incremented bp_invitations caches.
 *
 * @since BuddyBoss 1.3.5
 * @since BuddyPress 5.0.0
 */
function bp_invitations_reset_cache_incrementor() {
	bp_core_reset_incrementor( 'bp_invitations' );
}
add_action( 'bp_invitation_after_save', 'bp_invitations_reset_cache_incrementor' );
add_action( 'bp_invitation_after_delete', 'bp_invitations_reset_cache_incrementor' );

/**
 * Clear bbpress subscriptions cache.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param int $subscription_id The subscription ID.
 *
 * @return void
 */
function bb_subscriptions_clear_bbpress_cache( $subscription_id ) {
	if ( $subscription_id ) {
		// Delete the bbpress subscription cache.
		$subscription = bb_subscriptions_get_subscription( $subscription_id );
		if ( ! empty( $subscription->item_id ) ) {
			if ( 'forum' === $subscription->type ) {
				wp_cache_delete( 'bbp_get_forum_subscribers_' . $subscription->item_id, 'bbpress_users' );
			} elseif ( 'topic' === $subscription->type ) {
				wp_cache_delete( 'bbp_get_topic_subscribers_' . $subscription->item_id, 'bbpress_users' );
			}
		}
	}
}

/**
 * Reset incremental cache for add/delete subscription.
 *
 * @since BuddyBoss 2.2.6
 *
 * @return void
 */
function bb_subscriptions_reset_cache_incrementor() {
	bp_core_reset_incrementor( 'bb_subscriptions' );
}

add_action( 'bb_create_subscription', 'bb_subscriptions_reset_cache_incrementor' );
add_action( 'bb_delete_subscription', 'bb_subscriptions_reset_cache_incrementor' );

/**
 * Clear a cached subscription item when that item is updated.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param BB_Subscriptions $subscription Subscription object.
 */
function bb_subscriptions_clear_cache_for_subscription( $subscription ) {
	if ( ! empty( $subscription->id ) ) {
		wp_cache_delete( $subscription->id, 'bb_subscriptions' );

		// Delete the existing subscription cache.
		bb_subscriptions_clear_bbpress_cache( $subscription->id );
	}
}

add_action( 'bb_subscriptions_after_save', 'bb_subscriptions_clear_cache_for_subscription' );
add_action( 'bb_subscriptions_after_delete_subscription', 'bb_subscriptions_clear_cache_for_subscription' );

/**
 * Clear cache while updating the status of the subscriptions.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param string $type    Type subscription item.
 * @param int    $item_id The subscription item ID.
 *
 * @return void
 */
function bb_subscriptions_clear_cache_after_update_status( $type, $item_id ) {

	if ( empty( $type ) || empty( $item_id ) ) {
		return;
	}

	$subscription_ids = bb_get_subscriptions(
		array(
			'type'    => $type,
			'item_id' => $item_id,
			'user_id' => false,
			'fields'  => 'id',
			'status'  => null,
		),
		true
	);

	if ( ! empty( $subscription_ids['subscriptions'] ) ) {
		bp_core_reset_incrementor( 'bb_subscriptions' );

		foreach ( $subscription_ids['subscriptions'] as $id ) {
			wp_cache_delete( $id, 'bb_subscriptions' );

			// Delete the existing subscription cache.
			bb_subscriptions_clear_bbpress_cache( $id );
		}
	}
}

add_action( 'bb_subscriptions_after_update_subscription_status', 'bb_subscriptions_clear_cache_after_update_status', 10, 2 );


/**
 * Clear cache while updating the secondary item ID of the subscriptions.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param array $r Subscription arguments.
 *
 * @return void
 */
function bb_subscriptions_clear_cache_after_update_secondary_item_id( $r ) {

	if ( empty( $r ) ) {
		return;
	}

	$subscription_ids = bb_get_subscriptions(
		array(
			'type'    => $r['type'],
			'item_id' => $r['item_id'],
			'fields'  => 'id',
		),
		true
	);

	if ( ! empty( $subscription_ids['subscriptions'] ) ) {
		bp_core_reset_incrementor( 'bb_subscriptions' );

		foreach ( $subscription_ids['subscriptions'] as $id ) {
			wp_cache_delete( $id, 'bb_subscriptions' );

			// Delete the existing subscription cache.
			bb_subscriptions_clear_bbpress_cache( $id );
		}
	}
}

add_action( 'bb_subscriptions_after_update_secondary_item_id', 'bb_subscriptions_clear_cache_after_update_secondary_item_id', 10 );

/**
 * Clear cache when add/remove user item reaction.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param int $user_reaction_id User reaction id.
 *
 * @return void
 */
function bb_reaction_clear_user_item_cache( $user_reaction_id ) {
	bp_core_reset_incrementor( 'bb_reactions' );
	if ( ! empty( $user_reaction_id ) ) {
		wp_cache_delete( $user_reaction_id, 'bb_reactions' );
	}
}

add_action( 'bb_reaction_after_add_user_item_reaction', 'bb_reaction_clear_user_item_cache', 10, 1 );
add_action( 'bb_reaction_after_remove_user_item_reaction', 'bb_reaction_clear_user_item_cache', 10, 1 );

/**
 * Clear cache when update user reaction.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param int|false $deleted   The number of rows deleted, or false on error.
 * @param array     $r         Args of user item reactions.
 * @param object    $reactions Reaction data.
 *
 * @return void
 */
function bb_reaction_clear_remove_user_item_cache( $deleted, $r, $reactions ) {
	bp_core_reset_incrementor( 'bb_reactions' );
	if ( ! empty( $reactions ) ) {
		foreach ( $reactions as $id ) {
			wp_cache_delete( $id, 'bb_reactions' );
		}
	}
}

add_action( 'bb_reaction_after_remove_user_item_reactions', 'bb_reaction_clear_remove_user_item_cache', 10, 3 );

/**
 * Clear cache when reaction data updated.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param int $reaction_data_id Reaction data id.
 *
 * @return void
 */
function bb_reaction_clear_reactions_data_cache( $reaction_data_id ) {
	bp_core_reset_incrementor( 'bb_reaction_data' );
	if ( ! empty( $reaction_data_id ) ) {
		wp_cache_delete( $reaction_data_id, 'bb_reaction_data' );
	}
}

add_action( 'bb_reaction_after_add_reactions_data', 'bb_reaction_clear_reactions_data_cache', 10, 1 );

/**
 * Clear cache when reaction settings updated.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param string $option    Name of the updated option.
 * @param mixed  $old_value The old option value.
 * @param mixed  $value     The new option value.
 *
 * @return void
 */
function bb_reaction_clear_reactions_cache_on_update_settings( $old_value = '', $value = '', $option = '' ) {
	if (
		function_exists( 'wp_cache_flush_group' ) &&
		function_exists( 'wp_cache_supports' ) &&
		wp_cache_supports( 'flush_group' )
	) {
		bp_core_reset_incrementor( 'bb_reactions' );
		bp_core_reset_incrementor( 'bb_reaction_data' );

		wp_cache_flush_group( 'bb_reactions' );
		wp_cache_flush_group( 'bb_reaction_data' );
	} else {
		wp_cache_flush();
	}
}

add_action( 'update_option_bb_all_reactions', 'bb_reaction_clear_reactions_cache_on_update_settings', 10, 3 );
add_action( 'update_option_bb_reaction_mode', 'bb_reaction_clear_reactions_cache_on_update_settings', 10, 3 );

/**
 * Clear cache when emotion deleted.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int    $postid Post ID.
 * @param object $post   Post object.
 *
 * @return void
 */
function bb_reaction_clear_reactions_cache_on_delete_emotion( $postid, $post ) {
	if (
		! empty( $post ) &&
		! empty( $post->post_type ) &&
		'bb_reaction' === $post->post_type
	) {
		bb_reaction_clear_reactions_cache_on_update_settings();
	}
}

add_action( 'deleted_post', 'bb_reaction_clear_reactions_cache_on_delete_emotion', 10, 2 );

/**
 * Common function to clear topic related caches.
 *
 * @since BuddyBoss 2.8.80
 *
 * @param object|int $topic_data Topic relationship object or topic ID.
 * @param array      $args       Additional arguments.
 */
function bb_clear_topic_related_caches( $topic_data, $args = array() ) {
	if ( empty( $topic_data ) ) {
		return;
	}

	// Reset the incrementor to clear all cached queries.
	bp_core_reset_incrementor( 'bb_topics' );

	// If topic_data is an ID, get the full topic data.
	if ( is_numeric( $topic_data ) ) {
		$topic_data = bb_topics_manager_instance()->bb_get_topic( array( 'topic_id' => $topic_data ) );
	}

	// Clear individual topic relationship cache.
	if ( ! empty( $topic_data->id ) && ! empty( $topic_data->item_id ) && ! empty( $topic_data->item_type ) ) {
		$relationship_cache_key = 'bb_topic_relationship_' . $topic_data->id . '_' . $topic_data->item_id . '_' . $topic_data->item_type;
		wp_cache_delete( $relationship_cache_key, 'bb_topics' );
	}

	// Clear topic caches.
	if ( ! empty( $topic_data->name ) ) {
		wp_cache_delete( 'bb_topic_name_' . $topic_data->name, 'bb_topics' );
	}
	if ( ! empty( $topic_data->slug ) ) {
		wp_cache_delete( 'bb_topic_slug_' . $topic_data->slug, 'bb_topics' );
	}

	// Clear topic caches from args if provided.
	if ( ! empty( $args ) ) {
		if ( ! empty( $args['id'] ) ) {
			wp_cache_delete( 'bb_topic_id_' . $args['id'], 'bb_topics' );
		}
		if ( ! empty( $args['name'] ) ) {
			wp_cache_delete( 'bb_topic_name_' . $args['name'], 'bb_topics' );
		}
		if ( ! empty( $args['slug'] ) ) {
			wp_cache_delete( 'bb_topic_slug_' . $args['slug'], 'bb_topics' );
		}
	}

	// Clear activity topics cache for list of topics.
	if (
		function_exists( 'wp_cache_flush_group' ) &&
		function_exists( 'wp_cache_supports' ) &&
		wp_cache_supports( 'flush_group' )
	) {
		wp_cache_flush_group( 'bb_activity_topics' );
	} else {
		wp_cache_flush();
	}
}

/**
 * Reset cache when a topic is added.
 *
 * @since BuddyBoss 2.8.80
 *
 * @param object|int $topic_relationship Topic relationship object or topic ID.
 * @param array      $r                  Additional arguments.
 */
function bb_topic_added_cache_reset( $topic_relationship, $r ) {
	bb_clear_topic_related_caches( $topic_relationship, $r );
}

add_action( 'bb_topic_after_added', 'bb_topic_added_cache_reset', 10, 2 );
add_action( 'bb_topic_after_updated', 'bb_topic_added_cache_reset', 10, 2 );
add_action( 'bb_topic_relationship_after_updated', 'bb_topic_added_cache_reset', 10, 2 );

/**
 * Reset cache when a topic is deleted.
 *
 * @since BuddyBoss 2.8.80
 *
 * @param array $relationships_ids The IDs of the topic relationships that were deleted.
 * @param int   $topic_id          The ID of the topic that was deleted.
 */
function bb_topic_deleted_cache_reset( $relationships_ids, $topic_id ) {
	if ( empty( $relationships_ids ) || empty( $topic_id ) ) {
		return;
	}

	// Reset the incrementor to clear all cached queries.
	bp_core_reset_incrementor( 'bb_topics' );

	// Clear individual topic relationship caches.
	foreach ( $relationships_ids as $relationship_id ) {
		$relationship = bb_topics_manager_instance()->bb_get_topic( array( 'topic_id' => $relationship_id ) );
		if ( ! empty( $relationship ) ) {
			$relationship_cache_key = 'bb_topic_relationship_' . $relationship_id . '_' . $relationship->item_id . '_' . $relationship->item_type;
			wp_cache_delete( $relationship_cache_key, 'bb_topics' );
		}
	}

	// Clear topic caches.
	wp_cache_delete( 'bb_topic_id_' . $topic_id, 'bb_topics' );

	// Clear activity topics cache for list of topics.
	if (
		function_exists( 'wp_cache_flush_group' ) &&
		function_exists( 'wp_cache_supports' ) &&
		wp_cache_supports( 'flush_group' )
	) {
		wp_cache_flush_group( 'bb_activity_topics' );
	} else {
		wp_cache_flush();
	}
}

add_action( 'bb_topic_deleted', 'bb_topic_deleted_cache_reset', 10, 2 );


/**
 * Reset cache when a topic relationship is updated.
 *
 * @since BuddyBoss 2.8.80
 *
 * @param int $relationship_id The ID of the updated relationship.
 */
function bb_activity_topic_relationship_after_update_cache_reset( $relationship_id ) {
	bp_core_reset_incrementor( 'bb_activity_topics' );
	if ( ! empty( $relationship_id ) ) {
		wp_cache_delete( $relationship_id, 'bb_activity_topics' );
	}
}

add_action( 'bb_activity_topic_relationship_after_update', 'bb_activity_topic_relationship_after_update_cache_reset', 10, 1 );

/**
 * Clear topic redirect cache for a specific slug.
 *
 * @since BuddyBoss 2.8.80
 *
 * @param string|array $old_slug_or_args The old topic slug to clear cache for, or array of arguments.
 * @param string       $item_type The item type (optional, defaults to 'activity').
 * @param int          $item_id The item ID (optional, defaults to 0 for activity).
 */
function bb_clear_topic_redirect_cache( $old_slug_or_args, $item_type = 'activity', $item_id = 0 ) {
	// Handle array parameter (from action hooks).
	if ( is_array( $old_slug_or_args ) ) {
		$old_slug  = isset( $old_slug_or_args['old_topic_slug'] ) ? $old_slug_or_args['old_topic_slug'] : '';
		$item_type = isset( $old_slug_or_args['item_type'] ) ? $old_slug_or_args['item_type'] : 'activity';
		$item_id   = isset( $old_slug_or_args['item_id'] ) ? $old_slug_or_args['item_id'] : 0;
	} else {
		// Handle individual parameters.
		$old_slug = $old_slug_or_args;
	}

	if ( empty( $old_slug ) ) {
		return;
	}

	$old_slug = sanitize_title( $old_slug );

	// Clear cache for the specific slug.
	$cache_key = 'bb_topic_redirect_' . $old_slug . '_' . $item_type . '_' . $item_id;
	wp_cache_delete( $cache_key, 'bb_topics' );

	// Also clear cache for potential recursive redirects.
	// Get all slugs that might redirect to this one.
	global $wpdb;

	if ( function_exists( 'bb_topics_manager_instance' ) && bb_topics_manager_instance() ) {
		$topic_history_table = bb_topics_manager_instance()->topic_history_table;
		$sql                 = $wpdb->prepare(
			"SELECT DISTINCT old_topic_slug FROM {$topic_history_table} WHERE new_topic_slug = %s",
			$old_slug
		);
		$related_slugs       = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $related_slugs ) ) {
			foreach ( $related_slugs as $related_slug ) {
				$related_cache_key = 'bb_topic_redirect_' . $related_slug . '_' . $item_type . '_' . $item_id;
				wp_cache_delete( $related_cache_key, 'bb_topics' );
			}
		}
	}

	/**
	 * Fires after topic redirect cache is cleared.
	 *
	 * @since BuddyBoss 2.8.80
	 *
	 * @param string $old_slug The old topic slug.
	 * @param string $item_type The item type.
	 * @param int    $item_id The item ID.
	 */
	do_action( 'bb_topic_redirect_cache_cleared', $old_slug, $item_type, $item_id );
}

add_action( 'bb_topic_history_after_added', 'bb_clear_topic_redirect_cache', 10, 1 );

/**
 * Clear activity results cache when a topic is migrated.
 *
 * @since BuddyBoss 2.8.80
 *
 * @param object $topic                 The topic object.
 * @param int    $old_topic_id          The ID of the old topic.
 * @param int    $new_topic_id          The ID of the new topic.
 * @param int    $item_id               The ID of the item.
 * @param string $item_type             The type of item.
 * @param array  $migrated_activity_ids The IDs of the activities.
 */
function bb_clear_activity_results_cache( $topic, $old_topic_id, $new_topic_id, $item_id, $item_type, $migrated_activity_ids ) {
	if ( empty( $topic ) || empty( $item_type ) ) {
		return;
	}

	// Clear activity cache for filtered results.
	if (
		function_exists( 'wp_cache_flush_group' ) &&
		function_exists( 'wp_cache_supports' ) &&
		wp_cache_supports( 'flush_group' )
	) {
		wp_cache_flush_group( 'bp_activity' );
	} else {
		wp_cache_flush();
	}
}

add_action( 'bb_after_migrate_topic', 'bb_clear_activity_results_cache', 10, 6 );
