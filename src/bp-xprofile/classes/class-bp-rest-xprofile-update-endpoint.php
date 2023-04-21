<?php
/**
 * BP REST: BP_REST_XProfile_Update_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile Update endpoints.
 *
 * Use /xprofile/update
 *
 * @since 0.1.0
 */
class BP_REST_XProfile_Update_Endpoint extends WP_REST_Controller {

	/**
	 * XProfile Fields Class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_XProfile_Field_Groups_Endpoint
	 */
	protected $group_fields_endpoint;

	/**
	 * XProfile Data Class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_XProfile_Data_Endpoint
	 */
	protected $xprofile_data_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace              = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base              = buddypress()->profile->id . '/update';
		$this->group_fields_endpoint  = new BP_REST_XProfile_Field_Groups_Endpoint();
		$this->xprofile_data_endpoint = new BP_REST_XProfile_Data_Endpoint();
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
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'update_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

	}

	/**
	 * Update XProfile.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/xprofile/update Update xProfile
	 * @apiName        UpdateBBxProfile
	 * @apiGroup       Profile Fields
	 * @apiDescription Update xProfile for user.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} fields Fields array with field_id, group_id, type, value and visibility_level to update the data.
	 * @apiParam {Number} [profile_group_id] ID of the field group that have fields.
	 * @apiParam {Boolean} [hide_empty_groups=false] Whether to hide profile groups of fields that do not have any profile fields or not.
	 * @apiParam {string} [member_type] Limit fields by those restricted to a given member type, or array of member types.
	 * @apiParam {Boolean} [hide_empty_fields=false] Whether to hide profile fields where the user has not provided data or not.
	 * @apiParam {Boolean} [fetch_fields=true] Whether to fetch the fields for each group.
	 * @apiParam {Boolean} [fetch_field_data=true] Whether to fetch data for each field. Requires a $user_id.
	 * @apiParam {Boolean} [fetch_visibility_level=true] Whether to fetch the visibility level for each field.
	 * @apiParam {Array} [exclude_groups] Ensure result set excludes specific profile field groups.
	 * @apiParam {Array} [exclude_fields] Ensure result set excludes specific profile fields.
	 * @apiParam {Boolean} [update_meta_cache=true] Whether to pre-fetch xprofilemeta for all retrieved groups, fields, and data.
	 */
	public function update_items( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$user_id = bp_loggedin_user_id();

		$fields    = $request->get_param( 'fields' );
		$field_ids = array();

		$errors     = array();
		$old_values = array();
		$new_values = array();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $k => $field_post ) {

				$group_id         = ( isset( $field_post['group_id'] ) && ! empty( $field_post['group_id'] ) ) ? $field_post['group_id'] : '';
				$field_id         = ( isset( $field_post['field_id'] ) && ! empty( $field_post['field_id'] ) ) ? $field_post['field_id'] : '';
				$visibility_level = ( isset( $field_post['visibility_level'] ) && ! empty( $field_post['visibility_level'] ) ) ? $field_post['visibility_level'] : '';
				$value            = ( isset( $field_post['value'] ) && ! empty( $field_post['value'] ) ) ? $field_post['value'] : '';

				if ( empty( $field_id ) ) {
					continue;
				}

				$field_ids[] = $field_id;

				$field = xprofile_get_field( $field_id );

				$old_values[ $field_id ] = array(
					'value'      => xprofile_get_field_data( $field_id, $user_id ),
					'visibility' => xprofile_get_field_visibility_level( $field_id, $user_id ),
				);

				if ( isset( $field_post['value'] ) ) {
					if ( 'checkbox' === $field->type || 'multiselectbox' === $field->type ) {
						if ( is_serialized( $value ) ) {
							$value = maybe_unserialize( $value );
						}

						$value = json_decode( wp_json_encode( $value ), true );

						if ( ! is_array( $value ) ) {
							$value = (array) $value;
							$value = array_filter( $value );
						}
					}

					// Format social network value.
					if ( 'socialnetworks' === $field->type ) {
						if ( is_serialized( $value ) ) {
							$value = maybe_unserialize( $value );
						}

						$value = $this->xprofile_data_endpoint->bb_rest_format_social_network_value( $value );
					}

					$validation = $this->validate_update( $field_id, $user_id, $value );

					if ( empty( $validation ) ) {
						xprofile_set_field_data( $field_id, $user_id, $value, $field->is_required );
					} else {
						$errors[ $field_id ] = $validation;
					}
				}

				if ( ! empty( $visibility_level ) ) {
					xprofile_set_field_visibility_level( $field_id, $user_id, $visibility_level );
				}

				$new_values[ $field_id ] = array(
					'value'      => xprofile_get_field_data( $field_id, $user_id ),
					'visibility' => xprofile_get_field_visibility_level( $field_id, $user_id ),
				);
			}

			/**
			 * Fires after all XProfile fields have been saved for the current profile.
			 *
			 * @since BuddyPress 1.0.0
			 * @since BuddyPress 2.6.0 Added $old_values and $new_values parameters.
			 *
			 * @param int   $user_id    ID for the user whose profile is being saved.
			 * @param array $field_ids  Array of field IDs that were edited.
			 * @param bool  $errors     Whether or not any errors occurred.
			 * @param array $old_values Array of original values before update.
			 * @param array $new_values Array of newly saved values after update.
			 */
			do_action( 'xprofile_updated_profile', $user_id, $field_ids, $errors, $old_values, $new_values );
		}

		$args = array(
			'profile_group_id'       => $request['profile_group_id'],
			'user_id'                => bp_loggedin_user_id(),
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
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_xprofile_fields_get_items_query_args', $args, $request );

		// Actually, query it.
		$field_groups = bp_xprofile_get_groups( $args );

		$response = array();
		if ( empty( $errors ) ) {
			$response['updated'] = true;
		} else {
			$response['updated'] = $errors;
		}

		$retval = array();
		foreach ( (array) $field_groups as $item ) {
			$retval[] = $this->group_fields_endpoint->prepare_response_for_collection(
				$this->group_fields_endpoint->prepare_item_for_response( $item, $request )
			);
		}

		$response['data'] = $retval;

		$response = rest_ensure_response( $response );

		/**
		 * Fires after a XProfile update is created via the REST API.
		 *
		 * @param BP_XProfile_Field $field    Created field object.
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_update_items', $field_groups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a XProfile update.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function update_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to update your profile fields.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval  = true;
			$user_id = bp_loggedin_user_id();

			if ( empty( $user_id ) ) {
				$retval = new WP_Error(
					'bp_rest_login_required',
					__( 'Sorry, you are not logged in to update fields.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the XProfile updates `update_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_update_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the XProfile update schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_xprofile_update',
			'type'       => 'object',
			'properties' => array(
				'updated' => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Whether fields updated or giving an error.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'data'    => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Object of groups.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => $this->group_fields_endpoint->get_item_schema()['properties'],
				),
			),
		);

		/**
		 * Filters the xprofile field group schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_xprofile_update_schema', $schema );
	}

	/**
	 * Get the query params for the XProfile updates.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'edit';

		$params['fields'] = array(
			'description' => __( 'Fields array with field_id, group_id, type, value and visibility_level to update the data.', 'buddyboss' ),
			'type'        => 'array',
			'items'       => array( 'type' => 'object' ),
			'default'     => array(
				array(
					'field_id'         => '',
					'group_id'         => '',
					'value'            => '',
					'visibility_level' => '',
				),
			),
			'required'    => true,
			'properties'  => array(
				'field_id'         => array(
					'description'       => __( 'The ID of the field the data is from.', 'buddyboss' ),
					'type'              => 'integer',
					'required'          => true,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'group_id'         => array(
					'description'       => __( 'ID of the profile group of fields that have profile fields', 'buddyboss' ),
					'default'           => 0,
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'value'            => array(
					'description' => __( 'The value of the field data.', 'buddyboss' ),
				),
				'visibility_level' => array(
					'description' => __( 'Who may see the saved value for this profile field.', 'buddyboss' ),
					'default'     => 'public',
					'type'        => 'string',
					'enum'        => array_keys( bp_xprofile_get_visibility_levels() ),
				),
			),
		);

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
			'default'           => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_field_data'] = array(
			'description'       => __( 'Whether to fetch data for each field. Requires a $user_id.', 'buddyboss' ),
			'default'           => true,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['fetch_visibility_level'] = array(
			'description'       => __( 'Whether to fetch the visibility level for each field.', 'buddyboss' ),
			'default'           => true,
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
		return apply_filters( 'bp_rest_xprofile_update_collection_params', $params );
	}

	/**
	 * Validate field update for the user with value.
	 *
	 * @param int   $field_id The ID of the field, or the $name of the field.
	 * @param int   $user_id  Displayed user ID.
	 * @param mixed $value    The value for the field you want to set for the user.
	 *
	 * @return array
	 */
	public function validate_update( $field_id, $user_id, $value ) {
		$is_required = xprofile_check_is_required_field( $field_id );
		$field       = new BP_XProfile_Field( $field_id );
		if ( 'membertypes' === $field->type ) {

			$member_type_name = bp_get_member_type_key( $value );

			// Get selected profile type role.
			$selected_member_type_wp_roles = get_post_meta( $value, '_bp_member_type_wp_roles', true );

			if ( bp_current_user_can( 'administrator' ) ) {
				if ( empty( $selected_member_type_wp_roles ) || ( isset( $selected_member_type_wp_roles[0] ) && 'none' === $selected_member_type_wp_roles[0] ) ) {
					bp_set_member_type( $user_id, '' );
					bp_set_member_type( $user_id, $member_type_name );

					// Bypass profile type required error for admin.
					$errors      = false;
					$is_required = false;
				} elseif ( 'administrator' !== $selected_member_type_wp_roles[0] ) {
					$errors                  = true;
					$bp_error_message_string = __( 'Changing this profile type would remove your Administrator role and lock you out of the WordPress admin.', 'buddyboss' );
					$validations['field_id'] = $field_id;
					$validations['message']  = $bp_error_message_string;
				}
			} elseif ( bp_current_user_can( 'editor' ) ) {
				if ( empty( $selected_member_type_wp_roles ) || ( isset( $selected_member_type_wp_roles[0] ) && 'none' === $selected_member_type_wp_roles[0] ) ) {
					bp_set_member_type( $user_id, '' );
					bp_set_member_type( $user_id, $member_type_name );

					// Bypass profile type required error for editor.
					$errors      = false;
					$is_required = false;
				} elseif ( ! in_array( $selected_member_type_wp_roles[0], array( 'editor', 'administrator' ), true ) ) {
					$errors                  = true;
					$bp_error_message_string = __( 'Changing this profile type would remove your Editor role and lock you out of the WordPress admin.', 'buddyboss' );
					$validations['field_id'] = $field_id;
					$validations['message']  = $bp_error_message_string;
				}
			} else {
				bp_set_member_type( $user_id, '' );
				bp_set_member_type( $user_id, $member_type_name );

				if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
					$bp_current_user = new WP_User( $user_id );

					foreach ( $bp_current_user->roles as $role ) {
						// Remove role.
						$bp_current_user->remove_role( $role );
					}

					// Add role.
					$bp_current_user->add_role( $selected_member_type_wp_roles[0] );
				}
			}
		} elseif ( $is_required && empty( $value ) ) {
			$errors                  = true;
			$validations['field_id'] = $field_id;
			/* translators: Field name */
			$validations['message'] = sprintf( __( '%s is required and not allowed to be empty.', 'buddyboss' ), $field->name );
		}

		if (
			isset( $value )
			&& function_exists( 'xprofile_validate_field' )
			&& xprofile_validate_field( $field_id, $value, $user_id )
		) {
			$errors                  = true;
			$validations['field_id'] = $field_id;
			$validations['message']  = xprofile_validate_field( $field_id, $value, $user_id );
		}

		if ( ! empty( $errors ) && $validations ) {
			return $validations;
		}
	}

}
