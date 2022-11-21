<?php
/**
 * BP REST: BP_REST_Reply_Actions_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reply Actions endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Reply_Actions_Endpoint extends BP_REST_Reply_Endpoint {

	/**
	 * BP_REST_Forums_Endpoint Instance.
	 *
	 * @var BP_REST_Forums_Endpoint
	 */
	protected $forum_endpoint;

	/**
	 * BP_REST_Topics_Endpoint Instance.
	 *
	 * @var BP_REST_Topics_Endpoint
	 */
	protected $topic_endpoint;

	/**
	 * Rest_BBP_Walker_Reply Instance.
	 *
	 * @var Rest_BBP_Walker_Reply
	 */
	protected $bbb_walker_reply;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace        = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base        = 'reply';
		$this->forum_endpoint   = new BP_REST_Forums_Endpoint();
		$this->topic_endpoint   = new BP_REST_Topics_Endpoint();
		$this->bbb_walker_reply = new Rest_BBP_Walker_Reply();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/action/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'     => array(
						'description' => __( 'A unique numeric ID for the reply.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'action' => array(
						'description'       => __( 'Action name to perform on the reply.', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'enum'              => array(
							'spam',
							'trash',
						),
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'value'  => array(
						'description'       => __( 'Value for the action on reply.', 'buddyboss' ),
						'type'              => 'boolean',
						'sanitize_callback' => 'rest_sanitize_boolean',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'action_items' ),
					'permission_callback' => array( $this, 'action_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/move/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'                      => array(
						'description' => __( 'A unique numeric ID for the reply.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'move_option'             => array(
						'description'       => __( 'Options for Move the reply.', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'enum'              => array(
							'topic',
							'existing',
						),
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'destination_topic_id'    => array(
						'description'       => __( 'Destination Topic ID.', 'buddyboss' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'destination_topic_title' => array(
						'description'       => __( 'New Topic Title.', 'buddyboss' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'move_item' ),
					'permission_callback' => array( $this, 'move_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Actions on Reply.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/reply/action/:id Reply Actions
	 * @apiName        ActionBBPReply
	 * @apiGroup       Forum Replies
	 * @apiDescription Actions on Reply
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the reply.
	 * @apiParam {String=spam,trash} action Action name to perform on the reply.
	 * @apiParam {Boolean} value Value for the action on reply.
	 */
	public function action_items( $request ) {

		$action   = $request->get_param( 'action' );
		$value    = $request->get_param( 'value' );
		$reply_id = $request->get_param( 'id' );
		$retval   = '';

		switch ( $action ) {
			case 'spam':
				$retval = $this->rest_update_reply_spam( $reply_id, $value );
				break;
			case 'trash':
				$retval = $this->rest_update_reply_trash( $reply_id, $value );
				break;
		}

		if ( is_wp_error( $retval ) ) {
			return $retval;
		}

		$object = new WP_REST_Request();
		$object->set_param( 'id', $reply_id );
		$object->set_param( 'context', 'view' );

		return $this->get_item( $object );
	}

	/**
	 * Check if a given request has access to list replies.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function action_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform the action on the reply.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = $this->get_item_permissions_check( $request );
		}

		/**
		 * Filter the reply `action_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_action_item_permissions_check', $retval, $request );
	}

	/**
	 * Move a Reply.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/reply/move/:id Move Reply
	 * @apiName        MoveBBPReply
	 * @apiGroup       Forum Replies
	 * @apiDescription Move a Reply
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the reply.
	 * @apiParam {String=topic,existing} move_option Options for Move the reply.
	 * @apiParam {Number} [destination_topic_id] Destination Topic ID.
	 * @apiParam {String} [destination_topic_title] New Topic Title.
	 */
	public function move_item( $request ) {

		/** Move Reply */
		if ( empty( $request['id'] ) ) {
			return new WP_Error(
				'bp_rest_bbp_move_reply_reply_id',
				__( 'A reply ID is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			$move_reply_id = (int) $request['id'];
		}

		$move_reply = bbp_get_reply( $move_reply_id );

		// Reply exists.
		if ( empty( $move_reply ) ) {
			return new WP_Error(
				'bp_rest_bbp_mover_reply_r_not_found',
				__( 'The reply you want to move was not found.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic to Move From */
		// Get the current topic a reply is in.
		$source_topic = bbp_get_topic( $move_reply->post_parent );

		// No topic.
		if ( empty( $source_topic ) ) {
			return new WP_Error(
				'bp_rest_bbp_move_reply_source_not_found',
				__( 'The topic you want to move from was not found.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		// Use cannot edit topic.
		if ( ! current_user_can( 'edit_topic', $source_topic->ID ) ) {
			return new WP_Error(
				'bp_rest_bbp_move_reply_source_permission',
				__( 'You do not have permission to edit the source topic.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		// How to move.
		if ( ! empty( $request['move_option'] ) ) {
			$move_option = (string) trim( $request['move_option'] );
		}

		// Invalid move option.
		if ( empty( $move_option ) || ! in_array( $move_option, array( 'existing', 'topic' ), true ) ) {
			return new WP_Error(
				'bp_rest_bbp_move_reply_option',
				__( 'You need to choose a valid move option.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);

			// Valid move option.
		} else {

			// What kind of move.
			switch ( $move_option ) {

				// Into an existing topic.
				case 'existing':
					// Get destination topic id.
					if ( empty( $request['destination_topic_id'] ) ) {
						return new WP_Error(
							'bp_rest_bbp_move_reply_destination_id',
							__( 'A topic ID is required.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					} else {
						$destination_topic_id = (int) $request['destination_topic_id'];
					}

					// Get the destination topic.
					$destination_topic = bbp_get_topic( $destination_topic_id );

					// No destination topic.
					if ( empty( $destination_topic ) ) {
						return new WP_Error(
							'bp_rest_bbp_move_reply_destination_not_found',
							__( 'The topic you want to move to was not found.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}

					// User cannot edit the destination topic.
					if ( ! current_user_can( 'edit_topic', $destination_topic->ID ) ) {
						return new WP_Error(
							'bp_rest_bbp_move_reply_destination_permission',
							__( 'You do not have permission to edit the destination topic.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}

					// Bump the reply position.
					$reply_position = bbp_get_topic_reply_count( $destination_topic->ID, true ) + 1;

					// Update the reply.
					wp_update_post(
						array(
							'ID'          => $move_reply->ID,
							'post_title'  => '',
							'post_name'   => false, // will be automatically generated.
							'post_parent' => $destination_topic->ID,
							'menu_order'  => $reply_position,
							'guid'        => '',
						)
					);

					// Adjust reply meta values.
					bbp_update_reply_topic_id( $move_reply->ID, $destination_topic->ID );
					bbp_update_reply_forum_id( $move_reply->ID, bbp_get_topic_forum_id( $destination_topic->ID ) );

					break;

				// Move reply to a new topic.
				case 'topic':
				default:
					// User needs to be able to publish topics.
					if ( current_user_can( 'publish_topics' ) ) {

						// Use the new title that was passed.
						if ( ! empty( $request['destination_topic_title'] ) ) {
							$destination_topic_title = sanitize_text_field( $request['destination_topic_title'] );

							// Use the source topic title.
						} else {
							$destination_topic_title = $source_topic->post_title;
						}

						// Update the topic.
						$destination_topic_id = wp_update_post(
							array(
								'ID'          => $move_reply->ID,
								'post_title'  => $destination_topic_title,
								'post_name'   => false,
								'post_type'   => bbp_get_topic_post_type(),
								'post_parent' => $source_topic->post_parent,
								'guid'        => '',
							)
						);
						$destination_topic    = bbp_get_topic( $destination_topic_id );

						// Make sure the new topic knows its a topic.
						bbp_update_topic_topic_id( $move_reply->ID );

						// Shouldn't happen.
						if (
							false === $destination_topic_id
							|| is_wp_error( $destination_topic_id )
							|| empty( $destination_topic )
						) {
							return new WP_Error(
								'bp_rest_bbp_move_reply_destination_reply',
								__( 'There was a problem converting the reply into the topic. Please try again.', 'buddyboss' ),
								array(
									'status' => 400,
								)
							);
						}

						// User cannot publish posts.
					} else {
						return new WP_Error(
							'bp_rest_bbp_move_reply_destination_permission',
							__( 'You do not have permission to create new topics. The reply could not be converted into a topic.', 'buddyboss' ),
							array(
								'status' => rest_authorization_required_code(),
							)
						);
					}

					break;
			}
		}

		/** No Errors - Clean Up */
		// Update counts, etc...
		do_action( 'bbp_pre_move_reply', $move_reply->ID, $source_topic->ID, $destination_topic->ID );

		/** Date Check */
		// Check if the destination topic is older than the move reply.
		if ( strtotime( $move_reply->post_date ) < strtotime( $destination_topic->post_date ) ) {

			// Set destination topic post_date to 1 second before from reply.
			$destination_post_date = gmdate( 'Y-m-d H:i:s', strtotime( $move_reply->post_date ) - 1 );

			// Update destination topic.
			wp_update_post(
				array(
					'ID'            => $destination_topic_id,
					'post_date'     => $destination_post_date,
					'post_date_gmt' => get_gmt_from_date( $destination_post_date ),
				)
			);
		}

		// Set the last reply ID and freshness to the move_reply.
		$last_reply_id = $move_reply->ID;
		$freshness     = $move_reply->post_date;

		// Get the reply to.
		$parent = bbp_get_reply_to( $move_reply->ID );

		// Fix orphaned children.
		$children = get_posts(
			array(
				'post_type'  => bbp_get_reply_post_type(),
				'meta_key'   => '_bbp_reply_to', // phpcs:ignore
				'meta_type'  => 'NUMERIC',
				'meta_value' => $move_reply->ID, // phpcs:ignore
			)
		);

		if ( ! empty( $children ) ) {
			foreach ( $children as $child ) {
				bbp_update_reply_to( $child->ID, $parent );
			}
		}

		// Remove reply_to from moved reply.
		delete_post_meta( $move_reply->ID, '_bbp_reply_to' );

		// It is a new topic and we need to set some default metas to make.
		// the topic display in bbp_has_topics() list.
		if ( 'topic' === $move_option ) {
			bbp_update_topic_last_reply_id( $destination_topic->ID, $last_reply_id );
			bbp_update_topic_last_active_id( $destination_topic->ID, $last_reply_id );
			bbp_update_topic_last_active_time( $destination_topic->ID, $freshness );

			// Otherwise update the existing destination topic.
		} else {
			bbp_update_topic_last_reply_id( $destination_topic->ID );
			bbp_update_topic_last_active_id( $destination_topic->ID );
			bbp_update_topic_last_active_time( $destination_topic->ID );
		}

		// Update source topic ID last active.
		bbp_update_topic_last_reply_id( $source_topic->ID );
		bbp_update_topic_last_active_id( $source_topic->ID );
		bbp_update_topic_last_active_time( $source_topic->ID );

		/** Successful Move */
		// Update counts, etc...
		do_action( 'bbp_post_move_reply', $move_reply->ID, $source_topic->ID, $destination_topic->ID );

		$object = new WP_REST_Request();
		$object->set_param( 'id', $destination_topic->ID );
		$object->set_param( 'context', 'view' );

		return $this->topic_endpoint->get_item( $object );
	}

	/**
	 * Check if a given request has access to move reply.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function move_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform the action on the reply.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = $this->get_item_permissions_check( $request );
		}

		/**
		 * Filter the reply `action_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_action_item_permissions_check', $retval, $request );
	}

	/**
	 * Update Reply to be in spam.
	 *
	 * @param int     $reply_id Reply ID.
	 * @param boolean $value    Action value.
	 *
	 * @return bool|mixed
	 */
	protected function rest_update_reply_spam( $reply_id, $value ) {

		$status   = true;
		$is_spam  = bbp_is_reply_spam( $reply_id );
		$topic_id = bbp_get_reply_topic_id( $reply_id );

		// Subscribed and unsubscribing.
		if ( true === $is_spam && empty( $value ) ) {
			$status = bbp_unspam_reply( $reply_id );

			// Not subscribed and subscribing.
		} elseif ( false === $is_spam && ! empty( $value ) ) {
			$status = bbp_spam_reply( $reply_id );
		}

		if ( false !== $status && ! is_wp_error( $status ) && function_exists( 'bbp_update_total_parent_reply' ) ) {
			// Update total parent reply count when any parent trashed.
			bbp_update_total_parent_reply( $reply_id, $topic_id, '', 'update' );
		}

		return $status;
	}

	/**
	 * Move reply in trash and untrash.
	 *
	 * @param int     $reply_id Reply ID.
	 * @param boolean $value    Action value.
	 *
	 * @return bool|mixed
	 */
	protected function rest_update_reply_trash( $reply_id, $value ) {

		// What is the user doing here?
		if ( ! current_user_can( 'moderate', $reply_id ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to do this.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$post_status = get_post_status( $reply_id );
		$topic_id    = bbp_get_reply_topic_id( $reply_id );

		if (
			'trash' === $post_status
			&& empty( $value )
		) {
			$status = wp_untrash_post( $reply_id );
		} elseif (
			'trash' !== $post_status
			&& ! empty( $value )
		) {
			$status = wp_trash_post( $reply_id );
		}

		if ( false !== $status && ! is_wp_error( $status ) && function_exists( 'bbp_update_total_parent_reply' ) ) {
			// Update total parent reply count when any parent trashed.
			bbp_update_total_parent_reply( $reply_id, $topic_id, '', 'update' );
		}

		return ( ! empty( $status ) && ! is_wp_error( $status ) ? true : $status );
	}
}
