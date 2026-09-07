<?php
/**
 * @group core
 * @group drafts
 */
class BP_Tests_Core_Drafts extends BP_UnitTestCase {

	/**
	 * Forum ID that filter_block_unreadable_forum() denies.
	 *
	 * @var int
	 */
	protected $unreadable_forum_id = 0;

	/**
	 * State for the concurrent-tab injection used by the M2 race tests.
	 *
	 * @var int
	 */
	protected $race_user_id = 0;

	/**
	 * @var int
	 */
	protected $race_calls = 0;

	/**
	 * @var string
	 */
	protected $race_mode = '';

	/**
	 * @var string
	 */
	protected $race_key = '';

	/**
	 * @var bool
	 */
	protected $race_done = false;

	/**
	 * Which bb_draft_max_size() call the competing write actually landed on.
	 *
	 * @var int
	 */
	protected $race_fired_at = 0;

	/**
	 * @var bool
	 */
	protected $race_reentrant = false;

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

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

	/**
	 * Trimming the forum row must have no storage side effects of its own.
	 *
	 * bb_forums_trim_draft_row() used to unstamp each evicted entry's
	 * attachments and fire bb_draft_evicted immediately, while writing no
	 * usermeta. A budget refusal three lines later abandons the write - so the
	 * "evicted" drafts were still stored, their attachments were unprotected
	 * (the same reaping mechanism as B1), and the public hook had fired for
	 * evictions that never happened (PROD-9621 H4).
	 */
	public function test_forum_row_trim_has_no_side_effects_of_its_own() {
		$user_id       = self::factory()->user->create();
		$attachment_id = self::factory()->attachment->create( array( 'post_author' => $user_id ) );

		update_post_meta( $attachment_id, 'bb_media_draft', 1 );

		$fired = array();
		$spy   = function ( $uid, $key, $reason ) use ( &$fired ) {
			$fired[] = $key;
		};
		add_action( 'bb_draft_evicted', $spy, 10, 3 );

		$row = array(
			'draft_reply_old' => array(
				'data_key'        => 'draft_reply_old',
				'data'            => array(
					'bbp_reply_content' => str_repeat( 'o', 900 ),
					'bbp_media'         => wp_json_encode( array( array( 'id' => $attachment_id ) ) ),
				),
				'_draft_saved_at' => 100,
			),
			'draft_reply_new' => array(
				'data_key'        => 'draft_reply_new',
				'data'            => array( 'bbp_reply_content' => str_repeat( 'n', 900 ) ),
				'_draft_saved_at' => time(),
			),
		);

		// A budget small enough to force the old entry out.
		$trimmed = bb_forums_trim_draft_row( $row, 'draft_reply_new', $user_id, 1200 );

		remove_action( 'bb_draft_evicted', $spy, 10 );

		$this->assertSame( array( 'bb_user_topic_reply_draft:draft_reply_old' ), $trimmed['evicted'], 'The old entry must be reported as evicted.' );
		$this->assertArrayNotHasKey( 'draft_reply_old', $trimmed['row'], 'and removed from the in-memory row.' );
		$this->assertArrayHasKey( 'draft_reply_old', $trimmed['entries'], 'The removed entry must be handed back for deferred unstamping.' );

		// The two side effects must NOT have happened yet.
		$this->assertSame( '1', (string) get_post_meta( $attachment_id, 'bb_media_draft', true ), 'Trim must not unstamp - the write it belongs to can still be refused.' );
		$this->assertSame( array(), $fired, 'Trim must not fire bb_draft_evicted - nothing has been stored yet.' );
	}

