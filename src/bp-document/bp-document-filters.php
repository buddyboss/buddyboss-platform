<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'bp_document_folder_after_save', 'bp_document_update_document_privacy' );
add_action( 'delete_attachment', 'bp_document_delete_attachment_document', 0 );

// Activity.
add_action( 'bp_after_directory_activity_list', 'bp_document_add_theatre_template' );
add_action( 'bp_after_member_activity_content', 'bp_document_add_theatre_template' );
add_action( 'bp_after_group_activity_content', 'bp_document_add_theatre_template' );
add_action( 'bp_after_single_activity_content', 'bp_document_add_theatre_template' );
add_action( 'bp_activity_entry_content', 'bp_document_activity_entry' );
add_action( 'bp_activity_after_comment_content', 'bp_document_activity_comment_entry' );
add_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
add_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
add_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );
add_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );
add_action( 'bp_activity_after_save', 'bp_document_activity_update_document_privacy', 2 );
add_filter( 'bp_activity_get_edit_data', 'bp_document_get_edit_activity_data' );

// Search.
add_action( 'bp_search_after_result', 'bp_document_add_theatre_template', 99999 );

// Forums.
add_action( 'bbp_template_after_single_topic', 'bp_document_add_theatre_template' );
add_action( 'bbp_new_reply', 'bp_document_forums_new_post_document_save', 999 );
add_action( 'bbp_new_topic', 'bp_document_forums_new_post_document_save', 999 );
add_action( 'edit_post', 'bp_document_forums_new_post_document_save', 999 );

add_filter( 'bbp_get_reply_content', 'bp_document_forums_embed_attachments', 999999, 2 );
add_filter( 'bbp_get_topic_content', 'bp_document_forums_embed_attachments', 999999, 2 );

// Messages.
add_action( 'messages_message_sent', 'bp_document_attach_document_to_message' );
add_action( 'bp_messages_thread_after_delete', 'bp_document_messages_delete_attached_document', 10, 2 );
add_action( 'bp_messages_thread_messages_after_update', 'bp_document_user_messages_delete_attached_document', 10, 4 );
add_filter( 'bp_messages_message_validated_content', 'bp_document_message_validated_content', 20, 3 );

// Download Document.
add_action( 'init', 'bp_document_download_url_file' );

// Sync Attachment data.
// add_action( 'edit_attachment', 'bp_document_sync_document_data', 99, 1 );

add_filter( 'bp_get_document_name', 'convert_chars' );
add_filter( 'bp_get_document_name', 'wptexturize' );
add_filter( 'bp_get_document_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_document_name', 'stripslashes' );

add_filter( 'bp_get_folder_title', 'wptexturize' );
add_filter( 'bp_get_folder_title', 'wp_filter_kses', 1 );
add_filter( 'bp_get_folder_title', 'stripslashes' );
add_filter( 'bp_get_folder_title', 'convert_chars' );

add_filter( 'bp_repair_list', 'bp_document_add_admin_repair_items' );

// Change label for global search.
add_filter( 'bp_search_label_search_type', 'bp_document_search_label_search' );

add_action( 'bp_activity_after_email_content', 'bp_document_activity_after_email_content' );

add_filter( 'bp_get_activity_entry_css_class', 'bp_document_activity_entry_css_class' );

// Delete symlinks for documents when before saved.
add_action( 'bp_document_before_save', 'bp_document_delete_symlinks' );

// Create symlinks for documents when saved.
add_action( 'bp_document_after_save', 'bp_document_create_symlinks' );

// Clear document symlinks on delete.
add_action( 'bp_document_before_delete', 'bp_document_clear_document_symlinks_on_delete', 10 );

add_filter( 'bb_ajax_activity_update_privacy', 'bb_document_update_video_symlink', 99, 2 );

add_action( 'bp_add_rewrite_rules', 'bb_setup_document_preview' );
add_filter( 'query_vars', 'bb_setup_query_document_preview' );
add_action( 'template_include', 'bb_setup_template_for_document_preview', PHP_INT_MAX );

// Setup rewrite rule to access attachment document.
add_action( 'bp_add_rewrite_rules', 'bb_setup_attachment_document_preview' );
add_filter( 'query_vars', 'bb_setup_attachment_document_preview_query' );
add_action( 'template_include', 'bb_setup_attachment_document_preview_template', PHP_INT_MAX );

/**
 * Clear a user's symlinks document when attachment document delete.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $documents DB results of document items.
 */
function bp_document_clear_document_symlinks_on_delete( $documents ) {
	if ( ! empty( $documents[0] ) ) {
		foreach ( (array) $documents as $deleted_document ) {
			if ( isset( $deleted_document->id ) ) {
				bp_document_delete_symlinks( (int) $deleted_document->id );

				// Remove symlinks that is created randomly.
				$get_existing = get_post_meta( $deleted_document->attachment_id, 'bb_video_symlinks_arr', true );
				if ( $get_existing ) {
					foreach ( $get_existing as $symlink ) {
						if ( file_exists( $symlink ) ) {
							unlink( $symlink );
						}
					}
				}
			}
		}
	}
}

/**
 * Document search label.
 *
 * @param string $type Search label.
 *
 * @return mixed|string|void
 */
function bp_document_search_label_search( $type ) {

	if ( 'folders' === $type ) {
		$type = __( 'Document Folders', 'buddyboss' );
	} elseif ( 'documents' === $type ) {
		$type = __( 'Documents', 'buddyboss' );
	}

	return $type;
}

/**
 * Add document theatre template for activity pages
 */
function bp_document_add_theatre_template() {
	bp_get_template_part( 'document/theatre' );
}

/**
 * Get activity entry document to render on front end
 *
 * @BuddyBoss 1.2.5
 */
function bp_document_activity_entry() {

	if ( ( buddypress()->activity->id === bp_get_activity_object_name() && ! bp_is_profile_document_support_enabled() ) || ( bp_is_active( 'groups' ) && buddypress()->groups->id === bp_get_activity_object_name() && ! bp_is_group_document_support_enabled() ) ) {
		return false;
	}

	$document_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_document_ids', true );

	// Add document to single activity page.
	$document_activity = bp_activity_get_meta( bp_get_activity_id(), 'bp_document_activity', true );
	if ( bp_is_single_activity() && ! empty( $document_activity ) && '1' === $document_activity && empty( $document_ids ) ) {
		$document_ids = BP_Document::get_activity_document_id( bp_get_activity_id() );
	}

	/**
	 * If the content has been changed by these filters bb_moderation_has_blocked_message,
	 * bb_moderation_is_blocked_message, bb_moderation_is_suspended_message then
	 * it will hide document content which is created by blocked/blocked/suspended member.
	 */
	$hide_forum_activity = function_exists( 'bb_moderation_to_hide_forum_activity' ) ? bb_moderation_to_hide_forum_activity( bp_get_activity_id() ) : false;

	if ( true === $hide_forum_activity ) {
		return;
	}

	if ( ! empty( $document_ids ) && bp_has_document(
		array(
			'include'  => $document_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
		)
	) ) { ?>
		<div class="bb-activity-media-wrap bb-media-length-1 ">
			<?php

			bp_get_template_part( 'document/activity-document-move' );
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
	}
}

