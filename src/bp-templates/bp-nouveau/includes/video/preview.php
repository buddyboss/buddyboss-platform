<?php

if ( empty( get_query_var( 'bb-video-thumb-preview' ) ) && empty( get_query_var( 'id1' ) ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id    = base64_decode( get_query_var( 'bb-video-thumb-preview' ) );
$encode_id1   = base64_decode( get_query_var( 'id1' ) );
$size         = ( ! empty( get_query_var( 'size' ) ) ? get_query_var( 'size' ) : '' );
$explode_arr  = explode( 'forbidden_', $encode_id );
$explode_arr1 = explode( 'forbidden_', $encode_id1 );
$upload_dir   = wp_upload_dir();
$upload_dir   = $upload_dir['basedir'];

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 &&
     isset( $explode_arr1 ) && ! empty( $explode_arr1 ) && isset( $explode_arr1[1] ) && (int) $explode_arr1[1] > 0 ) {
	$attachment_id    = (int) $explode_arr[1];
	$id1              = (int) $explode_arr1[1];
	$document_privacy = ( function_exists( 'bb_media_user_can_access' ) ) ? bb_media_user_can_access( $id1, 'video' ) : true;
	$can_view         = isset( $document_privacy['can_view'] ) && true === (bool) $document_privacy['can_view'];
	if ( $can_view ) {

		if ( '' !== $size ) {
			$file               = image_get_intermediate_size( $attachment_id, $size );
			$attached_file_info = pathinfo( get_attached_file( $attachment_id ) );
			if ( $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {
				$file_path       = $attached_file_info['dirname'];
				$file_path       = $file_path . '/' . $file['file'];
				$output_file_src = $file_path;
			} else {
				$output_file_src = bb_core_scaled_attachment_path( $attachment_id );
			}
		} else {
			$output_file_src = bb_core_scaled_attachment_path( $attachment_id );
		}

		$type = get_post_mime_type( $attachment_id );

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

		header( "Content-Type: $type" );
		header( 'Cache-Control: max-age=2592000, public' );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 2592000 ) . ' GMT' );
		readfile( "$output_file_src" );
	} else {
		echo '// Silence is golden.';
		exit();
	}
} else {
	echo '// Silence is golden.';
	exit();
}
