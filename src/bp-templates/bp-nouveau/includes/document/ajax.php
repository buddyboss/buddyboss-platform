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
			'document_filter' => array(
				'function' => 'bp_nouveau_ajax_object_template_loader',
				'nopriv'   => true,
			),
		),
		array(
			'document_document_upload' => array(
				'function' => 'bp_nouveau_ajax_document_upload',
				'nopriv'   => true,
			),
		),
		array(
			'document_document_save' => array(
				'function' => 'bp_nouveau_ajax_document_document_save',
				'nopriv'   => true,
			),
		),
		array(
			'document_folder_save' => array(
				'function' => 'bp_nouveau_ajax_document_folder_save',
				'nopriv'   => true,
			),
		),
		array(
			'document_folder_move' => array(
				'function' => 'bp_nouveau_ajax_document_folder_move',
				'nopriv'   => true,
			),
		),
		array(
			'document_update_file_name' => array(
				'function' => 'bp_nouveau_ajax_document_update_file_name',
				'nopriv'   => true,
			),
		),
		array(
			'document_edit_folder' => array(
				'function' => 'bp_nouveau_ajax_document_edit_folder',
				'nopriv'   => true,
			),
		)
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

	$page = ! empty( $_POST['page'] ) ? (int) $_POST['page'] : 1;

	ob_start();
	if ( bp_has_albums( array( 'page' => $page ) ) ) {
		while ( bp_album() ) {
			bp_the_album();
			bp_get_template_part( 'media/album-entry' );
		}

		if ( bp_album_has_more_items() ) : ?>

            <li class="load-more">
                <a class="button outline" href="<?php bp_album_has_more_items(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
            </li>

		<?php endif;
	}
	$albums = ob_get_contents();
	ob_end_clean();

	wp_send_json_success( array(
		'albums' => $albums,
	) );
}

