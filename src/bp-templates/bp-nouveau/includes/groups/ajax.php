<?php
/**
 * Groups Ajax functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'admin_init', function () {

	$ajax_actions = array(
		array( 'groups_filter' => array( 'function' => 'bp_nouveau_ajax_object_template_loader', 'nopriv' => true ) ),
		array( 'groups_join_group' => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_leave_group' => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_accept_invite' => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array( 'groups_reject_invite' => array( 'function' => 'bp_nouveau_ajax_joinleave_group', 'nopriv' => false ) ),
		array(
			'groups_request_membership' => array(
				'function' => 'bp_nouveau_ajax_joinleave_group',
				'nopriv'   => false
			)
		),
		array(
			'groups_get_group_potential_invites' => array(
				'function' => 'bp_nouveau_ajax_get_users_to_invite',
				'nopriv'   => false
			)
		),
		array(
			'groups_get_group_members_listing' => array(
				'function' => 'bp_nouveau_ajax_groups_get_group_members_listing',
				'nopriv'   => false
			)
		),
		array(
			'groups_get_group_members_send_message' => array(
				'function' => 'bp_nouveau_ajax_groups_send_message',
				'nopriv'   => false
			)
		),
		array(
			'groups_send_group_invites' => array(
				'function' => 'bp_nouveau_ajax_send_group_invites',
				'nopriv'   => false
			)
		),
		array(
			'groups_delete_group_invite' => array(
				'function' => 'bp_nouveau_ajax_remove_group_invite',
				'nopriv'   => false
			)
		),
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
 * @return string HTML
 * @since BuddyPress 3.0.0
 *
 */
