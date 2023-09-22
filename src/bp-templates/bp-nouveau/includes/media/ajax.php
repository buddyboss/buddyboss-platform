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
				'media_get_media_description' => array(
					'function' => 'bp_nouveau_ajax_media_get_media_description',
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
			array(
				'media_get_album_view' => array(
					'function' => 'bp_nouveau_ajax_media_get_album_view',
					'nopriv'   => true,
				),
			),
			array(
				'media_move' => array(
					'function' => 'bp_nouveau_ajax_media_move',
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$page = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );

	$page = ! empty( $page ) ? $page : 1;

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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Upload file.
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_SANITIZE_NUMBER_INT );

	if (
		( ( bp_is_my_profile() || bp_is_user_media() ) && empty( bb_user_can_create_media() ) ) ||
		( bp_is_active( 'groups' ) && ! empty( $group_id ) && ! groups_can_user_manage_media( bp_loggedin_user_id(), $group_id ) )
	) {
		wp_send_json_error( $response );
	}

	$medias = filter_input( INPUT_POST, 'medias', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( empty( $medias ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please upload media before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$privacy = bb_filter_input_string( INPUT_POST, 'privacy' );
	$content = bb_filter_input_string( INPUT_POST, 'content' );

	// handle media uploaded.
	$media_ids = bp_media_add_handler( $medias, $privacy, $content );

	$media = '';
	if ( ! empty( $media_ids ) ) {
		ob_start();
		if (
			bp_has_media(
				array(
					'include'  => implode( ',', $media_ids ),
					'per_page' => 0,
				)
			)
		) {
			while ( bp_media() ) {
				bp_the_media();
				bp_get_template_part( 'media/entry' );
			}
		}
		$media = ob_get_contents();
		ob_end_clean();
	}

	$media_personal_count = 0;
	$media_group_count    = 0;
	$media_all_count      = 0;
	if ( bp_is_user_media() ) {
		add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
		bp_has_media( bp_ajax_querystring( 'media' ) );
		$media_personal_count = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
		remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
	}

	if ( bp_is_group_media() ) {
		$media_group_count = bp_media_get_total_group_media_count();
	}

	if ( bp_is_media_directory() ) {

		add_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );
		bp_has_media( bp_ajax_querystring( 'media' ) );
		$media_all_count = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
		remove_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );

		add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
		bp_has_media( bp_ajax_querystring( 'media' ) );
		$media_personal_count = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
		remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );

		add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_groups_scope', 20 );
		bp_has_media( bp_ajax_querystring( 'groups' ) );
		$media_group_count = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
		remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_groups_scope', 20 );

	}
	wp_send_json_success(
		array(
			'media'                => $media,
			'media_personal_count' => $media_personal_count,
			'media_group_count'    => $media_group_count,
			'media_all_count'      => $media_all_count,
		)
	);

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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	$media_content = '';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$media       = filter_input( INPUT_POST, 'media', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$activity_id = filter_input( INPUT_POST, 'activity_id', FILTER_SANITIZE_NUMBER_INT );
	$from_where  = bb_filter_input_string( INPUT_POST, 'from_where' );

	if ( empty( $media ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please select media to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$media_ids = array();
	foreach ( $media as $media_id ) {

		if ( bp_media_user_can_delete( $media_id ) ) {

			// delete media.
			if ( bp_media_delete( array( 'id' => $media_id ) ) ) {
				$media_ids[] = $media_id;
			}
		}
	}

	if ( count( $media_ids ) !== count( $media ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem deleting media.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	$response = array();
	if ( $activity_id ) {
		$response = bp_media_get_activity_media( $_POST['activity_id'] );
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

	$media_personal_count = 0;
	$media_group_count    = 0;
	if ( bp_is_user_media() ) {
		add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
		bp_has_media( bp_ajax_querystring( 'media' ) );
		$media_personal_count = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
		remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
	}
	if ( bp_is_group_media() ) {

	    // Update the count of photos in groups in navigation menu.
	    wp_cache_flush();

		$media_group_count = bp_media_get_total_group_media_count();
	}

	if ( bp_is_group_albums() ) {

		// Update the count of photos in groups in navigation menu when you are in single albums page.
		wp_cache_flush();
	}

	wp_send_json_success(
		array(
			'media'                => $media,
			'media_ids'            => ( isset( $response['media_activity_ids'] ) ) ? $response['media_activity_ids'] : '',
			'media_content'        => ( isset( $response['content'] ) ) ? $response['content'] : '',
			'delete_activity'      => $delete_box,
			'activity_content'     => $activity_content,
			'media_personal_count' => $media_personal_count,
			'media_group_count'    => $media_group_count,
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$medias = bb_filter_input_string( INPUT_POST, 'medias', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( empty( $medias ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please upload media before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$album_id = filter_input( INPUT_POST, 'album_id', FILTER_VALIDATE_INT );

	if ( empty( $album_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Please provide album to move media.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

	// Save media.
	$media_ids = array();
	foreach ( $medias as $media_id ) {

		$media = bp_media_move_media_to_album( $media_id, $album_id, $group_id );

		if ( ! $media ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
				esc_html__( 'There was a problem when trying to move the media.', 'buddyboss' )
			);

			wp_send_json_error( $response );
		}

		$media_ids[] = $media_id;
	}

	// Flush the cache.
	wp_cache_flush();

	$media = '';
	if ( ! empty( $media_ids ) ) {
		ob_start();
		if (
			bp_has_media(
				array(
					'include'  => implode( ',', $media_ids ),
					'per_page' => 0,
				)
			)
		) {
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

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

	// save media.
	$id       = filter_input( INPUT_POST, 'album_id', FILTER_VALIDATE_INT );
	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$privacy  = bb_filter_input_string( INPUT_POST, 'privacy' );

	$id       = ! empty( $id ) ? $id : false;
	$group_id = ! empty( $group_id ) ? $group_id : false;
	$privacy  = ! empty( $privacy ) ? $privacy : 'public';

	$user_id = bp_loggedin_user_id();
	if ( $id ) {
		$album   = new BP_Media_Album( $id );
		$user_id = $album->user_id;
	}

	if (
		empty( $user_id ) ||
		( ! empty( $group_id ) && ! groups_can_user_manage_albums( $user_id, $group_id ) ) ||
		! empty( $user_id ) && bp_is_my_profile() && ! bb_user_can_create_media()
	) {
		wp_send_json_error( $response );
	}

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

	$medias = filter_input( INPUT_POST, 'medias', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

	if ( ! empty( $medias ) ) {
		// set album id for media.
		foreach ( $medias as $key => $media ) {
			$medias[ $key ]['album_id'] = $album_id;
		}

		// save all media uploaded.
		bp_media_add_handler( $medias, $privacy );
	}

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' . $album_id );
	} else {
		$redirect_url = trailingslashit( bp_loggedin_user_domain() . bp_get_media_slug() . '/albums/' . $album_id );
	}

	$album = new BP_Media_Album( $album_id );

	if ( $group_id > 0 ) {
		$ul = bp_media_user_media_album_tree_view_li_html( $album->user_id, $group_id );
	} else {
		$ul = bp_media_user_media_album_tree_view_li_html( bp_loggedin_user_id() );
	}

	wp_send_json_success(
		array(
			'redirect_url' => $redirect_url,
			'tree_view'    => $ul,
			'album_id'     => $album_id,
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

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

	if ( ! bp_album_user_can_delete( $album_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'You do not have permission to delete this album.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete album.
	$album_id = bp_album_delete( array( 'id' => $album_id ) );

	if ( ! $album_id ) {
		wp_send_json_error( $response );
	}

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/albums/' );

		// Flush the cache so update the count.
		wp_cache_flush();
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$post_id  = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

	// check activity is media or not.
	$media_activity = bp_activity_get_meta( $post_id, 'bp_media_activity', true );

	remove_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );
	add_action( 'bp_before_activity_activity_content', 'bp_nouveau_activity_description' );
	add_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );

	if ( ! empty( $media_activity ) ) {
		$args = array(
			'include'     => $post_id,
			'show_hidden' => true,
			'scope'       => 'media',
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$media_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );

	if ( empty( $media_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide media id to update.', 'buddyboss' )
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

	if ( ! in_array( $privacy, array_keys( bp_media_get_visibility_levels() ) ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Privacy option is not valid.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

    if ( ! bp_media_user_can_edit( $media_id ) ) {
	    $response['feedback'] = sprintf(
		    '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
		    esc_html__( 'You don\'t have a permission to update privacy.', 'buddyboss' )
	    );

	    wp_send_json_error( $response );
    }

	$media          = new BP_Media( $media_id );
	$media->privacy = $privacy;
	$media->save();

	if ( bp_is_active( 'activity' ) && ! empty( $media->id ) ) {
		$attachment_id = $media->attachment_id;
		$activity_id   = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );
		if ( ! empty( $activity_id ) ) {
			$activity = new BP_Activity_Activity( $activity_id );
			if ( ! empty( $activity->id ) ) {
				$activity->privacy = $privacy;
				$activity->save();
			}
		}
	}

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

	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_media' ) ) {
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
	if ( 'media' !== $object ) {
		return $results;
	}

	$results['scopes'] = array();

	add_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$results['scopes']['all'] = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_results_media_all_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'media' ) );
	$results['scopes']['personal'] = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
	remove_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_personal_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_media_object_template_results_media_groups_scope', 20 );
	bp_has_media( bp_ajax_querystring( 'groups' ) );
	$results['scopes']['groups'] = bp_core_number_format( $GLOBALS['media_template']->total_media_count );
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
	$querystring = bp_parse_args( $querystring );

	$caller = bb_filter_input_string( INPUT_POST, 'caller' );

	if ( ! empty( $caller ) && 'bp-existing-media' === $caller ) {
		$querystring['album_id'] = 'existing-media';
	}

	return http_build_query( $querystring );
}

/**
 * Get description for the media.
 *
 * @since BuddyBoss 1.4.4
 */
function bp_nouveau_ajax_media_get_media_description() {

	$media_description = '';

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	$media_id      = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );

	if ( empty( $media_id ) || empty( $attachment_id ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $attachment_id ) ) {
		wp_send_json_error( $response );
	}

	$media = new BP_Media( $media_id );
	if ( bp_is_active( 'activity' ) && ! empty( $media->activity_id ) ) {

		remove_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );
		add_action( 'bp_before_activity_activity_content', 'bp_nouveau_activity_description' );
		add_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );

		$remove_comment_btn = false;

		$activity = new BP_Activity_Activity( $media->activity_id );
		if ( ! empty( $activity->id ) ) {
			$get_activity = new BP_Activity_Activity( $activity->secondary_item_id );
			if (
				! empty( $get_activity->id ) &&
				(
					( in_array( $activity->type, array( 'activity_update', 'activity_comment' ), true ) && ! empty( $get_activity->secondary_item_id ) && ! empty( $get_activity->item_id ) )
					|| in_array( $activity->privacy, array( 'public' ), true ) && empty( $get_activity->secondary_item_id ) && empty( $get_activity->item_id )
				)
			) {
				$remove_comment_btn = true;
			}
		}

		if ( true === $remove_comment_btn ) {
			add_filter( 'bp_nouveau_get_activity_comment_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			add_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			add_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			add_filter( 'bp_nouveau_get_activity_comment_buttons_activity_state', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
		}

		$args = array(
			'include'     => $media->activity_id,
			'privacy'     => false,
			'scope'       => false,
			'show_hidden' => true,
		);

		ob_start();
		if ( bp_has_activities( $args ) ) {
			while ( bp_activities() ) {
				bp_the_activity();
				bp_get_template_part( 'activity/entry' );
			}
		}
		$media_description = ob_get_contents();
		ob_end_clean();

		if ( true === $remove_comment_btn ) {
			remove_filter( 'bp_nouveau_get_activity_comment_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			remove_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			remove_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			remove_filter( 'bp_nouveau_get_activity_comment_buttons_activity_state', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
		}

		remove_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );
		remove_action( 'bp_before_activity_activity_content', 'bp_nouveau_activity_description' );
		add_action( 'bp_activity_entry_content', 'bp_media_activity_entry' );
	}

	if ( empty( trim( $media_description ) ) ) {
		$content          = get_post_field( 'post_content', $attachment_id );
		$media_privacy    = bb_media_user_can_access( $media_id, 'photo' );
		$can_download_btn = true === (bool) $media_privacy['can_download'];
		$can_edit_btn     = true === (bool) $media_privacy['can_edit'];
		$can_view         = true === (bool) $media_privacy['can_view'];
		$user_domain      = bp_core_get_user_domain( $media->user_id );
		$display_name     = bp_core_get_user_displayname( $media->user_id );
		$time_since       = bp_core_time_since( $media->date_created );
		$avatar           = bp_core_fetch_avatar(
			array(
				'item_id' => $media->user_id,
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
						<p><a href="<?php echo esc_url( $user_domain ); ?>"><?php echo esc_html( $display_name ); ?></a> <?php esc_html_e( 'uploaded an image', 'buddyboss' ); ?><a href="<?php echo esc_url( $user_domain ); ?>" class="view activity-time-since"></p>
						<p class="activity-date"><a href="<?php echo esc_url( $user_domain ); ?>"><?php echo $time_since; ?></a></p>
					</div>
				</div>
				<div class="activity-media-description">
					<div class="bp-media-activity-description"><?php echo esc_html( $content ); ?></div>
					<?php
					if ( $can_edit_btn ) {
						?>
						<a class="bp-add-media-activity-description <?php echo ( ! empty( $content ) ? esc_attr( 'show-edit' ) : esc_attr( 'show-add' ) ); ?>" href="#">
							<span class="bb-icon-l bb-icon-edit"></span>
							<span class="add"><?php esc_html_e( 'Add a description', 'buddyboss' ); ?></span>
							<span class="edit"><?php esc_html_e( 'Edit', 'buddyboss' ); ?></span>
						</a>

						<div class="bp-edit-media-activity-description" style="display: none;">
							<div class="innerWrap">
								<textarea id="add-activity-description" title="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" class="textInput" name="caption_text" placeholder="<?php esc_html_e( 'Add a description', 'buddyboss' ); ?>" role="textbox"><?php echo sanitize_textarea_field( $content ); ?></textarea>
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
				if ( ! empty( $media_id ) && $can_download_btn ) {
					$download_url = bp_media_download_link( $attachment_id, $media_id );
					if ( $download_url ) {
						?>
						<a class="download-media" href="<?php echo esc_url( $download_url ); ?>">
							<?php esc_html_e( 'Download', 'buddyboss' ); ?>
						</a>
						<?php
					}
				}
				?>
			</li>
			<?php
			$media_description = ob_get_contents();
			ob_end_clean();
		}
	}

	wp_send_json_success(
		array(
			'description' => $media_description,
		)
	);
}

/**
 * Return the album view.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_nouveau_ajax_media_get_album_view() {

	$type = bb_filter_input_string( INPUT_POST, 'type' );
	$id   = bb_filter_input_string( INPUT_POST, 'id' );

	if ( 'profile' === $type ) {
		$ul = bp_media_user_media_album_tree_view_li_html( $id, 0 );
	} else {
		$ul = bp_media_user_media_album_tree_view_li_html( bp_loggedin_user_id(), $id );
	}

	$first_text   = '';
	$create_album = false;
	if ( 'profile' === $type ) {
		$first_text   = esc_html__( ' Medias', 'buddyboss' );
		$create_album = is_user_logged_in() && bp_is_profile_media_support_enabled() && bb_user_can_create_media();

	} else {
		if ( bp_is_active( 'groups' ) ) {
			$group        = groups_get_group( (int) $id );
			$first_text   = bp_get_group_name( $group );
			$create_album = groups_can_user_manage_albums( bp_loggedin_user_id(), (int) $id );
		}
	}

	wp_send_json_success(
		array(
			'message'         => 'success',
			'html'            => $ul,
			'first_span_text' => stripslashes( $first_text ),
			'create_album'    => $create_album,
		)
	);
}

/**
 * Ajax media move.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_nouveau_ajax_media_move() {

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
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Move media.
	$album_id    = filter_input( INPUT_POST, 'album_id', FILTER_VALIDATE_INT );
	$media_id    = filter_input( INPUT_POST, 'media_id', FILTER_VALIDATE_INT );
	$group_id    = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$activity_id = filter_input( INPUT_POST, 'activity_id', FILTER_VALIDATE_INT );

	if ( 0 === $media_id ) {
		wp_send_json_error( $response );
	}

	if ( (int) $media_id > 0 ) {
		$has_access = bp_media_user_can_edit( $media_id );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have permission to move this media.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	if ( (int) $album_id > 0 ) {
		$has_access = bp_album_user_can_edit( $album_id );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have permission to move this media.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	$media    = bp_media_move_media_to_album( $media_id, $album_id, $group_id );

	// Flush the cache.
	wp_cache_flush();

	$response = bp_media_get_activity_media( $activity_id );

	if ( $media > 0 ) {
		$content = '';
		wp_send_json_success(
			array(
				'media_ids'     => $response['media_activity_ids'],
				'media_content' => $response['content'],
				'message'       => 'success',
				'html'          => $content,
			)
		);
	} else {
		wp_send_json_error( $response );
	}

}
