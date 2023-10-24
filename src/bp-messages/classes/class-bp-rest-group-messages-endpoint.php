<?php
/**
 * BP REST: BP_REST_Group_Messages_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group Messages endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Group_Messages_Endpoint extends WP_REST_Controller {

	/**
	 * Reuse some parts of the BP_REST_Messages_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Messages_Endpoint
	 */
	protected $message_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace        = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base        = buddypress()->messages->id;
		$this->message_endpoint = new BP_REST_Messages_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/group',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Init a Messages Thread or add a reply to an existing Thread.
	 * -- from bp_nouveau_ajax_groups_send_message();
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/messages/group Create Group Thread
	 * @apiName        CreateBBGroupThread
	 * @apiGroup       Messages
	 * @apiDescription Create Group thread
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} message Content of the Message to add to the Thread.
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 * @apiParam {Number} user_id Limit result to messages created by a specific user.
	 * @apiParam {String=open,private} type=open Type of message, Group thread or private reply.
	 * @apiParam {String=all,individual} users=all Group thread users individual or all.
	 * @apiParam {Array} [users_list] Limit result to messages created by a specific user.
	 */
	public function create_item( $request ) {
		global $wpdb, $bp;

		$group         = ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) ? $request['group_id'] : '';
		$message       = ( isset( $request['message'] ) && ! empty( $request['message'] ) ) ? $request['message'] : '';
		$users_list    = ( isset( $request['users_list'] ) && ! empty( $request['users_list'] ) ) ? $request['users_list'] : '';
		$message_users = ( isset( $request['users'] ) && ! empty( $request['users'] ) ) ? $request['users'] : '';
		$message_type  = ( isset( $request['type'] ) && ! empty( $request['type'] ) ) ? $request['type'] : '';

		// verification for phpcs.
		wp_verify_nonce( wp_create_nonce( 'group_messages' ), 'group_messages' );

		// Allow to send message when send only '0'.
		if ( '0' === $request['message'] ) {
			$message = '<p>' . $request['message'] . '</p>';
		}

		// Get Members list if "All Group Members" selected.
		if ( 'all' === $message_users ) {

			// Fetch all the group members.
			$members = BP_Groups_Member::get_group_member_ids( (int) $group );

			// Exclude logged-in user ids from the members list.
			if ( in_array( bp_loggedin_user_id(), $members, true ) ) {
				$members = array_values( array_diff( $members, array( bp_loggedin_user_id() ) ) );
			}

			if ( 'private' === $message_type ) {

				// Check Membership Access.
				foreach ( $members as $k => $member ) {
					$can_send_group_message = apply_filters( 'bb_user_can_send_group_message', true, $member, bp_loggedin_user_id() );
					if ( ! $can_send_group_message ) {
						unset( $members[ $k ] );
					}
				}

				// Check if force friendship is enabled and check recipients.
				if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {
					if ( ! bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) ) {
						foreach ( $members as $f => $member ) {
							if (
								! (
									bb_messages_allowed_messaging_without_connection( $member ) ||
									friends_check_friendship( bp_loggedin_user_id(), $member )
								)
							) {
								unset( $members[ $f ] );
							}
						}
					}
				}

				$members = array_values( $members );
			}

			// We get members array from $_POST['users_list'] because user already selected them.
		} else {
			$members = $users_list;

			// Check Membership Access.
			$not_access_list = array();

			// Check if force friendship is enabled and check recipients.
			$not_friends = array();

			foreach ( $members as $member ) {

				$can_send_group_message = apply_filters( 'bb_user_can_send_group_message', true, $member, bp_loggedin_user_id() );
				if ( ! $can_send_group_message ) {
					$not_access_list[] = bp_core_get_user_displayname( $member );
				}
			}

			if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {
				if ( ! bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) ) {
					foreach ( $members as $f => $member ) {
						if (
							! (
								bb_messages_allowed_messaging_without_connection( $member ) ||
								friends_check_friendship( bp_loggedin_user_id(), $member )
							)
						) {
							$not_friends[] = bp_core_get_user_displayname( $member );
						}
					}
				}
			}

			if ( ! empty( $not_access_list ) ) {
				return new WP_Error(
					'bp_rest_invalid_group_members_message',
					sprintf(
						'%1$s %2$s',
						( count( $not_access_list ) > 1 ) ? __( 'You don\'t have access to send the message to this members:  ', 'buddyboss' ) : __( 'You don\'t have access to send the message to this member:  ', 'buddyboss' ),
						implode( ', ', $not_access_list )
					),
					array(
						'status' => 400,
					)
				);
			}

			if ( ! empty( $not_friends ) ) {
				return new WP_Error(
					'bp_rest_invalid_group_members_message',
					sprintf(
						'%1$s %2$s',
						( count( $not_friends ) > 1 ) ? __( 'You need to be connected with this members in order to send a message: ', 'buddyboss' ) : __( 'You need to be connected with this member in order to send a message: ', 'buddyboss' ),
						implode( ', ', $not_friends )
					),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( empty( $group ) ) {
			return new WP_Error(
				'bp_rest_no_group_selected',
				__( 'Sorry, Group id is missing.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( empty( $members ) ) {
			return new WP_Error(
				'bp_rest_no_members_selected',
				__( 'Sorry, you have not selected any members.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if (
			empty( $message )
			&& ! (
				! empty( $request['bp_media_ids'] ) ||
				(
					! empty( $request['media_gif']['url'] ) &&
					! empty( $request['media_gif']['mp4'] )
				) ||
				! empty( $request['bp_documents'] ) ||
				! empty( $request['bp_videos'] )
			)
		) {
			return new WP_Error(
				'bp_rest_messages_empty_message',
				__( 'Sorry, Your message cannot be empty.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( ! empty( $request['bp_media_ids'] ) && function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( $group, bp_loggedin_user_id(), 0, 0, 'message' );
			if ( ! $can_send_media ) {
				return new WP_Error(
					'bp_rest_bp_message_media',
					__( 'You don\'t have access to send the media.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bp_documents'] ) && function_exists( 'bb_user_has_access_upload_document' ) ) {
			$can_send_document = bb_user_has_access_upload_document( $group, bp_loggedin_user_id(), 0, 0, 'message' );
			if ( ! $can_send_document ) {
				return new WP_Error(
					'bp_rest_bp_message_document',
					__( 'You don\'t have access to send the document.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bp_videos'] ) && function_exists( 'bb_user_has_access_upload_video' ) ) {
			$can_send_video = bb_user_has_access_upload_video( $group, bp_loggedin_user_id(), 0, 0, 'message' );
			if ( ! $can_send_video ) {
				return new WP_Error(
					'bp_rest_bp_message_document',
					__( 'You don\'t have access to send the video.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['media_gif'] ) && function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( $group, bp_loggedin_user_id(), 0, 0, 'message' );
			if ( ! $can_send_gif ) {
				return new WP_Error(
					'bp_rest_bp_message_document',
					__( 'You don\'t have access to send the gif.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		// Filter to validate message content if media, video, document and gif are available without any content.
		if ( empty( $message ) ) {
			add_filter( 'bp_messages_message_validated_content', array( $this->message_endpoint, 'bb_rest_is_validate_message_content' ), 10, 1 );
		}

		$_POST            = array();
		$_POST['users']   = $message_users;
		$_POST['type']    = $message_type;
		$_POST['content'] = $message;
		$_POST['group']   = $group;

		if ( ! empty( $request->get_param( 'bp_media_ids' ) ) ) {
			$media_ids = $request->get_param( 'bp_media_ids' );
			if ( ! is_array( $media_ids ) ) {
				$media_ids = wp_parse_id_list( explode( ',', $media_ids ) );
			}

			foreach ( $media_ids as $media_attachment_id ) {
				$_POST['media'][] = array(
					'id'      => $media_attachment_id,
					'name'    => get_the_title( $media_attachment_id ),
					'privacy' => 'message',
				);
			}
		}

		if ( ! empty( $request->get_param( 'media_gif' ) ) ) {
			$object = $request->get_param( 'media_gif' );
			$still  = ( ! empty( $object ) && array_key_exists( 'url', $object ) ) ? $object['url'] : '';
			$mp4    = ( ! empty( $object ) && array_key_exists( 'mp4', $object ) ) ? $object['mp4'] : '';

			$_POST['gif_data']['images']['480w_still']['url']   = $still;
			$_POST['gif_data']['images']['original_mp4']['mp4'] = $mp4;
		}

		if ( ! empty( $request->get_param( 'bp_documents' ) ) ) {
			$documents = $request->get_param( 'bp_documents' );
			if ( ! is_array( $documents ) ) {
				$documents = wp_parse_id_list( explode( ',', $documents ) );
			}

			foreach ( $documents as $document_attachment_id ) {
				$_POST['document'][] = array(
					'id'      => $document_attachment_id,
					'name'    => get_the_title( $document_attachment_id ),
					'privacy' => 'message',
				);
			}
		}

		if ( ! empty( $request->get_param( 'bp_videos' ) ) ) {
			$videos = $request->get_param( 'bp_videos' );
			if ( ! is_array( $videos ) ) {
				$videos = wp_parse_id_list( explode( ',', $videos ) );
			}

			foreach ( $videos as $video_attachment_id ) {
				$_POST['video'][] = array(
					'id'      => $video_attachment_id,
					'name'    => get_the_title( $video_attachment_id ),
					'privacy' => 'message',
				);
			}
		}

		// If "Group Thread" selected.
		if ( 'open' === $message_type ) {

			// "All Group Members" selected.
			if ( 'all' === $message_users ) {

				// Comma separated members list to find in meta query.
				$message_users_ids = implode( ',', $members );

				// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "message_users_ids".
				$_POST['message_meta_users_list'] = $message_users_ids;

				$group_thread                 = groups_get_groupmeta( (int) $group, 'group_message_thread' );
				$is_deleted                   = false;
				$group_thread_id              = '';
				$_POST['message_thread_type'] = '';

				if ( '' !== $group_thread && messages_is_valid_thread( $group_thread ) ) {

					$first_thread_message = BP_Messages_Thread::get_first_message( $group_thread );

					if ( ! empty( $first_thread_message ) ) {
						$users      = bp_messages_get_meta( $first_thread_message->id, 'group_message_users', true );
						$type       = bp_messages_get_meta( $first_thread_message->id, 'group_message_type', true );
						$group_from = bp_messages_get_meta( $first_thread_message->id, 'message_from', true );

						if ( 'all' !== $users || 'open' !== $type || 'group' !== $group_from ) {
							$_POST['message_thread_type'] = 'new';
						}
					}

					if ( empty( $_POST['message_thread_type'] ) ) {
						$total_threads = BP_Messages_Thread::get(
							array(
								'include_threads' => array( $group_thread ),
								'per_page'        => 1,
								'count_total'     => true,
								'is_deleted'      => 1,
							)
						);

						$is_deleted = ( ! empty( $total_threads['total'] ) ? true : false );

						if ( $is_deleted ) {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'new';
						}
					}
				} else {
					$_POST['message_thread_type'] = 'new';
				}

				if ( '' !== $group_thread && ! $is_deleted && isset( $_POST['message_thread_type'] ) && empty( $_POST['message_thread_type'] ) ) {
					// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
					$_POST['message_thread_type'] = 'reply';
					$group_thread_id              = $group_thread;
				} else {

					// Backward compatibility when we don't store thread_id in group meta.
					$meta = array(
						array(
							'key'     => 'group_id',
							'value'   => $group,
							'compare' => '=',
						),
						array(
							'key'     => 'group_message_users',
							'value'   => 'all',
							'compare' => '=',
						),
						array(
							'key'     => 'group_message_type',
							'value'   => 'open',
							'compare' => '=',
						),
						array(
							'key'   => 'message_users_ids',
							'value' => $message_users_ids,
						),
					);

					// Check if there is already previously group thread created.
					if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) { // phpcs:ignore

						$thread_id = 0;

						while ( bp_message_threads() ) {
							bp_message_thread();
							$thread_id = bp_get_message_thread_id();

							// Check the first message meta to check for all users and open type when missed entries found into DB.
							$first_thread_message = BP_Messages_Thread::get_first_message( $thread_id );

							if ( ! empty( $first_thread_message ) ) {
								$users      = bp_messages_get_meta( $first_thread_message->id, 'group_message_users', true );
								$type       = bp_messages_get_meta( $first_thread_message->id, 'group_message_type', true );
								$group_from = bp_messages_get_meta( $first_thread_message->id, 'message_from', true );

								if ( 'all' !== $users || 'open' !== $type || 'group' !== $group_from ) {
									$thread_id = 0;
								}
							}

							if ( $thread_id ) {
								break;
							}
						}

						// If $thread_id found then add as a reply to that thread.
						if ( $thread_id ) {
							$group_thread_id = $thread_id;

							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'reply';

							// Create a new group thread.
						} else {
							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'new';
						}

						// Create a new group thread.
					} else {
						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'new';
					}
				}

				/**
				 * Create Message based on the `message_thread_type` and `group_thread_id`.
				 */
				if ( isset( $_POST['message_thread_type'] ) && 'new' === $_POST['message_thread_type'] ) {
					$send = bp_groups_messages_new_message(
						array(
							'recipients'    => $members,
							'subject'       => wp_trim_words( $message, messages_get_default_subject_length() ),
							'content'       => $message,
							'error_type'    => 'wp_error',
							'append_thread' => false,
						)
					);

					if ( ! is_wp_error( $send ) && ! empty( $send ) ) {
						groups_update_groupmeta( (int) $group, 'group_message_thread', $send );
					}

					return $this->bp_rest_groups_messages_validate_message( $send, $request );
				} elseif ( isset( $_POST['message_thread_type'] ) && 'reply' === $_POST['message_thread_type'] && ! empty( $group_thread_id ) ) {

					groups_update_groupmeta( (int) $group, 'group_message_thread', $group_thread_id );

					$new_reply = bp_groups_messages_new_message(
						array(
							'thread_id'    => $group_thread_id,
							'subject'      => wp_trim_words( $message, messages_get_default_subject_length() ),
							'content'      => $message,
							'date_sent'    => bp_core_current_time(),
							'mark_visible' => true,
							'error_type'   => 'wp_error',
						)
					);

					return $this->bp_rest_groups_messages_validate_message( $new_reply, $request );
				}

				// "Individual Members" Selected.
			} else {
				$meta = array(
					array(
						'key'     => 'group_message_type',
						'value'   => 'open',
						'compare' => '!=',
					),
				);

				$individual_thread_id         = 0;
				$_POST['message_thread_type'] = '';

				// Check if there is already previously individual group thread created.
				if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) { // phpcs:ignore

					$thread_id = 0;

					while ( bp_message_threads() ) {
						bp_message_thread();
						$thread_id = bp_get_message_thread_id();

						if ( $thread_id ) {

							// get the thread recipients.
							$thread                     = new BP_Messages_Thread( $thread_id );
							$thread_recipients          = $thread->get_recipients();
							$previous_thread_recipients = array();

							// Store thread recipients to $previous_ids array.
							foreach ( $thread_recipients as $thread_recipient ) {
								if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
									$previous_thread_recipients[] = $thread_recipient->user_id;
								}
							}

							$current_recipients = $members;
							$members_recipients = array();

							// Store current recipients to $members array.
							foreach ( $current_recipients as $single_recipients ) {
								$members_recipients[] = (int) $single_recipients;
							}

							// check both previous and current recipients are same.
							$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members_recipients ) && count( $previous_thread_recipients ) === count( $members_recipients ) && array_diff( $previous_thread_recipients, $members_recipients ) === array_diff( $members_recipients, $previous_thread_recipients ) );

							$group_thread = (int) groups_get_groupmeta( (int) $group, 'group_message_thread' );

							// If recipients are matched.
							if ( $is_recipient_match && (int) $thread_id !== $group_thread ) {
								break;
							}
						}
					}

					if ( $thread_id ) {
						// get the thread recipients.
						$thread                     = new BP_Messages_Thread( $thread_id );
						$thread_recipients          = $thread->get_recipients();
						$previous_thread_recipients = array();

						$last_message = BP_Messages_Thread::get_last_message( $thread_id );
						$message_type = bp_messages_get_meta( $last_message->id, 'group_message_users', true );

						// Store thread recipients to $previous_ids array.
						foreach ( $thread_recipients as $thread_recipient ) {
							if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
								$previous_thread_recipients[] = $thread_recipient->user_id;
							}
						}

						$current_recipients = $members;
						$members_recipients = array();

						// Store current recipients to $members array.
						foreach ( $current_recipients as $single_recipients ) {
							$members_recipients[] = (int) $single_recipients;
						}

						// check both previous and current recipients are same.
						$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members_recipients ) && count( $previous_thread_recipients ) === count( $members_recipients ) && array_diff( $previous_thread_recipients, $members_recipients ) === array_diff( $members_recipients, $previous_thread_recipients ) );

						$group_thread = (int) groups_get_groupmeta( (int) $group, 'group_message_thread' );

						// If recipients are matched.
						if ( $is_recipient_match && (int) $thread_id !== $group_thread ) {
							$individual_thread_id = $thread_id;

							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'reply';

							// Else recipients not matched.
						} else {
							$previous_threads = BP_Messages_Message::get_existing_threads( $members, bp_loggedin_user_id() );
							$existing_thread  = 0;
							if ( $previous_threads ) {
								foreach ( $previous_threads as $thread ) {

									$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );

									if ( $is_active_recipient ) {
										// get the thread recipients.
										$thread                     = new BP_Messages_Thread( $thread->thread_id );
										$thread_recipients          = $thread->get_recipients();
										$previous_thread_recipients = array();

										// Store thread recipients to $previous_ids array.
										foreach ( $thread_recipients as $thread_recipient ) {
											if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
												$previous_thread_recipients[] = $thread_recipient->user_id;
											}
										}

										$current_recipients = $members;
										$members            = array();

										// Store current recipients to $members array.
										foreach ( $current_recipients as $single_recipients ) {
											$members[] = (int) $single_recipients;
										}

										// check both previous and current recipients are same.
										$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members ) && count( $previous_thread_recipients ) === count( $members ) && array_diff( $previous_thread_recipients, $members ) === array_diff( $members, $previous_thread_recipients ) );

										// check any messages of this thread should not be a open & all.
										$message_ids  = wp_list_pluck( $thread->messages, 'id' );
										$add_existing = true;
										foreach ( $message_ids as $id ) {
											// group_message_users not open.
											$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual.
											if ( 'all' === $message_users ) {
												$add_existing = false;
												break;
											}
										}

										// If recipients are matched.
										if ( $is_recipient_match && $add_existing ) {
											$existing_thread = (int) $thread->thread_id;
										}
									}
								}

								if ( $existing_thread > 0 ) {
									$individual_thread_id = $existing_thread;

									// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
									$_POST['message_thread_type'] = 'reply';
								} else {
									// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
									$_POST['message_thread_type'] = 'new';
								}
							} else {
								// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
								$_POST['message_thread_type'] = 'new';
							}
						}
						// Else no thread found.
					} else {
						$previous_threads = BP_Messages_Message::get_existing_threads( $members, bp_loggedin_user_id() );
						$existing_thread  = 0;
						if ( $previous_threads ) {
							foreach ( $previous_threads as $thread ) {
								$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );
								if ( $is_active_recipient ) {

									// get the thread recipients.
									$thread                     = new BP_Messages_Thread( $thread->thread_id );
									$thread_recipients          = $thread->get_recipients();
									$previous_thread_recipients = array();

									// Store thread recipients to $previous_ids array.
									foreach ( $thread_recipients as $thread_recipient ) {
										if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
											$previous_thread_recipients[] = $thread_recipient->user_id;
										}
									}

									$current_recipients = array();
									$current_recipients = $members;
									$members            = array();

									// Store current recipients to $members array.
									foreach ( $current_recipients as $single_recipients ) {
										$members[] = (int) $single_recipients;
									}

									// check both previous and current recipients are same.
									$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members ) && count( $previous_thread_recipients ) === count( $members ) && array_diff( $previous_thread_recipients, $members ) === array_diff( $members, $previous_thread_recipients ) );

									// check any messages of this thread should not be a open & all.
									$message_ids  = wp_list_pluck( $thread->messages, 'id' );
									$add_existing = true;
									foreach ( $message_ids as $id ) {
										// group_message_users not open.
										$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual.
										if ( 'all' === $message_users ) {
											$add_existing = false;
											break;
										}
									}

									// If recipients are matched.
									if ( $is_recipient_match && $add_existing ) {
										$existing_thread = (int) $thread->thread_id;
									}
								}
							}

							if ( $existing_thread > 0 ) {
								$individual_thread_id = $existing_thread;

								// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
								$_POST['message_thread_type'] = 'reply';
							} else {
								// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
								$_POST['message_thread_type'] = 'new';
							}
						} else {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'new';
						}
					}

					// Else no previous thread found.
				} else {
					$previous_threads = BP_Messages_Message::get_existing_threads( $members, bp_loggedin_user_id() );
					$existing_thread  = 0;

					if ( $previous_threads ) {
						foreach ( $previous_threads as $thread ) {

							$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );

							if ( $is_active_recipient ) {
								// get the thread recipients.
								$thread                     = new BP_Messages_Thread( $thread->thread_id );
								$thread_recipients          = $thread->get_recipients();
								$previous_thread_recipients = array();

								// Store thread recipients to $previous_ids array.
								foreach ( $thread_recipients as $thread_recipient ) {
									if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
										$previous_thread_recipients[] = $thread_recipient->user_id;
									}
								}

								$current_recipients = $members;
								$members            = array();

								// Store current recipients to $members array.
								foreach ( $current_recipients as $single_recipients ) {
									$members[] = (int) $single_recipients;
								}

								// check both previous and current recipients are same.
								$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members ) && count( $previous_thread_recipients ) === count( $members ) && array_diff( $previous_thread_recipients, $members ) === array_diff( $members, $previous_thread_recipients ) );

								// check any messages of this thread should not be a open & all.
								$message_ids  = wp_list_pluck( $thread->messages, 'id' );
								$add_existing = true;
								foreach ( $message_ids as $id ) {
									// group_message_users not open.
									$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual.
									if ( 'all' === $message_users ) {
										$add_existing = false;
										break;
									}
								}

								// If recipients are matched.
								if ( $is_recipient_match && $add_existing ) {
									$existing_thread = (int) $thread->thread_id;
								}
							}
						}

						if ( $existing_thread > 0 ) {
							$individual_thread_id = $existing_thread;

							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'reply';
						} else {
							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'new';
						}
					} else {
						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'new';
					}
				}

				/**
				 * Create Message based on the `message_thread_type` and `individual_thread_id`.
				 */
				if ( isset( $_POST['message_thread_type'] ) && 'new' === $_POST['message_thread_type'] ) {
					$send = bp_groups_messages_new_message(
						array(
							'recipients'    => $members,
							'subject'       => wp_trim_words( $message, messages_get_default_subject_length() ),
							'content'       => $message,
							'error_type'    => 'wp_error',
							'append_thread' => false,
						)
					);

					return $this->bp_rest_groups_messages_validate_message( $send, $request, 'individual' );
				} elseif ( isset( $_POST['message_thread_type'] ) && 'reply' === $_POST['message_thread_type'] && ! empty( $individual_thread_id ) ) {
					$new_reply = bp_groups_messages_new_message(
						array(
							'thread_id'    => $individual_thread_id,
							'subject'      => wp_trim_words( $message, messages_get_default_subject_length() ),
							'content'      => $message,
							'date_sent'    => bp_core_current_time(),
							'mark_visible' => true,
							'error_type'   => 'wp_error',
						)
					);

					return $this->bp_rest_groups_messages_validate_message( $new_reply, $request, 'individual' );
				}
			}

			// Else "Private Reply (BCC)" selected.
		} else {
			global $bb_background_updater;

			$all_members = $members;

			if ( ! empty( $members ) ) {
				if (
					! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) &&
					( $bb_background_updater instanceof BB_Background_Updater )
				) {
					$chunk_members = array_chunk( $members, function_exists( 'bb_get_email_queue_min_count' ) ? bb_get_email_queue_min_count() : 10 );
					if ( ! empty( $chunk_members ) ) {
						foreach ( $chunk_members as $key => $members ) {
							$bb_background_updater->data(
								array(
									'type'     => 'email',
									'group'    => 'group_private_message',
									'data_id'  => $group,
									'priority' => 5,
									'callback' => 'bb_send_group_message_background',
									'args'     => array( $_POST, $members, bp_loggedin_user_id(), $message, true ),
								),
							);
							$bb_background_updater->save();
						}
						$bb_background_updater->dispatch();
					}

					$message = true;
				} else {
					$message = bb_send_group_message_background( $_POST, $members, bp_loggedin_user_id(), $message, false );
				}
			}

			if ( empty( $message ) ) {
				remove_filter( 'bp_messages_message_validated_content', array( $this->message_endpoint, 'bb_rest_is_validate_message_content' ), 10, 1 );
			}

			$error = array();

			$retval = array(
				'message' => '',
				'errors'  => array(),
				'data'    => array(),
			);

			if ( 'all' !== $message_users ) {
				$retval['message'] = sprintf(
				/* translators: Message member count. */
					__( 'Your message was sent privately to %s members of this group.', 'buddyboss' ),
					count( $all_members )
				);
			} else {
				$retval['message'] = __( 'Your message was sent privately to all members of this group.', 'buddyboss' );
			}

			if ( ! empty( $error ) ) {
				$retval['errors'] = $error;
			}

			$response = rest_ensure_response( $retval );

			/**
			 * Fires after a thread is fetched via the REST API.
			 *
			 * @param BP_Messages_Box_Template $messages_box Fetched thread.
			 * @param WP_REST_Response         $response     The response data.
			 * @param WP_REST_Request          $request      The request sent to the API.
			 *
			 * @since 0.1.0
			 */
			do_action( 'bp_rest_group_messages_create_items', $message, $response, $request );

			return $response;

		}
	}

	/**
	 * Check if a given request has access to create a message.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create a group message.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && function_exists( 'bp_disable_group_messages' ) && true === bp_disable_group_messages() ) {
			$retval = true;
		}

		/**
		 * Filter the messages `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_group_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Get the message schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_messages',
			'type'       => 'object',
			'properties' => array(
				'message' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Information for the user.', 'buddyboss' ),
					'type'        => 'string',
				),
				'data'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Message thread', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(),
				),
			),
		);

		$schema['properties']['data']['properties'] = $this->message_endpoint->get_item_schema()['properties'];

		/**
		 * Filters the message schema.
		 *
		 * @param array $schema The endpoint schema.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_message_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for Messages collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'edit';
		unset( $params['page'], $params['per_page'], $params['search'] );

		$params['group_id'] = array(
			'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['message'] = array(
			'description'       => __( 'Content of the Message to add to the Thread.', 'buddyboss' ),
			'type'              => 'string',
			'required'          => false,
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['users'] = array(
			'description'       => __( 'Group thread users individual or all.', 'buddyboss' ),
			'type'              => 'string',
			'required'          => true,
			'enum'              => array( 'all', 'individual' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['users_list'] = array(
			'description'       => __( 'Limit result to messages created by a specific user.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Type of message, Group thread or private reply.', 'buddyboss' ),
			'type'              => 'string',
			'required'          => true,
			'enum'              => array( 'open', 'private' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_messages_group_collection_params', $params );
	}


	/**
	 * Create New Group Message.
	 * -- from bp_groups_messages_new_message();
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
	public function bp_rest_groups_messages_new_message( $args = '' ) {
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
	 * Check group message has been successfully sent or not.
	 * - bp_groups_messages_validate_message();
	 *
	 * @param mixed           $send    int|bool|WP_Error.
	 * @param WP_REST_Request $request Rest request.
	 * @param string          $type    Type of the message `all` or `individual`.
	 *
	 * @return WP_Error
	 */
	public function bp_rest_groups_messages_validate_message( $send, $request, $type = 'all' ) {
		if ( is_wp_error( $send ) ) {
			return new WP_Error(
				'bp_rest_unknown_error',
				$send->get_error_message(),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} elseif ( ! empty( $send ) ) {
			BP_Messages_Thread::$noCache = true;

			$thread     = new BP_Messages_Thread( (int) $send );
			$recipients = $thread->get_recipients();

			$recipients_count = ( count( $recipients ) > 1 ? count( $recipients ) - ( isset( $request['user_id'] ) && ! empty( $request['user_id'] ) ? 1 : 0 ) : 0 );

			if ( 'individual' === $type ) {
				$retval['message'] = sprintf(
				/* translators: Message member count. */
					__( 'Your message was sent to %s members of this group.', 'buddyboss' ),
					$recipients_count
				);
			} else {
				$retval['message'] = __( 'Your message was sent to all members of this group.', 'buddyboss' );
			}

			$last_message = wp_list_filter( $thread->messages, array( 'id' => $thread->last_message_id ) );
			$last_message = reset( $last_message );

			if ( ! empty( $last_message ) ) {
				$fields_update = $this->update_additional_fields_for_object( $last_message, $request );

				if ( is_wp_error( $fields_update ) ) {
					return $fields_update;
				}
			}

			$retval['data'][] = $this->prepare_response_for_collection(
				$this->message_endpoint->prepare_item_for_response( $thread, $request )
			);

			$response = rest_ensure_response( $retval );

			/**
			 * Fires after a thread is fetched via the REST API.
			 *
			 * @param BP_Messages_Box_Template $messages_box Fetched thread.
			 * @param WP_REST_Response         $response     The response data.
			 * @param WP_REST_Request          $request      The request sent to the API.
			 *
			 * @since 0.1.0
			 */
			do_action( 'bp_rest_group_messages_create_items', $thread, $response, $request );

			return $response;
		}
	}
}
