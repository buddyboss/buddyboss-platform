<?php
/**
 * BuddyBoss Settings History / Audit Log
 *
 * Tracks who changed what settings and when.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Settings History Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Settings_History {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var BB_Settings_History
	 */
	private static $instance = null;

	/**
	 * Default retention days.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var int
	 */
	private $retention_days = 90;

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @return BB_Settings_History
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
	 * @since BuddyBoss 3.0.0
	 */
	private function __construct() {
		// Create table on init (will check if exists).
		add_action( 'bb_settings_history_init', array( $this, 'maybe_create_table' ) );

		// Schedule daily cleanup.
		if ( ! wp_next_scheduled( 'bb_settings_history_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'bb_settings_history_cleanup' );
		}
		add_action( 'bb_settings_history_cleanup', array( $this, 'cleanup_history' ) );

		// Initialize table on first load.
		add_action( 'bp_loaded', array( $this, 'maybe_create_table' ), 20 );
	}

	/**
	 * Create database table if it doesn't exist.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function maybe_create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'bb_settings_history';

		// Check if table exists.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
			return; // Table already exists.
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			feature_id VARCHAR(100) NOT NULL,
			field_name VARCHAR(100) NOT NULL,
			old_value LONGTEXT,
			new_value LONGTEXT,
			user_id BIGINT UNSIGNED NOT NULL,
			timestamp DATETIME NOT NULL,
			ip_address VARCHAR(45),
			PRIMARY KEY (id),
			KEY feature_id (feature_id),
			KEY field_name (field_name),
			KEY user_id (user_id),
			KEY timestamp (timestamp)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log a settings change.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @param string $field_name Field name (option name).
	 * @param mixed  $old_value  Old value.
	 * @param mixed  $new_value  New value.
	 * @param int    $user_id    User ID. Optional. Defaults to current user.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function log_change( $feature_id, $field_name, $old_value, $new_value, $user_id = null ) {
		// Ensure table exists.
		$this->maybe_create_table();

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		// Skip if values are the same.
		if ( $old_value === $new_value ) {
			return true;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'bb_settings_history';

		$result = $wpdb->insert(
			$table,
			array(
				'feature_id'  => $feature_id,
				'field_name'  => $field_name,
				'old_value'   => maybe_serialize( $old_value ),
				'new_value'   => maybe_serialize( $new_value ),
				'user_id'     => $user_id,
				'timestamp'   => current_time( 'mysql' ),
				'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error(
				'db_error',
				__( 'Failed to log settings change.', 'buddyboss' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Get settings change history.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $args {
	 *     Optional. Arguments to filter history.
	 *
	 *     @type string $feature_id Filter by feature ID.
	 *     @type string $field_name Filter by field name.
	 *     @type int    $user_id    Filter by user ID.
	 *     @type int    $limit      Number of entries to return (default: 50).
	 *     @type int    $offset     Number of entries to skip (default: 0).
	 * }
	 * @return array Array of history entries.
	 */
	public function get_history( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bb_settings_history';

		$defaults = array(
			'feature_id' => null,
			'field_name' => null,
			'user_id'    => null,
			'limit'      => 50,
			'offset'     => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$where_values = array();

		if ( $args['feature_id'] ) {
			$where[] = 'feature_id = %s';
			$where_values[] = $args['feature_id'];
		}

		if ( $args['field_name'] ) {
			$where[] = 'field_name = %s';
			$where_values[] = $args['field_name'];
		}

		if ( $args['user_id'] ) {
			$where[] = 'user_id = %d';
			$where_values[] = $args['user_id'];
		}

		$where_clause = implode( ' AND ', $where );

		$query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY timestamp DESC LIMIT %d OFFSET %d";

		$where_values[] = $args['limit'];
		$where_values[] = $args['offset'];

		$results = $wpdb->get_results(
			$wpdb->prepare( $query, $where_values ),
			ARRAY_A
		);

		// Unserialize values.
		foreach ( $results as &$result ) {
			$result['old_value'] = maybe_unserialize( $result['old_value'] );
			$result['new_value'] = maybe_unserialize( $result['new_value'] );
		}

		return $results;
	}

	/**
	 * Clean up old history entries based on retention policy.
	 *
	 * Should be called via WP Cron daily.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return int Number of entries deleted.
	 */
	public function cleanup_history() {
		global $wpdb;
		$table = $wpdb->prefix . 'bb_settings_history';

		// Get retention days from option (allows customization).
		$retention_days = $this->get_retention_days();

		// Calculate cutoff date.
		$cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$retention_days} days" ) );

		// Delete old entries.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE timestamp < %s",
				$cutoff_date
			)
		);

		// Log cleanup.
		if ( $deleted > 0 ) {
			error_log(
				sprintf(
					/* translators: 1: number of entries, 2: retention days */
					'BuddyBoss Settings History: Cleaned up %1$d old entries (older than %2$d days)',
					$deleted,
					$retention_days
				)
			);
		}

		return $deleted;
	}

	/**
	 * Get retention policy setting.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return int Retention days.
	 */
	public function get_retention_days() {
		/**
		 * Filter the settings history retention days.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param int $retention_days Retention days (default: 90, min: 7, max: 365).
		 */
		$retention_days = apply_filters( 'bb_settings_history_retention_days', $this->retention_days );
		$retention_days = max( 7, min( 365, (int) $retention_days ) ); // Min 7, Max 365.

		return $retention_days;
	}

	/**
	 * Set retention policy.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param int $days Retention days (7-365).
	 * @return int Actual retention days set.
	 */
	public function set_retention_days( $days ) {
		// Minimum 7 days, maximum 365 days.
		$days = max( 7, min( 365, absint( $days ) ) );
		update_option( 'bb_settings_history_retention_days', $days );
		return $days;
	}
}

/**
 * Get the Settings History instance.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return BB_Settings_History
 */
function bb_settings_history() {
	return BB_Settings_History::instance();
}
