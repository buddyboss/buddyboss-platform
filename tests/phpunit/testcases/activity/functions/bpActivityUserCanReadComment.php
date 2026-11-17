<?php

/**
 * PROD-10649: an activity comment is readable only when its root activity is.
 *
 * A comment is stored with component 'activity' and no group of its own, so
 * bp_activity_user_can_read() checks the group of its root activity (item_id).
 *
 * @group activity
 * @group bp_activity_user_can_read
 */
class BP_Tests_Activity_Functions_BpActivityUserCanReadComment extends BP_UnitTestCase {

	protected $author;
	protected $stranger;

	public function setUp(): void {
		parent::setUp();

		$this->author   = self::factory()->user->create();
		$this->stranger = self::factory()->user->create();
	}

	/**
	 * Create a group post with a comment and a nested reply.
	 *
	 * @param string $status Group status.
	 *
	 * @return array Group, root, comment and reply IDs.
	 */
	protected function group_thread( $status ) {
		$g = self::factory()->group->create(
			array(
				'creator_id' => $this->author,
				'status'     => $status,
			)
		);

		$root = self::factory()->activity->create(
			array(
				'component'     => buddypress()->groups->id,
				'type'          => 'activity_update',
				'user_id'       => $this->author,
				'item_id'       => $g,
				'hide_sitewide' => 'public' !== $status,
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $root,
				'content'     => 'comment',
			)
		);

		$reply = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $root,
				'parent_id'   => $comment,
				'content'     => 'reply',
			)
		);

		return array( $g, $root, $comment, $reply );
	}

	protected function can_read( $activity_id, $user_id ) {
		$this->set_current_user( $user_id );

		return bp_activity_user_can_read( new BP_Activity_Activity( $activity_id ), $user_id );
	}

	public function test_comment_and_reply_in_private_group_are_refused_to_non_member() {
		list( , $root, $comment, $reply ) = $this->group_thread( 'private' );

		$this->assertFalse( $this->can_read( $root, $this->stranger ) );
		$this->assertFalse( $this->can_read( $comment, $this->stranger ) );
		$this->assertFalse( $this->can_read( $reply, $this->stranger ) );
	}

	public function test_comment_and_reply_in_hidden_group_are_refused_to_non_member() {
		list( , , $comment, $reply ) = $this->group_thread( 'hidden' );

		$this->assertFalse( $this->can_read( $comment, $this->stranger ) );
		$this->assertFalse( $this->can_read( $reply, $this->stranger ) );
	}

	public function test_comment_in_private_group_is_readable_by_member() {
		list( $g, , $comment, $reply ) = $this->group_thread( 'private' );

		$member = self::factory()->user->create();
		self::add_user_to_group( $member, $g );

		$this->assertTrue( $this->can_read( $comment, $member ) );
		$this->assertTrue( $this->can_read( $reply, $member ) );
	}

	public function test_comment_and_reply_in_private_group_are_refused_to_banned_member() {
		list( $g, , $comment, $reply ) = $this->group_thread( 'private' );

		$banned = self::factory()->user->create();
		self::add_user_to_group( $banned, $g );
		$member = new BP_Groups_Member( $banned, $g );
		$member->ban();

		$this->assertFalse( $this->can_read( $comment, $banned ) );
		$this->assertFalse( $this->can_read( $reply, $banned ) );
	}

	public function test_comment_in_private_group_is_readable_by_admin() {
		list( , , $comment ) = $this->group_thread( 'private' );

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertTrue( $this->can_read( $comment, $admin ) );
	}

	public function test_comment_in_public_group_is_readable_by_non_member() {
		list( , , $comment, $reply ) = $this->group_thread( 'public' );

		$this->assertTrue( $this->can_read( $comment, $this->stranger ) );
		$this->assertTrue( $this->can_read( $reply, $this->stranger ) );
	}

	public function test_comment_on_profile_post_is_readable_by_others() {
		$root    = self::factory()->activity->create( array( 'user_id' => $this->author ) );
		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $root,
				'content'     => 'comment',
			)
		);

		$this->assertTrue( $this->can_read( $comment, $this->stranger ) );
	}

	public function test_comment_on_spammed_root_is_refused() {
		$root    = self::factory()->activity->create( array( 'user_id' => $this->author ) );
		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $root,
				'content'     => 'comment',
			)
		);

		$root_activity = new BP_Activity_Activity( $root );
		bp_activity_mark_as_spam( $root_activity );
		$root_activity->save();

		$this->assertFalse( $this->can_read( $comment, $this->stranger ) );
	}

	public function test_comment_whose_root_is_missing_is_refused() {
		$root    = self::factory()->activity->create( array( 'user_id' => $this->author ) );
		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $root,
				'content'     => 'comment',
			)
		);

		$comment_activity = new BP_Activity_Activity( $comment );

		// Remove only the root row, as orphaned data would look.
		BP_Activity_Activity::delete( array( 'id' => $root ) );
		wp_cache_delete( $root, 'bp_activity' );

		$this->set_current_user( $this->stranger );
		$this->assertFalse( bp_activity_user_can_read( $comment_activity, $this->stranger ) );
	}

	/**
	 * @group bb_activity_user_can_view
	 */
	public function test_view_refuses_comment_in_private_group_to_non_member() {
		list( $g, $root, $comment, $reply ) = $this->group_thread( 'private' );

		$member = self::factory()->user->create();
		self::add_user_to_group( $member, $g );

		$this->set_current_user( $this->stranger );
		$this->assertFalse( bb_activity_user_can_view( new BP_Activity_Activity( $root ), $this->stranger ) );
		$this->assertFalse( bb_activity_user_can_view( new BP_Activity_Activity( $reply ), $this->stranger ) );

		$this->set_current_user( $member );
		$this->assertTrue( bb_activity_user_can_view( new BP_Activity_Activity( $comment ), $member ) );
	}

	/**
	 * @group bb_activity_user_can_view
	 */
	public function test_view_follows_photo_of_friends_only_post() {
		$friend = self::factory()->user->create();
		friends_add_friend( $this->author, $friend, true );

		$post = self::factory()->activity->create(
			array(
				'user_id' => $this->author,
				'privacy' => 'friends',
			)
		);

		// Each photo of a post with several photos gets a hidden sub-activity.
		$photo = self::factory()->activity->create(
			array(
				'user_id'           => $this->author,
				'privacy'           => 'media',
				'hide_sitewide'     => true,
				'secondary_item_id' => $post,
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $photo,
				'content'     => 'comment',
			)
		);

		$this->set_current_user( $this->stranger );
		$this->assertFalse( bb_activity_user_can_view( new BP_Activity_Activity( $photo ), $this->stranger ) );
		$this->assertFalse( bb_activity_user_can_view( new BP_Activity_Activity( $comment ), $this->stranger ) );

		$this->set_current_user( $friend );
		$this->assertTrue( bb_activity_user_can_view( new BP_Activity_Activity( $comment ), $friend ) );
	}

	/**
	 * @group bb_activity_user_can_view
	 */
	public function test_view_allows_comment_on_public_post_for_guest() {
		$root    = self::factory()->activity->create( array( 'user_id' => $this->author ) );
		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $root,
				'content'     => 'comment',
			)
		);

		$this->set_current_user( 0 );
		$this->assertTrue( bb_activity_user_can_view( new BP_Activity_Activity( $comment ), 0 ) );
	}

	/**
	 * @group bb_activity_user_can_view
	 */
	public function test_view_accepts_an_id_and_an_activity_row() {
		list( , , $comment ) = $this->group_thread( 'private' );

		// AJAX handlers hold a row from bp_activity_get_specific(), not a BP_Activity_Activity.
		$row = bp_activity_get_specific(
			array(
				'activity_ids'     => $comment,
				'display_comments' => 'stream',
			)
		)['activities'][0];

		$this->set_current_user( $this->stranger );
		$this->assertFalse( bb_activity_user_can_view( $comment, $this->stranger ) );
		$this->assertFalse( bb_activity_user_can_view( $row, $this->stranger ) );

		$this->set_current_user( $this->author );
		$this->assertTrue( bb_activity_user_can_view( $comment, $this->author ) );
		$this->assertTrue( bb_activity_user_can_view( $row, $this->author ) );
	}

	/**
	 * @group bb_activity_user_can_view
	 */
	public function test_view_lets_moderators_through() {
		$post = self::factory()->activity->create(
			array(
				'user_id' => $this->author,
				'privacy' => 'onlyme',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $post,
				'content'     => 'comment',
			)
		);

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->set_current_user( $this->stranger );
		$this->assertFalse( bb_activity_user_can_view( $comment, $this->stranger ) );

		$this->set_current_user( $admin );
		$this->assertTrue( bb_activity_user_can_view( $comment, $admin ) );
	}

	/**
	 * @group bb_activity_user_can_view
	 */
	public function test_view_is_filterable() {
		list( , , $comment ) = $this->group_thread( 'private' );

		add_filter( 'bb_activity_user_can_view', '__return_true' );
		$this->set_current_user( $this->stranger );
		$can_view = bb_activity_user_can_view( $comment, $this->stranger );
		remove_filter( 'bb_activity_user_can_view', '__return_true' );

		$this->assertTrue( $can_view );
	}

	/**
	 * A comment copies its post's privacy but keeps the commenter as its owner.
	 *
	 * @group bb_activity_user_can_view
	 */
	public function test_view_follows_post_author_for_others_comment_on_friends_only_post() {
		$commenter     = self::factory()->user->create();
		$author_friend = self::factory()->user->create();
		friends_add_friend( $this->author, $commenter, true );
		friends_add_friend( $this->author, $author_friend, true );

		$post = self::factory()->activity->create(
			array(
				'user_id' => $this->author,
				'privacy' => 'friends',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $commenter,
				'activity_id' => $post,
				'content'     => 'comment',
			)
		);

		$reply = bp_activity_new_comment(
			array(
				'user_id'     => $this->author,
				'activity_id' => $post,
				'parent_id'   => $comment,
				'content'     => 'reply',
			)
		);

		// A friend of the author, not of the commenter.
		$this->set_current_user( $author_friend );
		$this->assertTrue( bb_activity_user_can_view( $comment, $author_friend ) );
		$this->assertTrue( bb_activity_user_can_view( $reply, $author_friend ) );

		$this->set_current_user( $this->stranger );
		$this->assertFalse( bb_activity_user_can_view( $comment, $this->stranger ) );
		$this->assertFalse( bb_activity_user_can_view( $reply, $this->stranger ) );
	}

	/**
	 * @group bb_activity_user_can_view
	 */
	public function test_view_lets_author_read_others_comment_on_only_me_post() {
		$commenter = self::factory()->user->create();

		$post = self::factory()->activity->create(
			array(
				'user_id' => $this->author,
				'privacy' => 'public',
			)
		);

		$comment = bp_activity_new_comment(
			array(
				'user_id'     => $commenter,
				'activity_id' => $post,
				'content'     => 'comment',
			)
		);

		// The author narrows the post afterwards; saving it copies onlyme to its comments,
		// which stay owned by the commenter.
		$root          = new BP_Activity_Activity( $post );
		$root->privacy = 'onlyme';
		$root->save();
		$this->assertSame( 'onlyme', ( new BP_Activity_Activity( $comment ) )->privacy );

		$this->set_current_user( $this->author );
		$this->assertTrue( bb_activity_user_can_view( $comment, $this->author ) );

		$this->set_current_user( $commenter );
		$this->assertFalse( bb_activity_user_can_view( $comment, $commenter ) );
	}
}
