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

		add_filter( 'bb_media_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_document_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_video_do_symlink', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_video_create_thumb_symlinks', array( $this, 'bb_offload_do_symlink' ), PHP_INT_MAX, 4 );

		add_filter( 'bp_document_get_preview_url', array( $this, 'bp_document_offload_get_preview_url' ), PHP_INT_MAX, 6 );
		add_filter( 'bp_media_get_preview_image_url', array( $this, 'bp_media_offload_get_preview_url' ), PHP_INT_MAX, 5 );
		add_filter( 'bb_video_get_thumb_url', array( $this, 'bp_video_offload_get_thumb_preview_url' ), PHP_INT_MAX, 5 );
		add_filter( 'bb_video_get_symlink', array( $this, 'bp_video_offload_get_video_url' ), PHP_INT_MAX, 4 );
		add_filter( 'bb_media_settings_callback_symlink_direct_access', array( $this, 'bb_media_directory_callback_check_access' ), PHP_INT_MAX, 2 );
		add_filter( 'bb_media_check_default_access', array( $this, 'bb_media_check_default_access_access' ), PHP_INT_MAX, 1 );
		add_filter( 'bbp_get_topic_content', array( $this, 'bb_offload_get_content' ), 10, 1 );

		add_action( 'bp_core_before_regenerate_attachment_thumbnails', array( $this, 'bb_offload_download_add_back_to_local' ) );
		add_action( 'bp_core_after_regenerate_attachment_thumbnails', array( $this, 'bb_offload_download_remove_back_to_local' ) );

	}

	/**
	 * If the remove file from server selected then no need to check media permission.
	 *
	 * @param bool $bypass Whether to bypass check for the media directory.
	 *
	 * @return bool Whether to bypass check for the media directory.
	 *
	 * @since BuddyBoss 1.8.0
	 */
	public function bb_media_check_default_access_access( $bypass ) {
		$remove_local_files_setting = bp_get_option( Amazon_S3_And_CloudFront::SETTINGS_KEY );

		if ( isset( $remove_local_files_setting ) && isset( $remove_local_files_setting['remove-local-file'] ) && '1' === $remove_local_files_setting['remove-local-file'] ) {
			$bypass = true;
		}

		return $bypass;
	}

	/**
	 * Check Media accessible.
	 *
	 * @param array $directory  Directory list.
	 * @param array $sample_ids Sample uploaded ids.
	 *
	 * @return array|mixed
	 *
	 * @since BuddyBoss 1.8.0
	 */
	public function bb_media_directory_callback_check_access( $directory, $sample_ids ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $directory ) && class_exists( 'AS3CF_Utils' ) ) {
			foreach ( $sample_ids as $id => $v ) {
				$paths         = AS3CF_Utils::get_attachment_file_paths( $v, false, false, false );
				$original_path = ( isset( $paths ) && isset( $paths['original'] ) ? $paths['original'] : $paths['__as3cf_primary'] );
				$file          = str_replace( $uploads['basedir'], $uploads['baseurl'], $original_path );
				$fetch         = wp_remote_get( $file );
				if ( ! is_wp_error( $fetch ) && isset( $fetch['response']['code'] ) && 200 === $fetch['response']['code'] ) {
					$directory[] = $id;
				}
			}
		}

		return $directory;
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
		$server_from_local          = ( isset( $remove_local_files_setting ) && isset( $remove_local_files_setting['serve-from-s3'] ) && (bool) $remove_local_files_setting['serve-from-s3'] );

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

			$image_array = ( false !== $attachment_url ? @getimagesize( $attachment_url ) : array() );

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
			$media        = new BP_Media( $media_id );
			$get_metadata = wp_get_attachment_metadata( $media->attachment_id );
			if ( ! empty( $get_metadata ) && isset( $get_metadata['sizes'] ) && isset( $get_metadata['sizes'][ $size ] ) ) {
				$attachment_url = wp_get_attachment_image_url( $media->attachment_id, $size );
			} else {
				$attachment_url = wp_get_attachment_url( $media->attachment_id );
			}
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

	/**
	 * Copy file to local when thumbnail is not available.
	 *
	 * @since BuddyBoss 1.8.7
	 */
	public function bb_offload_download_add_back_to_local() {
		add_filter( 'as3cf_get_attached_file_copy_back_to_local', '__return_true' );
	}

	/**
	 * Remove the filter to copy file to local when thumbnail is not available.
	 *
	 * @since BuddyBoss 1.8.7
	 */
	public function bb_offload_download_remove_back_to_local() {
		remove_filter( 'as3cf_get_attached_file_copy_back_to_local', '__return_true' );
	}

	/**
	 * Fix the media url issue with API post_content on API.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param string $content Post content.
	 *
	 * @return mixed|string
	 */
	public function bb_offload_get_content( $content ) {
		global $as3cf;

		if ( ! empty( $as3cf->filter_local ) ) {
			$content = $as3cf->filter_local->filter_post( $content );
		}

		return $content;
	}

}

BB_AS3CF_Plugin_Compatibility::instance();
