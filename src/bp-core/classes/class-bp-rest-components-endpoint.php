<?php
/**
 * BP REST: BP_REST_Components_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Components endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Components_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'components';
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
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'name'   => array(
							'type'        => 'string',
							'required'    => true,
							'description' => __( 'Name of the component.', 'buddyboss' ),
						),
						'action' => array(
							'description' => __( 'Whether to activate or deactivate the component.', 'buddyboss' ),
							'type'        => 'string',
							'enum'        => array( 'activate', 'deactivate' ),
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve components.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {GET} /wp-json/buddyboss/v1/components Components
	 * @apiName GetBBComponents
	 * @apiGroup Components
	 * @apiDescription Retrieve components
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of records to be returned in result set.
	 * @apiParam {String=all,active,inactive} [status] Limit result set to items with a specific status.
	 * @apiParam {String=all,optional,retired,required} [type] Limit result set to items with a specific type.
	 */
	public function get_items( $request ) {
		$args = array(
			'type'     => $request['type'],
			'status'   => $request['status'],
			'per_page' => $request['per_page'],
		);

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_components_get_items_query_args', $args, $request );

		$type = $args['type'];

		// Get all components based on type.
		$components = bp_core_get_components( $type );

		// Active components.
		$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

		// Core component is always active.
		if ( 'optional' !== $type && array_key_exists( 'core', $components ) ) {
			$active_components['core'] = $components['core'];
		}

		// Inactive components.
		$inactive_components = array_diff( array_keys( $components ), array_keys( $active_components ) );

		$current_components = array();
		switch ( $args['status'] ) {
			case 'all':
				foreach ( $components as $name => $labels ) {
					$current_components[] = $this->get_component_info( $name );
				}
				break;

			case 'active':
				foreach ( array_keys( $active_components ) as $component ) {
					$current_components[] = $this->get_component_info( $component );
				}
				break;

			case 'inactive':
				foreach ( $inactive_components as $component ) {
					$current_components[] = $this->get_component_info( $component );
				}
				break;
		}

		$retval = array();
		foreach ( $current_components as $component ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $component, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $current_components ), $args['per_page'] );

		/**
		 * Fires after a list of components is fetched via the REST API.
		 *
		 * @param array $current_components Fetched components.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_components_get_items', $current_components, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list components.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you do not have access to list components.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_current_user_can( 'manage_options' ) ) {
			$retval = true;
		}

		/**
		 * Filter the components `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_components_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Activate/Deactivate a component.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {PATCH} /wp-json/buddyboss/v1/components Update component
	 * @apiName UpdateBBComponent
	 * @apiGroup Components
	 * @apiDescription Update component
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {String} name Name of component which needs to be activated/deactivated. Eg: activity, notifications, settings and further...
	 * @apiParam {String=activate,deactivate} action Action to be performed
	 */
	public function update_item( $request ) {
		$component = $request['name'];

		if ( ! $this->component_exists( $component ) ) {
			return new WP_Error(
				'bp_rest_component_nonexistent',
				__( 'Sorry, this component does not exist.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$action = $request['action'];
		if ( empty( $action ) || ! in_array( $action, array( 'activate', 'deactivate' ), true ) ) {
			return new WP_Error(
				'bp_rest_component_invalid_action',
				__( 'Sorry, this is not a valid action.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		if ( 'activate' === $action ) {
			if ( bp_is_active( $component ) ) {
				return new WP_Error(
					'bp_rest_component_already_active',
					__( 'Sorry, this component is already active.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}

			$component_info = $this->activate_helper( $component );
		} else {
			if ( ! bp_is_active( $component ) ) {
				return new WP_Error(
					'bp_rest_component_inactive',
					__( 'Sorry, this component is not active.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}

			if ( array_key_exists( $component, bp_core_get_components( 'required' ) ) ) {
				return new WP_Error(
					'bp_rest_required_component',
					__( 'Sorry, you cannot deactivate a required component.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}

			$component_info = $this->deactivate_helper( $component );
		}

		$request->set_param( 'context', 'edit' );

		$retval = array(
			$this->prepare_response_for_collection(
				$this->prepare_item_for_response( $component_info, $request )
			),
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a component is updated via the REST API.
		 *
		 * @param array $component_info Component info.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_components_update_item', $component_info, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a component.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->get_items_permissions_check( $request );

		/**
		 * Filter the components `update_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_components_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares component data for return as an object.
	 *
	 * @param array           $component The component and its values.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $component, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $component, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		/**
		 * Filter a component value returned from the API.
		 *
		 * @param WP_REST_Response $response The Response data.
		 * @param WP_REST_Request $request Request used to generate the response.
		 * @param array $component The component and its values.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_components_prepare_value', $response, $request, $component );
	}

	/**
	 * Verify Component Status.
	 *
	 * @param string $name Component name.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	protected function verify_component_status( $name ) {
		if ( 'core' === $name || bp_is_active( $name ) ) {
			return __( 'active', 'buddyboss' );
		}

		return __( 'inactive', 'buddyboss' );
	}

	/**
	 * Deactivate component helper.
	 *
	 * @param string $component Component id.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function deactivate_helper( $component ) {

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		unset( $active_components[ $component ] );

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		return $this->get_component_info( $component );
	}

	/**
	 * Activate component helper.
	 *
	 * @param string $component Component id.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function activate_helper( $component ) {

		$active_components =& buddypress()->active_components;

		// Set for the rest of the page load.
		$active_components[ $component ] = 1;

		// Save in the db.
		bp_update_option( 'bp-active-components', $active_components );

		// Ensure that dbDelta() is defined.
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Run the setup, in case tables have to be created.
		require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';

		bp_core_install( $active_components );
		bp_core_add_page_mappings( $active_components );

		return $this->get_component_info( $component );
	}

	/**
	 * Get component info helper.
	 *
	 * @param string $component Component id.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_component_info( $component ) {

		// Get all components.
		$components = bp_core_get_components();

		// Get specific component info.
		$info = $components[ $component ];

		// Return empty early.
		if ( empty( $info ) ) {
			return array();
		}

		return array(
			'name'        => $component,
			'status'      => $this->verify_component_status( $component ),
			'title'       => $info['title'],
			'description' => $info['description'],
		);
	}

	/**
	 * Does the component exist?
	 *
	 * @param string $component Component.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	protected function component_exists( $component ) {
		return in_array( $component, array_keys( bp_core_get_components() ), true );
	}

	/**
	 * Get the components schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_components',
			'type'       => 'object',
			'properties' => array(
				'name'        => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Name of the object.', 'buddyboss' ),
					'type'        => 'string',
				),
				'status'      => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Whether the object is active or inactive.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array( 'active', 'inactive' ),
				),
				'title'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'HTML title of the object.', 'buddyboss' ),
					'type'        => 'string',
				),
				'description' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'HTML description of the object.', 'buddyboss' ),
					'type'        => 'string',
				),
			),
		);

		/**
		 * Filters the components schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_components_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['status'] = array(
			'description'       => __( 'Limit result set to items with a specific status.', 'buddyboss' ),
			'default'           => 'all',
			'type'              => 'string',
			'enum'              => array( 'all', 'active', 'inactive' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to items with a specific type.', 'buddyboss' ),
			'default'           => 'all',
			'type'              => 'string',
			'enum'              => array( 'all', 'optional', 'retired', 'required' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_components_collection_params', $params );
	}
}
