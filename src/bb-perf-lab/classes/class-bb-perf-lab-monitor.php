<?php
/**
 * Performance Lab -- REST request monitor.
 *
 * TEMPORARY diagnostic tooling. See `bb-perf-lab.php` for removal instructions.
 *
 * @package    BuddyBoss\PerfLab
 * @subpackage PerfLab
 * @since      BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Records what each REST request costs the server.
 *
 * The measurement brackets the whole of `WP_REST_Server::serve_request()`, from
 * the moment the route is about to be dispatched to the moment the payload is
 * about to be echoed. Embedded sub-requests are dispatched inside that window,
 * so their cost is counted where it belongs: against the request that asked for
 * them.
 *
 * What it deliberately does not measure is everything either side of that
 * window -- DNS, TLS, the network, and WordPress's own bootstrap. That is the
 * point. A client stopwatch cannot separate them; this can.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Perf_Lab_Monitor {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var BB_Perf_Lab_Monitor|null
	 */
	protected static $instance = null;

	/**
	 * Measurements taken when the request began.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	protected $start = array();

	/**
	 * The request being measured, so nested dispatches are not mistaken for it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var WP_REST_Request|null
	 */
	protected $tracking = null;

	/**
	 * Whether logging is suspended, as it is while the bench runs.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var bool
	 */
	protected $suspended = false;

	/**
	 * Get the singleton.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_Perf_Lab_Monitor
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook the monitor into the REST lifecycle.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	protected function __construct() {
		if ( ! bb_perf_lab_setting( 'monitor_enabled' ) ) {
			return;
		}

		/*
		 * Per-query timings live behind `SAVEQUERIES`, which wpdb reads at query
		 * time. Defining it here catches everything from this point on, which is
		 * every query a REST request makes.
		 */
		if ( bb_perf_lab_setting( 'monitor_deep' ) && ! defined( 'SAVEQUERIES' ) ) {
			define( 'SAVEQUERIES', true );
		}

		add_filter( 'rest_pre_dispatch', array( $this, 'begin' ), 1, 3 );
		add_filter( 'rest_pre_echo_response', array( $this, 'finish' ), 999, 3 );
	}

	/**
	 * Name of the log table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'bb_perf_lab_log';
	}

	/**
	 * Create the log table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		$table   = self::table();
		$collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			logged_at datetime NOT NULL,
			route varchar(191) NOT NULL DEFAULT '',
			method varchar(10) NOT NULL DEFAULT '',
			label varchar(60) NOT NULL DEFAULT '',
			fields text NOT NULL,
			embed varchar(191) NOT NULL DEFAULT '',
			embed_fields varchar(191) NOT NULL DEFAULT '',
			selective tinyint(1) NOT NULL DEFAULT 0,
			per_page smallint(6) NOT NULL DEFAULT 0,
			items smallint(6) NOT NULL DEFAULT 0,
			wall_ms float NOT NULL DEFAULT 0,
			db_ms float NOT NULL DEFAULT 0,
			queries int(11) NOT NULL DEFAULT 0,
			mem_peak_kb int(11) NOT NULL DEFAULT 0,
			payload_kb float NOT NULL DEFAULT 0,
			user_id bigint(20) NOT NULL DEFAULT 0,
			slow_queries longtext NOT NULL,
			PRIMARY KEY  (id),
			KEY logged_at (logged_at),
			KEY route (route),
			KEY selective (selective),
			KEY label (label)
		) {$collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop the log table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb;

		$table = self::table();

		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Stop recording, so the bench's own dispatches do not land in the log.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $suspended Optional. Whether to suspend. Default true.
	 *
	 * @return void
	 */
	public function suspend( $suspended = true ) {
		$this->suspended = (bool) $suspended;
	}

	/**
	 * Take the opening measurements.
	 *
	 * Only the outermost request is tracked. WordPress dispatches embedded items
	 * through the same filter, and their cost belongs to the request that
	 * embedded them, not to a row of their own.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param mixed           $result  Response to replace the requested version with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return mixed The result, untouched.
	 */
	public function begin( $result, $server, $request ) {
		if ( $this->suspended || null !== $this->tracking || ! $this->wanted( $request ) ) {
			return $result;
		}

		global $wpdb;

		$this->tracking = $request;
		$this->start    = array(
			'time'    => microtime( true ),
			'queries' => (int) $wpdb->num_queries,
			'saved'   => ( isset( $wpdb->queries ) && is_array( $wpdb->queries ) ) ? count( $wpdb->queries ) : 0,
			'mem'     => bb_perf_lab_memory_start(),
		);

		return $result;
	}

	/**
	 * Take the closing measurements and write the row.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array           $served  Response data about to be echoed.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return array The response data, untouched.
	 */
	public function finish( $served, $server, $request ) {
		if ( $this->suspended || $this->tracking !== $request ) {
			return $served;
		}

		global $wpdb;

		$wall  = ( microtime( true ) - $this->start['time'] ) * 1000;
		$count = (int) $wpdb->num_queries - $this->start['queries'];
		$mem   = bb_perf_lab_memory_used( $this->start['mem'] );

		list( $db_ms, $slow ) = $this->query_timings( $this->start['saved'] );

		$payload = is_scalar( $served ) ? strlen( (string) $served ) : strlen( (string) wp_json_encode( $served ) );
		$items   = ( is_array( $served ) && wp_is_numeric_array( $served ) ) ? count( $served ) : 1;

		$fields       = (string) $request->get_param( '_fields' );
		$embed        = $request->get_param( '_embed' );
		$embed_fields = $request->get_param( 'embed_fields' );

		$this->record(
			array(
				'route'        => $request->get_route(),
				'method'       => $request->get_method(),
				'label'        => (string) $request->get_param( 'bb_perf_label' ),
				'fields'       => $fields,
				'embed'        => is_array( $embed ) ? implode( ',', $embed ) : (string) $embed,
				'embed_fields' => is_array( $embed_fields ) ? wp_json_encode( $embed_fields ) : (string) $embed_fields,
				'selective'    => ( '' !== $fields || ! empty( $embed_fields ) ) ? 1 : 0,
				'per_page'     => (int) $request->get_param( 'per_page' ),
				'items'        => $items,
				'wall_ms'      => round( $wall, 2 ),
				'db_ms'        => round( $db_ms, 2 ),
				'queries'      => $count,
				'mem_peak_kb'  => (int) round( max( 0, $mem ) / 1024 ),
				'payload_kb'   => round( $payload / 1024, 2 ),
				'user_id'      => get_current_user_id(),
				'slow_queries' => $slow,
			)
		);

		$this->tracking = null;

		return $served;
	}

	/**
	 * Write one row, then trim the log back to its ceiling.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $row Row to insert.
	 *
	 * @return void
	 */
	public function record( $row ) {
		global $wpdb;

		$table = self::table();

		$row['logged_at'] = current_time( 'mysql' );
		$row['route']     = substr( (string) $row['route'], 0, 191 );
		$row['label']     = substr( (string) $row['label'], 0, 60 );

		$wpdb->insert( $table, $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$max = (int) bb_perf_lab_setting( 'monitor_max_rows' );

		if ( $max < 1 ) {
			return;
		}

		/*
		 * Trimming on every write would double the cost of logging, so it only
		 * happens now and then. The log drifts a little over its ceiling between
		 * trims, which is of no consequence.
		 */
		if ( 0 !== $wpdb->insert_id % 50 ) {
			return;
		}

		$keep = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET %d", $max ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $keep > 0 ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id <= %d", $keep ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}

	/**
	 * Total the database time, and keep the queries worth looking at.
	 *
	 * Without `SAVEQUERIES` there are no timings to total, and the row carries a
	 * query count only.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $from Index into `$wpdb->queries` the request began at.
	 *
	 * @return array {
	 *     @type float  $0 Milliseconds spent in the database.
	 *     @type string $1 JSON list of the slowest queries, or ''.
	 * }
	 */
	protected function query_timings( $from ) {
		global $wpdb;

		if ( ! isset( $wpdb->queries ) || ! is_array( $wpdb->queries ) ) {
			return array( 0.0, '' );
		}

		$queries = array_slice( $wpdb->queries, $from );

		if ( empty( $queries ) ) {
			return array( 0.0, '' );
		}

		$total   = 0.0;
		$timings = array();

		foreach ( $queries as $query ) {
			$elapsed = isset( $query[1] ) ? (float) $query[1] : 0.0;
			$total  += $elapsed;

			$timings[] = array(
				'ms'     => round( $elapsed * 1000, 2 ),
				'sql'    => substr( preg_replace( '/\s+/', ' ', (string) $query[0] ), 0, 400 ),
				'caller' => substr( isset( $query[2] ) ? (string) $query[2] : '', 0, 300 ),
			);
		}

		usort(
			$timings,
			function ( $a, $b ) {
				if ( $a['ms'] === $b['ms'] ) {
					return 0;
				}

				return ( $a['ms'] < $b['ms'] ) ? 1 : -1;
			}
		);

		return array( $total * 1000, (string) wp_json_encode( array_slice( $timings, 0, 12 ) ) );
	}

	/**
	 * Whether this request is one the monitor was asked to record.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param WP_REST_Request $request Request to consider.
	 *
	 * @return bool
	 */
	protected function wanted( $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return false;
		}

		$only_user = (int) bb_perf_lab_setting( 'monitor_user_id' );

		if ( $only_user > 0 && get_current_user_id() !== $only_user ) {
			return false;
		}

		$routes = array_filter( array_map( 'trim', explode( ',', (string) bb_perf_lab_setting( 'monitor_routes' ) ) ) );

		if ( empty( $routes ) ) {
			return true;
		}

		$route = $request->get_route();

		foreach ( $routes as $needle ) {
			if ( false !== strpos( $route, $needle ) ) {
				return true;
			}
		}

		return false;
	}
}
