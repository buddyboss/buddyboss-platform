<?php
/**
 * Messages Ajax functions
 *
 * @since   BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	function () {

		$ajax_actions = array(
			array(
				'messages_send_message' => array(
					'function' => 'bp_nouveau_ajax_messages_send_message',
					'nopriv'   => false,
				),
			),
			array(
				'messages_send_reply' => array(
					'function' => 'bp_nouveau_ajax_messages_send_reply',
					'nopriv'   => false,
				),
			),
			array(
				'messages_get_user_message_threads' => array(
					'function' => 'bp_nouveau_ajax_get_user_message_threads',
					'nopriv'   => false,
				),
			),
			array(
				'messages_thread_read' => array(
					'function' => 'bp_nouveau_ajax_messages_thread_read',
					'nopriv'   => false,
				),
			),
			array(
				'messages_get_thread_messages' => array(
					'function' => 'bp_nouveau_ajax_get_thread_messages',
					'nopriv'   => false,
				),
			),
			array(
				'messages_delete' => array(
					'function' => 'bp_nouveau_ajax_delete_thread_messages',
					'nopriv'   => false,
				),
			),
			array(
				'messages_delete_thread' => array(
					'function' => 'bp_nouveau_ajax_delete_thread',
					'nopriv'   => false,
				),
			),
			array(
				'messages_hide_thread' => array(
					'function' => 'bp_nouveau_ajax_hide_thread',
					'nopriv'   => false,
				),
			),
			array(
				'messages_unhide_thread' => array(
					'function' => 'bp_nouveau_ajax_unhide_thread',
					'nopriv'   => false,
				),
			),
			array(
				'messages_unstar' => array(
					'function' => 'bp_nouveau_ajax_star_thread_messages',
					'nopriv'   => false,
				),
			),
			array(
				'messages_star' => array(
					'function' => 'bp_nouveau_ajax_star_thread_messages',
					'nopriv'   => false,
				),
			),
			array(
				'messages_unread' => array(
					'function' => 'bp_nouveau_ajax_readunread_thread_messages',
					'nopriv'   => false,
				),
			),
			array(
				'messages_read' => array(
					'function' => 'bp_nouveau_ajax_readunread_thread_messages',
					'nopriv'   => false,
				),
			),
			array(
				'messages_dismiss_sitewide_notice' => array(
					'function' => 'bp_nouveau_ajax_dismiss_sitewide_notice',
					'nopriv'   => false,
				),
			),
			array(
				'messages_search_recipients' => array(
					'function' => 'bp_nouveau_ajax_dsearch_recipients',
					'nopriv'   => false,
				),
			),
			array(
				'messages_recipient_list_for_blocks' => array(
					'function' => 'bb_nouveau_ajax_recipient_list_for_blocks',
					'nopriv'   => false,
				),
			),
			array(
				'messages_moderated_recipient_list' => array(
					'function' => 'bb_nouveau_ajax_moderated_recipient_list',
					'nopriv'   => false,
				),
			),
			array(
				'messages_left_join_members_list' => array(
					'function' => 'bb_nouveau_ajax_left_join_members_list',
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

	// Verify nonce.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
		wp_send_json_error( $response );
	}

	$content = filter_input( INPUT_POST, 'message_content', FILTER_DEFAULT );

	/**
	 * Filter to validate message content.
	 *
	 * @param bool   $validated_content True if message is valid, false otherwise.
	 * @param string $content           Content of the message.
	 * @param array  $_POST             POST Request Object.
	 *
	 * @return bool True if message is valid, false otherwise.
	 */
	$validated_content = (bool) apply_filters( 'bp_messages_message_validated_content', ! empty( $content ) && strlen( trim( html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) ), $content, $_POST );

	if ( ! $validated_content ) {
		$response['feedback'] = __( 'Your message was not sent. Please enter some content.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	// Validate recipients.
	if ( empty( $_POST['send_to'] ) || ! is_array( $_POST['send_to'] ) ) {
		$response['feedback'] = __( 'Please add at least one recipient.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	// Trim @ from usernames.
	/**
	 * Filters the results of trimming of `@` characters from usernames for who is set to receive a message.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value Array of un-trimmed usernames submitted.
	 *
	 * @param array $value Array of trimmed usernames.
	 */
	$recipients = apply_filters(
		'bp_messages_recipients',
		array_map(
			function ( $username ) {
				return trim( $username, '@' );
			},
			$_POST['send_to']
		)
	);

	$previous_threads = bb_messages_is_thread_exists_by_recipients( $recipients );
	if ( ! empty( $previous_threads ) ) {
		$current_thread = current( $previous_threads );

		if ( ! empty( $current_thread ) ) {
			$is_thread_archived = messages_is_valid_archived_thread( $current_thread->thread_id, bp_loggedin_user_id() );

			if ( $is_thread_archived ) {
				$response['feedback'] = __( 'You can’t send new messages in conversations you’ve archived.', 'buddyboss' );
				wp_send_json_error( $response );
			}
		}
	}

	// Attempt to send the message.
	$send = messages_new_message(
		array(
			'recipients'   => $recipients,
			'subject'      => wp_trim_words( $_POST['subject'], messages_get_default_subject_length() ),
			'content'      => $_POST['message_content'],
			'error_type'   => 'wp_error',
			'mark_visible' => true,
		)
	);

	// Send the message.
	if ( true === is_int( $send ) ) {
		$response              = array();
		$get_thread_recipients = array();

		$admins = array_map(
			'intval',
			get_users(
				array(
					'role'   => 'administrator',
					'fields' => 'ID',
				)
			)
		);

		if ( bp_has_message_threads( array( 'include' => $send ) ) ) {

			while ( bp_message_threads() ) {
				bp_message_thread();

				$last_message_id = (int) $messages_template->thread->last_message_id;
				$is_group_thread = 0;
				$first_message   = BP_Messages_Thread::get_first_message( bp_get_message_thread_id() );
				if ( isset( $first_message ) && isset( $first_message->id ) ) {
					$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
					$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
					$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
					$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

					if ( 'group' === $message_from && bp_get_message_thread_id() === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
						$is_group_thread = 1;
					}
				}

				$check_recipients = (array) $messages_template->thread->recipients;
				$recipients_ids   = wp_list_pluck( $check_recipients, 'user_id' );
				$can_message      = false;
				if (
					bp_is_active( 'messages' ) &&
					bb_messages_user_can_send_message(
						array(
							'sender_id'     => bp_loggedin_user_id(),
							'recipients_id' => $recipients_ids,
							'thread_id'     => bp_get_message_thread_id(),
						)
					)
				) {
					$can_message = true;
				}

				$response = array(
					'id'                              => bp_get_message_thread_id(),
					'can_user_send_message_in_thread' => $can_message,
					'message_id'                      => (int) $last_message_id,
					'subject'                         => wp_strip_all_tags( bp_get_message_thread_subject() ),
					'excerpt'                         => wp_strip_all_tags( bp_get_message_thread_excerpt() ),
					'content'                         => do_shortcode( bp_get_message_thread_content() ),
					'unread'                          => bp_message_thread_has_unread(),
					'sender_name'                     => bp_core_get_user_displayname( $messages_template->thread->last_sender_id ),
					'sender_is_you'                   => (int) bp_loggedin_user_id() === (int) $messages_template->thread->last_sender_id,
					'sender_link'                     => bp_core_get_userlink( $messages_template->thread->last_sender_id, false, true ),
					'sender_avatar'                   => esc_url(
						bp_core_fetch_avatar(
							array(
								'item_id' => $messages_template->thread->last_sender_id,
								'object'  => 'user',
								'type'    => 'thumb',
								'width'   => BP_AVATAR_THUMB_WIDTH,
								'height'  => BP_AVATAR_THUMB_HEIGHT,
								'html'    => false,
							)
						)
					),
					'count'                           => bp_get_message_thread_total_count(),
					'date'                            => strtotime( bp_get_message_thread_last_post_date_raw() ) * 1000,
					'display_date'                    => bp_nouveau_get_message_date( bp_get_message_thread_last_post_date_raw() ),
					'started_date'                    => bp_nouveau_get_message_date( $messages_template->thread->first_message_date, get_option( 'date_format' ) ),
				);

				if ( is_array( $messages_template->thread->recipients ) ) {
					$recipient_index = 0;
					foreach ( $messages_template->thread->recipients as $recipient ) {
						if ( empty( $recipient->is_deleted ) ) {
							$response['recipients'][] = array(
								'avatar'             => esc_url(
									bp_core_fetch_avatar(
										array(
											'item_id' => $recipient->user_id,
											'object'  => 'user',
											'type'    => 'thumb',
											'width'   => BP_AVATAR_THUMB_WIDTH,
											'height'  => BP_AVATAR_THUMB_HEIGHT,
											'html'    => false,
										)
									)
								),
								'user_link'          => bp_core_get_userlink( $recipient->user_id, false, true ),
								'user_name'          => bp_core_get_user_displayname( $recipient->user_id ),
								'is_deleted'         => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
								'is_you'             => bp_loggedin_user_id() === $recipient->user_id,
								'is_user_suspended'  => function_exists( 'bp_moderation_is_user_suspended' ) ? bp_moderation_is_user_suspended( $recipient->user_id ) : false,
								'is_user_blocked'    => function_exists( 'bp_moderation_is_user_blocked' ) ? bp_moderation_is_user_blocked( $recipient->user_id ) : false,
								'is_user_blocked_by' => function_exists( 'bb_moderation_is_user_blocked_by' ) ? bb_moderation_is_user_blocked_by( $recipient->user_id ) : false,
							);

							$response['action_recipients']['members'][ $recipient_index ] = array(
								'avatar'     => esc_url(
									bp_core_fetch_avatar(
										array(
											'item_id' => $recipient->user_id,
											'object'  => 'user',
											'type'    => 'thumb',
											'width'   => BP_AVATAR_THUMB_WIDTH,
											'height'  => BP_AVATAR_THUMB_HEIGHT,
											'html'    => false,
										)
									)
								),
								'user_link'  => bp_core_get_userlink( $recipient->user_id, false, true ),
								'user_name'  => bp_core_get_user_displayname( $recipient->user_id ),
								'is_deleted' => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
								'is_you'     => bp_loggedin_user_id() === $recipient->user_id,
								'id'         => $recipient->user_id,
							);

							if ( bp_is_active( 'moderation' ) ) {
								$response['action_recipients']['members'][ $recipient_index ]['is_user_blocked']    = bp_moderation_is_user_blocked( $recipient->user_id );
								$response['action_recipients']['members'][ $recipient_index ]['can_be_blocked']     = ( ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
								$response['action_recipients']['members'][ $recipient_index ]['is_user_blocked_by'] = bb_moderation_is_user_blocked_by( $recipient->user_id );
								$response['action_recipients']['members'][ $recipient_index ]['is_user_suspended']  = bp_moderation_is_user_suspended( $recipient->user_id );
							}
							$recipient_index++;
						}
					}
				}

				if ( bp_is_active( 'moderation' ) ) {
					$response['is_user_suspended'] = bp_moderation_is_user_suspended( $messages_template->thread->last_sender_id );
					$response['is_user_blocked']   = bp_moderation_is_user_blocked( $messages_template->thread->last_sender_id );
				}

				$response['action_recipients']['count']         = count( $check_recipients );
				$response['action_recipients']['current_count'] = (int) bb_messages_recipients_per_page();
				$response['action_recipients']['per_page']      = bb_messages_recipients_per_page();
				$response['action_recipients']['total_pages']   = ceil( (int) count( $check_recipients ) / (int) bb_messages_recipients_per_page() );

				if ( bp_is_active( 'messages', 'star' ) ) {
					$star_link = bp_get_the_message_star_action_link(
						array(
							'thread_id' => bp_get_message_thread_id(),
							'url_only'  => true,
						)
					);

					$response['star_link'] = $star_link;

					$star_link_data         = explode( '/', $star_link );
					$response['is_starred'] = array_search( 'unstar', $star_link_data, true );

					// Defaults to last.
					$sm_id = $last_message_id;

					if ( $response['is_starred'] ) {
						$sm_id = (int) $star_link_data[ $response['is_starred'] + 1 ];
					}

					$response['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . $sm_id );
					$response['starred_id'] = $sm_id;
				}

				$thread_extra_content = bp_nouveau_messages_catch_hook_content(
					array(
						'inboxListItem' => 'bp_messages_inbox_list_item',
						'threadOptions' => 'bp_messages_thread_options',
					)
				);

				if ( array_filter( $thread_extra_content ) ) {
					$response = array_merge( $response, $thread_extra_content );
				}

				$response['avatars'] = bp_messages_get_avatars( bp_get_message_thread_id(), bp_loggedin_user_id() );

				$get_thread_recipients = $messages_template->thread->recipients;
			}
		}

		if ( empty( $response ) ) {
			$response = array( 'id' => $send );
		}

		$inbox_unread_cnt = apply_filters( 'thread_recipient_inbox_unread_counts', array(), $get_thread_recipients );

		$response = apply_filters(
			'bb_nouveau_ajax_messages_send_message_success_response',
			array(
				'feedback'                      => __( 'Message successfully sent.', 'buddyboss' ),
				'type'                          => 'success',
				'thread'                        => $response,
				'recipient_inbox_unread_counts' => $inbox_unread_cnt,
				'thread_id'                     => $response['id'],
				'hash'                          => $response['message_id'],
			)
		);

		wp_send_json_success( $response );

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

	$hash    = ! empty( $_POST['hash'] ) ? wp_unslash( $_POST['hash'] ) : '';
	$content = filter_input( INPUT_POST, 'content', FILTER_DEFAULT );

	$response = array(
		'feedback' => __( 'There was a problem sending your reply. Please try again.', 'buddyboss' ),
		'type'     => 'error',
		'hash'     => $hash,
	);

	// Verify nonce.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['thread_id'] ) ) {
		$response['feedback'] = __( 'Please provide thread id.', 'buddyboss' );
		$response['hash']     = $hash;

		wp_send_json_error( $response );
	}

	/**
	 * Filter to validate message content.
	 *
	 * @param bool   $validated_content True if message is valid, false otherwise.
	 * @param string $content           Content of the message.
	 * @param array  $_POST             POST Request Object.
	 *
	 * @return bool True if message is valid, false otherwise.
	 */
	$validated_content = (bool) apply_filters( 'bp_messages_message_validated_content', ! empty( $content ) && strlen( trim( html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) ), $content, $_POST );

	if ( ! $validated_content ) {
		$response['feedback'] = __( 'Please add some content to your message.', 'buddyboss' );
		$response['hash']     = $hash;

		wp_send_json_error( $response );
	}

	$thread_id = (int) $_POST['thread_id'];

	if ( ! bp_current_user_can( 'bp_moderate' ) && ( ! messages_is_valid_thread( $thread_id ) || ! messages_check_thread_access( $thread_id ) ) ) {
		wp_send_json_error( $response );
	}

	if ( ! empty( $_POST['media'] ) ) {
		$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
		if ( ! $can_send_media ) {
			$response['feedback'] = __( 'You don\'t have access to send the media. ', 'buddyboss' );
			$response['hash']     = $hash;
			wp_send_json_error( $response );
		}
	}

	if ( ! empty( $_POST['document'] ) ) {
		$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
		if ( ! $can_send_document ) {
			$response['feedback'] = __( 'You don\'t have access to send the media. ', 'buddyboss' );
			$response['hash']     = $hash;
			wp_send_json_error( $response );
		}
	}

	if ( ! empty( $_POST['video'] ) ) {
		$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
		if ( ! $can_send_video ) {
			$response['feedback'] = __( 'You don\'t have access to send the media. ', 'buddyboss' );
			$response['hash']     = $hash;
			wp_send_json_error( $response );
		}
	}

	if ( ! empty( $_POST['gif_data'] ) ) {
		$can_send_document = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
		if ( ! $can_send_document ) {
			$response['feedback'] = __( 'You don\'t have access to send the gif. ', 'buddyboss' );
			$response['hash']     = $hash;
			wp_send_json_error( $response );
		}
	}

	// Find the thread is group or not.
	$group         = '';
	$first_message = BP_Messages_Thread::get_first_message( $thread_id );
	$group_id      = bp_messages_get_meta( $first_message->id, 'group_id', true ); // group id.
	$message_users = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
	$message_type  = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.

	if ( ! empty( $group_id ) && 'open' === $message_type && 'all' === $message_users ) {
		$group = groups_get_group( $group_id );
	}

	$date_sent = bp_core_current_time();

	// Check the sent_at param is requested or not.
	$send_at = ! empty( $_POST['send_at'] ) ? sanitize_text_field( wp_unslash( $_POST['send_at'] ) ) : '';

	if ( empty( $group ) ) {
		$new_reply = messages_new_message(
			array(
				'thread_id'    => $thread_id,
				'subject'      => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
				'content'      => $_POST['content'],
				'date_sent'    => $date_sent,
				'mark_visible' => false,
				'error_type'   => 'wp_error',
				'return'       => 'id',
				'send_at'      => $send_at,
			)
		);
	} else {
		$new_reply = bp_groups_messages_new_message(
			array(
				'thread_id'    => $thread_id,
				'subject'      => false,
				'content'      => $_POST['content'],
				'date_sent'    => $date_sent,
				'mark_visible' => false,
				'error_type'   => 'wp_error',
				'return'       => 'id',
				'send_at'      => $send_at,
			)
		);
	}

	if ( is_wp_error( $new_reply ) ) {
		$response['feedback'] = $new_reply->get_error_message();
		wp_send_json_error( $response );
	}

	// Send the reply.
	if ( empty( $new_reply ) ) {
		wp_send_json_error( $response );
	}

	// Get the message by pretending we're in the message loop.
	global $thread_template, $media_template, $document_template, $video_template;

	$bp           = buddypress();
	$reset_action = $bp->current_action;

	// Override bp_current_action().
	$bp->current_action = 'view';

	bp_thread_has_messages(
		array(
			'thread_id' => $thread_id,
			'before'    => $date_sent,
		)
	);

	$messages = BP_Messages_Message::get(
		array(
			'include'         => array( $new_reply ),
			'include_threads' => array( $thread_id ),
			'per_page'        => 1,
		)
	);

	// Set current message to current key.
	$thread_template->current_message = - 1;

	// Now manually iterate message like we're in the loop.
	bp_thread_the_message();

	// Manually call oEmbed
	// this is needed because we're not at the beginning of the loop.
	bp_messages_embed();

	if ( ! empty( $messages ) && ! empty( $messages['messages'] ) ) {
		$thread_template->message = current( $messages['messages'] );
	}

	$message_response = bb_get_message_response_object( $thread_template->message );

	$message_response['started_date_mysql'] = $thread_template->thread->first_message_date;

	// Clean up the loop.
	bp_thread_messages();

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	$response = apply_filters(
		'bb_nouveau_ajax_messages_send_reply_success',
		$message_response
	);

	wp_send_json_success( $response );

}

/**
 * AJAX get all user message threads.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_get_user_message_threads() {
	global $messages_template, $wpdb;

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'Unauthorized request.', 'buddyboss' ),
				'type'     => 'error',
			)
		);
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

	// add_filter( 'bb_messages_recipients_per_page', 'bb_get_user_message_recipients' );

	// Simulate the loop.
	if ( ! bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) {
		// Remove the bp_current_action() override.
		$bp->current_action = $reset_action;

		remove_filter( 'bb_messages_recipients_per_page', 'bb_get_user_message_recipients' );

		wp_send_json_error(
			array(
				'feedback' => __( 'Sorry, no messages were found.', 'buddyboss' ),
				'type'     => 'info',
			)
		);
	}

	// remove_filter( 'bb_messages_recipients_per_page', 'bb_get_user_message_recipients' );

	// remove the message thread filter.
	if ( 'starred' === $bp->current_action ) {
		remove_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
	}

	$threads       = new stdClass();
	$threads->meta = array(
		'total_page' => ceil( (int) $messages_template->total_thread_count / (int) $messages_template->pag_num ),
		'page'       => $messages_template->pag_page,
	);

	$threads->threads = array();
	$i                = 0;
	$content          = '';

	while ( bp_message_threads() ) :
		bp_message_thread();

		$bp_get_message_thread_id = bp_get_message_thread_id();

		$last_message_id           = (int) $messages_template->thread->last_message_id;
		$group_id                  = bp_messages_get_meta( $last_message_id, 'group_id', true );
		$group_name                = '';
		$group_avatar              = '';
		$group_link                = '';
		$group_message_users       = '';
		$group_message_type        = '';
		$group_message_thread_type = '';
		$group_message_fresh       = '';
		$group                     = '';
		$first_message             = '';

		if ( ! empty( $group_id ) ) {
			$group_message_users       = bp_messages_get_meta( $last_message_id, 'group_message_users', true );
			$group_message_type        = bp_messages_get_meta( $last_message_id, 'group_message_type', true );
			$group_message_thread_type = bp_messages_get_meta( $last_message_id, 'group_message_thread_type', true );
			$group_message_fresh       = bp_messages_get_meta( $last_message_id, 'group_message_fresh', true );

			if ( bp_is_active( 'groups' ) ) {
				$group      = groups_get_group( $group_id );
				$group_name = bp_get_group_name( $group );
				if ( empty( $group_name ) ) {
					$group_link = 'javascript:void(0);';
				} else {
					$group_link = bp_get_group_permalink( $group );
				}

				if ( ! bp_disable_group_avatar_uploads() ) {
					$group_avatar = bp_core_fetch_avatar(
						array(
							'item_id'    => $group_id,
							'object'     => 'group',
							'type'       => 'full',
							'avatar_dir' => 'group-avatars',
							'alt'        => sprintf(
								/* translators: group name. */
								__( 'Group logo of %s', 'buddyboss' ),
								$group_name
							),
							'title'      => $group_name,
							'html'       => false,
						)
					);
				} else {
					$group_avatar = bb_get_buddyboss_group_avatar();
				}
			} else {

				$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table             = $prefix . 'bp_groups';
				$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok.
				$group_link               = 'javascript:void(0);';
				$group_avatar             = ! bp_disable_group_avatar_uploads() ? bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) ) : bb_get_buddyboss_group_avatar();
				$legacy_group_avatar_name = '-groupavatar-full';
				$legacy_user_avatar_name  = '-avatar2';

				if ( ! empty( $group_name ) && ! bp_disable_group_avatar_uploads() ) {
					$directory         = 'group-avatars';
					$avatar_size       = '-bpfull';
					$avatar_folder_dir = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
					$avatar_folder_url = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

					$avatar = bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url );
					if ( '' !== $avatar ) {
						$group_avatar = $avatar;
					}
				}
			}

			$group_name = ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name;

		}

		$is_deleted_group = 0;
		if ( ! $group_id ) {
			$first_message = BP_Messages_Thread::get_first_message( $bp_get_message_thread_id );
			$group_id      = ( isset( $first_message->id ) ) ? (int) bp_messages_get_meta( $first_message->id, 'group_id', true ) : 0;

			if ( $group_id ) {

				$group_avatar = '';

				if ( bp_is_active( 'groups' ) ) {
					$group      = empty( $group ) ? groups_get_group( $group_id ) : $group;
					$group_name = bp_get_group_name( $group );
					$group_link = bp_get_group_permalink( $group );

					if ( ! bp_disable_group_avatar_uploads() ) {
						$group_avatar = bp_core_fetch_avatar(
							array(
								'item_id'    => $group_id,
								'object'     => 'group',
								'type'       => 'full',
								'avatar_dir' => 'group-avatars',
								'alt'        => sprintf(
									/* translators: group name. */
									__( 'Group logo of %s', 'buddyboss' ),
									$group_name
								),
								'title'      => $group_name,
								'html'       => false,
							)
						);
					} else {
						$group_avatar = bb_get_buddyboss_group_avatar();
					}
				} else {

					$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
					$groups_table             = $prefix . 'bp_groups';
					$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok.
					$group_link               = 'javascript:void(0);';
					$group_avatar             = ! bp_disable_group_avatar_uploads() ? bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) ) : bb_get_buddyboss_group_avatar();
					$legacy_group_avatar_name = '-groupavatar-full';
					$legacy_user_avatar_name  = '-avatar2';

					if ( ! empty( $group_name ) && ! bp_disable_group_avatar_uploads() ) {
						$directory         = 'group-avatars';
						$avatar_size       = '-bpfull';
						$avatar_folder_dir = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
						$avatar_folder_url = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

						$avatar = bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url );
						if ( '' !== $avatar ) {
							$group_avatar = $avatar;
						}
					}
				}

				$is_deleted_group = ( empty( $group_name ) ) ? 1 : 0;
				$group_name       = ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name;
			}
		}

		$is_group_thread = 0;
		if ( (int) $group_id > 0 ) {

			$first_message           = empty( $first_message ) ? BP_Messages_Thread::get_first_message( $bp_get_message_thread_id ) : $first_message;
			$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
			$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
			$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
			$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
			$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

			if ( 'group' === $message_from && $bp_get_message_thread_id === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
				$is_group_thread = 1;
			}
		}

		if ( ! $last_message_id ) {
			continue;
		}

		$sender_name      = bp_core_get_user_displayname( $messages_template->thread->last_sender_id );
		$check_recipients = (array) $messages_template->thread->recipients;
		$recipients_ids   = wp_list_pluck( $check_recipients, 'user_id' );
		$can_message      = false;
		if (
			bp_is_active( 'messages' ) &&
			bb_messages_user_can_send_message(
				array(
					'sender_id'     => bp_loggedin_user_id(),
					'recipients_id' => $recipients_ids
				)
			)
		) {
			$can_message = true;
		}

		// Check the thread is private or group.
		$is_private_thread = true;
		if ( 2 < $messages_template->thread->total_recipients_count ) {
			$is_private_thread = false;
		}

		$threads->threads[ $i ] = array(
			'id'                              => $bp_get_message_thread_id,
			'message_id'                      => (int) $last_message_id,
			'subject'                         => wp_strip_all_tags( bp_get_message_thread_subject() ),
			'group_avatar'                    => $group_avatar,
			'group_name'                      => html_entity_decode( $group_name, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
			'is_deleted'                      => $is_deleted_group,
			'is_group'                        => ! empty( $group_id ) ? true : false,
			'is_group_thread'                 => $is_group_thread,
			'group_link'                      => $group_link,
			'group_message_users'             => $group_message_users,
			'group_message_type'              => $group_message_type,
			'can_user_send_message_in_thread' => $can_message,
			'group_message_thread_type'       => $group_message_thread_type,
			'group_message_fresh'             => $group_message_fresh,
			'excerpt'                         => wp_trim_words( bp_get_message_thread_excerpt() ),
			'content'                         => do_shortcode( bp_get_message_thread_content() ),
			'unread'                          => bp_message_thread_has_unread(),
			'sender_name'                     => $sender_name,
			'sender_is_you'                   => (int) bp_loggedin_user_id() === (int) $messages_template->thread->last_sender_id,
			'sender_link'                     => bp_core_get_userlink( $messages_template->thread->last_sender_id, false, true ),
			'sender_avatar'                   => esc_url(
				bp_core_fetch_avatar(
					array(
						'item_id' => $messages_template->thread->last_sender_id,
						'object'  => 'user',
						'type'    => 'thumb',
						'width'   => BP_AVATAR_THUMB_WIDTH,
						'height'  => BP_AVATAR_THUMB_HEIGHT,
						'html'    => false,
					)
				)
			),
			'count'                           => bp_get_message_thread_total_count(),
			'date'                            => strtotime( bp_get_message_thread_last_post_date_raw() ) * 1000,
			'display_date'                    => bb_get_thread_sent_date(),
			'started_date'                    => bp_nouveau_get_message_date( $messages_template->thread->first_message_date, get_option( 'date_format' ) ),
			'is_private_thread'               => $is_private_thread,
			'has_media'                       => false,
		);

		if ( (int) bp_action_variable( 0 ) === (int) $bp_get_message_thread_id ) {
			$threads->threads[ $i ]['unread'] = false;
		}

		if ( is_array( $check_recipients ) ) {
			$count  = 1;
			$admins = array_map(
				'intval',
				get_users(
					array(
						'role'   => 'administrator',
						'fields' => 'ID',
					)
				)
			);
			foreach ( $check_recipients as $recipient ) {
				if ( empty( $recipient->is_deleted ) ) {
					$threads->threads[ $i ]['recipients'][ $count ] = array(
						'id'            => $recipient->user_id,
						'avatar'        => esc_url(
							bp_core_fetch_avatar(
								array(
									'item_id' => $recipient->user_id,
									'object'  => 'user',
									'type'    => 'thumb',
									'width'   => BP_AVATAR_THUMB_WIDTH,
									'height'  => BP_AVATAR_THUMB_HEIGHT,
									'html'    => false,
								)
							)
						),
						'user_link'     => bp_core_get_userlink( $recipient->user_id, false, true ),
						'user_name'     => bp_core_get_user_displayname( $recipient->user_id ),
						'is_deleted'    => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
						'is_you'        => bp_loggedin_user_id() === $recipient->user_id,
						'user_presence' => 1 === count( (array) $check_recipients ) ? bb_get_user_presence_html( $recipient->user_id ) : '',
					);

					if ( bp_is_active( 'moderation' ) ) {
						$threads->threads[ $i ]['recipients'][ $count ]['is_user_suspended']  = bp_moderation_is_user_suspended( $recipient->user_id );
						$threads->threads[ $i ]['recipients'][ $count ]['is_user_blocked']    = bp_moderation_is_user_blocked( $recipient->user_id );
						$threads->threads[ $i ]['recipients'][ $count ]['can_be_blocked']     = ( ! in_array( $recipient->user_id, $admins, true ) ) ? true : false;
						$threads->threads[ $i ]['recipients'][ $count ]['is_user_blocked_by'] = bb_moderation_is_user_blocked_by( $recipient->user_id );
						$threads->threads[ $i ]['recipients'][ $count ]['is_user_reported']   = bp_moderation_report_exist( $recipient->user_id, BP_Moderation_Members::$moderation_type_report );
						$threads->threads[ $i ]['recipients'][ $count ]['can_be_report']      = ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_user_can( bp_loggedin_user_id(), BP_Moderation_Members::$moderation_type_report );
						$threads->threads[ $i ]['recipients'][ $count ]['reported_type']      = bp_moderation_get_report_type( BP_Moderation_Members::$moderation_type_report, $recipient->user_id );
					}

					$threads->threads[ $i ]['recipients'][ $count ]['is_thread_archived'] = 0 < $recipient->is_hidden;

					$threads->threads[ $i ]['action_recipients']['members'][ $count ] = array(
						'avatar'     => esc_url(
							bp_core_fetch_avatar(
								array(
									'item_id' => $recipient->user_id,
									'object'  => 'user',
									'type'    => 'thumb',
									'width'   => BP_AVATAR_THUMB_WIDTH,
									'height'  => BP_AVATAR_THUMB_HEIGHT,
									'html'    => false,
								)
							)
						),
						'user_link'  => bp_core_get_userlink( $recipient->user_id, false, true ),
						'user_name'  => bp_core_get_user_displayname( $recipient->user_id ),
						'is_deleted' => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
						'is_you'     => bp_loggedin_user_id() === $recipient->user_id,
						'id'         => $recipient->user_id,
					);

					if ( bp_is_active( 'moderation' ) ) {
						$threads->threads[ $i ]['action_recipients']['members'][ $count ]['is_user_blocked']  = bp_moderation_is_user_blocked( $recipient->user_id );
						$threads->threads[ $i ]['action_recipients']['members'][ $count ]['can_be_blocked']   = ( ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
						$threads->threads[ $i ]['action_recipients']['members'][ $count ]['is_user_reported'] = bp_moderation_report_exist( $recipient->user_id, BP_Moderation_Members::$moderation_type_report );
						$threads->threads[ $i ]['action_recipients']['members'][ $count ]['can_be_report']    = ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_user_can( bp_loggedin_user_id(), BP_Moderation_Members::$moderation_type_report );
						$threads->threads[ $i ]['action_recipients']['members'][ $count ]['reported_type']    = bp_moderation_get_report_type( BP_Moderation_Members::$moderation_type_report, $recipient->user_id );
					}

					$count ++;
				}

				// Check the thread is hidden or not.
				if ( $recipient->is_hidden && bp_loggedin_user_id() === $recipient->user_id ) {
					$is_thread_archived = true;
				}
			}

			$threads->threads[ $i ]['action_recipients']['count']         = count( $check_recipients );
			$threads->threads[ $i ]['action_recipients']['current_count'] = (int) bb_messages_recipients_per_page();
			$threads->threads[ $i ]['action_recipients']['per_page']      = bb_messages_recipients_per_page();
			$threads->threads[ $i ]['action_recipients']['total_pages']   = ceil( (int) count( $check_recipients ) / (int) bb_messages_recipients_per_page() );
		}

		$threads->threads[ $i ]['is_thread_archived'] = false;
		if ( isset( $_POST['thread_type'] ) && 'archived' === $_POST['thread_type'] ) {
			$threads->threads[ $i ]['is_thread_archived'] = true;
		}

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link(
				array(
					'thread_id' => $bp_get_message_thread_id,
					'url_only'  => true,
				)
			);

			$threads->threads[ $i ]['star_link'] = $star_link;

			$star_link_data                       = explode( '/', $star_link );
			$threads->threads[ $i ]['is_starred'] = array_search( 'unstar', $star_link_data, true );

			// Defaults to last.
			$sm_id = $last_message_id;

			if ( $threads->threads[ $i ]['is_starred'] ) {
				$sm_id = (int) $star_link_data[ $threads->threads[ $i ]['is_starred'] + 1 ];
			}

			$threads->threads[ $i ]['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . $sm_id );
			$threads->threads[ $i ]['starred_id'] = $sm_id;
		}

		if ( empty( $threads->threads[ $i ]['excerpt'] ) ) {
			if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
				$media_ids = bp_messages_get_meta( $last_message_id, 'bp_media_ids', true );

				if ( ! empty( $media_ids ) ) {
					$threads->threads[ $i ]['has_media'] = true;
					$media_ids                           = explode( ',', $media_ids );
					if ( count( $media_ids ) < 2 ) {
						$threads->threads[ $i ]['excerpt'] = __( 'Sent a photo', 'buddyboss' );
					} else {
						$threads->threads[ $i ]['excerpt'] = __( 'Sent some photos', 'buddyboss' );
					}
				}
			}

			if ( bp_is_active( 'media' ) && bp_is_messages_video_support_enabled() ) {
				$video_ids = bp_messages_get_meta( $last_message_id, 'bp_video_ids', true );

				if ( ! empty( $video_ids ) ) {
					$threads->threads[ $i ]['has_media'] = true;
					$video_ids                           = explode( ',', $video_ids );
					if ( count( $video_ids ) < 2 ) {
						$threads->threads[ $i ]['excerpt'] = __( 'Sent a video', 'buddyboss' );
					} else {
						$threads->threads[ $i ]['excerpt'] = __( 'Sent some videos', 'buddyboss' );
					}
				}
			}

			if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() ) {
				$document_ids = bp_messages_get_meta( $last_message_id, 'bp_document_ids', true );

				if ( ! empty( $document_ids ) ) {
					$threads->threads[ $i ]['has_media'] = true;
					$document_ids                        = explode( ',', $document_ids );
					if ( count( $document_ids ) < 2 ) {
						$threads->threads[ $i ]['excerpt'] = __( 'Sent a document', 'buddyboss' );
					} else {
						$threads->threads[ $i ]['excerpt'] = __( 'Sent some documents', 'buddyboss' );
					}
				}
			}

			if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) {
				$gif_data = bp_messages_get_meta( $last_message_id, '_gif_data', true );

				if ( ! empty( $gif_data ) ) {
					$threads->threads[ $i ]['has_media'] = true;
					$threads->threads[ $i ]['excerpt']   = __( 'Sent a gif', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'moderation' ) ) {
			$threads->threads[ $i ]['is_user_suspended'] = bp_moderation_is_user_suspended( $messages_template->thread->last_sender_id );
			$threads->threads[ $i ]['is_user_blocked']   = bp_moderation_is_user_blocked( $messages_template->thread->last_sender_id );

			if ( bp_moderation_is_user_suspended( $messages_template->thread->last_sender_id ) ) {
				$threads->threads[ $i ]['excerpt'] = esc_html__( 'Hidden content from suspended member.', 'buddyboss' );
			} elseif ( bp_moderation_is_user_blocked( $messages_template->thread->last_sender_id ) ) {
				$threads->threads[ $i ]['excerpt'] = esc_html__( 'This content has been hidden as you have blocked this member.', 'buddyboss' );
			}
		}

		$thread_extra_content = bp_nouveau_messages_catch_hook_content(
			array(
				'inboxListItem' => 'bp_messages_inbox_list_item',
				'threadOptions' => 'bp_messages_thread_options',
			)
		);

		if ( array_filter( $thread_extra_content ) ) {
			$threads->threads[ $i ] = array_merge( $threads->threads[ $i ], $thread_extra_content );
		}

		$threads->threads[ $i ]['is_search'] = ( isset( $_POST ) && isset( $_POST['search_terms'] ) && '' !== trim( $_POST['search_terms'] ) ) ? true : false;
		$threads->threads[ $i ]['avatars']   = bp_messages_get_avatars( $bp_get_message_thread_id, bp_loggedin_user_id() );

		$i += 1;
	endwhile;

	$threads->threads = array_filter( $threads->threads );

	$extra_content = bp_nouveau_messages_catch_hook_content(
		array(
			'beforeLoop' => 'bp_before_member_messages_loop',
			'afterLoop'  => 'bp_after_member_messages_loop',
		)
	);

	if ( array_filter( $extra_content ) ) {
		$threads->extraContent = $extra_content; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	// Return the successful reply.
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

	// Mark thread as read.
	messages_mark_thread_read( $thread_id );

	// Mark latest message as read.
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'new_message' );
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'bb_messages_new' );
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'bb_groups_new_message' );
	}

	wp_send_json_success();
}

