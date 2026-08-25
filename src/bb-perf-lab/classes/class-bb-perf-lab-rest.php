<?php
/**
 * Performance Lab -- REST access to the benchmark.
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
 * Exposes the benchmark over REST so it can be driven without a browser.
 *
 * The admin screen runs on `admin-ajax`, which WordPress refuses to authenticate
 * with an Application Password -- `wp_authenticate_application_password()` gates
 * itself on the request being REST or XML-RPC. That makes the benchmark
 * unreachable from a script, which is exactly how a matrix of endpoints and
 * field selections wants to be run.
 *
 * Same capability check as the screen, same code underneath.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Perf_Lab_REST {

	/**
	 * Register the routes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function register_routes() {
		$namespace = bp_rest_namespace() . '/' . bp_rest_version();

		register_rest_route(
			$namespace,
			'/perf-lab/bench',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'bench' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
					'args'                => self::bench_args(),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/perf-lab/environment',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'environment' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);
	}

	/**
	 * Arguments the benchmark route accepts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array
	 */
	protected static function bench_args() {
		return array(
			'route'        => array(
				'description'       => __( 'Route to dispatch, e.g. /buddyboss/v1/activity.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => '/buddyboss/v1/activity',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'query'        => array(
				'description'       => __( 'Query string for the dispatch, without the field selection.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => 'per_page=20',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'fields'       => array(
				'description'       => __( 'The `_fields` under test.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => 'id',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'embed'        => array(
				'description'       => __( 'Relations to embed, comma separated.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'embed_fields' => array(
				'description'       => __( 'The `embed_fields` under test.', 'buddyboss' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'runs'         => array(
				'description'       => __( 'Iterations per arm.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 7,
				'sanitize_callback' => 'absint',
			),
			'flush'        => array(
				'description'       => __( 'Flush the object cache before each run.', 'buddyboss' ),
				'type'              => 'boolean',
				'default'           => true,
			),
			'user_id'      => array(
				'description'       => __( 'Run as this member. 0 runs as the caller.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
		);
	}

	/**
	 * Only administrators, as on the screen.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return true|WP_Error
	 */
	public static function permissions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'bb_perf_lab_forbidden',
				__( 'Sorry, you are not allowed to do that.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Run the benchmark.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function bench( $request ) {
		$result = BB_Perf_Lab_Bench::run(
			array(
				'route'        => $request->get_param( 'route' ),
				'query'        => $request->get_param( 'query' ),
				'fields'       => $request->get_param( 'fields' ),
				'embed'        => $request->get_param( 'embed' ),
				'embed_fields' => $request->get_param( 'embed_fields' ),
				'runs'         => $request->get_param( 'runs' ),
				'flush'        => (bool) $request->get_param( 'flush' ),
				'user_id'      => $request->get_param( 'user_id' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * What the numbers were taken on, so a report can say so.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return WP_REST_Response
	 */
	public static function environment() {
		global $wpdb, $wp_version;

		$counts = array();

		foreach ( array( 'bp_activity', 'bp_activity_meta', 'bp_groups', 'bp_groups_members', 'bp_follow', 'bp_friends' ) as $table ) {
			$full = $wpdb->prefix . $table;

			if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) ) ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$counts[ $table ] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$full}" );
		}

		$counts['users'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return rest_ensure_response(
			array(
				'wordpress'       => $wp_version,
				'php'             => PHP_VERSION,
				'platform'        => defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '',
				'mysql'           => $wpdb->db_version(),
				'object_cache'    => (bool) wp_using_ext_object_cache(),
				'savequeries'     => defined( 'SAVEQUERIES' ) && SAVEQUERIES,
				'opcache'         => function_exists( 'opcache_get_status' ) && is_array( @opcache_get_status( false ) ), // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				'active_plugins'  => count( (array) get_option( 'active_plugins', array() ) ),
				'rows'            => $counts,
			)
		);
	}
}
