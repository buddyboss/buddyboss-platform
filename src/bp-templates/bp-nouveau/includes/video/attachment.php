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
$encode_thread_id = base64_decode( get_query_var( 'video-thread-attachment-id' ) );
$explode_arr      = explode( 'forbidden_', $encode_id );
$explode_message_arr = explode( 'thread_', $encode_thread_id );
$from             = ( ! empty( get_query_var( 'video-thread-attachment-new' ) ) ? get_query_var( 'video-thread-attachment-new' ) : '' );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 ) {

	$attachment_id = (int) $explode_arr[1];

	$media = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->media->table_name} WHERE attachment_id = %d AND type= %s", $attachment_id, 'video' ) );

	if ( $media && empty( $from ) && empty( $encode_message_thread_id ) ) {
		echo '// Silence is golden.';
		exit();
	} elseif ( $media && ! empty( $from ) && ! empty( $explode_message_arr[1] ) && function_exists( 'bp_is_active' ) && bp_is_active( 'messages' ) ) {
		$thread_id = (int) $explode_message_arr[1];
		if ( function_exists( 'bp_loggedin_user_id' ) && function_exists( 'messages_check_thread_access' ) && ! messages_check_thread_access( $thread_id, bp_loggedin_user_id() ) ) {
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
