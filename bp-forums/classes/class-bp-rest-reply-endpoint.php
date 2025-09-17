<?php
/**
 * BP REST: BP_REST_Reply_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reply endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Reply_Endpoint extends WP_REST_Controller {

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
						'description' => __( 'A unique numeric ID for the reply.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve Replies.
	 * - from bbp_has_replies().
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/reply Replies
	 * @apiName        GetBBPReplies
	 * @apiGroup       Forum Replies
	 * @apiDescription Retrieve Replies
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String} [author] Author ID, or comma-separated list of IDs.
	 * @apiParam {Array} [author_exclude] An array of author IDs not to query from.
	 * @apiParam {Array} [exclude] An array of topic IDs not to retrieve.
	 * @apiParam {Array} [include] An array of topic IDs to retrieve.
	 * @apiParam {Number} [offset] The number of topics to offset before retrieval.
	 * @apiParam {String=asc,desc} [order=asc] Designates ascending or descending order of replies.
	 * @apiParam {Array=meta_value,date,ID,author,title,modified,parent,rand} [orderby] Sort retrieved replies by parameter.
	 * @apiParam {Number} [parent] Topic ID or Reply ID to retrieve all the child replies.
	 * @apiParam {Boolean} [thread_replies] Calculated value and the thread replies depth.
	 * @apiParam {String=all} [view] If current user can and is viewing all replies.
	 */
	public function get_items( $request ) {

		$args = array(
			'post_parent'      => ( ! empty( $request['parent'] ) ? $request['parent'] : 'any' ),
			'orderby'          => ( ! empty( $request['orderby'] ) ? $request['orderby'] : 'date' ),
			'order'            => ( ! empty( $request['order'] ) ? $request['order'] : 'ASC' ),
			'paged'            => ( ! empty( $request['page'] ) ? $request['page'] : '' ),
			'posts_per_page'   => ( ! empty( $request['per_page'] ) ? $request['per_page'] : '' ),
			'moderation_query' => false,
		);

		if ( ! empty( $request['search'] ) ) {
			$args['s'] = $this->forum_endpoint->bbp_sanitize_search_request( $request['search'] );
		}

		if ( ! empty( $request['author'] ) ) {
			$args['author'] = $request['author'];
		}

		if ( ! empty( $request['author_exclude'] ) ) {
			$args['author__not_in'] = $request['author_exclude'];
		}

		if ( ! empty( $request['exclude'] ) ) {
			$args['post__not_in'] = $request['exclude'];
		}

		if ( ! empty( $request['include'] ) ) {
			$args['post__in'] = $request['include'];
		}

		if ( ! empty( $request['offset'] ) ) {
			$args['offset'] = $request['offset'];
		}

		// Added support for fetch user replies by order.
		if ( isset( $request['hierarchical'] ) ) {
			$args['hierarchical'] = (bool) $request['hierarchical'];
		}

		if ( isset( $request['thread_replies'] ) ) {
			$thread_replies = (bool) $request['thread_replies'];
		} else {
			$thread_replies = (bool) ( bbp_thread_replies() );
		}

		if ( is_array( $args['orderby'] ) ) {
			$args['orderby'] = implode( ' ', $args['orderby'] );
		}

		if (
			! empty( $request['include'] )
			&& ! empty( $args['orderby'] )
			&& 'include' === $args['orderby']
		) {
			$args['orderby'] = 'post__in';
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_reply_get_items_query_args', $args, $request );

		$default_thread_replies = (bool) ( bbp_thread_replies() );

		$default = array(
			'post_type'                => bbp_get_reply_post_type(),
			'ignore_sticky_posts'      => true,
			'max_num_pages'            => false,
			'hierarchical'             => $default_thread_replies,
			'update_post_term_cache'   => false,

			// Conditionally prime the cache for related posts.
			'update_post_family_cache' => true,
		);

		// What are the default allowed statuses (based on user caps).
		if ( bbp_get_view_all( 'edit_others_replies' ) ) {

			// Default view=all statuses.
			$post_statuses = array_keys( bbp_get_topic_statuses() );

			// Add support for private status.
			if ( current_user_can( 'read_private_replies' ) ) {
				$post_statuses[] = bbp_get_private_status_id();
			}

			// Join post statuses together.
			$default['post_status'] = $post_statuses;

			// Lean on the 'perm' query var value of 'readable' to provide statuses.
		} else {
			$default['perm'] = 'readable';
		}

		// Parse arguments against default values.
		$r = bbp_parse_args( $args, $default, 'has_replies' );

		$replies_per_page = $r['posts_per_page'];
		$page_number      = $r['paged'];

		if ( $r['hierarchical'] && empty( $r['s'] ) && true === $thread_replies ) {
			$r['page']           = 1;
			$r['posts_per_page'] = - 1;

			// Run the query.
			$replies_query = new WP_Query( $r );

			if ( ! empty( $replies_query->posts ) ) {
				foreach ( $replies_query->posts as $k => $v ) {
					$replies_query->posts[ $k ]->reply_to = (int) get_post_meta( $v->ID, '_bbp_reply_to', true );
				}
			}

			// Revert arguments.
			$r['page']           = $page_number;
			$r['posts_per_page'] = $replies_per_page;

			// Parse arguments.
			$walk_arg = bbp_parse_args(
				array(),
				array(
					'walker'       => '',
					'max_depth'    => bbp_thread_replies_depth(),
					'callback'     => null,
					'end_callback' => null,
					'page'         => $r['page'],
					'per_page'     => $r['posts_per_page'],
				),
				'list_replies'
			);

			global $buddyboss_thread_reply;
			$buddyboss_thread_reply = array();

			$this->bbb_walker_reply->paged_walk( $replies_query->posts, $walk_arg['max_depth'], $walk_arg['page'], $walk_arg['per_page'], $walk_arg );
			$total_parent_replies       = $this->bbb_walker_reply->get_number_of_root_elements( $replies_query->posts );
			$replies_query->posts       = $buddyboss_thread_reply;
			$replies_query->found_posts = $total_parent_replies;

		} else {
			// Run the query.
			$replies_query = new WP_Query( $r );
		}

		if ( false === $thread_replies && bbp_thread_replies() ) {
			if ( ! empty( $replies_query->posts ) ) {
				foreach ( $replies_query->posts as $k => $v ) {
					$replies_query->posts[ $k ]->reply_to = (int) get_post_meta( $v->ID, '_bbp_reply_to', true );
				}
			}

			$r['page'] = 1;

			// Parse arguments.
			$walk_arg = bbp_parse_args(
				array(),
				array(
					'walker'       => '',
					'max_depth'    => bbp_thread_replies_depth(),
					'callback'     => null,
					'end_callback' => null,
					'page'         => $r['page'],
					'per_page'     => $replies_per_page,
				),
				'list_replies'
			);

			global $buddyboss_thread_reply;
			$buddyboss_thread_reply = array();

			$this->bbb_walker_reply->paged_walk( $replies_query->posts, $walk_arg['max_depth'], $walk_arg['page'], $walk_arg['per_page'], $walk_arg );
			$total_parent_replies       = $replies_query->found_posts;
			$replies_query->posts       = $buddyboss_thread_reply;
			$replies_query->found_posts = $total_parent_replies;
		}

		$replies = ( ! empty( $replies_query->posts ) ? $replies_query->posts : array() );

		$retval = array();
		foreach ( $replies as $reply ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $reply, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $replies_query->found_posts, $replies_per_page );

		/**
		 * Fires after a list of replies is fetched via the REST API.
		 *
		 * @param array            $replies  Fetched Replied.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_replies_get_items', $replies, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list replies.
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
		 * Filter the replies `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_replies_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single reply.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/reply/:id Reply
	 * @apiName        GetBBPReply
	 * @apiGroup       Forum Replies
	 * @apiDescription Retrieve a single reply.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the reply.
	 */
	public function get_item( $request ) {

		$reply = bbp_get_reply( $request['id'] );

		if ( bbp_thread_replies() ) {

			$reply_args = array(
				'post_type'      => bbp_get_reply_post_type(),
				'post_parent'    => $reply->post_parent,
				'paged'          => 1,
				'posts_per_page' => - 1,
			);

			// get all post status for moderator and admin.
			if ( bbp_get_view_all() ) {

				// Default view=all statuses.
				$post_statuses = array(
					bbp_get_public_status_id(),
					bbp_get_closed_status_id(),
					bbp_get_spam_status_id(),
					bbp_get_trash_status_id(),
				);

				// Add support for private status.
				if ( current_user_can( 'read_private_replies' ) ) {
					$post_statuses[] = bbp_get_private_status_id();
				}

				// Join post statuses together.
				$reply_args['post_status'] = implode( ',', $post_statuses );
			} else {
				$reply_args['perm'] = 'readable';
			}

			$reply_result = new WP_Query( $reply_args );

			if ( ! empty( $reply_result->posts ) ) {
				foreach ( $reply_result->posts as $k => $v ) {
					$reply_result->posts[ $k ]->reply_to = (int) get_post_meta( $v->ID, '_bbp_reply_to', true );
				}
			}

			// Parse arguments.
			$walk_arg = bbp_parse_args(
				array(),
				array(
					'walker'       => '',
					'max_depth'    => bbp_thread_replies_depth(),
					'callback'     => null,
					'end_callback' => null,
					'page'         => 1,
					'per_page'     => -1,
				),
				'list_replies'
			);

			global $buddyboss_thread_reply;
			$buddyboss_thread_reply = array();

			$this->bbb_walker_reply->paged_walk( $reply_result->posts, $walk_arg['max_depth'], $walk_arg['page'], $walk_arg['per_page'], $walk_arg );

			if ( isset( $buddyboss_thread_reply[ $reply->ID ] ) ) {
				$reply->depth = $buddyboss_thread_reply[ $reply->ID ]->depth;
			}
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $reply, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of reply is fetched via the REST API.
		 *
		 * @param array            $reply    Fetched reply..
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_reply_get_item', $reply, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list reply.
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

		$reply = bbp_get_reply( $request->get_param( 'id' ) );

		if ( true === $retval && empty( $reply->ID ) ) {
			$retval = new WP_Error(
				'bp_rest_reply_invalid_id',
				__( 'Invalid reply ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ( ! isset( $reply->post_type ) || 'reply' !== $reply->post_type ) ) {
			$retval = new WP_Error(
				'bp_rest_reply_invalid_id',
				__( 'Invalid reply ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && is_user_logged_in() && isset( $reply->post_type ) ) {
			$post_type = get_post_type_object( $reply->post_type );
			if ( ! current_user_can( $post_type->cap->read_post, $reply->ID ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to access this reply.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the reply `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a reply.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/reply Create Reply
	 * @apiName        CreateBBPReply
	 * @apiGroup       Forum Replies
	 * @apiDescription Create a reply.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} [title] The title of the reply.
	 * @apiParam {String} content The content of the reply.
	 * @apiParam {Number} topic_id ID of the topic to perform the reply on it.
	 * @apiParam {Number} [reply_to] Parent Reply ID for reply.
	 * @apiParam {Number} [forum_id] Forum ID to reply on.
	 * @apiParam {String} [tags] Tags to add into the topic with comma separated.
	 * @apiParam {Boolean} [subscribe] Whether user subscribe topic or not.
	 * @apiParam {Array} [bbp_media] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [bbp_media_gif] Save gif data into reply when Media component is enable. param(url,mp4)
	 */
	public function create_item( $request ) {

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

		if ( ! empty( $request ) ) {
			foreach ( $post_map as $key => $val ) {
				if ( isset( $request[ $val ] ) ) {
					$_POST[ $key ] = $request[ $val ];
				}
			}
		}

		$_POST['action'] = 'bbp-new-reply'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$reply = $this->prepare_reply_for_database( $request );

		// Define local variable(s).
		$forum_id       = 0;
		$reply_author   = 0;
		$reply_to       = 0;
		$reply_title    = '';
		$reply_content  = '';
		$anonymous_data = array();

		/** Reply Author */
		// User is anonymous.
		if ( bbp_is_anonymous() ) {

			$anonymous_args = array(
				'bbp_anonymous_name'    => ! empty( $request['anonymous_name'] ) ? sanitize_text_field( $request['anonymous_name'] ) : '',
				'bbp_anonymous_email'   => ! empty( $request['anonymous_email'] ) ? sanitize_email( $request['anonymous_email'] ) : '',
				'bbp_anonymous_website' => ! empty( $request['anonymous_website'] ) ? sanitize_text_field( $request['anonymous_website'] ) : '',
			);

			// Filter anonymous data (variable is used later).
			$anonymous_data = bbp_filter_anonymous_post_data( $anonymous_args );

			// Anonymous data checks out, so set cookies, etc...
			if ( ! empty( $anonymous_data ) && is_array( $anonymous_data ) ) {
				bbp_set_current_anonymous_user_data( $anonymous_data );
			}

			// User is logged in.
		} else {

			// User cannot create replies.
			if ( ! current_user_can( 'publish_replies' ) ) {
				return new WP_Error(
					'bp_rest_bbp_reply_permission',
					__( 'Sorry, You do not have permission to reply.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			// Reply author is current user.
			$reply_author = bbp_get_current_user_id();
		}

		/** Topic ID */
		// Topic id was not passed.
		if ( empty( $reply->bbp_topic_id ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_topic_id',
				__( 'Sorry, Discussion ID is missing.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);

			// Topic id is not a number.
		} elseif ( ! is_numeric( $reply->bbp_topic_id ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_topic_id',
				__( 'Sorry, Discussion ID must be a number.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
			// Topic id might be valid.
		} else {
			// Get the topic id.
			$posted_topic_id = intval( $reply->bbp_topic_id );

			// Topic id is a negative number.
			if ( 0 > $posted_topic_id ) {
				return new WP_Error(
					'bp_rest_bbp_reply_topic_id',
					__( 'Sorry, Discussion ID cannot be a negative number.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Topic does not exist.
			} elseif ( ! bbp_get_topic( $posted_topic_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_reply_topic_id',
					__( 'Sorry, Discussion does not exist.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Use the POST'ed topic id.
			} else {
				$topic_id = $posted_topic_id;
			}
		}

		/** Forum ID */
		// Try to use the forum id of the topic.
		if ( ! isset( $reply->bbp_forum_id ) && ! empty( $topic_id ) ) {
			$forum_id = bbp_get_topic_forum_id( $topic_id );

			// Error check the POST'ed forum id.
		} elseif ( isset( $reply->bbp_forum_id ) ) {

			// Empty Forum id was passed.
			if ( empty( $reply->bbp_forum_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_reply_forum_id',
					__( 'Sorry, Forum ID is missing.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Forum id is not a number.
			} elseif ( ! is_numeric( $reply->bbp_forum_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_reply_forum_id',
					__( 'Sorry, Forum ID must be a number.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Forum id might be valid.
			} else {

				// Get the forum id.
				$posted_forum_id = intval( $reply->bbp_forum_id );

				// Forum id is empty.
				if ( 0 === $posted_forum_id ) {
					return new WP_Error(
						'bp_rest_bbp_topic_forum_id',
						__( 'Sorry, Forum ID is missing.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);

					// Forum id is a negative number.
				} elseif ( 0 > $posted_forum_id ) {
					return new WP_Error(
						'bp_rest_bbp_topic_forum_id',
						__( 'Sorry, Forum ID cannot be a negative number.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);

					// Forum does not exist.
				} elseif ( ! bbp_get_forum( $posted_forum_id ) ) {
					return new WP_Error(
						'bp_rest_bbp_topic_forum_id',
						__( 'Sorry, Forum does not exist.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);

					// Use the POST'ed forum id.
				} else {
					$forum_id = $posted_forum_id;
				}
			}
		}

		// Forum exists.
		if ( ! empty( $forum_id ) ) {

			// Forum is a category.
			if ( bbp_is_forum_category( $forum_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_new_reply_forum_category',
					__( 'This forum is a category. No replies can be created in this forum.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Forum is not a category.
			} else {

				// Forum is closed and user cannot access.
				if ( bbp_is_forum_closed( $forum_id ) && ! current_user_can( 'edit_forum', $forum_id ) ) {
					return new WP_Error(
						'bp_rest_bbp_new_reply_forum_closed',
						__( 'This forum has been closed to new replies.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}

				/**
				 * Added logic for group forum
				 * Current user is part of that group or not.
				 * We need to check manually because bbpress updating that caps only on group forum page and
				 * in API those conditional tag will not work.
				 */
				$group_ids = bbp_get_forum_group_ids( $forum_id );
				if ( ! empty( $group_ids ) ) {
					$is_member = false;
					foreach ( $group_ids as $group_id ) {
						if ( groups_is_user_member( get_current_user_id(), $group_id ) ) {
							$is_member = true;
							break;
						}
					}
				}

				// Forum is private and user cannot access.
				if ( bbp_is_forum_private( $forum_id ) ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_private_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_new_reply_forum_private',
							__( 'This forum is private and you do not have the capability to read or create new replies in it.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}

					// Forum is hidden and user cannot access.
				} elseif ( bbp_is_forum_hidden( $forum_id ) ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_hidden_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_new_reply_forum_hidden',
							__( 'This forum is hidden and you do not have the capability to read or create new replies in it.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}
				}
			}
		}

		/** Unfiltered HTML */
		// Remove kses filters from title and content for capable users and if the nonce is verified.
		remove_filter( 'bbp_new_reply_pre_title', 'wp_filter_kses' );
		remove_filter( 'bbp_new_reply_pre_content', 'bbp_encode_bad', 10 );

		/** Reply Title */
		if ( ! empty( $reply->bbp_reply_title ) ) {
			$reply_title = esc_attr( wp_strip_all_tags( $reply->bbp_reply_title ) );
		}

		// Filter and sanitize.
		$reply_title = apply_filters( 'bbp_new_reply_pre_title', $reply_title );

		/** Reply Content */
		if ( ! empty( $reply->bbp_reply_content ) ) {
			$reply_content = $reply->bbp_reply_content;
		}

		// Filter and sanitize.
		$reply_content = apply_filters( 'bbp_new_reply_pre_content', $reply_content );

		// No reply content.
		if (
			empty( $reply_content )
			&& ! (
				! empty( $request['bbp_media'] ) ||
				! empty( $request['bbp_documents'] ) ||
				(
					! empty( $request['bbp_media_gif']['url'] ) &&
					! empty( $request['bbp_media_gif']['mp4'] )
				) ||
				(
					function_exists( 'bp_is_forums_video_support_enabled' )
					&& false !== bp_is_forums_video_support_enabled()
					&& ! empty( $request['bbp_videos'] )
				) ||
				(
					function_exists( 'bbp_use_autoembed' )
					&& false !== bbp_use_autoembed()
					&& ! empty( $request['link_url'] )
				)
			)
		) {
			return new WP_Error(
				'bp_rest_bbp_reply_content',
				__( 'Sorry, Your reply cannot be empty.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( empty( $forum_id ) && ! empty( $topic_id ) ) {
			$forum_id = bbp_get_topic_forum_id( $topic_id );
		}

		$reply_forum = ! empty( $forum_id ) ? $forum_id : 0;
		if ( ! empty( $request['bbp_media'] ) && function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), $reply_forum, 0, 'forum' );
			if ( ! $can_send_media ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the media.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_documents'] ) && function_exists( 'bb_user_has_access_upload_document' ) ) {
			$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), $reply_forum, 0, 'forum' );
			if ( ! $can_send_document ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the document.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_videos'] ) && function_exists( 'bb_user_has_access_upload_video' ) ) {
			$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), $reply_forum, 0, 'forum' );
			if ( ! $can_send_video ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the video.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_media_gif'] ) && function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), $reply_forum, 0, 'forum' );
			if ( ! $can_send_gif ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the gif.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		// Filter and sanitize.
		$reply_content = apply_filters( 'bbp_new_reply_pre_content', $reply_content );

		/** Reply Flooding */
		if ( ! bbp_check_for_flood( $anonymous_data, $reply_author ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_flood',
				__( 'Slow down; you move too fast.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Reply Duplicate */
		if ( ! bbp_check_for_duplicate(
			array(
				'post_type'      => bbp_get_reply_post_type(),
				'post_author'    => $reply_author,
				'post_content'   => $reply_content,
				'post_parent'    => $topic_id,
				'anonymous_data' => $anonymous_data,
			)
		) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_duplicate',
				__( 'Duplicate reply detected; it looks as though you\'ve already said that!', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Reply Blacklist */
		if ( ! bbp_check_for_blacklist( $anonymous_data, $reply_author, $reply_title, $reply_content ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_blacklist',
				__( 'Sorry, Your reply cannot be created at this time.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Reply Status */
		// Maybe put into moderation.
		if ( ! bbp_check_for_moderation( $anonymous_data, $reply_author, $reply_title, $reply_content ) ) {
			$reply_status = bbp_get_pending_status_id();

			// Default.
		} else {
			$reply_status = bbp_get_public_status_id();
		}

		/** Reply To */
		// Handle Reply To of the reply; $_REQUEST for non-JS submissions.
		if ( isset( $reply->bbp_reply_to ) ) {
			$reply_to = bbp_validate_reply_to( $reply->bbp_reply_to );
		}

		/** Topic Closed */
		// If topic is closed, moderators can still reply.
		if ( bbp_is_topic_closed( $topic_id ) && ! current_user_can( 'moderate' ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_topic_closed',
				__( 'Sorry, Discussion is closed.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Tags */
		// Either replace terms.
		if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) && ! empty( $reply->bbp_topic_tags ) ) {
			$terms = esc_attr( wp_strip_all_tags( $reply->bbp_topic_tags ) );

			// ...or remove them.
		} elseif ( isset( $reply->bbp_topic_tags ) ) {
			$terms = '';

			// Existing terms.
		} else {
			$terms = bbp_get_topic_tag_names( $topic_id );
		}

		/** Additional Actions (Before Save) */
		do_action( 'bbp_new_reply_pre_extras', $topic_id, $forum_id );

		// Bail if errors.
		if ( bbp_has_errors() ) {
			return;
		}

		/** No Errors */

		// Add the content of the form to $reply_data as an array.
		// Just in time manipulation of reply data before being created.
		$reply_data = apply_filters(
			'bbp_new_reply_pre_insert',
			array(
				'post_author'    => $reply_author,
				'post_title'     => $reply_title,
				'post_content'   => $reply_content,
				'post_status'    => $reply_status,
				'post_parent'    => $topic_id,
				'post_type'      => bbp_get_reply_post_type(),
				'comment_status' => 'closed',
				'menu_order'     => bbp_get_topic_reply_count( $topic_id, false ) + 1,
			)
		);

		// Insert reply.
		$reply_id = wp_insert_post( $reply_data );

		if ( empty( $reply_id ) || is_wp_error( $reply_id ) ) {
			$append_error = (
				( is_wp_error( $reply_id ) && $reply_id->get_error_message() )
				? __( 'The following problem(s) have been found with your reply: ', 'buddyboss' ) . $reply_id->get_error_message()
				: __( 'We are facing a problem to creating a reply.', 'buddyboss' )
			);

			return new WP_Error(
				'bp_rest_bbp_reply_error',
				$append_error,
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Tags */
		// Just in time manipulation of reply terms before being edited.
		$terms = apply_filters( 'bbp_new_reply_pre_set_terms', $terms, $topic_id, $reply_id );

		// Insert terms.
		if ( function_exists( 'bb_add_topic_tags' ) ) {
			if ( ! is_array( $terms ) && strstr( $terms, ',' ) ) {
				$terms = explode( ',', $terms );
			} else {
				$terms = (array) $terms;
			}
			$terms = bb_add_topic_tags( $terms, $topic_id, bbp_get_topic_tag_tax_id() );
		}

		// Term error.
		if ( is_wp_error( $terms ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_tags',
				__( 'There was a problem adding the tags to the topic.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Trash Check */
		// If this reply starts as trash, add it to pre_trashed_replies.
		// for the topic, so it is properly restored.
		if ( bbp_is_topic_trash( $topic_id ) || ( bbp_get_trash_status_id() === $reply_data['post_status'] ) ) {

			// Trash the reply.
			wp_trash_post( $reply_id );

			// Only add to pre-trashed array if topic is trashed.
			if ( bbp_is_topic_trash( $topic_id ) ) {

				// Get pre_trashed_replies for topic.
				$pre_trashed_replies = (array) get_post_meta( $topic_id, '_bbp_pre_trashed_replies', true );

				// Add this reply to the end of the existing replies.
				$pre_trashed_replies[] = $reply_id;

				// Update the pre_trashed_reply post meta.
				update_post_meta( $topic_id, '_bbp_pre_trashed_replies', $pre_trashed_replies );
			}

			/** Spam Check */
			// If reply or topic are spam, officially spam this reply.
		} elseif ( bbp_is_topic_spam( $topic_id ) || ( bbp_get_spam_status_id() === $reply_data['post_status'] ) ) {
			add_post_meta( $reply_id, '_bbp_spam_meta_status', bbp_get_public_status_id() );

			// Only add to pre-spammed array if topic is spam.
			if ( bbp_is_topic_spam( $topic_id ) ) {

				// Get pre_spammed_replies for topic.
				$pre_spammed_replies = (array) get_post_meta( $topic_id, '_bbp_pre_spammed_replies', true );

				// Add this reply to the end of the existing replies.
				$pre_spammed_replies[] = $reply_id;

				// Update the pre_spammed_replies post meta.
				update_post_meta( $topic_id, '_bbp_pre_spammed_replies', $pre_spammed_replies );
			}
		}

		/**
		 * Removed notification sent and called additionally.
		 * Due to we have moved all filters on title and content.
		 */
		remove_action( 'bbp_new_reply', 'bbp_notify_topic_subscribers', 9999, 5 );
		remove_action( 'bbp_new_reply', 'bbp_buddypress_add_notification', 9999, 7 );

		/** Update counts, etc... */
		do_action( 'bbp_new_reply', $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author, false, $reply_to );

		if ( ! empty( $topic_id ) && 0 === $reply_to && function_exists( 'bbp_update_total_parent_reply' ) ) {
			// Update total parent.
			bbp_update_total_parent_reply( $reply_id, $topic_id, bbp_get_topic_reply_count( $topic_id, false ) + 1, 'add' );
		}

		/** Additional Actions (After Save) */
		do_action( 'bbp_new_reply_post_extras', $reply_id );

		$reply         = bbp_get_reply( $reply_id );
		$fields_update = $this->update_additional_fields_for_object( $reply, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		if ( function_exists( 'bbp_buddypress_add_notification' ) ) {
			bbp_buddypress_add_notification( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author, false, $reply_to );
		}

		/**
		 * Fires after a reply is created via the REST API.
		 *
		 * @param array           $reply    Created reply.
		 * @param array           $topic_id Reply's topic ID.
		 * @param array           $forum_id Reply's form ID.
		 * @param WP_REST_Request $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_reply_create_item', $reply, $topic_id, $forum_id, $request );

		$object = new WP_REST_Request();
		$object->set_param( 'id', $reply_id );
		$object->set_param( 'context', 'view' );

		$response = $this->get_item( $object );

		if ( function_exists( 'bbp_notify_topic_subscribers' ) ) {
			/**
			 * Sends notification emails for new replies to subscribed topics.
			 */
			bbp_notify_topic_subscribers( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author );
		}

		return $response;
	}

	/**
	 * Check if a given request has access to create a reply.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create a reply.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() || bbp_allow_anonymous() ) {
			$retval = true;
		}

		/**
		 * Filter the reply `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update/Edit a reply.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/reply/:id Update Reply
	 * @apiName        UpdateBBPReply
	 * @apiGroup       Forum Replies
	 * @apiDescription Update a reply.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the reply.
	 * @apiParam {String} [title] The title of the reply.
	 * @apiParam {String} content The content of the reply.
	 * @apiParam {Number} topic_id ID of the topic to perform the reply on it.
	 * @apiParam {Number} [reply_to] Parent Reply ID for reply.
	 * @apiParam {Number} [forum_id] Forum ID to reply on.
	 * @apiParam {String} [tags] Tags to add into the topic with comma separated.
	 * @apiParam {Boolean} [subscribe] Whether user subscribe topic or not.
	 * @apiParam {String} [reason] Reason for editing a reply.
	 * @apiParam {Boolean} [log] Keep a log of reply edit.
	 * @apiParam {Array} [bbp_media] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [bbp_media_gif] Save gif data into reply when Media component is enable. param(url,mp4)
	 */
	public function update_item( $request ) {
		$reply_new = $this->prepare_reply_for_database( $request );

		// Define local variable(s).
		$revisions_removed = false;
		$reply             = 0;
		$reply_id          = 0;
		$reply_to          = 0;
		$reply_author      = 0;
		$reply_title       = '';
		$reply_content     = '';
		$reply_edit_reason = '';
		$anonymous_data    = array();

		/** Reply */
		// Reply id was not passed.
		if ( empty( $reply_new->bbp_reply_id ) ) {
			return new WP_Error(
				'bp_rest_bbp_edit_reply_id',
				__( 'Reply ID not found.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);

			// Reply id was passed.
		} elseif ( is_numeric( $reply_new->bbp_reply_id ) ) {
			$reply_id = (int) $reply_new->bbp_reply_id;
			$reply    = bbp_get_reply( $reply_id );
		}

		// Reply does not exist.
		if ( empty( $reply ) ) {
			return new WP_Error(
				'bp_rest_bbp_edit_reply_not_found',
				__( 'The reply you want to edit was not found.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);

			// Reply exists.
		} else {

			// Check users ability to create new reply.
			if ( ! bbp_is_reply_anonymous( $reply_id ) ) {

				// User cannot edit this reply.
				if ( ! current_user_can( 'edit_reply', $reply_id ) ) {
					return new WP_Error(
						'bp_rest_bbp_edit_reply_permissions',
						__( 'You do not have permission to edit that reply.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}

				// Set reply author.
				$reply_author = bbp_get_reply_author_id( $reply_id );

				// It is an anonymous post.
			} else {

				$anonymous_args = array(
					'bbp_anonymous_name'    => ! empty( $request['anonymous_name'] ) ? sanitize_text_field( $request['anonymous_name'] ) : '',
					'bbp_anonymous_email'   => ! empty( $request['anonymous_email'] ) ? sanitize_email( $request['anonymous_email'] ) : '',
					'bbp_anonymous_website' => ! empty( $request['anonymous_website'] ) ? sanitize_text_field( $request['anonymous_website'] ) : '',
				);

				// Filter anonymous data.
				$anonymous_data = bbp_filter_anonymous_post_data( $anonymous_args );
			}
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

		$_POST['action'] = 'bbp-edit-reply'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Remove kses filters from title and content for capable users.
		remove_filter( 'bbp_new_reply_pre_title', 'wp_filter_kses' );
		remove_filter( 'bbp_new_reply_pre_content', 'bbp_encode_bad', 10 );

		/** Reply Topic */
		$topic_id = bbp_get_reply_topic_id( $reply_id );

		/** Topic Forum */
		$forum_id = bbp_get_topic_forum_id( $topic_id );

		// Forum exists.
		if ( ! empty( $forum_id ) && ( bbp_get_reply_forum_id( $reply_id ) !== $forum_id ) ) {

			// Forum is a category.
			if ( bbp_is_forum_category( $forum_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_edit_reply_forum_category',
					__( 'This forum is a category. No replies can be created in this forum.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Forum is not a category.
			} else {

				// Forum is closed and user cannot access.
				if ( bbp_is_forum_closed( $forum_id ) && ! current_user_can( 'edit_forum', $forum_id ) ) {
					return new WP_Error(
						'bp_rest_bbp_edit_reply_forum_closed',
						__( 'This forum has been closed to new replies.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}

				/**
				 * Added logic for group forum
				 * Current user is part of that group or not.
				 * We need to check manually because bbpress updating that caps only on group forum page and
				 * in API those conditional tag will not work.
				 */
				$group_ids = bbp_get_forum_group_ids( $forum_id );
				if ( ! empty( $group_ids ) ) {
					$is_member = false;
					foreach ( $group_ids as $group_id ) {
						if ( groups_is_user_member( get_current_user_id(), $group_id ) ) {
							$is_member = true;
							break;
						}
					}
				}

				// Forum is private and user cannot access.
				if ( bbp_is_forum_private( $forum_id ) ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_private_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_edit_reply_forum_private',
							__( 'This forum is private and you do not have the capability to read or create new replies in it.', 'buddyboss' ),
							array(
								'status' => rest_authorization_required_code(),
							)
						);
					}

					// Forum is hidden and user cannot access.
				} elseif ( bbp_is_forum_hidden( $forum_id ) ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_hidden_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_edit_reply_forum_hidden',
							__( 'This forum is hidden and you do not have the capability to read or create new replies in it.', 'buddyboss' ),
							array(
								'status' => rest_authorization_required_code(),
							)
						);
					}
				}
			}
		}

		/** Reply Title */
		if ( ! empty( $reply_new->bbp_reply_title ) ) {
			$reply_title = esc_attr( wp_strip_all_tags( $reply_new->bbp_reply_title ) );
		}

		// Filter and sanitize.
		$reply_title = apply_filters( 'bbp_edit_reply_pre_title', $reply_title, $reply_id );

		/** Reply Content */
		if ( ! empty( $reply_new->bbp_reply_content ) ) {
			$reply_content = $reply_new->bbp_reply_content;
		}

		// Filter and sanitize.
		$reply_content = apply_filters( 'bbp_edit_reply_pre_content', $reply_content, $reply_id );

		// No reply content.
		if (
			empty( $reply_content )
			&& ! (
				! empty( $request['bbp_media'] ) ||
				! empty( $request['bbp_documents'] ) ||
				(
					! empty( $request['bbp_media_gif']['url'] ) &&
					! empty( $request['bbp_media_gif']['mp4'] )
				) || (
					function_exists( 'bp_is_forums_video_support_enabled' )
					&& false !== bp_is_forums_video_support_enabled()
					&& ! empty( $request['bbp_videos'] )
				) ||
				(
					function_exists( 'bbp_use_autoembed' )
					&& false !== bbp_use_autoembed()
					&& ! empty( $request['link_url'] )
				)
			)
		) {
			return new WP_Error(
				'bp_rest_bbp_edit_reply_content',
				__( 'Your reply cannot be empty.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( empty( $forum_id ) && ! empty( $topic_id ) ) {
			$forum_id = bbp_get_topic_forum_id( $topic_id );
		}

		$reply_forum = ! empty( $forum_id ) ? $forum_id : 0;
		if ( ! empty( $request['bbp_media'] ) && function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), $reply_forum, 0 );
			if ( ! $can_send_media ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the media.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_documents'] ) && function_exists( 'bb_user_has_access_upload_document' ) ) {
			$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), $reply_forum, 0 );
			if ( ! $can_send_document ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the document.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_videos'] ) && function_exists( 'bb_user_has_access_upload_video' ) ) {
			$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), $reply_forum, 0 );
			if ( ! $can_send_video ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the video.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_media_gif'] ) && function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), $reply_forum, 0 );
			if ( ! $can_send_gif ) {
				return new WP_Error(
					'bp_rest_bbp_reply_media',
					__( 'You don\'t have access to send the gif.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		/** Reply Blacklist */
		if ( ! bbp_check_for_blacklist( $anonymous_data, $reply_author, $reply_title, $reply_content ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_blacklist',
				__( 'Sorry, Your reply cannot be edited at this time.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Reply Status */
		// Maybe put into moderation.
		if ( ! bbp_check_for_moderation( $anonymous_data, $reply_author, $reply_title, $reply_content ) ) {

			// Set post status to pending if public.
			if ( bbp_get_public_status_id() === $reply->post_status ) {
				$reply_status = bbp_get_pending_status_id();
			}

			// Use existing post_status.
		} else {
			$reply_status = $reply->post_status;
		}

		/** Reply To */
		// Handle Reply To of the reply; $_REQUEST for non-JS submissions.
		if ( isset( $reply_new->bbp_reply_to ) ) {
			$reply_to = bbp_validate_reply_to( $reply_new->bbp_reply_to );
		}

		/** Topic Tags */
		// Either replace terms.
		if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) && ! empty( $reply_new->bbp_topic_tags ) ) {
			$terms = esc_attr( wp_strip_all_tags( $reply_new->bbp_topic_tags ) );

			// ...or remove them.
		} elseif ( isset( $reply_new->bbp_topic_tags ) ) {
			$terms = '';

			// Existing terms.
		} else {
			$terms = bbp_get_topic_tag_names( $topic_id );
		}

		/** Additional Actions (Before Save) */
		do_action( 'bbp_edit_reply_pre_extras', $reply_id );

		/** No Errors */
		// Add the content of the form to $reply_data as an array.
		// Just in time manipulation of reply data before being edited.
		$reply_data = apply_filters(
			'bbp_edit_reply_pre_insert',
			array(
				'ID'           => $reply_id,
				'post_title'   => $reply_title,
				'post_content' => $reply_content,
				'post_status'  => $reply_status,
				'post_parent'  => $topic_id,
				'post_author'  => $reply_author,
				'post_type'    => bbp_get_reply_post_type(),
			)
		);

		// Toggle revisions to avoid duplicates.
		if ( post_type_supports( bbp_get_reply_post_type(), 'revisions' ) ) {
			$revisions_removed = true;
			remove_post_type_support( bbp_get_reply_post_type(), 'revisions' );
		}

		if ( function_exists( 'bp_media_forums_new_post_media_save' ) ) {
			remove_action( 'edit_post', 'bp_media_forums_new_post_media_save', 999 );
		}

		if ( function_exists( 'bp_document_forums_new_post_document_save' ) ) {
			remove_action( 'edit_post', 'bp_document_forums_new_post_document_save', 999 );
		}

		// Insert topic.
		$reply_id = wp_update_post( $reply_data );

		if ( function_exists( 'bp_media_forums_new_post_media_save' ) ) {
			add_action( 'edit_post', 'bp_media_forums_new_post_media_save', 999 );
		}

		if ( function_exists( 'bp_document_forums_new_post_document_save' ) ) {
			add_action( 'edit_post', 'bp_document_forums_new_post_document_save', 999 );
		}

		// Toggle revisions back on.
		if ( true === $revisions_removed ) {
			$revisions_removed = false;
			add_post_type_support( bbp_get_reply_post_type(), 'revisions' );
		}

		/** Topic Tags */
		// Just in time manipulation of reply terms before being edited.
		$terms = apply_filters( 'bbp_edit_reply_pre_set_terms', $terms, $topic_id, $reply_id );

		// Insert terms.
		if ( function_exists( 'bb_add_topic_tags' ) ) {
			if ( ! is_array( $terms ) && strstr( $terms, ',' ) ) {
				$terms = explode( ',', $terms );
			} else {
				$terms = (array) $terms;
			}
			$terms = bb_add_topic_tags( $terms, $topic_id, bbp_get_topic_tag_tax_id(), bbp_get_topic_tag_names( $topic_id ) );
		}

		// Term error.
		if ( is_wp_error( $terms ) ) {
			return new WP_Error(
				'bp_rest_bbp_reply_tags',
				__( 'There was a problem adding the tags to the topic.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( empty( $reply_id ) || is_wp_error( $reply_id ) ) {
			$append_error = (
				( is_wp_error( $reply_id ) && $reply_id->get_error_message() )
				? __( 'The following problem(s) have been found with your reply: ', 'buddyboss' ) . $reply_id->get_error_message() . __( 'Please try again.', 'buddyboss' )
				: __( 'We are facing a problem to updating a reply.', 'buddyboss' )
			);

			return new WP_Error(
				'bp_rest_bbp_reply_error',
				$append_error,
				array(
					'status' => 400,
				)
			);
		}

		// Update counts, etc...
		do_action( 'bbp_edit_reply', $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author, true, $reply_to );

		/** Revisions */
		// Update locks.
		update_post_meta( $reply_id, '_edit_last', bbp_get_current_user_id() );
		delete_post_meta( $reply_id, '_edit_lock' );

		// Revision Reason.
		if ( ! empty( $reply_new->bbp_reply_edit_reason ) ) {
			$reply_edit_reason = esc_attr( wp_strip_all_tags( $reply_new->bbp_reply_edit_reason ) );
		}

		// Update revision log.
		if ( ! empty( $reply_new->bbp_log_reply_edit ) && ( true === $reply_new->bbp_log_reply_edit ) ) {
			$revision_id = wp_save_post_revision( $reply_id );
			if ( ! empty( $revision_id ) ) {
				bbp_update_reply_revision_log(
					array(
						'reply_id'    => $reply_id,
						'revision_id' => $revision_id,
						'author_id'   => bbp_get_current_user_id(),
						'reason'      => $reply_edit_reason,
					)
				);
			}
		}

		/** Additional Actions (After Save) */
		do_action( 'bbp_edit_reply_post_extras', $reply_id );

		$reply         = bbp_get_reply( $reply_id );
		$reply->edit   = true;
		$fields_update = $this->update_additional_fields_for_object( $reply, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		/**
		 * Fires after a reply is edited via the REST API.
		 *
		 * @param array           $reply   Edited Reply.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_reply_update_item', $reply, $request );

		$object = new WP_REST_Request();
		$object->set_param( 'id', $reply_id );
		$object->set_param( 'context', 'view' );

		return $this->get_item( $object );

	}

	/**
	 * Check if a given request has access to update a reply.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create a reply.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() || bbp_allow_anonymous() ) {
			$retval = $this->get_item_permissions_check( $request );
			$reply  = bbp_get_reply( $request->get_param( 'id' ) );
			if ( bbp_get_user_id( 0, true, true ) !== $reply->post_author && ! current_user_can( 'edit_reply', $request['id'] ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update this reply.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the reply `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a reply.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/reply/:id Trash/Delete Reply
	 * @apiName        DeleteBBPReply
	 * @apiGroup       Forum Replies
	 * @apiDescription Trash OR Delete a Reply.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the reply.
	 */
	public function delete_item( $request ) {

		$reply    = bbp_get_reply( $request['id'] );
		$topic_id = bbp_get_reply_topic_id( $reply->ID );

		$previous = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $reply, $request )
		);

		$success = wp_delete_post( $reply->ID );
		if ( false !== $success && ! is_wp_error( $success ) && function_exists( 'bbp_update_total_parent_reply' ) ) {
			// Update total parent reply count when any parent trashed.
			bbp_update_total_parent_reply( $reply->ID, $topic_id, '', 'update' );
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => ( ! empty( $success ) && ! is_wp_error( $success ) ? true : $success ),
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a reply is deleted via the REST API.
		 *
		 * @param array            $reply    Fetched reply.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_reply_delete_item', $reply, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a reply.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = $this->get_item_permissions_check( $request );

			if ( true === $retval && ! current_user_can( 'delete_reply', $request->get_param( 'id' ) ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this reply.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the reply `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Edit some arguments for the endpoint's CREATABLE, EDITABLE and DELETABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'create_item';

		if ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';

			$args = array(
				'id' => array(
					'description' => __( 'A unique numeric ID for the reply.', 'buddyboss' ),
					'type'        => 'integer',
					'required'    => true,
				),
			);
		}

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$unset_keys = array(
				'date',
				'date_gmt',
				'password',
				'slug',
				'status',
				'link',
				'author',
				'parent',
				'depth',
				'reply_to',
				'is_reply_anonymous',
				'anonymous_author_data',
				'classes',
				'current_user_permissions',
				'action_states',
				'revisions',
			);

			if ( ! empty( $unset_keys ) ) {
				foreach ( $unset_keys as $k ) {
					if ( array_key_exists( $k, $args ) ) {
						unset( $args[ $k ] );
					}
				}
			}

			$args['title']['type']       = 'string';
			$args['content']['type']     = 'string';
			$args['content']['required'] = true;

			$args['reply_to'] = array(
				'description'       => __( 'Parent Reply ID for reply.', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['topic_id'] = array(
				'description'       => __( 'ID of the topic to perform the reply on it.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['forum_id'] = array(
				'description'       => __( 'Forum ID to reply on.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => false,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['tags'] = array(
				'description'       => __( 'Tags to add into the topic.', 'buddyboss' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['subscribe'] = array(
				'description'       => __( 'Whether user subscribe topic or not.', 'buddyboss' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			$args['reason'] = array(
				'description'       => __( 'Reason for editing a reply.', 'buddyboss' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['log'] = array(
				'description'       => __( 'Keep a log of reply edit.', 'buddyboss' ),
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_reply_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepares component data for return as an object.
	 *
	 * @param array           $reply   The component and its values.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $reply, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Base fields for every post.
		$data = array(
			'id'                    => $reply->ID,
			'date'                  => $this->forum_endpoint->prepare_date_response( $reply->post_date_gmt, $reply->post_date ),
			'date_gmt'              => $this->forum_endpoint->prepare_date_response( $reply->post_date_gmt ),
			'guid'                  => array(
				'rendered' => esc_url( bbp_get_reply_permalink( $reply->ID ) ),
				'raw'      => $reply->guid,
			),
			'modified'              => $this->forum_endpoint->prepare_date_response( $reply->post_modified_gmt, $reply->post_modified ),
			'modified_gmt'          => $this->forum_endpoint->prepare_date_response( $reply->post_modified_gmt ),
			'password'              => $reply->post_password,
			'slug'                  => $reply->post_name,
			'status'                => $reply->post_status,
			'link'                  => bbp_get_reply_permalink( $reply->ID ),
			'author'                => (int) $reply->post_author,
			'parent'                => (int) $reply->post_parent,
			'depth'                 => (int) ( isset( $reply->depth ) && ! empty( $reply->depth ) ) ? $reply->depth : 1,
			'reply_to'              => bbp_get_reply_to( $reply->ID ),
			'is_reply_anonymous'    => (int) bbp_is_reply_anonymous( $reply->ID ),
			'anonymous_author_data' => (
				bbp_is_reply_anonymous( $reply->ID )
				? array(
					'name'    => bbp_get_reply_author_display_name( $reply->ID ),
					'email'   => bbp_get_reply_author_email( $reply->ID ),
					'website' => bbp_get_reply_author_url( $reply->ID ),
					'avatar'  => get_avatar_url( bbp_get_reply_author_email( $reply->ID ) ),
				)
				: false
			),
			'classes'               => bbp_get_reply_class( $reply->ID ),
			'title'                 => '',
			'content'               => array(),
			'short_content'         => '',
			'preview_data'          => '',
			'link_embed_url'        => '',
		);

		$data['title'] = array(
			'raw'      => $reply->post_title,
			'rendered' => bbp_get_reply_title( $reply->ID ),
		);

		/* Prepare content */
		if ( ! empty( $reply->post_password ) ) {
			$this->forum_endpoint->prepare_password_response( $reply->post_password );
		}

		$data['short_content'] = wp_trim_excerpt( '', $reply->ID );

		remove_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_gif', 98, 2 );
		remove_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_attachments', 98, 2 );
		remove_filter( 'bbp_get_reply_content', 'bp_video_forums_embed_attachments', 98, 2 );
		remove_filter( 'bbp_get_reply_content', 'bb_forums_link_preview', 999, 2 ); // Remove link preview.
		remove_filter( 'bbp_get_reply_content', 'bbp_reply_content_autoembed_paragraph', 99999, 1 ); // Remove link embed from content.
		remove_filter( 'bbp_get_reply_content', 'bp_document_forums_embed_attachments', 999999, 2 );

		$data['content'] = array(
			'raw'      => bb_rest_raw_content( $reply->post_content ),
			'rendered' => bbp_get_reply_content( $reply->ID ),
		);

		add_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_gif', 98, 2 );
		add_filter( 'bbp_get_reply_content', 'bp_media_forums_embed_attachments', 98, 2 );
		add_filter( 'bbp_get_reply_content', 'bp_video_forums_embed_attachments', 98, 2 );
		add_filter( 'bbp_get_reply_content', 'bb_forums_link_preview', 999, 2 ); // Restore link preview.
		add_filter( 'bbp_get_reply_content', 'bbp_reply_content_autoembed_paragraph', 99999, 1 ); // Restore link embed to content
		add_filter( 'bbp_get_reply_content', 'bp_document_forums_embed_attachments', 999999, 2 );

		// Don't leave our cookie lying around: https://github.com/WP-API/WP-API/issues/1055.
		if ( ! empty( $reply->post_password ) ) {
			$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = '';
		}
		/* -- Prepare content */

		// Add iframe embedded data in separate object.
		$link_embed = get_post_meta( $reply->ID, '_link_embed', true );

		if ( ! empty( $link_embed ) ) {

			$data['link_embed_url'] = $link_embed;

			$embed_data = bp_core_parse_url( $link_embed );

			if (
				isset( $embed_data['wp_embed'] ) &&
				$embed_data['wp_embed'] &&
				! empty( $embed_data['description'] )
			) {
				$data['preview_data'] = $embed_data['description'];
				$data['preview_data'] = $this->forum_endpoint->bp_rest_forums_remove_lazyload( $data['preview_data'], $reply->ID );
			}
		} else {
			$data['preview_data'] = bb_forums_link_preview( '', $reply->ID );
		}

		$forum_id = bbp_get_reply_forum_id( $reply->ID );

		if ( ! empty( $forum_id ) ) {
			$this->forum_endpoint->group = (
				function_exists( 'bbp_is_forum_group_forum' )
				&& bbp_is_forum_group_forum( $forum_id )
				&& function_exists( 'groups_get_group' )
			)
			? (
				! empty( bbp_get_forum_group_ids( $forum_id ) )
				? groups_get_group( current( bbp_get_forum_group_ids( $forum_id ) ) )
				: ''
			)
			: '';
		}

		if ( class_exists( 'BBP_Forums_Group_Extension' ) ) {
			$group_forum_extention = new BBP_Forums_Group_Extension();
			// Allow group member to view private/hidden forums.
			add_filter( 'bbp_map_meta_caps', array( $group_forum_extention, 'map_group_forum_meta_caps' ), 10, 4 );

			// Fix issue - Group organizers and moderators can not add topic tags.
			add_filter( 'bbp_map_topic_tag_meta_caps', array( $this->forum_endpoint, 'bb_rest_map_assign_topic_tags_caps' ), 10, 4 );
		}

		add_filter( 'bbp_map_group_forum_topic_meta_caps', array( $this->forum_endpoint, 'bb_rest_map_group_forum_topic_meta_caps' ), 99, 4 );

		// current user permission.
		$data['current_user_permissions'] = $this->get_reply_current_user_permissions( $reply->ID );

		$data['action_states'] = $this->get_reply_action_states( $reply->ID );

		remove_filter( 'bbp_map_group_forum_topic_meta_caps', array( $this->forum_endpoint, 'bb_rest_map_group_forum_topic_meta_caps' ), 99, 4 );

		$this->forum_endpoint->group = '';

		// Revisions.
		$data['revisions'] = $this->get_reply_revisions( $reply->ID );

		// Pass group ids for embedded members endpoint.
		$group_ids = '';
		if ( ! empty( $args['post_parent'] ) ) {
			$group = bbp_get_forum_group_ids( bbp_get_topic_forum_id( $args['post_parent'] ) );
			if ( ! empty( $group ) ) {
				$group_ids = count( $group ) > 1 ? implode( ', ', $group ) : $group[0];
			}
		}
		$request['group_id'] = $group_ids;

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $reply, $request ) );

		/**
		 * Filter a component value returned from the API.
		 *
		 * @param WP_REST_Response $response  The Response data.
		 * @param WP_REST_Request  $request   Request used to generate the response.
		 * @param WP_Post          $component The component and its values.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_prepare_value', $response, $request, $reply );
	}

	/**
	 * Get the forums schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'reply',
			'type'       => 'object',
			'properties' => array(
				'id'                       => array(
					'description' => __( 'Unique identifier for the reply.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'date'                     => array(
					'description' => __( 'The date the object was published, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'date_gmt'                 => array(
					'description' => __( 'The date the object was published, as GMT.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'guid'                     => array(
					'description' => __( 'The url identifier for the reply.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'GUID for the reply, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'GUID for the reply, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'modified'                 => array(
					'description' => __( 'The date for reply was last modified, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt'             => array(
					'description' => __( 'The date for reply was last modified, as GMT.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'password'                 => array(
					'description' => __( 'A password to protect access to the post.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'slug'                     => array(
					'description' => __( 'An alphanumeric unique identifier for the reply.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'status'                   => array(
					'description' => __( 'The current status of the reply.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'link'                     => array(
					'description' => __( 'The permalink to this reply on the site.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'format'      => 'uri',
				),
				'author'                   => array(
					'description' => __( 'The ID for the author of the reply.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'parent'                   => array(
					'description' => __( 'ID of the parent topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'depth'                    => array(
					'description' => __( 'Depth for the reply.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'reply_to'                 => array(
					'description' => __( 'Parent reply ID.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'is_reply_anonymous'       => array(
					'description' => __( 'Whether the post is by an anonymous user or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'anonymous_author_data'    => array(
					'description' => __( 'An anonymous users data.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'properties'  => array(
						'name'    => array(
							'description' => __( 'Name of the anonymous user.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'email'   => array(
							'description' => __( 'Email address of the anonymous user.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'website' => array(
							'description' => __( 'Website of the anonymous user.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'avatar'  => array(
							'description' => __( 'Avatar url of the anonymous user.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'classes'                  => array(
					'description' => __( 'Classes lists for the reply.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'title'                    => array(
					'description' => __( 'The title of the reply.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the title of the reply, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'The title of the reply, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'content'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The content of the reply.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the reply, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the reply, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'short_content'            => array(
					'description' => __( 'Short content of the reply.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'preview_data'             => array(
					'description' => __( 'WordPress Embed and link preview data.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'link_embed_url'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'WordPress Embed URL.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'current_user_permissions' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current user\'s permission with the reply.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'edit'  => array(
							'description' => __( 'Whether the current user can edit the reply or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'move'  => array(
							'description' => __( 'Whether the current user can move the reply or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'split' => array(
							'description' => __( 'Whether the current user can spit the reply or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'spam'  => array(
							'description' => __( 'Whether the current user can spam the reply or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'trash' => array(
							'description' => __( 'Whether the current user can trash the reply or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'action_states'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Available actions with current user for reply.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'spam'  => array(
							'description' => __( 'Check whether the reply status is spam or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'trash' => array(
							'description' => __( 'Check whether the reply status is trash or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'revisions'                => array(
					'description' => __( 'Revisions for reply.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
				),
			),
		);

		/**
		 * Filters the reply schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_reply_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		$params['author'] = array(
			'description'       => __( 'Author ID, or comma-separated list of IDs.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['author_exclude'] = array(
			'description'       => __( 'An array of author IDs not to query from.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'An array of reply IDs not to retrieve.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'An array of reply IDs to retrieve.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['offset'] = array(
			'description'       => __( 'The number of reply to offset before retrieval.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Designates ascending or descending order of replies.', 'buddyboss' ),
			'default'           => 'asc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort retrieved replies by parameter.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array(
					'meta_value',
					'date',
					'ID',
					'author',
					'title',
					'modified',
					'parent',
					'rand',
					'include'
				),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['parent'] = array(
			'description'       => __( 'Topic or Reply ID to retrieve all the child replies.', 'buddyboss' ),
			'default'           => '0',
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['thread_replies'] = array(
			'description'       => __( 'Calculated value and the thread replies depth.', 'buddyboss' ),
			'default'           => bbp_thread_replies(),
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_replys_collection_params', $params );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $post, $request ) {
		$group = ! empty( $request['group_id'] ) ? '?group_id=' . $request['group_id'] : '';
		$base  = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $post->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $post->post_author ) . $group ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array   $links The prepared links of the REST response.
		 * @param WP_Post $post  Post object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_reply_prepare_links', $links, $post );
	}

	/**
	 * Get current user permission for reply.
	 *
	 * @param integer $reply_id Reply ID.
	 *
	 * @return array|void
	 */
	protected function get_reply_current_user_permissions( $reply_id ) {

		if ( empty( $reply_id ) ) {
			return;
		}

		// Get reply.
		$reply = bbp_get_reply( $reply_id );

		return array(
			'edit'  => (
				current_user_can( 'edit_others_replies' ) ||
				(
					! empty( $reply_id ) &&
					current_user_can( 'edit_reply', $reply_id ) &&
					! bbp_past_edit_lock( $reply->post_date_gmt )
				)
			),
			'move'  => ! empty( $reply ) && current_user_can( 'moderate', $reply_id ),
			'split' => ! empty( $reply ) && current_user_can( 'moderate', $reply_id ),
			'spam'  => ! empty( $reply ) && current_user_can( 'moderate', $reply_id ),
			'trash' => ! empty( $reply ) && current_user_can( 'delete_reply', $reply_id ),
		);
	}

	/**
	 * Get Action states for the reply.
	 *
	 * @param integer $reply_id Reply ID.
	 *
	 * @return array|void
	 */
	protected function get_reply_action_states( $reply_id ) {
		if ( empty( $reply_id ) ) {
			return;
		}

		return array(
			'spam'  => ( ! empty( bbp_is_reply_spam( $reply_id ) ) ? bbp_is_reply_spam( $reply_id ) : false ),
			'trash' => ( ! empty( bbp_is_reply_trash( $reply_id ) ) ? bbp_is_reply_trash( $reply_id ) : false ),
		);

	}

	/**
	 * Get revisions for reply.
	 * from: bbp_get_reply_revision_log()
	 *
	 * @param int $reply_id ID of the reply.
	 *
	 * @return bool|void
	 */
	protected function get_reply_revisions( $reply_id = 0 ) {

		// Create necessary variables.
		$reply_id = bbp_get_reply_id( $reply_id );

		// Show the topic reply log if this is a topic in a reply loop.
		if ( bbp_is_topic( $reply_id ) ) {
			return $this->topic_endpoint->get_topic_revisions( $reply_id );
		}

		// Get the reply revision log.
		$revision_log = bbp_get_reply_raw_revision_log( $reply_id );

		// Check reply and revision log exist.
		if ( empty( $reply_id ) || empty( $revision_log ) || ! is_array( $revision_log ) ) {
			return false;
		}

		// Get the actual revisions.
		$revisions = bbp_get_reply_revisions( $reply_id );
		if ( empty( $revisions ) ) {
			return false;
		}

		if ( empty( $revisions ) ) {
			return false;
		}

		$retval = array();

		// Loop through revisions.
		foreach ( (array) $revisions as $revision ) {

			if ( empty( $revision_log[ $revision->ID ] ) ) {
				$author_id = $revision->post_author;
				$reason    = '';
			} else {
				$author_id = $revision_log[ $revision->ID ]['author'];
				$reason    = $revision_log[ $revision->ID ]['reason'];
			}

			$retval[] = array(
				'time'        => esc_html( bbp_get_time_since( bbp_convert_date( $revision->post_modified ) ) ),
				'author_id'   => $author_id,
				'author_name' => bbp_get_reply_author_display_name( $revision->ID ),
				'avatar'      => ( ! empty( $author_id ) ? get_avatar_url( $author_id, 300 ) : '' ),
				'reason'      => $reason,
			);
		}

		return apply_filters( 'bp_rest_bbp_get_reply_revision_log', $retval, $reply_id );
	}

	/**
	 * Prepare a single reply for create or update
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return object $reply User object.
	 */
	protected function prepare_reply_for_database( $request ) {
		$reply = new stdClass();

		if ( isset( $request['id'] ) ) {
			$reply->bbp_reply_id = $request['id'];
		} elseif ( isset( $request['reply_id'] ) ) {
			$reply->bbp_reply_id = $request['reply_id'];
		}

		if ( isset( $request['topic_id'] ) ) {
			$reply->bbp_topic_id = $request['topic_id'];
		}

		if ( isset( $request['forum_id'] ) ) {
			$reply->bbp_forum_id = $request['forum_id'];
		}

		if ( isset( $request['title'] ) ) {
			$reply->bbp_reply_title = $request['title'];
		}

		if ( isset( $request['content'] ) ) {
			$reply->bbp_reply_content = $request['content'];
		}

		if ( isset( $request['reply_to'] ) ) {
			$reply->bbp_reply_to = $request['reply_to'];
		}

		if ( isset( $request['tags'] ) ) {
			$reply->bbp_topic_tags = $request['tags'];
		}

		if ( isset( $request['reason'] ) ) {
			$reply->bbp_reply_edit_reason = $request['reason'];
		}

		if ( isset( $request['subscribe'] ) && ( true === $request['subscribe'] ) ) {
			$reply->bbp_topic_subscription = true;
		} elseif ( isset( $request['subscribe'] ) && ( false === $request['subscribe'] ) ) {
			$reply->bbp_topic_subscription = false;
		}

		if ( isset( $reply->bbp_topic_subscription ) ) {
			$_POST['bbp_topic_subscription'] = ( $reply->bbp_topic_subscription ) ? 'bbp_subscribe' : '';
		}

		if ( isset( $request['log'] ) ) {
			$reply->bbp_log_reply_edit = $request['log'];
		}

		/**
		 * Filter reply data before inserting user via REST API
		 *
		 * @param object          $reply   Reply object.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( 'bp_rest_reply_object', $reply, $request );
	}
}
