<?php
/**
 * Functions related to the BuddyBoss Document component and the WP Cache.
 *
 * @package BuddyBoss\Document
 * @since BuddyBoss 1.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Clear a cached document item when that item is updated.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param BP_Document $document Document object.
 */
function bp_document_clear_cache_for_document( $document ) {
	wp_cache_delete( $document->id, 'bp_document' );
	wp_cache_delete( 'bb_document_activity_' . $document->id, 'bp_document' ); // Used in bb_moderation_get_media_record_by_id().

	$group_id = ! empty( $document->group_id ) ? $document->group_id : false;

	if ( $group_id ) {
		wp_cache_delete( 'bp_total_document_for_group_' . $group_id, 'bp' );
	}

	bp_core_reset_incrementor( 'bp_document' );
	bp_core_reset_incrementor( 'bp_document_folder' );
}
add_action( 'bp_document_after_save', 'bp_document_clear_cache_for_document' );

/**
 * Clear cached data for deleted document items.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param array $deleted_ids IDs of deleted document items.
 */
function bp_document_clear_cache_for_deleted_document( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_document' );
		wp_cache_delete( 'bb_document_activity_' . $deleted_id, 'bp_document' ); // Used in bb_moderation_get_media_record_by_id().
	}
}
add_action( 'bp_document_deleted_documents', 'bp_document_clear_cache_for_deleted_document' );

/**
 * Reset cache incrementor for the Document component.
 *
 * Called whenever an document item is created, updated, or deleted, this
 * function effectively invalidates all cached results of document queries.
 *
 * @since BuddyBoss 1.4.0
 *
 * @return bool True on success, false on failure.
 */
function bp_document_reset_cache_incrementor() {
	bp_core_reset_incrementor( 'bp_document_folder' );
	return bp_core_reset_incrementor( 'bp_document' );
}
add_action( 'bp_document_delete', 'bp_document_reset_cache_incrementor' );
add_action( 'bp_document_add', 'bp_document_reset_cache_incrementor' );

/**
 * Clear a user's cached document count.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param object $document Document object item.
 */
function bp_document_clear_document_user_object_cache( $document ) {
	$user_id = ! empty( $document->user_id ) ? $document->user_id : false;

	if ( $user_id ) {
		wp_cache_delete( 'bp_total_document_for_user_' . $user_id, 'bp' );
	}
}
add_action( 'bp_document_add', 'bp_document_clear_document_user_object_cache', 10 );

/**
 * Clear a user's cached document count when delete.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param array $documents DB results of document items.
 */
function bp_document_clear_document_user_object_cache_on_delete( $documents ) {
	if ( ! empty( $documents ) ) {
		foreach ( (array) $documents as $deleted_document ) {
			$user_id = ! empty( $deleted_document->user_id ) ? $deleted_document->user_id : false;

			wp_cache_delete( 'bb_document_activity_' . $deleted_document->id, 'bp_document' ); // Used in bb_moderation_get_media_record_by_id().

			if ( ! empty( $deleted_document->activity_id ) ) {
				wp_cache_delete( 'bp_document_activity_id_' . $deleted_document->activity_id, 'bp_document' );
				wp_cache_delete( 'bp_document_attachment_id_' . $deleted_document->activity_id, 'bp_document' );
			}

			if ( $user_id ) {
				wp_cache_delete( 'bp_total_document_for_user_' . $user_id, 'bp' );
			}
		}
	}
}
add_action( 'bp_document_before_delete', 'bp_document_clear_document_user_object_cache_on_delete', 10 );

/**
 * Clear a user's cached document count.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param int $user_id ID of the user deleted.
 */
function bp_document_remove_all_user_object_cache_data( $user_id ) {
	wp_cache_delete( 'bp_total_document_for_user_' . $user_id, 'bp' );
}
add_action( 'bp_document_remove_all_user_data', 'bp_document_remove_all_user_object_cache_data' );

/**
 * Clear a group's cached document count.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param object $document Document object item.
 */
