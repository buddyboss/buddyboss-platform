<?php
/**
 * BP REST: BB_REST_Reactions_Endpoint class
 *
 * @since   2.4.30
 * @package BuddyBoss
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reactions endpoints.
 *
 * @since 2.4.30
 */
class BB_REST_Reactions_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 2.4.30
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'reactions';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.4.30
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_reactions' ),
					'permission_callback' => array( $this, 'get_reactions_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_reactions_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/user-reactions',
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
					'args'                => $this->get_endpoint_args_for_item_schema(),
				),
				'schema' => array( $this, 'get_user_reactions_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/user-reactions/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the user reaction.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_user_reactions_schema' ),
			)
		);
	}

	/**
	 * Retrieve reactions
	 *
	 * @since 2.4.30
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of reactions object data.
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/reactions Get Reactions
	 * @apiName        GetBBReactions
	 * @apiGroup       Reactions
	 * @apiDescription Retrieve supported reactions
	 * @apiVersion     1.0.0
	 */
	public function get_reactions( $request ) {
		$reactions = bb_load_reaction()->bb_get_reactions();

		$context = $request->get_param( 'context' );
		$context = ! empty( $context ) ? $context : 'view';

		$reactions = $this->add_additional_fields_to_object( $reactions, $request );
		$reactions = $this->filter_response_by_context( $reactions, $context );
		$response  = rest_ensure_response( $reactions );

		/**
		 * Fires after a list of reactions is fetched via the REST API.
		 *
		 * @param array            $reactions Fetched reactions.
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bb_rest_reactions_get_items', $reactions, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to reaction items.
	 *
	 * @since 2.4.30
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_reactions_items_permissions_check( $request ) {
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
		 * Filter the reactions `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_reactions_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve user reactions.
	 *
	 * @since 2.4.30
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response List of user reactions object data.
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/user-reactions Get Reactions
	 * @apiName        GetBBUserReactions
	 * @apiGroup       User Reactions
	 * @apiDescription Retrieve user reactions
	 * @apiVersion     1.0.0
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {Number} [reaction_id] Limit result set to items with a specific Reaction ID.
	 * @apiParam {String} [item_type] Limit result set to items with a specific item type.
	 * @apiParam {Number} [item_id] Limit result set to items with a specific item ID.
	 * @apiParam {Number} [user_id] Limit result set to items with a specific user ID.
	 * @apiParam {Array=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {Array=id,date_created} [order_by=id] Order by a specific parameter.
	 */
	public function get_items( $request ) {
		$args = array(
			'reaction_id' => $request->get_param( 'reaction_id' ),
			'item_type'   => $request->get_param( 'item_type' ),
			'item_id'     => $request->get_param( 'item_id' ),
			'user_id'     => $request->get_param( 'user_id' ),
			'per_page'    => $request->get_param( 'per_page' ),
			'paged'       => $request->get_param( 'paged' ),
			'order'       => $request->get_param( 'order' ),
			'order_by'    => $request->get_param( 'order_by' ),
			'count_total' => true,
		);

		$user_reactions = bb_load_reaction()->bb_get_user_reactions( $args );

		$retval = array();
		foreach ( $user_reactions['reactions'] as $user_reaction ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $user_reaction, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $user_reactions['total'], $args['per_page'] );

		/**
		 * Fires after a list of user reactions is fetched via the REST API.
		 *
		 * @param array            $user_reactions Fetched user reactions.
		 * @param WP_REST_Response $response       The response data.
		 * @param WP_REST_Request  $request        The request sent to the API.
		 */
		do_action( 'bb_rest_user_reactions_get_items', $user_reactions, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to user reaction items.
	 *
	 * @since 2.4.30
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
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
		 * Filter the reactions `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_get_user_reactions_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single user reaction.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/user-reactions/:id Get user reaction
	 * @apiName        GetBBUserReaction
	 * @apiGroup       User reaction
	 * @apiDescription Retrieve single user reaction
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the user reaction.
	 */
	public function get_item( $request ) {
		$id            = (int) $request->get_param( 'id' );
		$user_reaction = bb_load_reaction()->bb_get_user_reaction( $id );

		if ( empty( $user_reaction->id ) ) {
			return new WP_Error(
				'bp_rest_user_reaction_invalid_id',
				__( 'Invalid user reaction ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $user_reaction, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a user reaction is fetched via the REST API.
		 *
		 * @param BB_Reaction      $user_reaction Fetched user reaction.
		 * @param WP_REST_Response $response      The response data.
		 * @param WP_REST_Request  $request       The request sent to the API.
		 */
		do_action( 'bb_rest_user_reaction_get_item', $user_reaction, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific user reaction.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
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

		/**
		 * Filter the user_reaction `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_user_reaction_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a user reactions.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/user-reactions Create user reactions
	 * @apiName        CreateUserReaction
	 * @apiGroup       User reaction
	 * @apiDescription Create user reactions
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [reaction_id] The ID of reaction.
	 * @apiParam {String} [item_type] Type of item.
	 * @apiParam {Number} [item_id] The ID of item.
	 * @apiParam {Number} [user_id] The ID for the author of the reaction.
	 */
	public function create_item( $request ) {
		$args = array(
			'reaction_id' => (int) $request->get_param( 'reaction_id' ),
			'item_type'   => $request->get_param( 'item_type' ),
			'item_id'     => (int) $request->get_param( 'item_id' ),
			'user_id'     => (int) $request->get_param( 'user_id' ),
			'error_type'  => 'wp_error',
		);

		// Setup the backward compatibilty for the activity favorite.
		bb_load_reaction()::$status = false;

		$user_reaction = bb_load_reaction()->bb_add_user_item_reaction( $args );

		// Setup the backward compatibilty for the activity favorite.
		bb_load_reaction()::$status = true;

		if ( is_wp_error( $user_reaction ) ) {
			return $user_reaction;
		} elseif ( empty( $user_reaction ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_user_reaction',
				__( 'There is an error while adding the user reaction.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $user_reaction, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a user reactions is created via the REST API.
		 *
		 * @param BB_Reaction      $user_reaction The created user reactions.
		 * @param WP_REST_Response $response      The response data.
		 * @param WP_REST_Request  $request       The request sent to the API.
		 */
		do_action( 'bb_rest_user_reactions_create_item', $user_reaction, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a user reaction.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to create user reaction.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$retval = $this->validate_request_item( $request );
		}

		/**
		 * Filter the user reaction `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_user_reaction_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a single user reaction.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/user-reactions/:id Delete User Reaction
	 * @apiName        DeleteUserReaction
	 * @apiGroup       User Reaction
	 * @apiDescription Delete a single user reaction.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the user reaction.
	 */
	public function delete_item( $request ) {
		// Get the user reaction before it's deleted.
		$id            = (int) $request->get_param( 'id' );
		$user_reaction = bb_load_reaction()->bb_get_user_reaction( $id );

		if ( empty( $user_reaction->id ) ) {
			return new WP_Error(
				'bp_rest_user_reaction_invalid_id',
				__( 'Invalid user reaction ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$user_id = bp_loggedin_user_id();

		if (
			property_exists( $user_reaction, 'user_id' ) &&
			(int) $user_reaction->user_id !== $user_id
		) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete user reaction.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$previous = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $user_reaction, $request )
		);

		// Setup the backward compatibilty for the activity favorite.
		bb_load_reaction()::$status = false;

		$deleted = bb_load_reaction()->bb_remove_user_item_reaction( $user_reaction->id );

		// Setup the backward compatibilty for the activity favorite.
		bb_load_reaction()::$status = true;

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_user_reaction_cannot_delete',
				__( 'There was a problem to remove user reaction.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $deleted,
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a user reaction is deleted via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bb_rest_user_reaction_delete_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to for the user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this user reaction.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval        = true;
			$id            = $request->get_param( 'id' );
			$user_reaction = bb_load_reaction()->bb_get_user_reaction( $id );
			if ( empty( $user_reaction->id ) ) {
				$retval = new WP_Error(
					'bp_rest_user_reaction_invalid_id',
					__( 'Invalid user reaction ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the user reaction `delete_item` permissions check.
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 */
		return apply_filters( 'bb_rest_user_reaction_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Edit the type of some properties for the CREATABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = array();
		$key  = 'create_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['reaction_id'] = array(
				'description'       => __( 'Reaction ID.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'enum'              => array_column( bb_load_reaction()->bb_get_reactions(), 'id' ),
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['item_type'] = array(
				'description'       => __( 'Item type', 'buddyboss' ),
				'type'              => 'string',
				'required'          => true,
				'enum'              => array_keys( bb_load_reaction()->bb_get_registered_reaction_item_types() ),
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['item_id'] = array(
				'description'       => __( 'Item ID.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['user_id'] = array(
				'description'       => __( 'User ID.', 'buddyboss' ),
				'type'              => 'integer',
				'default'           => bp_loggedin_user_id(),
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_user_reactions_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepares user reaction data for return as an object.
	 *
	 * @since 2.4.30
	 *
	 * @param BB_Reaction     $item    Reaction object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'           => (int) $item->id,
			'user_id'      => (int) $item->user_id,
			'reaction_id'  => (int) $item->reaction_id,
			'item_type'    => $item->item_type,
			'item_id'      => (int) $item->item_id,
			'date_created' => bp_rest_prepare_date_response( $item->date_created ),
		);

		$context = $request->get_param( 'context' );
		$context = ! empty( $context ) ? $context : 'view';

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $item, $request ) );

		/**
		 * Filter a user reactions value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BB_Reaction      $item     Reaction object.
		 */
		return apply_filters( 'bb_rest_user_reactions_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 2.4.30
	 *
	 * @param BB_Reaction     $item    Reaction object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_links( $item, $request ) {
		$links = array(
			'user' => array(
				'href'       => rest_url( bp_rest_get_user_url( $item->user_id ) ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array           $links   The prepared links of the REST response.
		 * @param BB_Reaction     $item    Reaction object.
		 * @param WP_REST_Request $request Full details about the request.
		 */
		return apply_filters( 'bb_rest_user_reactions_prepare_links', $links, $item, $request );
	}

	/**
	 * Validate user reaction user ID/item ID/Item type.
	 *
	 * @param WP_REST_Request|int|array $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_request_item( $request ) {
		$reaction_id = (int) $request->get_param( 'reaction_id' );
		$item_type   = $request->get_param( 'item_type' );
		$item_id     = (int) $request->get_param( 'item_id' );
		$user_id     = (int) $request->get_param( 'user_id' );

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create user reaction.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( empty( $reaction_id ) ) {
			$retval = new WP_Error(
				'bp_rest_user_reaction_required_item_id',
				__( 'The reaction ID is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( empty( $item_id ) ) {
			$retval = new WP_Error(
				'bp_rest_user_reaction_required_item_id',
				__( 'The item ID is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( empty( $item_type ) ) {
			$retval = new WP_Error(
				'bp_rest_user_reaction_required_item_type',
				__( 'The item type is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			$retval = true;
		}

		/**
		 * Filters the validate request user reaction item data.
		 *
		 * @param boolean|WP_Error          $retval  The validate response.
		 * @param WP_REST_Request|int|array $request Full details about the request.
		 */
		return apply_filters( 'bb_rest_user_reaction_validate_request_item', $retval, $request );
	}

	/**
	 * Get the query params for collections of plugins.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		if ( $params['search'] ) {
			unset( $params['search'] );
		}

		$params['reaction_id'] = array(
			'description'       => __( 'Limit result set to items with a specific Reaction ID.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['item_type'] = array(
			'description'       => __( 'Limit result set to items with a specific item type.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['item_id'] = array(
			'description'       => __( 'Limit result set to items with a specific item ID.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit result set to items with a specific user ID.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
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

		$params['order_by'] = array(
			'description'       => __( 'Order by a specific parameter.', 'buddyboss' ),
			'default'           => 'id',
			'type'              => 'string',
			'enum'              => array( 'id', 'date_created' ),
			'sanitize_callback' => 'sanitize_key',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bb_rest_user_reactions_collection_params', $params );
	}

	/**
	 * Get the reaction schema, conforming to JSON Schema.
	 *
	 * @since 2.4.30
	 *
	 * @return array
	 */
	public function get_reactions_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_reactions',
			'type'       => 'object',
			'properties' => array(
				'id'   => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'The ID of the reaction.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'name' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'The name of the reaction.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'icon' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'The icon of the reaction.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the get reactions schema.
		 *
		 * @since 2.4.30
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_get_reactions_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the user reaction schema, conforming to JSON Schema.
	 *
	 * @since 2.4.30
	 *
	 * @return array
	 */
	public function get_user_reactions_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_user_reactions',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the user reaction.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'user_id'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the user.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'reaction_id'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the reaction.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'item_type'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The type of the item.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'item_id'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the item.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'date_created' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The date the user reaction was created.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the user reactions schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bb_rest_user_reactions_schema', $this->add_additional_fields_schema( $schema ) );
	}

}
