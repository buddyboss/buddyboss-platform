<?php
/**
 * BuddyBoss Performance Activity Integration.
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
 * Activity Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Activity extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-activity' );

		$purge_events = array(
			'bp_activity_add',              // Any Activity add.
			'bp_activity_after_save',       // Any activity privacy update.
			'bp_activity_delete',           // Any Activity deleted.
			'bp_activity_delete_comment',   // Any Activity comment deleted.

			// Added moderation support.
			'bp_suspend_activity_suspended',           // Any Activity Suspended.
			'bp_suspend_activity_comment_suspended',   // Any Activity Comment Suspended.
			'bp_suspend_activity_unsuspended',         // Any Activity Unsuspended.
			'bp_suspend_activity_comment_unsuspended', // Any Activity Comment Unsuspended.

			'bp_moderation_after_save',     // Hide activity when member blocked.
			'bb_moderation_after_delete'    // Unhide activity when member unblocked.
		);

		$this->purge_event( 'bp-activity', $purge_events );
		$this->purge_event( 'bbapp-deeplinking', $purge_events );

		/**
		 * Support for single items purge
		 *
		 * `bp_groups_posted_update`, `bp_activity_posted_update`, `bp_activity_comment_posted`, `bp_activity_comment_posted_notification_skipped` will manage with `bp_activity_add`
		 * `bp_groups_posted_update` : group activity update added
		 * `bp_activity_posted_update`: activity update added
		 * `bp_activity_comment_posted`: activity comment added
		 * `bp_activity_comment_posted_notification_skipped`: activity comment added without Notification
		 */
		$purge_single_events = array(
			'bp_activity_add'                         => 1, // Any Activity add.
			'bp_activity_delete'                      => 1, // Any Activity deleted.
			'bp_activity_delete_comment'              => 1, // Any Activity comment deleted.
			'updated_activity_meta'                   => 2, // Any Activity meta update.
			'bp_activity_add_user_favorite'           => 1, // if activity added in user favorite list.
			'bp_activity_remove_user_favorite'        => 1, // if activity remove from user favorite list.

			// Added Moderation Support.
			'bp_suspend_activity_suspended'           => 1, // Any Activity Suspended.
			'bp_suspend_activity_comment_suspended'   => 1, // Any Activity Comment Suspended.
			'bp_suspend_activity_unsuspended'         => 1, // Any Activity Unsuspended.
			'bp_suspend_activity_comment_unsuspended' => 1, // Any Activity Comment Unsuspended.

			'bp_moderation_after_save'                => 1, // Hide activity when member blocked.
			'bb_moderation_after_delete'              => 1, // Unhide activity when member unblocked.

			// Add Author Embed Support.
			'profile_update'                          => 1, // User updated on site.
			'deleted_user'                            => 1, // User deleted on site.
			'xprofile_avatar_uploaded'                => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'          => 1, // User avatar photo deleted.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_activity_feeds', 'buddyboss-app' );
		$cache_bb_activity   = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_activity ) {

			$this->cache_endpoint(
				'buddyboss/v1/activity',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/activity/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/******************************** Activity Events ********************************/
	/**
	 * Any Activity add
	 *
	 * @param array $r Arguments array.
	 */
	public function event_bp_activity_add( $r ) {
		if ( ! empty( $r['id'] ) ) {
			$this->purge_item_cache_by_item_id( $r['id'] );
		}
		if ( 'activity_comment' === $r['type'] && ! empty( $r['item_id'] ) ) {
			$this->purge_item_cache_by_item_id( $r['item_id'] );
		}
	}

	/**
	 * Any Activity deleted
	 *
	 * @param array $args Arguments array.
	 */
	public function event_bp_activity_delete( $args ) {
		if ( ! empty( $r['id'] ) ) {
			$this->purge_item_cache_by_item_id( $args['id'] );
		}
	}

	/**
	 * Any Activity comment deleted
	 *
	 * @param int $activity_id Activity id.
	 */
	public function event_bp_activity_delete_comment( $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/**
	 * Any Activity meta update
	 *
	 * @param int $meta_id     Activity Meta id.
	 * @param int $activity_id Activity id.
	 */
	public function event_updated_activity_meta( $meta_id, $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/**
	 * Activity added in user favorite list
	 *
	 * @param int $activity_id Activity id.
	 */
	public function event_bp_activity_add_user_favorite( $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/**
	 * Activity remove from user favorite list
	 *
	 * @param int $activity_id Activity id.
	 */
	public function event_bp_activity_remove_user_favorite( $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Activity ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_suspended( $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/**
	 * Suspended Activity Comment ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_comment_suspended( $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/**
	 * Unsuspended Activity ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_unsuspended( $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/**
	 * Unsuspended Activity Comment ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_comment_unsuspended( $activity_id ) {
		$this->purge_item_cache_by_item_id( $activity_id );
	}

	/**
	 * Update cache for activity when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$activity_ids = $this->get_activity_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				$this->purge_item_cache_by_item_id( $activity_id );
			}
		}
	}

	/**
	 * Update cache for activity when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$activity_ids = $this->get_activity_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				$this->purge_item_cache_by_item_id( $activity_id );
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
		$activity_ids = $this->get_activity_ids_by_userid( $user_id );
		if ( ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				$this->purge_item_cache_by_item_id( $activity_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$activity_ids = $this->get_activity_ids_by_userid( $user_id );
		if ( ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				$this->purge_item_cache_by_item_id( $activity_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$activity_ids = $this->get_activity_ids_by_userid( $user_id );
		if ( ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				$this->purge_item_cache_by_item_id( $activity_id );
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
				$activity_ids = $this->get_activity_ids_by_userid( $user_id );
				if ( ! empty( $activity_ids ) ) {
					foreach ( $activity_ids as $activity_id ) {
						$this->purge_item_cache_by_item_id( $activity_id );
					}
				}
			}
		}
	}

	/*********************************** Functions ***********************************/
	/**
	 * Get Activities ids from user name.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_activity_ids_by_userid( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->activity->table_name} WHERE user_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Purge item cache by item id.
	 *
	 * @param $activity_id
	 */
	private function purge_item_cache_by_item_id( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( bp_activity_get_permalink( $activity_id ) ) );
	}
}
