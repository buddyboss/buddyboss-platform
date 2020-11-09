<?php
/**
 * Video Ajax functions
 *
 * @since BuddyBoss 1.0.0
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
					'function' => 'bp_nouveau_ajax_albums_loader',
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
 * @since BuddyBoss 1.0.0
 *
 * @return string Template loop for the albums object
 */
function bp_nouveau_ajax_albums_loader() {
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

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
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
			bp_video_the_album();
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
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
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
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	add_filter( 'upload_dir', 'bp_video_upload_dir' );

	// Upload file.
	$result = bp_video_upload();

	remove_filter( 'upload_dir', 'bp_video_upload_dir' );

	if ( is_wp_error( $result ) ) {
		$response['feedback'] = $result->get_error_message();
		wp_send_json_error( $response, $result->get_error_code() );
	}

	wp_send_json_success( $result );
}

/**
 * Save video
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
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

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
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

	$privacy = filter_input( INPUT_POST, 'privacy', FILTER_SANITIZE_STRING );
	$content = filter_input( INPUT_POST, 'content', FILTER_SANITIZE_STRING );

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

	wp_send_json_success( array( 'video' => $video ) );
}

/**
 * Delete video
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
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

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$video = filter_input( INPUT_POST, 'video', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

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

			// delete video
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

	wp_send_json_success(
		array(
			'video' => $video,
		)
	);
}

/**
 * Move video to album
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
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

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
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

	// save video
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

	wp_send_json_success(
		array(
			'video' => $video,
		)
	);
}

/**
 * Save album
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
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

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$title = filter_input( INPUT_POST, 'title', FILTER_SANITIZE_STRING );

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
	$privacy  = filter_input( INPUT_POST, 'privacy', FILTER_SANITIZE_STRING );

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
		// set album id for video
		foreach ( $videos as $key => $video ) {
			$videos[ $key ]['album_id'] = $album_id;
		}

		// save all video uploaded
		bp_video_add_handler( $videos, $privacy );
	}

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' . $album_id );
	} else {
		$redirect_url = trailingslashit( bp_loggedin_user_domain() . bp_get_video_slug() . '/albums/' . $album_id );
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
 * @since BuddyBoss 1.0.0
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

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
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

	// delete album
	$album_id = bp_video_album_delete( array( 'id' => $album_id ) );

	if ( ! $album_id ) {
		wp_send_json_error( $response );
	}

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' );
	} else {
		$redirect_url = trailingslashit( bp_displayed_user_domain() . bp_get_video_slug() . '/albums/' );
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
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_video_get_activity() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Use default nonce
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$post_id  = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

	// check activity is video or not.
	$video_activity = bp_activity_get_meta( $post_id, 'bp_video_activity', true );

	remove_action( 'bp_activity_entry_content', 'bp_video_activity_entry' );
	add_action( 'bp_before_activity_activity_content', 'bp_nouveau_activity_description' );
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

	remove_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );
	remove_action( 'bp_before_activity_activity_content', 'bp_nouveau_activity_description' );
	add_action( 'bp_activity_entry_content', 'bp_video_activity_entry' );

	wp_send_json_success(
		array(
			'activity' => $activity,
		)
	);
}

/**
 * Delete attachment with its files
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_ajax_video_delete_attachment() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
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

	// delete attachment with its meta
	$deleted = wp_delete_attachment( $id, true );

	if ( ! $deleted ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success();
}

/**
 * Update video privacy
 *
 * @since BuddyBoss 1.2.0
 */
function bp_nouveau_ajax_video_update_privacy() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Use default nonce
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
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

	$privacy = filter_input( INPUT_POST, 'privacy', FILTER_SANITIZE_STRING );

	if ( empty( $privacy ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide privacy to update.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( ! in_array( $privacy, array_keys( bp_video_get_visibility_levels() ) ) ) {
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
 * @since BuddyBoss 1.3.5
 */
function bp_nouveau_ajax_video_description_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem. Please try again.', 'buddyboss' )
		),
	);

	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_video' ) ) {
		wp_send_json_error( $response );
	}

	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );
	$description   = filter_input( INPUT_POST, 'description', FILTER_SANITIZE_STRING );

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
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_video_tabs( $results, $object ) {
	if ( 'video' !== $object ) {
		return $results;
	}

	$results['scopes'] = array();

	add_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) );
	$results['scopes']['all'] = $GLOBALS['video_template']->total_video_count;
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_results_video_all_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'video' ) );
	$results['scopes']['personal'] = $GLOBALS['video_template']->total_video_count;
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_personal_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );
	bp_has_video( bp_ajax_querystring( 'groups' ) );
	$results['scopes']['groups'] = $GLOBALS['video_template']->total_video_count;
	remove_filter( 'bp_ajax_querystring', 'bp_video_object_template_results_video_groups_scope', 20 );

	return $results;
}