/**
 * Upload a media via a POST request.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_upload() {
	$response = array(
		'feedback' => __( 'There was a problem when trying to upload this file.', 'buddyboss' )
    );

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response, 500 );
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Use default nonce
	$nonce = $_POST['_wpnonce'];
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Upload file
	$result = bp_media_upload();

	if ( is_wp_error( $result ) ) {
		$response['feedback'] = $result->get_error_message();
		wp_send_json_error( $response, $result->get_error_code() );
	}

	wp_send_json_success( $result );
}

/**
 * Upload a document via a POST request.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_document_upload() {
	$response = array(
		'feedback' => __( 'There was a problem when trying to upload this file.', 'buddyboss' )
    );

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response, 500 );
	}

	if ( empty( $_POST['_wpnonce'] ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Use default nonce
	$nonce = $_POST['_wpnonce'];
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Upload file
	$result = bp_document_upload();

	if ( is_wp_error( $result ) ) {
		$response['feedback'] = $result->get_error_message();
		wp_send_json_error( $response, $result->get_error_code() );
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
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
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
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please upload media before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// handle media uploaded.
	$media_ids = bp_media_add_handler();

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

	wp_send_json_success( array( 'media' => $media ) );
}

/**
 * Delete media
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_delete() {
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

	if ( empty( $_POST['media'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please select media to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$media = $_POST['media'];

	$media_ids = array();
	foreach( $media as $media_id ) {

	    if ( bp_media_user_can_delete( $media_id ) ) {

		    // delete media
		    if ( bp_media_delete( array( 'id' => $media_id ) ) ) {
			    $media_ids[] = $media_id;
		    }
	    }
	}

	if ( count( $media_ids ) != count( $media ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem deleting media.', 'buddyboss' )
		);
		wp_send_json_error( $response );
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
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
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

	$album_privacy = 'public';
	$album = new BP_Media_Album( $_POST['album_id'] );
	if ( ! empty( $album ) ) {
		$album_privacy = $album->privacy;
	}

	// save media
	$medias = $_POST['medias'];
	$media_ids = array();
	foreach( $medias as $media_id ) {

		$media_obj           = new BP_Media( $media_id );
		$media_obj->album_id = (int) $_POST['album_id'];
		$media_obj->group_id = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : false;
		$media_obj->privacy  = $media_obj->group_id ? 'grouponly' : $album_privacy;

		if ( ! $media_obj->save() ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
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
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
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
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please enter title of album.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// save media
	$id       = ! empty( $_POST['album_id'] ) ? $_POST['album_id'] : false;
	$group_id = ! empty( $_POST['group_id'] ) ? $_POST['group_id'] : false;
	$title    = $_POST['title'];
	$privacy  = ! empty( $_POST['privacy'] ) ? $_POST['privacy'] : 'public';

	$album_id = bp_album_add( array( 'id' => $id, 'title' => $title, 'privacy' => $privacy, 'group_id' => $group_id ) );

	if ( ! $album_id ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem when trying to create the album.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	if ( ! empty( $_POST['medias'] ) && is_array( $_POST['medias'] ) ) {
		// set album id for media
		foreach ( $_POST['medias'] as $key => $media ) {
			$_POST['medias'][ $key ]['album_id'] = $album_id;
		}
	}

	// save all media uploaded
	bp_media_add_handler();

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' . $album_id );
	} else {
		$redirect_url = trailingslashit( bp_loggedin_user_domain() . bp_get_media_slug() . '/albums/' . $album_id );
	}

	wp_send_json_success( array(
		'redirect_url'     => $redirect_url,
	) );
}

/**
 * Delete album
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_ajax_media_album_delete() {
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

	if ( empty( $_POST['album_id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide ID of album to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( ! bp_album_user_can_delete( $_POST['album_id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'You do not have permission to delete this album.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete album
	$album_id = bp_album_delete( array( 'id' => $_POST['album_id'] ) );

	if ( ! $album_id ) {
		wp_send_json_error( $response );
	}

	$group_id = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : false;

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link = bp_get_group_permalink( groups_get_group( $_POST['group_id'] ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' );
	} else {
		$redirect_url = trailingslashit( bp_displayed_user_domain() . bp_get_media_slug() . '/albums/' );
	}

	wp_send_json_success( array(
		'redirect_url'     => $redirect_url,
	) );
}

/**
 * Delete album
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_ajax_media_folder_delete() {
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

	if ( empty( $_POST['album_id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide ID of folder to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( ! bp_album_user_can_delete( $_POST['album_id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'You do not have permission to delete this folder.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete album
	$album_id = bp_folder_delete( array( 'id' => $_POST['album_id'] ) );

	if ( ! $album_id ) {
		wp_send_json_error( $response );
	}

	$group_id = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : false;

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link = bp_get_group_permalink( groups_get_group( $_POST['group_id'] ) );
		$redirect_url = trailingslashit( $group_link . '/documents/' );
	} else {
		$redirect_url = trailingslashit( bp_displayed_user_domain() . bp_get_document_slug() );
	}

	wp_send_json_success( array(
		'redirect_url'     => $redirect_url,
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
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
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
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
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

add_filter('bp_nouveau_object_template_result', 'bp_nouveau_object_template_results_document_tabs', 10, 2);

/**
 * Object template results media tabs.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_document_tabs( $results, $object ) {
	if ( $object != 'document' ) {
		return $results;
	}

	$results['scopes'] = [];

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_all_scope', 20 );
	bp_has_document( bp_ajax_querystring( 'document' ) );
	$results['scopes']['all'] = $GLOBALS["document_template"]->total_document_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_all_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_personal_scope', 20 );
	bp_has_document( bp_ajax_querystring( 'document' ) );
	$results['scopes']['personal'] = $GLOBALS["document_template"]->total_document_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_personal_scope', 20 );

	return $results;
}

/**
 * Object template results media all scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_media_all_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope'] = array();

	if ( bp_is_active( 'friends' ) ) {
		$querystring['scope'][] = 'friends';
	}

	if ( bp_is_active( 'groups' ) ) {
		$querystring['scope'][] = 'groups';
	}

	if ( is_user_logged_in() ) {
		$querystring['scope'][] = 'personal';
	}

	$querystring['page'] = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id'] = 0;
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}

/**
 * Object template results media all scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_document_all_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope'] = array();

	if ( bp_is_active( 'friends' ) ) {
		$querystring['scope'][] = 'friends';
	}

	if ( bp_is_active( 'groups' ) ) {
		$querystring['scope'][] = 'groups';
	}

	if ( is_user_logged_in() ) {
		$querystring['scope'][] = 'personal';
	}

	$querystring['page'] = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id'] = 0;
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}

/**
 * Object template results media personal scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_media_personal_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope']    = 'personal';
	$querystring['page']     = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id']  = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	//$querystring['type']     = 'media';

	$privacy  = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
		$privacy[] = 'onlyme';
	}

	$querystring['privacy'] = $privacy;
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}

/**
 * Object template results media personal scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_document_personal_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope']    = 'personal';
	$querystring['page']     = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id']  = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	//$querystring['type']     = 'media';

	$privacy  = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
		$privacy[] = 'onlyme';
	}

	$querystring['privacy'] = $privacy;
	$querystring['count_total'] = true;
	return http_build_query( $querystring );
}

add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_albums_existing_media_query', 20 );

/**
 * Change the querystring based on caller of the albums media query
 *
 * @param $querystring
 *
 * @return string
 */