/**
 * Append the document content to activity read more content
 *
 * @param $content
 * @param $activity
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_append_document( $content, $activity ) {

	$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

	$args = array(
		'include'  => $document_ids,
		'order_by' => 'menu_order',
		'sort'     => 'ASC',
	);

	if ( bp_is_active( 'groups' ) && bp_is_group() && bp_is_group_document_support_enabled() ) {
		$args['privacy'] = array( 'grouponly' );
		if ( 'activity_comment' === $activity->type ) {
			$args['privacy'][] = 'comment';
		}
	}

	if ( ! empty( $document_ids ) && bp_has_document( $args ) ) {
		ob_start();
		?>
		<div class="bb-activity-media-wrap bb-media-length-1 ">
			<?php
			bp_get_template_part( 'document/activity-document-move' );
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Get activity comment entry document to render on front end.
 *
 * @param $comment_id
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_comment_entry( $comment_id ) {

	$document_ids = bp_activity_get_meta( $comment_id, 'bp_document_ids', true );

	if ( empty( $document_ids ) ) {
		return;
	}

	$comment  = new BP_Activity_Activity( $comment_id );
	$activity = new BP_Activity_Activity( $comment->item_id );

	$args = array(
		'include'  => $document_ids,
		'order_by' => 'menu_order',
		'sort'     => 'ASC',
		'user_id'  => false,
		'privacy'  => array(),
	);

	if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
		if ( bp_is_group_document_support_enabled() ) {
			$args['privacy'][] = 'comment';
			$args['privacy'][] = 'grouponly';
			if ( ! bp_is_group_albums_support_enabled() ) {
				$args['album_id'] = 'existing-document';
			}
		} else {
			$args['privacy']  = array( '0' );
			$args['album_id'] = 'existing-document';
		}
	} else {
		$args['privacy'] = bp_document_query_privacy( $activity->user_id, 0, $activity->component );
		if ( ! bp_is_group_document_support_enabled() ) {
			$args['user_id'] = 'null';
		}
		if ( ! bp_is_profile_document_support_enabled() ) {
			$args['album_id'] = 'existing-document';
		}
	}

	$args['privacy'][] = 'comment';
	if ( ! isset( $args['album_id'] ) ) {
		$args['album_id'] = 'existing-document';
	}

	if ( ! empty( $document_ids ) && bp_has_document( $args ) ) {
		?>
		<div class="bb-activity-media-wrap bb-media-length-1 ">
			<?php
			bp_get_template_part( 'document/activity-document-move' );
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
	}
}

/**
 * Remove the inline preview in popup activity comment document.
 *
 * @param $display
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_music_preview_remove_in_comment( $display ) {
	return false;
}

/**
 * Remove the inline preview in popup activity comment document.
 *
 * @param $display
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_text_preview_remove_in_comment( $display ) {
	return false;
}

/**
 * Remove the inline preview in popup activity comment document.
 *
 * @param $display
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_image_preview_remove_in_comment( $display ) {
	return false;
}

/**
 * Change the text in activity comment document view.
 *
 * @param $text
 *
 * @return string|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_change_popup_view_text_in_comment( $text ) {
	return __( 'View', 'buddyboss' );
}

/**
 * Change the text in activity comment document view.
 *
 * @param $text
 *
 * @return string|void
 * @since BuddyBoss 1.4.0
 */
function bp_document_change_popup_download_text_in_comment( $text ) {
	return __( 'Download', 'buddyboss' );
}

/**
 * Update document for activity.
 *
 * @param $content
 * @param $user_id
 * @param $activity_id
 *
 * @return bool
 * @since BuddyBoss 1.4.0
 */
function bp_document_update_activity_document_meta( $content, $user_id, $activity_id ) {
	global $bp_activity_post_update, $bp_activity_post_update_id, $bp_activity_edit;

	$documents           = filter_input( INPUT_POST, 'document', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$documents           = ! empty( $documents ) ? $documents : array();
	$actions             = bb_filter_input_string( INPUT_POST, 'action' );
	$moderated_documents = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );

	if ( ! empty( $documents ) ) {
		$document_order = array_column( $documents, 'menu_order' );
		array_multisort( $document_order, SORT_ASC, $documents );
	}

	if ( bp_is_active( 'moderation' ) && ! empty( $moderated_documents ) ) {
		$moderated_documents = explode( ',', $moderated_documents );
		foreach ( $moderated_documents as $document_id ) {
			if ( bp_moderation_is_content_hidden( $document_id, BP_Moderation_Document::$moderation_type ) ) {
				$bp_document                = new BP_Document( $document_id );
				$documents[]['document_id'] = $document_id;
				$documents[]['folder_id']   = $bp_document->folder_id;
				$documents[]['group_id']    = $bp_document->group_id;
				$documents[]['menu_order']  = $bp_document->menu_order;
			}
		}
	}

	if ( ! isset( $documents ) || empty( $documents ) ) {

		// delete document ids and meta for activity if empty document in request.
		if ( ! empty( $activity_id ) && $bp_activity_edit && isset( $_POST['edit'] ) ) {
			$old_document_ids = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );

			if ( ! empty( $old_document_ids ) ) {

				// Delete document if not exists in activity anymore.
				$old_document_ids = explode( ',', $old_document_ids );
				if ( ! empty( $old_document_ids ) ) {
					foreach ( $old_document_ids as $document_id ) {
						bp_document_delete( array( 'id' => $document_id ), 'activity' );
					}
				}
				bp_activity_delete_meta( $activity_id, 'bp_document_ids' );
			}
		}
		return false;
	}

	$bp_activity_post_update    = true;
	$bp_activity_post_update_id = $activity_id;

	// Update activity comment attached document privacy with parent one.
	if ( bp_is_active( 'activity' ) && ! empty( $activity_id ) && isset( $actions ) && 'new_activity_comment' === $actions ) {
		$parent_activity = new BP_Activity_Activity( $activity_id );
		if ( 'groups' === $parent_activity->component ) {
			$_POST['privacy'] = 'grouponly';
		} elseif ( ! empty( $parent_activity->privacy ) ) {
			$_POST['privacy'] = $parent_activity->privacy;
		}
	}

	remove_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
	remove_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
	remove_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
	remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );

	$document_ids = bp_document_add_handler( $documents, $_POST['privacy'] );

	add_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
	add_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
	add_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
	add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );

	// save document meta for activity.
	if ( ! empty( $activity_id ) ) {
		// Delete document if not exists in current document ids.
		if ( isset( $_POST['edit'] ) ) {
			$old_document_ids = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
			$old_document_ids = explode( ',', $old_document_ids );
			if ( ! empty( $old_document_ids ) ) {

				foreach ( $old_document_ids as $document_id ) {
					if ( bp_is_active( 'moderation' ) && bp_moderation_is_content_hidden( $document_id, BP_Moderation_Document::$moderation_type ) && ! in_array( $document_id, $document_ids ) ) {
						$document_ids[] = $document_id;
					}
					if ( ! in_array( $document_id, $document_ids ) ) {
						bp_document_delete( array( 'id' => $document_id ), 'activity' );
					}
				}

				// This is hack to update/delete parent activity if new media added in edit.
				bp_activity_update_meta( $activity_id, 'bp_document_ids', implode( ',', array_unique( array_merge( $document_ids, $old_document_ids ) ) ) );
			}
		}
		bp_activity_update_meta( $activity_id, 'bp_document_ids', implode( ',', $document_ids ) );
	}
}

