<?php
/**
 * Helper class for the CDN.
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss 2.6.10
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_CDN_Helpers Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with the Platform
 */
class BB_CDN_Helpers {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss 2.6.10
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_CDN_Helpers constructor.
	 *
	 * @since BuddyBoss 2.6.10
	 */
	public function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 2.6.10
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
	 *
	 * @since BuddyBoss 2.6.10
	 */
	public function compatibility_init() {
		add_filter( 'bp_nouveau_object_template_result', array( $this, 'bb_template_ajax_content_add_cdn' ), 10, 1 );
		add_filter( 'bb_media_after_get_preview_image_url_symlink', array( $this, 'bb_media_preview_symlink_add_cdn' ), 10, 1 );
		add_filter( 'bb_video_after_get_symlink', array( $this, 'bb_media_preview_symlink_add_cdn' ), 10, 1 );
		add_filter( 'bb_video_after_get_attachment_symlink', array( $this, 'bb_media_preview_symlink_add_cdn' ), 10, 1 );
		add_filter( 'bp_document_get_preview_url', array( $this, 'bb_media_preview_symlink_add_cdn' ), 10, 1 );
		add_filter( 'bb_document_video_get_symlink', array( $this, 'bb_media_preview_symlink_add_cdn' ), 10, 1 );
	}

	/**
	 * Function to add CDN URL to ajax content.
	 *
	 * @since BuddyBoss 2.6.10
	 *
	 * @param array $result data array.
	 *
	 * @return array
	 */
	public function bb_template_ajax_content_add_cdn( $result ) {
		if ( class_exists( 'CDN_Enabler_Engine' ) && ! empty( $result['contents'] ) ) {
			$result['contents'] = CDN_Enabler_Engine::rewriter( $result['contents'] );
		}

		return $result;
	}

	/**
	 * Function to add CDN URL to media preview symlink URL.
	 *
	 * @since BuddyBoss 2.6.10
	 *
	 * @param string $attachment_url Attachment URL.
	 *
	 * @return string
	 */
	public function bb_media_preview_symlink_add_cdn( $attachment_url ) {
		if ( class_exists( 'CDN_Enabler_Engine' ) && ! empty( $attachment_url ) ) {
			$attachment_url = CDN_Enabler_Engine::rewriter( $attachment_url );
		}

		return $attachment_url;
	}
}

BB_CDN_Helpers::instance();
