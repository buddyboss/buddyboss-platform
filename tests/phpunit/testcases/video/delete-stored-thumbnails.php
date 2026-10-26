<?php
/**
 * Stored video thumbnail IDs deleted with a video (PROD-10594 F-12).
 *
 * `video_preview_thumbnails['custom_image']` and `bp_video_preview_thumbnail_id` may hold
 * IDs saved before thumbnail validation existed. Deleting the video must only remove
 * thumbnails the video owner uploaded, or a custom thumbnail someone else (a group
 * organizer or moderator) uploaded for this video only. Never another user's attachment
 * that their own video still uses, or other posts.
 *
 * @group video
 * @group video_delete_thumbnails
 */
class BP_Tests_Video_Delete_Stored_Thumbnails extends BP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'BP_Video' ) || ! function_exists( 'bb_video_can_delete_stored_thumbnail' ) || empty( buddypress()->video->table_name ) ) {
			$this->markTestSkipped( 'The video component is not loaded.' );
		}
	}

	/**
	 * Create an attachment owned by a user.
	 *
	 * @param int  $user_id     Author ID.
	 * @param bool $thumb_upload Whether it came through the video thumbnail uploader.
	 *
	 * @return int
	 */
	protected function create_attachment( $user_id, $thumb_upload = false ) {
		$attachment_id = self::factory()->attachment->create(
			array(
				'post_author'    => $user_id,
				'post_mime_type' => 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		if ( $thumb_upload ) {
			update_post_meta( $attachment_id, 'bp_video_thumbnail_upload', 1 );
		}

		return $attachment_id;
	}

	/**
	 * Create a video owned by a user, with its own video attachment.
	 *
	 * @param int $user_id Owner ID.
	 *
	 * @return BP_Video
	 */
	protected function create_video( $user_id ) {
		$video_attachment_id = self::factory()->attachment->create(
			array(
				'post_author'    => $user_id,
				'post_mime_type' => 'video/mp4',
				'post_type'      => 'attachment',
			)
		);

		$video_id = bp_video_add(
			array(
				'attachment_id' => $video_attachment_id,
				'user_id'       => $user_id,
				'privacy'       => 'public',
			)
		);

		return new BP_Video( $video_id );
	}

	public function test_foreign_stored_thumbnails_survive_video_delete() {
		$owner    = self::factory()->user->create();
		$victim   = self::factory()->user->create();
		$video    = $this->create_video( $owner );
		$foreign  = $this->create_attachment( $victim, true );
		$foreign2 = $this->create_attachment( $victim );
		$page_id  = self::factory()->post->create( array( 'post_type' => 'page', 'post_author' => $victim ) );

		// The victim's custom image is in use on the victim's own video.
		$victim_video = $this->create_video( $victim );
		update_post_meta(
			$victim_video->attachment_id,
			'video_preview_thumbnails',
			array(
				'default_images' => array(),
				'custom_image'   => $foreign,
			)
		);

		update_post_meta(
			$video->attachment_id,
			'video_preview_thumbnails',
			array(
				'default_images' => array(),
				'custom_image'   => $foreign,
			)
		);
		update_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', $foreign2 );

		$this->assertFalse( bb_video_can_delete_stored_thumbnail( $video, $foreign ) );
		$this->assertFalse( bb_video_can_delete_stored_thumbnail( $video, $foreign2 ) );
		$this->assertFalse( bb_video_can_delete_stored_thumbnail( $video, $page_id ) );
		$this->assertFalse( bb_video_can_delete_stored_thumbnail( $video, $video->attachment_id ) );

		BP_Video::delete( array( 'id' => $video->id ) );

		$this->assertNotNull( get_post( $foreign ), 'Another user\'s custom image must not be deleted.' );
		$this->assertNotNull( get_post( $foreign2 ), 'Another user\'s attachment stored as poster must not be deleted.' );
		$this->assertNotNull( get_post( $page_id ) );
	}

	public function test_page_stored_as_poster_survives_video_delete() {
		$owner   = self::factory()->user->create();
		$video   = $this->create_video( $owner );
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_author' => $owner ) );

		update_post_meta(
			$video->attachment_id,
			'video_preview_thumbnails',
			array(
				'default_images' => array(),
				'custom_image'   => $page_id,
			)
		);
		update_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', $page_id );

		BP_Video::delete( array( 'id' => $video->id ) );

		$this->assertNotNull( get_post( $page_id ) );
	}

	public function test_owner_thumbnails_are_deleted_with_video() {
		$owner  = self::factory()->user->create();
		$video  = $this->create_video( $owner );
		$custom = $this->create_attachment( $owner, true );
		$poster = $this->create_attachment( $owner, true );

		update_post_meta(
			$video->attachment_id,
			'video_preview_thumbnails',
			array(
				'default_images' => array(),
				'custom_image'   => $custom,
			)
		);
		update_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', $poster );

		$this->assertTrue( bb_video_can_delete_stored_thumbnail( $video, $custom ) );
		$this->assertTrue( bb_video_can_delete_stored_thumbnail( $video, $poster ) );

		BP_Video::delete( array( 'id' => $video->id ) );

		$this->assertNull( get_post( $custom ) );
		$this->assertNull( get_post( $poster ), 'The current poster is deleted (the old check was inverted and never ran).' );
	}

	/**
	 * A custom thumbnail a group organizer or moderator uploaded for this video only is
	 * deleted with the video (review D-5 / A-3).
	 */
	public function test_editor_uploaded_thumbnail_used_only_by_this_video_is_deleted() {
		$owner     = self::factory()->user->create();
		$organizer = self::factory()->user->create();
		$video     = $this->create_video( $owner );
		$custom    = $this->create_attachment( $organizer, true );
		$unflagged = $this->create_attachment( $organizer );

		update_post_meta(
			$video->attachment_id,
			'video_preview_thumbnails',
			array(
				'default_images' => array(),
				'custom_image'   => $custom,
			)
		);
		update_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', $unflagged );

		$this->assertTrue( bb_video_can_delete_stored_thumbnail( $video, $custom ) );
		$this->assertTrue( bb_video_is_valid_thumbnail_for_video( $video, $custom, false ) );

		// Not uploaded through the thumbnail uploader.
		$this->assertFalse( bb_video_can_delete_stored_thumbnail( $video, $unflagged ) );

		BP_Video::delete( array( 'id' => $video->id ) );

		$this->assertNull( get_post( $custom ) );
		$this->assertNotNull( get_post( $unflagged ) );
	}

	/**
	 * A poisoned pointer to another member's thumbnail that their own video uses is
	 * refused on the poster delete paths (review A-1).
	 */
	public function test_delete_path_refuses_foreign_thumbnail_used_by_another_video() {
		$owner        = self::factory()->user->create();
		$victim       = self::factory()->user->create();
		$video        = $this->create_video( $owner );
		$victim_video = $this->create_video( $victim );
		$foreign      = $this->create_attachment( $victim, true );

		update_post_meta( $victim_video->attachment_id, 'bp_video_preview_thumbnail_id', $foreign );
		update_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', $foreign );

		self::set_current_user( $owner );

		$this->assertFalse( bb_video_can_delete_stored_thumbnail( $video, $foreign ) );
		$this->assertFalse( bb_video_is_valid_thumbnail_for_video( $video, $foreign, false ) );

		// Selecting it for display is unchanged.
		$this->assertTrue( bb_video_is_valid_thumbnail_for_video( $video, $foreign ) );
	}
}
