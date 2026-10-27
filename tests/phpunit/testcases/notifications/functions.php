<?php

/**
 * @group notifications
 */
class BP_Tests_Notifications_Functions extends BP_UnitTestCase {

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_save() {
		$u = self::factory()->user->create();

		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'item_id'        => 1
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		$incrementor_before = bp_notifications_get_user_count_cache_incrementor( $u );

		// Trigger invalidation via save
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'item_id'        => 2
		) );

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
		$this->assertNotSame( $incrementor_before, bp_notifications_get_user_count_cache_incrementor( $u ), 'The count cache incrementor should change after invalidation.' );
		$this->assertEquals( 3, bp_notifications_get_unread_notification_count( $u ), 'Unread count should reflect the change, not a stale cached value.' );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_delete() {
		$u  = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		$incrementor_before = bp_notifications_get_user_count_cache_incrementor( $u );

		// delete
		BP_Notifications_Notification::delete( array( 'id' => $n1, ) );

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
		$this->assertNotSame( $incrementor_before, bp_notifications_get_user_count_cache_incrementor( $u ), 'The count cache incrementor should change after invalidation.' );
		$this->assertEquals( 1, bp_notifications_get_unread_notification_count( $u ), 'Unread count should reflect the change, not a stale cached value.' );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_update_user_id() {
		$u = self::factory()->user->create();

		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		$incrementor_before = bp_notifications_get_user_count_cache_incrementor( $u );

		// mark all notifications by user as read
		BP_Notifications_Notification::update(
			array( 'is_new'  => false ),
			array( 'user_id' => $u    )
		);

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
		$this->assertNotSame( $incrementor_before, bp_notifications_get_user_count_cache_incrementor( $u ), 'The count cache incrementor should change after invalidation.' );
		$this->assertEquals( 0, bp_notifications_get_unread_notification_count( $u ), 'Unread count should reflect the change, not a stale cached value.' );
	}

	/**
	 * @group cache
	 */
	public function test_cache_invalidation_all_for_user_on_update_id() {
		$u  = self::factory()->user->create();
		$n1 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );

		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		// prime cache
		$count = bp_notifications_get_unread_notification_count( $u );

		// just to be sure...
		$this->assertEquals( 2, $count, 'Cache count should be 2 before invalidation.' );

		$incrementor_before = bp_notifications_get_user_count_cache_incrementor( $u );

		// mark one notification as read
		BP_Notifications_Notification::update(
			array( 'is_new' => false ),
			array( 'id'     => $n1   )
		);

		$this->assertFalse( wp_cache_get( 'all_for_user_' . $u, 'bp_notifications' ) );
		$this->assertNotSame( $incrementor_before, bp_notifications_get_user_count_cache_incrementor( $u ), 'The count cache incrementor should change after invalidation.' );
		$this->assertEquals( 1, bp_notifications_get_unread_notification_count( $u ), 'Unread count should reflect the change, not a stale cached value.' );
	}

	/**
	 * Counts for the same user with different query args must not share a cache slot.
	 *
	 * @group cache
	 * @ticket PROD-10344
	 */
	public function test_get_total_count_cache_does_not_collide_across_query_args() {
		$u = self::factory()->user->create();

		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u,
			'is_new'         => true,
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'is_new'         => true,
		) );
		self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u,
			'is_new'         => false,
		) );

		// Name the components explicitly: the default component list depends on
		// which components are active, which is not what this test is about.
		$components = array( 'groups', 'messages' );

		$queries = array(
			'unread'          => array( array( 'user_id' => $u, 'is_new' => true, 'component_name' => $components ), 2 ),
			'all'             => array( array( 'user_id' => $u, 'is_new' => 'both', 'component_name' => $components ), 3 ),
			'read'            => array( array( 'user_id' => $u, 'is_new' => false, 'component_name' => $components ), 1 ),
			'unread groups'   => array( array( 'user_id' => $u, 'is_new' => true, 'component_name' => 'groups' ), 1 ),
			'unread messages' => array( array( 'user_id' => $u, 'is_new' => true, 'component_name' => 'messages' ), 1 ),
		);

		// Prime every variant first, then read them all back from cache.
		foreach ( array( 'prime', 'cached' ) as $pass ) {
			foreach ( $queries as $label => $query ) {
				$this->assertSame(
					$query[1],
					BP_Notifications_Notification::get_total_count( $query[0] ),
					sprintf( 'Count for "%s" (%s pass).', $label, $pass )
				);
			}
		}
	}

	/**
	 * Conditions added through the where-conditions filter must be part of the cache key.
	 *
	 * @group cache
	 * @ticket PROD-10344
	 */
	public function test_get_total_count_cache_reflects_where_conditions_filter() {
		$u = self::factory()->user->create();

		self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u,
		) );

		$args = array( 'user_id' => $u, 'is_new' => true );

		$this->assertSame( 1, BP_Notifications_Notification::get_total_count( $args ) );

		$exclude_all = function ( $where_sql ) {
			return $where_sql . ' AND 1 = 0';
		};

		add_filter( 'bb_notifications_get_where_conditions', $exclude_all );
		$this->assertSame( 0, BP_Notifications_Notification::get_total_count( $args ), 'A filtered query must not reuse the unfiltered cached count.' );
		remove_filter( 'bb_notifications_get_where_conditions', $exclude_all );

		$this->assertSame( 1, BP_Notifications_Notification::get_total_count( $args ), 'The unfiltered count must still be served after the filter is removed.' );
	}

	/**
	 * The count cache key must be a plain hash, safe for Memcached (no whitespace).
	 *
	 * @group cache
	 * @ticket PROD-10344
	 */
	public function test_count_cache_key_is_a_plain_hash() {
		$u = self::factory()->user->create();

		$key = bp_notifications_get_count_cache_key( $u, 'SELECT COUNT(*) FROM wp_bp_notifications n WHERE n.user_id = ' . $u );

		$this->assertSame( 1, preg_match( '/^[a-f0-9]{32}$/', $key ), 'The key should be a 32-character hash.' );
	}

	/**
	 * @group bp_notifications_update_meta_cache
	 */
	public function test_bp_notifications_update_meta_cache() {
		$u = self::factory()->user->create();

		$n1 = self::factory()->notification->create( array(
			'component_name' => 'messages',
			'user_id'        => $u
		) );

		$n2 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u
		) );

		// Add cache for each notification.
		bp_notifications_update_meta( $n1, 'meta', 'data' );
		bp_notifications_update_meta( $n1, 'data', 'meta' );
		bp_notifications_update_meta( $n2, 'meta', 'human' );

		// Prime cache.
		bp_notifications_get_meta( $n1, 'meta' );

		// Ensure an empty cache for second notification.
		wp_cache_delete( $n2, 'notification_meta' );

		// Update notification meta cache.
		bp_notifications_update_meta_cache( array( $n1, $n2 ) );

		$expected = array(
			$n1 => array(
				'meta' => array(
					'data',
				),
				'data' => array(
					'meta',
				),
			),
			$n2 => array(
				'meta' => array(
					'human',
				),
			),
		);

		$found = array(
			$n1 => wp_cache_get( $n1, 'notification_meta' ),
			$n2 => wp_cache_get( $n2, 'notification_meta' ),
		);

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group bp_notifications_add_notification
	 */
	public function test_bp_notifications_add_notification_no_dupes() {
		$args = array(
			'user_id'           => 5,
			'item_id'           => 10,
			'secondary_item_id' => 25,
			'component_name'    => 'messages',
			'component_action'  => 'new_message'
		);

		self::factory()->notification->create( $args );

		$this->assertFalse( bp_notifications_add_notification( $args ) );
	}

	/**
	 * @group bp_notifications_add_notification
	 */
	public function test_bp_notifications_add_notification_allow_duplicate() {
		$args = array(
			'user_id'           => 5,
			'item_id'           => 10,
			'secondary_item_id' => 25,
			'component_name'    => 'messages',
			'component_action'  => 'new_message'
		);

		self::factory()->notification->create( $args );

		$args['allow_duplicate'] = true;

		$this->assertNotEmpty( bp_notifications_add_notification( $args ) );
	}

	/**
	 * @group bp_notifications_get_unread_notification_count
	 * @group cache
	 */
	public function test_bp_notifications_get_unread_notification_count_cache() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		// prime cache
		bp_notifications_get_unread_notification_count( $u2 );

		// mark the created notification as read
		bp_notifications_mark_notifications_by_item_id( $u2, 99, 'messages', 'new_message', $u1 );

		// now grab the updated notification count
		$n = bp_notifications_get_unread_notification_count( $u2 );

		// assert
		$this->assertEquals( 0, $n );
	}

	/**
	 * @group bp_has_notifications
	 */
	public function test_bp_has_notifications_filtering() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create a mixture of different notifications
		self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 100,
			'user_id'           => $u2,
			'secondary_item_id' => $u1,
			'is_new'            => true
		) );

		// now fetch only activity notifications
		bp_has_notifications( array(
			'component_name' => 'activity',
			'user_id'        => $u2
		) );

		// assert
		$this->assertEquals( 2, buddypress()->notifications->query_loop->total_notification_count );
	}

	/**
	 * @group bp_notifications_delete_notifications_on_user_delete
	 * @ticket BP6681
	 */
	public function test_bp_notifications_delete_notifications_on_user_delete_should_delete_all_notifications() {
		$u = self::factory()->user->create();

		// Create notifications
		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'messages',
			'component_action'  => 'new_message',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$n2 = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		$n3 = self::factory()->notification->create( array(
			'component_name' => 'groups',
			'user_id'        => $u,
		) );

		$n4 = self::factory()->notification->create( array(
			'component_name'   => 'friends',
			'component_action' => 'friendship_request',
			'user_id'          => $u,
		) );

		// Create notification for non-core component
		$n5 = self::factory()->notification->create( array(
			'component_name'    => 'foo',
			'component_action'  => 'bar',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		global $wpdb, $bp;

		/**
		 * Can't use BP_Notifications_Notification::get(), because class::parse_args,
		 * checks against bp_notifications_get_registered_components()
		 * and if component is disabled it will be ignored.
		 */
		$query = $wpdb->prepare( "SELECT id FROM {$bp->notifications->table_name} WHERE user_id = %d and is_new = 1", $u );

		// Make sure notifications have been added.
		$found1 = $wpdb->get_col( $query );
		$this->assertEqualSets( array( $n1, $n2, $n3, $n4, $n5 ), $found1 );

		wp_delete_user( $u );

		// Check if notifications are deleted.
		$found2 = $wpdb->get_col( $query );
		$this->assertEmpty( $found2 );
	}

	/**
	 * @group  notification_callback
	 * @ticket BP7141
	 */
	public function test_notification_callback_parameter_integrity() {
		$u = self::factory()->user->create();

		$n = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		// Override activity notification callback so we can test integrity.
		buddypress()->activity->notification_callback = array( $this, 'dummy_notification_callback' );

		// Fetch notifications with string format.
		bp_notifications_get_notifications_for_user( $u, 'string' );

		// Assert!
		// @todo When we cast all numeric strings as integers, this needs to be changed.
		$expected = array(
			'action'            => 'new_at_mention',
			'item_id'           => '99',
			'secondary_item_id' => '0',
			'total_items'       => 1,
			'id'                => (string) $n,
			'format'            => 'string'
		);
		$this->assertEquals( $expected, $this->n_args );

		// Fetch notifications with object format this time.
		bp_notifications_get_notifications_for_user( $u, 'object' );

		// Assert!
		$expected['format'] = 'array';
		$this->assertEquals( $expected, $this->n_args );

		// Reset!
		buddypress()->activity->notification_callback = 'bp_activity_format_notifications';
		unset( $this->n_args );
	}

	/**
	 * Used in test_notification_callback_parameter_integrity() test.
	 */
	public function dummy_notification_callback( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $id = 0 ) {
		$this->n_args = compact( 'action', 'item_id', 'secondary_item_id', 'total_items', 'id', 'format' );
	}

	/**
	 * @group cache
	 * @ticket BP7130
	 */
	public function test_get_grouped_notifications_for_user_cache_invalidation() {
		$u = self::factory()->user->create();

		$n1 = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 99,
			'user_id'           => $u,
		) );

		// Prime cache.
		$found = bp_notifications_get_grouped_notifications_for_user( $u );
		$this->assertEquals( 1, $found[0]->total_count );

		$n2 = self::factory()->notification->create( array(
			'component_name'    => 'activity',
			'component_action'  => 'new_at_mention',
			'item_id'           => 100,
			'user_id'           => $u,
		) );

		$found = bp_notifications_get_grouped_notifications_for_user( $u );
		$this->assertEquals( 2, $found[0]->total_count );
	}
}
