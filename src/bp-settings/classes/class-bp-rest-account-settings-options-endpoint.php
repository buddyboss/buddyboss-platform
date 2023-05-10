<?php
/**
 * BP REST: BP_REST_Account_Settings_Options_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Account Settings endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Account_Settings_Options_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'account-settings/(?P<nav>[\w-]+)';
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
				'args'   => array(
					'nav' => array(
						'description'       => esc_html__( 'Navigation item slug.', 'buddyboss' ),
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
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
	 * Retrieve Account Settings options.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/account-settings/:nav Get Settings Options
	 * @apiName        GetBBAccountSettingsOptions
	 * @apiGroup       Account Settings
	 * @apiDescription Retrieve account setting options based on navigation tab.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String=general,notifications,profile,invites,export,delete-account} nav Navigation item slug.
	 */
	public function get_item( $request ) {

		$nav    = $request->get_param( 'nav' );
		$fields = array();
		switch ( $nav ) {
			case 'general':
				$fields = $this->get_general_fields();
				break;

			case 'notifications':
				$fields = $this->get_notifications_fields();
				break;

			case 'profile':
				$fields = $this->get_profile_fields();
				break;

			case 'invites':
				$fields = $this->get_invites_fields();
				break;

			case 'export':
				$fields = $this->get_export_fields();
				break;

			case 'delete-account':
				$fields = $this->get_delete_account_fields();
				break;
		}

		$fields = apply_filters( 'bp_rest_account_setting_fields', $fields, $nav );

		if ( empty( $fields ) ) {
			return new WP_Error(
				'bp_rest_invalid_setting_nav',
				esc_html__( 'Sorry, you are not allowed to see the account settings options.', 'buddyboss' ),
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
		 * Fires after account setting options are fetched via the REST API.
		 *
		 * @param array            $fields   Fetched Fields.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_account_settings_options_get_item', $fields, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to account settings options.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			esc_html__( 'Sorry, you are not allowed to see the account settings options.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$nav    = $request->get_param( 'nav' );

			if ( empty( $nav ) ) {
				return new WP_Error(
					'bp_rest_invalid_setting_nav',
					esc_html__( 'Sorry, you are not allowed to see the account settings options.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		/**
		 * Filter the account settings options `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_account_settings_options_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Update Account Settings options.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error | WP_REST_Response
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/account-settings/:nav Update Settings Options
	 * @apiName        UpdateBBAccountSettingsOptions
	 * @apiGroup       Account Settings
	 * @apiDescription Update account setting options based on navigation tab.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String=general,notifications,profile,invites,export,delete-account} nav Navigation item slug.
	 * @apiParam {Array} fields The list of fields to update with name and value of the field.
	 */
	public function update_item( $request ) {
		$nav     = $request->get_param( 'nav' );
		$fields  = array();
		$updated = array();

		switch ( $nav ) {
			case 'general':
				$updated = $this->update_general_fields( $request );
				$fields  = $this->get_general_fields();
				break;

			case 'notifications':
				$updated = $this->update_notifications_fields( $request );
				$fields  = $this->get_notifications_fields();
				break;

			case 'profile':
				$updated = $this->update_profile_fields( $request );
				$fields  = $this->get_profile_fields();
				break;

			case 'invites':
				$updated = $this->update_invites_fields( $request );
				$fields  = $this->get_invites_fields();
				break;

			case 'export':
				$updated = $this->update_export_fields( $request );
				$fields  = $this->get_export_fields();
				break;

			case 'delete-account':
				$updated = $this->update_delete_account_fields( $request );
				$fields  = $this->get_delete_account_fields();
				break;
		}

		$fields  = apply_filters( 'bp_rest_account_setting_update_fields', $fields, $nav );
		$updated = apply_filters( 'bp_rest_account_setting_update_message', $updated, $nav );

		$fields_update = $this->update_additional_fields_for_object( $nav, $request );
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
			'success' => ( empty( $updated['error'] ) ? esc_html__( 'Your settings has been successfully updated.', 'buddyboss' ) : false ),
			'notices' => ( isset( $updated['notice'] ) ? $updated['notice'] : false ),
			'data'    => $data,
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after account setting options are updated via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_account_settings_options_update_item', $response, $request );

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
			esc_html__( 'Sorry, you are not allowed to see the account settings options.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$nav    = $request->get_param( 'nav' );

			if ( empty( $nav ) ) {
				return new WP_Error(
					'bp_rest_invalid_setting_nav',
					esc_html__( 'Sorry, you are not allowed to update the account settings options.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		/**
		 * Filter the account settings options `update_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_account_settings_options_update_item_permissions_check', $retval, $request );
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
				'nav'    => array(
					'description'       => esc_html__( 'Navigation item slug.', 'buddyboss' ),
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => 'rest_validate_request_arg',
				),
				'fields' => array(
					'context'     => array( 'view', 'edit' ),
					'description' => esc_html__( 'The list of fields Objects to update with name and value of the field.', 'buddyboss' ),
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
		return apply_filters( "bp_rest_update_accounts_{$key}_query_arguments", $args, $method );
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
			'name'        => ( isset( $field['name'] ) && ! empty( $field['name'] ) ? $field['name'] : '' ),
			'label'       => ( isset( $field['label'] ) && ! empty( $field['label'] ) ? wp_specialchars_decode( $field['label'], ENT_QUOTES ) : '' ),
			'type'        => ( isset( $field['field'] ) && ! empty( $field['field'] ) ? $field['field'] : '' ),
			'value'       => ( isset( $field['value'] ) && ! empty( $field['value'] ) ? $field['value'] : '' ),
			'placeholder' => ( isset( $field['placeholder'] ) && ! empty( $field['placeholder'] ) ? $field['placeholder'] : '' ),
			'options'     => ( isset( $field['options'] ) && ! empty( $field['options'] ) ? $field['options'] : array() ),
			'headline'    => ( isset( $field['group_label'] ) && ! empty( $field['group_label'] ) ? wp_specialchars_decode( $field['group_label'], ENT_QUOTES ) : '' ),
			'subfields'   => ( isset( $field['subfields'] ) && ! empty( $field['subfields'] ) ? $field['subfields'] : array() ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		/**
		 * Filter a notification value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param object           $field    Field object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_account_setting_prepare_value', $response, $request, $field );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $nav_slug Navigation slug.
	 *
	 * @return array Links for the given group.
	 */
	protected function prepare_links( $nav_slug ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base;
		$links = array(
			'options' => array(
				'href' => rest_url( trailingslashit( $base ) . $nav_slug ),
			),
		);

		return $links;
	}

	/**
	 * Get the Account Settings schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_account_settings_options',
			'type'       => 'object',
			'properties' => array(
				'name'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => esc_html__( 'A unique name for the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'label'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => esc_html__( 'Label of the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'type'      => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => esc_html__( 'The type the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'value'     => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => esc_html__( 'The saved value for the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'options'   => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => esc_html__( 'Available options for the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
				),
				'headline'  => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => esc_html__( 'Headline text for the field.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'string',
				),
				'subfields' => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => esc_html__( 'Related sub fields.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'object',
				),
			),
		);

		/**
		 * Filters the Account Settings schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_account_settings_options_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for the Account Settings collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Remove the search argument.
		unset( $params['search'] );
		unset( $params['page'] );
		unset( $params['per_page'] );

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_account_settings_options_collection_params', $params );
	}

	/**
	 * Get Fields for the General "Login Information".
	 * - From: 'members/single/settings/general'
	 *
	 * @return array|mixed|void
	 */
	public function get_general_fields() {
		$fields = array();

		if ( ! is_super_admin() ) {
			$fields[] = array(
				'name'        => 'current_password',
				'label'       => __( 'Current Password <span>(required to update email or change current password)</span>', 'buddyboss' ),
				'field'       => 'password',
				'value'       => '',
				'placeholder' => esc_html__( 'Enter password', 'buddyboss' ),
				'options'     => array(),
				'group_label' => '',
			);
		}

		$fields[] = array(
			'name'        => 'account_email',
			'label'       => esc_html__( 'Account Email', 'buddyboss' ),
			'field'       => 'email',
			'value'       => esc_attr( bp_core_get_user_email( bp_loggedin_user_id() ) ),
			'placeholder' => esc_html__( 'Enter email', 'buddyboss' ),
			'options'     => array(),
			'group_label' => '',
		);

		$fields[] = array(
			'name'        => 'pass1',
			'label'       => esc_html__( 'Add Your New Password', 'buddyboss' ),
			'field'       => 'password',
			'placeholder' => esc_html__( 'Enter password', 'buddyboss' ),
			'value'       => '',
			'options'     => array(),
			'group_label' => '',
		);

		$fields[] = array(
			'name'        => 'pass2',
			'label'       => esc_html__( 'Repeat Your New Password', 'buddyboss' ),
			'field'       => 'password',
			'placeholder' => esc_html__( 'Enter password', 'buddyboss' ),
			'value'       => '',
			'options'     => array(),
			'group_label' => '',
		);

		$fields = apply_filters( 'bp_rest_account_settings_general', $fields );

		return $fields;
	}

	/**
	 * Get Fields for the Notifications "Email Preferences".
	 * - From: 'members/single/settings/notifications'
	 *
	 * @return array|mixed|void
	 */
	public function get_notifications_fields() {
		$fields = array();

		if (
			function_exists( 'bb_register_notification_preferences' ) &&
			function_exists( 'bb_enabled_legacy_email_preference' ) &&
			false === bb_enabled_legacy_email_preference()
		) {
			$notification_preferences   = bb_register_notification_preferences();
			$enabled_notification_types = bb_enable_notifications_options();
			$manual_notifications       = bb_manual_notification_options();
			$enabled_all_notification   = bp_get_option( 'bb_enabled_notification', array() );

			if ( ! empty( $enabled_notification_types ) && ! empty( $enabled_notification_types['fields'] ) ) {
				$fields_data = array(
					'name'        => '',
					'label'       => $enabled_notification_types['label'],
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => '',
					'subfields'   => array(),
				);

				foreach ( $enabled_notification_types['fields'] as $key => $label ) {
					if ( ! empty( $key ) && ! empty( $label ) ) {
						$fields_data['subfields'][] = array(
							'name'     => $key,
							'label'    => $label,
							'value'    => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), $key, true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), $key, true ) : 'yes' ),
							'field'    => 'checkbox',
							'disabled' => false,
							'options'  => array(
								'yes' => esc_html__( 'Yes', 'buddyboss' ),
								'no'  => esc_html__( 'No', 'buddyboss' ),
							),
						);
					}
				}

				$fields[] = $fields_data;
			}

			if ( ! empty( $manual_notifications ) && ! empty( $manual_notifications['fields'] ) ) {
				$fields_data = array(
					'name'        => '',
					'label'       => $manual_notifications['label'],
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => '',
					'subfields'   => array(
						array(
							'name'     => '',
							'label'    => esc_html__( 'Email', 'buddyboss' ),
							'value'    => '',
							'field'    => '',
							'disabled' => false,
							'options'  => array(),
						),
					),
				);

				foreach ( $manual_notifications['fields'] as $key => $label ) {
					$disabled = false;
					if ( 'notification_web_push' === $key ) {
						$disabled = 'no' === bp_get_user_meta( bp_loggedin_user_id(), 'enable_notification_web', true );
					} elseif ( 'notification_app_push' === $key ) {
						$disabled = 'no' === bp_get_user_meta( bp_loggedin_user_id(), 'enable_notification_app', true );
					}

					if ( ! empty( $label ) && ! empty( $key ) ) {
						$fields_data['subfields'][] = array(
							'name'     => $key,
							'label'    => $label,
							'value'    => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), $key, true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), $key, true ) : 'yes' ),
							'field'    => 'checkbox',
							'disabled' => $disabled,
							'options'  => array(
								'yes' => esc_html__( 'Yes', 'buddyboss' ),
								'no'  => esc_html__( 'No', 'buddyboss' ),
							),
						);
					} elseif ( ! empty( $key ) ) {
						if ( 'notification_web_push' === $key ) {
							$label = esc_html__( 'Web', 'buddyboss' );
						} elseif ( 'notification_app_push' === $key ) {
							$label = esc_html__( 'App', 'buddyboss' );
						}
						$fields_data['subfields'][] = array(
							'name'     => '',
							'label'    => $label,
							'value'    => '',
							'field'    => '',
							'disabled' => false,
							'options'  => array(),
						);
					}
				}

				$fields[] = $fields_data;
			}

			if ( ! empty( $notification_preferences ) ) {

				foreach ( $notification_preferences as $group => $group_data ) {

					if ( ! empty( $group_data['fields'] ) ) {
						$group_data['fields'] = array_filter(
							array_map(
								function ( $fields ) {
									if (
										(
											isset( $fields['notification_read_only'], $fields['default'] ) &&
											true === (bool) $fields['notification_read_only'] &&
											'yes' === (string) $fields['default']
										) ||
										(
											! isset( $fields['notification_read_only'] ) ||
											false === (bool) $fields['notification_read_only']
										)
									) {
										return $fields;
									}
								},
								$group_data['fields']
							)
						);
					}

					if ( empty( $group_data['fields'] ) ) {
						continue;
					}

					if ( ! empty( $group_data['label'] ) ) {
						$fields[] = array(
							'name'        => '',
							'label'       => '',
							'field'       => '',
							'value'       => '',
							'options'     => array(),
							'group_label' => $group_data['label'],
						);
					}

					if ( ! empty( $group_data['fields'] ) ) {
						$default_enabled_notifications = array_column( $group_data['fields'], 'default', 'key' );
						$enabled_notification          = array_filter( array_combine( array_keys( $enabled_all_notification ), array_column( $enabled_all_notification, 'main' ) ) );
						$enabled_notification          = array_merge( $default_enabled_notifications, $enabled_notification );

						$group_data['fields'] = array_filter(
							$group_data['fields'],
							function ( $var ) use ( $enabled_notification ) {
								return ( key_exists( $var['key'], $enabled_notification ) && 'yes' === $enabled_notification[ $var['key'] ] );
							}
						);
					}

					if ( ! empty( $group_data['fields'] ) ) {
						foreach ( $group_data['fields'] as $field ) {

							$fields_data = array(
								'name'        => $field['key'],
								'label'       => $field['label'],
								'field'       => 'radio',
								'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), $field['key'], true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), $field['key'], true ) : 'yes' ),
								'options'     => array(
									'yes' => esc_html__( 'Yes', 'buddyboss' ),
									'no'  => esc_html__( 'No', 'buddyboss' ),
								),
								'group_label' => '',
							);

							$options = bb_notification_preferences_types( $field, bp_loggedin_user_id() );
							foreach ( $options as $key => $v ) {
								$is_render   = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_type_render', $v['is_render'], $field['key'], $key );
								$is_disabled = apply_filters( 'bb_is_' . $field['key'] . '_' . $key . '_preference_type_disabled', $v['disabled'], $field['key'], $key );
								$name        = ( 'email' === $key ) ? $field['key'] : $field['key'] . '_' . $key;
								if ( $is_render ) {
									$fields_data['subfields'][] = array(
										'name'     => esc_attr( $name ),
										'label'    => esc_html( $v['label'] ),
										'value'    => $v['is_checked'],
										'field'    => 'checkbox',
										'disabled' => $is_disabled,
										'options'  => array(
											'yes' => esc_html__( 'Yes', 'buddyboss' ),
											'no'  => esc_html__( 'No', 'buddyboss' ),
										),
									);

								} else {
									$fields_data['subfields'][] = array(
										'name'    => '',
										'label'   => esc_html( $v['label'] ),
										'value'   => '',
										'field'   => '',
										'options' => array(),
									);
								}
							}

							$fields[] = $fields_data;
						}
					}
				}
			}
		} else {
			if ( bp_is_active( 'activity' ) ) {
				$fields_activity[] = array(
					'name'        => '',
					'label'       => '',
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => esc_html__( 'Activity Feed', 'buddyboss' ),
				);

				if ( bp_activity_do_mentions() ) {
					$fields_activity[] = array(
						'name'        => 'notification_activity_new_mention',
						'label'       => sprintf(
						/* translators: %s: users mention name. */
							__( 'A member mentions you in an update using "@%s"', 'buddyboss' ),
							bp_activity_get_user_mentionname( bp_loggedin_user_id() )
						),
						'field'       => 'radio',
						'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_activity_new_mention', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_activity_new_mention', true ) : 'yes' ),
						'options'     => array(
							'yes' => esc_html__( 'Yes', 'buddyboss' ),
							'no'  => esc_html__( 'No', 'buddyboss' ),
						),
						'group_label' => '',
					);
				}

				$fields_activity[] = array(
					'name'        => 'notification_activity_new_reply',
					'label'       => __( "A member replies to an update or comment you've posted", 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_activity_new_reply', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_activity_new_reply', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_activity = apply_filters( 'bp_rest_account_settings_notifications_activity', $fields_activity );
				$fields          = array_merge( $fields, $fields_activity );
			}

			if ( bp_is_active( 'messages' ) ) {
				$fields_messages[] = array(
					'name'        => '',
					'label'       => '',
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => esc_html__( 'Messages', 'buddyboss' ),
				);

				$fields_messages[] = array(
					'name'        => 'notification_messages_new_message',
					'label'       => esc_html__( 'A member sends you a new message', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_messages_new_message', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_messages_new_message', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_messages = apply_filters( 'bp_rest_account_settings_notifications_messages', $fields_messages );
				$fields          = array_merge( $fields, $fields_messages );
			}

			if ( bp_is_active( 'groups' ) ) {
				$fields_groups[] = array(
					'name'        => '',
					'label'       => '',
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => esc_html__( 'Social Groups', 'buddyboss' ),
				);

				$fields_groups[] = array(
					'name'        => 'notification_groups_invite',
					'label'       => esc_html__( 'A member invites you to join a group', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_invite', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_invite', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_groups[] = array(
					'name'        => 'notification_groups_group_updated',
					'label'       => esc_html__( 'Group information is updated', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_group_updated', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_group_updated', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_groups[] = array(
					'name'        => 'notification_groups_admin_promotion',
					'label'       => esc_html__( 'You are promoted to a group organizer or moderator', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_admin_promotion', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_admin_promotion', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_groups[] = array(
					'name'        => 'notification_groups_membership_request',
					'label'       => esc_html__( 'A member requests to join a private group you organize', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_membership_request', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_groups_membership_request', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_groups[] = array(
					'name'        => 'notification_membership_request_completed',
					'label'       => esc_html__( 'Your request to join a group has been approved or denied', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_membership_request_completed', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_membership_request_completed', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				if ( function_exists( 'bp_disable_group_messages' ) && true === bp_disable_group_messages() ) {
					$fields_groups[] = array(
						'name'        => 'notification_group_messages_new_message',
						'label'       => esc_html__( 'A group sends you a new message', 'buddyboss' ),
						'field'       => 'radio',
						'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_group_messages_new_message', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_group_messages_new_message', true ) : 'yes' ),
						'options'     => array(
							'yes' => esc_html__( 'Yes', 'buddyboss' ),
							'no'  => esc_html__( 'No', 'buddyboss' ),
						),
						'group_label' => '',
					);
				}

				$fields_groups = apply_filters( 'bp_rest_account_settings_notifications_groups', $fields_groups );
				$fields        = array_merge( $fields, $fields_groups );
			}

			if ( bp_is_active( 'forums' ) && function_exists( 'bbp_is_subscriptions_active' ) && true === bbp_is_subscriptions_active() ) {
				$fields_forums[] = array(
					'name'        => '',
					'label'       => '',
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => esc_html__( 'Forums', 'buddyboss' ),
				);

				$fields_forums[] = array(
					'name'        => 'notification_forums_following_reply',
					'label'       => esc_html__( 'A member replies to a discussion you are subscribed to', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_forums_following_reply', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_forums_following_reply', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_forums[] = array(
					'name'        => 'notification_forums_following_topic',
					'label'       => esc_html__( 'A member creates discussion in a forum you are subscribed to', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_forums_following_topic', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_forums_following_topic', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_forums = apply_filters( 'bp_rest_account_settings_notifications_forums', $fields_forums );
				$fields        = array_merge( $fields, $fields_forums );
			}

			if ( bp_is_active( 'friends' ) ) {
				$fields_friends[] = array(
					'name'        => '',
					'label'       => '',
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => esc_html__( 'Connections', 'buddyboss' ),
				);

				$fields_friends[] = array(
					'name'        => 'notification_friends_friendship_request',
					'label'       => esc_html__( 'A member invites you to connect', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_friends_friendship_request', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_friends_friendship_request', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_friends[] = array(
					'name'        => 'notification_friends_friendship_accepted',
					'label'       => esc_html__( 'A member accepts your connection request', 'buddyboss' ),
					'field'       => 'radio',
					'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_friends_friendship_accepted', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_friends_friendship_accepted', true ) : 'yes' ),
					'options'     => array(
						'yes' => esc_html__( 'Yes', 'buddyboss' ),
						'no'  => esc_html__( 'No', 'buddyboss' ),
					),
					'group_label' => '',
				);

				$fields_friends = apply_filters( 'bp_rest_account_settings_notifications_friends', $fields_friends );
				$fields         = array_merge( $fields, $fields_friends );
			}
		}

		$fields = apply_filters( 'bp_rest_account_settings_notifications', $fields );

		return $fields;
	}

	/**
	 * Get Fields for the Profile "Privacy".
	 * - From: 'members/single/settings/profile'
	 *
	 * @return array|mixed|void
	 */
	public function get_profile_fields() {
		$fields       = array();
		$field_groups = bp_xprofile_get_groups(
			array(
				'fetch_fields'           => true,
				'user_id'                => bp_loggedin_user_id(),
				'fetch_field_data'       => true,
				'fetch_visibility_level' => true,
			)
		);

		if ( ! empty( $field_groups ) ) {
			foreach ( $field_groups as $group ) {
				$fields[] = array(
					'name'        => '',
					'label'       => '',
					'field'       => '',
					'value'       => '',
					'options'     => array(),
					'group_label' => $group->name,
				);

				if ( isset( $group->fields ) && ! empty( $group->fields ) ) {
					foreach ( $group->fields as $field ) {

						// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
						if ( function_exists( 'bp_core_hide_display_name_field' ) && true === bp_core_hide_display_name_field( $field->id ) ) {
							continue;
						}

						$fields[] = array(
							'name'        => 'field_' . $field->id,
							'label'       => $field->name,
							'field'       => ( ! empty( $this->bp_rest_get_xprofile_field_visibility( $field ) ) && 'allowed' === $this->bp_rest_get_xprofile_field_visibility( $field ) ) ? 'select' : '',
							'value'       => xprofile_get_field_visibility_level( $field->id, bp_loggedin_user_id() ),
							'options'     => array_column( bp_xprofile_get_visibility_levels(), 'label', 'id' ),
							'group_label' => '',
						);
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Get Fields for the Invites "Group Invites".
	 * - From: 'members/single/settings/group-invites'
	 *
	 * @return array|mixed|void
	 */
	public function get_invites_fields() {
		$fields = array(
			array(
				'name'        => 'account-group-invites-preferences',
				'label'       => esc_html__( 'Restrict Group invites to members who are connected.', 'buddyboss' ),
				'field'       => 'checkbox',
				'value'       => bp_nouveau_groups_get_group_invites_setting( bp_loggedin_user_id() ),
				'options'     => array(),
				'group_label' => '',
			),
		);

		$fields = apply_filters( 'bp_rest_account_settings_invites', $fields );

		return $fields;
	}

	/**
	 * Get Fields for the Export "Export Data".
	 * - From: 'members/single/settings/export-data'
	 *
	 * @return array|mixed|void
	 */
	public function get_export_fields() {
		$fields = array(
			array(
				'name'        => 'member-data-export-submit',
				'label'       => esc_html__( 'Request Data Export', 'buddyboss' ),
				'field'       => 'button',
				'value'       => '',
				'options'     => array(),
				'group_label' => '',
			),
		);

		$fields = apply_filters( 'bp_rest_account_settings_export', $fields );

		return $fields;
	}

	/**
	 * Get Fields for the Delete Account "Delete Account".
	 * - From: 'members/single/settings/delete-account'
	 *
	 * @return array|mixed|void
	 */
	public function get_delete_account_fields() {
		$fields = array(
			array(
				'name'        => 'delete-account-understand',
				'label'       => esc_html__( 'I understand the consequences.', 'buddyboss' ),
				'field'       => 'checkbox',
				'value'       => '',
				'options'     => array(),
				'group_label' => '',
			),
			array(
				'name'        => 'member-delete-account',
				'label'       => esc_html__( 'Delete Account', 'buddyboss' ),
				'field'       => 'button',
				'value'       => '',
				'options'     => array(),
				'group_label' => '',
			),
		);

		$fields = apply_filters( 'bp_rest_account_settings_delete_account', $fields );

		return $fields;
	}

	/**
	 * Update general fields.
	 * - from bp-settings\actions\general.php.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|bool|void
	 */
	public function update_general_fields( $request ) {
		$post_fields = $request->get_param( 'fields' );

		// Define local defaults.
		$bp            = buddypress(); // The instance.
		$email_error   = false;
		$pass_error    = false;
		$pass_changed  = false;        // true if the user changes their password .
		$email_changed = false;        // true if the user changes their email.
		$feedback      = array();      // array of strings for feedback.

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		// The core userdata of the user who is currently being displayed.
		$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );

		// Fetch the full name displayed user.
		$bp->displayed_user->fullname = isset( $bp->displayed_user->userdata->display_name ) ? $bp->displayed_user->userdata->display_name : '';

		// The domain for the user currently being displayed.
		$bp->displayed_user->domain = bp_core_get_user_domain( bp_displayed_user_id() );

		if (
			( is_super_admin() )
			|| (
				isset( $post_fields['current_password'] )
				&& ! empty( $post_fields['current_password'] )
				&& wp_check_password(
					$post_fields['current_password'],
					$bp->displayed_user->userdata->user_pass,
					bp_displayed_user_id()
				)
			)
		) {

			$update_user = get_userdata( bp_displayed_user_id() );

			/* Email Change Attempt ******************************************/

			if ( ! empty( $post_fields['account_email'] ) ) {

				// What is missing from the profile page vs signup.
				// let's double check the goodies.
				$user_email     = sanitize_email( esc_html( trim( $post_fields['account_email'] ) ) );
				$old_user_email = $bp->displayed_user->userdata->user_email;

				// User is changing email address.
				if ( $old_user_email !== $user_email ) {

					// Run some tests on the email address.
					$email_checks = bp_core_validate_email_address( $user_email );

					if ( true !== $email_checks ) {
						if ( isset( $email_checks['invalid'] ) ) {
							$email_error = 'invalid';
						}

						if ( isset( $email_checks['domain_banned'] ) || isset( $email_checks['domain_not_allowed'] ) ) {
							$email_error = 'blocked';
						}

						if ( isset( $email_checks['in_use'] ) ) {
							$email_error = 'taken';
						}
					}

					// Store a hash to enable email validation.
					if ( false === $email_error ) {
						$hash = wp_generate_password( 32, false );

						$pending_email = array(
							'hash'     => $hash,
							'newemail' => $user_email,
						);

						bp_update_user_meta( bp_displayed_user_id(), 'pending_email_change', $pending_email );
						$verify_link = bp_displayed_user_domain() . bp_get_settings_slug() . '/?verify_email_change=' . $hash;

						// Send the verification email.
						$args = array(
							'tokens' => array(
								'displayname'    => bp_core_get_user_displayname( bp_displayed_user_id() ),
								'old-user.email' => $old_user_email,
								'user.email'     => $user_email,
								'verify.url'     => esc_url( $verify_link ),
							),
						);
						bp_send_email( 'settings-verify-email-change', bp_displayed_user_id(), $args );

						// We mark that the change has taken place so as to ensure a.
						// success message, even though verification is still required.
						$post_fields['account_email'] = $update_user->user_email;
						$email_changed                = true;
					}

					// No change.
				} else {
					$email_error = false;
				}

				// Email address cannot be empty.
			} else {
				$email_error = 'empty';
			}

			/* Password Change Attempt ***************************************/
			if (
				! empty( $post_fields['pass1'] )
				&& ! empty( $post_fields['pass2'] )
			) {

				if (
					( $post_fields['pass1'] === $post_fields['pass2'] )
					&& ! strpos( ' ' . wp_unslash( $post_fields['pass1'] ), '\\' )
				) {

					// Password change attempt is successful.
					if (
						( ! empty( $post_fields['current_password'] ) && $post_fields['current_password'] !== $post_fields['pass1'] )
						|| is_super_admin()
					) {
						$update_user->user_pass = $post_fields['pass1'];
						$pass_changed           = true;

						// The new password is the same as the current password.
					} else {
						$pass_error = 'same';
					}

					// Password change attempt was unsuccessful.
				} else {
					$pass_error = 'mismatch';
				}

				// Both password fields were empty.
			} elseif (
				empty( $post_fields['pass1'] )
				&& empty( $post_fields['pass2'] )
			) {
				$pass_error = false;

				// One of the password boxes was left empty.
			} elseif (
				( empty( $post_fields['pass1'] ) && ! empty( $post_fields['pass2'] ) )
				|| ( ! empty( $post_fields['pass1'] ) && empty( $post_fields['pass2'] ) )
			) {
				$pass_error = 'empty';
			}

			// The structure of the $update_user object changed in WP 3.3, but wp_update_user() still expects the old format.
			if ( isset( $update_user->data ) && is_object( $update_user->data ) ) {
				$update_user = $update_user->data;
				$update_user = get_object_vars( $update_user );

				// Unset the password field to prevent it from emptying out the user's user_pass field in the database.
				// @see wp_update_user().
				if ( false === $pass_changed ) {
					unset( $update_user['user_pass'] );
				}
			}

			// Clear cached data, so that the changed settings take effect on the current page load.
			clean_user_cache( bp_displayed_user_id() );

			if (
				function_exists( 'bb_enabled_legacy_email_preference' ) &&
				! bb_enabled_legacy_email_preference()
			) {
				add_filter( 'send_password_change_email', '__return_false' );
			}

			if (
				( false === $email_error )
				&& ( false === $pass_error )
				&& ( wp_update_user( $update_user ) )
			) {
				$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );
			}

			if (
				function_exists( 'bb_enabled_legacy_email_preference' ) &&
				! bb_enabled_legacy_email_preference()
			) {
				remove_filter( 'send_password_change_email', '__return_false' );
			}

			// Password Error.
		} else {
			$pass_error = 'invalid';
		}

		// Email feedback.
		switch ( $email_error ) {
			case 'invalid':
				$feedback['email_invalid'] = esc_html__( 'That email address is invalid. Check the formatting and try again.', 'buddyboss' );
				break;
			case 'blocked':
				$feedback['email_blocked'] = esc_html__( 'That email address is currently unavailable for use.', 'buddyboss' );
				break;
			case 'taken':
				$feedback['email_taken'] = esc_html__( 'That email address is already taken.', 'buddyboss' );
				break;
			case 'empty':
				$feedback['email_empty'] = esc_html__( 'Email address cannot be empty.', 'buddyboss' );
				break;
			case false:
				// No change.
				break;
		}

		// Password feedback.
		switch ( $pass_error ) {
			case 'invalid':
				$feedback['pass_error'] = esc_html__( 'Your current password is invalid.', 'buddyboss' );
				break;
			case 'mismatch':
				$feedback['pass_mismatch'] = esc_html__( 'The new password fields did not match.', 'buddyboss' );
				break;
			case 'empty':
				$feedback['pass_empty'] = esc_html__( 'One of the password fields was empty.', 'buddyboss' );
				break;
			case 'same':
				$feedback['pass_same'] = esc_html__( 'The new password must be different from the current password.', 'buddyboss' );
				break;
			case false:
				// No change.
				break;
		}

		// Some kind of errors occurred.
		if (
			( ( false === $email_error ) || ( false === $pass_error ) )
			&& ( ( true !== $pass_changed ) && ( true !== $email_changed ) )
		) {
			$feedback['nochange'] = esc_html__( 'No changes were made to your account.', 'buddyboss' );
		} else {

			// If the user is changing their password, send them a confirmation email.
			if (
				! bb_enabled_legacy_email_preference() &&
				bb_get_modern_notification_admin_settings_is_enabled( 'bb_account_password', 'members' ) &&
				true === bb_is_notification_enabled( bp_displayed_user_id(), 'bb_account_password' )
			) {

				$unsubscribe_args = array(
					'user_id'           => (int) bp_displayed_user_id(),
					'notification_type' => 'settings-password-changed',
				);

				$args = array(
					'tokens' => array(
						'reset.url'   => esc_url( wp_lostpassword_url() ),
						'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
					),
				);

				// Send notification email.
				bp_send_email( 'settings-password-changed', (int) bp_displayed_user_id(), $args );
			}

			if (
				! bb_enabled_legacy_email_preference() &&
				bb_get_modern_notification_admin_settings_is_enabled( 'bb_account_password', 'members' ) &&
				bp_is_active( 'notifications' )
			) {

				// Send a notification to the user.
				bp_notifications_add_notification(
					array(
						'user_id'           => bp_displayed_user_id(),
						'item_id'           => bp_displayed_user_id(),
						'secondary_item_id' => bp_displayed_user_id(),
						'component_name'    => buddypress()->members->id,
						'component_action'  => 'bb_account_password',
						'date_notified'     => bp_core_current_time(),
						'allow_duplicate'   => true,
						'is_new'            => 1,
					)
				);

			}
		}

		$notice = $this->bp_rest_settings_pending_email_notice();

		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		if ( empty( $feedback ) ) {
			return array(
				'error'  => false,
				'notice' => $notice,
			);
		} else {
			return array(
				'error'  => $feedback,
				'notice' => $notice,
			);
		}

		return false;
	}

	/**
	 * Update notication fields.
	 * - from bp-settings\actions\general.php.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|bool|void
	 */
	public function update_notifications_fields( $request ) {
		$post_fields = $request->get_param( 'fields' );

		$_POST                  = array();
		$_POST['notifications'] = ( ! empty( $post_fields ) ? $post_fields : array() );

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		// phpcs:disable
		if ( isset( $_POST['notifications'] ) && ! empty( $_POST['notifications'] ) ) {
			bp_settings_update_notification_settings( bp_displayed_user_id(), (array) $_POST['notifications'] );
		}
		// phpcs:enable

		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		/**
		 * Fires after the notification settings have been saved, and before redirect.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_core_notification_settings_after_save' );

		return array(
			'error'  => false,
			'notice' => false,
		);
	}

	/**
	 * Update profile fields.
	 * - from bp_xprofile_action_settings().
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|bool|void
	 */
	public function update_profile_fields( $request ) {
		$post_fields = $request->get_param( 'fields' );

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		if ( ! empty( $post_fields ) ) {
			// Save the visibility settings.
			foreach ( $post_fields as $field => $value ) {

				$field_id = (int) str_replace( 'field_', '', $field );

				if ( ! empty( $field_id ) && is_int( $field_id ) && ! empty( $value ) ) {
					$visibility_level = $value;
					xprofile_set_field_visibility_level( $field_id, bp_displayed_user_id(), $visibility_level );
				}
			}
		}

		/**
		 * Fires after saving xprofile field visibilities.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_xprofile_settings_after_save' );

		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		return array(
			'error'  => false,
			'notice' => false,
		);
	}

	/**
	 * Update Invites fields "Group Invites".
	 * - from bp_nouveau_groups_screen_invites_restriction().
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|bool|void
	 */
	public function update_invites_fields( $request ) {
		$post_fields = $request->get_param( 'fields' );

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		// Nonce check.
		if ( empty( $post_fields['account-group-invites-preferences'] ) ) {
			bp_delete_user_meta( bp_displayed_user_id(), '_bp_nouveau_restrict_invites_to_friends' );
		} else {
			bp_update_user_meta( bp_displayed_user_id(), '_bp_nouveau_restrict_invites_to_friends', (int) $post_fields['account-group-invites-preferences'] );
		}

		do_action( 'bp_rest_invites_settings_after_save', $post_fields );

		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		return array(
			'error'  => false,
			'notice' => false,
		);
	}

	/**
	 * Update Export fields "Export Data".
	 * - from bp_settings_action_export().
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|bool|void
	 */
	public function update_export_fields( $request ) {
		$post_fields = $request->get_param( 'fields' );

		$error  = false;
		$notice = false;

		add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		if ( isset( $post_fields['member-data-export-submit'] ) ) {

			$user_id = bp_loggedin_user_id();

			$user       = get_userdata( $user_id );
			$request_id = wp_create_user_request( $user->data->user_email, 'export_personal_data' );

			if ( is_wp_error( $request_id ) ) {
				$error = $request_id->get_error_message();

			} elseif ( ! $request_id ) {

				$error = esc_html__( 'Unable to initiate the data export request.', 'buddyboss' );
			}

			if ( empty( $error ) ) {
				wp_send_user_request( $request_id );
				$notice = esc_html__( 'Please check your email to confirm the data export request.', 'buddyboss' );
			}
		}

		remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		return array(
			'error'  => $error,
			'notice' => $notice,
		);
	}

	/**
	 * Delete Account "Export Data".
	 * - from bp_settings_action_delete_account().
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|bool|void
	 */
	public function update_delete_account_fields( $request ) {
		$post_fields = $request->get_param( 'fields' );

		$error  = false;
		$notice = false;

		if (
			isset( $post_fields['delete-account-understand'] )
			&& ! empty( $post_fields['delete-account-understand'] )
			&& isset( $post_fields['member-delete-account'] )
			&& ! empty( $post_fields['member-delete-account'] )
		) {

			$bp = buddypress(); // The instance.
			add_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

			// The core userdata of the user who is currently being displayed.
			$bp->displayed_user->userdata = bp_core_get_core_userdata( bp_displayed_user_id() );

			// Fetch the full name displayed user.
			$bp->displayed_user->fullname = isset( $bp->displayed_user->userdata->display_name ) ? $bp->displayed_user->userdata->display_name : '';

			// The domain for the user currently being displayed.
			$bp->displayed_user->domain = bp_core_get_user_domain( bp_displayed_user_id() );

			if ( bp_disable_account_deletion() && ! bp_current_user_can( 'delete_users' ) ) {
				$error = esc_html__( 'Sorry, You can not able to delete the account.', 'buddyboss' );
			} elseif ( bp_core_delete_account( bp_displayed_user_id() ) ) {
				$username = bp_get_displayed_user_fullname();
				/* translators: %s: User's full name. */
				$notice = sprintf( esc_html__( '%s was successfully deleted.', 'buddyboss' ), $username );
			}

			remove_filter( 'bp_displayed_user_id', array( $this, 'bp_rest_get_displayed_user' ), 999 );

		} else {
			$error = esc_html__( 'No changes were made to your account.', 'buddyboss' );
		}

		return array(
			'error'  => $error,
			'notice' => $notice,
		);
	}

	/**
	 * Set current and display user with current user.
	 *
	 * @param int $user_id The user id.
	 *
	 * @return int
	 */
	public function bp_rest_get_displayed_user( $user_id ) {
		return get_current_user_id();
	}

	/**
	 * Add the 'pending email change' message to the settings page.
	 * -- from: bp_settings_pending_email_notice().
	 *
	 * @return void|string
	 */
	protected function bp_rest_settings_pending_email_notice() {
		$pending_email = bp_get_user_meta( bp_displayed_user_id(), 'pending_email_change', true );

		if ( empty( $pending_email['newemail'] ) ) {
			return;
		}

		if ( bp_get_displayed_user_email() === $pending_email['newemail'] ) {
			return;
		}

		return sprintf(
		/* translators: 1: New email. 2: Current email. */
			__( 'There is a pending change of your email address to %1$s. Check your email (%2$s) for the verification link.', 'buddyboss' ),
			'<strong>' . esc_html( $pending_email['newemail'] ) . '</strong>',
			'<strong>' . esc_html( bp_get_displayed_user_email() ) . '</strong>'
		);
	}

	/**
	 * Check current user can edit the visibility or not.
	 *
	 * @param BP_XProfile_Field $field_object Field Object.
	 *
	 * @return string
	 */
	public function bp_rest_get_xprofile_field_visibility( $field_object ) {
		global $field;

		// Get the field id into for user check.
		$GLOBALS['profile_template']              = new stdClass();
		$GLOBALS['profile_template']->in_the_loop = true;

		// Setup current user id into global.
		$field = $field_object;

		return (
		! bp_current_user_can( 'bp_xprofile_change_field_visibility' )
			? 'disabled'
			: (
		(
			! empty( $field->__get( 'allow_custom_visibility' ) )
			&& 'allowed' === $field->__get( 'allow_custom_visibility' )
			)
			? $field->__get( 'allow_custom_visibility' )
			: 'disabled'
		)
		);
	}
}
