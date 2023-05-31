<?php
/**
 * BuddyBoss Moderation Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation component.
 * Instance methods are available for creating/editing an moderation,
 * static methods for querying moderations.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation {

	/**
	 * ID of the moderation.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $id = null;

	/**
	 * ID of the moderation data.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $report_id = null;

	/**
	 * User ID who reported moderation item recently.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $user_id = null;

	/**
	 * ID of the moderation report item.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $item_id = null;

	/**
	 * The description for the Moderation report.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var string
	 */
	public $content = '';

	/**
	 * Moderation report item type, eg 'moderation, group, message etc'.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var string
	 */
	public $item_type = '';

	/**
	 * The date the Moderation report was recorded or updated, in 'Y-m-d h:i:s' format.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var string
	 */
	public $last_updated = '';

	/**
	 * The date the Moderation report was recorded or updated, in 'Y-m-d h:i:s' format.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var string
	 */
	public $date_created = '';

	/**
	 * Whether the Moderation report item should be hidden sitewide.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $hide_sitewide = 0;

	/**
	 * Report category id for Moderation report.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $category_id = 0;

	/**
	 * Blog id for Moderation report.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $blog_id = 0;

	/**
	 * Reported count for Moderation report.
	 *
	 * @since BuddyBoss 1.5.6
	 * @var int
	 */
	public $count = 0;

	/**
	 * Reported count for members Moderation report.
	 *
	 * @since BuddyBoss 2.1.1
	 * @var int
	 */
	public $count_report = 0;

	/**
	 * Report flag for members Moderation report.
	 *
	 * @since BuddyBoss 2.1.1
	 * @var int
	 */
	public $user_report = 0;

	/**
	 * Error holder.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @var WP_Error
	 */
	public $errors = array();

	/**
	 * Error type to return. Either 'bool' or 'wp_error'.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @var string
	 */
	public $error_type = 'bool';

	/**
	 * Bypass the cache and force a DB query.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @var bool
	 */
	public static $no_cache = false;

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool $item_id          Moderation item id.
	 * @param bool $item_type        Moderation item type.
	 * @param bool $blocking_user_id Moderation user id.
	 */
	public function __construct( $item_id = false, $item_type = false, $blocking_user_id = false ) {
		// Instantiate errors object.
		$this->id            = 0;
		$this->hide_sitewide = 0;
		$this->errors        = new WP_Error();
		$this->user_id       = ! empty( $blocking_user_id ) ? $blocking_user_id : get_current_user_id();
		$this->blog_id       = get_current_blog_id();
		$this->report_id     = 0;
		$this->category_id   = 0;

		if ( ! empty( $item_id ) && ! empty( $item_type ) ) {
			$this->item_id   = $item_id;
			$this->item_type = $item_type;

			$report_type = ( BP_Moderation_Members::$moderation_type_report === $item_type ) ? BP_Moderation_Members::$moderation_type : $this->item_type;

			$id = self::check_moderation_exist( $this->item_id, $report_type, self::$no_cache, BP_Moderation_Members::$moderation_type_report === $item_type );
			if ( ! empty( $id ) ) {
				$this->id = (int) $id;
				$this->populate( $item_type );
			}
		}
	}

	/**
	 * Check moderation item report exist or not
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int  $item_id     Moderation item id.
	 * @param int  $item_type   Moderation item type.
	 * @param bool $force_check bypass caching or not.
	 * @param bool $user_report if user report or not.
	 *
	 * @return false|int
	 */
	public static function check_moderation_exist( $item_id, $item_type, $force_check = false, $user_report = false ) {
		global $wpdb;

		$bp        = buddypress();
		$cache_key = 'bb_check_moderation_' . $item_type . '_' . $item_id . '_' . $user_report;
		$result    = wp_cache_get( $cache_key, 'bp_moderation' );

		if ( false === $result || true === $force_check ) {
			if ( true === $user_report ) {
				$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} ms WHERE ms.item_id = %d AND ms.item_type = %s AND ms.user_report = 1", $item_id, BP_Moderation_Members::$moderation_type ) ); // phpcs:ignore
			} else {
				$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} ms WHERE ms.item_id = %d AND ms.item_type = %s AND ms.reported = 1", $item_id, $item_type ) ); // phpcs:ignore
			}

			wp_cache_set( $cache_key, $result, 'bp_moderation' );
		}

		return is_numeric( $result ) ? (int) $result : false;
	}

	/**
	 * Check any moderation item block/report exist or not
	 *
	 * @since BuddyBoss 2.1.1
	 *
	 * @param int  $item_id     Moderation item id.
	 * @param int  $item_type   Moderation item type.
	 * @param bool $force_check bypass caching or not.
	 *
	 * @return false|int
	 */
	public static function check_any_moderation_exist( $item_id, $item_type, $force_check = false ) {
		global $wpdb;

		$bp        = buddypress();
		$cache_key = 'bb_check_any_moderation_' . $item_type . '_' . $item_id;
		$result    = wp_cache_get( $cache_key, 'bp_moderation' );

		if ( false === $result || true === $force_check ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name} ms WHERE ms.item_id = %d AND ms.item_type = %s AND ( ms.reported = 1 OR ms.user_report = 1 )", $item_id, $item_type ) ); // phpcs:ignore
			wp_cache_set( $cache_key, $result, 'bp_moderation' );
		}

		return is_numeric( $result ) ? (int) $result : false;
	}

	/**
	 * Populate the object with data about the specific moderation report.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function populate( $user_report = false ) {
		static $bb_report_row_query = array();
		global $wpdb;

		$row = wp_cache_get( $this->id, 'bp_moderation' );
		if ( false === $row ) {
			$bp  = buddypress();
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name} WHERE id = %d", $this->id ) ); // phpcs:ignore

			wp_cache_set( $this->id, $row, 'bp_moderation' );
		}

		if ( empty( $row ) ) {
			return;
		}

		$this->id            = (int) $row->id;
		$this->item_id       = (int) $row->item_id;
		$this->item_type     = $row->item_type;
		$this->hide_sitewide = (int) $row->hide_sitewide;
		$this->last_updated  = $row->last_updated;
		$this->blog_id       = (int) $row->blog_id;
		$this->count         = (int) bp_moderation_get_meta( $this->id, '_count' );
		$this->count_report  = (int) bp_moderation_get_meta( $this->id, '_count_user_reported' );

		/**
		 * Fetch User Report data
		 */
		$bp        = buddypress();
		$cache_key = 'bp_moderation_populate_' . $this->id . '_' . $this->user_id . '_' . ( ! empty( $user_report ) ? $user_report : $this->item_type );
		if ( ! isset( $bb_report_row_query[ $cache_key ] ) || true === self::$no_cache ) {
			if ( BP_Moderation_Members::$moderation_type_report === $user_report ) {
				$report_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name_reports} mr WHERE mr.moderation_id = %d AND mr.user_id = %d and user_report = 1", $this->id, $this->user_id ) ); // phpcs:ignore
			} else {
				$report_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name_reports} mr WHERE mr.moderation_id = %d AND mr.user_id = %d AND user_report = 0", $this->id, $this->user_id ) ); // phpcs:ignore
			}
			$bb_report_row_query[ $cache_key ] = ! empty( $report_row ) ? $report_row : false;
		} else {
			$report_row = $bb_report_row_query[ $cache_key ];
		}
		if ( empty( $report_row ) ) {
			return;
		}

		$this->report_id    = (int) $report_row->id;
		$this->content      = $report_row->content;
		$this->date_created = $report_row->date_created;
		$this->category_id  = (int) $report_row->category_id;
		$this->user_report  = $report_row->user_report;
	}

	/**
	 * Get moderation items, as specified by parameters.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args              {
	 *                                 An array of arguments. All items are optional.
	 *
	 * @type int         $page              Which page of results to fetch. Using page=1 without per_page will result
	 *                                           in no pagination. Default: 1.
	 * @type int|bool    $per_page          Number of results per page. Default: 25.
	 * @type int|bool    $max               Maximum number of results to return. Default: false (unlimited).
	 * @type string      $fields            Moderation fields to return. Pass 'ids' to get only the moderation IDs.
	 *                                           'all' returns full moderation objects.
	 * @type string      $user_id           Array of user to filter out moderation report.
	 * @type string      $sort              ASC or DESC. Default: 'DESC'.
	 * @type string      $order_by          Column to order results by.
	 * @type array       $exclude           Array of moderation report IDs to exclude. Default: false.
	 * @type array       $in                Array of ids to limit query by (IN). Default: false.
	 * @type array       $exclude_types     Array of moderation item type to exclude. Default: false.
	 * @type array       $in_types          Array of item type to limit query by (IN). Default: false.
	 * @type array       $meta_query        Array of meta_query conditions. See WP_Meta_Query::queries.
	 * @type array       $date_query        Array of date_query conditions. See first parameter of
	 *                                           WP_Date_Query::__construct().
	 * @type array       $filter_query      Array of advanced query conditions. See BP_Moderation_Query::__construct().
	 * @type bool        $display_reporters Whether to include moderation reported users. Default: false.
	 * @type bool        $update_meta_cache Whether to pre-fetch metadata for queried moderation items. Default: true.
	 * @type string|bool $count_total       If true, an additional DB query is run to count the total moderation items
	 *                                           for the query. Default: false.
	 * @type int         $hidden            whether to get hidden items or not. Default: false
	 * }
	 * @return array The array returned has two keys:
	 *               - 'total' is the count of located moderations
	 *               - 'moderations' is an array of the located moderation reports
	 * @see   BP_Moderation::get_filter_sql() for a description of the
	 *        'filter' parameter.
	 * @see   WP_Meta_Query::queries for a description of the 'meta_query'
	 *        parameter format.
	 */
	public static function get( $args = array() ) {
		global $wpdb;

		$bp = buddypress();
		$r  = bp_parse_args(
			$args,
			array(
				'page'              => 1,               // The current page.
				'per_page'          => 20,              // Moderation items per page.
				'user_id'           => false,           // filter by user id.
				'max'               => false,           // Max number of items to return.
				'fields'            => 'all',           // Fields to include.
				'sort'              => 'DESC',          // ASC or DESC.
				'order_by'          => 'last_updated', // Column to order by.
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
				'hidden'            => false,           // Get the moderation item base on it's hide status.
			)
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT ms.id';

		$from_sql = " FROM {$bp->moderation->table_name} ms";

		$join_sql = '';

		// Where conditions.
		$where_conditions = array( '1=1' );

		// Exclude report list for backend.
		if ( ! isset( $r['reported'] ) ) {
			$where_conditions['reported'] = 'reported=1';
		}

		// Scope takes precedence.
		if ( ! empty( $r['filter_query'] ) ) {
			$filter_query = new BP_Moderation_Query( $r['filter_query'], $r );
			$sql          = $filter_query->get_sql();

			if ( ! empty( $sql['where'] ) ) {
				$where_conditions['filter_query_sql'] = $sql['where'];
			}

			if ( ! empty( $sql['join'] ) ) {
				$join_sql .= $sql['join'];
			}
		}

		if ( isset( $r['user_report'] ) ) {
			if ( empty( $join_sql ) && empty( $r['user_id'] ) ) {
				$join_sql .= " LEFT JOIN {$bp->moderation->table_name_reports} mr ON ms.id = mr.moderation_id ";
			}
			$where_conditions['user_report'] = 'mr.user_report=1';
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
			case 'item_type':
			case 'item_id':
			case 'last_updated':
				break;

			default:
				$r['order_by'] = 'last_updated';
				break;
		}
		$order_by = 'ms.' . $r['order_by'];

		// The specific user_ids to which you want to limit the query.
		if ( ! empty( $r['user_id'] ) ) {

			// we added Report table user_id field support in BP_Moderation_Query so we need to take case care if table already added in joined query.
			if ( empty( $join_sql ) || ! strpos( "{$bp->moderation->table_name_reports} mr", $join_sql ) ) {
				$join_sql .= "INNER JOIN {$bp->moderation->table_name_reports} mr ON ms.id = mr.moderation_id ";
			}

			$user_ids                    = implode( ',', wp_parse_id_list( $r['user_id'] ) );
			$where_conditions['user_id'] = "mr.user_id IN ({$user_ids})";
			if ( ! isset( $r['user_report'] ) ) {
				$where_conditions['user_id'] .= ' and mr.user_report = 0 ';
			}
		}

		// Exclude specified items.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude                     = implode( ',', wp_parse_id_list( $r['exclude'] ) );
			$where_conditions['exclude'] = "ms.id NOT IN ({$exclude})";
		}

		// The specific ids to which you want to limit the query.
		if ( ! empty( $r['in'] ) ) {
			$in                     = implode( ',', wp_parse_id_list( $r['in'] ) );
			$where_conditions['in'] = "ms.id IN ({$in})";
		}

		// Exclude specified items type.
		if ( ! empty( $r['exclude_types'] ) ) {
			$not_in_types                      = "'" . implode(
				"', '",
				wp_parse_slug_list( $r['exclude_types'] )
			) . "'";
			$where_conditions['exclude_types'] = "ms.item_type NOT IN ({$not_in_types})";
		}

		// The specified items type to which you want to limit the query..
		if ( ! empty( $r['in_types'] ) ) {
			$in_types                     = "'" . implode( "', '", wp_parse_slug_list( $r['in_types'] ) ) . "'";
			$where_conditions['in_types'] = "ms.item_type IN ({$in_types})";
		} else {
			$content_type                 = bp_moderation_content_types();
			$in_types                     = "'" . implode( "', '", wp_parse_slug_list( array_keys( $content_type ) ) ) . "'";
			$where_conditions['in_types'] = "ms.item_type IN ({$in_types})";
		}

		// The specified items type to which you want to limit the query..
		if ( 1 === $r['hidden'] ) {
			$where_conditions['hidden'] = 'ms.hide_sitewide=1';
		} elseif ( 0 === $r['hidden'] ) {
			$where_conditions['hidden'] = 'ms.hide_sitewide=0';
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
		 * @since BuddyBoss 1.5.6
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
		 * @since BuddyBoss 1.5.6
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
		$moderation_ids_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort}, ms.id {$sort}";

		if ( ! empty( $per_page ) && ! empty( $page ) ) {
			// We query for $per_page + 1 items in order to
			// populate the has_more_items flag.
			$moderation_ids_sql .= $wpdb->prepare( ' LIMIT %d, %d', absint( ( $page - 1 ) * $per_page ), $per_page + 1 );
		}

		/**
		 * Filters the paged moderations MySQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param string $moderation_ids_sql MySQL statement used to query for Moderation IDs.
		 * @param array  $r                  Array of arguments passed into method.
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
			// Get moderation meta.
			$moderation_ids = array();
			foreach ( (array) $moderations as $moderation ) {
				$moderation_ids[] = $moderation->id;
			}

			/**
			 * Todo: Need to create function.
			 * if ( ! empty( $moderation_ids ) && $r['update_meta_cache'] ) {
			 * bp_moderation_update_meta_cache( $moderation_ids );
			 * }*/

			if ( $moderations && $r['display_reporters'] ) {
				$moderations = self::append_reporters( $moderations, array( 'user_id' => $r['user_id'] ) );
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
			 * @since BuddyBoss 1.5.6
			 *
			 * @param string $value     MySQL statement used to query for total moderations.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$total_moderations_sql = apply_filters( 'bp_moderation_total_moderations_sql', "SELECT count(DISTINCT ms.id) FROM {$bp->moderation->table_name} ms {$join_sql} {$where_sql}", $where_sql, $sort );
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
	 * Create filter SQL clauses.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $filter_array  {
	 *                             Fields and values to filter by.
	 *
	 * @type array|string|int $user_id       User ID(s).
	 * @type array|string|int $item_id       Item ID(s).
	 * @type array|string|int $blog_id       Blog ID(s).
	 *
	 * }
	 * @return string The filter clause, for use in a SQL query.
	 */
	public static function get_filter_sql( $filter_array ) {

		$filter_sql = array();

		if ( ! empty( $filter_array['item_id'] ) ) {
			$item_sql = self::get_in_operator_sql( 'ms.item_id', $filter_array['item_id'] );
			if ( ! empty( $item_sql ) ) {
				$filter_sql[] = $item_sql;
			}
		}

		if ( ! empty( $filter_array['blog_id'] ) ) {
			$blog_sql = self::get_in_operator_sql( 'ms.blog_id', $filter_array['blog_id'] );
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
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string     $field The database field.
	 * @param array|bool $items The values for the IN clause, or false when none are found.
	 *
	 * @return string|false
	 * @see   BP_Moderation::get_filter_sql()
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
	 * Get the SQL for the 'meta_query' param in BP_Moderation::get().
	 *
	 * We use WP_Meta_Query to do the heavy lifting of parsing the
	 * meta_query array and creating the necessary SQL clauses. However,
	 * since BP_Moderation::get() builds its SQL differently than
	 * WP_Query, we have to alter the return value (stripping the leading
	 * AND keyword from the 'where' clause).
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $meta_query An array of meta_query filters. See the
	 *                          documentation for WP_Meta_Query for details.
	 *
	 * @return array $sql_array 'join' and 'where' clauses.
	 */
	public static function get_meta_query_sql( $meta_query = array() ) {
		global $wpdb;

		$sql_array = array(
			'join'  => '',
			'where' => '',
		);

		if ( ! empty( $meta_query ) ) {
			$moderation_meta_query = new WP_Meta_Query( $meta_query );

			// WP_Meta_Query expects the table name at
			// $wpdb->moderationmeta.
			$wpdb->moderationmeta = buddypress()->moderation->table_name_meta;

			$meta_sql = $moderation_meta_query->get_sql( 'moderation', 'ms', 'id' );

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
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $date_query An array of date_query parameters. See the
	 *                          documentation for the first parameter of WP_Date_Query.
	 *
	 * @return string
	 */
	public static function get_date_query_sql( $date_query = array() ) {
		$sql = '';

		// Date query.
		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			$date_query = new BP_Date_Query( $date_query, 'last_updated' );
			$sql        = preg_replace( '/^\sAND/', '', $date_query->get_sql() );
		}

		return $sql;
	}

	/**
	 * Convert moderation IDs to moderation objects, as expected in template loop.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $moderation_ids Array of moderation IDs.
	 *
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
				$moderation->count         = (int) bp_moderation_get_meta( $moderation->id, '_count' );
				$moderation->count_report  = (int) bp_moderation_get_meta( $moderation->id, '_count_user_reported' );
				$moderation->user_report   = $moderation->user_report;
			}
			$moderations[] = $moderation;
		}

		return $moderations;
	}

	/**
	 * Append moderation reported users to their associated moderation report.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $moderations moderation array.
	 * @param array $args        arguments.
	 *
	 * @return array The updated moderations with users.
	 * @global wpdb $wpdb        WordPress database abstraction object.
	 */
	public static function append_reporters( $moderations, $args ) {
		$moderations_reporters = array();

		// Now fetch the activity comments and parse them into the correct position in the activities array.
		foreach ( (array) $moderations as $moderation ) {
			$moderations_reporters[ $moderation->id ] = self::get_moderation_reporters( $moderation->id, $args );
		}

		// Merge the comments with the activity items.
		foreach ( (array) $moderations as $key => $moderation ) {
			if ( isset( $moderations_reporters[ $moderation->id ] ) ) {
				$moderations[ $key ]->reporters = $moderations_reporters[ $moderation->id ];
				$moderations[ $key ]->reporters = self::append_user_fullnames( $moderations[ $key ]->reporters );
			}
		}

		return $moderations;
	}

	/**
	 * Get reporters that are associated with a specific moderation ID.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $moderation_id Moderation id.
	 * @param array $args          Argument to filter data.
	 *
	 * @return array reporters data.
	 */
	public static function get_moderation_reporters( $moderation_id, $args = array() ) {
		global $wpdb;

		$reporters = wp_cache_get( $moderation_id, 'bp_moderation_reporters' );
		if ( empty( $reporters ) || ! empty( $args ) ) {
			$bp = buddypress();

			$select_sql = "SELECT * FROM {$bp->moderation->table_name_reports} mr";

			// Where conditions.
			$where_conditions[] = $wpdb->prepare( 'mr.moderation_id = %d', $moderation_id ); // phpcs:ignore

			if ( ! empty( $args['user_id'] ) ) {
				$where_conditions[] = $wpdb->prepare( 'mr.user_id = %d', $args['user_id'] ); // phpcs:ignore
			}

			if ( isset( $args['user_repoted'] ) ) {
				$where_conditions[] = ! empty( $args['user_repoted'] ) ? 'user_report = 1' : 'user_report = 0';
			}

			// Join the where conditions together.
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

			$sql = "{$select_sql} {$where_sql} ORDER BY mr.date_created DESC";

			$sql       = apply_filters( 'bp_moderation_reports_sql', $sql, $moderation_id );
			$reporters = $wpdb->get_results( $sql ); // phpcs:ignore
			foreach ( $reporters as $key => $reporter ) {
				unset( $reporters[ $key ]->id );
				unset( $reporters[ $key ]->moderation_id );
				$reporters[ $key ]->user_id     = (int) $reporter->user_id;
				$reporters[ $key ]->category_id = (int) $reporter->category_id;
			}

			wp_cache_set( $moderation_id, $reporters, 'bp_moderation_reporters' );
		}

		return $reporters;
	}

	/**
	 * Append xProfile fullnames to an moderation/moderation data array.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $moderations Moderations/Moderations data array.
	 * @param string $user_key    User key name.
	 *
	 * @return array*
	 */
	protected static function append_user_fullnames( $moderations, $user_key = 'user_id' ) {

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
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $moderations Array of moderations.
	 *
	 * @return array $moderations Array of moderations.
	 */
	protected static function prefetch_object_data( $moderations ) {

		/**
		 * Filters inside prefetch_object_data method to aid in pre-fetching object data associated with moderation item.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $moderations Array of moderations.
		 */
		return apply_filters( 'bp_moderation_prefetch_object_data', $moderations );
	}

	/**
	 * Get specific moderation item id
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $item_id   Moderation item id.
	 * @param int $item_type Moderation item type.
	 *
	 * @return array|false|object|void
	 */
	public static function get_specific_moderation( $item_id, $item_type ) {
		global $wpdb;

		$bp = buddypress();

		$cache_key = 'bb_get_specific_moderation_' . $item_type . '_' . $item_id;
		$result    = wp_cache_get( $cache_key, 'bp_moderation' );

		if ( false === $result ) {
			$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name} ms WHERE ms.item_id = %d AND ms.item_type = %s", $item_id, $item_type ) ); // phpcs:ignore
			wp_cache_set( $cache_key, $result, 'bp_moderation' );
		}

		return ! empty( $result ) ? $result : false;
	}

	/** Static Methods ***************************************************/

	/**
	 * Hide Moderation entry
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function hide() {

		$this->hide_sitewide = 1;

		if ( $this->count <= 0 && $this->count_report <= 0 ) {
			$this->save();
		}

		$this->hide_related_content();
		bp_moderation_update_meta( $this->id, '_hide_by', get_current_user_id() );

		/**
		 * Fires after an moderation report item has been hide
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param BP_Moderation $this current class object.
		 */
		do_action( 'bp_moderation_after_hide', $this );

	}

	/**
	 * Save the moderation report to the database.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return WP_Error|bool True on success.
	 */
	public function save() {
		$this->id           = apply_filters_ref_array( 'bp_moderation_id_before_save', array( $this->id, &$this ) );
		$this->user_id      = apply_filters_ref_array( 'bp_moderation_user_id_before_save', array( $this->user_id, &$this ) );
		$this->item_id      = apply_filters_ref_array( 'bp_moderation_item_id_before_save', array( $this->item_id, &$this ) );
		$this->content      = apply_filters_ref_array( 'bp_moderation_content_before_save', array( $this->content, &$this ) );
		$this->item_type    = apply_filters_ref_array( 'bp_moderation_item_type_before_save', array( $this->item_type, &$this ) );
		$this->date_created = apply_filters_ref_array( 'bp_moderation_date_created_before_save', array( $this->date_created, &$this ) );
		$this->category_id  = apply_filters_ref_array( 'bp_moderation_category_id_before_save', array( $this->category_id, &$this ) );
		$this->blog_id      = apply_filters_ref_array( 'bp_moderation_blog_id_before_save', array( $this->blog_id, &$this ) );

		$this->date_created = empty( $this->date_created ) ? current_time( 'mysql' ) : $this->date_created;
		$this->last_updated = empty( $this->last_updated ) ? current_time( 'mysql' ) : $this->last_updated;
		$this->category_id  = isset( $this->category_id ) && 'other' !== $this->category_id ? $this->category_id : 0;
		$this->user_report  = isset( $this->user_report ) ? $this->user_report : 0;

		/**
		 * Fires before the current moderation report item gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param BP_Moderation $this Current instance of the moderation item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_moderation_before_save', array( &$this ) );

		if ( empty( $this->item_id ) || empty( $this->item_type ) ) {
			if ( 'bool' === $this->error_type ) {
				return false;
			} else {
				if ( empty( $this->item_id ) ) {
					$this->errors->add( 'bp_moderation_missing_item_id', esc_html__( 'Item ID field missing.', 'buddyboss' ) );
				} else {
					$this->errors->add( 'bp_moderation_missing_item_type', esc_html__( 'Item type field missing.', 'buddyboss' ) );
				}
			}
		}

		if ( 'wp_error' === $this->error_type && $this->errors->get_error_code() ) {
			return $this->errors;
		}

		// Get Moderation settings.
		$threshold          = false;
		$user_threshold     = false;
		$email_notification = false;
		$auto_hide          = false;

		if ( BP_Moderation_Members::$moderation_type === $this->item_type && ( bp_is_moderation_auto_suspend_enable() || bb_is_moderation_auto_suspend_report_enable() ) ) {
			$threshold          = bp_moderation_auto_suspend_threshold( 5 );
			$user_threshold     = bb_moderation_auto_suspend_report_threshold();
			$email_notification = bp_is_moderation_blocking_email_notification_enable();
		} elseif ( bp_is_moderation_auto_hide_enable( false, $this->item_type ) ) {
			$threshold          = bp_moderation_reporting_auto_hide_threshold( '5', $this->item_type );
			$email_notification = bp_is_moderation_reporting_email_notification_enable();
		}

		/**
		 * Check Content report already exist or not.
		 */
		$this->id        = self::check_any_moderation_exist( $this->item_id, $this->item_type, true );
		$this->report_id = self::check_moderation_report_exist( $this->id, $this->user_id, (bool) $this->user_report );

		/**
		 * IF any new Content reported then do some required actions
		 */
		if ( empty( $this->report_id ) || ( 0 === (int) $this->user_report && BP_Moderation_Members::$moderation_type === $this->item_type ) ) {

			// Update last update time as new reported added.
			$this->last_updated = current_time( 'mysql' );

			// Update count and check $threshold for auto hide/suspended and send email notification if auto hide/suspended.
			$this->count        = ! empty( $this->id ) ? (int) bp_moderation_get_meta( $this->id, '_count' ) : 0;
			$this->count_report = ! empty( $this->id ) ? (int) bp_moderation_get_meta( $this->id, '_count_user_reported' ) : 0;
			if ( BP_Moderation_Members::$moderation_type === $this->item_type && ! empty( $this->user_report ) ) {
				$this->count_report += 1;
			} else {
				$this->count += 1;
			}
			if ( ! empty( $threshold ) ) {
				if ( $this->count >= $threshold && empty( $this->hide_sitewide ) ) {
					$this->hide_sitewide = 1;
					$auto_hide           = true;
				}
				if ( BP_Moderation_Members::$moderation_type === $this->item_type && $this->count_report >= $user_threshold && empty( $this->hide_sitewide ) ) {
					$this->hide_sitewide = 1;
					$auto_hide           = true;
				}
			}
		}

		/**
		 * Manage Moderation report
		 */
		$result = $this->store();
		if ( empty( $result ) ) {
			return false;
		}

		/**
		 * Manage Moderation reporter data
		 */
		$result = $this->store_report();
		if ( empty( $result ) ) {
			return false;
		}

		if ( $auto_hide ) {
			// Content will be hide for all user.
			$this->hide();
			if ( ! empty( $email_notification ) ) {
				$this->send_emails();
			}
		} elseif ( BP_Moderation_Members::$moderation_type === $this->item_type && 0 === (int) $this->user_report ) {
			// Content will be hide when Blocked User for reported.
			$this->hide_related_content();
		}

		/**
		 * Fires after an moderation report item has been saved to the database.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param BP_Moderation $this Current instance of moderation item being saved. Passed by reference.
		 */
		do_action_ref_array( 'bp_moderation_after_save', array( &$this ) );

		return true;
	}

	/**
	 * Check moderation data exist for specific user or not
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $moderation_id Moderation report id.
	 * @param int $user_id       Moderation reporter id.
	 *
	 * @return false
	 */
	public static function check_moderation_report_exist( $moderation_id, $user_id, $user_report = false ) {
		global $wpdb;

		$bp = buddypress();

		if ( $user_report ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name_reports} mr WHERE mr.moderation_id = %d AND mr.user_id = %d and mr.user_report = 1", $moderation_id, $user_id ) ); // phpcs:ignore
		} else {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->moderation->table_name_reports} mr WHERE mr.moderation_id = %d AND mr.user_id = %d and mr.user_report = 0", $moderation_id, $user_id ) ); // phpcs:ignore
		}

		return is_numeric( $result ) ? (int) $result : false;
	}

	/**
	 * Store Moderation entry
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	public function store() {
		global $wpdb;

		$args = array(
			'item_id'      => $this->item_id,
			'item_type'    => $this->item_type,
			'user_report'  => $this->user_report,
			'last_updated' => $this->last_updated,
		);

		if (
			( in_array( $this->item_type, array( BP_Moderation_Members::$moderation_type_report, BP_Moderation_Members::$moderation_type ), true ) && empty( $this->user_report ) )
			|| ! in_array( $this->item_type, array( BP_Moderation_Members::$moderation_type_report, BP_Moderation_Members::$moderation_type ), true )
		) {
			$args['reported'] = 1;
		}

		// If we have an existing ID, update the moderation report item, otherwise insert it.
		if ( ! empty( $this->id ) ) {
			$q = BP_Core_Suspend::add_suspend( $args );
		} else {
			$args['blog_id'] = $this->blog_id;
			$q               = BP_Core_Suspend::add_suspend( $args );
		}

		if ( false === $q ) { // phpcs:ignore
			return false;
		}

		// If this is a new moderation report item, set the $id property.
		if ( empty( $this->id ) ) {
			$this->id = self::check_moderation_exist( $this->item_id, $this->item_type, true, (bool) $this->user_report );
		}

		return true;
	}

	/**
	 * Store Moderation report entry
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return false
	 */
	public function store_report() {
		global $wpdb;

		$bp = buddypress();

		if ( ! empty( $this->report_id ) && BP_Moderation_Members::$moderation_type === $this->item_type && empty( $this->user_report ) ) {
			$q_report = $wpdb->prepare( "INSERT INTO {$bp->moderation->table_name_reports} ( moderation_id, user_id, content, date_created, category_id, user_report ) VALUES ( %d, %d, %s, %s, %d, %d )", $this->id, $this->user_id, $this->content, $this->date_created, $this->category_id, $this->user_report ); // phpcs:ignore

			bp_moderation_update_meta( $this->id, '_count', $this->count );
			bp_moderation_update_meta( $this->id, '_count_user_reported', $this->count_report );
		} elseif ( ! empty( $this->report_id ) ) {
			$q_report = $wpdb->prepare( "UPDATE {$bp->moderation->table_name_reports} SET content = %s, date_created = %s, category_id = %d, user_report = %d WHERE id = %d AND moderation_id = %d AND user_id = %d ", $this->content, $this->date_created, $this->category_id, $this->user_report, $this->report_id, $this->id, $this->user_id ); // phpcs:ignore
			bp_moderation_update_meta( $this->id, '_count', $this->count );
			bp_moderation_update_meta( $this->id, '_count_user_reported', $this->count_report );
		} else {
			$q_report = $wpdb->prepare( "INSERT INTO {$bp->moderation->table_name_reports} ( moderation_id, user_id, content, date_created, category_id, user_report ) VALUES ( %d, %d, %s, %s, %d, %d )", $this->id, $this->user_id, $this->content, $this->date_created, $this->category_id, $this->user_report ); // phpcs:ignore

			bp_moderation_update_meta( $this->id, '_count', $this->count );
			bp_moderation_update_meta( $this->id, '_count_user_reported', $this->count_report );
		}

		if ( false === $wpdb->query( $q_report ) ) { // phpcs:ignore
			return false;
		}

		// If this is a new moderation report data, set the $report_id property.
		if ( empty( $this->report_id ) ) {
			$this->report_id = $wpdb->insert_id;
		}

		return true;
	}

	/**
	 * Function to send email as per moderation settings
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool|BP_Email|WP_Error
	 */
	public function send_emails() {

		$admins = get_users(
			array(
				'role'   => 'administrator',
				'fields' => 'ID',
			)
		);

		if ( ! empty( $admins ) ) {

			$_GET['username_visible'] = true;

			if ( BP_Moderation_Members::$moderation_type === $this->item_type && bp_is_moderation_auto_suspend_enable() ) {

				$tokens = array(
					'user_name'      => bp_core_get_user_displayname( $this->item_id ),
					'times_blocked'  => $this->count,
					'times_reported' => $this->count_report,
					'member_link'    => BP_Moderation_Members::get_permalink( $this->item_id ),
					'report_link'    => add_query_arg(
						array(
							'page'         => 'bp-moderation',
							'mid'          => $this->item_id,
							'content_type' => $this->item_type,
							'action'       => 'view',
						),
						bp_get_admin_url( 'admin.php' )
					),
				);

				foreach ( $admins as $admin ) {
					bp_moderation_member_suspend_email( bp_core_get_user_email( $admin ), $tokens );
				}
			} elseif ( bp_is_moderation_auto_hide_enable( false, $this->item_type ) ) {

				$content_report_link = ( bp_is_moderation_member_blocking_enable() ) ? add_query_arg( array( 'tab' => 'reported-content' ), bp_get_admin_url( 'admin.php' ) ) : bp_get_admin_url( 'admin.php' );

				$user_ids = bp_moderation_get_content_owner_id( $this->item_id, $this->item_type );
				if ( ! is_array( $user_ids ) ) {
					$user_ids = array( $user_ids );
				}

				$content_owner = array();
				if ( ! empty( $user_ids ) ) {
					foreach ( $user_ids as $user_id ) {
						$content_owner[] = bp_core_get_user_displayname( $user_id );
					}
				}

				$tokens = array(
					'content_type'          => bp_moderation_get_content_type( $this->item_type ),
					'content_owner'         => implode( ', ', $content_owner ),
					'content_timesreported' => $this->count,
					'content_link'          => bp_moderation_get_permalink( $this->item_id, $this->item_type ),
					'content_reportlink'    => add_query_arg(
						array(
							'page'         => 'bp-moderation',
							'mid'          => $this->item_id,
							'content_type' => $this->item_type,
							'action'       => 'view',
						),
						$content_report_link
					),
				);

				foreach ( $admins as $admin ) {
					bp_moderation_content_hide_email( bp_core_get_user_email( $admin ), $tokens );
				}
			}

			unset( $_GET['username_visible'] );
		}
	}

	/**
	 * Hide related content of report entry
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function hide_related_content() {
		/**
		 * Add related content of reported item into hidden list
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param int   $item_id  item id.
		 * @param int   $hide_sitewide item hidden sitewide or user specific.
		 * @param array $args hide arguments.
		 */
		do_action( "bp_suspend_hide_{$this->item_type}", $this->item_id, $this->hide_sitewide, array() );
	}

	/**
	 * Unhide Moderation entry
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function unhide() {
		$this->hide_sitewide = 0;

		if ( ! empty( $this->report_id ) && ! is_admin() ) {
			$this->delete();
		} else {

			$this->unhide_related_content();
			bp_moderation_delete_meta( $this->id, '_hide_by' );
		}

		/**
		 * Fires after an moderation report item has been unhide
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param BP_Moderation $this current class object.
		 */
		do_action( 'bp_moderation_after_unhide', $this );
	}

	/**
	 * Function to delete Moderation.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool $force_all Should delete all reported entry.
	 *
	 * @return false|int
	 */
	public function delete( $force_all = false ) {
		global $wpdb;
		$bp = buddypress();

		$updated_row   = false;
		$delete_parent = $force_all;

		if ( ! empty( $this->report_id ) ) {
			$updated_row = $this->delete_report( $force_all );

			if ( 1 > $this->count && 1 > $this->count_report ) {
				$delete_parent = true;
			}
		}

		if ( $delete_parent ) {
			$updated_row = $wpdb->update( $bp->moderation->table_name, array( 'reported' => 0 ), array( 'id' => $this->id ) ); // phpcs:ignore
			self::delete_meta( $this->id );

			if ( ! empty( $updated_row ) ) {
				$this->id = null;
			}
		}

		if ( ! empty( $updated_row ) ) {
			$this->unhide_related_content( $force_all );
		}

		if ( 0 === $this->count ) {
			$wpdb->update( $bp->moderation->table_name, array( 'reported' => 0 ), array( 'id' => $this->id ) ); // phpcs:ignore
		}

		/**
		 * Fires after an moderation report item has been deleted to the database.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param BP_Moderation $this Current instance of moderation item being deleted. Passed by reference.
		 */
		do_action_ref_array( 'bb_moderation_after_delete', array( &$this ) );

		return ! empty( $updated_row );
	}

	/**
	 * Function to delete Moderation report.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool $force_all Should delete all reported entry.
	 *
	 * @return false|int
	 */
	public function delete_report( $force_all ) {
		global $wpdb;
		$bp = buddypress();

		if ( $force_all ) {
			$args = array( 'moderation_id' => $this->id );
		} else {
			$args = array( 'id' => $this->report_id );
		}

		$updated_row = $wpdb->delete( $bp->moderation->table_name_reports, $args ); // phpcs:ignore

		if ( ! empty( $updated_row ) ) {
			$this->report_id = null;
			if ( BP_Moderation_Members::$moderation_type === $this->item_type && ! empty( $this->user_report ) ) {
				$this->count_report -= 1;
			} else {
				$this->count -= 1;
			}

			if ( 0 <= $this->count ) {
				bp_moderation_update_meta( $this->id, '_count', $this->count );
			}
			if ( 0 <= $this->count_report ) {
				bp_moderation_update_meta( $this->id, '_count_user_reported', $this->count_report );
			}
		}

		return ! empty( $updated_row );
	}

	/**
	 * Unction to delete Moderation meta.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $moderation_id Moderation Report ID.
	 *
	 * @return bool
	 */
	public static function delete_meta( $moderation_id = 0 ) {
		global $wpdb;
		$bp = buddypress();

		if ( empty( $moderation_id ) ) {
			return;
		}

		$args        = array( 'moderation_id' => $moderation_id );
		$updated_row = $wpdb->delete( $bp->moderation->table_name_meta, $args ); // phpcs:ignore

		return ! empty( $updated_row );
	}

	/**
	 * Un-hide related content of report entry
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool $force_all Should delete all reported entry.
	 */
	public function unhide_related_content( $force_all = false ) {

		/**
		 * Remove related content of reported item from hidden list.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param int $item_id       item id
		 * @param int $hide_sitewide item hidden sitewide or user specific
		 * @param int $force_all     un-hide for all users
		 */
		do_action( "bp_suspend_unhide_{$this->item_type}", $this->item_id, $this->hide_sitewide, $force_all );
	}

	/**
	 * Delete record by moderation_id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $moderation_id Moderation moderation_id.
	 */
	public static function delete_moderation_by_id( $moderation_id = 0 ) {
		global $wpdb, $bp;

		$args = array( 'moderation_id' => $moderation_id );
		$wpdb->delete( $bp->moderation->table_name_reports, $args ); // phpcs:ignore

		self::delete_meta( $moderation_id );
	}
}
