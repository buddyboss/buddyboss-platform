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
 * @param int $folder_id Folder id.
 * @param int $user_id   User id.
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bboss_document_user_can_manage_folder( $folder_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bboss_media_user_can_access' );

	$data = bboss_media_user_can_access( $folder_id, 'folder' );

	if ( isset( $data['can_edit'] ) ) {
		$data['can_manage'] = $data['can_edit'];
	}

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_document_user_can_manage_folder' ) && ! function_exists( 'bp_document_user_can_manage_folder' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_document_user_can_manage_folder()}.
	 *
	 * @deprecated Use bboss_document_user_can_manage_folder() instead.
	 */
	function bp_document_user_can_manage_folder( $folder_id = 0, $user_id = 0 ) {
		return bboss_document_user_can_manage_folder( $folder_id, $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check user have a permission to manage the document.
 *
 * @param int $document_id Document id.
 * @param int $user_id     User id.
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.0
 */
function bboss_document_user_can_manage_document( $document_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bboss_media_user_can_access' );

	$data = bboss_media_user_can_access( $document_id, 'document' );

	if ( isset( $data['can_edit'] ) ) {
		$data['can_manage'] = $data['can_edit'];
	}

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_document_user_can_manage_document' ) && ! function_exists( 'bp_document_user_can_manage_document' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_document_user_can_manage_document()}.
	 *
	 * @deprecated Use bboss_document_user_can_manage_document() instead.
	 */
	function bp_document_user_can_manage_document( $document_id = 0, $user_id = 0 ) {
		return bboss_document_user_can_manage_document( $document_id, $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check user have a permission to manage the album.
 *
 * @param int $album_id Album id.
 * @param int $user_id  User id.
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.7
 */
function bboss_media_user_can_manage_album( $album_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bboss_media_user_can_access' );

	$data = bboss_media_user_can_access( $album_id, 'album' );

	if ( isset( $data['can_edit'] ) ) {
		$data['can_manage'] = $data['can_edit'];
	}

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_media_user_can_manage_album' ) && ! function_exists( 'bp_media_user_can_manage_album' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_media_user_can_manage_album()}.
	 *
	 * @deprecated Use bboss_media_user_can_manage_album() instead.
	 */
	function bp_media_user_can_manage_album( $album_id = 0, $user_id = 0 ) {
		return bboss_media_user_can_manage_album( $album_id, $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Check user have a permission to manage the media.
 *
 * @param int $media_id   Media id.
 * @param int $user_id    User id.
 *
 * @return mixed|void
 * @since BuddyBoss 1.4.4
 */
function bboss_media_user_can_manage_media( $media_id = 0, $user_id = 0 ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bboss_media_user_can_access' );

	$data = bboss_media_user_can_access( $media_id, 'photo' );

	if ( isset( $data['can_edit'] ) ) {
		$data['can_manage'] = $data['can_edit'];
	}

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_media_user_can_manage_media' ) && ! function_exists( 'bp_media_user_can_manage_media' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_media_user_can_manage_media()}.
	 *
	 * @deprecated Use bboss_media_user_can_manage_media() instead.
	 */
	function bp_media_user_can_manage_media( $media_id = 0, $user_id = 0 ) {
		return bboss_media_user_can_manage_media( $media_id, $user_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Return absolute path of the document file.
 *
 * @param int $attachment_id Attachment id.
 * @since BuddyBoss 1.4.1
 */
function bboss_document_scaled_image_path( $attachment_id ) {
	$is_image         = wp_attachment_is_image( $attachment_id );
	$img_url          = get_attached_file( $attachment_id );
	$meta             = wp_get_attachment_metadata( $attachment_id );
	$img_url_basename = wp_basename( $img_url );
	if ( ! $is_image ) {
		if ( ! empty( $meta['sizes']['full'] ) ) {
			$img_url = str_replace( $img_url_basename, $meta['sizes']['full']['file'], $img_url );
		}
	}

	return $img_url;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_document_scaled_image_path' ) && ! function_exists( 'bp_document_scaled_image_path' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_document_scaled_image_path()}.
	 *
	 * @deprecated Use bboss_document_scaled_image_path() instead.
	 */
	function bp_document_scaled_image_path( $attachment_id ) {
		return bboss_document_scaled_image_path( $attachment_id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Return the preview url of the file.
 *
 * @param int    $document_id           Document ID.
 * @param string $extension             Extension name.
 * @param int    $preview_attachment_id Document Attachment ID.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.4.0
 */
function bboss_document_get_preview_image_url( $document_id, $extension, $preview_attachment_id ) {

	_deprecated_function( __FUNCTION__, '1.7.0', 'bp_document_get_preview_url' );

	$attachment_url = function_exists( 'bp_document_get_preview_url' ) ? bp_document_get_preview_url( $document_id, $preview_attachment_id ) : '';

	return apply_filters( 'bp_document_get_preview_image_url', $attachment_url, $document_id, $extension );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_document_get_preview_image_url' ) && ! function_exists( 'bp_document_get_preview_image_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_document_get_preview_image_url()}.
	 *
	 * @deprecated Use bboss_document_get_preview_image_url() instead.
	 */
	function bp_document_get_preview_image_url( $document_id, $extension, $preview_attachment_id ) {
		return bboss_document_get_preview_image_url( $document_id, $extension, $preview_attachment_id );
	}
}
// @bbwt-com-end compat-layer


