<?php
/**
 * BP REST: BB_REST_Subscriptions_Endpoint class
 *
 * @since   2.2.6
 * @package BuddyBoss
 */

defined( 'ABSPATH' ) || exit;

/**
 * Subscriptions endpoints.
 *
 * @since 2.2.6
 */
class BB_REST_Subscriptions_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 2.2.6
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'subscriptions';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.2.6
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
						'description' => __( 'A unique numeric ID for the Subscription.', 'buddyboss' ),
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
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/subscription-types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_type_items' ),
					'permission_callback' => array( $this, 'get_type_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_type_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve subscriptions.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of subscriptions object data.
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/subscription Get Subscriptions
	 * @apiName        GetBBSubscriptions
	 * @apiGroup       Subscriptions
	 * @apiDescription Retrieve subscriptions
	 * @apiVersion     1.0.0
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=forum,topic} [type] Limit results based on subscription type.
	 * @apiParam {String=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {String=id,type,item_id,date_recorded} [orderby=date_recorded] Order Subscriptions by which attribute.
	 * @apiParam {Number} [blog_id] Get subscription site wise. Default current site ID.
	 * @apiParam {Number} [item_id] Get Subscriptions that are user subscribed items.
	 * @apiParam {Number} [secondary_item_id] Get Subscriptions that are children of the subscribed items.
	 * @apiParam {Number} [status=1] Active Subscriptions. 1 = Active, 0 = Inactive.
	 * @apiParam {Array} [include] Ensure result set includes Subscriptions with specific IDs.
	 * @apiParam {Array} [exclude] Ensure result set excludes Subscriptions with specific IDs.
	 */
	public function get_items( $request ) {

		$args = array(
			'type'              => $request->get_param( 'type' ),
			'blog_id'           => $request->get_param( 'blog_id' ),
			'item_id'           => $request->get_param( 'item_id' ),
			'secondary_item_id' => $request->get_param( 'secondary_item_id' ),
			'per_page'          => $request->get_param( 'per_page' ),
			'page'              => $request->get_param( 'page' ),
			'user_id'           => bp_loggedin_user_id(),
			'order_by'          => $request->get_param( 'order_by' ),
			'order'             => $request->get_param( 'order' ),
			'include'           => $request->get_param( 'include' ),
			'exclude'           => $request->get_param( 'exclude' ),
			'status'            => $request->get_param( 'status' ),
			'count'             => true,
		);

		$subscriptions = bb_get_subscriptions( $args );
		$retval        = array();
		foreach ( $subscriptions['subscriptions'] as $subscription ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $subscription, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $subscriptions['total'], $args['per_page'] );

		/**
		 * Fires after a list of subscriptions is fetched via the REST API.
		 *
		 * @param array            $subscriptions Fetched subscriptions.
		 * @param WP_REST_Response $response      The response data.
		 * @param WP_REST_Request  $request       The request sent to the API.
		 */
		do_action( 'bb_rest_subscriptions_get_items', $subscriptions, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to subscription items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to view subscription.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the subscriptions `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_subscriptions_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Create a subscription.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/subscription Create Subscription
	 * @apiName        CreateBBSubscription
	 * @apiGroup       Subscription
	 * @apiDescription Create subscription
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String=forum,topic} type The type subscription.
	 * @apiParam {Number} item_id The ID of forum/topic.
	 * @apiParam {Number} [secondary_item_id] ID of the parent forum/topic.
	 * @apiParam {Number} [user_id] The ID of the user who created the Subscription. default logged-in user id.
	 * @apiParam {Number} [blog_id] The ID of site. default current site id.
	 */
	public function create_item( $request ) {

		// Setting context.
		$request->set_param( 'context', 'edit' );

		$subscription_id = bb_create_subscription( $this->prepare_item_for_database( $request ) );

		if ( is_wp_error( $subscription_id ) ) {
			return $subscription_id;
		} elseif ( ! is_numeric( $subscription_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_subscription',
				__( 'There is an error while adding the subscription.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$subscription = $this->get_subscription_object( $subscription_id );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $subscription, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a subscription is created via the REST API.
		 *
		 * @param BB_Subscriptions $subscription The created subscription.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bb_rest_subscriptions_create_item', $subscription, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a subscription.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to create subscription.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} else {
			$retval = $this->validate_request_item( $request );
		}

		/**
		 * Filter the subscriptions `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_subscriptions_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a subscription.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/subscription/:id Get Subscription
	 * @apiName        GetBBSubscription
	 * @apiGroup       Subscriptions
	 * @apiDescription Retrieve single subscription
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the Subscription.
	 */
	public function get_item( $request ) {
		$subscription = $this->get_subscription_object( $request );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $subscription, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a subscription is fetched via the REST API.
		 *
		 * @param BB_Subscriptions $subscription Fetched subscription.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bb_rest_subscriptions_get_item', $subscription, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific subscription.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to view subscription.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you cannot view the subscription.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);

			$subscription = $this->get_subscription_object( $request );
			if ( empty( $subscription->id ) ) {
				$retval = new WP_Error(
					'bp_rest_subscription_invalid_id',
					__( 'Invalid subscription ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( $this->can_user_see_or_delete( $subscription ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the subscription `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_subscriptions_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a subscription.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/subscription/:id Delete Subscription
	 * @apiName        DeleteBBSubscription
	 * @apiGroup       Subscriptions
	 * @apiDescription Delete a subscription.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Subscription.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the subscription before it's deleted.
		$subscription = $this->get_subscription_object( $request );
		$previous     = $this->prepare_item_for_response( $subscription, $request );

		if ( ! bb_delete_subscription( $subscription->id ) ) {
			$error = __( 'There was a problem unsubscribing.', 'buddyboss' );
			if ( isset( $previous->data, $previous->data['title'] ) && ! empty( $previous->data['title'] ) ) {
				$error = sprintf(
				/* translators: %s is forum/discussion title */
					__( 'There was a problem unsubscribing from %s', 'buddyboss' ),
					$previous->data['title']
				);
			}
			return new WP_Error(
				'bp_rest_subscription_cannot_delete',
				$error,
				array(
					'status' => 500,
				)
			);
		}

		$response_array = array(
			'deleted'  => true,
			'previous' => $previous->get_data(),
		);

		$type        = $request->get_param( 'type' );
		$total_pages = (int) $request->get_param( 'total_pages' );
		if ( ! empty( $type ) && 1 < $total_pages ) {
			$args = array(
				'type'     => $type,
				'per_page' => (int) $request->get_param( 'per_page' ),
				'page'     => (int) $request->get_param( 'page' ),
				'user_id'  => bp_loggedin_user_id(),
				'count'    => true,
			);

			$subscriptions = bb_get_subscriptions( $args );
			$total_items   = (int) $subscriptions['total'];
			$max_pages     = ceil( $total_items / (int) $args['per_page'] );

			$new_page = $args['page'];
			if ( 1 === (int) $max_pages ) {
				$new_page = 1;
			} elseif ( empty( $subscriptions['subscriptions'] ) && $args['page'] === $max_pages ) {
				$new_page = $args['page'] - 1;
			}

			$response_array = array_merge(
				$response_array,
				array(
					'type'        => $type,
					'total_pages' => (int) $max_pages,
					'per_page'    => (int) $request->get_param( 'per_page' ),
					'page'        => (int) $new_page,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data( $response_array );

		/**
		 * Fires after a subscription is deleted via the REST API.
		 *
		 * @param object           $subscription The deleted subscription.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bb_rest_subscriptions_delete_item', $subscription, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a subscription.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this subscription.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this subscription.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);

			$subscription = $this->get_subscription_object( $request );

			if ( empty( $subscription->id ) ) {
				$retval = new WP_Error(
					'bp_rest_subscription_invalid_id',
					__( 'Invalid subscription ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( $this->can_user_see_or_delete( $subscription ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the subscription `delete_item` permissions check.
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 */
		return apply_filters( 'bb_rest_subscriptions_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve subscriptions types.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of subscription types object data.
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/subscription-types Get Subscription types
	 * @apiName        GetBBSubscriptionTypes
	 * @apiGroup       Subscriptions
	 * @apiDescription Retrieve subscription Types
	 * @apiVersion     1.0.0
	 */
	public function get_type_items( $request ) {
		$subscription_types = bb_get_subscriptions_types();

		$retval = array();
		foreach ( $subscription_types as $key => $type ) {
			$data        = new stdClass();
			$data->type  = $key;
			$data->label = $type;
			$retval[]    = $this->prepare_response_for_collection(
				$this->prepare_type_item_for_response( $data, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, count( $subscription_types ), count( $subscription_types ) );

		/**
		 * Fires after a list of subscription type is fetched via the REST API.
		 *
		 * @param array            $subscription_types Fetched subscription types.
		 * @param WP_REST_Response $response           The response data.
		 * @param WP_REST_Request  $request            The request sent to the API.
		 */
		do_action( 'bb_rest_subscriptions_get_type_items', $subscription_types, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to subscription types.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_type_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to view subscription types.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the subscriptions `get_type_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_subscriptions_get_type_items_permissions_check', $retval, $request );
	}

	/**
	 * Get subscription object.
	 *
	 * @param WP_REST_Request|int|array $request Full details about the request.
	 *
	 * @return bool|BB_Subscriptions
	 */
	public function get_subscription_object( $request ) {
		if ( is_numeric( $request ) ) {
			$subscription_id = $request;
		} else {
			$subscription_id = (int) $request['id'];
		}

		if ( empty( $subscription_id ) ) {
			return false;
		}

		$subscription = bb_subscriptions_get_subscription( $subscription_id );

		if ( empty( $subscription ) || empty( $subscription->id ) ) {
			return false;
		}

		return $subscription;
	}

	/**
	 * Prepare a subscription for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return stdClass|WP_Error Object or WP_Error.
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_subscription = new stdClass();
		$schema                = $this->get_item_schema();
		$subscription          = $this->get_subscription_object( $request );

		// Subscription ID.
		if ( ! empty( $schema['properties']['id'] ) && ! empty( $subscription->id ) ) {
			$prepared_subscription->id = $subscription->id;
		}

		// Subscription blog ID.
		if ( ! empty( $schema['properties']['blog_id'] ) && isset( $request['blog_id'] ) ) {
			$prepared_subscription->blog_id = (int) $request['blog_id'];

			// Fallback on the existing blog ID in case of an update.
		} elseif ( isset( $subscription->blog_id ) && $subscription->blog_id ) {
			$prepared_subscription->blog_id = (int) $subscription->blog_id;
		}

		// Subscription user ID.
		if ( ! empty( $schema['properties']['user_id'] ) && isset( $request['user_id'] ) ) {
			$prepared_subscription->user_id = (int) $request['user_id'];

			// Fallback on the existing user id in case of an update.
		} elseif ( isset( $subscription->user_id ) && $subscription->user_id ) {
			$prepared_subscription->user_id = (int) $subscription->user_id;

			// Fallback on the current user otherwise.
		} else {
			$prepared_subscription->user_id = bp_loggedin_user_id();
		}

		// Subscription type.
		if ( ! empty( $schema['properties']['type'] ) && isset( $request['type'] ) ) {
			$prepared_subscription->type = $request['type'];

			// Fallback on the existing Subscription type in case of an update.
		} elseif ( isset( $subscription->type ) && $subscription->type ) {
			$prepared_subscription->type = $subscription->type;
		}

		// Subscription item ID.
		if ( ! empty( $schema['properties']['item_id'] ) && isset( $request['item_id'] ) ) {
			$prepared_subscription->item_id = (int) $request['item_id'];

			// Fallback on the existing item id in case of an update.
		} elseif ( isset( $subscription->item_id ) && $subscription->item_id ) {
			$prepared_subscription->item_id = (int) $subscription->item_id;
		}

		// Subscription secondary item ID.
		if ( ! empty( $schema['properties']['secondary_item_id'] ) && isset( $request['secondary_item_id'] ) ) {
			$prepared_subscription->secondary_item_id = (int) $request['secondary_item_id'];

			// Fallback on the existing secondary item id in case of an update.
		} elseif ( isset( $subscription->secondary_item_id ) && $subscription->secondary_item_id ) {
			$prepared_subscription->secondary_item_id = (int) $subscription->secondary_item_id;
		}

		// Subscription status.
		if ( ! empty( $schema['properties']['status'] ) && isset( $request['status'] ) ) {
			$prepared_subscription->status = (int) $request['status'];

			// Fallback on the existing status in case of an update.
		} elseif ( isset( $subscription->status ) && $subscription->status ) {
			$prepared_subscription->status = (int) $subscription->status;
		}

		/**
		 * Filters a subscription before it is inserted or updated via the REST API.
		 *
		 * @param stdClass        $prepared_subscription An object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request               Request object.
		 */
		return apply_filters( 'bb_rest_subscriptions_pre_insert_value', $prepared_subscription, $request );
	}

	/**
	 * Prepares subscription data for return as an object.
	 *
	 * @param BB_Subscriptions $item    Subscription object.
	 * @param WP_REST_Request  $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'                => (int) $item->id,
			'blog_id'           => (int) $item->blog_id,
			'user_id'           => (int) $item->user_id,
			'type'              => $item->type,
			'item_id'           => (int) $item->item_id,
			'secondary_item_id' => (int) $item->secondary_item_id,
			'date_recorded'     => bp_rest_prepare_date_response( $item->date_recorded ),
			'status'            => (bool) $item->status,
			'title'             => html_entity_decode( $item->title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
			'description_html'  => $item->description_html,
			'parent_html'       => $item->parent_html,
			'icon'              => $item->icon,
			'link'              => $item->link,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $item, $request ) );

		/**
		 * Filter a subscription value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BB_Subscriptions $item     Subscription object.
		 */
		return apply_filters( 'bb_rest_subscriptions_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepares subscription data for return as an object.
	 *
	 * @param array|object    $item    Subscription type object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_type_item_for_response( $item, $request ) {
		$subscriptions = bb_get_subscriptions(
			array(
				'type'     => array( $item->type ),
				'per_page' => 1,
				'page'     => 1,
				'count'    => true,
			),
			true
		);

		$data = array(
			'type'  => $item->type,
			'label' => $item->label,
			'count' => isset( $subscriptions['total'] ) ? $subscriptions['total'] : 0,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_type_links( $item, $request ) );

		/**
		 * Filter a subscription type value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BB_Subscriptions $item     Subscription object.
		 */
		return apply_filters( 'bb_rest_subscriptions_type_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BB_Subscriptions $item    Subscription object.
	 * @param WP_REST_Request  $request Full details about the request.
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

		if ( bp_is_active( 'forums' ) ) {
			if ( 'forum' === $item->type ) {
				$links['forum'] = array(
					'href'       => rest_url( sprintf( '%s/%s/%d', $this->namespace, 'forums', $item->item_id ) ),
					'embeddable' => true,
				);
			}

			if ( 'topic' === $item->type ) {
				$links['topic'] = array(
					'href'       => rest_url( sprintf( '%s/%s/%d', $this->namespace, 'topics', $item->item_id ) ),
					'embeddable' => true,
				);
			}
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array            $links   The prepared links of the REST response.
		 * @param BB_Subscriptions $item    Subscription object.
		 * @param WP_REST_Request  $request Full details about the request.
		 */
		return apply_filters( 'bb_rest_subscriptions_prepare_links', $links, $item, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object          $item    Subscription type object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_type_links( $item, $request ) {
		$base = '/' . $this->namespace . '/' . $this->rest_base;

		$links = array(
			'options' => array(
				'embeddable' => true,
				'href'       => add_query_arg(
					'type',
					$item->type,
					rest_url( untrailingslashit( $base ) )
				),
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array           $links   The prepared links of the REST response.
		 * @param object          $item    Subscription type object.
		 * @param WP_REST_Request $request Full details about the request.
		 */
		return apply_filters( 'bb_rest_subscriptions_type_prepare_links', $links, $item, $request );
	}

	/**
	 * Get the query params for collections of plugins.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'buddyboss' ),
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
			'default'           => 'date_recorded',
			'type'              => 'string',
			'enum'              => array( 'id', 'type', 'item_id', 'date_recorded' ),
			'sanitize_callback' => 'sanitize_key',
		);

		$params['blog_id'] = array(
			'description'       => __( 'The ID of the current blog site.', 'buddyboss' ),
			'default'           => get_current_blog_id(),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to items with a specific subscription type.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array_keys( bb_get_subscriptions_types() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['item_id'] = array(
			'description'       => __( 'Limit result set to items with a specific prime association ID.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['secondary_id'] = array(
			'description'       => __( 'Limit result set to items with a specific secondary association ID.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Limit result set to active subscriptions or not.', 'buddyboss' ),
			'default'           => 1,
			'type'              => 'integer',
			'enum'              => array( 0, 1 ),
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bb_rest_subscriptions_collection_params', $params );
	}

	/**
	 * Edit the type of the some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'create_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['type']['required']    = true;
			$args['item_id']['required'] = true;

			$args['status']['required']          = true;
			$args['status']['default']           = 1;
			$args['status']['enum']              = array( 0, 1 );
			$args['status']['sanitize_callback'] = 'absint';
			$args['status']['validate_callback'] = 'rest_validate_request_arg';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_subscriptions_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the subscription schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_subscription',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the subscription.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'blog_id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the current blog site.', 'buddyboss' ),
					'type'        => 'integer',
					'default'     => get_current_blog_id(),
				),
				'user_id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the user who created the subscription.', 'buddyboss' ),
					'type'        => 'integer',
					'default'     => bp_loggedin_user_id(),
				),
				'type'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The type of the subscription.', 'buddyboss' ),
					'enum'        => array_keys( bb_get_subscriptions_types() ),
					'type'        => 'string',
				),
				'item_id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of subscription item.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'secondary_item_id' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'ID of the subscription item parent.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'date_created'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The date the Subscription was created, in the site\'s timezone.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'status'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether to check the subscription is active or not.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'title'             => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Subscription item title.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'description_html'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Subscription item description.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'parent_html'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Subscription item parent title.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'icon'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Avatar/Image URLs of the item.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => array(
						'full'  => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Avatar/Image URL with full image size.', 'buddyboss' ),
							'type'        => 'string',
							'format'      => 'uri',
						),
						'thumb' => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Avatar/Image URL with thumb image size.', 'buddyboss' ),
							'type'        => 'string',
							'format'      => 'uri',
						),
					),
				),
				'link'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Subscription item link.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the subscription schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bb_rest_subscriptions_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the subscription type schema, conforming to JSON Schema.
	 *
	 * @return mixed|null
	 */
	public function get_type_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bb_subscription_types',
			'type'       => 'object',
			'properties' => array(
				'type'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The type of the subscription.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'label' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Label of subscription type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'count' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Count of the subscription items.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the subscription types schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bb_rest_subscription_types_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Whether user can able to access the subscription or not.
	 *
	 * @param BB_Subscriptions $subscription Subscription object.
	 *
	 * @return bool
	 */
	public function can_user_see_or_delete( $subscription ) {
		// Check for moderators or if user is subscribed to this item.
		return ( bp_current_user_can( 'bp_moderate' ) || bp_loggedin_user_id() === $subscription->user_id );
	}

	/**
	 * Validate subscription user ID/item ID/secondary item ID.
	 *
	 * @param WP_REST_Request|int|array $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_request_item( $request ) {
		$user_id = $request->get_param( 'user_id' );
		$type    = $request->get_param( 'type' );
		$item_id = $request->get_param( 'item_id' );

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create subscription.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Checked loggedin member.
		if ( bp_loggedin_user_id() !== $user_id ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to create subscription.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		} elseif ( empty( $item_id ) ) {
			$retval = new WP_Error(
				'bp_rest_subscription_required_item_id',
				__( 'The item ID is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( empty( $type ) ) {
			$retval = new WP_Error(
				'bp_rest_subscription_required_item_type',
				__( 'The item type is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			$retval = true;
		}

		/**
		 * Filters the validate request subscription item data.
		 *
		 * @param boolean|WP_Error          $retval  The validate response.
		 * @param WP_REST_Request|int|array $request Full details about the request.
		 */
		return apply_filters( 'bb_rest_subscriptions_validate_request_item', $retval, $request );
	}
}
