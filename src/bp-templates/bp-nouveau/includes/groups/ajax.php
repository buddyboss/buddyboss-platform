<?php
/**
 * Groups Ajax functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function() {

	$ajax_actions = array(
		array( 'groups_filter'                                 => array( 'function' => 'bp_nouveau_ajax_object_template_loader', 'nopriv' => true  ) ),
		array( 'groups_join_group'                             => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_leave_group'                            => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_accept_invite'                          => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_reject_invite'                          => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_request_membership'                     => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_get_group_potential_invites'            => array( 'function' => 'bp_nouveau_ajax_get_users_to_invite', 'nopriv' => false ) ),
		array( 'groups_get_group_potential_user_send_messages' => array( 'function' => 'bp_nouveau_ajax_group_get_users_to_send_message', 'nopriv' => false ) ),
		array( 'groups_get_group_members_listing'              => array( 'function' => 'bp_nouveau_ajax_groups_get_group_members_listing', 'nopriv' => false ) ),
		array( 'groups_get_group_members_send_message'         => array( 'function' => 'bp_nouveau_ajax_groups_get_group_members_send_message', 'nopriv' => false ) ),
		array( 'groups_send_group_invites'                     => array( 'function' => 'bp_nouveau_ajax_send_group_invites', 'nopriv' => false ) ),
		array( 'groups_delete_group_invite'                    => array( 'function' => 'bp_nouveau_ajax_remove_group_invite', 'nopriv' => false ) ),
	);

	foreach ( $ajax_actions as $ajax_action ) {
		$action = key( $ajax_action );

		add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

		if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
			add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
		}
	}
}, 12 );

/**
 * Join or leave a group when clicking the "join/leave" button via a POST request.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string HTML
 */
function bp_nouveau_ajax_joinleave_group() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() || empty( $_POST['action'] ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || empty( $_POST['item_id'] ) || ! bp_is_active( 'groups' ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['nonce'];
	$check = 'bp_nouveau_groups';

	// Use a specific one for actions needed it
	if ( ! empty( $_POST['_wpnonce'] ) && ! empty( $_POST['action'] ) ) {
		$nonce = $_POST['_wpnonce'];
		$check = $_POST['action'];
	}

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Cast gid as integer.
	$group_id = (int) $_POST['item_id'];

	$errors = array(
		'cannot' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You cannot join this group.', 'buddyboss' ) ),
		'member' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'You are already a member of the group.', 'buddyboss' ) ),
	);

	if ( groups_is_user_banned( bp_loggedin_user_id(), $group_id ) ) {
		$response['feedback'] = $errors['cannot'];

		wp_send_json_error( $response );
	}

	// Validate and get the group
	$group = groups_get_group( array( 'group_id' => $group_id ) );

	if ( empty( $group->id ) ) {
		wp_send_json_error( $response );
	}

	// Manage all button's possible actions here.
	switch ( $_POST['action'] ) {

		case 'groups_accept_invite':
			if ( ! groups_accept_invite( bp_loggedin_user_id(), $group_id ) ) {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invitation could not be accepted.', 'buddyboss' )
					),
					'type'     => 'error',
				);

			} else {
				if ( bp_is_active( 'activity' ) ) {
					groups_record_activity(
						array(
							'type'    => 'joined_group',
							'item_id' => $group->id,
						)
					);
				}

				// User is now a member of the group
				$group->is_member = '1';

				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invite accepted.', 'buddyboss' )
					),
					'type'     => 'success',
					'is_user'  => bp_is_user(),
					'contents' => bp_get_group_join_button( $group ),
					'is_group' => bp_is_group(),
				);
			}
			break;

		case 'groups_reject_invite':
			if ( ! groups_reject_invite( bp_loggedin_user_id(), $group_id ) ) {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invite could not be rejected', 'buddyboss' )
					),
					'type'     => 'error',
				);
			} else {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Group invite rejected', 'buddyboss' )
					),
					'type'     => 'success',
					'is_user'  => bp_is_user(),
				);
			}
			break;

		case 'groups_join_group':
			if ( groups_is_user_member( bp_loggedin_user_id(), $group->id ) ) {
				$response = array(
					'feedback' => $errors['member'],
					'type'     => 'error',
				);
			} elseif ( 'public' !== $group->status ) {
				$response = array(
					'feedback' => $errors['cannot'],
					'type'     => 'error',
				);
			} elseif ( ! groups_join_group( $group->id ) ) {
				$response = array(
					'feedback' => sprintf(
						'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
						esc_html__( 'Error joining this group.', 'buddyboss' )
					),
					'type'     => 'error',
				);
			} else {
				// User is now a member of the group
				$group->is_member = '1';

				$response = array(
					'contents' => bp_get_group_join_button( $group ),
					'is_group' => bp_is_group(),
					'type'     => 'success',
				);
			}
			break;

			case 'groups_request_membership' :
				if ( ! groups_send_membership_request( bp_loggedin_user_id(), $group->id ) ) {
					$response = array(
						'feedback' => sprintf(
							'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
							esc_html__( 'Error requesting membership.', 'buddyboss' )
						),
						'type'     => 'error',
					);
				} else {
					// Request is pending
					$group->is_pending = '1';

					$response = array(
						'contents' => bp_get_group_join_button( $group ),
						'is_group' => bp_is_group(),
						'type'     => 'success',
					);
				}
				break;

			case 'groups_leave_group' :
				if ( ! groups_leave_group( $group->id ) ) {
					$response = array(
						'feedback' => sprintf(
							'<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
							esc_html__( 'Error leaving group.', 'buddyboss' )
						),
						'type'     => 'error',
					);
				} else {
					// User is no more a member of the group
					$group->is_member = '0';
					$bp               = buddypress();

					/**
					 * When inside the group or in the loggedin user's group memberships screen
					 * we need to reload the page.
					 */
					$bp_is_group = bp_is_group() || ( bp_is_user_groups() && bp_is_my_profile() );

					$response = array(
						'contents' => bp_get_group_join_button( $group ),
						'is_group' => $bp_is_group,
						'type'     => 'success',
					);

					// Reset the message if not in a Group or in a loggedin user's group memberships one!
					if ( ! $bp_is_group && isset( $bp->template_message ) && isset( $bp->template_message_type ) ) {
						unset( $bp->template_message, $bp->template_message_type );

						@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
						@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
					}
				}
				break;
	}

	if ( 'error' === $response['type'] ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
}

