<?php
/**
 * BuddyBoss Bookmarks — public API and type registry.
 *
 * A generic bookmarking layer. Consumers register a bookmark type (see
 * BB_Bookmark_Type) and then use the functions here; nothing in this file knows
 * what a "post" or an "activity" is.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get all registered bookmark types, or one of them.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type Optional. A single type slug.
 *
 * @return array All types, or the named type's data (empty array when unknown).
 */
function bb_bookmark_register_types( $type = '' ) {

	/**
	 * Filters the registered bookmark types.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $types Types, keyed by slug.
	 */
	$types = apply_filters( 'bb_bookmark_register_types', array() );

	if ( ! empty( $type ) ) {
		return isset( $types[ $type ] ) ? $types[ $type ] : array();
	}

	return $types;
}

/**
 * Add a bookmark, or return the existing one.
 *
 * Upsert on (blog_id, user_id, type, item_id): calling this twice with the same
 * arguments returns the same row ID and never creates a duplicate. The table's
 * UNIQUE key enforces that even under concurrency.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of arguments.
 *
 *     @type string $type          Required. Bookmark type slug.
 *     @type int    $item_id       Required. Item ID.
 *     @type int    $user_id       Optional. Defaults to the current user.
 *     @type int    $blog_id       Optional. Defaults to the current site.
 *     @type int    $status        Optional. Default 1.
 *     @type string $date_recorded Optional. MySQL datetime. Defaults to now.
 * }
 *
 * @return int|WP_Error Bookmark row ID, or WP_Error on failure.
 */
function bb_bookmark_add( $args ) {
	$r = bp_parse_args(
		$args,
		array(
			'type'          => '',
			'item_id'       => 0,
			'user_id'       => get_current_user_id(),
			'blog_id'       => get_current_blog_id(),
			'status'        => 1,
			'date_recorded' => current_time( 'mysql' ),
		),
		'bb_bookmark_add'
	);

	if ( empty( $r['type'] ) ) {
		return new WP_Error( 'bb_bookmark_no_type', __( 'A bookmark type is required.', 'buddyboss' ), array( 'status' => 400 ) );
	}

	if ( empty( $r['item_id'] ) ) {
		return new WP_Error( 'bb_bookmark_no_item', __( 'An item ID is required to create a bookmark.', 'buddyboss' ), array( 'status' => 400 ) );
	}

	if ( empty( $r['user_id'] ) ) {
		return new WP_Error( 'bb_bookmark_no_user', __( 'You must be logged in to bookmark.', 'buddyboss' ), array( 'status' => 401 ) );
	}

	$existing = bb_bookmark_get_by_item( $r['type'], (int) $r['item_id'], (int) $r['user_id'], (int) $r['blog_id'] );

	$obj                = new stdClass();
	$obj->id            = ! empty( $existing->id ) ? (int) $existing->id : 0;
	$obj->blog_id       = (int) $r['blog_id'];
	$obj->user_id       = (int) $r['user_id'];
	$obj->type          = $r['type'];
	$obj->item_id       = (int) $r['item_id'];
	$obj->status        = (int) $r['status'];
	$obj->date_recorded = $r['date_recorded'];

	// An identical, already-active row: nothing to do. Return it as-is.
	if ( ! empty( $existing->id ) && (int) $existing->status === (int) $r['status'] ) {
		return (int) $existing->id;
	}

	$bookmark_id = BB_Bookmarks::add( $obj );

	if ( empty( $bookmark_id ) ) {
		// The insert may have lost a race against a concurrent request for the same
		// (blog_id, user_id, type, item_id) — the table's UNIQUE key would have
		// rejected our insert while the winner's row now exists. Look again: if a row
		// exists now, the caller's intent (a bookmark exists) is satisfied either way.
		$existing = bb_bookmark_get_by_item( $r['type'], (int) $r['item_id'], (int) $r['user_id'], (int) $r['blog_id'] );

		if ( ! empty( $existing->id ) ) {
			return (int) $existing->id;
		}

		return new WP_Error( 'bb_bookmark_not_created', __( 'The bookmark could not be saved.', 'buddyboss' ), array( 'status' => 500 ) );
	}

	/**
	 * Fires after a bookmark is added.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $type        Bookmark type.
	 * @param int    $item_id     Item ID.
	 * @param int    $user_id     User ID.
	 * @param int    $bookmark_id Bookmark row ID.
	 */
	do_action( 'bb_bookmark_added', $r['type'], (int) $r['item_id'], (int) $r['user_id'], (int) $bookmark_id );

	return (int) $bookmark_id;
}

