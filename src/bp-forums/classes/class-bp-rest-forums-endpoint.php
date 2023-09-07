<?php
/**
 * BP REST: BP_REST_Forums_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Forums endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Forums_Endpoint extends WP_REST_Controller {

	/**
	 * Group object.
	 *
	 * @var object|null.
	 */
	public $group;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'forums';
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
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the forum.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/subscribe/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the forum.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve Forums.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/forums Forums
	 * @apiName        GetBBPForums
	 * @apiGroup       Forums
	 * @apiDescription Retrieve forums
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String} [author] Author ID, or comma-separated list of IDs.
	 * @apiParam {Array} [author_exclude] An array of author IDs not to query from.
	 * @apiParam {Array} [exclude] An array of forums IDs not to retrieve.
	 * @apiParam {Array} [include] An array of forums IDs to retrieve.
	 * @apiParam {Number} [offset] The number of forums to offset before retrieval.
	 * @apiParam {String=asc,desc} [order=asc] Designates ascending or descending order of forums.
	 * @apiParam {Array=date,ID,author,title,name,modified,parent,rand,menu_order,relevance,popular,activity,include} [orderby] Sort retrieved forums by parameter..
	 * @apiParam {Array=publish,private,hidden} [status=publish private] Limit result set to forums assigned a specific status.
	 * @apiParam {Number} [parent] Forum ID to retrieve child pages for. Use 0 to only retrieve top-level forums.
	 * @apiParam {Boolean} [subscriptions] Retrieve subscribed forums by user.
	 */
	public function get_items( $request ) {

		$args = array(
			'post_parent'    => ( ! empty( $request['parent'] ) ? $request['parent'] : 0 ),
			'post_status'    => ( ! empty( $request['status'] ) ? $request['status'] : bbp_get_public_status_id() ),
			'orderby'        => ( ! empty( $request['orderby'] ) ? $request['orderby'] : 'menu_order title' ),
			'order'          => ( ! empty( $request['order'] ) ? $request['order'] : 'asc' ),
			'paged'          => ( ! empty( $request['page'] ) ? $request['page'] : '' ),
			'posts_per_page' => ( ! empty( $request['per_page'] ) ? $request['per_page'] : bbp_get_forums_per_page() ),
		);

		if ( ! empty( $request['search'] ) ) {
			$args['s'] = $this->bbp_sanitize_search_request( $request['search'] );
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

		if (
			! empty( $args['orderby'] )
			&& is_array( $args['orderby'] )
		) {
			if ( in_array( 'popular', $args['orderby'], true ) ) {
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_bbp_total_topic_count'; // phpcs:ignore
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
		$args = apply_filters( 'bp_rest_forums_get_items_query_args', $args, $request );

		$bbp_f = bbp_parse_args(
			$args,
			array(
				'post_type'           => bbp_get_forum_post_type(),
				'ignore_sticky_posts' => true,
			),
			'has_forums'
		);

		if ( isset( $request['subscriptions'] ) && ! empty( $request['subscriptions'] ) ) {
			$user_id = (int) ( isset( $args['author'] ) && ! empty( $args['author'] ) ) ? $args['author'] : bbp_get_current_user_id();

			$subscriptions = bbp_get_user_subscribed_forum_ids( $user_id );
			if ( ! empty( $subscriptions ) ) {
				$bbp_f['post__in'] = $subscriptions;
				if ( isset( $args['author'] ) ) {
					unset( $bbp_f['author'] );
				}
			} else {
				$bbp_f = array();
			}
		}

		// Run the query.
		$forums_query = new WP_Query( $bbp_f );

		$forums = ( ! empty( $forums_query->posts ) ? $forums_query->posts : array() );

		$retval = array();
		foreach ( $forums as $forum ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $forum, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $forums_query->found_posts, $args['posts_per_page'] );

		/**
		 * Fires after a list of forums is fetched via the REST API.
		 *
		 * @param array            $forums   Fetched forums.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_forums_get_items', $forums, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list forums.
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
		 * Filter the forums `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_forums_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single Forum.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/forums/:id Forum
	 * @apiName        GetBBPForum
	 * @apiGroup       Forums
	 * @apiDescription Retrieve a single forum
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the forum.
	 */
	public function get_item( $request ) {

		$forum = bbp_get_forum( $request['id'] );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $forum, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of forums is fetched via the REST API.
		 *
		 * @param array            $forum    Fetched forum.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_forum_get_item', $forum, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list forum.
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

		$forum = bbp_get_forum( $request['id'] );

		if ( true === $retval && empty( $forum->ID ) ) {
			$retval = new WP_Error(
				'bp_rest_forum_invalid_id',
				__( 'Invalid forum ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ( ! isset( $forum->post_type ) || 'forum' !== $forum->post_type ) ) {
			$retval = new WP_Error(
				'bp_rest_forum_invalid_id',
				__( 'Invalid forum ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && is_user_logged_in() && isset( $forum->post_type ) ) {
			$post_type = get_post_type_object( $forum->post_type );

			if ( ! current_user_can( $post_type->cap->read_post, $forum->ID ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to access this forum.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the forum `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_forum_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Subscribe/Unsubscribe users for the forum.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * NOTICE: Since 2.2.2, forum subscriptions have been migrated to a
	 * new API for subscribing users to notifications: /wp-json/buddyboss/v1/subscriptions
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/forums/subscribe/:id Subscribe/Unsubscribe Forum
	 * @apiName        GetBBPForumSubscribe
	 * @apiGroup       Forums
	 * @apiDescription Subscribe/Unsubscribe forum for the user.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the forum.
	 */
	public function update_item( $request ) {
		$forum = bbp_get_forum( $request['id'] );

		$user_id = bbp_get_user_id( 0, true, true );

		$success = false;
		$action  = '';

		$is_subscription = bbp_is_user_subscribed( $user_id, $forum->ID );
		if ( true === $is_subscription ) {
			$success = bbp_remove_user_subscription( $user_id, $forum->ID );
			$action  = 'unsubscribe';
		} elseif ( false === $is_subscription ) {
			$success = (bool) bbp_add_user_subscription( $user_id, $forum->ID );
			$action  = 'subscribe';
		}

		// Do additional subscriptions actions.
		do_action( 'bbp_subscriptions_handler', $success, $user_id, $forum->ID, $action );

		$retval['update'] = $success;

		$retval['data'] = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $forum, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a forum has been updated and fetched via the REST API.
		 *
		 * @param array            $forum    Fetched forum.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_forum_update_item', $forum, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a forum.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to subscribe/unsubscribe the forum.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$forum  = bbp_get_forum( $request->get_param( 'id' ) );

			if ( ! bb_is_enabled_subscription( 'forum' ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Subscription was disabled.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				empty( $forum->ID ) ||
				! isset( $forum->post_type ) ||
				'forum' !== $forum->post_type
			) {
				$retval = new WP_Error(
					'bp_rest_forum_invalid_id',
					__( 'Invalid forum ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}

			$user_id = bbp_get_user_id( 0, true, true );

			if ( true === $retval && ! current_user_can( 'edit_user', $user_id ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'You don\'t have the permission to update favorites.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the forum `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_forum_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares component data for return as an object.
	 *
	 * @param array           $forum   The component and its values.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $forum, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		// Base fields for every post.
		$data = array(
			'id'                 => $forum->ID,
			'date'               => $this->prepare_date_response( $forum->post_date_gmt, $forum->post_date ),
			'date_gmt'           => $this->prepare_date_response( $forum->post_date_gmt ),
			'guid'               => array(
				'rendered' => apply_filters( 'get_the_guid', $forum->guid ),
				'raw'      => $forum->guid,
			),
			'modified'           => $this->prepare_date_response( $forum->post_modified_gmt, $forum->post_modified ),
			'modified_gmt'       => $this->prepare_date_response( $forum->post_modified_gmt ),
			'password'           => $forum->post_password,
			'slug'               => $forum->post_name,
			'status'             => $forum->post_status,
			'link'               => bbp_get_forum_permalink( $forum->ID ),
			'author'             => (int) $forum->post_author,
			'parent'             => (int) $forum->post_parent,
			'menu_order'         => (int) $forum->menu_order,
			'sticky'             => is_sticky( $forum->ID ),
			'featured_media'     => array(),
			'total_topic_count'  => (int) get_post_meta( $forum->ID, '_bbp_total_topic_count', true ),
			'last_topic_id'      => (int) get_post_meta( $forum->ID, '_bbp_last_topic_id', true ),
			'total_reply_count'  => (int) get_post_meta( $forum->ID, '_bbp_total_reply_count', true ),
			'last_reply_id'      => (int) get_post_meta( $forum->ID, '_bbp_last_reply_id', true ),
			'last_active_author' => $this->get_forum_last_active_author_id( $forum->ID ),
			'last_active_time'   => $this->get_forum_last_active_time( $forum->ID ),
			'is_closed'          => bbp_is_forum_closed( $forum->ID ),
			'is_forum_category'  => bbp_is_forum_category( $forum->ID ),
		);

		$data['featured_media']['full'] = (string) (
			function_exists( 'bbp_get_forum_thumbnail_src' )
			? bbp_get_forum_thumbnail_src( $forum->ID, 'full', 'full' )
			: get_the_post_thumbnail_url( $forum->ID, 'full' )
		);

		$data['featured_media']['thumb'] = (string) (
			function_exists( 'bbp_get_forum_thumbnail_src' )
			? bbp_get_forum_thumbnail_src( $forum->ID, 'thumbnail', 'large' )
			: get_the_post_thumbnail_url( $forum->ID, 'thumbnail' )
		);

		$data['title'] = array(
			'raw'      => $forum->post_title,
			'rendered' => bbp_get_forum_title( $forum->ID ),
		);

		/* Prepare content */
		if ( ! empty( $forum->post_password ) ) {
			$this->prepare_password_response( $forum->post_password );
		}

		$data['short_content'] = wp_trim_excerpt( '', $forum->ID );

		$content = apply_filters( 'the_content', $forum->post_content );

		$data['content'] = array(
			'raw'      => $forum->post_content,
			'rendered' => $content,
		);

		// Don't leave our cookie lying around: https://github.com/WP-API/WP-API/issues/1055.
		if ( ! empty( $forum->post_password ) ) {
			$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = '';
		}
		/* -- Prepare content */

		$data['group'] = (
			(
				function_exists( 'bbp_is_forum_group_forum' )
				&& bbp_is_forum_group_forum( $forum->ID )
				&& function_exists( 'groups_get_group' )
			)
			? $this->bp_rest_get_group( $forum->ID )
			: ''
		);

		if ( ! empty( $data['group'] ) ) {
			$this->group = $data['group'];
		}

		if ( class_exists( 'BBP_Forums_Group_Extension' ) ) {
			$group_forum_extention = new BBP_Forums_Group_Extension();
			// Allow group member to view private/hidden forums.
			add_filter( 'bbp_map_meta_caps', array( $group_forum_extention, 'map_group_forum_meta_caps' ), 10, 4 );

			// Fix issue - Group organizers and moderators can not add topic tags.
			add_filter( 'bbp_map_topic_tag_meta_caps', array( $this, 'bb_rest_map_assign_topic_tags_caps' ), 10, 4 );
		}

		add_filter( 'bbp_map_group_forum_topic_meta_caps', array( $this, 'bb_rest_map_group_forum_topic_meta_caps' ), 99, 4 );

		// Setup subscribe/unsubscribe state.
		$data['action_states'] = $this->get_forum_action_states( $forum->ID );

		// current user permission.
		$data['user_permission'] = $this->get_forum_current_user_permissions( $forum->ID );

		remove_filter( 'bbp_map_group_forum_topic_meta_caps', array( $this, 'bb_rest_map_group_forum_topic_meta_caps' ), 99, 4 );

		$this->group = '';

		$data['sub_forums'] = $this->get_sub_forums(
			array(
				'post_parent'    => $forum->ID,
				'posts_per_page' => - 1,
			)
		);

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $forum ) );

		/**
		 * Filter a component value returned from the API.
		 *
		 * @param WP_REST_Response $response  The Response data.
		 * @param WP_REST_Request  $request   Request used to generate the response.
		 * @param array            $component The component and its values.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_forums_prepare_value', $response, $request, $forum );
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
			'title'      => 'bp_forums',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => __( 'Unique identifier for the Forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'date'               => array(
					'description' => __( 'The date the object was published, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'date_gmt'           => array(
					'description' => __( 'The date the object was published, as GMT.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'guid'               => array(
					'description' => __( 'The url identifier for the forum.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'GUID for the forum, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'GUID for the forum, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'modified'           => array(
					'description' => __( 'The date for forum was last modified, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt'       => array(
					'description' => __( 'The date for forum was last modified, as GMT.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'password'           => array(
					'description' => __( 'A password to protect access to the post.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'slug'               => array(
					'description' => __( 'An alphanumeric unique identifier for the forum.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'status'             => array(
					'description' => __( 'The current status of the forum.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'link'               => array(
					'description' => __( 'The permalink to this forum on the site.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'format'      => 'uri',
				),
				'author'             => array(
					'description' => __( 'The ID for the author of the forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'parent'             => array(
					'description' => __( 'ID of the parent forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'menu_order'         => array(
					'description' => __( 'Menu order for the forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'sticky'             => array(
					'description' => __( 'Whether the Forum is sticky or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'featured_media'     => array(
					'description' => __( 'Featured Image URLs for the forum.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'full'  => array(
							'description' => __( 'Forum featured image URL with full image size.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'thumb' => array(
							'description' => __( 'Forum featured image URL with thumbnail image size.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'total_topic_count'  => array(
					'description' => __( 'Total topics count in the forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'last_topic_id'      => array(
					'description' => __( 'Recently edited topic id into the forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'total_reply_count'  => array(
					'description' => __( 'Total replies count in the forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'last_reply_id'      => array(
					'description' => __( 'Recently posted reply id into the forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'last_active_author' => array(
					'description' => __( 'Last updated the user\'s ID in forum.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'last_active_time'   => array(
					'description' => __( 'Last updated time for the forum.', 'buddyboss' ),
					'type'        => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'is_closed'          => array(
					'description' => __( 'Whether the Forum is closed or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'is_forum_category'  => array(
					'description' => __( 'Whether the Forum is assigned as category or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'title'              => array(
					'description' => __( 'The title of the forum.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the title of the forum, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'The title of the forum, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'short_content'      => array(
					'description' => __( 'Short Content of the forum.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'content'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The content of the forum.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'Content for the Forum, as it exists in the database.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'rendered' => array(
							'description' => __( 'HTML content for the Forum, transformed for display.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'group'              => array(
					'description' => __( 'Forum\'s group.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
				),
				'action_states'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Available actions with current user for Forum.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'subscribed' => array(
							'description' => __( 'Check whether the current user is subscribed or not in the forum.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'user_permission'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current user\'s permission with the Forum.', 'buddyboss' ),
					'type'        => 'object',
					'properties'  => array(
						'show_topics'   => array(
							'description' => __( 'Whether shows the topics for the current user or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'show_subforum' => array(
							'description' => __( 'Whether shows the sub-forums for the current user or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
						'create_topic'  => array(
							'description' => __( 'Whether the current user can create a topic in the forum or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
						),
					),
				),
				'sub_forums'         => array(
					'description' => __( 'Child forums with current forum.', 'buddyboss' ),
					'context'     => array( 'embed', 'view', 'edit' ),
					'type'        => 'object',
				),
			),
		);

		/**
		 * Filters the forums schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_forum_schema', $this->add_additional_fields_schema( $schema ) );
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
		$params['per_page']['default'] = ( function_exists( 'bbp_get_forums_per_page' ) ? bbp_get_forums_per_page() : get_option( '_bbp_forums_per_page', 15 ) );

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
			'description'       => __( 'An array of forums IDs not to retrieve.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'An array of forums IDs to retrieve.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['offset'] = array(
			'description'       => __( 'The number of forums to offset before retrieval.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['order'] = array(
			'description'       => __( 'Designates ascending or descending order of forums.', 'buddyboss' ),
			'default'           => 'asc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort retrieved forums by parameter.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'ID',
					'author',
					'title',
					'name',
					'modified',
					'parent',
					'rand',
					'menu_order',
					'relevance',
					'popular',
					'activity',
					'include',
				),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['status'] = array(
			'description'       => __( 'Limit result set to forums assigned a specific status.', 'buddyboss' ),
			'default'           => array( 'publish', 'private' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array( 'publish', 'private', 'hidden' ),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['parent'] = array(
			'description'       => __( 'Forum ID to retrieve child pages for. Use 0 to only retrieve top-level forums.', 'buddyboss' ),
			'default'           => '0',
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['subscriptions'] = array(
			'description'       => __( 'Retrieve subscribed forums by user.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_forums_collection_params', $params );
	}

	/**
	 * Check the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @param string      $date_gmt GMT date format.
	 * @param string|null $date     forum date.
	 *
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	public function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
		}

		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Return the formatted datetime.
		return mysql_to_rfc3339( $date_gmt ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
	}

	/**
	 * Prepare response for the password protected posts.
	 *
	 * @param string $password WP_Post password.
	 *
	 * @return mixed
	 */
	public function prepare_password_response( $password ) {
		if ( ! empty( $password ) ) {
			/**
			 * Fake the correct cookie to fool post_password_required().
			 * Without this, get_the_content() will give a password form.
			 */
			require_once ABSPATH . 'wp-includes/class-phpass.php';

			$hasher                                 = new PasswordHash( 8, true );
			$value                                  = $hasher->HashPassword( $password );
			$_COOKIE[ 'wp-postpass_' . COOKIEHASH ] = wp_slash( $value );
		}

		return $password;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $post ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $post->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $post->post_author ) ),
				'embeddable' => true,
			),
		);

		if (
			function_exists( 'bbp_is_forum_group_forum' )
			&& function_exists( 'bb_get_child_forum_group_ids' )
			&& function_exists( 'groups_get_group' )
			&& ! empty( bb_get_child_forum_group_ids( $post->ID ) )
		) {
			$group          = $this->bp_rest_get_group( $post->ID );
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
		return apply_filters( 'bp_rest_forum_prepare_links', $links, $post );
	}

	/**
	 * Last active author for the forum.
	 *
	 * @param int $forum_id ID of the forum.
	 *
	 * @return int
	 */
	public function get_forum_last_active_author_id( $forum_id ) {

		if ( empty( $forum_id ) ) {
			return 0;
		}

		$last_id = get_post_meta( $forum_id, '_bbp_last_active_id', true );
		if ( ! empty( $last_id ) ) {
			$post = bbp_get_forum( $last_id );

			return ( ! empty( $post ) && ! empty( $post->post_author ) ) ? $post->post_author : 0;
		}

		return 0;
	}

	/**
	 * Last active time for forum.
	 *
	 * @param int $forum_id ID of the forum.
	 *
	 * @return string
	 */
	public function get_forum_last_active_time( $forum_id ) {
		$last_active = get_post_meta( $forum_id, '_bbp_last_active_time', true );
		if ( empty( $last_active ) ) {
			$reply_id = bbp_get_forum_last_reply_id( $forum_id );
			if ( ! empty( $reply_id ) ) {
				$last_active = get_post_field( 'post_date', $reply_id );
			} else {
				$topic_id = bbp_get_forum_last_topic_id( $forum_id );
				if ( ! empty( $topic_id ) ) {
					$last_active = $this->bbp_rest_get_topic_last_active_time( $topic_id );
				} else {
					$last_active = get_post_field( 'post_date', $forum_id );
				}
			}
		}

		return get_gmt_from_date( $last_active );
	}

	/**
	 * Last active time for Topic.
	 *
	 * @param int $topic_id Topic ID.
	 *
	 * @return mixed|void
	 */
	public function bbp_rest_get_topic_last_active_time( $topic_id = 0 ) {
		$topic_id = bbp_get_topic_id( $topic_id );

		// Try to get the most accurate freshness time possible.
		$last_active = get_post_meta( $topic_id, '_bbp_last_active_time', true );
		if ( empty( $last_active ) ) {
			$reply_id = bbp_get_topic_last_reply_id( $topic_id );
			if ( ! empty( $reply_id ) ) {
				$last_active = get_post_field( 'post_date', $reply_id );
			} else {
				$last_active = get_post_field( 'post_date', $topic_id );
			}
		}

		return apply_filters( 'bbp_rest_get_topic_last_active_time', get_gmt_from_date( $last_active ), $topic_id );
	}

	/**
	 * Get forum action states based on current user.
	 *
	 * @param int $forum_id ID of the forum.
	 *
	 * @return array|void
	 */
	public function get_forum_action_states( $forum_id ) {
		if ( empty( $forum_id ) ) {
			return;
		}

		$forum_id = (int) $forum_id;
		$user_id  = bbp_get_user_id( 0, true, true );

		$state = array(
			'subscribed' => false,
		);

		if ( bb_is_enabled_subscription( 'forum' ) && current_user_can( 'edit_user', $user_id ) ) {
			$state['subscribed'] = bbp_is_user_subscribed( $user_id, $forum_id );
		}

		return $state;
	}

	/**
	 * Forum permissions for the current user.
	 *
	 * @param int $forum_id ID of the forum.
	 *
	 * @return array|void
	 */
	public function get_forum_current_user_permissions( $forum_id ) {
		if ( empty( $forum_id ) ) {
			return;
		}

		$forum = bbp_get_forum( bbp_get_forum_id( (int) $forum_id ) );

		return array(
			'show_topics'   => $this->can_access_content( $forum_id ),
			'show_subforum' => $this->can_access_content( $forum_id ),
			'create_topic'  => (
				! empty( $forum )
				&& ! bbp_is_forum_category()
				&& ( bbp_current_user_can_publish_topics() || bbp_current_user_can_access_anonymous_user_form() )
				&& $this->can_access_content( $forum_id, true )
				&& ( ! bbp_is_user_keymaster() ? bbp_is_forum_open( (int) $forum_id ) : true )
			),
		);
	}

	/**
	 * Check current access permission.
	 *
	 * @param int  $forum_id ID of the forum.
	 * @param bool $create   force validate.
	 *
	 * @return bool
	 */
	public function can_access_content( $forum_id, $create = false ) {

		if (
			function_exists( 'bbp_is_forum_group_forum' )
			&& bbp_is_forum_group_forum( $forum_id )
		) {
			$group_ids = bbp_get_forum_group_ids( $forum_id );
			$user_id   = get_current_user_id();
			$flag      = false;
			if ( ! empty( $group_ids ) ) {
				foreach ( $group_ids as $group_id ) {

					if ( function_exists( 'bp_is_active' ) && bp_is_active( 'groups' ) ) {

						// if we checking access for showing forums, topic and replies then we need to check group is public or not.
						if ( ! $create ) {
							$group = groups_get_group( $group_id );
							if ( 'public' === $group->status ) {
								$flag = true;
							}
						}

						if (
							groups_is_user_member( $user_id, $group_id )
							|| groups_is_user_mod( $user_id, $group_id )
							|| groups_is_user_admin( $user_id, $group_id )
							|| bbp_is_user_keymaster( $user_id )
						) {
							$flag = true;
						}
					}
				}
			}

			return $flag;

		} elseif (
			empty( $forum_id )
			|| ! bbp_user_can_view_forum(
				array(
					'forum_id' => $forum_id,
				)
			)
		) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Get sub forums.
	 *
	 * @param array $args array for the parameters.
	 *
	 * @return array|void
	 */
	public function get_sub_forums( $args ) {
		$sub_forums = bbp_forum_get_subforums( $args );

		if ( empty( $sub_forums ) ) {
			return;
		}

		$retval = array();
		foreach ( $sub_forums as $sub_forum ) {
			$retval[] = array(
				'id'    => $sub_forum->ID,
				'slug'  => $sub_forum->post_name,
				'title' => bbp_get_forum_title( $sub_forum->ID ),
				'count' => array(
					'topic' => bbp_get_forum_topic_count( $sub_forum->ID ),
					'reply' => bbp_get_forum_reply_count( $sub_forum->ID ),
				),
				'link'  => bbp_get_forum_permalink( $sub_forum->ID ),
				'group' => (
					(
						function_exists( 'bbp_is_forum_group_forum' )
						&& bbp_is_forum_group_forum( $sub_forum->ID )
						&& function_exists( 'groups_get_group' )
					)
					? (
						bbp_get_forum_group_ids( $sub_forum->ID )
						? groups_get_group( current( bbp_get_forum_group_ids( $sub_forum->ID ) ) )
						: ''
					)
					: ''
				),
			);
		}

		return $retval;
	}

	/**
	 * Get Forum's group.
	 *
	 * @param int $forum_id ID of the forum.
	 *
	 * @return BP_Groups_Group|string
	 */
	public function bp_rest_get_group( $forum_id ) {
		if ( empty( $forum_id ) ) {
			return '';
		}

		if ( function_exists( 'bb_get_child_forum_group_ids' ) ) {
			$group_ids = bb_get_child_forum_group_ids( $forum_id );
		} else {
			$group_ids = bbp_get_forum_group_ids( $forum_id );
		}

		if ( ! empty( $group_ids ) ) {
			$group              = groups_get_group( current( $group_ids ) );
			$group->avatar_urls = array();
			if ( ! bp_disable_group_avatar_uploads() ) {
				$group->avatar_urls = array(
					'thumb' => bp_core_fetch_avatar(
						array(
							'html'    => false,
							'object'  => 'group',
							'item_id' => $group->id,
							'type'    => 'thumb',
						)
					),
					'full'  => bp_core_fetch_avatar(
						array(
							'html'    => false,
							'object'  => 'group',
							'item_id' => $group->id,
							'type'    => 'full',
						)
					),
				);
			}

			return $group;
		}

		return '';

	}

	/**
	 * Allow group members to have advanced priviledges in group forum topics.
	 *
	 * @param array  $caps    Array of user caps.
	 * @param string $cap     Capablility name to check.
	 * @param int    $user_id User ID for capability check.
	 * @param array  $args    Array of arguments.
	 *
	 * @return array
	 */
	public function bb_rest_map_group_forum_topic_meta_caps( $caps, $cap, $user_id, $args ) {
		if (
			empty( $this->group ) ||
			empty( $this->group->id ) ||
			empty( $user_id )
		) {
			return $caps;
		}

		switch ( $cap ) {

			// If user is a group mmember, allow them to create content.
			case 'read_forum':
			case 'publish_replies':
			case 'publish_topics':
			case 'read_hidden_forums':
			case 'read_private_forums':
				if (
					groups_is_user_member( $user_id, $this->group->id ) ||
					groups_is_user_mod( $user_id, $this->group->id ) ||
					groups_is_user_admin( $user_id, $this->group->id )
				) {
					$caps = array( 'participate' );
				}
				break;

			// If user is a group mod ar admin, map to participate cap.
			case 'moderate':
			case 'edit_topic':
			case 'edit_reply':
			case 'view_trash':
			case 'edit_others_replies':
			case 'edit_others_topics':
				if (
					groups_is_user_mod( $user_id, $this->group->id ) ||
					groups_is_user_admin( $user_id, $this->group->id )
				) {
					$caps = array( 'participate' );
				}
				break;

			// If user is a group admin, allow them to delete topics and replies.
			case 'delete_topic':
			case 'delete_reply':
				if ( groups_is_user_admin( $user_id, $this->group->id ) ) {
					$caps = array( 'participate' );
				}
				break;
		}

		return apply_filters( 'bb_rest_map_group_forum_topic_meta_caps', $caps, $cap, $user_id, $args );
	}

	/**
	 * Fix issue - Group organizers and moderators can not add topic tags.
	 * - from bbp_map_assign_topic_tags_caps();
	 *
	 * @param array  $caps    List of capabilities.
	 * @param string $cap     Capability name.
	 * @param int    $user_id User ID.
	 * @param array  $args    List of Arguments.
	 *
	 * @return array
	 */
	public function bb_rest_map_assign_topic_tags_caps( $caps, $cap, $user_id, $args ) {
		if (
			'assign_topic_tags' !== $cap ||
			empty( $this->group ) ||
			empty( $this->group->id ) ||
			empty( $user_id )
		) {
			return $caps;
		}

		if (
			groups_is_user_mod( $user_id, $this->group->id ) ||
			groups_is_user_admin( $user_id, $this->group->id )
		) {
			return array( 'participate' );
		}
	}

	/**
	 * Removed lazyload from link preview embed.
	 *
	 * @param string $content Topic or reply content.
	 * @param int    $post_id Topic or reply id.
	 *
	 * @return string $content
	 */
	public function bp_rest_forums_remove_lazyload( $content, $post_id ) {
		$link_embed = get_post_meta( $post_id, '_link_embed', true );

		if ( empty( $link_embed ) ) {
			return $content;
		}

		$content = preg_replace( '/iframe(.*?)data-lazy-type="iframe"/is', 'iframe$1', $content );
		$content = preg_replace( '/iframe(.*?)class="lazy/is', 'iframe$1class="', $content );
		$content = preg_replace( '/iframe(.*?)data-src=/is', 'iframe$1src=', $content );

		return $content;
	}

	/**
	 * Sanitize a query argument used to pass some search terms.
	 * Accepts a single parameter to be used for forums, topics, or replies.
	 *
	 * @param string $terms Search Term.
	 *
	 * @return string
	 */
	public function bbp_sanitize_search_request( $term ) {
		$retval = ! empty( $term ) && is_string( $term ) ? urldecode( trim( $term ) ) : '';

		// Filter & return.
		return apply_filters( 'bbp_sanitize_search_request', $retval, $term );
	}
}
