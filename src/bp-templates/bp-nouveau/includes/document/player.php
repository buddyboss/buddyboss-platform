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

$encode_id    = base64_decode( $_REQUEST['id'] );
$encode_id1   = base64_decode( $_REQUEST['id1'] );
$explode_arr  = explode( 'forbidden_', $encode_id );
$explode_arr1 = explode( 'forbidden_', $encode_id1 );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 &&
	 isset( $explode_arr1 ) && ! empty( $explode_arr1 ) && isset( $explode_arr1[1] ) && (int) $explode_arr1[1] > 0 ) {
	$id               = (int) $explode_arr[1];
	$id1              = (int) $explode_arr1[1];
	$document_privacy = ( function_exists( 'bp_document_user_can_manage_document' ) ) ? bp_document_user_can_manage_document( $id1, bp_loggedin_user_id() ) : true ;
	$can_view         = ( true === (bool) $document_privacy['can_view'] ) ? true : false;
	if ( $can_view ) {
		$type            = get_post_mime_type( $id );
		$output_file_src = bp_document_scaled_image_path( $id );

		if ( ! file_exists( $output_file_src ) ) {
			echo '// Silence is golden.';
			exit();
		}

		$fp     = @fopen( $output_file_src, 'rb' );
		$size   = filesize( $output_file_src ); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		header( "Content-Type: $type" );
		header( "Accept-Ranges: 0-$length" );
		header( 'Accept-Ranges: bytes' );
		if ( isset( $_SERVER['HTTP_RANGE'] ) ) {
			$c_start         = $start;
			$c_end           = $end;
			list( , $range ) = explode( '=', $_SERVER['HTTP_RANGE'], 2 );
			if ( strpos( $range, ',' ) !== false ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( "Content-Range: bytes $start-$end/$size" );
				exit;
			}
			if ( $range == '-' ) {
				$c_start = $size - substr( $range, 1 );
			} else {
				$range   = explode( '-', $range );
				$c_start = $range[0];
				$c_end   = ( isset( $range[1] ) && is_numeric( $range[1] ) ) ? $range[1] : $size;
			}
			$c_end = ( $c_end > $end ) ? $end : $c_end;
			if ( $c_start > $c_end || $c_start > $size - 1 || $c_end >= $size ) {
				header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
				header( "Content-Range: bytes $start-$end/$size" );
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1;
			fseek( $fp, $start );
			header( 'HTTP/1.1 206 Partial Content' );
		}
		header( "Content-Range: bytes $start-$end/$size" );
		header( 'Content-Length: ' . $length );
		$buffer = 1024 * 8;
		while ( ! feof( $fp ) && ( $p = ftell( $fp ) ) <= $end ) {
			if ( $p + $buffer > $end ) {
				$buffer = $end - $p + 1;
			}
			set_time_limit( 0 );
			echo fread( $fp, $buffer );
			ob_flush();
		}
		fclose( $fp );
	} else {
		echo '// Silence is golden.';
		exit();
	}
} else {
	echo '// Silence is golden.';
	exit();
}

