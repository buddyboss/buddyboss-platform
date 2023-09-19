<?php
/**
 * BuddyBoss Messages Functions.
 *
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss\Messages\Functions
 * @since   BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Create a new message.
 *
 * @since BuddyPress 2.4.0 Added 'error_type' as an additional $args parameter.
 *
 * @param array|string $args         {
 *                                   Array of arguments.
 *
 * @type int           $sender_id    Optional. ID of the user who is sending the
 *                                 message. Default: ID of the logged-in user.
 * @type int           $thread_id    Optional. ID of the parent thread. Leave blank to
 *                                 create a new thread for the message.
 * @type array         $recipients   IDs or usernames of message recipients. If this
 *                                 is an existing thread, it is unnecessary to pass a $recipients
 *                                 argument - existing thread recipients will be assumed.
 * @type string        $subject      Optional. Subject line for the message. For
 *                                 existing threads, the existing subject will be used. For new
 *                                 threads, 'No Subject' will be used if no $subject is provided.
 * @type string        $content      Content of the message. Cannot be empty.
 * @type string        $date_sent    Date sent, in 'Y-m-d H:i:s' format. Default: current date/time.
 * @type bool          $is_hidden    Optional. Whether to hide the thread from sender messages inbox or not. Default: false.
 * @type bool          $mark_visible Optional. Whether to mark thread visible to all other participants. Default: false.
 * @type string        $error_type   Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 *
 * @return int|bool|WP_Error ID of the message thread on success, false on failure.
 */
