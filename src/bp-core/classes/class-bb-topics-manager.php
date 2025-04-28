<?php
/**
 * BuddyBoss Platform Topics Manager.
 *
 * Handles database schema creation and CRUD operations for topics.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Activity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Topics data storage and operations.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Topics_Manager {

	/**
	 * Instance of this class.
	 *
	 * @since  BuddyBoss [BBVERSION]
	 *
	 * @var object
	 *
	 * @access private
	 */
	private static $instance = null;

	/**
	 * Table name for Topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $topics_table;

	/**
	 * Table name for Topic Relationships.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $topic_rel_table;

	/**
	 * Table name for Activity Topic Relationships.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $activity_topic_rel_table;

	/**
	 * Cache group for topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public static $topic_cache_group = 'bb_topics';

	/**
	 * WordPress Database instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Get the singleton instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_Topics_Manager The singleton instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$prefix     = bp_core_get_table_prefix();

		$this->topics_table             = $prefix . 'bb_topics';
		$this->topic_rel_table          = $prefix . 'bb_topic_relationships';
		$this->activity_topic_rel_table = $prefix . 'bb_activity_topic_relationship';

		$this->setup_hooks();
	}

	/**
	 * Setup hooks for logging actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function setup_hooks() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_bb_add_topic', array( $this, 'bb_add_topic_ajax' ) );
		add_action( 'wp_ajax_bb_edit_topic', array( $this, 'bb_edit_topic_ajax' ) );
		add_action( 'wp_ajax_bb_delete_topic', array( $this, 'bb_delete_topic_ajax' ) );
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function enqueue_scripts() {
		$bp  = buddypress();
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script(
			'bb-topics-manager',
			$bp->plugin_url . '/bp-core/js/bb-topics-manager' . $min . '.js',
			array(
				'jquery',
			),
			bp_get_version(),
			true
		);
		wp_localize_script(
			'bb-topics-manager',
			'bbTopicsManagerVars',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'delete_topic_confirm' => esc_html__( 'Are you sure you want to delete this topic?', 'buddyboss' ),
				'topics_limit'         => $this->bb_topics_limit(),
			)
		);
	}

	/**
	 * Create the necessary database tables.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function create_tables() {
		$sql             = array();
		$wpdb            = $GLOBALS['wpdb'];
		$charset_collate = $wpdb->get_charset_collate();

		$topics_table             = $this->topics_table;
		$topic_rel_table          = $this->topic_rel_table;
		$activity_topic_rel_table = $this->activity_topic_rel_table;

		$has_topics_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $topics_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_topics_table ) ) {

			$sql[] = "CREATE TABLE {$topics_table} (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				slug VARCHAR(255) NOT NULL,
				PRIMARY KEY (id),
				KEY name (name),
				KEY slug (slug)
			) $charset_collate;";
		}

		$has_topic_rel_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $topic_rel_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_topic_rel_table ) ) {

			$sql[] = "CREATE TABLE {$topic_rel_table} (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT,
				item_id BIGINT(20) UNSIGNED NOT NULL,
				item_type VARCHAR(10) NOT NULL,
				topic_id BIGINT(20) UNSIGNED NOT NULL,
				user_id BIGINT(20) UNSIGNED NOT NULL,
				permission_type VARCHAR(20) NOT NULL,
				permission_data LONGTEXT NULL,
				menu_order INT NOT NULL DEFAULT 0,
				date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY item_id (item_id),
				KEY item_type (item_type),
				KEY topic_id (topic_id),
				KEY user_id (user_id),
				KEY permission_type (permission_type),
				KEY date_created (date_created),
				KEY date_updated (date_updated)
			) $charset_collate;";
		}

		$has_activity_topic_rel_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $activity_topic_rel_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_activity_topic_rel_table ) ) {

			$sql[] = "CREATE TABLE {$activity_topic_rel_table} (
				id BIGINT(20) UNSIGNED AUTO_INCREMENT,
				topic_id BIGINT(20) UNSIGNED NOT NULL,
				activity_id BIGINT(20) UNSIGNED NOT NULL,
				component VARCHAR(20) NOT NULL,
				item_id BIGINT(20) UNSIGNED NOT NULL,
				date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY topic_id (topic_id),
				KEY activity_id (activity_id),
				KEY component (component),
				KEY item_id (item_id),
				KEY date_created (date_created),
				KEY date_updated (date_updated)
			) $charset_collate;";
		}

		if ( ! empty( $sql ) ) {
			// Ensure that dbDelta() is defined.
			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			dbDelta( $sql );
		}
	}

	/**
	 * Add a new global topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_add_topic_ajax() {
		check_ajax_referer( 'bb_add_topic', 'nonce' );

		$name              = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug              = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$permission_type   = isset( $_POST['permission_type'] ) ? sanitize_text_field( wp_unslash( $_POST['permission_type'] ) ) : 'anyone';
		$existing_topic_id = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;
		$item_id           = isset( $_POST['item_id'] ) ? absint( wp_unslash( $_POST['item_id'] ) ) : 0;
		$item_type         = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : 'activity';

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
		} else {
			$slug = sanitize_title( $slug );
		}

		if ( $this->bb_get_topic( 'slug', $slug ) ) {
			wp_send_json_error( array( 'error' => __( 'This topic name is already in use. Please enter a unique topic name.', 'buddyboss' ) ) );
		}

		if ( empty( $existing_topic_id ) ) {
			$topic_data = $this->bb_add_topic(
				array(
					'name'            => $name,
					'slug'            => $slug,
					'permission_type' => $permission_type,
					'item_id'         => $item_id,
					'item_type'       => $item_type,
				)
			);
		} else {
			$topic_data = $this->bb_update_topic(
				$existing_topic_id,
				array(
					'name'            => $name,
					'slug'            => $slug,
					'permission_type' => $permission_type,
				)
			);
		}

		if ( ! $topic_data ) {
			wp_send_json_error( array( 'error' => __( 'Failed to add topic.', 'buddyboss' ) ) );
		}

		wp_send_json_success( array( 'topic_id' => $topic_data->id ) );
	}

	/**
	 * Add a new global topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 * Array of arguments for adding a topic.
	 *
	 * @type string $name Required. The name of the topic.
	 * @type string $slug Optional. The slug for the topic. Auto-generated if empty.
	 * }
	 *
	 * @return int|WP_Error Topic ID on success, WP_Error on failure.
	 */
	public function bb_add_topic( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'name'       => '',
				'slug'       => '',
				'error_type' => 'bool',
			)
		);

		if ( empty( $r['name'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_name_required', __( 'Topic name is required.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		if ( empty( $r['slug'] ) ) {
			$r['slug'] = sanitize_title( $r['name'] );
		} else {
			$r['slug'] = sanitize_title( $r['slug'] );
		}

		// Check if slug already exists.
		if ( $this->bb_get_topic( 'slug', $r['slug'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_duplicate_slug', __( 'This topic name is already in use. Please enter a unique topic name.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		// Check if we've reached the maximum number of topics (20).
		if ( $this->bb_topics_limit_reached() ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_limit_reached', __( 'Maximum number of topics (20) has been reached.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		// Prepare data for insertion.
		$data   = array(
			'name' => sanitize_text_field( $r['name'] ),
			'slug' => $r['slug'],
		);
		$format = array( '%s', '%s' );

		// Use the updated table name property.
		$inserted = $this->wpdb->insert( $this->topics_table, $data, $format );
		error_log( print_r( $this->wpdb->last_error, true ) );

		if ( ! $inserted ) {
			return new WP_Error( 'bb_topic_db_insert_error', $this->wpdb->last_error );
		}

		$topic_id = $this->wpdb->insert_id;

		if ( $topic_id ) {
			$this->bb_add_topic_relationship(
				array(
					'topic_id'        => $topic_id,
					'permission_type' => $r['permission_type'],
					'item_id'         => $r['item_id'],
					'item_type'       => $r['item_type'],
				)
			);
		}

		/**
		 * Fires after a topic has been added.
		 *
		 * @param int   $topic_id The ID of the topic added.
		 * @param array $args     The arguments used to add the topic.
		 */
		do_action( 'bb_topic_added', $topic_id, $r );

		unset( $r, $data, $format, $inserted );

		return $this->bb_get_topic( 'id', $topic_id );
	}

	/**
	 * Add a new topic relationship.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 * Array of arguments for adding a topic relationship.
	 *
	 * @type int    $topic_id       The ID of the topic.
	 * @type string $permission_type The permission type.
	 * @type int    $item_id         The ID of the item.
	 * @type string $item_type       The type of item.
	 * @type int    $user_id         The ID of the user.
	 * @type array  $permission_data The permission data.
	 * @type int    $menu_order      The menu order.
	 * @type string $date_created    The date created.
	 * @type string $date_updated    The date updated.
	 * }
	 */
	public function bb_add_topic_relationship( $args ) {
		$r = bp_parse_args(
			$args,
			array(
				'topic_id'        => 0,
				'permission_type' => 'anyone',
				'item_id'         => 0,
				'item_type'       => 'user',
				'user_id'         => bp_loggedin_user_id(),
				'permission_data' => null,
				'menu_order'      => 0,
				'date_created'    => current_time( 'mysql' ),
				'date_updated'    => current_time( 'mysql' ),
				'error_type'      => 'bool',
			)
		);

		/**
		 * Fires before a topic relationship has been added.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_added', $r );

		$inserted = $this->wpdb->insert(
			$this->topic_rel_table,
			array(
				'topic_id'        => $r['topic_id'],
				'permission_type' => $r['permission_type'],
				'item_id'         => $r['item_id'],
				'item_type'       => $r['item_type'],
				'user_id'         => $r['user_id'],
				'permission_data' => $r['permission_data'],
				'menu_order'      => $r['menu_order'],
				'date_created'    => $r['date_created'],
				'date_updated'    => $r['date_updated'],
			),
			array( '%d', '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%s' )
		);

		if ( ! $inserted ) {
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error( 'bb_topic_relationship_db_insert_error', $this->wpdb->last_error );
			}

			return false;
		}

		/**
		 * Fires after a topic relationship has been added.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args The arguments used to add the topic relationship.
		 */
		do_action( 'bb_topic_relationship_added', $r );
	}

	/**
	 * Get a single topic by field (id or slug).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $field The field to query by ('id' or 'slug').
	 * @param mixed  $value The value to search for.
	 *
	 * @return object|null Topic object on success, null on failure.
	 */
	public function bb_get_topic( $field, $value ) {

		if ( ! in_array( $field, array( 'id', 'slug' ), true ) ) {
			return null;
		}

		if ( 'id' === $field ) {
			$value = absint( $value );
			if ( ! $value ) {
				return null;
			}
			$cache_key = 'bb_topic_id_' . $value;
		} else {
			$value = sanitize_title( $value );
			if ( empty( $value ) ) {
				return null;
			}
			$cache_key = 'bb_topic_slug_' . $value;
		}

		$topic = wp_cache_get( $cache_key, self::$topic_cache_group );
		if ( false !== $topic ) {
			return $topic;
		}

		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->topics_table} WHERE {$field} = %s",
			$value
		);

		$topic = $this->wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $topic ) {
			wp_cache_set( $cache_key, $topic, self::$topic_cache_group );
		}

		return $topic;
	}

	/**
	 * Get multiple topics based on arguments.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args    {
	 *                       Array of query arguments.
	 *
	 * @type int    $number  Number of topics to retrieve. Default -1 (all).
	 * @type int    $offset  Number of topics to skip. Default 0.
	 * @type string $orderby Field to order by ('id', 'name', 'slug', 'menu_order', 'date_created'). Default 'menu_order'.
	 * @type string $order   Order direction ('ASC', 'DESC'). Default 'ASC'.
	 * @type string $search  Search term to match against name or slug.
	 * @type string $scope   Filter by topic_scope ('global').
	 * @type array  $include Array of topic IDs to include.
	 * @type array  $exclude Array of topic IDs to exclude.
	 *                       }
	 * @return array Array of topic objects.
	 */
	public function bb_get_topics( $args = array() ) {
		$r = bp_parse_args(
			$args,
			array(
				'per_page'        => -1, // Retrieve all by default.
				'paged'           => 1,
				'orderby'         => 'menu_order',
				'order'           => 'ASC',
				'search'          => '',
				'item_id'         => 0,
				'item_type'       => '',
				'permission_type' => '',
				'user_id'         => 0,
				'include'         => array(),
				'exclude'         => array(),
				'count_total'     => false,
				'fields'          => 'all', // Fields to include.
				'error_type'      => 'bool',
			)
		);

		// Select conditions.
		$select_sql = 'SELECT t.id';

		$from_sql = ' FROM ' . $this->topic_rel_table . ' tr';

		$from_sql .= ' LEFT JOIN ' . $this->topics_table . ' t ON t.id = tr.topic_id';

		// Where conditions.
		$where_conditions = array();

		// Sorting.
		$sort = bp_esc_sql_order( $r['order'] );
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		// Validate orderby parameter.
		$allowed_orderby = array( 'id', 'name', 'date_created', 'menu_order', 'date_updated' );
		if ( ! in_array( $r['orderby'], $allowed_orderby, true ) ) {
			$r['orderby'] = 'menu_order';
		}

		$order_by = 'tr.' . $r['orderby'];

		$where_conditions[] = $this->wpdb->prepare( 'tr.item_id = %d', $r['item_id'] );

		// id.
		if ( ! empty( $r['id'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id = %d', $r['id'] );
		}

		// user_id.
		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.user_id = %d', $r['user_id'] );
		}

		// search.
		if ( ! empty( $r['search'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 't.name LIKE %s', '%' . $this->wpdb->esc_like( $r['search'] ) . '%' );
		}

		// include.
		if ( ! empty( $r['include'] ) ) {
			$include_ids        = implode( ',', array_map( 'absint', $r['include'] ) );
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id IN ( %s )', $include_ids );
		}

		// exclude.
		if ( ! empty( $r['exclude'] ) ) {
			$exclude_ids        = implode( ',', array_map( 'absint', $r['exclude'] ) );
			$where_conditions[] = $this->wpdb->prepare( 'tr.topic_id NOT IN ( %s )', $exclude_ids );
		}

		// item_type.
		if ( ! empty( $r['item_type'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.item_type = %s', $r['item_type'] );
		}

		// permission_type.
		if ( ! empty( $r['permission_type'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.permission_type = %s', $r['permission_type'] );
		}

		// user_id.
		if ( ! empty( $r['user_id'] ) ) {
			$where_conditions[] = $this->wpdb->prepare( 'tr.user_id = %d', $r['user_id'] );
		}

		/**
		 * Filters the MySQL WHERE conditions for the activity topics get sql method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at the point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bb_get_topics_where_conditions', $where_conditions, $r, $select_sql, $from_sql );

		// Join the where conditions together.
		$where_sql = '';
		if ( ! empty( $where_conditions ) ) {
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );
		}

		// Sanitize page and per_page parameters.
		$page       = absint( $r['paged'] );
		$per_page   = $r['per_page'];
		$pagination = '';
		if ( ! empty( $per_page ) && ! empty( $page ) && - 1 !== $per_page ) {
			$start_val = intval( ( $page - 1 ) * $per_page );
			if ( ! empty( $where_conditions['before'] ) ) {
				$start_val = 0;
				unset( $where_conditions['before'] );
			}
			$pagination = $this->wpdb->prepare( 'LIMIT %d, %d', $start_val, intval( $per_page ) );
		}

		// Query first for poll vote IDs.
		$topic_sql = "{$select_sql} {$from_sql} {$where_sql} ORDER BY {$order_by} {$sort} {$pagination}";

		$retval = array(
			'topics' => null,
			'total'  => null,
		);

		/**
		 * Filters the poll votes data MySQL statement.
		 *
		 * @since 2.6.00
		 *
		 * @param string $poll_votes_sql MySQL's statement used to query for poll votes.
		 * @param array  $r              Array of arguments passed into method.
		 */
		$topic_sql = apply_filters( 'bb_get_topics_sql', $topic_sql, $r );

		$cached = bp_core_get_incremented_cache( $topic_sql, self::$topic_cache_group );
		if ( false === $cached ) {
			$topic_ids = $this->wpdb->get_col( $topic_sql ); // phpcs:ignore
			bp_core_set_incremented_cache( $topic_sql, self::$topic_cache_group, $topic_ids );
		} else {
			$topic_ids = $cached;
		}

		if ( 'id' === $r['fields'] ) {
			// We only want the IDs.
			$topic_data = array_map( 'intval', $topic_ids );
		} else {
			$uncached_ids = bp_get_non_cached_ids( $topic_ids, self::$topic_cache_group );
			if ( ! empty( $uncached_ids ) ) {
				$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

				// phpcs:ignore
				$queried_data = $this->wpdb->get_results( 'SELECT t.*, tr.* FROM ' . $this->topics_table . ' t LEFT JOIN ' . $this->topic_rel_table . ' tr ON t.id = tr.topic_id WHERE t.id IN (' . $uncached_ids_sql . ')', ARRAY_A );

				foreach ( (array) $queried_data as $topic_data ) {
					wp_cache_set( $topic_data['id'], $topic_data, self::$topic_cache_group );
				}
			}

			$topic_data = array();
			foreach ( $topic_ids as $id ) {
				$topic = wp_cache_get( $id, self::$topic_cache_group );
				if ( ! empty( $topic ) ) {
					$topic_data[] = (object) $topic;
				}
			}

			if ( 'all' !== $r['fields'] ) {
				$topic_data = array_unique( array_column( $topic_data, $r['fields'] ) );
			}
		}

		$retval['topics'] = $topic_data;

		if ( ! empty( $r['count_total'] ) ) {

			/**
			 * Filters the total activity topics MySQL statement.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string $value     MySQL's statement used to query for total activity topics.
			 * @param string $where_sql MySQL WHERE statement portion.
			 */
			$total_activity_topic_sql = apply_filters( 'bb_total_topic_sql', 'SELECT count(DISTINCT tr.topic_id) FROM ' . $this->topic_rel_table . ' tr ' . $where_sql, $where_sql );
			$cached                   = bp_core_get_incremented_cache( $total_activity_topic_sql, self::$topic_cache_group );
			if ( false === $cached ) {
				// phpcs:ignore
				$total_activity_topics = $this->wpdb->get_var( $total_activity_topic_sql );
				bp_core_set_incremented_cache( $total_activity_topic_sql, self::$topic_cache_group, $total_activity_topics );
			} else {
				$total_activity_topics = $cached;
			}

			$retval['total'] = $total_activity_topics;
		}

		unset( $r, $select_sql, $from_sql, $where_conditions, $where_sql, $pagination, $topic_sql, $cached, $topic_ids, $uncached_ids, $uncached_ids_sql, $queried_data, $topic_data );

		return $retval;
	}

	/**
	 * Edit an existing topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_edit_topic_ajax() {

		check_ajax_referer( 'bb_edit_topic', 'nonce' );

		$topic_id = isset( $_POST['topic_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ) ) : 0;

		$topic = $this->bb_get_topic( 'id', $topic_id );

		if ( ! $topic ) {
			wp_send_json_error( array( 'error' => __( 'Topic not found.', 'buddyboss' ) ) );
		}

		wp_send_json_success( array( 'topic' => $topic ) );
	}

	/**
	 * Update an existing topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int   $topic_id The ID of the topic to update.
	 * @param array $args     Array of arguments to update (same keys as add_topic, omitting creator_id and
	 *                        topic_scope).
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function bb_update_topic( $topic_id, $args ) {

		$r = bp_parse_args(
			$args,
			array(
				'id'              => 0,
				'name'            => '',
				'slug'            => '',
				'scope'           => '',
				'permission_type' => '',
				'permission_data' => null,
				'menu_order'      => 0,
				'error_type'      => 'bool',
			)
		);

		if ( empty( $r['id'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_id_required', __( 'The topic ID is required to update topic.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		if ( empty( $r['name'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_name_required', __( 'The topic name is required to update topic.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		$topic_id = absint( $topic_id );
		$topic    = $this->bb_get_topic( 'id', $topic_id );

		if ( ! $topic ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_invalid_topic_id', __( 'Invalid topic ID.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		// Prepare data for update.
		$data   = array();
		$format = array();

		if ( isset( $r['name'] ) ) {
			$data['name'] = sanitize_text_field( $r['name'] );
			$data['slug'] = sanitize_title( $r['name'] );

			// Check if new slug conflicts with another topic.
			$existing = $this->bb_get_topic( 'slug', $data['slug'] );
			if ( $existing && $existing->id !== $topic_id ) {
				if ( 'wp_error' === $r['error_type'] ) {
					unset( $r );

					return new WP_Error( 'bb_topic_duplicate_slug', __( 'This topic name is already in use. Please enter a unique topic name.', 'buddyboss' ) );
				}

				unset( $r );

				return false;
			}
			$format[] = '%s';
			$format[] = '%s';
		}

		if ( isset( $r['scope'] ) ) {
			$data['scope'] = sanitize_key( $r['scope'] );
			$format[]      = '%s';
		}

		if ( isset( $r['permission_type'] ) ) {
			$data['permission_type'] = sanitize_key( $r['permission_type'] );
			$format[]                = '%s';
		}

		if ( isset( $r['permission_data'] ) ) { // Use isset to allow setting to null.
			$data['permission_data'] = is_null( $r['permission_data'] ) ? null : wp_json_encode( $r['permission_data'] );
			$format[]                = '%s';
		}

		if ( isset( $r['menu_order'] ) ) {
			$data['menu_order'] = absint( $r['menu_order'] );
			$format[]           = '%d';
		}

		if ( empty( $data ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_no_data_provided', __( 'No data provided to update.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		// Use the updated table name property.
		$updated = $this->wpdb->update(
			$this->topics_table,
			$data,
			array( 'id' => $topic_id ),
			$format,
			array( '%d' )
		);

		if ( false === $updated ) {
			if ( 'wp_error' === $r['error_type'] ) {
				unset( $r );

				return new WP_Error( 'bb_topic_db_update_error', __( 'There is an error while updating the topic.', 'buddyboss' ) );
			}

			unset( $r );

			return false;
		}

		/**
		 * Fires after a topic has been updated.
		 *
		 * @param int   $topic_id The ID of the topic updated.
		 * @param array $r        The arguments used to update the topic.
		 */
		do_action( 'bb_topic_updated', $topic_id, $r );

		unset( $r, $data, $format, $updated, $topic );

		return $this->bb_get_topic( 'id', $topic_id );
	}

	/**
	 * Delete an existing topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_delete_topic_ajax() {
		check_ajax_referer( 'bb_delete_topic', 'nonce' );

		$topic_id = isset( $_POST['topic_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ) ) : 0;

		$deleted = $this->bb_delete_topic( $topic_id );

		if ( is_wp_error( $deleted ) ) {
			wp_send_json_error( array( 'error' => $deleted->get_error_message() ) );
		}

		wp_send_json_success();
	}

	/**
	 * Delete a topic and its associated relationships.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $topic_id The ID of the topic to delete.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function bb_delete_topic( $topic_id ) {

		if ( empty( $topic_id ) ) {
			return false;
		}

		$topic_id = absint( $topic_id );
		if ( ! $this->bb_get_topic( 'id', $topic_id ) ) {
			return false;
		}

		/**
		 * Fires before a topic is deleted.
		 *
		 * @param int $topic_id The ID of the topic being deleted.
		 */
		do_action( 'bb_topic_relationship_before_delete', $topic_id );

		// 1. Delete activity relationships.
		$deleted_rels = $this->wpdb->delete( $this->activity_topic_rel_table, array( 'topic_id' => $topic_id ), array( '%d' ) );
		if ( false === $deleted_rels ) {
			return false;
		}

		do_action( 'bb_topic_relationship_after_deleted', $topic_id );

		// 3. Delete the topic itself.
		$deleted_topic = $this->wpdb->delete( $this->topics_table, array( 'id' => $topic_id ), array( '%d' ) );
		if ( ! $deleted_topic ) { // delete returns number of rows deleted, 0 is possible but false is error.
			return false;
		}

		/**
		 * Fires after a topic has been deleted.
		 *
		 * @param int $topic_id The ID of the topic that was deleted.
		 */
		do_action( 'bb_topic_deleted', $topic_id );

		unset( $topic_id, $deleted_rels, $deleted_topic );

		return true;
	}

	/**
	 * Check if the maximum number of topics has been reached.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if the maximum number of topics has been reached, false otherwise.
	 */
	public function bb_topics_limit_reached() {
		$topics_count = $this->bb_get_topics(
			array(
				'per_page'    => 1,
				'count_total' => true,
			)
		);
		return is_array( $topics_count ) && isset( $topics_count['total'] ) ? $topics_count['total'] >= $this->bb_topics_limit() : false;
	}

	/**
	 * Limit the number of topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int The maximum number of topics.
	 */
	public function bb_topics_limit() {
		return 20;
	}
}
