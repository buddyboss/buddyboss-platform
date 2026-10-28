<?php
/**
 * Group thread history across Group Messages ON -> OFF -> ON (PROD-9747 / PROD-3077, review round 8).
 *
 * @package BuddyBoss
 */

/**
 * Group threads are written through the real send path (bp_groups_messages_new_message() with the
 * request fields bp_media_messages_save_group_data() reads), and the setting is changed with
 * bp_update_option() without flushing the object cache, so only the product's own reset hooks keep
 * the caches right.
 *
 * @group messages
 * @group groups
 * @group prod-9747
 */
class BP_Tests_Messages_Group_Messages_Toggle_History extends BP_UnitTestCase {

	/**
	 * Original value of the "Group Messages" setting.
	 *
	 * @var mixed
	 */
	protected $group_messages_option;

	/**
	 * Seconds added to the base send time, so every message gets a later date_sent.
	 *
	 * @var int
	 */
	protected $clock = 0;

	/**
	 * Save the setting.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->group_messages_option = bp_get_option( 'bp-disable-group-messages' );
		$this->clock                 = 0;
	}

	/**
	 * Restore the setting and the request.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_POST = array();
		wp_cache_flush();
		bp_update_option( 'bp-disable-group-messages', $this->group_messages_option );
		wp_cache_flush();
		parent::tear_down();
	}

	/* Helpers ---------------------------------------------------------------- */

	/**
	 * Change "Group Messages" (true = enabled) the way the settings screen does: bp_update_option() only.
	 *
	 * Release's bb_clear_cache_while_group_messsage_settings_updated() unsets the unread-count group straight
	 * from WP_Object_Cache::$cache, which is a notice under the test cache; drop only that group first, so the
	 * cache of the disabled group threads is left to the product hooks.
	 *
	 * @param bool $enabled Whether Group Messages is enabled.
	 *
	 * @return void
	 */
	protected function set_group_messages( $enabled ) {
		wp_cache_flush_group( 'bp_messages_unread_count' );
		bp_update_option( 'bp-disable-group-messages', $enabled ? 1 : 0 );
	}

