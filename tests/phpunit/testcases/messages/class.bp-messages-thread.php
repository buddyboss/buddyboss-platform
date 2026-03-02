<?php

/**
 * @group BP_Messages_Thread
 * @group messages
 */
class BP_Tests_BP_Messages_Thread extends BP_UnitTestCase {

	/**
	 * @group cache
	 */
	public function test_construct_cache() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		// prime cache
		new BP_Messages_Thread( $message->thread_id );

		$thread_id = $message->thread_id;
		$before = null;
		$perpage = 10;
		$cache_key = "{$thread_id}{$before}{$perpage}";

		// Cache should exist
		$this->assertThat(
			wp_cache_get( $cache_key, 'bp_messages_threads' ),
			$this->logicalNot( $this->equalTo( false ) ),
			'Message thread cache should exist.'
		);
	}

	/**
	 * @group order
	 */
	public function test_construct_order_desc() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create thread
		$message_1 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo'
		) );

		$m1 = $message_1->id;

		// create reply
		$message_2 = self::factory()->message->create_and_get( array(
			'thread_id' => $message_1->thread_id,
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'content' => 'Bar',
			'show_log' => true
		) );
		$m2 = $message_2->id;

		// now get thread by DESC
		$thread = new BP_Messages_Thread( $message_1->thread_id, 'DESC' );

		// assert!
		$this->assertEquals(
			array( $m2, $m1 ),
			wp_list_pluck( $thread->messages, 'id' )
		);
	}

	/**
	 * @group get_current_threads_for_user
	 * @version 3.1.1 Search no longer search for subject, only messages
	 */
	public function test_get_current_threads_for_user_with_search_terms_inbox() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$message_1 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
			'content' => 'foo'
		) );

		$message_2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u3 ),
			'subject' => 'Bar',
			'content' => 'bar'
		) );

		$threads = BP_Messages_Thread::get_current_threads_for_user( array(
			'user_id' => $u3,
			'search_terms' => 'ar',
		) );

		$expected = array( $message_2->thread_id );
		$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

		$this->assertSame( $expected, $found );
	}

	/**
	 * @group get_current_threads_for_user
	 * @deprecated 3.1.1 Nor more sendbox
	 */
	// public function test_get_current_threads_for_user_with_search_terms_sentbox() {
	// 	$u1 = self::factory()->user->create();
	// 	$u2 = self::factory()->user->create();

	// 	$message_1 = self::factory()->message->create_and_get( array(
	// 		'sender_id' => $u1,
	// 		'recipients' => array( $u2 ),
	// 		'subject' => 'Foo',
	// 	) );

	// 	$message_2 = self::factory()->message->create_and_get( array(
	// 		'sender_id' => $u1,
	// 		'recipients' => array( $u2 ),
	// 		'subject' => 'Bar',
	// 	) );

	// 	$threads = BP_Messages_Thread::get_current_threads_for_user( array(
	// 		'user_id' => $u1,
	// 		'box' => 'sentbox',
	// 		'search_terms' => 'ar',
	// 	) );

	// 	$expected = array( $message_2->thread_id );
	// 	$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

	// 	$this->assertSame( $expected, $found );
	// }

	/**
	 * @group get_current_threads_for_user
	 * @expectedDeprecated BP_Messages_Thread::get_current_threads_for_user
	 * @deprecated 3.1.1 no more sendbox
	 */
	// public function test_get_current_threads_for_user_with_old_args() {
	// 	$u1 = self::factory()->user->create();
	// 	$u2 = self::factory()->user->create();

	// 	$message_1 = self::factory()->message->create_and_get( array(
	// 		'sender_id' => $u1,
	// 		'recipients' => array( $u2 ),
	// 		'subject' => 'Foo',
	// 	) );

	// 	$message_2 = self::factory()->message->create_and_get( array(
	// 		'sender_id' => $u1,
	// 		'recipients' => array( $u2 ),
	// 		'subject' => 'Bar',
	// 	) );

	// 	$threads = BP_Messages_Thread::get_current_threads_for_user( $u1, 'sentbox', 'all', null, null, 'ar' );

	// 	$expected = array( $message_2->thread_id );
	// 	$found = wp_parse_id_list( wp_list_pluck( $threads['threads'], 'thread_id' ) );

	// 	$this->assertSame( $expected, $found );
	// }

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_should_cache_its_values() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $message->thread_id );
		$recipients = $thread->get_recipients();

		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();

		$this->assertEquals( $recipients, $recipients_cached );
		$this->assertEquals( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_thread_message_is_sent() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$thread = new BP_Messages_Thread( $message->thread_id );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		messages_new_message( array(
			'sender_id' => $u2,
			'thread_id' => $message->thread_id,
			'recipients' => array( $u1 ),
			'subject' => 'Bar',
			'content' => 'Baz',
		) );

		// Cache should be empty.
		$num_queries = $wpdb->num_queries;
		$recipients_uncached = $thread->get_recipients();
		$this->assertEquals( $num_queries + 2, $wpdb->num_queries );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_single_thread_is_deleted() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		messages_delete_thread( $t1 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_array_of_threads_is_deleted() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		messages_delete_thread( array( $t1 ) );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_thread_is_read() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		// Mark thread as read
		$current_user = get_current_user_id();
		$this->set_current_user( $u2 );
		messages_mark_thread_read( $t1 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group get_recipients
	 * @group cache
	 */
	public function test_get_recipients_cache_should_be_busted_when_thread_is_unread() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );
		$recipients = $thread->get_recipients();

		// Verify that the cache is populated.
		$num_queries = $wpdb->num_queries;
		$recipients_cached = $thread->get_recipients();
		$this->assertEquals( $num_queries, $wpdb->num_queries );

		// Mark thread as unread
		$current_user = get_current_user_id();
		$this->set_current_user( $u2 );
		messages_mark_thread_unread( $t1 );

		// Cache should be empty.
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $t1, 'bp_messages' ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group check_access
	 */
	public function test_check_access_valid_thread() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		// save recipient ID
		$thread = new BP_Messages_Thread( $t1 );
		$r1 = wp_list_pluck( $thread->recipients, 'id' );
		$r1 = array_pop( $r1 );

		$this->assertEquals( $r1, BP_Messages_Thread::check_access( $t1, $u1 ) );
	}

	/**
	 * @group check_access
	 */
	public function test_check_access_invalid_thread() {
		$this->assertEquals( null, BP_Messages_Thread::check_access( 999, 1 ) );
	}

	/**
	 * @group is_valid
	 */
	public function test_is_valid_valid_thread() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
		) );

		$t1 = $message->thread_id;

		$this->assertEquals( $t1, BP_Messages_Thread::is_valid( $t1 ) );
	}

	/**
	 * @group is_valid
	 */
	public function test_is_valid_invalid_thread() {
		$this->assertEquals( null, BP_Messages_Thread::is_valid( 999 ) );
	}

	/**
	 * @group last_message
	 */
	public function test_last_message_populated() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$date = bp_core_current_time();

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'subject' => 'Foo',
			'date_sent' => $date,
			'content' => 'Bar and baz.',
		) );

		$t1 = $message->thread_id;

		$thread = new BP_Messages_Thread( $t1 );

		$this->assertNotNull( $thread->last_message_id );
		$this->assertEquals( 'Foo', $thread->last_message_subject );
		$this->assertEquals( $u1, $thread->last_sender_id );
		$this->assertEquals( $date, $thread->last_message_date );
		$this->assertEquals( 'Bar and baz.', $thread->last_message_content );
	}

	/**
	 * @group messages backward compatibility
	 */
	public function test_existing_thread_should_get_the_latest() {
		global $wpdb;

		$bp = buddypress();

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$now = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - 10;

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now ),
		) );

		$message2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 2 ),
			'append_thread' => false
		) );

		$message3 = self::factory()->message->create_and_get( array(
			'thread_id' => $message->thread_id,
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 2 ),
			'append_thread' => false
		) );

		// make sure the threads are setup properly first
		$this->assertTrue($message->thread_id !== $message2->thread_id);
		$this->assertTrue($message->thread_id === $message3->thread_id);

		$existing_thread_id = BP_Messages_Message::get_existing_thread( [ $u2 ], $u1 );
		$this->assertEquals($message->thread_id, $existing_thread_id);

		// now add a new message
		$last_message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
		) );

		$this->assertEquals($message->thread_id, $last_message->thread_id);
	}

	/**
	 * @group thread search
	 */
	public function test_search_thread_should_not_include_deleted() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();
		$u4 = self::factory()->user->create();

		$current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$now = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - 10;

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 1 ),
		) );

		$message2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2, $u3 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 2 ),
			'content' => get_user_by('id', $u2)->display_name,
		) );

		$message3 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2, $u3, $u4 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 3 ),
			'content' => get_user_by('id', $u2)->display_name,
		) );

		$this->set_current_user( $u2 );

		$message4 = self::factory()->message->create_and_get( array(
			'sender_id' => $u2,
			'recipients' => array( $u3, $u4 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 4 ),
		) );

		$this->set_current_user( $u1 );

		messages_delete_thread($message->thread_id);

		$threads = BP_Messages_Thread::get_current_threads_for_user([
			'user_id' => $u1,
		]);

		$this->assertEquals([$message3->thread_id, $message2->thread_id], wp_list_pluck($threads['threads'], 'thread_id'));

		$threads = BP_Messages_Thread::get_current_threads_for_user([
			'user_id' => $u1,
			'search_terms' => get_user_by('id', $u2)->display_name
		]);

		$this->assertEquals([$message3->thread_id, $message2->thread_id], wp_list_pluck($threads['threads'], 'thread_id'));

		$this->set_current_user( $current_user );
	}

	/**
	 * @group thread search
	 */
	public function test_search_thread_should_include_user_or_message() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();
		$u4 = self::factory()->user->create();

		$current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$now = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - 10;

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 1 ),
			'content' => get_user_by('id', $u2)->display_name
		) );

		$message2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2, $u3 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 2 ),
			'content' => get_user_by('id', $u2)->display_name
		) );

		$message3 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u3, $u4 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 3 ),
		) );

		$message4 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u4 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 4 ),
			'content' => get_user_by('id', $u2)->display_name
		) );

		$this->set_current_user( $u2 );

		$message5 = self::factory()->message->create_and_get( array(
			'sender_id' => $u2,
			'recipients' => array( $u3 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 5 ),
		) );

		$message6 = self::factory()->message->create_and_get( array(
			'sender_id' => $u2,
			'recipients' => array( $u3, $u4 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 6 ),
			'content' => get_user_by('id', $u2)->display_name
		) );

		$this->set_current_user( $u1 );

		$threads = BP_Messages_Thread::get_current_threads_for_user([
			'user_id' => $u1,
			'search_terms' => get_user_by('id', $u2)->display_name
		]);

		$this->assertEquals([
			$message4->thread_id,
			$message2->thread_id,
			$message->thread_id
		], wp_list_pluck($threads['threads'], 'thread_id'));

		$this->set_current_user( $current_user );
	}

	/**
	 * @group thread search
	 */
	public function test_search_thread_should_include_other_user_message() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$now = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - 10;

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 1 ),
		) );

		$message2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u2,
			'recipients' => array( $u1 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 2 ),
			'content' => 'foo'
		) );

		$threads = BP_Messages_Thread::get_current_threads_for_user([
			'user_id' => $u1,
			'search_terms' => 'foo'
		]);

		$this->assertEquals([$message->thread_id], wp_list_pluck($threads['threads'], 'thread_id'));

		$this->set_current_user( $current_user );
	}

	/**
	 * @group thread
	 */
	public function test_thread_started_day_should_be_the_first_message_or_last_deleted_to_current_user()
	{
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$now = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - 10;

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => $first_date = gmdate( 'Y-m-d H:i:s', $now + 1 ),
		) );

		$message2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 2 ),
		) );

		// user 1 should see the first message as start date
		$this->assertEquals( $first_date, BP_Messages_Thread::get_messages_started( $message->thread_id ) );

		// user 2 should see the first message as start date
		$this->set_current_user( $u2 );
		$this->assertEquals( $first_date, BP_Messages_Thread::get_messages_started( $message->thread_id ) );

		// if user 2 delete the thread
		messages_delete_thread($message->thread_id);

		// a new message is posted
		$message3 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => $after_date = gmdate( 'Y-m-d H:i:s', $now + 3 ),
		) );

		// user 2 should see the last message as start date
		$this->assertEquals( $after_date, BP_Messages_Thread::get_messages_started( $message->thread_id ) );

		// user 1 should see the first message as start date
		$this->set_current_user( $u1 );
		$this->assertEquals( $first_date, BP_Messages_Thread::get_messages_started( $message->thread_id ) );

		$this->set_current_user( $current_user );
	}

	/**
	 * @group thread
	 */
	public function test_thread_should_not_show_messages_if_user_has_deleted()
	{
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$now = time() + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - 10;

		$message = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => $first_date = gmdate( 'Y-m-d H:i:s', $now + 1 ),
		) );

		$message2 = self::factory()->message->create_and_get( array(
			'sender_id' => $u1,
			'recipients' => array( $u2 ),
			'date_sent' => gmdate( 'Y-m-d H:i:s', $now + 2 ),
		) );

		messages_delete_thread($message->thread_id);

		BP_Messages_Thread::$noCache = true;
		$thread = new BP_Messages_Thread($message->thread_id);

		$this->assertEmpty( $thread->messages );
		$this->assertEquals( 0, $thread->total_messages );

		$this->set_current_user( $current_user );
	}
}