/**
 * Delete a bookmark by row ID.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $bookmark_id Bookmark row ID.
 *
 * @return bool
 */
function bb_bookmark_delete( $bookmark_id ) {
	$bookmark_id = (int) $bookmark_id;

	if ( empty( $bookmark_id ) ) {
		return false;
	}

	$bookmark = BB_Bookmarks::get_single_bookmark( $bookmark_id );

	if ( empty( $bookmark ) ) {
		return false;
	}

	$row     = new stdClass();
	$row->id = $bookmark_id;

	if ( ! BB_Bookmarks::delete( $row ) ) {
		return false;
	}

	/**
	 * Fires after a bookmark is removed.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $type    Bookmark type.
	 * @param int    $item_id Item ID.
	 * @param int    $user_id User ID.
	 */
	do_action( 'bb_bookmark_removed', $bookmark->type, (int) $bookmark->item_id, (int) $bookmark->user_id );

	return true;
}

/**
 * Query bookmarks.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args         Query args. See BB_Bookmarks::get().
 * @param bool  $bypass_cache Bypass the object cache when true.
 *
 * @return array `array( 'bookmarks' => array, 'total' => int )`
 */
function bb_bookmark_query( $args = array(), $bypass_cache = false ) {
	if ( $bypass_cache ) {
		$args['cache'] = false;
	}

	return BB_Bookmarks::get( $args );
}

/**
 * Get a user's bookmarks.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args         Query args. `user_id` defaults to the current user.
 * @param bool  $bypass_cache Bypass the object cache when true.
 *
 * @return array `array( 'bookmarks' => array, 'total' => int )`
 */
function bb_bookmark_get_user_items( $args = array(), $bypass_cache = false ) {
	$args = bp_parse_args(
		$args,
		array( 'user_id' => get_current_user_id() ),
		'bb_bookmark_get_user_items'
	);

	return bb_bookmark_query( $args, $bypass_cache );
}

/**
 * Get a user's bookmark row for one item.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type    Bookmark type.
 * @param int    $item_id Item ID.
 * @param int    $user_id Optional. Defaults to the current user.
 * @param int    $blog_id Optional. Defaults to the current site.
 *
 * @return object|null Bookmark object, or null when not bookmarked.
 */
function bb_bookmark_get_by_item( $type, $item_id, $user_id = 0, $blog_id = 0 ) {
	$item_id = (int) $item_id;
	$user_id = ! empty( $user_id ) ? (int) $user_id : get_current_user_id();
	$blog_id = ! empty( $blog_id ) ? (int) $blog_id : get_current_blog_id();

	if ( empty( $type ) || empty( $item_id ) || empty( $user_id ) ) {
		return null;
	}

	$result = BB_Bookmarks::get(
		array(
			'type'    => $type,
			'user_id' => $user_id,
			'item_id' => $item_id,
			'blog_id' => $blog_id,
			'status'  => null,
			'count'   => false,
			'cache'   => false,
		)
	);

	return ! empty( $result['bookmarks'] ) ? current( $result['bookmarks'] ) : null;
}

/**
 * Delete every bookmark for an item, across all users.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type    Bookmark type.
 * @param int    $item_id Item ID.
 *
 * @return int Number of rows deleted.
 */
function bb_bookmark_delete_by_item( $type, $item_id ) {
	$item_id = (int) $item_id;

	if ( empty( $type ) || empty( $item_id ) ) {
		return 0;
	}

	$result = BB_Bookmarks::get(
		array(
			'type'    => $type,
			'item_id' => $item_id,
			'user_id' => 0,
			'status'  => null,
			'fields'  => 'id',
			'count'   => false,
			'cache'   => false,
		)
	);

	$deleted = 0;

	foreach ( (array) $result['bookmarks'] as $bookmark_id ) {
		if ( bb_bookmark_delete( $bookmark_id ) ) {
			++$deleted;
		}
	}

	return $deleted;
}

/**
 * Whether a user has bookmarked an item.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type    Bookmark type.
 * @param int    $item_id Item ID.
 * @param int    $user_id Optional. Defaults to the current user.
 *
 * @return bool
 */
