<?php
/**
 * BP REST: BP_REST_Friends_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Friendship endpoints.
 *
 * /friends/
 * /friends/{id}
 *
 * @since 0.1.0
 */
class BP_REST_Friends_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->friends->id;
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
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Identifier for the friendship.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'edit',
							)
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'edit',
							)
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve friendships.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/friends Friendships
	 * @apiName        GetBBFriendships
	 * @apiGroup       Connections
	 * @apiDescription Retrieve Friendships
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {Number} user_id ID of the user whose friends are being retrieved.
	 * @apiParam {Number} [is_confirmed=0] Wether the friendship has been accepted.
	 * @apiParam {Number} [id] ID of a specific friendship to retrieve.
	 * @apiParam {Number} [initiator_id] ID of the friendship initiator.
	 * @apiParam {Number} [friend_id] ID of a specific friendship to retrieve.
	 * @apiParam {Number=0,1} [is_confirmed=0] Filter based on IsConfirmed
	 * @apiParam {String=date_created,initiator_user_id,friend_user_id,id} [order_by=date_created] Column name to order the results by.
	 * @apiParam {String=asc,desc} [order=desc] Order results ascending or descending.
	 */
	public function get_items( $request ) {
		$args = array(
			'id'                => $request->get_param( 'id' ),
			'initiator_user_id' => $request->get_param( 'initiator_id' ),
			'friend_user_id'    => $request->get_param( 'friend_id' ),
			'is_confirmed'      => $request->get_param( 'is_confirmed' ),
			'order_by'          => $request->get_param( 'order_by' ),
			'sort_order'        => strtoupper( $request->get_param( 'order' ) ),
			'page'              => $request->get_param( 'page' ),
			'per_page'          => $request->get_param( 'per_page' ),
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_friends_get_items_query_args', $args, $request );

		// null is the default values.
		foreach ( $args as $key => $value ) {
			if ( empty( $value ) && 'is_confirmed' !== $key ) {
				$args[ $key ] = null;
			}
		}

		// Check if user is valid.
		$user = get_user_by( 'id', $request->get_param( 'user_id' ) );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'bp_rest_friends_get_items_user_failed',
				__( 'There was a problem confirming if user is valid.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Actually, query it.
		$friendships = BP_Friends_Friendship::get_friendships( $user->ID, $args );

		$retval = array();
		foreach ( (array) $friendships as $friendship ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $friendship, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $friendships ), $args['per_page'] );

		/**
		 * Fires after friendships are fetched via the REST API.
		 *
		 * @param array            $friendships Fetched friendships.
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_friends_get_items', $friendships, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to friendship items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the friends `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve single friendship.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/friends/:id Friendship
	 * @apiName        GetBBFriendship
	 * @apiGroup       Connections
	 * @apiDescription Retrieve single friendship
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id Identifier for the friendship.
	 */
	public function get_item( $request ) {

		// Get friendship object.
		$friendship = $this->get_friendship_object( $request['id'] );

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid friendship ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $friendship, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires before a friendship is retrieved via the REST API.
		 *
		 * @param BP_Friends_Friendship $friendship The friendship object.
		 * @param WP_REST_Response      $response   The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_friends_get_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get a friendship.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the friendship `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a new friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 * @api            {POST} /wp-json/buddyboss/v1/friends/ Create Friendship
	 * @apiName        CreateBBFriendship
	 * @apiGroup       Connections
	 * @apiDescription Create friendship
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} initiator_id User ID of the friendship initiator.
	 * @apiParam {Number} friend_id User ID of the `friend` - the one invited to the friendship.
	 * @apiParam {Boolean} [force] Whether to force friendship acceptance.
	 */
	public function create_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$initiator_id = get_user_by( 'id', $request['initiator_id'] );
		$friend_id    = get_user_by( 'id', $request['friend_id'] );

		// Check if users are valid.
		if ( ! $initiator_id || ! $friend_id ) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'There was a problem confirming if user is a valid one.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Check if users are friends or if there is a friendship request.
		if ( 'not_friends' !== friends_check_friendship_status( $initiator_id->ID, $friend_id->ID ) ) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'Those users are already friends or have sent friendship request(s) recently.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$is_moderator = bp_current_user_can( 'bp_moderate' );

		// Only admins can create friendship requests for other people.
		if ( ! in_array( bp_loggedin_user_id(), array( $initiator_id->ID, $friend_id->ID ), true ) && ! $is_moderator ) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'You are not allowed to perform this action.', 'buddyboss' ),
				array(
					'status' => 403,
				)
			);
		}

		// Only admins can force a friendship request.
		$force = false;
		if ( true === $request->get_param( 'force' ) && $is_moderator ) {
			$force = true;
		}

		// Adding friendship.
		if ( ! friends_add_friend( $initiator_id->ID, $friend_id->ID, $request['force'] ) ) {
			return new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'There was a problem sending the connection request.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Get friendship.
		$friendship = $this->get_friendship_object(
			BP_Friends_Friendship::get_friendship_id( $initiator_id->ID, $friend_id->ID )
		);

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Friendship does not exist.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $friendship, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a friendship is created via the REST API.
		 *
		 * @param BP_Friends_Friendship $friendship The friendship object.
		 * @param WP_REST_Response      $retval     The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_friends_create_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the friends `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/friends/:id Update Friendship
	 * @apiName        UpdateBBFriendship
	 * @apiGroup       Connections
	 * @apiDescription Update friendship
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id Identifier for the friendship.
	 */
	public function update_item( $request ) {
		$request->set_param( 'context', 'edit' );

		// Get friendship object.
		$friendship = $this->get_friendship_object( $request['id'] );

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid friendship ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Accept friendship.
		if ( ! friends_accept_friendship( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_friends_cannot_update_item',
				__( 'Could not accept friendship.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Getting new friendship object.
		$friendship = $this->get_friendship_object( $friendship->id );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $friendship, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a friendship is updated via the REST API.
		 *
		 * @param BP_Friends_Friendship $friendship Friendship object.
		 * @param WP_REST_Response      $response   The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_friends_update_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the friendship `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Unfriend a friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/friends/ Unfriend a friendship
	 * @apiName        UnfriendBBFriendship
	 * @apiGroup       Connections
	 * @apiDescription Unfriend friendship
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} friend_id ID of the Friend member.
	 */
	public function delete_items( $request ) {
		$request->set_param( 'context', 'edit' );

		if ( empty( $request['friend_id'] ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid ID of the friend member.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Check if user is valid.
		$user = get_user_by( 'id', $request['friend_id'] );
		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'bp_rest_invalid_friend_user',
				__( 'There was a problem confirming if friend user is a valid one.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$user_id   = bp_loggedin_user_id();
		$friend_id = $request['friend_id'];

		$friendship_id = BP_Friends_Friendship::get_friendship_id( $user_id, $friend_id );
		$friendship    = $this->get_friendship_object( $friendship_id );
		$previous      = $this->prepare_item_for_response( $friendship, $request );

		if (
			'is_friend' === BP_Friends_Friendship::check_is_friend( $user_id, $friend_id )
			&& friends_remove_friend( $user_id, $friend_id )
		) {
			$status = true;
		} else {
			$status = new WP_Error(
				'bp_rest_friends_cannot_unfriend_friendship',
				__( 'Connection could not be cancelled.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'unfriend' => $status,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a friendship is deleted via the REST API.
		 *
		 * @param BP_Friends_Friendship $friendship Friendship object.
		 * @param WP_REST_Response      $response   The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_friends_delete_items', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to unfriend a friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function delete_items_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the friendship `delete_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_delete_items_permissions_check', $retval, $request );
	}

	/**
	 * Reject/withdraw friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/friends/:id Delete Friendship
	 * @apiName        DeleteBBFriendship
	 * @apiGroup       Connections
	 * @apiDescription Delete friendship
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id Identifier for the friendship.
	 */
	public function delete_item( $request ) {
		$request->set_param( 'context', 'edit' );

		// Get friendship object.
		$friendship = $this->get_friendship_object( $request['id'] );

		if ( ! $friendship || empty( $friendship->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid friendship ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$user_id  = bp_loggedin_user_id();
		$previous = $this->prepare_item_for_response( $friendship, $request );

		/**
		 * If this change is being initiated by the initiator,
		 * use the `reject` function.
		 *
		 * This is the user who requested the friendship, and is doing the withdrawing.
		 */
		if ( $user_id === $friendship->initiator_user_id ) {
			$deleted = friends_withdraw_friendship( $friendship->initiator_user_id, $friendship->friend_user_id );
		} else {
			/**
			 * Otherwise, this change is being initiated by the user, friend,
			 * who received the friendship reject.
			 */
			$deleted = friends_reject_friendship( $friendship->id );
		}

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_friends_cannot_delete_item',
				__( 'Could not delete friendship.', 'buddyboss' ),
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
		 * Fires after a friendship is deleted via the REST API.
		 *
		 * @param BP_Friends_Friendship $friendship Friendship object.
		 * @param WP_REST_Response      $response   The response data.
		 * @param WP_REST_Request       $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_friends_delete_item', $friendship, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a friendship.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the friendship `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares friendship data to return as an object.
	 *
	 * @param BP_Friends_Friendship $friendship Friendship object.
	 * @param WP_REST_Request       $request    Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $friendship, $request ) {
		$data = array(
			'id'           => $friendship->id,
			'initiator_id' => $friendship->initiator_user_id,
			'friend_id'    => $friendship->friend_user_id,
			'is_confirmed' => (bool) $friendship->is_confirmed,
			'date_created' => bp_rest_prepare_date_response( $friendship->date_created ),
		);

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filter a friendship value returned from the API.
		 *
		 * @param WP_REST_Response      $response   Response generated by the request.
		 * @param WP_REST_Request       $request    Request used to generate the response.
		 * @param BP_Friends_Friendship $friendship The friendship object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_prepare_value', $response, $request, $friendship );
	}

	/**
	 * Get friendship object.
	 *
	 * @param int $friendship_id Friendship ID.
	 *
	 * @return BP_Friends_Friendship
	 * @since 0.1.0
	 */
	public function get_friendship_object( $friendship_id ) {
		return new BP_Friends_Friendship( $friendship_id );
	}

	/**
	 * Edit some arguments for the endpoint's CREATABLE and EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';
		} elseif ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// Remothe the ID for POST requests.
			unset( $args['id'] );

			// Those fields are required.
			$args['initiator_id']['required'] = true;
			$args['friend_id']['required']    = true;

			// This one is optional.
			$args['force'] = array(
				'description'       => __( 'Whether to force friendship acceptance.', 'buddyboss' ),
				'default'           => false,
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);

		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
			unset( $args['id'] );
			unset( $args['initiator_id'] );
			$args['friend_id']['required'] = true;
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_friends_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the friends schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_friends',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the friendship.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'initiator_id' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'User ID of the friendship initiator.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'friend_id'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'User ID of the `friend` - the one invited to the friendship.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'is_confirmed' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the friendship been confirmed/accepted.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'date_created' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( "The date the friendship was created, in the site's timezone.", 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
			),
		);

		/**
		 * Filters the friends schema.
		 *
		 * @param array $schema The endpoint schema.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_friends_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for friends collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		unset( $params['search'] );

		$params['user_id'] = array(
			'description'       => __( 'ID of the user whose friends are being retrieved.', 'buddyboss' ),
			'default'           => bp_loggedin_user_id(),
			'type'              => 'integer',
			'required'          => true,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['is_confirmed'] = array(
			'description'       => __( 'Wether the friendship has been accepted.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['id'] = array(
			'description'       => __( 'ID of a specific friendship to retrieve.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['initiator_id'] = array(
			'description'       => __( 'ID of the friendship initiator.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['friend_id'] = array(
			'description'       => __( 'ID of a specific friendship to retrieve.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order_by'] = array(
			'description'       => __( 'Column name to order the results by.', 'buddyboss' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'date_created', 'initiator_user_id', 'friend_user_id', 'id' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order results ascending or descending.', 'buddyboss' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_friends_collection_params', $params );
	}
}
