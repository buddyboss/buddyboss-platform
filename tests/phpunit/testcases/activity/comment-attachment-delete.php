<?php
/**
 * Tests for deleting a single photo, video or document from an activity comment.
 *
 * Deleting one attachment must delete the comment only when nothing is left
 * on it: no text, no GIF, and no other photo, video or document.
 *
 * @package BuddyBoss\Tests\Activity
 */

/**
 * @group activity
 * @group activity_comment_attachment_delete
 */
class BP_Tests_Activity_Comment_Attachment_Delete extends BP_UnitTestCase {

	/**
	 * Comment author.
	 *
	 * @var int
	 */
	protected $commenter;

	/**
	 * Root activity ID.
	 *
	 * @var int
	 */
	protected $activity_id;

	public function setUp(): void {
		parent::setUp();

		add_filter( 'bp_is_active', array( $this, 'force_components_active' ), 10, 2 );

		$author            = self::factory()->user->create();
		$this->commenter   = self::factory()->user->create();
		$this->activity_id = self::factory()->activity->create(
			array(
				'user_id' => $author,
				'content' => 'Root activity',
				'type'    => 'activity_update',
			)
		);

		self::set_current_user( $this->commenter );
	}

	public function tearDown(): void {
		remove_filter( 'bp_is_active', array( $this, 'force_components_active' ), 10 );

		parent::tearDown();
	}

	/**
	 * Make sure the attachment components count as active.
	 *
	 * @param bool   $is_active Whether the component is active.
	 * @param string $component Component ID.
	 * @return bool
	 */
	public function force_components_active( $is_active, $component ) {
		if ( in_array( $component, array( 'activity', 'media', 'document', 'video' ), true ) ) {
			return true;
		}

		return $is_active;
	}

	/**
	 * Post a comment on the root activity.
	 *
	 * @param string $content Comment text.
	 * @param int    $user_id Author, defaults to the commenter.
	 * @param int    $parent  Parent comment ID for a reply.
	 * @return int Comment ID.
	 */
	protected function create_comment( $content = '', $user_id = 0, $parent = 0 ) {
		return (int) bp_activity_new_comment(
			array(
				'activity_id' => $this->activity_id,
				'parent_id'   => $parent ? $parent : $this->activity_id,
				'content'     => $content,
				'user_id'     => $user_id ? $user_id : $this->commenter,
			)
		);
	}

	/**
	 * Attach a photo or video to an activity the way the uploader does: a media
	 * row pointing at the activity, the parent meta on the attachment, and the
	 * ID list on the activity.
	 *
	 * @param int    $activity_id Activity or comment ID.
	 * @param string $type        'photo' or 'video'.
	 * @return int Media ID.
	 */
	protected function attach_media( $activity_id, $type = 'photo' ) {
		$attachment_id = self::factory()->attachment->create_object(
			'p9606-' . wp_generate_password( 6, false ) . ( 'video' === $type ? '.mp4' : '.jpg' ),
			0,
			array(
				'post_mime_type' => 'video' === $type ? 'video/mp4' : 'image/jpeg',
				'post_type'      => 'attachment',
			)
		);

		$media                = 'video' === $type ? new BP_Video() : new BP_Media();
		$media->blog_id       = get_current_blog_id();
		$media->attachment_id = $attachment_id;
		$media->user_id       = $this->commenter;
		$media->title         = 'p9606';
		$media->activity_id   = $activity_id;
		$media->privacy       = 'comment';
		$media->status        = 'published';
		$media->date_created  = bp_core_current_time();
		$media->save();
		$media_id = (int) $media->id;

		$meta_prefix = 'video' === $type ? 'bp_video' : 'bp_media';
		update_post_meta( $attachment_id, $meta_prefix . '_parent_activity_id', $activity_id );

		$ids   = array_filter( explode( ',', (string) bp_activity_get_meta( $activity_id, $meta_prefix . '_ids', true ) ) );
		$ids[] = $media_id;
		bp_activity_update_meta( $activity_id, $meta_prefix . '_ids', implode( ',', $ids ) );

		return (int) $media_id;
	}

