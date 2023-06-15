<?php
/**
 * Video functions
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Video component
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $scripts The array of scripts to register.
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
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_video_enqueue_scripts() {

	if ( bp_is_user_video() || bp_is_single_video_album() || bp_is_single_album() || bp_is_video_directory() || bp_is_activity_component() || bp_is_group_activity() || bp_is_group_video() || bp_is_group_video_albums() || bp_is_group_messages() || bp_is_messages_component() || bp_is_forums_video_support_enabled() ) {

		if ( bp_is_profile_video_support_enabled() || bp_is_group_video_support_enabled() || bp_is_group_albums_support_enabled() || bp_is_messages_video_support_enabled() || bp_is_group_messages() ) {

			wp_enqueue_script( 'bp-media-dropzone' );
			wp_enqueue_style( 'bp-media-videojs-css' );
			wp_enqueue_script( 'bp-media-videojs' );
			wp_enqueue_script( 'bp-media-videojs-seek-buttons' );
			wp_enqueue_script( 'bp-media-videojs-flv' );
			wp_enqueue_script( 'bp-media-videojs-flash' );
			wp_enqueue_script( 'bp-nouveau-video' );
			wp_enqueue_script( 'bp-exif' );
		}
	}

}

/**
 * Localize the strings needed for the messages UI
 *
 * @since BuddyBoss 1.7.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts.
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_video_localize_scripts( $params = array() ) {

	$album_id         = 0;
	$type             = '';
	$user_id          = bp_loggedin_user_id();
	$group_id         = 0;
	$move_to_id_popup = $user_id;
	if ( bp_is_group_video() || bp_is_group_video_albums() ) {
		$album_id         = (int) bp_action_variable( 1 );
		$type             = 'group';
		$group_id         = ( bp_get_current_group_id() ) ? bp_get_current_group_id() : '';
		$move_to_id_popup = $group_id;
	} elseif ( bp_is_user_media() || bp_is_user_video() || bp_is_user_albums() ) {
		$album_id         = (int) bp_action_variable( 0 );
		$type             = 'profile';
		$move_to_id_popup = $user_id;
	} elseif ( bp_is_video_directory() ) {
		$album_id = 0;
		$type     = 'profile';
	}

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
		'profile_video'                      => bp_is_profile_video_support_enabled() && bb_video_user_can_upload( bp_loggedin_user_id(), 0 ),
		'profile_album'                      => bp_is_profile_albums_support_enabled(),
		'group_video'                        => bp_is_group_video_support_enabled() && ( bb_video_user_can_upload( bp_loggedin_user_id(), ( bp_is_active( 'groups' ) && bp_is_group_single() ? bp_get_current_group_id() : $group_id ) ) || bp_is_activity_directory() ),
		'group_album'                        => bp_is_group_albums_support_enabled(),
		'messages_video'                     => bp_is_messages_video_support_enabled() && bb_user_can_create_video(),
		'messages_video_active'              => bp_is_messages_video_support_enabled(),
		'dropzone_video_message'             => sprintf( '<strong>%s</strong> %s', esc_html__( 'Add Videos', 'buddyboss' ), esc_html__( 'Or drag and drop', 'buddyboss' ) ),
		'dropzone_video_thumbnail_message'   => __( 'Upload thumbnail', 'buddyboss' ),
		'video_select_error'                 => __( 'This file type is not supported for video uploads.', 'buddyboss' ),
		'empty_video_type'                   => __( 'Empty video file will not be uploaded.', 'buddyboss' ),
		'invalid_video_type'                 => __( 'Unable to upload the file', 'buddyboss' ),
		'video_size_error_header'            => __( 'File too large ', 'buddyboss' ),
		'video_size_error_description'       => __( 'This file type is too large.', 'buddyboss' ),
		'dictFileTooBig'                     => __( 'File is too large ({{filesize}} MB). Max filesize: {{maxFilesize}} MB.', 'buddyboss' ),
		'maxFiles'                           => bp_video_allowed_upload_video_per_batch(),
		'is_video_directory'                 => ( bp_is_video_directory() ) ? 'yes' : 'no',
		'create_album_error_title'           => __( 'Please enter title of album', 'buddyboss' ),
		'cover_video_size_error_header'      => __( 'Unable to reposition the image ', 'buddyboss' ),
		'cover_video_size_error_description' => __( 'To reposition your cover video, please upload a larger image and then try again.', 'buddyboss' ),
		'current_album'                      => $album_id,
		'current_type'                       => $type,
		'move_to_id_popup'                   => $move_to_id_popup,
		'video_dict_file_exceeded'           => sprintf( /* translators: 1: per batch */ __( 'You are allowed to upload only %s videos at a time.', 'buddyboss' ), bp_core_number_format( bp_video_allowed_upload_video_per_batch() ) ),
		'thumb_dict_file_exceeded'           => __( 'You are allowed to upload only 1 thumb at a time.', 'buddyboss' ),
		'dictInvalidFileType'                => __( 'Please upload only the following file types: ', 'buddyboss' ) . '<br /><div class="bb-allowed-file-types">' . implode( ', ', array_unique( $allowed ) ) . '</div>',
		'is_ffpmeg_installed'                => bb_video_is_ffmpeg_installed(),
		'generating_thumb'                   => 'Generating thumbnail…',
		'dictCancelUploadConfirmation'       => __( 'Are you sure you want to cancel this upload?', 'buddyboss' ),
	);

	if ( bp_is_single_video_album() ) {
		$params['video']['album_id'] = (int) bp_action_variable( 0 );
	}

	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$params['video']['group_id'] = bp_get_current_group_id();
	}

	$params['video']['i18n_strings'] = array(
		'select'                  => __( 'Select', 'buddyboss' ),
		'unselect'                => __( 'Unselect', 'buddyboss' ),
		'selectall'               => __( 'Select All', 'buddyboss' ),
		'unselectall'             => __( 'Unselect All', 'buddyboss' ),
		'no_videos_found'         => __( 'Sorry, no videos were found', 'buddyboss' ),
		'upload'                  => __( 'Upload', 'buddyboss' ),
		'upload_thumb'            => __( 'Change Thumbnail', 'buddyboss' ),
		'uploading'               => __( 'Uploading', 'buddyboss' ),
		'upload_status'           => __( '%1$d out of %2$d uploaded', 'buddyboss' ), // phpcs:ignore
		'album_delete_confirm'    => __( 'Are you sure you want to delete this album? Videos in this album will also be deleted.', 'buddyboss' ),
		'album_delete_error'      => __( 'There was a problem deleting the album.', 'buddyboss' ),
		'video_delete_confirm'    => __( 'Are you sure you want to delete this video?', 'buddyboss' ),
		'video_enlarge_text'      => __( 'Enlarge', 'buddyboss' ),
		'video_fullscreen_text'   => __( 'Full screen', 'buddyboss' ),
		'video_play_text'         => __( 'Play', 'buddyboss' ),
		'video_pause_text'        => __( 'Pause', 'buddyboss' ),
		'video_uploaded_text'     => __( 'Uploaded', 'buddyboss' ),
		'video_volume_text'       => __( 'Volume', 'buddyboss' ),
		'video_miniplayer_text'   => __( 'Miniplayer', 'buddyboss' ),
		'video_speed_text'        => __( 'Speed', 'buddyboss' ),
		'video_skip_back_text'    => __( 'Step Back (5)', 'buddyboss' ),
		'video_skip_forward_text' => __( 'Step Forward (5)', 'buddyboss' ),
		'video_picture_in_text'   => __( 'This video is playing in the miniplayer.', 'buddyboss' ),
	);

	return $params;
}

