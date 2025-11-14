<?php
/**
 * BuddyBoss DRM Event Model
 *
 * Manages DRM events stored in the database table.
 * Similar to MeprEvent, but for BuddyBoss DRM system.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Event model class.
 */
class BB_DRM_Event {

	/**
	 * Event type for Platform DRM events.
	 *
	 * @var string
	 */
	public static $platform_str = 'platform';

	/**
	 * Event type for add-on DRM events.
	 *
	 * @var string
	 */
	public static $addon_str = 'addon';

	/**
	 * Event ID.
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * Event name (e.g., 'no-license', 'invalid-license', 'addon-buddyboss-platform-pro').
	 *
	 * @var string
	 */
	public $event = '';

	/**
	 * Event arguments (JSON encoded data).
	 *
	 * @var string|null
	 */
	public $args = null;

	/**
	 * Event entity ID (e.g., 1 for Platform, or addon ID).
	 *
	 * @var int
	 */
	public $evt_id = 0;

	/**
	 * Event entity type ('platform' or 'addon').
	 *
	 * @var string
	 */
	public $evt_id_type = 'platform';

	/**
	 * Event creation timestamp.
	 *
	 * @var string
	 */
	public $created_at = null;

	/**
	 * Constructor.
	 *
	 * @param mixed $obj Event ID or object with properties to initialize.
	 */
	public function __construct( $obj = null ) {
		if ( is_numeric( $obj ) && $obj > 0 ) {
			// Load from database by ID.
			$this->load( $obj );
		} elseif ( is_object( $obj ) || is_array( $obj ) ) {
			// Initialize from object/array.
			$this->initialize( $obj );
		}
	}

	/**
	 * Initialize event properties from an object or array.
	 *
	 * @param object|array $data Event data.
	 */
	private function initialize( $data ) {
		$data = (object) $data;

		$this->id          = isset( $data->id ) ? (int) $data->id : 0;
		$this->event       = isset( $data->event ) ? sanitize_text_field( $data->event ) : '';
		$this->args        = isset( $data->args ) ? $data->args : null;
		$this->evt_id      = isset( $data->evt_id ) ? (int) $data->evt_id : 0;
		$this->evt_id_type = isset( $data->evt_id_type ) ? sanitize_text_field( $data->evt_id_type ) : 'platform';
		$this->created_at  = isset( $data->created_at ) ? $data->created_at : null;
	}

