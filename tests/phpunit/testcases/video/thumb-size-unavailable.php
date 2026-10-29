<?php
/**
 * Video poster thumbnail sizes the poster is too small to produce (PROD-9829).
 *
 * A poster smaller than a registered size can never produce that size, so
 * bb_video_get_attachment_symlink() must regenerate it once, record it, and skip it
 * on later requests instead of regenerating the whole attachment on every page load.
 *
 * @group video
 * @group video_thumb_size_unavailable
 */
class BP_Tests_Video_Thumb_Size_Unavailable extends BP_UnitTestCase {

	/**
	 * Number of wp_generate_attachment_metadata() runs seen.
	 *
	 * @var int
	 */
	protected $regenerations = 0;

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'BP_Video' ) || empty( buddypress()->video->table_name ) ) {
			$this->markTestSkipped( 'The video component is not loaded.' );
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			$this->markTestSkipped( 'GD is not available.' );
		}

		$this->regenerations = 0;
		add_filter( 'bb_enable_symlinks', '__return_true' );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'count_regeneration' ) );
	}

	public function tearDown(): void {
		remove_filter( 'bb_enable_symlinks', '__return_true' );
		remove_filter( 'wp_generate_attachment_metadata', array( $this, 'count_regeneration' ) );
		remove_filter( 'bb_video_get_image_sizes', array( $this, 'shrink_popup_size' ) );

		parent::tearDown();
	}

	/**
	 * Count metadata regenerations.
	 *
	 * @param array $metadata Attachment metadata.
	 *
	 * @return array
	 */
	public function count_regeneration( $metadata ) {
		$this->regenerations++;

		return $metadata;
	}

	/**
	 * Register the popup size smaller than the 480x270 test poster.
	 *
	 * @param array $sizes Image sizes.
	 *
	 * @return array
	 */
	public function shrink_popup_size( $sizes ) {
		$sizes['bb-video-poster-popup-image'] = array(
			'height' => 200,
			'width'  => 300,
		);

		return $sizes;
	}

	/**
	 * Create a video and a trusted poster image of the given dimensions.
	 *
	 * @param int $width  Poster width.
	 * @param int $height Poster height.
	 *
	 * @return array BP_Video and the poster attachment ID.
	 */
	protected function create_video_with_poster( $width, $height ) {
		$user_id = self::factory()->user->create();

		$file  = wp_tempnam( 'bb-video-poster.jpg' );
		$image = imagecreatetruecolor( $width, $height );
		imagejpeg( $image, $file );
		imagedestroy( $image );

		$upload = wp_upload_bits( 'bb-video-poster-' . $width . 'x' . $height . '.jpg', null, file_get_contents( $file ) );
		unlink( $file );

		$poster_id = self::factory()->attachment->create_object(
			$upload['file'],
			0,
			array(
				'post_author'    => $user_id,
				'post_mime_type' => 'image/jpeg',
			)
		);
		update_post_meta( $poster_id, 'bp_video_thumbnail_upload', 1 );
		wp_update_attachment_metadata(
			$poster_id,
			array(
				'width'  => $width,
				'height' => $height,
				'file'   => _wp_relative_upload_path( $upload['file'] ),
				'sizes'  => array(),
			)
		);

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

		$this->regenerations = 0;

		return array( new BP_Video( $video_id ), $poster_id );
	}

	public function test_small_poster_size_is_regenerated_once_then_skipped() {
		list( $video, $poster_id ) = $this->create_video_with_poster( 480, 270 );

		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-poster-popup-image' );
		$this->assertSame( 1, $this->regenerations, 'The first request tries the size once.' );
		$this->assertSame( '1500x900', get_post_meta( $poster_id, '_bb_video_thumb_unavailable_bb-video-poster-popup-image', true ) );

		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-poster-popup-image' );
		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-poster-popup-image' );
		$this->assertSame( 1, $this->regenerations, 'Later requests must not regenerate a size the poster is too small for.' );
	}

	public function test_each_size_is_recorded_separately() {
		list( $video, $poster_id ) = $this->create_video_with_poster( 480, 270 );

		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-poster-popup-image' );
		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-activity-image' );

		$this->assertTrue( bb_video_is_thumb_size_unavailable( $poster_id, 'bb-video-poster-popup-image' ) );
		$this->assertTrue( bb_video_is_thumb_size_unavailable( $poster_id, 'bb-video-activity-image' ) );
		$this->assertFalse( bb_video_is_thumb_size_unavailable( $poster_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' ), 'A size the poster can produce is never recorded.' );
	}

	public function test_large_poster_size_is_not_recorded() {
		list( $video, $poster_id ) = $this->create_video_with_poster( 1600, 1000 );

		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-poster-popup-image' );

		$this->assertFalse( bb_video_is_thumb_size_unavailable( $poster_id, 'bb-video-poster-popup-image' ) );
		$this->assertSame( '', get_post_meta( $poster_id, '_bb_video_thumb_unavailable_bb-video-poster-popup-image', true ) );
	}

	public function test_record_stops_applying_when_size_dimensions_change() {
		list( $video, $poster_id ) = $this->create_video_with_poster( 480, 270 );

		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-poster-popup-image' );
		$this->assertTrue( bb_video_is_thumb_size_unavailable( $poster_id, 'bb-video-poster-popup-image' ) );

		// The site now registers the size smaller than the poster.
		add_filter( 'bb_video_get_image_sizes', array( $this, 'shrink_popup_size' ) );
		$this->regenerations = 0;

		$this->assertFalse( bb_video_is_thumb_size_unavailable( $poster_id, 'bb-video-poster-popup-image' ) );

		bb_video_get_attachment_symlink( $video, $poster_id, 'bb-video-poster-popup-image' );
		$this->assertSame( 1, $this->regenerations, 'A record made for other dimensions must not block regeneration.' );
		$this->assertNotEmpty( image_get_intermediate_size( $poster_id, 'bb-video-poster-popup-image' ), 'The resized size is now generated.' );
	}

	public function test_poster_without_dimensions_is_not_recorded() {
		list( , $poster_id ) = $this->create_video_with_poster( 480, 270 );

		// A failed or partial regeneration leaves no dimensions: that may be transient, so keep retrying.
		wp_update_attachment_metadata( $poster_id, array() );

		$this->assertFalse( bb_video_maybe_mark_thumb_size_unavailable( $poster_id, 'bb-video-poster-popup-image' ) );
		$this->assertFalse( bb_video_is_thumb_size_unavailable( $poster_id, 'bb-video-poster-popup-image' ) );
	}

	public function test_unregistered_size_is_never_recorded() {
		list( , $poster_id ) = $this->create_video_with_poster( 480, 270 );

		$this->assertSame( array(), bb_video_get_image_size_dimensions( 'not-a-video-size' ) );
		$this->assertFalse( bb_video_maybe_mark_thumb_size_unavailable( $poster_id, 'not-a-video-size' ) );
		$this->assertFalse( bb_video_is_thumb_size_unavailable( $poster_id, 'not-a-video-size' ) );
	}
}
