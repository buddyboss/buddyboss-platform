<?php
/**
 * BP REST: BP_REST_Document_Folder_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Document Folder endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Document_Folder_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Document_Endpoint Instance.
	 *
	 * @var BP_REST_Document_Endpoint
	 */
	protected $document_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace         = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base         = 'document/folder';
		$this->document_endpoint = new BP_REST_Document_Endpoint();
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
						'description' => __( 'A unique numeric ID for the folder.', 'buddyboss' ),
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
			'/' . $this->rest_base . '/tree',
			array(
				'args' => array(
					'group_id'     => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
						'type'        => 'integer',
					),
					'hierarchical' => array(
						'description' => __( 'Whether to retrieve as a hierarchical or not.', 'buddyboss' ),
						'type'        => 'boolean',
						'default'     => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'folder_tree_items' ),
					'permission_callback' => array( $this, 'folder_tree_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Retrieve document folders.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/document/folder Get Folders
	 * @apiName        GetBBFolders
	 * @apiGroup       Document
	 * @apiDescription Retrieve Folders.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} [page] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String} [search] Limit results to those matching a string.
	 * @apiParam {String=asc,desc} [order=desc] Order sort attribute ascending or descending.
	 * @apiParam {String=id,title,date_created,user_id,group_id,privacy} [orderby=date_created] Order by a specific parameter.
	 * @apiParam {Number} [max] Maximum number of results to return.
	 * @apiParam {Number} [user_id] Limit result set to items created by a specific user (ID).
	 * @apiParam {Number} [parent] A unique numeric ID for the Folder.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Array=public,loggedin,friends,onlyme,grouponly} [privacy=public] Privacy of the Folder.
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
		);

		if ( ! empty( $request['search'] ) ) {
			$args['search_terms'] = $request['search'];
		}

		if ( ! empty( $request['max'] ) ) {
			$args['max'] = $request['max'];
		}

		if ( ! empty( $request['user_id'] ) ) {
			$args['user_id'] = $request['user_id'];
		}

		if ( isset( $request['parent'] ) && null !== $request['parent'] ) {
			$args['parent'] = $request['parent'];
		}

		if ( ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
		}

		if ( ! empty( $request['privacy'] ) ) {
			$args['privacy'] = $request['privacy'];
		}

		if ( ! empty( $request['exclude'] ) ) {
			$args['exclude'] = $request['exclude'];
		}

		if ( ! empty( $request['include'] ) ) {
			$args['in'] = $request['include'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_document_folder_get_items_query_args', $args, $request );

		$folders = $this->assemble_response_data( $args );

		$retval = array();
		foreach ( $folders['folders'] as $folder ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->document_endpoint->prepare_item_for_response( $folder, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $folders['total'], $args['per_page'] );

		/**
		 * Fires after a list of document's folder is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @param array            $folders  Fetched Folders.
		 */
		do_action( 'bp_rest_document_folder_get_items', $folders, $response, $request );

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

		/**
		 * Filter the folder `get_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_folder_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a single Folder.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 * @api            {GET} /wp-json/buddyboss/v1/document/folder/:id Get Folder
	 * @apiName        GetBBFolder
	 * @apiGroup       Document
	 * @apiDescription Retrieve a single folder.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} id A unique numeric ID for the folder.
	 */
	public function get_item( $request ) {

		$id = $request['id'];

		$folders = $this->assemble_response_data( array( 'folder_ids' => array( $id ) ) );

		if ( empty( $folders['folders'] ) ) {
			return new WP_Error(
				'bp_rest_folder_invalid_id',
				__( 'Invalid Folder ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$retval = '';
		foreach ( $folders['folders'] as $folder ) {
			$retval = $this->prepare_response_for_collection(
				$this->document_endpoint->prepare_item_for_response( $folder, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a folder is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_folder_get_item', $response, $request );

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

		$folder = new BP_Document_Folder( $request->get_param( 'id' ) );

		if ( true === $retval && empty( $folder->id ) ) {
			$retval = new WP_Error(
				'bp_rest_folder_invalid_id',
				__( 'Invalid Folder ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		if (
			true === $retval
			&& 'public' !== $folder->privacy
			&& true === $this->bp_rest_check_folder_privacy_restriction( $folder )
		) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to view this folder.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the document folder `get_item` permissions check.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Request $request The request sent to the API.
		 * @param bool            $retval  Returned value.
		 */
		return apply_filters( 'bp_rest_document_folder_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create document folder.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/document/folder Create Folder
	 * @apiName        CreateBBFolder
	 * @apiGroup       Document
	 * @apiDescription Create Document Folder.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {string} title Folder Title.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {Number} [parent] A unique numeric ID for the Parent Folder.
	 * @apiParam {string=public,loggedin,friends,onlyme,grouponly} [privacy=public] Privacy of the Folder.
	 */
	public function create_item( $request ) {

		$args = array(
			'title'   => wp_strip_all_tags( $request['title'] ),
			'privacy' => $request['privacy'],
		);

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
			$args['privacy']  = 'grouponly';
		}

		if ( isset( $request['parent'] ) && ! empty( $request['parent'] ) ) {
			$args['parent']   = $request['parent'];
			$parent_folder    = new BP_Document_Folder( $args['parent'] );
			$args['privacy']  = $parent_folder->privacy;
			$args['group_id'] = $parent_folder->group_id;
		}

		if (
			function_exists( 'bb_document_user_can_upload' ) &&
			! bb_document_user_can_upload( bp_loggedin_user_id(), (int) ( isset( $args['group_id'] ) ? $args['group_id'] : 0 ) )
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
		$args = apply_filters( 'bp_rest_document_folder_create_items_query_args', $args, $request );

		$folder_id = bp_folder_add( $args );

		if ( is_wp_error( $folder_id ) ) {
			return $folder_id;
		}

		$folders = $this->assemble_response_data( array( 'folder_ids' => array( $folder_id ) ) );
		$folder  = current( $folders['folders'] );

		$fields_update = $this->update_additional_fields_for_object( $folder, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->document_endpoint->prepare_item_for_response( $folder, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a Document folder is created via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_folder_create_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to create a folder.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$error = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a folder.', 'buddyboss' ),
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
			} elseif ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
				if (
					! bp_is_active( 'groups' )
					|| ! groups_can_user_manage_document( bp_loggedin_user_id(), (int) $request['group_id'] )
				) {
					$retval = new WP_Error(
						'bp_rest_invalid_permission',
						__( 'You don\'t have a permission to create a folder inside this group.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}

			if ( true === $retval && isset( $request['parent'] ) && ! empty( $request['parent'] ) ) {
				$parent_folder = new BP_Document_Folder( $request['parent'] );
				if ( empty( $parent_folder->id ) ) {
					$retval = new WP_Error(
						'bp_rest_invalid_parent_folder',
						__( 'Invalid Parent Folder ID.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				} elseif ( ! bp_folder_user_can_edit( $parent_folder->id ) ) {
					$retval = new WP_Error(
						'bp_rest_invalid_permission',
						__( 'You don\'t have a permission to create a folder inside this folder.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}
		}

		/**
		 * Filter the document folder `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_folder_create_items_permissions_check', $retval, $request );
	}

	/**
	 * Update a folder.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/document/folder/:id Update Folder
	 * @apiName        UpdateBBFolder
	 * @apiGroup       Document
	 * @apiDescription Update a folder.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the folder
	 * @apiParam {string} [title] Folder title.
	 * @apiParam {Number} [parent] A unique numeric ID for the parent folder.
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 * @apiParam {string=public,loggedin,onlyme,friends,grouponly} [privacy] Privacy of the folder.
	 */
	public function update_item( $request ) {
		$id = $request['id'];

		$folders = $this->assemble_response_data( array( 'folder_ids' => array( $id ) ) );

		if ( empty( $folders['folders'] ) ) {
			return new WP_Error(
				'bp_rest_folder_invalid_id',
				__( 'Invalid Folder ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$folder = end( $folders['folders'] );

		$args = array(
			'id'       => $folder->id,
			'user_id'  => $folder->user_id,
			'title'    => $folder->title,
			'group_id' => $folder->group_id,
			'parent'   => $folder->parent,
			'privacy'  => $folder->privacy,
		);

		if ( isset( $request['title'] ) && ! empty( $request['title'] ) ) {
			$args['title'] = wp_strip_all_tags( $request['title'] );
		}

		if ( isset( $request['privacy'] ) && ! empty( $request['privacy'] ) ) {
			$args['privacy'] = $request['privacy'];
		}

		if ( isset( $request['group_id'] ) && ! empty( $request['group_id'] ) ) {
			$args['group_id'] = $request['group_id'];
			$args['privacy']  = 'grouponly';
		}

		if ( isset( $request['parent'] ) && ! empty( $request['parent'] ) ) {
			$args['parent']  = $request['parent'];
			$parent_folder   = new BP_Document_Folder( $args['parent'] );
			$args['privacy'] = $parent_folder->privacy;
		} elseif ( isset( $request['parent'] ) && 0 === (int) $request['parent'] ) {
			$args['parent'] = $request['parent'];
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_document_folder_update_items_query_args', $args, $request );

		if ( isset( $request['privacy'] ) && ! empty( $request['privacy'] ) ) {
			bp_document_update_privacy( $folder->id, $request['privacy'], 'folder' );
		}

		// Move folders.
		if ( (int) $args['parent'] !== (int) $folder->parent ) {
			$folder_id             = $folder->id;
			$destination_folder_id = $args['parent'];
			$group_id              = $args['group_id'];

			if ( (int) $folder_id > 0 ) {
				if ( ! bp_folder_user_can_edit( $folder_id ) ) {
					return new WP_Error(
						'bp_rest_authorization_required',
						__( 'Sorry, You don\'t have permission to move this folder.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}

			if ( (int) $destination_folder_id > 0 ) {
				if ( ! bp_folder_user_can_edit( $destination_folder_id ) ) {
					return new WP_Error(
						'bp_rest_authorization_required',
						__( 'Sorry, You don\'t have permission to move this folder.', 'buddyboss' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			}

			$fetch_children = bp_document_get_folder_children( $folder_id );
			if ( ! empty( $fetch_children ) ) {
				if ( in_array( $destination_folder_id, $fetch_children, true ) ) {
					return new WP_Error(
						'bp_rest_invalid_move_folder',
						__( 'Couldn’t move item because it\'s parent folder.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				}
			}

			bp_document_move_folder_to_folder( $folder_id, $destination_folder_id, $group_id );
		}

		$updated_folder_id = bp_folder_add( $args );

		if ( is_wp_error( $updated_folder_id ) ) {
			return $updated_folder_id;
		}

		if ( ! is_numeric( $updated_folder_id ) ) {
			return new WP_Error(
				'bp_rest_user_cannot_update_folder',
				__( 'Cannot update existing folder.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$folders = $this->assemble_response_data( array( 'folder_ids' => array( $updated_folder_id ) ) );
		$folder  = current( $folders['folders'] );

		$fields_update = $this->update_additional_fields_for_object( $folder, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$retval = $this->prepare_response_for_collection(
			$this->document_endpoint->prepare_item_for_response( $folder, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after an document folder is updated via the REST API.
		 *
		 * @param WP_REST_Response     $response The response data.
		 * @param WP_REST_Request      $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_folder_update_item', $response, $request );

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
			__( 'Sorry, you need to be logged in to update this folder.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		$retval = $error;

		if ( is_user_logged_in() ) {
			$retval = true;
			$folder = new BP_Document_Folder( $request->get_param( 'id' ) );

			if ( empty( $folder->id ) ) {
				$retval = new WP_Error(
					'bp_rest_folder_invalid_id',
					__( 'Invalid Folder ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif (
				! bp_folder_user_can_edit( $folder ) ||
				(
					function_exists( 'bb_media_user_can_upload' ) &&
					! bb_media_user_can_upload( bp_loggedin_user_id(), (int) ( isset( $request['group_id'] ) ? $request['group_id'] : $folder->group_id ) )
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
						__( 'You don\'t have a permission to edit a folder inside this group.', 'buddyboss' ),
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
	 * Delete a single Folder.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/document/folder/:id Delete Folder
	 * @apiName        DeleteBBFolder
	 * @apiGroup       Document
	 * @apiDescription Delete a single Folder.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the folder.
	 */
	public function delete_item( $request ) {

		$id = $request['id'];

		$folders = $this->assemble_response_data( array( 'folder_ids' => array( $id ) ) );

		if ( empty( $folders['folders'] ) ) {
			return new WP_Error(
				'bp_rest_folder_invalid_id',
				__( 'Invalid Folder ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$previous = '';
		foreach ( $folders['folders'] as $folder ) {
			$previous = $this->prepare_response_for_collection(
				$this->document_endpoint->prepare_item_for_response( $folder, $request )
			);
		}

		if ( ! bp_folder_user_can_delete( $id ) ) {
			return WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to delete this folder.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$status = bp_folder_delete( array( 'id' => $id ) );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => $status,
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a folder is deleted via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_document_folder_delete_item', $response, $request );

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
			__( 'Sorry, you need to be logged in to delete this folder.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$folder = new BP_Document_Folder( $request->get_param( 'id' ) );

			if ( empty( $folder->id ) ) {
				$retval = new WP_Error(
					'bp_rest_folder_invalid_id',
					__( 'Invalid Folder ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! bp_folder_user_can_delete( $folder ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to delete this folder.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the document folder `delete_item` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_folder_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve document folder tree.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/document/folder/tree Folder tree
	 * @apiName        GetBBFoldersTree
	 * @apiGroup       Document
	 * @apiDescription Retrieve Folder tree
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [group_id] A unique numeric ID for the Group.
	 */
	public function folder_tree_items( $request ) {
		global $wpdb, $bp;

		$group_id = $request->get_param( 'group_id' );
		$user_id  = bp_loggedin_user_id();

		if ( empty( $group_id ) ) {
			$group_id = 0;
		}

		// phpcs:ignore
		$documents_folder_query = $wpdb->prepare( "SELECT * FROM {$bp->document->table_name_folder} WHERE user_id = %d AND group_id = %d ORDER BY id DESC", $user_id, $group_id );

		// phpcs:ignore
		$data = $wpdb->get_results( $documents_folder_query, ARRAY_A ); // db call ok; no-cache ok.

		if ( isset( $request['hierarchical'] ) && false !== $request['hierarchical'] ) {

			if ( ! empty( $data ) ) {
				// Build array of item references.
				foreach ( $data as $key => &$item ) {
					$items_by_reference[ $item['id'] ] = &$item;
					// Children array.
					$items_by_reference[ $item['id'] ]['children'] = array();
				}
			}

			if ( ! empty( $data ) ) {
				// Set items as children of the relevant parent item.
				foreach ( $data as $key => &$item ) {
					if ( $item['parent'] && isset( $items_by_reference[ $item['parent'] ] ) ) {
						$items_by_reference[ $item['parent'] ]['children'][] = &$item;
					}
				}
			}

			if ( ! empty( $data ) ) {
				// Remove items that were added to parents elsewhere.
				foreach ( $data as $key => &$item ) {
					if ( $item['parent'] && isset( $items_by_reference[ $item['parent'] ] ) ) {
						unset( $data[ $key ] );
					}
				}
			}
		}

		if ( ! empty( $data ) ) {
			$data = array_values( $data );
		}

		$response = rest_ensure_response( $data );

		/**
		 * Fires after a list of document's folder tree is fetched via the REST API.
		 *
		 * @since 0.1.0
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_document_folder_tree_items', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to view the folder tree.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 * @since 0.1.0
	 */
	public function folder_tree_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to view folder tree.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the folder tree `folder_tree_items` permissions check.
		 *
		 * @param bool            $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_document_folder_tree_items_permissions_check', $retval, $request );
	}

	/**
	 * Get document folders.
	 *
	 * @param array|string $args All arguments and defaults are shared with BP_Document_Folder::get(),
	 *                           except for the following.
	 *
	 * @return array
	 */
	public function assemble_response_data( $args ) {

		// Fetch specific document items based on ID's.
		if ( isset( $args['folder_ids'] ) && ! empty( $args['folder_ids'] ) ) {
			return bp_folder_get_specific( $args );

			// Fetch all activity items.
		} else {
			return bp_folder_get( $args );
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

		$args['title'] = array(
			'description'       => __( 'Folder Title.', 'buddyboss' ),
			'type'              => 'string',
			'required'          => true,
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['parent'] = array(
			'description'       => __( 'A unique numeric ID for the parent folder.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['group_id'] = array(
			'description'       => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$args['privacy'] = array(
			'description'       => __( 'Privacy of the folder.', 'buddyboss' ),
			'type'              => 'string',
			'enum'              => array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ),
			'default'           => 'public',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key        = 'edit';
			$args['id'] = array(
				'description'       => __( 'A unique numeric ID for the folder', 'buddyboss' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			);

			unset( $args['privacy']['default'] );

			$args['title']['required'] = false;
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_document_folder_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the document folder schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_document_folder',
			'type'       => 'object',
			'properties' => array(),
		);

		$schema['properties'] = $this->document_endpoint->get_item_schema()['properties'];

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
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order folders by which attribute.', 'buddyboss' ),
			'default'           => 'date_created',
			'type'              => 'string',
			'enum'              => array( 'id', 'title', 'date_created', 'user_id', 'group_id', 'privacy' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['max'] = array(
			'description'       => __( 'Maximum number of results to return', 'buddyboss' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_id'] = array(
			'description'       => __( 'Limit results to a specific user.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['parent'] = array(
			'description'       => __( 'A unique numeric ID for the parent Folder.', 'buddyboss' ),
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

		$params['privacy'] = array(
			'description'       => __( 'Privacy of the folder.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly' ),
				'type' => 'string',
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['include'] = array(
			'description'       => __( 'Ensure result set includes specific folder IDs.', 'buddyboss' ),
			'default'           => array(),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific folder IDs.', 'buddyboss' ),
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
		return apply_filters( 'bp_rest_document_folder_collection_params', $params );
	}

	/**
	 * Check user access based on the privacy for the single folder.
	 *
	 * @param BP_Document_Folder $folder Document Folder object.
	 *
	 * @return bool
	 */
	protected function bp_rest_check_folder_privacy_restriction( $folder ) {
		return (
				'onlyme' === $folder->privacy
				&& bp_loggedin_user_id() !== $folder->user_id
			)
			|| (
				'loggedin' === $folder->privacy
				&& empty( bp_loggedin_user_id() )
			)
			|| (
				bp_is_active( 'groups' )
				&& 'grouponly' === $folder->privacy
				&& ! empty( $folder->group_id )
				&& 'public' !== bp_get_group_status( groups_get_group( $folder->group_id ) )
				&& empty( groups_is_user_admin( bp_loggedin_user_id(), $folder->group_id ) )
				&& empty( groups_is_user_mod( bp_loggedin_user_id(), $folder->group_id ) )
				&& empty( groups_is_user_member( bp_loggedin_user_id(), $folder->group_id ) )
			)
			|| (
				bp_is_active( 'friends' )
				&& 'friends' === $folder->privacy
				&& ! empty( $folder->user_id )
				&& bp_loggedin_user_id() !== $folder->user_id
				&& 'is_friend' !== friends_check_friendship_status( $folder->user_id, bp_loggedin_user_id() )
			);
	}
}
