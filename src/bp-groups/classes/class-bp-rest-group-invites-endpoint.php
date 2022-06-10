<?php
/**
 * BP REST: BP_REST_Group_Invites_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group Invites endpoints.
 *
 * Use /groups/{group_id}/invites
 * Use /groups/{group_id}/invites/{user_id}
 *
 * @since 0.1.0
 */
class BP_REST_Group_Invites_Endpoint extends WP_REST_Controller {

	/**
	 * Reuse some parts of the BP_REST_Groups_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace              = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base              = buddypress()->groups->id . '/invites';
		$this->groups_endpoint        = new BP_REST_Groups_Endpoint();
		$this->group_members_endpoint = new BP_REST_Group_Membership_Endpoint();
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
			'/' . $this->rest_base . '/(?P<invite_id>[\d]+)',
			array(
				'args'   => array(
					'invite_id' => array(
						'description' => __( 'A unique numeric ID for the group invitation.', 'buddyboss' ),
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/multiple',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_multiple_item' ),
					'permission_callback' => array( $this, 'create_multiple_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE, true ),

				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve group invitations.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/invites Invites
	 * @apiName        GetBBGroupsInvites
	 * @apiGroup       Groups
	 * @apiDescription Retrieve invites for group
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [group_id] ID of the group to limit results to.
	 * @apiParam {String} [user_id] Return only invitations extended to this user.
	 * @apiParam {Number} [inviter_id] Return only invitations extended by this user.
	 * @apiParam {String=draft,sent,all} [invite_sent=sent] Limit result set to invites that have been sent, not sent, or include all.
	 * @apiParam {String=id,include} [orderby=id] Order invites by which attribute.
	 * @apiParam {String=asc,desc} [sort_order=desc] Order sort attribute ascending or descending.
	 */
	public function get_items( $request ) {
		$args = array(
			'item_id'     => $request['group_id'],
			'user_id'     => $request['user_id'],
			'invite_sent' => $request['invite_sent'],
			'per_page'    => $request['per_page'],
			'page'        => $request['page'],
			'order_by'    => ( ! empty( $request['orderby'] ) ? $request['orderby'] : '' ),
			'sort_order'  => ( ! empty( $request['order'] ) ? $request['order'] : '' ),
		);

		/**
		 * Inviter_id is a special case, because 0 can be meaningful for requests,
		 * but if it is zero for invitations, we can safely ignore it and should.
		 * So, only apply non-zero inviter_ids.
		 */
		if ( $request['inviter_id'] ) {
			$args['inviter_id'] = $request['inviter_id'];
		}

		// If the query is not restricted by group, user or inviter, limit it to the current user, if not an admin.
		if ( ! $args['item_id'] && ! $args['user_id'] && ! $args['inviter_id'] && ! bp_current_user_can( 'bp_moderate' ) ) {
			$args['user_id'] = bp_loggedin_user_id();
		}

		if ( ! empty( $request['include'] ) ) {
			$args['id'] = $request['include'];
			if (
				! empty( $args['order_by'] )
				&& 'include' === $args['order_by']
			) {
				$args['order_by'] = 'in';
			}
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_group_invites_get_items_query_args', $args, $request );

		// Get invites.
		$invites_data = groups_get_invites( $args );

		$retval = array();
		foreach ( $invites_data as $invitation ) {
			if ( $invitation instanceof BP_Invitation ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $invitation, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $invites_data ), $args['per_page'] );

		/**
		 * Fires after a list of group invites are fetched via the REST API.
		 *
		 * @param array            $invites_data Invited users from the group.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_invites_get_items', $invites_data, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group invitations.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to see the group invitations.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$user_id     = bp_loggedin_user_id();
		$user_id_arg = $request['user_id'];
		$group       = $this->groups_endpoint->get_group_object( $request['group_id'] );
		$inviter     = bp_rest_get_user( $request['inviter_id'] );

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

				// If an inviter ID has been passed, check that it is valid.
			} elseif ( $request['inviter_id'] && ! $inviter instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_member_invalid_id',
					__( 'Invalid member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the group invites `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_invites_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Fetch a specific group invitation by ID.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/:invite_id Invite
	 * @apiName        GetBBGroupsInvite
	 * @apiGroup       Groups
	 * @apiDescription Retrieve single invitation.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} invite_id A unique numeric ID for the group invitation.
	 */
	public function get_item( $request ) {
		$invite = $this->fetch_single_invite( $request['invite_id'] );
		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $invite, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a membership request is fetched via the REST API.
		 *
		 * @param BP_Invitation    $invite   Invitation object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_invite_get_item', $invite, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to fetch group invitation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$user_id = bp_loggedin_user_id();
		$invite  = $this->fetch_single_invite( $request['invite_id'] );
		$retval  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to see the group invitations.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( $user_id ) {
			$retval = true;

			if ( ! $invite ) {
				$retval = new WP_Error(
					'bp_rest_group_invite_invalid_id',
					__( 'Invalid group invitation ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the group membership request `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_invites_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Invite a member to a group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/groups/invites Create Group Invite
	 * @apiName        CreateBBGroupsInvites
	 * @apiGroup       Groups
	 * @apiDescription Create group invitation.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} user_id The ID of the user who is invited to join the Group.
	 * @apiParam {Number} [inviter_id=1] The ID of the user who made the invite.
	 * @apiParam {Number} group_id The ID of the group to which the user has been invited.
	 * @apiParam {String} [message] The optional message to send to the invited user.
	 * @apiParam {Boolean} [send_invite=true] Whether the invite should be sent to the invitee.
	 */
	public function create_item( $request ) {
		$inviter_id_arg = $request['inviter_id'] ? $request['inviter_id'] : bp_loggedin_user_id();
		$group          = $this->groups_endpoint->get_group_object( $request['group_id'] );
		$user           = bp_rest_get_user( $request['user_id'] );
		$inviter        = bp_rest_get_user( $inviter_id_arg );

		$invite_id = groups_invite_user(
			array(
				'user_id'     => $user->ID,
				'group_id'    => $group->id,
				'inviter_id'  => $inviter->ID,
				'send_invite' => isset( $request['invite_sent'] ) ? (bool) $request['invite_sent'] : 1,
				'content'     => $request['message'],
			)
		);

		if ( ! $invite_id ) {
			return new WP_Error(
				'bp_rest_group_invite_cannot_create_item',
				__( 'Could not invite member to the group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$invite = new BP_Invitation( $invite_id );

		// Set context.
		$request->set_param( 'context', 'edit' );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $invite, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member is invited to a group via the REST API.
		 *
		 * @param BP_Invitation    $invite   The invitation object.
		 * @param WP_User          $user     The invited user.
		 * @param WP_User          $inviter  The inviter user.
		 * @param BP_Groups_Group  $group    The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_invites_create_item', $invite, $user, $inviter, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to invite a member to a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$inviter_id_arg = $request['inviter_id'] ? $request['inviter_id'] : bp_loggedin_user_id();
		$group          = $this->groups_endpoint->get_group_object( $request['group_id'] );
		$user           = bp_rest_get_user( $request['user_id'] );
		$inviter        = bp_rest_get_user( $inviter_id_arg );
		$retval         = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create an invitation.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( empty( $user->ID ) || empty( $inviter->ID ) || $user->ID === $inviter->ID ) {
				$retval = new WP_Error(
					'bp_rest_member_invalid_id',
					__( 'Invalid member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
				// Only a site admin or the user herself can extend invites.
			} elseif ( ! bp_current_user_can( 'bp_moderate' ) && bp_loggedin_user_id() !== $inviter_id_arg ) {
				$retval = new WP_Error(
					'bp_rest_group_invite_cannot_create_item',
					__( 'Sorry, you are not allowed to create the invitation as requested.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the group invites `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_invites_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Invite multiple member to a group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/groups/invites/multiple Create Group Invite
	 * @apiName        CreateBBGroupsMultipleInvites
	 * @apiGroup       Groups
	 * @apiDescription Create Multiple group invitation.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} user_id The ID of the users who is invited to join the Group.
	 * @apiParam {Number} [inviter_id] The ID of the user who made the invite.
	 * @apiParam {Number} group_id The ID of the group to which the user has been invited.
	 * @apiParam {String} [message] The optional message to send to the invited user.
	 * @apiParam {Boolean} [send_invite=true] Whether the invite should be sent to the invitee.
	 */
	public function create_multiple_item( $request ) {
		$inviter_id_arg = $request['inviter_id'] ? $request['inviter_id'] : bp_loggedin_user_id();
		$group          = $this->groups_endpoint->get_group_object( $request['group_id'] );
		$user_ids       = (array) $request['user_id'];
		$inviter        = bp_rest_get_user( $inviter_id_arg );
		$users          = array();
		$retval         = array();

		if ( ! empty( $user_ids ) ) {
			foreach ( $user_ids as $user_id ) {
				$users[] = bp_rest_get_user( $user_id );
			}
		}

		$invited = array();
		foreach ( $users as $user ) {
			$invited[ $user->ID ] = groups_invite_user(
				array(
					'user_id'     => $user->ID,
					'group_id'    => $group->id,
					'inviter_id'  => $inviter->ID,
					'send_invite' => isset( $request['invite_sent'] ) ? (bool) $request['invite_sent'] : 1,
					'content'     => $request['message'],
				)
			);
		}

		$invited = array_filter( $invited );

		if ( ! $invited ) {
			return new WP_Error(
				'bp_rest_group_invite_cannot_create_item',
				__( 'Could not invite member to the group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		foreach ( $invited as $invite_id ) {

			$invite = new BP_Invitation( $invite_id );

			// Set context.
			$request->set_param( 'context', 'edit' );

			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $invite, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member is invited to a group via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param array            $users    The invited user.
		 * @param WP_User          $inviter  The inviter user.
		 * @param BP_Groups_Group  $group    The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @param array            $invited  The invitation object.
		 */
		do_action( 'bp_rest_group_multiple_invites_create_item', $invited, $users, $inviter, $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to invite a multiple member to a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_multiple_item_permissions_check( $request ) {
		$inviter_id_arg = $request['inviter_id'] ? $request['inviter_id'] : bp_loggedin_user_id();
		$group          = $this->groups_endpoint->get_group_object( $request['group_id'] );
		$inviter        = bp_rest_get_user( $inviter_id_arg );
		$user_ids       = (array) $request['user_id'];
		$retval         = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create an invitation.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( empty( $user_ids ) || in_array( $inviter->ID, $user_ids, true ) ) {
				$retval = new WP_Error(
					'bp_rest_member_invalid_id',
					__( 'Invalid members ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// Only a site admin or the user herself can extend invites.
			} elseif ( ! bp_current_user_can( 'bp_moderate' ) && bp_loggedin_user_id() !== $inviter_id_arg ) {
				$retval = new WP_Error(
					'bp_rest_group_invite_cannot_create_item',
					__( 'Sorry, you are not allowed to create the invitation as requested.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the group invites `create_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool|WP_Error   $retval  Whether the request can continue.
		 */
		return apply_filters( 'bp_rest_group_invites_multiple_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Accept a group invitation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/groups/invites/:invite_id Update Group Invite
	 * @apiName        UpdateBBGroupsInvite
	 * @apiGroup       Groups
	 * @apiDescription Update group invitation.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} invite_id A unique numeric ID for the group invitation.
	 */
	public function update_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$invite = $this->fetch_single_invite( $request['invite_id'] );
		$accept = groups_accept_invite( $invite->user_id, $invite->item_id );
		if ( ! $accept ) {
			return new WP_Error(
				'bp_rest_group_invite_cannot_update_item',
				__( 'Could not accept group invitation.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$accepted_member     = new BP_Groups_Member( $invite->user_id, $invite->item_id );
		$request['group_id'] = $invite->item_id;
		$retval              = $this->prepare_response_for_collection(
			$this->group_members_endpoint->prepare_item_for_response( $accepted_member, $request )
		);

		$response = rest_ensure_response( $retval );
		$group    = $this->groups_endpoint->get_group_object( $invite->item_id );

		/**
		 * Fires after a group invite is accepted via the REST API.
		 *
		 * @param BP_Groups_Member $accepted_member Accepted group member.
		 * @param BP_Groups_Group  $group           The group object.
		 * @param WP_REST_Response $response        The response data.
		 * @param WP_REST_Request  $request         The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_invites_update_item', $accepted_member, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to accept a group invitation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to see the group invitations.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$user_id = bp_loggedin_user_id();
		$invite  = $this->fetch_single_invite( $request['invite_id'] );

		if ( $user_id ) {
			$retval = true;

			if ( ! $invite ) {
				$retval = new WP_Error(
					'bp_rest_group_invite_invalid_id',
					__( 'Invalid group invitation ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// Only the invitee or a site admin should be able to accept an invitation.
			} elseif (
				! bp_current_user_can( 'bp_moderate' ) &&
				$user_id !== $invite->user_id
			) {
				$retval = new WP_Error(
					'bp_rest_group_invite_cannot_update_item',
					__( 'Sorry, you are not allowed to accept the invitation as requested.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the group invites `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_invites_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Remove (reject/delete) a group invitation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/groups/invites/:invite_id Delete Group Invite
	 * @apiName        DeleteBBGroupsInvite
	 * @apiGroup       Groups
	 * @apiDescription Delete group invitation.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} invite_id A unique numeric ID for the group invitation.
	 */
	public function delete_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$user_id = bp_loggedin_user_id();
		$invite  = $this->fetch_single_invite( $request['invite_id'] );

		// Set the invite response before it is deleted.
		$previous = $this->prepare_item_for_response( $invite, $request );

		/**
		 * If this change is being initiated by the invited user,
		 * use the `reject` function.
		 */
		if ( $user_id === $invite->user_id ) {
			$deleted = groups_reject_invite( $invite->user_id, $invite->item_id, $invite->inviter_id );
			/**
			 * Otherwise, this change is being initiated by a group admin, site admin,
			 * or the inviter, and we should use the `uninvite` function.
			 */
		} else {
			$deleted = groups_uninvite_user( $invite->user_id, $invite->item_id, $invite->inviter_id );
		}

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_group_invite_cannot_delete_item',
				__( 'Could not delete group invitation.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
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

		$user  = bp_rest_get_user( $invite->user_id );
		$group = $this->groups_endpoint->get_group_object( $invite->item_id );

		/**
		 * Fires after a group invite is deleted via the REST API.
		 *
		 * @param WP_User          $user     The invited user.
		 * @param BP_Groups_Group  $group    The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_invites_delete_item', $user, $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a group invitation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to see the group invitations.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$user_id = bp_loggedin_user_id();
		$invite  = $this->fetch_single_invite( $request['invite_id'] );

		if ( $user_id ) {
			$retval = true;

			if ( ! $invite ) {
				$retval = new WP_Error(
					'bp_rest_group_invite_invalid_id',
					__( 'Invalid group invitation ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// The inviter, the invitee, group admins, and site admins can all delete invites.
			} elseif (
				! bp_current_user_can( 'bp_moderate' ) &&
				! in_array( $user_id, array( $invite->user_id, $invite->inviter_id ), true ) &&
				! groups_is_user_admin( $user_id, $invite->item_id )
			) {
				$retval = new WP_Error(
					'bp_rest_group_invite_cannot_delete_item',
					__( 'Sorry, you are not allowed to delete the invitation as requested.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the group invites `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Whether the request can continue.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_invites_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group invitation data to return as an object.
	 *
	 * @param BP_Invitation   $invite  Invite object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $invite, $request ) {
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
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Invitation    $invite   The invite object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_invites_prepare_value', $response, $request, $invite );
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
			'inviter'    => array(
				'href'       => rest_url( bp_rest_get_user_url( $invite->inviter_id ) ),
				'embeddable' => true,
			),
			'group'      => array(
				'href'       => rest_url( $this->namespace . '/' . buddypress()->groups->id . '/' . $invite->item_id ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array    $links  The prepared links of the REST response.
		 * @param stdClass $invite Invite object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_invites_prepare_links', $links, $invite );
	}

	/**
	 * Helper function to fetch a single group invite.
	 *
	 * @param int $invite_id The ID of the invitation you wish to fetch.
	 *
	 * @return BP_Invitation|bool $invite Invitation if found, false otherwise.
	 * @since 0.1.0
	 */
	public function fetch_single_invite( $invite_id = 0 ) {
		$invites = groups_get_invites( array( 'id' => $invite_id ) );
		if ( $invites ) {
			return current( $invites );
		} else {
			return false;
		}
	}

	/**
	 * Edit the type of the some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $method   Optional. HTTP method of the request.
	 * @param boolean $multiple Is multiple invite or not.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE, $multiple = false ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$key                            = 'create_item';
			$args['message']['type']        = 'string';
			$args['message']['description'] = __( 'The optional message to send to the invited user.', 'buddyboss' );
			$args['send_invite']            = $args['invite_sent'];
			$args['inviter_id']['default']  = bp_loggedin_user_id();
			$args['group_id']['required']   = true;
			$args['user_id']['required']    = true;

			// Remove arguments not needed for the CREATABLE transport method.
			unset( $args['message']['properties'], $args['invite_sent'], $args['date_modified'], $args['type'] );

			$args['send_invite']['description'] = __( 'Whether the invite should be sent to the invitee.', 'buddyboss' );
			$args['send_invite']['default']     = true;

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';
			}
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		if ( WP_REST_Server::CREATABLE === $method && true === $multiple ) {
			$args['user_id'] = array(
				'description'       => __( 'Return only invitations extended to those users.', 'buddyboss' ),
				'type'              => 'array',
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
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
		return apply_filters( "bp_rest_group_invites_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the group invite schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_group_invites',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the BP Invitation object.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'user_id'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The ID of the user who is invited to join the Group.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'invite_sent'   => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Whether the invite has been sent to the invitee.', 'buddyboss' ),
					'type'        => 'boolean',
				),
				'inviter_id'    => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The ID of the user who made the invite.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'group_id'      => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The ID of the group to which the user has been invited.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'date_modified' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( "The date the object was created or last updated, in the site's timezone.", 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'type'          => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Invitation or request.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array( 'invite', 'request' ),
					'default'     => 'invite',
				),
				'message'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The raw and rendered versions for the content of the message.', 'buddyboss' ),
					'type'        => 'object',
					'arg_options' => array(
						'sanitize_callback' => null,
						'validate_callback' => null,
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the object, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the object, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),

			),
		);

		/**
		 * Filters the group invites schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_invites_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of group invites.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Remove the search param.
		unset( $params['search'] );

		$params['group_id'] = array(
			'description'       => __( 'ID of the group to limit results to.', 'buddyboss' ),
			'required'          => false,
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Return only invitations extended to this user.', 'buddyboss' ),
			'required'          => false,
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['inviter_id'] = array(
			'description'       => __( 'Return only invitations extended by this user.', 'buddyboss' ),
			'required'          => false,
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['invite_sent'] = array(
			'description'       => __( 'Limit result set to invites that have been sent, not sent, or include all.', 'buddyboss' ),
			'default'           => 'sent',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
			'enum'              => array( 'draft', 'sent', 'all' ),
		);

		$params['order_by'] = array(
			'description'       => __( 'Name of the field to order according to.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'enum'              => array(
				'id',
				'include',
			),
			'sanitize_callback' => 'sanitize_key',
		);

		$params['sort_order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'default'           => 'asc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_group_invites_collection_params', $params );
	}
}
