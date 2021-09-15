<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'BB_SEOPRESS_Plugin_Compatibility' ) ) {
	return;
}

/**
 * BB_SEOPRESS_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss 1.7.4
 */
class BB_SEOPRESS_Plugin_Compatibility {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since BuddyBoss 1.7.4
	 */
	private static $instance = null;

	/**
	 * BB_SEOPRESS_Plugin_Compatibility constructor.
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

		add_action('wp', 'bb_remove_seopress_redirections_hook', 0);
		//add_action('plugins_loaded', 'bb_seopress_init', 999);
	}

	public function bb_seopress_init() {
		add_action('wp', 'bb_remove_seopress_redirections_hook', 0);
	}
	public function bb_remove_seopress_redirections_hook() {

		error_log( 'dfdf' );
		remove_action('template_redirect', 'seopress_redirections_hook', 1);
//		if ( function_exists( 'seopress_redirections_term_enabled' ) && 'yes' === seopress_redirections_term_enabled() && ( ! empty( get_query_var( 'bb-media-preview' ) ) || ! empty( get_query_var( 'bb-document-preview' ) ) || ! empty( get_query_var( 'bb-document-player' ) ) || ! empty( get_query_var( 'bb-video-thumb-preview' ) ) || ! empty( get_query_var( 'bb-video-preview' ) ) ) ) {
//			remove_action('template_redirect', 'seopress_redirections_hook', 1 );
//		}

	}


}

BB_SEOPRESS_Plugin_Compatibility::instance();
