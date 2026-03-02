<?php
/**
 * BP REST: BP_REST_Attachments_Member_Avatar_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Member Avatar endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Attachments_Member_Avatar_Endpoint extends WP_REST_Controller {

	use BP_REST_Attachments;

	/**
	 * BP_Attachment_Avatar Instance.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_Attachment_Avatar
	 */
	protected $avatar_instance;

	/**
	 * Member object.
	 *
	 * @since 0.1.0
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * Member object type.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	protected $object = 'user';

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace       = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base       = 'members';
		$this->avatar_instance = new BP_Attachment_Avatar();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<user_id>[\d]+)/avatar',
			array(
				'args'   => array(
					'user_id' => array(
						'description' => __( 'A unique numeric ID for the Member.', 'buddyboss' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_item_collection_params(),
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
	 * Fetch an existing member avatar.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/members/:user_id/avatar Member Avatar
	 * @apiName        GetBBMemberAvatar
	 * @apiGroup       Members
	 * @apiDescription Retrieve member avatar
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser if the site is in Private Network.
	 * @apiParam {Number} user_id A unique numeric ID for the Member.
	 * @apiParam {Boolean} [html=false] Whether to return an <img> HTML element, vs a raw URL to an avatar.
	 * @apiParam {String} [alt] The alt attribute for the <img> element.
	 * @apiParam {Boolean} [no_grav=false] Whether to disable the default Gravatar fallback.
	 */
	public function get_item( $request ) {
		$args = array();

		foreach ( array( 'full', 'thumb' ) as $type ) {
			$args[ $type ] = bp_core_fetch_avatar(
				array(
					'object'  => $this->object,
					'type'    => $type,
					'item_id' => (int) $this->user->ID,
					'html'    => (bool) $request['html'],
					'alt'     => $request['alt'],
					'no_grav' => (bool) $request['no_gravatar'],
				)
			);
		}

		// Get the avatar object.
		$avatar = $this->get_avatar_object( $args );

		if ( ! $avatar->full && ! $avatar->thumb ) {
			return new WP_Error(
				'bp_rest_attachments_member_avatar_no_image',
				__( 'Sorry, there was a problem fetching the avatar.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $avatar, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member avatar is fetched via the REST API.
		 *
		 * @param string $avatar The avatar.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_attachments_member_avatar_get_item', $avatar, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to get a member avatar.
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

		$this->user = bp_rest_get_user( $request['user_id'] );

		if ( true === $retval && ! $this->user instanceof WP_User ) {
			$retval = new WP_Error(
				'bp_rest_member_invalid_id',
				__( 'Invalid member ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the member avatar `get_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_member_avatar_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Upload a member avatar.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/members/:user_id/avatar Create Member Avatar
	 * @apiName        CreateBBMemberAvatar
	 * @apiGroup       Members
	 * @apiDescription Create member avatar. This endpoint requires request to be sent in "multipart/form-data" format.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} user_id A unique numeric ID for the Member.
	 * @apiParam {string=bp_avatar_upload} action Action name for upload the Member avatar.
	 */
	public function create_item( $request ) {
		$request->set_param( 'context', 'edit' );

		// Get the image file from  $_FILES.
		$files = $request->get_file_params();

		if ( empty( $files ) ) {
			return new WP_Error(
				'bp_rest_attachments_member_avatar_no_image_file',
				__( 'Sorry, you need an image file to upload.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Upload the avatar.
		$avatar = $this->upload_avatar_from_file( $files );
		if ( is_wp_error( $avatar ) ) {
			return $avatar;
		}

		// Crop args.
		$r = array(
			'item_id'       => $request['user_id'],
			'object'        => 'user',
			'avatar_dir'    => 'avatars',
			'original_file' => $avatar->full,
			'crop_w'        => bp_core_avatar_full_width(),
			'crop_h'        => bp_core_avatar_full_height(),
			'crop_x'        => 0,
			'crop_y'        => 0,
		);

		/** This action is documented in bp-core/bp-core-avatars.php */
		do_action( 'xprofile_avatar_uploaded', (int) $request['user_id'], 'crop', $r );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $avatar, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member avatar is uploaded via the REST API.
		 *
		 * @param stdClass $avatar Avatar object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_attachments_member_avatar_create_item', $avatar, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to upload a member avatar.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );
		$args   = array();

		if ( isset( $this->user->ID ) ) {
			$args = array(
				'item_id' => (int) $this->user->ID,
				'object'  => 'user',
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

		if ( true === $retval && 'POST' === $request->get_method() && bp_disable_avatar_uploads() ) {
			$retval = new WP_Error(
				'bp_rest_attachments_member_avatar_disabled',
				__( 'Sorry, member avatar upload is disabled.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		if ( true === $retval && ! empty( $args ) && ! bp_attachments_current_user_can( 'edit_avatar', $args ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not authorized to perform this action.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the member avatar `create_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_member_avatar_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete an existing member avatar.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/members/:user_id/cover Delete Member Avatar
	 * @apiName        DeleteBBMemberAvatar
	 * @apiGroup       Members
	 * @apiDescription Delete member avatar
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} user_id A unique numeric ID for the Member.
	 */
	public function delete_item( $request ) {
		$request->set_param( 'context', 'edit' );
		$user_id = (int) $this->user->ID;

		if ( ! bp_get_user_has_avatar( $user_id ) ) {
			return new WP_Error(
				'bp_rest_attachments_member_avatar_no_uploaded_avatar',
				__( 'Sorry, there are no uploaded avatars for this user on this site.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$args = array();

		foreach ( array( 'full', 'thumb' ) as $type ) {
			$args[ $type ] = bp_core_fetch_avatar(
				array(
					'object'  => $this->object,
					'type'    => $type,
					'item_id' => $user_id,
					'html'    => false,
				)
			);
		}

		// Get the avatar object before deleting it.
		$avatar = $this->get_avatar_object( $args );

		$deleted = bp_core_delete_existing_avatar(
			array(
				'object'  => $this->object,
				'item_id' => $user_id,
			)
		);

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_attachments_member_avatar_delete_failed',
				__( 'Sorry, there was a problem deleting the avatar.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $avatar,
			)
		);

		/**
		 * Fires after a member avatar is deleted via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_attachments_member_avatar_delete_item', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to delete member avatar.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->create_item_permissions_check( $request );

		/**
		 * Filter the member avatar `delete_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_member_avatar_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares avatar data to return as an object.
	 *
	 * @param object          $avatar Avatar object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $avatar, $request ) {
		$data = array(
			'full'  => $avatar->full,
			'thumb' => $avatar->thumb,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		/**
		 * Filter a member avatar value returned from the API.
		 *
		 * @param WP_REST_Response $response Response.
		 * @param WP_REST_Request $request Request used to generate the response.
		 * @param object $avatar Avatar object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_attachments_member_avatar_prepare_value', $response, $request, $avatar );
	}

	/**
	 * Get the member avatar schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_attachments_member_avatar',
			'type'       => 'object',
			'properties' => array(
				'full'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Full size of the image file.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
				'thumb' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Thumb size of the image file.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'uri',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the member avatar schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_attachments_member_avatar_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for the `get_item`.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Removing unused params.
		unset( $params['search'], $params['page'], $params['per_page'] );

		$params['html'] = array(
			'description'       => __( 'Whether to return an <img> HTML element, vs a raw URL to an avatar.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['alt'] = array(
			'description'       => __( 'The alt attribute for the <img> element.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['no_gravatar'] = array(
			'description'       => __( 'Whether to disable the default Gravatar fallback.', 'buddyboss' ),
			'default'           => false,
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the item collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_attachments_member_avatar_collection_params', $params );
	}
}
