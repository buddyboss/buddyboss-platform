<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_Pmpro_Helpers Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 */
class BB_Pmpro_Helpers {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Pmpro_Helpers constructor.
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

		add_filter( 'the_content', array( $this, 'bb_remove_pmpro_shortcodes' ), 99 );
	}

	/**
	 * Function to remove Paid Membership Pro shortcode.
	 *
	 * @since BuddyBoss 2.3.2
	 *
	 * @param  string $content The page content.
	 *
	 * @return string
	 */
	public function bb_remove_pmpro_shortcodes( $content ) {
		if (
			defined( 'PMPRO_VERSION' ) &&
			bp_is_active( 'search' ) &&
			(
				isset( $_GET['bp_search'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				(
					isset( $_POST['action'] ) && // phpcs:ignore
					'bp_search_ajax' === sanitize_text_field( wp_unslash( $_POST['action'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
				)
			)
		) {
			global $pmpro_core_pages;

			if ( $pmpro_core_pages ) {
				foreach ( $pmpro_core_pages as $pmpro_page_name => $pmpro_page_id ) {
					if (
						! empty( $content ) &&
						stripos( $content, '[pmpro_' . $pmpro_page_name . ']' ) !== false
					) {
						$content = str_replace( '[pmpro_' . $pmpro_page_name . ']', '', $content );
						$content = str_replace( '[/pmpro_' . $pmpro_page_name . ']', '', $content );
					}
				}
			}
		}

		return $content;
	}
}

BB_Pmpro_Helpers::instance();
