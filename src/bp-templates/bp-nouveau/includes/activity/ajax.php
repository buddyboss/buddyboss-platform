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
			array(
				'activity_update_pinned_post' => array(
					'function' => 'bb_nouveau_ajax_activity_update_pinned_post',
					'nopriv'   => true,
				),
			),
			array(
				'activity_update_close_comments' => array(
					'function' => 'bb_nouveau_ajax_activity_update_close_comments',
					'nopriv'   => false,
				),
			),
			array(
				'activity_loadmore_comments' => array(
					'function' => 'bb_nouveau_ajax_activity_load_more_comments',
					'nopriv'   => true,
				),
			),
			array(
				'activity_sync_from_modal' => array(
					'function' => 'bb_nouveau_ajax_activity_sync_from_modal',
					'nopriv'   => true,
				),
			),
			array(
				'toggle_activity_notification_status' => array(
					'function' => 'bb_nouveau_ajax_toggle_activity_notification_status',
					'nopriv'   => false,
				),
			),
			array(
				'delete_scheduled_activity' => array(
					'function' => 'bb_nouveau_ajax_delete_scheduled_activity',
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

	$error_message = esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' );

	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $error_message );
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error( $error_message );
	}

	$item_id   = sanitize_text_field( $_POST['item_id'] );
	$item_type = sanitize_text_field( $_POST['item_type'] );
	$user_id   = bp_loggedin_user_id();

	if ( ! bb_all_enabled_reactions( $item_type ) ) {
		wp_send_json_error( esc_html__( 'Reactions are temporarily disabled by site admin, please try again later', 'buddyboss' ) );
	}

	if ( ! empty( $_POST['reaction_id'] ) ) {
		$reaction_id = sanitize_text_field( $_POST['reaction_id'] );
	} else {
		$reaction_id = bb_load_reaction()->bb_reactions_reaction_id();
	}

	$reacted = bp_activity_add_user_favorite(
		$item_id,
		$user_id,
		array(
			'type'        => $item_type,
			'reaction_id' => $reaction_id,
			'error_type'  => 'wp_error',
		)
	);

	// If there was an error, return it.
	if ( is_wp_error( $reacted ) ) {
		wp_send_json_error( $reacted->get_error_message() );
	}

	$response = array(
		'reaction_button' => bb_get_activity_post_reaction_button_html( $item_id, $item_type, $reaction_id, true ),
		'reaction_count'  => bb_get_activity_post_user_reactions_html( $item_id, $item_type, false ),
	);

	$fav_count = (int) bp_get_total_favorite_count_for_user( $user_id );

	if ( 1 === $fav_count ) {
		$response['directory_tab'] = sprintf(
			'<li id="activity-favorites" data-bp-scope="favorites" data-bp-object="activity">
				<a href="%1$s"><div class="bb-component-nav-item-point">%2$s</div> </a>
			</li>',
			esc_url( bp_loggedin_user_domain() . bp_get_activity_slug() . '/favorites/' ),
			bb_is_reaction_emotions_enabled() ? esc_html__( 'Reactions', 'buddyboss' ) : esc_html__( 'Likes', 'buddyboss' )
		);
	}

	wp_send_json_success( $response );
}

/**
 * Un-favourite an activity via a POST request.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string JSON reply
 */
