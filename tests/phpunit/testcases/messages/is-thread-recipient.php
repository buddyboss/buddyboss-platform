<?php

/**
 * BP_Messages_Thread::is_thread_recipient() reads one recipient row instead of the full list (PROD-9747).
 *
 * @group messages
 * @group is_thread_recipient
 */
class BP_Tests_Messages_Is_Thread_Recipient extends BP_UnitTestCase {

	/**
	 * Create a thread from $sender to $recipients and return its ID.
	 *
	 * @param int   $sender     Sender ID.
	 * @param array $recipients Recipient IDs.
	 *
	 * @return int
	 */
	protected function create_thread( $sender, $recipients ) {
		$message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $sender,
				'recipients' => $recipients,
				'subject'    => 'PROD-9747 is_thread_recipient',
			)
		);

		return (int) $message->thread_id;
	}

	/**
	 * Mark a member's recipient row deleted and drop the thread caches.
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $user_id   User ID.
	 */
	protected function mark_deleted( $thread_id, $user_id ) {
		global $wpdb;

		$wpdb->update( buddypress()->messages->table_name_recipients, array( 'is_deleted' => 1 ), array( 'thread_id' => $thread_id, 'user_id' => $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );
		bp_core_reset_incrementor( 'bp_messages' );
	}

	/**
	 * Same answers as the full-list check: members, non-members, deleted rows, no thread, logged-out.
	 */
	public function test_answers_match_release_rule() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$outsider = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2, $u3 ) );

		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id, $u1 ) );
		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id, $u2 ) );
		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id, $u3 ) );
		$this->assertFalse( BP_Messages_Thread::is_thread_recipient( $thread_id, $outsider ) );
		$this->assertFalse( BP_Messages_Thread::is_thread_recipient( 0, $u1 ) );
		$this->assertFalse( BP_Messages_Thread::is_thread_recipient( $thread_id + 1000, $u1 ) );

		$this->mark_deleted( $thread_id, $u3 );
		$this->assertFalse( BP_Messages_Thread::is_thread_recipient( $thread_id, $u3 ), 'A deleted recipient row is not active.' );
		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id, $u2 ) );

		// The logged-in user is the default; no user at all keeps release's "true".
		self::set_current_user( $u2 );
		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id ) );
		self::set_current_user( $outsider );
		$this->assertFalse( BP_Messages_Thread::is_thread_recipient( $thread_id ) );
		self::set_current_user( 0 );
		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id ) );
	}

	/**
	 * The full recipient list is not loaded (revert-sensitive: release fills the thread_recipients cache).
	 */
	public function test_does_not_load_full_recipient_list() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2 ) );

		wp_cache_delete( 'thread_recipients_' . $thread_id, 'bp_messages' );

		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id, $u2 ) );
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $thread_id, 'bp_messages' ), 'The full recipient list was not read.' );
	}

	/**
	 * While the full list is filtered, the filtered list decides (release path kept).
	 */
	public function test_filtered_recipient_list_is_respected() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2 ) );

		$drop_u2 = function ( $recipients ) use ( $u2 ) {
			unset( $recipients[ $u2 ] );
			return $recipients;
		};
		add_filter( 'bp_messages_thread_get_recipients', $drop_u2 );

		$this->assertFalse( BP_Messages_Thread::is_thread_recipient( $thread_id, $u2 ), 'The filter removed the member.' );
		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id, $u1 ) );

		remove_filter( 'bp_messages_thread_get_recipients', $drop_u2 );

		$this->assertTrue( BP_Messages_Thread::is_thread_recipient( $thread_id, $u2 ) );
	}
}
