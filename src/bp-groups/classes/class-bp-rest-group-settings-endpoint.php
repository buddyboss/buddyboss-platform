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
	 * Navigation.
	 *
	 * @var array Setting Navigation items.
	 */
	protected $nav;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace       = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base       = buddypress()->groups->id;
		$this->groups_endpoint = new BP_REST_Groups_Endpoint();
		$this->nav             = array( 'edit-details', 'group-settings' );
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {

		if ( bp_is_active( 'forums' ) && function_exists( 'bbp_is_group_forums_active' ) && bbp_is_group_forums_active() ) {
			$this->nav[] = 'forum';
		}

		if ( function_exists( 'bp_ld_sync' ) ) {
			$va = bp_ld_sync( 'settings' )->get( 'buddypress.enabled', true );
			if ( '1' === $va ) {
				$this->nav[] = 'courses';
			}
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/settings',
			array(
				'args'   => array(
					'id'  => array(
						'description' => __( 'A unique numeric ID for the Group.', 'buddyboss' ),
						'type'        => 'integer',
					),
					'nav' => array(
						'description'       => __( 'Navigation item slug.', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'enum'              => $this->nav,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
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
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Group.
	 * @apiParam {String=edit-details,group-settings,forum,courses} nav Navigation item slug.
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

		$nav    = $request->get_param( 'nav' );
		$fields = array();
		switch ( $nav ) {
			case 'edit-details':
				$fields = $this->get_detais_fields( $group->id );
				break;

			case 'group-settings':
				$fields = $this->get_settings_fields( $group->id );
				break;

			case 'forum':
				$fields = $this->get_forum_fields( $group->id );
				break;

			case 'courses':
				$fields = $this->get_courses_fields( $group->id );
				break;
		}

		$fields = apply_filters( 'bp_rest_groups_setting_fields', $fields, $group->id, $nav );

		if ( is_wp_error( $fields ) ) {
			return $fields;
		}

		if ( empty( $fields ) ) {
			return new WP_Error(
				'bp_rest_invalid_group_setting_nav',
				__( 'Sorry, you are not allowed to see the group settings options.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

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
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to see the group settings.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_is_active( 'groups' ) ) {
			$group  = $this->groups_endpoint->get_group_object( $request );
			$retval = true;
			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! $this->groups_endpoint->can_see( $group ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to see the group settings.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
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
		$nav   = $request->get_param( 'nav' );

		$fields  = array();
		$updated = array();

		switch ( $nav ) {
			case 'edit-details':
				$updated = $this->update_details_fields( $request );
				$fields  = $this->get_detais_fields( $group->id );
				break;

			case 'group-settings':
				$updated = $this->update_settings_fields( $request );
				$fields  = $this->get_settings_fields( $group->id );
				break;

			case 'forum':
				$updated = $this->update_forum_fields( $request );
				$fields  = $this->get_forum_fields( $group->id );
				break;

			case 'courses':
				$updated = $this->update_courses_fields( $request );
				$fields  = $this->get_courses_fields( $group->id );
				break;
		}

		$fields  = apply_filters( 'bp_rest_group_setting_update_fields', $fields, $group->id, $nav );
		$updated = apply_filters( 'bp_rest_group_setting_update_message', $updated, $group->id, $nav );

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
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to update the group settings.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_is_active( 'groups' ) ) {
			$retval = true;
			$group  = $this->groups_endpoint->get_group_object( $request );
			if ( empty( $group->id ) ) {
				$retval = new WP_Error(
					'bp_rest_group_invalid_id',
					__( 'Invalid group ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
				// If group author does not match logged_in user, block update.
			} elseif ( ! $this->groups_endpoint->can_user_delete_or_update( $group ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to update the group settings.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
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
				'nav'    => array(
					'description'       => __( 'Navigation item slug.', 'buddyboss' ),
					'type'              => 'string',
					'required'          => true,
					'enum'              => $this->nav,
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
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
	 * Get Group Details fields.
	 *
	 * @param integer $group_id Group ID.
	 *
	 * @return mixed|void
	 */
	protected function get_detais_fields( $group_id ) {
		$fields                             = array();
		$group                              = groups_get_group( $group_id );
		buddypress()->groups->current_group = $group;

		$fields[] = array(
			'label'       => esc_html__( 'Group Name (required)', 'buddyboss' ),
			'name'        => 'group-name',
			'description' => '',
			'field'       => 'text',
			'value'       => ( function_exists( 'bp_get_group_name_editable' ) ? bp_get_group_name_editable( $group ) : bp_get_group_name( $group ) ),
			'options'     => array(),
		);

		$fields[] = array(
			'label'       => esc_html__( 'Group Description', 'buddyboss' ),
			'name'        => 'group-desc',
			'description' => '',
			'field'       => 'textarea',
			'value'       => bp_get_group_description_editable( $group ),
			'options'     => array(),
		);

		if (
			(
				function_exists( 'bb_enabled_legacy_email_preference' ) &&
				(
					(
						! bb_enabled_legacy_email_preference() &&
						bb_get_modern_notification_admin_settings_is_enabled( 'bb_groups_details_updated', 'groups' )
					) ||
					bb_enabled_legacy_email_preference()
				)
			) ||
			! function_exists( 'bb_enabled_legacy_email_preference' )
		) {
			$checked = 0;
			$label   = esc_html__( 'Notify group members of these changes via email', 'buddyboss' );

			if (
				function_exists( 'bb_enabled_legacy_email_preference' ) &&
				! bb_enabled_legacy_email_preference() &&
				bb_get_modern_notification_admin_settings_is_enabled( 'bb_groups_details_updated', 'groups' )
			) {
				$label   = esc_html__( 'Notify group members of these changes', 'buddyboss' );
				$checked = 1;
			}

			$fields[] = array(
				'label'       => '',
				'name'        => 'group-notify-members',
				'description' => '',
				'field'       => 'checkbox',
				'value'       => '',
				'options'     => array(
					array(
						'label'             => $label,
						'value'             => 1,
						'description'       => '',
						'is_default_option' => $checked,
					),
				),
			);
		}

		return apply_filters( 'bp_rest_group_details', $fields, $group_id );
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
					'is_default_option' => 'hidden' === bp_get_new_group_status(),
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
				'description' => esc_html__( 'Which members of this group are allowed to upload photos?', 'buddyboss' ),
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
				'description' => esc_html__( 'Which members of this group are allowed to create albums?', 'buddyboss' ),
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

		if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) {
			$fields[] = array(
				'label'       => esc_html__( 'Group Documents', 'buddyboss' ),
				'name'        => 'group-document-status',
				'description' => esc_html__( 'Which members of this group are allowed to upload documents?', 'buddyboss' ),
				'field'       => 'radio',
				'value'       => bp_group_get_document_status( $group_id ),
				'options'     => array(
					array(
						'label'             => esc_html__( 'All group members', 'buddyboss' ),
						'value'             => 'members',
						'description'       => '',
						'is_default_option' => 'members' === bp_group_get_document_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers and Moderators only', 'buddyboss' ),
						'value'             => 'mods',
						'description'       => '',
						'is_default_option' => 'mods' === bp_group_get_document_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers only', 'buddyboss' ),
						'value'             => 'admins',
						'description'       => '',
						'is_default_option' => 'admins' === bp_group_get_document_status( $group_id ),
					),
				),
			);
		}

		if ( bp_is_active( 'media' ) && bp_is_group_video_support_enabled() ) {
			$fields[] = array(
				'label'       => esc_html__( 'Group Videos', 'buddyboss' ),
				'name'        => 'group-video-status',
				'description' => esc_html__( 'Which members of this group are allowed to upload videos?', 'buddyboss' ),
				'field'       => 'radio',
				'value'       => bp_group_get_video_status( $group_id ),
				'options'     => array(
					array(
						'label'             => esc_html__( 'All group members', 'buddyboss' ),
						'value'             => 'members',
						'description'       => '',
						'is_default_option' => 'members' === bp_group_get_video_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers and Moderators only', 'buddyboss' ),
						'value'             => 'mods',
						'description'       => '',
						'is_default_option' => 'mods' === bp_group_get_video_status( $group_id ),
					),
					array(
						'label'             => esc_html__( 'Organizers only', 'buddyboss' ),
						'value'             => 'admins',
						'description'       => '',
						'is_default_option' => 'admins' === bp_group_get_video_status( $group_id ),
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
			$current_types    = bp_groups_get_group_type( $group_id, false );
			$group_type_field = array(
				'label'       => esc_html__( 'Group Type', 'buddyboss' ),
				'name'        => 'group-types',
				'description' => esc_html__( 'What type of group is this? (optional)', 'buddyboss' ),
				'field'       => 'select',
				'value'       => ( ! empty( $current_types ) ? current( $current_types ) : '' ),
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
	 * Details Group settings.
	 *
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return array
	 */
	protected function update_details_fields( $request ) {
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

		$group_name           = ( array_key_exists( 'group-name', (array) $post_fields ) && ! empty( $post_fields['group-name'] ) ) ? $post_fields['group-name'] : bp_get_group_name( $group );
		$group_desc           = ( array_key_exists( 'group-desc', (array) $post_fields ) && ! empty( $post_fields['group-desc'] ) ) ? $post_fields['group-desc'] : bp_get_group_description_editable( $group );
		$group_notify_members = (bool) ( array_key_exists( 'group-notify-members', (array) $post_fields ) && ! empty( $post_fields['group-notify-members'] ) ) ? $post_fields['group-notify-members'] : false;

		$error  = '';
		$notice = '';

		if ( ! groups_edit_base_group_details(
			array(
				'group_id'       => $group_id,
				'name'           => $group_name,
				'slug'           => null,
				'description'    => $group_desc,
				'notify_members' => $group_notify_members,
				'parent_id'      => false,
			)
		) ) {
			$error = __( 'There was an error updating group details. Please try again.', 'buddyboss' );
		} else {
			$notice = __( 'Group details were successfully updated.', 'buddyboss' );
		}

		/**
		 * Fires before the redirect if a group details has been edited and saved.
		 *
		 * @param int $group_id ID of the group that was edited.
		 */
		do_action( 'groups_group_details_edited', $group_id );

		return array(
			'error'  => $error,
			'notice' => $notice,
		);

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

		$error          = '';
		$notice         = '';
		$validate_error = false;

		if ( ! in_array( $status, $allowed_status, true ) ) {
			$validate_error = true;
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_invite_status = bb_groups_get_settings_status( 'invite' );
		$invite_status         = ( array_key_exists( 'group-invite-status', (array) $post_fields ) && ! empty( $post_fields['group-invite-status'] ) ) ? $post_fields['group-invite-status'] : bp_group_get_invite_status( $group->id );

		if ( ! in_array( $invite_status, $allowed_invite_status, true ) ) {
			$validate_error = true;
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_activity_feed_status = bb_groups_get_settings_status( 'activity_feed' );
		$activity_feed_status         = ( array_key_exists( 'group-activity-feed-status', (array) $post_fields ) && ! empty( $post_fields['group-activity-feed-status'] ) ) ? $post_fields['group-activity-feed-status'] : bp_group_get_activity_feed_status( $group->id );

		if ( ! in_array( $activity_feed_status, $allowed_activity_feed_status, true ) ) {
			$validate_error = true;
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_media_status = bb_groups_get_settings_status( 'media' );
		$media_status         = ( array_key_exists( 'group-media-status', (array) $post_fields ) && ! empty( $post_fields['group-media-status'] ) ) ? $post_fields['group-media-status'] : bp_group_get_media_status( $group->id );

		if ( ! in_array( $media_status, $allowed_media_status, true ) ) {
			$validate_error = true;
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_document_status = bb_groups_get_settings_status( 'document' );
		$document_status         = ( array_key_exists( 'group-document-status', (array) $post_fields ) && ! empty( $post_fields['group-document-status'] ) ) ? $post_fields['group-document-status'] : bp_group_get_document_status( $group->id );

		if ( ! in_array( $document_status, $allowed_document_status, true ) ) {
			$validate_error = true;
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_album_status = bb_groups_get_settings_status( 'album' );
		$album_status         = ( array_key_exists( 'group-album-status', (array) $post_fields ) && ! empty( $post_fields['group-album-status'] ) ) ? $post_fields['group-album-status'] : bp_group_get_album_status( $group->id );

		if ( ! in_array( $album_status, $allowed_album_status, true ) ) {
			$validate_error = true;
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_video_status = bb_groups_get_settings_status( 'video' );
		$video_status         = ( array_key_exists( 'group-video-status', (array) $post_fields ) && ! empty( $post_fields['group-video-status'] ) ) ? $post_fields['group-video-status'] : bp_group_get_video_status( $group->id );

		if ( ! in_array( $video_status, $allowed_video_status, true ) ) {
			$validate_error = true;
		}

		// Checked against a whitelist for security.
		/** This filter is documented in bp-groups/bp-groups-admin.php */
		$allowed_message_status = bb_groups_get_settings_status( 'message' );
		$message_status         = ( array_key_exists( 'group-message-status', (array) $post_fields ) && ! empty( $post_fields['group-message-status'] ) ) ? $post_fields['group-message-status'] : bp_group_get_message_status( $group->id );

		if ( ! in_array( $message_status, $allowed_message_status, true ) ) {
			$validate_error = true;
		}

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

		$parent_id    = isset( $post_fields['bp-groups-parent'] ) && array_key_exists( 'bp-groups-parent', (array) $post_fields ) ? $post_fields['bp-groups-parent'] : bp_get_parent_group_id( $group->id );
		$enable_forum = ( isset( $group->enable_forum ) ? $group->enable_forum : false );

		if ( true === $validate_error || ! groups_edit_group_settings( $group_id, $enable_forum, $status, $invite_status, $activity_feed_status, $parent_id, $media_status, $document_status, $video_status, $album_status, $message_status ) ) {
			$error = __( 'There was an error updating group settings. Please try again.', 'buddyboss' );
		} else {
			$notice = __( 'Group settings were successfully updated.', 'buddyboss' );
		}

		return array(
			'error'  => $error,
			'notice' => $notice,
		);

	}

	/**
	 * Get Group forum Settings.
	 *
	 * @param integer $group_id Group ID.
	 *
	 * @return mixed|void
	 */
	protected function get_forum_fields( $group_id ) {
		$fields                             = array();
		buddypress()->groups->current_group = groups_get_group( $group_id );

		if ( ! bp_is_active( 'forums' ) || ! function_exists( 'bbp_is_group_forums_active' ) || ! bbp_is_group_forums_active() ) {
			return new WP_Error(
				'bp_rest_invalid_group_setting_nav',
				__( 'Sorry, you are not allowed to see the forum group settings options.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$forum_id  = 0;
		$forum_ids = bbp_get_group_forum_ids( $group_id );

		// Get the first forum ID.
		if ( ! empty( $forum_ids ) ) {
			$forum_id = (int) is_array( $forum_ids ) ? $forum_ids[0] : $forum_ids;
		}

		$checked = bp_get_new_group_enable_forum() || bp_group_is_forum_enabled( $group_id );

		$fields[] = array(
			'label'       => esc_html__( 'Group Forum Settings', 'buddyboss' ),
			'name'        => '',
			'description' => esc_html__( 'Create a discussion forum to allow members of this group to communicate in a structured, bulletin-board style fashion.', 'buddyboss' ),
			'field'       => 'heading',
			'value'       => '',
			'options'     => array(),
		);

		$fields[] = array(
			'label'       => esc_html__( 'Yes. I want this group to have a discussion forum.', 'buddyboss' ),
			'name'        => 'bbp-edit-group-forum',
			'description' => esc_html__( 'Saying no will not delete existing forum content.', 'buddyboss' ),
			'field'       => 'checkbox',
			'value'       => $checked,
			'options'     => array(),
		);

		return apply_filters( 'bp_rest_group_settings_forum', $fields, $group_id );

	}

	/**
	 * Update Group Forum settings.
	 *
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return array|WP_Error
	 */
	protected function update_forum_fields( $request ) {
		$post_fields                        = $request->get_param( 'fields' );
		$group_id                           = $request->get_param( 'id' );
		$group                              = groups_get_group( $group_id );
		buddypress()->groups->current_group = $group;

		if ( ! bp_is_active( 'forums' ) || ! function_exists( 'bbp_is_group_forums_active' ) || ! bbp_is_group_forums_active() || ! class_exists( 'BBP_Forums_Group_Extension' ) ) {
			return new WP_Error(
				'bp_rest_invalid_group_setting_nav',
				__( 'Sorry, you are not allowed to update the forum group settings options.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( empty( $post_fields ) ) {
			return array(
				'error'  => '',
				'notice' => '',
			);
		}

		$edit_forum = ( array_key_exists( 'bbp-edit-group-forum', (array) $post_fields ) && ! empty( $post_fields['bbp-edit-group-forum'] ) ) ? true : false;
		$forum_id   = 0;

		$group_forum_extention = new BBP_Forums_Group_Extension();

		$forum_ids = array_values( bbp_get_group_forum_ids( $group_id ) );

		// Check for the last associated values if no forum set from setting.
		if ( $edit_forum && empty( $forum_ids ) ) {

			$last_forum_id = (int) groups_get_groupmeta( $group_id, 'last_forum_id' );

			if ( ! empty( $last_forum_id ) ) {

				$forum_ids = (array) $last_forum_id;
				$forum_id  = $last_forum_id;

				// Flag to remove the last associations meta.
				$restored_associations = true;

				// Check if same values associated in group and forum.
				$last_group_ids = get_post_meta( $forum_id, '_last_bbp_group_ids', true );
				$last_group_ids = ! empty( $last_group_ids ) ? array_filter( $last_group_ids ) : array();

				if ( in_array( $group_id, $last_group_ids ) ) {

					// Look for forum can be associated.
					$valid_forum = $group_forum_extention->forum_can_associate_with_group( $forum_id, $group_id, false );

					// Look for forum if exits.
					$forum_exist = bbp_get_forum( $forum_id );
					if ( empty( $valid_forum ) || empty( $forum_exist ) ) {
						$restored_associations = false;
					}
				} else {
					$restored_associations = false;
				}

				if ( false === $restored_associations ) {
					$forum_ids = array();
					$forum_id  = 0;
				}
			}
		}

		// Normalize group forum relationships now.
		if ( ! empty( $forum_ids ) ) {

			// Loop through forums, and make sure they exist.
			foreach ( $forum_ids as $forum_id ) {

				// Look for forum.
				$forum = bbp_get_forum( $forum_id );

				// No forum exists, so break the relationship.
				if ( empty( $forum ) ) {
					$group_forum_extention->remove_forum(
						array(
							'forum_id' => $forum_id,
							'group_id' => $group_id,
						)
					);
					unset( $forum_ids[ $forum_id ] );
				}
			}

			// No support for multiple forums yet.
			$forum_id = (int) ( is_array( $forum_ids ) ? $forum_ids[0] : $forum_ids );
		}

		// Update the group ID and forum ID relationships.
		bbp_update_forum_group_ids( $forum_id, (array) $group_id );
		bbp_update_group_forum_ids( $group_id, (array) $forum_ids );

		// Update the group forum setting.
		$group = $group_forum_extention->toggle_group_forum( $group_id, $edit_forum, $forum_id );

		if ( true === $edit_forum ) {
			// Delete last associations forum id.
			if ( ! empty( $last_forum_id ) ) {
				delete_post_meta( $last_forum_id, '_last_bbp_group_ids' );
			}

			// Update associations forum id.
			if ( ! empty( $forum_id ) ) {
				delete_post_meta( $forum_id, '_last_bbp_group_ids' );
			}
			groups_delete_groupmeta( $group_id, 'last_forum_id' );
		}

		// Create a new forum.
		if ( empty( $forum_id ) && ( true === $edit_forum ) ) {

			// Set the default forum status.
			switch ( $group->status ) {
				case 'hidden':
					$status = bbp_get_hidden_status_id();
					break;
				case 'private':
					$status = bbp_get_private_status_id();
					break;
				case 'public':
				default:
					$status = bbp_get_public_status_id();
					break;
			}

			// Create the initial forum.
			$forum_id = bbp_insert_forum(
				array(
					'post_parent'  => bbp_get_group_forums_root_id(),
					'post_title'   => $group->name,
					'post_content' => $group->description,
					'post_status'  => $status,
				)
			);

			// Setup forum args with forum ID.
			$new_forum_args = array( 'forum_id' => $forum_id );

			// If in admin, also include the group ID.
			if ( ! empty( $group_id ) ) {
				$new_forum_args['group_id'] = $group_id;
			}

			// Run the BP-specific functions for new groups.
			$group_forum_extention->new_forum( $new_forum_args );
		}

		$notice = __( 'Group settings were successfully updated.', 'buddyboss' );

		return array(
			'error'  => '',
			'notice' => $notice,
		);
	}

	/**
	 * Get Group course Settings.
	 *
	 * @param integer $group_id Group ID.
	 *
	 * @return mixed|void
	 */
	protected function get_courses_fields( $group_id ) {
		$fields                             = array();
		buddypress()->groups->current_group = groups_get_group( $group_id );

		if ( ! function_exists( 'bp_ld_sync' ) || '1' !== bp_ld_sync( 'settings' )->get( 'buddypress.enabled', true ) ) {
			return new WP_Error(
				'bp_rest_invalid_group_setting_nav',
				__( 'Sorry, you are not allowed to see the courses group settings options.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$has_ld_group = bp_ld_sync( 'buddypress' )->sync->generator( $group_id )->hasLdGroup();

		$fields[] = array(
			'label'       => esc_html__( 'Group Courses Settings', 'buddyboss' ),
			'name'        => '',
			'description' => esc_html__( 'Create and associate to a LearnDash group, allowing courses and reports to be managed within the group.', 'buddyboss' ),
			'field'       => 'heading',
			'value'       => '',
			'options'     => array(),
		);

		$fields[] = array(
			'label'       => esc_html__( 'Yes. I want this group to sync with a LearnDash group.', 'buddyboss' ),
			'name'        => 'bp-ld-sync-enable',
			'description' => '',
			'field'       => 'checkbox',
			'value'       => $has_ld_group,
			'options'     => array(),
		);

		return apply_filters( 'bp_rest_group_settings_courses', $fields, $group_id );
	}

	/**
	 * Update Group Courses settings.
	 *
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return array
	 */
	protected function update_courses_fields( $request ) {
		$post_fields                        = $request->get_param( 'fields' );
		$group_id                           = $request->get_param( 'id' );
		$group                              = groups_get_group( $group_id );
		buddypress()->groups->current_group = $group;

		if ( ! function_exists( 'bp_ld_sync' ) || '1' !== bp_ld_sync( 'settings' )->get( 'buddypress.enabled', true ) ) {
			return new WP_Error(
				'bp_rest_invalid_group_setting_nav',
				__( 'Sorry, you are not allowed to update the courses group settings options.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		if ( empty( $post_fields ) ) {
			return array(
				'error'  => '',
				'notice' => '',
			);
		}

		$generator = bp_ld_sync( 'buddypress' )->sync->generator( $group_id );

		if ( array_key_exists( 'bp-ld-sync-enable', (array) $post_fields ) && empty( $post_fields['bp-ld-sync-enable'] ) ) {
			$generator->desyncFromLearndash();
		} elseif ( array_key_exists( 'bp-ld-sync-enable', (array) $post_fields ) && ! empty( $post_fields['bp-ld-sync-enable'] ) ) {
			$generator->associateToLearndash()->syncBpAdmins();
		}

		$notice = __( 'Group settings were successfully updated.', 'buddyboss' );

		return array(
			'error'  => '',
			'notice' => $notice,
		);
	}

	/**
	 * Disabled dropdown options for forum.
	 *
	 * @param object $forum    Forum post data.
	 * @param int    $forum_id Selected forum id.
	 *
	 * @uses bbp_get_forum_group_ids() Get forum group id.
	 *
	 * @return bool
	 */
	protected function is_option_disabled( $forum, $forum_id ) {
		if ( ! bp_is_active( 'forums' ) ) {
			return false;
		}

		if ( $forum->ID === $forum_id ) {
			return false;
		}

		if ( ! empty( $forum->post_parent ) ) {
			return true;
		}

		$group_ids = bbp_get_forum_group_ids( $forum->ID );

		if ( ! empty( $group_ids ) ) {
			return true;
		}

		if ( bbp_is_forum_category( $forum->ID ) ) {
			return true;
		}

		return false;
	}

}

