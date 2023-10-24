<?php
/**
 * Activity Ajax functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	function() {
		$ajax_actions = array(
			array(
				'activity_filter' => array(
					'function' => 'bp_nouveau_ajax_object_template_loader',
					'nopriv'   => true,
				),
			),
			array(
				'get_single_activity_content' => array(
					'function' => 'bp_nouveau_ajax_get_single_activity_content',
					'nopriv'   => true,
				),
			),
			array(
				'activity_mark_fav' => array(
					'function' => 'bp_nouveau_ajax_mark_activity_favorite',
					'nopriv'   => false,
				),
			),
			array(
				'activity_mark_unfav' => array(
					'function' => 'bp_nouveau_ajax_unmark_activity_favorite',
					'nopriv'   => false,
				),
			),
			array(
				'activity_clear_new_mentions' => array(
					'function' => 'bp_nouveau_ajax_clear_new_mentions',
					'nopriv'   => false,
				),
			),
			array(
				'delete_activity' => array(
					'function' => 'bp_nouveau_ajax_delete_activity',
					'nopriv'   => false,
				),
			),
			array(
				'new_activity_comment' => array(
					'function' => 'bp_nouveau_ajax_new_activity_comment',
					'nopriv'   => false,
				),
			),
			array(
				'bp_nouveau_get_activity_objects' => array(
					'function' => 'bp_nouveau_ajax_get_activity_objects',
					'nopriv'   => false,
				),
			),
			array(
				'post_update' => array(
					'function' => 'bp_nouveau_ajax_post_update',
					'nopriv'   => false,
				),
			),
			array(
				'bp_spam_activity' => array(
					'function' => 'bp_nouveau_ajax_spam_activity',
					'nopriv'   => false,
				),
			),
			array(
				'activity_update_privacy' => array(
					'function' => 'bp_nouveau_ajax_activity_update_privacy',
					'nopriv'   => false,
				),
			),
			array(
				'post_draft_activity' => array(
					'function' => 'bb_nouveau_ajax_post_draft_activity',
					'nopriv'   => false,
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
 * Mark an activity as a favourite via a POST request.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply
 */
function bp_nouveau_ajax_mark_activity_favorite() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error();
	}

	if ( bp_activity_add_user_favorite( $_POST['id'] ) ) {
		$response = array(
			'content'    => __( 'Unlike', 'buddyboss' ),
			'like_count' => bp_activity_get_favorite_users_string( $_POST['id'] ),
			'tooltip'    => bp_activity_get_favorite_users_tooltip_string( $_POST['id'] ),
		); // here like_count is for activity total like count

		if ( ! bp_is_user() ) {
			$fav_count = (int) bp_get_total_favorite_count_for_user( bp_loggedin_user_id() );

			if ( 1 === $fav_count ) {
				$response['directory_tab'] = '<li id="activity-favorites" data-bp-scope="favorites" data-bp-object="activity">
					<a href="' . bp_loggedin_user_domain() . bp_get_activity_slug() . '/favorites/">
						' . esc_html__( 'Likes', 'buddyboss' ) . '
					</a>
				</li>';
			} else {
				$response['fav_count'] = $fav_count;
			}
		}

		wp_send_json_success( $response );
	} else {
		wp_send_json_error();
	}
}

/**
 * Un-favourite an activity via a POST request.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply
 */
function bp_nouveau_ajax_unmark_activity_favorite() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error();
	}

	if ( bp_activity_remove_user_favorite( $_POST['id'] ) ) {
		$response = array(
			'content'    => __( 'Like', 'buddyboss' ),
			'like_count' => bp_activity_get_favorite_users_string( $_POST['id'] ),
			'tooltip'    => bp_activity_get_favorite_users_tooltip_string( $_POST['id'] ),
		); // here like_count is for activity total like count.

		$fav_count = (int) bp_get_total_favorite_count_for_user( bp_loggedin_user_id() );

		if ( 0 === $fav_count && ! bp_is_single_activity() ) {
			$response['no_favorite'] = '<li><div class="bp-feedback bp-messages info">
				' . __( 'Sorry, there was no activity found.', 'buddyboss' ) . '
			</div></li>';
		} else {
			$response['fav_count'] = $fav_count;
		}

		wp_send_json_success( $response );
	} else {
		wp_send_json_error();
	}
}

