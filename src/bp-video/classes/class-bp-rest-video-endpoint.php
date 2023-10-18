<?php
/**
 * BP REST: BP_REST_Video_Endpoint class
 *
 * @package BuddyBoss
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Video endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Video_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Media_Endpoint endpoint
	 *
	 * @var string
	 */
	public $media_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace      = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base      = 'video';
		$this->media_endpoint = new BP_REST_Media_Endpoint();

		$this->bp_rest_video_support();

		add_filter( 'bp_rest_activity_create_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_activity_update_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_activity_comment_create_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );

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
			'/' . $this->rest_base . '/upload',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_item' ),
					'permission_callback' => array( $this, 'upload_item_permissions_check' ),
				),
			)
		);

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
						'description' => __( 'A unique numeric ID for the video.', 'buddyboss' ),
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
	}

	/**
	 * Upload Video.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since          0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/video/upload Upload Video
	 * @apiName        UploadBBVideo
	 * @apiGroup       Video
	 * @apiDescription Upload Video.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} file File object which is going to upload.
	 */
	public function upload_item( $request ) {

		if ( 'messages' === $request->get_param( 'component' ) ) {
			$_POST['component'] = 'messages';
		}

		$file = $request->get_file_params();

		if ( empty( $file ) ) {
			return new WP_Error(
				'bp_rest_video_file_required',
				__( 'Sorry, you have not uploaded any video.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if (
			isset( $file['file']['size'] ) &&
			function_exists( 'bp_video_allowed_upload_video_size' ) &&
			$file['file']['size'] > bp_video_allowed_upload_video_size() * 1048576
		) {
			return new WP_Error(
				'bp_rest_max_upload_size',
				sprintf(
				/* translators: 1: File size, 2: Allowed size. */
					__( 'File is too large (%1$s MB). Max file size: %2$s MB.', 'buddyboss' ),
					round( $file['file']['size'] / 1048576, 1 ),
					bp_video_allowed_upload_video_size()
				),
				array(
					'status' => 400,
				)
			);
		}

		add_filter( 'upload_dir', 'bp_video_upload_dir_script' );

		/**
		 * Create and upload the video file.
		 */
		$upload = bp_video_upload();

		remove_filter( 'upload_dir', 'bp_video_upload_dir_script' );

		if ( is_wp_error( $upload ) ) {
			return new WP_Error(
				'bp_rest_video_upload_error',
				$upload->get_error_message(),
				array(
					'status' => 400,
				)
			);
		}

		if ( isset( $request['preview'] ) && '' !== $request['preview'] ) {
			$data = array(
				'id'         => $upload['id'],
				'js_preview' => $request['preview'],
			);
			bp_video_preview_image_by_js( $data );
		}

		$retval = array(
			'upload_id' => $upload['id'],
			'name'      => $upload['name'],
			'url'       => $upload['url'],
			'ext'       => $upload['ext'],
		);

		if ( 'messages' === $request->get_param( 'component' ) && isset( $upload['vid_msg_url'] ) ) {
			$retval['vid_msg_url'] = $upload['vid_msg_url'];
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of video is uploaded via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_video_upload_item', $response, $request );

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

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to upload video.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the video `upload_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_video_upload_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve videos.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since          0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/video Get Videos
	 * @apiName        GetBBVideos
	 * @apiGroup       Video
	 * @apiDescription Retrieve videos.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {String=date_created,menu_order,id,include} [orderby=date_created] Order by a specific parameter.
	 * @apiParam {Number} [user_id] Limit result set to items created by a specific user (ID).
	 * @apiParam {Number} [max] Maximum number of results to return.
	 * @apiParam {Number} [album_id] A unique numeric ID for the Album.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the Video's Activity.
	 * @apiParam {Array=public,loggedin,onlyme,friends,grouponly} [privacy=public] Privacy of the video.
	 * @apiParam {Array=friends,groups,personal} [scope] Scope of the video.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {Boolean} [count_total=true] Show total count or not.
	 */
	public function get_items( $request ) {
		$args = array(
			'page'        => $request['page'],
			'per_page'    => $request['per_page'],
			'sort'        => strtoupper( $request['order'] ),
			'order_by'    => $request['orderby'],
			'count_total' => $request['count_total'],
			'scope'       => array(),
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

		if ( isset( $request['album_id'] ) && '' !== $request['album_id'] ) {
			$args['album_id'] = $request['album_id'];
		}

		if ( ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( ! empty( $request['activity_id'] ) ) {
			$args['activity_id'] = $request['activity_id'];
		}

		if ( ! empty( $request['message_id'] ) ) {
			$args['message_id'] = $request['message_id'];
		}

		if ( ! empty( $request['privacy'] ) ) {
			$args['privacy'] = $request['privacy'];
		}

		if ( ! empty( $request['exclude'] ) ) {
			$args['exclude'] = $request['exclude'];
		}

		if ( ! empty( $request['include'] ) ) {
			$args['video_ids'] = $request['include'];
			if (
				! empty( $args['order_by'] )
				&& 'include' === $args['order_by']
			) {
				$args['order_by'] = 'in';
			}
		}

		$args['scope'] = $this->bp_rest_video_default_scope( $args['scope'], $args );

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_video_get_items_query_args', $args, $request );

		if ( isset( $args['album_id'] ) && 0 === $args['album_id'] ) {
			$args['album_id'] = 'existing-video';
		}

		$videos = $this->assemble_response_data( $args );

		$retval = array();
		foreach ( $videos['videos'] as $video ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->media_endpoint->prepare_item_for_response( $video, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $videos['total'], $args['per_page'] );

		/**
		 * Fires after a list of videos is fetched via the REST API.
		 *
		 * @param array            $video    Fetched videos.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_video_get_items', $videos, $response, $request );

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

		if ( function_exists( 'bp_rest_enable_private_network' ) && true === bp_rest_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && ! empty( $request['group_id'] ) && bp_is_active( 'groups' ) ) {
			$group       = groups_get_group( $request['group_id'] );
			$user_id     = ( ! empty( $request['user_id'] ) ? $request['user_id'] : bp_loggedin_user_id() );
			$user_groups = groups_get_user_groups( $user_id );

			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! bp_current_user_can( 'bp_moderate' ) && ! empty( $group->id ) && 'public' !== bp_get_group_status( $group ) && isset( $user_groups['groups'] ) && ! in_array( $group->id, wp_parse_id_list( $user_groups['groups'] ), true ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, Restrict access to only group members.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the videos `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_video_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single video.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since          0.1.0
	 * @api            {GET} /wp-json/buddyboss/v1/video/:id Get Video
	 * @apiName        GetBBVideo
	 * @apiGroup       Video
	 * @apiDescription Retrieve a single video.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the video video.
	 */
	public function get_item( $request ) {

		$videos = $this->assemble_response_data( array( 'video_ids' => array( $request->get_param( 'id' ) ) ) );

		if ( empty( $videos['videos'] ) ) {
			return new WP_Error(
				'bp_rest_video_id',
				__( 'Invalid video ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = '';
		foreach ( $videos['videos'] as $video ) {
			$retval = $this->prepare_response_for_collection(
				$this->media_endpoint->prepare_item_for_response( $video, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a video is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_video_get_item', $response, $request );

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

		if ( function_exists( 'bp_rest_enable_private_network' ) && true === bp_rest_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval ) {

			$video = new BP_Video( $request->get_param( 'id' ) );

			if ( empty( $video->id ) ) {
				$retval = new WP_Error(
					'bp_rest_video_invalid_id',
					__( 'Invalid video ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				'public' !== $video->privacy &&
				! bp_current_user_can( 'bp_moderate' ) &&
				true === $this->bp_rest_check_privacy_restriction( $video )
			) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, Restrict access to view this video.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the video `get_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_video_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create videos.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since          0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/video Create Videos
	 * @apiName        CreateBBVideos
	 * @apiGroup       Video
	 * @apiDescription Create Video.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} upload_ids Video specific IDs.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the activity.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [album_id] A unique numeric ID for the Video Album.
	 * @apiParam {string} [content] Video Content.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly} [privacy=public] Privacy of the video.
	 */
	public function create_item( $request ) {

		$args = array(
			'upload_ids' => $request['upload_ids'],
			'privacy'    => $request['privacy'],
		);

		$args['upload_ids'] = $request['upload_ids'];

		if ( empty( $request['upload_ids'] ) ) {
			return new WP_Error(
				'bp_rest_no_video_found',
				__( 'Sorry, you are not allowed to create a Video item.', 'buddyboss' ),
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
			$album            = new BP_Video_Album( $args['album_id'] );
			$args['group_id'] = $album->group_id;
			$args['privacy']  = $album->privacy;
		}

		if ( isset( $request['activity_id'] ) && ! empty( $request['activity_id'] ) ) {
			$args['activity_id'] = $request['activity_id'];
		}

		if ( isset( $request['content'] ) && ! empty( $request['content'] ) ) {
			$args['content'] = $request['content'];
		}

		if ( isset( $request['message_id'] ) && ! empty( $request['message_id'] ) ) {
			$args['message_id'] = $request['message_id'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_video_create_items_query_args', $args, $request );

		$videos_ids = $this->bp_rest_create_video( $args );

		if ( is_wp_error( $videos_ids ) ) {
			return $videos_ids;
		}

		$videos = $this->assemble_response_data( array( 'video_ids' => $videos_ids ) );

		$fields_update = $this->update_additional_fields_for_object( $videos['videos'], $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = array();
		foreach ( $videos['videos'] as $video ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->media_endpoint->prepare_item_for_response( $video, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a Video is created via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_video_create_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a video.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a video.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if (
			is_user_logged_in() &&
			(
				! function_exists( 'bb_video_user_can_upload' ) ||
				(
					function_exists( 'bb_video_user_can_upload' ) &&
					bb_video_user_can_upload( bp_loggedin_user_id(), $request->get_param( 'group_id' ) )
				)
			)
		) {
			$retval = true;

			if (
				isset( $request['group_id'] ) &&
				! empty( $request['group_id'] ) &&
				(
					! bp_is_active( 'groups' )
					|| ! groups_can_user_manage_video( bp_loggedin_user_id(), (int) $request['group_id'] )
					|| ! function_exists( 'bp_is_group_video_support_enabled' )
					|| ( function_exists( 'bp_is_group_video_support_enabled' ) && false === bp_is_group_video_support_enabled() )
				)
			) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to create a video inside this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( isset( $request['album_id'] ) && ! empty( $request['album_id'] ) ) {
				$parent_album = new BP_Video_Album( $request['album_id'] );
				if ( empty( $parent_album->id ) ) {
					$retval = new WP_Error(
						'bp_rest_invalid_album_id',
						__( 'Invalid Album ID.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}

				if ( function_exists( 'bb_media_user_can_access' ) ) {
					$album_privacy = bb_media_user_can_access( $parent_album->id, 'album' );
				} else {
					$album_privacy = bp_media_user_can_manage_album( $parent_album->id, bp_loggedin_user_id() );
				}

				if ( true === $retval && true !== (bool) $album_privacy['can_add'] ) {
					$retval = new WP_Error(
						'bp_rest_invalid_permission',
						__( 'You don\'t have a permission to create a video inside this album.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		if ( true === $retval && ! empty( $request['upload_ids'] ) ) {
			foreach ( (array) $request['upload_ids'] as $attachment_id ) {
				$attachment_id = (int) $attachment_id;
				$wp_attachment = get_post( $attachment_id );

				if ( true !== $retval ) {
					continue;
				}

				if ( empty( $wp_attachment ) || 'attachment' !== $wp_attachment->post_type ) {
					$retval = new WP_Error(
						'bp_rest_invalid_upload_id',
						sprintf(
						/* translators: Attachment ID. */
							__( 'Invalid attachment id: %d', 'buddyboss' ),
							$attachment_id
						),
						array(
							'status' => 404,
						)
					);
				} elseif ( bp_loggedin_user_id() !== (int) $wp_attachment->post_author ) {
					$retval = new WP_Error(
						'bp_rest_invalid_video_author',
						sprintf(
						/* translators: Attachment ID. */
							__( 'You are not a valid author for attachment id: %d', 'buddyboss' ),
							$attachment_id
						),
						array(
							'status' => 404,
						)
					);
				} elseif ( 'messages' !== $request['component'] &&
					function_exists( 'bp_get_attachment_video_id' ) && ! empty( bp_get_attachment_video_id( (int) $attachment_id ) ) &&
					empty( $request['album_id'] ) ) {
					$retval = new WP_Error(
						'bp_rest_duplicate_video_upload_id',
						sprintf(
						/* translators: Attachment ID. */
							__( 'Video already exists for attachment id: %d', 'buddyboss' ),
							$attachment_id
						),
						array(
							'status' => 404,
						)
					);
				}
			}
		}

		/**
		 * Filter the Video `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_video_create_items_permissions_check', $retval, $request );
	}

	/**
	 * Update a video.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since          0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/video/:id Update Video
	 * @apiName        UpdateBBVideo
	 * @apiGroup       Video
	 * @apiDescription Update a single Video.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the video video.
	 * @apiParam {Number} [album_id] A unique numeric ID for the Album.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {string} [content] Video Content.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly} [privacy] Privacy of the video.
	 */
	public function update_item( $request ) {

		$videos = $this->assemble_response_data( array( 'video_ids' => array( $request->get_param( 'id' ) ) ) );

		if ( empty( $videos['videos'] ) ) {
			return new WP_Error(
				'bp_rest_video_id',
				__( 'Invalid video ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$video = end( $videos['videos'] );

		$args = array(
			'id'            => $video->id,
			'privacy'       => $video->privacy,
			'attachment_id' => $video->attachment_id,
			'group_id'      => $video->group_id,
			'album_id'      => $video->album_id,
			'activity_id'   => $video->activity_id,
			'message_id'    => $video->message_id,
			'user_id'       => $video->user_id,
			'menu_order'    => $video->menu_order,
		);

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( isset( $request['privacy'] ) && ! empty( $request['privacy'] ) ) {
			$args['privacy'] = $request['privacy'];
		}

		if ( isset( $request['message_id'] ) && ! empty( $request['message_id'] ) ) {
			$args['message_id'] = $request['message_id'];
		}

		if ( isset( $request['album_id'] ) && (int) $args['album_id'] !== (int) $request['album_id'] ) {
			$args['album_id'] = $request['album_id'];
			$moved_video_id   = bp_video_move_video_to_album( $args['id'], $args['album_id'], $args['group_id'] );
			if ( empty( (int) $moved_video_id ) || is_wp_error( $moved_video_id ) ) {
				return new WP_Error(
					'bp_rest_invalid_move_with_album',
					__( 'Sorry, you are not allowed to move this video with album.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$moved_video = new BP_Video( (int) $moved_video_id );
				if ( ! empty( $moved_video ) ) {
					$args['group_id'] = $moved_video->group_id;
					$args['album_id'] = $moved_video->album_id;
					$args['privacy']  = $moved_video->privacy;
				}
			}
		}

		if ( isset( $request['content'] ) ) {
			$args['content'] = $request['content'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_video_update_items_query_args', $args, $request );

		$id = $this->bp_rest_create_video( $args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		if ( empty( $id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_video',
				__( 'Cannot update existing video.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$videos = $this->assemble_response_data( array( 'video_ids' => array( $request['id'] ) ) );
		$video  = current( $videos['videos'] );

		$fields_update = $this->update_additional_fields_for_object( $video, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = '';
		foreach ( $videos['videos'] as $video ) {
			$retval = $this->prepare_response_for_collection(
				$this->media_endpoint->prepare_item_for_response( $video, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a video is updated via the REST API.
		 *
		 * @param BP_Video         $video    The updated Video.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_video_update_item', $video, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a video.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to update this video.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$video  = new BP_Video( $request['id'] );

			if ( empty( $video->id ) ) {
				$retval = new WP_Error(
					'bp_rest_video_invalid_id',
					__( 'Invalid video ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! bp_video_user_can_edit( $video ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update this video.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			} elseif (
				isset( $request['group_id'] ) &&
				! empty( $request['group_id'] ) &&
				(
					! bp_is_active( 'groups' )
					|| ! groups_can_user_manage_video( bp_loggedin_user_id(), (int) $request['group_id'] )
				)
			) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to edit a video inside this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif (
				isset( $request['album_id'] ) &&
				! empty( $request['album_id'] ) &&
				! bp_album_user_can_edit( (int) $request['album_id'] )
			) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to move/update a video inside this Album.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the video to `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_video_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a single video.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since          0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/video/:id Delete Video
	 * @apiName        DeleteBBVideo
	 * @apiGroup       Video
	 * @apiDescription Delete a single Video.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the video video.
	 */
	public function delete_item( $request ) {

		$id = $request->get_param( 'id' );

		$videos = $this->assemble_response_data( array( 'video_ids' => array( $id ) ) );

		if ( empty( $videos['videos'] ) ) {
			return new WP_Error(
				'bp_rest_video_id',
				__( 'Invalid video ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = '';
		foreach ( $videos['videos'] as $video ) {
			$previous = $this->prepare_response_for_collection(
				$this->media_endpoint->prepare_item_for_response( $video, $request )
			);
		}

		if ( ! bp_video_user_can_delete( $id ) ) {
			return new wp_error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this video.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$status = bp_video_delete( array( 'id' => $id ) );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $status,
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a video is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_video_get_item', $response, $request );

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
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this video.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$video  = new BP_Video( $request['id'] );

			if ( empty( $video->id ) ) {
				$retval = new WP_Error(
					'bp_rest_video_invalid_id',
					__( 'Invalid video ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! bp_video_user_can_delete( $video ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this video.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the video `delete_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_video_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Get videos.
	 *
	 * @param array|string $args All arguments and defaults are shared with BP_Video::get(),
	 *                           except for the following.
	 *
	 * @return array
	 */
	public function assemble_response_data( $args ) {

		// Fetch specific video items based on ID's.
		if ( isset( $args['video_ids'] ) && ! empty( $args['video_ids'] ) ) {
			return bp_video_get_specific( $args );

			// Fetch all videos items.
		} else {
			return bp_video_get( $args );
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

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['upload_ids'] = array(
				'description'       => __( 'Video specific IDs.', 'buddyboss' ),
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
			'description'       => __( 'A unique numeric ID for the Video Album.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['privacy'] = array(
			'description'       => __( 'Privacy of the video.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly' ),
			'default'           => 'public',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( WP_REST_Server::EDITABLE === $method ) {
			$args['id'] = array(
				'description'       => __( 'A unique numeric ID for the video.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			unset( $args['privacy']['default'] );

			$key = 'update';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_video_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the video schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_video',
			'type'       => 'object',
			'properties' => array(
				'id'                    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'blog_id'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current Site ID.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'attachment_id'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Unique identifier for the video object.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_id'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID for the author of the video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'title'                 => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Video title.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'description'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Video description.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'album_id'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Album.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'group_id'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'activity_id'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the activity.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'message_id'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Message thread.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'hide_activity_actions' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Based on this hide like/comment button for media activity comments.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'privacy'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Privacy of the video.', 'buddyboss' ),
					'enum'        => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'menu_order'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Order of the item.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'date_created'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The date the video was created, in the site\'s timezone.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'attachment_data'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Wordpress Video Data.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'full'           => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Video URL with full image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'thumb'          => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Video URL with thumbnail image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'activity_thumb' => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Video URL for the activity image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'meta'           => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Meta items for the video.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'object',
						),
					),
				),
				'group_name'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group name associate with the video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'visibility'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Visibility of the video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_nicename'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s nice name to create a video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_login'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s login name to create a video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'display_name'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s display name to create a video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'url'                   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Video file URL.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'download_url'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Download Video file URL.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_permissions'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current user\'s permission with the Video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
				),
				'type'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current media type video.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
			),
		);

		/**
		 * Filters the video schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_video_schema', $this->add_additional_fields_schema( $schema ) );
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
			'description'       => __( 'Order by a specific parameter.', 'buddyboss' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'date_created', 'menu_order', 'id', 'include' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit result set to items created by a specific user.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['max'] = array(
			'description'       => __( 'Maximum number of results to return.', 'buddyboss' ),
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
			'description'       => __( 'A unique numeric ID for the Video\'s Activity.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['privacy'] = array(
			'description'       => __( 'Privacy of the video.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description'       => __( 'Scope of the video.', 'buddyboss' ),
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
			'description'       => __( 'Ensure result set excludes specific video IDs.', 'buddyboss' ),
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
		return apply_filters( 'bp_rest_video_collection_params', $params );
	}

	/**
	 * Create the Video IDs from Upload IDs.
	 *
	 * @param array $args Key value array of query var to query value.
	 *
	 * @return array|WP_Error
	 * @since 0.1.0
	 */
	public function bp_rest_create_video( $args ) {

		$video_privacy = ( ! empty( $args['privacy'] ) ? $args['privacy'] : 'public' );
		$upload_ids    = ( ! empty( $args['upload_ids'] ) ? $args['upload_ids'] : '' );
		$activity_id   = ( ! empty( $args['activity_id'] ) ? $args['activity_id'] : false );
		$content       = ( isset( $args['content'] ) ? $args['content'] : false );
		$user_id       = ( ! empty( $args['user_id'] ) ? $args['user_id'] : get_current_user_id() );
		$id            = ( ! empty( $args['id'] ) ? $args['id'] : '' );
		$message_id    = ( ! empty( $args['message_id'] ) ? $args['message_id'] : 0 );

		$group_id = ( ! empty( $args['group_id'] ) ? $args['group_id'] : false );
		$album_id = ( ! empty( $args['album_id'] ) ? $args['album_id'] : false );

		// Override the privacy if album ID is given.
		if ( ! empty( $args['album_id'] ) ) {
			$albums = bp_album_get_specific( array( 'album_ids' => array( $args['album_id'] ) ) );
			if ( ! empty( $albums['albums'] ) ) {
				$album         = array_pop( $albums['albums'] );
				$video_privacy = $album->privacy;
				$group_id      = $album->group_id;
			}
		}

		// Update Video.
		if ( ! empty( $id ) ) {
			$wp_attachment_id  = $args['attachment_id'];
			$wp_attachment_url = wp_get_attachment_url( $wp_attachment_id );

			// when the file found to be empty it's means it's not a valid attachment.
			if ( empty( $wp_attachment_url ) ) {
				return;
			}

			$video_activity_id = $activity_id;

			$parent_activity = get_post_meta( $wp_attachment_id, 'bp_video_parent_activity_id', true );
			if ( ! empty( $parent_activity ) && bp_is_active( 'activity' ) ) {
				$activity_id = $parent_activity;
				$all_videos  = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
				$all_videos  = explode( ',', $all_videos );
				$key         = array_search( $id, $all_videos, true );
				if ( false !== $key ) {
					unset( $all_videos[ $key ] );
				}
			}

			// extract the nice title name.
			$title = get_the_title( $wp_attachment_id );

			$add_video_args = array(
				'id'            => $id,
				'attachment_id' => $wp_attachment_id,
				'title'         => $title,
				'description'   => wp_filter_nohtml_kses( $content ),
				'activity_id'   => $video_activity_id,
				'message_id'    => $message_id,
				'album_id'      => ( ! empty( $args['album_id'] ) ? $args['album_id'] : false ),
				'group_id'      => ( ! empty( $args['group_id'] ) ? $args['group_id'] : false ),
				'privacy'       => $video_privacy,
				'user_id'       => $user_id,
				'error_type'    => 'wp_error',
			);

			if ( isset( $args['menu_order'] ) ) {
				$add_video_args['menu_order'] = ( ! empty( $args['menu_order'] ) ? $args['menu_order'] : 0 );
			}

			$video_id = bp_video_add( $add_video_args );

			if ( is_int( $video_id ) ) {

				// save video is saved in attachment.
				update_post_meta( $wp_attachment_id, 'bp_video_saved', true );

				// save video meta for activity.
				if ( ! empty( $video_activity_id ) ) {
					update_post_meta( $wp_attachment_id, 'bp_video_activity_id', $video_activity_id );
				}

				// Added backward compatibility.
				// Save video description while update.
				if ( false !== $content ) {
					$video_post['ID']           = $wp_attachment_id;
					$video_post['post_content'] = wp_filter_nohtml_kses( $content );
					wp_update_post( $video_post );
				}

				bp_video_add_generate_thumb_background_process( $video_id );

				$created_video_ids[] = $video_id;
			}

			if ( ! empty( $all_videos ) ) {
				foreach ( $all_videos as $v_id ) {
					$video = new BP_Video( $v_id );
					if ( ! empty( $video->id ) ) {
						$created_video_ids[] = $video->id;
						$video->privacy      = $video_privacy;
						$video->save();
					}
				}
			}
		}

		// created Videos.
		if ( ! empty( $upload_ids ) ) {
			$valid_upload_ids = array();
			foreach ( $upload_ids as $wp_attachment_id ) {
				$wp_attachment_url = wp_get_attachment_url( $wp_attachment_id );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$valid_upload_ids[] = $wp_attachment_id;
			}

			$videos = array();

			if ( ! empty( $valid_upload_ids ) ) {
				foreach ( $valid_upload_ids as $wp_attachment_id ) {

					// Check if media id already available for the messages.
					if ( 'message' === $video_privacy ) {
						$mid = get_post_meta( $wp_attachment_id, 'bp_video_id', true );

						if ( ! empty( $mid ) ) {
							$created_video_ids[] = $mid;
							continue;
						}
					}
					// extract the nice title name.
					$title = get_the_title( $wp_attachment_id );

					$videos[] = array(
						'id'      => $wp_attachment_id,
						'name'    => $title,
						'privacy' => $video_privacy,
					);
				}
			}

			if ( ! empty( $videos ) ) {
				$created_video_ids = bp_video_add_handler( $videos, $video_privacy, $content, $group_id, $album_id );
			}
		}

		if ( empty( $created_video_ids ) ) {
			return new WP_Error(
				'bp_rest_video_creation_error',
				__( 'Error creating video, please try again.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		// Link all uploaded video to main activity.
		if ( ! empty( $activity_id ) ) {
			$main_activity = new BP_Activity_Activity( $activity_id );
			if ( ! empty( $main_activity->id ) && 'video' !== $main_activity->privacy ) {
				$created_video_ids_joined = implode( ',', $created_video_ids );
				bp_activity_update_meta( $activity_id, 'bp_video_ids', $created_video_ids_joined );

				if ( empty( $group_id ) ) {
					$main_activity->privacy = $video_privacy;
					$main_activity->save();
				}
			}
		}

		return $created_video_ids;
	}

	/**
	 * Register custom field for the activity api.
	 */
	public function bp_rest_video_support() {
		bp_rest_register_field(
			'activity',      // Id of the BuddyPress component the REST field is about.
			'bp_videos', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_video_ids_get_rest_field_callback' ),    // The function to use to get the value of the REST Field.
				'update_callback' => array( $this, 'bp_video_ids_update_rest_field_callback' ), // The function to use to update the value of the REST Field.
				'schema'          => array(                                // The example_field REST schema.
					'description' => 'Activity Videos.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		register_rest_field(
			'activity_comments',      // Id of the BuddyPress component the REST field is about.
			'bp_videos', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_video_ids_get_rest_field_callback' ),    // The function to use to get the value of the REST Field.
				'update_callback' => array( $this, 'bp_video_ids_update_rest_field_callback' ), // The function to use to update the value of the REST Field.
				'schema'          => array(                                // The example_field REST schema.
					'description' => 'Activity Videos.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		if ( function_exists( 'bp_is_forums_video_support_enabled' ) && true === bp_is_forums_video_support_enabled() ) {
			// Topic Video Video Support.
			register_rest_field(
				'topics',
				'bbp_videos',
				array(
					'get_callback'    => array( $this, 'bbp_video_get_rest_field_callback' ),
					'update_callback' => array( $this, 'bbp_video_update_rest_field_callback' ),
					'schema'          => array(
						'description' => 'Topic Videos.',
						'type'        => 'object',
						'context'     => array( 'embed', 'view', 'edit' ),
					),
				)
			);

			// Reply Video Video Support.
			register_rest_field(
				'reply',
				'bbp_videos',
				array(
					'get_callback'    => array( $this, 'bbp_video_get_rest_field_callback' ),
					'update_callback' => array( $this, 'bbp_video_update_rest_field_callback' ),
					'schema'          => array(
						'description' => 'Reply Videos.',
						'type'        => 'object',
						'context'     => array( 'embed', 'view', 'edit' ),
					),
				)
			);
		}

		// Messages Video Support.
		bp_rest_register_field(
			'messages',      // Id of the BuddyPress component the REST field is about.
			'bp_videos', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_video_ids_get_rest_field_callback_messages' ),
				// The function to use to get the value of the REST Field.
				'update_callback' => array( $this, 'bp_video_ids_update_rest_field_callback_messages' ),
				// The function to use to update the value of the REST Field.
				'schema'          => array(                                // The example_field REST schema.
					'description' => 'Messages Videos.',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		// Added param to main activity to check the comment has access to upload video or not.
		bp_rest_register_field(
			'activity',
			'comment_upload_video',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_video' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload video or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to comment activity to check the child comment has access to upload video or not.
		register_rest_field(
			'activity_comments',
			'comment_upload_video',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_video' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload video or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * The function to use to get videos of the activity REST Field.
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_video_ids_get_rest_field_callback( $activity, $attribute ) {
		$activity_id = $activity['id'];

		if ( empty( $activity_id ) ) {
			return;
		}

		$video_ids = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
		$video_id  = bp_activity_get_meta( $activity_id, 'bp_video_id', true );
		$video_ids = trim( $video_ids );
		$video_ids = explode( ',', $video_ids );

		if ( ! empty( $video_id ) ) {
			$video_ids[] = $video_id;
			$video_ids   = array_filter( array_unique( $video_ids ) );
		}

		if ( empty( $video_ids ) ) {
			return;
		}

		$videos = $this->assemble_response_data(
			array(
				'per_page'  => 0,
				'video_ids' => $video_ids,
				'sort'      => 'ASC',
				'order_by'  => 'menu_order',
			)
		);

		if ( empty( $videos['videos'] ) ) {
			return;
		}

		$retval = array();
		$object = new WP_REST_Request();
		foreach ( $videos['videos'] as $video ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->media_endpoint->prepare_item_for_response( $video, $object )
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the videos's value of the activity REST Field.
	 * - from bp_video_update_activity_video_meta();
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Activity_Activity object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_video_ids_update_rest_field_callback( $object, $value, $attribute ) {

		global $bp_activity_edit, $bp_video_upload_count, $bp_new_activity_comment, $bp_activity_post_update_id, $bp_activity_post_update, $bb_activity_comment_edit, $bb_activity_comment_edit_id;

		$group_id = 0;
		if ( 'groups' === $value->component ) {
			$group_id = $value->item_id;
		}

		if ( 'activity_comment' === $value->type && ! empty( $value->secondary_item_id ) && ! empty( $value->item_id ) ) {
			$parent_activity = new BP_Activity_Activity( (int) $value->item_id );
			if ( ! empty( $parent_activity->id ) ) {
				$group_id = $parent_activity->item_id;
			}
			unset( $parent_activity );
		}

		if (
			'bp_videos' !== $attribute ||
			(
				function_exists( 'bb_video_user_can_upload' ) &&
				! bb_video_user_can_upload( bp_loggedin_user_id(), (int) $group_id )
			)
		) {
			$value->bp_videos = null;

			return $value;
		}

		// Set variable if current action is edit activity comment.
		$is_edit_activity_comment = $bb_activity_comment_edit && 'activity_comment' === $value->type && isset( $_POST['edit_comment'] );

		if ( $is_edit_activity_comment ) {
			$bb_activity_comment_edit_id = $value->id;
			if ( false === $bb_activity_comment_edit && empty( $object ) ) {
				return $value;
			}
		} else {
			$bp_activity_edit = ( isset( $value->edit ) ? true : false );
			// phpcs:ignore
			$_POST['edit'] = $bp_activity_edit;

			if ( false === $bp_activity_edit && empty( $object ) ) {
				return $value;
			}
		}

		$bp_new_activity_comment = ( ( 'activity_comment' === $value->type && false === $bb_activity_comment_edit ) ? $value->id : 0 );

		$activity_id = $value->id;
		$privacy     = $value->privacy;
		$group_id    = 0;

		$videos             = wp_parse_id_list( $object );
		$old_video_ids      = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
		$old_video_ids      = ( ! empty( $old_video_ids ) ? explode( ',', $old_video_ids ) : array() );
		$new_videos         = array();
		$old_videos         = array();
		$old_videos_objects = array();

		if ( ! empty( $old_video_ids ) ) {
			foreach ( $old_video_ids as $id ) {
				$video_object                                       = new BP_Video( $id );
				$old_videos_objects[ $video_object->attachment_id ] = $video_object;
				$old_videos[ $id ]                                  = $video_object->attachment_id;
			}
		}

		if ( ! $is_edit_activity_comment ) {
			$bp_activity_post_update    = true;
			$bp_activity_post_update_id = $activity_id;
		}

		if ( ! empty( $value->component ) && 'groups' === $value->component ) {
			$group_id = $value->item_id;
			$privacy  = 'grouponly';
		}

		if ( ! isset( $videos ) || empty( $videos ) ) {

			// delete video ids and meta for activity if empty video in request.
			if ( ! empty( $activity_id ) && ! empty( $old_video_ids ) ) {
				foreach ( $old_video_ids as $video_id ) {
					bp_video_delete( array( 'id' => $video_id ), 'activity' );
				}
				bp_activity_delete_meta( $activity_id, 'bp_video_ids' );

				// Delete media meta from activity for activity comment.
				if ( $is_edit_activity_comment ) {
					bp_activity_delete_meta( $activity_id, 'bp_video_id' );
					bp_activity_delete_meta( $activity_id, 'bp_video_activity' );
				}
			}

			return $value;
		} else {

			$order_count = 0;
			foreach ( $videos as $id ) {

				$wp_attachment_url = wp_get_attachment_url( $id );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$order_count ++;

				if ( in_array( $id, $old_videos, true ) ) {
					$new_videos[] = array(
						'video_id' => $old_videos_objects[ $id ]->id,
					);
				} else {
					$new_videos[] = array(
						'id'         => $id,
						'name'       => get_the_title( $id ),
						'album_id'   => 0,
						'group_id'   => $group_id,
						'menu_order' => $order_count,
						'privacy'    => $privacy,
					);
				}
			}
		}

		$bp_video_upload_count = count( $new_videos );

		remove_action( 'bp_activity_posted_update', 'bp_video_update_activity_video_meta', 10, 3 );
		remove_action( 'bp_groups_posted_update', 'bp_video_groups_activity_update_video_meta', 10, 4 );
		remove_action( 'bp_activity_comment_posted', 'bp_video_activity_comments_update_video_meta', 10, 3 );
		remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_video_activity_comments_update_video_meta', 10, 3 );

		$video_ids = bp_video_add_handler( $new_videos, $privacy, '', $group_id );

		add_action( 'bp_activity_posted_update', 'bp_video_update_activity_video_meta', 10, 3 );
		add_action( 'bp_groups_posted_update', 'bp_video_groups_activity_update_video_meta', 10, 4 );
		add_action( 'bp_activity_comment_posted', 'bp_video_activity_comments_update_video_meta', 10, 3 );
		add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_video_activity_comments_update_video_meta', 10, 3 );

		// save video meta for activity.
		if ( ! empty( $activity_id ) ) {
			// Delete video if not exists in current video ids.
			if ( ! empty( $old_video_ids ) ) {
				foreach ( $old_video_ids as $video_id ) {

					if ( ! in_array( (int) $video_id, $video_ids, true ) ) {
						bp_video_delete( array( 'id' => $video_id ), 'activity' );
					}
				}
			}
			bp_activity_update_meta( $activity_id, 'bp_video_ids', implode( ',', $video_ids ) );
		}
	}

	/**
	 * Filter Query argument for the activity for video support.
	 *
	 * @param array  $args   Query arguments.
	 * @param string $method HTTP method of the request.
	 *
	 * @return array
	 */
	public function bp_rest_activity_query_arguments( $args, $method ) {

		$args['bp_videos'] = array(
			'description'       => __( 'Video specific IDs.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
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

		if ( function_exists( 'bp_is_forums_video_support_enabled' ) && true === bp_is_forums_video_support_enabled() ) {
			$params['bbp_videos'] = array(
				'description'       => __( 'Video specific IDs.', 'buddyboss' ),
				'type'              => 'array',
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		return $params;
	}

	/**
	 * Filter Query argument for the Messages for video support.
	 *
	 * @param array $params Query arguments.
	 *
	 * @return array
	 */
	public function bp_rest_message_query_arguments( $params ) {

		if ( function_exists( 'bp_is_messages_video_support_enabled' ) && true === bp_is_messages_video_support_enabled() ) {
			$params['bp_videos'] = array(
				'description'       => __( 'Video specific IDs.', 'buddyboss' ),
				'type'              => 'array',
				'items'             => array( 'type' => 'integer' ),
				'sanitize_callback' => 'wp_parse_id_list',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		return $params;
	}

	/**
	 * The function to use to get videos of the topic REST Field.
	 *
	 * @param array  $post      WP_Post object as array.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bbp_video_get_rest_field_callback( $post, $attribute ) {

		$p_id = $post['id'];

		if ( empty( $p_id ) ) {
			return;
		}

		$video_ids = get_post_meta( $p_id, 'bp_video_ids', true );
		$video_id  = get_post_meta( $p_id, 'bp_video_id', true );
		$video_ids = trim( $video_ids );
		$video_ids = explode( ',', $video_ids );

		if ( ! empty( $video_id ) ) {
			$video_ids[] = $video_id;
			$video_ids   = array_filter( array_unique( $video_ids ) );
		}

		if ( empty( $video_ids ) ) {
			return;
		}

		$videos = $this->assemble_response_data(
			array(
				'per_page'  => 0,
				'video_ids' => $video_ids,
				'sort'      => 'ASC',
				'order_by'  => 'menu_order',
			)
		);

		if ( empty( $videos['videos'] ) ) {
			return;
		}

		$retval = array();
		$object = new WP_REST_Request();

		foreach ( $videos['videos'] as $video ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->media_endpoint->prepare_item_for_response( $video, $object )
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the videos's value of the topic REST Field.
	 * - from bp_video_forums_new_post_video_save();
	 *
	 * @param object $object Value for the schema.
	 * @param object $value  The value of the REST Field to save.
	 *
	 * @return object
	 */
	protected function bbp_video_update_rest_field_callback( $object, $value ) {

		// save video.
		$videos = wp_parse_id_list( $object );

		$edit = ( isset( $value->edit ) ? true : false );

		if ( empty( $videos ) && false === $edit ) {
			$value->bbp_video = null;

			return $value;
		}

		$post_id = $value->ID;

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// Get current forum ID.
		if ( 'reply' === $value->post_type ) {
			$forum_id = bbp_get_reply_forum_id( $post_id );
		} else {
			$forum_id = bbp_get_topic_forum_id( $post_id );
		}

		$group_ids = bbp_get_forum_group_ids( $forum_id );
		$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

		// fetch currently uploaded video ids.
		$existing_video_ids            = get_post_meta( $post_id, 'bp_video_ids', true );
		$existing_video_attachments    = array();
		$existing_video_attachment_ids = array();

		if ( ! empty( $existing_video_ids ) ) {
			$existing_video_ids = explode( ',', $existing_video_ids );

			foreach ( $existing_video_ids as $existing_video_id ) {
				$bp_video = new BP_Video( $existing_video_id );

				if ( ! empty( $bp_video->attachment_id ) ) {
					$existing_video_attachment_ids[]                  = $bp_video->attachment_id;
					$existing_video_attachments[ $existing_video_id ] = $bp_video->attachment_id;
				}
			}
		}

		$video_ids  = array();
		$menu_order = 0;

		if ( ! empty( $videos ) ) {
			foreach ( $videos as $video ) {

				$wp_attachment_url = wp_get_attachment_url( $video );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$menu_order ++;

				$attachment_id = ! empty( $video ) ? $video : 0;
				$menu_order    = $menu_order;

				if ( ! empty( $existing_video_attachment_ids ) ) {
					$index = array_search( $attachment_id, $existing_video_attachment_ids, true );
					if ( ! empty( $attachment_id ) && false !== $index ) {
						$exisiting_video_id    = array_search( $attachment_id, $existing_video_attachments, true );
						$existing_video_update = new BP_Video( $exisiting_video_id );

						$existing_video_update->menu_order = $menu_order;
						$existing_video_update->save();

						unset( $existing_video_ids[ $index ] );
						$video_ids[] = $exisiting_video_id;
						continue;
					}
				}

				// extract the nice title name.
				$title = get_the_title( $attachment_id );

				$video_id = bp_video_add(
					array(
						'attachment_id' => $attachment_id,
						'title'         => $title,
						'group_id'      => $group_id,
						'privacy'       => 'forums',
						'error_type'    => 'wp_error',
					)
				);

				if ( ! is_wp_error( $video_id ) ) {
					$video_ids[] = $video_id;

					bp_video_add_generate_thumb_background_process( $video_id );

					// save video is saved in attachment.
					update_post_meta( $attachment_id, 'bp_video_saved', true );
				}
			}
		}

		$video_ids = implode( ',', $video_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $post_id, 'bp_video_ids', $video_ids );

		// save video meta for activity.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_video_ids', $video_ids );
		}

		// delete videos which were not saved or removed from form.
		if ( ! empty( $existing_video_ids ) ) {
			foreach ( $existing_video_ids as $video_id ) {
				bp_video_delete( array( 'id' => $video_id ) );
			}
		}
	}

	/**
	 * The function to use to get videos of the messages REST Field.
	 *
	 * @param array  $data      The message value for the REST response.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return array|void The value of the REST Field to include into the REST response.
	 */
	protected function bp_video_ids_get_rest_field_callback_messages( $data, $attribute ) {
		$message_id = $data['id'];

		if ( empty( $message_id ) ) {
			return;
		}

		$thread_id = ! empty( $data['thread_id'] ) ? $data['thread_id'] : 0;
		if ( empty( $thread_id ) ) {
			return;
		}

		$group_name   = ! empty( $data['group_name'] ) ? $data['group_name'] : '';
		$message_from = ! empty( $data['message_from'] ) ? $data['message_from'] : '';

		if (
			bp_is_active( 'video' ) &&
			(
				(
					! empty( $group_name ) &&
					'group' === $message_from &&
					bp_is_group_video_support_enabled()
				) ||
				(
					'group' !== $message_from &&
					bp_is_messages_video_support_enabled()
				)
			)
		) {
			$video_ids = bp_messages_get_meta( $message_id, 'bp_video_ids', true );
			$video_id  = bp_messages_get_meta( $message_id, 'bp_video_id', true );
			$video_ids = trim( $video_ids );
			$video_ids = explode( ',', $video_ids );

			if ( ! empty( $video_id ) ) {
				$video_ids[] = $video_id;
				$video_ids   = array_filter( array_unique( $video_ids ) );
			}

			if ( empty( $video_ids ) ) {
				return;
			}

			$videos = $this->assemble_response_data(
				array(
					'per_page'         => 0,
					'video_ids'        => $video_ids,
					'sort'             => 'ASC',
					'order_by'         => 'menu_order',
					'moderation_query' => false,
				)
			);

			if ( empty( $videos['videos'] ) ) {
				return;
			}

			$retval = array();
			$object = new WP_REST_Request();
			$object->set_param( 'context', 'view' );

			foreach ( $videos['videos'] as $video ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->media_endpoint->prepare_item_for_response( $video, $object )
				);
			}

			return $retval;
		}
	}

	/**
	 * The function to use to update the videos's value of the messages REST Field.
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Messages_Message object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_video_ids_update_rest_field_callback_messages( $object, $value, $attribute ) {

		if ( 'bp_videos' !== $attribute || empty( $object ) ) {
			$value->bp_videos = null;

			return $value;
		}

		$message_id = $value->id;

		$videos = wp_parse_id_list( $object );
		if ( empty( $videos ) ) {
			$value->bp_videos = null;

			return $value;
		}

		$thread_id = $value->thread_id;

		if ( function_exists( 'bb_user_has_access_upload_video' ) ) {
			$can_send_video = bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
			if ( ! $can_send_video ) {
				$value->bp_videos = null;

				return $value;
			}
		}

		$thread = new BP_Messages_Thread( $thread_id );

		$is_group_message_thread = false;
		$first_message           = BP_Messages_Thread::get_first_message( $thread->thread_id );
		$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
		$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
		$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
		$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

		if ( 'group' === $message_from && $thread->thread_id === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
			$is_group_message_thread = true;
		}

		$thread->group_id        = $group_id;
		$thread->is_group_thread = $is_group_message_thread;

		if ( empty( apply_filters( 'bp_user_can_create_message_video', bb_user_has_access_upload_video( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' ), $thread, bp_loggedin_user_id() ) ) ) {
			$value->bp_videos = null;

			return $value;
		}

		$args = array(
			'upload_ids' => $videos,
			'privacy'    => 'message',
		);

		remove_action( 'bp_video_add', 'bp_activity_video_add', 9 );
		remove_filter( 'bp_video_add_handler', 'bp_activity_create_parent_video_activity', 9 );

		$videos_ids = $this->bp_rest_create_video( $args );

		add_action( 'bp_video_add', 'bp_activity_video_add', 9 );
		add_filter( 'bp_video_add_handler', 'bp_activity_create_parent_video_activity', 9 );

		if ( is_wp_error( $videos_ids ) ) {
			$value->bp_videos = $videos_ids;

			return $value;
		}

		bp_messages_update_meta( $message_id, 'bp_video_ids', implode( ',', $videos_ids ) );
	}

	/**
	 * Get default scope for the video.
	 * - from bp_video_default_scope().
	 *
	 * @since 0.1.0
	 *
	 * @param string $scope Default scope.
	 * @param array  $args  Array of request parameters.
	 *
	 * @return string
	 */
	public function bp_rest_video_default_scope( $scope, $args = array() ) {
		$new_scope = array();

		if ( is_array( $scope ) ) {
			$scope = array_filter( $scope );
		}

		if ( ( 'all' === $scope || empty( $scope ) ) && ( empty( $args['group_id'] ) && empty( $args['user_id'] ) ) ) {
			$new_scope[] = 'public';

			if ( bp_is_active( 'friends' ) && bp_is_profile_video_support_enabled() ) {
				$new_scope[] = 'friends';
			}

			if ( bp_is_active( 'groups' ) && bp_is_group_video_support_enabled() ) {
				$new_scope[] = 'groups';
			}

			if ( is_user_logged_in() && bp_is_profile_video_support_enabled() ) {
				$new_scope[] = 'personal';
			}
		}

		$new_scope = array_unique( $new_scope );

		if ( empty( $new_scope ) ) {
			$new_scope = (array) $scope;
		}

		/**
		 * Filter to update default scope for rest api.
		 *
		 * @since 0.1.0
		 */
		$new_scope = apply_filters( 'bp_rest_video_default_scope', $new_scope );

		return implode( ',', $new_scope );
	}

	/**
	 * Check user access based on the privacy for the single Video.
	 *
	 * @param BP_Video $video Video object.
	 *
	 * @return bool
	 */
	protected function bp_rest_check_privacy_restriction( $video ) {
		$bool = ( 'onlyme' === $video->privacy && bp_loggedin_user_id() !== $video->user_id ) ||
			(
				'loggedin' === $video->privacy
				&& empty( bp_loggedin_user_id() )
			) ||
			(
				bp_is_active( 'groups' )
				&& 'grouponly' === $video->privacy
				&& ! empty( $video->group_id )
				&& 'public' !== bp_get_group_status( groups_get_group( $video->group_id ) )
				&& empty( groups_is_user_admin( bp_loggedin_user_id(), $video->group_id ) )
				&& empty( groups_is_user_mod( bp_loggedin_user_id(), $video->group_id ) )
				&& empty( groups_is_user_member( bp_loggedin_user_id(), $video->group_id ) )
			) ||
			(
				bp_is_active( 'friends' )
				&& 'friends' === $video->privacy
				&& ! empty( $video->user_id )
				&& bp_loggedin_user_id() !== $video->user_id
				&& 'is_friend' !== friends_check_friendship_status( $video->user_id, bp_loggedin_user_id() )
			);

		return $bool;
	}


	/**
	 * The function to use to set `comment_upload_video`
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_rest_user_can_comment_upload_video( $activity, $attribute ) {
		$activity_id = $activity['id'];

		if ( empty( $activity_id ) ) {
			return false;
		}

		$component = $activity['component'];
		$type      = 'activity_comment' === $activity['type'];
		$item_id   = $activity['primary_item_id'];

		if ( ! empty( $item_id ) ) {
			$parent_activity = new BP_Activity_Activity( $item_id );
			if ( true === $type ) {
				if ( 'groups' === $parent_activity->component ) {
					$item_id   = $parent_activity->item_id;
					$component = 'groups';
				}
			}

			if (
				'blogs' === $parent_activity->component ||
				(
					! empty( $activity['component'] ) &&
					'blogs' === $activity['component']
				)
			) {
				return false;
			}
		}

		$user_id = bp_loggedin_user_id();
		if ( empty( $user_id ) ) {
			return false;
		}

		$group_id = 0;
		if ( bp_is_active( 'groups' ) && 'groups' === $component && ! empty( $item_id ) ) {
			$group_id = $item_id;
		}

		if ( function_exists( 'bb_user_has_access_upload_video' ) ) {
			return bb_user_has_access_upload_video( $group_id, $user_id, 0, 0, 'profile' );
		}

		return false;
	}

}
