<?php
/**
 * Media functions
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Media component
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific media scripts.
 */
function bp_nouveau_media_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-media' => array(
			'file'         => 'js/buddypress-media%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'footer'       => true,
		),
		'bp-nouveau-media-theatre' => array(
			'file'         => 'js/buddypress-media-theatre%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'version'      => bp_get_version(),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the media scripts
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_media_enqueue_scripts() {
	wp_enqueue_script( 'bp-nouveau-media' );
	wp_enqueue_script( 'bp-nouveau-media-theatre' );
	wp_enqueue_script( 'giphy' );
	wp_enqueue_script( 'emojionearea' );
	wp_enqueue_style( 'emojionearea' );
	wp_enqueue_script( 'isInViewport' );
}

/**
 * Localize the strings needed for the messages UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_media_localize_scripts( $params = array() ) {

	$params['media'] = array(
		'max_upload_size' => bp_media_file_upload_max_size(),
		'profile_media'   => bp_is_profile_media_support_enabled(),
		'profile_album'   => bp_is_profile_albums_support_enabled(),
		'group_media'     => bp_is_group_media_support_enabled(),
		'group_album'     => bp_is_group_albums_support_enabled(),
		'messages_media'  => bp_is_messages_media_support_enabled(),
	);

	if ( bp_is_single_album() ) {
		$params['media']['album_id'] = (int) bp_action_variable( 0 );
	}

	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$params['media']['group_id'] = bp_get_current_group_id();
    }

	$params['media']['emoji']            = array(
		'profile'  => bp_is_profiles_emoji_support_enabled(),
		'groups'   => bp_is_groups_emoji_support_enabled(),
		'messages' => bp_is_messages_emoji_support_enabled(),
		'forums'   => bp_is_forums_emoji_support_enabled(),
	);
	$params['media']['emoji_filter_url'] = buddypress()->plugin_url . 'bp-core/images/emojifilter/';

	$params['media']['gif']         = array(
		'profile'  => bp_is_profiles_gif_support_enabled(),
		'groups'   => bp_is_groups_gif_support_enabled(),
		'messages' => bp_is_messages_gif_support_enabled(),
		'forums'   => bp_is_forums_gif_support_enabled(),
	);
	$params['media']['gif_api_key'] = bp_media_get_gif_api_key();

    if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {

        // check if topic edit
	    if ( bbp_is_topic_edit() ) {
		    $params['media']['bbp_is_topic_edit'] = true;

		    $media_ids = get_post_meta( bbp_get_topic_id(), 'bp_media_ids', true );
		    if ( ! empty( $media_ids ) && bp_has_media(
				    array(
					    'include'  => $media_ids,
					    'order_by' => 'menu_order',
					    'sort'     => 'ASC'
				    ) ) ) {
			    $params['media']['topic_edit_media'] = array();
			    $index                               = 0;
			    while ( bp_media() ) {
				    bp_the_media();

				    $params['media']['topic_edit_media'][] = array(
					    'id'            => bp_get_media_id(),
					    'attachment_id' => bp_get_media_attachment_id(),
					    'name'          => bp_get_media_title(),
					    'thumb'         => bp_get_media_attachment_image_thumbnail(),
					    'url'           => bp_get_media_attachment_image(),
					    'menu_order'    => $index,
				    );
				    $index ++;
			    }
		    }

		    $gif_data = get_post_meta( bbp_get_topic_id(), '_gif_data', true );

		    if ( ! empty( $gif_data ) ) {
			    $preview_url = wp_get_attachment_url( $gif_data['still'] );
			    $video_url = wp_get_attachment_url( $gif_data['mp4'] );

			    $params['media']['topic_edit_gif_data'] = array(
				    'preview_url' => $preview_url,
				    'video_url' => $video_url,
				    'gif_raw_data' => get_post_meta( bbp_get_topic_id(), '_gif_raw_data', true ),
			    );
		    }
	    }

        // check if reply edit
        if ( bbp_is_reply_edit() ) {
	        $params['media']['bbp_is_reply_edit'] = true;

	        $media_ids = get_post_meta( bbp_get_reply_id(), 'bp_media_ids', true );
	        if ( ! empty( $media_ids ) && bp_has_media(
			        array(
				        'include'  => $media_ids,
				        'order_by' => 'menu_order',
				        'sort'     => 'ASC'
			        ) ) ) {
		        $params['media']['reply_edit_media'] = array();
		        $index                               = 0;
		        while ( bp_media() ) {
			        bp_the_media();

			        $params['media']['reply_edit_media'][] = array(
				        'id'            => bp_get_media_id(),
				        'attachment_id' => bp_get_media_attachment_id(),
				        'name'          => bp_get_media_title(),
				        'thumb'         => bp_get_media_attachment_image_thumbnail(),
				        'url'           => bp_get_media_attachment_image(),
				        'menu_order'    => $index,
			        );
			        $index ++;
		        }
	        }

	        $gif_data = get_post_meta( bbp_get_reply_id(), '_gif_data', true );

	        if ( ! empty( $gif_data ) ) {
		        $preview_url = wp_get_attachment_url( $gif_data['still'] );
		        $video_url = wp_get_attachment_url( $gif_data['mp4'] );

		        $params['media']['reply_edit_gif_data'] = array(
		        	'preview_url' => $preview_url,
		        	'video_url' => $video_url,
			        'gif_raw_data' => get_post_meta( bbp_get_reply_id(), '_gif_raw_data', true ),
		        );
	        }
        }

        // check if forum edit
	    if ( bbp_is_forum_edit() ) {
		    $params['media']['bbp_is_forum_edit'] = true;

		    $media_ids = get_post_meta( bbp_get_forum_id(), 'bp_media_ids', true );
		    if ( ! empty( $media_ids ) && bp_has_media(
				    array(
					    'include'  => $media_ids,
					    'order_by' => 'menu_order',
					    'sort'     => 'ASC'
				    ) ) ) {
			    $params['media']['forum_edit_media'] = array();
			    $index                               = 0;
			    while ( bp_media() ) {
				    bp_the_media();

				    $params['media']['forum_edit_media'][] = array(
					    'id'            => bp_get_media_id(),
					    'attachment_id' => bp_get_media_attachment_id(),
					    'name'          => bp_get_media_title(),
					    'thumb'         => bp_get_media_attachment_image_thumbnail(),
					    'url'           => bp_get_media_attachment_image(),
					    'menu_order'    => $index,
				    );
				    $index ++;
			    }
		    }

		    $gif_data = get_post_meta( bbp_get_forum_id(), '_gif_data', true );

		    if ( ! empty( $gif_data ) ) {
			    $preview_url = wp_get_attachment_url( $gif_data['still'] );
			    $video_url = wp_get_attachment_url( $gif_data['mp4'] );

			    $params['media']['forum_edit_gif_data'] = array(
				    'preview_url' => $preview_url,
				    'video_url' => $video_url,
				    'gif_raw_data' => get_post_meta( bbp_get_forum_id(), '_gif_raw_data', true ),
			    );
		    }
        }
    }

	$params['media']['i18n_strings'] = array(
		'select'               => __( 'Select', 'buddyboss' ),
		'unselect'             => __( 'Unselect', 'buddyboss' ),
		'selectall'            => __( 'Select All', 'buddyboss' ),
		'unselectall'          => __( 'Unselect All', 'buddyboss' ),
		'no_photos_found'      => __( 'Sorry, no photos were found', 'buddyboss' ),
		'upload'               => __( 'Upload', 'buddyboss' ),
		'uploading'            => __( 'Uploading', 'buddyboss' ),
		'upload_status'        => __( '%d out of %d uploaded', 'buddyboss' ),
		'album_delete_confirm' => __( 'Are you sure you want to delete this album? Photos in this album will also be deleted.', 'buddyboss' ),
		'album_delete_error'   => __( 'There was a problem deleting the album.', 'buddyboss' ),
	);

	return $params;
}

/**
 * Get the nav items for the Media directory
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array An associative array of nav items.
 */
function bp_nouveau_get_media_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'media',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array(),
		'link'      => bp_get_media_directory_permalink(),
		'text'      => __( 'All Photos', 'buddyboss' ),
		'count'     => bp_get_total_media_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {
		$nav_items['personal'] = array(
			'component' => 'media',
			'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope
			'li_class'  => array(),
			'link'      => bp_loggedin_user_domain() . bp_get_media_slug() . '/my-media/',
			'text'      => __( 'My Photos', 'buddyboss' ),
			'count'     => bp_media_get_total_media_count(),
			'position'  => 15,
		);
	}

	/**
	 * Use this filter to introduce your custom nav items for the media directory.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $nav_items The list of the media directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_media_directory_nav_items', $nav_items );
}