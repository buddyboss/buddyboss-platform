<?php
/**
 * Document Attachment.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( empty( get_query_var( 'document-attachment-id' ) ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id   = base64_decode( get_query_var( 'document-attachment-id' ) );
$explode_arr = explode( 'forbidden_', $encode_id );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 ) {

	$attachment_id  = (int) $explode_arr[1];
	$post_author_id = (int) get_post_field( 'post_author', $attachment_id );

	if ( $post_author_id === get_current_user_id() ) {

		$output_file_src = bb_core_scaled_attachment_path( $attachment_id );

		if ( ! file_exists( $output_file_src ) ) {
			echo '// Silence is golden.';
			exit();
		}

		// Clear all output buffer.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		$stream = new BP_Media_Stream( $output_file_src, $attachment_id );
		$stream->start();

	} else {
		echo '// Silence is golden.';
		exit();
	}
} else {
	echo '// Silence is golden.';
	exit();
}