/**
 * AJAX get list of members to invite to group.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_get_users_to_invite() {
	$bp = buddypress();

	$response = array(
		'feedback' => __( 'There was a problem performing this action. Please try again.', 'buddyboss' ),
		'type'     => 'error',
	);

	if ( empty( $_POST['nonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['nonce'];
	$check = 'bp_nouveau_groups';

	// Use a specific one for actions needed it
	if ( ! empty( $_POST['_wpnonce'] ) && ! empty( $_POST['action'] ) ) {
		$nonce = $_POST['_wpnonce'];
		$check = $_POST['action'];
	}

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$request = wp_parse_args( $_POST, array(
		'scope' => 'members',
	) );

	if ( 'groups_get_group_potential_invites' === $request['action'] ) {

		// check if subgroup.
		$parent_group_id = bp_get_parent_group_id( $request['group_id'] );

		if ( isset( $parent_group_id ) && $parent_group_id > 0 ) {

			$check_admin = groups_is_user_admin( bp_loggedin_user_id(), $parent_group_id );
			$check_moder = groups_is_user_mod( bp_loggedin_user_id(), $parent_group_id );

			// Check role of current logged in user for this group.
			if ( false === $check_admin && false === $check_moder ) {
				wp_send_json_error( array(
					'feedback' => __( 'You are not authorized to send invites to other users.', 'buddyboss' ),
					'type'     => 'info',
				) );
			}
		}

		$group_type = bp_groups_get_group_type( $request['group_id'] );

		// Include profile type if in Group Types > E.g Team > Group Invites ( Meta Box ) specific profile type selected.
		if ( false !== $group_type ) {
			$group_type_id = bp_group_get_group_type_id( $group_type );
			$get_selected_member_types = get_post_meta( $group_type_id, '_bp_group_type_enabled_member_type_group_invites', true );
			if ( isset( $get_selected_member_types ) && ! empty( $get_selected_member_types ) ) {
				$request['member_type'] = implode( ',', $get_selected_member_types );
			}
		}

		// Include users ( Restrict group invites to only members of who already exists in parent group ) in BuddyBoss > Settings > Social Groups > Group Hierarchies
		//if ( false !== $group_type ) {
			if ( true === bp_enable_group_hierarchies() ) {
				if ( true === bp_enable_group_restrict_invites() ) {
					$parent_group_id = bp_get_parent_group_id( $request['group_id'] );
					if ( $parent_group_id > 0 ) {
						$members_query      = groups_get_group_members( array(
							'group_id' => $parent_group_id,
						) );
						$members            = wp_list_pluck( $members_query['members'], 'ID' );
						$request['include'] = implode( ',', $members );

						if ( empty( $request['include'] ) ) {
							wp_send_json_error( array(
								'feedback' => __( 'No members found in parent group.', 'buddyboss' ),
								'type'     => 'info',
							) );
						}
					}
				}
			}
		//}

		// Exclude users if ( Restrict invites if user already in other same group type ) is checked
		if ( false !== $group_type ) {
			$group_type_id = bp_group_get_group_type_id( $group_type );
			$meta = get_post_custom( $group_type_id );
			$get_restrict_invites_same_group_types = isset( $meta[ '_bp_group_type_restrict_invites_user_same_group_type' ] ) ? intval( $meta[ '_bp_group_type_restrict_invites_user_same_group_type' ][ 0 ] ) : 0;
			if ( 1 === $get_restrict_invites_same_group_types ) {
				$group_arr = bp_get_group_ids_by_group_types( $group_type );
				if ( isset( $group_arr ) && !empty( $group_arr ) ) {
					$group_arr = wp_list_pluck( $group_arr, 'id' );
					if (($key = array_search( $request['group_id'], $group_arr ) ) !== false) {
						unset( $group_arr[$key] );
					}
					$member_arr = array();
					foreach ( $group_arr as $group_id ) {
						$members_query = groups_get_group_members( array(
							'group_id' => $group_id,
						) );
						$members_list  = wp_list_pluck( $members_query['members'], 'ID' );
						foreach ( $members_list as $id ) {
							$member_arr[] = $id;
						}
					}
					$member_arr = array_unique( $member_arr );
					if ( isset( $members ) && ! empty( $members ) ) {
						$members            = array_diff( $members, $member_arr );
						$request['include'] = implode( ',', $members );
					}
					$request['exclude'] = implode( ',', $member_arr );
				}
			}
		}
	}

	$bp->groups->invites_scope = 'members';
	$message = __( 'Select members to invite by clicking the + button next to each member. Once you\'ve made a selection, use the "Send Invites" navigation item to customize the invite.', 'buddyboss' );

	if ( 'friends' === $request['scope'] ) {
		$request['user_id'] = bp_loggedin_user_id();
		$bp->groups->invites_scope = 'friends';
		$message = __( 'Select which connections to invite by clicking the + button next to each member. Once you\'ve made a selection, use the "Send Invites" navigation item to customize the invite.', 'buddyboss' );
	}

	if ( 'invited' === $request['scope'] ) {

		if ( ! bp_group_has_invites( array( 'user_id' => 'any', 'group_id' => $request['group_id'] ) ) ) {
			wp_send_json_error( array(
				'feedback' => __( 'No pending group invitations found.', 'buddyboss' ),
				'type'     => 'info',
			) );
		}

		$request['is_confirmed'] = false;
		$bp->groups->invites_scope = 'invited';
		$message = __( 'You can view the group\'s pending invitations from this screen.', 'buddyboss' );
	}

	$potential_invites = bp_nouveau_get_group_potential_invites( $request );

	if ( empty( $potential_invites->users ) ) {
		$error = array(
			'feedback' => __( 'No members were found. Try another filter.', 'buddyboss' ),
			'type'     => 'info',
		);

		if ( 'friends' === $bp->groups->invites_scope ) {
			$error = array(
				'feedback' => __( 'All your connections are already members of this group or have already received an invite to join this group or have requested to join it.', 'buddyboss' ),
				'type'     => 'info',
			);

			if ( 0 === (int) bp_get_total_friend_count( bp_loggedin_user_id() ) ) {
				$error = array(
					'feedback' => __( 'You have no connections.', 'buddyboss' ),
					'type'     => 'info',
				);
			}
		}

		unset( $bp->groups->invites_scope );

		wp_send_json_error( $error );
	}

	$potential_invites->users = array_map( 'bp_nouveau_prepare_group_potential_invites_for_js', array_values( $potential_invites->users ) );
	$potential_invites->users = array_filter( $potential_invites->users );

	// Set a message to explain use of the current scope
	$potential_invites->feedback = $message;

	unset( $bp->groups->invites_scope );

	wp_send_json_success( $potential_invites );
}

/**
 * AJAX send group invite.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_send_group_invites() {
	$bp = buddypress();

	$response = array(
		'feedback' => __( 'Invites could not be sent. Please try again.', 'buddyboss' ),
		'type'     => 'error',
	);

	// Verify nonce
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'groups_send_invites' ) ) {
		wp_send_json_error( $response );
	}

	$group_id = bp_get_current_group_id()?: $_POST['group_id'];

	if ( bp_is_group_create() && ! empty( $_POST['group_id'] ) ) {
		$group_id = (int) $_POST['group_id'];
	}

	if ( ! bp_groups_user_can_send_invites( $group_id ) ) {
		$response['feedback'] = __( 'You are not allowed to send invitations for this group.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['users'] ) ) {
		wp_send_json_error( $response );
	}

	if ( ! empty( $_POST['message'] ) ) {
		$bp->groups->invites_message = wp_kses( wp_unslash( $_POST['message'] ), array() );

		add_filter( 'groups_notification_group_invites_message', 'bp_nouveau_groups_invites_custom_message', 10, 1 );
	}

	// For feedback
	$invited = array();

	foreach ( (array) $_POST['users'] as $user_id ) {
		$invited[ (int) $user_id ] = groups_invite_user(
			array(
				'user_id'  => $user_id,
				'group_id' => $group_id,
			)
		);
	}

	// Send the invites.
	groups_send_invites( bp_loggedin_user_id(), $group_id );

	if ( ! empty( $_POST['message'] ) ) {
		unset( $bp->groups->invites_message );

		remove_filter( 'groups_notification_group_invites_message', 'bp_nouveau_groups_invites_custom_message', 10, 1 );
	}

	if ( array_search( false, $invited ) ) {
		$errors = array_keys( $invited, false );

		$error_count   = count( $errors );
		$error_message = sprintf(
			/* translators: count of users affected */
			_n(
				'Invitation failed for %s user.',
				'Invitation failed for %s users.',
				$error_count, 'buddyboss'
			),
			number_format_i18n( $error_count )
		);

		wp_send_json_error(
			array(
				'feedback' => $error_message,
				'users'    => $errors,
				'type'     => 'error',
			)
		);
	}

	wp_send_json_success(
		array(
			'feedback' => __( 'Invitations sent.', 'buddyboss' ),
			'type'     => 'success',
		)
	);
}

