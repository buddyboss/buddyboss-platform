<?php
/**
 * Video functions
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Video component
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific video scripts.
 */
function bp_nouveau_video_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge(
		$scripts,
		array(
			'bp-nouveau-video' => array(
				'file'         => 'js/buddypress-video%s.js',
				'dependencies' => array( 'bp-nouveau', 'bp-nouveau-media' ),
				'footer'       => true,
			),
		)
	);
}

/**
 * Enqueue the video scripts
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_video_enqueue_scripts() {

	if ( bp_is_user_video() ||
		 bp_is_single_video_album() ||
		 bp_is_video_directory() ||
		 bp_is_activity_component() ||
		 bp_is_group_activity() ||
		 bp_is_group_video() ||
		 bp_is_group_video_albums() ||
		 bp_is_group_messages() ||
		 bp_is_messages_component()
	) {

		if ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() || bp_is_group_albums_support_enabled() || bp_is_messages_video_support_enabled() || bp_is_group_messages() ) {
			wp_enqueue_script( 'bp-media-dropzone' );
			wp_enqueue_script( 'bp-nouveau-video' );
			wp_enqueue_script( 'bp-exif' );
		}
	}

}

/**
 * Localize the strings needed for the messages UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_video_localize_scripts( $params = array() ) {

	$extensions     = array();
	$mime_types     = array();
	$all_extensions = bp_video_extensions_list();
	foreach ( $all_extensions as $extension ) {
		if ( isset( $extension['is_active'] ) && true === (bool) $extension['is_active'] ) {
			$mime_types[] = $extension['mime_type'];
			$extensions[] = $extension['extension'];
		}
	}

	$allowed = array_merge( $mime_types, $extensions );

	// initialize video vars because it is used globally.
	$params['video'] = array(
		'max_upload_size'                    => bp_video_file_upload_max_size(),
		'video_type'                         => implode( ',', array_unique( $allowed ) ),
		'profile_video'                      => bp_is_profile_video_support_enabled(),
		'profile_album'                      => bp_is_profile_albums_support_enabled(),
		'group_video'                        => bp_is_group_video_support_enabled(),
		'group_album'                        => bp_is_group_albums_support_enabled(),
		'messages_video'                     => bp_is_messages_video_support_enabled(),
		'dropzone_video_message'             => __( 'Drop videos here to upload', 'buddyboss' ),
		'video_select_error'                 => __( 'This file type is not supported for video uploads.', 'buddyboss' ),
		'empty_video_type'                   => __( 'Empty video file will not be uploaded.', 'buddyboss' ),
		'invalid_video_type'                 => __( 'Unable to upload the file', 'buddyboss' ),
		'video_size_error_header'            => __( 'File too large ', 'buddyboss' ),
		'video_size_error_description'       => __( 'This file type is too large.', 'buddyboss' ),
		'dictFileTooBig'                     => __( 'File is too large ({{filesize}} MB). Max filesize: {{maxFilesize}} MB.', 'buddyboss' ),
		'maxFiles'                           => apply_filters( 'bp_video_upload_chunk_limit', 10 ),
		'cover_video_size_error_header'      => __( 'Unable to reposition the image ', 'buddyboss' ),
		'cover_video_size_error_description' => __( 'To reposition your cover video, please upload a larger image and then try again.', 'buddyboss' ),
	);

	if ( bp_is_single_video_album() ) {
		$params['video']['album_id'] = (int) bp_action_variable( 0 );
	}

	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$params['video']['group_id'] = bp_get_current_group_id();
	}

	$params['video']['i18n_strings'] = array(
		'select'               => __( 'Select', 'buddyboss' ),
		'unselect'             => __( 'Unselect', 'buddyboss' ),
		'selectall'            => __( 'Select All', 'buddyboss' ),
		'unselectall'          => __( 'Unselect All', 'buddyboss' ),
		'no_videos_found'      => __( 'Sorry, no videos were found', 'buddyboss' ),
		'upload'               => __( 'Upload', 'buddyboss' ),
		'uploading'            => __( 'Uploading', 'buddyboss' ),
		'upload_status'        => __( '%1$d out of %2$d uploaded', 'buddyboss' ),
		'album_delete_confirm' => __( 'Are you sure you want to delete this album? Videos in this album will also be deleted.', 'buddyboss' ),
		'album_delete_error'   => __( 'There was a problem deleting the album.', 'buddyboss' ),
	);

	return $params;
}

/**
 * Get the nav items for the Video directory
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array An associative array of nav items.
 */
