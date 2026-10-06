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
	 * @param int       $sender   Sender ID.
	 * @param int|array $members  Member ID(s); the first one replies.
	 * @param int       $group_id Group ID.
	 *
	 * @return int
	 */
	protected function create_group_thread_with_reply( $sender, $members, $group_id ) {
		$members       = (array) $members;
		$group_message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $sender,
				'recipients' => $members,
				'subject'    => 'Group broadcast',
			)
		);
		$group_thread  = (int) $group_message->thread_id;
		$this->flag_group_message( $group_message->id, $group_thread, $group_id );

		// A member's reply carries no group meta of its own.
		self::factory()->message->create(
			array(
				'sender_id'  => $members[0],
				'thread_id'  => $group_thread,
				'recipients' => array_merge( array( $sender ), array_slice( $members, 1 ) ),
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
	public function test_group_thread_with_replies_stays_listed_when_group_messages_disabled() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		// Three members, so the private message below cannot land in the group thread.
		$group_thread = $this->create_group_thread_with_reply( $u1, array( $u2, $u3 ), $group_id );

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

		// As in release: only the group message itself is left out, so the replied thread stays listed; the web open gate blocks it.
		$this->assertContains( $group_thread, $listed, 'Group thread with replies stays listed, as in release.' );
		$this->assertContains( $private_thread, $listed, 'Other threads stay listed.' );
		$this->assertSame( 0, bb_messages_validate_groups_thread( $group_thread ), 'Opening the group thread on the web is blocked.' );
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

		// As in release: only the group message itself is left out, the replied thread stays listed and opens (TC-18).
		$this->assertContains( $group_thread, array_map( 'intval', (array) $threads['threads'] ) );
		$this->assertSame( $group_thread, bb_messages_validate_groups_thread( $group_thread ) );
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

	/**
	 * Header preview: the paged recipients are the first rows of the full list (TC-03, TC-09).
	 */
	public function test_thread_recipients_are_first_page_of_full_list() {
		$sender = self::factory()->user->create();
		$others = self::factory()->user->create_many( 25 );

		$thread_id = $this->create_thread( $sender, $others );
		$this->set_current_user( $sender );
		wp_cache_flush();

		$thread   = new BP_Messages_Thread( $thread_id );
		$per_page = bb_messages_recipients_per_page();
		$full     = array_keys( $thread->get_recipients() );

		$this->assertCount( $per_page, $thread->recipients );
		$this->assertSame( array_slice( $full, 0, $per_page ), array_keys( $thread->recipients ) );
	}

	/**
	 * Compose and send twice to the same member: one new thread, then the same thread (TC-11, TC-12).
	 */
	public function test_second_message_to_same_recipient_reuses_thread() {
		$sender    = self::factory()->user->create();
		$recipient = self::factory()->user->create();
		$others    = self::factory()->user->create_many( 25 );

		// The sender is also in a large thread with the recipient.
		$this->create_thread( $sender, array_merge( array( $recipient ), $others ) );
		$this->set_current_user( $sender );

		$first = messages_new_message(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'PROD-9747 test',
				'content'    => 'First message',
			)
		);
		$second = messages_new_message(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'PROD-9747 test',
				'content'    => 'Second message',
			)
		);

		$this->assertIsInt( $first );
		$this->assertSame( $first, $second );
		$this->assertSame( 2, (int) BP_Messages_Thread::get_messages_count( $first ) );
	}

	/**
	 * The recipient sees the new thread as unread (TC-13).
	 */
	public function test_new_thread_is_listed_unread_for_recipient() {
		$sender    = self::factory()->user->create();
		$recipient = self::factory()->user->create();

		$thread_id = $this->create_thread( $sender, array( $recipient ) );
		$this->set_current_user( $recipient );
		wp_cache_flush();

		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id' => $recipient,
				'fields'  => 'ids',
			)
		);

		$this->assertContains( $thread_id, array_map( 'intval', (array) $threads['threads'] ) );
		$this->assertSame( 1, (int) messages_get_unread_count( $recipient ) );
	}

	/**
	 * The unread count leaves out group threads while group messages are disabled (TC-14).
	 *
	 * @group groups
	 */
	public function test_unread_count_ignores_group_thread_when_group_messages_disabled() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		bp_update_option( 'bp-disable-group-messages', 0 );
		$this->create_group_thread_with_reply( $u1, array( $u2, self::factory()->user->create() ), $group_id );
		$this->create_thread( $u1, array( $u2 ) );
		wp_cache_flush();

		$this->assertSame( 1, (int) messages_get_unread_count( $u2 ), 'Only the private thread counts as unread.' );

		// Changing the setting in tear_down() unsets the unread-count group straight from the object cache.
		wp_cache_flush();
	}

	/**
	 * The group thread stays listed whatever the Group Messages setting; only opening it follows the setting (TC-15).
	 *
	 * @group groups
	 */
	public function test_group_thread_follows_group_messages_setting() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $group_id, $u2 );

		$group_thread = $this->create_group_thread_with_reply( $u1, $u2, $group_id );
		$this->set_current_user( $u2 );

		$listed = function () use ( $u2 ) {
			$threads = BP_Messages_Thread::get_current_threads_for_user(
				array(
					'user_id' => $u2,
					'fields'  => 'ids',
				)
			);

			return empty( $threads['threads'] ) ? array() : array_map( 'intval', $threads['threads'] );
		};

		bp_update_option( 'bp-disable-group-messages', 0 );
		$this->assertContains( $group_thread, $listed() );
		$this->assertSame( 0, bb_messages_validate_groups_thread( $group_thread ) );

		bp_update_option( 'bp-disable-group-messages', 1 );
		$this->assertContains( $group_thread, $listed() );
		$this->assertSame( $group_thread, bb_messages_validate_groups_thread( $group_thread ) );

		bp_update_option( 'bp-disable-group-messages', 0 );
		$this->assertContains( $group_thread, $listed() );
		$this->assertSame( 0, bb_messages_validate_groups_thread( $group_thread ) );
	}

	/**
	 * A member who is not in the thread has no access to it (TC-16).
	 */
	public function test_non_recipient_has_no_access_to_thread() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$thread_id = $this->create_thread( $u1, array( $u2 ) );

		$this->assertNotEmpty( messages_check_thread_access( $thread_id, $u2 ) );
		$this->assertEmpty( messages_check_thread_access( $thread_id, $u3 ) );
	}

	/**
	 * As in release: a private message to the only other member of a group thread
	 * goes into that group thread, also while Group Messages is disabled.
	 *
	 * @group groups
	 */
	public function test_private_message_to_two_member_group_thread_uses_group_thread() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		bp_update_option( 'bp-disable-group-messages', 0 );
		$group_thread = $this->create_group_thread_with_reply( $u1, $u2, $group_id );

		$this->assertSame( $group_thread, (int) BP_Messages_Message::get_existing_thread( array( $u2 ), $u1 ) );
		$this->assertSame( $group_thread, $this->create_thread( $u1, array( $u2 ) ) );
	}
}
