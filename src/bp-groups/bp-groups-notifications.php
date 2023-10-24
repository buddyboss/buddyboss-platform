<?php
/**
 * BuddyBoss Groups Notification Functions.
 *
 * These functions handle the recording, deleting and formatting of notifications
 * for the user and for this specific component.
 *
 * @package BuddyBoss\Groups\Activity
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Emails ********************************************************************/

/**
 * Notify all group members when a group is updated.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int                  $group_id  ID of the group.
 * @param BP_Groups_Group|null $old_group Group before new details were saved.
 */
function groups_notification_group_updated( $group_id = 0, $old_group = null ) {
	$group = groups_get_group( $group_id );

	if ( $old_group instanceof BP_Groups_Group ) {
		$changed = array();

		if ( $group->name !== $old_group->name ) {
			$changed[] = sprintf(
				__( '* Name changed from "%1$s" to "%2$s".', 'buddyboss' ),
				esc_html( $old_group->name ),
				esc_html( $group->name )
			);
		}

		if ( $group->description !== $old_group->description ) {
			$changed[] = sprintf(
				__( '* Description changed from "%1$s" to "%2$s".', 'buddyboss' ),
				esc_html( $old_group->description ),
				esc_html( $group->description )
			);
		}

		if ( $group->slug !== $old_group->slug ) {
			$changed[] = sprintf(
				__( '* Permalink changed from "%1$s" to "%2$s".', 'buddyboss' ),
				esc_url( bp_get_group_permalink( $old_group ) ),
				esc_url( bp_get_group_permalink( $group ) )
			);
		}
	}

	/**
	 * Filters the bullet points listing updated items in the email notification after a group is updated.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array $changed Array of bullet points.
	 */
	$changed = apply_filters( 'groups_notification_group_update_updated_items', $changed );

	$changed_text = '';
	if ( ! empty( $changed ) ) {
		$changed_text = implode( "\n", $changed );
	}

	$user_ids = BP_Groups_Member::get_group_member_ids( $group->id );

	$type_key = 'notification_groups_group_updated';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	$background_process = false;
	if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && 1 < count( (array) $user_ids ) ) {
		$background_process = true;
	}
	foreach ( (array) $user_ids as $user_id ) {

		// Continue if member opted out of receiving this email.
		if ( false === bb_is_notification_enabled( (int) $user_id, $type_key ) ) {
			continue;
		}

		$unsubscribe_args = array(
			'user_id'           => $user_id,
			'notification_type' => 'groups-details-updated',
		);

		$args = array(
			'tokens' => array(
				'changed_text' => $changed_text,
				'group'        => $group,
				'group.id'     => $group_id,
				'group.url'    => esc_url( bp_get_group_permalink( $group ) ),
				'group.name'   => $group->name,
				'unsubscribe'  => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
			),
		);
		if ( true === $background_process ) {
			bb_email_queue()->add_record( 'groups-details-updated', (int) $user_id, $args );
		} else {
			bp_send_email( 'groups-details-updated', (int) $user_id, $args );
		}
	}

	if ( true === $background_process ) {
		// call email background process.
		bb_email_queue()->bb_email_background_process();
	}

	/**
	 * Fires after the notification is sent that a group has been updated.
	 *
	 * See https://buddypress.trac.wordpress.org/ticket/3644 for blank message parameter.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.5.0 $subject has been unset and is deprecated.
	 *
	 * @param array  $user_ids Array of user IDs to notify about the update.
	 * @param string $subject  Deprecated in 2.5; now an empty string.
	 * @param string $value    Empty string preventing PHP error.
	 * @param int    $group_id ID of the group that was updated.
	 */
	do_action( 'bp_groups_sent_updated_email', $user_ids, '', '', $group_id );
}

/**
 * Notify group admin about new membership request.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $requesting_user_id ID of the user requesting group membership.
 * @param int $admin_id           ID of the group admin.
 * @param int $group_id           ID of the group.
 * @param int $membership_id      ID of the group membership object.
 */
