<?php
/**
 * Document Ajax functions
 *
 * @since BuddyBoss 1.4.0
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	function () {
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
				'document_child_folder_save' => array(
					'function' => 'bp_nouveau_ajax_document_child_folder_save',
					'nopriv'   => true,
				),
			),
			array(
				'document_move' => array(
					'function' => 'bp_nouveau_ajax_document_move',
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
			),
			array(
				'document_delete' => array(
					'function' => 'bp_nouveau_ajax_document_delete',
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
				'document_get_folder_view' => array(
					'function' => 'bp_nouveau_ajax_document_get_folder_view',
					'nopriv'   => true,
				),
			),
			array(
				'document_save_privacy' => array(
					'function' => 'bp_nouveau_ajax_document_save_privacy',
					'nopriv'   => true,
				),
			),
			array(
				'document_get_activity' => array(
					'function' => 'bp_nouveau_ajax_document_get_activity',
					'nopriv'   => true,
				),
			),
			array(
				'document_activity_delete' => array(
					'function' => 'bp_nouveau_ajax_document_activity_delete',
					'nopriv'   => true,
				),
			),
			array(
				'document_folder_delete' => array(
					'function' => 'bp_nouveau_ajax_document_folder_delete',
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
 * Upload a document via a POST request.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_upload() {
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

	// Use default nonce.
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	add_filter( 'upload_dir', 'bp_document_upload_dir' );

	// Upload file.
	$result = bp_document_upload();

	remove_filter( 'upload_dir', 'bp_document_upload_dir' );

	if ( is_wp_error( $result ) ) {

		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$error_msg = $result->get_error_message();
			if ( 'Sorry, this file type is not permitted for security reasons.' === $error_msg ) {
				$response['feedback'] = __( 'Make sure you whitelisted extension and MIME TYPE in media settings and added correct MIME TYPE in extension entry.', 'buddyboss' );
			} else {
				$response['feedback'] = $result->get_error_message();
			}
		} else {
			$response['feedback'] = $result->get_error_message();
		}
		wp_send_json_error( $response, $result->get_error_code() );
	}

	wp_send_json_success( $result );
}

/**
 * Delete folder.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_folder_delete() {
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

	// Use default nonce.
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['folder_id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide ID of folder to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	$folder_id = filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT );
	if ( ! bp_folder_user_can_delete( $folder_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'You do not have permission to delete this folder.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete folder.
	$folder_id = bp_folder_delete( array( 'id' => $folder_id ) );

	if ( ! $folder_id ) {
		wp_send_json_error( $response );
	}

	$group_id = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : false;

	if ( ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
		$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
		$redirect_url = trailingslashit( $group_link . '/documents/' );
	} else {
		$redirect_url = trailingslashit( bp_displayed_user_domain() . bp_get_document_slug() );
	}

	wp_send_json_success(
		array(
			'redirect_url' => $redirect_url,
		)
	);
}

/**
 * Get activity for the document.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_get_activity() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	$post_id 	= filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$group_id 	= filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$author_id 	= filter_input( INPUT_POST, 'author', FILTER_VALIDATE_INT );

	// check activity is document or not.
	$document_activity = bp_activity_get_meta( $post_id, 'bp_document_activity', true );

	remove_action( 'bp_activity_entry_content', 'bp_document_activity_entry' );
	add_action( 'bp_before_activity_activity_content', 'bp_nouveau_document_activity_description' );
	add_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );

	if ( ! empty( $document_activity ) ) {
		$args = array(
			'include'     => $post_id,
			'show_hidden' => true,
			'scope'       => 'document',
		);
	} else {
		if ( $group_id > 0 ) {
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
	remove_action( 'bp_before_activity_activity_content', 'bp_nouveau_document_activity_description' );
	add_action( 'bp_activity_entry_content', 'bp_document_activity_entry' );

	wp_send_json_success(
		array(
			'activity' => $activity,
		)
	);
}

/**
 * Delete attachment with its files
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_delete_attachment() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Nonce check!
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide attachment id to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	// delete attachment with its meta.
	$post_id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$deleted = wp_delete_attachment( $post_id, true );

	if ( ! $deleted ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success();
}

// add_filter( 'bp_nouveau_object_template_result', 'bp_nouveau_object_template_results_document_tabs', 10, 2 );

/**
 * Object template results media tabs.
 *
 * @param $results
 * @param $object
 *
 * @since BuddyBoss 1.4.0
 *
 * @return mixed
 */
