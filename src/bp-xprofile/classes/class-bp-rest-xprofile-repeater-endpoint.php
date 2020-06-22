<?php
/**
 * BP REST: BP_REST_XProfile_Repeater_Fields_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile Repeater Fields endpoints.
 *
 * Use /xprofile/repeater
 *
 * @since 0.1.0
 */
class BP_REST_XProfile_Repeater_Endpoint extends WP_REST_Controller {

	/**
	 * XProfile Repeater Class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_XProfile_Repeater_Endpoint
	 */
	protected $group_fields_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace             = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base             = buddypress()->profile->id . '/repeater';
		$this->group_fields_endpoint = new BP_REST_XProfile_Field_Groups_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
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
	 * Create a new Repeater Group.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/xprofile/repeater/:id Create xProfile Repeater
	 * @apiName        CreateBBxProfileRepeaterFields
	 * @apiGroup       Profile Fields
	 * @apiDescription Create a new Repeater Fields Set in Group.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the group of profile fields.
	 * @apiParam {Boolean} [fetch_fields=true] Whether to fetch the fields for each group.
	 * @apiParam {Boolean} [fetch_field_data=true] Whether to fetch data for each field. Requires a $user_id.
	 * @apiParam {Boolean} [fetch_visibility_level=true] Whether to fetch the visibility level for each field.
	 */
	public function create_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the field group before it's deleted.
		$field_group = xprofile_get_field_group( (int) $request['id'] );

		if ( empty( $field_group->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to update your profile repeater fields.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$count = bp_get_profile_field_set_count( $field_group->id, $user_id );
		$count++;
		bp_set_profile_field_set_count( $field_group->id, $user_id, $count );

		$clone_fields = bp_get_repeater_clone_field_ids_subset( $field_group->id, $user_id );
		if ( empty( $clone_fields ) ) {
			$group_fields = bp_get_repeater_template_field_ids( $field_group->id );
			if ( ! empty( $group_fields ) ) {
				foreach ( $group_fields as $field_id ) {
					bp_clone_field_for_repeater_sets( $field_id );
				}
			}
		}

		$field_group = $this->group_fields_endpoint->get_xprofile_field_group_object( $request );

		$retval = $this->group_fields_endpoint->prepare_response_for_collection(
			$this->group_fields_endpoint->prepare_item_for_response( $field_group, $request )
		);

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'added' => true,
				'data'  => $retval,
			)
		);

