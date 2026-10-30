<?php
/**
 * Which video privacies are queued for the background thumbnail job (PROD-9829).
 *
 * Comment videos must be queued like activity post videos, so they get a server-side
 * poster when the browser could not capture one. Forum and message videos stay excluded.
 *
 * @group video
 * @group video_background_thumbnail
 */
class BP_Tests_Video_Background_Thumbnail_Privacy extends BP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'BP_Video' ) || empty( buddypress()->video->table_name ) ) {
			$this->markTestSkipped( 'The video component is not loaded.' );
		}
	}

	public function test_comment_videos_are_queued() {
		$this->assertFalse( bb_video_privacy_skips_background_thumbnail( 'comment' ) );
	}

	public function test_activity_post_videos_are_queued() {
		foreach ( array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ) as $privacy ) {
			$this->assertFalse( bb_video_privacy_skips_background_thumbnail( $privacy ), $privacy );
		}
	}

	public function test_forum_and_message_videos_are_skipped() {
		$this->assertTrue( bb_video_privacy_skips_background_thumbnail( 'forums' ) );
		$this->assertTrue( bb_video_privacy_skips_background_thumbnail( 'message' ) );
	}

	public function test_privacy_match_is_exact() {
		$this->assertFalse( bb_video_privacy_skips_background_thumbnail( '' ) );
		$this->assertFalse( bb_video_privacy_skips_background_thumbnail( 'Forums' ) );
		$this->assertFalse( bb_video_privacy_skips_background_thumbnail( 'forum' ) );
	}
}
