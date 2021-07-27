<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS3CF_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with AS3CF
 *
 * @since 0.8.3
 */
class BB_AS3CF_Plugin_Compatibility {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since 1.1.0
	 */
	private static $instance = null;

	/**
	 * @param Amazon_S3_And_CloudFront $as3cf
	 */
	public function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since 1.1.0
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

		add_filter( 'bb_media_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_document_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_video_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_video_create_thumb_symlinks', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );

		add_action( 'bb_before_document_upload_handler', array( $this, 'bb_offload_media_set_private' ) );
		add_action( 'bb_before_media_upload_handler', array( $this, 'bb_offload_media_set_private' ) );
		add_action( 'bb_before_video_upload_handler', array( $this, 'bb_offload_media_set_private' ) );
		add_action( 'bb_before_video_preview_image_by_js', array( $this, 'bb_offload_media_set_private' ) );
		add_action( 'bb_video_before_preview_generate', array( $this, 'bb_offload_media_set_private' ) );
		add_action( 'bb_before_bp_video_thumbnail_upload_handler', array( $this, 'bb_offload_media_set_private' ) );
		add_action( 'bb_document_before_generate_document_previews', array( $this, 'bb_offload_media_set_private' ) );

		add_action( 'bb_after_document_upload_handler', array( $this, 'bb_offload_media_unset_private' ) );
		add_action( 'bb_after_media_upload_handler', array( $this, 'bb_offload_media_unset_private' ) );
		add_action( 'bb_after_video_upload_handler', array( $this, 'bb_offload_media_unset_private' ) );
		add_action( 'bb_after_video_preview_image_by_js', array( $this, 'bb_offload_media_unset_private' ) );
		add_action( 'bb_video_after_preview_generate', array( $this, 'bb_offload_media_unset_private' ) );
		add_action( 'bb_after_bp_video_thumbnail_upload_handler', array( $this, 'bb_offload_media_unset_private' ) );
		add_action( 'bb_document_after_generate_document_previews', array( $this, 'bb_offload_media_unset_private' ) );

		add_filter( 'bp_document_get_preview_url', array( $this, 'bp_document_offload_get_preview_url' ), PHP_INT_MAX, 6 );
		add_filter( 'bp_media_get_preview_image_url', array( $this, 'bp_media_offload_get_preview_url' ), PHP_INT_MAX, 5 );
		add_filter( 'bb_video_get_thumb_url', array( $this, 'bp_video_offload_get_thumb_preview_url' ), PHP_INT_MAX, 5 );
		add_filter( 'bb_video_get_symlink', array( $this, 'bp_video_offload_get_video_url' ), PHP_INT_MAX, 4 );

		add_action( 'bb_try_before_video_background_create_thumbnail', array( $this, 'bb_video_set_wp_offload_download_video_local' ), 99999, 1 );
		add_action( 'bb_try_after_video_background_create_thumbnail', array( $this, 'bb_video_unset_wp_offload_download_video_local' ), 99999, 1 );

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

		/**
		 * @var Amazon_S3_And_CloudFront|\Amazon_S3_And_CloudFront_Pro $as3cf
		 */
		global $as3cf;

		if ( ! $as3cf->is_attachment_served_by_provider( $attachment_id ) ) {
			// Not served by provider, use symlink or rewrite url.
			$can = true;
		} else {
			$can = false;
		}

		return $can;
	}

	/**
	 * Copy to local media file when the offload media used and remove local file setting used in offload media plugin to regenerate the thumb of the PDF.
	 *
	 * @param bool               $default       default false.
	 * @param string             $file          file to download.
	 * @param int                $attachment_id attachment id.
	 * @param Media_Library_Item $as3cf_item    media library object.
	 *
	 * @return bool
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bb_document_as3cf_get_attached_file_copy_back_to_local( $default, $file, $attachment_id, $as3cf_item ) {
		$default = true;

		return $default;
	}

	/**
	 * Set the uploaded document to make private on offload media plugin.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bb_offload_media_set_private() {
		add_filter( 'as3cf_upload_acl', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
		add_filter( 'as3cf_upload_acl_sizes', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
	}

	/**
	 * Remove the private URL generate document preview.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bb_offload_media_unset_private() {
		remove_filter( 'as3cf_upload_acl', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
		remove_filter( 'as3cf_upload_acl_sizes', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
	}

	/**
	 * Return the offload media plugin attachment url.
	 *
	 * @param string $attachment_url attachment url.
	 * @param int    $document_id    media id.
	 * @param string $extension      extension.
	 * @param string $size           size of the media.
	 * @param int    $attachment_id  attachment id.
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

			$imageArray = @getimagesize( $attachment_url );

			if ( ! $attachment_url || empty( $imageArray ) ) {

				add_filter( 'as3cf_get_attached_file_copy_back_to_local', array( $this, 'bb_document_as3cf_get_attached_file_copy_back_to_local' ), PHP_INT_MAX, 4 );
				add_filter( 'as3cf_upload_acl', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
				add_filter( 'as3cf_upload_acl_sizes', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );

				bp_document_generate_document_previews( $attachment_id );

				remove_filter( 'as3cf_upload_acl', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
				remove_filter( 'as3cf_upload_acl_sizes', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
				remove_filter( 'as3cf_get_attached_file_copy_back_to_local', array( $this, 'bb_document_as3cf_get_attached_file_copy_back_to_local' ), PHP_INT_MAX, 4 );

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
	 * Make all the media to private signed URL if someone using the offload media to store in AWS.
	 *
	 * @handles `as3cf_upload_acl`
	 * @handles `as3cf_upload_acl_sizes`
	 *
	 * @param string $acl defaults to 'public-read'.
	 *
	 * @return string $acl make the media to private with signed url.
	 *
	 * @since   BuddyBoss 1.7.0
	 */
	public function bb_media_private_upload_acl( $acl ) {
		$acl = 'private';

		return $acl;
	}

	/**
	 * Filter to download the video on local server.
	 *
	 * @param BP_Video $video Video object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bb_video_set_wp_offload_download_video_local( $video ) {
		add_filter( 'as3cf_get_attached_file_copy_back_to_local', array( $this, 'bb_document_as3cf_get_attached_file_copy_back_to_local' ), PHP_INT_MAX, 4 );
		add_filter( 'as3cf_upload_acl', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
		add_filter( 'as3cf_upload_acl_sizes', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
	}

	/**
	 * Filter to download the video on local server.
	 *
	 * @param BP_Video $video Video object.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function bb_video_unset_wp_offload_download_video_local( $video ) {
		remove_filter( 'as3cf_upload_acl', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
		remove_filter( 'as3cf_upload_acl_sizes', array( $this, 'bb_media_private_upload_acl' ), 10, 1 );
		remove_filter( 'as3cf_get_attached_file_copy_back_to_local', array( $this, 'bb_document_as3cf_get_attached_file_copy_back_to_local' ), PHP_INT_MAX, 4 );
	}

	/**
	 * Return the offload media plugin attachment url.
	 *
	 * @param string $attachment_url Attachment url.
	 * @param int    $video_id       Video id.
	 * @param string $size           size of the media.
	 * @param int    $attachment_id  Attachment id.
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
	 *
	 * @return string $attachment_url Attachment URL.
	 */
	public function bp_video_offload_get_video_url( $attachment_url, $video_id, $attachment_id, $symlink ) {

		if (! $symlink ) {
			$attachment_url = wp_get_attachment_url( $attachment_id );
		}

		return $attachment_url;
	}

}

BB_AS3CF_Plugin_Compatibility::instance();