/**
 * AJAX get messages for each thread.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_get_thread_messages() {
	global $thread_template, $media_template, $document_template, $wpdb, $bp;

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'Unauthorized request.', 'buddyboss' ),
				'type'     => 'error',
			)
		);
	}

	$response = array(
		'feedback' => __( 'Sorry, no messages were found.', 'buddyboss' ),
		'type'     => 'info',
	);

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_id = (int) $_POST['id'];

	// This is removed because if we hide the conversation and search the messages from the search then it will automatically unhide and notice will be removed.
	// Mark thread active if it's in hidden mode.
	// $result = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 0, $thread_id, bp_loggedin_user_id() ) );

	$post = $_POST;

	$thread_id = apply_filters( 'bb_messages_validate_thread', $thread_id );
	if ( empty( $thread_id ) ) {
		wp_send_json_error( $response );
	}

	$thread = bp_nouveau_get_thread_messages( $thread_id, $post );

	wp_send_json_success( $thread );
}

/**
 * AJAX delete logged in user entire messages of given thread.
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

	$inbox_unread_cnt = array(
		'user_id'            => bp_loggedin_user_id(),
		'inbox_unread_count' => messages_get_unread_count( bp_loggedin_user_id() ),
	);

	BP_Messages_Thread::$noCache = true;

	wp_send_json_success(
		array(
			'id'                            => $thread_id,
			'type'                          => 'success',
			'messages'                      => __( 'Messages successfully deleted.', 'buddyboss' ),
			'messages_count'                => bp_get_message_thread_total_count( $thread_id ),
			'recipient_inbox_unread_counts' => $inbox_unread_cnt,
			'thread_exists'                 => messages_is_valid_thread( $thread_id ),
		)
	);

}

/**
 * AJAX delete entire thread.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_delete_thread() {

	global $wpdb, $bp;

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

		// Get the message ids in order to pass to the action.
		$messages = BP_Messages_Message::get(
			array(
				'fields'          => 'ids',
				'include_threads' => array( $thread_id ),
				'order'           => 'DESC',
				'per_page'        => - 1,
				'orderby'         => 'id',
			)
		);

		$message_ids = ( isset( $messages['messages'] ) && is_array( $messages['messages'] ) ) ? $messages['messages'] : array();

		/**
		 * Fires before an entire message thread is deleted.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param int   $thread_id     ID of the thread being deleted.
		 * @param array $message_ids   IDs of messages being deleted.
		 * @param bool  $thread_delete True entire thread will be deleted.
		 */
		do_action( 'bp_messages_thread_before_delete', $thread_id, $message_ids, true );

		// Removed the thread id from the group meta.
		if ( bp_is_active( 'groups' ) && function_exists( 'bp_disable_group_messages' ) && true === bp_disable_group_messages() ) {
			// Get the group id from the first message.
			$first_message    = BP_Messages_Thread::get_first_message( (int) $thread_id );
			$message_group_id = (int) bp_messages_get_meta( $first_message->id, 'group_id', true ); // group id.
			if ( $message_group_id > 0 ) {
				$group_thread = (int) groups_get_groupmeta( $message_group_id, 'group_message_thread' );
				if ( $group_thread > 0 && $group_thread === (int) $thread_id ) {
					groups_update_groupmeta( $message_group_id, 'group_message_thread', '' );
				}
			}
		}

		if ( bp_is_active( 'notifications' ) ) {
			// Delete Message Notifications.
			bp_messages_message_delete_notifications( $thread_id, $message_ids );
		}

		$thread_recipients = BP_Messages_Thread::get_recipients_for_thread( (int) $thread_id );

		// Delete thread messages.
		$query = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $query ); // db call ok; no-cache ok.

		// Delete messages meta.
		$query_meta = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_meta} WHERE message_id IN(%s)", implode( ',', $message_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $query_meta ); // db call ok; no-cache ok.

		// Delete thread.
		$query_recipients = $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $query_recipients ); // db call ok; no-cache ok.

		/**
		 * Fires after message thread deleted.
		 *
		 * @since BuddyBoss 1.5.6
		 */
		do_action( 'bp_messages_message_delete_thread', $thread_id, $thread_recipients );

		/**
		 * Fires before an entire message thread is deleted.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @param array $message_ids   IDs of messages being deleted.
		 * @param int   $user_id       ID of the user the threads were deleted for.
		 * @param bool  $thread_delete True entire thread will be deleted.
		 *
		 * @param int   $thread_id     ID of the thread being deleted.
		 */
		do_action( 'bp_messages_thread_after_delete', $thread_id, $message_ids, bp_loggedin_user_id(), true );
	}

	wp_send_json_success(
		array(
			'type'     => 'success',
			'messages' => 'Thread successfully deleted.',
		)
	);

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

	// Use global nonce for bulk actions involving more than one id.
	if ( 1 !== count( $ids ) ) {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
			wp_send_json_error( $response );
		}

		foreach ( $ids as $mid ) {
			if ( 'star' === $action ) {
				bp_messages_star_set_action(
					array(
						'action'     => 'star',
						'message_id' => $mid,
					)
				);
			} else {
				$thread_id = messages_get_message_thread_id( $mid );

				bp_messages_star_set_action(
					array(
						'action'    => 'unstar',
						'thread_id' => $thread_id,
						'bulk'      => true,
					)
				);
			}

			$messages[ $mid ] = array(
				'star_link'  => bp_get_the_message_star_action_link(
					array(
						'message_id' => $mid,
						'url_only'   => true,
					)
				),
				'is_starred' => 'star' === $action,
			);
		}

		// Use global star nonce for bulk actions involving one id or regular action
	} else {
		$id = reset( $ids );

		if ( empty( $_POST['star_nonce'] ) || ! wp_verify_nonce( $_POST['star_nonce'], 'bp-messages-star-' . $id ) ) {
			wp_send_json_error( $response );
		}

		bp_messages_star_set_action(
			array(
				'action'     => $action,
				'message_id' => $id,
			)
		);

		$messages[ $id ] = array(
			'star_link'  => bp_get_the_message_star_action_link(
				array(
					'message_id' => $id,
					'url_only'   => true,
				)
			),
			'is_starred' => 'star' === $action,
		);
	}

	if ( 'star' === $action ) {
		$success_message = __( 'Messages successfully starred.', 'buddyboss' );
	} else {
		$success_message = __( 'Messages successfully unstarred.', 'buddyboss' );
	}

	wp_send_json_success(
		array(
			'feedback' => esc_html( $success_message ),
			'type'     => 'success',
			'messages' => $messages,
		)
	);
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
			// Mark unread.
			messages_mark_thread_unread( $thread_id );
		} else {
			// Mark read.
			messages_mark_thread_read( $thread_id );
		}

		$response['messages'][ $thread_id ] = array(
			'unread' => 'unread' === $action,
		);
	}

	$response['type'] = 'success';
	$response['ids']  = $thread_ids;

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
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __(
			'There was a problem dismissing the notice. Please try again.',
			'buddyboss'
		) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	// Check capability.
	if ( ! is_user_logged_in() // || ! bp_core_can_edit_settings()
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

		wp_send_json_success(
			array(
				'feedback' => '<div class="bp-feedback info"><span class="bp-icon" aria-hidden="true"></span><p>' . __(
					'Sitewide notice dismissed',
					'buddyboss'
				) . '</p></div>',
				'type'     => 'success',
			)
		);
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
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __(
			'There was a problem loading recipients. Please try again.',
			'buddyboss'
		) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'messages_load_recipient' ) ) {
		wp_send_json_error( $response );
	}

	$exclude_user_ids = array();
	if ( isset( $_GET['except'] ) && ! empty( $_GET['except'] ) ) {
		$exclude          = array_map( 'sanitize_text_field', $_GET['except'] );
		$exclude_user_ids = bb_get_user_id_by_activity_mentionname( $exclude );
	}

	add_filter( 'bp_members_suggestions_query_args', 'bp_nouveau_ajax_search_recipients_exclude_current' );

	if (
		bp_is_active( 'friends' ) &&
		bp_force_friendship_to_message() &&
		empty( bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) )
	) {
		add_filter( 'bp_user_query_uid_clauses', 'bb_messages_update_recipient_user_query_uid_clauses', 9999, 2 );
	}

	$results = bp_core_get_suggestions(
		array(
			'term'            => sanitize_text_field( $_GET['term'] ),
			'type'            => 'members',
			'only_friends'    => false,
			'count_total'     => 'count_query',
			'page'            => $_GET['page'],
			'limit'           => 10,
			'populate_extras' => true,
			'exclude'         => $exclude_user_ids,
		)
	);

	if (
		bp_is_active( 'friends' ) &&
		bp_force_friendship_to_message() &&
		empty( bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) )
	) {
		remove_filter( 'bp_user_query_uid_clauses', 'bb_messages_update_recipient_user_query_uid_clauses', 9999 );
	}

	$results_total = apply_filters( 'bp_members_suggestions_results_total', $results['total'] );
	$results       = apply_filters( 'bp_members_suggestions_results', isset( $results['members'] ) ? $results['members'] : array() );

	wp_send_json_success(
		array(
			'results'     => array_map(
				function ( $result ) {
					return array(
						'id'    => "@{$result->ID}",
						'text'  => $result->name,
						'image' => $result->image,
						'html'  => '<div class="cur"><img class="avatar" src="' . esc_url( $result->image ) . '"><span class="username"><strong>' . $result->name . '</strong></div>',
					);
				},
				$results
			),
			'total_pages' => ceil( $results_total / 10 ),
		)
	);
}

