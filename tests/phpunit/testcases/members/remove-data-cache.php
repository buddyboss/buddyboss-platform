<?php
/**
 * @group members
 * @group cache
 */
class BP_Tests_Members_Remove_Data_Cache extends BP_UnitTestCase {

	/**
	 * Removing a member's data must not evict the whole object cache.
	 */
	public function test_bp_core_remove_data_does_not_flush_unrelated_cache() {
		$user_id = self::factory()->user->create();

		wp_cache_set( 'prod_9882_unrelated', 'warm', 'bp_tests' );
		wp_cache_set( 'bp_user_' . $user_id, 'cached', 'bp' );
		wp_cache_set( 'bp_total_member_count', 10, 'bp' );

		bp_core_remove_data( $user_id );

		$this->assertSame( 'warm', wp_cache_get( 'prod_9882_unrelated', 'bp_tests' ) );
		$this->assertFalse( wp_cache_get( 'bp_user_' . $user_id, 'bp' ) );
		$this->assertFalse( wp_cache_get( 'bp_total_member_count', 'bp' ) );
	}

	/**
	 * Deleting a member must refresh the connection caches of the members on the
	 * other side of a pending request or a confirmed friendship, and the friend
	 * notification counts of the request recipients.
	 *
	 * @group friends
	 * @group bb_friends_clear_related_users_cache_on_remove_data
	 */
	public function test_friends_caches_of_other_members_are_cleared_when_member_is_deleted() {
		if ( ! bp_is_active( 'friends' ) ) {
			$this->markTestSkipped( 'The Connections component is not active.' );
		}

		$deleted   = self::factory()->user->create();
		$recipient = self::factory()->user->create();
		$friend    = self::factory()->user->create();

		// Pending request from the deleted member, and a confirmed friendship.
		friends_add_friend( $deleted, $recipient );
		friends_add_friend( $deleted, $friend, true );

		// Prime the other members' caches.
		$this->assertSame( array( $deleted ), BP_Friends_Friendship::get_friendship_request_user_ids( $recipient ) );
		$this->assertCount( 1, BP_Friends_Friendship::get_friendship_ids_for_user( $recipient ) );
		$this->assertCount( 1, BP_Friends_Friendship::get_friendship_ids_for_user( $friend ) );
		$this->assertSame( 'awaiting_response', BP_Friends_Friendship::check_is_friend( $recipient, $deleted ) );

		$has_notifications = bp_is_active( 'notifications' );
		if ( $has_notifications ) {
			$this->assertSame( 1, (int) bp_notifications_get_unread_notification_count( $recipient ) );
		}

		$this->delete_member( $deleted );

		$this->assertEmpty( BP_Friends_Friendship::get_friendship_request_user_ids( $recipient ) );
		$this->assertEmpty( BP_Friends_Friendship::get_friendship_ids_for_user( $recipient ) );
		$this->assertEmpty( BP_Friends_Friendship::get_friendship_ids_for_user( $friend ) );
		$this->assertSame( 'not_friends', BP_Friends_Friendship::check_is_friend( $recipient, $deleted ) );

		if ( $has_notifications ) {
			$this->assertSame( 0, (int) bp_notifications_get_unread_notification_count( $recipient ) );
		}
	}

	/**
	 * Marking a member as spam keeps the account, so the other members must not
	 * keep seeing a cached pending request from, or friendship with, the spammer.
	 *
	 * @group friends
	 * @group bb_friends_clear_related_users_cache_on_remove_data
	 */
	public function test_friends_caches_of_other_members_are_cleared_when_member_is_spammed() {
		if ( ! bp_is_active( 'friends' ) ) {
			$this->markTestSkipped( 'The Connections component is not active.' );
		}

		$spammer   = self::factory()->user->create();
		$recipient = self::factory()->user->create();

		friends_add_friend( $spammer, $recipient );

		$this->assertSame( array( $spammer ), BP_Friends_Friendship::get_friendship_request_user_ids( $recipient ) );
		$this->assertSame( 'pending', BP_Friends_Friendship::check_is_friend( $spammer, $recipient ) );

		do_action( 'bp_make_spam_user', $spammer );

		$this->assertEmpty( BP_Friends_Friendship::get_friendship_request_user_ids( $recipient ) );
		$this->assertSame( 'not_friends', BP_Friends_Friendship::check_is_friend( $spammer, $recipient ) );
	}

