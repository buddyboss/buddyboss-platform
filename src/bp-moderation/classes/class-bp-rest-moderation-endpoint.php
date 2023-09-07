<?php
/**
 * BP REST: BP_REST_Moderation_Endpoint class
 *
 * @package BuddyBoss
 *
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Moderation endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Moderation_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->moderation->id;

		if ( bp_is_active( 'activity' ) ) {
			// Moderation support for activity/activity_comments.
			$this->bp_rest_moderation_activity_support();
		}

		if ( bp_is_active( 'groups' ) ) {
			// Moderation support for Group.
			$this->bp_rest_moderation_group_support();
		}

		if ( bp_is_active( 'forums' ) ) {
			// Moderation support for Forums.
			$this->bp_rest_moderation_forums_support();

			// Moderation support for Topic.
			$this->bp_rest_moderation_topic_support();

			// Moderation support for Reply.
			$this->bp_rest_moderation_reply_support();
		}

		if ( bp_is_active( 'media' ) ) {
			// Moderation support for media.
			$this->bp_rest_moderation_media_support();
		}

		// Moderation support for video.
		if ( bp_is_active( 'video' ) ) {
			$this->bp_rest_moderation_video_support();
		}

		if ( bp_is_active( 'document' ) ) {
			// Moderation support for document.
			$this->bp_rest_moderation_document_support();
		}

		if ( bp_is_active( 'messages' ) ) {
			// Moderation support for messages.
			$this->bp_rest_moderation_messages_support();
		}

		// Moderation support for Members.
		$this->bp_rest_moderation_member_support();

		// Moderation support for Blog comments.
		// EX: /wp-json/wp/v2/comments?post=4662.
		$this->bp_rest_moderation_blog_comments_support();
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
						'description' => __( 'A unique numeric ID for the moderation.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => 'true',
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
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve blocked members.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/moderation Get Reported Members
	 * @apiName        GetBBReportedMembers
	 * @apiGroup       Moderation
	 * @apiDescription Retrieve Reported Members
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {Number} [item_id] Get the result by reported item.
	 * @apiParam {String=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {String=id,item_type,item_id,last_updated,hide_sitewide} [order_by=last_updated] Column name to order the results by.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {Boolean} [reporters=false] Whether to show the reporter or not.
	 * @apiParam {Boolean} [status] Whether to show the blocked or suspended. 0-Blocked, 1-Suspended
	 * @apiParam {Number} [blog_id] Limit result set to items created by a specific site.
	 */
	public function get_items( $request ) {

		$args = array(
			'user_id'           => bp_loggedin_user_id(),
			'page'              => $request['page'],
			'per_page'          => $request['per_page'],
			'sort'              => strtoupper( $request['order'] ),
			'order_by'          => $request['order_by'],
			'in_types'          => array( 'user' ),
			'update_meta_cache' => true,
			'count_total'       => true,
			'display_reporters' => false,
			'filter'            => false,
		);

		if ( isset( $request['exclude'] ) ) {
			$args['exclude'] = $request['exclude'];
		}

		if ( isset( $request['include'] ) ) {
			$args['in'] = $request['include'];
		}

		if ( isset( $request['reporters'] ) ) {
			$args['display_reporters'] = $request['reporters'];
		}

		if ( isset( $request['item_id'] ) ) {
			$args['filter']['item_id'] = $request['item_id'];
		}

		if ( isset( $request['status'] ) ) {
			$args['filter']['hide_sitewide'] = $request['status'];
		}

		if ( isset( $request['blog_id'] ) ) {
			$args['filter']['blog_id'] = $request['blog_id'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_moderation_get_items_query_args', $args, $request );

		$moderations = bp_moderation_get( $args );

		$retval = array();
		foreach ( $moderations['moderations'] as $moderation ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $moderation, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $moderations['total'], $args['per_page'] );

		/**
		 * Fires after a list of blocked members is fetched via the REST API.
		 *
		 * @param array            $moderations Fetched blocked members.
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_moderation_get_items', $moderations, $response, $request );

		return $response;

	}

	/**
	 * Check if a given request has access to moderation items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to view the block members.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the moderation `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a moderation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 *
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/moderation/:id Get Reported Member
	 * @apiName        GetBBReportedMember
	 * @apiGroup       Moderation
	 * @apiDescription Retrieve Reported Member
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Moderation.
	 */
	public function get_item( $request ) {
		$moderation = $this->get_moderation_object( $request );

		if ( empty( $moderation->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid moderation ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $moderation, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a moderation is fetched via the REST API.
		 *
		 * @param BP_Moderation    $moderation Fetched moderation.
		 * @param WP_REST_Response $response   The response data.
		 * @param WP_REST_Request  $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_moderation_get_item', $moderation, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to moderation item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to view the block member.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the moderation `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a Moderation.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/moderation Block a Member
	 * @apiName        CreateBBReportMember
	 * @apiGroup       Moderation
	 * @apiDescription Block a Member.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} item_id User ID which needs to be blocked.
	 */
	public function create_item( $request ) {
		$item_id = $request->get_param( 'item_id' );
		$type    = $request->get_param( 'type' );

		$user_id = bp_loggedin_user_id();

		if ( empty( $item_id ) ) {
			return new WP_Error(
				'bp_rest_moderation_missing_data',
				__( 'Required field missing.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( bp_moderation_report_exist( $item_id, BP_Moderation_Members::$moderation_type ) ) {
			return new WP_Error(
				'bp_rest_moderation_already_reported',
				__( 'Already reported this item.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$moderation = bp_moderation_add(
			array(
				'content_id'   => $item_id,
				'content_type' => $type,
				'note'         => esc_html__( 'Member block', 'buddyboss' ),
			)
		);

		if ( empty( $moderation->id ) && empty( $moderation->report_id ) && ! empty( $moderation->errors ) ) {
			return new WP_Error(
				'bp_rest_invalid_moderation',
				__( 'Sorry, something goes wrong please try again.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( ! empty( $moderation->id ) ) {

			if ( bp_is_active( 'friends' ) ) {
				$friend_status = bp_is_friend( $item_id );
				if (
					! empty( $friend_status ) &&
					in_array(
						$friend_status,
						array( 'is_friend', 'pending', 'awaiting_response' ),
						true
					)
				) {
					friends_remove_friend( bp_loggedin_user_id(), $item_id );
				}
			}

			if (
				function_exists( 'bp_is_following' ) &&
				bp_is_following(
					array(
						'leader_id'   => $item_id,
						'follower_id' => $user_id,
					)
				)
			) {
				bp_stop_following(
					array(
						'leader_id'   => $item_id,
						'follower_id' => $user_id,
					)
				);
			}
		}

		$moderation = $this->get_moderation_object( array( 'id' => $moderation->id ) );

		if ( empty( $moderation->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid moderation ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $moderation, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a moderation item is created via the REST API.
		 *
		 * @param BP_Moderation    $moderation Created moderation.
		 * @param WP_REST_Response $response   The response data.
		 * @param WP_REST_Request  $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_create_item', $moderation, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a moderation.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to block member.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$item_id = $request->get_param( 'item_id' );
		$user    = bp_rest_get_user( $item_id );

		if ( is_user_logged_in() && $user instanceof WP_User ) {
			$retval = true;
		}

		if ( true === $retval && ! empty( $user->roles ) && in_array( 'administrator', $user->roles, true ) ) {
			$retval = new WP_Error(
				'bp_rest_invalid_item_id',
				__( 'Sorry, you can not able to block admin users.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && (int) bp_loggedin_user_id() === (int) $item_id ) {
			$retval = new WP_Error(
				'bp_rest_invalid_item_id',
				__( 'Sorry, you can not able to block him self.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the moderation `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Unblock a moderated member.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/moderation:id Unblock Member
	 * @apiName        DeleteBBReportMember
	 * @apiGroup       Moderation
	 * @apiDescription Unblock Member.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the moderation.
	 */
	public function delete_item( $request ) {
		$moderation = $this->get_moderation_object( $request );

		if ( empty( $moderation->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid moderation ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = $this->prepare_item_for_response( $moderation, $request );

		$item_id = $moderation->item_id;
		$type    = $moderation->item_type;

		if (
			empty( $item_id )
			|| ! bp_moderation_report_exist( $item_id, BP_Moderation_Members::$moderation_type )
		) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid moderation ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $moderation->hide_sitewide ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid moderation ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$moderation_deleted = bp_moderation_delete(
			array(
				'content_id'   => $item_id,
				'content_type' => $type,
			)
		);

		if ( ! empty( $moderation_deleted->report_id ) ) {
			return new WP_Error(
				'bp_rest_moderation_block_error',
				__( 'Sorry, Something happened wrong', 'buddyboss' ),
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
		 * Fires after a moderation is fetched via the REST API.
		 *
		 * @param BP_Moderation    $moderation Fetched moderation.
		 * @param WP_REST_Response $response   The response data.
		 * @param WP_REST_Request  $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_moderation_delete_item', $moderation, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete a moderation member.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to unblock member.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the moderation `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Edit the type of the some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'create_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['item_id']['required'] = true;
			$args['type']['required']    = true;
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_moderation_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get moderation object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return BP_Moderation|string A Moderation object.
	 */
	public function get_moderation_object( $request ) {
		$moderation_id = is_numeric( $request ) ? $request : (int) $request['id'];

		$args = array( 'in' => array( $moderation_id ) );

		if ( isset( $request['reporters'] ) ) {
			$args['display_reporters'] = $request['reporters'];
		}

		$moderation = bp_moderation_get( $args );

		if ( is_array( $moderation ) && ! empty( $moderation['moderations'][0] ) ) {
			return $moderation['moderations'][0];
		}

		return '';
	}

	/**
	 * Prepares moderation data for return as an object.
	 *
	 * @param BP_Moderation   $moderation The Moderation object.
	 * @param WP_REST_Request $request    Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $moderation, $request ) {
		$data = array(
			'id'            => $moderation->id,
			'blog_id'       => $moderation->blog_id,
			'item_id'       => $moderation->item_id,
			'type'          => $moderation->item_type,
			'last_updated'  => $moderation->last_updated,
			'hide_sitewide' => $moderation->hide_sitewide,
			'count'         => $moderation->count,
		);

		if ( isset( $request['reporters'] ) && ! empty( $request['reporters'] ) ) {
			$data['reporters'] = $moderation->reporters;
		}

		$data = $this->add_additional_fields_to_object( $data, $request );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $moderation ) );

		/**
		 * Filter a moderation value returned from the API.
		 *
		 * @param WP_REST_Response $response   The response data.
		 * @param WP_REST_Request  $request    Request used to generate the response.
		 * @param BP_Moderation    $moderation The Moderation object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_prepare_value', $response, $request, $moderation );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Moderation $moderation The Moderation object.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $moderation ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$url  = $base . $moderation->id;

		// Entity meta.
		$links = array(
			'self' => array(
				'href' => rest_url( $url ),
			),
			'user' => array(
				'href'       => add_query_arg(
					array( 'username_visible' => 1 ),
					rest_url( bp_rest_get_user_url( $moderation->item_id ) )
				),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array         $links      The prepared links of the REST response.
		 * @param BP_Moderation $moderation The Moderation object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_moderation_prepare_links', $links, $moderation );
	}

	/**
	 * Get the query params for Moderation collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		unset( $params['search'] );

		$params['item_id'] = array(
			'description'       => __( 'Get the result by reported item.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'sanitize_key',
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

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order_by'] = array(
			'description'       => __( 'Column name to order the results by.', 'buddyboss' ),
			'default'           => 'last_updated',
			'type'              => 'string',
			'enum'              => array( 'id', 'item_type', 'item_id', 'last_updated', 'hide_sitewide' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

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

		$params['reporters'] = array(
			'description'       => __( 'Whether to show the reporter ids or not.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Whether to show the blocked or suspended. 0-Blocked, 1-Suspended', 'buddyboss' ),
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['blog_id'] = array(
			'description'       => __( 'Fetch the data for specific blog ID.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_messages_collection_params', $params );
	}

	/**
	 * Get the plugin schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_moderation',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the moderation.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'blog_id'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Current Site ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'item_id'       => array(
					'context'           => array( 'view', 'edit' ),
					'description'       => __( 'Item ID which needs to be blocked.', 'buddyboss' ),
					'type'              => 'integer',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'type'          => array(
					'context'           => array( 'view', 'edit' ),
					'description'       => __( 'Item type for the block.', 'buddyboss' ),
					'type'              => 'string',
					'default'           => 'user',
					'enum'              => (array) ( class_exists( 'BP_Moderation_Members' ) ? BP_Moderation_Members::$moderation_type : 'user' ),
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'hide_sitewide' => array(
					'context'           => array( 'view', 'edit' ),
					'description'       => __( 'Whether it is hidden of all or not.', 'buddyboss' ),
					'type'              => 'boolean',
					'readonly'          => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'count'         => array(
					'context'           => array( 'view', 'edit' ),
					'description'       => __( 'Number of time item was reported.', 'buddyboss' ),
					'type'              => 'integer',
					'readonly'          => true,
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'reporters'     => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Reported users for the item.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the moderation schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_moderation_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/********************** Activity Support ***************************************/

	/**
	 * Added support for blocked content into activity and activity comments.
	 */
	protected function bp_rest_moderation_activity_support() {

		bp_rest_register_field(
			'activity',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_can_report' ),
				'schema'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'activity_comments',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'activity',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_is_reported' ),
				'schema'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Whether the activity is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'activity_comments',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the activity is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'activity',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Activity report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'activity_comments',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Activity comment report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'activity',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_report_type' ),
				'schema'       => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Activity report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'activity_comments',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_activity_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Activity comment report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		add_filter( 'bp_rest_activity_prepare_value', array( $this, 'bp_rest_moderation_activity_prepare_value' ), 999, 3 );
	}

	/**
	 * The function to use to get can_report of the activity REST Field.
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_activity_can_report( $activity ) {
		$item_id   = $activity['id'];
		$item_type = BP_Suspend_Activity::$type;

		if ( empty( $item_id ) ) {
			return false;
		}

		if ( 'activity_comment' === $activity['type'] ) {
			$activity_comment_data = new BP_Moderation_Activity_Comment();
			$item_data             = $activity_comment_data->update_button_sub_items( $item_id );
			$item_id               = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type             = ( isset( $item_data['type'] ) ) ? $item_data['type'] : BP_Suspend_Activity_Comment::$type;
		} else {
			$activity_data = new BP_Moderation_Activity();
			$item_data     = $activity_data->update_button_sub_items( $item_id );
			$item_id       = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type     = ( isset( $item_data['type'] ) ) ? $item_data['type'] : $item_type;
		}

		if (
			! empty( $activity['user_id'] ) &&
			(
				! bb_is_group_activity_comment( $item_id ) &&
				'groups' !== $activity['component']
			) &&
			(
				bp_moderation_is_user_suspended( $activity['user_id'] ) ||
				bp_moderation_is_user_blocked( $activity['user_id'] ) ||
				bb_moderation_is_user_blocked_by( $activity['user_id'] )
			)
		) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $item_id, $item_type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the activity REST Field.
	 *
	 * @param BP_Activity_Activity $activity Activity Array.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_activity_is_reported( $activity ) {
		$item_id   = $activity['id'];
		$item_type = BP_Suspend_Activity::$type;

		if ( empty( $item_id ) ) {
			return false;
		}

		if ( 'activity_comment' === $activity['type'] ) {
			$activity_comment_data = new BP_Moderation_Activity_Comment();
			$item_data             = $activity_comment_data->update_button_sub_items( $item_id );
			$item_id               = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type             = ( isset( $item_data['type'] ) ) ? $item_data['type'] : BP_Suspend_Activity_Comment::$type;
		} else {
			$activity_data = new BP_Moderation_Activity();
			$item_data     = $activity_data->update_button_sub_items( $item_id );
			$item_id       = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type     = ( isset( $item_data['type'] ) ) ? $item_data['type'] : $item_type;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $item_id, $item_type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get activity report button text.
	 *
	 * @param BP_Activity_Activity $activity Activity array.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_activity_report_button_text( $activity ) {
		$item_id   = $activity['id'];
		$item_type = BP_Suspend_Activity::$type;

		if ( empty( $item_id ) ) {
			return false;
		}

		if ( 'activity_comment' === $activity['type'] ) {
			$activity_comment_data = new BP_Moderation_Activity_Comment();
			$item_data             = $activity_comment_data->update_button_sub_items( $item_id );
			$item_id               = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type             = ( isset( $item_data['type'] ) ) ? $item_data['type'] : BP_Suspend_Activity_Comment::$type;
		} else {
			$activity_data = new BP_Moderation_Activity();
			$item_data     = $activity_data->update_button_sub_items( $item_id );
			$item_id       = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type     = ( isset( $item_data['type'] ) ) ? $item_data['type'] : $item_type;
		}

		return bp_moderation_get_report_button_text( $item_type, $item_id );
	}

	/**
	 * Function to get activity report type.
	 *
	 * @param BP_Activity_Activity $activity Activity array.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_activity_report_type( $activity ) {
		$item_id   = $activity['id'];
		$item_type = BP_Suspend_Activity::$type;

		if ( empty( $item_id ) ) {
			return false;
		}

		if ( 'activity_comment' === $activity['type'] ) {
			$activity_comment_data = new BP_Moderation_Activity_Comment();
			$item_data             = $activity_comment_data->update_button_sub_items( $item_id );
			$item_id               = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type             = ( isset( $item_data['type'] ) ) ? $item_data['type'] : BP_Suspend_Activity_Comment::$type;
		} else {
			$activity_data = new BP_Moderation_Activity();
			$item_data     = $activity_data->update_button_sub_items( $item_id );
			$item_id       = ( isset( $item_data['id'] ) ) ? $item_data['id'] : $item_id;
			$item_type     = ( isset( $item_data['type'] ) ) ? $item_data['type'] : $item_type;
		}

		return bp_moderation_get_report_type( $item_type, $item_id );
	}

	/**
	 * Filter an activity value returned from the API(Activity/Activity Comment)
	 *
	 * @param WP_REST_Response     $response The response data.
	 * @param WP_REST_Request      $request  Request used to generate the response.
	 * @param BP_Activity_Activity $activity The activity object.
	 *
	 * @return mixed
	 */
	public function bp_rest_moderation_activity_prepare_value( $response, $request, $activity ) {

		$data = $response->get_data();

		$type = BP_Suspend_Activity::$type;
		if ( 'activity_comment' === $activity->type ) {
			$type = BP_Suspend_Activity_Comment::$type;
		}

		$is_user_suspended  = bp_moderation_is_user_suspended( $activity->user_id );
		$is_user_blocked    = bp_moderation_is_user_blocked( $activity->user_id );
		$is_blocked_by_user = bb_moderation_is_user_blocked_by( $activity->user_id );
		$is_hidden          = bp_moderation_is_content_hidden( $activity->id, $type );

		if ( empty( $is_user_suspended ) && empty( $is_user_blocked ) && empty( $is_blocked_by_user ) && empty( $is_hidden ) ) {
			return $response;
		}

		if ( $is_user_suspended || $is_user_blocked || $is_blocked_by_user ) {
			$user_displayname = bp_core_get_user_displayname( $activity->user_id );
			if ( $is_user_suspended ) {
				$data['name'] = bb_moderation_is_suspended_label( $activity->user_id );
			} elseif ( $is_user_blocked ) {
				$data['name'] = bb_moderation_has_blocked_label( $user_displayname, $activity->user_id );
			} elseif ( $is_blocked_by_user ) {
				$data['name'] = bb_moderation_is_blocked_label( $user_displayname, $activity->user_id );
			}

			if ( isset( $data['user_avatar'] ) ) {
				$blocked_by_show_avatar = false;
				$group_id               = $request->get_param( 'group_id' );
				if ( empty( $group_id ) ) {
					$activity_endpoint = new BP_REST_Activity_Endpoint();
					$activity_data     = $activity_endpoint->bp_rest_activitiy_edit_data( $activity );
					$group_id          = ! empty( $activity_data['group_id'] ) ? $activity_data['group_id'] : '';
				}
				if (
					$is_blocked_by_user &&
					! empty( $group_id ) &&
					(
						groups_is_user_admin( bp_loggedin_user_id(), $group_id ) ||
						groups_is_user_mod( bp_loggedin_user_id(), $group_id )
					)
				) {
					$blocked_by_show_avatar = true;
				}
				if ( true === $blocked_by_show_avatar ) {
					remove_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );
					add_filter( 'bb_get_blocked_avatar_url', array( $this, 'bb_moderation_fetch_avatar_url_filter' ), 10, 3 );
				}
				$data['user_avatar'] = array(
					'full'  => bp_core_fetch_avatar(
						array(
							'item_id' => $data['user_id'],
							'html'    => false,
							'type'    => 'full',
						)
					),

					'thumb' => bp_core_fetch_avatar(
						array(
							'item_id' => $data['user_id'],
							'html'    => false,
						)
					),
				);
				if ( true === $blocked_by_show_avatar ) {
					remove_filter( 'bb_get_blocked_avatar_url', array( $this, 'bb_moderation_fetch_avatar_url_filter' ), 10, 3 );
					add_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );
				}
			}
		}

		$content      = $activity->content;
		$hide_objects = false;
		if ( $is_hidden ) {
			$content            = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );
			$data['can_report'] = false;
		} elseif ( $is_user_suspended && 'groups' !== $activity->component ) {
			$is_suspended_content = bb_moderation_is_suspended_message( $content, $type, $activity->id );
			if ( $content !== $is_suspended_content ) {
				$content      = $is_suspended_content;
				$hide_objects = true;
			}
		} elseif ( $is_user_blocked && 'groups' !== $activity->component  ) {
			$has_blocked_content = bb_moderation_has_blocked_message( $content, $type, $activity->id );
			if ( $content !== $has_blocked_content ) {
				$content      = $has_blocked_content;
				$hide_objects = true;
			}
		} elseif ( $is_blocked_by_user && 'groups' !== $activity->component  ) {
			$s_blocked_content = bb_moderation_is_blocked_message( $content, $type, $activity->id );
			if ( $content !== $s_blocked_content ) {
				$content      = $s_blocked_content;
				$hide_objects = true;
			}
		}
		if ( true === $hide_objects ) {
			$data['media_gif']    = null;
			$data['bp_media_ids'] = null;
			$data['bp_documents'] = null;
			$data['bp_videos']    = null;
		}

		$data['can_edit']     = false;
		$data['can_delete']   = false;
		$data['can_favorite'] = false;
		$data['can_comment']  = false;

		if (
			! $is_hidden &&
			bb_is_group_activity_comment( $activity ) ||
			(
				'activity_comment' !== $activity->type &&
				'groups' === $activity->component
			)
		) {
			$data['can_favorite'] = true;
			$data['can_comment']  = true;
		}

		$data['content'] = array(
			'raw'      => $content,
			'rendered' => wpautop( $content ),
		);

		$data['content_stripped'] = $content;

		$response->set_data( $data );

		return $response;
	}

	/********************** Group Support *****************************************/

	/**
	 * Added support for blocked content into Group.
	 */
	protected function bp_rest_moderation_group_support() {

		bp_rest_register_field(
			'groups',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_group_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'groups',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_group_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the group is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'groups',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_group_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'groups',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_group_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		// Added moderation data into group members endpoint.
		register_rest_field(
			'bp_group_members',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_member_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'bp_group_members',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_member_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the member is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		add_filter( 'bp_rest_group_members_prepare_value', array( $this, 'bb_rest_group_members_moderation_value' ), 999, 3 );
	}

	/**
	 * The function to use to get can_report of the group REST Field.
	 *
	 * @param BP_Groups_Group $group Group object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_group_can_report( $group ) {
		$group_id = $group['id'];

		if ( empty( $group_id ) ) {
			return false;
		}

		if ( ! empty( $group['creator_id'] ) && ( bp_moderation_is_user_suspended( $group['creator_id'] ) || bp_moderation_is_user_blocked( $group['creator_id'] ) ) ) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $group_id, BP_Suspend_Group::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the group REST Field.
	 *
	 * @param BP_Groups_Group $group Group object.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_group_is_reported( $group ) {
		$group_id = $group['id'];

		if ( empty( $group_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $group_id, BP_Suspend_Group::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get group report button text.
	 *
	 * @param BP_Groups_Group $group Group object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_group_report_button_text( $group ) {
		$group_id = $group['id'];

		if ( empty( $group_id ) ) {
			return false;
		}

		return bp_moderation_get_report_button_text( BP_Suspend_Group::$type, $group_id );
	}

	/**
	 * Function to get group report type.
	 *
	 * @param BP_Groups_Group $group Group object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_group_report_type( $group ) {
		$group_id = $group['id'];

		if ( empty( $group_id ) ) {
			return false;
		}

		return bp_moderation_get_report_type( BP_Suspend_Group::$type, $group_id );
	}

	/********************** Forums Support *****************************************/

	/**
	 * Added support for blocked content into forum.
	 */
	protected function bp_rest_moderation_forums_support() {

		bp_rest_register_field(
			'forums',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_forum_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'forums',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_forum_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the Forum is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'forums',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_forum_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Forum report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'forums',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_forum_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Forum report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * The function to use to get can_report of the forum REST Field.
	 *
	 * @param WP_Post $forum Forum post object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_forum_can_report( $forum ) {
		$forum_id = $forum['id'];

		if ( empty( $forum_id ) || ! empty( $forum['group'] ) ) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $forum_id, BP_Suspend_Forum::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the forum REST Field.
	 *
	 * @param WP_Post $forum Forum post object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_forum_is_reported( $forum ) {
		$forum_id = $forum['id'];

		if ( empty( $forum_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $forum_id, BP_Suspend_Forum::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get forum report button text.
	 *
	 * @param WP_Post $forum Forum post object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_forum_report_button_text( $forum ) {
		$forum_id = $forum['id'];

		if ( empty( $forum_id ) ) {
			return false;
		}

		return bp_moderation_get_report_button_text( BP_Suspend_Forum::$type, $forum_id );
	}

	/**
	 * Function to get forum report type.
	 *
	 * @param WP_Post $forum Forum post object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_forum_report_type( $forum ) {
		$forum_id = $forum['id'];

		if ( empty( $forum_id ) ) {
			return false;
		}

		return bp_moderation_get_report_type( BP_Suspend_Forum::$type, $forum_id );
	}

	/********************** Topic Support *****************************************/

	/**
	 * Added support for blocked content into topic.
	 */
	protected function bp_rest_moderation_topic_support() {

		register_rest_field(
			'topics',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_topic_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'topics',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_topic_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the topic is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'topics',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_topic_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Topic report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'topics',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_topic_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Topic report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * The function to use to get can_report of the topic REST Field.
	 *
	 * @param WP_Post $topic Topic post object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_topic_can_report( $topic ) {
		$topic_id = $topic['id'];

		if ( empty( $topic_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $topic_id, BP_Suspend_Forum_Topic::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the topic REST Field.
	 *
	 * @param WP_Post $topic Topic post object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_topic_is_reported( $topic ) {
		$topic_id = $topic['id'];

		if ( empty( $topic_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $topic_id, BP_Suspend_Forum_Topic::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get topic report button text.
	 *
	 * @param WP_Post $topic Topic post object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_topic_report_button_text( $topic ) {
		$topic_id = $topic['id'];

		if ( empty( $topic_id ) ) {
			return false;
		}

		return bp_moderation_get_report_button_text( BP_Suspend_Forum_Topic::$type, $topic_id );
	}

	/**
	 * Function to get topic report type.
	 *
	 * @param WP_Post $topic Topic post object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_topic_report_type( $topic ) {
		$topic_id = $topic['id'];

		if ( empty( $topic_id ) ) {
			return false;
		}

		return bp_moderation_get_report_type( BP_Suspend_Forum_Topic::$type, $topic_id );
	}

	/********************** Reply Support *****************************************/

	/**
	 * Added support for blocked content into reply.
	 */
	protected function bp_rest_moderation_reply_support() {

		register_rest_field(
			'reply',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_reply_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'reply',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_reply_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the reply is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'reply',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_reply_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Reply report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'reply',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_reply_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Reply report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		add_filter( 'bp_rest_reply_prepare_value', array( $this, 'bp_rest_moderation_reply_prepare_value' ), 999, 3 );
	}

	/**
	 * The function to use to get can_report of the reply REST Field.
	 *
	 * @param WP_Post $reply Reply post object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_reply_can_report( $reply ) {
		$reply_id = $reply['id'];

		if ( empty( $reply_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $reply_id, BP_Suspend_Forum_Reply::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the reply REST Field.
	 *
	 * @param WP_Post $reply Reply post object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_reply_is_reported( $reply ) {
		$reply_id = $reply['id'];

		if ( empty( $reply_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $reply_id, BP_Suspend_Forum_Reply::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get reply report button text.
	 *
	 * @param WP_Post $reply Reply post object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_reply_report_button_text( $reply ) {
		$reply_id = $reply['id'];

		if ( empty( $reply_id ) ) {
			return false;
		}

		return bp_moderation_get_report_button_text( BP_Suspend_Forum_Reply::$type, $reply_id );
	}

	/**
	 * Function to get reply report type.
	 *
	 * @param WP_Post $reply Reply post object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_reply_report_type( $reply ) {
		$reply_id = $reply['id'];

		if ( empty( $reply_id ) ) {
			return false;
		}

		return bp_moderation_get_report_type( BP_Suspend_Forum_Reply::$type, $reply_id );
	}

	/**
	 * Filter a reply value returned from the API.
	 *
	 * @param WP_REST_Response $response The response data.
	 * @param WP_REST_Request  $request  Request used to generate the response.
	 * @param WP_Post          $reply    Reply post object.
	 *
	 * @return mixed
	 */
	public function bp_rest_moderation_reply_prepare_value( $response, $request, $reply ) {

		$data = $response->get_data();

		$type = BP_Suspend_Forum_Reply::$type;

		$is_user_suspended  = bp_moderation_is_user_suspended( $reply->post_author );
		$is_user_blocked    = bp_moderation_is_user_blocked( $reply->post_author );
		$is_hidden          = bp_moderation_is_content_hidden( $reply->ID, $type );
		$is_blocked_by_user = bb_moderation_is_user_blocked_by( $reply->post_author );
		$is_user_inactive   = bp_is_user_inactive( $reply->post_author );

		if (
			empty( $is_user_suspended ) &&
			empty( $is_user_blocked ) &&
			empty( $is_blocked_by_user ) &&
			empty( $is_hidden ) &&
			empty( $is_user_inactive )
		) {
			return $response;
		}

		$content      = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );
		$hide_objects = false;
		if ( $is_user_suspended || $is_user_blocked || $is_blocked_by_user || $is_user_inactive ) {
			$user_displayname = bp_core_get_user_displayname( $reply->post_author );
			if ( $is_user_suspended ) {
				$data['name'] = bb_moderation_is_suspended_label( $reply->post_author );
				$content      = bb_moderation_is_suspended_message( $reply->post_content, $type, $reply->ID );
				if ( isset( $data['content']['raw'] ) && $data['content']['raw'] !== $content ) {
					$hide_objects = true;
				}
			} elseif ( $is_user_blocked ) {
				$data['name'] = bb_moderation_has_blocked_label( $user_displayname, $reply->post_author );
				$content      = bb_moderation_has_blocked_message( $reply->post_content, $type, $reply->ID );
				if ( isset( $data['content']['raw'] ) && $data['content']['raw'] !== $content ) {
					$hide_objects = true;
				}
			} elseif ( $is_blocked_by_user ) {
				$data['name'] = bb_moderation_is_blocked_label( $user_displayname, $reply->post_author );
				$content      = bb_moderation_is_blocked_message( $reply->post_content, $type, $reply->ID );
				if ( isset( $data['content']['raw'] ) && $data['content']['raw'] !== $content ) {
					$hide_objects = true;
				}
			} elseif ( $is_user_inactive ) {
				$data['name'] = bb_moderation_is_deleted_label();
				$content      = $reply->post_content;
			}
		}
		if ( true === $hide_objects ) {
			$data['bbp_media']     = null;
			$data['bbp_media_gif'] = null;
			$data['bbp_documents'] = null;
			$data['bbp_videos']    = null;
		}

		if ( isset( $data['short_content'] ) ) {
			$data['short_content'] = wpautop( $content );
		}

		$data['content'] = array(
			'raw'      => $content,
			'rendered' => wpautop( $content ),
		);

		$response->set_data( $data );

		return $response;
	}

	/********************** Member Support *****************************************/
	/**
	 * Added support for blocked members.
	 */
	protected function bp_rest_moderation_member_support() {

		bp_rest_register_field(
			'members',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_member_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'members',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_member_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the member is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'members',
			'can_user_report',
			array(
				'get_callback' => array( $this, 'bp_rest_member_can_user_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'members',
			'user_reported',
			array(
				'get_callback' => array( $this, 'bp_rest_member_is_user_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the member is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		add_filter( 'bp_rest_members_prepare_value', array( $this, 'bp_rest_moderation_prepare_value' ), 999, 3 );
	}

	/**
	 * The function to use to get can_report of the members REST Field.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_member_can_report( $user ) {
		$user_id = $user['id'];

		if ( empty( $user_id ) ) {
			return false;
		}

		if ( ! empty( $user_id ) && ( bp_moderation_is_user_suspended( $user_id ) || bp_moderation_is_user_blocked( $user_id ) ) ) {
			return false;
		}

		if ( is_user_logged_in() && ! user_can( $user_id, 'administrator' ) && bp_moderation_user_can( $user_id, BP_Suspend_Member::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the members REST Field.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_member_is_reported( $user ) {
		$user_id = $user['id'];

		if ( empty( $user_id ) ) {
			return false;
		}

		if (
			is_user_logged_in() &&
			$this->bp_rest_moderation_report_exist( $user_id, BP_Suspend_Member::$type ) &&
			! bp_moderation_is_user_suspended( $user_id )
		) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the members REST Field.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_member_is_user_reported( $user ) {
		$user_id = $user['id'];

		if ( empty( $user_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $user_id, BP_Moderation_Members::$moderation_type_report ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get can_user_report of the members REST Field.
	 *
	 * @param WP_User $user User object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_member_can_user_report( $user ) {
		$user_id = $user['id'];

		if ( empty( $user_id ) ) {
			return false;
		}

		if ( ! empty( $user_id ) && ( bp_moderation_is_user_suspended( $user_id ) || bp_moderation_report_exist( $user_id, BP_Moderation_Members::$moderation_type_report ) ) ) {
			return false;
		}

		if ( is_user_logged_in() && ! user_can( $user_id, 'administrator' ) && bp_moderation_user_can( $user_id, BP_Moderation_Members::$moderation_type_report ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Filters user data returned from the API.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 * @param WP_User          $user     WP_User object.
	 *
	 * @return WP_REST_Response
	 */
	public function bp_rest_moderation_prepare_value( $response, $request, $user ) {
		$data = $response->get_data();

		$type = BP_Suspend_Member::$type;

		$is_user_suspended  = bp_moderation_is_user_suspended( $user->ID );
		$is_user_blocked    = bp_moderation_is_user_blocked( $user->ID );
		$is_hidden          = bp_moderation_is_content_hidden( $user->ID, $type );
		$is_blocked_by_user = bb_moderation_is_user_blocked_by( $user->ID );
		if ( empty( $is_user_suspended ) && empty( $is_user_blocked ) && empty( $is_blocked_by_user ) && empty( $is_hidden ) ) {
			return $response;
		}

		$username_visible         = $request->get_param( 'username_visible' );
		$_GET['username_visible'] = $username_visible;

		if ( empty( $username_visible ) ) {
			$user_displayname = bp_core_get_user_displayname( $user->ID );
			if ( $is_user_suspended ) {
				$data['name'] = bb_moderation_is_suspended_label( $user->ID );
			} elseif ( $is_user_blocked ) {
				$data['name'] = bb_moderation_has_blocked_label( $user_displayname, $user->ID );
			} elseif ( $is_blocked_by_user ) {
				$data['name'] = bb_moderation_is_blocked_label( $user_displayname, $user->ID );
			}
		}
		$data['profile_name'] = bp_core_get_user_displayname( $data['id'] );
		$data['user_login']   = '';
		$data['xprofile']     = new stdClass();
		$data['followers']    = 0;
		$data['following']    = 0;
		$data['mention_name'] = '';
		$data['cover_url']    = '';

		$response->set_data( $data );

		return $response;
	}

	/********************** Media Support *****************************************/

	/**
	 * Added support for blocked Media.
	 */
	protected function bp_rest_moderation_media_support() {

		bp_rest_register_field(
			'media',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_media_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'media',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_media_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the media is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'media',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_media_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Media report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'media',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_media_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Media report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * Added support for blocked Video.
	 */
	protected function bp_rest_moderation_video_support() {

		bp_rest_register_field(
			'video',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_media_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'video',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_media_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the Video is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'video',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_media_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Video report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'video',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_media_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Video report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * The function to use to get can_report of the media REST Field.
	 *
	 * @param BP_Media $media The Media object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_media_can_report( $media ) {
		$media_id = $media['id'];

		if ( empty( $media_id ) ) {
			return false;
		}

		if ( ! empty( $media['type'] ) && 'video' === $media['type'] ) {
			$type = BP_Suspend_Video::$type;
		} else {
			$type = BP_Suspend_Media::$type;
		}

		if ( ! empty( $media['user_id'] ) && ( bp_moderation_is_user_suspended( $media['user_id'] ) || bp_moderation_is_user_blocked( $media['user_id'] ) ) ) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $media_id, $type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the media REST Field.
	 *
	 * @param BP_Media $media The Media object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_media_is_reported( $media ) {
		$media_id = $media['id'];

		if ( empty( $media_id ) ) {
			return false;
		}

		if ( ! empty( $media['type'] ) && 'video' === $media['type'] ) {
			$type = BP_Suspend_Video::$type;
		} else {
			$type = BP_Suspend_Media::$type;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $media_id, $type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get media report button text.
	 *
	 * @param BP_Media $media The Media object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_media_report_button_text( $media ) {
		$media_id = $media['id'];

		if ( empty( $media_id ) ) {
			return false;
		}

		if ( ! empty( $media['type'] ) && 'video' === $media['type'] ) {
			$type = BP_Suspend_Video::$type;
		} else {
			$type = BP_Suspend_Media::$type;
		}

		return bp_moderation_get_report_button_text( $type, $media_id );
	}

	/**
	 * Function to get media report type.
	 *
	 * @param BP_Media $media The Media object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_media_report_type( $media ) {
		$media_id = $media['id'];

		if ( empty( $media_id ) ) {
			return false;
		}

		if ( ! empty( $media['type'] ) && 'video' === $media['type'] ) {
			$type = BP_Suspend_Video::$type;
		} else {
			$type = BP_Suspend_Media::$type;
		}

		return bp_moderation_get_report_type( $type, $media_id );
	}

	/********************** Document Support *****************************************/

	/**
	 * Added support for blocked Document.
	 */
	protected function bp_rest_moderation_document_support() {

		bp_rest_register_field(
			'document',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_document_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'bp_document_folder',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_document_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'document',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_document_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Document report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'document',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_document_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Document report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		bp_rest_register_field(
			'document',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_document_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the document is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'bp_document_folder',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_document_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the document is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);
	}

	/**
	 * The function to use to get can_report of the document REST Field.
	 *
	 * @param BP_Document $document The Document object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_document_can_report( $document ) {
		$document_id = $document['id'];

		if ( empty( $document ) ) {
			return false;
		}

		$type = BP_Suspend_Document::$type;
		if ( 'folder' === $document['type'] ) {
			return false;
		}

		if ( ! empty( $document['user_id'] ) && ( bp_moderation_is_user_suspended( $document['user_id'] ) || bp_moderation_is_user_blocked( $document['user_id'] ) ) ) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $document_id, $type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the document REST Field.
	 *
	 * @param BP_Document $document The Document object.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_document_is_reported( $document ) {
		$document_id = $document['id'];

		if ( empty( $document ) ) {
			return false;
		}

		$type = BP_Suspend_Document::$type;
		if ( 'folder' === $document['type'] ) {
			return false;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $document_id, $type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get document report button text.
	 *
	 * @param BP_Document $document The Document object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_document_report_button_text( $document ) {
		$document_id = $document['id'];

		if ( empty( $document ) ) {
			return false;
		}

		return bp_moderation_get_report_button_text( BP_Suspend_Document::$type, $document_id );
	}

	/**
	 * Function to get document report type.
	 *
	 * @param BP_Document $document The Document object.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_document_report_type( $document ) {
		$document_id = $document['id'];

		if ( empty( $document ) ) {
			return false;
		}

		return bp_moderation_get_report_type( BP_Suspend_Document::$type, $document_id );
	}

	/********************** Messages Support *****************************************/

	/**
	 * Added support for blocked Document.
	 */
	protected function bp_rest_moderation_messages_support() {
		add_filter( 'bp_rest_messages_prepare_recipient_value', array( $this, 'bp_rest_moderation_messages_prepare_recipient_value' ), 999, 2 );

		add_filter( 'bp_rest_message_prepare_value', array( $this, 'bp_rest_moderation_message_prepare_value' ), 999, 2 );

		add_filter( 'bp_rest_messages_prepare_value', array( $this, 'bp_rest_moderation_messages_prepare_value' ), 999, 3 );
	}

	/**
	 * Filter a recipient value returned from the API.
	 *
	 * @param array  $data      The recipient value for the REST response.
	 * @param object $recipient The recipient object.
	 *
	 * @return array
	 */
	public function bp_rest_moderation_messages_prepare_recipient_value( $data, $recipient ) {
		$data['current_user_permissions']['can_report']      = false;
		$data['current_user_permissions']['reported']        = false;
		$data['current_user_permissions']['can_user_report'] = false;
		$data['current_user_permissions']['user_reported']   = false;

		$is_user_suspended = bp_moderation_is_user_suspended( $recipient->user_id );
		$is_user_blocked   = bp_moderation_is_user_blocked( $recipient->user_id );
		$is_user_reported  = $this->bp_rest_moderation_report_exist( $recipient->user_id, BP_Moderation_Members::$moderation_type_report );

		if ( ! empty( $recipient->user_id ) && ( bp_moderation_is_user_suspended( $recipient->user_id ) || bp_moderation_is_user_blocked( $recipient->user_id ) ) ) {
			$data['current_user_permissions']['can_report'] = false;
		}

		if ( is_user_logged_in() && ! user_can( $recipient->user_id, 'administrator' ) && bp_moderation_user_can( $recipient->user_id, BP_Suspend_Member::$type ) ) {
			$data['current_user_permissions']['can_report'] = true;
		}

		if ( is_user_logged_in() && ! user_can( $recipient->user_id, 'administrator' ) && bp_moderation_user_can( $recipient->user_id, BP_Moderation_Members::$moderation_type_report ) && ! $is_user_reported ) {
			$data['current_user_permissions']['can_user_report'] = true;
		}

		if ( is_user_logged_in() && $is_user_reported ) {
			$data['current_user_permissions']['user_reported'] = true;
		}

		if ( $is_user_suspended ) {
			$data['current_user_permissions']['can_user_report'] = false;
			$data['current_user_permissions']['user_reported']   = true;
		}

		if ( ! empty( $recipient->user_id ) ) {
			$data['current_user_permissions']['can_user_report'] = empty( get_userdata( $recipient->user_id ) ) ? false : $data['current_user_permissions']['can_user_report'];
		}

		if ( empty( $is_user_suspended ) && empty( $is_user_blocked ) ) {
			return $data;
		}

		$data['current_user_permissions']['reported'] = true;

		return $data;
	}

	/**
	 * Filter a message value returned from the API.
	 *
	 * @param array               $data    The message value for the REST response.
	 * @param BP_Messages_Message $message The Message object.
	 *
	 * @return array
	 */
	public function bp_rest_moderation_message_prepare_value( $data, $message ) {
		$sender_user        = (int) $message->sender_id;
		$is_user_suspended  = bp_moderation_is_user_suspended( $sender_user );
		$is_user_blocked    = bp_moderation_is_user_blocked( $sender_user );
		$is_blocked_by_user = bb_moderation_is_user_blocked_by( $sender_user );

		if ( empty( $is_user_suspended ) && empty( $is_user_blocked ) ) {
			return $data;
		}

		$content = '';

		if ( $is_user_suspended ) {
			$content = bb_moderation_is_suspended_message( $message->message, BP_Moderation_Message::$moderation_type, $message->id );
		} elseif ( $is_user_blocked ) {
			$content = bb_moderation_has_blocked_message( $message->message, BP_Moderation_Message::$moderation_type, $message->id );
		} elseif ( $is_blocked_by_user ) {
			$content = bb_moderation_is_blocked_message( $message->message, BP_Moderation_Message::$moderation_type, $message->id );
		}

		if ( ! empty( $content ) && $message->message !== $content ) {
			if ( ! empty( $data['media_gif'] ) ) {
				$data['media_gif'] = null;
			}
			if ( ! empty( $data['bp_media_ids'] ) ) {
				$data['bp_media_ids'] = null;
			}
			if ( ! empty( $data['bp_documents'] ) ) {
				$data['bp_documents'] = null;
			}
			if ( ! empty( $data['bp_videos'] ) ) {
				$data['bp_videos'] = null;
			}
		}

		if ( ! empty( $content ) ) {
			$data['message'] = array(
				'raw'      => wp_strip_all_tags( $content ),
				'rendered' => apply_filters( 'bp_get_the_thread_message_content', wpautop( $content ) ),
			);
		}

		$data['display_date'] = '';

		return $data;
	}

	/**
	 * Filter a thread value returned from the API.
	 *
	 * @param WP_REST_Response   $response Response generated by the request.
	 * @param WP_REST_Request    $request  Request used to generate the response.
	 * @param BP_Messages_Thread $thread   The thread object.
	 *
	 * @return WP_REST_Response
	 */
	public function bp_rest_moderation_messages_prepare_value( $response, $request, $thread ) {
		$data = $response->get_data();

		if ( ! isset( $data['last_sender_id'] ) && empty( $data['last_sender_id'] ) ) {
			return $response;
		}

		$sender_user        = $data['last_sender_id'];
		$is_user_suspended  = bp_moderation_is_user_suspended( $sender_user );
		$is_user_blocked    = bp_moderation_is_user_blocked( $sender_user );
		$is_blocked_by_user = bb_moderation_is_user_blocked_by( $sender_user );

		if ( empty( $is_user_suspended ) && empty( $is_user_blocked ) ) {
			return $response;
		}

		$content = '';

		if ( $is_user_suspended ) {
			$content = bb_moderation_is_suspended_message( $data['message']['rendered'], BP_Moderation_Message::$moderation_type, $data['message_id'] );
		} elseif ( $is_blocked_by_user ) {
			$content = bb_moderation_is_blocked_message( $data['message']['rendered'], BP_Moderation_Message::$moderation_type, $data['message_id'] );
		} elseif ( $is_user_blocked ) {
			$content = bb_moderation_has_blocked_message( $data['message']['rendered'], BP_Moderation_Message::$moderation_type, $data['message_id'] );
		}

		if ( ! empty( $content ) ) {
			if ( isset( $data['excerpt'] ) ) {
				if ( isset( $data['excerpt']['raw'] ) ) {
					$data['excerpt']['raw'] = $content;
				}
				if ( isset( $data['excerpt']['rendered'] ) ) {
					$data['excerpt']['rendered'] = apply_filters( 'bp_get_message_thread_excerpt', $content );
				}
			}

			if ( isset( $data['message'] ) ) {
				if ( isset( $data['message']['raw'] ) ) {
					$data['message']['raw'] = $content;
				}
				if ( isset( $data['message']['rendered'] ) ) {
					$data['message']['rendered'] = apply_filters( 'bp_get_the_thread_message_content', wpautop( $content ) );
				}
			}

			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Wrapper function for bp_moderation_report_exist().
	 *
	 * @param integer $item_id   Item ID.
	 * @param string  $item_type Item Type.
	 *
	 * @return bool
	 */
	protected function bp_rest_moderation_report_exist( $item_id, $item_type ) {
		$sub_items     = bp_moderation_get_sub_items( $item_id, $item_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $item_id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : $item_type;

		if ( is_user_logged_in() && bp_moderation_report_exist( $item_sub_id, $item_sub_type ) ) {
			return true;
		}

		return false;
	}

	/********************** Blog Comment Support *****************************************/
	/**
	 * Added support for blogs comments.
	 */
	protected function bp_rest_moderation_blog_comments_support() {
		register_rest_field(
			'comment',
			'can_report',
			array(
				'get_callback' => array( $this, 'bp_rest_blog_comment_can_report' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user can report or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'comment',
			'reported',
			array(
				'get_callback' => array( $this, 'bp_rest_blog_comment_is_reported' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the comment is reported or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'comment',
			'report_button_text',
			array(
				'get_callback' => array( $this, 'bp_rest_blog_comment_report_button_text' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Blog comment report button text.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'comment',
			'report_type',
			array(
				'get_callback' => array( $this, 'bp_rest_blog_comment_report_type' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Blog comment report type.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			)
		);

		register_rest_field(
			'comment',
			'can_reply',
			array(
				'get_callback' => array( $this, 'bb_rest_blog_comment_can_reply' ),
				'schema'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can reply or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			)
		);

		add_filter( 'rest_prepare_comment', array( $this, 'bp_rest_moderation_prepare_comment' ), 9999, 2 );

		add_filter( 'rest_pre_insert_comment', array( $this, 'bb_rest_pre_insert_comment' ), 10, 2 );
	}

	/**
	 * The function to use to get can_report of the activity REST Field.
	 *
	 * @param WP_Post $post Post Array.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_blog_comment_can_report( $post ) {
		$comment_id = $post['id'];

		if ( empty( $comment_id ) ) {
			return false;
		}

		if ( ! empty( $post['author'] ) && ( bp_moderation_is_user_suspended( $post['author'] ) || bp_moderation_is_user_blocked( $post['author'] ) ) ) {
			return false;
		}

		if ( is_user_logged_in() && bp_moderation_user_can( $comment_id, BP_Suspend_Comment::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * The function to use to get reported of the activity REST Field.
	 *
	 * @param WP_Post $post Post Array.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bp_rest_blog_comment_is_reported( $post ) {
		$comment_id = $post['id'];

		if ( empty( $comment_id ) ) {
			return false;
		}

		if ( is_user_logged_in() && $this->bp_rest_moderation_report_exist( $comment_id, BP_Suspend_Comment::$type ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Function to get blog comment report button text.
	 *
	 * @param WP_Post $post Post Array.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_blog_comment_report_button_text( $post ) {
		$comment_id = $post['id'];

		if ( empty( $comment_id ) ) {
			return false;
		}

		return bp_moderation_get_report_button_text( BP_Suspend_Comment::$type, $comment_id );
	}

	/**
	 * Function to get blog comment report type.
	 *
	 * @param WP_Post $post Post Array.
	 *
	 * @return false|mixed|void
	 */
	public function bp_rest_blog_comment_report_type( $post ) {
		$comment_id = $post['id'];

		if ( empty( $comment_id ) ) {
			return false;
		}

		return bp_moderation_get_report_type( BP_Suspend_Comment::$type, $comment_id );
	}

	/**
	 * Filters a comment returned from the REST API.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_Comment       $comment  The original comment object.
	 *
	 * @return WP_REST_Response
	 */
	public function bp_rest_moderation_prepare_comment( $response, $comment ) {

		$data = $response->get_data();

		$type = BP_Suspend_Comment::$type;

		$is_user_suspended  = bp_moderation_is_user_suspended( $comment->user_id );
		$is_user_blocked    = bp_moderation_is_user_blocked( $comment->user_id );
		$is_hidden          = bp_moderation_is_content_hidden( $comment->comment_ID, $type );
		$is_blocked_by_user = bb_moderation_is_user_blocked_by( $comment->user_id );
		$is_user_inactive   = bp_is_user_inactive( $comment->user_id );

		if (
			empty( $is_user_suspended ) &&
			empty( $is_user_blocked ) &&
			empty( $is_blocked_by_user ) &&
			empty( $is_hidden ) &&
			empty( $is_user_inactive )
		) {
			return $response;
		}

		$content = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );

		if ( $is_user_suspended || $is_user_blocked || $is_blocked_by_user || $is_user_inactive ) {
			$data['author_url'] = '';
			$user_displayname   = bp_core_get_user_displayname( $comment->user_id );
			if ( $is_user_suspended ) {
				$data['author_name'] = bb_moderation_is_suspended_label( $comment->user_id );
				$content             = bb_moderation_is_suspended_message( $comment->comment_content, $type, $comment->comment_ID );
			} elseif ( $is_user_blocked ) {
				$data['author_name'] = bb_moderation_has_blocked_label( $user_displayname, $comment->user_id );
				$content             = bb_moderation_has_blocked_message( $comment->comment_content, $type, $comment->comment_ID );
			} elseif ( $is_blocked_by_user ) {
				$data['author_name'] = bb_moderation_is_blocked_label( $user_displayname, $comment->user_id );
				$content             = bb_moderation_is_blocked_message( $comment->comment_content, $type, $comment->comment_ID );
			} elseif ( $is_user_inactive ) {
				$data['author_name'] = bb_moderation_is_deleted_label();
				$content             = $comment->comment_content;
			}
		}

		$data['content'] = array(
			'raw'      => $content,
			'rendered' => wpautop( $content ),
		);

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Function will return original avatar of blocked by member.
	 *
	 * @since BuddyBoss 2.1.6.2
	 *
	 * @param string $avatar_url     Updated avatar url.
	 * @param string $old_avatar_url Old avatar url before updated.
	 * @param array  $params         Array of parameters for the request.
	 *
	 * @return string $old_avatar_url  Old avatar url before updated.
	 */
	public function bb_moderation_fetch_avatar_url_filter( $avatar_url, $old_avatar_url, $params ) {
		return $old_avatar_url;
	}

	/**
	 * The function to check the logged-in member can reply or not to the comment.
	 *
	 * @param WP_Post $post Post Array.
	 *
	 * @return string The value of the REST Field to include into the REST response.
	 */
	public function bb_rest_blog_comment_can_reply( $post ) {
		$comment_id = $post['id'];

		if ( empty( $comment_id ) ) {
			return false;
		}

		if (
			! empty( $post['author'] ) &&
			(
				bp_moderation_is_user_suspended( $post['author'] ) ||
				bp_moderation_is_user_blocked( $post['author'] ) ||
				bb_moderation_is_user_blocked_by( $post['author'] )
			)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Function to check user can not reply to parent comment if parent comment added by blocked user or blocked by user.
	 *
	 * @param array|WP_Error  $prepared_comment The prepared comment data for wp_insert_comment().
	 * @param WP_REST_Request $request          Request used to insert the comment.
	 *
	 * @return array|WP_Error
	 */
	public function bb_rest_pre_insert_comment( $prepared_comment, $request ) {
		if ( empty( $prepared_comment['comment_parent'] ) ) {
			return $prepared_comment;
		}

		$user_id = BP_Moderation_Comment::get_content_owner_id( $prepared_comment['comment_parent'] );

		if (
			empty( $user_id ) ||
			! bp_moderation_is_user_blocked( $user_id ) ||
			! bb_moderation_is_user_blocked_by( $user_id )
		) {
			return $prepared_comment;
		}

		return new WP_Error(
			'bp_rest_comment_cannot_create_reply',
			__( 'Sorry, you are not allowed to reply on this Comment.', 'buddyboss' ),
			array( 'status' => 400 )
		);

	}

	/**
	 * Filters user data returned from the API.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 * @param WP_User          $user     WP_User object.
	 *
	 * @return WP_REST_Response
	 */
	public function bb_rest_group_members_moderation_value( $response, $request, $user ) {
		$data = $response->get_data();

		$is_user_blocked    = bp_moderation_is_user_blocked( $user->ID );
		$is_blocked_by_user = bb_moderation_is_user_blocked_by( $user->ID );
		if ( empty( $is_user_blocked ) && empty( $is_blocked_by_user ) ) {
			return $response;
		}

		$username_visible         = $request->get_param( 'username_visible' );
		$_GET['username_visible'] = $username_visible;

		if ( empty( $username_visible ) ) {
			$user_displayname = bp_core_get_user_displayname( $user->ID );
			if ( $is_user_blocked ) {
				$data['name'] = bb_moderation_has_blocked_label( $user_displayname, $user->ID );
			} elseif ( $is_blocked_by_user ) {
				$data['name'] = bb_moderation_is_blocked_label( $user_displayname, $user->ID );
			}
		}
		$data['profile_name']       = bp_core_get_user_displayname( $data['id'] );
		$data['user_login']         = '';
		$data['xprofile']           = new stdClass();
		$data['cover_url']          = '';
		$data['mention_name']       = '';
		$data['create_friendship']  = false;
		$data['send_group_message'] = false;
		$data['can_follow']         = false;
		if ( $is_blocked_by_user ) {
			$data['followers']       = 0;
			$data['following']       = 0;
			$data['registered_date'] = '';
			$data['last_activity']   = '';
		}
		$response->set_data( $data );

		return $response;
	}
}