function messages_new_message( $args = '' ) {
	global $wpdb, $bp;

	$current_sent_time = bp_core_current_time();

	$send_date = false;

	// Parse the default arguments.
	$r = bp_parse_args(
		$args,
		array(
			'sender_id'     => bp_loggedin_user_id(),
			'thread_id'     => false,   // False for a new message, thread id for a reply to a thread.
			'recipients'    => array(), // Can be an array of usernames, user_ids or mixed.
			'subject'       => false,
			'content'       => false,
			'date_sent'     => $current_sent_time,
			'append_thread' => true,
			'is_hidden'     => false,
			'mark_visible'  => false,
			'group_thread'  => false,
			'error_type'    => 'bool',
			'send_at'       => false,
			'mark_read'     => false,
		),
		'messages_new_message'
	);

	if ( ! empty( $r['send_at'] ) && strtotime( $r['date_sent'] ) !== strtotime( $r['send_at'] ) ) {

		$date_sent_timestamp        = strtotime( $r['date_sent'] );
		$date_sent_timestamp_before = $date_sent_timestamp - ( 60 * 5 );
		$send_at_timestamp          = strtotime( $r['send_at'] );

		// Check the pusher date is not more than 5 mins.
		if ( $send_at_timestamp <= $date_sent_timestamp && $send_at_timestamp >= $date_sent_timestamp_before ) {
			$r['date_sent'] = $r['send_at'];
			$send_date      = true;
		}
	}

	// Bail if no sender or no content.
	if ( empty( $r['sender_id'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {
			$error_code = 'messages_empty_sender';
			$feedback   = __( 'Your message was not sent. Please use a valid sender.', 'buddyboss' );

			return new WP_Error( $error_code, $feedback );
		} else {
			return false;
		}
	}

	$group_id = ! empty( $_POST['group'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['group'] ) ) : 0;

	/**
	 * Filter to validate message content.
	 *
	 * @since BuddyBoss 2.0.4
	 *
	 * @param bool   $validated_content True if message is valid, false otherwise.
	 * @param string $content           Content of the message.
	 * @param array  $_POST             POST Request Object.
	 *
	 * @return bool True if message is valid, false otherwise.
	 */
	$validated_content = (bool) apply_filters( 'bp_messages_message_validated_content', ! empty( $r['content'] ) && strlen( trim( html_entity_decode( wp_strip_all_tags( $r['content'] ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) ), $r['content'], $_POST );

	if ( ! $validated_content ) {
		if ( 'wp_error' === $r['error_type'] ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'Your message was not sent. Please enter some content.', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		} else {
			return false;
		}
	}

	if ( ! empty( $_POST['media'] ) ) {
		$can_send_media = bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
		if ( ! $can_send_media ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'You don\'t have access to send the media. ', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		}
	}

	if ( ! empty( $_POST['document'] ) ) {
		$can_send_document = bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
		if ( ! $can_send_document ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'You don\'t have access to send the document. ', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		}
	}

	if ( ! empty( $_POST['video'] ) ) {
		$can_send_video = bb_user_has_access_upload_video( $group_id, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
		if ( ! $can_send_video ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'You don\'t have access to send the video. ', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		}
	}

	if ( ! empty( $_POST['gif_data'] ) ) {
		$can_send_gif = bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
		if ( ! $can_send_gif ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'You don\'t have access to send the gif. ', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		}
	}

	// Create a new message object.
	$message               = new BP_Messages_Message();
	$message->thread_id    = $r['thread_id'];
	$message->sender_id    = $r['sender_id'];
	$message->subject      = $r['subject'];
	$message->message      = $r['content'];
	$message->date_sent    = $r['date_sent'];
	$message->is_hidden    = $r['is_hidden'];
	$message->mark_visible = $r['mark_visible'];
	$message->mark_read    = $r['mark_read'];

	$new_reply       = false;
	$is_group_thread = isset( $r['group_thread'] ) ? (bool) $r['group_thread'] : false;

	// If we have a thread ID...
	if ( ! empty( $r['thread_id'] ) ) {

		// ...use the existing recipients
		$thread              = new BP_Messages_Thread( $r['thread_id'] );
		$message->recipients = $thread->get_recipients();

		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		if ( isset( $message->recipients[ $r['sender_id'] ] ) && ( count( $message->recipients ) > 1 ) ) {
			unset( $message->recipients[ $r['sender_id'] ] );
		}

		// Filter out the suspended recipients.
		if ( function_exists( 'bp_moderation_is_user_suspended' ) && count( $message->recipients ) > 0 ) {
			foreach ( $message->recipients as $key => $recipient ) {
				if ( bp_moderation_is_user_suspended( $key ) ) {
					unset( $message->recipients[ $key ] );
				}
			}
		}

		// Set a default reply subject if none was sent.
		if ( empty( $message->subject ) ) {
			$re = __( 'Re', 'buddyboss' ) . ': ';

			if ( strpos( $thread->messages[0]->subject, $re ) === 0 ) {
				$message->subject = $thread->messages[0]->subject;
			} else {
				$message->subject = $re . $thread->messages[0]->subject;
			}
		}

		$new_reply     = true;
		$first_message = BP_Messages_Thread::get_first_message( (int) $r['thread_id'] );
		$message_id    = $first_message->id;
		if ( isset( $message_id ) ) {
			$group = (int) bp_messages_get_meta( $message_id, 'group_id', true ); // group id.
			if ( ! empty( $group ) && bp_is_active( 'groups' ) && $group > 0 ) {
				$group_thread = (int) groups_get_groupmeta( $group, 'group_message_thread' );
				if ( (int) $r['thread_id'] === $group_thread ) {
					$is_group_thread = true;
				}
			} elseif ( ! empty( $group ) && ! bp_is_active( 'groups' ) && $group > 0 ) {
				$prefix            = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_meta_table = $prefix . 'bp_groups_groupmeta';
				$thread_id         = (int) $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$groups_meta_table} WHERE meta_key = %s AND group_id = %d", 'group_message_thread', $group ) ); // db call ok; no-cache ok;
				if ( (int) $r['thread_id'] === $thread_id ) {
					$is_group_thread = true;
				}
			}
		}

		// ...otherwise use the recipients passed
	} else {

		// Bail if no recipients.
		if ( empty( $r['recipients'] ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error( 'message_empty_recipients', __( 'Message could not be sent. Please enter a recipient.', 'buddyboss' ) );
			} else {
				return false;
			}
		}

		// Set a default subject if none exists.
		if ( empty( $message->subject ) ) {
			$message->subject = __( 'No Subject', 'buddyboss' );
		}

		// Setup the recipients array.
		$recipient_ids = array();

		// Invalid recipients are added to an array, for future enhancements.
		$invalid_recipients = array();

		// Loop the recipients and convert all usernames to user_ids where needed.
		foreach ( (array) $r['recipients'] as $recipient ) {

			// Trim spaces and skip if empty.
			$recipient = trim( $recipient );
			if ( empty( $recipient ) ) {
				continue;
			}

			// Check user_login / nicename columns first
			// @see http://buddypress.trac.wordpress.org/ticket/5151.
			if ( bp_is_username_compatibility_mode() ) {
				$recipient_id = bp_core_get_userid( urldecode( $recipient ) );
			} else {
				$recipient_id = bp_core_get_userid_from_nicename( $recipient );
			}

			// Check against user ID column if no match and if passed recipient is numeric.
			if ( empty( $recipient_id ) && is_numeric( $recipient ) ) {
				if ( bp_core_get_core_userdata( (int) $recipient ) ) {
					$recipient_id = (int) $recipient;
				}
			}

			// If $recipient_id still blank then try last time to find $recipient_id via the nickname field.
			if ( empty( $recipient_id ) ) {
				$recipient_id = bp_core_get_userid_from_nickname( $recipient );
			}

			// Decide which group to add this recipient to.
			if ( empty( $recipient_id ) ) {
				$invalid_recipients[] = $recipient;
			} else {
				$recipient_ids[] = (int) $recipient_id;
			}
		}

		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		$self_send = array_search( $r['sender_id'], $recipient_ids );
		if ( ! empty( $self_send ) && ( count( $recipient_ids ) > 1 ) ) {
			unset( $recipient_ids[ $self_send ] );
		}

		// Remove duplicates & bail if no recipients.
		$recipient_ids = array_unique( $recipient_ids );
		if ( empty( $recipient_ids ) ) {
			if ( 'wp_error' === $r['error_type'] ) {
				return new WP_Error( 'message_invalid_recipients', __( 'Message could not be sent because you have entered an invalid username. Please try again.', 'buddyboss' ) );
			} else {
				return false;
			}
		}

		// Format this to match existing recipients.
		foreach ( (array) $recipient_ids as $i => $recipient_id ) {
			$message->recipients[ $i ]          = new stdClass();
			$message->recipients[ $i ]->user_id = $recipient_id;
		}

		$previous_threads = BP_Messages_Message::get_existing_threads( $recipient_ids, $r['sender_id'], true );

		$previous_thread  = null;
		if ( $previous_threads ) {

			foreach ( $previous_threads as $thread ) {

				$is_active_recipient = BP_Messages_Thread::is_thread_recipient( (int) $thread->thread_id, $r['sender_id'] );
				if ( ! $is_active_recipient ) {
					continue;
				}

				$first_message = BP_Messages_Thread::get_first_message( (int) $thread->thread_id );
				$message_id    = $first_message->id;
				$group         = bp_messages_get_meta( $message_id, 'group_id', true ); // group id.
				$message_users = bp_messages_get_meta( $message_id, 'group_message_users', true ); // all - individual.
				$message_type  = bp_messages_get_meta( $message_id, 'group_message_type', true ); // open - private.
				$thread_type   = bp_messages_get_meta( $message_id, 'group_message_thread_type', true ); // new - reply.
				$message_from  = bp_messages_get_meta( $message_id, 'message_from', true ); // group.

				if ( ! empty( $group ) && 'all' === $message_users && 'open' === $message_type && 'new' === $thread_type && 'group' === $message_from ) {
					$previous_thread = null;
				} else {
					$previous_thread     = (int) $thread->thread_id;
					$total_users_threads = $wpdb->get_results( $wpdb->prepare( "SELECT is_deleted FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", (int) $previous_thread ) ); // db call ok; no-cache ok;
					foreach ( $total_users_threads as $total_users_thread ) {
						if ( 1 === (int) $total_users_thread->is_deleted ) {
							$previous_thread = null;
							break;
						}
					}
					if ( $previous_thread ) {
						break;
					}
				}
			}
		} else {
			$previous_threads = null;
		}

		if ( $previous_thread && $r['append_thread'] ) {
			$message->thread_id = (int) $previous_thread;

			// Set a default reply subject if none was sent.
			if ( empty( $message->subject ) ) {
				$message->subject = sprintf(
					__( '%s', 'buddyboss' ),
					wp_trim_words( $thread->messages[0]->subject, messages_get_default_subject_length() )
				);
			}
		}
	}

	// Check if force friendship is enabled and check recipients.
	if ( true !== $is_group_thread && ( count( $message->recipients ) < 2 ) ) {

		$error_messages = array(
			'new_message'       => __( 'You need to be connected with this member in order to send a message.', 'buddyboss' ),
			'new_reply'         => __( 'You need to be connected with this member to continue this conversation.', 'buddyboss' ),
			'new_group_message' => __( 'You need to be connected with all recipients in order to send them a message.', 'buddyboss' ),
			'new_group_reply'   => __( 'You need to be connected with all recipients to continue this conversation.', 'buddyboss' ),
		);

		foreach ( (array) $message->recipients as $i => $recipient ) {
			if (
				! bb_messages_user_can_send_message(
					array(
						'sender_id'     => $message->sender_id,
						'recipients_id' => $recipient->user_id,
					)
				)
			) {
				if ( 'wp_error' === $r['error_type'] ) {
					if ( $new_reply && 1 === count( $message->recipients ) ) {
						return new WP_Error( 'message_invalid_recipients', $error_messages['new_reply'] );
					} elseif ( $new_reply && count( $message->recipients ) > 1 ) {
						return new WP_Error( 'message_invalid_recipients', $error_messages['new_group_reply'] );
					} elseif ( count( $message->recipients ) > 1 ) {
						return new WP_Error( 'message_invalid_recipients', $error_messages['new_group_message'] );
					} else {
						return new WP_Error( 'message_invalid_recipients', $error_messages['new_message'] );
					}
				} else {
					return false;
				}
			}
		}
	}

	// Check user can send the message.
	if ( true !== $is_group_thread ) {
		$has_access = bb_user_can_send_messages( '', (array) $message->recipients, 'wp_error' );
		if ( is_wp_error( $has_access ) ) {
			return $has_access;
		}
	}

	// Prepare to update the deleted user's last message if message sending successful.
	$last_message_data = BP_Messages_Thread::prepare_last_message_status( $message->thread_id );

	// Bail if message failed to send.
	$send = $message->send();
	if ( false === is_int( $send ) ) {
		if ( 'wp_error' === $r['error_type'] ) {
			if ( is_wp_error( $send ) ) {
				return $send;
			} else {
				return new WP_Error( 'message_generic_error', __( 'There was a problem sending your message.', 'buddyboss' ) );
			}
		}

		return false;
	}

	if ( $send_date ) {
		// Save the meta for current sent time.
		bp_messages_update_meta( $send, 'user_date_sent', $current_sent_time );
	}

	// only update after the send().
	BP_Messages_Thread::update_last_message_status( $last_message_data );

	/**
	 * Fires after a message has been successfully sent.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param BP_Messages_Message $message Message object. Passed by reference.
	 */
	do_action_ref_array( 'messages_message_sent', array( &$message ) );

	if ( isset( $r['return'] ) && 'id' === $r['return'] ) {
		// Return the Message ID.
		return $send;
	} elseif ( isset( $r['return'] ) && 'object' === $r['return'] ) {
		$message->id = $send;
		return $message;
	}

	// Return the thread ID.
	return $message->thread_id;
}

/**
 * Create New Group Message.
 *
 * @param array|string $args         { Array of arguments.
 *
 * @type int           $sender_id    Optional. ID of the user who is sending the message. Default: ID of the logged-in user.
 * @type int           $thread_id    Optional. ID of the parent thread. Leave blank to create a new thread for the message.
 * @type array         $recipients   IDs or usernames of message recipients. If this is an existing thread, it is unnecessary to pass a $recipients argument - existing thread recipients will be assumed.
 * @type string        $subject      Optional. Subject line for the message. For existing threads, the existing subject will be used. For new threads, 'No Subject' will be used if no $subject is provided.
 * @type string        $content      Content of the message. Cannot be empty.
 * @type string        $date_sent    Date sent, in 'Y-m-d H:i:s' format. Default: current date/time.
 * @type bool          $is_hidden    Optional. Whether to hide the thread from sender messages inbox or not. Default: false.
 * @type bool          $mark_visible Optional. Whether to mark thread visible to all other participants. Default: false.
 * @type string        $error_type   Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 *
 * @return int|bool|WP_Error ID of the message thread on success, false on failure.
 */
function bp_groups_messages_new_message( $args = '' ) {

	$send = '';
	remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
	add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );

	$r = bp_parse_args(
		$args,
		array(
			'sender_id'     => bp_loggedin_user_id(),
			'thread_id'     => false,   // False for a new message, thread id for a reply to a thread.
			'recipients'    => array(), // Can be an array of usernames, user_ids or mixed.
			'subject'       => false,
			'content'       => false,
			'date_sent'     => bp_core_current_time(),
			'append_thread' => false,
			'is_hidden'     => false,
			'mark_visible'  => false,
			'group_thread'  => true,
			'error_type'    => 'wp_error',
		),
		'bp_groups_messages_new_message'
	);

	// Attempt to send the message.
	$send = messages_new_message( $r );
	remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
	add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

	return $send;
}

/**
 * Send a notice.
 *
 * @param string $subject Subject of the notice.
 * @param string $message Content of the notice.
 *
 * @return bool True on success, false on failure.
 */
function messages_send_notice( $subject, $message ) {
	if ( ! bp_current_user_can( 'bp_moderate' ) || empty( $subject ) || empty( $message ) ) {
		return false;

		// Has access to send notices, lets do it.
	} else {
		$notice            = new BP_Messages_Notice();
		$notice->subject   = $subject;
		$notice->message   = $message;
		$notice->date_sent = bp_core_current_time();
		$notice->is_active = 1;
		$notice->save(); // Send it.

		/**
		 * Fires after a notice has been successfully sent.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param string $subject Subject of the notice.
		 * @param string $message Content of the notice.
		 */
		do_action_ref_array( 'messages_send_notice', array( $subject, $message ) );

		return true;
	}
}

/**
 * Deletes message thread(s) for a given user.
 *
 * Note that "deleting" a thread for a user means removing it from the user's
 * message boxes. A thread is not deleted from the database until it's been
 * "deleted" by all recipients.
 *
 * @since BuddyPress 2.7.0 The $user_id parameter was added. Previously the current user
 *              was always assumed.
 *
 * @param int|array $thread_ids Thread ID or array of thread IDs.
 * @param int       $user_id    ID of the user to delete the threads for. Defaults
 *                              to the current logged-in user.
 *
 * @return bool True on success, false on failure.
 */
function messages_delete_thread( $thread_ids, $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id =
			bp_displayed_user_id() ?
				bp_displayed_user_id() :
				bp_loggedin_user_id();
	}

	/**
	 * Fires before specified thread IDs have been deleted.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.7.0 The $user_id parameter was added.
	 *
	 * @param int|array $thread_ids Thread ID or array of thread IDs to be deleted.
	 * @param int       $user_id    ID of the user the threads are being deleted for.
	 */
	do_action( 'messages_before_delete_thread', $thread_ids, $user_id );

	if ( is_array( $thread_ids ) ) {
		$error = 0;
		for ( $i = 0, $count = count( $thread_ids ); $i < $count; ++ $i ) {
			if ( ! BP_Messages_Thread::delete( $thread_ids[ $i ], $user_id ) ) {
				$error = 1;
			}
		}

		if ( ! empty( $error ) ) {
			return false;
		}

		/**
		 * Fires after specified thread IDs have been deleted.
		 *
		 * @since BuddyPress 1.0.0
		 * @since BuddyPress 2.7.0 The $user_id parameter was added.
		 *
		 * @param int|array Thread ID or array of thread IDs that were deleted.
		 * @param int       ID of the user that the threads were deleted for.
		 */
		do_action( 'messages_delete_thread', $thread_ids, $user_id );

		return true;
	} else {
		if ( ! BP_Messages_Thread::delete( $thread_ids, $user_id ) ) {
			return false;
		}

		/** This action is documented in bp-messages/bp-messages-functions.php */
		do_action( 'messages_delete_thread', $thread_ids, $user_id );

		return true;
	}
}

/**
 * Check whether a user has access to a thread.
 *
 * @param int $thread_id ID of the thread.
 * @param int $user_id   Optional. ID of the user. Default: ID of the logged-in user.
 *
 * @return int|null Message ID if the user has access, otherwise null.
 */
function messages_check_thread_access( $thread_id, $user_id = 0 ) {
	return BP_Messages_Thread::check_access( $thread_id, $user_id );
}

/**
 * Mark a thread as read.
 *
 * Wrapper for {@link BP_Messages_Thread::mark_as_read()}.
 *
 * @param int $thread_id ID of the thread.
 * @param int $user_id   Optional. The user the thread will be marked as read.
 *
 * @return false|int Number of threads marked as read or false on error.
 */
function messages_mark_thread_read( $thread_id, $user_id = 0  ) {
	return BP_Messages_Thread::mark_as_read( $thread_id, $user_id );
}

/**
 * Mark a thread as unread.
 *
 * Wrapper for {@link BP_Messages_Thread::mark_as_unread()}.
 *
 * @param int $thread_id ID of the thread.
 *
 * @return false|int Number of threads marked as unread or false on error.
 */
function messages_mark_thread_unread( $thread_id ) {
	return BP_Messages_Thread::mark_as_unread( $thread_id );
}

/**
 * Set messages-related cookies.
 *
 * Saves the 'bp_messages_send_to', 'bp_messages_subject', and
 * 'bp_messages_content' cookies, which are used when setting up the default
 * values on the messages page.
 *
 * @param string $recipients Comma-separated list of recipient usernames.
 * @param string $subject    Subject of the message.
 * @param string $content    Content of the message.
 */
function messages_add_callback_values( $recipients, $subject, $content ) {
	@setcookie( 'bp_messages_send_to', $recipients, time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp_messages_subject', $subject, time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp_messages_content', $content, time() + 60 * 60 * 24, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
}

/**
 * Unset messages-related cookies.
 *
 * @see messages_add_callback_values()
 */
function messages_remove_callback_values() {
	@setcookie( 'bp_messages_send_to', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp_messages_subject', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	@setcookie( 'bp_messages_content', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
}

/**
 * Get the unread messages count for a user.
 *
 * @param int $user_id Optional. ID of the user. Default: ID of the logged-in user.
 *
 * @return int
 */
function messages_get_unread_count( $user_id = 0 ) {
	return BP_Messages_Thread::get_inbox_count( $user_id );
}

/**
 * Get the thread unread messages count for a user.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int $thread_id Thread ID of the message.
 * @param int $user_id   Optional. ID of the user. Default: ID of the logged-in user.
 *
 * @return int
 */
function bb_get_thread_messages_unread_count( $thread_id, $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( empty( $user_id ) || empty( $thread_id ) ) {
		return;
	}

	$cache_key    = 'bb_thread_message_unread_count_' . $user_id . '_' . $thread_id;
	$unread_count = wp_cache_get( $cache_key, 'bp_messages_unread_count' );

	if ( false === $unread_count ) {
		global $wpdb;
		$bp           = buddypress();
		$unread_count = (int) $wpdb->get_col( $wpdb->prepare( "SELECT unread_count from {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d AND unread_count > 0", $user_id, $thread_id ) );
		wp_cache_set( $cache_key, $unread_count, 'bp_messages_unread_count' );
	}
	return $unread_count;
}

/**
 * Check whether a user is the sender of a message.
 *
 * @param int $user_id    ID of the user.
 * @param int $message_id ID of the message.
 *
 * @return int|null Returns the ID of the message if the user is the
 *                  sender, otherwise null.
 */
function messages_is_user_sender( $user_id, $message_id ) {
	return BP_Messages_Message::is_user_sender( $user_id, $message_id );
}

/**
 * Get the ID of the sender of a message.
 *
 * @param int $message_id ID of the message.
 *
 * @return int|null The ID of the sender if found, otherwise null.
 */
function messages_get_message_sender( $message_id ) {
	return BP_Messages_Message::get_message_sender( $message_id );
}

/**
 * Check whether a message thread exists.
 *
 * @param int $thread_id ID of the thread.
 *
 * @return false|int|null The message thread ID on success, null on failure.
 */
function messages_is_valid_thread( $thread_id ) {
	return BP_Messages_Thread::is_valid( $thread_id );
}

/**
 * Get the thread ID from a message ID.
 *
 * @since BuddyPress 2.3.0
 *
 * @param int $message_id ID of the message.
 *
 * @return int The ID of the thread if found, otherwise 0.
 */
function messages_get_message_thread_id( $message_id = 0 ) {

	$messages = BP_Messages_Message::get(
		array(
			'fields'   => 'thread_ids',
			'include'  => array( $message_id ),
			'per_page' => 1,
		)
	);

	return (int) ( ! empty( $messages['messages'] ) ? current( $messages['messages'] ) : 0 );
}

/**
 * Filter default message length. (30 characters)
 *
 * @since BuddyBoss 1.0.0
 */
function messages_get_default_subject_length() {
	return apply_filters( 'bp_messages_get_default_subject_length', 30 );
}

/** Messages Meta *******************************************************/

/**
 * Delete metadata for a message.
 *
 * If $meta_key is false, this will delete all meta for the message ID.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int         $message_id ID of the message to have meta deleted for.
 * @param string|bool $meta_key   Meta key to delete. Default false.
 * @param string|bool $meta_value Meta value to delete. Default false.
 * @param bool        $delete_all Whether or not to delete all meta data.
 *
 * @return bool True on successful delete, false on failure.
 * @see   delete_metadata() for full documentation excluding $meta_type variable.
 */
function bp_messages_delete_meta( $message_id, $meta_key = false, $meta_value = false, $delete_all = false ) {
	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		global $wpdb;

		$keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_key FROM {$wpdb->messagemeta} WHERE message_id = %d", $message_id ) );

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = false;

	// No keys, so stop now!
	if ( empty( $keys ) ) {
		return $retval;
	}

	add_filter( 'query', 'bp_filter_metaid_column_name' );

	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'message', $message_id, $key, $meta_value, $delete_all );
	}

	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get a piece of message metadata.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int    $message_id ID of the message to retrieve meta for.
 * @param string $meta_key   Meta key to retrieve. Default empty string.
 * @param bool   $single     Whether or not to fetch all or a single value.
 *
 * @return mixed
 * @see   get_metadata() for full documentation excluding $meta_type variable.
 */
function bp_messages_get_meta( $message_id, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'message', $message_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Update a piece of message metadata.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int         $message_id ID of the message to have meta deleted for.
 * @param string|bool $meta_key   Meta key to update.
 * @param string|bool $meta_value Meta value to update.
 * @param string      $prev_value If specified, only update existing metadata entries with
 *                                the specified value. Otherwise, update all entries.
 *
 * @return mixed
 * @see   update_metadata() for full documentation excluding $meta_type variable.
 */
function bp_messages_update_meta( $message_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'message', $message_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of message metadata.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int         $message_id ID of the message to have meta deleted for.
 * @param string|bool $meta_key   Meta key to update.
 * @param string|bool $meta_value Meta value to update.
 * @param bool        $unique     Whether the specified metadata key should be
 *                                unique for the object. If true, and the object
 *                                already has a value for the specified metadata key,
 *                                no change will be made.
 *
 * @return mixed
 * @see   add_metadata() for full documentation excluding $meta_type variable.
 */
function bp_messages_add_meta( $message_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'message', $message_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/** Email *********************************************************************/

/**
 * Email message recipients to alert them of a new unread private message.
 *
 * @since BuddyPress 1.0.0
 *
 * @param array|BP_Messages_Message $raw_args      {
 *                                                 Array of arguments. Also accepts a BP_Messages_Message object.
 *
 * @type array                      $recipients    User IDs of recipients.
 * @type string                     $email_subject Subject line of message.
 * @type string                     $email_content Content of message.
 * @type int                        $sender_id     User ID of sender.
 * }
 */
function messages_notification_new_message( $raw_args = array() ) {

	// Disabled the email notification if enabled "Delay Email Notifications" setting from the backend.
	if ( function_exists( 'bb_check_delay_email_notification' ) && bb_check_delay_email_notification() ) {
		return;
	}

	if ( is_object( $raw_args ) ) {
		$args = (array) $raw_args;
	} else {
		$args = $raw_args;
	}

	// These should be extracted below.
	$recipients    = array();
	$email_subject = $email_content = '';
	$sender_id     = 0;

	// Barf.
	extract( $args );

	if ( empty( $recipients ) ) {
		return;
	}

	$sender_name = bp_core_get_user_displayname( $sender_id );

	if ( ! isset( $message ) ) {
		$message = '';
	}

	$all_recipients = array();

	// check if it has enough recipients to use batch emails.
	$min_count_recipients = function_exists( 'bb_email_queue_has_min_count' ) && bb_email_queue_has_min_count( $recipients );

	$type_key = 'notification_messages_new_message';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	// Email each recipient.
	foreach ( $recipients as $recipient ) {

		if ( $sender_id == $recipient->user_id || false === bb_is_notification_enabled( $recipient->user_id, $type_key ) ) {
			continue;
		}

		// Check the sender is blocked by recipient or not.
		if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $recipient->user_id, get_current_user_id() ) ) {
			continue;
		}

		// User data and links.
		$ud = get_userdata( $recipient->user_id );
		if ( empty( $ud ) ) {
			continue;
		}

		// Disabled the notification for user who archived this thread.
		if ( isset( $recipient->is_hidden ) && $recipient->is_hidden ) {
			continue;
		}

		$unsubscribe_args = array(
			'user_id'           => $recipient->user_id,
			'notification_type' => 'messages-unread',
		);

		if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && $min_count_recipients ) {
			$all_recipients[] = array(
				'email_type' => 'messages-unread',
				'recipient'  => $ud,
				'arguments'  => array(
					'tokens' => array(
						'message_id'  => $id,
						'usermessage' => stripslashes( $message ),
						'message.url' => esc_url( bp_core_get_user_domain( $recipient->user_id ) . bp_get_messages_slug() . '/view/' . $thread_id . '/' ),
						'sender.name' => $sender_name,
						'usersubject' => sanitize_text_field( stripslashes( $subject ) ),
						'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
					),
				),
			);

		} else {
			bp_send_email(
				'messages-unread',
				$ud,
				array(
					'tokens' => array(
						'message_id'  => $id,
						'usermessage' => stripslashes( $message ),
						'message.url' => esc_url( bp_core_get_user_domain( $recipient->user_id ) . bp_get_messages_slug() . '/view/' . $thread_id . '/' ),
						'sender.name' => $sender_name,
						'usersubject' => sanitize_text_field( stripslashes( $subject ) ),
						'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
					),
				)
			);
		}
	}

	if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && ! empty( $all_recipients ) ) {
		// Added bulk data into email queue.
		bb_email_queue()->add_bulk_record( $all_recipients );

		// call email background process.
		bb_email_queue()->bb_email_background_process();
	}

	/**
	 * Fires after the sending of a new message email notification.
	 *
	 * @since            BuddyPress 1.5.0
	 *
	 * @param array  $recipients    User IDs of recipients.
	 * @param string $email_subject Deprecated in 2.5; now an empty string.
	 * @param string $email_content Deprecated in 2.5; now an empty string.
	 * @param array  $args          Array of originally provided arguments.
	 *
	 * @deprecated       2.5.0 Use the filters in BP_Email.
	 *                   $email_subject and $email_content arguments unset and deprecated.
	 */
	do_action( 'bp_messages_sent_notification_email', $recipients, '', '', $args );
}

add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

/**
 * Email message recipients to alert them of a new unread group message.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param array $raw_args
 */
function group_messages_notification_new_message( $raw_args = array() ) {

	// Disabled the email notification if enabled "Delay Email Notifications" setting from the backend.
	if ( function_exists( 'bb_check_delay_email_notification' ) && bb_check_delay_email_notification() ) {
		return;
	}

	if ( is_object( $raw_args ) ) {
		$args = (array) $raw_args;
	} else {
		$args = $raw_args;
	}

	// These should be extracted below.
	$recipients    = array();
	$email_subject = $subject = $email_content = '';
	$sender_id     = $id = 0;

	// Barf.
	extract( $args );

	if ( empty( $recipients ) ) {
		return;
	}

	$sender_name = bp_core_get_user_displayname( $sender_id );

	if ( isset( $message ) ) {
		$message = wpautop( $message );
	} else {
		$message = '';
	}

	$group      = bp_messages_get_meta( $id, 'group_id', true );
	$group_name = bp_get_group_name( groups_get_group( $group ) );

	// check if it has enough recipients to use batch emails.
	$min_count_recipients = function_exists( 'bb_email_queue_has_min_count' ) && bb_email_queue_has_min_count( $recipients );

	if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && $min_count_recipients ) {
		global $bb_background_updater;
		$chunk_recipients = array_chunk( $recipients, bb_get_email_queue_min_count() );
		if ( ! empty( $chunk_recipients ) ) {
			foreach ( $chunk_recipients as $key => $data_recipients ) {
				$bb_background_updater->data(
					array(
						'type'     => 'email',
						'group'    => 'group_messages_new_message_email',
						'data_id'  => $group,
						'priority' => 5,
						'callback' => 'bb_render_messages_recipients',
						'args'     => array(
							$data_recipients,
							'group-message-email',
							bp_get_messages_slug(),
							$thread_id,
							$sender_id,
							array(
								'message_id'  => $id,
								'usermessage' => stripslashes( $message ),
								'message'     => stripslashes( $message ),
								'sender.id'   => $sender_id,
								'sender.name' => $sender_name,
								'usersubject' => sanitize_text_field( stripslashes( $subject ) ),
								'group.name'  => $group_name,
							),
						),
					),
				);
				$bb_background_updater->save();
			}
			$bb_background_updater->dispatch();
		}
	} else {
		// Send an email to each recipient.

		$type_key = 'notification_group_messages_new_message';
		if ( ! bb_enabled_legacy_email_preference() ) {
			$type_key = bb_get_prefences_key( 'legacy', $type_key );
		}

		foreach ( $recipients as $recipient ) {

			if (
				(int) $sender_id === (int) $recipient->user_id ||
				false === bb_is_notification_enabled( $recipient->user_id, $type_key )
			) {
				continue;
			}

			// User data and links.
			$ud = get_userdata( $recipient->user_id );
			if ( empty( $ud ) ) {
				continue;
			}

			// Check the sender is blocked by recipient or not.
			if (
				function_exists( 'bb_moderation_allowed_specific_notification' ) &&
				bb_moderation_allowed_specific_notification(
					array(
						'type'              => buddypress()->messages->id,
						'group_id'          => $group,
						'recipient_user_id' => $recipient->user_id,
					)
				)
			) {
				continue;
			}

			// Disabled the notification for user who archived this thread.
			if ( isset( $recipient->is_hidden ) && $recipient->is_hidden ) {
				continue;
			}

			$unsubscribe_args = array(
				'user_id'           => $recipient->user_id,
				'notification_type' => 'group-message-email',
			);

			bp_send_email(
				'group-message-email',
				$ud,
				array(
					'tokens' => array(
						'message_id'  => $id,
						'usermessage' => stripslashes( $message ),
						'message'     => stripslashes( $message ),
						'message.url' => esc_url( bp_core_get_user_domain( $recipient->user_id ) . bp_get_messages_slug() . '/view/' . $thread_id . '/' ),
						'sender.id'   => $sender_id,
						'sender.name' => $sender_name,
						'usersubject' => sanitize_text_field( stripslashes( $subject ) ),
						'group.name'  => $group_name,
						'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
					),
				)
			);
		}
	}

	/**
	 * Fires after the sending of a new group message email notification.
	 *
	 * @since            BuddyPress 1.5.0
	 *
	 * @param array  $recipients    User IDs of recipients.
	 * @param string $email_subject Deprecated in 2.5; now an empty string.
	 * @param string $email_content Deprecated in 2.5; now an empty string.
	 * @param array  $args          Array of originally provided arguments.
	 *
	 * @deprecated       2.5.0 Use the filters in BP_Email.
	 *                   $email_subject and $email_content arguments unset and deprecated.
	 */
	do_action( 'group_messages_notification_new_message', $recipients, '', '', $args );
}


/**
 * Delete user from DB callback
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_messages_thread_delete_completely() {
	return true;
}

/**
 * When a user is deleted, we need to clean up the database and remove all the
 * message data from each table.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $user_id The ID of the deleted user.
 */
function bp_messages_remove_data( $user_id ) {
	// Delete all the messages and there meta
	BP_Messages_Message::delete_user_message( $user_id );
}

add_action( 'wpmu_delete_user', 'bp_messages_remove_data' );
add_action( 'delete_user', 'bp_messages_remove_data' );
add_action( 'bp_make_spam_user', 'bp_messages_remove_data' );

/**
 * Display Sites notices on all the page
 *
 * @since BuddyBoss 1.1.7
 */
function bp_messages_show_sites_notices() {
	if (
		( ! bp_is_directory() && ! bp_is_single_item() && bp_is_blog_page() )
		|| ( empty( bp_is_blog_page() ) && bp_is_members_component() ) // check that it's members page on the members component
	) {
		bp_nouveau_template_notices();
		wp_enqueue_script( 'bp-nouveau' );
	}
}

add_action( 'wp_footer', 'bp_messages_show_sites_notices' );

/**
 * Get Message thread avatars by thread id.
 *
 * @since BuddyBoss 1.4.7
 *
 * @param integer $thread_id Message thread id.
 * @param integer $user_id   user id.
 *
 * @return array
 */
function bp_messages_get_avatars( $thread_id, $user_id ) {
	global $wpdb;

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$avatar_urls      = array();
	$avatars_user_ids = array();
	$thread_messages  = BP_Messages_Thread::get_messages( $thread_id, null, 99999999 );
	$recepients       = BP_Messages_Thread::get_recipients_for_thread( $thread_id );

	if ( count( $recepients ) > 2 ) {
		foreach ( $thread_messages as $message ) {
			if ( $message->sender_id !== $user_id ) {

				if ( count( $avatars_user_ids ) >= 2 ) {
					continue;
				}

				if ( ! in_array( $message->sender_id, $avatars_user_ids ) ) {
					$avatars_user_ids[] = $message->sender_id;
				}
			}
		}
	} else {
		unset( $recepients[ $user_id ] );
		if ( ! empty( $recepients ) ) {
			$avatars_user_ids[] = current( $recepients )->user_id;
		}
	}

	if ( count( $recepients ) > 2 && count( $avatars_user_ids ) < 2 ) {
		unset( $recepients[ $user_id ] );
		if ( count( $avatars_user_ids ) === 0 ) {
			$avatars_user_ids = array_slice( array_keys( $recepients ), 0, 2 );
		} else {
			unset( $recepients[ $avatars_user_ids[0] ] );
			$avatars_user_ids = array_merge( $avatars_user_ids, array_slice( array_keys( $recepients ), 0, 1 ) );
		}
	}

	if ( ! empty( $avatars_user_ids ) ) {
		$avatars_user_ids = array_reverse( $avatars_user_ids );
		foreach ( (array) $avatars_user_ids as $avatar_user_id ) {
			$avatar_urls[] = array(
				'url'                => esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => $avatar_user_id,
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => BP_AVATAR_THUMB_WIDTH,
							'height'  => BP_AVATAR_THUMB_HEIGHT,
							'html'    => false,
						)
					)
				),
				'name'               => esc_attr( bp_core_get_user_displayname( $avatar_user_id ) ),
				'id'                 => esc_attr( $avatar_user_id ),
				'type'               => 'user',
				'link'               => bp_core_get_user_domain( $avatar_user_id ),
				'is_user_suspended'  => function_exists( 'bp_moderation_is_user_suspended' ) ? bp_moderation_is_user_suspended( $avatar_user_id ) : false,
				'is_user_blocked'    => function_exists( 'bp_moderation_is_user_blocked' ) ? bp_moderation_is_user_blocked( $avatar_user_id ) : false,
				'is_user_blocked_by' => function_exists( 'bb_moderation_is_user_blocked_by' ) ? bb_moderation_is_user_blocked_by( $avatar_user_id ) : false,
				'is_deleted'         => empty( get_userdata( $avatar_user_id ) ) ? 1 : 0,
				'user_presence'      => 1 === count( (array) $avatars_user_ids ) ? bb_get_user_presence_html( $avatar_user_id ) : '',
			);
		}
	}

	$first_message    = end( $thread_messages );
	$first_message_id = ( ! empty( $first_message ) ? $first_message->id : false );
	$group_id         = ( isset( $first_message_id ) ) ? (int) bp_messages_get_meta( $first_message_id, 'group_id', true ) : 0;
	if ( ! empty( $first_message_id ) && ! empty( $group_id ) ) {
		$message_from  = bp_messages_get_meta( $first_message_id, 'message_from', true ); // group.
		$message_users = bp_messages_get_meta( $first_message_id, 'group_message_users', true ); // all - individual.
		$message_type  = bp_messages_get_meta( $first_message_id, 'group_message_type', true ); // open - private.

		if ( 'group' === $message_from && 'all' === $message_users && 'open' === $message_type ) {
			if ( bp_is_active( 'groups' ) ) {
				$group            = groups_get_group( $group_id );
				$group_name       = bp_get_group_name( $group );
				$group_avatar_url = '';

				if ( ! bp_disable_group_avatar_uploads() ) {
					$group_avatar_url = bp_core_fetch_avatar(
						array(
							'item_id'    => $group_id,
							'object'     => 'group',
							'type'       => 'full',
							'avatar_dir' => 'group-avatars',
							'alt'        => sprintf( __( 'Group logo of %s', 'buddyboss' ), $group_name ),
							'title'      => $group_name,
							'html'       => false,
						)
					);
				} else {
					$group_avatar_url = bb_get_buddyboss_group_avatar();
				}

				$group_avatar = array(
					'url'  => $group_avatar_url,
					'name' => $group_name,
					'id'   => $group_id,
					'type' => 'group',
					'link' => bp_get_group_permalink( $group ),
				);

			} else {

				/**
				 *
				 * Filters table prefix.
				 *
				 * @since BuddyBoss 1.4.7
				 *
				 * @param int $wpdb ->base_prefix table prefix
				 */
				$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table             = $prefix . 'bp_groups';
				$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$default_group_avatar_url = ! bp_disable_group_avatar_uploads() ? bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) ) : bb_get_buddyboss_group_avatar();
				$legacy_group_avatar_name = '-groupavatar-full';
				$legacy_user_avatar_name  = '-avatar2';
				$group_avatar_url         = '';

				if ( ! empty( $group_name ) && ! bp_disable_group_avatar_uploads() ) {
					$directory         = 'group-avatars';
					$avatar_size       = '-bpfull';
					$avatar_folder_dir = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
					$avatar_folder_url = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

					$group_avatar_url = bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url );
				}

				$group_avatar = array(
					'url'  => ! empty( $group_avatar_url ) ? $group_avatar_url : $default_group_avatar_url,
					'name' => ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name,
					'id'   => $group_id,
					'type' => 'group',
					'link' => '',
				);
			}

			if ( ! empty( $group_avatar ) ) {
				$avatar_urls = array( $group_avatar );
			}
		}
	}

	/**
	 *
	 * Filters the avatar url array to be applied in message thread.
	 *
	 * @since BuddyBoss 1.4.7
	 *
	 * @param int   $thread_id   Message thread id
	 * @param int   $user_id     user id
	 *
	 * @param array $avatar_urls avatar urls in
	 */
	return apply_filters( 'bp_messages_get_avatars', $avatar_urls, $thread_id, $user_id );
}