/**
 * AJAX remove group invite.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_remove_group_invite() {
	$user_id  = (int) $_POST['user'];
	$group_id = bp_get_current_group_id();

	// Verify nonce
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'groups_invite_uninvite_user' ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'Group invitation could not be removed.', 'buddyboss' ),
				'type'     => 'error',
			)
		);
	}

	if ( BP_Groups_Member::check_for_membership_request( $user_id, $group_id ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'The member is already a member of the group.', 'buddyboss' ),
				'type'     => 'warning',
				'code'     => 1,
			)
		);
	}

	// Remove the unsent invitation.
	if ( ! groups_uninvite_user( $user_id, $group_id ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'Group invitation could not be removed.', 'buddyboss' ),
				'type'     => 'error',
				'code'     => 0,
			)
		);
	}

	wp_send_json_success(
		array(
			'feedback'    => __( 'There are no more pending invitations for the group.', 'buddyboss' ),
			'type'        => 'info',
			'has_invites' => bp_group_has_invites( array( 'user_id' => 'any' ) ),
		)
	);
}

/**
 * Send Group Messages to group members
 *
 * @since BuddyBoss 1.2.0
 */
function bp_nouveau_ajax_group_get_users_to_send_message() {

	if ( false === bp_disable_group_messages() ) {
		return;
	}

	if ( empty( $_GET['action'] ) ) {
		wp_send_json_error();
	}

	$response = array(
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'There was a problem loading recipients. Please try again.', 'buddyboss' ) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'retrieve_group_members' ) ) {
		wp_send_json_error( $response );
	}

	$args          = array(
		'per_page'     => 99999999999,
		'group_id'     => $_GET['group'],
		'search_terms' => $_GET['term'],
	);
	$group_members = groups_get_group_members( $args );

	$result = array();

	if ( empty( $group_members['members'] ) ) {
		wp_send_json_success( [
			'results' => array_map( function ( $result ) {
				return [
					'id'   => "@{$result->ID}",
					'text' => $result->name,
				];
			},
				$result ),
		] );
	} else {
		foreach ( $group_members['members'] as $member ) {
			$result[] = (object) array(
				'id'   => $member->ID,
				'name' => bp_core_get_user_displayname( $member->ID ),
			);
		}
		$results = apply_filters( 'bp_nouveau_ajax_group_get_users_to_send_message', $result );
		wp_send_json_success( [
			'results' => array_map( function ( $result ) {
				return [
					'id'   => "@{$result->ID}",
					'text' => $result->name,
				];
			},
				$results ),
		] );
	}
}

