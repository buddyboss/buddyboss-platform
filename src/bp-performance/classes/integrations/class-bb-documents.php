<?php
/**
 * BuddyBoss Performance Documents Integration.
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
 * Documents Integration Class.
 *
 * @package BuddyBossApp\Performance
 */
class BB_Documents extends Integration_Abstract {


	/**
	 * Add(Start) Integration
	 *
	 * @return mixed|void
	 */
	public function set_up() {
		$this->register( 'bp-document' );

		$purge_events = array(
			'bp_document_add',                  // Any Document File add.
			'bp_document_after_save',           // Any Document File after save.
			'bp_document_before_delete',        // Any Document File delete.
			'bp_folder_add',                    // Any Document Folder add.
			'bp_document_folder_after_save',    // Any Document Folder delete.
			'bp_document_folder_before_delete', // Any Document Folder delete.

			// Added moderation support.
			'bp_suspend_document_suspended',            // Any Document Suspended.
			'bp_suspend_document_unsuspended',          // Any Document Unsuspended.
			'bp_suspend_document_folder_suspended',     // Any Document Folder Suspended.
			'bp_suspend_document_folder_unsuspended',   // Any Document Folder Unsuspended.
			'bp_moderation_after_save',                 // Hide document when member blocked.
			'bb_moderation_after_delete'                // Unhide document when member unblocked.
		);

		$this->purge_event( 'bp-document', $purge_events );

		/**
		 * Support for single items purge
		 */
		$purge_single_events = array(
			'bp_document_add'                        => 1, // Any Media Photo add.
			'bp_document_after_save'                 => 1, // Any Document File add.
			'bp_document_before_delete'              => 1, // Any Document File add.

			'bp_folder_add'                          => 1, // Any Document Folder add.
			'bp_document_folder_after_save'          => 1, // Any Document Folder delete.
			'bp_document_folder_before_delete'       => 1, // Any Document Folder delete.

			'updated_document_meta'                  => 2, // Any document meta update.
			'updated_folder_meta'                    => 2, // Any folder meta update.

			// Document group information update support.
			'groups_update_group'                    => 1, // When Group Details updated.
			'groups_group_after_save'                => 1, // When Group Details save.
			'groups_group_details_edited'            => 1, // When Group Details updated form Manage.

			// Added moderation support.
			'bp_suspend_document_suspended'          => 1, // Any Document Suspended.
			'bp_suspend_document_unsuspended'        => 1, // Any Document Unsuspended.
			'bp_suspend_document_folder_suspended'   => 1, // Any Document Folder Suspended.
			'bp_suspend_document_folder_unsuspended' => 1, // Any Document Folder Unsuspended.
			'bp_moderation_after_save'               => 1, // Hide document when member blocked.
			'bb_moderation_after_delete'             => 1, // Unhide document when member unblocked.

			// Add Author Embed Support.
			'profile_update'                         => 1, // User updated on site.
			'deleted_user'                           => 1, // User deleted on site.
			'xprofile_avatar_uploaded'               => 1, // User avatar photo updated.
			'bp_core_delete_existing_avatar'         => 1, // User avatar photo deleted.
		);

		$this->purge_single_events( $purge_single_events );

		$is_component_active = Helper::instance()->get_app_settings( 'cache_component', 'buddyboss-app' );
		$settings            = Helper::instance()->get_app_settings( 'cache_bb_media', 'buddyboss-app' );
		$cache_bb_media      = isset( $is_component_active ) && isset( $settings ) ? ( $is_component_active && $settings ) : false;

		if ( $cache_bb_media ) {

			$this->cache_endpoint(
				'buddyboss/v1/document',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => array( 'type', 'id' ),
					'include_param'     => array(
						'type' => 'type',
						'id'   => 'include',
					),
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/document/folder',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id'         => array( 'type', 'id' ),
					'include_param'     => array(
						'id' => 'include',
					),
				),
				true
			);

			$this->cache_endpoint(
				'buddyboss/v1/document/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id' => array( 'type', 'id' ),
				),
				false
			);

