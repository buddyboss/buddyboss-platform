<?php
/**
 * @group core
 * @group drafts
 */
class BP_Tests_Core_Drafts extends BP_UnitTestCase {

	public function test_draft_meta_key_matching_accepts_only_buddyboss_shapes() {
		$this->assertTrue( bb_draft_is_draft_meta_key( 'draft_user' ) );
		$this->assertTrue( bb_draft_is_draft_meta_key( 'draft_user_5' ) );
		$this->assertTrue( bb_draft_is_draft_meta_key( 'draft_group_12' ) );
		$this->assertTrue( bb_draft_is_draft_meta_key( 'bb_user_topic_reply_draft' ) );

		// Third-party keys sharing the prefix must never match (PROD-9621 R2-M2).
		$this->assertFalse( bb_draft_is_draft_meta_key( 'draft_custom_thing' ) );
		$this->assertFalse( bb_draft_is_draft_meta_key( 'draft_group_extra' ) );
		$this->assertFalse( bb_draft_is_draft_meta_key( 'draft_userdata' ) );
		$this->assertFalse( bb_draft_is_draft_meta_key( 'draft_group_' ) );
		$this->assertFalse( bb_draft_is_draft_meta_key( 'draft_group_12abc' ) );
		$this->assertFalse( bb_draft_is_draft_meta_key( 'session_tokens' ) );
	}

	public function test_strip_data_urls_removes_multi_megabyte_payload_and_keeps_text() {
		$content  = 'Text before <img src="data:image/png;base64,' . str_repeat( 'A', 1600000 ) . '"> and after.';
		$stripped = bb_draft_strip_data_urls( $content );

		$this->assertStringContainsString( 'Text before', $stripped );
		$this->assertStringContainsString( 'and after.', $stripped );
		$this->assertStringNotContainsString( 'data:image', $stripped );
		$this->assertLessThan( 200, strlen( $stripped ) );
	}

	public function test_strip_data_urls_leaves_normal_content_untouched() {
		$plain = 'No images here at all';
		$this->assertSame( $plain, bb_draft_strip_data_urls( $plain ) );

		$normal_img = 'Look: <img src="https://example.com/pic.png" alt="x"> done';
		$this->assertSame( $normal_img, bb_draft_strip_data_urls( $normal_img ) );
	}

	public function test_strip_data_urls_spares_normal_images_when_a_data_url_is_present() {
		// The fixture MUST contain a data: URL so the removal regexes actually
		// run - a data-free fixture early-returns and proves nothing.
		$mixed = 'A <img src="data:image/png;base64,QUJD"> B <img src="https://example.com/pic.png" alt="keep"> C <img class="emojioneemoji" src="https://cdn/emoji.png" data-emoji-char="x"> D data: mentioned in text.';

		$stripped = bb_draft_strip_data_urls( $mixed );

		$this->assertStringNotContainsString( 'base64', $stripped );
		$this->assertStringContainsString( 'https://example.com/pic.png', $stripped, 'A normal linked image must survive the strip.' );
		$this->assertStringContainsString( 'emojioneemoji', $stripped, 'Emoji images must survive the strip.' );
		$this->assertStringContainsString( 'data: mentioned in text.', $stripped );
	}

	public function test_attachment_ownership_gate() {
		$owner    = self::factory()->user->create();
		$stranger = self::factory()->user->create();

		$attachment_id = self::factory()->attachment->create( array( 'post_author' => $owner ) );
		$post_id       = self::factory()->post->create( array( 'post_author' => $owner ) );

		$this->assertTrue( bb_draft_user_can_manage_attachment( $attachment_id, $owner ) );

		// Another member's attachment must be untouchable (PROD-9621 RF-1).
		$this->assertFalse( bb_draft_user_can_manage_attachment( $attachment_id, $stranger ) );

		// Non-attachment posts and invalid IDs never qualify.
		$this->assertFalse( bb_draft_user_can_manage_attachment( $post_id, $owner ) );
		$this->assertFalse( bb_draft_user_can_manage_attachment( 0, $owner ) );
		$this->assertFalse( bb_draft_user_can_manage_attachment( 999999999, $owner ) );
	}