function groups_notification_new_membership_request( $requesting_user_id = 0, $admin_id = 0, $group_id = 0, $membership_id = 0 ) {

	// Trigger a BuddyPress Notification.
	if ( bp_is_active( 'notifications' ) ) {

		$action = 'new_membership_request';

		if ( ! bb_enabled_legacy_email_preference() ) {
			$action = 'bb_groups_new_request';
		}

		bp_notifications_add_notification(
			array(
				'user_id'           => $admin_id,
				'item_id'           => $group_id,
				'secondary_item_id' => $requesting_user_id,
				'component_name'    => buddypress()->groups->id,
				'component_action'  => $action,
			)
		);
	}

	$type_key = 'notification_groups_membership_request';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	// Bail if member opted out of receiving this email.
	if ( false === bb_is_notification_enabled( (int) $admin_id, $type_key ) ) {
		return;
	}

	$unsubscribe_args = array(
		'user_id'           => $admin_id,
		'notification_type' => 'groups-membership-request',
	);

	$request_message = '';
	$requests        = groups_get_requests(
		$args        = array(
			'user_id' => $requesting_user_id,
			'item_id' => $group_id,
		)
	);
	if ( $requests ) {
		$request_message = current( $requests )->content;
	}

	$group = groups_get_group( $group_id );
	$args  = array(
		'tokens' => array(
			'admin.id'             => $admin_id,
			'group'                => $group,
			'group.name'           => $group->name,
			'group.id'             => $group_id,
			'group.url'            => esc_url( bp_get_group_permalink( $group ) ),
			'group-requests.url'   => esc_url( bp_get_group_permalink( $group ) . 'admin/membership-requests' ),
			'membership.id'        => $membership_id,
			'profile.url'          => esc_url( bp_core_get_user_domain( $requesting_user_id ) ),
			'requesting-user.id'   => $requesting_user_id,
			'requesting-user.name' => bp_core_get_user_displayname( $requesting_user_id ),
			'request.message'      => $request_message,
			'unsubscribe'          => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
		),
	);
	bp_send_email( 'groups-membership-request', (int) $admin_id, $args );
}

/**
 * Notify member about their group membership request.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int  $requesting_user_id ID of the user requesting group membership.
 * @param int  $group_id           ID of the group.
 * @param bool $accepted           Optional. Whether the membership request was accepted.
 *                                 Default: true.
 */
function groups_notification_membership_request_completed( $requesting_user_id = 0, $group_id = 0, $accepted = true ) {

	// Trigger a BuddyPress Notification.
	if ( bp_is_active( 'notifications' ) ) {

		// What type of acknowledgement.
		$type = ! empty( $accepted ) ? 'membership_request_accepted' : 'membership_request_rejected';

		if ( ! bb_enabled_legacy_email_preference() ) {
			$type = ! empty( $accepted ) ? 'bb_groups_request_accepted' : 'bb_groups_request_rejected';
		}

		bp_notifications_add_notification(
			array(
				'user_id'          => $requesting_user_id,
				'item_id'          => $group_id,
				'component_name'   => buddypress()->groups->id,
				'component_action' => $type,
			)
		);
	}

	$type_key = 'notification_membership_request_completed';

	if ( ! bb_enabled_legacy_email_preference() ) {
		$action   = ! empty( $accepted ) ? '0' : '1';
		$type_key = bb_get_prefences_key( 'legacy', $type_key, $action );
	}

	// Bail if member opted out of receiving this email.
	if ( false === bb_is_notification_enabled( $requesting_user_id, $type_key ) ) {
		return;
	}

	$group = groups_get_group( $group_id );
	$args  = array(
		'tokens' => array(
			'group'              => $group,
			'group.id'           => $group_id,
			'group.name'         => $group->name,
			'group.url'          => esc_url( bp_get_group_permalink( $group ) ),
			'requesting-user.id' => $requesting_user_id,
		),
	);

	if ( ! empty( $accepted ) ) {

		$unsubscribe_args = array(
			'user_id'           => $requesting_user_id,
			'notification_type' => 'groups-membership-request-accepted',
		);

		$args['tokens']['unsubscribe'] = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );

		bp_send_email( 'groups-membership-request-accepted', (int) $requesting_user_id, $args );

	} else {

		$unsubscribe_args = array(
			'user_id'           => $requesting_user_id,
			'notification_type' => 'groups-membership-request-rejected',
		);

		$args['tokens']['unsubscribe'] = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );

		bp_send_email( 'groups-membership-request-rejected', (int) $requesting_user_id, $args );
	}
}
add_action( 'groups_membership_accepted', 'groups_notification_membership_request_completed', 10, 3 );
add_action( 'groups_membership_rejected', 'groups_notification_membership_request_completed', 10, 3 );

/**
 * Notify group member they have been promoted.
 *
 * @since BuddyPress 1.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 */
function groups_notification_promoted_member( $user_id = 0, $group_id = 0 ) {

	// What type of promotion is this?
	if ( groups_is_user_admin( $user_id, $group_id ) ) {
		$promoted_to = get_group_role_label( $group_id, 'organizer_singular_label_name' );
		$type        = 'member_promoted_to_admin';
	} else {
		$promoted_to = get_group_role_label( $group_id, 'moderator_singular_label_name' );
		$type        = 'member_promoted_to_mod';
	}

	if ( ! bb_enabled_legacy_email_preference() ) {
		$type = 'bb_groups_promoted';
	}

	// Trigger a BuddyPress Notification.
	if ( bp_is_active( 'notifications' ) ) {

		add_action( 'bp_notification_after_save', 'bb_groups_add_notification_metas', 5 );

		bp_notifications_add_notification(
			array(
				'user_id'          => $user_id,
				'item_id'          => $group_id,
				'component_name'   => buddypress()->groups->id,
				'component_action' => $type,
			)
		);

		remove_action( 'bp_notification_after_save', 'bb_groups_add_notification_metas', 5 );
	}

	$type_key = 'notification_groups_admin_promotion';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	// Bail if admin opted out of receiving this email.
	if ( false === bb_is_notification_enabled( (int) $user_id, $type_key ) ) {
		return;
	}

	$unsubscribe_args = array(
		'user_id'           => $user_id,
		'notification_type' => 'groups-member-promoted',
	);

	$group = groups_get_group( $group_id );
	$args  = array(
		'tokens' => array(
			'group'       => $group,
			'group.id'    => $group_id,
			'group.url'   => esc_url( bp_get_group_permalink( $group ) ),
			'group.name'  => $group->name,
			'promoted_to' => $promoted_to,
			'user.id'     => $user_id,
			'unsubscribe' => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
		),
	);
	bp_send_email( 'groups-member-promoted', (int) $user_id, $args );
}
add_action( 'groups_promoted_member', 'groups_notification_promoted_member', 10, 2 );

