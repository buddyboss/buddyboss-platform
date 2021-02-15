<?php
/**
 * BuddyBoss Performance Group Integration.
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
 * Group Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Groups extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-groups' );

		$event_groups = array( 'buddypress', 'buddypress-groups' );

		$purge_events = array(
			'bp_group_admin_edit_after',         // When Group change form admin.
			'groups_create_group_step_complete', // When Group created from Manage.

			// Added moderation support.
			'bp_suspend_groups_suspended',       // Any Group Suspended.
			'bp_suspend_groups_unsuspended',     // Any Group Unsuspended.
		);

		/**
		 * Add Custom events to purge group endpoint cache
		 */
		$purge_events = apply_filters( 'bbplatform_cache_bp_groups', $purge_events );
		$this->purge_event( 'bp-groups', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bp_group_admin_edit_after'             => 1,  // When Group change form admin.
			'groups_create_group_step_complete'     => 0,  // When Group created from Manage.
			'groups_group_details_edited'           => 1,  // When Group Details updated form Manage.
			'groups_group_settings_edited'          => 1,  // When Group setting updated form Manage.
			'groups_avatar_uploaded'                => 1,  // When Group avatar updated form Manage.
			'bp_core_delete_existing_avatar'        => 1,  // When Group avatar deleted.
			'groups_cover_image_uploaded'           => 1,  // When Group cover photo uploaded form Manage.
			'groups_cover_image_deleted'            => 1,  // When Group cover photo deleted form Manage.
			'bp_group_admin_after_edit_screen_save' => 1,  // When Group forums setting Manage.
			'groups_join_group'                     => 1,  // When user join in group.
			'groups_member_after_save'              => 1,  // When Group member ban, unban, promoted, demoted.
			'groups_member_after_remove'            => 1,  // When Group member removed.
			'groups_membership_requested'           => 3,  // When Group membership request.
			'groups_membership_accepted'            => 2,  // When Group invitation accepted.
			'groups_membership_rejected'            => 2,  // When Group invitation rejected.
			'groups_invite_user'                    => 1,  // When user invite in group.
			'bp_invitations_accepted_request'       => 1,  // When Group request accepted.
			'bp_invitations_accepted_invite'        => 1,  // When Group invitation accepted.
			'bp_invitation_after_delete'            => 1,  // When Group invitation deleted.
			'added_group_meta'                      => 2,  // When Group added update. This needed for sorting by group last activity, member course.
			'updated_group_meta'                    => 2,  // When Group meta update. This needed for sorting by group last activity, member course.
			'delete_group_meta'                     => 2,  // When Group meta deleted. This needed for sorting by group last activity, member course.

			// Added moderation support.
			'bp_suspend_groups_suspended'           => 1, // Any Group Suspended.
			'bp_suspend_groups_unsuspended'         => 1, // Any Group Unsuspended.

			// Add Author Embed Support.
			'profile_update'                        => 1, // User updated on site.
			'deleted_user'                          => 1, // User deleted on site.
			'xprofile_avatar_uploaded'              => 1, // User avatar photo updated.
			// 'bp_core_delete_existing_avatar'     => 1, //User avatar photo deleted. Manage with group as both use same action.
		);

		/**
		 * Add Custom events to purge single group endpoint cache
		 */
		$purge_single_events = apply_filters( 'bbplatform_cache_bp_groups', $purge_single_events );
		$this->purge_single_events( 'bbplatform_cache_purge_bp-groups_single', $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_social_groups', 'buddyboss-app' );
		$cache_bb_groups     = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_groups ) {

			$this->cache_endpoint(
				'buddyboss/v1/groups',
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
				'buddyboss/v1/groups/<id>',
				Cache::instance()->month_in_seconds * 60,
				array_keys( $purge_single_events ),
				$event_groups,
				array(),
				false
			);
		}
	}

	/******************************** Group Events ********************************/
	/**
	 * When Group change form admin
	 *
	 * @param int $group_id Group id.
	 */
	public function event_bp_group_admin_edit_after( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group created from Manage
	 */
	public function event_groups_create_group_step_complete() {
		$bp       = buddypress();
		$group_id = $bp->groups->new_group_id;
		if ( ! empty( $group_id ) ) {
			Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
		}
	}

	/**
	 * When Group Details updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_details_edited( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group setting updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_settings_edited( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group avarar updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_avatar_uploaded( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group cover photo uploaded form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_cover_image_uploaded( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group cover photo deleted form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_cover_image_deleted( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group forums setting Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_bp_group_admin_after_edit_screen_save( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When user join in group
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_join_group( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group member ban, unban, promoted, demoted
	 *
	 * @param object $member Member object.
	 */
	public function event_groups_member_after_save( $member ) {
		$group_id = $member->group_id;
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group member removed
	 *
	 * @param object $member Member object.
	 */
	public function event_groups_member_after_remove( $member ) {
		$group_id = $member->group_id;
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group membership request
	 *
	 * @param int   $user_id  User id.
	 * @param array $admins   Array of group admins.
	 * @param int   $group_id Group id.
	 */
	public function event_groups_membership_requested( $user_id, $admins, $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group invitation accepted
	 *
	 * @param int $user_id  User id.
	 * @param int $group_id Group id.
	 */
	public function event_groups_membership_accepted( $user_id, $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group invitation rejected
	 *
	 * @param int $user_id  User id.
	 * @param int $group_id Group id.
	 */
	public function event_groups_membership_rejected( $user_id, $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When user invite in group
	 *
	 * @param array $r Arguments array.
	 */
	public function event_groups_invite_user( $r ) {
		$group_id = $r['group_id'];
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group request accepted
	 *
	 * @param array $r Arguments array.
	 */
	public function event_bp_invitations_accepted_request( $r ) {
		$group_id = $r['item_id'];
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group invitation accepted
	 *
	 * @param array $r Arguments array.
	 */
	public function event_bp_invitations_accepted_invite( $r ) {
		$group_id = $r['item_id'];
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group invitation deleted
	 *
	 * @param array $r Arguments array.
	 */
	public function event_bp_invitation_after_delete( $r ) {
		$group_id = $r['item_id'];
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group added update. This needed for sorting by group last activity, member course.
	 *
	 * @param int $meta_id  Meta id.
	 * @param int $group_id Group id.
	 */
	public function event_added_group_meta( $meta_id, $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group meta update. This needed for sorting by group last activity, member course.
	 *
	 * @param int $meta_id  Meta id.
	 * @param int $group_id Group id.
	 */
	public function event_updated_group_meta( $meta_id, $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * When Group meta deleted. This needed for sorting by group last activity, member course.
	 *
	 * @param int $meta_id  Meta id.
	 * @param int $group_id Group id.
	 */
	public function event_delete_group_meta( $meta_id, $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Group ID.
	 *
	 * @param int $group_id Group ID.
	 */
	public function event_bp_suspend_groups_suspended( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/**
	 * Unsuspended Group ID.
	 *
	 * @param int $group_id Group ID.
	 */
	public function event_bp_suspend_groups_unsuspended( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
	}

	/****************************** Author Embed Support *****************************/
	/**
	 * User updated on site
	 *
	 * @param int $user_id User id.
	 */
	public function event_profile_update( $user_id ) {
		$group_ids = $this->get_group_ids_by_userid( $user_id );
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User id.
	 */
	public function event_deleted_user( $user_id ) {
		$group_ids = $this->get_group_ids_by_userid( $user_id );
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User id.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$group_ids = $this->get_group_ids_by_userid( $user_id );
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_id ) {
				Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
			}
		}
	}

	/**
	 * User avatar photo deleted
	 *
	 * @param array $args Arguments array.
	 */
	public function event_bp_core_delete_existing_avatar( $args ) {
		$item_id = ! empty( $args['item_id'] ) ? absint( $args['item_id'] ) : 0; // group/user id.
		if ( ! empty( $user_id ) ) {
			if ( isset( $args['object'] ) && 'user' === $args['object'] ) {
				$user_id   = $item_id;
				$group_ids = $this->get_group_ids_by_userid( $user_id );
				if ( ! empty( $group_ids ) ) {
					foreach ( $group_ids as $group_id ) {
						Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
					}
				}
			} elseif ( isset( $args['object'] ) && 'group' === $args['object'] ) {
				$group_id = $item_id;
				Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
			}
		}
	}

	/*********************************** Functions ***********************************/
	/**
	 * Get Activities ids from user name.
	 *
	 * @param int $user_id User id.
	 *
	 * @return array
	 */
	private function get_group_ids_by_userid( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name} WHERE creator_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}
}