			$this->cache_endpoint(
				'buddyboss/v1/document/folder/<id>',
				Cache::instance()->month_in_seconds * 60,
				array(
					'unique_id' => array( 'type', 'id' ),
				),
				false
			);
		}
	}

	/****************************** Document Events *****************************/
	/**
	 * Any Document Added.
	 *
	 * @param BP_Document $document Document object.
	 */
	public function event_bp_document_add( $document ) {
		if ( ! empty( $document->id ) ) {
			Cache::instance()->purge_by_group( 'bp-document_document_' . $document->id );
		}
	}

	/**
	 * Any Document Saved.
	 *
	 * @param BP_Document $document Current instance of document item being saved. Passed by reference.
	 */
	public function event_bp_document_after_save( $document ) {
		if ( ! empty( $document->id ) ) {
			Cache::instance()->purge_by_group( 'bp-document_document_' . $document->id );
		}
	}

	/**
	 * Any Document Delete.
	 *
	 * @param array $documents Array of document.
	 */
	public function event_bp_document_before_delete( $documents ) {
		if ( ! empty( $documents ) ) {
			foreach ( $documents as $document ) {
				if ( ! empty( $document->id ) ) {
					Cache::instance()->purge_by_group( 'bp-document_document_' . $document->id );
				}
			}
		}
	}

	/**
	 * Any Folder Added.
	 *
	 * @param BP_Document_Folder $folder Folder object.
	 */
	public function event_bp_folder_add( $folder ) {
		if ( ! empty( $folder->id ) ) {
			Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder->id );
		}
	}

	/**
	 * Any Folder Saved.
	 *
	 * @param BP_Document_Folder $folder Current instance of folder item being saved. Passed by reference.
	 */
	public function event_bp_document_folder_after_save( $folder ) {
		if ( ! empty( $folder->id ) ) {
			Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder->id );
		}
	}

	/**
	 * Any Folder Delete.
	 *
	 * @param array $folders Array of document folders.
	 */
	public function event_bp_document_folder_before_delete( $folders ) {
		if ( ! empty( $folders ) ) {
			foreach ( $folders as $folder ) {
				if ( ! empty( $folder->id ) ) {
					Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder->id );
				}
			}
		}
	}

	/**
	 * Any Document meta update
	 *
	 * @param int $meta_id     Document Meta id.
	 * @param int $document_id Document id.
	 */
	public function event_updated_document_meta( $meta_id, $document_id ) {
		Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
	}

	/**
	 * Any Document Folder meta update
	 *
	 * @param int $meta_id   Folder Meta id.
	 * @param int $folder_id Folder id.
	 */
	public function event_updated_folder_meta( $meta_id, $folder_id ) {
		Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
	}

	/****************************** Group Embed Support *****************************/
	/**
	 * When Group Details updated.
	 *
	 * @param int $group_id Group id.
	 */
	public function event_groups_update_group( $group_id ) {
		$document_ids = $this->get_document_ids_by_group_id( $group_id );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document_id ) {
				Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
			}
		}

		$document_folder_ids = $this->get_document_folder_ids_by_group_id( $group_id );
		if ( ! empty( $document_folder_ids ) ) {
			foreach ( $document_folder_ids as $folder_id ) {
				Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
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
			$document_ids = $this->get_document_ids_by_group_id( $group->id );
			if ( ! empty( $document_ids ) ) {
				foreach ( $document_ids as $document_id ) {
					Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
				}
			}

			$document_folder_ids = $this->get_document_folder_ids_by_group_id( $group->id );
			if ( ! empty( $document_folder_ids ) ) {
				foreach ( $document_folder_ids as $folder_id ) {
					Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
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
		$document_ids = $this->get_document_ids_by_group_id( $group_id );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document_id ) {
				Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
			}
		}

		$document_folder_ids = $this->get_document_folder_ids_by_group_id( $group_id );
		if ( ! empty( $document_folder_ids ) ) {
			foreach ( $document_folder_ids as $folder_id ) {
				Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
			}
		}
	}

	/******************************* Moderation Support ******************************/
	/**
	 * Suspended Document ID.
	 *
	 * @param int $document_id Document ID.
	 */
	public function event_bp_suspend_document_suspended( $document_id ) {
		Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
	}

	/**
	 * Unsuspended Document ID.
	 *
	 * @param int $document_id Document ID.
	 */
	public function event_bp_suspend_document_unsuspended( $document_id ) {
		Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
	}

	/**
	 * Suspended Document Folder ID.
	 *
	 * @param int $folder_id Folder ID.
	 */
	public function event_bp_suspend_document_folder_suspended( $folder_id ) {
		Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
	}

	/**
	 * Unsuspended Document Folder ID.
	 *
	 * @param int $folder_id Folder ID.
	 */
	public function event_bp_suspend_document_folder_unsuspended( $folder_id ) {
		Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
	}

	/**
	 * Update cache for document when member blocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bp_moderation_after_save( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$document_ids = $this->get_document_ids_by_user_id( $bp_moderation->item_id );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document_id ) {
				Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
			}
		}
	}

	/**
	 * Update cache for document when member unblocked.
	 *
	 * @param BP_Moderation $bp_moderation Current instance of moderation item. Passed by reference.
	 */
	public function event_bb_moderation_after_delete( $bp_moderation ) {
		if ( empty( $bp_moderation->item_id ) || empty( $bp_moderation->item_type ) || 'user' !== $bp_moderation->item_type ) {
			return;
		}
		$document_ids = $this->get_document_ids_by_user_id( $bp_moderation->item_id );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document_id ) {
				Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
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
		$document_ids = $this->get_document_ids_by_user_id( $user_id );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document_id ) {
				Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
			}
		}

		$document_folder_ids = $this->get_document_folder_ids_by_user_id( $user_id );
		if ( ! empty( $document_folder_ids ) ) {
			foreach ( $document_folder_ids as $folder_id ) {
				Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
			}
		}
	}

	/**
	 * User deleted on site
	 *
	 * @param int $user_id User ID.
	 */
	public function event_deleted_user( $user_id ) {
		$document_ids = $this->get_document_ids_by_user_id( $user_id );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document_id ) {
				Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
			}
		}

		$document_folder_ids = $this->get_document_folder_ids_by_user_id( $user_id );
		if ( ! empty( $document_folder_ids ) ) {
			foreach ( $document_folder_ids as $folder_id ) {
				Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
			}
		}
	}

	/**
	 * User avatar photo updated
	 *
	 * @param int $user_id User ID.
	 */
	public function event_xprofile_avatar_uploaded( $user_id ) {
		$document_ids = $this->get_document_ids_by_user_id( $user_id );
		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document_id ) {
				Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
			}
		}

		$document_folder_ids = $this->get_document_folder_ids_by_user_id( $user_id );
		if ( ! empty( $document_folder_ids ) ) {
			foreach ( $document_folder_ids as $folder_id ) {
				Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
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
				$document_ids = $this->get_document_ids_by_user_id( $user_id );
				if ( ! empty( $document_ids ) ) {
					foreach ( $document_ids as $document_id ) {
						Cache::instance()->purge_by_group( 'bp-document_document_' . $document_id );
					}
				}

				$document_folder_ids = $this->get_document_folder_ids_by_user_id( $user_id );
				if ( ! empty( $document_folder_ids ) ) {
					foreach ( $document_folder_ids as $folder_id ) {
						Cache::instance()->purge_by_group( 'bp-document_folder_' . $folder_id );
					}
				}
			}
		}
	}

	/*********************************** Functions ***********************************/
	/**
	 * Get Document ids from user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_document_ids_by_user_id( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->document->table_name} WHERE user_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get Document Ids .
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array
	 */
	private function get_document_ids_by_group_id( $group_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->document->table_name} WHERE group_id = %d", $group_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get Document Folders ids from user ID.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 */
	private function get_document_folder_ids_by_user_id( $user_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->document->table_name_folder} WHERE user_id = %d", $user_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

	/**
	 * Get Document Folder Ids .
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array
	 */
	private function get_document_folder_ids_by_group_id( $group_id ) {
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT id FROM {$bp->document->table_name_folder} WHERE group_id = %d", $group_id );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_col( $sql );
	}

}