/**
 * Update document for group activity.
 *
 * @param $content
 * @param $user_id
 * @param $group_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_groups_activity_update_document_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_document_update_activity_document_meta( $content, $user_id, $activity_id );
}

/**
 * Update document for activity comment.
 *
 * @param $comment_id
 * @param $r
 * @param $activity
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_comments_update_document_meta( $comment_id, $r, $activity ) {
	global $bp_new_activity_comment;
	$bp_new_activity_comment = $comment_id;
	bp_document_update_activity_document_meta( false, false, $comment_id );
}

/**
 * Delete document when related activity is deleted.
 *
 * @param $activities
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_delete_activity_document( $activities ) {
	if ( ! empty( $activities ) ) {
		remove_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );
		foreach ( $activities as $activity ) {

			// Do not delete attached document, if the activity belongs to a forum topic/reply.
			// Attached document could still be used inside that component.
			if (
				! empty( $activity->type ) &&
				in_array( $activity->type, array( 'bbp_reply_create', 'bbp_topic_create' ), true )
			) {
				continue;
			}

			$activity_id       = $activity->id;
			$document_activity = bp_activity_get_meta( $activity_id, 'bp_document_activity', true );
			if ( ! empty( $document_activity ) && '1' == $document_activity ) {
				bp_document_delete( array( 'activity_id' => $activity_id ) );
			}

			// get document ids attached to activity.
			$document_ids = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				foreach ( $document_ids as $document_id ) {
					bp_document_delete( array( 'id' => $document_id ) );
				}
			}
		}
		add_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );
	}
}

/**
 * Update document privacy according to folder's privacy.
 *
 * @param $folder
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_update_document_privacy( $folder ) {

	if ( ! empty( $folder->id ) ) {

		$privacy      = $folder->privacy;
		$document_ids = BP_Document::get_folder_document_ids( $folder->id );
		$activity_ids = array();

		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $document ) {
				$document_obj          = new BP_Document( $document );
				$document_obj->privacy = $privacy;
				$document_obj->save();

				$attachment_id    = $document_obj->attachment_id;
				$main_activity_id = get_post_meta( $attachment_id, 'bp_document_parent_activity_id', true );

				if ( ! empty( $main_activity_id ) ) {
					$activity_ids[] = $main_activity_id;
				}
			}
		}

		if ( bp_is_active( 'activity' ) && ! empty( $activity_ids ) ) {
			foreach ( $activity_ids as $activity_id ) {
				$activity = new BP_Activity_Activity( $activity_id );

				if ( ! empty( $activity ) ) {
					$activity->privacy = $privacy;
					$activity->save();
				}
			}
		}
	}
}

/**
 * Save document when new topic or reply is saved
 *
 * @param int $post_id post id of the topic or reply.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_forums_new_post_document_save( $post_id ) {

	if ( ! empty( $_POST['bbp_document'] ) ) {

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// save document.
		$documents = json_decode( stripslashes( $_POST['bbp_document'] ), true );

		if ( ! empty( $documents ) ) {
			$document_order = array_column( $documents, 'menu_order' );
			array_multisort( $document_order, SORT_ASC, $documents );
		}

		// fetch currently uploaded document ids.
		$existing_document                = array();
		$existing_document_ids            = get_post_meta( $post_id, 'bp_document_ids', true );
		$existing_document_attachment_ids = array();
		if ( ! empty( $existing_document_ids ) ) {
			$existing_document_ids = explode( ',', $existing_document_ids );

			foreach ( $existing_document_ids as $existing_document_id ) {
				$existing_document[ $existing_document_id ] = new BP_Document( $existing_document_id );

				if ( ! empty( $existing_document[ $existing_document_id ]->attachment_id ) ) {
					$existing_document_attachment_ids[] = $existing_document[ $existing_document_id ]->attachment_id;
				}
			}
		}

		$document_ids = array();
		foreach ( $documents as $document ) {

			$title                = ! empty( $document['name'] ) ? $document['name'] : '';
			$attachment_id        = ! empty( $document['id'] ) ? $document['id'] : 0;
			$attached_document_id = ! empty( $document['document_id'] ) ? $document['document_id'] : 0;
			$folder_id            = ! empty( $document['folder_id'] ) ? $document['folder_id'] : 0;
			$group_id             = ! empty( $document['group_id'] ) ? $document['group_id'] : 0;
			$forum_id             = ! empty( $document['forum_id'] ) ? $document['forum_id'] : 0;
			$topic_id             = ! empty( $document['topic_id'] ) ? $document['topic_id'] : 0;
			$reply_id             = ! empty( $document['reply_id'] ) ? $document['reply_id'] : 0;
			$menu_order           = ! empty( $document['menu_order'] ) ? $document['menu_order'] : 0;

			if ( ! empty( $existing_document_attachment_ids ) ) {
				$index = array_search( $attachment_id, $existing_document_attachment_ids );
				if ( ! empty( $attachment_id ) && $index !== false && ! empty( $existing_document[ $attached_document_id ] ) ) {

					$existing_document[ $attached_document_id ]->menu_order = $menu_order;
					$existing_document[ $attached_document_id ]->save();

					unset( $existing_document_ids[ $index ] );
					$document_ids[] = $attached_document_id;
					continue;
				}
			}

			if ( 0 === $reply_id && bbp_get_reply_post_type() === get_post_type( $post_id ) ) {
				$reply_id = $post_id;
				$topic_id = bbp_get_reply_topic_id( $reply_id );
				$forum_id = bbp_get_topic_forum_id( $topic_id );
			} elseif ( 0 === $topic_id && bbp_get_topic_post_type() === get_post_type( $post_id ) ) {
				$topic_id = $post_id;
				$forum_id = bbp_get_topic_forum_id( $topic_id );
			} elseif ( 0 === $forum_id && bbp_get_forum_post_type() === get_post_type( $post_id ) ) {
				$forum_id = $post_id;
			}

			$attachment_data = get_post( $document['id'] );
			$file            = get_attached_file( $document['id'] );
			$file_type       = wp_check_filetype( $file );
			$file_name       = basename( $file );

			$document_id = bp_document_add(
				array(
					'attachment_id' => $attachment_id,
					'title'         => $title,
					'folder_id'     => $folder_id,
					'group_id'      => $group_id,
					'privacy'       => 'forums',
					'error_type'    => 'wp_error',
					'menu_order'    => $menu_order,
				)
			);

			if ( ! is_wp_error( $document_id ) && ! empty( $document_id ) ) {
				$document_ids[] = $document_id;

				// save document meta.
				bp_document_update_meta( $document_id, 'forum_id', $forum_id );
				bp_document_update_meta( $document_id, 'topic_id', $topic_id );
				bp_document_update_meta( $document_id, 'reply_id', $reply_id );
				bp_document_update_meta( $document_id, 'file_name', $file_name );
				bp_document_update_meta( $document_id, 'extension', '.' . $file_type['ext'] );

				// save document is saved in attachment.
				update_post_meta( $attachment_id, 'bp_document_saved', true );
			}
		}

		$document_ids = implode( ',', $document_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $post_id, 'bp_document_ids', $document_ids );

		// save document meta for activity.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			if ( ! empty( $document_ids ) ) {
				bp_activity_update_meta( $main_activity_id, 'bp_document_ids', $document_ids );
			} else {
				bp_activity_delete_meta( $main_activity_id, 'bp_document_ids' );
			}
		}

		// delete documents which were not saved or removed from form.
		if ( ! empty( $existing_document_ids ) ) {
			foreach ( $existing_document_ids as $document_id ) {
				bp_document_delete( array( 'id' => $document_id ) );
			}
		}
	}
}

/**
 * Embed topic or reply attachments in a post.
 *
 * @param $content
 * @param $id
 *
 * @return string
 * @since BuddyBoss 1.4.0
 */
function bp_document_forums_embed_attachments( $content, $id ) {

	// Do not embed attachment in wp-admin area.
	if ( is_admin() ) {
		return $content;
	}

	$forum_id = 0;

	// Get current forum ID.
	if ( bbp_get_reply_post_type() === get_post_type( $id ) ) {
		$forum_id = bbp_get_reply_forum_id( $id );
	} elseif ( bbp_get_topic_post_type() === get_post_type( $id ) ) {
		$forum_id = bbp_get_topic_forum_id( $id );
	} elseif ( bbp_get_forum_post_type() === get_post_type( $id ) ) {
		$forum_id = $id;
	}

	$group_ids = bbp_get_forum_group_ids( $forum_id );
	$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

	if (
		(
			(
				empty( $group_id ) ||
				(
					! empty( $group_id ) &&
					! bp_is_active( 'groups' )
				)
			) &&
			! bp_is_forums_document_support_enabled()
		) ||
		(
			bp_is_active( 'groups' ) &&
			! empty( $group_id ) &&
			! bp_is_group_document_support_enabled()
		)
	) {
		return $content;
	}

	$document_ids = get_post_meta( $id, 'bp_document_ids', true );

	if ( ! empty( $document_ids ) && bp_has_document(
		array(
			'include'  => $document_ids,
			'order_by' => 'menu_order',
			'sort'     => 'ASC',
			'privacy'  => array( 'forums' ),
		)
	) ) {
		ob_start();
		?>
		<div class="bb-activity-media-wrap forums-media-wrap">
			<?php
			while ( bp_document() ) {
				bp_the_document();
				bp_get_template_part( 'document/activity-entry' );
			}
			?>
		</div>
		<?php
		$content .= ob_get_clean();
	}

	return $content;
}

