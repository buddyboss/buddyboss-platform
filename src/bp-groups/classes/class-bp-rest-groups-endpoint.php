<?php
/**
 * BP REST: BP_REST_Groups_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Groups endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Groups_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->groups->id;
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
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
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
	}

	/**
	 * Retrieve groups.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of groups object data.
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups Get Groups
	 * @apiName        GetBBGroups
	 * @apiGroup       Groups
	 * @apiDescription Retrieve groups
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=active,newest,alphabetical,random,popular,include} [type=active] Shorthand for certain orderby/order combinations.
	 * @apiParam {String=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {String=date_created,last_activity,total_member_count,name,random} [orderby=date_created] Order Groups by which attribute.
	 * @apiParam {Array=public,private,hidden   } [status] Group statuses to limit results to.
	 * @apiParam {Number} [user_id] Pass a user_id to limit to only Groups that this user is a member of.
	 * @apiParam {Array} [parent_id] Get Groups that are children of the specified Group(s) IDs.
	 * @apiParam {Array} [meta] Get Groups based on their meta data information.
	 * @apiParam {Array} [include] Ensure result set includes Groups with specific IDs.
	 * @apiParam {Array} [exclude] Ensure result set excludes Groups with specific IDs.
	 * @apiParam {String} [group_type] Limit results set to a certain Group type.
	 * @apiParam {Boolean} [enable_forum] Whether the Group has a forum enabled or not.
	 * @apiParam {Boolean} [show_hidden] Whether results should include hidden Groups.
	 * @apiParam {String=all,personal} [scope=all] Limit result set to items with a specific scope.
	 * @apiParam {Boolean} [can_post] Fetch current users groups which can post activity in it.
	 */
	public function get_items( $request ) {
		$args = array(
			'type'         => $request['type'],
			'order'        => $request['order'],
			'fields'       => $request['fields'],
			'orderby'      => $request['orderby'],
			'user_id'      => $request['user_id'],
			'include'      => $request['include'],
			'parent_id'    => $request['parent_id'],
			'exclude'      => $request['exclude'],
			'search_terms' => $request['search'],
			'meta_query'   => $request['meta'], // phpcs:ignore
			'group_type'   => $request['group_type'],
			'show_hidden'  => $request['show_hidden'],
			'per_page'     => $request['per_page'],
			'status'       => $request['status'],
			'page'         => $request['page'],
			'can_post'     => (bool) $request['can_post'],
		);
		if ( empty( $request['parent_id'] ) ) {
			$args['parent_id'] = null;
			if (
				true === (bool) bp_enable_group_hide_subgroups() &&
				isset( $request['user_id'] ) &&
				0 === (int) $request['user_id']
			) {
				$args['parent_id'] = 0;
			}
		}

		// See if the user can see hidden groups.
		if ( isset( $request['show_hidden'] ) && true === (bool) $request['show_hidden'] && ! $this->can_see_hidden_groups( $request ) ) {
			$args['show_hidden'] = false;
		}

		if ( isset( $request['scope'] ) ) {
			if ( 'personal' === $request['scope'] ) {
				$args['user_id'] = ( ! empty( $request['user_id'] ) ? (int) $request['user_id'] : get_current_user_id() );
			}
		}

		if (
			(
				empty( $request['scope'] )
				|| ( isset( $request['scope'] ) && 'all' === $request['scope'] )
			)
			&& function_exists( 'bp_groups_get_excluded_group_ids_by_type' )
			&& ! empty( bp_groups_get_excluded_group_ids_by_type() )
			&& empty( $request['parent_id'] )
		) {
			$args['exclude'] = array_unique( bp_groups_get_excluded_group_ids_by_type() );
		}

		if (
			(
				! empty( $request['include'] )
				&& ! empty( $args['orderby'] )
				&& 'include' === $args['orderby']
			) ||
			(
				! empty( $args['orderby'] )
				&& 'id' === $args['orderby']
			)
		) {
			if ( 'include' === $args['orderby'] ) {
				$args['orderby'] = 'in';
			}
			$args['type'] = '';
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_groups_get_items_query_args', $args, $request );

		// Actually, query it.
		if ( true === $args['can_post'] && function_exists( 'bb_groups_get_join_sql_for_activity' ) && function_exists( 'bb_groups_get_where_conditions_for_activity' ) ) {
			add_filter( 'bp_groups_get_join_sql', 'bb_groups_get_join_sql_for_activity', 10, 2 );
			add_filter( 'bp_groups_get_where_conditions', 'bb_groups_get_where_conditions_for_activity', 10, 2 );
		}
		$groups = groups_get_groups( $args );
		if ( true === $args['can_post'] && function_exists( 'bb_groups_get_join_sql_for_activity' ) && function_exists( 'bb_groups_get_where_conditions_for_activity' ) ) {
			remove_filter( 'bp_groups_get_join_sql', 'bb_groups_get_join_sql_for_activity', 10, 2 );
			remove_filter( 'bp_groups_get_where_conditions', 'bb_groups_get_where_conditions_for_activity', 10, 2 );
		}
		// Users need (at least, should we be more restrictive ?) to be logged in to use the edit context.
		if ( 'edit' === $request->get_param( 'context' ) && ! is_user_logged_in() ) {
			$request->set_param( 'context', 'view' );
		}

		$retval = array();
		foreach ( $groups['groups'] as $group ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $group, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $groups['total'], $args['per_page'] );

		/**
		 * Fires after a list of groups is fetched via the REST API.
		 *
		 * @param array            $groups   Fetched groups.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_get_items', $groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
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
		 * Filter the groups `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/:id Get Group
	 * @apiName        GetBBGroup
	 * @apiGroup       Groups
	 * @apiDescription Retrieve single group
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the Group.
	 */
	public function get_item( $request ) {
		$group = $this->get_group_object( $request );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $group, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group is fetched via the REST API.
		 *
		 * @param BP_Groups_Group  $group    Fetched group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_get_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
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

		$group = $this->get_group_object( $request );

		if ( true === $retval ) {
			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! $this->can_see( $group ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you cannot view the group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the groups `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/groups Create Group
	 * @apiName        CreateBBGroups
	 * @apiGroup       Groups
	 * @apiDescription Create groups
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [creator_id=1] The ID of the user who created the Group.
	 * @apiParam {String} name The name of the Group.
	 * @apiParam {String} [slug] The URL-friendly slug for the Group.
	 * @apiParam {String} description The description of the Group.
	 * @apiParam {String=public,private,hidden} [status=public] The status of the Group.
	 * @apiParam {Boolean} [enable_forum] Whether the Group has a forum enabled or not.
	 * @apiParam {Number} [parent_id] ID of the parent Group.
	 * @apiParam {Array} [types] Set type(s) for a group.
	 */
	public function create_item( $request ) {

		// Setting context.
		$request->set_param( 'context', 'edit' );

		// If no group name.
		if ( empty( $request['name'] ) ) {
			return new WP_Error(
				'bp_rest_create_group_empty_name',
				__( 'Please, enter the name of group.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$group_id = groups_create_group( $this->prepare_item_for_database( $request ) );

		if ( ! is_numeric( $group_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_group',
				__( 'Cannot create new group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$group         = $this->get_group_object( $group_id );
		$fields_update = $this->update_additional_fields_for_object( $group, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		// Set group type(s).
		if ( ! empty( $request['types'] ) ) {
			bp_groups_set_group_type( $group_id, $request['types'] );
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $group, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group is created via the REST API.
		 *
		 * @param BP_Groups_Group  $group    The created group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_create_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create groups.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_user_can_create_groups() ) {
			$retval = true;
		}

		/**
		 * Filter the groups `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a group.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {PATCH} /wp-json/buddyboss/v1/groups/:id Update Group
	 * @apiName        UpdateBBGroup
	 * @apiGroup       Groups
	 * @apiDescription Update a group
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Group.
	 * @apiParam {Number} [creator_id] The ID of the user who created the Group.
	 * @apiParam {String} [name] The name of the Group.
	 * @apiParam {String} [description] The description of the Group.
	 * @apiParam {String=public,private,hidden} [status] The status of the Group.
	 * @apiParam {Boolean} [enable_forum] Whether the Group has a forum enabled or not.
	 * @apiParam {Number} [parent_id] ID of the parent Group.
	 * @apiParam {Array} [types] Set type(s) for a group.
	 * @apiParam {Array} [append_types] Append type(s) for a group.
	 * @apiParam {Array} [remove_types] Remove type(s) for a group.
	 */
	public function update_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$group_id = groups_create_group( $this->prepare_item_for_database( $request ) );

		if ( ! is_numeric( $group_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_group',
				__( 'Cannot update existing group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$group         = $this->get_group_object( $group_id );
		$fields_update = $this->update_additional_fields_for_object( $group, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $group, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group is updated via the REST API.
		 *
		 * @param BP_Groups_Group  $group    The updated group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_update_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to update this group.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$group  = $this->get_group_object( $request );

			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);

				// If group author does not match logged_in user, block update.
			} elseif ( ! $this->can_user_delete_or_update( $group ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the groups `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/groups/:id Delete Group
	 * @apiName        DeleteBBGroup
	 * @apiGroup       Groups
	 * @apiDescription Delete a group.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Group.
	 * @apiParam {boolean} delete_group_forum Delete the Group forum if exist.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the group before it's deleted.
		$group    = $this->get_group_object( $request );
		$previous = $this->prepare_item_for_response( $group, $request );

		// Delete group forum.
		if ( isset( $request['delete_group_forum'] ) && true === $request['delete_group_forum'] ) {
			$forum_ids = bbp_get_group_forum_ids( $group->id );
			foreach ( $forum_ids as $forum_id ) {
				wp_delete_post( $forum_id, true );
			}
		}

		if ( ! groups_delete_group( $group->id ) ) {
			return new WP_Error(
				'bp_rest_group_cannot_delete',
				__( 'Could not delete the group.', 'buddyboss' ),
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
		 * Fires after a group is deleted via the REST API.
		 *
		 * @param object           $group    The deleted group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_delete_item', $group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a group.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this group.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$group  = $this->get_group_object( $request );

			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! $this->can_user_delete_or_update( $group ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the groups `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group data for return as an object.
	 *
	 * @param BP_Groups_Group $item    Group object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'                 => $item->id,
			'creator_id'         => bp_get_group_creator_id( $item ),
			'parent_id'          => $item->parent_id,
			'date_created'       => bp_rest_prepare_date_response( $item->date_created ),
			'description'        => array(
				'raw'      => $item->description,
				'rendered' => bp_get_group_description( $item ),
			),
			'enable_forum'       => $this->bp_rest_group_is_forum_enabled( $item ),
			'link'               => bp_get_group_permalink( $item ),
			'name'               => bp_get_group_name( $item ),
			'name_raw'           => $item->name,
			'slug'               => bp_get_group_slug( $item ),
			'status'             => bp_get_group_status( $item ),
			'types'              => bp_groups_get_group_type( $item->id, false ),
			'group_type_label'   => $this->get_group_type_label( $item ),
			'subgroups_id'       => $this->bp_rest_get_sub_groups( $item->id ),
			'admins'             => array(),
			'mods'               => array(),
			'total_member_count' => null,
			'last_activity'      => null,
			'is_member'          => groups_is_user_member( get_current_user_id(), $item->id ) ? true : false,
			'invite_id'          => groups_is_user_invited( get_current_user_id(), $item->id ),
			'request_id'         => groups_is_user_pending( get_current_user_id(), $item->id ),
			'is_admin'           => ( ! empty( groups_is_user_admin( get_current_user_id(), $item->id ) ) ? true : false ),
			'is_mod'             => ( ! empty( groups_is_user_mod( get_current_user_id(), $item->id ) ) ? true : false ),
			'members_count'      => groups_get_total_member_count( $item->id ),
			'role'               => '',
			'plural_role'        => '',
			'can_join'           => $this->bp_rest_user_can_join( $item ),
			'can_post'           => $this->bp_rest_user_can_post( $item ),
			'create_media'       => ( bp_is_active( 'media' ) && groups_can_user_manage_media( bp_loggedin_user_id(), $item->id ) ),
			'create_album'       => ( bp_is_active( 'media' ) && groups_can_user_manage_albums( bp_loggedin_user_id(), $item->id ) ),
			'create_video'       => ( bp_is_active( 'video' ) && groups_can_user_manage_video( bp_loggedin_user_id(), $item->id ) ),
			'create_document'    => ( bp_is_active( 'document' ) && groups_can_user_manage_document( bp_loggedin_user_id(), $item->id ) ),
		);

		// BuddyBoss Platform support.
		if ( function_exists( 'bp_get_user_group_role_title' ) && bp_loggedin_user_id() ) {
			$data['role'] = bp_get_user_group_role_title( bp_loggedin_user_id(), $item->id );

			// BuddyPress support.
		} elseif ( function_exists( 'bp_groups_get_group_roles' ) && bp_loggedin_user_id() ) {
			$group_role = bp_groups_get_group_roles();

			if ( groups_is_user_admin( bp_loggedin_user_id(), $item->id ) ) {
				$data['role'] = $group_role['admin']->name;
			} elseif ( groups_is_user_mod( bp_loggedin_user_id(), $item->id ) ) {
				$data['role'] = $group_role['mod']->name;
			} elseif ( groups_is_user_member( bp_loggedin_user_id(), $item->id ) ) {
				$data['role'] = $group_role['member']->name;
			}
		}

		if ( function_exists( 'bp_get_group_member_section_title' ) && bp_loggedin_user_id() ) {
			$data['plural_role'] = $this->bp_get_group_member_section_title( $item->id, bp_loggedin_user_id() );
			if ( empty( $data['plural_role'] ) ) {
				$data['plural_role'] = $data['role'];
			}
		} else {
			$data['plural_role'] = $data['role'];
		}

		// Get item schema.
		$schema = $this->get_item_schema();

		// Avatars.
		if ( ! empty( $schema['properties']['avatar_urls'] ) ) {
			$data['avatar_urls'] = array(
				'thumb'      => bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $item->id,
						'type'    => 'thumb',
					)
				),
				'full'       => bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $item->id,
						'type'    => 'full',
					)
				),
				'is_default' => ! bp_get_group_has_avatar( $item->id ),
			);
		}

		// Cover Image.
		if ( ! empty( $schema['properties']['cover_url'] ) && function_exists( 'bp_get_group_cover_url' ) ) {
			$data['cover_url']        = bp_get_group_cover_url( $item );
			$data['cover_is_default'] = ! bp_attachments_get_group_has_cover_image( $item->id );
		}

		if ( $this->bp_rest_group_is_forum_enabled( $item ) && function_exists( 'bbpress' ) ) {
			$data['forum'] = groups_get_groupmeta( $item->id, 'forum_id' );
			if ( is_array( $data['forum'] ) && ! empty( $data['forum'][0] ) ) {
				$data['forum'] = $data['forum'][0];
			} else {
				$data['forum'] = 0;
			}
		} else {
			$data['forum'] = 0;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Get group type(s).
		if ( false === $data['types'] ) {
			$data['types'] = array();
		}

		if ( ! empty( $data['types'] ) ) {
			$group_type_data                     = array();
			$group_type_data['group_type_label'] = isset( $data['group_type_label'] ) && ! empty( $data['group_type_label'] ) ? $data['group_type_label'] : '';
			$group_type_data['types']            = bp_groups_get_group_type( $item->id, false );
			// Group type's label background and text color.
			$group_type       = isset( $data['types'][0] ) ? $data['types'][0] : '';
			$label_color_data = function_exists( 'bb_get_group_type_label_colors' ) ? bb_get_group_type_label_colors( $group_type ) : '';
			if ( ! empty( $label_color_data ) ) {
				$group_type_data['label_colors'] = $label_color_data;
			}
			$data['group_type'] = $group_type_data;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// If this is the 'edit' context, fill in more details--similar to "populate_extras".
		if ( 'edit' === $context ) {
			$data['total_member_count'] = groups_get_groupmeta( $item->id, 'total_member_count' );
			$data['last_activity']      = bp_rest_prepare_date_response( groups_get_groupmeta( $item->id, 'last_activity' ) );

			// Add admins and moderators to their respective arrays.
			$admin_mods = groups_get_group_members(
				array(
					'group_id'   => $item->id,
					'group_role' => array(
						'admin',
						'mod',
					),
				)
			);

			foreach ( (array) $admin_mods['members'] as $user ) {
				// Make sure to unset private data.
				$private_keys = array_intersect(
					array_keys( get_object_vars( $user ) ),
					array(
						'user_pass',
						'user_email',
						'user_activation_key',
					)
				);

				foreach ( $private_keys as $private_key ) {
					unset( $user->{$private_key} );
				}

				if ( ! empty( $user->is_admin ) ) {
					$data['admins'][] = $user;
				} else {
					$data['mods'][] = $user;
				}
			}
		}

		// Member subscribed the group or not?
		if ( function_exists( 'bb_is_enabled_subscription' ) && bb_is_enabled_subscription( 'group' ) ) {
			$subscribed = 0;
			if ( is_user_logged_in() && function_exists( 'bb_is_member_subscribed_group' ) ) {
				$subscribed = bb_is_member_subscribed_group( $item->id, bp_loggedin_user_id() );
			}
			$data['is_subscribed'] = ! empty( $subscribed );
			$data['subscribed_id'] = empty( $subscribed ) ? 0 : $subscribed;
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter a group value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Groups_Group  $item     Group object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepare a group for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return stdClass|WP_Error Object or WP_Error.
	 * @since 0.1.0
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_group = new stdClass();
		$schema         = $this->get_item_schema();
		$group          = $this->get_group_object( $request );

		// Group ID.
		if ( ! empty( $schema['properties']['id'] ) && ! empty( $group->id ) ) {
			$prepared_group->group_id = $group->id;
		}

		// Group Creator ID.
		if ( ! empty( $schema['properties']['creator_id'] ) && isset( $request['creator_id'] ) ) {
			$prepared_group->creator_id = (int) $request['creator_id'];

			// Fallback on the existing creator id in case of an update.
		} elseif ( isset( $group->creator_id ) && $group->creator_id ) {
			$prepared_group->creator_id = (int) $group->creator_id;

			// Fallback on the current user otherwise.
		} else {
			$prepared_group->creator_id = bp_loggedin_user_id();
		}

		// Group Slug.
		if ( ! empty( $schema['properties']['slug'] ) && isset( $request['slug'] ) ) {
			$prepared_group->slug = $request['slug'];
		}

		// Group Name.
		if ( ! empty( $schema['properties']['name'] ) && isset( $request['name'] ) ) {
			$prepared_group->name = $request['name'];
		}

		// Do additional checks for the Group's slug.
		if ( WP_REST_Server::CREATABLE === $request->get_method() || ( isset( $group->slug ) && isset( $prepared_group->slug ) && $group->slug !== $prepared_group->slug ) ) {
			// Fallback on the group name if the slug is not defined.
			if ( ! isset( $prepared_group->slug ) && ! isset( $group->slug ) ) {
				$prepared_group->slug = $prepared_group->name;
			}

			// Make sure it is unique and sanitize it.
			$prepared_group->slug = groups_check_slug( sanitize_title( esc_attr( $prepared_group->slug ) ) );
		}

		// Group description.
		if ( ! empty( $schema['properties']['description'] ) && isset( $request['description'] ) ) {
			if ( is_string( $request['description'] ) ) {
				$prepared_group->description = $request['description'];
			} elseif ( isset( $request['description']['raw'] ) ) {
				$prepared_group->description = $request['description']['raw'];
			}
		}

		// Group status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {
			$prepared_group->status = $request['status'];
		}

		// Group Forum Enabled.
		if ( ! empty( $schema['properties']['enable_forum'] ) && isset( $request['enable_forum'] ) ) {
			$prepared_group->enable_forum = (bool) $request['enable_forum'];
		}

		// Group Parent ID.
		if ( ! empty( $schema['properties']['parent_id'] ) && isset( $request['parent_id'] ) ) {
			$prepared_group->parent_id = $request['parent_id'];
		}

		// Update group type(s).
		if ( isset( $prepared_group->group_id ) && isset( $request['types'] ) ) {
			bp_groups_set_group_type( $prepared_group->group_id, $request['types'], false );
		}

		// Remove group type(s).
		if ( isset( $prepared_group->group_id ) && isset( $request['remove_types'] ) ) {
			array_map(
				function( $type ) use ( $prepared_group ) {
					bp_groups_remove_group_type( $prepared_group->group_id, $type );
				},
				$request['remove_types']
			);
		}

		// Append group type(s).
		if ( isset( $prepared_group->group_id ) && isset( $request['append_types'] ) ) {
			bp_groups_set_group_type( $prepared_group->group_id, $request['append_types'], true );
		}

		/**
		 * Filters a group before it is inserted or updated via the REST API.
		 *
		 * @param stdClass        $prepared_group An object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request        Request object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_pre_insert_value', $prepared_group, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Groups_Group $group Group object.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $group ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $group->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $group->creator_id ) ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array           $links The prepared links of the REST response.
		 * @param BP_Groups_Group $group Group object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_prepare_links', $links, $group );
	}

	/**
	 * See if user can delete or update a group.
	 *
	 * @param BP_Groups_Group $group Group item.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function can_user_delete_or_update( $group ) {
		return (
			bp_current_user_can( 'bp_moderate' ) ||
			bp_loggedin_user_id() === $group->creator_id ||
			(
				function_exists( 'groups_is_user_admin' ) &&
				groups_is_user_admin( bp_loggedin_user_id(), $group->id )
			)
		);
	}

	/**
	 * Can a user see a group?
	 *
	 * @since 0.1.0
	 *
	 * @param  BP_Groups_Group $group Group object.
	 * @return bool
	 */
	public function can_see( $group ) {

		// If it is not a hidden group, user can see it.
		if ( 'hidden' !== $group->status ) {
			return true;
		}

		// Check for moderators or if user is a member of the group.
		return ( bp_current_user_can( 'bp_moderate' ) || groups_is_user_member( bp_loggedin_user_id(), $group->id ) || groups_is_user_invited( bp_loggedin_user_id(), $group->id ) );
	}

	/**
	 * Can this user see hidden groups?
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	protected function can_see_hidden_groups( $request ) {
		if ( $request['show_hidden'] ) {

			if ( bp_current_user_can( 'bp_moderate' ) ) {
				return true;
			}

			if ( ( is_user_logged_in() && empty( $request['user_id'] ) ) || ( isset( $request['user_id'] ) && absint( $request['user_id'] ) === bp_loggedin_user_id() ) ) {
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * Get group object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|BP_Groups_Group
	 * @since 0.1.0
	 */
	public function get_group_object( $request ) {
		if ( ! empty( $request['group_id'] ) ) {
			$group_id = (int) $request['group_id'];
		} elseif ( is_numeric( $request ) ) {
			$group_id = $request;
		} else {
			$group_id = (int) $request['id'];
		}

		$group = groups_get_group( $group_id );

		if ( empty( $group ) || empty( $group->id ) ) {
			return false;
		}

		return $group;
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

		if ( isset( $args['can_post'] ) && WP_REST_Server::READABLE !== $method ) {
			unset( $args['can_post'] );
		}

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$key                         = 'create_item';
			$args['description']['type'] = 'string';

			// Add group types.
			$args['types'] = array(
				'description'       => __( 'Set type(s) for a group.', 'buddyboss' ),
				'type'              => 'array',
				'enum'              => bp_groups_get_group_types(),
				'sanitize_callback' => 'bp_rest_sanitize_group_types',
				'validate_callback' => 'bp_rest_validate_group_types',
				'items'             => array(
					'type' => 'string',
				),
			);

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';
				unset( $args['slug'] );

				// Append group types.
				$args['append_types'] = array(
					'description'       => __( 'Append type(s) for a group.', 'buddyboss' ),
					'type'              => 'array',
					'enum'              => bp_groups_get_group_types(),
					'sanitize_callback' => 'bp_rest_sanitize_group_types',
					'validate_callback' => 'bp_rest_validate_group_types',
					'items'             => array(
						'type' => 'string',
					),
				);

				// Remove group types.
				$args['remove_types'] = array(
					'description'       => __( 'Remove type(s) for a group.', 'buddyboss' ),
					'type'              => 'array',
					'enum'              => bp_groups_get_group_types(),
					'sanitize_callback' => 'bp_rest_sanitize_group_types',
					'validate_callback' => 'bp_rest_validate_group_types',
					'items'             => array(
						'type' => 'string',
					),
				);
			}
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key  = 'delete_item';
			$args = array(
				'id' => array(
					'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
					'type'        => 'integer',
					'required'    => true,
				),
			);

			if ( bp_is_active( 'forums' ) ) {
				$args['delete_group_forum'] = array(
					'description'       => __( 'Delete the Group forum if exist.', 'buddyboss' ),
					'type'              => 'boolean',
					'default'           => false,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
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
		return apply_filters( "bp_rest_groups_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the group schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_groups',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'creator_id'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the user who created the Group.', 'buddyboss' ),
					'type'        => 'integer',
					'default'     => bp_loggedin_user_id(),
				),
				'name'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The name of the Group.', 'buddyboss' ),
					'type'        => 'string',
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'name_raw'           => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Content for the name of the Group, as it exists in the database.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'slug'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The URL-friendly slug for the Group.', 'buddyboss' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
					),
				),
				'link'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The permalink to the Group on the site.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'description'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The description of the Group.', 'buddyboss' ),
					'type'        => 'object',
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null,
						// Note: validation implemented in self::prepare_item_for_database().
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the description of the Group, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the description of the Group, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'status'             => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The status of the Group.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => buddypress()->groups->valid_status,
					'default'     => 'public',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'enable_forum'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the Group has a forum enabled or not.', 'buddyboss' ),
					'type'        => 'boolean',
				),
				'parent_id'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'ID of the parent Group.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'date_created'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( "The date the Group was created, in the site's timezone.", 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'types'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The type(s) of the Group.', 'buddyboss' ),
					'readonly'    => true,
					'enum'        => bp_groups_get_group_types(),
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
					),
				),
				'group_type_label'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Name of the group type.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'subgroups_id'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Sub Groups id if having a sub groups.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
				),
				'admins'             => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Group administrators.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
				),
				'mods'               => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Group moderators.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'array',
					'items'       => array(
						'type' => 'object',
					),
				),
				'total_member_count' => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Count of all Group members.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'last_activity'      => array(
					'context'     => array( 'edit' ),
					'description' => __( "The date the Group was last active, in the site's timezone.", 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
					'format'      => 'date-time',
				),
				// Adding additional schema.
				'is_member'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The current user is member of a group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'invite_id'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Return\'s invite ID if current user is invited for a group or not.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'request_id'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Return\'s request ID if invitation is pending for a group or not.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'is_admin'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The current user is admin of a group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'is_mod'             => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The current user is moderator of a group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'members_count'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Members count of the group.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'role'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current member\'s role label in the group.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'plural_role'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current member\'s role label in the plural form in the group', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'can_join'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Check current user can join or request access.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_post'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Check current user can post activity or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'forum'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Forum id of the group.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'create_media'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user has permission to upload media to the group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'create_album'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user has permission to create an album to the group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'create_video'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user has permission to upload video to the group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'create_document'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user has permission to upload document to the group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'group_type'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the group type details will pass.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
			),
		);

		// Avatars.
		if ( ! bp_disable_group_avatar_uploads() ) {
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
				'description' => __( 'Whether to check group has default avatar or not.', 'buddyboss' ),
				'readonly'    => true,
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
			);

			$schema['properties']['avatar_urls'] = array(
				'description' => __( 'Avatar URLs for the group.', 'buddyboss' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		if ( ! bp_disable_group_cover_image_uploads() ) {
			$schema['properties']['cover_url'] = array(
				'description' => __( 'Cover Image URLs for the group.', 'buddyboss' ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['cover_is_default'] = array(
				'description' => __( 'Whether to check the default cover image or not.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);
		}

		// Group subscriptions related schemas.
		if ( function_exists( 'bb_is_enabled_subscription' ) && bb_is_enabled_subscription( 'group' ) ) {
			$schema['properties']['is_subscribed'] = array(
				'context'     => array( 'embed', 'view', 'edit' ),
				'description' => __( 'The current user is subscribed of a group or not.', 'buddyboss' ),
				'type'        => 'boolean',
				'readonly'    => true,
			);

			$schema['properties']['subscribed_id'] = array(
				'context'     => array( 'embed', 'view', 'edit' ),
				'description' => __( 'The group subscription ID of current user.', 'buddyboss' ),
				'type'        => 'integer',
				'readonly'    => true,
			);
		}

		/**
		 * Filters the group schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of groups.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['type'] = array(
			'description'       => __( 'Shorthand for certain orderby/order combinations.', 'buddyboss' ),
			'default'           => 'active',
			'type'              => 'string',
			'enum'              => array( 'active', 'newest', 'alphabetical', 'random', 'popular', 'include' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order Groups by which attribute.', 'buddyboss' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'date_created', 'last_activity', 'total_member_count', 'name', 'random', 'id', 'include' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Group statuses to limit results to.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array(
				'enum' => buddypress()->groups->valid_status,
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Pass a user_id to limit to only Groups that this user is a member of.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['parent_id'] = array(
			'description'       => __( 'Get Groups that are children of the specified Group(s) IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		// @todo Confirm what's the proper sanitization here.
		$params['meta'] = array(
			'description'       => __( 'Get Groups based on their meta data information.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Ensure result set includes Groups with specific IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes Groups with specific IDs', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['group_type'] = array(
			'description'       => __( 'Limit results set to a certain Group type.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'enum'              => bp_groups_get_group_types(),
			'sanitize_callback' => 'bp_rest_sanitize_group_types',
			'validate_callback' => 'bp_rest_validate_group_types',
		);

		$params['enable_forum'] = array(
			'description'       => __( 'Whether the Group has a forum enabled or not.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['show_hidden'] = array(
			'description'       => __( 'Whether results should include hidden Groups.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description'       => __( 'Limit result set to items with a specific scope.', 'buddyboss' ),
			'type'              => 'string',
			'default'           => 'all',
			'enum'              => array( 'all', 'personal' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['can_post'] = array(
			'description'       => __( 'Fetch current users groups which can post activity in it.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_groups_collection_params', $params );
	}

	/**
	 * Check the forum is enable or not with platform.
	 *
	 * @param BP_Groups_Group $group Group object.
	 *
	 * @return bool
	 */
	public function bp_rest_group_is_forum_enabled( $group ) {

		if ( function_exists( 'bbp_is_group_forums_active' ) ) {
			return bbp_is_group_forums_active() && bp_group_is_forum_enabled( $group );
		} else {
			return bp_group_is_forum_enabled( $group );
		}

		return false;
	}

	/**
	 * Check the group join with members type.
	 * - from bp_get_group_join_button().
	 *
	 * @param BP_Groups_Group $item Group object.
	 *
	 * @return bool
	 */
	protected function bp_rest_user_can_join( $item ) {
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return false;
		}

		if (
			'hidden' === bp_get_group_status( $item )
			|| bp_group_is_user_banned( $item, $user_id )
			|| ! empty( $item->is_member )
		) {
			return false;
		}

		// Don't Show the button if restrict invite is enabled and member is not a part of parent group.
		$parent_group_id = bp_get_parent_group_id( $item->id );
		if (
			isset( $parent_group_id )
			&& $parent_group_id > 0
			&& function_exists( 'bp_enable_group_hierarchies' )
			&& true === bp_enable_group_hierarchies()
			&& function_exists( 'bp_enable_group_restrict_invites' )
			&& true === bp_enable_group_restrict_invites()
		) {
			$is_member = groups_is_user_member( $user_id, $parent_group_id );
			if ( false === $is_member ) {
				return false;
			}
		}

		if ( 'public' === bp_get_group_status( $item ) ) {
			return true;
		}

		if ( 'private' === bp_get_group_status( $item ) ) {
			if ( $item->is_invited ) {
				return true;
			} elseif ( $item->is_pending ) {
				return false;
			} else {
				// Check for the group type > profile type joining.
				if (
					function_exists( 'bp_member_type_enable_disable' )
					&& true === bp_member_type_enable_disable()
					&& function_exists( 'bp_disable_group_type_creation' )
					&& true === bp_disable_group_type_creation()
				) {
					$group_type = bp_groups_get_group_type( $item->id );

					$group_type_id = bp_group_get_group_type_id( $group_type );

					$get_selected_member_type_join = get_post_meta( $group_type_id, '_bp_group_type_enabled_member_type_join', true );

					$get_requesting_user_member_type = bp_get_member_type( $user_id );

					if ( is_array( $get_selected_member_type_join ) && in_array( $get_requesting_user_member_type, $get_selected_member_type_join, true ) ) {
						return true;
					} else {
						return true;
					}
				} else {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check the user can post activity into group or not based on settings.
	 *
	 * @param BP_Groups_Group $item Group object.
	 *
	 * @return bool
	 */
	protected function bp_rest_user_can_post( $item ) {
		if ( ! bp_is_active( 'activity' ) ) {
			return false;
		}

		return is_user_logged_in() && bp_group_is_member( $item ) && bp_group_is_member_allowed_posting( $item );
	}

	/**
	 * Get sub groups id.
	 *
	 * @param integer $parent_group_id Group ID.
	 *
	 * @return array
	 */
	public function bp_rest_get_sub_groups( $parent_group_id ) {
		if ( empty( $parent_group_id ) ) {
			return array();
		}

		$user_id = bp_loggedin_user_id();
		$filter  = ( false !== $user_id && ! bp_user_can( $user_id, 'bp_moderate' ) );

		if ( function_exists( 'bp_include_group_by_context ' ) ) {
			$sub_groups = groups_get_groups(
				array(
					'parent_id'   => $parent_group_id,
					'fields'      => 'ids',
					'show_hidden' => true,
					'per_page'    => false,
					'page'        => false,
				)
			);

			// Reset parents array to rebuild for next round.
			$groups = array();
			foreach ( $sub_groups['groups'] as $group ) {
				if ( $filter ) {
					if ( bp_include_group_by_context( $group, $user_id, 'normal' ) ) {
						$groups[] = $group->id;
					}
				} else {
					$groups[] = $group->id;
				}
			}

			return $groups;
			// buddypress support.
		} else {
			$sub_groups = groups_get_groups(
				array(
					'parent_id'   => $parent_group_id,
					'fields'      => 'ids',
					'show_hidden' => false,
					'per_page'    => false,
					'page'        => false,
				)
			);

			if ( ! empty( $sub_groups ) && isset( $sub_groups['groups'] ) && ! empty( $sub_groups['groups'] ) ) {
				return $sub_groups['groups'];
			}
		}

		return array();
	}

	/**
	 * Return the group member section header while in the groups members loop.
	 *
	 * @param integer $group_id Group ID.
	 * @param integer $user_id  User ID.
	 *
	 * @return string
	 */
	public function bp_get_group_member_section_title( $group_id, $user_id ) {

		if ( empty( $group_id ) || empty( $user_id ) ) {
			return;
		}

		$user_group_role_title = bp_get_user_group_role_title( $user_id, $group_id );
		$group_admin           = groups_get_group_admins( $group_id );
		$group_mode            = groups_get_group_mods( $group_id );
		$group_member          = groups_get_group_members(
			array(
				'group_id' => $group_id,
				'per_page' => 10,
				'page'     => 1,
			)
		);

		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			if ( isset( $group_admin ) && count( $group_admin ) > 1 ) {
				return get_group_role_label( $group_id, 'organizer_plural_label_name' );
			} else {
				return get_group_role_label( $group_id, 'organizer_singular_label_name' );
			}
		} elseif ( groups_is_user_mod( $user_id, $group_id ) ) {
			if ( isset( $group_mode ) && count( $group_mode ) > 1 ) {
				return get_group_role_label( $group_id, 'moderator_plural_label_name' );
			} else {
				return get_group_role_label( $group_id, 'moderator_singular_label_name' );
			}
		} elseif ( groups_is_user_member( $user_id, $group_id ) ) {
			$member_count = (int) ( isset( $group_member['count'] ) ? $group_member['count'] : 0 );
			if ( $member_count > 1 ) {
				return get_group_role_label( $group_id, 'member_plural_label_name' );
			} else {
				return get_group_role_label( $group_id, 'member_singular_label_name' );
			}
		}

		return $user_group_role_title;

	}

	/**
	 * Fetch group type label.
	 *
	 * @param BP_Groups_Group $group Group object.
	 *
	 * @return mixed|string|void
	 */
	public function get_group_type_label( $group ) {

		if ( function_exists( 'bp_disable_group_type_creation' ) && true !== bp_disable_group_type_creation() ) {
			return '';
		}

		$group_type = bp_groups_get_group_type( $group->id );
		$group_type = bp_groups_get_group_type_object( $group_type );

		return isset( $group_type->labels['singular_name'] ) ? wp_specialchars_decode( $group_type->labels['singular_name'] ) : __( 'Group', 'buddyboss' );
	}
}
