<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.3.60
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Download an image from the specified URL and attach it to a post.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 2.3.60
 *
 * @param string $file The URL of the image to download
 *
 * @return int|void
 */
function bboss_activity_media_sideload_attachment( $file ) {

	_deprecated_function( __FUNCTION__, '2.3.60', 'bboss_bb_media_sideload_attachment' );

	return bboss_bb_media_sideload_attachment( $file );
}

/**
 * This handles a sideloaded file in the same way as an uploaded file is handled by {@link media_handle_upload()}
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 2.3.60
 * 
 * @param array $file_array Array similar to a {@link $_FILES} upload array
 * @param array $post_data  allows you to overwrite some of the attachment
 *
 * @return int|object The ID of the attachment or a WP_Error on failure
 */
function bboss_activity_media_handle_sideload( $file_array, $post_data = array() ) {

	_deprecated_function( __FUNCTION__, '2.3.60', 'bboss_bb_media_handle_sideload' );

	return bboss_bb_media_handle_sideload( $file_array, $post_data );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_media_handle_sideload' ) && ! function_exists( 'bp_activity_media_handle_sideload' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_media_handle_sideload()}.
	 *
	 * @deprecated Use bboss_activity_media_handle_sideload() instead.
	 */
	function bp_activity_media_handle_sideload( $file_array, $post_data = array() ) {
		return bboss_activity_media_handle_sideload( $file_array, $post_data );
	}
}
if ( function_exists( 'bboss_activity_media_sideload_attachment' ) && ! function_exists( 'bp_activity_media_sideload_attachment' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_media_sideload_attachment()}.
	 *
	 * @deprecated Use bboss_activity_media_sideload_attachment() instead.
	 */
	function bp_activity_media_sideload_attachment( $file ) {
		return bboss_activity_media_sideload_attachment( $file );
	}
}
// @bbwt-com-end compat-layer
