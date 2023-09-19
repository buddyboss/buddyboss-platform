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
				'document_get_document_description' => array(
					'function' => 'bp_nouveau_ajax_document_get_document_description',
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response, 500 );
	}

	// Upload file.
	$result = bp_document_upload();

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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$folder_id = filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT );

	if ( empty( $folder_id ) ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Please provide ID of folder to delete.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

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

	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

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
	$nonce = bb_filter_input_string( INPUT_POST, 'nonce' );
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'bp_nouveau_media' ) ) {
		wp_send_json_error( $response );
	}

	$post_id  = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

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
	remove_action( 'bp_before_activity_activity_content', 'bp_nouveau_document_activity_description' );
	add_action( 'bp_activity_entry_content', 'bp_document_activity_entry' );

	wp_send_json_success(
		array(
			'activity' => $activity,
		)
	);
}

/**
 * Get description for the document.
 *
 * @since BuddyBoss 1.4.2
 */
function bp_nouveau_ajax_document_get_document_description() {

	$document_description = '';

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

	$document_id   = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );

	if ( empty( $document_id ) || empty( $attachment_id ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $attachment_id ) ) {
		wp_send_json_error( $response );
	}

	$document     = new BP_Document( $document_id );
	if ( bp_is_active( 'activity' ) && ! empty( $document->activity_id ) ) {

		remove_action( 'bp_activity_entry_content', 'bp_document_activity_entry' );
		add_action( 'bp_before_activity_activity_content', 'bp_nouveau_document_activity_description' );
		add_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );

		$remove_comment_btn = false;

		$activity = new BP_Activity_Activity( $document->activity_id );
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
			'include'     => $document->activity_id,
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
		$document_description = ob_get_contents();
		ob_end_clean();

		if ( true === $remove_comment_btn ) {
			remove_filter( 'bp_nouveau_get_activity_comment_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			remove_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			remove_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
			remove_filter( 'bp_nouveau_get_activity_comment_buttons_activity_state', 'bb_nouveau_get_activity_entry_buttons_callback', 99, 2 );
		}

		remove_filter( 'bp_get_activity_content_body', 'bp_nouveau_clear_activity_content_body', 99, 2 );
		remove_action( 'bp_before_activity_activity_content', 'bp_nouveau_document_activity_description' );
		add_action( 'bp_activity_entry_content', 'bp_document_activity_entry' );
	}

	if ( empty( trim( $document_description ) ) ) {
		$content          = get_post_field( 'post_content', $attachment_id );
		$document_privacy = bb_media_user_can_access( $document_id, 'document' );
		$can_download_btn = true === (bool) $document_privacy['can_download'];
		$can_edit_btn     = true === (bool) $document_privacy['can_edit'];
		$can_view         = true === (bool) $document_privacy['can_view'];
		$user_domain      = bp_core_get_user_domain( $document->user_id );
		$display_name     = bp_core_get_user_displayname( $document->user_id );
		$time_since       = bp_core_time_since( $document->date_created );
		$avatar           = bp_core_fetch_avatar(
			array(
				'item_id' => $document->user_id,
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
						<p><a href="<?php echo esc_url( $user_domain ); ?>"><?php echo esc_html( $display_name ); ?></a> <?php esc_html_e( 'uploaded a document', 'buddyboss' ); ?><a href="<?php echo esc_url( $user_domain ); ?>" class="view activity-time-since"></p>
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
								<textarea id="add-activity-description" title="<?php esc_attr_e( 'Add a description', 'buddyboss' ); ?>" class="textInput" name="caption_text" placeholder="<?php esc_attr_e( 'Add a description', 'buddyboss' ); ?>" role="textbox"><?php echo sanitize_textarea_field( $content ); ?></textarea>
							</div>
							<div class="in-profile description-new-submit">
								<input type="hidden" id="bp-attachment-id" value="<?php echo esc_attr( $attachment_id ); ?>">
								<input type="submit" id="bp-activity-description-new-submit" class="button small" name="description-new-submit" value="<?php esc_attr_e( 'Done Editing', 'buddyboss' ); ?>">
								<input type="reset" id="bp-activity-description-new-reset" class="text-button small" value="<?php esc_attr_e( 'Cancel', 'buddyboss' ); ?>">
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
				if ( ! empty( $document_id ) && $can_download_btn ) {
					$download_url = bp_document_download_link( $attachment_id, $document_id );
					if ( $download_url ) {
						?>
						<a class="download-document" href="<?php echo esc_url( $download_url ); ?>">
							<?php esc_html_e( 'Download', 'buddyboss' ); ?>
						</a>
						<?php
					}
				}
				?>
			</li>
			<?php
			$document_description = ob_get_contents();
			ob_end_clean();
		}
	}

	wp_send_json_success(
		array(
			'description' => $document_description,
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
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
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

/**
 * Save document
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$documents = filter_input( INPUT_POST, 'documents', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	if ( empty( $documents ) ) {
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

	$folder_id = filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT );

	if ( isset( $documents ) && ! empty( $documents ) && $folder_id > 0 ) {
		if ( ! empty( $documents ) && is_array( $documents ) ) {
			// set folder id for document.
			foreach ( $documents as $key => $document ) {
				if ( 0 === (int) $document['folder_id'] ) {
					$documents[ $key ]['folder_id'] = $folder_id;
				}
			}
		}
	}

	$privacy = bb_filter_input_string( INPUT_POST, 'privacy' );
	$content = bb_filter_input_string( INPUT_POST, 'content' );

	// handle document uploaded.
	$document_ids = bp_document_add_handler( $documents, $privacy, $content );
	$document     = '';
	if ( ! empty( $document_ids ) ) {
		ob_start();
		if (
			bp_has_document(
				array(
					'include'  => implode( ',', $document_ids ),
					'per_page' => 0,
				)
			)
		) {
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		$response['feedback'] = esc_html__( 'Please login to create a folder.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	$title = bb_filter_input_string( INPUT_POST, 'title' );

	if ( empty( $title ) ) {
		$response['feedback'] = esc_html__( 'Please enter title of folder.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	if ( strpbrk( $title, '\\/?%*:|"<>' ) !== false ) {
		$response['feedback'] = esc_html__( 'Invalid folder name', 'buddyboss' );
		wp_send_json_error( $response );
	}

	// save document.
	$id        = filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT );
	$group_id  = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$title     = wp_strip_all_tags( $title );
	$privacy   = bb_filter_input_string( INPUT_POST, 'privacy' );
	$privacy   = ! empty( $privacy ) ? $privacy : 'public';
	$parent    = filter_input( INPUT_POST, 'parent', FILTER_VALIDATE_INT );
	$folder_id = filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT );

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

	// Flush the cache.
	wp_cache_flush();

	$folder = new BP_Document_Folder( $folder_id );

	if ( $group_id > 0 ) {
		$ul = bp_document_user_document_folder_tree_view_li_html( $folder->user_id, $group_id );
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$title = bb_filter_input_string( INPUT_POST, 'title' );

	if ( empty( $title ) ) {
		$response['feedback'] = esc_html__( 'Please enter title of folder.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		$response['feedback'] = esc_html__( 'Please login to create a folder.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	if ( strpbrk( $title, '\\/?%*:|"<>' ) !== false ) {
		$response['feedback'] = esc_html__( 'Invalid folder name', 'buddyboss' );
		wp_send_json_error( $response );
	}

	// save folder.
	$group_id  = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$title     = wp_strip_all_tags( $title );
	$folder_id = filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT );
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

	$folder = new BP_Document_Folder( $folder_id );

	if ( $group_id > 0 ) {
		$ul = bp_document_user_document_folder_tree_view_li_html( $folder->user_id, $group_id );
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Move document.
	$folder_id   = filter_input( INPUT_POST, 'folder_id', FILTER_VALIDATE_INT );
	$document_id = filter_input( INPUT_POST, 'document_id', FILTER_VALIDATE_INT );
	$group_id    = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

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

	// Flush the cache.
	wp_cache_flush();

	$page = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );

	if ( $document > 0 ) {

		$content = '';
		ob_start();

		if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) {

			if ( empty( $page ) || 1 === $page ) {
				?>

				<div class="document-data-table-head">
					<span class="data-head-sort-label"><?php esc_attr_e( 'Sort By:', 'buddyboss' ); ?></span>
					<div class="data-head data-head-name">
				<span>
					<?php esc_attr_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>

					</div>
					<div class="data-head data-head-modified">
				<span>
					<?php esc_attr_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>

					</div>
					<div class="data-head data-head-visibility">
				<span>
					<?php esc_attr_e( 'Visibility', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>
					</div>
				</div><!-- .document-data-table-head -->

				<div id="media-folder-document-data-table">
				<?php
				bp_get_template_part( 'document/activity-document-move' );
				bp_get_template_part( 'document/activity-document-folder-move' );
			}

			while ( bp_document() ) {
				bp_the_document();

				bp_get_template_part( 'document/document-entry' );

			}

			if ( bp_document_has_more_items() ) {
				?>
				<div class="pager">
					<div class="dt-more-container load-more">
						<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_attr_e( 'Load More', 'buddyboss' ); ?></a>
					</div>
				</div>
				<?php
			}

			if ( empty( $page ) || 1 === $page ) {
				?>
				</div> <!-- #media-folder-document-data-table -->
				<?php
			}
		} else {
			bp_nouveau_user_feedback( 'media-loop-document-none' );
		}

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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$document_id            = filter_input( INPUT_POST, 'document_id', FILTER_VALIDATE_INT );
	$attachment_document_id = filter_input( INPUT_POST, 'attachment_document_id', FILTER_VALIDATE_INT );
	$title                  = bb_filter_input_string( INPUT_POST, 'name' );
	$type                   = bb_filter_input_string( INPUT_POST, 'document_type' );

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

			// Generate the document HTML to update the preview links.
			ob_start();
			if (
				bp_has_document(
					array(
						'include'  => $document['document_id'],
						'per_page' => 0,
					)
				)
			) {
				while ( bp_document() ) {
					bp_the_document();
					bp_get_template_part( 'document/document-entry' );
				}
			}
			$html_document = ob_get_contents();
			ob_end_clean();

			wp_send_json_success(
				array(
					'message'  => 'success',
					'response' => $document,
					'document' => $html_document,
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

		if ( strpbrk( $title, '\\/?%*:|"<>' ) !== false ) {
			$response['feedback'] = esc_html__( 'Invalid folder name', 'buddyboss' );
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
			esc_html__( 'Please enter title of folder.', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}

	if ( strpbrk( $title, '\\/?%*:|"<>' ) !== false ) {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'Invalid folder name', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	// save folder.
	$id       = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$group_id = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );
	$privacy  = bb_filter_input_string( INPUT_POST, 'privacy' );

	if ( (int) $id > 0 ) {
		$has_access = bp_folder_user_can_edit( $id );
		if ( ! $has_access ) {
			$response['feedback'] = sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You don\'t have permission to rename this folder', 'buddyboss' ) );
			wp_send_json_error( $response );
		}
	}

	if ( empty( $privacy ) ) {
		$folder_id = bp_document_get_root_parent_id( $id );
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$id            = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );
	$type          = bb_filter_input_string( INPUT_POST, 'type' );
	$scope         = bb_filter_input_string( INPUT_POST, 'scope' );

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

	// Flush the cache.
	wp_cache_flush();

	$content = '';
	ob_start();

	$string = '';
	if ( '' !== $scope && 'personal' === $scope ) {
		$string = '&scope=' . $scope;
	}

	$page = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );

	if ( bp_has_document( bp_ajax_querystring( 'document' ) . $string ) ) :

		if ( empty( $page ) || 1 === $page ) :
			?>

			<div class="document-data-table-head">
				<span class="data-head-sort-label"><?php esc_attr_e( 'Sort By:', 'buddyboss' ); ?></span>
				<div class="data-head data-head-name">
				<span>
					<?php esc_attr_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>

				</div>
				<div class="data-head data-head-modified">
				<span>
					<?php esc_attr_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>

				</div>
				<div class="data-head data-head-visibility">
				<span>
					<?php esc_attr_e( 'Visibility', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
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
					<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_attr_e( 'Load More', 'buddyboss' ); ?></a>
				</div>
			</div>
			<?php
		endif;

		if ( empty( $page ) || 1 === $page ) :
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$destination_folder_id = filter_input( INPUT_POST, 'folder_move_to_id', FILTER_VALIDATE_INT );
	$folder_id             = filter_input( INPUT_POST, 'current_folder_id', FILTER_VALIDATE_INT );
	$group_id              = filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT );

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

	$page = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );

	$content = '';
	ob_start();

	if ( bp_has_document( bp_ajax_querystring( 'document' ) ) ) :

		if ( empty( $page ) || 1 === $page ) :
			?>

			<div class="document-data-table-head">
				<span class="data-head-sort-label">:<?php esc_attr_e( 'Sort By:', 'buddyboss' ); ?></span>
				<div class="data-head data-head-name">
				<span>
					<?php esc_attr_e( 'Name', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>

				</div>
				<div class="data-head data-head-modified">
				<span>
					<?php esc_attr_e( 'Modified', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
				</span>

				</div>
				<div class="data-head data-head-visibility">
				<span>
					<?php esc_attr_e( 'Visibility', 'buddyboss' ); ?>
					<i class="bb-icon-f bb-icon-caret-down"></i>
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
					<a class="button outline full" href="<?php bp_document_load_more_link(); ?>"><?php esc_attr_e( 'Load More', 'buddyboss' ); ?></a>
				</div>
			</div>
			<?php
		endif;

		if ( empty( $page ) || 1 === $page ) :
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

	$type = bb_filter_input_string( INPUT_GET, 'type' );
	$id   = filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT );

	if ( 'profile' === $type ) {
		$ul = bp_document_user_document_folder_tree_view_li_html( $id, 0 );
	} else {
		$ul = bp_document_user_document_folder_tree_view_li_html( bp_loggedin_user_id(), $id );
	}

	$first_text = '';
	if ( 'profile' === $type ) {
		$first_text = esc_html__( ' Documents', 'buddyboss' );
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

function bp_nouveau_ajax_document_save_privacy() {
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

	if ( ! is_user_logged_in() ) {
		$response['feedback'] = esc_html__( 'Please login to edit a privacy.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$id      = filter_input( INPUT_POST, 'item_id', FILTER_VALIDATE_INT );
	$type    = bb_filter_input_string( INPUT_POST, 'type' );
	$privacy = bb_filter_input_string( INPUT_POST, 'value' );

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

	$document = ( 'document' === $type ? bb_get_document_attachments( $id ) : '' );

	wp_send_json_success(
		array(
			'message'  => 'success',
			'html'     => $type,
			'document' => $document,
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

	// Use default nonce.
	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$check = 'bp_nouveau_media';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$id            = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
	$attachment_id = filter_input( INPUT_POST, 'attachment_id', FILTER_VALIDATE_INT );
	$type          = bb_filter_input_string( INPUT_POST, 'type' );
	$activity_id   = filter_input( INPUT_POST, 'activity_id', FILTER_VALIDATE_INT );

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
	$activity         = new BP_Activity_Activity( $activity_id );
	$activity_content = '';
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

	wp_send_json_success(
		array(
			'message'          => 'success',
			'delete_activity'  => $delete_box,
			'activity_content' => $activity_content,
		)
	);
}