/**
 * Exclude logged in member from recipients list.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_search_recipients_exclude_current( $user_query ) {
	if ( isset( $user_query['exclude'] ) && ! $user_query['exclude'] ) {
		$user_query['exclude'] = array();
	} elseif ( ! empty( $user_query['exclude'] ) ) {
		$user_query['exclude'] = wp_parse_id_list( $user_query['exclude'] );
	}

	$user_query['exclude'][] = get_current_user_id();

	// Avoid duplicate user IDs.
	$user_query['exclude'] = array_unique( $user_query['exclude'] );

	return $user_query;
}

/**
 * Messages for each thread.
 *
 * @since BuddyBoss 1.3.0
 *
 * @param int   $thread_id thread id.
 * @param array $post      $_POST data.
 *
 * @return stdClass|void
 */
function bp_nouveau_get_thread_messages( $thread_id, $post ) {
	global $thread_template, $media_template, $wpdb, $document_template, $video_template;

	if ( ! $thread_id ) {
		return;
	}

	$bp            = buddypress();
	$reset_action  = $bp->current_action;
	$group_id      = 0;
	$login_user_id = bp_loggedin_user_id();

	// Override bp_current_action().
	$bp->current_action = 'view';

	$response = array(
		'feedback' => __( 'Sorry, no messages were found.', 'buddyboss' ),
		'type'     => 'info',
	);

	$response_no_more = array(
		'feedback' => __( 'Sorry, no more messages can be loaded.', 'buddyboss' ),
		'type'     => 'info',
	);

	bp_get_thread(
		array(
			'thread_id'            => $thread_id,
			'exclude_current_user' => true,
		)
	);

	$thread     = new stdClass();
	$recipients = (array) $thread_template->thread->recipients;

	// Strip the sender from the recipient list, and unset them if they are
	// not alone. If they are alone, let them talk to themselves.
	if ( isset( $recipients[ $login_user_id ] ) && ( count( $recipients ) > 1 ) ) {
		unset( $recipients[ $login_user_id ] );
	}

	$check_recipients = $recipients;

	$is_group_message_thread = bb_messages_is_group_thread( bp_get_the_thread_id() );

	// Check recipients if connected or not.
	if ( ! $is_group_message_thread && count( $recipients ) < 2 ) {
		add_filter( 'bp_after_bb_parse_button_args_parse_args', 'bb_messaged_set_friend_button_args' );
		foreach ( $recipients as $recipient ) {
			if (
				$login_user_id !== $recipient->user_id &&
				! bb_messages_user_can_send_message(
					array(
						'sender_id'     => $login_user_id,
						'recipients_id' => $recipient->user_id,
					)
				)
			) {
				if ( count( $recipients ) > 1 ) {
					$thread->feedback_error = array(
						'feedback' => __( 'You must be connected to this member to send them a message.', 'buddyboss' ),
						'type'     => 'notice',
					);
				} else {
					$thread->feedback_error = array(
						'feedback' => sprintf(
							'%1$s %2$s',
							__( 'You must be connected to this member to send them a message.', 'buddyboss' ),
							'<div class="button-wrapper" data-bp-item-id="' . $recipient->user_id . '" data-bp-item-component="members" data-bp-used-to-component="messages">' . bp_get_add_friend_button(
								$recipient->user_id,
								false,
								array(
									'block_self' => false,
									'link_text'  => __(
										'Send Connection Request',
										'buddyboss'
									),
								)
							) . '</div>'
						),
						'type'     => 'notice',
					);
				}
				break;
			}
		}
		remove_filter( 'bp_after_bb_parse_button_args_parse_args', 'bb_messaged_set_friend_button_args' );
	}

	// Check the thread is hide/archived or not.
	$is_thread_archived = messages_is_valid_archived_thread( $thread_id, $login_user_id );

	if ( 0 < $is_thread_archived ) {
		$thread->feedback_error = array(
			'feedback' => sprintf(
				'%1$s %2$s',
				__( 'You can’t send new messages in conversations you’ve archived.', 'buddyboss' ),
				sprintf(
					'<div class="button-wrapper" data-bp-item-id="' . $thread_id . '" data-bp-item-component="messages" data-bp-used-to-component="messages"><div class="archive-button archived generic-button"><a href="#" class="archive-button archived unhide" rel="unhide" data-bp-action="unhide_thread" data-bp-thread-id="' . $thread_id . '">%s</a></div></div>',
					__( 'Unarchive Conversation', 'buddyboss' )
				)
			),
			'type'     => 'notice',
		);
	}

	// Check moderation if user blocked or not for single user thread.
	if ( bp_is_active( 'moderation' ) && ! empty( $recipients ) && 1 === count( $recipients ) ) {
		$recipient_id = current( array_keys( $recipients ) );

		if ( bp_moderation_is_user_suspended( $recipient_id ) ) {
			$thread->feedback_error = array(
				'feedback' => __( 'Unable to send new messages to this member.', 'buddyboss' ),
				'type'     => 'notice',
			);
		} elseif ( bb_moderation_is_user_blocked_by( $recipient_id ) ) {
			$thread->feedback_error = array(
				'feedback' => __( 'Unable to send new messages to this member.', 'buddyboss' ),
				'type'     => 'notice',
			);
		} elseif ( bp_moderation_is_user_blocked( $recipient_id ) ) {
			$thread->feedback_error = array(
				'feedback' => sprintf(
					'%1$s %2$s',
					__( 'You can\'t send messages to members you have blocked.', 'buddyboss' ),
					sprintf(
						'<div class="blocked-button blocked generic-button"><a href="' . esc_url( trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ) . 'blocked-members' ) . '" class="blocked-button blocked add">%s</a></div>',
						__( 'View Blocked Members', 'buddyboss' )
					)
				),
				'type'     => 'notice',
			);
		}
	}

	$last_message_id           = $thread_template->thread->messages[0]->id;
	$bp_get_the_thread_id      = bp_get_the_thread_id();
	$group_name                = '';
	$group_avatar              = '';
	$group_link                = '';
	$group_message_users       = '';
	$group_message_type        = '';
	$group_message_thread_type = '';
	$group_message_fresh       = '';
	$first_message             = BP_Messages_Thread::get_first_message( $bp_get_the_thread_id );
	$group_id                  = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
	$message_from              = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.
	$is_group_message_thread   = bb_messages_is_group_thread( $bp_get_the_thread_id );

	if ( ! $is_group_message_thread ) {
		$thread = bb_user_can_send_messages( $thread, (array) $thread_template->thread->recipients, '' );

		if ( isset( $thread->feedback_error, $thread->feedback_error['from'] ) && ! empty( $thread->feedback_error['from'] ) ) {
			$thread->feedback_error['type'] = 'notice';
		}
	}

	$group_joined_date = '';
	$is_deleted_group  = 0;
	if ( ! empty( $group_id ) ) {
		$group_message_users       = bp_messages_get_meta( $last_message_id, 'group_message_users', true );
		$group_message_type        = bp_messages_get_meta( $last_message_id, 'group_message_type', true );
		$group_message_thread_type = bp_messages_get_meta( $last_message_id, 'group_message_thread_type', true );
		$group_message_fresh       = bp_messages_get_meta( $last_message_id, 'group_message_fresh', true );
		$message_from              = bp_messages_get_meta( $last_message_id, 'message_from', true );

		if ( bp_is_active( 'groups' ) ) {
			$get_group  = groups_get_group( $group_id );
			$group_name = bp_get_group_name( $get_group );
			if ( empty( $group_name ) ) {
				$group_link = 'javascript:void(0);';
			} else {
				$group_link = bp_get_group_permalink( $get_group );
			}
			$group_avatar = bp_core_fetch_avatar(
				array(
					'item_id'    => $group_id,
					'object'     => 'group',
					'type'       => 'full',
					'avatar_dir' => 'group-avatars',
					/* translators: %s: Group Name */
					'alt'        => sprintf( __( 'Group logo of %s', 'buddyboss' ), $group_name ),
					'title'      => $group_name,
					'html'       => false,
				)
			);

			if ( ! empty( $group_name ) ) {
				$current_group_member = new BP_Groups_Member( $login_user_id, $group_id );

				if ( ! empty( $current_group_member->id ) ) {
					$joined_date = groups_get_membermeta( $current_group_member->id, 'joined_date' );
					if ( empty( $joined_date ) ) {
						$joined_date = groups_get_membermeta( $current_group_member->id, 'membership_accept_date' );
					}

					if ( ! empty( $joined_date ) ) {
						$joined_date = bb_get_thread_start_date( $joined_date, true );
					}

					$group_joined_date = $joined_date;
				}
			}
		} else {

			$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
			$groups_table             = $prefix . 'bp_groups';
			$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
			$group_link               = 'javascript:void(0);';
			$group_avatar             = bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) );
			$legacy_group_avatar_name = '-groupavatar-full';
			$legacy_user_avatar_name  = '-avatar2';

			if ( ! empty( $group_name ) ) {
				$group_link        = 'javascript:void(0);';
				$directory         = 'group-avatars';
				$avatar_size       = '-bpfull';
				$avatar_folder_dir = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
				$avatar_folder_url = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

				$avatar = bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url );
				if ( '' !== $avatar ) {
					$group_avatar = $avatar;
				}
			}
		}

		$is_deleted_group = ( empty( $group_name ) ) ? 1 : 0;
		$group_name       = ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name;

	}

	// Simulate the loop.
	$args = array(
		'thread_id'            => $thread_id,
		'per_page'             => isset( $post['per_page'] ) && $post['per_page'] ? $post['per_page'] : 10,
		'before'               => isset( $post['before'] ) && $post['before'] ? $post['before'] : null,
		'exclude_current_user' => true,
	);

	if ( ! bp_thread_has_messages( $args ) ) {
		// Remove the bp_current_action() override.
		$bp->current_action = $reset_action;

		wp_send_json_error( $args['before'] ? $response_no_more : $response );
	}

	if ( ! $group_id ) {
		$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
		$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );

		if ( $group_id ) {
			if ( bp_is_active( 'groups' ) ) {
				$get_group    = groups_get_group( $group_id );
				$group_name   = bp_get_group_name( $get_group );
				$group_link   = bp_get_group_permalink( $get_group );
				$group_avatar = bp_core_fetch_avatar(
					array(
						'item_id'    => $group_id,
						'object'     => 'group',
						'type'       => 'full',
						'avatar_dir' => 'group-avatars',
						/* translators: %s: Group Name */
						'alt'        => sprintf( __( 'Group logo of %s', 'buddyboss' ), $group_name ),
						'title'      => $group_name,
						'html'       => false,
					)
				);
			} else {

				$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table             = $prefix . 'bp_groups';
				$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$group_link               = 'javascript:void(0);';
				$group_avatar             = bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) );
				$legacy_group_avatar_name = '-groupavatar-full';
				$legacy_user_avatar_name  = '-avatar2';

				if ( ! empty( $group_name ) ) {
					$group_link        = 'javascript:void(0);';
					$directory         = 'group-avatars';
					$avatar_size       = '-bpfull';
					$avatar_folder_dir = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
					$avatar_folder_url = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

					$avatar = bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url );
					if ( '' !== $avatar ) {
						$group_avatar = $avatar;
					}
				}
			}

			$group_name = ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name;
		}
	}

	$is_group_thread = 0;
	if ( (int) $group_id > 0 ) {
		$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
		$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
		$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
		$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

		if ( 'group' === $message_from && $bp_get_the_thread_id === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
			$is_group_thread = 1;

			if ( isset( $thread->feedback_erro ) && 'notice' !== $thread->feedback_error['type'] ) {
				unset( $thread->feedback_error );
			}
		}
	}

	$subject_deleted_text = apply_filters( 'delete_user_message_subject_text', __( 'Deleted', 'buddyboss' ) );
	$participated         = BP_Messages_Message::get(
		array(
			'fields'          => 'ids',
			'include_threads' => array( $thread_template->thread->thread_id ),
			'user_id'         => $login_user_id,
			'subject'         => $subject_deleted_text,
			'orderby'         => 'id',
			'per_page'        => - 1,
		)
	);

	$all_recipients = $thread_template->thread->get_recipients();

	$is_participated = ( ! empty( $participated['messages'] ) ? $participated['messages'] : array() );

	$thread->thread = array(
		'id'                        => $bp_get_the_thread_id,
		'subject'                   => wp_strip_all_tags( bp_get_the_thread_subject() ),
		'started_date'              => bb_get_thread_start_date( $thread_template->thread->first_message_date, false ),
		'started_date_mysql'        => $thread_template->thread->first_message_date,
		'group_id'                  => $group_id,
		'group_name'                => html_entity_decode( ucwords( $group_name ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
		'is_group_thread'           => $is_group_thread,
		'is_deleted'                => $is_deleted_group,
		'group_avatar'              => $group_avatar,
		'group_link'                => $group_link,
		'group_message_users'       => $group_message_users,
		'group_message_type'        => $group_message_type,
		'group_message_thread_type' => $group_message_thread_type,
		'group_message_fresh'       => $group_message_fresh,
		'message_from'              => $message_from,
		'is_participated'           => empty( $is_participated ) ? 0 : 1,
		'avatars'                   => bp_messages_get_avatars( $bp_get_the_thread_id, bp_loggedin_user_id() ),
		'is_thread_archived'        => $is_thread_archived,
		'group_joined_date'         => $group_joined_date,
	);

	if ( is_array( $thread_template->thread->recipients ) ) {

		// Get the total number of recipients in the current thread.
		$recipients_count               = bb_get_thread_total_recipients_count();
		$count                          = 1;
		$admins                         = function_exists( 'bb_get_all_admin_users' ) ? bb_get_all_admin_users() : '';
		$bp_force_friendship_to_message = bp_force_friendship_to_message();

		foreach ( $thread_template->thread->recipients as $recipient ) {

			if ( empty( $recipient->is_deleted ) ) {
				$thread->thread['recipients']['members'][ $count ] = array(
					'avatar'        => esc_url(
						bp_core_fetch_avatar(
							array(
								'item_id' => $recipient->user_id,
								'object'  => 'user',
								'type'    => 'thumb',
								'width'   => BP_AVATAR_THUMB_WIDTH,
								'height'  => BP_AVATAR_THUMB_HEIGHT,
								'html'    => false,
							)
						)
					),
					'user_link'     => bp_core_get_userlink( $recipient->user_id, false, true ),
					'user_name'     => bp_core_get_user_displayname( $recipient->user_id ),
					'is_deleted'    => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
					'is_you'        => $login_user_id === $recipient->user_id,
					'id'            => $recipient->user_id,
					'user_presence' => 1 === count( (array) $thread_template->thread->recipients ) ? bb_get_user_presence_html( $recipient->user_id ) : '',
				);

				if ( bp_is_active( 'moderation' ) ) {
					$thread->thread['recipients']['members'][ $count ]['is_user_blocked']    = bp_moderation_is_user_blocked( $recipient->user_id );
					$thread->thread['recipients']['members'][ $count ]['can_be_blocked']     = ( ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
					$thread->thread['recipients']['members'][ $count ]['is_user_suspended']  = bp_moderation_is_user_suspended( $recipient->user_id );
					$thread->thread['recipients']['members'][ $count ]['is_user_blocked_by'] = bb_moderation_is_user_blocked_by( $recipient->user_id );
					$thread->thread['recipients']['members'][ $count ]['is_user_reported']   = bp_moderation_report_exist( $recipient->user_id, BP_Moderation_Members::$moderation_type_report );
					$thread->thread['recipients']['members'][ $count ]['can_be_report']      = ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_user_can( bp_loggedin_user_id(), BP_Moderation_Members::$moderation_type_report );
					$thread->thread['recipients']['members'][ $count ]['reported_type']      = bp_moderation_get_report_type( BP_Moderation_Members::$moderation_type_report, $recipient->user_id );
				}

				$count ++;
			}
		}

		$thread->thread['recipients']['count']         = $recipients_count;
		$thread->thread['recipients']['current_count'] = count( $thread->thread['recipients']['members'] );
		$thread->thread['recipients']['per_page']      = bb_messages_recipients_per_page();
		$thread->thread['recipients']['total_pages']   = ceil( (int) $recipients_count / (int) bb_messages_recipients_per_page() );

	}

	$recipients_ids = wp_list_pluck( $all_recipients, 'user_id' );
	$can_message    = false;
	if (
		bb_messages_user_can_send_message(
			array(
				'sender_id'     => bp_loggedin_user_id(),
				'recipients_id' => $recipients_ids,
				'thread_id'     => $bp_get_the_thread_id
			)
		)
	) {
		$can_message = true;
	}

	$thread->thread['can_user_send_message_in_thread'] = $can_message;

	// Check user is deleted.
	if ( ! $is_group_thread && ! empty( $check_recipients ) && 1 === count( $check_recipients ) ) {
		$recipient_id = current( array_keys( $check_recipients ) );
		$is_deleted   = get_user_by( 'id', $recipient_id );

		if ( ! $is_deleted ) {
			$thread->feedback_error = array(
				'feedback' => __( 'Unable to send new messages at this time.', 'buddyboss' ),
				'type'     => 'notice',
			);
		}
	}

	// Check the user has ability to send message into group thread or not.
	if (
		true === bp_disable_group_messages() &&
		$is_group_thread &&
		$group_id &&
		(
			(
				bp_is_active( 'groups' ) &&
				! groups_can_user_manage_messages( bp_loggedin_user_id(), $group_id )
			) ||
			! bp_is_active( 'groups' )
		)
	) {
		$status = ( bp_is_active( 'groups' ) ? bp_group_get_message_status( $group_id ) : '' );
		$notice = __( 'Only group organizers can send messages to this group.', 'buddyboss' );
		if ( 'mods' === $status ) {
			$notice = __( 'Only group organizers and moderators can send messages to this group.', 'buddyboss' );
		}

		$thread->feedback_error = array(
			'feedback' => $notice,
			'type'     => 'notice',
		);

		$thread->thread['can_user_send_message_in_thread'] = false;
	}

	$thread->messages = array();
	$i                = 0;

	while ( bp_thread_messages() ) :
		bp_thread_the_message();

		$bp_get_the_thread_message_id        = bp_get_the_thread_message_id();
		$bp_get_the_thread_message_sender_id = bp_get_the_thread_message_sender_id();
		$group_id                            = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_id', true );
		$group_message_users                 = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_users', true );
		$group_message_type                  = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_type', true );
		$group_message_thread_type           = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_thread_type', true );
		$group_message_fresh                 = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_fresh', true );
		$message_from                        = bp_messages_get_meta( $bp_get_the_thread_message_id, 'message_from', true );
		$message_left                        = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_left', true );
		$message_joined                      = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_joined', true );
		$message_banned                      = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_ban', true );
		$message_unbanned                    = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_un_ban', true );
		$message_deleted                     = bp_messages_get_meta( $bp_get_the_thread_message_id, 'bp_messages_deleted', true );

		if ( $group_id && $message_from && 'group' === $message_from ) {

			$group_text = '';
			if ( bp_is_active( 'groups' ) ) {
				$get_group  = groups_get_group( $group_id );
				$group_name = bp_get_group_name( $get_group );
				if ( empty( $group_name ) ) {
					$group_link = 'javascript:void(0);';
				} else {
					$group_link = bp_get_group_permalink( $get_group );
				}
				$group_avatar = bp_core_fetch_avatar(
					array(
						'item_id'    => $group_id,
						'object'     => 'group',
						'type'       => 'full',
						'avatar_dir' => 'group-avatars',
						/* translators: %s: Group Name */
						'alt'        => sprintf( __( 'Group logo of %s', 'buddyboss' ), $group_name ),
						'title'      => $group_name,
						'html'       => false,
					)
				);
			} else {

				$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table             = $prefix . 'bp_groups';
				$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$group_link               = 'javascript:void(0);';
				$group_avatar             = bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) );
				$legacy_group_avatar_name = '-groupavatar-full';
				$legacy_user_avatar_name  = '-avatar2';

				if ( ! empty( $group_name ) ) {
					$group_link        = 'javascript:void(0);';
					$directory         = 'group-avatars';
					$avatar_size       = '-bpfull';
					$avatar_folder_dir = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
					$avatar_folder_url = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

					$avatar = bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url );
					if ( '' !== $avatar ) {
						$group_avatar = $avatar;
					}
				}
			}

			if ( empty( $group_name ) ) {
				$group_name = '"' . __( 'Deleted Group', 'buddyboss' ) . '"';
				if ( $group_message_users && $group_message_type && 'individual' === $group_message_users && ( 'private' === $group_message_type || 'open' === $group_message_type ) ) {
					$group_text = sprintf( __( 'Sent from %s', 'buddyboss' ), $group_name );
				}
			} else {
				if ( $group_message_users && $group_message_type && 'individual' === $group_message_users && ( 'private' === $group_message_type || 'open' === $group_message_type ) ) {
					$group_text = sprintf( '%1$s <a href="%2$s">%3$s</a>', __( 'Sent from', 'buddyboss' ), $group_link, $group_name );
				}
			}

			$is_group_notice = false;
			if ( $message_left && 'yes' === $message_left ) {
				$is_group_notice = true;
				$content         = bb_messages_get_group_join_leave_text(
					array(
						'thread_id'  => $bp_get_the_thread_id,
						'message_id' => $bp_get_the_thread_message_id,
						'user_id'    => $login_user_id,
						'sender_id'  => $bp_get_the_thread_message_sender_id,
						'type'       => 'left',
					)
				);
			} elseif ( $message_joined && 'yes' === $message_joined ) {
				$is_group_notice = true;
				$content         = bb_messages_get_group_join_leave_text(
					array(
						'thread_id'  => $bp_get_the_thread_id,
						'message_id' => $bp_get_the_thread_message_id,
						'user_id'    => $login_user_id,
						'sender_id'  => $bp_get_the_thread_message_sender_id,
						'type'       => 'joined',
					)
				);
			} elseif ( $message_deleted && 'yes' === $message_deleted ) {
				$content = '<p class="joined deleted-message">' . __( 'This message was deleted', 'buddyboss' ) . '</p>';
			} elseif ( $message_unbanned && 'yes' === $message_unbanned ) {
				/* translators: %s: Group Name */
				$content = sprintf( __( '<p class="joined">Removed Ban <strong>%s</strong></p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_banned && 'yes' === $message_banned ) {
				/* translators: %s: Group Name */
				$content = sprintf( __( '<p class="joined">Ban <strong>%s</strong></p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( 'This message was deleted' === wp_strip_all_tags( bp_get_the_thread_message_content() ) ) {
				$content = '<p class="joined">' . wp_strip_all_tags( bp_get_the_thread_message_content() ) . '</p>';
			} else {
				$content = do_shortcode( bp_get_the_thread_message_content() );
			}

			$thread->messages[ $i ] = array(
				'group_name'                => $group_name,
				'is_deleted'                => $is_deleted_group,
				'group_link'                => $group_link,
				'group_avatar'              => $group_avatar,
				'group_message_users'       => $group_message_users,
				'group_message_type'        => $group_message_type,
				'group_message_thread_type' => $group_message_thread_type,
				'group_message_fresh'       => $group_message_fresh,
				'message_from'              => $message_from,
				'group_text'                => $group_text,
				'id'                        => $bp_get_the_thread_message_id,
				'content'                   => preg_replace( '#(<p></p>)#', '<p><br></p>', $content ),
				'sender_id'                 => $bp_get_the_thread_message_sender_id,
				'sender_name'               => esc_html( bp_get_the_thread_message_sender_name() ),
				'sender_link'               => bp_get_the_thread_message_sender_link(),
				'sender_is_you'             => $bp_get_the_thread_message_sender_id === $login_user_id,
				'sender_avatar'             => esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => $bp_get_the_thread_message_sender_id,
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => 32,
							'height'  => 32,
							'html'    => false,
						)
					)
				),
				'date'                      => bp_get_the_thread_message_date_sent() * 1000,
				'display_date'              => bb_get_the_thread_message_sent_time(),
				'is_group_notice'           => $is_group_notice,
			);

		} else {

			$message_left     = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_left', true );
			$message_joined   = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_joined', true );
			$message_banned   = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_ban', true );
			$message_unbanned = bp_messages_get_meta( $bp_get_the_thread_message_id, 'group_message_group_un_ban', true );
			$message_deleted  = bp_messages_get_meta( $bp_get_the_thread_message_id, 'bp_messages_deleted', true );

			$is_group_notice = false;
			if ( $message_left && 'yes' === $message_left ) {
				$is_group_notice = true;
				$content         = bb_messages_get_group_join_leave_text(
					array(
						'thread_id'  => $bp_get_the_thread_id,
						'message_id' => $bp_get_the_thread_message_id,
						'user_id'    => $login_user_id,
						'sender_id'  => $bp_get_the_thread_message_sender_id,
						'type'       => 'left',
					)
				);
			} elseif ( $message_joined && 'yes' === $message_joined ) {
				$is_group_notice = true;
				$content         = bb_messages_get_group_join_leave_text(
					array(
						'thread_id'  => $bp_get_the_thread_id,
						'message_id' => $bp_get_the_thread_message_id,
						'user_id'    => $login_user_id,
						'sender_id'  => $bp_get_the_thread_message_sender_id,
						'type'       => 'joined',
					)
				);
			} elseif ( $message_deleted && 'yes' === $message_deleted ) {
				$content = '<p class="joined deleted-message">' . __( 'This message was deleted', 'buddyboss' ) . '</p>';
			} elseif ( $message_unbanned && 'yes' === $message_unbanned ) {
				/* translators: %s: Group Name */
				$content = sprintf( __( '<p class="joined">Removed Ban <strong>%s</strong></p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_banned && 'yes' === $message_banned ) {
				/* translators: %s: Group Name */
				$content = sprintf( __( '<p class="joined">Ban <strong>%s</strong></p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( 'This message was deleted' === wp_strip_all_tags( bp_get_the_thread_message_content() ) ) {
				$content = '<p class="joined deleted-message">' . wp_strip_all_tags( bp_get_the_thread_message_content() ) . '</p>';
			} else {
				$content = do_shortcode( bp_get_the_thread_message_content() );
			}

			$thread->messages[ $i ] = array(
				'id'              => $bp_get_the_thread_message_id,
				'content'         => preg_replace( '#(<p></p>)#', '<p><br></p>', $content ),
				'sender_id'       => $bp_get_the_thread_message_sender_id,
				'sender_name'     => esc_html( bp_get_the_thread_message_sender_name() ),
				'is_deleted'      => empty( get_userdata( $bp_get_the_thread_message_sender_id ) ) ? 1 : 0,
				'sender_link'     => bp_get_the_thread_message_sender_link(),
				'sender_is_you'   => $bp_get_the_thread_message_sender_id === $login_user_id,
				'sender_avatar'   => esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => $bp_get_the_thread_message_sender_id,
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => 32,
							'height'  => 32,
							'html'    => false,
						)
					)
				),
				'date'            => bp_get_the_thread_message_date_sent() * 1000,
				'display_date'    => bb_get_the_thread_message_sent_time(),
				'is_group_notice' => $is_group_notice,
			);
		}

		$has_message_updated = false;
		if ( bp_is_active( 'moderation' ) ) {
			$thread->messages[ $i ]['is_user_suspended']  = bp_moderation_is_user_suspended( $bp_get_the_thread_message_sender_id );
			$thread->messages[ $i ]['is_user_blocked']    = bp_moderation_is_user_blocked( $bp_get_the_thread_message_sender_id );
			$thread->messages[ $i ]['is_user_blocked_by'] = bb_moderation_is_user_blocked_by( $bp_get_the_thread_message_sender_id );

			if ( 'yes' !== $message_joined && 'yes' !== $message_left ) {
				if ( bp_moderation_is_user_suspended( $bp_get_the_thread_message_sender_id ) ) {
					$filtred_content = bb_moderation_is_suspended_message( $content, BP_Moderation_Message::$moderation_type, $bp_get_the_thread_message_id );
					if ( $content !== $filtred_content ) {
						$has_message_updated               = true;
						$thread->messages[ $i ]['content'] = '<span class="suspended">' . $filtred_content . '</span>';
					}
				} elseif ( bb_moderation_is_user_blocked_by( $bp_get_the_thread_message_sender_id ) ) {
					$filtred_content = bb_moderation_is_blocked_message( $content, BP_Moderation_Message::$moderation_type, $bp_get_the_thread_message_id );
					if ( $content !== $filtred_content ) {
						$has_message_updated               = true;
						$thread->messages[ $i ]['content'] = '<span class="blocked">' . $filtred_content . '</span>';
					}
				} elseif ( bp_moderation_is_user_blocked( $bp_get_the_thread_message_sender_id ) ) {
					$filtred_content = bb_moderation_has_blocked_message( $content, BP_Moderation_Message::$moderation_type, $bp_get_the_thread_message_id );
					if ( $content !== $filtred_content ) {
						$has_message_updated               = true;
						$thread->messages[ $i ]['content'] = '<span class="blocked">' . $filtred_content . '</span>';
					}
				}
			}
		}

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link(
				array(
					'message_id' => $bp_get_the_thread_message_id,
					'url_only'   => true,
				)
			);

			$thread->messages[ $i ]['star_link']  = $star_link;
			$thread->messages[ $i ]['is_starred'] = array_search( 'unstar', explode( '/', $star_link ), true );
			$thread->messages[ $i ]['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . $bp_get_the_thread_message_id );
		}

		$is_group_thread = bb_messages_is_group_thread( $thread_id );

		if ( bp_is_active( 'media' ) && ( ( ( empty( $is_group_thread ) || ( ! empty( $is_group_thread ) && ! bp_is_active( 'groups' ) ) ) && bp_is_messages_media_support_enabled() ) || ( bp_is_active( 'groups' ) && ! empty( $is_group_thread ) && bp_is_group_media_support_enabled() ) ) ) {
			$media_ids = bp_messages_get_meta( $bp_get_the_thread_message_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) && bp_has_media(
				array(
					'include'          => $media_ids,
					'privacy'          => array( 'message' ),
					'order_by'         => 'menu_order',
					'sort'             => 'ASC',
					'user_id'          => false,
					'moderation_query' => $has_message_updated,
				)
			) ) {
				$thread->messages[ $i ]['media'] = array();
				while ( bp_media() ) {
					bp_the_media();

					$thread->messages[ $i ]['media'][] = array(
						'id'            => bp_get_media_id(),
						'message_id'    => $bp_get_the_thread_message_id,
						'thread_id'     => bp_get_the_thread_id(),
						'title'         => bp_get_media_title(),
						'attachment_id' => bp_get_media_attachment_id(),
						'thumbnail'     => bp_get_media_attachment_image_thumbnail(),
						'full'          => bb_get_media_photos_theatre_popup_image(),
						'meta'          => $media_template->media->attachment_data->meta,
						'privacy'       => bp_get_media_privacy(),
						'height'        => ( isset( $media_template->media->attachment_data->meta['height'] ) ? $media_template->media->attachment_data->meta['height'] : '' ),
						'width'         => ( isset( $media_template->media->attachment_data->meta['width'] ) ? $media_template->media->attachment_data->meta['width'] : '' ),
					);
				}
			}
		}

		if ( bp_is_active( 'video' ) && bp_is_messages_video_support_enabled() ) {
			$video_ids = bp_messages_get_meta( $bp_get_the_thread_message_id, 'bp_video_ids', true );

			if (
				! empty( $video_ids ) &&
				bp_has_video(
					array(
						'include'          => $video_ids,
						'privacy'          => array( 'message' ),
						'order_by'         => 'menu_order',
						'sort'             => 'ASC',
						'user_id'          => false,
						'moderation_query' => $has_message_updated,
					)
				)
			) {
				$thread->messages[ $i ]['video'] = array();
				while ( bp_video() ) {
					bp_the_video();

					$video_html = '';
					if ( 1 === $video_template->video_count ) {
						ob_start();
						bp_get_template_part( 'video/single-video' );
						?>
						<p class="bb-video-loader"></p>
						<?php
						if ( ! empty( bp_get_video_length() ) ) {
							?>
							<p class="bb-video-duration"><?php bp_video_length(); ?></p>
							<?php
						}
						$thumbnail_url = bb_video_get_thumb_url( bp_get_video_id(), bp_get_video_attachment_id(), 'bb-video-profile-album-add-thumbnail-directory-poster-image' );

						if ( empty( $thumbnail_url ) ) {
							$thumbnail_url = bb_get_video_default_placeholder_image();
						}
						?>
						<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap hide" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php bp_video_popup_thumb(); ?>" data-privacy="<?php bp_video_privacy(); ?>"  data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
							<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php bp_video_title(); ?>" />
						</a>
						<?php
						$video_html = ob_get_clean();
						$video_html = str_replace( 'video-js', 'video-js single-activity-video', $video_html );
						$video_html = str_replace( 'id="theatre-video', 'id="video', $video_html );
					}

					$thread->messages[ $i ]['video'][] = array(
						'id'            => bp_get_video_id(),
						'message_id'    => $bp_get_the_thread_message_id,
						'thread_id'     => bp_get_the_thread_id(),
						'title'         => bp_get_video_title(),
						'attachment_id' => bp_get_video_attachment_id(),
						'thumbnail'     => bp_get_video_attachment_image_thumbnail(),
						'full'          => bp_get_video_attachment_image(),
						'meta'          => $video_template->video->attachment_data->meta,
						'privacy'       => bp_get_video_privacy(),
						'video_html'    => $video_html,
					);
				}
			}
		}

		if ( bp_is_active( 'media' ) && ( ( ( empty( $is_group_thread ) || ( ! empty( $is_group_thread ) && ! bp_is_active( 'groups' ) ) ) && bp_is_messages_document_support_enabled() ) || ( bp_is_active( 'groups' ) && ! empty( $is_group_thread ) && bp_is_group_document_support_enabled() ) ) ) {
			$document_ids = bp_messages_get_meta( $bp_get_the_thread_message_id, 'bp_document_ids', true );

			if ( ! empty( $document_ids ) && bp_has_document(
				array(
					'include'          => $document_ids,
					'order_by'         => 'menu_order',
					'sort'             => 'ASC',
					'moderation_query' => $has_message_updated,
				)
			) ) {
				$thread->messages[ $i ]['document'] = array();
				while ( bp_document() ) {
					bp_the_document();

					$attachment_id         = bp_get_document_attachment_id();
					$extension             = bp_document_extension( $attachment_id );
					$svg_icon              = bp_document_svg_icon( $extension, $attachment_id );
					$svg_icon_download     = bp_document_svg_icon( 'download' );
					$download_url          = bp_document_download_link( $attachment_id, bp_get_document_id() );
					$filename              = basename( get_attached_file( $attachment_id ) );
					$size                  = bp_document_size_format( filesize( get_attached_file( $attachment_id ) ) );
					$extension_description = '';
					$url                   = wp_get_attachment_url( $attachment_id );
					$extension_lists       = bp_document_extensions_list();
					$text_attachment_url   = wp_get_attachment_url( $attachment_id );
					$mirror_text           = bp_document_mirror_text( $attachment_id );
					$audio_url             = '';
					$video_url             = '';

					if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
						$audio_url = bp_document_get_preview_url( bp_get_document_id(), $attachment_id );
					}

					if ( in_array( $extension, bp_get_document_preview_video_extensions(), true ) ) {
						$video_url = bb_document_video_get_symlink( bp_get_document_id(), true );
					}

					if ( ! empty( $extension_lists ) ) {
						$extension_lists = array_column( $extension_lists, 'description', 'extension' );
						$extension_name  = '.' . $extension;
						if ( ! empty( $extension_lists ) && ! empty( $extension ) && array_key_exists( $extension_name, $extension_lists ) ) {
							$extension_description = '<span class="document-extension-description">' . esc_html( $extension_lists[ $extension_name ] ) . '</span>';
						}
					}

					$output = '';
					ob_start();

					if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
						$audio_url = bp_document_get_preview_url( bp_get_document_id(), $attachment_id );
						?>
						<div class="document-audio-wrap">
							<audio controls controlsList="nodownload">
								<source src="<?php echo esc_url( $audio_url ); ?>" type="audio/mpeg">
								<?php esc_html_e( 'Your browser does not support the audio element.', 'buddyboss' ); ?>
							</audio>
						</div>
						<?php
					}

					$attachment_url      = bp_document_get_preview_url( bp_get_document_id(), bp_get_document_attachment_id(), 'bb-document-pdf-preview-activity-image' );
					$full_attachment_url = bp_document_get_preview_url( bp_get_document_id(), bp_get_document_attachment_id(), 'bb-document-pdf-image-popup-image' );

					if ( $attachment_url && ! in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
						?>
						<div class="document-preview-wrap">
							<img src="<?php echo esc_url( $attachment_url ); ?>" alt=""/>
						</div><!-- .document-preview-wrap -->
						<?php
					}
					$sizes = is_file( get_attached_file( $attachment_id ) ) ? get_attached_file( $attachment_id ) : 0;
					if ( $sizes && filesize( $sizes ) / 1e+6 < 2 ) {
						if ( in_array( $extension, bp_get_document_preview_code_extensions(), true ) ) {
							$data      = bp_document_get_preview_text_from_attachment( $attachment_id );
							$file_data = $data['text'];
							$more_text = $data['more_text']
							?>
							<div class="document-text-wrap">
								<div class="document-text" data-extension="<?php echo esc_attr( $extension ); ?>">
									<textarea class="document-text-file-data-hidden" style="display: none;"><?php echo wp_kses_post( $file_data ); ?></textarea>
								</div>
								<div class="document-expand">
									<a href="#" class="document-expand-anchor">
										<i class="bb-icon-l bb-icon-expand document-icon-plus"></i> <?php esc_html_e( 'Expand', 'buddyboss' ); ?>
									</a>
								</div>
							</div> <!-- .document-text-wrap -->
							<?php
							if ( true === $more_text ) {

								printf(
								/* translators: %s: download string */
									'<div class="more_text_view">%s</div>',
									sprintf(
									/* translators: %s: download url */
										wp_kses_post( 'This file was truncated for preview. Please <a href="%s">download</a> to view the full file.', 'buddyboss' ),
										esc_url( $download_url )
									)
								);
							}
						}
					}

					$output .= ob_get_clean();

					$thread->messages[ $i ]['document'][] = array(
						'id'                    => bp_get_document_id(),
						'title'                 => bp_get_document_title(),
						'attachment_id'         => bp_get_document_attachment_id(),
						'url'                   => $download_url,
						'extension'             => $extension,
						'svg_icon'              => $svg_icon,
						'svg_icon_download'     => $svg_icon_download,
						'filename'              => $filename,
						'size'                  => $size,
						'meta'                  => $document_template->document->attachment_data->meta,
						'download_text'         => __( 'Click to view', 'buddyboss' ),
						'extension_description' => $extension_description,
						'download'              => __( 'Download', 'buddyboss' ),
						'collapse'              => __( 'Collapse', 'buddyboss' ),
						'expand'                => __( 'Expand', 'buddyboss' ),
						'copy_download_link'    => __( 'Copy Download Link', 'buddyboss' ),
						'more_action'           => __( 'More actions', 'buddyboss' ),
						'preview'               => $attachment_url,
						'full_preview'          => ( '' !== $full_attachment_url ) ? $full_attachment_url : $attachment_url,
						'msg_preview'           => $output,
						'privacy'               => bp_get_db_document_privacy(),
						'author'                => bp_get_document_user_id(),
						'text_preview'          => $text_attachment_url ? esc_url( $text_attachment_url ) : '',
						'mp3_preview'           => $audio_url ? $audio_url : '',
						'document_title'        => $filename ? $filename : '',
						'mirror_text'           => $mirror_text ? $mirror_text : '',
						'video'                 => $video_url ? $video_url : '',
					);
				}
			}
		}

		if ( bp_is_active( 'media' ) && ( ( ( empty( $is_group_thread ) || ( ! empty( $is_group_thread ) && ! bp_is_active( 'groups' ) ) ) && bp_is_messages_gif_support_enabled() ) || ( bp_is_active( 'groups' ) && ! empty( $is_group_thread ) && bp_is_groups_gif_support_enabled() ) ) ) {
			$gif_data = bp_messages_get_meta( $bp_get_the_thread_message_id, '_gif_data', true );

			if ( ! empty( $gif_data ) && ! $has_message_updated ) {
				$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
				$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

				$thread->messages[ $i ]['gif'] = array(
					'preview_url' => $preview_url,
					'video_url'   => $video_url,
				);
			}
		}

		$extra_content = bp_nouveau_messages_catch_hook_content(
			array(
				'beforeMeta'    => 'bp_before_message_meta',
				'afterMeta'     => 'bp_after_message_meta',
				'beforeContent' => 'bp_before_message_content',
				'afterContent'  => 'bp_after_message_content',
			)
		);

		if ( array_filter( $extra_content ) ) {
			$thread->messages[ $i ] = array_merge( $thread->messages[ $i ], $extra_content );
		}

		// Sent date convert into the site zone.
		$date_sent_formatted = bp_core_get_format_date( $thread_template->message->date_sent, 'Y-m-d h:i:s' );

		$thread->messages[ $i ]['sent_date']       = ucfirst( bb_get_thread_start_date( $thread_template->message->date_sent ) );
		$thread->messages[ $i ]['sent_split_date'] = get_date_from_gmt( $date_sent_formatted, 'Y-m-d' );

		$i += 1;
	endwhile;

	$thread->messages = array_filter( $thread->messages );

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	// pagination.
	$thread->per_page                        = $thread_template->thread->messages_perpage;
	$thread->messages_count                  = $thread_template->thread->total_messages;
	$thread->next_messages_timestamp         = $thread_template->thread->messages[ count( $thread_template->thread->messages ) - 1 ]->date_sent;
	$thread->group_id                        = $group_id;
	$thread->is_group_thread                 = $is_group_thread;
	$thread->can_user_send_message_in_thread = ( $is_group_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : apply_filters( 'bb_can_user_send_message_in_thread', true, $thread_template->thread->thread_id, (array) $thread_template->thread->get_recipients() );
	$thread->user_can_upload_media           = bb_user_has_access_upload_media( 0, $login_user_id, 0, $thread_id, 'message' );
	$thread->user_can_upload_document        = bb_user_has_access_upload_document( 0, $login_user_id, 0, $thread_id, 'message' );
	$thread->user_can_upload_video           = bb_user_has_access_upload_video( 0, $login_user_id, 0, $thread_id, 'message' );
	$thread->user_can_upload_gif             = bb_user_has_access_upload_gif( 0, $login_user_id, 0, $thread_id, 'message' );
	$thread->user_can_upload_emoji           = bb_user_has_access_upload_emoji( 0, $login_user_id, 0, $thread_id, 'message' );
	$thread->is_thread_archived              = ( 0 < $is_thread_archived ) ? true : false;

	return $thread;
}

