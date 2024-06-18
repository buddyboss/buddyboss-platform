<?php
/**
 * Helper class for the third party plugins The Event Calendar.
 *
 * @package BuddyBoss
 *
 * @since BuddyBoss 2.3.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_The_Event_Calendar_Helpers Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 */
class BB_The_Event_Calendar_Helpers {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_The_Event_Calendar_Helpers constructor.
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
		add_filter( 'parse_query', array( $this, 'bb_core_tribe_events_parse_query' ) );
		add_filter( 'tribe_rewrite_parse_query_vars', array( $this, 'bb_core_tribe_events_set_query_vars' ) );
	}

	/**
	 * Function to suppress "The Event Calendar" plugin's parse_query filter.
	 *
	 * @since BuddyBoss 2.0.3
	 *
	 * @param array $query default query variable.
	 *
	 * @return array|mixed
	 */
	public function bb_core_tribe_events_parse_query( $query ) {

		if ( true === is_search() ||
			(
				true === (bool) defined( 'DOING_AJAX' ) &&
				true === (bool) DOING_AJAX &&
				isset( $_REQUEST['action'] ) &&
				'bp_search_ajax' === $_REQUEST['action']
			)
		) {
			$query->set( 'tribe_suppress_query_filters', true );
		}

		return $query;
	}

	/**
	 * Function to pass query vars when enabled "Use updated calendar designs"
	 * and enabled private mode from the platform.
	 *
	 * @since BuddyBoss 2.3.1
	 *
	 * @param array $query_vars The parsed query vars array.
	 *
	 * @return array
	 */
	public function bb_core_tribe_events_set_query_vars( $query_vars ) {

		// Check the user logged in or not.
		if ( ! is_user_logged_in() ) {
			$enable_private_network = bp_enable_private_network();

			// Check if enabled private site or not from the platform.
			if ( ! $enable_private_network ) {

				if ( apply_filters( 'bp_private_network_pre_check', false ) ) {
					return $query_vars;
				}

				// Check if enabled "Use updated calendar designs" option from the event calendar plugin.
				if ( ! function_exists( 'tribe_events_views_v2_is_enabled' ) || ! tribe_events_views_v2_is_enabled() ) {
					return $query_vars;
				}

				$query_vars['tribe_redirected'] = true;
			}
		}

		return $query_vars;
	}

}

BB_The_Event_Calendar_Helpers::instance();
