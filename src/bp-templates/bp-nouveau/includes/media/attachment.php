<?php
/**
 * Media Attachment.
 *
 * @since   BuddyBoss 2.0.4
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $bp, $wpdb;

if ( empty( get_query_var( 'media-attachment-id' ) ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id       = base64_decode( get_query_var( 'media-attachment-id' ) );
$explode_arr     = explode( 'forbidden_', $encode_id );
$size            = ( ! empty( get_query_var( 'size' ) ) ? get_query_var( 'size' ) : '' );
$upload_dir      = wp_upload_dir();
$upload_dir      = $upload_dir['basedir'];
$output_file_src = '';

$encode_thread_id = base64_decode( get_query_var( 'media-thread-id' ) );
$thread_arr       = explode( 'thread_', $encode_thread_id );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 ) {

	$attachment_id = (int) $explode_arr[1];

	$media = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->media->table_name} WHERE attachment_id = %d AND type= %s", $attachment_id, 'photo' ) );

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

	if ( wp_attachment_is_image( $attachment_id ) ) {

		$attached_file_info = pathinfo( get_attached_file( $attachment_id ) );
		$type               = get_post_mime_type( $attachment_id );
		$file               = image_get_intermediate_size( $attachment_id, $size );
		$file_path          = $attached_file_info['dirname'];

		if ( '' !== $size && $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {

			$file_path_org   = $file_path . '/' . $file['file'];
			$output_file_src = $file_path_org;

			if ( ! file_exists( $output_file_src ) ) {
				bp_media_regenerate_attachment_thumbnails( $attachment_id );
				$file = image_get_intermediate_size( $attachment_id, $size );

				if ( $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {
					$file_path_org   = $file_path . '/' . $file['file'];
					$output_file_src = $file_path_org;
				} else {
					$output_file_src = bb_core_scaled_attachment_path( $attachment_id );
				}
			}
		} elseif ( ! $file ) {

			bp_media_regenerate_attachment_thumbnails( $attachment_id );

			$file = image_get_intermediate_size( $attachment_id, $size );

			if ( $file && ! empty( $file['path'] ) ) {

				$output_file_src = $upload_dir . '/' . $file['path'];

			} elseif ( wp_get_attachment_image_src( $attachment_id ) ) {

				$output_file_src = get_attached_file( $attachment_id );

				// Regenerate attachment thumbnails.
				if ( ! file_exists( $output_file_src ) ) {
					bp_media_regenerate_attachment_thumbnails( $attachment_id );
					$file = image_get_intermediate_size( $attachment_id, $size );
				}

				if ( $file && ! empty( $file['path'] ) ) {
					$output_file_src = $upload_dir . '/' . $file['path'];
				} else {
					$output_file_src = bb_core_scaled_attachment_path( $attachment_id );
				}
			}
		} else {
			$output_file_src = bb_core_scaled_attachment_path( $attachment_id );
		}

		if ( ! file_exists( $output_file_src ) ) {
			$file = image_get_intermediate_size( $attachment_id, 'full' );
			if ( $file && ! empty( $file['path'] ) ) {
				$output_file_src = $upload_dir . '/' . $file['path'];
			} else {
				$output_file_src = bb_core_scaled_attachment_path( $attachment_id );
			}
		}

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