/**
 * Get the nav items for the Video directory
 *
 * @since BuddyBoss 1.7.0
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

	if ( is_user_logged_in() && bp_is_group_video_support_enabled() && bp_is_active( 'groups' ) ) {
		$nav_items['group'] = array(
			'component' => 'video',
			'slug'      => 'groups', // slug is used because BP_Core_Nav requires it, but it's the scope.
			'li_class'  => array(),
			'link'      => bp_loggedin_user_domain() . bp_get_video_slug() . '/groups-video/',
			'text'      => __( 'My Groups', 'buddyboss' ),
			'count'     => bp_video_get_user_total_group_video_count(),
			'position'  => 15,
		);
	}

	/**
	 * Use this filter to introduce your custom nav items for the video directory.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $nav_items The list of the video directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_video_directory_nav_items', $nav_items );
}

/**
 * Download the file.
 *
 * @param int    $attachment_id id of the attachment.
 * @param string $type          type of download.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_download_file( $attachment_id, $type = 'video' ) {

	// Add action to prevent issues in IE.
	add_action( 'nocache_headers', 'bp_video_ie_nocache_headers_fix' );

	if ( 'video' === $type ) {

		$the_file = wp_get_attachment_url( $attachment_id );

		if ( ! $the_file ) {
			return;
		}

		// clean the file url.
		$the_file = stripslashes( trim( $the_file ) );

		// get filename.
		$file_name = basename( $the_file );

		bp_video_download_file_force( $the_file, strtok( $file_name, '?' ) );
	}

}

/**
 * Edit button alter when video activity other than activity page.
 *
 * @param array $buttons     Array of Buttons visible on activity entry.
 * @param int   $activity_id Activity ID.
 *
 * @return mixed
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_video_activity_edit_button( $buttons, $activity_id ) {
	if (
		isset( $buttons['activity_edit'] ) &&
		(
			bp_is_video_component() ||
			! bp_is_activity_component()
		) &&
		! empty( $_REQUEST['action'] ) && // phpcs:ignore
		(
			'video_get_activity' === $_REQUEST['action'] || // phpcs:ignore
			'video_get_video_description' === $_REQUEST['action'] // phpcs:ignore
		)
	) { // phpcs:ignore
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
 * @return array|mixed|string|void
 * @since BuddyBoss 1.7.0
 */
function bp_video_allowed_video_type() {

	$extension_lists = array(
		'bb_vid_1' => array(
			'extension'   => '.mp4',
			'mime_type'   => 'video/mp4',
			'description' => __( 'MP4', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_2' => array(
			'extension'   => '.webm',
			'mime_type'   => 'video/webm',
			'description' => __( 'WebM', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_3' => array(
			'extension'   => '.ogg',
			'mime_type'   => 'video/ogg',
			'description' => __( 'Ogg', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
		'bb_vid_4' => array(
			'extension'   => '.mov',
			'mime_type'   => 'video/quicktime',
			'description' => __( 'Quicktime', 'buddyboss' ),
			'is_default'  => 1,
			'is_active'   => 1,
			'icon'        => '',
		),
	);

	/**
	 * Filter get video support extension.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$extension_lists = apply_filters( 'bp_video_allowed_video_type', $extension_lists );

	return $extension_lists;
}
