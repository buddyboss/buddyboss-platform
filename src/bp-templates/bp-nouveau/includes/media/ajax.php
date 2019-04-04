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
			'media_move_to_album' => array(
				'function' => 'bp_nouveau_ajax_media_move_to_album',
				'nopriv'   => true,
			),
		),
		array(
			'media_album_save' => array(
				'function' => 'bp_nouveau_ajax_media_album_save',
				'nopriv'   => true,
			),
		),
		array(
			'media_get_activity' => array(
				'function' => 'bp_nouveau_ajax_media_get_activity',
				'nopriv'   => true,
			),
		),
		array(
			'media_delete_attachment' => array(
				'function' => 'bp_nouveau_ajax_media_delete_attachment',
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

	wp_send_json_success( $result );
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

	if ( empty( $_POST['content'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please write something about media.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$main_activity_id = false;
	// make an activity
	if ( bp_is_active( 'activity' ) ) {

		/**
		 * Filters the content provided in the activity input field.
		 *
		 * @since BuddyPress 1.2.0
		 *
		 * @param string $value Activity message being posted.
		 */
		$content = apply_filters( 'bp_activity_post_update_content', $_POST['content'] );

		$main_activity_id = bp_activity_post_update( array( 'content' => $content, 'user_id' => bp_displayed_user_id() ) );
	}

	// save media
	$medias = $_POST['medias'];
	$media_ids = array();
	foreach( $medias as $media ) {

		$activity_id = false;
		// make an activity for the media
		if ( bp_is_active( 'activity' ) ) {
			$content = '&nbsp;';
			$activity_id = bp_activity_post_update( array( 'content' => $content, 'user_id' => bp_displayed_user_id(), 'hide_sitewide' => true ) );
		}

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

	$media = '';
	if ( ! empty( $media_ids ) ) {
		$media_ids = implode( ',', $media_ids );

		//save media meta for activity
		if ( ! empty( $main_activity_id ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
		}

		ob_start();
		if ( bp_has_media( array( 'include' => $media_ids ) ) ) {
			while ( bp_media() ) {
				bp_the_media();
				bp_get_template_part( 'media/entry' );
			}
		}
		$media = ob_get_contents();
		ob_end_clean();
	}

	wp_send_json_success( array(
		'media' => $media,
	) );
}

/**
 * Move media to album
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_move_to_album() {
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

	if ( empty( $_POST['album_id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please provide album to move media.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// save media
	$medias = $_POST['medias'];
	$media_ids = array();
	foreach( $medias as $media_id ) {

		$media_obj = new BP_Media( $media_id );
		$media_obj->album_id = (int) $_POST['album_id'];

		if ( ! $media_obj->save() ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error">%s</div>',
				esc_html__( 'There was a problem when trying to move the media.', 'buddyboss' )
			);

			wp_send_json_error( $response );
		}

		$media_ids[] = $media_id;
	}

	$media = '';
	if ( ! empty( $media_ids ) ) {
		ob_start();
		if ( bp_has_media( array( 'include' => implode( ',', $media_ids ) ) ) ) {
			while ( bp_media() ) {
				bp_the_media();
				bp_get_template_part( 'media/entry' );
			}
		}
		$media = ob_get_contents();
		ob_end_clean();
	}

	wp_send_json_success( array(
		'media' => $media,
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
	$title       = $_POST['title'];
	$description = ! empty( $_POST['description'] ) ? $_POST['description'] : '';
	$privacy     = ! empty( $_POST['privacy'] ) ? $_POST['privacy'] : 'public';

	$album_id = bp_album_add( array( 'title' => $title, 'description' => $description, 'privacy' => $privacy ) );

	ob_start();
	if ( bp_has_albums( array( 'include' => $album_id ) ) ) {
		while ( bp_album() ) {
			bp_the_album();
			bp_get_template_part( 'media/album-entry' );
		}
	}
	$album = ob_get_contents();
	ob_end_clean();

	wp_send_json_success( array(
		'album'     => $album,
	) );
}

/**
 * Get activity for the media
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_get_activity() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error">%s</div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	remove_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );

	ob_start();
	if ( bp_has_activities( array( 'include' => $_POST['id'], 'show_hidden' => true ) ) ) {
		while ( bp_activities() ) {
			bp_the_activity();
			bp_get_template_part( 'activity/entry' );
		}
	}
	$activity = ob_get_contents();
	ob_end_clean();

	add_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );

	wp_send_json_success( array(
		'activity'     => $activity,
	) );
}

/**
 * Delete attachment with its files
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_ajax_media_delete_attachment() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error">%s</div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please provide attachment id to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	//delete attachment with its meta
	$deleted = wp_delete_attachment( $_POST['id'], true );

	if ( ! $deleted ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success();
}

add_filter('bp_nouveau_object_template_result', 'bp_nouveau_object_template_results_media_tabs', 10, 2);
/**
 * Object template results media tabs.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_media_tabs( $results, $object ) {
	if ( $object != 'media' ) {
		return $results;
	}

	$results['scopes'] = [];

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_media_all_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$results['scopes']['all'] = $GLOBALS["media_template"]->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_media_all_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_media_personal_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$results['scopes']['personal'] = $GLOBALS["media_template"]->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_media_personal_scope', 20 );

	return $results;
}

/**
 * Object template results media all scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_media_all_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope'] = 'all';
	$querystring['page'] = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id'] = 0;
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}

/**
 * Object template results members personal scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_media_personal_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope'] = 'personal';
	$querystring['page'] = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id'] = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}
