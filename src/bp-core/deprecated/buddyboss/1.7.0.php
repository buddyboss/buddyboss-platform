<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check user have a permission to manage the folder.
 *
 * @param int $folder_id
 * @param int $user_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_user_can_manage_folder( $folder_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bb_media_user_can_access' );

	$data = bb_media_user_can_access( $folder_id, 'folder' );

	/**
	 * Filter to get the folder access.
	 *
	 * @deprecated 1.7.0 Use {@see 'bb_media_user_can_access'} instead.
	 *
	 * @param array $data      Access data.
	 * @param int   $folder_id Folder id.
	 * @param int   $user_id   User id.
	 */
	return apply_filters( 'bp_document_user_can_manage_folder', $data, $folder_id, $user_id );

}

/**
 * Check user have a permission to manage the document.
 *
 * @param int $document_id
 * @param int $user_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_user_can_manage_document( $document_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bb_media_user_can_access' );

	$data = bb_media_user_can_access( $document_id, 'document' );

	/**
	 * Filter to get the document access.
	 *
	 * @deprecated 1.7.0 Use {@see 'bb_media_user_can_access'} instead.
	 *
	 * @param array $data        Access data.
	 * @param int   $document_id Document id.
	 * @param int   $user_id     User id.
	 */
	return apply_filters( 'bp_document_user_can_manage_document', $data, $document_id, $user_id );

}

/**
 * Check user have a permission to manage the album.
 *
 * @param int $album_id
 * @param int $user_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.7
 */
function bp_media_user_can_manage_album( $album_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bb_media_user_can_access' );

	$data = bb_media_user_can_access( $album_id, 'album' );

	/**
	 * Filter to get the album access.
	 *
	 * @deprecated 1.7.0 Use {@see 'bb_media_user_can_access'} instead.
	 *
	 * @param array $data     Access data.
	 * @param int   $album_id Album id.
	 * @param int   $user_id  User id.
	 */
	return apply_filters( 'bp_media_user_can_manage_album', $data, $album_id, $user_id );
}

/**
 * Check user have a permission to manage the media.
 *
 * @param int $media_id
 * @param int $user_id
 * @param int $thread_id
 * @param int $message_id
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.4
 */
function bp_media_user_can_manage_media( $media_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bb_media_user_can_access' );

	$data = bb_media_user_can_access( $media_id, 'photo' );

	/**
	 * Filter to get the media access.
	 *
	 * @deprecated 1.7.0 Use {@see 'bb_media_user_can_access'} instead.
	 *
	 * @param array $data     Access data.
	 * @param int   $media_id Media id.
	 * @param int   $user_id  User id.
	 */
	return apply_filters( 'bp_media_user_can_manage_media', $data, $media_id, $user_id );
}
