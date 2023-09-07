<?php
/**
 * BP REST: BP_REST_Members_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Members endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Members_Endpoint extends WP_REST_Users_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'members';
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
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the user.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
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
					'args'                => array(
						'id' => array(
							'required'    => true,
							'description' => __( 'A unique numeric ID for the Member.', 'buddyboss' ),
							'type'        => 'integer',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/me',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_current_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_current_item' ),
					'permission_callback' => array( $this, 'update_current_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_current_item' ),
					'permission_callback' => array( $this, 'delete_current_item_permissions_check' ),
					'args'                => array(
						'force'    => array(
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Required to be true, as users do not support trashing.', 'buddyboss' ),
						),
						'reassign' => array(
							'type'              => 'integer',
							'description'       => __( 'Reassign the deleted user\'s posts and links to this user ID.', 'buddyboss' ),
							'required'          => true,
							'sanitize_callback' => array( $this, 'check_reassign' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/members Get Members
	 * @apiName        GetBBMembers
	 * @apiGroup       Members
	 * @apiDescription Retrieve Members
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=active,newest,alphabetical,random,online,popular,include} [type=newest] Shorthand for certain orderby/order combinations.
	 * @apiParam {Number} [user_id] Limit results to friends of a user.
	 * @apiParam {Arrays} [user_ids] Pass IDs of users to limit result set.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {String} [member_type] Limit results set to certain type(s).
	 * @apiParam {String} [xprofile] Limit results set to a certain xProfile field.
	 * @apiParam {Array} [bp_ps_search] Profile Search form field data(s).
	 * @apiParam {String=all,personal,following,followers} [scope=all] Limit result set to items with a specific scope.
	 */
	public function get_items( $request ) {
		$args = array(
			'type'           => $request['type'],
			'user_id'        => $request['user_id'],
			'user_ids'       => $request['user_ids'],
			'xprofile_query' => $request['xprofile'],
			'include'        => $request['include'],
			'exclude'        => $request['exclude'],
			'member_type'    => $request['member_type'],
			'search_terms'   => $request['search'],
			'per_page'       => $request['per_page'],
			'page'           => $request['page'],
		);

		if ( empty( $request['user_ids'] ) ) {
			$args['user_ids'] = false;
		}

		if ( empty( $request['exclude'] ) ) {
			$args['exclude'] = false;
		}

		if ( empty( $request['include'] ) ) {
			$args['include'] = false;
		}

		if ( empty( $request['xprofile'] ) ) {
			$args['xprofile_query'] = false;
		}

		if ( empty( $request['member_type'] ) ) {
			$args['member_type'] = '';
		}

		if ( ! empty( $request['scope'] ) && 'all' !== $request['scope'] ) {
			if ( 'following' === $request['scope'] ) {
				$user_id       = ( ! empty( $request['user_id'] ) ? (int) $request['user_id'] : get_current_user_id() );
				$following_ids = $this->rest_bp_get_following_ids( array( 'user_id' => $user_id ) );
				if ( ! empty( $following_ids ) ) {
					$args['include'] = $following_ids;
					unset( $args['user_id'] );
				} else {
					$response = rest_ensure_response( array() );

					return bp_rest_response_add_total_headers( $response, 0, $args['per_page'] );
				}
			}

			if ( 'followers' === $request['scope'] ) {
				$user_id       = ( ! empty( $request['user_id'] ) ? (int) $request['user_id'] : get_current_user_id() );
				$followers_ids = $this->rest_bp_get_follower_ids( array( 'user_id' => $user_id ) );
				if ( ! empty( $followers_ids ) ) {
					$args['include'] = $followers_ids;
					unset( $args['user_id'] );
				} else {
					$response = rest_ensure_response( array() );

					return bp_rest_response_add_total_headers( $response, 0, $args['per_page'] );
				}
			}

			if ( 'personal' === $request['scope'] ) {
				$args['user_id'] = ( ! empty( $request['user_id'] ) ? (int) $request['user_id'] : get_current_user_id() );
			}
		}

		if ( ! empty( $request['bp_ps_search'] ) ) {
			$results = $this->rest_bp_ps_search( $request['bp_ps_search'] );
			if ( $results['validated'] ) {
				$users = $results['users'];

				if ( isset( $args['include'] ) && ! empty( $args['include'] ) ) {
					$included = explode( ',', $args['include'] );
					$users    = array_intersect( $users, $included );
					if ( count( $users ) === 0 ) {
						$users = array( 0 );
					}
				}

				$users           = apply_filters( 'bp_ps_search_results', $users );
				$args['include'] = implode( ',', $users );
			}
		} else if ( ! empty( $args['include'] ) ) {
			$args['type'] = 'in';
		}

		if (
			(
				empty( $request['scope'] )
				|| ( isset( $request['scope'] ) && 'all' === $request['scope'] )
			)
			&& empty( $request['bp_ps_search'] )
			&& function_exists( 'bp_get_users_of_removed_member_types' )
			&& ! empty( bp_get_users_of_removed_member_types() )
		) {
			$args['exclude'] = bp_get_users_of_removed_member_types();
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_members_get_items_query_args', $args, $request );

		// Actually, query it.
		$member_query = new BP_User_Query( $args );
		$members      = array_values( $member_query->results );

		$retval = array();
		foreach ( $members as $member ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $member, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $member_query->total_users, $args['per_page'] );

		/**
		 * Fires after a list of members is fetched via the REST API.
		 *
		 * @param array $members Fetched members.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_members_get_items', $members, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = true;

		if ( function_exists( 'bp_rest_enable_private_network' ) && true === bp_rest_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the members `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Checks if a given request has access to read a user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = true;

		if ( function_exists( 'bp_rest_enable_private_network' ) && true === bp_rest_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$user = bp_rest_get_user( $request['id'] );

		if ( true === $retval && ! $user instanceof WP_User ) {
			$retval = new WP_Error(
				'bp_rest_member_invalid_id',
				__( 'Invalid member ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && get_current_user_id() === $user->ID ) {
			$retval = true;
		} elseif ( true === $retval && 'edit' === $request['context'] && ! current_user_can( 'list_users' ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to view members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the members `get_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Checks if a given request has access create members.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to view members.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( ( is_user_logged_in() && current_user_can( 'bp_moderate' ) ) ) {
			$retval = true;
		}

		/**
		 * Filter or override the members `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Check if a given request has access to update a member.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$error  = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		$retval = $error;

		$user             = bp_rest_get_user( $request['id'] );
		$member_type_edit = isset( $request['member_type'] );

		if ( ! $user instanceof WP_User ) {
			$retval = new WP_Error(
				'bp_rest_member_invalid_id',
				__( 'Invalid member ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		} else {
			$action = 'delete';

			if ( 'DELETE' !== $request->get_method() ) {
				$action = 'update';
			}

			if ( get_current_user_id() === $user->ID ) {
				if ( $member_type_edit && ! bp_current_user_can( 'bp_moderate' ) ) {
					$retval = $error;
				} else {
					$retval = parent::update_item_permissions_check( $request );
				}
			} elseif ( ! $this->can_manage_member( $user, $action ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to view members.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} else {
				$retval = true;
			}
		}

		/**
		 * Filter the members `update_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Deletes a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/members/:id Delete Member
	 * @apiName        DeleteBBMembers
	 * @apiGroup       Members
	 * @apiDescription Delete a member.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Member.
	 */
	public function delete_item( $request ) {

		$user_id = (int) $request['id'];
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		if ( empty( $user_id ) ) {
			return new WP_Error(
				'bp_rest_member_invalid_id',
				__( 'Invalid member ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$user = bp_rest_get_user( $user_id );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error(
				'bp_rest_member_invalid_id',
				__( 'Invalid member ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = $this->prepare_item_for_response( $user, $request );
		$status   = false;
		if ( bp_core_delete_account( $user_id ) ) {
			$status = true;
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $status,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires immediately after a user is deleted via the REST API.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_User          $user     The user data.
		 * @param WP_REST_Response $response The response returned from the API.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_delete_user', $user, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a member.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval  = true;
			$user_id = (int) $request['id'];
			if ( empty( $user_id ) ) {
				$user_id = bp_loggedin_user_id();
			}

			if ( bp_loggedin_user_id() !== absint( $user_id ) && ! bp_current_user_can( 'delete_users' ) ) {
				$retval = new WP_Error(
					'bp_rest_user_cannot_delete',
					__( 'Sorry, you are not allowed to delete this user.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( function_exists( 'bp_disable_account_deletion' ) && bp_disable_account_deletion() ) {
				$retval = new WP_Error(
					'bp_rest_user_cannot_delete',
					__( 'Sorry, you are not allowed to delete this user.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the members `delete_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Deleting the current user is not implemented into this endpoint.
	 *
	 * This action is specific to the User Settings endpoint.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error                WP_Error object to inform it's not implemented.
	 * @since 0.1.0
	 */
	public function delete_current_item_permissions_check( $request ) {
		return new WP_Error(
			'bp_rest_invalid_method',
			/* translators: %s: transport method name */
			sprintf( __( '\'%s\' Transport Method not implemented.', 'buddyboss' ), $request->get_method() ),
			array(
				'status' => 405,
			)
		);
	}

	/**
	 * Deleting the current user is not implemented into this endpoint.
	 *
	 * This action is specific to the User Settings endpoint.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error                WP_Error to inform it's not implemented.
	 * @since 0.1.0
	 */
	public function delete_current_item( $request ) {
		return new WP_Error(
			'bp_rest_invalid_method',
			/* translators: %s: transport method name */
			sprintf( __( '\'%s\' Transport method not implemented.', 'buddyboss' ), $request->get_method() ),
			array(
				'status' => 405,
			)
		);
	}

	/**
	 * Prepares a single user output for response.
	 *
	 * @param WP_User         $user User object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $user, $request ) {
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->user_data( $user, $request );
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $user ) );

		// Update current user's last activity.
		if ( strpos( $request->get_route(), 'members/me' ) !== false && get_current_user_id() === $user->ID ) {
			bp_update_user_last_activity();
		}

		/**
		 * Filters user data returned from the API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_REST_Request  $request  The request object.
		 * @param WP_User          $user     WP_User object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_prepare_value', $response, $request, $user );
	}

	/**
	 * Method to facilitate fetching of user data.
	 *
	 * This was abstracted to be used in other BuddyPress endpoints.
	 *
	 * @param WP_User $user    User object.
	 * @param string  $context The context of the request. Defaults to 'view'.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function user_data( $user, $request ) {
		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$user_data = get_userdata( $user->ID );
		$followers = $this->rest_bp_get_follower_ids( array( 'user_id' => $user->ID ) );
		$following = $this->rest_bp_get_following_ids( array( 'user_id' => $user->ID ) );
		$data      = array(
			'id'                 => $user->ID,
			'name'               => $user->display_name,
			'user_login'         => $user->user_login,
			'link'               => bp_core_get_user_domain( $user->ID, $user->user_nicename, $user->user_login ),
			'member_types'       => bp_get_member_type( $user->ID, false ),
			'roles'              => array(),
			'capabilities'       => array(),
			'extra_capabilities' => array(),
			'registered_date'    => bp_rest_prepare_date_response( $user_data->user_registered ),
			'profile_name'       => bp_core_get_user_displayname( $user->ID ),
			'last_activity'      => $this->bp_rest_get_member_last_active( $user->ID, array( 'relative' => false ) ),
			'xprofile'           => array(),
			'followers'          => ! empty( $followers ) ? count( $followers ) : 0,
			'following'          => ! empty( $following ) ? count( $following ) : 0,
			'is_wp_admin'        => false,
		);

		// Fetch user roles.
		$user_roles = ! empty( $user->ID ) ? $user_data->roles : '';
		if ( ! empty( $user_roles ) ) {
			// If user is admin then set true, otherwise it should be false.
			$data['is_wp_admin'] = in_array( 'administrator', $user_roles, true ) ? true : false;
		}

		// Load xprofile data when required.
		if ( 'embed' !== $context ) {
			$data['xprofile'] = $this->xprofile_data( $user->ID );
		}

		$data['friendship_status'] = (
			(
				bp_is_active( 'friends' )
				&& function_exists( 'friends_check_friendship_status' )
			)
			? friends_check_friendship_status( get_current_user_id(), $user->ID )
			: ''
		);

		$data['friendship_id'] = (
			(
				bp_is_active( 'friends' )
				&& function_exists( 'friends_get_friendship_id' )
			)
			? friends_get_friendship_id( get_current_user_id(), $user->ID )
			: ''
		);

		$data['create_friendship'] = ( bp_is_active( 'friends' ) && is_user_logged_in() && apply_filters( 'bp_rest_user_can_create_friendship', true, $user->ID ) );

		$data['is_following'] = (bool) (
		function_exists( 'bp_is_following' )
			? bp_is_following(
				array(
					'leader_id'   => $user->ID,
					'follower_id' => get_current_user_id(),
				)
			)
			: '0'
		);

		$data['can_follow'] = bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active();;

		if ( 'edit' === $context ) {
			$data['registered_date']    = bp_rest_prepare_date_response( $user_data->user_registered );
			$data['roles']              = (array) array_values( $user_data->roles );
			$data['capabilities']       = (array) array_keys( $user_data->allcaps );
			$data['extra_capabilities'] = (array) array_keys( $user_data->caps );
		}

		// The name used for that user in @-mentions.
		if ( bp_is_active( 'activity' ) ) {
			$data['mention_name'] = bp_activity_get_user_mentionname( $user->ID );
		}

		// Get item schema.
		$schema = $this->get_item_schema();

		// Avatars.
		if ( ! empty( $schema['properties']['avatar_urls'] ) ) {
			$blocked_by_show_avatar = false;
			$group_ids               = $request->get_param( 'group_id' );
			if ( ! empty( $group_ids ) ) {
				$group_ids = strpos( $group_ids, ',' ) !== false ? explode( ',', $group_ids ) : array( $group_ids );
				foreach ( $group_ids as $group_id ) {
					if (
						groups_is_user_admin( bp_loggedin_user_id(), $group_id ) ||
						groups_is_user_mod( bp_loggedin_user_id(), $group_id )
					) {
						$blocked_by_show_avatar = true;
						break;
					}
				}
			}
			if ( true === $blocked_by_show_avatar ) {
				remove_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );
				add_filter( 'bb_get_blocked_avatar_url', array( $this, 'bb_moderation_fetch_avatar_url_filter', ), 10, 3 );
			}
			$data['avatar_urls'] = array(
				'full'       => bp_core_fetch_avatar(
					array(
						'item_id' => $user->ID,
						'html'    => false,
						'type'    => 'full',
					)
				),
				'thumb'      => bp_core_fetch_avatar(
					array(
						'item_id' => $user->ID,
						'html'    => false,
					)
				),
				'is_default' => ! bp_get_user_has_avatar( $user->ID ),
			);
			if ( true === $blocked_by_show_avatar ) {
				remove_filter( 'bb_get_blocked_avatar_url', array( $this, 'bb_moderation_fetch_avatar_url_filter' ), 10, 3 );
				add_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );
			}
		}

		// Cover Image.
		$data['cover_url']        = (
			empty( bp_disable_cover_image_uploads() )
			? bp_attachments_get_attachment(
				'url',
				array(
					'object_dir' => 'members',
					'item_id'    => $user->ID,
				)
			)
			: false
		);
		$data['cover_is_default'] = ! bp_attachments_get_user_has_cover_image( $user->ID );

		// Fallback.
		if ( false === $data['member_types'] ) {
			$data['member_types'] = array();
		}

		if ( function_exists( 'bp_member_type_enable_disable' ) && bp_member_type_enable_disable() === false ) {
			$data['member_types'] = array();
		}

		if ( ! empty( $data['member_types'] ) ) {
			$member_types = array();
			foreach ( $data['member_types'] as $name ) {
				$member_types[ $name ] = bp_get_member_type_object( $name );

				// Member type's label background and text color.
				$label_color_data = function_exists( 'bb_get_member_type_label_colors' ) ? bb_get_member_type_label_colors( $name ) : '';
				if ( ! empty( $label_color_data ) ) {
					$member_types[ $name ]->label_colors = $label_color_data;
				}
			}
			$data['member_types'] = $member_types;
		}

		// It will check non-admin members can send message or not before they can connected to each other.
		$allowed_message = false;

		if (
			bp_is_active( 'messages' ) &&
			bb_messages_user_can_send_message(
				array(
					'sender_id'     => bp_loggedin_user_id(),
					'recipients_id' => $user->ID,
				)
			)
		) {
			$allowed_message = true;
		}

		// It will check non-admin members can send message or not before they can connected to each other.
		// Also check access controls settings.
		$data['can_send_message'] = (
			bp_is_active( 'messages' ) &&
			bp_loggedin_user_id() &&
			apply_filters( 'bp_rest_user_can_show_send_message_button', true, $user->ID ) &&
			$allowed_message
		);

		return $data;
	}

	/**
	 * Prepares a single user for creation or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return stdClass
	 * @todo Improve sanitization and schema verification.
	 *
	 * @since 0.1.0
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_user = parent::prepare_item_for_database( $request );

		// The parent class uses username instead of user_login.
		if ( ! isset( $prepared_user->user_login ) && isset( $request['user_login'] ) ) {
			$prepared_user->user_login = $request['user_login'];
		}

		// Set member type.
		if ( isset( $prepared_user->ID ) && isset( $request['member_type'] ) ) {
			// Append on update. Add on creation.
			$append = WP_REST_Server::EDITABLE === $request->get_method();
			bp_set_member_type( $prepared_user->ID, $request['member_type'], $append );
		}

		/**
		 * Filters an user object before it is inserted or updated via the REST API.
		 *
		 * @param stdClass $prepared_user An object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request Request object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_pre_insert_value', $prepared_user, $request );
	}

	/**
	 * Get XProfile info from the user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function xprofile_data( $user_id ) {
		$data = array();

		// Get XProfile groups, only if the component is active.
		if ( bp_is_active( 'xprofile' ) ) {
			$fields_endpoint = new BP_REST_XProfile_Fields_Endpoint();

			$groups = bp_xprofile_get_groups(
				array(
					'user_id'                        => $user_id,
					'fetch_fields'                   => true,
					'fetch_field_data'               => true,
					'hide_empty_fields'              => true,
					'repeater_show_main_fields_only' => false,
				)
			);

			foreach ( $groups as $group ) {
				$data['groups'][ $group->id ] = array(
					'name' => wp_specialchars_decode( $group->name, ENT_QUOTES ),
				);

				foreach ( $group->fields as $item ) {

					/**
					 * Added support for display name format support from platform.
					 */
					// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
					if ( function_exists( 'bp_core_hide_display_name_field' ) && true === bp_core_hide_display_name_field( $item->id ) ) {
						continue;
					}

					if ( function_exists( 'bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
						if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() === $item->id ) {
							continue;
						}
					}
					/**
					 * --Added support for display name format support from platform.
					 */

					$field_value = $item->data->value;

					$data['groups'][ $group->id ]['fields'][ $item->id ] = array(
						'name'  => $item->name,
						'value' => array(
							'raw'          => $fields_endpoint->get_profile_field_raw_value( $field_value, $item ),
							'unserialized' => $fields_endpoint->get_profile_field_unserialized_value( $field_value, $item ),
							'rendered'     => $fields_endpoint->get_profile_field_rendered_value( $field_value, $item ),
						),
					);
				}
			}
		} else {
			$data = array( __( 'No extended profile data available as the component is inactive', 'buddyboss' ) );
		}

		return $data;
	}

	/**
	 * Can user manage (delete/update) a member?
	 *
	 * @param WP_User $user User object.
	 * @param string  $action The action to perform (update or delete).
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	protected function can_manage_member( $user, $action = 'delete' ) {
		$capability = 'delete_user';

		if ( 'update' === $action ) {
			$capability = 'edit_user';
		}

		return ( current_user_can( 'bp_moderate' ) || current_user_can( $capability, $user->ID ) );
	}

	/**
	 * Updates the values of additional fields added to a data object.
	 *
	 * This function makes sure updating the field value thanks to the `id` property of
	 * the created/updated object type is consistent accross BuddyPress components.
	 *
	 * @param WP_User         $object The WordPress user object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True on success, WP_Error object if a field cannot be updated.
	 * @since 0.1.0
	 */
	protected function update_additional_fields_for_object( $object, $request ) {
		if ( ! isset( $object->data ) ) {
			return new WP_Error(
				'invalid_user',
				__( 'The data for the user was not found.', 'buddyboss' )
			);
		}

		$member     = $object->data;
		$member->id = $member->ID;

		return WP_REST_Controller::update_additional_fields_for_object( $member, $request );
	}

	/**
	 * Make sure to retrieve the needed arguments for the endpoint CREATABLE method.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		// Add member type args.
		$member_type_args = array(
			'description'       => __( 'Set type(s) for a member.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => bp_get_member_types(),
			'context'           => array( 'edit' ),
			'sanitize_callback' => 'bp_rest_sanitize_member_types',
			'validate_callback' => 'bp_rest_sanitize_member_types',
		);

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// We don't need the mention name to create a user.
			unset( $args['mention_name'] );

			// Add member type args.
			$args['types'] = $member_type_args;

			// But we absolutely need the email.
			$args['email'] = array(
				'description' => __( 'The email address for the member.', 'buddyboss' ),
				'type'        => 'string',
				'format'      => 'email',
				'context'     => array( 'edit' ),
				'required'    => true,
			);
		} elseif ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			/**
			 * 1. The mention name or user login are not updatable.
			 * 2. The password belongs to the Settings endpoint parameter.
			 */
			unset( $args['mention_name'], $args['user_login'], $args['password'] );

			// Add member type args.
			$args['types'] = $member_type_args;

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
		return apply_filters( "bp_rest_members_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the members schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_members',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => __( 'A unique numeric ID for the Member.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'               => array(
					'description' => __( 'Display name for the member.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'mention_name'       => array(
					'description' => __( 'The name used for that user in @-mentions.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'link'               => array(
					'description' => __( 'Profile URL of the member.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_login'         => array(
					'description' => __( 'An alphanumeric identifier for the Member.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'check_username' ),
					),
				),
				'member_types'       => array(
					'description' => __( 'Member types associated with the member.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'registered_date'    => array(
					'description' => __( 'Registration date for the member.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'password'           => array(
					'description' => __( 'Password for the member (never included).', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array(), // Password is never displayed.
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'check_user_password' ),
					),
				),
				'roles'              => array(
					'description' => __( 'Roles assigned to the member.', 'buddyboss' ),
					'type'        => 'array',
					'context'     => array( 'edit' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'capabilities'       => array(
					'description' => __( 'All capabilities assigned to the user.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'extra_capabilities' => array(
					'description' => __( 'Any extra capabilities assigned to the user.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'profile_name'       => array(
					'description' => __( 'Display name for the member based on the privacy setting.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'last_activity'      => array(
					'description' => __( 'Last Active time for the member.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'xprofile'           => array(
					'description' => __( 'Member XProfile groups and its fields.', 'buddyboss' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'followers'          => array(
					'description' => __( 'Followers counts for the current user.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'following'          => array(
					'description' => __( 'Followings counts for the current user.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'friendship_status'  => array(
					'description' => __( 'Friendship relation with, current, logged in user.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
					'enum'        => array( 'is_friends', 'not_friends', 'pending', 'awaiting_response' ),
				),
				'friendship_id'      => array(
					'description' => __( 'A unique numeric ID for the friendship.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'create_friendship'  => array(
					'description' => __( 'Logged in user can create friendship with current user.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_following'       => array(
					'description' => __( 'Check if a user is following or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_wp_admin'        => array(
					'description' => __( 'Whether the member is an administrator.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'can_follow'         => array(
					'description' => __( 'Check if a user can follow or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'can_send_message'   => array(
					'description' => __( 'Logged in user can send message or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		// Avatars.
		if ( true === buddypress()->avatar->show_avatars ) {
			$avatar_properties = array();

			$avatar_properties['full'] = array(
				/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
				'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_full_width() ), bp_core_number_format( bp_core_avatar_full_height() ) ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'embed', 'view', 'edit' ),
			);

			$avatar_properties['thumb'] = array(
				/* translators: 1: Thumb avatar width in pixels. 2: Thumb avatar height in pixels */
				'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_thumb_width() ), bp_core_number_format( bp_core_avatar_thumb_height() ) ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'embed', 'view', 'edit' ),
			);

			$avatar_properties['is_default'] = array(
				'description' => __( 'Whether the member has a default avatar or not.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
			);

			$schema['properties']['avatar_urls'] = array(
				'description' => __( 'Avatar URLs for the member.', 'buddyboss' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		$schema['properties']['cover_url'] = array(
			'description' => __( 'Cover images URL for the member.', 'buddyboss' ),
			'type'        => 'string',
			'context'     => array( 'embed', 'view', 'edit' ),
			'readonly'    => true,
		);

		$schema['properties']['cover_is_default'] = array(
			'description' => __( 'Whether to check member has default cover image or not.', 'buddyboss' ),
			'type'        => 'boolean',
			'context'     => array( 'embed', 'view', 'edit' ),
			'readonly'    => true,
		);

		/**
		 * Filters the members schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_members_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params = array_intersect_key(
			parent::get_collection_params(),
			array(
				'context'  => true,
				'page'     => true,
				'per_page' => true,
				'search'   => true,
			)
		);

		$params['type'] = array(
			'description'       => __( 'Shorthand for certain orderby/order combinations.', 'buddyboss' ),
			'default'           => 'newest',
			'type'              => 'string',
			'enum'              => array( 'active', 'newest', 'alphabetical', 'random', 'online', 'popular', 'include' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit results to friends of a user.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_ids'] = array(
			'description'       => __( 'Pass IDs of users to limit result set.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Ensure result set includes specific IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['member_type'] = array(
			'description'       => __( 'Limit results set to certain type(s).', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['xprofile'] = array(
			'description'       => __( 'Limit results set to a certain xProfile field.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['bp_ps_search'] = array(
			'description' => __( 'Profile Search form field data(s).', 'buddyboss' ),
			'default'     => array(),
			'type'        => 'object',
		);

		$params['scope'] = array(
			'description'       => __( 'Limit result set to items with a specific scope.', 'buddyboss' ),
			'type'              => 'string',
			'default'           => 'all',
			'enum'              => array( 'all', 'personal', 'following', 'followers' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_members_collection_params', $params );
	}

	/**
	 * Returns BuddyBoss Profile Search results.
	 *
	 * @param array $request Array of filter's data.
	 *
	 * @return array
	 * @since 0.1.0
	 * - bp-core\profile-search\bps-search.php bp_ps_search()
	 */
	public function rest_bp_ps_search( $request ) {
		$results = array(
			'users'     => array( 0 ),
			'validated' => true,
		);

		$fields = bp_ps_parse_request( $request );

		$copied_arr = array();

		foreach ( $fields as $f ) {
			// Disable search for some individual field.
			if ( ! apply_filters( 'bp_ps_field_can_filter', true, $f, $request ) ) {
				continue;
			}

			if ( ! isset( $f->filter ) ) {
				continue;
			}
			if ( ! is_callable( $f->search ) ) {
				continue;
			}

			$f = apply_filters( 'bp_ps_field_before_query', $f );

			$found = call_user_func( $f->search, $f );
			$found = apply_filters( 'bp_ps_field_search_results', $found, $f );

			$copied_arr = $found;
			if ( isset( $copied_arr ) && ! empty( $copied_arr ) ) {
				foreach ( $copied_arr as $key => $user ) {
					$field_visibility = xprofile_get_field_visibility_level( intval( $f->id ), intval( $user ) );
					if ( 'adminsonly' === $field_visibility && ! current_user_can( 'administrator' ) ) {
						$key = array_search( $user, $found, true );
						if ( false !== $key ) {
							unset( $found[ $key ] );
						}
					}
					if ( 'friends' === $field_visibility && ! current_user_can( 'administrator' ) && false === friends_check_friendship( intval( $user ), get_current_user_id() ) ) {
						$key = array_search( $user, $found, true );
						if ( false !== $key ) {
							unset( $found[ $key ] );
						}
					}
				}
			}

			$match_all = apply_filters( 'bp_ps_match_all', true );
			if ( $match_all ) {
				$users = isset( $users ) ? array_intersect( $users, $found ) : $found;
				if ( count( $users ) === 0 ) {
					return $results;
				}
			} else {
				$users = isset( $users ) ? array_merge( $users, $found ) : $found;
			}
		}

		if ( isset( $users ) ) {
			$results['users'] = $users;
		} else {
			$results['validated'] = false;
		}

		return $results;
	}

	/**
	 * Returns a comma separated list of user_ids for a given user's following.
	 *
	 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string.
	 *
	 * @return mixed      Comma-seperated string of user IDs on success. Integer zero on failure.
	 */
	private function rest_bp_get_following_ids( $args ) {
		if ( bp_is_active( 'follow' ) ) {
			return bp_get_following_ids( $args );
		} else {
			return ( function_exists( 'bp_get_following' ) ? bp_get_following( $args ) : '' );
		}
	}

	/**
	 * Returns a comma separated list of user_ids for a given user's followers.
	 *
	 * @param mixed $args Arguments can be passed as an associative array or as a URL argument string.
	 *
	 * @return mixed      Comma-seperated string of user IDs on success. Integer zero on failure.
	 */
	private function rest_bp_get_follower_ids( $args ) {
		if ( bp_is_active( 'follow' ) ) {
			return bp_get_follower_ids( $args );
		} else {
			return ( function_exists( 'bp_get_followers' ) ? bp_get_followers( $args ) : '' );
		}
	}

	/**
	 * Return the current member's last active time.
	 * -- from bp_get_member_last_active().
	 *
	 * @param int   $user_id User ID.
	 * @param array $args    Array of optional arguments.
	 *
	 * @return string
	 */
	public function bp_rest_get_member_last_active( $user_id, $args = array() ) {

		// Parse the activity format.
		$r = bp_parse_args(
			$args,
			array(
				'active_format' => true,
				'relative'      => true,
			)
		);

		$last_active = bp_get_user_last_activity( $user_id );

		// Backwards compatibility for anyone forcing a 'true' active_format.
		if ( true === $r['active_format'] ) {
			/* translators: last active format. */
			$r['active_format'] = __( 'active %s', 'buddyboss' );
		}

		// Member has logged in at least one time.
		if ( isset( $last_active ) ) {
			// We do not want relative time, so return now.
			// @todo Should the 'bp_member_last_active' filter be applied here?
			if ( ! $r['relative'] ) {
				return ( empty( $last_active ) ? __( 'Not recently active', 'buddyboss' ) : bp_rest_prepare_date_response( $last_active ) );
			}

			// Backwards compatibility for pre 1.5 'ago' strings.
			$last_activity = ! empty( $r['active_format'] )
				? bp_core_get_last_activity( $last_active, $r['active_format'] )
				: bp_core_time_since( $last_active );

			// Member has never logged in or been active.
		} else {
			$last_activity = __( 'Never active', 'buddyboss' );
		}

		/**
		 * Filters the current members last active time.
		 *
		 * @param string $last_activity Formatted time since last activity.
		 * @param array  $r             Array of parsed arguments for query.
		 */
		return apply_filters( 'bp_member_last_active', $last_activity, $r );
	}

	/**
	 * Function will return original avatar of blocked by member.
	 *
	 * @since BuddyBoss 2.1.6.2
	 *
	 * @param string $avatar_url     Updated avatar url.
	 * @param string $old_avatar_url Old avatar url before updated.
	 * @param array  $params         Array of parameters for the request.
	 *
	 * @return string $old_avatar_url  Old avatar url before updated.
	 */
	public function bb_moderation_fetch_avatar_url_filter( $avatar_url, $old_avatar_url, $params ) {
		return $old_avatar_url;
	}
}
