<?php
/**
 * BP REST: BP_REST_Messages_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Messages endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Messages_Endpoint extends WP_REST_Controller {

	/**
	 * Current Message ID.
	 *
	 * @var integer
	 */
	protected $message_id;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->messages->id;

		$this->message_id = 0;

		add_filter( 'rest_post_dispatch', array( $this, 'bp_rest_post_dispatch' ), 10, 3 );
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search-recipients',
			array(
				'args'   => array(
					'term'     => array(
						'description' => __( 'Text for search recipients.', 'buddyboss' ),
						'type'        => 'string',
						'required'    => true,
					),
					'group_id' => array(
						'description' => __( 'Group id to search members.', 'buddyboss' ),
						'type'        => 'integer',
					),
					'exclude'  => array(
						'description'       => __( 'Ensure result set excludes specific member IDs.', 'buddyboss' ),
						'default'           => array(),
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => 'wp_parse_id_list',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'page'     => array(
						'description' => __( 'Current page of the collection.', 'buddyboss' ),
						'type'        => 'integer',
						'default'     => 1,
						'minimum'     => 1,
						'required'    => false,
					),
					'per_page' => array(
						'description' => __( 'Maximum number of items to be returned in result set.', 'buddyboss' ),
						'type'        => 'integer',
						'default'     => 10,
						'minimum'     => 1,
						'maximum'     => 100,
						'required'    => false,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_recipients_items' ),
					'permission_callback' => array( $this, 'search_recipients_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_search_recipients_items_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/search-thread',
			array(
				'args'   => array(
					'user_id'              => array(
						'description' => __( 'Sender users ID.', 'buddyboss' ),
						'type'        => 'integer',
					),
					'recipient_id'         => array(
						'description' => __( 'Thread recipient ID.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'include_group_thread' => array(
						'description' => __( 'Include group thread or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'required'    => false,
						'default'     => false,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_thread_items' ),
					'permission_callback' => array( $this, 'search_thread_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Attention: (?P<id>[\d]+) is the placeholder for **Thread** ID, not the Message ID one.
		$thread_endpoint = '/' . $this->rest_base . '/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$thread_endpoint,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Register the starred route.
		if ( bp_is_active( 'messages', 'star' ) ) {
			// Attention: (?P<id>[\d]+) is the placeholder for **Message** ID, not the Thread ID one.
			$starred_endpoint = '/' . $this->rest_base . '/' . bp_get_messages_starred_slug() . '/(?P<id>[\d]+)';

			register_rest_route(
				$this->namespace,
				$starred_endpoint,
				array(
					'args'   => array(
						'id' => array(
							'description' => __( 'ID of one of the message of the Thread.', 'buddyboss' ),
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_starred' ),
						'permission_callback' => array( $this, 'update_starred_permissions_check' ),
					),
					'schema' => array( $this, 'get_item_schema' ),
				)
			);
		}
	}

	/**
	 * Retrieve threads.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/messages Threads
	 * @apiName        GetBBThreads
	 * @apiGroup       Messages
	 * @apiDescription Retrieve threads
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=sentbox,inbox,starred} [box=inbox] Filter the result by box.
	 * @apiParam {String=all,read,unread} [type=all] Filter the result by thread status.
	 * @apiParam {Number} user_id Limit result to messages created by a specific user.
	 * @apiParam {Boolean} is_hidden List the archived threads.
	 */
	public function get_items( $request ) {
		$args = array(
			'user_id'      => $request['user_id'],
			'box'          => $request['box'],
			'type'         => $request['type'],
			'page'         => $request['page'],
			'per_page'     => $request['per_page'],
			'search_terms' => $request['search'],
			'is_hidden'    => $request['is_hidden'],
		);

		// Include the meta_query for starred messages.
		if ( 'starred' === $args['box'] ) {
			$args['meta_query'] = array( // phpcs:ignore
				array(
					'key'   => 'starred_by_user',
					'value' => $args['user_id'],
				),
			);
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_messages_get_items_query_args', $args, $request );

		global $rest_request;
		$rest_request = $request;

		add_filter( 'bp_messages_recipient_get_where_conditions', array( $this, 'bb_rest_messages_set_hidden_where_query' ), 9, 2 );

		// Actually, query it.
		$messages_box = new BP_Messages_Box_Template( $args );

		remove_filter( 'bp_messages_recipient_get_where_conditions', array( $this, 'bb_rest_messages_set_hidden_where_query' ), 9, 2 );

		$retval = array();
		if ( ! empty( $messages_box->threads ) ) {
			foreach ( (array) $messages_box->threads as $thread ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $thread, $request )
				);
			}
		}

		// Added header for the unread count for box=inbox.
		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $messages_box->total_thread_count, $args['per_page'] );

		// Added unread thread count into header response.
		$hidden_threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'fields'      => 'ids',
				'user_id'     => bp_loggedin_user_id(),
				'is_hidden'   => true,
				'thread_type' => 'archived',
			)
		);

		$response->header( 'X-WP-ArchiveTotal', (int) ( isset( $hidden_threads['total'] ) ? $hidden_threads['total'] : 0 ) );

		/**
		 * Fires after a thread is fetched via the REST API.
		 *
		 * @param BP_Messages_Box_Template $messages_box Fetched thread.
		 * @param WP_REST_Response         $response     The response data.
		 * @param WP_REST_Request          $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_messages_get_items', $messages_box, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to thread items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$user = bp_rest_get_user( $request['user_id'] );

			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( (int) bp_loggedin_user_id() === $user->ID || bp_current_user_can( 'bp_moderate' ) ) {
				$retval = true;
			} else {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you cannot view the messages.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the messages `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get a single thread.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/messages/:id Thread
	 * @apiName        GetBBThread
	 * @apiGroup       Messages
	 * @apiDescription Retrieve single thread
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id ID of the Messages Thread.
	 * @apiParam {Date} [before] Messages to get before a specific date.
	 * @apiParam {Boolean} [recipients_pagination] Load recipients in a paginated manner.
	 * @apiParam {Number} [recipients_page=1] Current page of the recipients.
	 */
	public function get_item( $request ) {
		$thread = $this->get_thread_object( $request['id'], $request );

		if ( ! isset( $request['recipients_pagination'] ) || false === $request['recipients_pagination'] ) {
			$thread->recipients = $thread->get_recipients( $thread->thread_id );
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $thread, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a thread is fetched via the REST API.
		 *
		 * @param BP_Messages_Thread $thread  Thread object.
		 * @param WP_REST_Response   $retval  The response data.
		 * @param WP_REST_Request    $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_messages_get_item', $thread, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to a thread item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see this thread.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( is_user_logged_in() ) {
			$thread = $this->get_thread_object( $request['id'], $request );

			if ( empty( $thread->thread_id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Sorry, this thread does not exist.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_current_user_can( 'bp_moderate' ) || messages_check_thread_access( $thread->thread_id ) ) {
				$retval = true;
			} else {
				$retval = $error;
			}
		}

		/**
		 * Filter the messages `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Init a Messages Thread or add a reply to an existing Thread.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/messages Create Thread
	 * @apiName        CreateBBThread
	 * @apiGroup       Messages
	 * @apiDescription Create thread
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [id] ID of the Messages Thread. Required when replying to an existing Thread.
	 * @apiParam {String} [subject] Subject of the Message initializing the Thread.
	 * @apiParam {String} message Content of the Message to add to the Thread.
	 * @apiParam {Array} [recipients] The list of the recipients user IDs of the Message.
	 * @apiParam {Number} [sender_id] The user ID of the Message sender.
	 */
	public function create_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		if ( empty( $request['id'] ) && empty( $request['recipients'] ) ) {
			return new WP_Error(
				'bp_rest_empty_recipients',
				__( 'Please, enter recipients user IDs.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		// Allow to send message when send only '0'.
		if ( '0' === $request['message'] ) {
			$request['message'] = '<p>' . $request['message'] . '</p>';
		}

		if (
			empty( $request['message'] )
			&& ! (
				! empty( $request['bp_media_ids'] ) ||
				! empty( $request['bp_videos'] ) ||
				(
					! empty( $request['media_gif']['url'] ) &&
					! empty( $request['media_gif']['mp4'] )
				) ||
				! empty( $request['bp_documents'] )
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

		$thread_id = ! empty( $request['id'] ) ? (int) $request['id'] : 0;

		if ( ! empty( $request['bp_media_ids'] ) && function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
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
			$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
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
			$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
			if ( ! $can_send_video ) {
				return new WP_Error(
					'bp_rest_bp_message_video',
					__( 'You don\'t have access to send the video.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['media_gif'] ) && function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
			if ( ! $can_send_gif ) {
				return new WP_Error(
					'bp_rest_bp_message_gif',
					__( 'You don\'t have access to send the gif.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if (
			empty( $request['message'] )
			&& ! (
				(
					function_exists( 'bb_user_has_access_upload_media' )
					&& false !== bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' )
					&& ! empty( $request['bp_media_ids'] )
				)
				|| (
					function_exists( 'bb_user_has_access_upload_gif' )
					&& false !== bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' )
					&& ! empty( $request['media_gif']['url'] )
					&& ! empty( $request['media_gif']['mp4'] )
				)
				|| (
					function_exists( 'bb_user_has_access_upload_document' )
					&& false !== bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' )
					&& ! empty( $request['bp_documents'] )
				)
				|| (
					function_exists( 'bb_user_has_access_upload_video' )
					&& false !== bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' )
					&& ! empty( $request['bp_videos'] )
				)
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

		$message_object = $this->prepare_item_for_database( $request );

		$send_at = $request->get_param( 'send_at' );
		if ( ! empty( $send_at ) ) {
			$message_object->send_at = $send_at;
		}

		// Find the thread is group or not.
		$group = '';
		if ( ! empty( $request['id'] ) && bp_is_active( 'groups' ) ) {
			$first_message = BP_Messages_Thread::get_first_message( $request['id'] );
			$group_id      = bp_messages_get_meta( $first_message->id, 'group_id', true ); // group id.

			if ( ! empty( $group_id ) ) {
				$group          = groups_get_group( $group_id );
				$_POST['group'] = $group_id;
			}
		}

		// Filter to validate message content if media, video, document and gif are available without any content.
		if ( empty( $request['message'] ) ) {
			add_filter( 'bp_messages_message_validated_content', array( $this, 'bb_rest_is_validate_message_content' ), 10, 1 );
		}

		$message_object->return = 'object';
		if ( empty( $group ) ) {
			$message = messages_new_message( $message_object );
		} else {
			// Create the message or the reply.
			$message = bp_groups_messages_new_message( $message_object );
		}

		if ( empty( $request['message'] ) ) {
			remove_filter( 'bp_messages_message_validated_content', array( $this, 'bb_rest_is_validate_message_content' ), 10, 1 );
		}

		// Validate it created a Thread or was added to it.
		if ( ! isset( $message->id ) || ! is_int( $message->id ) ) {
			return new WP_Error(
				'bp_rest_messages_create_failed',
				__( 'There was a problem sending your message.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		global $bp, $wpdb;

		$thread_id = $message->thread_id;

		// Mark thread active if it's in hidden mode.
		$unread_query = $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = %d WHERE thread_id = %d AND user_id = %d", 0, $thread_id, $message_object->sender_id ); // phpcs:ignore
		$wpdb->query( $unread_query ); // phpcs:ignore

		// Delete thread cache.
		if ( function_exists( 'bp_messages_delete_thread_paginated_messages_cache' ) ) {
			bp_messages_delete_thread_paginated_messages_cache( $thread_id );
		} else {
			$cache_key = "{$thread_id}99999999";
			wp_cache_delete( $cache_key, 'bp_messages_threads' );
		}

		// Make sure to get the newest message to update REST Additional fields.
		$thread           = $this->get_thread_object( $thread_id, $request );
		$last_message_obj = BP_Messages_Message::get(
			array(
				'include'         => array( $message->id ),
				'include_threads' => array( $thread_id ),
				'per_page'        => 1,
			)
		);
		if ( ! empty( $last_message_obj ) && ! empty( $last_message_obj['messages'] ) ) {
			$last_message = current( $last_message_obj['messages'] );
		}

		if ( empty( $last_message ) ) {
			$last_message = BP_Messages_Thread::get_last_message( $thread_id );
		}

		$fields_update = $this->update_additional_fields_for_object( $last_message, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $thread, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a message is created via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response    $retval  The response data.
		 * @param WP_REST_Request     $request The request sent to the API.
		 * @param BP_Messages_Message $message Message object.
		 * @param BP_Messages_Thread  $thread  Thread object.
		 */
		do_action( 'bp_rest_messages_create_item', $thread, $response, $request, $message );

		return $response;
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
			__( 'Sorry, you are not allowed to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to create a message.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$thread_id = (int) $request->get_param( 'id' );
			$sender_id = (int) $request->get_param( 'sender_id' );

			// It's an existing thread.
			if ( $thread_id ) {
				$sender_id = ( 0 !== $sender_id ? $sender_id : bp_loggedin_user_id() );

				if (
					! empty( $sender_id ) &&
					messages_is_valid_thread( $thread_id ) &&
					messages_check_thread_access( $thread_id, $sender_id )
				) {
					$retval = true;
				}
			} else {
				// It's a new thread.
				$retval = true;
			}
		}

		/**
		 * Filter the messages `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Search Recipients for the message.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/messages/search-recipients Search Recipients
	 * @apiName        SearchBBRecipients
	 * @apiGroup       Messages
	 * @apiDescription Search Recipients
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {string} term Text for search recipients.
	 * @apiParam {number} group_id Group id to search members.
	 * @apiParam {number} [exclude] Ensure result set excludes specific member IDs.
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 */
	public function search_recipients_items( $request ) {
		$term     = $request->get_param( 'term' );
		$group_id = $request->get_param( 'group_id' );

		if ( empty( $term ) ) {
			return new WP_Error(
				'bp_rest_term_required',
				__( 'Sorry, term is required parameter.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( empty( $group_id ) ) {
			add_filter(
				'bp_members_suggestions_query_args',
				array(
					$this,
					'bp_rest_nouveau_ajax_search_recipients_exclude_current',
				)
			);
		}

		$args = array(
			'term'         => sanitize_text_field( $term ),
			'type'         => 'members',
			'only_friends' => false,
			'page'         => $request->get_param( 'page' ),
			'limit'        => $request->get_param( 'per_page' ),
			'count_total'  => 'count_query',
		);

		if ( ! empty( $request->get_param( 'exclude' ) ) ) {
			$args['exclude'] = $request->get_param( 'exclude' );
		}

		$is_blocked_by_users = function_exists( 'bb_moderation_get_blocked_by_user_ids' ) ? bb_moderation_get_blocked_by_user_ids( get_current_user_id() ) : array();
		if ( ! empty( $is_blocked_by_users ) ) {
			if ( ! empty( $request->get_param( 'exclude' ) ) ) {
				$is_blocked_by_users = array_merge( $is_blocked_by_users, $request->get_param( 'exclude' ) );
			}
			$args['exclude'] = $is_blocked_by_users;
		}

		if ( ! empty( $group_id ) ) {
			$args['group_id'] = $group_id;
		}

		if (
			bp_is_active( 'friends' ) &&
			bp_force_friendship_to_message() &&
			empty( bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) )
		) {
			add_filter( 'bp_user_query_uid_clauses', 'bb_messages_update_recipient_user_query_uid_clauses', 9999, 2 );
		}

		$results = bp_core_get_suggestions( $args );

		if (
			bp_is_active( 'friends' ) &&
			bp_force_friendship_to_message() &&
			empty( bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) )
		) {
			remove_filter( 'bp_user_query_uid_clauses', 'bb_messages_update_recipient_user_query_uid_clauses', 9999, 2 );
		}

		$results_total = apply_filters( 'bp_members_suggestions_results_total', $results['total'] );
		$results       = apply_filters( 'bp_members_suggestions_results', isset( $results['members'] ) ? $results['members'] : array() );

		$results = apply_filters( 'bp_members_suggestions_results', $results );

		$retval = array_map(
			function ( $result ) {
				return array(
					'id'         => $result->user_id,
					'userhandle' => "@{$result->ID}",
					'text'       => $result->name,
					'image'      => $result->image,
				);
			},
			$results
		);

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $results_total, $args['limit'] );

		/**
		 * Fires after a member suggetion is fetched via the REST API.
		 *
		 * @param array            $results  member array.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_messages_search_recipients_items', $results, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to search recipients.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function search_recipients_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to search recipients.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the messages `search_recipients_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_search_recipients_items_permissions_check', $retval, $request );
	}

	/**
	 * Search Existing thread by user and recipient for the message.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/messages/search-thread Search Thread
	 * @apiName        SearchBBThread
	 * @apiGroup       Messages
	 * @apiDescription Search Existing thread by user and recipient for the message.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {number} user_id Sender users ID.
	 * @apiParam {number} recipient_id Thread recipient ID.
	 * @apiParam {Boolean} include_group_thread Include group thread or not.
	 */
	public function search_thread_items( $request ) {
		$user_id            = $request->get_param( 'user_id' );
		$recipient_id       = (array) $request->get_param( 'recipient_id' );
		$allow_group_thread = (bool) $request->get_param( 'include_group_thread' );

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$retval = array();

		if ( class_exists( 'BP_Messages_Message' ) && method_exists( 'BP_Messages_Message', 'get_existing_thread' ) ) {
			$threads = BP_Messages_Message::get_existing_threads( $recipient_id, $user_id );

			if ( ! empty( $threads ) && false === $allow_group_thread ) {
				foreach ( $threads as $key => $thread ) {
					$is_group_message_thread = bb_messages_is_group_thread( (int) $thread->thread_id );
					if ( $is_group_message_thread ) {
						unset( $threads[ $key ] );
					}
				}
			}

			if ( ! empty( $threads ) ) {
				$thread_id = current( $threads )->thread_id;
				$thread    = $this->get_thread_object( $thread_id, $request );

				$retval = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $thread, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a thread id is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 */
		do_action( 'bp_rest_messages_search_thread_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to search thread.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function search_thread_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to search thread.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the messages `search_thread_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_search_thread_items_permissions_check', $retval, $request );
	}

	/**
	 * Update metadata for one of the messages of the thread.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/messages/:id Update Thread
	 * @apiName        UpdateBBThread
	 * @apiGroup       Messages
	 * @apiDescription Update thread
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id ID of the Messages Thread.
	 * @apiParam {Number} message_id By default the latest message of the thread will be updated. Specify this message ID to edit another message of the thread.
	 * @apiParam {Date} [before] Messages to get before a specific date.
	 * @apiParam {Boolean} [recipients_pagination] Load recipients in a paginated manner.
	 * @apiParam {Number} [recipients_page=1] Current page of the recipients.
	 */
	public function update_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the thread.
		$thread = $this->get_thread_object( $request['id'], $request );
		$error  = new WP_Error(
			'bp_rest_messages_update_failed',
			__( 'There was an error trying to update the message.', 'buddyboss' ),
			array(
				'status' => 500,
			)
		);

		if ( ! $thread->thread_id ) {
			return $error;
		}

		// By default use the last message.
		$last_message = reset( $thread->messages );
		$message_id   = $last_message->id;
		if ( $request['message_id'] ) {
			$message_id = $request['message_id'];
		}

		$updated_message = wp_list_filter( $thread->messages, array( 'id' => $message_id ) );
		$updated_message = reset( $updated_message );

		/**
		 * Filter here to allow more users to edit the message meta (eg: the recipients).
		 *
		 * @param boolean             $value           Whether the user can edit the message meta.
		 *                                             By default: only the sender and a community moderator can.
		 * @param BP_Messages_Message $updated_message The updated message object.
		 * @param WP_REST_Request     $request         The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$can_edit_item_meta = apply_filters(
			'bp_rest_messages_can_edit_item_meta',
			bp_loggedin_user_id() === $updated_message->sender_id || bp_current_user_can( 'bp_moderate' ),
			$updated_message,
			$request
		);

		// The message must exist in the thread, and the logged in user must be the sender.
		if ( ! isset( $updated_message->id ) || ! $updated_message->id || ! $can_edit_item_meta ) {
			return $error;
		}

		$fields_update = $this->update_additional_fields_for_object( $updated_message, $request );
		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		if ( ! isset( $request['recipients_pagination'] ) || false === $request['recipients_pagination'] ) {
			$thread->recipients = $thread->get_recipients( $thread->thread_id );
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $thread, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a message is updated via the REST API.
		 *
		 * @param BP_Messages_Message $updated_message The updated message.
		 * @param WP_REST_Response    $response        The response data.
		 * @param WP_REST_Request     $request         The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_messages_update_item', $updated_message, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a message.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the message `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Adds or removes the message from the current user's starred box.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/messages/starred/:id Update Starred Thread
	 * @apiName        UpdateBBThreadStarred
	 * @apiGroup       Messages
	 * @apiDescription Update starred thread
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id ID of one of the message of the Thread.
	 */
	public function update_starred( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$message = $this->get_message_object( $request['id'] );

		if ( empty( $message->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Sorry, this message does not exist.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$user_id = bp_loggedin_user_id();
		$result  = false;
		$action  = 'star';
		$info    = __( 'Sorry, you cannot add the message to your starred box.', 'buddyboss' );

		if ( bp_messages_is_message_starred( $message->id, $user_id ) ) {
			$action = 'unstar';
			$info   = __( 'Sorry, you cannot remove the message from your starred box.', 'buddyboss' );
		}

		$result = bp_messages_star_set_action(
			array(
				'user_id'    => $user_id,
				'message_id' => $message->id,
				'action'     => $action,
			)
		);

		if ( ! $result ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_starred_message',
				$info,
				array(
					'status' => 500,
				)
			);
		}

		// Prepare the message for the REST response.
		$data = $this->prepare_response_for_collection(
			$this->prepare_message_for_response( $message, $request )
		);

		$response = rest_ensure_response( $data );

		/**
		 * Fires after a message is starred/unstarred via the REST API.
		 *
		 * @param BP_Messages_Message $message  Message object.
		 * @param string              $action   Informs about the update performed.
		 *                                      Possible values are `star` or `unstar`.
		 * @param WP_REST_Response    $response The response data.
		 * @param WP_REST_Request     $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_message_update_starred_item', $message, $action, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update user starred messages.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_starred_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to star/unstar messages.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$thread_id = messages_get_message_thread_id( $request['id'] );

			if ( messages_check_thread_access( $thread_id ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the message `update_starred` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_update_starred_permissions_check', $retval, $request );
	}

	/**
	 * Delete a thread.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/messages/:id Delete Thread
	 * @apiName        DeleteBBThread
	 * @apiGroup       Messages
	 * @apiDescription Delete thread
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id ID of the Messages Thread.
	 * @apiParam {Number} user_id The user ID to remove from the thread.
	 * @apiParam {Date} [before] Messages to get before a specific date.
	 * @apiParam {Boolean} [recipients_pagination] Load recipients in a paginated manner.
	 * @apiParam {Number} [recipients_page=1] Current page of the recipients.
	 */
	public function delete_item( $request ) {
		global $wpdb, $bp;

		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the thread before it's deleted.
		$thread             = $this->get_thread_object( $request['id'], $request );
		$thread->recipients = $thread->get_recipients( $thread->thread_id );

		if ( ! isset( $request['recipients_pagination'] ) || false === $request['recipients_pagination'] ) {
			$thread->recipients = $thread->get_recipients( $thread->thread_id );
		}

		$previous = $this->prepare_item_for_response( $thread, $request );

		$user_id = bp_loggedin_user_id();
		if ( ! empty( $request['user_id'] ) ) {
			$user_id = $request['user_id'];
		}

		// Check the user is one of the recipients.
		$recipient_ids = wp_parse_id_list( wp_list_pluck( $thread->recipients, 'user_id' ) );

		// Delete a thread.
		if ( ! in_array( $user_id, $recipient_ids, true ) ) {
			return new WP_Error(
				'bp_rest_messages_delete_thread_failed',
				__( 'There was a problem deleting your conversation.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$thread_id = $thread->thread_id;

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
		 */
		do_action( 'bp_messages_message_delete_thread', $thread_id, $thread_recipients );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a thread is deleted via the REST API.
		 *
		 * @param BP_Messages_Thread $thread   Thread object.
		 * @param WP_REST_Response   $response The response data.
		 * @param WP_REST_Request    $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_messages_delete_item', $thread, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a thread.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {

		$retval  = $this->get_item_permissions_check( $request );
		$user_id = bp_loggedin_user_id();

		if ( ! empty( $request['user_id'] ) ) {
			$user_id = $request['user_id'];
		}

		if ( true === $retval && ! bp_user_can( $user_id, 'bp_moderate', array( 'site_id' => bp_get_root_blog_id() ) ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this thread messages.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the thread `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepare a message for create.
	 *
	 * @param WP_REST_Request $request The request sent to the API.
	 *
	 * @return stdClass
	 * @since 0.1.0
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_thread = new stdClass();
		$schema          = $this->get_item_schema();
		$thread          = $this->get_thread_object( $request['id'], $request );

		if ( ! empty( $schema['properties']['id'] ) && ! empty( $request['id'] ) ) {
			$prepared_thread->thread_id = $request['id'];
		} elseif ( ! empty( $thread->thread_id ) ) {
			$prepared_thread->thread_id = $thread->thread_id;
		}

		if ( ! empty( $request['sender_id'] ) ) {
			$prepared_thread->sender_id = (int) $request['sender_id'];
		} elseif ( ! empty( $thread->sender_id ) ) {
			$prepared_thread->sender_id = $thread->sender_id;
		} else {
			$prepared_thread->sender_id = bp_loggedin_user_id();
		}

		if ( ! empty( $schema['properties']['message'] ) && ! empty( $request['message'] ) ) {
			$prepared_thread->content = $request['message'];
		} elseif ( ! empty( $thread->message ) ) {
			$prepared_thread->message = $thread->message;
		}

		if ( ! empty( $schema['properties']['subject'] ) && ! empty( $request['subject'] ) ) {
			$prepared_thread->subject = $request['subject'];
		} elseif ( ! empty( $thread->subject ) ) {
			$prepared_thread->subject = $thread->subject;
		}

		if ( ! empty( $schema['properties']['recipients'] ) && ! empty( $request['recipients'] ) ) {
			$prepared_thread->recipients = $request['recipients'];
		} elseif ( ! empty( $thread->recipients ) ) {
			$prepared_thread->recipients = wp_parse_id_list( wp_list_pluck( $thread->recipients, 'user_id' ) );
		}

		$prepared_thread->mark_visible = true;

		/**
		 * Filters a message before it is inserted via the REST API.
		 *
		 * @param stdClass        $prepared_thread An object prepared for inserting into the database.
		 * @param WP_REST_Request $request         Request object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_message_pre_insert_value', $prepared_thread, $request );
	}

	/**
	 * Prepares message data for the REST response.
	 *
	 * @param BP_Messages_Message $message The Message object.
	 * @param WP_REST_Request     $request Full details about the request.
	 *
	 * @return array The Message data for the REST response.
	 * @since 0.1.0
	 */
	public function prepare_message_for_response( $message, $request ) {
		global $wpdb;

		$group_name                = '';
		$group_member_action_type  = '';
		$group_id                  = bp_messages_get_meta( $message->id, 'group_id', true );
		$group_message_users       = bp_messages_get_meta( $message->id, 'group_message_users', true );
		$group_message_type        = bp_messages_get_meta( $message->id, 'group_message_type', true );
		$group_message_thread_type = bp_messages_get_meta( $message->id, 'group_message_thread_type', true );
		$group_message_fresh       = bp_messages_get_meta( $message->id, 'group_message_fresh', true );
		$message_from              = bp_messages_get_meta( $message->id, 'message_from', true );
		$message_left              = bp_messages_get_meta( $message->id, 'group_message_group_left', true );
		$message_joined            = bp_messages_get_meta( $message->id, 'group_message_group_joined', true );
		$message_banned            = bp_messages_get_meta( $message->id, 'group_message_group_ban', true );
		$message_unbanned          = bp_messages_get_meta( $message->id, 'group_message_group_un_ban', true );
		$message_deleted           = bp_messages_get_meta( $message->id, 'bp_messages_deleted', true );

		if ( ! empty( $group_id ) ) {
			// Get Group Name.
			if ( bp_is_active( 'groups' ) ) {
				$group_name = bp_get_group_name( groups_get_group( $group_id ) );
				$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
			} else {
				$prefix       = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table = $prefix . 'bp_groups';
				// phpcs:ignore
				$group_name = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$group_link = '';
			}
		}

		if ( ! empty( $group_id ) && ! empty( $message_from ) && 'group' === $message_from ) {

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
		}

		if ( $message_left && 'yes' === $message_left ) {
			$message->message = sprintf(
			/* translators: %s: Group name */
				__( 'Left "%s"', 'buddyboss' ),
				ucwords( $group_name )
			);
			$group_member_action_type        = 'left';
			$group_message_left_join_members = bp_messages_get_meta( $message->id, 'group_message_group_left_users' );
		} elseif ( 'yes' === $message_joined ) {
			$message->message = sprintf(
			/* translators: %s: Group name */
				__( 'Joined "%s"', 'buddyboss' ),
				ucwords( $group_name )
			);
			$group_member_action_type        = 'join';
			$group_message_left_join_members = bp_messages_get_meta( $message->id, 'group_message_group_joined_users' );
		} elseif ( $message_deleted && 'yes' === $message_deleted ) {
			$message->message = __( 'This message was deleted', 'buddyboss' );
		} elseif ( $message_unbanned && 'yes' === $message_unbanned ) {
			$message->message = sprintf(
			/* translators: %s: Group name */
				__( 'Removed Ban "%s"', 'buddyboss' ),
				ucwords( $group_name )
			);
		} elseif ( $message_banned && 'yes' === $message_banned ) {
			$message->message = sprintf(
			/* translators: %s: Group name */
				__( 'Ban "%s"', 'buddyboss' ),
				ucwords( $group_name )
			);
		} elseif ( 'This message was deleted' === wp_strip_all_tags( $message->message ) ) {
			$message->message = wp_strip_all_tags( $message->message );
		} else {
			$message->message = $message->message;
		}

		$this->message_id = (int) $message->id;

		add_filter( 'embed_post_id', array( $this, 'bb_get_the_thread_message_id' ) );

		$message_rendered = apply_filters( 'bp_get_the_thread_message_content', $message->message );
		$message_rendered = preg_replace( '#(<p></p>)#', '<p><br></p>', $message_rendered );

		$data = array(
			'id'                        => (int) $message->id,
			'thread_id'                 => (int) $message->thread_id,
			'sender_id'                 => (int) $message->sender_id,
			'subject'                   => array(
				'raw'      => $message->subject,
				'rendered' => apply_filters( 'bp_get_message_thread_subject', $message->subject ),
			),
			'message'                   => array(
				'raw'      => wp_strip_all_tags( $message->message ),
				'rendered' => $message_rendered,
			),
			'date_sent'                 => bp_rest_prepare_date_response( $message->date_sent ),
			'display_date'              => bp_core_time_since( $message->date_sent ),
			'group_name'                => ( isset( $group_name ) ? $group_name : '' ),
			'group_text'                => ( isset( $group_text ) ? $group_text : '' ),
			'group_link'                => ( isset( $group_link ) ? $group_link : '' ),
			'group_message_users'       => $group_message_users,
			'group_message_type'        => $group_message_type,
			'group_message_thread_type' => $group_message_thread_type,
			'group_message_fresh'       => $group_message_fresh,
			'message_from'              => $message_from,
			'group_member_action_type'  => $group_member_action_type,
		);

		if ( isset( $group_message_left_join_members ) && ! empty( $group_message_left_join_members ) ) {
			$group_message_left_join_members          = array_map(
				function ( $user ) {
					$user['user_id']      = (int) $user['user_id'];
					$user['name']         = bp_core_get_user_displayname( $user['user_id'] );
					$user['time']         = bp_rest_prepare_date_response( $user['time'] );
					$user['user_avatars'] = bp_core_fetch_avatar(
						array(
							'item_id' => $user['user_id'],
							'html'    => false,
							'type'    => 'thumb',
						)
					);

					return $user;
				},
				$group_message_left_join_members
			);
			$data['group_message_left_join_memebers'] = $group_message_left_join_members;
			$data['group_message_left_join_members']  = $group_message_left_join_members;
		} elseif ( in_array( $group_member_action_type, array( 'join', 'left' ), true ) ) {
			$group_message_left_join_members          = array(
				array(
					'user_id'      => $message->sender_id,
					'name'         => bp_core_get_user_displayname( $message->sender_id ),
					'time'         => bp_rest_prepare_date_response( $message->date_sent ),
					'user_avatars' => bp_core_fetch_avatar(
						array(
							'item_id' => $message->sender_id,
							'html'    => false,
							'type'    => 'thumb',
						)
					),
				),
			);
			$data['group_message_left_join_memebers'] = $group_message_left_join_members;
			$data['group_message_left_join_members']  = $group_message_left_join_members;
		}

		// Sender details.
		$data['sender_data'] = array(
			'sender_name' => bp_core_get_user_displayname( $message->sender_id ),
		);
		if ( true === buddypress()->avatar->show_avatars ) {
			foreach ( array( 'full', 'thumb' ) as $type ) {
				$data['sender_data']['user_avatars'][ $type ] = bp_core_fetch_avatar(
					array(
						'item_id' => $message->sender_id,
						'html'    => false,
						'type'    => $type,
					)
				);
			}
		}

		remove_filter( 'embed_post_id', array( $this, 'bb_get_the_thread_message_id' ) );

		if ( bp_is_active( 'messages', 'star' ) ) {
			$user_id = bp_loggedin_user_id();

			if ( isset( $request['user_id'] ) && $request['user_id'] ) {
				$user_id = (int) $request['user_id'];
			}

			$data['is_starred'] = bp_messages_is_message_starred( $data['id'], $user_id );
		}

		// Add REST Fields (BP Messages meta) data.
		$data = $this->add_additional_fields_to_object( $data, $request );

		/**
		 * Filter a message value returned from the API.
		 *
		 * @param array               $data    The message value for the REST response.
		 * @param BP_Messages_Message $message The Message object.
		 * @param WP_REST_Request     $request Request used to generate the response.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_message_prepare_value', $data, $message, $request );
	}

	/**
	 * Prepares recipient data for the REST response.
	 *
	 * @param object          $recipient The recipient object.
	 * @param WP_REST_Request $request   Full details about the request.
	 *
	 * @return array                     The recipient data for the REST response.
	 * @since 0.1.0
	 */
	public function prepare_recipient_for_response( $recipient, $request ) {
		$data = array(
			'id'        => (int) $recipient->id,
			'user_id'   => (int) $recipient->user_id,
			'user_link' => esc_url( bp_core_get_user_domain( $recipient->user_id ) ),
			'name'      => bp_core_get_user_displayname( $recipient->user_id ),
		);

		// Fetch the user avatar urls (Full & thumb).
		if ( true === buddypress()->avatar->show_avatars ) {
			foreach ( array( 'full', 'thumb' ) as $type ) {
				$data['user_avatars'][ $type ] = bp_core_fetch_avatar(
					array(
						'item_id' => $recipient->user_id,
						'html'    => false,
						'type'    => $type,
					)
				);
			}
		}

		$data_query = array(
			'thread_id'    => (int) $recipient->thread_id,
			'unread_count' => (int) $recipient->unread_count,
			'sender_only'  => (int) $recipient->sender_only,
			'is_deleted'   => (int) $recipient->is_deleted,
		);

		if ( ! empty( $recipient->user_id ) ) {
			$data_query['is_deleted'] = empty( get_userdata( $recipient->user_id ) ) ? 1 : 0;
		}

		if ( isset( $recipient->is_hidden ) ) {
			$data_query['is_hidden'] = (int) ( isset( $recipient->is_hidden ) ? $recipient->is_hidden : 0 );
		}

		$data = array_merge(
			$data,
			$data_query
		);

		$data['current_user_permissions'] = $this->get_current_user_permissions( $recipient, $request );

		/**
		 * Filter a recipient value returned from the API.
		 *
		 * @param array           $data      The recipient value for the REST response.
		 * @param object          $recipient The recipient object.
		 * @param WP_REST_Request $request   Request used to generate the response.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_prepare_recipient_value', $data, $recipient, $request );
	}

	/**
	 * Prepares thread data for return as an object.
	 *
	 * @param BP_Messages_Thread $thread  Thread object.
	 * @param WP_REST_Request    $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $thread, $request ) {
		global $wpdb;

		$content = '';

		if ( '' === $content ) {
			if ( ! empty( $thread->messages ) ) {
				foreach ( $thread->messages as $thread_message ) {
					$content = trim( wp_strip_all_tags( do_shortcode( $thread_message->message ) ) );
					if ( '' !== $content ) {
						if ( ! isset( $thread->last_message_content ) ) {
							$thread->last_message_content = $thread_message->message;
						}

						if ( ! isset( $thread->last_message_subject ) ) {
							$thread->last_message_subject = $thread_message->subject;
						}

						break;
					}
				}
			}
		}

		$last_message = reset( $thread->messages );
		if ( ! empty( $last_message ) && ! isset( $thread->last_message_id ) ) {
			$thread->last_message_id = $last_message->id;
		}

		$excerpt = '';
		if ( isset( $thread->last_message_content ) ) {
			// Added fallback support from api, if first line does not wrap with paragraph tag.
			$excerpt = apply_filters( 'bp_get_message_thread_content', $thread->last_message_content );
			$excerpt = wp_trim_words( wp_strip_all_tags( preg_replace( '#(<br\s*?\/?>|</(\w+)><(\w+)>)#', ' ', bp_create_excerpt( $excerpt, 75, array( 'ending' => '&hellip;' ) ) ) ) );
		}

		$group_id      = bp_messages_get_meta( $thread->last_message_id, 'group_id', true );
		$first_message = ( method_exists( 'BP_Messages_Thread', 'get_first_message' ) ? BP_Messages_Thread::get_first_message( $thread->thread_id ) : '' );

		if ( ! empty( $group_id ) && ! empty( $first_message ) ) {
			$group_id = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		}

		if ( (int) $group_id > 0 && ! empty( $first_message ) ) {
			$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
			$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
			$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
			$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
			$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

			if ( bp_is_active( 'groups' ) ) {
				$group_name = bp_get_group_name( groups_get_group( $group_id ) );
				$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
			} else {

				$prefix       = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table = $prefix . 'bp_groups';
				// phpcs:ignore
				$group_name = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$group_link = '';
			}

			$group_name = ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name;
		}

		if (
			isset( $message_from )
			&& 'group' === $message_from
			&& isset( $group_message_thread_id )
			&& $thread->thread_id === (int) $group_message_thread_id
			&& isset( $message_users )
			&& isset( $message_type )
			&& 'all' === $message_users
			&& 'open' === $message_type
		) {
			$is_group_thread = 1;
		}

		if ( isset( $is_group_thread ) && $is_group_thread ) {
			$avatar = array(
				'thumb' => bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $group_id,
						'type'    => 'thumb',
					)
				),
				'full'  => bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $group_id,
						'type'    => 'full',
					)
				),
			);
		} else {

			$recepients     = $thread->recipients;
			$curren_user_id = ( isset( $request['user_id'] ) && ! empty( $request['user_id'] ) ? $request['user_id'] : bp_current_user_id() );

			if ( ! empty( $recepients ) && array_key_exists( $curren_user_id, $recepients ) ) {
				unset( $recepients[ $curren_user_id ] );
			}

			if ( ! empty( $recepients ) && count( $recepients ) > 1 ) {
				$avatar = array(
					'full'  => bp_core_fetch_avatar(
						array(
							'item_id' => $thread->last_sender_id,
							'html'    => false,
							'type'    => 'full',
							'object'  => 'user',
						)
					),
					'thumb' => bp_core_fetch_avatar(
						array(
							'item_id' => $thread->last_sender_id,
							'html'    => false,
							'object'  => 'user',
						)
					),
				);
			} else {
				$avatar_user = ! empty( $recepients ) && ! empty( reset( $recepients ) ) ? reset( $recepients )->user_id : $curren_user_id;
				$avatar      = array(
					'full'  => bp_core_fetch_avatar(
						array(
							'item_id' => $avatar_user,
							'html'    => false,
							'type'    => 'full',
							'object'  => 'user',
						)
					),
					'thumb' => bp_core_fetch_avatar(
						array(
							'item_id' => $avatar_user,
							'html'    => false,
							'object'  => 'user',
						)
					),
				);
			}
		}

		$next_messages_timestamp = ( ! empty( $thread->messages ) && count( $thread->messages ) >= apply_filters( 'bp_messages_default_per_page', 10 ) ? $thread->messages[ count( $thread->messages ) - 1 ]->date_sent : '' );

		if ( '' !== $next_messages_timestamp ) {
			$time = new DateTime( $next_messages_timestamp );
			$time->modify( '-1 second' );
			$next_messages_timestamp = bp_rest_prepare_date_response( $time->format( 'Y-m-d H:i:s' ) );
		}

		// Get recipient object of current user.
		$permission_args = BP_Messages_Thread::get(
			array(
				'include_threads' => array( $thread->thread_id ),
				'user_id'         => bp_loggedin_user_id(),
				'count_total'     => false,
			)
		);

		$permission_args = ( ! empty( $permission_args ) && ! empty( $permission_args['recipients'] ) ? current( $permission_args['recipients'] ) : array() );

		// Total recipients counts.
		$total_recipients = ( isset( $thread->total_recipients_count ) ? $thread->total_recipients_count : ( ( isset( $thread->recipients ) && count( $thread->recipients ) > 1 ) ? count( $thread->recipients ) - ( isset( $request['user_id'] ) && ! empty( $request['user_id'] ) ? 1 : 0 ) : 0 ) );

		$message_rendered = apply_filters( 'bp_get_message_thread_content', $thread->last_message_content );
		$message_rendered = preg_replace( '#(<p></p>)#', '<p><br></p>', $message_rendered );

		$data = array(
			'id'                        => (int) $thread->thread_id,
			'message_id'                => (int) $thread->last_message_id,
			'last_sender_id'            => (int) $thread->last_sender_id,
			'subject'                   => array(
				'raw'      => $thread->last_message_subject,
				'rendered' => apply_filters( 'bp_get_message_thread_subject', $thread->last_message_subject ),
			),
			'excerpt'                   => array(
				'raw'      => $excerpt,
				'rendered' => apply_filters( 'bp_get_message_thread_excerpt', $excerpt ),
			),
			'message'                   => array(
				'raw'      => $thread->last_message_content,
				'rendered' => $message_rendered,
			),
			'date'                      => bp_rest_prepare_date_response( $thread->last_message_date ),
			'start_date'                => bp_rest_prepare_date_response( ( isset( $thread->first_message_date ) ? $thread->first_message_date : $thread->last_message_date ) ),
			'unread_count'              => ! empty( $thread->unread_count ) ? $thread->unread_count : 0,
			'sender_ids'                => $thread->sender_ids,
			'current_user'              => ( isset( $request['user_id'] ) && ! empty( $request['user_id'] ) ? $request['user_id'] : bp_loggedin_user_id() ),
			'can_send_message'          => $this->bp_rest_can_send_message( $thread->thread_id, bp_loggedin_user_id() ),
			'avatar'                    => $avatar,
			'is_group'                  => ( ! empty( $group_id ) ? $group_id : false ),
			'is_group_thread'           => ( isset( $is_group_thread ) ? $is_group_thread : false ),
			'group_name'                => ( isset( $group_name ) ? $group_name : '' ),
			'group_link'                => ( isset( $group_link ) ? $group_link : '' ),
			'group_message_users'       => ( isset( $message_users ) ? $message_users : '' ),
			'group_message_type'        => ( isset( $message_type ) ? $message_type : '' ),
			'group_message_from'        => ( isset( $message_from ) ? $message_from : '' ),
			'recipients_count'          => $total_recipients,
			'recipients_per_page'       => function_exists( 'bb_messages_recipients_per_page' ) ? bb_messages_recipients_per_page() : 0,
			'recipients_total_pages'    => function_exists( 'bb_messages_recipients_per_page' ) ? ceil( (int) $total_recipients / (int) bb_messages_recipients_per_page() ) : 1,
			'recipients'                => array(),
			'message_per_page'          => $thread->messages_perpage,
			'messages_count'            => $thread->total_messages,
			'next_messages_timestamp'   => $next_messages_timestamp,
			'messages'                  => array(),
			'loggedin_user_permissions' => $this->get_current_user_permissions( $permission_args, $request ),
			'is_hidden'                 => $this->bb_rest_thread_is_hidden( $thread->thread_id, bp_loggedin_user_id() ),
		);

		if ( $thread->messages ) {

			// update order of the message to latest one at the end.
			$thread_messages  = array_reverse( $thread->messages );
			$thread->messages = $thread_messages;

			// Loop through messages to prepare them for the response.
			foreach ( $thread->messages as $message ) {
				$data['messages'][] = $this->prepare_message_for_response( $message, $request );
			}
		}

		if ( $thread->recipients ) {
			// Loop through recipients to prepare them for the response.
			foreach ( $thread->recipients as $recipient ) {
				$data['recipients'][ $recipient->user_id ] = $this->prepare_recipient_for_response( $recipient, $request );
			}
		}

		$data['avatar'] = $this->bp_rest_messages_get_avatars( $thread->thread_id );

		// Pluck starred message ids.
		$data['starred_message_ids'] = array_keys( array_filter( wp_list_pluck( $data['messages'], 'is_starred', 'id' ) ) );

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $thread, $response ) );

		/**
		 * Filter a thread value returned from the API.
		 *
		 * @param WP_REST_Response   $response Response generated by the request.
		 * @param WP_REST_Request    $request  Request used to generate the response.
		 * @param BP_Messages_Thread $thread   The thread object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_prepare_value', $response, $request, $thread );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Messages_Thread $thread   Thread object.
	 * @param WP_REST_Response   $response Rest response.
	 *
	 * @return array Links for the given thread.
	 * @since 0.1.0
	 */
	protected function prepare_links( $thread, $response ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $thread->thread_id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		// Add star links for each message of the thread.
		if ( bp_is_active( 'messages', 'star' ) ) {
			$starred_base = $base . bp_get_messages_starred_slug() . '/';

			foreach ( $thread->messages as $message ) {
				$links[ $message->id ] = array(
					'href' => rest_url( $starred_base . $message->id ),
				);
			}
		}

		$data = $response->get_data();
		if ( ! empty( $data ) && ! empty( $data['is_group'] ) && bp_is_active( 'groups' ) ) {
			$links['group'] = array(
				'href'       => rest_url( sprintf( '%s/%s/%d', $this->namespace, buddypress()->groups->id, $data['is_group'] ) ),
				'embeddable' => true,
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array              $links  The prepared links of the REST response.
		 * @param BP_Messages_Thread $thread Thread object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_messages_prepare_links', $links, $thread );
	}

	/**
	 * Get thread object.
	 *
	 * @param int             $thread_id Thread ID.
	 * @param WP_REST_Request $request   Full details about the request.
	 *
	 * @return BP_Messages_Thread
	 * @since 0.1.0
	 */
	public function get_thread_object( $thread_id, $request = array() ) {
		$args = array();
		if ( isset( $request['before'] ) ) {
			$args['before'] = $request['before'];
		} elseif ( isset( $request['send_at'] ) ) {
			$args['before'] = $request['send_at'];
		}
		if ( isset( $request['recipients_page'] ) ) {
			$args['page'] = $request['recipients_page'];
		}
		$thread = new BP_Messages_Thread( $thread_id, '', $args );

		return $thread;
	}

	/**
	 * Get the message object thanks to its ID.
	 *
	 * @param int $message_id Message ID.
	 *
	 * @return BP_Messages_Message
	 * @since 0.1.0
	 */
	public function get_message_object( $message_id ) {
		return new BP_Messages_Message( $message_id );
	}

	/**
	 * Select the item schema arguments needed for the CREATABLE, EDITABLE and DELETABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$key                       = 'get_item';
		$args                      = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$args['id']['description'] = __( 'ID of the Messages Thread.', 'buddyboss' );

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// Edit the Thread ID description and default properties.
			$args['id']['description'] = __( 'ID of the Messages Thread. Required when replying to an existing Thread.', 'buddyboss' );
			$args['id']['default']     = 0;

			// Add the sender_id argument.
			$args['sender_id'] = array(
				'description'       => __( 'The user ID of the Message sender.', 'buddyboss' ),
				'required'          => false,
				'default'           => bp_loggedin_user_id(),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			// Edit subject's properties.
			$args['subject']['type']        = 'string';
			$args['subject']['default']     = false;
			$args['subject']['description'] = __( 'Subject of the Message initializing the Thread.', 'buddyboss' );

			// Edit message's properties.
			$args['message']['type']        = 'string';
			$args['message']['required']    = false;
			$args['message']['description'] = __( 'Content of the Message to add to the Thread.', 'buddyboss' );

			// Edit recipients properties.
			$args['recipients']['required']          = false;
			$args['recipients']['items']             = array( 'type' => 'integer' );
			$args['recipients']['sanitize_callback'] = 'wp_parse_id_list';
			$args['recipients']['validate_callback'] = 'rest_validate_request_arg';
			$args['recipients']['description']       = __( 'The list of the recipients user IDs of the Message.', 'buddyboss' );

			// Remove unused properties for this transport method.
			unset( $args['subject']['properties'], $args['message']['properties'] );

			$args['send_at'] = array(
				'description'       => __( 'Messages send date according UTC time and date.', 'buddyboss' ),
				'required'          => false,
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			);

		} else {
			unset( $args['sender_id'], $args['subject'], $args['message'], $args['recipients'] );

			if (
				WP_REST_Server::READABLE === $method ||
				WP_REST_Server::EDITABLE === $method ||
				WP_REST_Server::DELETABLE === $method
			) {
				$args['recipients_pagination'] = array(
					'description' => __( 'Load recipients in a paginated manner.', 'buddyboss' ),
					'required'    => false,
					'default'     => false,
					'type'        => 'boolean',
				);

				$args['recipients_page'] = array(
					'description'       => __( 'Current page of the recipients.', 'buddyboss' ),
					'required'          => false,
					'default'           => 1,
					'minimum'           => 1,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';

				$args['message_id'] = array(
					'description'       => __( 'By default the latest message of the thread will be updated. Specify this message ID to edit another message of the thread.', 'buddyboss' ),
					'required'          => false,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			if ( WP_REST_Server::DELETABLE === $method ) {
				$key = 'delete_item';

				$args['user_id'] = array(
					'description'       => __( 'The user ID to remove from the thread', 'buddyboss' ),
					'required'          => true,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
					'default'           => bp_loggedin_user_id(),
				);
			}

			if ( WP_REST_Server::READABLE === $method ) {
				$args['is_hidden'] = array(
					'description'       => __( 'List the archived threads.', 'buddyboss' ),
					'required'          => false,
					'default'           => false,
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}
		}

		if (
			WP_REST_Server::READABLE === $method ||
			WP_REST_Server::EDITABLE === $method ||
			WP_REST_Server::DELETABLE === $method
		) {
			$args['before'] = array(
				'description'       => __( 'Messages to get before a specific date.', 'buddyboss' ),
				'required'          => false,
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_messages_{$key}_query_arguments", $args, $method );
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
				'id'                        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Thread.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'message_id'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the latest message of the Thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'last_sender_id'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of latest sender of the Thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'subject'                   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Title of the latest message of the Thread.', 'buddyboss' ),
					'type'        => 'object',
					'arg_options' => array(
						'sanitize_callback' => null,
						'validate_callback' => null,
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Title of the latest message of the Thread, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'edit' ),
							'default'     => false,
						),
						'rendered' => array(
							'description' => __( 'Title of the latest message of the Thread, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
							'default'     => false,
						),
					),
				),
				'excerpt'                   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Summary of the latest message of the Thread.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'arg_options' => array(
						'sanitize_callback' => null,
						'validate_callback' => null,
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Summary for the latest message of the Thread, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML summary for the latest message of the Thread, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'message'                   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Content of the latest message of the Thread.', 'buddyboss' ),
					'type'        => 'object',
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => null,
						'validate_callback' => null,
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the latest message of the Thread, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the latest message of the Thread, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'date'                      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( "The date the latest message of the Thread, in the site's timezone.", 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'start_date'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( "The date the first message of the Thread, in the site's timezone.", 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'unread_count'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Total count of unread messages into the Thread for the requested user.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'sender_ids'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The list of user IDs for all messages in the Thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'current_user'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current Logged in user\'s ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'can_send_message'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current user can send message or not.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'avatar'                    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Avatar URLs for the author of the activity.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => array(
						'full'  => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
							'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_full_width() ), bp_core_number_format( bp_core_avatar_full_height() ) ),
							'type'        => 'string',
							'format'      => 'uri',
						),
						'thumb' => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							/* translators: 1: Thumb avatar width in pixels. 2: Thumb avatar height in pixels */
							'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_thumb_width() ), bp_core_number_format( bp_core_avatar_thumb_height() ) ),
							'type'        => 'string',
							'format'      => 'uri',
						),
					),
				),
				'is_group'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group ID if message sent from group.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'is_group_thread'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether is a group thread or not.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'group_name'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group name if thread created from group.  ', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'group_link'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The permalink to the Group on the site.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'group_message_users'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Thread for all group users or selected one.', 'buddyboss' ),
					'type'        => 'string',
				),
				'group_message_type'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Thread type its from all or private one.', 'buddyboss' ),
					'type'        => 'string',
				),
				'group_message_from'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Message from group or not.', 'buddyboss' ),
					'type'        => 'string',
				),
				'recipients_count'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Recipient users count.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'recipients_per_page'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Number of recipient loading per page.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'recipients_total_pages'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Total number of pages for the recipients.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'recipients'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The list of recipient User Objects involved into the Thread.', 'buddyboss' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
				),
				'message_per_page'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Number of message loading per page.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'messages_count'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Total message count into the thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'next_messages_timestamp'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Last Message time stamp from response.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'messages'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'List of message objects for the thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
				),
				'loggedin_user_permissions' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'List of user permission for loggedin user.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
				),
				'is_hidden'                 => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether to check the thread archived or not for loggedin user.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'starred_message_ids'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'List of starred message IDs.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'default'     => array(),
				),
			),
		);

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
		$params['context']['default'] = 'view';
		$boxes                        = array( 'sentbox', 'inbox' );

		if ( bp_is_active( 'messages', 'star' ) ) {
			$boxes[] = 'starred';
		}

		$params['box'] = array(
			'description'       => __( 'Filter the result by box.', 'buddyboss' ),
			'default'           => 'inbox',
			'type'              => 'string',
			'enum'              => $boxes,
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Filter the result by thread status.', 'buddyboss' ),
			'default'           => 'all',
			'type'              => 'string',
			'enum'              => array( 'all', 'read', 'unread' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit result to messages created by a specific user.', 'buddyboss' ),
			'default'           => bp_loggedin_user_id(),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_messages_collection_params', $params );
	}

	/**
	 * Get current user's permission.
	 *
	 * @param object          $recipient The recipient object.
	 * @param WP_REST_Request $request   Full details about the request.
	 *
	 * @return array                     Get current user permission.
	 * @since 0.1.0
	 */
	public function get_current_user_permissions( $recipient, $request ) {
		$retval = array(
			'unread'              => false,
			'delete_messages'     => false,
			'delete_thread'       => false,
			'can_manage_media'    => false,
			'can_manage_video'    => false,
			'can_manage_document' => false,
			'hide_thread'         => false,
		);

		$retval = apply_filters( 'bp_rest_messages_current_user_permissions', $retval, $recipient, $request );

		if ( empty( $recipient ) || empty( $recipient->thread_id ) || empty( $recipient->user_id ) ) {
			return $retval;
		}

		$retval['unread']          = true;
		$retval['delete_messages'] = true;
		$retval['delete_thread']   = bp_user_can(
			$recipient->user_id,
			'bp_moderate',
			array(
				'site_id' => bp_get_root_blog_id(),
			)
		);

		$thread = new BP_Messages_Thread( $recipient->thread_id );

		$is_group_message_thread = false;
		$first_message           = BP_Messages_Thread::get_first_message( $thread->thread_id );
		$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
		$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
		$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
		$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

		if ( 'group' === $message_from && $thread->thread_id === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
			$is_group_message_thread = true;
		}

		$thread->group_id        = $group_id;
		$thread->is_group_thread = $is_group_message_thread;

		if (
			bp_is_active( 'media' ) &&
			function_exists( 'bb_user_has_access_upload_media' ) &&
			bb_user_has_access_upload_media( $group_id, $recipient->user_id, 0, $thread->thread_id )
		) {
			$retval['can_manage_media'] = apply_filters( 'bp_user_can_create_message_media', true, $thread, $recipient->user_id );
		}

		if (
			bp_is_active( 'video' ) &&
			function_exists( 'bb_user_has_access_upload_video' ) &&
			bb_user_has_access_upload_video( $group_id, $recipient->user_id, 0, $thread->thread_id )
		) {
			$retval['can_manage_video'] = apply_filters( 'bp_user_can_create_message_video', true, $thread, $recipient->user_id );
		}

		if (
			bp_is_active( 'document' ) &&
			function_exists( 'bb_user_has_access_upload_document' ) &&
			bb_user_has_access_upload_document( $group_id, $recipient->user_id, 0, $thread->thread_id )
		) {
			$retval['can_manage_document'] = apply_filters( 'bp_user_can_create_message_document', true, $thread, $recipient->user_id );
		}

		if ( isset( $recipient->is_hidden ) ) {
			$retval['hide_thread'] = true;
		}

		return $retval;
	}

	/**
	 * Exclude logged in member from recipients list.
	 * - from: bp_nouveau_ajax_search_recipients_exclude_current()
	 *
	 * @param array $user_query Array of argument.
	 *
	 * @return mixed
	 * @since 0.1.0
	 */
	public function bp_rest_nouveau_ajax_search_recipients_exclude_current( $user_query ) {
		if ( isset( $user_query['exclude'] ) && ! $user_query['exclude'] ) {
			$user_query['exclude'] = array();
		} elseif ( ! empty( $user_query['exclude'] ) ) {
			$user_query['exclude'] = wp_parse_id_list( $user_query['exclude'] );
		}

		$user_query['exclude'][] = get_current_user_id();

		return $user_query;
	}

	/**
	 * Get the search recipients schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_search_recipients_items_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_search_recipients',
			'type'       => 'object',
			'properties' => array(
				'id'         => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for user.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'userhandle' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'User\'s nickname as handle.', 'buddyboss' ),
					'type'        => 'string',
				),
				'text'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Display Name for the user.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'image'      => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Avatar URL for the user.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
			),
		);

		/**
		 * Filters the message search recipients schema.
		 *
		 * @since 0.1.0
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_message_search_recipients_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Add Global header for the unread message count.
	 *
	 * @param WP_HTTP_Response $result  Rest response.
	 * @param WP_REST_Server   $server  Rest Server.
	 * @param WP_REST_Request  $request Rest request.
	 *
	 * @return WP_HTTP_Response
	 */
	public function bp_rest_post_dispatch( $result, $server, $request ) {
		if ( function_exists( 'messages_get_unread_count' ) ) {
			$result->header( 'bbp-unread-messages', (int) messages_get_unread_count( bp_loggedin_user_id() ) );
		}

		return $result;
	}

	/**
	 * Get avatars for the messages thread.
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $user_id   User ID.
	 *
	 * @return mixed|void
	 */
	public function bp_rest_messages_get_avatars( $thread_id, $user_id = 0 ) {
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

					if ( ! in_array( $message->sender_id, $avatars_user_ids, true ) ) {
						$avatars_user_ids[] = $message->sender_id;
					}
				}
			}
		} else {
			unset( $recepients[ $user_id ] );
			$avatars_user_ids[] = current( $recepients )->user_id;
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
					'full'  => bp_core_fetch_avatar(
						array(
							'item_id' => $avatar_user_id,
							'html'    => false,
							'type'    => 'full',
							'object'  => 'user',
						)
					),
					'thumb' => bp_core_fetch_avatar(
						array(
							'item_id' => $avatar_user_id,
							'html'    => false,
							'object'  => 'user',
						)
					),
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

				$group_avatar = array(
					'thumb' => '',
					'full'  => '',
				);

				if ( bp_is_active( 'groups' ) ) {
					$group_name = bp_get_group_name( groups_get_group( $group_id ) );

					if ( ! bp_disable_group_avatar_uploads() ) {
						$group_avatar = array(
							'thumb' => bp_core_fetch_avatar(
								array(
									'html'    => false,
									'object'  => 'group',
									'item_id' => $group_id,
									'type'    => 'thumb',
								)
							),
							'full'  => bp_core_fetch_avatar(
								array(
									'html'    => false,
									'object'  => 'group',
									'item_id' => $group_id,
									'type'    => 'full',
								)
							),
						);
					} else {
						$group_avatar = array(
							'thumb' => function_exists( 'bb_get_buddyboss_group_avatar' ) ? bb_get_buddyboss_group_avatar( 'thumb' ) : buddypress()->plugin_url . 'bp-core/images/group-avatar-buddyboss-50.png',
							'full'  => function_exists( 'bb_get_buddyboss_group_avatar' ) ? bb_get_buddyboss_group_avatar() : buddypress()->plugin_url . 'bp-core/images/group-avatar-buddyboss.png',
						);
					}
				} else {
					$prefix       = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
					$groups_table = $prefix . 'bp_groups';
					// phpcs:ignore
					$group_name   = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;

					if ( ! empty( $group_name ) && ! bp_disable_group_avatar_uploads() ) {
						$directory                = 'group-avatars';
						$avatar_size              = '-bpfull';
						$legacy_group_avatar_name = '-groupavatar-full';
						$legacy_user_avatar_name  = '-avatar2';
						$avatar_folder_dir        = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
						$avatar_folder_url        = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

						$avatar = bp_core_get_group_avatar( $legacy_user_avatar_name, $legacy_group_avatar_name, $avatar_size, $avatar_folder_dir, $avatar_folder_url );
						if ( '' !== $avatar ) {
							$group_avatar = array(
								'thumb' => $avatar,
								'full'  => $avatar,
							);
						}
					} elseif ( function_exists( 'bb_attachments_get_default_profile_group_avatar_image' ) ) {
						$group_avatar = array(
							'thumb' => bb_attachments_get_default_profile_group_avatar_image(
								array(
									'object' => 'group',
									'type'   => 'thumb',
								)
							),
							'full'  => bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) ),
						);
					}
				}

				if ( ! empty( $group_avatar ) ) {
					$avatar_urls = array( $group_avatar );
				}
			}
		}

		return apply_filters( 'bp_rest_messages_get_avatars', $avatar_urls, $thread_id, $user_id );
	}

	/**
	 * Update the current message ID.
	 *
	 * @param int $message_id Message ID.
	 *
	 * @return int
	 */
	public function bb_get_the_thread_message_id( $message_id ) {
		if ( 0 === $this->message_id ) {
			return $message_id;
		}

		return $this->message_id;
	}

	/**
	 * Given permission that current user can send message or not.
	 *
	 * @param int $thread_id Current thread id.
	 * @param int $user_id   Logged in user id.
	 *
	 * @return bool|WP_Error
	 */
	public function bp_rest_can_send_message( $thread_id, $user_id ) {
		global $wpdb, $bp;

		$thread_recepients = BP_Messages_Thread::get(
			array(
				'include_threads' => array( $thread_id ),
				'count_total'     => false,
				'per_page'        => - 1,
			)
		);

		$recepients = ! empty( $thread_recepients ) && ! empty( $thread_recepients['recipients'] ) ? $thread_recepients['recipients'] : array();
		$recepients = array_column( $recepients, null, 'user_id' );

		$current_recepient = isset( $recepients[ $user_id ] ) ? $recepients[ $user_id ] : false;

		// Prioritise the archived notice for the app.
		if ( ! empty( $current_recepient ) && $current_recepient->is_hidden ) {
			return new WP_Error(
				'bp_rest_archived_conversation',
				__( 'You can\'t send messages in conversations you\'ve archived.', 'buddyboss' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$is_group_message_thread = bb_messages_is_group_thread( (int) $thread_id );
		if ( $is_group_message_thread ) {
			$first_message = BP_Messages_Thread::get_first_message( $thread_id );
			$group_id      = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
			if (
				true === bp_disable_group_messages() &&
				$is_group_message_thread &&
				$group_id &&
				(
					(
						bp_is_active( 'groups' ) &&
						! groups_can_user_manage_messages( $user_id, $group_id )
					) ||
					! bp_is_active( 'groups' )
				)
			) {
				$status = ( bp_is_active( 'groups' ) ? bp_group_get_message_status( $group_id ) : '' );
				if ( 'admins' === $status ) {
					return new WP_Error(
						'bp_rest_authorization_required',
						__( 'Only group organizers can send messages to this group.', 'buddyboss' ),
						array( 'status' => rest_authorization_required_code() )
					);
				} elseif ( 'mods' === $status ) {
					return new WP_Error(
						'bp_rest_authorization_required',
						__( 'Only group organizers and moderators can send messages to this group.', 'buddyboss' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
			}
		} else {

			// Check user is deleted.
			if ( 2 >= count( $recepients ) ) {
				$another_recepient = $recepients;
				unset( $another_recepient[ $user_id ] );
				$recipient_id = ( ! empty( $another_recepient ) ? current( $another_recepient )->user_id : 0 );
				$is_deleted   = get_user_by( 'id', $recipient_id );

				if ( ! $is_deleted ) {
					return new WP_Error(
						'bp_rest_deleted_unset',
						__( 'Unable to send new messages at this time.', 'buddyboss' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
			}

			// Check moderation if user blocked or not for single user thread.
			if (
				bp_is_active( 'moderation' ) &&
				! empty( $recepients ) &&
				count( $recepients ) === 2
			) {
				$another_recepient = $recepients;
				unset( $another_recepient[ $user_id ] );
				$recipient_id = ( ! empty( $another_recepient ) ? current( $another_recepient )->user_id : 0 );

				if ( bp_moderation_is_user_suspended( $recipient_id ) ) {
					return new WP_Error(
						'bp_rest_user_suspended',
						__( 'Unable to send new messages to this member.', 'buddyboss' ),
						array( 'status' => 400 )
					);
				} elseif ( function_exists( 'bb_moderation_is_user_blocked_by' ) && bb_moderation_is_user_blocked_by( $recipient_id ) ) {
					return new WP_Error(
						'bp_rest_user_suspended',
						__( 'Unable to send new messages to this member.', 'buddyboss' ),
						array( 'status' => 400 )
					);
				} elseif ( bp_moderation_is_user_blocked( $recipient_id ) ) {
					return new WP_Error(
						'bp_rest_user_blocked',
						__( 'You can\'t send messages to members you have blocked.', 'buddyboss' ),
						array( 'status' => 400 )
					);
				}
			}

			// Check recipients if connected or not.
			if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) && count( $recepients ) < 3 ) {
				foreach ( $recepients as $recepient ) {
					if (
						(int) $user_id !== (int) $recepient->user_id &&
						! bb_messages_user_can_send_message(
							array(
								'sender_id'     => (int) $user_id,
								'recipients_id' => (int) $recepient->user_id,
							)
						)
					) {
						return new WP_Error(
							'bp_rest_friendship_required',
							__( 'You must be connected to this member to send them a message.', 'buddyboss' ),
							array( 'status' => rest_authorization_required_code() )
						);
					}
				}
			}
		}

		$user_can_send_message = ( $is_group_message_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : bb_user_can_send_messages( true, $recepients );

		if ( is_wp_error( $user_can_send_message ) && ! empty( $user_can_send_message->get_error_messages() ) ) {
			$messages = $user_can_send_message->get_error_messages();
			return new WP_Error(
				'bp_rest_authorization_required',
				is_array( $messages ) ? current( $messages ) : $messages,
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;

	}

	/**
	 * Filter to change where for get hidden threads.
	 *
	 * @param array $where Where conditions SQL statement.
	 * @param array $args  Array of parsed arguments for the get method.
	 *
	 * @return string
	 */
	public function bb_rest_messages_set_hidden_where_query( $where, $args ) {
		global $rest_request;

		if ( ! empty( $args['is_hidden'] ) ) {
			$where = $where . ' AND r.is_hidden = 1 ';
		} elseif ( isset( $args['is_hidden'] ) && false === (bool) $args['is_hidden'] ) {
			$where = $where . ' AND r.is_hidden = 0 ';
		}

		if ( ! empty( $rest_request ) && ! empty( $rest_request['exclude'] ) ) {
			$exclude = implode( ',', wp_parse_id_list( $rest_request['exclude'] ) );
			$where   = $where . " AND m.thread_id NOT IN ({$exclude}) ";
		}

		return $where;
	}

	/**
	 * Function to check the thread id hidden/archive or not.
	 *
	 * @param int $thread_id Thread id.
	 * @param int $user_id   User id.
	 *
	 * @return bool
	 */
	public function bb_rest_thread_is_hidden( $thread_id, $user_id ) {
		global $wpdb, $bp;
		// Check the thread is hide/archived or not.
		return (bool) $wpdb->query( $wpdb->prepare( "SELECT * FROM {$bp->messages->table_name_recipients} WHERE is_hidden = %d AND thread_id = %d AND user_id = %d", 1, $thread_id, $user_id ) );
	}

	/**
	 * Function to validate message content if media, video, document and gif available and content empty.
	 *
	 * @param bool $is_valid True if message is valid, false otherwise.
	 *
	 * @return bool
	 */
	public function bb_rest_is_validate_message_content( $is_valid ) {
		return true;
	}
}
