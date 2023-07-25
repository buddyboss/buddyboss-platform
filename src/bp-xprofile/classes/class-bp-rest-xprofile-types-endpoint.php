<?php
/**
 * BP REST: BP_REST_XProfile_Types_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * XProfile Type endpoints.
 *
 * Use /xprofile/types
 *
 * @since 0.1.0
 */
class BP_REST_XProfile_Types_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->profile->id . '/types';
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
	 * Retrieve XProfile Types.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/xprofile/types Profile Types
	 * @apiName        GetBBProfileTypes
	 * @apiGroup       Profile Fields
	 * @apiDescription Retrieve Profile Types.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 */
	public function get_items( $request ) {
		$registered_types = bp_get_member_types( array(), 'objects' );

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
		 * Fires after a list of field are fetched via the REST API.
		 *
		 * @param array            $registered_types Fetched member types
		 * @param WP_REST_Response $response         The response data.
		 * @param WP_REST_Request  $request          The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_xprofile_types_get_items', $registered_types, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to XProfile Types.
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

		if ( true === $retval && function_exists( 'bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Profile Types is disabled from setting.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the XProfile types `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_types_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Prepares single XProfile type to return as an object.
	 *
	 * @param array           $type   Xprofile Type.
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $type, $request ) {
		$data = array(
			'labels'         => array(
				'name'          => ( isset( $type->labels['name'] ) && ! empty( $type->labels['name'] ) ) ? wp_specialchars_decode( $type->labels['name'] ) : '',
				'singular_name' => ( isset( $type->labels['singular_name'] ) && ! empty( $type->labels['singular_name'] ) ) ? wp_specialchars_decode( $type->labels['singular_name'] ) : '',
			),
			'has_directory'  => ( isset( $type->has_directory ) ? $type->has_directory : false ),
			'name'           => ( isset( $type->name ) ? $type->name : '' ),
			'directory_slug' => ( isset( $type->directory_slug ) ? $type->directory_slug : '' ),
		);

		// Get the edit schema.
		$schema  = $this->get_item_schema();
		$schema  = $schema['properties'];
		$post_id = $this->bp_rest_member_type_post_by_type( $type->name );

		// Define default visibility property.
		if ( isset( $schema['enable_filter'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['enable_filter'] = (
					! empty( get_post_meta( $post_id, '_bp_member_type_enable_filter', true ) )
					? get_post_meta( $post_id, '_bp_member_type_enable_filter', true )
					: (
						// Added support for BP Member Types.
						! empty( get_post_meta( $post_id, '_bp_member_type_enable_directory', true ) )
						? get_post_meta( $post_id, '_bp_member_type_enable_directory', true )
						: false
					)
				);
			} else {
				$data['enable_filter'] = false;
			}
		}

		// Define default visibility property.
		if ( isset( $schema['enable_remove'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['enable_remove'] = (
					! empty( get_post_meta( $post_id, '_bp_member_type_enable_remove', true ) )
					? get_post_meta( $post_id, '_bp_member_type_enable_remove', true )
					: false
				);
			} else {
				$data['enable_remove'] = false;
			}
		}

		// Define default network search visibility property.
		if ( isset( $schema['network_search_enable_remove'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['network_search_enable_remove'] = (bool) (
				! empty( get_post_meta( $post_id, '_bp_member_type_enable_search_remove', true ) )
					? get_post_meta( $post_id, '_bp_member_type_enable_search_remove', true )
					: 0
				);
			} else {
				$data['network_search_enable_remove'] = false;
			}
		}

		// Define default visibility property.
		if ( isset( $schema['enable_profile_field'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['enable_profile_field'] = (
					! empty( get_post_meta( $post_id, '_bp_member_type_enable_profile_field', true ) )
					? get_post_meta( $post_id, '_bp_member_type_enable_profile_field', true )
					: (
						// Added support for BP Member Types.
						! empty( get_post_meta( $post_id, '_bp_member_type_enable_registration', true ) )
						? get_post_meta( $post_id, '_bp_member_type_enable_registration', true )
						: false
					)
				);
			} else {
				$data['enable_profile_field'] = false;
			}
		}

		// Define default visibility property.
		if ( isset( $schema['bp-group-type'] ) ) {
			$get_all_registered_group_types = bp_groups_get_group_types( array(), 'names' );
			$get_selected_group_types       = ( ! empty( $post_id ) && ! empty( get_post_meta( $post_id, '_bp_member_type_enabled_group_type_create', true ) ) ) ? get_post_meta( $post_id, '_bp_member_type_enabled_group_type_create', true ) : array();
			$group_types                    = array();
			if ( ! empty( $get_all_registered_group_types ) ) {
				foreach ( $get_all_registered_group_types as $group_type ) {
					$group_types[] = array(
						'name'     => $group_type,
						'selected' => in_array( $group_type, $get_selected_group_types, true ),
					);
				}
			}
			$data['bp-group-type'] = $group_types;
		}

		// Define default visibility property.
		if ( isset( $schema['bp-group-type-auto-join'] ) ) {
			$get_all_registered_group_types = bp_groups_get_group_types( array(), 'names' );
			$get_selected_group_types       = ( ! empty( $post_id ) && ! empty( get_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', true ) ) ) ? get_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', true ) : array();
			$group_types_auto_join          = array();
			if ( ! empty( $get_all_registered_group_types ) ) {
				foreach ( $get_all_registered_group_types as $group_type ) {
					$group_types_auto_join[] = array(
						'name'     => $group_type,
						'selected' => in_array( $group_type, $get_selected_group_types, true ),
					);
				}
			}
			$data['bp-group-type-auto-join'] = $group_types_auto_join;
		}

		// Define default visibility property.
		if ( isset( $schema['bp-member-type-enabled-invite'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['bp-member-type-enabled-invite'] = (
					! empty( get_post_meta( $post_id, '_bp_member_type_enable_invite', true ) )
					? get_post_meta( $post_id, '_bp_member_type_enable_invite', true )
					: false
				);
			} else {
				$data['bp-member-type-enabled-invite'] = false;
			}
		}

		// Define default visibility property.
		if ( isset( $schema['bp-member-type-invite'] ) ) {
			$member_types              = bp_get_member_types( array(), 'names' );
			$get_selected_member_types = ( ! empty( $post_id ) && ! empty( get_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', true ) ) ) ? get_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', true ) : array();
			$member_types_invite       = array();
			if ( ! empty( $member_types ) ) {
				foreach ( $member_types as $member_type ) {
					$member_types_invite[] = array(
						'name'     => $member_type,
						'selected' => in_array( $member_type, $get_selected_member_types, true ),
					);
				}
			}
			$data['bp-member-type-invite'] = $member_types_invite;
		}

		// Define default visibility property.
		if ( isset( $schema['wp_roles'] ) ) {
			if ( ! empty( $post_id ) ) {
				$data['wp_roles'] = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );
			} else {
				$data['wp_roles'] = '';
			}
		}

		if ( isset( $schema['allow_messaging_without_connection'] ) ) {
			$data['allow_messaging_without_connection'] = (
				! empty( $post_id ) &&
				true === (bool) get_post_meta( $post_id, '_bp_member_type_allow_messaging_without_connection', true )
			);
		}

		$response = rest_ensure_response( $data );

		/**
		 * Filter the XProfile field returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param array            $type     Xprofile Type
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_xprofile_types_prepare_value', $response, $request, $type );
	}


	/**
	 * Get the XProfile types schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_xprofile_types',
			'type'       => 'object',
			'properties' => array(
				'labels'         => array(
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
				'has_directory'  => array(
					'description' => __( 'Whether the profile type should have its own type-specific directory.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'           => array(
					'description' => __( 'Slug of the member type.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'directory_slug' => array(
					'description' => __( 'Directory slug of the member type.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		$post_types = get_post_types();

		if (
			! empty( $post_types )
			&& (
				in_array( 'bp-member-type', $post_types, true )
				|| in_array( 'bmt-member-type', $post_types, true ) // Added support for BP Member Types.
			)
		) {
			$schema['properties']['enable_filter'] = array(
				'description' => __( 'Display this profile type in "Types" filter in Members Directory.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['enable_remove'] = array(
				'description' => __( 'Hide all members of this type from Members Directory.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['network_search_enable_remove'] = array(
				'description' => __( 'Hide all members of this type from Network Search results.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['enable_profile_field'] = array(
				'description' => __( 'Allow users to self-select as this profile type from the "Profile Type" profile field dropdown.', 'buddyboss' ),
				'type'        => 'boolean',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			if (
				bp_is_active( 'groups' )
				&& function_exists( 'bp_restrict_group_creation' )
				&& false === bp_restrict_group_creation()
			) {
				$schema['properties']['bp-group-type'] = array(
					'description' => __( 'Which group types this profile type is allowed to create.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				);
			}

			if (
				bp_is_active( 'groups' )
				&& function_exists( 'bp_disable_group_type_creation' )
				&& function_exists( 'bp_enable_group_auto_join' )
				&& true === bp_disable_group_type_creation()
				&& true === bp_enable_group_auto_join()
			) {
				$schema['properties']['bp-group-type-auto-join'] = array(
					'description' => __( 'On Registration and Account activation, Profile Type members will auto-join Groups from Selected Group Types below other than Hidden Groups.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				);
			}

			if (
				bp_is_active( 'invites' )
				&& function_exists( 'bp_disable_invite_member_type' )
				&& true === bp_disable_invite_member_type()
			) {
				$schema['properties']['bp-member-type-enabled-invite'] = array(
					'description' => __( 'Allow members to select the profile type that the invited recipient will be automatically assigned to on registration.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				);

				$schema['properties']['bp-member-type-invite'] = array(
					'description' => __( 'Allowed profile types to select on the email invites.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				);
			}

			$schema['properties']['wp_roles'] = array(
				'description' => __( 'Users of this profile type will be auto-assigned to the following WordPress roles.', 'buddyboss' ),
				'type'        => 'array',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
			);

			if ( bp_is_active( 'messages' ) && bp_is_active( 'friends' ) && true === (bool) bp_get_option( 'bp-force-friendship-to-message', false ) ) {
				$schema['properties']['allow_messaging_without_connection'] = array(
					'description' => __( 'Allow messaging without connection.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				);
			}

		}

		/**
		 * Filters the xprofile field group schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_xprofile_types_schema', $schema );
	}

	/**
	 * Get the query params for the XProfile types.
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
		return apply_filters( 'bp_rest_xprofile_types_collection_params', $params );
	}

	/**
	 * Get Member post by profile type.
	 *
	 * @param string $member_type Member type name.
	 *
	 * @return array
	 */
	protected function bp_rest_member_type_post_by_type( $member_type ) {
		if ( empty( $member_type ) ) {
			return;
		}

		global $wpdb;

		// phpcs:disable
		$query   = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '%s' AND LOWER(meta_value) = '%s'";
		$query   = $wpdb->prepare( $query, '_bp_member_type_key', $member_type );
		$post_id = $wpdb->get_var( $query );

		// Fallback to legacy way to retrieve profile type from name by using singular label.
		if ( ! $post_id ) {
			$name    = str_replace( array( '-', '-' ), array( ' ', ',' ), $member_type );
			$query   = "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '%s' AND LOWER(meta_value) = '%s'";
			$query   = $wpdb->prepare( $query, '_bp_member_type_label_singular_name', $name );
			$post_id = $wpdb->get_var( $query );
		}
		// phpcs:enable

		return apply_filters( 'bp_member_type_post_by_type', $post_id );
	}

}
