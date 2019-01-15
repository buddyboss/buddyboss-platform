<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('Bp_Search_bbPress_Forums')):

	/**
	 *
	 * BuddyPress Global Search  - search bbpress forums
	 * **************************************
	 *
	 *
	 */
	class Bp_Search_bbPress_Forums extends Bp_Search_bbPress {
		public $type = 'forum';

		/**
		 * Insures that only one instance of Class exists in memory at any
		 * one time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return object Bp_Search_Forums
		 */
		public static function instance() {
			// Store the instance locally to avoid private static replication
			static $instance = null;

			// Only run these methods if they haven't been run previously
			if (null === $instance) {
				$instance = new Bp_Search_bbPress_Forums();
			}

			// Always return the instance
			return $instance;
		}

		/**
		 * A dummy constructor to prevent this class from being loaded more than once.
		 *
		 * @since 1.0.0
		 */
		private function __construct() { /* Do nothing here */
		}

	}

// End class Bp_Search_Posts

endif;
?>