	/**
	 * The upgrade slice must queue its continuation before it runs.
	 *
	 * _bp_db_version is bumped by bp_version_bump() inside bp_is_update(),
	 * before the updater body executes, so bp_setup_updater() never re-enters
	 * it. A fatal or timeout inside the synchronous healing slice would
	 * therefore skip both the continuation and every migration that follows it
	 * in the same routine, with no retry (PROD-9621 H1).
	 */
	public function test_upgrade_queues_the_continuation_before_running_the_slice() {
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

		if ( ! function_exists( 'bb_drafts_cleanup_on_upgrade' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/bp-core-update.php';
		}

		delete_option( 'bb_drafts_cleanup_on_upgrade' );
		delete_option( 'bb_draft_oneshot_done' );
		delete_option( 'bb_draft_oneshot_state' );
		$existing = wp_next_scheduled( 'bb_draft_oneshot' );
		if ( $existing ) {
			wp_unschedule_event( $existing, 'bb_draft_oneshot' );
		}

		// Observe the schedule state from INSIDE the slice - that is the window
		// a fatal would land in.
		$scheduled_during_slice = null;
		$spy                    = function ( $max_size ) use ( &$scheduled_during_slice ) {
			if ( null === $scheduled_during_slice ) {
				$scheduled_during_slice = (bool) wp_next_scheduled( 'bb_draft_oneshot' );
			}

			return $max_size;
		};
		add_filter( 'bb_draft_max_size', $spy );

		bb_drafts_cleanup_on_upgrade();

		remove_filter( 'bb_draft_max_size', $spy );

		$this->assertTrue( $scheduled_during_slice, 'The continuation must already be queued while the slice is running, or a fatal there loses the remaining work.' );

		// It completed here, so the now-redundant continuation is withdrawn.
		$this->assertTrue( (bool) get_option( 'bb_draft_oneshot_done' ) );
		$this->assertFalse( wp_next_scheduled( 'bb_draft_oneshot' ), 'A completed slice must not leave a pointless cron event behind.' );

		delete_option( 'bb_drafts_cleanup_on_upgrade' );
	}

	/**
	 * Healing one forum row must be a single write, not one per inner draft.
	 *
	 * Routing every inner drop through bb_draft_dispose() meant a full-row read
	 * plus a full-row write each time, and the trim loop re-serialized the
	 * whole row on every iteration - O(n.B) on rows that are megabytes wide by
	 * definition, inside a synchronous upgrade request (PROD-9621 H2).
	 */
	public function test_forum_row_heal_writes_once_regardless_of_how_many_it_drops() {
		$user_id = self::factory()->user->create();
		$row     = array();

		// Six inner drafts, each individually over the per-draft cap.
		for ( $i = 0; $i < 6; $i++ ) {
			$row[ 'draft_reply_' . $i ] = array(
				'data_key'        => 'draft_reply_' . $i,
				'data'            => array( 'bbp_reply_content' => str_repeat( 'x', bb_draft_max_size() + 100 ) ),
				'_draft_saved_at' => 100 + $i,
			);
		}

		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', $row );

		$writes = 0;
		$count  = function () use ( &$writes ) {
			++$writes;
		};
		add_action( 'updated_user_meta', $count );
		add_action( 'added_user_meta', $count );
		add_action( 'deleted_user_meta', $count );

		$disposed = bb_draft_heal_forum_row( $user_id );

		remove_action( 'updated_user_meta', $count );
		remove_action( 'added_user_meta', $count );
		remove_action( 'deleted_user_meta', $count );

		$this->assertSame( 6, $disposed, 'Every oversized inner draft must be dropped.' );
		$this->assertSame( 1, $writes, 'The whole heal must be one write; one per dropped draft is what made this quadratic.' );
		$this->assertSame( '', (string) bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true ), 'An emptied row is deleted, not stored as array().' );
	}

