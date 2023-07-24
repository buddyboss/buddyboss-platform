<?php
/**
 * BuddyBoss Performance Replies Integration.
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
 * Replies Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Replies extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bbp-replies' );

		$purge_events = array(
			'save_post_reply', // When reply created.
			'edit_post_reply', // When reply updated.
			'trashed_post', // When reply trashed.
			'untrashed_post', // When reply untrashed.
			'deleted_post', // When reply deleted.
			'bbp_post_move_reply', // When reply moved.
			'bbp_post_split_topic', // When reply split as topic.
			'bbp_new_reply', // When new reply created update count and last reply id and author id.

			// Added moderation support.
			'bp_suspend_forum_reply_suspended',   // Any Forum Reply Suspended.
			'bp_suspend_forum_reply_unsuspended', // Any Forum Reply Unsuspended.
			'bp_moderation_after_save',           // Hide forum reply when member blocked.
			'bb_moderation_after_delete'          // Unhide forum reply when member unblocked.
		);

		$this->purge_event( 'bbp-replies', $purge_events );
		$this->purge_event( 'bbapp-deeplinking', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'save_post_reply'                    => 1, // When reply created.
			'edit_post_reply'                    => 1, // When reply updated.
			'trashed_post'                       => 1, // When reply trashed.
			'untrashed_post'                     => 1, // When reply untrashed.
			'deleted_post'                       => 1, // When reply deleted.
			'bbp_spammed_reply'                  => 1, // When reply spammed.
			'bbp_unspammed_reply'                => 1, // When reply unspammed.
			'bbp_approved_reply'                 => 1, // When reply approved.
			'bbp_unapproved_reply'               => 1, // When reply unapproved.
			'bbp_post_move_reply'                => 3, // When reply moved.
			'bbp_post_split_topic'               => 3, // When reply split as topic.
			'bbp_new_reply'                      => 3, // When new reply created update count and last reply id and author id.

			// Added moderation support.
			'bp_suspend_forum_reply_suspended'   => 1, // Any Forum Reply Suspended.
			'bp_suspend_forum_reply_unsuspended' => 1, // Any Forum Reply Unsuspended.
			'bp_moderation_after_save'           => 1, // Any Forum Reply Suspended.
			'bb_moderation_after_delete'         => 1, // Any Forum Reply Unsuspended.

			// Add Author Embed Support.
			'profile_update'                     => 1, // User updated on site.
			'deleted_user'                       => 1, // User deleted on site.
			'xprofile_avatar_uploaded'           => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'     => 1, // User avatar photo deleted.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_forum_discussions', 'buddyboss-app' );
		$cache_bb_replies    = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_replies ) {

			$this->cache_endpoint(
				'buddyboss/v1/reply',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/reply/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/****************************** Reply Events *****************************/
	/**
	 * When reply created
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_save_post_reply( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply updated
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_edit_post_reply( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply trashed
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_trashed_post( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply untrashed
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_untrashed_post( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply deleted
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_deleted_post( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply spammed
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bbp_spammed_reply( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply unspammed
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bbp_unspammed_reply( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply approved
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bbp_approved_reply( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply unapproved
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bbp_unapproved_reply( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply moved
	 *
	 * @param int $reply_id             Reply ID.
	 * @param int $source_topic_id      Source Topic ID.
	 * @param int $destination_topic_id Destination Topic ID.
	 */
	public function event_bbp_post_move_reply( $reply_id, $source_topic_id, $destination_topic_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When reply split as topic
	 *
	 * @param int $reply_id             Reply ID.
	 * @param int $source_topic_id      Source Topic ID.
	 * @param int $destination_topic_id Destination Topic ID.
	 */
	public function event_bbp_post_split_topic( $reply_id, $source_topic_id, $destination_topic_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * When new reply created update count and last reply id and author id
	 *
	 * @param int $reply_id Reply ID.
	 * @param int $topic_id Topic ID.
	 * @param int $forum_id Forum ID.
	 */
	public function event_bbp_new_reply( $reply_id, $topic_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Forum Reply ID.
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bp_suspend_forum_reply_suspended( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * Unsuspended Forum Reply ID.
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bp_suspend_forum_reply_unsuspended( $reply_id ) {
		$this->purge_item_cache_by_item_id( $reply_id );
	}

	/**
	 * Update cache for replies when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$replies_ids = $this->get_reply_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $replies_ids ) ) {
			foreach ( $replies_ids as $reply_id ) {
				$this->purge_item_cache_by_item_id( $reply_id );
			}
		}
	}

	/**
	 * Update cache for replies when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$replies_ids = $this->get_reply_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $replies_ids ) ) {
			foreach ( $replies_ids as $reply_id ) {
				$this->purge_item_cache_by_item_id( $reply_id );
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
		$replies_ids = $this->get_reply_ids_by_userid( $user_id );
		if ( ! empty( $replies_ids ) ) {
			foreach ( $replies_ids as $reply_id ) {
				$this->purge_item_cache_by_item_id( $reply_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$replies_ids = $this->get_reply_ids_by_userid( $user_id );
		if ( ! empty( $replies_ids ) ) {
			foreach ( $replies_ids as $reply_id ) {
				$this->purge_item_cache_by_item_id( $reply_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$replies_ids = $this->get_reply_ids_by_userid( $user_id );
		if ( ! empty( $replies_ids ) ) {
			foreach ( $replies_ids as $reply_id ) {
				$this->purge_item_cache_by_item_id( $reply_id );
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
				$replies_ids = $this->get_reply_ids_by_userid( $user_id );
				if ( ! empty( $replies_ids ) ) {
					foreach ( $replies_ids as $reply_id ) {
						$this->purge_item_cache_by_item_id( $reply_id );
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
	private function get_reply_ids_by_userid( $user_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type='reply' AND post_author = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Purge item cache by item id.
	 *
	 * @param $reply_id
	 */
	private function purge_item_cache_by_item_id( $reply_id ) {
		Cache::instance()->purge_by_group( 'bbp-replies_' . $reply_id );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( get_permalink( $reply_id ) ) );
	}
}
