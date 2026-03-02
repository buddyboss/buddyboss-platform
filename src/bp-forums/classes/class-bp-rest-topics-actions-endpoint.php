<?php
/**
 * BP REST: BP_REST_Topics_Actions_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Topics Actions endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Topics_Actions_Endpoint extends BP_REST_Topics_Endpoint {

	/**
	 * BP_REST_Groups_Endpoint Instance.
	 *
	 * @var BP_REST_Forums_Endpoint
	 */
	protected $forum_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace      = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base      = 'topics';
		$this->forum_endpoint = new BP_REST_Forums_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/merge/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'             => array(
						'description' => __( 'A unique numeric ID for the topic.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'destination_id' => array(
						'description' => __( 'A unique numeric ID for the destination topic.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'subscribers'    => array(
						'description' => __( 'Whether to migrate subscriptions or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'default'     => true,
					),
					'favorites'      => array(
						'description' => __( 'Whether to migrate favorites or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'default'     => true,
					),
					'tags'           => array(
						'description' => __( 'Whether to migrate tags or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'default'     => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'merge_item' ),
					'permission_callback' => array( $this, 'merge_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/split/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'                    => array(
						'description' => __( 'A unique numeric ID for the topic.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'reply_id'              => array(
						'description' => __( 'A unique numeric ID for the topic\'s reply.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'split_option'          => array(
						'description' => __( 'Choose a valid split option.', 'buddyboss' ),
						'type'        => 'string',
						'required'    => true,
						'enum'        => array( 'reply', 'existing' ),
					),
					'new_destination_title' => array(
						'description'       => __( 'New Topic title for the split with option reply.', 'buddyboss' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'destination_id'        => array(
						'description' => __( 'A unique numeric ID for the destination topic.', 'buddyboss' ),
						'type'        => 'integer',
					),
					'subscribers'           => array(
						'description' => __( 'Whether to migrate subscriptions or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'default'     => true,
					),
					'favorites'             => array(
						'description' => __( 'Whether to migrate favorites or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'default'     => true,
					),
					'tags'                  => array(
						'description' => __( 'Whether to migrate tags or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'default'     => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'split_item' ),
					'permission_callback' => array( $this, 'split_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/action/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'     => array(
						'description' => __( 'A unique numeric ID for the topic.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'action' => array(
						'description'       => __( 'Action name to perform on the topic', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'enum'              => array(
							'favorite',
							'subscribe',
							'close',
							'sticky',
							'super_sticky',
							'spam',
							'trash',
						),
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'value'  => array(
						'description'       => __( 'Value for the action on topic.', 'buddyboss' ),
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
			'/' . $this->rest_base . '/dropdown/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'       => array(
						'description' => __( 'A unique numeric ID for the topic.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'page'     => array(
						'required'          => false,
						'default'           => 1,
						'description'       => __( 'Current page of the collection.', 'buddyboss' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'per_page' => array(
						'required'          => false,
						'default'           => 10,
						'description'       => __( 'Maximum number of items to be returned in result set.', 'buddyboss' ),
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'maximum'           => 9999999,
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'dropdown_items' ),
					'permission_callback' => array( $this, 'dropdown_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_dropdown_item_schema' ),
			)
		);
	}

	/**
	 * Merge Topic
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/topics/merge/:id Merge Topic
	 * @apiName        MergeBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Merge Topic
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 * @apiParam {Number} destination_id A unique numeric ID for the destination topic.
	 * @apiParam {Boolean} [subscribers=true] Whether to migrate subscriptions or not.
	 * @apiParam {Boolean} [favorites=true] Whether to migrate favorites or not.
	 * @apiParam {Boolean} [tags=true] Whether to migrate tags or not.
	 */
	public function merge_item( $request ) {

		// Define local variable(s).
		$source_topic_id      = 0;
		$destination_topic_id = 0;
		$source_topic         = 0;
		$destination_topic    = 0;
		$subscribers          = array();
		$favoriters           = array();
		$replies              = array();

		// Topic id.
		if ( empty( $request['id'] ) ) {
			return new WP_Error(
				'bp_rest_topic_invalid_id',
				__( 'Invalid topic ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		} else {
			$source_topic_id = (int) $request['id'];
		}

		// Source topic not found.
		$source_topic = bbp_get_topic( $source_topic_id );
		if ( empty( $source_topic ) ) {
			return new WP_Error(
				'bp_rest_bbp_merge_topic_source_not_found',
				__( 'The topic you want to merge was not found.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Cannot edit source topic.
		if ( ! current_user_can( 'edit_topic', $source_topic->ID ) ) {
			return new WP_Error(
				'bp_rest_bbp_merge_topic_source_permission',
				__( 'Sorry, You do not have permission to edit the source topic.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/** Destination Topic */
		// Topic id.
		if ( empty( $request['destination_id'] ) ) {
			return new WP_Error(
				'bp_rest_bbp_merge_topic_destination_id',
				__( 'Sorry, Destination discussion ID not found.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		} else {
			$destination_topic_id = (int) $request['destination_id'];
		}

		// Destination topic not found.
		$destination_topic = bbp_get_topic( $destination_topic_id );
		if ( empty( $destination_topic ) ) {
			return new WP_Error(
				'bp_rest_bbp_merge_topic_destination_not_found',
				__( 'Sorry, The discussion you want to merge to was not found.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Cannot edit destination topic.
		if ( ! current_user_can( 'edit_topic', $destination_topic->ID ) ) {
			return new WP_Error(
				'bp_rest_bbp_merge_topic_destination_permission',
				__( 'Sorry, You do not have the permissions to edit the destination discussion.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/** No Errors */
		// Update counts, etc...
		do_action( 'bbp_merge_topic', $destination_topic->ID, $source_topic->ID );

		/** Date Check */
		// Check if the destination topic is older than the source topic.
		if ( strtotime( $source_topic->post_date ) < strtotime( $destination_topic->post_date ) ) {

			// Set destination topic post_date to 1 second before source topic.
			$destination_post_date = gmdate( 'Y-m-d H:i:s', strtotime( $source_topic->post_date ) - 1 );

			// Update destination topic.
			wp_update_post(
				array(
					'ID'            => $destination_topic_id,
					'post_date'     => $destination_post_date,
					'post_date_gmt' => get_gmt_from_date( $destination_post_date ),
				)
			);
		}

		/** Subscriptions */
		// Get subscribers from source topic.
		$subscribers = bbp_get_topic_subscribers( $source_topic->ID );

		// Remove the topic from everybody's subscriptions.
		if ( ! empty( $subscribers ) ) {

			// Loop through each user.
			foreach ( (array) $subscribers as $subscriber ) {

				// Shift the subscriber if told to.
				if ( ! empty( $request['subscribers'] ) && ( true === $request['subscribers'] ) && bb_is_enabled_subscription( 'topic' ) ) {
					bbp_add_user_subscription( $subscriber, $destination_topic->ID );
				}

				// Remove old subscription.
				bbp_remove_user_subscription( $subscriber, $source_topic->ID );
			}
		}

		/** Favorites */
		// Get favoriters from source topic.
		$favoriters = bbp_get_topic_favoriters( $source_topic->ID );

		// Remove the topic from everybody's favorites.
		if ( ! empty( $favoriters ) ) {

			// Loop through each user.
			foreach ( (array) $favoriters as $favoriter ) {

				// Shift the favoriter if told to.
				if ( ! empty( $request['favorites'] ) && true === $request['favorites'] ) {
					bbp_add_user_favorite( $favoriter, $destination_topic->ID );
				}

				// Remove old favorite.
				bbp_remove_user_favorite( $favoriter, $source_topic->ID );
			}
		}

		/** Tags */
		// Get the source topic tags.
		$source_topic_tags = wp_get_post_terms( $source_topic->ID, bbp_get_topic_tag_tax_id(), array( 'fields' => 'names' ) );

		// Tags to possibly merge.
		if ( ! empty( $source_topic_tags ) && ! is_wp_error( $source_topic_tags ) ) {

			// Shift the tags if told to.
			if ( ! empty( $request['tags'] ) && ( true === $request['tags'] ) ) {
				wp_set_post_terms( $destination_topic->ID, $source_topic_tags, bbp_get_topic_tag_tax_id(), true );
			}

			// Delete the tags from the source topic.
			wp_delete_object_term_relationships( $source_topic->ID, bbp_get_topic_tag_tax_id() );
		}

		/** Source Topic */
		// Status.
		bbp_open_topic( $source_topic->ID );

		// Sticky.
		bbp_unstick_topic( $source_topic->ID );

		// Delete source topic's last & count meta data.
		delete_post_meta( $source_topic->ID, '_bbp_last_reply_id' );
		delete_post_meta( $source_topic->ID, '_bbp_last_active_id' );
		delete_post_meta( $source_topic->ID, '_bbp_last_active_time' );
		delete_post_meta( $source_topic->ID, '_bbp_voice_count' );
		delete_post_meta( $source_topic->ID, '_bbp_reply_count' );
		delete_post_meta( $source_topic->ID, '_bbp_reply_count_hidden' );
		delete_post_meta( $source_topic->ID, '_bbp_parent_reply_count' );

		// Delete source topics user relationships.
		delete_post_meta( $source_topic->ID, '_bbp_favorite' );
		delete_post_meta( $source_topic->ID, '_bbp_subscription' );

		$parent_replies = 0;

		// Get the replies of the source topic.
		$replies = (array) get_posts(
			array(
				'post_parent'            => $source_topic->ID,
				'post_type'              => bbp_get_reply_post_type(),
				'posts_per_page'         => - 1,
				'order'                  => 'ASC',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		// Prepend the source topic to its replies array for processing.
		array_unshift( $replies, $source_topic );

		if ( ! empty( $replies ) ) {

			/** Merge Replies */
			// Change the post_parent of each reply to the destination topic id.
			foreach ( $replies as $reply ) {

				// Update the reply.
				wp_update_post(
					array(
						'ID'          => $reply->ID,
						'post_title'  => sprintf(
							/* translators: Topic Title. */
							__( 'Reply To: %s', 'buddyboss' ),
							$destination_topic->post_title
						),
						'post_name'   => false,
						'post_type'   => bbp_get_reply_post_type(),
						'post_parent' => $destination_topic->ID,
						'guid'        => '',
					)
				);

				// Adjust reply meta values.
				bbp_update_reply_topic_id( $reply->ID, $destination_topic->ID );
				bbp_update_reply_forum_id( $reply->ID, bbp_get_topic_forum_id( $destination_topic->ID ) );

				// Update the reply position.
				bbp_update_reply_position( $reply->ID );

				// Do additional actions per merged reply.
				do_action( 'bbp_merged_topic_reply', $reply->ID, $destination_topic->ID );

				if ( ! empty( $destination_topic->ID ) && empty( get_post_meta( $reply->ID, '_bbp_reply_to', true ) ) ) {
					$parent_replies++;
				}
			}
		}

		/** Successful Merge */
		// Update topic's last meta data.
		bbp_update_topic_last_reply_id( $destination_topic->ID );
		bbp_update_topic_last_active_id( $destination_topic->ID );
		bbp_update_topic_last_active_time( $destination_topic->ID );

		// Update parent reply count with destination topic_id.
		if ( ! empty( $destination_topic->ID ) && 0 !== $parent_replies ) {
			$parent_reply_count = get_post_meta( $destination_topic->ID, '_bbp_parent_reply_count', true );
			update_post_meta( $destination_topic->ID, '_bbp_parent_reply_count', (int) $parent_reply_count + $parent_replies );
		}

		// Send the post parent of the source topic as it has been shifted.
		// (possibly to a new forum) so we need to update the counts of the.
		// old forum as well as the new one.
		do_action( 'bbp_merged_topic', $destination_topic->ID, $source_topic->ID, $source_topic->post_parent );

		/**
		 * Fires after a list of topic is merged via the REST API.
		 *
		 * @param array           $destination_topic Destination topic.
		 * @param array           $source_topic      Source topic.
		 * @param WP_REST_Request $request           The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_topic_get_item', $destination_topic, $source_topic, $request );

		$object = new WP_REST_Request();
		$object->set_param( 'id', $destination_topic->ID );
		$object->set_param( 'context', 'view' );

		return $this->get_item( $object );
	}

	/**
	 * Check if a given request has access to merge a topic.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function merge_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to merge this topic.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( is_user_logged_in() ) {
			$retval = $this->get_item_permissions_check( $request );

			if ( true === $retval && ! current_user_can( 'edit_topic', $request->get_param( 'id' ) ) ) {
				$retval = $error;
			}
		}

		/**
		 * Filter the topic `merge_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_merge_item_permissions_check', $retval, $request );
	}

	/**
	 * Split Topic
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/topics/split/:id Split Topic
	 * @apiName        SplitBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Split Topic
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 * @apiParam {Number} reply_id A unique numeric ID for the topic's reply.
	 * @apiParam {String=reply,existing} split_option Choose a valid split option.
	 * @apiParam {String} [new_destination_title] New Topic title for the split with option reply.
	 * @apiParam {Number} [destination_id] A unique numeric ID for the destination topic.
	 * @apiParam {Boolean} [subscribers=true] Whether to migrate subscriptions or not.
	 * @apiParam {Boolean} [favorites=true] Whether to migrate favorites or not.
	 * @apiParam {Boolean} [tags=true] Whether to migrate tags or not.
	 */
	public function split_item( $request ) {

		global $wpdb;

		// Prevent debug notices.
		$split_option = false;

		/** Split Reply */

		if ( empty( $request['reply_id'] ) ) {
			return new WP_Error(
				'bp_rest_bbp_split_topic_reply_id',
				__( 'Reply ID to split the topic from not found!', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		} else {
			$from_reply_id = (int) $request['reply_id'];
		}

		$from_reply = bbp_get_reply( $from_reply_id );

		// Reply exists.
		if ( empty( $from_reply ) ) {
			return new WP_Error(
				'bp_rest_bbp_split_topic_r_not_found',
				__( 'The reply you want to split from was not found.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/** Topic to Split */
		// Get the topic being split.
		$source_topic = bbp_get_topic( $from_reply->post_parent );

		// No topic.
		if ( empty( $source_topic ) ) {
			return new WP_Error(
				'bp_rest_bbp_split_topic_source_not_found',
				__( 'The topic you want to split was not found.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Use cannot edit topic.
		if ( ! current_user_can( 'edit_topic', $source_topic->ID ) ) {
			return new WP_Error(
				'bp_rest_bbp_split_topic_source_permission',
				__( 'Sorry, You do not have the permissions to edit the source discussion.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		// How to Split.
		if ( ! empty( $request['split_option'] ) ) {
			$split_option = sanitize_key( $request['split_option'] );
		}

		if ( empty( $split_option ) || ! in_array( $split_option, array( 'existing', 'reply' ), true ) ) {
			return new WP_Error(
				'bp_rest_bbp_split_topic_option',
				__( 'Sorry, You need to choose a valid split option.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);

			// Valid Split Option.
		} else {
			// What kind of split.
			switch ( $split_option ) {

				// Into an existing topic.
				case 'existing':
					// Get destination topic id.
					if ( empty( $request['destination_id'] ) ) {
						return new WP_Error(
							'bp_rest_bbp_split_topic_destination_id',
							__( 'Sorry, Destination discussion ID not found!', 'buddyboss' ),
							array(
								'status' => 404,
							)
						);
					} else {
						$destination_topic_id = (int) $request['destination_id'];
					}

					// Get the destination topic.
					$destination_topic = bbp_get_topic( $destination_topic_id );

					// No destination topic.
					if ( empty( $destination_topic ) ) {
						return new WP_Error(
							'bp_rest_bbp_split_topic_destination_not_found',
							__( 'Sorry, The discussion you want to split to was not found!', 'buddyboss' ),
							array(
								'status' => 404,
							)
						);
					}

					// User cannot edit the destination topic.
					if ( ! current_user_can( 'edit_topic', $destination_topic->ID ) ) {
						return new WP_Error(
							'bp_rest_bbp_split_topic_destination_permission',
							__( 'Sorry, You do not have the permissions to edit the destination discussion!', 'buddyboss' ),
							array(
								'status' => rest_authorization_required_code(),
							)
						);
					}

					break;

				// Split at reply into a new topic.
				case 'reply':
				default:
					// User needs to be able to publish topics.
					if ( current_user_can( 'publish_topics' ) ) {

						// Use the new title that was passed.
						if ( ! empty( $request['new_destination_title'] ) ) {
							$destination_topic_title = esc_attr( wp_strip_all_tags( $request['new_destination_title'] ) );

							// Use the source topic title.
						} else {
							$destination_topic_title = $source_topic->post_title;
						}

						// Update the topic.
						$destination_topic_id = wp_update_post(
							array(
								'ID'          => $from_reply->ID,
								'post_title'  => $destination_topic_title,
								'post_name'   => false,
								'post_type'   => bbp_get_topic_post_type(),
								'post_parent' => $source_topic->post_parent,
								'menu_order'  => 0,
								'guid'        => '',
							)
						);

						$destination_topic = bbp_get_topic( $destination_topic_id );

						// Make sure the new topic knows its a topic.
						bbp_update_topic_topic_id( $from_reply->ID );

						// Shouldn't happen.
						if (
							false === $destination_topic_id
							|| is_wp_error( $destination_topic_id )
							|| empty( $destination_topic )
						) {
							return new WP_Error(
								'bp_rest_bbp_split_topic_destination_reply',
								__( 'Sorry, There was a problem converting the reply into the discussion. Please try again.', 'buddyboss' ),
								array(
									'status' => 404,
								)
							);
						}

						// User cannot publish posts.
					} else {
						return new WP_Error(
							'bp_rest_bbp_split_topic_destination_permission',
							__( 'Sorry, You do not have the permissions to create new topics. The reply could not be converted into a discussion.', 'buddyboss' ),
							array(
								'status' => rest_authorization_required_code(),
							)
						);
					}

					break;
			}
		}

		/** No Errors - Do the Spit */
		// Update counts, etc...
		do_action( 'bbp_pre_split_topic', $from_reply->ID, $source_topic->ID, $destination_topic->ID );

		/** Date Check */
		// Check if the destination topic is older than the from reply.
		if ( strtotime( $from_reply->post_date ) < strtotime( $destination_topic->post_date ) ) {

			// Set destination topic post_date to 1 second before from reply.
			$destination_post_date = gmdate( 'Y-m-d H:i:s', strtotime( $from_reply->post_date ) - 1 );

			// Update destination topic.
			wp_update_post(
				array(
					'ID'            => $destination_topic_id,
					'post_date'     => $destination_post_date,
					'post_date_gmt' => get_gmt_from_date( $destination_post_date ),
				)
			);
		}

		/** Subscriptions */
		// Copy the subscribers.
		if ( ! empty( $request['subscribers'] ) && true === $request['subscribers'] && bb_is_enabled_subscription( 'topic' ) ) {

			// Get the subscribers.
			$subscribers = bbp_get_topic_subscribers( $source_topic->ID );

			if ( ! empty( $subscribers ) ) {

				// Add subscribers to new topic.
				foreach ( (array) $subscribers as $subscriber ) {
					bbp_add_user_subscription( $subscriber, $destination_topic->ID );
				}
			}
		}

		/** Favorites */
		// Copy the favoriters if told to.
		if ( ! empty( $request['favorites'] ) && ( true === $request['favorites'] ) ) {

			// Get the favoriters.
			$favoriters = bbp_get_topic_favoriters( $source_topic->ID );

			if ( ! empty( $favoriters ) ) {

				// Add the favoriters to new topic.
				foreach ( (array) $favoriters as $favoriter ) {
					bbp_add_user_favorite( $favoriter, $destination_topic->ID );
				}
			}
		}

		/** Tags */
		// Copy the tags if told to.
		if ( ! empty( $request['tags'] ) && ( true === $request['tags'] ) ) {

			// Get the source topic tags.
			$source_topic_tags = wp_get_post_terms( $source_topic->ID, bbp_get_topic_tag_tax_id(), array( 'fields' => 'names' ) );

			if ( ! empty( $source_topic_tags ) ) {
				wp_set_post_terms( $destination_topic->ID, $source_topic_tags, bbp_get_topic_tag_tax_id(), true );
			}
		}

		/** Split Replies */
		// get_posts() is not used because it doesn't allow us to use '>='.
		// comparision without a filter.
		// phpcs:ignore
		$replies = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE {$wpdb->posts}.post_date >= %s AND {$wpdb->posts}.post_parent = %d AND {$wpdb->posts}.post_type = %s ORDER BY {$wpdb->posts}.post_date ASC", $from_reply->post_date, $source_topic->ID, bbp_get_reply_post_type() ) );

		$source_parent_replies      = 0;
		$destination_parent_replies = 0;

		// Make sure there are replies to loop through.
		if ( ! empty( $replies ) && ! is_wp_error( $replies ) ) {

			// Save reply ids.
			$reply_ids = array();

			// Change the post_parent of each reply to the destination topic id.
			foreach ( $replies as $reply ) {

				if ( ! empty( $source_topic->ID ) && empty( get_post_meta( $reply->ID, '_bbp_reply_to', true ) ) ) {
					$source_parent_replies++;
				}

				// Update the reply.
				wp_update_post(
					array(
						'ID'          => $reply->ID,
						'post_title'  => sprintf(
							/* translators: Topic Title. */
							__( 'Reply To: %s', 'buddyboss' ),
							$destination_topic->post_title
						),
						'post_name'   => false, // will be automatically generated.
						'post_parent' => $destination_topic->ID,
						'guid'        => '',
					)
				);

				// Gather reply ids.
				$reply_ids[] = (int) $reply->ID;

				// Adjust reply meta values.
				bbp_update_reply_topic_id( $reply->ID, $destination_topic->ID );
				bbp_update_reply_forum_id( $reply->ID, bbp_get_topic_forum_id( $destination_topic->ID ) );

				// Adjust reply position.
				bbp_update_reply_position( $reply->ID );

				// Adjust reply to values.
				$reply_to = bbp_get_reply_to( $reply->ID );

				// Not a reply to a reply that moved over.
				if ( ! in_array( $reply_to, $reply_ids, true ) ) {
					bbp_update_reply_to( $reply->ID, 0 );
				}

				// New topic from reply can't be a reply to.
				if ( ( $from_reply->ID === $destination_topic->ID ) && ( $from_reply->ID === $reply_to ) ) {
					bbp_update_reply_to( $reply->ID, 0 );
				}

				// Do additional actions per split reply.
				do_action( 'bbp_split_topic_reply', $reply->ID, $destination_topic->ID );

				if ( ! empty( $destination_topic->ID ) && empty( get_post_meta( $reply->ID, '_bbp_reply_to', true ) ) ) {
					$destination_parent_replies++;
				}
			}

			// Update parent reply count with source topic_id.
			if ( ! empty( $source_topic->ID ) && 0 !== $source_parent_replies ) {
				$parent_reply_count = get_post_meta( $source_topic->ID, '_bbp_parent_reply_count', true );
				update_post_meta( $source_topic->ID, '_bbp_parent_reply_count', (int) $parent_reply_count - $source_parent_replies );
			}

			// Update parent reply count with destination topic_id.
			if ( ! empty( $destination_topic->ID ) && 0 !== $destination_parent_replies ) {
				$parent_reply_count = get_post_meta( $destination_topic->ID, '_bbp_parent_reply_count', true );
				update_post_meta( $destination_topic->ID, '_bbp_parent_reply_count', (int) $parent_reply_count + $destination_parent_replies );
			}

			// Remove reply to from new topic.
			if ( $from_reply->ID === $destination_topic->ID ) {
				delete_post_meta( $from_reply->ID, '_bbp_reply_to' );
			}

			// Set the last reply ID and freshness.
			$last_reply_id = $reply->ID;
			$freshness     = $reply->post_date;

			// Set the last reply ID and freshness to the from_reply.
		} else {
			$last_reply_id = $from_reply->ID;
			$freshness     = $from_reply->post_date;
		}

		// It is a new topic and we need to set some default metas to make.
		// the topic display in bbp_has_topics() list.
		if ( 'reply' === $split_option ) {
			bbp_update_topic_last_reply_id( $destination_topic->ID, $last_reply_id );
			bbp_update_topic_last_active_id( $destination_topic->ID, $last_reply_id );
			bbp_update_topic_last_active_time( $destination_topic->ID, $freshness );
		}

		// Update source topic ID last active.
		bbp_update_topic_last_reply_id( $source_topic->ID );
		bbp_update_topic_last_active_id( $source_topic->ID );
		bbp_update_topic_last_active_time( $source_topic->ID );

		/** Successful Split */
		// Update counts, etc...
		do_action( 'bbp_post_split_topic', $from_reply->ID, $source_topic->ID, $destination_topic->ID );

		/**
		 * Fires after a list of topic is split via the REST API.
		 *
		 * @param array           $from_reply        Reply ID to start split from.
		 * @param array           $source_topic      Source topic.
		 * @param array           $destination_topic Destination topic.
		 * @param WP_REST_Request $request           The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_split_get_item', $from_reply, $source_topic, $destination_topic, $request );

		$object = new WP_REST_Request();
		$object->set_param( 'id', $destination_topic->ID );
		$object->set_param( 'context', 'view' );

		return $this->get_item( $object );
	}

	/**
	 * Check if a given request has access to merge a topic.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function split_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to split a topic.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = $this->get_item_permissions_check( $request );

			if ( true === $retval && ! current_user_can( 'edit_topic', $request->get_param( 'id' ) ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to split this topic.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the topic `split_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_split_item_permissions_check', $retval, $request );
	}

	/**
	 * Actions on Topic
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * NOTICE: Since 2.2.2, forum subscriptions have been migrated to a
	 * new API for subscribing users to notifications: /wp-json/buddyboss/v1/subscriptions
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/topics/action/:id Topic Actions
	 * @apiName        ActionBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Actions on Topic
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 * @apiParam {String=favorite,subscribe,close,sticky,super_sticky,spam,trash} action Action name to perform on the topic.
	 * @apiParam {Boolean} value Value for the action on topic.
	 */
	public function action_items( $request ) {

		$action   = $request->get_param( 'action' );
		$value    = $request->get_param( 'value' );
		$topic_id = $request->get_param( 'id' );
		$user_id  = bbp_get_user_id( 0, true, true );
		$retval   = '';

		switch ( $action ) {
			case 'favorite':
				$retval = $this->rest_update_favorite( $topic_id, $value, $user_id );
				break;
			case 'subscribe':
				$retval = $this->rest_update_subscribe( $topic_id, $value, $user_id );
				break;
			case 'close':
				$retval = $this->rest_update_close( $topic_id, $value );
				break;
			case 'sticky':
			case 'super_sticky':
				$retval = $this->rest_update_sticky( $topic_id, $action, $value );
				break;
			case 'spam':
				$retval = $this->rest_update_spam( $topic_id, $value );
				break;
			case 'trash':
				$retval = $this->rest_update_trash( $topic_id, $value );
				break;
		}

		if ( is_wp_error( $retval ) ) {
			return $retval;
		}

		$object = new WP_REST_Request();
		$object->set_param( 'id', $topic_id );
		$object->set_param( 'context', 'view' );

		return $this->get_item( $object );
	}

	/**
	 * Check if a given request has access to perform an action on topic.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function action_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform the action on the topic.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = $this->get_item_permissions_check( $request );
		}

		/**
		 * Filter the topic `action_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_action_item_permissions_check', $retval, $request );
	}

	/**
	 * Topic's Dropdown
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/topics/dropdown/:id Topic Actions
	 * @apiName        DropdownBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Siblings of the topic.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 */
	public function dropdown_items( $request ) {

		$topic_id = $request->get_param( 'id' );
		$parent   = bbp_get_topic_forum_id( $topic_id );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		$topics_query = new WP_Query(
			array(
				'post_type'          => bbp_get_topic_post_type(),
				'post_status'        => 'publish',
				'post__not_in'       => array( $topic_id ),
				'post_parent'        => $parent,
				'posts_per_page'     => $per_page,
				'paged'              => $page,
				'orderby'            => 'menu_order title',
				'order'              => 'ASC',
				'disable_categories' => true,
			)
		);

		$topics = $topics_query->posts;

		if ( empty( $topics ) ) {
			$retval = new WP_Error(
				'bp_rest_no_other_topics',
				__( 'No discussions available', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		foreach ( $topics as $topic ) {
			$data[] = array(
				'id'    => $topic->ID,
				'title' => array(
					'raw'      => $topic->post_title,
					'rendered' => bbp_get_topic_title( $topic->ID ),
				),
			);
		}

		$response = rest_ensure_response( $data );
		$response = bp_rest_response_add_total_headers( $response, $topics_query->found_posts, $per_page );

		/**
		 * Fires after a list of topics is fetched via the REST API.
		 *
		 * @param array            $topics   Fetched Topics.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_topics_dropdown_items', $topics, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to view the siblings of the topic.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function dropdown_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform the action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$topic = bbp_get_topic( $request->get_param( 'id' ) );
			if ( empty( $topic ) ) {
				$retval = new WP_Error(
					'bp_rest_topic_invalid_id',
					__( 'Invalid topic ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the topic `dropdown_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_dropdown_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the forums schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_dropdown_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'topics_dropdown',
			'type'       => 'object',
			'properties' => array(
				'id'    => array(
					'description' => __( 'Unique identifier for the topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title' => array(
					'description' => __( 'The title of the topic.', 'buddyboss' ),
					'context'     => array( 'view', 'edit' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the title of the topic, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'The title of the topic, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);

		/**
		 * Filters the topic dropdown schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_topic_dropdown_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Update favourites for the topic.
	 *
	 * @param integer $topic_id Topic ID.
	 * @param boolean $value    Action value.
	 * @param integer $user_id  Current users ID.
	 *
	 * @return bool|WP_Error
	 */
	protected function rest_update_favorite( $topic_id, $value, $user_id ) {
		if ( ! bbp_is_favorites_active() ) {
			return new WP_Error(
				'bp_rest_bbp_topic_action_disabled',
				__( 'Favorites are no longer active.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Bail if user cannot add favorites for this user.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to do this.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$favorited = bbp_is_user_favorite( $user_id, $topic_id );

		$status = true;

		if ( true === $favorited && empty( $value ) ) {
			$status = bbp_remove_user_favorite( $user_id, $topic_id );

		} elseif ( false === $favorited && ! empty( $value ) ) {
			$status = bbp_add_user_favorite( $user_id, $topic_id );
		}

		return $status;
	}

	/**
	 * Update status for the topic.
	 *
	 * @param integer $topic_id Topic ID.
	 * @param boolean $value    Action value.
	 *
	 * @return bool|WP_Error
	 */
	protected function rest_update_close( $topic_id, $value ) {
		// What is the user doing here?
		if ( ! current_user_can( 'moderate', $topic_id ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to do this.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$is_open = bbp_is_topic_open( $topic_id );
		$status  = true;

		if ( true === $is_open && ! empty( $value ) ) {
			$status = bbp_close_topic( $topic_id );

		} elseif ( false === $is_open && empty( $value ) ) {
			$status = bbp_open_topic( $topic_id );
		}

		return $status;
	}

	/**
	 * Update Subscription for the topic.
	 *
	 * @param integer $topic_id Topic ID.
	 * @param boolean $value    Action value.
	 * @param integer $user_id  Current users ID.
	 *
	 * @return bool|WP_Error
	 */
	protected function rest_update_subscribe( $topic_id, $value, $user_id ) {
		if ( ! bb_is_enabled_subscription( 'topic' ) ) {
			return new WP_Error(
				'bp_rest_bbp_topic_action_disabled',
				__( 'Subscriptions are no longer active.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// Bail if user cannot add subscription for this user.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to do this.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$status     = true;
		$subscribed = bbp_is_user_subscribed( $user_id, $topic_id );

		// Subscribed and unsubscribing.
		if ( true === $subscribed && empty( $value ) ) {
			$status = bbp_remove_user_subscription( $user_id, $topic_id );

			// Not subscribed and subscribing.
		} elseif ( false === $subscribed && ! empty( $value ) ) {
			$status = bbp_add_user_subscription( $user_id, $topic_id );
		}

		return $status;
	}

	/**
	 * Update sticky things for the topic.
	 *
	 * @param integer $topic_id Topic ID.
	 * @param string  $action   Action name to update.
	 * @param boolean $value    Action value.
	 *
	 * @return bool|WP_Error
	 */
	protected function rest_update_sticky( $topic_id, $action, $value ) {
		// What is the user doing here?
		if ( ! current_user_can( 'moderate', $topic_id ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to do this.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$is_super = ( 'super_sticky' === $action ) ? true : false;

		$is_sticky = bbp_is_topic_sticky( $topic_id );
		$status    = true;

		if ( true === $is_sticky && empty( $value ) ) {
			$status = bbp_unstick_topic( $topic_id );

		} elseif ( false === $is_sticky && ! empty( $value ) ) {
			$status = bbp_stick_topic( $topic_id, $is_super );
		}

		return $status;
	}

	/**
	 * Make topics as spam or not.
	 *
	 * @param integer $topic_id Topic ID.
	 * @param boolean $value    Action value.
	 *
	 * @return bool|WP_Error
	 */
	protected function rest_update_spam( $topic_id, $value ) {
		// What is the user doing here?
		if ( ! current_user_can( 'moderate', $topic_id ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to do this.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$is_spam = bbp_is_topic_spam( $topic_id );
		$status  = true;

		if ( true === $is_spam && empty( $value ) ) {
			$status = bbp_unspam_topic( $topic_id );

		} elseif ( false === $is_spam && ! empty( $value ) ) {
			$status = bbp_spam_topic( $topic_id );
		}

		return $status;
	}

	/**
	 * Move topic into trash or untrash.
	 *
	 * @param integer $topic_id Topic ID.
	 * @param boolean $value    Action value.
	 *
	 * @return bool|WP_Error
	 */
	protected function rest_update_trash( $topic_id, $value ) {
		// What is the user doing here?
		if ( ! current_user_can( 'moderate', $topic_id ) ) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'You do not have permission to do this.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$post_status = get_post_status( $topic_id );

		if ( 'trash' === $post_status && empty( $value ) ) {
			$status = wp_untrash_post( $topic_id );
		} elseif ( 'trash' !== $post_status && ! empty( $value ) ) {
			$status = wp_trash_post( $topic_id );
		}

		return ( ! empty( $status ) && ! is_wp_error( $status ) ? true : $status );
	}
}