/**
 * Check whether given thread is group thread or not.
 *
 * @param int $thread_id Thread id.
 *
 * @since BuddyBoss 1.5.7
 *
 * @return bool
 */
function bb_messages_is_group_thread( $thread_id ) {

	if ( ! $thread_id || ! bp_is_active( 'messages' ) ) {
		return false;
	}

	$is_group_message_thread = false;
	$first_message           = BP_Messages_Thread::get_first_message( $thread_id );
	$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
	$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
	$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
	$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

	if ( 'group' === $message_from && $thread_id === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
		$is_group_message_thread = true;
	}

	return $is_group_message_thread;
}

/**
 * Recipients per page list.
 *
 * @return int $per_page Return per page for recipients.
 *
 * @since BuddyBoss 1.7.6
 */
function bb_messages_recipients_per_page() {
	return apply_filters( 'bb_messages_recipients_per_page', 20 );
}

/**
 * Send bulk group message to the members.
 *
 * @param array  $post_data       Post data.
 * @param array  $members         Array of Member ids.
 * @param int    $current_user_id Currently logged-in user id.
 * @param string $content         Message content text.
 * @param bool   $is_background   Rendered from background process or not.
 *
 * @since BuddyBoss 1.8.0
 *
 * @return bool|int|void|WP_Error
 */
