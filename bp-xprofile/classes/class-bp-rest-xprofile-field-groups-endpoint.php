<?php
/**
 * BP REST: BP_REST_XProfile_Field_Groups_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile Field Groups Endpoints.
 *
 * Use /xprofile/groups
 * Use /xprofile/groups/{id}
 *
 * @since 0.1.0
 */
class BP_REST_XProfile_Field_Groups_Endpoint extends WP_REST_Controller {

	/**
	 * XProfile Fields Class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_XProfile_Fields_Endpoint
	 */
	protected $fields_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace       = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base       = buddypress()->profile->id . '/groups';
		$this->fields_endpoint = new BP_REST_XProfile_Fields_Endpoint();
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
						'description' => __( 'A unique numeric ID for the group of profile fields.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Edit some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$key                         = 'update_item';
			$args['description']['type'] = 'string';
			unset( $args['description']['properties'] );

			if ( WP_REST_Server::CREATABLE === $method ) {
				$key = 'create_item';
				unset( $args['group_order'] );
			}
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
		return apply_filters( "bp_rest_xprofile_field_groups_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Retrieve XProfile groups.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/xprofile/groups Get xProfile Groups
	 * @apiName        GetBBxProfileGroups
	 * @apiGroup       Profile Fields
	 * @apiDescription Retrieve xProfile Groups
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [profile_group_id] ID of the field group that have fields.
	 * @apiParam {Boolean} [hide_empty_groups=false] Whether to hide profile groups of fields that do not have any profile fields or not.
	 * @apiParam {Number} [user_id=1] Required if you want to load a specific user's data.
	 * @apiParam {string} [member_type] Limit fields by those restricted to a given member type, or array of member types.
	 * @apiParam {Boolean} [hide_empty_fields=false] Whether to hide profile groups of fields that do not have any profile fields or not.
	 * @apiParam {Boolean} [fetch_fields=false] Whether to fetch the fields for each group.
	 * @apiParam {Boolean} [fetch_field_data=false] Whether to fetch data for each field. Requires a $user_id.
	 * @apiParam {Boolean} [fetch_visibility_level=false] Whether to fetch the visibility level for each field.
	 * @apiParam {Array} [exclude_groups] Ensure result set excludes specific profile field groups.
	 * @apiParam {Array} [exclude_fields] Ensure result set excludes specific profile fields.
	 * @apiParam {Boolean} [update_meta_cache=true] Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.
	 */
	public function get_items( $request ) {
		$args = array(
			'profile_group_id'       => $request['profile_group_id'],
			'user_id'                => $request['user_id'],
			'member_type'            => $request['member_type'],
			'hide_empty_groups'      => $request['hide_empty_groups'],
			'hide_empty_fields'      => $request['hide_empty_fields'],
			'fetch_fields'           => $request['fetch_fields'],
			'fetch_field_data'       => $request['fetch_field_data'],
			'fetch_visibility_level' => $request['fetch_visibility_level'],
			'exclude_groups'         => $request['exclude_groups'],
			'exclude_fields'         => $request['exclude_fields'],
			'update_meta_cache'      => $request['update_meta_cache'],
		);

		if ( empty( $request['member_type'] ) ) {
			$args['member_type'] = null;
		}

		if ( empty( $request['exclude_fields'] ) ) {
			$args['exclude_fields'] = false;
		}

		if ( empty( $request['exclude_groups'] ) ) {
			$args['exclude_groups'] = false;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_xprofile_field_groups_get_items_query_args', $args, $request );

		// Actually, query it.
		$field_groups = bp_xprofile_get_groups( $args );

		$retval = array();
		foreach ( (array) $field_groups as $item ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $item, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of field groups are fetched via the REST API.
		 *
		 * @param array $field_groups Fetched field groups.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_field_groups_get_items', $field_groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to XProfile field groups items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
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
		 * Filter the XProfile fields groups `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve single XProfile field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/xprofile/groups/:id Get xProfile Group
	 * @apiName        GetBBxProfilGroup
	 * @apiGroup       Profile Fields
	 * @apiDescription Retrieve Single xProfile Group
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the group of profile fields.
	 * @apiParam {Number} [user_id=1] Required if you want to load a specific user's data.
	 * @apiParam {string} [member_type] Limit fields by those restricted to a given member type, or array of member types.
	 * @apiParam {Boolean} [hide_empty_fields=false] Whether to hide profile groups of fields that do not have any profile fields or not.
	 * @apiParam {Boolean} [fetch_fields=false] Whether to fetch the fields for each group.
	 * @apiParam {Boolean} [fetch_field_data=false] Whether to fetch data for each field. Requires a $user_id.
	 * @apiParam {Boolean} [fetch_visibility_level=false] Whether to fetch the visibility level for each field.
	 * @apiParam {Array} [exclude_fields] Ensure result set excludes specific profile fields.
	 * @apiParam {Boolean} [update_meta_cache=true] Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.
	 */
	public function get_item( $request ) {
		$field_group = $this->get_xprofile_field_group_object( $request );

		if ( empty( $field_group->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $field_group, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a field group is fetched via the REST API.
		 *
		 * @param BP_XProfile_Group $field_group Fetched field group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_field_groups_get_item', $field_group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = $this->get_items_permissions_check( $request );

		/**
		 * Filter the XProfile fields groups `get_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a XProfile field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/xprofile/groups Create xProfile Group
	 * @apiName        CreateBBxProfileGroup
	 * @apiGroup       Profile Fields
	 * @apiDescription Create a Group
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} name The name of group of profile fields.
	 * @apiParam {String} [description] The description of the group of profile fields.
	 * @apiParam {Boolean} [can_delete=true] Whether the group of profile fields can be deleted or not.
	 * @apiParam {Boolean} [repeater_enabled=false] The description of the profile field.
	 */
	public function create_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$args = array(
			'name'        => $request['name'],
			'description' => $request['description'],
			'can_delete'  => $request['can_delete'],
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_xprofile_field_groups_create_item_query_args', $args, $request );

		if ( empty( $args['name'] ) ) {
			return new WP_Error(
				'bp_rest_required_param_missing',
				__( 'Required param missing.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$group_id = xprofile_insert_field_group( $args );

		if ( ! $group_id ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_xprofile_field_group',
				__( 'Cannot create new XProfile field group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$field_group = $this->get_xprofile_field_group_object( $group_id );

		// Create Additional fields.
		$fields_update = $this->update_additional_fields_for_object( $field_group, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $field_group, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a XProfile field group is created via the REST API.
		 *
		 * @param BP_XProfile_Group $field_group Created field group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_field_groups_create_item', $field_group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a XProfile field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to view this XProfile field group.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the XProfile fields groups `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a XProfile field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/xprofile/groups/:id Update xProfile Group
	 * @apiName        UpdateBBxProfileGroup
	 * @apiGroup       Profile Fields
	 * @apiDescription Update a Group
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the group of profile fields.
	 * @apiParam {String} [name] The name of group of profile fields.
	 * @apiParam {String} [description] The description of the group of profile fields.
	 * @apiParam {Number} [group_order] The order of the group of profile fields.
	 * @apiParam {Boolean} [can_delete] Whether the group of profile fields can be deleted or not.
	 * @apiParam {Boolean} [repeater_enabled] The description of the profile field.
	 */
	public function update_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$field_group = $this->get_xprofile_field_group_object( $request );

		if ( empty( $field_group->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$args = array(
			'field_group_id' => $field_group->id,
			'name'           => is_null( $request['name'] ) ? $field_group->name : $request['name'],
			'description'    => is_null( $request['description'] ) ? $field_group->description : $request['description'],
			'can_delete'     => is_null( $request['can_delete'] ) ? (bool) $field_group->can_delete : $request['can_delete'],
		);

		$group_id = xprofile_insert_field_group( $args );

		if ( ! $group_id ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_xprofile_field_group',
				__( 'Cannot update XProfile field group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Update the position if the group_order exists.
		if ( is_numeric( $request['group_order'] ) ) {
			xprofile_update_field_group_position( $group_id, $request['group_order'] );
		}

		// Update the group meta for repeater set.
		if ( isset( $request['repeater_enabled'] ) ) {
			if ( true === $request['repeater_enabled'] ) {
				bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'on' );
			} else {
				bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'off' );
			}
		} else {
			$repeater_enabled = bp_xprofile_get_meta( $group_id, 'group', 'is_repeater_enabled', true );
			if ( ! empty( $repeater_enabled ) && 'on' === $repeater_enabled ) {
				bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'on' );
			} else {
				bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'off' );
			}
		}

		$field_group = $this->get_xprofile_field_group_object( $group_id );

		// Update Additional fields.
		$fields_update = $this->update_additional_fields_for_object( $field_group, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $field_group, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a XProfile field group is updated via the REST API.
		 *
		 * @param BP_XProfile_Group $field_group Updated field group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_field_groups_update_item', $field_group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a XProfile field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->create_item_permissions_check( $request );

		/**
		 * Filter the XProfile fields groups `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a XProfile field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/xprofile/groups/:id Delete xProfile Group
	 * @apiName        DeleteBBxProfileGroup
	 * @apiGroup       Profile Fields
	 * @apiDescription Delete xProfile Group.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the group of profile fields.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the field group before it's deleted.
		$field_group = $this->get_xprofile_field_group_object( $request );

		if ( empty( $field_group->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! xprofile_delete_field_group( $field_group->id ) ) {
			return new WP_Error(
				'bp_rest_xprofile_field_group_cannot_delete',
				__( 'Could not delete XProfile field group.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$previous = $this->prepare_item_for_response( $field_group, $request );
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a field group is deleted via the REST API.
		 *
		 * @param BP_XProfile_Group $field_group Deleted field group.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_field_groups_delete_item', $field_group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a field group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->create_item_permissions_check( $request );

		/**
		 * Filter the XProfile fields groups `delete_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares single XProfile field group data for return as an object.
	 *
	 * @param BP_XProfile_Group $group XProfile field group data.
	 * @param WP_REST_Request   $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $group, $request ) {
		$data = array(
			'id'               => (int) $group->id,
			'name'             => wp_specialchars_decode( $group->name, ENT_QUOTES ),
			'description'      => array(
				'raw'      => $group->description,
				'rendered' => wp_specialchars_decode( apply_filters( 'bp_get_the_profile_field_description', $group->description ) ),
			),
			'group_order'      => (int) $group->group_order,
			'can_delete'       => (bool) $group->can_delete,
			'repeater_enabled' => false,
		);

		// Check for repeater set is enable of not.
		$repeater_enabled = bp_xprofile_get_meta( $group->id, 'group', 'is_repeater_enabled', true );
		if ( ! empty( $repeater_enabled ) && 'on' === $repeater_enabled ) {
			$data['repeater_enabled'] = true;
		}

		// If the fields have been requested, we populate them.
		if ( $request['fetch_fields'] ) {
			$data['fields'] = array();

			foreach ( $group->fields as $field ) {

				/**
				 * Added support for display name format support from platform.
				 */
				if ( function_exists( 'bp_core_hide_display_name_field' ) && true === bp_core_hide_display_name_field( $field->id ) ) {
					continue;
				}

				if ( function_exists( 'bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
					if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() === $field->id ) {
						continue;
					}
				}
				/**
				 * --Added support for display name format support from platform.
				 */

				$data['fields'][] = $this->fields_endpoint->assemble_response_data( $field, $request );
			}
		}

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $group ) );

		/**
		 * Filter the XProfile field group returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request Request used to generate the response.
		 * @param BP_XProfile_Group $group XProfile field group.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_prepare_value', $response, $request, $group );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_XProfile_Group $group XProfile field group.
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
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array $links The prepared links of the REST response.
		 * @param BP_XProfile_Group $group XProfile field group object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_prepare_links', $links, $group );
	}

	/**
	 * Get XProfile field group object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return BP_XProfile_Group|string XProfile field group object.
	 * @since 0.1.0
	 */
	public function get_xprofile_field_group_object( $request ) {
		$profile_group_id = is_numeric( $request ) ? $request : (int) $request['id'];

		$args = array(
			'profile_group_id'       => $profile_group_id,
			'user_id'                => (int) ( isset( $request['user_id'] ) ? $request['user_id'] : 0 ),
			'member_type'            => ( isset( $request['member_type'] ) ? $request['member_type'] : false ),
			'hide_empty_fields'      => ( isset( $request['hide_empty_fields'] ) ? $request['hide_empty_fields'] : false ),
			'fetch_fields'           => ( isset( $request['fetch_fields'] ) ? $request['fetch_fields'] : false ),
			'fetch_field_data'       => ( isset( $request['fetch_field_data'] ) ? $request['fetch_field_data'] : false ),
			'fetch_visibility_level' => ( isset( $request['fetch_visibility_level'] ) ? $request['fetch_visibility_level'] : false ),
			'exclude_fields'         => ( isset( $request['exclude_fields'] ) ? $request['exclude_fields'] : false ),
			'update_meta_cache'      => ( isset( $request['update_meta_cache'] ) ? $request['update_meta_cache'] : true ),
		);

		if ( empty( $request['member_type'] ) ) {
			$args['member_type'] = null;
		}

		if ( empty( $request['exclude_fields'] ) ) {
			$args['exclude_fields'] = false;
		}

		$field_group = current( bp_xprofile_get_groups( $args ) );

		if ( empty( $field_group->id ) ) {
			return '';
		}

		return $field_group;
	}

	/**
	 * Get the XProfile field group schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_xprofile_group',
			'type'       => 'object',
			'properties' => array(
				'id'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the group of profile fields.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'name'             => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The name of group of profile fields.', 'buddyboss' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The description of the group of profile fields.', 'buddyboss' ),
					'type'        => 'object',
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null,
						// Note: validation implemented in self::prepare_item_for_database().
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the group of profile fields, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the group of profile fields, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'group_order'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The order of the group of profile fields.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'can_delete'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the group of profile fields can be deleted or not.', 'buddyboss' ),
					'type'        => 'boolean',
				),
				'repeater_enabled' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the group of profile fields can be repeated or not.', 'buddyboss' ),
					'type'        => 'boolean',
				),
				'fields'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The fields associated with this group of profile fields.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the xprofile field group schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_xprofile_field_group_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for XProfile field groups.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['profile_group_id'] = array(
			'description'       => __( 'ID of the field group that have fields.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['hide_empty_groups'] = array(
			'description'       => __( 'Whether to hide profile groups of fields that do not have any profile fields or not.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Required if you want to load a specific user\'s data.', 'buddyboss' ),
			'default'           => bp_loggedin_user_id(),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['member_type'] = array(
			'description'       => __( 'Limit fields by those restricted to a given member type, or array of member types. If `$user_id` is provided, the value of `$member_type` will be overridden by the member types of the provided user. The special value of \'any\' will return only those fields that are unrestricted by member type - i.e., those applicable to any type.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'sanitize_callback' => 'bp_rest_sanitize_member_types',
			'validate_callback' => 'bp_rest_validate_member_types',
		);

		$params['hide_empty_fields'] = array(
			'description'       => __( 'Whether to hide profile groups of fields that do not have any profile fields or not.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_fields'] = array(
			'description'       => __( 'Whether to fetch the fields for each group.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_field_data'] = array(
			'description'       => __( 'Whether to fetch data for each field. Requires a $user_id.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_visibility_level'] = array(
			'description'       => __( 'Whether to fetch the visibility level for each field.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_groups'] = array(
			'description'       => __( 'Ensure result set excludes specific profile field groups.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude_fields'] = array(
			'description'       => __( 'Ensure result set excludes specific profile fields.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'string' ),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['update_meta_cache'] = array(
			'description'       => __( 'Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.', 'buddyboss' ),
			'default'           => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_xprofile_field_groups_collection_params', $params );
	}
}
