<?php
/**
 * BP REST: BP_REST_Activity_Endpoint class
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
class BP_REST_Activity_Endpoint extends WP_REST_Controller {

	/**
	 * User favorites.
	 *
	 * @since 0.1.0
	 *
	 * @var array|null
	 */
	protected $user_favorites = null;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = buddypress()->activity->id;
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

		$activity_endpoint = '/' . $this->rest_base . '/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$activity_endpoint,
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the activity.', 'buddyboss' ),
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

		// Register the favorite route.
		register_rest_route(
			$this->namespace,
			$activity_endpoint . '/favorite',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the activity.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_favorite' ),
					'permission_callback' => array( $this, 'update_favorite_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve activities.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/activity Get Activities
	 * @apiName        GetBBActivities
	 * @apiGroup       Activity
	 * @apiDescription Retrieve activities
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {Array=asc,desc} [order=desc] Ensure result set includes specific IDs.
	 * @apiParam {String} [after] Limit result set to items published after a given ISO8601 compliant date.
	 * @apiParam {Number} [user_id] Limit result set to items created by a specific user (ID).
	 * @apiParam {String=ham_only,spam_only,all} [status=ham_only] Limit result set to items with a specific status.
	 * @apiParam {String=just-me,friends,groups,favorites,mentions,following} [scope] Limit result set to items with a specific scope.
	 * @apiParam {Number} [group_id] Limit result set to items created by a specific group.
	 * @apiParam {Number} [site_id] Limit result set to items created by a specific site.
	 * @apiParam {Number} [primary_id] Limit result set to items with a specific prime association ID.
	 * @apiParam {Number} [secondary_id] Limit result set to items with a specific secondary association ID.
	 * @apiParam {String} [component] Limit result set to items with a specific active component.
	 * @apiParam {String} [type] Limit result set to items with a specific activity type.
	 * @apiParam {String=stream,threaded,false} [display_comments=false] No comments by default, stream for within stream display, threaded for below each activity item.
	 * @apiParam {Array=public,loggedin,onlyme,friends,media} [privacy] Privacy of the activity.
	 */
	public function get_items( $request ) {
		global $bp;

		$args = array(
			'exclude'           => $request['exclude'],
			'in'                => $request['include'],
			'page'              => $request['page'],
			'per_page'          => $request['per_page'],
			'search_terms'      => $request['search'],
			'sort'              => strtoupper( $request['order'] ),
			'order_by'          => $request['orderby'],
			'spam'              => $request['status'],
			'display_comments'  => $request['display_comments'],
			'site_id'           => $request['site_id'],
			'group_id'          => $request['group_id'],
			'scope'             => $request['scope'],
			'privacy'           => ( ! empty( $request['privacy'] ) ? ( is_array( $request['privacy'] ) ? $request['privacy'] : (array) $request['privacy'] ) : '' ),
			'count_total'       => true,
			'fields'            => 'all',
			'show_hidden'       => false,
			'update_meta_cache' => true,
			'filter'            => array(),
		);

		if ( empty( $args['display_comments'] ) || 'false' === $args['display_comments'] ) {
			$args['display_comments'] = false;
		}

		if ( empty( $request['exclude'] ) ) {
			$args['exclude'] = false;
		}

		if ( empty( $request['include'] ) ) {
			$args['in'] = false;
		}

		if ( isset( $request['after'] ) ) {
			$args['since'] = $request['after'];
		}

		if ( isset( $request['user_id'] ) ) {
			$args['filter']['user_id'] = $request['user_id'];
			if ( ! empty( $request['user_id'] ) ) {
				$bp->displayed_user->id = (int) $request['user_id'];
			}
		}

		$item_id = 0;
		if ( ! empty( $args['group_id'] ) ) {
			$request['component']         = 'groups';
			$args['filter']['object']     = 'groups';
			$args['filter']['primary_id'] = $args['group_id'];
			$args['privacy']              = array( 'public' );

			$item_id = $args['group_id'];
		} elseif ( ! empty( $request['component'] ) && 'groups' === $request['component'] && ! empty( $request['primary_id'] ) ) {
			$args['privacy'] = array( 'public' );
		}

		if ( ! empty( $args['site_id'] ) ) {
			$args['filter']['object']     = 'blogs';
			$args['filter']['primary_id'] = $args['site_id'];

			$item_id = $args['site_id'];
		}

		if ( empty( $args['group_id'] ) && empty( $args['site_id'] ) ) {
			if ( isset( $request['component'] ) ) {
				$args['filter']['object'] = $request['component'];
			}

			if ( ! empty( $request['primary_id'] ) ) {
				$item_id                      = $request['primary_id'];
				$args['filter']['primary_id'] = $item_id;
			}
		}

		if ( empty( $request['scope'] ) ) {
			$args['scope'] = false;
		}

		if ( isset( $request['type'] ) ) {
			$args['filter']['action'] = $request['type'];
		}

		if ( ! empty( $request['secondary_id'] ) ) {
			$args['filter']['secondary_id'] = $request['secondary_id'];
		}

		if ( ! empty( $args['order_by'] ) && 'include' === $args['order_by'] ) {
			$args['order_by'] = 'in';
		}

		if ( $args['in'] ) {
			$args['count_total'] = false;
		}

		if ( $this->show_hidden( $request['component'], $item_id ) ) {
			$args['show_hidden'] = true;
		}

		$args['scope'] = $this->bp_rest_activity_default_scope(
			$args['scope'],
			( $request['user_id'] ? $request['user_id'] : 0 ),
			$args['group_id'],
			isset( $request['component'] ) ? $request['component'] : '',
			$request['primary_id']
		);

		if ( empty( $args['scope'] ) ) {
			$args['privacy'] = 'public';
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_activity_get_items_query_args', $args, $request );

		// Actually, query it.
		$activities = bp_activity_get( $args );

		$retval = array();
		foreach ( $activities['activities'] as $activity ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $activity, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $activities['total'], $args['per_page'] );

		/**
		 * Fires after a list of activities is fetched via the REST API.
		 *
		 * @param array            $activities Fetched activities.
		 * @param WP_REST_Response $response   The response data.
		 * @param WP_REST_Request  $request    The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_get_items', $activities, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to activity items.
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

		/**
		 * Filter the activity `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve an activity.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/activity/:id Get Activity
	 * @apiName        GetBBActivity
	 * @apiGroup       Activity
	 * @apiDescription Retrieve single activity
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 * @apiParam {String=stream,threaded,false} [display_comments=false] No comments by default, stream for within stream display, threaded for below each activity item.
	 */
	public function get_item( $request ) {
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

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $activity, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity is fetched via the REST API.
		 *
		 * @param BP_Activity_Activity $activity Fetched activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_get_item', $activity, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific activity.
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
				__( 'Sorry, you cannot view the activities.', 'buddyboss' ),
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
		return apply_filters( 'bp_rest_activity_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create an activity.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/activity Create activity
	 * @apiName        CreateBBActivity
	 * @apiGroup       Activity
	 * @apiDescription Create activity
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} primary_item_id The ID of some other object primarily associated with this one.
	 * @apiParam {Number} secondary_item_id The ID of some other object also associated with this one.
	 * @apiParam {Number} user_id The ID for the author of the activity.
	 * @apiParam {String} link The permalink to this activity on the site.
	 * @apiParam {String=settings,notifications,groups,forums,activity,media,messages,friends,invites,search,members,xprofile,blogs} component The active component the activity relates to.
	 * @apiParam {String=new_member,new_avatar,updated_profile,activity_update,created_group,joined_group,group_details_updated,bbp_topic_create,bbp_reply_create,activity_comment,friendship_accepted,friendship_created,new_blog_post,new_blog_comment} type The activity type of the activity.
	 * @apiParam {String} content Allowed HTML content for the activity.
	 * @apiParam {String} date The date the activity was published, in the site's timezone.
	 * @apiParam {Boolean=true,false} hidden Whether the activity object should be sitewide hidden or not.
	 * @apiParam {string=public,loggedin,onlyme,friends,media} [privacy] Privacy of the activity.
	 * @apiParam {Array} [bp_media_ids] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [media_gif] Save gif data into activity when Media component is enable. param(url,mp4)
	 */
	public function create_item( $request ) {
		$request->set_param( 'context', 'edit' );

		/**
		 * Map data into POST to work with link preview.
		 */
		$post_map = array(
			'link_url'         => 'link_url',
			'link_embed'       => 'link_embed',
			'link_title'       => 'link_title',
			'link_description' => 'link_description',
			'link_image'       => 'link_image',
		);

		if ( ! empty( $post_map ) ) {
			foreach ( $post_map as $key => $val ) {
				if ( isset( $request[ $val ] ) ) {
					$_POST[ $key ] = $request[ $val ];
				}
			}
		}

		$_POST[ 'action' ] = 'new-activity';

		$prepared_activity  = $this->prepare_item_for_database( $request );
		$request['content'] = isset( $prepared_activity->content ) ? $prepared_activity->content : $request['content'];
		if ( true === $this->bp_rest_activity_content_validate( $request ) ) {
			return new WP_Error(
				'bp_rest_create_activity_empty_content',
				__( 'Please, enter some content.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			$group_id = 0;
			if ( bp_is_active( 'groups' ) && isset( $prepared_activity->component ) && buddypress()->groups->id === $prepared_activity->component ) {
				$group_id = isset( $prepared_activity->group_id ) ? $prepared_activity->group_id : $request->get_param( 'primary_item_id' );
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

		if ( ! isset( $request['hidden'] ) && isset( $prepared_activity->hide_sitewide ) ) {
			$request['hidden'] = $prepared_activity->hide_sitewide;
		}

		// Fallback for the activity_update type.
		$type = 'activity_update';
		if ( ! empty( $request['type'] ) ) {
			$type = $request['type'];
		}

		$prime       = $request['primary_item_id'];
		$activity_id = 0;

		remove_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

		// Post a regular activity update.
		if ( 'activity_update' === $type ) {
			if ( bp_is_active( 'groups' ) && ! is_null( $prime ) ) {
				remove_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
				$activity_id = groups_post_update( $prepared_activity );
				add_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
			} else {
				remove_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
				$activity_id = bp_activity_post_update( $prepared_activity );
				add_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
			}

			// Post an activity comment.
		} elseif ( 'activity_comment' === $type ) {

			// ID of the root activity item.
			if ( isset( $prime ) ) {
				$prepared_activity->activity_id = (int) $prime;
			}

			// ID of a parent comment.
			if ( isset( $request['secondary_item_id'] ) ) {
				$prepared_activity->parent_id = (int) $request['secondary_item_id'];
			}

			$prepared_activity->skip_notification = true;

			$activity_id = bp_activity_new_comment( $prepared_activity );

			// Otherwise add an activity.
		} else {
			$activity_id = bp_activity_add( $prepared_activity );
		}

		add_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

		if ( ! is_numeric( $activity_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_create_activity',
				__( 'Cannot create new activity.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$activity = bp_activity_get_specific(
			array(
				'activity_ids'     => array( $activity_id ),
				'display_comments' => 'stream',
			)
		);

		$activity      = current( $activity['activities'] );
		$fields_update = $this->update_additional_fields_for_object( $activity, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		bp_activity_at_name_send_emails( $activity );

		if ( 'activity_update' === $type ) {
			if ( bp_is_active( 'groups' ) && ! is_null( $prime ) ) {
				$group_id = ! empty( $prepared_activity->group_id ) ? $prepared_activity->group_id : ( ! empty( $prepared_activity->item_id ) ? $prepared_activity->item_id : 0 );
				bb_subscription_send_subscribe_group_notifications(
					$prepared_activity->content,
					$prepared_activity->user_id,
					$group_id,
					$activity_id
				);
			} else {
				bb_activity_send_email_to_following_post( $prepared_activity->content, $prepared_activity->user_id, $activity_id );
			}

			// Post an activity comment.
		} elseif ( 'activity_comment' === $type ) {
			$activity = new BP_Activity_Activity( ( ! empty( $prepared_activity->activity_id ) ? $prepared_activity->activity_id : 0 ) );

			/**
			 * Fires near the end of an activity comment posting, before the returning of the comment ID.
			 * Sends a notification to the user.
			 *
			 * @param int                  $activity_id       ID of the newly posted activity comment.
			 * @param array                $prepared_activity Array of parsed comment arguments.
			 * @param BP_Activity_Activity $activity          Activity item being commented on.
			 *
			 * @see   bp_activity_new_comment_notification_helper().
			 */
			do_action( 'bp_activity_comment_posted', $activity_id, (array) $prepared_activity, $activity );
		}

		// Update current user's last activity.
		bp_update_user_last_activity();

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $activity, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity item is created via the REST API.
		 *
		 * @param BP_Activity_Activity $activity The created activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_create_item', $activity, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create an activity.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create activities.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( is_user_logged_in() ) {
			$user_id = $request->get_param( 'user_id' );

			if ( empty( $user_id ) || (int) bp_loggedin_user_id() === (int) $user_id ) {
				$item_id   = $request->get_param( 'primary_item_id' );
				$component = $request->get_param( 'component' );

				// The current user can create an activity.
				$retval = true;

				if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $component && ! is_null( $item_id ) ) {
					if ( ! $this->show_hidden( $component, $item_id ) ) {
						$retval = $error;
					}
				}
			}
		}

		/**
		 * Filter the activity `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update an activity.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/activity/:id Update activity
	 * @apiName        UpdateBBActivity
	 * @apiGroup       Activity
	 * @apiDescription Update single activity
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 * @apiParam {Number} [primary_item_id] The ID of some other object primarily associated with this one.
	 * @apiParam {Number} [secondary_item_id] The ID of some other object also associated with this one.
	 * @apiParam {Number} [user_id] The ID for the author of the activity.
	 * @apiParam {string} [link] The permalink to this activity on the site.
	 * @apiParam {String=settings,notifications,groups,forums,activity,media,messages,friends,invites,search,members,xprofile,blogs} [component] The active component the activity relates to.
	 * @apiParam {String=new_member,new_avatar,updated_profile,activity_update,created_group,joined_group,group_details_updated,bbp_topic_create,bbp_reply_create,activity_comment,friendship_accepted,friendship_created,new_blog_post,new_blog_comment} [type] The activity type of the activity.
	 * @apiParam {String} [content] Allowed HTML content for the activity.
	 * @apiParam {String} [date] The date the activity was published, in the site's timezone.
	 * @apiParam {Boolean=true,false} [hidden] Whether the activity object should be sitewide hidden or not.
	 * @apiParam {string=public,loggedin,onlyme,friends,media} [privacy] Privacy of the activity.
	 * @apiParam {Array} [bp_media_ids] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [media_gif] Save gif data into activity when Media component is enable. param(url,mp4)
	 */
	public function update_item( $request ) {
		$request->set_param( 'context', 'edit' );
		$activity_object = $this->prepare_item_for_database( $request );

		if (
			(
				empty( $activity_object->content )
				&& empty( bp_activity_get_meta( $activity_object->id, 'bp_media_ids', true ) )
				&& empty( bp_activity_get_meta( $activity_object->id, '_gif_data', true ) )
				&& empty( bp_activity_get_meta( $activity_object->id, 'bp_document_ids', true ) )
			) && true === $this->bp_rest_activity_content_validate( $request )
		) {
			return new WP_Error(
				'bp_rest_update_activity_empty_content',
				__( 'Please, enter some content.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/**
		 * Map data into POST to work with link preview.
		 */
		$post_map = array(
			'link_url'         => 'link_url',
			'link_embed'       => 'link_embed',
			'link_title'       => 'link_title',
			'link_description' => 'link_description',
			'link_image'       => 'link_image',
		);

		if ( ! empty( $post_map ) ) {
			foreach ( $post_map as $key => $val ) {
				if ( isset( $request[ $val ] ) ) {
					$_POST[ $key ] = $request[ $val ];
				}
			}
		}

		$_POST[ 'action' ] = 'edit-activity';

		$allow_edit = $this->bp_rest_activitiy_edit_data( $activity_object );
		$activity   = new BP_Activity_Activity( $activity_object->id );

		if (
			! empty( $activity->id ) &&
			! empty( $allow_edit ) &&
			false === (bool) $allow_edit['can_edit_privacy'] &&
			isset( $request['privacy'] ) &&
			! empty( $request['privacy'] ) &&
			isset( $activity->privacy ) &&
			$request['privacy'] !== $activity->privacy
		) {
			return new WP_Error(
				'bp_rest_update_invalid_activity_privacy',
				__( 'Sorry, you are not allow to update the privacy', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$activity_id = bp_activity_add( $activity_object );

		if ( ! is_numeric( $activity_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_activity',
				__( 'Cannot update existing activity.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$activity       = $this->get_activity_object( $activity_id );
		$activity->edit = true;
		$fields_update  = $this->update_additional_fields_for_object( $activity, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		if ( function_exists( 'bp_document_activity_update_document_privacy' ) ) {
			// Update privacy for the documents which are uploaded in root of the documents.
			bp_document_activity_update_document_privacy( $activity );
		}

		if ( function_exists( 'bp_media_activity_update_media_privacy' ) ) {
			// Update privacy for the media which are uploaded in the activity.
			bp_media_activity_update_media_privacy( $activity );
		}

		if ( function_exists( 'bp_video_activity_update_video_privacy' ) ) {
			// Update privacy for the videos which are uploaded in root of the documents.
			bp_video_activity_update_video_privacy( $activity );
		}

		bp_activity_update_meta( $activity_id, '_is_edited', bp_core_current_time() );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $activity, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an activity is updated via the REST API.
		 *
		 * @param BP_Activity_Activity $activity The updated activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_update_item', $activity, $response, $request );

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
			__( 'Sorry, you are not allowed to update this activity.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$activity = $this->get_activity_object( $request );

			if ( empty( $activity->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				function_exists( 'bp_is_activity_edit_enabled' )
				&& ! bp_is_activity_edit_enabled()
				&& function_exists( 'bp_activity_user_can_edit' )
				&& ! bp_activity_user_can_edit( $activity )
			) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update this activity.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( bp_activity_user_can_delete( $activity ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the activity `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete activity.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/activity/:id Delete activity
	 * @apiName        DeleteBBActivity
	 * @apiGroup       Activity
	 * @apiDescription Delete single activity
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the activity before it's deleted.
		$activity = $this->get_activity_object( $request );
		$previous = $this->prepare_item_for_response( $activity, $request );

		if ( 'activity_comment' === $activity->type ) {
			$retval = bp_activity_delete_comment( $activity->item_id, $activity->id );
		} else {
			$retval = bp_activity_delete(
				array(
					'id' => $activity->id,
				)
			);
		}

		if ( ! $retval ) {
			return new WP_Error(
				'bp_rest_activity_cannot_delete',
				__( 'Could not delete the activity.', 'buddyboss' ),
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
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after an activity is deleted via the REST API.
		 *
		 * @param BP_Activity_Activity $activity The deleted activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_delete_item', $activity, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete an activity.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete this activity.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$activity = $this->get_activity_object( $request );

			if ( empty( $activity->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid activity ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_activity_user_can_delete( $activity ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the activity `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Gets the current user's favorites.
	 *
	 * @return array Array of activity IDs.
	 * @since 0.1.0
	 */
	public function get_user_favorites() {
		if ( null === $this->user_favorites ) {
			if ( is_user_logged_in() ) {
				$user_favorites       = bp_activity_get_user_favorites( get_current_user_id() );
				$this->user_favorites = array_filter( wp_parse_id_list( $user_favorites ) );
			} else {
				$this->user_favorites = array();
			}
		}

		return $this->user_favorites;
	}

	/**
	 * Adds or removes the activity from the current user's favorites.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/activity/:id/favorite Activity favorite
	 * @apiName        UpdateBBActivityFavorite
	 * @apiGroup       Activity
	 * @apiDescription Make activity favorite/unfavorite
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the activity
	 */
	public function update_favorite( $request ) {
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

		$user_id = get_current_user_id();

		$result = false;
		if ( in_array( $activity->id, $this->get_user_favorites(), true ) ) {
			$result  = bp_activity_remove_user_favorite( $activity->id, $user_id );
			$message = __( 'Sorry, you cannot remove the activity from your favorites.', 'buddyboss' );

			// Update the user favorites, removing the activity ID.
			$this->user_favorites = array_diff( $this->get_user_favorites(), array( $activity->id ) );
		} else {
			$result  = bp_activity_add_user_favorite( $activity->id, $user_id );
			$message = __( 'Sorry, you cannot add the activity to your favorites.', 'buddyboss' );

			// Update the user favorites, adding the activity ID.
			$this->user_favorites[] = (int) $activity->id;
		}

		if ( ! $result ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_activity_favorite',
				$message,
				array(
					'status' => 500,
				)
			);
		}

		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Prepare the response now the user favorites has been updated.
		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $activity, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after user favorited activities has been updated via the REST API.
		 *
		 * @param BP_Activity_Activity $activity       The updated activity.
		 * @param array                $user_favorites The updated user favorites.
		 * @param WP_REST_Response     $response       The response data.
		 * @param WP_REST_Request      $request        The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_update_favorite', $activity, $this->get_user_favorites(), $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update user favorites.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_favorite_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to update favorites.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if (
			is_user_logged_in() && bp_activity_can_favorite()
			&& ( ! function_exists( 'bp_is_activity_like_active' ) || true === bp_is_activity_like_active() )
		) {
			$retval = true;
		}

		/**
		 * Filter the activity `update_favorite` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_update_favorite_permissions_check', $retval, $request );
	}

	/**
	 * Renders the content of an activity.
	 *
	 * @param BP_Activity_Activity $activity Activity data.
	 *
	 * @return string The rendered activity content.
	 * @since 0.1.0
	 */
	public function render_item( $activity ) {
		global $bp;
		$rendered = '';

		// Do not truncate activities.
		add_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );

		if ( 'activity_comment' === $activity->type ) {
			add_filter( 'bp_blogs_activity_comment_content_with_read_more', '__return_false' );
			$rendered = apply_filters( 'bp_get_activity_content', $activity->content, $activity );
			remove_filter( 'bp_blogs_activity_comment_content_with_read_more', '__return_false' );
		} else {
			$activities_template = null;

			if ( isset( $GLOBALS['activities_template'] ) ) {
				$activities_template = $GLOBALS['activities_template'];
			}

			// Set the `activities_template` global for the current activity.
			$GLOBALS['activities_template']           = new stdClass();
			$GLOBALS['activities_template']->activity = $activity;

			// Set up activity oEmbed cache.
			bp_activity_embed();

			// removed combined gif data with content.
			if ( function_exists( 'bp_media_activity_embed_gif' ) ) {
				remove_filter( 'bp_get_activity_content_body', 'bp_media_activity_embed_gif', 20, 2 );
			}

			// Removed link preview from content.
			remove_filter( 'bp_get_activity_content_body', 'bp_activity_link_preview', 20, 2 );

			// Removed lazyload from link preview.
			add_filter( 'bp_get_activity_content_body', array( $this, 'bp_rest_activity_remove_lazyload' ), 999, 2 );

			// Removed Iframe embedded from content.
			if (
				function_exists( 'bp_use_embed_in_activity' ) &&
				bp_use_embed_in_activity() &&
				method_exists( $bp->embed, 'autoembed' ) &&
				method_exists( $bp->embed, 'run_shortcode' )
			) {
				remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'autoembed' ), 8, 2 );
				remove_filter( 'bp_get_activity_content_body', array( $bp->embed, 'run_shortcode' ), 7, 2 );
			}

			$rendered = apply_filters_ref_array(
				'bp_get_activity_content_body',
				array(
					$activity->content,
					&$activity,
				)
			);

			remove_filter( 'bp_get_activity_content_body', array( $this, 'bp_rest_activity_remove_lazyload' ), 999, 2 );

			// Restore the link preview.
			add_filter( 'bp_get_activity_content_body', 'bp_activity_link_preview', 20, 2 );

			// removed combined gif data with content.
			if ( function_exists( 'bp_media_activity_embed_gif' ) ) {
				add_filter( 'bp_get_activity_content_body', 'bp_media_activity_embed_gif', 20, 2 );
			}

			// Restore the `activities_template` global.
			$GLOBALS['activities_template'] = $activities_template;
		}

		// Restore the filter to truncate activities.
		remove_filter( 'bp_activity_maybe_truncate_entry', '__return_false' );

		return $rendered;
	}

	/**
	 * Prepares activity data for return as an object.
	 *
	 * @param BP_Activity_Activity $activity Activity data.
	 * @param WP_REST_Request      $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $activity, $request ) {
		$top_level_parent_id = 'activity_comment' === $activity->type ? $activity->item_id : 0;
		global $activities_template, $bp;
		$activities_template                            = new \stdClass();
		$activities_template->disable_blogforum_replies = (bool) bp_core_get_root_option( 'bp-disable-blogforum-comments' );
		$activities_template->activity                  = $activity;
		$activities_template->in_the_loop               = true;

		// Remove feature image from content from the activity feed which added last in the content.
		$blog_id = '';
		if ( 'blogs' === $activity->component && isset( $activity->secondary_item_id ) && 'new_blog_' . get_post_type( $activity->secondary_item_id ) === $activity->type ) {
			$blog_post = get_post( $activity->secondary_item_id );
			if ( ! empty( $blog_post->ID ) ) {
				$blog_id = $blog_post->ID;
				remove_filter( 'bb_add_feature_image_blog_post_as_activity_content', 'bb_add_feature_image_blog_post_as_activity_content_callback' );
			}
		}

		$data = array(
			'user_id'           => $activity->user_id,
			'name'              => bp_core_get_user_displayname( $activity->user_id ),
			'component'         => $activity->component,
			'content'           => array(
				'raw'      => bb_rest_raw_content( $activity->content ),
				'rendered' => $this->render_item( $activity ),
			),
			'date'              => bp_rest_prepare_date_response( $activity->date_recorded ),
			'id'                => $activity->id,
			'link'              => bp_activity_get_permalink( $activity->id ),
			'primary_item_id'   => $activity->item_id,
			'secondary_item_id' => $activity->secondary_item_id,
			'status'            => $activity->is_spam ? 'spam' : 'published',
			'title'             => $this->bb_rest_activity_action( $activity->action, $activity ),
			'type'              => $activity->type,
			'favorited'         => in_array( $activity->id, $this->get_user_favorites(), true ),

			// extend response.
			'can_favorite'      => bp_activity_can_favorite(),
			'favorite_count'    => $this->get_activity_favorite_count( $activity->id ),
			'can_comment'       => ( 'activity_comment' === $activity->type ) ? bp_activity_can_comment_reply( $activity ) : bp_activity_can_comment(),
			'can_edit'          => (
				(
					function_exists( 'bp_is_activity_edit_enabled' )
					&& bp_is_activity_edit_enabled()
					&& function_exists( 'bp_activity_user_can_edit' )
					&& bp_activity_user_can_edit( $activity )
				) && (
					isset( $activity->privacy ) &&
					! in_array( $activity->privacy, array( 'document', 'media', 'video' ), true )
				)
				? true
				: false
			),
			'is_edited'         => ! empty( bp_activity_get_meta( $activity->id, '_is_edited', true ) ),
			'can_delete'        => bp_activity_user_can_delete( $activity ),
			'content_stripped'  => html_entity_decode( wp_strip_all_tags( $activity->content ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
			'privacy'           => ( isset( $activity->privacy ) ? $activity->privacy : false ),
			'activity_data'     => $this->bp_rest_activitiy_edit_data( $activity ),
			'feature_media'     => '',
			'preview_data'      => '',
			'link_embed_url'    => '',
		);

		// Add feature image as separate object which added last in the content.
		if ( ! empty( $blog_id ) && ! empty( get_post_thumbnail_id( $blog_id ) ) ) {
			$data['feature_media'] = wp_get_attachment_image_url( get_post_thumbnail_id( $blog_id ), 'full' );
		}

		// Add iframe embedded data in separate object.
		$link_embed = bp_activity_get_meta( $activity->id, '_link_embed', true );

		if ( ! empty( $link_embed ) ) {
			$data['link_embed_url'] = $link_embed;
		}

		if ( ! empty( $link_embed ) && method_exists( $bp->embed, 'autoembed' ) ) {
			$data['preview_data'] = $bp->embed->autoembed( '', $activity );

			// Removed lazyload from link preview.
			$data['preview_data'] = $this->bp_rest_activity_remove_lazyload( $data['preview_data'], $activity, true );
		} elseif ( method_exists( $bp->embed, 'autoembed' ) && ! empty( $data['content_stripped'] ) ) {
			$skip_embed = false;
			if ( ! empty( $data['content']['rendered'] ) ) {

				// Check if already embed in rendered content.
				preg_match( '/<iframe[^>]*><\/iframe>/', $data['content']['rendered'], $matchcontent );
				if ( ! empty( $matchcontent[0] ) ) {
					$skip_embed = true;
				}
			}
			if ( ! $skip_embed ) {
				$check_embedded_content = $bp->embed->autoembed( $data['content_stripped'], $activity );
				if ( ! empty( $check_embedded_content ) ) {
					preg_match( '/<iframe[^>]*><\/iframe>/', $check_embedded_content, $match );
					if ( ! empty( $match[0] ) ) {
						$data['preview_data'] = $match[0];
						// Use a regular expression to find the src URL.
						preg_match( '/src="([^"]+)"/', $match[0], $matches );
						if ( ! empty( $matches[1] ) ) {
	
							// Set link_embed_url with the iframe src URL as a fallback.
							$data['link_embed_url'] = $matches[1];
						}
					}
				}
				// Removed lazyload from link preview.
				$data['preview_data'] = $this->bp_rest_activity_remove_lazyload( $data['preview_data'], $activity, true );
			}
		}

		// Add link preview data in separate object.
		$link_preview = bp_activity_link_preview( '', $activity );
		if ( ! empty( $link_preview ) ) {
			$data['preview_data'] = $link_preview;
		} elseif ( empty( $link_preview )  && in_array( $activity->type, array( 'bbp_reply_create', 'bbp_topic_create' ), true ) ) {
			$data['preview_data'] = $this->bp_rest_activity_remove_lazyload( $data['preview_data'], $activity, true );
		}

		// remove comment options from media/document/video activity.
		if (
			! empty( $activity->item_id ) &&
			! empty( $activity->secondary_item_id ) &&
			! empty( $activity->privacy ) &&
			in_array( $activity->privacy, array( 'media', 'document', 'video' ), true ) &&
			'activity_comment' === $activity->type
		) {
			$item_activity = new BP_Activity_Activity( $activity->item_id );
			if (
				empty( $item_activity->item_id ) &&
				! empty( $item_activity->secondary_item_id ) &&
				! empty( $item_activity->privacy ) &&
				in_array( $item_activity->privacy, array( 'media', 'document', 'video' ), true )
			) {

				$secondary_activity = new BP_Activity_Activity( $item_activity->secondary_item_id );
				if (
					! empty( $secondary_activity->privacy ) &&
					in_array( $secondary_activity->privacy, array( 'media', 'document', 'video' ), true )
				) {
					$data['can_comment']  = false;
					$data['can_edit']     = false;
					$data['can_favorite'] = false;
				}
			}
		}

		// Get item schema.
		$schema = $this->get_item_schema();

		// Get comments (count).
		if ( ! empty( $activity->children ) ) {
			$data['comment_count'] = bp_activity_recurse_comment_count( $activity );

			if ( ! empty( $schema['properties']['comments'] ) && 'threaded' === $request['display_comments'] ) {
				// First check the comment is disabled from the activity settings for post type.
				// For more information please check this PROD-2475.
				if ( 'blogs' === $activity->component && $data['can_comment'] ) {
					$data['comments'] = $this->prepare_activity_comments( $activity->children, $request );
					// This is for activity comment to attach the comment in the feed.
				} elseif ( 'blogs' !== $activity->component ) {
					$data['comments'] = $this->prepare_activity_comments( $activity->children, $request );
				}
			}
		} else {
			$activity->children    = BP_Activity_Activity::get_activity_comments( $activity->id, $activity->mptt_left, $activity->mptt_right, $request['status'], $top_level_parent_id );
			$data['comment_count'] = ! empty( $activity->children ) ? bp_activity_recurse_comment_count( $activity ) : 0;
		}

		if ( ! empty( $schema['properties']['user_avatar'] ) ) {
			$data['user_avatar'] = array(
				'full'  => bp_core_fetch_avatar(
					array(
						'item_id' => $activity->user_id,
						'html'    => false,
						'type'    => 'full',
					)
				),
				'thumb' => bp_core_fetch_avatar(
					array(
						'item_id' => $activity->user_id,
						'html'    => false,
					)
				),
			);
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $activity ) );

		/**
		 * Filter an activity value returned from the API.
		 *
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  Request used to generate the response.
		 * @param BP_Activity_Activity $activity The activity object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_prepare_value', $response, $request, $activity );
	}

	/**
	 * Prepare activity comments.
	 *
	 * @param array           $comments Comments.
	 * @param WP_REST_Request $request  Full details about the request.
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
			$data[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $comment, $request )
			);
		}

		/**
		 * Filter activity comments returned from the API.
		 *
		 * @param array           $data     An array of activity comments.
		 * @param array           $comments Comments.
		 * @param WP_REST_Request $request  Request used to generate the response.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_prepare_comments', $data, $comments, $request );
	}

	/**
	 * Prepare an activity for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return stdClass|WP_Error Object or WP_Error.
	 * @since 0.1.0
	 */
	protected function prepare_item_for_database( $request ) {
		$prepared_activity = new stdClass();
		$schema            = $this->get_item_schema();
		$activity          = $this->get_activity_object( $request );

		if ( ! empty( $schema['properties']['id'] ) && ! empty( $activity->id ) ) {
			$prepared_activity     = $activity;
			$prepared_activity->id = $activity->id;

			if ( 'activity_comment' !== $request['type'] ) {
				$prepared_activity->error_type = 'wp_error';
			}
		}

		// Activity author ID.
		if ( ! empty( $schema['properties']['user_id'] ) && isset( $request['user_id'] ) ) {
			$prepared_activity->user_id = (int) $request['user_id'];
		} else {
			$prepared_activity->user_id = get_current_user_id();
		}

		// Activity component.
		if ( ! empty( $schema['properties']['component'] ) && isset( $request['component'] ) ) {
			$prepared_activity->component = $request['component'];
		} else {
			$prepared_activity->component = ( isset( $activity->component ) ? $activity->component : buddypress()->activity->id );
		}

		// Activity Item ID.
		if ( ! empty( $schema['properties']['primary_item_id'] ) && isset( $request['primary_item_id'] ) ) {
			$item_id = (int) $request['primary_item_id'];

			// Set the group ID of the activity.
			if ( bp_is_active( 'groups' ) && isset( $prepared_activity->component ) && buddypress()->groups->id === $prepared_activity->component ) {
				$prepared_activity->group_id = $item_id;

				$status = bp_get_group_status( groups_get_group( $item_id ) );

				// Use a generic item ID for other components.
			} else {
				$prepared_activity->item_id = $item_id;
			}
		}

		// Secondary Item ID.
		if ( ! empty( $schema['properties']['secondary_item_id'] ) && isset( $request['secondary_item_id'] ) ) {
			$prepared_activity->secondary_item_id = (int) $request['secondary_item_id'];
		}

		// Activity type.
		if ( ! empty( $schema['properties']['type'] ) && isset( $request['type'] ) ) {
			$prepared_activity->type = $request['type'];
		}

		// Activity content.
		if ( ! empty( $schema['properties']['content'] ) && isset( $request['content'] ) ) {
			if ( is_string( $request['content'] ) ) {
				$prepared_activity->content = $request['content'];
			} elseif ( isset( $request['content']['raw'] ) ) {
				$prepared_activity->content = $request['content']['raw'];
			}
		}

		// Activity Sitewide visibility.
		if ( ! empty( $schema['properties']['hidden'] ) && isset( $request['hidden'] ) ) {
			$prepared_activity->hide_sitewide = (bool) $request['hidden'];
		}

		// Activity Privacy.
		if ( ! empty( $schema['properties']['privacy'] ) && isset( $request['privacy'] ) ) {
			$prepared_activity->privacy = $request['privacy'];
		} else {
			if ( ! empty( $activity->privacy ) ) {
				$prepared_activity->privacy = $activity->privacy;
			} else {
				$prepared_activity->privacy = 'public';
			}
		}

		if ( ! empty( $status ) && in_array( $status, array( 'hidden', 'private' ), true ) ) {
			$prepared_activity->hide_sitewide = true;
		}

		// Ignore privacy passed when posting into group.
		if ( ! empty( $status ) ) {
			$prepared_activity->privacy = 'public';
		}

		/**
		 * Filters an activity before it is inserted or updated via the REST API.
		 *
		 * @param stdClass        $prepared_activity An object prepared for inserting or updating the database.
		 * @param WP_REST_Request $request           Request object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_pre_insert_value', $prepared_activity, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $activity ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$url  = $base . $activity->id;

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $activity->user_id ) ),
				'embeddable' => true,
			),
		);

		if ( 'activity_comment' === $activity->type ) {
			$links['up'] = array(
				'href' => rest_url( $url ),
			);
		}

		if ( bp_activity_can_favorite() ) {
			$links['favorite'] = array(
				'href' => rest_url( $url . '/favorite' ),
			);
		}

		if ( bp_is_active( 'groups' ) && 'groups' === $activity->component && ! empty( $activity->item_id ) ) {
			$links['group'] = array(
				'href'       => rest_url( sprintf( '%s/%s/%d', $this->namespace, buddypress()->groups->id, $activity->item_id ) ),
				'embeddable' => true,
			);
		}

		if ( 'bbp_topic_create' === $activity->type && function_exists( 'bb_activity_topic_id' ) && bb_activity_topic_id( $activity ) ) {
			$links['topic'] = array(
				'href'       => rest_url( sprintf( '%s/%s/%d', $this->namespace, 'topics', bb_activity_topic_id( $activity ) ) ),
				'embeddable' => true,
			);
		}

		if ( 'bbp_reply_create' === $activity->type && function_exists( 'bb_activity_reply_topic_id' ) && bb_activity_reply_topic_id( $activity ) ) {
			$links['topic'] = array(
				'href'       => rest_url( sprintf( '%s/%s/%d', $this->namespace, 'topics', bb_activity_reply_topic_id( $activity ) ) ),
				'embeddable' => true,
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array                $links    The prepared links of the REST response.
		 * @param BP_Activity_Activity $activity Activity object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_activity_prepare_links', $links, $activity );
	}

	/**
	 * Can this user see the activity?
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return boolean
	 * @since 0.1.0
	 */
	protected function can_see( $request ) {
		$activity = $this->get_activity_object( $request );

		return ( ! empty( $activity ) ? bp_activity_user_can_read( $activity, bp_loggedin_user_id() ) : false );
	}

	/**
	 * Show hidden activity?
	 *
	 * @param string $component The activity component.
	 * @param int    $item_id   The activity item ID.
	 *
	 * @return boolean
	 * @since 0.1.0
	 */
	protected function show_hidden( $component, $item_id ) {
		$user_id = get_current_user_id();
		$retval  = false;

		if ( ! is_null( $component ) ) {
			// If activity is from a group, do an extra cap check.
			if ( ! $retval && ! empty( $item_id ) && bp_is_active( $component ) && buddypress()->groups->id === $component ) {
				// Group admins and mods have access as well.
				if ( groups_is_user_admin( $user_id, $item_id ) || groups_is_user_mod( $user_id, $item_id ) ) {
					$retval = true;

					// User is a member of the group.
				} elseif ( (bool) groups_is_user_member( $user_id, $item_id ) ) {
					$retval = true;
				}
			}
		}

		// Moderators as well.
		if ( bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		return (bool) $retval;
	}

	/**
	 * Get activity object.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return BP_Activity_Activity|string An activity object.
	 * @since 0.1.0
	 */
	public function get_activity_object( $request ) {
		$activity_id = is_numeric( $request ) ? $request : (int) $request['id'];

		$activity = bp_activity_get_specific(
			array(
				'activity_ids'     => array( $activity_id ),
				'display_comments' => true,
			)
		);

		if ( is_array( $activity ) && ! empty( $activity['activities'][0] ) ) {
			return $activity['activities'][0];
		} else {
			$activity = new BP_Activity_Activity( $activity_id );

			if ( is_object( $activity ) && ! empty( $activity->id ) ) {
				return $activity;
			}
		}

		return '';
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
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$key                     = 'create_item';
			$args['content']['type'] = 'string';
			unset( $args['content']['properties'] );

			if ( WP_REST_Server::EDITABLE === $method ) {
				$key = 'update_item';
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
		return apply_filters( "bp_rest_activity_{$key}_query_arguments", $args, $method );
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
			'title'      => 'bp_activity',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the activity.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'primary_item_id'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of some other object primarily associated with this one.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'secondary_item_id' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID of some other object also associated with this one.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'user_id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID for the author of the activity.', 'buddyboss' ),
					'type'        => 'integer',
				),
				'name'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'User\'s display name for the activity.', 'buddyboss' ),
					'type'        => 'string',
				),
				'link'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The permalink to this activity on the site.', 'buddyboss' ),
					'format'      => 'uri',
					'type'        => 'string',
				),
				'component'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The active BuddyPress component the activity relates to.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array_keys( buddypress()->active_components ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'type'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The activity type of the activity.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array_keys( bp_activity_get_types() ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'title'             => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The description of the activity\'s type (eg: Username posted an update)', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'content'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Allowed HTML content for the activity.', 'buddyboss' ),
					'type'        => 'object',
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null,
						// Note: validation implemented in self::prepare_item_for_database().
					),
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the activity, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the activity, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'date'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( "The date the activity was published, in the site's timezone.", 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'status'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the activity has been marked as spam or not.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array( 'published', 'spam' ),
					'readonly'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_key',
					),
				),
				'comments'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A list of objects children of the activity object.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'comment_count'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Total number of comments of the activity object.', 'buddyboss' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'hidden'            => array(
					'context'     => array( 'edit' ),
					'description' => __( 'Whether the activity object should be sitewide hidden or not.', 'buddyboss' ),
					'type'        => 'boolean',
				),
				'favorited'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the activity object has been favorited by the current user.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_favorite'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user have the favorite access for the activity object.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'favorite_count'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Favorite count for the activity object.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_comment'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user have the comment access for the activity object.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'comment_count'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Comment count for the activity object.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_edit'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user have the edit access for the activity object.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'is_edited'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Determine whether an activity has been edited or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_delete'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether or not user have the delete access for the activity object.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'content_stripped'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Content for the activity without HTML tags.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'privacy'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Privacy of the activity.', 'buddyboss' ),
					'type'        => 'string',
					'enum'        => array( 'public', 'loggedin', 'onlyme', 'friends', 'media' ),
				),
				'activity_data'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Activity data for allow edit or not.', 'buddyboss' ),
					'type'        => 'object',
				),
				'feature_media'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Feature media image which added last in the content for blog post as well as custom post type.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
				),
				'preview_data'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'WordPress Embed data with activity.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'link_embed_url'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'WordPress Embed URL with activity.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);

		// Avatars.
		if ( true === buddypress()->avatar->show_avatars ) {
			$avatar_properties = array();

			$avatar_properties['full'] = array(
				'context'     => array( 'embed', 'view', 'edit' ),
				/* translators: 1: Full avatar width in pixels. 2: Full avatar height in pixels */
				'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_full_width() ), bp_core_number_format( bp_core_avatar_full_height() ) ),
				'type'        => 'string',
				'format'      => 'uri',
			);

			$avatar_properties['thumb'] = array(
				'context'     => array( 'embed', 'view', 'edit' ),
				/* translators: 1: Thumb avatar width in pixels. 2: Thumb avatar height in pixels */
				'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_thumb_width() ), bp_core_number_format( bp_core_avatar_thumb_height() ) ),
				'type'        => 'string',
				'format'      => 'uri',
			);

			$schema['properties']['user_avatar'] = array(
				'context'     => array( 'embed', 'view', 'edit' ),
				'description' => __( 'Avatar URLs for the author of the activity.', 'buddyboss' ),
				'type'        => 'object',
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		/**
		 * Filters the activity schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_activity_schema', $this->add_additional_fields_schema( $schema ) );
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

		$params['orderby'] = array(
			'description'       => __( 'Order by a specific parameter.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'enum'              => array( 'id', 'include' ),
			'sanitize_callback' => 'sanitize_key',
		);

		$params['after'] = array(
			'description'       => __( 'Limit result set to items published after a given ISO8601 compliant date.', 'buddyboss' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit result set to items created by a specific user (ID).', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Limit result set to items with a specific status.', 'buddyboss' ),
			'default'           => 'ham_only',
			'type'              => 'string',
			'enum'              => array( 'ham_only', 'spam_only', 'all' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description'       => __( 'Limit result set to items with a specific scope.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array( 'just-me', 'friends', 'groups', 'favorites', 'mentions', 'following' ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['group_id'] = array(
			'description'       => __( 'Limit result set to items created by a specific group.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['site_id'] = array(
			'description'       => __( 'Limit result set to items created by a specific site.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['primary_id'] = array(
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

		$params['component'] = array(
			'description'       => __( 'Limit result set to items with a specific active BuddyPress component.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array_keys( buddypress()->active_components ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['type'] = array(
			'description'       => __( 'Limit result set to items with a specific activity type.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array_keys( bp_activity_get_types() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['display_comments'] = array(
			'description'       => __( 'No comments by default, stream for within stream display, threaded for below each activity item.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['privacy'] = array(
			'description'       => __( 'Privacy of the activity.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array( 'public', 'loggedin', 'onlyme', 'friends', 'media' ),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
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
	 * Get favorite count for activity.
	 *
	 * @param int $activity_id The activity id.
	 *
	 * @return int|mixed
	 */
	public function get_activity_favorite_count( $activity_id ) {

		if ( empty( $activity_id ) ) {
			return;
		}

		$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count', true );

		return ( ! empty( $fav_count ) ? $fav_count : 0 );
	}

	/**
	 * Validate
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return int|mixed
	 */
	public function bp_rest_activity_content_validate( $request ) {
		$toolbar_option = true;

		if ( ! empty( trim( wp_strip_all_tags( $request['content'] ) ) ) ) {
			return false;
		}

		if ( bp_is_activity_link_preview_active() && ! empty( $request['link_url'] ) ) {
			return false;
		}

		$toolbar_option = (
			bp_is_active( 'media' )
			&& (
				empty( $request['bp_media_ids'] )
				&& (
					empty( $request['media_gif'] )
					&& (
						empty( $request['media_gif']['url'] )
						|| empty( $request['media_gif']['mp4'] )
					)
				)
				&& empty( $request['bp_documents'] )
				&& empty( $request['bp_videos'] )
			)
		);

		return $toolbar_option;
	}

	/**
	 * Get default scope for the activity
	 * - from: bp_activity_default_scope();
	 *
	 * @param string $scope      Default scope.
	 * @param int    $user_id    User ID.
	 * @param int    $group_id   Group ID.
	 * @param string $component  Component name.
	 * @param int    $primary_id Primary ID.
	 *
	 * @return string
	 */
	public function bp_rest_activity_default_scope( $scope = 'all', $user_id = 0, $group_id = 0, $component = '', $primary_id = 0 ) {
		$new_scope = array();

		if (
			bp_loggedin_user_id()
			&& (
				'all' === $scope
				|| empty( $scope )
				|| 'just-me' === $scope
			)
		) {
			if ( bp_is_active( 'groups' ) && ( ! empty( $group_id ) || ( ! empty( $component ) && 'groups' === $component && ! empty( $primary_id ) ) ) ) {
				$new_scope[] = 'activity';
			} else {
				$new_scope[] = 'just-me';

				if (
					empty( $user_id ) ||
					(
						bp_loggedin_user_id() === $user_id &&
						( ! function_exists( 'bp_is_activity_tabs_active' ) || ! bp_is_activity_tabs_active() )
					)
				) {
					if ( empty( $user_id ) ) {
						$new_scope[] = 'public';
					}

					if ( function_exists( 'bp_activity_do_mentions' ) && bp_activity_do_mentions() ) {
						$new_scope[] = 'mentions';
					}

					if ( bp_is_active( 'friends' ) ) {
						$new_scope[] = 'friends';
					}

					if ( bp_is_active( 'groups' ) ) {
						$new_scope[] = 'groups';
					}

					if ( function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
						$new_scope[] = 'following';
					}
				}

				if ( bp_is_single_activity() && bp_is_active( 'media' ) ) {
					$new_scope[] = 'media';
					$new_scope[] = 'document';
				}
			}
		} elseif ( ! bp_loggedin_user_id() && ( 'all' === $scope || empty( $scope ) ) ) {
			$new_scope[] = 'public';
		}

		$new_scope = array_unique( $new_scope );

		if ( empty( $new_scope ) ) {
			$new_scope = (array) $scope;
		}

		if (
			bp_loggedin_user_id() &&
			empty( $user_id ) &&
			function_exists( 'bp_is_relevant_feed_enabled' ) &&
			bp_is_relevant_feed_enabled()
		) {
			$key = array_search( 'public', $new_scope, true );
			if ( is_array( $new_scope ) && false !== $key ) {
				unset( $new_scope[ $key ] );
				if ( bp_is_active( 'forums' ) ) {
					$new_scope[] = 'forums';
				}
			}
		}

		/**
		 * Filter to update default scope.
		 */
		$new_scope = apply_filters( 'bp_rest_activity_default_scope', $new_scope );

		return implode( ',', $new_scope );

	}

	/**
	 * Collect the activity information.
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 *
	 * @return array
	 */
	public function bp_rest_activitiy_edit_data( $activity ) {
		global $activities_template;
		if ( ! is_object( $activities_template ) ) {
			$activities_template = new stdClass();
		}

		if ( ! isset( $activities_template->activity ) ) {
			$activities_template->activity = $activity;
		}

		$activity_temp = $activities_template->activity;

		if ( empty( $activity->id ) ) {
			return array();
		}

		if ( ! function_exists( 'bp_activity_get_edit_data' ) ) {
			return array();
		}

		$parent_activity = empty( $activity->item_id ) ? false : new BP_Activity_Activity( $activity->item_id );

		// For getting group comment activity_data.
		if ( 'activity_comment' === $activity->type && ! empty( $parent_activity->id ) && 'groups' === $parent_activity->component && ! empty( $parent_activity->item_id ) ) {
			$activities_template->activity = $parent_activity;
			$edit_activity_data            = bp_activity_get_edit_data( $activity->item_id );
		} else {
			$edit_activity_data = bp_activity_get_edit_data( $activity->id );
		}

		$edit_activity_data = bp_activity_get_edit_data( $activity->id );
		$edit_activity_data = empty( $edit_activity_data ) ? array() : $edit_activity_data;

		if ( ! empty( $edit_activity_data ) ) {
			// Removed unwanted data.
			$unset_keys = array( 'id', 'content', 'item_id', 'object', 'privacy', 'media', 'gif', 'document', 'video' );
			foreach ( $unset_keys as $key ) {
				if ( array_key_exists( $key, $edit_activity_data ) ) {
					unset( $edit_activity_data[ $key ] );
				}
			}
		}

		if ( isset( $edit_activity_data['group_media'] ) && 'activity_comment' === $activity->type && ! empty( $activity->item_id ) && ! empty( $parent_activity ) ) {
			if ( ! empty( $parent_activity->id ) && 'groups' === $parent_activity->component && ! empty( $parent_activity->item_id ) ) {
				$edit_activity_data['group_media'] = bp_is_group_media_support_enabled() && ( ! function_exists( 'bb_media_user_can_upload' ) || bb_media_user_can_upload( bp_loggedin_user_id(), ( bp_is_active( 'groups' ) ? $parent_activity->item_id : 0 ) ) );
			}
		}

		if ( isset( $edit_activity_data['group_document'] ) && 'activity_comment' === $activity->type && ! empty( $activity->item_id ) && ! empty( $parent_activity ) ) {
			if ( ! empty( $parent_activity->id ) && 'groups' === $parent_activity->component && ! empty( $parent_activity->item_id ) ) {
				$edit_activity_data['group_document'] = bp_is_group_document_support_enabled() && ( ! function_exists( 'bb_document_user_can_upload' ) || bb_document_user_can_upload( bp_loggedin_user_id(), ( bp_is_active( 'groups' ) ? $parent_activity->item_id : 0 ) ) );
			}
		}

		$activities_template->activity = $activity_temp;

		return (array) $edit_activity_data;
	}

	/**
	 * Removed lazyload from link preview embed.
	 *
	 * @param string               $content  Activity Content.
	 * @param BP_Activity_Activity $activity Activity object.
	 *
	 * @return null|string|string[]
	 */
	public function bp_rest_activity_remove_lazyload( $content, $activity, $preview = false ) {

		// Generate link preview for the forums.
		if (
			bp_is_active( 'forums' ) &&
			in_array( $activity->type, array( 'bbp_reply_create', 'bbp_topic_create' ), true ) &&
			! empty( $activity->item_id ) &&
			true === $preview
		) {
			$post_id    = $activity->item_id;
			$link_embed = get_post_meta( $post_id, '_link_embed', true );
			if ( ! empty( $link_embed ) ) {
				if ( bbp_is_reply( $post_id ) ) {
					$content = bbp_reply_content_autoembed_paragraph( $content, $post_id );
				} else {
					$content = bbp_topic_content_autoembed_paragraph( $content, $post_id );
				}
			} else {
				$content = bb_forums_link_preview( $content, $post_id );
			}
		}

		if ( empty( $content ) ) {
			return $content;
		}

		$content = preg_replace( '/iframe(.*?)data-lazy-type="iframe"/is', 'iframe$1', $content );
		$content = preg_replace( '/iframe(.*?)class="lazy/is', 'iframe$1class="', $content );
		$content = preg_replace( '/iframe(.*?)data-src=/is', 'iframe$1src=', $content );

		return $content;
	}

	/**
	 * Function to update the activity action.
	 *
	 * @param string               $action   Activity action from DB.
	 * @param BP_Activity_Activity $activity Activity object.
	 *
	 * @return array|string|string[]|null
	 */
	public function bb_rest_activity_action( $action, $activity ) {
		if ( empty( $activity->type ) || 'group_details_updated' !== $activity->type ) {
			return $action;
		}

		return preg_replace( "/[\r\n]+/", "\r\n", $action );
	}
}
