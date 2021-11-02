<?php
/**
 * BP REST: BP_REST_Attachments_Group_Cover_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group Cover endpoints.
 *
 * /groups/<group_id>/cover
 *
 * @since 0.1.0
 */
class BP_REST_Attachments_Group_Cover_Endpoint extends WP_REST_Controller {

	use BP_REST_Attachments;

	/**
	 * BP_Attachment_Cover_Image Instance.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_Attachment_Cover_Image
	 */
	protected $attachment_instance;

	/**
	 * Reuse some parts of the BP_REST_Groups_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Hold the group object.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_Groups_Group
	 */
	protected $group;

	/**
	 * Group object type.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $object = 'group';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace           = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base           = buddypress()->groups->id;
		$this->groups_endpoint     = new BP_REST_Groups_Endpoint();
		$this->attachment_instance = new BP_Attachment_Cover_Image();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<group_id>[\d]+)/cover',
			array(
				'args'   => array(
					'group_id' => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
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
	 * Fetch an existing group cover.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/:group_id/cover Group Cover
	 * @apiName        GetBBGroupCover
	 * @apiGroup       Groups
	 * @apiDescription Retrieve group cover
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 */
	public function get_item( $request ) {
		$cover_url = bp_get_group_cover_url( $this->group );

		if ( empty( $cover_url ) ) {
			return new WP_Error(
				'bp_rest_attachments_group_cover_no_image',
				__( 'Sorry, there was a problem fetching this group cover.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $cover_url, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group cover is fetched via the REST API.
		 *
		 * @param string $cover_url The group cover url.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_attachments_group_cover_get_item', $cover_url, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get a group cover.
	 *
	 * @param WP_REST_Request $request Full details about the request.
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

		$this->group = $this->groups_endpoint->get_group_object( $request );

		if ( true === $retval && ! $this->group ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group id.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the group cover `get_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_group_cover_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Upload a group cover.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/groups/:group_id/cover Create Group Cover
	 * @apiName        CreateBBGroupCover
	 * @apiGroup       Groups
	 * @apiDescription Create group cover. This endpoint requires request to be sent in "multipart/form-data" format.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 * @apiParam {string=bp_cover_image_upload} action Action name for upload the group cover image.
	 */
	public function create_item( $request ) {
		$request->set_param( 'context', 'edit' );

		// Get the image file from $_FILES.
		$files = $request->get_file_params();

		if ( empty( $files ) ) {
			return new WP_Error(
				'bp_rest_attachments_group_cover_no_image_file',
				__( 'Sorry, you need an image file to upload.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Upload the group cover.
		$cover_url = $this->upload_cover_from_file( $files );
		if ( is_wp_error( $cover_url ) ) {
			return $cover_url;
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $cover_url, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a group cover is uploaded via the REST API.
		 *
		 * @param string $cover_url The group cover url.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_attachments_group_cover_create_item', $cover_url, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to upload a group cover.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = $this->delete_item_permissions_check( $request );

		if ( true === $retval && bp_disable_group_cover_image_uploads() ) {
			$retval = new WP_Error(
				'bp_rest_attachments_group_cover_disabled',
				__( 'Sorry, group cover upload is disabled.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		/**
		 * Filter the group cover `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_group_cover_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete an existing group cover.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/groups/:group_id/cover Delete Group Cover
	 * @apiName        DeleteBBGroupCover
	 * @apiGroup       Groups
	 * @apiDescription Delete group cover
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} group_id A unique numeric ID for the Group.
	 */
	public function delete_item( $request ) {
		$request->set_param( 'context', 'edit' );

		$cover_url = bp_get_group_cover_url( $this->group );
		$deleted   = bp_attachments_delete_file(
			array(
				'item_id'    => (int) $this->group->id,
				'object_dir' => $this->get_cover_object_component(),
				'type'       => 'cover-image',
			)
		);

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_attachments_group_cover_delete_failed',
				__( 'Sorry, there was a problem deleting this group cover.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		/**
		 * Fires if the cover photo was successfully deleted.
		 *
		 * @param int $item_id Inform about the item id the cover photo was deleted for.
		 */
		do_action( 'groups_cover_image_deleted', (int) $this->group->id );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $cover_url,
			)
		);

		/**
		 * Fires after a group cover is deleted via the REST API.
		 *
		 * @param BP_Groups_Group $group The group object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_attachments_group_cover_delete_item', $this->group, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete a group cover.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );
		$args   = array();

		if ( isset( $this->group->id ) ) {
			$args = array(
				'item_id' => (int) $this->group->id,
				'object'  => $this->object,
			);
		}

		if ( true === $retval && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you need to be logged in to perform this action.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && ! empty( $args ) && ! bp_attachments_current_user_can( 'edit_cover_image', $args ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not authorized to perform this action.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the group cover `delete_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_group_cover_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares group cover to return as an object.
	 *
	 * @param string          $cover_url Group cover url.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $cover_url, $request ) {
		$data = array(
			'image'   => ( is_array( $cover_url ) ? $cover_url['cover'] : $cover_url ),
			'warning' => ( is_array( $cover_url ) && isset( $cover_url['warning'] ) ? $cover_url['warning'] : '' ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		/**
		 * Filter a group cover value returned from the API.
		 *
		 * @param WP_REST_Response $response Response.
		 * @param WP_REST_Request $request Request used to generate the response.
		 * @param string $cover_url Group cover url.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_group_cover_prepare_value', $response, $request, $cover_url );
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
			'title'      => 'bp_attachments_group_cover',
			'type'       => 'object',
			'properties' => array(
				'image'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Full size of the image file.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'warning' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Warning while uploading the cover photo.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the group cover schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_attachments_group_cover_schema', $this->add_additional_fields_schema( $schema ) );
	}
}