/**
 * Notify a member they have been invited to a group.
 *
 * @since BuddyPress 1.0.0
 *
 * @param BP_Groups_Group  $group           Group object.
 * @param BP_Groups_Member $member          Member object.
 * @param int              $inviter_user_id ID of the user who sent the invite.
 */
function groups_notification_group_invites( &$group, &$member, $inviter_user_id ) {

	// @todo $inviter_ud may be used for caching, test without it
	$inviter_ud = bp_core_get_core_userdata( $inviter_user_id );

	if ( $member instanceof BP_Groups_Member ) {
		$invited_user_id = $member->user_id;
	} elseif ( is_int( $member ) ) {
		$invited_user_id = $member;
	}

	// Check the sender is blocked by recipient or not.
	if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $invited_user_id, $inviter_user_id ) ) {
		return;
	}

	// Bail if member has already been invited.
	if ( ! empty( $member->invite_sent ) ) {
		return;
	}

	// Trigger a BuddyPress Notification.
	if ( bp_is_active( 'notifications' ) ) {

		$action = 'group_invite';

		if ( ! bb_enabled_legacy_email_preference() ) {
			$action = 'bb_groups_new_invite';
		}

		bp_notifications_add_notification(
			array(
				'user_id'          => $invited_user_id,
				'item_id'          => $group->id,
				'component_name'   => buddypress()->groups->id,
				'component_action' => $action,
			)
		);
	}

	$type_key = 'notification_groups_invite';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	// Bail if member opted out of receiving this email.
	if ( false === bb_is_notification_enabled( $invited_user_id, $type_key ) ) {
		return;
	}

	$invited_link = bp_core_get_user_domain( $invited_user_id ) . bp_get_groups_slug();

	$unsubscribe_args = array(
		'user_id'           => $invited_user_id,
		'notification_type' => 'groups-invitation',
	);

	$invite_message = '';
	$invitations    = groups_get_invites(
		$args       = array(
			'user_id'    => $invited_user_id,
			'item_id'    => $group->id,
			'inviter_id' => $inviter_user_id,
		)
	);
	if ( $invitations ) {
		$invite_message = current( $invitations )->content;
	}

	$args = array(
		'tokens' => array(
			'group'          => $group,
			'group.url'      => bp_get_group_permalink( $group ),
			'group.name'     => $group->name,
			'inviter.name'   => bp_core_get_userlink( $inviter_user_id, true, false, true ),
			'inviter.url'    => bp_core_get_user_domain( $inviter_user_id ),
			'inviter.id'     => $inviter_user_id,
			'invites.url'    => esc_url( $invited_link . '/invites/' ),
			'invite.message' => $invite_message,
			'unsubscribe'    => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
		),
	);
	bp_send_email( 'groups-invitation', (int) $invited_user_id, $args );
}

/** Notifications *************************************************************/

/**
 * Format notifications for the Groups component.
 *
 * @since BuddyPress 1.0.0
 * @since BuddyBoss 1.9.3
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item ID.
 * @param int    $secondary_item_id The secondary item ID.
 * @param int    $total_items       The total number of messaging-related notifications
 *                                  waiting for the user.
 * @param string $format            'string' for BuddyBar-compatible notifications; 'array'
 *                                  for WP Toolbar. Default: 'string'.
 * @param int    $notification_id   Notification ID.
 * @param string $screen            Notification Screen type.
 *
 * @return string
 */