function bb_send_group_message_background( $post_data, $members = array(), $current_user_id = 0, $content = '', $is_background = false ) {

	global $bp;
	// setup post data into $_POST.
	if ( is_array( $post_data ) ) {
		$_POST = $post_data;
	}
	$message_args = array();
	$message      = '';

	if ( empty( $members ) ) {
		return;
	}

	if ( empty( bp_loggedin_user_id() ) ) {
		$bp->loggedin_user->id = $current_user_id;
	}
	// We have to send Message to all members to "Individual" message in both cases like "All Group Members" OR "Individual Members" selected.
	foreach ( $members as $member ) {

		$member_check     = array();
		$member_check[]   = $member;
		$member_check[]   = $current_user_id;
		$previous_threads = BP_Messages_Message::get_existing_threads( $member_check, $current_user_id );
		$existing_thread  = 0;
		$member_thread_id = 0;

		if ( $previous_threads ) {
			foreach ( $previous_threads as $thread ) {

				$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, $current_user_id );

				if ( $is_active_recipient ) {

					// get the thread recipients.
					$thread_recipients          = BP_Messages_Thread::get_recipients_for_thread( (int) $thread->thread_id );
					$recipients_user_ids        = ( ! empty( $thread_recipients ) ? wp_parse_id_list( array_column( json_decode( wp_json_encode( $thread_recipients ), true ), 'user_id' ) ) : array() );
					$previous_thread_recipients = ( ! empty( $recipients_user_ids ) ? array_diff( $recipients_user_ids, array( (int) $current_user_id ) ) : array() );

					$current_recipients = array();
					if ( is_array( $member ) ) {
						$current_recipients = $member;
					} else {
						$current_recipients[] = $member;
					}
					$compare_members = array();

					// Store current recipients to $members array.
					foreach ( $current_recipients as $single_recipients ) {
						$compare_members[] = (int) $single_recipients;
					}

					$first_message = BP_Messages_Thread::get_first_message( $thread->thread_id );
					$message_user  = bp_messages_get_meta( $first_message->id, 'group_message_users', true );
					$message_type  = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.

					// check both previous and current recipients are same.
					$is_recipient_match = empty( array_diff( $previous_thread_recipients, $compare_members ) );

					// If recipients are matched.
					if (
						$is_recipient_match &&
						(
							'all' !== $message_user ||
							( 'all' === $message_user && 'open' !== $message_type )
						)
					) {
						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$existing_thread = (int) $thread->thread_id;
					}
				}
			}

			if ( $existing_thread > 0 ) {
				// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
				$_POST['message_thread_type'] = 'reply';

				$member_thread_id = $existing_thread;
			} else {
				// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
				$_POST['message_thread_type'] = 'new';
			}
		} else {
			// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
			$_POST['message_thread_type'] = 'new';
		}

		/**
		 * Create Message based on the `message_thread_type` and `member_thread_id`.
		 */
		if ( isset( $_POST['message_thread_type'] ) && 'new' === $_POST['message_thread_type'] ) {
			$message_args = array(
				'sender_id'           => $current_user_id,
				'recipients'          => $member,
				'subject'             => wp_trim_words( $content, messages_get_default_subject_length() ),
				'content'             => $content,
				'append_thread'       => false,
				'message_thread_type' => 'new',
			);
		} elseif ( isset( $_POST['message_thread_type'] ) && 'reply' === $_POST['message_thread_type'] && ! empty( $member_thread_id ) ) {
			$message_args = array(
				'sender_id'           => $current_user_id,
				'thread_id'           => $member_thread_id,
				'subject'             => false,
				'content'             => $content,
				'mark_visible'        => true,
				'message_thread_type' => 'reply',
				'error_type'          => 'wp_error',
			);
		}

		if ( $is_background ) {
			add_filter( 'bb_is_email_queue', 'bb_disabled_email_queue' );
		}

		$message = bp_groups_messages_new_message( $message_args );

		if ( $is_background ) {
			remove_filter( 'bb_is_email_queue', 'bb_disabled_email_queue' );
		}
	}

	return $message;
}