	/**
	 * Attach a document to an activity the way the uploader does.
	 *
	 * @param int $activity_id Activity or comment ID.
	 * @return int Document ID.
	 */
	protected function attach_document( $activity_id ) {
		$attachment_id = self::factory()->attachment->create_object(
			'p9606-' . wp_generate_password( 6, false ) . '.pdf',
			0,
			array(
				'post_mime_type' => 'application/pdf',
				'post_type'      => 'attachment',
			)
		);

		$document                = new BP_Document();
		$document->blog_id       = get_current_blog_id();
		$document->attachment_id = $attachment_id;
		$document->user_id       = $this->commenter;
		$document->title         = 'p9606';
		$document->activity_id   = $activity_id;
		$document->privacy       = 'comment';
		$document->status        = 'published';
		$document->date_created  = bp_core_current_time();
		$document->date_modified = bp_core_current_time();
		$document->save();
		$document_id = (int) $document->id;

		update_post_meta( $attachment_id, 'bp_document_parent_activity_id', $activity_id );

		$ids   = array_filter( explode( ',', (string) bp_activity_get_meta( $activity_id, 'bp_document_ids', true ) ) );
		$ids[] = $document_id;
		bp_activity_update_meta( $activity_id, 'bp_document_ids', implode( ',', $ids ) );

		return (int) $document_id;
	}