/**
 * Clear mentions if the directory tab is clicked
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply.
 */
function bp_nouveau_ajax_clear_new_mentions() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error();
	}

	bp_activity_clear_new_mentions( bp_loggedin_user_id() );
	wp_send_json_success();
}

/**
 * Deletes an Activity item/Activity comment item received via a POST request.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply.
 */
function bp_nouveau_ajax_delete_activity() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error">%s</div>',
			esc_html__( 'There was a problem when deleting. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_activity_delete_link' ) ) {
		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$activity = new BP_Activity_Activity( (int) $_POST['id'] );

	// Check access.
	if ( ! bp_activity_user_can_delete( $activity ) ) {
		wp_send_json_error( $response );
	}

	/** This action is documented in bp-activity/bp-activity-actions.php */
	do_action( 'bp_activity_before_action_delete_activity', $activity->id, $activity->user_id );

	// Deleting an activity comment.
	if ( ! empty( $_POST['is_comment'] ) ) {
		if ( ! bp_activity_delete_comment( $activity->item_id, $activity->id ) ) {
			wp_send_json_error( $response );
		}

		// Deleting an activity.
	} else {
		if ( ! bp_activity_delete(
			array(
				'id'      => $activity->id,
				'user_id' => $activity->user_id,
			)
		) ) {
			wp_send_json_error( $response );
		}
	}

	/** This action is documented in bp-activity/bp-activity-actions.php */
	do_action( 'bp_activity_action_delete_activity', $activity->id, $activity->user_id );

	// The activity has been deleted successfully.
	$response = array( 'deleted' => $activity->id );

	// If on a single activity redirect to user's home.
	if ( ! empty( $_POST['is_single'] ) ) {
		$response['redirect'] = bp_core_get_user_domain( $activity->user_id );
		bp_core_add_message( __( 'Activity deleted successfully', 'buddyboss' ) );
	}

	$activity_html      = '';
	$parent_activity_id = 0;
	if ( isset( $activity->secondary_item_id ) && ! empty( $activity->secondary_item_id ) ) {
		$parent_activity_id = $activity->secondary_item_id;
		ob_start();
		if ( bp_has_activities(
			array(
				'include' => $parent_activity_id,
			)
		) ) {
			while ( bp_activities() ) {
				bp_the_activity();
				bp_get_template_part( 'activity/entry' );
			}
		}
		$activity_html = ob_get_contents();
		ob_end_clean();
		$response['activity']           = $activity_html;
		$response['parent_activity_id'] = $parent_activity_id;
	}
	wp_send_json_success( $response );
}

/**
 * Fetches an activity's full, non-excerpted content via a POST request.
 * Used for the 'Read More' link on long activity items.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply.
 */
function bp_nouveau_ajax_get_single_activity_content() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error">%s</div>',
			esc_html__( 'There was a problem displaying the content. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error( $response );
	}

	$activity_array = bp_activity_get_specific(
		array(
			'activity_ids'     => $_POST['id'],
			'display_comments' => 'stream',
		)
	);

	if ( empty( $activity_array['activities'][0] ) ) {
		wp_send_json_error( $response );
	}

	$activity = $activity_array['activities'][0];

	/**
	 * Fires before the return of an activity's full, non-excerpted content via a POST request.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $activity Activity content. Passed by reference.
	 */
	do_action_ref_array( 'bp_nouveau_get_single_activity_content', array( &$activity ) );

	// Activity content retrieved through AJAX should run through normal filters, but not be truncated.
	remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

	if ( bp_is_active( 'media' ) ) {
		add_filter( 'bp_get_activity_content_body', 'bp_media_activity_append_media', 20, 2 );
		add_filter( 'bp_get_activity_content_body', 'bp_video_activity_append_video', 20, 2 );
		add_filter( 'bp_get_activity_content_body', 'bp_document_activity_append_document', 20, 2 );
		add_filter( 'bp_get_activity_content_body', 'bp_media_activity_append_gif', 20, 2 );
	}

	/** This filter is documented in bp-activity/bp-activity-template.php */
	$content = apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity->content, &$activity ) );

	wp_send_json_success( array( 'contents' => $content ) );
}