function bp_nouveau_object_template_results_document_tabs( $results, $object ) {
	if ( 'document' !== $object ) {
		return $results;
	}

	$results['scopes'] = array();

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_all_scope', 20 );
	bp_has_document( bp_ajax_querystring( 'document' ) );
	$results['scopes']['all'] = $GLOBALS['document_template']->total_document_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_all_scope', 20 );

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_personal_scope', 20 );
	bp_has_document( bp_ajax_querystring( 'document' ) );
	$results['scopes']['personal'] = $GLOBALS['document_template']->total_document_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_document_personal_scope', 20 );

	return $results;
}

/**
 * Object template results document all scope.
 *
 * @param $querystring
 *
 * @since BuddyBoss 1.4.0
 *
 * @return string
 */
function bp_nouveau_object_template_results_document_all_scope( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	$querystring['scope'] = array();

	if ( bp_is_profile_document_support_enabled() && bp_is_active( 'friends' ) ) {
		$querystring['scope'][] = 'friends';
	}

	if ( bp_is_group_document_support_enabled() && bp_is_active( 'groups' ) ) {
		$querystring['scope'][] = 'groups';
	}

	if ( bp_is_profile_document_support_enabled() && is_user_logged_in() ) {
		$querystring['scope'][] = 'personal';
	}

	$querystring['user_id']     = 0;
	$querystring['count_total'] = true;
	$querystring['type']        = 'document';
	return http_build_query( $querystring );
}

/**
 * Object template results media personal scope.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_object_template_results_document_personal_scope( $querystring ) {

	if ( ! bp_is_profile_document_support_enabled() ) {
		return $querystring;
	}

	$querystring = wp_parse_args( $querystring );

	$querystring['scope']   = 'personal';
	$querystring['user_id'] = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	$querystring['type']    = 'document';
	$privacy                = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
		$privacy[] = 'onlyme';
	}

	$querystring['privacy']     = $privacy;
	$querystring['count_total'] = true;

	return http_build_query( $querystring );
}

/**
 * Change the querystring based on caller of the albums media query
 *
 * @param $querystring
 */
function bp_nouveau_object_template_results_folders_existing_document_query( $querystring ) {
	$querystring = wp_parse_args( $querystring );

	if ( ! empty( $_POST['caller'] ) && 'bp-existing-document' === $_POST['caller'] ) {
		$querystring['folder_id'] = 0;
	}

	return http_build_query( $querystring );
}

add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_folders_existing_document_query', 20 );

