<?php

// Exit if accessed directly
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
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_CDN_Helpers constructor.
	 */
	public function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
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
		add_filter( 'bp_nouveau_object_template_result', array( $this, 'bb_template_ajax_content_add_cdn' ), 10, 1 );
		add_filter( 'bb_media_after_get_preview_image_url_symlink', array( $this, 'bb_media_preview_symlink_add_cdn' ), 10, 1 );
	}

	/**
	 * Function to add CDN URL to ajax content.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