function bp_document_clear_document_group_object_cache( $document ) {
	$group_id = ! empty( $document->group_id ) ? $document->group_id : false;
	$folder_id = ! empty( $document->folder_id ) ? $document->folder_id : false;

	if ( $group_id ) {
		wp_cache_delete( 'bp_total_document_for_group_' . $group_id, 'bp' );
	}

	if ( $folder_id ) {
		bp_core_reset_incrementor( 'bp_document' );
		bp_core_reset_incrementor( 'bp_document_folder' );
		wp_cache_delete( $folder_id, 'bp_document_folder' );
	}
}
add_action( 'bp_document_add', 'bp_document_clear_document_group_object_cache', 10 );

/**
 * Clear a group's cached document count when delete.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param array $documents DB results of document items.
 */
function bp_document_clear_document_group_object_cache_on_delete( $documents ) {
	if ( ! empty( $documents[0] ) ) {
		foreach ( (array) $documents[0] as $deleted_document ) {
			$group_id = ! empty( $deleted_document->group_id ) ? $deleted_document->group_id : false;

			if ( $group_id ) {
				wp_cache_delete( 'bp_total_document_for_group_' . $group_id, 'bp' );
			}
		}
	}
}
add_action( 'bp_document_before_delete', 'bp_document_clear_document_group_object_cache_on_delete', 10 );

/**
 * Clear a cached folder item when that item is updated.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param BP_Document_Folder $folder Folder object.
 */
function bp_document_clear_cache_for_folder( $folder ) {
	bp_core_reset_incrementor( 'bp_document' );
	bp_core_reset_incrementor( 'bp_document_folder' );
	wp_cache_delete( $folder->id, 'bp_document_folder' );
}
add_action( 'bp_document_folder_after_save', 'bp_document_clear_cache_for_folder' );

/**
 * Clear cached data for deleted folder items.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param array $deleted_ids IDs of deleted folder items.
 */
function bp_document_clear_cache_for_deleted_folder( $deleted_ids ) {
	foreach ( (array) $deleted_ids as $deleted_id ) {
		wp_cache_delete( $deleted_id, 'bp_document_folder' );
	}
	bp_core_reset_incrementor( 'bp_document' );
	bp_core_reset_incrementor( 'bp_document_folder' );
}
add_action( 'bp_folders_deleted_folders', 'bp_document_clear_cache_for_deleted_folder' );

/**
 * Reset cache incrementor for the Folder.
 *
 * Called whenever an folder item is created, updated, or deleted, this
 * function effectively invalidates all cached results of folder queries.
 *
 * @since BuddyBoss 1.4.0
 *
 * @return bool True on success, false on failure.
 */
function bp_document_folder_reset_cache_incrementor() {
	bp_core_reset_incrementor( 'bp_document' );
	return bp_core_reset_incrementor( 'bp_document_folder' );
}
add_action( 'bp_folder_delete', 'bp_document_folder_reset_cache_incrementor' );
add_action( 'bp_folder_add', 'bp_document_folder_reset_cache_incrementor' );

/**
 * Clear a group's cached folder count.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param object $folder Folder object item.
 */
function bp_document_clear_folder_group_object_cache( $folder ) {
	$group_id = ! empty( $folder->group_id ) ? $folder->group_id : false;

	if ( $group_id ) {
		wp_cache_delete( 'bp_total_folder_for_group_' . $group_id, 'bp' );
	}
}
add_action( 'bp_folder_add', 'bp_document_clear_folder_group_object_cache', 10 );

/**
 * Clear a group's cached folder count when delete.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param array $folders DB results of folder items.
 */
function bp_document_clear_folder_group_object_cache_on_delete( $folders ) {
	if ( ! empty( $folders[0] ) ) {
		foreach ( (array) $folders[0] as $deleted_folder ) {
			$group_id = ! empty( $deleted_folder->group_id ) ? $deleted_folder->group_id : false;

			if ( $group_id ) {
				wp_cache_delete( 'bp_total_folder_for_group_' . $group_id, 'bp' );
			}
		}
	}
}
add_action( 'bp_document_folder_before_delete', 'bp_document_clear_folder_group_object_cache_on_delete', 10 );
