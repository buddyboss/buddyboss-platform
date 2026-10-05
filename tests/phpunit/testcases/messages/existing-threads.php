<?php

/**
 * Existing-thread lookup and group-thread listing for very large conversations (PROD-9747).
 *
 * @group messages
 * @group BP_Messages_Thread
 */
class BP_Tests_Messages_Existing_Threads extends BP_UnitTestCase {

	/**
	 * Original value of the "Group Messages" setting.
	 *
	 * @var mixed
	 */
	protected $group_messages_option;

	public function set_up() {
		parent::set_up();
		$this->group_messages_option = bp_get_option( 'bp-disable-group-messages' );
	}

	public function tear_down() {
		bp_update_option( 'bp-disable-group-messages', $this->group_messages_option );
		parent::tear_down();
	}

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
				'subject'    => 'PROD-9747',
			)
		);

		return (int) $message->thread_id;
	}

	/**
	 * Flag a message as an open group message sent to all group members.
	 *
	 * @param int $message_id Message ID.
	 * @param int $thread_id  Thread ID.
	 * @param int $group_id   Group ID.
	 */
	protected function flag_group_message( $message_id, $thread_id, $group_id ) {
		bp_messages_update_meta( $message_id, 'group_id', $group_id );
		bp_messages_update_meta( $message_id, 'group_message_thread_id', $thread_id );
		bp_messages_update_meta( $message_id, 'group_message_users', 'all' );
		bp_messages_update_meta( $message_id, 'group_message_type', 'open' );
		bp_messages_update_meta( $message_id, 'message_from', 'group' );
	}

	/**
	 * Create a group thread with a member's reply and return its ID.
	 *
	 * @param int $sender   Sender ID.
	 * @param int $member   Member ID.
	 * @param int $group_id Group ID.
	 *
	 * @return int
	 */
	protected function create_group_thread_with_reply( $sender, $member, $group_id ) {
		$group_message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $member ),
				'subject'    => 'Group broadcast',
			)
		);
		$group_thread  = (int) $group_message->thread_id;
		$this->flag_group_message( $group_message->id, $group_thread, $group_id );

		// A member's reply carries no group meta of its own.
		self::factory()->message->create(
			array(
				'sender_id'  => $member,
				'thread_id'  => $group_thread,
				'recipients' => array( $sender ),
				'content'    => 'Reply',
			)
		);

		return $group_thread;
	}

	public function test_existing_thread_matches_exact_recipients() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2 ) );

		$this->assertSame( $thread_id, (int) BP_Messages_Message::get_existing_thread( array( $u2 ), $u1 ) );

		$threads = BP_Messages_Message::get_existing_threads( array( $u2 ), $u1, true );
		$this->assertSame( array( $thread_id ), array_map( 'intval', wp_list_pluck( $threads, 'thread_id' ) ) );
	}

	public function test_existing_thread_ignores_threads_with_extra_or_missing_recipients() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$this->create_thread( $u1, array( $u2, $u3 ) );

		// Subset of the group thread's recipients.
		$this->assertNull( BP_Messages_Message::get_existing_thread( array( $u2 ), $u1 ) );
		$this->assertNull( BP_Messages_Message::get_existing_threads( array( $u2 ), $u1, true ) );

		// Superset of the group thread's recipients.
		$u4 = self::factory()->user->create();
		$this->assertNull( BP_Messages_Message::get_existing_thread( array( $u2, $u3, $u4 ), $u1 ) );
	}

	public function test_existing_thread_matches_multi_recipient_thread() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2, $u3 ) );

		// Order of the requested recipients must not matter.
		$this->assertSame( $thread_id, (int) BP_Messages_Message::get_existing_thread( array( $u3, $u2 ), $u1 ) );
	}

	public function test_existing_thread_skips_large_thread_the_sender_belongs_to() {
		$sender  = self::factory()->user->create();
		$partner = self::factory()->user->create();
		$others  = self::factory()->user->create_many( 25 );

		// A large thread containing both users, then their one-to-one thread.
		$this->create_thread( $sender, array_merge( array( $partner ), $others ) );
		$one_to_one = $this->create_thread( $sender, array( $partner ) );

		$this->assertSame( $one_to_one, (int) BP_Messages_Message::get_existing_thread( array( $partner ), $sender ) );

		$threads = BP_Messages_Message::get_existing_threads( array( $partner ), $sender, true );
		$this->assertSame( array( $one_to_one ), array_map( 'intval', wp_list_pluck( $threads, 'thread_id' ) ) );
	}

	/**
	 * @group moderation
	 */
	public function test_existing_thread_counts_suspended_recipient() {
		if ( ! bp_is_active( 'moderation' ) ) {
			$this->markTestSkipped( 'Moderation component is not active.' );
		}

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2, $u3 ) );

		BP_Suspend_Member::suspend_user( $u3 );

		// A suspended member is still a recipient: only the full set matches.
		$this->assertNull( BP_Messages_Message::get_existing_thread( array( $u2 ), $u1 ) );
		$this->assertSame( $thread_id, (int) BP_Messages_Message::get_existing_thread( array( $u2, $u3 ), $u1 ) );

		BP_Suspend_Member::unsuspend_user( $u3 );
	}

	/**
	 * @group groups
	 */
	public function test_group_thread_with_replies_is_hidden_when_group_messages_disabled() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		$group_message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Group broadcast',
			)
		);
		$group_thread  = (int) $group_message->thread_id;
		$this->flag_group_message( $group_message->id, $group_thread, $group_id );

		// A member's reply carries no group meta of its own.
		self::factory()->message->create(
			array(
				'sender_id'  => $u2,
				'thread_id'  => $group_thread,
				'recipients' => array( $u1 ),
				'content'    => 'Reply',
			)
		);

		bp_update_option( 'bp-disable-group-messages', 0 );
		$private_thread = $this->create_thread( $u1, array( $u2 ) );

		$this->set_current_user( $u2 );
		wp_cache_flush();

		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id' => $u2,
				'fields'  => 'ids',
			)
		);
		$listed  = array_map( 'intval', (array) $threads['threads'] );

		$this->assertNotContains( $group_thread, $listed, 'Group thread must be hidden while group messages are disabled.' );
		$this->assertContains( $private_thread, $listed, 'Other threads must stay listed.' );
		$this->assertSame( 0, bb_messages_validate_groups_thread( $group_thread ), 'Opening the group thread is blocked.' );
	}

	/**
	 * @group groups
	 */
	public function test_group_thread_is_listed_for_member_when_group_messages_enabled() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $group_id, $u2 );

		$group_message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Group broadcast',
			)
		);
		$group_thread  = (int) $group_message->thread_id;
		$this->flag_group_message( $group_message->id, $group_thread, $group_id );

		bp_update_option( 'bp-disable-group-messages', 1 );
		$this->set_current_user( $u2 );
		wp_cache_flush();

		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id' => $u2,
				'fields'  => 'ids',
			)
		);

		$this->assertContains( $group_thread, array_map( 'intval', (array) $threads['threads'] ) );
	}

	/**
	 * @group groups
	 */
	public function test_group_thread_with_replies_is_listed_when_groups_component_inactive() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		$group_thread = $this->create_group_thread_with_reply( $u1, $u2, $group_id );

		bp_update_option( 'bp-disable-group-messages', 1 );
		$this->set_current_user( $u2 );
		wp_cache_flush();

		$active_components = buddypress()->active_components;
		unset( buddypress()->active_components['groups'] );

		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id' => $u2,
				'fields'  => 'ids',
			)
		);

		buddypress()->active_components = $active_components;

		// As in release: only the group message itself is left out, the replied thread stays listed.
		$this->assertContains( $group_thread, array_map( 'intval', (array) $threads['threads'] ) );
	}

	/**
	 * @group groups
	 */
	public function test_deleted_user_group_thread_is_deleted_when_group_messages_disabled() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		$group_message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2, $u3 ),
				'subject'    => 'Group broadcast',
			)
		);
		$group_thread  = (int) $group_message->thread_id;
		$this->flag_group_message( $group_message->id, $group_thread, $group_id );

		// Another member's reply keeps the thread listed for $u2 once $u2's own messages are deleted.
		self::factory()->message->create(
			array(
				'sender_id'  => $u3,
				'thread_id'  => $group_thread,
				'recipients' => array( $u1, $u2 ),
				'content'    => 'Reply',
			)
		);

		bp_update_option( 'bp-disable-group-messages', 0 );
		wp_cache_flush();

		$deleted  = array();
		$callback = function ( $thread_id, $user_id ) use ( &$deleted ) {
			$deleted[ (int) $thread_id ] = (int) $user_id;
		};
		add_action( 'bp_messages_thread_before_mark_delete', $callback, 10, 2 );

		BP_Messages_Message::delete_user_message( $u2 );

		remove_action( 'bp_messages_thread_before_mark_delete', $callback, 10 );

		$this->assertArrayHasKey( $group_thread, $deleted, 'The group thread must be deleted for the deleted user.' );
		$this->assertSame( $u2, $deleted[ $group_thread ] );
	}
}
