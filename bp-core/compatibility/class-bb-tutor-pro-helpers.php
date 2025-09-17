<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_Tutor_Pro_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss 2.1.7
 */
class BB_Tutor_Pro_Plugin_Compatibility {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since BuddyBoss 2.1.7
	 */
	private static $instance = null;

	/**
	 * BB_Tutor_Pro_Plugin_Compatibility constructor.
	 */
	public function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 2.1.7
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
	 * @since BuddyBoss 2.1.7
	 *
	 * @return void
	 */
	public function compatibility_init() {
		add_action( 'bp_init', array( $this, 'check_current_action' ) );
	}

	/**
	 * Check if it is group's zoom tab.
	 *
	 * @since BuddyBoss 2.1.7
	 *
	 * @return void
	 */
	public function check_current_action() {
		if ( 'groups' === bp_current_component() && 'zoom' === bp_current_action() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'deregister_timepicker' ) );
		}
	}

	/**
	 * Deregister timepicker script and style for group's zoom tab
	 *
	 * @since BuddyBoss 2.1.7
	 *
	 * @return void
	 */
	public function deregister_timepicker() {
		wp_deregister_script( 'tutor_zoom_timepicker_js' );
		wp_dequeue_script( 'tutor_zoom_timepicker_js' );
		wp_deregister_style( 'tutor_zoom_timepicker_css' );
		wp_dequeue_style( 'tutor_zoom_timepicker_css' );
	}

}

BB_Tutor_Pro_Plugin_Compatibility::instance();