/**
 * Retrieve the possible members list to send group message.
 *
 * @since BuddyBoss 1.2.0
 */
function bp_nouveau_ajax_groups_get_group_members_listing() {

	if ( false === bp_disable_group_messages() ) {
		return;
	}

	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$response = array(
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'There was a problem loading recipients. Please try again.', 'buddyboss' ) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'retrieve_group_members' ) ) {
		wp_send_json_error( $response );
	}

	$per_page        = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_per_page', 10 );
	$search_per_page = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_search_per_page', 99999999999999 );
	$page            = (int) $_POST['page'];

	if ( isset( $_POST['term'] ) && '' !== $_POST['term'] ) {
		$args = array(
			'per_page'            => $search_per_page,
			'group_id'            => $_POST['group'],
			'search_terms'        => $_POST['term'],
			'exclude'             => array( bp_loggedin_user_id() ),
			'exclude_admins_mods' => false,
		);
	} else {
		$args = array(
			'page'                => $page,
			'per_page'            => $per_page,
			'group_id'            => $_POST['group'],
			'exclude'             => array( bp_loggedin_user_id() ),
			'exclude_admins_mods' => false,
		);
	}

	$group_members = groups_get_group_members( $args );
	$html          = '';
	$paginate      = '';
	$result        = array();
	$total_page    = 0;

	if ( empty( $group_members['members'] ) ) {
		wp_send_json_success( [
			'results'    => 'no_member',
		] );
	} else {
		$total_page = (int) ceil( (int) $group_members['count'] / $per_page );
		ob_start();
		foreach ( $group_members['members'] as $member ) {

			$image  = htmlspecialchars_decode( bp_core_fetch_avatar( array(
				'item_id' => $member->ID,
				'object'  => 'user',
				'type'    => 'thumb',
				'class'   => '',
			) ) );

			$name = bp_core_get_user_displayname( $member->ID );
			?>
			<li class="<?php echo $member->ID; ?>">
				<div class="item-avatar">
					<?php echo $image; ?>
				</div>
				<div class="item">
					<div class="list-title member-name">
						<?php echo $name; ?>
					</div>
				</div>
				<div class="action">
					<button type="button" class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons" data-bp-user-id="<?php echo esc_attr( $member->ID ); ?>" data-bp-user-name="<?php echo esc_attr( $name ); ?>" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Add Recipient', 'buddyboss' ); ?>">
						<span class="icons" aria-hidden="true"></span>
						<span class="bp-screen-reader-text">
							<?php esc_html_e( 'Add Recipient', 'buddyboss' ); ?>
						</span>
					</button>
				</div>
			</li>
			<?php
		}

		if ( $total_page !== (int) $_POST['page'] ) {
			?>
			<li class="load-more">
				<div class="center">
					<i class="dashicons dashicons-update animate-spin"></i>
				</div>
			</li>
		<?php
		}

		$html = ob_get_contents();
		ob_clean();

		if ( empty( $_POST['term'] ) ) {

			ob_start();

			if ( 1 !== (int) $_POST['page'] ) { ?>
				<a href="javascript:void(0);" id="bp-group-messages-prev-page" class="button group-message-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Previous page',
					'buddyboss' ); ?>"> <span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span> </a>
			<?php }

			if ( $total_page !== (int) $_POST['page'] ) {
				$page = $page + 1;
				?>
				<a href="javascript:void(0);" id="bp-group-messages-next-page" class="button group-message-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Next page',
					'buddyboss' ); ?>"> <span class="bp-screen-reader-text"><?php esc_html_e( 'Next page',
							'buddyboss' ); ?></span>
					<span class="dashicons dashicons-arrow-right" aria-hidden="true"></span> </a>
			<?php }

			$paginate = ob_get_contents();
			ob_clean();

		}

		$html        = apply_filters( 'bp_nouveau_ajax_group_get_users_to_send_message_html', $html );
		$total_page  = apply_filters( 'bp_nouveau_ajax_group_get_users_to_send_message_total_page', $total_page );
		$page        = apply_filters( 'bp_nouveau_ajax_group_get_users_to_send_message_page', $page );
		$paginate    = apply_filters( 'bp_nouveau_ajax_group_get_users_to_send_message_paginate', $paginate );
		$total_count = apply_filters( 'bp_nouveau_ajax_group_get_users_to_send_message_total', $group_members['count'] );

		wp_send_json_success( [
			'results'     => $html,
			'total_page'  => $total_page,
			'page'        => $page,
			'pagination'  => $paginate,
			'total_count' => __( 'Members', 'buddyboss' ),
		] );

	}
}