/**
 * Posts new Activity comments received via a POST request.
 *
 * @since BuddyPress 3.0.0
 *
 * @global BP_Activity_Template $activities_template
 *
 * @return string JSON reply.
 */
function bp_nouveau_ajax_new_activity_comment() {
	global $activities_template;
	$bp = buddypress();

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error">%s</div>',
			esc_html__( 'There was an error posting your reply. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce_new_activity_comment'] ) || ! wp_verify_nonce( $_POST['_wpnonce_new_activity_comment'], 'new_activity_comment' ) ) {
		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( $response );
	}

	// Check content empty or not for the media, document and gif.
	// If content will empty then return true and allow empty content in DB for the media, document and gif.
	$content = apply_filters( 'bb_is_activity_content_empty', $_POST );

	if ( false === $content ) { // Check if $content will false then content would be empty.
		wp_send_json_error(
			array(
				'feedback' => sprintf(
					'<div class="bp-feedback bp-messages error">%s</div>',
					esc_html__( 'Please do not leave the comment area blank.', 'buddyboss' )
				),
			)
		);
	}

	if ( empty( $_POST['form_id'] ) || empty( $_POST['comment_id'] ) || ! is_numeric( $_POST['form_id'] ) || ! is_numeric( $_POST['comment_id'] ) ) {
		wp_send_json_error( $response );
	}

	$edit_comment_id = 0;
	if ( ! empty( $_POST['edit_comment'] ) ) {
		$_POST['edit_comment'] = true;
		$edit_comment_id       = sanitize_text_field( wp_unslash( $_POST['comment_id'] ) );
	}

	$comment_id = bp_activity_new_comment(
		array(
			'id'          => $edit_comment_id,
			'activity_id' => $_POST['form_id'],
			'content'     => $_POST['content'],
			'parent_id'   => $_POST['comment_id'],
			'skip_error'  => false === $content ? false : true // Pass true when $content will be not empty.
		)
	);

	if ( ! $comment_id ) {
		if ( ! empty( $bp->activity->errors['new_comment'] ) && is_wp_error( $bp->activity->errors['new_comment'] ) ) {
			$response = array(
				'feedback' => sprintf(
					'<div class="bp-feedback bp-messages error">%s</div>',
					esc_html( $bp->activity->errors['new_comment']->get_error_message() )
				),
			);
			unset( $bp->activity->errors['new_comment'] );
		}

		wp_send_json_error( $response );
	}

	$activity = new BP_Activity_Activity( $comment_id );

	// Load the new activity item into the $activities_template global.
	bp_has_activities(
		array(
			'display_comments' => 'stream',
			'hide_spam'        => false,
			'show_hidden'      => true,
			'include'          => $comment_id,
			'privacy'          => (array) $activity->privacy,
			'scope'            => false,
		)
	);

	// Swap the current comment with the activity item we just loaded.
	if ( isset( $activities_template->activities[0] ) ) {
		$activities_template->activity                  = new stdClass();
		$activities_template->activity->id              = $activities_template->activities[0]->item_id;
		$activities_template->activity->current_comment = $activities_template->activities[0];

		// Because the whole tree has not been loaded, we manually
		// determine depth.
		$depth     = 1;
		$parent_id = (int) $activities_template->activities[0]->secondary_item_id;
		while ( $parent_id !== (int) $activities_template->activities[0]->item_id ) {
			$depth++;
			$p_obj     = new BP_Activity_Activity( $parent_id );
			$parent_id = (int) $p_obj->secondary_item_id;
		}
		$activities_template->activity->current_comment->depth = $depth;
	}

	ob_start();
	// Get activity comment template part.
	bp_get_template_part( 'activity/comment' );
	$response = array( 'contents' => ob_get_contents() );
	ob_end_clean();

	unset( $activities_template );

	wp_send_json_success( $response );
}

/**
 * Get items to attach the activity to.
 *
 * This is used within the activity post form autocomplete field.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply
 */
