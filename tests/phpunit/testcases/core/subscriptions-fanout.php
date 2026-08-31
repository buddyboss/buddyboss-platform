<?php
/**
 * Subscription fan-out tests.
 *
 * @package BuddyBoss\Tests
 */

/**
 * Tests for the subscription fan-out: the light single-column fetch in
 * BB_Subscriptions::get(), the threshold-routed dispatcher, the keyset
 * background worker, and the chunk claim lifecycle.
 *
 * @group core
 * @group subscriptions
 * @group subscriptions_fanout
 */
class BB_Tests_Subscriptions_Fanout extends BP_UnitTestCase {

	/**
	 * Subscription type registered for these tests.
	 *
	 * @var string
	 */
	protected static $type = 'bbtest_fanout';

	/**
	 * Direct send-callback invocations captured during a test.
	 *
	 * @var array
	 */
	public static $sent = array();

	/**
	 * Monotonic item id source so no two tests share an item_id: the getters'
	 * per-request static memo is keyed by args (incl. item_id) and would
	 * otherwise serve a previous test's rolled-back list.
	 *
	 * @var int
	 */
	protected static $next_item_id = 100000;

	/**
	 * Set up: register the test type, block outbound HTTP, empty the queue.
	 */
	public function setUp(): void {
		parent::setUp();

		self::$sent = array();

		add_filter( 'bb_register_subscriptions_types', array( $this, 'register_test_type' ) );

		// The dispatcher and the worker call $bb_background_updater->dispatch(),
		// which fires a non-blocking loopback request; never let tests hit the network.
		add_filter( 'pre_http_request', array( $this, 'block_http' ) );

		$this->truncate_queue();
	}

	/**
	 * Tear down: empty the queue.
	 */
	public function tearDown(): void {
		$this->truncate_queue();

		parent::tearDown();
	}

	/**
	 * Short-circuit every HTTP request made during a test.
	 *
	 * @return WP_Error
	 */
	public function block_http() {
		return new WP_Error( 'bb_tests_http_blocked', 'HTTP requests are blocked in tests.' );
	}

	/**
	 * Return a fresh, never-reused item id.
	 *
	 * @return int
	 */
	protected function next_item_id() {
		return ++self::$next_item_id;
	}

