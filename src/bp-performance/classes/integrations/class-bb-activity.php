<?php
/**
 * BuddyBoss Performance Activity Integration.
 *
 * @package BuddyBoss\Performance
 */

namespace BuddyBoss\Performance\Integration;

use BuddyBoss\Performance\Helper;
use BuddyBoss\Performance\Cache;

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

		$event_groups = array( 'buddypress', 'buddypress-activity' );

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
		);

		/**
		 * Add Custom events to purge activities endpoint cache
		 */
		$purge_events = apply_filters( 'bbplatform_cache_bp_activity', $purge_events );
		$this->purge_event( 'bp-activity', $purge_events );

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

			// Add Author Embed Support.
			'profile_update'                          => 1, // User updated on site.
			'deleted_user'                            => 1, // User deleted on site.
			'xprofile_avatar_uploaded'                => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'          => 1, // User avatar photo deleted.
		);

		/**
		 * Add Custom events to purge single activity endpoint cache
		 */
		$purge_single_events = apply_filters( 'bbplatform_cache_bp_activity_single', $purge_single_events );
		$this->purge_single_events( 'bbplatform_cache_purge_bp-activity_single', $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_activity_feeds', 'buddyboss-app' );
		$cache_bb_activity   = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_activity ) {

			$this->cache_endpoint(
				'buddyboss/v1/activity',
				Cache::instance()->month_in_seconds * 60,
				$purge_events,
				$event_groups,
				array(
					'unique_id'         => 'id',
					'purge_deep_events' => array_keys( $purge_single_events ),
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/activity/<id>',
				Cache::instance()->month_in_seconds * 60,
				array_keys( $purge_single_events ),
				$event_groups,
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
			Cache::instance()->purge_by_group( 'bp-activity_' . $r['id'] );
		}
		if ( 'activity_comment' === $r['type'] && ! empty( $r['item_id'] ) ) {
			Cache::instance()->purge_by_group( 'bp-activity_' . $r['item_id'] );
		}
	}

	/**
	 * Any Activity deleted
	 *
	 * @param array $args Arguments array.
	 */
	public function event_bp_activity_delete( $args ) {
		if ( ! empty( $r['id'] ) ) {
			Cache::instance()->purge_by_group( 'bp-activity_' . $args['id'] );
		}
	}

	/**
	 * Any Activity comment deleted
	 *
	 * @param int $activity_id Activity id.
	 */
	public function event_bp_activity_delete_comment( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
	}

	/**
	 * Any Activity meta update
	 *
	 * @param int $meta_id     Activity Meta id.
	 * @param int $activity_id Activity id.
	 */
	public function event_updated_activity_meta( $meta_id, $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
	}

	/**
	 * Activity added in user favorite list
	 *
	 * @param int $activity_id Activity id.
	 */
	public function event_bp_activity_add_user_favorite( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
	}

	/**
	 * Activity remove from user favorite list
	 *
	 * @param int $activity_id Activity id.
	 */
	public function event_bp_activity_remove_user_favorite( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Activity ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_suspended( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
	}

	/**
	 * Suspended Activity Comment ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_comment_suspended( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
	}

	/**
	 * Unsuspended Activity ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_unsuspended( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
	}

	/**
	 * Unsuspended Activity Comment ID.
	 *
	 * @param int $activity_id Activity ID.
	 */
	public function event_bp_suspend_activity_comment_unsuspended( $activity_id ) {
		Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
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
				Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
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
				Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
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
				Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
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
						Cache::instance()->purge_by_group( 'bp-activity_' . $activity_id );
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
}
