<?php
/**
 * BuddyBoss Performance Group Integration.
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

		$purge_events = array(
			'bp_group_admin_edit_after',         // When Group change form admin.
			'groups_created_group',              // When Group is created from api.
			'groups_create_group_step_complete', // When Group created from Manage.
			'groups_delete_group',               // When Group was deleted.
			'groups_join_group',                 // When user join the group.
			'groups_leave_group',                // When user leave the group.
			'bp_invitations_accepted_invite',    // When Group invitation has been accepted.

			// Added moderation support.
			'bp_suspend_groups_suspended',       // Any Group Suspended.
			'bp_suspend_groups_unsuspended',     // Any Group Unsuspended.
		);

		$this->purge_event( 'bp-groups', $purge_events );
		$this->purge_event( 'bbapp-deeplinking', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bp_group_admin_edit_after'                          => 1,  // When Group change form admin.
			'groups_create_group_step_complete'                  => 0,  // When Group created from Manage.
			'groups_created_group'                               => 1,  // When Group was created/updated.
			'groups_delete_group'                                => 1,  // When Group was deleted.
			'groups_group_details_edited'                        => 1,  // When Group Details updated form Manage.
			'groups_group_settings_edited'                       => 1,  // When Group setting updated form Manage.
			'groups_avatar_uploaded'                             => 1,  // When Group avatar updated form Manage.
			'bp_core_delete_existing_avatar'                     => 1,  // When Group avatar deleted.
			'groups_cover_image_uploaded'                        => 1,  // When Group cover photo uploaded form Manage.
			'groups_cover_image_deleted'                         => 1,  // When Group cover photo deleted form Manage.
			'bp_group_admin_after_edit_screen_save'              => 1,  // When Group forums setting Manage.
			'groups_join_group'                                  => 1,  // When user join in group.
			'groups_leave_group'                                 => 2,  // When user leave the group.
			'groups_member_after_save'                           => 1,  // When Group member ban, unban, promoted, demoted.
			'groups_member_after_remove'                         => 1,  // When Group member removed.
			'groups_membership_requested'                        => 3,  // When Group membership request.
			'groups_membership_accepted'                         => 2,  // When Group invitation accepted.
			'groups_membership_rejected'                         => 2,  // When Group invitation rejected.
			'groups_invite_user'                                 => 1,  // When user invite in group.
			'bp_invitations_accepted_request'                    => 1,  // When Group request accepted.
			'bp_invitations_accepted_invite'                     => 1,  // When Group invitation accepted.
			'bp_invitation_after_delete'                         => 1,  // When Group invitation deleted.
			'added_group_meta'                                   => 2,  // When Group added update. This needed for sorting by group last activity, member course.
			'updated_group_meta'                                 => 2,  // When Group meta update. This needed for sorting by group last activity, member course.
			'delete_group_meta'                                  => 2,  // When Group meta deleted. This needed for sorting by group last activity, member course.

			// Added moderation support.
			'bp_suspend_groups_suspended'                        => 1, // Any Group Suspended.
			'bp_suspend_groups_unsuspended'                      => 1, // Any Group Unsuspended.

			// Add Author Embed Support.
			'profile_update'                                     => 1, // User updated on site.
			'deleted_user'                                       => 1, // User deleted on site.
			'xprofile_avatar_uploaded'                           => 1, // User avatar photo updated.
			// 'bp_core_delete_existing_avatar'                  => 1, //User avatar photo deleted. Manage with group as both use same action.

			// When change/update the group avatar and cover options.
			'update_option_bp-disable-group-avatar-uploads'      => 3,
			'update_option_bp-default-group-avatar-type'         => 3,
			'update_option_bp-default-custom-group-avatar'       => 3,
			'update_option_bp-disable-group-cover-image-uploads' => 3,
			'update_option_bp-default-group-cover-type'          => 3,
			'update_option_bp-default-custom-group-cover'        => 3,

			// For Group Media/Album Support.
			'update_option_bp_media_group_media_support'    => 3,
			'update_option_bp_media_group_albums_support'   => 3,

			// For Group Document Support.
			'update_option_bp_media_group_document_support' => 3,

			// For Group Video Support.
			'update_option_bp_video_group_video_support'    => 3,
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_social_groups', 'buddyboss-app' );
		$cache_bb_groups     = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_groups ) {

			$this->cache_endpoint(
				'buddyboss/v1/groups',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id' => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/groups/<id>',
				Cache::instance()->month_in_seconds * 60,
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
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group created from Manage
	 */
	public function event_groups_create_group_step_complete() {
		$bp       = buddypress();
		$group_id = $bp->groups->new_group_id;
		if ( ! empty( $group_id ) ) {
			$this->purge_item_cache_by_item_id( $group_id );
		}
	}

	/**
	 * When Group was created/updated.
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_created_group( $group_id ) {
		if ( ! empty( $group_id ) ) {
			$this->purge_item_cache_by_item_id( $group_id );
		}
	}

	/**
	 * When Group was Deleted.
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_delete_group( $group_id ) {
		if ( ! empty( $group_id ) ) {
			$this->purge_item_cache_by_item_id( $group_id );
		}
	}

	/**
	 * When Group Details updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_details_edited( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group setting updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_settings_edited( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group avarar updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_avatar_uploaded( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group cover photo uploaded form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_cover_image_uploaded( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group cover photo deleted form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_cover_image_deleted( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group forums setting Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_bp_group_admin_after_edit_screen_save( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When user join in group
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_join_group( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When user join in group
	 *
	 * @param int $group_id Group id.
	 * @param int $user_id  User id.
	 */
	public function event_groups_leave_group( $group_id, $user_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group member ban, unban, promoted, demoted
	 *
	 * @param object $member Member object.
	 */
	public function event_groups_member_after_save( $member ) {
		$this->purge_item_cache_by_item_id( $member->group_id );
	}

	/**
	 * When Group member removed
	 *
	 * @param object $member Member object.
	 */
	public function event_groups_member_after_remove( $member ) {
		$this->purge_item_cache_by_item_id( $member->group_id );
	}

	/**
	 * When Group membership request
	 *
	 * @param int   $user_id  User id.
	 * @param array $admins   Array of group admins.
	 * @param int   $group_id Group id.
	 */
	public function event_groups_membership_requested( $user_id, $admins, $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group invitation accepted
	 *
	 * @param int $user_id  User id.
	 * @param int $group_id Group id.
	 */
	public function event_groups_membership_accepted( $user_id, $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group invitation rejected
	 *
	 * @param int $user_id  User id.
	 * @param int $group_id Group id.
	 */
	public function event_groups_membership_rejected( $user_id, $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When user invite in group
	 *
	 * @param array $r Arguments array.
	 */
	public function event_groups_invite_user( $r ) {
		$this->purge_item_cache_by_item_id( $r['group_id'] );
	}

	/**
	 * When Group request accepted
	 *
	 * @param array $r Arguments array.
	 */
	public function event_bp_invitations_accepted_request( $r ) {
		$this->purge_item_cache_by_item_id( $r['item_id'] );
	}

	/**
	 * When Group invitation accepted
	 *
	 * @param array $r Arguments array.
	 */
	public function event_bp_invitations_accepted_invite( $r ) {
		$this->purge_item_cache_by_item_id( $r['item_id'] );
	}

	/**
	 * When Group invitation deleted
	 *
	 * @param array $r Arguments array.
	 */
	public function event_bp_invitation_after_delete( $r ) {
		$this->purge_item_cache_by_item_id( $r['item_id'] );
	}

	/**
	 * When Group added update. This needed for sorting by group last activity, member course.
	 *
	 * @param int $meta_id  Meta id.
	 * @param int $group_id Group id.
	 */
	public function event_added_group_meta( $meta_id, $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group meta update. This needed for sorting by group last activity, member course.
	 *
	 * @param int $meta_id  Meta id.
	 * @param int $group_id Group id.
	 */
	public function event_updated_group_meta( $meta_id, $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * When Group meta deleted. This needed for sorting by group last activity, member course.
	 *
	 * @param int $meta_id  Meta id.
	 * @param int $group_id Group id.
	 */
	public function event_delete_group_meta( $meta_id, $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Group ID.
	 *
	 * @param int $group_id Group ID.
	 */
	public function event_bp_suspend_groups_suspended( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
	}

	/**
	 * Unsuspended Group ID.
	 *
	 * @param int $group_id Group ID.
	 */
	public function event_bp_suspend_groups_unsuspended( $group_id ) {
		$this->purge_item_cache_by_item_id( $group_id );
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
				$this->purge_item_cache_by_item_id( $group_id );
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
				$this->purge_item_cache_by_item_id( $group_id );
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
				$this->purge_item_cache_by_item_id( $group_id );
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
						$this->purge_item_cache_by_item_id( $group_id );
					}
				}
			} elseif ( isset( $args['object'] ) && 'group' === $args['object'] ) {
				$this->purge_item_cache_by_item_id( $item_id );
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

	/**
	 * Purge item cache by item id.
	 *
	 * @param $group_id
	 */
	private function purge_item_cache_by_item_id( $group_id ) {
		Cache::instance()->purge_by_group( 'bp-groups_' . $group_id );
		$group = new \BP_Groups_Group( $group_id );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( bp_get_group_permalink( $group ) ) );
	}

	/**
	 * When Group Avatars option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_disable_group_avatar_uploads( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When Default Group Avatar option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_group_avatar_type( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When Upload Custom Avatar option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_custom_group_avatar( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When Group Cover Images option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_disable_group_cover_image_uploads( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When Default Group Cover Image option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_group_cover_type( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When Upload Custom Cover Image option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_custom_group_cover( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * Purge caches when change the settings related to group avatar and cover from the backend.
	 */
	public function purge_cache_on_change_default_group_images_settings() {
		Cache::instance()->purge_by_component( 'bp-groups' );
		Cache::instance()->purge_by_component( 'app_page' );
		Cache::instance()->purge_by_component( 'sfwd-' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
	}

	/**
	 * When Upload media option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_media_group_media_support( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When Upload album option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_media_group_albums_support( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When Upload document option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_media_group_document_support( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}

	/**
	 * When group video option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_video_group_video_support( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_group_images_settings();
	}
}
