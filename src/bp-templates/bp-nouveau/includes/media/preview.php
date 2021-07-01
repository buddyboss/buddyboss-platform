<?php

if ( ! defined( 'ABSPATH' ) ) {
	$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
	include_once $parse_uri[0] . '/wp-load.php';
}

define( 'WP_USE_THEMES', true );

global $wpdb, $bp;

if ( empty( $_REQUEST ) && empty( $_REQUEST['id'] ) && empty( $_REQUEST['id1'] ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id       = base64_decode( $_REQUEST['id'] );
$encode_id1      = base64_decode( $_REQUEST['id1'] );
$explode_arr     = explode( 'forbidden_', $encode_id );
$explode_arr1    = explode( 'forbidden_', $encode_id1 );
$size            = ( isset( $_REQUEST['size'] ) && ! empty( $_REQUEST['size'] ) ? $_REQUEST['size'] : '' );
$upload_dir      = wp_upload_dir();
$upload_dir      = $upload_dir['basedir'];
$output_file_src = '';

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 &&
     isset( $explode_arr1 ) && ! empty( $explode_arr1 ) && isset( $explode_arr1[1] ) && (int) $explode_arr1[1] > 0 ) {

	$id                 = (int) $explode_arr[1];
	$id1                = (int) $explode_arr1[1];
	$media_privacy      = ( function_exists( 'bb_media_user_can_access' ) ) ? bb_media_user_can_access( $id1, 'photo' ) : true;
	$can_view           = true === (bool) $media_privacy['can_view'];
	$attached_file_info = pathinfo( get_attached_file( $id ) );

	if ( $can_view && wp_attachment_is_image( $id ) ) {

		$type      = get_post_mime_type( $id );
		$file      = image_get_intermediate_size( $id, $size );
		$file_path = $attached_file_info['dirname'];

		if ( '' !== $size && $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {

			$file_path       = $file_path . '/' . $file['file'];
			$output_file_src = $file_path;

			if ( ! file_exists( $output_file_src ) ) {
				bp_media_regenerate_attachment_thumbnails( $id );
				$file = image_get_intermediate_size( $id, $size );

				if ( $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {
					$file_path       = $file_path . '/' . $file['file'];
					$output_file_src = $file_path;
				} else {
					$output_file_src = bb_core_scaled_attachment_path( $id );
				}

			}

		} elseif ( ! $file ) {

			bp_media_regenerate_attachment_thumbnails( $id );

			$file = image_get_intermediate_size( $id, $size );

			if ( $file && ! empty( $file['path'] ) ) {

				$output_file_src = $upload_dir . '/' . $file['path'];

			} elseif ( wp_get_attachment_image_src( $id ) ) {

				$output_file_src = get_attached_file( $id );

				// Regenerate attachment thumbnails.
				if ( ! file_exists( $output_file_src ) ) {
					bp_media_regenerate_attachment_thumbnails( $id );
					$file = image_get_intermediate_size( $id, $size );
				}

				if ( $file && ! empty( $file['path'] ) ) {
					$output_file_src = $upload_dir . '/' . $file['path'];
				} else {
					$output_file_src = bb_core_scaled_attachment_path( $id );
				}
			}
		} else {
			$output_file_src = bb_core_scaled_attachment_path( $id );
		}

		if ( ! file_exists( $output_file_src ) ) {
			echo '// Silence is golden.';
			exit();
		}
		header( "Content-Type: $type" );
		readfile( "$output_file_src" );
	} else {
		echo '// Silence is golden.';
		exit();
	}
} else {
	echo '// Silence is golden.';
	exit();
}