	/**
	 * Healing releases the stamps of what it dropped, keeping what survives.
	 */
	public function test_forum_row_heal_releases_only_the_dropped_attachments() {
		$user_id = self::factory()->user->create();
		$dropped = self::factory()->attachment->create( array( 'post_author' => $user_id ) );
		$kept    = self::factory()->attachment->create( array( 'post_author' => $user_id ) );

		update_post_meta( $dropped, 'bb_media_draft', 1 );
		update_post_meta( $kept, 'bb_media_draft', 1 );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				// Oversized - will be dropped.
				'draft_reply_big'   => array(
					'data_key'        => 'draft_reply_big',
					'data'            => array(
						'bbp_reply_content' => str_repeat( 'x', bb_draft_max_size() + 100 ),
						'bbp_media'         => wp_json_encode( array( array( 'id' => $dropped ) ) ),
					),
					'_draft_saved_at' => 100,
				),
				// Legal - must survive with its stamp intact.
				'draft_reply_small' => array(
					'data_key'        => 'draft_reply_small',
					'data'            => array(
						'bbp_reply_content' => 'small',
						'bbp_media'         => wp_json_encode( array( array( 'id' => $kept ) ) ),
					),
					'_draft_saved_at' => time(),
				),
			)
		);

		bb_draft_heal_forum_row( $user_id );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertArrayHasKey( 'draft_reply_small', $row, 'A legal inner draft must survive the heal.' );
		$this->assertArrayNotHasKey( 'draft_reply_big', $row );
		$this->assertSame( '', (string) get_post_meta( $dropped, 'bb_media_draft', true ), 'The dropped draft releases its attachment.' );
		$this->assertSame( '1', (string) get_post_meta( $kept, 'bb_media_draft', true ), 'The surviving draft keeps its attachment protected.' );
	}

	/**
	 * The per-draft cap must refuse before the expensive sanitizer runs.
	 *
	 * kses on the full client payload ran before any cap could reject it - a
	 * CPU amplifier on an endpoint every open composer hits every 20 seconds.
	 * Data URLs are still stripped first, so a pasted bitmap stays cheap and is
	 * judged on its post-strip width (PROD-9621 M4).
	 */
	public function test_forum_draft_strip_runs_before_kses_and_leaves_content_intact() {
		$entry = array(
			'data_key' => 'draft_reply',
			'data'     => array(
				'bbp_reply_content' => 'keep <b>this</b> <img src="data:image/png;base64,' . str_repeat( 'A', 200000 ) . '"> and this',
			),
		);

		$stripped = bb_forums_strip_draft_data_urls( $entry );

		$this->assertStringNotContainsString( 'base64', $stripped['data']['bbp_reply_content'], 'The payload must be gone before the cap is measured.' );
		$this->assertStringContainsString( 'keep', $stripped['data']['bbp_reply_content'] );
		$this->assertStringContainsString( 'and this', $stripped['data']['bbp_reply_content'] );
		$this->assertLessThan( 200, strlen( $stripped['data']['bbp_reply_content'] ), 'A pasted bitmap must collapse to nearly nothing, so it is never near the cap.' );

		// Strip alone must not sanitize - that is the whole point of the split.
		$this->assertStringContainsString( '<b>this</b>', $stripped['data']['bbp_reply_content'] );

		// Non-content members and non-arrays pass through untouched.
		$this->assertSame( array( 'data_key' => 'x' ), bb_forums_strip_draft_data_urls( array( 'data_key' => 'x' ) ) );
		$this->assertSame( 'not an array', bb_forums_strip_draft_data_urls( 'not an array' ) );
	}

	/**
	 * The two public draft hooks must actually fire, with their documented args.
	 *
	 * They shipped as public contracts with zero assertions anywhere
	 * (PROD-9621 M7).
	 */
	public function test_public_draft_hooks_fire_with_their_documented_arguments() {
		$user_id = self::factory()->user->create();

		// Fill the user's draft budget so the next save must evict.
		$per_draft = bb_draft_max_size();
		$total     = bb_draft_user_total_max_size();
		$fillers   = (int) ceil( $total / $per_draft ) + 1;

		for ( $i = 1; $i <= $fillers; $i++ ) {
			bp_update_user_meta(
				$user_id,
				'draft_group_' . $i,
				array(
					'data_key'        => 'draft_group_' . $i,
					'data'            => array( 'content' => str_repeat( 'x', $per_draft - 200 ) ),
					'_draft_saved_at' => 100 + $i,
				)
			);
		}

		$evicted = array();
		$spy     = function ( $uid, $key, $reason ) use ( &$evicted ) {
			$evicted[] = array( $uid, $key, $reason );
		};
		add_action( 'bb_draft_evicted', $spy, 10, 3 );

		bb_draft_flush_user_meta_sizes( $user_id );
		$result = bb_draft_enforce_user_budget( $user_id, 'draft_user', $per_draft - 200 );

		remove_action( 'bb_draft_evicted', $spy, 10 );

		$this->assertNotEmpty( $result['evicted'], 'The aggregate cap must have evicted something.' );
		$this->assertNotEmpty( $evicted, 'bb_draft_evicted must fire for every eviction - it is a public contract.' );
		$this->assertSame( count( $result['evicted'] ), count( $evicted ), 'One fire per evicted key.' );
		$this->assertSame( $user_id, $evicted[0][0], 'Arg 1 is the user id.' );
		$this->assertSame( $result['evicted'][0], $evicted[0][1], 'Arg 2 is the evicted key.' );
		$this->assertSame( 'aggregate_cap', $evicted[0][2], 'Arg 3 is the documented reason.' );

		// Oldest first, per the docblock.
		$this->assertStringContainsString( 'draft_group_1', $evicted[0][1], 'Eviction must be oldest-first.' );
	}

	/**
	 * A legacy empty inner key must not take the member's whole row with it.
	 *
	 * bb_draft_dispose() treats an empty $inner_key as the whole-row sentinel.
	 * A stored row carrying an empty-string key - which release could produce,
	 * since it validated nothing - would therefore be deleted entirely by any
	 * loop that walked its keys and passed each one through (PROD-9621 M5).
	 */
	public function test_empty_inner_key_in_a_stored_row_never_wipes_the_row() {
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

		$user_id = self::factory()->user->create();

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				// A legacy/garbage entry with an empty key, long expired.
				''                => array(
					'data_key'        => '',
					'data'            => array( 'bbp_reply_content' => 'legacy junk' ),
					'_draft_saved_at' => 100,
				),
				// A real expired draft that SHOULD be collected.
				'draft_reply_old' => array(
					'data_key'        => 'draft_reply_old',
					'data'            => array( 'bbp_reply_content' => 'old' ),
					'_draft_saved_at' => 100,
				),
				// A fresh draft that must survive.
				'draft_reply_new' => array(
					'data_key'        => 'draft_reply_new',
					'data'            => array( 'bbp_reply_content' => 'new' ),
					'_draft_saved_at' => time(),
				),
			)
		);

		delete_option( 'bb_draft_cleanup_cursor' );
		delete_transient( 'bb_draft_cleanup_lock' );
		bb_drafts_delete_expired( 0 );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertIsArray( $row, 'The row must survive - an empty inner key must not trigger the whole-row delete.' );
		$this->assertArrayHasKey( 'draft_reply_new', $row, 'The fresh draft must still be there.' );
		$this->assertArrayNotHasKey( 'draft_reply_old', $row, 'The genuinely expired draft is still collected.' );
	}

	/**
	 * The heal's size bookkeeping must match what serialize() really produces.
	 *
	 * A row is wider than the sum of its entries - every element also carries
	 * its key. Subtracting only the entry bytes leaves the tracked width above
	 * the truth, so the oldest-first trim keeps going and evicts drafts the
	 * member should have kept.
	 */
	public function test_heal_size_bookkeeping_matches_serialize_exactly() {
		// The formula must equal what serialize() charges for the key.
		foreach ( array( 'draft_reply_1000', 'draft_topic', 'x' ) as $key ) {
			$with    = strlen( maybe_serialize( array( $key => 1 ) ) );
			$without = strlen( maybe_serialize( array() ) );
			// element = key + value(i:1;) ; isolate the key by subtracting the value.
			$value_bytes = strlen( 'i:1;' );
			$this->assertSame(
				$with - $without - $value_bytes,
				bb_draft_serialized_key_bytes( $key ),
				"Key accounting must be exact for '{$key}'."
			);
		}

		// End to end: a row of many small entries must not be over-trimmed.
		$user_id = self::factory()->user->create();
		$row     = array();
		$cap     = bb_draft_user_total_max_size();

		// ~1000 tiny entries: the regime where ignoring key bytes drifts most.
		for ( $i = 0; $i < 1000; $i++ ) {
			$row[ 'draft_reply_' . ( 1000 + $i ) ] = array(
				'data_key'        => 'draft_reply_' . ( 1000 + $i ),
				'data'            => array( 'bbp_reply_content' => str_repeat( 'x', 300 ) ),
				'_draft_saved_at' => 100 + $i,
			);
		}

		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', $row );
		bb_draft_heal_forum_row( $user_id );

		$healed = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertIsArray( $healed );
		$this->assertLessThanOrEqual( $cap, strlen( maybe_serialize( $healed ) ), 'The healed row must fit the budget.' );

		// And it must not have gone further than needed: putting the single
		// oldest survivor back must push it over the cap again.
		$survivors = $healed;
		ksort( $survivors );
		$oldest_key = null;
		$oldest_ts  = PHP_INT_MAX;
		foreach ( $row as $k => $entry ) {
			if ( ! isset( $healed[ $k ] ) && $entry['_draft_saved_at'] < $oldest_ts ) {
				$oldest_ts  = $entry['_draft_saved_at'];
				$oldest_key = $k;
			}
		}

		$this->assertNotNull( $oldest_key, 'Something must have been evicted for this to be a real test.' );

		$healed[ $oldest_key ] = $row[ $oldest_key ];
		$this->assertGreaterThan(
			$cap,
			strlen( maybe_serialize( $healed ) ),
			'Restoring one evicted draft must exceed the cap - otherwise the trim went too far.'
		);
	}

	/**
	 * Clear every draft row and maintenance option before a GLOBAL pass.
	 *
	 * bb_drafts_delete_expired(), bb_drafts_oneshot_batch() and
	 * bb_drafts_cleanup_on_upgrade() all walk the WHOLE usermeta table. A test
	 * that invokes one therefore acts on fixtures belonging to other tests,
	 * and what it finds depends on ordering, on ids, and - because the sweep is
	 * time-budgeted - on wall clock. That made this file flaky roughly one run
	 * in six, taking pre-existing dispose tests down with it.
	 *
	 * Call this first in any test that runs a global pass, so the pass only
	 * ever sees that test's own rows.
	 */
	/**
	 * The strip must cover data: URIs that are not base64 encoded.
	 *
	 * The original rule required ";base64," and let every other form through at
	 * full size - data:image/svg+xml,<svg …>, ;utf8,, percent-encoded - so a
	 * member could park megabytes in a draft and only ever be refused for size,
	 * never cleaned. Not an injection path: kses escapes the surviving tag to
	 * inert text and leaves no live data: src. A size-coverage gap
	 * (PROD-9621 M3).
	 */
	public function test_strip_data_urls_covers_non_base64_uris() {
		$cases = array(
			'a <img src="data:image/svg+xml,<svg onload=alert(1)/>"> b',
			'a <img src="data:image/svg+xml;utf8,%3Csvg%3E"> b',
			'a <img src="data:text/html,%3Cscript%3E"> b',
		);

		foreach ( $cases as $input ) {
			$stripped = bb_draft_strip_data_urls( $input );

			$this->assertStringNotContainsString( 'data:', $stripped, 'Every data: URI form must be stripped, not just base64.' );
			$this->assertLessThan( strlen( $input ), strlen( $stripped ) );
		}

		// Content with no data URI, and a legitimate remote image, must be
		// byte-identical - the widened rule must not eat ordinary markup.
		$plain = 'plain text, nothing to strip';
		$this->assertSame( $plain, bb_draft_strip_data_urls( $plain ) );

		$real = 'see <img src="https://example.com/a.png" alt="x"> ok';
		$this->assertSame( $real, bb_draft_strip_data_urls( $real ) );

		// And it must stay linear on a multi-megabyte payload rather than
		// hitting a PCRE backtrack limit, which is why both rules are negated
		// character classes.
		// No wall-clock assertion here. An earlier version asserted the 2MB case
		// completed inside two seconds, and that failed twice out of forty runs
		// under concurrent load - a flaky test of the machine, not of the code.
		// What actually needs asserting is that PCRE did not bail: a backtrack
		// limit makes preg_replace() return null, which this function converts
		// into "keep the original", so the payload would silently survive.
		$big = 'x <img src="data:image/svg+xml,' . str_repeat( 'A', 2 * MB_IN_BYTES ) . '"> y';
		$out = bb_draft_strip_data_urls( $big );

		$this->assertIsString( $out, 'A PCRE failure would return null and silently keep the payload.' );
		$this->assertLessThan( 200, strlen( $out ), 'The payload must be gone, which only happens if the match did not bail.' );
		$this->assertSame( PREG_NO_ERROR, preg_last_error(), 'A backtrack limit here would mean the pattern is not linear.' );
	}

	protected function isolate_draft_maintenance() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- test isolation; the meta cache is flushed straight after.
		$rows = $wpdb->get_results( "SELECT user_id, meta_key FROM {$wpdb->usermeta} WHERE meta_key LIKE '%draft%' OR meta_key = 'bb_user_topic_reply_draft'" );

		foreach ( (array) $rows as $row ) {
			delete_user_meta( (int) $row->user_id, $row->meta_key );
			clean_user_cache( (int) $row->user_id );
			wp_cache_delete( (int) $row->user_id, 'user_meta' );
			bb_draft_flush_user_meta_sizes( (int) $row->user_id );
		}

		delete_option( 'bb_draft_cleanup_cursor' );
		delete_option( 'bb_draft_oneshot_state' );
		delete_option( 'bb_draft_oneshot_done' );
		delete_option( 'bb_drafts_cleanup_on_upgrade' );
		delete_transient( 'bb_draft_cleanup_lock' );

		$scheduled = wp_next_scheduled( 'bb_draft_oneshot' );
		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, 'bb_draft_oneshot' );
		}
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

	/**
	 * H1 regression: the all_data merge must re-authorize every sibling key.
	 *
	 * The unload beacon replays whatever the tab still holds, so a sibling entry
	 * stored while the member could still see its forum can arrive long after
	 * that access was revoked. Before the fix the loop checked isset(), sanitized
	 * and capped, but never re-ran the view/publish gate the primary path
	 * enforces - so the stale sibling kept taking writes.
	 *
	 * bbp_user_can_view_forum() is forced through its own filter rather than
	 * built out of bbPress capability state: the assertion is that the merge loop
	 * HONOURS the gate, and forcing the gate's answer keeps the test independent
	 * of how visibility happens to be configured.
	 */
	public function test_all_data_merge_reauthorizes_each_sibling_key() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$readable_forum   = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$unreadable_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$readable_key   = 'draft_discussion_' . $readable_forum;
		$unreadable_key = 'draft_discussion_' . $unreadable_forum;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$readable_key   => array(
					'data_key'        => $readable_key,
					'data'            => array( 'bbp_topic_content' => 'readable original' ),
					'_draft_saved_at' => time() - 60,
				),
				$unreadable_key => array(
					'data_key'        => $unreadable_key,
					'data'            => array( 'bbp_topic_content' => 'SIBLING ORIGINAL' ),
					'_draft_saved_at' => time() - 60,
				),
			)
		);

		$this->unreadable_forum_id = $unreadable_forum;
		add_filter( 'bbp_user_can_view_forum', array( $this, 'filter_block_unreadable_forum' ), 10, 2 );

		$_POST    = array();
		$_REQUEST = array();

		$_REQUEST['draft_topic_reply'] = wp_json_encode(
			array(
				'data_key'    => $readable_key,
				'object'      => 'topic',
				'post_action' => 'update',
				'data'        => array( 'bbp_topic_content' => 'readable updated' ),
			)
		);
		$_REQUEST['all_data'] = wp_json_encode(
			array(
				$unreadable_key => array( 'bbp_topic_content' => 'SIBLING SMUGGLED UPDATE' ),
			)
		);

		$nonce_key = '_wpnonce_post_topic_reply_draft';

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST[ $nonce_key ] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST            = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		// wp_send_json_*() exits. It only routes through the filterable wp_die()
		// when wp_doing_ajax() is true - otherwise it calls a bare die() that
		// would kill the whole test run - so force that first, then convert the
		// exit into an exception we can swallow.
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();

		try {
			bb_post_topic_reply_draft();
		} catch ( Exception $e ) {
			// Expected: the handler finished and tried to exit.
			unset( $e );
		}

		ob_end_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'bbp_user_can_view_forum', array( $this, 'filter_block_unreadable_forum' ), 10 );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertIsArray( $stored, 'The aggregate row must still exist.' );
		$this->assertSame(
			'SIBLING ORIGINAL',
			$stored[ $unreadable_key ]['data']['bbp_topic_content'],
			'A sibling draft in a forum the member can no longer view must NOT take the smuggled write.'
		);

		// Negative control: the authorized key in the SAME request must still
		// save, or this test would also pass for a handler that rejects
		// everything.
		$this->assertSame(
			'readable updated',
			$stored[ $readable_key ]['data']['bbp_topic_content'],
			'The primary, authorized key must still be written.'
		);
	}

	/**
	 * Deny view access to one specific forum.
	 *
	 * @param bool      $retval   Incoming value.
	 * @param int|array $forum_id Forum ID (bbPress passes the ID, not args).
	 * @return bool
	 */
	public function filter_block_unreadable_forum( $retval, $forum_id = 0 ) {
		// bbp_user_can_view_forum() passes the forum ID itself as the second
		// filter argument, not the parsed args array - verified against the
		// live filter before relying on it.
		if ( is_array( $forum_id ) ) {
			$forum_id = isset( $forum_id['forum_id'] ) ? $forum_id['forum_id'] : 0;
		}

		if ( ! empty( $this->unreadable_forum_id ) && (int) $forum_id === (int) $this->unreadable_forum_id ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Route wp_die() to a thrower so wp_send_json_*() does not end the run.
	 *
	 * @return callable
	 */
	public function filter_draft_die_handler() {
		return array( $this, 'throw_on_draft_die' );
	}

	/**
	 * Turn the handler's exit into a catchable exception.
	 *
	 * @throws Exception Always.
	 * @return void
	 */
	public function throw_on_draft_die() {
		throw new Exception( 'draft-handler-exit' );
	}

	/**
	 * H2 premise guard: kses must strip data-emoji-char but keep alt.
	 *
	 * The draft path now converts emoji images to their unicode character before
	 * storing, and falls back to the image's alt when a draft stored by an older
	 * build carries no data-emoji-char. That fallback is only sound while kses
	 * keeps alt and drops data-emoji-char - the exact asymmetry that caused the
	 * original bug. If a future WP or allowedtags change alters either half, the
	 * fallback silently stops working, so the premise is asserted here.
	 *
	 * This does NOT test the JavaScript: this repository has no configured Jest
	 * harness (package.json references a jest.config.js that does not exist), so
	 * the conversion itself is covered by browser QA, not by a unit test.
	 */
	public function test_kses_strips_emoji_char_attribute_but_keeps_alt() {
		$filtered = bp_activity_filter_kses(
			'hi <img class="emojioneemoji" src="https://example.com/e.png" data-emoji-char="X" alt="X"> there'
		);

		$this->assertStringNotContainsString(
			'data-emoji-char',
			$filtered,
			'If kses ever starts keeping data-emoji-char, the draft conversion is no longer needed - revisit the fix.'
		);
		$this->assertStringContainsString(
			'alt="X"',
			$filtered,
			'The alt fallback the draft conversion relies on must survive kses.'
		);
		$this->assertStringContainsString(
			'emojioneemoji',
			$filtered,
			'The class survives, which is why the publish path cannot re-populate the attribute on its own.'
		);
	}

	/**
	 * Perform a second tab's write from INSIDE the handler's write window.
	 *
	 * The bb_draft_max_size() cap is consulted again after the aggregate row has been
	 * read into a PHP variable, which makes it the honest injection point: the
	 * competing request commits while this one is still holding its copy, which
	 * is exactly what a second browser tab does.
	 *
	 * @param int $size Incoming cap.
	 * @return int
	 */
	public function inject_competing_draft_write( $size ) {
		if ( $this->race_reentrant ) {
			return $size;
		}

		++$this->race_calls;

		// Fire once, and only after the handler has taken its copy of the row.
		if ( $this->race_done || $this->race_calls < 3 ) {
			return $size;
		}

		$this->race_done      = true;
		$this->race_fired_at  = $this->race_calls;
		$this->race_reentrant = true;

		// The competing request is a separate PHP process with its own read.
		wp_cache_delete( $this->race_user_id, 'user_meta' );
		$row = bp_get_user_meta( $this->race_user_id, 'bb_user_topic_reply_draft', true );

		if ( 'discard' === $this->race_mode ) {
			unset( $row[ $this->race_key ] );
		} else {
			$row[ $this->race_key ]['data']['bbp_topic_content'] = 'OTHER TAB UPDATE';
			$row[ $this->race_key ]['_draft_saved_at']           = time();
		}

		bp_update_user_meta( $this->race_user_id, 'bb_user_topic_reply_draft', $row );
		bb_draft_flush_user_meta_sizes( $this->race_user_id );

		$this->race_reentrant = false;

		return $size;
	}

	/**
	 * Seed two inner forum drafts and arm the competing-tab injection.
	 *
	 * @param string $mode 'discard' or 'update'.
	 * @return array Draft keys [ other tab's, this request's ].
	 */
	protected function seed_two_tab_forum_drafts( $mode ) {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$this->race_user_id  = $user_id;
		$this->race_mode     = $mode;
		$this->race_calls    = 0;
		$this->race_done     = false;
		$this->race_fired_at = 0;

		$other_forum   = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$current_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$other_key   = 'draft_discussion_' . $other_forum;
		$current_key = 'draft_discussion_' . $current_forum;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$other_key   => array(
					'data_key'        => $other_key,
					'object'          => 'topic',
					'data'            => array( 'bbp_topic_content' => 'OTHER TAB ORIGINAL' ),
					'_draft_saved_at' => time() - 120,
				),
				$current_key => array(
					'data_key'        => $current_key,
					'object'          => 'topic',
					'data'            => array( 'bbp_topic_content' => 'THIS TAB ORIGINAL' ),
					'_draft_saved_at' => time() - 120,
				),
			)
		);

		$this->race_key = $other_key;

		return array( $other_key, $current_key );
	}

	/**
	 * Drive the save handler for one inner key with the injection armed.
	 *
	 * @param string $data_key Inner draft key to save.
	 * @param string $content  Content to store.
	 */
	protected function drive_draft_save_with_race( $data_key, $content ) {
		$_POST    = array();
		$_REQUEST = array();

		$_REQUEST['draft_topic_reply'] = wp_json_encode(
			array(
				'data_key'    => $data_key,
				'object'      => 'topic',
				'post_action' => 'update',
				'data'        => array( 'bbp_topic_content' => $content ),
			)
		);

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		add_filter( 'bb_draft_max_size', array( $this, 'inject_competing_draft_write' ), 10, 1 );

		ob_start();

		try {
			bb_post_topic_reply_draft();
		} catch ( Exception $e ) {
			// Expected: the handler finished and tried to exit.
			unset( $e );
		}

		ob_end_clean();

		remove_filter( 'bb_draft_max_size', array( $this, 'inject_competing_draft_write' ), 10 );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		wp_cache_delete( $this->race_user_id, 'user_meta' );

		return bp_get_user_meta( $this->race_user_id, 'bb_user_topic_reply_draft', true );
	}

	/**
	 * A discard in another tab must survive this tab's autosave.
	 *
	 * The handler used to write back the whole row it read at the start of the
	 * request. A second tab discarding a DIFFERENT inner draft inside that
	 * window had its deletion undone - and because the discard had already
	 * released that entry's attachment stamps, the orphan cron then reaped the
	 * media the resurrected draft still referenced (PROD-9621 M2).
	 */
	public function test_concurrent_discard_is_not_resurrected_by_a_sibling_autosave() {
		list( $other_key, $current_key ) = $this->seed_two_tab_forum_drafts( 'discard' );

		$stored = $this->drive_draft_save_with_race( $current_key, 'THIS TAB AUTOSAVE' );

		$this->assertSame( 3, $this->race_fired_at, 'The competing write must land inside the handler, after it read the row, or this test proves nothing.' );
		$this->assertArrayNotHasKey(
			$other_key,
			(array) $stored,
			'A draft discarded by another tab must stay discarded - writing the stale row back resurrects it.'
		);

		// Negative control: this request's own key must still be written, or the
		// test would also pass for a handler that stores nothing at all.
		$this->assertSame(
			'THIS TAB AUTOSAVE',
			$stored[ $current_key ]['data']['bbp_topic_content'],
			'The key this request owns must still be saved.'
		);
	}

	/**
	 * Another tab's autosave must survive this tab's autosave.
	 *
	 * Same stale whole-row write-back as above, in its lost-update form: the
	 * other tab's newer content was silently reverted to what this request had
	 * read minutes earlier (PROD-9621 M2).
	 */
	public function test_concurrent_sibling_autosave_is_not_clobbered() {
		list( $other_key, $current_key ) = $this->seed_two_tab_forum_drafts( 'update' );

		$stored = $this->drive_draft_save_with_race( $current_key, 'THIS TAB AUTOSAVE' );

		$this->assertSame( 3, $this->race_fired_at, 'The competing write must land inside the handler, after it read the row, or this test proves nothing.' );
		$this->assertSame(
			'OTHER TAB UPDATE',
			$stored[ $other_key ]['data']['bbp_topic_content'],
			'A sibling draft another tab just autosaved must not be reverted by this request.'
		);
		$this->assertSame(
			'THIS TAB AUTOSAVE',
			$stored[ $current_key ]['data']['bbp_topic_content'],
			'The key this request owns must still be saved.'
		);
	}
	/**
	 * A cap DECISION must act in the same key space the measurement reports.
	 *
	 * The bb_draft_get_user_meta_sizes() memo reports LOGICAL keys and
	 * bb_draft_enforce_user_budget() feeds those straight to
	 * bb_draft_dispose(), which re-applies bp_get_user_meta_key(). The two
	 * halves therefore have to agree: if the measurement reverted to raw
	 * literals no cap could ever fire on a filtered install, and if dispose
	 * stopped re-filtering it would delete a raw lookalike while the member's
	 * real draft survived over budget. The sweep test covers expiry; this
	 * covers the budget path, which is the half N2's recommendation asked for
	 * and the one that runs on every save (PROD-9621 N2).
	 */
	public function test_budget_eviction_disposes_the_filtered_row_not_a_raw_lookalike() {
		$user_id = self::factory()->user->create();

		// A stray unfiltered lookalike a migration could have left behind. It is
		// not this install's draft key, so nothing may measure or delete it.
		update_user_meta( $user_id, 'draft_group_11', 'STRAY-RAW-ROW' );

		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );

		// Oldest - the eviction candidate.
		bp_update_user_meta(
			$user_id,
			'draft_group_11',
			array(
				'data_key'        => 'draft_group_11',
				'data'            => array( 'content' => str_repeat( 'a', 150 * KB_IN_BYTES ) ),
				'_draft_saved_at' => 100,
			)
		);

		// Newest - must survive.
		bp_update_user_meta(
			$user_id,
			'draft_group_22',
			array(
				'data_key'        => 'draft_group_22',
				'data'            => array( 'content' => str_repeat( 'b', 150 * KB_IN_BYTES ) ),
				'_draft_saved_at' => time(),
			)
		);

		bb_draft_flush_user_meta_sizes( $user_id );

		$sizes = bb_draft_get_user_meta_sizes( $user_id );

		// Premise: both filtered rows are measured, and the raw lookalike is not.
		$this->assertArrayHasKey( 'draft_group_11', $sizes['drafts'] );
		$this->assertArrayHasKey( 'draft_group_22', $sizes['drafts'] );

		// A third draft pushes the member past bb_draft_user_total_max_size().
		$result = bb_draft_enforce_user_budget( $user_id, 'draft_user', 90 * KB_IN_BYTES );

		$evicted_row  = bp_get_user_meta( $user_id, 'draft_group_11', true );
		$survivor_row = bp_get_user_meta( $user_id, 'draft_group_22', true );

		remove_filter( 'bp_get_user_meta_key', array( $this, 'filter_prefix_user_meta_key' ) );
		bb_draft_flush_user_meta_sizes( $user_id );

		$stray = get_user_meta( $user_id, 'draft_group_11', true );

		$this->assertTrue( $result['allowed'], 'The save is under the total meta budget and must be allowed.' );
		$this->assertContains(
			'draft_group_11',
			$result['evicted'],
			'The oldest draft must be reported evicted under its LOGICAL key - that is what the client drops from localStorage.'
		);
		$this->assertSame( '', $evicted_row, 'The evicted draft must really be gone from the row the writers use.' );
		$this->assertNotEmpty( $survivor_row, 'The newer draft must survive - evicting it is the wrong-order bug.' );
		$this->assertSame( 'STRAY-RAW-ROW', $stray, 'An unfiltered lookalike is not ours on this install and must be left alone.' );
	}

	/**
	 * The non-base64 strip must not eat a member's prose.
	 *
	 * That rule's payload class has to allow ordinary text, so before it was
	 * anchored to attribute context a plain-text mention of a data: URI deleted
	 * everything up to the next quote or `>` - and in prose containing neither,
	 * to the end of the draft. A member writing a forum post ABOUT data URIs
	 * silently lost the rest of it on autosave, and the loss became permanent
	 * once they restored that draft and published (PROD-9621).
	 *
	 * The base64 rule above it is deliberately NOT anchored: its payload class
	 * is `[A-Za-z0-9+/=]`, which cannot run past a space, and it is the load
	 * bearing protection against the pasted megabyte screenshot that opened
	 * this ticket - narrowing it to attribute values would stop it seeing a
	 * `url(data:…)` inside a style attribute.
	 */
	public function test_strip_data_urls_does_not_eat_surrounding_prose() {
		$untouched = array(
			'prose'         => 'You can inline an image with data:image/svg+xml, and the browser renders it. Here is the rest of the post.',
			'apostrophe'    => 'Try data:text/plain, and you will see it works. It\'s the simplest example.',
			'inline code'   => "Paste this:\n`data:application/json,{\"a\":1}`\nEverything below should survive.",
			'markup around' => '<a href="/x">link</a> then data:image/gif,R0lGOD in prose, then <b>bold</b> and more.',
		);

		foreach ( $untouched as $label => $input ) {
			$this->assertSame(
				$input,
				bb_draft_strip_data_urls( $input ),
				sprintf( 'A data: URI outside an attribute is prose, not a payload - "%s" must come back byte-identical.', $label )
			);
		}

		// And every real attribute shape must still be stripped, including the
		// two the old single pattern happened to cover and the unquoted one it
		// stopped short on.
		$stripped = array(
			'double quoted' => 'a <img src="data:image/svg+xml,<svg onload=alert(1)/>"> b',
			'single quoted' => "a <img src='data:image/svg+xml;utf8,%3Csvg%3E'> b",
			'unquoted'      => 'a <img src=data:image/gif,R0lGOD> b',
		);

		foreach ( $stripped as $label => $input ) {
			$out = bb_draft_strip_data_urls( $input );

			$this->assertStringNotContainsString( 'data:', $out, sprintf( 'The %s attribute payload must be stripped.', $label ) );
			$this->assertStringNotContainsString( 'R0lGOD', $out, sprintf( 'The %s attribute payload must be stripped.', $label ) );
		}

		// A quoted payload containing `>` (every inline SVG) must run to the
		// closing quote, not stop at the first angle bracket and leave a tail.
		$svg = '<img src="data:image/svg+xml,<svg><rect/></svg>" alt="x">';
		$this->assertStringNotContainsString( 'rect', bb_draft_strip_data_urls( $svg ), 'The payload must run to the attribute delimiter, not to the first `>`.' );
	}
}
