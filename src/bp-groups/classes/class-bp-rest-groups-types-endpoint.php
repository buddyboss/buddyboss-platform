<?php
/**
 * BP REST: BP_REST_Groups_Types_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Groups Type endpoints.
 *
 * Use /groups/types
 *
 * @since 0.1.0
 */
class BP_REST_Groups_Types_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->groups->id . '/types';
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
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

	}

	/**
	 * Retrieve Groups Types.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/types Groups Types
	 * @apiName        GetBBGroupsTypes
	 * @apiGroup       Groups
	 * @apiDescription Retrieve Groups Types.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 */
	public function get_items( $request ) {
		$registered_types = bp_groups_get_group_types( array(), 'objects' );

		$retval = array();
		if ( ! empty( $registered_types ) ) {
			foreach ( $registered_types as $type ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $type, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of types are fetched via the REST API.
		 *
		 * @param array            $registered_types Fetched groups types
		 * @param WP_REST_Response $response         The response data.
		 * @param WP_REST_Request  $request          The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_groups_types_get_items', $registered_types, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to Groups Types.
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

		if ( function_exists( 'bp_disable_group_type_creation' ) && false === bp_disable_group_type_creation() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Group Type is disabled from setting.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the Groups types `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_types_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Prepares single Groups type to return as an object.
	 *
	 * @param array           $type   Groups Type.
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $type, $request ) {
		$data = array(
			'labels'                => array(
				'name'          => ( isset( $type->labels['name'] ) && ! empty( $type->labels['name'] ) ) ? wp_specialchars_decode( $type->labels['name'] ) : '',
				'singular_name' => ( isset( $type->labels['singular_name'] ) && ! empty( $type->labels['singular_name'] ) ) ? wp_specialchars_decode( $type->labels['singular_name'] ) : '',
			),
			'name'                  => ( isset( $type->name ) ? $type->name : '' ),
			'description'           => ( isset( $type->description ) ? $type->description : '' ),
			'directory_slug'        => ( isset( $type->directory_slug ) ? $type->directory_slug : '' ),
			'has_directory'         => ( isset( $type->has_directory ) ? $type->has_directory : false ),
			'show_in_create_screen' => ( isset( $type->show_in_create_screen ) ? $type->show_in_create_screen : '' ),
			'show_in_list'          => ( isset( $type->show_in_list ) ? $type->show_in_list : '' ),
			'create_screen_checked' => ( isset( $type->create_screen_checked ) ? $type->create_screen_checked : '' ),
		);

		// Get the edit schema.
		$schema  = $this->get_item_schema();
		$schema  = $schema['properties'];
		$post_id = function_exists( 'bp_group_get_group_type_id' ) ? bp_group_get_group_type_id( $data['name'] ) : 0;

		// Define default visibility property.
		if ( isset( $schema['enable_filter'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['enable_filter'] = (bool) (
					! empty( get_post_meta( $post_id, '_bp_group_type_enable_filter', true ) )
					? get_post_meta( $post_id, '_bp_group_type_enable_filter', true )
					: ''
				);
			} else {
				$data['enable_filter'] = false;
			}
		}

		if ( isset( $schema['enable_remove'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['enable_remove'] = (bool) (
					! empty( get_post_meta( $post_id, '_bp_group_type_enable_remove', true ) )
					? get_post_meta( $post_id, '_bp_group_type_enable_remove', true )
					: ''
				);
			} else {
				$data['enable_remove'] = false;
			}
		}

		if ( isset( $schema['restrict-invites-user-same-group-type'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['restrict-invites-user-same-group-type'] = (bool) (
					! empty( get_post_meta( $post_id, '_bp_group_type_restrict_invites_user_same_group_type', true ) )
					? get_post_meta( $post_id, '_bp_group_type_restrict_invites_user_same_group_type', true )
					: ''
				);
			} else {
				$data['restrict-invites-user-same-group-type'] = false;
			}
		}

		if ( isset( $schema['member_type_group_invites'] ) ) {
			$member_types              = bp_get_member_types( array(), 'names' );
			$get_selected_member_types = ( ! empty( $post_id ) && ! empty( get_post_meta( $post_id, '_bp_group_type_enabled_member_type_group_invites', true ) ) ) ? get_post_meta( $post_id, '_bp_group_type_enabled_member_type_group_invites', true ) : array();
			$member_types_invite       = array();
			if ( ! empty( $member_types ) ) {
				foreach ( $member_types as $member_type ) {
					$member_types_invite[] = array(
						'name'     => $member_type,
						'selected' => in_array( $member_type, $get_selected_member_types, true ),
					);
				}
			}
			$data['member_type_group_invites'] = $member_types_invite;
		}

		if ( isset( $schema['member_type_join'] ) ) {
			$member_types              = bp_get_member_types( array(), 'names' );
			$get_selected_member_types = ( ! empty( $post_id ) && ! empty( get_post_meta( $post_id, '_bp_group_type_enabled_member_type_join', true ) ) ) ? get_post_meta( $post_id, '_bp_group_type_enabled_member_type_join', true ) : array();
			$member_types_join         = array();
			if ( ! empty( $member_types ) ) {
				foreach ( $member_types as $member_type ) {
					$member_types_join[] = array(
						'name'     => $member_type,
						'selected' => in_array( $member_type, $get_selected_member_types, true ),
					);
				}
			}
			$data['member_type_join'] = $member_types_join;
		}

		if ( isset( $schema['group_type_role_labels'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['group_type_role_labels'] = (
					! empty( get_post_meta( $post_id, '_bp_group_type_role_labels', true ) )
					? get_post_meta( $post_id, '_bp_group_type_role_labels', true )
					: ''
				);
			} else {
				$data['group_type_role_labels'] = array();
			}
		}

		// Group type's label background and text color.
		$label_color_data = function_exists( 'bb_get_group_type_label_colors' ) ? bb_get_group_type_label_colors( $data['name'] ) : '';
		if ( ! empty( $label_color_data ) && isset( $schema['label_colors'] ) ) {
			$data['label_colors'] = $label_color_data;
		}

		$response = rest_ensure_response( $data );

		/**
		 * Filter the Groups type field returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param array            $type     Xprofile Type
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_groups_types_prepare_value', $response, $request, $type );
	}


	/**
	 * Get the Groups types schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_groups_types',
			'type'       => 'object',
			'properties' => array(
				'labels'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Labels to use in various parts of the interface.', 'buddyboss' ),
					'type'        => 'object',
					'arg_options' => array(
						'sanitize_callback' => null,
						'validate_callback' => null,
					),
					'properties'  => array(
						'name'          => array(
							'description' => __( 'Default name. Should typically be plural.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
						'singular_name' => array(
							'description' => __( 'Singular name.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'name'                  => array(
					'description' => __( 'Slug of the group type.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'           => array(
					'description' => __( 'Description of the group type.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'directory_slug'        => array(
					'description' => __( 'Directory slug of the group type.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'has_directory'         => array(
					'description' => __( 'Whether the group type should have its own type-specific directory.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'show_in_create_screen' => array(
					'description' => __( 'Whether this group type is allowed to be selected on the group creation page.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'show_in_list'          => array(
					'description' => __( 'Whether this group type should be shown in lists.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'create_screen_checked' => array(
					'description' => __( 'Whether we should have our group type checkbox checked by default on group create.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		$post_types = get_post_types();
		if ( ! empty( $post_types ) && in_array( 'bp-group-type', $post_types, true ) ) {
			$schema['properties']['enable_filter'] = array(
				'description' => __( 'Display this group type in "Types" filter in Groups Directory.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['enable_remove'] = array(
				'description' => __( 'Hide all groups of this type from Groups Directory.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['restrict-invites-user-same-group-type'] = array(
				'description' => __( 'If a member is already in a group of this type, they cannot be sent an invite to join another group of this type.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['member_type_group_invites'] = array(
				'description' => __( 'Only members of the selected profile types may be sent requests to join this group.', 'buddyboss' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['member_type_join'] = array(
				'description' => __( 'Members of the selected Profile Types below can join Private groups of the Group Type without approval.', 'buddyboss' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['group_type_role_labels'] = array(
				'description' => __( 'Rename the group member roles for groups of this type.', 'buddyboss' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['label_colors'] = array(
				'description' => __( 'Label\'s text and background colors for group types.' , 'buddyboss' ),
				'type'        => 'array',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);
		}

		/**
		 * Filters the groups types field schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_groups_types_schema', $schema );
	}

	/**
	 * Get the query params for the Groups types.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		unset( $params['page'] );
		unset( $params['per_page'] );
		unset( $params['search'] );

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_groups_types_collection_params', $params );
	}

}