function groups_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string', $notification_id = 0, $screen = 'web' ) {

	switch ( $action ) {
		case 'new_membership_request':
			$group_id           = $item_id;
			$requesting_user_id = $secondary_item_id;

			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';

			// Set up the string and the filter
			// because different values are passed to the filters,
			// we'll return values inline.
			if ( (int) $total_items > 1 ) {
				$text              = sprintf( __( '%1$d new membership requests for the group "%2$s"', 'buddyboss' ), (int) $total_items, $group->name );
				$amount            = 'multiple';
				$notification_link = $group_link . 'admin/membership-requests/?n=1';

				if ( 'string' == $format ) {

					/**
					 * Filters groups multiple new membership request notification for string format.
					 *
					 * This is a dynamic filter that is dependent on item count and action.
					 * Complete filter - bp_groups_multiple_new_membership_requests_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for request.
					 * @param string $group_link        The permalink for the group.
					 * @param int    $total_items       Total number of membership requests.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . 's_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $total_items, $group->name, $text, $notification_link );
				} else {

					/**
					 * Filters groups multiple new membership request notification for any non-string format.
					 *
					 * This is a dynamic filter that is dependent on item count and action.
					 * Complete filter - bp_groups_multiple_new_membership_requests_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param string $group_link        The permalink for the group.
					 * @param int    $total_items       Total number of membership requests.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . 's_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$group_link,
						$total_items,
						$group->name,
						$text,
						$notification_link
					);
				}
			} else {
				$user_fullname     = bp_core_get_user_displayname( $requesting_user_id );
				$text              = sprintf( __( '%1$s requests membership for the group: %2$s', 'buddyboss' ), $user_fullname, $group->name );
				$notification_link = $group_link . 'admin/membership-requests/?n=1';

				if ( 'string' == $format ) {

					/**
					 * Filters groups single new membership request notification for string format.
					 *
					 * This is a dynamic filter that is dependent on item count and action.
					 * Complete filter - bp_groups_single_new_membership_request_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for request.
					 * @param string $group_link        The permalink for the group.
					 * @param string $user_fullname     Full name of requesting user.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $user_fullname, $group->name, $text, $notification_link );
				} else {

					/**
					 * Filters groups single new membership request notification for any non-string format.
					 *
					 * This is a dynamic filter that is dependent on item count and action.
					 * Complete filter - bp_groups_single_new_membership_request_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param string $group_link        The permalink for the group.
					 * @param string $user_fullname     Full name of requesting user.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$group_link,
						$user_fullname,
						$group->name,
						$text,
						$notification_link
					);
				}
			}

			break;

		case 'membership_request_accepted':
			$group_id = $item_id;

			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';

			if ( (int) $total_items > 1 ) {
				$text              = sprintf( __( '%d accepted group membership requests', 'buddyboss' ), (int) $total_items, $group->name );
				$amount            = 'multiple';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {

					/**
					 * Filters multiple accepted group membership requests notification for string format.
					 * Complete filter - bp_groups_multiple_membership_request_accepted_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $total_items       Total number of accepted requests.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $group->name, $text, $notification_link );
				} else {

					/**
					 * Filters multiple accepted group membership requests notification for non-string format.
					 * Complete filter - bp_groups_multiple_membership_request_accepted_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification
					 * @param int    $total_items       Total number of accepted requests.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$total_items,
						$group->name,
						$text,
						$notification_link
					);
				}
			} else {
				$text              = sprintf( __( 'Membership for group "%s" accepted', 'buddyboss' ), $group->name );
				$filter            = 'bp_groups_single_membership_request_accepted_notification';
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {

					/**
					 * Filters single accepted group membership request notification for string format.
					 * Complete filter - bp_groups_single_membership_request_accepted_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param string $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {

					/**
					 * Filters single accepted group membership request notification for non-string format.
					 * Complete filter - bp_groups_single_membership_request_accepted_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param string $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						$filter,
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$group_link,
						$group->name,
						$text,
						$notification_link
					);
				}
			}

			break;

		case 'membership_request_rejected':
			$group_id = $item_id;

			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';

			if ( (int) $total_items > 1 ) {
				$text              = sprintf( __( '%d rejected group membership requests', 'buddyboss' ), (int) $total_items, $group->name );
				$amount            = 'multiple';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {

					/**
					 * Filters multiple rejected group membership requests notification for string format.
					 * Complete filter - bp_groups_multiple_membership_request_rejected_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $group->name );
				} else {

					/**
					 * Filters multiple rejected group membership requests notification for non-string format.
					 * Complete filter - bp_groups_multiple_membership_request_rejected_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$total_items,
						$group->name,
						$text,
						$notification_link
					);
				}
			} else {
				$text              = sprintf( __( 'Membership for group "%s" rejected', 'buddyboss' ), $group->name );
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {

					/**
					 * Filters single rejected group membership requests notification for string format.
					 * Complete filter - bp_groups_single_membership_request_rejected_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {

					/**
					 * Filters single rejected group membership requests notification for non-string format.
					 * Complete filter - bp_groups_single_membership_request_rejected_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$group_link,
						$group->name,
						$text,
						$notification_link
					);
				}
			}

			break;

		case 'member_promoted_to_admin':
			$group_id = $item_id;

			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';

			if ( (int) $total_items > 1 ) {
				$text              = sprintf( __( 'You were promoted to the role of %1$s in %2$d groups', 'buddyboss' ), strtolower( get_group_role_label( $group_id, 'organizer_singular_label_name' ) ), (int) $total_items );
				$amount            = 'multiple';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					/**
					 * Filters multiple promoted to group admin notification for string format.
					 * Complete filter - bp_groups_multiple_member_promoted_to_admin_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					/**
					 * Filters multiple promoted to group admin notification for non-string format.
					 * Complete filter - bp_groups_multiple_member_promoted_to_admin_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$total_items,
						$text,
						$notification_link
					);
				}
			} else {
				$text              = sprintf( __( 'You were promoted to the role of %1$s in the group "%2$s"', 'buddyboss' ), strtolower( get_group_role_label( $group_id, 'organizer_singular_label_name' ) ), $group->name );
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					/**
					 * Filters single promoted to group admin notification for non-string format.
					 * Complete filter - bp_groups_single_member_promoted_to_admin_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					/**
					 * Filters single promoted to group admin notification for non-string format.
					 * Complete filter - bp_groups_single_member_promoted_to_admin_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$group_link,
						$group->name,
						$text,
						$notification_link
					);
				}
			}

			break;

		case 'member_promoted_to_mod':
			$group_id = $item_id;

			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';

			if ( (int) $total_items > 1 ) {
				$text              = sprintf( __( 'You were promoted to a %1$s in %2$d groups', 'buddyboss' ), strtolower( get_group_role_label( $group_id, 'moderator_singular_label_name' ) ), (int) $total_items );
				$amount            = 'multiple';
				$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

				if ( 'string' == $format ) {
					/**
					 * Filters multiple promoted to group mod notification for string format.
					 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					/**
					 * Filters multiple promoted to group mod notification for non-string format.
					 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$total_items,
						$text,
						$notification_link
					);
				}
			} else {
				$text              = sprintf( __( 'You were promoted to a %1$s in the group "%2$s"', 'buddyboss' ), strtolower( get_group_role_label( $group_id, 'moderator_singular_label_name' ) ), $group->name );
				$notification_link = $group_link . '?n=1';

				if ( 'string' == $format ) {
					/**
					 * Filters single promoted to group mod notification for string format.
					 * Complete filter - bp_groups_single_member_promoted_to_mod_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					/**
					 * Filters single promoted to group admin notification for non-string format.
					 * Complete filter - bp_groups_single_member_promoted_to_mod_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$group_link,
						$group->name,
						$text,
						$notification_link
					);
				}
			}

			break;

		case 'group_invite':
			$group_id   = $item_id;
			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';

			$notification_link = bp_loggedin_user_domain() . bp_get_groups_slug() . '/invites/?n=1';

			if ( (int) $total_items > 1 ) {
				$text   = sprintf( __( 'You have %d new group invitations', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';

				if ( 'string' == $format ) {
					/**
					 * Filters multiple group invitation notification for string format.
					 * Complete filter - bp_groups_multiple_group_invite_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
				} else {
					/**
					 * Filters multiple group invitation notification for non-string format.
					 * Complete filter - bp_groups_multiple_group_invite_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $total_items       Total number of rejected requests.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$total_items,
						$text,
						$notification_link
					);
				}
			} else {
				$text   = sprintf( __( 'You have an invitation to the group: %s', 'buddyboss' ), $group->name );
				$filter = 'bp_groups_single_group_invite_notification';

				if ( 'string' == $format ) {
					/**
					 * Filters single group invitation notification for string format.
					 * Complete filter - bp_groups_single_group_invite_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param string $string            HTML anchor tag for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
				} else {
					/**
					 * Filters single group invitation notification for non-string format.
					 * Complete filter - bp_groups_single_group_invite_notification.
					 *
					 * @since BuddyPress 1.0.0
					 *
					 * @param array  $array             Array holding permalink and content for notification.
					 * @param int    $group_link        The permalink for the group.
					 * @param string $group->name       Name of the group.
					 * @param string $text              Notification content.
					 * @param string $notification_link The permalink for notification.
					 */
					return apply_filters(
						'bp_groups_' . $amount . '_' . $action . '_notification',
						array(
							'link' => $notification_link,
							'text' => $text,
						),
						$group_link,
						$group->name,
						$text,
						$notification_link
					);
				}
			}

			break;

		default:
			/**
			 * Filters plugin-added group-related custom component_actions.
			 *
			 * @since BuddyPress 2.4.0
			 * @since BuddyBoss 1.9.3
			 *
			 * @param string $notification      Null value.
			 * @param int    $item_id           The primary item ID.
			 * @param int    $secondary_item_id The secondary item ID.
			 * @param int    $total_items       The total number of messaging-related notifications
			 *                                  waiting for the user.
			 * @param string $format            'string' for BuddyBar-compatible notifications;
			 *                                  'array' for WP Toolbar.
			 * @param int    $notification_id   Notification ID.
			 * @param string $screen            Notification Screen type.
			 */
			$custom_action_notification = apply_filters( 'bp_groups_' . $action . '_notification', null, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen );

			if ( ! is_null( $custom_action_notification ) ) {
				return $custom_action_notification;
			}

			break;
	}

	/**
	 * Fires right before returning the formatted group notifications.
	 *
	 * @since BuddyPress 1.0.0
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $action            The type of notification being rendered.
	 * @param int    $item_id           The primary item ID.
	 * @param int    $secondary_item_id The secondary item ID.
	 * @param int    $total_items       Total amount of items to format.
	 * @param string $format            'string' for BuddyBar-compatible notifications;
	 *                                  'array' for WP Toolbar.
	 * @param int    $notification_id   Notification ID.
	 * @param string $screen            Notification Screen type.
	 */
	do_action( 'groups_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen );

	return false;
}

