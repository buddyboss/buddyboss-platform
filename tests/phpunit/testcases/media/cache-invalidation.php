<?php
/**
 * Cache invalidation for media, video and document actions.
 *
 * These caches used to be cleared by a global `wp_cache_flush()` in the
 * nouveau AJAX handlers; they must now be cleared at the model layer.
 *
 * @group media
 * @group cache
 */
class BP_Tests_Media_Cache_Invalidation extends BP_UnitTestCase {

	protected $user_id;
	protected $group_id;

	public function set_up() {
		parent::set_up();
		$this->user_id  = self::factory()->user->create();
		$this->group_id = self::factory()->group->create( array( 'creator_id' => $this->user_id ) );
		$this->set_current_user( $this->user_id );
	}

	protected function attachment() {
		return self::factory()->attachment->create( array( 'post_author' => $this->user_id ) );
	}

	public function test_group_media_count_cache_is_cleared_on_media_delete() {
		$media_id = bp_media_add( array( 'attachment_id' => $this->attachment(), 'user_id' => $this->user_id, 'group_id' => $this->group_id ) );

		$this->assertSame( 1, bp_media_get_total_group_media_count( $this->group_id ) );

		bp_media_delete( array( 'id' => $media_id ) );

		$this->assertSame( 0, bp_media_get_total_group_media_count( $this->group_id ) );
	}

	public function test_group_video_count_cache_is_cleared_on_video_delete() {
		$video_id = bp_video_add( array( 'attachment_id' => $this->attachment(), 'user_id' => $this->user_id, 'group_id' => $this->group_id ) );

		$this->assertSame( 1, bp_video_get_total_group_video_count( $this->group_id ) );

		bp_video_delete( array( 'id' => $video_id ) );

		$this->assertSame( 0, bp_video_get_total_group_video_count( $this->group_id ) );
	}

	public function test_group_album_count_cache_is_cleared_on_album_delete() {
		$album_id = bp_album_add( array( 'title' => 'Album', 'user_id' => $this->user_id, 'group_id' => $this->group_id ) );

		$this->assertSame( 1, bp_media_get_total_group_album_count( $this->group_id ) );

		bp_album_delete( array( 'id' => $album_id ) );

		$this->assertSame( 0, bp_media_get_total_group_album_count( $this->group_id ) );
	}

	public function test_group_document_count_cache_is_cleared_on_document_delete() {
		$document_id = bp_document_add( array( 'attachment_id' => $this->attachment(), 'user_id' => $this->user_id, 'group_id' => $this->group_id ) );

		$this->assertSame( 1, bp_document_get_total_group_document_count( $this->group_id ) );

		bp_document_delete( array( 'id' => $document_id ) );

		$this->assertSame( 0, bp_document_get_total_group_document_count( $this->group_id ) );
	}

	public function test_group_folder_count_cache_is_cleared_on_folder_delete() {
		$folder_id = bp_folder_add( array( 'title' => 'Folder', 'user_id' => $this->user_id, 'group_id' => $this->group_id ) );

		$this->assertSame( 1, bp_document_get_total_group_folder_count( $this->group_id ) );

		bp_folder_delete( array( 'id' => $folder_id ) );

		$this->assertSame( 0, bp_document_get_total_group_folder_count( $this->group_id ) );
	}

	public function test_album_counts_are_refreshed_after_media_move() {
		$album_id = bp_album_add( array( 'title' => 'Album', 'user_id' => $this->user_id ) );
		$media_id = bp_media_add( array( 'attachment_id' => $this->attachment(), 'user_id' => $this->user_id ) );

		$before = bb_media_get_album_counts( $album_id );
		$this->assertSame( 0, $before['album_media_count'] );

		bp_media_move_media_to_album( $media_id, $album_id );

		$after = bb_media_get_album_counts( $album_id );
		$this->assertSame( 1, $after['album_media_count'] );
	}

	public function test_album_counts_are_refreshed_after_video_move() {
		$album_id = bp_video_album_add( array( 'title' => 'Album', 'user_id' => $this->user_id ) );
		$video_id = bp_video_add( array( 'attachment_id' => $this->attachment(), 'user_id' => $this->user_id ) );

		$before = bb_media_get_album_counts( $album_id );
		$this->assertSame( 0, $before['album_video_count'] );

		bp_video_move_video_to_album( $video_id, $album_id );

		$after = bb_media_get_album_counts( $album_id );
		$this->assertSame( 1, $after['album_video_count'] );
	}
}
