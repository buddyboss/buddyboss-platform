<?php

if ( ! defined( 'ABSPATH' ) ) {
	$parse_uri = explode( 'wp-content', $_SERVER[ 'SCRIPT_FILENAME' ] );
	include_once $parse_uri[ 0 ] . '/wp-load.php';
}

define( 'WP_USE_THEMES', true );

global $wpdb, $bp;

if ( empty( $_REQUEST ) && empty( $_REQUEST[ 'id' ] ) && empty( $_REQUEST[ 'id1' ] ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id    = base64_decode( $_REQUEST['id'] );
$encode_id1   = base64_decode( $_REQUEST['id1'] );
$size         = ( isset( $_REQUEST['size'] ) && ! empty( $_REQUEST['size'] ) ? base64_decode( $_REQUEST['size'] ) : '' );
$explode_arr  = explode( 'forbidden_', $encode_id );
$explode_arr1 = explode( 'forbidden_', $encode_id1 );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[ 1 ] ) && (int) $explode_arr[ 1 ] > 0 &&
     isset( $explode_arr1 ) && ! empty( $explode_arr1 ) && isset( $explode_arr1[ 1 ] ) && (int) $explode_arr1[ 1 ] > 0 ) {
	$id               = (int) $explode_arr[ 1 ];
	$id1              = (int) $explode_arr1[ 1 ];
	$document_privacy = ( function_exists( 'bb_media_user_can_access' ) ) ? bb_media_user_can_access( $id1, 'video' ) : true;
	$can_view         = isset( $document_privacy[ 'can_view' ] ) && true === (bool) $document_privacy[ 'can_view' ];
	if ( $can_view ) {

		if ( '' !== $size ) {
			$file               = image_get_intermediate_size( $id, $size );
			$attached_file_info = pathinfo( get_attached_file( $id ) );
			if ( $file && ! empty( $file['file'] ) && ! empty( $attached_file_info['dirname'] ) ) {
				$file_path       = $attached_file_info['dirname'];
				$file_path       = $file_path . '/' . $file['file'];
				$output_file_src = $file_path;
			} else {
				$output_file_src = bb_core_scaled_attachment_path( $id );
			}
		} else {
			$output_file_src = bb_core_scaled_attachment_path( $id );
		}

		$type = get_post_mime_type( $id );

		if ( ! file_exists( $output_file_src ) ) {
			echo '// Silence is golden.';
			exit();
		}

		// Clear all output buffer
		while ( ob_get_level() ) {
		    ob_end_clean();
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