/**
 * Attach document to the message object.
 *
 * @param $message
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_attach_document_to_message( &$message ) {

	if (
		bp_is_messages_document_support_enabled() &&
		! empty( $message->id ) &&
		(
			! empty( $_POST['document'] ) ||
			! empty( $_POST['bp_document_ids'] )
		)
	) {

		$documents = array();

		if ( ! empty( $_POST['document'] ) ) {
			$documents = $_POST['document'];
		} else if ( ! empty( $_POST['bp_document_ids'] ) ) {
			$documents = $_POST['bp_document_ids'];
		}

		$document_ids = array();

		if ( ! empty( $documents ) ) {
			foreach ( $documents as $attachment ) {

				$attachment_id = ( is_array( $attachment ) && ! empty( $attachment['id'] ) ) ? $attachment['id'] : $attachment;

				// Get media_id from the attachment ID.
				$document_id = get_post_meta( $attachment_id, 'bp_document_id', true );

				if ( ! empty( $document_id ) ) {

					$document_ids[] = $document_id;

					// Attach already created media.
					$document             = new BP_Document( $document_id );
					$document->privacy    = 'message';
					$document->message_id = $message->id;
					$document->save();

					update_post_meta( $document->attachment_id, 'bp_document_saved', true );
					update_post_meta( $document->attachment_id, 'bp_media_parent_message_id', $message->id );
					update_post_meta( $document->attachment_id, 'thread_id', $message->thread_id );
					bp_document_update_meta( $document_id, 'thread_id', $message->thread_id );
				}
			}

			if ( ! empty( $document_ids ) ) {
				bp_messages_update_meta( $message->id, 'bp_document_ids', implode( ',', $document_ids ) );
			}
		}
	}
}

/**
 * Delete document attached to messages.
 *
 * @param $thread_id
 * @param $message_ids
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_messages_delete_attached_document( $thread_id, $message_ids ) {

	if ( ! empty( $message_ids ) ) {
		foreach ( $message_ids as $message_id ) {

			// get document ids attached to message.
			$document_ids = bp_messages_get_meta( $message_id, 'bp_document_ids', true );

			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				foreach ( $document_ids as $document_id ) {
					bp_document_delete( array( 'id' => $document_id ) );
				}
			}
		}
	}
}

/**
 * Delete document attached to messages.
 *
 * @param $thread_id
 * @param $message_ids
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_user_messages_delete_attached_document( $thread_id, $message_ids, $user_id, $update_message_ids ) {

	if ( ! empty( $update_message_ids ) ) {
		foreach ( $update_message_ids as $message_id ) {

			// get document ids attached to message.
			$document_ids = bp_messages_get_meta( $message_id, 'bp_document_ids', true );

			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				foreach ( $document_ids as $document_id ) {
					bp_document_delete( array( 'id' => $document_id ) );
				}
			}
		}
	}
}

/**
 * Validate message if document is not empty.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param bool         $validated_content True if message is valid, false otherwise.
 * @param string       $content           Message content.
 * @param array|object $post              Request object.
 *
 * @return bool
 *
 * @since BuddyBoss 1.5.1
 */
function bp_document_message_validated_content( $validated_content, $content, $post ) {
	if ( ! bp_is_messages_document_support_enabled() || ! isset( $post['document'] ) ) {
		return (bool) $validated_content;
	}

	return (bool) ! empty( $post['document'] );
}

/**
 * Delete document entries attached to the attachment.
 *
 * @param int $attachment_id ID of the attachment being deleted.
 *
 * @return bool
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_delete_attachment_document( $attachment_id ) {
	global $wpdb;

	$bp = buddypress();

	$document = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->document->table_name} WHERE attachment_id = %d", $attachment_id ) ); // db call ok; no-cache ok;
	if ( ! $document ) {
		return false;
	}
	remove_action( 'delete_attachment', 'bp_document_delete_attachment_document', 0 );
	bp_document_delete( array( 'id' => $document->id ), 'attachment' );
	add_action( 'delete_attachment', 'bp_document_delete_attachment_document', 0 );
}

/**
 * Check if user have a access to download the file. If not redirect to homepage.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_download_url_file() {
	if ( isset( $_GET['attachment'] ) && isset( $_GET['download_document_file'] ) && isset( $_GET['document_file'] ) && isset( $_GET['document_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'folder' !== $_GET['document_type'] ) {
			$document_privacy = bb_media_user_can_access( $_GET['document_file'], 'document', $_GET['attachment'] ); // phpcs:ignore WordPress.Security.NonceVerification
			$can_download_btn = ( true === (bool) $document_privacy['can_download'] ) ? true : false;
		} else {
			$folder_privacy   = bb_media_user_can_access( $_GET['document_file'], 'folder' ); // phpcs:ignore WordPress.Security.NonceVerification
			$can_download_btn = ( true === (bool) $folder_privacy['can_download'] ) ? true : false;
		}
		if ( $can_download_btn ) {
			bp_document_download_file( $_GET['attachment'], $_GET['document_type'] ); // phpcs:ignore WordPress.Security.NonceVerification
		} else {
			wp_safe_redirect( site_url() );
			exit;
		}
	}
}

/** Sync the description of the document with the media attachment.
 *
 * @param $attachment_id
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_sync_document_data( $attachment_id ) {
	if ( ! is_admin() || wp_doing_ajax() ) {
		return;
	}
	global $wpdb, $bp;
	// Check if document is attached to a document.
	$document = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->document->table_name} WHERE attachment_id = %d", $attachment_id ) ); // db call ok; no-cache ok;
	if ( $document ) {
		$document_post = get_post( $attachment_id );
		$document      = bp_document_rename_file( $document->id, $attachment_id, $document_post->post_title, true );
	}
}

/**
 * Update document privacy when activity is updated.
 *
 * @param $activity Activity object.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_activity_update_document_privacy( $activity ) {
	$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

	if ( ! empty( $document_ids ) ) {
		$document_ids = explode( ',', $document_ids );

		foreach ( $document_ids as $document_id ) {
			$document = new BP_Document( $document_id );
			// Do not update the privacy if the document is added to forum.
			if (
				! in_array( $document->privacy, array( 'forums', 'message', 'media', 'document', 'grouponly', 'video' ), true ) &&
				'comment' !== $document->privacy &&
				! empty( $document->blog_id )
			) {
				$document->privacy = $activity->privacy;
				$document->save();
			}
		}
	}
}

/**
 * Protect downloads from ms-files.php in multisite.
 *
 * @param string $rewrite rewrite rules.
 * @return string
 * @since BuddyBoss 1.4.1
 */
function bp_document_protect_download_rewite_rules( $rewrite ) {
	if ( ! is_multisite() ) {
		return $rewrite;
	}

	$rule  = "\n# Document Rules - Protect Files from ms-files.php\n\n";
	$rule .= "<IfModule mod_rewrite.c>\n";
	$rule .= "RewriteEngine On\n";
	$rule .= "RewriteCond %{QUERY_STRING} file=document_uploads/ [NC]\n";
	$rule .= "RewriteRule /ms-files.php$ - [F]\n";
	$rule .= "</IfModule>\n\n";

	return $rule . $rewrite;
}
add_filter( 'mod_rewrite_rules', 'bp_document_protect_download_rewite_rules' );

