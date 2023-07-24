<?php
/**
 * BuddyBoss Performance Media ALbums Integration.
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
 * Media ALbums Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Media_Albums extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-media-albums' );

		$purge_events = array(
			'bp_media_album_after_save',   // Any Media Album add.
			'bp_media_album_after_delete', // Any Media Album deleted.
			'bp_video_album_after_save',   // Any Video Album add.
			'bp_video_album_after_delete', // Any Video Album deleted.

			// Added moderation support.
			'bp_suspend_media_suspended',         // Any Media Suspended.
			'bp_suspend_media_unsuspended',       // Any Media Unsuspended.
			'bp_suspend_media_album_suspended',   // Any Media Album Suspended.
			'bp_suspend_media_album_unsuspended', // Any Media Album Unsuspended.
			'bp_moderation_after_save',           // Hide media album when member blocked.
			'bb_moderation_after_delete'          // Unhide media album when member unblocked.
		);

		$this->purge_event( 'bp-media-albums', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bp_media_album_after_save'          => 1, // Any Media Album add.
			'bp_media_album_after_delete'        => 1, // Any Media Album deleted.
			'bp_video_album_after_save'          => 1, // Any Video Album add.
			'bp_video_album_after_delete'        => 1, // Any Video Album deleted.

			'bp_media_add'                       => 1, // Any Media Photo add.
			'bp_media_after_save'                => 1, // Any Media Photo updated.
			'bp_media_before_delete'             => 1, // Any Media Photos deleted.

			'bp_video_add'                       => 1, // Any Video File add.
			'bp_video_after_save'                => 1, // Any Video File updated.
			'bp_video_before_delete'             => 1, // Any Video File deleted.

			// Media group information update support.
			'groups_update_group'                => 1,   // When Group Details updated.
			'groups_group_after_save'            => 1,   // When Group Details save.
			'groups_group_details_edited'        => 1,   // When Group Details updated form Manage.

			// Added moderation support.
			'bp_suspend_media_suspended'         => 1, // Any Media Suspended.
			'bp_suspend_media_unsuspended'       => 1, // Any Media Unsuspended.
			'bp_suspend_media_album_suspended'   => 1, // Any Media Album Suspended.
			'bp_suspend_media_album_unsuspended' => 1, // Any Media Album Unsuspended.
			'bp_moderation_after_save'           => 1, // Hide media album when member blocked.
			'bb_moderation_after_delete'         => 1, // Unhide media album when member unblocked.

			// Add Author Embed Support.
			'profile_update'                     => 1, // User updated on site.
			'deleted_user'                       => 1, // User deleted on site.
			'xprofile_avatar_uploaded'           => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'     => 1, // User avatar photo deleted.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_media', 'buddyboss-app' );
		$cache_bb_media      = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_media ) {

			$this->cache_endpoint(
				'buddyboss/v1/media/albums',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => 'id',
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/media/albums/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(),
				false
			);
		}
	}

	/****************************** Media Album Events *****************************/
	/**
	 * Any Media Album.
	 *
	 * @param BP_Media_Album $album Media Album object.
	 */
	public function event_bp_media_album_after_save( $album ) {
		if ( ! empty( $album->id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-albums_' . $album->id );
		}
	}

	/**
	 * Any Media Album.
	 *
	 * @param array $albums Array of media albums.
	 */
	public function event_bp_media_album_after_delete( $albums ) {
		if ( ! empty( $albums ) ) {
			foreach ( $albums as $album ) {
				if ( ! empty( $album->id ) ) {
					Cache::instance()->purge_by_group( 'bp-media-albums_' . $album->id );
				}
			}
		}
	}

	/**
	 * Any Media Photos add
	 *
	 * @param BP_Media $media Media object.
	 */
	public function event_bp_media_add( $media ) {
		if ( ! empty( $media->album_id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-albums_' . $media->album_id );
		}
	}

	/**
	 * Any Media Photos after save
	 *
	 * @param BP_Media $media Current instance of media item being saved. Passed by reference.
	 */
	public function event_bp_media_after_save( $media ) {
		if ( ! empty( $media->album_id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-albums_' . $media->album_id );
		}
	}

	/**
	 * Any Media Photos before delete
	 *
	 * @param array $medias Array of media.
	 */
	public function event_bp_media_before_delete( $medias ) {
		if ( ! empty( $medias ) ) {
			foreach ( $medias as $media ) {
				if ( ! empty( $media->album_id ) ) {
					Cache::instance()->purge_by_group( 'bp-media-albums_' . $media->album_id );
				}
			}
		}
	}

	/****************************** Video Album Events *****************************/
	/**
	 * Any Video Album.
	 *
	 * @param BP_Video_Album $album Video Album object.
	 */
	public function event_bp_video_album_after_save( $album ) {
		if ( ! empty( $album->id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-albums_' . $album->id );
		}
	}

	/**
	 * Any Video Album.
	 *
	 * @param array $albums Array of video albums.
	 */
	public function event_bp_video_album_after_delete( $albums ) {
		if ( ! empty( $albums ) ) {
			foreach ( $albums as $album ) {
				if ( ! empty( $album->id ) ) {
					Cache::instance()->purge_by_group( 'bp-media-albums_' . $album->id );
				}
			}
		}
	}

	/**
	 * Any Video Photos add
	 *
	 * @param BP_Video $video Video object.
	 */
	public function event_bp_video_add( $video ) {
		if ( ! empty( $video->album_id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-albums_' . $video->album_id );
		}
	}

	/**
	 * Any Video Photos after save
	 *
	 * @param BP_Video $video Current instance of video item being saved. Passed by reference.
	 */
	public function event_bp_video_after_save( $video ) {
		if ( ! empty( $video->album_id ) ) {
			Cache::instance()->purge_by_group( 'bp-media-albums_' . $video->album_id );
		}
	}

	/**
	 * Any Videos before delete
	 *
	 * @param array $videos Array of video.
	 */
	public function event_bp_video_before_delete( $videos ) {
		if ( ! empty( $videos ) ) {
			foreach ( $videos as $video ) {
				if ( ! empty( $video->album_id ) ) {
					Cache::instance()->purge_by_group( 'bp-media-albums_' . $video->album_id );
				}
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
		$album_ids = $this->get_album_ids_by_group_id( $group_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
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
			$album_ids = $this->get_album_ids_by_group_id( $group->id );
			if ( ! empty( $album_ids ) ) {
				foreach ( $album_ids as $album_id ) {
					Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
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
		$album_ids = $this->get_album_ids_by_group_id( $group_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
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
		$album_ids = $this->get_album_id_by_media_id( $media_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
			}
		}
	}

	/**
	 * Unsuspended Media ID.
	 *
	 * @param int $media_id Media ID.
	 */
	public function event_bp_suspend_media_unsuspended( $media_id ) {
		$album_ids = $this->get_album_id_by_media_id( $media_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
			}
		}
	}

	/**
	 * Suspended Media Album ID.
	 *
	 * @param int $album_id Media Album ID.
	 */
	public function event_bp_suspend_media_album_suspended( $album_id ) {
		Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
	}

	/**
	 * Unsuspended Media Album ID.
	 *
	 * @param int $album_id Media Album ID.
	 */
	public function event_bp_suspend_media_album_unsuspended( $album_id ) {
		Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
	}

	/**
	 * Update cache for media album when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$album_ids = $this->get_album_ids_by_user_id( $bp_moderation->item_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
			}
		}
	}

	/**
	 * Update cache for media album when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$album_ids = $this->get_album_ids_by_user_id( $bp_moderation->item_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
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
		$album_ids = $this->get_album_ids_by_user_id( $user_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$album_ids = $this->get_album_ids_by_user_id( $user_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$album_ids = $this->get_album_ids_by_user_id( $user_id );
		if ( ! empty( $album_ids ) ) {
			foreach ( $album_ids as $album_id ) {
				Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
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
				$album_ids = $this->get_album_ids_by_user_id( $user_id );
				if ( ! empty( $album_ids ) ) {
					foreach ( $album_ids as $album_id ) {
						Cache::instance()->purge_by_group( 'bp-media-albums_' . $album_id );
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
	private function get_album_ids_by_user_id( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->media->table_name_albums} WHERE user_id = %d", $user_id );

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
	private function get_album_ids_by_group_id( $group_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->media->table_name_albums} WHERE group_id = %d", $group_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get Album ID from Media ID.
	 *
	 * @param int $media_id Media ID.
	 *
	 * @return array
	 */
	private function get_album_id_by_media_id( $media_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT album_id FROM {$bp->media->table_name} WHERE id = %d", $media_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}
}
