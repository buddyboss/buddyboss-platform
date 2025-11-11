<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyBoss Offload Media Helpers Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 *
 * @since BuddyBoss 2.15.0
 */
class BB_OM_Helpers {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 *
	 * @since BuddyBoss 2.15.0
	 */
	private static $instance = null;

	/**
	 * BB_OM_Helpers constructor.
	 *
	 * @since BuddyBoss 2.15.0
	 */
	public function __construct() {

		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 2.15.0
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
	 * @since BuddyBoss 2.15.0
	 */
	public function compatibility_init() {

		add_filter( 'bp_core_get_js_strings', array( $this, 'bb_offload_localize_scripts' ) );
	}

	/**
	 * Add extra parameter into localize scripts for offload media plugin.
	 *
	 * @since BuddyBoss 2.15.0
	 *
	 * @param array $params Localize scripts parameter.
	 *
	 * @return array
	 */
	public function bb_offload_localize_scripts( $params = array() ) {
		if ( bp_is_active( 'activity' ) && bp_is_active( 'media' ) ) {
			$params['is_om_active'] = true;
		}
		return $params;
	}
}

BB_OM_Helpers::instance();
