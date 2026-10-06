<?php

/**
 * Thread avatars without loading every recipient of a large thread (PROD-9747).
 *
 * @group messages
 * @group bp_messages_get_avatars
 */
class BP_Tests_Messages_Avatars extends BP_UnitTestCase {

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
				'subject'    => 'PROD-9747 avatars',
				'date_sent'  => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ),
			)
		);

		return (int) $message->thread_id;
	}

	/**
	 * Reply in a thread.
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $sender    Sender ID.
	 */
	protected function reply( $thread_id, $sender ) {
		static $offset = 0;
		++$offset;

		// Messages dated in the future are not read, so stay between the thread start and now.
		$message_id = messages_new_message(
			array(
				'thread_id' => $thread_id,
				'sender_id' => $sender,
				'content'   => 'Reply from ' . $sender,
				'date_sent' => gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS + ( $offset * 60 ) ),
			)
		);

		$this->assertNotEmpty( $message_id );
		$this->assertNotWPError( $message_id );
	}

	/**
	 * Avatar user IDs, in the order they are returned.
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $user_id   Viewing user ID.
	 *
	 * @return array
	 */
	protected function avatar_ids( $thread_id, $user_id ) {
		wp_cache_flush();
		$this->set_current_user( $user_id );

		return array_map( 'intval', wp_list_pluck( bp_messages_get_avatars( $thread_id, $user_id ), 'id' ) );
	}

	/**
	 * Recipient user IDs of the whole thread, in the order the full list returns them, without $exclude.
	 *
	 * @param int   $thread_id Thread ID.
	 * @param array $exclude   User IDs to leave out.
	 *
	 * @return array
	 */
	protected function full_recipient_ids( $thread_id, $exclude ) {
		wp_cache_flush();

		return array_values( array_diff( array_keys( BP_Messages_Thread::get_recipients_for_thread( $thread_id ) ), $exclude ) );
	}

	public function test_one_to_one_thread_shows_the_other_member() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2 ) );

		$this->assertSame( array( $u2 ), $this->avatar_ids( $thread_id, $u1 ) );
		$this->assertSame( array( $u1 ), $this->avatar_ids( $thread_id, $u2 ) );
	}

	public function test_large_thread_without_replies_uses_first_recipients_of_full_list() {
		$sender  = self::factory()->user->create();
		$members = self::factory()->user->create_many( 15 );

		$thread_id = $this->create_thread( $sender, $members );
		$expected  = array_reverse( array_slice( $this->full_recipient_ids( $thread_id, array( $sender ) ), 0, 2 ) );

		$this->assertCount( 2, $expected );
		$this->assertSame( $expected, $this->avatar_ids( $thread_id, $sender ) );
	}

	public function test_large_thread_with_one_reply_uses_sender_and_first_other_recipient() {
		$sender  = self::factory()->user->create();
		$members = self::factory()->user->create_many( 15 );

		$thread_id = $this->create_thread( $sender, $members );
		$this->reply( $thread_id, $members[7] );

		$others   = $this->full_recipient_ids( $thread_id, array( $sender, $members[7] ) );
		$expected = array_reverse( array( $members[7], $others[0] ) );

		$this->assertSame( $expected, $this->avatar_ids( $thread_id, $sender ) );
	}

	public function test_large_thread_with_two_replies_uses_both_senders() {
		$sender  = self::factory()->user->create();
		$members = self::factory()->user->create_many( 15 );

		$thread_id = $this->create_thread( $sender, $members );
		$this->reply( $thread_id, $members[3] );
		$this->reply( $thread_id, $members[9] );

		// Messages are read newest first; the list is then reversed.
		$this->assertSame( array( $members[3], $members[9] ), $this->avatar_ids( $thread_id, $sender ) );
	}

	public function test_three_member_thread_matches_full_list() {
		$sender  = self::factory()->user->create();
		$members = self::factory()->user->create_many( 2 );

		$thread_id = $this->create_thread( $sender, $members );
		$expected  = array_reverse( $this->full_recipient_ids( $thread_id, array( $sender ) ) );

		$this->assertSame( $expected, $this->avatar_ids( $thread_id, $sender ) );
	}

	public function test_recipients_filter_is_still_applied() {
		$sender  = self::factory()->user->create();
		$members = self::factory()->user->create_many( 15 );

		$thread_id = $this->create_thread( $sender, $members );
		$keep      = $members[5];

		$filter = function ( $recipients ) use ( $sender, $keep ) {
			return array_intersect_key( $recipients, array_flip( array( $sender, $keep ) ) );
		};
		add_filter( 'bp_messages_thread_get_recipients', $filter );

		// With only two recipients left the thread reads as one-to-one.
		$avatars = $this->avatar_ids( $thread_id, $sender );

		remove_filter( 'bp_messages_thread_get_recipients', $filter );

		$this->assertSame( array( $keep ), $avatars );
	}
}