	/**
	 * Count the callbacks registered on a hook (all priorities).
	 *
	 * @param string $hook Hook name.
	 * @return int
	 */
	protected function count_filter_callbacks( $hook ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook ] ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			$count += count( $callbacks );
		}

		return $count;
	}

	/**
	 * Register a permissive subscription type whose send callback records calls.
	 *
	 * @param array $types Registered types.
	 * @return array
	 */
	public function register_test_type( $types ) {
		$types[ self::$type ] = array(
			'label'              => array(
				'singular' => 'Fanout Test',
				'plural'   => 'Fanout Tests',
			),
			'subscription_type'  => self::$type,
			'items_callback'     => '__return_empty_array',
			'send_callback'      => array( __CLASS__, 'record_send' ),
			'validate_callback'  => '__return_true',
			'notification_type'  => 'bb_test_fanout_note',
			'notification_group' => 'core',
		);

		return $types;
	}

	/**
	 * Send callback: records the chunk it was invoked with.
	 *
	 * @param array $args Send arguments.
	 * @return false
	 */
	public static function record_send( $args ) {
		self::$sent[] = $args;

		return false;
	}

	/**
	 * Empty the background job queue table.
	 */
	protected function truncate_queue() {
		global $wpdb, $bb_background_updater;

		$this->assertNotEmpty( $bb_background_updater, 'The background updater must be initialised for these tests.' );

		$table = $bb_background_updater::$table_name;
		$wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore
	}

	/**
	 * Fetch queued fan-out rows, unserialized.
	 *
	 * @return array[] Each row: [ 'id' => int, 'callback' => string, 'args' => array ].
	 */
	protected function get_queue_rows() {
		global $wpdb, $bb_background_updater;

		$table = $bb_background_updater::$table_name;
		$rows  = $wpdb->get_results( "SELECT id, data FROM {$table} WHERE `group` = 'send_notifications_to_subscribers' ORDER BY id ASC" ); // phpcs:ignore

		$out = array();
		foreach ( $rows as $row ) {
			$data  = maybe_unserialize( $row->data );
			$out[] = array(
				'id'       => (int) $row->id,
				'callback' => isset( $data['callback'] ) ? $data['callback'] : '',
				'args'     => isset( $data['args'][0] ) ? $data['args'][0] : array(),
			);
		}

		return $out;
	}

	/**
	 * Create $count users each subscribed to the given item.
	 *
	 * @param int $count   Number of subscribers.
	 * @param int $item_id Item ID.
	 * @return int[] User IDs in creation order.
	 */
	protected function create_subscribers( $count, $item_id ) {
		$user_ids = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$user_id = self::factory()->user->create();

			$subscription = bb_create_subscription(
				array(
					'type'    => self::$type,
					'user_id' => $user_id,
					'item_id' => $item_id,
					'status'  => true,
				)
			);

			$this->assertNotWPError( $subscription, 'Subscription creation must succeed for the test type.' );

			$user_ids[] = $user_id;
		}

		return $user_ids;
	}

	/**
	 * Collect the user_ids multiset across all queued chunk rows.
	 *
	 * @return int[]
	 */
	protected function queued_chunk_user_ids() {
		$ids = array();

		foreach ( $this->get_queue_rows() as $row ) {
			if ( ! empty( $row['args']['user_ids'] ) ) {
				$ids = array_merge( $ids, array_map( 'intval', (array) $row['args']['user_ids'] ) );
			}
		}

		return $ids;
	}

	/**
	 * The light single-column fetch returns int user ids in subscription-id order.
	 */
	public function test_get_user_id_fields_returns_ints_in_id_order() {
		$item_id  = $this->next_item_id();
		$user_ids = $this->create_subscribers( 5, $item_id );

		$result = bb_get_subscription_users(
			array(
				'type'     => self::$type,
				'item_id'  => $item_id,
				'status'   => true,
				'order_by' => 'id',
				'order'    => 'ASC',
				'count'    => false,
			),
			true
		);

		$this->assertSame( $user_ids, $result['subscriptions'], 'user_id fetch must preserve subscription-id order.' );

		foreach ( $result['subscriptions'] as $value ) {
			$this->assertIsInt( $value, 'Light fetch must int-cast id columns.' );
		}
	}

	/**
	 * The light single-column fetch must not warm per-row object cache entries.
	 */
	public function test_get_light_path_primes_no_per_row_cache() {
		$item_id = $this->next_item_id();
		$this->create_subscribers( 3, $item_id );

		// Row ids for the created subscriptions.
		$rows = BB_Subscriptions::get(
			array(
				'type'    => self::$type,
				'item_id' => $item_id,
				'fields'  => 'id',
				'count'   => false,
				'cache'   => false,
			)
		);
		$this->assertNotEmpty( $rows['subscriptions'] );

		wp_cache_flush();

		bb_get_subscription_users(
			array(
				'type'    => self::$type,
				'item_id' => $item_id,
				'count'   => false,
			),
			true
		);

		foreach ( $rows['subscriptions'] as $subscription_id ) {
			$this->assertFalse(
				wp_cache_get( $subscription_id, 'bb_subscriptions' ),
				'The light branch must not prime per-row caches.'
			);
		}
	}

	/**
	 * A junk order_by value falls back instead of breaking the query.
	 */
	public function test_get_unknown_order_by_falls_back_to_date_recorded() {
		$item_id = $this->next_item_id();
		$this->create_subscribers( 3, $item_id );

		$result = BB_Subscriptions::get(
			array(
				'type'     => self::$type,
				'item_id'  => $item_id,
				'fields'   => 'user_id',
				'order_by' => 'evil; DROP TABLE x--',
				'count'    => false,
				'cache'    => false,
			)
		);

		$this->assertCount( 3, $result['subscriptions'], 'A junk order_by must fall back, not break the query.' );
	}

	/**
	 * order_by 'in' without include must not emit invalid SQL.
	 */
	public function test_get_order_by_in_with_empty_include_falls_back() {
		$item_id = $this->next_item_id();
		$this->create_subscribers( 2, $item_id );

		$result = BB_Subscriptions::get(
			array(
				'type'     => self::$type,
				'item_id'  => $item_id,
				'fields'   => 'user_id',
				'order_by' => 'in',
				'count'    => false,
				'cache'    => false,
			)
		);

		$this->assertCount( 2, $result['subscriptions'], "order_by 'in' without include must not produce invalid SQL." );
	}

	/**
	 * cache => false reads must not leave an incremented-cache entry behind.
	 */
	public function test_get_cache_false_does_not_write_incremented_cache() {
		$item_id = $this->next_item_id();
		$this->create_subscribers( 2, $item_id );

		$captured = array();
		$capture  = function ( $sql ) use ( &$captured ) {
			$captured[] = $sql;

			return $sql;
		};
		add_filter( 'bb_subscriptions_get_paged_subscriptions_sql', $capture );

		BB_Subscriptions::get(
			array(
				'type'    => self::$type,
				'item_id' => $item_id,
				'fields'  => 'id',
				'count'   => false,
				'cache'   => false,
			)
		);

		remove_filter( 'bb_subscriptions_get_paged_subscriptions_sql', $capture );

		$this->assertNotEmpty( $captured );
		$this->assertFalse( bp_core_get_incremented_cache( end( $captured ), 'bb_subscriptions' ), 'A one-off read must not populate the incremented cache.' );
	}

	/**
	 * An item with no subscribers returns an empty list.
	 */
	public function test_get_zero_rows_returns_empty_array() {
		$result = BB_Subscriptions::get(
			array(
				'type'    => self::$type,
				'item_id' => 999999,
				'fields'  => 'user_id',
				'count'   => false,
				'cache'   => false,
			)
		);

		$this->assertSame( array(), $result['subscriptions'] );
	}

	/**
	 * A single subscriber is sent directly, not queued.
	 */
	public function test_dispatcher_direct_call_for_single_subscriber() {
		$item_id  = $this->next_item_id();
		$user_ids = $this->create_subscribers( 1, $item_id );

		bb_send_notifications_to_subscribers(
			array(
				'type'              => self::$type,
				'item_id'           => $item_id,
				'notification_from' => 'bb_test_fanout_note',
				'data'              => array(),
			)
		);

		$this->assertCount( 1, self::$sent, 'A single subscriber must be sent directly, not queued.' );
		$this->assertSame( $user_ids, array_map( 'intval', (array) self::$sent[0]['user_ids'] ) );
		$this->assertSame( array(), $this->get_queue_rows(), 'No queue rows for the direct path.' );
	}

	/**
	 * An at-threshold list is chunked with every subscriber exactly once.
	 */
	public function test_dispatcher_small_list_queues_chunks_exactly_once() {
		add_filter( 'bb_subscription_background_fanout_min_count', array( $this, 'filter_five' ) );
		add_filter( 'bb_subscription_queue_min_count', array( $this, 'filter_two' ) );

		$item_id  = $this->next_item_id();
		$user_ids = $this->create_subscribers( 5, $item_id );

		bb_send_notifications_to_subscribers(
			array(
				'type'              => self::$type,
				'item_id'           => $item_id,
				'notification_from' => 'bb_test_fanout_note',
				'data'              => array(),
			)
		);

		$this->assertSame( array(), self::$sent, 'At-threshold lists queue chunks; no direct sends.' );

		$queued = $this->queued_chunk_user_ids();
		sort( $queued );
		sort( $user_ids );
		$this->assertSame( $user_ids, $queued, 'Every subscriber must appear in exactly one queued chunk.' );
	}

	/**
	 * Above the threshold exactly one fan-out row is queued.
	 */
	public function test_dispatcher_over_threshold_queues_single_batch_row() {
		add_filter( 'bb_subscription_background_fanout_min_count', array( $this, 'filter_five' ) );

		$item_id = $this->next_item_id();
		$this->create_subscribers( 6, $item_id );

		bb_send_notifications_to_subscribers(
			array(
				'type'              => self::$type,
				'item_id'           => $item_id,
				'notification_from' => 'bb_test_fanout_note',
				'data'              => array(),
			)
		);

		$rows = $this->get_queue_rows();
		$this->assertCount( 1, $rows, 'Above threshold: exactly one fan-out row.' );
		$this->assertSame( 'bb_send_notifications_to_subscribers_batch', $rows[0]['callback'] );
		$this->assertSame( 0, (int) $rows[0]['args']['last_id'] );
		$this->assertSame( 1000, (int) $rows[0]['args']['per_page'], 'Default page size is carried in the row.' );
		$this->assertNotEmpty( $rows[0]['args']['fanout_id'], 'The fan-out row must carry a per-dispatch id for the cursor key.' );
		$this->assertSame( 'bb_test_fanout_note', $rows[0]['args']['notification_type'] );
		$this->assertArrayNotHasKey( 'user_ids', $rows[0]['args'], 'The fan-out row must not carry member ids.' );
	}

	/**
	 * Zero subscribers: no send, no queue row, no error.
	 */
	public function test_dispatcher_zero_subscribers_sends_nothing() {
		global $wpdb;

		bb_send_notifications_to_subscribers(
			array(
				'type'              => self::$type,
				'item_id'           => $this->next_item_id(),
				'notification_from' => 'bb_test_fanout_note',
				'data'              => array(),
			)
		);

		$this->assertSame( '', $wpdb->last_error );
		$this->assertSame( array(), self::$sent );
		$this->assertSame( array(), $this->get_queue_rows() );
	}

	/**
	 * Disabled subscriptions (status = 0) are never delivered.
	 */
	public function test_dispatcher_skips_disabled_subscriptions() {
		$item_id  = $this->next_item_id();
		$user_ids = $this->create_subscribers( 2, $item_id );

		bb_subscriptions_update_subscriptions_status( self::$type, $item_id, 0, get_current_blog_id() );

		bb_send_notifications_to_subscribers(
			array(
				'type'              => self::$type,
				'item_id'           => $item_id,
				'notification_from' => 'bb_test_fanout_note',
				'data'              => array(),
			)
		);

		$this->assertSame( array(), self::$sent, 'Disabled subscriptions must not be sent.' );
		$this->assertSame( array(), $this->queued_chunk_user_ids(), 'Disabled subscriptions must not be queued.' );
		$this->assertCount( 2, $user_ids );
	}

	/**
	 * Queued payloads drop the activity object and keep an activity.id token.
	 */
	public function test_compact_notification_data_strips_object_and_keeps_id() {
		$activity     = new stdClass();
		$activity->id = 4242;

		$compact = bb_subscriptions_compact_notification_data(
			array(
				'email_tokens' => array(
					'tokens' => array(
						'activity'    => $activity,
						'poster.name' => 'Tester',
					),
				),
			)
		);

		$this->assertArrayNotHasKey( 'activity', $compact['email_tokens']['tokens'] );
		$this->assertSame( 4242, $compact['email_tokens']['tokens']['activity.id'] );
		$this->assertSame( 4242, $compact['activity_id'], 'activity_id is backfilled for callers that only passed the object.' );
		$this->assertSame( 'Tester', $compact['email_tokens']['tokens']['poster.name'] );

		$plain = array( 'topic_id' => 5, 'email_tokens' => array( 'tokens' => array( 'x' => 'y' ) ) );
		$this->assertSame( $plain, bb_subscriptions_compact_notification_data( $plain ), 'Payloads without the object pass through unchanged.' );
	}

	/**
	 * A PHP_INT_MAX threshold (opt-out idiom) must not overflow into an invalid LIMIT.
	 */
	public function test_dispatcher_survives_php_int_max_threshold_filter() {
		global $wpdb;

		add_filter( 'bb_subscription_background_fanout_min_count', array( $this, 'filter_int_max' ) );
		add_filter( 'bb_subscription_queue_min_count', array( $this, 'filter_two' ) );

		$item_id  = $this->next_item_id();
		$user_ids = $this->create_subscribers( 3, $item_id );

		bb_send_notifications_to_subscribers(
			array(
				'type'              => self::$type,
				'item_id'           => $item_id,
				'notification_from' => 'bb_test_fanout_note',
				'data'              => array(),
			)
		);

		$this->assertSame( '', $wpdb->last_error, 'PHP_INT_MAX opt-out must not produce a SQL error.' );

		$queued = $this->queued_chunk_user_ids();
		sort( $queued );
		sort( $user_ids );
		$this->assertSame( $user_ids, $queued, 'PHP_INT_MAX opt-out keeps delivery in-request and complete.' );
	}

	/**
	 * Keyset paging covers every subscriber exactly once across full and partial pages.
	 */
	public function test_worker_pages_deliver_every_subscriber_exactly_once() {
		add_filter( 'bb_subscription_queue_min_count', array( $this, 'filter_two' ) );

		$item_id  = $this->next_item_id();
		$user_ids = $this->create_subscribers( 7, $item_id );

		$args = array(
			'type'              => self::$type,
			'item_id'           => $item_id,
			'blog_id'           => get_current_blog_id(),
			'data'              => array(),
			'notification_type' => 'bb_test_fanout_note',
			'notification_from' => 'bb_test_fanout_note',
			'last_id'           => 0,
			'per_page'          => 3,
		);

		$where_filters_before = $this->count_filter_callbacks( 'bb_subscriptions_get_where_conditions' );

		// Drive the chain to completion, executing each queued next-page row.
		$guard = 0;
		while ( $args && $guard++ < 10 ) {
			$this->truncate_page_rows_only();
			bb_send_notifications_to_subscribers_batch( $args );

			$args = null;
			foreach ( $this->get_queue_rows() as $row ) {
				if ( 'bb_send_notifications_to_subscribers_batch' === $row['callback'] ) {
					$args = $row['args'];
				}
			}
		}

		$queued = $this->queued_chunk_user_ids();
		sort( $queued );
		sort( $user_ids );
		$this->assertSame( $user_ids, $queued, 'Keyset paging must cover every subscriber exactly once.' );
		$this->assertSame( $where_filters_before, $this->count_filter_callbacks( 'bb_subscriptions_get_where_conditions' ), 'The keyset where-filter closure must not leak past the worker run.' );
	}

	/**
	 * Running the same page row twice without draining must not fork a second chain.
	 */
	public function test_worker_next_page_row_is_queued_once_for_concurrent_runs() {
		add_filter( 'bb_subscription_queue_min_count', array( $this, 'filter_two' ) );

		$item_id = $this->next_item_id();
		$this->create_subscribers( 4, $item_id );

		$page_args = array(
			'type'              => self::$type,
			'item_id'           => $item_id,
			'blog_id'           => get_current_blog_id(),
			'data'              => array(),
			'notification_type' => 'bb_test_fanout_note',
			'notification_from' => 'bb_test_fanout_note',
			'last_id'           => 0,
			'per_page'          => 3,
			'fanout_id'         => 'test-fanout-id',
		);

		// Two workers executing the same page row: the second resumes from the
		// cursor (page 2, partial) and must not insert a second next-page row.
		bb_send_notifications_to_subscribers_batch( $page_args );
		bb_send_notifications_to_subscribers_batch( $page_args );

		$next_rows = array_filter(
			$this->get_queue_rows(),
			function ( $row ) {
				return 'bb_send_notifications_to_subscribers_batch' === $row['callback'];
			}
		);

		$this->assertCount( 1, $next_rows, 'Exactly one next-page row may exist for a chain.' );
	}

	/**
	 * A partial page ends the chain and removes the durable cursor.
	 */
	public function test_worker_partial_page_ends_chain_and_cleans_cursor() {
		global $wpdb;

		add_filter( 'bb_subscription_queue_min_count', array( $this, 'filter_two' ) );

		$item_id = $this->next_item_id();
		$this->create_subscribers( 2, $item_id );

		bb_send_notifications_to_subscribers_batch(
			array(
				'type'              => self::$type,
				'item_id'           => $item_id,
				'blog_id'           => get_current_blog_id(),
				'data'              => array(),
				'notification_type' => 'bb_test_fanout_note',
				'notification_from' => 'bb_test_fanout_note',
				'last_id'           => 0,
				'per_page'          => 3,
			)
		);

		$rows      = $this->get_queue_rows();
		$page_rows = array_filter(
			$rows,
			function ( $row ) {
				return 'bb_send_notifications_to_subscribers_batch' === $row['callback'];
			}
		);

		$this->assertCount( 0, $page_rows, 'A partial page must not re-queue the chain.' );
		$this->assertCount( 1, $rows, 'Two subscribers with a chunk size of two make exactly one chunk row.' );

		$cursors = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'bb_sub_fanout_cursor_%'" ); // phpcs:ignore
		$this->assertSame( 0, $cursors, 'The chain cursor must be deleted at termination.' );
	}

	/**
	 * A re-run of an already-processed page row resumes from the durable cursor.
	 */
	public function test_worker_rerun_resumes_from_cursor_without_duplicates() {
		add_filter( 'bb_subscription_queue_min_count', array( $this, 'filter_two' ) );

		$item_id  = $this->next_item_id();
		$user_ids = $this->create_subscribers( 4, $item_id );

		$page_args = array(
			'type'              => self::$type,
			'item_id'           => $item_id,
			'blog_id'           => get_current_blog_id(),
			'data'              => array(),
			'notification_type' => 'bb_test_fanout_note',
			'notification_from' => 'bb_test_fanout_note',
			'last_id'           => 0,
			'per_page'          => 3,
		);

		bb_send_notifications_to_subscribers_batch( $page_args );
		$first_run = $this->queued_chunk_user_ids();

		// Re-run the SAME page row (stale-lock takeover / mid-task fatal retry).
		bb_send_notifications_to_subscribers_batch( $page_args );
		$after_rerun = $this->queued_chunk_user_ids();

		// The re-run resumes past the durable cursor: page 1 must not be queued
		// a second time, and page 2 must be — the union is every subscriber once.
		$this->assertCount( 3, $first_run, 'The first run queues exactly the first page.' );
		sort( $after_rerun );
		sort( $user_ids );
		$this->assertSame( $user_ids, $after_rerun, 'After a re-run every subscriber is queued exactly once.' );
	}

	/**
	 * A deleted source activity terminates the chain and removes the cursor.
	 */
	public function test_worker_deleted_activity_terminates_chain() {
		global $wpdb;

		$item_id = $this->next_item_id();
		$this->create_subscribers( 3, $item_id );

		$args = array(
			'type'              => self::$type,
			'item_id'           => $item_id,
			'blog_id'           => get_current_blog_id(),
			'data'              => array( 'activity_id' => 99999999 ),
			'notification_type' => 'bb_test_fanout_note',
			'notification_from' => 'bb_test_fanout_note',
			'last_id'           => 0,
			'per_page'          => 3,
			'fanout_id'         => 'test-deleted-activity',
		);

		// Pre-seed the chain's cursor (as a previous page run would have) so the
		// assertion proves the terminating path deletes it.
		$cursor_key = 'bb_sub_fanout_cursor_' . md5( maybe_serialize( array( $args['type'], $args['item_id'], (int) $args['blog_id'], $args['notification_from'], $args['data'], $args['fanout_id'] ) ) );
		update_option( $cursor_key, 7, false );

		bb_send_notifications_to_subscribers_batch( $args );

		$this->assertSame( array(), $this->get_queue_rows(), 'A deleted source activity must terminate the chain without queueing.' );
		$this->assertFalse( get_option( $cursor_key ), 'The terminating path must delete the chain cursor.' );
	}

	/**
	 * A trashed (or spammed/pending) forum topic ends the chain like a deleted one; a published topic does not.
	 */
	public function test_worker_trashed_topic_terminates_chain_but_published_topic_proceeds() {
		if ( ! function_exists( 'bbp_get_topic_post_type' ) ) {
			$this->markTestSkipped( 'Forums component not loaded.' );
		}

		add_filter( 'bb_subscription_queue_min_count', array( $this, 'filter_two' ) );

		$item_id = $this->next_item_id();
		$this->create_subscribers( 2, $item_id );

		$base_args = array(
			'type'              => self::$type,
			'item_id'           => $item_id,
			'blog_id'           => get_current_blog_id(),
			'notification_type' => 'bb_test_fanout_note',
			'notification_from' => 'bb_test_fanout_note',
			'last_id'           => 0,
			'per_page'          => 3,
			'fanout_id'         => 'test-trashed-topic',
		);

		// Trashed topic: chain must stop before queueing anything and remove its cursor.
		$trashed_topic = self::factory()->post->create(
			array(
				'post_type'   => bbp_get_topic_post_type(),
				'post_status' => bbp_get_trash_status_id(),
			)
		);
		$args          = $base_args;
		$args['data']  = array( 'topic_id' => $trashed_topic );
		$cursor_key    = 'bb_sub_fanout_cursor_' . md5( maybe_serialize( array( $args['type'], $args['item_id'], (int) $args['blog_id'], $args['notification_from'], $args['data'], $args['fanout_id'] ) ) );
		update_option( $cursor_key, 3, false );

		bb_send_notifications_to_subscribers_batch( $args );

		$this->assertSame( array(), $this->get_queue_rows(), 'A trashed topic must end the chain without queueing.' );
		$this->assertFalse( get_option( $cursor_key ), 'The trashed-topic path must delete the chain cursor.' );

		// Published topic: chain proceeds and queues the page.
		$live_topic   = self::factory()->post->create(
			array(
				'post_type'   => bbp_get_topic_post_type(),
				'post_status' => bbp_get_public_status_id(),
			)
		);
		$args['data'] = array( 'topic_id' => $live_topic );

		bb_send_notifications_to_subscribers_batch( $args );

		$this->assertCount( 1, $this->get_queue_rows(), 'A published topic must be fanned out (one chunk row for two subscribers).' );
	}

	/**
	 * The chunk claim refuses a duplicate run and is independent per chunk.
	 */
	public function test_claim_refuses_duplicate_run_and_allows_other_chunks() {
		$args = array(
			'type'              => self::$type,
			'item_id'           => 4001,
			'blog_id'           => get_current_blog_id(),
			'notification_from' => 'bb_test_fanout_note',
			'data'              => array( 'activity_id' => 123 ),
			'user_ids'          => array( 1, 2, 3 ),
		);

		$this->assertTrue( bb_subscriptions_claim_notification_chunk( $args ) );
		$this->assertFalse( bb_subscriptions_claim_notification_chunk( $args ), 'The same chunk must not be claimable twice.' );

		$other             = $args;
		$other['user_ids'] = array( 4, 5, 6 );
		$this->assertTrue( bb_subscriptions_claim_notification_chunk( $other ), 'A different chunk must claim independently.' );
	}

	/**
	 * Claim, refresh and completion all land under the pristine key when user_ids is mutated.
	 */
	public function test_claim_key_survives_author_removal_mutation() {
		$pristine = array(
			'type'              => self::$type,
			'item_id'           => 4002,
			'blog_id'           => get_current_blog_id(),
			'notification_from' => 'bb_test_fanout_note',
			'data'              => array( 'activity_id' => 456 ),
			'user_ids'          => array( 10, 11, 12 ),
		);

		$chunk_key = bb_subscriptions_get_notification_chunk_key( $pristine );
		$this->assertTrue( bb_subscriptions_claim_notification_chunk( $pristine, $chunk_key ) );

		// Simulate the callbacks' author removal, then touch + complete with
		// the pristine key exactly as the send callbacks do.
		$mutated = $pristine;
		unset( $mutated['user_ids'][1] );

		$this->assertNotSame( $chunk_key, bb_subscriptions_get_notification_chunk_key( $mutated ), 'Removing the author changes the derived key — the premise of the explicit key.' );

		bb_subscriptions_touch_notification_chunk_claim( $mutated, $chunk_key );
		$this->assertNotFalse( wp_cache_get( 'bb_sub_chunk_claim_' . $chunk_key, 'bb_subscriptions' ), 'The refresh must land on the pristine claim key.' );

		bb_subscriptions_complete_notification_chunk( $mutated, $chunk_key );

		$this->assertNotFalse( wp_cache_get( 'bb_sub_chunk_done_' . $chunk_key, 'bb_subscriptions' ), 'The completion marker must land on the pristine key.' );
		$this->assertFalse( wp_cache_get( 'bb_sub_chunk_claim_' . $chunk_key, 'bb_subscriptions' ), 'Completion releases the pristine claim.' );
		$this->assertFalse( wp_cache_get( 'bb_sub_chunk_done_' . bb_subscriptions_get_notification_chunk_key( $mutated ), 'bb_subscriptions' ), 'Nothing may be written under the mutated key.' );

		// The claim is gone (as if its TTL lapsed), so only the marker can refuse.
		$this->assertFalse(
			bb_subscriptions_claim_notification_chunk( $pristine ),
			'A late duplicate row (pristine payload) must be refused by the completion marker.'
		);
	}

	/**
	 * A failed cache write (not a lost race) must fail open so no chunk is dropped.
	 */
	public function test_claim_fails_open_when_cache_writes_fail() {
		$args = array(
			'type'              => self::$type,
			'item_id'           => $this->next_item_id(),
			'blog_id'           => get_current_blog_id(),
			'notification_from' => 'bb_test_fanout_note',
			'data'              => array( 'activity_id' => 789 ),
			'user_ids'          => array( 20, 21 ),
		);

		wp_suspend_cache_addition( true );
		$claimed = bb_subscriptions_claim_notification_chunk( $args );
		wp_suspend_cache_addition( false );

		$this->assertTrue( $claimed, 'An infrastructure failure must not be mistaken for a lost race.' );
		$this->assertFalse( wp_cache_get( 'bb_sub_chunk_claim_' . bb_subscriptions_get_notification_chunk_key( $args ), 'bb_subscriptions' ), 'Nothing was stored while additions were suspended.' );

		// With a working cache the same chunk is a normal claim / lost race.
		$this->assertTrue( bb_subscriptions_claim_notification_chunk( $args ) );
		$this->assertFalse( bb_subscriptions_claim_notification_chunk( $args ) );
	}

	/**
	 * A completed chunk is refused even after its claim has expired.
	 */
	public function test_completed_chunk_is_refused_after_claim_expiry() {
		$args = array(
			'type'              => self::$type,
			'item_id'           => $this->next_item_id(),
			'blog_id'           => get_current_blog_id(),
			'notification_from' => 'bb_test_fanout_note',
			'notification_type' => 'bb_test_fanout_note',
			'data'              => array( 'activity_id' => 321 ),
			'user_ids'          => array( 30, 31, 32 ),
		);
		$key  = bb_subscriptions_get_notification_chunk_key( $args );

		$this->assertTrue( bb_subscriptions_claim_notification_chunk( $args ) );
		bb_subscriptions_complete_notification_chunk( $args );

		// Simulate the claim TTL lapsing: only the completion marker remains.
		wp_cache_delete( 'bb_sub_chunk_claim_' . $key, 'bb_subscriptions' );

		$this->assertFalse( bb_subscriptions_claim_notification_chunk( $args ), 'A late duplicate row must be refused by the completion marker alone.' );

		// Without the marker the same chunk would be claimable again — proves the marker is what refuses.
		wp_cache_delete( 'bb_sub_chunk_done_' . $key, 'bb_subscriptions' );
		$this->assertTrue( bb_subscriptions_claim_notification_chunk( $args ) );
	}

	/**
	 * Filter helper: 2.
	 */
	public function filter_two() {
		return 2;
	}

	/**
	 * Filter helper: 5.
	 */
	public function filter_five() {
		return 5;
	}

	/**
	 * Filter helper: PHP_INT_MAX.
	 */
	public function filter_int_max() {
		return PHP_INT_MAX;
	}

	/**
	 * Remove only fan-out PAGE rows, keeping chunk rows, so repeated worker
	 * invocations in a loop do not re-read their own next-page row twice.
	 */
	protected function truncate_page_rows_only() {
		global $wpdb, $bb_background_updater;

		$table = $bb_background_updater::$table_name;
		$rows  = $wpdb->get_results( "SELECT id, data FROM {$table} WHERE `group` = 'send_notifications_to_subscribers'" ); // phpcs:ignore

		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row->data );
			if ( isset( $data['callback'] ) && 'bb_send_notifications_to_subscribers_batch' === $data['callback'] ) {
				$wpdb->delete( $table, array( 'id' => $row->id ) ); // phpcs:ignore
			}
		}
	}
}