function bb_bookmark_is_bookmarked( $type, $item_id, $user_id = 0 ) {
	$item_id = (int) $item_id;
	$user_id = ! empty( $user_id ) ? (int) $user_id : get_current_user_id();

	if ( empty( $type ) || empty( $item_id ) || empty( $user_id ) ) {
		return false;
	}

	$key    = 'is_bookmarked:' . get_current_blog_id() . ':' . $user_id . ':' . $type . ':' . $item_id;
	$cached = bp_core_get_incremented_cache( $key, BB_Bookmarks::CACHE_GROUP );

	if ( false !== $cached ) {
		return ! empty( current( (array) $cached ) );
	}

	$result = BB_Bookmarks::get(
		array(
			'type'    => $type,
			'user_id' => $user_id,
			'item_id' => $item_id,
			'status'  => 1,
			'fields'  => 'id',
			'count'   => false,
		)
	);

	$bookmarked = ! empty( $result['bookmarks'] );

	bp_core_set_incremented_cache( $key, BB_Bookmarks::CACHE_GROUP, array( $bookmarked ? 1 : 0 ) );

	return $bookmarked;
}

/**
 * Toggle a user's bookmark for an item.
 *
 * Returns what the store actually did — never a hardcoded success. A caller
 * (and therefore the browser) must be able to tell a failed write from a
 * successful one.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type    Bookmark type.
 * @param int    $item_id Item ID.
 * @param int    $user_id Optional. Defaults to the current user.
 *
 * @return bool|WP_Error True when now bookmarked, false when now removed,
 *                       WP_Error when the operation failed.
 */
function bb_bookmark_toggle( $type, $item_id, $user_id = 0 ) {
	$item_id = (int) $item_id;
	$user_id = ! empty( $user_id ) ? (int) $user_id : get_current_user_id();

	if ( empty( $user_id ) ) {
		return new WP_Error( 'bb_bookmark_no_user', __( 'You must be logged in to bookmark.', 'buddyboss' ), array( 'status' => 401 ) );
	}

	if ( empty( $type ) || empty( bb_bookmark_register_types( $type ) ) ) {
		return new WP_Error( 'bb_bookmark_invalid_type', __( 'This item cannot be bookmarked.', 'buddyboss' ), array( 'status' => 400 ) );
	}

	if ( empty( $item_id ) ) {
		return new WP_Error( 'bb_bookmark_no_item', __( 'An item ID is required to bookmark.', 'buddyboss' ), array( 'status' => 400 ) );
	}

	$existing = bb_bookmark_get_by_item( $type, $item_id, $user_id );

	// Removal path: deliberately has NO availability check. A user must always
	// be able to remove a bookmark, even if the item has since been unpublished
	// or deleted — otherwise the bookmark is stuck in their list forever.
	if ( ! empty( $existing->id ) && 1 === (int) $existing->status ) {
		if ( ! bb_bookmark_delete( (int) $existing->id ) ) {
			return new WP_Error( 'bb_bookmark_not_removed', __( 'The bookmark could not be removed.', 'buddyboss' ), array( 'status' => 500 ) );
		}

		return false;
	}

	$bookmark_id = bb_bookmark_add(
		array(
			'type'    => $type,
			'item_id' => $item_id,
			'user_id' => $user_id,
		)
	);

	if ( is_wp_error( $bookmark_id ) ) {
		return $bookmark_id;
	}

	return true;
}

/**
 * How many users have bookmarked an item.
 *
 * Derived from the table with a cached COUNT(*) — never a stored counter. A
 * read-modify-write counter drifts under concurrency and has no path back to
 * the truth; a derived count cannot drift.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type    Bookmark type.
 * @param int    $item_id Item ID.
 *
 * @return int
 */
function bb_bookmark_get_count( $type, $item_id ) {
	$item_id = (int) $item_id;

	if ( empty( $type ) || empty( $item_id ) ) {
		return 0;
	}

	$result = BB_Bookmarks::get(
		array(
			'type'       => $type,
			'item_id'    => $item_id,
			'user_id'    => 0,
			'status'     => 1,
			'count_only' => true,
		)
	);

	return ! empty( $result['total'] ) ? (int) $result['total'] : 0;
}

