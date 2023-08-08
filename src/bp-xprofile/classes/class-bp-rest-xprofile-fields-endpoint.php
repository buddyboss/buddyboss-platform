<?php
/**
 * BP REST: BP_REST_XProfile_Fields_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile Fields endpoints.
 *
 * Use /xprofile/fields
 * Use /xprofile/fields/{id}
 *
 * @since 0.1.0
 */
class BP_REST_XProfile_Fields_Endpoint extends WP_REST_Controller {

	/**
	 * Current Users ID.
	 *
	 * @var integer Member ID.
	 */
	protected $user_id;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->profile->id . '/fields';

		// Support for Blog comments.
		// EX: /wp-json/wp/v2/comments.
		add_filter( 'rest_prepare_comment', array( $this, 'bp_rest_prepare_comment' ), 10, 2 );
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
						'description' => __( 'A unique numeric ID for the profile field.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'user_id'          => array(
							'description'       => __( 'Required if you want to load a specific user\'s data.', 'buddyboss' ),
							'default'           => 0,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'fetch_field_data' => array(
							'description'       => __( 'Whether to fetch data for the field. Requires a $user_id.', 'buddyboss' ),
							'default'           => true,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
							'validate_callback' => 'rest_validate_request_arg',
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
					'args'                => array(
						'delete_data' => array(
							'description'       => __( 'Required if you want to delete users data for the field.', 'buddyboss' ),
							'default'           => false,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve XProfile fields.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/xprofile/fields Get xProfile Fields
	 * @apiName        GetBBxProfileFields
	 * @apiGroup       Profile Fields
	 * @apiDescription Retrieve Multiple xProfile Fields
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [profile_group_id] ID of the profile group of fields that have profile fields
	 * @apiParam {Boolean} [hide_empty_groups=false] Whether to hide profile groups of fields that do not have any profile fields or not.
	 * @apiParam {Number} [user_id=1] Required if you want to load a specific user's data.
	 * @apiParam {string} [member_type] Limit fields by those restricted to a given member type, or array of member types.
	 * @apiParam {Boolean} [hide_empty_fields=false] Whether to hide profile fields where the user has not provided data or not.
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
			'fetch_field_data'       => $request['fetch_field_data'],
			'fetch_visibility_level' => $request['fetch_visibility_level'],
			'exclude_groups'         => $request['exclude_groups'],
			'exclude_fields'         => $request['exclude_fields'],
			'update_meta_cache'      => $request['update_meta_cache'],
			'fetch_fields'           => true,
		);

		if ( empty( $request['member_type'] ) ) {
			$args['member_type'] = false;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_xprofile_fields_get_items_query_args', $args, $request );

		// Actually, query it.
		$field_groups = bp_xprofile_get_groups( $args );

		$retval = array();
		foreach ( $field_groups as $group ) {
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

				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $field, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of field are fetched via the REST API.
		 *
		 * @param array $field_groups Fetched field groups.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_fields_get_items', $field_groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to XProfile fields.
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
		 * Filter the XProfile fields `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_fields_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve single XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/xprofile/fields/:field_id Get xProfile Field
	 * @apiName        GetBBxProfileField
	 * @apiGroup       Profile Fields
	 * @apiDescription Retrieve xProfile single Field
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the profile field.
	 * @apiParam {Number} [user_id=0] Required if you want to load a specific user's data.
	 * @apiParam {Boolean} [fetch_field_data] Whether to fetch data for the field. Requires a $user_id.
	 */
	public function get_item( $request ) {
		$field = $this->get_xprofile_field_object( $request );

		if ( empty( $field->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! empty( $request->get_param( 'user_id' ) ) ) {
			$field->data = new stdClass();

			// Ensure that the requester is allowed to see this field.
			$hidden_user_fields = bp_xprofile_get_hidden_fields_for_user( $request->get_param( 'user_id' ) );

			if ( in_array( $field->id, $hidden_user_fields, true ) ) {
				$field->data->value = __( 'Value suppressed.', 'buddyboss' );
			} else {
				// Get the raw value for the field.
				$field->data->value = BP_XProfile_ProfileData::get_value_byid( $field->id, $request->get_param( 'user_id' ) );
			}
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $field, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after XProfile field is fetched via the REST API.
		 *
		 * @param BP_XProfile_Field $field    Fetched field object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_fields_get_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool
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

		$field = $this->get_xprofile_field_object( $request );
		if ( true === $retval && empty( $field->id ) ) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval ) {
			$retval = $this->get_xprofile_field_display_permission( $retval, $field->id );
		}

		/**
		 * Filter the XProfile fields `get_item` permissions check.
		 *
		 * @param bool $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_fields_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Set additional field properties.
	 *
	 * @param integer         $field_id The profile field object ID.
	 * @param WP_REST_Request $request The request sent to the API.
	 *
	 * @since 0.1.0
	 */
	public function set_additional_field_properties( $field_id = 0, WP_REST_Request $request = null ) {
		if ( ! $field_id ) {
			return;
		}

		// Get the edit schema.
		$schema = $this->get_endpoint_args_for_item_schema( $request->get_method() );

		// Define default visibility property.
		if ( isset( $schema['default_visibility'] ) ) {
			$default_visibility = $schema['default_visibility']['default'];

			if ( $request['default_visibility'] ) {
				$default_visibility = $request['default_visibility'];
			}

			// Save the default visibility.
			bp_xprofile_update_field_meta( $field_id, 'default_visibility', $default_visibility );
		}

		// Define allow custom visibility property.
		if ( isset( $schema['allow_custom_visibility'] ) ) {
			$allow_custom_visibility = $schema['allow_custom_visibility']['default'];

			if ( $request['allow_custom_visibility'] ) {
				$allow_custom_visibility = $request['allow_custom_visibility'];
			}

			// Save the default visibility.
			bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $allow_custom_visibility );
		}

		// Define autolink property.
		if ( isset( $schema['do_autolink'] ) ) {
			$do_autolink = $schema['do_autolink']['default'];

			if ( $request['do_autolink'] ) {
				$do_autolink = $request['do_autolink'];
			}

			// Save the default visibility.
			bp_xprofile_update_field_meta( $field_id, 'do_autolink', $do_autolink );
		}

		// Define alternate title property.
		if ( isset( $schema['alternate_name'] ) ) {
			$alternate_name = ( ! empty( $schema['alternate_name']['default'] ) ? $schema['alternate_name']['default'] : '' );

			if ( $request['alternate_name'] ) {
				$alternate_name = $request['alternate_name'];
			}

			// Save the alternate name.
			bp_xprofile_update_field_meta( $field_id, 'alternate_name', $alternate_name );
		}
	}

	/**
	 * Create a XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/xprofile/fields Create xProfile Field
	 * @apiName        CreateBBxProfileField
	 * @apiGroup       Profile Fields
	 * @apiDescription Create xProfile Field.
	 * @apiVersion     1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} group_id The ID of the group the field is part of.
	 * @apiParam {Number} [parent_id] The ID of the parent field.
	 * @apiParam {string} type The type for the profile field.
	 * @apiParam {string} name The name of the profile field.
	 * @apiParam {string} [alternate_name] The alternate name of the profile field.
	 * @apiParam {string} [description] The description of the profile field.
	 * @apiParam {Boolean} [is_required] Whether the profile field must have a value.
	 * @apiParam {Boolean=true,false} [can_delete=true] Whether the profile field can be deleted or not.
	 * @apiParam {Number} [field_order] The order of the profile field into the group of fields.
	 * @apiParam {Number} [option_order] The order of the option into the profile field list of options.
	 * @apiParam {string=asc,desc} [order_by=asc] The way profile field's options are ordered.
	 * @apiParam {Boolean} [is_default_option] Whether the option is the default one for the profile field.
	 * @apiParam {string=public,adminsonly,loggedin,friends} [default_visibility=public] Default visibility for the profile field.
	 * @apiParam {string=allowed,disabled} [allow_custom_visibility=allowed] Whether to allow members to set the visibility for the profile field data or not.
	 * @apiParam {string=on,off} [do_autolink=off] Autolink status for this profile field.
	 */
	public function create_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$args = array(
			'field_group_id'    => $request['group_id'],
			'parent_id'         => $request['parent_id'],
			'type'              => $request['type'],
			'name'              => $request['name'],
			'description'       => $request['description'],
			'is_required'       => $request['required'],
			'can_delete'        => $request['can_delete'],
			'order_by'          => $request['order_by'],
			'is_default_option' => $request['is_default_option'],
			'option_order'      => $request['option_order'],
			'field_order'       => $request['field_order'],
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_xprofile_fields_create_item_query_args', $args, $request );

		$field_id = xprofile_insert_field( $args );
		if ( ! $field_id ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_xprofile_field',
				__( 'Cannot create new XProfile field.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Define visibility and autolink field properties.
		$this->set_additional_field_properties( $field_id, $request );

		$field = $this->get_xprofile_field_object( $field_id );

		// Create Additional fields.
		$fields_update = $this->update_additional_fields_for_object( $field, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $field, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a XProfile field is created via the REST API.
		 *
		 * @param BP_XProfile_Field $field Created field object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_fields_create_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a XProfile field.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the XProfile fields `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_fields_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/xprofile/fields/:field_id Update xProfile Field
	 * @apiName        UpdateBBxProfileField
	 * @apiGroup       Profile Fields
	 * @apiDescription Update xProfile Field.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the profile field.
	 * @apiParam {Number} [group_id] The ID of the group the field is part of.
	 * @apiParam {Number} [parent_id] The ID of the parent field.
	 * @apiParam {string} [type] The type for the profile field.
	 * @apiParam {string} [name] The name of the profile field.
	 * @apiParam {string} [alternate_name] The alternate name of the profile field.
	 * @apiParam {string} [description] The description of the profile field.
	 * @apiParam {Boolean} [is_required] Whether the profile field must have a value.
	 * @apiParam {Boolean=true,false} [can_delete=true] Whether the profile field can be deleted or not.
	 * @apiParam {Number} [field_order] The order of the profile field into the group of fields.
	 * @apiParam {Number} [option_order] The order of the option into the profile field list of options.
	 * @apiParam {string=asc,desc} [order_by=asc] The way profile field's options are ordered.
	 * @apiParam {Boolean} [is_default_option] Whether the option is the default one for the profile field.
	 * @apiParam {string=public,adminsonly,loggedin,friends} [default_visibility=public] Default visibility for the profile field.
	 * @apiParam {string=allowed,disabled} [allow_custom_visibility=allowed] Whether to allow members to set the visibility for the profile field data or not.
	 * @apiParam {string=on,off} [do_autolink=off] Autolink status for this profile field.
	 */
	public function update_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$field = $this->get_xprofile_field_object( $request );

		if ( empty( $field->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid profile field ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$args = array(
			'field_id'          => $field->id,
			'field_group_id'    => is_null( $request['group_id'] ) ? $field->group_id : $request['group_id'],
			'parent_id'         => is_null( $request['parent_id'] ) ? $field->parent_id : $request['parent_id'],
			'type'              => is_null( $request['type'] ) ? $field->type : $request['type'],
			'name'              => is_null( $request['name'] ) ? $field->name : $request['name'],
			'description'       => is_null( $request['description'] ) ? $field->description : $request['description'],
			'is_required'       => is_null( $request['required'] ) ? $field->is_required : $request['required'],
			'can_delete'        => $request['can_delete'], // Set to true by default.
			'order_by'          => is_null( $request['order_by'] ) ? $field->order_by : $request['order_by'],
			'is_default_option' => is_null( $request['is_default_option'] ) ? $field->is_default_option : $request['is_default_option'],
			'option_order'      => is_null( $request['option_order'] ) ? $field->option_order : $request['option_order'],
			'field_order'       => is_null( $request['field_order'] ) ? $field->field_order : $request['field_order'],
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_xprofile_fields_update_item_query_args', $args, $request );

		// Specific check to make sure the Full Name xprofile field will remain undeletable.
		if ( bp_xprofile_fullname_field_id() === $field->id ) {
			$args['can_delete'] = false;
		}

		$field_id = xprofile_insert_field( $args );
		if ( ! $field_id ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_xprofile_field',
				__( 'Cannot update XProfile field.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Define visibility and autolink field properties.
		$this->set_additional_field_properties( $field_id, $request );

		$field = $this->get_xprofile_field_object( $field_id );

		// Update Additional fields.
		$fields_update = $this->update_additional_fields_for_object( $field, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $field, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a XProfile field is updated via the REST API.
		 *
		 * @param BP_XProfile_Field $field Updated field object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_fields_update_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->delete_item_permissions_check( $request );

		/**
		 * Filter the XProfile fields `update_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_fields_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/xprofile/fields/:field_id Delete xProfile Field
	 * @apiName        DeleteBBxProfileField
	 * @apiGroup       Profile Fields
	 * @apiDescription Delete xProfile Field.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the profile field.
	 * @apiParam {Boolean} [delete_data=false] Required if you want to delete users data for the field.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the field before it's deleted.
		$field    = new BP_XProfile_Field( (int) $request->get_param( 'id' ) );
		$previous = $this->prepare_item_for_response( $field, $request );

		if ( ! $field->delete( $request['delete_data'] ) ) {
			return new WP_Error(
				'bp_rest_xprofile_field_cannot_delete',
				__( 'Could not delete XProfile field.', 'buddyboss' ),
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
		 * Fires after a XProfile field is deleted via the REST API.
		 *
		 * @param BP_XProfile_Field $field Deleted field object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_fields_delete_item', $field, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a XProfile field.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete this field.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$field  = $this->get_xprofile_field_object( $request );

			if ( empty( $field->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid field ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( true === $retval && ! bp_current_user_can( 'bp_moderate' ) ) {
				$retval = $this->get_xprofile_field_display_permission( $retval, $field->id );
			}

			if ( true === $retval && ! bp_current_user_can( 'bp_moderate' ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this field.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the XProfile fields `delete_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_fields_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares single XProfile field data to return as an object.
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 * @param WP_REST_Request   $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $field, $request ) {
		$response = rest_ensure_response(
			$this->assemble_response_data( $field, $request )
		);
		$response->add_links( $this->prepare_links( $field ) );

		/**
		 * Filter the XProfile field returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request Request used to generate the response.
		 * @param BP_XProfile_Field $field XProfile field object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_fields_prepare_value', $response, $request, $field );
	}

	/**
	 * Assembles single XProfile field data for return as an object.
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 * @param WP_REST_Request   $request Full data about the request.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function assemble_response_data( $field, $request ) {
		$data = array(
			'id'                => (int) $field->id,
			'group_id'          => (int) $field->group_id,
			'parent_id'         => (int) $field->parent_id,
			'type'              => $field->type,
			'name'              => wp_specialchars_decode( $field->name ),
			'alternate_name'    => '',
			'description'       => array(
				'raw'      => $field->description,
				'rendered' => apply_filters( 'bp_get_the_profile_field_description', $field->description ),
			),
			'is_required'       => (bool) $field->is_required,
			'can_delete'        => (bool) $field->can_delete,
			'field_order'       => (int) $field->field_order,
			'option_order'      => (int) $field->option_order,
			'order_by'          => $field->order_by,
			'is_default_option' => (bool) $field->is_default_option,
			'options'           => array(),
		);

		// When we add paragraph as field for the profiles field then its not displaying proper format.
		$current_user_id = $request->get_param( 'user_id' );
		if ( empty( $current_user_id ) ) {
			$current_user_id = bp_loggedin_user_id();
		}

		$this->user_id = $current_user_id;

		if ( ! empty( $request['fetch_visibility_level'] ) ) {
			$data['visibility_level']        = $field->visibility_level;
			$data['allow_custom_visibility'] = $this->bp_rest_get_field_visibility( $field );
		}

		if ( true === wp_validate_boolean( $request->get_param( 'fetch_field_data' ) ) ) {
			if ( isset( $field->data->id ) ) {
				$data['data']['id'] = $field->data->id;
			}

			$field_value = isset( $field->data->value ) ? $field->data->value : '';

			$data['data']['value'] = array(
				'raw'          => $this->get_profile_field_raw_value( $field_value, $field ),
				'unserialized' => $this->get_profile_field_unserialized_value( $field_value, $field ),
				'rendered'     => $this->get_profile_field_rendered_value( $field_value, $field ),
			);
		}

		// Added settings for date field.
		if ( 'datebox' === $field->type ) {
			$datebox_field    = new BP_XProfile_Field_Type_Datebox();
			$data['settings'] = $datebox_field::get_field_settings( $field->id );
		}

		// Added settings and format options for phone field.
		if ( 'telephone' === $field->type ) {
			$telephone_field                    = new BP_XProfile_Field_Type_Telephone();
			$data['settings']                   = $telephone_field->get_field_settings( $field );
			$data['settings']['format_options'] = $telephone_field->get_phone_formats();
		}

		// Added options for membertype field.
		if ( 'membertypes' === $field->type && function_exists( 'bp_check_member_type_field_have_options' ) && true === bp_check_member_type_field_have_options() ) {
			$data['options'] = $this->get_member_type_options( $field, $request );
		}

		// Added options for membertype field.
		if ( 'socialnetworks' === $field->type ) {
			$data['options'] = $this->get_socialnetworks_type_options( $field, $request );
		}

		// Added options for selectbox, multiselectbox, radio and checkbox fields.
		if ( 'selectbox' === $field->type || 'multiselectbox' === $field->type || 'radio' === $field->type || 'checkbox' === $field->type ) {
			add_filter( 'bp_xprofile_field_get_children', array( $this, 'bb_rest_xprofile_field_get_children' ), 20, 1 );
			$data['options'] = $field->get_children();
			remove_filter( 'bp_xprofile_field_get_children', array( $this, 'bb_rest_xprofile_field_get_children' ), 20, 1 );
		}

		if ( 'gender' === $field->type ) {
			$data['options'] = $this->get_gender_type_options( $field, $request );
		}

		// Added options for date field.
		if ( 'datebox' === $field->type ) {
			if ( empty( $data['options'] ) ) {
				$data['options'] = array();
			}
			$data['options']['day']   = $this->get_date_field_options_array( $field, 'day' );
			$data['options']['month'] = $this->get_date_field_options_array( $field, 'month' );
			$data['options']['year']  = $this->get_date_field_options_array( $field, 'year' );
		}

		$is_repeater_enabled = 'on' === bp_xprofile_get_meta( $field->group_id, 'group', 'is_repeater_enabled', true ) ? true : false;
		if ( $is_repeater_enabled ) {
			$data['repeater_data'] = $this->get_repeater_fields_data( $field, $request );
		}

		// Get alternate name for the field.
		$alternate_name = bp_xprofile_get_meta( (int) $field->id, 'field', 'alternate_name' );
		if ( ! empty( $alternate_name ) ) {
			$data['alternate_name'] = wp_specialchars_decode( $alternate_name );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		return $data;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $field ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $field->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array $links The prepared links of the REST response.
		 * @param BP_XProfile_Field $field XProfile field object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_fields_prepare_links', $links, $field );
	}

	/**
	 * Get XProfile field object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return BP_XProfile_Field|string XProfile field object|string.
	 * @since 0.1.0
	 */
	public function get_xprofile_field_object( $request ) {
		if ( is_numeric( $request ) ) {
			$field_id = $request;
			$user_id  = null;
		} else {
			$field_id = $request->get_param( 'id' );
			$user_id  = $request->get_param( 'user_id' );
		}

		$field = xprofile_get_field( $field_id, $user_id );

		if ( empty( $field ) ) {
			return '';
		}

		return $field;
	}

	/**
	 * Retrieve the rendered value of a profile field.
	 *
	 * @param string                    $value The raw value of the field.
	 * @param integer|BP_XProfile_Field $profile_field The ID or the full object for the field.
	 *
	 * @return string                                   The field value for the display context.
	 * @since 0.1.0
	 */
	public function get_profile_field_rendered_value( $value = '', $profile_field = null ) {
		if ( empty( $value ) ) {
			return '';
		}

		$profile_field = xprofile_get_field( $profile_field );

		if ( ! isset( $profile_field->id ) ) {
			return '';
		}

		// Unserialize the BuddyPress way.
		$value = bp_unserialize_profile_field( $value );

		global $field;
		$reset_global = $field;

		// Set the $field global as the `xprofile_filter_link_profile_data` filter needs it.
		$field = $profile_field;

		if ( 'membertypes' === $profile_field->type ) {
			// Need to pass $profile_field as object.
			$all_member_type = $this->get_member_type_options( $profile_field, array( 'show_all' => true ) );
			if ( ! empty( $all_member_type ) ) {
				$all_member_type = array_column( $all_member_type, 'name', 'id' );
			}

			if ( ! empty( $all_member_type ) && array_key_exists( $profile_field->data->value, $all_member_type ) ) {
				$value = $all_member_type[ $profile_field->data->value ];
			}
		}

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		/**
		 * Apply Filters to sanitize XProfile field value.
		 *
		 * @param string $value Value for the profile field.
		 * @param string $type Type for the profile field.
		 * @param int $id ID for the profile field.
		 *
		 * @since 0.1.0
		 */
		$value = apply_filters( 'bp_get_the_profile_field_value', $value, $field->type, $field->id );

		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		// Reset the global before returning the value.
		$field = $reset_global;

		return wp_specialchars_decode( $value );
	}

	/**
	 * Retrieve the field raw data.
	 *
	 * @since 0.1.0
	 *
	 * @param string                    $value         The raw value of the field.
	 * @param integer|BP_XProfile_Field $profile_field The ID or the full object for the field.
	 *
	 * @return array Field raw data.
	 */
	public function get_profile_field_raw_value( $value = '', $profile_field = null ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( ! empty( $profile_field ) ) {
			$profile_field = xprofile_get_field( $profile_field );

			if ( ! isset( $profile_field->id ) ) {
				return '';
			}

			if ( 'telephone' === $profile_field->type ) {
				$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
			}
		}

		return wp_specialchars_decode( $value );
	}

	/**
	 * Retrieve the unserialized value of a profile field.
	 *
	 * @param string                    $value The raw value of the field.
	 * @param integer|BP_XProfile_Field $profile_field The ID or the full object for the field.
	 *
	 * @return array         The unserialized field value.
	 * @since 0.1.0
	 */
	public function get_profile_field_unserialized_value( $value = '', $profile_field = null ) {
		if ( empty( $value ) ) {
			return array();
		}

		if ( ! empty( $profile_field ) ) {
			$profile_field = xprofile_get_field( $profile_field );

			if ( ! isset( $profile_field->id ) ) {
				return '';
			}

			if ( 'telephone' === $profile_field->type ) {
				$value = wp_strip_all_tags( html_entity_decode( $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
			}
		}

		$unserialized_value = maybe_unserialize( $value );
		if ( ! is_array( $unserialized_value ) ) {
			$unserialized_value = (array) wp_specialchars_decode( $unserialized_value, ENT_QUOTES );
		} elseif ( ! empty( $unserialized_value ) && is_array( $unserialized_value ) ) {
			foreach ( $unserialized_value as $k => $v ) {
				$unserialized_value[ $k ] = wp_specialchars_decode( $v, ENT_QUOTES );
			}
		}

		return $unserialized_value;
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

		if ( WP_REST_Server::READABLE === $method ) {
			// Add specific properties to the view context.
			$args['allow_custom_visibility'] = array(
				'context'     => array( 'view' ),
				'description' => __( 'Whether to allow members to set the visibility for the profile field data or not.', 'buddyboss' ),
				'type'        => 'string',
				'enum'        => array( 'allowed', 'disabled' ),
			);
		}

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$args['description']['type'] = 'string';
			unset( $args['description']['properties'] );

			// Add specific properties to the edit context.
			$edit_args = array();

			// The visibility level chose by the administrator is the default visibility.
			$edit_args['default_visibility']                = $args['visibility_level'];
			$edit_args['default_visibility']['description'] = __( 'Default visibility for the profile field.', 'buddyboss' );

			// Unset the visibility level which can be the user defined visibility.
			unset( $args['visibility_level'] );

			// Add specific properties to the edit context.
			$edit_args['allow_custom_visibility'] = array(
				'context'     => array( 'edit' ),
				'description' => __( 'Whether to allow members to set the visibility for the profile field data or not.', 'buddyboss' ),
				'default'     => 'allowed',
				'type'        => 'string',
				'enum'        => array( 'allowed', 'disabled' ),
			);

			$edit_args['do_autolink'] = array(
				'context'     => array( 'edit' ),
				'description' => __( 'Autolink status for this profile field', 'buddyboss' ),
				'default'     => 'off',
				'type'        => 'string',
				'enum'        => array( 'on', 'off' ),
			);

			// Set required params for the CREATABLE method.
			if ( WP_REST_Server::CREATABLE === $method ) {
				$key                          = 'create_item';
				$args['group_id']['required'] = true;
				$args['type']['required']     = true;
				$args['name']['required']     = true;
			} elseif ( WP_REST_Server::EDITABLE === $method ) {
				$key                                        = 'update_item';
				$args['can_delete']['default']              = true;
				$args['order_by']['default']                = 'asc';
				$edit_args['default_visibility']['default'] = 'public';
			}

			// Merge arguments.
			$args = array_merge( $args, $edit_args );
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
		return apply_filters( "bp_rest_xprofile_fields_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the XProfile field schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_xprofile_field',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the profile field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'group_id'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the group the field is part of.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'parent_id'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the parent field.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'type'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The type for the profile field.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => buddypress()->profile->field_types,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'name'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The name of the profile field.', 'buddyboss' ),
					'type'        => 'string',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'alternate_name'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The alternate name of the profile field.', 'buddyboss' ),
					'type'        => 'string',
					'default'     => '',
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The description of the profile field.', 'buddyboss' ),
					'type'        => 'object',
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null,
						// Note: validation implemented in self::prepare_item_for_database().
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the profile field, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the profile field, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'is_required'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the profile field must have a value.', 'buddyboss' ),
					'type'        => 'boolean',
				),
				'can_delete'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the profile field can be deleted or not.', 'buddyboss' ),
					'default'     => true,
					'type'        => 'boolean',
				),
				'field_order'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The order of the profile field into the group of fields.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'option_order'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The order of the option into the profile field list of options', 'buddyboss' ),
					'type'        => 'integer',
				),
				'order_by'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The way profile field\'s options are ordered.', 'buddyboss' ),
					'default'     => 'asc',
					'type'        => 'string',
					'enum'        => array( 'asc', 'desc' ),
				),
				'is_default_option' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the option is the default one for the profile field.', 'buddyboss' ),
					'type'        => 'boolean',
				),
				'visibility_level'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Who may see the saved value for this profile field.', 'buddyboss' ),
					'default'     => 'public',
					'type'        => 'string',
					'enum'        => array_keys( bp_xprofile_get_visibility_levels() ),
				),
				'options'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Options of the profile field.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'data'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The saved value for this profile field.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => array(
						'raw'          => array(
							'description' => __( 'Value for the field, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'unserialized' => array(
							'description' => __( 'Unserialized value for the field, regular string will be casted as array.', 'buddyboss' ),
							'type'        => 'array',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
						'rendered'     => array(
							'description' => __( 'HTML value for the field, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
		);

		/**
		 * Filters the xprofile field schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_xprofile_field_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for the XProfile fields.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['profile_group_id'] = array(
			'description'       => __( 'ID of the profile group of fields that have profile fields', 'buddyboss' ),
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
			'description'       => __( 'Whether to hide profile fields where the user has not provided data or not.', 'buddyboss' ),
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
		return apply_filters( 'bp_rest_xprofile_fields_collection_params', $params );
	}

	/**
	 * Get Profile field options
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 * @param array             $request request argument.
	 *
	 * @return array
	 */
	public function get_member_type_options( $field, $request ) {
		$posts = new \WP_Query(
			array(
				'posts_per_page' => - 1,
				'post_type'      => bp_get_member_type_post_type(),
				'post_status'    => 'publish',
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$member_type   = bp_get_member_type( ! empty( $request['user_id'] ) ? (int) $request['user_id'] : get_current_user_id() );
		$post_selected = 0;
		if ( '' !== $member_type ) {
			$post_selected = (int) bp_member_type_post_by_type( $member_type );
		}

		$options = array();
		if ( $posts ) {
			foreach ( $posts->posts as $post ) {
				$enabled = get_post_meta( $post->ID, '_bp_member_type_enable_profile_field', true );
				$name    = get_post_meta( $post->ID, '_bp_member_type_label_singular_name', true );
				$key     = get_post_meta( $post->ID, '_bp_member_type_key', true );
				if ( '' === $enabled || '1' === $enabled || $post_selected === $post->ID || ! empty( $request['show_all'] ) ) {
					$options[] = array(
						'id'                => $post->ID,
						'group_id'          => $field->group_id,
						'parent_id'         => $field->id,
						'type'              => 'option',
						'name'              => $name,
						'key'               => $key,
						'description'       => '',
						'is_required'       => '0',
						'is_default_option' => ( $post_selected === $post->ID ),
					);
				}
			}
		}

		return $options;
	}

	/**
	 * Get datebox options
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 * @param string            $type Date type parameter.
	 *
	 * @return array
	 */
	private function get_date_field_options_array( $field, $type = '' ) {
		$eng_months = array(
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
		);

		$options = array();

		// $type will be passed by calling function when needed.
		switch ( $type ) {
			case 'day':
				for ( $i = 1; $i < 32; ++ $i ) {
					$options[] = array(
						'type' => 'option',
						'name' => $i,
					);
				}
				break;

			case 'month':
				for ( $i = 0; $i < 12; ++ $i ) {
					$options[] = array(
						'type' => 'option',
						'name' => $eng_months[ $i ],
					);
				}
				break;

			case 'year':
				$settings = BP_XProfile_Field_Type_Datebox::get_field_settings( $field->id );

				if ( 'relative' === $settings['range_type'] ) {
					// phpcs:ignore
					$start = date( 'Y' ) + $settings['range_relative_start'];
					// phpcs:ignore
					$end = date( 'Y' ) + $settings['range_relative_end'];
				} else {
					$start = $settings['range_absolute_start'];
					$end   = $settings['range_absolute_end'];
				}

				for ( $i = $end; $i >= $start; $i -- ) {
					$options[] = array(
						'type' => 'option',
						'name' => $i,
					);
				}
				break;
		}

		return $options;
	}

	/**
	 * Get Social Network field options
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 * @param array             $request request argument.
	 *
	 * @return array
	 */
	public function get_socialnetworks_type_options( $field, $request ) {
		// Does option have children?
		$options = $field->get_children();

		if ( empty( $options ) ) {
			$default_options = apply_filters(
				'social_network_default_options',
				array(
					'facebook',
					'twitter',
					'linkedIn',
				)
			);
			$all_options     = bp_xprofile_social_network_provider();
			$options         = array();
			if ( empty( $default_options ) ) {
				$options = bp_xprofile_social_network_provider();
			} else {
				foreach ( $all_options as $opt ) {
					if ( in_array( $opt->value, $default_options, true ) ) {
						$options[] = $opt;
					}
				}
			}
		}

		$providers = bp_xprofile_social_network_provider();
		if ( ! empty( $options ) ) {
			foreach ( $options as $k => $option ) {
				$option->value = $option->name;
				$key           = bp_social_network_search_key( $option->name, $providers );
				$option->name  = $providers[ $key ]->name;
				$option->icon  = $providers[ $key ]->svg;
				$options[ $k ] = $option;
			}
		}

		return $options;
	}

	/**
	 * Get Gender field options
	 *
	 * @param BP_XProfile_Field $field XProfile field object.
	 * @param array             $request request argument.
	 *
	 * @return array
	 */
	public function get_gender_type_options( $field, $request ) {

		$options = $field->get_children();

		if ( isset( $field->id ) && ! empty( $field->id ) ) {
			$order = bp_xprofile_get_meta( $field->id, 'field', 'gender-option-order' );
		} else {
			$order = array();
		}

		for ( $k = 0, $count = count( $options ); $k < $count; ++ $k ) {
			if ( ! empty( $order ) ) {
				$key = $order[ $k ];

				if ( 'male' === $key ) {
					$options[ $k ]->value = 'his_' . $options[ $k ]->name;
				} elseif ( 'female' === $key ) {
					$options[ $k ]->value = 'her_' . $options[ $k ]->name;
				} else {
					$options[ $k ]->value = 'their_' . $options[ $k ]->name;
				}
			} else {
				if ( '1' === $options[ $k ]->option_order ) {
					$options[ $k ]->value = 'his_' . $options[ $k ]->name;
				} elseif ( '2' === $options[ $k ]->option_order ) {
					$options[ $k ]->value = 'her_' . $options[ $k ]->name;
				} else {
					$options[ $k ]->value = 'their_' . $options[ $k ]->name;
				}
			}
		}

		return $options;
	}

	/**
	 * Get Repeater field data.
	 *
	 * @param BP_XProfile_Field $field Field Object.
	 * @param WP_REST_Request   $request Full data about the request.
	 *
	 * @return array|void
	 */
	public function get_repeater_fields_data( $field, $request ) {
		global $bp, $wpdb;

		if ( empty( $field ) ) {
			return;
		}

		$user_id = ( $request['user_id'] ) ? $request['user_id'] : bp_loggedin_user_id();

		if ( empty( $user_id ) ) {
			return;
		}

		$field_id = $field->id;

		$clone_field_ids_all = bp_get_repeater_clone_field_ids_all( $field->group_id );
		$exclude_fields_cs   = bp_get_repeater_template_field_ids( $field->group_id );

		// include only the subset of clones the current user has data in.
		$user_field_set_count     = bp_get_profile_field_set_count( $field->group_id, $user_id );
		$clone_field_ids_has_data = bp_get_repeater_clone_field_ids_subset( $field->group_id, $user_field_set_count );
		$clones_to_exclude        = array_diff( $clone_field_ids_all, $clone_field_ids_has_data );

		$exclude_fields_cs = array_merge( $exclude_fields_cs, $clones_to_exclude );
		if ( ! empty( $exclude_fields_cs ) ) {
			$exclude_fields_cs  = implode( ',', $exclude_fields_cs );
			$exclude_fields_sql = "AND  m1.object_id NOT IN ({$exclude_fields_cs})";
		} else {
			$exclude_fields_sql = '';
		}

		// phpcs:ignore
		$sql = "select m1.object_id, CAST(m2.meta_value AS DECIMAL) AS 'clone_number' FROM {$bp->profile->table_name_meta} as m1
        JOIN {$bp->profile->table_name_meta} AS m2 ON m1.object_id = m2.object_id
        WHERE m1.meta_key = '_cloned_from' AND m1.meta_value = %d
        AND m2.meta_key = '_clone_number' {$exclude_fields_sql} ORDER BY m2.object_id, m2.meta_value ASC";

		// phpcs:ignore
		$sql = $wpdb->prepare( $sql, $field_id );
		// phpcs:ignore
		$results = $wpdb->get_results( $sql );
		$user_id = ( ! empty( $request['user_id'] ) ? $request['user_id'] : bp_loggedin_user_id() );
		$data    = array();

		$user_fields = bp_get_profile_field_set_count( $field->group_id, $user_id );

		if ( ! empty( $results ) && ! is_wp_error( $results ) ) {

			$count = 1;

			foreach ( $results as $k => $sub_field ) {

				$sub_field_clone_number = $sub_field->clone_number;
				$sub_field_id           = $sub_field->object_id;

				if ( $count > $user_fields ) {
					break;
				}

				$data[ $k ]['id']           = $sub_field_id;
				$data[ $k ]['clone_number'] = $sub_field_clone_number;

				$field_data = $this->get_xprofile_field_data_object( $sub_field_id, $user_id );

				if ( ! empty( $request['fetch_field_data'] ) ) {
					$data[ $k ]['value'] = array(
						'raw'          => $this->get_profile_field_raw_value( $field_data->value, $sub_field_id ),
						'unserialized' => $this->get_profile_field_unserialized_value( $field_data->value, $sub_field_id ),
						'rendered'     => $this->get_profile_field_rendered_value( $field_data->value, $sub_field_id ),
					);
				}

				if ( ! empty( $request['fetch_visibility_level'] ) ) {
					$data[ $k ]['visibility_level']        = xprofile_get_field_visibility_level( $sub_field_id, $user_id );
					$data[ $k ]['allow_custom_visibility'] = bp_xprofile_get_meta( $sub_field_id, 'field', 'allow_custom_visibility' );
				}

				$count ++;
			}
		}

		return $data;
	}

	/**
	 * Check display setting permission from platform.
	 *
	 * @param boolean $retval   Return value should be boolean or WP_Error.
	 * @param int     $field_id xProfile Field ID to check permission.
	 *
	 * @return WP_Error|Boolean
	 */
	public function get_xprofile_field_display_permission( $retval, $field_id = 0 ) {

		if ( empty( $field_id ) ) {
			return $retval;
		}

		/**
		 * Added support for display name format support from platform.
		 */
		// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
		if (
			(
				function_exists( 'bp_core_hide_display_name_field' )
				&& true === bp_core_hide_display_name_field( $field_id )
			)
			|| (
				function_exists( 'bp_member_type_enable_disable' )
				&& false === bp_member_type_enable_disable()
				&& function_exists( 'bp_get_xprofile_member_type_field_id' )
				&& bp_get_xprofile_member_type_field_id() === $field_id
			)
		) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		return $retval;
	}

	/**
	 * Check current user can edit the visibility or not.
	 *
	 * @param BP_XProfile_Field $field_object Field Object.
	 *
	 * @return string
	 */
	public function bp_rest_get_field_visibility( $field_object ) {
		global $field;

		// Get the field id into for user check.
		$GLOBALS['profile_template']              = new stdClass();
		$GLOBALS['profile_template']->in_the_loop = true;

		// Setup current user id into global.
		$field = $field_object;

		return (
			! bp_current_user_can( 'bp_xprofile_change_field_visibility' )
			? 'disabled'
			: (
				(
					! empty( $field->__get( 'allow_custom_visibility' ) )
					&& 'allowed' === $field->__get( 'allow_custom_visibility' )
				)
				? $field->__get( 'allow_custom_visibility' )
				: 'disabled'
			)
		);
	}

	/**
	 * Get XProfile field data object.
	 *
	 * @param int $field_id Field id.
	 * @param int $user_id User id.
	 *
	 * @return BP_XProfile_ProfileData
	 * @since 0.1.0
	 */
	public function get_xprofile_field_data_object( $field_id, $user_id ) {
		return new BP_XProfile_ProfileData( $field_id, $user_id );
	}

	/**
	 * Set current and display user with current user.
	 *
	 * @param int $user_id The user id.
	 *
	 * @return int
	 */
	public function bp_rest_get_displayed_user( $user_id ) {
		return ( ! empty( $this->user_id ) ? $this->user_id : bp_loggedin_user_id() );
	}

	/**
	 * Filters a comment returned from the REST API.
	 * - From: xprofile_filter_comments()
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Comment       $comment  The original comment object.
	 *
	 * @return WP_REST_Response
	 */
	public function bp_rest_prepare_comment( $response, $comment ) {

		$data = $response->get_data();

		$data['author_name'] = bp_core_get_user_displayname( $comment->user_id );

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Filters the found children for a field.
	 *
	 * @param object $children Found children for a field.
	 *
	 * @return mixed
	 */
	public function bb_rest_xprofile_field_get_children( $children ) {

		if ( empty( $children ) ) {
			return $children;
		}

		foreach ( $children as $k => $option ) {
			if ( ! empty( $option->name ) ) {
				$option->name = stripslashes_deep( $option->name );
			}
		}

		return $children;
	}
}