/**
 * Remove all notifications for any member belonging to a specific group.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $group_id ID of the group.
 */
function bp_groups_delete_group_delete_all_notifications( $group_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_all_notifications_by_type( $group_id, buddypress()->groups->id );
	}
}
add_action( 'groups_delete_group', 'bp_groups_delete_group_delete_all_notifications', 10 );

/**
 * Remove Group invite notification when a user is uninvited.
 *
 * @since BuddyBoss 1.3.5
 * @since BuddyPress 5.0.0
 *
 * @param int $group_id ID of the group being uninvited from.
 * @param int $user_id  ID of the user being uninvited.
 */
function bp_groups_uninvite_user_delete_group_invite_notification( $group_id = 0, $user_id = 0 ) {
	if ( ! bp_is_active( 'notifications' ) || ! $group_id || ! $user_id ) {
		return;
	}

	bp_notifications_delete_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'group_invite' );
	bp_notifications_delete_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_new_invite' );
}
add_action( 'groups_uninvite_user', 'bp_groups_uninvite_user_delete_group_invite_notification', 10, 2 );

/**
 * When a demotion takes place, delete any corresponding promotion notifications.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 */
function bp_groups_delete_promotion_notifications( $user_id = 0, $group_id = 0 ) {
	if ( bp_is_active( 'notifications' ) && ! empty( $group_id ) && ! empty( $user_id ) ) {
		bp_notifications_delete_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'member_promoted_to_admin' );
		bp_notifications_delete_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'member_promoted_to_mod' );
	}
}
add_action( 'groups_demoted_member', 'bp_groups_delete_promotion_notifications', 10, 2 );

