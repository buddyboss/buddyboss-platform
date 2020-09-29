<?php
/**
 * BuddyBoss Moderation Classes
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation component.
 * Instance methods are available for creating/editing an moderation,
 * static methods for querying moderations.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation {

	/**
	 * ID of the moderation.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var int
	 */
	public $id = null;

	/**
	 * ID of the moderation data.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var int
	 */
	public $data_id = null;

	/**
	 * User ID who reported moderation item recently.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var int
	 */
	public $updated_by = null;

	/**
	 * ID of the moderation report item.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var int
	 */
	public $item_id = null;

	/**
	 * The description for the Moderation report.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var string
	 */
	public $content = '';

	/**
	 * Moderation report item type, eg 'moderation, group, message etc'.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var string
	 */
	public $item_type = '';

	/**
	 * The date the Moderation report was recorded or updated, in 'Y-m-d h:i:s' format.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var string
	 */
	public $date_updated = '';

	/**
	 * Whether the Moderation report item should be hidden sitewide.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var int
	 */
	public $hide_sitewide = 0;

	/**
	 * Report category id for Moderation report.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var int
	 */
	public $category_id = 0;

	/**
	 * Blog id for Moderation report.
	 *
	 * @since BuddyBoss 1.5.4
	 * @var int
	 */
	public $blog_id = 0;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @var WP_Error
	 */
	public $errors = array();

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param bool $item_id    Moderation item id.
	 * @param bool $item_type  Moderation item type.
	 */
	public function __construct( $item_id = false, $item_type = false ) {
		// Instantiate errors object.
		$this->errors = new WP_Error();

		$id = self::check_moderation_exist( $item_id, $item_type );
		if ( ! empty( $id ) ) {
			$this->id = (int) $id;
			$this->populate();
		}
	}

	/**
	 * Populate the object with data about the specific moderation report.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function populate() {
		global $wpdb;

		$row = wp_cache_get( $this->id, 'bb_moderation' );

		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name} WHERE id = %d", $this->id ) ); // phpcs:ignore

			wp_cache_set( $this->id, $row, 'bb_moderation' );
		}

		if ( empty( $row ) ) {
			$this->id = 0;
			return;
		}

		$this->id            = (int) $row->id;
		$this->item_id       = (int) $row->item_id;
		$this->item_type     = $row->item_type;
		$this->hide_sitewide = (int) $row->hide_sitewide;
		$this->updated_by    = (int) $row->updated_by;
		$this->date_updated  = $row->date_updated;
		$this->blog_id       = (int) $row->blog_id;
	}

	/**
	 * Save the moderation report to the database.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		$this->id            = apply_filters_ref_array( 'bp_moderation_id_before_save', array( $this->id, &$this ) );
		$this->updated_by    = apply_filters_ref_array( 'bp_moderation_updated_by_before_save', array( $this->updated_by, &$this ) );
		$this->item_id       = apply_filters_ref_array( 'bp_moderation_item_id_before_save', array( $this->item_id, &$this ) );
		$this->content       = apply_filters_ref_array( 'bp_moderation_content_before_save', array( $this->content, &$this ) );
		$this->item_type     = apply_filters_ref_array( 'bp_moderation_item_type_before_save', array( $this->item_type, &$this ) );
		$this->date_updated  = apply_filters_ref_array( 'bp_moderation_date_updated_before_save', array( $this->date_updated, &$this ) );
		$this->hide_sitewide = apply_filters_ref_array( 'bp_moderation_hide_sitewide_before_save', array( $this->hide_sitewide, &$this ) );
		$this->category_id   = apply_filters_ref_array( 'bp_moderation_category_id_before_save', array( $this->category_id, &$this ) );
		$this->blog_id       = apply_filters_ref_array( 'bp_moderation_blog_id_before_save', array( $this->blog_id, &$this ) );

		/**
		 * Fires before the current moderation report item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param BP_Moderation $this Current instance of the moderation item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_moderation_before_save', array( &$this ) );

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		if ( empty( $this->item_id ) || empty( $this->item_type ) ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				if ( empty( $this->item_id ) ) {
					$this->errors->add( 'bp_moderation_missing_item_id' );
				} else {
					$this->errors->add( 'bp_moderation_missing_item_type' );
				}

				return $this->errors;
			}
		}

		// If we have an existing ID, update the moderation report item, otherwise insert it.
		$this->id = self::check_moderation_exist( $this->item_id, $this->item_type );
		if ( ! empty( $this->id ) ) {
			$q = $wpdb->prepare( "UPDATE {$bp->moderation->table_name} SET hide_sitewide = %d, updated_by = %d, date_updated = %s WHERE id = %d", $this->hide_sitewide, $this->updated_by, $this->date_updated, $this->id ); // phpcs:ignore
		} else {
			$q = $wpdb->prepare( "INSERT INTO {$bp->moderation->table_name} ( item_id, item_type, hide_sitewide, updated_by, date_updated, blog_id ) VALUES ( %d, %s, %d, %d, %s, %d )", $this->item_id, $this->item_type, $this->hide_sitewide, $this->updated_by, $this->date_updated, $this->blog_id ); // phpcs:ignore
		}

		if ( false === $wpdb->query( $q ) ) { // phpcs:ignore
			return false;
		}

		// If this is a new moderation report item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;

			// If an existing moderation report item, prevent any changes to the content generating new @mention notifications.
		} else {
			add_filter( 'bp_moderation_at_name_do_notifications', '__return_false' );
		}

		/**
		 * Manage Moderation reporter data
		 */
		$this->data_id = self::check_moderation_data_exist( $this->id, $this->updated_by );
		if ( ! empty( $this->data_id ) ) {
			$q_data = $wpdb->prepare( "UPDATE {$bp->moderation->table_name_reports} SET content = %s, date_created = %s, category_id = %d WHERE id = %d AND moderation_id = %d AND user_id = %d ", $this->content, $this->date_updated, $this->category_id, $this->data_id, $this->id, $this->updated_by ); // phpcs:ignore
		} else {
			$q_data = $wpdb->prepare( "INSERT INTO {$bp->moderation->table_name_reports} ( moderation_id, user_id, content, date_created, category_id ) VALUES ( %d, %d, %s, %s, %d )", $this->id, $this->updated_by, $this->content, $this->date_updated, $this->category_id ); // phpcs:ignore

			// Todo: Count update.
		}

		if ( false === $wpdb->query( $q_data ) ) { // phpcs:ignore
			return false;
		}

		// If this is a new moderation report data, set the $data_id property.
		if ( empty( $this->data_id ) ) {
			$this->data_id = $wpdb->insert_id;

			// If an existing moderation report item, prevent any changes to the content generating new @mention notifications.
		} else {
			add_filter( 'bp_moderation_at_name_do_notifications', '__return_false' );
		}

		/**
		 * Fires after an moderation report item has been saved to the database.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param BP_Moderation $this Current instance of moderation item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_moderation_after_save', array( &$this ) );

		return true;
	}

	/** Static Methods ***************************************************/

	/**
	 * Get moderation items, as specified by parameters.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @see BP_Moderation::get_filter_sql() for a description of the
	 *      'filter' parameter.
	 * @see WP_Meta_Query::queries for a description of the 'meta_query'
	 *      parameter format.
	 *
	 * @param array $args {
	 *     An array of arguments. All items are optional.
	 *     @type int          $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 *     @type int|bool     $per_page          Number of results per page. Default: 25.
	 *     @type int|bool     $max               Maximum number of results to return. Default: false (unlimited).
	 *     @type string       $fields            Moderation fields to return. Pass 'ids' to get only the moderation IDs.
	 *                                           'all' returns full moderation objects.
	 *     @type string       $user_id           Array of user to filter out moderation report.
	 *     @type string       $sort              ASC or DESC. Default: 'DESC'.
	 *     @type string       $order_by          Column to order results by.
	 *     @type array        $exclude           Array of moderation report IDs to exclude. Default: false.
	 *     @type array        $in                Array of ids to limit query by (IN). Default: false.
	 *     @type array        $exclude_types     Array of moderation item type to exclude. Default: false.
	 *     @type array        $in_types          Array of item type to limit query by (IN). Default: false.
	 *     @type array        $meta_query        Array of meta_query conditions. See WP_Meta_Query::queries.
	 *     @type array        $date_query        Array of date_query conditions. See first parameter of
	 *                                           WP_Date_Query::__construct().
	 *     @type array        $filter_query      Array of advanced query conditions. See BP_Moderation_Query::__construct().
	 *     @type bool         $display_reporters Whether to include moderation reported users. Default: false.
	 *     @type bool         $update_meta_cache Whether to pre-fetch metadata for queried moderation items. Default: true.
	 *     @type string|bool  $count_total       If true, an additional DB query is run to count the total moderation items
	 *                                           for the query. Default: false.
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located moderations
	 *               - 'moderations' is an array of the located moderation reports
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		$bp = buddypress();
		$r  = wp_parse_args(
			$args,
			array(
				'page'              => 1,               // The current page.
				'per_page'          => 20,              // Moderation items per page.
				'user_id'           => false,           // filter by user id.
				'max'               => false,           // Max number of items to return.
				'fields'            => 'all',           // Fields to include.
				'sort'              => 'DESC',          // ASC or DESC.
				'order_by'          => 'date_updated', // Column to order by.
				'exclude'           => false,           // Array of ids to exclude.
				'in'                => false,           // Array of ids to limit query by (IN).
				'exclude_types'     => false,           // Array of type to exclude.
				'in_types'          => false,           // Array of type to limit query by (IN).
				// phpcs:ignore
				'meta_query'        => false,           // Filter by moderationmeta.
				'date_query'        => false,           // Filter by date.
				'filter_query'      => false,           // Advanced filtering - see BP_Moderation_Query.
				// phpcs:ignore
				'filter'            => false,           // See self::get_filter_sql().
				'display_reporters' => false,           // Whether or not to fetch user data.
				'update_meta_cache' => true,            // Whether or not to update meta cache.
				'count_total'       => false,           // Whether or not to use count_total.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT mo.id';

		$from_sql = " FROM {$bp->moderation->table_name} mo";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array( '1=1' );

		// Scope takes precedence.
		if ( ! empty( $r['filter_query'] ) ) {
			$filter_query = new BP_Moderation_Query( $r['filter_query'] );
			$sql          = $filter_query->get_sql();
			if ( ! empty( $sql ) ) {
				$where_conditions['filter_query_sql'] = $sql;
			}
		}

		// Regular filtering.
		$filter_sql = self::get_filter_sql( $r['filter'] );
		if ( $r['filter'] && $filter_sql ) {
			$where_conditions['filter_sql'] = $filter_sql;
		}

		// Sorting.
		$sort = $r['sort'];
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		switch ( $r['order_by'] ) {
			case 'id':
			case 'user_id':
			case 'item_type':
			case 'item_id':
			case 'date_updated':
			case 'hide_sitewide':
				break;

			default:
				$r['order_by'] = 'date_updated';
				break;
		}
		$order_by = 'mo.' . $r['order_by'];

		// The specific user_ids to which you want to limit the query.
		if ( ! empty( $r['user_id'] ) ) {
			$join_sql                   .= "INNER JOIN {$bp->moderation->table_name_reports} mr ON mo.id = mr.moderation_id ";
			$user_ids                    = implode( ',', wp_parse_id_list( $r['user_id'] ) );
			$where_conditions['user_id'] = "mr.user_id IN ({$user_ids})";
		}

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "mo.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "mo.id IN ({$in})";
		}

		// Exclude specified items type.
		if ( ! empty( $r['exclude_types'] ) ) {
			$not_in                            = "'" . implode( "', '", wp_parse_slug_list( $r['exclude_types'] ) ) . "'";
			$where_conditions['exclude_types'] = "mo.item_type NOT IN ({$not_in})";
		}

		// The specified items type to which you want to limit the query..
		if ( ! empty( $r['in_types'] ) ) {
			$not_in                       = "'" . implode( "', '", wp_parse_slug_list( $r['in_types'] ) ) . "'";
			$where_conditions['in_types'] = "mo.item_type IN ({$not_in})";
		}

		// Process meta_query into SQL.
		$meta_query_sql = self::get_meta_query_sql( $r['meta_query'] );

		if ( ! empty( $meta_query_sql['join'] ) ) {
			$join_sql .= $meta_query_sql['join'];
		}

		if ( ! empty( $meta_query_sql['where'] ) ) {
			$where_conditions[] = $meta_query_sql['where'];
		}

		// Process date_query into SQL.
		$date_query_sql = self::get_date_query_sql( $r['date_query'] );

		if ( ! empty( $date_query_sql ) ) {
			$where_conditions['date'] = $date_query_sql;
		}

		/**
		 * Filters the MySQL WHERE conditions for the Moderation items get method.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bp_moderation_get_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		/**
		 * Filter the MySQL JOIN clause for the main Moderation query.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bp_moderation_get_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		// Sanitize page and per_page parameters.
		$page     = absint( $r['page'] );
		$per_page = absint( $r['per_page'] );

		$retval = array(
			'moderations'    => null,
			'total'          => null,
			'has_more_items' => null,
		);

		// Query first for moderation IDs.
		$moderation_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, mo.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$moderation_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged moderations MySQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param string $moderation_ids_sql MySQL statement used to query for Moderation IDs.
		 * @param array  $r                Array of arguments passed into method.
		 */
		$moderation_ids_sql = apply_filters( 'bp_moderation_paged_moderations_sql', $moderation_ids_sql, $r );

		$cache_group = 'bp_moderation';
		$cached      = bp_core_get_incremented_cache( $moderation_ids_sql, $cache_group );
		if ( false === $cached ) {
			$moderation_ids = $wpdb->get_col( $moderation_ids_sql ); // phpcs:ignore
			bp_core_set_incremented_cache( $moderation_ids_sql, $cache_group, $moderation_ids );
		} else {
			$moderation_ids = $cached;
		}

		$retval['has_more_items'] = ! empty( $per_page ) && count( $moderation_ids ) > $per_page;

		// If we've fetched more than the $per_page value, we
		// can discard the extra now.
		if ( ! empty( $per_page ) && count( $moderation_ids ) === $per_page + 1 ) {
			array_pop( $moderation_ids );
		}

		if ( 'ids' === $r['fields'] ) {
			$moderations = array_map( 'intval', $moderation_ids );
		} else {
			$moderations = self::get_moderation_data( $moderation_ids );
		}

		if ( 'ids' !== $r['fields'] ) {
			// Get the fullnames of users so we don't have to query in the loop.
			$moderations = self::append_user_fullnames( $moderations );

			// Get moderation meta.
			$moderation_ids = array();
			foreach ( (array) $moderations as $moderation ) {
				$moderation_ids[] = $moderation->id;
			}

			/**
			 * Todo: Need to create function.
			if ( ! empty( $moderation_ids ) && $r['update_meta_cache'] ) {
				bp_moderation_update_meta_cache( $moderation_ids );
			}*/

			if ( $moderations && $r['display_reporters'] ) {
				$moderations = self::append_reporters( $moderations );
			}

			// Pre-fetch data associated with moderation users and other objects.
			self::prefetch_object_data( $moderations );
		}

		$retval['moderations'] = $moderations;

		// If $max is set, only return up to the max results.
		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total moderations MySQL statement.
			 *
			 * @since BuddyBoss 1.5.4
			 *
			 * @param string $value     MySQL statement used to query for total moderations.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_moderations_sql = apply_filters( 'bp_moderation_total_moderations_sql', "SELECT count(DISTINCT mo.id) FROM {$bp->moderation->table_name} mo {$join_sql} {$where_sql}", $where_sql, $sort );
			$cached                = bp_core_get_incremented_cache( $total_moderations_sql, $cache_group );
			if ( false === $cached ) {
				$total_moderations = $wpdb->get_var( $total_moderations_sql ); // phpcs:ignore
				bp_core_set_incremented_cache( $total_moderations_sql, $cache_group, $total_moderations );
			} else {
				$total_moderations = $cached;
			}

			if ( ! empty( $r['max'] ) ) {
				if ( (int) $total_moderations > (int) $r['max'] ) {
					$total_moderations = $r['max'];
				}
			}

			$retval['total'] = $total_moderations;
		}

		return $retval;
	}

	/**
	 * Convert moderation IDs to moderation objects, as expected in template loop.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $moderation_ids Array of moderation IDs.
	 * @return array
	 */
	protected static function get_moderation_data( $moderation_ids = array() ) {
		global $wpdb;

		// Bail if no moderation ID's passed.
		if ( empty( $moderation_ids ) ) {
			return array();
		}

		// Get BuddyPress.
		$bp = buddypress();

		$moderations  = array();
		$uncached_ids = bp_get_non_cached_ids( $moderation_ids, 'bp_moderation' );

		// Prime caches as necessary.
		if ( ! empty( $uncached_ids ) ) {
			// Format the moderation ID's for use in the query below.
			$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

			// Fetch data from moderation table, preserving order.
			$queried_adata = $wpdb->get_results( "SELECT * FROM {$bp->moderation->table_name} WHERE id IN ({$uncached_ids_sql})" ); // phpcs:ignore

			// Put that data into the placeholders created earlier,
			// and add it to the cache.
			foreach ( (array) $queried_adata as $adata ) {
				wp_cache_set( $adata->id, $adata, 'bp_moderation' );
			}
		}

		// Now fetch data from the cache.
		foreach ( $moderation_ids as $moderation_id ) {
			// Integer casting.
			$moderation = wp_cache_get( $moderation_id, 'bp_moderation' );
			if ( ! empty( $moderation ) ) {
				$moderation->id            = (int) $moderation->id;
				$moderation->item_id       = (int) $moderation->item_id;
				$moderation->hide_sitewide = (int) $moderation->hide_sitewide;
				$moderation->blog_id       = (int) $moderation->blog_id;
			}

			$moderations[] = $moderation;
		}

		return $moderations;
	}

	/**
	 * Pre-fetch data for objects associated with moderation items.
	 *
	 * Moderation items are associated with users, and often with other
	 * BuddyPress data objects. Here, we pre-fetch data about these
	 * associated objects, so that inline lookups - done primarily when
	 * building action strings - do not result in excess database queries.
	 *
	 * This method contains nothing but a filter that allows other
	 * components, such as bp-activity and bp-groups, to hook in and prime
	 * their own caches at the beginning of an Moderation loop.
	 *
	 * @param array $moderations Array of moderations.
	 *
	 * @return array $moderations Array of moderations.
	 * @since BuddyBoss 1.5.4
	 */
	protected static function prefetch_object_data( $moderations ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with moderation item.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $moderations Array of moderations.
		 */
		return apply_filters( 'bp_moderation_prefetch_object_data', $moderations );
	}

	/**
	 * Append xProfile fullnames to an moderation/moderation data array.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array  $moderations Moderations/Moderations data array.
	 * @param string $user_key   User key name.
	 *
	 * @return array*
	 */
	protected static function append_user_fullnames( $moderations, $user_key = 'updated_by' ) {

		if ( bp_is_active( 'xprofile' ) && ! empty( $moderations ) ) {
			$moderation_user_ids = wp_list_pluck( $moderations, $user_key );

			if ( ! empty( $moderation_user_ids ) ) {
				$fullnames = bp_core_get_user_displaynames( $moderation_user_ids );
				if ( ! empty( $fullnames ) ) {
					foreach ( (array) $moderations as $i => $moderation ) {
						if ( ! empty( $fullnames[ $moderation->$user_key ] ) ) {
							$moderations[ $i ]->user_fullname = $fullnames[ $moderation->$user_key ];
						}
					}
				}
			}
		}

		return $moderations;
	}

	/**
	 * Append moderation reported users to their associated moderation report.
	 *
	 * @param array $moderations moderations array.
	 *
	 * @return array The updated moderations with users.
	 * @since BuddyBoss 1.5.4
	 *
	 * @global wpdb $wpdb        WordPress database abstraction object.
	 */
	public static function append_reporters( $moderations ) {
		$moderations_reporters = array();

		// Now fetch the activity comments and parse them into the correct position in the activities array.
		foreach ( (array) $moderations as $moderation ) {
			$moderations_reporters[ $moderation->id ] = self::get_moderation_reporters( $moderation->id );
		}

		// Merge the comments with the activity items.
		foreach ( (array) $moderations as $key => $moderation ) {
			if ( isset( $moderations_reporters[ $moderation->id ] ) ) {
				$moderations[ $key ]->reporters = $moderations_reporters[ $moderation->id ];
				$moderations[ $key ]->reporters = self::append_user_fullnames( $moderations[ $key ]->reporters, 'user_id' );
			}
		}

		return $moderations;
	}

	/**
	 * Get reporters that are associated with a specific moderation ID.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param int $moderation_id Moderation id.
	 * @return array reporters data.
	 */
	public static function get_moderation_reporters( $moderation_id ) {
		global $wpdb;

		$reporters = wp_cache_get( $moderation_id, 'bp_moderation_data' );
		if ( empty( $reporters ) ) {
			$bp = buddypress();

			$sql       = $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name_reports} mr WHERE mr.moderation_id = %d ORDER BY mr.date_created DESC", $moderation_id ); // phpcs:ignore
			$sql       = apply_filters( 'bp_moderation_reports_sql', $sql, $moderation_id );
			$reporters = $wpdb->get_results( $sql ); // phpcs:ignore
			foreach ( $reporters as $key => $reporter ) {
				unset( $reporters[ $key ]->id );
				unset( $reporters[ $key ]->moderation_id );
				$reporters[ $key ]->user_id     = (int) $reporter->user_id;
				$reporters[ $key ]->category_id = (int) $reporter->category_id;
			}

			wp_cache_set( $moderation_id, $reporters, 'bp_moderation_data' );
		}

		return $reporters;
	}

	/**
	 * Get the SQL for the 'meta_query' param in BP_Moderation::get().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Moderation::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for WP_Meta_Query for details.
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$activity_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->moderationmeta.
			$wpdb->activitymeta = buddypress()->moderation->table_name_meta;

			$meta_sql = $activity_meta_query->get_sql( 'moderation', 'mo', 'id' );

			// Strip the leading AND - BP handles it in get().
			$sql_array['where'] = preg_replace( '/^\sAND/', '', $meta_sql['where'] );
			$sql_array['join']  = $meta_sql['join'];
		}

		return $sql_array;
	}

	/**
	 * Get the SQL for the 'date_query' param in BP_Moderation::get().
	 *
	 * We use BP_Date_Query, which extends WP_Date_Query, to do the heavy lifting
	 * of parsing the date_query array and creating the necessary SQL clauses.
	 * However, since BP_Moderation::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading AND
	 * keyword from the query).
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $date_query An array of date_query parameters. See the
	 *                          documentation for the first parameter of WP_Date_Query.
	 * @return string
	 */
	public static function get_date_query_sql( $date_query = array() ) {
		$sql = '';

		// Date query.
		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			$date_query = new BP_Date_Query( $date_query, 'date_updated' );
			$sql        = preg_replace( '/^\sAND/', '', $date_query->get_sql() );
		}

		return $sql;
	}

	/**
	 * Create filter SQL clauses.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $filter_array {
	 *     Fields and values to filter by.
	 *
	 *     @type array|string|int $user_id          User ID(s).
	 *     @type array|string|int $item_id          Item ID(s).
	 *     @type array|string|int $hide_sitewide    filter hidden items.
	 *     @type array|string|int $blog_id          Blog ID(s).
	 *
	 * }
	 * @return string The filter clause, for use in a SQL query.
	 */
	public static function get_filter_sql( $filter_array ) {

		$filter_sql = array();

		if ( ! empty( $filter_array['item_id'] ) ) {
			$item_sql = self::get_in_operator_sql( 'mo.item_id', $filter_array['item_id'] );
			if ( ! empty( $item_sql ) ) {
				$filter_sql[] = $item_sql;
			}
		}

		if ( ! empty( $filter_array['hide_sitewide'] ) ) {
			$hide_sitewide_sql = self::get_in_operator_sql( 'mo.hide_sitewide', $filter_array['hide_sitewide'] );
			if ( ! empty( $hide_sitewide_sql ) ) {
				$filter_sql[] = $hide_sitewide_sql;
			}
		}

		if ( ! empty( $filter_array['blog_id'] ) ) {
			$blog_sql = self::get_in_operator_sql( 'mo.blog_id', $filter_array['blog_id'] );
			if ( ! empty( $blog_sql ) ) {
				$filter_sql[] = $blog_sql;
			}
		}

		if ( empty( $filter_sql ) ) {
			return false;
		}

		return join( ' AND ', $filter_sql );
	}

	/**
	 * Create SQL IN clause for filter queries.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @see BP_Moderation::get_filter_sql()
	 *
	 * @param string     $field The database field.
	 * @param array|bool $items The values for the IN clause, or false when none are found.
	 * @return string|false
	 */
	public static function get_in_operator_sql( $field, $items ) {
		global $wpdb;

		// Split items at the comma.
		if ( ! is_array( $items ) ) {
			$items = explode( ',', $items );
		}

		// Array of prepared integers or quoted strings.
		$items_prepared = array();

		// Clean up and format each item.
		foreach ( $items as $item ) {
			// Clean up the string.
			$item = trim( $item );
			// Pass everything through prepare for security and to safely quote strings.
			$items_prepared[] = ( is_numeric( $item ) ) ? $wpdb->prepare( '%d', $item ) : $wpdb->prepare( '%s', $item );
		}

		// Build IN operator sql syntax.
		if ( count( $items_prepared ) ) {
			return sprintf( '%s IN ( %s )', trim( $field ), implode( ',', $items_prepared ) );
		} else {
			return false;
		}
	}

	/**
	 * Check moderation item report exist or not
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param int $item_id    Moderation item id.
	 * @param int $item_type  Moderation item type.
	 *
	 * @return false
	 */
	public static function check_moderation_exist( $item_id, $item_type ) {
		global $wpdb;

		$bp = buddypress();

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} mo WHERE mo.item_id = %d AND mo.item_type = %s", $item_id, $item_type ) ); // phpcs:ignore

		return is_numeric( $result ) ? (int) $result : false;
	}

	/**
	 * Check moderation data exist for specific user or not
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param int $moderation_id Moderation report id.
	 * @param int $user_id       Moderation reporter id.
	 *
	 * @return false
	 */
	public static function check_moderation_data_exist( $moderation_id, $user_id ) {
		global $wpdb;

		$bp = buddypress();

		$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name_reports} mr WHERE mr.moderation_id = %d AND mr.user_id = %d", $moderation_id, $user_id ) ); // phpcs:ignore

		return is_numeric( $result ) ? (int) $result : false;
	}
}
