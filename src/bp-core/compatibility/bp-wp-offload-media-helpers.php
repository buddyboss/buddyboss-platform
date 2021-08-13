<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_AS3CF_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss 1.7.4
 */
class BB_AS3CF_Plugin_Compatibility {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since BuddyBoss 1.7.4
	 */
	private static $instance = null;

	/**
	 * BB_AS3CF_Plugin_Compatibility constructor.
	 */
	public function __construct() {

		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 1.7.4
	 *
	 * @return Controller|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Register the compatibility hooks for the plugin.
	 */
	public function compatibility_init() {

		add_filter( 'as3cf_get_attached_file_copy_back_to_local', '__return_true' );

		add_filter( 'bb_media_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_document_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_video_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_video_create_thumb_symlinks', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );

		add_filter( 'bp_document_get_preview_url', array( $this, 'bp_document_offload_get_preview_url' ), PHP_INT_MAX, 6 );
		add_filter( 'bp_media_get_preview_image_url', array( $this, 'bp_media_offload_get_preview_url' ), PHP_INT_MAX, 5 );
		add_filter( 'bb_video_get_thumb_url', array( $this, 'bp_video_offload_get_thumb_preview_url' ), PHP_INT_MAX, 5 );
		add_filter( 'bb_video_get_symlink', array( $this, 'bp_video_offload_get_video_url' ), PHP_INT_MAX, 4 );

	}

	/**
	 * Function to set the false to use the default media symlink instead use the offload media URL of media.
	 *
	 * @param bool   $can           default true.
	 * @param int    $id            media/document/video id.
	 * @param int    $attachment_id attachment id.
	 * @param string $size          preview size.
	 *
	 * @return bool true if the offload media used.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bb_offload_do_symlink( $can, $id, $attachment_id, $size ) {

		$remove_local_files_setting = bp_get_option( Amazon_S3_And_CloudFront::SETTINGS_KEY );
		$server_from_local          = (bool) $remove_local_files_setting['serve-from-s3'];

		if ( ! $server_from_local ) {
			return true;
		}

		$wp_upload_directory = wp_get_upload_dir();
		$attachment_url      = wp_get_attachment_url( $attachment_id );
		$upload_base_url     = $wp_upload_directory['baseurl'];

		// If the URL from the local then use the symlink/rewrite_url based on settings.
		if ( strpos( $attachment_url, $upload_base_url ) !== false ) {
			$can = true;
		} else {
			$can = false;
		}

		return $can;
	}

	/**
	 * Return the offload media plugin attachment url.
	 *
	 * @param string $attachment_url attachment url.
	 * @param int    $document_id    media id.
	 * @param string $extension      extension.
	 * @param string $size           size of the media.
	 * @param int    $attachment_id  attachment id.
	 * @param bool   $symlink        display symlink or not.
	 *
	 * @return false|mixed|string return the original document URL.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bp_document_offload_get_preview_url( $attachment_url, $document_id, $extension, $size, $attachment_id, $symlink ) {

		if ( ! $symlink && in_array( $extension, bp_get_document_preview_doc_extensions(), true ) ) {
			$get_metadata = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $get_metadata ) && isset( $get_metadata['sizes'] ) && isset( $get_metadata['sizes'][ $size ] ) ) {
				$attachment_url = wp_get_attachment_image_url( $attachment_id, $size );
			} else {
				$attachment_url = wp_get_attachment_image_url( $attachment_id, 'full' );
			}

			$image_array = @getimagesize( $attachment_url );

			if ( ! $attachment_url || empty( $image_array ) ) {

				bp_document_generate_document_previews( $attachment_id );

				if ( ! empty( $get_metadata ) && isset( $get_metadata['sizes'] ) && isset( $get_metadata['sizes'][ $size ] ) ) {
					$attachment_url = wp_get_attachment_image_url( $attachment_id, $size );
				} else {
					$attachment_url = wp_get_attachment_image_url( $attachment_id, 'full' );
				}
			}
		}

		if ( ! $symlink && in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
			if ( ! empty( $attachment_id ) && ! empty( $document_id ) ) {
				$attachment_url = wp_get_attachment_url( $attachment_id );
			}
		}

		return $attachment_url;
	}

	/**
	 * Return the offload media plugin attachment url.
	 *
	 * @param string $attachment_url attachment url.
	 * @param int    $media_id       media id.
	 * @param int    $attachment_id  attachment id.
	 * @param string $size           size of the media.
	 * @param bool   $symlink        display symlink or not.
	 *
	 * @return false|mixed|string return the original media URL.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bp_media_offload_get_preview_url( $attachment_url, $media_id, $attachment_id, $size, $symlink ) {

		if ( ! $symlink ) {
			$media          = new BP_Media( $media_id );
			$attachment_url = wp_get_attachment_url( $media->attachment_id );
		}

		return $attachment_url;
	}

	/**
	 * Return the offload media plugin attachment url.
	 *
	 * @param string $attachment_url Attachment url.
	 * @param int    $video_id       Video id.
	 * @param string $size           size of the media.
	 * @param int    $attachment_id  Attachment id.
	 * @param bool   $symlink        display symlink or not.
	 *
	 * @return false|mixed|string return the original document URL.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bp_video_offload_get_thumb_preview_url( $attachment_url, $video_id, $size, $attachment_id, $symlink ) {

		if ( ! $symlink ) {
			$get_metadata = wp_get_attachment_metadata( $attachment_id );
			if ( ! empty( $get_metadata ) && isset( $get_metadata['sizes'] ) && isset( $get_metadata['sizes'][ $size ] ) ) {
				$attachment_url = wp_get_attachment_image_url( $attachment_id, $size );
			} else {
				$attachment_url = wp_get_attachment_url( $attachment_id );
			}
		}

		return $attachment_url;
	}

	/**
	 * Return the offload media plugin attachment url.
	 *
	 * @param string $attachment_url Attachment url.
	 * @param int    $video_id       Video id.
	 * @param int    $attachment_id  Attachment id.
	 * @param bool   $symlink        display symlink or not.
	 *
	 * @return string $attachment_url Attachment URL.
	 */
	public function bp_video_offload_get_video_url( $attachment_url, $video_id, $attachment_id, $symlink ) {

		if ( ! $symlink ) {
			$attachment_url = wp_get_attachment_url( $attachment_id );
		}

		return $attachment_url;
	}

}

BB_AS3CF_Plugin_Compatibility::instance();