function bp_nouveau_ajax_unmark_activity_favorite() {

	$error_message = esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' );

	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $error_message );
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error( $error_message );
	}

	if ( empty( $_POST['item_id'] ) ) {
		wp_send_json_error( esc_html__( 'No item id', 'buddyboss' ) );
	}

	$item_id   = sanitize_text_field( $_POST['item_id'] );
	$item_type = sanitize_text_field( $_POST['item_type'] );
	$user_id   = bp_loggedin_user_id();

	if ( ! bb_all_enabled_reactions( $item_type ) ) {
		wp_send_json_error( esc_html__( 'Reactions are temporarily disabled by site admin, please try again later', 'buddyboss' ) );
	}

	$un_reacted = bp_activity_remove_user_favorite(
		$item_id,
		$user_id,
		array(
			'type'       => $item_type,
			'error_type' => 'wp_error',
		)
	);

	if ( is_wp_error( $un_reacted ) ) {
		wp_send_json_error( $un_reacted->get_error_message() );
	}

	$response = array(
		'reaction_button' => bb_get_activity_post_reaction_button_html( $item_id, $item_type ),
		'reaction_count'  => bb_get_activity_post_user_reactions_html( $item_id, $item_type, false ),
	);

	$fav_count = (int) bp_get_total_favorite_count_for_user( $user_id );
	if ( 0 === $fav_count && ! bp_is_single_activity() ) {
		$response['no_favorite'] = sprintf(
			'<aside class="bp-feedback bp-messages info">
				<span class="bp-icon" aria-hidden="true"></span>
				<p>%s</p>
			</aside>',
			esc_html__( 'Sorry, there was no activity found.', 'buddyboss' )
		);
	}

	wp_send_json_success( $response );
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

	$args = array(
		'activity_ids'     => $_POST['id'],
		'display_comments' => 'stream',
	);

	// Check scheduled status.
	$post_status = filter_input( INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	if ( ! empty( $post_status ) && sanitize_text_field( wp_unslash( $post_status ) ) === bb_get_activity_scheduled_status() ) {
		$args['status'] = $post_status;
	}

	$activity_array = bp_activity_get_specific( $args );

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
			'skip_error'  => false === $content ? false : true, // Pass true when $content will be not empty.
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

		// Set activity related properties to be used in the loop.
		if ( ! isset( $activities_template->activity->component ) ) {
			if ( ! isset( $p_obj ) ) {
				$a_obj = new BP_Activity_Activity( $activities_template->activities[0]->item_id );
			} else {
				$a_obj = new BP_Activity_Activity( $p_obj->item_id );
			}

			$activities_template->activity->component         = $a_obj->component;
			$activities_template->activity->item_id           = $a_obj->item_id;
			$activities_template->activity->secondary_item_id = $a_obj->secondary_item_id;
		}
	}

	ob_start();

	$comment_template_args = array();
	if ( ! empty( $_POST['edit_comment'] ) && ! bp_is_single_activity() ) {
		$comment_template_args = array(
			'show_replies'   => false,
			'limit_comments' => true,
		);
	}

	// Get activity comment template part.
	add_filter( 'bp_get_activity_comment_css_class', 'bb_activity_recent_comment_class' );
	bp_get_template_part( 'activity/comment', null, $comment_template_args );
	remove_filter( 'bp_get_activity_comment_css_class', 'bb_activity_recent_comment_class' );
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
		$exclude_groups                     = array();
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
		if ( ! empty( $exclude_groups ) ) {
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
		} elseif ( ! empty( sanitize_text_field( wp_unslash( (int) $_POST['poll_id'] ) ) ) ) {
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

	// Check if the activity comments closed.
	if ( ! empty( $activity_id ) && bb_is_close_activity_comments_enabled() && bb_is_activity_comments_closed( $activity_id ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'The comments are closed for the activity. The activity cannot be edited.', 'buddyboss' )
			)
		);
	}

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

	$activity_metas = bb_activity_get_metadata( $activity_id );

	if (
		bp_is_active( 'media' ) &&
		! empty( $_POST['media'] )
	) {
		$group_id = ( 'group' === $object ) ? $item_id : 0;

		$media_ids      = $activity_metas['bp_media_ids'][0] ?? '';
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

		$document_ids      = $activity_metas['bp_document_ids'][0] ?? '';
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

	$activity_status      = bb_get_activity_published_status();
	$schedule_date_time   = '';
	$is_scheduled         = false;
	$activity_action_type = bb_filter_input_string( INPUT_POST, 'activity_action_type' );
	if ( ! empty( $activity_action_type ) && 'scheduled' === $activity_action_type ) {
		$activity_status = bb_get_activity_scheduled_status();

		$activity_schedule_date_raw = bb_filter_input_string( INPUT_POST, 'activity_schedule_date_raw' );
		$activity_schedule_time     = bb_filter_input_string( INPUT_POST, 'activity_schedule_time' );
		$activity_schedule_meridiem = bb_filter_input_string( INPUT_POST, 'activity_schedule_meridiem' );
		if ( ! empty( $activity_schedule_date_raw ) && ! empty( $activity_schedule_time ) && ! empty( $activity_schedule_meridiem ) ) {
			$is_scheduled = true;

			$activity_schedule_date_raw = sanitize_text_field( $activity_schedule_date_raw );
			$activity_schedule_meridiem = sanitize_text_field( $activity_schedule_meridiem ); // 'pm' or 'am'
			$activity_schedule_time     = sanitize_text_field( $activity_schedule_time );

			// Convert 12-hour time format to 24-hour time format.
			$activity_schedule_time_24hr = date( 'H:i', strtotime( $activity_schedule_time . ' ' . $activity_schedule_meridiem ) );

			// Combine date and time.
			$activity_datetime = $activity_schedule_date_raw . ' ' . $activity_schedule_time_24hr;

			// Convert to MySQL datetime format.
			$schedule_date_time = get_gmt_from_date( $activity_datetime );

			// Get current GMT timestamp.
			$current_timestamp = gmdate( 'U' );

			// Add 1 hour to the timestamp (in seconds).
			$next_hour_timestamp = $current_timestamp + 3600;

			// Add 3 months to the timestamp (in seconds).
			$three_months_ago_timestamp = strtotime( '+3 months', $current_timestamp );
			$scheduled_timestamp        = strtotime( $schedule_date_time );
			if ( empty( $activity_id ) ) {
				// Check if the scheduled date is within the next hour.
				if ( $scheduled_timestamp < $next_hour_timestamp ) {
					wp_send_json_error(
						array(
							'message' => __( 'Please set a minimum schedule time for at least 1 hour later.', 'buddyboss' ),
						)
					);
				}

				// Check if the scheduled date is more than three months ago.
				if ( $scheduled_timestamp > $three_months_ago_timestamp ) {
					wp_send_json_error(
						array(
							'message' => __( 'Please set a schedule date between next three months.', 'buddyboss' ),
						)
					);
				}
			} elseif ( ! empty( $activity_id ) ) {

				// Check if the scheduled time is changed.
				$obj_activity = new BP_Activity_Activity( $activity_id );
				if ( strtotime( $obj_activity->date_recorded ) !== $scheduled_timestamp && $scheduled_timestamp < $next_hour_timestamp ) {
					wp_send_json_error(
						array(
							'message' => __( 'Please set a minimum schedule time for at least 1 hour later.', 'buddyboss' ),
						)
					);
				}
			}
		}
	}

	if ( $is_scheduled && ! bb_is_enabled_activity_schedule_posts() ) {
		wp_send_json_error(
			array(
				'message' => __( 'Schedule activity settings disabled.', 'buddyboss' ),
			)
		);
	}

	if ( 'user' === $object && bp_is_active( 'activity' ) ) {

		if ( ! bb_user_can_create_activity() ) {
			wp_send_json_error(
				array(
					'message' => __( 'You don\'t have access to do a activity.', 'buddyboss' ),
				)
			);
		}
		if (
			$is_scheduled &&
			(
				! function_exists( 'bb_can_user_schedule_activity' ) ||
				! bb_can_user_schedule_activity()
			)
		) {
			wp_send_json_error(
				array(
					'message' => __( 'You don\'t have access to schedule the activity.', 'buddyboss' ),
				)
			);
		}

		$content = $_POST['content'];

		if ( ! empty( $_POST['user_id'] ) && bp_get_displayed_user() && $_POST['user_id'] != bp_get_displayed_user()->id ) {
			$content = sprintf( '@%s %s', bp_get_displayed_user_mentionname(), $content );

			// Draft activity meta key.
			$draft_activity_meta_key .= '_' . bp_get_displayed_user()->id;
		}

		$post_array = array(
			'id'         => $activity_id,
			'content'    => $content,
			'privacy'    => $privacy,
			'error_type' => 'wp_error',
		);

		if ( $is_scheduled ) {
			$post_array['recorded_time'] = $schedule_date_time;
			$post_array['status']        = $activity_status;
		}
		$activity_id = bp_activity_post_update( $post_array );

	} elseif ( 'group' === $object ) {
		if ( $item_id && bp_is_active( 'groups' ) ) {

			$_POST['group_id'] = $item_id; // Set POST variable for group id for further processing from other components.

			if (
				$is_scheduled &&
				(
					! function_exists( 'bb_can_user_schedule_activity' ) ||
					! bb_can_user_schedule_activity(
						array(
							'object'   => 'group',
							'group_id' => $item_id,
						)
					)
				)
			) {
				wp_send_json_error(
					array(
						'message' => __( 'You don\'t have permission to schedule activity in particular group.', 'buddyboss' ),
					)
				);
			}

			$post_array = array(
				'id'       => $activity_id,
				'content'  => $_POST['content'],
				'group_id' => $item_id,
			);

			if ( $is_scheduled ) {
				$post_array['recorded_time'] = $schedule_date_time;
				$post_array['status']        = $activity_status;
			}

			// This function is setting the current group!
			$activity_id = groups_post_update( $post_array );

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
			 * @param bool $is_private Privacy status for the update.
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

/**
 * Update activity pinned post.
 *
 * @since BuddyBoss 2.4.60
 *
 * @return void
 */
function bb_nouveau_ajax_activity_update_pinned_post() {
	$response = array(
		'feedback' => esc_html__( 'There was a problem marking this operation. Please try again.', 'buddyboss' ),
	);

	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( $response );
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['pin_action'] ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	if ( ! in_array( $_POST['pin_action'], array( 'pin', 'unpin' ), true ) ) {
		wp_send_json_error( $response );
	}

	$args = array(
		'action'      => $_POST['pin_action'],
		'activity_id' => (int) $_POST['id'],
		'retval'      => 'string',
	);

	$retval = bb_activity_pin_unpin_post( $args );

	if ( ! empty( $retval ) ) {
		if ( 'unpinned' === $retval ) {
			$response['feedback'] = esc_html__( 'Your pinned post has been removed', 'buddyboss' );
		} elseif ( 'pinned' === $retval ) {
			$response['feedback'] = esc_html__( 'Your post has been pinned', 'buddyboss' );
		} elseif ( 'not_allowed' === $retval || 'not_member' === $retval ) {
			$response['feedback'] = esc_html__( 'Your are not allowed to pinned or unpinned the post', 'buddyboss' );
		} elseif ( 'pin_updated' === $retval ) {
			$response['feedback'] = esc_html__( 'Your pinned post has been updated', 'buddyboss' );
		}

		$response = apply_filters( 'bb_ajax_activity_update_pinned_post', $response, $_POST );
	}

	if ( ! empty( $retval ) && in_array( $retval, array( 'unpinned', 'pinned', 'pin_updated' ), true ) ) {
		wp_send_json_success( $response );
	} else {
		wp_send_json_error( $response );
	}
}

/**
 * Update close activity comments.
 *
 * @since BuddyBoss 2.5.80
 *
 * @return void
 */
function bb_nouveau_ajax_activity_update_close_comments() {
	$response = array(
		'feedback' => esc_html__( 'There was a problem marking this operation. Please try again.', 'buddyboss' ),
	);

	if ( ! bb_is_close_activity_comments_enabled() ) {
		wp_send_json_error(
			array(
				'feedback' => esc_html__( 'There was a problem marking this operation. Close comments setting is disabled.', 'buddyboss' ),
			)
		);
	}

	if (
		! bp_is_post_request() ||
		! is_user_logged_in() ||
		empty( $_POST['nonce'] ) ||
		! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' )
	) {
		wp_send_json_error( $response );
	}

	if (
		empty( $_POST['id'] ) ||
		empty( $_POST['close_comments_action'] ) ||
		! in_array( $_POST['close_comments_action'], array( 'close_comments', 'unclose_comments' ), true )
	) {
		wp_send_json_error( $response );
	}

	$args = array(
		'action'      => $_POST['close_comments_action'],
		'activity_id' => (int) $_POST['id'],
		'user_id'     => bp_loggedin_user_id(),
		'retval'      => 'string',
	);

	$retval = bb_activity_close_unclose_comments( $args );
	if ( ! empty( $retval ) ) {

		if ( 'unclosed_comments' === $retval ) {
			$response['feedback'] = esc_html__( 'You turned on commenting for this post', 'buddyboss' );
		} elseif ( 'closed_comments' === $retval ) {
			$response['feedback'] = esc_html__( 'You turned off commenting for this post', 'buddyboss' );
		} elseif ( 'not_allowed' === $retval || 'not_member' === $retval ) {
			$response['feedback'] = esc_html__( 'You are not permitted with the requested operation', 'buddyboss' );
		}

		/**
		 * Filters the response before updating activity close comments via AJAX.
		 * This filter allows modification of the response data before it's used to update activity close comments via AJAX.
		 *
		 * @since BuddyBoss 2.5.80
		 *
		 * @param mixed $response The response data. Can be of any type.
		 * @param array $_POST    The $_POST data received via AJAX request.
		 */
		$response = apply_filters( 'bb_ajax_activity_update_close_comments', $response, $_POST );
	}

	if ( ! empty( $retval ) && in_array( $retval, array( 'unclosed_comments', 'closed_comments' ), true ) ) {
		wp_send_json_success( $response );
	} else {
		wp_send_json_error( $response );
	}
}

/**
 * Get more comments.
 *
 * @since BuddyBoss 2.5.80
 *
 * @return void
 */
function bb_nouveau_ajax_activity_load_more_comments() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid request.', 'buddyboss' ),
			)
		);
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid request.', 'buddyboss' ),
			)
		);
	}

	if ( empty( $_POST['activity_id'] ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Activity id cannot be empty.', 'buddyboss' ),
			)
		);
	}

	if ( empty( $_POST['parent_comment_id'] ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Parent comment id cannot be empty.', 'buddyboss' ),
			)
		);
	}

	global $activities_template;
	$activity_id       = ! empty( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;
	$parent_comment_id = ! empty( $_POST['parent_comment_id'] ) ? (int) $_POST['parent_comment_id'] : 0;

	$activities_template = new stdClass();
	$parent_commment     = new BP_Activity_Activity( $parent_comment_id );
	if ( empty( $parent_commment ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid request.', 'buddyboss' ),
			)
		);
	}
	$comments = BP_Activity_Activity::append_comments(
		array( $parent_commment ),
		'',
		true,
		array(
			'limit'                  => bb_get_activity_comment_loading(),
			'offset'                 => ! empty( $_POST['offset'] ) ? (int) $_POST['offset'] : 0,
			'last_comment_timestamp' => ! empty( $_POST['last_comment_timestamp'] ) ? sanitize_text_field( $_POST['last_comment_timestamp'] ) : '',
			'last_comment_id'        => ! empty( $_POST['last_comment_id'] ) ? (int) $_POST['last_comment_id'] : 0,
			'comment_order_by'       => apply_filters( 'bb_activity_recurse_comments_order_by', 'ASC' ),
		)
	);

	if ( empty( $comments[0] ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'No more items to load.', 'buddyboss' ),
			)
		);
	}

	$activities_template->activity = $comments[0];
	// We have all comments and replies just loop through.
	ob_start();

	$args = array(
		'limit_comments'         => true,
		'comment_load_limit'     => bb_get_activity_comment_loading(),
		'parent_comment_id'      => $parent_comment_id,
		'main_activity_id'       => $activity_id,
		'is_ajax_load_more'      => true,
		'last_comment_timestamp' => ! empty( $_POST['last_comment_timestamp'] ) ? sanitize_text_field( $_POST['last_comment_timestamp'] ) : '',
		'last_comment_id'        => ! empty( $_POST['last_comment_id'] ) ? (int) $_POST['last_comment_id'] : 0,
	);

	// Check if parent is the main activity.
	if ( isset( $activities_template->activity ) ) {
		// No current comment.
		bp_activity_recurse_comments( $activities_template->activity, $args );
	} else {
		wp_send_json_error(
			array(
				'message' => __( 'No more items to load.', 'buddyboss' ),
			)
		);
	}

	wp_send_json_success(
		array(
			'comments' => ob_get_clean(),
		)
	);
}

