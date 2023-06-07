<?php
/**
 * BuddyBoss Performance Messages Integration.
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
 * Messages Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Messages extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-messages' );

		$purge_events = array(
			'messages_message_sent', // when new message created.
			'bp_messages_thread_after_delete', // when message deleted.
			'messages_delete_thread', // when message thread deleted.
			'wp_ajax_messages_hide_thread', // when message thread hide.
			'bb_messages_thread_archived', // When thread has been hidden.
			'bb_messages_thread_unarchived', // When thread has been unhidden.

			// Added moderation support.
			'bp_suspend_user_suspended', // Any User Suspended.
			'bp_suspend_user_unsuspended', // Any User Unsuspended.
			'bp_suspend_message_thread_suspended', // Any Message Thread Suspended.
			'bp_suspend_message_thread_unsuspended', // Any Message Thread Unsuspended.

			'update_option_bp-force-friendship-to-message', // Fired when admin update the group settings.
			'update_option_bp-active-components', // Fired when admin enable/disable the friends component.
			'update_option_bp-disable-group-messages', // Fired when admin disabled group messages.
			'update_option_bb-access-control-send-message', // Fired access control settings has been updated.

			'bb_group_messages_banned_member', // When user banned into the group message.
			'bb_group_messages_unbanned_member', // When user unbanned into the group message.
			'groups_before_delete_group', // When group has been deleted.

			'groups_join_group', // When member join the group thread.
			'groups_leave_group', // When member leave the group thread.
			'groups_remove_member', // When admin/moderator removed user from the thread.

			'groups_promote_member', // When group member promoted.
			'groups_demote_member', // When group member demoted.

		);

		$this->purge_event( 'bp-messages', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'messages_message_sent'                        => 1, // when new message created.
			'bp_messages_thread_after_delete'              => 2, // when message deleted.
			'messages_delete_thread'                       => 1, // when message thread deleted.
			'wp_ajax_messages_hide_thread'                 => 1, // when message thread hide.
			'messages_thread_mark_as_read'                 => 1, // when messages mark as read.
			'messages_thread_mark_as_unread'               => 1, // when messages mark as unread.
			'updated_message_meta'                         => 1, // when messages meta updated.
			'add_message_meta'                             => 1, // when messages meta added.
			'delete_message_meta'                          => 1, // when messages meta deleted.
			'bb_messages_thread_archived'                  => 2, // When thread has been hidden.
			'bb_messages_thread_unarchived'                => 2, // When thread has been unhidden.

			// Added moderation support.
			'bp_suspend_user_suspended'                    => 1, // Any User Suspended.
			'bp_suspend_user_unsuspended'                  => 1, // Any User Unsuspended.
			'bp_suspend_message_thread_suspended'          => 1, // Any Message Thread Suspended.
			'bp_suspend_message_thread_unsuspended'        => 1, // Any Message Thread Unsuspended.
			'bp_moderation_after_save'                     => 1, // Any User blocked.
			'bb_moderation_after_delete'                   => 1, // Any User unblocked.

			// Add Author Embed Support.
			'profile_update'                               => 1, // User updated on site.
			'deleted_user'                                 => 1, // User deleted on site.
			'xprofile_avatar_uploaded'                     => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'               => 1, // User avatar photo deleted.

			// Admin settings update.
			'update_option_bp-force-friendship-to-message' => 2, // Fired when admin update the group settings.
			'update_option_bp-active-components'           => 2, // Fired when admin enable/disable the friends component.
			'update_option_bp-disable-group-messages'      => 2, // Fired when admin disabled group messages.
			'update_option_bb-access-control-send-message' => 2, // Fired access control settings has been updated.

			// Group thread updates.
			'bb_group_messages_banned_member'              => 1, // When user banned into the group message.
			'bb_group_messages_unbanned_member'            => 1, // When user unbanned into the group message.
			'groups_before_delete_group'                   => 1, // When user deleted the group.
			'groups_join_group'                            => 1, // When member join the group thread.
			'groups_leave_group'                           => 1, // When member leave the group thread.
			'groups_remove_member'                         => 1, // When admin/moderator removed user from the thread.
			'updated_group_meta'                           => 3, // When admin/moderator removed user from the thread.
			'groups_promote_member'                        => 1, // When group member promoted.
			'groups_demote_member'                         => 1, // When group member demoted.

			// User Friendship updates.
			'friends_friendship_requested'                 => 3, // User sent the friendship.
			'friends_friendship_withdrawn'                 => 2, // User withdrawn the friendship.
			'friends_friendship_post_delete'               => 2, // User delete the friendship.
			'friends_friendship_accepted'                  => 3, // User accept the friendship.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_private_messaging', 'buddyboss-app' );
		$cache_bb_messages   = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_messages ) {

			$this->cache_endpoint(
				'buddyboss/v1/messages',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id' => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/messages/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/****************************** Messages Events *****************************/
	/**
	 * When new message created
	 *
	 * @param BP_Messages_Message $message Message object. Passed by reference.
	 */
	public function event_messages_message_sent( $message ) {
		$thread_id = $message->thread_id;
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When message deleted
	 *
	 * @param int   $thread_id   ID of the thread being deleted.
	 * @param array $message_ids IDs of messages being deleted.
	 */
	public function event_bp_messages_thread_after_delete( $thread_id, $message_ids ) {
	}

	/**
	 * When message thread deleted
	 *
	 * @param int|array $thread_ids Thread ID or array of thread IDs that were deleted.
	 */
	public function event_messages_delete_thread( $thread_ids ) {
		if ( ! empty( $thread_ids ) ) {
			$thread_ids = ! is_array( $thread_ids ) ? array( $thread_ids ) : $thread_ids;
			foreach ( $thread_ids as $thread_id ) {
				Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
			}
		}
	}

	/**
	 * When message thread hide
	 *
	 * @param array $thread_ids Array of thread IDs.
	 */
	public function event_wp_ajax_messages_hide_thread( $thread_ids = array() ) {
		if ( empty( $thread_ids ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$thread_ids = wp_parse_id_list( $_POST['id'] );
		}
		if ( ! empty( $thread_ids ) ) {
			foreach ( $thread_ids as $thread_id ) {
				Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
			}
		}
	}

	/**
	 * When messages mark as read.
	 *
	 * @param int $thread_id The message thread ID.
	 */
	public function event_messages_thread_mark_as_read( $thread_id ) {
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When messages mark as unread.
	 *
	 * @param int $thread_id The message thread ID.
	 */
	public function event_messages_thread_mark_as_unread( $thread_id ) {
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When messages meta updated.
	 *
	 * @param int $message_id Message id.
	 */
	public function event_updated_message_meta( $message_id ) {
		$thread_id = $this->get_thread_id( $message_id );
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When messages meta added.
	 *
	 * @param int $message_id Message id.
	 */
	public function event_add_message_meta( $message_id ) {
		$thread_id = $this->get_thread_id( $message_id );
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When messages meta deleted.
	 *
	 * @param int $message_id Message id.
	 */
	public function event_delete_message_meta( $message_id ) {
		$thread_id = $this->get_thread_id( $message_id );
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When message thread has been archived/hidden.
	 *
	 * @param int $thread_id Thread id.
	 * @param int $user_id User id.
	 *
	 * @return void
	 */
	public function event_bb_messages_thread_archived( $thread_id, $user_id ) {
		Cache::instance()->purge_by_user_id( $user_id, 'bp-messages_' . $thread_id );
	}

	/**
	 * When message thread has been unarchived/unhide.
	 *
	 * @param int $thread_id Thread id.
	 * @param int $user_id User id.
	 *
	 * @return void
	 */
	public function event_bb_messages_thread_unarchived( $thread_id, $user_id ) {
		Cache::instance()->purge_by_user_id( $user_id, 'bp-messages_' . $thread_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended User ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_suspended( $user_id ) {
		$thread_ids = $this->get_thread_ids_by_userid( $user_id );
		if ( ! empty( $thread_ids ) ) {
			foreach ( $thread_ids as $thread_id ) {
				Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
			}
		}
	}

	/**
	 * Unsuspended User ID.
	 *
	 * @param int $user_id User ID.
	 */
	public function event_bp_suspend_user_unsuspended( $user_id ) {
		$thread_ids = $this->get_thread_ids_by_userid( $user_id );
		if ( ! empty( $thread_ids ) ) {
			foreach ( $thread_ids as $thread_id ) {
				Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
			}
		}
	}

	/**
	 * Suspended Message thread ID.
	 *
	 * @param int $thread_id Message thread ID.
	 */
	public function event_bp_suspend_message_thread_suspended( $thread_id ) {
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * Unsuspended Message thread ID.
	 *
	 * @param int $thread_id Message thread ID.
	 */
	public function event_bp_suspend_message_thread_unsuspended( $thread_id ) {
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When user blocked.
	 *
	 * @param BP_Moderation $moderation Object of moderation data.
	 */
	public function event_bp_moderation_after_save( $moderation ) {
		if ( 'user' === $moderation->item_type ) {
			$creator_id = $moderation->user_id;
			$blocked_id = $moderation->item_id;

			$creator_thread_ids = $this->get_thread_ids_by_userid( $creator_id );
			if ( ! empty( $creator_thread_ids ) ) {
				foreach ( $creator_thread_ids as $thread_id ) {
					Cache::instance()->purge_by_user_id( $creator_id, 'bp-messages_' . $thread_id );
				}
			}

			$blocked_thread_ids = $this->get_thread_ids_by_userid( $blocked_id );
			if ( ! empty( $blocked_thread_ids ) ) {
				foreach ( $blocked_thread_ids as $thread_id ) {
					Cache::instance()->purge_by_user_id( $blocked_id, 'bp-messages_' . $thread_id );
				}
			}
		}
	}

	/**
	 * When user unblocked.
	 *
	 * @param BP_Moderation $moderation Object of moderation data.
	 */
	public function event_bb_moderation_after_delete( $moderation ) {
		if ( 'user' === $moderation->item_type ) {
			$creator_id   = $moderation->user_id;
			$unblocked_id = $moderation->item_id;

			$creator_thread_ids = $this->get_thread_ids_by_userid( $creator_id );
			if ( ! empty( $creator_thread_ids ) ) {
				foreach ( $creator_thread_ids as $thread_id ) {
					Cache::instance()->purge_by_user_id( $creator_id, 'bp-messages_' . $thread_id );
				}
			}

			$unblocked_thread_ids = $this->get_thread_ids_by_userid( $unblocked_id );
			if ( ! empty( $unblocked_thread_ids ) ) {
				foreach ( $unblocked_thread_ids as $thread_id ) {
					Cache::instance()->purge_by_user_id( $unblocked_id, 'bp-messages_' . $thread_id );
				}
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
		$thread_ids = $this->get_thread_ids_by_userid( $user_id );
		if ( ! empty( $thread_ids ) ) {
			foreach ( $thread_ids as $thread_id ) {
				Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$thread_ids = $this->get_thread_ids_by_userid( $user_id );
		if ( ! empty( $thread_ids ) ) {
			foreach ( $thread_ids as $thread_id ) {
				Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$thread_ids = $this->get_thread_ids_by_userid( $user_id );
		if ( ! empty( $thread_ids ) ) {
			foreach ( $thread_ids as $thread_id ) {
				Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
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
				$thread_ids = $this->get_thread_ids_by_userid( $user_id );
				if ( ! empty( $thread_ids ) ) {
					foreach ( $thread_ids as $thread_id ) {
						Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
					}
				}
			}
		}
	}

	/**
	 * Member connection setting updated for messages
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 *
	 * @return void
	 */
	public function event_update_option_bp_force_friendship_to_message( $old_value, $new_value ) {
		if ( $old_value !== $new_value ) {
			Cache::instance()->purge_by_component( 'bp-messages' );
		}
	}

	/**
	 * When the component has been updated.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 *
	 * @return void
	 */
	public function event_update_option_bp_active_components( $old_value, $new_value ) {
		if (
			( isset( $old_value['messages'] ) && ! isset( $new_value['messages'] ) ) ||
			( ! isset( $old_value['messages'] ) && isset( $new_value['messages'] ) ) ||
			( isset( $old_value['friends'] ) && ! isset( $new_value['friends'] ) ) ||
			( ! isset( $old_value['friends'] ) && isset( $new_value['friends'] ) )
		) {
			Cache::instance()->purge_by_component( 'bp-messages' );
		}
	}

	/**
	 * When group messages has been disabled by admin.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 *
	 * @return void
	 */
	public function event_update_option_bp_disable_group_messages( $old_value, $new_value ) {
		if ( $old_value !== $new_value ) {
			Cache::instance()->purge_by_component( 'bp-messages' );
		}
	}

	/**
	 * When group messages has been disabled by admin.
	 *
	 * @param mixed $old_value The old option value.
	 * @param mixed $new_value The new option value.
	 *
	 * @return void
	 */
	public function event_update_option_bb_access_control_send_message( $old_value, $new_value ) {
		$is_changed = false;
		if ( isset( $old_value['access-control-type'] ) && ! isset( $new_value['access-control-type'] ) ) {
			$is_changed = true;
		} elseif ( ! isset( $old_value['access-control-type'] ) && isset( $new_value['access-control-type'] ) ) {
			$is_changed = true;
		} elseif ( isset( $old_value, $new_value ) ) {

			if ( isset( $old_value['access-control-type'] ) && isset( $new_value['access-control-type'] ) && ( ! empty( $old_value['access-control-type'] ) || ! empty( $new_value['access-control-type'] ) ) && $old_value['access-control-type'] !== $new_value['access-control-type'] ) {
				$is_changed = true;
			} elseif ( isset( $old_value['access-control-options'] ) && isset( $new_value['access-control-options'] ) && ( ! empty( $old_value['access-control-options'] ) || ! empty( $new_value['access-control-options'] ) ) ) {

				if ( ! empty( array_merge( array_diff( $old_value['access-control-options'], $new_value['access-control-options'] ), array_diff( $new_value['access-control-options'], $old_value['access-control-options'] ) ) ) ) {
					$is_changed = true;
				} else {
					foreach ( $new_value['access-control-options'] as $option ) {
						$key = 'access-control-' . $option . '-options';
						if ( $old_value[ $key ] !== $new_value[ $key ] ) {
							$is_changed = true;
							break;
						}
					}
				}
			}
		}

		if ( $is_changed ) {
			Cache::instance()->purge_by_component( 'bp-messages' );
		}
	}

	/**
	 * When user banned into the group thread.
	 *
	 * @param int $thread_id Message thread ID.
	 */
	public function event_bb_group_messages_banned_member( $thread_id ) {
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * When user unbanned into the group thread.
	 *
	 * @param int $thread_id Message thread ID.
	 */
	public function event_bb_group_messages_unbanned_member( $thread_id ) {
		Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
	}

	/**
	 * Fire before group delete.
	 *
	 * @param int $group_id Group id.
	 *
	 * @return void
	 */
	public function event_groups_before_delete_group( $group_id ) {
		$thread_id = $this->get_group_thread_id( $group_id );
		if ( $thread_id ) {
			Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
		}
	}

	/**
	 * User joined the group thread.
	 *
	 * @param int $group_id Group id.
	 *
	 * @return void
	 */
	public function event_groups_join_group( $group_id ) {
		$thread_id = $this->get_group_thread_id( $group_id );
		if ( $thread_id ) {
			Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
		}
	}

	/**
	 * When user left the group thread.
	 *
	 * @param int $group_id Group id.
	 *
	 * @return void
	 */
	public function event_groups_leave_group( $group_id ) {
		$thread_id = $this->get_group_thread_id( $group_id );
		if ( $thread_id ) {
			Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
		}
	}

	/**
	 * When admin removed user from the group thread.
	 *
	 * @param int $group_id Group id.
	 *
	 * @return void
	 */
	public function event_groups_remove_member( $group_id ) {
		$thread_id = $this->get_group_thread_id( $group_id );
		if ( $thread_id ) {
			Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
		}
	}

	/**
	 * Fire when group settings has been updated.
	 *
	 * @param int    $meta_id    ID of updated metadata entry.
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 */
	public function event_updated_group_meta( $meta_id, $object_id, $meta_key ) {
		if ( 'message_status' !== $meta_key ) {
			return;
		}

		$thread_id = $this->get_group_thread_id( $object_id );
		if ( $thread_id ) {
			Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
		}
	}

	/**
	 * When the member send connection request.
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 *
	 * @return void
	 */
	public function event_friends_friendship_requested( $friendship_id, $initiator_user_id, $friend_user_id ) {
		if ( $initiator_user_id ) {
			Cache::instance()->purge_by_user_id( $initiator_user_id, 'bp-messages' );
		}

		if ( $friend_user_id ) {
			Cache::instance()->purge_by_user_id( $friend_user_id, 'bp-messages' );
		}
	}

	/**
	 * When the member withdrawn connection request.
	 *
	 * @param int                   $friendship_id ID of the friendship.
	 * @param BP_Friends_Friendship $friendship    Friendship object. Passed by reference.
	 *
	 * @return void
	 */
	public function event_friends_friendship_withdrawn( $friendship_id, $friendship ) {
		if ( isset( $friendship->initiator_user_id, $friendship->friend_user_id ) ) {
			if ( $friendship->initiator_user_id ) {
				Cache::instance()->purge_by_user_id( $friendship->initiator_user_id, 'bp-messages' );
			}

			if ( $friendship->friend_user_id ) {
				Cache::instance()->purge_by_user_id( $friendship->friend_user_id, 'bp-messages' );
			}
		}
	}

	/**
	 * When the member withdrawn connection request.
	 *
	 * @param int $initiator_user_id ID of the initiator.
	 * @param int $friend_user_id    ID of the friend.
	 *
	 * @return void
	 */
	public function event_friends_friendship_post_delete( $initiator_user_id, $friend_user_id ) {
		if ( $initiator_user_id ) {
			Cache::instance()->purge_by_user_id( $initiator_user_id, 'bp-messages' );
		}

		if ( $friend_user_id ) {
			Cache::instance()->purge_by_user_id( $friend_user_id, 'bp-messages' );
		}
	}

	/**
	 * When the member accepted connection request.
	 *
	 * @param int $friendship_id     ID of the pending friendship connection.
	 * @param int $initiator_user_id ID of the friendship initiator.
	 * @param int $friend_user_id    ID of the friend user.
	 *
	 * @return void
	 */
	public function event_friends_friendship_accepted( $friendship_id, $initiator_user_id, $friend_user_id ) {
		if ( $initiator_user_id ) {
			Cache::instance()->purge_by_user_id( $initiator_user_id, 'bp-messages' );
		}

		if ( $friend_user_id ) {
			Cache::instance()->purge_by_user_id( $friend_user_id, 'bp-messages' );
		}
	}

	/**
	 * Fire when group member promoted.
	 *
	 * @param int $group_id Group id.
	 *
	 * @return void
	 */
	public function event_groups_promote_member( $group_id ) {
		$thread_id = $this->get_group_thread_id( $group_id );
		if ( $thread_id ) {
			Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
		}
	}

	/**
	 * Fire when group member demoted.
	 *
	 * @param int $group_id Group id.
	 *
	 * @return void
	 */
	public function event_groups_demote_member( $group_id ) {
		$thread_id = $this->get_group_thread_id( $group_id );
		if ( $thread_id ) {
			Cache::instance()->purge_by_group( 'bp-messages_' . $thread_id );
		}
	}


	/*********************************** Functions ***********************************/
	/**
	 * Get thread ids from user name.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_thread_ids_by_userid( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_recipients} WHERE user_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get thread id from message id.
	 *
	 * @param int $message_id Message ID.
	 *
	 * @return integer
	 */
	private function get_thread_id( $message_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_messages} WHERE id = %d LIMIT 1", $message_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var( $sql );
	}

	/**
	 * Get thread id from group id.
	 *
	 * @param int $group_id Group id.
	 *
	 * @return int
	 */
	private function get_group_thread_id( $group_id ) {
		global $wpdb;
		$groups     = $wpdb->base_prefix . 'bp_groups';
		$group_meta = $wpdb->base_prefix . 'bp_groups_groupmeta';

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT meta_value from {$group_meta} gm, {$groups} g WHERE g.id = gm.group_id AND gm.meta_key = 'group_message_thread' AND g.id = %d", $group_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( $sql );
	}
}
