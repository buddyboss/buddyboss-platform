<?php

/**
 * @group messages
 * @group cache
 */
class BP_Tests_Message_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_messages_update_meta_cache
	 */
	public function test_bp_messages_update_meta_cache() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create the thread
		$message_1 = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'This is a knive',
		) );

		// create a reply
		$message_2 = self::factory()->message->create_and_get( array(
			'thread_id'  => $message_1->thread_id,
			'sender_id'  => $u2,
			'recipients' => array( $u1 ),
			'content'    => "That's a spoon",
		) );

		$m1 = $message_1->id;
		$m2 = $message_2->id;

		// add cache for each message
		bp_messages_update_meta( $m1, 'utensil',  'knive' );
		bp_messages_update_meta( $m1, 'is_knive', 'yes' );

		bp_messages_update_meta( $m2, 'utensil',  'spoon' );
		bp_messages_update_meta( $m2, 'is_knive', 'no' );
		bp_messages_update_meta( $m2, 'is_spoon', 'yes' );

		// prime cache
		bp_messages_get_meta( $m1, 'utensil' );

		// Ensure an empty cache for second message
		wp_cache_delete( $m2, 'message_meta' );

		// update message meta cache
		bp_messages_update_meta_cache( array( $m1, $m2 ) );

		$expected = array(
			$m1 => array(
				'utensil' => array(
					'knive',
				),
				'is_knive' => array(
					'yes',
				),
			),
			$m2 => array(
				'utensil' => array(
					'spoon',
				),
				'is_knive' => array(
					'no',
				),
				'is_spoon' => array(
					'yes',
				),
			),
		);

		$found = array(
			$m1 => wp_cache_get( $m1, 'message_meta' ),
			$m2 => wp_cache_get( $m2, 'message_meta' ),
		);

		$this->assertEquals( $expected, $found );
	}

	/**
	 * @group bp_messages_update_meta_cache
	 * @group bp_thread_has_messages
	 */
	public function test_bp_messages_metadata_cache_in_message_loop() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// create the message and thread
		$m = self::factory()->message->create_and_get( array(
			'sender_id'  => $u1,
			'recipients' => array( $u2 ),
			'subject'    => 'Oy',
		) );

		// add message cache
		bp_messages_update_meta( $m->id, 'yolo', 'gah' );

		// prime meta cache in message loop
		bp_thread_has_messages( array(
			'thread_id' => $m->thread_id,
			'update_meta_cache' => true
		) );

		$this->assertNotEmpty( wp_cache_get( $m->id, 'message_meta' ) );
	}

	/**
	 * @group bp_messages_delete_meta
	 * @group messages_delete_thread
	 *
	 * @deprecated Buddyboss 3.1.1 Messages no loadger perminently deleted
	 */
	// public function test_bp_messages_delete_metadata_cache_on_thread_delete() {
	// 	$this->old_current_user = get_current_user_id();

	// 	$u1 = self::factory()->user->create();
	// 	$u2 = self::factory()->user->create();

	// 	// create the thread
	// 	$message_1 = self::factory()->message->create_and_get( array(
	// 		'sender_id'  => $u1,
	// 		'recipients' => array( $u2 ),
	// 		'subject'    => 'Oy',
	// 	) );

	// 	// create a reply
	// 	$message_2 = self::factory()->message->create_and_get( array(
	// 		'thread_id'  => $message_1->thread_id,
	// 		'sender_id'  => $u2,
	// 		'recipients' => array( $u1 ),
	// 		'content'    => 'Yo',
	// 	) );

	// 	$m1 = $message_1->id;
	// 	$m2 = $message_2->id;
	// 	$t1 = $message_1->thread_id;

	// 	// add message meta
	// 	bp_messages_update_meta( $m1, 'yolo', 'gah' );
	// 	bp_messages_update_meta( $m2, 'yolo', 'bah' );

	// 	// prime message meta cache
	// 	bp_messages_get_meta( $m1, 'yolo' );
	// 	bp_messages_get_meta( $m2, 'yolo' );

	// 	// delete thread
	// 	// to outright delete a thread, both recipients must delete it
	// 	$this->set_current_user( $u1 );
	// 	messages_delete_thread( $t1 );
	// 	$this->set_current_user( $u2 );
	// 	messages_delete_thread( $t1 );

	// 	// assert empty meta cache
	// 	$this->assertEmpty( wp_cache_get( $m1, 'message_meta' ) );
	// 	$this->assertEmpty( wp_cache_get( $m2, 'message_meta' ) );

	// 	// cleanup
	// 	$this->set_current_user( $this->old_current_user );
	// }

	/**
	 * Helper method to grab the message IDs from a message thread.
	 *
	 * @param int $thread_id The message thread ID
	 * @return array
	 */
	protected function get_message_ids( $thread_id = 0 ) {
		$thread = new BP_Messages_Thread( $thread_id );
		return wp_list_pluck( $thread->messages, 'id' );
	}

	/**
	 * Deleting a member blanks their messages with direct queries; the thread
	 * list of the other participant must not keep serving the cached text.
	 *
	 * @group bb_messages_clear_cache_on_delete_user_message
	 */
	public function test_thread_list_is_refreshed_after_sender_messages_are_deleted() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Subject',
				'content'    => 'Original message text',
			)
		);

		// Prime the thread list and message caches.
		$threads = BP_Messages_Thread::get_current_threads_for_user( array( 'user_id' => $u2 ) );
		$this->assertSame( 'Original message text', $threads['threads'][0]->last_message_content );

		BP_Messages_Message::delete_user_message( $u1 );

		$threads = BP_Messages_Thread::get_current_threads_for_user( array( 'user_id' => $u2 ) );
		$content = ! empty( $threads['threads'] ) ? $threads['threads'][0]->last_message_content : '';
		$this->assertNotSame( 'Original message text', $content );
	}

	/**
	 * Deleting a member resets every recipient's unread count in the member's
	 * threads with a direct query, so the other recipients must not keep their
	 * cached unread counts.
	 *
	 * @group bb_messages_clear_cache_on_delete_user_message
	 */
	public function test_other_recipients_unread_counts_are_cleared_after_sender_messages_are_deleted() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2, $u3 ),
				'subject'    => 'Subject',
				'content'    => 'Original message text',
			)
		);

		// Prime the other recipients' unread count caches.
		foreach ( array( $u2, $u3 ) as $recipient ) {
			$this->assertSame( 1, (int) messages_get_unread_count( $recipient ) );
			$this->assertSame( 1, (int) bb_get_thread_messages_unread_count( $message->thread_id, $recipient ) );
		}

		BP_Messages_Message::delete_user_message( $u1 );

		foreach ( array( $u2, $u3 ) as $recipient ) {
			$this->assertSame( 0, (int) messages_get_unread_count( $recipient ) );
			$this->assertSame( 0, (int) bb_get_thread_messages_unread_count( $message->thread_id, $recipient ) );
		}
	}

	/**
	 * `delete_user_message()` only walks the first page of the member's own thread
	 * list, so the caches of older threads must be cleared by the listener.
	 *
	 * @group bb_messages_clear_cache_on_delete_user_message
	 */
	public function test_threads_beyond_the_first_page_are_refreshed_after_sender_messages_are_deleted() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// A distinct extra recipient per message, so each message starts a new thread
		// instead of joining the existing conversation with the same recipients.
		$thread_ids = array();
		for ( $i = 0; $i < 12; $i++ ) {
			$message      = self::factory()->message->create_and_get(
				array(
					'sender_id'  => $u1,
					'recipients' => array( $u2, self::factory()->user->create() ),
					'subject'    => 'Subject ' . $i,
					'content'    => 'Original message text ' . $i,
					'date_sent'  => gmdate( 'Y-m-d H:i:s', strtotime( '-' . ( 60 - $i ) . ' minutes' ) ),
				)
			);
			$thread_ids[] = $message->thread_id;
		}
		$this->assertCount( 12, array_unique( $thread_ids ) );

		$oldest_thread = $thread_ids[0];

		$this->set_current_user( $u2 );

		// Prime the oldest thread's message cache and the recipient's unread counts.
		$messages = BP_Messages_Thread::get_messages( $oldest_thread );
		$this->assertSame( 'Original message text 0', $messages[0]->message );
		$this->assertSame( 12, (int) messages_get_unread_count( $u2 ) );
		$this->assertSame( 1, (int) bb_get_thread_messages_unread_count( $oldest_thread, $u2 ) );

		BP_Messages_Message::delete_user_message( $u1 );

		$messages = BP_Messages_Thread::get_messages( $oldest_thread );
		$content  = ! empty( $messages ) ? $messages[0]->message : '';
		$this->assertNotSame( 'Original message text 0', $content );
		$this->assertSame( 0, (int) messages_get_unread_count( $u2 ) );
		$this->assertSame( 0, (int) bb_get_thread_messages_unread_count( $oldest_thread, $u2 ) );
	}

	/**
	 * A thread the member had already deleted for themselves is skipped by
	 * `delete_user_message()`'s own thread loop, but the direct query still resets
	 * the other recipients' unread counts in it, so their caches must be cleared by
	 * the listener.
	 *
	 * @group bb_messages_clear_cache_on_delete_user_message
	 */
	public function test_threads_already_deleted_by_sender_are_refreshed_after_sender_messages_are_deleted() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Subject',
				'content'    => 'First message',
			)
		);

		$thread_id = $message->thread_id;

		// The recipient replies, so the thread survives the sender deleting it.
		self::factory()->message->create_and_get(
			array(
				'thread_id'  => $thread_id,
				'sender_id'  => $u2,
				'recipients' => array( $u1 ),
				'content'    => 'Reply',
			)
		);

		// The sender writes again, leaving the recipient with an unread message.
		self::factory()->message->create_and_get(
			array(
				'thread_id'  => $thread_id,
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'content'    => 'Second message',
			)
		);

		// The sender deletes the thread for themselves.
		messages_delete_thread( $thread_id, $u1 );

		// The sender's last-deleted marker is per request; the recipient reads in a later request.
		BP_Messages_Thread::$last_deleted_message = null;

		// Prime the recipient's unread count caches.
		$this->assertGreaterThan( 0, (int) messages_get_unread_count( $u2 ) );
		$this->assertGreaterThan( 0, (int) bb_get_thread_messages_unread_count( $thread_id, $u2 ) );

		BP_Messages_Message::delete_user_message( $u1 );

		$this->assertSame( 0, (int) messages_get_unread_count( $u2 ) );
		$this->assertSame( 0, (int) bb_get_thread_messages_unread_count( $thread_id, $u2 ) );
	}
}
