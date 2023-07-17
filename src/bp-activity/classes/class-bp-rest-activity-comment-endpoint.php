<?php
/**
 * BP REST: BP_REST_Activity_Comment_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activity endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Activity_Comment_Endpoint extends WP_REST_Controller {

	/**
	 * Activity endpoints class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Activity_Endpoint
	 */
	protected $activity_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace         = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base         = buddypress()->activity->id;
		$this->activity_endpoint = new BP_REST_Activity_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		$activity_endpoint = '/' . $this->rest_base . '/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$activity_endpoint . '/comment',
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
	}

	/**
	 * Retrieve activity comments.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {GET} /wp-json/buddyboss/v1/activity/:id/comment Get activity comments
	 * @apiName        GetActivityComment
	 * @apiGroup       Activity
	 * @apiDescription Get all comments for an activity.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 * @apiParam {String=threaded,stream,false} [display_comments=threaded] Comments by default, stream for within stream display, threaded for below each activity item.
	 */
	public function get_items( $request ) {

		$retval = array();

		$activity = $this->get_activity_object( $request );

		if ( empty( $activity->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid activity ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( ! empty( $activity->children ) ) {
			$request->set_param( 'context', 'view' );
			$retval['comments'] = $this->prepare_activity_comments( $activity->children, $request );
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of activity details is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_comment_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to activity comment.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
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

		if ( true === $retval && ! bp_is_active( 'activity' ) ) {
			$retval = new WP_Error(
				'bp_rest_component_required',
				__( 'Sorry, Activity component was not enabled.', 'buddyboss' ),
				array(
					'status' => '404',
				)
			);
		}

		/**
		 * Filter the activity comment permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_comment_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Create an activity comment.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api {POST} /wp-json/buddyboss/v1/activity/:id/comment Create activity comment
	 * @apiName CreateActivityComment
	 * @apiGroup Activity
	 * @apiDescription Create comment under activity.
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 * @apiParam {Number} [parent_id] ID of the parent activity/comment item.
	 * @apiParam {String} content The content of the comment.
	 * @apiParam {String=threaded,stream,false} [display_comments=threaded] Comments by default, stream for within stream display, threaded for below each activity item.
	 */
	public function create_item( $request ) {

		if ( true === $this->activity_endpoint->bp_rest_activity_content_validate( $request ) ) {
			return new WP_Error(
				'bp_rest_comment_blank_content',
				__( 'Please do not leave the comment area blank.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			$group_id = 0;
			// Get the parent activity.
			$parent_activity = new BP_Activity_Activity( $request['id'] );
			if ( bp_is_active( 'groups' ) && isset( $parent_activity->component ) && buddypress()->groups->id === $parent_activity->component ) {
				$group_id = isset( $parent_activity->group_id ) ? $parent_activity->group_id : 0;
			}
			if ( ! empty( $request['bp_media_ids'] ) && function_exists( 'bb_user_has_access_upload_media' ) ) {
				$can_send_media = bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), 0, 0, 'profile' );
				if ( ! $can_send_media ) {
					return new WP_Error(
						'bp_rest_bp_activity_media',
						__( 'You don\'t have access to send the media.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}
			}

			if ( ! empty( $request['bp_documents'] ) && function_exists( 'bb_user_has_access_upload_document' ) ) {
				$can_send_document = bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), 0, 0, 'profile' );
				if ( ! $can_send_document ) {
					return new WP_Error(
						'bp_rest_bp_activity_document',
						__( 'You don\'t have access to send the document.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}
			}

			if ( ! empty( $request['bp_videos'] ) && function_exists( 'bb_user_has_access_upload_video' ) ) {
				$can_send_video = bb_user_has_access_upload_video( $group_id, bp_loggedin_user_id(), 0, 0, 'profile' );
				if ( ! $can_send_video ) {
					return new WP_Error(
						'bp_rest_bp_activity_video',
						__( 'You don\'t have access to send the video.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}
			}

			if ( ! empty( $request['media_gif'] ) && function_exists( 'bb_user_has_access_upload_gif' ) ) {
				$can_send_gif = bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), 0, 0, 'profile' );
				if ( ! $can_send_gif ) {
					return new WP_Error(
						'bp_rest_bp_activity_gif',
						__( 'You don\'t have access to send the gif.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}
			}
		}

		if ( empty( $request['parent_id'] ) ) {
			$request['parent_id'] = '';
		}

		$args = array(
			'content'           => $request['content'],
			'parent_id'         => $request['parent_id'],
			'activity_id'       => $request['id'],
			'user_id'           => bp_loggedin_user_id(),
			'skip_notification' => true,
		);

		remove_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

		$comment_id = bp_activity_new_comment( $args );

		add_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

		if ( is_wp_error( $comment_id ) ) {
			return new WP_Error(
				'comment_error',
				esc_html( $comment_id->get_error_message() ),
				array(
					'status' => 500,
				)
			);
		}

		$activity_comment = new BP_Activity_Activity( $comment_id );

		$fields_update = $this->update_additional_fields_for_object( $activity_comment, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$activity = new BP_Activity_Activity( $request['id'] );

		bp_activity_at_name_send_emails( $activity );

		/**
		 * Fires near the end of an activity comment posting, before the returning of the comment ID.
		 * Sends a notification to the user.
		 *
		 * @param int                  $comment_id ID of the newly posted activity comment.
		 * @param array                $args       Array of parsed comment arguments.
		 * @param BP_Activity_Activity $activity   Activity item being commented on.
		 *
		 * @see   bp_activity_new_comment_notification_helper().
		 */
		do_action( 'bp_activity_comment_posted', $comment_id, $args, $activity );

		// Update current user's last activity.
		bp_update_user_last_activity();

		$retval            = array();
		$retval['created'] = true;

		if ( empty( $request['display_comments'] ) ) {
			$request->set_param( 'display_comments', 'threaded' );
		}

		$activity = $this->get_activity_object( $request );

		if ( ! empty( $activity->children ) ) {
			$request->set_param( 'context', 'view' );
			$retval['comments'] = $this->prepare_activity_comments( $activity->children, $request );
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity comment is created via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_create_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create an activity comment.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create an activity comment.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval   = true;
			$activity = $this->get_activity_object( $request );

			if ( empty( $activity ) || empty( $activity->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the activity comment `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_comment_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Edit the type of the some properties for the CREATABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = $this->get_collection_params();
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			$args['context']['default'] = 'edit';

			$args['content'] = array(
				'description'       => __( 'The content for the comment.', 'buddyboss' ),
				'required'          => false,
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['parent_id'] = array(
				'description'       => __( 'Parent comment ID.', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array $args Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_activity_comment_{$key}_query_arguments", $args, $method );
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
			'title'      => 'activity_comments',
			'type'       => 'object',
			'properties' => array(
				'created'  => array(
					'context'     => array( 'embed', 'edit' ),
					'description' => __( 'Whether the comment created or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'comments' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A list of comments for activity.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'properties'  => $this->activity_endpoint->get_item_schema()['properties'],
				),
			),
		);

		/**
		 * Filters the activity details schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_activity_comment_details_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of plugins.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Removing unused params.
		unset( $params['search'], $params['page'], $params['per_page'] );

		$params['id'] = array(
			'description' => __( 'A unique numeric ID for the activity.', 'buddyboss' ),
			'type'        => 'integer',
			'reqiured'    => true,
		);

		$params['display_comments'] = array(
			'description'       => __( 'Comments by default, stream for within stream display, threaded for below each activity item.', 'buddyboss' ),
			'default'           => 'threaded',
			'enum'              => array( 'stream', 'threaded' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_activity_collection_params', $params );
	}

	/**
	 * Get activity object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return BP_Activity_Activity|string An activity object.
	 * @since 0.1.0
	 */
	protected function get_activity_object( $request ) {
		$activity_id      = is_numeric( $request ) ? $request : (int) $request['id'];
		$display_comments = ( property_exists( $request, 'display_comments' ) ? $request['display_comments'] : true );
		$activity         = bp_activity_get_specific(
			array(
				'activity_ids'     => array( $activity_id ),
				'display_comments' => $display_comments,
			)
		);

		if ( is_array( $activity ) && ! empty( $activity['activities'][0] ) ) {
			return $activity['activities'][0];
		}

		return '';
	}

	/**
	 * Prepare activity comments.
	 *
	 * @param array           $comments Comments.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array           An array of activity comments.
	 * @since 0.1.0
	 */
	protected function prepare_activity_comments( $comments, $request ) {
		$data = array();

		if ( empty( $comments ) ) {
			return $data;
		}

		foreach ( $comments as $comment ) {
			$data[] = $this->activity_endpoint->prepare_response_for_collection(
				$this->activity_endpoint->prepare_item_for_response( $comment, $request )
			);
		}

		/**
		 * Filter activity comments returned from the API.
		 *
		 * @param array $data An array of activity comments.
		 * @param array $comments Comments.
		 * @param WP_REST_Request $request Request used to generate the response.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_prepare_comments', $data, $comments, $request );
	}
}
