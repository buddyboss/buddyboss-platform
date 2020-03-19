<?php
/**
 * Messages Ajax functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function() {
	$ajax_actions = array(
		array( 'messages_send_message'             => array( 'function' => 'bp_nouveau_ajax_messages_send_message', 'nopriv' => false ) ),
		array( 'messages_send_reply'               => array( 'function' => 'bp_nouveau_ajax_messages_send_reply', 'nopriv' => false ) ),
		array( 'messages_get_user_message_threads' => array( 'function' => 'bp_nouveau_ajax_get_user_message_threads', 'nopriv' => false ) ),
		array( 'messages_thread_read'              => array( 'function' => 'bp_nouveau_ajax_messages_thread_read', 'nopriv' => false ) ),
		array( 'messages_get_thread_messages'      => array( 'function' => 'bp_nouveau_ajax_get_thread_messages', 'nopriv' => false ) ),
		array( 'messages_delete'                   => array( 'function' => 'bp_nouveau_ajax_delete_thread_messages', 'nopriv' => false ) ),
		array( 'messages_unstar'                   => array( 'function' => 'bp_nouveau_ajax_star_thread_messages', 'nopriv' => false ) ),
		array( 'messages_star'                     => array( 'function' => 'bp_nouveau_ajax_star_thread_messages', 'nopriv' => false ) ),
		array( 'messages_unread'                   => array( 'function' => 'bp_nouveau_ajax_readunread_thread_messages', 'nopriv' => false ) ),
		array( 'messages_read'                     => array( 'function' => 'bp_nouveau_ajax_readunread_thread_messages', 'nopriv' => false ) ),
		array( 'messages_dismiss_sitewide_notice'  => array( 'function' => 'bp_nouveau_ajax_dismiss_sitewide_notice', 'nopriv' => false ) ),
		array( 'messages_search_recipients'        => array( 'function' => 'bp_nouveau_ajax_dsearch_recipients', 'nopriv' => false ) ),
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
 * AJAX send message and display error.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_messages_send_message() {
	global $thread_template, $messages_template;

	$response = array(
		'feedback' => __( 'Your message could not be sent. Please try again.', 'buddyboss' ),
		'type'     => 'error',
	);

	// Verify nonce
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
		wp_send_json_error( $response );
	}

	// Validate subject and message content
	if ( empty( $_POST['message_content'] ) ) {
		$response['feedback'] = __( 'Your message was not sent. Please enter some content.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	// Validate recipients
	if ( empty( $_POST['send_to'] ) || ! is_array( $_POST['send_to'] ) ) {
		$response['feedback'] = __( 'Please add at least one recipient.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	// Trim @ from usernames
	/**
	 * Filters the results of trimming of `@` characters from usernames for who is set to receive a message.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value Array of trimmed usernames.
	 * @param array $value Array of un-trimmed usernames submitted.
	 */
	$recipients = apply_filters( 'bp_messages_recipients', array_map( function( $username ) {
		return trim( $username, '@' );
	}, $_POST['send_to'] ) );

	// Attempt to send the message.
	$send = messages_new_message( array(
		'recipients' => $recipients,
		'subject'    => wp_trim_words($_POST['message_content'], messages_get_default_subject_length()),
		'content'    => $_POST['message_content'],
		'error_type' => 'wp_error',
	) );

	// Send the message.
	if ( true === is_int( $send ) ) {
		$response = array();

		if ( bp_has_message_threads( array( 'include' => $send ) ) ) {

			while ( bp_message_threads() ) {
				bp_message_thread();
				$last_message_id = (int) $messages_template->thread->last_message_id;

				$response = array(
					'id'            => bp_get_message_thread_id(),
					'message_id'    => (int) $last_message_id,
					'subject'       => strip_tags( bp_get_message_thread_subject() ),
					'excerpt'       => strip_tags( bp_get_message_thread_excerpt() ),
					'content'       => do_shortcode( bp_get_message_thread_content() ),
					'unread'        => bp_message_thread_has_unread(),
					'sender_name'   => bp_core_get_user_displayname( $messages_template->thread->last_sender_id ),
					'sender_is_you' => $messages_template->thread->last_sender_id == bp_loggedin_user_id(),
					'sender_link'   => bp_core_get_userlink( $messages_template->thread->last_sender_id, false, true ),
					'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
						'item_id' => $messages_template->thread->last_sender_id,
						'object'  => 'user',
						'type'    => 'thumb',
						'width'   => BP_AVATAR_THUMB_WIDTH,
						'height'  => BP_AVATAR_THUMB_HEIGHT,
						'html'    => false,
					) ) ),
					'count'         => bp_get_message_thread_total_count(),
					'date'          => strtotime( bp_get_message_thread_last_post_date_raw() ) * 1000,
					'display_date'  => bp_nouveau_get_message_date( bp_get_message_thread_last_post_date_raw() ),
					'started_date' => bp_nouveau_get_message_date( $messages_template->thread->first_message_date, get_option('date_format') ),
				);

				if ( is_array( $messages_template->thread->recipients ) ) {
					foreach ( $messages_template->thread->recipients as $recipient ) {
						if ( empty( $recipient->is_deleted ) ) {
							$response['recipients'][] = array(
								'avatar'    => esc_url( bp_core_fetch_avatar( array(
									'item_id' => $recipient->user_id,
									'object'  => 'user',
									'type'    => 'thumb',
									'width'   => BP_AVATAR_THUMB_WIDTH,
									'height'  => BP_AVATAR_THUMB_HEIGHT,
									'html'    => false,
								) ) ),
								'user_link' => bp_core_get_userlink( $recipient->user_id, false, true ),
								'user_name' => bp_core_get_user_displayname( $recipient->user_id ),
								'is_you'    => $recipient->user_id == bp_loggedin_user_id()
							);
						}
					}
				}

				if ( bp_is_active( 'messages', 'star' ) ) {
					$star_link = bp_get_the_message_star_action_link( array(
						'thread_id' => bp_get_message_thread_id(),
						'url_only'  => true,
					) );

					$response['star_link'] = $star_link;

					$star_link_data         = explode( '/', $star_link );
					$response['is_starred'] = array_search( 'unstar', $star_link_data );

					// Defaults to last
					$sm_id = $last_message_id;

					if ( $response['is_starred'] ) {
						$sm_id = (int) $star_link_data[ $response['is_starred'] + 1 ];
					}

					$response['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . $sm_id );
					$response['starred_id'] = $sm_id;
				}

				$thread_extra_content = bp_nouveau_messages_catch_hook_content( array(
					'inboxListItem' => 'bp_messages_inbox_list_item',
					'threadOptions' => 'bp_messages_thread_options',
				) );

				if ( array_filter( $thread_extra_content ) ) {
					$response = array_merge( $response, $thread_extra_content );
				}
			}
		}

		if ( empty( $response ) ) {
			$response = array( 'id' => $send );
		}

		wp_send_json_success( array(
			'feedback'  => __( 'Message successfully sent.', 'buddyboss' ),
			'type'      => 'success',
			'thread'    => $response,
		) );

	// Message could not be sent.
	} else {
		$response['feedback'] = $send->get_error_message();

		wp_send_json_error( $response );
	}
}

