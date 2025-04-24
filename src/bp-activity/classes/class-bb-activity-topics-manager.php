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
	}

	/**
	 * Create the necessary database tables.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public static function create_tables() {
		global $wpdb;

		$bp_prefix              = bp_core_get_table_prefix();
		$topics_table           = $bp_prefix . 'bb_activity_topics';
		$has_topics_table       = $wpdb->query( $wpdb->prepare( 'show tables like %s', $topics_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$activity_rel_table     = $bp_prefix . 'bb_activity_topic_relationship';
		$has_activity_rel_table = $wpdb->query( $wpdb->prepare( 'show tables like %s', $activity_rel_table ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$charset_collate        = $wpdb->get_charset_collate();

		if ( empty( $has_topics_table ) ) {

			$sql_topics = "CREATE TABLE {$topics_table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			slug VARCHAR(255) NOT NULL,
			creator_id BIGINT UNSIGNED NOT NULL,
			global_permission_type VARCHAR(20) NOT NULL DEFAULT 'anyone',
			global_permission_data TEXT NULL,
			topic_scope VARCHAR(10) NOT NULL DEFAULT 'global',
			date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_creator_id (creator_id),
			INDEX idx_perm_type (global_permission_type),
			INDEX idx_scope (topic_scope),
			UNIQUE KEY idx_slug (slug)
		) $charset_collate;";

			dbDelta( $sql_topics );
		}

		if ( empty( $has_activity_rel_table ) ) {

			$sql_activity_rel = "CREATE TABLE {$activity_rel_table} (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			activity_id BIGINT UNSIGNED NOT NULL,
			topic_id BIGINT UNSIGNED NOT NULL,
			date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			date_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_activity_id (activity_id),
			INDEX idx_topic_id (topic_id),
			UNIQUE KEY idx_activity_topic (activity_id, topic_id)
		) $charset_collate;";

			dbDelta( $sql_activity_rel );
		}
	}

	/**
	 * Add a new global topic via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_add_activity_topic_ajax() {
		check_ajax_referer( 'bb_add_activity_topic', 'nonce' );

		$name                   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$slug                   = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) ) : '';
		$creator_id             = isset( $_POST['creator_id'] ) ? absint( wp_unslash( $_POST['creator_id'] ) ) : get_current_user_id();
		$global_permission_type = isset( $_POST['global_permission_type'] ) ? sanitize_text_field( wp_unslash( $_POST['global_permission_type'] ) ) : 'anyone';

		$topic_id = $this->bb_add_activity_topic(
			array(
				'name'                   => $name,
				'slug'                   => $slug,
				'creator_id'             => $creator_id,
				'global_permission_type' => $global_permission_type,
			)
		);

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
			'name'                   => '',
			'slug'                   => '',
			'creator_id'             => get_current_user_id(),
			'global_permission_type' => 'anyone',
			'global_permission_data' => null,
			'topic_scope'            => 'global',
		);

		$args = wp_parse_args( $args, $defaults );

		// Validation.
		if ( empty( $args['name'] ) ) {
			return new WP_Error( 'missing_name', __( 'Topic name is required.', 'buddyboss' ) );
		}

		if ( empty( $args['slug'] ) ) {
			$args['slug'] = sanitize_title( $args['name'] );
		} else {
			$args['slug'] = sanitize_title( $args['slug'] );
		}

		// Check if slug already exists.
		if ( $this->bb_get_activity_topic( 'slug', $args['slug'] ) ) {
			return new WP_Error( 'duplicate_slug', __( 'A topic with this slug already exists.', 'buddyboss' ) );
		}

		// Prepare data for insertion.
		$data   = array(
			'name'                   => sanitize_text_field( $args['name'] ),
			'slug'                   => $args['slug'],
			'creator_id'             => absint( $args['creator_id'] ),
			'global_permission_type' => sanitize_key( $args['global_permission_type'] ),
			'global_permission_data' => is_null( $args['global_permission_data'] ) ? null : wp_json_encode( $args['global_permission_data'] ),
			'topic_scope'            => sanitize_key( $args['topic_scope'] ),
		);
		$format = array( '%s', '%s', '%d', '%s', '%s', '%s' );

		// Use the updated table name property.
		$inserted = $this->wpdb->insert( $this->topics_table, $data, $format );

		if ( ! $inserted ) {
			return new WP_Error( 'db_insert_error', __( 'Could not insert topic into the database.', 'buddyboss' ), $this->wpdb->last_error );
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
		if ( $topic && ! empty( $topic->global_permission_data ) ) {
			$topic->global_permission_data = json_decode( $topic->global_permission_data, true );
		}

		return $topic;
	}
}
