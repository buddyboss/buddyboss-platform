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
				wp_clear_scheduled_hook( 'bb_activity_publish', array( (int) $activity->id ) );
				wp_schedule_single_event( strtotime( $activity->date_recorded . 'GMT' ), 'bb_activity_publish', array( (int) $activity->id ) );
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

			$metas = bb_activity_get_metadata( $activity_id );

			// Published the media.
			if ( ! empty( $metas['bp_media_ids'][0] ) ) {
				$media_ids = explode( ",", $metas['bp_media_ids'][0] );
				foreach ( $media_ids as $media_id ) {
					$media = new BP_Media( $media_id );
					$media->status = bb_media_get_published_status();
					$media->save();

					// Also update the individual videos activity.
					if ( count( $media_ids ) > 1 ) {
						$media_activity         = new BP_Activity_Activity( $media->activity_id );
						$media_activity->status = bb_get_activity_published_status();
						$media_activity->save();
					}
				}
			}

			// Published the video.
			if ( ! empty( $metas['bp_video_ids'][0] ) ) {
				$video_ids = explode( ",", $metas['bp_video_ids'][0] );
				foreach ( $video_ids as $video_id ) {
					$video = new BP_Video( $video_id );
					$video->status = bb_video_get_published_status();
					$video->save();

					// Also update the individual videos activity.
					if ( count( $video_ids ) > 0 ) {
						$video_activity         = new BP_Activity_Activity( $video->activity_id );
						$video_activity->status = bb_get_activity_published_status();
						$video_activity->save();
					}
				}
			}

			// Published the document.
			if ( ! empty( $metas['bp_document_ids'][0] ) ) {
				$document_ids = explode( ",", $metas['bp_document_ids'][0] );
				foreach ( $document_ids as $document_id ) {
					$document = new BP_Document( $document_id );
					$document->status = bb_document_get_published_status();
					$document->save();

					// Also update the individual documents activity.
					if ( count( $document_ids ) > 0 ) {
						$document_activity         = new BP_Activity_Activity( $document->activity_id );
						$document_activity->status = bb_get_activity_published_status();
						$document_activity->save();
					}
				}
			}

			// Send mentioned notifications.
			add_filter( 'bp_activity_at_name_do_notifications', '__return_true' );

			if ( ! empty( $activity->item_id ) ) {
				bb_group_activity_at_name_send_emails( $activity->content, $activity->user_id, $activity->item_id, $activity_id );
				bb_subscription_send_subscribe_group_notifications( $activity->content, $activity->user_id, $activity->item_id, $activity_id );
			} else {
				bb_activity_at_name_send_emails( $activity->content, $activity->user_id, $activity_id );
			}

			bb_activity_send_email_to_following_post( $activity->content, $activity->user_id, $activity_id );
		}
	}
}