add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_albums_existing_video_query', 20 );

/**
 * Change the querystring based on caller of the albums video query
 *
 * @param $querystring
 *
 * @return string
 */
function bp_nouveau_object_template_results_albums_existing_video_query( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$caller = filter_input( INPUT_POST, 'caller', FILTER_SANITIZE_STRING );

	if ( ! empty( $caller ) && 'bp-existing-video' === $caller ) {
		$querystring['album_id'] = 0;
	}

	return http_build_query( $querystring );
}

/**
 * Get description for the video.
 *
 * @since BuddyBoss 1.4.4
 */
function bp_nouveau_ajax_video_get_video_description() {

	$video_description = '';

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
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

	$content = get_post_field( 'post_content', $attachment_id );

	$video_privacy    = bp_video_user_can_manage_video( $video_id, bp_loggedin_user_id() );
	$can_download_btn = ( true === (bool) $video_privacy['can_download'] ) ? true : false;
	$can_manage_btn   = ( true === (bool) $video_privacy['can_manage'] ) ? true : false;
	$can_view         = ( true === (bool) $video_privacy['can_view'] ) ? true : false;

	$video        = new BP_Video( $video_id );
	$user_domain  = bp_core_get_user_domain( $video->user_id );
	$display_name = bp_core_get_user_displayname( $video->user_id );
	$time_since   = bp_core_time_since( $video->date_created );
	$avatar       = bp_core_fetch_avatar(
		array(
			'item_id' => $video->user_id,
			'object'  => 'user',
			'type'    => 'full',
		)
	);

	ob_start();

	if ( $can_view ) {
		?>
		<li class="activity activity_update activity-item mini ">
			<div class="bp-activity-head">
				<div class="activity-avatar item-avatar">
					<a href="<?php echo esc_url( $user_domain ); ?>"><?php echo $avatar; ?></a>
				</div>

				<div class="activity-header">
					<p><a href="<?php echo esc_url( $user_domain ); ?>"><?php echo $display_name; ?></a> <?php echo __( 'uploaded an image', 'buddyboss' ); ?><a href="<?php echo esc_url( $user_domain ); ?>" class="view activity-time-since"></p>
					<p class="activity-date"><a href="<?php echo esc_url( $user_domain ); ?>"><?php echo $time_since; ?></a></p>
				</div>
			</div>
			<div class="activity-video-description">
				<div class="bp-video-activity-description"><?php echo esc_html( $content ); ?></div>
				<?php
				if ( $can_manage_btn ) {
					?>
					<a class="bp-add-video-activity-description <?php echo( ! empty( $content ) ? 'show-edit' : 'show-add' ); ?>" href="#">
						<span class="bb-icon-edit-thin"></span>
						<span class="add"><?php _e( 'Add a description', 'buddyboss' ); ?></span>
						<span class="edit"><?php _e( 'Edit', 'buddyboss' ); ?></span>
					</a>

					<div class="bp-edit-video-activity-description" style="display: none;">
						<div class="innerWrap">
							<textarea id="add-activity-description" title="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" class="textInput" name="caption_text" placeholder="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" role="textbox"><?php echo $content; ?></textarea>
						</div>
						<div class="in-profile description-new-submit">
							<input type="hidden" id="bp-attachment-id" value="<?php echo $attachment_id; ?>">
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
							<?php _e( 'Download', 'buddyboss' ); ?>
						</a>
						<?php
					}
				}
			}
			?>
		</li>
		<?php
		$video_description = ob_get_contents();
		ob_end_clean();
	}

	wp_send_json_success(
		array(
			'description' => $video_description,
		)
	);
}

function bp_nouveau_ajax_video_get_album_view() {

	$type = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );
	$id   = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_STRING );

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
 * @since BuddyBoss 1.4.9
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
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_video';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Move media.
	$album_id    = ! empty( $_POST['album_id'] ) ? (int) $_POST['album_id'] : 0;
	$video_id    = ! empty( $_POST['video_id'] ) ? (int) $_POST['video_id'] : 0;
	$group_id    = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;
	$activity_id = ! empty( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;

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