/**
 * Warm the bookmark cache for a page of items in one query.
 *
 * Call this before a loop that will ask `bb_bookmark_is_bookmarked()` per item —
 * otherwise a 20-card grid costs 20 queries.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type     Bookmark type.
 * @param array  $item_ids Item IDs about to be rendered.
 * @param int    $user_id  Optional. Defaults to the current user.
 *
 * @return void
 */
function bb_bookmark_prime_cache( $type, $item_ids, $user_id = 0 ) {
	$item_ids = wp_parse_id_list( $item_ids );
	$user_id  = ! empty( $user_id ) ? (int) $user_id : get_current_user_id();

	if ( empty( $type ) || empty( $item_ids ) || empty( $user_id ) ) {
		return;
	}

	// One query for the user's bookmarked item IDs among $item_ids.
	$result = BB_Bookmarks::get(
		array(
			'type'          => $type,
			'user_id'       => $user_id,
			'include_items' => $item_ids,
			'status'        => 1,
			'fields'        => 'item_id',
			'count'         => false,
		)
	);

	$bookmarked_item_ids = array_map( 'intval', (array) $result['bookmarks'] );

	// Write a per-item cache entry for every id — including the negatives.
	// Without the negative entries, an unbookmarked card still falls through to
	// its own query in bb_bookmark_is_bookmarked(). Each entry is read back by
	// bb_bookmark_is_bookmarked() using the identical key built there.
	foreach ( $item_ids as $item_id ) {
		$bookmarked = in_array( (int) $item_id, $bookmarked_item_ids, true );
		$key        = 'is_bookmarked:' . get_current_blog_id() . ':' . $user_id . ':' . $type . ':' . $item_id;

		bp_core_set_incremented_cache( $key, BB_Bookmarks::CACHE_GROUP, array( $bookmarked ? 1 : 0 ) );
	}
}

/**
 * Purge an item's bookmarks when the underlying post is deleted.
 *
 * Relies on the type slug convention: for post-backed bookmark types the slug
 * IS the WordPress post type. A component type (e.g. `activity`) never matches
 * here and registers its own cleanup.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $post_id Post being deleted.
 *
 * @return void
 */
function bb_bookmark_delete_post_items( $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( empty( $post_type ) || empty( bb_bookmark_register_types( $post_type ) ) ) {
		return;
	}

	bb_bookmark_delete_by_item( $post_type, (int) $post_id );
}
add_action( 'before_delete_post', 'bb_bookmark_delete_post_items' );

/**
 * Purge a user's bookmarks when the user is deleted.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $user_id User being deleted.
 *
 * @return void
 */
function bb_bookmark_delete_user_items( $user_id ) {
	global $wpdb;

	$user_id = (int) $user_id;

	if ( empty( $user_id ) ) {
		return;
	}

	$table = BB_Bookmarks::get_bookmark_tbl();

	// Look up the affected row IDs and delete them one at a time via
	// bb_bookmark_delete() so `bb_bookmark_removed` fires per row — a bulk
	// `$wpdb->delete()` here would silently skip that action (see PROD-9206).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
	$bookmark_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id = %d", $user_id ) );

	foreach ( $bookmark_ids as $bookmark_id ) {
		bb_bookmark_delete( (int) $bookmark_id );
	}

	BB_Bookmarks::purge_cache();
}
add_action( 'deleted_user', 'bb_bookmark_delete_user_items' );
add_action( 'wpmu_delete_user', 'bb_bookmark_delete_user_items' );

/**
 * Purge a site's bookmarks when the site is deleted (multisite).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $blog_id Site being deleted.
 *
 * @return void
 */
function bb_bookmark_delete_blog_items( $blog_id ) {
	global $wpdb;

	$blog_id = (int) $blog_id;

	if ( empty( $blog_id ) ) {
		return;
	}

	$table = BB_Bookmarks::get_bookmark_tbl();

	// Look up the affected row IDs and delete them one at a time via
	// bb_bookmark_delete() so `bb_bookmark_removed` fires per row — a bulk
	// `$wpdb->delete()` here would silently skip that action (see PROD-9206).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
	$bookmark_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE blog_id = %d", $blog_id ) );

	foreach ( $bookmark_ids as $bookmark_id ) {
		bb_bookmark_delete( (int) $bookmark_id );
	}

	BB_Bookmarks::purge_cache();
}
add_action( 'delete_blog', 'bb_bookmark_delete_blog_items' );
