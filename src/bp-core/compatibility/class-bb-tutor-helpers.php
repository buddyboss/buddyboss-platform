<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TUTOR\Input;

/**
 * BB_Tutor_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss 2.1.7
 */
class BB_Tutor_Plugin_Compatibility {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss2.6.80
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Tutor_Plugin_Compatibility constructor.
	 */
	public function __construct() {
		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 2.6.80
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
	 * @since BuddyBoss 2.6.80
	 *
	 * @return void
	 */
	public function compatibility_init() {
		add_action( 'login_form', array( $this, 'bb_remove_tutor_login_recaptcha' ) );
		add_filter( 'authenticate', array( $this, 'bb_remove_tutor_login_authentication' ) );
	}

	/**
	 * Remove BuddyBoss reCAPTCHA from the Tutor LMS login page.
	 *
	 * @since BuddyBoss 2.6.80
	 *
	 * @return void
	 */
	public function bb_remove_tutor_login_recaptcha() {
		$page_now = $GLOBALS['pagenow'];

		if ( 'tutor_login' === $page_now ) {
			remove_action( 'login_form', 'bb_recaptcha_login', 99 );
		}
	}

	/**
	 * Remove BuddyBoss reCAPTCHA authentication from the Tutor LMS login page.
	 * When enable 'Authentication → Enable Fraud Protection',
	 * then need to remove BuddyBoss authentication for tutor login page.
	 *
	 * @since BuddyBoss 2.6.80
	 *
	 * @param WP_User|WP_Error $user WP_User or WP_Error object if a previous
	 *                               callback failed authentication.
	 *
	 * @return WP_User|WP_Error|null WP_User object if the user is authenticated, WP_Error object on error, or null if
	 *                               not authenticated.
	 */
	public function bb_remove_tutor_login_authentication( $user ) {
		if ( Input::has( 'tutor_action' ) ) {
			remove_filter( 'authenticate', 'bb_recaptcha_validate_login', 99999, 1 );
		}

		return $user;
	}

}

BB_Tutor_Plugin_Compatibility::instance();