function bp_nouveau_object_template_results_albums_existing_media_query( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	if ( ! empty( $_POST['caller'] ) && 'bp-existing-media' == $_POST['caller'] ) {
		$querystring['album_id'] = 0;
	}

	return http_build_query( $querystring );
}

add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_folders_existing_document_query', 20 );

/**
 * Change the querystring based on caller of the albums media query
 *
 * @param $querystring
 *
 * @return string
 */
function bp_nouveau_object_template_results_folders_existing_document_query( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	if ( ! empty( $_POST['caller'] ) && 'bp-existing-document' == $_POST['caller'] ) {
		$querystring['folder_id'] = 0;
	}

	return http_build_query( $querystring );
}

/**
 * Save media
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_document_document_save() {
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
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please upload media before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// handle media uploaded.
	$media_ids = bp_document_add_handler();

	$media = '';
	if ( ! empty( $media_ids ) ) {
		ob_start();
		if ( bp_has_document( array( 'include' => implode( ',', $media_ids ) ) ) ) {
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/document-entry' );
			}
		}
		$media = ob_get_contents();
		ob_end_clean();
	}

	wp_send_json_success( array( 'media' => $media ) );
}

/**
 * Save folder
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_document_folder_save() {
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
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please enter title of folder.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// save media
	$id       = ! empty( $_POST['album_id'] ) ? $_POST['album_id'] : false;
	$group_id = ! empty( $_POST['group_id'] ) ? $_POST['group_id'] : false;
	$title    = $_POST['title'];
	$privacy  = ! empty( $_POST['privacy'] ) ? $_POST['privacy'] : 'public';
	$parent   = ! empty( $_POST['parent'] ) ? (int) $_POST['parent'] : 0;

	if ( $parent > 0 ) {
		$id = false;
	}

	$album_id = bp_folder_add( array( 'id' => $id, 'title' => $title, 'privacy' => $privacy, 'group_id' => $group_id, 'parent' => $parent ) );

	if ( ! $album_id ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem when trying to create the folder.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	if ( ! empty( $_POST['medias'] ) && is_array( $_POST['medias'] ) ) {
		// set album id for media
		foreach ( $_POST['medias'] as $key => $media ) {
			$_POST['medias'][$key]['folder_id'] = $album_id;
		}
	}

	// save all media uploaded
	bp_document_add_handler();

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . bp_get_document_slug() . '/folders/' . $album_id );
	} else {
		$redirect_url = trailingslashit( bp_loggedin_user_domain() . bp_get_document_slug() . '/folders/' . $album_id );
	}

	wp_send_json_success( array(
		'redirect_url'     => $redirect_url,
	) );
}

function bp_nouveau_ajax_document_folder_move() {

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

	// Move document
	$folder_id   = ! empty( $_POST['folder_id'] ) ? (int) $_POST['folder_id'] : 0;
	$document_id = ! empty( $_POST['document_id'] ) ? (int) $_POST['document_id'] : 0;

	if ( 0 === $document_id ) {
		wp_send_json_error( $response );
	}

	$document = bp_document_move_to_folder( $document_id, $folder_id );

	if ( $document > 0 ) {

		$content = '';
        ob_start();

		if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :

			if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>

                <div class="document-data-table-head">
                    <span class="data-head-sort-label">Sort By:</span>
                    <div class="data-head data-head-name">
                <span>
                    Name
                    <i class="bb-icon-triangle-fill"></i>
                </span>

                    </div>
                    <div class="data-head data-head-modified">
                <span>
                    Modified
                    <i class="bb-icon-triangle-fill"></i>
                </span>

                    </div>
                    <div class="data-head data-head-visibility">
                <span>
                    Visibility
                    <i class="bb-icon-triangle-fill"></i>
                </span>
                    </div>
                </div><!-- .document-data-table-head -->

                <div id="media-folder-document-data-table">
				<?php
				bp_get_template_part( 'document/activity-document-move' );
				bp_get_template_part( 'document/activity-document-folder-move' );

			endif;

			while ( bp_document() ) :
				bp_the_document();

				bp_get_template_part( 'document/document-entry' );

			endwhile;

			if ( bp_document_has_more_items() ) : ?>
                <div class="pager">
                    <div class="dt-more-container load-more">
                        <a class="button outline full"
                           href="<?php bp_document_load_more_link(); ?>"><?php _e( 'Load More',
								'buddyboss' ); ?></a>
                    </div>
                </div>
			<?php
			endif;

			if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
                </div> <!-- #media-folder-document-data-table -->
			<?php
			endif;

		else :

			bp_nouveau_user_feedback( 'media-loop-document-none' );

		endif;

		$content .= ob_get_clean();


		wp_send_json_success( array(
			'message'     => 'success',
            'html'          => $content
		) );


	} else {
		wp_send_json_error( $response );
	}

}

function bp_nouveau_ajax_document_update_file_name() {

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

	$document_id            = ! empty( $_POST['document_id'] ) ? (int) base64_decode( $_POST['document_id'] ) : 0;
	$attachment_document_id = ! empty( $_POST['attachment_document_id'] ) ? (int) base64_decode( $_POST['attachment_document_id'] ) : 0;
	$title                  = ! empty( $_POST['name'] ) ? $_POST['name'] : '';
	$type                   = ! empty( $_POST['document_type'] ) ? $_POST['document_type'] : '';

	if ( 'document' === $type ) {
		if ( 0 === $document_id || 0 === $attachment_document_id || '' === $title ) {
			wp_send_json_error( $response );
		}

		$document = bp_document_rename_file( $document_id, $attachment_document_id, $title );

		if ( $document > 0 ) {
			wp_send_json_success( array(
				'message' => 'success',
			) );
		} else {
			wp_send_json_error( $response );
		}
	} else {
		if ( 0 === $document_id || '' === $title ) {
			wp_send_json_error( $response );
		}

		$folder = bp_document_rename_folder( $document_id, $title );

		if ( $folder > 0 ) {
			wp_send_json_success( array(
				'message' => 'success',
			) );
		} else {
			wp_send_json_error( $response );
		}
	}

}

/**
 * Rename folder
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_document_edit_folder() {
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
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please enter title of folder.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// save media
	$group_id = ! empty( $_POST['group_id'] ) ? $_POST['group_id'] : false;
	$title    = $_POST['title'];
	$privacy  = ! empty( $_POST['privacy'] ) ? $_POST['privacy'] : 'public';
	$parent   = ! empty( $_POST['parent'] ) ? (int) $_POST['parent'] : 0;
	$move_to  = ! empty( $_POST['moveTo'] ) ? (int) $_POST['moveTo'] : 0;

	if ( $parent > 0 ) {
		$id = false;
	}

	if ( 0 === $move_to ) {
		$move_to = $parent;
	}

	$album_id = bp_folder_add( array( 'id' => $parent, 'title' => $title, 'privacy' => $privacy, 'group_id' => $group_id, 'parent' => $move_to ) );

	if ( ! $album_id ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem when trying to create the folder.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . bp_get_document_slug() . '/folders/' . $album_id );
	} else {
		$redirect_url = trailingslashit( bp_loggedin_user_domain() . bp_get_document_slug() . '/folders/' . $album_id );
	}

	wp_send_json_success( array(
		'redirect_url'     => $redirect_url,
	) );
}
