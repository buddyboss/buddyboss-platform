<?php

/**
 * Last-name visibility must hold on every activity output path, including the
 * per-activity comment tree cache that is shared across viewers.
 *
 * @group activity
 * @group bb_activity_get_item_user_displayname
 * @group bp_activity_comments_cache
 */
class BP_Tests_Activity_Functions_BbActivityNamePrivacy extends BP_UnitTestCase {

	protected $xprofile_was_active;
	protected $format_backup;

	public function set_up() {
		parent::set_up();

		$this->xprofile_was_active = bp_is_active( 'xprofile' );
		buddypress()->active_components['xprofile'] = '1';

		$this->format_backup = bp_get_option( 'bp-display-name-format' );
		bp_update_option( 'bp-display-name-format', 'first_last_name' );
	}

	public function tear_down() {
		$GLOBALS['bb_default_display_avatar'] = false;
		bp_update_option( 'bp-display-name-format', $this->format_backup );

		if ( ! $this->xprofile_was_active ) {
			unset( buddypress()->active_components['xprofile'] );
		}

		parent::tear_down();
	}

	/**
	 * Create a member whose Last Name is visible to logged-in members only.
	 *
	 * The stored WP display_name legitimately holds the full name (the sync writes it with
	 * viewer = self); only the read side is viewer-dependent.
	 *
	 * @return int User ID.
	 */
	protected function create_member_with_hidden_last_name() {
		$u = self::factory()->user->create();

		// `profile_update` syncs first/last name into the xprofile fields.
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Alex',
				'last_name'    => 'Quillfeather',
				'display_name' => 'Alex Quillfeather',
			)
		);

		// Set the visibility last: the sync above re-saves the default levels.
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

		// The format layer memoises the member's name per request; the first resolution
		// happened during user creation, before the names existed. Refresh it once.
		$GLOBALS['bb_default_display_avatar'] = true;
		bp_core_get_user_displayname( $u, $u );

		return $u;
	}

	/**
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_item_displayname_hides_last_name_from_guest_and_shows_it_to_members() {
		$u      = $this->create_member_with_hidden_last_name();
		$member = self::factory()->user->create();

		$item               = new stdClass();
		$item->user_id      = $u;
		$item->display_name = 'Alex Quillfeather'; // Raw WP column, as joined by the activity query.

		$this->set_current_user( 0 );
		$this->assertSame( 'Alex', bb_activity_get_item_user_displayname( $item ) );

		$this->set_current_user( $member );
		$this->assertSame( 'Alex Quillfeather', bb_activity_get_item_user_displayname( $item ) );
	}

	/**
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_item_displayname_prefers_resolved_user_fullname() {
		$item                = new stdClass();
		$item->user_id       = 0;
		$item->display_name  = 'Raw Column';
		$item->user_fullname = 'Already Resolved';

		$this->assertSame( 'Already Resolved', bb_activity_get_item_user_displayname( $item ) );
		$this->assertSame( '', bb_activity_get_item_user_displayname( null ) );
	}

	/**
	 * bp_core_get_user_displayname() must strip a hidden last name wherever it sits in the
	 * stored display_name - a bare "Last", a "Last First" order, or any value not written by
	 * BuddyBoss's "First Last" sync (the wp-admin "Display name publicly as" dropdown,
	 * importers, other plugins). A plain str_replace of ' ' . $last_name only matched a
	 * space-prefixed trailing token and leaked the name for every other shape.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_strips_hidden_last_name_in_any_order() {
		$member = self::factory()->user->create();

		// Bare last name (display_name is only the hidden last name): a guest must not see it;
		// it falls back to the public first name.
		$u1 = $this->create_member_with_hidden_last_name();
		wp_update_user( array( 'ID' => $u1, 'display_name' => 'Quillfeather' ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertStringNotContainsString( 'Quillfeather', bp_core_get_user_displayname( $u1, 0 ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->assertSame( 'Alex', bp_core_get_user_displayname( $u1, 0 ) );

		// Last-first order.
		$u2 = $this->create_member_with_hidden_last_name();
		wp_update_user( array( 'ID' => $u2, 'display_name' => 'Quillfeather Alex' ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'Alex', bp_core_get_user_displayname( $u2, 0 ) );

		// Control: normal "First Last" still redacts to the first name for a guest and stays
		// full for a logged-in member.
		$u3 = $this->create_member_with_hidden_last_name();
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'Alex', bp_core_get_user_displayname( $u3, 0 ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( $member );
		$this->assertSame( 'Alex Quillfeather', bp_core_get_user_displayname( $u3, $member ) );
	}

	/**
	 * The comment tree is cached per activity with no viewer in the key. A tree cached
	 * while a member viewed it must not hand that member's `user_fullname` to a guest.
	 *
	 * @group bp_activity_comments_cache
	 */
	public function test_cached_comment_tree_reresolves_names_for_current_viewer() {
		$bp                = buddypress();
		$reset_component   = $bp->current_component;
		$reset_action      = $bp->current_action;

		$author = $this->create_member_with_hidden_last_name();
		$other  = self::factory()->user->create();
		$viewer = self::factory()->user->create();

		$activity_id = self::factory()->activity->create(
			array(
				'type'    => 'activity_update',
				'user_id' => $other,
			)
		);

		$comment_id = bp_activity_new_comment(
			array(
				'user_id'     => $author,
				'activity_id' => $activity_id,
				'content'     => 'comment by the member with a hidden last name',
			)
		);

		$reply_id = bp_activity_new_comment(
			array(
				'user_id'     => $author,
				'activity_id' => $activity_id,
				'parent_id'   => $comment_id,
				'content'     => 'nested reply by the same member',
			)
		);

		// Single-activity context: this is the only front-end path that caches the tree.
		$bp->current_component = 'activity';
		$bp->current_action    = (string) $activity_id;
		$this->assertTrue( bp_is_single_activity() );

		// 1) A logged-in member views the permalink first and primes the cache with the full name.
		$this->set_current_user( $viewer );
		wp_cache_delete( $activity_id, 'bp_activity_comments' );
		$as_member = $this->get_comment_tree( $activity_id );
		$this->assertSame( 'Alex Quillfeather', $as_member[ $comment_id ]->user_fullname );
		$this->assertSame( 'Alex Quillfeather', $as_member[ $comment_id ]->children[ $reply_id ]->user_fullname );

		$cached = wp_cache_get( $activity_id, 'bp_activity_comments' );
		$this->assertIsArray( $cached, 'The tree must be cached for the single-activity request.' );

		// 2) A guest reads the same activity from the warm cache.
		$this->set_current_user( 0 );
		$as_guest = $this->get_comment_tree( $activity_id );
		$this->assertSame( 'Alex', $as_guest[ $comment_id ]->user_fullname );
		$this->assertSame( 'Alex', $as_guest[ $comment_id ]->children[ $reply_id ]->user_fullname );

		// 3) The reverse direction: a member must still get the name they are entitled to.
		$this->set_current_user( $other );
		$as_other = $this->get_comment_tree( $activity_id );
		$this->assertSame( 'Alex Quillfeather', $as_other[ $comment_id ]->user_fullname );

		$bp->current_component = $reset_component;
		$bp->current_action    = $reset_action;
	}

	/**
	 * The activity loop template tags must output the viewer's name, never the raw
	 * `display_name` joined by the activity query.
	 *
	 * @group bp_get_activity_avatar
	 * @group bp_get_activity_comment_name
	 */
	public function test_activity_loop_template_tags_hide_last_name_from_guest() {
		global $activities_template;

		$author = $this->create_member_with_hidden_last_name();

		$activity_id = self::factory()->activity->create(
			array(
				'type'    => 'activity_update',
				'user_id' => $author,
			)
		);
		$comment_id  = bp_activity_new_comment(
			array(
				'user_id'     => $author,
				'activity_id' => $activity_id,
				'content'     => 'comment',
			)
		);

		$this->set_current_user( 0 );

		$this->assertTrue( bp_has_activities( array( 'include' => $activity_id, 'display_comments' => 'threaded', 'show_hidden' => true ) ) );
		bp_the_activity();

		$this->assertSame( 'Alex Quillfeather', $activities_template->activity->display_name, 'Fixture: the raw joined column holds the full name.' );
		$this->assertSame( 'Alex', bp_get_activity_member_display_name() );
		$this->assertStringContainsString( 'alt="Profile photo of Alex"', bp_get_activity_avatar() );
		$this->assertStringNotContainsString( 'Quillfeather', bp_get_activity_avatar() );
		$this->assertStringNotContainsString( 'Quillfeather', bp_get_activity_secondary_avatar() );

		// Inside the comment loop the tags read from `current_comment`.
		$this->assertArrayHasKey( $comment_id, $activities_template->activity->children );
		$activities_template->activity->current_comment = $activities_template->activity->children[ $comment_id ];
		$this->assertSame( 'Alex', bp_get_activity_comment_name() );
		$this->assertStringContainsString( 'alt="Profile photo of Alex"', bp_get_activity_avatar() );
		unset( $activities_template->activity->current_comment );

		$activities_template = null;
	}

	/**
	 * Group members loop tags must not expose a hidden last name to guests.
	 *
	 * @group bp_get_group_member_name
	 */
	public function test_group_members_loop_hides_last_name_from_guest() {
		global $members_template;

		if ( ! bp_is_active( 'groups' ) ) {
			$this->markTestSkipped( 'Groups component is not active.' );
		}

		$author  = $this->create_member_with_hidden_last_name();
		$creator = self::factory()->user->create();
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );
		groups_join_group( $group, $author );

		$this->set_current_user( 0 );
		$this->assertTrue( bp_group_has_members( array( 'group_id' => $group, 'exclude_admins_mods' => false ) ) );

		$found = false;
		while ( bp_group_members() ) {
			bp_group_the_member();
			if ( (int) bp_get_group_member_id() !== $author ) {
				continue;
			}
			$found = true;
			$this->assertSame( 'Alex', bp_get_group_member_name() );
			$this->assertStringContainsString( 'alt="Profile photo of Alex"', bp_get_group_member_avatar() );
			$this->assertStringNotContainsString( 'Quillfeather', bp_get_group_member_avatar_thumb() );
			$this->assertStringNotContainsString( 'Quillfeather', bp_get_group_member_avatar_mini() );
		}
		$this->assertTrue( $found, 'The member with the hidden last name must be in the loop.' );

		$members_template = null;
	}

	/**
	 * Fetch the nested comment tree for an activity through the public API.
	 *
	 * @param int $activity_id Activity ID.
	 * @return array Comment tree keyed by comment ID.
	 */
	protected function get_comment_tree( $activity_id ) {
		$activity = new BP_Activity_Activity( $activity_id );

		return BP_Activity_Activity::get_activity_comments( $activity_id, $activity->mptt_left, $activity->mptt_right );
	}
}