function bp_nouveau_ajax_hide_thread() {

	global $bp, $wpdb;

	$response = array(
		'feedback' => __( 'There was a problem archiving conversation.', 'buddyboss' ),
		'type'     => 'error',
	);

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_ids = wp_parse_id_list( $_POST['id'] );

	$is_group_message_thread = bb_messages_is_group_thread( (int) current( $thread_ids ) );
	if ( $is_group_message_thread ) {
		$thread_id     = current( $thread_ids );
		$first_message = BP_Messages_Thread::get_first_message( $thread_id );
		$group_id      = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		$group_name    = bp_get_group_name( groups_get_group( $group_id ) );
		if ( empty( $group_name ) ) {
			$group_name = __( 'Deleted Group', 'buddyboss' );
		}

		$toast_message = sprintf(
			__( 'Messages for "%s" have been archived.', 'buddyboss' ),
			$group_name
		);

	} else {
		$thread_recipients = BP_Messages_Thread::get_recipients_for_thread( (int) current( $thread_ids ) );
		$recipients        = array();
		if ( ! empty( $thread_recipients ) ) {
			foreach ( $thread_recipients as $recepient ) {
				if ( bp_loggedin_user_id() !== $recepient->user_id ) {
					$recipients[] = bp_core_get_user_displayname( $recepient->user_id );
				}
			}
		}

		$toast_message = sprintf(
			__( 'The conversation with %s has been archived.', 'buddyboss' ),
			implode( ', ', $recipients )
		);
	}

	foreach ( $thread_ids as $thread_id ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 1, (int) $thread_id, bp_loggedin_user_id() ) );

		/**
		 * Fires when messages thread was archived.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param int $thread_id The message thread ID.
		 */
		do_action( 'bb_messages_thread_archived', $thread_id, bp_loggedin_user_id() );
	}

	// Mark each notification for each PM message as read when hide the thread.
	if ( bp_is_active( 'notifications' ) && class_exists( 'BP_Notifications_Notification' ) ) {

		// Get unread PM notifications for the user.
		$new_pm_notifications = BP_Notifications_Notification::get(
			array(
				'user_id'          => bp_loggedin_user_id(),
				'component_name'   => buddypress()->messages->id,
				'component_action' => array( 'new_message', 'bb_groups_new_message', 'bb_messages_new' ),
				'is_new'           => 1,
			)
		);

		$unread_message_ids = wp_list_pluck( $new_pm_notifications, 'item_id' );

		if ( ! empty( $unread_message_ids ) ) {

			// Mark each notification for each PM message as read.
			foreach ( $unread_message_ids as $message_id ) {
				bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'new_message' );
				bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'bb_messages_new' );
				bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), (int) $message_id, buddypress()->messages->id, 'bb_groups_new_message' );
			}
		}
	}

	$inbox_unread_cnt = array(
		'user_id'            => bp_loggedin_user_id(),
		'inbox_unread_count' => messages_get_unread_count( bp_loggedin_user_id() ),
	);

	$thread_link = bb_get_messages_archived_url();
	if ( isset( $_POST['is_current_thread'] ) && 'yes' === $_POST['is_current_thread'] ) {
		$thread_link = bb_get_message_archived_thread_view_link( current( $thread_ids ) );
	}

	wp_send_json_success(
		array(
			'type'                          => 'success',
			'messages'                      => __( 'Thread removed successfully.', 'buddyboss' ),
			'recipient_inbox_unread_counts' => $inbox_unread_cnt,
			'toast_message'                 => $toast_message,
			'thread_ids'                    => $thread_ids,
			'thread_link'                   => $thread_link,
		)
	);
}

