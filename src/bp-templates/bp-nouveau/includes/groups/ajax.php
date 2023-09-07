<?php
/**
 * Groups Ajax functions
 *
 * @since   BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	function () {

		$ajax_actions = array(
			array(
				'groups_filter' => array(
					'function' => 'bp_nouveau_ajax_object_template_loader',
					'nopriv'   => true,
				),
			),
			array(
				'groups_join_group' => array(
					'function' => 'bp_nouveau_ajax_joinleave_group',
					'nopriv'   => false,
				),
			),
			array(
				'groups_leave_group' => array(
					'function' => 'bp_nouveau_ajax_joinleave_group',
					'nopriv'   => false,
				),
			),
			array(
				'groups_accept_invite' => array(
					'function' => 'bp_nouveau_ajax_joinleave_group',
					'nopriv'   => false,
				),
			),
			array(
				'groups_reject_invite' => array(
					'function' => 'bp_nouveau_ajax_joinleave_group',
					'nopriv'   => false,
				),
			),
			array(
				'groups_request_membership' => array(
					'function' => 'bp_nouveau_ajax_joinleave_group',
					'nopriv'   => false,
				),
			),
			array(
				'groups_get_group_potential_invites' => array(
					'function' => 'bp_nouveau_ajax_get_users_to_invite',
					'nopriv'   => false,
				),
			),
			array(
				'groups_get_group_members_listing' => array(
					'function' => 'bp_nouveau_ajax_groups_get_group_members_listing',
					'nopriv'   => false,
				),
			),
			array(
				'groups_get_group_members_send_message' => array(
					'function' => 'bp_nouveau_ajax_groups_send_message',
					'nopriv'   => false,
				),
			),
			array(
				'groups_send_group_invites' => array(
					'function' => 'bp_nouveau_ajax_send_group_invites',
					'nopriv'   => false,
				),
			),
			array(
				'groups_delete_group_invite' => array(
					'function' => 'bp_nouveau_ajax_remove_group_invite',
					'nopriv'   => false,
				),
			),
			array(
				'groups_subscribe' => array(
					'function' => 'bb_nouveau_ajax_group_subscription',
					'nopriv'   => false,
				),
			),
			array(
				'groups_unsubscribe' => array(
					'function' => 'bb_nouveau_ajax_group_subscription',
					'nopriv'   => false,
				),
			),
		);

		foreach ( $ajax_actions as $ajax_action ) {
			$action = key( $ajax_action );

			add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

			if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
				add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
			}
		}
	},
	12
);

/**
 * Join or leave a group when clicking the "join/leave" button via a POST request.
 *
 * @since BuddyPress 3.0.0
 * @return string HTML
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
			if ( ! groups_check_user_has_invite( bp_loggedin_user_id(), $group_id ) ) {
				wp_send_json_error( $response );
			}

			if ( ! groups_accept_invite( bp_loggedin_user_id(), $group_id ) ) {
				$response = array(
					'feedback' => sprintf( '<div class="bp-feedback error"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Group invitation could not be accepted.', 'buddyboss' ) ),
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
					'feedback'  => sprintf( '<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Group invite accepted.', 'buddyboss' ) ),
					'type'      => 'success',
					'is_user'   => bp_is_user(),
					'contents'  => bp_get_group_join_button( $group ),
					'is_group'  => bp_is_group(),
					'group_url' => bp_get_group_permalink( $group ),
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
					'feedback'  => sprintf( '<div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>', esc_html__( 'Group invite rejected', 'buddyboss' ) ),
					'type'      => 'success',
					'is_user'   => bp_is_user(),
					'group_url' => bp_get_group_permalink( $group ),
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

		case 'groups_request_membership':
			if ( ! groups_send_membership_request(
				array(
					'user_id'  => bp_loggedin_user_id(),
					'group_id' => $group->id,
				)
			) ) {
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
					'contents'  => bp_get_group_join_button( $group ),
					'is_group'  => bp_is_group(),
					'type'      => 'success',
					'group_url' => ( bp_is_group() ? bp_get_group_permalink( $group ) : '' ),
				);
			}
			break;

		case 'groups_leave_group':
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

				if ( 'hidden' === $group->status ) {
					$response['group_url'] = esc_url( bp_get_groups_directory_permalink() );
				}

				// Reset the message if not in a Group or in a loggedin user's group memberships one!
				if ( ! $bp_is_group && isset( $bp->template_message ) && isset( $bp->template_message_type ) ) {
					unset( $bp->template_message, $bp->template_message_type );

					@setcookie( 'bp-message', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
					@setcookie( 'bp-message-type', false, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
				}
			}
			break;
	}

	/**
	 * Filters change the success/fail message.
	 *
	 * @since BuddyBoss 1.5.0
	 *
	 * @param array $response Array of response message.
	 * @param int   $group_id Group id.
	 */
	$response = apply_filters( 'bp_nouveau_ajax_joinleave_group', $response, $group_id );

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

	$request = bp_parse_args(
		$_POST,
		array(
			'scope' => 'members',
		)
	);

	if ( 'groups_get_group_potential_invites' === $request['action'] ) {

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
		// if ( false !== $group_type ) {
		if ( true === bp_enable_group_hierarchies() ) {
			if ( true === bp_enable_group_restrict_invites() ) {
				$parent_group_id = bp_get_parent_group_id( $request['group_id'] );
				if ( $parent_group_id > 0 ) {
					$members_query      = groups_get_group_members(
						array(
							'group_id'            => $parent_group_id,
							'exclude_admins_mods' => false,
						)
					);
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
		// }

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
						$members_query = groups_get_group_members(
							array(
								'group_id' => $group_id,
							)
						);
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
		$request = apply_filters( 'groups_get_group_potential_invites_requests_args', $request );
	}

	$bp->groups->invites_scope = 'members';
	$message                   = __( 'Select members to invite by clicking the + button next to each member.', 'buddyboss' );

	if ( 'friends' === $request['scope'] ) {
		$request['user_id']        = bp_loggedin_user_id();
		$bp->groups->invites_scope = 'friends';
		$message                   = __( 'Select which connections to invite by clicking the + button next to each member.', 'buddyboss' );
	}

	if ( 'invited' === $request['scope'] ) {

		if ( ! bp_group_has_invites(
			array(
				'user_id'  => 'any',
				'group_id' => $request['group_id'],
			)
		) ) {

			if ( isset( $request ) && isset( $request['search_terms'] ) && '' !== $request['search_terms'] ) {
				// This message displays if you search in pending invites screen and if no results found in search.
				wp_send_json_error(
					array(
						'feedback' => __( 'All members already received invitations.', 'buddyboss' ),
						'type'     => 'info',
					)
				);
			} else {
				// This message displays when pending invites screen doesn't have any users invitation.
				wp_send_json_error(
					array(
						'feedback' => __( 'No pending group invitations found.', 'buddyboss' ),
						'type'     => 'info',
					)
				);
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
										<a href="<?php echo $inviter['user_link']; ?>" class="bp-tooltip"
										   data-bp-tooltip-pos="up" data-bp-tooltip="<?php echo $inviter['name']; ?>">
											<img src="<?php echo $inviter['avatar']; ?>" width="30px"
												 class="avatar mini" alt="<?php echo $inviter['name']; ?>">
										</a>
									</li>
								<?php } ?>
							</ul>
						<?php } ?>
						<p class="status">
							<?php
							if ( isset( $user ) && isset( $user['is_sent'] ) && '' !== $user['is_sent'] && false === $user['is_sent'] ) {
								esc_html_e( 'The invite has not been sent.', 'buddyboss' );
							} else {
								esc_html_e( 'The invite has been sent.', 'buddyboss' );
							}
							?>
						</p>
					</div>
				<?php } ?>
			</div>
			<div class="action">
				<?php if ( empty( $user['is_sent'] ) || ( false === $user['is_sent'] && true === $user['is_sent'] ) ) { ?>
					<button data-bp-user-id="<?php echo $user['id']; ?>"
							data-bp-user-name="<?php echo $user['name']; ?>" type="button" class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons
														<?php
														if ( isset( $user['selected'] ) && $user['selected'] ) {
															?>
                            selected<?php } ?>" data-bp-tooltip-pos="left"
                            data-bp-tooltip="<?php echo ( isset( $user['selected'] ) && $user['selected'] ) ? esc_attr__( 'Cancel invitation', 'buddyboss' ) : esc_attr__( 'Invite', 'buddyboss' ); ?>">
						<span class="icons" aria-hidden="true"></span> <span class="bp-screen-reader-text">
						<?php
						if ( isset( $user['selected'] ) && $user['selected'] ) {
							esc_html_e( 'Cancel invitation', 'buddyboss' );
						} else {
							esc_html_e( 'Invite', 'buddyboss' );
						}
						?>
					</span>
					</button>
				<?php } ?>

				<?php
				if ( isset( $user['can_edit'] ) && true === $user['can_edit'] ) {
					if ( 'invited' === $request['scope'] ) {
						?>
						<button data-bp-user-id="<?php echo $user['id']; ?>"
								data-bp-user-name="<?php echo $user['name']; ?>" type="button"
								class="button remove-button group-remove-invite-button bp-tooltip bp-icons"
								data-bp-tooltip-pos="left"
								data-bp-tooltip="<?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?>">
							<span class=" icons" aria-hidden="true"></span>
							<span class="bp-screen-reader-text"><?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?></span>
						</button>
						<?php
					} else {
						?>
						<button data-bp-user-id="<?php echo $user['id']; ?>"
								data-bp-user-name="<?php echo $user['name']; ?>" type="button"
								class="button invite-button group-remove-invite-button bp-tooltip bp-icons"
								data-bp-tooltip-pos="left"
								data-bp-tooltip="<?php esc_attr_e( 'Cancel invitation', 'buddyboss' ); ?>">
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
				<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
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
		if ( 1 !== $page ) {
			?>
			<a href="javascript:void(0);" id="bp-group-invites-prev-page" class="button group-invite-button bp-tooltip"
			   data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Previous page', 'buddyboss' ); ?>">
				<span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
				<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span> </a>
			<?php
		}

		if ( $total_page !== $page ) {
			$page = $page + 1;
			?>
			<a href="javascript:void(0);" id="bp-group-invites-next-page" class="button group-invite-button bp-tooltip"
			   data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Next page', 'buddyboss' ); ?>"> <span
						class="bp-screen-reader-text"><?php esc_html_e( 'Next page', 'buddyboss' ); ?></span>
				<span class="dashicons dashicons-arrow-right" aria-hidden="true"></span> </a>
			<?php
		}
	}

	$paginate = ob_get_contents();
	ob_clean();

	// Set a message to explain use of the current scope.
	$potential_invites->feedback = $message;

	// Set a pagination.
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

	// Verify nonce.
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

	// For feedback.
	$invited = array();

	foreach ( (array) $_POST['users'] as $user_id ) {
		$user_id = (int) $user_id;

		// Check friends & settings component is active and all members can be invited.
		if ( bp_is_active( 'friends' ) && bp_nouveau_groups_get_group_invites_setting( $user_id ) && 'is_friend' !== BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $user_id ) ) {
			continue;
		}

		$invited[ $user_id ] = groups_invite_user(
			array(
				'user_id'  => $user_id,
				'group_id' => $group_id,
				'content'  => $_POST['message'],
			)
		);
	}

	if ( ! $invited ) {
		wp_send_json_error( $response );
	}

	if ( ! empty( $_POST['message'] ) ) {
		$bp->groups->invites_message = wp_kses( wp_unslash( $_POST['message'] ), array() );

		add_filter( 'groups_notification_group_invites_message', 'bp_nouveau_groups_invites_custom_message', 10, 1 );
	}

	// Send the invites.
	groups_send_invites( array( 'group_id' => $group_id ) );

	if ( ! empty( $_POST['message'] ) ) {
		unset( $bp->groups->invites_message );

		remove_filter( 'groups_notification_group_invites_message', 'bp_nouveau_groups_invites_custom_message', 10, 1 );
	}

	if ( array_search( false, $invited ) ) {
		$errors = array_keys( $invited, false );

		$error_count   = count( $errors );
		$error_message = sprintf( /* translators: count of users affected */ _n( 'Invitation failed for %s user.', 'Invitation failed for %s users.', $error_count, 'buddyboss' ), bp_core_number_format( $error_count ) );

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

	$response = array(
		'feedback' => __( 'Group invitation could not be removed.', 'buddyboss' ),
		'type'     => 'error',
	);

	// Verify nonce.
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'groups_invite_uninvite_user' ) ) {
		wp_send_json_error( $response );
	}

	// Verify that a sent invite exists.
	$inviter_ids = groups_get_invites(
		array(
			'user_id'     => $user_id,
			'item_id'     => $group_id,
			'invite_sent' => 'sent',
			'fields'      => 'inviter_ids',
		)
	);

	if ( empty( $inviter_ids ) ) {
		wp_send_json_error( $response );
	}

	// Is the current user the inviter?
	$inviter_id = in_array( bp_loggedin_user_id(), $inviter_ids, true ) ? bp_loggedin_user_id() : false;

	// A site moderator, group admin or the inviting user should be able to remove an invitation.
	if ( ! bp_is_item_admin() && ! $inviter_id ) {
		wp_send_json_error( $response );
	}

	if ( groups_is_user_member( $user_id, $group_id ) ) {
		wp_send_json_error(
			array(
				'feedback' => __( 'The member is already a member of the group.', 'buddyboss' ),
				'type'     => 'warning',
				'code'     => 1,
			)
		);
	}

	// Remove the invitation.
	if ( ! groups_uninvite_user( $user_id, $group_id, $inviter_id ) ) {
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

	if ( empty( wp_unslash( $_POST['nonce'] ) ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'retrieve_group_members' ) ) {
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
		wp_send_json_success( array( 'results' => 'no_member' ) );
	} else {
		$total_page = (int) ceil( (int) $group_members['count'] / $per_page );
		ob_start();
		foreach ( $group_members['members'] as $member ) {

			$image = htmlspecialchars_decode(
				bp_core_fetch_avatar(
					array(
						'item_id' => $member->ID,
						'object'  => 'user',
						'type'    => 'thumb',
						'class'   => '',
					)
				),
				ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
			);

			$name = bp_core_get_user_displayname( $member->ID );

			$is_friends_connection  = true;

			if (
				! bb_messages_user_can_send_message(
					array(
						'sender_id'     => bp_loggedin_user_id(),
						'recipients_id' => $member->ID,
						'group_id'      => bp_get_current_group_id(),

					)
				)
			) {
				$is_friends_connection = false;
			}
			?>
			<li class="group-message-member-li
			<?php
			echo $member->ID;
			echo ( $is_friends_connection ) ? ' can-grp-msg ' : ' is_disabled can-not-grp-msg';
			?>
			">
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
				<div class="action <?php echo ( $is_friends_connection ) ? esc_attr( 'can-grp-msg' ) : esc_attr( 'can-not-grp-msg' ); ?>">
					<?php
					if ( $is_friends_connection ) {
						?>
						<button type="button"
								class="button invite-button group-add-remove-invite-button bp-tooltip bp-icons"
								data-bp-user-id="<?php echo esc_attr( $member->ID ); ?>"
								data-bp-user-name="<?php echo esc_attr( $name ); ?>" data-bp-tooltip-pos="left"
								data-bp-tooltip="<?php esc_attr_e( 'Add Member', 'buddyboss' ); ?>">
							<span class="icons" aria-hidden="true"></span> <span class="bp-screen-reader-text">
							<?php esc_html_e( 'Add Member', 'buddyboss' ); ?>
						</span>
						</button>
						<?php
					} else {
						?>
						<span data-bp-tooltip-pos="left" data-bp-tooltip="<?php esc_attr_e( 'Restricted', 'buddyboss' ); ?>">
							<i class="bb-icon-l bb-icon-cancel" aria-hidden="true"></i>
						</span>
						<?php
					}
					?>
				</div>
			</li>
			<?php
		}

		if ( $total_page !== (int) $_POST['page'] ) {
			?>
			<li class="load-more">
				<div class="center">
					<i class="bb-icon-l bb-icon-spinner animate-spin"></i>
				</div>
			</li>
			<?php
		}

		$html = ob_get_contents();
		ob_clean();

		if ( empty( $_POST['term'] ) ) {

			ob_start();

			if ( 1 !== (int) $_POST['page'] ) {
				?>
				<a href="javascript:void(0);" id="bp-group-messages-prev-page"
				   class="button group-message-button bp-tooltip" data-bp-tooltip-pos="up"
				   data-bp-tooltip="<?php esc_attr_e( 'Previous page', 'buddyboss' ); ?>">
					<span class="dashicons dashicons-arrow-left" aria-hidden="true"></span>
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Previous page', 'buddyboss' ); ?></span> </a>
				<?php
			}

			if ( $total_page !== (int) $_POST['page'] ) {
				$page = $page + 1;
				?>
				<a href="javascript:void(0);" id="bp-group-messages-next-page"
				   class="button group-message-button bp-tooltip" data-bp-tooltip-pos="up"
				   data-bp-tooltip="<?php esc_attr_e( 'Next page', 'buddyboss' ); ?>"> <span
							class="bp-screen-reader-text"><?php esc_html_e( 'Next page', 'buddyboss' ); ?></span>
					<span class="dashicons dashicons-arrow-right" aria-hidden="true"></span> </a>
				<?php
			}

			$paginate = ob_get_contents();
			ob_clean();

		}

		$html       = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_html', $html );
		$total_page = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_total_page', $total_page );
		$page       = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_page', $page );
		$paginate   = apply_filters( 'bp_nouveau_ajax_groups_get_group_members_listing_paginate', $paginate );

		wp_send_json_success(
			array(
				'results'     => $html,
				'total_page'  => $total_page,
				'page'        => $page,
				'pagination'  => $paginate,
				'total_count' => __( 'Members', 'buddyboss' ),
			)
		);

	}
}

/**
 * Send group message to group members.
 *
 * @since BuddyBoss 1.2.9
 */
function bp_nouveau_ajax_groups_send_message() {

	global $wpdb, $bp, $bb_background_updater;

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

	$wp_nonce = bb_filter_input_string( INPUT_POST, 'nonce' );

	if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'send_messages_users' ) ) {
		wp_send_json_error( $response );
	}

	$gif_data = filter_input( INPUT_POST, 'gif_data', FILTER_DEFAULT );
	$media    = filter_input( INPUT_POST, 'media', FILTER_DEFAULT );
	$document = filter_input( INPUT_POST, 'document', FILTER_DEFAULT );
	$video    = filter_input( INPUT_POST, 'video', FILTER_DEFAULT );
	$message  = '';

	if ( isset( $_POST['gif_data'] ) ) {
		unset( $_POST['gif_data'] );
	}
	if ( isset( $gif_data ) && '' !== $gif_data ) {
		$_POST['gif_data'] = json_decode( wp_kses_stripslashes( $gif_data ), true );
	}

	if ( isset( $_POST['media'] ) ) {
		unset( $_POST['media'] );
	}
	if ( isset( $media ) && '' !== $media ) {
		$_POST['media'] = json_decode( wp_kses_stripslashes( $media ), true );
	}

	if ( isset( $_POST['document'] ) ) {
		unset( $_POST['document'] );
	}
	if ( isset( $document ) && '' !== $document ) {
		$_POST['document'] = json_decode( wp_kses_stripslashes( $document ), true );
	}

	if ( isset( $_POST['video'] ) ) {
		unset( $_POST['video'] );
	}
	if ( isset( $video ) && '' !== $video ) {
		$_POST['video'] = json_decode( wp_kses_stripslashes( $video ), true );
	}

	$content = filter_input( INPUT_POST, 'content', FILTER_DEFAULT );

	/**
	 * Filter to validate message content.
	 *
	 * @param bool   $validated_content True if message is valid, false otherwise.
	 * @param string $content           Content of the message.
	 * @param array  $_POST             POST Request Object.
	 *
	 * @return bool True if message is valid, false otherwise.
	 */
	$validated_content = (bool) apply_filters( 'bp_messages_message_validated_content', ! empty( $content ) && strlen( trim( html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) ), $content, $_POST );

	if ( ! $validated_content ) {
		$response['feedback'] = __( 'Your message was not sent. Please enter some content.', 'buddyboss' );

		wp_send_json_error( $response );
	}

	$group         = filter_input( INPUT_POST, 'group', FILTER_VALIDATE_INT ); // Group id.
	$message_users = bb_filter_input_string( INPUT_POST, 'users' ); // all - individual.
	$message_type  = bb_filter_input_string( INPUT_POST, 'type' ); // open - private.

	// Get Members list if "All Group Members" selected.
	if ( 'all' === $message_users ) {

		// Fetch all the group members.
		$members = BP_Groups_Member::get_group_member_ids( (int) $group );

		// Exclude logged-in user ids from the members list.
		if ( in_array( bp_loggedin_user_id(), $members, true ) ) {
			$members = array_values( array_diff( $members, array( bp_loggedin_user_id() ) ) );
		}

		if ( 'private' === $message_type ) {

			// Check Membership Access.
			foreach ( $members as $k => $member ) {
				$can_send_group_message = apply_filters( 'bb_user_can_send_group_message', true, $member, bp_loggedin_user_id() );
				if ( ! $can_send_group_message ) {
					unset( $members[ $k ] );
				}
			}

			// Check if force friendship is enabled and check recipients.
			if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {
				if ( bp_is_active( 'messages' ) && ! bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) ) {
					foreach ( $members as $f => $member ) {
						if (
							! (
								bb_messages_allowed_messaging_without_connection( $member ) ||
								friends_check_friendship( bp_loggedin_user_id(), $member )
							)
						) {
							unset( $members[ $f ] );
						}
					}
				}
			}

			$members = array_values( $members );
		}

		// We get members array from $_POST['users_list'] because user already selected them.
	} else {

		$members = filter_input( INPUT_POST, 'users_list', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		// Check Membership Access.
		$not_access_list = array();

		// Check if force friendship is enabled and check recipients.
		$not_friends = array();

		foreach ( $members as $member ) {

			$can_send_group_message = apply_filters( 'bb_user_can_send_group_message', true, $member, bp_loggedin_user_id() );
			if ( ! $can_send_group_message ) {
				$not_access_list[] = bp_core_get_user_displayname( $member );
			}
		}

		if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {
			if ( bp_is_active( 'messages' ) && ! bb_messages_allowed_messaging_without_connection( bp_loggedin_user_id() ) ) {
				foreach ( $members as $f => $member ) {
					if (
						! (
							bb_messages_allowed_messaging_without_connection( $member ) ||
							friends_check_friendship( bp_loggedin_user_id(), $member )
						)
					) {
						$not_friends[] = bp_core_get_user_displayname( $member );
					}
				}
			}
		}

		if ( ! empty( $not_access_list ) ) {
			$response['feedback'] = sprintf(
				'%1$s <strong>%2$s</strong>',
				( count( $not_access_list ) > 1 ) ? __( 'You don\'t have access to send the message to this members:  ', 'buddyboss' ) : __( 'You don\'t have access to send the message to this member:  ', 'buddyboss' ),
				implode( ', ', $not_access_list )
			);
			wp_send_json_error( $response );
		}

		if ( ! empty( $not_friends ) ) {
			$response['feedback'] = sprintf(
				'%1$s <strong>%2$s</strong>',
				( count( $not_friends ) > 1 ) ? __( 'You need to be connected with this members in order to send a message:  ', 'buddyboss' ) : __( 'You need to be connected with this member in order to send a message:  ', 'buddyboss' ),
				implode( ', ', $not_friends )
			);
			wp_send_json_error( $response );
		}

		if ( empty( $members ) ) {
			$response['feedback'] = __( 'No Members Selected.', 'buddyboss' );
			wp_send_json_error( $response );
		}
	}

	if ( empty( $group ) ) {
		$response['feedback'] = __( 'No group Selected.', 'buddyboss' );
		wp_send_json_error( $response );
	}

	// If "Group Thread" selected.
	if ( 'open' === $message_type ) {

		// "All Group Members" selected.
		if ( 'all' === $message_users ) {

			// Comma separated members list to find in meta query.
			$message_users_ids = implode( ',', $members );

			// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "message_users_ids".
			$_POST['message_meta_users_list'] = $message_users_ids;

			$group_thread                 = groups_get_groupmeta( (int) $group, 'group_message_thread' );
			$is_deleted                   = false;
			$group_thread_id              = '';
			$_POST['message_thread_type'] = '';

			if ( '' !== $group_thread && messages_is_valid_thread( $group_thread ) ) {

				$first_thread_message = BP_Messages_Thread::get_first_message( $group_thread );

				if ( ! empty( $first_thread_message ) ) {
					$users      = bp_messages_get_meta( $first_thread_message->id, 'group_message_users', true );
					$type       = bp_messages_get_meta( $first_thread_message->id, 'group_message_type', true );
					$group_from = bp_messages_get_meta( $first_thread_message->id, 'message_from', true );

					if ( 'all' !== $users || 'open' !== $type || 'group' !== $group_from ) {
						$_POST['message_thread_type'] = 'new';
					}
				}

				if ( empty( $_POST['message_thread_type'] ) ) {
					$total_threads = BP_Messages_Thread::get(
						array(
							'include_threads' => array( $group_thread ),
							'per_page'        => 1,
							'count_total'     => true,
							'is_deleted'      => 1,
						)
					);

					$is_deleted = ( ! empty( $total_threads['total'] ) ? true : false );

					if ( $is_deleted ) {
						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'new';
					}
				}
			} else {
				$_POST['message_thread_type'] = 'new';
			}

			if ( '' !== $group_thread && ! $is_deleted && isset( $_POST['message_thread_type'] ) && empty( $_POST['message_thread_type'] ) ) {
				// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
				$_POST['message_thread_type'] = 'reply';
				$group_thread_id              = $group_thread;
			} else {

				// Backward compatibility when we don't store thread_id in group meta.
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
				if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) { // phpcs:ignore

					$thread_id = 0;

					while ( bp_message_threads() ) {
						bp_message_thread();
						$thread_id = bp_get_message_thread_id();

						// Check the first message meta to check for all users and open type when missed entries found into DB.
						$first_thread_message = BP_Messages_Thread::get_first_message( $thread_id );

						if ( ! empty( $first_thread_message ) ) {
							$users      = bp_messages_get_meta( $first_thread_message->id, 'group_message_users', true );
							$type       = bp_messages_get_meta( $first_thread_message->id, 'group_message_type', true );
							$group_from = bp_messages_get_meta( $first_thread_message->id, 'message_from', true );

							if ( 'all' !== $users || 'open' !== $type || 'group' !== $group_from ) {
								$thread_id = 0;
							}
						}

						if ( $thread_id ) {
							break;
						}
					}

					// If $thread_id found then add as a reply to that thread.
					if ( $thread_id ) {
						$group_thread_id = $thread_id;

						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'reply';

						// Create a new group thread.
					} else {
						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'new';
					}

					// Create a new group thread.
				} else {
					// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
					$_POST['message_thread_type'] = 'new';
				}
			}

			/**
			 * Create Message based on the `message_thread_type` and `group_thread_id`.
			 */
			if ( isset( $_POST['message_thread_type'] ) && 'new' === $_POST['message_thread_type'] ) {

				$send = bp_groups_messages_new_message(
					array(
						'recipients'    => $members,
						'subject'       => wp_trim_words( $content, messages_get_default_subject_length() ),
						'content'       => $content,
						'error_type'    => 'wp_error',
						'append_thread' => false,
					)
				);

				if ( ! is_wp_error( $send ) && ! empty( $send ) ) {
					groups_update_groupmeta( (int) $group, 'group_message_thread', $send );
				}

				bp_groups_messages_validate_message( $send );

			} elseif ( isset( $_POST['message_thread_type'] ) && 'reply' === $_POST['message_thread_type'] && ! empty( $group_thread_id ) ) {

				groups_update_groupmeta( (int) $group, 'group_message_thread', $group_thread_id );

				$new_reply = bp_groups_messages_new_message(
					array(
						'thread_id'    => $group_thread_id,
						'subject'      => false,
						'content'      => $content,
						'date_sent'    => bp_core_current_time(),
						'mark_visible' => true,
						'error_type'   => 'wp_error',
					)
				);

				bp_groups_messages_validate_message( $new_reply );
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

			$individual_thread_id         = 0;
			$_POST['message_thread_type'] = '';

			// Check if there is already previously individual group thread created.
			if ( bp_has_message_threads( array( 'meta_query' => $meta ) ) ) {

				$thread_id = 0;

				while ( bp_message_threads() ) {
					bp_message_thread();
					$thread_id = bp_get_message_thread_id();

					if ( $thread_id ) {

						// get the thread recipients.
						$thread                     = new BP_Messages_Thread( $thread_id );
						$thread_recipients          = $thread->get_recipients();
						$previous_thread_recipients = array();

						// Store thread recipients to $previous_ids array.
						foreach ( $thread_recipients as $thread_recipient ) {
							if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
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
				}

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
						if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
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
						$individual_thread_id = $thread_id;

						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'reply';

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
										if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
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
										// group_message_users not open.
										$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual.
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
								$individual_thread_id = $existing_thread;

								// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
								$_POST['message_thread_type'] = 'reply';
							} else {
								// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
								$_POST['message_thread_type'] = 'new';
							}
						} else {
							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'new';
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
									if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
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
									// group_message_users not open.
									$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual.
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
							$individual_thread_id = $existing_thread;

							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'reply';
						} else {
							// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
							$_POST['message_thread_type'] = 'new';
						}
					} else {
						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'new';
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
								if ( bp_loggedin_user_id() !== $thread_recipient->user_id ) {
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
								// group_message_users not open.
								$message_users = bp_messages_get_meta( $id, 'group_message_users', true ); // all - individual.
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
						$individual_thread_id = $existing_thread;

						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'reply';
					} else {
						// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
						$_POST['message_thread_type'] = 'new';
					}
				} else {
					// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "group_message_thread_type".
					$_POST['message_thread_type'] = 'new';
				}
			}

			/**
			 * Create Message based on the `message_thread_type` and `individual_thread_id`.
			 */
			if ( isset( $_POST['message_thread_type'] ) && 'new' === $_POST['message_thread_type'] ) {
				$send = bp_groups_messages_new_message(
					array(
						'recipients'    => $members,
						'subject'       => wp_trim_words( $content, messages_get_default_subject_length() ),
						'content'       => $content,
						'error_type'    => 'wp_error',
						'append_thread' => false,
					)
				);

				bp_groups_messages_validate_message( $send, 'individual' );
			} elseif ( isset( $_POST['message_thread_type'] ) && 'reply' === $_POST['message_thread_type'] && ! empty( $individual_thread_id ) ) {
				$new_reply = bp_groups_messages_new_message(
					array(
						'thread_id'    => $individual_thread_id,
						'subject'      => false,
						'content'      => $content,
						'date_sent'    => bp_core_current_time(),
						'mark_visible' => true,
						'error_type'   => 'wp_error',
					)
				);

				bp_groups_messages_validate_message( $new_reply, 'individual' );
			}
		}

		// Else "Private Reply (BCC)" selected.
	} else {

		if ( ! empty( $members ) ) {

			// Comma separated members list to find in meta query.
			$message_users_ids = implode( ',', $members );

			// This post variable will use in "bp_media_messages_save_group_data" function for storing message meta "message_users_ids".
			$_POST['message_meta_users_list'] = $message_users_ids;

			if ( ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ) {
				$chunk_members = array_chunk( $members, bb_get_email_queue_min_count() );
				if ( ! empty( $chunk_members ) ) {
					foreach ( $chunk_members as $key => $members ) {
						$bb_background_updater->data(
							array(
								'type'     => 'email',
								'group'    => 'group_private_message',
								'data_id'  => $group,
								'priority' => 5,
								'callback' => 'bb_send_group_message_background',
								'args'     => array( $_POST, $members, bp_loggedin_user_id(), $content, true ),
							),
						);
						$bb_background_updater->save();
					}
					$bb_background_updater->dispatch();
				}

				$message = true;
			} else {
				$message = bb_send_group_message_background( $_POST, $members, bp_loggedin_user_id(), $content, false );
			}
		}

		if ( is_wp_error( $message ) ) {
			$response['feedback'] = $message->get_error_message();
			wp_send_json_error( $response );
		} elseif ( ! empty( $message ) ) {
			if ( 'all' !== $message_users ) {
				$response['feedback'] = __( 'Your message was sent privately to %%count%% members of this group.', 'buddyboss' );
			} else {
				$response['feedback'] = __( 'Your message was sent privately to all members of this group you can message.', 'buddyboss' );
			}

			$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
			$response['type']          = 'success';
			wp_send_json_success( $response );
		}
	}
}

