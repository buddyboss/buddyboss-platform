<?php
/**
 * BuddyBoss Performance Forums Integration.
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
 * Forums Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Forums extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bbp-forums' );

		$event_groups = array( 'bbpress', 'bbpress-forums' );

		$purge_events = array(
			'save_post_forum', // When forum created.
			'edit_post_forum', // When forum updated.
			'trashed_post', // When forum trashed.
			'untrashed_post', // When forum untrashed.
			'deleted_post', // When forum deleted.
			'bbp_new_topic', // When new topic created, update count and last topic id and author id.
			'bbp_edit_topic', // When topic updated, update count and last topic id and author id.
			'bbp_new_reply', // When new reply created, update count and last reply id and author id.
			'bbp_edit_reply', // When reply updated, update count and last reply id and author id.
			'bbp_merged_topic', // When topic merged, update count and last reply id and author id.
			'bbp_post_move_reply', // When reply moved, update count and last reply id and author id.
			'bbp_post_split_topic', // When split topic, update count and last reply id and author id.
			'bbp_add_user_subscription', // When user subscribe forum.
			'bbp_remove_user_subscription', // When user remove forum's subscribe.

			// Added moderation support.
			'bp_suspend_groups_suspended',        // Any Group Suspended.
			'bp_suspend_groups_unsuspended',      // Any Group Unsuspended.
			'bp_suspend_forum_suspended',         // Any Forum Suspended.
			'bp_suspend_forum_unsuspended',       // Any Forum Unsuspended.
			'bp_suspend_forum_topic_suspended',   // Any Forum Topic Suspended.
			'bp_suspend_forum_topic_unsuspended', // Any Forum Topic Unsuspended.
			'bp_suspend_forum_reply_suspended',   // Any Forum Reply Suspended.
			'bp_suspend_forum_reply_unsuspended', // Any Forum Reply Unsuspended.
			'bp_moderation_after_save',           // Update cache for forums when member blocked.
			'bb_moderation_after_delete'          // Update cache for forums when member unblocked.
		);

		$this->purge_event( 'bbp-forums', $purge_events );
		$this->purge_event( 'bbapp-deeplinking', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'save_post_forum'                                    => 1, // When forum created.
			'edit_post_forum'                                    => 1, // When forum updated.
			'trashed_post'                                       => 1, // When forum trashed.
			'untrashed_post'                                     => 1, // When forum untrashed.
			'deleted_post'                                       => 1, // When forum deleted.
			'bbp_add_user_subscription'                          => 2, // When user subscribe forum.
			'bbp_remove_user_subscription'                       => 2, // When user remove forum's subscribe.
			'bbp_new_topic'                                      => 2, // When new topic created, update count and last topic id and author id.
			'bbp_edit_topic'                                     => 2, // When topic updated, update count and last topic id and author id.
			'bbp_new_reply'                                      => 3, // When new reply created, update count and last reply id and author id.
			'bbp_edit_reply'                                     => 3, // When reply updated, update count and last reply id and author id.
			'bbp_merged_topic'                                   => 3, // When topic merged, update count and last reply id and author id.
			'bbp_post_move_reply'                                => 3, // When reply moved, update count and last reply id and author id.
			'bbp_post_split_topic'                               => 3, // When split topic, update count and last reply id and author id.

			// Group Embed data.
			'bp_group_admin_edit_after'                          => 1, // When forum Group change form admin.
			'groups_group_details_edited'                        => 1, // When forum Group Details updated form Manage.
			'groups_group_settings_edited'                       => 1, // When forum Group setting updated form Manage.
			'bp_group_admin_after_edit_screen_save'              => 1, // When Group forums setting Manage.
			'groups_avatar_uploaded'                             => 1, // When forum Group avarar updated form Manage.
			'groups_cover_image_uploaded'                        => 1, // When forum Group cover photo uploaded form Manage.
			'groups_cover_image_deleted'                         => 1, // When forum Group cover photo deleted form Manage.

			// Added moderation support.
			'bp_suspend_groups_suspended'                        => 1, // Any Group Suspended.
			'bp_suspend_groups_unsuspended'                      => 1, // Any Group Unsuspended.
			'bp_suspend_forum_suspended'                         => 1, // Any Forum Suspended.
			'bp_suspend_forum_unsuspended'                       => 1, // Any Forum Unsuspended.
			'bp_suspend_forum_topic_suspended'                   => 1, // Any Forum Topic Suspended.
			'bp_suspend_forum_topic_unsuspended'                 => 1, // Any Forum Topic Unsuspended.
			'bp_suspend_forum_reply_suspended'                   => 1, // Any Forum Reply Suspended.
			'bp_suspend_forum_reply_unsuspended'                 => 1, // Any Forum Reply Unsuspended.
			'bp_moderation_after_save'                           => 1, // Update cache for forums when member blocked.
			'bb_moderation_after_delete'                         => 1, // Update cache for forums when member unblocked.

			// Add Author Embed Support.
			'profile_update'                                     => 1, // User updated on site.
			'deleted_user'                                       => 1, // User deleted on site.
			'xprofile_avatar_uploaded'                           => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'                     => 1, // User avatar photo deleted.

			// When change/update the group avatar and cover options.
			'update_option_bp-disable-group-avatar-uploads'      => 3,
			'update_option_bp-default-group-avatar-type'         => 3,
			'update_option_bp-default-custom-group-avatar'       => 3,
			'update_option_bp-disable-group-cover-image-uploads' => 3,
			'update_option_bp-default-group-cover-type'          => 3,
			'update_option_bp-default-custom-group-cover'        => 3,
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_forum_discussions', 'buddyboss-app' );
		$cache_bb_forums     = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_forums ) {

			$this->cache_endpoint(
				'buddyboss/v1/forums',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id' => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/forums/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/******************************** Forum Events ********************************/
	/**
	 * When forum created
	 *
	 * @param int $forum_id Forum post ID.
	 */
	public function event_save_post_forum( $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * When forum updated
	 *
	 * @param int $forum_id Forum post ID.
	 */
	public function event_edit_post_forum( $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * When forum trashed
	 *
	 * @param int $forum_id Forum post ID.
	 */
	public function event_trashed_post( $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * When forum untrashed
	 *
	 * @param int $forum_id Forum post ID.
	 */
	public function event_untrashed_post( $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * When forum deleted
	 *
	 * @param int $forum_id Forum post ID.
	 */
	public function event_deleted_post( $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * When user subscribe forum
	 *
	 * @param int $user_id  User ID.
	 * @param int $forum_id Forum post ID.
	 */
	public function event_bbp_add_user_subscription( $user_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * When user remove forums subscribe
	 *
	 * @param int $user_id  User ID.
	 * @param int $forum_id Forum post ID.
	 */
	public function event_bbp_remove_user_subscription( $user_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * When new topic created, update count and last topic id and author id
	 *
	 * @param int $topic_id Topic post ID.
	 * @param int $forum_id Forum post ID.
	 */
	public function event_bbp_new_topic( $topic_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * When topic updated, update count and last topic id and author id
	 *
	 * @param int $topic_id Topic post ID.
	 * @param int $forum_id Forum post ID.
	 */
	public function event_bbp_edit_topic( $topic_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * When new reply created, update count and last reply id and author id
	 *
	 * @param int $reply_id Reply post ID.
	 * @param int $topic_id Topic post ID.
	 * @param int $forum_id Forum post ID.
	 */
	public function event_bbp_new_reply( $reply_id, $topic_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * When reply updated, update count and last reply id and author id
	 *
	 * @param int $reply_id Reply post ID.
	 * @param int $topic_id Topic post ID.
	 * @param int $forum_id Forum post ID.
	 */
	public function event_bbp_edit_reply( $reply_id, $topic_id, $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * When topic merged, update count and last reply id and author id
	 *
	 * @param int $destination_topic_id  Destination Topic ID.
	 * @param int $source_topic_id       Source Topic ID.
	 * @param int $source_topic_forum_id Source Topic Forum ID.
	 */
	public function event_bbp_merged_topic( $destination_topic_id, $source_topic_id, $source_topic_forum_id ) {
		$this->purge_item_cache_by_item_id( $source_topic_forum_id );
		$this->purge_subscription_cache_by_items( $source_topic_forum_id );
	}

	/**
	 * When reply moved, update count and last reply id and author id
	 *
	 * @param int $move_reply_id        Move Reply ID.
	 * @param int $source_topic_id      Source Topic ID.
	 * @param int $destination_topic_id Destination Topic ID.
	 */
	public function event_bbp_post_move_reply( $move_reply_id, $source_topic_id, $destination_topic_id ) {
		$destination_forum_id = bbp_get_topic_forum_id( $destination_topic_id );
		$this->purge_item_cache_by_item_id( $destination_forum_id );
		$this->purge_subscription_cache_by_items( $destination_forum_id );
	}

	/**
	 * When split topic update count and last reply id and author id
	 *
	 * @param int $from_reply_id        From Reply ID.
	 * @param int $source_topic_id      Source Topic ID.
	 * @param int $destination_topic_id Destination Topic ID.
	 */
	public function event_bbp_post_split_topic( $from_reply_id, $source_topic_id, $destination_topic_id ) {
		$destination_forum_id = bbp_get_topic_forum_id( $destination_topic_id );
		$this->purge_item_cache_by_item_id( $destination_forum_id );
		$this->purge_subscription_cache_by_items( $destination_forum_id );
	}

	/****************************** Group Embed Support *****************************/
	/**
	 * When forum Group change form admin
	 *
	 * @param int $group_id Group id.
	 */
	public function event_bp_group_admin_edit_after( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}
		}

	}

	/**
	 * When forum Group Details updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_details_edited( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}
		}
	}

	/**
	 * When forum Group setting updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_settings_edited( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}
		}
	}

	/**
	 * When Group forums setting Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_bp_group_admin_after_edit_screen_save( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}
		}
	}

	/**
	 * When forum Group avarar updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_avatar_uploaded( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}
		}
	}

	/**
	 * When forum Group cover photo uploaded form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_cover_image_uploaded( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}
		}
	}

	/**
	 * When forum Group cover photo deleted form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_cover_image_deleted( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}
		}
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Group ID.
	 *
	 * @param int $group_id Group ID.
	 */
	public function event_bp_suspend_groups_suspended( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}

			$this->purge_subscription_cache_by_items( $forum_ids );
		}
	}

	/**
	 * Unsuspended Group ID.
	 *
	 * @param int $group_id Group ID.
	 */
	public function event_bp_suspend_groups_unsuspended( $group_id ) {
		$forum_ids = bbp_get_group_forum_ids( $group_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}

			$this->purge_subscription_cache_by_items( $forum_ids );
		}
	}

	/**
	 * Suspended Forum ID.
	 *
	 * @param int $forum_id Forum ID.
	 */
	public function event_bp_suspend_forum_suspended( $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );

		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * Unsuspended Forum ID.
	 *
	 * @param int $forum_id Forum ID.
	 */
	public function event_bp_suspend_forum_unsuspended( $forum_id ) {
		$this->purge_item_cache_by_item_id( $forum_id );

		$this->purge_subscription_cache_by_items( $forum_id );
	}

	/**
	 * Suspended Forum Topic ID.
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bp_suspend_forum_topic_suspended( $topic_id ) {
		$forum_id = bbp_get_topic_forum_id( $topic_id );
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * Unsuspended Forum Topic ID.
	 *
	 * @param int $topic_id Topic ID.
	 */
	public function event_bp_suspend_forum_topic_unsuspended( $topic_id ) {
		$forum_id = bbp_get_topic_forum_id( $topic_id );
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * Suspended Forum Reply ID.
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bp_suspend_forum_reply_suspended( $reply_id ) {
		$forum_id = bbp_get_reply_forum_id( $reply_id );
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * Unsuspended Forum Reply ID.
	 *
	 * @param int $reply_id Reply ID.
	 */
	public function event_bp_suspend_forum_reply_unsuspended( $reply_id ) {
		$forum_id = bbp_get_reply_forum_id( $reply_id );
		$this->purge_item_cache_by_item_id( $forum_id );
	}

	/**
	 * Update cache for forums when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$forum_ids = $this->get_forum_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}

			$this->purge_subscription_cache_by_items( $forum_ids );
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
		$forum_ids = $this->get_forum_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}

			$this->purge_subscription_cache_by_items( $forum_ids );
		}
	}

	/****************************** Author Embed Support *****************************/
	/**
	 * User updated on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_profile_update( $user_id ) {
		$forum_ids = $this->get_forum_ids_by_userid( $user_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}

			$this->purge_subscription_cache_by_items( $forum_ids );
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$forum_ids = $this->get_forum_ids_by_userid( $user_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}

			$this->purge_subscription_cache_by_items( $forum_ids );
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$forum_ids = $this->get_forum_ids_by_userid( $user_id );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$this->purge_item_cache_by_item_id( $forum_id );
			}

			$this->purge_subscription_cache_by_items( $forum_ids );
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
				$forum_ids = $this->get_forum_ids_by_userid( $user_id );
				if ( ! empty( $forum_ids ) ) {
					foreach ( $forum_ids as $forum_id ) {
						$this->purge_item_cache_by_item_id( $forum_id );
					}

					$this->purge_subscription_cache_by_items( $forum_ids );
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
	private function get_forum_ids_by_userid( $user_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type='forum' AND post_author = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Purge item cache by item id.
	 *
	 * @param $forum_id
	 */
	private function purge_item_cache_by_item_id( $forum_id ) {
		Cache::instance()->purge_by_group( 'bbp-forums_' . $forum_id );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking_' . untrailingslashit( get_permalink( $forum_id ) ) );
	}

	/**
	 * When Group Avatars option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_disable_group_avatar_uploads( $old_value, $value, $option ) {
		Cache::instance()->purge_by_component( 'bbp-forums' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
	}

	/**
	 * When Default Group Avatar option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_group_avatar_type( $old_value, $value, $option ) {
		Cache::instance()->purge_by_component( 'bbp-forums' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
	}

	/**
	 * When Upload Custom Avatar option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_custom_group_avatar( $old_value, $value, $option ) {
		Cache::instance()->purge_by_component( 'bbp-forums' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
	}

	/**
	 * When Group Cover Images option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_disable_group_cover_image_uploads( $old_value, $value, $option ) {
		Cache::instance()->purge_by_component( 'bbp-forums' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
	}

	/**
	 * When Default Group Cover Image option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_group_cover_type( $old_value, $value, $option ) {
		Cache::instance()->purge_by_component( 'bbp-forums' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
	}

	/**
	 * When Upload Custom Cover Image option changed.
	 *
	 * @param string $option    Name of the updated option.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 */
	public function event_update_option_bp_default_custom_group_cover( $old_value, $value, $option ) {
		Cache::instance()->purge_by_component( 'bbp-forums' );
		Cache::instance()->purge_by_group( 'bbapp-deeplinking' );
	}

	/**
	 * Purge items subscription cache.
	 *
	 * @param int|array $forum_ids Forum ids.
	 */
	private function purge_subscription_cache_by_items( $forum_ids ) {
		if ( empty( $forum_ids ) ) {
			return;
		}

		// Create an array if is not array.
		$forum_ids = array_filter( wp_parse_id_list( $forum_ids ) );

		if ( empty( $forum_ids ) ) {
			return;
		}

		$args = array(
			'user_id'       => false,
			'type'          => 'topic',
			'include_items' => $forum_ids,
			'fields'        => 'id',
			'status'        => null,
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