/**
 * Save media
 *
 * @since BuddyBoss 1.4.0
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

	// Use default nonce.
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['documents'] ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please upload a document before saving.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		$response['feedback'] = sprintf(
				'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
				esc_html__( 'Please login to upload a document.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	if ( isset( $_POST['documents'] ) && ! empty( $_POST['documents'] ) && isset( $_POST['folder_id'] ) && (int) $_POST['folder_id'] > 0 ) {
		$documents = $_POST['documents'];
		$folder_id  = (int) $_POST['folder_id'];
		if ( ! empty( $documents ) && is_array( $documents ) ) {
			// set folder id for document.
			foreach ( $documents as $key => $document ) {
				if ( 0 === (int) $document['folder_id'] ) {
					$_POST['documents'][ $key ]['folder_id'] = $folder_id;
				}
			}
		}
	}

	// handle media uploaded.
	$document_ids = bp_document_add_handler( $_POST['documents'] );
	$document     = '';
	if ( ! empty( $document_ids ) ) {
		ob_start();
		if ( bp_has_document( array( 'include' => implode( ',', $document_ids ) ) ) ) {
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/document-entry' );
			}
		}
		$document = ob_get_contents();
		ob_end_clean();
	}

	wp_send_json_success( array( 'document' => $document ) );
}

/**
 * Save folder
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_folder_save() {
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
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['title'] ) ) {
		$response['feedback'] = esc_html__( 'Please enter title of folder.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		$response['feedback'] = esc_html__( 'Please login to create a folder.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	// save media.
	$id        = ! empty( $_POST['folder_id'] ) ? filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT ) : false;
	$group_id  = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : false;
	$title     = $_POST['title'];
	$privacy   = ! empty( $_POST['privacy'] ) ? filter_input( INPUT_POST, 'privacy', FILTER_SANITIZE_STRING ) : 'public';
	$parent    = ! empty( $_POST['parent'] ) ? (int) filter_input( INPUT_POST, 'parent', FILTER_VALIDATE_INT ) : 0;
	$folder_id = ! empty( $_POST['folder_id'] ) ? (int) filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT ) : 0;

	if ( $parent > 0 ) {
		$id = false;
	}

	if ( ! $id && ! $parent ) {
		$parent = $folder_id;
	}

	if ( $parent > 0 ) {
		$parent_folder = BP_Document_Folder::get_folder_data( array( $parent ) );
		$privacy       = $parent_folder[0]->privacy;
	}

	if ( (int) $parent > 0 ) {
		$has_access = bp_folder_user_can_edit( $parent );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have a permission to create a folder inside this folder.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	$folder_id = bp_folder_add(
		array(
			'id'       => $id,
			'title'    => $title,
			'privacy'  => $privacy,
			'group_id' => $group_id,
			'parent'   => $parent,
		)
	);

	if ( ! $folder_id ) {
		$response['feedback'] = esc_html__( 'There was a problem when trying to create the folder.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	if ( $group_id > 0 ) {
		$ul = bp_document_user_document_folder_tree_view_li_html( 0, $group_id );
	} else {
		$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
	}

	$document = '';
	if ( ! empty( $folder_id ) ) {
		ob_start();
		if ( bp_has_folders( array( 'include' => $folder_id ) ) ) {
			while ( bp_folder() ) {
				bp_the_folder();
				bp_get_template_part( 'document/document-entry' );
			}
		}
		$document = ob_get_contents();
		ob_end_clean();
	}

	$response = array(
		'document'  => $document,
		'tree_view' => $ul,
		'folder_id' => $folder_id,
	);

	wp_send_json_success( $response );
}

function bp_nouveau_ajax_document_child_folder_save() {
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
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['title'] ) ) {
		$response['feedback'] = esc_html__( 'Please enter title of folder.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		$response['feedback'] = esc_html__( 'Please login to create a folder.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	// save folder.
	$id        = 0;
	$group_id  = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : false;
	$title     = $_POST['title'];
	$folder_id = ! empty( $_POST['folder_id'] ) ? (int) filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT ) : 0;
	$privacy   = '';

	if ( $folder_id > 0 ) {
		$parent_folder = BP_Document_Folder::get_folder_data( array( $folder_id ) );
		$privacy       = $parent_folder[0]->privacy;
	}

	if ( (int) $folder_id > 0 ) {
		$has_access = bp_folder_user_can_edit( $folder_id );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have permission to create folder inside this folder.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	$folder_id = bp_folder_add(
		array(
			'id'       => false,
			'title'    => $title,
			'privacy'  => $privacy,
			'group_id' => $group_id,
			'parent'   => $folder_id,
		)
	);

	if ( ! $folder_id ) {
		$response['feedback'] = esc_html__( 'There was a problem when trying to create the folder.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	if ( $group_id > 0 ) {
		$ul = bp_document_user_document_folder_tree_view_li_html( 0, $group_id );
	} else {
		$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id() );
	}

	$document = '';
	if ( ! empty( $folder_id ) ) {
		ob_start();
		if ( bp_has_folders( array( 'include' => $folder_id ) ) ) {
			while ( bp_folder() ) {
				bp_the_folder();
				bp_get_template_part( 'document/document-entry' );
			}
		}
		$document = ob_get_contents();
		ob_end_clean();
	}

	$response = array(
		'document'  => $document,
		'tree_view' => $ul,
		'folder_id' => $folder_id,
	);

	wp_send_json_success( $response );
}

/**
 * Ajax document move.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_move() {

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
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Move document.
	$folder_id   = ! empty( $_POST['folder_id'] ) ? (int) $_POST['folder_id'] : 0;
	$document_id = ! empty( $_POST['document_id'] ) ? (int) $_POST['document_id'] : 0;
	$group_id    = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;

	if ( 0 === $document_id ) {
		wp_send_json_error( $response );
	}

	if ( (int) $document_id > 0 ) {
		$has_access = bp_document_user_can_edit( $document_id );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have permission to move this document.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	if ( (int) $folder_id > 0 ) {
		$has_access = bp_folder_user_can_edit( $folder_id );
		if ( ! $has_access ) {
			$response['feedback'] = esc_html__( 'You don\'t have permission to move this document.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	$document = bp_document_move_document_to_folder( $document_id, $folder_id, $group_id );

	if ( $document > 0 ) {

		$content = '';
		ob_start();

		if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :

			if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
				?>

				<div class="document-data-table-head">
					<span class="data-head-sort-label"><?php esc_html_e( 'Sort By:', 'buddyboss' ); ?></span>
					<div class="data-head data-head-name">
				<span>
					<?php esc_html_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>

					</div>
					<div class="data-head data-head-modified">
				<span>
					<?php esc_html_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>

					</div>
					<div class="data-head data-head-visibility">
				<span>
					<?php esc_html_e( 'Visibility', 'buddyboss' ); ?>
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

			if ( bp_document_has_more_items() ) :
				?>
				<div class="pager">
					<div class="dt-more-container load-more">
						<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
					</div>
				</div>
				<?php
			endif;

			if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
				?>
				</div> <!-- #media-folder-document-data-table -->
				<?php
			endif;

		else :

			bp_nouveau_user_feedback( 'media-loop-document-none' );

		endif;

		$content .= ob_get_clean();

		wp_send_json_success(
			array(
				'message' => 'success',
				'html'    => $content,
			)
		);

	} else {
		wp_send_json_error( $response );
	}

}

/**
 * Update the document name.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_update_file_name() {

	$response = array(
		'feedback' => esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	$document_id            = ! empty( $_POST['document_id'] ) ? (int) filter_input( INPUT_POST, 'document_id', FILTER_SANITIZE_STRING ) : 0;
	$attachment_document_id = ! empty( $_POST['attachment_document_id'] ) ? (int) filter_input( INPUT_POST, 'attachment_document_id', FILTER_SANITIZE_STRING ) : 0;
	$title                  = ! empty( $_POST['name'] ) ? filter_input( INPUT_POST, 'name', FILTER_SANITIZE_STRING ) : '';
	$type                   = ! empty( $_POST['document_type'] ) ? filter_input( INPUT_POST, 'document_type', FILTER_SANITIZE_STRING ) : '';

	if ( 'document' === $type ) {
		if ( 0 === $document_id || 0 === $attachment_document_id || '' === $title ) {
			wp_send_json_error( $response );
		}

		if ( (int) $document_id > 0 ) {
			$has_access = bp_document_user_can_edit( $document_id );
			if ( ! $has_access ) {
				$response['feedback'] = esc_html__( "You don't have a permission to rename the document.", 'buddyboss' );
				wp_send_json_error( $response );
			}
		}

		$document = bp_document_rename_file( $document_id, $attachment_document_id, $title );

		if ( isset( $document['document_id'] ) && $document['document_id'] > 0 ) {
			wp_send_json_success(
				array(
					'message'  => 'success',
					'response' => $document,
				)
			);
		} else {
			if ( '' === $document ) {
				wp_send_json_error( $response );
			} else {
				$response = array(
						'feedback' => $document,
				);
				wp_send_json_error( $response );
			}
		}
	} else {
		if ( 0 === $document_id || '' === $title ) {
			wp_send_json_error( $response );
		}

		if ( (int) $document_id > 0 ) {
			$has_access = bp_folder_user_can_edit( $document_id );
			if ( ! $has_access ) {
				$response['feedback'] = esc_html__( 'You don\'t have permission to rename folder', 'buddyboss' );
				wp_send_json_error( $response );
			}
		}

		$folder = bp_document_rename_folder( $document_id, $title );

		$response = array(
			'document_id' => $document_id,
			'title'       => $title,
		);

		if ( $folder > 0 ) {
			wp_send_json_success(
				array(
					'message'  => 'success',
					'response' => $response,
				)
			);
		} else {
			wp_send_json_error( $response );
		}
	}

}

/**
 * Rename folder.
 *
 * @since BuddyBoss 1.4.0
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

	// Use default nonce.
	$nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
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

	// save folder.
	$title    = $_POST['title'];
	$id       = ! empty( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$group_id = ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : 0;

	if ( (int) $id > 0 ) {
		$has_access = bp_folder_user_can_edit( $id );
		if ( ! $has_access ) {
			$response['feedback'] = sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You don\'t have permission to rename this folder', 'buddyboss' ) );
			wp_send_json_error( $response );
		}
	}

	if ( ! empty( $_POST['privacy'] ) ) {
		$privacy = $_POST['privacy'];
	} else {
		$folder_id = bp_document_get_root_parent_id( $_POST['id'] );
		$folder    = new BP_Document_Folder( $folder_id );
		$privacy   = $folder->privacy;
	}

	if ( $group_id > 0 ) {
		$privacy = 'grouponly';
	}

	$folder_id = bp_document_rename_folder( $id, $title, $privacy );

	if ( ! $folder_id ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem when trying to rename the folder.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	wp_send_json_success(
		array(
			'message' => 'success',
		)
	);
}

/**
 * Ajax delete the document.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_nouveau_ajax_document_delete() {

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

	$id            = ! empty( $_POST['id'] ) ? (int) filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT ) : 0;
	$attachment_id = ! empty( $_POST['attachment_id'] ) ? (int) filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT ) : 0;
	$type          = ! empty( $_POST['type'] ) ? filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING ) : '';
	$scope         = ! empty( $_POST['scope'] ) ? filter_input( INPUT_POST, 'scope', FILTER_SANITIZE_STRING ) : '';

	if ( '' === $type ) {
		wp_send_json_error( $response );
	}

	if ( 'folder' === $type ) {
		if ( bp_folder_user_can_delete( $id ) ) {
			bp_folder_delete( array( 'id' => $id ) );
		}
	} else {
		if ( bp_document_user_can_delete( $id ) ) {
			$args = array(
				'id'            => $id,
				'attachment_id' => $attachment_id,
			);
			bp_document_delete( $args );
		}
	}

	$content = '';
	ob_start();

	$string = '';
	if ( '' !== $scope && 'personal' === $scope ) {
		$string = '&scope=' . $scope;
	}

	if ( bp_has_document( bp_ajax_querystring( 'document' ) . $string ) ) :

		if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
			?>

			<div class="document-data-table-head">
				<span class="data-head-sort-label"><?php esc_html_e( 'Sort By:', 'buddyboss' ); ?></span>
				<div class="data-head data-head-name">
				<span>
					<?php esc_html_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>

				</div>
				<div class="data-head data-head-modified">
				<span>
					<?php esc_html_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>

				</div>
				<div class="data-head data-head-visibility">
				<span>
					<?php esc_html_e( 'Visibility', 'buddyboss' ); ?>
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

		if ( bp_document_has_more_items() ) :
			?>
			<div class="pager">
				<div class="dt-more-container load-more">
					<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
				</div>
			</div>
			<?php
		endif;

		if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
			?>
			</div> <!-- #media-folder-document-data-table -->
			<?php
		endif;

	else :

		bp_nouveau_user_feedback( 'media-loop-document-none' );

	endif;

	$content .= ob_get_clean();

	wp_send_json_success(
		array(
			'message' => 'success',
			'html'    => $content,
		)
	);

}

/**
 * Move folder to another folder.
 *
 * @since BuddyBoss 1.4.0
 */
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

	$destination_folder_id = ! empty( $_POST['folderMoveToId'] ) ? (int) filter_input( INPUT_POST, 'folderMoveToId', FILTER_VALIDATE_INT ) : 0;
	$folder_id             = ! empty( $_POST['currentFolderId'] ) ? (int) filter_input( INPUT_POST, 'currentFolderId', FILTER_VALIDATE_INT ) : 0;
	$group_id              = ! empty( $_POST['group'] ) ? (int) filter_input( INPUT_POST, 'group', FILTER_VALIDATE_INT ) : 0;

	if ( (int) $folder_id > 0 ) {
		$has_access = bp_folder_user_can_edit( $folder_id );
		if ( ! $has_access ) {
			$response['feedback'] = sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You don\'t have permission to move this folder.', 'buddyboss' ) );
			wp_send_json_error( $response );
		}
	}

	if ( (int) $destination_folder_id > 0 ) {
		$has_access_destination = bp_folder_user_can_edit( $destination_folder_id );
		if ( ! $has_access_destination ) {
			$response['feedback'] = sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You don\'t have permission to move this folder.', 'buddyboss' ) );
			wp_send_json_error( $response );
		}
	}

	if ( '' === $destination_folder_id ) {
		wp_send_json_error( $response );
	}

	if ( $destination_folder_id === $folder_id ) {
		$response = array(
			'feedback' => sprintf(
				'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
				esc_html__( 'Couldn’t move item. ', 'buddyboss' )
			),
		);
		wp_send_json_error( $response );
	}

	$fetch_children = bp_document_get_folder_children( $folder_id );
	if ( ! empty( $fetch_children ) ) {
		if ( in_array( $destination_folder_id, $fetch_children, true ) ) {
			$response = array(
				'feedback' => sprintf(
					'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
					esc_html__( 'Couldn’t move item because it\'s parent folder. ', 'buddyboss' )
				),
			);
			wp_send_json_error( $response );
		}
	}

	bp_document_move_folder_to_folder( $folder_id, $destination_folder_id, $group_id );

	$content = '';
	ob_start();

	if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :

		if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
			?>

			<div class="document-data-table-head">
				<span class="data-head-sort-label">:<?php esc_html_e( 'Sort By:', 'buddyboss' ); ?></span>
				<div class="data-head data-head-name">
				<span>
					<?php esc_html_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>

				</div>
				<div class="data-head data-head-modified">
				<span>
					<?php esc_html_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-triangle-fill"></i>
				</span>

				</div>
				<div class="data-head data-head-visibility">
				<span>
					<?php esc_html_e( 'Visibility', 'buddyboss' ); ?>
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

		if ( bp_document_has_more_items() ) :
			?>
			<div class="pager">
				<div class="dt-more-container load-more">
					<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
				</div>
			</div>
			<?php
		endif;

		if ( empty( $_POST['page'] ) || 1 === (int) filter_input( INPUT_POST, 'page', FILTER_SANITIZE_STRING ) ) :
			?>
			</div> <!-- #media-folder-document-data-table -->
			<?php
		endif;

	else :

		bp_nouveau_user_feedback( 'media-loop-document-none' );

	endif;

	$content .= ob_get_clean();

	wp_send_json_success(
		array(
			'message' => 'success',
			'html'    => $content,
		)
	);

}

