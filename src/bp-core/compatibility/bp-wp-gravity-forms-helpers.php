<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_GFForms_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_GFForms_Plugin_Compatibility {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private static $instance = null;

	/**
	 * BB_GFForms_Plugin_Compatibility constructor.
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

		$this->bb_fix_gfforms_gfur_email_activation_conflict();

	}

	/**
	 * Function to fix the gravity form user registration email activation conflict
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_fix_gfforms_gfur_email_activation_conflict() {

		if ( class_exists( 'GF_User_Registration' ) && 1 === (int) gf_user_registration()->get_plugin_setting( 'custom_registration_page_enable' ) ) {
			remove_filter( 'wpmu_signup_user_notification_email', 'bp_email_wpmu_signup_user_notification_email', 999, 5 );
		}
		
	}

}

BB_GFForms_Plugin_Compatibility::instance();