function bp_nouveau_ajax_get_activity_objects() {
	$response = array();

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error( $response );
	}

	if ( 'group' === $_POST['type'] ) {
		$exclude_groups = array();
		$exclude_groups_args                = array();
		$exclude_groups_args['user_id']     = bp_loggedin_user_id();
		$exclude_groups_args['show_hidden'] = true;
		$exclude_groups_args['fields']      = 'ids';
		$exclude_groups_args['per_page']    = - 1;
		if ( isset( $_POST['search'] ) ) {
			$exclude_groups_args['search_terms'] = $_POST['search'];
		}

		$exclude_groups_query = groups_get_groups( $exclude_groups_args );

		if ( ! empty( $exclude_groups_query['groups'] ) ) {
			foreach ( $exclude_groups_query['groups'] as $exclude_group ) {
				if ( false === groups_is_user_allowed_posting( bp_loggedin_user_id(), $exclude_group ) ) {
					$exclude_groups[] = $exclude_group;
				}
			}
		}
		$args                = array();
		$args['user_id']     = bp_loggedin_user_id();
		$args['show_hidden'] = true;
		$args['per_page']    = bb_activity_post_form_groups_per_page();
		$args['orderby']     = 'name';
		$args['order']       = 'ASC';
		if ( isset( $_POST['page'] ) ) {
			$args['page'] = $_POST['page'];
		}
		if ( isset( $_POST['search'] ) ) {
			$args['search_terms'] = $_POST['search'];
		}
		if ( ! empty( $exclude_groups ) ){
			$args['exclude'] = $exclude_groups;
		}

		$groups = groups_get_groups( $args );

		wp_send_json_success( array_map( 'bp_nouveau_prepare_group_for_js', $groups['groups'] ) );
	} else {

		/**
		 * Filters the response for custom activity objects.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $response Array of custom response objects to send to AJAX return.
		 * @param array $value    Activity object type from $_POST global.
		 */
		$response = apply_filters( 'bp_nouveau_get_activity_custom_objects', $response, $_POST['type'] );
	}

	if ( empty( $response ) ) {
		wp_send_json_error( array( 'error' => __( 'No activities were found.', 'buddyboss' ) ) );
	} else {
		wp_send_json_success( $response );
	}
}

/**
 * Processes Activity updates received via a POST request.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply.
 */
