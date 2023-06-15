<?php
/**
 * BP REST: BP_REST_Group_Membership_Request_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group Membership Request Endpoint.
 *
 * Use /groups/{group_id}/membership-request
 * Use /groups/membership-request/{request_id}
 * Use /groups/{group_id}/membership-request/{user_id}
 *
 * @since 0.1.0
 */
class BP_REST_Group_Membership_Request_Endpoint extends WP_REST_Controller {

	/**
	 * Reuse some parts of the BP_REST_Groups_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Reuse some parts of the BP_REST_Group_Invites_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Group_Invites_Endpoint
	 */
	protected $invites_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace              = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base              = buddypress()->groups->id . '/membership-requests';
		$this->groups_endpoint        = new BP_REST_Groups_Endpoint();
		$this->group_members_endpoint = new BP_REST_Group_Membership_Endpoint();
		$this->invites_endpoint       = new BP_REST_Group_Invites_Endpoint();
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
			'/' . $this->rest_base . '/(?P<request_id>[\d]+)',
			array(
				'args'   => array(
					'request_id' => array(
						'description' => __( 'A unique numeric ID for the group membership request.', 'buddyboss' ),
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
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Fetch pending group membership requests.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/membership-requests Group Membership Requests
	 * @apiName        GetBBGroupsMembershipsRequest
	 * @apiGroup       Groups
	 * @apiDescription Retrieve group membership requests
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {Number} [group_id=0] The ID of the group the user requested a membership for.
	 * @apiParam {Number} [user_id=0] Return only Membership requests made by a specific user.
	 */
	public function get_items( $request ) {
		$args = array(
			'item_id'  => $request['group_id'],
			'user_id'  => $request['user_id'],
			'per_page' => $request['per_page'],
			'page'     => $request['page'],
		);

		// If the query is not restricted by group or user, limit it to the current user, if not an admin.
		if ( ! $args['item_id'] && ! $args['user_id'] && ! bp_current_user_can( 'bp_moderate' ) ) {
			$args['user_id'] = bp_loggedin_user_id();
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_group_membership_requests_get_items_query_args', $args, $request );

		$group_requests = groups_get_requests( $args );

		$retval = array();
		foreach ( $group_requests as $group_request ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group_request, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $group_requests ), $args['per_page'] );

		/**
		 * Fires after a list of group membership request is fetched via the REST API.
		 *
		 * @param array of BP_Invitations $group_requests List of membership requests.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_membership_requests_get_items', $group_requests, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to fetch group membership requests.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to view membership requests.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$user_id     = bp_loggedin_user_id();
		$user_id_arg = $request['user_id'];
		$group       = $this->groups_endpoint->get_group_object( $request['group_id'] );

		// If the query is not restricted by group or user, limit it to the current user, if not an admin.
		if ( ! $request['group_id'] && ! $request['user_id'] && ! bp_current_user_can( 'bp_moderate' ) ) {
			$user_id_arg = $user_id;
		}

		$user = bp_rest_get_user( $user_id_arg );

		if ( $user_id ) {
			$retval = true;

			// If a group ID has been passed, check that it is valid.
			if ( $request['group_id'] && ! $group instanceof BP_Groups_Group ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// If a user ID has been passed, check that it is valid.
			} elseif ( $user_id_arg && ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_member_invalid_id',
					__( 'Invalid member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// Site administrators can do anything. Otherwise, the user must manage the subject group or be the requester.
			} elseif (
				! bp_current_user_can( 'bp_moderate' ) &&
				! ( $request['group_id'] && groups_is_user_admin( $user_id, $request['group_id'] ) ) &&
				$user_id_arg !== $user_id
			) {
				$retval = new WP_Error(
					'bp_rest_group_membership_requests_cannot_get_items',
					__( 'Sorry, you are not allowed to view membership requests.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}
		}

		/**
		 * Filter the `get_items` permissions check.
		 *
		 * @param bool|WP_Error $retval Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_membership_requests_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Fetch a sepcific pending group membership request by ID.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {GET} /wp-json/buddyboss/v1/groups/membership-requests/:request_id  Get Membership Request
	 * @apiName GetBBGroupsMembershipsRequest
	 * @apiGroup Groups
	 * @apiDescription Retrieve group membership request by ID.
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} request_id A unique numeric ID for the group membership request.
	 */
	public function get_item( $request ) {
		$group_request = $this->fetch_single_membership_request( $request['request_id'] );
		$retval        = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $group_request, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a membership request is fetched via the REST API.
		 *
		 * @param BP_Invitation $group_request Membership request object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_membership_requests_get_item', $group_request, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to fetch group membership request.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to get a membership.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$user_id       = bp_loggedin_user_id();
		$group_request = $this->fetch_single_membership_request( $request['request_id'] );

		if ( $user_id ) {
			$retval = true;

			if ( ! $group_request ) {
				$retval = new WP_Error(
					'bp_rest_group_membership_requests_invalid_id',
					__( 'Invalid group membership request ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				! bp_current_user_can( 'bp_moderate' ) &&
				$user_id !== $group_request->user_id &&
				! groups_is_user_admin( $user_id, $group_request->item_id )
			) {
				$retval = new WP_Error(
					'bp_rest_group_membership_requests_cannot_get_item',
					__( 'Sorry, you are not allowed to view a membership request.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}
		}

		/**
		 * Filter the group membership request `get_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_membership_requests_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Request membership to a group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/groups/membership-requests Create Group Membership Request
	 * @apiName        CreateBBGroupsMembershipsRequest
	 * @apiGroup       Groups
	 * @apiDescription Create group membership request
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [user_id] The ID of the user who requested a Group membership.
	 * @apiParam {Number} group_id The ID of the group the user requested a membership for.
	 * @apiParam {String} [message] The optional message to send to the invited user.
	 */
	public function create_item( $request ) {
		$user_id_arg = $request['user_id'] ? $request['user_id'] : bp_loggedin_user_id();
		$user        = bp_rest_get_user( $user_id_arg );
		$group       = $this->groups_endpoint->get_group_object( $request['group_id'] );

		// Avoid duplicate requests.
		if ( groups_check_for_membership_request( $user->ID, $group->id ) ) {
			return new WP_Error(
				'bp_rest_group_membership_requests_duplicate_request',
				__( 'There is already a request to this member.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$request_id = groups_send_membership_request(
			array(
				'group_id' => $group->id,
				'user_id'  => $user->ID,
				'content'  => $request['message'],
			)
		);

		if ( ! $request_id ) {
			return new WP_Error(
				'bp_rest_group_membership_requests_cannot_create_item',
				__( 'Could not send membership request to this group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Setting context.
		$request->set_param( 'context', 'edit' );

		if ( true === $request_id ) {
			$request->set_param( 'joined', true );
		} else {
			$request->set_param( 'joined', false );
		}
		$request->set_param( 'user_id', $user->ID );

		$invite = new BP_Invitation( $request_id );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $invite, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group membership request is made via the REST API.
		 *
		 * @param WP_User $user The user.
		 * @param BP_Invitation $invite The invitation object.
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_membership_requests_create_item', $user, $invite, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to make a group membership request.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create a membership request.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$user_id     = bp_loggedin_user_id();
		$user_id_arg = $request['user_id'] ? $request['user_id'] : bp_loggedin_user_id();
		$user        = bp_rest_get_user( $user_id_arg );
		$group       = $this->groups_endpoint->get_group_object( $request['group_id'] );

		// User must be logged in.
		if ( is_user_logged_in() ) {
			$retval = true;

			// Check for valid user.
			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_group_member_invalid_id',
					__( 'Invalid member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// Check for valid group.
			} elseif ( ! $group instanceof BP_Groups_Group ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// Normal users can only extend invitations on their own behalf.
			} elseif (
				! bp_current_user_can( 'bp_moderate' ) &&
				$user_id !== $user_id_arg
			) {
				$retval = new WP_Error(
					'bp_rest_group_membership_requests_cannot_create_item',
					__( 'User may not extend requests on behalf of another user.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}
		}

		/**
		 * Filter the group membership request `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_membership_requests_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Accept or reject a pending group membership request.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/groups/membership-requests/:request_id
	 * @apiName        UpdateBBGroupsMembershipsRequest
	 * @apiGroup       Groups
	 * @apiDescription Update group membership request
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} request_id A unique numeric ID for the group membership request.
	 */
	public function update_item( $request ) {
		$group_request = $this->fetch_single_membership_request( $request['request_id'] );
		$success       = groups_accept_membership_request( false, $group_request->user_id, $group_request->item_id );
		if ( ! $success ) {
			return new WP_Error(
				'bp_rest_group_member_request_cannot_update_item',
				__( 'There was an error accepting the membership request.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Setting context.
		$request->set_param( 'context', 'edit' );

		$g_member = new BP_Groups_Member( $group_request->user_id, $group_request->item_id );

		$retval = $this->prepare_response_for_collection(
			$this->group_members_endpoint->prepare_item_for_response( $g_member, $request )
		);

		$response = rest_ensure_response( $retval );
		$group    = $this->groups_endpoint->get_group_object( $group_request->item_id );

		/**
		 * Fires after a group membership request is accepted/rejected via the REST API.
		 *
		 * @param BP_Groups_Member $g_member The groups member object.
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_membership_requests_update_item', $g_member, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to accept a group membership request.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to make an update.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$user_id       = bp_loggedin_user_id();
		$group_request = $this->fetch_single_membership_request( $request['request_id'] );

		if ( $user_id ) {
			$retval = true;

			if ( ! $group_request ) {
				$retval = new WP_Error(
					'bp_rest_group_membership_requests_invalid_id',
					__( 'Invalid group membership request ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				! bp_current_user_can( 'bp_moderate' ) &&
				! groups_is_user_admin( $user_id, $group_request->item_id )
			) {
				$retval = new WP_Error(
					'bp_rest_group_member_request_cannot_update_item',
					__( 'User is not allowed to approve membership requests to this group.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}
		}

		/**
		 * Filter the group membership request `update_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_membership_requests_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Reject a pending group membership request.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/groups/membership-requests/:request_id Delete Group Membership Request
	 * @apiName        DeleteBBGroupsMembershipsRequest
	 * @apiGroup       Groups
	 * @apiDescription Delete group membership request
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} request_id A unique numeric ID for the group membership request.
	 */
	public function delete_item( $request ) {

		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get invite.
		$group_request = $this->fetch_single_membership_request( $request['request_id'] );

		// Set the invite response before it is deleted.
		$previous = $this->prepare_item_for_response( $group_request, $request );

		/**
		 * If this change is being initiated by the requesting user,
		 * use the `delete` function.
		 */
		if ( bp_loggedin_user_id() === $group_request->user_id ) {
			$success = groups_delete_membership_request( false, $group_request->user_id, $group_request->item_id );
			/**
			 * Otherwise, this change is being initiated by a group admin or site admin,
			 * and we should use the `reject` function.
			 */
		} else {
			$success = groups_reject_membership_request( false, $group_request->user_id, $group_request->item_id );
		}

		if ( ! $success ) {
			return new WP_Error(
				'bp_rest_group_membership_requests_cannot_delete_item',
				__( 'There was an error rejecting the membership request.', 'buddyboss' ),
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

		$user  = bp_rest_get_user( $group_request->user_id );
		$group = $this->groups_endpoint->get_group_object( $group_request->item_id );

		$response->add_links( $this->prepare_links( $group_request ) );

		/**
		 * Fires after a group membership request is rejected via the REST API.
		 *
		 * @param WP_User $user The user.
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_membership_requests_delete_item', $user, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to reject a group membership request.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete a request.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$user_id       = bp_loggedin_user_id();
		$group_request = $this->fetch_single_membership_request( $request['request_id'] );

		if ( $user_id ) {
			$retval = true;

			if ( ! $group_request ) {
				$retval = new WP_Error(
					'bp_rest_group_membership_requests_invalid_id',
					__( 'Invalid group membership request ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				! bp_current_user_can( 'bp_moderate' ) &&
				$user_id !== $group_request->user_id &&
				! groups_is_user_admin( $user_id, $group_request->item_id )
			) {
				$retval = new WP_Error(
					'bp_rest_group_membership_requests_cannot_delete_item',
					__( 'User is not allowed to delete this membership request.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			}
		}

		/**
		 * Filter the group membership request `delete_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Whether the request may proceed.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_membership_requests_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group invitation data to return as an object.
	 *
	 * @param BP_Invitation   $invite Invite object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $invite, $request ) {

		// Check the member request is auto join or not?
		$joined = ! empty( $request['joined'] ) ? $request['joined'] : false;

		// If joined is true then set user and group IDs.
		if ( true === $joined && empty( $invite->id ) ) {
			$invite->user_id = ! empty( $request['user_id'] ) ? $request['user_id'] : bp_loggedin_user_id();
			$invite->item_id = ! empty( $request['group_id'] ) ? $request['group_id'] : false;
		}

		$data = array(
			'id'            => $invite->id,
			'user_id'       => $invite->user_id,
			'invite_sent'   => $invite->invite_sent,
			'inviter_id'    => $invite->inviter_id,
			'group_id'      => $invite->item_id,
			'date_modified' => bp_rest_prepare_date_response( $invite->date_modified ),
			'type'          => $invite->type,
			'message'       => array(
				'raw'      => $invite->content,
				'rendered' => apply_filters( 'the_content', $invite->content ),
			),
			'joined'        => $joined,
		);

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $invite ) );

		/**
		 * Filter a group invite value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request Request used to generate the response.
		 * @param BP_Invitation $invite The invite object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_membership_requests_prepare_value', $response, $request, $invite );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Invitation $invite Invite object.
	 *
	 * @return array Links for the given plugin.
	 * @since 0.1.0
	 */
	protected function prepare_links( $invite ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$url  = $base . $invite->id;

		$group_id = ( ! empty( $invite->item_id ) ? $invite->item_id : 0 );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $invite->user_id ) ),
				'embeddable' => true,
			),
		);

		if ( ! empty( $group_id ) ) {
			$links['group'] = array(
				'embeddable' => true,
				'href'       => rest_url( $this->namespace . '/' . buddypress()->groups->id . '/' . $group_id ),
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array $links The prepared links of the REST response.
		 * @param BP_Invitation $invite Invite object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_membership_requests_prepare_links', $links, $invite );
	}

	/**
	 * Helper function to fetch a single group invite.
	 *
	 * @param int $request_id The ID of the request you wish to fetch.
	 *
	 * @return BP_Invitation|bool $group_request Membership request if found, false otherwise.
	 * @since 0.1.0
	 */
	public function fetch_single_membership_request( $request_id = 0 ) {
		$group_requests = groups_get_requests( array( 'id' => $request_id ) );
		if ( $group_requests ) {
			return current( $group_requests );
		} else {
			return false;
		}
	}

	/**
	 * Endpoint args.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			$args['message']['type']        = 'string';
			$args['message']['description'] = __( 'The optional message to send to the invited user.', 'buddyboss' );
			$args['group_id']['required']   = true;
			$args['user_id']['default']     = bp_loggedin_user_id();

			// Remove arguments not needed for the CREATABLE transport method.
			unset( $args['message']['properties'], $args['date_modified'], $args['type'] );

		} elseif ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array $args Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_group_membership_requests_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the group membership request schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {

		// Get schema from the membership endpoint.
		$schema = $this->invites_endpoint->get_item_schema();

		// Set title to this endpoint.
		$schema['title'] = 'bp_group_membership_request';

		// Adapt some item schema property descriptions to this endpoint.
		$schema['properties']['user_id']['description']  = __( 'The ID of the user who requested a Group membership.', 'buddyboss' );
		$schema['properties']['group_id']['description'] = __( 'The ID of the group the user requested a membership for.', 'buddyboss' );
		$schema['properties']['type']['default']         = 'request';

		$schema['properties']['joined'] = array(
			'context'     => array( 'view', 'edit' ),
			'description' => __( 'The user auto join in private group or not.', 'buddyboss' ),
			'type'        => 'boolean',
			'default'     => false,
		);

		// Remove unused properties.
		unset( $schema['properties']['invite_sent'], $schema['properties']['inviter_id'] );

		/**
		 * Filters the group membership request schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_membership_requests_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of group invites.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = $this->invites_endpoint->get_collection_params();
		$params['context']['default'] = 'view';

		// Adapt some item schema property descriptions to this endpoint.
		$params['user_id']['description']  = __( 'Return only Membership requests made by a specific user.', 'buddyboss' );
		$params['group_id']['description'] = __( 'The ID of the group the user requested a membership for.', 'buddyboss' );

		// Remove unused properties.
		unset( $params['invite_sent'], $params['inviter_id'] );

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_group_membership_requests_collection_params', $params );
	}
}
