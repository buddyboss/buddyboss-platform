<?php
/**
 * BuddyBoss Video Classes
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database interaction class for the BuddyBoss video album component.
 * Instance methods are available for creating/editing an video albums,
 * static methods for querying video album.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Video_Album {

	/** Properties ************************************************************/

	/**
	 * ID of the album.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $id;

	/**
	 * User ID of the album.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $user_id;

	/**
	 * Group ID of the album.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var int
	 */
	public $group_id;

	/**
	 * Title of the album.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $title;

	/**
	 * Privacy of the album.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $privacy;

	/**
	 * Upload date of the album.
	 *
	 * @since BuddyBoss 1.7.0
	 * @var string
	 */
	public $date_created;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @var WP_Error
	 */
	public $errors;

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int|bool $id Optional. The ID of a specific video album.
	 */
	public function __construct( $id = false ) {
		// Instantiate errors object.
		$this->errors = new WP_Error();

		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific album item.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function populate() {

		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_video_album' );

		if ( false === $row ) {
			$bp = buddypress();
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->video->table_name_albums} WHERE id = %d", $this->id ) );

			wp_cache_set( $this->id, $row, 'bp_video_album' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;

			return;
		}

		$this->id           = (int) $row->id;
		$this->user_id      = (int) $row->user_id;
		$this->group_id     = (int) $row->group_id;
		$this->title        = $row->title;
		$this->privacy      = $row->privacy;
		$this->date_created = $row->date_created;
	}

	/**
	 * Save the video album to the database.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {

		global $wpdb;

		$bp = buddypress();

		$this->id           = apply_filters_ref_array( 'bp_video_id_before_save', array( $this->id, &$this ) );
		$this->user_id      = apply_filters_ref_array( 'bp_video_user_id_before_save', array( $this->user_id, &$this ) );
		$this->group_id     = apply_filters_ref_array( 'bp_video_group_id_before_save', array( $this->group_id, &$this ) );
		$this->title        = apply_filters_ref_array( 'bp_video_title_before_save', array( $this->title, &$this ) );
		$this->privacy      = apply_filters_ref_array( 'bp_video_privacy_before_save', array( $this->privacy, &$this ) );
		$this->date_created = apply_filters_ref_array( 'bp_video_date_created_before_save', array( $this->date_created, &$this ) );

		/**
		 * Fires before the current album gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param BP_Video $this Current instance of the album being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_video_album_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		// If we have an existing ID, update the album, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->video->table_name_albums} SET user_id = %d, group_id = %d, title = %s, privacy = %s, date_created = %s WHERE id = %d", $this->user_id, $this->group_id, $this->title, $this->privacy, $this->date_created, $this->id ); //phpcs:ignore
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->video->table_name_albums} ( user_id, group_id, title, privacy, date_created ) VALUES ( %d, %d, %s, %s, %s )", $this->user_id, $this->group_id, $this->title, $this->privacy, $this->date_created ); //phpcs:ignore
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( false === $wpdb->query( $q ) ) {
			return false;
		}

		// If this is a new album, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Fires after an album has been saved to the database.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param BP_Video $this Current instance of album being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_video_album_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get albums, as specified by parameters.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $args         {
	 *                            An array of arguments. All items are optional.
	 *
	 * @type int         $page         Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 * @type int|bool    $per_page     Number of results per page. Default: 20.
	 * @type int|bool    $max          Maximum number of results to return. Default: false (unlimited).
	 * @type string      $fields       Video fields to return. Pass 'ids' to get only the video IDs.
	 *                                           'all' returns full video objects.
	 * @type string      $sort         ASC or DESC. Default: 'DESC'.
	 * @type string      $order_by     Column to order results by.
	 * @type array       $exclude      Array of video IDs to exclude. Default: false.
	 * @type string      $search_terms Limit results by a search term. Default: false.
	 * @type string|bool $count_total  If true, an additional DB query is run to count the total albums
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located videos
	 *               - 'albums' is an array of the located videos
	 */
	public static function get( $args = array() ) {

		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'page'         => 1,               // The current page.
				'per_page'     => 20,              // albums per page.
				'max'          => false,           // Max number of items to return.
				'fields'       => 'all',           // Fields to include.
				'sort'         => 'DESC',          // ASC or DESC.
				'order_by'     => 'date_created',  // Column to order by.
				'exclude'      => false,           // Array of ids to exclude.
				'in'           => false,           // Array of ids to include.
				'search_terms' => false,           // Terms to search by.
				'user_id'      => false,           // user id.
				'group_id'     => false,           // group id.
				'privacy'      => false,           // public, loggedin, onlyme, friends, grouponly.
				'count_total'  => false,           // Whether or not to use count_total.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT m.id';

		$from_sql = " FROM {$bp->video->table_name_albums} m";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Searching.
		if ( $r['search_terms'] ) {
			$search_terms_like              = '%' . bp_esc_like( $r['search_terms'] ) . '%';
			$where_conditions['search_sql'] = $wpdb->prepare( 'm.title LIKE %s', $search_terms_like );

			/**
			 * Filters whether or not to include users for search parameters.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param bool $value Whether or not to include user search. Default false.
			 */
			if ( apply_filters( 'bp_video_album_get_include_user_search', false ) ) {
				$user_search = get_user_by( 'slug', $r['search_terms'] );
				if ( false !== $user_search ) {
					$user_id                         = $user_search->ID;
					$where_conditions['search_sql'] .= $wpdb->prepare( ' OR m.user_id = %d', $user_id );
				}
			}
		}

		// Sorting.
		$sort = $r['sort'];
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		switch ( $r['order_by'] ) {
			case 'id':
			case 'user_id':
			case 'group_id':
			case 'attachment_id':
			case 'title':
				break;

			default:
				$r['order_by'] = 'date_created';
				break;
		}
		$order_by = 'm.' . $r['order_by'];

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "m.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "m.id IN ({$in})";
		}

		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions['user'] = "m.user_id = {$r['user_id']}";
		}

		if ( ! empty( $r['group_id'] ) ) {
			$where_conditions['group'] = "m.group_id = {$r['group_id']}";
		}

		if ( ! empty( $r['privacy'] ) ) {
			$privacy                     = "'" . implode( "', '", $r['privacy'] ) . "'";
			$where_conditions['privacy'] = "m.privacy IN ({$privacy})";
		}

		/**
		 * Filters the MySQL WHERE conditions for the albums get method.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_video_album_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions['2'] = '2';
		}

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		/**
		 * Filter the MySQL JOIN clause for the main video query.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_video_album_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'albums'         => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for album IDs.
		$album_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, m.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$album_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged video MySQL statement.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param string $album_ids_sql MySQL statement used to query for Video IDs.
		 * @param array  $r             Array of arguments passed into method.
		 */
		$album_ids_sql = apply_filters( 'bp_video_album_paged_activities_sql', $album_ids_sql, $r );

		$cache_group = 'bp_video_album';

		$cached = bp_core_get_incremented_cache( $album_ids_sql, $cache_group );
		if ( false === $cached ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$album_ids = $wpdb->get_col( $album_ids_sql );
			bp_core_set_incremented_cache( $album_ids_sql, $cache_group, $album_ids );
		} else {
			$album_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $album_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $album_ids ) === $per_page + 1 ) {
			array_pop( $album_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$albums = array_map( 'intval', $album_ids );
		} else {
			$albums = self::get_album_data( $album_ids );
		}

		if ( 'ids' !== $r['fields'] ) {
			// Pre-fetch data associated with video users and other objects.
			$albums = self::prefetch_object_data( $albums );
		}

		$retval['albums'] = $albums;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total video MySQL statement.
			 *
			 * @since BuddyBoss 1.7.0
			 *
			 * @param string $value     MySQL statement used to query for total videos.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_albums_sql = apply_filters( 'bp_video_album_total_videos_sql', "SELECT count(DISTINCT m.id) FROM {$bp->video->table_name_albums} m {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached           = bp_core_get_incremented_cache( $total_albums_sql, $cache_group );
			if ( false === $cached ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$total_albums = $wpdb->get_var( $total_albums_sql );
				bp_core_set_incremented_cache( $total_albums_sql, $cache_group, $total_albums );
			} else {
				$total_albums = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_albums > (int) $r['max'] ) {
					$total_albums = $r['max'];
				}
			}

			$retval['total'] = $total_albums;
		}

		return $retval;
	}

	/**
	 * Convert video IDs to video objects, as expected in template loop.
	 *
	 * @param array $album_ids Array of video IDs.
	 *
	 * @return array
	 * @since BuddyBoss 1.7.0
	 */
	public static function get_album_data( $album_ids = array() ) {
		global $wpdb;

		// Bail if no video ID's passed.
		if ( empty( $album_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$albums       = array();
		$uncached_ids = bp_get_non_cached_ids( $album_ids, 'bp_video_album' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the album ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from album table, preserving order.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->video->table_name_albums} WHERE id IN ({$uncached_ids_sql})" );

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_video_album' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $album_ids as $album_id ) {
			// Integer casting.
			$album = wp_cache_get( $album_id, 'bp_video_album' );
			if ( ! empty( $album ) ) {
				$album->id       = (int) $album->id;
				$album->user_id  = (int) $album->user_id;
				$album->group_id = (int) $album->group_id;
			}

			$album->video = bp_video_get(
				array(
					'album_id'    => $album->id,
					'count_total' => true,
				)
			);

			$albums[] = $album;
		}

		return $albums;
	}

	/**
	 * Get whether an album exists for a given id.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $id ID to check.
	 *
	 * @return int|bool Album ID if found; false if not.
	 */
	public static function album_exists( $id ) {
		if ( empty( $id ) ) {
			return false;
		}

		$args = array(
			'in' => $id,
		);

		$albums = self::get( $args );

		$album_id = false;
		if ( ! empty( $albums['albums'] ) ) {
			$album_id = current( $albums['albums'] )->id;
		}

		return $album_id;
	}

	/**
	 * Append xProfile full names to an video array.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $albums Albums array.
	 *
	 * @return array
	 */
	protected static function append_user_fullnames( $albums ) {

		if ( bp_is_active( 'xprofile' ) && ! empty( $albums ) ) {
			$album_user_ids = wp_list_pluck( $albums, 'user_id' );

			if ( ! empty( $album_user_ids ) ) {
				$fullnames = bp_core_get_user_displaynames( $album_user_ids );
				if ( ! empty( $fullnames ) ) {
					foreach ( (array) $albums as $i => $album ) {
						if ( ! empty( $fullnames[ $album->user_id ] ) ) {
							$albums[ $i ]->user_fullname = $fullnames[ $album->user_id ];
						}
					}
				}
			}
		}

		return $albums;
	}

	/**
	 * Pre-fetch data for objects associated with albums.
	 *
	 * Albums are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $albums Array of video albums.
	 *
	 * @return array $albums Array of video albums.
	 */
	protected static function prefetch_object_data( $albums ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with album.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $videos Array of video albums.
		 */
		return apply_filters( 'bp_video_album_prefetch_object_data', $albums );
	}

	/**
	 * Count total album for the given group
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array|bool|int
	 */
	public static function total_group_album_count( $group_id = 0 ) {

		if ( empty( $group_id ) ) {
			return 0;
		}

		$total_count = self::get(
			array(
				'group_id'    => $group_id,
				'count_total' => true,
			)
		);

		return ( isset( $total_count['total'] ) ? $total_count['total'] : 0 );
	}

	/**
	 * Delete albums from the database.
	 *
	 * To delete a specific album, pass an 'id' parameter.
	 * Otherwise use the filters.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $args         {
	 *                            Array of Arguments.
	 * @type int    $id           Optional. The ID of a specific item to delete.
	 * @type int    $user_id      Optional. The user ID to filter by.
	 * @type int    $group_id     Optional. The group ID to filter by.
	 * @type string $title        Optional. The title to filter by.
	 * @type string $date_created Optional. The date to filter by.
	 *                    }
	 *
	 * @return array|bool An array of deleted video IDs on success, false on failure.
	 */
	public static function delete( $args = array() ) {
		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'id'           => false,
				'user_id'      => false,
				'group_id'     => false,
				'date_created' => false,
			)
		);

		// Setup empty array from where query arguments.
		$where_args = array();

		// ID.
		if ( ! empty( $r['id'] ) ) {
			$where_args[] = $wpdb->prepare( 'id = %d', $r['id'] );
		}

		// User ID.
		if ( ! empty( $r['user_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'user_id = %d', $r['user_id'] );
		}

		// Group ID.
		if ( ! empty( $r['group_id'] ) ) {
			$where_args[] = $wpdb->prepare( 'group_id = %d', $r['group_id'] );
		}

		// Date created.
		if ( ! empty( $r['date_created'] ) ) {
			$where_args[] = $wpdb->prepare( 'date_created = %s', $r['date_created'] );
		}

		// Bail if no where arguments.
		if ( empty( $where_args ) ) {
			return false;
		}

		// Join the where arguments for querying.
		$where_sql = 'WHERE ' . join( ' AND ', $where_args );

		// Fetch all video albums being deleted so we can perform more actions.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$albums = $wpdb->get_results( "SELECT * FROM {$bp->video->table_name_albums} {$where_sql}" );

		/**
		 * Action to allow intercepting albums to be deleted.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $albums Array of video albums.
		 * @param array $r      Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_video_album_before_delete', array( $albums, $r ) );

		// Attempt to delete video albums from the database.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query( "DELETE FROM {$bp->video->table_name_albums} {$where_sql}" );

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting albums just deleted.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param array $albums Array of video albums.
		 * @param array $r      Array of parsed arguments.
		 */
		do_action_ref_array( 'bp_video_album_after_delete', array( $albums, $r ) );

		// Pluck the video albums IDs out of the $albums array.
		$album_ids = wp_parse_id_list( wp_list_pluck( $albums, 'id' ) );

		// delete the video associated with album.
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				bp_video_delete( array( 'album_id' => $album_id ) );
			}
		}

		return $album_ids;
	}

}