/**
 * Get particular activity to sync when activity modal is closed.
 *
 * @since BuddyBoss 2.5.80
 *
 * @return void
 */
function bb_nouveau_ajax_activity_sync_from_modal() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid request.', 'buddyboss' ),
			)
		);
	}

	// Nonce verification.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Invalid request.', 'buddyboss' ),
			)
		);
	}

	if ( empty( $_POST['activity_id'] ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Activity id cannot be empty.', 'buddyboss' ),
			)
		);
	}

	$activity_id = ! empty( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;

	$args = array(
		'in'               => $activity_id,
		'display_comments' => true,
	);

	ob_start();
	if ( bp_has_activities( $args ) ) {
		while ( bp_activities() ) {
			bp_the_activity();
			bp_get_template_part( 'activity/entry' );
		}
	}

	wp_send_json_success(
		array(
			'activity' => ob_get_clean(),
		)
	);
}

/**
 * Mute/Unmute Activity Notification.
 *
 * @since BuddyBoss 2.5.80
 *
 * @return void
 */
function bb_nouveau_ajax_toggle_activity_notification_status() {
	$response = array(
		'feedback' => esc_html__( 'There was a problem marking this operation. Please try again.', 'buddyboss' ),
	);

	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( $response );
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['notification_toggle_action'] ) || ! in_array( $_POST['notification_toggle_action'], array( 'mute', 'unmute' ), true ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$args = array(
		'action'      => $_POST['notification_toggle_action'],
		'activity_id' => (int) $_POST['id'],
		'user_id'     => bp_loggedin_user_id(),
	);

	$retval = bb_toggle_activity_notification_status( $args );

	if ( 'unmute' === $retval ) {
		$response['feedback'] = esc_html__( 'Notifications for this activity have been unmuted.', 'buddyboss' );
	} elseif ( 'mute' === $retval ) {
		$response['feedback'] = esc_html__( 'Notifications for this activity have been muted.', 'buddyboss' );
	} elseif ( 'already_muted' === $retval ) {
		$response['feedback'] = esc_html__( 'Notifications for this activity already been muted.', 'buddyboss' );
	}

	if ( ! empty( $retval ) && in_array( $retval, array( 'unmute', 'mute', 'already_muted' ), true ) ) {
		wp_send_json_success( $response );
	} else {
		wp_send_json_error( $response );
	}
}

