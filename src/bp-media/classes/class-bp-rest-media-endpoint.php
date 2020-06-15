<?php
/**
 * BP REST: BP_REST_Media_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Media endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Media_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'media';

		$this->bp_rest_media_support();

		add_filter( 'bp_rest_activity_create_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_activity_update_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );

		add_filter( 'bp_rest_topic_create_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );
		add_filter( 'bp_rest_topic_update_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );
		add_filter( 'bp_rest_reply_create_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );
		add_filter( 'bp_rest_reply_update_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );

		add_filter( 'bp_rest_messages_group_collection_params', array( $this, 'bp_rest_message_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_messages_create_item_query_arguments', array( $this, 'bp_rest_message_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_messages_update_item_query_arguments', array( $this, 'bp_rest_message_query_arguments' ), 99, 3 );
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
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'delete_items_permissions_check' ),
					'args'                => array(
						'media_ids' => array(
							'description' => __( 'A unique numeric IDs for the media.', 'buddyboss' ),
							'type'        => 'array',
							'items'       => array(
								'type' => 'integer',
							),
							'required'    => true,
						),
					),
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
						'description' => __( 'A unique numeric ID for the media.', 'buddyboss' ),
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
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/upload',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_item' ),
					'permission_callback' => array( $this, 'upload_item_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Retrieve medias.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/media Get Photos
	 * @apiName        GetBBPhotos
	 * @apiGroup       Media
	 * @apiDescription Retrieve photos.
	 * @apiVersion     1.0.0
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {String=date_created,menu_order} [orderby=date_created] Order by a specific parameter.
	 * @apiParam {Number} [user_id] Limit result set to items created by a specific user (ID).
	 * @apiParam {Number} [max] Maximum number of results to return.
	 * @apiParam {Number} [album_id] A unique numeric ID for the Album.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the Media's Activity.
	 * @apiParam {Array=public,loggedin,onlyme,friends,grouponly,message} [privacy=public] Privacy of the media.
	 * @apiParam {Array=friends,groups,personal} [scope] Scope of the media.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {Boolean} [count_total=true] Show total count or not.
	 */
	public function get_items( $request ) {
		$args = array(
			'page'        => $request['page'],
			'per_page'    => $request['per_page'],
			'sort'        => $request['order'],
			'order_by'    => $request['orderby'],
			'count_total' => $request['count_total'],
		);

		if ( ! empty( $request['search'] ) ) {
			$args['search_terms'] = $request['search'];
		}

		if ( ! empty( $request['max'] ) ) {
			$args['max'] = $request['max'];
		}

		if ( ! empty( $request['scope'] ) ) {
			$args['scope'] = $request['scope'];
		}

		if ( ! empty( $request['user_id'] ) ) {
			$args['user_id'] = $request['user_id'];
		}

		if ( ! empty( $request['album_id'] ) ) {
			$args['album_id'] = $request['album_id'];
		}

		if ( ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( ! empty( $request['activity_id'] ) ) {
			$args['activity_id'] = $request['activity_id'];
		}

		if ( ! empty( $request['privacy'] ) ) {
			$args['privacy'] = $request['privacy'];
		}

		if ( ! empty( $request['exclude'] ) ) {
			$args['exclude'] = $request['exclude'];
		}

		if ( ! empty( $request['include'] ) ) {
			$args['media_ids'] = $request['include'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_media_get_items_query_args', $args, $request );

		$medias = $this->assemble_response_data( $args );

		$retval = array();
		foreach ( $medias['medias'] as $media ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $medias['total'], $args['per_page'] );

		/**
		 * Fires after a list of members is fetched via the REST API.
		 *
		 * @param array            $media    Fetched medias.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_get_items', $medias, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = true;

		if ( function_exists( 'bp_enable_private_network' ) && true !== bp_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the members `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single media.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 * @api            {GET} /wp-json/buddyboss/v1/media/:id Get Photo
	 * @apiName        GetBBPhoto
	 * @apiGroup       Media
	 * @apiDescription Retrieve a single photo.
	 * @apiVersion     1.0.0
	 * @apiParam {Number} id A unique numeric ID for the media photo.
	 */
	public function get_item( $request ) {

		$id = $request['id'];

		$medias = $this->assemble_response_data( array( 'media_ids' => array( $id ) ) );

		if ( empty( $medias['medias'] ) ) {
			return new WP_Error(
				'bp_rest_media_id',
				__( 'Invalid media ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = '';
		foreach ( $medias['medias'] as $media ) {
			$retval = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a media is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_get_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = true;

		if ( function_exists( 'bp_enable_private_network' ) && true !== bp_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$media = new BP_Media( $request['id'] );

		if ( true === $retval && empty( $media->id ) ) {
			$retval = new WP_Error(
				'bp_rest_media_invalid_id',
				__( 'Invalid media ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the members `get_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create medias.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/media Create Photos
	 * @apiName        CreateBBPhotos
	 * @apiGroup       Media
	 * @apiDescription Create Media Photos.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} upload_ids Media specific IDs.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the activity.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [album_id] A unique numeric ID for the Media Album.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly,message} [privacy=public] Privacy of the media.
	 */
	public function create_item( $request ) {

		$args = array(
			'upload_ids' => $request['upload_ids'],
			'privacy'    => $request['privacy'],
		);

		$args['upload_ids'] = $request['upload_ids'];

		if ( empty( $request['upload_ids'] ) ) {
			return new WP_Error(
				'bp_rest_no_media_found',
				__( 'Sorry, you are not allowed to create a Media item.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( isset( $request['album_id'] ) && ! empty( $request['album_id'] ) ) {
			$args['album_id'] = $request['album_id'];
		}

		if ( isset( $request['activity_id'] ) && ! empty( $request['activity_id'] ) ) {
			$args['activity_id'] = $request['activity_id'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_media_create_items_query_args', $args, $request );

		$medias_ids = $this->bp_rest_create_media( $args );

		if ( is_wp_error( $medias_ids ) ) {
			return $medias_ids;
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => $medias_ids ) );

		$retval = array();
		foreach ( $medias['medias'] as $media ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a Media is created via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_create_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a media.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to create a media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the Media `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_create_items_permissions_check', $retval, $request );
	}

	/**
	 * Update a media.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/media/:id Update Photo
	 * @apiName        UpdateBBPhoto
	 * @apiGroup       Media
	 * @apiDescription Update a single Photo.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the media photo.
	 * @apiParam {Number} [album_id] A unique numeric ID for the Album.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly,message} [privacy] Privacy of the media.
	 */
	public function update_item( $request ) {
		$id = $request['id'];

		$medias = $this->assemble_response_data( array( 'media_ids' => array( $id ) ) );

		if ( empty( $medias['medias'] ) ) {
			return new WP_Error(
				'bp_rest_media_id',
				__( 'Invalid media ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$media = end( $medias['medias'] );

		$args = array(
			'id'            => $media->id,
			'privacy'       => $media->privacy,
			'attachment_id' => $media->attachment_id,
			'group_id'      => $media->group_id,
			'album_id'      => $media->album_id,
		);

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( isset( $request['privacy'] ) && ! empty( $request['privacy'] ) ) {
			$args['privacy'] = $request['privacy'];
		}

		if ( isset( $request['album_id'] ) && ! empty( $request['album_id'] ) ) {
			$args['album_id'] = $request['album_id'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_media_update_items_query_args', $args, $request );

		$id     = $this->bp_rest_create_media( $args );
		$status = true;

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		if ( empty( $id ) ) {
			$status = false;
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => array( $request['id'] ) ) );

		$retval = '';
		foreach ( $medias['medias'] as $media ) {
			$retval = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $request )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'updated' => $status,
				'data'    => $retval,
			)
		);

		/**
		 * Fires after an activity is updated via the REST API.
		 *
		 * @param BP_Activity_Activity $activity The updated activity.
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_activity_update_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a media.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to update this media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$media = new BP_Media( $request['id'] );

		if ( true === $retval && empty( $media->id ) ) {
			$retval = new WP_Error(
				'bp_rest_media_invalid_id',
				__( 'Invalid media ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ! bp_media_user_can_delete( $media ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to update this media.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		/**
		 * Filter the member to `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete multiple medias.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/media/ Delete Photos
	 * @apiName        DeleteBBPhotos
	 * @apiGroup       Media
	 * @apiDescription Delete Multiple Photos.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} media_ids A unique numeric IDs for the media photo.
	 */
	public function delete_items( $request ) {

		$media_ids = $request['media_ids'];

		if ( ! empty( $media_ids ) ) {
			$media_ids = array_unique( $media_ids );
			$media_ids = array_filter( $media_ids );
		}

		if ( empty( $media_ids ) ) {
			return new WP_Error(
				'bp_rest_media_invalid_ids',
				__( 'Invalid media IDs.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => $media_ids ) );

		if ( empty( $medias['medias'] ) ) {
			return new WP_Error(
				'bp_rest_media_ids',
				__( 'Invalid media IDs.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = array();
		foreach ( $medias['medias'] as $media ) {
			$previous[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $request )
			);
		}

		$status = array();

		foreach ( $media_ids as $id ) {
			if ( ! bp_media_user_can_delete( $id ) ) {
				$status[ $id ] = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this media.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} else {
				$status[ $id ] = bp_media_delete( array( 'id' => $id ), true );
			}
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $status,
				'previous' => $previous,
			)
		);

		/**
		 * Fires after medias is deleted via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_delete_items', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to for the user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function delete_items_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to delete this media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && empty( $request['media_ids'] ) ) {
			$retval = new WP_Error(
				'bp_rest_media_invalid_ids',
				__( 'Invalid media IDs.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the members `delete_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_delete_items_permissions_check', $retval, $request );
	}

	/**
	 * Delete a single media.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/media/:id Delete Photo
	 * @apiName        DeleteBBPhoto
	 * @apiGroup       Media
	 * @apiDescription Delete a single Photo.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the media photo.
	 */
	public function delete_item( $request ) {

		$id = $request['id'];

		$medias = $this->assemble_response_data( array( 'media_ids' => array( $id ) ) );

		if ( empty( $medias['medias'] ) ) {
			return new WP_Error(
				'bp_rest_media_id',
				__( 'Invalid media ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = '';
		foreach ( $medias['medias'] as $media ) {
			$previous = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $request )
			);
		}

		if ( ! bp_media_user_can_delete( $id ) ) {
			return WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$status = bp_media_delete( array( 'id' => $id ), true );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $status,
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a media is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_get_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to for the user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to delete this media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$media = new BP_Media( $request['id'] );

		if ( true === $retval && empty( $media->id ) ) {
			$retval = new WP_Error(
				'bp_rest_media_invalid_id',
				__( 'Invalid media ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if ( true === $retval && ! bp_media_user_can_delete( $media ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the members `delete_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Upload Media.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/media/upload Upload Media
	 * @apiName        UploadBBMedia
	 * @apiGroup       Media
	 * @apiDescription Upload Media.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} file File object which is going to upload.
	 */
	public function upload_item( $request ) {

		$file = $request->get_file_params();

		if ( empty( $file ) ) {
			return new WP_Error(
				'bp_rest_media_file_required',
				__( 'Sorry, you have not uploaded any media.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		/**
		 * Create and upload the media file.
		 */
		$upload = bp_media_upload();

		if ( is_wp_error( $upload ) ) {
			return new WP_Error(
				'bp_rest_media_upload_error',
				$upload->get_error_message(),
				array(
					'status' => 400,
				)
			);
		}

		$retval = array(
			'upload_id'    => $upload['id'],
			'upload'       => $upload['url'],
			'upload_thumb' => $upload['thumb'],
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of members is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_media_upload_item', $response, $request );

		return $response;

	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function upload_item_permissions_check( $request ) {

		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to upload media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the members `upload_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_upload_item_permissions_check', $retval, $request );
	}

	/**
	 * Get medias.
	 *
	 * @param array|string $args All arguments and defaults are shared with BP_Media::get(),
	 *                           except for the following.
	 *
	 * @return array
	 */
	public function assemble_response_data( $args ) {

		// Fetch specific media items based on ID's.
		if ( isset( $args['media_ids'] ) && ! empty( $args['media_ids'] ) ) {
			return bp_media_get_specific( $args );

			// Fetch all activity items.
		} else {
			return bp_media_get( $args );
		}
	}

	/**
	 * Select the item schema arguments needed for the CREATABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = array();
		$key  = 'create';

		if ( WP_REST_Server::EDITABLE === $method ) {
			$args['id'] = array(
				'description'       => __( 'A unique numeric ID for the media.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$key = 'update';
		}

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['upload_ids'] = array(
				'description'       => __( 'Media specific IDs.', 'buddyboss' ),
				'default'           => array(),
				'type'              => 'array',
				'required'          => true,
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['activity_id'] = array(
				'description'       => __( 'A unique numeric ID for the activity.', 'buddyboss' ),
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		$args['group_id'] = array(
			'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['album_id'] = array(
			'description'       => __( 'A unique numeric ID for the Media Album.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['privacy'] = array(
			'description'       => __( 'Privacy of the media.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly', 'message' ),
			'default'           => 'public',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_media_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepares activity data for return as an object.
	 *
	 * @param BP_Media        $media   Media data.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $media, $request ) {
		$data = array(
			'id'              => $media->id,
			'blog_id'         => $media->blog_id,
			'attachment_id'   => $media->attachment_id,
			'user_id'         => $media->user_id,
			'title'           => $media->title,
			'album_id'        => $media->album_id,
			'group_id'        => $media->group_id,
			'activity_id'     => $media->activity_id,
			'privacy'         => $media->privacy,
			'menu_order'      => $media->menu_order,
			'date_created'    => $media->date_created,
			'attachment_data' => $media->attachment_data,
			'user_email'      => $media->user_email,
			'user_nicename'   => $media->user_nicename,
			'user_login'      => $media->user_login,
			'display_name'    => $media->display_name,
		);

		$response = rest_ensure_response( $data );

		/**
		 * Filter an activity value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Media         $media    The Media object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_media_prepare_value', $response, $request, $media );
	}


	/**
	 * Get the media schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_media',
			'type'       => 'object',
			'properties' => array(
				'id'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'blog_id'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current Site ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'attachment_id'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Unique identifier for the media object.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_id'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID for the author of the media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'title'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Media title.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'album_id'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Album.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'group_id'        => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'activity_id'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the activity.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'privacy'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Privacy of the media.', 'buddyboss' ),
					'enum'        => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly', 'message' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'menu_order'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Order of the item.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'date_created'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The date the media was created, in the site\'s timezone.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'attachment_data' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Wordpress Media Data.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'full'           => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Media URL with full image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'thumb'          => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Media URL with thumbnail image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'activity_thumb' => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Media URL for the activity image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'meta'           => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Meta items for the media.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'object',
						),
					),
				),
				'user_email'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The user\'s email id to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_nicename'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s nice name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_login'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s login name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'display_name'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s display name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
			),
		);

		/**
		 * Filters the media schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_media_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order media by which attribute.', 'buddyboss' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'date_created', 'menu_order' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit results to friends of a user.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['max'] = array(
			'description'       => __( 'Maximum number of results to return', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['album_id'] = array(
			'description'       => __( 'A unique numeric ID for the Album.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['group_id'] = array(
			'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['activity_id'] = array(
			'description'       => __( 'A unique numeric ID for the Media\'s Activity.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['privacy'] = array(
			'description'       => __( 'Privacy of the media.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly', 'message' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description'       => __( 'Scope of the media.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array( 'friends', 'groups', 'personal' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
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

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific media IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['count_total'] = array(
			'description' => __( 'Show total count or not.', 'buddyboss' ),
			'default'     => true,
			'type'        => 'boolean',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_media_collection_params', $params );
	}

	/**
	 * Create the Media IDs from Upload IDs.
	 *
	 * @param array $args Key value array of query var to query value.
	 *
	 * @return array|WP_Error
	 * @since 0.1.0
	 */
	public function bp_rest_create_media( $args ) {

		$media_privacy = ( ! empty( $args['privacy'] ) ? $args['privacy'] : 'public' );
		$upload_ids    = ( ! empty( $args['upload_ids'] ) ? $args['upload_ids'] : '' );
		$activity_id   = ( ! empty( $args['activity_id'] ) ? $args['activity_id'] : false );
		$user_id       = ( ! empty( $args['user_id'] ) ? $args['user_id'] : get_current_user_id() );
		$id            = ( ! empty( $args['id'] ) ? $args['id'] : '' );

		// Override the privacy if album ID is given.
		if ( ! empty( $args['album_id'] ) ) {
			$albums = bp_album_get_specific( array( 'album_ids' => array( $args['album_id'] ) ) );
			if ( ! empty( $albums['albums'] ) ) {
				$album         = array_pop( $albums['albums'] );
				$media_privacy = $album->privacy;
			}
		}

		// Update Media.
		if ( ! empty( $id ) ) {
			$wp_attachment_id  = $args['attachment_id'];
			$wp_attachment_url = wp_get_attachment_url( $wp_attachment_id );

			// when the file found to be empty it's means it's not a valid attachment.
			if ( empty( $wp_attachment_url ) ) {
				return;
			}

			$media_activity_id = $activity_id;

			// extract the nice title name.
			$title = get_the_title( $wp_attachment_id );

			$media_id = bp_media_add(
				array(
					'id'            => $id,
					'attachment_id' => $wp_attachment_id,
					'title'         => $title,
					'activity_id'   => $media_activity_id,
					'album_id'      => ( ! empty( $args['album_id'] ) ? $args['album_id'] : false ),
					'group_id'      => ( ! empty( $args['group_id'] ) ? $args['group_id'] : false ),
					'privacy'       => $media_privacy,
					'user_id'       => $user_id,
					'error_type'    => 'wp_error',
				)
			);

			if ( is_int( $media_id ) ) {

				// save media is saved in attachment.
				update_post_meta( $wp_attachment_id, 'bp_media_saved', true );

				// save media meta for activity.
				if ( ! empty( $media_activity_id ) ) {
					update_post_meta( $wp_attachment_id, 'bp_media_activity_id', $media_activity_id );
				}

				$created_media_ids[] = $media_id;

			}
		}

		// created Medias.
		if ( ! empty( $upload_ids ) ) {
			foreach ( $upload_ids as $wp_attachment_id ) {
				$wp_attachment_url = wp_get_attachment_url( $wp_attachment_id );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$media_activity_id = false;

				// make an activity for the media.
				if ( bp_is_active( 'activity' ) ) {
					$media_activity_id = bp_activity_post_update(
						array(
							'hide_sitewide' => true,
							'privacy'       => 'media',
						)
					);
					if ( $media_activity_id ) {
						// update activity meta.
						bp_activity_update_meta( $media_activity_id, 'bp_media_activity', '1' );
					}
				}

				// extract the nice title name.
				$title = get_the_title( $wp_attachment_id );

				$media_id = bp_media_add(
					array(
						'attachment_id' => $wp_attachment_id,
						'title'         => $title,
						'activity_id'   => $media_activity_id,
						'album_id'      => ( ! empty( $args['album_id'] ) ? $args['album_id'] : false ),
						'group_id'      => ( ! empty( $args['group_id'] ) ? $args['group_id'] : false ),
						'privacy'       => $media_privacy,
						'user_id'       => $user_id,
						'error_type'    => 'wp_error',
					)
				);

				if ( is_int( $media_id ) ) {

					// save media is saved in attachment.
					update_post_meta( $wp_attachment_id, 'bp_media_saved', true );

					// save media meta for activity.
					if ( ! empty( $activity_id ) ) {
						update_post_meta( $wp_attachment_id, 'bp_media_parent_activity_id', $activity_id );
						update_post_meta( $wp_attachment_id, 'bp_media_activity_id', $media_activity_id );
					}

					$created_media_ids[] = $media_id;

				}
			}
		}

		if ( empty( $created_media_ids ) ) {
			return new WP_Error(
				'bp_rest_media_creation_error',
				__( 'Error creating media, please try again.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		// Link all uploaded media to main activity.
		if ( ! empty( $activity_id ) && empty( $id ) ) {
			$created_media_ids_joined = implode( ',', $created_media_ids );
			bp_activity_update_meta( $activity_id, 'bp_media_ids', $created_media_ids_joined );

			$main_activity = new BP_Activity_Activity( $activity_id );
			if ( ! empty( $main_activity ) ) {
				$main_activity->privacy = $media_privacy;
				$main_activity->save();
			}
		}

		return $created_media_ids;
	}

	/**
	 * Register custom field for the activity api.
	 */
	public function bp_rest_media_support() {
		bp_rest_register_field(
			'activity',      // Id of the BuddyPress component the REST field is about.
			'bp_media_ids', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_media_ids_get_rest_field_callback' ),    // The function to use to get the value of the REST Field.
				'update_callback' => array( $this, 'bp_media_ids_update_rest_field_callback' ), // The function to use to update the value of the REST Field.
				'schema'          => array(                                // The example_field REST schema.
					'description' => 'Activity Medias.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		bp_rest_register_field(
			'activity',      // Id of the BuddyPress component the REST field is about.
			'media_gif', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_gif_data_get_rest_field_callback' ),
				'update_callback' => array( $this, 'bp_gif_data_update_rest_field_callback' ), // The function to use to update the value of the REST Field.
				'schema'          => array(
					'description' => 'Topic Gifs.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		if ( function_exists( 'bp_is_forums_media_support_enabled' ) && true === bp_is_forums_media_support_enabled() ) {
			// Topic Media Photo Support.
			register_rest_field(
				'topics',
				'bbp_media',
				array(
					'get_callback'    => array( $this, 'bbp_media_get_rest_field_callback' ),
					'update_callback' => array( $this, 'bbp_media_update_rest_field_callback' ),
					'schema'          => array(
						'description' => 'Topic Medias.',
						'type'        => 'object',
						'context'     => array( 'embed', 'view', 'edit' ),
					),
				)
			);

			// Reply Media Photo Support.
			register_rest_field(
				'reply',
				'bbp_media',
				array(
					'get_callback'    => array( $this, 'bbp_media_get_rest_field_callback' ),
					'update_callback' => array( $this, 'bbp_media_update_rest_field_callback' ),
					'schema'          => array(
						'description' => 'Topic Medias.',
						'type'        => 'object',
						'context'     => array( 'embed', 'view', 'edit' ),
					),
				)
			);
		}

		if ( function_exists( 'bp_is_forums_gif_support_enabled' ) && true === bp_is_forums_gif_support_enabled() ) {
			// Topic Media Gif Support.
			register_rest_field(
				'topics',
				'bbp_media_gif',
				array(
					'get_callback'    => array( $this, 'bbp_media_gif_get_rest_field_callback' ),
					'update_callback' => array( $this, 'bbp_media_gif_update_rest_field_callback' ),
					'schema'          => array(
						'description' => 'Topic Gifs.',
						'type'        => 'object',
						'context'     => array( 'embed', 'view', 'edit' ),
					),
				)
			);

			// Reply Media Gif Support.
			register_rest_field(
				'reply',
				'bbp_media_gif',
				array(
					'get_callback'    => array( $this, 'bbp_media_gif_get_rest_field_callback' ),
					'update_callback' => array( $this, 'bbp_media_gif_update_rest_field_callback' ),
					'schema'          => array(
						'description' => 'Topic Gifs.',
						'type'        => 'object',
						'context'     => array( 'embed', 'view', 'edit' ),
					),
				)
			);
		}

		if ( function_exists( 'bp_is_messages_media_support_enabled' ) && true === bp_is_messages_media_support_enabled() ) {
			// Messages Media Photo Support.
			bp_rest_register_field(
				'messages',      // Id of the BuddyPress component the REST field is about.
				'bp_media_ids', // Used into the REST response/request.
				array(
					'get_callback'    => array( $this, 'bp_media_ids_get_rest_field_callback_messages' ),
					// The function to use to get the value of the REST Field.
					'update_callback' => array( $this, 'bp_media_ids_update_rest_field_callback_messages' ),
					// The function to use to update the value of the REST Field.
					'schema'          => array(                                // The example_field REST schema.
						'description' => 'Messages Medias.',
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
		}

		if ( function_exists( 'bp_is_messages_gif_support_enabled' ) && true === bp_is_messages_gif_support_enabled() ) {
			// Messages Media Gif Support.
			bp_rest_register_field(
				'messages',      // Id of the BuddyPress component the REST field is about.
				'media_gif', // Used into the REST response/request.
				array(
					'get_callback'    => array( $this, 'bp_gif_data_get_rest_field_callback_messages' ),
					'update_callback' => array( $this, 'bp_gif_data_update_rest_field_callback_messages' ),
					// The function to use to update the value of the REST Field.
					'schema'          => array(                                // The example_field REST schema.
						'description' => 'Message Gifs.',
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
					),
				)
			);
		}
	}

	/**
	 * The function to use to get medias of the activity REST Field.
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_media_ids_get_rest_field_callback( $activity, $attribute ) {
		$activity_id = $activity['id'];

		if ( empty( $activity_id ) ) {
			return;
		}

		$media_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
		$media_ids = trim( $media_ids );
		$media_ids = explode( ',', $media_ids );

		if ( empty( $media_ids ) ) {
			return;
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => $media_ids ) );

		if ( empty( $medias['medias'] ) ) {
			return;
		}

		$retval = array();
		foreach ( $medias['medias'] as $media ) {
			$retval[] = array(
				'id'    => $media->id,
				'full'  => wp_get_attachment_image_url( $media->attachment_id, 'full' ),
				'thumb' => wp_get_attachment_image_url( $media->attachment_id, 'bp-media-thumbnail' ),
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the medias's value of the activity REST Field.
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Activity_Activity object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_media_ids_update_rest_field_callback( $object, $value, $attribute ) {

		if ( 'bp_media_ids' !== $attribute || empty( $object ) ) {
			$value->bp_media_ids = null;
			return $value;
		}

		$privacy = $value->privacy;
		$medias  = wp_parse_id_list( $object );
		if ( empty( $medias ) ) {
			$value->bp_media_ids = null;
			return $value;
		}

		$args = array(
			'upload_ids'  => $medias,
			'privacy'     => $privacy,
			'activity_id' => $value->id,
		);

		if ( ! empty( $value->component ) && 'groups' === $value->component ) {
			$args['group_id'] = $value->item_id;
			$args['privacy']  = 'grouponly';
		}

		$medias_ids = $this->bp_rest_create_media( $args );

		if ( is_wp_error( $medias_ids ) ) {
			$value->bp_media_ids = $medias_ids;
			return $value;
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => $medias_ids ) );

		if ( empty( $medias['medias'] ) ) {
			return;
		}

		$retval = array();
		foreach ( $medias['medias'] as $media ) {
			$retval[] = array(
				'id'    => $media->id,
				'full'  => wp_get_attachment_image_url( $media->attachment_id, 'full' ),
				'thumb' => wp_get_attachment_image_url( $media->attachment_id, 'bp-media-thumbnail' ),
			);
		}

		$value->bp_media_ids = $retval;
		return $value;
	}

	/**
	 * The function to use to get gif data of the activity REST Field.
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_gif_data_get_rest_field_callback( $activity, $attribute ) {
		$activity_id = $activity['id'];

		if ( empty( $activity_id ) ) {
			return;
		}

		$gif_data = bp_activity_get_meta( $activity_id, '_gif_data', true );

		if ( empty( $gif_data ) ) {
			return;
		}

		$preview_url   = wp_get_attachment_url( $gif_data['still'] );
		$video_url     = wp_get_attachment_url( $gif_data['mp4'] );
		$rendered_data = ( function_exists( 'bp_media_activity_embed_gif_content' ) ? bp_media_activity_embed_gif_content( $activity_id ) : '' );

		$retval = array(
			'preview_url' => $preview_url,
			'video_url'   => $video_url,
			'rendered'    => preg_replace( '/[\r\n]*[\t]+/', '', $rendered_data ),
		);

		return $retval;
	}

	/**
	 * The function to use to update the Gif data's value of the activity REST Field.
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Activity_Activity object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_gif_data_update_rest_field_callback( $object, $value, $attribute ) {

		if ( 'media_gif' !== $attribute || empty( $object ) ) {
			return $value;
		}

		$still = $object['url'];
		$mp4   = $object['mp4'];

		if ( ! empty( $still ) && ! empty( $mp4 ) ) {

			$still = bp_media_sideload_attachment( $still );
			$mp4   = bp_media_sideload_attachment( $mp4 );

			bp_activity_update_meta(
				$value->id,
				'_gif_data',
				array(
					'still' => $still,
					'mp4'   => $mp4,
				)
			);

			bp_activity_update_meta(
				$value->id,
				'_gif_raw_data',
				array(
					'still' => $still,
					'mp4'   => $mp4,
				)
			);
		}

		return $value;
	}

	/**
	 * Filter Query argument for the activity for media support.
	 *
	 * @param array  $args   Query arguments.
	 * @param string $method HTTP method of the request.
	 *
	 * @return array
	 */
	public function bp_rest_activity_query_arguments( $args, $method ) {

		$args['bp_media_ids'] = array(
			'description'       => __( 'Media specific IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['media_gif'] = array(
			'description' => __( 'Save gif data into activity', 'buddyboss' ),
			'type'        => 'object',
			'items'       => array( 'type' => 'string' ),
			'default'     => array(
				'url' => '',
				'mp4' => '',
			),
			'properties'  => array(
				'url' => array(
					'description'       => __( 'URL for the gif media from object `480w_still->url`', 'buddyboss' ),
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'mp4' => array(
					'description'       => __( 'Gif file URL from object `original_mp4->mp4`', 'buddyboss' ),
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				),
			),
		);

		return $args;
	}

	/**
	 * Extend the parameters for the Topics and Reply Endpoints.
	 *
	 * @param array $params Query params.
	 *
	 * @return mixed
	 */
	public function bp_rest_forums_collection_params( $params ) {

		if ( function_exists( 'bp_is_forums_media_support_enabled' ) && true === bp_is_forums_media_support_enabled() ) {
			$params['bbp_media'] = array(
				'description'       => __( 'Media specific IDs.', 'buddyboss' ),
				'default'           => array(),
				'type'              => 'array',
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		if ( function_exists( 'bp_is_forums_gif_support_enabled' ) && true === bp_is_forums_gif_support_enabled() ) {
			$params['bbp_media_gif'] = array(
				'description' => __( 'Save gif data into topic.', 'buddyboss' ),
				'type'        => 'object',
				'items'       => array( 'type' => 'string' ),
				'default'     => array(
					'url' => '',
					'mp4' => '',
				),
				'properties'  => array(
					'url' => array(
						'description'       => __( 'URL for the gif media from object `480w_still->url`', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'mp4' => array(
						'description'       => __( 'Gif file URL from object `original_mp4->mp4`', 'buddyboss' ),
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			);
		}

		return $params;
	}

	/**
	 * Filter Query argument for the Messages for media support.
	 *
	 * @param array $params Query arguments.
	 *
	 * @return array
	 */
	public function bp_rest_message_query_arguments( $params ) {

		if ( function_exists( 'bp_is_messages_media_support_enabled' ) && true === bp_is_messages_media_support_enabled() ) {
			$params['bp_media_ids'] = array(
				'description'       => __( 'Media specific IDs.', 'buddyboss' ),
				'default'           => array(),
				'type'              => 'array',
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		if ( function_exists( 'bp_is_messages_gif_support_enabled' ) && true === bp_is_messages_gif_support_enabled() ) {
			$params['media_gif'] = array(
				'description' => __( 'Save gif data into topic.', 'buddyboss' ),
				'type'        => 'object',
				'items'       => array( 'type' => 'string' ),
				'default'     => array(
					'url' => '',
					'mp4' => '',
				),
				'properties'  => array(
					'url' => array(
						'description'       => __( 'URL for the gif media from object `480w_still->url`', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'mp4' => array(
						'description'       => __( 'Gif file URL from object `original_mp4->mp4`', 'buddyboss' ),
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			);
		}

		return $params;
	}

	/**
	 * The function to use to get medias of the topic REST Field.
	 *
	 * @param array  $post      WP_Post object as array.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bbp_media_get_rest_field_callback( $post, $attribute ) {

		$p_id = $post['id'];

		if ( empty( $p_id ) ) {
			return;
		}

		$media_ids = get_post_meta( $p_id, 'bp_media_ids', true );
		$media_ids = trim( $media_ids );
		$media_ids = explode( ',', $media_ids );

		if ( empty( $media_ids ) ) {
			return;
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => $media_ids ) );

		if ( empty( $medias['medias'] ) ) {
			return;
		}

		$retval = array();
		foreach ( $medias['medias'] as $media ) {
			$retval[] = array(
				'id'    => $media->id,
				'full'  => wp_get_attachment_image_url( $media->attachment_id, 'full' ),
				'thumb' => wp_get_attachment_image_url( $media->attachment_id, 'bp-media-thumbnail' ),
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the medias's value of the topic REST Field.
	 *
	 * @param object $object     Value for the schema.
	 * @param object $value      The value of the REST Field to save.
	 *
	 * @return object
	 */
	protected function bbp_media_update_rest_field_callback( $object, $value ) {

		$medias = wp_parse_id_list( $object );
		if ( empty( $medias ) ) {
			$value->bbp_media = null;
			return $value;
		}

		$p_id = $value->ID;

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $p_id, '_bbp_activity_id', true );

		// Get current forum ID.
		if ( 'reply' === $value->post_type ) {
			$forum_id = bbp_get_reply_forum_id( $p_id );
		} else {
			$forum_id = bbp_get_topic_forum_id( $p_id );
		}

		$group_ids = bbp_get_forum_group_ids( $forum_id );
		$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

		// fetch currently uploaded media ids.
		$existing_media                = array();
		$existing_media_ids            = get_post_meta( $p_id, 'bp_media_ids', true );
		$existing_media_attachment_ids = array();
		if ( ! empty( $existing_media_ids ) ) {
			$existing_media_ids = explode( ',', $existing_media_ids );

			foreach ( $existing_media_ids as $existing_media_id ) {
				$existing_media[ $existing_media_id ] = new BP_Media( $existing_media_id );

				if ( ! empty( $existing_media[ $existing_media_id ]->attachment_id ) ) {
					$existing_media_attachment_ids[] = $existing_media[ $existing_media_id ]->attachment_id;
				}
			}
		}

		$args = array(
			'upload_ids'  => $medias,
			'privacy'     => 'public',
			'activity_id' => $main_activity_id,
		);

		if ( ! empty( $group_id ) ) {
			$args['group_id'] = $group_id;
			$args['privacy']  = 'grouponly';
		}

		$medias_ids = $this->bp_rest_create_media( $args );

		if ( is_wp_error( $medias_ids ) ) {
			return;
		}

		$medias_ids = implode( ',', $medias_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $p_id, 'bp_media_ids', $medias_ids );

		// delete medias which were not saved or removed from form.
		if ( ! empty( $existing_media_ids ) ) {
			foreach ( $existing_media_ids as $media_id ) {
				bp_media_delete( array( 'id' => $media_id ) );
			}
		}
	}

	/**
	 * The function to use to get medias gif for the topic REST Field.
	 *
	 * @param array  $post     WP_Post object.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bbp_media_gif_get_rest_field_callback( $post, $attribute ) {

		$p_id = $post['id'];

		if ( empty( $p_id ) ) {
			return;
		}

		$gif_data = get_post_meta( $p_id, '_gif_data', true );

		if ( empty( $gif_data ) ) {
			return;
		}

		$preview_url = wp_get_attachment_url( $gif_data['still'] );
		$video_url   = wp_get_attachment_url( $gif_data['mp4'] );

		$retval = array(
			'preview_url' => $preview_url,
			'video_url'   => $video_url,
			'rendered'    => $this->bp_rest_media_forums_embed_gif( $p_id ),
		);

		return $retval;
	}

	/**
	 * The function to use to update the medias's value of the activity REST Field.
	 *
	 * @param object $object     Topics as a object.
	 * @param object $value      The value of the REST Field to save.
	 */
	protected function bbp_media_gif_update_rest_field_callback( $object, $value ) {

		$still   = ( ! empty( $object ) && array_key_exists( 'url', $object ) ) ? $object['url'] : '';
		$mp4     = ( ! empty( $object ) && array_key_exists( 'mp4', $object ) ) ? $object['mp4'] : '';
		$post_id = $value->ID;

		if ( ! empty( $still ) && ! empty( $mp4 ) ) {

			// save activity id if it is saved in forums and enabled in platform settings.
			$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

			$still = bp_media_sideload_attachment( $still );
			$mp4   = bp_media_sideload_attachment( $mp4 );

			$gdata = array(
				'still' => $still,
				'mp4'   => $mp4,
			);

			update_post_meta( $post_id, '_gif_data', $gdata );
			$gif_data          = $gdata;
			$gif_data['saved'] = true;
			update_post_meta( $post_id, '_gif_raw_data', $gif_data );

			// save media meta for forum.
			if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
				bp_activity_update_meta( $main_activity_id, '_gif_data', $gdata );
				bp_activity_update_meta( $main_activity_id, '_gif_raw_data', $gif_data );
			}
		} else {
			delete_post_meta( $post_id, '_gif_data' );
			delete_post_meta( $post_id, '_gif_raw_data' );
		}
	}

	/**
	 * Get Gif data.
	 * - based on bp_media_forums_embed_gif().
	 *
	 * @param integer $id Forum/Topic/Reply Id.
	 *
	 * @return false|string|void
	 */
	protected function bp_rest_media_forums_embed_gif( $id ) {
		$gif_data = get_post_meta( $id, '_gif_data', true );

		if ( empty( $gif_data ) ) {
			return;
		}

		$preview_url = wp_get_attachment_url( $gif_data['still'] );
		$video_url   = wp_get_attachment_url( $gif_data['mp4'] );

		return '<div class="activity-attached-gif-container">' .
			'<div class="gif-image-container">' .
				'<div class="gif-player">' .
					'<video preload="auto" playsinline poster="' . esc_url( $preview_url ) . '" loop muted playsinline>' .
						'<source src="' . esc_url( $video_url ) . '" type="video/mp4">' .
					'</video>' .
					'<a href="#" class="gif-play-button">' .
						'<span class="dashicons dashicons-video-alt3"></span>' .
					'</a>' .
					'<span class="gif-icon"></span>' .
				'</div>' .
			'</div>' .
		'</div>';
	}

	/**
	 * The function to use to get medias of the messages REST Field.
	 *
	 * @param array  $data      The message value for the REST response.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_media_ids_get_rest_field_callback_messages( $data, $attribute ) {
		$message_id = $data['id'];

		if ( empty( $message_id ) ) {
			return;
		}

		$media_ids = bp_messages_get_meta( $message_id, 'bp_media_ids', true );
		$media_ids = trim( $media_ids );
		$media_ids = explode( ',', $media_ids );

		if ( empty( $media_ids ) ) {
			return;
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => $media_ids ) );

		if ( empty( $medias['medias'] ) ) {
			return;
		}

		$retval = array();
		foreach ( $medias['medias'] as $media ) {
			$retval[] = array(
				'id'    => $media->id,
				'full'  => wp_get_attachment_image_url( $media->attachment_id, 'full' ),
				'thumb' => wp_get_attachment_image_url( $media->attachment_id, 'bp-media-thumbnail' ),
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the medias's value of the messages REST Field.
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Messages_Message object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_media_ids_update_rest_field_callback_messages( $object, $value, $attribute ) {

		if ( 'bp_media_ids' !== $attribute || empty( $object ) ) {
			$value->bp_media_ids = null;
			return $value;
		}

		$message_id = $value->id;

		$medias = wp_parse_id_list( $object );
		if ( empty( $medias ) ) {
			$value->bp_media_ids = null;
			return $value;
		}

		$args = array(
			'upload_ids' => $medias,
			'privacy'    => 'message',
		);

		$medias_ids = $this->bp_rest_create_media( $args );

		if ( is_wp_error( $medias_ids ) ) {
			$value->bp_media_ids = $medias_ids;
			return $value;
		}

		bp_messages_update_meta( $message_id, 'bp_media_ids', implode( ',', $medias_ids ) );
	}

	/**
	 * The function to use to get medias gif for the Messages REST Field.
	 *
	 * @param array $message   The message value for the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_gif_data_get_rest_field_callback_messages( $message ) {

		$message_id = $message['id'];

		if ( empty( $message_id ) ) {
			return;
		}

		$gif_data = bp_messages_get_meta( $message_id, '_gif_data', true );

		if ( empty( $gif_data ) ) {
			return;
		}

		$preview_url = wp_get_attachment_url( $gif_data['still'] );
		$video_url   = wp_get_attachment_url( $gif_data['mp4'] );

		$retval = array(
			'preview_url' => $preview_url,
			'video_url'   => $video_url,
			'rendered'    => $this->bp_rest_media_message_embed_gif( $message_id ),
		);

		return $retval;
	}

	/**
	 * The function to use to update the medias's value of the activity REST Field.
	 *
	 * @param object $object     Message as a object.
	 * @param object $value      The value of the REST Field to save.
	 */
	protected function bp_gif_data_update_rest_field_callback_messages( $object, $value ) {

		$still      = ( ! empty( $object ) && array_key_exists( 'url', $object ) ) ? $object['url'] : '';
		$mp4        = ( ! empty( $object ) && array_key_exists( 'mp4', $object ) ) ? $object['mp4'] : '';
		$message_id = $value->id;

		if ( ! empty( $still ) && ! empty( $mp4 ) ) {

			$still = bp_media_sideload_attachment( $still );
			$mp4   = bp_media_sideload_attachment( $mp4 );

			$gdata = array(
				'still' => $still,
				'mp4'   => $mp4,
			);

			bp_messages_update_meta( $message_id, '_gif_data', $gdata );
			$gif_data          = $gdata;
			$gif_data['saved'] = true;
			bp_messages_update_meta( $message_id, '_gif_raw_data', $gif_data );
		}
	}

	/**
	 * Get Gif data for message.
	 *
	 * @param integer $id Message ID.
	 *
	 * @return false|string|void
	 */
	protected function bp_rest_media_message_embed_gif( $id ) {
		$gif_data = bp_messages_get_meta( $id, '_gif_data', true );

		if ( empty( $gif_data ) ) {
			return;
		}

		$preview_url = wp_get_attachment_url( $gif_data['still'] );
		$video_url   = wp_get_attachment_url( $gif_data['mp4'] );

		return '<div class="activity-attached-gif-container">' .
			'<div class="gif-image-container">' .
				'<div class="gif-player">' .
					'<video preload="auto" playsinline poster="' . esc_url( $preview_url ) . '" loop muted playsinline>' .
						'<source src="' . esc_url( $video_url ) . '" type="video/mp4">' .
					'</video>' .
					'<a href="#" class="gif-play-button">' .
						'<span class="dashicons dashicons-video-alt3"></span>' .
					'</a>' .
					'<span class="gif-icon"></span>' .
				'</div>' .
			'</div>' .
		'</div>';
	}
}
