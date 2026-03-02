<?php
/**
 * BP REST: BP_REST_Members_Actions_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Members Actions endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Members_Actions_Endpoint extends WP_REST_Users_Controller {

	/**
	 * Reuse some parts of the BP_REST_Members_Endpoint class.
	 *
	 * @since 0.1.0
	 *
	 * @var BP_REST_Members_Endpoint
	 */
	protected $members_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace        = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base        = buddypress()->members->id . '/action';
		$this->members_endpoint = new BP_REST_Members_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id'     => array(
						'description' => __( 'A unique numeric ID for the member.', 'buddyboss' ),
						'type'        => 'integer',
					),
					'action' => array(
						'description'       => __( 'Action name which you want to perform for the member.', 'buddyboss' ),
						'type'              => 'string',
						'enum'              => apply_filters( 'bp_rest_members_action_enum_args', array( 'follow', 'unfollow' ) ),
						'validate_callback' => 'rest_validate_request_arg',
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

		register_rest_route(
			$this->namespace,
			'/' . buddypress()->members->id . '/presence',
			array(
				'args'   => array(
					'ids' => array(
						'description'       => __( 'A unique users IDs of the member.', 'buddyboss' ),
						'type'              => 'array',
						'required'          => true,
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => 'wp_parse_id_list',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'presence_item' ),
					'permission_callback' => array( $this, 'presence_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_presence_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access create members.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 *
	 * @api {POST} /wp-json/buddyboss/v1/members/action/:user_id Member Action
	 * @apiName GetBBMembers-UpdateMembersAction
	 * @apiGroup Members
	 * @apiDescription Update members action
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Number} user_id A unique numeric ID for the member.
	 * @apiParam {String=follow,unfollow} action Action name which you want to perform for the member.
	 */
	public function update_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$args = array(
			'leader_id'   => (int) $request['id'],
			'follower_id' => get_current_user_id(),
		);

		$response           = array();
		$retval             = array();
		$response['action'] = false;

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array $args Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_members_action_query_args', $args, $request );

		$action = $request['action'];

		switch ( $action ) {
			case 'follow':
				if ( ! $this->bp_rest_follow_is_following( $args ) ) {
					$result             = $this->bp_rest_follow_start_following( $args );
					$response['action'] = ( ! empty( $result ) ? true : false );
				}
				break;
			case 'unfollow':
				if ( $this->bp_rest_follow_is_following( $args ) ) {
					$result             = $this->bp_rest_follow_stop_following( $args );
					$response['action'] = ( ! empty( $result ) ? true : false );
				}
				break;
		}

		$member_query = bp_core_get_users( array( 'include' => (int) $request['id'] ) );
		$members      = $member_query['users'];

		$request->set_param( 'context', 'view' );

		foreach ( $members as $member ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->members_endpoint->prepare_item_for_response( $member, $request )
			);
		}

		$response['data'] = ( count( $members ) > 1 ? $retval : ( ! empty( $retval ) ? $retval[0] : '' ) );

		$response = rest_ensure_response( $response );

		/**
		 * Fires after a Member action is updated via the REST API.
		 *
		 * @param BP_XProfile_Field $field Created field object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_members_action_update_item', $response, $request );

		return $response;

	}

	/**
	 * Check if a given request has access to update a member.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you do not have access to list components.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$user   = bp_rest_get_user( $request['id'] );

			if ( ! $user instanceof WP_User ) {
				$retval = new WP_Error(
					'bp_rest_member_invalid_id',
					__( 'Invalid member ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the members `update_item` permissions check.
		 *
		 * @param bool|WP_Error $retval Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_members_action_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Checks if a given member is online or not.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 *
	 * @api {POST} /wp-json/buddyboss/v1/members/presence Member Presence State
	 * @apiName GetBBMembers-MembersPresence
	 * @apiGroup Members
	 * @apiDescription Members Presence.
	 * @apiVersion 1.0.0
	 * @apiPermission LoggedInUser
	 * @apiParam {Array} ids A unique numeric ID for the members
	 */
	public function presence_item( $request ) {

		if ( isset( $request['ids'] ) ) {
			bp_core_record_activity();
		}

		$users  = $request->get_param( 'ids' );
		$retval = bb_get_users_presence( $users );

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a member presence status is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_presence_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to check member status for presence.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function presence_item_permissions_check( $request ) {

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the members `presence_item_permissions_check` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_presence_item_permissions_check', $retval, $request );
	}

	/**
	 * Get the members action schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_members_action',
			'type'       => 'object',
			'properties' => array(
				'action' => array(
					'description' => __( 'Action performed or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'data'   => array(
					'description' => __( 'Object of member.', 'buddyboss' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id'                => array(
							'description' => __( 'A unique numeric ID for the Member.', 'buddyboss' ),
							'type'        => 'integer',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
						'name'              => array(
							'description' => __( 'Display name for the member.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'arg_options' => array(
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
						'mention_name'      => array(
							'description' => __( 'The name used for that user in @-mentions.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'arg_options' => array(
								'sanitize_callback' => 'sanitize_text_field',
							),
						),
						'link'              => array(
							'description' => __( 'Profile URL of the member.', 'buddyboss' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
						'user_login'        => array(
							'description' => __( 'An alphanumeric identifier for the Member.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'required'    => true,
							'arg_options' => array(
								'sanitize_callback' => array( $this, 'check_username' ),
							),
						),
						'member_types'      => array(
							'description' => __( 'Member types associated with the member.', 'buddyboss' ),
							'type'        => 'object',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
						'xprofile'          => array(
							'description' => __( 'Member XProfile groups and its fields.', 'buddyboss' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'friendship_status' => array(
							'description' => __( 'Friendship relation with, current, logged in user.', 'buddyboss' ),
							'type'        => 'string',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
							'enum'        => array( 'is_friends', 'not_friends', 'pending', 'awaiting_response' ),
						),
						'is_following'      => array(
							'description' => __( 'Check if a user is following or not.', 'buddyboss' ),
							'type'        => 'boolean',
							'context'     => array( 'embed', 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
		);

		// Avatars.
		if ( true === buddypress()->avatar->show_avatars ) {
			$avatar_properties = array();

			$avatar_properties['full'] = array(
				/* translators: Full image size for the member Avatar */
				'description' => sprintf( __( 'Avatar URL with full image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_full_width() ), bp_core_number_format( bp_core_avatar_full_height() ) ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'embed', 'view', 'edit' ),
			);

			$avatar_properties['thumb'] = array(
				/* translators: Thumb imaze size for the member Avatar */
				'description' => sprintf( __( 'Avatar URL with thumb image size (%1$d x %2$d pixels).', 'buddyboss' ), bp_core_number_format( bp_core_avatar_thumb_width() ), bp_core_number_format( bp_core_avatar_thumb_height() ) ),
				'type'        => 'string',
				'format'      => 'uri',
				'context'     => array( 'embed', 'view', 'edit' ),
			);

			$schema['properties']['data']['avatar_urls'] = array(
				'description' => __( 'Avatar URLs for the member.', 'buddyboss' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);
		}

		$schema['properties']['data']['cover_url'] = array(
			'description' => __( 'Cover images URL for the member.', 'buddyboss' ),
			'type'        => 'string',
			'context'     => array( 'embed', 'view', 'edit' ),
			'readonly'    => true,
		);

		/**
		 * Filters the members action schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_members_action_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of members.
	 *
	 * @since 0.1.0
	 *
	 * @return array
	 */
	public function get_presence_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_members_presence',
			'type'       => 'object',
			'properties' => array(
				'id'     => array(
					'description' => __( 'A unique numeric ID for the Member.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'status' => array(
					'description' => __( 'Current presence status of the user is online or offline.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the members presence schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_members_presence_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Check member is following or not.
	 *
	 * @param array $args Argument with leader_id and follower_id.
	 *
	 * @return mixed
	 */
	private function bp_rest_follow_is_following( $args ) {
		if ( bp_is_active( 'follow' ) && function_exists( 'bp_follow_is_following' ) ) {
			return bp_follow_is_following( $args );
		} elseif ( function_exists( 'bp_is_following' ) ) {
			return bp_is_following( $args );
		}

		return false;
	}

	/**
	 * Start Following the member.
	 *
	 * @param array $args Argument with leader_id and follower_id.
	 *
	 * @return mixed
	 */
	private function bp_rest_follow_start_following( $args ) {
		if ( bp_is_active( 'follow' ) && function_exists( 'bp_follow_start_following' ) ) {
			return bp_follow_start_following( $args );
		} elseif ( function_exists( 'bp_start_following' ) ) {
			return bp_start_following( $args );
		}

		return false;
	}

	/**
	 * Stop Following the member.
	 *
	 * @param array $args Argument with leader_id and follower_id.
	 *
	 * @return mixed
	 */
	private function bp_rest_follow_stop_following( $args ) {
		if ( bp_is_active( 'follow' ) && function_exists( 'bp_follow_stop_following' ) ) {
			return bp_follow_stop_following( $args );
		} elseif ( function_exists( 'bp_stop_following' ) ) {
			return bp_stop_following( $args );
		}

		return false;
	}

}