/**
 * Send Group email into background.
 *
 * @since BuddyBoss 1.9.0
 *
 * @param array  $recipients   Message Recipients.
 * @param string $email_type   Email type.
 * @param string $message_slug Message Slug.
 * @param int    $thread_id    Message Thread ID.
 * @param int    $sender_id    Sender ID.
 * @param array  $tokens       Message Tokens.
 */
function bb_render_messages_recipients( $recipients, $email_type, $message_slug, $thread_id, $sender_id, $tokens = array() ) {

	if ( empty( $recipients ) ) {
		return;
	}

	// Send an email to all recipient.

	$type_key = 'notification_group_messages_new_message';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	foreach ( $recipients as $recipient ) {

		if (
			(int) $sender_id === (int) $recipient->user_id ||
			false === bb_is_notification_enabled( $recipient->user_id, $type_key )
		) {
			continue;
		}

		$group = bp_messages_get_meta( $tokens['message_id'], 'group_id', true );
		// Check the sender is blocked by recipient or not.
		if (
			function_exists( 'bb_moderation_allowed_specific_notification' ) &&
			bb_moderation_allowed_specific_notification(
				array(
					'type'              => buddypress()->messages->id,
					'group_id'          => $group,
					'recipient_user_id' => $recipient->user_id,
				)
			)
		) {
			continue;
		}

		// User data and links.
		$ud = get_userdata( $recipient->user_id );
		if ( empty( $ud ) ) {
			continue;
		}

		$unsubscribe_args = array(
			'user_id'           => $recipient->user_id,
			'notification_type' => 'group-message-email',
		);

		$tokens['message.url'] = esc_url( bp_core_get_user_domain( $recipient->user_id ) . $message_slug . '/view/' . $thread_id . '/' );
		$tokens['unsubscribe'] = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );

		bp_send_email(
			$email_type,
			$ud,
			array(
				'tokens' => $tokens,
			)
		);
	}
}

/**
 * Check last message is a joined group message.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $thread_id    Message Thread ID.
 * @param int $user_id      User ID.
 *
 * @return boolean  Is last message joined group message.
 */
function bb_is_last_message_group_join_message( $thread_id, $user_id ) {

	global $wpdb, $bp;

	if ( empty( $thread_id ) || empty( $user_id ) ) {
		return false;
	}

	$last_message    = BP_Messages_Thread::get_last_message( $thread_id, true );
	$is_join_message = bp_messages_get_meta( $last_message->id, 'group_message_group_joined' );
	if ( 'yes' === $is_join_message ) {
		$joined_user       = array(
			'user_id' => $user_id,
			'time'    => bp_core_current_time(),
		);
		$joined_users      = bp_messages_get_meta( $last_message->id, 'group_message_group_joined_users' );
		$joined_users_data = empty( $joined_users ) ? array( $joined_user ) : array_merge( $joined_users, array( $joined_user ) );
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined_users', $joined_users_data );
		return true;
	}
	return false;
}

/**
 * Check last message is a left group message.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $thread_id    Message Thread ID.
 * @param int $user_id      User ID.
 *
 * @return boolean  Is last message left group message.
 */
function bb_is_last_message_group_left_message( $thread_id, $user_id ) {

	global $wpdb, $bp;

	if ( empty( $thread_id ) || empty( $user_id ) ) {
		return false;
	}

	$last_message    = BP_Messages_Thread::get_last_message( $thread_id, true );
	$is_left_message = bp_messages_get_meta( $last_message->id, 'group_message_group_left' );
	if ( 'yes' === $is_left_message ) {
		$left_user       = array(
			'user_id' => $user_id,
			'time'    => bp_core_current_time(),
		);
		$left_users      = bp_messages_get_meta( $last_message->id, 'group_message_group_left_users' );
		$left_users_data = empty( $left_users ) ? array( $left_user ) : array_merge( $left_users, array( $left_user ) );
		bp_messages_update_meta( $last_message->id, 'group_message_group_left_users', $left_users_data );
		return true;
	}
	return false;
}

/**
 * Change friend button arguments.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $args Button arguments.
 *
 * @return array Button arguments with updated.
 */
function bb_messaged_set_friend_button_args( $args = array() ) {

	if ( isset( $args['block_self'] ) ) {
		$args['block_self'] = false;
	}

	if ( isset( $args['id'] ) && 'not_friends' === $args['id'] ) {
		$args['link_text'] = __( 'Send Connection Request', 'buddyboss' );
	} elseif ( isset( $args['id'] ) && 'pending' === $args['id'] ) {
		$args['link_href'] = '';
	}

	return $args;
}

/**
 * Update meta query when fetching the threads for user unread count.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $sub_query Array of meta query arguments.
 * @param array $r          Array of arguments.
 *
 * @return array|mixed
 */
function bb_messages_update_unread_count( $sub_query, $r ) {
	$bp = buddypress();

	if ( false === bp_disable_group_messages() || ! bp_is_active( 'groups' ) ) {
		$sub_query = "AND m.id IN ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} WHERE meta_key = 'group_message_users' AND meta_value = 'all' AND message_id IN ( SELECT DISTINCT message_id FROM {$bp->messages->table_name_meta} WHERE meta_key = 'group_message_type' AND meta_value = 'open' ) )";
	} elseif ( bp_is_active( 'groups' ) ) {
		// Determine groups of user.
		$groups = groups_get_groups(
			array(
				'fields'      => 'ids',
				'per_page'    => - 1,
				'user_id'     => $r['user_id'],
				'show_hidden' => true,
			)
		);

		$group_ids     = ( isset( $groups['groups'] ) ? $groups['groups'] : array() );
		$group_ids_sql = '';

		if ( ! empty( $group_ids ) ) {
			$group_ids_sql = implode( ',', array_unique( $group_ids ) );
			$group_ids_sql = "AND ( meta_key = 'group_id' AND meta_value NOT IN ({$group_ids_sql}) )";
		}

		$sub_query = "AND m.id IN ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} WHERE 1 =1 {$group_ids_sql} AND message_id IN ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} WHERE meta_key  = 'group_message_users' and meta_value = 'all' AND message_id in ( SELECT DISTINCT message_id from {$bp->messages->table_name_meta} where meta_key  = 'group_message_type' and meta_value = 'open' ) ) )";
	}

	return $sub_query;
}

/**
 * Checks whether a message thread is archived or not.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $thread_id ID of the thread.
 * @param int $user_id   The user ID.
 *
 * @return boolean
 */
function messages_is_valid_archived_thread( $thread_id, $user_id = 0 ) {
	return BP_Messages_Thread::is_valid_archived( $thread_id, $user_id );
}

/**
 * Checks whether thread exists through the recipients.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $recipients Username array of the user.
 * @param int   $user_id    The user ID.
 *
 * @return boolean|array
 */