/**
 * Check group message has been successfully sent or not.
 *
 * @param mixed  $send int|bool|WP_Error
 * @param string $type Type of the message `all` or `individual`.
 */
function bp_groups_messages_validate_message( $send, $type = 'all' ) {
	if ( is_wp_error( $send ) ) {
		$response['feedback'] = $send->get_error_message();
		wp_send_json_error( $response );
	} elseif ( ! empty( $send ) ) {
		if ( 'individual' === $type ) {
			$response['feedback'] = __( 'Your message was sent to %%count%% members of this group.', 'buddyboss' );
		} else {
			$response['feedback'] = __( 'Your message was sent to all members of this group.', 'buddyboss' );
		}
		$response['redirect_link'] = '<a href="' . bp_loggedin_user_domain() . bp_get_messages_slug() . '"> ' . __( 'View message.', 'buddyboss' ) . '</a>';
		$response['type']          = 'success';
		wp_send_json_success( $response );
	}
}

/**
 * Subscribe or un-subscribe a group when clicking the bell button via a POST request.
 *
 * @since BuddyBoss [BBVERIOSN]
 */
function bb_nouveau_ajax_group_subscription() {
	$response = array(
		'feedback'              => esc_html__( 'There was a problem subscribing/unsubscribing.', 'buddyboss' ),
		'is_group_subscription' => true,
		'type'                  => 'error',
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() || empty( $_POST['action'] ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || empty( $_POST['item_id'] ) || ! bp_is_active( 'groups' ) ) {
		wp_send_json_error( $response );
	}

	// Cast gid as integer.
	$group_id = (int) sanitize_text_field( wp_unslash( $_POST['item_id'] ) );
	$user_id  = bp_loggedin_user_id();

	// Use default nonce.
	$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
	$check = 'bb-group-subscription-' . $group_id;

	// Use a specific one for actions needed it.
	if ( ! empty( $_POST['_wpnonce'] ) ) {
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
	}

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( ! bb_is_enabled_subscription( 'group' ) ) {
		wp_send_json_error(
			array(
				'feedback'              => esc_html__( 'Subscriptions are no longer active.', 'buddyboss' ),
				'is_group_subscription' => true,
			)
		);
	}

	// Validate and get the group.
	$group = groups_get_group( $group_id );

	if ( empty( $group->id ) ) {
		wp_send_json_error( $response );
	}

	$group_name = bp_get_group_name( $group );
	$group_name = bp_create_excerpt( $group_name, 35, array( 'ending' => __( '&hellip;', 'buddyboss' ) ) );
	$group_name = sprintf(
		'<strong>%s</strong>',
		$group_name
	);

	// Check the current user's member of the group or not.
	$is_group_member = groups_is_user_member( $user_id, $group_id );
	if ( false === (bool) $is_group_member ) {
		wp_send_json_error( $response );
	}

	$is_subscription = bb_is_member_subscribed_group( $group_id, $user_id );

	// Manage all button's possible actions here.
	switch ( $_POST['action'] ) {

		case 'groups_subscribe':
			if ( $is_subscription ) {
				$response = array(
					'feedback'              => esc_html__( 'You are already subscribe this group.', 'buddyboss' ),
					'type'                  => 'error',
					'is_group_subscription' => true,
				);
			} else {

				$subscription_id = bb_create_subscription(
					array(
						'user_id'           => $user_id,
						'item_id'           => $group_id,
						'type'              => 'group',
						'secondary_item_id' => $group->parent_id,
					)
				);

				if ( is_wp_error( $subscription_id ) ) {
					$response = array(
						'feedback'              => sprintf(
							/* translators: Group name. */
							esc_html__( 'There was a problem subscribing to %s.', 'buddyboss' ),
							$group_name
						),
						'type'                  => 'error',
						'is_group_subscription' => true,
					);
				} else {

					ob_start();
					bp_nouveau_group_header_buttons(
						array(
							'type'           => 'subscription',
							'button_element' => 'button',
							'container'      => '',
						)
					);
					$contents = ob_get_clean();

					$response = array(
						'contents'              => $contents,
						'is_group_subscription' => true,
						'type'                  => 'success',
						'feedback'              => sprintf(
						/* translators: Group name. */
							esc_html__( 'You\'ve been subscribed to %s.', 'buddyboss' ),
							$group_name
						),
					);
				}
			}
			break;

		case 'groups_unsubscribe':
			if ( $is_subscription && bb_delete_subscription( $is_subscription ) ) {
				ob_start();
				bp_nouveau_group_header_buttons(
					array(
						'type'           => 'subscription',
						'button_element' => 'button',
						'container'      => '',
					)
				);
				$contents = ob_get_clean();

				$response = array(
					'contents'              => $contents,
					'is_group_subscription' => true,
					'type'                  => 'success',
					'feedback'              => sprintf(
					/* translators: Group name. */
						esc_html__( 'You\'ve been unsubscribed from %s.', 'buddyboss' ),
						$group_name
					),
				);
			} else {
				$response = array(
					'feedback'              => sprintf(
					/* translators: Group name. */
						esc_html__( 'There was a problem unsubscribing from %s.', 'buddyboss' ),
						$group_name
					),
					'type'                  => 'error',
					'is_group_subscription' => true,
				);
			}
			break;
	}

	/**
	 * Filters change the success/fail message.
	 *
	 * @since BuddyBoss [BBVERIOSN]
	 *
	 * @param array $response Array of response message.
	 * @param int   $group_id Group id.
	 */
	$response = apply_filters( 'bb_nouveau_ajax_group_subscription', $response, $group_id );

	if ( 'error' === $response['type'] ) {
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
}
