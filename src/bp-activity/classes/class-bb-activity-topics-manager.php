<?php
/**
 * BuddyBoss Platform Activity Topics Manager.
 *
 * Handles database schema creation and CRUD operations for activity topics.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Activity
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Activity Topics data storage and operations.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Activity_Topics_Manager {

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
	 * Table name for Activity Topics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $topics_table;

	/**
	 * Table name for Activity Topic Relationships.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public $activity_rel_table;

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
	 * @return BB_Activity_Topics_Manager The singleton instance.
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

		$this->topics_table       = $prefix . 'bb_activity_topics';
		$this->activity_rel_table = $prefix . 'bb_activity_topic_relationship';

		$this->setup_hooks();
	}

	/**
	 * Setup hooks for logging actions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function setup_hooks() {
		add_action( 'wp_ajax_bb_add_activity_topic', array( $this, 'bb_add_activity_topic_ajax' ) );
		add_action( 'wp_ajax_bb_edit_activity_topic', array( $this, 'bb_edit_activity_topic_ajax' ) );
		add_action( 'wp_ajax_bb_delete_activity_topic', array( $this, 'bb_delete_activity_topic_ajax' ) );
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

		$topics_table       = $this->topics_table;
		$activity_rel_table = $this->activity_rel_table;

		$has_topics_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $topics_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_topics_table ) ) {

			$sql[] = "CREATE TABLE {$topics_table} (
				id BIGINT UNSIGNED AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				slug VARCHAR(255) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				scope VARCHAR(10) NOT NULL DEFAULT 'global',
				permission_type VARCHAR(20) NOT NULL DEFAULT 'anyone',
				permission_data TEXT NULL,
				menu_order INT NOT NULL DEFAULT 0,
				date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY name (name),
				KEY slug (slug),
				KEY user_id (user_id),
				KEY scope (scope),
				KEY permission_type (permission_type),
				KEY date_created (date_created),
				KEY date_updated (date_updated)
			) $charset_collate;";
		}

		$has_activity_rel_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $activity_rel_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $has_activity_rel_table ) ) {

			$sql[] = "CREATE TABLE {$activity_rel_table} (
				id BIGINT UNSIGNED AUTO_INCREMENT,
				activity_id BIGINT UNSIGNED NOT NULL,
				topic_id BIGINT UNSIGNED NOT NULL,
				date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY activity_id (activity_id),
				KEY topic_id (topic_id),
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
	public function bb_add_activity_topic_ajax() {
		check_ajax_referer( 'bb_add_activity_topic', 'nonce' );

		$name            = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug            = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$permission_type = isset( $_POST['permission_type'] ) ? sanitize_text_field( wp_unslash( $_POST['permission_type'] ) ) : 'anyone';
		$topic_id        = isset( $_POST['topic_id'] ) ? absint( wp_unslash( $_POST['topic_id'] ) ) : 0;

		if ( empty( $topic_id ) ) {
			$topic_id = $this->bb_add_activity_topic(
				array(
					'name'            => $name,
					'slug'            => $slug,
					'permission_type' => $permission_type,
				)
			);
		} else {
			$topic_id = $this->bb_update_activity_topic(
				$topic_id,
				array(
					'name'            => $name,
					'slug'            => $slug,
					'permission_type' => $permission_type,
				)
			);
		}

		if ( is_wp_error( $topic_id ) ) {
			wp_send_json_error( array( 'error' => $topic_id->get_error_message() ) );
		}

		wp_send_json_success( array( 'topic_id' => $topic_id ) );
	}

	/**
	 * Add a new global topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args                   {
	 *                                      Array of arguments for adding a topic.
	 *
	 * @type string $name                   Required. The name of the topic.
	 * @type string $slug                   Optional. The slug for the topic. Auto-generated if empty.
	 * @type int    $creator_id             Optional. User ID of the creator. Defaults to current user.
	 * @type string $global_permission_type Optional. Permission type ('anyone', 'profile_types', 'roles',
	 *       'admin_mod'). Default 'anyone'.
	 * @type mixed  $global_permission_data Optional. Data for permissions (e.g., array of profile type IDs or role
	 *       slugs).
	 *                                      }
	 * @return int|WP_Error Topic ID on success, WP_Error on failure.
	 */
	public function bb_add_activity_topic( $args ) {
		$defaults = array(
			'name'            => '',
			'slug'            => '',
			'user_id'         => bp_loggedin_user_id(),
			'permission_type' => 'anyone',
			'permission_data' => null,
			'scope'           => 'global',
			'menu_order'      => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		// Validation.
		if ( empty( $args['name'] ) ) {
			return new WP_Error( 'bb_activity_topic_name_required', __( 'Topic name is required.', 'buddyboss' ) );
		}

		if ( empty( $args['slug'] ) ) {
			$args['slug'] = sanitize_title( $args['name'] );
		} else {
			$args['slug'] = sanitize_title( $args['slug'] );
		}

		// Check if slug already exists.
		if ( $this->bb_get_activity_topic( 'slug', $args['slug'] ) ) {
			return new WP_Error( 'bb_activity_topic_duplicate_slug', __( 'A topic with this slug already exists.', 'buddyboss' ) );
		}

		if ( 0 === $args['menu_order'] ) {
			$highest_order      = $this->wpdb->get_var( "SELECT MAX(menu_order) FROM {$this->topics_table}" );
			$args['menu_order'] = (int) $highest_order + 1;
		}

		// Prepare data for insertion.
		$data   = array(
			'name'            => sanitize_text_field( $args['name'] ),
			'slug'            => $args['slug'],
			'user_id'         => absint( $args['user_id'] ),
			'permission_type' => sanitize_key( $args['permission_type'] ),
			'permission_data' => is_null( $args['permission_data'] ) ? null : wp_json_encode( $args['permission_data'] ),
			'scope'           => sanitize_key( $args['scope'] ),
			'menu_order'      => absint( $args['menu_order'] ),
			'date_created'    => current_time( 'mysql' ),
			'date_updated'    => current_time( 'mysql' ),
		);
		$format = array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s' );

		// Use the updated table name property.
		$inserted = $this->wpdb->insert( $this->topics_table, $data, $format );

		if ( ! $inserted ) {
			return new WP_Error( 'bb_activity_topic_db_insert_error', $this->wpdb->last_error );
		}

		$topic_id = $this->wpdb->insert_id;

		/**
		 * Fires after a topic has been added.
		 *
		 * @param int   $topic_id The ID of the topic added.
		 * @param array $args     The arguments used to add the topic.
		 */
		do_action( 'bb_activity_topic_added', $topic_id, $args );

		return $topic_id;
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
	public function bb_get_activity_topic( $field, $value ) {
		// Use updated table name property in queries.
		if ( 'id' === $field ) {
			$value = absint( $value );
			if ( ! $value ) {
				return null;
			}
			$sql = $this->wpdb->prepare( "SELECT * FROM {$this->topics_table} WHERE id = %d", $value );
		} elseif ( 'slug' === $field ) {
			$value = sanitize_title( $value );
			if ( empty( $value ) ) {
				return null;
			}
			$sql = $this->wpdb->prepare( "SELECT * FROM {$this->topics_table} WHERE slug = %s", $value );
		} else {
			return null; // Invalid field.
		}

		$topic = $this->wpdb->get_row( $sql );

		// Decode JSON permission data.
		if ( $topic && ! empty( $topic->permission_data ) ) {
			$topic->permission_data = json_decode( $topic->permission_data, true );
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
	 * @type string $orderby Field to order by ('id', 'name', 'slug', 'menu_order', 'date_created'). Default 'name'.
	 * @type string $order   Order direction ('ASC', 'DESC'). Default 'ASC'.
	 * @type string $search  Search term to match against name or slug.
	 * @type string $scope   Filter by topic_scope ('global').
	 * @type array  $include Array of topic IDs to include.
	 * @type array  $exclude Array of topic IDs to exclude.
	 *                       }
	 * @return array Array of topic objects.
	 */
	public function bb_get_activity_topics( $args = array() ) {
		$defaults = array(
			'number'  => -1, // Retrieve all by default.
			'offset'  => 0,
			'orderby' => 'name',
			'order'   => 'ASC',
			'search'  => '',
			'scope'   => '', // e.g., 'global'.
			'include' => array(),
			'exclude' => array(),
		);
		$args     = wp_parse_args( $args, $defaults );

		// Use updated table name property.
		$sql       = "SELECT * FROM {$this->topics_table}";
		$where     = array();
		$limits    = '';
		$order_sql = '';

		// WHERE clauses.
		if ( ! empty( $args['scope'] ) ) {
			$where[] = $this->wpdb->prepare( 'scope = %s', sanitize_key( $args['scope'] ) );
		}
		if ( ! empty( $args['search'] ) ) {
			$search_term = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$where[]     = $this->wpdb->prepare( '(name LIKE %s OR slug LIKE %s)', $search_term, $search_term );
		}
		if ( ! empty( $args['include'] ) ) {
			$include_ids = implode( ',', array_map( 'absint', $args['include'] ) );
			$where[]     = "id IN ({$include_ids})";
		}
		if ( ! empty( $args['exclude'] ) ) {
			$exclude_ids = implode( ',', array_map( 'absint', $args['exclude'] ) );
			$where[]     = "id NOT IN ({$exclude_ids})";
		}

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$allowed_orderby = array( 'id', 'name', 'slug', 'menu_order', 'date_created' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
		$order           = ( 'DESC' === strtoupper( $args['order'] ) ) ? 'DESC' : 'ASC';
		$order_sql       = " ORDER BY {$orderby} {$order}";

		if ( $args['number'] > 0 ) {
			if ( $args['offset'] > 0 ) {
				$limits = $this->wpdb->prepare( ' LIMIT %d, %d', $args['offset'], $args['number'] );
			} else {
				$limits = $this->wpdb->prepare( ' LIMIT %d', $args['number'] );
			}
		}

		$sql .= $order_sql . $limits;

		$results = $this->wpdb->get_results( $sql );

		// Decode JSON permission data.
		if ( $results ) {
			foreach ( $results as $topic ) {
				if ( ! empty( $topic->permission_data ) ) {
					$topic->permission_data = json_decode( $topic->permission_data, true );
				}
			}
		}

		return $results ? $results : array();
	}

	/**
	 * Edit an existing topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_edit_activity_topic_ajax() {
		check_ajax_referer( 'bb_edit_activity_topic', 'nonce' );

		$topic_id = isset( $_POST['topic_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ) ) : 0;

		$topic = $this->bb_get_activity_topic( 'id', $topic_id );

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
	public function bb_update_activity_topic( $topic_id, $args ) {
		$topic_id = absint( $topic_id );
		$topic    = $this->bb_get_activity_topic( 'id', $topic_id );

		if ( ! $topic ) {
			return new WP_Error( 'bb_activity_invalid_topic_id', __( 'Invalid topic ID.', 'buddyboss' ) );
		}

		// Prepare data for update.
		$data   = array();
		$format = array();

		if ( isset( $args['name'] ) ) {
			if ( empty( $args['name'] ) ) {
				return new WP_Error( 'bb_activity_topic_name_required', __( 'Topic name cannot be empty.', 'buddyboss' ) );
			}
			$data['name'] = sanitize_text_field( $args['name'] );
			$format[]     = '%s';
		}

		if ( isset( $args['slug'] ) ) {
			$slug = sanitize_title( $args['slug'] );
			if ( empty( $slug ) ) {
				if ( isset( $data['name'] ) ) {
					$slug = sanitize_title( $data['name'] );
				} else {
					return new WP_Error( 'bb_activity_invalid_slug', __( 'Invalid topic slug.', 'buddyboss' ) );
				}
			}
			// Check if new slug conflicts with another topic.
			$existing = $this->bb_get_activity_topic( 'slug', $slug );
			if ( $existing && $existing->id !== $topic_id ) {
				return new WP_Error( 'bb_activity_duplicate_slug', __( 'A topic with this slug already exists.', 'buddyboss' ) );
			}
			$data['slug'] = $slug;
			$format[]     = '%s';
		}

		if ( isset( $args['scope'] ) ) {
			$data['scope'] = sanitize_key( $args['scope'] );
			$format[]      = '%s';
		}

		if ( isset( $args['permission_type'] ) ) {
			$data['permission_type'] = sanitize_key( $args['permission_type'] );
			$format[]                = '%s';
		}

		if ( isset( $args['permission_data'] ) ) { // Use isset to allow setting to null.
			$data['permission_data'] = is_null( $args['permission_data'] ) ? null : wp_json_encode( $args['permission_data'] );
			$format[]                = '%s';
		}

		if ( isset( $args['menu_order'] ) ) {
			$data['menu_order'] = absint( $args['menu_order'] );
			$format[]           = '%d';
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'bb_activity_no_data', __( 'No data provided to update.', 'buddyboss' ) );
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
			return new WP_Error( 'bb_activity_db_update_error', __( 'Could not update topic in the database.', 'buddyboss' ), $this->wpdb->last_error );
		}

		/**
		 * Fires after a topic has been updated.
		 *
		 * @param int   $topic_id The ID of the topic updated.
		 * @param array $args     The arguments used to update the topic.
		 */
		do_action( 'bb_activity_topic_updated', $topic_id, $args );

		return true;
	}

	/**
	 * Delete an existing topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_delete_activity_topic_ajax() {
		check_ajax_referer( 'bb_delete_activity_topic', 'nonce' );

		$topic_id = isset( $_POST['topic_id'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['topic_id'] ) ) ) : 0;

		$deleted = $this->bb_delete_activity_topic( $topic_id );

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
	public function bb_delete_activity_topic( $topic_id ) {
		$topic_id = absint( $topic_id );
		if ( ! $this->bb_get_activity_topic( 'id', $topic_id ) ) {
			return new WP_Error( 'bb_activity_invalid_topic_id', __( 'Invalid topic ID.', 'buddyboss' ) );
		}

		/**
		 * Fires before a topic is deleted.
		 *
		 * @param int $topic_id The ID of the topic being deleted.
		 */
		do_action( 'bb_activity_topic_relationship_before_delete', $topic_id );

		// 1. Delete activity relationships.
		$deleted_rels = $this->wpdb->delete( $this->activity_rel_table, array( 'topic_id' => $topic_id ), array( '%d' ) );
		if ( false === $deleted_rels ) {
			return new WP_Error( 'bb_activity_db_delete_error', __( 'Could not delete topic relationships.', 'buddyboss' ), $this->wpdb->last_error );
		}

		do_action( 'bb_activity_topic_relationship_after_deleted', $topic_id );

		// 3. Delete the topic itself.
		$deleted_topic = $this->wpdb->delete( $this->topics_table, array( 'id' => $topic_id ), array( '%d' ) );
		if ( ! $deleted_topic ) { // delete returns number of rows deleted, 0 is possible but false is error.
			return new WP_Error( 'bb_activity_db_delete_error', __( 'Could not delete topic.', 'buddyboss' ), $this->wpdb->last_error );
		}

		/**
		 * Fires after a topic has been deleted.
		 *
		 * @param int $topic_id The ID of the topic that was deleted.
		 */
		do_action( 'bb_activity_topic_deleted', $topic_id );

		return true;
	}

	/**
	 * Get the permission type for the activity topic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $existing_permission_type The existing permission type.
	 *
	 * @return array Array of permission types.
	 */
	public function bb_activity_topic_permission_type( $existing_permission_type = '' ) {
		$permission_types = array(
			'anyone'      => __( 'Anyone', 'buddyboss' ),
			'mods_admins' => __( 'Admin', 'buddyboss' ),
		);

		// If an existing permission type is provided, return only that type.
		if ( ! empty( $existing_permission_type ) && isset( $permission_types[ $existing_permission_type ] ) ) {
			return array( $existing_permission_type => $permission_types[ $existing_permission_type ] );
		}

		// Otherwise return all permission types.
		return $permission_types;
	}
}
