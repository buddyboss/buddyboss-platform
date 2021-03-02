<?php
/**
 * BuddyBoss Performance Friends Integration.
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
 * Friends Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Friends extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-friends' );

		$purge_events = array(
			'deleted_user', // New user deleted on site.
			'make_spam_user', // When user mark as spam user.
			'make_ham_user', // When user mark as ham user.
			'add_user_to_blog', // when user added in subsite.
			'remove_user_from_blog', // when user removed from subsite.
			'friends_friendship_requested', // When friendship requested.
			'friends_friendship_deleted', // When friendship request deleted.
			'friends_friendship_post_delete', // When friendship deleted.
			'friends_friendship_withdrawn', // When friendship withdrawn.

			// Added moderation support.
			'bp_suspend_user_suspended',       // Any User Suspended.
			'bp_suspend_user_unsuspended',     // Any User Unsuspended.
		);

		$this->purge_event( 'bp-friends', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'deleted_user'                   => 1, // New user deleted on site.
			'make_spam_user'                 => 1, // When user mark as spam user.
			'make_ham_user'                  => 1, // When user mark as ham user.
			'add_user_to_blog'               => 1, // when user added in subsite.
			'remove_user_from_blog'          => 1, // when user removed from subsite.
			'friends_friendship_requested'   => 3, // When friendship requested.
			'friends_friendship_accepted'    => 3, // When friendship request accepted.
			'friends_friendship_deleted'     => 3, // When friendship request delete.
			'friends_friendship_rejected'    => 2, // When friendship request rejected.
			'friends_friendship_post_delete' => 2, // When friendship deleted.
			'friends_friendship_withdrawn'   => 2, // When friendship withdrawn.

			// Added moderation support.
			'bp_suspend_user_suspended'      => 1, // Any User Suspended.
			'bp_suspend_user_unsuspended'    => 1, // Any User Unsuspended.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_member_connections', 'buddyboss-app' );
		$cache_bb_friends    = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_friends ) {

			$this->cache_endpoint(
				'buddyboss/v1/friends',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/friends/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/******************************** Friends Events ********************************/
	/**
	 * New user deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$friendship_ids = $this->get_friendship_ids_by_userid( $user_id );
		if ( ! empty( $friendship_ids ) ) {
			foreach ( $friendship_ids as $friendship_id ) {
				Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
			}
		}
	}

	/**
	 * When user mark as spam user
	 *
	 * @param int $user_id User ID.
	 */
	public function event_make_spam_user( $user_id ) {
		$friendship_ids = $this->get_friendship_ids_by_userid( $user_id );
		if ( ! empty( $friendship_ids ) ) {
			foreach ( $friendship_ids as $friendship_id ) {
				Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
			}
		}
	}

	/**
	 * When user mark as ham user
	 *
	 * @param int $user_id User ID.
	 */
	public function event_make_ham_user( $user_id ) {
		$friendship_ids = $this->get_friendship_ids_by_userid( $user_id );
		if ( ! empty( $friendship_ids ) ) {
			foreach ( $friendship_ids as $friendship_id ) {
				Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
			}
		}
	}

	/**
	 * When user added in subsite.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_add_user_to_blog( $user_id ) {
		$friendship_ids = $this->get_friendship_ids_by_userid( $user_id );
		if ( ! empty( $friendship_ids ) ) {
			foreach ( $friendship_ids as $friendship_id ) {
				Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
			}
		}
	}

	/**
	 * When user removed from subsite.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_remove_user_from_blog( $user_id ) {
		$friendship_ids = $this->get_friendship_ids_by_userid( $user_id );
		if ( ! empty( $friendship_ids ) ) {
			foreach ( $friendship_ids as $friendship_id ) {
				Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
			}
		}
	}

	/**
	 * When friendship requested
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 */
	public function event_friends_friendship_requested( $friendship_id, $initiator_user_id, $friend_user_id ) {
		Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
	}

	/**
	 * When friendship request accepted
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 */
	public function event_friends_friendship_accepted( $friendship_id, $initiator_user_id, $friend_user_id ) {
		Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
	}

	/**
	 * When friendship request accepted
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 */
	public function event_friends_friendship_deleted( $friendship_id, $initiator_user_id, $friend_user_id ) {
		Cache::instance()->purge_by_group( 'bp-friends_' . $initiator_user_id );
		Cache::instance()->purge_by_group( 'bp-friends_' . $friend_user_id );
	}


	/**
	 * When friendship request rejected
	 *
	 * @param int                   $friendship_id ID of the pending friendship.
	 * @param BP_Friends_Friendship $friendship    Friendship object. Passed by reference.
	 */
	public function event_friends_friendship_rejected( $friendship_id, $friendship ) {
		Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
	}

	/**
	 * When friendship deleted
	 *
	 * @param int $initiator_userid ID of the friendship initiator.
	 * @param int $friend_userid    ID of the friend user.
	 */
	public function event_friends_friendship_post_delete( $initiator_userid, $friend_userid ) {
		Cache::instance()->purge_by_group( 'bp-friends_' . $initiator_userid );
		Cache::instance()->purge_by_group( 'bp-friends_' . $friend_userid );
	}

	/**
	 * When friendship withdrawn
	 *
	 * @param int                   $friendship_id ID of the friendship.
	 * @param BP_Friends_Friendship $friendship    Friendship object. Passed by reference.
	 */
	public function event_friends_friendship_withdrawn( $friendship_id, $friendship ) {
		Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended User ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_suspended( $user_id ) {
		$friendship_ids = $this->get_friendship_ids_by_userid( $user_id );
		if ( ! empty( $friendship_ids ) ) {
			foreach ( $friendship_ids as $friendship_id ) {
				Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
			}
		}
	}

	/**
	 * Unsuspended User ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_unsuspended( $user_id ) {
		$friendship_ids = $this->get_friendship_ids_by_userid( $user_id );
		if ( ! empty( $friendship_ids ) ) {
			foreach ( $friendship_ids as $friendship_id ) {
				Cache::instance()->purge_by_group( 'bp-friends_' . $friendship_id );
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
	private function get_friendship_ids_by_userid( $user_id ) {
		global $wpdb;
		$bp = buddypress();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->friends->table_name} WHERE initiator_user_id = %d OR friend_user_id = %d ", $user_id, $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}
}