/**
 * Send group message to group members.
 *
 * @since BuddyBoss 1.2.0
 */
function bp_nouveau_ajax_groups_get_group_members_send_message() {

	if ( false === bp_disable_group_messages() ) {
		return;
	}

	if ( empty( $_POST['action'] ) ) {
		wp_send_json_error();
	}

	$response = array(
		'feedback' => '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>' . __( 'There was a problem loading recipients. Please try again.', 'buddyboss' ) . '</p></div>',
		'type'     => 'error',
	);

	if ( false === bp_is_active( 'messages' ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'send_messages_users' ) ) {
		wp_send_json_error( $response );
	}

	if ( isset( $_POST['gif'] ) && '' !== $_POST['gif'] ) {
		$_POST['gif_data'] = json_decode( wp_kses_stripslashes( $_POST[ 'gif' ] ), true );
	}

	if ( isset( $_POST['media'] ) && '' !== $_POST['media'] ) {
		$_POST['media'] = json_decode( wp_kses_stripslashes( $_POST[ 'media' ] ), true );
	}

	if ( isset( $_POST['users'] ) && 'all' === $_POST['users'] ) {

		$args = array(
			'per_page' => 99999999999999,
			'group'    => $_POST['group'],
			'exclude'  => array( bp_loggedin_user_id() ),
		);

		$group_members = groups_get_group_members( $args );
		$members            = wp_list_pluck( $group_members['members'], 'ID' );
	} elseif ( isset( $_POST['users'] ) && 'individual' === $_POST['users'] ) {
		$members            = $_POST['users_list'];
	}

	$message_users_ids = implode( ',', $members );
	$_POST['message_meta_users_list'] = $message_users_ids;

	$meta = array(
		array(
			'key'   => 'group_id',
			'value' => $_POST['group'],
		),
		array(
			'key'   => 'group_message_users',
			'value' => $_POST['users'],
		),
		array(
			'key'   => 'group_message_type',
			'value' => $_POST['type'],
		),
		array(
			'key'   => 'message_users_ids',
			'value' => $message_users_ids,
		),
	);

	if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) {

		$thread_id                    = 0;
		$_POST['message_thread_type'] = 'reply';

		while ( bp_message_threads() ) :
			bp_message_thread();
			$thread_id = bp_get_message_thread_id();
			break;
		endwhile;

		$new_reply = messages_new_message( array(
			'thread_id' => $thread_id,
			'subject'   => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
			'content'   => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
			'date_sent' => $date_sent = bp_core_current_time(),
			'error_type' => 'wp_error',
		) );

		if ( is_wp_error( $new_reply ) ) {
			$response['feedback'] = $new_reply->get_error_message();
			wp_send_json_error( $response );
		}  elseif ( !empty( $new_reply ) ) {
			$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
			$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'Click here.', 'buddyboss' ) . '</a>';
			$response['type']          = 'success';
			wp_send_json_success( $response );
		}

	} else  {

		$_POST['message_thread_type'] = 'new';

		if ( isset( $_POST['type'] ) && 'private' === $_POST['type'] && is_array( $members ) ) {
			foreach ( $members as $member ) {
				// Attempt to send the message.
				$send = messages_new_message( array(
					'recipients' => $member,
					'subject'    => wp_trim_words($_POST['content'], messages_get_default_subject_length()),
					'content'    => $_POST['content'],
					'error_type' => 'wp_error',
				) );
			}
		} else {
			// Attempt to send the message.
			$send = messages_new_message( array(
				'recipients' => $members,
				'subject'    => wp_trim_words($_POST['content'], messages_get_default_subject_length()),
				'content'    => $_POST['content'],
				'error_type' => 'wp_error',
			) );
		}

		if ( is_wp_error( $send ) ) {
			$response['feedback'] = $send->get_error_message();
			wp_send_json_error( $response );
		}  elseif ( !empty( $send ) ) {
			$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
			$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'Click here.', 'buddyboss' ) . '</a>';
			$response['type']          = 'success';

			wp_send_json_success( $response );
		}
	}

}
