<?php
/**
 * BP REST: BP_REST_Topics_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Topics endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Topics_Endpoint extends WP_REST_Controller {


	/**
	 * BP_REST_Forums_Endpoint Instance.
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
						'description' => __( 'A unique numeric ID for the topic.', 'buddyboss' ),
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
	 * Retrieve Topics.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *                                 - from bbp_has_topics().
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/topics Topics
	 * @apiName        GetBBPTopics
	 * @apiGroup       Forum Topics
	 * @apiDescription Retrieve topics
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
	 * @apiParam {String=asc,desc} [order=asc] Designates ascending or descending order of topics.
	 * @apiParam {Array=meta_value,date,ID,author,title,modified,parent,rand,popular,activity,include} [orderby] Sort retrieved topics by parameter.
	 * @apiParam {Array=publish,private,hidden} [status=publish private] Limit result set to topic assigned a specific status.
	 * @apiParam {Number} [parent] Forum ID to retrieve all the topics.
	 * @apiParam {Boolean} [subscriptions] Retrieve subscribed topics by user.
	 * @apiParam {Boolean} [favorites] Retrieve favorite topics by the current user.
	 * @apiParam {String} [tag] Search topic with specific tag.
	 * @apiParam {String=all} [view] If current user can and is viewing all topics.
	 */
	public function get_items( $request ) {

		global $wpdb;

		$args = array(
			'post_parent'    => ( ! empty( $request['parent'] ) ? $request['parent'] : '' ),
			'orderby'        => ( ! empty( $request['orderby'] ) ? $request['orderby'] : 'meta_value' ),
			'order'          => ( ! empty( $request['order'] ) ? $request['order'] : 'desc' ),
			'paged'          => ( ! empty( $request['page'] ) ? $request['page'] : '' ),
			'posts_per_page' => ( ! empty( $request['per_page'] ) ? $request['per_page'] : bbp_get_topics_per_page() ),
		);

		if ( ! empty( $request['status'] ) ) {
			$args['post_status'] = implode( ' ', $request['status'] );
		}

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
		$default_show_stickies = false;

		if (
			! empty( $args['post_parent'] )
			&& 'forum' === get_post_type( $args['post_parent'] )
			&& empty( $request['search'] )
		) {
			$default_show_stickies = true;
		}

		if (
			! empty( $args['orderby'] )
			&& is_array( $args['orderby'] )
		) {
			if ( in_array( 'popular', $args['orderby'], true ) ) {
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_bbp_reply_count'; // phpcs:ignore
			} elseif ( in_array( 'activity', $args['orderby'], true ) ) {
				$args['orderby']  = 'meta_value';
				$args['meta_key'] = '_bbp_last_active_time'; // phpcs:ignore
			}
		}

		if ( is_array( $args['orderby'] ) ) {
			$args['orderby'] = implode( ' ', $args['orderby'] );
		}

		if (
			! empty( $request['include'] )
			&& ! empty( $args['orderby'] )
			&& 'include' === $args['orderby']
		) {
			$bbp_t['orderby'] = 'post__in';
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_topics_get_items_query_args', $args, $request );

		$default = array(
			'post_type'                => bbp_get_topic_post_type(), // Narrow query down to bbPress topics.
			'show_stickies'            => $default_show_stickies,    // Ignore sticky topics?
			'max_num_pages'            => false,                     // Maximum number of pages to show.

			// Conditionally prime the cache for related posts.
			'update_post_family_cache' => true,
		);

		if ( ! empty( $args['post_parent'] ) ) {
			// phpcs:ignore
			$default['meta_key'] = '_bbp_last_active_time';
		}

		// What are the default allowed statuses (based on user caps).
		if ( bbp_get_view_all( 'edit_others_topics' ) ) {

			// Default view=all statuses.
			$post_statuses = array_keys( bbp_get_topic_statuses() );

			// Add support for private status.
			if ( current_user_can( 'read_private_topics' ) ) {
				$post_statuses[] = bbp_get_private_status_id();
			}

			// Join post statuses together.
			$default['post_status'] = $post_statuses;

			// Lean on the 'perm' query var value of 'readable' to provide statuses.
		} else {
			$default['perm'] = 'readable';
		}

		$tag = sanitize_title( $request->get_param( 'tag' ) );

		if ( bbp_allow_topic_tags() && ! empty( $tag ) ) {
			$default['term']     = bbp_get_topic_tag_slug( $tag );
			$default['taxonomy'] = bbp_get_topic_tag_tax_id();
		}

		$bbp_t = bbp_parse_args( $args, $default, 'has_topics' );

		if ( isset( $request['subscriptions'] ) && ! empty( $request['subscriptions'] ) ) {
			$user_id = (int) (
			( isset( $args['author'] ) && ! empty( $args['author'] ) )
				? $args['author']
				: bbp_get_current_user_id()
			);

			$subscriptions = bbp_get_user_subscribed_topic_ids( $user_id );
			if ( ! empty( $subscriptions ) ) {
				$bbp_t['post__in'] = $subscriptions;
				if ( isset( $args['author'] ) ) {
					unset( $bbp_t['author'] );
				}
			} else {
				$bbp_t = array();
			}
		} elseif ( isset( $request['favorites'] ) && ! empty( $request['favorites'] ) ) {
			$user_id = (int) (
			( isset( $args['author'] ) && ! empty( $args['author'] ) )
				? $args['author']
				: bbp_get_current_user_id()
			);

			$favorites = bbp_get_user_favorites_topic_ids( $user_id );
			if ( ! empty( $favorites ) ) {
				$bbp_t['post__in'] = $favorites;
				if ( isset( $args['author'] ) ) {
					unset( $bbp_t['author'] );
				}
			} else {
				$bbp_t = array();
			}
		}

		// Run the query.
		$topics_query = new WP_Query( $bbp_t );

		/** Stickies */
		// Put sticky posts at the top of the posts array.
		if ( ! empty( $bbp_t['show_stickies'] ) && $bbp_t['paged'] <= 1 ) {

			// Strip the super stickies from topic query.
			// bp-forums/groups.php L791.
			if (
				! empty( $bbp_t['post_parent'] )
				&& 'forum' === get_post_type( $bbp_t['post_parent'] )
			) {
				$group_ids = bbp_get_forum_group_ids( $bbp_t['post_parent'] );
				if ( ! empty( $group_ids ) ) {
					add_filter( 'bbp_get_super_stickies', array( $this, 'no_super_stickies' ), 10, 1 );
				}
			}

			// Get super stickies and stickies in this forum.
			$stickies = bbp_get_super_stickies();

			// Strip the super stickies from topic query.
			if (
				! empty( $bbp_t['post_parent'] )
				&& 'forum' === get_post_type( $bbp_t['post_parent'] )
			) {
				$group_ids = bbp_get_forum_group_ids( $bbp_t['post_parent'] );
				if ( ! empty( $group_ids ) ) {
					remove_filter( 'bbp_get_super_stickies', array( $this, 'no_super_stickies' ), 10, 1 );
				}
			}

			// Get stickies for current forum.
			if ( ! empty( $bbp_t['post_parent'] ) ) {
				$stickies = array_merge( $stickies, bbp_get_stickies( $bbp_t['post_parent'] ) );
			}

			// Remove any duplicate stickies.
			$stickies = array_unique( $stickies );

			// We have stickies.
			if ( is_array( $stickies ) && ! empty( $stickies ) ) {

				// Start the offset at -1 so first sticky is at correct 0 offset.
				$sticky_offset = - 1;

				// Loop over topics and relocate stickies to the front.
				foreach ( $stickies as $sticky_index => $sticky_id ) {

					// Get the post offset from the posts array.
					$post_offsets = wp_filter_object_list( $topics_query->posts, array( 'ID' => $sticky_id ), 'OR', 'ID' );

					// Continue if no post offsets.
					if ( empty( $post_offsets ) ) {
						continue;
					}

					// Loop over posts in current query and splice them into position.
					foreach ( array_keys( $post_offsets ) as $post_offset ) {
						$sticky_offset ++;

						$sticky = $topics_query->posts[ $post_offset ];

						// Remove sticky from current position.
						array_splice( $topics_query->posts, $post_offset, 1 );

						// Move to front, after other stickies.
						array_splice( $topics_query->posts, $sticky_offset, 0, array( $sticky ) );

						// Cleanup.
						unset( $stickies[ $sticky_index ] );
						unset( $sticky );
					}

					// Cleanup.
					unset( $post_offsets );
				}

				// Cleanup.
				unset( $sticky_offset );

				// If any posts have been excluded specifically, Ignore those that are sticky.
				if ( ! empty( $stickies ) && ! empty( $bbp_t['post__not_in'] ) ) {
					$stickies = array_diff( $stickies, $bbp_t['post__not_in'] );
				}

				// Fetch sticky posts that weren't in the query results.
				if ( ! empty( $stickies ) ) {

					// Query to use in get_posts to get sticky posts.
					$sticky_query = array(
						'post_type'              => bbp_get_topic_post_type(),
						'post_parent'            => 'any',
						'meta_key'               => '_bbp_last_active_time', // phpcs:ignore
						'orderby'                => 'meta_value',
						'order'                  => 'DESC',
						'include'                => $stickies,
						'suppress_filters'       => false,
						'update_post_term_cache' => false,
					);

					// Cleanup.
					unset( $stickies );

					// Conditionally exclude private/hidden forum ID's.
					$exclude_forum_ids = bbp_exclude_forum_ids( 'array' );
					if ( ! empty( $exclude_forum_ids ) ) {
						$sticky_query['post_parent__not_in'] = $exclude_forum_ids;
					}

					// What are the default allowed statuses (based on user caps).
					if ( bbp_get_view_all( 'edit_others_topics' ) ) {
						$sticky_query['post_status'] = $bbp_t['post_status'];

						// Lean on the 'perm' query var value of 'readable' to provide statuses.
					} else {
						$sticky_query['post_status'] = $bbp_t['perm'];
					}

					// Get all stickies.
					$sticky_posts = get_posts( $sticky_query );

					if ( ! empty( $sticky_posts ) ) {

						// Get a count of the visible stickies.
						$sticky_count = count( $sticky_posts );

						// Merge the stickies topics with the query topics.
						$topics_query->posts = array_merge( $sticky_posts, $topics_query->posts );

						// Adjust loop and counts for new sticky positions.
						$topics_query->found_posts = (int) $topics_query->found_posts + (int) $sticky_count;
						$topics_query->post_count  = (int) $topics_query->post_count + (int) $sticky_count;

						// Cleanup.
						unset( $sticky_posts );
					}
				}
			}
		}

		// If no limit to posts per page, set it to the current post_count.
		if ( - 1 === $bbp_t['posts_per_page'] ) {
			$topics_query->posts_per_page = $topics_query->post_count;
		}
		/** --Stickies */

		$topics = ( ! empty( $topics_query->posts ) ? $topics_query->posts : array() );

		$retval = array();
		foreach ( $topics as $topic ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $topic, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $topics_query->found_posts, $args['posts_per_page'] );

		/**
		 * Fires after a list of topics is fetched via the REST API.
		 *
		 * @param array            $topics   Fetched Topics.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_topics_get_items', $topics, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list topics.
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
		 * Filter the topics `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topics_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single topic.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/topics/:id Topic
	 * @apiName        GetBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Retrieve a single topic.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 */
	public function get_item( $request ) {

		$topic = bbp_get_topic( $request['id'] );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $topic, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of topic is fetched via the REST API.
		 *
		 * @param array            $topic    Fetched topic.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_topic_get_item', $topic, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list topic.
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

		$topic = bbp_get_topic( $request->get_param( 'id' ) );

		if ( true === $retval && empty( $topic->ID ) ) {
			$retval = new WP_Error(
				'bp_rest_topic_invalid_id',
				__( 'Invalid topic ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ( ! isset( $topic->post_type ) || 'topic' !== $topic->post_type ) ) {
			$retval = new WP_Error(
				'bp_rest_topic_invalid_id',
				__( 'Invalid topic ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( isset( $topic->post_type ) ) {
			$post_type = get_post_type_object( $topic->post_type );

			if ( true === $retval && is_user_logged_in() && ! current_user_can( $post_type->cap->read_post, $topic->ID ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to access this topic.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the topic `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a topic.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/topics Create Topic
	 * @apiName        CreateBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Create a topic.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} title The title of the topic.
	 * @apiParam {String} content The content of the topic.
	 * @apiParam {Number} parent ID of the parent Forum.
	 * @apiParam {String=publish,closed,spam,trash,pending} [status=publish] The current status of the topic.
	 * @apiParam {String=stick,super,unstick} [sticky=unstick] Whether the topic is sticky or not.
	 * @apiParam {Number} [group] ID of the forum's group.
	 * @apiParam {String} [topic_tags] Topic's tags with comma separated.
	 * @apiParam {Array} [bbp_media] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [bbp_media_gif] Save gif data into topic when Media component is enable. param(url,mp4)
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

		if ( ! empty( $request ) ) {
			foreach ( $post_map as $key => $val ) {
				if ( isset( $request[ $val ] ) ) {
					$_POST[ $key ] = $request[ $val ];
				}
			}
		}

		$_POST['action'] = 'bbp-new-topic'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$topic = $this->prepare_topic_for_database( $request );

		// Define local variable(s).
		$forum_id       = 0;
		$topic_author   = 0;
		$anonymous_data = 0;
		$topic_title    = '';
		$topic_content  = '';
		$terms          = array( bbp_get_topic_tag_tax_id() => array() );

		/** Topic Author */
		if ( bbp_is_anonymous() ) {

			$anonymous_args = array(
				'bbp_anonymous_name'    => ! empty( $request['anonymous_name'] ) ? sanitize_text_field( $request['anonymous_name'] ) : '',
				'bbp_anonymous_email'   => ! empty( $request['anonymous_email'] ) ? sanitize_email( $request['anonymous_email'] ) : '',
				'bbp_anonymous_website' => ! empty( $request['anonymous_website'] ) ? sanitize_text_field( $request['anonymous_website'] ) : '',
			);

			// Filter anonymous data.
			$anonymous_data = bbp_filter_anonymous_post_data( $anonymous_args );

			// Anonymous data checks out, so set cookies, etc...
			if ( ! empty( $anonymous_data ) && is_array( $anonymous_data ) ) {
				bbp_set_current_anonymous_user_data( $anonymous_data );
			}

			// User is logged in.
		} else {

			// User cannot create topics.
			if ( ! current_user_can( 'publish_topics' ) ) {
				return new WP_Error(
					'bp_rest_bbp_topic_permissions',
					__( 'Sorry, You do not have permission to create new discussions.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}

			// Topic author is current user.
			$topic_author = bbp_get_current_user_id();
		}

		// Remove kses filters from title and content for capable users and if the nonce is verified.
		remove_filter( 'bbp_new_topic_pre_title', 'wp_filter_kses' );
		remove_filter( 'bbp_new_topic_pre_content', 'bbp_encode_bad', 10 );

		/** Discussion Title */
		if ( ! empty( $topic->bbp_topic_title ) ) {
			$topic_title = esc_attr( wp_strip_all_tags( $topic->bbp_topic_title ) );
		}

		// Filter and sanitize.
		$topic_title = apply_filters( 'bbp_new_topic_pre_title', $topic_title );

		// No topic title.
		if ( empty( $topic_title ) ) {
			return new WP_Error(
				'bp_rest_bbp_topic_title',
				__( 'Sorry, Your discussion needs a subject.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Content */
		if ( ! empty( $topic->bbp_topic_content ) ) {
			$topic_content = $topic->bbp_topic_content;
		}

		// Filter and sanitize.
		$topic_content = apply_filters( 'bbp_new_topic_pre_content', $topic_content );

		// No topic content.
		if (
			empty( $topic_content )
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
				'bp_rest_bbp_topic_content',
				__( 'Sorry, Your discussion cannot be empty.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Forum */
		// Error check the POST'ed topic id.
		if ( isset( $topic->bbp_forum_id ) ) {

			// Empty Forum id was passed.
			if ( empty( $topic->bbp_forum_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_topic_forum_id',
					__( 'Sorry, Forum ID is missing.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
				// Forum id is not a number.
			} elseif ( ! is_numeric( $topic->bbp_forum_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_topic_forum_id',
					__( 'Sorry, Forum ID must be a number.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Forum id might be valid.
			} else {

				// Get the forum id.
				$posted_forum_id = intval( $topic->bbp_forum_id );

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
					'bp_rest_bbp_topic_forum_category',
					__( 'Sorry, This forum is a category. No discussions can be created in this forum.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Forum is not a category.
			} else {

				// Forum is closed and user cannot access.
				if ( bbp_is_forum_closed( $forum_id ) && ! current_user_can( 'edit_forum', $forum_id ) ) {
					return new WP_Error(
						'bp_rest_bbp_topic_forum_closed',
						__( 'Sorry, This forum has been closed to new discussions.', 'buddyboss' ),
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
				$is_member = false;
				$group_ids = array();
				if ( function_exists( 'bbp_get_forum_group_ids' ) ) {
					$group_ids = bbp_get_forum_group_ids( $forum_id );
					if ( ! empty( $group_ids ) ) {
						foreach ( $group_ids as $group_id ) {
							if ( groups_is_user_member( $topic_author, $group_id ) ) {
								$is_member = true;
								break;
							}
						}
					}
				}

				// Forum is private and user cannot access.
				if ( bbp_is_forum_private( $forum_id ) && ! bbp_is_user_keymaster() ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_private_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_topic_forum_closed',
							__( 'Sorry, This forum is private and you do not have the capability to read or create new discussions in it.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}

					// Forum is hidden and user cannot access.
				} elseif ( bbp_is_forum_hidden( $forum_id ) && ! bbp_is_user_keymaster() ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_hidden_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_topic_forum_closed',
							__( 'Sorry, This forum is hidden and you do not have the capability to read or create new discussions in it.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}
				}
			}
		}

		$topic_forum = ! empty( $forum_id ) ? $forum_id : 0;
		if ( ! empty( $request['bbp_media'] ) && function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), $topic_forum, 0, 'forum' );
			if ( ! $can_send_media ) {
				return new WP_Error(
					'bp_rest_bbp_topic_media',
					__( 'You don\'t have access to send the media.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_documents'] ) && function_exists( 'bb_user_has_access_upload_document' ) ) {
			$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), $topic_forum, 0, 'forum' );
			if ( ! $can_send_document ) {
				return new WP_Error(
					'bp_rest_bbp_topic_media',
					__( 'You don\'t have access to send the document.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_videos'] ) && function_exists( 'bb_user_has_access_upload_video' ) ) {
			$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), $topic_forum, 0, 'forum' );
			if ( ! $can_send_video ) {
				return new WP_Error(
					'bp_rest_bbp_topic_media',
					__( 'You don\'t have access to send the video.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_media_gif'] ) && function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), $topic_forum, 0, 'forum' );
			if ( ! $can_send_gif ) {
				return new WP_Error(
					'bp_rest_bbp_topic_media',
					__( 'You don\'t have access to send the gif.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		/** Topic Flooding */
		if ( ! bbp_check_for_flood( $anonymous_data, $topic_author ) ) {
			return new WP_Error(
				'bp_rest_bbp_topic_flood',
				__( 'Slow down; you move too fast.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Duplicate */

		if ( ! bbp_check_for_duplicate(
			array(
				'post_type'      => bbp_get_topic_post_type(),
				'post_author'    => $topic_author,
				'post_content'   => $topic_content,
				'anonymous_data' => $anonymous_data,
			)
		) ) {
			return new WP_Error(
				'bp_rest_bbp_topic_duplicate',
				__( 'Duplicate discussion detected; it looks as though you\'ve already said that!', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Blacklist */
		if ( ! bbp_check_for_blacklist( $anonymous_data, $topic_author, $topic_title, $topic_content ) ) {
			return new WP_Error(
				'bp_rest_bbp_topic_blacklist',
				__( 'Sorry, Your discussion cannot be created at this time.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Status */
		// Maybe put into moderation.
		if ( ! bbp_check_for_moderation( $anonymous_data, $topic_author, $topic_title, $topic_content ) ) {
			$topic_status = bbp_get_pending_status_id();

			// Check a whitelist of possible topic status ID's.
		} elseif ( ! empty( $topic->bbp_topic_status ) && in_array( $topic->bbp_topic_status, array_keys( bbp_get_topic_statuses() ), true ) ) {
			$topic_status = $topic->bbp_topic_status;

			// Default to published if nothing else.
		} else {
			$topic_status = bbp_get_public_status_id();
		}

		/** Topic Tags */
		if ( bbp_allow_topic_tags() && ! empty( $topic->bbp_topic_tags ) ) {

			// Escape tag input.
			$terms = esc_attr( wp_strip_all_tags( $topic->bbp_topic_tags ) );

			// Explode by comma.
			if ( strstr( $terms, ',' ) ) {
				$terms = explode( ',', $terms );
			}

			// Add topic tag ID as main key.
			$terms = array( bbp_get_topic_tag_tax_id() => $terms );
		}

		/** Additional Actions (Before Save) */
		do_action( 'bbp_new_topic_pre_extras', $forum_id );

		// Bail if errors.
		if ( bbp_has_errors() ) {
			return new WP_Error(
				'bp_rest_bbp_topic_unknown',
				__( 'Unknown error.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** No Errors */
		// Add the content of the form to $topic_data as an array.
		// Just in time manipulation of topic data before being created.
		$topic_data = apply_filters(
			'bbp_new_topic_pre_insert',
			array(
				'post_author'    => $topic_author,
				'post_title'     => $topic_title,
				'post_content'   => $topic_content,
				'post_status'    => $topic_status,
				'post_parent'    => $forum_id,
				'post_type'      => bbp_get_topic_post_type(),
				'comment_status' => 'closed',
			)
		);

		// Insert topic.
		$topic_id = wp_insert_post( $topic_data );

		if ( empty( $topic_id ) || is_wp_error( $topic_id ) ) {
			$append_error = (
			( is_wp_error( $topic_id ) && $topic_id->get_error_message() )
				? __( 'The following problem(s) have been found with your topic: ', 'buddyboss' ) . $topic_id->get_error_message()
				: __( 'We are facing a problem to creating a topic.', 'buddyboss' )
			);

			return new WP_Error(
				'bp_rest_bbp_topic_error',
				$append_error,
				array(
					'status' => 400,
				)
			);
		}

		// update tags.
		if ( function_exists( 'bb_add_topic_tags' ) ) {
			bb_add_topic_tags( (array) $terms[ bbp_get_topic_tag_tax_id() ], $topic_id, bbp_get_topic_tag_tax_id() );
		}

		/** Trash Check */
		// If the forum is trash, or the topic_status is switched to.
		// trash, trash it properly.
		if (
			( bbp_get_trash_status_id() === get_post_field( 'post_status', $forum_id ) )
			|| ( bbp_get_trash_status_id() === $topic_data['post_status'] )
		) {

			// Trash the reply.
			wp_trash_post( $topic_id );
		}

		/** Spam Check */
		// If reply or topic are spam, officially spam this reply.
		if ( bbp_get_spam_status_id() === $topic_data['post_status'] ) {
			add_post_meta( $topic_id, '_bbp_spam_meta_status', bbp_get_public_status_id() );
		}

		/**
		 * Removed notification sent and called additionally.
		 * Due to we have moved all filters on title and content.
		 */
		remove_action( 'bbp_new_topic', 'bbp_notify_forum_subscribers', 9999, 4 );
		remove_action( 'bbp_new_topic', 'bbp_buddypress_add_topic_notification', 9999, 2 );

 		/** Update counts, etc... */
		do_action( 'bbp_new_topic', $topic_id, $forum_id, $anonymous_data, $topic_author );

		/** Stickies */
		// Sticky check after 'bbp_new_topic' action so forum ID meta is set.
		if ( ! empty( $topic->bbp_stick_topic ) && in_array(
			$topic->bbp_stick_topic,
			array(
				'stick',
				'super',
				'unstick',
			),
			true
		) ) {

			// What's the caps?
			if ( current_user_can( 'moderate' ) ) {

				// What's the haps?
				switch ( $topic->bbp_stick_topic ) {

					// Sticky in this forum.
					case 'stick':
						bbp_stick_topic( $topic_id );
						break;

					// Super sticky in all forums.
					case 'super':
						bbp_stick_topic( $topic_id, true );
						break;

					// We can avoid this as it is a new topic.
					case 'unstick':
					default:
						break;
				}
			}
		}

		// Handle Subscription Checkbox.
		if ( bb_is_enabled_subscription( 'topic' ) ) {
			$author_id = bbp_get_user_id( 0, true, true );
			// Check if subscribed.
			$subscribed = bbp_is_user_subscribed( $author_id, $topic_id );

			// Subscribed and unsubscribing.
			if ( true === $subscribed && empty( $topic->bbp_topic_subscription ) ) {
				bbp_remove_user_subscription( $author_id, $topic_id );

				// Not subscribed and subscribing.
			} elseif ( false === $subscribed && ! empty( $topic->bbp_topic_subscription ) ) {
				bbp_add_user_subscription( $author_id, $topic_id );
			}
		}

		/** Additional Actions (After Save) */
		do_action( 'bbp_new_topic_post_extras', $topic_id );

		$topic         = bbp_get_topic( $topic_id );
		$fields_update = $this->update_additional_fields_for_object( $topic, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		if ( function_exists( 'bbp_buddypress_add_topic_notification' ) ) {
			bbp_buddypress_add_topic_notification( $topic_id, $forum_id );
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $topic, $request )
		);

		$response = rest_ensure_response( $retval );

		if ( function_exists( 'bbp_notify_forum_subscribers' ) ) {
			/**
			 * Sends notification emails for new topics to subscribed forums.
			 */
			bbp_notify_forum_subscribers( $topic_id, $forum_id, $anonymous_data, $topic_author );
		}

		/**
		 * Fires after a topic is created and fetched via the REST API.
		 *
		 * @param array            $topic    Created topic.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_topic_create_item', $topic, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a topic.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create a topic.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() || bbp_allow_anonymous() ) {
			$retval = true;
		}

		/**
		 * Filter the topic `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Update/Edit a topic.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/topics/:id Update Topic
	 * @apiName        UpdateBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Update a topic.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 * @apiParam {String} title The title of the topic.
	 * @apiParam {String} content The content of the topic.
	 * @apiParam {Number} parent ID of the parent Forum.
	 * @apiParam {String=publish,closed,spam,trash,pending} [status=publish] The current status of the topic.
	 * @apiParam {String=stick,super,unstick} [sticky=unstick] Whether the topic is sticky or not.
	 * @apiParam {Number} [group] ID of the forum's group.
	 * @apiParam {String} [topic_tags] Topic's tags with comma separated.
	 * @apiParam {String} [reason_editing] Reason for editing a topic.
	 * @apiParam {Boolean} [log] Keep a log of topic edit.
	 * @apiParam {Array} [bbp_media] Media specific IDs when Media component is enable.
	 * @apiParam {Array} [bbp_media_gif] Save gif data into topic when Media component is enable. param(url,mp4)
	 */
	public function update_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$topic_new = $this->prepare_topic_for_database( $request );

		// Define local variable(s).
		$revisions_removed = false;
		$topic             = 0;
		$topic_id          = 0;
		$topic_author      = 0;
		$forum_id          = 0;
		$topic_title       = '';
		$topic_content     = '';
		$topic_edit_reason = '';
		$anonymous_data    = array();

		// Topic id was not passed.
		if ( empty( $topic_new->bbp_topic_id ) ) {
			new WP_Error(
				'bp_rest_topic_invalid_id',
				__( 'Invalid topic ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);

			// Topic id was passed.
		} elseif ( is_numeric( $topic_new->bbp_topic_id ) ) {
			$topic_id = (int) $topic_new->bbp_topic_id;
			$topic    = bbp_get_topic( $topic_id );
		}

		// Topic does not exist.
		if ( empty( $topic ) ) {
			new WP_Error(
				'bp_rest_bbp_edit_topic_not_found',
				__( 'Sorry, The discussion you want to edit was not found.', 'buddyboss' ),
				array(
					'status' => 404,
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

		$_POST['action'] = 'bbp-edit-topic'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Topic exists.
		// Check users ability to create new topic.
		if ( ! bbp_is_topic_anonymous( $topic_id ) ) {

			// User cannot edit this topic.
			if ( ! current_user_can( 'edit_topic', $topic_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_edit_topic_permissions',
					__( 'Sorry, You do not have permission to edit that discussion.', 'buddyboss' ),
					array(
						'status' => 403,
					)
				);
			}

			// Set topic author.
			$topic_author = bbp_get_topic_author_id( $topic_id );

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

		// Remove kses filters from title and content for capable users.
		remove_filter( 'bbp_new_topic_pre_title', 'wp_filter_kses' );
		remove_filter( 'bbp_new_topic_pre_content', 'bbp_encode_bad', 10 );

		/** Topic Forum */
		// Forum id was not passed.
		if ( empty( $topic_new->bbp_forum_id ) ) {
			return new WP_Error(
				'bp_rest_bbp_topic_forum_id',
				__( 'Sorry, Forum ID is missing.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);

			// Forum id was passed.
		} elseif ( is_numeric( $topic_new->bbp_forum_id ) ) {
			$forum_id = (int) $topic_new->bbp_forum_id;
		}

		// Current forum this topic is in.
		$current_forum_id = bbp_get_topic_forum_id( $topic_id );

		// Forum exists.
		if ( ! empty( $forum_id ) && ( $forum_id !== $current_forum_id ) ) {

			// Forum is a category.
			if ( bbp_is_forum_category( $forum_id ) ) {
				return new WP_Error(
					'bp_rest_bbp_edit_topic_forum_category',
					__( 'Sorry, This forum is a category. No discussions can be created in this forum.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);

				// Forum is not a category.
			} else {

				// Forum is closed and user cannot access.
				if ( bbp_is_forum_closed( $forum_id ) && ! current_user_can( 'edit_forum', $forum_id ) ) {
					return new WP_Error(
						'bp_rest_bbp_edit_topic_forum_closed',
						__( 'Sorry, This forum has been closed to new discussions.', 'buddyboss' ),
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
				$is_member = false;
				$group_ids = array();
				if ( function_exists( 'bbp_get_forum_group_ids' ) ) {
					$group_ids = bbp_get_forum_group_ids( $forum_id );
					if ( ! empty( $group_ids ) ) {
						foreach ( $group_ids as $group_id ) {
							if ( groups_is_user_member( $topic_author, $group_id ) ) {
								$is_member = true;
								break;
							}
						}
					}
				}

				// Forum is private and user cannot access.
				if ( bbp_is_forum_private( $forum_id ) && ! bbp_is_user_keymaster() ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_private_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_edit_topic_forum_private',
							__( 'Sorry, This forum is private and you do not have the capability to read or create new discussions in it.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}

					// Forum is hidden and user cannot access.
				} elseif ( bbp_is_forum_hidden( $forum_id ) && ! bbp_is_user_keymaster() ) {
					if (
						( empty( $group_ids ) && ! current_user_can( 'read_hidden_forums' ) )
						|| ( ! empty( $group_ids ) && ! $is_member )
					) {
						return new WP_Error(
							'bp_rest_bbp_edit_topic_forum_hidden',
							__( 'Sorry, This forum is hidden and you do not have the capability to read or create new discussions in it.', 'buddyboss' ),
							array(
								'status' => 400,
							)
						);
					}
				}
			}
		}

		/** Discussion Title */
		if ( ! empty( $topic_new->bbp_topic_title ) ) {
			$topic_title = esc_attr( wp_strip_all_tags( $topic_new->bbp_topic_title ) );
		}

		// Filter and sanitize.
		$topic_title = apply_filters( 'bbp_edit_topic_pre_title', $topic_title, $topic_id );

		// No topic title.
		if ( empty( $topic_title ) ) {
			return new WP_Error(
				'bp_rest_bbp_edit_topic_title',
				__( 'Sorry, Your discussion needs a title.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Content */
		if ( ! empty( $topic_new->bbp_topic_content ) ) {
			$topic_content = $topic_new->bbp_topic_content;
		}

		// Filter and sanitize.
		$topic_content = apply_filters( 'bbp_edit_topic_pre_content', $topic_content, $topic_id );

		// No topic content.
		if (
			empty( $topic_content )
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
				'bp_rest_bbp_edit_topic_content',
				__( 'Sorry, Your discussion cannot be empty.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$topic_forum = ! empty( $forum_id ) ? $forum_id : 0;
		if ( ! empty( $request['bbp_media'] ) && function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), $topic_forum, 0, 'forum' );
			if ( ! $can_send_media ) {
				return new WP_Error(
					'bp_rest_bbp_topic_media',
					__( 'You don\'t have access to send the media.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_documents'] ) && function_exists( 'bb_user_has_access_upload_document' ) ) {
			$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), $topic_forum, 0, 'forum' );
			if ( ! $can_send_document ) {
				return new WP_Error(
					'bp_rest_bbp_topic_media',
					__( 'You don\'t have access to send the document.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		if ( ! empty( $request['bbp_media_gif'] ) && function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), $topic_forum, 0, 'forum' );
			if ( ! $can_send_gif ) {
				return new WP_Error(
					'bp_rest_bbp_topic_media',
					__( 'You don\'t have access to send the gif.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		/** Topic Blacklist */
		if ( ! bbp_check_for_blacklist( $anonymous_data, $topic_author, $topic_title, $topic_content ) ) {
			return new WP_Error(
				'bp_rest_bbp_topic_blacklist',
				__( 'Sorry, Your discussion cannot be edited at this time.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/** Topic Status */
		// Maybe put into moderation.
		if ( ! bbp_check_for_moderation( $anonymous_data, $topic_author, $topic_title, $topic_content ) ) {

			// Set post status to pending if public or closed.
			if ( in_array(
				$topic->post_status,
				array(
					bbp_get_public_status_id(),
					bbp_get_closed_status_id(),
				),
				true
			) ) {
				$topic_status = bbp_get_pending_status_id();
			}

			// Check a whitelist of possible topic status ID's.
		} elseif ( ! empty( $topic_new->bbp_topic_status ) && in_array( $topic_new->bbp_topic_status, array_keys( bbp_get_topic_statuses() ), true ) ) {
			$topic_status = $topic_new->bbp_topic_status;

			// Use existing post_status.
		} else {
			$topic_status = $topic->post_status;
		}

		/** Topic Tags */
		// Either replace terms.
		if ( bbp_allow_topic_tags() && current_user_can( 'assign_topic_tags' ) && ! empty( $topic_new->bbp_topic_tags ) ) {

			// Escape tag input.
			$terms = esc_attr( wp_strip_all_tags( $topic_new->bbp_topic_tags ) );

			// Explode by comma.
			if ( strstr( $terms, ',' ) ) {
				$terms = explode( ',', $terms );
			}

			// Add topic tag ID as main key.
			$terms = array( bbp_get_topic_tag_tax_id() => $terms );

			// ...or remove them.
		} elseif ( isset( $topic_new->bbp_topic_tags ) ) {
			$terms = array( bbp_get_topic_tag_tax_id() => array() );

			// Existing terms.
		} else {
			$terms = array( bbp_get_topic_tag_tax_id() => explode( ',', bbp_get_topic_tag_names( $topic_id, ',' ) ) );
		}

		/** Additional Actions (Before Save) */
		do_action( 'bbp_edit_topic_pre_extras', $topic_id );

		// Add the content of the form to $topic_data as an array.
		// Just in time manipulation of topic data before being edited.
		$topic_data = apply_filters(
			'bbp_edit_topic_pre_insert',
			array(
				'ID'           => $topic_id,
				'post_title'   => $topic_title,
				'post_content' => $topic_content,
				'post_status'  => $topic_status,
				'post_parent'  => $forum_id,
				'post_author'  => $topic_author,
				'post_type'    => bbp_get_topic_post_type(),
			)
		);

		// Toggle revisions to avoid duplicates.
		if ( post_type_supports( bbp_get_topic_post_type(), 'revisions' ) ) {
			$revisions_removed = true;
			remove_post_type_support( bbp_get_topic_post_type(), 'revisions' );
		}

		if ( function_exists( 'bp_media_forums_new_post_media_save' ) ) {
			remove_action( 'edit_post', 'bp_media_forums_new_post_media_save', 999 );
		}

		if ( function_exists( 'bp_document_forums_new_post_document_save' ) ) {
			remove_action( 'edit_post', 'bp_document_forums_new_post_document_save', 999 );
		}

		// Insert topic.
		$topic_id = wp_update_post( $topic_data );

		if ( function_exists( 'bp_media_forums_new_post_media_save' ) ) {
			add_action( 'edit_post', 'bp_media_forums_new_post_media_save', 999 );
		}

		if ( function_exists( 'bp_document_forums_new_post_document_save' ) ) {
			add_action( 'edit_post', 'bp_document_forums_new_post_document_save', 999 );
		}

		// Toggle revisions back on.
		if ( true === $revisions_removed ) {
			$revisions_removed = false;
			add_post_type_support( bbp_get_topic_post_type(), 'revisions' );
		}

		if ( empty( $topic_id ) || is_wp_error( $topic_id ) ) {
			$append_error = (
			( is_wp_error( $topic_id ) && $topic_id->get_error_message() )
				? __( 'The following problem(s) have been found with your topic: ', 'buddyboss' ) . $topic_id->get_error_message() . __( 'Please try again.', 'buddyboss' )
				: __( 'We are facing a problem to update a topic.', 'buddyboss' )
			);

			return new WP_Error(
				'bp_rest_bbp_topic_error',
				$append_error,
				array(
					'status' => 400,
				)
			);
		}

		// update tags.
		if ( function_exists( 'bb_add_topic_tags' ) ) {
			bb_add_topic_tags( (array) $terms[ bbp_get_topic_tag_tax_id() ], $topic_id, bbp_get_topic_tag_tax_id(), bbp_get_topic_tag_names( $topic_id ) );
		}

		// Update counts, etc...
		do_action( 'bbp_edit_topic', $topic_id, $forum_id, $anonymous_data, $topic_author, true /* Is edit */ );

		/** Revisions */
		// Revision Reason.
		if ( ! empty( $topic_new->bbp_topic_edit_reason ) ) {
			$topic_edit_reason = esc_attr( wp_strip_all_tags( $topic_new->bbp_topic_edit_reason ) );
		}

		// Update revision log.
		if ( ! empty( $topic_new->bbp_log_topic_edit ) && ( true === $topic_new->bbp_log_topic_edit ) ) {
			$revision_id = wp_save_post_revision( $topic_id );
			if ( ! empty( $revision_id ) ) {
				bbp_update_topic_revision_log(
					array(
						'topic_id'    => $topic_id,
						'revision_id' => $revision_id,
						'author_id'   => bbp_get_current_user_id(),
						'reason'      => $topic_edit_reason,
					)
				);
			}
		}

		/** Move Topic */
		// If the new forum id is not equal to the old forum id, run the.
		// bbp_move_topic action and pass the topic's forum id as the.
		// first arg and topic id as the second to update counts.
		if ( $forum_id !== $topic->post_parent ) {
			bbp_move_topic_handler( $topic_id, $topic->post_parent, $forum_id );
		}

		/** Stickies */
		if ( ! empty( $topic_new->bbp_stick_topic ) && in_array( $topic_new->bbp_stick_topic, array_keys( bbp_get_topic_types() ), true ) ) {
			// What's the caps?
			if ( current_user_can( 'moderate' ) ) {

				// What's the haps?
				switch ( $topic_new->bbp_stick_topic ) {

					// Sticky in forum.
					case 'stick':
						bbp_stick_topic( $topic_id );
						break;

					// Sticky in all forums.
					case 'super':
						bbp_stick_topic( $topic_id, true );
						break;

					// Normal.
					case 'unstick':
					default:
						bbp_unstick_topic( $topic_id );
						break;
				}
			}
		}

		// Handle Subscription Checkbox.
		if ( bb_is_enabled_subscription( 'topic' ) ) {
			$author_id = bbp_get_user_id( 0, true, true );
			// Check if subscribed.
			$subscribed = bbp_is_user_subscribed( $author_id, $topic_id );

			// Subscribed and unsubscribing.
			if ( true === $subscribed && empty( $topic_new->bbp_topic_subscription ) ) {
				bbp_remove_user_subscription( $author_id, $topic_id );

				// Not subscribed and subscribing.
			} elseif ( false === $subscribed && ! empty( $topic_new->bbp_topic_subscription ) ) {
				bbp_add_user_subscription( $author_id, $topic_id );
			}
		}

		/** Additional Actions (After Save) */
		do_action( 'bbp_edit_topic_post_extras', $topic_id );

		$topic         = bbp_get_topic( $topic_id );
		$topic->edit   = true;
		$fields_update = $this->update_additional_fields_for_object( $topic, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $topic, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a topic is updated and fetched via the REST API.
		 *
		 * @param array            $topic    Updated topic.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_topic_update_item', $topic, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a topic.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create a topic.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() || bbp_allow_anonymous() ) {
			$retval = $this->get_item_permissions_check( $request );
		}

		if ( true === $retval ) {
			$topic = bbp_get_topic( $request->get_param( 'id' ) );
			if ( bbp_get_user_id( 0, true, true ) !== $topic->post_author && ! current_user_can( 'edit_topic', $request->get_param( 'id' ) ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update this topic.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the topic `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a topic.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/topics/:id Trash/Delete Topic
	 * @apiName        DeleteBBPTopic
	 * @apiGroup       Forum Topics
	 * @apiDescription Trash OR Delete a topic.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the topic.
	 */
	public function delete_item( $request ) {

		$topic = bbp_get_topic( $request['id'] );

		$previous = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $topic, $request )
		);

		$success = wp_delete_post( $topic->ID );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => ( ! empty( $success ) && ! is_wp_error( $success ) ? true : $success ),
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a topic is deleted via the REST API.
		 *
		 * @param array            $topic    Deleted/Trashed topic.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_topic_delete_item', $topic, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a topic.
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

			if ( ! current_user_can( 'delete_topic', $request->get_param( 'id' ) ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this topic.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the topic `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_delete_item_permissions_check', $retval, $request );
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
					'description' => __( 'A unique numeric ID for the topic.', 'buddyboss' ),
					'type'        => 'integer',
					'required'    => true,
				),
			);
		} elseif ( WP_REST_Server::EDITABLE === $method || WP_REST_Server::CREATABLE === $method ) {
			$unset_keys = array(
				'date',
				'date_gmt',
				'password',
				'slug',
				'link',
				'author',
				'total_reply_count',
				'last_reply_id',
				'last_active_author',
				'last_active_time',
				'is_closed',
				'classes',
				'voice_count',
				'forum_id',
				'is_topic_anonymous',
				'anonymous_author_data',
				'action_states',
				'current_user_permissions',
				'revisions',
			);

			if ( ! empty( $unset_keys ) ) {
				foreach ( $unset_keys as $k ) {
					if ( array_key_exists( $k, $args ) ) {
						unset( $args[ $k ] );
					}
				}
			}

			$args['title']['type']         = 'string';
			$args['title']['required']     = true;
			$args['content']['type']       = 'string';
			$args['content']['required']   = false;
			$args['status']['default']     = 'publish';
			$args['status']['enum']        = array_keys( bbp_get_topic_statuses() );
			$args['sticky']['type']        = 'string';
			$args['sticky']['enum']        = array( 'stick', 'super', 'unstick' );
			$args['parent']['description'] = __( 'ID of the parent Forum.', 'buddyboss' );
			$args['parent']['required']    = true;
			$args['group']['type']         = 'integer';
			$args['group']['description']  = __( 'ID of the forum\'s group', 'buddyboss' );

			$params['subscribe'] = array(
				'description'       => __( 'whether user subscribe topic or no', 'buddyboss' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			$args['reason_editing'] = array(
				'description'       => __( 'Reason for editing a topic.', 'buddyboss' ),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['log'] = array(
				'description'       => __( 'Keep a log of topic edit.', 'buddyboss' ),
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$params['subscribe'] = array(
				'description'       => __( 'whether user subscribe topic or no', 'buddyboss' ),
				'type'              => 'boolean',
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
		return apply_filters( "bp_rest_topic_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepares component data for return as an object.
	 *
	 * @param array           $topic   The component and its values.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $topic, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Base fields for every post.
		$data = array(
			'id'                    => $topic->ID,
			'date'                  => $this->forum_endpoint->prepare_date_response( $topic->post_date_gmt, $topic->post_date ),
			'date_gmt'              => $this->forum_endpoint->prepare_date_response( $topic->post_date_gmt ),
			'guid'                  => array(
				'rendered' => esc_url( bbp_get_topic_permalink( $topic->ID ) ),
				'raw'      => $topic->guid,
			),
			'modified'              => $this->forum_endpoint->prepare_date_response( $topic->post_modified_gmt, $topic->post_modified ),
			'modified_gmt'          => $this->forum_endpoint->prepare_date_response( $topic->post_modified_gmt ),
			'password'              => $topic->post_password,
			'slug'                  => $topic->post_name,
			'status'                => $topic->post_status,
			'link'                  => bbp_get_topic_permalink( $topic->ID ),
			'author'                => (int) $topic->post_author,
			'parent'                => (int) $topic->post_parent,
			'sticky'                => bbp_is_topic_sticky( $topic->ID ),
			'total_reply_count'     => ( bbp_show_lead_topic() ? bbp_get_topic_reply_count( $topic->ID ) : bbp_get_topic_post_count( $topic->ID ) ),
			'last_reply_id'         => bbp_get_topic_last_reply_id( $topic->ID ),
			'last_active_author'    => bbp_get_reply_author_id( bbp_get_topic_last_active_id( $topic->ID ) ),
			'last_active_time'      => $this->forum_endpoint->bbp_rest_get_topic_last_active_time( $topic->ID ),
			'is_closed'             => bbp_is_topic_closed( $topic->ID ),
			'voice_count'           => (int) get_post_meta( $topic->ID, '_bbp_voice_count', true ),
			'forum_id'              => (int) bbp_get_topic_forum_id( $topic->ID ),
			'is_topic_anonymous'    => (int) bbp_is_topic_anonymous( $topic->ID ),
			'anonymous_author_data' => (
				bbp_is_topic_anonymous( $topic->ID )
				? array(
					'name'    => bbp_get_topic_author_display_name( $topic->ID ),
					'email'   => bbp_get_topic_author_email( $topic->ID ),
					'website' => bbp_get_topic_author_url( $topic->ID ),
					'avatar'  => get_avatar_url( bbp_get_topic_author_email( $topic->ID ) ),
				)
				: false
			),
			'classes'               => bbp_get_topic_class( $topic->ID ),
			'title'                 => '',
			'content'               => array(),
			'short_content'         => '',
			'preview_data'          => '',
			'link_embed_url'        => '',
		);

		$data['title'] = array(
			'raw'      => $topic->post_title,
			'rendered' => bbp_get_topic_title( $topic->ID ),
		);

		/* Prepare content */
		if ( ! empty( $topic->post_password ) ) {
			$this->forum_endpoint->prepare_password_response( $topic->post_password );
		}

		$data['short_content'] = wp_trim_excerpt( '', $topic->ID );

		remove_filter( 'bbp_get_topic_content', 'bp_media_forums_embed_gif', 98, 2 );
		remove_filter( 'bbp_get_topic_content', 'bp_media_forums_embed_attachments', 98, 2 );
		remove_filter( 'bbp_get_topic_content', 'bp_video_forums_embed_attachments', 98, 2 );
		remove_filter( 'bbp_get_topic_content', 'bb_forums_link_preview', 999, 2 ); // Removed link preview from content.
		remove_filter( 'bbp_get_topic_content', 'bbp_topic_content_autoembed_paragraph', 99999, 1 ); // Removed link embed from content.
		remove_filter( 'bbp_get_topic_content', 'bp_document_forums_embed_attachments', 999999, 2 );

		$data['content'] = array(
			'raw'      => $topic->post_content,
			'rendered' => bbp_get_topic_content( $topic->ID ),
		);

		add_filter( 'bbp_get_topic_content', 'bp_media_forums_embed_gif', 98, 2 );
		add_filter( 'bbp_get_topic_content', 'bp_media_forums_embed_attachments', 98, 2 );
		add_filter( 'bbp_get_topic_content', 'bp_video_forums_embed_attachments', 98, 2 );
		add_filter( 'bbp_get_topic_content', 'bb_forums_link_preview', 999, 2 ); // Restore link preview to content.
		add_filter( 'bbp_get_topic_content', 'bbp_topic_content_autoembed_paragraph', 99999, 1 ); // Restore link embed to content.
		add_filter( 'bbp_get_topic_content', 'bp_document_forums_embed_attachments', 999999, 2 );

		// Don't leave our cookie lying around: https://github.com/WP-API/WP-API/issues/1055.
		if ( ! empty( $topic->post_password ) ) {
			$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = '';
		}
		/* -- Prepare content */

		// Add iframe embedded data in separate object.
		$link_embed = get_post_meta( $topic->ID, '_link_embed', true );

		if ( ! empty( $link_embed ) ) {

			$data['link_embed_url'] = $link_embed;

			$embed_data = bp_core_parse_url( $link_embed );

			if (
				isset( $embed_data['wp_embed'] ) &&
				$embed_data['wp_embed'] &&
				! empty( $embed_data['description'] )
			) {
				$data['preview_data'] = $embed_data['description'];
				$data['preview_data'] = $this->forum_endpoint->bp_rest_forums_remove_lazyload( $data['preview_data'], $topic->ID );
			}
		} else {
			$data['preview_data'] = bb_forums_link_preview( '', $topic->ID );
		}

		$data['group'] = (
			(
				function_exists( 'bbp_is_forum_group_forum' )
				&& bbp_get_topic_forum_id( $topic->ID )
				&& bbp_is_forum_group_forum( bbp_get_topic_forum_id( $topic->ID ) )
				&& function_exists( 'groups_get_group' )
			)
			? (
				! empty( bbp_get_forum_group_ids( bbp_get_topic_forum_id( $topic->ID ) ) )
				? groups_get_group( current( bbp_get_forum_group_ids( bbp_get_topic_forum_id( $topic->ID ) ) ) )
				: ''
			)
			: ''
		);

		if ( ! empty( $data['group'] ) ) {
			$this->forum_endpoint->group = $data['group'];
		}

		if ( class_exists( 'BBP_Forums_Group_Extension' ) ) {
			$group_forum_extention = new BBP_Forums_Group_Extension();
			// Allow group member to view private/hidden forums.
			add_filter( 'bbp_map_meta_caps', array( $group_forum_extention, 'map_group_forum_meta_caps' ), 10, 4 );

			// Fix issue - Group organizers and moderators can not add topic tags.
			add_filter( 'bbp_map_topic_tag_meta_caps', array( $this->forum_endpoint, 'bb_rest_map_assign_topic_tags_caps' ), 10, 4 );
		}

		add_filter( 'bbp_map_group_forum_topic_meta_caps', array( $this->forum_endpoint, 'bb_rest_map_group_forum_topic_meta_caps' ), 99, 4 );

		// Setup subscribe/unsubscribe state.
		$data['action_states'] = $this->get_topic_action_states( $topic->ID );

		$data['topic_tags'] = $this->get_topic_tags( $topic->ID );

		// current user permission.
		$data['current_user_permissions'] = $this->get_topic_current_user_permissions( $topic->ID );

		remove_filter( 'bbp_map_group_forum_topic_meta_caps', array( $this->forum_endpoint, 'bb_rest_map_group_forum_topic_meta_caps' ), 99, 4 );

		$this->forum_endpoint->group = '';

		// Revisions.
		$data['revisions'] = $this->get_topic_revisions( $topic->ID );

		// Pass group ids for embedded members endpoint.
		$group_ids = '';
		if ( ! empty( $args['post_parent'] ) ) {
			$group = bbp_get_forum_group_ids( $args['post_parent'] );
			if ( ! empty( $group ) ) {
				$group_ids = is_array( $group ) ? implode( ', ', $group ) : $group[0];
			}
		}
		$request['group_id'] = $group_ids;

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $topic, $request ) );

		/**
		 * Filter a component value returned from the API.
		 *
		 * @param WP_REST_Response $response  The Response data.
		 * @param WP_REST_Request  $request   Request used to generate the response.
		 * @param array            $component The component and its values.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_prepare_value', $response, $request, $topic );
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
			'title'      => 'topics',
			'type'       => 'object',
			'properties' => array(
				'id'                       => array(
					'description' => __( 'Unique identifier for the topic.', 'buddyboss' ),
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
					'description' => __( 'The url identifier for the topic.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'GUID for the topic, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'GUID for the topic, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'modified'                 => array(
					'description' => __( 'The date for topic was last modified, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt'             => array(
					'description' => __( 'The date for topic was last modified, as GMT.', 'buddyboss' ),
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
					'description' => __( 'An alphanumeric unique identifier for the topic.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'status'                   => array(
					'description' => __( 'The current status of the topic.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'link'                     => array(
					'description' => __( 'The permalink to this topic on the site.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'format'      => 'uri',
				),
				'author'                   => array(
					'description' => __( 'The ID for the author of the topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'parent'                   => array(
					'description' => __( 'ID of the parent topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'sticky'                   => array(
					'description' => __( 'Whether the topic is sticky or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'total_reply_count'        => array(
					'description' => __( 'Total replies count in the topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'last_reply_id'            => array(
					'description' => __( 'Recently posted reply id into the topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'last_active_author'       => array(
					'description' => __( 'Last updated the user\'s ID in topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'last_active_time'         => array(
					'description' => __( 'Last updated time for the topic.', 'buddyboss' ),
					'type'        => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'is_closed'                => array(
					'description' => __( 'Whether the topic is closed or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'voice_count'              => array(
					'description' => __( 'Voice count of the topic', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'forum_id'                 => array(
					'description' => __( 'Forum ID for the topic.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'is_topic_anonymous'       => array(
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
					'description' => __( 'Classes lists for the topic.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'title'                    => array(
					'description' => __( 'The title of the topic.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the title of the topic, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'The title of the topic, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'content'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The content of the topic.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the topic, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the topic, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'short_content'            => array(
					'description' => __( 'Short Content of the topic.', 'buddyboss' ),
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
				'group'                    => array(
					'description' => __( 'Topic forum\'s group.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
				),
				'action_states'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Available actions with current user for topic.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'subscribed'   => array(
							'description' => __( 'Check whether the current user is subscribed or not in the topic.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'favorited'    => array(
							'description' => __( 'Check whether the topic is favorited or not for the user.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'open'         => array(
							'description' => __( 'Check whether the topic is open or not for the user.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'sticky'       => array(
							'description' => __( 'Check whether the topic is sticky or not for the user.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'super_sticky' => array(
							'description' => __( 'Check whether the topic is super sticky or not for the user.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'spam'         => array(
							'description' => __( 'Check whether the topic status is spam or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'trash'        => array(
							'description' => __( 'Check whether the topic status is trash or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'topic_tags'               => array(
					'description' => __( 'Topic\'s tags', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'current_user_permissions' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current user\'s permission with the topic.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'show_replies' => array(
							'description' => __( 'Whether shows the replies for the current user or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'edit'         => array(
							'description' => __( 'Whether the current user can edit the topic or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'moderate'     => array(
							'description' => __( 'Whether the current user is moderator or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'reply'        => array(
							'description' => __( 'Whether the current user can reply on topic or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'trash'        => array(
							'description' => __( 'Whether the current user can trash a topic or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'revisions'                => array(
					'description' => __( 'Revisions for topic.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
				),
			),
		);

		/**
		 * Filters the topic schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_topic_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                        = parent::get_collection_params();
		$params['context']['default']  = 'view';
		$params['per_page']['default'] = bbp_get_topics_per_page();

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
			'description'       => __( 'An array of topic IDs not to retrieve.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'An array of topic IDs to retrieve.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['offset'] = array(
			'description'       => __( 'The number of topics to offset before retrieval.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Designates ascending or descending order of topics.', 'buddyboss' ),
			'default'           => 'asc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort retrieved topics by parameter.', 'buddyboss' ),
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
					'popular',
					'activity',
					'include'
				),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Limit result set to topic assigned a specific status.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array( 'publish', 'private', 'hidden' ),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['parent'] = array(
			'description'       => __( 'Forum ID to retrieve all the topics.', 'buddyboss' ),
			'default'           => '0',
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['subscriptions'] = array(
			'description'       => __( 'Retrieve subscribed topics by user.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['favorites'] = array(
			'description'       => __( 'Retrieve favorite topics by the current user.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['tag'] = array(
			'description'       => __( 'Search topic with specific tag.', 'buddyboss' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_topics_collection_params', $params );
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

		$form_id = (int) bbp_get_topic_forum_id( $post->ID );

		if ( ! empty( $form_id ) ) {
			$form_base      = sprintf( '/%s/%s/', $this->forum_endpoint->namespace, $this->forum_endpoint->rest_base );
			$links['forum'] = array(
				'href'       => rest_url( $form_base . $form_id ),
				'embeddable' => true,
			);
		}

		if (
			function_exists( 'bbp_is_forum_group_forum' )
			&& bbp_get_topic_forum_id( $post->ID )
			&& bbp_is_forum_group_forum( bbp_get_topic_forum_id( $post->ID ) )
			&& function_exists( 'groups_get_group' )
		) {
			$group = (
				! empty( bbp_get_forum_group_ids( bbp_get_topic_forum_id( $post->ID ) ) )
				? groups_get_group( current( bbp_get_forum_group_ids( bbp_get_topic_forum_id( $post->ID ) ) ) )
				: ''
			);

			$links['group'] = array(
				'href'       => rest_url( sprintf( '/%s/%s/', $this->namespace, 'groups' ) . $group->id ),
				'embeddable' => true,
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array   $links The prepared links of the REST response.
		 * @param WP_Post $post  Post object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_topic_prepare_links', $links, $post );
	}

	/**
	 * Get topic actions state based on current user.
	 *
	 * @param int $topic_id ID of the topic.
	 *
	 * @return array|void
	 */
	public function get_topic_action_states( $topic_id ) {
		if ( empty( $topic_id ) ) {
			return;
		}

		$topic_id = (int) $topic_id;
		$user_id  = bbp_get_user_id( 0, true, true );

		$state = array(
			'subscribed'   => '',
			'favorited'    => '',
			'open'         => bbp_is_topic_open( $topic_id ),
			'sticky'       => bbp_is_topic_sticky( $topic_id ),
			'super_sticky' => bbp_is_topic_super_sticky( $topic_id ),
			'spam'         => bbp_is_topic_spam( $topic_id ),
			'trash'        => bbp_is_topic_trash( $topic_id ),
		);

		if ( bbp_is_favorites_active() && current_user_can( 'edit_user', $user_id ) ) {
			$state['favorited'] = bbp_is_user_favorite( $user_id, $topic_id );
		}

		if ( bb_is_enabled_subscription( 'topic' ) && current_user_can( 'edit_user', $user_id ) ) {
			$state['subscribed'] = bbp_is_user_subscribed( $user_id, $topic_id );
		}

		return $state;
	}

	/**
	 * Topic permissions for the current user.
	 *
	 * @param int $topic_id ID of the topic.
	 *
	 * @return array|void
	 */
	public function get_topic_current_user_permissions( $topic_id ) {
		if ( empty( $topic_id ) ) {
			return;
		}

		$topic   = bbp_get_topic( bbp_get_topic_id( (int) $topic_id ) );
		$form_id = bbp_get_topic_forum_id( $topic_id );
		if ( empty( $form_id ) && ! empty( $topic_id ) ) {
			$form_id = $topic->ID;
		}

		return array(
			'show_replies' => $this->forum_endpoint->can_access_content( $form_id ),
			'edit'         => (
				current_user_can( 'moderate' )
				|| (
					! empty( $topic )
					&& current_user_can( 'edit_topic', $topic->ID )
					&& ! bbp_past_edit_lock( $topic->post_date_gmt )
				)
			),
			'moderate'     => ! empty( $topic ) && current_user_can( 'moderate', $topic_id ),
			'reply'        => $this->can_reply( $topic->ID, $form_id ),
			'trash'        => ! empty( $topic ) && current_user_can( 'delete_topic', $topic->ID ),
		);
	}

	/**
	 * Get Topic Tags.
	 *
	 * @param int $topic_id ID of the topic.
	 *
	 * @return mixed|void
	 */
	public function get_topic_tags( $topic_id ) {

		if ( empty( $topic_id ) ) {
			return;
		}

		// Topic is spammed so display pre-spam terms.
		if ( bbp_is_topic_spam( $topic_id ) ) {

			// Get pre-spam terms.
			$new_terms = get_post_meta( $topic_id, '_bbp_spam_topic_tags', true );

			// If terms exist, explode them and compile the return value.
			if ( empty( $new_terms ) ) {
				$new_terms = '';
			}

			// Topic is not spam so get real terms.
		} else {
			$terms = array_filter( (array) get_the_terms( $topic_id, bbp_get_topic_tag_tax_id() ) );

			// Loop through them.
			foreach ( $terms as $term ) {
				$new_terms[] = $term->name;
			}
		}

		// Set the return value.
		$topic_tags = ( ! empty( $new_terms ) ) ? implode( ', ', $new_terms ) : '';

		return apply_filters( 'bbp_get_form_topic_tags', esc_attr( $topic_tags ) );
	}

	/**
	 * Get revisions for topic.
	 * from: bbp_get_topic_revision_log()
	 *
	 * @param int $topic_id ID of the topic.
	 *
	 * @return bool|void
	 */
	public function get_topic_revisions( $topic_id = 0 ) {
		// Create necessary variables.
		$topic_id     = bbp_get_topic_id( $topic_id );
		$revision_log = bbp_get_topic_raw_revision_log( $topic_id );

		if ( empty( $topic_id ) || empty( $revision_log ) || ! is_array( $revision_log ) ) {
			return false;
		}

		$revisions = bbp_get_topic_revisions( $topic_id );
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
				'author_id'   => bbp_get_topic_author_id( $topic_id ),
				'author_name' => bbp_get_topic_author_display_name( $revision->ID ),
				'avatar'      => ( ! empty( bbp_get_topic_author_id( $topic_id ) ) ? get_avatar_url( bbp_get_topic_author_id( $topic_id ), 300 ) : '' ),
				'reason'      => $reason,
			);
		}

		return apply_filters( 'bp_rest_bbp_get_topic_revision_log', $retval, $topic_id );
	}

	/**
	 * Prepare a single topic for create or update
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return object $topic User object.
	 */
	protected function prepare_topic_for_database( $request ) {
		$topic = new stdClass();

		if ( isset( $request['id'] ) ) {
			$topic->bbp_topic_id = $request['id'];
		}
		if ( isset( $request['parent'] ) ) {
			$topic->bbp_forum_id = $request['parent'];
		}
		if ( isset( $request['group'] ) ) {
			$topic->bbp_group_id = $request['group'];
		}
		if ( isset( $request['title'] ) ) {
			$topic->bbp_topic_title = $request['title'];
		}
		if ( isset( $request['status'] ) ) {
			$topic->bbp_topic_status = $request['status'];
		}
		if ( isset( $request['topic_tags'] ) ) {
			$topic->bbp_topic_tags = $request['topic_tags'];
		}
		if ( isset( $request['content'] ) ) {
			$topic->bbp_topic_content = $request['content'];
		}
		if ( isset( $request['sticky'] ) ) {
			$topic->bbp_stick_topic = $request['sticky'];
		}
		if ( isset( $request['reason_editing'] ) ) {
			$topic->bbp_topic_edit_reason = $request['reason_editing'];
		}
		if ( isset( $request['log'] ) ) {
			$topic->bbp_log_topic_edit = $request['log'];
		}
		if ( isset( $request['subscribe'] ) && ( true === $request['subscribe'] ) ) {
			$topic->bbp_topic_subscription = true;
		} elseif ( isset( $request['subscribe'] ) && ( false === $request['subscribe'] ) ) {
			$topic->bbp_topic_subscription = false;
		}

		/**
		 * Filter topic data before inserting user via REST API
		 *
		 * @param object          $topic   Topic object.
		 * @param WP_REST_Request $request Request object.
		 */
		return apply_filters( 'bp_rest_topic_object', $topic, $request );
	}

	/**
	 * Strip super stickies from the topic query
	 *
	 * @param array $super the super sticky post ID's.
	 *
	 * @return array (empty)
	 */
	public function no_super_stickies( $super = array() ) {
		$super = array();

		return $super;
	}

	/**
	 * Verify if user is able to add topic reply or not.
	 *
	 * @param int $topic_id Topic ID.
	 * @param int $forum_id Forum ID.
	 *
	 * @return bool
	 */
	public function can_reply( $topic_id, $forum_id ) {

		if ( empty( $topic_id ) || empty( $forum_id ) ) {
			return false;
		}

		if ( bbp_is_user_keymaster() ) {
			return true;
		}

		if ( ! bbp_is_topic_closed( $topic_id ) && ! bbp_is_forum_closed( $forum_id ) && bbp_current_user_can_publish_replies() && $this->forum_endpoint->can_access_content( $forum_id, true ) ) {
			return true;
		}

		return false;
	}
}
