<?php
/**
 * BuddyBoss Bookmarks store.
 *
 * Generic bookmark storage: CRUD, a query builder and object caching over the
 * `bb_bookmark` table. Knows nothing about what is being bookmarked — that is
 * the bookmark type's job (see BB_Bookmark_Type).
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bookmarks store.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Bookmarks {

	/**
	 * Object cache group.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'bb_bookmarks';

	/**
	 * Get the bookmarks table name.
	 *
	 * Network-shared: `bp_core_get_table_prefix()` returns the base prefix on
	 * multisite, and the table carries a `blog_id` column, matching the App
	 * plugin's own bookmarks table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string
	 */
	public static function get_bookmark_tbl() {
		return bp_core_get_table_prefix() . 'bb_bookmark';
	}

	/**
	 * Supported table columns.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string[]
	 */
	public static function get_tbl_columns() {
		return array( 'id', 'blog_id', 'user_id', 'type', 'item_id', 'status', 'date_recorded' );
	}

	/**
	 * Validate a column name against the schema.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $column Column name.
	 *
	 * @return string The column when valid, otherwise 'all'.
	 */
	public static function validate_column( $column ) {
		if ( 'all' === $column || in_array( $column, self::get_tbl_columns(), true ) ) {
			return $column;
		}

		return 'all';
	}

	/**
	 * Insert or update a bookmark row.
	 *
	 * When `$bookmark_obj->id` is set the row is updated; otherwise it is
	 * inserted. The table's UNIQUE key on (blog_id, user_id, type, item_id)
	 * means a duplicate insert fails rather than creating a second row —
	 * callers should look the row up first (see `bb_bookmark_add()`).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param object $bookmark_obj Bookmark object.
	 *
	 * @return int|false Row ID on success, false on failure.
	 */
	public static function add( $bookmark_obj ) {
		global $wpdb;

		$table = self::get_bookmark_tbl();

		if ( empty( $bookmark_obj->type ) || empty( $bookmark_obj->item_id ) || empty( $bookmark_obj->user_id ) ) {
			return false;
		}

		/**
		 * Fires before a bookmark is saved.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param object $bookmark_obj Bookmark object, passed by reference.
		 */
		do_action_ref_array( 'bb_bookmarks_before_save', array( &$bookmark_obj ) );

		$data = array(
			'blog_id'       => ! empty( $bookmark_obj->blog_id ) ? (int) $bookmark_obj->blog_id : get_current_blog_id(),
			'user_id'       => (int) $bookmark_obj->user_id,
			'type'          => $bookmark_obj->type,
			'item_id'       => (int) $bookmark_obj->item_id,
			'status'        => isset( $bookmark_obj->status ) ? (int) $bookmark_obj->status : 1,
			'date_recorded' => ! empty( $bookmark_obj->date_recorded ) ? $bookmark_obj->date_recorded : current_time( 'mysql' ),
		);

		$format = array( '%d', '%d', '%s', '%d', '%d', '%s' );

		if ( ! empty( $bookmark_obj->id ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table write.
			$saved = $wpdb->update( $table, $data, array( 'id' => (int) $bookmark_obj->id ), $format, array( '%d' ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table write.
			$saved = $wpdb->insert( $table, $data, $format );
		}

		if ( false === $saved ) {
			return false;
		}

		if ( empty( $bookmark_obj->id ) ) {
			$bookmark_obj->id = (int) $wpdb->insert_id;
		}

		self::purge_cache( (int) $bookmark_obj->id );

		/**
		 * Fires after a bookmark is saved.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param object $bookmark_obj Bookmark object, passed by reference.
		 */
		do_action_ref_array( 'bb_bookmarks_after_save', array( &$bookmark_obj ) );

		return (int) $bookmark_obj->id;
	}

	/**
	 * Delete a bookmark row.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param object $bookmark Bookmark object with at least an `id`.
	 *
	 * @return bool
	 */
	public static function delete( $bookmark ) {
		global $wpdb;

		if ( empty( $bookmark->id ) ) {
			return false;
		}

		$table = self::get_bookmark_tbl();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table write.
		$deleted = $wpdb->delete( $table, array( 'id' => (int) $bookmark->id ), array( '%d' ) );

		if ( false === $deleted ) {
			return false;
		}

		self::purge_cache( (int) $bookmark->id );

		/**
		 * Fires after a bookmark row is deleted.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param object $bookmark Bookmark object.
		 */
		do_action( 'bb_bookmarks_after_delete', $bookmark );

		return true;
	}

	/**
	 * Update the status of every bookmark for an item.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $type    Bookmark type.
	 * @param int    $item_id Item ID.
	 * @param int    $status  1 = active, 0 = inactive.
	 * @param int    $blog_id Optional. Site ID.
	 *
	 * @return bool
	 */
	public static function update_status( $type, $item_id, $status, $blog_id = 0 ) {
		global $wpdb;

		$table = self::get_bookmark_tbl();

		$where = array(
			'type'    => $type,
			'item_id' => (int) $item_id,
		);

		if ( ! empty( $blog_id ) ) {
			$where['blog_id'] = (int) $blog_id;
		}

		// Look up the affected row IDs first so their per-row caches can be purged after the write.
		$lookup_sql  = "SELECT id FROM {$table} WHERE type = %s AND item_id = %d";
		$lookup_args = array( $type, (int) $item_id );

		if ( ! empty( $blog_id ) ) {
			$lookup_sql   .= ' AND blog_id = %d';
			$lookup_args[] = (int) $blog_id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- custom table read, used only to target cache invalidation below; query and args are built and prepared above.
		$bookmark_ids = $wpdb->get_col( $wpdb->prepare( $lookup_sql, $lookup_args ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- custom table write.
		$updated = $wpdb->update( $table, array( 'status' => (int) $status ), $where );

		if ( ! is_int( $updated ) ) {
			return false;
		}

		foreach ( $bookmark_ids as $bookmark_id ) {
			wp_cache_delete( (int) $bookmark_id, self::CACHE_GROUP );
		}

		self::purge_cache();

		return true;
	}

	/**
	 * Query bookmarks.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 *     Optional. Query arguments.
	 *
	 *     @type array|string $type          Bookmark type(s).
	 *     @type int          $blog_id       Site ID. Default: current.
	 *     @type int          $user_id       User ID. Default: current user.
	 *     @type int          $item_id       Item ID.
	 *     @type int|null     $status        1, 0, or null for any. Default: 1.
	 *     @type array        $include_items Limit to these item IDs.
	 *     @type string       $order_by      Column to sort by. Default: 'date_recorded'.
	 *     @type string       $order         'ASC' or 'DESC'. Default: 'DESC'.
	 *     @type int          $per_page      Items per page. Default: null (no limit).
	 *     @type int          $page          Page number.
	 *     @type string       $fields        'all' | 'id' | a column name. Default: 'all'.
	 *     @type bool         $count         Whether to compute the total. Default: true.
	 *     @type bool         $cache         Whether to read from cache. Default: true.
	 *     @type bool         $count_only    Whether to skip the id lookup and return only the
	 *                                       total. Default: false.
	 * }
	 *
	 * @return array {
	 *     @type array $bookmarks Bookmark objects, or IDs when `fields` is 'id'.
	 *     @type int   $total     Total matching rows (non-paginated).
	 * }
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'type'          => array(),
				'blog_id'       => get_current_blog_id(),
				'user_id'       => get_current_user_id(),
				'item_id'       => 0,
				'status'        => 1,
				'include_items' => false,
				'order_by'      => 'date_recorded',
				'order'         => 'DESC',
				'per_page'      => null,
				'page'          => null,
				'fields'        => 'all',
				'count'         => true,
				'cache'         => true,
				'count_only'    => false,
			),
			'bb_bookmarks_get'
		);

		$r['fields'] = self::validate_column( $r['fields'] );
		$table       = self::get_bookmark_tbl();

		$where = array();

		if ( ! empty( $r['type'] ) ) {
			$types = is_array( $r['type'] ) ? $r['type'] : preg_split( '/[\s,]+/', $r['type'] );
			$types = array_map( 'sanitize_key', $types );

			$placeholders  = implode( ',', array_fill( 0, count( $types ), '%s' ) );
			$where['type'] = $wpdb->prepare( "bm.type IN ({$placeholders})", $types ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- placeholders are generated, values are prepared.
		}

		if ( ! empty( $r['blog_id'] ) ) {
			$where['blog_id'] = $wpdb->prepare( 'bm.blog_id = %d', $r['blog_id'] );
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where['user_id'] = $wpdb->prepare( 'bm.user_id = %d', $r['user_id'] );
		}

		if ( ! empty( $r['item_id'] ) ) {
			$where['item_id'] = $wpdb->prepare( 'bm.item_id = %d', $r['item_id'] );
		}

		if ( null !== $r['status'] ) {
			$where['status'] = $wpdb->prepare( 'bm.status = %d', (int) $r['status'] );
		}

		if ( ! empty( $r['include_items'] ) ) {
			$include_items = wp_parse_id_list( $r['include_items'] );

			if ( empty( $include_items ) ) {
				// A non-empty array that parses down to zero valid IDs must match
				// nothing, not produce a SQL syntax error ("bm.item_id IN ()").
				$where['include_items'] = '1 = 0';
			} else {
				$items                  = implode( ',', $include_items );
				$where['include_items'] = "bm.item_id IN ({$items})";
			}
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$order_by = self::validate_column( $r['order_by'] );
		$order_by = 'all' === $order_by ? 'date_recorded' : $order_by;
		$order    = 'ASC' === strtoupper( trim( $r['order'] ) ) ? 'ASC' : 'DESC';

		$pagination = '';
		if ( ! empty( $r['per_page'] ) && ! empty( $r['page'] ) && -1 !== (int) $r['per_page'] ) {
			$pagination = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $r['page'] - 1 ) * $r['per_page'] ), intval( $r['per_page'] ) );
		}

		$results = array( 'bookmarks' => array() );

		// When only the total is wanted, skip the id lookup (and its hydration) entirely.
		if ( empty( $r['count_only'] ) ) {
			// Select the requested column directly instead of always fetching ids
			// and hydrating every row via get_single_bookmark() only to discard
			// everything but one column with wp_list_pluck() -- that hydration
			// (get_post(), get_the_post_thumbnail_url(), get_permalink(),
			// bp_core_get_user_displayname()) is one query PER ROW and is only
			// actually needed when the caller wants the full object ('all').
			$column  = ( 'all' === $r['fields'] ) ? 'id' : $r['fields'];
			$ids_sql = "SELECT bm.{$column} FROM {$table} bm {$where_sql} ORDER BY bm.{$order_by} {$order}, bm.id {$order} {$pagination}";

			$cached = bp_core_get_incremented_cache( $ids_sql, self::CACHE_GROUP );
			if ( false === $cached || false === $r['cache'] ) {
				$ids = $wpdb->get_col( $ids_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- parts prepared above.
				bp_core_set_incremented_cache( $ids_sql, self::CACHE_GROUP, $ids );
			} else {
				$ids = $cached;
			}

			if ( 'all' === $r['fields'] ) {
				$ids = array_map( 'intval', (array) $ids );

				$bookmarks = array();
				foreach ( $ids as $id ) {
					$bookmark = self::get_single_bookmark( $id );
					if ( ! empty( $bookmark ) ) {
						$bookmarks[] = $bookmark;
					}
				}

				$results['bookmarks'] = $bookmarks;
			} elseif ( in_array( $r['fields'], array( 'id', 'blog_id', 'user_id', 'item_id', 'status' ), true ) ) {
				// Numeric columns -- cast for callers that rely on the historical
				// int shape (e.g. 'id' and 'item_id' consumers).
				$results['bookmarks'] = array_map( 'intval', (array) $ids );
			} else {
				// 'type' / 'date_recorded' -- string columns, returned as-is.
				$results['bookmarks'] = array_values( (array) $ids );
			}
		}

		if ( ! empty( $r['count'] ) || ! empty( $r['count_only'] ) ) {
			$count_sql = "SELECT COUNT(DISTINCT bm.id) FROM {$table} bm {$where_sql}";

			$cached_total = bp_core_get_incremented_cache( $count_sql, self::CACHE_GROUP );
			if ( false === $cached_total || false === $r['cache'] ) {
				$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- parts prepared above.
				bp_core_set_incremented_cache( $count_sql, self::CACHE_GROUP, array( $total ) );
			} else {
				$total = (int) ( ! empty( $cached_total ) ? current( $cached_total ) : 0 );
			}

			$results['total'] = $total;
		}

		return $results;
	}

	/**
	 * Fetch a single bookmark, hydrated by its type's items callback.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $id Bookmark row ID.
	 *
	 * @return object|int Bookmark object, or 0 when not found.
	 */
	public static function get_single_bookmark( $id ) {
		global $wpdb;

		$id = (int) $id;

		if ( empty( $id ) ) {
			return 0;
		}

		$bookmark = wp_cache_get( $id, self::CACHE_GROUP );

		if ( false === $bookmark ) {
			$table = self::get_bookmark_tbl();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table from a trusted helper.
			$bookmark = $wpdb->get_row( $wpdb->prepare( "SELECT bm.* FROM {$table} bm WHERE bm.id = %d", $id ) );

			wp_cache_set( $id, $bookmark, self::CACHE_GROUP );
		}

		if ( empty( $bookmark ) ) {
			return 0;
		}

		$obj                = new stdClass();
		$obj->id            = (int) $bookmark->id;
		$obj->blog_id       = (int) $bookmark->blog_id;
		$obj->user_id       = (int) $bookmark->user_id;
		$obj->type          = $bookmark->type;
		$obj->item_id       = (int) $bookmark->item_id;
		$obj->status        = (int) $bookmark->status;
		$obj->date_recorded = $bookmark->date_recorded;

		$type_data = bb_bookmark_register_types( $obj->type );

		if ( ! empty( $type_data['items_callback'] ) && is_callable( $type_data['items_callback'] ) ) {
			$items = call_user_func( $type_data['items_callback'], array( $obj ) );

			if ( ! empty( $items ) ) {
				$obj = current( $items );
			}
		}

		return $obj;
	}

	/**
	 * Invalidate cached bookmark queries.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $bookmark_id Optional. A single row to drop from cache.
	 *
	 * @return void
	 */
	public static function purge_cache( $bookmark_id = 0 ) {
		if ( ! empty( $bookmark_id ) ) {
			wp_cache_delete( (int) $bookmark_id, self::CACHE_GROUP );
		}

		bp_core_reset_incrementor( self::CACHE_GROUP );
	}
}