function bp_nouveau_get_video_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'video',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope.
		'li_class'  => array(),
		'link'      => bp_get_video_directory_permalink(),
		'text'      => __( 'All Videos', 'buddyboss' ),
		'count'     => bp_get_total_video_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() && bp_is_profile_video_support_enabled() ) {
		$nav_items['personal'] = array(
			'component' => 'video',
			'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope.
			'li_class'  => array(),
			'link'      => bp_loggedin_user_domain() . bp_get_video_slug() . '/my-video/',
			'text'      => __( 'My Videos', 'buddyboss' ),
			'count'     => bp_video_get_total_video_count(),
			'position'  => 15,
		);
	}

	if ( is_user_logged_in() && bp_is_group_video_support_enabled() ) {
		$nav_items['group'] = array(
			'component' => 'video',
			'slug'      => 'groups', // slug is used because BP_Core_Nav requires it, but it's the scope.
			'li_class'  => array(),
			'link'      => bp_loggedin_user_domain() . bp_get_document_slug() . '/groups-video/',
			'text'      => __( 'My Groups', 'buddyboss' ),
			'count'     => bp_video_get_user_total_group_video_count(),
			'position'  => 15,
		);
	}

	/**
	 * Use this filter to introduce your custom nav items for the video directory.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $nav_items The list of the video directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_video_directory_nav_items', $nav_items );
}

function bp_video_download_file( $attachment_id, $type = 'video' ) {

	// Add action to prevent issues in IE.
	add_action( 'nocache_headers', 'bp_video_ie_nocache_headers_fix' );

	if ( 'video' === $type ) {

		$the_file = wp_get_attachment_url( $attachment_id );

		if ( ! $the_file ) {
			return;
		}

		// clean the file url.
		$file_url = stripslashes( trim( $the_file ) );

		// get filename.
		$file_name = basename( $the_file );

		bp_video_download_file_force( $the_file, $file_name );
	}

}

/**
 * Edit button alter when video activity other than activity page.
 *
 * @param array $buttons     Array of Buttons visible on activity entry.
 * @param int   $activity_id Activity ID.
 *
 * @return mixed
 * @since BuddyBoss 1.5.1
 */
function bp_nouveau_video_activity_edit_button( $buttons, $activity_id ) {
	if ( isset( $buttons['activity_edit'] ) && ( bp_is_video_component() || ! bp_is_activity_component() ) && ! empty( $_REQUEST['action'] ) && 'video_get_activity' === $_REQUEST['action'] ) {
		$activity = new BP_Activity_Activity( $activity_id );

		if ( ! empty( $activity->id ) && 'video' !== $activity->privacy ) {
			$buttons['activity_edit']['button_attr']['href'] = bp_activity_get_permalink( $activity_id ) . 'edit';

			$classes  = explode( ' ', $buttons['activity_edit']['button_attr']['class'] );
			$edit_key = array_search( 'edit', $classes, true );
			if ( ! empty( $edit_key ) ) {
				unset( $classes[ $edit_key ] );
			}
			$buttons['activity_edit']['button_attr']['class'] = implode( ' ', $classes );
		}
	}

	return $buttons;
}

/**
 * Function get video support extension.
 *
 * @param string $format
 *
 * @return array|mixed|string|void
 */
function bp_video_allowed_video_type() {

	$extension_lists = array(
		'bb_vid_1'  => array(
			'extension'   => '.flv',
			'mime_type'   => 'video/x-flv',
			'description' => __( 'Flash', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_2'  => array(
			'extension'   => '.mp4',
			'mime_type'   => 'video/mp4',
			'description' => __( 'MPEG-4', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_3'  => array(
			'extension'   => '.m3u8',
			'mime_type'   => 'application/x-mpegURL',
			'description' => __( 'iPhone Index', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_4'  => array(
			'extension'   => '.ts',
			'mime_type'   => 'video/MP2T',
			'description' => __( 'iPhone Segment', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_5'  => array(
			'extension'   => '.3gp',
			'mime_type'   => 'video/3gpp',
			'description' => __( '3GP Mobile', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_6'  => array(
			'extension'   => '.mov',
			'mime_type'   => 'video/quicktime',
			'description' => __( 'QuickTime', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_7'  => array(
			'extension'   => '.avi',
			'mime_type'   => 'video/x-msvideo',
			'description' => __( 'A/V Interleave', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_8'  => array(
			'extension'   => '.wmv',
			'mime_type'   => 'video/x-ms-wmv',
			'description' => __( 'Windows Media', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_9'  => array(
			'extension'   => '.webm',
			'mime_type'   => 'video/webm',
			'description' => __( 'Open Web Media Project', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_10' => array(
			'extension'   => '.m4v',
			'mime_type'   => 'video/x-m4v',
			'description' => __( 'M4v', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_11' => array(
			'extension'   => '.qt',
			'mime_type'   => 'video/quicktime',
			'description' => __( 'Quicktime', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_12' => array(
			'extension'   => '.mpg',
			'mime_type'   => 'video/mpeg',
			'description' => __( 'MPEG', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_13' => array(
			'extension'   => '.asf',
			'mime_type'   => 'video/x-ms-asf',
			'description' => __( 'Microsoft Advanced Systems Format (ASF)', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_14' => array(
			'extension'   => '.vob',
			'mime_type'   => 'video/dvd',
			'description' => __( 'VOB', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_15' => array(
			'extension'   => '.vob',
			'mime_type'   => 'video/mpeg',
			'description' => __( 'VOB', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_16' => array(
			'extension'   => '.vob',
			'mime_type'   => 'video/x-ms-vob',
			'description' => __( 'VOB', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_17' => array(
			'extension'   => '.mkv',
			'mime_type'   => 'video/x-matroska',
			'description' => __( 'Matroska', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
	);

	$extension_lists = apply_filters( 'bp_video_allowed_video_type', $extension_lists );

	return $extension_lists;
}