function bp_document_check_download_folder_protection() {

	$upload_dir = wp_get_upload_dir();
	$files      = array(
		array(
			'base'    => $upload_dir['basedir'] . '/bb_documents',
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => $upload_dir['basedir'] . '/bb_documents',
			'file'    => '.htaccess',
			'content' => '# Apache 2.2
<IfModule !mod_authz_core.c>
	Order Deny,Allow
	Deny from all
</IfModule>

# Apache 2.4
<IfModule mod_authz_core.c>
	Require all denied
</IfModule>
# BEGIN BuddyBoss code execution protection
<IfModule mod_rewrite.c>
RewriteRule ^.*$ - [F,L,NC]
</IfModule>
<IfModule mod_php5.c>
php_flag engine 0
</IfModule>
<IfModule mod_php7.c>
php_flag engine 0
</IfModule>

AddHandler cgi-script .php .phtml .php3 .pl .py .jsp .asp .htm .shtml .sh .cgi
Options -ExecCGI
# END BuddyBoss code execution protection',
		),
	);

	foreach ( $files as $file ) {
		if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
			$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
			if ( $file_handle ) {
				fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
				fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		}
	}
}
add_action( 'bp_init', 'bp_document_check_download_folder_protection', 9999 );

/**
 * Prepare attachment for JavaScript.
 *
 * @param array $response JS version of a attachment post object.
 * @return array
 * @since BuddyBoss 1.4.1
 */
function bp_document_prepare_attachment_for_js( $response, $attachment, $meta ) {
	if ( isset( $response['url'] ) && strstr( $response['url'], 'bb_documents/' ) ) {
		$response['icon']  = includes_url() . 'images/media/default.png';
		$response['type']  = 'text';
		$response['sizes'] = array();
	}

	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'bp_document_prepare_attachment_for_js', 999, 3 );

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since BuddyBoss 1.4.1
 * @param int $limit Time limit.
 */
function bp_document_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
	}
}

/**
 * Check and set certain server config variables to ensure downloads work as intended.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_document_check_server_config() {
	bp_document_set_time_limit( 0 );
	if ( function_exists( 'apache_setenv' ) ) {
		@apache_setenv( 'no-gzip', 1 ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_apache_setenv
	}
	@ini_set( 'zlib.output_compression', 'Off' ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_ini_set
	@session_write_close(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.VIP.SessionFunctionsUsage.session_session_write_close
}

/**
 * Clean all output buffers.
 *
 * Can prevent errors, for example: transfer closed with 3 bytes remaining to read.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_document_clean_buffers() {
	if ( ob_get_level() ) {
		$levels = ob_get_level();
		for ( $i = 0; $i < $levels; $i++ ) {
			@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		}
	} else {
		@ob_end_clean(); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	}
}

/**
 * Set constants to prevent caching by some plugins.
 *
 * @param  mixed $return Value to return. Previously hooked into a filter.
 * @return mixed
 * @since BuddyBoss 1.4.1
 */
function bp_document_set_nocache_constants( $return = true ) {
	bp_document_maybe_define_constant( 'DONOTCACHEPAGE', true );
	bp_document_maybe_define_constant( 'DONOTCACHEOBJECT', true );
	bp_document_maybe_define_constant( 'DONOTCACHEDB', true );
	return $return;
}

/**
 * Define a constant if it is not already defined.
 *
 * @since BuddyBoss 1.4.1
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function bp_document_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since BuddyBoss 1.4.1
 */
function bp_document_nocache_headers() {
	bp_document_set_nocache_constants();
	nocache_headers();
}

/**
 * Get content type of a download.
 *
 * @param  string $file_path File path.
 * @return string
 * @since BuddyBoss 1.4.1
 */
function bp_document_get_download_content_type( $file_path ) {
	$file_extension = strtolower( substr( strrchr( $file_path, '.' ), 1 ) );
	$ctype          = 'application/force-download';

	foreach ( get_allowed_mime_types() as $mime => $type ) {
		$mimes = explode( '|', $mime );
		if ( in_array( $file_extension, $mimes, true ) ) {
			$ctype = $type;
			break;
		}
	}

	return $ctype;
}

/**
 * Set headers for the download.
 *
 * @param string $file_path      File path.
 * @param string $filename       File name.
 * @param array  $download_range Array containing info about range download request (see {@see get_download_range} for structure).
 * @since BuddyBoss 1.4.1
 */
function bp_document_download_headers( $file_path, $filename, $download_range = array() ) {
	bp_document_check_server_config();
	bp_document_clean_buffers();
	bp_document_nocache_headers();

	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'Content-Type: ' . bp_document_get_download_content_type( $file_path ) );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
	header( 'Content-Transfer-Encoding: binary' );

	$file_size = @filesize( $file_path ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	if ( ! $file_size ) {
		return;
	}

	if ( isset( $download_range['is_range_request'] ) && true === $download_range['is_range_request'] ) {
		if ( false === $download_range['is_range_valid'] ) {
			header( 'HTTP/1.1 416 Requested Range Not Satisfiable' );
			header( 'Content-Range: bytes 0-' . ( $file_size - 1 ) . '/' . $file_size );
			exit;
		}

		$start  = $download_range['start'];
		$end    = $download_range['start'] + $download_range['length'] - 1;
		$length = $download_range['length'];

		header( 'HTTP/1.1 206 Partial Content' );
		header( "Accept-Ranges: 0-$file_size" );
		header( "Content-Range: bytes $start-$end/$file_size" );
		header( "Content-Length: $length" );
	} else {
		header( 'Content-Length: ' . $file_size );
	}
}

/**
 * Force download - this is the default method.
 *
 * @param string $file_path File path.
 * @param string $filename  File name.
 * @since BuddyBoss 1.4.1
 */
function bp_document_download_file_force( $file_path, $filename ) {
	$parsed_file_path = bp_document_parse_file_path( $file_path );
	$download_range   = bp_document_get_download_range( @filesize( $parsed_file_path['file_path'] ) ); // @codingStandardsIgnoreLine.

	bp_document_download_headers( $parsed_file_path['file_path'], $filename, $download_range );

	$start  = isset( $download_range['start'] ) ? $download_range['start'] : 0;
	$length = isset( $download_range['length'] ) ? $download_range['length'] : 0;
	if ( ! bp_document_readfile_chunked( $parsed_file_path['file_path'], $start, $length ) ) {
		if ( $parsed_file_path['remote_file'] ) {
			bp_document_download_file_redirect( $file_path );
		} else {
			bp_document_download_error( __( 'File not found', 'buddyboss' ) );
		}
	}

	exit;
}

/**
 * Die with an error message if the download fails.
 *
 * @param string  $message Error message.
 * @param string  $title   Error title.
 * @param integer $status  Error status.
 * @since BuddyBoss 1.4.1
 */
function bp_document_download_error( $message, $title = '', $status = 404 ) {
	if ( ! strstr( $message, '<a ' ) ) {
		$message .= ' <a href="' . esc_url( site_url() ) . '" class="bp-document-forward">' . esc_html__( 'Go to document', 'buddyboss' ) . '</a>';
	}
	wp_die( $message, $title, array( 'response' => $status ) ); // WPCS: XSS ok.
}

/**
 * Redirect to a file to start the download.
 *
 * @param string $file_path File path.
 * @param string $filename  File name.
 * @since BuddyBoss 1.4.1
 */
function bp_document_download_file_redirect( $file_path, $filename = '' ) {
	header( 'Location: ' . $file_path );
	exit;
}

/**
 * Parse file path and see if its remote or local.
 *
 * @param  string $file_path File path.
 * @return array
 * @since BuddyBoss 1.4.1
 */