	public function test_activity_data_key_validation() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		// Own-feed draft.
		$this->assertTrue( bb_draft_validate_activity_data_key( 'draft_user', 'user', 0, $u1 ) );

		// Composing on ANOTHER member's profile is a legitimate flow; the key
		// lives under the saving user's own meta (PROD-9621 RF-6).
		$this->assertTrue( bb_draft_validate_activity_data_key( 'draft_user_' . $u2, 'user', 0, $u1 ) );

		// Nonexistent member suffix, forged shapes, arbitrary keys: rejected.
		$this->assertFalse( bb_draft_validate_activity_data_key( 'draft_user_999999999', 'user', 0, $u1 ) );
		$this->assertFalse( bb_draft_validate_activity_data_key( 'session_tokens', 'user', 0, $u1 ) );
		$this->assertFalse( bb_draft_validate_activity_data_key( 'draft_userx', 'user', 0, $u1 ) );
	}

	public function test_activity_data_key_validation_for_groups() {
		if ( ! bp_is_active( 'groups' ) ) {
			$this->markTestSkipped( 'Groups component is not active.' );
		}

		$member     = self::factory()->user->create();
		$non_member = self::factory()->user->create();
		$group_id   = self::factory()->group->create( array( 'creator_id' => $member ) );

		$this->assertTrue( bb_draft_validate_activity_data_key( 'draft_group_' . $group_id, 'group', $group_id, $member ) );

		// Non-members, mismatched item ids, and unknown groups: rejected.
		$this->assertFalse( bb_draft_validate_activity_data_key( 'draft_group_' . $group_id, 'group', $group_id, $non_member ) );
		$this->assertFalse( bb_draft_validate_activity_data_key( 'draft_group_' . $group_id, 'group', $group_id + 1, $member ) );
		$this->assertFalse( bb_draft_validate_activity_data_key( 'draft_group_999999999', 'group', 999999999, $member ) );
	}

	public function test_topic_reply_data_key_validation() {
		if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
			$this->markTestSkipped( 'Forums component is not loaded.' );
		}

		$forum_id = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$topic_id = self::factory()->post->create( array( 'post_type' => bbp_get_topic_post_type() ) );
		$reply_id = self::factory()->post->create( array( 'post_type' => bbp_get_reply_post_type() ) );

		// The five shapes the forum composer builds.
		$this->assertTrue( bb_draft_validate_topic_reply_data_key( 'draft_topic' ) );
		$this->assertTrue( bb_draft_validate_topic_reply_data_key( 'draft_reply' ) );
		$this->assertTrue( bb_draft_validate_topic_reply_data_key( 'draft_discussion_' . $forum_id ) );
		$this->assertTrue( bb_draft_validate_topic_reply_data_key( 'draft_reply_' . $topic_id ) );
		$this->assertTrue( bb_draft_validate_topic_reply_data_key( 'draft_reply_' . $topic_id . '_' . $reply_id ) );

		// Wrong post types and unknown IDs: rejected.
		$this->assertFalse( bb_draft_validate_topic_reply_data_key( 'draft_discussion_' . $topic_id ) );
		$this->assertFalse( bb_draft_validate_topic_reply_data_key( 'draft_reply_' . $forum_id ) );
		$this->assertFalse( bb_draft_validate_topic_reply_data_key( 'draft_reply_' . $topic_id . '_' . $forum_id ) );
		$this->assertFalse( bb_draft_validate_topic_reply_data_key( 'draft_discussion_999999999' ) );
		$this->assertFalse( bb_draft_validate_topic_reply_data_key( 'topic_' . $topic_id ) );
	}

	public function test_budget_eviction_removes_oldest_and_spares_third_party_meta() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta(
			$user_id,
			'draft_group_1',
			array(
				'data_key'        => 'draft_group_1',
				'data'            => array( 'content' => str_repeat( 'a', 200 ) ),
				'_draft_saved_at' => 100,
			)
		);
		bp_update_user_meta(
			$user_id,
			'draft_group_2',
			array(
				'data_key'        => 'draft_group_2',
				'data'            => array( 'content' => str_repeat( 'b', 200 ) ),
				'_draft_saved_at' => 200,
			)
		);

		// Third-party meta sharing the prefix must survive every sweep.
		bp_update_user_meta( $user_id, 'draft_custom_thing', str_repeat( 'z', 200 ) );

		// Compute the fixture sizes independently and set the cap so that
		// exactly ONE eviction (the oldest draft) makes the new save fit.
		$size_newest = strlen( maybe_serialize( bp_get_user_meta( $user_id, 'draft_group_2', true ) ) );
		$new_size    = 400;
		$total_cap   = $size_newest + $new_size;

		add_filter(
			'bb_draft_user_total_max_size',
			function () use ( $total_cap ) {
				return $total_cap;
			}
		);

		$result = bb_draft_enforce_user_budget( $user_id, 'draft_user', $new_size );

		$this->assertTrue( $result['allowed'] );
		$this->assertSame( array( 'draft_group_1' ), $result['evicted'], 'Only the OLDEST draft may be evicted.' );

		// Assert stored state directly - the independent source of truth.
		$this->assertFalse( metadata_exists( 'user', $user_id, 'draft_group_1' ), 'Evicted draft row must be deleted.' );
		$this->assertTrue( metadata_exists( 'user', $user_id, 'draft_group_2' ), 'Newer draft must survive.' );
		$this->assertTrue( metadata_exists( 'user', $user_id, 'draft_custom_thing' ), 'Third-party draft_* meta must never be touched.' );
	}

	public function test_meta_budget_refuses_save_for_heavy_meta_user() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta( $user_id, 'unrelated_heavy_meta', str_repeat( 'x', 2000 ) );

		add_filter(
			'bb_draft_user_meta_budget',
			function () {
				return 1000;
			}
		);

		$result = bb_draft_enforce_user_budget( $user_id, 'draft_user', 500 );

		$this->assertFalse( $result['allowed'], 'Save must be refused when total user meta would exceed the platform budget.' );
		$this->assertSame( array(), $result['evicted'], 'A refusal must not evict anything.' );
	}

	public function test_meta_budget_allows_save_for_light_meta_user() {
		// Negative control for the refusal test: without heavy meta the same
		// save passes under the same filtered budget.
		$user_id = self::factory()->user->create();

		add_filter(
			'bb_draft_user_meta_budget',
			function () {
				return 1000000;
			}
		);

		$result = bb_draft_enforce_user_budget( $user_id, 'draft_user', 500 );

		$this->assertTrue( $result['allowed'] );
	}

	public function test_dispose_unstamps_owned_attachments_and_deletes_row() {
		$user_id       = self::factory()->user->create();
		$attachment_id = self::factory()->attachment->create( array( 'post_author' => $user_id ) );

		update_post_meta( $attachment_id, 'bb_media_draft', 1 );

		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key' => 'draft_user',
				'data'     => array(
					'content' => 'x',
					'media'   => array( array( 'id' => $attachment_id ) ),
				),
			)
		);

		$this->assertTrue( bb_draft_dispose( $user_id, 'draft_user' ) );

		$this->assertFalse( metadata_exists( 'user', $user_id, 'draft_user' ) );
		$this->assertEmpty( get_post_meta( $attachment_id, 'bb_media_draft', true ), 'Disposal must release the bb_media_draft stamp.' );
	}

	public function test_dispose_inner_forum_draft_rewrites_row_and_deletes_when_empty() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_topic'   => array( 'data' => array( 'bbp_topic_content' => 'one' ) ),
				'draft_reply_5' => array( 'data' => array( 'bbp_reply_content' => 'two' ) ),
			)
		);

		$this->assertTrue( bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', 'draft_topic' ) );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertIsArray( $row );
		$this->assertArrayNotHasKey( 'draft_topic', $row );
		$this->assertArrayHasKey( 'draft_reply_5', $row, 'The sibling inner draft must survive.' );

		// Removing the last inner draft deletes the row instead of storing array() (RF-7).
		$this->assertTrue( bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', 'draft_reply_5' ) );
		$this->assertFalse( metadata_exists( 'user', $user_id, 'bb_user_topic_reply_draft' ) );
	}

	public function test_dispose_rejects_non_draft_keys() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta( $user_id, 'draft_custom_thing', array( 'data' => array() ) );

		$this->assertFalse( bb_draft_dispose( $user_id, 'draft_custom_thing' ) );
		$this->assertTrue( metadata_exists( 'user', $user_id, 'draft_custom_thing' ) );
	}

	public function test_manage_context_validates_shape_only() {
		$user_id = self::factory()->user->create();

		// A slim delete carries no data member: group ID unavailable, and the
		// member may have left the group - shape-only validation must accept.
		$this->assertTrue( bb_draft_validate_activity_data_key( 'draft_group_12345', 'group', 0, $user_id, 'manage' ) );
		$this->assertTrue( bb_draft_validate_activity_data_key( 'draft_user', 'user', 0, $user_id, 'manage' ) );
		$this->assertTrue( bb_draft_validate_activity_data_key( 'draft_user_999999', 'user', 0, $user_id, 'manage' ) );

		// Forged shapes stay rejected even in manage context.
		$this->assertFalse( bb_draft_validate_activity_data_key( 'session_tokens', 'user', 0, $user_id, 'manage' ) );
		$this->assertFalse( bb_draft_validate_activity_data_key( 'draft_group_x', 'group', 0, $user_id, 'manage' ) );

		// Negative control: the SAVE context still enforces membership.
		$this->assertFalse( bb_draft_validate_activity_data_key( 'draft_group_12345', 'group', 12345, $user_id, 'save' ) );
	}

	public function test_heal_context_bypasses_the_meta_budget_refusal() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta( $user_id, 'unrelated_heavy_meta', str_repeat( 'x', 5000 ) );
		bp_update_user_meta(
			$user_id,
			'draft_group_1',
			array(
				'data_key'        => 'draft_group_1',
				'data'            => array( 'content' => str_repeat( 'a', 2000 ) ),
				'_draft_saved_at' => 100,
			)
		);
		bp_update_user_meta(
			$user_id,
			'draft_group_2',
			array(
				'data_key'        => 'draft_group_2',
				'data'            => array( 'content' => str_repeat( 'b', 2000 ) ),
				'_draft_saved_at' => 200,
			)
		);

		add_filter(
			'bb_draft_user_meta_budget',
			function () {
				return 4000; // The user's total meta (7000+) exceeds this.
			}
		);
		add_filter(
			'bb_draft_user_total_max_size',
			function () {
				return 2500; // Combined drafts (4000+) exceed this.
			}
		);

		// The save context refuses this user outright (negative control)...
		$save = bb_draft_enforce_user_budget( $user_id, 'draft_user', 100, 'save' );
		$this->assertFalse( $save['allowed'] );
		$this->assertSame( array(), $save['evicted'] );

		// ...which is exactly why the HEAL context must not: it exists for
		// users over the refusal threshold. Oldest draft evicted, budget met.
		$heal = bb_draft_enforce_user_budget( $user_id, '', 0, 'heal' );
		$this->assertTrue( $heal['allowed'] );
		$this->assertSame( array( 'draft_group_1' ), $heal['evicted'] );
		$this->assertFalse( metadata_exists( 'user', $user_id, 'draft_group_1' ) );
		$this->assertTrue( metadata_exists( 'user', $user_id, 'draft_group_2' ) );
	}

	public function test_forum_row_heals_at_inner_granularity_not_wholesale() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_topic'    => array(
					'data'            => array( 'bbp_topic_content' => str_repeat( 'a', 3000 ) ),
					'_draft_saved_at' => 100,
				),
				'draft_reply_5'  => array(
					'data'            => array( 'bbp_reply_content' => str_repeat( 'b', 300 ) ),
					'_draft_saved_at' => 200,
				),
			)
		);

		add_filter(
			'bb_draft_max_size',
			function () {
				return 1000; // draft_topic (3000+) exceeds; draft_reply_5 does not.
			}
		);

		bb_draft_heal_forum_row( $user_id );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertIsArray( $row, 'The row must survive healing - never disposed wholesale.' );
		$this->assertArrayNotHasKey( 'draft_topic', $row, 'The oversized inner draft is disposed.' );
		$this->assertArrayHasKey( 'draft_reply_5', $row, 'The legal sibling inner draft survives.' );
	}

	public function test_batch_scan_survives_a_window_of_third_party_rows() {
		global $wpdb;

		$user_id = self::factory()->user->create();

		// Five third-party lookalikes inserted FIRST (lower umeta_id)...
		for ( $i = 1; $i <= 5; $i++ ) {
			add_user_meta( $user_id, 'draft_user_notes' . $i, 'third-party ' . $i );
		}
		// ...then one real BuddyBoss draft row after them.
		add_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key' => 'draft_user',
				'data'     => array( 'content' => 'real draft' ),
			)
		);

		// A window consisting entirely of filtered third-party rows must not
		// end the scan: the raw cursor advances and has_more stays true.
		$first = bb_draft_get_rows_batch( 0, 5, false );
		$this->assertSame( array(), $first['rows'] );
		$this->assertTrue( $first['has_more'] );
		$this->assertGreaterThan( 0, $first['last_id'] );

		$second = bb_draft_get_rows_batch( $first['last_id'], 5, false );
		$found  = wp_list_pluck( $second['rows'], 'meta_key' );
		$this->assertContains( 'draft_user', $found, 'The real draft past the filtered window must still be reached.' );
	}

	public function test_dispose_removes_corrupt_and_legacy_empty_rows() {
		$user_id = self::factory()->user->create();

		// A corrupt (non-array) row - e.g. a serialized value truncated
		// mid-write - is exactly the oversized shape healing must remove.
		add_user_meta( $user_id, 'draft_user', 'corrupt-not-an-array' );
		$this->assertTrue( bb_draft_dispose( $user_id, 'draft_user' ) );
		$this->assertFalse( metadata_exists( 'user', $user_id, 'draft_user' ) );

		// A legacy-empty array() row (old forum publish paths) is removable too.
		add_user_meta( $user_id, 'bb_user_topic_reply_draft', array() );
		$this->assertTrue( bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft' ) );
		$this->assertFalse( metadata_exists( 'user', $user_id, 'bb_user_topic_reply_draft' ) );

		// A missing row still reports failure - nothing was removed.
		$this->assertFalse( bb_draft_dispose( $user_id, 'draft_user' ) );

		// An inner-key request on a corrupt row has nothing addressable.
		add_user_meta( $user_id, 'bb_user_topic_reply_draft', 'corrupt' );
		$this->assertFalse( bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', 'draft_topic' ) );
	}

	public function test_cleanup_collects_legacy_empty_forum_row_and_spares_fresh_drafts() {
		$user_id = self::factory()->user->create();

		add_user_meta( $user_id, 'bb_user_topic_reply_draft', array() );
		add_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => 'fresh' ),
				'_draft_saved_at' => time(),
			)
		);

		$result = bb_drafts_delete_expired( 0 );

		$this->assertTrue( $result['complete'] );
		$this->assertFalse( metadata_exists( 'user', $user_id, 'bb_user_topic_reply_draft' ), 'A legacy-empty aggregate row is collected immediately.' );
		$this->assertTrue( metadata_exists( 'user', $user_id, 'draft_user' ), 'A fresh draft survives the sweep (negative control).' );
	}

	public function test_cleanup_resumes_from_persisted_cursor_and_clears_it_on_completion() {
		global $wpdb;

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$expired = array(
			'data_key'        => 'draft_user',
			'data'            => array( 'content' => 'old' ),
			'_draft_saved_at' => 100,
		);

		add_user_meta( $u1, 'draft_user', $expired );
		add_user_meta( $u2, 'draft_user', $expired );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$u1_row_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = 'draft_user'", $u1 ) );

		// Simulate a budget-interrupted previous slice that stopped after u1's row.
		update_option( 'bb_draft_cleanup_cursor', $u1_row_id, false );

		$result = bb_drafts_delete_expired( 0 );

		$this->assertTrue( $result['complete'] );
		$this->assertTrue( metadata_exists( 'user', $u1, 'draft_user' ), 'Rows before the persisted cursor were handled by the interrupted slice - not re-scanned.' );
		$this->assertFalse( metadata_exists( 'user', $u2, 'draft_user' ), 'Rows after the cursor are reached by the resumed slice.' );
		$this->assertFalse( get_option( 'bb_draft_cleanup_cursor' ), 'A completed pass clears the cursor so the next daily run starts fresh.' );
	}

	public function test_oneshot_heals_aggregate_oversized_user_and_disposes_corrupt_rows() {
		$heavy   = self::factory()->user->create();
		$light   = self::factory()->user->create();
		$corrupt = self::factory()->user->create();

		// Six ~60KB drafts: none individually over the 100KB per-draft cap,
		// combined ~360KB over the 300KB per-user budget - the "ten 90KB
		// legacy drafts" class the aggregate stage exists for.
		for ( $i = 1; $i <= 6; $i++ ) {
			add_user_meta(
				$heavy,
				'draft_group_' . $i,
				array(
					'data_key'        => 'draft_group_' . $i,
					'data'            => array( 'content' => str_repeat( 'x', 60000 ) ),
					'_draft_saved_at' => $i,
				)
			);
		}

		add_user_meta(
			$light,
			'draft_user',
			array(
				'data_key' => 'draft_user',
				'data'     => array( 'content' => 'small' ),
			)
		);

		// A corrupt scalar row over the per-draft cap - the truncated
		// multi-MB shape the one-shot must be able to remove.
		add_user_meta( $corrupt, 'draft_user', str_repeat( 'x', 200000 ) );

		$result = bb_drafts_oneshot_batch( 0 );

		$this->assertTrue( $result['complete'] );
		$this->assertSame( 1, (int) get_option( 'bb_draft_oneshot_done' ) );
		$this->assertFalse( get_option( 'bb_draft_oneshot_state' ), 'The persisted stage state is cleared on completion.' );

		$this->assertFalse( metadata_exists( 'user', $corrupt, 'draft_user' ), 'A corrupt oversized row is disposed.' );

		$heavy_sizes = bb_draft_get_user_meta_sizes( $heavy );
		$this->assertLessThanOrEqual( bb_draft_user_total_max_size(), array_sum( $heavy_sizes['drafts'] ), 'The aggregate-oversized user is healed at upgrade, not on their next save.' );
		$this->assertFalse( metadata_exists( 'user', $heavy, 'draft_group_1' ), 'The OLDEST draft is the one evicted.' );
		$this->assertTrue( metadata_exists( 'user', $heavy, 'draft_group_6' ), 'Newer drafts survive - healing is not wholesale deletion.' );

		$this->assertTrue( metadata_exists( 'user', $light, 'draft_user' ), 'An under-budget user is untouched (negative control).' );
	}
}