function bp_nouveau_ajax_joinleave_group() {
	$response = array(
		'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' ) ),
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
					'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Group invitation could not be accepted.', 'buddyboss' ) ),
					'type'     => 'error',
				);

			} else {
				if ( bp_is_active( 'activity' ) ) {
					groups_record_activity( array(
							'type'    => 'joined_group',
							'item_id' => $group->id,
						) );
				}

				// User is now a member of the group
				$group->is_member = '1';

				$response = array(
					'feedback' => sprintf( '<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Group invite accepted.', 'buddyboss' ) ),
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
					'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Group invite could not be rejected', 'buddyboss' ) ),
					'type'     => 'error',
				);
			} else {
				$response = array(
					'feedback' => sprintf( '<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Group invite rejected', 'buddyboss' ) ),
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
					'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Error joining this group.', 'buddyboss' ) ),
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
					'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Error requesting membership.', 'buddyboss' ) ),
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
					'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Error leaving group.', 'buddyboss' ) ),
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
			$group_type_id                         = bp_group_get_group_type_id( $group_type );
			$meta                                  = get_post_custom( $group_type_id );
			$get_restrict_invites_same_group_types = isset( $meta['_bp_group_type_restrict_invites_user_same_group_type'] ) ? intval( $meta['_bp_group_type_restrict_invites_user_same_group_type'][0] ) : 0;
			if ( 1 === $get_restrict_invites_same_group_types ) {
				$group_arr = bp_get_group_ids_by_group_types( $group_type );
				if ( isset( $group_arr ) && ! empty( $group_arr ) ) {
					$group_arr = wp_list_pluck( $group_arr, 'id' );
					if ( ( $key = array_search( $request['group_id'], $group_arr ) ) !== false ) {
						unset( $group_arr[ $key ] );
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
	$message                   = __( 'Select members to invite by clicking the + button next to each member.', 'buddyboss' );

	if ( 'friends' === $request['scope'] ) {
		$request['user_id']        = bp_loggedin_user_id();
		$bp->groups->invites_scope = 'friends';
		$message                   = __( 'Select which connections to invite by clicking the + button next to each member.', 'buddyboss' );
	}

	if ( 'invited' === $request['scope'] ) {

		if ( ! bp_group_has_invites( array( 'user_id' => 'any', 'group_id' => $request['group_id'] ) ) ) {

			if ( isset( $request ) && isset( $request['search_terms'] ) && '' !== $request['search_terms'] ) {
				// This message displays if you search in pending invites screen and if no results found in search.
				wp_send_json_error( array(
					'feedback' => __( 'All members already received invitations.', 'buddyboss' ),
					'type'     => 'info',
				) );
			} else {
				// This message displays when pending invites screen doesn't have any users invitation.
				wp_send_json_error( array(
					'feedback' => __( 'No pending group invitations found.', 'buddyboss' ),
					'type'     => 'info',
				) );
			}
		}

		$request['is_confirmed']   = false;
		$bp->groups->invites_scope = 'invited';
		$message                   = __( 'You can view the group\'s pending invitations from this screen.', 'buddyboss' );
	}

	$potential_invites = bp_nouveau_get_group_potential_invites( $request );

	if ( empty( $potential_invites->users ) ) {
		if ( isset( $request ) && isset( $request['search_terms'] ) && '' !== $request['search_terms'] ) {
			// This message displays if you search in Pending Invites screen and if no results found.
			$error = array(
				'feedback' => __( 'No members found.', 'buddyboss' ),
				'type'     => 'info',
			);
		} else {
			if ( isset( $request ) && isset( $request['search_terms'] ) && '' !== $request['search_terms'] && 'members' === $bp->groups->invites_scope ) {
				// This message displays in Send Invites screen in Members tab, if you search members and if no results found.
				$error = array(
					'feedback' => __( 'No members found.', 'buddyboss' ),
					'type'     => 'info',
				);
			} elseif ( isset( $request ) && ! isset( $request['search_terms'] ) && 'members' === $bp->groups->invites_scope ) {
				// This message displays when all site members are in the group, already invited or already requested to join.
				$error = array(
					'feedback' => __( 'All site members are already members of this group, or have already received an invite to join this group, or have requested to join it.', 'buddyboss' ),
					'type'     => 'info',
				);
			} else {
				// General default message.
				$error = array(
					'feedback' => __( 'No members found.', 'buddyboss' ),
					'type'     => 'info',
				);
			}

		}

		if ( 'friends' === $bp->groups->invites_scope ) {
			if ( isset( $request ) && isset( $request['search_terms'] ) && '' !== $request['search_terms'] ) {
				// This message displays if you search in Send Invites screen and if no results found.
				$error = array(
					'feedback' => __( 'No members found.', 'buddyboss' ),
					'type'     => 'info',
				);
			} else {
				// This message displays when all of your connections are in the group, already invited or already requested to join.
				$error = array(
					'feedback' => __( 'All your connections are already members of this group, or have already received an invite to join this group, or have requested to join it.', 'buddyboss' ),
					'type'     => 'info',
				);
			}

			// This message displays if you have no connections.
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
	$html       = '';
	ob_start();

	foreach ( $potential_invites->users as $user ) {
		?>
		<li class="<?php echo $user['id']; ?>">
			<div class="item-avatar">
				<a href="<?php echo esc_url( bp_core_get_user_domain( $user['id'] ) ); ?>">
					<img src="<?php echo $user['avatar']; ?>" class="avatar" alt=""/> </a>
			</div>

			<div class="item">
				<div class="list-title member-name">
					<a href="<?php echo esc_url( bp_core_get_user_domain( $user['id'] ) ); ?>">
						<?php echo $user['name']; ?>
					</a>
				</div>

				<?php if ( isset( $user ) && isset( $user['is_sent'] ) && '' !== $user['is_sent'] ) { ?>
					<div class="item-meta">
						<?php if ( isset( $user ) && isset( $user['invited_by'] ) && '' !== $user['invited_by'] ) { ?>
							<ul class="group-inviters">
								<li><?php esc_html_e( 'Invited by:', 'buddyboss' ); ?></li>
								<?php foreach ( $user['invited_by'] as $inviter ) { ?>
									<li>
										<a href="<?php echo $inviter['user_link']; ?>" class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo $inviter['name']; ?>">
											<img src="<?php echo $inviter['avatar']; ?>" width="30px" class="avatar mini" alt="<?php echo $inviter['name']; ?>">
										</a>
									</li>
								<?php } ?>
							</ul>
						<?php } ?>
						<p class="status">
							<?php if ( isset( $user ) && isset( $user['is_sent'] ) && '' !== $user['is_sent'] && false === $user['is_sent'] ) { ?><?php esc_html_e( 'The invite has not been sent.', 'buddyboss' ); ?><?php } else { ?><?php esc_html_e( 'The invite has been sent.', 'buddyboss' ); ?><?php } ?>
						</p>
					</div>
				<?php } ?>
			</div>
			<div class="action">
				<?php if ( empty( $user['is_sent'] ) || ( false === $user['is_sent'] && true === $user['is_sent'] ) ) { ?>
					<button data-bp-user-id="<?php echo $user['id']; ?>" data-bp-user-name="<?php echo $user['name']; ?>" type="button" class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons<?php if ( $user['selected'] ) { ?> selected<?php } ?>" data-bp-tooltip-pos="left" data-bp-tooltip="<?php if ( $user['selected'] ) { ?><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?><?php } else { ?><?php esc_attr_e( 'Invite', 'buddyboss' ); ?><?php } ?>">
						<span class="icons" aria-hidden="true"></span> <span class="bp-screen-reader-text">
						<?php if ( $user['selected'] ) { ?><?php esc_html_e( 'Cancel invitation', 'buddyboss' ); ?><?php } else { ?><?php esc_html_e( 'Invite', 'buddyboss' ); ?><?php } ?>
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
					} else {
						?>
						<button data-bp-user-id="<?php echo $user['id']; ?>" data-bp-user-name="<?php echo $user['name']; ?>" type="button" class="button invite-button group-remove-invite-button bp-tooltip bp-icons" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?>">
							<span class=" icons" aria-hidden="true"></span>
							<span class="bp-screen-reader-text"><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?></span>
						</button>
						<?php
					}
					?><?php } ?>
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
	$paginate                = '';

	ob_start();

	if ( $total_page > 1 ) {
		if ( 1 !== $page ) { ?>
			<a href="javascript:void(0);" id="bp-group-invites-prev-page" class="button group-invite-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Previous page', 'buddyboss' ); ?>">
				<span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span> </a>
		<?php }


		if ( $total_page !== $page ) {
			$page = $page + 1;
			?>
			<a href="javascript:void(0);" id="bp-group-invites-next-page" class="button group-invite-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Next page', 'buddyboss' ); ?>"> <span class="bp-screen-reader-text"><?php esc_html_e( 'Next page', 'buddyboss' ); ?></span>
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

	$group_id = bp_get_current_group_id() ?: $_POST['group_id'];

	if ( bp_is_group_create() && ! empty( $_POST['group_id'] ) ) {
		$group_id = (int) $_POST['group_id'];
	}

	if ( ! bp_groups_user_can_send_invites( $group_id ) ) {
		$response['feedback'] = __( 'You are not allowed to send invitations for this group.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['users'] ) ) {
		$response['feedback'] = __( 'Please select members to send invitations for this group.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	if ( ! empty( $_POST['message'] ) ) {
		$bp->groups->invites_message = wp_kses( wp_unslash( $_POST['message'] ), array() );

		add_filter( 'groups_notification_group_invites_message', 'bp_nouveau_groups_invites_custom_message', 10, 1 );
	}

	// For feedback
	$invited = array();

	foreach ( (array) $_POST['users'] as $user_id ) {
		$invited[ (int) $user_id ] = groups_invite_user( array(
				'user_id'  => $user_id,
				'group_id' => $group_id,
			) );
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
		$error_message = sprintf( /* translators: count of users affected */ _n( 'Invitation failed for %s user.', 'Invitation failed for %s users.', $error_count, 'buddyboss' ), number_format_i18n( $error_count ) );

		wp_send_json_error( array(
				'feedback' => $error_message,
				'users'    => $errors,
				'type'     => 'error',
			) );
	}

	wp_send_json_success( array(
			'feedback' => __( 'Invitations sent.', 'buddyboss' ),
			'type'     => 'success',
		) );
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
		wp_send_json_error( array(
				'feedback' => __( 'Group invitation could not be removed.', 'buddyboss' ),
				'type'     => 'error',
			) );
	}

	if ( BP_Groups_Member::check_for_membership_request( $user_id, $group_id ) ) {
		wp_send_json_error( array(
				'feedback' => __( 'The member is already a member of the group.', 'buddyboss' ),
				'type'     => 'warning',
				'code'     => 1,
			) );
	}

	// Remove the unsent invitation.
	if ( ! groups_uninvite_user( $user_id, $group_id ) ) {
		wp_send_json_error( array(
				'feedback' => __( 'Group invitation could not be removed.', 'buddyboss' ),
				'type'     => 'error',
				'code'     => 0,
			) );
	}

	wp_send_json_success( array(
			'feedback'    => __( 'There are no more pending invitations for the group.', 'buddyboss' ),
			'type'        => 'info',
			'has_invites' => bp_group_has_invites( array( 'user_id' => 'any' ) ),
		) );
}

/**
 * Retrieve the possible members list to send group message.
 *
 * @since BuddyBoss 1.2.9
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
			'results' => 'no_member',
		] );
	} else {
		$total_page = (int) ceil( (int) $group_members['count'] / $per_page );
		ob_start();
		foreach ( $group_members['members'] as $member ) {

			$image = htmlspecialchars_decode( bp_core_fetch_avatar( array(
				'item_id' => $member->ID,
				'object'  => 'user',
				'type'    => 'thumb',
				'class'   => '',
			) ) );

			$name = bp_core_get_user_displayname( $member->ID );
			?>
			<li class="group-message-member-li <?php echo $member->ID; ?>">
				<div class="item-avatar">
					<a href="<?php echo esc_url( bp_core_get_user_domain( $member->ID ) ); ?>">
						<?php echo $image; ?>
					</a>
				</div>
				<div class="item">
					<div class="list-title member-name">
						<a href="<?php echo esc_url( bp_core_get_user_domain( $member->ID ) ); ?>">
							<?php echo $name; ?>
						</a>
					</div>
				</div>
				<div class="action">
					<button type="button" class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons" data-bp-user-id="<?php echo esc_attr( $member->ID ); ?>" data-bp-user-name="<?php echo esc_attr( $name ); ?>" data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Add Recipient', 'buddyboss' ); ?>">
						<span class="icons" aria-hidden="true"></span> <span class="bp-screen-reader-text">
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
				<a href="javascript:void(0);" id="bp-group-messages-prev-page" class="button group-message-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Previous page', 'buddyboss' ); ?>">
					<span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span> </a>
			<?php }

			if ( $total_page !== (int) $_POST['page'] ) {
				$page = $page + 1;
				?>
				<a href="javascript:void(0);" id="bp-group-messages-next-page" class="button group-message-button bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Next page', 'buddyboss' ); ?>"> <span class="bp-screen-reader-text"><?php esc_html_e( 'Next page', 'buddyboss' ); ?></span>
					<span class="dashicons dashicons-arrow-right" aria-hidden="true"></span> </a>
			<?php }

			$paginate = ob_get_contents();
			ob_clean();

		}

		$html        = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_html', $html );
		$total_page  = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_total_page', $total_page );
		$page        = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_page', $page );
		$paginate    = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_paginate', $paginate );
		$total_count = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_total', $group_members['count'] );

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
 * @since BuddyBoss 1.2.9
 */
function bp_nouveau_ajax_groups_send_message() {

	global $wpdb, $bp;

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
		$_POST['gif_data'] = json_decode( wp_kses_stripslashes( $_POST['gif'] ), true );
	}

	if ( isset( $_POST['media'] ) && '' !== $_POST['media'] ) {
		$_POST['media'] = json_decode( wp_kses_stripslashes( $_POST['media'] ), true );
	}

	// Get Members list if "All Group Members" selected.
	if ( 'all' === $_POST['users'] ) {

		// Fetch all the group members.
		$args = array(
			'per_page'            => 9999999999999999999,
			'group'               => $_POST['group'],
			'exclude'             => array( bp_loggedin_user_id() ),
			'exclude_admins_mods' => false,
		);

		$group_members = groups_get_group_members( $args );
		$members       = wp_list_pluck( $group_members['members'], 'ID' );

		// We get members array from $_POST['users_list'] because user already selected them.
	} else {

		$members = $_POST['users_list'];

	}

	if ( empty( $members ) ) {
		$response['feedback'] = 'No Members Selected.';
		wp_send_json_error( $response );
	}

	$group         = ( isset( $_POST ) && isset( $_POST['group'] ) && '' !== $_POST['group'] ) ? trim( $_POST['group'] ) : ''; // Group id
	$message_users = ( isset( $_POST ) && isset( $_POST['users'] ) && '' !== $_POST['users'] ) ? trim( $_POST['users'] ) : ''; // all - individual
	$message_type  = ( isset( $_POST ) && isset( $_POST['type'] ) && '' !== $_POST['type'] ) ? trim( $_POST['type'] ) : ''; // open - private

	if ( empty( $group ) ) {
		$response['feedback'] = 'No group Selected.';
		wp_send_json_error( $response );
	}

	// If "Group Thread" selected.
	if ( 'open' === $message_type ) {

		// "All Group Members" selected.
		if ( 'all' === $message_users ) {

			// Comma separated members list to find in meta query.
			$message_users_ids = implode( ',', $members );

			// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "message_users_ids"
			$_POST['message_meta_users_list'] = $message_users_ids;

			$group_thread = groups_get_groupmeta( (int) $group, 'group_message_thread' );
			$is_deleted   = false;
			if ( '' !== $group_thread ) {
				$total_threads = $wpdb->get_results( $wpdb->prepare( "SELECT is_deleted FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", (int) $group_thread ) ); // db call ok; no-cache ok;
				foreach ( $total_threads as $thread ) {
					if ( 1 === (int) $thread->is_deleted ) {
						$is_deleted = true;
						break;
					}
				}

				if ( $is_deleted ) {

					// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
					$_POST['message_thread_type'] = 'new';

					remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					// Attempt to send the message.
					$send = messages_new_message( array(
						'recipients'    => $members,
						'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
						'content'       => $_POST['content'],
						'error_type'    => 'wp_error',
						'append_thread' => false,
					) );
					remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

					if ( is_wp_error( $send ) ) {
						$response['feedback'] = $send->get_error_message();
						wp_send_json_error( $response );
					} elseif ( ! empty( $send ) ) {
						groups_update_groupmeta( (int) $group, 'group_message_thread', $send );
						$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
						$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
						$response['type']          = 'success';
						wp_send_json_success( $response );
					}

				}

			}
			if ( '' !== $group_thread && ! $is_deleted ) {
				// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
				$_POST['message_thread_type'] = 'reply';

				remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
				add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
				$new_reply = messages_new_message( array(
					'thread_id'  => $group_thread,
					'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
					'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
					'date_sent'  => $date_sent = bp_core_current_time(),
					'error_type' => 'wp_error',
				) );
				remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
				add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

				if ( is_wp_error( $new_reply ) ) {
					$response['feedback'] = $new_reply->get_error_message();
					wp_send_json_error( $response );
				} elseif ( ! empty( $new_reply ) ) {
					$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
					$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
					$response['type']          = 'success';
					wp_send_json_success( $response );
				}

			} else {

				$meta = array(
					array(
						'key'     => 'group_id',
						'value'   => $group,
						'compare' => '=',
					),
					array(
						'key'     => 'group_message_users',
						'value'   => 'all',
						'compare' => '=',
					),
					array(
						'key'     => 'group_message_type',
						'value'   => 'open',
						'compare' => '=',
					),
					array(
						'key'   => 'message_users_ids',
						'value' => $message_users_ids,
					),
				);

				// Check if there is already previously group thread created.
				if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) {

					$thread_id = 0;

					while ( bp_message_threads() ) :
						bp_message_thread();
						$thread_id = bp_get_message_thread_id();

						if ( $thread_id ) {
							break;
						}

					endwhile;

					// If $thread_id found then add as a reply to that thread.
					if ( $thread_id ) {

						groups_update_groupmeta( (int) $group, 'group_message_thread', $thread_id );

						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'reply';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						$new_reply = messages_new_message( array(
							'thread_id'  => $thread_id,
							'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'date_sent'  => $date_sent = bp_core_current_time(),
							'error_type' => 'wp_error',
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

						if ( is_wp_error( $new_reply ) ) {
							$response['feedback'] = $new_reply->get_error_message();
							wp_send_json_error( $response );
						} elseif ( ! empty( $new_reply ) ) {
							$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
							$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
							$response['type']          = 'success';
							wp_send_json_success( $response );
						}

						// Create a new group thread.
					} else {

						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'new';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						// Attempt to send the message.
						$send = messages_new_message( array(
							'recipients'    => $members,
							'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
							'content'       => $_POST['content'],
							'error_type'    => 'wp_error',
							'append_thread' => false,
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

						if ( is_wp_error( $send ) ) {
							$response['feedback'] = $send->get_error_message();
							wp_send_json_error( $response );
						} elseif ( ! empty( $send ) ) {

							groups_update_groupmeta( (int) $group, 'group_message_thread', $send );

							$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
							$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
							$response['type']          = 'success';
							wp_send_json_success( $response );
						}

					}

					// Create a new group thread.
				} else {

					// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
					$_POST['message_thread_type'] = 'new';

					remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					// Attempt to send the message.
					$send = messages_new_message( array(
						'recipients'    => $members,
						'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
						'content'       => $_POST['content'],
						'error_type'    => 'wp_error',
						'append_thread' => false,
					) );
					remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

					if ( is_wp_error( $send ) ) {
						$response['feedback'] = $send->get_error_message();
						wp_send_json_error( $response );
					} elseif ( ! empty( $send ) ) {
						groups_update_groupmeta( (int) $group, 'group_message_thread', $send );
						$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
						$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
						$response['type']          = 'success';
						wp_send_json_success( $response );
					}
				}
			}
			// "Individual Members" Selected.
		} else {

			$meta = array(
				array(
					'key'     => 'group_message_type',
					'value'   => 'open',
					'compare' => '!=',
				),
			);

			// Check if there is already previously individual group thread created.
			if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) {

				$thread_id = 0;

				while ( bp_message_threads() ) :
					bp_message_thread();
					$thread_id = bp_get_message_thread_id();

					if ( $thread_id ) {

						// get the thread recipients.
						$thread                     = new BP_Messages_Thread( $thread_id );
						$thread_recipients          = $thread->get_recipients();
						$previous_thread_recipients = array();

						// Store thread recipients to $previous_ids array.
						foreach ( $thread_recipients as $thread_recipient ) {
							if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
								$previous_thread_recipients[] = $thread_recipient->user_id;
							}
						}

						$current_recipients = array();
						$current_recipients = $members;
						$members_recipients = array();

						// Store current recipients to $members array.
						foreach ( $current_recipients as $single_recipients ) {
							$members_recipients[] = (int) $single_recipients;
						}

						// check both previous and current recipients are same.
						$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members_recipients ) && count( $previous_thread_recipients ) === count( $members_recipients ) && array_diff( $previous_thread_recipients, $members_recipients ) === array_diff( $members_recipients, $previous_thread_recipients ) );

						$group_thread = (int) groups_get_groupmeta( (int) $group, 'group_message_thread' );

						// If recipients are matched.
						if ( $is_recipient_match && (int) $thread_id !== $group_thread ) {
							break;
						}
					}

				endwhile;

				// If $thread_id found then add as a reply to that thread.
				if ( $thread_id ) {

					// get the thread recipients.
					$thread                     = new BP_Messages_Thread( $thread_id );
					$thread_recipients          = $thread->get_recipients();
					$previous_thread_recipients = array();

					$last_message = BP_Messages_Thread::get_last_message( $thread_id );
					$message_type = bp_messages_get_meta( $last_message->id, 'group_message_users', true );

					// Store thread recipients to $previous_ids array.
					foreach ( $thread_recipients as $thread_recipient ) {
						if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
							$previous_thread_recipients[] = $thread_recipient->user_id;
						}
					}

					$current_recipients = array();
					$current_recipients = $members;
					$members_recipients = array();

					// Store current recipients to $members array.
					foreach ( $current_recipients as $single_recipients ) {
						$members_recipients[] = (int) $single_recipients;
					}

					// check both previous and current recipients are same.
					$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members_recipients ) && count( $previous_thread_recipients ) === count( $members_recipients ) && array_diff( $previous_thread_recipients, $members_recipients ) === array_diff( $members_recipients, $previous_thread_recipients ) );

					$group_thread = (int) groups_get_groupmeta( (int) $group, 'group_message_thread' );

					// If recipients are matched.
					if ( $is_recipient_match && (int) $thread_id !== $group_thread ) {

						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'reply';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						$new_reply = messages_new_message( array(
							'thread_id'  => $thread_id,
							'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'date_sent'  => $date_sent = bp_core_current_time(),
							'error_type' => 'wp_error',
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

						if ( is_wp_error( $new_reply ) ) {
							$response['feedback'] = $new_reply->get_error_message();
							wp_send_json_error( $response );
						} elseif ( ! empty( $new_reply ) ) {
							$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
							$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
							$response['type']          = 'success';
							wp_send_json_success( $response );
						}

						// Else recipients not matched.
					} else {

						$previous_threads = BP_Messages_Message::get_existing_threads( $members, bp_loggedin_user_id() );
						$existing_thread  = 0;
						if ( $previous_threads ) {
							foreach ( $previous_threads as $thread ) {

								$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );
								if ( $is_active_recipient ) {

									// get the thread recipients.
									$thread                     = new BP_Messages_Thread( $thread->thread_id );
									$thread_recipients          = $thread->get_recipients();
									$previous_thread_recipients = array();

									// Store thread recipients to $previous_ids array.
									foreach ( $thread_recipients as $thread_recipient ) {
										if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
											$previous_thread_recipients[] = $thread_recipient->user_id;
										}
									}

									$current_recipients = array();
									$current_recipients = $members;
									$members            = array();

									// Store current recipients to $members array.
									foreach ( $current_recipients as $single_recipients ) {
										$members[] = (int) $single_recipients;
									}

									// check both previous and current recipients are same.
									$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members ) && count( $previous_thread_recipients ) === count( $members ) && array_diff( $previous_thread_recipients, $members ) === array_diff( $members, $previous_thread_recipients ) );

									// check any messages of this thread should not be a open & all.
									$message_ids  = wp_list_pluck( $thread->messages, 'id' );
									$add_existing = true;
									foreach ( $message_ids as $id ) {
										// group_message_users not open
										$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual
										if ( 'all' === $message_users ) {
											$add_existing = false;
											break;
										}
									}
									// If recipients are matched.
									if ( $is_recipient_match && $add_existing ) {
										$existing_thread = (int) $thread->thread_id;
									}
								}
							}

							if ( $existing_thread > 0 ) {
								// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
								$_POST['message_thread_type'] = 'reply';

								remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
								add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
								$new_reply = messages_new_message( array(
									'thread_id'  => $existing_thread,
									'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
									'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
									'date_sent'  => $date_sent = bp_core_current_time(),
									'error_type' => 'wp_error',
								) );
								remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
								add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

								if ( is_wp_error( $new_reply ) ) {
									$response['feedback'] = $new_reply->get_error_message();
									wp_send_json_error( $response );
								} elseif ( ! empty( $new_reply ) ) {
									$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
									$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
									$response['type']          = 'success';
									wp_send_json_success( $response );
								}
							} else {
								// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
								$_POST['message_thread_type'] = 'new';

								remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
								add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
								// Attempt to send the message.
								$send = messages_new_message( array(
									'recipients'    => $members,
									'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
									'content'       => $_POST['content'],
									'error_type'    => 'wp_error',
									'append_thread' => false,
								) );
								remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
								add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

								if ( is_wp_error( $send ) ) {
									$response['feedback'] = $send->get_error_message();
									wp_send_json_error( $response );
								} elseif ( ! empty( $send ) ) {
									$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
									$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
									$response['type']          = 'success';
									wp_send_json_success( $response );
								}
							}
						} else {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
							$_POST['message_thread_type'] = 'new';

							remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							// Attempt to send the message.
							$send = messages_new_message( array(
								'recipients'    => $members,
								'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
								'content'       => $_POST['content'],
								'error_type'    => 'wp_error',
								'append_thread' => false,
							) );
							remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

							if ( is_wp_error( $send ) ) {
								$response['feedback'] = $send->get_error_message();
								wp_send_json_error( $response );
							} elseif ( ! empty( $send ) ) {
								$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
								$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
								$response['type']          = 'success';
								wp_send_json_success( $response );
							}
						}
					}
					// Else no thread found.
				} else {

					$previous_threads = BP_Messages_Message::get_existing_threads( $members, bp_loggedin_user_id() );
					$existing_thread  = 0;
					if ( $previous_threads ) {
						foreach ( $previous_threads as $thread ) {
							$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );
							if ( $is_active_recipient ) {

								// get the thread recipients.
								$thread                     = new BP_Messages_Thread( $thread->thread_id );
								$thread_recipients          = $thread->get_recipients();
								$previous_thread_recipients = array();

								// Store thread recipients to $previous_ids array.
								foreach ( $thread_recipients as $thread_recipient ) {
									if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
										$previous_thread_recipients[] = $thread_recipient->user_id;
									}
								}

								$current_recipients = array();
								$current_recipients = $members;
								$members            = array();

								// Store current recipients to $members array.
								foreach ( $current_recipients as $single_recipients ) {
									$members[] = (int) $single_recipients;
								}

								// check both previous and current recipients are same.
								$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members ) && count( $previous_thread_recipients ) === count( $members ) && array_diff( $previous_thread_recipients, $members ) === array_diff( $members, $previous_thread_recipients ) );

								// check any messages of this thread should not be a open & all.
								$message_ids  = wp_list_pluck( $thread->messages, 'id' );
								$add_existing = true;
								foreach ( $message_ids as $id ) {
									// group_message_users not open
									$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual
									if ( 'all' === $message_users ) {
										$add_existing = false;
										break;
									}
								}
								// If recipients are matched.
								if ( $is_recipient_match && $add_existing ) {
									$existing_thread = (int) $thread->thread_id;
								}
							}
						}
						if ( $existing_thread > 0 ) {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
							$_POST['message_thread_type'] = 'reply';

							remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							$new_reply = messages_new_message( array(
								'thread_id'  => $existing_thread,
								'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'date_sent'  => $date_sent = bp_core_current_time(),
								'error_type' => 'wp_error',
							) );
							remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

							if ( is_wp_error( $new_reply ) ) {
								$response['feedback'] = $new_reply->get_error_message();
								wp_send_json_error( $response );
							} elseif ( ! empty( $new_reply ) ) {
								$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
								$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
								$response['type']          = 'success';
								wp_send_json_success( $response );
							}
						} else {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
							$_POST['message_thread_type'] = 'new';

							remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							// Attempt to send the message.
							$send = messages_new_message( array(
								'recipients'    => $members,
								'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
								'content'       => $_POST['content'],
								'error_type'    => 'wp_error',
								'append_thread' => false,
							) );
							remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

							if ( is_wp_error( $send ) ) {
								$response['feedback'] = $send->get_error_message();
								wp_send_json_error( $response );
							} elseif ( ! empty( $send ) ) {
								$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
								$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
								$response['type']          = 'success';
								wp_send_json_success( $response );
							}
						}
					} else {
						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'new';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						// Attempt to send the message.
						$send = messages_new_message( array(
							'recipients'    => $members,
							'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
							'content'       => $_POST['content'],
							'error_type'    => 'wp_error',
							'append_thread' => false,
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

						if ( is_wp_error( $send ) ) {
							$response['feedback'] = $send->get_error_message();
							wp_send_json_error( $response );
						} elseif ( ! empty( $send ) ) {
							$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
							$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
							$response['type']          = 'success';
							wp_send_json_success( $response );
						}
					}
				}
				// Else no previous thread found.
			} else {

				$previous_threads = BP_Messages_Message::get_existing_threads( $members, bp_loggedin_user_id() );
				$existing_thread  = 0;
				if ( $previous_threads ) {
					foreach ( $previous_threads as $thread ) {

						$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );

						if ( $is_active_recipient ) {

							// get the thread recipients.
							$thread                     = new BP_Messages_Thread( $thread->thread_id );
							$thread_recipients          = $thread->get_recipients();
							$previous_thread_recipients = array();

							// Store thread recipients to $previous_ids array.
							foreach ( $thread_recipients as $thread_recipient ) {
								if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
									$previous_thread_recipients[] = $thread_recipient->user_id;
								}
							}

							$current_recipients = array();
							$current_recipients = $members;
							$members            = array();

							// Store current recipients to $members array.
							foreach ( $current_recipients as $single_recipients ) {
								$members[] = (int) $single_recipients;
							}

							// check both previous and current recipients are same.
							$is_recipient_match = ( is_array( $previous_thread_recipients ) && is_array( $members ) && count( $previous_thread_recipients ) === count( $members ) && array_diff( $previous_thread_recipients, $members ) === array_diff( $members, $previous_thread_recipients ) );

							// check any messages of this thread should not be a open & all.
							$message_ids  = wp_list_pluck( $thread->messages, 'id' );
							$add_existing = true;
							foreach ( $message_ids as $id ) {
								// group_message_users not open
								$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual
								if ( 'all' === $message_users ) {
									$add_existing = false;
									break;
								}
							}
							// If recipients are matched.
							if ( $is_recipient_match && $add_existing ) {
								$existing_thread = (int) $thread->thread_id;
							}
						}
					}

					if ( $existing_thread > 0 ) {
						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'reply';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						$new_reply = messages_new_message( array(
							'thread_id'  => $existing_thread,
							'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'date_sent'  => $date_sent = bp_core_current_time(),
							'error_type' => 'wp_error',
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

						if ( is_wp_error( $new_reply ) ) {
							$response['feedback'] = $new_reply->get_error_message();
							wp_send_json_error( $response );
						} elseif ( ! empty( $new_reply ) ) {
							$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
							$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
							$response['type']          = 'success';
							wp_send_json_success( $response );
						}
					} else {
						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'new';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						// Attempt to send the message.
						$send = messages_new_message( array(
							'recipients'    => $members,
							'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
							'content'       => $_POST['content'],
							'error_type'    => 'wp_error',
							'append_thread' => false,
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

						if ( is_wp_error( $send ) ) {
							$response['feedback'] = $send->get_error_message();
							wp_send_json_error( $response );
						} elseif ( ! empty( $send ) ) {
							$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
							$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
							$response['type']          = 'success';
							wp_send_json_success( $response );
						}
					}
				} else {

					// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
					$_POST['message_thread_type'] = 'new';

					remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					// Attempt to send the message.
					$send = messages_new_message( array(
						'recipients'    => $members,
						'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
						'content'       => $_POST['content'],
						'error_type'    => 'wp_error',
						'append_thread' => false,
					) );
					remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

					if ( is_wp_error( $send ) ) {
						$response['feedback'] = $send->get_error_message();
						wp_send_json_error( $response );
					} elseif ( ! empty( $send ) ) {
						$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
						$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
						$response['type']          = 'success';
						wp_send_json_success( $response );
					}
				}
			}
		}

		// Else "Private Reply (BCC)" selected.
	} else {

		// We have to send Message to all members to "Individual" message in both cases like "All Group Members" OR "Individual Members" selected.
		foreach ( $members as $member ) {

			if ( bp_loggedin_user_id() ) {
				$meta = array(
					array(
						'key'     => 'group_message_type',
						'value'   => 'open',
						'compare' => '!=',
					),
				);
			}

			$thread_loop_message_member = $member;
			$thread_loop_message_sent   = false;

			// Find existing thread which are private.
			if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) {

				$thread_id = 0;

				while ( bp_message_threads() ) : bp_message_thread();

					$thread_id = bp_get_message_thread_id();

					if ( $thread_id ) {

						// get the thread recipients.
						$thread                     = new BP_Messages_Thread( $thread_id );
						$thread_recipients          = $thread->get_recipients();
						$previous_thread_recipients = array();

						// Store thread recipients to $previous_ids array.
						foreach ( $thread_recipients as $thread_recipient ) {
							if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
								$previous_thread_recipients[] = $thread_recipient->user_id;
							}
						}

						$current_recipients   = array();
						$current_recipients[] = $thread_loop_message_member;
						$member_arr           = array();

						// Store current recipients to $members array.
						foreach ( $current_recipients as $single_recipients ) {
							$member_arr[] = (int) $single_recipients;
						}

						$first_message = BP_Messages_Thread::get_first_message( $thread_id );
						$message_user  = bp_messages_get_meta( $first_message->id, 'group_message_users', true );
						$message_type  = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private


						// check both previous and current recipients are same.
						$is_recipient_match = ( $previous_thread_recipients == $member_arr );

						// If recipients are matched.
						if ( $is_recipient_match && 'all' !== $message_user ) {

							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
							$_POST['message_thread_type'] = 'reply';

							remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							$message = messages_new_message( array(
								'thread_id'  => $thread_id,
								'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'date_sent'  => $date_sent = bp_core_current_time(),
								'error_type' => 'wp_error',
							) );
							remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

							$thread_loop_message_sent = true;

							// If recipients then break the loop and go ahead because we don't need to check other threads.
							break;
						} elseif ( $is_recipient_match && 'all' === $message_user && 'open' !== $message_type ) {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
							$_POST['message_thread_type'] = 'reply';

							remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							$message = messages_new_message( array(
								'thread_id'  => $thread_id,
								'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'date_sent'  => $date_sent = bp_core_current_time(),
								'error_type' => 'wp_error',
							) );
							remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );

							$thread_loop_message_sent = true;

							// If recipients then break the loop and go ahead because we don't need to check other threads.
							break;
						}
					}

				endwhile;

				// If there is no any thread matched.
				if ( false === $thread_loop_message_sent ) {

					$member_check     = array();
					$member_check[]   = $member;
					$member_check[]   = bp_loggedin_user_id();
					$previous_threads = BP_Messages_Message::get_existing_threads( $member_check, bp_loggedin_user_id() );
					$existing_thread  = 0;

					if ( $previous_threads ) {
						foreach ( $previous_threads as $thread ) {

							$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );

							if ( $is_active_recipient ) {

								// get the thread recipients.
								$thread                     = new BP_Messages_Thread( $thread->thread_id );
								$thread_recipients          = $thread->get_recipients();
								$previous_thread_recipients = array();

								// Store thread recipients to $previous_ids array.
								foreach ( $thread_recipients as $thread_recipient ) {
									if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
										$previous_thread_recipients[] = $thread_recipient->user_id;
									}
								}

								$current_recipients = array();
								if ( is_array( $member ) ) {
									$current_recipients = $member;
								} else {
									$current_recipients[] = $member;
								}
								$members = array();

								// Store current recipients to $members array.
								foreach ( $current_recipients as $single_recipients ) {
									$members[] = (int) $single_recipients;
								}

								$first_message = BP_Messages_Thread::get_first_message( $thread->thread_id );
								$message_user  = bp_messages_get_meta( $first_message->id, 'group_message_users', true );
								$message_type  = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private


								// check both previous and current recipients are same.
								$is_recipient_match = ( $previous_thread_recipients == $members );

								// If recipients are matched.
								if ( $is_recipient_match && 'all' !== $message_user ) {
									$existing_thread = (int) $thread->thread_id;
								} elseif ( $is_recipient_match && 'all' === $message_user && 'open' !== $message_type ) {
									$existing_thread = (int) $thread->thread_id;
								}
							}
						}
						if ( $existing_thread > 0 ) {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
							$_POST['message_thread_type'] = 'reply';

							remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							$message = messages_new_message( array(
								'thread_id'  => $existing_thread,
								'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
								'date_sent'  => $date_sent = bp_core_current_time(),
								'error_type' => 'wp_error',
							) );
							remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						} else {
							// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
							$_POST['message_thread_type'] = 'new';

							remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							// Attempt to send the message.
							$message = messages_new_message( array(
								'recipients'    => $member,
								'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
								'content'       => $_POST['content'],
								'error_type'    => 'wp_error',
								'append_thread' => false,
							) );
							remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
							add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						}
					} else {

						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'new';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						// Attempt to send the message.
						$message = messages_new_message( array(
							'recipients'    => $member,
							'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
							'content'       => $_POST['content'],
							'error_type'    => 'wp_error',
							'append_thread' => false,
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
					}
				}
				// If no existing private thread found.
			} else {

				$member_check     = array();
				$member_check[]   = $member;
				$member_check[]   = bp_loggedin_user_id();
				$previous_threads = BP_Messages_Message::get_existing_threads( $member_check, bp_loggedin_user_id() );
				$existing_thread  = 0;

				if ( $previous_threads ) {
					foreach ( $previous_threads as $thread ) {

						$is_active_recipient = BP_Messages_Thread::is_thread_recipient( $thread->thread_id, bp_loggedin_user_id() );

						if ( $is_active_recipient ) {

							// get the thread recipients.
							$thread                     = new BP_Messages_Thread( $thread->thread_id );
							$thread_recipients          = $thread->get_recipients();
							$previous_thread_recipients = array();

							// Store thread recipients to $previous_ids array.
							foreach ( $thread_recipients as $thread_recipient ) {
								if ( $thread_recipient->user_id !== bp_loggedin_user_id() ) {
									$previous_thread_recipients[] = $thread_recipient->user_id;
								}
							}

							$current_recipients = array();
							if ( is_array( $member ) ) {
								$current_recipients = $member;
							} else {
								$current_recipients[] = $member;
							}
							$members = array();

							// Store current recipients to $members array.
							foreach ( $current_recipients as $single_recipients ) {
								$members[] = (int) $single_recipients;
							}

							$first_message = BP_Messages_Thread::get_first_message( $thread->thread_id );
							$message_user  = bp_messages_get_meta( $first_message->id, 'group_message_users', true );
							$message_type  = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private

							// check both previous and current recipients are same.
							$is_recipient_match = ( $previous_thread_recipients == $members );

							// If recipients are matched.
							if ( $is_recipient_match && 'all' !== $message_user ) {
								$existing_thread = (int) $thread->thread_id;
							} elseif ( $is_recipient_match && 'all' === $message_user && 'open' !== $message_type ) {
								$existing_thread = (int) $thread->thread_id;
							}
						}
					}
					if ( $existing_thread > 0 ) {
						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'reply';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						$message = messages_new_message( array(
							'thread_id'  => $existing_thread,
							'subject'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'content'    => ! empty( $_POST['content'] ) ? $_POST['content'] : ' ',
							'date_sent'  => $date_sent = bp_core_current_time(),
							'error_type' => 'wp_error',
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
					} else {
						// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
						$_POST['message_thread_type'] = 'new';

						remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						// Attempt to send the message.
						$message = messages_new_message( array(
							'recipients'    => $member,
							'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
							'content'       => $_POST['content'],
							'error_type'    => 'wp_error',
							'append_thread' => false,
						) );
						remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
						add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
					}
				} else {
					// This post variable will using in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type"
					$_POST['message_thread_type'] = 'new';

					remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					// Attempt to send the message.
					$message = messages_new_message( array(
						'recipients'    => $member,
						'subject'       => wp_trim_words( $_POST['content'], messages_get_default_subject_length() ),
						'content'       => $_POST['content'],
						'error_type'    => 'wp_error',
						'append_thread' => false,
					) );
					remove_action( 'messages_message_sent', 'group_messages_notification_new_message', 10 );
					add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
				}
			}
		}
		if ( is_wp_error( $message ) ) {
			$response['feedback'] = $message->get_error_message();
			wp_send_json_error( $response );
		} elseif ( ! empty( $message ) ) {
			$response['feedback']      = __( 'Your message was sent successfully.', 'buddyboss' );
			$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
			$response['type']          = 'success';
			wp_send_json_success( $response );
		}
	}
}
