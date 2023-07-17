<?php
/**
 * BuddyBoss Performance Media Photos Integration.
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
 * Media Photos Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Media_Photos extends Integration_Abstract {

	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-media-photos' );

		$purge_events = array(
			'bp_media_add',              // Any Media Photo add.
			'bp_media_after_save',       // Any Media Photo updated.
			'bp_media_deleted_medias',   // Any Media Photos deleted.

			// Added moderation support.
			'bp_suspend_media_suspended',   // Any Media Suspended.
			'bp_suspend_media_unsuspended', // Any Media Unsuspended.
			'bp_moderation_after_save',     // Hide media when member blocked.
			'bb_moderation_after_delete'    // Unhide media when member unblocked.
		);

		$this->purge_event( 'bp-media-photos', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bp_media_add'                   => 1, // Any Media Photo add.
			'bp_media_after_save'            => 1, // Any Media Photo updated.
			'bp_media_deleted_medias'        => 1, // Any Media Photos deleted.

			// Media group information update support.
			'groups_update_group'            => 1,   // When Group Details updated.
			'groups_group_after_save'        => 1,   // When Group Details save.
			'groups_group_details_edited'    => 1,   // When Group Details updated form Manage.

			// Added moderation support.
			'bp_suspend_media_suspended'     => 1, // Any Media Suspended.
			'bp_suspend_media_unsuspended'   => 1, // Any Media Unsuspended.
			'bp_moderation_after_save'       => 1, // Hide media when member blocked.
			'bb_moderation_after_delete'     => 1, // Unhide media when member unblocked.

			// Add Author Embed Support.
			'profile_update'                 => 1, // User updated on site.
			'deleted_user'                   => 1, // User deleted on site.
			'xprofile_avatar_uploaded'       => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar' => 1, // User avatar photo deleted.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_media', 'buddyboss-app' );
		$cache_bb_media      = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_media ) {

			$this->cache_endpoint(
				'buddyboss/v1/media',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/media/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/****************************** Media Events *****************************/
	/**
	 * Any Media Photos add
	 *
	 * @param BP_Media $media Media object.
	 */
	public function event_bp_media_add( $media ) {
		if ( ! empty( $media->id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-photos' . $media->id );
		}
	}

	/**
	 * Any Media Photos after save
	 *
	 * @param BP_Media $media Current instance of media item being saved. Passed by reference.
	 */
	public function event_bp_media_after_save( $media ) {
		if ( ! empty( $media->id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-photos_' . $media->id );
		}
	}

	/**
	 * Any Media Photos deleted
	 *
	 * @param array $media_ids_deleted Array of affected media item IDs.
	 */
	public function event_bp_media_deleted_medias( $media_ids_deleted ) {
		if ( ! empty( $media_ids_deleted ) ) {
			foreach ( $media_ids_deleted as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
			}
		}
	}

	/****************************** Group Embed Support *****************************/
	/**
	 * When Group Details updated.
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_update_group( $group_id ) {
		$media_ids = $this->get_media_ids_by_group_id( $group_id );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
			}
		}
	}

	/**
	 * Fires after the current group item has been saved.
	 *
	 * @param BP_Groups_Group $group Current instance of the group item that was saved. Passed by reference.
	 */
	public function event_groups_group_after_save( $group ) {
		if ( ! empty( $group->id ) ) {
			$media_ids = $this->get_media_ids_by_group_id( $group->id );
			if ( ! empty( $media_ids ) ) {
				foreach ( $media_ids as $media_id ) {
					Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
				}
			}
		}
	}

	/**
	 * When Group Details updated form Manage
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_group_details_edited( $group_id ) {
		$media_ids = $this->get_media_ids_by_group_id( $group_id );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
			}
		}
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Media ID.
	 *
	 * @param int $media_id Media ID.
	 */
	public function event_bp_suspend_media_suspended( $media_id ) {
		Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
	}

	/**
	 * Unsuspended Media ID.
	 *
	 * @param int $media_id Media ID.
	 */
	public function event_bp_suspend_media_unsuspended( $media_id ) {
		Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
	}

	/**
	 * Update cache for media when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$media_ids = $this->get_media_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
			}
		}
	}

	/**
	 * Update cache for media when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$media_ids = $this->get_media_ids_by_userid( $bp_moderation->item_id );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
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
		$media_ids = $this->get_media_ids_by_userid( $user_id );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$media_ids = $this->get_media_ids_by_userid( $user_id );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$media_ids = $this->get_media_ids_by_userid( $user_id );
		if ( ! empty( $media_ids ) ) {
			foreach ( $media_ids as $media_id ) {
				Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
			}
		}
	}

	/**
	 * User avatar photo deleted
	 *
	 * @param array $args Arguments array.
	 */
	public function event_bp_core_delete_existing_avatar( $args ) {
		$user_id = ! empty( $args['item_id'] ) ? absint( $args['item_id'] ) : 0;
		if ( ! empty( $user_id ) ) {
			if ( isset( $args['object'] ) && 'user' === $args['object'] ) {
				$media_ids = $this->get_media_ids_by_userid( $user_id );
				if ( ! empty( $media_ids ) ) {
					foreach ( $media_ids as $media_id ) {
						Cache::instance()->purge_by_group( 'bp-media-photos_' . $media_id );
					}
				}
			}
		}
	}

	/*********************************** Functions ***********************************/
	/**
	 * Get Media ids from user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_media_ids_by_userid( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->media->table_name} WHERE user_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get Media Ids .
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array
	 */
	private function get_media_ids_by_group_id( $group_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->media->table_name} WHERE group_id = %d", $group_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}
}
