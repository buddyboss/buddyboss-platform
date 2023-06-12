<?php
/**
 * BuddyBoss Performance Members Integration.
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
 * Members Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Members extends Integration_Abstract {

	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-members' );

		$purge_events = array(
			'after_signup_site', // when Record blog registation information for future activation.
			'after_signup_user', // when Record user signup information for future activation.
			'wpmu_activate_user', // when user activated in multisite.
			'wpmu_activate_blog', // when blog activated in multisite.
			'wpmu_new_user', // when user created in multisite.
			'user_register', // New user created on site.
			'deleted_user', // New user deleted on site.
			'make_spam_user', // When user mark as spam user.
			'make_ham_user', // When user mark as ham user.
			'add_user_to_blog', // when user added in subsite.
			'remove_user_from_blog', // when user removed from subsite.
			'bp_core_signup_user', // when Record user signup information for future activation in buddypress.
			'bp_core_signup_after_delete', // when Record user signup information deleted in buddypress.
			'friends_friendship_accepted', // when Friend added friends list should be update.
			'friends_friendship_post_delete', // when Friend removed friends list should be update.

			// Added moderation support.
			'bp_suspend_user_suspended',       // Any User Suspended.
			'bp_suspend_user_unsuspended',     // Any User Unsuspended.
		);

		$this->purge_event( 'bp-members', $purge_events );
		$this->purge_event( 'bbapp-deeplinking', $purge_events );
		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'profile_update'                                 => 1, // New user updated on site.
			'deleted_user'                                   => 1, // New user deleted on site.
			'make_spam_user'                                 => 1, // When user mark as spam user.
			'make_ham_user'                                  => 1, // When user mark as ham user.
			'add_user_to_blog'                               => 1, // when user added in subsite.
			'remove_user_from_blog'                          => 1, // when user removed from subsite.
			'bp_core_signup_after_delete'                    => 1, // when Record user signup information deleted in buddypress.
			'bp_start_following'                             => 1, // when user start following.
			'bp_follow_start_following'                      => 1, // when user start following using BuddyPress Follow.
			'bp_stop_following'                              => 1, // when user stop following.
			'bp_follow_stop_following'                       => 1, // when user stop following using BuddyPress Follow.
			'friends_friendship_requested'                   => 3, // When friendship requested.
			'friends_friendship_accepted'                    => 3, // When friendship request accepted.
			'friends_friendship_deleted'                     => 3, // When friendship request delete.
			'friends_friendship_rejected'                    => 2, // When friendship request rejected.
			'friends_friendship_post_delete'                 => 2, // When friendship deleted.
			'friends_friendship_withdrawn'                   => 2, // When friendship withdrawn.
			'xprofile_updated_profile'                       => 1, // When user xprofile field updated.
			'xprofile_data_after_save'                       => 1, // When user xprofile field updated.
			'xprofile_avatar_uploaded'                       => 1, // When User avatar photo updated form Manage.
			'bp_core_delete_existing_avatar'                 => 1, // When User avatar photo deleted.
			'xprofile_cover_image_uploaded'                  => 1, // When user cover photo uploaded form Manage.
			'xprofile_cover_image_deleted'                   => 1, // When user cover photo deleted form Manage.
			'bp_core_user_updated_last_activity'             => 1, // When user last activity meta updated.
			// 'updated_user_meta'                           => 1, // When user meta updated. we enabled this if only required.
			'badgeos_update_users_points'                    => 1, // When user point updated using badgeos.
			'gamipress_update_user_points'                   => 1, // When user point updated using gamipress.

			// Added moderation support.
			'bp_suspend_user_suspended'                      => 1, // Any User Suspended.
			'bp_suspend_user_unsuspended'                    => 1, // Any User Unsuspended.

			// When change/update the profile avatar and cover options.
			'update_option_show_avatars'                     => 3,
			'update_option_avatar_rating'                    => 3,
			'update_option_avatar_default'                   => 3,
			'update_option_bp-profile-avatar-type'           => 3,
			'update_option_bp-disable-avatar-uploads'        => 3,
			'update_option_bp-enable-profile-gravatar'       => 3,
			'update_option_default-profile-avatar-type'      => 3,
			'update_option_bp-default-custom-profile-avatar' => 3,
			'update_option_bp-disable-cover-image-uploads'   => 3,
			'update_option_bp-default-profile-cover-type'    => 3,
			'update_option_bp-default-custom-profile-cover'  => 3,
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_members', 'buddyboss-app' );
		$cache_bb_members    = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_members ) {

			$this->cache_endpoint(
				'buddyboss/v1/members',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
					'purge_deep_events' => array_keys( $purge_single_events ),
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/members/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/**
	 * New user updated on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_profile_update( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );

	}

	/**
	 * New user deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user mark as spam user
	 *
	 * @param int $user_id User ID.
	 */
	public function event_make_spam_user( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user mark as ham user
	 *
	 * @param int $user_id User ID.
	 */
	public function event_make_ham_user( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user added in subsite.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_add_user_to_blog( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user removed from subsite.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_remove_user_from_blog( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When Record user signup information deleted in buddypress.
	 *
	 * @param array $signup_ids Signup IDs.
	 */
	public function event_bp_core_signup_after_delete( $signup_ids ) {

		if ( ! empty( $signup_ids ) && is_array( $signup_ids ) ) {
			$to_delete = \BP_Signup::get(
				array(
					'include' => $signup_ids,
				)
			);
			$signups   = ! empty( $to_delete['signups'] ) ? $to_delete['signups'] : '';
			if ( ! empty( $signups ) ) {
				foreach ( $signups as $signup ) {
					$user_id = username_exists( $signup->user_login );
					$this->purge_item_cache_by_item_id( $user_id );
				}
			}
		}

	}

	/**
	 * When user start following.
	 *
	 * @param BP_Activity_Follow $follow Object of Follow.
	 */
	public function event_bp_start_following( $follow ) {
		$this->purge_item_cache_by_item_id( $follow->leader_id );
	}

	/**
	 * When user start following using BuddyPress Follow.
	 *
	 * @param BP_Activity_Follow $follow Object of Follow.
	 */
	public function event_bp_follow_start_following( $follow ) {
		$this->purge_item_cache_by_item_id( $follow->leader_id );
	}

	/**
	 * When user stop following.
	 *
	 * @param BP_Activity_Follow $follow Object of Follow.
	 */
	public function event_bp_stop_following( $follow ) {
		$this->purge_item_cache_by_item_id( $follow->leader_id );
	}

	/**
	 * When user stop following using BuddyPress Follow.
	 *
	 * @param BP_Activity_Follow $follow Object of Follow.
	 */
	public function event_bp_follow_stop_following( $follow ) {
		$this->purge_item_cache_by_item_id( $follow->leader_id );
	}

	/**
	 * When friendship requested
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 */
	public function event_friends_friendship_requested( $friendship_id, $initiator_user_id, $friend_user_id ) {
		$this->purge_item_cache_by_item_id( $initiator_user_id );
		$this->purge_item_cache_by_item_id( $friend_user_id );
	}

	/**
	 * When friendship request accepted
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 */
	public function event_friends_friendship_accepted( $friendship_id, $initiator_user_id, $friend_user_id ) {
		$this->purge_item_cache_by_item_id( $initiator_user_id );
		$this->purge_item_cache_by_item_id( $friend_user_id );
	}

	/**
	 * When friendship request accepted
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 */
	public function event_friends_friendship_deleted( $friendship_id, $initiator_user_id, $friend_user_id ) {
		$this->purge_item_cache_by_item_id( $initiator_user_id );
		$this->purge_item_cache_by_item_id( $friend_user_id );
	}

	/**
	 * When friendship request rejected
	 *
	 * @param int                   $friendship_id ID of the pending friendship.
	 * @param BP_Friends_Friendship $friendship    Friendship object. Passed by reference.
	 */
	public function event_friends_friendship_rejected( $friendship_id, $friendship ) {
		$this->purge_item_cache_by_item_id( $friendship->initiator_user_id );
		$this->purge_item_cache_by_item_id( $friendship->friend_user_id );
	}

	/**
	 * When friendship deleted
	 *
	 * @param int $initiator_userid ID of the friendship initiator.
	 * @param int $friend_userid    ID of the friend user.
	 */
	public function event_friends_friendship_post_delete( $initiator_userid, $friend_userid ) {
		$this->purge_item_cache_by_item_id( $initiator_userid );
		$this->purge_item_cache_by_item_id( $friend_userid );
	}

	/**
	 * When friendship withdrawn
	 *
	 * @param int                   $friendship_id ID of the friendship.
	 * @param BP_Friends_Friendship $friendship    Friendship object. Passed by reference.
	 */
	public function event_friends_friendship_withdrawn( $friendship_id, $friendship ) {
		$this->purge_item_cache_by_item_id( $friendship->initiator_user_id );
		$this->purge_item_cache_by_item_id( $friendship->friend_user_id );
	}

	/**
	 * When user xprofile field updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_updated_profile( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user xprofile update using API.
	 *
	 * @param BP_XProfile_ProfileData $field Current instance of the profile data being saved.
	 */
	public function event_xprofile_data_after_save( $field ) {
		if ( ! empty( $field->user_id ) ) {
			$this->purge_item_cache_by_item_id( $field->user_id );
		}
	}

	/**
	 * When User avatar photo updated form Manage
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When User avatar photo deleted
	 *
	 * @param array $args Array of arguments used for avatar deletion.
	 */
	public function event_bp_core_delete_existing_avatar( $args ) {
		$user_id = ! empty( $args['item_id'] ) ? absint( $args['item_id'] ) : 0;
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user cover photo uploaded form Manage
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_cover_image_uploaded( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user cover photo deleted form Manage
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_cover_image_deleted( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user last activity meta updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_core_user_updated_last_activity( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id, false );
	}

	/**
	 * When user point updated using badgeos.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_badgeos_update_users_points( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * When user point updated using gamipress.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_gamipress_update_user_points( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended User ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_suspended( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * Unsuspended User ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_unsuspended( $user_id ) {
		$this->purge_item_cache_by_item_id( $user_id );
	}

	/**
	 * Purge item cache by item id.
	 *
	 * @param $member_id
	 */
	private function purge_item_cache_by_item_id( $member_id, $clear_subscription = true ) {
		Cache::instance()->purge_by_group( 'bp-members_' . $member_id );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( bp_core_get_user_domain( $member_id ) ) );
		if ( $clear_subscription ) {
			$this->purge_subscription_cache_by_user_id( $member_id );
		}
	}

	/**
	 * When Avatar Display option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_show_avatars( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Maximum Rating option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_avatar_rating( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Default Avatar option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_avatar_default( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Profile Avatars option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_profile_avatar_type( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Upload Avatars option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_disable_avatar_uploads( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Enable Gravatars option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_enable_profile_gravatar( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Default Profile Avatar option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_profile_avatar_type( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Upload Custom Avatar option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_custom_profile_avatar( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Profile Cover Images option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_disable_cover_image_uploads( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Default Profile Cover Image option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_profile_cover_type( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * When Upload Custom Cover Image option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_custom_profile_cover( $old_value, $value, $option ) {
		$this->purge_cache_on_change_default_profile_images_settings();
	}

	/**
	 * Purge caches when change the settings related to profile avatar and cover from the backend.
	 */
	public function purge_cache_on_change_default_profile_images_settings() {
		Cache::instance()->purge_by_component( 'bp-' );
		Cache::instance()->purge_by_component( 'bbp-' );
		Cache::instance()->purge_by_component( 'app_page' );
		Cache::instance()->purge_by_component( 'blog-post' );
		Cache::instance()->purge_by_component( 'categories' );
		Cache::instance()->purge_by_component( 'post_comment' );
		Cache::instance()->purge_by_component( 'sfwd-' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
		Cache::instance()->purge_by_group( 'bb-subscriptions' );
		Cache::instance()->purge_by_component( 'bb-subscriptions_' );
	}

	/**
	 * Purge item cache by user id.
	 *
	 * @param int $user_id User ID.
	 */
	private function purge_subscription_cache_by_user_id( $user_id ) {
		$args = array(
			'user_id' => $user_id,
			'fields'  => 'id',
			'status'  => null,
		);

		$all_subscription = bb_get_subscriptions(
			$args,
			true
		);

		if ( ! empty( $all_subscription['subscriptions'] ) ) {
			foreach ( $all_subscription['subscriptions'] as $subscription_id ) {
				Cache::instance()->purge_by_group( 'bb-subscriptions_' . $subscription_id );
			}

			Cache::instance()->purge_by_group( 'bb-subscriptions' );
		}
	}
}
