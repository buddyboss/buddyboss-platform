<?php
/**
 * Performance Lab -- A/B benchmark runner.
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
 * Runs one endpoint several ways in a single process and compares the cost.
 *
 * A stopwatch in the client measures the network, the TLS handshake, WordPress
 * booting, the endpoint, and the client parsing the answer, all as one number.
 * Selective fields can only ever move one of those. When the others dominate, a
 * large saving inside the endpoint shows up as a rounding error end to end, and
 * the feature looks broken when it is working perfectly well.
 *
 * This dispatches in-process, so what it reports is the endpoint and nothing
 * else.
 *
 * Three arms run, interleaved, so that a warming cache cannot flatter whichever
 * went second:
 *
 * - `full`      -- no selection at all, every field built.
 * - `selective` -- the selection under test.
 * - `floor`     -- `_fields=id`, the least an item can be asked for.
 *
 * The floor is the arm that matters most, and the one a client-side benchmark
 * cannot produce. Whatever it costs is what the request costs before a single
 * field is built: the query, the permission checks, the cache priming. No
 * selection can ever go below it, so `full - floor` is the whole of what
 * selective fields has to work with, and `full - selective` is how much of that
 * it actually took.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Perf_Lab_Bench {

	/**
	 * Run the comparison.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 *     Optional. Arguments for the run.
	 *
	 *     @type string $route        Route to dispatch. Default '/buddyboss/v1/activity'.
	 *     @type string $query        Extra query string, e.g. 'per_page=20&scope=all'.
	 *     @type string $fields       `_fields` for the selective arm.
	 *     @type string $embed        `_embed` relations, or ''.
	 *     @type string $embed_fields `embed_fields` for the selective arm.
	 *     @type int    $runs         Iterations per arm. Default 7.
	 *     @type bool   $flush        Flush the object cache before each run. Default true.
	 *     @type int    $user_id      Run as this user. Default 0 for the current one.
	 * }
	 *
	 * @return array|WP_Error The comparison, or an error when the route fails.
	 */
	public static function run( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'route'        => '/buddyboss/v1/activity',
				'query'        => 'per_page=20',
				'fields'       => 'id,date,content,user_id',
				'embed'        => '',
				'embed_fields' => '',
				'runs'         => 7,
				'flush'        => true,
				'user_id'      => 0,
			)
		);

		$runs = max( 1, min( 25, (int) $args['runs'] ) );

		$switched = false;
		$previous = get_current_user_id();

		if ( ! empty( $args['user_id'] ) && (int) $args['user_id'] !== $previous ) {
			wp_set_current_user( (int) $args['user_id'] );
			$switched = true;
		}

		// The bench's own dispatches are not app traffic and must not be logged.
		BB_Perf_Lab_Monitor::instance()->suspend( true );

		$arms = array(
			'full'      => array(
				'fields'       => '',
				'embed_fields' => '',
			),
			'selective' => array(
				'fields'       => $args['fields'],
				'embed_fields' => $args['embed_fields'],
			),
			'floor'     => array(
				'fields'       => 'id',
				'embed_fields' => 'id',
			),
		);

		$samples = array_fill_keys( array_keys( $arms ), array() );
		$error   = null;

		/*
		 * Interleaved, not batched: a whole arm at a time would hand the second
		 * arm every cache the first one warmed, and the comparison would measure
		 * the ordering rather than the work.
		 */
		for ( $i = 0; $i < $runs; $i++ ) {
			foreach ( $arms as $arm => $selection ) {
				$sample = self::measure( $args, $selection, (bool) $args['flush'] );

				if ( is_wp_error( $sample ) ) {
					$error = $sample;
					break 2;
				}

				$samples[ $arm ][] = $sample;
			}
		}

		BB_Perf_Lab_Monitor::instance()->suspend( false );

		if ( $switched ) {
			wp_set_current_user( $previous );
		}

		if ( null !== $error ) {
			return $error;
		}

		$result = array(
			'route'  => $args['route'],
			'query'  => $args['query'],
			'fields' => $args['fields'],
			'embed'  => $args['embed'],
			'runs'   => $runs,
			'flush'  => (bool) $args['flush'],
			'arms'   => array(),
		);

		foreach ( $samples as $arm => $rows ) {
			$result['arms'][ $arm ] = self::summarise( $rows );
		}

		$result['verdict'] = self::verdict( $result['arms'] );

		return $result;
	}

	/**
	 * Dispatch once and measure what it cost.
	 *
	 * The dispatch mirrors `WP_REST_Server::serve_request()`: `rest_do_request()`
	 * on its own returns a response nothing has filtered, so neither `_fields`
	 * nor the embeds would have been applied and the payload would be a fiction.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args      Run arguments.
	 * @param array $selection Field selection for this arm.
	 * @param bool  $flush     Whether to flush the object cache first.
	 *
	 * @return array|WP_Error Measurements, or the endpoint's error.
	 */
	protected static function measure( $args, $selection, $flush ) {
		global $wpdb;

		if ( $flush ) {
			wp_cache_flush();
		}

		$request = new WP_REST_Request( 'GET', $args['route'] );

		$params = array();
		parse_str( (string) $args['query'], $params );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		if ( '' !== $selection['fields'] ) {
			$request->set_param( '_fields', $selection['fields'] );
		}

		if ( '' !== (string) $selection['embed_fields'] ) {
			$request->set_param( 'embed_fields', $selection['embed_fields'] );
		}

		$embed = '' !== (string) $args['embed'] ? array_filter( array_map( 'trim', explode( ',', (string) $args['embed'] ) ) ) : false;

		if ( ! empty( $embed ) ) {
			$request->set_param( '_embed', implode( ',', $embed ) );
		}

		$server = rest_get_server();

		$saved_from = ( isset( $wpdb->queries ) && is_array( $wpdb->queries ) ) ? count( $wpdb->queries ) : 0;
		$queries_at = (int) $wpdb->num_queries;
		$mem_at     = bb_perf_lab_memory_start();
		$started    = microtime( true );

		$response = rest_do_request( $request );
		$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $server, $request );

		if ( $response->is_error() ) {
			$error = $response->as_error();

			return new WP_Error(
				'bb_perf_lab_dispatch_failed',
				sprintf(
					/* translators: 1: route, 2: error message */
					__( 'The route %1$s answered with an error: %2$s', 'buddyboss' ),
					$args['route'],
					$error->get_error_message()
				)
			);
		}

		$data    = $server->response_to_data( $response, empty( $embed ) ? false : $embed );
		$payload = strlen( (string) wp_json_encode( $data ) );
		$wall    = ( microtime( true ) - $started ) * 1000;
		$mem     = bb_perf_lab_memory_used( $mem_at );
		$count   = (int) $wpdb->num_queries - $queries_at;

		$db_ms = 0.0;

		if ( isset( $wpdb->queries ) && is_array( $wpdb->queries ) ) {
			foreach ( array_slice( $wpdb->queries, $saved_from ) as $query ) {
				$db_ms += isset( $query[1] ) ? (float) $query[1] * 1000 : 0.0;
			}
		}

		return array(
			'wall_ms'    => $wall,
			'db_ms'      => $db_ms,
			'queries'    => $count,
			'payload_kb' => $payload / 1024,
			'mem_kb'     => max( 0, $mem ) / 1024,
			'items'      => ( is_array( $data ) && wp_is_numeric_array( $data ) ) ? count( $data ) : 1,
		);
	}

	/**
	 * Reduce an arm's samples to the numbers worth reading.
	 *
	 * The median is what the comparison is built on. A mean would let one
	 * unlucky run -- a checkpoint, a neighbour on the box -- move the answer,
	 * which over a handful of runs it very often does.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $rows Samples for one arm.
	 *
	 * @return array
	 */
	protected static function summarise( $rows ) {
		$out = array( 'samples' => count( $rows ) );

		foreach ( array( 'wall_ms', 'db_ms', 'queries', 'payload_kb', 'mem_kb', 'items' ) as $metric ) {
			$values = wp_list_pluck( $rows, $metric );

			sort( $values );

			$out[ $metric ] = array(
				'median' => round( self::median( $values ), 2 ),
				'min'    => round( (float) reset( $values ), 2 ),
				'max'    => round( (float) end( $values ), 2 ),
			);
		}

		return $out;
	}

	/**
	 * Median of a sorted list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $values Sorted values.
	 *
	 * @return float
	 */
	protected static function median( $values ) {
		$count = count( $values );

		if ( 0 === $count ) {
			return 0.0;
		}

		$middle = (int) floor( ( $count - 1 ) / 2 );

		if ( 0 !== $count % 2 ) {
			return (float) $values[ $middle ];
		}

		return ( (float) $values[ $middle ] + (float) $values[ $middle + 1 ] ) / 2;
	}

	/**
	 * Turn the three arms into a reading of what the numbers mean.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $arms Summarised arms.
	 *
	 * @return array
	 */
	protected static function verdict( $arms ) {
		$full      = $arms['full']['wall_ms']['median'];
		$selective = $arms['selective']['wall_ms']['median'];
		$floor     = $arms['floor']['wall_ms']['median'];

		// What the request costs before any field is built at all.
		$irreducible = $full > 0 ? ( $floor / $full ) * 100 : 0;

		// The most any selection could ever save.
		$headroom = max( 0, $full - $floor );

		// What this selection did save.
		$saved = max( 0, $full - $selective );

		$verdict = array(
			'server_saving_ms'     => round( $saved, 2 ),
			'server_saving_pct'    => $full > 0 ? round( ( $saved / $full ) * 100, 1 ) : 0,
			'payload_saving_pct'   => $arms['full']['payload_kb']['median'] > 0
				? round( ( 1 - ( $arms['selective']['payload_kb']['median'] / $arms['full']['payload_kb']['median'] ) ) * 100, 1 )
				: 0,
			'irreducible_pct'      => round( $irreducible, 1 ),
			'headroom_ms'          => round( $headroom, 2 ),
			'captured_of_headroom' => $headroom > 0 ? round( ( $saved / $headroom ) * 100, 1 ) : 0,
			'queries_saved'        => $arms['full']['queries']['median'] - $arms['selective']['queries']['median'],
			'db_ms_saved'          => round( $arms['full']['db_ms']['median'] - $arms['selective']['db_ms']['median'], 2 ),
		);

		$notes = array();

		if ( $verdict['irreducible_pct'] > 80 ) {
			$notes[] = __( 'Over 80% of this request is spent before any field is built -- the query, the permission checks, the cache priming. Selective fields cannot reach that, so the end-to-end gain will stay small no matter how narrow the selection. Look at the query itself.', 'buddyboss' );
		}

		if ( $verdict['captured_of_headroom'] > 70 ) {
			$notes[] = __( 'The selection is taking most of the saving that was available to it. Field building is working as intended.', 'buddyboss' );
		} elseif ( $headroom > 0 && $verdict['captured_of_headroom'] < 30 ) {
			$notes[] = __( 'There was room to save and the selection took little of it, which points at work still running for fields nobody asked for.', 'buddyboss' );
		}

		if ( $verdict['payload_saving_pct'] > 30 && $verdict['server_saving_pct'] < 10 ) {
			$notes[] = __( 'The payload shrank far more than the time did. On a fast connection that trade is nearly invisible; on a slow or metered one it is the whole point.', 'buddyboss' );
		}

		$verdict['notes'] = $notes;

		return $verdict;
	}
}