	/**
	 * Whether an activity row still exists, read straight from the table.
	 *
	 * @param int $activity_id Activity ID.
	 * @return bool
	 */
	protected function activity_exists( $activity_id ) {
		global $wpdb;

		$table = buddypress()->activity->table_name;

		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $activity_id ) ); // phpcs:ignore
	}

	/**
	 * Whether a media/video row still exists.
	 *
	 * @param int $media_id Media ID.
	 * @return bool
	 */
	protected function media_exists( $media_id ) {
		global $wpdb;

		$table = buddypress()->media->table_name;

		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $media_id ) ); // phpcs:ignore
	}

	/**
	 * Whether a document row still exists.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	protected function document_exists( $document_id ) {
		global $wpdb;

		$table = buddypress()->document->table_name;

		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $document_id ) ); // phpcs:ignore
	}

	/*
	 * Photo deleted from a comment.
	 */

	public function test_photo_delete_keeps_comment_with_text() {
		$comment = $this->create_comment( 'keep me' );
		$photo   = $this->attach_media( $comment );

		$this->assertNotEmpty( bp_media_delete( array( 'id' => $photo ) ) );

		$this->assertFalse( $this->media_exists( $photo ) );
		$this->assertTrue( $this->activity_exists( $comment ) );
	}

	public function test_photo_delete_keeps_comment_with_other_photos_and_replies() {
		$comment = $this->create_comment();
		$photo_1 = $this->attach_media( $comment );
		$photo_2 = $this->attach_media( $comment );
		$photo_3 = $this->attach_media( $comment );
		$reply   = $this->create_comment( 'reply from another member', self::factory()->user->create(), $comment );

		$this->assertNotEmpty( bp_media_delete( array( 'id' => $photo_2 ) ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->activity_exists( $reply ) );
		$this->assertTrue( $this->media_exists( $photo_1 ) );
		$this->assertFalse( $this->media_exists( $photo_2 ) );
		$this->assertTrue( $this->media_exists( $photo_3 ) );
		$this->assertSame( array( $photo_1, $photo_3 ), wp_parse_id_list( bp_activity_get_meta( $comment, 'bp_media_ids', true ) ) );
	}

	public function test_photo_delete_keeps_comment_with_a_document() {
		$comment  = $this->create_comment();
		$photo    = $this->attach_media( $comment );
		$document = $this->attach_document( $comment );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->document_exists( $document ) );
		// The document meta must survive the photo meta cleanup (V-7).
		$this->assertSame( array( $document ), wp_parse_id_list( bp_activity_get_meta( $comment, 'bp_document_ids', true ) ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_media_ids', true ) );
	}

	public function test_photo_delete_keeps_comment_with_a_video() {
		$comment = $this->create_comment();
		$photo   = $this->attach_media( $comment );
		$video   = $this->attach_media( $comment, 'video' );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->media_exists( $video ) );
	}

	public function test_photo_delete_keeps_comment_with_a_gif() {
		$comment = $this->create_comment();
		$photo   = $this->attach_media( $comment );
		bp_activity_update_meta( $comment, '_gif_data', array( 'still' => 1, 'mp4' => 2 ) );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
	}

	public function test_last_photo_delete_deletes_empty_comment_and_its_replies() {
		$comment = $this->create_comment();
		$photo   = $this->attach_media( $comment );
		$reply   = $this->create_comment( 'reply', self::factory()->user->create(), $comment );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertFalse( $this->activity_exists( $comment ) );
		$this->assertFalse( $this->activity_exists( $reply ) );
		$this->assertTrue( $this->activity_exists( $this->activity_id ) );
	}

	/*
	 * Document deleted from a comment.
	 */

	public function test_document_delete_keeps_comment_with_text() {
		$comment  = $this->create_comment( 'keep me' );
		$document = $this->attach_document( $comment );

		$this->assertNotEmpty( bp_document_delete( array( 'id' => $document ) ) );

		$this->assertFalse( $this->document_exists( $document ) );
		$this->assertTrue( $this->activity_exists( $comment ) );
	}

	public function test_document_delete_keeps_comment_with_other_documents() {
		$comment    = $this->create_comment();
		$document_1 = $this->attach_document( $comment );
		$document_2 = $this->attach_document( $comment );

		bp_document_delete( array( 'id' => $document_1 ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->document_exists( $document_2 ) );
	}

	public function test_document_delete_keeps_comment_with_a_photo() {
		$comment  = $this->create_comment();
		$document = $this->attach_document( $comment );
		$photo    = $this->attach_media( $comment );

		bp_document_delete( array( 'id' => $document ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->media_exists( $photo ) );
		// The photo meta must survive the document meta cleanup (V-7).
		$this->assertSame( array( $photo ), wp_parse_id_list( bp_activity_get_meta( $comment, 'bp_media_ids', true ) ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_document_ids', true ) );
	}

	public function test_last_document_delete_deletes_empty_comment() {
		$comment  = $this->create_comment();
		$document = $this->attach_document( $comment );

		bp_document_delete( array( 'id' => $document ) );

		$this->assertFalse( $this->activity_exists( $comment ) );
	}

	/*
	 * Video deleted from a comment.
	 */

	public function test_video_delete_keeps_comment_with_text() {
		$comment = $this->create_comment( 'keep me' );
		$video   = $this->attach_media( $comment, 'video' );

		$this->assertNotEmpty( bp_video_delete( array( 'id' => $video ) ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
	}

	public function test_video_delete_keeps_comment_with_a_document() {
		$comment  = $this->create_comment();
		$video    = $this->attach_media( $comment, 'video' );
		$document = $this->attach_document( $comment );

		bp_video_delete( array( 'id' => $video ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->document_exists( $document ) );
		// The document meta must survive the video meta cleanup (V-7).
		$this->assertSame( array( $document ), wp_parse_id_list( bp_activity_get_meta( $comment, 'bp_document_ids', true ) ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_video_ids', true ) );
	}

	public function test_video_delete_keeps_comment_with_other_videos() {
		$comment = $this->create_comment();
		$video_1 = $this->attach_media( $comment, 'video' );
		$video_2 = $this->attach_media( $comment, 'video' );

		bp_video_delete( array( 'id' => $video_1 ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->media_exists( $video_2 ) );
	}

	/**
	 * A video-only comment as the comment forms create it: with no text they
	 * send `&#8203;` as the content (buddypress-activity.js, classic and RL),
	 * and that placeholder is what gets stored.
	 */
	public function test_last_video_delete_deletes_video_only_comment_and_its_replies() {
		$comment = $this->create_comment( '&#8203;' );
		$video   = $this->attach_media( $comment, 'video' );
		$reply   = $this->create_comment( 'reply', self::factory()->user->create(), $comment );

		// The fixture must match what the UI stores, or this test proves nothing.
		$this->assertSame( '&#8203;', ( new BP_Activity_Activity( $comment ) )->content );

		bp_video_delete( array( 'id' => $video ) );

		$this->assertFalse( $this->activity_exists( $comment ) );
		$this->assertFalse( $this->activity_exists( $reply ) );
		$this->assertTrue( $this->activity_exists( $this->activity_id ) );
	}

	public function test_video_delete_keeps_video_only_comment_with_other_videos() {
		$comment = $this->create_comment( '&#8203;' );
		$video_1 = $this->attach_media( $comment, 'video' );
		$video_2 = $this->attach_media( $comment, 'video' );

		bp_video_delete( array( 'id' => $video_1 ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertTrue( $this->media_exists( $video_2 ) );
	}

	public function test_last_video_delete_deletes_empty_comment() {
		$comment = $this->create_comment();
		$video   = $this->attach_media( $comment, 'video' );

		bp_video_delete( array( 'id' => $video ) );

		$this->assertFalse( $this->activity_exists( $comment ) );
	}

	/*
	 * Activity-level behaviour is unchanged.
	 */

	public function test_photo_delete_keeps_activity_with_text() {
		$activity = self::factory()->activity->create(
			array(
				'user_id' => $this->commenter,
				'content' => 'post with a photo',
				'type'    => 'activity_update',
			)
		);
		$photo    = $this->attach_media( $activity );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertTrue( $this->activity_exists( $activity ) );
	}

	public function test_last_photo_delete_deletes_empty_activity() {
		$activity = self::factory()->activity->create(
			array(
				'user_id' => $this->commenter,
				'content' => '',
				'type'    => 'activity_update',
			)
		);
		$photo    = $this->attach_media( $activity );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertFalse( $this->activity_exists( $activity ) );
	}

	/*
	 * The helper itself.
	 */

	public function test_helper_rejects_non_comments_and_missing_ids() {
		$this->assertFalse( bb_activity_comment_has_remaining_content( $this->activity_id ) );
		$this->assertFalse( bb_activity_comment_has_remaining_content( 0 ) );
		$this->assertFalse( bb_activity_comment_has_remaining_content( PHP_INT_MAX ) );
	}

	public function test_helper_treats_markup_only_text_as_empty() {
		$comment = $this->create_comment( '<p> </p>' );

		$this->assertFalse( bb_activity_comment_has_remaining_content( $comment ) );

		$this->attach_media( $comment );
		$this->assertTrue( bb_activity_comment_has_remaining_content( $comment ) );
	}

	/**
	 * Placeholder content that the forms or the templates treat as empty.
	 *
	 * @return array
	 */
	public function placeholder_content_provider() {
		return array(
			'zero-width entity'        => array( '&#8203;' ),
			'zero-width in markup'     => array( '<p>&#8203;</p>' ),
			'zero-width character'     => array( "\xE2\x80\x8B" ),
			'no-break space entity'    => array( '&nbsp;' ),
			'no-break space character' => array( "\xC2\xA0" ),
			'whitespace'               => array( "  \n\t " ),
			'line break markup'        => array( '<br>' ),
			'mixed placeholders'       => array( '&#8203; &nbsp;<br>' ),
		);
	}

	/**
	 * @dataProvider placeholder_content_provider
	 *
	 * @param string $content Stored comment content.
	 */
	public function test_helper_treats_placeholder_content_as_empty( $content ) {
		$comment = $this->create_comment();
		$this->set_comment_content( $comment, $content );

		$this->assertFalse( bb_activity_comment_has_remaining_content( $comment ) );
	}

	public function test_helper_counts_real_text_next_to_placeholders() {
		$comment = $this->create_comment();

		foreach ( array( '&#8203;ok', '0', '&nbsp;a&nbsp;', '<p>&#8203;</p><p>x</p>' ) as $content ) {
			$this->set_comment_content( $comment, $content );
			$this->assertTrue( bb_activity_comment_has_remaining_content( $comment ), $content );
		}
	}

	/**
	 * Store the content as-is, bypassing the save filters, so the helper reads
	 * exactly the given value.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $content    Content.
	 */
	protected function set_comment_content( $comment_id, $content ) {
		global $wpdb;

		$wpdb->update( buddypress()->activity->table_name, array( 'content' => $content ), array( 'id' => $comment_id ) );
		wp_cache_delete( $comment_id, 'bp_activity' );
		$this->assertSame( $content, ( new BP_Activity_Activity( $comment_id ) )->content );
	}

	public function test_helper_result_is_filterable() {
		$comment = $this->create_comment();
		$photo   = $this->attach_media( $comment );

		add_filter( 'bb_activity_comment_has_remaining_content', '__return_true' );
		bp_media_delete( array( 'id' => $photo ) );
		remove_filter( 'bb_activity_comment_has_remaining_content', '__return_true' );

		$this->assertTrue( $this->activity_exists( $comment ) );
	}

	/*
	 * Attachment meta on a kept comment.
	 */

	public function test_last_photo_delete_clears_media_meta_on_kept_comment() {
		$comment = $this->create_comment( 'keep me' );
		$photo   = $this->attach_media( $comment );
		bp_activity_update_meta( $comment, 'bp_media_id', $photo );
		bp_activity_update_meta( $comment, 'bp_media_activity', '1' );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_media_ids', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_media_id', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_media_activity', true ) );
	}

	public function test_photo_delete_removes_only_the_deleted_id_from_kept_comment_meta() {
		$comment = $this->create_comment();
		$photo_1 = $this->attach_media( $comment );
		$photo_2 = $this->attach_media( $comment );
		bp_activity_update_meta( $comment, 'bp_media_id', $photo_1 );

		bp_media_delete( array( 'id' => $photo_1 ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertSame( (string) $photo_2, (string) bp_activity_get_meta( $comment, 'bp_media_ids', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_media_id', true ) );
	}

	public function test_last_document_delete_clears_document_meta_on_kept_comment() {
		$comment  = $this->create_comment( 'keep me' );
		$document = $this->attach_document( $comment );
		bp_activity_update_meta( $comment, 'bp_document_id', $document );
		bp_activity_update_meta( $comment, 'bp_document_activity', '1' );

		bp_document_delete( array( 'id' => $document ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_document_ids', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_document_id', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_document_activity', true ) );
	}

	public function test_last_video_delete_clears_video_meta_on_kept_comment() {
		$comment = $this->create_comment( 'keep me' );
		$video   = $this->attach_media( $comment, 'video' );
		bp_activity_update_meta( $comment, 'bp_video_id', $video );
		bp_activity_update_meta( $comment, 'bp_video_activity', '1' );

		bp_video_delete( array( 'id' => $video ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_video_ids', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_video_id', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_video_activity', true ) );
	}

	public function test_meta_helper_ignores_unknown_types() {
		$comment = $this->create_comment( 'keep me' );
		bp_activity_update_meta( $comment, 'bp_foo_ids', '5' );

		bb_activity_comment_remove_deleted_attachment_meta( $comment, 'foo', array( 5 ) );

		$this->assertSame( '5', (string) bp_activity_get_meta( $comment, 'bp_foo_ids', true ) );
	}

	/**
	 * Partial delete: the deleted ID leaves the list, while bp_{type}_id that
	 * points at a surviving item and the bp_{type}_activity flag are kept (V-4).
	 *
	 * @return array
	 */
	public function attachment_type_provider() {
		return array(
			'photo'    => array( 'media' ),
			'document' => array( 'document' ),
			'video'    => array( 'video' ),
		);
	}

	/**
	 * Attach one item of the given meta type.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $type       'media', 'document' or 'video'.
	 * @return int Item ID.
	 */
	protected function attach_by_type( $comment_id, $type ) {
		if ( 'document' === $type ) {
			return $this->attach_document( $comment_id );
		}

		return $this->attach_media( $comment_id, 'video' === $type ? 'video' : 'photo' );
	}

	/**
	 * Delete one item of the given meta type through the public API.
	 *
	 * @param int    $id   Item ID.
	 * @param string $type 'media', 'document' or 'video'.
	 */
	protected function delete_by_type( $id, $type ) {
		$function = 'media' === $type ? 'bp_media_delete' : 'bp_' . $type . '_delete';

		$this->assertNotEmpty( $function( array( 'id' => $id ) ) );
	}

	/**
	 * @dataProvider attachment_type_provider
	 *
	 * @param string $type Meta type.
	 */
	public function test_partial_delete_keeps_surviving_id_and_activity_flag( $type ) {
		$comment = $this->create_comment();
		$item_1  = $this->attach_by_type( $comment, $type );
		$item_2  = $this->attach_by_type( $comment, $type );
		$item_3  = $this->attach_by_type( $comment, $type );
		bp_activity_update_meta( $comment, 'bp_' . $type . '_id', $item_3 );
		bp_activity_update_meta( $comment, 'bp_' . $type . '_activity', '1' );

		$this->delete_by_type( $item_1, $type );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertSame( $item_2 . ',' . $item_3, (string) bp_activity_get_meta( $comment, 'bp_' . $type . '_ids', true ) );
		$this->assertSame( (string) $item_3, (string) bp_activity_get_meta( $comment, 'bp_' . $type . '_id', true ) );
		$this->assertSame( '1', (string) bp_activity_get_meta( $comment, 'bp_' . $type . '_activity', true ) );
	}

	/**
	 * A document whose ID equals a deleted photo's ID must stay in the
	 * comment's document meta: the cleanup is scoped to the deleted type (V-7).
	 */
	public function test_photo_delete_keeps_document_meta_with_the_same_id() {
		global $wpdb;

		$comment  = $this->create_comment();
		$photo    = $this->attach_media( $comment );
		$document = $this->attach_document( $comment );

		// Give the document the photo's numeric ID.
		$table = buddypress()->document->table_name;
		$this->assertEmpty( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $photo ) ) ); // phpcs:ignore
		$wpdb->update( $table, array( 'id' => $photo ), array( 'id' => $document ) ); // phpcs:ignore
		wp_cache_flush();
		bp_activity_update_meta( $comment, 'bp_document_ids', (string) $photo );
		$this->assertTrue( $this->document_exists( $photo ) );

		bp_media_delete( array( 'id' => $photo ) );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertFalse( $this->media_exists( $photo ) );
		$this->assertTrue( $this->document_exists( $photo ) );
		$this->assertSame( (string) $photo, (string) bp_activity_get_meta( $comment, 'bp_document_ids', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_media_ids', true ) );
	}

	/**
	 * Empty input writes nothing: no deleted IDs, or no comment ID.
	 */
	public function test_meta_helper_with_empty_input_writes_nothing() {
		$comment = $this->create_comment( 'keep me' );
		bp_activity_update_meta( $comment, 'bp_media_id', 77 );
		bp_activity_update_meta( $comment, 'bp_media_activity', '1' );

		bb_activity_comment_remove_deleted_attachment_meta( $comment, 'media', array() );
		bb_activity_comment_remove_deleted_attachment_meta( $comment, 'media', '' );
		bb_activity_comment_remove_deleted_attachment_meta( 0, 'media', array( 77 ) );

		$this->assertSame( '77', (string) bp_activity_get_meta( $comment, 'bp_media_id', true ) );
		$this->assertSame( '1', (string) bp_activity_get_meta( $comment, 'bp_media_activity', true ) );
		$this->assertEmpty( bp_activity_get_meta( $comment, 'bp_media_ids', true ) );
	}

	/*
	 * Saving an edit that still lists a deleted attachment (F-R7-1).
	 */

	/**
	 * Edit-save after a per-item delete without a reload: the stale form still
	 * sends the deleted item. The add handler must not push the previous
	 * item's ID again for it: the handlers reset $media_id / $video_id /
	 * $document_id on each loop pass, so a skipped (deleted) entry adds
	 * nothing (before, the meta became e.g. 501,501,503).
	 *
	 * @dataProvider attachment_type_provider
	 *
	 * @param string $type Meta type.
	 */
	public function test_add_handler_does_not_duplicate_ids_for_a_deleted_item( $type ) {
		$comment = $this->create_comment( 'keep me' );
		$item_1  = $this->attach_by_type( $comment, $type );
		$item_2  = $this->attach_by_type( $comment, $type );
		$item_3  = $this->attach_by_type( $comment, $type );

		$this->delete_by_type( $item_2, $type );

		$key     = 'media' === $type ? 'media_id' : $type . '_id';
		$entries = array();
		foreach ( array( $item_1, $item_2, $item_3 ) as $id ) {
			$entries[] = array( $key => $id );
		}

		$handler = 'bp_' . $type . '_add_handler';
		// Not wp_parse_id_list(): it de-duplicates and would hide the defect.
		$ids     = array_map( 'intval', array_values( (array) $handler( $entries, 'public' ) ) );

		$this->assertSame( array( $item_1, $item_3 ), $ids );
	}

	/*
	 * Cache refresh for a kept comment (V-2).
	 */

	/**
	 * Deleting one attachment from a kept comment fires
	 * bb_activity_comment_attachment_deleted, on the partial and the
	 * last-attachment branch, so caches of the comment and its thread
	 * (the App API cache) are purged. bp_activity_delete_comment does not
	 * fire for a kept comment.
	 *
	 * @dataProvider attachment_type_provider
	 *
	 * @param string $type Meta type.
	 */
	public function test_kept_comment_fires_attachment_deleted_action( $type ) {
		$calls  = array();
		$record = function ( $comment_id, $deleted_type, $deleted_ids ) use ( &$calls ) {
			$calls[] = array( (int) $comment_id, $deleted_type, array_map( 'intval', (array) $deleted_ids ) );
		};
		add_action( 'bb_activity_comment_attachment_deleted', $record, 10, 3 );

		$comment = $this->create_comment( 'keep me' );
		$item_1  = $this->attach_by_type( $comment, $type );
		$item_2  = $this->attach_by_type( $comment, $type );

		$this->delete_by_type( $item_1, $type );
		$this->delete_by_type( $item_2, $type );

		remove_action( 'bb_activity_comment_attachment_deleted', $record, 10 );

		$this->assertTrue( $this->activity_exists( $comment ) );
		$this->assertSame(
			array(
				array( $comment, $type, array( $item_1 ) ),
				array( $comment, $type, array( $item_2 ) ),
			),
			$calls
		);
	}

	/**
	 * The action does not fire when the comment is deleted with its last
	 * attachment; bp_activity_delete_comment covers that case.
	 */
	public function test_deleted_comment_does_not_fire_attachment_deleted_action() {
		$fired  = 0;
		$record = function () use ( &$fired ) {
			$fired++;
		};
		add_action( 'bb_activity_comment_attachment_deleted', $record );

		$comment = $this->create_comment();
		$photo   = $this->attach_media( $comment );

		$this->delete_by_type( $photo, 'media' );

		remove_action( 'bb_activity_comment_attachment_deleted', $record );

		$this->assertFalse( $this->activity_exists( $comment ) );
		$this->assertSame( 0, $fired );
	}
}
