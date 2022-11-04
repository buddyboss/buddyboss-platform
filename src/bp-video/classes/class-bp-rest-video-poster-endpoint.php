<?php
/**
 * BP REST: BP_REST_Video_Poster_Endpoint class
 *
 * @package BuddyBoss
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Video poster endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Video_Poster_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Video_Endpoint Instance.
	 *
	 * @var BP_REST_Video_Endpoint
	 */
	protected $video_endpoint;

	/**
	 * BP_REST_Media_Endpoint Instance.
	 *
	 * @var BP_REST_Video_Endpoint
	 */
	protected $media_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'video';

		$this->video_endpoint = new BP_REST_Video_Endpoint();
		$this->media_endpoint = new BP_REST_Media_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/upload_poster',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_poster' ),
					'permission_callback' => array( $this, 'upload_poster_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/poster',
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
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
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
	 * Upload poster.
	 *
	 * @since          0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {POST} /wp-json/buddyboss/v1/video/:id/upload_poster Upload Video Poster
	 * @apiName        UploadBBVideoPoster
	 * @apiGroup       Video
	 * @apiDescription Upload Video Poster.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} file File object which is going to upload.
	 */
	public function upload_poster( $request ) {

		$file = $request->get_file_params();

		if ( empty( $file ) ) {
			return new WP_Error(
				'bp_rest_thumb_file_required',
				__( 'Sorry, you have not uploaded any thumb.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if (
			isset( $file['file']['size'] ) &&
			function_exists( 'bp_media_allowed_upload_media_size' ) &&
			$file['file']['size'] > bp_media_allowed_upload_media_size() * 1048576
		) {
			return new WP_Error(
				'bp_rest_max_upload_size',
				sprintf(
				/* translators: 1: File size, 2: Allowed size. */
					__( 'File is too large (%1$s MB). Max file size: %2$s MB.', 'buddyboss' ),
					round( $file['file']['size'] / 1048576, 1 ),
					bp_media_allowed_upload_media_size()
				),
				array(
					'status' => 400,
				)
			);
		}

		/**
		 * Create and upload the poster file.
		 */
		$upload = bp_video_thumbnail_upload();

		if ( is_wp_error( $upload ) ) {
			return new WP_Error(
				'bp_rest_video_upload_error',
				$upload->get_error_message(),
				array(
					'status' => 400,
				)
			);
		}

		$response = rest_ensure_response( $upload );

		/**
		 * Fires after a video poster is uploaded via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 */
		do_action( 'bp_rest_video_poster_upload_item', $response, $request );

		return $response;

	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function upload_poster_permissions_check( $request ) {

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to upload poster image.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$video = new BP_Video( $request['id'] );
			if ( empty( $video->id ) ) {
				$retval = new WP_Error(
					'bp_rest_video_invalid_id',
					__( 'Invalid video ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$video->type = 'video';
				$permission  = $this->media_endpoint->get_media_current_user_permissions( $video );
				if ( empty( $permission['upload_poster'] ) ) {
					$retval = new WP_Error(
						'bp_rest_video_poster_invalid_access',
						__( 'You don\'t have permission to upload the poster on this video.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the video poster `upload_poster` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool            $retval  Returned value.
		 */
		return apply_filters( 'bp_rest_video_poster_upload_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve Video posters.
	 *
	 * @since          0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {GET} /wp-json/buddyboss/v1/video/:id/poster Get Posters
	 * @apiName        GetBBVideoPosters
	 * @apiGroup       Video
	 * @apiDescription Retrieve Video posters.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the video video.
	 */
	public function get_items( $request ) {

		$id = $request->get_param( 'id' );

		$videos = $this->video_endpoint->assemble_response_data( array( 'video_ids' => array( $id ) ) );

		if ( empty( $videos['videos'] ) ) {
			return new WP_Error(
				'bp_rest_video_invalid_id',
				__( 'Invalid video ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$data = $this->get_video_poster( $id, $request );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$response = rest_ensure_response( $data );

		/**
		 * Fires after a list of video poster is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_video_poster_get_items', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function get_items_permissions_check( $request ) {

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to fetch poster images.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$video = new BP_Video( $request['id'] );
			if ( empty( $video->id ) ) {
				$retval = new WP_Error(
					'bp_rest_video_invalid_id',
					__( 'Invalid video ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$video->type = 'video';
				$permission  = $this->media_endpoint->get_media_current_user_permissions( $video );
				if ( empty( $permission['upload_poster'] ) ) {
					$retval = new WP_Error(
						'bp_rest_video_poster_invalid_access',
						__( 'You don\'t have permission to view the poster on this video.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the video posters `get_items` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool            $retval  Returned value.
		 */
		return apply_filters( 'bp_rest_video_poster_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Create Video poster.
	 *
	 * @since          0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {POST/PUT} /wp-json/buddyboss/v1/video/:id/poster Add Video Poster
	 * @apiName        UpdateBBVideoPoster
	 * @apiGroup       Video
	 * @apiDescription Add Video Poster
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the video video.
	 * @apiParam {Number} attachment_id A Unique numeric ID for the video poster.
	 */
	public function create_item( $request ) {
		$id            = $request->get_param( 'id' );
		$file          = $request->get_file_params();
		$attachment_id = $request->get_param( 'attachment_id' );
		$attachment_id = filter_var( $attachment_id, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0 ) ) );

		if ( empty( $attachment_id ) && ! empty( $file ) ) {
			$attachment = $this->upload_poster( $request );

			if ( is_wp_error( $attachment ) ) {
				return $attachment;
			}

			$attachment_id = ! empty( $attachment->data ) && ! empty( $attachment->data['id'] ) ? $attachment->data['id'] : false;
		}

		if ( empty( $attachment_id ) ) {
			return new WP_Error(
				'bp_rest_attachment_invalid_id',
				__( 'Invalid attachment ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$video                     = new BP_Video( $id );
		$auto_generated_thumbnails = (array) get_post_meta( $video->attachment_id, 'video_preview_thumbnails', true );

		// New thumbnail upload time remove the previous one.
		if ( empty( $request->get_param( 'attachment_id' ) ) && ! empty( $file ) && ! empty( $auto_generated_thumbnails['custom_image'] ) ) {
			wp_delete_post( $auto_generated_thumbnails['custom_image'], true );
		}

		// Set the meta data after upload new custom thumbnail.
		if ( empty( $request->get_param( 'attachment_id' ) ) && ! empty( $file ) ) {
			$auto_generated_thumbnails['custom_image'] = $attachment_id;
			update_post_meta( $video->attachment_id, 'video_preview_thumbnails', $auto_generated_thumbnails);
		}
    
		update_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', $attachment_id );

		$response = $this->get_video_poster( $id, $request );

		/**
		 * Fires after a video poster is created via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_video_poster_create_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to add poster image.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$video         = new BP_Video( $request['id'] );
			$attachment_id = $request->get_param( 'attachment_id' );

			if ( empty( $video->id ) ) {
				$retval = new WP_Error(
					'bp_rest_video_invalid_id',
					__( 'Invalid video ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( function_exists( 'bp_get_attachment_media_id' ) && ! empty( bp_get_attachment_media_id( (int) $attachment_id ) ) ) {
				$retval = new WP_Error(
					'bp_rest_duplicate_media_upload_id',
					sprintf(
					/* translators: Attachment ID. */
						__( 'Media already exists for attachment id: %d', 'buddyboss' ),
						$attachment_id
					),
					array(
						'status' => 404,
					)
				);
			} else {
				$video->type = 'video';
				$permission  = $this->media_endpoint->get_media_current_user_permissions( $video );
				if ( empty( $permission['upload_poster'] ) ) {
					$retval = new WP_Error(
						'bp_rest_video_poster_invalid_access',
						__( 'You don\'t have permission to add the poster on this video.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the video poster `create_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool            $retval  Returned value.
		 */
		return apply_filters( 'bp_rest_video_poster_create_item_permissions_check', $retval, $request );
	}


	/**
	 * Delete Video poster.
	 *
	 * @since          0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {GET} /wp-json/buddyboss/v1/video/:id/poster Delete Poster
	 * @apiName        DeleteBBVideoPoster
	 * @apiGroup       Video
	 * @apiDescription Delete Video Poster
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the video video.
	 * @apiParam {Number} attachment_id A Unique numeric ID for the video poster.
	 */
	public function delete_item( $request ) {
		$id            = $request->get_param( 'id' );
		$attachment_id = $request->get_param( 'attachment_id' );

		$video                     = new BP_Video( $id );
		$auto_generated_thumbnails = get_post_meta( $video->attachment_id, 'video_preview_thumbnails', true );
		$preview_thumbnail_id      = get_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', true );

		if (
			isset( $auto_generated_thumbnails['default_images'] ) &&
			! empty( $auto_generated_thumbnails['default_images'] ) &&
			in_array( $attachment_id, $auto_generated_thumbnails['default_images'], true )
		) {
			return new WP_Error(
				'bp_rest_invalid_request',
				__( 'Sorry, You can not able to delete auto generated video poster.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if (
			isset( $auto_generated_thumbnails['custom_image'] ) &&
			! empty( $auto_generated_thumbnails['custom_image'] ) &&
			$attachment_id != $auto_generated_thumbnails['custom_image']
		) {
			return new WP_Error(
				'bp_rest_invalid_default_id',
				__( 'Sorry, You have passed invalid poster ID.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		wp_delete_post( $attachment_id, true );
		$auto_generated_thumbnails['custom_image'] = '';
		update_post_meta( $video->attachment_id, 'video_preview_thumbnails', $auto_generated_thumbnails );

		$setup_id = '';

		if ( isset( $auto_generated_thumbnails['default_images'] ) && ! empty( $auto_generated_thumbnails['default_images'] ) ) {
			$setup_id = current( $auto_generated_thumbnails['default_images'] );
		}

		update_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', $setup_id );

		$response = $this->get_video_poster( $id, $request );

		/**
		 * Fires after a list of video poster is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_video_poster_delete_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get all users.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to delete poster images.', 'buddyboss' ),
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
			} else {
				$video->type = 'video';
				$permission  = $this->media_endpoint->get_media_current_user_permissions( $video );
				if ( empty( $permission['upload_poster'] ) ) {
					$retval = new WP_Error(
						'bp_rest_video_poster_invalid_access',
						__( 'You don\'t have permission to delete the poster on this video.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the video poster `delete_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @param bool            $retval  Returned value.
		 */
		return apply_filters( 'bp_rest_video_poster_delete_item_permissions_check', $retval, $request );
	}


	/**
	 * Get poster for the video.
	 *
	 * @since 0.1.0
	 *
	 * @param int             $id      Video ID.
	 * @param WP_REST_Request $request The request sent to the API.
	 *
	 * @return array
	 */
	public function get_video_poster( $id, $request ) {

		$videos = $this->video_endpoint->assemble_response_data( array( 'video_ids' => array( $id ) ) );

		if ( empty( $videos['videos'] ) ) {
			return new WP_Error(
				'bp_rest_video_invalid_id',
				__( 'Invalid video ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$video = current( $videos['videos'] );

		$posters = $this->bp_rest_video_poster_prepare_value( $video, $request );

		if ( empty( $posters ) ) {
			$posters = array(
				array(
					'url'           => array(
						'full'  => esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/images/video-placeholder.jpg' ),
						'thumb' => esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/images/video-placeholder.jpg' ),
					),
					'attachment_id' => '',
					'preview'       => true,
					'can_delete'    => false,
					'can_set'       => false,
				),
			);
		}

		$retval = array();
		foreach ( $posters as $poster ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $poster, $request )
			);
		}

		return $retval;
	}

	/**
	 * Prepares video thumbs data for return as an object.
	 *
	 * @since 0.1.0
	 *
	 * @param BP_Video        $video   Video data.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function bp_rest_video_poster_prepare_value( $video, $request ) {
		$posters                   = array();
		$auto_generated_thumbnails = get_post_meta( $video->attachment_id, 'video_preview_thumbnails', true );
		$preview_thumbnail_id      = get_post_meta( $video->attachment_id, 'bp_video_preview_thumbnail_id', true );

		if ( ! empty( $auto_generated_thumbnails['default_images'] ) ) {
			foreach ( $auto_generated_thumbnails['default_images'] as $auto_generated_thumbnail ) {
				$posters[] = array(
					'url'           => array(
						'full'  => bb_video_get_attachment_symlink( $video, $auto_generated_thumbnail, 'bb-video-activity-image' ),
						'thumb' => bb_video_get_attachment_symlink( $video, $auto_generated_thumbnail, 'bb-video-poster-popup-image' ),
					),
					'attachment_id' => $auto_generated_thumbnail,
					'preview'       => ( (int) $preview_thumbnail_id === (int) $auto_generated_thumbnail ) ? true : false,
					'can_delete'    => false,
					'can_set'       => ( empty( $preview_thumbnail_id ) || in_array( $preview_thumbnail_id, $auto_generated_thumbnails, true ) ) ? true : false,
				);
			}
		}

		if ( ! empty( $auto_generated_thumbnails['custom_image'] ) ) {
			$posters[] = array(
				'url'           => array(
					'full'  => bb_video_get_attachment_symlink( $video, $auto_generated_thumbnails['custom_image'], 'bb-video-activity-image' ),
					'thumb' => bb_video_get_attachment_symlink( $video, $auto_generated_thumbnails['custom_image'], 'bb-video-poster-popup-image' ),
				),
				'attachment_id' => $auto_generated_thumbnails['custom_image'],
				'preview'       => ( (int) $preview_thumbnail_id === (int) $auto_generated_thumbnails['custom_image'] ) ? true : false,
				'can_delete'    => true,
				'can_set'       => true,
			);
		}

		return $posters;
	}

	/**
	 * Prepares video poster data for return as an object.
	 *
	 * @param array           $poster  Poster Data.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	public function prepare_item_for_response( $poster, $request ) {
		$data = array(
			'attachment_id' => ( ! empty( $poster['attachment_id'] ) ? $poster['attachment_id'] : 0 ),
			'url'           => array(
				'full'  => ( ! empty( $poster['url'] ) && ! empty( $poster['url']['full'] ) ? $poster['url']['full'] : '' ),
				'thumb' => ( ! empty( $poster['url'] ) && ! empty( $poster['url']['thumb'] ) ? $poster['url']['thumb'] : '' ),
			),
			'preview'       => (bool) ( ! empty( $poster['preview'] ) ? $poster['preview'] : false ),
			'can_delete'    => (bool) ( ! empty( $poster['can_delete'] ) ? $poster['can_delete'] : false ),
			'can_set'       => (bool) ( ! empty( $poster['can_set'] ) ? $poster['can_set'] : false ),
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$response = rest_ensure_response( $data );

		/**
		 * Filter a video poster value returned from the API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param array            $poster   Poster Data.
		 *
		 * @param WP_REST_Response $response The response data.
		 */
		return apply_filters( 'bp_rest_video_poster_prepare_value', $response, $request, $poster );
	}

	/**
	 * Select the item schema arguments needed for the CREATABLE methods.
	 *
	 * @since 0.1.0
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = array();
		$key  = 'create';

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::DELETABLE === $method ) {
			$args['id'] = array(
				'description'       => __( 'A unique numeric ID for the video.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['attachment_id'] = array(
				'description'       => __( 'A Unique numeric ID for the video poster.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		if ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete';
		}

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args['attachment_id']['required'] = false;
			$args['file']                      = array(
				'description' => __( 'File path for video poster.', 'buddyboss' ),
				'type'        => 'string',
				'required'    => false,
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 0.1.0
		 *
		 * @param string $method HTTP method of the request.
		 *
		 * @param array  $args   Query arguments.
		 */
		return apply_filters( "bp_rest_video_poster_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the video poster schema, conforming to JSON Schema.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_video_poster',
			'type'       => 'object',
			'properties' => array(
				'attachment_id' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A Unique numeric ID for the video poster.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
				),
				'url'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'URL of the Video poster.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'full'  => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Video poster URL with full image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'thumb' => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Video poster URL with thumbnail image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
					),
				),
				'preview'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether to check it\'s setup for the preview or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_delete'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether to check user can delete the video poster image or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_set'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether to check user can set as preview or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the video poster schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_video_poster_schema', $this->add_additional_fields_schema( $schema ) );
	}

}
