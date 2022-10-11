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

	// Parse the default arguments.
	$r = bp_parse_args(
		$args,
		array(
			'sender_id'     => bp_loggedin_user_id(),
			'thread_id'     => false,   // False for a new message, thread id for a reply to a thread.
			'recipients'    => array(), // Can be an array of usernames, user_ids or mixed.
			'subject'       => false,
			'content'       => false,
			'date_sent'     => bp_core_current_time(),
			'append_thread' => true,
			'is_hidden'     => false,
			'mark_visible'  => false,
			'group_thread'  => false,
			'error_type'    => 'bool',
		),
		'messages_new_message'
	);

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
	$validated_content = (bool) apply_filters( 'bp_messages_message_validated_content', ! empty( $r['content'] ) && strlen( trim( html_entity_decode( wp_strip_all_tags( $r['content'] ) ) ) ), $r['content'], $_POST );

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
		$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
		if ( ! $can_send_media ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'You don\'t have access to send the media. ', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		}
	}

	if ( ! empty( $_POST['document'] ) ) {
		$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
		if ( ! $can_send_document ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'You don\'t have access to send the document. ', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		}
	}

	if ( ! empty( $_POST['video'] ) ) {
		$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
		if ( ! $can_send_video ) {
			$error_code = 'messages_empty_content';
			$feedback   = __( 'You don\'t have access to send the video. ', 'buddyboss' );
			return new WP_Error( $error_code, $feedback );
		}
	}

	if ( ! empty( $_POST['gif_data'] ) ) {
		$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), 0, $r['thread_id'], 'message' );
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

		$previous_threads = BP_Messages_Message::get_existing_threads( $recipient_ids, $r['sender_id'] );
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
	if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) && true !== $is_group_thread ) {

		$error_messages = array(
			'new_message'       => __( 'You need to be connected with this member in order to send a message.', 'buddyboss' ),
			'new_reply'         => __( 'You need to be connected with this member to continue this conversation.', 'buddyboss' ),
			'new_group_message' => __( 'You need to be connected with all recipients in order to send them a message.', 'buddyboss' ),
			'new_group_reply'   => __( 'You need to be connected with all recipients to continue this conversation.', 'buddyboss' ),
		);

		foreach ( (array) $message->recipients as $i => $recipient ) {
			if ( ! friends_check_friendship( $message->sender_id, $recipient->user_id ) ) {
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
				return new WP_Error( 'message_generic_error', __( 'Message was not sent. Please try again.', 'buddyboss' ) );
			}
		}

		return false;
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
 *
 * @return false|int Number of threads marked as read or false on error.
 */
function messages_mark_thread_read( $thread_id ) {
	return BP_Messages_Thread::mark_as_read( $thread_id );
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

	if ( isset( $message ) ) {
		$message = wpautop( $message );
	} else {
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
		global $bb_email_background_updater;
		$chunk_recipients = array_chunk( $recipients, 10 );
		if ( ! empty( $chunk_recipients ) ) {
			foreach ( $chunk_recipients as $key => $data_recipients ) {

				// Check the sender is blocked by recipient or not.
				if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $data_recipients->user_id, $sender_id ) ) {
					continue;
				}

				$bb_email_background_updater->data(
					array(
						array(
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
									'sender.name' => $sender_name,
									'usersubject' => sanitize_text_field( stripslashes( $subject ) ),
									'group.name'  => $group_name,
								),
							),
						),
					)
				);
				$bb_email_background_updater->save();
			}
			$bb_email_background_updater->dispatch();
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
			if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $recipient->user_id, $sender_id ) ) {
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
				'url'  => esc_url(
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
				'name' => esc_attr( bp_core_get_user_displayname( $avatar_user_id ) ),
			);
		}
	}

	$first_message    = end( $thread_messages );
	$first_message_id = ( ! empty( $first_message ) ? $first_message->id : false );
	$group_id         = ( isset( $first_message_id ) ) ? (int) bp_messages_get_meta( $first_message_id, 'group_id', true ) : 0;
	if ( ! empty( $first_message_id ) && ! empty( $group_id ) ) {
		$message_from  = bp_messages_get_meta( $first_message_id, 'message_from', true ); // group
		$message_users = bp_messages_get_meta( $first_message_id, 'group_message_users', true ); // all - individual
		$message_type  = bp_messages_get_meta( $first_message_id, 'group_message_type', true ); // open - private

		if ( 'group' === $message_from && 'all' === $message_users && 'open' === $message_type ) {
			if ( bp_is_active( 'groups' ) ) {

				$group_name       = bp_get_group_name( groups_get_group( $group_id ) );
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

	// setup post data into $_POST.
	if ( is_array( $post_data ) ) {
		$_POST = $post_data;
	}
	$message_args = array();
	$message      = '';

	if ( empty( $members ) ) {
		return;
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
				'is_hidden'           => true,
				'append_thread'       => false,
				'message_thread_type' => 'new',
			);
		} elseif ( isset( $_POST['message_thread_type'] ) && 'reply' === $_POST['message_thread_type'] && ! empty( $member_thread_id ) ) {
			$message_args = array(
				'sender_id'           => $current_user_id,
				'thread_id'           => $member_thread_id,
				'subject'             => wp_trim_words( $content, messages_get_default_subject_length() ),
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
