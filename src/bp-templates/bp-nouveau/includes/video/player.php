<?php

if ( empty( get_query_var( 'video-preview' ) ) && empty( get_query_var( 'id1' ) ) ) {
	echo '// Silence is golden.';
	exit();
}

$encode_id    = base64_decode( get_query_var( 'video-preview' ) );
$encode_id1   = base64_decode( get_query_var( 'id1' ) );
$explode_arr  = explode( 'forbidden_', $encode_id );
$explode_arr1 = explode( 'forbidden_', $encode_id1 );

if ( isset( $explode_arr ) && ! empty( $explode_arr ) && isset( $explode_arr[1] ) && (int) $explode_arr[1] > 0 &&
     isset( $explode_arr1 ) && ! empty( $explode_arr1 ) && isset( $explode_arr1[1] ) && (int) $explode_arr1[1] > 0 ) {
	$attachment_id = (int) $explode_arr[1];
	$id1           = (int) $explode_arr1[1];
	$video_privacy = ( function_exists( 'bb_media_user_can_access' ) ) ? bb_media_user_can_access( $id1, 'video' ) : true;
	$can_view      = isset( $video_privacy['can_view'] ) && true === (bool) $video_privacy['can_view'];
	if ( $can_view ) {
		$type            = get_post_mime_type( $attachment_id );
		$output_file_src = bb_core_scaled_attachment_path( $attachment_id );

		if ( ! file_exists( $output_file_src ) ) {
			echo '// Silence is golden.';
			exit();
		}

		$stream = new BP_Video_Stream( $output_file_src, $id );
		$stream->start();

	} else {
		echo '// Silence is golden.';
		exit();
	}
} else {
	echo '// Silence is golden.';
	exit();
}