/**
 * Mark notifications read when a member accepts a group invitation.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 */
function bp_groups_accept_invite_mark_notifications( $user_id, $group_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'group_invite' );
		bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_new_invite' );
	}
}
add_action( 'groups_accept_invite', 'bp_groups_accept_invite_mark_notifications', 10, 2 );
add_action( 'groups_reject_invite', 'bp_groups_accept_invite_mark_notifications', 10, 2 );
add_action( 'groups_delete_invite', 'bp_groups_accept_invite_mark_notifications', 10, 2 );

/**
 * Mark notifications read when a member's group membership request is granted.
 *
 * @since BuddyPress 2.8.0
 *
 * @param int $user_id  ID of the user.
 * @param int $group_id ID of the group.
 */
function bp_groups_accept_request_mark_notifications( $user_id, $group_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		// First null parameter marks read for all admins.
		bp_notifications_mark_notifications_by_item_id( null, $group_id, buddypress()->groups->id, 'new_membership_request', $user_id );
		bp_notifications_mark_notifications_by_item_id( null, $group_id, buddypress()->groups->id, 'bb_groups_new_request', $user_id );
	}
}
add_action( 'groups_membership_accepted', 'bp_groups_accept_request_mark_notifications', 10, 2 );
add_action( 'groups_membership_rejected', 'bp_groups_accept_request_mark_notifications', 10, 2 );

/**
 * Mark notifications read when a member views their group memberships.
 *
 * @since BuddyPress 1.9.0
 */
function bp_groups_screen_my_groups_mark_notifications() {

	// Delete group request notifications for the user.
	if ( isset( $_GET['n'] ) && bp_is_active( 'notifications' ) ) {

		// Get the necessary ID's.
		$group_id = buddypress()->groups->id;
		$user_id  = bp_loggedin_user_id();

		// Mark notifications read.
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'membership_request_accepted' );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'membership_request_rejected' );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'member_promoted_to_mod' );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'member_promoted_to_admin' );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'bb_groups_request_accepted' );
		bp_notifications_mark_notifications_by_type( $user_id, $group_id, 'bb_groups_request_rejected' );
	}
}
add_action( 'groups_screen_my_groups', 'bp_groups_screen_my_groups_mark_notifications', 10 );
add_action( 'groups_screen_group_home', 'bp_groups_screen_my_groups_mark_notifications', 10 );

/*
 * Request membership screen in clear read notification and count.
 */
add_action( 'groups_screen_group_request_membership', 'bp_groups_screen_my_groups_mark_notifications', 10 );

/**
 * Mark group invitation notifications read when a member views their invitations.
 *
 * @since BuddyPress 1.9.0
 */
function bp_groups_screen_invites_mark_notifications() {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->groups->id, 'group_invite' );
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->groups->id, 'bb_groups_new_invite' );
	}
}
add_action( 'groups_screen_group_invites', 'bp_groups_screen_invites_mark_notifications', 10 );

/**
 * Mark group join requests read when an admin or moderator visits the group administration area.
 *
 * @since BuddyPress 1.9.0
 */
