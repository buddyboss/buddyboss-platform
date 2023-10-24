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

		register_rest_route(
			$this->namespace,
			$activity_endpoint . '/comment/(?P<comment_id>[\d]+)',
			array(
				'args'   => array(
					'comment_id' => array(
						'description' => __( 'A unique numeric ID for the activity comment.', 'buddyboss' ),
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
	 * Retrieve an activity comment.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/activity/:id/comment/:comment_id Get Activity Comment
	 * @apiName        GetBBActivityComment
	 * @apiGroup       Activity
	 * @apiDescription Retrieve single activity comment
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 * @apiParam {Number} comment_id A unique numeric ID for the activity comment.
	 * @apiParam {String=stream,threaded,false} [display_comments=false] No comments by default, stream for within stream display, threaded for below each activity item.
	 */
	public function get_item( $request ) {
		$activity_comment = $this->get_activity_comment_object( $request );

		if ( empty( $activity_comment->id ) ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid activity comment ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		} elseif ( $activity_comment->item_id !== (int) $request['id'] ) {
			return new WP_Error(
				'bp_rest_invalid_id',
				__( 'Invalid activity ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$request->set_param( 'context', 'view' );
		$comments = $this->prepare_activity_comments( array( $activity_comment ), $request );
		$retval  = ! empty( $comments[0] ) ? $comments[0] : $comments;

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity comment is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param BP_Activity_Activity $activity_comment Fetched activity comment.
		 * @param WP_REST_Response     $response         The response data.
		 * @param WP_REST_Request      $request          The request sent to the API.
		 */
		do_action( 'bp_rest_activity_comment_get_item', $activity_comment, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific activity comment.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
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

		if ( true === $retval && ! $this->can_see( $request ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you cannot view the activity comment.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the activity `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_comment_get_item_permissions_check', $retval, $request );
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

		$comment_id = bp_activity_new_comment( $args );

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

		bp_activity_at_name_send_emails( $activity_comment );

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
	 * Update an activity comment.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/activity/:id/comment/:comment_id Update activity comment
	 * @apiName        UpdateBBActivityComment
	 * @apiGroup       Activity
	 * @apiDescription Update single activity comment
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 * @apiParam {Number} comment_id A unique numeric ID for the activity comment.
	 * @apiParam {Number} [parent_id] The ID of some other object activity associated with this one.
	 * @apiParam {Number} [user_id] The ID for the author of the activity.
	 * @apiParam {String} [content] Allowed HTML content for the activity.
	 * @apiParam {Array} [bp_media_ids] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [bp_videos] Video specific IDs when Media component is enable.
	 * @apiParam {Array} [bp_documents] Document specific IDs when Media component is enable.
	 * @apiParam {Array} [media_gif] Save gif data into activity when Media component is enable. param(url,mp4)
	 */
	public function update_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$is_validate = $this->validate_activity_comment_request( $request );
		if ( is_wp_error( $is_validate ) ) {
			return $is_validate;
		}

		// GET and SET for activity comment edit.
		$edit_comment_id = (int) $request['comment_id'];
		if ( 0 < $edit_comment_id ) {
			$_POST['edit_comment'] = true;
		}

		$args = array(
			'id'                => $edit_comment_id,
			'content'           => $request['content'],
			'parent_id'         => $request['parent_id'],
			'activity_id'       => $request['id'],
			'user_id'           => bp_loggedin_user_id(),
			'skip_notification' => true,
			'error_type'        => 'wp_error',
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

		$activity_comment               = new BP_Activity_Activity( $comment_id );
		$activity_comment->edit_comment = true;

		$fields_update = $this->update_additional_fields_for_object( $activity_comment, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$current_comment = $this->get_activity_comment_object( $request );
		$comments        = $this->prepare_activity_comments( array( $current_comment ), $request );
		$retval          = ! empty( $comments[0] ) ? $comments[0] : $comments;

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity comment is updated via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param BP_Activity_Activity $activity_comment The updated activity comment.
		 * @param WP_REST_Response     $response         The response data.
		 * @param WP_REST_Request      $request          The request sent to the API.
		 */
		do_action( 'bp_rest_activity_comment_update_item', $activity_comment, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update an activity.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to update this activity comment.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$activity_comment = $this->get_activity_comment_object( $request );

			if ( empty( $activity_comment->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity comment ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				(
					function_exists( 'bb_is_activity_comment_edit_enabled' )
					&& ! bb_is_activity_comment_edit_enabled()
				) ||
				(
					function_exists( 'bb_activity_comment_user_can_edit' )
					&& ! bb_activity_comment_user_can_edit( $activity_comment )
				)
			) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update this activity comment.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( bp_activity_user_can_delete( $activity_comment ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the activity comment `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_comment_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete activity comment.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {DELETE} /wp-json/buddyboss/v1/activity/:id/comment/:comment_id Delete activity comment
	 * @apiName        DeleteBBActivityComment
	 * @apiGroup       Activity
	 * @apiDescription Delete single activity comment
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 * @apiParam {Number} comment_id A unique numeric ID for the activity comment.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the activity comment before it's deleted.
		$activity_comment = $this->get_activity_comment_object( $request );
		$previous         = $this->prepare_activity_comments( array( $activity_comment ), $request );

		$retval = bp_activity_delete_comment( $activity_comment->item_id, $activity_comment->id );

		if ( ! $retval ) {
			return new WP_Error(
				'bp_rest_activity_comment_cannot_delete',
				__( 'Could not delete the activity comment.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous[0],
			)
		);

		/**
		 * Fires after an activity comment is deleted via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param BP_Activity_Activity $activity_comment The deleted activity comment.
		 * @param WP_REST_Response     $response         The response data.
		 * @param WP_REST_Request      $request          The request sent to the API.
		 */
		do_action( 'bp_rest_activity_comment_delete_item', $activity_comment, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete an activity comment.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete this activity comment.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$activity_comment = $this->get_activity_comment_object( $request );

			if ( empty( $activity_comment->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity comment ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( $activity_comment->item_id !== (int) $request['id'] ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_activity_user_can_delete( $activity_comment ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the activity comment `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_comment_delete_item_permissions_check', $retval, $request );
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

	/**
	 * Get activity comment object.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request|array $request  Full details about the request.
	 *
	 * @return BP_Activity_Activity|string An activity comment object.
	 */
	public function get_activity_comment_object( $request ) {
		$activity_comment_id = ! empty( $request['comment_id'] ) ? (int) $request['comment_id'] : 0;
		$activity_comment    = new BP_Activity_Activity( $activity_comment_id );

		if ( ! empty( $activity_comment->id ) && ! empty( $request['display_comments'] ) ) {
			$activity_comment->comments = BP_Activity_Activity::append_comments( array( $activity_comment ) );
		}

		return $activity_comment;
	}

	public function validate_activity_comment_request( $request ) {
		$is_validate = true;

		if ( true === $this->activity_endpoint->bp_rest_activity_content_validate( $request ) ) {
			$is_validate = new WP_Error(
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
					$is_validate = new WP_Error(
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
					$is_validate = new WP_Error(
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
					$is_validate = new WP_Error(
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
					$is_validate = new WP_Error(
						'bp_rest_bp_activity_gif',
						__( 'You don\'t have access to send the gif.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}
			}
		}

		return $is_validate;
	}

	/**
	 * Can this user see the activity comment?
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean
	 * @since 0.1.0
	 */
	protected function can_see( $request ) {
		$activity_comment = $this->get_activity_comment_object( $request );

		return ( ! empty( $activity_comment ) && bp_activity_user_can_read( $activity_comment, bp_loggedin_user_id() ) );
	}
}
