<?php
/**
 * Video Attachment.
 *
 * @since   BuddyBoss 2.0.4
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( empty( get_query_var( 'video-attachment-id' ) ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id        = base64_decode( get_query_var( 'video-attachment-id' ) );
$explode_arr      = explode( 'forbidden_', $encode_id );
$encode_thread_id = base64_decode( get_query_var( 'video-thread-id' ) );
$thread_arr       = explode( 'thread_', $encode_thread_id );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 ) {
	global $bp, $wpdb;

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

	if ( $media ) {
		// Saved items follow their own privacy, including message thread participation.
		$media_access = function_exists( 'bb_media_user_can_access' ) ? bb_media_user_can_access( $media->id, 'video', $attachment_id ) : array();
		if ( empty( $media_access['can_view'] ) ) {
			echo '// Silence is golden.';
			exit();
		}
	} else {
		$is_bb_video_upload = (bool) get_post_meta( $attachment_id, 'bp_video_upload', true );

		// Unsaved uploads are only previewed by the member who uploaded them.
		if (
			! $is_bb_video_upload ||
			! is_user_logged_in() ||
			(
				(int) get_post_field( 'post_author', $attachment_id ) !== bp_loggedin_user_id() &&
				! bp_current_user_can( 'bp_moderate' )
			)
		) {
			echo '// Silence is golden.';
			exit();
		}
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