	/**
	 * Next send time, one minute after the previous one.
	 *
	 * @return string
	 */
	protected function next_date() {
		$this->clock += 60;

		return gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS + $this->clock );
	}

	/**
	 * Send an open group message to all members, as the group "Send Message" screen does (Group Messages ON).
	 *
	 * @param int   $sender   Sender ID.
	 * @param int[] $members  Member IDs.
	 * @param int   $group_id Group ID.
	 *
	 * @return int Thread ID.
	 */
	protected function send_group_broadcast( $sender, $members, $group_id ) {
		$this->set_current_user( $sender );
		$_POST = array(
			'group'               => (string) $group_id,
			'users'               => 'all',
			'type'                => 'open',
			'message_thread_type' => 'new',
			'action'              => 'groups_get_group_potential_user_send_messages',
		);

		$thread_id = bp_groups_messages_new_message(
			array(
				'sender_id'     => $sender,
				'recipients'    => $members,
				'subject'       => 'Group broadcast',
				'content'       => 'Group broadcast',
				'date_sent'     => $this->next_date(),
				'append_thread' => false,
			)
		);
		$_POST     = array();

		$this->assertIsInt( $thread_id );
		groups_update_groupmeta( $group_id, 'group_message_thread', $thread_id );

		return (int) $thread_id;
	}

	/**
	 * Reply in a thread as a member.
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $sender    Sender ID.
	 *
	 * @return int Message ID.
	 */
	protected function reply( $thread_id, $sender ) {
		$this->set_current_user( $sender );
		$message_id = messages_new_message(
			array(
				'sender_id' => $sender,
				'thread_id' => $thread_id,
				'content'   => 'Reply from ' . $sender,
				'date_sent' => $this->next_date(),
				'return'    => 'id',
			)
		);

		$this->assertIsInt( $message_id );

		return (int) $message_id;
	}

	/**
	 * Messages of a thread straight from the table, oldest first.
	 *
	 * @param int $thread_id Thread ID.
	 *
	 * @return array Rows (id, sender_id, message, date_sent).
	 */
	protected function stored_messages( $thread_id ) {
		global $wpdb;
		$table = buddypress()->messages->table_name_messages;

		return $wpdb->get_results( $wpdb->prepare( "SELECT id, sender_id, message, date_sent FROM {$table} WHERE thread_id = %d ORDER BY date_sent ASC, id ASC", $thread_id ), ARRAY_A ); // phpcs:ignore
	}

	/**
	 * Thread IDs a member's message list shows (the inbox, header and sidebar opt in to the exclusion).
	 *
	 * @param int $user_id User ID.
	 *
	 * @return int[]
	 */
	protected function listed( $user_id ) {
		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id'                        => $user_id,
				'fields'                         => 'ids',
				'exclude_disabled_group_threads' => true,
			)
		);

		return empty( $threads['threads'] ) ? array() : array_map( 'intval', $threads['threads'] );
	}

	/**
	 * Group, organizer and two members.
	 *
	 * @return array
	 */
	protected function group_fixture() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $group_id, $u2 );
		groups_join_group( $group_id, $u3 );

		return compact( 'u1', 'u2', 'u3', 'group_id' );
	}

	/* Tests ------------------------------------------------------------------ */

	/**
	 * Requirement (user rule "whatever works in release we keep", ticket scope PROD-3077): turning Group Messages
	 * OFF and back ON never changes a group thread's stored history. Message IDs grow with send order, the
	 * thread's last message is the newest message (highest ID), the thread shows its messages newest first,
	 * and a reply sent after re-enabling gets a higher ID and becomes the last message.
	 *
	 * Uses only release APIs, so it also runs against the release code as a regression guard.
	 */
	public function test_group_thread_history_survives_off_on_toggle() {
		$f = $this->group_fixture();
		$this->set_group_messages( true );

		$thread_id = $this->send_group_broadcast( $f['u1'], array( $f['u2'], $f['u3'] ), $f['group_id'] );
		$this->reply( $thread_id, $f['u2'] );
		$this->reply( $thread_id, $f['u3'] );

		$before     = $this->stored_messages( $thread_id );
		$before_ids = array_map( 'intval', wp_list_pluck( $before, 'id' ) );
		$this->assertCount( 3, $before_ids );
		$sorted = $before_ids;
		sort( $sorted );
		$this->assertSame( $sorted, $before_ids, 'IDs grow with send order.' );
		$this->assertSame( max( $before_ids ), (int) BP_Messages_Thread::get_last_message( $thread_id )->id, 'Last message = highest ID.' );

		// OFF: the thread cannot be opened, nothing stored changes.
		$this->set_group_messages( false );
		$this->assertSame( 0, bb_messages_validate_groups_thread( $thread_id ) );
		$this->assertSame( $before, $this->stored_messages( $thread_id ), 'OFF changes no stored message.' );

		// ON again: same history, same last message, and the thread opens.
		$this->set_group_messages( true );
		$this->assertSame( $thread_id, bb_messages_validate_groups_thread( $thread_id ) );
		$this->assertSame( $before, $this->stored_messages( $thread_id ), 'History unchanged after ON -> OFF -> ON.' );

		$this->set_current_user( $f['u2'] );
		$thread = new BP_Messages_Thread( $thread_id, 'ASC' );
		// BP_Messages_Thread::get_messages() returns newest first whatever $order (release behaviour, the views reverse it).
		$this->assertSame( array_reverse( $before_ids ), array_map( 'intval', wp_list_pluck( $thread->messages, 'id' ) ), 'The thread shows the same messages, newest first.' );
		$this->assertSame( max( $before_ids ), (int) $thread->last_message_id );

		// A reply after re-enabling.
		$new_id = $this->reply( $thread_id, $f['u3'] );
		$this->assertGreaterThan( max( $before_ids ), $new_id, 'New message ID is higher than every earlier one.' );
		$this->assertSame( $new_id, (int) BP_Messages_Thread::get_last_message( $thread_id )->id );

		$this->set_current_user( $f['u2'] );
		$thread = new BP_Messages_Thread( $thread_id, 'ASC' );
		$this->assertSame( array_reverse( array_merge( $before_ids, array( $new_id ) ) ), array_map( 'intval', wp_list_pluck( $thread->messages, 'id' ) ) );
		$this->assertSame( $new_id, (int) $thread->last_message_id, 'The new reply is the last message.' );
	}

	/**
	 * Requirement (PROD-3077 list rule + "disabled-id cache not stale after re-enable"): with the setting changed
	 * only through bp_update_option(), the member's list hides the group thread while OFF and shows it again
	 * after ON with the reply sent after re-enabling as its last message; a group thread created while ON is
	 * hidden on the next OFF, and the persistent copy of the list is not left behind while ON.
	 */
	public function test_list_and_disabled_ids_follow_toggle_without_cache_flush() {
		$f = $this->group_fixture();
		$this->set_group_messages( true );

		$thread_id = $this->send_group_broadcast( $f['u1'], array( $f['u2'], $f['u3'] ), $f['group_id'] );
		$this->reply( $thread_id, $f['u2'] );

		$this->assertContains( $thread_id, $this->listed( $f['u2'] ), 'ON: listed.' );

		// OFF: hidden; the list is cached (object cache and, without a persistent cache, the option).
		$this->set_group_messages( false );
		$this->assertSame( array( $thread_id ), bb_messages_get_disabled_group_thread_ids() );
		$this->assertNotContains( $thread_id, $this->listed( $f['u2'] ), 'OFF: hidden from the list.' );
		$this->assertSame( array( $thread_id ), bb_messages_get_cached_disabled_group_thread_ids() );

		// ON: the cached OFF list is dropped and nothing is hidden.
		$this->set_group_messages( true );
		$this->assertFalse( bb_messages_get_cached_disabled_group_thread_ids(), 'Cache reset when the setting changes.' );
		$this->assertFalse( bp_get_option( '_bb_messages_disabled_group_thread_ids', false ), 'No persistent copy left while ON.' );
		$this->assertSame( array(), bb_messages_get_disabled_group_thread_ids() );
		$this->assertFalse( bb_messages_is_disabled_group_thread( $thread_id ) );

		$new_id = $this->reply( $thread_id, $f['u3'] );

		$this->set_current_user( $f['u2'] );
		$this->assertContains( $thread_id, $this->listed( $f['u2'] ), 'ON again: listed.' );
		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id'                        => $f['u2'],
				'exclude_disabled_group_threads' => true,
			)
		);
		$listed  = array();
		foreach ( (array) $threads['threads'] as $thread ) {
			$listed[ (int) $thread->thread_id ] = (int) $thread->last_message_id;
		}
		$this->assertArrayHasKey( $thread_id, $listed );
		$this->assertSame( $new_id, $listed[ $thread_id ], 'The list shows the reply sent after re-enabling as last message.' );

		// A second group thread created while ON is hidden on the next OFF together with the first one.
		$second = $this->send_group_broadcast( $f['u1'], array( $f['u2'], $f['u3'] ), $f['group_id'] );

		$this->set_group_messages( false );
		$ids = bb_messages_get_disabled_group_thread_ids();
		sort( $ids );
		$this->assertSame( array( $thread_id, $second ), $ids, 'OFF again: both group threads, nothing stale.' );
		$this->assertSame( array(), array_values( array_intersect( array( $thread_id, $second ), $this->listed( $f['u2'] ) ) ) );
	}

	/**
	 * Requirement (Groups component inactive path, doc 52:88): the disabled list does not depend on the Groups
	 * component. With Groups inactive and Group Messages OFF, the list hides exactly the threads the release
	 * open check refuses (bb_messages_validate_groups_thread() has no Groups check either), private threads stay
	 * listed and open; with Group Messages ON nothing is hidden.
	 */
	public function test_groups_component_inactive_list_matches_open_check() {
		$f = $this->group_fixture();
		$this->set_group_messages( true );

		$group_thread = $this->send_group_broadcast( $f['u1'], array( $f['u2'], $f['u3'] ), $f['group_id'] );
		$this->reply( $group_thread, $f['u2'] );
		$this->set_current_user( $f['u1'] );
		$private_thread = (int) messages_new_message(
			array(
				'sender_id'  => $f['u1'],
				'recipients' => array( $f['u2'] ),
				'subject'    => 'Private',
				'content'    => 'Private',
				'date_sent'  => $this->next_date(),
			)
		);
		$this->assertGreaterThan( 0, $private_thread );

		$active_components = buddypress()->active_components;
		unset( buddypress()->active_components['groups'] );

		try {
			$this->assertFalse( bp_is_active( 'groups' ) );

			$this->set_group_messages( false );
			$this->assertSame( array( $group_thread ), bb_messages_get_disabled_group_thread_ids() );
			$this->assertSame( 0, bb_messages_validate_groups_thread( $group_thread ), 'Release open check refuses it.' );
			$this->assertSame( $private_thread, bb_messages_validate_groups_thread( $private_thread ) );

			$listed = $this->listed( $f['u2'] );
			$this->assertNotContains( $group_thread, $listed, 'List agrees with the open check.' );
			$this->assertContains( $private_thread, $listed );

			$this->set_group_messages( true );
			$this->assertSame( array(), bb_messages_get_disabled_group_thread_ids() );
			$this->assertSame( $group_thread, bb_messages_validate_groups_thread( $group_thread ) );
		} finally {
			buddypress()->active_components = $active_components;
		}
	}

	/**
	 * Requirement (existing-data compatibility): group threads already in the database before the update (rows
	 * written without any of the new hooks running, no cached list, no persistent option) are found on the
	 * first lookup while Group Messages is OFF, and a thread whose first message is a private message keeps
	 * opening and stays listed. Nothing is written to the messages tables by the lookup.
	 */
	public function test_existing_rows_without_hooks_are_found_on_first_lookup() {
		global $wpdb;

		$bp       = buddypress();
		$messages = $bp->messages->table_name_messages;
		$meta     = $bp->messages->table_name_meta;
		$recips   = $bp->messages->table_name_recipients;

		$f         = $this->group_fixture();
		$max_id    = (int) $wpdb->get_var( "SELECT COALESCE( MAX( thread_id ), 0 ) FROM {$messages}" ); // phpcs:ignore
		$group_tid = $max_id + 101;
		$priv_tid  = $max_id + 102;

		$insert = function ( $thread_id, $sender, $recipients, $date, $flags ) use ( $wpdb, $messages, $meta, $recips ) {
			$wpdb->insert( $messages, array( 'thread_id' => $thread_id, 'sender_id' => $sender, 'subject' => 'Legacy', 'message' => 'Legacy', 'date_sent' => $date ) ); // phpcs:ignore
			$message_id = (int) $wpdb->insert_id;
			foreach ( $flags as $key => $value ) {
				$wpdb->insert( $meta, array( 'message_id' => $message_id, 'meta_key' => $key, 'meta_value' => $value ) ); // phpcs:ignore
			}
			foreach ( $recipients as $user_id ) {
				if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$recips} WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) ) ) { // phpcs:ignore
					$wpdb->insert( $recips, array( 'user_id' => $user_id, 'thread_id' => $thread_id, 'unread_count' => 0, 'sender_only' => 0, 'is_deleted' => 0 ) ); // phpcs:ignore
				}
			}

			return $message_id;
		};

		$people = array( $f['u1'], $f['u2'], $f['u3'] );
		$insert(
			$group_tid,
			$f['u1'],
			$people,
			$this->next_date(),
			array(
				'group_id'                  => $f['group_id'],
				'group_message_users'       => 'all',
				'group_message_type'        => 'open',
				'group_message_thread_type' => 'new',
				'message_from'              => 'group',
				'group_message_thread_id'   => $group_tid,
			)
		);
		$insert(
			$group_tid,
			$f['u2'],
			$people,
			$this->next_date(),
			array(
				'group_id'            => $f['group_id'],
				'group_message_users' => 'all',
				'group_message_type'  => 'open',
				'message_from'        => 'group',
			)
		);
		// A private thread whose later message carries open group meta: the first message decides.
		$insert( $priv_tid, $f['u1'], array( $f['u1'], $f['u2'] ), $this->next_date(), array( 'bp_messages_starred' => '' ) );
		$insert(
			$priv_tid,
			$f['u2'],
			array( $f['u1'], $f['u2'] ),
			$this->next_date(),
			array(
				'group_message_users'     => 'all',
				'group_message_type'      => 'open',
				'message_from'            => 'group',
				'group_message_thread_id' => $priv_tid,
			)
		);

		// State of a site that has just been updated: Group Messages OFF, nothing cached or stored.
		$wpdb->update( $wpdb->options, array( 'option_value' => '0' ), array( 'option_name' => 'bp-disable-group-messages' ) ); // phpcs:ignore
		wp_cache_flush();
		bp_delete_option( '_bb_messages_disabled_group_thread_ids' );
		$this->assertFalse( bp_disable_group_messages() );
		$this->assertFalse( bb_messages_get_cached_disabled_group_thread_ids() );

		$rows_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$messages}" ) . ':' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meta}" ); // phpcs:ignore

		$ids = bb_messages_get_disabled_group_thread_ids();
		$this->assertContains( $group_tid, $ids, 'Existing group thread is found.' );
		$this->assertNotContains( $priv_tid, $ids, 'Existing private thread is not hidden.' );
		$this->assertSame( 0, bb_messages_validate_groups_thread( $group_tid ), 'Same answer as the release open check.' );
		$this->assertSame( $priv_tid, bb_messages_validate_groups_thread( $priv_tid ) );

		$listed = $this->listed( $f['u1'] );
		$this->assertNotContains( $group_tid, $listed );
		$this->assertContains( $priv_tid, $listed, 'Existing private thread stays listed.' );

		$rows_after = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$messages}" ) . ':' . (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meta}" ); // phpcs:ignore
		$this->assertSame( $rows_before, $rows_after, 'The lookup writes nothing to the messages tables.' );
	}
}