function bp_document_parse_file_path( $file_path ) {
	$wp_uploads     = wp_upload_dir();
	$wp_uploads_dir = $wp_uploads['basedir'];
	$wp_uploads_url = $wp_uploads['baseurl'];

	/**
	 * Replace uploads dir, site url etc with absolute counterparts if we can.
	 * Note the str_replace on site_url is on purpose, so if https is forced
	 * via filters we can still do the string replacement on a HTTP file.
	 */
	$replacements = array(
		$wp_uploads_url                  => $wp_uploads_dir,
		network_site_url( '/', 'https' ) => ABSPATH,
		str_replace( 'https:', 'http:', network_site_url( '/', 'http' ) ) => ABSPATH,
		site_url( '/', 'https' )         => ABSPATH,
		str_replace( 'https:', 'http:', site_url( '/', 'http' ) ) => ABSPATH,
	);

	$file_path        = str_replace( array_keys( $replacements ), array_values( $replacements ), $file_path );
	$parsed_file_path = wp_parse_url( $file_path );
	$remote_file      = true;

	// Paths that begin with '//' are always remote URLs.
	if ( '//' === substr( $file_path, 0, 2 ) ) {
		return array(
			'remote_file' => true,
			'file_path'   => is_ssl() ? 'https:' . $file_path : 'http:' . $file_path,
		);
	}

	// See if path needs an abspath prepended to work.
	if ( file_exists( ABSPATH . $file_path ) ) {
		$remote_file = false;
		$file_path   = ABSPATH . $file_path;

	} elseif ( '/wp-content' === substr( $file_path, 0, 11 ) ) {
		$remote_file = false;
		$file_path   = realpath( WP_CONTENT_DIR . substr( $file_path, 11 ) );

		// Check if we have an absolute path.
	} elseif ( ( ! isset( $parsed_file_path['scheme'] ) || ! in_array( $parsed_file_path['scheme'], array( 'http', 'https', 'ftp' ), true ) ) && isset( $parsed_file_path['path'] ) && file_exists( $parsed_file_path['path'] ) ) {
		$remote_file = false;
		$file_path   = $parsed_file_path['path'];
	}

	return array(
		'remote_file' => $remote_file,
		'file_path'   => $file_path,
	);
}

/**
 * Parse the HTTP_RANGE request from iOS devices.
 * Does not support multi-range requests.
 *
 * @param int $file_size Size of file in bytes.
 * @return array {
 *     Information about range download request: beginning and length of
 *     file chunk, whether the range is valid/supported and whether the request is a range request.
 *
 *     @type int  $start            Byte offset of the beginning of the range. Default 0.
 *     @type int  $length           Length of the requested file chunk in bytes. Optional.
 *     @type bool $is_range_valid   Whether the requested range is a valid and supported range.
 *     @type bool $is_range_request Whether the request is a range request.
 * }
 * @since BuddyBoss 1.4.1
 */
function bp_document_get_download_range( $file_size ) {
	$start          = 0;
	$download_range = array(
		'start'            => $start,
		'is_range_valid'   => false,
		'is_range_request' => false,
	);

	if ( ! $file_size ) {
		return $download_range;
	}

	$end                      = $file_size - 1;
	$download_range['length'] = $file_size;

	if ( isset( $_SERVER['HTTP_RANGE'] ) ) { // @codingStandardsIgnoreLine.
		$http_range                         = sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ); // WPCS: input var ok.
		$download_range['is_range_request'] = true;

		$c_start = $start;
		$c_end   = $end;
		// Extract the range string.
		list( , $range ) = explode( '=', $http_range, 2 );
		// Make sure the client hasn't sent us a multibyte range.
		if ( strpos( $range, ',' ) !== false ) {
			return $download_range;
		}

		/*
		 * If the range starts with an '-' we start from the beginning.
		 * If not, we forward the file pointer
		 * and make sure to get the end byte if specified.
		 */
		if ( '-' === $range[0] ) {
			// The n-number of the last bytes is requested.
			$c_start = $file_size - substr( $range, 1 );
		} else {
			$range   = explode( '-', $range );
			$c_start = ( isset( $range[0] ) && is_numeric( $range[0] ) ) ? (int) $range[0] : 0;
			$c_end   = ( isset( $range[1] ) && is_numeric( $range[1] ) ) ? (int) $range[1] : $file_size;
		}

		/*
		 * Check the range and make sure it's treated according to the specs: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html.
		 * End bytes can not be larger than $end.
		 */
		$c_end = ( $c_end > $end ) ? $end : $c_end;
		// Validate the requested range and return an error if it's not correct.
		if ( $c_start > $c_end || $c_start > $file_size - 1 || $c_end >= $file_size ) {
			return $download_range;
		}
		$start  = $c_start;
		$end    = $c_end;
		$length = $end - $start + 1;

		$download_range['start']          = $start;
		$download_range['length']         = $length;
		$download_range['is_range_valid'] = true;
	}
	return $download_range;
}

/**
 * Read file chunked.
 *
 * Reads file in chunks so big downloads are possible without changing PHP.INI - http://codeigniter.com/wiki/Download_helper_for_large_files/.
 *
 * @param  string $file   File.
 * @param  int    $start  Byte offset/position of the beginning from which to read from the file.
 * @param  int    $length Length of the chunk to be read from the file in bytes, 0 means full file.
 * @return bool Success or fail
 * @since BuddyBoss 1.4.1
 */
