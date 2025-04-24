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
}