	/**
	 * Deleting a member must refresh the follower/following counts and lists of
	 * the members they followed and the members who followed them.
	 *
	 * @group activity
	 * @group bb_activity_follow_clear_related_users_cache_on_remove_data
	 */
	public function test_follow_caches_of_other_members_are_cleared_when_member_is_deleted() {
		if ( ! bp_is_active( 'activity' ) ) {
			$this->markTestSkipped( 'The Activity component is not active.' );
		}

		$deleted  = self::factory()->user->create();
		$leader   = self::factory()->user->create();
		$follower = self::factory()->user->create();

		bp_start_following(
			array(
				'leader_id'   => $leader,
				'follower_id' => $deleted,
			)
		);
		bp_start_following(
			array(
				'leader_id'   => $deleted,
				'follower_id' => $follower,
			)
		);

		// Prime the other members' caches.
		$leader_counts   = BP_Activity_Follow::get_counts( $leader );
		$follower_counts = BP_Activity_Follow::get_counts( $follower );
		$this->assertSame( 1, $leader_counts['followers'] );
		$this->assertSame( 1, $follower_counts['following'] );
		$this->assertSame( array( $deleted ), array_map( 'intval', BP_Activity_Follow::get_followers( $leader ) ) );
		$this->assertSame( array( $deleted ), array_map( 'intval', BP_Activity_Follow::get_following( $follower ) ) );

		$this->delete_member( $deleted );

		$leader_counts   = BP_Activity_Follow::get_counts( $leader );
		$follower_counts = BP_Activity_Follow::get_counts( $follower );
		$this->assertSame( 0, $leader_counts['followers'] );
		$this->assertSame( 0, $follower_counts['following'] );
		$this->assertSame( array(), BP_Activity_Follow::get_followers( $leader ) );
		$this->assertSame( array(), BP_Activity_Follow::get_following( $follower ) );
	}

	/**
	 * Removing a member's blog data deletes their blog rows with a direct query,
	 * so the sitewide blog total and the member's own blog count must be refreshed.
	 *
	 * @group blogs
	 * @group bb_blogs_clear_cache_on_remove_data
	 */
	public function test_blog_counts_are_refreshed_after_member_blog_data_is_removed() {
		if ( ! is_multisite() || ! bp_is_active( 'blogs' ) ) {
			$this->markTestSkipped( 'Blog tracking requires multisite and the Blogs component.' );
		}

		// The blog factory fires the deprecated `wpmu_new_blog` hook that BuddyBoss still listens to.
		if ( function_exists( 'wp_initialize_site' ) ) {
			$this->setExpectedDeprecated( 'wpmu_new_blog' );
		}

		$user_id = self::factory()->user->create();
		$blog_id = self::factory()->blog->create( array( 'meta' => array( 'public' => 1 ) ) );

		bp_blogs_record_blog( $blog_id, $user_id );

		// Prime the counts.
		$total = (int) bp_blogs_total_blogs();
		$this->assertGreaterThan( 0, $total );
		$this->assertSame( 1, (int) bp_blogs_total_blogs_for_user( $user_id ) );

		bp_blogs_remove_data( $user_id );

		$this->assertSame( $total - 1, (int) bp_blogs_total_blogs() );
		$this->assertSame( 0, (int) bp_blogs_total_blogs_for_user( $user_id ) );
	}

	/**
	 * Delete a member through the regular `delete_user` hook chain.
	 *
	 * Avatar removal is unhooked because, in the test environment, it runs the
	 * profile completion code against fixture users without profile data, which
	 * is unrelated to the caches under test.
	 *
	 * @param int $user_id ID of the member to delete.
	 */
	protected function delete_member( $user_id ) {
		remove_action( 'delete_user', 'bp_core_delete_avatar_on_user_delete' );
		remove_action( 'wpmu_delete_user', 'bp_core_delete_avatar_on_user_delete' );

		self::delete_user( $user_id );
	}
}
