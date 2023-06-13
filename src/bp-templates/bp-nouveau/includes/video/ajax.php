<?php
/**
 * Video Ajax functions
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.7.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	function() {
		$ajax_actions = array(
			array(
				'video_filter' => array(
					'function' => 'bp_nouveau_ajax_object_template_loader',
					'nopriv'   => true,
				),
			),
			array(
				'video_albums_loader' => array(
					'function' => 'bp_nouveau_ajax_video_albums_loader',
					'nopriv'   => true,
				),
			),
			array(
				'video_get_video_description' => array(
					'function' => 'bp_nouveau_ajax_video_get_video_description',
					'nopriv'   => true,
				),
			),
			array(
				'video_upload' => array(
					'function' => 'bp_nouveau_ajax_video_upload',
					'nopriv'   => true,
				),
			),
			array(
				'video_thumbnail_upload' => array(
					'function' => 'bp_nouveau_ajax_video_thumbnail_upload',
					'nopriv'   => true,
				),
			),
			array(
				'video_save' => array(
					'function' => 'bp_nouveau_ajax_video_save',
					'nopriv'   => true,
				),
			),
			array(
				'video_delete' => array(
					'function' => 'bp_nouveau_ajax_video_delete',
					'nopriv'   => true,
				),
			),
			array(
				'video_move' => array(
					'function' => 'bp_nouveau_ajax_video_move',
					'nopriv'   => true,
				),
			),
			array(
				'video_move_to_album' => array(
					'function' => 'bp_nouveau_ajax_video_move_to_album',
					'nopriv'   => true,
				),
			),
			array(
				'video_album_save' => array(
					'function' => 'bp_nouveau_ajax_video_album_save',
					'nopriv'   => true,
				),
			),
			array(
				'video_album_delete' => array(
					'function' => 'bp_nouveau_ajax_video_album_delete',
					'nopriv'   => true,
				),
			),
			array(
				'video_get_activity' => array(
					'function' => 'bp_nouveau_ajax_video_get_activity',
					'nopriv'   => true,
				),
			),
			array(
				'video_delete_attachment' => array(
					'function' => 'bp_nouveau_ajax_video_delete_attachment',
					'nopriv'   => true,
				),
			),
			array(
				'video_update_privacy' => array(
					'function' => 'bp_nouveau_ajax_video_update_privacy',
					'nopriv'   => true,
				),
			),
			array(
				'video_description_save' => array(
					'function' => 'bp_nouveau_ajax_video_description_save',
					'nopriv'   => true,
				),
			),
			array(
				'video_thumbnail_delete_attachment' => array(
					'function' => 'bp_nouveau_ajax_video_thumbnail_delete_attachment',
					'nopriv'   => true,
				),
			),
			array(
				'video_get_edit_thumbnail_data' => array(
					'function' => 'bp_nouveau_ajax_video_get_edit_thumbnail_data',
					'nopriv'   => true,
				),
			),
			array(
				'video_thumbnail_save' => array(
					'function' => 'bp_nouveau_ajax_video_thumbnail_save',
					'nopriv'   => true,
				),
			),
			array(
				'video_thumbnail_delete' => array(
					'function' => 'bp_nouveau_ajax_video_thumbnail_delete',
					'nopriv'   => true,
				),
			),
		);

		foreach ( $ajax_actions as $ajax_action ) {
			$action = key( $ajax_action );

			add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

			if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
				add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
			}
		}
	},
	12
);

/**
 * Load the template loop for the albums object.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_albums_loader() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$page = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );

	$page = ! empty( $page ) ? $page : 1;

	ob_start();
	if ( bp_has_video_albums( array( 'page' => $page ) ) ) {
		while ( bp_video_album() ) {
			bp_the_video_album();
			bp_get_template_part( 'video/album-entry' );
		}

		if ( bp_video_album_has_more_items() ) : ?>

			<li class="load-more">
				<a class="button outline" href="<?php bp_video_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</li>

			<?php
		endif;
	}
	$albums = ob_get_contents();
	ob_end_clean();

	wp_send_json_success(
		array(
			'albums' => $albums,
		)
	);
}

/**
 * Upload a video via a POST request.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_upload() {
	$response = array(
		'feedback' => __( 'There was a problem when trying to upload this file.', 'buddyboss' ),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response, 500 );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Upload file.
	$result = bp_video_upload();

	if ( is_wp_error( $result ) ) {
		$response['feedback'] = $result->get_error_message();
		wp_send_json_error( $response, $result->get_error_code() );
	}

	wp_send_json_success( $result );
}

/**
 * Upload a video thumbnail via a POST request.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_thumbnail_upload() {
	$response = array(
		'feedback' => __( 'There was a problem when trying to upload this file.', 'buddyboss' ),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response, 500 );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Upload file.
	$result = bp_video_thumbnail_upload();

	if ( is_wp_error( $result ) ) {
		$response['feedback'] = $result->get_error_message();
		wp_send_json_error( $response, $result->get_error_code() );
	}

	wp_send_json_success( $result );
}

/**
 * Delete attachment with its files
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_thumbnail_delete_attachment() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

	if ( empty( $id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide attachment id to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete attachment with its meta.
	$deleted = wp_delete_attachment( $id, true );

	if ( ! $deleted ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success();
}

/**
 * Save video
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_SANITIZE_NUMBER_INT );

	if (
		( ( bp_is_my_profile() || bp_is_user_media() ) && empty( bb_user_can_create_video() ) ) ||
		( bp_is_active( 'groups' ) && ! empty( $group_id ) && ! groups_can_user_manage_video( bp_loggedin_user_id(), $group_id ) )
	) {
		wp_send_json_error( $response );
	}

	$videos = filter_input( INPUT_POST, 'videos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( empty( $videos ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please upload video before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$privacy = bb_filter_input_string( INPUT_POST, 'privacy' );
	$content = bb_filter_input_string( INPUT_POST, 'content' );

	// handle video uploaded.
	$video_ids = bp_video_add_handler( $videos, $privacy, $content );

	$video = '';
	if ( ! empty( $video_ids ) ) {
		ob_start();
		if ( bp_has_video( array( 'include' => implode( ',', $video_ids ) ) ) ) {
			while ( bp_video() ) {
				bp_the_video();
				bp_get_template_part( 'video/entry' );
			}
		}
		$video = ob_get_contents();
		ob_end_clean();
	}

	$video_personal_count = 0;
	$video_group_count    = 0;
	$video_all_count      = 0;
	if ( bp_is_user_video() || ( ( bp_is_profile_albums_support_enabled() || bp_is_group_albums_support_enabled() ) && bp_is_single_album() ) ) {
		add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
		bp_has_video( bp_ajax_querystring( 'video' ) );
		$video_personal_count = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
		remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
	}

	if ( bp_is_group_video() ) {
		$video_group_count = bp_video_get_total_group_video_count();
	}

	if ( bp_is_video_directory() ) {

		add_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );
		bp_has_video( bp_ajax_querystring( 'video' ) );
		$video_all_count = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
		remove_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );

		add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
		bp_has_video( bp_ajax_querystring( 'video' ) );
		$video_personal_count = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
		remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );

		add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );
		bp_has_video( bp_ajax_querystring( 'groups' ) );
		$video_group_count = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
		remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );

	}

	wp_send_json_success(
		array(
			'video'                => $video,
			'video_personal_count' => $video_personal_count,
			'video_group_count'    => $video_group_count,
			'video_all_count'      => $video_all_count,
		)
	);
}

/**
 * Delete video
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_delete() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$video       = filter_input( INPUT_POST, 'video', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$activity_id = filter_input( INPUT_POST, 'activity_id', FILTER_SANITIZE_NUMBER_INT );
	$from_where  = bb_filter_input_string( INPUT_POST, 'from_where' );

	if ( empty( $video ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please select video to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$video_ids = array();
	foreach ( $video as $video_id ) {

		if ( bp_video_user_can_delete( $video_id ) ) {

			// delete video.
			if ( bp_video_delete( array( 'id' => $video_id ) ) ) {
				$video_ids[] = $video_id;
			}
		}
	}

	if ( count( $video_ids ) !== count( $video ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem deleting video.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	$response = array();
	if ( $activity_id ) {
		$response = bp_video_get_activity_video( $activity_id );
	}

	$delete_box       = false;
	$activity_content = '';
	if ( 'activity' === $from_where ) {
		// Get activity object.
		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			$delete_box = true;
		} else {
			ob_start();
			if ( bp_has_activities(
				array(
					'include' => $activity_id,
				)
			) ) {
				while ( bp_activities() ) {
					bp_the_activity();
					bp_get_template_part( 'activity/entry' );
				}
			}
			$activity_content = ob_get_contents();
			ob_end_clean();
		}
	}

	$video_personal_count = 0;
	$video_group_count    = 0;

	if ( bp_is_user_video() ) {
		add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
		bp_has_video( bp_ajax_querystring( 'video' ) );
		$video_personal_count = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
		remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
	}

	if ( bp_is_group_video() ) {

		// Update the count of photos in groups in navigation menu.
		wp_cache_flush();

		$video_group_count = bp_video_get_total_group_video_count();
	}

	if ( bp_is_group_albums() ) {

		// Update the count of photos in groups in navigation menu when you are in single albums page.
		wp_cache_flush();
	}

	wp_send_json_success(
		array(
			'video'                => $video,
			'video_personal_count' => $video_personal_count,
			'video_group_count'    => $video_group_count,
			'video_ids'            => ( isset( $response['video_activity_ids'] ) ) ? $response['video_activity_ids'] : '',
			'video_content'        => ( isset( $response['content'] ) ) ? $response['content'] : '',
			'delete_activity'      => $delete_box,
			'activity_content'     => $activity_content,
		)
	);
}

/**
 * Move video to album
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_move_to_album() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$videos = filter_input( INPUT_POST, 'videos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( empty( $videos ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please upload video before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$album_id = filter_input( INPUT_POST, 'album_id', FILTER_VALIDATE_INT );

	if ( empty( $album_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please provide album to move video.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

	$album_privacy = 'public';
	$album         = new BP_Video_Album( $album_id );
	if ( ! empty( $album ) ) {
		$album_privacy = $album->privacy;
	}

	// save video.
	$video_ids = array();
	foreach ( $videos as $video_id ) {

		$video_obj           = new BP_Video( $video_id );
		$video_obj->album_id = $album_id;
		$video_obj->group_id = ! empty( $group_id ) ? $group_id : false;
		$video_obj->privacy  = $video_obj->group_id ? 'grouponly' : $album_privacy;

		if ( ! $video_obj->save() ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
				esc_html__( 'There was a problem when trying to move the video.', 'buddyboss' )
			);

			wp_send_json_error( $response );
		}

		$video_ids[] = $video_id;
	}

	$video_html = '';
	if ( ! empty( $video_ids ) ) {
		ob_start();
		if ( bp_has_video( array( 'include' => implode( ',', $video_ids ) ) ) ) {
			while ( bp_video() ) {
				bp_the_video();
				bp_get_template_part( 'video/entry' );
			}
		}
		$video_html = ob_get_contents();
		ob_end_clean();
	}

	wp_send_json_success(
		array(
			'video' => $video_html,
		)
	);
}

/**
 * Save album
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_album_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$title = bb_filter_input_string( INPUT_POST, 'title' );

	if ( empty( $title ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please enter title of album.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// save video.
	$id       = filter_input( INPUT_POST, 'album_id', FILTER_VALIDATE_INT );
	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$privacy  = bb_filter_input_string( INPUT_POST, 'privacy' );

	$id       = ! empty( $id ) ? $id : false;
	$group_id = ! empty( $group_id ) ? $group_id : false;
	$privacy  = ! empty( $privacy ) ? $privacy : 'public';

	$user_id = bp_loggedin_user_id();
	if ( $id ) {
		$album   = new BP_Video_Album( $id );
		$user_id = $album->user_id;
	}

	if ( ! array_key_exists( $privacy, bp_video_get_visibility_levels() ) && ! empty( $id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Invalid privacy status.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	$album_id = bp_video_album_add(
		array(
			'id'       => $id,
			'title'    => $title,
			'privacy'  => $privacy,
			'group_id' => $group_id,
			'user_id'  => $user_id,
		)
	);

	if ( ! $album_id ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem when trying to create the album.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	$videos = filter_input( INPUT_POST, 'videos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( ! empty( $videos ) ) {
		// set album id for video.
		foreach ( $videos as $key => $video ) {
			$videos[ $key ]['album_id'] = $album_id;
		}

		// save all video uploaded.
		bp_video_add_handler( $videos, $privacy );
	}

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' . $album_id );
	} else {
		$redirect_url = trailingslashit( bp_loggedin_user_domain() . bp_get_media_slug() . '/albums/' . $album_id );
	}

	wp_send_json_success(
		array(
			'redirect_url' => $redirect_url,
		)
	);
}

/**
 * Delete album
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_album_delete() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$album_id = filter_input( INPUT_POST, 'album_id', FILTER_VALIDATE_INT );
	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

	if ( empty( $album_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide ID of album to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( ! bp_video_album_user_can_delete( $album_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'You do not have permission to delete this album.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete album.
	$album_id = bp_video_album_delete( array( 'id' => $album_id ) );

	if ( ! $album_id ) {
		wp_send_json_error( $response );
	}

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' );
	} else {
		$redirect_url = trailingslashit( bp_displayed_user_domain() . bp_get_media_slug() . '/albums/' );
	}

	wp_send_json_success(
		array(
			'redirect_url' => $redirect_url,
		)
	);
}

/**
 * Get activity for the video
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_get_activity() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$post_id       = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$group_id      = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$video_id      = filter_input( INPUT_POST, 'video_id', FILTER_VALIDATE_INT );
	$reset_comment = bb_filter_input_string( INPUT_POST, 'reset_comment' );

	// check activity is video or not.
	$video_activity = bp_activity_get_meta( $post_id, 'bp_video_activity', true );

	$video_data = array();
	if ( ! empty( $video_id ) ) {
		$args = array(
			'include' => $video_id,
			'user_id' => false,
		);
		ob_start();
		if ( bp_has_video( $args ) ) {
			while ( bp_video() ) {
				bp_the_video();
				bp_get_template_part( 'video/single-video' );
			}
		}
		$video_data = ob_get_contents();
		ob_end_clean();
	}

	remove_action( 'bp_activity_entry_content', 'bp_video_activity_entry' );
	add_action( 'bp_before_activity_activity_content', 'bp_nouveau_video_activity_description' );
	add_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );

	if ( ! empty( $video_activity ) ) {
		$args = array(
			'include'     => $post_id,
			'show_hidden' => true,
			'scope'       => 'video',
			'privacy'     => false,
		);
	} else {
		if ( $group_id > 0 && bp_is_active( 'groups' ) ) {
			$args = array(
				'include'     => $post_id,
				'object'      => buddypress()->groups->id,
				'primary_id'  => $group_id,
				'privacy'     => false,
				'scope'       => false,
				'show_hidden' => (bool) ( groups_is_user_member( bp_loggedin_user_id(), $group_id ) || bp_current_user_can( 'bp_moderate' ) ),
			);
		} else {
			$args = array(
				'include' => $post_id,
				'privacy' => false,
				'scope'   => false,
			);
		}
	}

	ob_start();
	if ( bp_has_activities( $args ) ) {
		while ( bp_activities() ) {
			bp_the_activity();
			bp_get_template_part( 'activity/entry' );
		}
	}
	$activity = ob_get_contents();
	ob_end_clean();

	remove_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99 );
	remove_action( 'bp_before_activity_activity_content', 'bp_nouveau_video_activity_description' );
	add_action( 'bp_activity_entry_content', 'bp_video_activity_entry' );

	// This will call only when we close video popup.
	if ( 'true' === $reset_comment ) {
		ob_start();
		bp_activity_comments();
		bp_nouveau_activity_comment_form();
		$activity = ob_get_contents();
		ob_end_clean();
	}

	wp_send_json_success(
		array(
			'activity'   => $activity,
			'video_data' => $video_data,
		)
	);

}

/**
 * Delete attachment with its files
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_delete_attachment() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

	if ( empty( $id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide attachment id to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete attachment with its meta.
	$deleted = wp_delete_attachment( $id, true );

	if ( ! $deleted ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success();
}

/**
 * Update video privacy
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_update_privacy() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$video_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

	if ( empty( $video_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide video id to update.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$privacy = bb_filter_input_string( INPUT_POST, 'privacy' );

	if ( empty( $privacy ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide privacy to update.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( ! in_array( $privacy, array_keys( bp_video_get_visibility_levels() ), true ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Privacy option is not valid.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$video          = new BP_Video( $video_id );
	$video->privacy = $privacy;
	$video->save();

	wp_send_json_success();
}

/**
 * Update video activity description.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_description_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem. Please try again.', 'buddyboss' )
		),
	);

	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_video' ) ) {
		wp_send_json_error( $response );
	}

	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );
	$description   = bb_filter_input_string( INPUT_POST, 'description' );

	// check description empty.
	if ( empty( $description ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was an error in updating a description. Please try again.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$attachment = get_post( $attachment_id );

	if ( empty( $attachment ) && ( 'attachment' !== $attachment->post_type ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was an error in updating a description. Please try again.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$video_post['ID']           = $attachment_id;
	$video_post['post_content'] = $description;
	wp_update_post( $video_post );

	$response['description'] = $description;
	wp_send_json_success( $response );
}

add_filter( 'bp_nouveau_object_template_result', 'bp_nouveau_object_template_results_video_tabs', 10, 2 );

/**
 * Object template results video tabs.
 *
 * @param string $results data array.
 * @param string $object scope name.
 *
 * @return mixed
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_object_template_results_video_tabs( $results, $object ) {
	if ( 'video' !== $object ) {
		return $results;
	}

	$results['scopes'] = array();

	add_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) );
	$results['scopes']['all'] = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) );
	$results['scopes']['personal'] = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'groups' ) );
	$results['scopes']['groups'] = bp_core_number_format( $GLOBALS['video_template']->total_video_count );
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );

	return $results;
}

add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_albums_existing_video_query', 20 );

/**
 * Change the querystring based on caller of the albums video query
 *
 * @param string $querystring query string of query.
 *
 * @return string
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_object_template_results_albums_existing_video_query( $querystring ) {
	$querystring = bp_parse_args( $querystring );

	$caller = bb_filter_input_string( INPUT_POST, 'caller' );

	if ( ! empty( $caller ) && 'bp-existing-video' === $caller ) {
		$querystring['album_id'] = 0;
	}

	return http_build_query( $querystring );
}

/**
 * Get description for the video.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_get_video_description() {

	$video_description = '';
	$video_data        = '';

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_video' ) ) {
		wp_send_json_error( $response );
	}

	$video_id      = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );

	if ( empty( $video_id ) || empty( $attachment_id ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $attachment_id ) ) {
		wp_send_json_error( $response );
	}

	$content          = get_post_field( 'post_content', $attachment_id );
	$video_privacy    = bb_media_user_can_access( $video_id, 'video' );
	$can_download_btn = true === (bool) $video_privacy['can_download'];
	$can_edit_btn     = true === (bool) $video_privacy['can_edit'];
	$can_view         = true === (bool) $video_privacy['can_view'];
	$video            = new BP_Video( $video_id );
	$user_domain      = bp_core_get_user_domain( $video->user_id );
	$display_name     = bp_core_get_user_displayname( $video->user_id );
	$time_since       = bp_core_time_since( $video->date_created );
	add_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );
	$avatar           = bp_core_fetch_avatar(
		array(
			'item_id' => $video->user_id,
			'object'  => 'user',
			'type'    => 'full',
		)
	);
	remove_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );

	ob_start();

	if ( $can_view ) {
		?>
		<li class="activity activity_update activity-item mini ">
			<div class="bp-activity-head">
				<div class="activity-avatar item-avatar">
					<a href="<?php echo esc_url( $user_domain ); ?>"><?php echo wp_kses_post( $avatar ); ?></a>
				</div>

				<div class="activity-header">
					<p><a href="<?php echo esc_url( $user_domain ); ?>"><?php echo esc_html( $display_name ); ?></a> <?php esc_html_e( 'uploaded an video', 'buddyboss' ); ?><a href="<?php echo esc_url( $user_domain ); ?>" class="view activity-time-since"></p>
					<p class="activity-date"><a href="<?php echo esc_url( $user_domain ); ?>"><?php echo wp_kses_post( $time_since ); ?></a></p>
				</div>
			</div>
			<div class="activity-video-description">
				<div class="bp-video-activity-description"><?php echo esc_html( $content ); ?></div>
				<?php
				if ( $can_edit_btn ) {
					?>
					<a class="bp-add-video-activity-description <?php echo( ! empty( $content ) ? 'show-edit' : 'show-add' ); ?>" href="#">
						<span class="bb-icon-l bb-icon-edit"></span>
						<span class="add"><?php esc_html_e( 'Add a description', 'buddyboss' ); ?></span>
						<span class="edit"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></span>
					</a>

					<div class="bp-edit-video-activity-description" style="display: none;">
						<div class="innerWrap">
							<textarea id="add-activity-description" title="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" class="textInput" name="caption_text" placeholder="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" role="textbox"><?php echo wp_kses_post( $content ); ?></textarea>
						</div>
						<div class="in-profile description-new-submit">
							<input type="hidden" id="bp-attachment-id" value="<?php echo esc_attr( $attachment_id ); ?>">
							<input type="submit" id="bp-activity-description-new-submit" class="button small" name="description-new-submit" value="<?php esc_html_e( 'Done Editing', 'buddyboss' ); ?>">
							<input type="reset" id="bp-activity-description-new-reset" class="text-button small" value="<?php esc_html_e( 'Cancel', 'buddyboss' ); ?>">
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<?php
			if ( ! empty( $video_id ) ) {
				if ( $can_download_btn ) {
					$download_url = bp_video_download_link( $attachment_id, $video_id );
					if ( $download_url ) {
						?>
						<a class="download-video" href="<?php echo esc_url( $download_url ); ?>">
							<?php esc_html_e( 'Download', 'buddyboss' ); ?>
						</a>
						<?php
					}
				}
			}
			?>
		</li>
		<?php
		$video_description = ob_get_clean();

		if ( ! empty( $video_id ) ) {
			$args = array(
				'user_id'  => false,
				'include'  => $video_id,
				'album_id' => 'existing-video',
			);
			ob_start();
			if ( bp_has_video( $args ) ) {
				while ( bp_video() ) {
					bp_the_video();
					bp_get_template_part( 'video/single-video' );
				}
			}
			$video_data = ob_get_clean();
		}
	}

	wp_send_json_success(
		array(
			'description' => $video_description,
			'video_data'  => $video_data,
		)
	);
}

/**
 * Get the album vide based on the parent child.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_get_album_view() {

	$type = bb_filter_input_string( INPUT_POST, 'type' );
	$id   = bb_filter_input_string( INPUT_POST, 'id' );

	if ( 'profile' === $type ) {
		$ul = bp_video_user_video_album_tree_view_li_html( $id, 0 );
	} else {
		$ul = bp_video_user_video_album_tree_view_li_html( bp_loggedin_user_id(), $id );
	}

	$first_text = '';
	if ( 'profile' === $type ) {
		$first_text = esc_html__( ' Videos', 'buddyboss' );
	} else {
		if ( bp_is_active( 'groups' ) ) {
			$group      = groups_get_group( (int) $id );
			$first_text = bp_get_group_name( $group );
		}
	}

	wp_send_json_success(
		array(
			'message'         => 'success',
			'html'            => $ul,
			'first_span_text' => stripslashes( $first_text ),
		)
	);
}

/**
 * Ajax video move.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_move() {

	$response = array(
		'feedback' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Move video.
	$album_id    = filter_input( INPUT_POST, 'album_id', FILTER_VALIDATE_INT );
	$video_id    = filter_input( INPUT_POST, 'video_id', FILTER_VALIDATE_INT );
	$group_id    = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$activity_id = filter_input( INPUT_POST, 'activity_id', FILTER_VALIDATE_INT );

	if ( 0 === $video_id ) {
		wp_send_json_error( $response );
	}

	if ( (int) $video_id > 0 ) {
		$has_access = bp_video_user_can_edit( $video_id );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have permission to move this video.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	if ( (int) $album_id > 0 ) {
		$has_access = bp_video_album_user_can_edit( $album_id );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have permission to move this video.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	$video    = bp_video_move_video_to_album( $video_id, $album_id, $group_id );
	$response = bp_video_get_activity_video( $activity_id );

	if ( $video > 0 ) {
		$content = '';
		wp_send_json_success(
			array(
				'video_ids'     => $response['video_activity_ids'],
				'video_content' => $response['content'],
				'message'       => 'success',
				'html'          => $content,
			)
		);
	} else {
		wp_send_json_error( $response );
	}

}

/**
 * Get Video edit thumbnail data.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_get_edit_thumbnail_data() {

	$response = array(
		'feedback' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_SANITIZE_NUMBER_INT );
	$video_id      = filter_input( INPUT_POST, 'video_id', FILTER_SANITIZE_NUMBER_INT );

	if ( 0 === $video_id || 0 === $attachment_id ) {
		wp_send_json_error( $response );
	}

	$video = new BP_Video( $video_id );

	if ( ! $video ) {
		wp_send_json_error( $response );
	}

	$auto_generated_thumbnails = bb_video_get_auto_generated_preview_ids( $attachment_id );
	$preview_thumbnail_id      = bb_get_video_thumb_id( $attachment_id );
	$default_images            = '';
	$dropzone_arr              = '';
	if ( ! empty( $auto_generated_thumbnails ) ) {
		$auto_generated_thumbnails_arr = $auto_generated_thumbnails;
		ob_start();
		if ( $auto_generated_thumbnails_arr ) {
			foreach ( $auto_generated_thumbnails_arr as $auto_generated_thumbnail ) {
				$attachment_url = bb_video_get_attachment_symlink( $video, $auto_generated_thumbnail, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
				?>
				<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3">
					<div class="">
						<input <?php checked( $preview_thumbnail_id, $auto_generated_thumbnail ); ?> id="bb-video-<?php echo esc_attr( $auto_generated_thumbnail ); ?>" class="bb-custom-check" type="radio" value="<?php echo esc_attr( $auto_generated_thumbnail ); ?>" name="bb-video-thumbnail-select" />
						<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Select', 'buddyboss' ); ?>" for="bb-video-<?php echo esc_attr( $auto_generated_thumbnail ); ?>">
							<span class="bb-icon-l bb-icon-check"></span>
						</label>
						<a class="" href="#">
							<img src="<?php echo esc_url( $attachment_url ); ?>" class=""/>
						</a>
					</div>
				</li>
				<?php
			}
		}
		$default_images = ob_get_contents();
		ob_end_clean();
	}

	if ( $preview_thumbnail_id ) {
		$auto_generated_thumbnails_arr = $auto_generated_thumbnails;
		if ( ! in_array( $preview_thumbnail_id, $auto_generated_thumbnails_arr, true ) ) {

			$file  = get_attached_file( $preview_thumbnail_id );
			$type  = pathinfo( $file, PATHINFO_EXTENSION );
			$data  = file_get_contents( $file ); // phpcs:ignore
			$thumb = 'data:image/' . $type . ';base64,' . base64_encode( $data ); // phpcs:ignore

			$dropzone_arr = array(
				'id'            => $video_id,
				'attachment_id' => $video->attachment_id,
				'thumb'         => $thumb,
				'url'           => $thumb,
				'name'          => $video->title,
				'saved'         => true,
			);
		}
	}

	wp_send_json_success(
		array(
			'default_images'   => $default_images,
			'dropzone_edit'    => $dropzone_arr,
			'ffmpeg_generated' => get_post_meta( $attachment_id, 'bb_ffmpeg_preview_generated', true ),
		)
	);

}

/**
 * Save the video thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_thumbnail_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$thumbnail                 = filter_input( INPUT_POST, 'video_thumbnail', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$video_attachment_id       = filter_input( INPUT_POST, 'video_attachment_id', FILTER_SANITIZE_NUMBER_INT );
	$video_id                  = filter_input( INPUT_POST, 'video_id', FILTER_SANITIZE_NUMBER_INT );
	$pre_selected_id           = filter_input( INPUT_POST, 'video_default_id', FILTER_SANITIZE_NUMBER_INT );
	$auto_generated_thumbnails = bb_video_get_auto_generated_preview_ids( $video_attachment_id );
	$old_thumbnail_id          = get_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', true );

	if ( $pre_selected_id !== $old_thumbnail_id && ! in_array( $old_thumbnail_id, $auto_generated_thumbnails, true ) ) {
		bb_video_delete_thumb_symlink( $video_id, $old_thumbnail_id );
	}

	if ( $video_attachment_id && $thumbnail ) {
		$pre_selected_id = current( $thumbnail );
		$pre_selected_id = $pre_selected_id['id'];
		update_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', $pre_selected_id );

		$thumbnail_images = array(
			'default_images' => $auto_generated_thumbnails,
			'custom_image'   => $pre_selected_id,
		);

		update_post_meta( $video_attachment_id, 'video_preview_thumbnails', $thumbnail_images );
		update_post_meta( $pre_selected_id, 'bp_video_upload', 1 );

	} elseif ( $video_attachment_id && $pre_selected_id ) {
		update_post_meta( $video_attachment_id, 'bp_video_preview_thumbnail_id', $pre_selected_id );
	}

	$thumbnail_url = bb_video_get_thumb_url( $video_id, $pre_selected_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );

	if ( empty( $thumbnail_url ) ) {
		$thumbnail_url = bb_get_video_default_placeholder_image();
	}

	wp_send_json_success(
		array(
			'thumbnail'           => $thumbnail_url,
			'video_attachment_id' => $video_attachment_id,
			'video_attachments'   => wp_json_encode( bb_video_get_attachments_symlinks( $video_attachment_id, $video_id ) ),
		)
	);
}

/**
 * Save the video thumbnail.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_ajax_video_thumbnail_delete() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$thumbnail_id  = 0;
	$thumbnail_url = '';

	$video_id            = filter_input( INPUT_POST, 'video_id', FILTER_SANITIZE_NUMBER_INT );
	$attachment_id       = filter_input( INPUT_POST, 'attachment_id', FILTER_SANITIZE_NUMBER_INT );
	$video_attachment_id = filter_input( INPUT_POST, 'video_attachment_id', FILTER_SANITIZE_NUMBER_INT );

	if ( ! empty( $attachment_id ) && ! empty( $video_attachment_id ) ) {
		$auto_generated_thumbnails = get_post_meta( $attachment_id, 'video_preview_thumbnails', true );
		$old_preview_thumbnail_id  = get_post_meta( $attachment_id, 'bp_video_preview_thumbnail_id', true );
		$default_images            = isset( $auto_generated_thumbnails['default_images'] ) && ! empty( $auto_generated_thumbnails['default_images'] ) ? $auto_generated_thumbnails['default_images'] : array();
		$thumbnail_images          = array(
			'default_images' => $default_images,
		);
		update_post_meta( $attachment_id, 'video_preview_thumbnails', $thumbnail_images );
		if (
			isset( $default_images ) && ! empty( $default_images ) &&
			$old_preview_thumbnail_id == $video_attachment_id
		) {
			update_post_meta( $attachment_id, 'bp_video_preview_thumbnail_id', $default_images[0] );
			$thumbnail_id  = $default_images[0];
			$thumbnail_url = bb_video_get_thumb_url( $video_id, $default_images[0], 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
		} else {
			$thumbnail_id  = $old_preview_thumbnail_id;
			$thumbnail_url = bb_video_get_thumb_url( $video_id, $old_preview_thumbnail_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
		}
		wp_delete_post( $video_attachment_id, true );
		bb_video_delete_thumb_symlink( $attachment_id, $video_attachment_id );
	}

	if ( empty( $thumbnail_url ) ) {
		$thumbnail_url = bb_get_video_default_placeholder_image();
	}

	wp_send_json_success(
		array(
			'thumbnail'         => $thumbnail_url,
			'thumbnail_id'      => $thumbnail_id,
			'video_attachments' => wp_json_encode( bb_video_get_attachments_symlinks( $attachment_id, $video_id ) ),
		)
	);
}
