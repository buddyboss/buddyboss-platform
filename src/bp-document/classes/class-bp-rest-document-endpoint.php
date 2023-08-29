<?php
/**
 * BP REST: BP_REST_Document_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Document endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Document_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'document';

		$this->bp_rest_document_support();
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
						'description' => __( 'A unique numeric ID for the document.', 'buddyboss' ),
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
	 * Upload Document.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/document/upload Upload Document
	 * @apiName        UploadBBDocument
	 * @apiGroup       Document
	 * @apiDescription Upload Document. This endpoint requires request to be sent in "multipart/form-data" format.
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
				'bp_rest_document_file_required',
				__( 'Sorry, you have not uploaded any document.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if (
			isset( $file['file']['size'] ) &&
			function_exists( 'bp_media_allowed_upload_document_size' ) &&
			$file['file']['size'] > bp_media_allowed_upload_document_size() * 1048576
		) {
			return new WP_Error(
				'bp_rest_max_upload_size',
				sprintf(
					/* translators: 1: File size, 2: Allowed size. */
					__( 'File is too large (%1$s MB). Max file size: %2$s MB.', 'buddyboss' ),
					round( $file['file']['size'] / 1048576, 1 ),
					bp_media_allowed_upload_document_size()
				),
				array(
					'status' => 400,
				)
			);
		}

		add_filter( 'upload_dir', 'bp_document_upload_dir_script' );

		/**
		 * Create and upload the document file.
		 */
		$upload = bp_document_upload();

		remove_filter( 'upload_dir', 'bp_document_upload_dir_script' );

		if ( is_wp_error( $upload ) ) {
			return new WP_Error(
				'bp_rest_document_upload_error',
				$upload->get_error_message(),
				array(
					'status' => 400,
				)
			);
		}

		$retval = array(
			'id'                => $upload['id'],
			'url'               => $upload['url'],
			'name'              => $upload['name'],
			'type'              => $upload['type'],
			'size'              => $upload['size'],
			'extension'         => $upload['extension'],
			'svg_icon'          => $upload['svg_icon'],
			'svg_icon_download' => $upload['svg_icon_download'],
			'text'              => $upload['text'],
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a document is uploaded via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_upload_item', $response, $request );

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
			__( 'Sorry, you are not allowed to upload document.', 'buddyboss' ),
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
		return apply_filters( 'bp_rest_document_upload_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve documents.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/document Get Documents
	 * @apiName        GetBBDocuments
	 * @apiGroup       Document
	 * @apiDescription Retrieve Documents.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=asc,desc} [order=asc] Order sort attribute ascending or descending.
	 * @apiParam {String=title,date_created,date_modified,group_id,privacy,id,include} [orderby=title] Order by a specific parameter.
	 * @apiParam {Number} [user_id] Limit result set to items created by a specific user (ID).
	 * @apiParam {Number} [max] Maximum number of results to return.
	 * @apiParam {Number} [folder_id] A unique numeric ID for the Folder.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the Document's Activity.
	 * @apiParam {Array=public,loggedin,friends,onlyme,grouponly} [privacy=public] Privacy of the Document.
	 * @apiParam {Array=public,friends,groups,personal} [scope] Scope of the Document.
	 * @apiParam {Array} [exclude] Ensure result set excludes specific IDs.
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {String=both,document,folder} [type=both] Ensure result set includes specific document type.
	 * @apiParam {Boolean} [count_total=true] Show total count or not.
	 */
	public function get_items( $request ) {
		$args = array(
			'page'        => $request['page'],
			'per_page'    => $request['per_page'],
			'sort'        => strtoupper( $request['order'] ),
			'order_by'    => $request['orderby'],
			'count_total' => $request['count_total'],
			'scope'       => '',
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

		if ( ! empty( $request['folder_id'] ) ) {
			$args['folder_id'] = $request['folder_id'];
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
			$args['document_ids'] = $request['include'];
			if (
				! empty( $args['order_by'] )
				&& 'include' === $args['order_by']
			) {
				$args['order_by'] = 'in';
			}
		}

		if ( ! empty( $request['type'] ) ) {
			$args['type'] = $request['type'];
		}

		$args['scope'] = $this->bp_rest_document_default_scope( $args['scope'], $args );

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_document_get_items_query_args', $args, $request );

		$documents = $this->assemble_response_data( $args );

		$retval = array();
		foreach ( $documents['documents'] as $document ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $document, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $documents['total'], $args['per_page'] );

		/**
		 * Fires after a list of documents is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 *
		 * @param array            $documents Fetched documents.
		 */
		do_action( 'bp_rest_document_get_items', $documents, $response, $request );

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

		$group_id = $request->get_param( 'group_id' );
		$user_id  = $request->get_param( 'user_id' );

		if ( true === $retval && ! empty( $group_id ) && bp_is_active( 'groups' ) ) {
			$group       = groups_get_group( $group_id );
			$user_id     = ( ! empty( $user_id ) ? $user_id : bp_loggedin_user_id() );
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
		return apply_filters( 'bp_rest_document_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single document.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 * @api            {GET} /wp-json/buddyboss/v1/document/:id Get Document
	 * @apiName        GetBBDocument
	 * @apiGroup       Document
	 * @apiDescription Retrieve a single document.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the document.
	 */
	public function get_item( $request ) {

		$id = $request['id'];

		$documents = $this->assemble_response_data( array( 'document_ids' => array( $id ) ) );

		if ( empty( $documents['documents'] ) ) {
			return new WP_Error(
				'bp_rest_document_invalid_id',
				__( 'Invalid document ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = '';
		foreach ( $documents['documents'] as $document ) {
			$retval = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $document, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a document is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_get_item', $response, $request );

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

		$document = new BP_Document( $request->get_param( 'id' ) );

		if ( true === $retval && empty( $document->id ) ) {
			$retval = new WP_Error(
				'bp_rest_document_invalid_id',
				__( 'Invalid document ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if (
			true === $retval
			&& 'public' !== $document->privacy
			&& ! bp_current_user_can( 'bp_moderate' )
			&& true === $this->bp_rest_check_privacy_restriction( $document )
		) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to view this document.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the document `get_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 * @param bool            $retval  Returned value.
		 */
		return apply_filters( 'bp_rest_document_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create documents.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/document Create Document
	 * @apiName        CreateBBDocument
	 * @apiGroup       Document
	 * @apiDescription Create Document.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} document_ids Document specific IDs.
	 * @apiParam {Number} [activity_id] A unique numeric ID for the activity.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [folder_id] A unique numeric ID for the Document Folder.
	 * @apiParam {string} [content] Document Content.
	 * @apiParam {string=public,loggedin,friends,onlyme,grouponly} [privacy=public] Privacy of the Document.
	 */
	public function create_item( $request ) {

		$args = array(
			'document_ids' => $request['document_ids'],
			'privacy'      => $request['privacy'],
		);

		if ( empty( $request['document_ids'] ) ) {
			return new WP_Error(
				'bp_rest_no_document_found',
				__( 'Sorry, you are not allowed to create a Document item.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( isset( $request['folder_id'] ) && ! empty( $request['folder_id'] ) ) {
			$args['folder_id'] = $request['folder_id'];
			$parent_folder     = new BP_Document_Folder( $args['folder_id'] );
			$args['privacy']   = $parent_folder->privacy;
			$args['group_id']  = $parent_folder->group_id;
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
		$args = apply_filters( 'bp_rest_document_create_items_query_args', $args, $request );

		$document_ids = $this->bp_rest_create_document( $args );

		if ( is_wp_error( $document_ids ) ) {
			return $document_ids;
		}

		$documents = $this->assemble_response_data( array( 'document_ids' => $document_ids ) );
		$document  = current( $documents['documents'] );

		$fields_update = $this->update_additional_fields_for_object( $document, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $document, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a Document is created via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_create_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a document.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a document.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( is_user_logged_in() ) {
			$retval = true;

			if (
				function_exists( 'bb_document_user_can_upload' ) &&
				! bb_document_user_can_upload( bp_loggedin_user_id(), (int) $request->get_param( 'group_id' ) )
			) {
				$retval = $error;
			}
		}

		if ( true === $retval && isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			if (
				! bp_is_active( 'groups' )
				|| ! groups_can_user_manage_document( bp_loggedin_user_id(), (int) $request['group_id'] )
				|| ! function_exists( 'bp_is_group_document_support_enabled' )
				|| ( function_exists( 'bp_is_group_document_support_enabled' ) && false === bp_is_group_document_support_enabled() )
			) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to create a document inside this group.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		if ( true === $retval && isset( $request['folder_id'] ) && ! empty( $request['folder_id'] ) ) {
			$parent_folder = new BP_Document_Folder( $request['folder_id'] );
			if ( empty( $parent_folder->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_parent_folder_id',
					__( 'Invalid Parent Folder ID.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			} elseif ( ! bp_folder_user_can_edit( $parent_folder->id ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_permission',
					__( 'You don\'t have a permission to create a document inside this folder.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		if ( true === $retval && ! empty( $request['document_ids'] ) ) {
			foreach ( (array) $request['document_ids'] as $attachment_id ) {
				$attachment_id = (int) $attachment_id;
				$wp_attachment = get_post( $attachment_id );

				if ( true !== $retval ) {
					continue;
				}

				if ( empty( $wp_attachment ) || 'attachment' !== $wp_attachment->post_type ) {
					$retval = new WP_Error(
						'bp_rest_invalid_document_id',
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
						'bp_rest_invalid_document_author',
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
						function_exists( 'bp_get_attachment_document_id' ) &&
						! empty( bp_get_attachment_document_id( (int) $attachment_id ) )
					) {
					$retval = new WP_Error(
						'bp_rest_duplicate_document_upload_id',
						sprintf(
							/* translators: Attachment ID. */
							__( 'Document already exists for attachment id: %d', 'buddyboss' ),
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
		 * Filter the document `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_create_items_permissions_check', $retval, $request );
	}

	/**
	 * Update a document.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/document/:id Update Document
	 * @apiName        UpdateBBDocument
	 * @apiGroup       Document
	 * @apiDescription Update a single Document.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the document.
	 * @apiParam {Number} [folder_id] A unique numeric ID for the folder.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {string} [title] Document title.
	 * @apiParam {string} [content] Document Content.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly} [privacy] Privacy of the document.
	 */
	public function update_item( $request ) {
		$id = $request['id'];

		$documents = $this->assemble_response_data( array( 'document_ids' => array( $id ) ) );

		if ( empty( $documents['documents'] ) ) {
			return new WP_Error(
				'bp_rest_document_invalid_id',
				__( 'Invalid document ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$document = end( $documents['documents'] );

		$args = array(
			'id'            => $document->id,
			'privacy'       => $document->privacy,
			'attachment_id' => $document->attachment_id,
			'group_id'      => $document->group_id,
			'activity_id'   => $document->activity_id,
			'message_id'    => $document->message_id,
			'folder_id'     => $document->folder_id,
			'title'         => $document->title,
			'user_id'       => $document->user_id,
			'menu_order'    => $document->menu_order,
		);

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
			$args['privacy']  = 'grouponly';
		}

		if ( isset( $request['message_id'] ) && ! empty( $request['message_id'] ) ) {
			$args['message_id'] = $request['message_id'];
		}

		if ( isset( $request['folder_id'] ) && ( (int) $args['folder_id'] !== (int) $request['folder_id'] ) ) {
			$parent_folder     = new BP_Document_Folder( $request['folder_id'] );
			$args['privacy']   = ( ! empty( $parent_folder ) ? $parent_folder->privacy : $document->privacy );
			$args['group_id']  = ( ! empty( $parent_folder ) ? $parent_folder->group_id : $document->group_id );
			$moved_document_id = bp_document_move_document_to_folder( $args['id'], $request['folder_id'], $args['group_id'] );
			if ( empty( (int) $moved_document_id ) || is_wp_error( $moved_document_id ) ) {
				return new WP_Error(
					'bp_rest_invalid_move_with_folder',
					__( 'Sorry, you are not allowed to move this document with folder.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$moved_document = new BP_Document( (int) $moved_document_id );
				if ( ! empty( $moved_document ) ) {
					$args['group_id']  = $moved_document->group_id;
					$args['folder_id'] = $moved_document->folder_id;
					$args['privacy']   = $moved_document->privacy;
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
		$args = apply_filters( 'bp_rest_document_update_items_query_args', $args, $request );

		if ( ! empty( $request['title'] ) ) {
			$document_rename = bp_document_rename_file( $document->id, $document->attachment_id, $request['title'] );
			if ( ! isset( $document_rename['document_id'] ) || $document_rename['document_id'] < 1 ) {
				return new WP_Error(
					'bp_rest_document_rename',
					$document_rename,
					array(
						'status' => 404,
					)
				);
			}
		}

		if (
			empty( $document->folder_id )
			&& ( ! isset( $request['folder_id'] ) || empty( $request['folder_id'] ) )
			&& isset( $request['privacy'] )
			&& ! empty( $request['privacy'] )
		) {
			bp_document_update_privacy( $document->id, $request['privacy'], 'document' );
		}

		$id = $this->bp_rest_create_document( $args );

		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$id = ( ! empty( $id ) && is_array( $id ) ) ? current( $id ) : $id;

		if ( ! is_numeric( $id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_document',
				__( 'Cannot update existing document.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$documents = $this->assemble_response_data( array( 'document_ids' => array( $request['id'] ) ) );
		$document  = current( $documents['documents'] );

		$fields_update = $this->update_additional_fields_for_object( $document, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $document, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an document is updated via the REST API.
		 *
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_update_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a document.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to update this document.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( is_user_logged_in() ) {
			$retval = true;

			$document = new BP_Document( $request->get_param( 'id' ) );

			if ( empty( $document->id ) ) {
				$retval = new WP_Error(
					'bp_rest_document_invalid_id',
					__( 'Invalid document ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				! bp_document_user_can_edit( $document ) ||
				(
					function_exists( 'bb_document_user_can_upload' ) &&
					! bb_document_user_can_upload( bp_loggedin_user_id(), (int) ( isset( $request['group_id'] ) ? $request['group_id'] : $document->group_id ) )
				)
			) {
				$retval = $error;
			} elseif ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
				if (
					! bp_is_active( 'groups' )
					|| ! groups_can_user_manage_document( bp_loggedin_user_id(), (int) $request['group_id'] )
				) {
					$retval = new WP_Error(
						'bp_rest_invalid_permission',
						__( 'You don\'t have a permission to edit a document inside this group.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}

			if ( true === $retval && isset( $request['folder_id'] ) && ! empty( $request['folder_id'] ) ) {
				if ( ! bp_folder_user_can_edit( (int) $request['folder_id'] ) ) {
					$retval = new WP_Error(
						'bp_rest_invalid_permission',
						__( 'You don\'t have permission to move/update a document inside the folder.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the document to `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a single document.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/document/:id Delete Document
	 * @apiName        DeleteBBDocument
	 * @apiGroup       Document
	 * @apiDescription Delete a single Document.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the document.
	 */
	public function delete_item( $request ) {

		$id = $request['id'];

		$documents = $this->assemble_response_data( array( 'document_ids' => array( $id ) ) );

		if ( empty( $documents['documents'] ) ) {
			return new WP_Error(
				'bp_rest_document_invalid_id',
				__( 'Invalid document ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = '';
		foreach ( $documents['documents'] as $document ) {
			$previous = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $document, $request )
			);
		}

		if ( ! bp_document_user_can_delete( $id ) ) {
			return WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this document.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$status = bp_document_delete( array( 'id' => $id ) );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $status,
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a document is deleted via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_delete_item', $response, $request );

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
			__( 'Sorry, you need to be logged in to delete this document.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$document = new BP_Document( $request->get_param( 'id' ) );

			if ( ! empty( $document->id ) && bp_document_user_can_delete( $document ) ) {
				$retval = true;
			}
		}

		/**
		 * Filter the document `delete_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_delete_item_permissions_check', $retval, $request );
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
			$args['document_ids'] = array(
				'description'       => __( 'Document specific IDs.', 'buddyboss' ),
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

		$args['content'] = array(
			'description'       => __( 'Document Content.', 'buddyboss' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['group_id'] = array(
			'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['folder_id'] = array(
			'description'       => __( 'A unique numeric ID for the Document Folder.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['privacy'] = array(
			'description'       => __( 'Privacy of the document.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ),
			'default'           => 'public',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key        = 'edit';
			$args['id'] = array(
				'description'       => __( 'A unique numeric ID for the document.', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			unset( $args['privacy']['default'] );
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_document_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get documents.
	 *
	 * @param array|string $args All arguments and defaults are shared with BP_Document::get(),
	 *                           except for the following.
	 *
	 * @return array
	 */
	public function assemble_response_data( $args ) {

		// Fetch specific document items based on ID's.
		if ( isset( $args['document_ids'] ) && ! empty( $args['document_ids'] ) ) {
			return bp_document_get_specific( $args );

			// Fetch all activity items.
		} else {
			if ( ! empty( $args['type'] ) && 'both' !== $args['type'] ) {
				if ( 'document' === $args['type'] ) {
					return bp_document_get( $args );
				} elseif ( 'folder' === $args['type'] ) {
					return bp_folder_get( $args );
				}
			} else {
				return bp_document_get( $args );
			}
		}
	}

	/**
	 * Prepares document data for return as an object.
	 *
	 * @since 0.1.0
	 *
	 * @param BP_Document     $document Document data.
	 * @param WP_REST_Request $request  Full details about the request.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $document, $request ) {
		$data = $this->document_get_prepare_response( $document, $request );

		$data = $this->add_additional_fields_to_object( $data, $request );

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $document ) );

		/**
		 * Filter an document value returned from the API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Document      $document The Document object.
		 *
		 * @param WP_REST_Response $response The response data.
		 */
		return apply_filters( 'bp_rest_document_prepare_value', $response, $request, $document );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 0.1.0
	 *
	 * @param BP_Document $document Document data.
	 *
	 * @return array
	 */
	protected function prepare_links( $document ) {
		$base = sprintf( '/%s/%s/', $this->namespace, ( empty( $document->attachment_id ) ? 'document/folder' : $this->rest_base ) );
		$url  = $base . $document->id;

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $url ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'user'       => array(
				'href'       => rest_url( bp_rest_get_user_url( $document->user_id ) ),
				'embeddable' => true,
			),
		);

		if ( ! empty( $document->activity_id ) && bp_is_active( 'activity' ) ) {
			$activity_base     = sprintf( '/%s/%s/', $this->namespace, buddypress()->activity->id );
			$activity_url      = $activity_base . $document->activity_id;
			$links['activity'] = array(
				'href'       => rest_url( $activity_url ),
				'embeddable' => true,
			);
		}

		if ( ! empty( $document->group_id ) && bp_is_active( 'groups' ) ) {
			$group_base     = sprintf( '/%s/%s/', $this->namespace, buddypress()->groups->id );
			$group_url      = $group_base . $document->group_id;
			$links['group'] = array(
				'href'       => rest_url( $group_url ),
				'embeddable' => true,
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 0.1.0
		 *
		 * @param array       $links    The prepared links of the REST response.
		 * @param BP_Document $document Document data.
		 */
		return apply_filters( 'bp_rest_document_prepare_links', $links, $document );
	}

	/**
	 * Prepare object response for the document/folder.
	 *
	 * @param BP_Document $document Document object.
	 * @param array       $request  Request paramaters.
	 *
	 * @return array
	 */
	public function document_get_prepare_response( $document, $request ) {
		$data = array(
			'id'                    => $document->id,
			'blog_id'               => $document->blog_id,
			'attachment_id'         => ( isset( $document->attachment_id ) ? $document->attachment_id : 0 ),
			'user_id'               => $document->user_id,
			'title'                 => $document->title,
			'description'           => '',
			'type'                  => ( empty( $document->attachment_id ) ? 'folder' : 'document' ),
			'folder_id'             => $document->parent,
			'group_id'              => $document->group_id,
			'activity_id'           => ( isset( $document->activity_id ) ? $document->activity_id : 0 ),
			'message_id'            => ( isset( $document->message_id ) ? $document->message_id : 0 ),
			'hide_activity_actions' => false,
			'privacy'               => $document->privacy,
			'menu_order'            => ( isset( $document->menu_order ) ? $document->menu_order : 0 ),
			'date_created'          => $document->date_created,
			'date_modified'         => $document->date_modified,
			'group_name'            => $document->group_name,
			'group_status'          => ( bp_is_active( 'groups' ) && ! empty( $document->group_id ) ? bp_get_group_status( groups_get_group( $document->group_id ) ) : '' ),
			'visibility'            => $document->visibility,
			'count'                 => 0,
			'download_url'          => '',
			'extension'             => '',
			'extension_description' => '',
			'svg_icon'              => '',
			'filename'              => '',
			'size'                  => '',
			'msg_preview'           => '',
			'attachment_data'       => ( isset( $document->attachment_data ) ? $document->attachment_data : array() ),
			'user_nicename'         => get_the_author_meta( 'user_nicename', $document->user_id ),
			'user_login'            => get_the_author_meta( 'user_login', $document->user_id ),
			'display_name'          => bp_core_get_user_displayname( $document->user_id ),
			'user_permissions'      => $this->get_document_current_user_permissions( $document, $request ),
		);

		// Below condition will check if document has comments then like/comment button will not visible for that particular media.
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

		if ( ! empty( $document->attachment_id ) ) {
			$data['description']  = wp_specialchars_decode( get_post_field( 'post_content', $document->attachment_id ), ENT_QUOTES );
			$data['download_url'] = bp_document_download_link( $document->attachment_id, $document->id );
			$data['extension']    = bp_document_extension( $document->attachment_id );
			$data['svg_icon']     = bp_document_svg_icon( $data['extension'], $document->attachment_id, 'svg' );
			$data['filename']     = basename( get_attached_file( $document->attachment_id ) );
			$data['size']         = bp_document_size_format( filesize( get_attached_file( $document->attachment_id ) ) );

			$extension_lists = bp_document_extensions_list();
			if ( ! empty( $extension_lists ) && ! empty( $data['extension'] ) ) {
				$extension_lists = array_column( $extension_lists, 'description', 'extension' );
				$extension_name  = '.' . $data['extension'];
				if ( ! empty( $extension_lists ) && ! empty( $data['extension'] ) && array_key_exists( $extension_name, $extension_lists ) ) {
					$data['extension_description'] = esc_html( $extension_lists[ $extension_name ] );
				}
			}

			$output = '';
			ob_start();

			if ( in_array( $data['extension'], bp_get_document_preview_music_extensions(), true ) ) {
				$audio_url = bp_document_get_preview_audio_url( $document->id, $document->attachment_id, $data['extension'] );

				echo '<div class="document-audio-wrap">' .
					'<audio controls controlsList="nodownload">' .
						'<source src="' . esc_url_raw( $audio_url ) . '" type="audio/mpeg">' .
						esc_html__( 'Your browser does not support the audio element.', 'buddyboss' ) .
					'</audio>' .
				'</div>';

			}

			if ( function_exists( 'bp_document_get_preview_url' ) ) {
				$attachment_url = bp_document_get_preview_url( $document->id, $document->attachment_id );
			} else {
				$attachment_url = bp_document_get_preview_image_url( $document->id, $data['extension'], $document->attachment_id );
			}

			if ( $attachment_url ) {
				echo '<div class="document-preview-wrap">' .
					'<img src="' . esc_url_raw( $attachment_url ) . '" alt="" />' .
				'</div>';
			}
			$sizes = is_file( get_attached_file( $document->attachment_id ) ) ? get_attached_file( $document->attachment_id ) : 0;
			if ( $sizes && filesize( $sizes ) / 1e+6 < 2 ) {
				if ( in_array( $data['extension'], bp_get_document_preview_code_extensions(), true ) ) {
					$data_temp = bp_document_get_preview_text_from_attachment( $document->attachment_id );
					$file_data = $data_temp['text'];
					$more_text = $data_temp['more_text'];

					echo '<div class="document-text-wrap">' .
						'<div class="document-text" data-extension="' . esc_attr( $data['extension'] ) . '">' .
							'<textarea class="document-text-file-data-hidden" style="display: none;">' . wp_kses_post( $file_data ) . '</textarea>' .
						'</div>' .
						'<div class="document-expand">' .
							'<a href="#" class="document-expand-anchor"><i class="bb-icon-l bb-icon-plus document-icon-plus"></i> ' . esc_html__( 'Click to expand', 'buddyboss' ) . '</a>' .
						'</div>' .
					'</div>';

					if ( true === $more_text ) {
						printf(
						/* translators: %s: download string */
							'<div class="more_text_view">%s</div>',
							sprintf(
							/* translators: %s: download url */
								wp_kses_post( 'This file was truncated for preview. Please <a href="%s">download</a> to view the full file.', 'buddyboss' ),
								esc_url( $data['download_url'] )
							)
						);
					}
				}
			}

			$output .= ob_get_clean();

			$data['msg_preview'] = $output;
		} else {
			$child_doc            = count( bp_document_get_folder_document_ids( $document->id ) );
			$child_folder         = count( $this->bp_document_get_folder_children_ids( $document->id ) );
			$data['count']        = (int) $child_doc + (int) $child_folder;
			$data['svg_icon']     = bp_document_svg_icon( 'folder', '', 'svg' );
			$data['download_url'] = bp_document_folder_download_link( $document->id );
		}

		return $data;
	}

	/**
	 * Get the document schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_document',
			'type'       => 'object',
			'properties' => array(
				'id'                    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Document.', 'buddyboss' ),
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
					'description' => __( 'Unique identifier for the document object.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_id'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The ID for the author of the document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'title'                 => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Document title.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'description'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The Document description.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'type'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether it is a document or folder.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'folder_id'             => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the parent Folder.', 'buddyboss' ),
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
					'description' => __( 'Based on this hide like/comment button for document activity comments.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'boolean',
				),
				'privacy'               => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Privacy of the document.', 'buddyboss' ),
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
					'description' => __( 'The date the document was created, in the site\'s timezone.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'date_modified'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The date the document was modified, in the site\'s timezone.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
					'format'      => 'date-time',
				),
				'group_name'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group name associate with the document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'group_status'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Group status associate with the document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'visibility'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Visibility of the document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'count'                 => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Count of the child documents and folders of the folder.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'download_url'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Download URL for the document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'extension'             => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Document file extension.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'extension_description' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Document file description.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'svg_icon'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Document Icon based on the extension.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'filename'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Full name of the document file with extension.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'size'                  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Size of the uploaded document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'msg_preview'           => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Message preview for the document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'attachment_data'       => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Wordpress Document Data.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
					'properties'  => array(
						'full'           => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Document URL with full image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'thumb'          => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Document URL with thumbnail image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'activity_thumb' => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Document URL for the activity image size.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'string',
						),
						'meta'           => array(
							'context'     => array( 'embed', 'view', 'edit' ),
							'description' => __( 'Meta items for the document.', 'buddyboss' ),
							'readonly'    => true,
							'type'        => 'object',
						),
					),
				),
				'user_nicename'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s nice name to create a document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'user_login'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s login name to create a document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'display_name'          => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'The User\'s display name to create a document.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
			),
		);

		/**
		 * Filters the document schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_document_schema', $this->add_additional_fields_schema( $schema ) );
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
			'default'           => 'asc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order documents by which attribute.', 'buddyboss' ),
			'default'           => 'title',
			'type'              => 'string',
			'enum'              => array( 'title', 'date_created', 'date_modified', 'group_id', 'privacy', 'id', 'include' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['max'] = array(
			'description'       => __( 'Maximum number of results to return', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['scope'] = array(
			'description'       => __( 'Scope of the Document.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array( 'public', 'friends', 'groups', 'personal' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit results to a specific user.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['folder_id'] = array(
			'description'       => __( 'A unique numeric ID for the Folder.', 'buddyboss' ),
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
			'description'       => __( 'A unique numeric ID for the Document\'s Activity.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['privacy'] = array(
			'description'       => __( 'Privacy of the Document.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Ensure result set includes specific document IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific document IDs.', 'buddyboss' ),
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

		$params['type'] = array(
			'description' => __( 'Type of document.', 'buddyboss' ),
			'default'     => 'both',
			'type'        => 'string',
			'enum'        => array( 'both', 'document', 'folder' ),
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_document_collection_params', $params );
	}

	/**
	 * Get document permissions based on current user.
	 *
	 * @param BP_Document $document The Document object.
	 * @param array       $request  Request parameter as array.
	 *
	 * @return array
	 */
	protected function get_document_current_user_permissions( $document, $request ) {
		$retval = array(
			'download'           => 0,
			'copy_download_link' => 0,
			'edit_privacy'       => 0,
			'edit_post_privacy'  => 0,
			'edit_description'   => 0,
			'rename'             => 0,
			'move'               => 0,
			'delete'             => 0,
		);

		$document_privacy = array();

		if ( ! empty( $document->attachment_id ) ) {
			$document_privacy = bb_media_user_can_access( $document->id, 'document' );
		} else {
			$document_privacy = bb_media_user_can_access( $document->id, 'folder' );
		}

		if ( ! empty( $document_privacy ) ) {
			if ( isset( $document_privacy['can_download'] ) && true === (bool) $document_privacy['can_download'] ) {
				$retval['download']           = 1;
				$retval['copy_download_link'] = 1;
			}

			if ( isset( $document_privacy['can_edit'] ) && true === (bool) $document_privacy['can_edit'] ) {
				$retval['rename']           = 1;
				$retval['edit_description'] = 1;

				if ( 0 === (int) $document->group_id && 0 === (int) $document->parent ) {
					if ( ! empty( $document->attachment_id ) && bp_is_active( 'activity' ) ) {
						$parent_activity_id = get_post_meta( $document->attachment_id, 'bp_document_parent_activity_id', true );
						if ( ! empty( $parent_activity_id ) ) {
							$retval['edit_post_privacy'] = $parent_activity_id;
						} else {
							$retval['edit_post_privacy'] = $document->activity_id;
						}

						$activity = new BP_Activity_Activity( (int) $retval['edit_post_privacy'] );
						if ( ! empty( $activity->id ) && ! empty( $activity->type ) && 'activity_comment' === $activity->type ) {
							$retval['edit_post_privacy'] = 0;
						}
					} else {
						$retval['edit_privacy'] = 1;
					}
				}
			}

			if ( isset( $document_privacy['can_delete'] ) && true === (bool) $document_privacy['can_delete'] ) {
				$retval['delete'] = 1;
			}

			if ( isset( $document_privacy['can_move'] ) && true === (bool) $document_privacy['can_move'] ) {
				$retval['move'] = 1;
			}
		}

		if (
			isset( $request['support'] )
			&& (
				'activity' === $request['support']
				|| 'forums' === $request['support']
				|| 'message' === $request['support']
			)
		) {
			unset( $retval['rename'] );

			if (
				'activity' === $request['support']
				|| 'message' === $request['support']
				|| 'forums' === $request['support']
			) {
				unset( $retval['edit_privacy'] );
				unset( $retval['edit_post_privacy'] );
			}

			if ( 'message' === $request['support'] ) {
				unset( $retval['move'] );
				unset( $retval['delete'] );
			}

			if ( 'forums' === $request['support'] ) {
				unset( $retval['move'] );
			}
		}

		return $retval;
	}

	/**
	 * Create the Document IDs from Upload IDs.
	 *
	 * @param array $args Key value array of query var to query value.
	 *
	 * @return array|WP_Error
	 * @since 0.1.0
	 */
	public function bp_rest_create_document( $args ) {

		$document_privacy    = ( ! empty( $args['privacy'] ) ? $args['privacy'] : 'public' );
		$document_upload_ids = ( ! empty( $args['document_ids'] ) ? $args['document_ids'] : '' );
		$activity_id         = ( ! empty( $args['activity_id'] ) ? $args['activity_id'] : false );
		$content             = ( isset( $args['content'] ) ? $args['content'] : false );
		$user_id             = ( ! empty( $args['user_id'] ) ? $args['user_id'] : get_current_user_id() );
		$id                  = ( ! empty( $args['id'] ) ? $args['id'] : '' );
		$message_id          = ( ! empty( $args['message_id'] ) ? $args['message_id'] : 0 );

		$group_id  = ( ! empty( $args['group_id'] ) ? $args['group_id'] : false );
		$folder_id = ( ! empty( $args['folder_id'] ) ? $args['folder_id'] : false );

		// Override the privacy if Folder ID is given.
		if ( ! empty( $folder_id ) ) {
			$folders = bp_folder_get_specific( array( 'folder_ids' => array( $folder_id ) ) );
			if ( ! empty( $folders['folders'] ) ) {
				$folder           = array_pop( $folders['folders'] );
				$document_privacy = $folder->privacy;
				$group_id         = $folder->group_id;
			}
		}

		// Update Document.
		if ( ! empty( $id ) ) {

			$wp_attachment_id  = $args['attachment_id'];
			$wp_attachment_url = wp_get_attachment_url( $wp_attachment_id );

			// when the file found to be empty it's means it's not a valid attachment.
			if ( empty( $wp_attachment_url ) ) {
				return;
			}

			$document_activity_id = $activity_id;

			$parent_activity = get_post_meta( $wp_attachment_id, 'bp_document_parent_activity_id', true );
			if ( ! empty( $parent_activity ) && bp_is_active( 'activity' ) ) {
				$activity_id   = $parent_activity;
				$all_documents = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
				$all_documents = explode( ',', $all_documents );
				$key           = array_search( $id, $all_documents, true );
				if ( false !== $key ) {
					unset( $all_documents[ $key ] );
				}
			}

			// extract the nice title name.
			$title = get_the_title( $wp_attachment_id );

			$add_document_args = array(
				'id'            => $id,
				'attachment_id' => $wp_attachment_id,
				'title'         => $title,
				'activity_id'   => $document_activity_id,
				'message_id'    => $message_id,
				'folder_id'     => ( ! empty( $args['folder_id'] ) ? $args['folder_id'] : false ),
				'group_id'      => ( ! empty( $args['group_id'] ) ? $args['group_id'] : false ),
				'privacy'       => $document_privacy,
				'user_id'       => $user_id,
				'error_type'    => 'wp_error',
			);

			if ( isset( $args['menu_order'] ) ) {
				$add_document_args['menu_order'] = ( ! empty( $args['menu_order'] ) ? $args['menu_order'] : 0 );
			}

			$document_id = bp_document_add( $add_document_args );

			if ( is_int( $document_id ) ) {

				// save document is saved in attachment.
				update_post_meta( $wp_attachment_id, 'bp_document_saved', true );

				// save document meta for activity.
				if ( ! empty( $document_activity_id ) ) {
					update_post_meta( $wp_attachment_id, 'bp_document_activity_id', $document_activity_id );
				}

				// save document description while update.
				if ( false !== $content ) {
					$document_post['ID']           = $wp_attachment_id;
					$document_post['post_content'] = wp_filter_nohtml_kses( $content );
					wp_update_post( $document_post );
				}

				$created_document_ids[] = $document_id;

			}

			if ( ! empty( $all_documents ) ) {
				foreach ( $all_documents as $d_id ) {
					$document = new BP_Document( $d_id );
					if ( ! empty( $document->id ) ) {
						$created_document_ids[] = $document->id;
						$document->privacy      = $document_privacy;
						$document->save();
					}
				}
			}
		}

		// created Documents.
		if ( ! empty( $document_upload_ids ) ) {
			$valid_upload_ids = array();

			foreach ( $document_upload_ids as $wp_attachment_id ) {
				$wp_attachment_url = wp_get_attachment_url( $wp_attachment_id );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$valid_upload_ids[] = $wp_attachment_id;
			}

			$documents = array();
			if ( ! empty( $valid_upload_ids ) ) {
				foreach ( $valid_upload_ids as $wp_attachment_id ) {

					// Check if document id already available for the messages.
					if ( 'message' === $document_privacy ) {
						$mid = get_post_meta( $wp_attachment_id, 'bp_document_id', true );

						if ( ! empty( $mid ) ) {
							$created_document_ids[] = $mid;
							continue;
						}
					}
					// extract the nice title name.
					$title = get_the_title( $wp_attachment_id );

					$documents[] = array(
						'id'      => $wp_attachment_id,
						'name'    => $title,
						'privacy' => $document_privacy,
					);
				}
			}

			if ( ! empty( $documents ) ) {
				$created_document_ids = bp_document_add_handler( $documents, $document_privacy, $content, $group_id, $folder_id );
			}
		}

		if ( empty( $created_document_ids ) ) {
			return new WP_Error(
				'bp_rest_document_creation_error',
				__( 'Error creating document, please try again.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		// Link all uploaded video to main activity.
		if ( ! empty( $activity_id ) ) {
			$main_activity = new BP_Activity_Activity( $activity_id );
			if ( ! empty( $main_activity->id ) && 'document' !== $main_activity->privacy ) {
				$created_document_ids_joined = implode( ',', $created_document_ids );
				bp_activity_update_meta( $activity_id, 'bp_document_ids', $created_document_ids_joined );

				if ( empty( $group_id ) ) {
					$main_activity->privacy = $document_privacy;
					$main_activity->save();
				}
			}
		}

		return $created_document_ids;
	}

	/**
	 * Get default scope for the document.
	 * - from bp_document_default_scope();
	 *
	 * @param string $scope Default scope.
	 * @param array  $args  Array of document argument.
	 *
	 * @return string
	 */
	public function bp_rest_document_default_scope( $scope, $args ) {
		$new_scope = array();

		if ( is_array( $scope ) ) {
			$scope = array_filter( $scope );
		}

		if ( ( 'all' === $scope || empty( $scope ) ) && ( empty( $args['group_id'] ) && empty( $args['user_id'] ) ) ) {
			$new_scope[] = 'public';

			if ( is_user_logged_in() ) {
				$new_scope[] = 'personal';

				if ( bp_is_active( 'friends' ) ) {
					$new_scope[] = 'friends';
				}
			}

			if ( bp_is_active( 'groups' ) ) {
				$new_scope[] = 'groups';
			}
		} elseif ( ( 'all' === $scope || empty( $scope ) ) && ! empty( $args['group_id'] ) ) {
			$new_scope = array( 'groups' );
		}

		$new_scope = array_unique( $new_scope );

		if ( empty( $new_scope ) ) {
			$new_scope = (array) $scope;
		}

		/**
		 * Filter to update default scope.
		 */
		$new_scope = apply_filters( 'bp_rest_document_default_scope', $new_scope );

		return implode( ',', $new_scope );
	}

	/**
	 * Check user access based on the privacy for the single document.
	 *
	 * @param BP_Document $document Document object.
	 *
	 * @return bool
	 */
	protected function bp_rest_check_privacy_restriction( $document ) {
		return (
					'onlyme' === $document->privacy
					&& bp_loggedin_user_id() !== $document->user_id
				)
				|| (
					'loggedin' === $document->privacy
					&& empty( bp_loggedin_user_id() )
				)
				|| (
					bp_is_active( 'groups' )
					&& 'grouponly' === $document->privacy
					&& ! empty( $document->group_id )
					&& 'public' !== bp_get_group_status( groups_get_group( $document->group_id ) )
					&& empty( groups_is_user_admin( bp_loggedin_user_id(), $document->group_id ) )
					&& empty( groups_is_user_mod( bp_loggedin_user_id(), $document->group_id ) )
					&& empty( groups_is_user_member( bp_loggedin_user_id(), $document->group_id ) )
				)
				|| (
					bp_is_active( 'friends' )
					&& 'friends' === $document->privacy
					&& ! empty( $document->user_id )
					&& bp_loggedin_user_id() !== $document->user_id
					&& 'is_friend' !== friends_check_friendship_status( $document->user_id, bp_loggedin_user_id() )
				);
	}

	/**
	 * Added document support for activity, forum and messages.
	 */
	public function bp_rest_document_support() {

		// Activity Document.
		bp_rest_register_field(
			'activity',      // Id of the BuddyPress component the REST field is about.
			'bp_documents', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_documents_get_rest_field_callback' ),
				// The function to use to get the value of the REST Field.
				'update_callback' => array( $this, 'bp_documents_update_rest_field_callback' ),
				// The function to use to update the value of the REST Field.
				'schema'          => array(                                // The example_field REST schema.
					'description' => 'Activity Documents.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Activity Comment Document.
		register_rest_field(
			'activity_comments',      // Id of the BuddyPress component the REST field is about.
			'bp_documents', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_documents_get_rest_field_callback' ),    // The function to use to get the value of the REST Field.
				'update_callback' => array( $this, 'bp_documents_update_rest_field_callback' ), // The function to use to update the value of the REST Field.
				'schema'          => array(                                // The example_field REST schema.
					'description' => 'Activity Documents.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to main activity to check the comment has access to upload document or not.
		bp_rest_register_field(
			'activity',
			'comment_upload_document',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_document' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload media or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Added param to comment activity to check the child comment has access to upload document or not.
		register_rest_field(
			'activity_comments',
			'comment_upload_document',
			array(
				'get_callback' => array( $this, 'bp_rest_user_can_comment_upload_document' ),
				'schema'       => array(
					'description' => 'Whether to check user can upload media or not.',
					'type'        => 'boolean',
					'readonly'    => true,
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		add_filter( 'bp_rest_activity_create_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_activity_update_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_activity_comment_create_item_query_arguments', array( $this, 'bp_rest_activity_query_arguments' ), 99, 3 );

		// Messages Document Support.
		bp_rest_register_field(
			'messages',      // Id of the BuddyPress component the REST field is about.
			'bp_documents', // Used into the REST response/request.
			array(
				'get_callback'    => array( $this, 'bp_documents_get_rest_field_callback_messages' ),
				// The function to use to get the value of the REST Field.
				'update_callback' => array( $this, 'bp_documents_update_rest_field_callback_messages' ),
				// The function to use to update the value of the REST Field.
				'schema'          => array(                                // The example_field REST schema.
					'description' => 'Messages Documents.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		add_filter( 'bp_rest_messages_group_collection_params', array( $this, 'bp_rest_message_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_messages_create_item_query_arguments', array( $this, 'bp_rest_message_query_arguments' ), 99, 3 );
		add_filter( 'bp_rest_messages_update_item_query_arguments', array( $this, 'bp_rest_message_query_arguments' ), 99, 3 );

		// Topic Document Support.
		register_rest_field(
			'topics',
			'bbp_documents',
			array(
				'get_callback'    => array( $this, 'bbp_document_get_rest_field_callback' ),
				'update_callback' => array( $this, 'bbp_document_update_rest_field_callback' ),
				'schema'          => array(
					'description' => 'Topic Documentss.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		// Reply Document Support.
		register_rest_field(
			'reply',
			'bbp_documents',
			array(
				'get_callback'    => array( $this, 'bbp_document_get_rest_field_callback' ),
				'update_callback' => array( $this, 'bbp_document_update_rest_field_callback' ),
				'schema'          => array(
					'description' => 'Reply Documents.',
					'type'        => 'object',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		add_filter( 'bp_rest_topic_create_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );
		add_filter( 'bp_rest_topic_update_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );
		add_filter( 'bp_rest_reply_create_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );
		add_filter( 'bp_rest_reply_update_item_query_arguments', array( $this, 'bp_rest_forums_collection_params' ), 99, 3 );
	}

	/**
	 * The function to use to get documents of the activity REST Field.
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_documents_get_rest_field_callback( $activity, $attribute ) {
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
				! bp_is_profile_document_support_enabled()
			) ||
			(
				bp_is_active( 'groups' ) &&
				! empty( $group_id ) &&
				! bp_is_group_document_support_enabled()
			)
		) {
			return false;
		}

		$document_ids = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
		$document_id  = bp_activity_get_meta( $activity_id, 'bp_document_id', true );
		$document_ids = trim( $document_ids );
		$document_ids = explode( ',', $document_ids );

		if ( ! empty( $document_id ) ) {
			$document_ids[] = $document_id;
			$document_ids   = array_filter( array_unique( $document_ids ) );
		}

		if ( empty( $document_ids ) ) {
			return;
		}

		$documents = $this->assemble_response_data(
			array(
				'document_ids' => $document_ids,
				'sort'         => 'ASC',
				'order_by'     => 'menu_order',
			)
		);

		if ( empty( $documents['documents'] ) ) {
			return;
		}

		$retval = array();
		$object = new WP_REST_Request();
		$object->set_param( 'support', 'activity' );
		$object->set_param( 'context', 'view' );

		foreach ( $documents['documents'] as $document ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response(
					$document,
					$object
				)
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the document's value of the activity REST Field.
	 * - from bp_document_update_activity_document_meta();
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Activity_Activity object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_documents_update_rest_field_callback( $object, $value, $attribute ) {

		global $bp_activity_edit, $bp_document_upload_count, $bp_new_activity_comment, $bp_activity_post_update_id, $bp_activity_post_update;

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
			'bp_documents' !== $attribute ||
			(
				function_exists( 'bb_document_user_can_upload' ) &&
				! bb_document_user_can_upload( bp_loggedin_user_id(), (int) $group_id )
			)
		) {
			$value->bp_documents = null;

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

		$documents = wp_parse_id_list( $object );

		$old_document_ids      = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
		$old_document_ids      = ( ! empty( $old_document_ids ) ? explode( ',', $old_document_ids ) : array() );
		$new_documents         = array();
		$old_documents         = array();
		$old_documents_objects = array();

		if ( ! empty( $old_document_ids ) ) {
			foreach ( $old_document_ids as $id ) {
				$document_object = new BP_Document( $id );
				$old_documents_objects[ $document_object->attachment_id ] = $document_object;
				$old_documents[ $id ]                                     = $document_object->attachment_id;
			}
		}

		$bp_activity_post_update    = true;
		$bp_activity_post_update_id = $activity_id;

		if ( ! empty( $value->component ) && 'groups' === $value->component ) {
			$group_id = $value->item_id;
			$privacy  = 'grouponly';
		}

		if ( ! isset( $documents ) || empty( $documents ) ) {

			// delete document ids and meta for activity if empty document in request.
			// delete media ids and meta for activity if empty media in request.
			if ( ! empty( $activity_id ) && ! empty( $old_document_ids ) ) {
				foreach ( $old_document_ids as $document_id ) {
					bp_document_delete( array( 'id' => $document_id ), 'activity' );
				}
				bp_activity_delete_meta( $activity_id, 'bp_document_ids' );
			}

			return $value;
		} else {

			$order_count = 0;
			foreach ( $documents as $id ) {

				$wp_attachment_url = wp_get_attachment_url( $id );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$order_count ++;

				if ( in_array( $id, $old_documents, true ) ) {
					$new_documents[] = array(
						'document_id' => $old_documents_objects[ $id ]->id,
					);
				} else {
					$new_documents[] = array(
						'id'         => $id,
						'name'       => get_the_title( $id ),
						'folder_id'  => 0,
						'group_id'   => $group_id,
						'menu_order' => $order_count,
						'privacy'    => $privacy,
						'error_type' => 'wp_error',
					);
				}
			}
		}

		$bp_document_upload_count = count( $new_documents );

		remove_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
		remove_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
		remove_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
		remove_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );

		$document_ids = bp_document_add_handler( $new_documents, $privacy, '', $group_id );

		add_action( 'bp_activity_posted_update', 'bp_document_update_activity_document_meta', 10, 3 );
		add_action( 'bp_groups_posted_update', 'bp_document_groups_activity_update_document_meta', 10, 4 );
		add_action( 'bp_activity_comment_posted', 'bp_document_activity_comments_update_document_meta', 10, 3 );
		add_action( 'bp_activity_comment_posted_notification_skipped', 'bp_document_activity_comments_update_document_meta', 10, 3 );

		// save document meta for activity.
		if ( ! empty( $activity_id ) ) {
			// Delete document if not exists in current document ids.

			if ( true === $bp_activity_edit ) {
				if ( ! empty( $old_document_ids ) ) {
					foreach ( $old_document_ids as $document_id ) {
						if ( ! in_array( (int) $document_id, $document_ids, true ) ) {
							bp_document_delete( array( 'id' => $document_id ), 'activity' );
						}
					}
				}
			}
			bp_activity_update_meta( $activity_id, 'bp_document_ids', implode( ',', $document_ids ) );
		}
	}

	/**
	 * The function to use to set `comment_upload_document`
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_rest_user_can_comment_upload_document( $activity, $attribute ) {
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

		if ( function_exists( 'bb_user_has_access_upload_document' ) ) {
			return bb_user_has_access_upload_document( $group_id, $user_id, 0, 0, 'profile' );
		}

		return false;
	}

	/**
	 * The function to use to get documents of the messages REST Field.
	 *
	 * @param array  $data      The message value for the REST response.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bp_documents_get_rest_field_callback_messages( $data, $attribute ) {
		$message_id = $data['id'];

		if ( empty( $message_id ) ) {
			return;
		}

		$thread_id       = ( isset( $data['thread_id'] ) ? $data['thread_id'] : 0 );
		$is_group_thread = false;

		if ( empty( $thread_id ) ) {
			return;
		}

		if ( function_exists( 'bb_messages_is_group_thread' ) ) {
			$is_group_thread = bb_messages_is_group_thread( $thread_id );
		} else {
			$first_message           = BP_Messages_Thread::get_first_message( $thread_id );
			$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
			$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
			$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
			$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

			if ( 'group' === $message_from && $thread_id === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
				$is_group_thread = true;
			}
		}

		if (
			(
				(
					empty( $is_group_thread ) ||
					(
						! empty( $is_group_thread ) &&
						! bp_is_active( 'groups' )
					)
				) &&
				! bp_is_messages_document_support_enabled()
			) ||
			(
				bp_is_active( 'groups' ) &&
				! empty( $is_group_thread ) &&
				! bp_is_group_document_support_enabled()
			)
		) {
			return;
		}

		$document_ids = bp_messages_get_meta( $message_id, 'bp_document_ids', true );
		$document_id  = bp_messages_get_meta( $message_id, 'bp_document_id', true );
		$document_ids = trim( $document_ids );
		$document_ids = explode( ',', $document_ids );

		if ( ! empty( $document_id ) ) {
			$document_ids[] = $document_id;
			$document_ids   = array_filter( array_unique( $document_ids ) );
		}

		if ( empty( $document_ids ) ) {
			return;
		}

		$documents = $this->assemble_response_data(
			array(
				'document_ids'     => $document_ids,
				'sort'             => 'ASC',
				'order_by'         => 'menu_order',
				'moderation_query' => false,
			)
		);

		if ( empty( $documents['documents'] ) ) {
			return;
		}

		$retval = array();
		$object = new WP_REST_Request();
		$object->set_param( 'support', 'message' );

		foreach ( $documents['documents'] as $document ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $document, $object )
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the documents value of the messages REST Field.
	 *
	 * @param object $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case the BP_Messages_Message object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return object
	 */
	protected function bp_documents_update_rest_field_callback_messages( $object, $value, $attribute ) {

		if ( 'bp_documents' !== $attribute || empty( $object ) ) {
			$value->bp_documents = null;
			return $value;
		}

		$message_id = $value->id;

		$thread_id = $value->thread_id;

		$documents = wp_parse_id_list( $object );

		if ( empty( $documents ) ) {
			$value->bp_documents = null;
			return $value;
		}

		$thread_id = $value->thread_id;

		if ( function_exists( 'bb_user_has_access_upload_document' ) ) {
			$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), 0, $thread_id, 'message' );
			if ( ! $can_send_document ) {
				$value->bp_documents = null;

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

		if ( empty( apply_filters( 'bp_user_can_create_message_document', bp_is_messages_document_support_enabled(), $thread, bp_loggedin_user_id() ) ) ) {
			$value->bp_media_ids = null;

			return $value;
		}

		$args = array(
			'document_ids' => $documents,
			'privacy'      => 'message',
		);

		remove_action( 'bp_document_add', 'bp_activity_document_add', 9 );
		remove_filter( 'bp_document_add_handler', 'bp_activity_create_parent_document_activity', 9 );

		$document_ids = $this->bp_rest_create_document( $args );

		add_action( 'bp_document_add', 'bp_activity_document_add', 9 );
		add_filter( 'bp_document_add_handler', 'bp_activity_create_parent_document_activity', 9 );

		if ( is_wp_error( $document_ids ) ) {
			$value->bp_documents = $document_ids;
			return $value;
		}

		if ( ! empty( $document_ids ) ) {
			foreach ( $document_ids as $id ) {
				bp_document_add_meta( $id, 'thread_id', $thread_id );
			}
		}

		bp_messages_update_meta( $message_id, 'bp_document_ids', implode( ',', $document_ids ) );
	}

	/**
	 * The function to use to get documents of the topic/reply REST Field.
	 *
	 * @param array  $post      WP_Post object as array.
	 * @param string $attribute The REST Field key used into the REST response.
	 *
	 * @return string            The value of the REST Field to include into the REST response.
	 */
	protected function bbp_document_get_rest_field_callback( $post, $attribute ) {

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
				! bp_is_forums_document_support_enabled()
			) ||
			(
				bp_is_active( 'groups' ) &&
				! empty( $group_id ) &&
				! bp_is_group_document_support_enabled()
			)
		) {
			return;
		}

		$document_ids = get_post_meta( $p_id, 'bp_document_ids', true );
		$document_id  = get_post_meta( $p_id, 'bp_document_id', true );
		$document_ids = trim( $document_ids );
		$document_ids = explode( ',', $document_ids );

		if ( ! empty( $document_id ) ) {
			$document_ids[] = $document_id;
			$document_ids   = array_filter( array_unique( $document_ids ) );
		}

		if ( empty( $document_ids ) ) {
			return;
		}

		$documents = $this->assemble_response_data(
			array(
				'document_ids' => $document_ids,
				'sort'         => 'ASC',
				'order_by'     => 'menu_order',
			)
		);

		if ( empty( $documents['documents'] ) ) {
			return;
		}

		$retval = array();
		$object = new WP_REST_Request();
		$object->set_param( 'support', 'forums' );

		foreach ( $documents['documents'] as $document ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $document, $object )
			);
		}

		return $retval;
	}

	/**
	 * The function to use to update the document's value of the topic REST Field.
	 * - from bp_document_forums_new_post_document_save();
	 *
	 * @param object $object     Value for the schema.
	 * @param object $value      The value of the REST Field to save.
	 *
	 * @return object
	 */
	protected function bbp_document_update_rest_field_callback( $object, $value ) {

		$documents = wp_parse_id_list( $object );
		if ( empty( $documents ) ) {
			$value->bbp_documents = null;

			return $value;
		}

		$post_id = $value->ID;

		$reply_id = 0;
		$topic_id = 0;
		$forum_id = 0;

		// Get current forum ID.
		if ( bbp_get_reply_post_type() === get_post_type( $post_id ) ) {
			$forum_id = bbp_get_reply_forum_id( $post_id );
		} else {
			$forum_id = bbp_get_topic_forum_id( $post_id );
		}

		if ( function_exists( 'bb_user_has_access_upload_document' ) ) {
			$can_send_document = bb_user_has_access_upload_document( 0, bp_loggedin_user_id(), $forum_id, 0, 'forum' );
			if ( ! $can_send_document ) {
				$value->bbp_documents = null;

				return $value;
			}
		}

		$group_ids = bbp_get_forum_group_ids( $forum_id );
		$group_id  = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );

		// save activity id if it is saved in forums and enabled in platform settings.
		$main_activity_id = get_post_meta( $post_id, '_bbp_activity_id', true );

		// fetch currently uploaded document ids.
		$existing_document_ids            = get_post_meta( $post_id, 'bp_document_ids', true );
		$existing_document_attachment_ids = array();
		$existing_document_attachments    = array();

		if ( ! empty( $existing_document_ids ) ) {
			$existing_document_ids = explode( ',', $existing_document_ids );

			foreach ( $existing_document_ids as $existing_document_id ) {
				$existing_document_object = new BP_Document( $existing_document_id );

				if ( ! empty( $existing_document_object->attachment_id ) ) {
					$existing_document_attachment_ids[]                     = $existing_document_object->attachment_id;
					$existing_document_attachments[ $existing_document_id ] = $existing_document_object->attachment_id;
				}
			}
		}

		$document_ids     = array();
		$menu_order_count = 0;

		if ( ! empty( $documents ) ) {
			foreach ( $documents as $document ) {

				$wp_attachment_url = wp_get_attachment_url( $document );

				// when the file found to be empty it's means it's not a valid attachment.
				if ( empty( $wp_attachment_url ) ) {
					continue;
				}

				$menu_order_count ++;

				$attachment_id = ! empty( $document ) ? $document : 0;
				$menu_order    = $menu_order_count;

				if ( ! empty( $existing_document_attachment_ids ) ) {
					$index = array_search( $attachment_id, $existing_document_attachment_ids, true );
					if ( ! empty( $attachment_id ) && false !== $index ) {
						$exisiting_document_id                = array_search( $attachment_id, $existing_document_attachments, true );
						$existing_document_update             = new BP_Document( $exisiting_document_id );
						$existing_document_update->group_id   = $group_id;
						$existing_document_update->privacy    = 'forums';
						$existing_document_update->menu_order = $menu_order;
						$existing_document_update->save();

						unset( $existing_document_ids[ $index ] );
						$document_ids[] = $exisiting_document_id;
						continue;
					}
				}

				if ( 0 === $reply_id && bbp_get_reply_post_type() === get_post_type( $post_id ) ) {
					$reply_id = $post_id;
					$topic_id = bbp_get_reply_topic_id( $reply_id );
					$forum_id = bbp_get_topic_forum_id( $topic_id );
				} elseif ( 0 === $topic_id && bbp_get_topic_post_type() === get_post_type( $post_id ) ) {
					$topic_id = $post_id;
					$forum_id = bbp_get_topic_forum_id( $topic_id );
				} elseif ( 0 === $forum_id && bbp_get_forum_post_type() === get_post_type( $post_id ) ) {
					$forum_id = $post_id;
				}

				// extract the nice title name.
				$title     = get_the_title( $attachment_id );
				$file      = get_attached_file( $attachment_id );
				$file_type = wp_check_filetype( $file );
				$file_name = basename( $file );

				$document_id = bp_document_add(
					array(
						'attachment_id' => $attachment_id,
						'title'         => $title,
						'folder_id'     => 0,
						'group_id'      => $group_id,
						'privacy'       => 'forums',
						'error_type'    => 'wp_error',
						'menu_order'    => $menu_order,
					)
				);

				if ( ! is_wp_error( $document_id ) && ! empty( $document_id ) ) {
					$document_ids[] = $document_id;

					// save document meta.
					bp_document_update_meta( $document_id, 'forum_id', $forum_id );
					bp_document_update_meta( $document_id, 'topic_id', $topic_id );
					bp_document_update_meta( $document_id, 'reply_id', $reply_id );
					bp_document_update_meta( $document_id, 'file_name', $file_name );
					bp_document_update_meta( $document_id, 'extension', '.' . $file_type['ext'] );

					// save document is saved in attachment.
					update_post_meta( $attachment_id, 'bp_document_saved', true );
				}
			}
		}

		$document_ids = implode( ',', $document_ids );

		// Save all attachment ids in forums post meta.
		update_post_meta( $post_id, 'bp_document_ids', $document_ids );

		// save document meta for activity.
		if ( ! empty( $main_activity_id ) && bp_is_active( 'activity' ) ) {
			bp_activity_update_meta( $main_activity_id, 'bp_document_ids', $document_ids );
		}

		// delete documents which were not saved or removed from form.
		if ( ! empty( $existing_document_ids ) ) {
			foreach ( $existing_document_ids as $document_id ) {
				bp_document_delete( array( 'id' => $document_id ) );
			}
		}

	}

	/**
	 * Filter Query argument for the activity for document support.
	 *
	 * @param array  $args   Query arguments.
	 * @param string $method HTTP method of the request.
	 *
	 * @return array
	 */
	public function bp_rest_activity_query_arguments( $args, $method ) {

		$args['bp_documents'] = array(
			'description'       => __( 'Document specific IDs.', 'buddyboss' ),
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

		$params['bbp_documents'] = array(
			'description'       => __( 'Document specific IDs.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Filter Query argument for the Messages for document support.
	 *
	 * @param array $params Query arguments.
	 *
	 * @return array
	 */
	public function bp_rest_message_query_arguments( $params ) {

		$params['bp_documents'] = array(
			'description'       => __( 'Document specific IDs.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Return all folder id of the folder.
	 *
	 * @param int $folder_id Folder ID.
	 *
	 * @return array
	 */
	public function bp_document_get_folder_children_ids( $folder_id ) {
		global $wpdb, $bp;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$bp->document->table_name_folder} WHERE parent = %d", $folder_id ) ) );
	}
}
