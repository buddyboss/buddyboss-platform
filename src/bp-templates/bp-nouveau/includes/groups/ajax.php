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
		array( 'groups_filter'                      => array( 'function' => 'bp_nouveau_ajax_object_template_loader', 'nopriv' => true  ) ),
		array( 'groups_join_group'                  => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_leave_group'                 => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_accept_invite'               => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_reject_invite'               => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_request_membership'          => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_get_group_potential_invites' => array( 'function' => 'bp_nouveau_ajax_get_users_to_invite', 'nopriv' => false ) ),
		array( 'groups_send_group_invites'          => array( 'function' => 'bp_nouveau_ajax_send_group_invites', 'nopriv' => false ) ),
		array( 'groups_delete_group_invite'         => array( 'function' => 'bp_nouveau_ajax_remove_group_invite', 'nopriv' => false ) ),
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
			$group_type_id             = bp_group_get_group_type_id( $group_type );
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
						wp_send_json_error(
							array(
								'feedback' => __( 'No members found in parent group.', 'buddyboss' ),
								'type'     => 'info',
							)
						);
					}
				}
			}
		}
		//}

		// Exclude users if ( Restrict invites if user already in other same group type ) is checked
		if ( false !== $group_type ) {
			$group_type_id                         = bp_group_get_group_type_id( $group_type );
			$meta                                  = get_post_custom( $group_type_id );
			$get_restrict_invites_same_group_types = isset( $meta['_bp_group_type_restrict_invites_user_same_group_type'] ) ? intval( $meta['_bp_group_type_restrict_invites_user_same_group_type'][0] ) : 0;
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
	$message = __( 'Select members to invite by clicking the + button next to each member.', 'buddyboss' );

	if ( 'friends' === $request['scope'] ) {
		$request['user_id'] = bp_loggedin_user_id();
		$bp->groups->invites_scope = 'friends';
		$message = __( 'Select which connections to invite by clicking the + button next to each member.', 'buddyboss' );
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
			'feedback' => __( 'All site members have already joined this group.', 'buddyboss' ),
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

	$total_page = (int) $potential_invites->meta['total_page'];
	$page       = ( isset( $_POST ) && '' !== $_POST['page'] && ! is_null( $_POST['page'] ) ) ? (int) $_POST['page'] : 1;
	$html = '';
	ob_start();

	foreach ( $potential_invites->users as $user ) {
		?>
		<li class="<?php  echo $user['id']; ?>">
			<div class="item-avatar">
				<img src="<?php echo $user['avatar']; ?>" class="avatar" alt="" />
			</div>

			<div class="item">
				<div class="list-title member-name">
					<?php echo $user['name']; ?>
				</div>

				<?php if ( isset( $user ) && isset( $user['is_sent'] ) && '' !== $user['is_sent'] ) {  ?>
					<div class="item-meta">
						<?php if ( isset( $user ) && isset( $user['invited_by'] ) && '' !== $user['invited_by'] ) {  ?>
						<ul class="group-inviters">
							<li><?php esc_html_e( 'Invited by:', 'buddyboss' ); ?></li>
							<?php foreach ( $user['invited_by'] as $inviter ) { ?>
							<li>
								<a href="<?php echo $inviter['user_link']; ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo $inviter['user_name']; ?>">
									<img src="<?php echo $inviter['avatar']; ?>" width="30px" class="avatar mini" alt="<?php echo $inviter['user_name']; ?>">
								</a>
							</li>
							<?php } ?>
						</ul>
						<?php } ?>
						<p class="status">
							<?php if ( isset( $user ) && isset( $user['is_sent'] ) && '' !== $user['is_sent'] && false === $user['is_sent'] ) {  ?>
							<?php esc_html_e( 'The invite has not been sent.', 'buddyboss' ); ?>
							<?php } else { ?>
							<?php esc_html_e( 'The invite has been sent.', 'buddyboss' ); ?>
							<?php } ?>
						</p>
					</div>
				<?php } ?>
			</div>
			<div class="action">
				<?php if ( empty( $user['is_sent'] ) || ( false === $user['is_sent'] && true === $user['is_sent'] ) ) { ?>
				<button data-bp-user-id="<?php echo $user['id']; ?>" data-bp-user-name="<?php echo $user['name']; ?>" type="button" class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons<?php if ( $user['selected'] ) { ?> selected<?php } ?>" data-bp-tooltip-pos="left" data-bp-tooltip="<?php if ( $user['selected'] ) { ?><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?><?php } else { ?><?php esc_attr_e( 'Invite', 'buddyboss' ); ?><?php } ?>">
					<span class="icons" aria-hidden="true"></span>
					<span class="bp-screen-reader-text">
						<?php if ( $user['selected'] ) { ?>
							<?php esc_html_e( 'Cancel invitation', 'buddyboss' ); ?>
						<?php } else { ?>
							<?php esc_html_e( 'Invite', 'buddyboss' ); ?>
						<?php } ?>
					</span>
				</button>
				<?php } ?>

				<?php
				if ( isset( $user['can_edit'] ) && true === $user['can_edit'] ) {
					if ( 'invited' === $request['scope'] ) {
						?>
						<button data-bp-user-id="<?php echo $user['id']; ?>" data-bp-user-name="<?php echo $user['name']; ?>" type="button" class="button remove-button group-remove-invite-button bp-tooltip bp-icons" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?>">
							<span class=" icons" aria-hidden="true"></span>
							<span class="bp-screen-reader-text"><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?></span>
						</button>
						<?php
					}  else {
						?>
						<button data-bp-user-id="<?php echo $user['id']; ?>" data-bp-user-name="<?php echo $user['name']; ?>" type="button" class="button invite-button group-remove-invite-button bp-tooltip bp-icons" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?>">
							<span class=" icons" aria-hidden="true"></span>
							<span class="bp-screen-reader-text"><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?></span>
						</button>
						<?php
					}
					?>
				<?php } ?>
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

	$potential_invites->html = $html;
	$paginate   = '';

	ob_start();

	if ( $total_page > 1 ) {
		if ( 1 !== $page ) { ?>
			<a href="javascript:void(0);" id="bp-group-invites-prev-page" class="button group-invite-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Previous page',
				'buddyboss' ); ?>"> <span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span> </a>
		<?php }


		if ( $total_page !== $page ) {
			$page = $page + 1;
			?>
			<a href="javascript:void(0);" id="bp-group-invites-next-page" class="button group-invite-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Next page',
				'buddyboss' ); ?>"> <span class="bp-screen-reader-text"><?php esc_html_e( 'Next page',
						'buddyboss' ); ?></span>
				<span class="dashicons dashicons-arrow-right" aria-hidden="true"></span> </a>
		<?php }
	}

	$paginate = ob_get_contents();
	ob_clean();

	// Set a message to explain use of the current scope
	$potential_invites->feedback = $message;

	// Set a pagination
	$potential_invites->pagination = $paginate;
	$potential_invites->page       = $page;

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