function bp_nouveau_ajax_post_update() {
	$bp = buddypress();

	if ( ! is_user_logged_in() || empty( $_POST['_wpnonce_post_update'] ) || ! wp_verify_nonce( $_POST['_wpnonce_post_update'], 'post_update' ) ) {
		wp_send_json_error();
	}

	if ( ! strlen( trim( html_entity_decode( wp_strip_all_tags( $_POST['content'] ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) ) ) {

		// check activity toolbar options if one of them is set, activity can be empty.
		$toolbar_option = false;
		if ( bp_is_active( 'media' ) && ! empty( $_POST['document'] ) ) {
			$toolbar_option = true;
		} elseif ( bp_is_active( 'media' ) && ! empty( $_POST['media'] ) ) {
			$toolbar_option = true;
		} elseif ( bp_is_activity_link_preview_active() && ! empty( $_POST['link_url'] ) ) {
			$toolbar_option = true;
		} elseif ( bp_is_active( 'media' ) && ! empty( $_POST['gif_data'] ) ) {
			$toolbar_option = true;
		} elseif ( bp_is_active( 'video' ) && ! empty( $_POST['video'] ) ) {
			$toolbar_option = true;
		}

		if ( ! $toolbar_option ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please enter some content to post.', 'buddyboss' ),
				)
			);
		}
	}

	$activity_id = ! empty( $_POST['id'] ) ? (int) $_POST['id'] : 0;
	$item_id     = 0;
	$object      = '';
	$is_private  = false;

	// Try to get the item id from posted variables.
	if ( ! empty( $_POST['item_id'] ) ) {
		$item_id = (int) $_POST['item_id'];
	}

	// Try to get the object from posted variables.
	if ( ! empty( $_POST['object'] ) ) {
		$object = sanitize_key( $_POST['object'] );

		// If the object is not set and we're in a group, set the item id and the object.
	} elseif ( bp_is_group() ) {
		$item_id = bp_get_current_group_id();
		$object  = 'group';
		$status  = groups_get_current_group()->status;
	}

	$draft_activity_meta_key = 'draft_' . $object;

	if (
		bp_is_active( 'media' ) &&
		! empty( $_POST['media'] )
	) {
		$group_id = ( 'group' === $object ) ? $item_id : 0;

		$media_ids      = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
		$existing_media = ( ! empty( $media_ids ) ) ? explode( ',', $media_ids ) : array();
		$posted_media   = array_column( $_POST['media'], 'media_id' );
		$posted_media   = wp_parse_id_list( $posted_media );
		$is_same_media  = ( count( $existing_media ) === count( $posted_media ) && ! array_diff( $existing_media, $posted_media ) );

		if ( ! bb_media_user_can_upload( bp_loggedin_user_id(), $group_id ) && ! $is_same_media ) {
			$message = sprintf(
			/* translators: 1: string or media and medias. 2: group text. */
				__( 'You don\'t have access to upload %1$s%2$s.', 'buddyboss' ),
				_n( 'media', 'medias', count( $_POST['media'] ), 'buddyboss' ),
				( ! empty( $group_id ) ? __( ' inside group', 'buddyboss' ) : '' )
			);
			wp_send_json_error( array( 'message' => $message ) );
		}
	}

	if (
		bp_is_active( 'document' ) &&
		! empty( $_POST['document'] )
	) {
		$group_id = ( 'group' === $object ) ? $item_id : 0;

		$document_ids      = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
		$existing_document = ( ! empty( $document_ids ) ) ? explode( ',', $document_ids ) : array();
		$posted_document   = array_column( $_POST['document'], 'document_id' );
		$posted_document   = wp_parse_id_list( $posted_document );
		$is_same_document  = ( count( $existing_document ) === count( $posted_document ) && ! array_diff( $existing_document, $posted_document ) );

		if ( ! bb_document_user_can_upload( bp_loggedin_user_id(), $group_id ) && ! $is_same_document ) {
			$message = sprintf(
			/* translators: 1: string or media and medias. 2: group text. */
				__( 'You don\'t have access to upload %1$s%2$s.', 'buddyboss' ),
				_n( 'document', 'documents', count( $_POST['document'] ), 'buddyboss' ),
				( ! empty( $group_id ) ? __( ' inside group', 'buddyboss' ) : '' )
			);

			wp_send_json_error( array( 'message' => $message ) );
		}
	}

	$privacy = 'public';
	if ( ! empty( $_POST['privacy'] ) && in_array( $_POST['privacy'], array( 'public', 'onlyme', 'loggedin', 'friends' ), true ) ) {
		$privacy = $_POST['privacy']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	if ( 'user' === $object && bp_is_active( 'activity' ) ) {

		if ( ! bb_user_can_create_activity() ) {
			wp_send_json_error(
				array(
					'message' => __( 'You don\'t have access to do a activity.', 'buddyboss' ),
				)
			);
		}

		$content = $_POST['content'];

		if ( ! empty( $_POST['user_id'] ) && bp_get_displayed_user() && $_POST['user_id'] != bp_get_displayed_user()->id ) {
			$content = sprintf( '@%s %s', bp_get_displayed_user_mentionname(), $content );

			// Draft activity meta key.
			$draft_activity_meta_key .= '_' . bp_get_displayed_user()->id;
		}

		$activity_id = bp_activity_post_update(
			array(
				'id'         => $activity_id,
				'content'    => $content,
				'privacy'    => $privacy,
				'error_type' => 'wp_error',
			)
		);

	} elseif ( 'group' === $object ) {
		if ( $item_id && bp_is_active( 'groups' ) ) {

			$_POST['group_id'] = $item_id; // Set POST variable for group id for further processing from other components

			// This function is setting the current group!
			$activity_id = groups_post_update(
				array(
					'id'       => $activity_id,
					'content'  => $_POST['content'],
					'group_id' => $item_id,
				)
			);

			if ( empty( $status ) ) {
				if ( ! empty( $bp->groups->current_group->status ) ) {
					$status = $bp->groups->current_group->status;
				} else {
					$group  = groups_get_group( array( 'group_id' => $group_id ) );
					$status = $group->status;
				}

				$is_private = 'public' !== $status;
			}

			// Draft activity meta key.
			$draft_activity_meta_key .= '_' . $item_id;
		}
	} else {
		/** This filter is documented in bp-activity/bp-activity-actions.php */
		$activity_id = apply_filters( 'bp_activity_custom_update', false, $object, $item_id, $_POST['content'] );
	}

	if ( empty( $activity_id ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'There was a problem posting your update. Please try again.', 'buddyboss' ),
			)
		);
	}

	if ( is_wp_error( $activity_id ) ) {
		wp_send_json_error(
			array(
				'message' => $activity_id->get_error_message(),
			)
		);
	}

	// Delete draft activity.
	delete_user_meta( bp_loggedin_user_id(), $draft_activity_meta_key );

	ob_start();
	if ( bp_has_activities(
		array(
			'include'     => $activity_id,
			'show_hidden' => $is_private,
		)
	) ) {
		while ( bp_activities() ) {
			bp_the_activity();
			bp_get_template_part( 'activity/entry' );
		}
	}
	$activity = ob_get_contents();
	ob_end_clean();

	wp_send_json_success(
		array(
			'id'                      => $activity_id,
			'message'                 => esc_html__( 'Update posted.', 'buddyboss' ) . ' ' . sprintf( '<a href="%s" class="just-posted">%s</a>', esc_url( bp_activity_get_permalink( $activity_id ) ), esc_html__( 'View activity.', 'buddyboss' ) ),
			'activity'                => $activity,

			/**
			 * Filters whether or not an AJAX post update is private.
			 *
			 * @since BuddyPress 3.0.0
			 *
			 * @param string/bool $is_private Privacy status for the update.
			 */
			'is_private'              => apply_filters( 'bp_nouveau_ajax_post_update_is_private', $is_private ),
			'is_directory'            => bp_is_activity_directory(),
			'is_user_activity'        => bp_is_user_activity(),
			'is_active_activity_tabs' => bp_is_activity_tabs_active(),
		)
	);
}

