<?php
/**
 * BuddyBoss Activity Schedule Classes.
 *
 * @package BuddyBoss\Activity
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Activity_Schedule' ) ) {
	/**
	 * BuddyBoss Activity Schedule.
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
			add_action( 'bp_activity_after_save', array( $this, 'bb_register_schedule_activity' ), 999, 1 );
			add_action( 'bb_activity_publish', array( $this, 'bb_check_and_publish_scheduled_activity' ) );
			
			// Check if the activation transient exists.
			if ( get_transient( '_bp_activation_redirect' ) ) {
				bb_create_activity_schedule_cron_event();
			}
		}

		/**
		 * Schedule activity publish event.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array|object $activity The activity object or array.
		 */
		public function bb_register_schedule_activity( $activity ) {
			if ( empty( $activity->id ) || in_array( $activity->privacy, array( 'media', 'video', 'document') ) || bb_get_activity_scheduled_status() !== $activity->status ) {
				return;
			}

			bb_create_activity_schedule_cron_event();
		}

		/**
		 * Get all the scheduled activities and publish it.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void
		 */
		public function bb_check_and_publish_scheduled_activity() {
			global $wpdb;
		
			$bp_prefix        = bp_core_get_table_prefix();
			$current_time     = bp_core_current_time();
			$scheduled_status = bb_get_activity_scheduled_status();
			$published_status = bb_get_activity_published_status();

			// Get all activities that are scheduled and past due.
			$activities = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id FROM {$bp_prefix}bp_activity
					 WHERE type='activity_update' AND privacy NOT IN ( 'media', 'video', 'document' ) AND status = %s AND date_recorded <= %s",
					$scheduled_status, $current_time
				)
			);

			foreach ( $activities as $scheduled_activity ) {
				$activity = new BP_Activity_Activity( $scheduled_activity->id );

				if ( $activity ) {

					// Publish the activity.
					$activity->status = $published_status;
					$activity->save();

					// Remove edited time from scheduled activities.
					bp_activity_delete_meta( $activity->id, '_is_edited' );

					$metas = bb_activity_get_metadata( $activity->id );

					// Publish the media.
					if ( ! empty( $metas['bp_media_ids'][0] ) ) {
						$media_ids = explode( ',', $metas['bp_media_ids'][0] );
						$this->bb_publish_schedule_activity_medias_and_documents( $media_ids );
					}

					// Publish the video.
					if ( ! empty( $metas['bp_video_ids'][0] ) ) {
						$video_ids = explode( ',', $metas['bp_video_ids'][0] );
						$this->bb_publish_schedule_activity_medias_and_documents( $video_ids, 'video' );
					}

					// Publish the document.
					if ( ! empty( $metas['bp_document_ids'][0] ) ) {
						$document_ids = explode( ',', $metas['bp_document_ids'][0] );
						$this->bb_publish_schedule_activity_medias_and_documents( $document_ids, 'document' );
					}

					// Send mentioned notifications.
					add_filter( 'bp_activity_at_name_do_notifications', '__return_true' );

					if ( ! empty( $activity->item_id ) ) {
						bb_group_activity_at_name_send_emails( $activity->content, $activity->user_id, $activity->item_id, $activity->id );
						bb_subscription_send_subscribe_group_notifications( $activity->content, $activity->user_id, $activity->item_id, $activity->id );
					} else {
						bb_activity_at_name_send_emails( $activity->content, $activity->user_id, $activity->id );
					}

					bb_activity_send_email_to_following_post( $activity->content, $activity->user_id, $activity->id );
				}
			}
		}

		/**
		 * Publish scheduled activity media/video/document and their individual activities.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $ids  Ids of media/video/document.
		 * @param string $type Media type : 'media', 'video', 'document'.
		 */
		public function bb_publish_schedule_activity_medias_and_documents( $ids, $type = 'media' ) {
			global $wpdb;

			if ( ! empty( $ids ) ) {
				$bp_prefix  = bp_core_get_table_prefix();
				$table_name = "{$bp_prefix}bp_media";
				if ( 'document' === $type ) {
					$table_name = "{$bp_prefix}bp_document";
				}

				// Check table exists.
				$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
				if ( $table_exists ) {
					foreach ( $ids as $id ) {
						$wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET status = 'published' WHERE id = %d", $id ) );

						// Also update the individual medias/videos/document activity.
						if ( count( $ids ) > 1 ) {
							$activity_id      = $wpdb->get_var( $wpdb->prepare( "SELECT activity_id FROM {$table_name} WHERE id = %d", $id ) );
							$activity         = new BP_Activity_Activity( $activity_id );
							$activity->status = bb_get_activity_published_status();
							$activity->save();
						}
					}
				}
			}
		}
	}
}
