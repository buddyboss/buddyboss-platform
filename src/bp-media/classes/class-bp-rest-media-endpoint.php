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
	 * @apiParam {Number} [activity_id] A unique numeric ID for the Media's Activity.
	 * @apiParam {Array=public,loggedin,onlyme,friends,grouponly} [privacy=public] Privacy of the media.
	 * @apiParam {Array=friends,groups,personal} [scope] Scope of the media.
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
			$args['media_ids'] = $request['include'];
			if (
				! empty( $args['order_by'] )
				&& 'include' === $args['order_by']
			) {
				$args['order_by'] = 'in';
			}
		}

		$args['scope'] = $this->bp_rest_media_default_scope( $args['scope'], $args );

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_media_get_items_query_args', $args, $request );

		if ( isset( $args['album_id'] ) && 0 === $args['album_id'] ) {
			$args['album_id'] = 'existing-media';
		}

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
	 * @apiPermission  LoggedInUser if the site is in Private Network.
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

		if ( function_exists( 'bp_rest_enable_private_network' ) && true === bp_rest_enable_private_network() && ! is_user_logged_in() ) {
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

		if (
			true === $retval
			&& 'public' !== $media->privacy
			&& ! bp_current_user_can( 'bp_moderate' )
			&& true === $this->bp_rest_check_privacy_restriction( $media )
		) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to view this media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
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
	 * @apiDescription Create Media Photos. This endpoint requires request to be sent in "multipart/form-data" format.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} upload_ids Media specific IDs.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the activity.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [album_id] A unique numeric ID for the Media Album.
	 * @apiParam {string} [content] Media Content.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly} [privacy=public] Privacy of the media.
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
			$album            = new BP_Media_Album( $args['album_id'] );
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

		if (
			function_exists( 'bb_media_user_can_upload' ) &&
			! bb_media_user_can_upload( bp_loggedin_user_id(), (int) ( isset( $args['group_id'] ) ? $args['group_id'] : 0 ) )
		) {
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to create a folder.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
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

		$fields_update = $this->update_additional_fields_for_object( $medias['medias'], $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

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
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a media.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if (
			is_user_logged_in() &&
			(
				! function_exists( 'bb_media_user_can_upload' ) ||
				(
					function_exists( 'bb_media_user_can_upload' ) &&
					bb_media_user_can_upload( bp_loggedin_user_id(), $request->get_param( 'group_id' ) )
				)
			)
		) {
			$retval = true;

			if (
				isset( $request['group_id'] ) &&
				! empty( $request['group_id'] ) &&
				(
					! bp_is_active( 'groups' )
					|| ! groups_can_user_manage_media( bp_loggedin_user_id(), (int) $request['group_id'] )
					|| ! function_exists( 'bp_is_group_media_support_enabled' )
					|| ( function_exists( 'bp_is_group_media_support_enabled' ) && false === bp_is_group_media_support_enabled() )
				)
			) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to create a media inside this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( isset( $request['album_id'] ) && ! empty( $request['album_id'] ) ) {
				$parent_album = new BP_Media_Album( $request['album_id'] );
				if ( empty( $parent_album->id ) ) {
					$retval = new WP_Error(
						'bp_rest_invalid_album_id',
						__( 'Invalid Album ID.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}

				$album_privacy = bb_media_user_can_access( $parent_album->id, 'album' );
				if ( true === $retval && true !== (bool) $album_privacy['can_add'] ) {
					$retval = new WP_Error(
						'bp_rest_invalid_permission',
						__( 'You don\'t have a permission to create a media inside this album.', 'buddyboss' ),
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
						'bp_rest_invalid_media_author',
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
					function_exists( 'bp_get_attachment_media_id' ) && ! empty( bp_get_attachment_media_id( (int) $attachment_id ) ) &&
					empty( $request['album_id'] ) ) {
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
				}
			}
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
	 * @apiParam {string} [content] Media Content.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly} [privacy] Privacy of the media.
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
			'activity_id'   => $media->activity_id,
			'message_id'    => $media->message_id,
			'user_id'       => $media->user_id,
			'menu_order'    => $media->menu_order,
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
			$moved_media_id   = bp_media_move_media_to_album( $args['id'], $args['album_id'], $args['group_id'] );
			if ( empty( (int) $moved_media_id ) || is_wp_error( $moved_media_id ) ) {
				return new WP_Error(
					'bp_rest_invalid_move_with_album',
					__( 'Sorry, you are not allowed to move this media with album.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$moved_media = new BP_Media( (int) $moved_media_id );
				if ( ! empty( $moved_media ) ) {
					$args['group_id'] = $moved_media->group_id;
					$args['album_id'] = $moved_media->album_id;
					$args['privacy']  = $moved_media->privacy;
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
		$args = apply_filters( 'bp_rest_media_update_items_query_args', $args, $request );

		$id = $this->bp_rest_create_media( $args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		if ( empty( $id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_media',
				__( 'Cannot update existing media.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$medias = $this->assemble_response_data( array( 'media_ids' => array( $request['id'] ) ) );
		$media  = current( $medias['medias'] );

		$fields_update = $this->update_additional_fields_for_object( $media, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = '';
		foreach ( $medias['medias'] as $media ) {
			$retval = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $request )
			);
		}

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
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to update this media.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$media  = new BP_Media( $request['id'] );

			if ( empty( $media->id ) ) {
				$retval = new WP_Error(
					'bp_rest_media_invalid_id',
					__( 'Invalid media ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				! bp_media_user_can_edit( $media ) ||
				(
					function_exists( 'bb_media_user_can_upload' ) &&
					! bb_media_user_can_upload( bp_loggedin_user_id(), (int) ( isset( $request['group_id'] ) ? $request['group_id'] : $media->group_id ) )
				)
			) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update this media.', 'buddyboss' ),
					array(
						'status' => 500,
					)
				);
			} elseif (
				isset( $request['group_id'] ) &&
				! empty( $request['group_id'] ) &&
				(
					! bp_is_active( 'groups' )
					|| ! groups_can_user_manage_media( bp_loggedin_user_id(), (int) $request['group_id'] )
				)
			) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to edit a media inside this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		if ( true === $retval && isset( $request['album_id'] ) && ! empty( $request['album_id'] ) ) {
			if ( ! bp_album_user_can_edit( (int) $request['album_id'] ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to move/update a media inside this Album.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
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
	 * @api            {DELETE} /wp-json/buddyboss/v1/media/ Delete Medias
	 * @apiName        DeleteBBPhotos
	 * @apiGroup       Media
	 * @apiDescription Delete Multiple Photos/Videos.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} media_ids A unique numeric IDs for the media photo/video.
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

		$medias = BP_Media::get(
			array(
				'in'       => $media_ids,
				'video'    => true,
				'per_page' => 0,
			)
		);

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

		foreach ( $medias['medias'] as $media ) {
			if ( empty( $media->type ) || 'photo' === $media->type ) {
				if ( ! bp_media_user_can_delete( $media->id ) ) {
					$status[ $media->id ] = new WP_Error(
						'bp_rest_authorization_required',
						__( 'Sorry, you are not allowed to delete this media.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				} else {
					$status[ $media->id ] = bp_media_delete( array( 'id' => $media->id ) );
				}
			} else {
				if ( ! bp_video_user_can_delete( $media->id ) ) {
					$status[ $media->id ] = new WP_Error(
						'bp_rest_authorization_required',
						__( 'Sorry, you are not allowed to delete this media.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				} else {
					$status[ $media->id ] = bp_video_delete( array( 'id' => $media->id ) );
				}
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
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this media.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && ! empty( $request['media_ids'] ) ) {
			$retval = true;
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
			return new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this media.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$status = bp_media_delete( array( 'id' => $id ) );

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
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this media.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$media  = new BP_Media( $request['id'] );

			if ( empty( $media->id ) ) {
				$retval = new WP_Error(
					'bp_rest_media_invalid_id',
					__( 'Invalid media ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! bp_media_user_can_delete( $media ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this media.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
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
	 * @apiDescription Upload Media. This endpoint requires request to be sent in "multipart/form-data" format.
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
				'bp_rest_media_file_required',
				__( 'Sorry, you have not uploaded any media.', 'buddyboss' ),
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

		add_filter( 'upload_dir', 'bp_media_upload_dir_script' );

		/**
		 * Create and upload the media file.
		 */
		$upload = bp_media_upload();

		remove_filter( 'upload_dir', 'bp_media_upload_dir_script' );

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
			'name'         => $upload['name'],
		);

		if ( 'messages' === $request->get_param( 'component' ) && isset( $upload['msg_url'] ) ) {
			$retval['msg_url'] = $upload['msg_url'];
		}

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

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to upload media.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
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
			'enum'              => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly' ),
			'default'           => 'public',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( WP_REST_Server::EDITABLE === $method ) {
			$args['id'] = array(
				'description'       => __( 'A unique numeric ID for the media.', 'buddyboss' ),
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
			'id'                    => $media->id,
			'blog_id'               => $media->blog_id,
			'attachment_id'         => $media->attachment_id,
			'user_id'               => $media->user_id,
			'title'                 => $media->title,
			'description'           => wp_specialchars_decode( get_post_field( 'post_content', $media->attachment_id ), ENT_QUOTES ),
			'album_id'              => $media->album_id,
			'group_id'              => $media->group_id,
			'activity_id'           => $media->activity_id,
			'message_id'            => $media->message_id,
			'hide_activity_actions' => false,
			'privacy'               => $media->privacy,
			'menu_order'            => $media->menu_order,
			'date_created'          => $media->date_created,
			'attachment_data'       => $media->attachment_data,
			'group_name'            => ( isset( $media->group_name ) ? $media->group_name : '' ),
			'visibility'            => ( isset( $media->visibility ) ? $media->visibility : '' ),
			'user_nicename'         => get_the_author_meta( 'user_nicename', $media->user_id ),
			'user_login'            => get_the_author_meta( 'user_login', $media->user_id ),
			'display_name'          => bp_core_get_user_displayname( $media->user_id ),
			'url'                   => bp_media_get_preview_image_url( $media->id, $media->attachment_id, 'bb-media-photos-popup-image' ),
			'download_url'          => bp_media_download_link( $media->attachment_id, $media->id ),
			'user_permissions'      => $this->get_media_current_user_permissions( $media ),
			'type'                  => $media->type,
		);

		// Below condition will check if media has comments then like/comment button will not visible for that particular media.
		if ( ! empty( $data['activity_id'] ) && bp_is_active( 'activity' ) ) {
			$activity = new BP_Activity_Activity( $data['activity_id'] );
			if ( isset( $activity->secondary_item_id ) ) {
				$get_activity = new BP_Activity_Activity( $activity->secondary_item_id );
				if (
					! empty( $get_activity->id ) &&
					(
						( in_array( $activity->type, array( 'activity_update', 'activity_comment' ), true ) && ! empty( $get_activity->secondary_item_id ) && ! empty( $get_activity->item_id ) )
						|| 'public' === $activity->privacy && empty( $get_activity->secondary_item_id ) && empty( $get_activity->item_id )
					)
				) {
					$data['hide_activity_actions'] = true;
				}
			}
		}

		if ( 'video' === $media->type ) {
			add_filter( 'bb_check_ios_device', array( $this, 'bb_rest_disable_symlink' ), 1 );
			$data['url'] = bb_video_get_symlink( $media->id );
			remove_filter( 'bb_check_ios_device', array( $this, 'bb_rest_disable_symlink' ), 1 );

			// Update the download link for the video.
			$data['download_url'] = bp_video_download_link( $media->attachment_id, $media->id );
		}

		$data = $this->add_additional_fields_to_object( $data, $request );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $media ) );

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
	 * Prepare links for the request.
	 *
	 * @param BP_Media $media Media data.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	protected function prepare_links( $media ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );
		$url  = $base . $media->id;

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $media->user_id ) ),
				'embeddable' => true,
			),
		);

		if ( ! empty( $media->activity_id ) && bp_is_active( 'activity' ) ) {
			$activity_base     = sprintf( '/%s/%s/', $this->namespace, buddypress()->activity->id );
			$activity_url      = $activity_base . $media->activity_id;
			$links['activity'] = array(
				'href'       => rest_url( $activity_url ),
				'embeddable' => true,
			);
		}

		// Video URL support.
		if ( ! empty( $media->type ) && 'video' === $media->type ) {
			$video_base = sprintf( '/%s/%s/', $this->namespace, 'video' );

			$links['self']       = array(
				'href' => rest_url( $video_base . $media->id ),
			);
			$links['collection'] = array(
				'href' => rest_url( $video_base ),
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 0.1.0
		 *
		 * @param array    $links The prepared links of the REST response.
		 * @param BP_Media $media Media data.
		 */
		return apply_filters( 'bp_rest_media_prepare_links', $links, $media );
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
				'id'                    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Media.', 'buddyboss' ),
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
					'description' => __( 'Unique identifier for the media object.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_id'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID for the author of the media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'title'                 => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Media title.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'description'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Media description.', 'buddyboss' ),
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
					'description' => __( 'Privacy of the media.', 'buddyboss' ),
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
					'description' => __( 'The date the media was created, in the site\'s timezone.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'attachment_data'       => array(
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
				'group_name'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group name associate with the media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'visibility'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Visibility of the media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_nicename'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s nice name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_login'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s login name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'display_name'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s display name to create a media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'url'                   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Media file URL.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'download_url'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Download Media file URL.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_permissions'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current user\'s permission with the media.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
				),
				'type'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Current media type, photo or video.', 'buddyboss' ),
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
			'enum'              => array( 'date_created', 'menu_order', 'id', 'include' ),
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
				'enum' => array( 'public', 'loggedin', 'onlyme', 'friends', 'grouponly' ),
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
				$media_privacy = $album->privacy;
				$group_id      = $album->group_id;
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

			$parent_activity = get_post_meta( $wp_attachment_id, 'bp_media_parent_activity_id', true );
			if ( ! empty( $parent_activity ) && bp_is_active( 'activity' ) ) {
				$activity_id = $parent_activity;
				$all_medias  = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
				$all_medias  = explode( ',', $all_medias );
				$key         = array_search( $id, $all_medias, true );
				if ( false !== $key ) {
					unset( $all_medias[ $key ] );
				}
			}

			// extract the nice title name.
			$title = get_the_title( $wp_attachment_id );

			$add_media_args = array(
				'id'            => $id,
				'attachment_id' => $wp_attachment_id,
				'title'         => $title,
				'activity_id'   => $media_activity_id,
				'message_id'    => $message_id,
				'album_id'      => ( ! empty( $args['album_id'] ) ? $args['album_id'] : false ),
				'group_id'      => ( ! empty( $args['group_id'] ) ? $args['group_id'] : false ),
				'privacy'       => $media_privacy,
				'user_id'       => $user_id,
				'error_type'    => 'wp_error',
			);

			if ( isset( $args['menu_order'] ) ) {
				$add_media_args['menu_order'] = ( ! empty( $args['menu_order'] ) ? $args['menu_order'] : 0 );
			}

			$media_id = bp_media_add( $add_media_args );

			if ( is_int( $media_id ) ) {

				// save media is saved in attachment.
				update_post_meta( $wp_attachment_id, 'bp_media_saved', true );

				// save media meta for activity.
				if ( ! empty( $media_activity_id ) ) {
					update_post_meta( $wp_attachment_id, 'bp_media_activity_id', $media_activity_id );
				}

				// save media description while update.
				if ( false !== $content ) {
					$media_post['ID']           = $wp_attachment_id;
					$media_post['post_content'] = wp_filter_nohtml_kses( $content );
					wp_update_post( $media_post );
				}

				$created_media_ids[] = $media_id;

			}

			if ( ! empty( $all_medias ) ) {
				foreach ( $all_medias as $m_id ) {
					$media = new BP_Media( $m_id );
					if ( ! empty( $media->id ) ) {
						$created_media_ids[] = $media->id;
						$media->privacy      = $media_privacy;
						$media->save();
					}
				}
			}
		}

		// created Medias.
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

			$medias = array();

			if ( ! empty( $valid_upload_ids ) ) {
				foreach ( $valid_upload_ids as $wp_attachment_id ) {

					// Check if media id already available for the messages.
					if ( 'message' === $media_privacy ) {
						$mid = get_post_meta( $wp_attachment_id, 'bp_media_id', true );

						if ( ! empty( $mid ) ) {
							$created_media_ids[] = $mid;
							continue;
						}
					}

					// extract the nice title name.
					$title = get_the_title( $wp_attachment_id );

					$medias[] = array(
						'id'      => $wp_attachment_id,
						'name'    => $title,
						'privacy' => $media_privacy,
					);
				}
			}

			if ( ! empty( $medias ) ) {
				$created_media_ids = bp_media_add_handler( $medias, $media_privacy, $content, $group_id, $album_id );
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

		// Link all uploaded video to main activity.
		if ( ! empty( $activity_id ) ) {
			$main_activity = new BP_Activity_Activity( $activity_id );
			if ( ! empty( $main_activity->id ) && 'media' !== $main_activity->privacy ) {
				$created_media_ids_joined = implode( ',', $created_media_ids );
				bp_activity_update_meta( $activity_id, 'bp_media_ids', $created_media_ids_joined );

				if ( empty( $group_id ) ) {
					$main_activity->privacy = $media_privacy;
					$main_activity->save();
				}
			}
		}

		return $created_media_ids;
	}

	/**
	 * Register custom field for the activity api.
	 */
	public function bp_rest_media_support() {

		// Activity Media.
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

		// Activity Gif.
		bp_rest_register_field(
			'activity',      // Id of the BuddyPress component the REST field is about.
			'media_gif', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_gif_data_get_rest_field_callback' ),
				'update_callback' => array( $this, 'bp_gif_data_update_rest_field_callback' ), // The function to use to update the value of the REST Field.
				'schema'          => array(
					'description' => 'Activity Gifs.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Activity Comment Media.
		register_rest_field(
			'activity_comments',      // Id of the BuddyPress component the REST field is about.
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

		// Activity Comment Gif.
		register_rest_field(
			'activity_comments',      // Id of the BuddyPress component the REST field is about.
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

		// Added param to main activity to check the comment has access to upload media or not.
		bp_rest_register_field(
			'activity',
			'comment_upload_media',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_media' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload media or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to comment activity to check the child comment has access to upload media or not.
		register_rest_field(
			'activity_comments',
			'comment_upload_media',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_media' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload media or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

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
					'description' => 'Reply Medias.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

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
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

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
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to main activity to check the comment has access to upload gif or not.
		bp_rest_register_field(
			'activity',
			'comment_upload_gif',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_gif' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload gif or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to comment activity to check the child comment has access to upload gif or not.
		register_rest_field(
			'activity_comments',
			'comment_upload_gif',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_gif' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload gif or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to main activity to check the comment has access to upload emoji or not.
		bp_rest_register_field(
			'activity',
			'comment_upload_emoji',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_emoji' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload emoji or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to comment activity to check the child comment has access to upload emoji or not.
		register_rest_field(
			'activity_comments',
			'comment_upload_emoji',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_emoji' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload emoji or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);
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

		$value = new BP_Activity_Activity( $activity_id );

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
			(
				empty( $group_id ) &&
				! bp_is_profile_media_support_enabled()
			) ||
			(
				bp_is_active( 'groups' ) &&
				! empty( $group_id ) &&
				! bp_is_group_media_support_enabled()
			)
		) {
			return false;
		}

		$media_ids = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
		$media_id  = bp_activity_get_meta( $activity_id, 'bp_media_id', true );

		$media_ids = trim( $media_ids );
		$media_ids = explode( ',', $media_ids );

		if ( ! empty( $media_id ) ) {
			$media_ids[] = $media_id;
			$media_ids   = array_filter( array_unique( $media_ids ) );
		}

		if ( empty( $media_ids ) ) {
			return;
		}

		$medias = $this->assemble_response_data(
			array(
				'per_page'  => 0,
				'media_ids' => $media_ids,
				'sort'      => 'ASC',
				'order_by'  => 'menu_order',
			)
		);

		if ( empty( $medias['medias'] ) ) {
			return;
		}

		$retval = array();
		$object = new WP_REST_Request();
		$object->set_param( 'context', 'view' );

		foreach ( $medias['medias'] as $media ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $object )
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the medias's value of the activity REST Field.
	 * - from bp_media_update_activity_media_meta();
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Activity_Activity object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_media_ids_update_rest_field_callback( $object, $value, $attribute ) {

		global $bp_activity_edit, $bp_media_upload_count, $bp_new_activity_comment, $bp_activity_post_update_id, $bp_activity_post_update;

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
			'bp_media_ids' !== $attribute ||
			(
				function_exists( 'bb_user_has_access_upload_media' ) &&
				! bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), 0, 0, 'profile' )
			)
		) {
			$value->bp_media_ids = null;

			return $value;
		}

		$bp_activity_edit = ( isset( $value->edit ) ? true : false );
		// phpcs:ignore
		$_POST['edit'] = $bp_activity_edit;

		if ( false === $bp_activity_edit && empty( $object ) ) {
			return $value;
		}

		$bp_new_activity_comment = ( 'activity_comment' === $value->type ? $value->id : 0 );

		$activity_id = $value->id;
		$privacy     = $value->privacy;
		$group_id    = 0;

		$medias             = wp_parse_id_list( $object );
		$old_media_ids      = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
		$old_media_ids      = ( ! empty( $old_media_ids ) ? explode( ',', $old_media_ids ) : array() );
		$new_medias         = array();
		$old_medias         = array();
		$old_medias_objects = array();

		if ( ! empty( $old_media_ids ) ) {
			foreach ( $old_media_ids as $id ) {
				$media_object                                       = new BP_Media( $id );
				$old_medias_objects[ $media_object->attachment_id ] = $media_object;
				$old_medias[ $id ]                                  = $media_object->attachment_id;
			}
		}

		$bp_activity_post_update    = true;
		$bp_activity_post_update_id = $activity_id;

		if ( ! empty( $value->component ) && 'groups' === $value->component ) {
			$group_id = $value->item_id;
			$privacy  = 'grouponly';
		}

		if ( ! isset( $medias ) || empty( $medias ) ) {

			// delete media ids and meta for activity if empty media in request.
			if ( ! empty( $activity_id ) && ! empty( $old_media_ids ) ) {
				foreach ( $old_media_ids as $media_id ) {
					bp_media_delete( array( 'id' => $media_id ), 'activity' );
				}
				bp_activity_delete_meta( $activity_id, 'bp_media_ids' );
			}

			return $value;
		} else {

			$order_count = 0;
			foreach ( $medias as $id ) {

				$wp_attachment_url = wp_get_attachment_url( $id );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$order_count ++;

				if ( in_array( $id, $old_medias, true ) ) {
					$new_medias[] = array(
						'media_id' => $old_medias_objects[ $id ]->id,
					);
				} else {
					$new_medias[] = array(
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

		$bp_media_upload_count = count( $new_medias );

		remove_action( 'bp_activity_posted_update', 'bp_media_update_activity_media_meta', 10, 3 );
		remove_action( 'bp_groups_posted_update', 'bp_media_groups_activity_update_media_meta', 10, 4 );
		remove_action( 'bp_activity_comment_posted', 'bp_media_activity_comments_update_media_meta', 10, 3 );
		remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_media_activity_comments_update_media_meta', 10, 3 );

		$media_ids = bp_media_add_handler( $new_medias, $privacy, '', $group_id );

		add_action( 'bp_activity_posted_update', 'bp_media_update_activity_media_meta', 10, 3 );
		add_action( 'bp_groups_posted_update', 'bp_media_groups_activity_update_media_meta', 10, 4 );
		add_action( 'bp_activity_comment_posted', 'bp_media_activity_comments_update_media_meta', 10, 3 );
		add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_media_activity_comments_update_media_meta', 10, 3 );

		// save media meta for activity.
		if ( ! empty( $activity_id ) ) {
			// Delete media if not exists in current media ids.
			if ( ! empty( $old_media_ids ) ) {
				foreach ( $old_media_ids as $media_id ) {

					if ( ! in_array( (int) $media_id, $media_ids, true ) ) {
						bp_media_delete( array( 'id' => $media_id ), 'activity' );
					}
				}
			}
			bp_activity_update_meta( $activity_id, 'bp_media_ids', implode( ',', $media_ids ) );
		}
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

		$value = new BP_Activity_Activity( $activity_id );

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
			function_exists( 'bb_user_has_access_upload_gif' ) &&
			! bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), 0, 0, 'profile' )
		) {
			return;
		}

		$gif_data = bp_activity_get_meta( $activity_id, '_gif_data', true );

		if ( empty( $gif_data ) ) {
			return;
		}

		$preview_url   = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
		$video_url     = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];
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

		if ( 'media_gif' !== $attribute ) {
			return $value;
		}

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
			function_exists( 'bb_user_has_access_upload_gif' ) &&
			! bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), 0, 0, 'profile' )
		) {
			return $value;
		}

		$bp_activity_edit = ( isset( $value->edit ) ? true : false );
		// phpcs:ignore
		$_POST['edit'] = $bp_activity_edit;

		if ( empty( $object ) && false === $bp_activity_edit ) {
			return $value;
		}

		$still = $object['url'];
		$mp4   = $object['mp4'];

		if ( true === $bp_activity_edit && empty( $still ) && empty( $mp4 ) ) {
			bp_activity_delete_meta(
				$value->id,
				'_gif_data'
			);

			bp_activity_delete_meta(
				$value->id,
				'_gif_raw_data'
			);

			return $value;
		}

		if ( ! empty( $still ) && ! empty( $mp4 ) ) {

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
	 * The function to use to set `comment_upload_media`
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_rest_user_can_comment_upload_media( $activity, $attribute ) {
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

		if ( function_exists( 'bb_user_has_access_upload_media' ) ) {
			return bb_user_has_access_upload_media( $group_id, $user_id, 0, 0, 'profile' );
		}

		return false;
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
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['media_gif'] = array(
			'description' => __( 'Save gif data into activity', 'buddyboss' ),
			'type'        => 'object',
			'items'       => array( 'type' => 'string' ),
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

		$params['bbp_media'] = array(
			'description'       => __( 'Media specific IDs.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['bbp_media_gif'] = array(
			'description' => __( 'Save gif data into topic.', 'buddyboss' ),
			'type'        => 'object',
			'items'       => array( 'type' => 'string' ),
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

		$params['bp_media_ids'] = array(
			'description'       => __( 'Media specific IDs.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['media_gif'] = array(
			'description' => __( 'Save gif data into topic.', 'buddyboss' ),
			'type'        => 'object',
			'items'       => array( 'type' => 'string' ),
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

		// Get current forum ID.
		if ( bbp_get_reply_post_type() === get_post_type( $p_id ) ) {
			$forum_id = bbp_get_reply_forum_id( $p_id );
		} else {
			$forum_id = bbp_get_topic_forum_id( $p_id );
		}

		$group_ids = bbp_get_forum_group_ids( $forum_id );
		$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

		if (
			(
				(
					empty( $group_id ) ||
					(
						! empty( $group_id ) &&
						! bp_is_active( 'groups' )
					)
				) &&
				! bp_is_forums_media_support_enabled()
			) ||
			(
				bp_is_active( 'groups' ) &&
				! empty( $group_id ) &&
				! bp_is_group_media_support_enabled()
			)
		) {
			return;
		}

		$media_ids = get_post_meta( $p_id, 'bp_media_ids', true );
		$media_id  = get_post_meta( $p_id, 'bp_media_id', true );
		$media_ids = trim( $media_ids );
		$media_ids = explode( ',', $media_ids );

		if ( ! empty( $media_id ) ) {
			$media_ids[] = $media_id;
			$media_ids   = array_filter( array_unique( $media_ids ) );
		}

		if ( empty( $media_ids ) ) {
			return;
		}

		$medias = $this->assemble_response_data(
			array(
				'per_page'  => 0,
				'media_ids' => $media_ids,
				'sort'      => 'ASC',
				'order_by'  => 'menu_order',
			)
		);

		if ( empty( $medias['medias'] ) ) {
			return;
		}

		$retval = array();
		$object = new WP_REST_Request();

		foreach ( $medias['medias'] as $media ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $media, $object )
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the medias's value of the topic REST Field.
	 * - from bp_media_forums_new_post_media_save();
	 *
	 * @param object $object     Value for the schema.
	 * @param object $value      The value of the REST Field to save.
	 *
	 * @return object
	 */
	protected function bbp_media_update_rest_field_callback( $object, $value ) {

		// save media.
		$medias = wp_parse_id_list( $object );

		$edit = ( isset( $value->edit ) ? true : false );

		if ( empty( $medias ) && false === $edit ) {
			$value->bbp_media = null;

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

		if ( function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), $forum_id, 0, 'forum' );
			if ( ! $can_send_media ) {
				$value->bbp_media = null;

				return $value;
			}
		}

		$group_ids = bbp_get_forum_group_ids( $forum_id );
		$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

		// fetch currently uploaded media ids.
		$existing_media_ids            = get_post_meta( $post_id, 'bp_media_ids', true );
		$existing_media_attachments    = array();
		$existing_media_attachment_ids = array();

		if ( ! empty( $existing_media_ids ) ) {
			$existing_media_ids = explode( ',', $existing_media_ids );

			foreach ( $existing_media_ids as $existing_media_id ) {
				$bp_media = new BP_Media( $existing_media_id );

				if ( ! empty( $bp_media->attachment_id ) ) {
					$existing_media_attachment_ids[]                  = $bp_media->attachment_id;
					$existing_media_attachments[ $existing_media_id ] = $bp_media->attachment_id;
				}
			}
		}

		$media_ids  = array();
		$menu_order = 0;

		if ( ! empty( $medias ) ) {
			foreach ( $medias as $media ) {

				$wp_attachment_url = wp_get_attachment_url( $media );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$menu_order ++;

				$attachment_id = ! empty( $media ) ? $media : 0;
				$menu_order    = $menu_order;

				if ( ! empty( $existing_media_attachment_ids ) ) {
					$index = array_search( $attachment_id, $existing_media_attachment_ids, true );
					if ( ! empty( $attachment_id ) && false !== $index ) {
						$exisiting_media_id    = array_search( $attachment_id, $existing_media_attachments, true );
						$existing_media_update = new BP_Media( $exisiting_media_id );

						$existing_media_update->menu_order = $menu_order;
						$existing_media_update->save();

						unset( $existing_media_ids[ $index ] );
						$media_ids[] = $exisiting_media_id;
						continue;
					}
				}

				// extract the nice title name.
				$title = get_the_title( $attachment_id );

				$media_id = bp_media_add(
					array(
						'attachment_id' => $attachment_id,
						'title'         => $title,
						'group_id'      => $group_id,
						'privacy'       => 'forums',
						'error_type'    => 'wp_error',
					)
				);

				if ( ! is_wp_error( $media_id ) ) {
					$media_ids[] = $media_id;

					// save media is saved in attachment.
					update_post_meta( $attachment_id, 'bp_media_saved', true );
				}
			}
		}

		$media_ids = implode( ',', $media_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $post_id, 'bp_media_ids', $media_ids );

		// save media meta for activity.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_media_ids', $media_ids );
		}

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
	 * @param array  $post      WP_Post object.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bbp_media_gif_get_rest_field_callback( $post, $attribute ) {

		$p_id = $post['id'];

		if ( empty( $p_id ) ) {
			return;
		}

		// Get current forum ID.
		if ( bbp_get_reply_post_type() === get_post_type( $p_id ) ) {
			$forum_id = bbp_get_reply_forum_id( $p_id );
		} else {
			$forum_id = bbp_get_topic_forum_id( $p_id );
		}

		$group_ids = bbp_get_forum_group_ids( $forum_id );
		$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

		if (
			(
				(
					empty( $group_id ) ||
					(
						! empty( $group_id ) &&
						! bp_is_active( 'groups' )
					)
				) &&
				! bp_is_forums_gif_support_enabled()
			) ||
			(
				bp_is_active( 'groups' ) &&
				! empty( $group_id ) &&
				! bp_is_groups_gif_support_enabled()
			)
		) {
			return;
		}

		$gif_data = get_post_meta( $p_id, '_gif_data', true );

		if ( empty( $gif_data ) ) {
			return;
		}

		$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
		$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

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
	 * @param object $object Topics as a object.
	 * @param object $value  The value of the REST Field to save.
	 */
	protected function bbp_media_gif_update_rest_field_callback( $object, $value ) {

		$still   = ( ! empty( $object ) && array_key_exists( 'url', $object ) ) ? $object['url'] : '';
		$mp4     = ( ! empty( $object ) && array_key_exists( 'mp4', $object ) ) ? $object['mp4'] : '';
		$post_id = $value->ID;

		// Get current forum ID.
		if ( 'reply' === $value->post_type ) {
			$forum_id = bbp_get_reply_forum_id( $post_id );
		} else {
			$forum_id = bbp_get_topic_forum_id( $post_id );
		}

		if ( function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), $forum_id, 0, 'forum' );
			if ( ! $can_send_gif ) {
				return;
			}
		}

		if ( ! empty( $still ) && ! empty( $mp4 ) ) {

			// save activity id if it is saved in forums and enabled in platform settings.
			$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

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

			// Delete activity meta as well.
			$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );
			if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
				bp_activity_delete_meta( $main_activity_id, '_gif_data' );
				bp_activity_delete_meta( $main_activity_id, '_gif_raw_data' );
			}
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

		$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
		$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

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
	 * @return array|void The value of the REST Field to include into the REST response.
	 */
	protected function bp_media_ids_get_rest_field_callback_messages( $data, $attribute ) {
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
			bp_is_active( 'media' ) &&
			(
				(
					! empty( $group_name ) &&
					'group' === $message_from &&
					bp_is_group_media_support_enabled()
				) ||
				(
					'group' !== $message_from &&
					bp_is_messages_media_support_enabled()
				)
			)
		) {
			$media_ids = bp_messages_get_meta( $message_id, 'bp_media_ids', true );
			$media_id  = bp_messages_get_meta( $message_id, 'bp_media_id', true );
			$media_ids = trim( $media_ids );
			$media_ids = explode( ',', $media_ids );

			if ( ! empty( $media_id ) ) {
				$media_ids[] = $media_id;
				$media_ids   = array_filter( array_unique( $media_ids ) );
			}

			if ( empty( $media_ids ) ) {
				return;
			}

			$medias = $this->assemble_response_data(
				array(
					'media_ids'        => $media_ids,
					'sort'             => 'ASC',
					'order_by'         => 'menu_order',
					'moderation_query' => false,
				)
			);

			if ( empty( $medias['medias'] ) ) {
				return;
			}

			$retval = array();
			$object = new WP_REST_Request();
			$object->set_param( 'context', 'view' );

			foreach ( $medias['medias'] as $media ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $media, $object )
				);
			}

			return $retval;
		}
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

		$thread_id = $value->thread_id;

		if ( function_exists( 'bb_user_has_access_upload_media' ) ) {
			$can_send_media = bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
			if ( ! $can_send_media ) {
				$value->bp_media_ids = null;
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

		if ( empty( apply_filters( 'bp_user_can_create_message_media', bb_user_has_access_upload_media( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' ), $thread, bp_loggedin_user_id() ) ) ) {
			$value->bp_media_ids = null;

			return $value;
		}

		$args = array(
			'upload_ids' => $medias,
			'privacy'    => 'message',
		);

		remove_action( 'bp_media_add', 'bp_activity_media_add', 9 );
		remove_filter( 'bp_media_add_handler', 'bp_activity_create_parent_media_activity', 9 );

		$medias_ids = $this->bp_rest_create_media( $args );

		add_action( 'bp_media_add', 'bp_activity_media_add', 9 );
		add_filter( 'bp_media_add_handler', 'bp_activity_create_parent_media_activity', 9 );

		if ( is_wp_error( $medias_ids ) ) {
			$value->bp_media_ids = $medias_ids;

			return $value;
		}

		bp_messages_update_meta( $message_id, 'bp_media_ids', implode( ',', $medias_ids ) );
	}

	/**
	 * The function to use to get medias gif for the Messages REST Field.
	 *
	 * @param array $message The message value for the REST response.
	 *
	 * @return array|void The value of the REST Field to include into the REST response.
	 */
	protected function bp_gif_data_get_rest_field_callback_messages( $message ) {
		$message_id = $message['id'];

		if ( empty( $message_id ) ) {
			return;
		}

		$thread_id = ! empty( $message['thread_id'] ) ? $message['thread_id'] : 0;
		if ( empty( $thread_id ) ) {
			return;
		}

		$group_name   = ! empty( $message['group_name'] ) ? $message['group_name'] : '';
		$message_from = ! empty( $message['message_from'] ) ? $message['message_from'] : '';

		if (
			bp_is_active( 'media' ) &&
			(
				(
					! empty( $group_name ) &&
					'group' === $message_from &&
					bp_is_groups_gif_support_enabled()
				) ||
				(
					'group' !== $message_from &&
					bp_is_messages_gif_support_enabled()
				)
			)
		) {
			$gif_data = bp_messages_get_meta( $message_id, '_gif_data', true );

			if ( empty( $gif_data ) ) {
				return;
			}

			$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
			$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

			return array(
				'preview_url' => $preview_url,
				'video_url'   => $video_url,
				'rendered'    => $this->bp_rest_media_message_embed_gif( $message_id ),
			);
		}
	}

	/**
	 * The function to use to update the medias's value of the activity REST Field.
	 *
	 * @param object $object Message as a object.
	 * @param object $value  The value of the REST Field to save.
	 */
	protected function bp_gif_data_update_rest_field_callback_messages( $object, $value ) {

		$still      = ( ! empty( $object ) && array_key_exists( 'url', $object ) ) ? $object['url'] : '';
		$mp4        = ( ! empty( $object ) && array_key_exists( 'mp4', $object ) ) ? $object['mp4'] : '';
		$message_id = $value->id;

		$thread_id = $value->thread_id;

		if ( function_exists( 'bb_user_has_access_upload_gif' ) ) {
			$can_send_gif = bb_user_has_access_upload_gif( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
			if ( ! $can_send_gif ) {
				return;
			}
		}

		if ( ! empty( $still ) && ! empty( $mp4 ) ) {

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

		$preview_url = ( is_int( $gif_data['still'] ) ) ? wp_get_attachment_url( $gif_data['still'] ) : $gif_data['still'];
		$video_url   = ( is_int( $gif_data['mp4'] ) ) ? wp_get_attachment_url( $gif_data['mp4'] ) : $gif_data['mp4'];

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
	 * Get default scope for the media.
	 * - from bp_media_default_scope().
	 *
	 * @since 0.1.0
	 *
	 * @param string $scope Default scope.
	 * @param array  $args  Array of request parameters.
	 *
	 * @return string
	 */
	public function bp_rest_media_default_scope( $scope, $args = array() ) {
		$new_scope = array();

		if ( is_array( $scope ) ) {
			$scope = array_filter( $scope );
		}

		if ( ( 'all' === $scope || empty( $scope ) ) && ( empty( $args['group_id'] ) && empty( $args['user_id'] ) ) ) {
			$new_scope[] = 'public';

			if ( bp_is_active( 'friends' ) && bp_is_profile_media_support_enabled() ) {
				$new_scope[] = 'friends';
			}

			if ( bp_is_active( 'groups' ) && bp_is_group_media_support_enabled() ) {
				$new_scope[] = 'groups';
			}

			if ( is_user_logged_in() && bp_is_profile_media_support_enabled() ) {
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
		$new_scope = apply_filters( 'bp_rest_media_default_scope', $new_scope );

		return implode( ',', $new_scope );
	}

	/**
	 * Check user access based on the privacy for the single Media.
	 *
	 * @param BP_Media $media Media object.
	 *
	 * @return bool
	 */
	protected function bp_rest_check_privacy_restriction( $media ) {
		$bool = ( 'onlyme' === $media->privacy && bp_loggedin_user_id() !== $media->user_id ) ||
			(
				'loggedin' === $media->privacy
				&& empty( bp_loggedin_user_id() )
			) ||
			(
				bp_is_active( 'groups' )
				&& 'grouponly' === $media->privacy
				&& ! empty( $media->group_id )
				&& 'public' !== bp_get_group_status( groups_get_group( $media->group_id ) )
				&& empty( groups_is_user_admin( bp_loggedin_user_id(), $media->group_id ) )
				&& empty( groups_is_user_mod( bp_loggedin_user_id(), $media->group_id ) )
				&& empty( groups_is_user_member( bp_loggedin_user_id(), $media->group_id ) )
			) ||
			(
				bp_is_active( 'friends' )
				&& 'friends' === $media->privacy
				&& ! empty( $media->user_id )
				&& bp_loggedin_user_id() !== $media->user_id
				&& 'is_friend' !== friends_check_friendship_status( $media->user_id, bp_loggedin_user_id() )
			);

		return $bool;
	}

	/**
	 * Get media permissions based on current user.
	 *
	 * @param BP_Media $media   The Media object.
	 *
	 * @return array
	 */
	public function get_media_current_user_permissions( $media ) {
		$retval = array(
			'download'           => 0,
			'edit_privacy'       => 0,
			'edit_post_privacy'  => 0,
			'edit_album_privacy' => 0,
			'edit_description'   => 0,
			'move'               => 0,
			'delete'             => 0,
		);

		if ( empty( $media->type ) || 'photo' === $media->type ) {
			$media_privacy = bb_media_user_can_access( $media->id, 'photo' );
		} else {
			$media_privacy           = bb_media_user_can_access( $media->id, 'video' );
			$retval['upload_poster'] = 0;
		}

		if ( ! empty( $media_privacy ) ) {
			if ( isset( $media_privacy['can_download'] ) && true === (bool) $media_privacy['can_download'] ) {
				$retval['download'] = 1;
			}

			if ( isset( $media_privacy['can_move'] ) && true === (bool) $media_privacy['can_move'] ) {
				$retval['move'] = 1;
			}

			if ( isset( $media_privacy['can_edit'] ) && true === (bool) $media_privacy['can_edit'] ) {
				$retval['edit_description'] = 1;
				if ( array_key_exists( 'upload_poster', $retval ) ) {
					$retval['upload_poster'] = 1;
				}

				if ( 0 === (int) $media->group_id && 0 === (int) $media->album_id ) {
					if ( ! empty( $media->attachment_id ) && bp_is_active( 'activity' ) ) {
						if ( ! empty( $media->type ) && 'video' === $media->type ) {
							$parent_activity_id = get_post_meta( $media->attachment_id, 'bp_video_parent_activity_id', true );
						} else {
							$parent_activity_id = get_post_meta( $media->attachment_id, 'bp_media_parent_activity_id', true );
						}
						if ( ! empty( $parent_activity_id ) ) {
							$retval['edit_post_privacy'] = $parent_activity_id;
						} else {
							$retval['edit_post_privacy'] = $media->activity_id;
						}

						$activity = new BP_Activity_Activity( (int) $retval['edit_post_privacy'] );
						if ( ! empty( $activity->id ) && ! empty( $activity->type ) && 'activity_comment' === $activity->type ) {
							$retval['edit_post_privacy'] = 0;
						}
					} else {
						$retval['edit_privacy'] = 1;
					}
				} elseif ( 0 === (int) $media->group_id && 0 !== (int) $media->album_id ) {
					$retval['edit_album_privacy'] = $media->album_id;
				}
			}

			if ( isset( $media_privacy['can_delete'] ) && true === (bool) $media_privacy['can_delete'] ) {
				$retval['delete'] = 1;
			}
		}

		return $retval;
	}

	/**
	 * Disabled symlink for the video api.
	 *
	 * @param bool $retval Return value.
	 *
	 * @return bool
	 */
	public function bb_rest_disable_symlink( $retval ) {
		return true;
	}

	/**
	 * The function to use to set `comment_upload_gif`
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_rest_user_can_comment_upload_gif( $activity, $attribute ) {
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

		if ( function_exists( 'bb_user_has_access_upload_gif' ) ) {
			return bb_user_has_access_upload_gif( $group_id, $user_id, 0, 0, 'profile' );
		}

		return false;
	}

	/**
	 * The function to use to set `comment_upload_emoji`
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_rest_user_can_comment_upload_emoji( $activity, $attribute ) {
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

		if ( function_exists( 'bb_user_has_access_upload_emoji' ) ) {
			return bb_user_has_access_upload_emoji( $group_id, $user_id, 0, 0, 'profile' );
		}

		return false;
	}
}
