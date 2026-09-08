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
		update_site_option( 'bb_draft_cleanup_cursor', $u1_row_id );

		$result = bb_drafts_delete_expired( 0 );

		$this->assertTrue( $result['complete'] );
		$this->assertTrue( metadata_exists( 'user', $u1, 'draft_user' ), 'Rows before the persisted cursor were handled by the interrupted slice - not re-scanned.' );
		$this->assertFalse( metadata_exists( 'user', $u2, 'draft_user' ), 'Rows after the cursor are reached by the resumed slice.' );
		$this->assertFalse( get_site_option( 'bb_draft_cleanup_cursor' ), 'A completed pass clears the cursor so the next daily run starts fresh.' );
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

		delete_site_option( 'bb_draft_cleanup_cursor' );
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
	 * M1: a filter scoped to the draft keys leaves the internal probe key
	 * unchanged, so bb_draft_meta_key_wrap()'s identity short-circuit used to
	 * report invertible=true while every real stored key was actually wrapped -
	 * silently disabling the cap, eviction, expiry and healing. The wrap must
	 * VERIFY against the real canonical keys even in the identity case, and
	 * decline (invertible=false) when the probe-derived wrap does not describe
	 * them, so the sweep never deletes on a guess.
	 */
	public function test_draft_scoped_filter_keeps_the_maintenance_layer_working() {
		$user_id = self::factory()->user->create();

		add_filter( 'bp_get_user_meta_key', array( $this, 'filter_draft_scoped_user_meta_key' ) );

		// Stored through the writer, so it lands under the WRAPPED key.
		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => 'expired' ),
				'_draft_saved_at' => 100,
			)
		);

		$wrap  = bb_draft_meta_key_wrap();
		$batch = bb_draft_get_rows_batch( 0, 200, false );

		// The wrap follows the real draft key, so it is derivable and correct -
		// NOT the silent identity that used to make everything inert (M1).
		$this->assertTrue( $wrap['invertible'], 'A pure draft-scoped wrap must be invertible, not fall through to identity.' );
		$this->assertSame( 'bbdraft_', $wrap['prefix'], 'The wrap prefix must be derived from the real filtered key.' );
		$this->assertSame( '', $wrap['suffix'] );

		// The sweep now SEES the row and reports its LOGICAL key, so the cap,
		// eviction, expiry and healing are no longer disabled.
		$this->assertCount( 1, $batch['rows'], 'The maintenance scan must see the stored draft under a draft-scoped filter.' );
		$this->assertSame( 'draft_user', $batch['rows'][0]['meta_key'], 'The scan must map the wrapped key back to its logical form.' );

		// End-to-end: the expiry sweep actually removes the expired draft.
		add_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );
		$result = bb_drafts_delete_expired( 0 );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );

		remove_filter( 'bp_get_user_meta_key', array( $this, 'filter_draft_scoped_user_meta_key' ) );

		$this->assertSame( 1, $result['deleted'], 'The expiry sweep must actually delete the expired draft, proving the layer is not inert.' );
	}

	/**
	 * Retain drafts for one day (for expiry tests).
	 *
	 * @return int
	 */
	public function filter_one_day_retention() {
		return 1;
	}

	/**
	 * Count of writes to the aggregate forum draft row (for M4).
	 *
	 * @var int
	 */
	protected $agg_row_writes = 0;

	/**
	 * Record a write to the aggregate forum draft row.
	 *
	 * @param int    $meta_id   Meta row ID.
	 * @param int    $object_id User ID.
	 * @param string $meta_key  Meta key.
	 * @return void
	 */
	/**
	 * Count of bb_draft_cleanup_lock (re)sets (for M6).
	 *
	 * @var int
	 */
	protected $cleanup_lock_sets = 0;

	protected $reference_scans = 0;

	/**
	 * Count a cleanup-lock set/refresh.
	 *
	 * @param mixed $value Transient value.
	 * @return mixed The value unchanged.
	 */
	public function count_cleanup_lock_set( $value ) {
		$this->cleanup_lock_sets++;

		return $value;
	}

	public function count_agg_row_write( $meta_id, $object_id, $meta_key ) {
		if ( bp_get_user_meta_key( 'bb_user_topic_reply_draft' ) === $meta_key ) {
			$this->agg_row_writes++;
		}
	}

	/**
	 * M4: the expiry sweep must remove all expired inner drafts of the
	 * aggregate row in ONE write, not one full-row write per inner draft.
	 */
	public function test_expiry_sweep_disposes_forum_inner_drafts_in_one_write() {
		$this->isolate_draft_maintenance();

		$user_id = self::factory()->user->create();
		$old     = time() - ( 40 * DAY_IN_SECONDS );
		$now     = time();

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_reply_1' => array( 'data_key' => 'draft_reply_1', '_draft_saved_at' => $old, 'data' => array( 'bbp_reply_content' => 'expired 1' ) ),
				'draft_reply_2' => array( 'data_key' => 'draft_reply_2', '_draft_saved_at' => $old, 'data' => array( 'bbp_reply_content' => 'expired 2' ) ),
				'draft_reply_3' => array( 'data_key' => 'draft_reply_3', '_draft_saved_at' => $old, 'data' => array( 'bbp_reply_content' => 'expired 3' ) ),
				'draft_reply_9' => array( 'data_key' => 'draft_reply_9', '_draft_saved_at' => $now, 'data' => array( 'bbp_reply_content' => 'fresh' ) ),
			)
		);

		$this->agg_row_writes = 0;
		add_action( 'updated_user_meta', array( $this, 'count_agg_row_write' ), 10, 3 );
		add_action( 'added_user_meta', array( $this, 'count_agg_row_write' ), 10, 3 );

		$result = bb_drafts_delete_expired( 0 );

		remove_action( 'updated_user_meta', array( $this, 'count_agg_row_write' ), 10 );
		remove_action( 'added_user_meta', array( $this, 'count_agg_row_write' ), 10 );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame( 3, $result['deleted'], 'The three expired inner drafts must be removed.' );
		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'draft_reply_9', $stored, 'The fresh inner draft must survive.' );
		$this->assertArrayNotHasKey( 'draft_reply_1', $stored );
		$this->assertSame(
			1,
			$this->agg_row_writes,
			'All expired inner drafts must be removed in ONE row write, not one per draft.'
		);
	}

	/**
	 * M6: the cleanup lock must be refreshed each window, so an unlimited CLI
	 * drain that outlasts the 5-minute TTL keeps holding it instead of letting
	 * the daily cron in to share the cursor.
	 */
	public function test_expiry_sweep_refreshes_its_lock_each_window() {
		$this->isolate_draft_maintenance();

		// More than one scan window (200) so the sweep loops at least twice.
		for ( $i = 1; $i <= 250; $i++ ) {
			bp_update_user_meta(
				self::factory()->user->create(),
				'draft_group_' . $i,
				array( 'data_key' => 'draft_group_' . $i, '_draft_saved_at' => time(), 'data' => array( 'content' => 'x' ) )
			);
		}

		$this->cleanup_lock_sets = 0;
		add_filter( 'pre_set_site_transient_bb_draft_cleanup_lock', array( $this, 'count_cleanup_lock_set' ) );

		bb_drafts_delete_expired( 0 );

		remove_filter( 'pre_set_site_transient_bb_draft_cleanup_lock', array( $this, 'count_cleanup_lock_set' ) );

		$this->assertGreaterThanOrEqual(
			2,
			$this->cleanup_lock_sets,
			'The lock must be set once at the top and refreshed at least once inside the window loop.'
		);
	}

	/**
	 * Make a draft-stamped, unsaved attachment with a back-dated post date.
	 *
	 * @param int $owner_id Attachment author.
	 * @param int $days_old How many days back to date it.
	 * @return int Attachment ID.
	 */
	protected function make_stamped_unsaved_attachment( $owner_id, $days_old = 40 ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'post_author' => $owner_id,
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', time() - ( $days_old * DAY_IN_SECONDS ) ),
				'post_date'     => gmdate( 'Y-m-d H:i:s', time() - ( $days_old * DAY_IN_SECONDS ) ),
			)
		);
		update_post_meta( $id, 'bp_media_saved', '0' );
		update_post_meta( $id, 'bb_media_draft', 1 );

		return (int) $id;
	}

	/**
	 * M3: a stamp left by a cap-refused save, referenced by no stored draft,
	 * must be released so the orphan cron can finally reap the file - while a
	 * stamp a stored draft still references is left alone.
	 */
	public function test_orphaned_draft_stamps_are_released_for_unreferenced_attachments() {
		$user_id = self::factory()->user->create();

		$orphan   = $this->make_stamped_unsaved_attachment( $user_id ); // referenced by nothing
		$in_draft = $this->make_stamped_unsaved_attachment( $user_id ); // referenced by a stored draft

		// A stored forum draft that references $in_draft.
		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_discussion_11' => array(
					'data_key'        => 'draft_discussion_11',
					'_draft_saved_at' => time(),
					'data'            => array( 'bbp_media' => wp_json_encode( array( array( 'id' => $in_draft ) ) ) ),
				),
			)
		);

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );
		$result = bb_drafts_release_orphaned_draft_stamps( 0 );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );

		$this->assertSame( 1, $result['released'], 'Exactly the unreferenced stamped attachment must be released.' );
		$this->assertSame(
			'',
			get_post_meta( $orphan, 'bb_media_draft', true ),
			'The unreferenced stamp must be released so the orphan cron can reap the file.'
		);
		$this->assertSame(
			'1',
			(string) get_post_meta( $in_draft, 'bb_media_draft', true ),
			'An attachment a stored draft still references must keep its protection.'
		);
	}

	/**
	 * M3: with expiry disabled, drafts are kept forever, so their attachment
	 * stamps must never be swept.
	 */
	public function test_orphaned_stamp_sweep_is_off_when_expiry_is_disabled() {
		$user_id = self::factory()->user->create();
		$orphan  = $this->make_stamped_unsaved_attachment( $user_id );

		add_filter( 'bb_draft_retention_days', '__return_zero' );
		$result = bb_drafts_release_orphaned_draft_stamps( 0 );
		remove_filter( 'bb_draft_retention_days', '__return_zero' );

		$this->assertSame( 0, $result['released'] );
		$this->assertSame( '1', (string) get_post_meta( $orphan, 'bb_media_draft', true ) );
	}

	/**
	 * M3 HIGH: the sweep must not stall behind >500 REFERENCED stamped
	 * attachments - the bare LIMIT 500 with no order returned the same
	 * referenced rows forever and released nothing. The cursor must carry the
	 * scan past them to the real orphans.
	 */
	public function test_orphaned_stamp_sweep_reaches_orphans_past_500_referenced() {
		$this->isolate_draft_maintenance();

		$user_id = self::factory()->user->create();

		// One aggregate row referencing many low-ID stamped attachments, plus a
		// single higher-ID orphan the old LIMIT 500 window could never reach.
		$referenced_entries = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$ref = $this->make_stamped_unsaved_attachment( $user_id );
			$referenced_entries[ 'draft_reply_' . ( $i + 1 ) ] = array(
				'data_key'        => 'draft_reply_' . ( $i + 1 ),
				'_draft_saved_at' => time(),
				'data'            => array( 'bbp_media' => wp_json_encode( array( array( 'id' => $ref ) ) ) ),
			);
		}
		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', $referenced_entries );

		// The orphan is created AFTER the referenced ones, so it has a higher ID
		// and would sort last - exactly where a no-order LIMIT window drops it.
		$orphan = $this->make_stamped_unsaved_attachment( $user_id );

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );
		// Tiny page size forces the cursor to iterate, exercising the resume.
		add_filter( 'bb_draft_stamp_sweep_batch_size', array( $this, 'filter_stamp_sweep_limit_two' ) );
		$result = bb_drafts_release_orphaned_draft_stamps( 0 );
		remove_filter( 'bb_draft_stamp_sweep_batch_size', array( $this, 'filter_stamp_sweep_limit_two' ) );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );

		$this->assertSame( 1, $result['released'], 'Only the orphan must be released.' );
		$this->assertSame( '', (string) get_post_meta( $orphan, 'bb_media_draft', true ), 'The higher-ID orphan must be reached past the referenced ones.' );
	}

	/**
	 * M6, stamp sweep: the lock must be refreshed each window, so an unlimited
	 * CLI drain that outlasts the 5-minute TTL keeps holding it instead of
	 * letting the daily cron in to share the cursor. The sibling expiry sweep
	 * had this; the stamp sweep set the lock once and never again.
	 */
	public function test_orphan_stamp_sweep_refreshes_its_lock_each_window() {
		$this->isolate_draft_maintenance();

		$user_id = self::factory()->user->create();

		// Six unreferenced old orphans + batch size two => three release windows,
		// so the loop must refresh the lock more than the single entry set.
		for ( $i = 0; $i < 6; $i++ ) {
			$this->make_stamped_unsaved_attachment( $user_id );
		}

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );
		add_filter( 'bb_draft_stamp_sweep_batch_size', array( $this, 'filter_stamp_sweep_limit_two' ) );

		$this->cleanup_lock_sets = 0;
		add_filter( 'pre_set_transient_bb_draft_stamp_sweep_lock', array( $this, 'count_cleanup_lock_set' ) );

		bb_drafts_release_orphaned_draft_stamps( 0 );

		remove_filter( 'pre_set_transient_bb_draft_stamp_sweep_lock', array( $this, 'count_cleanup_lock_set' ) );
		remove_filter( 'bb_draft_stamp_sweep_batch_size', array( $this, 'filter_stamp_sweep_limit_two' ) );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );

		$this->assertGreaterThanOrEqual(
			2,
			$this->cleanup_lock_sets,
			'The stamp sweep must set the lock once at entry and refresh it inside the window loop, or an unlimited CLI drain loses the lock mid-run.'
		);
	}

	/**
	 * The reference scan must signal a partial scan (false) rather than hand a
	 * partial set to the release loop - releasing against an incomplete map
	 * would free an attachment a not-yet-scanned draft still holds. Driven
	 * deterministically by a start time already past the budget.
	 */
	public function test_reference_scan_reports_incompletion_when_over_budget() {
		$user_id = self::factory()->user->create();

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_discussion_11' => array( 'data_key' => 'draft_discussion_11', '_draft_saved_at' => time(), 'data' => array( 'bbp_topic_content' => 'x' ) ),
			)
		);

		$result = bb_drafts_collect_referenced_attachment_ids( 1, time() - 100 );

		$this->assertFalse(
			$result,
			'A scan that cannot finish inside its budget must return false, never a partial referenced set.'
		);
	}

	/**
	 * M3 (regression): the sweep must complete its reference scan regardless of
	 * the run budget. The budget was passed to the scan, so on a site whose scan
	 * exceeds the daily 10s it timed out, released nothing, and - with no
	 * continuation - restarted from zero every day, leaving the leak permanent.
	 * The scan is now unbudgeted; only the release loop is budgeted (and resumes
	 * via its persisted cursor). Under a small positive budget the referenced
	 * attachment is still found and kept while the orphan is released.
	 */
	public function test_orphan_stamp_sweep_completes_scan_regardless_of_run_budget() {
		$this->isolate_draft_maintenance();

		$user_id  = self::factory()->user->create();
		$orphan   = $this->make_stamped_unsaved_attachment( $user_id );
		$in_draft = $this->make_stamped_unsaved_attachment( $user_id );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_discussion_11' => array( 'data_key' => 'draft_discussion_11', '_draft_saved_at' => time(), 'data' => array( 'bbp_media' => wp_json_encode( array( array( 'id' => $in_draft ) ) ) ) ),
			)
		);

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );
		$result = bb_drafts_release_orphaned_draft_stamps( 1 );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );

		$this->assertSame(
			'',
			(string) get_post_meta( $orphan, 'bb_media_draft', true ),
			'The orphan must be released even under a small run budget.'
		);
		$this->assertSame(
			'1',
			(string) get_post_meta( $in_draft, 'bb_media_draft', true ),
			'A referenced attachment must be kept - proving the scan ran to completion, not a budget-truncated slice.'
		);
	}

	/**
	 * The unbudgeted reference scan runs FIRST and can outlast the 5-minute lock
	 * TTL on a large library; it must refresh the lock each window too, not only
	 * the release loop after it. Otherwise the lock expires mid-scan and a second
	 * run starts, reopening the cursor-clobber race - just relocated to the scan.
	 */
	public function test_orphan_stamp_sweep_refreshes_its_lock_during_the_scan() {
		$this->isolate_draft_maintenance();

		// Three draft ROWS + a scan page size of two => the reference scan spans
		// more than one window. No orphan attachments, so the release loop runs
		// zero windows and only the scan phase can refresh the lock beyond entry.
		for ( $i = 0; $i < 3; $i++ ) {
			bp_update_user_meta(
				self::factory()->user->create(),
				'draft_user',
				array( 'data_key' => 'draft_user', '_draft_saved_at' => time(), 'data' => array( 'content' => 'x' ) )
			);
		}

		add_filter( 'bb_draft_reference_scan_batch_size', array( $this, 'return_two' ) );

		$this->cleanup_lock_sets = 0;
		add_filter( 'pre_set_transient_bb_draft_stamp_sweep_lock', array( $this, 'count_cleanup_lock_set' ) );

		bb_drafts_release_orphaned_draft_stamps( 0 );

		remove_filter( 'pre_set_transient_bb_draft_stamp_sweep_lock', array( $this, 'count_cleanup_lock_set' ) );
		remove_filter( 'bb_draft_reference_scan_batch_size', array( $this, 'return_two' ) );

		$this->assertGreaterThanOrEqual(
			2,
			$this->cleanup_lock_sets,
			'The reference scan must refresh the lock each window, or a scan longer than the TTL loses the lock before the release loop starts.'
		);
	}

	/**
	 * D2: the orphan-stamp sweep queries per-site attachment tables
	 * ($wpdb->posts / $wpdb->postmeta), so it MUST run on every blog - it is
	 * wired to the per-site `bb_draft_stamp_release_hook`, never the root-only
	 * `bb_draft_cleanup_hook`. The earlier root-only wiring left every subsite's
	 * stamped orphans permanently uncollectable. The usermeta expiry sweep, by
	 * contrast, stays on the root-only hook because usermeta is network-global.
	 */
	public function test_orphan_stamp_sweep_runs_on_the_per_site_hook_not_the_root_only_hook() {
		// The sweep is on the per-site hook.
		$this->assertNotFalse(
			has_action( 'bb_draft_stamp_release_hook', 'bb_drafts_release_orphaned_draft_stamps' ),
			'The orphan-stamp sweep must be wired to the per-site bb_draft_stamp_release_hook.'
		);

		// The sweep is NOT on the root-only hook (the D2 bug).
		$this->assertFalse(
			has_action( 'bb_draft_cleanup_hook', 'bb_drafts_release_orphaned_draft_stamps' ),
			'The orphan-stamp sweep must NOT be wired to the root-only bb_draft_cleanup_hook - that never reaches subsite attachments.'
		);

		// The usermeta expiry sweep stays on the root-only hook (it is network-global).
		$this->assertNotFalse(
			has_action( 'bb_draft_cleanup_hook', 'bb_drafts_delete_expired' ),
			'The usermeta expiry sweep must remain on the root-only bb_draft_cleanup_hook.'
		);
	}

	/**
	 * D2: bb_drafts_schedule_cleanup() must schedule the per-site stamp-release
	 * event unconditionally (before the root-blog guard), so every blog's cron
	 * carries it. The root-only expiry event stays behind the guard.
	 */
	public function test_schedule_cleanup_registers_the_per_site_stamp_release_event() {
		wp_clear_scheduled_hook( 'bb_draft_stamp_release_hook' );
		wp_clear_scheduled_hook( 'bb_draft_cleanup_hook' );

		bb_drafts_schedule_cleanup();

		$this->assertNotFalse(
			wp_next_scheduled( 'bb_draft_stamp_release_hook' ),
			'bb_drafts_schedule_cleanup() must schedule the per-site stamp-release event.'
		);

		wp_clear_scheduled_hook( 'bb_draft_stamp_release_hook' );
		wp_clear_scheduled_hook( 'bb_draft_cleanup_hook' );
	}

	/**
	 * Counts how many times the reference scan runs (its batch-size filter fires
	 * exactly once per scan invocation).
	 */
	public function count_reference_scan( $value ) {
		++$this->reference_scans;

		return $value;
	}

	/**
	 * H3: the reference scan reads network-global draft usermeta, so its answer
	 * is identical on every blog. Now that the sweep runs per-site (D2), the set
	 * is cached network-wide and reused: a second sweep with nothing changed must
	 * NOT re-scan, and stamping a new attachment (a new reference) must drop the
	 * cache so the next sweep rescans - the cache is only ever a superset of the
	 * live set, never a subset, so it can leak a stamp for one cycle but never
	 * release an in-use attachment.
	 */
	public function test_reference_scan_is_cached_network_wide_and_invalidated_on_stamp() {
		$this->isolate_draft_maintenance();

		$user_id  = self::factory()->user->create();
		$orphan   = $this->make_stamped_unsaved_attachment( $user_id );
		$in_draft = $this->make_stamped_unsaved_attachment( $user_id );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				'draft_discussion_11' => array(
					'data_key'        => 'draft_discussion_11',
					'_draft_saved_at' => time(),
					'data'            => array( 'bbp_media' => wp_json_encode( array( array( 'id' => $in_draft ) ) ) ),
				),
			)
		);

		add_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );
		$this->reference_scans = 0;
		add_filter( 'bb_draft_reference_scan_batch_size', array( $this, 'count_reference_scan' ) );

		// First sweep: cold cache, so the scan runs and seeds the cache.
		bb_drafts_release_orphaned_draft_stamps( 0 );
		$this->assertSame( 1, $this->reference_scans, 'The first sweep must run the reference scan.' );
		$this->assertIsArray(
			get_site_transient( 'bb_draft_referenced_stamp_ids' ),
			'The first sweep must seed the network-wide referenced-attachment cache.'
		);

		// Second sweep with nothing changed: cache hit, so NO re-scan.
		bb_drafts_release_orphaned_draft_stamps( 0 );
		$this->assertSame( 1, $this->reference_scans, 'A second sweep with an unchanged draft set must reuse the cache, not re-scan.' );

		// Stamping a new attachment adds a reference the cache must not miss.
		$new_attachment = self::factory()->post->create(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'post_author' => $user_id,
			)
		);
		update_post_meta( $new_attachment, 'bp_media_saved', '0' );
		bb_draft_protect_payload_attachments( array( array( array( 'id' => $new_attachment ) ) ), $user_id );

		$this->assertFalse(
			get_site_transient( 'bb_draft_referenced_stamp_ids' ),
			'Stamping a new attachment must invalidate the referenced-attachment cache.'
		);

		// Next sweep: cache is cold again, so the scan runs.
		bb_drafts_release_orphaned_draft_stamps( 0 );
		$this->assertSame( 2, $this->reference_scans, 'After a stamp invalidates the cache, the next sweep must re-scan.' );

		remove_filter( 'bb_draft_reference_scan_batch_size', array( $this, 'count_reference_scan' ) );
		remove_filter( 'bb_draft_retention_days', array( $this, 'filter_one_day_retention' ) );
	}

	/**
	 * The daily expiry sweep and the one-shot migration read-modify-write the
	 * same aggregated usermeta rows, so they must serialize against EACH OTHER,
	 * not only against themselves - otherwise a concurrent run can resurrect a
	 * key one of them just removed. Each now backs off while the other's lock is
	 * held.
	 */
	public function test_expiry_and_oneshot_serialize_against_each_other() {
		$this->isolate_draft_maintenance();

		$user_id = self::factory()->user->create();
		bp_update_user_meta( $user_id, 'draft_user', array( 'data_key' => 'draft_user', '_draft_saved_at' => 100, 'data' => array( 'content' => 'expired' ) ) );

		// Expiry must back off while the one-shot holds its lock, and touch nothing.
		set_site_transient( 'bb_draft_oneshot_lock', 1, 5 * MINUTE_IN_SECONDS );
		$blocked_expiry = bb_drafts_delete_expired( 0 );
		delete_site_transient( 'bb_draft_oneshot_lock' );

		$this->assertTrue( ! empty( $blocked_expiry['locked'] ), 'Expiry must back off while the one-shot lock is held.' );
		$this->assertNotEmpty( bp_get_user_meta( $user_id, 'draft_user', true ), 'A backed-off expiry sweep must not dispose anything.' );

		// One-shot must back off while the expiry holds its lock.
		set_site_transient( 'bb_draft_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );
		$blocked_oneshot = bb_drafts_oneshot_batch( 0 );
		delete_site_transient( 'bb_draft_cleanup_lock' );

		$this->assertTrue( ! empty( $blocked_oneshot['locked'] ), 'One-shot must back off while the expiry lock is held.' );
	}

	/**
	 * A lock collision mid-drain must re-arm the +60s continuation. WP deletes a
	 * single-event cron entry before invoking it, so a bb_draft_cleanup tick that
	 * backs off on the one-shot lock (or its own) would otherwise leave nothing
	 * to resume the persisted cursor until the daily recurring fire, up to 24h.
	 */
	public function test_expiry_rearms_continuation_on_lock_collision() {
		$this->isolate_draft_maintenance();

		// A drain is in progress (a persisted cursor) and no continuation queued.
		update_site_option( 'bb_draft_cleanup_cursor', 12345 );
		$existing = wp_next_scheduled( 'bb_draft_cleanup' );
		if ( $existing ) {
			wp_unschedule_event( $existing, 'bb_draft_cleanup' );
		}

		set_site_transient( 'bb_draft_oneshot_lock', 1, 5 * MINUTE_IN_SECONDS );
		$result = bb_drafts_delete_expired( 0 );
		delete_site_transient( 'bb_draft_oneshot_lock' );

		$this->assertTrue( ! empty( $result['locked'] ), 'The collision must report locked.' );
		$this->assertNotFalse(
			wp_next_scheduled( 'bb_draft_cleanup' ),
			'A lock collision mid-drain must re-arm the continuation, not stall until the daily fire.'
		);

		$queued = wp_next_scheduled( 'bb_draft_cleanup' );
		if ( $queued ) {
			wp_unschedule_event( $queued, 'bb_draft_cleanup' );
		}
	}

	/**
	 * Budget enforcement must not report success when eviction could not bring
	 * the total under the cap. The loop breaks on success and otherwise falls
	 * through, so a run where every remaining candidate fails to dispose (or
	 * there is too little evictable content - here the new draft alone exceeds
	 * the cap) used to still return allowed => true, silently letting the soft
	 * per-user cap be exceeded.
	 */
	public function test_budget_refuses_when_eviction_cannot_reach_the_cap() {
		$user_id = self::factory()->user->create();

		// One evictable candidate.
		bp_update_user_meta( $user_id, 'draft_group_1', array( 'data_key' => 'draft_group_1', '_draft_saved_at' => 100, 'data' => array( 'content' => str_repeat( 'a', 300 ) ) ) );

		// Cap below the NEW draft's own size, so no amount of eviction fits it.
		add_filter( 'bb_draft_user_total_max_size', array( $this, 'return_fifty' ) );
		$result = bb_draft_enforce_user_budget( $user_id, 'draft_user', 500 );
		remove_filter( 'bb_draft_user_total_max_size', array( $this, 'return_fifty' ) );

		$this->assertFalse(
			$result['allowed'],
			'Budget must refuse when eviction cannot bring the total under the cap.'
		);
		$this->assertContains(
			'draft_group_1',
			$result['evicted'],
			'Premise: eviction was attempted (the candidate was evicted) - the total simply could not fit.'
		);
	}

	/**
	 * @return int
	 */
	public function return_fifty() {
		return 50;
	}

	/**
	 * @return int
	 */
	public function return_two() {
		return 2;
	}

	/**
	 * @return int
	 */
	public function filter_stamp_sweep_limit_two() {
		return 2;
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

		// The synchronous upgrade slice now hands the stage-2 aggregate to a
		// cron continuation rather than running it in the admin request
		// (reviewer: stage-2 unbounded), so completion is signalled once that
		// continuation drains - drive it to completion the way cron would.
		$this->assertNotEmpty( wp_next_scheduled( 'bb_draft_oneshot' ), 'Once the window has passed the slice must run and queue the continuation.' );

		$guard_iterations = 0;
		do {
			$guard_iterations++;
			$guard_result = bb_drafts_oneshot_batch( 0 );
		} while ( empty( $guard_result['complete'] ) && $guard_iterations < 20 );

		$this->assertTrue( (bool) get_option( 'bb_draft_oneshot_done' ), 'The pass must complete once its continuation drains.' );

		$guard_scheduled = wp_next_scheduled( 'bb_draft_oneshot' );
		if ( $guard_scheduled ) {
			wp_unschedule_event( $guard_scheduled, 'bb_draft_oneshot' );
		}

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

		delete_site_option( 'bb_draft_cleanup_cursor' );

		// M12: the lock is network-scoped so a subsite `wp bb drafts cleanup`
		// run and the root cron serialize against the one shared usermeta row
		// set. A per-site transient of the same name must therefore NOT block
		// the sweep - if it did, the lock would be per-site and the multisite
		// race the network scope exists to prevent would be back.
		set_transient( 'bb_draft_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );
		$not_blocked = bb_drafts_delete_expired( 0 );
		$this->assertTrue( empty( $not_blocked['locked'] ), 'A per-site lock must not block the network-scoped sweep.' );
		delete_transient( 'bb_draft_cleanup_lock' );

		// Re-stamp the draft the unblocked run above just collected, so the
		// held-lock assertions below start from a populated row again.
		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'data'            => array( 'content' => 'expired' ),
				'_draft_saved_at' => 100,
			)
		);
		delete_site_option( 'bb_draft_cleanup_cursor' );

		set_site_transient( 'bb_draft_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );

		$blocked = bb_drafts_delete_expired( 0 );

		$this->assertTrue( ! empty( $blocked['locked'] ), 'A sweep finding the lock held must report it.' );
		$this->assertSame( 0, $blocked['deleted'] );
		$this->assertFalse( $blocked['complete'], 'The work is not done, so the run must not claim completion.' );
		$this->assertNotEmpty( bp_get_user_meta( $user_id, 'draft_user', true ), 'A locked-out run must not touch anything.' );

		delete_site_transient( 'bb_draft_cleanup_lock' );

		$ran = bb_drafts_delete_expired( 0 );

		$this->assertTrue( empty( $ran['locked'] ) );
		$this->assertTrue( $ran['complete'] );
		$this->assertSame( '', (string) bp_get_user_meta( $user_id, 'draft_user', true ), 'Once the lock is free the expired draft is collected.' );
		$this->assertFalse( get_site_transient( 'bb_draft_cleanup_lock' ), 'The lock must be released when the sweep returns.' );
	}

	/**
	 * Disabling expiry must still release the lock.
	 */
	public function test_expiry_sweep_releases_the_lock_when_expiry_is_disabled() {
		// Isolate: this test runs a pass over the whole usermeta table.
		$this->isolate_draft_maintenance();

		delete_site_transient( 'bb_draft_cleanup_lock' );
		add_filter( 'bb_draft_retention_days', '__return_zero' );

		$result = bb_drafts_delete_expired( 0 );

		remove_filter( 'bb_draft_retention_days', '__return_zero' );

		$this->assertTrue( $result['complete'] );
		$this->assertFalse( get_site_transient( 'bb_draft_cleanup_lock' ), 'The early return must not leak the lock, or every later sweep is blocked for 5 minutes.' );
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

		// The synchronous slice defers the stage-2 aggregate to cron, so the
		// continuation is still queued and completion is not yet signalled.
		$this->assertFalse( (bool) get_option( 'bb_draft_oneshot_done' ), 'The synchronous slice must hand stage 2 to the continuation, not run it inline.' );
		$this->assertNotEmpty( wp_next_scheduled( 'bb_draft_oneshot' ), 'The deferred continuation must remain queued.' );

		// Draining the continuation the way cron would completes the pass and
		// withdraws the now-redundant event.
		$queue_iterations = 0;
		do {
			$queue_iterations++;
			$queue_result = bb_drafts_oneshot_batch( 0 );
		} while ( empty( $queue_result['complete'] ) && $queue_iterations < 20 );

		$this->assertTrue( (bool) get_option( 'bb_draft_oneshot_done' ) );
		$this->assertFalse( wp_next_scheduled( 'bb_draft_oneshot' ), 'A completed pass must not leave a pointless cron event behind.' );

		delete_option( 'bb_drafts_cleanup_on_upgrade' );
		delete_option( 'bb_draft_oneshot_done' );
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

		delete_site_option( 'bb_draft_cleanup_cursor' );
		delete_site_transient( 'bb_draft_cleanup_lock' );
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

		delete_site_option( 'bb_draft_cleanup_cursor' );
		delete_option( 'bb_draft_oneshot_state' );
		delete_site_option( 'bb_draft_oneshot_state' );
		delete_option( 'bb_draft_oneshot_done' );
		delete_site_option( 'bb_draft_oneshot_done' );
		delete_option( 'bb_drafts_cleanup_on_upgrade' );
		delete_site_option( 'bb_drafts_cleanup_on_upgrade' );
		delete_site_option( 'bb_draft_cleanup_epoch' );
		delete_site_transient( 'bb_draft_cleanup_lock' );
		delete_site_transient( 'bb_draft_oneshot_lock' );
		delete_transient( 'bb_draft_stamp_sweep_lock' );
		delete_option( 'bb_draft_stamp_sweep_cursor' );
		delete_site_transient( 'bb_draft_referenced_stamp_ids' );

		$scheduled = wp_next_scheduled( 'bb_draft_oneshot' );
		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, 'bb_draft_oneshot' );
		}
	}

	/**
	 * M2: publishing one forum draft must not clobber a sibling another request
	 * wrote inside the publish window.
	 *
	 * The publish handlers read the aggregate row once and wrote the remainder
	 * back with no fresh read, so a stale copy overwrote (or, with the emptied
	 * case now a delete, deleted) a sibling committed meanwhile. The shared
	 * removal helper now drops the cache and re-reads before removing the one
	 * key. Simulated deterministically: prime the cache, write a new sibling to
	 * STORAGE only (leaving the cache stale), then remove the published key and
	 * assert the sibling survives.
	 */
	public function test_publishing_a_draft_keeps_a_concurrently_written_sibling() {
		global $wpdb;

		$user_id  = self::factory()->user->create();
		$meta_key = 'bb_user_topic_reply_draft';

		$published_key = 'draft_discussion_11';
		$sibling_key   = 'draft_discussion_22';
		$concurrent_key = 'draft_reply_33';

		// Initial stored row: the key being published plus one existing sibling.
		bp_update_user_meta(
			$user_id,
			$meta_key,
			array(
				$published_key => array( 'data_key' => $published_key, 'data' => array( 'bbp_topic_content' => 'publishing this' ) ),
				$sibling_key   => array( 'data_key' => $sibling_key, 'data' => array( 'bbp_topic_content' => 'existing sibling' ) ),
			)
		);

		// Prime the object cache with that shape.
		bp_get_user_meta( $user_id, $meta_key, true );

		// A concurrent request adds a THIRD draft, straight to storage, WITHOUT
		// busting the object cache - the exact stale-cache window the fix closes.
		$stored_key = bp_get_user_meta_key( $meta_key );
		$new_row    = array(
			$published_key  => array( 'data_key' => $published_key, 'data' => array( 'bbp_topic_content' => 'publishing this' ) ),
			$sibling_key    => array( 'data_key' => $sibling_key, 'data' => array( 'bbp_topic_content' => 'existing sibling' ) ),
			$concurrent_key => array( 'data_key' => $concurrent_key, 'data' => array( 'bbp_reply_content' => 'written by another tab' ) ),
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- deliberately leaves the object cache stale to reproduce the race.
		$wpdb->update(
			$wpdb->usermeta,
			array( 'meta_value' => maybe_serialize( $new_row ) ),
			array( 'user_id' => $user_id, 'meta_key' => $stored_key )
		);

		// Publish removes only $published_key.
		bb_forums_delete_published_draft_key( $user_id, $published_key );

		wp_cache_delete( $user_id, 'user_meta' );
		$stored = bp_get_user_meta( $user_id, $meta_key, true );

		$this->assertIsArray( $stored, 'The row must still exist.' );
		$this->assertArrayNotHasKey( $published_key, $stored, 'The published draft must be removed.' );
		$this->assertArrayHasKey( $sibling_key, $stored, 'The pre-existing sibling must survive.' );
		$this->assertArrayHasKey(
			$concurrent_key,
			$stored,
			'A sibling written by a concurrent request inside the publish window must NOT be clobbered.'
		);
	}

	/**
	 * M2 LOW: publishing a forum draft must flush the per-user size memo, in
	 * step with every other draft mutator. The memo is a per-request lower bound
	 * on the member's usermeta bytes; if the publish path removes a draft but
	 * leaves the memo primed, later size-gated logic in the SAME request (a
	 * follow-up autosave's budget check) reads a total that still counts the
	 * bytes just published away.
	 */
	public function test_publishing_a_draft_flushes_the_user_meta_size_memo() {
		$user_id  = self::factory()->user->create();
		$meta_key = 'bb_user_topic_reply_draft';

		$published_key = 'draft_discussion_11';
		$sibling_key   = 'draft_discussion_22';

		bp_update_user_meta(
			$user_id,
			$meta_key,
			array(
				// A large inner draft so removing it moves the byte total well
				// past any measurement noise.
				$published_key => array( 'data_key' => $published_key, 'data' => array( 'bbp_topic_content' => str_repeat( 'x', 4096 ) ) ),
				$sibling_key   => array( 'data_key' => $sibling_key, 'data' => array( 'bbp_topic_content' => 'kept' ) ),
			)
		);

		// Prime the memo for this user (this is what a preceding autosave in the
		// same request would have done).
		$before = bb_draft_get_user_meta_sizes( $user_id );

		// Publish removes the large key.
		bb_forums_delete_published_draft_key( $user_id, $published_key );

		// Read again WITHOUT flushing by hand: a correct publish path already
		// flushed, so this re-measures the now-smaller row. A publish that
		// skipped the flush returns the stale primed total.
		$after = bb_draft_get_user_meta_sizes( $user_id );

		$this->assertLessThan(
			$before['total'],
			$after['total'],
			'Publishing a draft must flush the size memo so a later same-request read sees the smaller total.'
		);
	}

	public function filter_prefix_user_meta_key( $key ) {
		return 'bbtest_' . $key;
	}

	public function filter_hash_user_meta_key( $key ) {
		return 'bbtest_' . md5( $key );
	}

	/**
	 * Wrap ONLY the draft keys, leaving every other key (the internal probe
	 * included) untouched - the natural way to scope a bp_get_user_meta_key
	 * filter to drafts (M1).
	 *
	 * @param string $key Meta key.
	 * @return string Possibly wrapped key.
	 */
	public function filter_draft_scoped_user_meta_key( $key ) {
		if ( bb_draft_is_draft_meta_key( $key ) ) {
			return 'bbdraft_' . $key;
		}

		return $key;
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
	 * M13: the sibling re-authorization's PUBLISH-RIGHTS half must be
	 * load-bearing, not merely redundant with the view-forum check.
	 *
	 * The existing merge test denies a sibling only via bbp_user_can_view_forum,
	 * and bb_draft_user_can_save_topic_reply_draft() calls that same view check
	 * internally, so removing EITHER gate alone left the test green. Here the
	 * sibling is a TOPIC in a viewable forum with topic-publishing DENIED, while
	 * the primary is a REPLY with reply-publishing allowed - so only the
	 * publish-rights gate can reject the sibling, and removing the helper call
	 * makes the smuggled update land.
	 */
	public function test_all_data_merge_reauthorizes_publish_rights_per_sibling() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_id = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$topic_id = self::factory()->post->create(
			array( 'post_type' => bbp_get_topic_post_type(), 'post_parent' => $forum_id )
		);

		$primary_key = 'draft_reply_' . $topic_id;    // a reply - publishing allowed
		$sibling_key = 'draft_discussion_' . $forum_id; // a topic - publishing DENIED

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array( 'data_key' => $primary_key, 'data' => array( 'bbp_reply_content' => 'reply original' ), '_draft_saved_at' => time() - 60 ),
				$sibling_key => array( 'data_key' => $sibling_key, 'data' => array( 'bbp_topic_content' => 'SIBLING ORIGINAL' ), '_draft_saved_at' => time() - 60 ),
			)
		);

		$_POST    = array();
		$_REQUEST = array();

		$_REQUEST['draft_topic_reply'] = wp_json_encode(
			array( 'data_key' => $primary_key, 'object' => 'reply', 'post_action' => 'update', 'data' => array( 'bbp_reply_content' => 'reply updated' ) )
		);
		$_REQUEST['all_data'] = wp_json_encode(
			array( $sibling_key => array( 'bbp_topic_content' => 'SIBLING SMUGGLED UPDATE' ) )
		);

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		// Views allowed; topic-publishing denied; reply-publishing allowed.
		add_filter( 'bbp_current_user_can_publish_topics', '__return_false' );
		add_filter( 'bbp_current_user_can_publish_replies', '__return_true' );
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();
		try {
			bb_post_topic_reply_draft();
		} catch ( Exception $e ) {
			unset( $e );
		}
		ob_end_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'bbp_current_user_can_publish_replies', '__return_true' );
		remove_filter( 'bbp_current_user_can_publish_topics', '__return_false' );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			'SIBLING ORIGINAL',
			$stored[ $sibling_key ]['data']['bbp_topic_content'],
			'A sibling TOPIC the member may not publish must NOT take the smuggled write - the publish-rights gate must reject it even though the forum is viewable.'
		);
		$this->assertSame(
			'reply updated',
			$stored[ $primary_key ]['data']['bbp_reply_content'],
			'Negative control: the primary reply, which the member may publish, still saves.'
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

	/**
	 * A draft refused by a size cap must still protect the member's uploads.
	 *
	 * `bb_media_draft` is what stops `bp_media_delete_orphaned_attachments()`
	 * hard-deleting a freshly uploaded file six hours later. Applying it only
	 * after every cap accepted meant a member who wrote a long post and then
	 * attached a photo had that photo deleted out from under them: the autosave
	 * was refused for size, the stamp never ran, and the cron reaped the file
	 * while it was still sitting in their composer. The release base stamped
	 * inline and unconditionally; the deferral traded a bounded leak for real
	 * data loss (PROD-9621 BLOCKER-1).
	 *
	 * Browser-verified on bbtesting.com as member5: photo uploaded against a
	 * 156 KB draft, autosave refused with "Your draft is too large to save",
	 * attachment left with no stamp and matching the cron's own predicate.
	 */
	public function test_size_capped_save_still_protects_uploaded_attachments() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$key   = 'draft_discussion_' . $forum;

		$mine     = $this->make_draft_attachment( $user_id );
		$somebody = $this->make_draft_attachment( self::factory()->user->create() );

		$rejected = '';
		$cb       = function ( $uid, $k, $size, $reason ) use ( &$rejected ) {
			$rejected = $reason;
		};
		add_action( 'bb_draft_cap_rejected', $cb, 10, 4 );

		$this->drive_forum_draft_save(
			$key,
			array(
				// Over the 100 KB per-draft cap, which refuses before the
				// attachment normalisation loops are ever reached.
				'bbp_topic_content' => str_repeat( 'x', 150 * KB_IN_BYTES ),
				'bbp_media'         => wp_json_encode( array( array( 'id' => $mine ), array( 'id' => $somebody ) ) ),
			)
		);

		remove_action( 'bb_draft_cap_rejected', $cb, 10 );

		// Premise: the cap really did refuse. Without this the test would pass
		// for a build that simply accepted the oversized draft.
		$this->assertSame( 'per_draft', $rejected, 'The oversized draft must still be refused - the fix must not weaken the cap.' );

		$this->assertSame(
			'1',
			(string) get_post_meta( $mine, 'bb_media_draft', true ),
			'A refused save must still protect the uploads it carried, or the orphan cron deletes the member files.'
		);

		$this->assertSame(
			'',
			(string) get_post_meta( $somebody, 'bb_media_draft', true ),
			'Protection is per owner - a crafted payload must not stamp another member attachment.'
		);
	}

	/**
	 * An attachment in the state a fresh composer upload leaves behind.
	 *
	 * @param int $owner_id Owner user ID.
	 * @return int Attachment ID.
	 */
	protected function make_draft_attachment( $owner_id ) {
		$attachment_id = self::factory()->post->create(
			array(
				'post_type'   => 'attachment',
				'post_author' => $owner_id,
				'post_status' => 'inherit',
			)
		);

		update_post_meta( $attachment_id, 'bp_media_saved', '0' );

		return $attachment_id;
	}

	/**
	 * Drive the forum draft save handler with one payload.
	 *
	 * @param string $data_key Inner draft key.
	 * @param array  $data     Draft data payload.
	 * @return void
	 */
	protected function drive_forum_draft_save( $data_key, $data ) {
		$_POST    = array();
		$_REQUEST = array();

		// wp_slash() because the handler stripslashes() before decoding, exactly
		// as WordPress hands it a real request.
		$_REQUEST['draft_topic_reply'] = wp_slash(
			wp_json_encode(
				array(
					'data_key'    => $data_key,
					'object'      => 'topic',
					'post_action' => 'update',
					'data'        => $data,
				)
			)
		);

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

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
	}

	/**
	 * Budget eviction must credit the element key, not just the entry.
	 *
	 * A serialized row carries every element's KEY as well as its value, so
	 * evicting an inner draft frees the entry plus its key. Counting only the
	 * entry under-credits each eviction, the running total stays above the
	 * truth, and the oldest-first loop keeps going - destroying drafts it did
	 * not need to. This is the same undercount `f643e4d0c8` fixed in
	 * `bb_forums_trim_draft_row()`; the budget is that loop's sibling and was
	 * left behind (PROD-9621).
	 *
	 * The shortfall is dialled through `$new_size` so it lands in the window
	 * where exactly one element key decides whether a third draft dies: two
	 * evictions are enough when the key bytes are counted, three when they are
	 * not. An assertion on the formula alone would stay green with the fix
	 * reverted, which is why this drives the real function instead.
	 */
	public function test_budget_eviction_counts_the_element_key_bytes() {
		$this->isolate_draft_maintenance();

		$user_id = self::factory()->user->create();

		// Keys of identical length so every element costs the same.
		$keys = array( 'draft_discussion_1001', 'draft_discussion_1002', 'draft_discussion_1003', 'draft_discussion_1004' );
		$row  = array();
		$age  = 100;

		foreach ( $keys as $key ) {
			$row[ $key ] = array(
				'data_key'        => $key,
				'data'            => array( 'bbp_topic_content' => str_repeat( 'q', 64 ) ),
				'_draft_saved_at' => $age++,
			);
		}

		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', $row );
		bb_draft_flush_user_meta_sizes( $user_id );

		$entry_bytes = strlen( maybe_serialize( $row[ $keys[0] ] ) );
		$key_bytes   = bb_draft_serialized_key_bytes( $keys[0] );

		$this->assertGreaterThan( 0, $key_bytes, 'Premise: an element key costs bytes, or there is nothing to account for.' );

		$sizes     = bb_draft_get_user_meta_sizes( $user_id );
		$row_bytes = (int) $sizes['drafts']['bb_user_topic_reply_draft'];
		$total_cap = bb_draft_user_total_max_size();

		// Land the shortfall strictly between "two entries" and "two entries
		// plus their two keys", so the key term alone decides the outcome.
		$shortfall = ( 2 * $entry_bytes ) + $key_bytes;
		$new_size  = $total_cap - $row_bytes + $shortfall;

		$result = bb_draft_enforce_user_budget( $user_id, 'draft_user', $new_size );

		$this->assertTrue( $result['allowed'], 'Premise: the save is under the total meta budget.' );
		$this->assertCount(
			2,
			$result['evicted'],
			'Two evictions free the shortfall once each element key is credited; counting only the entry evicts a third draft that did not have to die.'
		);

		// And the survivors must be the NEWEST two, not an arbitrary pair.
		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertArrayNotHasKey( $keys[0], $stored, 'Oldest first.' );
		$this->assertArrayNotHasKey( $keys[1], $stored, 'Oldest first.' );
		$this->assertArrayHasKey( $keys[2], $stored, 'The newer drafts must survive.' );
		$this->assertArrayHasKey( $keys[3], $stored, 'The newer drafts must survive.' );
	}

	/**
	 * An attachment carried only by an all_data sibling must be protected.
	 *
	 * The three per-type normalisation loops that stamp `bb_media_draft` only
	 * ever see the PRIMARY draft entry. The sibling merge replaces a stored
	 * entry's whole `data` with client JSON and ran none of them, so a file
	 * referenced solely by a sibling reached storage unstamped and
	 * bp_media_delete_orphaned_attachments() hard-deleted it while the member
	 * was still drafting with it.
	 *
	 * Reachable on the shipped BuddyBoss theme: one shared reply modal serves
	 * every reply target on the page, and reopening it re-runs the key setup on
	 * the same instance, so `all_draft_data` accumulates several inner keys and
	 * the unload beacon replays them together (browser-verified, PROD-9621).
	 *
	 * The foreign attachment is the control that keeps the stamping honest: the
	 * list is client JSON, so ownership must still be checked per ID.
	 */
	public function test_all_data_sibling_attachments_are_orphan_protected() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$sibling_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$sibling_key = 'draft_discussion_' . $sibling_forum;

		$mine     = $this->make_draft_attachment( $user_id );
		$somebody = $this->make_draft_attachment( self::factory()->user->create() );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array(
					'data_key'        => $primary_key,
					'data'            => array( 'bbp_topic_content' => 'primary original' ),
					'_draft_saved_at' => time() - 60,
				),
				$sibling_key => array(
					'data_key'        => $sibling_key,
					'data'            => array( 'bbp_topic_content' => 'sibling original' ),
					'_draft_saved_at' => time() - 60,
				),
			)
		);

		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			// The primary carries NO attachment, so anything stamped here can
			// only have come from the sibling path.
			array( 'bbp_topic_content' => 'primary updated' ),
			array(
				$sibling_key => array(
					'bbp_topic_content' => 'sibling updated',
					'bbp_media'         => wp_json_encode( array( array( 'id' => $mine ), array( 'id' => $somebody ) ) ),
				),
			)
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		// Premise: the sibling really was merged. Without this the test would
		// also pass for a handler that silently dropped every sibling.
		$this->assertSame(
			'sibling updated',
			$stored[ $sibling_key ]['data']['bbp_topic_content'],
			'The sibling merge must have happened, or this test proves nothing about it.'
		);

		$this->assertSame(
			'1',
			(string) get_post_meta( $mine, 'bb_media_draft', true ),
			'An attachment stored by the sibling merge must be orphan-protected, or the cron deletes a file the stored draft references.'
		);

		$this->assertSame(
			'',
			(string) get_post_meta( $somebody, 'bb_media_draft', true ),
			'Sibling stamping is per owner - a crafted all_data payload must not stamp another member attachment.'
		);
	}

	/**
	 * Cap the per-type attachment count on the sibling-merge path.
	 *
	 * The primary draft entry refuses a save whose media/document/video list
	 * exceeds bb_draft_max_attachments_per_type(). The sibling-merge path gated
	 * only on the merged entry's total serialized byte size, and a minimal
	 * {"id":N} reference is ~15-20 bytes, so a single sibling could carry
	 * thousands of IDs under the byte cap and force one uncached get_post() per
	 * ID in the ownership loop - a self-inflicted DoS / cap bypass. An over-cap
	 * sibling must be skipped (its stored copy kept), exactly like the byte-cap
	 * branch, while a within-cap sibling in the same request still merges.
	 */
	public function test_sibling_merge_is_bounded_by_the_per_type_attachment_cap() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		// Small cap keeps the payload tiny; the production default is 50.
		add_filter( 'bb_draft_max_attachments_per_type', array( $this, 'return_three' ) );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$over_forum    = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$under_forum   = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$over_key    = 'draft_discussion_' . $over_forum;
		$under_key   = 'draft_discussion_' . $under_forum;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array( 'data_key' => $primary_key, 'data' => array( 'bbp_topic_content' => 'primary original' ), '_draft_saved_at' => time() - 60 ),
				$over_key    => array( 'data_key' => $over_key, 'data' => array( 'bbp_topic_content' => 'over original' ), '_draft_saved_at' => time() - 60 ),
				$under_key   => array( 'data_key' => $under_key, 'data' => array( 'bbp_topic_content' => 'under original' ), '_draft_saved_at' => time() - 60 ),
			)
		);

		// Four media refs > cap of three: fits well under the byte cap, so only
		// the count cap can stop it.
		$over_media = wp_json_encode( array(
			array( 'id' => 900001 ),
			array( 'id' => 900002 ),
			array( 'id' => 900003 ),
			array( 'id' => 900004 ),
		) );

		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			array( 'bbp_topic_content' => 'primary updated' ),
			array(
				$over_key  => array( 'bbp_topic_content' => 'over OVERSIZED', 'bbp_media' => $over_media ),
				$under_key => array( 'bbp_topic_content' => 'under updated', 'bbp_media' => wp_json_encode( array( array( 'id' => 900005 ) ) ) ),
			)
		);

		remove_filter( 'bb_draft_max_attachments_per_type', array( $this, 'return_three' ) );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			'over original',
			$stored[ $over_key ]['data']['bbp_topic_content'],
			'A sibling whose attachment count exceeds the per-type cap must be skipped, keeping its stored copy.'
		);

		// Control: a within-cap sibling in the SAME request still merges, so the
		// cap is selective, not a blanket drop of every sibling.
		$this->assertSame(
			'under updated',
			$stored[ $under_key ]['data']['bbp_topic_content'],
			'A within-cap sibling must still merge, or the cap is dropping legal drafts.'
		);
	}

	public function return_three() {
		return 3;
	}

	/**
	 * @return int
	 */
	public function return_800() {
		return 800;
	}

	/**
	 * A merged sibling that the budget trim then EVICTS must not have its
	 * attachment re-stamped. The sibling merge pushes the sibling's attachment
	 * IDs into $stamp_attachment_ids, and the aggregate-row trim can evict that
	 * sibling (its stored _draft_saved_at is old - the merge does not refresh it
	 * on a content-only change - so it sorts oldest, and only the primary key is
	 * protected). The eviction-release loop correctly drops the evicted sibling's
	 * stamp; the unconditional re-stamp loop then re-applied it, permanently
	 * protecting a file nothing stored references. Reachable by ordinary heavy
	 * forum users hitting their draft budget.
	 */
	public function test_evicted_sibling_attachment_is_not_re_stamped() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$sibling_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$sibling_key = 'draft_discussion_' . $sibling_forum;

		// Referenced ONLY by the sibling, so once it is evicted nothing stored
		// points at the attachment and its stamp must stay released.
		$orphaned = $this->make_stamped_unsaved_attachment( $user_id );

		// Small caps so the two-entry aggregate row exceeds the budget and the
		// trim must evict the unprotected sibling (the primary key is protected).
		add_filter( 'bb_draft_max_size', array( $this, 'return_800' ) );
		add_filter( 'bb_draft_user_total_max_size', array( $this, 'return_800' ) );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array( 'data_key' => $primary_key, '_draft_saved_at' => time(), 'data' => array( 'bbp_topic_content' => str_repeat( 'p', 350 ) ) ),
				$sibling_key => array( 'data_key' => $sibling_key, '_draft_saved_at' => 100, 'data' => array( 'bbp_topic_content' => str_repeat( 's', 300 ), 'bbp_media' => wp_json_encode( array( array( 'id' => $orphaned ) ) ) ) ),
			)
		);

		// The beacon replays the sibling with changed content (so it merges and
		// its attachment is pushed into the re-stamp set); the budget trim then
		// evicts it.
		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			array( 'bbp_topic_content' => str_repeat( 'p', 350 ) . ' updated' ),
			array(
				$sibling_key => array( 'bbp_topic_content' => str_repeat( 's', 300 ) . ' changed', 'bbp_media' => wp_json_encode( array( array( 'id' => $orphaned ) ) ) ),
			)
		);

		remove_filter( 'bb_draft_user_total_max_size', array( $this, 'return_800' ) );
		remove_filter( 'bb_draft_max_size', array( $this, 'return_800' ) );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		// Premise: the sibling really was evicted by the trim, or the test proves
		// nothing about the eviction path.
		$this->assertArrayNotHasKey(
			$sibling_key,
			is_array( $stored ) ? $stored : array(),
			'Premise: the budget trim must have evicted the sibling.'
		);

		$this->assertSame(
			'',
			(string) get_post_meta( $orphaned, 'bb_media_draft', true ),
			'An evicted sibling attachment must stay released, not be re-stamped by the unconditional re-stamp loop.'
		);
	}

	/**
	 * An authorization failure on the PRIMARY entry must not kill the whole
	 * request before the sibling merge - the exact GH1 data-loss class, reached
	 * via the auth path instead of the cap path. The unload beacon replays every
	 * key the tab holds, so a member who has just lost access to the primary's
	 * forum (removed from a group, forum made private) can still be carrying a
	 * genuine, independently-authorized update to a sibling draft in a forum
	 * they CAN still see. The auth gate used to wp_send_json_error() outright,
	 * discarding that sibling's last save; it now records the rejection and falls
	 * through to the sibling merge like the cap rejections do.
	 */
	public function test_auth_rejected_primary_still_saves_a_valid_sibling() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$sibling_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$sibling_key = 'draft_discussion_' . $sibling_forum;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$sibling_key => array( 'data_key' => $sibling_key, '_draft_saved_at' => time() - 60, 'data' => array( 'bbp_topic_content' => 'sibling original' ) ),
			)
		);

		// The member can no longer view the PRIMARY's forum, but the sibling's is
		// still readable.
		$this->unreadable_forum_id = $primary_forum;
		add_filter( 'bbp_user_can_view_forum', array( $this, 'filter_block_unreadable_forum' ), 10, 2 );

		// One unload beacon: an unauthorized primary + a valid sibling update.
		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			array( 'bbp_topic_content' => 'primary that can no longer be saved' ),
			array(
				$sibling_key => array( 'bbp_topic_content' => 'sibling UPDATED' ),
			)
		);

		remove_filter( 'bbp_user_can_view_forum', array( $this, 'filter_block_unreadable_forum' ), 10 );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			'sibling UPDATED',
			$stored[ $sibling_key ]['data']['bbp_topic_content'],
			'An auth-rejected primary must not kill the request before the sibling merge - the sibling update must persist (GH1 via the auth path).'
		);

		$this->assertArrayNotHasKey(
			$primary_key,
			$stored,
			'The unauthorized primary entry must not be written.'
		);
	}

	/**
	 * When a sibling draft's attachment list shrinks, the dropped attachment's
	 * bb_media_draft stamp must be released - the same set difference the primary
	 * entry gets. The sibling merge re-stamped what it KEEPS but never released
	 * what it stopped referencing, so a photo removed from a sibling reply kept
	 * its stamp for ever, protected from the orphan cron by a draft that no
	 * longer points at it. An attachment another surviving entry still holds
	 * must be kept.
	 */
	public function test_sibling_merge_releases_a_dropped_attachment_stamp() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$sibling_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$sibling_key = 'draft_discussion_' . $sibling_forum;

		$dropped = $this->make_stamped_unsaved_attachment( $user_id ); // sibling-only, removed this request
		$kept    = $this->make_stamped_unsaved_attachment( $user_id ); // referenced by the primary, must survive

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array( 'data_key' => $primary_key, '_draft_saved_at' => time() - 60, 'data' => array( 'bbp_topic_content' => 'p', 'bbp_media' => wp_json_encode( array( array( 'id' => $kept ) ) ) ) ),
				$sibling_key => array( 'data_key' => $sibling_key, '_draft_saved_at' => time() - 60, 'data' => array( 'bbp_topic_content' => 's', 'bbp_media' => wp_json_encode( array( array( 'id' => $dropped ), array( 'id' => $kept ) ) ) ) ),
			)
		);

		// The sibling now references only $kept - it drops $dropped.
		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			array( 'bbp_topic_content' => 'p updated', 'bbp_media' => wp_json_encode( array( array( 'id' => $kept ) ) ) ),
			array(
				$sibling_key => array( 'bbp_topic_content' => 's updated', 'bbp_media' => wp_json_encode( array( array( 'id' => $kept ) ) ) ),
			)
		);

		// Premise: the sibling really was merged (else the test proves nothing).
		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertSame( 's updated', $stored[ $sibling_key ]['data']['bbp_topic_content'], 'The sibling merge must have happened.' );

		$this->assertSame(
			'',
			(string) get_post_meta( $dropped, 'bb_media_draft', true ),
			'An attachment a sibling stopped referencing must have its stamp released so the orphan cron can reap it.'
		);
		$this->assertSame(
			'1',
			(string) get_post_meta( $kept, 'bb_media_draft', true ),
			'An attachment another surviving entry still references must keep its stamp.'
		);
	}

	/**
	 * Replacing one inner draft must not unstamp a sibling's attachment.
	 *
	 * `bb_draft_release_replaced_attachments()` computed "still held" from the
	 * entry that REPLACED this one and nothing else. The aggregated forum row
	 * keeps every inner topic/reply draft together, and the shared reply modal
	 * carries its content across reply targets, so one attachment is routinely
	 * referenced by several inner drafts at once. Replacing one of them then
	 * released a file a sibling inner draft still pointed at, and the
	 * orphan-cleanup crons hard-deleted it - the precise outcome the function's
	 * own docblock promises it prevents. Browser-verified: all three stamps
	 * disappeared while a sibling draft still referenced all three files.
	 *
	 * `$only_here` is the negative control. Without it this test would stay
	 * green for a build that simply stopped releasing anything at all, which
	 * would leak orphan protection for ever instead of over-releasing it.
	 */
	public function test_replacing_an_inner_draft_keeps_a_sibling_attachment_stamped() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$sibling_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$sibling_key = 'draft_discussion_' . $sibling_forum;

		$shared    = $this->make_draft_attachment( $user_id );
		$only_here = $this->make_draft_attachment( $user_id );

		// Both start protected, exactly as a previous save would have left them.
		update_post_meta( $shared, 'bb_media_draft', 1 );
		update_post_meta( $only_here, 'bb_media_draft', 1 );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array(
					'data_key'        => $primary_key,
					'data'            => array(
						'bbp_topic_content' => 'primary original',
						'bbp_media'         => wp_json_encode( array( array( 'id' => $shared ), array( 'id' => $only_here ) ) ),
					),
					'_draft_saved_at' => time() - 60,
				),
				// The sibling still references the shared attachment.
				$sibling_key => array(
					'data_key'        => $sibling_key,
					'data'            => array(
						'bbp_topic_content' => 'sibling original',
						'bbp_media'         => wp_json_encode( array( array( 'id' => $shared ) ) ),
					),
					'_draft_saved_at' => time() - 60,
				),
			)
		);

		// The member replaces the primary draft with content holding no
		// attachments at all.
		$this->drive_forum_draft_save(
			$primary_key,
			array( 'bbp_topic_content' => 'primary updated, attachments dropped' )
		);

		$this->assertSame(
			'1',
			(string) get_post_meta( $shared, 'bb_media_draft', true ),
			'An attachment another inner draft of the same row still references must keep its orphan protection.'
		);

		// Negative control: an attachment nothing references any more must
		// still be released, or the fix has simply disabled releasing.
		$this->assertSame(
			'',
			(string) get_post_meta( $only_here, 'bb_media_draft', true ),
			'An attachment no stored draft references any more must still be released.'
		);
	}

	/**
	 * Disposing one inner draft must not unstamp a sibling's attachment.
	 *
	 * `bb_draft_dispose()` is the shared removal path - the member's own
	 * "Discard Draft", budget eviction and the expiry sweep all reach it - and
	 * when it drops a single inner key it released that entry's attachments
	 * outright. The rest of the aggregated row survives, and the shared reply
	 * modal carries content across reply targets, so a surviving sibling was
	 * routinely still referencing the released file; the orphan-cleanup crons
	 * then hard-deleted it.
	 *
	 * Browser-verified: discarding one reply draft dropped all three
	 * `bb_media_draft` stamps while the other reply draft still referenced all
	 * three files. `bb_draft_heal_forum_row()` and the forum handler's eviction
	 * branch already got this exclusion right; these were the outliers
	 * (PROD-9621).
	 *
	 * `$only_here` is the negative control: without it the test would stay
	 * green for a build that stopped releasing anything at all, leaking orphan
	 * protection for ever instead of over-releasing it.
	 */
	public function test_disposing_one_inner_draft_keeps_a_sibling_attachment_stamped() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$doomed_key  = 'draft_reply_4001';
		$sibling_key = 'draft_reply_4002';

		$shared    = $this->make_draft_attachment( $user_id );
		$only_here = $this->make_draft_attachment( $user_id );

		update_post_meta( $shared, 'bb_media_draft', 1 );
		update_post_meta( $only_here, 'bb_media_draft', 1 );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$doomed_key  => array(
					'data_key'        => $doomed_key,
					'data'            => array(
						'bbp_reply_content' => 'the draft being discarded',
						'bbp_media'         => wp_json_encode( array( array( 'id' => $shared ), array( 'id' => $only_here ) ) ),
					),
					'_draft_saved_at' => time() - 60,
				),
				$sibling_key => array(
					'data_key'        => $sibling_key,
					'data'            => array(
						'bbp_reply_content' => 'the draft that stays',
						'bbp_media'         => wp_json_encode( array( array( 'id' => $shared ) ) ),
					),
					'_draft_saved_at' => time() - 30,
				),
			)
		);

		$this->assertTrue(
			bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', $doomed_key ),
			'Premise: the dispose must actually have removed the inner draft.'
		);

		// Premise: the sibling really did survive the removal.
		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertArrayHasKey( $sibling_key, $stored, 'The sibling inner draft must survive.' );
		$this->assertArrayNotHasKey( $doomed_key, $stored, 'The disposed inner draft must be gone.' );

		$this->assertSame(
			'1',
			(string) get_post_meta( $shared, 'bb_media_draft', true ),
			'An attachment the surviving sibling still references must keep its orphan protection.'
		);

		// Negative control: nothing references this one any more.
		$this->assertSame(
			'',
			(string) get_post_meta( $only_here, 'bb_media_draft', true ),
			'An attachment no stored draft references any more must still be released.'
		);
	}

	/**
	 * Drive the forum draft save handler with a primary entry and siblings.
	 *
	 * Mirrors drive_forum_draft_save() but also sends the `all_data` map the
	 * unload beacon replays.
	 *
	 * @param string $data_key Primary inner draft key.
	 * @param array  $data     Primary draft data payload.
	 * @param array  $all_data Sibling map, keyed by inner draft key.
	 * @return void
	 */
	protected function drive_forum_draft_save_with_siblings( $data_key, $data, $all_data ) {
		$_POST    = array();
		$_REQUEST = array();

		// wp_slash() because the handler stripslashes() before decoding, exactly
		// as WordPress hands it a real request.
		$_REQUEST['draft_topic_reply'] = wp_slash(
			wp_json_encode(
				array(
					'data_key'    => $data_key,
					'object'      => 'topic',
					'post_action' => 'update',
					'data'        => $data,
				)
			)
		);

		$_REQUEST['all_data'] = wp_slash( wp_json_encode( $all_data ) );

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

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
	}

	/**
	 * The upgrade one-shot must shed the poster before it deletes the draft.
	 *
	 * A one-draft-per-row key has nothing to evict inside the row, so the
	 * healing pass had exactly one move: delete it. `js_preview` is a
	 * canvas.toDataURL() poster frame that nothing depends on surviving a
	 * round trip, and on a legacy row it is routinely almost the whole
	 * payload - measured at 99.8% of a 150 KB row, dropping to 260 bytes once
	 * shed. So the pass destroyed the member's unpublished text to reclaim
	 * space the poster alone was using, at upgrade time, with no notice
	 * (PROD-9621 Q6).
	 *
	 * The shedding that already existed was client-side only (`beb5d52a45`),
	 * which can never reach a row that is already in the database - and
	 * legacy rows are the entire population this one-shot exists to heal.
	 *
	 * Asserts on the CONTENT, not just the row's presence: a build that kept
	 * the row but dropped the text would otherwise pass.
	 */
	public function test_oneshot_sheds_the_poster_instead_of_destroying_the_draft() {
		$user_id = self::factory()->user->create();
		$text    = 'IMPORTANT MEMBER TEXT the member has not published yet.';

		$entry = array(
			'object'          => 'user',
			'data_key'        => 'draft_user',
			'data'            => array(
				'content' => $text,
				'video'   => array(
					array(
						'id'         => 0,
						'name'       => 'clip.mp4',
						'js_preview' => 'data:image/png;base64,' . str_repeat( 'A', 150000 ),
					),
				),
			),
			'_draft_saved_at' => time() - 3600,
		);

		bp_update_user_meta( $user_id, 'draft_user', $entry );

		// Premise: the row really is over the cap, and shedding really is
		// enough to bring it under. Without both, the test proves nothing.
		$shed = $entry;
		unset( $shed['data']['video'][0]['js_preview'] );
		$this->assertGreaterThan( bb_draft_max_size(), strlen( maybe_serialize( $entry ) ), 'Premise: the seeded row must exceed the cap.' );
		$this->assertLessThanOrEqual( bb_draft_max_size(), strlen( maybe_serialize( $shed ) ), 'Premise: shedding the poster must be enough to fit.' );

		delete_option( 'bb_draft_oneshot_state' );
		bb_drafts_oneshot_batch( 0 );

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );

		$this->assertIsArray( $stored, 'The salvageable draft must survive the healing pass, not be deleted.' );
		$this->assertSame( $text, $stored['data']['content'], "The member's unpublished text must be intact." );
		$this->assertArrayNotHasKey( 'js_preview', $stored['data']['video'][0], 'The poster frame must have been shed.' );
		$this->assertLessThanOrEqual( bb_draft_max_size(), strlen( maybe_serialize( $stored ) ), 'The stored row must now be within the cap.' );
	}

	/**
	 * Negative control: a row still too large after shedding is still deleted.
	 *
	 * Without this, the fix above would pass for a build that simply stopped
	 * healing oversized rows - which would leave the poisoned usermeta this
	 * whole ticket exists to clear sitting in the database (PROD-9621 Q6).
	 */
	public function test_oneshot_still_disposes_a_row_that_is_oversized_on_its_own_merits() {
		$user_id = self::factory()->user->create();

		$entry = array(
			'object'          => 'user',
			'data_key'        => 'draft_user',
			'data'            => array(
				// Over the cap on text alone, so shedding cannot rescue it.
				'content' => str_repeat( 'x', 150 * KB_IN_BYTES ),
				'video'   => array(
					array(
						'id'         => 0,
						'js_preview' => 'data:image/png;base64,' . str_repeat( 'A', 1000 ),
					),
				),
			),
			'_draft_saved_at' => time() - 3600,
		);

		bp_update_user_meta( $user_id, 'draft_user', $entry );

		$shed = $entry;
		unset( $shed['data']['video'][0]['js_preview'] );
		$this->assertGreaterThan( bb_draft_max_size(), strlen( maybe_serialize( $shed ) ), 'Premise: this row must still exceed the cap after shedding.' );

		delete_option( 'bb_draft_oneshot_state' );
		bb_drafts_oneshot_batch( 0 );

		$this->assertEmpty(
			bp_get_user_meta( $user_id, 'draft_user', true ),
			'A row that is genuinely too large must still be disposed - the healing guarantee must not weaken.'
		);
	}

	/**
	 * The forum heal must shed an inner draft's poster before dropping it.
	 *
	 * Kept symmetric with the one-draft-per-row path above. The forum row
	 * holds many inner drafts, so pass 1 drops only the ones individually
	 * over the cap - but it dropped them whole, with the same salvageable
	 * text loss. A salvage-only pass also has to reach the row write, which
	 * the `empty( $removed )` early return used to prevent (PROD-9621 Q6).
	 */
	public function test_forum_heal_sheds_an_inner_poster_instead_of_dropping_the_inner_draft() {
		$user_id = self::factory()->user->create();
		$text    = 'FORUM DRAFT TEXT worth keeping.';
		$key     = 'draft_reply_7001';

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$key => array(
					'data_key'        => $key,
					'data'            => array(
						'bbp_reply_content' => $text,
						'bbp_video'         => wp_json_encode(
							array(
								array(
									'id'         => 0,
									'js_preview' => 'data:image/png;base64,' . str_repeat( 'A', 150000 ),
								),
							)
						),
					),
					'_draft_saved_at' => time() - 3600,
				),
			)
		);

		$acted = bb_draft_heal_forum_row( $user_id );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertIsArray( $stored, 'The aggregate row must survive.' );
		$this->assertArrayHasKey( $key, $stored, 'The salvageable inner draft must not be dropped.' );
		$this->assertSame( $text, $stored[ $key ]['data']['bbp_reply_content'], 'The inner draft text must be intact.' );

		$video = json_decode( $stored[ $key ]['data']['bbp_video'], true );
		$this->assertArrayNotHasKey( 'js_preview', $video[0], 'The inner poster frame must have been shed.' );

		// A salvage is work done, so it must be reported - a caller summing
		// this into a healed total would otherwise call a rewritten row
		// untouched.
		$this->assertSame( 1, $acted, 'The salvage must be counted in the return value.' );
	}

	/**
	 * Replacing an activity draft must release the attachments it dropped.
	 *
	 * The activity save path only ever ADDED bb_media_draft. An attachment the
	 * member removed from their draft therefore stayed orphan-protected for
	 * good, and the cleanup crons could never reclaim it - a slow leak of
	 * files nothing references and nothing can delete.
	 *
	 * bb_draft_release_replaced_attachments() existed for exactly this and had
	 * one of its two call sites: the forum handler got it, the activity handler
	 * did not (PROD-9621).
	 *
	 * `$kept` is the control that stops this becoming an over-release, which is
	 * the failure mode the same helper already caused once on the forum side:
	 * an attachment the new draft still holds must keep its stamp.
	 */
	public function test_replacing_an_activity_draft_releases_only_the_dropped_attachments() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$dropped = $this->make_draft_attachment( $user_id );
		$kept    = $this->make_draft_attachment( $user_id );

		update_post_meta( $dropped, 'bb_media_draft', 1 );
		update_post_meta( $kept, 'bb_media_draft', 1 );

		$key = 'draft_user_' . $user_id;

		bp_update_user_meta(
			$user_id,
			$key,
			array(
				'object'   => 'user',
				'data_key' => $key,
				'data'     => array(
					'content' => 'original',
					'media'   => array(
						array( 'id' => $dropped ),
						array( 'id' => $kept ),
					),
				),
			)
		);

		// The member removes one attachment and keeps the other.
		$this->drive_activity_draft_save(
			$key,
			array(
				'content' => 'updated',
				'media'   => array( array( 'id' => $kept ) ),
			)
		);

		// Premise: the save actually stored the new entry.
		$stored = bp_get_user_meta( $user_id, $key, true );
		$this->assertSame( 'updated', $stored['data']['content'], 'Premise: the replacement must have been stored.' );

		$this->assertSame(
			'',
			(string) get_post_meta( $dropped, 'bb_media_draft', true ),
			'An attachment the replaced draft dropped must have its stamp released, or it can never be cleaned up.'
		);

		$this->assertSame(
			'1',
			(string) get_post_meta( $kept, 'bb_media_draft', true ),
			'An attachment the new draft still holds must keep its stamp - releasing it would expose a live draft to the orphan crons.'
		);
	}

	/**
	 * Drive the activity draft save handler with one payload.
	 *
	 * @param string $data_key Activity draft usermeta key.
	 * @param array  $data     Draft data payload.
	 * @return void
	 */
	protected function drive_activity_draft_save( $data_key, $data ) {
		$_POST    = array();
		$_REQUEST = array();

		$_REQUEST['draft_activity'] = wp_slash(
			wp_json_encode(
				array(
					'data_key'    => $data_key,
					'object'      => 'user',
					'post_action' => 'update',
					'data'        => $data,
				)
			)
		);

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_draft'] = wp_create_nonce( 'post_draft_activity' );
		$_REQUEST                     = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();

		try {
			bb_nouveau_ajax_post_draft_activity();
		} catch ( Exception $e ) {
			// Expected: the handler finished and tried to exit.
			unset( $e );
		}

		ob_end_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * Q12 premise: what the payload predicate counts as content.
	 *
	 * `bb_draft_topic_reply_entry_has_payload()` is the single predicate the
	 * save guard, the fetch endpoint and the has_draft localize all resolve
	 * through, and the JS packs mirror it in bbDraftDataHasPayload(). If it
	 * drifts, a real draft stops being offered - so the line it draws is
	 * asserted here rather than left implicit.
	 *
	 * The scaffolding cases are the ones that matter: tags, the subscription
	 * checkbox, the sticky flag and the topic/reply IDs travel with EVERY
	 * serialized form, so counting any of them as content would make every
	 * emptied composer look like a draft worth keeping - which is the bug.
	 */
	public function test_q12_payload_predicate_separates_content_from_form_scaffolding() {
		$scaffolding = array(
			'bbp_topic_tags'         => '',
			'bbp_topic_id'           => '2181',
			'bbp_reply_to'           => '4350',
			'action'                 => 'bbp-new-reply',
			'bbp_topic_subscription' => '',
			'bbp_stick_topic'        => '',
			'bbp_document'           => '[]',
			'link_preview_data'      => '',
		);

		$has_payload = array(
			'reply text'        => array( 'bbp_reply_content' => 'real text' ),
			'topic text'        => array( 'bbp_topic_content' => '<p>real text</p>' ),
			'topic title only'  => array( 'bbp_topic_title' => 'Just a title' ),
			'marked up text'    => array( 'bbp_reply_content' => '<p><b>bold</b></p>' ),
			'photo only'        => array( 'bbp_media' => wp_json_encode( array( array( 'id' => 123 ) ) ) ),
			'document only'     => array( 'bbp_document' => wp_json_encode( array( array( 'id' => 123 ) ) ) ),
			'video only'        => array( 'bbp_video' => wp_json_encode( array( array( 'id' => 123 ) ) ) ),
			'gif only'          => array( 'bbp_media_gif' => wp_json_encode( array( 'url' => 'x.gif' ) ) ),
			'link preview only' => array( 'link_preview_data' => wp_json_encode( array( 'link_url' => 'https://x.test' ) ) ),
			'derived link url'  => array( 'bb_link_url' => wp_json_encode( array( 'url' => 'https://x.test' ) ) ),
		);

		foreach ( $has_payload as $label => $data ) {
			$this->assertTrue(
				bb_draft_topic_reply_entry_has_payload( array( 'data' => array_merge( $scaffolding, $data ) ) ),
				sprintf( 'A draft carrying %s must be treated as restorable content.', $label )
			);
		}

		$no_payload = array(
			'scaffolding alone'      => array(),
			'empty reply string'     => array( 'bbp_reply_content' => '' ),
			'empty paragraph'        => array( 'bbp_reply_content' => '<p></p>' ),
			'line break only'        => array( 'bbp_reply_content' => '<br>' ),
			'whitespace only'        => array( 'bbp_reply_content' => "  \n\t " ),
			'non breaking space'     => array( 'bbp_reply_content' => '<p>&nbsp;</p>' ),
			'empty attachment lists' => array(
				'bbp_media'    => '[]',
				'bbp_document' => '[]',
				'bbp_video'    => '[]',
			),
			'tags but no text'       => array( 'bbp_topic_tags' => 'alpha,beta' ),
			'subscribed but empty'   => array( 'bbp_topic_subscription' => 'bbp_subscribe' ),
		);

		foreach ( $no_payload as $label => $data ) {
			$this->assertFalse(
				bb_draft_topic_reply_entry_has_payload( array( 'data' => array_merge( $scaffolding, $data ) ) ),
				sprintf( 'A draft carrying only %s has nothing to restore and must not count as a draft.', $label )
			);
		}

		// Malformed shapes must answer false rather than warn or fatal - this
		// predicate runs on stored rows written by older builds.
		$this->assertFalse( bb_draft_topic_reply_entry_has_payload( array() ) );
		$this->assertFalse( bb_draft_topic_reply_entry_has_payload( array( 'data' => '' ) ) );
		$this->assertFalse( bb_draft_topic_reply_entry_has_payload( array( 'data' => 'not-an-array' ) ) );
		$this->assertFalse( bb_draft_topic_reply_entry_has_payload( array( 'data' => array( 'bbp_reply_content' => array( 'nested' ) ) ) ) );
	}

	/**
	 * Q12: a save carrying nothing must not replace a stored draft.
	 *
	 * The composer forced its own validity flag true whenever the ALREADY
	 * STORED copy held content - the "still available in older draft" checks -
	 * and then wrote the freshly serialized, empty form over it anyway. So
	 * clearing the editor replaced the member's saved text with an empty
	 * string, on the server, while leaving `is_content_valid` true so a draft
	 * indicator still showed over the empty box.
	 *
	 * Fixed in both JS packs, but the empty copy already sitting in members'
	 * localStorage on live installs is replayed by the unload sync, so the
	 * handler is the boundary that has to refuse it. Browser-reproduced before
	 * the fix on bbtesting.com: `draft_reply_2181` went from
	 * "Q12-BASELINE-CONTENT-ALPHA" to "" server-side purely by clearing the
	 * editor.
	 *
	 * The second half is the negative control: a save that DOES carry content
	 * must still be written, or this test would also pass for a handler that
	 * had stopped saving drafts altogether.
	 */
	public function test_q12_contentless_save_must_not_replace_a_stored_draft() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_id = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$data_key = 'draft_discussion_' . $forum_id;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$data_key => array(
					'data_key'         => $data_key,
					'object'           => 'topic',
					'data'             => array( 'bbp_topic_content' => 'THE MEMBER WROTE THIS' ),
					'is_content_valid' => true,
					'_draft_saved_at'  => time() - 60,
				),
			)
		);

		// Exactly what an emptied composer serializes: the scaffolding survives,
		// the content does not.
		$this->drive_forum_draft_save_with_siblings(
			$data_key,
			array(
				'bbp_topic_content'      => '',
				'bbp_topic_tags'         => '',
				'bbp_topic_subscription' => '',
			),
			array()
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertIsArray( $stored, 'The aggregate row must still exist.' );
		$this->assertSame(
			'THE MEMBER WROTE THIS',
			$stored[ $data_key ]['data']['bbp_topic_content'],
			'A save carrying no content must not replace stored content - that is the data loss.'
		);

		// Negative control: content still saves.
		$this->drive_forum_draft_save_with_siblings(
			$data_key,
			array( 'bbp_topic_content' => 'THE MEMBER WROTE MORE' ),
			array()
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			'THE MEMBER WROTE MORE',
			$stored[ $data_key ]['data']['bbp_topic_content'],
			'A save that carries content must still be written, or the guard is refusing everything.'
		);
	}

	/**
	 * Q12: a contentless all_data sibling must not replace a stored draft.
	 *
	 * This is the path a pre-existing empty localStorage copy actually arrives
	 * on. The unload sync replays EVERY inner key the tab still holds, not just
	 * the one being edited, and the sibling merge assigns the client's `data`
	 * over the stored entry wholesale - so an empty sibling copy destroyed a
	 * stored draft the member had not even opened in that tab.
	 *
	 * `$live_key` is the negative control: the sibling merge must still work
	 * for a sibling that carries content, or this test would pass for a build
	 * that simply dropped all siblings.
	 */
	public function test_q12_contentless_all_data_sibling_must_not_replace_a_stored_draft() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$empty_forum   = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$live_forum    = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$empty_key   = 'draft_discussion_' . $empty_forum;
		$live_key    = 'draft_discussion_' . $live_forum;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array(
					'data_key'        => $primary_key,
					'data'            => array( 'bbp_topic_content' => 'primary original' ),
					'_draft_saved_at' => time() - 60,
				),
				$empty_key   => array(
					'data_key'        => $empty_key,
					'data'            => array( 'bbp_topic_content' => 'SIBLING THE MEMBER STILL WANTS' ),
					'_draft_saved_at' => time() - 60,
				),
				$live_key    => array(
					'data_key'        => $live_key,
					'data'            => array( 'bbp_topic_content' => 'sibling original' ),
					'_draft_saved_at' => time() - 60,
				),
			)
		);

		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			array( 'bbp_topic_content' => 'primary updated' ),
			array(
				// A stale, emptied localStorage copy.
				$empty_key => array(
					'bbp_topic_content' => '',
					'bbp_topic_tags'    => '',
					'bbp_document'      => '[]',
				),
				// A sibling that really did change.
				$live_key  => array( 'bbp_topic_content' => 'sibling updated' ),
			)
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			'SIBLING THE MEMBER STILL WANTS',
			$stored[ $empty_key ]['data']['bbp_topic_content'],
			'A contentless sibling replay must not wipe a stored sibling draft.'
		);

		// Negative controls: the request itself still did its work.
		$this->assertSame(
			'sibling updated',
			$stored[ $live_key ]['data']['bbp_topic_content'],
			'A sibling carrying content must still merge, or the guard is refusing every sibling.'
		);
		$this->assertSame(
			'primary updated',
			$stored[ $primary_key ]['data']['bbp_topic_content'],
			'The primary key must still be written.'
		);
	}

	/**
	 * Q12: a contentless stored entry must not be offered to the composer.
	 *
	 * The client no longer writes these, but rows already written on live
	 * installs stop being overwritten now that the save guard exists - so
	 * without a read-side mask they would show a "Draft" indicator over an
	 * empty composer for ever. One real row in that exact state was found on
	 * the test install (`draft_reply_2181_4350`: empty content, no attachment,
	 * `is_content_valid` still true).
	 *
	 * The entry that DOES carry content is the negative control - the endpoint
	 * must keep returning drafts, not filter them all out.
	 */
	public function test_q12_contentless_entry_is_not_offered_to_the_composer() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$empty_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$real_forum  = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$empty_key = 'draft_discussion_' . $empty_forum;
		$real_key  = 'draft_discussion_' . $real_forum;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$empty_key => array(
					'data_key'         => $empty_key,
					'object'           => 'topic',
					'is_content_valid' => true,
					'data'             => array(
						'bbp_topic_content' => '',
						'bbp_document'      => '[]',
						'bbp_topic_tags'    => '',
					),
					'_draft_saved_at'  => time() - 60,
				),
				$real_key  => array(
					'data_key'         => $real_key,
					'object'           => 'topic',
					'is_content_valid' => true,
					'data'             => array( 'bbp_topic_content' => 'a real draft' ),
					'_draft_saved_at'  => time() - 60,
				),
			)
		);

		$response = $this->drive_forum_draft_fetch();

		$this->assertTrue( ! empty( $response['success'] ), 'The fetch endpoint must answer success.' );
		$this->assertArrayNotHasKey(
			$empty_key,
			$response['data']['drafts'],
			'An entry with no text and no attachment has nothing to restore and must not be offered.'
		);
		$this->assertArrayHasKey(
			$real_key,
			$response['data']['drafts'],
			'A draft carrying content must still be offered, or the mask is hiding everything.'
		);
	}

	/**
	 * Drive bb_get_topic_reply_drafts() and return its decoded JSON response.
	 *
	 * @return array Decoded response.
	 */
	protected function drive_forum_draft_fetch() {
		$_POST    = array();
		$_REQUEST = array();

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();

		try {
			bb_get_topic_reply_drafts();
		} catch ( Exception $e ) {
			// Expected: the handler finished and tried to exit.
			unset( $e );
		}

		$body = ob_get_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		return (array) json_decode( $body, true );
	}

	/**
	 * R2/Q16: a re-encoded attachment list must survive the meta write intact.
	 *
	 * The handler reads a client attachment list with
	 * `json_decode( stripslashes( ... ) )`, normalises it (ownership check,
	 * `bb_media_draft` stamp) and writes it back with `wp_json_encode()`. That
	 * re-encoded string carries REAL backslashes - `é` for a non-ASCII
	 * filename, `\"` for a quote - and `update_metadata()` runs
	 * `wp_unslash()` on the value before storing it. So the escapes were
	 * stripped and the stored list stopped being valid JSON.
	 *
	 * Measured before the fix: `résumé.pdf` stored as `ru00e9sumu00e9.pdf`
	 * and `Bob"s file.pdf` as `Bob"s file.pdf`, `json_decode()` returning a
	 * syntax error, and `bb_draft_collect_attachment_ids()` recovering ZERO
	 * ids from the stored entry.
	 *
	 * That last number is why this is data loss rather than cosmetic: every
	 * "is this attachment still referenced?" exclusion added by this branch
	 * (Q1/Q3/Q4/H4) parses that JSON. A list that will not parse reads as "no
	 * attachments referenced", so the replaced-entry release unstamps files
	 * the stored draft still points at and the orphan cron deletes them.
	 *
	 * Asserts the stored bytes are byte-identical to what the handler encoded,
	 * that they parse, and that the ids are recoverable - the property the
	 * attachment-retention logic actually depends on.
	 */
	public function test_q16_reencoded_attachment_list_survives_the_meta_write() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_id = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$data_key = 'draft_discussion_' . $forum_id;

		$attachment_id = $this->make_draft_attachment( $user_id );

		// The two characters that break the round trip: a non-ASCII letter
		// (encoded as \uXXXX) and a double quote (encoded as \").
		$document_list = wp_json_encode(
			array(
				array(
					'id'    => $attachment_id,
					'name'  => 'résumé.pdf',
					'title' => 'Bob"s file.pdf',
				),
			)
		);

		$this->drive_forum_draft_save_with_siblings(
			$data_key,
			array(
				'bbp_topic_content' => 'a draft with an attachment',
				'bbp_document'      => $document_list,
			),
			array()
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertIsArray( $stored, 'The draft must have been stored.' );
		$this->assertArrayHasKey( $data_key, $stored );

		$stored_list = $stored[ $data_key ]['data']['bbp_document'];

		$this->assertIsString( $stored_list, 'The attachment list is stored as a JSON string.' );

		$decoded = json_decode( $stored_list, true );

		$this->assertNotNull(
			$decoded,
			'The stored attachment list must still be valid JSON - ' . json_last_error_msg() . ' - or every attachment-retention check reads it as empty.'
		);

		// The property the retention logic depends on.
		$this->assertSame(
			array( (int) $attachment_id ),
			bb_draft_collect_attachment_ids( $stored[ $data_key ] ),
			'The attachment id must be recoverable from the stored draft, or the orphan cron reaps a file the draft still references.'
		);

		// And the member's filename must come back as they typed it.
		$this->assertSame( 'résumé.pdf', $decoded[0]['name'], 'A non-ASCII filename must survive the round trip.' );
		$this->assertSame( 'Bob"s file.pdf', $decoded[0]['title'], 'A quote in a filename must survive the round trip.' );

		// The other half of the same defect: bb_draft_protect_payload_attachments()
		// also reads this list through stripslashes(), so an unparseable list
		// meant the member's upload was never stamped at all and the orphan
		// cron deleted it six hours later.
		$this->assertSame(
			'1',
			(string) get_post_meta( $attachment_id, 'bb_media_draft', true ),
			'The upload must be orphan-protected on the beacon path too, or the cron deletes a file the draft references.'
		);
	}

	/**
	 * R2/Q16, sibling path: the same must hold for an all_data replay.
	 *
	 * `all_data` arrives as one JSON envelope, so the handler's
	 * `json_decode( stripslashes( ... ) )` hands back inner attachment lists
	 * that are already UNSLASHED. Assigning them straight onto the stored
	 * entry meant `update_metadata()`'s `wp_unslash()` stripped their escapes
	 * too - the same corruption reached by a different route, on the path the
	 * unload beacon uses for every sibling draft the tab holds.
	 */
	public function test_q16_all_data_sibling_attachment_list_survives_the_meta_write() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$primary_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$sibling_forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $primary_forum;
		$sibling_key = 'draft_discussion_' . $sibling_forum;

		$attachment_id = $this->make_draft_attachment( $user_id );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			array(
				$primary_key => array(
					'data_key'        => $primary_key,
					'data'            => array( 'bbp_topic_content' => 'primary original' ),
					'_draft_saved_at' => time() - 60,
				),
				$sibling_key => array(
					'data_key'        => $sibling_key,
					'data'            => array( 'bbp_topic_content' => 'sibling original' ),
					'_draft_saved_at' => time() - 60,
				),
			)
		);

		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			array( 'bbp_topic_content' => 'primary updated' ),
			array(
				$sibling_key => array(
					'bbp_topic_content' => 'sibling updated',
					'bbp_document'      => wp_json_encode(
						array(
							array(
								'id'   => $attachment_id,
								'name' => 'résumé.pdf',
							),
						)
					),
				),
			)
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		// Premise: the sibling merge really happened.
		$this->assertSame(
			'sibling updated',
			$stored[ $sibling_key ]['data']['bbp_topic_content'],
			'The sibling merge must have happened, or this test proves nothing about it.'
		);

		$this->assertNotNull(
			json_decode( $stored[ $sibling_key ]['data']['bbp_document'], true ),
			'A sibling attachment list must still be valid JSON after the merge - ' . json_last_error_msg() . '.'
		);

		$this->assertSame(
			array( (int) $attachment_id ),
			bb_draft_collect_attachment_ids( $stored[ $sibling_key ] ),
			'The sibling attachment id must be recoverable from the stored draft.'
		);
	}

	/**
	 * R1/Q15: the row trim must not re-serialize the whole row per eviction.
	 *
	 * `bb_forums_trim_draft_row()` asked "does it fit yet?" with
	 * `strlen( maybe_serialize( $row ) )` on every loop iteration, so an
	 * over-budget row with N eviction candidates serialized O(N) copies of a
	 * row that is by definition near the cap. Measured on a 200-entry / 10 MB
	 * row: 182 whole-row `serialize()` calls moving 953.8 MB, on the live
	 * autosave endpoint.
	 *
	 * The sibling `bb_draft_enforce_user_budget()` has always precomputed
	 * per-entry `bytes` and subtracted; this is that pattern.
	 *
	 * Asserted here as a BEHAVIOURAL invariant rather than a timing: the
	 * trimmed row must be exactly as small as the old implementation made it,
	 * and must be at or under the budget. An accounting shortcut that drifted
	 * by even a few bytes would either evict one entry too many (member loses
	 * a draft) or leave the row over budget (the caller's budget check then
	 * refuses the save) - so exactness is the thing worth locking down.
	 */
	public function test_q15_row_trim_is_exact_and_bounded() {
		$user_id = self::factory()->user->create();

		$row = array();

		for ( $i = 1; $i <= 40; $i++ ) {
			$row[ 'draft_reply_' . $i ] = array(
				'data_key'        => 'draft_reply_' . $i,
				'object'          => 'reply',
				'data'            => array( 'bbp_reply_content' => str_repeat( 'x', 1000 ) ),
				'_draft_saved_at' => 1700000000 + $i,
			);
		}

		$protect_key = 'draft_reply_40';
		$max_bytes   = 12000;

		$result = bb_forums_trim_draft_row( $row, $protect_key, $user_id, $max_bytes );

		// The protected entry always survives.
		$this->assertArrayHasKey( $protect_key, $result['row'], 'The just-saved draft must never be evicted.' );

		// The row really is under the budget - measured the authoritative way,
		// not with the function's own accounting.
		$this->assertLessThanOrEqual(
			$max_bytes,
			strlen( maybe_serialize( $result['row'] ) ),
			'The trim must actually bring the row under the budget, or the caller refuses the save.'
		);

		// And it did not over-evict: putting back the last evicted entry would
		// push it over. This is what catches accounting drift in either
		// direction.
		$this->assertNotEmpty( $result['evicted'], 'This fixture is over budget, so something must have been evicted.' );

		$last_evicted = end( $result['evicted'] );
		$last_inner   = substr( $last_evicted, strlen( 'bb_user_topic_reply_draft:' ) );

		$restored = $result['row'];

		$restored[ $last_inner ] = $row[ $last_inner ];

		$this->assertGreaterThan(
			$max_bytes,
			strlen( maybe_serialize( $restored ) ),
			'The trim evicted more than it needed to - the last eviction was unnecessary.'
		);

		// Oldest-first ordering is preserved.
		$this->assertSame(
			'bb_user_topic_reply_draft:draft_reply_1',
			$result['evicted'][0],
			'Eviction must still start with the oldest draft.'
		);

		// Removed entries are reported for the caller's deferred unstamping.
		$this->assertSame(
			count( $result['evicted'] ),
			count( $result['entries'] ),
			'Every evicted key must come back with its entry so the caller can unstamp it.'
		);
	}

	/**
	 * Drive the save handler through the NESTED-ARRAY request shape.
	 *
	 * The two shipped clients do not agree on how they post: the in-page XHR
	 * sends `draft_topic_reply` as an object, which jQuery serializes to
	 * `draft_topic_reply[data][bbp_document]=...` and PHP rebuilds as a nested
	 * array whose leaves are SLASHED by WordPress; the unload beacon sends one
	 * `JSON.stringify()` string, which the handler decodes with
	 * `json_decode( stripslashes( ... ) )` and so arrives UNSLASHED.
	 *
	 * {@see drive_forum_draft_save_with_siblings()} covers the string shape.
	 * This covers the array shape, slashing the leaves the way
	 * wp_magic_quotes() does for a real request.
	 *
	 * @param string $data_key Inner draft key.
	 * @param array  $data     Draft data members (unslashed; slashed here).
	 * @return void
	 */
	protected function drive_forum_draft_save_as_nested_array( $data_key, $data ) {
		$_POST    = array();
		$_REQUEST = array();

		$_REQUEST['draft_topic_reply'] = wp_slash(
			array(
				'data_key'    => $data_key,
				'object'      => 'topic',
				'post_action' => 'update',
				'data'        => $data,
			)
		);

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();

		try {
			bb_post_topic_reply_draft();
		} catch ( Exception $e ) {
			unset( $e );
		}

		ob_end_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * R2/Q16, in-page XHR shape: attachment list must survive intact.
	 *
	 * Same invariant as the beacon-shape test, driven through the other
	 * request shape, because the two arrive with different slash states and
	 * the handler has to answer the same for both (bb-dev §54a-1/§54a-7).
	 */
	public function test_q16_nested_array_request_keeps_attachment_list_parseable() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_id = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$data_key = 'draft_discussion_' . $forum_id;

		$attachment_id = $this->make_draft_attachment( $user_id );

		$this->drive_forum_draft_save_as_nested_array(
			$data_key,
			array(
				'bbp_topic_content' => 'a draft with an attachment',
				'bbp_document'      => wp_json_encode(
					array(
						array(
							'id'    => $attachment_id,
							'name'  => 'résumé.pdf',
							'title' => 'Bob"s file.pdf',
						),
					)
				),
			)
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertIsArray( $stored, 'The draft must have been stored.' );
		$this->assertArrayHasKey( $data_key, $stored );

		$decoded = json_decode( $stored[ $data_key ]['data']['bbp_document'], true );

		$this->assertNotNull(
			$decoded,
			'In-page XHR shape: the stored attachment list must still be valid JSON - ' . json_last_error_msg() . '.'
		);
		$this->assertSame(
			array( (int) $attachment_id ),
			bb_draft_collect_attachment_ids( $stored[ $data_key ] ),
			'In-page XHR shape: the attachment id must be recoverable from the stored draft.'
		);
		$this->assertSame( 'résumé.pdf', $decoded[0]['name'], 'A non-ASCII filename must survive the round trip.' );
		$this->assertSame( 'Bob"s file.pdf', $decoded[0]['title'], 'A quote in a filename must survive the round trip.' );
	}

	/**
	 * R2/Q16: a read-modify-write of the row must not strip its escapes.
	 *
	 * `bb_draft_heal_forum_row()` reads the aggregate row with
	 * `bp_get_user_meta()` - so UNSLASHED - modifies one inner draft and
	 * writes the whole row back. `update_metadata()` unslashes the value once
	 * more before storing, so writing it back untouched stripped a backslash
	 * layer from every string in the row, including the `\uXXXX` escapes in the
	 * attachment lists of the inner drafts the heal exists to PRESERVE.
	 *
	 * Measured: `résumé.pdf` came back as `ru00e9sumu00e9.pdf`, and a filename
	 * containing a double quote broke the list's JSON outright, losing that
	 * draft's whole attachment list (and with it the "still referenced" answer
	 * every retention check on this branch depends on).
	 *
	 * The oversized entry is what makes the heal act at all; the sibling with
	 * the awkward filename is the one whose bytes must come back untouched.
	 */
	public function test_q16_heal_pass_preserves_sibling_json_escapes() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$attachment_id = $this->make_draft_attachment( $user_id );

		$sibling_list = wp_json_encode(
			array(
				array(
					'id'    => $attachment_id,
					'name'  => 'résumé.pdf',
					'title' => 'Bob"s file.pdf',
				),
			)
		);

		// Stored the correct way: slashed, so the meta API's single unslash
		// lands the intended bytes.
		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			wp_slash(
				array(
					// Over the per-draft cap, so the heal has something to do.
					'draft_reply_1' => array(
						'data_key'        => 'draft_reply_1',
						'object'          => 'reply',
						'data'            => array( 'bbp_reply_content' => str_repeat( 'x', bb_draft_max_size() + 1024 ) ),
						'_draft_saved_at' => time() - 120,
					),
					// The innocent bystander.
					'draft_reply_2' => array(
						'data_key'        => 'draft_reply_2',
						'object'          => 'reply',
						'data'            => array(
							'bbp_reply_content' => 'a small sibling draft',
							'bbp_document'      => $sibling_list,
						),
						'_draft_saved_at' => time() - 60,
					),
				)
			)
		);

		// Premise: it really did land intact, or this test proves nothing.
		$before = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			$sibling_list,
			$before['draft_reply_2']['data']['bbp_document'],
			'Premise: the fixture must be stored byte-identical before the heal runs.'
		);

		$acted = bb_draft_heal_forum_row( $user_id );

		$this->assertGreaterThan( 0, $acted, 'The heal must have acted, or the row was never rewritten.' );

		$after = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertArrayHasKey( 'draft_reply_2', $after, 'The small sibling must survive the heal.' );

		$this->assertSame(
			$sibling_list,
			$after['draft_reply_2']['data']['bbp_document'],
			'The heal must write the row back byte-identical - a lost backslash layer corrupts the filename and can break the JSON.'
		);

		$this->assertSame(
			array( (int) $attachment_id ),
			bb_draft_collect_attachment_ids( $after['draft_reply_2'] ),
			'The sibling attachment id must still be recoverable after the heal.'
		);
	}

	/**
	 * S1: disposing one inner draft must not corrupt the member's others.
	 *
	 * `bb_draft_dispose()` reads the aggregate row with `bp_get_user_meta()` -
	 * so UNSLASHED - removes one inner key and writes the row back.
	 * `update_metadata()` unslashes the value once more before storing, so the
	 * surviving siblings lost a backslash layer: `résumé.pdf` came back as
	 * `ru00e9sumu00e9.pdf` and a filename containing a double quote broke the
	 * attachment list's JSON outright.
	 *
	 * This is the SHARED removal path - the member's own discard, the per-user
	 * budget eviction and the nightly expiry cron all route through here - so
	 * it corrupts drafts the member never touched, with no action on their
	 * part.
	 *
	 * The survivor's bytes are the assertion; the disposed key going away is
	 * the premise that proves the function actually ran.
	 */
	public function test_s1_dispose_preserves_the_surviving_siblings_bytes() {
		$user_id = self::factory()->user->create();

		$attachment_id = $this->make_draft_attachment( $user_id );

		$survivor_list = wp_json_encode(
			array(
				array(
					'id'    => $attachment_id,
					'name'  => 'résumé.pdf',
					'title' => 'Bob"s file.pdf',
				),
			)
		);
		$survivor_text = 'path C:\\temp\\notes';

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			wp_slash(
				array(
					'draft_reply_1' => array(
						'data_key'        => 'draft_reply_1',
						'object'          => 'reply',
						'data'            => array( 'bbp_reply_content' => 'the one being discarded' ),
						'_draft_saved_at' => time() - 120,
					),
					'draft_reply_2' => array(
						'data_key'        => 'draft_reply_2',
						'object'          => 'reply',
						'data'            => array(
							'bbp_reply_content' => $survivor_text,
							'bbp_document'      => $survivor_list,
						),
						'_draft_saved_at' => time() - 60,
					),
				)
			)
		);

		// Premise: the fixture landed byte-identical.
		$before = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame( $survivor_list, $before['draft_reply_2']['data']['bbp_document'], 'Premise: fixture stored verbatim.' );
		$this->assertSame( $survivor_text, $before['draft_reply_2']['data']['bbp_reply_content'], 'Premise: fixture stored verbatim.' );

		$this->assertTrue( bb_draft_dispose( $user_id, 'bb_user_topic_reply_draft', 'draft_reply_1' ) );

		$after = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		// Premise: it really did dispose the target.
		$this->assertArrayNotHasKey( 'draft_reply_1', $after, 'The disposed inner draft must be gone.' );
		$this->assertArrayHasKey( 'draft_reply_2', $after, 'The sibling must survive.' );

		$this->assertSame(
			$survivor_list,
			$after['draft_reply_2']['data']['bbp_document'],
			'Disposing one draft must leave a sibling attachment list byte-identical.'
		);
		$this->assertSame(
			$survivor_text,
			$after['draft_reply_2']['data']['bbp_reply_content'],
			'Disposing one draft must leave a sibling text byte-identical.'
		);
		$this->assertSame(
			array( (int) $attachment_id ),
			bb_draft_collect_attachment_ids( $after['draft_reply_2'] ),
			'The sibling attachment id must still be recoverable after an unrelated dispose.'
		);
	}

	/**
	 * S2: the salvage pass must not alter the text it exists to preserve.
	 *
	 * `bb_draft_salvage_oversized_draft()` was added by Q6 precisely to stop
	 * the healing path destroying member text: it sheds the video poster frame
	 * so an oversized row fits instead of being deleted. But it reads the row
	 * unslashed and writes it back without re-slashing, so the meta API's
	 * second unslash mangled the very content being rescued -
	 * `path C:\temp\notes` came back as `path C:tempnotes`.
	 */
	public function test_s2_salvage_preserves_the_text_it_rescues() {
		$user_id = self::factory()->user->create();

		$typed = 'path C:\\temp\\notes and a résumé';

		// `draft_user` (the activity one-draft-per-row shape), because
		// bb_draft_shed_preview_frames() operates on a draft ENTRY - it reads
		// $draft['data'] - and the forum aggregate row is a map of entries with
		// no 'data' member of its own, so salvage is a no-op there.
		//
		// A poster frame big enough that the row is over the per-draft cap and
		// shedding it brings the row back under - which is what makes salvage
		// act rather than bail.
		$poster = 'data:image/png;base64,' . str_repeat( 'A', bb_draft_max_size() );

		bp_update_user_meta(
			$user_id,
			'draft_user',
			wp_slash(
				array(
					'data_key'        => 'draft_user',
					'object'          => 'user',
					'data'            => array(
						'content' => $typed,
						'video'   => array(
							array(
								'id'         => 1234,
								'js_preview' => $poster,
							),
						),
					),
					'_draft_saved_at' => time() - 60,
				)
			)
		);

		$before = bp_get_user_meta( $user_id, 'draft_user', true );

		$this->assertSame( $typed, $before['data']['content'], 'Premise: fixture stored verbatim.' );

		$this->assertTrue(
			bb_draft_salvage_oversized_draft( $user_id, 'draft_user' ),
			'Premise: salvage must have acted, or this test proves nothing.'
		);

		$after = bp_get_user_meta( $user_id, 'draft_user', true );

		// Premise: the poster really was shed.
		$this->assertTrue(
			empty( $after['data']['video'][0]['js_preview'] ),
			'Premise: the poster frame must have been shed.'
		);

		$this->assertSame(
			$typed,
			$after['data']['content'],
			'Salvage must leave the rescued text byte-identical - it exists to preserve it.'
		);
	}

	/**
	 * The one-shot heal must NOT delete a draft that shrank under the cap
	 * between the metadata scan and the heal (stale-size TOCTOU). The scan
	 * measures row size in a batch query and heals a whole window later, so the
	 * member can edit an oversized-at-scan row back under the cap in that gap.
	 * A plain-text row has no poster frame to shed, so salvage used to return
	 * false regardless of the row's CURRENT size, and the caller then disposed
	 * it unconditionally - permanently deleting a valid, in-progress draft.
	 * Salvage now re-measures the fresh row and reports it handled (true) when
	 * it is already under the cap, so the caller never disposes it.
	 */
	public function test_salvage_does_not_dispose_a_row_that_shrank_under_the_cap() {
		$user_id = self::factory()->user->create();

		// A small plain-text draft: under the per-draft cap, and NOTHING to shed
		// (no video poster frame) - the exact shape the old code disposed.
		bp_update_user_meta(
			$user_id,
			'draft_user',
			array(
				'data_key'        => 'draft_user',
				'object'          => 'user',
				'data'            => array( 'content' => 'a small, valid, in-progress note' ),
				'_draft_saved_at' => time() - 60,
			)
		);

		$this->assertLessThanOrEqual(
			bb_draft_max_size(),
			strlen( maybe_serialize( bp_get_user_meta( $user_id, 'draft_user', true ) ) ),
			'Premise: the fixture row is under the per-draft cap.'
		);

		// "Handled - do not dispose." The caller runs `elseif salvage() {} elseif
		// dispose() {}`, so a true return short-circuits the unconditional delete.
		$this->assertTrue(
			bb_draft_salvage_oversized_draft( $user_id, 'draft_user' ),
			'A row already under the cap must be reported handled so the heal loop never disposes it.'
		);

		$this->assertNotEmpty(
			bp_get_user_meta( $user_id, 'draft_user', true ),
			'The valid, under-cap draft must still exist - salvage must not have destroyed it.'
		);
	}

	/**
	 * S4: the activity handler must store the same content on both transports.
	 *
	 * The activity composer posts two ways, exactly like the forum one: the
	 * in-page XHR sends an object (leaves arrive slashed) and the unload
	 * beacon sends one `JSON.stringify()` string, which
	 * `json_decode( stripslashes( … ) )` unslashes. `update_metadata()` then
	 * unslashes once more on write, so the beacon transport ate the member's
	 * backslashes while the XHR transport kept them - same handler, same
	 * keystrokes, different stored content depending on whether the save came
	 * from the autosave tick or from closing the tab.
	 */
	public function test_s4_activity_draft_content_is_transport_independent() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$typed = 'path C:\\temp\\notes';

		$this->drive_activity_draft_save( 'draft_user', array( 'content' => $typed ) );

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );

		$this->assertIsArray( $stored, 'The activity draft must have been stored.' );
		$this->assertSame(
			$typed,
			$stored['data']['content'],
			'The beacon transport must store the member content exactly as the XHR transport does.'
		);
	}

	/**
	 * H4: an over-50 attachment list must not be stored truncated.
	 *
	 * The bound was a hard 50 applied with array_slice() to the array that is
	 * subsequently STORED, and on the activity path it ran before
	 * bb_draft_protect_payload_attachments(). So on a site whose media "Upload
	 * Limit" is above 50 - the field accepts up to 100 - entries past 50 were
	 * dropped from the draft the member would restore AND never stamped, and
	 * unstamped with no bb_media_draft is exactly what
	 * bp_media_delete_orphaned_attachments() hard-deletes six hours later.
	 *
	 * The bound now follows the configured limit and the handlers refuse an
	 * over-bound list instead of storing a subset. This asserts the case that
	 * used to lose files: 60 attachments on a 60-limit site are stored complete
	 * and every one of them is stamped.
	 */
	public function test_h4_attachment_list_at_the_configured_limit_is_stored_whole_and_stamped() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		add_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_60' ) );

		$ids  = array();
		$list = array();

		for ( $i = 0; $i < 60; $i++ ) {
			$id     = $this->make_draft_attachment( $user_id );
			$ids[]  = (int) $id;
			$list[] = array( 'id' => $id );
		}

		$this->drive_activity_draft_save(
			'draft_user',
			array(
				'content' => 'sixty photos',
				'media'   => $list,
			)
		);

		remove_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_60' ) );

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );

		$this->assertIsArray( $stored, 'The draft must have been stored.' );
		$this->assertCount(
			60,
			$stored['data']['media'],
			'The stored draft must keep every attachment the member attached, not the first 50.'
		);

		$unstamped = array();

		foreach ( $ids as $id ) {
			if ( '1' !== (string) get_post_meta( $id, 'bb_media_draft', true ) ) {
				$unstamped[] = $id;
			}
		}

		$this->assertSame(
			array(),
			$unstamped,
			'Every attachment the draft references must be orphan-protected, or the cron deletes the ones past the bound.'
		);
	}

	/**
	 * Raise the draft attachment bound to 60 for the H4 test.
	 *
	 * @return int
	 */
	public function filter_draft_attachment_bound_60() {
		return 60;
	}

	/**
	 * Lower the per-type attachment bound so the refusal is cheap to reach.
	 *
	 * @return int
	 */
	public function filter_draft_attachment_bound_2() {
		return 2;
	}

	/**
	 * Drive bb_post_topic_reply_draft() as a DISCARD (post_action delete).
	 *
	 * @param string $data_key Draft key to discard.
	 * @return void
	 */
	protected function drive_forum_draft_discard( $data_key ) {
		$_POST    = array();
		$_REQUEST = array();

		// The client discard omits the data member, exactly like the JS.
		$_REQUEST['draft_topic_reply'] = wp_slash(
			wp_json_encode(
				array(
					'data_key'    => $data_key,
					'object'      => 'topic',
					'post_action' => 'delete',
				)
			)
		);

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();

		try {
			bb_post_topic_reply_draft();
		} catch ( Exception $e ) {
			unset( $e );
		}

		ob_end_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * Discarding a forum draft must actually remove it from storage and release
	 * its attachment protection.
	 *
	 * The GH1 refactor stopped seeding $decided_draft_keys with the primary key,
	 * populating it only inside is_draft_update-gated blocks. A discard sends
	 * post_action delete (is_draft_update false), so the key was never decided
	 * and the fresh-read merge copied it straight back from storage - the
	 * discard silently did nothing while reporting success, and the draft
	 * resurfaced on the next lazy fetch.
	 */
	public function test_discarding_a_forum_draft_removes_it_and_releases_its_attachment() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type(), 'post_status' => 'publish' ) );
		$att   = $this->make_draft_attachment( $user_id );
		update_post_meta( $att, 'bb_media_draft', 1 );

		$key = 'draft_discussion_' . $forum;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			wp_slash(
				array(
					$key => array(
						'data_key'        => $key,
						'object'          => 'topic',
						'_draft_saved_at' => time() - 60,
						'data'            => array(
							'bbp_topic_content' => 'discard me',
							'bbp_media'         => wp_json_encode( array( array( 'id' => $att ) ) ),
						),
					),
				)
			)
		);

		$this->drive_forum_draft_discard( $key );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertTrue(
			empty( $row ) || ! isset( $row[ $key ] ),
			'A discarded forum draft must be removed from storage, not silently re-copied by the merge.'
		);
		$this->assertSame(
			'',
			(string) get_post_meta( $att, 'bb_media_draft', true ),
			'The discarded draft was the only reference, so its attachment stamp must be released.'
		);
	}

	/**
	 * Discarding one forum draft must NOT release an attachment a sibling draft
	 * still references.
	 */
	public function test_discarding_a_forum_draft_keeps_a_sibling_attachment() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_a = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type(), 'post_status' => 'publish' ) );
		$forum_b = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type(), 'post_status' => 'publish' ) );
		$shared  = $this->make_draft_attachment( $user_id );
		update_post_meta( $shared, 'bb_media_draft', 1 );

		$discard_key = 'draft_discussion_' . $forum_a;
		$sibling_key = 'draft_discussion_' . $forum_b;
		$media_json  = wp_json_encode( array( array( 'id' => $shared ) ) );

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			wp_slash(
				array(
					$discard_key => array( 'data_key' => $discard_key, 'object' => 'topic', '_draft_saved_at' => time() - 60, 'data' => array( 'bbp_media' => $media_json ) ),
					$sibling_key => array( 'data_key' => $sibling_key, 'object' => 'topic', '_draft_saved_at' => time() - 60, 'data' => array( 'bbp_media' => $media_json ) ),
				)
			)
		);

		$this->drive_forum_draft_discard( $discard_key );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertArrayNotHasKey( $discard_key, $row, 'The discarded draft must be removed.' );
		$this->assertArrayHasKey( $sibling_key, $row, 'The sibling draft must survive.' );
		$this->assertSame(
			'1',
			(string) get_post_meta( $shared, 'bb_media_draft', true ),
			'An attachment the surviving sibling still references must keep its protection.'
		);
	}

	/**
	 * Drive the activity draft handler with a RAW request array (bypasses the
	 * scalar-only drive_activity_draft_save helper), returning the response.
	 *
	 * @param array $draft Raw draft_activity payload.
	 * @return array Decoded JSON response.
	 */
	protected function drive_activity_draft_raw( $draft ) {
		$_POST    = array();
		$_REQUEST = array();

		$_REQUEST['draft_activity'] = wp_slash( wp_json_encode( $draft ) );

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_draft'] = wp_create_nonce( 'post_draft_activity' );
		$_REQUEST                     = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();

		try {
			bb_nouveau_ajax_post_draft_activity();
		} catch ( Exception $e ) {
			unset( $e );
		}

		$body = ob_get_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		$decoded = json_decode( (string) $body, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * C2: a crafted array data_key/object must be rejected WITHOUT emitting an
	 * "Array to string conversion" notice.
	 *
	 * The suite converts warnings to exceptions, so a regression back to the
	 * bare (string) cast turns this test red (GH review C2).
	 */
	public function test_c2_array_data_key_is_rejected_without_warning() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$response = $this->drive_activity_draft_raw(
			array(
				'data_key'    => array( 'x' ),
				'object'      => array( 'y' ),
				'post_action' => 'update',
				'data'        => array( 'content' => 'hi', 'item_id' => array( 'z' ) ),
			)
		);

		$this->assertArrayHasKey( 'success', $response, 'The handler must return a JSON response, not error out on a warning.' );
		$this->assertFalse( $response['success'], 'A non-scalar data_key must be rejected.' );
	}

	/**
	 * C1: the feature image is owner-gated before the caps, mirroring the
	 * media/document/video lists.
	 *
	 * A member's OWN feature image is stamped early (BLOCKER-1 protection) so
	 * the orphan cron spares it even if a later cap rejects. The Core-only
	 * foreign-id drop cannot be exercised where Pro is active (its per-object
	 * check owns that path and is preserved), so this asserts the owner-gate's
	 * stamp, which is the branch this install can reach (GH review C1).
	 */
	public function test_c1_owned_feature_image_is_stamped_early() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$attachment_id = $this->make_draft_attachment( $user_id );

		$this->drive_activity_draft_raw(
			array(
				'data_key'    => 'draft_user',
				'object'      => 'user',
				'post_action' => 'update',
				'data'        => array(
					'content'                       => 'a draft with a feature image',
					'bb_activity_post_feature_image' => array( 'id' => $attachment_id ),
				),
			)
		);

		// The early owner-gate stamps before any cap or Pro check, so the meta
		// is present regardless of what the rest of the request decided.
		$this->assertSame(
			'1',
			(string) get_post_meta( $attachment_id, 'bb_activity_post_feature_image_draft', true ),
			'An owned feature image must be orphan-protected by the early owner-gate.'
		);
	}

	/**
	 * The one-shot must CONVERGE across budgeted (cron-style) slices and still
	 * run stage 2, now that the stage-2 aggregate is deferred off any
	 * time-limited slice (reviewer: stage-2 unbounded).
	 *
	 * The failure this guards against is a defer that loops forever: an
	 * "always defer when budget>0" without a scan_done marker re-completes an
	 * empty re-scan every slice and never reaches the aggregate, so the
	 * aggregate-oversized user is never trimmed. Slice 1 finishes the scan and
	 * defers; slice 2 runs the aggregate and drains stage 2.
	 */
	public function test_oneshot_converges_across_budgeted_slices() {
		$this->isolate_draft_maintenance();

		$heavy = self::factory()->user->create();
		$cap   = bb_draft_user_total_max_size();
		$count = (int) ceil( ( $cap + 80000 ) / 40000 );

		for ( $i = 1; $i <= $count; $i++ ) {
			bp_update_user_meta(
				$heavy,
				'draft_group_' . $i,
				array(
					'data_key'        => 'draft_group_' . $i,
					'_draft_saved_at' => 1000 + $i,
					'data'            => array( 'content' => str_repeat( 'x', 40000 ) ),
				)
			);
		}

		$before = array_sum( bb_draft_get_user_meta_sizes( $heavy )['drafts'] );
		$this->assertGreaterThan( $cap, $before, 'Fixture must start the user over the aggregate cap.' );

		$iterations = 0;
		do {
			$iterations++;
			$result = bb_drafts_oneshot_batch( 10 );
		} while ( empty( $result['complete'] ) && $iterations < 50 );

		$after = array_sum( bb_draft_get_user_meta_sizes( $heavy )['drafts'] );

		$this->assertTrue( ! empty( $result['complete'] ), 'The one-shot must converge, not loop forever deferring the aggregate.' );
		$this->assertLessThanOrEqual( $cap, $after, 'Stage 2 must trim the aggregate-oversized user under the cap.' );
		$this->assertTrue( (bool) get_option( 'bb_draft_oneshot_done' ), 'Completion must set the durable done marker.' );

		$scheduled = wp_next_scheduled( 'bb_draft_oneshot' );
		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, 'bb_draft_oneshot' );
		}
		delete_option( 'bb_draft_oneshot_done' );
	}

	/**
	 * The unbudgeted (WP-CLI) drain must complete stage 1 AND stage 2 in ONE
	 * call - it does not defer the aggregate, unlike a time-limited slice.
	 */
	public function test_oneshot_budget_zero_completes_in_one_call() {
		$this->isolate_draft_maintenance();

		$heavy = self::factory()->user->create();
		$cap   = bb_draft_user_total_max_size();
		$count = (int) ceil( ( $cap + 80000 ) / 40000 );

		for ( $i = 1; $i <= $count; $i++ ) {
			bp_update_user_meta(
				$heavy,
				'draft_group_' . $i,
				array(
					'data_key'        => 'draft_group_' . $i,
					'_draft_saved_at' => 1000 + $i,
					'data'            => array( 'content' => str_repeat( 'x', 40000 ) ),
				)
			);
		}

		$result = bb_drafts_oneshot_batch( 0 );
		$after  = array_sum( bb_draft_get_user_meta_sizes( $heavy )['drafts'] );

		$this->assertTrue( ! empty( $result['complete'] ), 'The unbudgeted drain must complete in one call.' );
		$this->assertLessThanOrEqual( $cap, $after, 'Stage 2 must run inline for the unbudgeted drain.' );

		delete_option( 'bb_draft_oneshot_done' );
	}

	/**
	 * A held lock makes the one-shot back off instead of racing on its state
	 * (reviewer: no concurrency lock on bb_draft_oneshot_state).
	 */
	public function test_oneshot_backs_off_while_the_lock_is_held() {
		$this->isolate_draft_maintenance();

		set_site_transient( 'bb_draft_oneshot_lock', 1, 5 * MINUTE_IN_SECONDS );

		$result = bb_drafts_oneshot_batch( 10 );

		$this->assertNotEmpty( $result['locked'], 'A run finding the lock held must report locked.' );
		$this->assertEmpty( $result['complete'], 'A locked run has not completed the pass.' );

		delete_site_transient( 'bb_draft_oneshot_lock' );

		$scheduled = wp_next_scheduled( 'bb_draft_oneshot' );
		if ( $scheduled ) {
			wp_unschedule_event( $scheduled, 'bb_draft_oneshot' );
		}
	}

	/**
	 * Drive the forum draft handler and RETURN the JSON response body.
	 *
	 * @param string $data_key Primary draft key.
	 * @param array  $data     Primary draft data.
	 * @param array  $all_data Sibling entries (data_key => data).
	 * @return array Decoded JSON response.
	 */
	protected function drive_forum_draft_capture_response( $data_key, $data, $all_data ) {
		$_POST    = array();
		$_REQUEST = array();

		$_REQUEST['draft_topic_reply'] = wp_slash(
			wp_json_encode(
				array(
					'data_key'    => $data_key,
					'object'      => 'topic',
					'post_action' => 'update',
					'data'        => $data,
				)
			)
		);
		$_REQUEST['all_data'] = wp_slash( wp_json_encode( $all_data ) );

		// phpcs:disable WordPress.Security.NonceVerification -- this test drives the handler that performs the verification.
		$_POST['_wpnonce_post_topic_reply_draft'] = wp_create_nonce( 'post_topic_reply_draft_data' );
		$_REQUEST                                 = array_merge( $_REQUEST, $_POST );
		// phpcs:enable WordPress.Security.NonceVerification

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );

		ob_start();

		try {
			bb_post_topic_reply_draft();
		} catch ( Exception $e ) {
			unset( $e );
		}

		$body = ob_get_clean();

		remove_filter( 'wp_die_ajax_handler', array( $this, 'filter_draft_die_handler' ), 99 );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		$decoded = json_decode( (string) $body, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * GH1, cap-rejection variant: an over-cap PRIMARY entry must not cost a
	 * sibling its update, and the primary must still report its cap error.
	 *
	 * The unload beacon carries the primary entry plus an all_data array of
	 * siblings. The per-draft byte cap on the primary used to call
	 * wp_send_json_error(), which wp_die()s before the sibling merge ran -
	 * silently discarding a sibling's genuine update while answering with a
	 * cap error scoped only to the primary. The rejection is now a flag: the
	 * request falls through to the sibling merge, then answers with the
	 * primary's error (GH1).
	 */
	public function test_gh1_over_cap_primary_saves_sibling_and_still_reports_error() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_a = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$forum_b = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $forum_a;
		$sibling_key = 'draft_discussion_' . $forum_b;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			wp_slash(
				array(
					$sibling_key => array(
						'data_key'        => $sibling_key,
						'object'          => 'topic',
						'_draft_saved_at' => time() - 60,
						'data'            => array( 'bbp_topic_content' => 'SIBLING OLD TEXT' ),
					),
				)
			)
		);

		$over_cap = str_repeat( 'A', bb_draft_max_size() + 5000 );

		$response = $this->drive_forum_draft_capture_response(
			$primary_key,
			array( 'bbp_topic_content' => $over_cap ),
			array(
				$primary_key => array( 'bbp_topic_content' => $over_cap ),
				$sibling_key => array( 'bbp_topic_content' => 'SIBLING NEW TEXT' ),
			)
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		// The sibling's genuine update survived the primary's rejection.
		$this->assertSame(
			'SIBLING NEW TEXT',
			$stored[ $sibling_key ]['data']['bbp_topic_content'],
			'The sibling update in the same beacon must be saved even when the primary is over-cap.'
		);

		// The over-cap primary was NOT stored.
		$this->assertTrue(
			empty( $stored[ $primary_key ] ),
			'The over-cap primary entry must not be stored.'
		);

		// And the response still reports the primary cap error.
		$this->assertArrayHasKey( 'success', $response, 'The handler must return a JSON response.' );
		$this->assertFalse( $response['success'], 'An over-cap primary must report an error.' );
		$this->assertStringContainsString(
			'too large',
			isset( $response['data']['message'] ) ? $response['data']['message'] : '',
			'The error must carry the per-draft cap message.'
		);
	}

	/**
	 * GH1, attachment-bound variant: an over-BOUND primary must not cost a
	 * sibling its update either.
	 */
	public function test_gh1_over_bound_primary_saves_sibling() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_a = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$forum_b = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $forum_a;
		$sibling_key = 'draft_discussion_' . $forum_b;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			wp_slash(
				array(
					$sibling_key => array(
						'data_key'        => $sibling_key,
						'object'          => 'topic',
						'_draft_saved_at' => time() - 60,
						'data'            => array( 'bbp_topic_content' => 'SIBLING OLD TEXT' ),
					),
				)
			)
		);

		add_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_2' ) );

		$list = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$list[] = array( 'id' => $this->make_draft_attachment( $user_id ) );
		}

		$response = $this->drive_forum_draft_capture_response(
			$primary_key,
			array(
				'bbp_topic_content' => 'over-bound primary',
				'bbp_media'         => wp_json_encode( $list ),
			),
			array(
				$sibling_key => array( 'bbp_topic_content' => 'SIBLING NEW TEXT' ),
			)
		);

		remove_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_2' ) );

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			'SIBLING NEW TEXT',
			$stored[ $sibling_key ]['data']['bbp_topic_content'],
			'The sibling update must survive an over-bound primary.'
		);
		$this->assertTrue( empty( $stored[ $primary_key ] ), 'The over-bound primary must not be stored.' );
		$this->assertFalse( $response['success'], 'An over-bound primary must report an error.' );
	}

	/**
	 * Snapshots of every bb_draft_oneshot_state write, for the H5 cursor test.
	 *
	 * @var array
	 */
	protected $oneshot_state_writes = array();

	/**
	 * Record a bb_draft_oneshot_state write (option added).
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Stored value.
	 * @return void
	 */
	public function record_oneshot_state_added( $option, $value ) {
		if ( 'bb_draft_oneshot_state' === $option ) {
			$this->oneshot_state_writes[] = $value;
		}
	}

	/**
	 * Record a bb_draft_oneshot_state write (option updated).
	 *
	 * @param string $option    Option name.
	 * @param mixed  $old_value Previous value.
	 * @param mixed  $value     Stored value.
	 * @return void
	 */
	public function record_oneshot_state_updated( $option, $old_value, $value ) {
		if ( 'bb_draft_oneshot_state' === $option ) {
			$this->oneshot_state_writes[] = $value;
		}
	}

	/**
	 * S5: an ordinary autosave of ONE forum draft must leave every SIBLING
	 * entry in the aggregate row byte-identical.
	 *
	 * The handler rewrites the whole row, and its in-memory copy used to mix
	 * slash states: siblings unslashed from the fresh storage read, the
	 * primary entry slashed from the request. update_metadata()'s single
	 * wp_unslash() then stripped a backslash layer from every sibling on
	 * every autosave - `r\u00e9sum\u00e9.pdf` became `ru00e9sumu00e9.pdf` in a
	 * sibling's attachment JSON, and a member-typed `C:\temp` lost its
	 * backslash - corrupting drafts the member never touched, on the hottest
	 * draft write on the site (PROD-9621 S5).
	 *
	 * Driven through BOTH request shapes, because they arrive in different
	 * slash states and the boundary normalisation must make them
	 * indistinguishable past that point (bb-dev §54a-1/§54a-7).
	 */
	public function test_s5_autosave_preserves_sibling_escapes_on_both_transports() {
		foreach ( array( 'beacon', 'nested_array' ) as $transport ) {
			$user_id = self::factory()->user->create();
			$this->set_current_user( $user_id );

			$forum_a = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
			$forum_b = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

			$primary_key = 'draft_discussion_' . $forum_a;
			$sibling_key = 'draft_discussion_' . $forum_b;

			$attachment_id = $this->make_draft_attachment( $user_id );

			// Exactly what the fixed write paths store: real \uXXXX escapes in
			// the attachment JSON, real backslashes in the member's text.
			$sibling_json = wp_json_encode(
				array(
					array(
						'id'   => $attachment_id,
						'name' => 'r\u00e9sum\u00e9.pdf',
					),
				)
			);
			$sibling_text = 'sibling text with path C:\\temp\\notes';

			bp_update_user_meta(
				$user_id,
				'bb_user_topic_reply_draft',
				wp_slash(
					array(
						$sibling_key => array(
							'data_key'        => $sibling_key,
							'object'          => 'topic',
							'_draft_saved_at' => time() - 60,
							'data'            => array(
								'bbp_topic_content' => $sibling_text,
								'bbp_media'         => $sibling_json,
							),
						),
					)
				)
			);

			if ( 'beacon' === $transport ) {
				$this->drive_forum_draft_save( $primary_key, array( 'bbp_topic_content' => 'autosave of a different draft' ) );
			} else {
				$this->drive_forum_draft_save_as_nested_array( $primary_key, array( 'bbp_topic_content' => 'autosave of a different draft' ) );
			}

			$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

			// Negative control: the autosave itself must have been written, or
			// this test also passes for a handler that rejects everything.
			$this->assertArrayHasKey( $primary_key, $stored, "[$transport] The autosaved draft must be stored." );

			$this->assertSame(
				$sibling_json,
				$stored[ $sibling_key ]['data']['bbp_media'],
				"[$transport] The sibling's attachment JSON must survive the autosave byte-identical."
			);
			$this->assertSame(
				$sibling_text,
				$stored[ $sibling_key ]['data']['bbp_topic_content'],
				"[$transport] The sibling's text must survive the autosave byte-identical."
			);
		}
	}

	/**
	 * S5/R3: the SAME keystrokes must store the SAME forum draft content on
	 * both transports.
	 *
	 * The in-page XHR posts a nested array (leaves WP-slashed); the unload
	 * beacon posts one JSON string that decodes unslashed. Before the boundary
	 * normalisation the two reached kses and storage in different slash
	 * states, so a member-typed backslash survived one transport and not the
	 * other - which stored draft the member got back depended on which timer
	 * happened to fire last.
	 */
	public function test_s5_forum_draft_content_is_transport_independent() {
		$typed = 'my path is C:\\temp\\notes';

		$stored_by_transport = array();

		foreach ( array( 'beacon', 'nested_array' ) as $transport ) {
			$user_id = self::factory()->user->create();
			$this->set_current_user( $user_id );

			$forum_id = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
			$data_key = 'draft_discussion_' . $forum_id;

			if ( 'beacon' === $transport ) {
				$this->drive_forum_draft_save( $data_key, array( 'bbp_topic_content' => $typed ) );
			} else {
				$this->drive_forum_draft_save_as_nested_array( $data_key, array( 'bbp_topic_content' => $typed ) );
			}

			$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

			$this->assertArrayHasKey( $data_key, $stored, "[$transport] The draft must be stored." );

			$stored_by_transport[ $transport ] = $stored[ $data_key ]['data']['bbp_topic_content'];

			$this->assertSame(
				$typed,
				$stored[ $data_key ]['data']['bbp_topic_content'],
				"[$transport] Member-typed backslashes must reach storage intact."
			);
		}

		$this->assertSame(
			$stored_by_transport['beacon'],
			$stored_by_transport['nested_array'],
			'Both transports must store identical content for identical keystrokes.'
		);
	}

	/**
	 * GH1: an empty PRIMARY entry must not cost a sibling its update.
	 *
	 * The kept-stored-primary guard used to answer with
	 * wp_send_json_success(), which dies - and the sibling merge over
	 * `all_data` runs later in the handler. The unload beacon replays every
	 * key the tab holds, so a request whose primary happened to be an emptied
	 * reply box still carried a sibling's genuine last-ever save, and the
	 * early exit silently discarded it while reporting success.
	 */
	public function test_gh1_empty_primary_does_not_drop_a_sibling_update() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_a = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$forum_b = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$primary_key = 'draft_discussion_' . $forum_a;
		$sibling_key = 'draft_discussion_' . $forum_b;

		bp_update_user_meta(
			$user_id,
			'bb_user_topic_reply_draft',
			wp_slash(
				array(
					$primary_key => array(
						'data_key'        => $primary_key,
						'object'          => 'topic',
						'_draft_saved_at' => time() - 60,
						'data'            => array( 'bbp_topic_content' => 'PRIMARY STORED TEXT' ),
					),
					$sibling_key => array(
						'data_key'        => $sibling_key,
						'object'          => 'topic',
						'_draft_saved_at' => time() - 60,
						'data'            => array( 'bbp_topic_content' => 'SIBLING OLD TEXT' ),
					),
				)
			)
		);

		// Unload beacon: the primary composer was emptied, the sibling was
		// genuinely edited on the same page.
		$this->drive_forum_draft_save_with_siblings(
			$primary_key,
			array( 'bbp_topic_content' => '<p><br></p>' ),
			array(
				$primary_key => array( 'bbp_topic_content' => '<p><br></p>' ),
				$sibling_key => array( 'bbp_topic_content' => 'SIBLING NEW TEXT' ),
			)
		);

		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

		$this->assertSame(
			'PRIMARY STORED TEXT',
			$stored[ $primary_key ]['data']['bbp_topic_content'],
			'The emptied primary must not overwrite the stored draft (Q12 guard still holds).'
		);
		$this->assertSame(
			'SIBLING NEW TEXT',
			$stored[ $sibling_key ]['data']['bbp_topic_content'],
			'The sibling update in the same request must be written - the kept primary must not end the request early.'
		);
	}

	/**
	 * GH2: the row trim must fit rows whose inner keys are INTEGERS.
	 *
	 * Legacy []-appended rows carry integer inner keys, which PHP serializes
	 * as `i:N;` while the width helper priced them as strings - 4 bytes high
	 * per key. The drifted accounting under-trimmed, and the authoritative
	 * backstop was dead code (it reseeded from the full candidate list, its
	 * first array_shift() returned an already-evicted candidate, and the
	 * isset() check broke the loop). The row came back over budget and the
	 * caller's budget check refused the whole save. 487 of 3,204 swept
	 * budget/row combinations violated the invariant before the fix
	 * (PROD-9621 GH2).
	 *
	 * Property sweep rather than one fixture: the failure lands only when the
	 * budget falls inside the drift margin, and the sweep covers that band
	 * without hand-computing it. Mutation-verified as a PAIR: reverting only
	 * the pricing stays green (the live backstop absorbs the drift), reverting
	 * only the backstop stays green (the pricing is exact), reverting both
	 * goes red - the two fixes are each other's safety net, and this test is
	 * what fails when the net is gone.
	 */
	public function test_gh2_trim_fits_rows_with_integer_inner_keys() {
		for ( $count = 6; $count <= 22; $count += 8 ) {
			$row = array();

			for ( $i = 0; $i < $count; $i++ ) {
				$row[ $i ] = array(
					'_draft_saved_at' => 1000 + $i,
					'data'            => array( 'bbp_reply_content' => str_repeat( 'x', 120 + $i ) ),
				);
			}

			$full_size = strlen( maybe_serialize( $row ) );

			for ( $max_bytes = (int) ( $full_size * 0.2 ); $max_bytes < $full_size; $max_bytes += 7 ) {
				$result = bb_forums_trim_draft_row( $row, 0, 1, $max_bytes );
				$size   = strlen( maybe_serialize( $result['row'] ) );

				// Invariant, asserted on EVERY case: the returned row fits, or
				// nothing evictable remains (the protected key 0 is never a
				// candidate, so a one-entry row is the legal floor).
				$this->assertTrue(
					$size <= $max_bytes || 1 === count( $result['row'] ),
					"Row of {$count} int-keyed drafts trimmed to cap {$max_bytes} came back at {$size} bytes with evictable entries left."
				);
			}
		}
	}

	/**
	 * H4, forum handler: an over-bound attachment list is REFUSED, with the
	 * member's uploads already protected and storage untouched.
	 *
	 * The bound used to array_slice() the list that was then stored, so
	 * entries past it were dropped from the draft AND left unstamped - which
	 * is exactly the predicate the orphan cron hard-deletes. The refusal must
	 * come AFTER the protection pass (BLOCKER-1: a cap must never decide
	 * whether an already-uploaded file survives the cron).
	 */
	public function test_h4_forum_over_bound_attachment_list_is_refused_not_truncated() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$forum_id = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$data_key = 'draft_discussion_' . $forum_id;

		add_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_2' ) );

		$ids  = array();
		$list = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$id     = $this->make_draft_attachment( $user_id );
			$ids[]  = (int) $id;
			$list[] = array( 'id' => $id );
		}

		$this->drive_forum_draft_save(
			$data_key,
			array(
				'bbp_topic_content' => 'three attachments against a bound of two',
				'bbp_media'         => wp_json_encode( $list ),
			)
		);

		remove_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_2' ) );

		// Refused, not truncated: nothing may have been stored.
		$stored = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertTrue(
			empty( $stored[ $data_key ] ),
			'An over-bound list must refuse the save, never store a truncated draft.'
		);

		// BLOCKER-1: the protection pass ran before the refusal, so the
		// member's uploads within the bound are stamped against the orphan
		// cron even though the save was refused.
		$this->assertSame( '1', (string) get_post_meta( $ids[0], 'bb_media_draft', true ), 'The first upload must be orphan-protected despite the refusal.' );
		$this->assertSame( '1', (string) get_post_meta( $ids[1], 'bb_media_draft', true ), 'The second upload must be orphan-protected despite the refusal.' );
	}

	/**
	 * H4, activity handler: same refusal contract as the forum handler.
	 */
	public function test_h4_activity_over_bound_attachment_list_is_refused_not_truncated() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		add_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_2' ) );

		$ids  = array();
		$list = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$id     = $this->make_draft_attachment( $user_id );
			$ids[]  = (int) $id;
			$list[] = array( 'id' => $id );
		}

		$this->drive_activity_draft_save(
			'draft_user',
			array(
				'content' => 'three attachments against a bound of two',
				'media'   => $list,
			)
		);

		remove_filter( 'bb_draft_max_attachments_per_type', array( $this, 'filter_draft_attachment_bound_2' ) );

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );
		$this->assertEmpty( $stored, 'An over-bound list must refuse the save, never store a truncated draft.' );

		$this->assertSame( '1', (string) get_post_meta( $ids[0], 'bb_media_draft', true ), 'The first upload must be orphan-protected despite the refusal.' );
		$this->assertSame( '1', (string) get_post_meta( $ids[1], 'bb_media_draft', true ), 'The second upload must be orphan-protected despite the refusal.' );
	}

	/**
	 * H5: the one-shot must persist its stage-1 cursor after EVERY window.
	 *
	 * Without the per-window persist, an interruption that never reached the
	 * post-loop branches (a fatal inside a heal, a killed worker - and under
	 * WP-CLI, where the budget is 0, those branches never run mid-loop at
	 * all) discarded every completed window and the continuation restarted
	 * the scan from row zero.
	 *
	 * Seeds one row more than a scan window (200) so the loop crosses a
	 * window boundary, then asserts a state write with a mid-scan shape:
	 * cursor advanced, heavy_users still null (stage 1 not yet complete).
	 */
	public function test_h5_oneshot_persists_cursor_after_every_window() {
		$user_id = self::factory()->user->create();

		for ( $i = 1; $i <= 201; $i++ ) {
			bp_update_user_meta(
				$user_id,
				'draft_group_' . $i,
				array(
					'data_key'        => 'draft_group_' . $i,
					'_draft_saved_at' => time(),
					'data'            => array( 'content' => 'tiny' ),
				)
			);
		}

		delete_option( 'bb_draft_oneshot_state' );
		delete_option( 'bb_draft_oneshot_done' );

		$this->oneshot_state_writes = array();

		add_action( 'added_option', array( $this, 'record_oneshot_state_added' ), 10, 2 );
		add_action( 'updated_option', array( $this, 'record_oneshot_state_updated' ), 10, 3 );

		$result = bb_drafts_oneshot_batch( 0 );

		remove_action( 'added_option', array( $this, 'record_oneshot_state_added' ), 10 );
		remove_action( 'updated_option', array( $this, 'record_oneshot_state_updated' ), 10 );

		delete_option( 'bb_draft_oneshot_state' );
		delete_option( 'bb_draft_oneshot_done' );

		$this->assertNotEmpty( $result['complete'], 'The unbudgeted run must complete.' );

		$mid_scan_persists = 0;

		foreach ( $this->oneshot_state_writes as $state ) {
			if ( is_array( $state ) && ! empty( $state['cursor'] ) && ( ! isset( $state['heavy_users'] ) || null === $state['heavy_users'] ) ) {
				++$mid_scan_persists;
			}
		}

		$this->assertGreaterThanOrEqual(
			1,
			$mid_scan_persists,
			'Stage 1 must persist its cursor after every window, not only on a budget break - an interrupted scan must resume where it stopped.'
		);
	}
}
