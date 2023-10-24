<?php
/**
 * BP REST: BP_REST_Notifications_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Notifications endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Notifications_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->notifications->id;
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
						'description' => __( 'A unique numeric ID for the notification.', 'buddyboss' ),
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk/read',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_read' ),
					'permission_callback' => array( $this, 'update_read_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_read_schema(),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve notifications.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/notifications Notifications
	 * @apiName        GetBBNotifications
	 * @apiGroup       Notifications
	 * @apiDescription Retrieve notifications
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String=id,date_notified,item_id,secondary_item_id,component_name,component_action,include} [order_by=id] Name of the field to order according to.
	 * @apiParam {String=ASC,DESC} [sort_order=ASC] Order sort attribute ascending or descending.
	 * @apiParam {String} [component_name]  Limit result set to notifications associated with a specific component.
	 * @apiParam {String} [component_action]  Limit result set to notifications associated with a specific component's action name.
	 * @apiParam {Number} [user_id] Limit result set to notifications addressed to a specific user.
	 * @apiParam {Number} [item_id] Limit result set to notifications associated with a specific item ID.
	 * @apiParam {Number} [secondary_item_id] Limit result set to notifications associated with a specific secondary item ID.
	 * @apiParam {Boolean} [is_new=true] Limit result set to items from specific states.
	 */
	public function get_items( $request ) {
		$args = $this->prepare_get_item_for_database( $request );

		// Actually, query it.
		$notifications = BP_Notifications_Notification::get( $args );

		$retval = array();
		foreach ( $notifications as $notification ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $notification, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after notifications are fetched via the REST API.
		 *
		 * @param array            $notifications Fetched notifications.
		 * @param WP_REST_Response $response      The response data.
		 * @param WP_REST_Request  $request       The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_notifications_get_items', $notifications, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to notification items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see the notifications.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
		if ( is_user_logged_in() ) {
			$retval = true;

			if ( bp_loggedin_user_id() !== $request['user_id'] && ! $this->can_see() ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to see the notifications.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the notifications `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a notification.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/notifications/:id Notification
	 * @apiName        GetBBNotification
	 * @apiGroup       Notifications
	 * @apiDescription Retrieve a notification
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the notification.
	 */
	public function get_item( $request ) {
		$notification = $this->get_notification_object( $request );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $notification, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a notification is fetched via the REST API.
		 *
		 * @param BP_Notifications_Notification $notification Fetched notification.
		 * @param WP_REST_Response              $response     The response data.
		 * @param WP_REST_Request               $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_notifications_get_item', $notification, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific notification.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see the notification.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval       = true;
			$notification = $this->get_notification_object( $request );

			if ( is_null( $notification->item_id ) ) {
				$retval = new WP_Error(
					'bp_rest_notification_invalid_id',
					__( 'Invalid notification ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! $this->can_see( $notification->id ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you cannot view this notification.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the notifications `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a notification.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/notifications Create Notification
	 * @apiName        CreateBBNotifications
	 * @apiGroup       Notifications
	 * @apiDescription Create a notifications
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [user_id] A unique numeric ID for the notification.
	 * @apiParam {Number} [item_id] The ID of the item associated with the notification.
	 * @apiParam {Number} [secondary_item_id] The ID of the secondary item associated with the notification.
	 * @apiParam {String} [component] The name of the component associated with the notification.
	 * @apiParam {String} [action] The name of the component action associated with the notification.
	 * @apiParam {String} [date] The date the notification was sent/created.
	 * @apiParam {Number} [is_new] Whether the notification is new or not.
	 */
	public function create_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		if ( function_exists( 'bb_activity_add_notification_metas' ) ) {
			add_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );
		}
		if ( function_exists( 'bb_groups_add_notification_metas' ) ) {
			add_action( 'bp_notification_after_save', 'bb_groups_add_notification_metas', 5 );
		}
		if ( function_exists( 'bb_forums_add_notification_metas' ) ) {
			add_action( 'bp_notification_after_save', 'bb_forums_add_notification_metas', 5 );
		}

		$notification_id = bp_notifications_add_notification( $this->prepare_item_for_database( $request ) );

		if ( function_exists( 'bb_activity_add_notification_metas' ) ) {
			remove_action( 'bp_notification_after_save', 'bb_activity_add_notification_metas', 5 );
		}
		if ( function_exists( 'bb_groups_add_notification_metas' ) ) {
			remove_action( 'bp_notification_after_save', 'bb_groups_add_notification_metas', 5 );
		}
		if ( function_exists( 'bb_forums_add_notification_metas' ) ) {
			remove_action( 'bp_notification_after_save', 'bb_forums_add_notification_metas', 5 );
		}

		if ( ! is_numeric( $notification_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_notification',
				__( 'Cannot create new notification.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$notification  = $this->get_notification_object( $notification_id );
		$fields_update = $this->update_additional_fields_for_object( $notification, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $notification, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a notification is created via the REST API.
		 *
		 * @param BP_Notifications_Notification $notification The created notification.
		 * @param WP_REST_Response              $response     The response data.
		 * @param WP_REST_Request               $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_notifications_create_item', $notification, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a notification.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = $this->get_items_permissions_check( $request );

		/**
		 * Filter the notifications `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a notification.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/notifications/:id Update Notification
	 * @apiName        UpdateBBNotification
	 * @apiGroup       Notifications
	 * @apiDescription Update notification
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the notification.
	 * @apiParam {Number} [is_new=0] Whether it's a new notification or not.
	 */
	public function update_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$notification = $this->get_notification_object( $request );

		if ( $request['is_new'] === $notification->is_new ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_notification_status',
				__( 'Notification is already with the status you are trying to update into.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$notification->is_new = $request['is_new'];
		$updated              = $notification->save();

		if ( ! (bool) $updated ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_notification',
				__( 'Cannot update the status of this notification.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Make sure to update the status of the notification.
		$notification = $this->prepare_item_for_database( $request );

		// Update additional fields.
		$fields_update = $this->update_additional_fields_for_object( $notification, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $notification, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a notification is updated via the REST API.
		 *
		 * @param BP_Notifications_Notification $notification The updated notification.
		 * @param WP_REST_Response              $response     The response data.
		 * @param WP_REST_Request               $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_notifications_update_item', $notification, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a notification.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the notifications `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a notification.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/notifications/:id Delete Notification
	 * @apiName        DeleteBBNotification
	 * @apiGroup       Notifications
	 * @apiDescription Delete notification
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the notification.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the notification before it's deleted.
		$notification = $this->get_notification_object( $request );
		$previous     = $this->prepare_item_for_response( $notification, $request );

		if ( ! BP_Notifications_Notification::delete( array( 'id' => $notification->id ) ) ) {
			return new WP_Error(
				'bp_rest_notification_invalid_id',
				__( 'Invalid notification ID.', 'buddyboss' ),
				array(
					'status' => 404,
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
		 * Fires after a notification is deleted via the REST API.
		 *
		 * @param BP_Notifications_Notification $notification The deleted notification.
		 * @param WP_REST_Response              $response     The response data.
		 * @param WP_REST_Request               $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_notifications_delete_item', $notification, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a notification.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the notifications `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares notification data for return as an object.
	 *
	 * @param BP_Notifications_Notification $notification Notification data.
	 * @param WP_REST_Request               $request      Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $notification, $request ) {
		$data = array(
			'id'                => $notification->id,
			'user_id'           => $notification->user_id,
			'item_id'           => $notification->item_id,
			'secondary_item_id' => $notification->secondary_item_id,
			'component'         => $notification->component_name,
			'action'            => $notification->component_action,
			'date'              => bp_rest_prepare_date_response( $notification->date_notified ),
			'is_new'            => $notification->is_new,
			'description'       => array(
				'rendered' => '',
			),
			'link_url'          => '',
			'rest_actions'      => '',
			'readonly'          => isset( $notification->readonly ) ? $notification->readonly : false,
		);

		$component        = $notification->component_name;
		$object           = $notification->component_name;
		$item_id          = $notification->item_id;
		$object_id        = $notification->item_id;
		$component_action = $notification->component_action;

		switch ( $component ) {
			case 'groups':
				if ( ! empty( $notification->item_id ) ) {
					$object = 'group';
				}
				break;
			case 'follow':
			case 'friends':
				if ( ! empty( $notification->item_id ) ) {
					$object = 'user';
				}
				break;
			default:
				if ( ! empty( $notification->secondary_item_id ) ) {
					$object    = 'user';
					$object_id = $notification->secondary_item_id;
					$item_id   = $notification->secondary_item_id;
				} else {
					$object = 'user';
				}
				break;
		}

		if (
			! empty( $notification->secondary_item_id ) &&
			in_array(
				$component_action,
				array(
					'bb_groups_new_request',
					'bb_groups_subscribed_discussion',
					'bb_groups_subscribed_activity',
				),
				true
			)
		) {
			$item_id   = $notification->secondary_item_id;
			$object_id = $notification->secondary_item_id;
			$object    = 'user';
		}

		// Avatars.
		$data['avatar_urls'] = array(
			'full'  => bp_core_fetch_avatar(
				array(
					'item_id' => $item_id,
					'html'    => false,
					'type'    => 'full',
					'object'  => $object,
				)
			),
			'thumb' => bp_core_fetch_avatar(
				array(
					'item_id' => $item_id,
					'html'    => false,
					'object'  => $object,
				)
			),
		);

		// Notification object.
		$data['object']    = $object;
		$data['object_id'] = $object_id;

		global $bp;

		if ( ! isset( $bp->notifications ) ) {
			$bp->notifications = new \stdClass();
		}

		if ( ! isset( $bp->notifications->query_loop ) ) {
			$bp->notifications->query_loop = new \stdClass();
		}

		$bp->notifications->query_loop->notification = $notification;
		$data['description']['rendered']             = bp_get_the_notification_description();

		if ( ! empty( $data['description']['rendered'] ) ) {
			// Extract the first URL from Description.
			preg_match( '/\bhttps?:\/\/[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|\/))/', $data['description']['rendered'], $matches_url );

			if ( isset( $matches_url[0] ) && wp_http_validate_url( $matches_url[0] ) ) {
				$data['link_url'] = $matches_url[0];
			}
		}

		$data['link_url']     = $this->bp_rest_link_url_update( $data['link_url'], $notification );
		$data['rest_actions'] = $this->bp_rest_get_notification_actions( $notification );

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $notification, $object ) );

		/**
		 * Filter a notification value returned from the API.
		 *
		 * @param WP_REST_Response              $response     The response data.
		 * @param WP_REST_Request               $request      Request used to generate the response.
		 * @param BP_Notifications_Notification $notification Notification object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_prepare_value', $response, $request, $notification );
	}

	/**
	 * Prepare a notification for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return stdClass
	 * @since 0.1.0
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_notification = new stdClass();
		$schema                = $this->get_item_schema();
		$notification          = $this->get_notification_object( $request );

		if ( ! empty( $schema['properties']['id'] ) && ! empty( $notification->id ) ) {
			$prepared_notification->id = $notification->id;
		}

		if ( ! empty( $schema['properties']['user_id'] ) && isset( $request['user_id'] ) ) {
			$prepared_notification->user_id = (int) $request['user_id'];
		} elseif ( isset( $notification->user_id ) ) {
			$prepared_notification->user_id = $notification->user_id;
		} else {
			$prepared_notification->user_id = bp_loggedin_user_id();
		}

		if ( ! empty( $schema['properties']['item_id'] ) && isset( $request['item_id'] ) ) {
			$prepared_notification->item_id = $request['item_id'];
		} elseif ( isset( $notification->item_id ) ) {
			$prepared_notification->item_id = $notification->item_id;
		}

		if ( ! empty( $schema['properties']['secondary_item_id'] ) && isset( $request['secondary_item_id'] ) ) {
			$prepared_notification->secondary_item_id = $request['secondary_item_id'];
		} elseif ( isset( $notification->secondary_item_id ) ) {
			$prepared_notification->secondary_item_id = $notification->secondary_item_id;
		}

		if ( ! empty( $schema['properties']['component'] ) && isset( $request['component'] ) ) {
			$prepared_notification->component_name = $request['component'];
		} elseif ( isset( $notification->component_name ) ) {
			$prepared_notification->component_name = $notification->component_name;
		}

		if ( ! empty( $schema['properties']['action'] ) && isset( $request['action'] ) ) {
			$prepared_notification->component_action = $request['action'];
		} elseif ( isset( $notification->component_action ) ) {
			$prepared_notification->component_action = $notification->component_action;
		}

		if ( ! empty( $schema['properties']['is_new'] ) && isset( $request['is_new'] ) ) {
			$prepared_notification->is_new = $request['is_new'];
		} elseif ( isset( $notification->is_new ) ) {
			$prepared_notification->is_new = $notification->is_new;
		}

		if ( ! empty( $schema['properties']['date'] ) && isset( $request['date'] ) ) {
			$prepared_notification->date_notified = $request['date'];
		} elseif ( isset( $notification->date_notified ) ) {
			$prepared_notification->date_notified = $notification->date_notified;
		}

		/**
		 * Filters a notification before it is inserted or updated via the REST API.
		 *
		 * @param stdClass        $prepared_notification An object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request               Request object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_pre_insert_value', $prepared_notification, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Notifications_Notification $notification Notification item.
	 * @param string                        $object Notification object.
	 *
	 * @return array Links for the given plugin.
	 * @since 0.1.0
	 */
	protected function prepare_links( $notification, $object ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		if ( 'user' === $object && (int) $notification->user_id !== (int) $notification->secondary_item_id ) {
			$users_ids = array(
				$notification->user_id,
				$notification->secondary_item_id,
			);
		} else {
			$users_ids = $notification->user_id;
		}

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $notification->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $users_ids ) ),
				'embeddable' => true,
			),
		);

		if (
			'bb_forums_subscribed_reply' === $notification->component_action ||
			'bbp_new_reply' === $notification->component_action
		) {

			$description = bp_get_the_notification_description( $notification );
			$url         = '';
			$page        = '';
			$topic_id    = '';
			$reply_id    = '';

			if ( ! empty( $description ) ) {
				// Extract the first URL from Description.
				preg_match( '/\bhttps?:\/\/[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|\/))/', $description, $matches_url );

				if ( isset( $matches_url[0] ) && wp_http_validate_url( $matches_url[0] ) ) {
					$url = $matches_url[0];
				}
			}

			if ( ! empty( $url ) ) {
				$url   = urldecode( wp_specialchars_decode( $url ) );
				$parse = wp_parse_url( $url );
				if ( ! empty( $parse['fragment'] ) ) {
					wp_parse_str( $url, $params );
				}
				if ( ! empty( $params ) ) {
					$topic_id = ( isset( $params['topic_id'] ) ? $params['topic_id'] : '' );
					$reply_id = ( isset( $params['reply_id'] ) ? $params['reply_id'] : '' );
				}
				$explode = explode( '/', $url );
				if ( ! empty( $explode ) ) {
					$val = array_search( 'page', $explode, true );
					if ( $val ) {
						$page = $explode[ $val + 1 ];
					}
				}
			}

			if ( ! empty( $topic_id ) ) {
				$topic_base     = sprintf( '/%s/%s/', $this->namespace, 'topics' );
				$topic_url      = $topic_base . $topic_id;
				$links['topic'] = array(
					'href' => rest_url( $topic_url ),
				);
			}

			if ( ! empty( $topic_id ) && ! empty( $reply_id ) && ! empty( $page ) ) {
				$reply_base = sprintf( '/%s/%s/', $this->namespace, 'reply' );
				$reply_url  = add_query_arg(
					array(
						'_embed'     => true,
						'parent'     => $topic_id,
						'page'       => $page,
						'reply_jump' => $reply_id,
					),
					$reply_base
				);

				$links['reply'] = array(
					'href' => rest_url( $reply_url ),
				);
			}
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array                         $links        The prepared links of the REST response.
		 * @param BP_Notifications_Notification $notification Notification object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_prepare_links', $links, $notification );
	}

	/**
	 * Can this user see the notification?
	 *
	 * @param int $notification_id Notification ID.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	protected function can_see( $notification_id = 0 ) {

		// Check notification access.
		if ( ! empty( $notification_id ) && (bool) BP_Notifications_Notification::check_access( bp_loggedin_user_id(), $notification_id ) ) {
			return true;
		}

		// Moderators as well.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get notification object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return BP_Notifications_Notification|string A notification object.
	 * @since 0.1.0
	 */
	public function get_notification_object( $request ) {
		$notification_id = is_numeric( $request ) ? $request : (int) $request['id'];

		$notification = bp_notifications_get_notification( $notification_id );

		if ( empty( $notification->id ) ) {
			return '';
		}

		return $notification;
	}

	/**
	 * Select the item schema arguments needed for the EDITABLE method.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			// Only switching the is_new property can be achieved.
			$args                      = array_intersect_key( $args, array( 'is_new' => true ) );
			$args['is_new']['default'] = 0;
		} elseif ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			if ( array_key_exists( 'object', $args ) ) {
				unset( $args['object'] );
			}
			if ( array_key_exists( 'object_id', $args ) ) {
				unset( $args['object_id'] );
			}
			if ( array_key_exists( 'link_url', $args ) ) {
				unset( $args['link_url'] );
			}
			if ( array_key_exists( 'rest_actions', $args ) ) {
				unset( $args['rest_actions'] );
			}
			if ( array_key_exists( 'readonly', $args ) ) {
				unset( $args['readonly'] );
			}
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_notifications_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the notification schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_notifications',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the notification.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the user the notification is addressed to.', 'buddyboss' ),
					'type'        => 'integer',
					'default'     => bp_loggedin_user_id(),
				),
				'item_id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the item associated with the notification.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'secondary_item_id' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the secondary item associated with the notification.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'component'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The name of the BuddyPress component the notification relates to.', 'buddyboss' ),
					'type'        => 'string',
				),
				'action'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The name of the component\'s action the notification is about.', 'buddyboss' ),
					'type'        => 'string',
				),
				'date'              => array(
					'description' => __( 'The date the notification was created, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'is_new'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether it\'s a new notification or not.', 'buddyboss' ),
					'type'        => 'integer',
					'default'     => 1,
				),
				'object'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The name of the notification object.', 'buddyboss' ),
					'type'        => 'string',
				),
				'object_id'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of the notification object.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'description'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Description of notification.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => array(
						'rendered' => array(
							'description' => __( 'HTML description for the object, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'link_url'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Link URL for the notification.', 'buddyboss' ),
					'type'        => 'string',
				),
				'rest_actions'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Rest Actions which perform accept/reject based on the status.', 'buddyboss' ),
					'type'        => 'object',
				),
				'readonly'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Readonly for the moderated members notification.', 'buddyboss' ),
					'type'        => 'object',
				),
			),
		);

		$avatar_properties = array();

		$avatar_properties['full'] = array(
			/* translators: Full image size for the member Avatar */
			'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_full_width() ), bp_core_number_format( bp_core_avatar_full_height() ) ),
			'type'        => 'string',
			'format'      => 'uri',
			'context'     => array( 'embed', 'view', 'edit' ),
		);

		$avatar_properties['thumb'] = array(
			/* translators: Thumb image size for the member Avatar */
			'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_thumb_width() ), bp_core_number_format( bp_core_avatar_thumb_height() ) ),
			'type'        => 'string',
			'format'      => 'uri',
			'context'     => array( 'embed', 'view', 'edit' ),
		);

		$schema['properties']['avatar_urls'] = array(
			'description' => __( 'Avatar URLs for the notification.', 'buddyboss' ),
			'type'        => 'object',
			'context'     => array( 'embed', 'view', 'edit' ),
			'readonly'    => true,
			'properties'  => $avatar_properties,
		);

		/**
		 * Filters the notifications schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_notification_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for the notifications collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Remove the search argument.
		unset( $params['search'] );

		$params['order_by'] = array(
			'description'       => __( 'Name of the field to order according to.', 'buddyboss' ),
			'default'           => 'id',
			'type'              => 'string',
			'enum'              => array(
				'id',
				'date_notified',
				'item_id',
				'secondary_item_id',
				'component_name',
				'component_action',
				'include',
			),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['sort_order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'default'           => 'ASC',
			'type'              => 'string',
			'enum'              => array( 'ASC', 'DESC' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['component_name'] = array(
			'description'       => __( 'Limit result set to notifications associated with a specific component', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['component_action'] = array(
			'description'       => __( 'Limit result set to notifications associated with a specific component\'s action name.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit result set to notifications addressed to a specific user.', 'buddyboss' ),
			'default'           => bp_loggedin_user_id(),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['item_id'] = array(
			'description'       => __( 'Limit result set to notifications associated with a specific item ID.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['secondary_item_id'] = array(
			'description'       => __( 'Limit result set to notifications associated with a specific secondary item ID.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['is_new'] = array(
			'description'       => __( 'Limit result set to items from specific states.', 'buddyboss' ),
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
		return apply_filters( 'bp_rest_notifications_collection_params', $params );
	}

	/**
	 * Action to perform.
	 *
	 * @param BP_Notifications_Notification $notification A notification object.
	 *
	 * @return array|string
	 */
	public function bp_rest_get_notification_actions( $notification ) {
		$component_action = $notification->component_action;

		$data = array(
			'status' => '',
			'accept' => array(),
			'reject' => array(),
		);

		switch ( $component_action ) {
			case 'friendship_accepted':
			case 'bb_connections_request_accepted':
			case 'membership_request_accepted':
			case 'bb_groups_request_accepted':
			case 'membership_request_rejected':
			case 'bb_groups_request_rejected':
			case 'member_promoted_to_admin':
			case 'member_promoted_to_mod':
			case 'bb_groups_promoted':
				break;

			case 'friendship_request':
			case 'bb_connections_new_request':
				if (
					! empty( $notification->secondary_item_id )
					&& bp_is_active( 'friends' )
					&& class_exists( 'BP_Friends_Friendship' )
				) {
					$friendship = new BP_Friends_Friendship( $notification->secondary_item_id );
					if ( $friendship->id === $notification->secondary_item_id ) {

						if ( ! empty( $friendship->is_confirmed ) ) {
							$data['status'] = __( 'Accepted', 'buddyboss' );
						} else {
							$data['status']             = __( 'Pending', 'buddyboss' );
							$data['accept']['endpoint'] = rest_url( $this->namespace . '/' . buddypress()->friends->id . '/' . $friendship->id );
							$data['accept']['method']   = 'PATCH';
							$data['accept']['link_url'] = bp_loggedin_user_domain() . bp_get_friends_slug();

							$data['reject']['endpoint'] = rest_url( $this->namespace . '/' . buddypress()->friends->id . '/' . $friendship->id );
							$data['reject']['method']   = 'DELETE';
							$data['reject']['link_url'] = bp_loggedin_user_domain() . bp_get_friends_slug();
						}
					} else {
						$data['status'] = __( 'Rejected', 'buddyboss' );
					}
				}
				break;

			case 'new_membership_request':
			case 'bb_groups_new_request':
				if (
					! empty( $notification->secondary_item_id )
					&& bp_is_active( 'groups' )
					&& function_exists( 'groups_get_requests' )
				) {
					$group     = groups_get_group( $notification->item_id );
					$is_member = groups_is_user_member( $notification->secondary_item_id, $notification->item_id );
					if ( ! empty( $is_member ) ) {
						$data['status'] = __( 'Accepted', 'buddyboss' );
					} else {
						$requests = groups_get_requests(
							array(
								'user_id' => $notification->secondary_item_id,
								'item_id' => $notification->item_id,
							)
						);

						if ( ! empty( $requests ) ) {
							$current_request = current( $requests );
							if ( ! empty( $current_request->accepted ) ) {
								$data['status'] = __( 'Accepted', 'buddyboss' );
							} else {
								$data['status']             = __( 'Pending', 'buddyboss' );
								$data['accept']['endpoint'] = rest_url( $this->namespace . '/' . buddypress()->groups->id . '/membership-requests/' . $current_request->id );
								$data['accept']['method']   = 'PATCH';
								$data['accept']['link_url'] = trailingslashit( bp_get_group_permalink( $group ) . 'members' );

								$data['reject']['endpoint'] = rest_url( $this->namespace . '/' . buddypress()->groups->id . '/membership-requests/' . $current_request->id );
								$data['reject']['method']   = 'DELETE';
								$data['reject']['link_url'] = trailingslashit( bp_get_group_permalink( $group ) . 'members' );
							}
						} else {
							$data['status'] = __( 'Rejected', 'buddyboss' );
						}
					}
				}
				break;

			case 'group_invite':
			case 'bb_groups_new_invite':
				if ( bp_is_active( 'groups' ) && function_exists( 'groups_get_invites' ) ) {
					$group     = groups_get_group( $notification->item_id );
					$is_member = groups_is_user_member( $notification->user_id, $notification->item_id );
					if ( ! empty( $is_member ) ) {
						$data['status'] = __( 'Accepted', 'buddyboss' );
					} else {
						$invites = groups_get_invites(
							array(
								'user_id' => $notification->user_id,
								'item_id' => $notification->item_id,
							)
						);

						if ( ! empty( $invites ) ) {
							$current_invites = current( $invites );
							if ( ! empty( $current_invites->accepted ) ) {
								$data['status'] = __( 'Accepted', 'buddyboss' );
							} else {
								$data['status']             = __( 'Pending', 'buddyboss' );
								$data['accept']['endpoint'] = rest_url( $this->namespace . '/' . buddypress()->groups->id . '/invites/' . $current_invites->id );
								$data['accept']['method']   = 'PATCH';
								$data['accept']['link_url'] = bp_get_group_permalink( $group );

								$data['reject']['endpoint'] = rest_url( $this->namespace . '/' . buddypress()->groups->id . '/invites/' . $current_invites->id );
								$data['reject']['method']   = 'DELETE';
								$data['reject']['link_url'] = bp_get_group_permalink( $group );
							}
						} else {
							$data['status'] = __( 'Rejected', 'buddyboss' );
						}
					}
				}
				break;
		}

		if ( array(
			'status' => '',
			'accept' => array(),
			'reject' => array(),
		) === $data ) {
			return '';
		}

		return $data;
	}

	/**
	 * Update Link URL after request accept/reject.
	 *
	 * @param string                        $url          Link URL for the notification.
	 * @param BP_Notifications_Notification $notification A notification object.
	 *
	 * @return string
	 */
	public function bp_rest_link_url_update( $url, $notification ) {
		$component_action = $notification->component_action;

		switch ( $component_action ) {
			case 'friendship_accepted':
			case 'bb_connections_request_accepted':
			case 'membership_request_accepted':
			case 'bb_groups_request_accepted':
			case 'membership_request_rejected':
			case 'bb_groups_request_rejected':
			case 'member_promoted_to_admin':
			case 'member_promoted_to_mod':
			case 'bb_groups_promoted':
				break;

			case 'friendship_request':
			case 'bb_connections_new_request':
				if (
					! empty( $notification->secondary_item_id )
					&& bp_is_active( 'friends' )
					&& class_exists( 'BP_Friends_Friendship' )
				) {
					$friendship = new BP_Friends_Friendship( $notification->secondary_item_id );
					if ( $friendship->id === $notification->secondary_item_id ) {
						if ( ! empty( $friendship->is_confirmed ) ) {
							$url = bp_loggedin_user_domain() . bp_get_friends_slug();
						}
					} else {
						$url = bp_loggedin_user_domain() . bp_get_friends_slug();
					}
				}
				break;

			case 'new_membership_request':
			case 'bb_groups_new_request':
				if (
					! empty( $notification->secondary_item_id )
					&& bp_is_active( 'groups' )
					&& function_exists( 'groups_get_requests' )
				) {
					$group     = groups_get_group( $notification->item_id );
					$is_member = groups_is_user_member( $notification->secondary_item_id, $notification->item_id );
					if ( ! empty( $is_member ) ) {
						$url = trailingslashit( bp_get_group_permalink( $group ) . 'members' );
					} else {
						$requests = groups_get_requests(
							array(
								'user_id' => $notification->secondary_item_id,
								'item_id' => $notification->item_id,
							)
						);

						if ( empty( $requests ) ) {
							$url = trailingslashit( bp_get_group_permalink( $group ) . 'members' );
						}
					}
				}
				break;

			case 'group_invite':
			case 'bb_groups_new_invite':
				if ( bp_is_active( 'groups' ) && function_exists( 'groups_get_invites' ) ) {
					$group     = groups_get_group( $notification->item_id );
					$is_member = groups_is_user_member( $notification->user_id, $notification->item_id );
					if ( ! empty( $is_member ) ) {
						$url = bp_get_group_permalink( $group );
					} else {
						$invites = groups_get_invites(
							array(
								'user_id' => $notification->user_id,
								'item_id' => $notification->item_id,
							)
						);

						if ( empty( $invites ) ) {
							$url = bp_get_group_permalink( $group );
						}
					}
				}
				break;
		}

		return $url;
	}

	/**
	 * Mark as read notification for the current user's.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/notifications/bulk/read Notification read in bulk
	 * @apiName        UpdateBBNotificationRead
	 * @apiGroup       Notifications
	 * @apiDescription Mark as read bulk notifications
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function update_read( $request ) {
		$schema   = $this->get_endpoint_args_for_read_schema();
		$page     = ! empty( $request->get_param( 'page' ) ) ? (int) $request->get_param( 'page' ) : 1;
		$per_page = ! empty( $request->get_param( 'per_page' ) ) ? (int) $request->get_param( 'per_page' ) : $schema['per_page']['default'];

		$user_id          = get_current_user_id();
		$notification_ids = BP_Notifications_Notification::get(
			array(
				'user_id'           => $user_id,
				'order_by'          => 'date_notified',
				'sort_order'        => 'DESC',
				'page'              => 1,
				'per_page'          => ( $per_page * $page ),
				'update_meta_cache' => false,
				'fields'            => 'id',
			)
		);
		if ( $notification_ids ) {
			foreach ( $notification_ids as $notification_id ) {
				BP_Notifications_Notification::update(
					array( 'is_new' => 0 ),
					array( 'id' => $notification_id )
				);
			}
		}

		/**
		 * Fire after user read notifications has been updated via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param array           $notification_ids Array of notification IDs.
		 * @param WP_REST_Request $request          The request sent to the API.
		 */
		do_action( 'bp_rest_after_notifications_mark_read', $notification_ids, $request );

		// Get another unread notification.
		$request->set_param( 'user_id', $user_id );
		$request->set_param( 'page', 1 );
		$request->set_param( 'is_new', true );
		$response = $this->get_items( $request );

		/**
		 * Fire after user read notifications has been updated via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request  $request  The request sent to the API.
		 * @param WP_REST_Response $response The response data.
		 */
		do_action( 'bp_rest_notifications_update_read', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update user notifications.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_read_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see the notification.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the notifications `update_read` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_update_read_permissions_check', $retval, $request );
	}

	/**
	 * Prepare a notification arguments for get items.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	protected function prepare_get_item_for_database( $request ) {
		$args = array(
			'user_id'           => $request->get_param( 'user_id' ),
			'item_id'           => $request->get_param( 'item_id' ),
			'secondary_item_id' => $request->get_param( 'secondary_item_id' ),
			'component_name'    => $request->get_param( 'component_name' ),
			'component_action'  => $request->get_param( 'component_action' ),
			'order_by'          => $request->get_param( 'order_by' ),
			'sort_order'        => strtoupper( $request->get_param( 'sort_order' ) ),
			'is_new'            => $request->get_param( 'is_new' ),
			'page'              => $request->get_param( 'page' ),
			'per_page'          => $request->get_param( 'per_page' ),
		);

		if ( empty( $request->get_param( 'component_action' ) ) ) {
			$args['component_action'] = false;
		}

		if ( empty( $request->get_param( 'component_name' ) ) ) {
			$args['component_name'] = bp_notifications_get_registered_components();
		}

		if ( ! empty( $request->get_param( 'include' ) ) ) {
			$args['id'] = $request->get_param( 'include' );
			if (
				! empty( $args['order_by'] )
				&& 'include' === $args['order_by']
			) {
				$args['order_by'] = 'in';
			}
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_notifications_get_items_query_args', $args, $request );
	}

	/**
	 * Select the item schema arguments needed for the EDITABLE method to read notification.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_read_schema( $method = WP_REST_Server::EDITABLE ) {
		$args = parent::get_collection_params();
		$key  = 'update_read';

		$support_args = array();

		if ( array_key_exists( 'per_page', $args ) ) {
			$support_args['per_page']            = $args['per_page'];
			$support_args['per_page']['default'] = 25;
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_notifications_{$key}_query_arguments", $support_args, $method );
	}
}
