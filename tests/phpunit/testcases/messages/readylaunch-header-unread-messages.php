<?php
/**
 * ReadyLaunch header messages dropdown (readylaunch/header/unread-messages.php).
 *
 * @package BuddyBoss\Tests
 */

/**
 * PROD-9747: the ReadyLaunch header dropdown is rendered from the template file.
 *
 * Covers cbb483e3f4 (one page of recipients, no full list), 124fb5fc68 (F-5 group name read from the
 * first message), the round-5 fix I (other-recipients count when the page is partial) and the PROD-3077
 * rule (group threads removed from the dropdown while Group Messages is disabled).
 *
 * @group messages
 * @group prod-9747
 */
class BP_Tests_Messages_ReadyLaunch_Header_Unread extends BP_UnitTestCase {

	/**
	 * Original value of the "Group Messages" setting.
	 *
	 * @var mixed
	 */
	protected $group_messages_option;

	/**
	 * Save the setting.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->group_messages_option = bp_get_option( 'bp-disable-group-messages' );
	}

	/**
	 * Restore the setting.
	 *
	 * @return void
	 */
	public function tear_down() {
		// Release's setting-change hook unsets a group straight from WP_Object_Cache::$cache (notice under the test cache).
		wp_cache_flush();
		bp_update_option( 'bp-disable-group-messages', $this->group_messages_option );
		wp_cache_flush();
		parent::tear_down();
	}

	/**
	 * Toggle "Group Messages" (true = enabled).
	 *
	 * @param bool $enabled Whether Group Messages is enabled.
	 *
	 * @return void
	 */
	protected function set_group_messages( $enabled ) {
		wp_cache_flush();
		bp_update_option( 'bp-disable-group-messages', $enabled ? 1 : 0 );
	}

	/**
	 * Render the ReadyLaunch header dropdown for a user.
	 *
	 * @param int $user_id Viewer.
	 *
	 * @return array Item HTML keyed by thread ID.
	 */
	protected function render( $user_id ) {
		$template = BP_PLUGIN_DIR . 'bp-templates/bp-nouveau/readylaunch/header/unread-messages.php';
		$this->assertFileExists( $template );

		$this->set_current_user( $user_id );
		wp_cache_flush();

		ob_start();
		include $template;
		$html = ob_get_clean();

		preg_match_all( '#<li class="read-item[^"]*" data-thread-id="(\d+)">(.*?)</li>#s', $html, $matches, PREG_SET_ORDER );

		$items = array();
		foreach ( $matches as $match ) {
			$items[ (int) $match[1] ] = $match[2];
		}

		return $items;
	}