function bp_groups_screen_group_admin_requests_mark_notifications( $group_id ) {
	if ( bp_is_active( 'notifications' ) && ! empty( $group_id ) ) {
		// Mark as read group join requests notification.
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $group_id, buddypress()->groups->id, 'new_membership_request' );
		bp_notifications_mark_notifications_by_item_id( bp_loggedin_user_id(), $group_id, buddypress()->groups->id, 'bb_groups_new_request' );
	}
}
add_action( 'groups_screen_group_admin_requests', 'bp_groups_screen_group_admin_requests_mark_notifications', 10 );

/**
 * Delete new group membership notifications when a user is being deleted.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id ID of the user.
 */
function bp_groups_remove_data_for_user_notifications( $user_id ) {
	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_delete_notifications_from_user( $user_id, buddypress()->groups->id, 'new_membership_request' );
		bp_notifications_delete_notifications_from_user( $user_id, buddypress()->groups->id, 'bb_groups_new_request' );
	}
}
add_action( 'groups_remove_data_for_user', 'bp_groups_remove_data_for_user_notifications', 10 );

/**
 * Render the group settings fields on the Notification Settings page.
 *
 * @since BuddyPress 1.0.0
 */
function groups_screen_notification_settings() {

	// Bail out if legacy method not enabled.
	if ( false === bb_enabled_legacy_email_preference() ) {
		return;
	}

	if ( ! $group_invite = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_invite', true ) ) {
		$group_invite = 'yes';
	}

	if ( ! $group_update = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_group_updated', true ) ) {
		$group_update = 'yes';
	}

	if ( ! $group_promo = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_admin_promotion', true ) ) {
		$group_promo = 'yes';
	}

	if ( ! $group_request = bp_get_user_meta( bp_displayed_user_id(), 'notification_groups_membership_request', true ) ) {
		$group_request = 'yes';
	}

	if ( ! $group_request_completed = bp_get_user_meta( bp_displayed_user_id(), 'notification_membership_request_completed', true ) ) {
		$group_request_completed = 'yes';
	}

	if ( true === bp_disable_group_messages() ) {
		if ( ! $group_message = bp_get_user_meta( bp_displayed_user_id(), 'notification_group_messages_new_message', true ) ) {
			$group_message = 'yes';
		}
	}
	?>

	<table class="notification-settings" id="groups-notification-settings">
		<thead>
		<tr>
			<th class="icon"></th>
			<th class="title"><?php esc_html_e( 'Social Groups', 'buddyboss' ); ?></th>
			<th class="yes"><?php esc_html_e( 'Yes', 'buddyboss' ); ?></th>
			<th class="no"><?php esc_html_e( 'No', 'buddyboss' ); ?></th>
		</tr>
		</thead>

		<tbody>
		<tr id="groups-notification-settings-invitation">
			<td></td>
			<td><?php esc_html_e( 'A member invites you to join a group', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_invite]" id="notification-groups-invite-yes" class="bs-styled-radio" value="yes" <?php checked( $group_invite, 'yes', true ); ?> />
					<label for="notification-groups-invite-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_invite]" id="notification-groups-invite-no" class="bs-styled-radio" value="no" <?php checked( $group_invite, 'no', true ); ?> />
					<label for="notification-groups-invite-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>
		<tr id="groups-notification-settings-info-updated">
			<td></td>
			<td><?php esc_html_e( 'Group information is updated', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_group_updated]" id="notification-groups-group-updated-yes" class="bs-styled-radio" value="yes" <?php checked( $group_update, 'yes', true ); ?> />
					<label for="notification-groups-group-updated-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_group_updated]" id="notification-groups-group-updated-no" class="bs-styled-radio" value="no" <?php checked( $group_update, 'no', true ); ?> />
					<label for="notification-groups-group-updated-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>
		<tr id="groups-notification-settings-promoted">
			<td></td>
			<td><?php esc_html_e( 'You are promoted to a group organizer or moderator', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_admin_promotion]" id="notification-groups-admin-promotion-yes" class="bs-styled-radio" value="yes" <?php checked( $group_promo, 'yes', true ); ?> />
					<label for="notification-groups-admin-promotion-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_admin_promotion]" id="notification-groups-admin-promotion-no" class="bs-styled-radio" value="no" <?php checked( $group_promo, 'no', true ); ?> />
					<label for="notification-groups-admin-promotion-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>
		<tr id="groups-notification-settings-request">
			<td></td>
			<td><?php esc_html_e( 'A member requests to join a private group you organize', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_membership_request]" id="notification-groups-membership-request-yes" class="bs-styled-radio" value="yes" <?php checked( $group_request, 'yes', true ); ?> />
					<label for="notification-groups-membership-request-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_groups_membership_request]" id="notification-groups-membership-request-no" class="bs-styled-radio" value="no" <?php checked( $group_request, 'no', true ); ?> />
					<label for="notification-groups-membership-request-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>
		<tr id="groups-notification-settings-request-completed">
			<td></td>
			<td><?php esc_html_e( 'Your request to join a group has been approved or denied', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_membership_request_completed]" id="notification-groups-membership-request-completed-yes" class="bs-styled-radio" value="yes" <?php checked( $group_request_completed, 'yes', true ); ?> />
					<label for="notification-groups-membership-request-completed-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_membership_request_completed]" id="notification-groups-membership-request-completed-no" class="bs-styled-radio" value="no" <?php checked( $group_request_completed, 'no', true ); ?> />
					<label for="notification-groups-membership-request-completed-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>

		<?php
		if ( true === bp_disable_group_messages() ) {
			?>
			<tr id="groups-notification-settings-request-messages">
				<td></td>
				<td><?php esc_html_e( 'A group sends you a new message', 'buddyboss' ); ?></td>
				<td class="yes">
					<div class="bp-radio-wrap">
						<input type="radio" name="notifications[notification_group_messages_new_message]" id="notification-groups-messages-yes" class="bs-styled-radio" value="yes" <?php checked( $group_message, 'yes', true ); ?> />
						<label for="notification-groups-messages-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
				<td class="no">
					<div class="bp-radio-wrap">
						<input type="radio" name="notifications[notification_group_messages_new_message]" id="notification-groups-messages-no" class="bs-styled-radio" value="no" <?php checked( $group_message, 'no', true ); ?> />
						<label for="notification-groups-messages-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
			</tr>
			<?php
		}
		?>

		<?php

		/**
		 * Fires at the end of the available group settings fields on Notification Settings page.
		 *
		 * @since BuddyPress 1.0.0
		 */
		do_action( 'groups_screen_notification_settings' );
		?>

		</tbody>
	</table>

	<?php

}

