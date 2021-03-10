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

			// Added moderation support.
			'bp_suspend_user_suspended',       // Any User Suspended.
			'bp_suspend_user_unsuspended',     // Any User Unsuspended.
			'bp_suspend_message_thread_suspended',       // Any Message Thread Suspended.
			'bp_suspend_message_thread_unsuspended',     // Any Message Thread Unsuspended.
		);

		$this->purge_event( 'bp-messages', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'messages_message_sent'                 => 1, // when new message created.
			'bp_messages_thread_after_delete'       => 2, // when message deleted.
			'messages_delete_thread'                => 1, // when message thread deleted.
			'wp_ajax_messages_hide_thread'          => 1, // when message thread hide.
			'messages_thread_mark_as_read'          => 1, // when messages mark as read.
			'messages_thread_mark_as_unread'        => 1, // when messages mark as unread.
			'updated_message_meta'                  => 1, // when messages meta updated.
			'add_message_meta'                      => 1, // when messages meta added.
			'delete_message_meta'                   => 1, // when messages meta deleted.

			// Added moderation support.
			'bp_suspend_user_suspended'             => 1, // Any User Suspended.
			'bp_suspend_user_unsuspended'           => 1, // Any User Unsuspended.
			'bp_suspend_message_thread_suspended'   => 1, // Any Message Thread Suspended.
			'bp_suspend_message_thread_unsuspended' => 1, // Any Message Thread Unsuspended.

			// Add Author Embed Support.
			'profile_update'                        => 1, // User updated on site.
			'deleted_user'                          => 1, // User deleted on site.
			'xprofile_avatar_uploaded'              => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'        => 1, // User avatar photo deleted.
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
					'unique_id'         => 'id',
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
						Cache::instance()->purge_by_group( 'bp-messages_' . $thread_ids );
					}
				}
			}
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

}
