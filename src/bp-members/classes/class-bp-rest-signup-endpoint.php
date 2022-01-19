<?php
/**
 * BP REST: BP_REST_Signup_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Signup endpoints.
 *
 * Use /signup
 * Use /signup/{id}
 * Use /signup/activate/{id}
 *
 * @since 0.1.0
 */
class BP_REST_Signup_Endpoint extends WP_REST_Controller {

	/**
	 * Variable to store the password fields data.
	 *
	 * @var array of password fields.
	 */
	protected $default_password_fields;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'signup';

		$this->default_password_fields = array(
			'signup_password'         => array(
				'label'          => __( 'Password', 'buddyboss' ),
				'required'       => true,
				'value'          => '',
				'attribute_type' => 'password',
				'type'           => 'password',
				'class'          => 'password-entry',
			),
			'signup_password_confirm' => array(
				'label'          => __( 'Confirm Password', 'buddyboss' ),
				'required'       => true,
				'value'          => '',
				'attribute_type' => 'password',
				'type'           => 'password',
				'class'          => 'password-entry-confirm',
			),
		);
	}

	/**
	 * Register the component routes.
	 *
	 * @since 0.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/form',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'signup_form_items' ),
					'permission_callback' => array( $this, 'signup_form_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
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
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Identifier for the signup. Can be a signup ID, an email address, or a user_login.', 'buddyboss' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);

		// Register the activate route.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate/(?P<activation_key>[\w-]+)',
			array(
				'args'   => array(
					'activation_key' => array(
						'description' => __( 'Activation key of the signup.', 'buddyboss' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'activate_item' ),
					'permission_callback' => array( $this, 'activate_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'edit' ) ),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Retrieve Signup Form Fields.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/signup/form Signup Form
	 * @apiName        GetBBSignupFormFields
	 * @apiGroup       Signups
	 * @apiDescription Retrieve Signup Form Fields.
	 * @apiVersion     1.0.0
	 * @apiPermission  WithoutLoggedInUser
	 */
	public function signup_form_items( $request ) {
		$fields = array();

		/* Account detail fields */
		$account_fields = bp_nouveau_get_signup_fields( 'account_details' );

		if ( ! empty( $account_fields ) ) {
			foreach ( $account_fields as $k => $field ) {
				if ( array_key_exists( $k, $this->default_password_fields ) ) {
					$field = $this->default_password_fields[ $k ];
				}

				$fields[] = array(
					'id'          => $k,
					'label'       => $field['label'],
					'description' => '',
					'type'        => $field['type'],
					'required'    => $field['required'],
					'options'     => array(),
					'member_type' => '',
				);
			}
		}

		/* xProfile fields */
		if ( bp_is_active( 'xprofile' ) ) {
			$signup_group_id = $this->bp_rest_xprofile_base_group_id();
			$field_group     = bp_xprofile_get_groups(
				array(
					'profile_group_id' => $signup_group_id,
					'fetch_fields'     => true,
				)
			);

			if ( ! empty( $field_group ) ) {
				$field_group     = current( $field_group );
				$fields_endpoint = new BP_REST_XProfile_Fields_Endpoint();
				if ( ! empty( $field_group->fields ) ) {
					foreach ( $field_group->fields as $field ) {

						/**
						 * Added support for display name format support from platform.
						 */
						// Get the current display settings from BuddyBoss > Settings > Profiles > Display Name Format.
						if ( function_exists( 'bp_core_hide_display_name_field' ) && true === bp_core_hide_display_name_field( $field->id ) ) {
							continue;
						}

						if ( function_exists( 'bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
							if ( function_exists( 'bp_get_xprofile_member_type_field_id' ) && bp_get_xprofile_member_type_field_id() === $field->id ) {
								continue;
							}
						}
						/**
						 * --Added support for display name format support from platform.
						 */

						$field    = $fields_endpoint->assemble_response_data( $field, $request );
						$fields[] = array(
							'id'          => 'field_' . $field['id'],
							'label'       => ( ! empty( $field['alternate_name'] ) ? $field['alternate_name'] : $field['name'] ),
							'description' => $field['description']['rendered'],
							'type'        => $field['type'],
							'required'    => $field['is_required'],
							'options'     => $field['options'],
							'member_type' => bp_xprofile_get_meta( $field['id'], 'field', 'member_type', false ),
						);
					}
				}
			}
		}

		/* xProfile fields */
		$blog_fields = bp_nouveau_get_signup_fields( 'blog_details' );
		if ( ! empty( $blog_fields ) && bp_get_blog_signup_allowed() ) {
			if ( array_key_exists( 'signup_blog_privacy_public', $blog_fields ) ) {
				unset( $blog_fields['signup_blog_privacy_public'] );
			}
			if ( array_key_exists( 'signup_blog_privacy_private', $blog_fields ) ) {
				unset( $blog_fields['signup_blog_privacy_private'] );
			}
			$fields[] = array(
				'id'          => 'signup_with_blog',
				'label'       => __( "Yes, I'd like to create a new site", 'buddyboss' ),
				'description' => '',
				'type'        => 'checkbox',
				'required'    => '',
				'options'     => array(),
			);

			$not_required = array( 'signup_blog_url', 'signup_blog_title' );

			foreach ( $blog_fields as $k => $field ) {
				$fields[] = array(
					'id'          => $k,
					'label'       => $field['label'],
					'description' => '',
					'type'        => $field['type'],
					'required'    => ( ! in_array( $k, $not_required, true ) ? $field['required'] : false ),
					'options'     => array(),
				);
			}

			$fields[] = array(
				'id'          => 'signup_blog_privacy',
				'label'       => __( 'I would like my site to appear in search engines, and in public listings around this network.', 'buddyboss' ),
				'description' => '',
				'type'        => 'radio',
				'required'    => true,
				'options'     => array(
					array(
						'id'                => '',
						'type'              => 'option',
						'name'              => __( 'Yes', 'buddyboss' ),
						'value'             => 'public',
						'is_default_option' => true,
					),
					array(
						'id'                => '',
						'type'              => 'option',
						'name'              => __( 'No', 'buddyboss' ),
						'value'             => 'private',
						'is_default_option' => false,
					),
				),
			);
		}

		/* Legal agreement field */
		$legal_agreement_field = function_exists( 'bb_register_legal_agreement' ) ? bb_register_legal_agreement() : false;

		if ( $legal_agreement_field ) {
			$page_ids = bp_core_get_directory_page_ids();
			$terms    = ! empty( $page_ids['terms'] ) ? $page_ids['terms'] : false;
			$privacy  = ! empty( $page_ids['privacy'] ) ? $page_ids['privacy'] : (int) get_option( 'wp_page_for_privacy_policy' );

			$headline = '';
			if ( ! empty( $terms ) && ! empty( $privacy ) ) {
				$headline = sprintf(
					/* translators: 1. Term agreement page. 2. Privacy page. */
					__( 'I agree to the %1$s and %2$s.', 'buddyboss' ),
					'<a href="' . esc_url( get_permalink( $terms ) ) . '">' . get_the_title( $terms ) . '</a>',
					'<a href="' . esc_url( get_permalink( $privacy ) ) . '">' . get_the_title( $privacy ) . '</a>'
				);
			} elseif ( ! empty( $terms ) && empty( $privacy ) ) {
				$headline = sprintf(
					/* translators: Term agreement page. */
					__( 'I agree to the %s.', 'buddyboss' ),
					'<a href="' . esc_url( get_permalink( $terms ) ) . '">' . get_the_title( $terms ) . '</a>'
				);
			} elseif ( empty( $terms ) && ! empty( $privacy ) ) {
				$headline = sprintf(
					/* translators: Privacy page. */
					__( 'I agree to the %s.', 'buddyboss' ),
					'<a href="' . esc_url( get_permalink( $privacy ) ) . '">' . get_the_title( $privacy ) . '</a>'
				);
			}

			$fields[] = array(
				'id'          => 'legal_agreement',
				'label'       => $headline,
				'description' => '',
				'type'        => 'checkbox',
				'required'    => true,
				'options'     => array(),
				'member_type' => '',
			);
		}

		$response = rest_ensure_response( $fields );

		/**
		 * Fires after a list of signup fields is fetched via the REST API.
		 *
		 * @param array            $fields   Fetched Form fields.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_signup_form_items', $fields, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to view the signup form fields.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function signup_form_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not able to view the register form fields.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( ! is_user_logged_in() ) {
			$retval = true;
		}

		/**
		 * Filter the signup `signup_form_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_form_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve signups.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/signup Signups
	 * @apiName        GetBBSignups
	 * @apiGroup       Signups
	 * @apiDescription Retrieve signups
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} [include] Ensure result set includes specific IDs.
	 * @apiParam {String=asc,desc} [order] Order sort attribute ascending or descending.
	 * @apiParam {String} [orderby=signup_id] Order by a specific parameter.
	 * @apiParam {String} [user_login] Specific user login to return.
	 * @apiParam {Number} [number] Total number of signups to return.
	 * @apiParam {Number} [offset] 'Offset the result set by a specific number of items.
	 */
	public function get_items( $request ) {
		$args = array(
			'include'    => $request['include'],
			'order'      => $request['order'],
			'orderby'    => $request['orderby'],
			'user_login' => $request['user_login'],
			'number'     => $request['number'],
			'offset'     => $request['offset'],
		);

		if ( empty( $request['include'] ) ) {
			$args['include'] = false;
		}

		/**
		 * Filter the query arguments for the request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		$args = apply_filters( 'bp_rest_signup_get_items_query_args', $args, $request );

		// Actually, query it.
		$signups = BP_Signup::get( $args );

		$retval = array();
		foreach ( $signups['signups'] as $signup ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $signup, $request )
			);
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $signups['total'], $args['number'] );

		/**
		 * Fires after a list of signups is fetched via the REST API.
		 *
		 * @param array            $signups  Fetched signups.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_signup_get_items', $signups, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to signup items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() && bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the signup `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve single signup.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/signup/:id Signup
	 * @apiName        GetBBSignups
	 * @apiGroup       Signups
	 * @apiDescription Retrieve signup
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} id Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 */
	public function get_item( $request ) {
		// Get signup.
		$signup = $this->get_signup_object( $request['id'] );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $signup, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires before a signup is retrieved via the REST API.
		 *
		 * @param BP_Signup        $signup   The signup object.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_signup_get_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get a signup.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool
	 * @since 0.1.0
	 */
	public function get_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$signup = $this->get_signup_object( $request['id'] );

			if ( empty( $signup ) ) {
				$retval = new WP_Error(
					'bp_rest_invalid_id',
					__( 'Invalid signup id.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! bp_current_user_can( 'bp_moderate' ) ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not authorized to perform this action.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the signup `get_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create signup.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/signup Create signup
	 * @apiName        CreateBBSignups
	 * @apiGroup       Signups
	 * @apiDescription Create signup
	 * @apiVersion     1.0.0
	 * @apiPermission  WithoutLoggedInUser
	 * @apiParam {String} signup_email New user email address.
	 * @apiParam {String} [signup_email_confirm] New user confirm email address.
	 * @apiParam {String} signup_password New user account password.
	 * @apiParam {String} [signup_password_confirm] New user confirm account password.
	 */
	public function create_item( $request ) {
		$bp = buddypress();

		$request->set_param( 'context', 'edit' );

		$form_fields     = $this->signup_form_items( $request );
		$form_fields_all = $form_fields->get_data();
		$param           = $request->get_params();

		$posted_data = array();
		$date_fields = array();
		if ( ! empty( $form_fields_all ) ) {
			$form_fields_with_type = array_column( $form_fields_all, 'type', 'id' );
			$form_fields           = array_column( $form_fields_all, 'id' );
			if ( in_array( 'datebox', $form_fields_with_type, true ) ) {
				$key           = array_search( 'datebox', $form_fields_with_type, true );
				$form_fields[] = $key;
				$date_fields[] = $key;
				$param[ $key ] = '';
				$form_fields[] = $key . '_day';
				$form_fields[] = $key . '_month';
				$form_fields[] = $key . '_year';
			}
			$form_fields = array_flip( $form_fields );
			$posted_data = array_intersect_key( $param, $form_fields );
		}

		if ( empty( $posted_data ) ) {
			return new WP_Error(
				'bp_rest_signup_cannot_create',
				__( 'Cannot create new signup.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		// verification for phpcs.
		wp_verify_nonce( wp_create_nonce( 'rest_signup' ), 'rest_signup' );

		$_POST = array();
		$_POST = $posted_data;

		$user_name  = (
			function_exists( 'bp_get_signup_username_value' )
			? bp_get_signup_username_value()
			: (
				isset( $_POST['signup_username'] )
				? sanitize_text_field( wp_unslash( $_POST['signup_username'] ) )
				: ''
			)
		);
		$user_email = (
			function_exists( 'bp_get_signup_email_value' )
			? bp_get_signup_email_value()
			: (
				isset( $_POST['signup_email'] )
				? sanitize_text_field( wp_unslash( $_POST['signup_email'] ) )
				: ''
			)
		);

		// Check the base account details for problems.
		$account_details = bp_core_validate_user_signup( $user_name, $user_email );

		$email_opt    = function_exists( 'bp_register_confirm_email' ) && true === bp_register_confirm_email() ? true : false;
		$password_opt = function_exists( 'bp_register_confirm_password' ) ? bp_register_confirm_password() : true;

		// If there are errors with account details, set them for display.
		if ( ! empty( $account_details['errors']->errors['user_name'] ) ) {
			$bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];
		}

		if ( ! empty( $account_details['errors']->errors['user_email'] ) ) {
			$bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];
		}

		// Check that both password fields are filled in.
		if ( isset( $_POST['signup_password'] ) && empty( $_POST['signup_password'] ) ) {
			$bp->signup->errors['signup_password'] = __( 'Please make sure to enter your password.', 'buddyboss' );
		}

		// if email opt enabled.
		if ( true === $email_opt ) {

			// Check that both password fields are filled in.
			if ( empty( $_POST['signup_email'] ) || empty( $_POST['signup_email_confirm'] ) ) {
				$bp->signup->errors['signup_email'] = __( 'Please make sure to enter your email twice.', 'buddyboss' );
			}

			// Check that the passwords match.
			if (
				( ! empty( $_POST['signup_email'] ) && ! empty( $_POST['signup_email_confirm'] ) )
				&& $_POST['signup_email'] !== $_POST['signup_email_confirm']
			) {
				$bp->signup->errors['signup_email'] = __( 'The emails entered do not match.', 'buddyboss' );
			}
		}

		// if password opt enabled.
		if ( true === $password_opt ) {

			// Check that both password fields are filled in.
			if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) ) {
				$bp->signup->errors['signup_password'] = __( 'Please make sure to enter your password twice.', 'buddyboss' );
			}

			// Check that the passwords match.
			if (
				( ! empty( $_POST['signup_password'] ) && ! empty( $_POST['signup_password_confirm'] ) )
				&& $_POST['signup_password'] !== $_POST['signup_password_confirm']
			) {
				$bp->signup->errors['signup_password'] = __( 'The passwords entered do not match.', 'buddyboss' );
			}
		}

		// Adding error message for the legal agreement checkbox.
		if ( function_exists( 'bb_register_legal_agreement' ) && true === bb_register_legal_agreement() && empty( $_POST['legal_agreement'] ) ) {
			$bp->signup->errors['legal_agreement'] = __( 'This is a required field.', 'buddyboss' );
		}

		$bp->signup->username = $user_name;
		$bp->signup->email    = $user_email;

		// Now we've checked account details, we can check profile information.
		if ( bp_is_active( 'xprofile' ) ) {

			$xprofile_fields = array_filter(
				$posted_data,
				function ( $v, $k ) {
					return strpos( $k, 'field_' ) === 0 && empty( strpos( $k, '_day' ) ) && empty( strpos( $k, '_month' ) ) && empty( strpos( $k, '_year' ) );
				},
				ARRAY_FILTER_USE_BOTH
			);

			$all_fields          = array_column( $form_fields_all, null, 'id' );
			$profile_fields      = array_intersect_key( $all_fields, $xprofile_fields );
			$fields_with_type    = array_column( $form_fields_all, 'type', 'id' );
			$fields_member_types = array_filter( array_column( $profile_fields, 'member_type', 'id' ) );

			$member_type_field_id = array_search( 'membertypes', $fields_with_type, true );
			$all_member_types     = ! empty( $member_type_field_id ) && isset( $profile_fields[ $member_type_field_id ] ) ? array_column( $profile_fields[ $member_type_field_id ]['options'], 'key', 'id' ) : array();
			$selected_member_type = ( ! empty( $member_type_field_id ) && isset( $posted_data[ $member_type_field_id ] ) ? $posted_data[ $member_type_field_id ] : '' );
			$selected_member_type = ( ! empty( $selected_member_type ) && isset( $all_member_types[ $selected_member_type ] ) ) ? $all_member_types[ $selected_member_type ] : $selected_member_type;

			$profile_field_ids = array();

			// Make sure hidden field is passed and populated.
			if ( isset( $xprofile_fields ) && ! empty( $xprofile_fields ) ) {

				// Loop through the posted fields formatting any datebox values then validate the field.
				foreach ( (array) $xprofile_fields as $field => $value ) {

					$field_type = ( ! empty( $fields_member_types ) && isset( $fields_member_types[ $field ] ) ? array_filter(
						$fields_member_types[ $field ],
						function ( $v ) {
							return 'null' !== $v;
						}
					) : array() );

					if ( ! empty( $field_type ) && ! empty( $selected_member_type ) && ! in_array( $selected_member_type, $field_type, true ) ) {
						unset( $_POST[ $field ] );
						continue;
					}

					$field_id            = str_replace( 'field_', '', $field );
					$profile_field_ids[] = $field_id;
					if ( ! empty( $date_fields ) && in_array( $field, $date_fields, true ) ) {
						unset( $_POST[ $field ] );
					}

					bp_xprofile_maybe_format_datebox_post_data( $field_id );

					// Trim post fields.
					if ( isset( $_POST[ 'field_' . $field_id ] ) ) {
						if ( is_array( $_POST[ 'field_' . $field_id ] ) ) {
							$_POST[ 'field_' . $field_id ] = array_map( 'trim', $_POST[ 'field_' . $field_id ] ); // phpcs:ignore
						} else {
							$_POST[ 'field_' . $field_id ] = trim( $_POST[ 'field_' . $field_id ] ); // phpcs:ignore
						}
					}

					if ( ! empty( $date_fields ) && in_array( $field, $date_fields, true ) && ! isset( $_POST[ 'field_' . $field_id ] ) ) {
						$_POST[ 'field_' . $field_id ] = '';
					}

					// Create errors for required fields without values.
					if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST[ 'field_' . $field_id ] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
						$bp->signup->errors[ 'field_' . $field_id ] = __( 'This is a required field.', 'buddyboss' );
					} else {
						// Validate xprofile.
						$message = ( function_exists( 'xprofile_validate_field' ) ? xprofile_validate_field( $field_id, $_POST[ 'field_' . $field_id ], '' ) : '' ); // phpcs:ignore
						if ( isset( $_POST[ 'field_' . $field_id ] ) && ! empty( $message ) ) {
							$bp->signup->errors[ 'field_' . $field_id ] = $message;
						}
					}
				}
			}
		}

		// Finally, let's check the blog details, if the user wants a blog and blog creation is enabled.
		if ( isset( $_POST['signup_with_blog'] ) && ! empty( $_POST['signup_with_blog'] ) ) {
			$active_signup = bp_core_get_root_option( 'registration' );

			if ( 'blog' === $active_signup || 'all' === $active_signup ) {
				$blog_details = bp_core_validate_blog_signup( ( isset( $_POST['signup_blog_url'] ) ? $_POST['signup_blog_url'] : '' ), ( isset( $_POST['signup_blog_title'] ) ? $_POST['signup_blog_title'] : '' ) ); // phpcs:ignore

				// If there are errors with blog details, set them for display.
				if ( ! empty( $blog_details['errors']->errors['blogname'] ) ) {
					$bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];
				}

				if ( ! empty( $blog_details['errors']->errors['blog_title'] ) ) {
					$bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
				}
			}
		}

		if ( ! empty( $bp->signup->errors ) ) {
			if ( function_exists( 'bp_xprofile_nickname_field_id' ) && isset( $bp->signup->errors['signup_username'] ) ) {
				if ( ! isset( $bp->signup->errors[ 'field_' . bp_xprofile_nickname_field_id() ] ) ) {
					$bp->signup->errors[ 'field_' . bp_xprofile_nickname_field_id() ] = $bp->signup->errors['signup_username'];
				}
				unset( $bp->signup->errors['signup_username'] );
			}

			return new WP_Error(
				'bp_rest_register_errors',
				$bp->signup->errors,
				array(
					'status' => 400,
				)
			);
		}

		// No errors! Let's register those deets.
		$active_signup = bp_core_get_root_option( 'registration' );

		if ( 'none' === $active_signup ) {
			return new WP_Error(
				'bp_rest_signup_disabled',
				__( 'Sorry, you are not authorized to perform this action.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		// Make sure the profiles fields module is enabled.
		if ( bp_is_active( 'xprofile' ) && isset( $profile_field_ids ) && ! empty( $profile_field_ids ) ) {

			/**
			 * Loop through the posted fields, formatting any
			 * datebox values, then add to usermeta.
			 */
			foreach ( (array) $profile_field_ids as $field_id ) {
				bp_xprofile_maybe_format_datebox_post_data( $field_id );

				if ( ! empty( $_POST[ 'field_' . $field_id ] ) ) {
					$usermeta[ 'field_' . $field_id ] = $_POST[ 'field_' . $field_id ]; // phpcs:ignore
				}

				if ( ! empty( $_POST[ 'field_' . $field_id . '_visibility' ] ) ) {
					$usermeta[ 'field_' . $field_id . '_visibility' ] = $_POST[ 'field_' . $field_id . '_visibility' ]; // phpcs:ignore
				}
			}

			// Store the profile field ID's in usermeta.
			$usermeta['profile_field_ids'] = implode( ',', $profile_field_ids );
		}

		// Hash and store the password.
		$usermeta['password'] = wp_hash_password( $_POST['signup_password'] ); // phpcs:ignore

		// If the user decided to create a blog, save those details to usermeta.
		if ( 'blog' === $active_signup || 'all' === $active_signup ) {
			$usermeta['public'] = (
				(
					isset( $_POST['signup_blog_privacy'] )
					&& 'public' === $_POST['signup_blog_privacy']
				)
				? true
				: false
			);
		}

		/**
		 * Filters the user meta used for signup.
		 *
		 * @param array $usermeta Array of user meta to add to signup.
		 *
		 * @since 0.1.0
		 */
		$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );

		// Finally, sign up the user and/or blog.
		if ( isset( $_POST['signup_with_blog'] ) && is_multisite() ) {
			$wp_user_id = bp_core_signup_blog(
				$blog_details['domain'],
				$blog_details['path'],
				$blog_details['blog_title'],
				$user_name,
				$user_email,
				$usermeta
			);
		} else {
			$wp_user_id = bp_core_signup_user(
				$user_name,
				sanitize_text_field( wp_unslash( $_POST['signup_password'] ) ),
				$user_email,
				$usermeta
			);
		}

		if ( is_wp_error( $wp_user_id ) ) {
			return new WP_Error(
				'bp_rest_signup_cannot_create',
				$wp_user_id->get_error_message(),
				array(
					'status' => 500,
				)
			);
		}

		$signup        = $this->get_signup_object( $user_email );
		$signup_update = $this->update_additional_fields_for_object( $signup, $request );

		if ( is_wp_error( $signup_update ) ) {
			return new WP_Error(
				'bp_rest_rest_errors',
				__( 'Sorry, you are not authorized to perform this action.', 'buddyboss' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! empty( $wp_user_id ) && ! is_wp_error( $wp_user_id ) && ! empty( $_POST['legal_agreement'] ) ) {
			update_user_meta( $wp_user_id, 'bb_legal_agreement', true );
		}

		$retval            = array();
		$retval['success'] = true;
		$retval['message'] = __( 'Before you can login, you need to confirm your email address via the email we just sent to you.', 'buddyboss' );
		$retval['data']    = array();

		$retval['data'] = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $signup, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a signup item is created via the REST API.
		 *
		 * @param BP_Signup        $signup   The created signup.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_signup_create_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a signup.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not authorized to perform this action.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( ! is_user_logged_in() || bp_current_user_can( 'bp_moderate' ) ) {
			$retval = true;
		}

		/**
		 * Filter the signup `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a signup.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/signup/:id Delete signup
	 * @apiName        DeleteBBSignups
	 * @apiGroup       Signups
	 * @apiDescription Delete signup
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {String} id Identifier for the signup. Can be a signup ID, an email address, or a user_login.
	 */
	public function delete_item( $request ) {
		$request->set_param( 'context', 'edit' );

		// Get the signup before it's deleted.
		$signup   = $this->get_signup_object( $request['id'] );
		$previous = $this->prepare_item_for_response( $signup, $request );
		$deleted  = BP_Signup::delete( array( $signup->id ) );

		if ( ! $deleted ) {
			return new WP_Error(
				'bp_rest_signup_cannot_delete',
				__( 'Could not delete signup.', 'buddyboss' ),
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
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a signup is deleted via the REST API.
		 *
		 * @param BP_Signup        $signup   The deleted signup.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_signup_delete_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a signup.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = $this->get_item_permissions_check( $request );

		/**
		 * Filter the signup `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Activate a signup.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 * @since 0.1.0
	 *
	 * @api            {PATCH} /wp-json/buddyboss/v1/signup/activate/:activation_key Activate a signup
	 * @apiName        ActivateBBSignups
	 * @apiGroup       Signups
	 * @apiDescription Activate a signup.
	 * @apiVersion     1.0.0
	 * @apiParam {String} activation_key Identifier for the signup.
	 */
	public function activate_item( $request ) {
		$request->set_param( 'context', 'edit' );

		// Get the activation key.
		$activation_key = $request->get_param( 'activation_key' );

		// Get the signup to activate thanks to the activation key.
		$signup    = $this->get_signup_object( $activation_key );
		$activated = bp_core_activate_signup( $activation_key );

		if ( is_wp_error( $activated ) ) {
			return new WP_Error(
				'bp_rest_signup_activate_fail',
				__( 'Fail to activate the signup.', 'buddyboss' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $signup, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a signup is activated via the REST API.
		 *
		 * @param BP_Signup        $signup   The activated signup.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_signup_activate_item', $signup, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to activate a signup.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function activate_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_invalid_activation_key',
			__( 'Invalid activation key.', 'buddyboss' ),
			array(
				'status' => 404,
			)
		);

		// Get the activation key.
		$activation_key = $request->get_param( 'activation_key' );

		// Check the activation key is valid.
		if ( $this->get_signup_object( $activation_key ) ) {
			$retval = true;
		}

		/**
		 * Filter the signup `activate_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_activate_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepares signup to return as an object.
	 *
	 * @param BP_Signup       $signup  Signup object.
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $signup, $request ) {
		$data = array(
			'id'         => $signup->id,
			'user_login' => $signup->user_login,
			'user_name'  => $signup->user_name,
			'registered' => bp_rest_prepare_date_response( $signup->registered ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		if ( 'edit' === $context ) {
			$data['user_email'] = $signup->user_email;
		}

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		// @todo add prepare_links
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $signup ) );

		/**
		 * Filter the signup response returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BP_Signup        $signup   Signup object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_prepare_value', $response, $request, $signup );
	}

	/**
	 * Get signup object.
	 *
	 * @param int $identifier Signup identifier.
	 *
	 * @return BP_Signup|bool
	 * @since 0.1.0
	 */
	public function get_signup_object( $identifier ) {
		if ( is_numeric( $identifier ) ) {
			$signup_args['include'] = array( intval( $identifier ) );
		} elseif ( is_email( $identifier ) ) {
			$signup_args['usersearch'] = $identifier;
		} else {
			// The activation key is used when activating a signup.
			$signup_args['activation_key'] = $identifier;
		}

		// Get signups.
		$signups = \BP_Signup::get( $signup_args );

		if ( ! empty( $signups['signups'] ) ) {
			return reset( $signups['signups'] );
		}

		return false;
	}

	/**
	 * Edit the type of the some properties for the CREATABLE & EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			if ( ! function_exists( 'bp_xprofile_nickname_field_id' ) ) {
				$args['signup_username'] = array(
					'context'           => array( 'edit' ),
					'description'       => __( 'New user Username .', 'buddyboss' ),
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_user',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			$args['signup_email'] = array(
				'context'           => array( 'edit' ),
				'description'       => __( 'New user email address.', 'buddyboss' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => 'rest_validate_request_arg',
			);

			if ( function_exists( 'bp_register_confirm_email' ) && true === bp_register_confirm_email() ) {
				$args['signup_email_confirm'] = array(
					'context'           => array( 'edit' ),
					'description'       => __( 'New user confirm email address.', 'buddyboss' ),
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_email',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			$args['signup_password'] = array(
				'context'           => array( 'edit' ),
				'description'       => __( 'New user account password.', 'buddyboss' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			);

			if (
				( function_exists( 'bp_register_confirm_password' ) && false !== bp_register_confirm_password() )
				|| ! function_exists( 'bp_register_confirm_password' )
			) {
				$args['signup_password_confirm'] = array(
					'context'           => array( 'edit' ),
					'description'       => __( 'New user confirm account password.', 'buddyboss' ),
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			if ( bp_get_blog_signup_allowed() ) {
				$args['signup_with_blog'] = array(
					'context'           => array( 'edit' ),
					'description'       => __( 'If user likes to create a new site.', 'buddyboss' ),
					'type'              => 'boolean',
					'sanitize_callback' => 'rest_sanitize_boolean',
					'validate_callback' => 'rest_validate_request_arg',
				);

				$args['signup_blog_url'] = array(
					'context'           => array( 'edit' ),
					'description'       => __( 'New Site URL.', 'buddyboss' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				);

				$args['signup_blog_title'] = array(
					'context'           => array( 'edit' ),
					'description'       => __( 'New Site Title.', 'buddyboss' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				);

				$args['signup_blog_privacy'] = array(
					'context'           => array( 'edit' ),
					'description'       => __( 'If user would like to site appear in search engines, and in public listings around this network.', 'buddyboss' ),
					'type'              => 'string',
					'enum'              => array( 'public', 'private' ),
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}
		} elseif ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';
		} elseif ( WP_REST_Server::DELETABLE === $method ) {
			$key = 'delete_item';
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_signup_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param BP_Signup $signup Signup object.
	 *
	 * @return array Links for the given plugin.
	 * @since 0.1.0
	 */
	protected function prepare_links( $signup ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( $base . $signup->user_login ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		if ( isset( $signup->active ) && empty( $signup->active ) ) {
			$links['activate'] = array(
				'href' => rest_url( $base . 'activate/' . $signup->user_login ),
			);
		}

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @param array     $links  The prepared links of the REST response.
		 * @param BP_Signup $signup Signup object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_signup_prepare_links', $links, $signup );
	}

	/**
	 * Get the signup schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_signup',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the signup.', 'buddyboss' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'user_login'     => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The username of the user the signup is for.', 'buddyboss' ),
					'required'    => true,
					'type'        => 'string',
					'readonly'    => true,
				),
				'user_name'      => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The full name of the user the signup is for.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'user_email'     => array(
					'context'     => array( 'edit' ),
					'description' => __( 'The email for the user the signup is for.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'activation_key' => array(
					'context'     => array(),
					'description' => __( 'Activation key of the signup.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'registered'     => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'The registered date for the user, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'readonly'    => true,
					'format'      => 'date-time',
				),
			),
		);

		/**
		 * Filters the signup schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_signup_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		unset( $params['page'], $params['per_page'], $params['search'] );

		$params['number'] = array(
			'description'       => __( 'Total number of signups to return.', 'buddyboss' ),
			'default'           => 1,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['offset'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'buddyboss' ),
			'default'           => 0,
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
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

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order by a specific parameter (default: signup_id).', 'buddyboss' ),
			'default'           => 'signup_id',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['user_login'] = array(
			'description'       => __( 'Specific user login to return.', 'buddyboss' ),
			'default'           => '',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_signup_collection_params', $params );
	}

	/**
	 * Get the group id of the base name field
	 * - from bp_xprofile_base_group_id()
	 *
	 * @param int  $defalut    xProfile field group id.
	 * @param bool $get_option Get from options.
	 *
	 * @return int
	 */
	protected function bp_rest_xprofile_base_group_id( $defalut = 1, $get_option = true ) {
		if ( is_multisite() ) {
			$field_id = get_site_option( 'bp-xprofile-base-group-id' );
		}

		if ( empty( $field_id ) && $get_option ) {
			$field_id = bp_get_option( 'bp-xprofile-base-group-id', $defalut );
		}

		return (int) apply_filters( 'bp_xprofile_base_group_id', $field_id );
	}
}
