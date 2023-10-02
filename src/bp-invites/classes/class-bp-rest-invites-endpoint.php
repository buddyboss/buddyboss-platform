<?php
/**
 * BP REST: BP_REST_Invites_Endpoint class
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email Invites endpoints.
 *
 * @since 0.1.0
 */
class BP_REST_Invites_Endpoint extends WP_REST_Controller {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'invites';
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
						'description' => __( 'A unique numeric ID for the member invitation.', 'buddyboss' ),
						'type'        => 'integer',
						'required'    => true,
					),
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
			'/' . $this->rest_base . '/profile-type',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_invite_profile_type' ),
					'permission_callback' => array( $this, 'get_invite_profile_type_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Sent Invites.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of bp-invite post's object data.
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/invites Sent Invites
	 * @apiName        GetBBInvites
	 * @apiGroup       Email Invites
	 * @apiDescription Retrieve Sent Invites.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} [page=1] Current page of the collection.
	 * @apiParam {Number} [per_page=10] Maximum number of items to be returned in result set.
	 * @apiParam {String=asc,desc} [order=desc] Designates ascending or descending order of invites.
	 * @apiParam {String=date,ID,rand} [orderby=date] Sort retrieved invites by parameter.
	 */
	public function get_items( $request ) {

		$args = array(
			'post_type'      => bp_get_invite_post_type(),
			'author'         => (int) get_current_user_id(),
			'paged'          => ( ! empty( $request['page'] ) ? $request['page'] : '' ),
			'posts_per_page' => ( ! empty( $request['per_page'] ) ? $request['per_page'] : '' ),
			'orderby'        => ( ! empty( $request['orderby'] ) ? $request['orderby'] : 'date' ),
			'order'          => ( ! empty( $request['order'] ) ? $request['order'] : 'desc' ),
		);

		$invites_query = new WP_Query( $args );

		$sent_invites = ( ! empty( $invites_query->posts ) ? $invites_query->posts : array() );

		$retval = array();
		foreach ( $sent_invites as $invite ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $invite, $request )
			);
		}

		wp_reset_postdata();

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $invites_query->found_posts, $args['posts_per_page'] );

		/**
		 * Fires after a list of sent invites is fetched via the REST API.
		 *
		 * @param array            $sent_invites Fetched Invites.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_invites_get_items', $sent_invites, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to invites items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to view invites.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			if ( function_exists( 'bp_allow_user_to_send_invites' ) && false === bp_allow_user_to_send_invites() ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you don\'t have permission to view invites.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the invites `get_items` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_invites_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Create an Invites/Send Invites.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {POST} /wp-json/buddyboss/v1/invites Send Invites
	 * @apiName        CreateBBInvites
	 * @apiGroup       Email Invites
	 * @apiDescription Create an Invites/Send Invites.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Array} fields Fields array with name, email_id and profile_type to create an invites.
	 * @apiParam {string} [email_subject] Subject for invite a member.
	 * @apiParam {String} [email_content] Content for invite a member.
	 */
	public function create_item( $request ) {

		$fields = $request->get_param( 'fields' );

		if ( empty( $fields ) ) {
			return new WP_Error(
				'bp_rest_required_fields',
				__( 'Sorry, you need to set the fields parameter.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		}

		$invite_exists_array     = array();
		$failed_invite           = array();
		$invite_restricted_array = array();
		$duplicate_email_inputs  = array();

		$bp = buddypress();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $k => $field ) {
				if (
					isset( $field['name'] )
					&& isset( $field['email_id'] )
					&& '' !== $field['name']
					&& '' !== $field['name']
					&& '' !== $field['email_id']
					&& is_email( $field['email_id'] )
				) {
					// Ignore duplicate email input.
					if ( in_array( $field['email_id'], $duplicate_email_inputs, true ) ) {
						continue;
					}
					$duplicate_email_inputs[] = strtolower( trim( $field['email_id'] ) );

					if ( email_exists( (string) $field['email_id'] ) ) {
						$invite_exists_array[] = $field['email_id'];
					} elseif ( ! function_exists( 'bb_is_allowed_register_email_address' ) ) {
						$invite_correct_array[] = array(
							'name'        => $field['name'],
							'email'       => $field['email_id'],
							'member_type' => ( isset( $field['profile_type'] ) && ! empty( $field['profile_type'] ) ) ? $field['profile_type'] : '',
						);
					} elseif (
						function_exists( 'bb_is_allowed_register_email_address' ) &&
						bb_is_allowed_register_email_address( $field['email_id'] )
					) {
						$invite_correct_array[] = array(
							'name'        => $field['name'],
							'email'       => $field['email_id'],
							'member_type' => ( isset( $field['profile_type'] ) && ! empty( $field['profile_type'] ) ) ? $field['profile_type'] : '',
						);
					} else {
						$invite_restricted_array[] =  $field['email_id'];
					}
				} else {
					$invite_wrong_array[] = array(
						'name'        => ( isset( $field['name'] ) ? $field['name'] : '' ),
						'email'       => ( isset( $field['email_id'] ) ? $field['email_id'] : '' ),
						'member_type' => ( isset( $field['profile_type'] ) ? $field['profile_type'] : '' ),
					);
				}
			}
		}

		$invitations_ids = array();

		$query_string = array();
		if ( ! empty( $invite_correct_array ) ) {

			if ( ! function_exists( 'bp_invites_kses_allowed_tags' ) ) {
				require trailingslashit( buddypress()->plugin_dir . 'bp-invites/actions' ) . '/invites.php';
			}

			// check if it has enough recipients to use batch emails.
			$min_count_recipients = function_exists( 'bb_email_queue_has_min_count' ) && bb_email_queue_has_min_count( $invite_correct_array );

			foreach ( $invite_correct_array as $key => $value ) {

				$_POST = array();

				$email          = $value['email'];
				$name           = $value['name'];
				$member_type    = $value['member_type'];
				$query_string[] = $email;
				$inviter_name   = bp_core_get_user_displayname( bp_loggedin_user_id() );

				if ( true === bp_disable_invite_member_email_subject() ) {
					$subject = $request->get_param( 'email_subject' );
					if ( empty( $subject ) ) {
						$subject = stripslashes( wp_strip_all_tags( bp_get_member_invitation_subject() ) );
					} else {
						$_POST['bp_member_invites_custom_subject'] = $subject;
					}
				} else {
					$subject = stripslashes( wp_strip_all_tags( bp_get_member_invitation_subject() ) );
				}

				if ( true === bp_disable_invite_member_email_content() ) {
					$message = $request->get_param( 'email_content' );
					if ( empty( $message ) ) {
						$message = stripslashes( wp_strip_all_tags( bp_get_member_invitation_message() ) );
					} else {
						$_POST['bp_member_invites_custom_content'] = $message;
					}
				} else {
					$message = stripslashes( wp_strip_all_tags( bp_get_member_invitation_message() ) );
				}

				$message .= '

' . bp_get_member_invites_wildcard_replace( stripslashes( wp_strip_all_tags( bp_get_invites_member_invite_url() ) ), $email );

				$inviter_name = bp_core_get_user_displayname( bp_loggedin_user_id() );
				$site_name    = get_bloginfo( 'name' );
				$inviter_url  = bp_loggedin_user_domain();

				$email_encode = rawurlencode( $email );

				// set post variable.
				$_POST['custom_user_email'] = $email;

				// Set both variable which will use in email.
				$_POST['custom_user_name']   = $name;
				$_POST['custom_user_avatar'] = apply_filters( 'bp_sent_invite_email_avatar', function_exists( 'bb_attachments_get_default_profile_group_avatar_image' ) ? bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'user' ) ) : buddypress()->plugin_url . 'bp-core/images/profile-avatar-buddyboss.png' );

				$accept_link = add_query_arg(
					array(
						'bp-invites' => 'accept-member-invitation',
						'email'      => $email_encode,
						// phpcs:ignore
						'inviter'    => base64_encode( bp_loggedin_user_id() ),
					),
					bp_get_root_domain() . '/' . bp_get_signup_slug() . '/'
				);
				$accept_link = apply_filters( 'bp_member_invitation_accept_url', $accept_link );
				$args        = array(
					'tokens' => array(
						'inviter.name' => $inviter_name,
						'inviter.url'  => $inviter_url,
						'invitee.url'  => $accept_link,
					),
				);

				/**
				 * Remove Recipients avatar and name.
				 *
				 * T:1602 - https://trello.com/c/p2VKGMHs/1602-recipients-name-and-avatar-should-not-be-showing-on-email-invite-template
				 */
				add_filter( 'bp_email_get_salutation', '__return_false' );

				$insert_post_args = array(
					'post_author'  => $bp->loggedin_user->id,
					'post_content' => $message,
					'post_title'   => $subject,
					'post_status'  => 'publish',
					'post_type'    => bp_get_invite_post_type(),
				);

				$post_id = wp_insert_post( $insert_post_args );

				if ( ! empty( $post_id ) || ! is_wp_error( $post_id ) ) {
					$invitations_ids[] = $post_id;

					// Send invitation email.
					if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && $min_count_recipients ) {
						bb_email_queue()->add_record( 'invites-member-invite', $email, $args );
						// call email background process.
						bb_email_queue()->bb_email_background_process();
					} else {
						bp_send_email( 'invites-member-invite', $email, $args );
					}

					// Save a blank bp_ia_accepted post_meta.
					update_post_meta( $post_id, 'bp_member_invites_accepted', '' );
					update_post_meta( $post_id, '_bp_invitee_email', $email );
					update_post_meta( $post_id, '_bp_invitee_name', $name );
					update_post_meta( $post_id, '_bp_inviter_name', $inviter_name );
					update_post_meta( $post_id, '_bp_invitee_status', 0 );
					update_post_meta( $post_id, '_bp_invitee_member_type', $member_type );
				} else {
					$failed_invite[] = $value;
				}
			}
		}

		$retval = array(
			'data'   => array(),
			'exists' => '',
			'failed' => '',
		);

		if ( ! empty( $invite_exists_array ) ) {
			$retval['exists'] = trim( __( 'Invitations did not send to the following email addresses, because they are already members:', 'buddyboss' ) . ' ' . implode( ', ', $invite_exists_array ) );
		}

		if ( ! empty( $failed_invite ) ) {
			$retval['failed'] = trim( __( 'Invitations did not send because these email addresses are invalid:', 'buddyboss' ) . ' ' . implode( ', ', wp_list_pluck( array_filter( $failed_invite ), 'email' ) ) );
		}

		if ( ! empty( $invite_restricted_array ) ) {
			$retval['failed'] = trim( __( 'Invitations did not send to the following email addresses, because the address or domain has been blacklisted:', 'buddyboss' ) . ' ' . implode( ', ', $invite_restricted_array ) );
		}

		if ( ! empty( $invitations_ids ) ) {
			$send_invitations = get_posts(
				array(
					'post_type'              => bp_get_invite_post_type(),
					'include'                => $invitations_ids,
					'suppress_filters'       => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $send_invitations as $invite ) {
				$retval['data'][] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $invite, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of invites has been send via the REST API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_invites_get_items', $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create an invites.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to create invites.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			if ( function_exists( 'bp_allow_user_to_send_invites' ) && false === bp_allow_user_to_send_invites() ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you don\'t have permission to create invites.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the invites `create_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_invites_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a invites.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 0.1.0
	 *
	 * @api            {DELETE} /wp-json/buddyboss/v1/invites/:id Revoke Invite
	 * @apiName        DeleteBBInvites
	 * @apiGroup       Email Invites
	 * @apiDescription Remoke Invites.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the member invitation.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		$invite = get_post( $request['id'] );

		$previous = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $invite, $request )
		);

		$success = wp_delete_post( $invite->ID );

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => ( ! empty( $success ) && ! is_wp_error( $success ) ? true : $success ),
				'previous' => $previous,
			)
		);

		/**
		 * Fires after a invitation is deleted via the REST API.
		 *
		 * @param object           $invite   The deleted invitation.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_invites_delete_item', $invite, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete an invite.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to revoke invite.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$invite = get_post( $request['id'] );

			if ( function_exists( 'bp_allow_user_to_send_invites' ) && false === bp_allow_user_to_send_invites() ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you don\'t have permission to revoke invite.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			} elseif ( empty( $invite->ID ) ) {
				$retval = new WP_Error(
					'bp_rest_invite_invalid_id',
					__( 'Invalid invite ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! isset( $invite->post_type ) || 'bp-invite' !== $invite->post_type ) {
				$retval = new WP_Error(
					'bp_rest_invite_invalid_id',
					__( 'Invalid invite ID.', 'buddyboss' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the invites `delete_item` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_invites_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Sent Invites Profile Type.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error List of bp-invite profile types
	 * @since 0.1.0
	 *
	 * @api            {GET} /wp-json/buddyboss/v1/invites/profile-type Invites Profile Type
	 * @apiName        GetBBInvitesProfileType
	 * @apiGroup       Email Invites
	 * @apiDescription Retrieve Sent Invites Profile Type.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 */
	public function get_invite_profile_type( $request ) {

		$member_types = array();

		if ( function_exists( 'bp_check_member_send_invites_tab_member_type_allowed' ) && true === bp_check_member_send_invites_tab_member_type_allowed() ) {
			$current_user              = bp_loggedin_user_id();
			$member_type               = bp_get_member_type( $current_user );
			$member_type_post_id       = bp_member_type_post_by_type( $member_type );
			$get_selected_member_types = get_post_meta( $member_type_post_id, '_bp_member_type_allowed_member_type_invite', true );
			if ( isset( $get_selected_member_types ) && ! empty( $get_selected_member_types ) ) {
				$member_types = $get_selected_member_types;
			} else {
				$member_types = bp_get_active_member_types();
			}
		}

		$retval = array();

		if ( ! empty( $member_types ) ) {
			foreach ( $member_types as $type ) {
				$name     = bp_get_member_type_key( $type );
				$type_obj = bp_get_member_type_object( $name );
				if ( ! empty( $type_obj ) ) {
					$member_type = $type_obj->labels['singular_name'];
				}

				$retval[] = array(
					'value' => $name,
					'label' => esc_html( $member_type ),
				);
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of sent invites profile type is fetched via the REST API.
		 *
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		do_action( 'bp_rest_invites_get_invite_profile_type', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to invites profile type items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 * @since 0.1.0
	 */
	public function get_invite_profile_type_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to view invites profile type.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			if ( function_exists( 'bp_allow_user_to_send_invites' ) && false === bp_allow_user_to_send_invites() ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you don\'t have permission to view invites profile type.', 'buddyboss' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the invites `get_invite_profile_type` permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_invites_get_invite_profile_type_permissions_check', $retval, $request );
	}

	/**
	 * Prepares Invite data for return as an object.
	 *
	 * @param WP_Post         $item    bp-invite post object.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response
	 * @since 0.1.0
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'             => $item->ID,
			'name'           => get_post_meta( $item->ID, '_bp_invitee_name', true ),
			'email'          => get_post_meta( $item->ID, '_bp_invitee_email', true ),
			'date'           => $this->prepare_date_response( $item->post_date_gmt, $item->post_date ),
			'date_gmt'       => $this->prepare_date_response( $item->post_date_gmt ),
			'status'         => '',
			'invitee-status' => (bool) get_post_meta( $item->ID, '_bp_invitee_status', true ),
			'revoke-invite'  => false,
		);

		if (
			function_exists( 'bp_allow_custom_registration' )
			&& function_exists( 'bp_custom_register_page_url' )
			&& bp_allow_custom_registration()
			&& '' !== bp_custom_register_page_url()
		) {
			$data['status'] = ( '1' === get_post_meta( $item->ID, '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss' ) : __( 'Invited', 'buddyboss' );
		} else {
			$data['status']        = ( '1' === get_post_meta( $item->ID, '_bp_invitee_status', true ) ) ? __( 'Registered', 'buddyboss' ) : __( 'Revoke Invite', 'buddyboss' );
			$data['revoke-invite'] = ( '1' === get_post_meta( $item->ID, '_bp_invitee_status', true ) ) ? false : true;
		}

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		/**
		 * Filter a invite value returned from the API.
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param WP_Post          $item     bp-invite post object.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( 'bp_rest_invites_prepare_value', $response, $request, $item );
	}

	/**
	 * Edit some arguments for the endpoint's CREATABLE and EDITABLE methods.
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 * @since 0.1.0
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'create_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$args = array(
				'fields' => array(
					'description' => __( 'Fields array with name and email_id to create an invites.', 'buddyboss' ),
					'type'        => 'array',
					'items'       => array( 'type' => 'object' ),
					'required'    => true,
					'properties'  => array(
						'name'     => array(
							'description'       => __( 'Recipient Name for the invite.', 'buddyboss' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'email_id' => array(
							'description'       => __( 'Recipient Email for the invite.', 'buddyboss' ),
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			);

			if ( true === bp_check_member_send_invites_tab_member_type_allowed() ) {
				$args['fields']['description']                = __( 'Fields array with name, email_id and profile_type to create an invites.', 'buddyboss' );
				$args['fields']['properties']['profile_type'] = array(
					'description'       => __( 'Profile Type for the invite.', 'buddyboss' ),
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			if ( true === bp_disable_invite_member_email_subject() ) {
				$args['email_subject'] = array(
					'description'       => __( 'Subject for invite a member.', 'buddyboss' ),
					'type'              => 'string',
					'default'           => stripslashes( wp_strip_all_tags( bp_get_member_invitation_subject() ) ),
					'validate_callback' => 'rest_validate_request_arg',
				);
			}

			if ( true === bp_disable_invite_member_email_content() ) {
				$args['email_content'] = array(
					'description'       => __( 'Content for invite a member.', 'buddyboss' ),
					'type'              => 'string',
					'default'           => bp_get_member_invites_wildcard_replace( bp_get_member_invitation_message() ),
					'validate_callback' => 'rest_validate_request_arg',
				);
			}
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 *
		 * @since 0.1.0
		 */
		return apply_filters( "bp_rest_invites_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the invite schema, conforming to JSON Schema.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_invites',
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => __( 'Unique identifier for the invite.', 'buddyboss' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'           => array(
					'description' => __( 'Member\'s name.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'email'          => array(
					'description' => __( 'Member\'s email address', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'date'           => array(
					'description' => __( 'The date the object was published, in the site\'s timezone.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'date_gmt'       => array(
					'description' => __( 'The date the object was published, as GMT.', 'buddyboss' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'status'         => array(
					'description' => __( 'Status to perform on it.', 'buddyboss' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'invitee-status' => array(
					'description' => __( 'Whether invitee is registered or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'revoke-invite'  => array(
					'description' => __( 'Whether revoke invite or not.', 'buddyboss' ),
					'type'        => 'boolean',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			),
		);

		/**
		 * Filters the Invites schema.
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_invites_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Get the query params for collections of invites.
	 *
	 * @return array
	 * @since 0.1.0
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		unset( $params['search'] );

		$params['order'] = array(
			'description'       => __( 'Designates ascending or descending order of invites.', 'buddyboss' ),
			'default'           => 'desc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Sort retrieved invites by parameter.', 'buddyboss' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
				'enum' => array(
					'date',
					'ID',
					'rand',
				),
			),
			'sanitize_callback' => 'bp_rest_sanitize_string_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_invites_collection_params', $params );
	}

	/**
	 * Check the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @param string      $date_gmt GMT date format.
	 * @param string|null $date     forum date.
	 *
	 * @return string|null ISO8601/RFC3339 formatted datetime.
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
		}

		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Return the formatted datetime.
		return mysql_to_rfc3339( $date_gmt ); // phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_to_rfc3339, PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved
	}
}
