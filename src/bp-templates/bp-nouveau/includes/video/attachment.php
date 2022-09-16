<?php
/**
 * Video Attachment.
 *
 * @since   BuddyBoss 2.0.4
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $bp;

if ( empty( get_query_var( 'video-attachment-id' ) ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id        = base64_decode( get_query_var( 'video-attachment-id' ) );
$explode_arr      = explode( 'forbidden_', $encode_id );
$encode_thread_id = base64_decode( get_query_var( 'video-thread-id' ) );
$thread_arr       = explode( 'thread_', $encode_thread_id );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 ) {

	$attachment_id = (int) $explode_arr[1];

	$media = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->media->table_name} WHERE attachment_id = %d AND type= %s", $attachment_id, 'video' ) );

	if (
		$media &&
		(
			! isset( $thread_arr ) ||
			empty( $thread_arr ) ||
			! isset( $thread_arr[1] ) ||
			(int) $thread_arr[1] <= 0
		)
	) {
		echo '// Silence is golden.';
		exit();
	}

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
