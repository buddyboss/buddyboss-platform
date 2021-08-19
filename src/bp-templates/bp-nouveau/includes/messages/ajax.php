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
	 * @param bool   $validated_content True if message is not valid, false otherwise.
	 * @param string $content           Content of the message.
	 * @param array  $_POST             POST Request Object.
	 *
	 * @return bool True if message is not valid, false otherwise.
	 */
	$validated_content = (bool) apply_filters( 'bp_messages_message_validated_content', ! empty( $content ) && strlen( trim( html_entity_decode( wp_strip_all_tags( $content ) ) ) ), $content, $_POST );

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

	// Attempt to send the message.
	$send = messages_new_message(
		array(
			'recipients'   => $recipients,
			'subject'      => wp_trim_words( $_POST['message_content'], messages_get_default_subject_length() ),
			'content'      => $_POST['message_content'],
			'error_type'   => 'wp_error',
			'mark_visible' => true,
		)
	);

	// Send the message.
	if ( true === is_int( $send ) ) {
		$response = array();

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

				$can_message     = ( $is_group_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : apply_filters( 'bb_can_user_send_message_in_thread', true, $messages_template->thread->thread_id, (array) $messages_template->thread->recipients );
				$un_access_users = array();
				if ( $can_message && ! $is_group_thread && bp_is_active( 'friends' ) && bp_force_friendship_to_message() ) {
					foreach ( (array) $messages_template->thread->recipients as $recipient ) {
						if ( bp_loggedin_user_id() !== $recipient->user_id ) {
							if ( ! friends_check_friendship( bp_loggedin_user_id(), $recipient->user_id ) ) {
								$un_access_users[] = false;
							}
						}
					}
					if ( ! empty( $un_access_users ) ) {
						$can_message = false;
					}
				}

				$check_recipients = (array) $messages_template->thread->recipients;
				// Strip the sender from the recipient list, and unset them if they are
				// not alone. If they are alone, let them talk to themselves.
				if ( isset( $check_recipients[ bp_loggedin_user_id() ] ) && ( count( $check_recipients ) > 1 ) ) {
					unset( $check_recipients[ bp_loggedin_user_id() ] );
				}

				// Check moderation if user blocked or not for single user thread.
				if ( $can_message && ! $is_group_thread && bp_is_active( 'moderation' ) && ! empty( $check_recipients ) && 1 === count( $check_recipients ) ) {
					$recipient_id = current( array_keys( $check_recipients ) );
					if ( bp_moderation_is_user_suspended( $recipient_id ) ) {
						$can_message = false;
					} elseif ( bp_moderation_is_user_blocked( $recipient_id ) ) {
						$can_message = false;
					}
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
					'sender_is_you'                   => $messages_template->thread->last_sender_id == bp_loggedin_user_id(),
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
					foreach ( $messages_template->thread->recipients as $recipient ) {
						if ( empty( $recipient->is_deleted ) ) {
							$response['recipients'][] = array(
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
								'is_you'     => $recipient->user_id === bp_loggedin_user_id(),
							);
						}
					}
				}

				if ( bp_is_active( 'moderation' ) ) {
					$response['is_user_suspended'] = bp_moderation_is_user_suspended( $messages_template->thread->last_sender_id );
					$response['is_user_blocked']   = bp_moderation_is_user_blocked( $messages_template->thread->last_sender_id );
				}

				if ( bp_is_active( 'messages', 'star' ) ) {
					$star_link = bp_get_the_message_star_action_link(
						array(
							'thread_id' => bp_get_message_thread_id(),
							'url_only'  => true,
						)
					);

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
			}
		}

		if ( empty( $response ) ) {
			$response = array( 'id' => $send );
		}

		wp_send_json_success(
			array(
				'feedback' => __( 'Message successfully sent.', 'buddyboss' ),
				'type'     => 'success',
				'thread'   => $response,
			)
		);

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

	// Verify nonce.
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'messages_send_message' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['thread_id'] ) ) {
		$response['feedback'] = __( 'Please provide thread id.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	$content = filter_input( INPUT_POST, 'content', FILTER_DEFAULT );

	/**
	 * Filter to validate message content.
	 *
	 * @param bool   $validated_content True if message is not valid, false otherwise.
	 * @param string $content           Content of the message.
	 * @param array  $_POST             POST Request Object.
	 *
	 * @return bool True if message is not valid, false otherwise.
	 */
	$validated_content = (bool) apply_filters( 'bp_messages_message_validated_content', ! empty( $content ) && strlen( trim( html_entity_decode( wp_strip_all_tags( $content ) ) ) ), $content, $_POST );

	if ( ! $validated_content ) {
		$response['feedback'] = __( 'Please add some content to your message.', 'buddyboss' );

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
			wp_send_json_error( $response );
		}
	}

	if ( ! empty( $_POST['document'] ) ) {
		$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
		if ( ! $can_send_document ) {
			$response['feedback'] = __( 'You don\'t have access to send the media. ', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	if ( ! empty( $_POST['video'] ) ) {
		$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
		if ( ! $can_send_video ) {
			$response['feedback'] = __( 'You don\'t have access to send the media. ', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	if ( ! empty( $_POST['gif_data'] ) ) {
		$can_send_document = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
		if ( ! $can_send_document ) {
			$response['feedback'] = __( 'You don\'t have access to send the gif. ', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	$new_reply = messages_new_message(
		array(
			'thread_id'    => $thread_id,
			'subject'      => ! empty( $_POST['subject'] ) ? $_POST['subject'] : false,
			'content'      => $_POST['content'],
			'date_sent'    => $date_sent = bp_core_current_time(),
			'mark_visible' => true,
			'error_type'   => 'wp_error',
		)
	);

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

	// Set current message to current key.
	$thread_template->current_message = - 1;

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
		'is_deleted'    => empty( get_userdata( bp_get_the_thread_message_sender_id() ) ) ? 1 : 0,
		'sender_link'   => bp_get_the_thread_message_sender_link(),
		'sender_is_you' => bp_get_the_thread_message_sender_id() === bp_loggedin_user_id(),
		'sender_avatar' => esc_url(
			bp_core_fetch_avatar(
				array(
					'item_id' => bp_get_the_thread_message_sender_id(),
					'object'  => 'user',
					'type'    => 'thumb',
					'width'   => 32,
					'height'  => 32,
					'html'    => false,
				)
			)
		),
		'date'          => bp_get_the_thread_message_date_sent() * 1000,
		'display_date'  => bp_get_the_thread_message_time_since(),
	);

	if ( bp_is_active( 'moderation' ) ) {
		$reply['is_user_suspended'] = bp_moderation_is_user_suspended( bp_get_the_thread_message_sender_id() );
		$reply['is_user_blocked']   = bp_moderation_is_user_blocked( bp_get_the_thread_message_sender_id() );
	}

	if ( bp_is_active( 'messages', 'star' ) ) {

		$star_link = bp_get_the_message_star_action_link(
			array(
				'message_id' => bp_get_the_thread_message_id(),
				'url_only'   => true,
			)
		);

		$reply['star_link']  = $star_link;
		$reply['is_starred'] = array_search( 'unstar', explode( '/', $star_link ) );

	}

	if ( bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() ) {
		$media_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_media_ids', true );

		if ( ! empty( $media_ids ) && bp_has_media(
			array(
				'include'  => $media_ids,
				'privacy'  => array( 'message' ),
				'order_by' => 'menu_order',
				'sort'     => 'ASC',
			)
		) ) {
			$reply['media'] = array();
			while ( bp_media() ) {
				bp_the_media();

				$reply['media'][] = array(
					'id'            => bp_get_media_id(),
					'title'         => bp_get_media_title(),
					'message_id'    => bp_get_the_thread_message_id(),
					'thread_id'     => bp_get_the_thread_id(),
					'attachment_id' => bp_get_media_attachment_id(),
					'thumbnail'     => bp_get_media_attachment_image_thumbnail(),
					'full'          => bp_get_media_attachment_image(),
					'meta'          => $media_template->media->attachment_data->meta,
					'privacy'       => bp_get_media_privacy(),
				);
			}
		}
	}

	if ( bp_is_active( 'video' ) && bp_is_messages_video_support_enabled() ) {
		$video_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_video_ids', true );

		if (
			! empty( $video_ids ) &&
			bp_has_video(
				array(
					'include'  => $video_ids,
					'privacy'  => array( 'message' ),
					'order_by' => 'menu_order',
					'sort'     => 'ASC',
				)
			)
		) {
			$reply['video'] = array();
			while ( bp_video() ) {
				bp_the_video();

				$reply['video'][] = array(
					'id'            => bp_get_video_id(),
					'title'         => bp_get_video_title(),
					'message_id'    => bp_get_the_thread_message_id(),
					'thread_id'     => bp_get_the_thread_id(),
					'attachment_id' => bp_get_video_attachment_id(),
					'thumbnail'     => bp_get_video_attachment_image_thumbnail(),
					'full'          => bp_get_video_attachment_image(),
					'meta'          => $video_template->video->attachment_data->meta,
					'privacy'       => bp_get_video_privacy(),
				);
			}
		}
	}

	if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() ) {
		$document_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_document_ids', true );

		if ( ! empty( $document_ids ) && bp_has_document(
			array(
				'include'  => $document_ids,
				'order_by' => 'menu_order',
				'sort'     => 'ASC',
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

				if ( ! empty( $extension_lists ) ) {
					$extension_lists = array_column( $extension_lists, 'description', 'extension' );
					$extension_name  = '.' . $extension;
					if ( ! empty( $extension_lists ) && ! empty( $extension ) && array_key_exists( $extension_name, $extension_lists ) ) {
						$extension_description = '<span class="document-extension-description">' . esc_html( $extension_lists[ $extension_name ] ) . '</span>';
					}
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

				if ( $attachment_url ) {
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
								<textarea class="document-text-file-data-hidden"
										  style="display: none;"><?php echo wp_kses_post( $file_data ); ?></textarea>
							</div>
							<div class="document-expand">
								<a href="#" class="document-expand-anchor"><i
											class="bb-icon-plus document-icon-plus"></i> <?php esc_html_e( 'Click to expand', 'buddyboss' ); ?>
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
				);
			}
		}
	}

	if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) {
		$gif_data = bp_messages_get_meta( bp_get_the_thread_message_id(), '_gif_data', true );

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

	// Clean up the loop.
	bp_thread_messages();

	// Remove the bp_current_action() override.
	$bp->current_action = $reset_action;

	// set a flag
	$reply['is_new'] = true;

	wp_send_json_success(
		array(
			'messages'  => array( $reply ),
			'thread_id' => $thread_id,
			'feedback'  => __( 'Your reply was sent successfully', 'buddyboss' ),
			'type'      => 'success',
		)
	);

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

	// Simulate the loop.
	if ( ! bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) {
		// Remove the bp_current_action() override.
		$bp->current_action = $reset_action;

		wp_send_json_error(
			array(
				'feedback' => __( 'Sorry, no messages were found.', 'buddyboss' ),
				'type'     => 'info',
			)
		);
	}

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

	while ( bp_message_threads() ) :
		bp_message_thread();

		if ( '' === trim( wp_strip_all_tags( do_shortcode( bp_get_message_thread_content() ) ) ) ) {
			foreach ( $messages_template->thread->messages as $message ) {
				$content = trim( wp_strip_all_tags( do_shortcode( $message->message ) ) );
				if ( '' !== $content ) {

					$messages_template->thread->last_message_id      = $message->id;
					$messages_template->thread->thread_id            = $message->thread_id;
					$messages_template->thread->last_message_subject = $message->subject;
					$messages_template->thread->last_message_content = $message->message;
					$messages_template->thread->last_sender_id       = $message->sender_id;
					$messages_template->thread->last_message_date    = $message->date_sent;

					break;
				}
			}
			if ( '' === $content ) {
				$thread_messages = BP_Messages_Thread::get_messages( bp_get_message_thread_id(), null, 99999999 );
				foreach ( $thread_messages as $thread_message ) {
					$content = trim( wp_strip_all_tags( do_shortcode( $thread_message->message ) ) );
					if ( '' !== $content ) {
						$messages_template->thread->last_message_id      = $thread_message->id;
						$messages_template->thread->thread_id            = $thread_message->thread_id;
						$messages_template->thread->last_message_subject = $thread_message->subject;
						$messages_template->thread->last_message_content = $thread_message->message;
						$messages_template->thread->last_sender_id       = $thread_message->sender_id;
						$messages_template->thread->last_message_date    = $thread_message->date_sent;
						break;
					}
				}
			}
		}

		$last_message_id           = (int) $messages_template->thread->last_message_id;
		$group_id                  = bp_messages_get_meta( $last_message_id, 'group_id', true );
		$group_name                = '';
		$group_avatar              = '';
		$group_link                = '';
		$group_message_users       = '';
		$group_message_type        = '';
		$group_message_thread_type = '';
		$group_message_fresh       = '';

		if ( ! empty( $group_id ) ) {
			$group_message_users       = bp_messages_get_meta( $last_message_id, 'group_message_users', true );
			$group_message_type        = bp_messages_get_meta( $last_message_id, 'group_message_type', true );
			$group_message_thread_type = bp_messages_get_meta( $last_message_id, 'group_message_thread_type', true );
			$group_message_fresh       = bp_messages_get_meta( $last_message_id, 'group_message_fresh', true );

			if ( bp_is_active( 'groups' ) ) {
				$group_name = bp_get_group_name( groups_get_group( $group_id ) );
				if ( empty( $group_name ) ) {
					$group_link = 'javascript:void(0);';
				} else {
					$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
				}

				$group_avatar = bp_core_fetch_avatar(
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

				$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table             = $prefix . 'bp_groups';
				$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$group_link               = 'javascript:void(0);';
				$group_avatar             = buddypress()->plugin_url . 'bp-core/images/mystery-group.png';
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

		$is_deleted_group = 0;
		if ( ! $group_id ) {
			$first_message = BP_Messages_Thread::get_first_message( bp_get_message_thread_id() );
			$group_id      = ( isset( $first_message->id ) ) ? (int) bp_messages_get_meta( $first_message->id, 'group_id', true ) : 0;

			if ( $group_id ) {
				if ( bp_is_active( 'groups' ) ) {
					$group_name   = bp_get_group_name( groups_get_group( $group_id ) );
					$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
					$group_avatar = bp_core_fetch_avatar(
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

					$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
					$groups_table             = $prefix . 'bp_groups';
					$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
					$group_link               = 'javascript:void(0);';
					$group_avatar             = buddypress()->plugin_url . 'bp-core/images/mystery-group.png';
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
		}

		$is_group_thread = 0;
		if ( (int) $group_id > 0 ) {

			$first_message           = BP_Messages_Thread::get_first_message( bp_get_message_thread_id() );
			$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
			$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
			$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
			$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
			$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

			if ( 'group' === $message_from && bp_get_message_thread_id() === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
				$is_group_thread = 1;
			}
		}

		if ( ! $last_message_id ) {
			continue;
		}

		$can_message     = ( $is_group_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : apply_filters( 'bb_can_user_send_message_in_thread', true, $messages_template->thread->thread_id, (array) $messages_template->thread->recipients );
		$un_access_users = array();
		if ( $can_message && ! $is_group_thread && bp_is_active( 'friends' ) && bp_force_friendship_to_message() ) {
			foreach ( (array) $messages_template->thread->recipients as $recipient ) {
				if ( bp_loggedin_user_id() !== $recipient->user_id ) {
					if ( ! friends_check_friendship( bp_loggedin_user_id(), $recipient->user_id ) ) {
						$un_access_users[] = false;
					}
				}
			}
			if ( ! empty( $un_access_users ) ) {
				$can_message = false;
			}
		}

		$check_recipients = (array) $messages_template->thread->recipients;
		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		if ( isset( $check_recipients[ bp_loggedin_user_id() ] ) && ( count( $check_recipients ) > 1 ) ) {
			unset( $check_recipients[ bp_loggedin_user_id() ] );
		}

		// Check moderation if user blocked or not for single user thread.
		if ( $can_message && ! $is_group_thread && bp_is_active( 'moderation' ) && ! empty( $check_recipients ) && 1 === count( $check_recipients ) ) {
			$recipient_id = current( array_keys( $check_recipients ) );
			if ( bp_moderation_is_user_suspended( $recipient_id ) ) {
				$can_message = false;
			} elseif ( bp_moderation_is_user_blocked( $recipient_id ) ) {
				$can_message = false;
			}
		}

		$threads->threads[ $i ] = array(
			'id'                              => bp_get_message_thread_id(),
			'message_id'                      => (int) $last_message_id,
			'subject'                         => wp_strip_all_tags( bp_get_message_thread_subject() ),
			'group_avatar'                    => $group_avatar,
			'group_name'                      => html_entity_decode( $group_name ),
			'is_deleted'                      => $is_deleted_group,
			'is_group'                        => ! empty( $group_id ) ? true : false,
			'is_group_thread'                 => $is_group_thread,
			'group_link'                      => $group_link,
			'group_message_users'             => $group_message_users,
			'group_message_type'              => $group_message_type,
			'can_user_send_message_in_thread' => $can_message,
			'group_message_thread_type'       => $group_message_thread_type,
			'group_message_fresh'             => $group_message_fresh,
			'excerpt'                         => wp_strip_all_tags( bp_get_message_thread_excerpt() ),
			'content'                         => do_shortcode( bp_get_message_thread_content() ),
			'unread'                          => bp_message_thread_has_unread(),
			'sender_name'                     => bp_core_get_user_displayname( $messages_template->thread->last_sender_id ),
			'sender_is_you'                   => $messages_template->thread->last_sender_id === bp_loggedin_user_id(),
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
			foreach ( $messages_template->thread->recipients as $recipient ) {
				if ( empty( $recipient->is_deleted ) ) {
					$threads->threads[ $i ]['recipients'][ $count ] = array(
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
						'is_you'     => $recipient->user_id === bp_loggedin_user_id(),
					);

					if ( bp_is_active( 'moderation' ) ) {
						$threads->threads[ $i ]['recipients'][ $count ]['is_user_suspended'] = bp_moderation_is_user_suspended( $recipient->user_id );
						$threads->threads[ $i ]['recipients'][ $count ]['is_user_blocked']   = bp_moderation_is_user_blocked( $recipient->user_id );
						$threads->threads[ $i ]['recipients'][ $count ]['can_be_blocked']    = ( ! in_array( $recipient->user_id, $admins, true ) ) ? true : false;
					}

					$count ++;
				}
			}
		}

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link(
				array(
					'thread_id' => bp_get_message_thread_id(),
					'url_only'  => true,
				)
			);

			$threads->threads[ $i ]['star_link'] = $star_link;

			$star_link_data                       = explode( '/', $star_link );
			$threads->threads[ $i ]['is_starred'] = array_search( 'unstar', $star_link_data );

			// Defaults to last.
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
				if ( count( $media_ids ) < 2 ) {
					$threads->threads[ $i ]['excerpt'] = __( 'sent a photo', 'buddyboss' );
				} else {
					$threads->threads[ $i ]['excerpt'] = __( 'sent some photos', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'media' ) && bp_is_messages_video_support_enabled() ) {
			$video_ids = bp_messages_get_meta( $last_message_id, 'bp_video_ids', true );

			if ( ! empty( $video_ids ) ) {
				$video_ids = explode( ',', $video_ids );
				if ( sizeof( $video_ids ) < 2 ) {
					$threads->threads[ $i ]['excerpt'] = __( 'sent a video', 'buddyboss' );
				} else {
					$threads->threads[ $i ]['excerpt'] = __( 'sent some videos', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'media' ) && bp_is_messages_document_support_enabled() ) {
			$document_ids = bp_messages_get_meta( $last_message_id, 'bp_document_ids', true );

			if ( ! empty( $document_ids ) ) {
				$document_ids = explode( ',', $document_ids );
				if ( count( $document_ids ) < 2 ) {
					$threads->threads[ $i ]['excerpt'] = __( 'sent a document', 'buddyboss' );
				} else {
					$threads->threads[ $i ]['excerpt'] = __( 'sent some documents', 'buddyboss' );
				}
			}
		}

		if ( bp_is_active( 'media' ) && bp_is_messages_gif_support_enabled() ) {
			$gif_data = bp_messages_get_meta( $last_message_id, '_gif_data', true );

			if ( ! empty( $gif_data ) ) {
				$threads->threads[ $i ]['excerpt'] = __( 'sent a gif', 'buddyboss' );
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
		$threads->threads[ $i ]['avatars']   = bp_messages_get_avatars( bp_get_message_thread_id(), bp_loggedin_user_id() );

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

	// Mark thread as read.
	messages_mark_thread_read( $thread_id );

	// Mark latest message as read.
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id(
			bp_loggedin_user_id(),
			(int) $message_id,
			buddypress()->messages->id,
			'new_message'
		);
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

	// Mark thread active if it's in hidden mode.
	$result = $wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 0, $thread_id, bp_loggedin_user_id() ) );

	$post   = $_POST;
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

	wp_send_json_success(
		array(
			'id'             => $thread_id,
			'type'           => 'success',
			'messages'       => 'Messages successfully deleted.',
			'messages_count' => bp_get_message_thread_total_count( $thread_id ),
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

		if ( bp_is_active( 'notifications' ) ) {
			// Delete Message Notifications.
			bp_messages_message_delete_notifications( $thread_id, $message_ids );
		}

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
		do_action( 'bp_messages_message_delete_thread', $thread_id );
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

	// Use global nonce for bulk actions involving more than one id
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

	add_filter( 'bp_members_suggestions_query_args', 'bp_nouveau_ajax_search_recipients_exclude_current' );

	$results = bp_core_get_suggestions(
		array(
			'term'         => sanitize_text_field( $_GET['term'] ),
			'type'         => 'members',
			'only_friends' => bp_is_active( 'friends' ) && bp_force_friendship_to_message(),
		)
	);

	$results = apply_filters( 'bp_members_suggestions_results', $results );

	wp_send_json_success(
		array(
			'results' => array_map(
				function ( $result ) {
					return array(
						'id'   => "@{$result->ID}",
						'text' => $result->name,
					);
				},
				$results
			),
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

	$bp           = buddypress();
	$reset_action = $bp->current_action;
	$group_id     = 0;

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

	bp_get_thread( array( 'thread_id' => $thread_id ) );

	$thread = new stdClass();

	$recipients = (array) $thread_template->thread->recipients;
	// Strip the sender from the recipient list, and unset them if they are
	// not alone. If they are alone, let them talk to themselves.
	if ( isset( $recipients[ bp_loggedin_user_id() ] ) && ( count( $recipients ) > 1 ) ) {
		unset( $recipients[ bp_loggedin_user_id() ] );
	}

	// Check recipients if connected or not.
	if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {

		foreach ( $recipients as $recipient ) {
			if ( bp_loggedin_user_id() != $recipient->user_id && ! friends_check_friendship( bp_loggedin_user_id(), $recipient->user_id ) ) {
				if ( count( $recipients ) > 1 ) {
					$thread->feedback_error = array(
						'feedback' => __( 'You need to be connected with all recipients to continue this conversation.', 'buddyboss' ),
						'type'     => 'info',
					);
				} else {
					$thread->feedback_error = array(
						'feedback' => __( 'You need to be connected with this member to continue this conversation.', 'buddyboss' ),
						'type'     => 'info',
					);
				}
				break;
			}
		}
	}

	// Check moderation if user blocked or not for single user thread.
	if ( bp_is_active( 'moderation' ) && ! empty( $recipients ) && 1 === count( $recipients ) ) {
		$recipient_id = current( array_keys( $recipients ) );

		if ( bp_moderation_is_user_suspended( $recipient_id ) ) {
			$thread->feedback_error = array(
				'feedback' => __( "You can't message suspended member.", 'buddyboss' ),
				'type'     => 'info',
			);
		} elseif ( bp_moderation_is_user_blocked( $recipient_id ) ) {
			$thread->feedback_error = array(
				'feedback' => __( "You can't message a blocked member.", 'buddyboss' ),
				'type'     => 'info',
			);
		}
	}

	$last_message_id           = $thread_template->thread->messages[0]->id;
	$group_name                = '';
	$group_avatar              = '';
	$group_link                = '';
	$group_message_users       = '';
	$group_message_type        = '';
	$group_message_thread_type = '';
	$group_message_fresh       = '';
	$first_message             = BP_Messages_Thread::get_first_message( bp_get_the_thread_id() );
	$group_id                  = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
	$message_from              = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.
	$is_group_message_thread   = bb_messages_is_group_thread( bp_get_the_thread_id() );

	if ( ! $is_group_message_thread ) {
		$thread = bb_user_can_send_messages( $thread, (array) $thread_template->thread->recipients, '' );
	}

	$is_deleted_group = 0;
	if ( ! empty( $group_id ) ) {
		$group_message_users       = bp_messages_get_meta( $last_message_id, 'group_message_users', true );
		$group_message_type        = bp_messages_get_meta( $last_message_id, 'group_message_type', true );
		$group_message_thread_type = bp_messages_get_meta( $last_message_id, 'group_message_thread_type', true );
		$group_message_fresh       = bp_messages_get_meta( $last_message_id, 'group_message_fresh', true );
		$message_from              = bp_messages_get_meta( $last_message_id, 'message_from', true );

		if ( bp_is_active( 'groups' ) ) {
			$group_name = bp_get_group_name( groups_get_group( $group_id ) );
			if ( empty( $group_name ) ) {
				$group_link = 'javascript:void(0);';
			} else {
				$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
			}
			$group_avatar = bp_core_fetch_avatar(
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

			$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
			$groups_table             = $prefix . 'bp_groups';
			$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
			$group_link               = 'javascript:void(0);';
			$group_avatar             = buddypress()->plugin_url . 'bp-core/images/mystery-group.png';
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
		'thread_id' => $thread_id,
		'per_page'  => isset( $post['per_page'] ) && $post['per_page'] ? $post['per_page'] : 10,
		'before'    => isset( $post['before'] ) && $post['before'] ? $post['before'] : null,
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
				$group_name   = bp_get_group_name( groups_get_group( $group_id ) );
				$group_link   = bp_get_group_permalink( groups_get_group( $group_id ) );
				$group_avatar = bp_core_fetch_avatar(
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

				$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table             = $prefix . 'bp_groups';
				$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$group_link               = 'javascript:void(0);';
				$group_avatar             = buddypress()->plugin_url . 'bp-core/images/mystery-group.png';
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

		if ( 'group' === $message_from && bp_get_the_thread_id() === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
			$is_group_thread = 1;
			unset( $thread->feedback_error );
		}
	}

	$subject_deleted_text = apply_filters( 'delete_user_message_subject_text', 'Deleted' );
	$participated         = BP_Messages_Message::get(
		array(
			'fields'          => 'ids',
			'include_threads' => array( $thread_template->thread->thread_id ),
			'user_id'         => bp_loggedin_user_id(),
			'subject'         => $subject_deleted_text,
			'orderby'         => 'id',
			'per_page'        => - 1,
		)
	);

	$is_participated = ( ! empty( $participated['messages'] ) ? $participated['messages'] : array() );

	$can_message     = ( $is_group_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : apply_filters( 'bb_can_user_send_message_in_thread', true, $thread_template->thread->thread_id, (array) $thread_template->thread->recipients );
	$un_access_users = array();
	if ( $can_message && ! $is_group_thread && bp_is_active( 'friends' ) && bp_force_friendship_to_message() ) {
		foreach ( (array) $thread_template->thread->recipients as $recipient ) {
			if ( bp_loggedin_user_id() !== $recipient->user_id ) {
				if ( ! friends_check_friendship( bp_loggedin_user_id(), $recipient->user_id ) ) {
					$un_access_users[] = false;
				}
			}
		}
		if ( ! empty( $un_access_users ) ) {
			$can_message = false;
		}
	}

	$check_recipients = (array) $thread_template->thread->recipients;
	// Strip the sender from the recipient list, and unset them if they are
	// not alone. If they are alone, let them talk to themselves.
	if ( isset( $check_recipients[ bp_loggedin_user_id() ] ) && ( count( $check_recipients ) > 1 ) ) {
		unset( $check_recipients[ bp_loggedin_user_id() ] );
	}

	// Check moderation if user blocked or not for single user thread.
	if ( $can_message && ! $is_group_thread && bp_is_active( 'moderation' ) && ! empty( $check_recipients ) && 1 === count( $check_recipients ) ) {
		$recipient_id = current( array_keys( $check_recipients ) );
		if ( bp_moderation_is_user_suspended( $recipient_id ) ) {
			$can_message = false;
		} elseif ( bp_moderation_is_user_blocked( $recipient_id ) ) {
			$can_message = false;
		}
	}

	$thread->thread = array(
		'id'                              => bp_get_the_thread_id(),
		'subject'                         => wp_strip_all_tags( bp_get_the_thread_subject() ),
		'started_date'                    => bp_nouveau_get_message_date( $thread_template->thread->first_message_date, get_option( 'date_format' ) ),
		'group_id'                        => $group_id,
		'group_name'                      => html_entity_decode( ucwords( $group_name ) ),
		'is_group_thread'                 => $is_group_thread,
		'can_user_send_message_in_thread' => $can_message,
		'is_deleted'                      => $is_deleted_group,
		'group_avatar'                    => $group_avatar,
		'group_link'                      => $group_link,
		'group_message_users'             => $group_message_users,
		'group_message_type'              => $group_message_type,
		'group_message_thread_type'       => $group_message_thread_type,
		'group_message_fresh'             => $group_message_fresh,
		'message_from'                    => $message_from,
		'is_participated'                 => empty( $is_participated ) ? 0 : 1,
	);

	if ( is_array( $thread_template->thread->recipients ) ) {
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
		foreach ( $thread_template->thread->recipients as $recipient ) {
			if ( empty( $recipient->is_deleted ) ) {
				$thread->thread['recipients'][ $count ] = array(
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
					'is_you'     => $recipient->user_id === bp_loggedin_user_id(),
					'id'         => $recipient->user_id,
				);

				if ( bp_is_active( 'moderation' ) ) {
					$thread->thread['recipients'][ $count ]['is_blocked']     = bp_moderation_is_user_blocked( $recipient->user_id );
					$thread->thread['recipients'][ $count ]['can_be_blocked'] = ( ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
				}

				$count ++;
			}
		}
	}

	$thread->messages = array();
	$i                = 0;

	while ( bp_thread_messages() ) :
		bp_thread_the_message();

		$group_id                  = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_id', true );
		$group_message_users       = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_users', true );
		$group_message_type        = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_type', true );
		$group_message_thread_type = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_thread_type', true );
		$group_message_fresh       = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_fresh', true );
		$message_from              = bp_messages_get_meta( bp_get_the_thread_message_id(), 'message_from', true );
		$message_left              = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_left', true );
		$message_joined            = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_joined', true );
		$message_banned            = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_ban', true );
		$message_unbanned          = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_un_ban', true );
		$message_deleted           = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_messages_deleted', true );

		if ( $group_id && $message_from && 'group' === $message_from ) {

			if ( bp_is_active( 'groups' ) ) {
				$group_name = bp_get_group_name( groups_get_group( $group_id ) );
				if ( empty( $group_name ) ) {
					$group_link = 'javascript:void(0);';
				} else {
					$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
				}
				$group_avatar = bp_core_fetch_avatar(
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

				$prefix                   = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table             = $prefix . 'bp_groups';
				$group_name               = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$group_link               = 'javascript:void(0);';
				$group_avatar             = buddypress()->plugin_url . 'bp-core/images/mystery-group.png';
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
				if ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group %s to all group members.', 'buddyboss' ), $group_name );
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group %s to the people in this conversation.', 'buddyboss' ), $group_name );
				} elseif ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group %s individually to all group members.', 'buddyboss' ), $group_name );
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group %s to individual members.', 'buddyboss' ), $group_name );
				}
			} else {
				if ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group <a href="%1$s">%2$s</a> to all group members.', 'buddyboss' ), $group_link, $group_name );
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group <a href="%1$s">%2$s</a> to the people in this conversation.', 'buddyboss' ), $group_link, $group_name );
				} elseif ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group <a href="%1$s">%2$s</a> individually to all group members.', 'buddyboss' ), $group_link, $group_name );
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf( __( 'Sent from group <a href="%1$s">%2$s</a> to individual members.', 'buddyboss' ), $group_link, $group_name );
				}
			}

			if ( $message_left && 'yes' === $message_left ) {
				$content = sprintf( __( '<p class="joined">Left "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_deleted && 'yes' === $message_deleted ) {
				$content = '<p class="joined">' . __( 'This message was deleted.', 'buddyboss' ) . '</p>';
			} elseif ( $message_unbanned && 'yes' === $message_unbanned ) {
				$content = sprintf( __( '<p class="joined">Removed Ban "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_banned && 'yes' === $message_banned ) {
				$content = sprintf( __( '<p class="joined">Ban "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_joined && 'yes' === $message_joined ) {
				$content = sprintf( __( '<p class="joined">Joined "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( 'This message was deleted.' === wp_strip_all_tags( bp_get_the_thread_message_content() ) ) {
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
				'id'                        => bp_get_the_thread_message_id(),
				'content'                   => $content,
				'sender_id'                 => bp_get_the_thread_message_sender_id(),
				'sender_name'               => esc_html( bp_get_the_thread_message_sender_name() ),
				'sender_link'               => bp_get_the_thread_message_sender_link(),
				'sender_is_you'             => bp_get_the_thread_message_sender_id() === bp_loggedin_user_id(),
				'sender_avatar'             => esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => bp_get_the_thread_message_sender_id(),
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => 32,
							'height'  => 32,
							'html'    => false,
						)
					)
				),
				'date'                      => bp_get_the_thread_message_date_sent() * 1000,
				'display_date'              => bp_get_the_thread_message_time_since(),
			);

		} else {

			$message_left     = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_left', true );
			$message_joined   = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_joined', true );
			$message_banned   = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_ban', true );
			$message_unbanned = bp_messages_get_meta( bp_get_the_thread_message_id(), 'group_message_group_un_ban', true );
			$message_deleted  = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_messages_deleted', true );

			if ( $message_left && 'yes' === $message_left ) {
				$content = sprintf( __( '<p class="joined">Left "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_deleted && 'yes' === $message_deleted ) {
				$content = '<p class="joined">' . __( 'This message was deleted.', 'buddyboss' ) . '</p>';
			} elseif ( $message_unbanned && 'yes' === $message_unbanned ) {
				$content = sprintf( __( '<p class="joined">Removed Ban "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_banned && 'yes' === $message_banned ) {
				$content = sprintf( __( '<p class="joined">Ban "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( $message_joined && 'yes' === $message_joined ) {
				$content = sprintf( __( '<p class="joined">Joined "%s"</p>', 'buddyboss' ), ucwords( $group_name ) );
			} elseif ( 'This message was deleted.' === wp_strip_all_tags( bp_get_the_thread_message_content() ) ) {
				$content = '<p class="joined">' . wp_strip_all_tags( bp_get_the_thread_message_content() ) . '</p>';
			} else {
				$content = do_shortcode( bp_get_the_thread_message_content() );
			}

			$thread->messages[ $i ] = array(
				'id'            => bp_get_the_thread_message_id(),
				'content'       => $content,
				'sender_id'     => bp_get_the_thread_message_sender_id(),
				'sender_name'   => esc_html( bp_get_the_thread_message_sender_name() ),
				'is_deleted'    => empty( get_userdata( bp_get_the_thread_message_sender_id() ) ) ? 1 : 0,
				'sender_link'   => bp_get_the_thread_message_sender_link(),
				'sender_is_you' => bp_get_the_thread_message_sender_id() === bp_loggedin_user_id(),
				'sender_avatar' => esc_url(
					bp_core_fetch_avatar(
						array(
							'item_id' => bp_get_the_thread_message_sender_id(),
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => 32,
							'height'  => 32,
							'html'    => false,
						)
					)
				),
				'date'          => bp_get_the_thread_message_date_sent() * 1000,
				'display_date'  => bp_get_the_thread_message_time_since(),
			);
		}

		if ( bp_is_active( 'moderation' ) ) {
			$thread->messages[ $i ]['is_user_suspended'] = bp_moderation_is_user_suspended( bp_get_the_thread_message_sender_id() );
			$thread->messages[ $i ]['is_user_blocked']   = bp_moderation_is_user_blocked( bp_get_the_thread_message_sender_id() );

			if ( bp_moderation_is_user_suspended( bp_get_the_thread_message_sender_id() ) ) {
				$thread->messages[ $i ]['content'] = '<p class="suspended">' . esc_html__( 'This content has been hidden as the member is suspended.', 'buddyboss' ) . '</p>';
			} elseif ( bp_moderation_is_user_blocked( bp_get_the_thread_message_sender_id() ) ) {
				$thread->messages[ $i ]['content'] = '<p class="blocked">' . esc_html__( 'This content has been hidden as you have blocked this member.', 'buddyboss' ) . '</p>';
			}
		}

		if ( bp_is_active( 'messages', 'star' ) ) {
			$star_link = bp_get_the_message_star_action_link(
				array(
					'message_id' => bp_get_the_thread_message_id(),
					'url_only'   => true,
				)
			);

			$thread->messages[ $i ]['star_link']  = $star_link;
			$thread->messages[ $i ]['is_starred'] = array_search( 'unstar', explode( '/', $star_link ) );
			$thread->messages[ $i ]['star_nonce'] = wp_create_nonce( 'bp-messages-star-' . bp_get_the_thread_message_id() );
		}

		$is_group_thread = bb_messages_is_group_thread( $thread_id );

		if ( bp_is_active( 'media' ) && ( ( ( empty( $is_group_thread ) || ( ! empty( $is_group_thread ) && ! bp_is_active( 'groups' ) ) ) && bp_is_messages_media_support_enabled() ) || ( bp_is_active( 'groups' ) && ! empty( $is_group_thread ) && bp_is_group_media_support_enabled() ) ) ) {
			$media_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_media_ids', true );

			if ( ! empty( $media_ids ) && bp_has_media(
				array(
					'include'  => $media_ids,
					'privacy'  => array( 'message' ),
					'order_by' => 'menu_order',
					'sort'     => 'ASC',
					'user_id'  => false,
				)
			) ) {
				$thread->messages[ $i ]['media'] = array();
				while ( bp_media() ) {
					bp_the_media();

					$thread->messages[ $i ]['media'][] = array(
						'id'            => bp_get_media_id(),
						'message_id'    => bp_get_the_thread_message_id(),
						'thread_id'     => bp_get_the_thread_id(),
						'title'         => bp_get_media_title(),
						'attachment_id' => bp_get_media_attachment_id(),
						'thumbnail'     => bp_get_media_attachment_image_thumbnail(),
						'full'          => bb_get_media_photos_theatre_popup_image(),
						'meta'          => $media_template->media->attachment_data->meta,
						'privacy'       => bp_get_media_privacy(),
					);
				}
			}
		}

		if ( bp_is_active( 'video' ) && bp_is_messages_video_support_enabled() ) {
			$video_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_video_ids', true );

			if (
				! empty( $video_ids ) &&
				bp_has_video(
					array(
						'include'  => $video_ids,
						'privacy'  => array( 'message' ),
						'order_by' => 'menu_order',
						'sort'     => 'ASC',
						'user_id'  => false,
					)
				)
			) {
				$thread->messages[ $i ]['video'] = array();
				while ( bp_video() ) {
					bp_the_video();

					$thread->messages[ $i ]['video'][] = array(
						'id'            => bp_get_video_id(),
						'message_id'    => bp_get_the_thread_message_id(),
						'thread_id'     => bp_get_the_thread_id(),
						'title'         => bp_get_video_title(),
						'attachment_id' => bp_get_video_attachment_id(),
						'thumbnail'     => bp_get_video_attachment_image_thumbnail(),
						'full'          => bp_get_video_attachment_image(),
						'meta'          => $video_template->video->attachment_data->meta,
						'privacy'       => bp_get_video_privacy(),
					);
				}
			}
		}

		if ( bp_is_active( 'media' ) && ( ( ( empty( $is_group_thread ) || ( ! empty( $is_group_thread ) && ! bp_is_active( 'groups' ) ) ) && bp_is_messages_document_support_enabled() ) || ( bp_is_active( 'groups' ) && ! empty( $is_group_thread ) && bp_is_group_document_support_enabled() ) ) ) {
			$document_ids = bp_messages_get_meta( bp_get_the_thread_message_id(), 'bp_document_ids', true );

			if ( ! empty( $document_ids ) && bp_has_document(
				array(
					'include'  => $document_ids,
					'order_by' => 'menu_order',
					'sort'     => 'ASC',
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

					if ( in_array( $extension, bp_get_document_preview_music_extensions(), true ) ) {
						$audio_url = bp_document_get_preview_url( bp_get_document_id(), $attachment_id );
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

					if ( '' !== $attachment_url ) {
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
									<textarea class="document-text-file-data-hidden"
											  style="display: none;"><?php echo wp_kses_post( $file_data ); ?></textarea>
								</div>
								<div class="document-expand">
									<a href="#" class="document-expand-anchor"><i
												class="bb-icon-plus document-icon-plus"></i> <?php esc_html_e( 'Click to expand', 'buddyboss' ); ?>
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
					);
				}
			}
		}

		if ( bp_is_active( 'media' ) && ( ( ( empty( $is_group_thread ) || ( ! empty( $is_group_thread ) && ! bp_is_active( 'groups' ) ) ) && bp_is_messages_gif_support_enabled() ) || ( bp_is_active( 'groups' ) && ! empty( $is_group_thread ) && bp_is_groups_gif_support_enabled() ) ) ) {
			$gif_data = bp_messages_get_meta( bp_get_the_thread_message_id(), '_gif_data', true );

			if ( ! empty( $gif_data ) ) {
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
	$thread->can_user_send_message_in_thread = ( $is_group_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : apply_filters( 'bb_can_user_send_message_in_thread', true, $thread_template->thread->thread_id, (array) $thread_template->thread->recipients );
	$thread->user_can_upload_media           = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
	$thread->user_can_upload_document        = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
	$thread->user_can_upload_video           = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
	$thread->user_can_upload_gif             = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
	$thread->user_can_upload_emoji           = bb_user_has_access_upload_emoji( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );

	return $thread;
}

function bp_nouveau_ajax_hide_thread() {

	global $bp, $wpdb;

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
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 1, (int) $thread_id, bp_loggedin_user_id() ) );
	}

	wp_send_json_success(
		array(
			'type'     => 'success',
			'messages' => 'Thread removed successfully.',
		)
	);
}