/**
 * Deletes the scheduled Activity item received via a POST request.
 *
 * @since BuddyBoss 2.6.10
 *
 * @return void JSON reply.
 */
function bb_nouveau_ajax_delete_scheduled_activity() {
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

	$nonce = bb_filter_input_string( INPUT_POST, '_wpnonce' );

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'scheduled_post_nonce' ) ) {
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

	if (
		(
			// Check for non admin member to delete scheduled post which they scheduled when they have permission to moderate.
			! bp_current_user_can( 'bp_moderate' ) && 'groups' !== $activity->component
		) ||
		(
			// If Groups allow to schedule post then check user can delete schedule post or not;
			'groups' === $activity->component && bp_is_active( 'groups' ) && bb_is_enabled_activity_schedule_posts_filter()
		)
	) {
		$is_admin = groups_is_user_admin( $activity->user_id, $activity->item_id );
		$is_mod   = groups_is_user_mod( $activity->user_id, $activity->item_id );
		if ( ! $is_admin && ! $is_mod ) {
			wp_send_json_error(
				array(
					'feedback' => __( 'You don\'t have permission to delete scheduled activity.', 'buddyboss' ),
				)
			);
		}
	}

	do_action( 'bb_activity_before_action_delete_scheduled_activity', $activity->id, $activity->user_id );

	if (
		! bp_activity_delete(
			array(
				'id'      => $activity->id,
				'user_id' => $activity->user_id,
			)
		)
	) {
		wp_send_json_error( $response );
	}

	do_action( 'bb_activity_action_delete_scheduled_activity', $activity->id, $activity->user_id );

	// The activity has been deleted successfully.
	$response = array( 'deleted' => $activity->id );

	// If on a single activity redirect to user's home.
	if ( ! empty( $_POST['is_single'] ) ) {
		$response['redirect'] = bp_core_get_user_domain( $activity->user_id );
		bp_core_add_message( __( 'Activity deleted successfully', 'buddyboss' ) );
	}

	wp_send_json_success( $response );
}