/**
 * AJAX send message reply and display error.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_messages_send_reply() {
	$response = array(
		'feedback' => __( 'There was a problem sending your reply. Please try again.', 'buddyboss' ),
		'type'     => 'error',
	);

	// Verify nonce
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['content'] ) || empty( $_POST['thread_id'] ) ) {
		$response['feedback'] = __( 'Please add some content to your message.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	$thread_id = (int) $_POST['thread_id'];

	if ( ! bp_current_user_can( 'bp_moderate' ) && ( ! messages_is_valid_thread( $thread_id ) || ! messages_check_thread_access( $thread_id ) ) ) {
		wp_send_json_error( $response );
	}

	$new_reply = messages_new_message( array(
		'thread_id' => $thread_id,
		'subject'   => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
		'content'   => $_POST['content'],
		'date_sent' => $date_sent = bp_core_current_time(),
		'error_type' => 'wp_error',
	) );

	if ( is_wp_error( $new_reply ) ) {
		$response['feedback'] = $new_reply->get_error_message();
		wp_send_json_error( $response );
	}

	// Send the reply.
	if ( empty( $new_reply ) ) {
		wp_send_json_error( $response );
	}

	// Get the message by pretending we're in the message loop.
	global $thread_template, $media_template;

	$bp           = buddypress();
	$reset_action = $bp->current_action;

	// Override bp_current_action().
	$bp->current_action = 'view';

	bp_thread_has_messages( array( 'thread_id' => $thread_id , 'before' => $date_sent ) );

	// Set current message to current key.
	$thread_template->current_message = -1;

	// Now manually iterate message like we're in the loop.
	bp_thread_the_message();

	// Manually call oEmbed
	// this is needed because we're not at the beginning of the loop.
	bp_messages_embed();

	// Output single message template part.
	$reply = array(
		'id'            => bp_get_the_thread_message_id(),
		'content'       => do_shortcode( bp_get_the_thread_message_content() ),
		'sender_id'     => bp_get_the_thread_message_sender_id(),
		'sender_name'   => esc_html( bp_get_the_thread_message_sender_name() ),
		'sender_link'   => bp_get_the_thread_message_sender_link(),
		'sender_is_you' => bp_get_the_thread_message_sender_id() === bp_loggedin_user_id(),
		'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
			'item_id' => bp_get_the_thread_message_sender_id(),
			'object'  => 'user',
			'type'    => 'thumb',
			'width'   => 32,
			'height'  => 32,
			'html'    => false,
		) ) ),
		'date'          => bp_get_the_thread_message_date_sent() * 1000,
		'display_date'  => bp_get_the_thread_message_time_since(),
	);

	if ( bp_is_active( 'messages', 'star' ) ) {
		$star_link = bp_get_the_message_star_action_link( array(
			'message_id' => bp_get_the_thread_message_id(),
			'url_only'  => true,
		) );

		$reply['star_link']  = $star_link;
		$reply['is_starred'] = array_search( 'unstar', explode( '/', $star_link ) );
	}

	if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
		$media_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_media_ids', true );

		if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) {
			$reply['media'] = array();
			while ( bp_media() ) {
				bp_the_media();

				$reply['media'][] = array(
					'id'        => bp_get_media_id(),
					'title'     => bp_get_media_title(),
					'thumbnail' => bp_get_media_attachment_image_thumbnail(),
					'full'      => bp_get_media_attachment_image(),
					'meta'      => $media_template->media->attachment_data->meta,
				);
			}
		}
	}

	if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) {
		$gif_data = bp_messages_get_meta( bp_get_the_thread_message_id(), '_gif_data', true );

		if ( ! empty( $gif_data ) ) {
			$preview_url = wp_get_attachment_url( $gif_data['still'] );
			$video_url = wp_get_attachment_url( $gif_data['mp4'] );
			$reply['gif'] = array(
				'preview_url' => $preview_url,
				'video_url' => $video_url,
			);
		}
	}

	$extra_content = bp_nouveau_messages_catch_hook_content( array(
		'beforeMeta'    => 'bp_before_message_meta',
		'afterMeta'     => 'bp_after_message_meta',
		'beforeContent' => 'bp_before_message_content',
		'afterContent'  => 'bp_after_message_content',
	) );

	if ( array_filter( $extra_content ) ) {
		$reply = array_merge( $reply, $extra_content );
	}

	// Clean up the loop.
	bp_thread_messages();

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	// set a flag
	$reply['is_new'] = true;

	wp_send_json_success( array(
		'messages' => array( $reply ),
		'feedback' => __( 'Your reply was sent successfully', 'buddyboss' ),
		'type'     => 'success',
	) );
}

/**
 * AJAX get all user message threads.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_get_user_message_threads() {
	global $messages_template;

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( array(
			'feedback' => __( 'Unauthorized request.', 'buddyboss' ),
			'type'     => 'error'
		) );
	}

	$bp           = buddypress();
	$reset_action = $bp->current_action;

	// Override bp_current_action().
	if ( isset( $_POST['box'] ) ) {
		$bp->current_action = $_POST['box'];
	}

	// Add the message thread filter.
	if ( 'starred' === $bp->current_action ) {
		add_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
	}

	// Simulate the loop.
	if ( ! bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) {
		// Remove the bp_current_action() override.
		$bp->current_action = $reset_action;

		wp_send_json_error( array(
			'feedback' => __( 'Sorry, no messages were found.', 'buddyboss' ),
			'type'     => 'info'
		) );
	}

	// remove the message thread filter.
	if ( 'starred' === $bp->current_action ) {
		remove_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
	}

	$threads       = new stdClass;
	$threads->meta = array(
		'total_page' => ceil( (int) $messages_template->total_thread_count / (int) $messages_template->pag_num ),
		'page'       => $messages_template->pag_page,
	);

	$threads->threads = array();
	$i                = 0;

	while ( bp_message_threads() ) : bp_message_thread();
		$last_message_id = (int) $messages_template->thread->last_message_id;

		if ( ! $last_message_id ) {
			continue;
		}

		$threads->threads[ $i ] = array(
			'id'            => bp_get_message_thread_id(),
			'message_id'    => (int) $last_message_id,
			'subject'       => strip_tags( bp_get_message_thread_subject() ),
			'excerpt'       => strip_tags( bp_get_message_thread_excerpt() ),
			'content'       => do_shortcode( bp_get_message_thread_content() ),
			'unread'        => bp_message_thread_has_unread(),
			'sender_name'   => bp_core_get_user_displayname( $messages_template->thread->last_sender_id ),
			'sender_is_you' => $messages_template->thread->last_sender_id == bp_loggedin_user_id(),
			'sender_link'   => bp_core_get_userlink( $messages_template->thread->last_sender_id, false, true ),
			'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
				'item_id' => $messages_template->thread->last_sender_id,
				'object'  => 'user',
				'type'    => 'thumb',
				'width'   => BP_AVATAR_THUMB_WIDTH,
				'height'  => BP_AVATAR_THUMB_HEIGHT,
				'html'    => false,
			) ) ),
			'count'         => bp_get_message_thread_total_count(),
			'date'          => strtotime( bp_get_message_thread_last_post_date_raw() ) * 1000,
			'display_date'  => bp_nouveau_get_message_date( bp_get_message_thread_last_post_date_raw() ),
			'started_date' => bp_nouveau_get_message_date( $messages_template->thread->first_message_date, get_option('date_format') ),
		);

		if ( is_array( $messages_template->thread->recipients ) ) {
			foreach ( $messages_template->thread->recipients as $recipient ) {
				if ( empty( $recipient->is_deleted ) ) {
					$threads->threads[ $i ]['recipients'][] = array(
						'avatar' => esc_url( bp_core_fetch_avatar( array(
							'item_id' => $recipient->user_id,
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => BP_AVATAR_THUMB_WIDTH,
							'height'  => BP_AVATAR_THUMB_HEIGHT,
							'html'    => false,
						) ) ),
						'user_link' => bp_core_get_userlink( $recipient->user_id, false, true ),
						'user_name' => bp_core_get_user_displayname( $recipient->user_id ),
						'is_you' => $recipient->user_id == bp_loggedin_user_id()
					);
				}
			}
		}

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link( array(
				'thread_id' => bp_get_message_thread_id(),
				'url_only'  => true,
			) );

			$threads->threads[ $i ]['star_link']  = $star_link;

			$star_link_data = explode( '/', $star_link );
			$threads->threads[ $i ]['is_starred'] = array_search( 'unstar', $star_link_data );

			// Defaults to last
			$sm_id = $last_message_id;

			if ( $threads->threads[ $i ]['is_starred'] ) {
				$sm_id = (int) $star_link_data[ $threads->threads[ $i ]['is_starred'] + 1 ];
			}

			$threads->threads[ $i ]['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . $sm_id );
			$threads->threads[ $i ]['starred_id'] = $sm_id;
		}

		if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
			$media_ids = bp_messages_get_meta( $last_message_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$media_ids = explode( ',', $media_ids );
				if ( sizeof( $media_ids ) < 2 ) {
					$threads->threads[ $i ]['excerpt'] = __( 'sent a photo', 'buddyboss' );
				} else {
					$threads->threads[ $i ]['excerpt'] = __( 'sent some photos', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) {
			$gif_data = bp_messages_get_meta( $last_message_id, '_gif_data', true );

			if ( ! empty( $gif_data ) ) {
				$threads->threads[ $i ]['excerpt'] = __( 'sent a gif', 'buddyboss' );
			}
		}

		$thread_extra_content = bp_nouveau_messages_catch_hook_content( array(
			'inboxListItem' => 'bp_messages_inbox_list_item',
			'threadOptions' => 'bp_messages_thread_options',
		) );

		if ( array_filter( $thread_extra_content ) ) {
			$threads->threads[ $i ] = array_merge( $threads->threads[ $i ], $thread_extra_content );
		}

		$i += 1;
	endwhile;

	$threads->threads = array_filter( $threads->threads );

	$extra_content = bp_nouveau_messages_catch_hook_content( array(
		'beforeLoop' => 'bp_before_member_messages_loop',
		'afterLoop'  => 'bp_after_member_messages_loop',
	) );

	if ( array_filter( $extra_content ) ) {
		$threads->extraContent = $extra_content;
	}

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	// Return the successfull reply.
	wp_send_json_success( $threads );
}

/**
 * AJAX mark message as read.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_messages_thread_read() {
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['id'] ) || empty( $_POST['message_id'] ) ) {
		wp_send_json_error();
	}

	$thread_id  = (int) $_POST['id'];
	$message_id = (int) $_POST['message_id'];

	if ( ! messages_is_valid_thread( $thread_id ) || ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) ) {
		wp_send_json_error();
	}

	// Mark thread as read
	messages_mark_thread_read( $thread_id );

	// Mark latest message as read
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'new_message' );
	}

	wp_send_json_success();
}

/**
 * AJAX get messages for each thread.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_get_thread_messages() {
	global $thread_template, $media_template;

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( array(
			'feedback' => __( 'Unauthorized request.', 'buddyboss' ),
			'type'     => 'error'
		) );
	}

	$response = array(
		'feedback' => __( 'Sorry, no messages were found.', 'buddyboss' ),
		'type'     => 'info'
	);

	$response_no_more = array(
		'feedback' => __( 'Sorry, no more messages can be loaded.', 'buddyboss' ),
		'type'     => 'info'
	);

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_id    = (int) $_POST['id'];
	$bp           = buddypress();
	$reset_action = $bp->current_action;

	// Override bp_current_action().
	$bp->current_action = 'view';

	bp_get_thread( array( 'thread_id' => $thread_id ) );

	$thread = new stdClass;

	// Check recipients if connected or not
	if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {

		$recipients = (array) $thread_template->thread->recipients;

		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		if ( isset( $recipients[ bp_loggedin_user_id() ] ) && ( count( $recipients ) > 1 ) ) {
			unset( $recipients[ bp_loggedin_user_id() ] );
		}

		foreach ( $recipients as $recipient ) {
			if ( bp_loggedin_user_id() != $recipient->user_id && ! friends_check_friendship( bp_loggedin_user_id(), $recipient->user_id ) ) {
				if ( sizeof( $recipients ) > 1 ) {
					$thread->feedback_error = array( 'feedback' => __( 'You need to be connected with all recipients to continue this conversation.', 'buddyboss' ), 'type' => 'error' );
				} else {
					$thread->feedback_error = array( 'feedback' => __( 'You need to be connected with this member to continue this conversation.', 'buddyboss' ), 'type' => 'error' );
				}
				break;
			}
		}
	}

	// Simulate the loop.

	$args = [
		'thread_id' => $thread_id,
		'per_page' => isset($_POST['per_page']) && $_POST['per_page']? $_POST['per_page'] : 10,
		'before' => isset($_POST['before']) && $_POST['before']? $_POST['before'] : null,
	];

	if ( ! bp_thread_has_messages( $args ) ) {
		// Remove the bp_current_action() override.
		$bp->current_action = $reset_action;

		wp_send_json_error( $args['before']? $response_no_more : $response );
	}

	if ( empty( $_POST['js_thread'] ) ) {
		$thread->thread = array(
			'id'      => bp_get_the_thread_id(),
			'subject' => strip_tags( bp_get_the_thread_subject() ),
			'started_date' => bp_nouveau_get_message_date( $thread_template->thread->first_message_date, get_option('date_format') ),
		);

		if ( is_array( $thread_template->thread->recipients ) ) {
			foreach ( $thread_template->thread->recipients as $recipient ) {
				if ( empty( $recipient->is_deleted ) ) {
					$thread->thread['recipients'][] = array(
						'avatar' => esc_url( bp_core_fetch_avatar( array(
							'item_id' => $recipient->user_id,
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => BP_AVATAR_THUMB_WIDTH,
							'height'  => BP_AVATAR_THUMB_HEIGHT,
							'html'    => false,
						) ) ),
						'user_link' => bp_core_get_userlink( $recipient->user_id, false, true ),
						'user_name' => bp_core_get_user_displayname( $recipient->user_id ),
						'is_you' => $recipient->user_id == bp_loggedin_user_id()
					);
				}
			}
		}
	}

	$thread->messages = array();
	$i = 0;

	while ( bp_thread_messages() ) : bp_thread_the_message();
		$thread->messages[ $i ] = array(
			'id'            => bp_get_the_thread_message_id(),
			'content'       => do_shortcode( bp_get_the_thread_message_content() ),
			'sender_id'     => bp_get_the_thread_message_sender_id(),
			'sender_name'   => esc_html( bp_get_the_thread_message_sender_name() ),
			'sender_link'   => bp_get_the_thread_message_sender_link(),
			'sender_is_you' => bp_get_the_thread_message_sender_id() === bp_loggedin_user_id(),
			'sender_avatar' => esc_url( bp_core_fetch_avatar( array(
				'item_id' => bp_get_the_thread_message_sender_id(),
				'object'  => 'user',
				'type'    => 'thumb',
				'width'   => 32,
				'height'  => 32,
				'html'    => false,
			) ) ),
			'date'          => bp_get_the_thread_message_date_sent() * 1000,
			'display_date'  => bp_get_the_thread_message_time_since(),
		);

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link( array(
				'message_id' => bp_get_the_thread_message_id(),
				'url_only'  => true,
			) );

			$thread->messages[ $i ]['star_link']  = $star_link;
			$thread->messages[ $i ]['is_starred'] = array_search( 'unstar', explode( '/', $star_link ) );
			$thread->messages[ $i ]['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . bp_get_the_thread_message_id() );
		}

		if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
			$media_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_media_ids', true );

			if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids, 'order_by' => 'menu_order', 'sort' => 'ASC' ) ) ) {
				$thread->messages[ $i ]['media'] = array();
				while ( bp_media() ) {
					bp_the_media();

					$thread->messages[ $i ]['media'][] = array(
						'id'        => bp_get_media_id(),
						'title'     => bp_get_media_title(),
						'thumbnail' => bp_get_media_attachment_image_thumbnail(),
						'full'      => bp_get_media_attachment_image(),
						'meta'      => $media_template->media->attachment_data->meta,
					);
				}
			}
		}

		if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) {
			$gif_data = bp_messages_get_meta( bp_get_the_thread_message_id(), '_gif_data', true );

			if ( ! empty( $gif_data ) ) {
				$preview_url = wp_get_attachment_url( $gif_data['still'] );
				$video_url = wp_get_attachment_url( $gif_data['mp4'] );
				$thread->messages[ $i ]['gif'] = array(
					'preview_url' => $preview_url,
					'video_url' => $video_url,
				);
			}
		}

		$extra_content = bp_nouveau_messages_catch_hook_content( array(
			'beforeMeta'    => 'bp_before_message_meta',
			'afterMeta'     => 'bp_after_message_meta',
			'beforeContent' => 'bp_before_message_content',
			'afterContent'  => 'bp_after_message_content',
		) );

		if ( array_filter( $extra_content ) ) {
			$thread->messages[ $i ] = array_merge( $thread->messages[ $i ], $extra_content );
		}

		$i += 1;
	endwhile;

	$thread->messages = array_filter( $thread->messages );

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	// pagination
	$thread->per_page = $thread_template->thread->messages_perpage;
	$thread->messages_count = $thread_template->thread->total_messages;
	$thread->next_messages_timestamp = $thread_template->thread->messages[count($thread_template->thread->messages) - 1]->date_sent;

	wp_send_json_success( $thread );
}

/**
 * AJAX delete entire message thread.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_delete_thread_messages() {
	$response = array(
		'feedback' => __( 'There was a problem deleting your messages. Please try again.', 'buddyboss' ),
		'type'     => 'error',
	);

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_ids = wp_parse_id_list( $_POST['id'] );

	foreach ( $thread_ids as $thread_id ) {
		if ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error( $response );
		}

		messages_delete_thread( $thread_id );
	}

	wp_send_json_success( array(
		'feedback' => __( 'Messages deleted', 'buddyboss' ),
		'type'     => 'success',
	) );
}

/**
 * AJAX mark message with star.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_star_thread_messages() {
	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$action = str_replace( 'messages_', '', $_POST['action'] );

	if ( 'star' === $action ) {
		$error_message = __( 'There was a problem starring your messages. Please try again.', 'buddyboss' );
	} else {
		$error_message = __( 'There was a problem unstarring your messages. Please try again.', 'buddyboss' );
	}

	$response = array(
		'feedback' => esc_html( $error_message ),
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages', 'star' ) || empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	// Check capability.
	if ( ! is_user_logged_in() || ! bp_core_can_edit_settings() ) {
		wp_send_json_error( $response );
	}

	$ids      = wp_parse_id_list( $_POST['id'] );
	$messages = array();

	// Use global nonce for bulk actions involving more than one id
	if ( 1 !== count( $ids ) ) {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
			wp_send_json_error( $response );
		}

		foreach ( $ids as $mid ) {
			if ( 'star' === $action ) {
				bp_messages_star_set_action( array(
					'action'     => 'star',
					'message_id' => $mid,
				) );
			} else {
				$thread_id = messages_get_message_thread_id( $mid );

				bp_messages_star_set_action( array(
					'action'    => 'unstar',
					'thread_id' => $thread_id,
					'bulk'      => true
				) );
			}

			$messages[ $mid ] = array(
				'star_link' => bp_get_the_message_star_action_link( array(
					'message_id' => $mid,
					'url_only'  => true,
				) ),
				'is_starred' => 'star' === $action,
			);
		}

	// Use global star nonce for bulk actions involving one id or regular action
	} else {
		$id = reset( $ids );

		if ( empty( $_POST['star_nonce'] ) || ! wp_verify_nonce( $_POST['star_nonce'], 'bp-messages-star-' . $id ) ) {
			wp_send_json_error( $response );
		}

		bp_messages_star_set_action( array(
			'action'     => $action,
			'message_id' => $id,
		) );

		$messages[ $id ] = array(
			'star_link' => bp_get_the_message_star_action_link( array(
				'message_id' => $id,
				'url_only'  => true,
			) ),
			'is_starred' => 'star' === $action,
		);
	}

	if ( 'star' === $action ) {
		$success_message = __( 'Messages successfully starred.', 'buddyboss' );
	} else {
		$success_message = __( 'Messages successfully unstarred.', 'buddyboss' );
	}

	wp_send_json_success( array(
		'feedback' => esc_html( $success_message ),
		'type'     => 'success',
		'messages' => $messages,
	) );
}

/**
 * AJAX mark message as read/unread
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_readunread_thread_messages() {
	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$action = str_replace( 'messages_', '', $_POST['action'] );

	$response = array(
		'feedback' => __( 'There was a problem marking your messages as read. Please try again.', 'buddyboss' ),
		'type'     => 'error',
	);

	if ( 'unread' === $action ) {
		$response = array(
			'feedback' => __( 'There was a problem marking your messages as unread. Please try again.', 'buddyboss' ),
			'type'     => 'error',
		);
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_ids = wp_parse_id_list( $_POST['id'] );

	$response['messages'] = array();

	if ( 'unread' === $action ) {
		$response['feedback'] = __( 'Messages marked as unread.', 'buddyboss' );
	} else {
		$response['feedback'] = __( 'Messages marked as read.', 'buddyboss' );
	}

	foreach ( $thread_ids as $thread_id ) {
		if ( ! messages_check_thread_access( $thread_id ) && ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error( $response );
		}

		if ( 'unread' === $action ) {
			// Mark unread
			messages_mark_thread_unread( $thread_id );
		} else {
			// Mark read
			messages_mark_thread_read( $thread_id );
		}

		$response['messages'][ $thread_id ] = array(
			'unread' => 'unread' === $action,
		);
	}

	$response['type'] = 'success';

	wp_send_json_success( $response );
}

/**
 * AJAX dismiss sitewide notice.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_dismiss_sitewide_notice() {
	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$response = array(
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'There was a problem dismissing the notice. Please try again.', 'buddyboss' ) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	// Check capability.
	if (
		! is_user_logged_in()
		// || ! bp_core_can_edit_settings()
	) {
		wp_send_json_error( $response );
	}

	// Mark the active notice as closed.
	$notice = BP_Messages_Notice::get_active();

	if ( ! empty( $notice->id ) ) {
		$user_id = bp_loggedin_user_id();

		$closed_notices = bp_get_user_meta( $user_id, 'closed_notices', true );

		if ( empty( $closed_notices ) ) {
			$closed_notices = array();
		}

		// Add the notice to the array of the user's closed notices.
		$closed_notices[] = (int) $notice->id;
		bp_update_user_meta( $user_id, 'closed_notices', array_map( 'absint', array_unique( $closed_notices ) ) );

		wp_send_json_success( array(
			'feedback' => '<div class="bp-feedback info"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'Sitewide notice dismissed', 'buddyboss' ) . '</p></div>',
			'type'     => 'success',
		) );
	}
}

/**
 * AJAX load recipient list.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_dsearch_recipients() {
	if ( empty( $_GET['action'] ) ) {
		wp_send_json_error();
	}

	$response = array(
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'There was a problem loading recipients. Please try again.', 'buddyboss' ) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'messages_load_recipient' ) ) {
		wp_send_json_error( $response );
	}

	add_filter( 'bp_members_suggestions_query_args', 'bp_nouveau_ajax_search_recipients_exclude_current' );

	$results = bp_core_get_suggestions( [
		'term' => sanitize_text_field( $_GET['term'] ),
		'type' => 'members',
	] );

	$results = apply_filters( 'bp_members_suggestions_results', $results );

	wp_send_json_success( [
		'results' => array_map( function($result) {
			return [
				'id' => "@{$result->ID}",
				'text' => $result->name
			];
		}, $results)
	] );
}

/**
 * Exclude logged in member from recipients list.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_search_recipients_exclude_current( $user_query ) {
	if ( isset( $user_query['exclude'] ) && ! $user_query['exclude'] ) {
		$user_query['exclude'] = [];
	}

	$user_query['exclude'][] = get_current_user_id();

	return $user_query;
}

/**
 * Exclude members from messages suggestions list if require users to be connected before they can message each other
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $results
 *
 * @return array
 */
function bp_nouveau_ajax_search_recipients_exclude_non_friend( $results ) {

	if ( true === bp_force_friendship_to_message() ) {
		$new_users = array();
		foreach ( $results as $user ) {
			$member_friend_status = friends_check_friendship_status( $user->user_id, bp_loggedin_user_id() );
			if ( 'is_friend' === $member_friend_status ) {
				$new_users[] = $user;
			}
		}
		return $new_users;
	}
	return $results;
}

add_filter( 'bp_members_suggestions_results', 'bp_nouveau_ajax_search_recipients_exclude_non_friend' );
