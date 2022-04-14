<?php
/**
 * Added compatibility support for third party plugins TranslatePress.
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss [BBBVERSION]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'BB_TranslatePress_Plugin_Compatibility' ) ) {
	return;
}

/**
 * BB_TranslatePress_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_TranslatePress_Plugin_Compatibility {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private static $instance = null;

	/**
	 * BB_TranslatePress_Plugin_Compatibility constructor.
	 */
	public function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
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
		add_filter( 'bp_uri', array( $this, 'remove_langcode_from_url' ), PHP_INT_MAX );
	}

	/**
	 * Remove lang code from URL slug
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $path URI Path.
	 *
	 * @return string $path
	 */
	public function remove_langcode_from_url( $path ) {

		$tp_settings = get_option( 'trp_settings' );
		if ( $tp_settings && isset( $tp_settings['url-slugs'] ) && ! empty( $tp_settings['url-slugs'] ) ) {
			foreach ( $tp_settings['url-slugs'] as $lang_key => $lang_slug ) {
				$path = str_replace( $lang_slug . '/', '', $path );
			}
		}

		return $path;

	}

}

BB_TranslatePress_Plugin_Compatibility::instance();