		/**
		 * Fires after a XProfile repeater fields created via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param BP_XProfile_Group $field_group Deleted field group.
		 * @param WP_REST_Response  $response  The response data.
		 * @param WP_REST_Request   $request   The request sent to the API.
		 */
		do_action( 'bp_rest_xprofile_repeater_fields_create_item', $field_group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a new Repeater Group.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function create_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to update your profile repeater fields.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		// Get the field group before it's deleted.
		$field_group = xprofile_get_field_group( (int) $request['id'] );

		if ( true === $retval && empty( $field_group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid Group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$repeater_enabled = bp_xprofile_get_meta( $field_group->id, 'group', 'is_repeater_enabled', true );

		if ( empty( $field_group ) || 'on' !== $repeater_enabled ) {
			$retval = new WP_Error(
				'bp_rest_invalid_repeater_id',
				__( 'Invalid Repeater Group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the XProfile repeater fields `create_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_xprofile_repeater_fields_items_permissions_check', $retval, $request );
	}

	/**
	 * Delete a XProfile Repeater fields.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/xprofile/repeater/:id Delete xProfile Repeater
	 * @apiName        DeleteBBxProfileRepeaterFields
	 * @apiGroup       Profile Fields
	 * @apiDescription Delete a Repeater Fields Set in Group.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the group of profile fields.
	 * @apiParam {Boolean} [fetch_fields=true] Whether to fetch the fields for each group.
	 * @apiParam {Boolean} [fetch_field_data=true] Whether to fetch data for each field. Requires a $user_id.
	 * @apiParam {Boolean} [fetch_visibility_level=true] Whether to fetch the visibility level for each field.
	 * @apiParam {Array} fields Field IDs which you want to delete it.
	 */
	public function delete_item( $request ) {
		// Setting context.
		// Get the field group before it's deleted.
		$field_group = xprofile_get_field_group( (int) $request['id'] );

		if ( ! isset( $request['fields'] ) || empty( $request['fields'] ) ) {
			$request['fields'] = array();
		}

		if ( empty( $field_group->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid field group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete your profile repeater fields.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$group_fields = bp_get_repeater_template_field_ids( $field_group->id );

		$required           = false;
		$result             = true;
		$field_set_sequence = array();
		$deleted_field_ids  = array();
		if ( ! empty( $group_fields ) ) {
			foreach ( $group_fields as $field_id ) {
				$field_set_sequence += $this->get_repeater_fields( $field_id, $user_id, $field_group->id );
				$is_required         = xprofile_check_is_required_field( $field_id );
				if ( true === $is_required ) {
					$required = $is_required;
				}
			}
		}

		$posted_field_ids = array_keys( $field_set_sequence );

		$fields = $request['fields'];
		$count  = bp_get_profile_field_set_count( $field_group->id, $user_id );

		if (
			( isset( $fields ) && ! empty( $fields ) )
			&& (
				( true === $required && $count > 1 )
				|| ( true !== $required && $count >= 1 )
			)
		) {
			$deleted_field_ids = wp_parse_id_list( $fields );
			if ( ! empty( $deleted_field_ids ) ) {
				foreach ( $deleted_field_ids as $deleted_field_id ) {
					if ( array_key_exists( $deleted_field_id, $field_set_sequence ) ) {
						unset( $field_set_sequence[ $deleted_field_id ] );
					}
				}
			}
			--$count;
			if ( 0 === $count ) {
				$count        = 1;
				$clone_fields = bp_get_repeater_clone_field_ids_subset( $field_group->id, $user_id );
				if ( ! empty( $clone_fields ) ) {
					foreach ( $clone_fields as $deleted_field_id ) {
						if ( array_key_exists( $deleted_field_id, $field_set_sequence ) ) {
							unset( $field_set_sequence[ $deleted_field_id ] );
						}
					}
				}
			}
		}

		$field_set_sequence    = array_values( $field_set_sequence );
		$repeater_set_sequence = array_unique( $field_set_sequence );

		$this->bp_profile_repeaters_update_field_data( $user_id, $posted_field_ids, $repeater_set_sequence, $deleted_field_ids, $field_group->id );

		$field_group = $this->group_fields_endpoint->get_xprofile_field_group_object( $request );

		$retval = $this->group_fields_endpoint->prepare_response_for_collection(
			$this->group_fields_endpoint->prepare_item_for_response( $field_group, $request )
		);

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted' => $result,
				'data'    => $retval,
			)
		);

		/**
		 * Fires after a XProfile repeater field is deleted via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param BP_XProfile_Group $field_group Deleted field group.
		 * @param WP_REST_Response  $response  The response data.
		 * @param WP_REST_Request   $request   The request sent to the API.
		 */
		do_action( 'bp_rest_xprofile_repeater_fields_delete_item', $field_group, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a XProfile Repeater field.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this field.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		// Get the field group before it's deleted.
		$field_group = xprofile_get_field_group( (int) $request['id'] );

		if ( true === $retval && empty( $field_group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid Group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$repeater_enabled = bp_xprofile_get_meta( $field_group->id, 'group', 'is_repeater_enabled', true );

		if ( empty( $field_group ) || 'on' !== $repeater_enabled ) {
			$retval = new WP_Error(
				'bp_rest_invalid_repeater_id',
				__( 'Invalid Repeater Group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the XProfile fields `delete_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_xprofile_repeater_fields_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Get the XProfile Repeater field schema, conforming to JSON Schema.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_xprofile_repeater_fields',
			'type'       => 'object',
			'properties' => array(
				'data' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
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
		return apply_filters( 'bp_rest_xprofile_repeater_fields_schema', $schema );
	}

	/**
	 * Edit some properties for the EDITABLE methods.
	 *
	 * @since 0.1.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::DELETABLE ) {
		$args = $this->get_collection_params();

		$args['fields'] = array(
			'description'       => __( 'Pass Field IDs which you want to delete it.', 'buddyboss' ),
			'type'              => 'array',
			'required'          => true,
			'items'             => array( 'type' => 'int' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the method query arguments.
		 *
		 * @since 0.1.0
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( 'bp_rest_xprofile_repeater_field_delete_query_arguments', $args, $method );
	}

	/**
	 * Get the query params for XProfile Repeater field.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'edit';

		// Removing unused params.
		unset( $params['search'], $params['page'], $params['per_page'] );

		$params['id'] = array(
			'description' => __( 'A unique numeric ID for the group of profile fields.', 'buddyboss' ),
			'type'        => 'integer',
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

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_xprofile_repeater_fields_collection_params', $params );
	}

	/**
	 * Get sub field using parent field ID and group ID.
	 *
	 * @param integer $field_id The profile field object ID.
	 * @param integer $user_id  The ID of the user.
	 * @param integer $group_id The profile group object ID.
	 *
	 * @return array|void
	 */
	public function get_repeater_fields( $field_id, $user_id, $group_id ) {
		global $bp, $wpdb;

		if ( empty( $field_id ) || empty( $user_id ) ) {
			return;
		}

		// phpcs:ignore
		$sql = "select m1.object_id FROM {$bp->profile->table_name_meta} as m1 WHERE m1.meta_key = '_cloned_from' AND m1.meta_value = %d";
		// phpcs:ignore
		$sql = $wpdb->prepare( $sql, $field_id );
		// phpcs:ignore
		$results = $wpdb->get_col( $sql );
		$data    = array();

		$user_fields = bp_get_profile_field_set_count( $group_id, $user_id );

		if ( ! empty( $results ) && ! is_wp_error( $results ) ) {

			$count = 1;

			foreach ( $results as $k => $sub_field_id ) {

				if ( $count > $user_fields ) {
					break;
				}

				$data[ $sub_field_id ] = $count;
				$count++;
			}
		}

		return $data;
	}

	/**
	 * Update Field data with delete.
	 *
	 * @param integer $user_id               The ID of the user.
	 * @param array   $posted_field_ids      The remaining field ids after delete.
	 * @param array   $repeater_set_sequence The repeater sequence array.
	 * @param array   $deleted_field_ids     Fields IDs to delete.
	 * @param integer $field_group_id        The ID of the field group.
	 */
	protected function bp_profile_repeaters_update_field_data( $user_id, $posted_field_ids, $repeater_set_sequence, $deleted_field_ids, $field_group_id ) {
		global $wpdb;
		$bp = buddypress();

		if ( ! empty( $errors ) ) {
			return;
		}

		$is_repeater_enabled = 'on' === bp_xprofile_get_meta( $field_group_id, 'group', 'is_repeater_enabled', true ) ? true : false;
		if ( ! $is_repeater_enabled ) {
			return;
		}

		// First, clear the data for deleted fields, if any.
		if ( isset( $deleted_field_ids ) && ! empty( $deleted_field_ids ) ) {
			$deleted_field_ids = wp_parse_id_list( $deleted_field_ids );
			foreach ( $deleted_field_ids as $deleted_field_id ) {
				xprofile_delete_field_data( $deleted_field_id, $user_id );
			}
		}

		$field_set_sequence = wp_parse_id_list( $repeater_set_sequence );

		// We'll take the data from all clone fields and dump it into the main/template field.
		// This is done to ensure that search etc, work smoothly.
		$main_field_data = array();

		$counter            = 1;
		$field_set_sequence = (array) $field_set_sequence;
		if ( ! empty( $field_set_sequence ) ) {
			foreach ( (array) $field_set_sequence as $field_set_number ) {

				// phpcs:ignore
				$fields_of_current_set = $wpdb->get_col("SELECT object_id FROM {$bp->profile->table_name_meta} WHERE meta_key = '_clone_number' AND meta_value = {$field_set_number} " . ' AND object_id IN (' . implode( ',', $posted_field_ids ) . ") and object_type = 'field' " );

				if ( ! empty( $fields_of_current_set ) && ! is_wp_error( $fields_of_current_set ) ) {
					foreach ( $fields_of_current_set as $field_of_current_set ) {
						// phpcs:ignore
						$cloned_from = $wpdb->get_var( "SELECT meta_value FROM {$bp->profile->table_name_meta} WHERE object_id = {$field_of_current_set} AND meta_key = '_cloned_from' " );

						$sql  = "SELECT m1.object_id FROM {$bp->profile->table_name_meta} AS m1 JOIN {$bp->profile->table_name_meta} AS m2 ON m1.object_id = m2.object_id ";
						$sql .= " WHERE m1.object_type = 'field' AND m1.meta_key = '_cloned_from' AND m1.meta_value = {$cloned_from} ";
						$sql .= " AND m2.meta_key = '_clone_number' AND m2.meta_value = {$counter} ";

						// phpcs:ignore
						$corresponding_field_id = $wpdb->get_var( $sql );
						if ( ! empty( $corresponding_field_id ) ) {
							$new_data             = xprofile_get_field_data( $field_of_current_set, $user_id );
							$new_visibility_level = xprofile_get_field_visibility_level( $field_of_current_set, $user_id );
							xprofile_set_field_visibility_level( $corresponding_field_id, $user_id, $new_visibility_level );

							// phpcs:ignore
							$type = $wpdb->get_var( $wpdb->prepare( "SELECT `type` FROM {$bp->table_prefix}bp_xprofile_fields WHERE id = %d", $corresponding_field_id ) );

							if ( 'datebox' === $type ) {
								$new_data = $new_data . ' 00:00:00';
							}
							xprofile_set_field_data( $corresponding_field_id, $user_id, $new_data );

							if ( ! isset( $main_field_data[ $cloned_from ] ) ) {
								$main_field_data[ $cloned_from ] = array();
							}

							$main_field_data[ $cloned_from ][] = is_array( $new_data ) ? implode( ' ', $new_data ) : $new_data;
						}
					}
				}

				$counter ++;
			}
		}

		if ( ! empty( $main_field_data ) ) {
			foreach ( $main_field_data as $main_field_id => $values ) {
				$values_str = implode( ' ', $values );
				xprofile_set_field_data( $main_field_id, $user_id, $values_str );
			}
		}

		bp_set_profile_field_set_count( $field_group_id, $user_id, count( $field_set_sequence ) );
	}

}