function bb_messages_is_thread_exists_by_recipients( $recipients = array(), $user_id = 0 ) {

	if ( empty( $recipients ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	// Setup the recipients array.
	$recipient_ids = array();

	// Loop the recipients and convert all usernames to user_ids where needed.
	foreach ( (array) $recipients as $recipient ) {

		// Trim spaces and skip if empty.
		$recipient = trim( $recipient );
		if ( empty( $recipient ) ) {
			continue;
		}

		// Check user_login / nicename columns first
		// @see http://buddypress.trac.wordpress.org/ticket/5151.
		if ( bp_is_username_compatibility_mode() && function_exists( 'bp_core_get_userid' ) ) {
			$recipient_id = bp_core_get_userid( urldecode( $recipient ) );
		} elseif ( function_exists( 'bp_core_get_userid_from_nicename' ) ) {
			$recipient_id = bp_core_get_userid_from_nicename( $recipient );
		}

		// Check against user ID column if no match and if passed recipient is numeric.
		if ( empty( $recipient_id ) && is_numeric( $recipient ) && function_exists( 'bp_core_get_core_userdata' ) ) {
			if ( bp_core_get_core_userdata( (int) $recipient ) ) {
				$recipient_id = (int) $recipient;
			}
		}

		// If $recipient_id still blank then try last time to find $recipient_id via the nickname field.
		if ( empty( $recipient_id ) && function_exists( 'bp_core_get_userid_from_nickname' ) ) {
			$recipient_id = bp_core_get_userid_from_nickname( $recipient );
		}

		// Decide which group to add this recipient to.
		if ( ! empty( $recipient_id ) ) {
			$recipient_ids[] = (int) $recipient_id;
		}
	}

	// Strip the sender from the recipient list, and unset them if they are
	// not alone. If they are alone, let them talk to themselves.
	$self_send = array_search( $user_id, $recipient_ids, true );
	if ( ! empty( $self_send ) && ( count( $recipient_ids ) > 1 ) ) {
		unset( $recipient_ids[ $self_send ] );
	}

	// Remove duplicates & bail if no recipients.
	$recipient_ids = array_unique( $recipient_ids );
	if ( empty( $recipient_ids ) ) {
		return false;
	}

	return BP_Messages_Message::get_existing_threads( $recipient_ids, $user_id );
}

/**
 * Send digest email into background.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $recipient_messages Message array.
 * @param int   $thread_id          ID of the thread.
 */
function bb_render_digest_messages_template( $recipient_messages, $thread_id ) {
	global $wpdb;

	if ( empty( $recipient_messages ) || empty( $thread_id ) ) {
		return;
	}

	$thread_id = (int) $thread_id;

	// Get notification type key.
	$type_key = 'notification_messages_new_message';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	$email_type    = 'messages-unread-digest';
	$first_message = BP_Messages_Thread::get_first_message( $thread_id );
	$group_id      = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
	$message_users = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
	$message_type  = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
	$group_name    = '';

	if ( ! empty( $group_id ) && 'open' === $message_type && 'all' === $message_users ) {
		$email_type = 'group-message-digest';
		if ( bp_is_active( 'groups' ) ) {
			$group      = groups_get_group( $group_id );
			$group_name = bp_get_group_name( $group );
		} else {
			$prefix       = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
			$groups_table = $prefix . 'bp_groups';
			$group_name   = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok.
		}

		$group_name = ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name;

		// Get notification type key.
		$type_key = 'notification_group_messages_new_message';
		if ( ! bb_enabled_legacy_email_preference() ) {
			$type_key = bb_get_prefences_key( 'legacy', $type_key );
		}
	} else {
		$group_id = 0;
	}

	$message_slug = bp_get_messages_slug();

	static $moderation = array();
	foreach ( $recipient_messages as $messages ) {

		foreach ( $messages as $message_key => $message ) {

			// Check the sender is blocked by recipient or not.
			$cache_key = $message['recipients_id'] . '-' . $message['sender_id'];
			if ( ! isset( $moderation[ $cache_key ] ) ) {
				$moderation[ $cache_key ] = (bool) apply_filters( 'bb_is_recipient_moderated', false, $message['recipients_id'], $message['sender_id'] );
			}

			if ( true === $moderation[ $cache_key ] ) {
				unset( $messages[ $message_key ] );
			}
		}

		if ( empty( $messages ) ) {
			continue;
		}

		$current_message = current( $messages );
		$recipients_id   = isset( $current_message['recipients_id'] ) ? $current_message['recipients_id'] : 0;

		if ( empty( $recipients_id ) ) {
			continue;
		}

		// Notification enabled or not.
		if ( false === bb_is_notification_enabled( $recipients_id, $type_key ) ) {
			continue;
		}

		// User data and links.
		$ud = get_userdata( $recipients_id );
		if ( empty( $ud ) ) {
			continue;
		}

		if ( 1 === count( $messages ) ) {
			if ( ! empty( $group_id ) ) {
				$email_type = 'group-message-email';
			} else {
				$email_type = 'messages-unread';
			}
		}

		$unsubscribe_args = array(
			'user_id'           => $recipients_id,
			'notification_type' => $email_type,
		);

		$tokens                = array();
		$tokens['usersubject'] = isset( $first_message->subject ) ? $first_message->subject : '';
		$tokens['group.id']    = $group_id;
		$tokens['group.name']  = $group_name;
		$tokens['message.url'] = esc_url( bp_core_get_user_domain( $recipients_id ) . $message_slug . '/view/' . $thread_id . '/' );
		$tokens['unsubscribe'] = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );

		if ( in_array( $email_type, array( 'messages-unread', 'group-message-email' ), true ) ) {
			$messages    = current( $messages );
			$sender_name = bp_core_get_user_displayname( $messages['sender_id'] );

			$tokens['message_id']  = $messages['message_id'];
			$tokens['usermessage'] = stripslashes( $messages['message'] );
			$tokens['message']     = stripslashes( $messages['message'] );
			$tokens['sender.name'] = $sender_name;
			$tokens['sender.id']   = $messages['sender_id'];
		} else {
			$tokens['unread.count'] = count( $messages );
			// Slice array to get last five records.
			$messages          = array_slice( $messages, - 5 );
			$tokens['message'] = $messages;
		}

		bp_send_email(
			$email_type,
			$ud,
			array(
				'tokens' => $tokens,
			)
		);
	}
}

/**
 * Get thread id from message id.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $message_id Message ID.
 *
 * @return integer
 */
function bb_get_thread_id_by_message_id( $message_id ) {
	global $wpdb;
	$bp = buddypress();

	if ( empty( $message_id ) ) {
		return 0;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$sql = $wpdb->prepare( "SELECT thread_id FROM {$bp->messages->table_name_messages} WHERE id = %d LIMIT 1", $message_id );

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->get_var( $sql );
}

/**
 * Function to search value by minutes from the bb_get_delay_notification_times function.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $time Notification delay time.
 *
 * @return array
 */
function bb_get_delay_notification_time_by_minutes( $time = 15 ) {
	$get_delay_times     = bb_notification_get_digest_cron_times();
	$search_schedule_key = array_search( (int) $time, array_column( $get_delay_times, 'value' ), true );

	if ( isset( $get_delay_times[ $search_schedule_key ] ) ) {
		return $get_delay_times[ $search_schedule_key ];
	}

	return array();
}

/**
 * Schedule digest notification action times.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return array
 */
function bb_notification_get_digest_cron_times() {

	$delay_times = array(
		array(
			'label'        => sprintf(
			/* translators: %s: The admin setting field label. */
				__( '%d mins', 'buddyboss' ),
				5
			),
			'value'        => 5,
			'schedule_key' => 'bb_schedule_5min',
		),
		array(
			'label'        => sprintf(
			/* translators: %s: The admin setting field label. */
				__( '%d mins', 'buddyboss' ),
				15
			),
			'value'        => 15,
			'schedule_key' => 'bb_schedule_15min',
		),
		array(
			'label'        => sprintf(
			/* translators: %s: The admin setting field label. */
				__( '%d mins', 'buddyboss' ),
				30
			),
			'value'        => 30,
			'schedule_key' => 'bb_schedule_30min',
		),
		array(
			'label'        => sprintf(
			/* translators: %s: The admin setting field label. */
				__( '%d hour', 'buddyboss' ),
				1
			),
			'value'        => 60,
			'schedule_key' => 'bb_schedule_1hour',
		),
		array(
			'label'        => sprintf(
			/* translators: %s: The admin setting field label. */
				__( '%d hours', 'buddyboss' ),
				3
			),
			'value'        => 180,
			'schedule_key' => 'bb_schedule_3hours',
		),
		array(
			'label'        => sprintf(
			/* translators: %s: The admin setting field label. */
				__( '%d hours', 'buddyboss' ),
				12
			),
			'value'        => 720,
			'schedule_key' => 'bb_schedule_12hours',
		),
		array(
			'label'        => sprintf(
			/* translators: %s: The admin setting field label. */
				__( '%d hours', 'buddyboss' ),
				24
			),
			'value'        => 1440,
			'schedule_key' => 'bb_schedule_24hours',
		),
	);

	return apply_filters( 'bb_notification_get_digest_cron_times', $delay_times );
}

/**
 * Prepare the object for the Messsage response.
 *
 * @since  BuddyBoss 2.2
 *
 * @param object $message Message object.
 *
 * @return array
 */
function bb_get_message_response_object( $message ) {

	global $media_template, $video_template, $document_template;
	$content   = $message->message;
	$sender_id = $message->sender_id;
	// If user was deleted, mark content as deleted.
	if ( false === bp_core_get_core_userdata( $sender_id ) ) {
		$content = esc_html__( 'This message was deleted', 'buddyboss' );
	}
	$content    = preg_replace( '#(<p></p>)#', '<p><br></p>', apply_filters( 'bp_get_the_thread_message_content', $content ) );
	$excerpt    = apply_filters( 'bb_get_the_thread_message_excerpt', preg_replace( '#(<br\s*?\/?>|</(\w+)><(\w+)>)#', ' ', $content ) );
	$message_id = $message->id;
	$excerpt    = wp_trim_words( wp_strip_all_tags( $excerpt ) );
	$thread_id  = $message->thread_id;

	$sender_display_name = bp_core_get_user_displayname( $sender_id );

	if ( empty( $sender_display_name ) ) {
		$sender_display_name = __( 'Deleted User', 'buddyboss' );
	}
	$sender_display_name = apply_filters( 'bp_get_the_thread_message_sender_name', $sender_display_name );

	// Check message media, document, video, GIF access.
	$has_message_media_access    = bb_user_has_access_upload_media( 0, $sender_id, 0, $thread_id, 'message' );
	$has_message_document_access = bb_user_has_access_upload_document( 0, $sender_id, 0, $thread_id, 'message' );
	$has_message_video_access    = bb_user_has_access_upload_video( 0, $sender_id, 0, $thread_id, 'message' );
	$has_message_gif_access      = bb_user_has_access_upload_gif( 0, $sender_id, 0, $thread_id, 'message' );

	$has_media = false;
	if ( empty( $excerpt ) ) {
		if ( bp_is_active( 'media' ) && $has_message_media_access ) {
			$media_ids = bp_messages_get_meta( $message_id, 'bp_media_ids', true );

			if ( ! empty( $media_ids ) ) {
				$has_media = true;
				$media_ids = explode( ',', $media_ids );
				if ( count( $media_ids ) < 2 ) {
					$excerpt = __( 'Sent a photo', 'buddyboss' );
				} else {
					$excerpt = __( 'Sent some photos', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'media' ) && $has_message_video_access ) {
			$video_ids = bp_messages_get_meta( $message_id, 'bp_video_ids', true );

			if ( ! empty( $video_ids ) ) {
				$has_media = true;
				$video_ids = explode( ',', $video_ids );
				if ( count( $video_ids ) < 2 ) {
					$excerpt = __( 'Sent a video', 'buddyboss' );
				} else {
					$excerpt = __( 'Sent some videos', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'media' ) && $has_message_document_access ) {
			$document_ids = bp_messages_get_meta( $message_id, 'bp_document_ids', true );

			if ( ! empty( $document_ids ) ) {
				$has_media    = true;
				$document_ids = explode( ',', $document_ids );
				if ( count( $document_ids ) < 2 ) {
					$excerpt = __( 'Sent a document', 'buddyboss' );
				} else {
					$excerpt = __( 'Sent some documents', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'media' ) && $has_message_gif_access ) {
			$gif_data = bp_messages_get_meta( $message_id, '_gif_data', true );

			if ( ! empty( $gif_data ) ) {
				$has_media = true;
				$excerpt   = __( 'Sent a gif', 'buddyboss' );
			}
		}
	}

	$sent_date_formatted = $message->date_sent;
	$site_sent_date      = get_date_from_gmt( $sent_date_formatted );
	$sent_time           = apply_filters( 'bb_get_the_thread_message_sent_time', date_i18n( 'g:i A', strtotime( $site_sent_date ) ) );

	// Output single message template part.
	$reply = array(
		'id'                => $message_id,
		'thread_id'         => $thread_id,
		'content'           => do_shortcode( $content ),
		'sender_id'         => $sender_id,
		'sender_name'       => esc_html( $sender_display_name ),
		'is_deleted'        => empty( get_userdata( $sender_id ) ) ? 1 : 0,
		'sender_link'       => apply_filters( 'bp_get_the_thread_message_sender_link', bp_core_get_userlink( $sender_id, false, true ) ),
		'sender_is_you'     => $sender_id === bp_loggedin_user_id(),
		'sender_avatar'     => esc_url(
			bp_core_fetch_avatar(
				array(
					'item_id' => $sender_id,
					'object'  => 'user',
					'type'    => 'thumb',
					'width'   => 32,
					'height'  => 32,
					'html'    => false,
				)
			)
		),
		'date'              => apply_filters( 'bp_get_the_thread_message_date_sent', strtotime( $message->date_sent ) ) * 1000,
		'display_date'      => $sent_time,
		'display_date_list' => bb_get_thread_sent_date( $message->date_sent ),
		'excerpt'           => $excerpt,
		'sent_date'         => ucfirst( bb_get_thread_start_date( $message->date_sent ) ),
		'sent_split_date'   => date_i18n( 'Y-m-d', strtotime( $message->date_sent ) ),
		'refresh_element'   => true,
		'has_media'         => $has_media,
	);

	$get_thread_recipients = BP_Messages_Thread::get_recipients_for_thread( (int) $thread_id );

	if ( bp_is_active( 'moderation' ) ) {
		$reply['is_user_suspended'] = bp_moderation_is_user_suspended( $sender_id );
		$reply['is_user_blocked']   = bp_moderation_is_user_blocked( $sender_id );
	}

	if ( bp_is_active( 'messages', 'star' ) ) {

		$star_link = bp_get_the_message_star_action_link(
			array(
				'message_id' => $message_id,
				'url_only'   => true,
			)
		);

		$reply['star_link']  = $star_link;
		$reply['is_starred'] = array_search( 'unstar', explode( '/', $star_link ), true );

	}

	if ( bp_is_active( 'media' ) && $has_message_media_access ) {
		$media_ids = bp_messages_get_meta( $message_id, 'bp_media_ids', true );

		if ( ! empty( $media_ids ) && bp_has_media(
				array(
					'include'  => $media_ids,
					'privacy'  => array( 'message' ),
					'order_by' => 'menu_order',
					'sort'     => 'ASC',
					'per_page' => 0,
				)
			) ) {
			$reply['media'] = array();
			while ( bp_media() ) {
				bp_the_media();

				$reply['media'][] = array(
					'id'            => bp_get_media_id(),
					'title'         => bp_get_media_title(),
					'message_id'    => bp_get_the_thread_message_id(),
					'thread_id'     => $thread_id,
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

	if ( bp_is_active( 'video' ) && $has_message_video_access ) {
		$video_ids = bp_messages_get_meta( $message_id, 'bp_video_ids', true );

		if (
			! empty( $video_ids ) &&
			bp_has_video(
				array(
					'include'  => $video_ids,
					'privacy'  => array( 'message' ),
					'order_by' => 'menu_order',
					'sort'     => 'ASC',
					'per_page' => 0,
				)
			)
		) {
			$reply['video'] = array();
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

				$reply['video'][] = array(
					'id'            => bp_get_video_id(),
					'title'         => bp_get_video_title(),
					'message_id'    => bp_get_the_thread_message_id(),
					'thread_id'     => $thread_id,
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

	if ( bp_is_active( 'media' ) && $has_message_document_access ) {
		$document_ids = bp_messages_get_meta( $message_id, 'bp_document_ids', true );

		if ( ! empty( $document_ids ) && bp_has_document(
				array(
					'include'  => $document_ids,
					'order_by' => 'menu_order',
					'sort'     => 'ASC',
					'per_page' => 0,
				)
			) ) {
			$reply['document'] = array();
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
				$extension_lists       = bp_document_extensions_list();
				$text_attachment_url   = wp_get_attachment_url( $attachment_id );
				$mirror_text           = bp_document_mirror_text( $attachment_id );
				$audio_url             = '';
				$video_url             = '';

				if ( ! empty( $extension_lists ) ) {
					$extension_lists = array_column( $extension_lists, 'description', 'extension' );
					$extension_name  = '.' . $extension;
					if ( ! empty( $extension_lists ) && ! empty( $extension ) && array_key_exists( $extension_name, $extension_lists ) ) {
						$extension_description = '<span class="document-extension-description">' . esc_html( $extension_lists[ $extension_name ] ) . '</span>';
					}
				}

				if ( in_array( $extension, bp_get_document_preview_video_extensions(), true ) ) {
					$video_url = bb_document_video_get_symlink( bp_get_document_id(), true );
				}

				if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
					$audio_url = bp_document_get_preview_url( bp_get_document_id(), $attachment_id );
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

				$reply['document'][] = array(
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
					'privacy'               => bp_get_db_document_privacy(),
					'author'                => bp_get_document_user_id(),
					'preview'               => $attachment_url,
					'full_preview'          => ( '' !== $full_attachment_url ) ? $full_attachment_url : $attachment_url,
					'msg_preview'           => $output,
					'text_preview'          => $text_attachment_url ? esc_url( $text_attachment_url ) : '',
					'mp3_preview'           => $audio_url ? $audio_url : '',
					'document_title'        => $filename ? $filename : '',
					'mirror_text'           => $mirror_text ? $mirror_text : '',
					'video'                 => $video_url ? $video_url : '',
				);
			}
		}
	}

	if ( bp_is_active( 'media' ) && $has_message_gif_access ) {
		$gif_data = bp_messages_get_meta( $message_id, '_gif_data', true );

		if ( ! empty( $gif_data ) ) {
			$preview_url  = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
			$video_url    = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];
			$reply['gif'] = array(
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
		$reply = array_merge( $reply, $extra_content );
	}

	// set a flag.
	$reply['is_new']  = true;
	$inbox_unread_cnt = apply_filters( 'thread_recipient_inbox_unread_counts', array(), $get_thread_recipients );
	$hash             = ! empty( $_POST['hash'] ) ? wp_unslash( $_POST['hash'] ) : '';

	return array(
		'messages'                      => array( $reply ),
		'thread_id'                     => $thread_id,
		'feedback'                      => __( 'Your reply was sent successfully', 'buddyboss' ),
		'hash'                          => $hash,
		'recipient_inbox_unread_counts' => $inbox_unread_cnt,
		'type'                          => 'success',
	);
}

/**
 * Prepare the string to display list member joined or left the group in the message screen.
 *
 * @since BuddyBoss 2.2.7
 *
 * @param array $args The ID of message.
 *
 * @return string
 */
function bb_messages_get_group_join_leave_text( $args ) {
	$content = '';

	$r = bp_parse_args(
		$args,
		array(
			'thread_id'  => 0,
			'message_id' => 0,
			'user_id'    => get_current_user_id(),
			'sender_id'  => 0,
			'group_name' => '',
			'type'       => 'joined',
		)
	);

	// Set default message if meta doesn't exist.
	if ( ! empty( $r['sender_id'] ) ) {
		/* translators: 1. Member Name. */
		$content = sprintf(
			( 'left' === $r['type'] ? __( '%1$s left the group.', 'buddyboss' ) : __( '%1$s joined the group.', 'buddyboss' ) ),
			'<strong>' . bp_core_get_user_displayname( $r['sender_id'] ) . '</strong>'
		);
	}

	if ( empty( $r['message_id'] ) ) {
		return $content;
	}

	$users = array();
	if ( 'joined' === $r['type'] ) {
		$users = bp_messages_get_meta( $r['message_id'], 'group_message_group_joined_users' );
	} elseif ( 'left' === $r['type'] ) {
		$users = bp_messages_get_meta( $r['message_id'], 'group_message_group_left_users' );
	}

	if ( empty( $users ) ) {
		return $content;
	}

	/*
	 * Member 1 : John joined/left the group
	 * Member 2 : John and Charles joined/left the group
	 * Member 3+ : John joined/left the group, along with 2 others.
	 * Member 3-6 : When hovering over “2 others”,  show tooltip with list of members.
	 * Member 7+ : When hovering over “2 others”,  show tooltip with list of members. When clicking on “6 others”, open members modal showing all members who are included in the join/leave status.
	 */

	if ( is_array( $users ) ) {
		$users   = array_filter( array_column( $users, 'user_id' ) );
		$content = __( 'Left group', 'buddyboss' );

		/*
		 * Member 1 : John joined/left the group
		 */
		if ( 1 === count( $users ) ) {
			$user_id = ! empty( current( $users ) ) ? current( $users ) : 0;
			if ( ! empty( $user_id ) ) {
				/* translators: 1. Member Name. */
				$content = sprintf(
					( 'left' === $r['type'] ? __( '%1$s left the group.', 'buddyboss' ) : __( '%1$s joined the group.', 'buddyboss' ) ),
					'<strong>' . bp_core_get_user_displayname( $user_id ) . '</strong>'
				);
			}

		/*
		 * Member 2 : John and Charles joined/left the group
		 */
		} elseif ( 2 === count( $users ) ) {
			$first_user_id = ! empty( current( $users ) ) ? current( $users ) : 0;
			$last_user_id  = ! empty( end( $users ) ) ? end( $users ) : 0;

			if ( ! empty( $first_user_id ) && ! empty( $last_user_id ) ) {
				/* translators: 1. Member Name. 2. Member Name. */
				$content = sprintf(
					( 'left' === $r['type'] ? __( '%1$s and %2$s left the group.', 'buddyboss' ) : __( '%1$s and %2$s joined the group.', 'buddyboss' ) ),
					'<strong>' . bp_core_get_user_displayname( $first_user_id ) . '</strong>',
					'<strong>' . bp_core_get_user_displayname( $last_user_id ) . '</strong>'
				);
			} elseif ( ! empty( $first_user_id ) ) {
				/* translators: 1. Member Name */
				$content = sprintf(
					( 'left' === $r['type'] ? __( '%1$s left the group.', 'buddyboss' ) : __( '%1$s joined the group.', 'buddyboss' ) ),
					'<strong>' . bp_core_get_user_displayname( $first_user_id ) . '</strong>',
				);
			} elseif ( ! empty( $last_user_id ) ) {
				/* translators: 1. Member Name. */
				$content = sprintf(
					( 'left' === $r['type'] ? __( '%1$s left the group.', 'buddyboss' ) : __( '%1$s joined the group.', 'buddyboss' ) ),
					'<strong>' . bp_core_get_user_displayname( $last_user_id ) . '</strong>'
				);
			}

		/*
		 * Member 3+ : John joined/left the group, along with 2 others.
		 *  -> Member 3-6 : When hovering over “2 others”,  show tooltip with list of members.
		 *  -> Member 7+ : When hovering over “2 others”,  show tooltip with list of members. When clicking on “6 others”, open members modal showing all members who are included in the join/leave status.
		 */
		} elseif ( 3 <= count( $users ) ) {
			$total_user_ids = count( $users );
			$first_user_id  = ! empty( current( $users ) ) ? current( $users ) : 0;
			unset( $users[0] );

			// Display only 5 members name in the tooltips.
			$first_five_members = array_filter( array_slice( $users, 0, 5 ) );
			$member_names       = array_map(
				function ( $user_id ) {
					return bp_core_get_user_displayname( $user_id );
				},
					$first_five_members
			);
			$member_names       = implode( ', ', $member_names );
			if ( 6 < $total_user_ids ) {
				$member_names = $member_names . '&hellip;';
			}

			// If 3-6 members then show tooltip with list of members.
			/* translators: 1. Other member list, 2. Other member count. */
			$to_others = sprintf(
				'<strong class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="%1$s">%2$s</strong>',
				$member_names,
				sprintf(
					__( '%d others', 'buddyboss' ),
					( $total_user_ids - 1 )
				)
			);

			// If 7+ members then show tooltip with list of members and open member modal when click on count.
			if ( 7 <= $total_user_ids && ! empty( $r['thread_id'] ) ) {
				/* translators: 1. Thread ID, 2. Message ID, 3. Message type, 4. Other member list. */
				$to_others = sprintf(
					'<a href="#message-members-list" class="view_other_members" data-thread-id="%1$d" data-message-id="%2$d" data-message-type="%3$s" data-action="bp_view_others">%4$s</a>',
					$r['thread_id'],
					$r['message_id'],
					$r['type'],
					$to_others
				);
			}

			/* translators: 1. Member Name, 2. Other member list. */
			$content = sprintf(
				( 'left' === $r['type'] ? __( '%1$s left the group, along with %2$s', 'buddyboss' ) : __( '%1$s joined the group, along with %2$s', 'buddyboss' ) ),
				'<strong>' . bp_core_get_user_displayname( $first_user_id ) . '</strong>',
				$to_others
			);
		}
	}

	return '<p class="joined">' . $content . '</p>';
}


/**
 * Check if user can send message.
 *
 * @since BuddyBoss 2.3.90
 *
 * @param array $args An array of arguments.
 *
 * @return bool|WP_Error
 */
function bb_messages_user_can_send_message( $args = array() ) {
	$can_send_message = false;

	$r = bp_parse_args(
		$args,
		array(
			'sender_id'     => 0,
			'recipients_id' => 0,
			'thread_id'     => 0,
			'group_id'      => 0,
		),
	);

	// Bail if no sender.
	if ( empty( $r['sender_id'] ) ) {
		return apply_filters( 'bb_messages_user_can_send_message', false, $r );
	}

	$sender_id      = $r['sender_id'];
	$recipients_ids = ! empty( $r['recipients_id'] ) && is_int( $r['recipients_id'] ) ? array( $r['recipients_id'] ) : $r['recipients_id'];
	$thread_id      = $r['thread_id'];

	// Check the sender has the capability to send message.
	if (
		! empty( $thread_id ) &&
		! messages_check_thread_access( $thread_id, $sender_id ) &&
		! bp_current_user_can( 'bp_moderate' )
	) {
		return apply_filters( 'bb_messages_user_can_send_message', false, $r );
	}

	// If no recipients, check if the thread has recipients.
	if ( 0 === $recipients_ids && ! empty( $thread_id ) ) {
		$recipients_ids     = BP_Messages_Thread::get_recipients_for_thread( $thread_id );
		$recipients_ids     = wp_list_pluck( $recipients_ids, 'user_id' );
		$r['recipients_id'] = $recipients_ids;
	}

	// Strip the sender from the recipient list.
	if ( ! empty( $recipients_ids ) ) {
		$recipients_ids = array_unique( $recipients_ids );
		$key            = array_search( $sender_id, $recipients_ids, true );
		if ( false !== $key ) {
			unset( $recipients_ids[ $key ] );
			$recipients_ids = array_values( $recipients_ids );
		}
	}

	// Bail if no recipients.
	if ( empty( $recipients_ids ) ) {
		return apply_filters( 'bb_messages_user_can_send_message', false, $r );
	}

	$is_group_message_thread = false;

	// Check if the thread is a group message thread.
	if ( ! empty( $thread_id ) ) {
		$is_group_message_thread = (bool) bb_messages_is_group_thread( (int) $thread_id );
		if ( $is_group_message_thread ) {
			return apply_filters( 'bb_messages_user_can_send_message', true, $r );
		}
	}

	// Check recipients if connected or not.
	if (
		false === $is_group_message_thread &&
		bp_is_active( 'friends' ) &&
		bp_force_friendship_to_message() &&
		1 === count( $recipients_ids )
	) {

		// Check if the sender is allowed to send message to the recipient or vice a versa based on the member type settings.
		if (
			bb_messages_allowed_messaging_without_connection( (int) $sender_id ) ||
			bb_messages_allowed_messaging_without_connection( (int) current( $recipients_ids ) )
		) {
			$can_send_message = true;
			// Check if the sender is connected to the recipient.
		} elseif ( friends_check_friendship( (int) $sender_id, (int) current( $recipients_ids ) ) ) {
			$can_send_message = true;
		}
	} else {
		$can_send_message = true;
	}

	// Check moderation if user blocked or not for single user thread.
	if (
		false === $is_group_message_thread &&
		bp_is_active( 'moderation' ) &&
		count( $recipients_ids ) === 1
	) {
		if ( bp_moderation_is_user_suspended( current( $recipients_ids ) ) ) {
			return apply_filters( 'bb_messages_user_can_send_message', false, $r );
		} elseif ( function_exists( 'bb_moderation_is_user_blocked_by' ) && bb_moderation_is_user_blocked_by( current( $recipients_ids ) ) ) {
			return apply_filters( 'bb_messages_user_can_send_message', false, $r );
		} elseif ( bp_moderation_is_user_blocked( current( $recipients_ids ) ) ) {
			return apply_filters( 'bb_messages_user_can_send_message', false, $r );
		}
	}

	// Check the access control settings.
	if ( ! empty( $r['group_id'] ) && count( $recipients_ids ) === 1 ) {
		$can_send_message = (bool) ( $is_group_message_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : apply_filters( 'bb_user_can_send_group_message', $can_send_message, current( $recipients_ids ), $sender_id );
	} else {
		$can_send_message = (bool) ( $is_group_message_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : bb_user_can_send_messages( $can_send_message, $recipients_ids, '' );
	}

	return apply_filters( 'bb_messages_user_can_send_message', $can_send_message, $r );
}

/**
 * Check if sender can send message without connection.
 *
 * @since BuddyBoss 2.3.90
 *
 * @param int $user_id User ID.
 *
 * @return bool
 */
function bb_messages_allowed_messaging_without_connection( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( empty( $user_id ) || false === bp_member_type_enable_disable() ) {
		return false;
	}

	$profile_types_allowed_messaging = get_option( 'bp_member_types_allowed_messaging_without_connection', array() );
	$member_profile_type             = bp_get_member_type( $user_id );
	if (
		! empty( $member_profile_type ) &&
		! empty( $profile_types_allowed_messaging ) &&
		array_key_exists( $member_profile_type, $profile_types_allowed_messaging ) &&
		true === $profile_types_allowed_messaging[ $member_profile_type ]
	) {
		return true;
	}

	return false;
}

/**
 * Filter only those message recipients to those are allowed to send message.
 *
 * @since BuddyBoss 2.3.90
 *
 * @param array         $sql   Clauses in the user_id SQL query.
 * @param BP_User_Query $query User query object.
 *
 * @return array
 */
function bb_messages_update_recipient_user_query_uid_clauses( $sql, BP_User_Query $query ) {
	if (
		bp_is_active( 'friends' ) &&
		bp_force_friendship_to_message() &&
		! empty( $sql['where']['search'] ) &&
		'ID' === $query->uid_name &&
		strpos( $sql['where']['search'], "u.$query->uid_name IN" ) > 1
	) {
		$pattern = '/u\.ID\s+IN\s+\((\d+(?:,\d+)*)\)/';
		preg_match_all( $pattern, $sql['where']['search'], $matches );

		$user_ids = array();
		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $match ) {
				$user_ids = array_merge( $user_ids, wp_parse_id_list( $match ) );
			}
		}

		$filtered_user_ids = array();
		$friend_ids        = friends_get_friend_user_ids( bp_loggedin_user_id() );

		foreach ( $user_ids as $user_id ) {
			if ( bb_messages_allowed_messaging_without_connection( $user_id ) ) {
				$filtered_user_ids[] = $user_id;
				continue;
			}
			if ( ! empty( $friend_ids ) && in_array( $user_id, $friend_ids, true ) ) {
				$filtered_user_ids[] = $user_id;
			}
		}

		$sql['where']['search'] = '';
		if ( ! empty( $filtered_user_ids ) ) {
			$filtered_user_ids      = implode( ',', $filtered_user_ids );
			$sql['where']['search'] = "u.{$query->uid_name} IN ({$filtered_user_ids})";
		}
	}

	return $sql;
}

/**
 * Run migration for resolving the issue related to the messages.
 *
 * @since BuddyBoss 2.4.30
 *
 * @return void
 */
function bb_messages_migration() {
	global $wpdb;
	$db_prefix = bp_core_get_table_prefix();

	/**
	 * Run migration for resolving group message thread meta fix.
	 *
	 * @since BuddyBoss 2.4.30
	 */
	$message      = $db_prefix . 'bp_messages_messages';
	$message_meta = $db_prefix . 'bp_messages_meta';

	$sql  = "SELECT m.id, m.thread_id FROM {$message} m";
	$sql .= " INNER JOIN {$message_meta} mm ON mm.message_id = m.id AND ( (mm.meta_key = '%s' OR mm.meta_key = '%s') AND mm.meta_value = '%s' ) ";
	$sql .= " LEFT JOIN {$message_meta} mm_users ON mm_users.message_id = m.id AND mm_users.meta_key = '%s'";
	$sql .= " LEFT JOIN {$message_meta} mm_type ON mm_type.message_id = m.id AND mm_type.meta_key = '%s'";
	$sql .= ' WHERE mm_users.message_id IS NULL AND mm_type.message_id IS NULL';

	// Retrieve all messages that are missing the required specified metadata.
	$messages = $wpdb->get_results( $wpdb->prepare( $sql, 'group_message_group_joined', 'group_message_group_left', 'yes', 'group_message_users', 'group_message_type' ) ); // phpcs:ignore

	if ( ! empty( $messages ) ) {
		foreach ( $messages as $message ) {
			$first_message = BP_Messages_Thread::get_first_message( $message->thread_id );
			$message_users = bp_messages_get_meta( $first_message->id, 'group_message_users', true );
			$message_type  = bp_messages_get_meta( $first_message->id, 'group_message_type', true );

			bp_messages_update_meta( $message->id, 'group_message_users', $message_users );
			bp_messages_update_meta( $message->id, 'group_message_type', $message_type );
		}
	}
}
