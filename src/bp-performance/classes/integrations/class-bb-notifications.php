<?php
/**
 * BuddyBoss Performance Notification Integration.
 *
 * @package BuddyBoss\Performance
 */

namespace BuddyBoss\Performance\Integration;

use BuddyBoss\Performance\Helper;
use BuddyBoss\Performance\Cache;
use BP_Notifications_Notification;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Notification Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Notifications extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-notifications' );

		$purge_events = array(
			'bp_notification_after_save', // When new notification created.
			'bp_notification_before_update', // When notification updated.
			'bp_notification_before_delete', // When notification deleted.

			// Added moderation support.
			'bp_suspend_user_suspended',    // Update notification when member suspended.
			'bp_suspend_user_unsuspended',  // Update notification when member unsuspended.
			'bp_moderation_after_save',     // Update notification when member blocked.
			'bb_moderation_after_delete'    // Update notification when member unblocked.
		);

		$this->purge_event( 'bp-notifications', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bp_notification_after_save'      => 1, // When new notification created.
			'bp_notification_before_update'   => 2, // When notification updated.
			'bp_notification_before_delete'   => 1, // When notification deleted.

			// Notification actions support.
			'friends_friendship_requested'    => 1, // When friendship requested.
			'friends_friendship_accepted'     => 1, // When friendship request accepted.
			'friends_friendship_rejected'     => 1, // When friendship request rejected.
			'friends_friendship_deleted'      => 1, // When friendship request deleted.
			'groups_member_after_save'        => 1, // When Group member ban, unban, promoted, demoted.
			'groups_membership_requested'     => 3, // When Group membership request.
			'groups_membership_accepted'      => 2, // When Group invitation accepted.
			'groups_membership_rejected'      => 2, // When Group invitation rejected.
			'groups_invite_user'              => 1, // When user invite in group.
			'bp_invitations_accepted_request' => 1, // When Group request accepted.
			'bp_invitations_accepted_invite'  => 1, // When Group invitation accepted.
			'bp_invitation_after_delete'      => 1, // When Group invitation deleted.

			// Add Author Embed Support.
			'profile_update'                  => 1, // User updated on site.
			'deleted_user'                    => 1, // User deleted on site.
			'xprofile_avatar_uploaded'        => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'  => 1, // User avatar photo deleted.

			// Added moderation support.
			'bp_suspend_user_suspended'       => 1, // Update notification when member suspended.
			'bp_suspend_user_unsuspended'     => 1, // Update notification when member unsuspended.
			'bp_moderation_after_save'        => 1, // Update notification when member blocked.
			'bb_moderation_after_delete'      => 1, // Update notification when member unblocked.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active    = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings               = Helper::instance()->get_app_settings( 'cache_bb_notifications', 'buddyboss-app' );
		$cache_bb_notifications = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_notifications ) {

			$this->cache_endpoint(
				'buddyboss/v1/notifications',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/notifications/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/**
	 * When new notification created
	 *
	 * @param BP_Notifications_Notification $n Current instance of the notification item being saved.
	 */
	public function event_bp_notification_after_save( $n ) {
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n->id );
	}

	/**
	 * When notification updated
	 *
	 * @param array $update_args See BP_Notifications_Notification::update().
	 * @param array $where_args  See BP_Notifications_Notification::update().
	 */
	public function event_bp_notification_before_update( $update_args, $where_args ) {
		if ( ! empty( $where_args['id'] ) ) {
			$n = bp_notifications_get_notification( $where_args['id'] );
			Cache::instance()->purge_by_group( 'bp-notifications_' . $n->id );
		}
	}

	/**
	 * When notification deleted
	 *
	 * @param array $args Associative array of columns/values, to determine
	 *                    which rows should be deleted. Of the format
	 *                    array( 'item_id' => 7, 'component_action' => 'members' ).
	 */
	public function event_bp_notification_before_delete( $args ) {

		// Pull up a list of items matching the args (those about te be deleted).
		$ns = BP_Notifications_Notification::get( $args );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n->id );
			}
		}
	}

	/**
	 * When friendship requested
	 *
	 * @param int $friendship_id ID of the friendship connection.
	 */
	public function event_friends_friendship_requested( $friendship_id ) {
		$n_id = $this->get_notification_id_by_friendship_id( $friendship_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When friendship request accepted
	 *
	 * @param int $friendship_id ID of the friendship connection.
	 */
	public function event_friends_friendship_accepted( $friendship_id ) {
		$n_id = $this->get_notification_id_by_friendship_id( $friendship_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When friendship request accepted
	 *
	 * @param int $friendship_id ID of the friendship connection.
	 */
	public function event_friends_friendship_deleted( $friendship_id ) {
		$n_id = $this->get_notification_id_by_friendship_id( $friendship_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When friendship request rejected
	 *
	 * @param int $friendship_id ID of the friendship connection.
	 */
	public function event_friends_friendship_rejected( $friendship_id ) {
		$n_id = $this->get_notification_id_by_friendship_id( $friendship_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When Group member ban, unban, promoted, demoted
	 *
	 * @param BP_Groups_Member $member Current instance of the group membership item has been saved.
	 */
	public function event_groups_member_after_save( $member ) {
		$group_id = $member->group_id;
		$n_id     = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When Group membership request
	 *
	 * @param int   $user_id  ID of the user requesting membership.
	 * @param array $admins   Array of group admins.
	 * @param int   $group_id ID of the group being requested to.
	 */
	public function event_groups_membership_requested( $user_id, $admins, $group_id ) {
		$n_id = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When Group invitation accepted
	 *
	 * @param int $user_id  ID of the user who accepted membership.
	 * @param int $group_id ID of the group that was accepted membership to.
	 */
	public function event_groups_membership_accepted( $user_id, $group_id ) {
		$n_id = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When Group invitation rejected
	 *
	 * @param int $user_id  ID of the user who accepted membership.
	 * @param int $group_id ID of the group that was accepted membership to.
	 */
	public function event_groups_membership_rejected( $user_id, $group_id ) {
		$n_id = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When user invite in group
	 *
	 * @param array $r Array of parsed arguments for the group invite.
	 */
	public function event_groups_invite_user( $r ) {
		$group_id = $r['group_id'];
		$n_id     = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When Group request accepted
	 *
	 * @param array $r Array of parsed arguments for the group invite.
	 */
	public function event_bp_invitations_accepted_request( $r ) {
		$group_id = $r['item_id'];
		$n_id     = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When Group invitation accepted
	 *
	 * @param array $r Array of parsed arguments for the group invite.
	 */
	public function event_bp_invitations_accepted_invite( $r ) {
		$group_id = $r['item_id'];
		$n_id     = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * When Group invitation deleted
	 *
	 * @param array $r Array of parsed arguments for the group invite.
	 */
	public function event_bp_invitation_after_delete( $r ) {
		$group_id = $r['item_id'];
		$n_id     = $this->get_notification_id_by_group_id( $group_id );
		Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
	}

	/**
	 * User updated on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_profile_update( $user_id ) {
		$ns = $this->get_notification_ids_by_userid( $user_id );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n_id ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$ns = $this->get_notification_ids_by_userid( $user_id );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n_id ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$ns = $this->get_notification_ids_by_userid( $user_id );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n_id ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
			}
		}
	}

	/**
	 * User avatar photo deleted
	 *
	 * @param array $args Array of arguments used for avatar deletion.
	 */
	public function event_bp_core_delete_existing_avatar( $args ) {
		$user_id = ! empty( $args['item_id'] ) ? absint( $args['item_id'] ) : 0;
		if ( ! empty( $user_id ) ) {
			if ( isset( $args['object'] ) && 'user' === $args['object'] ) {
				$ns = $this->get_notification_ids_by_userid( $user_id );
				if ( ! empty( $ns ) ) {
					foreach ( $ns as $n_id ) {
						Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
					}
				}
			}
		}
	}

	/**
	 * Update cache for notifications when member suspended.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_suspended( $user_id ) {
		$ns = $this->get_notification_ids_by_userid( $user_id );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n_id ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
			}
		}
	}

	/**
	 * Update cache for notifications when member unsuspended.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_unsuspended( $user_id ) {
		$ns = $this->get_notification_ids_by_userid( $user_id );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n_id ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
			}
		}
	}

	/**
	 * Update cache for notifications when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$ns = $this->get_notification_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n_id ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
			}
		}
	}

	/**
	 * Update cache for notifications when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$ns = $this->get_notification_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $ns ) ) {
			foreach ( $ns as $n_id ) {
				Cache::instance()->purge_by_group( 'bp-notifications_' . $n_id );
			}
		}
	}


	/**
	 * Get notification ids from user name.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_notification_ids_by_userid( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->notifications->table_name} WHERE user_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get notification id from friendship id.
	 *
	 * @param int $friendship_id ID of the pending friendship connection.
	 *
	 * @return integer
	 */
	private function get_notification_id_by_friendship_id( $friendship_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->notifications->table_name} WHERE component_action='friends' AND component_action like 'friendship_%' AND secondary_item_id = %d LIMIT 1", $friendship_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql );
	}

	/**
	 * Get notification id from group id.
	 *
	 * @param int $group_id ID of the group.
	 *
	 * @return integer
	 */
	private function get_notification_id_by_group_id( $group_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->notifications->table_name} WHERE component_action='groups' AND component_action = 'group_invite' AND item_id = %d LIMIT 1", $group_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql );
	}
}
