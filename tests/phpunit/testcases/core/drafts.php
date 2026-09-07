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

	/**
	 * Retention below one day means "never expire", not "expire everything".
	 *
	 * Filtering the window to 0 is the obvious way to switch expiry off; if
	 * that were taken literally the cutoff would land at the current time and
	 * the next cleanup run would delete every draft on the site.
	 */
	public function test_retention_days_treats_zero_as_disabled() {
		$this->assertSame( 30, bb_draft_retention_days(), 'Default retention window.' );

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_retention_zero' ) );
		$this->assertSame( 0, bb_draft_retention_days() );
		$this->assertSame( 0, bb_draft_retention_seconds() );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_retention_zero' ) );

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_retention_negative' ) );
		$this->assertSame( 0, bb_draft_retention_days(), 'A negative window is disabled, never a negative cutoff.' );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_retention_negative' ) );
	}

	public function filter_retention_zero() {
		return 0;
	}

	public function filter_retention_negative() {
		return -5;
	}

	public function test_cleanup_deletes_nothing_when_retention_is_disabled() {
		$user_id = self::factory()->user->create();

		// Ancient by any measure - this row expires under the default window.
		add_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => 'ancient' ),
				'_draft_saved_at' => 100,
			)
		);

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_retention_zero' ) );
		$disabled = bb_drafts_delete_expired( 0 );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_retention_zero' ) );

		$this->assertSame( 0, $disabled['deleted'] );
		$this->assertTrue( metadata_exists( 'user', $user_id, 'draft_user' ), 'Expiry switched off must delete nothing.' );

		// Negative control: the SAME fixture must be collected once expiry is on,
		// otherwise the assertion above would pass for the wrong reason.
		$enabled = bb_drafts_delete_expired( 0 );

		$this->assertSame( 1, $enabled['deleted'] );
		$this->assertFalse( metadata_exists( 'user', $user_id, 'draft_user' ) );
	}

	/**
	 * The forum handler's discard path must release its attachment stamps.
	 *
	 * The block that was meant to do this read one nesting level too shallow
	 * ($entry['bbp_media'] rather than $entry['data']['bbp_media']), so it was
	 * dead code and every discarded forum draft pinned its attachments out of
	 * orphan cleanup forever.
	 */
	public function test_unstamp_releases_forum_entry_attachments_at_the_data_nesting_level() {
		$user_id       = self::factory()->user->create();
		$attachment_id = self::factory()->attachment->create( array( 'post_author' => $user_id ) );

		update_post_meta( $attachment_id, 'bb_media_draft', 1 );

		$entry = array(
			'data_key'        => 'draft_reply',
			'data'            => array(
				'bbp_media' => wp_json_encode( array( array( 'id' => $attachment_id ) ) ),
			),
			'_draft_saved_at' => time(),
		);

		bb_draft_unstamp_attachments( $entry, $user_id );

		$this->assertSame( '', (string) get_post_meta( $attachment_id, 'bb_media_draft', true ), 'The stamp must be released so the orphan cron can reclaim the file.' );

		// Negative control: a shallow entry (the shape the dead code expected)
		// carries no attachment list, so nothing is collected from it.
		$other_attachment = self::factory()->attachment->create( array( 'post_author' => $user_id ) );
		update_post_meta( $other_attachment, 'bb_media_draft', 1 );

		bb_draft_unstamp_attachments( array( 'bbp_media' => wp_json_encode( array( array( 'id' => $other_attachment ) ) ) ), $user_id );

		$this->assertSame( '1', (string) get_post_meta( $other_attachment, 'bb_media_draft', true ), 'Attachment lists are only read from the entry data key.' );
	}

	/**
	 * The composer's has_draft probe must resolve the key the writers used.
	 *
	 * Writers store through bp_update_user_meta(), which passes the key through
	 * bp_get_user_meta_key(). A probe on the raw literal reports "no draft" on
	 * any install that filters that key, so the lazy fetch never fires and the
	 * member is never offered their stored draft.
	 */
	public function test_has_draft_probe_resolves_the_filtered_meta_key() {
		if ( ! bp_is_active( 'forums' ) ) {
			$this->markTestSkipped( 'Forums component inactive.' );
		}

		$user_id  = self::factory()->user->create();
		$old_user = get_current_user_id();
		$this->set_current_user( $user_id );

		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );

		// Stored exactly the way every draft writer stores it.
		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_reply' => array(
					'data_key'        => 'draft_reply',
					'data'            => array( 'bbp_reply_content' => 'stored' ),
					'_draft_saved_at' => time(),
				),
			)
		);

		// Exercise the REAL localize function, not a restatement of the probe.
		$params = bb_nouveau_forum_localize_scripts( array() );

		remove_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );
		$this->set_current_user( $old_user );

		$this->assertTrue(
			$params['forums']['has_draft'],
			'has_draft must report the draft the writers actually stored; a raw-literal probe reports false here and the member is never offered their draft.'
		);
	}

	/**
	 * Replacing a draft must not strip protection from attachments it keeps.
	 *
	 * A restored draft re-sends its stored attachment list verbatim, so the
	 * kept attachments appear in BOTH the replaced and the replacing entry.
	 * Unstamping the whole replaced entry leaves them unprotected with
	 * bp_media_saved = 0, and bp_media_delete_orphaned_attachments() then
	 * hard-deletes a file the stored draft still references (PROD-9621 N1).
	 */
	public function test_replacing_a_draft_keeps_stamps_on_attachments_it_still_holds() {
		$user_id = self::factory()->user->create();

		$kept    = self::factory()->attachment->create( array( 'post_author' => $user_id ) );
		$dropped = self::factory()->attachment->create( array( 'post_author' => $user_id ) );

		update_post_meta( $kept, 'bb_media_draft', 1 );
		update_post_meta( $dropped, 'bb_media_draft', 1 );

		$previous = array(
			'data_key' => 'draft_reply',
			'data'     => array(
				'bbp_media' => wp_json_encode(
					array(
						array( 'id' => $kept, 'bb_media_draft' => 1 ),
						array( 'id' => $dropped, 'bb_media_draft' => 1 ),
					)
				),
			),
		);

		// The replacing entry keeps $kept and carries the stored flag back, which
		// is exactly the shape a flag-gated stamp would refuse to re-apply.
		$current = array(
			'data_key' => 'draft_reply',
			'data'     => array(
				'bbp_media' => wp_json_encode( array( array( 'id' => $kept, 'bb_media_draft' => 1 ) ) ),
			),
		);

		$released = bb_draft_release_replaced_attachments( $previous, $current, $user_id );

		$this->assertSame( array( $dropped ), $released, 'Only the attachment the new entry dropped may be released.' );
		$this->assertSame( '1', (string) get_post_meta( $kept, 'bb_media_draft', true ), 'A kept attachment must stay protected, or the orphan cron deletes a file the draft still references.' );
		$this->assertSame( '', (string) get_post_meta( $dropped, 'bb_media_draft', true ), 'A dropped attachment must be released so the orphan cron can reclaim it.' );
	}

	/**
	 * A discard carries no data member, so every attachment is released.
	 */
	public function test_discarding_a_draft_releases_every_attachment_it_held() {
		$user_id       = self::factory()->user->create();
		$attachment_id = self::factory()->attachment->create( array( 'post_author' => $user_id ) );

		update_post_meta( $attachment_id, 'bb_media_draft', 1 );

		$previous = array(
			'data_key' => 'draft_reply',
			'data'     => array( 'bbp_media' => wp_json_encode( array( array( 'id' => $attachment_id ) ) ) ),
		);

		// The slim delete payload the client sends: data omitted entirely.
		$released = bb_draft_release_replaced_attachments( $previous, array( 'data_key' => 'draft_reply' ), $user_id );

		$this->assertSame( array( $attachment_id ), $released );
		$this->assertSame( '', (string) get_post_meta( $attachment_id, 'bb_media_draft', true ) );
	}

	/**
	 * Attachments the acting member does not own are never touched.
	 */
	public function test_release_never_touches_a_foreign_attachment() {
		$user_id   = self::factory()->user->create();
		$other_id  = self::factory()->user->create();
		$foreign   = self::factory()->attachment->create( array( 'post_author' => $other_id ) );

		update_post_meta( $foreign, 'bb_media_draft', 1 );

		$previous = array(
			'data_key' => 'draft_reply',
			'data'     => array( 'bbp_media' => wp_json_encode( array( array( 'id' => $foreign ) ) ) ),
		);

		$released = bb_draft_release_replaced_attachments( $previous, array(), $user_id );

		$this->assertSame( array(), $released );
		$this->assertSame( '1', (string) get_post_meta( $foreign, 'bb_media_draft', true ) );
	}

	/**
	 * A filtered user-meta key must still be recognised as a draft key.
	 *
	 * Writers store through bp_update_user_meta(), so on any install that
	 * filters bp_get_user_meta_key the stored key is not the literal one. The
	 * caps, the expiry cron and the healing pass all read raw keys back out of
	 * the table, so they need the inverse of that filter (PROD-9621 N2).
	 */
	public function test_logical_meta_key_inverts_the_user_meta_key_filter() {
		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );

		$this->assertSame( 'draft_user', bb_draft_logical_meta_key( 'bbtest_draft_user' ) );
		$this->assertSame( 'draft_group_12', bb_draft_logical_meta_key( 'bbtest_draft_group_12' ) );
		$this->assertSame( 'bb_user_topic_reply_draft', bb_draft_logical_meta_key( 'bbtest_bb_user_topic_reply_draft' ) );

		// An unprefixed row is NOT ours on this install - acting on it would
		// re-filter the key onto a different, live row.
		$this->assertSame( '', bb_draft_logical_meta_key( 'draft_user' ) );
		// Third-party keys still never match, prefixed or not.
		$this->assertSame( '', bb_draft_logical_meta_key( 'bbtest_draft_userdata' ) );
		$this->assertSame( '', bb_draft_logical_meta_key( 'bbtest_draft_group_extra' ) );

		remove_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );

		// Unfiltered install: identity.
		$this->assertSame( 'draft_user', bb_draft_logical_meta_key( 'draft_user' ) );
		$this->assertSame( '', bb_draft_logical_meta_key( 'bbtest_draft_user' ) );
	}

	/**
	 * The size measurement must see drafts stored under a filtered key.
	 *
	 * Measuring raw literals reports zero draft bytes on a filtered install,
	 * so no per-user cap can ever fire there (PROD-9621 N2).
	 */
	public function test_meta_sizes_measure_drafts_stored_under_a_filtered_key() {
		$user_id = self::factory()->user->create();

		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );

		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => str_repeat( 'a', 500 ) ),
				'_draft_saved_at' => time(),
			)
		);

		bb_draft_flush_user_meta_sizes( $user_id );
		$sizes = bb_draft_get_user_meta_sizes( $user_id );

		remove_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );
		bb_draft_flush_user_meta_sizes( $user_id );

		$this->assertArrayHasKey( 'draft_user', $sizes['drafts'], 'Sizes must be keyed by the logical key the writers asked for.' );
		$this->assertGreaterThan( 500, $sizes['drafts']['draft_user'] );
	}

	/**
	 * The expiry sweep must collect a filtered row and leave a stray raw one.
	 *
	 * Matching raw literals made the sweep hand bb_draft_dispose() an
	 * unfiltered key, which re-filtered onto the member's LIVE draft and
	 * deleted that instead - while the row actually found survived
	 * (PROD-9621 N2).
	 */
	public function test_expiry_sweep_acts_on_the_stored_key_not_a_raw_lookalike() {
		$user_id = self::factory()->user->create();

		// A stray unfiltered row a migration could have left behind.
		update_user_meta( $user_id, 'draft_user', 'STRAY-RAW-ROW' );

		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );

		// The member's real draft, expired, stored the way every writer stores.
		bp_update_user_meta(
			$user_id,
			'draft_group_77',
			array(
				'data_key'        => 'draft_group_77',
				'data'            => array( 'content' => 'expired group draft' ),
				'_draft_saved_at' => 100,
			)
		);

		// And a fresh one that must survive the sweep untouched.
		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => 'fresh draft' ),
				'_draft_saved_at' => time(),
			)
		);

		delete_option( 'bb_draft_cleanup_cursor' );
		bb_drafts_delete_expired( 0 );

		$expired = bp_get_user_meta( $user_id, 'draft_group_77', true );
		$fresh   = bp_get_user_meta( $user_id, 'draft_user', true );

		remove_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );

		$stray = get_user_meta( $user_id, 'draft_user', true );

		$this->assertSame( '', $expired, 'The expired filtered draft must be collected.' );
		$this->assertNotEmpty( $fresh, 'A fresh draft must survive - deleting it is the wrong-row bug.' );
		$this->assertSame( 'STRAY-RAW-ROW', $stray, 'An unfiltered lookalike row is not ours on this install and must be left alone.' );
	}

	/**
	 * A key filter that cannot be inverted must stop the sweep, not guess.
	 */
	public function test_non_invertible_key_filter_disables_the_sweep() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => 'expired' ),
				'_draft_saved_at' => 100,
			)
		);

		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_hash_user_meta_key' ) );

		$wrap  = bb_draft_meta_key_wrap();
		$batch = bb_draft_get_rows_batch( 0, 200, false );

		remove_filter( 'bp_get_user_meta_key', array( $this, 'filter_hash_user_meta_key' ) );

		$this->assertFalse( $wrap['invertible'], 'A hashing filter is not a wrap and must be reported as non-invertible.' );
		$this->assertSame( array(), $batch['rows'], 'Without an invertible wrap the sweep must return nothing rather than delete on a guess.' );
		$this->assertFalse( $batch['has_more'] );
		$this->assertNotEmpty( get_user_meta( $user_id, 'draft_user', true ), 'Nothing may be removed while keys cannot be attributed.' );
	}

	/**
	 * The activity composer's discard now delegates to bb_draft_dispose().
	 *
	 * The discard path used to hand-roll unstamp + delete and skipped the size
	 * memo flush, so it could drift from the maintenance routes it is supposed
	 * to match. This asserts the delegate does all three things for an activity
	 * draft shape - media, feature image, row, memo (PROD-9621 N3).
	 */
	public function test_dispose_handles_the_full_activity_draft_shape() {
		$user_id = self::factory()->user->create();

		$media_id   = self::factory()->attachment->create( array( 'post_author' => $user_id ) );
		$feature_id = self::factory()->attachment->create( array( 'post_author' => $user_id ) );

		update_post_meta( $media_id, 'bb_media_draft', 1 );
		update_post_meta( $feature_id, 'bb_activity_post_feature_image_draft', 1 );

		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array(
					'content'                        => str_repeat( 'a', 400 ),
					'media'                          => array( array( 'id' => $media_id ) ),
					'bb_activity_post_feature_image' => array( 'id' => $feature_id ),
				),
				'_draft_saved_at' => time(),
			)
		);

		$before = bb_draft_get_user_meta_sizes( $user_id );
		$this->assertArrayHasKey( 'draft_user', $before['drafts'], 'Fixture must be measurable, or the test proves nothing.' );

		$this->assertTrue( bb_draft_dispose( $user_id, 'draft_user' ) );

		$this->assertSame( '', (string) bp_get_user_meta( $user_id, 'draft_user', true ), 'The row must be gone.' );
		$this->assertSame( '', (string) get_post_meta( $media_id, 'bb_media_draft', true ), 'Media stamp must be released.' );
		$this->assertSame( '', (string) get_post_meta( $feature_id, 'bb_activity_post_feature_image_draft', true ), 'Feature image stamp must be released.' );

		$after = bb_draft_get_user_meta_sizes( $user_id );
		$this->assertArrayNotHasKey( 'draft_user', $after['drafts'], 'The memoized sizes must be invalidated.' );
	}

	/**
	 * The draft key must resolve to the object and forum it addresses.
	 */
	public function test_topic_reply_key_context_resolves_object_and_forum() {
		if ( ! bp_is_active( 'forums' ) ) {
			$this->markTestSkipped( 'Forums component inactive.' );
		}

		$forum_id = $this->factory->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$topic_id = $this->factory->post->create(
			array(
				'post_type'   => bbp_get_topic_post_type(),
				'post_parent' => $forum_id,
			)
		);
		update_post_meta( $topic_id, '_bbp_forum_id', $forum_id );

		$bare_topic = bb_draft_topic_reply_key_context( 'draft_topic' );
		$this->assertSame( 'topic', $bare_topic['object'] );
		$this->assertSame( 0, $bare_topic['forum_id'], 'A bare shape carries no forum, so no forum gate applies.' );

		$discussion = bb_draft_topic_reply_key_context( 'draft_discussion_' . $forum_id );
		$this->assertSame( 'topic', $discussion['object'] );
		$this->assertSame( $forum_id, $discussion['forum_id'] );

		$reply = bb_draft_topic_reply_key_context( 'draft_reply_' . $topic_id );
		$this->assertSame( 'reply', $reply['object'] );
		$this->assertSame( $topic_id, $reply['topic_id'] );
		$this->assertSame( $forum_id, $reply['forum_id'], 'A reply draft belongs to whichever forum owns its topic.' );

		// Shapes that address nothing real stay rejected.
		$this->assertFalse( bb_draft_topic_reply_key_context( 'draft_discussion_999999' ) );
		$this->assertFalse( bb_draft_topic_reply_key_context( 'draft_nonsense' ) );
		$this->assertFalse( bb_draft_topic_reply_key_context( 'draft_discussion_' . $topic_id ), 'A topic ID in a forum slot is not a forum.' );

		// The bool wrapper keeps its contract.
		$this->assertTrue( bb_draft_validate_topic_reply_data_key( 'draft_reply_' . $topic_id ) );
		$this->assertFalse( bb_draft_validate_topic_reply_data_key( 'draft_nonsense' ) );
	}

	/**
	 * Saving a topic draft must need the TOPIC publish right, not either one.
	 *
	 * The gate used to accept anyone who could publish topics OR replies
	 * anywhere, so a reply-only community could store topic drafts
	 * (PROD-9621 N4).
	 */
	public function test_topic_draft_save_requires_the_topic_publish_right() {
		if ( ! bp_is_active( 'forums' ) ) {
			$this->markTestSkipped( 'Forums component inactive.' );
		}

		$user_id  = self::factory()->user->create();
		$old_user = get_current_user_id();
		$this->set_current_user( $user_id );

		$topic_context = bb_draft_topic_reply_key_context( 'draft_topic' );
		$reply_context = bb_draft_topic_reply_key_context( 'draft_reply' );

		// Reply-only member: replies allowed, topics refused.
		add_filter( 'bbp_current_user_can_publish_topics', '__return_false' );
		add_filter( 'bbp_current_user_can_publish_replies', '__return_true' );

		$topic_allowed = bb_draft_user_can_save_topic_reply_draft( $topic_context, $user_id );
		$reply_allowed = bb_draft_user_can_save_topic_reply_draft( $reply_context, $user_id );

		remove_filter( 'bbp_current_user_can_publish_topics', '__return_false' );
		remove_filter( 'bbp_current_user_can_publish_replies', '__return_true' );
		$this->set_current_user( $old_user );

		$this->assertFalse( $topic_allowed, 'A member who cannot publish topics must not be able to store a topic draft.' );
		$this->assertTrue( $reply_allowed, 'The same member must still be able to store a reply draft.' );
	}

	/**
	 * Saving must need view access to the forum the key resolves to.
	 *
	 * The activity composer already checks group membership for
	 * draft_group_{N}; the forum surface accepted any existing forum
	 * (PROD-9621 N4).
	 */
	public function test_forum_draft_save_requires_view_access_to_that_forum() {
		if ( ! bp_is_active( 'forums' ) ) {
			$this->markTestSkipped( 'Forums component inactive.' );
		}

		$user_id  = self::factory()->user->create();
		$old_user = get_current_user_id();
		$this->set_current_user( $user_id );

		$forum_id = $this->factory->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$context  = bb_draft_topic_reply_key_context( 'draft_discussion_' . $forum_id );

		add_filter( 'bbp_current_user_can_publish_topics', '__return_true' );

		$with_access = bb_draft_user_can_save_topic_reply_draft( $context, $user_id );

		add_filter( 'bbp_user_can_view_forum', '__return_false' );
		$without_access = bb_draft_user_can_save_topic_reply_draft( $context, $user_id );
		remove_filter( 'bbp_user_can_view_forum', '__return_false' );

		remove_filter( 'bbp_current_user_can_publish_topics', '__return_true' );
		$this->set_current_user( $old_user );

		$this->assertTrue( $with_access, 'A viewable forum must accept the draft.' );
		$this->assertFalse( $without_access, 'A forum the member cannot view must refuse the draft.' );
	}

	/**
	 * The upgrade guard must survive an unreliable object cache.
	 *
	 * It used to be a transient - in the one routine whose sibling option was
	 * deliberately NOT a transient, because transients live in the very object
	 * cache this pass repairs. A lost guard re-runs a 10-second synchronous
	 * healing slice on every admin request in the upgrade window
	 * (PROD-9621 N5).
	 */
	public function test_upgrade_guard_is_a_durable_option_and_blocks_a_repeat_run() {
		if ( ! function_exists( 'bb_drafts_cleanup_on_upgrade' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/bp-core-update.php';
		}

		delete_option( 'bb_drafts_cleanup_on_upgrade' );
		delete_option( 'bb_draft_oneshot_done' );
		delete_option( 'bb_draft_oneshot_state' );
		delete_option( 'bb_draft_cleanup_epoch' );

		// A slice that already ran inside this window must not be repeated.
		update_option( 'bb_drafts_cleanup_on_upgrade', time(), false );

		bb_drafts_cleanup_on_upgrade();

		$this->assertFalse( (bool) get_option( 'bb_draft_oneshot_done' ), 'The guard must block a second synchronous slice in the same window.' );
		$this->assertNotEmpty( get_option( 'bb_draft_cleanup_epoch' ), 'The epoch is still recorded even when the slice is skipped.' );

		// The guard is an option, not a transient: it is readable as one, and a
		// stale one lets the slice run again.
		$this->assertIsNumeric( get_option( 'bb_drafts_cleanup_on_upgrade' ) );

		update_option( 'bb_drafts_cleanup_on_upgrade', time() - ( 2 * HOUR_IN_SECONDS ), false );

		bb_drafts_cleanup_on_upgrade();

		$this->assertTrue( (bool) get_option( 'bb_draft_oneshot_done' ), 'Once the window has passed the slice must run.' );

		delete_option( 'bb_drafts_cleanup_on_upgrade' );
		delete_option( 'bb_draft_oneshot_done' );
	}

	/**
	 * Two overlapping sweeps must not share the cursor.
	 *
	 * The recurring event and the self-scheduled continuation both run
	 * bb_drafts_delete_expired() against one bb_draft_cleanup_cursor option.
	 * Without a lock the slower run can write its cursor back after the faster
	 * one finished and deleted it, and the next slice then skips every row
	 * below that stale position (PROD-9621 N10).
	 */
	public function test_expiry_sweep_is_serialized_by_a_lock() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => 'expired' ),
				'_draft_saved_at' => 100,
			)
		);

		delete_option( 'bb_draft_cleanup_cursor' );
		set_transient( 'bb_draft_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );

		$blocked = bb_drafts_delete_expired( 0 );

		$this->assertTrue( ! empty( $blocked['locked'] ), 'A sweep finding the lock held must report it.' );
		$this->assertSame( 0, $blocked['deleted'] );
		$this->assertFalse( $blocked['complete'], 'The work is not done, so the run must not claim completion.' );
		$this->assertNotEmpty( bp_get_user_meta( $user_id, 'draft_user', true ), 'A locked-out run must not touch anything.' );

		delete_transient( 'bb_draft_cleanup_lock' );

		$ran = bb_drafts_delete_expired( 0 );

		$this->assertTrue( empty( $ran['locked'] ) );
		$this->assertTrue( $ran['complete'] );
		$this->assertSame( '', (string) bp_get_user_meta( $user_id, 'draft_user', true ), 'Once the lock is free the expired draft is collected.' );
		$this->assertFalse( get_transient( 'bb_draft_cleanup_lock' ), 'The lock must be released when the sweep returns.' );
	}

	/**
	 * Disabling expiry must still release the lock.
	 */
	public function test_expiry_sweep_releases_the_lock_when_expiry_is_disabled() {
		delete_transient( 'bb_draft_cleanup_lock' );
		add_filter( 'bb_draft_retention_days', '__return_zero' );

		$result = bb_drafts_delete_expired( 0 );

		remove_filter( 'bb_draft_retention_days', '__return_zero' );

		$this->assertTrue( $result['complete'] );
		$this->assertFalse( get_transient( 'bb_draft_cleanup_lock' ), 'The early return must not leak the lock, or every later sweep is blocked for 5 minutes.' );
	}

	/**
	 * The maintenance window must be bounded in BYTES, not only in rows.
	 *
	 * The rows this machinery cleans up are the oversized ones, so a
	 * row-count-only window has no bound on memory - 200 x 1.5MB rows is
	 * ~300MB of meta_value before unserializing. A fatal there also skips the
	 * cursor persist, so the sweep restarts at the same window forever
	 * (PROD-9621 B2).
	 */
	public function test_row_batch_is_bounded_by_bytes_and_still_progresses() {
		$users = array();

		// Five rows of ~20KB each.
		for ( $i = 0; $i < 5; $i++ ) {
			$user_id = self::factory()->user->create();
			$users[] = $user_id;
			bp_update_user_meta(
				$user_id,
				'draft_user',
				array(
					'data_key'        => 'draft_user',
					'data'            => array( 'content' => str_repeat( 'x', 20000 ) ),
					'_draft_saved_at' => time(),
				)
			);
		}

		// A budget that fits roughly two rows must return fewer than all five
		// and report that more remain.
		$batch = bb_draft_get_rows_batch( 0, 200, true, 45000 );

		$this->assertLessThan( 5, count( $batch['rows'] ), 'The window must be trimmed to the byte budget.' );
		$this->assertNotEmpty( $batch['rows'] );
		$this->assertTrue( $batch['has_more'], 'A trimmed window must report that more rows remain.' );

		// Values are still delivered for the rows that were kept.
		foreach ( $batch['rows'] as $row ) {
			$this->assertArrayHasKey( 'meta_value', $row );
			$this->assertNotSame( '', $row['meta_value'], 'The second query must attach the payload for kept rows.' );
		}

		// A single row wider than the entire budget must still be returned, or
		// the cursor can never advance past it.
		$batch_tiny = bb_draft_get_rows_batch( 0, 200, true, 1 );
		$this->assertCount( 1, $batch_tiny['rows'], 'One row is always kept so the scan makes progress.' );
		$this->assertTrue( $batch_tiny['has_more'] );

		// Metadata-only mode is unaffected by the byte budget.
		$batch_ids = bb_draft_get_rows_batch( 0, 200, false );
		$this->assertCount( 5, $batch_ids['rows'] );
		$this->assertArrayNotHasKey( 'meta_value', $batch_ids['rows'][0] );

		// Walking the cursor with a tiny budget must eventually cover every row.
		$seen   = array();
		$cursor = 0;
		for ( $guard = 0; $guard < 20; $guard++ ) {
			$b = bb_draft_get_rows_batch( $cursor, 200, true, 1 );
			if ( empty( $b['rows'] ) ) {
				break;
			}
			foreach ( $b['rows'] as $r ) {
				$seen[ (int) $r['umeta_id'] ] = true;
			}
			$cursor = (int) $b['last_id'];
			if ( empty( $b['has_more'] ) ) {
				break;
			}
		}
		$this->assertCount( 5, $seen, 'A byte-trimmed scan must still reach every row across windows.' );
	}

	public function filter_prefix_user_meta_key( $key ) {
		return 'bbtest_' . $key;
	}

	public function filter_hash_user_meta_key( $key ) {
		return 'bbtest_' . md5( $key );
	}

	/**
	 * Every bb_draft_dispose() write path must invalidate the memoized sizes.
	 *
	 * The sizes are memoized per request, so a dispose that skips the flush
	 * leaves any later reader in the same request measuring pre-write bytes -
	 * and cap decisions are made from those bytes. Three write paths exist
	 * (legacy/corrupt row, inner forum draft, whole row); the outlier is the
	 * bug, so all three are asserted.
	 */
	public function test_dispose_invalidates_memoized_sizes_on_every_write_path() {
		// Path 3 - whole activity row.
		$u1 = self::factory()->user->create();
		bp_update_user_meta(
			$u1,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => str_repeat( 'a', 500 ) ),
				'_draft_saved_at' => time(),
			)
		);

		$before = bb_draft_get_user_meta_sizes( $u1 );
		$this->assertArrayHasKey( 'draft_user', $before['drafts'], 'Fixture must be measurable, or the test proves nothing.' );

		bb_draft_dispose( $u1, 'draft_user' );

		$after = bb_draft_get_user_meta_sizes( $u1 );
		$this->assertArrayNotHasKey( 'draft_user', $after['drafts'], 'Whole-row dispose must invalidate the memo.' );

		// Path 2 - one inner forum draft out of two, row survives.
		$u2 = self::factory()->user->create();
		bp_update_user_meta(
			$u2,
			'bb_user_topic_reply_draft',
			array(
				'draft_topic' => array(
					'data_key'        => 'draft_topic',
					'data'            => array( 'bbp_topic_content' => str_repeat( 'b', 400 ) ),
					'_draft_saved_at' => time(),
				),
				'draft_reply' => array(
					'data_key'        => 'draft_reply',
					'data'            => array( 'bbp_reply_content' => str_repeat( 'c', 400 ) ),
					'_draft_saved_at' => time(),
				),
			)
		);

		$before2 = bb_draft_get_user_meta_sizes( $u2 );
		$bytes2  = $before2['drafts']['bb_user_topic_reply_draft'];

		bb_draft_dispose( $u2, 'bb_user_topic_reply_draft', 'draft_reply' );

		$after2 = bb_draft_get_user_meta_sizes( $u2 );
		$this->assertLessThan( $bytes2, $after2['drafts']['bb_user_topic_reply_draft'], 'Inner-draft dispose must invalidate the memo so the row measures smaller.' );

		// Path 1 - legacy-empty array() row removed wholesale.
		$u3 = self::factory()->user->create();
		add_user_meta( $u3, 'bb_user_topic_reply_draft', array() );

		$before3 = bb_draft_get_user_meta_sizes( $u3 );
		$this->assertArrayHasKey( 'bb_user_topic_reply_draft', $before3['drafts'] );

		bb_draft_dispose( $u3, 'bb_user_topic_reply_draft' );

		$after3 = bb_draft_get_user_meta_sizes( $u3 );
		$this->assertArrayNotHasKey( 'bb_user_topic_reply_draft', $after3['drafts'], 'Legacy-empty row dispose must invalidate the memo.' );
	}
}