/**
 * Save activity draft data.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_nouveau_ajax_post_draft_activity() {
	if ( ! is_user_logged_in() || empty( $_POST['_wpnonce_post_draft'] ) || ! wp_verify_nonce( $_POST['_wpnonce_post_draft'], 'post_draft_activity' ) ) {
		wp_send_json_error();
	}

	$draft_activity = $_REQUEST['draft_activity'] ?? '';

	if ( ! empty( $_REQUEST['draft_activity'] ) && ! is_array( $_REQUEST['draft_activity'] ) ) {
		$draft_activity = json_decode( stripslashes( $draft_activity ), true );
	}

	if ( is_array( $draft_activity ) && isset( $draft_activity['data_key'], $draft_activity['object'] ) ) {

		if ( isset( $draft_activity['post_action'] ) && 'update' === $draft_activity['post_action'] ) {

			// Set media draft meta key to avoid delete from cron job 'bp_media_delete_orphaned_attachments'.
			if ( isset( $draft_activity['data']['media'] ) && ! empty( $draft_activity['data']['media'] ) ) {
				foreach ( $draft_activity['data']['media'] as $media_key => $new_media_attachment ) {
					if ( ! isset( $new_media_attachment['bb_media_draft'] ) ) {
						$draft_activity['data']['media'][ $media_key ]['bb_media_draft'] = 1;
						update_post_meta( $new_media_attachment['id'], 'bb_media_draft', 1 );
					}
				}
			}

			// Set media draft meta key to avoid delete from cron job 'bp_media_delete_orphaned_attachments'.
			if ( isset( $draft_activity['data']['document'] ) && ! empty( $draft_activity['data']['document'] ) ) {
				foreach ( $draft_activity['data']['document'] as $document_key => $new_document_attachment ) {
					if ( ! isset( $new_document_attachment['bb_media_draft'] ) ) {
						$draft_activity['data']['document'][ $document_key ]['bb_media_draft'] = 1;
						update_post_meta( $new_document_attachment['id'], 'bb_media_draft', 1 );
					}
				}
			}

			// Set video draft meta key to avoid delete from cron job 'bp_media_delete_orphaned_attachments'.
			if ( isset( $draft_activity['data']['video'] ) && ! empty( $draft_activity['data']['video'] ) ) {
				foreach ( $draft_activity['data']['video'] as $video_key => $new_video_attachment ) {
					if ( ! isset( $new_video_attachment['bb_media_draft'] ) ) {
						$draft_activity['data']['video'][ $video_key ]['bb_media_draft'] = 1;
						update_post_meta( $new_video_attachment['id'], 'bb_media_draft', 1 );
					}
				}
			}

			bp_update_user_meta( bp_loggedin_user_id(), $draft_activity['data_key'], $draft_activity );
		} else {
			bp_delete_user_meta( bp_loggedin_user_id(), $draft_activity['data_key'] );

			// Delete media when discard the activity.
			if ( isset( $draft_activity['delete_media'] ) && 'true' === $draft_activity['delete_media'] && ! empty( $draft_activity['data'] ) ) {

				$medias    = $draft_activity['data']['media'] ?? array();
				$documents = $draft_activity['data']['document'] ?? array();
				$videos    = $draft_activity['data']['video'] ?? array();

				// Delete the medias.
				if ( ! empty( $medias ) ) {
					foreach ( $medias as $media ) {
						if ( ! empty( $media['id'] ) && 0 < (int) $media['id'] ) {
							wp_delete_attachment( $media['id'], true );
						}
					}
				}

				// Delete the documents.
				if ( ! empty( $documents ) ) {
					foreach ( $documents as $document ) {
						if ( ! empty( $document['id'] ) && 0 < (int) $document['id'] ) {
							wp_delete_attachment( $document['id'], true );
						}
					}
				}

				// Delete the videos.
				if ( ! empty( $videos ) ) {
					foreach ( $videos as $video ) {
						if ( ! empty( $video['id'] ) && 0 < (int) $video['id'] ) {
							wp_delete_attachment( $video['id'], true );
						}
					}
				}

			}

			$draft_activity['data'] = false;
		}
	}

	wp_send_json_success(
		array(
			'draft_activity' => $draft_activity,
		)
	);
}

/**
 * AJAX spam an activity item or comment.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply.
 */