add_action( 'bp_notification_settings', 'groups_screen_notification_settings' );

/**
 * Fire user notification when group information has been updated.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param int $group_id Group id.
 *
 * @return void
 */
function bb_groups_notification_groups_updated( $group_id = 0 ) {
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	$user_ids = (array) BP_Groups_Member::get_group_member_ids( $group_id );

	if ( empty( $user_ids ) ) {
		return;
	}

	$sender_id = bp_loggedin_user_id();

	// Remove sender from the group member list.
	$unset_sender_key = array_search( $sender_id, $user_ids, true );
	if ( false !== $unset_sender_key ) {
		unset( $user_ids[ $unset_sender_key ] );
	}

	$min_count = (int) apply_filters( 'bb_groups_details_updated_notifications_count', 10 );
	if (
		function_exists( 'bb_notifications_background_enabled' ) &&
		true === bb_notifications_background_enabled() &&
		count( $user_ids ) > $min_count
	) {
		global $bb_background_updater;

		$chunk_user_ids = array_chunk( $user_ids, $min_count );
		if ( ! empty( $chunk_user_ids ) ) {
			foreach ( $chunk_user_ids as $chunk_user_id ) {
				$bb_background_updater->data(
					array(
						'type'     => 'notification',
						'group'    => 'groups_updated_notification',
						'data_id'  => $group_id,
						'priority' => 5,
						'callback' => 'bb_add_background_notifications',
						'args'     => array(
							$chunk_user_id,
							$group_id,
							$sender_id,
							buddypress()->groups->id,
							'bb_groups_details_updated',
							bp_core_current_time(),
							true,
						),
					),
				);
				$bb_background_updater->save();
			}
		}
		$bb_background_updater->dispatch();
	} else {
		foreach ( $user_ids  as $user_id ) {
			bp_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $group_id,
					'secondary_item_id' => $sender_id,
					'component_name'    => buddypress()->groups->id,
					'component_action'  => 'bb_groups_details_updated',
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			);
		}
	}
}

/**
 * Mark group detail update notifications as read when a member views their group.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_groups_group_details_update_mark_notifications() {
	if ( isset( $_GET['n'] ) && bp_is_active( 'notifications' ) && bp_is_group_single() ) {

		// Get the necessary ID's.
		$group_id = bp_get_current_group_id();
		$user_id  = bp_loggedin_user_id();

		// Mark notifications read.
		bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_details_updated' );
		bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_promoted' );
	}
}

add_action( 'bp_template_redirect', 'bb_groups_group_details_update_mark_notifications' );

/**
 * Create notification meta based on groups.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param object $notification Notification object.
 */
function bb_groups_add_notification_metas( $notification ) {
	if (
		bb_enabled_legacy_email_preference() ||
		empty( $notification->id ) ||
		empty( $notification->item_id ) ||
		empty( $notification->user_id ) ||
		empty( $notification->component_action ) ||
		'bb_groups_promoted' !== $notification->component_action
	) {
		return;
	}

	$group_id = $notification->item_id;
	$user_id  = $notification->user_id;

	if ( groups_is_user_admin( $user_id, $group_id ) ) {
		$promoted_to = get_group_role_label( $group_id, 'organizer_singular_label_name' );
	} else {
		$promoted_to = get_group_role_label( $group_id, 'moderator_singular_label_name' );
	}

	bp_notifications_update_meta( $notification->id, 'promoted_to', $promoted_to );
}
