<?php
/**
 * Media Ajax functions
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
				'media_filter' => array(
					'function' => 'bp_nouveau_ajax_object_template_loader',
					'nopriv'   => true,
				),
			),
			array(
				'media_albums_loader' => array(
					'function' => 'bp_nouveau_ajax_albums_loader',
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
				'media_delete' => array(
					'function' => 'bp_nouveau_ajax_media_delete',
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
				'media_album_delete' => array(
					'function' => 'bp_nouveau_ajax_media_album_delete',
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
			array(
				'media_update_privacy' => array(
					'function' => 'bp_nouveau_ajax_media_update_privacy',
					'nopriv'   => true,
				),
			),
			array(
				'media_description_save' => array(
					'function' => 'bp_nouveau_ajax_media_description_save',
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
 * Upload a media via a POST request.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_media_upload() {
	$response = array(
		'feedback' => __( 'There was a problem when trying to upload this file.', 'buddyboss' ),
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
	foreach ( $media as $media_id ) {

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

	wp_send_json_success(
		array(
			'media' => $media,
		)
	);
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
	$album         = new BP_Media_Album( $_POST['album_id'] );
	if ( ! empty( $album ) ) {
		$album_privacy = $album->privacy;
	}

	// save media
	$medias    = $_POST['medias'];
	$media_ids = array();
	foreach ( $medias as $media_id ) {

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

	wp_send_json_success(
		array(
			'media' => $media,
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

	if ( ! array_key_exists( $privacy, bp_media_get_visibility_levels() ) && ! empty( $id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Invalid privacy status.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	$album_id = bp_album_add(
		array(
			'id'       => $id,
			'title'    => $title,
			'privacy'  => $privacy,
			'group_id' => $group_id,
		)
	);

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
		$group_link   = bp_get_group_permalink( groups_get_group( $_POST['group_id'] ) );
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

	// check activity is media or not.
	$media_activity = bp_activity_get_meta( $_POST['id'], 'bp_media_activity', true );

	remove_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );
	add_action( 'bp_before_activity_activity_content', 'bp_nouveau_activity_description' );
	add_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );

	if ( ! empty( $media_activity ) ) {
		$args = array(
			'include'     => $_POST['id'],
			'show_hidden' => true,
			'scope'       => 'media',
		);
	} else {
		$args = array(
			'include' => $_POST['id'],
			'privacy' => false,
			'scope'   => false,
		);
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
	add_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );

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

	// delete attachment with its meta
	$deleted = wp_delete_attachment( $_POST['id'], true );

	if ( ! $deleted ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success();
}

/**
 * Update media privacy
 *
 * @since BuddyBoss 1.2.0
 */
function bp_nouveau_ajax_media_update_privacy() {
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
			esc_html__( 'Please provide media id to update.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( empty( $_POST['privacy'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide privacy to update.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$privacy = $_POST['privacy'];
	if ( ! in_array( $privacy, array_keys( bp_media_get_visibility_levels() ) ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Privacy option is not valid.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$media_id = $_POST['id'];

	$media          = new BP_Media( $media_id );
	$media->privacy = $privacy;
	$media->save();

	wp_send_json_success();
}

/**
 * Update media activity description.
 *
 * @since BuddyBoss 1.3.5
 */
function bp_nouveau_ajax_media_description_save() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	$attachment_id = $_POST['attachment_id'];
	$description = $_POST['description'];

	$attachment = get_post( $attachment_id );

	if ( empty( $attachment ) && ( 'attachment' !== $attachment->post_type ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was an error for updating a description. Please try again.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$media_post['ID']           = $attachment_id;
	$media_post['post_content'] = $description;
	wp_update_post( $media_post );

	$response['description'] = $description;
	wp_send_json_success( $response );
}

add_filter( 'bp_nouveau_object_template_result', 'bp_nouveau_object_template_results_media_tabs', 10, 2 );
/**
 * Object template results media tabs.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_media_tabs( $results, $object ) {
	if ( $object != 'media' ) {
		return $results;
	}

	$results['scopes'] = array();

	add_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$results['scopes']['all'] = $GLOBALS['media_template']->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$results['scopes']['personal'] = $GLOBALS['media_template']->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_groups_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'groups' ) );
	$results['scopes']['groups'] = $GLOBALS['media_template']->total_media_count;
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_groups_scope', 20 );

	return $results;
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