function bp_nouveau_ajax_spam_activity() {
	$bp = buddypress();

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback bp-messages error">%s</div>',
			esc_html__( 'There was a problem marking this activity as spam. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() || ! bp_is_active( 'activity' ) || empty( $bp->activity->akismet ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) || ! is_numeric( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	// Is the current user allowed to spam items?
	if ( ! bp_activity_user_can_mark_spam() ) {
		wp_send_json_error( $response );
	}

	$activity = new BP_Activity_Activity( (int) $_POST['id'] );

	if ( empty( $activity->component ) ) {
		wp_send_json_error( $response );
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_activity_akismet_spam_' . $activity->id ) ) {
		wp_send_json_error( $response );
	}

	/** This action is documented in bp-activity/bp-activity-actions.php */
	do_action( 'bp_activity_before_action_spam_activity', $activity->id, $activity );

	// Mark as spam.
	bp_activity_mark_as_spam( $activity );
	$activity->save();

	/** This action is documented in bp-activity/bp-activity-actions.php */
	do_action( 'bp_activity_action_spam_activity', $activity->id, $activity->user_id );

	// Prepare the successfull reply.
	$response = array( 'spammed' => $activity->id );

	// If on a single activity redirect to user's home.
	if ( ! empty( $_POST['is_single'] ) ) {
		$response['redirect'] = bp_core_get_user_domain( $activity->user_id );
		bp_core_add_message( __( 'This activity has been marked as spam and is no longer visible.', 'buddyboss' ) );
	}

	// Send the json reply.
	wp_send_json_success( $response );
}

/**
 * Update activity privacy via a POST request.
 *
 * @since BuddyBoss 1.2.3
 *
 * @return string JSON reply
 */
function bp_nouveau_ajax_activity_update_privacy() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['privacy'] ) ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error();
	}

	if ( ! in_array( $_POST['privacy'], array( 'public', 'loggedin', 'onlyme', 'friends' ) ) ) {
		wp_send_json_error();
	}

	$activity = new BP_Activity_Activity( (int) $_POST['id'] );

	if ( bp_activity_user_can_delete( $activity ) ) {
		remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
		$activity->privacy = $_POST['privacy'];
		$activity->save();
		add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );

		$response = apply_filters( 'bb_ajax_activity_update_privacy', array(), $_POST );

		wp_send_json_success( $response );
	} else {
		wp_send_json_error();
	}
}
