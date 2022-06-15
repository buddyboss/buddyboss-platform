<?php
/**
 * BP REST: BP_REST_Members_Permissions_Endpoint class
 *
 * @package BuddyBoss
 * @since 1.5.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Member Permissions Settings endpoints.
 *
 * @since 1.5.7
 */
class BP_REST_Members_Permissions_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 1.5.7
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'members';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 1.5.7
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/me/permissions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve Settings Based on Membership.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 1.5.7
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/members/me/permissions Member Permissions
	 * @apiName        GetBBMemberPermissions
	 * @apiGroup       Members
	 * @apiDescription Retrieve Member Permissions
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function get_items( $request ) {

		$user_id = bp_loggedin_user_id();

		$data = array();

		$data['can_create_activity'] = bp_is_active( 'activity' ) && (
			! function_exists( 'bb_user_can_create_activity' ) ||
			(
				function_exists( 'bb_user_can_create_activity' ) &&
				bb_user_can_create_activity()
			)
		);
		$data['can_create_group']    = bp_is_active( 'groups' ) && ! bp_restrict_group_creation() && bp_user_can_create_groups();
		$data['can_join_group']      = bp_is_active( 'groups' ) && $this->bp_rest_check_user_group_join( $user_id );

		$data['can_create_media'] = (
			function_exists( 'bb_user_has_access_upload_media' )
			? bb_user_has_access_upload_media( 0, $user_id, 0, 0, 'profile' )
			: bp_is_active( 'media' ) && bp_is_profile_media_support_enabled() && ( ! function_exists( 'bb_user_can_create_media' ) || bb_user_can_create_media() )
		);

		$data['can_create_forum_media'] = (
			function_exists( 'bb_user_has_access_upload_media' )
			? bb_user_has_access_upload_media( 0, $user_id, 0, 0, 'forum' )
			: bp_is_active( 'media' ) && bp_is_forums_media_support_enabled() && ( ! function_exists( 'bb_user_can_create_media' ) || bb_user_can_create_media() )
		);

		$data['can_create_message_media'] = (
			function_exists( 'bb_user_has_access_upload_media' )
			? bb_user_has_access_upload_media( 0, $user_id, 0, 0, 'message' )
			: bp_is_active( 'media' ) && bp_is_messages_media_support_enabled() && ( ! function_exists( 'bb_user_can_create_media' ) || bb_user_can_create_media() )
		);

		$data['can_create_document'] = (
			function_exists( 'bb_user_has_access_upload_document' )
			? bb_user_has_access_upload_document( 0, $user_id, 0, 0, 'profile' )
			: bp_is_active( 'document' ) && bp_is_profile_document_support_enabled() && ( ! function_exists( 'bb_user_can_create_document' ) || bb_user_can_create_document() )
		);

		$data['can_create_forum_document'] = (
			function_exists( 'bb_user_has_access_upload_document' )
			? bb_user_has_access_upload_document( 0, $user_id, 0, 0, 'forum' )
			: bp_is_active( 'document' ) && bp_is_forums_document_support_enabled() && ( ! function_exists( 'bb_user_can_create_document' ) || bb_user_can_create_document() )
		);

		$data['can_create_message_document'] = (
			function_exists( 'bb_user_has_access_upload_document' )
			? bb_user_has_access_upload_document( 0, $user_id, 0, 0, 'message' )
			: bp_is_active( 'document' ) && bp_is_messages_document_support_enabled() && ( ! function_exists( 'bb_user_can_create_document' ) || bb_user_can_create_document() )
		);

		$data['can_create_video'] = (
			function_exists( 'bb_user_has_access_upload_video' )
			? bb_user_has_access_upload_video( 0, $user_id, 0, 0, 'profile' )
			: bp_is_active( 'video' ) && bp_is_profile_video_support_enabled() && ( ! function_exists( 'bb_user_can_create_video' ) || bb_user_can_create_video() )
		);

		$data['can_create_forum_video'] = (
			function_exists( 'bb_user_has_access_upload_video' )
			? bb_user_has_access_upload_video( 0, $user_id, 0, 0, 'forum' )
			: bp_is_active( 'video' ) && bp_is_forums_video_support_enabled() && ( ! function_exists( 'bb_user_can_create_video' ) || bb_user_can_create_video() )
		);

		$data['can_create_message_video'] = (
			function_exists( 'bb_user_has_access_upload_video' )
			? bb_user_has_access_upload_video( 0, $user_id, 0, 0, 'message' )
			: bp_is_active( 'video' ) && bp_is_messages_video_support_enabled() && ( ! function_exists( 'bb_user_can_create_video' ) || bb_user_can_create_video() )
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$response = rest_ensure_response( $data );

		/**
		 * Fires after a list of courses is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 1.0.7
		 */
		do_action( 'bp_rest_members_permissions_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to list courses.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 1.5.7
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the user Members Permissions `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 1.0.7
		 */
		return apply_filters( 'bp_rest_members_permissions_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Get the forums schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 1.0.7
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'members_permission',
			'type'       => 'object',
			'properties' => array(
				'can_create_activity'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create activity or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_group'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_join_group'              => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can join the group or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_media'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the media or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_forum_media'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the media into forums or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_message_media'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the media into messages or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_document'         => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the document or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_forum_document'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the document into forums or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_message_document' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the document into messages or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_video'            => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the video or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_forum_video'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the video into forums or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'can_create_message_video'    => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'Whether the user can create the video into messages or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the course schema.
		 *
		 * @param string $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_members_permissions_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Check the current has permission to join the group or not.
	 *
	 * @param int $user_id Current User ID.
	 *
	 * @return bool
	 */
	protected function bp_rest_check_user_group_join( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			return false;
		}

		return apply_filters( 'bp_rest_user_can_join_group', true, $user_id );
	}
}
