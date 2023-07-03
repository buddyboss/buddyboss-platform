<?php

if ( empty( get_query_var( 'bb-media-preview' ) ) && empty( get_query_var( 'id1' ) ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id       = base64_decode( get_query_var( 'bb-media-preview' ) );
$encode_id1      = base64_decode( get_query_var( 'id1' ) );
$explode_arr     = explode( 'forbidden_', $encode_id );
$explode_arr1    = explode( 'forbidden_', $encode_id1 );
$size            = ( ! empty( get_query_var( 'size' ) ) ? get_query_var( 'size' ) : '' );
$upload_dir      = wp_upload_dir();
$upload_dir      = $upload_dir['basedir'];
$output_file_src = '';

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 &&
     isset( $explode_arr1 ) && ! empty( $explode_arr1 ) && isset( $explode_arr1[1] ) && (int) $explode_arr1[1] > 0 ) {

	$attachment_id      = (int) $explode_arr[1];
	$id1                = (int) $explode_arr1[1];
	$media_privacy      = ( function_exists( 'bb_media_user_can_access' ) ) ? bb_media_user_can_access( $id1, 'photo' ) : true;
	$can_view           = true === (bool) $media_privacy['can_view'];
	$attached_file_info = pathinfo( get_attached_file( $attachment_id ) );

	if ( $can_view && wp_attachment_is_image( $attachment_id ) ) {

		$type      = get_post_mime_type( $attachment_id );
		$file      = image_get_intermediate_size( $attachment_id, $size );
		$file_path = $attached_file_info['dirname'];

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

