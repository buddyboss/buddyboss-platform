<?php
/**
 * BuddyBoss Activity Schedule Classes
 *
 * @package BuddyBoss\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Activity_Schedule' ) ) {
	/**
	 * BuddyBoss Activity Schedule.
	 *
	 * Handles schedule posts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_Activity_Schedule {
		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @access private
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return Controller|BB_Reaction|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			add_action( 'bp_activity_after_save', array( $this, 'register_schedule_activity' ), 10, 1 );
			add_action( 'bb_activity_publish', array( $this, 'bb_check_and_publish_schedule_activity' ), 10, 1 );
		}

		/**
		 * Schedule the activity.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array|object $activity The activity object or array.
		 *
		 * @return bool
		 */
		public function register_schedule_activity( $activity ) {
			if ( empty( $activity->id ) || bb_get_activity_scheduled_status() !== $activity->status ) {
				return false;
			}

			if ( mysql2date( 'U', $activity->date_recorded, false ) > mysql2date( 'U', gmdate( 'Y-m-d H:i:59' ), false ) ) {
				wp_clear_scheduled_hook( 'bb_activity_publish', array( $activity->id ) );
				wp_schedule_single_event( strtotime( get_gmt_from_date( $activity->date_recorded ) . ' GMT' ), 'bb_activity_publish', array( $activity->id ) );

				return true;
			}

			return false;
		}

		/**
		 * Check the scheduled activity and publish it.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $activity_id Activity ID.
		 *
		 * @return void
		 */
		public function bb_check_and_publish_schedule_activity( $activity_id ) {
			$activity = new BP_Activity_Activity( $activity_id );

			if ( empty( $activity->id ) || bb_get_activity_scheduled_status() !== $activity->status ) {
				return;
			}

			$time = strtotime( $activity->date_recorded . ' GMT' );

			// Reschedule an event.
			if ( $time > time() ) {
				wp_clear_scheduled_hook( 'bb_activity_publish', array( $activity_id ) ); // Clear anything else in the system.
				wp_schedule_single_event( $time, 'bb_activity_publish', array( $activity_id ) );

				return;
			}

			// Publish the activity.
			$activity->status = bb_get_activity_published_status();
			$activity->save();
		}

	}
}