function bp_document_readfile_chunked( $file, $start = 0, $length = 0 ) {
	if ( ! defined( 'BP_DOCUMENT_CHUNK_SIZE' ) ) {
		define( 'BP_DOCUMENT_CHUNK_SIZE', 1024 * 1024 );
	}
	$handle = @fopen( $file, 'r' ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen

	if ( false === $handle ) {
		return false;
	}

	if ( ! $length ) {
		$length = @filesize( $file ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
	}

	$read_length = (int) BP_DOCUMENT_CHUNK_SIZE;

	if ( $length ) {
		$end = $start + $length - 1;

		@fseek( $handle, $start ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		$p = @ftell( $handle ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

		while ( ! @feof( $handle ) && $p <= $end ) { // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			// Don't run past the end of file.
			if ( $p + $read_length > $end ) {
				$read_length = $end - $p + 1;
			}

			echo @fread( $handle, $read_length ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.XSS.EscapeOutput.OutputNotEscaped, WordPress.WP.AlternativeFunctions.file_system_read_fread
			$p = @ftell( $handle ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged

			if ( ob_get_length() ) {
				ob_flush();
				flush();
			}
		}
	} else {
		while ( ! @feof( $handle ) ) { // @codingStandardsIgnoreLine.
			echo @fread( $handle, $read_length ); // @codingStandardsIgnoreLine.
			if ( ob_get_length() ) {
				ob_flush();
				flush();
			}
		}
	}

	return @fclose( $handle ); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fclose
}

/**
 * Set up activity arguments for use with the 'document' scope.
 *
 * @since BuddyBoss 1.4.2
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array $retval
 */
function bp_activity_filter_document_scope( $retval = array(), $filter = array() ) {
	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'value'   => 'document',
			'compare' => '=',
		),
		array(
			'column' => 'hide_sitewide',
			'value'  => 1,
		),
	);

	return $retval;
}
add_filter( 'bp_activity_set_document_scope_args', 'bp_activity_filter_document_scope', 10, 2 );

/**
 * Shows default icon for media in WordPress media library list view.
 *
 * @since BuddyBoss 1.4.3
 *
 * @param array   $attr       List of image attributes.
 * @param WP_Post $attachment Attachment Post object.
 * @param string  $size       Sizes for the image.
 *
 * @return mixed
 */
function bp_document_media_library_list_view_document_attachment_image( $attr, $attachment, $size ) {
	if ( ! is_admin() ) {
		return $attr;
	}

	global $current_screen;

	if (
		empty( $current_screen )
		|| ! isset( $current_screen->parent_file )
		|| $current_screen->parent_file !== 'upload.php'
		|| empty( $attachment )
		|| empty( $attachment->ID )
	) {
		return $attr;
	}

	$meta = get_post_meta( $attachment->ID, '_wp_attached_file', true );
	if ( empty( $meta ) ) {
		return $attr;
	}

	if ( strstr( $meta, 'bb_documents/' ) ) {
		$attr['src']   = includes_url() . 'images/media/default.png';
		$attr['style'] = 'width:42px;height:60px;border:0;';
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'bp_document_media_library_list_view_document_attachment_image', 10, 3 );

/**
 * Add document repair list item.
 *
 * @param array $repair_list Repair list.
 *
 * @since BuddyBoss 1.4.4
 * @return array Repair list items.
 */
function bp_document_add_admin_repair_items( $repair_list ) {
	if ( bp_is_active( 'activity' ) ) {
		$repair_list[] = array(
			'bp-repair-document',
			esc_html__( 'Repair documents', 'buddyboss' ),
			'bp_document_admin_repair_document',
		);
	}

	return $repair_list;
}

/**
 * Repair BuddyBoss document.
 *
 * @since BuddyBoss 1.4.4
 */
function bp_document_admin_repair_document() {
	global $wpdb;
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$bp     = buddypress();

	$document_query = "SELECT id, activity_id FROM {$bp->document->table_name} WHERE activity_id != 0 LIMIT 50 OFFSET $offset ";
	$documents      = $wpdb->get_results( $document_query );

	if ( ! empty( $documents ) ) {
		foreach ( $documents as $document ) {
			if ( ! empty( $document->id ) && ! empty( $document->activity_id ) ) {
				$activity = new BP_Activity_Activity( $document->activity_id );
				if ( ! empty( $activity->id ) ) {
					if ( 'activity_comment' === $activity->type ) {
						$activity = new BP_Activity_Activity( $activity->item_id );
					}
					if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
						$up_document           = new BP_Document( $document->id );
						$up_document->privacy  = 'grouponly';
						$up_document->group_id = $activity->item_id;
						$up_document->save();

					}
					if ( 'document' === $activity->privacy ) {
						if ( ! empty( $activity->secondary_item_id ) ) {
							$document_activity = new BP_Activity_Activity( $activity->secondary_item_id );
							if ( ! empty( $document_activity->id ) ) {
								if ( 'activity_comment' === $document_activity->type ) {
									$document_activity = new BP_Activity_Activity( $document_activity->item_id );
								}
								if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $document_activity->component ) {
									$up_document           = new BP_Document( $document->id );
									$up_document->privacy  = 'grouponly';
									$up_document->group_id = $document_activity->item_id;
									$up_document->save();
									$activity->item_id   = $document_activity->item_id;
									$activity->component = buddypress()->groups->id;
								}
							}
						}

						$activity->hide_sitewide = true;
						$activity->save();
					}
				}
			}
			$offset ++;
		}
		$records_updated = sprintf( __( '%s documents updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		return array(
			'status'  => 1,
			'message' => __( 'Repairing documents &hellip; Complete!', 'buddyboss' ),
		);
	}
}

/**
 * Set up document arguments for use with the 'public' scope.
 *
 * @since BuddyBoss 1.4.4
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 *
 * @return array
 */
function bp_members_filter_document_public_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$folder_id = 0;
	$folders   = array();
	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$folder_ids           = array();
			$user_root_folder_ids = bp_document_get_folder_children( (int) $folder_id );
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}
			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'folder_id',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		$folders = array(
			'column'  => 'folder_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	$privacy = array( 'public' );

	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => $privacy,
		),
		array(
			'column'  => 'group_id',
			'compare' => '=',
			'value'   => '0',
		),
		$folders,
	);

	if ( ! bp_is_profile_document_support_enabled() ) {
		$args[] = array(
			'column'  => 'user_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	return $args;
}

add_filter( 'bp_document_set_document_public_scope_args', 'bp_members_filter_document_public_scope', 10, 2 );


/**
 * Set up document arguments for use with the 'public' scope.
 *
 * @since BuddyBoss 1.4.4
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 *
 * @return array
 */
function bp_members_filter_folder_public_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$folder_id = 0;
	$folders   = array();
	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$folder_ids           = array();
			$user_root_folder_ids = bp_document_get_folder_children( (int) $folder_id );
			if ( $user_root_folder_ids ) {
				foreach ( $user_root_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}
			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'parent',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		$folders = array(
			'column'  => 'parent',
			'compare' => '=',
			'value'   => '0',
		);
	}

	$privacy = array( 'public' );

	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => $privacy,
		),
		array(
			'column'  => 'group_id',
			'compare' => '=',
			'value'   => '0',
		),
		$folders,
	);

	if ( ! bp_is_profile_document_support_enabled() ) {
		$args[] = array(
			'column'  => 'user_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	return $args;
}

add_filter( 'bp_document_set_folder_public_scope_args', 'bp_members_filter_folder_public_scope', 10, 2 );

/**
 * Added text on the email when replied on the activity.
 *
 * @since BuddyBoss 1.4.7
 *
 * @param BP_Activity_Activity $activity Activity Object.
 */
function bp_document_activity_after_email_content( $activity ) {
	$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

	if ( ! empty( $document_ids ) ) {
		$document_ids  = explode( ',', $document_ids );
		$document_text = sprintf(
			_n( '%s document', '%s documents', count( $document_ids ), 'buddyboss' ),
			bp_core_number_format( count( $document_ids ) )
		);
		$content       = sprintf(
			/* translator: 1. Activity link, 2. Activity document count */
			__( '<a href="%1$s" target="_blank">%2$s uploaded</a>', 'buddyboss' ),
			bp_activity_get_permalink( $activity->id ),
			$document_text
		);
		echo wpautop( $content );
	}
}

/**
 * Adds activity document data for the edit activity
 *
 * @param array $activity Activity data.
 *
 * @return array $activity Returns the activity with document if document saved otherwise no documents.
 *
 * @since BuddyBoss 1.5.1
 */
function bp_document_get_edit_activity_data( $activity ) {

	if ( ! empty( $activity['id'] ) ) {

		// Fetch document ids of activity.
		$document_ids = bp_activity_get_meta( $activity['id'], 'bp_document_ids', true );
		$document_id  = bp_activity_get_meta( $activity['id'], 'bp_document_id', true );

		if ( ! empty( $document_id ) && ! empty( $document_ids ) ) {
			$document_ids = $document_ids . ',' . $document_id;
		} elseif ( ! empty( $document_id ) && empty( $document_ids ) ) {
			$document_ids = $document_id;
		}

		if ( ! empty( $document_ids ) ) {
			$activity['document'] = array();

			$document_ids = explode( ',', $document_ids );
			$document_ids = array_unique( $document_ids );
			$folder_id    = 0;

			foreach ( $document_ids as $document_id ) {
				if ( bp_is_active( 'moderation' ) && bp_moderation_is_content_hidden( $document_id, BP_Moderation_Document::$moderation_type ) ) {
					continue;
				}
				$document = new BP_Document( $document_id );

				$size = 0;
				$file = get_attached_file( $document->attachment_id );
				if ( $file && file_exists( $file ) ) {
					$size = filesize( $file );
				}

				$activity['document'][] = array(
					'id'          => $document_id,
					'doc_id'      => $document->attachment_id,
					'name'        => $document->title,
					'group_id'    => $document->group_id,
					'folder_id'   => $document->folder_id,
					'activity_id' => $document->activity_id,
					'type'        => 'document',
					'url'         => wp_get_attachment_url( $document->attachment_id ),
					'size'        => $size,
					'saved'       => true,
					'menu_order'  => $document->menu_order,
					'full_name'   => esc_attr( basename( $file ) ),
				);

				if ( 0 === $folder_id && $document->folder_id > 0 ) {
					$folder_id                    = $document->folder_id;
					$activity['can_edit_privacy'] = false;
				}
			}
		}

		$activity['profile_document'] = bp_is_profile_document_support_enabled() && bb_document_user_can_upload( bp_loggedin_user_id(), 0 );
		$activity['group_document']   = bp_is_group_document_support_enabled() && bb_document_user_can_upload( bp_loggedin_user_id(), ( bp_is_active( 'groups' ) && 'groups' === $activity['object'] ? $activity['item_id'] : 0 ) );

	}

	return $activity;
}

/**
 * Added activity entry class for media.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $class class.
 *
 * @return string
 */
function bp_document_activity_entry_css_class( $class ) {

	if ( bp_is_active( 'media' ) && bp_is_active( 'activity' ) ) {
		$document_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_document_ids', true );
		if ( ! empty( $document_ids ) ) {
			$class .= ' document-activity';
		}
	}

	return $class;

}

/**
 * Added the video symlink data to update when privacy update.
 *
 * @param array $response  Response array.
 * @param array $post_data The post data.
 *
 * @return array $response data.
 */
function bb_document_update_video_symlink( $response, $post_data ) {

	if ( ! empty( $post_data['id'] ) ) {

		// Fetch document ids of activity.
		$document_ids = bp_activity_get_meta( $post_data['id'], 'bp_document_ids', true );

		if ( ! empty( $document_ids ) ) {

			$document_ids = explode( ',', $document_ids );
			$count        = count( $document_ids );
			if ( 1 === $count ) {
				$document = new BP_Document( (int) current( $document_ids ) );
				$file_url = wp_get_attachment_url( $document->attachment_id );
				$filetype = wp_check_filetype( $file_url );
				$ext      = $filetype['ext'];
				if ( empty( $ext ) ) {
					$path = wp_parse_url( $file_url, PHP_URL_PATH );
					$ext  = pathinfo( basename( $path ), PATHINFO_EXTENSION );
				}

				if ( ! empty( $filetype ) && strstr( $filetype['type'], 'video/' ) ) {
					$response['video_symlink']     = bb_document_video_get_symlink( (int) current( $document_ids ) );
					$response['video_extension']   = 'video/' . $ext;
					$response['extension']         = $ext;
					$response['video_id']          = (int) current( $document_ids );
					$response['video_link_update'] = true;
					$response['video_js_id']       = 'video-' . (int) current( $document_ids ) . '_html5_api';
				}
			}
		}
	}

	return $response;

}


/**
 * Add rewrite rule to setup document preview.
 *
 * @since BuddyBoss 1.7.2
 */
function bb_setup_document_preview() {
	add_rewrite_rule( 'bb-document-preview/([^/]+)/([^/]+)/?$', 'index.php?bb-document-preview=$matches[1]&id1=$matches[2]', 'top' );
	add_rewrite_rule( 'bb-document-preview/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?bb-document-preview=$matches[1]&id1=$matches[2]&size=$matches[3]', 'top' );
	add_rewrite_rule( 'bb-document-player/([^/]+)/([^/]+)/?$', 'index.php?bb-document-player=$matches[1]&id1=$matches[2]', 'top' );
}

/**
 * Setup query variable for document preview.
 *
 * @param array $query_vars Array of query variables.
 *
 * @return array
 *
 * @since BuddyBoss 1.7.2
 */
function bb_setup_query_document_preview( $query_vars ) {
	$query_vars[] = 'bb-document-preview';
	$query_vars[] = 'bb-document-player';
	$query_vars[] = 'id1';
	$query_vars[] = 'size';

	return $query_vars;
}

/**
 * Setup template for the document preview.
 *
 * @param string $template Template path to include.
 *
 * @return string
 *
 * @since BuddyBoss 1.7.2
 */
function bb_setup_template_for_document_preview( $template ) {
	if ( ! empty( get_query_var( 'bb-document-preview' ) ) ) {

		/**
		 * Hooks to perform any action before the template load.
		 *
		 * @since BuddyBoss 1.7.2
		 */
		do_action( 'bb_setup_template_for_document_preview' );

		return trailingslashit( buddypress()->plugin_dir ) . 'bp-templates/bp-nouveau/includes/document/preview.php';
	}

	if ( ! empty( get_query_var( 'bb-document-player' ) ) ) {

		/**
		 * Hooks to perform any action before the template load.
		 *
		 * @since BuddyBoss 1.7.2
		 */
		do_action( 'bb_setup_template_for_document_player' );

		return trailingslashit( buddypress()->plugin_dir ) . 'bp-templates/bp-nouveau/includes/document/player.php';
	}

	return $template;
}

/**
 * Add rewrite rule to setup attachment document preview.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_setup_attachment_document_preview() {
	add_rewrite_rule( 'bb-attachment-document-preview/([^/]+)/?$', 'index.php?document-attachment-id=$matches[1]', 'top' );
}

/**
 * Setup query variable for attachment document preview.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param array $query_vars Array of query variables.
 *
 * @return array
 */
function bb_setup_attachment_document_preview_query( $query_vars ) {
	$query_vars[] = 'document-attachment-id';

	return $query_vars;
}

/**
 * Setup template for the attachment document preview.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param string $template Template path to include.
 *
 * @return string
 */
function bb_setup_attachment_document_preview_template( $template ) {

	if ( ! empty( get_query_var( 'document-attachment-id' ) ) ) {

		/**
		 * Hooks to perform any action before the template load.
		 *
		 * @since BuddyBoss 2.0.4
		 */
		do_action( 'bb_setup_attachment_document_preview_template' );

		return trailingslashit( buddypress()->plugin_dir ) . 'bp-templates/bp-nouveau/includes/document/attachment.php';
	}

	return $template;
}

/**
 * Enable document preview without trailing slash.
 *
 * @since BuddyBoss 2.3.2
 *
 * @param string $redirect_url URL to render.
 *
 * @return mixed|string
 */
function bb_document_remove_specific_trailing_slash( $redirect_url ) {
	if (
		strpos( $redirect_url, 'bb-document-preview' ) !== false ||
		strpos( $redirect_url, 'bb-attachment-document-preview' ) !== false
	) {
		$redirect_url = untrailingslashit( $redirect_url );
	}
	return $redirect_url;
}
add_filter( 'redirect_canonical', 'bb_document_remove_specific_trailing_slash', 9999 );

/**
 * Put document attachment as media.
 *
 * @since BuddyBoss 2.3.60
 *
 * @param WP_Post $attachment Attachment Post object.
 *
 * @return mixed
 */
function bb_messages_document_save( $attachment ) {

	if (
		(
			bp_is_group_messages() ||
			bp_is_messages_component() ||
			(
				! empty( $_POST['component'] ) &&
				'messages' === $_POST['component']
			)
		) &&
		bp_is_messages_document_support_enabled() &&
		! empty( $attachment )
	) {
		$documents[] = array(
			'id'      => $attachment->ID,
			'name'    => $attachment->post_title,
			'privacy' => 'message',
		);

		remove_action( 'bp_document_add', 'bp_activity_document_add', 9 );
		remove_filter( 'bp_document_add_handler', 'bp_activity_create_parent_document_activity', 9 );

		$document_ids = bp_document_add_handler( $documents, 'message' );

		if ( ! is_wp_error( $document_ids ) ) {
			update_post_meta( $attachment->ID, 'bp_media_parent_message_id', 0 );

			// Message not actually sent.
			update_post_meta( $attachment->ID, 'bp_document_saved', 0 );

			$thread_id = 0;
			if ( ! empty( $_POST['thread_id'] ) ) {
				$thread_id = absint( $_POST['thread_id'] );
			}

			// Message not actually sent.
			update_post_meta( $attachment->ID, 'thread_id', $thread_id );
		}

		add_filter( 'bp_document_add_handler', 'bp_activity_create_parent_document_activity', 9 );
		add_action( 'bp_document_add', 'bp_activity_document_add', 9 );

		return $document_ids;
	}

	return false;
}

add_action( 'bb_document_upload', 'bb_messages_document_save' );
