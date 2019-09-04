<?php
/**
 * @package WordPress
 * @subpackage BuddyPress for LearnDash
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BuddyPress_LearnDash_Admin' ) ):

	/**
	 *
	 * BuddyPress_LearnDash_Admin
	 * ********************
	 *
	 *
	 */
	class BuddyPress_LearnDash_Admin {
		/**
		 * Empty constructor function to ensure a single instance
		 */
		public function __construct() {
			// ... leave empty, see Singleton below
		}

		/* Singleton
		 * ===================================================================
		 */

		/**
		 * Admin singleton
		 *
		 * @since BuddyPress for LearnDash (1.0.0)
		 *
		 * @param  array $options [description]
		 *
		 * @uses BuddyPress_LearnDash_Admin::setup() Init admin class
		 *
		 * @return object Admin class
		 */
		public static function instance() {
			static $instance = null;

			if ( null === $instance ) {
				$instance = new BuddyPress_LearnDash_Admin;
			}

			return $instance;
		}

		public function convert_users_to_bp_member_type( $role, $bp_member_tpe ) {
			$all_users = get_users( 'role=' . $role );
			foreach ( $all_users as $user ) {
				$member_type = bp_get_member_type( $user->ID );
				if ( $member_type != $bp_member_tpe ) {
					bp_set_member_type( $user->ID, $bp_member_tpe );
				}
			}
		}

		public function remove_convertion_users_to_bp_member_type( $role, $bp_member_tpe ) {
			$subscribers = get_users( 'role=' . $role );
			foreach ( $subscribers as $user ) {
				$member_type = bp_get_member_type( $user->ID );
				if ( $member_type == $bp_member_tpe ) {
					bp_set_member_type( $user->ID, '' );
				}
			}
		}

	}

// End class BuddyPress_LearnDash_Admin
endif;