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
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->messages->id;
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
					'term' => array(
						'description' => __( 'Text for search recipients.', 'buddyboss' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_recipients_items' ),
					'permission_callback' => array( $this, 'search_recipients_items_permissions_check' ),
				),
				'schema' => apply_filters(
					'bp_rest_message_search_recipients_schema',
					$this->add_additional_fields_schema(
						array(
							'$schema'    => 'http://json-schema.org/draft-04/schema#',
							'title'      => 'bp_messages',
							'type'       => 'object',
							'properties' => array(
								'id'   => array(
									'context'     => array( 'view', 'edit' ),
									'description' => __( 'A unique numeric ID for user.', 'buddyboss' ),
									'type'        => 'integer',
								),
								'text' => array(
									'context'     => array( 'view', 'edit' ),
									'description' => __( 'Display Name for the user.', 'buddyboss' ),
									'readonly'    => true,
									'type'        => 'integer',
								),
							),
						)
					)
				),
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
	 */
	public function get_items( $request ) {
		$args = array(
			'user_id'      => $request['user_id'],
			'box'          => $request['box'],
			'type'         => $request['type'],
			'page'         => $request['page'],
			'per_page'     => $request['per_page'],
			'search_terms' => $request['search'],
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

		// Actually, query it.
		$messages_box = new BP_Messages_Box_Template( $args );

		$retval = array();
		if ( ! empty( $messages_box->threads ) ) {
			foreach ( (array) $messages_box->threads as $thread ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $thread, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $messages_box->total_thread_count, $args['per_page'] );

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
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to see the messages.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$user = bp_rest_get_user( $request['user_id'] );

		if ( true === $retval && ! $user instanceof WP_User ) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid member ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && (int) bp_loggedin_user_id() !== $user->ID && ! bp_current_user_can( 'bp_moderate' ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you cannot view the messages.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
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
	 */
	public function get_item( $request ) {
		$thread = $this->get_thread_object( $request['id'] );

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
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to see this thread.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$thread = $this->get_thread_object( $request['id'] );

		if ( true === $retval && empty( $thread->thread_id ) ) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Sorry, this thread does not exist.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		} else {
			$id = messages_check_thread_access( $thread->thread_id );
			if ( true === $retval && is_null( $id ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to see this thread.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			if ( true === $retval ) {
				$retval = true;
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

		// Create the message or the reply.
		$thread_id = messages_new_message( $this->prepare_item_for_database( $request ) );

		// Validate it created a Thread or was added to it.
		if ( false === $thread_id ) {
			return new WP_Error(
				'bp_rest_messages_create_failed',
				__( 'There was an error trying to create the message.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Make sure to get the newest message to update REST Additional fields.
		$thread        = $this->get_thread_object( $thread_id );
		$last_message  = wp_list_filter( $thread->messages, array( 'id' => $thread->last_message_id ) );
		$last_message  = reset( $last_message );
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
		 * @param BP_Messages_Thread $thread  Thread object.
		 * @param WP_REST_Response   $retval  The response data.
		 * @param WP_REST_Request    $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_messages_create_item', $thread, $response, $request );

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
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to create a message.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
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
	 */
	public function search_recipients_items( $request ) {
		$term = $request->get_param( 'term' );

		if ( empty( $term ) ) {
			return new WP_Error(
				'bp_rest_term_required',
				__( 'Sorry, term is required parameter.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		add_filter(
			'bp_members_suggestions_query_args',
			array(
				$this,
				'bp_rest_nouveau_ajax_search_recipients_exclude_current',
			)
		);

		$results = bp_core_get_suggestions(
			array(
				'term' => sanitize_text_field( $term ),
				'type' => 'members',
			)
		);

		$results = apply_filters( 'bp_members_suggestions_results', $results );

		$retval = array_map(
			function ( $result ) {
				return array(
					'id'   => "@{$result->ID}",
					'text' => $result->name,
				);
			},
			$results
		);

		$response = rest_ensure_response( $retval );

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
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to search recipients.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
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
	 */
	public function update_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the thread.
		$thread = $this->get_thread_object( $request['id'] );
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
		$message_id = $thread->last_message_id;
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
		$retval    = true;
		$thread_id = messages_get_message_thread_id( $request['id'] );

		if ( ! is_user_logged_in() || ! messages_check_thread_access( $thread_id ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to star/unstar messages.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
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
	 * @api            {PATCH} /wp-json/buddyboss/v1/messages/:id Delete Thread
	 * @apiName        DeleteBBThread
	 * @apiGroup       Messages
	 * @apiDescription Delete thread
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id ID of the Messages Thread.
	 * @apiParam {Number} user_id The user ID to remove from the thread.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the thread before it's deleted.
		$thread   = $this->get_thread_object( $request['id'] );
		$previous = $this->prepare_item_for_response( $thread, $request );

		$user_id = bp_loggedin_user_id();
		if ( ! empty( $request['user_id'] ) ) {
			$user_id = $request['user_id'];
		}

		// Check the user is one of the recipients.
		$recipient_ids = wp_parse_id_list( wp_list_pluck( $thread->recipients, 'user_id' ) );

		// Delete a thread.
		if ( ! in_array( $user_id, $recipient_ids, true ) || ! messages_delete_thread( $thread->thread_id, $user_id ) ) {
			return new WP_Error(
				'bp_rest_messages_delete_thread_failed',
				__( 'There was an error trying to delete the thread.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

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
		$retval = $this->get_item_permissions_check( $request );

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
		$thread          = $this->get_thread_object( $request['id'] );

		if ( ! empty( $schema['properties']['id'] ) && ! empty( $request['id'] ) ) {
			$prepared_thread->thread_id = $request['id'];
		} elseif ( ! empty( $thread->thread_id ) ) {
			$prepared_thread->thread_id = $thread->thread_id;
		}

		if ( ! empty( $schema['properties']['sender_id'] ) && ! empty( $request['sender_id'] ) ) {
			$prepared_thread->sender_id = $thread->sender_id;
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

		if ( ! empty( $group_id ) && ! empty( $message_from ) && 'group' === $message_from ) {

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

			if ( empty( $group_name ) ) {
				$group_name = '"' . __( 'Deleted Group', 'buddyboss' ) . '"';
				if ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: %s: Group name */
						__( 'Sent from group %s to all group members.', 'buddyboss' ),
						$group_name
					);
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: %s: Group name */
						__( 'Sent from group %s to the people in this conversation.', 'buddyboss' ),
						$group_name
					);
				} elseif ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: %s: Group name */
						__( 'Sent from group %s individually to all group members.', 'buddyboss' ),
						$group_name
					);
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: %s: Group name */
						__( 'Sent from group %s to individual members.', 'buddyboss' ),
						$group_name
					);
				}
			} else {
				if ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: 1: Group link. 2: Group name. */
						__( 'Sent from group <a href="%1$s">%2$s</a> to all group members.', 'buddyboss' ),
						$group_link,
						$group_name
					);
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'open' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: 1: Group link. 2: Group name. */
						__( 'Sent from group <a href="%1$s">%2$s</a> to the people in this conversation.', 'buddyboss' ),
						$group_link,
						$group_name
					);
				} elseif ( $group_message_users && $group_message_type && 'all' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: 1: Group link. 2: Group name. */
						__( 'Sent from group <a href="%1$s">%2$s</a> individually to all group members.', 'buddyboss' ),
						$group_link,
						$group_name
					);
				} elseif ( $group_message_users && $group_message_type && 'individual' === $group_message_users && 'private' === $group_message_type ) {
					$group_text = sprintf(
						/* translators: 1: Group link. 2: Group name. */
						__( 'Sent from group <a href="%1$s">%2$s</a> to individual members.', 'buddyboss' ),
						$group_link,
						$group_name
					);
				}
			}

			if ( $message_left && 'yes' === $message_left ) {
				$message->message = sprintf(
					/* translators: %s: Group name */
					__( 'Left "%s"', 'buddyboss' ),
					ucwords( $group_name )
				);
			} elseif ( $message_deleted && 'yes' === $message_deleted ) {
				$message->message = __( 'This message was deleted.', 'buddyboss' );
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
			} elseif ( $message_joined && 'yes' === $message_joined ) {
				$message->message = sprintf(
					/* translators: %s: Group name */
					__( 'Joined "%s"', 'buddyboss' ),
					ucwords( $group_name )
				);
			} elseif ( 'This message was deleted.' === wp_strip_all_tags( $message->message ) ) {
				$message->message = wp_strip_all_tags( $message->message );
			} else {
				$message->message = $message->message;
			}
		}

		$data = array(
			'id'                        => (int) $message->id,
			'thread_id'                 => (int) $message->thread_id,
			'sender_id'                 => (int) $message->sender_id,
			'subject'                   => array(
				'raw'      => $message->subject,
				'rendered' => apply_filters( 'bp_get_message_thread_subject', wp_staticize_emoji( $message->subject ) ),
			),
			'message'                   => array(
				'raw'      => wp_strip_all_tags( $message->message ),
				'rendered' => apply_filters( 'bp_get_the_thread_message_content', wp_staticize_emoji( $message->message ) ),
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
		);

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

		$data = array_merge(
			$data,
			array(
				'thread_id'    => (int) $recipient->thread_id,
				'unread_count' => (int) $recipient->unread_count,
				'sender_only'  => (int) $recipient->sender_only,
				'is_deleted'   => (int) $recipient->is_deleted,
				'is_hidden'    => (int) ( isset( $recipient->is_hidden ) ? $recipient->is_hidden : 0 ),
			)
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

		$excerpt = '';
		if ( isset( $thread->last_message_content ) ) {
			$excerpt = wp_strip_all_tags( bp_create_excerpt( $thread->last_message_content, 75 ) );
		}

		$group_id      = bp_messages_get_meta( $thread->last_message_id, 'group_id', true );
		$first_message = BP_Messages_Thread::get_first_message( $thread->thread_id );

		$avatar = array(
			'full'  => bp_core_fetch_avatar(
				array(
					'item_id' => $thread->last_message_id,
					'html'    => false,
					'type'    => 'full',
					'object'  => 'user',
				)
			),
			'thumb' => bp_core_fetch_avatar(
				array(
					'item_id' => $thread->last_message_id,
					'html'    => false,
					'object'  => 'user',
				)
			),
		);

		if ( ! empty( $group_id ) ) {
			$group_id = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		}

		if ( (int) $group_id > 0 ) {
			$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
			$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
			$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
			$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
			$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

			if ( bp_is_active( 'groups' ) ) {
				$group_name = bp_get_group_name( groups_get_group( $group_id ) );
				$avatar     = array(
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
				$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
			} else {

				$prefix       = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table = $prefix . 'bp_groups';
				// phpcs:ignore
				$group_name = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok;
				$avatar     = array(
					'thumb' => buddypress()->plugin_url . 'bp-core/images/mystery-group.png',
					'full'  => buddypress()->plugin_url . 'bp-core/images/mystery-group.png',
				);
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

		$data = array(
			'id'                  => $thread->thread_id,
			'message_id'          => $thread->last_message_id,
			'last_sender_id'      => $thread->last_sender_id,
			'subject'             => array(
				'raw'      => $thread->last_message_subject,
				'rendered' => apply_filters( 'bp_get_message_thread_subject', wp_staticize_emoji( $thread->last_message_subject ) ),
			),
			'excerpt'             => array(
				'raw'      => $excerpt,
				'rendered' => apply_filters( 'bp_get_message_thread_excerpt', $excerpt ),
			),
			'message'             => array(
				'raw'      => $thread->last_message_content,
				'rendered' => apply_filters( 'bp_get_message_thread_content', wp_staticize_emoji( $thread->last_message_content ) ),
			),
			'date'                => bp_rest_prepare_date_response( $thread->last_message_date ),
			'start_date'          => bp_rest_prepare_date_response( ( isset( $thread->first_message_date ) ? $thread->first_message_date : $thread->last_message_date ) ),
			'unread_count'        => ! empty( $thread->unread_count ) ? $thread->unread_count : 0,
			'sender_ids'          => $thread->sender_ids,
			'current_user'        => ( isset( $request['user_id'] ) && ! empty( $request['user_id'] ) ? $request['user_id'] : bp_current_user_id() ),
			'avatar'              => $avatar,
			'is_group'            => ( ! empty( $group_id ) ? $group_id : false ),
			'is_group_thread'     => ( isset( $is_group_thread ) ? $is_group_thread : '' ),
			'group_name'          => ( isset( $group_name ) ? $group_name : '' ),
			'group_link'          => ( isset( $group_link ) ? $group_link : '' ),
			'group_message_users' => ( isset( $message_users ) ? $message_users : '' ),
			'group_message_type'  => ( isset( $message_type ) ? $message_type : '' ),
			'group_message_from'  => ( isset( $message_from ) ? $message_from : '' ),
			'recipients_count'    => ( ( isset( $thread->recipients ) && count( $thread->recipients ) > 1 ) ? count( $thread->recipients ) - ( isset( $request['user_id'] ) && ! empty( $request['user_id'] ) ? 1 : 0 ) : 0 ),
			'recipients'          => array(),
			'messages'            => array(),
		);

		if ( $thread->messages ) {
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

		// Pluck starred message ids.
		$data['starred_message_ids'] = array_keys( array_filter( wp_list_pluck( $data['messages'], 'is_starred', 'id' ) ) );

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $thread ) );

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
	 * @param BP_Messages_Thread $thread Thread object.
	 *
	 * @return array Links for the given thread.
	 * @since 0.1.0
	 */
	protected function prepare_links( $thread ) {
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
	 * @param int $thread_id Thread ID.
	 *
	 * @return BP_Messages_Thread
	 * @since 0.1.0
	 */
	public function get_thread_object( $thread_id ) {
		return new BP_Messages_Thread( $thread_id );
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
			$args['message']['description'] = __( 'Content of the Message to add to the Thread.', 'buddyboss' );

			// Edit recipients properties.
			$args['recipients']['required']          = true;
			$args['recipients']['items']             = array( 'type' => 'integer' );
			$args['recipients']['sanitize_callback'] = 'wp_parse_id_list';
			$args['recipients']['validate_callback'] = 'rest_validate_request_arg';
			$args['recipients']['description']       = __( 'The list of the recipients user IDs of the Message.', 'buddyboss' );

			// Remove unused properties for this transport method.
			unset( $args['subject']['properties'], $args['message']['properties'] );

		} else {
			unset( $args['sender_id'], $args['subject'], $args['message'], $args['recipients'] );

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
				'id'                  => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Thread.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'message_id'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The ID of the latest message of the Thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'last_sender_id'      => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The ID of latest sender of the Thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'subject'             => array(
					'context'     => array( 'view', 'edit' ),
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
							'context'     => array( 'edit' ),
							'default'     => false,
						),
						'rendered' => array(
							'description' => __( 'Title of the latest message of the Thread, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'default'     => false,
						),
					),
				),
				'excerpt'             => array(
					'context'     => array( 'view', 'edit' ),
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
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML summary for the latest message of the Thread, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'message'             => array(
					'context'     => array( 'view', 'edit' ),
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
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the latest message of the Thread, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'date'                => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( "The date the latest message of the Thread, in the site's timezone.", 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'start_date'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( "The date the first message of the Thread, in the site's timezone.", 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'unread_count'        => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Total count of unread messages into the Thread for the requested user.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'sender_ids'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The list of user IDs for all messages in the Thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'current_user'        => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Current Logged in user\'s ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'avatar'              => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Avatar URLs for the author of the activity.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => array(
						'full'  => array(
							'context'     => array( 'view', 'edit' ),
							/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
							'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddyboss' ), number_format_i18n( bp_core_avatar_full_width() ), number_format_i18n( bp_core_avatar_full_height() ) ),
							'type'        => 'string',
							'format'      => 'uri',
						),
						'thumb' => array(
							'context'     => array( 'view', 'edit' ),
							/* translators: 1: Thumb avatar width in pixels. 2: Thumb avatar height in pixels */
							'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddyboss' ), number_format_i18n( bp_core_avatar_thumb_width() ), number_format_i18n( bp_core_avatar_thumb_height() ) ),
							'type'        => 'string',
							'format'      => 'uri',
						),
					),
				),
				'is_group'            => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Group ID if message sent from group.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'is_group_thread'     => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Whether is a group thread or not.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'group_name'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Group name if thread created from group.  ', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'group_link'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The permalink to the Group on the site.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'group_message_users' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Thread for all group users or selected one.', 'buddyboss' ),
					'type'        => 'string',
				),
				'group_message_type'  => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Thread type its from all or private one.', 'buddyboss' ),
					'type'        => 'string',
				),
				'group_message_from'  => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Message from group or not.', 'buddyboss' ),
					'type'        => 'string',
				),
				'recipients_count'    => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Recipient users count.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'recipients'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The list of recipient User Objects involved into the Thread.', 'buddyboss' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
				),
				'messages'            => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'List of message objects for the thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
				),
				'starred_message_ids' => array(
					'context'     => array( 'view', 'edit' ),
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
		return array(
			'unread'          => true,
			'hide_thread'     => (bool) ( isset( $recipient->is_hidden ) && empty( $recipient->is_hidden ) ),
			'delete_messages' => true,
			'delete_thread'   => bp_user_can(
				$recipient->user_id,
				'bp_moderate',
				array(
					'site_id' => bp_get_root_blog_id(),
				)
			),
		);
	}

	/**
	 * Exclude logged in member from recipients list.
	 *
	 * @param array $user_query Array of argument.
	 *
	 * @return mixed
	 * @since 0.1.0
	 */
	public function bp_rest_nouveau_ajax_search_recipients_exclude_current( $user_query ) {
		if ( isset( $user_query['exclude'] ) && ! $user_query['exclude'] ) {
			$user_query['exclude'] = array();
		}

		$user_query['exclude'][] = get_current_user_id();

		return $user_query;
	}
}
