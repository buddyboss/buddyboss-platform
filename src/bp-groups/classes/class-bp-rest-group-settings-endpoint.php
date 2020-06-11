<?php
/**
 * BP REST: BP_REST_Group_Settings_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Group Settings endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Group_Settings_Endpoint extends WP_REST_Controller {

	/**
	 * BP_REST_Groups_Endpoint Instance.
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $groups_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace       = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base       = buddypress()->groups->id;
		$this->groups_endpoint = new BP_REST_Groups_Endpoint();
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/settings',
			array(
				'args'   => array(
					'id' => array(
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
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve groups settings.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of groups object data.
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/groups/:id/settings Group Settings
	 * @apiName        GetBBGroupsSettings
	 * @apiGroup       Groups
	 * @apiDescription Retrieve groups settings.
	 * @apiVersion     1.0.0
	 * @apiParam {Number} id A unique numeric ID for the Group.
	 */
	public function get_item( $request ) {

		$group = $this->groups_endpoint->get_group_object( $request );

		if ( empty( $group->id ) ) {
			return new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		$fields = $this->get_settings_fields( $group->id );
		$retval = array();
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $field, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of groups settings is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_settings_get_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to group details.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to see the group settings.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && ! bp_is_active( 'groups' ) ) {
			$retval = new WP_Error(
				'bp_rest_component_required',
				__( 'Sorry, Groups component was not enabled.', 'buddyboss' ),
				array(
					'status' => '404',
				)
			);
		}

		$group = $this->groups_endpoint->get_group_object( $request );
		if ( true === $retval && empty( $group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// If group author does not match logged_in user, block update.
		if ( true === $retval && ! $this->groups_endpoint->can_user_delete_or_update( $group ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to see the group settings.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 *  Filter the group settings permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_settings_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Update Group Settings options.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error | WP_REST_Response
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/groups/:id/settings Update Group Settings
	 * @apiName        UpdateBBGroupsSettings
	 * @apiGroup       Groups
	 * @apiDescription Update Group settings.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Group.
	 * @apiParam {Array} fields The list of fields to update with name and value of the field.
	 */
	public function update_item( $request ) {
		$group = $this->groups_endpoint->get_group_object( $request );

		$updated = $this->update_settings_fields( $request );
		$fields  = $this->get_settings_fields( $group->id );

		$fields  = apply_filters( 'bp_rest_group_setting_update_fields', $fields, $group->id );
		$updated = apply_filters( 'bp_rest_group_setting_update_message', $updated, $group->id );

		$fields_update = $this->update_additional_fields_for_object( $group->id, $request );
		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$data = array();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$data[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $field, $request )
				);
			}
		}

		$retval = array(
			'error'   => ( isset( $updated['error'] ) ? $updated['error'] : false ),
			'notices' => ( isset( $updated['notice'] ) ? $updated['notice'] : false ),
			'data'    => $data,
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after group setting options are updated via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_group_settings_options_update_item', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update account settings options.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function update_item_permissions_check( $request ) {
		$retval = true;

		$retval = true;

		if ( ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to update the group settings.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( true === $retval && ! bp_is_active( 'groups' ) ) {
			$retval = new WP_Error(
				'bp_rest_component_required',
				__( 'Sorry, Groups component was not enabled.', 'buddyboss' ),
				array(
					'status' => '404',
				)
			);
		}

		$group = $this->groups_endpoint->get_group_object( $request );
		if ( true === $retval && empty( $group->id ) ) {
			$retval = new WP_Error(
				'bp_rest_group_invalid_id',
				__( 'Invalid group ID.', 'buddyboss' ),
				array(
					'status' => 404,
				)
			);
		}

		// If group author does not match logged_in user, block update.
		if ( true === $retval && ! $this->groups_endpoint->can_user_delete_or_update( $group ) ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, you are not allowed to update the group settings.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		/**
		 * Filter the group settings options `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_settings_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Edit some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key  = 'update_item';
			$args = array(
				'id'     => array(
					'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
					'type'        => 'integer',
					'required'    => true,
				),
				'fields' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The list of fields Objects to update with name and value of the field.', 'buddyboss' ),
					'type'        => 'object',
					'required'    => true,
				),
			);
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_group_settings_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepares account settings data for return as an object.
	 *
	 * @param object          $field   Field object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $field, $request ) {
		$data = array(
			'label'       => ( isset( $field['label'] ) && ! empty( $field['label'] ) ? $field['label'] : '' ),
			'name'        => ( isset( $field['name'] ) && ! empty( $field['name'] ) ? $field['name'] : '' ),
			'description' => ( isset( $field['description'] ) && ! empty( $field['description'] ) ? $field['description'] : '' ),
			'type'        => ( isset( $field['field'] ) && ! empty( $field['field'] ) ? $field['field'] : '' ),
			'value'       => ( isset( $field['value'] ) && ! empty( $field['value'] ) ? $field['value'] : '' ),
			'options'     => ( isset( $field['options'] ) && ! empty( $field['options'] ) ? $field['options'] : array() ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		/**
		 * Filter a group settings value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param object           $field    Field object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_group_setting_prepare_value', $response, $request, $field );
	}

	/**
	 * Get the group details schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_groups_details',
			'type'       => 'object',
			'properties' => array(
				'label'       => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Label for the setting.', 'buddyboss' ),
					'type'        => 'object',
					'readonly'    => true,
					'items'       => array(
						'type' => 'array',
					),
				),
				'name'        => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Setting field name.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'description' => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Setting field description.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'type'        => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Field type for the setting.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'value'       => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Selected value for the setting.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
				'options'     => array(
					'context'     => array( 'embed', 'view' ),
					'description' => __( 'Available options for the setting.', 'buddyboss' ),
					'type'        => 'array',
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the group details schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_group_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get Group Settings.
	 *
	 * @param integer $group_id Group ID.
	 *
	 * @return mixed|void
	 */
	protected function get_settings_fields( $group_id ) {
		$fields                             = array();
		buddypress()->groups->current_group = groups_get_group( $group_id );

		$fields[] = array(
			'label'       => esc_html__( 'Privacy Options', 'buddyboss' ),
			'name'        => 'group-status',
			'description' => '',
			'field'       => 'radio',
			'value'       => bp_get_new_group_status(),
			'options'     => array(
				array(
					'label'             => esc_html__( 'This is a public group', 'buddyboss' ),
					'value'             => 'public',
					'description'       => '<ul id="public-group-description">' .
											'<li>' . esc_html__( 'Any site member can join this group.', 'buddyboss' ) . '</li>' .
											'<li>' . esc_html__( 'This group will be listed in the groups directory and in search results.', 'buddyboss' ) . '</li>' .
											'<li>' . esc_html__( 'Group content and activity will be visible to any site member.', 'buddyboss' ) . '</li>' .
										'</ul>',
					'is_default_option' => 'public' === bp_get_new_group_status() || ! bp_get_new_group_status(),
				),
				array(
					'label'             => esc_html__( 'This is a private group', 'buddyboss' ),
					'value'             => 'private',
					'description'       => '<ul id="public-group-description">' .
											'<li>' . esc_html__( 'Only people who request membership and are accepted can join the group.', 'buddyboss' ) . '</li>' .
											'<li>' . esc_html__( 'This group will be listed in the groups directory and in search results.', 'buddyboss' ) . '</li>' .
											'<li>' . esc_html__( 'Group content and activity will only be visible to members of the group.', 'buddyboss' ) . '</li>' .
										'</ul>',
					'is_default_option' => 'private' === bp_get_new_group_status(),
				),
				array(
					'label'             => esc_html__( 'This is a hidden group', 'buddyboss' ),
					'value'             => 'hidden',
					'description'       => '<ul id="public-group-description">' .
											'<li>' . esc_html__( 'Only people who are invited can join the group.', 'buddyboss' ) . '</li>' .
											'<li>' . esc_html__( 'This group will not be listed in the groups directory or search results', 'buddyboss' ) . '</li>' .
											'<li>' . esc_html__( 'Group content and activity will only be visible to members of the group.', 'buddyboss' ) . '</li>' .
										'</ul>',
					'is_default_option' => 'private' === bp_get_new_group_status(),
				),
			),
		);

		$fields[] = array(
			'label'       => esc_html__( 'Group Invitations', 'buddyboss' ),
			'name'        => 'group-invite-status',
			'description' => esc_html__( 'Which members of this group are allowed to invite others?', 'buddyboss' ),
			'field'       => 'radio',
			'value'       => bp_group_get_invite_status( $group_id ),
			'options'     => array(
				array(
					'label'             => esc_html__( 'All group members', 'buddyboss' ),
					'value'             => 'members',
					'description'       => '',
					'is_default_option' => 'members' === bp_group_get_invite_status( $group_id ),
				),
				array(
					'label'             => esc_html__( 'Organizers and Moderators only', 'buddyboss' ),
					'value'             => 'mods',
					'description'       => '',
					'is_default_option' => 'mods' === bp_group_get_invite_status( $group_id ),
				),
				array(
					'label'             => esc_html__( 'Organizers only', 'buddyboss' ),
					'value'             => 'admins',
					'description'       => '',
					'is_default_option' => 'admins' === bp_group_get_invite_status( $group_id ),
				),
			),
		);

		$fields[] = array(
			'label'       => esc_html__( 'Activity Feeds', 'buddyboss' ),
			'name'        => 'group-activity-feed-status',
			'description' => esc_html__( 'Which members of this group are allowed to post into the activity feed?', 'buddyboss' ),
			'field'       => 'radio',
			'value'       => bp_group_get_activity_feed_status( $group_id ),
			'options'     => array(
				array(
					'label'             => esc_html__( 'All group members', 'buddyboss' ),
					'value'             => 'members',
					'description'       => '',
					'is_default_option' => 'members' === bp_group_get_activity_feed_status( $group_id ),
				),
				array(
					'label'             => esc_html__( 'Organizers and Moderators only', 'buddyboss' ),
					'value'             => 'mods',
					'description'       => '',
					'is_default_option' => 'mods' === bp_group_get_activity_feed_status( $group_id ),
				),
				array(
					'label'             => esc_html__( 'Organizers only', 'buddyboss' ),
					'value'             => 'admins',
					'description'       => '',
					'is_default_option' => 'admins' === bp_group_get_activity_feed_status( $group_id ),
				),
			),
		);

		if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) {
			$fields[] = array(
				'label'       => esc_html__( 'Group Photos', 'buddyboss' ),
				'name'        => 'group-media-status',
				'description' => esc_html__( 'Which members of this group are allowed to manage photos?', 'buddyboss' ),
				'field'       => 'radio',
				'value'       => bp_group_get_media_status( $group_id ),
				'options'     => array(
					array(
						'label'             => esc_html__( 'All group members', 'buddyboss' ),
						'value'             => 'members',
						'description'       => '',
						'is_default_option' => 'members' === bp_group_get_media_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers and Moderators only', 'buddyboss' ),
						'value'             => 'mods',
						'description'       => '',
						'is_default_option' => 'mods' === bp_group_get_media_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers only', 'buddyboss' ),
						'value'             => 'admins',
						'description'       => '',
						'is_default_option' => 'admins' === bp_group_get_media_status( $group_id ),
					),
				),
			);
		}

		if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) {
			$fields[] = array(
				'label'       => esc_html__( 'Group Albums', 'buddyboss' ),
				'name'        => 'group-album-status',
				'description' => esc_html__( 'Which members of this group are allowed to manage albums?', 'buddyboss' ),
				'field'       => 'radio',
				'value'       => bp_group_get_album_status( $group_id ),
				'options'     => array(
					array(
						'label'             => esc_html__( 'All group members', 'buddyboss' ),
						'value'             => 'members',
						'description'       => '',
						'is_default_option' => 'members' === bp_group_get_album_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers and Moderators only', 'buddyboss' ),
						'value'             => 'mods',
						'description'       => '',
						'is_default_option' => 'mods' === bp_group_get_album_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers only', 'buddyboss' ),
						'value'             => 'admins',
						'description'       => '',
						'is_default_option' => 'admins' === bp_group_get_album_status( $group_id ),
					),
				),
			);
		}

		if ( bp_is_active( 'messages' ) && function_exists( 'bp_disable_group_messages' ) && true === bp_disable_group_messages() ) {
			$fields[] = array(
				'label'       => esc_html__( 'Group Messages', 'buddyboss' ),
				'name'        => 'group-message-status',
				'description' => esc_html__( 'Which members of this group are allowed to send group messages?', 'buddyboss' ),
				'field'       => 'radio',
				'value'       => bp_group_get_message_status( $group_id ),
				'options'     => array(
					array(
						'label'             => esc_html__( 'All group members', 'buddyboss' ),
						'value'             => 'members',
						'description'       => '',
						'is_default_option' => 'members' === bp_group_get_message_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers and Moderators only', 'buddyboss' ),
						'value'             => 'mods',
						'description'       => '',
						'is_default_option' => 'mods' === bp_group_get_message_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers only', 'buddyboss' ),
						'value'             => 'admins',
						'description'       => '',
						'is_default_option' => 'admins' === bp_group_get_message_status( $group_id ),
					),
				),
			);
		}

		// Group Types.
		$group_types = bp_groups_get_group_types( array( 'show_in_create_screen' => true ), 'objects' );

		// Hide Group Types if none is selected in Users > Profile Type > E.g. (Students) > Allowed Group Types meta box.
		if (
			function_exists( 'bp_restrict_group_creation' )
			&& function_exists( 'bp_member_type_enable_disable' )
			&& false === bp_restrict_group_creation()
			&& true === bp_member_type_enable_disable()
		) {
			$get_all_registered_member_types = bp_get_active_member_types();
			if ( isset( $get_all_registered_member_types ) && ! empty( $get_all_registered_member_types ) ) {

				$current_user_member_type = bp_get_member_type( bp_loggedin_user_id() );
				if ( '' !== $current_user_member_type ) {
					$member_type_post_id = bp_member_type_post_by_type( $current_user_member_type );
					$include_group_type  = get_post_meta( $member_type_post_id, '_bp_member_type_enabled_group_type_create', true );
					if ( isset( $include_group_type ) && ! empty( $include_group_type ) && 'none' === $include_group_type[0] ) {
						$group_types = '';
					}
				}
			}
		}

		if ( $group_types ) {
			$group_type_field = array(
				'label'       => esc_html__( 'Group Type', 'buddyboss' ),
				'name'        => 'group-types',
				'description' => esc_html__( 'What type of group is this? (optional)', 'buddyboss' ),
				'field'       => 'select',
				'value'       => '',
				'options'     => array(),
			);

			$group_hierarchies['options'][] = array(
				'label'             => __( 'Select Group Type', 'buddyboss' ),
				'value'             => '',
				'description'       => '',
				'is_default_option' => '',
			);

			foreach ( $group_types as $type ) {

				if (
					function_exists( 'bp_restrict_group_creation' )
					&& function_exists( 'bp_member_type_enable_disable' )
					&& false === bp_restrict_group_creation()
					&& true === bp_member_type_enable_disable()
				) {

					$get_all_registered_member_types = bp_get_active_member_types();

					if ( isset( $get_all_registered_member_types ) && ! empty( $get_all_registered_member_types ) ) {

						$current_user_member_type = bp_get_member_type( bp_loggedin_user_id() );

						if ( '' !== $current_user_member_type ) {

							$member_type_post_id = bp_member_type_post_by_type( $current_user_member_type );
							$include_group_type  = get_post_meta( $member_type_post_id, '_bp_member_type_enabled_group_type_create', true );

							if ( isset( $include_group_type ) && ! empty( $include_group_type ) ) {
								if ( in_array( $type->name, $include_group_type, true ) ) {
									$group_type_field['options'][] = array(
										'label'       => $type->labels['singular_name'],
										'value'       => $type->name,
										'description' => '',
										'is_default_option' => ( ( ( true === bp_groups_has_group_type( $group_id, $type->name ) ) ? $type->name : '' ) === $type->name ),
									);
								}
							} else {
								$group_type_field['options'][] = array(
									'label'             => $type->labels['singular_name'],
									'value'             => $type->name,
									'description'       => '',
									'is_default_option' => ( ( ( true === bp_groups_has_group_type( $group_id, $type->name ) ) ? $type->name : '' ) === $type->name ),
								);
							}
						} else {
							$group_type_field['options'][] = array(
								'label'             => $type->labels['singular_name'],
								'value'             => $type->name,
								'description'       => '',
								'is_default_option' => ( ( ( true === bp_groups_has_group_type( $group_id, $type->name ) ) ? $type->name : '' ) === $type->name ),
							);
						}
					} else {
						$group_type_field['options'][] = array(
							'label'             => $type->labels['singular_name'],
							'value'             => $type->name,
							'description'       => '',
							'is_default_option' => ( ( ( true === bp_groups_has_group_type( $group_id, $type->name ) ) ? $type->name : '' ) === $type->name ),
						);
					}
				} else {
					$group_type_field['options'][] = array(
						'label'             => $type->labels['singular_name'],
						'value'             => $type->name,
						'description'       => '',
						'is_default_option' => ( ( ( true === bp_groups_has_group_type( $group_id, $type->name ) ) ? $type->name : '' ) === $type->name ),
					);
				}
			}

			$fields[] = $group_type_field;
		}
		// --Group Types.

		if ( function_exists( 'bp_enable_group_hierarchies' ) && bp_enable_group_hierarchies() ) {
			$current_parent_group_id = bp_get_parent_group_id();
			$possible_parent_groups  = bp_get_possible_parent_groups();

			$group_hierarchies = array(
				'label'       => esc_html__( 'Group Parent', 'buddyboss' ),
				'name'        => 'bp-groups-parent',
				'description' => esc_html__( 'Which group should be the parent of this group? (optional)', 'buddyboss' ),
				'field'       => 'select',
				'value'       => $current_parent_group_id,
				'options'     => array(),
			);

			if ( $possible_parent_groups ) {
				$group_hierarchies['options'][] = array(
					'label'             => __( 'Select Parent', 'buddyboss' ),
					'value'             => 0,
					'description'       => '',
					'is_default_option' => empty( $current_parent_group_id ) || 0 === $current_parent_group_id,
				);
				foreach ( $possible_parent_groups as $possible_parent_group ) {
					$group_hierarchies['options'][] = array(
						'label'             => $possible_parent_group->name,
						'value'             => $possible_parent_group->id,
						'description'       => '',
						'is_default_option' => ( $current_parent_group_id === $possible_parent_group->id ),
					);
				}
			}

			$fields[] = $group_hierarchies;
		}

		return apply_filters( 'bp_rest_group_settings', $fields, $group_id );

	}

	/**
	 * Update Group settings.
	 *
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return array
	 */
	protected function update_settings_fields( $request ) {
		$post_fields                        = $request->get_param( 'fields' );
		$group_id                           = $request->get_param( 'id' );
		$group                              = groups_get_group( $group_id );
		buddypress()->groups->current_group = $group;

		if ( empty( $post_fields ) ) {
			return array(
				'error'  => '',
				'notice' => '',
			);
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_status = apply_filters( 'groups_allowed_status', array( 'public', 'private', 'hidden' ) );
		$status         = ( array_key_exists( 'group-status', (array) $post_fields ) && ! empty( $post_fields['group-status'] ) ) ? $post_fields['group-status'] : ( ! bp_get_new_group_status() ? 'public' : bp_get_new_group_status() );

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_invite_status = apply_filters( 'groups_allowed_invite_status', array( 'members', 'mods', 'admins' ) );
		$invite_status         = ( array_key_exists( 'group-invite-status', (array) $post_fields ) && ! empty( $post_fields['group-invite-status'] ) ) ? $post_fields['group-invite-status'] : bp_group_get_invite_status( $group->id );

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_activity_feed_status = apply_filters( 'groups_allowed_activity_feed_status', array( 'members', 'mods', 'admins' ) );
		$activity_feed_status         = ( array_key_exists( 'group-activity-feed-status', (array) $post_fields ) && ! empty( $post_fields['group-activity-feed-status'] ) ) ? $post_fields['group-activity-feed-status'] : bp_group_get_activity_feed_status( $group->id );

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_media_status = apply_filters( 'groups_allowed_media_status', array( 'members', 'mods', 'admins' ) );
		$media_status         = ( array_key_exists( 'group-media-status', (array) $post_fields ) && ! empty( $post_fields['group-media-status'] ) ) ? $post_fields['group-media-status'] : bp_group_get_media_status( $group->id );

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_album_status = apply_filters( 'groups_allowed_album_status', array( 'members', 'mods', 'admins' ) );
		$album_status         = ( array_key_exists( 'group-album-status', (array) $post_fields ) && ! empty( $post_fields['group-album-status'] ) ) ? $post_fields['group-album-status'] : bp_group_get_album_status( $group->id );

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_message_status = apply_filters( 'groups_allowed_message_status', array( 'mods', 'admins', 'members' ) );
		$message_status         = ( array_key_exists( 'group-message-status', (array) $post_fields ) && ! empty( $post_fields['group-message-status'] ) ) ? $post_fields['group-message-status'] : bp_group_get_message_status( $group->id );

		/*
		 * Save group types.
		 *
		 * Ensure we keep types that have 'show_in_create_screen' set to false.
		 */
		$current_types = bp_groups_get_group_type( $group_id, false );
		$current_types = array_intersect( bp_groups_get_group_types( array( 'show_in_create_screen' => false ) ), (array) $current_types );
		if ( isset( $post_fields['group-types'] ) ) {
			$current_types = array_merge( $current_types, (array) $post_fields['group-types'] );

			// Set group types.
			bp_groups_set_group_type( $group_id, $current_types );

			// No group types checked, so this means we want to wipe out all group types.
		} else {
			/*
			 * Passing a blank string will wipe out all types for the group.
			 *
			 * Ensure we keep types that have 'show_in_create_screen' set to false.
			 */
			$current_types = empty( $current_types ) ? '' : $current_types;

			// Set group types.
			bp_groups_set_group_type( $group_id, $current_types );
		}

		$parent_id    = isset( $post_fields['bp-groups-parent'] ) && array_key_exists( 'bp-groups-parent', (array) $post_fields ) ? $post_fields['bp-groups-parent'] : '0';
		$enable_forum = ( isset( $group->enable_forum ) ? $group->enable_forum : false );

		$error  = '';
		$notice = '';

		if ( ! groups_edit_group_settings( $group_id, $enable_forum, $status, $invite_status, $activity_feed_status, $parent_id, $media_status, $album_status, $message_status ) ) {
			$error = __( 'There was an error updating group settings. Please try again.', 'buddyboss' );
		} else {
			$notice = __( 'Group settings were successfully updated.', 'buddyboss' );
		}

		return array(
			'error'  => $error,
			'notice' => $notice,
		);

	}
}

