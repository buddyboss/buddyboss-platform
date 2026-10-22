<?php
/**
 * BB REST: BB_REST_Topics_Endpoint class
 *
 * @since   2.8.80
 * @package BuddyBoss
 */

defined( 'ABSPATH' ) || exit;

/**
 * Topics endpoints.
 *
 * @since 2.8.80
 */
class BB_REST_Topics_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 2.8.80
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'bb-topics';

		if ( function_exists( 'bb_is_enabled_activity_topics' ) && bb_is_enabled_activity_topics() ) {
			$this->bp_rest_activity_support();
		}
	}

	/**
	 * Register the fields for the activity object.
	 *
	 * @since 2.8.80
	 */
	public function bp_rest_activity_support() {
		bp_rest_register_field(
			'activity',
			'bb_topic',
			array(
				'get_callback' => array( $this, 'bb_topic_name_get_rest_field_callback' ),
				'schema'       => array(
					'description' => 'Topic Name.',
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		bp_rest_register_field(
			'activity',
			'bb_topic_id',
			array(
				'update_callback' => array( $this, 'bb_topic_update_rest_field_callback' ),
				'schema'          => array(
					'description' => 'Topic ID.',
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 2.8.80
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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ),
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
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the topic.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
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
				),
			),
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/order',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_order' ),
					'permission_callback' => array( $this, 'update_order_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( 'update_order' ),
				),
			)
		);
	}

	/**
	 * Get topics.
	 *
	 * @since          2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/bb-topics Get Topics
	 * @apiName        GetTopics
	 * @apiGroup       BB Topics
	 * @apiDescription Get Topics
	 * @apiVersion     1.0.0
	 * @apiPermission  User
	 * @apiParam {String} [orderby=id] Order by attribute.
	 * @apiParam {String} [order=ASC] Order direction.
	 * @apiParam {Number} [group_id] Group ID.
	 */
	public function get_items( $request ) {
		$args = array(
			'per_page' => $request->get_param( 'per_page' ),
			'paged'    => $request->get_param( 'page' ),
			'orderby'  => $request->get_param( 'orderby' ),
			'order'    => $request->get_param( 'order' ),
		);

		// Set the default orderby if empty.
		if ( empty( $args['orderby'] ) ) {
			$args['orderby'] = 'menu_order';
		}

		// Set the default order if empty.
		if ( empty( $args['order'] ) ) {
			$args['order'] = 'ASC';
		}

		if ( ! empty( $request->get_param( 'item_id' ) ) ) {
			$args['item_id'] = $request->get_param( 'item_id' );
		}

		if ( ! empty( $request->get_param( 'item_type' ) ) ) {
			$args['item_type'] = $request->get_param( 'item_type' );
		}

		$args['count_total'] = true;

		$args['fields'] = 'name,slug,topic_id,item_type,permission_type';
		if ( 'groups' === $args['item_type'] && ! empty( $args['item_id'] ) ) {
			$topics_data = function_exists( 'bb_get_group_activity_topics' ) ? bb_get_group_activity_topics( $args ) : array();
		} else {
			$topics_data = bb_activity_topics_manager_instance()->bb_get_activity_topics( $args );
		}
		$topics_count = ! empty( $topics_data['total'] ) ? $topics_data['total'] : 0;
		$topics       = ! empty( $topics_data['topics'] ) ? $topics_data['topics'] : array();

		$data = array();
		if ( ! empty( $topics ) ) {
			foreach ( $topics as $topic ) {
				if ( ! empty( $topic ) ) {
					$data[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( (object) $topic, $request ) );
				}
			}
		}

		$response = rest_ensure_response( $data );
		$response = bp_rest_response_add_total_headers( $response, $topics_count, $request->get_param( 'per_page' ) );

		/**
		 * Fires after the topics were fetched via the REST API.
		 *
		 * @since 2.8.80
		 *
		 * @param array            $topics   Fetched topics.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_topics_get_items', $topics, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to view topics.
	 *
	 * @since 2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean|WP_Error True, if the request has access, WP_Error otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$item_type = $request->get_param( 'item_type' );
		$retval    = false;
		if ( 'activity' === $item_type ) {
			$retval = true;
		} elseif ( 'groups' === $item_type && bp_is_active( 'groups' ) ) {
			$retval  = true;
			$item_id = $request->get_param( 'item_id' );
			$group   = ! empty( $item_id ) ? groups_get_group( $item_id ) : false;
			if (
				! function_exists( 'bb_is_enabled_group_activity_topics' ) ||
				! bb_is_enabled_group_activity_topics()
			) {
				$retval = new WP_Error( 'bp_rest_access_denied', __( 'You cannot view topics.', 'buddyboss' ), array( 'status' => 403 ) );
			} elseif ( empty( $item_id ) ) {
				$retval = new WP_Error( 'bp_rest_topic_item_id_required', __( 'Item ID is required.', 'buddyboss' ), array( 'status' => 400 ) );
			} elseif ( ! $group->id ) {
				$retval = new WP_Error( 'bp_rest_group_invalid_id', __( 'Invalid group ID.', 'buddyboss' ), array( 'status' => 404 ) );
			} elseif ( ! bp_current_user_can( 'administrator' ) && 'public' !== $group->status ) {
				if (
					! groups_is_user_member( bp_loggedin_user_id(), $item_id ) &&
					! groups_is_user_mod( bp_loggedin_user_id(), $item_id ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $item_id )
				) {
					$retval = new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot view topics for this group.', 'buddyboss' ), array( 'status' => 403 ) );
				}
			}
		}

		/**
		 * Filter the topic `get_items` permissions check.
		 *
		 * @since 2.8.80
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_topics_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Create a topic.
	 *
	 * @since          2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/bb-topics Create Topic
	 * @apiName        CreateTopic
	 * @apiGroup       BB Topics
	 * @apiDescription Create a new topic
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} title Topic title.
	 * @apiParam {Number} [user_id] User ID who created the topic. Defaults to logged in user.
	 * @apiParam {String} permission_type Permission type for the topic.
	 * @apiParam {String} [permission_data] Permission data for the topic.
	 * @apiParam {Number} [menu_order] Order value for the topic.
	 * @apiParam {Number} [item_id] Item ID (Group ID for groups, for activity topics it is not required).
	 * @apiParam {String} [item_type] Item type (groups or activity).
	 */
	public function create_item( $request ) {
		$args = array(
			'name'            => sanitize_text_field( $request->get_param( 'title' ) ),
			'user_id'         => $request->get_param( 'user_id' ) ? absint( $request->get_param( 'user_id' ) ) : bp_loggedin_user_id(),
			'permission_type' => sanitize_text_field( $request->get_param( 'permission_type' ) ),
			'permission_data' => $request->get_param( 'permission_data' ),
			'menu_order'      => $request->get_param( 'menu_order' ) ? absint( $request->get_param( 'menu_order' ) ) : 0,
			'item_type'       => $request->get_param( 'item_type' ),
			'error_type'      => 'wp_error',
		);

		if ( ! empty( $request->get_param( 'item_id' ) ) ) {
			$args['item_id'] = $request->get_param( 'item_id' );
		}

		// Create the topic.
		$topic_relationship = bb_topics_manager_instance()->bb_add_topic( $args );

		if ( is_wp_error( $topic_relationship ) ) {
			return $topic_relationship;
		}

		if ( empty( $topic_relationship ) ) {
			return new WP_Error(
				'bp_rest_topic_creation_failed',
				__( 'Failed to create the topic.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $topic_relationship, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an topic is created via the REST API.
		 *
		 * @since 2.8.80
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_topic_create_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a topic.
	 *
	 * @since 2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to create a topic, WP_Error otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create topics.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$name            = sanitize_text_field( $request->get_param( 'title' ) );
			$permission_type = $request->get_param( 'permission_type' );
			$item_type       = $request->get_param( 'item_type' );
			$item_id         = $request->get_param( 'item_id' );

			if ( empty( $name ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_name_required',
					__( 'Topic name is required.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( empty( $permission_type ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_permission_type_required',
					__( 'Permission type is required.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			} elseif ( empty( $item_type ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_item_type_required',
					__( 'Item type is required.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( bp_is_active( 'groups' ) && 'groups' === $item_type ) {
				$group                   = ! empty( $item_id ) ? groups_get_group( $item_id ) : false;
				$valid_group_permissions = array( 'admins', 'mods', 'members' );
				$group_topics_options    = function_exists( 'bb_get_group_activity_topic_options' ) ? bb_get_group_activity_topic_options() : '';
				if (
					! function_exists( 'bb_is_enabled_group_activity_topics' ) ||
					! bb_is_enabled_group_activity_topics()
				) {
					$retval = new WP_Error(
						'bp_rest_access_denied',
						__( 'You cannot create a topic.', 'buddyboss' ),
						array( 'status' => 403 )
					);
				} elseif ( empty( $item_id ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_item_id_required',
						__( 'Item ID is required.', 'buddyboss' ),
						array( 'status' => 400 )
					);
				} elseif ( ! in_array( $permission_type, $valid_group_permissions, true ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_invalid_permission_type',
						__( 'Invalid permission type for group topics. Must be one of: admins, mods, members', 'buddyboss' ),
						array( 'status' => 400 )
					);
				} elseif ( ! $group->id ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddyboss' ),
						array( 'status' => 404 )
					);
				} elseif (
					! bp_current_user_can( 'administrator' ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $item_id )
				) {
					$retval = new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot create a topic for this group.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( 'only_from_activity_topics' === $group_topics_options ) {
					$name = bb_topics_manager_instance()->bb_get_topic_by( 'name', $name );
					if ( empty( $name ) ) {
						$retval = new WP_Error( 'bp_rest_topic_name_not_found', __( 'Topic name not found in activity topics.', 'buddyboss' ), array( 'status' => 400 ) );
					}
				}
			} elseif ( 'activity' === $item_type ) {
				$valid_global_permissions = array( 'mods_admins', 'anyone' );
				if ( ! empty( $item_id ) ) {
					$retval = new WP_Error( 'bp_rest_topic_item_id_not_required', __( 'Item ID is not required for activity topics.', 'buddyboss' ), array( 'status' => 400 ) );
				} elseif ( ! bp_current_user_can( 'administrator' ) ) {
					$retval = new WP_Error( 'bp_rest_topic_invalid_permission_type', __( 'You are not allowed to create a topic.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( ! in_array( $permission_type, $valid_global_permissions, true ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_invalid_permission_type',
						__( 'Invalid permission type for global topics. Must be one of: mods_admins, anyone', 'buddyboss' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		/**
		 * Filter the topic `create_item` permissions check.
		 *
		 * @since 2.8.80
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_topics_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Get a topic.
	 *
	 * @since          2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/bb-topics/:id Get Topic
	 * @apiName        GetTopic
	 * @apiGroup       BB Topics
	 * @apiDescription Get Topic
	 * @apiVersion     1.0.0
	 * @apiPermission  User
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 */
	public function get_item( $request ) {
		$id = $request->get_param( 'id' );

		if ( empty( $id ) ) {
			return new WP_Error( 'bp_rest_topic_invalid_id', __( 'Invalid topic ID.', 'buddyboss' ), array( 'status' => 404 ) );
		}

		$args = array(
			'topic_id' => $id,
		);

		$topic = bb_topics_manager_instance()->bb_get_topic( $args );

		if ( empty( $topic ) ) {
			return new WP_Error( 'bp_rest_topic_empty', __( 'Topic not found.', 'buddyboss' ), array( 'status' => 404 ) );
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $topic, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an topic is fetched via the REST API.
		 *
		 * @since 2.8.80
		 *
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  The request sent to the API.
		 *
		 * @param BB_Topics_Manager $topic    Fetched topic.
		 */
		do_action( 'bp_rest_topic_get_item', $topic, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to view a topic.
	 *
	 * @since 2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean|WP_Error True, if the request has access, WP_Error otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$id        = $request->get_param( 'id' );
		$topic     = ! empty( $id ) ? bb_topics_manager_instance()->bb_get_topic( array( 'topic_id' => $id ) ) : false;
		$item_type = ! empty( $topic->item_type ) ? $topic->item_type : false;

		$retval = false;
		if ( empty( $id ) ) {
			$retval = new WP_Error( 'bp_rest_topic_invalid_id', __( 'Invalid topic ID.', 'buddyboss' ), array( 'status' => 404 ) );
		} elseif ( empty( $topic ) ) {
			$retval = new WP_Error( 'bp_rest_topic_empty', __( 'Topic not found.', 'buddyboss' ), array( 'status' => 404 ) );
		} elseif ( 'activity' === $item_type ) {
			$retval = true;
		} elseif ( 'groups' === $item_type && bp_is_active( 'groups' ) ) {
			$retval = true;
			$group  = ! empty( $topic->item_id ) ? groups_get_group( $topic->item_id ) : false;
			if (
				! function_exists( 'bb_is_enabled_group_activity_topics' ) ||
				! bb_is_enabled_group_activity_topics()
			) {
				$retval = new WP_Error( 'bp_rest_access_denied', __( 'You cannot view topics.', 'buddyboss' ), array( 'status' => 403 ) );
			} elseif ( empty( $topic->item_id ) ) {
				$retval = new WP_Error( 'bp_rest_topic_item_id_required', __( 'Item ID is required.', 'buddyboss' ), array( 'status' => 400 ) );
			} elseif ( empty( $group->id ) ) {
				$retval = new WP_Error( 'bp_rest_group_invalid_id', __( 'Invalid group ID.', 'buddyboss' ), array( 'status' => 404 ) );
			} elseif ( ! bp_current_user_can( 'administrator' ) && 'public' !== $group->status ) {
				// Check if user has any level of access (member, mod, or admin).
				if (
					! groups_is_user_member( bp_loggedin_user_id(), $topic->item_id ) &&
					! groups_is_user_mod( bp_loggedin_user_id(), $topic->item_id ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $topic->item_id )
				) {
					$retval = new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot view topics for this group.', 'buddyboss' ), array( 'status' => 403 ) );
				}
			}
		}

		/**
		 * Filter the topic `get_item` permissions check.
		 *
		 * @since 2.8.80
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_topics_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a topic.
	 *
	 * @since          2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @api            {PUT} /wp-json/buddyboss/v1/bb-topics/:id Update Topic
	 * @apiName        UpdateTopic
	 * @apiGroup       BB Topics
	 * @apiDescription Update an existing topic
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 * @apiParam {String} [title] The title of the topic.
	 * @apiParam {String} [permission_type] Permission type for the topic.
	 * @apiParam {String} [permission_data] Permission data for the topic.
	 * @apiParam {Number} [item_id] Item ID (Group ID for groups, for activity topics it is not required).
	 * @apiParam {String} [item_type] Item type (groups or activity).
	 */
	public function update_item( $request ) {
		$topic_id = $request->get_param( 'id' );
		if ( empty( $topic_id ) ) {
			return new WP_Error(
				'bp_rest_topic_invalid_id',
				__( 'Invalid topic ID.', 'buddyboss' ),
				array( 'status' => 404 )
			);
		}

		$item_id        = $request->get_param( 'item_id' );
		$item_type      = $request->get_param( 'item_type' );
		$get_topic_args = array(
			'topic_id' => $topic_id,
		);
		if ( ! empty( $item_id ) ) {
			$get_topic_args['item_id'] = $item_id;
		}

		if ( ! empty( $item_type ) ) {
			$get_topic_args['item_type'] = $item_type;
		}

		$topic = bb_topics_manager_instance()->bb_get_topic( $get_topic_args );
		if ( ! $topic ) {
			return new WP_Error(
				'bp_rest_topic_invalid_id',
				__( 'Invalid topic ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$args = array(
			'id'              => $topic_id,
			'name'            => $request->get_param( 'title' ) ? sanitize_text_field( $request->get_param( 'title' ) ) : $topic->name,
			'permission_type' => $request->get_param( 'permission_type' ) ? sanitize_text_field( $request->get_param( 'permission_type' ) ) : $topic->permission_type,
			'item_id'         => $item_id ? absint( $item_id ) : $topic->item_id,
			'item_type'       => $item_type ? sanitize_text_field( $item_type ) : $topic->item_type,
			'error_type'      => 'wp_error',
		);

		if ( ! empty( $args['name'] ) ) {
			$args['slug'] = sanitize_title( $args['name'] );
		}

		// Update the topic.
		$updated_topic = bb_topics_manager_instance()->bb_update_topic( $args );

		if ( is_wp_error( $updated_topic ) ) {
			return $updated_topic;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $updated_topic, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a topic is updated via the REST API.
		 *
		 * @since 2.8.80
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_topic_update_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a topic.
	 *
	 * @since 2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update a topic, WP_Error otherwise.
	 */
	public function update_item_permissions_check( $request ) {

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create topics.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$name            = sanitize_text_field( $request->get_param( 'title' ) );
			$item_id         = $request->get_param( 'item_id' );
			$item_type       = $request->get_param( 'item_type' );
			$topic_id        = $request->get_param( 'id' );
			$permission_type = $request->get_param( 'permission_type' );

			// Get the topic.
			$get_topic_args = array(
				'topic_id' => $topic_id,
			);
			if ( ! empty( $item_id ) ) {
				$get_topic_args['item_id'] = $item_id;
			}
			if ( ! empty( $item_type ) ) {
				$get_topic_args['item_type'] = $item_type;
			}
			$topic = bb_topics_manager_instance()->bb_get_topic( $get_topic_args );

			if ( empty( $name ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_name_required',
					__( 'Topic name is required.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( empty( $item_type ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_item_type_required',
					__( 'Item type is required.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( bp_is_active( 'groups' ) && ! empty( $item_type ) && 'groups' === $item_type ) {
				$group                = ! empty( $item_id ) ? groups_get_group( $item_id ) : false;
				$group_topics_options = function_exists( 'bb_get_group_activity_topic_options' ) ? bb_get_group_activity_topic_options() : '';
				if (
					! function_exists( 'bb_is_enabled_group_activity_topics' ) ||
					! bb_is_enabled_group_activity_topics()
				) {
					$retval = new WP_Error( 'bp_rest_access_denied', __( 'You cannot update a topic.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( empty( $item_id ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_item_id_required',
						__( 'Item ID is required.', 'buddyboss' ),
						array( 'status' => 400 )
					);
				} elseif ( empty( $group->id ) ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddyboss' ),
						array( 'status' => 404 )
					);
				} elseif (
					! bp_current_user_can( 'administrator' ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $item_id )
				) {
					$retval = new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot create a topic for this group.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( 'only_from_activity_topics' === $group_topics_options ) {
					$name = bb_topics_manager_instance()->bb_get_topic_by( 'name', $name );
					if ( empty( $name ) ) {
						$retval = new WP_Error( 'bp_rest_topic_name_not_found', __( 'Topic name not found in activity topics.', 'buddyboss' ), array( 'status' => 400 ) );
					}
				} elseif ( empty( $topic ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_invalid_id',
						__( 'Invalid topic ID.', 'buddyboss' ),
						array(
							'status' => 404,
						)
					);
				} else {
					$valid_permissions = array( 'admins', 'mods', 'members' );
					$permission_type   = empty( $permission_type ) ? $topic->permission_type : $permission_type;
					if ( ! empty( $valid_permissions ) && ! in_array( $permission_type, $valid_permissions, true ) ) {
						$retval = new WP_Error(
							'bp_rest_topic_invalid_permission_type',
							sprintf(
							/* translators: %s: Valid permission types for group topics. */
								__( 'Invalid permission type for group topics. Must be one of: %s', 'buddyboss' ),
								implode( ', ', $valid_permissions )
							),
							array( 'status' => 400 )
						);
					} elseif (
						$topic->is_global_activity &&
						! empty( $title ) &&
						$title !== $topic->name
					) {
						$retval = new WP_Error(
							'bp_rest_topic_global_activity_update_error',
							__( 'You cannot assign or update a global topic under a group.', 'buddyboss' ),
							array( 'status' => 403 )
						);
					}
				}
			} elseif ( 'activity' === $item_type ) {
				if ( ! empty( $item_id ) ) {
					$retval = new WP_Error( 'bp_rest_topic_item_id_not_required', __( 'Item ID is not required for activity topics.', 'buddyboss' ), array( 'status' => 400 ) );
				} elseif ( ! bp_current_user_can( 'administrator' ) ) {
					$retval = new WP_Error( 'bp_rest_topic_invalid_permission_type', __( 'You are not allowed to create a topic.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( empty( $topic ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_invalid_id',
						__( 'Invalid topic ID.', 'buddyboss' ),
						array(
							'status' => 404,
						)
					);
				} else {
					$valid_permissions = array( 'mods_admins', 'anyone' );
					$permission_type   = empty( $permission_type ) ? $topic->permission_type : $permission_type;
					if ( ! empty( $valid_permissions ) && ! in_array( $permission_type, $valid_permissions, true ) ) {
						$retval = new WP_Error(
							'bp_rest_topic_invalid_permission_type',
							sprintf(
							/* translators: %s: Valid permission types for global topics. */
								__( 'Invalid permission type for group topics. Must be one of: %s', 'buddyboss' ),
								implode( ', ', $valid_permissions )
							),
							array( 'status' => 400 )
						);
					}
				}
			}
		}

		/**
		 * Filter the topic `update_item` permissions check.
		 *
		 * @since 2.8.80
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_topics_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a topic.
	 *
	 * @since          2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/bb-topics/{id} Delete Topic
	 * @apiName        DeleteTopic
	 * @apiGroup       BB Topics
	 * @apiDescription Delete an existing topic
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id Topic ID.
	 * @apiParam {Number} [item_id] Item ID (Group ID for groups, for activity topics it is not required).
	 * @apiParam {String} [item_type] Item type (groups or activity).
	 * @apiParam {String} [migrate_type] Migrate type (migrate or delete).
	 * @apiParam {Number} [new_topic_id] New topic ID when migrate type is migrate.
	 */
	public function delete_item( $request ) {
		$topic_id     = $request->get_param( 'id' );
		$item_id      = $request->get_param( 'item_id' );
		$item_type    = $request->get_param( 'item_type' );
		$migrate_type = $request->get_param( 'migrate_type' );

		if ( ! empty( $migrate_type ) && 'migrate' !== $migrate_type ) {
			$retval = new WP_Error(
				'bp_rest_topic_invalid_migrate_type',
				__( 'Invalid migrate type. Value must be "migrate".', 'buddyboss' ),
				array( 'status' => 400 )
			);
			return $retval;
		}

		$new_topic_id = $request->get_param( 'new_topic_id' );

		$args = array(
			'topic_id' => $topic_id,
			'fields'   => 'all',
		);
		if ( 'activity' === $item_type ) {
			$args['item_type'] = 'activity';
		} elseif ( 'groups' === $item_type ) {
			$args['item_id']   = $item_id;
			$args['item_type'] = $item_type;
		}

		$get_topic = bb_topics_manager_instance()->bb_get_topics( $args );
		$get_topic = ! empty( $get_topic['topics'] ) ? current( $get_topic['topics'] ) : false;

		unset( $args['fields'] );

		if ( 'migrate' === $migrate_type && ! empty( $new_topic_id ) ) {
			$args['new_topic_id'] = $new_topic_id;
			$args['migrate_type'] = $migrate_type;
			$migrated             = bb_topics_manager_instance()->bb_migrate_topic( $args );
			if ( is_wp_error( $migrated ) ) {
				return new WP_Error(
					'bp_rest_topic_migrate_failed',
					__( 'There was an error while migrating the topic.', 'buddyboss' ),
					array( 'status' => 500 )
				);
			}
		}
		$deleted = bb_topics_manager_instance()->bb_delete_topic( $args );
		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_topic_delete_failed',
				__( 'There was an error while deleting the topic.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $this->prepare_item_for_response( $get_topic, $request ),
			)
		);

		/**
		 * Fires after an topic is deleted via the REST API.
		 *
		 * @since 2.8.80
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_topic_delete_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a topic.
	 *
	 * @since 2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete topics.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$topic_id     = $request->get_param( 'id' );
			$item_id      = $request->get_param( 'item_id' );
			$item_type    = $request->get_param( 'item_type' );
			$migrate_type = $request->get_param( 'migrate_type' );
			$new_topic_id = $request->get_param( 'new_topic_id' );

			$args = array(
				'topic_id' => $topic_id,
				'fields'   => 'id',
			);
			if ( 'groups' === $item_type ) {
				$args['item_id']   = $item_id;
				$args['item_type'] = $item_type;
			}
			$get_topic = bb_topics_manager_instance()->bb_get_topics( $args );

			$new_topic = bb_topics_manager_instance()->bb_get_topic(
				array(
					'topic_id' => $new_topic_id,
					'fields'   => 'id',
				)
			);

			if ( ! empty( $migrate_type ) && 'migrate' !== $migrate_type ) {
				$retval = new WP_Error(
					'bp_rest_topic_invalid_migrate_type',
					__( 'Invalid migrate type. Value must be "migrate".', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( empty( $topic_id ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_invalid_id',
					__( 'Invalid topic ID.', 'buddyboss' ),
					array( 'status' => 404 )
				);
			} elseif ( empty( $item_type ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_item_type_required',
					__( 'Item type is required.', 'buddyboss' ),
					array( 'status' => 404 )
				);
			} elseif ( empty( $get_topic['topics'] ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_not_found',
					__( 'Topic not found.', 'buddyboss' ),
					array( 'status' => 404 )
				);
			} elseif ( 'migrate' === $migrate_type && empty( $new_topic_id ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_invalid_new_topic_id',
					__( 'Invalid new topic ID.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( 'migrate' === $migrate_type && ! empty( $new_topic_id ) && empty( $new_topic ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_invalid_new_topic_data',
					__( 'Invalid new topic data.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( bp_is_active( 'groups' ) && ! empty( $item_type ) && 'groups' === $item_type ) {
				$item_id = ! empty( $item_id ) ? $item_id : 0;
				$group   = ! empty( $item_id ) ? groups_get_group( $item_id ) : false;
				if (
					! function_exists( 'bb_is_enabled_group_activity_topics' ) ||
					! bb_is_enabled_group_activity_topics()
				) {
					$retval = new WP_Error( 'bp_rest_access_denied', __( 'You cannot delete a topic.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( empty( $item_id ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_item_id_required',
						__( 'Item ID is required.', 'buddyboss' ),
						array( 'status' => 400 )
					);
				} elseif ( empty( $group ) ) {
					$retval = new WP_Error( 'bp_rest_group_invalid_id', __( 'Invalid group ID.', 'buddyboss' ), array( 'status' => 404 ) );
				} elseif (
					! bp_current_user_can( 'administrator' ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $item_id )
				) {
					$retval = new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot delete a topic for this group.', 'buddyboss' ), array( 'status' => 403 ) );
				}
			} elseif ( 'activity' === $item_type ) {
				if ( ! empty( $item_id ) ) {
					$retval = new WP_Error( 'bp_rest_topic_item_id_not_required', __( 'Item ID is not required for activity topics.', 'buddyboss' ), array( 'status' => 400 ) );
				} elseif ( ! bp_current_user_can( 'administrator' ) ) {
					$retval = new WP_Error( 'bp_rest_topic_invalid_permission_type', __( 'Sorry, you are not allowed to delete topics.', 'buddyboss' ), array( 'status' => 403 ) );
				}
			}
		}

		/**
		 * Filter the topic `delete_item` permissions check.
		 *
		 * @since 2.8.80
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_topics_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Update the order of topics.
	 *
	 * @since          2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 *
	 * @api            {PUT} /wp-json/buddyboss/v1/bb-topics/order Update Topics Order
	 * @apiName        UpdateTopicsOrder
	 * @apiGroup       BB Topics
	 * @apiDescription Update the order of topics
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} item_id Item ID (Group ID for groups, for activity topics it is not required).
	 * @apiParam {String} item_type Item type (groups or activity).
	 *
	 * @apiParam {Array} topics Array of topic IDs in the desired order.
	 * @apiParam {Number} topics[].topic_id Topic ID.
	 * @apiParam {Number} topics[].menu_order Menu order.
	 */
	public function update_order( $request ) {
		$group     = $request->get_param( 'item_id' );
		$item_type = $request->get_param( 'item_type' );
		$topics    = $request->get_param( 'topics' );

		$success = true;
		$wpdb    = $GLOBALS['wpdb'];

		// Update each topic with its new order in the relationships table.
		foreach ( $topics as $topic ) {
			$update_data = array( 'menu_order' => $topic['menu_order'] );
			if ( 'groups' === $item_type ) {
				$where_data = array(
					'topic_id'  => $topic['topic_id'],
					'item_id'   => $group,
					'item_type' => $item_type,
				);
				$format     = array( '%d', '%d', '%s' );
			} elseif ( 'activity' === $item_type ) {
				$where_data = array(
					'topic_id'  => $topic['topic_id'],
					'item_type' => $item_type,
				);
				$format     = array( '%d', '%s' );
			}

			// phpcs:ignore
			$result = $wpdb->update(
				bb_topics_manager_instance()->topic_rel_table,
				$update_data,
				$where_data,
				array( '%d' ),
				$format
			);

			if ( false === $result ) {
				$success = false;
				break;
			}
		}

		if ( $success ) {
			$get_topics = bb_topics_manager_instance()->bb_get_topics(
				array(
					'item_id'   => $group,
					'item_type' => $item_type,
				)
			);

			$data = array();
			if ( ! empty( $get_topics['topics'] ) ) {
				foreach ( $get_topics['topics'] as $topic ) {
					if ( ! empty( $topic ) ) {
						$data[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( (object) $topic, $request ) );
					}
				}
			}

			$response = new WP_REST_Response();
			$response->set_data( $data );

			/**
			 * Fires after topics order is updated via the REST API.
			 *
			 * @since 2.8.80
			 *
			 * @param WP_REST_Response $response The response data.
			 * @param WP_REST_Request  $request  The request sent to the API.
			 */
			do_action( 'bp_rest_topics_update_order', $response, $request );

			return $response;
		}

		return new WP_Error(
			'bp_rest_topic_order_update_failed',
			__( 'Failed to update topic order.', 'buddyboss' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Check if a given request has access to update topics order.
	 *
	 * @since 2.8.80
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update topics order, WP_Error otherwise.
	 */
	public function update_order_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create topics.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$item_id   = $request->get_param( 'item_id' );
			$item_type = $request->get_param( 'item_type' );
			$topics    = $request->get_param( 'topics' );

			if ( empty( $item_type ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_item_type_required',
					__( 'Item type is required.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( empty( $topics ) || ! is_array( $topics ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_invalid_order',
					__( 'Invalid topic order data.', 'buddyboss' ),
					array( 'status' => 400 )
				);
			} elseif ( bp_is_active( 'groups' ) && ! empty( $item_type ) && 'groups' === $item_type ) {
				$group = ! empty( $item_id ) ? groups_get_group( $item_id ) : false;

				if (
					! function_exists( 'bb_is_enabled_group_activity_topics' ) ||
					! bb_is_enabled_group_activity_topics()
				) {
					$retval = new WP_Error( 'bp_rest_access_denied', __( 'You cannot update topics order.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( empty( $item_id ) ) {
					$retval = new WP_Error(
						'bp_rest_topic_item_id_required',
						__( 'Item ID is required.', 'buddyboss' ),
						array( 'status' => 400 )
					);
				} elseif ( empty( $group->id ) ) {
					$retval = new WP_Error(
						'bp_rest_group_invalid_id',
						__( 'Invalid group ID.', 'buddyboss' ),
						array( 'status' => 404 )
					);
				} elseif (
					! bp_current_user_can( 'administrator' ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $item_id )
				) {
					$retval = new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot allow to order topics for this group.', 'buddyboss' ), array( 'status' => 403 ) );
				}
			} elseif ( 'activity' === $item_type ) {
				if ( ! bp_current_user_can( 'administrator' ) ) {
					$retval = new WP_Error( 'bp_rest_topic_invalid_permission_type', __( 'You are not allowed to update order of topics.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif ( ! empty( $item_id ) ) {
					$retval = new WP_Error( 'bp_rest_topic_item_id_not_required', __( 'Item ID is not required for activity topics.', 'buddyboss' ), array( 'status' => 400 ) );
				}
			} else {
				$topic_ids = array_map(
					function ( $topic ) {
						return $topic['topic_id'];
					},
					$topics
				);

				foreach ( $topic_ids as $topic_id ) {
					$topic = bb_topics_manager_instance()->bb_get_topic(
						array(
							'topic_id'  => $topic_id,
							'item_type' => $item_type,
						)
					);

					if ( empty( $topic ) ) {
						$retval = new WP_Error(
							'bp_rest_topic_not_found',
							__( 'One or more topics not found.', 'buddyboss' ),
							array( 'status' => 404 )
						);
					}
				}
			}
		}

		/**
		 * Filter the topics order update permissions check.
		 *
		 * @since 2.8.80
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_topics_update_order_permissions_check', $retval, $request );
	}

	/**
	 * Get the endpoint arguments for the item schema.
	 *
	 * @since 2.8.80
	 *
	 * @param string $method Optional. HTTP method of the request. Default 'GET'.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = array(); // Initialize the empty array.
		$key  = 'get_item';

		// Common item_type argument definition.
		$item_type_arg = array(
			'description'       => __( 'Type of item.', 'buddyboss' ),
			'type'              => array( 'string', 'array' ),
			'validate_callback' => function ( $param ) {
				$allowed = array( 'activity', 'groups' );
				if ( is_array( $param ) ) {
					foreach ( $param as $value ) {
						if ( ! in_array( $value, $allowed, true ) ) {
							return false;
						}
					}

					return true;
				}

				return in_array( $param, $allowed, true );
			},
			'sanitize_callback' => function ( $param ) {
				return $param;
			},
		);

		// Common arguments for create and update.
		$common_args = array(
			'title'     => array(
				'description' => __( 'The title of the topic.', 'buddyboss' ),
				'type'        => 'string',
				'required'    => true,
			),
			'item_type' => array_merge( $item_type_arg, array( 'required' => true ) ),
		);

		if ( WP_REST_Server::READABLE === $method ) {
			$key = 'get_items';

			// Add get_items specific args.
			$args = array(
				'per_page'     => array(
					'description'       => __( 'Number of items to return per page.', 'buddyboss' ),
					'type'              => 'integer',
					'default'           => 20,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'page'         => array(
					'description'       => __( 'Current page of the collection.', 'buddyboss' ),
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'orderby'      => array(
					'description'       => __( 'Order by attribute.', 'buddyboss' ),
					'type'              => 'string',
					'default'           => 'menu_order',
					'enum'              => array( 'id', 'name', 'date_created', 'menu_order', 'date_updated' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'order'        => array(
					'description'       => __( 'Order direction.', 'buddyboss' ),
					'type'              => 'string',
					'default'           => 'ASC',
					'enum'              => array( 'ASC', 'DESC' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'item_id'      => array(
					'description'       => __( 'Item ID.', 'buddyboss' ),
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'item_type'    => $item_type_arg,
				'filter_query' => array(
					'description'       => __( 'Whether to filter the query.', 'buddyboss' ),
					'type'              => 'boolean',
					'default'           => false,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
				),
			);
		}

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			// Merge common args with create-specific args.
			$args = array_merge(
				$args,
				$common_args,
				array(
					'permission_type' => array(
						'description' => __( 'The permission type of the topic.', 'buddyboss' ),
						'type'        => 'string',
						'required'    => true,
					),
				)
			);
		}

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			// Merge common args with update-specific args.
			$args = array_merge( $args, $common_args );
		}

		// Add update_order specific args.
		if ( 'update_order' === $method ) {
			$args = array(
				'item_type' => array_merge( $item_type_arg, array( 'required' => true ) ),
				'topics'    => array(
					'description' => __( 'Array of topics with their IDs.', 'buddyboss' ),
					'type'        => 'array',
					'required'    => true,
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name'     => array(
								'type' => 'string',
							),
							'topic_id' => array(
								'type' => 'string',
							),
						),
					),
				),
			);
		}

		if ( 'delete_item' === $method ) {
			$args = array(
				'id'        => array(
					'description'       => __( 'The ID of the topic.', 'buddyboss' ),
					'type'              => 'integer',
					'sanitize_callback' => 'absint',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'item_type' => array_merge( $item_type_arg, array( 'required' => true ) ),
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 2.8.80
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_topics_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepare a topic for response.
	 *
	 * @since 2.8.80
	 *
	 * @param stdClass        $item    Topic object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'name'     => $item->name,
			'slug'     => $item->slug,
			'topic_id' => $item->topic_id,
		);

		$data['is_global_activity'] = function_exists( 'bb_topics_manager_instance' ) && method_exists( bb_topics_manager_instance(), 'bb_is_topic_global' ) ? bb_topics_manager_instance()->bb_is_topic_global( $item->topic_id ) : false;

		if ( isset( $item->item_id ) ) {
			$data['item_id'] = $item->item_id;
		}

		$data['permission_type'] = $item->permission_type;

		$item_id   = $request->get_param( 'item_id' );
		$item_type = $request->get_param( 'item_type' );
		if ( bp_is_active( 'groups' ) && ! empty( $item_id ) && ! empty( $item_type ) && 'groups' === $item_type ) {
			$data['is_delete'] = true;

			$data['can_post'] = false;
			if (
				(
					groups_is_user_admin( bp_loggedin_user_id(), $item_id ) &&
					in_array( $data['permission_type'], array( 'admins', 'mods' ), true )
				) ||
				(
					groups_is_user_mod( bp_loggedin_user_id(), $item_id ) &&
					in_array( $data['permission_type'], array( 'mods' ), true )
				) ||
				(
					groups_is_user_member( bp_loggedin_user_id(), $item_id ) &&
					in_array( $data['permission_type'], array( 'members' ), true )
				)
			) {
				$data['can_post'] = true;
			}
		}

		if ( empty( $item_id ) && ! empty( $item_type ) && 'activity' === $item_type ) {
			$data['can_post'] = false;
			if (
				(
					bp_current_user_can( 'bp_moderate' ) &&
					in_array( $data['permission_type'], array( 'mods_admins', 'anyone' ), true )
				) ||
				(
					! bp_current_user_can( 'bp_moderate' ) &&
					in_array( $data['permission_type'], array( 'anyone' ), true )
				)
			) {
				$data['can_post'] = true;
			}
		}

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter a topic value returned from the API.
		 *
		 * @since 2.8.80
		 *
		 * @param WP_REST_Response  $response The response data.
		 * @param WP_REST_Request   $request  Request used to generate the response.
		 * @param BB_Topics_Manager $topic    The topic object.
		 */
		return apply_filters( 'bp_rest_topic_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object $item Topic object.
	 *
	 * @return array
	 */
	protected function prepare_links( $item ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		return array(
			'self'       => array(
				array(
					'href' => rest_url( $base . $item->topic_id ),
				),
			),
			'collection' => array(
				array(
					'href' => rest_url( $base ),
				),
			),
		);
	}

	/**
	 * Get the topic schema, conforming to JSON Schema.
	 *
	 * @since 2.8.80
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'topic',
			'type'       => 'object',
			'properties' => array(
				'name'               => array(
					'description' => __( 'The name of the topic.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'slug'               => array(
					'description' => __( 'The slug of the topic.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'topic_id'           => array(
					'description' => __( 'The topic ID.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'is_global_activity' => array(
					'description' => __( 'Whether this is a global topic.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'item_id'            => array(
					'description' => __( 'The item ID (e.g., Group ID).', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}

	/**
	 * Get the topic name.
	 *
	 * @since 2.8.80
	 *
	 * @param array  $activity  The activity object.
	 * @param string $field   The field.
	 * @param object $request The request.
	 *
	 * @return string The topic name.
	 */
	public function bb_topic_name_get_rest_field_callback( $activity, $field, $request ) {
		$activity_id = $activity['id'];

		if ( empty( $activity_id ) ) {
			return null;
		}

		if (
			'groups' === $activity['component'] &&
			function_exists( 'bb_is_enabled_group_activity_topics' ) &&
			! bb_is_enabled_group_activity_topics()
		) {
			return null;
		}

		if (
			'activity' === $activity['component'] &&
			function_exists( 'bb_is_enabled_activity_topics' ) &&
			! bb_is_enabled_activity_topics()
		) {
			return null;
		}

		$get_topic = bb_activity_topics_manager_instance()->bb_get_activity_topic( $activity_id, 'all' );

		if ( empty( $get_topic ) ) {
			return null;
		}

		return $get_topic;
	}

	/**
	 * The function to use to update the topic id's value of the activity REST Field.
	 *
	 * @since 2.8.80
	 *
	 * @param int    $topic_id   The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case, the BP_Activity_Activity object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used in the REST response.
	 *
	 * @return WP_Error|bool
	 */
	protected function bb_topic_update_rest_field_callback( $topic_id, $value, $attribute ) {
		// Bail if the activity object is empty.
		if ( empty( $value ) ) {
			return false;
		}

		if ( ! in_array( $value->component, array( 'groups', 'activity' ), true ) ) {
			return;
		}

		if ( 'activity_update' !== $value->type ) {
			return;
		}

		// Get the topic ID.
		// $topic_id is already passed as the first parameter.
		if ( bb_is_activity_topic_required() && empty( $topic_id ) ) {
			return new WP_Error( 'bp_rest_topic_id_required', __( 'Topic ID is required.', 'buddyboss' ), array( 'status' => 400 ) );
		}

		if ( is_numeric( $topic_id ) && $topic_id > 0 ) {
			$topic_id = (int) $topic_id;
			$args     = array(
				'topic_id'  => $topic_id,
				'item_type' => 'activity',
			);
			if ( 'groups' === $value->component ) {
				$args['item_id']   = $value->item_id;
				$args['item_type'] = 'groups';
			}

			$get_topic = bb_topics_manager_instance()->bb_get_topic( $args );
			if ( empty( $get_topic ) ) {
				return new WP_Error( 'bp_rest_topic_not_found', __( 'Topic not found.', 'buddyboss' ), array( 'status' => 404 ) );
			}

			$permission_type = $get_topic->permission_type;
			if ( ! empty( $get_topic->item_id ) && 'groups' === $get_topic->item_type && bp_is_active( 'groups' ) ) {
				$group = groups_get_group( $get_topic->item_id );
				if ( empty( $group ) ) {
					return new WP_Error( 'bp_rest_group_invalid_id', __( 'Invalid group ID.', 'buddyboss' ), array( 'status' => 404 ) );
				}

				$existing_topic_id = bb_activity_topics_manager_instance()->bb_get_activity_topic( $value->id );
				if (
					! function_exists( 'bb_is_enabled_group_activity_topics' ) ||
					! bb_is_enabled_group_activity_topics()
				) {
					return new WP_Error( 'bp_rest_access_denied', __( 'You cannot update a topic.', 'buddyboss' ), array( 'status' => 403 ) );
				} elseif (
					empty( $existing_topic_id ) ||
					(int) $existing_topic_id !== (int) $topic_id
				) {

					if (
						in_array( $permission_type, array( 'admins' ), true ) &&
						! groups_is_user_admin( bp_loggedin_user_id(), $get_topic->item_id )
					) {
						return new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot update a topic for this group.', 'buddyboss' ), array( 'status' => 403 ) );
					} elseif (
						in_array( $permission_type, array( 'mods' ), true ) &&
						! groups_is_user_mod( bp_loggedin_user_id(), $get_topic->item_id ) &&
						! groups_is_user_admin( bp_loggedin_user_id(), $get_topic->item_id )
					) {
						return new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot update a topic for this group.', 'buddyboss' ), array( 'status' => 403 ) );
					} elseif (
						in_array( $permission_type, array( 'members' ), true ) &&
						! groups_is_user_member( bp_loggedin_user_id(), $get_topic->item_id ) &&
						! groups_is_user_mod( bp_loggedin_user_id(), $get_topic->item_id ) &&
						! groups_is_user_admin( bp_loggedin_user_id(), $get_topic->item_id )
					) {
						return new WP_Error( 'bp_rest_group_access_denied', __( 'You cannot update a topic for this group.', 'buddyboss' ), array( 'status' => 403 ) );
					}
				}
			} elseif ( ! bp_current_user_can( 'administrator' ) && 'mods_admins' === $permission_type ) {
				return new WP_Error( 'bp_rest_access_denied', __( 'You cannot update a topic.', 'buddyboss' ), array( 'status' => 403 ) );
			}
		}

		$args = array(
			'activity_id' => $value->id,
			'topic_id'    => $topic_id,
		);
		if ( 'groups' === $value->component ) {
			$args['component'] = $value->component;
			$args['item_id']   = $value->item_id;
		}
		bb_activity_topics_manager_instance()->bb_add_activity_topic_relationship( $args );
	}
}
