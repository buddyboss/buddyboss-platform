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
function bp_activity_media_sideload_attachment( $file ) {

	_deprecated_function( __FUNCTION__, '2.3.60', 'bb_media_sideload_attachment' );

	return bb_media_sideload_attachment( $file );
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
function bp_activity_media_handle_sideload( $file_array, $post_data = array() ) {

	_deprecated_function( __FUNCTION__, '2.3.60', 'bb_media_handle_sideload' );

	return bb_media_handle_sideload( $file_array, $post_data );
}