function bp_nouveau_ajax_document_get_folder_view() {

	$type = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );
	$id   = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_STRING );

	if ( 'profile' === $type ) {
		$ul = bp_document_user_document_folder_tree_view_li_html( $id );
	} else {
		$ul = bp_document_user_document_folder_tree_view_li_html( 0, $id );
	}

	$first_text = '';
	if ( 'profile' === $type ) {
		$first_text = esc_html__( ' Documents', 'buddyboss' );
	} else {
		if ( bp_is_active( 'groups') ) {
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

function bp_nouveau_ajax_document_save_privacy() {
	global $wpdb, $bp;

	if ( ! is_user_logged_in() ) {
		$response['feedback'] = esc_html__( 'Please login to edit a privacy.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	$id      = filter_input( INPUT_POST, 'itemId', FILTER_VALIDATE_INT );
	$type    = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );
	$privacy = filter_input( INPUT_POST, 'value', FILTER_SANITIZE_STRING );

	if ( 'folder' === $type ) {
		if ( (int) $id > 0 ) {
			$has_access = bp_folder_user_can_edit( $id );
			if ( ! $has_access ) {
				$response['feedback'] = esc_html__( 'You don\'t have permission to update this folder privacy.', 'buddyboss' );
				wp_send_json_error( $response );
			}
		}
	} else {
		if ( (int) $id > 0 ) {
			$has_access = bp_document_user_can_edit( $id );
			if ( ! $has_access ) {
				$response['feedback'] = esc_html__( 'You don\'t have permission to update this document privacy.', 'buddyboss' );
				wp_send_json_error( $response );
			}
		}
	}

	if ( ! array_key_exists( $privacy, bp_document_get_visibility_levels() ) ) {
		$response['feedback'] = esc_html__( 'Invalid privacy status.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	// Update document privacy with nested level.
	bp_document_update_privacy( $id, $privacy, $type );

	wp_send_json_success(
		array(
			'message' => 'success',
			'html'    => $type,
		)
	);

}

function bp_nouveau_ajax_document_activity_delete() {

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

	$id            = ! empty( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$attachment_id = ! empty( $_POST['attachment_id'] ) ? (int) $_POST['attachment_id'] : 0;
	$type          = ! empty( $_POST['type'] ) ? $_POST['type'] : '';
	$activity_id   = ! empty( $_POST['activity_id'] ) ? $_POST['activity_id'] : 0;

	if ( '' === $type ) {
		wp_send_json_error( $response );
	}

	if ( bp_document_user_can_delete( $id ) ) {
		$args = array(
			'id'            => $id,
			'attachment_id' => $attachment_id,
		);
		bp_document_delete( $args );
	}

	$delete_box = false;

	// Get activity object.
	$activity = new BP_Activity_Activity( $activity_id );
	if ( empty( $activity->id ) ) {
		$delete_box = true;
	}

	wp_send_json_success(
		array(
			'message'         => 'success',
			'delete_activity' => $delete_box,
		)
	);
}
