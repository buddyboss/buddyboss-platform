<?php
/**
 * Media Ajax functions
 *
 * @since BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function() {
	$ajax_actions = array(
		array(
			'media_filter' => array(
				'function' => 'bp_nouveau_ajax_object_template_loader',
				'nopriv'   => true,
			),
		),
		array(
			'media_upload' => array(
				'function' => 'bp_nouveau_ajax_media_upload',
				'nopriv'   => true,
			),
		),
		array(
			'media_save' => array(
				'function' => 'bp_nouveau_ajax_media_save',
				'nopriv'   => true,
			),
		),
		array(
			'media_album_save' => array(
				'function' => 'bp_nouveau_ajax_media_album_save',
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
}, 12 );

/**
 * Upload a media via a POST request.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_upload() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['_wpnonce'];
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Upload file
	$result = bp_media_upload();

	if ( is_wp_error( $result ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'There was a problem when trying to upload this file.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	return wp_send_json_success( $result );
}

/**
 * Save media
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['_wpnonce'];
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['medias'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please upload media before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$activity_id = false;
	// make an activity for the media
	if ( bp_is_active( 'activity' ) ) {
		$content = '&nbsp;';
		$activity_id = bp_activity_post_update( array( 'content' => $content, 'user_id' => bp_displayed_user_id() ) );
	}

	// save media
	$medias = $_POST['medias'];
	$media_ids = array();
	foreach( $medias as $media ) {
		$media_id = bp_media_add( array(
			'attachment_id' => $media['id'],
			'title'         => $media['name'],
			'activity_id'   => $activity_id,
			'album_id'      => $media['album_id'],
			'error_type'    => 'wp_error'
		) );

		if ( is_wp_error( $media_id ) ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error">%s</div>',
				esc_html__( 'There was a problem when trying to add the media.', 'buddyboss' )
			);

			wp_send_json_error( $response );
		}

		$media_ids[] = $media_id;
	}

	ob_start();
	if ( bp_has_media( array( 'include' => $media_ids ) ) ) {
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'members/single/media/entry' );
		}
	}
	$media = ob_get_contents();
	ob_end_clean();

	wp_send_json_success( array(
		'media'     => $media,
	) );
}

/**
 * Save album
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_album_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['_wpnonce'];
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['title'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please enter title of album.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// save media
	$title = $_POST['title'];
	$description = ! empty( $_POST['description'] ) ? $_POST['description'] : '';
	$privacy = ! empty( $_POST['privacy'] ) ? $_POST['privacy'] : 'public';

	$album_id = bp_album_add( array( 'title' => $title, 'description' => $description, 'privacy' => $privacy ) );

	ob_start();
	if ( bp_has_albums( array( 'include' => $album_id ) ) ) {
		while ( bp_album() ) {
			bp_the_album();
			bp_get_template_part( 'members/single/media/album-entry' );
		}
	}
	$album = ob_get_contents();
	ob_end_clean();

	wp_send_json_success( array(
		'album'     => $album,
	) );
}
