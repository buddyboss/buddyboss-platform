<?php
/**
 * BuddyBoss Performance Topic Integration.
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
 * Topic Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Topics extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bbp-topics' );

		$purge_events = array(
			'save_post_topic', // When topic created.
			'edit_post_topic', // When topic updated.
			'trashed_post', // When topic trashed.
			'untrashed_post', // When topic untrashed.
			'deleted_post', // When topic deleted.
			'bbp_merged_topic', // When topic merged as reply.
			'bbp_post_split_topic', // When topic split.

			// Added moderation support.
			'bp_suspend_forum_topic_suspended',     // Any Forum Topic Suspended.
			'bp_suspend_forum_topic_unsuspended',   // Any Forum Topic Unsuspended.
			'bp_suspend_forum_reply_suspended',     // Any Forum Reply Suspended.
			'bp_suspend_forum_reply_unsuspended',   // Any Forum Reply Unsuspended.
			'bp_moderation_after_save',             // Update cache for topics when member blocked.
			'bb_moderation_after_delete'            // Update cache for topics when member unblocked.
		);

		$this->purge_event( 'bbp-topics', $purge_events );
		$this->purge_event( 'bbapp-deeplinking', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'save_post_topic'                    => 1, // When topic created.
			'edit_post_topic'                    => 1, // When topic updated.
			'trashed_post'                       => 1, // When topic trashed.
			'untrashed_post'                     => 1, // When topic untrashed.
			'deleted_post'                       => 1, // When topic deleted.
			'bbp_add_user_subscription'          => 2, // When topic subscription added.
			'bbp_remove_user_subscription'       => 2, // When topic subscription removed.
			'bbp_add_user_favorite'              => 2, // When topic favorite added.
			'bbp_remove_user_favorite'           => 2, // When topic favorite removed.
			'bbp_opened_topic'                   => 1, // When topic opened.
			'bbp_closed_topic'                   => 1, // When topic closed.
			'bbp_spammed_topic'                  => 1, // When topic spammed.
			'bbp_unspammed_topic'                => 1, // When topic unspammed.
			'bbp_stick_topic'                    => 1, // When topic stick.
			'bbp_unstick_topic'                  => 1, // When topic unstick.
			'bbp_approved_topic'                 => 1, // When topic approved.
			'bbp_unapproved_topic'               => 1, // When topic unapproved.
			'bbp_merged_topic'                   => 3, // When topic merged as reply.
			'bbp_post_split_topic'               => 3, // When topic split.
			'bbp_new_reply'                      => 3, // When new reply created, update count and last reply id and author id.
			'bbp_edit_reply'                     => 3, // When reply updated, update count and last reply id and author id.
			'bbp_post_move_reply'                => 3, // When reply moved, update count and last reply id and author id.

			// Added moderation support.
			'bp_suspend_forum_topic_suspended'   => 1, // Any Forum Topic Suspended.
			'bp_suspend_forum_topic_unsuspended' => 1, // Any Forum Topic Unsuspended.
			'bp_suspend_forum_reply_suspended'   => 1, // Any Forum Reply Suspended.
			'bp_suspend_forum_reply_unsuspended' => 1, // Any Forum Reply Unsuspended.
			'bp_moderation_after_save'           => 1, // Update cache for topics when member blocked.
			'bb_moderation_after_delete'         => 1, // Update cache for topics when member unblocked.

			// Add Author Embed Support.
			'profile_update'                     => 1, // User updated on site.
			'deleted_user'                       => 1, // User deleted on site.
			'xprofile_avatar_uploaded'           => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'     => 1, // User avatar photo deleted.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_forum_discussions', 'buddyboss-app' );
		$cache_bb_topics     = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_topics ) {

			$this->cache_endpoint(
				'buddyboss/v1/topics',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
					'purge_deep_events' => array_keys( $purge_single_events ),
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/topics/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/****************************** Topic Events *****************************/
	/**
	 * When topic created
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_save_post_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic updated
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_edit_post_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic trashed
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_trashed_post( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic untrashed
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_untrashed_post( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic deleted
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_deleted_post( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic subscription added
	 *
	 * @param int $user_id  User ID.
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_add_user_subscription( $user_id, $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic subscription removed
	 *
	 * @param int $user_id  User ID.
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_remove_user_subscription( $user_id, $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic favorite added
	 *
	 * @param int $user_id  User ID.
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_add_user_favorite( $user_id, $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * When topic favorite removed
	 *
	 * @param int $user_id  User ID.
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_remove_user_favorite( $user_id, $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * When topic opened
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_opened_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic closed
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_closed_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic spammed
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_spammed_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic unspammed
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_unspammed_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic stick
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_stick_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * When topic unstick
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_unstick_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * When topic approved
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_approved_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic unapproved
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bbp_unapproved_topic( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * When topic merged as reply
	 *
	 * @param int $destination_topic_id  Destination ID.
	 * @param int $source_topic_id       Source Topic ID.
	 * @param int $source_topic_forum_id Source Forum ID.
	 */
	public function event_bbp_merged_topic( $destination_topic_id, $source_topic_id, $source_topic_forum_id ) {
		$this->purge_item_cache_by_item_id( $destination_topic_id );
		$this->purge_item_cache_by_item_id( $source_topic_id );
		$this->purge_subscription_cache_by_items( $destination_topic_id );
		$this->purge_subscription_cache_by_items( $source_topic_id );
	}

	/**
	 * When topic split
	 *
	 * @param int $from_reply_id        Reply ID.
	 * @param int $source_topic_id      Source Topic ID.
	 * @param int $destination_topic_id Desination Topic ID.
	 */
	public function event_bbp_post_split_topic( $from_reply_id, $source_topic_id, $destination_topic_id ) {
		$this->purge_item_cache_by_item_id( $destination_topic_id );
		$this->purge_item_cache_by_item_id( $source_topic_id );
		$this->purge_subscription_cache_by_items( $destination_topic_id );
		$this->purge_subscription_cache_by_items( $source_topic_id );
	}

	/**
	 * When new reply created, update count and last reply id and author id
	 *
	 * @param int $reply_id Reply ID.
	 * @param int $topic_id Topic ID.
	 * @param int $forum_id Forum ID.
	 */
	public function event_bbp_new_reply( $reply_id, $topic_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * When reply updated, update count and last reply id and author id
	 *
	 * @param int $reply_id Reply ID.
	 * @param int $topic_id Topic ID.
	 * @param int $forum_id Forum ID.
	 */
	public function event_bbp_edit_reply( $reply_id, $topic_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * When reply moved, update count and last reply id and author id
	 *
	 * @param int $move_reply_id        Moved Reply ID.
	 * @param int $source_topic_id      Source Topic ID.
	 * @param int $destination_topic_id Desination Topic ID.
	 */
	public function event_bbp_post_move_reply( $move_reply_id, $source_topic_id, $destination_topic_id ) {
		$this->purge_item_cache_by_item_id( $destination_topic_id );
		$this->purge_item_cache_by_item_id( $source_topic_id );
		$this->purge_subscription_cache_by_items( $destination_topic_id );
		$this->purge_subscription_cache_by_items( $source_topic_id );
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Forum Topic ID.
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bp_suspend_forum_topic_suspended( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );

		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * Unsuspended Forum Topic ID.
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bp_suspend_forum_topic_unsuspended( $topic_id ) {
		$this->purge_item_cache_by_item_id( $topic_id );

		$this->purge_subscription_cache_by_items( $topic_id );
	}

	/**
	 * Suspended Forum Reply ID.
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bp_suspend_forum_reply_suspended( $reply_id ) {
		$topic_id = bbp_get_reply_topic_id( $reply_id );
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * Unsuspended Forum Reply ID.
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bp_suspend_forum_reply_unsuspended( $reply_id ) {
		$topic_id = bbp_get_reply_topic_id( $reply_id );
		$this->purge_item_cache_by_item_id( $topic_id );
	}

	/**
	 * Update cache for topics when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$topic_ids = $this->get_topic_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $topic_ids ) ) {
			foreach ( $topic_ids as $topic_id ) {
				$this->purge_item_cache_by_item_id( $topic_id );
			}

			$this->purge_subscription_cache_by_items( $topic_ids );
		}
	}

	/**
	 * Update cache for topics when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$topic_ids = $this->get_topic_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $topic_ids ) ) {
			foreach ( $topic_ids as $topic_id ) {
				$this->purge_item_cache_by_item_id( $topic_id );
			}

			$this->purge_subscription_cache_by_items( $topic_ids );
		}
	}

	/****************************** Author Embed Support *****************************/
	/**
	 * User updated on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_profile_update( $user_id ) {
		$topic_ids = $this->get_topic_ids_by_userid( $user_id );
		if ( ! empty( $topic_ids ) ) {
			foreach ( $topic_ids as $topic_id ) {
				$this->purge_item_cache_by_item_id( $topic_id );
			}

			$this->purge_subscription_cache_by_items( $topic_ids );
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$topic_ids = $this->get_topic_ids_by_userid( $user_id );
		if ( ! empty( $topic_ids ) ) {
			foreach ( $topic_ids as $topic_id ) {
				$this->purge_item_cache_by_item_id( $topic_id );
			}

			$this->purge_subscription_cache_by_items( $topic_ids );
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$topic_ids = $this->get_topic_ids_by_userid( $user_id );
		if ( ! empty( $topic_ids ) ) {
			foreach ( $topic_ids as $topic_id ) {
				$this->purge_item_cache_by_item_id( $topic_id );
			}

			$this->purge_subscription_cache_by_items( $topic_ids );
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
				$topic_ids = $this->get_topic_ids_by_userid( $user_id );
				if ( ! empty( $topic_ids ) ) {
					foreach ( $topic_ids as $topic_id ) {
						$this->purge_item_cache_by_item_id( $topic_id );
					}

					$this->purge_subscription_cache_by_items( $topic_ids );
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
	private function get_topic_ids_by_userid( $user_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type='topic' AND post_author = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Purge item cache by item id.
	 *
	 * @param $topic_id
	 */
	private function purge_item_cache_by_item_id( $topic_id ) {
		Cache::instance()->purge_by_group( 'bbp-topics_' . $topic_id );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( get_permalink( $topic_id ) ) );
	}

	/**
	 * Purge items subscription cache.
	 *
	 * @param int|array $topic_ids Topic ids.
	 */
	private function purge_subscription_cache_by_items( $topic_ids ) {
		if ( empty( $topic_ids ) ) {
			return;
		}

		// Create an array if is not array.
		$topic_ids = array_filter( wp_parse_id_list( $topic_ids ) );

		if ( empty( $topic_ids ) ) {
			return;
		}

		$args = array(
			'user_id' => false,
			'type'    => 'topic',
			'fields'  => 'id',
			'status'  => null,
		);

		$args['include_items'] = ( is_array( $topic_ids ) ? $topic_ids : array( $topic_ids ) );

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
