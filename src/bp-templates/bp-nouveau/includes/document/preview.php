<?php
/**
 * Display the preview of the documents.
 */

if ( ! defined( 'ABSPATH' ) ) { //phpcs:ignore
	$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] ); //phpcs:ignore
	include_once $parse_uri[0] . '/wp-load.php';
}

define( 'WP_USE_THEMES', true );

global $wpdb, $bp;

if ( empty( $_REQUEST ) && empty( $_REQUEST['id'] ) && empty( $_REQUEST['id1'] ) ) { //phpcs:ignore
	echo '// Silence is golden.';
	exit();
}

$encode_attachment_id    = base64_decode( $_REQUEST['id'] ); //phpcs:ignore
$encode_document_id      = base64_decode( $_REQUEST['id1'] ); //phpcs:ignore
$encode_is_aws_sanitized = filter_input( INPUT_GET, 'id2', FILTER_SANITIZE_STRING );
$encode_is_aws           = ( $encode_is_aws_sanitized ) ? base64_decode( $encode_is_aws_sanitized ) : ''; //phpcs:ignore
$explode_attachment_arr  = explode( 'forbidden_', $encode_attachment_id );
$explode_document_arr    = explode( 'forbidden_', $encode_document_id );

if ( isset( $explode_attachment_arr ) && ! empty( $explode_attachment_arr ) && isset( $explode_attachment_arr[1] ) && (int) $explode_attachment_arr[1] > 0 &&
	isset( $explode_document_arr ) && ! empty( $explode_document_arr ) && isset( $explode_document_arr[1] ) && (int) $explode_document_arr[1] > 0 ) {
	$attachment_id    = (int) $explode_attachment_arr[1];
	$document_id      = (int) $explode_document_arr[1];
	$document_privacy = ( function_exists( 'bp_document_user_can_manage_document' ) ) ? bp_document_user_can_manage_document( $document_id, bp_loggedin_user_id() ) : true;
	$can_view         = true === (bool) $document_privacy['can_view'];


	if ( $can_view && wp_attachment_is_image( $attachment_id ) ) {
		$mime_type       = get_post_mime_type( $attachment_id );
		$output_file_src = bp_document_scaled_image_path( $attachment_id );
		if ( ! file_exists( $output_file_src ) ) {
			echo '// Silence is golden.';
			exit();
		}
		header( "Content-Type: $mime_type" );
		readfile( "$output_file_src" ); //phpcs:ignore
		// WP OFFLOAD MEDIA Support.
	} elseif ( $can_view && '' !== $encode_is_aws ) {
		if ( wp_get_attachment_image_url( $attachment_id ) ) {
			$mime_type       = get_post_mime_type( $attachment_id );
			$output_file_src = wp_get_attachment_url( $attachment_id );
			header( "Content-Type: $mime_type" );
			readfile( "$output_file_src" ); //phpcs:ignore
		}
	} else {
		echo '// Silence is golden.';
		exit();
	}
} else {
	echo '// Silence is golden.';
	exit();
}