	/**
	 * Text of a span of an item (posted, notification-users).
	 *
	 * @param string $item  Item HTML.
	 * @param string $css_class Span class.
	 *
	 * @return string
	 */
	protected function span_text( $item, $css_class ) {
		if ( ! preg_match( '#<span class="' . preg_quote( $css_class, '#' ) . '">(.*?)</span>#s', $item, $match ) ) {
			return '';
		}

		return trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( $match[1] ), ENT_QUOTES ) ) );
	}

	/**
	 * Flag a message as an open group message sent to all members.
	 *
	 * @param int $message_id Message ID.
	 * @param int $thread_id  Thread ID.
	 * @param int $group_id   Group ID.
	 *
	 * @return void
	 */
	protected function flag_group_message( $message_id, $thread_id, $group_id ) {
		bp_messages_update_meta( $message_id, 'group_id', $group_id );
		bp_messages_update_meta( $message_id, 'group_message_thread_id', $thread_id );
		bp_messages_update_meta( $message_id, 'group_message_users', 'all' );
		bp_messages_update_meta( $message_id, 'group_message_type', 'open' );
		bp_messages_update_meta( $message_id, 'group_message_thread_type', 'new' );
		bp_messages_update_meta( $message_id, 'message_from', 'group' );
	}

	/**
	 * Group thread from $sender to $members with a member reply (the last message has no group meta).
	 *
	 * @param int   $sender   Sender.
	 * @param int[] $members  Members.
	 * @param int   $group_id Group ID.
	 *
	 * @return int Thread ID.
	 */
	protected function create_group_thread( $sender, $members, $group_id ) {
		$message   = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $sender,
				'recipients' => $members,
				'subject'    => 'Group broadcast',
			)
		);
		$thread_id = (int) $message->thread_id;
		$this->flag_group_message( $message->id, $thread_id, $group_id );

		self::factory()->message->create(
			array(
				'sender_id'  => $members[0],
				'thread_id'  => $thread_id,
				'recipients' => array_merge( array( $sender ), array_slice( $members, 1 ) ),
				'content'    => 'Member reply',
			)
		);

		return $thread_id;
	}

	/**
	 * PROD-3077: the dropdown drops the group thread while Group Messages is disabled and lists it when enabled.
	 */
	public function test_group_thread_is_removed_from_dropdown_while_group_messages_disabled() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $group_id, $u2 );
		groups_join_group( $group_id, $u3 );

		$this->set_group_messages( false );
		$group_thread   = $this->create_group_thread( $u1, array( $u2, $u3 ), $group_id );
		$private_thread = (int) self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Private',
			)
		)->thread_id;

		$items = $this->render( $u2 );
		$this->assertArrayNotHasKey( $group_thread, $items, 'Group thread removed while disabled.' );
		$this->assertArrayHasKey( $private_thread, $items );

		$this->set_group_messages( true );
		$items = $this->render( $u2 );
		$this->assertArrayHasKey( $group_thread, $items, 'Group thread listed while enabled.' );
	}

	/**
	 * F-5 (124fb5fc68): when the last message is a member reply, the group is read from the first message,
	 * so the item shows the (escaped) group name. Release only looked at the first message when
	 * last_message_id was 0, and showed member names instead.
	 */
	public function test_group_thread_with_member_reply_shows_group_name() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create(
			array(
				'creator_id' => $u1,
				'name'       => 'rock & roll club',
			)
		);
		groups_join_group( $group_id, $u2 );
		groups_join_group( $group_id, $u3 );

		$this->set_group_messages( true );
		$group_thread = $this->create_group_thread( $u1, array( $u2, $u3 ), $group_id );

		$items = $this->render( $u3 );
		$this->assertArrayHasKey( $group_thread, $items );
		$this->assertStringContainsString( 'Rock &amp; Roll Club', $items[ $group_thread ], 'Escaped group name in the item.' );
		$this->assertStringNotContainsString( '&amp;amp;', $items[ $group_thread ], 'Escaped once.' );
		$this->assertSame( 'Rock & Roll Club', $this->span_text( $items[ $group_thread ], 'notification-users' ) );
	}

	/**
	 * Fix I: in a thread larger than one page, the "Name: " prefix of the last message follows the number of
	 * other members who have not left (release counted the full list), even when the loaded page holds only one.
	 * cbb483e3f4: the thread keeps one page of recipients (the template no longer loads the full list).
	 */
	public function test_partial_page_counts_other_active_members_like_release() {
		global $wpdb, $messages_template;

		$bp     = buddypress();
		$viewer = self::factory()->user->create();
		$others = array();
		for ( $i = 0; $i < 24; $i++ ) {
			$others[] = self::factory()->user->create();
		}

		$this->set_group_messages( true );
		$thread_id = (int) self::factory()->message->create_and_get(
			array(
				'sender_id'  => $viewer,
				'recipients' => $others,
				'subject'    => 'Large thread',
			)
		)->thread_id;

		$per_page = (int) bb_messages_recipients_per_page();
		$page     = BP_Messages_Thread::get(
			array(
				'include_threads' => array( $thread_id ),
				'per_page'        => $per_page,
			)
		);
		$page_ids = array_map( 'intval', wp_list_pluck( $page['recipients'], 'user_id' ) );
		$this->assertCount( $per_page, $page_ids, 'First page is full.' );

		// Last sender: a member outside the loaded page.
		$outside = array_values( array_diff( $others, $page_ids ) );
		$this->assertGreaterThanOrEqual( 3, count( $outside ) );
		$last_sender = $outside[0];
		self::factory()->message->create(
			array(
				'sender_id'  => $last_sender,
				'thread_id'  => $thread_id,
				'recipients' => array( $viewer ),
				'content'    => 'Reply from outside the page',
			)
		);

		// Everybody on the page except the viewer and one member has left the thread.
		$keep   = current( array_diff( $page_ids, array( $viewer ) ) );
		$leaver = array_diff( $page_ids, array( $viewer, $keep ) );
		$wpdb->query( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 1 WHERE thread_id = {$thread_id} AND user_id IN (" . implode( ',', array_map( 'intval', $leaver ) ) . ')' ); // phpcs:ignore
		bp_core_reset_incrementor( 'bp_messages' );

		$active_others = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND is_deleted = 0 AND user_id != %d", $thread_id, $viewer ) ); // phpcs:ignore
		$this->assertGreaterThanOrEqual( 2, $active_others, 'Release (full list) would count two or more others.' );

		$items = $this->render( $viewer );
		$this->assertArrayHasKey( $thread_id, $items );
		$this->assertStringStartsWith(
			bp_core_get_user_displayname( $last_sender ) . ':',
			$this->span_text( $items[ $thread_id ], 'posted' ),
			'More than one other member: the last sender name is shown, as in release.'
		);

		$this->assertLessThanOrEqual( $per_page, count( (array) $messages_template->thread->recipients ), 'Only one page of recipients is loaded.' );
	}

	/**
	 * Control: a one-to-one thread shows the excerpt without the sender name.
	 */
	public function test_one_to_one_thread_has_no_sender_prefix() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$this->set_group_messages( true );
		$thread_id = (int) self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'One to one',
				'content'    => 'Hello there',
			)
		)->thread_id;

		$items = $this->render( $u2 );
		$this->assertArrayHasKey( $thread_id, $items );
		$this->assertSame( 'Hello there', $this->span_text( $items[ $thread_id ], 'posted' ) );
	}

	/**
	 * Avatar block of an item.
	 *
	 * @param string $item Item HTML.
	 *
	 * @return string
	 */
	protected function avatar_html( $item ) {
		if ( ! preg_match( '#<div class="notification-avatar[^"]*">(.*?)</div>#s', $item, $match ) ) {
			return '';
		}

		return $match[1];
	}

	/**
	 * Thread of 25 members that is larger than one recipient page, rendered through the header fallback branch
	 * (bp_messages_get_avatars() returns nothing, as when a site filters it out).
	 *
	 * @return array {
	 *     @type int   $thread_id Thread ID.
	 *     @type int[] $members   All members.
	 *     @type int[] $page_ids  Members on the first recipient page.
	 *     @type int[] $outside   Members outside the first page.
	 * }
	 */
	protected function create_large_thread_for_fallback() {
		$members = array();
		for ( $i = 0; $i < 25; $i++ ) {
			$members[] = self::factory()->user->create();
		}

		$this->set_group_messages( true );
		$thread_id = (int) self::factory()->message->create_and_get(
			array(
				'sender_id'  => $members[0],
				'recipients' => array_slice( $members, 1 ),
				'subject'    => 'Large thread',
				'content'    => 'First message',
			)
		)->thread_id;

		$per_page = (int) bb_messages_recipients_per_page();
		$page     = BP_Messages_Thread::get(
			array(
				'include_threads' => array( $thread_id ),
				'per_page'        => $per_page,
			)
		);
		$page_ids = array_map( 'intval', wp_list_pluck( $page['recipients'], 'user_id' ) );
		$this->assertCount( $per_page, $page_ids, 'First page is full.' );
		$outside = array_values( array_diff( $members, $page_ids ) );
		$this->assertGreaterThanOrEqual( 3, count( $outside ), 'Some members are outside the first page.' );

		add_filter( 'bp_messages_get_avatars', '__return_empty_array' );

		return compact( 'thread_id', 'members', 'page_ids', 'outside' );
	}

	/**
	 * Mark members as having left the thread.
	 *
	 * @param int   $thread_id Thread ID.
	 * @param int[] $user_ids  Members.
	 *
	 * @return void
	 */
	protected function leave_thread( $thread_id, $user_ids ) {
		global $wpdb;

		$user_ids = array_filter( array_map( 'intval', (array) $user_ids ) );
		if ( empty( $user_ids ) ) {
			return;
		}

		$bp = buddypress();
		$wpdb->query( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 1 WHERE thread_id = " . (int) $thread_id . ' AND user_id IN (' . implode( ',', $user_ids ) . ')' ); // phpcs:ignore
		bp_core_reset_incrementor( 'bp_messages' );
	}

	/**
	 * R6-9: fallback branch, the last sender is outside the loaded page and still in the thread: the avatar link
	 * gets the profile hover, as in release (release read the full list). No PHP notice (notices fail the test).
	 */
	public function test_fallback_last_sender_outside_page_gets_hover_like_release() {
		$t           = $this->create_large_thread_for_fallback();
		$viewer      = $t['page_ids'][0];
		$last_sender = $t['outside'][0];
		self::factory()->message->create(
			array(
				'sender_id'  => $last_sender,
				'thread_id'  => $t['thread_id'],
				'recipients' => array( $viewer ),
				'content'    => 'Reply from outside the page',
			)
		);

		$items = $this->render( $viewer );
		$this->assertArrayHasKey( $t['thread_id'], $items );
		$this->assertStringContainsString( 'data-bb-hp-profile="' . $last_sender . '"', $this->avatar_html( $items[ $t['thread_id'] ] ) );
	}

	/**
	 * R6-9: fallback branch, the last sender outside the page has left the thread: no profile hover, as in
	 * release (release skipped members who left when it built the list).
	 */
	public function test_fallback_last_sender_who_left_gets_no_hover_like_release() {
		$t           = $this->create_large_thread_for_fallback();
		$viewer      = $t['page_ids'][0];
		$last_sender = $t['outside'][0];
		self::factory()->message->create(
			array(
				'sender_id'  => $last_sender,
				'thread_id'  => $t['thread_id'],
				'recipients' => array( $viewer ),
				'content'    => 'Reply, then left',
			)
		);
		$this->leave_thread( $t['thread_id'], array( $last_sender ) );

		$items = $this->render( $viewer );
		$this->assertArrayHasKey( $t['thread_id'], $items );
		$avatar = $this->avatar_html( $items[ $t['thread_id'] ] );
		$this->assertStringNotContainsString( 'data-bb-hp-profile="' . $last_sender . '"', $avatar );
		$this->assertStringContainsString( esc_url( bp_core_get_user_domain( $last_sender ) ), $avatar, 'The avatar still links to the last sender.' );
	}

	/**
	 * R6-9: fallback branch, viewer outside the page and the only other active member outside the page too: the
	 * other member is shown (release: first other active member of the full list).
	 */
	public function test_fallback_viewer_outside_page_shows_other_active_member_like_release() {
		$t      = $this->create_large_thread_for_fallback();
		$viewer = $t['outside'][0];
		$other  = $t['outside'][1];
		$this->leave_thread( $t['thread_id'], array_diff( $t['members'], array( $viewer, $other ) ) );

		$items = $this->render( $viewer );
		$this->assertArrayHasKey( $t['thread_id'], $items );
		$avatar = $this->avatar_html( $items[ $t['thread_id'] ] );
		$this->assertStringContainsString( 'data-bb-hp-profile="' . $other . '"', $avatar );
		$this->assertStringNotContainsString( '<img class="avatar" src="" alt="" />', $avatar, 'Not the empty avatar.' );
	}

	/**
	 * R6-9: fallback branch, viewer outside the page and every other member left: the viewer is shown
	 * (release: $current_user), not an empty avatar.
	 */
	public function test_fallback_viewer_outside_page_alone_shows_viewer_like_release() {
		$t      = $this->create_large_thread_for_fallback();
		$viewer = $t['outside'][0];
		$this->leave_thread( $t['thread_id'], array_diff( $t['members'], array( $viewer ) ) );

		$items = $this->render( $viewer );
		$this->assertArrayHasKey( $t['thread_id'], $items );
		$avatar = $this->avatar_html( $items[ $t['thread_id'] ] );
		$this->assertStringContainsString( 'data-bb-hp-profile="' . $viewer . '"', $avatar );
	}

	/**
	 * R6-9 / release parity: fallback branch, viewer on the page and the only other active member outside it.
	 * Release read the full list, so $first_three held that member and the avatar showed them (R7-T1).
	 */
	public function test_fallback_viewer_on_page_other_active_member_outside_page_like_release() {
		$t      = $this->create_large_thread_for_fallback();
		$viewer = $t['page_ids'][0];
		$other  = $t['outside'][0];
		$this->leave_thread( $t['thread_id'], array_diff( $t['members'], array( $viewer, $other ) ) );

		$items = $this->render( $viewer );
		$this->assertArrayHasKey( $t['thread_id'], $items );
		$avatar = $this->avatar_html( $items[ $t['thread_id'] ] );
		$this->assertStringContainsString( 'data-bb-hp-profile="' . $other . '"', $avatar, 'Release showed the other active member.' );
	}
}