/**
 * Function which get next recipients list for block member in message section and message header.
 */
function bb_nouveau_ajax_recipient_list_for_blocks() {
	$post_data = bb_filter_input_string( INPUT_POST, 'post_data', array( FILTER_REQUIRE_ARRAY ) );
	$user_id   = bp_loggedin_user_id() ? (int) bp_loggedin_user_id() : '';

	if ( ! isset( $post_data['thread_id'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_recipient_list_for_blocks', esc_html__( 'Missing thread id.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( ! isset( $post_data['page_no'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_recipient_list_for_blocks', esc_html__( 'Invalid page number.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	// Get all admin ids.
	$administrator_ids                 = function_exists( 'bb_get_all_admin_users' ) ? bb_get_all_admin_users() : '';
	$args                              = array();
	$args['exclude_moderated_members'] = filter_var( $post_data['exclude_moderated_members'], FILTER_VALIDATE_BOOLEAN );
	$args['exclude_current_user']      = filter_var( $post_data['exclude_current_user'], FILTER_VALIDATE_BOOLEAN );
	$args['page']                      = (int) $post_data['page_no'];
	if ( $args['exclude_moderated_members'] ) {
		$args['exclude_admin_user'] = $administrator_ids;
	}

	$member_action = '';
	if ( isset( $post_data['member_action'] ) ) {
		$member_action = bb_filter_var_string( $post_data['member_action'] );
	}

	if ( 'report' === $member_action ) {
		$args['exclude_reported_members'] = $args['exclude_moderated_members'];
		unset( $args['exclude_moderated_members'] );
	}

	$bp_moderation_type = '';
	$thread             = new BP_Messages_Thread( false );
	$results            = $thread->get_pagination_recipients( $post_data['thread_id'], $args );
	if ( is_array( $results ) ) {
		$count          = 1;
		$recipients_arr = array();
		foreach ( $results as $recipient ) {
			if ( isset( $recipient->user_id ) ) {
				if ( (int) $recipient->user_id !== $user_id ) {
					if ( empty( $recipient->is_deleted ) ) {
						$recipients_arr['members'][ $count ] = array(
							'avatar'     => esc_url(
								bp_core_fetch_avatar(
									array(
										'item_id' => $recipient->user_id,
										'object'  => 'user',
										'type'    => 'thumb',
										'width'   => BP_AVATAR_THUMB_WIDTH,
										'height'  => BP_AVATAR_THUMB_HEIGHT,
										'html'    => false,
									)
								)
							),
							'user_link'  => bp_core_get_userlink( $recipient->user_id, false, true ),
							'user_name'  => bp_core_get_user_displayname( $recipient->user_id ),
							'is_deleted' => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
							'is_you'     => bp_loggedin_user_id() === $recipient->user_id,
							'id'         => $recipient->user_id,
						);
						if ( bp_is_active( 'moderation' ) && 'block' === $member_action ) {
							$bp_moderation_type                                     = BP_Moderation_Members::$moderation_type;
							$recipients_arr['members'][ $count ]['is_user_blocked'] = bp_moderation_is_user_blocked( $recipient->user_id );
							$recipients_arr['members'][ $count ]['can_be_blocked']  = ( ! in_array( (int) $recipient->user_id, $administrator_ids, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
						} elseif ( 'report' === $member_action && bp_is_active( 'moderation' ) && bb_is_moderation_member_reporting_enable() ) {
							$bp_moderation_type                                      = BP_Moderation_Members::$moderation_type_report;
							$recipients_arr['members'][ $count ]['is_user_reported'] = bp_moderation_report_exist( $recipient->user_id, $bp_moderation_type );
							$recipients_arr['members'][ $count ]['can_be_report']    = ! in_array( (int) $recipient->user_id, $administrator_ids, true ) && false === bp_moderation_user_can( $user_id, $bp_moderation_type );
						}
						$count ++;
					}
				}
			}
		}
	}
	$recipients_arr['moderation_type'] = ( bp_is_active( 'moderation' ) ? BP_Moderation_Members::$moderation_type : '' );
	wp_send_json_success(
		array(
			'recipients' => $recipients_arr,
			'type'       => 'success',
		)
	);
}

/**
 * Function which get moderated recipients list when click on block a member in the message screen.
 *
 * @since BuddyBoss 1.7.8
 *
 * @return string|Object A JSON object containing html with success data.
 */
function bb_nouveau_ajax_moderated_recipient_list() {
	$post_data = bb_filter_input_string( INPUT_POST, 'post_data', array( FILTER_REQUIRE_ARRAY ) );
	$user_id   = bp_loggedin_user_id() ? (int) bp_loggedin_user_id() : '';
	if ( ! isset( $post_data['thread_id'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_recipient_list_for_blocks', esc_html__( 'Missing thread id.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}
	if ( ! isset( $post_data['page_no'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_recipient_list_for_blocks', esc_html__( 'Invalid page number.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}
	// Get all admin ids.
	$administrator_ids                 = function_exists( 'bb_get_all_admin_users' ) ? bb_get_all_admin_users() : array();
	$args                              = array();
	$args['exclude_moderated_members'] = filter_var( $post_data['exclude_moderated_members'], FILTER_VALIDATE_BOOLEAN );
	$args['exclude_current_user']      = filter_var( $post_data['exclude_current_user'], FILTER_VALIDATE_BOOLEAN );
	$args['page']                      = (int) $post_data['page_no'];
	if ( $args['exclude_moderated_members'] ) {
		$args['exclude_admin_user'] = $administrator_ids;
	}

	$member_action = '';
	if ( isset( $post_data['member_action'] ) ) {
		$member_action = bb_filter_var_string( $post_data['member_action'] );
	}

	if ( 'report' === $member_action ) {
		unset( $args['exclude_moderated_members'] );
	}

	$thread_id = (int) $post_data['thread_id'];

	$is_group_message_thread = bb_messages_is_group_thread( $thread_id );
	$first_message           = BP_Messages_Thread::get_first_message( $thread_id );
	$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );

	if ( $is_group_message_thread && $group_id && bp_is_active( 'groups' ) ) {
		$banned_member   = groups_get_group_members(
			array(
				'group_id'        => $group_id,
				'group_role'      => array( 'banned' ),
				'per_page'        => - 1,
				'populate_extras' => false,
			)
		);
		$args['exclude'] = ! empty( $banned_member['members'] ) ? array_column( $banned_member['members'], 'ID' ) : array();

		add_filter( 'bp_recipients_recipient_get_join_sql', 'bb_recipients_recipient_get_join_sql_with_group_members', 10, 2 );
	}

	$thread  = new BP_Messages_Thread( false );
	$results = $thread->get_pagination_recipients( $thread_id, $args );
	$html    = '';
	$item    = 0;

	if ( is_array( $results ) ) {
		ob_start();
		?>
		<div class="bb-report-type-wrp">
			<?php
			foreach ( $results as $recipient ) {
				if ( isset( $recipient->user_id ) ) {
					if ( (int) $recipient->user_id !== $user_id ) {

						if ( ! empty( $member_action ) ) {
							$user_data = get_userdata( $recipient->user_id );
							if ( empty( $user_data ) ) {
								continue;
							}
						}

						if ( empty( $recipient->is_deleted ) ) {
							$avatar    = esc_url(
								bp_core_fetch_avatar(
									array(
										'item_id' => $recipient->user_id,
										'object'  => 'user',
										'type'    => 'thumb',
										'width'   => BP_AVATAR_THUMB_WIDTH,
										'height'  => BP_AVATAR_THUMB_HEIGHT,
										'html'    => false,
									)
								)
							);
							$user_name = bp_core_get_user_displayname( $recipient->user_id );
							?>
							<div class="user-item-wrp" id="user-<?php echo esc_attr( $recipient->user_id ); ?>">
								<?php if ( ! empty ( bp_core_get_user_domain( $recipient->user_id ) ) ) { ?>
									<div class="user-avatar">
										<a href="<?php echo esc_url( bp_core_get_user_domain( $recipient->user_id ) ); ?>"><img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_html( $user_name ); ?>"></a>
									</div>
									<div class="user-name">
										<a href="<?php echo esc_url( bp_core_get_user_domain( $recipient->user_id ) ); ?>"><?php echo esc_html( $user_name ); ?></a>
									</div>
								<?php } else { ?>
									<div class="user-avatar">
										<span><img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_html( $user_name ); ?>"></span>
									</div>
									<div class="user-name">
										<span><?php echo esc_html( $user_name ); ?></span>
									</div>
								<?php } ?>
								<?php if ( 'block' === $member_action && bp_is_active( 'moderation' ) ) { ?>
									<div class="user-actions">
										<?php
										$can_be_blocked = ( ! in_array( (int) $recipient->user_id, $administrator_ids, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
										if ( true === bp_moderation_is_user_blocked( $recipient->user_id ) ) {
											?>
											<a id="reported-user" class="blocked-member button small disabled">
												<?php esc_html_e( 'Blocked', 'buddyboss' ); ?>
											</a>
											<?php
										} elseif ( false !== $can_be_blocked ) {
											$bp_moderation_type = BP_Moderation_Members::$moderation_type;
											?>
											<a id="report-content-<?php echo esc_attr( $bp_moderation_type ); ?>-<?php echo esc_attr( $recipient->user_id ); ?>"
												href="#block-member" class="block-member button small"
												data-bp-content-id="<?php echo esc_attr( $recipient->user_id ); ?>"
												data-bp-content-type="<?php echo esc_attr( $bp_moderation_type ); ?>"
												data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"
												data-bp-block-title="<?php esc_html_e( 'Block', 'buddyboss' ); ?>"
												data-bp-blocked-title="<?php esc_html_e( 'Blocked', 'buddyboss' ); ?>">
												<?php esc_html_e( 'Block', 'buddyboss' ); ?>
											</a>
											<?php
										}
										?>
									</div>
								<?php } elseif ( 'report' === $member_action && bp_is_active( 'moderation' ) && bb_is_moderation_member_reporting_enable() ) { ?>
									<div class="user-actions">
										<?php
										$bp_moderation_type = BP_Moderation_Members::$moderation_type_report;
										$can_be_blocked     = ! in_array( (int) $recipient->user_id, $administrator_ids, true ) && false === (bool) bp_moderation_user_can( $user_id, $bp_moderation_type, false );

										if ( bp_moderation_report_exist( $recipient->user_id, $bp_moderation_type ) ) {
											?>
											<a id="reported-user" class="reported-content button small disabled">
												<?php esc_html_e( 'Reported', 'buddyboss' ); ?>
											</a>
											<?php
										} elseif ( false !== $can_be_blocked ) {
											?>
											<a id="report-content-<?php echo esc_attr( $bp_moderation_type ); ?>-<?php echo esc_attr( $recipient->user_id ); ?>"
												href="#content-report" class="report-content button small"
												data-bp-content-id="<?php echo esc_attr( $recipient->user_id ); ?>"
												data-bp-content-type="<?php echo esc_attr( $bp_moderation_type ); ?>"
												data-bp-nonce="<?php echo esc_attr( wp_create_nonce( 'bp-moderation-content' ) ); ?>"
												reported_type="<?php echo esc_attr( bp_moderation_get_report_type( $bp_moderation_type, $recipient->user_id ) ); ?>"
												data-bp-report-title="<?php esc_html_e( 'Report Member', 'buddyboss' ); ?>"
												data-bp-reported-title="<?php esc_html_e( 'Reported Member', 'buddyboss' ); ?>">
												<?php esc_html_e( 'Report Member', 'buddyboss' ); ?>
											</a>
											<?php
										}
										?>
									</div>
								<?php } ?>
							</div>
							<?php
							$item ++;
						}
					}
				}
			}

			if ( 0 === $item && 'block' === $member_action ) {
				echo '<p class="bbm-notice">' . esc_html__( 'All members in this thread are already blocked.', 'buddyboss' ) . '</p>';
			}
			?>
		</div>
		<?php
		if ( 1 < $thread->total_recipients_count && $thread->total_recipients_count > bb_messages_recipients_per_page() ) {
			?>
			<div class="bb-report-type-pagination">
				<p class="page-data"
					data-thread-id="<?php echo esc_attr( $post_data['thread_id'] ); ?>">
					<a href="javascript:void(0);" name="load_more_rl" id="load_more_rl" class="load_more_rl button small outline"
						data-thread-id="<?php echo esc_attr( $post_data['thread_id'] ); ?>"
						data-tp="<?php echo esc_attr( ceil( (int) $thread->total_recipients_count / (int) bb_messages_recipients_per_page() ) ); ?>"
						data-tc="<?php echo esc_attr( $thread->total_recipients_count ); ?>"
						data-pp="<?php echo esc_attr( bb_messages_recipients_per_page() ); ?>" data-cp="2"
						data-member-action="<?php echo esc_attr( $member_action ); ?>"
						data-action="bp_load_more"><?php esc_html_e( 'Finding members...', 'buddyboss' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
		?>
		<?php
		$html .= ob_get_clean();
	}

	if ( 0 === $item && 'block' === $member_action && empty( $html ) ) {
		echo '<div class="bb-report-type-wrp"><p class="bbm-notice">' . esc_html__( 'All members in this thread are already blocked.', 'buddyboss' ) . '</p></div>';
	}

	if ( $is_group_message_thread && $group_id && bp_is_active( 'groups' ) ) {
		remove_filter( 'bp_recipients_recipient_get_join_sql', 'bb_recipients_recipient_get_join_sql_with_group_members', 10, 2 );
	}

	wp_send_json_success(
		array(
			'content' => $html,
			'type'    => 'success',
		)
	);
}

/**
 * Function which get left/join members list.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void|string|Object A JSON object containing html with success data.
 */
function bb_nouveau_ajax_left_join_members_list() {
	$post_data = bb_filter_input_string( INPUT_POST, 'post_data', array( FILTER_REQUIRE_ARRAY ) );
	if ( ! isset( $post_data['message_id'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_left_join_members_list', esc_html__( 'Missing message id.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}
	if ( ! isset( $post_data['message_type'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_left_join_members_list', esc_html__( 'Missing message type.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}
	$html = '';

	if ( 'joined' === $post_data['message_type'] ) {
		$results = bp_messages_get_meta( $post_data['message_id'], 'group_message_group_joined_users' );
	} else {
		$results = bp_messages_get_meta( $post_data['message_id'], 'group_message_group_left_users' );
	}
	ob_start();
	if ( is_array( $results ) ) {
		?>
		<div class="bb-report-type-wrp">
			<?php
			foreach ( $results as $recipient ) {
				$avatar    = esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => $recipient['user_id'],
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => BP_AVATAR_THUMB_WIDTH,
							'height'  => BP_AVATAR_THUMB_HEIGHT,
							'html'    => false,
						)
					)
				);
				$user_name = ( $recipient['user_id'] === bp_loggedin_user_id() ) ? __( 'You', 'buddyboss' ) : bp_core_get_user_displayname( $recipient['user_id'] );
				?>
				<div class="user-item-wrp" id="user-<?php echo esc_attr( $recipient['user_id'] ); ?>">
					<div class="user-avatar">
						<a href="<?php echo esc_url( bp_core_get_user_domain( $recipient['user_id'] ) ); ?>"><img src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_html( $user_name ); ?>"></a>
					</div>
					<div class="user-name">
						<a href="<?php echo esc_url( bp_core_get_user_domain( $recipient['user_id'] ) ); ?>"><?php echo esc_html( $user_name ); ?></a>
					</div>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
	$html .= ob_get_contents();
	ob_end_clean();
	wp_send_json_success(
		array(
			'content' => $html,
			'type'    => 'success',
		)
	);
}

/**
 * Return the while fetching members thread.
 *
 * @since BuddyBoss 1.9.0
 *
 * @return int Recipient per page
 */
function bb_get_user_message_recipients() {
	return 5;
}

/**
 * Unhide the conversation.
 *
 * @since BuddyBoss 2.1.4
 */
function bp_nouveau_ajax_unhide_thread() {
	global $bp, $wpdb;

	$response = array(
		'feedback' => __( 'There was a problem unarchiving the conversation.', 'buddyboss' ),
		'type'     => 'error',
	);

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( $response );
	}

	$thread_ids = wp_parse_id_list( $_POST['id'] );

	$is_group_message_thread = bb_messages_is_group_thread( (int) current( $thread_ids ) );
	if ( $is_group_message_thread ) {
		$thread_id     = current( $thread_ids );
		$first_message = BP_Messages_Thread::get_first_message( $thread_id );
		$group_id      = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		$group_name    = bp_get_group_name( groups_get_group( $group_id ) );
		if ( empty( $group_name ) ) {
			$group_name = __( 'Deleted Group', 'buddyboss' );
		}

		$toast_message = sprintf(
			__( 'Messages for "%s" have been unarchived.', 'buddyboss' ),
			$group_name
		);

	} else {
		$thread_recipients = BP_Messages_Thread::get_recipients_for_thread( (int) current( $thread_ids ) );
		$recipients        = array();
		if ( ! empty( $thread_recipients ) ) {
			foreach ( $thread_recipients as $recepient ) {
				if ( bp_loggedin_user_id() !== $recepient->user_id ) {
					$recipients[] = bp_core_get_user_displayname( $recepient->user_id );
				}
			}
		}

		$toast_message = sprintf(
			__( 'The conversation with %s has been unarchived.', 'buddyboss' ),
			implode( ', ', $recipients )
		);
	}

	foreach ( $thread_ids as $thread_id ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 0, (int) $thread_id, bp_loggedin_user_id() ) );

		/**
		 * Fires when messages thread was un-archived.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param int $thread_id The message thread ID.
		 */
		do_action( 'bb_messages_thread_unarchived', $thread_id, bp_loggedin_user_id() );
	}

	$inbox_unread_cnt = array(
		'user_id'            => bp_loggedin_user_id(),
		'inbox_unread_count' => messages_get_unread_count( bp_loggedin_user_id() ),
	);

	$thread_link = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );
	if ( isset( $_POST['is_current_thread'] ) && 'yes' === $_POST['is_current_thread'] ) {
		$thread_link = bp_get_message_thread_view_link( current( $thread_ids ) );
	}

	wp_send_json_success(
		array(
			'type'                          => 'success',
			'messages'                      => __( 'Thread un-archived successfully.', 'buddyboss' ),
			'recipient_inbox_unread_counts' => $inbox_unread_cnt,
			'thread_ids'                    => $thread_ids,
			'toast_message'                 => $toast_message,
			'thread_link'                   => $thread_link,
		)
	);
}

