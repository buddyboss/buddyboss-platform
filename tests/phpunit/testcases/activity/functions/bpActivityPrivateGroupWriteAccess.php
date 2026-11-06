<?php

/**
 * PROD-10649: commenting, reacting and the favorites feed follow the group rules of the activity.
 *
 * bb_validate_activity_privacy() only checks the activity privacy, never the group, so these
 * paths let a non-member of a private or hidden group write to it or read it back.
 *
 * @group activity
 * @group bp_activity_new_comment
 */
class BP_Tests_Activity_Functions_BpActivityPrivateGroupWriteAccess extends BP_UnitTestCase {

	protected $author;
	protected $member;
	protected $stranger;
	protected $group_id;
	protected $root;
	protected $comment;

	/**
	 * The test install has no reactions yet. Add the default Like once for the class, because
	 * bb_reactions_reaction_id() keeps the ID in a static that a per-test rollback would orphan.
	 */
	public static function wpSetUpBeforeClass() {
		if ( function_exists( 'bb_load_reaction' ) && bb_load_reaction() && ! bp_get_option( 'bb_reactions_default_like_reaction_added' ) ) {
			bb_load_reaction()->bb_register_activity_like();
		}
	}

	public function setUp(): void {
		parent::setUp();

		$this->author   = self::factory()->user->create();
		$this->member   = self::factory()->user->create();
		$this->stranger = self::factory()->user->create();

		$this->group_id = self::factory()->group->create(
			array(
				'creator_id' => $this->author,
				'status'     => 'private',
			)
		);
		self::add_user_to_group( $this->member, $this->group_id );

		$this->root = self::factory()->activity->create(
			array(
				'component'     => buddypress()->groups->id,
				'type'          => 'activity_update',
				'user_id'       => $this->author,
				'item_id'       => $this->group_id,
				'hide_sitewide' => true,
			)
		);

		$this->comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $this->root,
				'content'     => 'private comment',
			)
		);

	}

	protected function skip_without_reactions() {
		if ( ! function_exists( 'bb_load_reaction' ) || ! bb_load_reaction() || ! bb_load_reaction()->bb_reactions_reaction_id() ) {
			$this->markTestSkipped( 'Reactions are not available.' );
		}
	}

	protected function comment_as( $user_id, $activity_id, $parent_id = false ) {
		$this->set_current_user( $user_id );

		return bp_activity_new_comment(
			array(
				'user_id'     => $user_id,
				'activity_id' => $activity_id,
				'parent_id'   => $parent_id,
				'content'     => 'reply',
				'error_type'  => 'wp_error',
			)
		);
	}

	protected function react_as( $user_id, $activity_id ) {
		$this->set_current_user( $user_id );

		return bp_activity_add_user_favorite( $activity_id, $user_id, array( 'error_type' => 'wp_error' ) );
	}

	protected function favorite_ids_as( $user_id, $scope = 'favorites' ) {
		$this->set_current_user( $user_id );

		$activities = bp_activity_get(
			array(
				'scope'       => $scope,
				'user_id'     => $user_id,
				'show_hidden' => false,
				'per_page'    => 50,
			)
		);

		return array_map( 'intval', wp_list_pluck( $activities['activities'], 'id' ) );
	}

	protected function add_reaction_row( $user_id, $activity_id ) {
		$reaction = bb_load_reaction();

		$added = $reaction->bb_add_user_item_reaction(
			array(
				'user_id'     => $user_id,
				'item_id'     => $activity_id,
				'item_type'   => 'activity',
				'reaction_id' => $reaction->bb_reactions_reaction_id(),
			)
		);
		$this->assertNotEmpty( $added );

		return $added;
	}

	public function test_non_member_cannot_comment_on_private_group_post() {
		$result = $this->comment_as( $this->stranger, $this->root );

		$this->assertWPError( $result );
		$this->assertSame( 'bp_activity_comment_not_allowed', $result->get_error_code() );
	}

	public function test_non_member_cannot_reply_to_private_group_comment() {
		$result = $this->comment_as( $this->stranger, $this->root, $this->comment );

		$this->assertWPError( $result );
	}

	public function test_banned_member_cannot_comment_on_private_group_post() {
		$member = new BP_Groups_Member( $this->member, $this->group_id );
		$member->ban();

		$this->assertWPError( $this->comment_as( $this->member, $this->root ) );
	}

	public function test_member_can_comment_and_reply_on_private_group_post() {
		$this->assertIsInt( $this->comment_as( $this->member, $this->root ) );
		$this->assertIsInt( $this->comment_as( $this->member, $this->root, $this->comment ) );
	}

	public function test_reply_on_public_post_cannot_target_a_private_group_comment() {
		$public_root = self::factory()->activity->create( array( 'user_id' => $this->stranger ) );

		$this->assertWPError( $this->comment_as( $this->stranger, $public_root, $this->comment ) );
	}

	public function test_reply_cannot_use_a_comment_from_another_thread_as_parent() {
		$own_root    = self::factory()->activity->create( array( 'user_id' => $this->stranger ) );
		$other_root  = self::factory()->activity->create( array( 'user_id' => $this->author ) );
		$other_reply = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $other_root,
				'content'     => 'other thread',
			)
		);

		// Before the fix this saved the row and then never returned.
		$result = $this->comment_as( $this->stranger, $own_root, $other_reply );

		$this->assertWPError( $result );
		$this->assertSame( 'invalid_parent_activity', $result->get_error_code() );
		global $wpdb;
		$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bp_activity WHERE type = 'activity_comment' AND item_id = %d", $own_root ) ) );
	}

	public function test_reply_cannot_use_another_post_as_parent() {
		$own_root   = self::factory()->activity->create( array( 'user_id' => $this->stranger ) );
		$other_root = self::factory()->activity->create( array( 'user_id' => $this->author ) );

		$this->assertWPError( $this->comment_as( $this->stranger, $own_root, $other_root ) );
	}

	public function test_nested_reply_in_same_public_thread_still_works() {
		$root    = self::factory()->activity->create( array( 'user_id' => $this->author ) );
		$comment = $this->comment_as( $this->stranger, $root );
		$reply   = $this->comment_as( $this->author, $root, $comment );

		$this->assertIsInt( $reply );
		$this->assertIsInt( $this->comment_as( $this->stranger, $root, $reply ) );
	}

	public function test_non_member_can_comment_on_public_profile_post() {
		$public_root = self::factory()->activity->create( array( 'user_id' => $this->author ) );

		$this->assertIsInt( $this->comment_as( $this->stranger, $public_root ) );
	}

	public function test_non_member_cannot_react_to_private_group_post() {
		$this->skip_without_reactions();

		$result = $this->react_as( $this->stranger, $this->root );

		$this->assertWPError( $result );
		$this->assertSame( 'bp_activity_add_user_favorite_not_allowed', $result->get_error_code() );
	}

	public function test_member_can_react_to_private_group_post() {
		$this->skip_without_reactions();

		$this->assertNotWPError( $this->react_as( $this->member, $this->root ) );
	}

	public function test_favorites_scope_returns_nothing_for_guest() {
		$this->skip_without_reactions();

		$this->add_reaction_row( $this->member, $this->root );

		$this->assertSame( array(), $this->favorite_ids_as( 0 ) );
	}

	public function test_favorites_scope_drops_private_group_post_after_leaving_group() {
		$this->skip_without_reactions();

		$this->add_reaction_row( $this->member, $this->root );
		$this->assertContains( $this->root, $this->favorite_ids_as( $this->member ) );

		groups_leave_group( $this->group_id, $this->member );

		$this->assertNotContains( $this->root, $this->favorite_ids_as( $this->member ) );
	}

	public function test_favorites_scope_keeps_private_group_post_for_admin() {
		$this->skip_without_reactions();

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->add_reaction_row( $admin, $this->root );

		$this->assertContains( $this->root, $this->favorite_ids_as( $admin ) );
	}

	public function test_media_scope_matches_nothing_without_specific_ids() {
		$this->skip_without_reactions();

		$sub_activity = self::factory()->activity->create(
			array(
				'component'     => buddypress()->groups->id,
				'type'          => 'activity_update',
				'user_id'       => $this->author,
				'item_id'       => $this->group_id,
				'privacy'       => 'media',
				'hide_sitewide' => true,
			)
		);

		$this->add_reaction_row( $this->stranger, $this->root );

		$this->assertNotContains( $sub_activity, $this->favorite_ids_as( $this->stranger, 'favorites,media' ) );
	}
}