	/**
	 * Load event from database by ID.
	 *
	 * @param int $id Event ID.
	 * @return bool True if loaded successfully.
	 */
	private function load( $id ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$event      = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$id
			)
		);

		if ( $event ) {
			$this->initialize( $event );
			return true;
		}

		return false;
	}

	/**
	 * Get the database table name.
	 *
	 * @return string Table name with prefix.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'bb_drm_events';
	}

	/**
	 * Store event in database.
	 *
	 * @return int|false Event ID on success, false on failure.
	 */
	public function store() {
		global $wpdb;

		$table_name = self::get_table_name();

		// Check if this is a unique event - if so, reuse existing event ID.
		$this->use_existing_if_unique();

		$data = array(
			'event'       => $this->event,
			'args'        => $this->args,
			'evt_id'      => $this->evt_id,
			'evt_id_type' => $this->evt_id_type,
		);

		if ( $this->id > 0 ) {
			// Update existing event.
			$wpdb->update(
				$table_name,
				$data,
				array( 'id' => $this->id ),
				array( '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);

			do_action( 'bb_drm_event_update', $this );
			return $this->id;
		} else {
			// Create new event.
			$data['created_at'] = current_time( 'mysql' );

			$wpdb->insert(
				$table_name,
				$data,
				array( '%s', '%s', '%d', '%s', '%s' )
			);

			$this->id         = $wpdb->insert_id;
			$this->created_at = $data['created_at'];

			do_action( 'bb_drm_event_create', $this );
			do_action( 'bb_drm_event', $this );
			do_action( "bb_drm_event_{$this->event}", $this );

			return $this->id;
		}
	}

	/**
	 * Delete event from database.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function destroy() {
		global $wpdb;

		if ( $this->id <= 0 ) {
			return false;
		}

		$table_name = self::get_table_name();

		do_action( 'bb_drm_event_destroy', $this );

		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $this->id ),
			array( '%d' )
		);

		return (bool) $result;
	}

	/**
	 * Get a single event by ID.
	 *
	 * @param int $id Event ID.
	 * @return BB_DRM_Event|null Event object or null if not found.
	 */
	public static function get_one( $id ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$event      = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$id
			)
		);

		if ( $event ) {
			return new self( $event );
		}

		return null;
	}

	/**
	 * Get a single event by event name, evt_id, and evt_id_type.
	 *
	 * @param string $event       Event name.
	 * @param int    $evt_id      Event entity ID.
	 * @param string $evt_id_type Event entity type.
	 * @return BB_DRM_Event|null Event object or null if not found.
	 */
	public static function get_one_by_event_and_evt_id_and_evt_id_type( $event, $evt_id, $evt_id_type ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$result     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE event = %s AND evt_id = %d AND evt_id_type = %s",
				$event,
				$evt_id,
				$evt_id_type
			)
		);

		if ( $result ) {
			return new self( $result );
		}

		return null;
	}

	/**
	 * Get the latest event for a given event name.
	 *
	 * @param string $event Event name.
	 * @return BB_DRM_Event|null Latest event or null if not found.
	 */
	public static function latest( $event ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$result     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE event = %s ORDER BY id DESC LIMIT 1",
				$event
			)
		);

		if ( $result ) {
			return new self( $result );
		}

		return null;
	}

	/**
	 * Get the latest event by event name within a specific time period.
	 *
	 * @param string $event        Event name.
	 * @param int    $elapsed_days Number of days to look back.
	 * @return BB_DRM_Event|null Latest event or null if not found.
	 */
	public static function latest_by_elapsed_days( $event, $elapsed_days ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$result     = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name}
				WHERE event = %s
				AND created_at >= NOW() - INTERVAL %d DAY
				ORDER BY id DESC
				LIMIT 1",
				$event,
				$elapsed_days
			)
		);

		if ( $result ) {
			return new self( $result );
		}

		return null;
	}

	/**
	 * Get all events.
	 *
	 * @param string $order_by Order by clause (e.g., 'id DESC').
	 * @param int    $limit    Number of results to return.
	 * @return array Array of BB_DRM_Event objects.
	 */
	public static function get_all( $order_by = 'id DESC', $limit = 100 ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$query      = "SELECT * FROM {$table_name}";

		if ( ! empty( $order_by ) ) {
			$query .= " ORDER BY {$order_by}";
		}

		if ( $limit > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d', $limit );
		}

		$results = $wpdb->get_results( $query );
		$events  = array();

		foreach ( $results as $result ) {
			$events[] = new self( $result );
		}

		return $events;
	}

	/**
	 * Get all events by event name.
	 *
	 * @param string $event    Event name.
	 * @param string $order_by Order by clause.
	 * @param int    $limit    Number of results to return.
	 * @return array Array of BB_DRM_Event objects.
	 */
	public static function get_all_by_event( $event, $order_by = 'id DESC', $limit = 100 ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$query      = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE event = %s",
			$event
		);

		if ( ! empty( $order_by ) ) {
			$query .= " ORDER BY {$order_by}";
		}

		if ( $limit > 0 ) {
			$query .= $wpdb->prepare( ' LIMIT %d', $limit );
		}

		$results = $wpdb->get_results( $query );
		$events  = array();

		foreach ( $results as $result ) {
			$events[] = new self( $result );
		}

		return $events;
	}

	/**
	 * Get event count.
	 *
	 * @return int Total number of events.
	 */
	public static function get_count() {
		global $wpdb;

		$table_name = self::get_table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
	}

	/**
	 * Get event count by event name.
	 *
	 * @param string $event Event name.
	 * @return int Number of events.
	 */
	public static function get_count_by_event( $event ) {
		global $wpdb;

		$table_name = self::get_table_name();
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE event = %s",
				$event
			)
		);
	}

	/**
	 * Record a DRM event.
	 *
	 * @param string $event       Event name.
	 * @param int    $evt_id      Event entity ID.
	 * @param string $evt_id_type Event entity type ('platform' or 'addon').
	 * @param mixed  $args        Additional event arguments.
	 * @return int|false Event ID on success, false on failure.
	 */
	public static function record( $event, $evt_id = 1, $evt_id_type = 'platform', $args = '' ) {
		$e                = new self();
		$e->event         = $event;
		$e->evt_id        = $evt_id;
		$e->evt_id_type   = $evt_id_type;
		$e->args          = $args;

		// Convert arrays/objects to JSON.
		if ( is_array( $args ) || is_object( $args ) ) {
			$e->args = wp_json_encode( $args );
		}

		return $e->store();
	}

	/**
	 * Get event arguments as object/array.
	 *
	 * @return mixed Decoded arguments or null.
	 */
	public function get_args() {
		if ( ! empty( $this->args ) && is_string( $this->args ) ) {
			return json_decode( $this->args );
		}
		return $this->args;
	}

	/**
	 * Check if this event is unique (only one should exist per event/evt_id/evt_id_type).
	 *
	 * DRM events are unique - we reuse the same event record.
	 *
	 * @return bool Always true for DRM events.
	 */
	private function is_unique() {
		return true;
	}

	/**
	 * Reuse existing event ID if this is a unique event.
	 */
	private function use_existing_if_unique() {
		if ( $this->is_unique() && $this->id <= 0 ) {
			$existing_event = self::get_one_by_event_and_evt_id_and_evt_id_type(
				$this->event,
				$this->evt_id,
				$this->evt_id_type
			);

			if ( $existing_event ) {
				$this->id         = $existing_event->id;
				$this->created_at = $existing_event->created_at;
			}
		}
	}

	/**
	 * Create database table for DRM events.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			event varchar(255) NOT NULL DEFAULT '',
			args text DEFAULT NULL,
			evt_id bigint(20) NOT NULL DEFAULT 1,
			evt_id_type varchar(255) NOT NULL DEFAULT 'platform',
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY event_event (event(191)),
			KEY event_evt_id (evt_id),
			KEY event_evt_id_type (evt_id_type(191)),
			KEY event_created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Check if table was created successfully.
		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Check if database table exists.
	 *
	 * @return bool True if table exists.
	 */
	public static function table_exists() {
		global $wpdb;

		$table_name = self::get_table_name();
		return $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name;
	}

	/**
	 * Drop the database table.
	 * USE WITH CAUTION - This will delete all DRM event data!
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function drop_table() {
		global $wpdb;

		$table_name = self::get_table_name();
		return (bool) $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
}
