<?php
/**
 * BuddyBoss Performance Videos Integration.
 *
 * @package BuddyBoss\Performance
 */

namespace BuddyBoss\Performance\Integration;

use BuddyBoss\Performance\Cache;
use BuddyBoss\Performance\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Videos Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Videos extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-video' );

		$purge_events = array(
			'bp_video_add',                   // Any Video File add.
			'bp_video_before_delete',         // Any Video File delete.
			'bp_video_deleted_videos',        // Any Video File delete.
			'bp_video_delete',                // Any Video File delete.

			// Added moderation support.
			'bp_suspend_video_suspended',       // Hide video when member suspend.
			'bp_suspend_video_unsuspended',     // Unhide video when member suspend.
			'bp_moderation_after_save',         // Hide video when member blocked.
			'bb_moderation_after_delete'        // Unhide video when member unblocked.
		);

		$this->purge_event( 'bp-video', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bp_video_add'                   => 1, // Any Video File add.
			'bp_video_after_save'            => 1, // Any Video File updated.
			'bp_video_before_delete'         => 1, // Any Video File deleted.
			'updated_video_meta'             => 2, // Any Video meta update.

			// Video group information update support.
			'groups_update_group'            => 1, // When Group Details updated.
			'groups_group_after_save'        => 1, // When Group Details save.
			'groups_group_details_edited'    => 1, // When Group Details updated form Manage.

			// Add Author Embed Support.
			'profile_update'                 => 1, // User updated on site.
			'deleted_user'                   => 1, // User deleted on site.
			'xprofile_avatar_uploaded'       => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar' => 1, // User avatar photo deleted.

			// Added moderation support.
			'bp_suspend_video_suspended'     => 1, // Hide video when member suspend.
			'bp_suspend_video_unsuspended'   => 1, // Unhide video when member suspend.
			'bp_moderation_after_save'       => 1, // Hide video when member blocked.
			'bb_moderation_after_delete'     => 1, // Unhide video when member unblocked.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_media', 'buddyboss-app' );
		$cache_bb_media      = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_media ) {

			$this->cache_endpoint(
				'buddyboss/v1/video',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'     => array( 'id' ),
					'include_param' => array(
						'type' => 'type',
						'id'   => 'include',
					),
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/video/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id' => array( 'id' ),
				),
				false
			);

		}
	}

	/****************************** Video Events *****************************/
	/**
	 * Any Video Added.
	 *
	 * @param BP_Video $video Video object.
	 */
	public function event_bp_video_add( $video ) {
		if ( ! empty( $video->id ) ) {
			Cache::instance()->purge_by_group( 'bp-video_' . $video->id );
		}
	}

	/**
	 * Any Video Deleted.
	 *
	 * @param BP_Video $video Video object.
	 */
	public function event_bp_video_delete( $video ) {
		if ( ! empty( $video->id ) ) {
			Cache::instance()->purge_by_group( 'bp-video_' . $video->id );
		}
	}

	/**
	 * Any Video Saved.
	 *
	 * @param BP_Video $video Current instance of video item being saved. Passed by reference.
	 */
	public function event_bp_video_after_save( $video ) {
		if ( ! empty( $video->id ) ) {
			Cache::instance()->purge_by_group( 'bp-video_' . $video->id );
		}
	}

	/**
	 * Any Video Delete.
	 *
	 * @param array $videos Array of video.
	 */
	public function event_bp_video_before_delete( $videos ) {
		if ( ! empty( $videos ) ) {
			foreach ( $videos as $video ) {
				if ( ! empty( $video->id ) ) {
					Cache::instance()->purge_by_group( 'bp-video_' . $video->id );
				}
			}
		}
	}

	/**
	 * Any Video Delete.
	 *
	 * @param array $videos_ids Array of video ids.
	 */
	public function event_bp_video_deleted_videos( $videos_ids ) {
		if ( ! empty( $videos_ids ) ) {
			foreach ( $videos_ids as $videos_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $videos_id );
			}
		}
	}

	/**
	 * Any Video meta update
	 *
	 * @param int $meta_id  Video Meta id.
	 * @param int $video_id Video id.
	 */
	public function event_updated_video_meta( $meta_id, $video_id ) {
		Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
	}


	/****************************** Group Embed Support *****************************/
	/**
	 * When Group Details updated.
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_update_group( $group_id ) {
		$video_ids = $this->get_video_ids_by_group_id( $group_id );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
			}
		}

	}

	/**
	 * Fires after the current group item has been saved.
	 *
	 * @param BP_Groups_Group $group Current instance of the group item that was saved. Passed by reference.
	 */
	public function event_groups_group_after_save( $group ) {
		if ( ! empty( $group->id ) ) {
			$video_ids = $this->get_video_ids_by_group_id( $group->id );
			if ( ! empty( $video_ids ) ) {
				foreach ( $video_ids as $video_id ) {
					Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
				}
			}

		}
	}

	/**
	 * When Group Details updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_details_edited( $group_id ) {
		$video_ids = $this->get_video_ids_by_group_id( $group_id );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
			}
		}
	}

	/****************************** Author Embed Support *****************************/
	/**
	 * User updated on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_profile_update( $user_id ) {
		$video_ids = $this->get_video_ids_by_user_id( $user_id );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
			}
		}

	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$video_ids = $this->get_video_ids_by_user_id( $user_id );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$video_ids = $this->get_video_ids_by_user_id( $user_id );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
			}
		}
	}

	/**
	 * User avatar photo deleted
	 *
	 * @param array $args Arguments array.
	 */
	public function event_bp_core_delete_existing_avatar( $args ) {
		$user_id = ! empty( $args['item_id'] ) ? absint( $args['item_id'] ) : 0;
		if ( ! empty( $user_id ) ) {
			if ( isset( $args['object'] ) && 'user' === $args['object'] ) {
				$video_ids = $this->get_video_ids_by_user_id( $user_id );
				if ( ! empty( $video_ids ) ) {
					foreach ( $video_ids as $video_id ) {
						Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
					}
				}
			}
		}
	}

	/*********************************** Functions ***********************************/
	/**
	 * Get Video ids from user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_video_ids_by_user_id( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->video->table_name} WHERE user_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get Video Ids .
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array
	 */
	private function get_video_ids_by_group_id( $group_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->video->table_name} WHERE group_id = %d", $group_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Update cache for video when member suspend.
	 *
	 * @param int $video_id Video ID.
	 */
	public function event_bp_suspend_video_suspended( $video_id ) {
		Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
	}

	/**
	 * Update cache for video when member unsuspend.
	 *
	 * @param int $video_id Video ID.
	 */
	public function event_bp_suspend_video_unsuspended( $video_id ) {
		Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
	}

	/**
	 * Update cache for video when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$video_ids = $this->get_video_ids_by_user_id( $bp_moderation->item_id );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
			}
		}
	}

	/**
	 * Update cache for video when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$video_ids = $this->get_video_ids_by_user_id( $bp_moderation->item_id );
		if ( ! empty( $video_ids ) ) {
			foreach ( $video_ids as $video_id ) {
				Cache::instance()->purge_by_group( 'bp-video_' . $video_id );
			}
		}
	}

}
