<?php
/**
 * BuddyBoss Groups Notification Class.
 *
 * @package BuddyBoss\Groups
 *
 * @since BuddyBoss 1.9.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Groups_Notification class.
 *
 * @since BuddyBoss 1.9.3
 */
class BP_Groups_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Instance of this class.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @return null|BP_Groups_Notification|Controller|object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function __construct() {
		// Initialize.
		$this->start();
	}

	/**
	 * Initialize all methods inside it.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @return mixed|void
	 */
	public function load() {
		$this->register_notification_group(
			'groups',
			esc_html__( 'Social Groups', 'buddyboss' ),
			esc_html__( 'Social Groups', 'buddyboss' ),
			9
		);

		// Group information update.
		$this->register_notification_for_group_updated();

		// Group user has been promoted as admin/mod.
		$this->register_notification_for_group_user_promotion();

		// Group user invites.
		$this->register_notification_for_group_invite();

		// User request for the group membership.
		$this->register_notification_for_group_membership_request();

		// Group membership request has been accepted.
		$this->register_notification_for_group_membership_request_accepted();

		// Group membership request has been rejected.
		$this->register_notification_for_group_membership_request_rejected();

		if ( true === bp_disable_group_messages() ) {
			$this->register_notification_for_group_user_messages();
		}

		// Registered notification for group activity and discussion subscription.
		$this->register_notification_for_group_subscriptions();

		$this->register_notification_filter(
			esc_html__( 'Group invitations and requests', 'buddyboss' ),
			array( 'bb_groups_new_invite', 'bb_groups_new_request', 'bb_groups_request_accepted', 'bb_groups_request_rejected' ),
			60
		);

		$this->register_notification_filter(
			esc_html__( 'Group promotions', 'buddyboss' ),
			array( 'bb_groups_promoted' ),
			75
		);

		$this->register_notification_filter(
			esc_html__( 'Group details changed', 'buddyboss' ),
			array( 'bb_groups_details_updated' ),
			80
		);
	}

	/**
	 * Register notification for group update.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_group_updated() {
		$this->register_notification_type(
			'bb_groups_details_updated',
			esc_html__( 'The details of a group you manage are updated', 'buddyboss' ),
			esc_html__( 'A group\'s details are updated', 'buddyboss' ),
			'groups'
		);

		$this->register_email_type(
			'groups-details-updated',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Group details updated', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "Group details for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; were updated.\n\n{{{group.description}}}\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "Group details for the group \"{{group.name}}\" were updated:\n\n{{changed_text}}\n\nTo view the group, visit: {{{group.url}}}", 'buddyboss' ),
				'situation_label'     => __( "A group's details are updated", 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when one of your groups is updated.', 'buddyboss' ),
			),
			'bb_groups_details_updated'
		);

		$this->register_notification(
			'groups',
			'bb_groups_details_updated',
			'bb_groups_details_updated'
		);

		add_filter( 'bp_groups_bb_groups_details_updated_notification', array( $this, 'bb_format_groups_notification' ), 10, 7 );
	}

	/**
	 * Register notification for group user has been promoted as admin/mod.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_group_user_promotion() {
		$this->register_notification_type(
			'bb_groups_promoted',
			__( 'You\'re promoted in a group', 'buddyboss' ),
			__( 'A member is promoted in a group', 'buddyboss' ),
			'groups'
		);

		$this->register_email_type(
			'groups-member-promoted',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] You have been promoted in the group: "{{group.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "You have been promoted to <b>{{promoted_to}}</b> in the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;.\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "You have been promoted to {{promoted_to}} in the group: \"{{group.name}}\".\n\nTo visit the group, go to: {{{group.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is promoted in a group', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you have been promoted in a group.', 'buddyboss' ),
			),
			'bb_groups_promoted'
		);

		$this->register_notification(
			'groups',
			'bb_groups_promoted',
			'bb_groups_promoted'
		);

		add_filter( 'bp_groups_bb_groups_promoted_notification', array( $this, 'bb_format_groups_notification' ), 10, 7 );
	}

	/**
	 * Register notification for user invites.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_group_invite() {
		$this->register_notification_type(
			'bb_groups_new_invite',
			esc_html__( 'You receive a new invite to join a group', 'buddyboss' ),
			esc_html__( 'A member receives an invite to join a group', 'buddyboss' ),
			'groups'
		);

		$this->register_email_type(
			'groups-invitation',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] You have an invitation to the group: "{{group.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join the group: <a href=\"{{{group.url}}}\">{{group.name}}</a>.\n\n{{{group.invite_message}}}\n\n{{{group.small_card}}}\n\n<a href=\"{{{invites.url}}}\">Click here</a> to manage this and all other pending group invites.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{inviter.name}} has invited you to join the group: \"{{group.name}}\".\n\n{{{group.invite_message}}}\n\nTo accept your invitation, visit: {{{invites.url}}}\n\nTo learn more about the group, visit: {{{group.url}}}.\nTo view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives an invite to join a group', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are invited to join a group.', 'buddyboss' ),
			),
			'bb_groups_new_invite'
		);

		$this->register_notification(
			'groups',
			'bb_groups_new_invite',
			'bb_groups_new_invite'
		);

		add_filter( 'bp_groups_bb_groups_new_invite_notification', array( $this, 'bb_format_groups_notification' ), 10, 7 );
	}

	/**
	 * Register notification for the group membership.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_group_membership_request() {
		$this->register_notification_type(
			'bb_groups_new_request',
			esc_html__( 'A member requests to join a group you manage', 'buddyboss' ),
			esc_html__( 'A group organizer receives a request to join their group', 'buddyboss' ),
			'groups'
		);

		$this->register_email_type(
			'groups-membership-request',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Membership request for group: {{group.name}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{profile.url}}}\">{{requesting-user.name}}</a> wants to join the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;. As you are organizer of this group, you must either accept or reject the membership request.\n\n{{{member.card}}}\n\n<a href=\"{{{group-requests.url}}}\">Click here</a> to manage this and all other pending requests.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{requesting-user.name}} wants to join the group \"{{group.name}}\". As you are the organizer of this group, you must either accept or reject the membership request.\n\nTo manage this and all other pending requests, visit: {{{group-requests.url}}}\n\nTo view {{requesting-user.name}}'s profile, visit: {{{profile.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A group organizer receives a request to join their group', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone requests to be a member of your group.', 'buddyboss' ),
			),
			'bb_groups_new_request'
		);

		$this->register_notification(
			'groups',
			'bb_groups_new_request',
			'bb_groups_new_request'
		);

		add_filter( 'bp_groups_bb_groups_new_request_notification', array( $this, 'bb_format_groups_notification' ), 10, 7 );

	}

	/**
	 * Register notification for membership request has been accepted.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_group_membership_request_accepted() {
		$this->register_notification_type(
			'bb_groups_request_accepted',
			esc_html__( 'Your request to join a group is accepted', 'buddyboss' ),
			esc_html__( 'A member is accepted into a group', 'buddyboss' ),
			'groups'
		);

		$this->register_email_type(
			'groups-membership-request-accepted',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" accepted', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been accepted.\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "Your membership request for the group \"{{group.name}}\" has been accepted.\n\nTo view the group, visit: {{{group.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is accepted into a group', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
			),
			'bb_groups_request_accepted'
		);

		$this->register_notification(
			'groups',
			'bb_groups_request_accepted',
			'bb_groups_request_accepted'
		);

		add_filter( 'bp_groups_bb_groups_request_accepted_notification', array( $this, 'bb_format_groups_notification' ), 10, 7 );

	}

	/**
	 * Register notification for membership request has been rejected.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_group_membership_request_rejected() {
		$this->register_notification_type(
			'bb_groups_request_rejected',
			esc_html__( 'Your request to join a group is rejected', 'buddyboss' ),
			esc_html__( 'A member is rejected from joining a group', 'buddyboss' ),
			'groups'
		);

		$this->register_email_type(
			'groups-membership-request-rejected',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" rejected', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been rejected.\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "Your membership request for the group \"{{group.name}}\" has been rejected.\n\nTo request membership again, visit: {{{group.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is rejected from joining a group', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
			),
			'bb_groups_request_rejected'
		);

		$this->register_notification(
			'groups',
			'bb_groups_request_rejected',
			'bb_groups_request_rejected'
		);

		add_filter( 'bp_groups_bb_groups_request_rejected_notification', array( $this, 'bb_format_groups_notification' ), 10, 7 );
	}

	/**
	 * Register notification for group messages.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_group_user_messages() {
		$this->register_notification_type(
			'bb_groups_new_message',
			esc_html__( 'You receive a new group message', 'buddyboss' ),
			esc_html__( 'A member receives a new group message', 'buddyboss' ),
			'groups'
		);

		$this->register_email_type(
			'group-message-email',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] New message from group: "{{{group.name}}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "{{{sender.name}}} from {{{group.name}}} sent you a message.\n\n{{{message}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{{sender.name}}} from {{{group.name}}} sent you a message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives a new group message', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you a group message.', 'buddyboss' ),
			),
			'bb_groups_new_message'
		);

		if ( function_exists( 'bb_check_delay_email_notification' ) && bb_check_delay_email_notification() ) {
			$this->register_email_type(
				'group-message-digest',
				array(
					/* translators: do not remove {} brackets or translate its contents. */
					'email_title'         => __( '[{{{site.name}}}] New messages from group: "{{{group.name}}}"', 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_content'       => __( "You have {{{unread.count}}} unread messages from {{{group.name}}}.\n\n{{{message}}}", 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_plain_content' => __( "You have {{{unread.count}}} unread messages from {{{group.name}}}.\n\n{{{message}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
					'situation_label'     => __( 'A member receives a new group message', 'buddyboss' ),
					'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you a group message.', 'buddyboss' ),
				),
				'bb_groups_new_message'
			);
		}

		$this->register_notification(
			'groups',
			'bb_groups_new_message',
			'bb_groups_new_message',
			'bb-icon-f bb-icon-comment'
		);
	}

	/**
	 * Format the notifications.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen ) {
		return $content;
	}

	/**
	 * Format Group notifications.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array
	 */
	public function bb_format_groups_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {

		$notification = bp_notifications_get_notification( $notification_id );

		// Group Details update.
		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_details_updated' === $notification->component_action ) {

			$group_id   = $item_id;
			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';

			$notification_link = $group_link . '?n=1';
			$title             = bp_get_group_name( $group );

			if ( 'web_push' === $screen ) {
				$text = __( 'The group details were updated', 'buddyboss' );
			} else {
				if ( (int) $total_items > 1 ) {
					$text = sprintf(
						/* translators: 1. group name. 2. total times. */
						esc_html__( 'The group details for "%1$s" were updated %2$d times.', 'buddyboss' ),
						$group->name,
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$text = sprintf(
					/* translators: group name */
						esc_html__( 'The group details for "%s" were updated', 'buddyboss' ),
						$group->name
					);
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $title,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$group_link,
				$group->name,
				$text,
				$notification_link,
				$screen
			);
		}

		// Group members promoted.
		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_promoted' === $notification->component_action ) {

			$group_id          = $item_id;
			$group             = groups_get_group( $group_id );
			$group_link        = bp_get_group_permalink( $group );
			$amount            = 'single';
			$notification_link = $group_link . '?n=1';
			$promote_text      = bp_notifications_get_meta( $notification_id, 'promoted_to', true );
			$title             = bp_get_group_name( $group );

			if ( 'web_push' === $screen ) {
				if ( $promote_text ) {
					$text = sprintf(
						/* translators: Promoted text. */
						__( 'Your role was changed to "%s"', 'buddyboss' ),
						$promote_text
					);
				} else {
					$text = __( 'Your role was changed', 'buddyboss' );
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$text = sprintf(
						/* translators: 1. User Role name. 2. total times. */
						__( 'You were promoted to a %1$s in %2$d groups', 'buddyboss' ),
						$promote_text,
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					if ( $promote_text ) {
						$text = sprintf(
							/* translators: group name */
							esc_html__( 'Your role in "%1$s" was changed to "%2$s"', 'buddyboss' ),
							$group->name,
							$promote_text
						);
					} else {
						$text = sprintf(
							/* translators: group name */
							esc_html__( 'Your role in "%1$s" was changed', 'buddyboss' ),
							$group->name
						);
					}
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $title,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$group_link,
				$group->name,
				$text,
				$notification_link,
				$screen
			);
		}

		// Member who is invited to join the group.
		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_new_invite' === $notification->component_action ) {

			$group_id   = $item_id;
			$group      = groups_get_group( $group_id );
			$group_link = bp_get_group_permalink( $group );
			$amount     = 'single';
			$title      = bp_get_group_name( $group );

			if ( 'web_push' === $screen ) {
				$notification_link = bp_core_get_user_domain( $notification->user_id ) . bp_get_groups_slug() . '/invites/?n=1';
				$text              = __( 'You\'ve been invited to join this group.', 'buddyboss' );
			} else {
				$notification_link = bp_loggedin_user_domain() . bp_get_groups_slug() . '/invites/?n=1';

				if ( (int) $total_items > 1 ) {
					$text = sprintf(
						/* translators: total times. */
						esc_html__( 'You have %d new group invitations', 'buddyboss' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$text = sprintf(
						/* translators: group name */
						esc_html__( 'You\'ve been invited to join "%s"', 'buddyboss' ),
						$group->name
					);
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $title,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$group_link,
				$group->name,
				$text,
				$notification_link,
				$screen
			);
		}

		// All group organizers in the group.
		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_new_request' === $notification->component_action ) {

			$group_id          = $item_id;
			$group             = groups_get_group( $group_id );
			$group_link        = bp_get_group_permalink( $group );
			$amount            = 'single';
			$title             = bp_get_group_name( $group );
			$notification_link = $group_link . 'admin/membership-requests/?n=1';

			if ( 'web_push' === $screen ) {
				$user_fullname = bp_core_get_user_displayname( $notification->secondary_item_id );
				$text          = sprintf(
					/* translators: user name */
					__( '%s has requested to join this group', 'buddyboss' ),
					$user_fullname
				);
			} else {
				if ( (int) $total_items > 1 ) {
					$text = sprintf(
						/* translators: 1. total times. 2. group name */
						esc_html__( '%1$d new membership requests for the group "%2$s"', 'buddyboss' ),
						(int) $total_items,
						$group->name
					);
					$amount = 'multiple';
				} else {
					$user_fullname = bp_core_get_user_displayname( $secondary_item_id );
					$text          = sprintf(
						/* translators: 1. user name. 2.group name. */
						esc_html__( '%1$s has requested to join "%2$s"', 'buddyboss' ),
						$user_fullname,
						$group->name
					);
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $title,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$group_link,
				$group->name,
				$text,
				$notification_link,
				$screen
			);
		}

		// Member whose group request was accepted.
		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_request_accepted' === $notification->component_action ) {

			$group_id          = $item_id;
			$group             = groups_get_group( $group_id );
			$group_link        = bp_get_group_permalink( $group );
			$amount            = 'single';
			$notification_link = $group_link . '?n=1';
			$title             = bp_get_group_name( $group );

			if ( 'web_push' === $screen ) {
				$text = __( 'Your request to join has been approved', 'buddyboss' );
			} else {
				if ( (int) $total_items > 1 ) {
					$text = sprintf(
					/* translators: total groups count. */
						esc_html__( '%d accepted group membership requests', 'buddyboss' ),
						(int) $total_items,
						$group->name
					);

					$amount            = 'multiple';
					$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';
				} else {
					$text = sprintf(
					/* translators: group name. */
						esc_html__( '%s has approved your request to join', 'buddyboss' ),
						$group->name
					);
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $title,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$group_link,
				$group->name,
				$text,
				$notification_link,
				$screen
			);
		}

		// Member whose group request was rejected.
		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_request_rejected' === $notification->component_action ) {

			$group_id          = $item_id;
			$group             = groups_get_group( $group_id );
			$group_link        = bp_get_group_permalink( $group );
			$amount            = 'single';
			$notification_link = $group_link . '?n=1';
			$title             = bp_get_group_name( $group );

			if ( 'web_push' === $screen ) {
				$text = __( 'Your request to join has been denied', 'buddyboss' );
			} else {
				if ( (int) $total_items > 1 ) {
					$text = sprintf(
					/* translators: total times */
						esc_html__( '%d rejected group membership requests', 'buddyboss' ),
						(int) $total_items,
						$group->name
					);

					$amount            = 'multiple';
					$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';
				} else {
					$text = sprintf(
					/* translators: group name. */
						esc_html__( '%s has denied your request to join', 'buddyboss' ),
						$group->name
					);
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $title,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$group_link,
				$group->name,
				$text,
				$notification_link,
				$screen
			);
		}

		// Validate the return value & return if validated.
		if (
			! empty( $content ) &&
			is_array( $content ) &&
			isset( $content['text'] ) &&
			isset( $content['link'] )
		) {
			if ( 'string' === $format ) {
				if ( empty( $content['link'] ) ) {
					$content = esc_html( $content['text'] );
				} else {
					$content = '<a href="' . esc_url( $content['link'] ) . '">' . esc_html( $content['text'] ) . '</a>';
				}
			} else {
				$content = array(
					'text'  => $content['text'],
					'link'  => $content['link'],
					'title' => ( isset( $content['title'] ) ? $content['title'] : '' ),
					'image' => ( isset( $content['image'] ) ? $content['image'] : '' ),
				);
			}
		}

		return $content;
	}

	/**
	 * Register notification for group activity subscriptions.
	 *
	 * @since BuddyBoss 2.2.8
	 */
	public function register_notification_for_group_subscriptions() {

		$subscription_types = array();

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) ) {
			// Register the group activity subscription notifications.
			$activity_notification_tooltip_text = __( 'Requires group subscriptions to enable', 'buddyboss' );
			if ( function_exists( 'bb_enable_group_subscriptions' ) && true === bb_enable_group_subscriptions() ) {
				$activity_notification_tooltip_text = __( 'Required by group subscriptions', 'buddyboss' );
			}

			$subscription_types[] = 'bb_groups_subscribed_activity';

			$this->register_notification_type(
				'bb_groups_subscribed_activity',
				esc_html__( 'New post in a group you\'re subscribed to', 'buddyboss' ),
				esc_html__( 'A new activity post in a group a member is subscribed to', 'buddyboss' ),
				'groups',
				function_exists( 'bb_enable_group_subscriptions' ) && true === bb_enable_group_subscriptions(),
				true,
				$activity_notification_tooltip_text
			);

			$this->register_email_type(
				'groups-new-activity',
				array(
					/* translators: do not remove {} brackets or translate its contents. */
					'email_title'         => __( '[{{{site.name}}}] {{poster.name}} posted {{activity.type}} in {{group.name}}', 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> posted {{activity.type}} in <a href=\"{{{group.url}}}\">{{group.name}}</a>:\n\n{{{activity.content}}}", 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_plain_content' => __( "{{poster.name}} posted {{activity.type}} in {{group.name}}:\n\n{{{activity.content}}}\"\n\nView the post: {{{activity.url}}}", 'buddyboss' ),
					'situation_label'     => __( 'New activity post in a group a member is subscribed to', 'buddyboss' ),
					'unsubscribe_text'    => __( 'You will no longer receive emails of new posts in groups you are subscribed to.', 'buddyboss' ),
				),
				'bb_groups_subscribed_activity'
			);

			$this->register_notification(
				'groups',
				'bb_groups_subscribed_activity',
				'bb_groups_subscribed_activity',
				'bb-icon-f bb-icon-comment'
			);

			add_filter( 'bp_groups_bb_groups_subscribed_activity_notification', array( $this, 'bb_format_groups_subscription_notification' ), 10, 7 );
		}

		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'forums' ) ) {

			// Register the group discussion subscription notifications.
			$discussion_notification_tooltip_text = __( 'Requires group subscriptions to enable', 'buddyboss' );
			if ( function_exists( 'bb_enable_group_subscriptions' ) && true === bb_enable_group_subscriptions() ) {
				$discussion_notification_tooltip_text = __( 'Required by group subscriptions', 'buddyboss' );
			}

			$this->register_notification_type(
				'bb_groups_subscribed_discussion',
				esc_html__( 'New discussion in a group you\'re subscribed to', 'buddyboss' ),
				esc_html__( 'A new discussion in a group a member is subscribed to', 'buddyboss' ),
				'groups',
				function_exists( 'bb_enable_group_subscriptions' ) && true === bb_enable_group_subscriptions(),
				true,
				$discussion_notification_tooltip_text
			);

			$this->register_email_type(
				'groups-new-discussion',
				array(
					/* translators: do not remove {} brackets or translate its contents. */
					'email_title'         => __( '[{{{site.name}}}] New discussion in {{group.name}}', 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> created a discussion in <a href=\"{{{group.url}}}\">{{group.name}}</a>:\n\n{{{discussion.content}}}", 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_plain_content' => __( "{{poster.name}} created a discussion {{discussion.title}} in {{group.name}}:\n\n{{{discussion.content}}}\n\nDiscussion Link: {{discussion.url}}", 'buddyboss' ),
					'situation_label'     => __( 'New forum discussion in a group a member is subscribed to', 'buddyboss' ),
					'unsubscribe_text'    => __( 'You will no longer receive emails of new discussions in groups you are subscribed to.', 'buddyboss' ),
				),
				'bb_groups_subscribed_discussion'
			);

			$this->register_notification(
				'groups',
				'bb_groups_subscribed_discussion',
				'bb_groups_subscribed_discussion',
				'bb-icon-f bb-icon-comment-square-dots'
			);

			add_filter( 'bp_groups_bb_groups_subscribed_discussion_notification', array( $this, 'bb_format_groups_subscription_notification' ), 10, 7 );

			$subscription_types[] = 'bb_groups_subscribed_discussion';
		}

		if ( ! empty( $subscription_types ) ) {
			// Register the subscription for group activity and discussion.
			$this->bb_register_subscription_type(
				array(
					'label'              => array(
						'singular' => __( 'Group', 'buddyboss' ),
						'plural'   => __( 'Groups', 'buddyboss' ),
					),
					'subscription_type'  => 'group',
					'items_callback'     => array( $this, 'bb_render_subscribed_groups' ),
					'send_callback'      => array( $this, 'bb_send_subscribed_group_notifications' ),
					'validate_callback'  => array( $this, 'bb_validate_group_subscription_request' ),
					'notification_type'  => $subscription_types,
					'notification_group' => 'groups',
				)
			);
		}
	}

	/**
	 * Validate callback function for group type subscription.
	 *
	 * @param array $args {
	 *     Used to validate the subscription request.
	 *
	 *     @type string $type                   Required. The subscription type.
	 *     @type string $item_id                Required. The subscription item ID.
	 *     @type string $secondary_item_id      Required. The subscription parent item ID.
	 * }
	 *
	 * @return bool|WP_Error
	 */
	public function bb_validate_group_subscription_request( $args ) {
		// Parse the arguments.
		$r = bp_parse_args(
			$args,
			array(
				'type'              => '',
				'blog_id'           => get_current_blog_id(),
				'item_id'           => 0,
				'secondary_item_id' => 0,
			)
		);

		// Initially set is false.
		$retval = false;

		$switch = false;
		// Switch to given blog_id if current blog is not same.
		if ( is_multisite() && get_current_blog_id() !== $r['blog_id'] ) {
			switch_to_blog( $r['blog_id'] );
			$switch = true;
		}

		if ( empty( $r['item_id'] ) ) {
			$retval = new WP_Error(
				'bb_subscription_required_item_id',
				__( 'The item ID is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( empty( $r['type'] ) ) {
			$retval = new WP_Error(
				'bb_subscription_required_item_type',
				__( 'The item type is required.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( 'group' !== $r['type'] ) {
			$retval = new WP_Error(
				'bb_subscription_invalid_item_id_or_type',
				__( 'The item id is not matching with the type.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			// Get group.
			$group = groups_get_group( $r['item_id'] );

			// Validate the secondary item if exists.
			if ( ! empty( $r['secondary_item_id'] ) && $r['secondary_item_id'] !== $group->parent_id ) {
				$retval = new WP_Error(
					'bb_subscription_invalid_secondary_item_id',
					__( 'The secondary item ID is not valid.', 'buddyboss' ),
					array(
						'status' => 400,
					)
				);
			} else {
				$retval = true;
			}
		}

		// Restore current blog.
		if ( $switch ) {
			restore_current_blog();
		}

		return $retval;
	}

	/**
	 * Render callback function on frontend.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @param array $items Array of subscription list.
	 *
	 * @return array
	 */
	public function bb_render_subscribed_groups( $items ) {
		static $cached_items = array();

		$type_data = bb_register_subscriptions_types( 'group' );

		if ( ! empty( $items ) ) {
			$blog_id = get_current_blog_id();

			$switch = false;
			// Switch to given blog_id if current blog is not same.
			if ( is_multisite() && get_current_blog_id() !== $blog_id ) {
				switch_to_blog( $blog_id );
				$switch = true;
			}

			foreach ( $items as $item_key => $item ) {
				$subscription = bp_parse_args(
					$item,
					array(
						'id'                => 0,
						'blog_id'           => get_current_blog_id(),
						'user_id'           => 0,
						'item_id'           => 0,
						'secondary_item_id' => 0,
					)
				);

				if (
					empty( $subscription['id'] ) ||
					empty( $subscription['item_id'] )
				) {
					continue;
				}

				if ( ! empty( $cached_items[ $subscription['id'] ] ) ) {
					$items[ $item_key ] = $cached_items[ $subscription['id'] ];
					continue;
				}

				$group_full_image  = bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $subscription['item_id'],
						'type'    => 'full',
					)
				);
				$group_thumb_image = bp_core_fetch_avatar(
					array(
						'html'    => false,
						'object'  => 'group',
						'item_id' => $subscription['item_id'],
						'type'    => 'thumb',
					)
				);

				// Get group.
				$group = groups_get_group( $subscription['item_id'] );

				$data = array();
				if ( empty( $group ) || empty( $group->id ) ) {
					$data['link']          = '';
					$data['icon']['full']  = $group_full_image;
					$data['icon']['thumb'] = $group_thumb_image;
					$data['title']         = sprintf(
					/* translators: Subscription label. */
						__( 'Deleted %s', 'buddyboss' ),
						$type_data['label']['singular']
					);
				} else {
					$data['title']         = $group->name;
					$data['link']          = bp_get_group_permalink( $group );
					$data['icon']['full']  = (string) $group_full_image;
					$data['icon']['thumb'] = (string) $group_thumb_image;
				}

				if ( ! empty( $group->parent_id ) ) {
					$data['parent_html'] = '<strong>' . bp_get_group_name( groups_get_group( $group->parent_id ) ) . '</strong>';
				}

				// Parse the data.
				$data = bp_parse_args(
					$data,
					array(
						'title'            => '',
						'description_html' => '',
						'parent_html'      => '',
						'icon'             => array(),
						'link'             => '',
					)
				);

				// Reassign the extra data to exist object.
				$items[ $item_key ]                  = (object) array_merge( (array) $item, $data );
				$cached_items[ $subscription['id'] ] = $items[ $item_key ];
			}

			// Restore current blog.
			if ( $switch ) {
				restore_current_blog();
			}
		}

		return $items;
	}

	/**
	 * Send callback function for group type notification.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return bool|void
	 */
	public function bb_send_subscribed_group_notifications( $args ) {

		// Bail if Legacy mode is enabled.
		if ( bb_enabled_legacy_email_preference() ) {
			return false;
		}

		$r = bp_parse_args(
			$args,
			array(
				'type'              => '',
				'item_id'           => '',
				'data'              => array(),
				'notification_type' => '',
				'notification_from' => '',
				'user_ids'          => array(),
			)
		);

		if ( empty( $r['user_ids'] ) || empty( $r['type'] ) || empty( $r['notification_type'] ) || ! bb_is_enabled_subscription( $r['type'] ) || empty( $r['notification_from'] ) ) {
			return false;
		}

		$data_id                 = 0;
		$author_id               = 0;
		$type_key                = '';
		$email_notification_type = '';
		$usernames               = array();
		if ( 'bb_groups_subscribed_activity' === $r['notification_from'] ) {
			// Bail if component is not activated.
			if ( ! bp_is_active( 'activity' ) ) {
				return false;
			}

			$data_id  = ! empty( $r['data']['activity_id'] ) ? $r['data']['activity_id'] : 0;
			$activity = ! empty( $r['data']['email_tokens']['tokens']['activity'] ) ? $r['data']['email_tokens']['tokens']['activity'] : '';

			if ( empty( $activity ) ) {
				$activity = new BP_Activity_Activity( $data_id );
			}

			if ( empty( $activity ) || 'groups' !== $activity->component ) {
				return false;
			}

			$type_key                = 'bb_groups_subscribed_activity';
			$email_notification_type = 'groups-new-activity';
			$author_id               = ! empty( $activity->user_id ) ? $activity->user_id : 0;
			$usernames               = function_exists( 'bp_activity_do_mentions' ) && bp_activity_do_mentions() ? bp_activity_find_mentions( $activity->content ) : array();
		} elseif ( 'bb_groups_subscribed_discussion' === $r['notification_from'] ) {
			// Bail if component is not activated.
			if ( ! bp_is_active( 'forums' ) || ! function_exists( 'bbp_get_topic_content' ) ) {
				return false;
			}

			$data_id                 = ! empty( $r['data']['topic_id'] ) ? $r['data']['topic_id'] : 0;
			$type_key                = 'bb_groups_subscribed_discussion';
			$email_notification_type = 'groups-new-discussion';
			$author_id               = ! empty( $r['data']['author_id'] ) ? $r['data']['author_id'] : bbp_get_topic_author_id( $data_id );
			$usernames               = function_exists( 'bp_find_mentions_by_at_sign' ) ? bp_find_mentions_by_at_sign( array(), bbp_get_topic_content( $data_id ) ) : array();
		}

		if ( empty( $data_id ) || empty( $author_id ) || empty( $type_key ) || empty( $email_notification_type ) ) {
			return false;
		}

		$email_tokens = ! empty( $r['data']['email_tokens'] ) ? $r['data']['email_tokens'] : array();

		// Remove author from the users.
		$unset_post_key = array_search( $author_id, $r['user_ids'], true );
		if ( false !== $unset_post_key ) {
			unset( $r['user_ids'][ $unset_post_key ] );
		}

		foreach ( $r['user_ids'] as $user_id ) {
			$user_id           = (int) $user_id;
			$send_mail         = true;
			$send_notification = true;

			// Check the mention notification is enabled then disabled to send email notification.
			if ( ! empty( $usernames ) && isset( $usernames[ $user_id ] ) ) {
				if ( true === bb_is_notification_enabled( $user_id, 'bb_new_mention' ) ) {
					$send_mail = false;
				}
			}

			// It will check some condition to notification disable or disabled.
			if ( false === bb_is_notification_enabled( $user_id, $type_key ) ) {
				$send_mail = false;
			}

			// It will check the moderation part.
			if (
				function_exists( 'bb_moderation_allowed_specific_notification' ) &&
				bb_moderation_allowed_specific_notification(
					array(
						'type'              => buddypress()->groups->id,
						'group_id'          => $r['item_id'],
						'recipient_user_id' => $user_id,
					)
				)
			) {
				$send_notification = false;
				$send_mail         = false;
			}

			if ( true === $send_mail ) {
				$unsubscribe_args = array(
					'user_id'           => $user_id,
					'notification_type' => $email_notification_type,
				);

				$email_tokens['tokens']['unsubscribe']      = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );
				$email_tokens['tokens']['receiver-user.id'] = $user_id;

				// Send notification email.
				bp_send_email( $email_notification_type, (int) $user_id, $email_tokens );
			}

			if ( true === $send_notification && bp_is_active( 'notifications' ) ) {
				add_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
				bp_notifications_add_notification(
					array(
						'user_id'           => $user_id,
						'item_id'           => $data_id,
						'secondary_item_id' => $author_id,
						'component_name'    => buddypress()->groups->id,
						'component_action'  => $type_key,
						'date_notified'     => bp_core_current_time(),
						'is_new'            => 1,
					)
				);
				remove_filter( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
			}
		}

		return true;
	}

	/**
	 * Format Group activity notifications.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array
	 */
	public function bb_format_groups_subscription_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {

		$notification = bp_notifications_get_notification( $notification_id );

		// Group activity update.
		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_subscribed_activity' === $notification->component_action ) {

			$user_id           = $secondary_item_id;
			$user_fullname     = bp_core_get_user_displayname( $user_id );
			$notification_link = bp_get_notifications_permalink();
			$activity          = new BP_Activity_Activity( $item_id );
			$activity_excerpt  = '"' . bp_create_excerpt(
				wp_strip_all_tags( $activity->content ),
				50,
				array(
					'ending' => __( '&hellip;', 'buddyboss' ),
				)
			) . '"';

			if ( '&nbsp;' === $activity_excerpt ) {
				$activity_excerpt = '';
			}

			$activity_excerpt = str_replace( '&hellip;"', '&hellip;', $activity_excerpt );
			$activity_excerpt = str_replace( '&#8203;', '', $activity_excerpt );
			$activity_excerpt = str_replace( '""', '', $activity_excerpt );

			$media_ids    = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );
			$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );
			$video_ids    = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );
			$gif_data     = bp_activity_get_meta( $activity->id, '_gif_data', true );
			$amount       = 'single';
			$group        = groups_get_group( $activity->item_id );
			$group_name   = bp_get_group_name( $group );

			if ( 'web_push' === $screen ) {
				$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );
				if ( ! empty( $activity_excerpt ) ) {
					$text = sprintf(
					/* translators: 1: User full name, 2: Activity content. */
						__( '%1$s posted an update: %2$s', 'buddyboss' ),
						$user_fullname,
						$activity_excerpt
					);
				} elseif ( $media_ids ) {
					$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
					if ( count( $media_ids ) > 1 ) {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted some photos', 'buddyboss' ),
							$user_fullname
						);
					} else {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted a photo', 'buddyboss' ),
							$user_fullname
						);
					}
				} elseif ( $document_ids ) {
					$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
					if ( count( $document_ids ) > 1 ) {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted some documents', 'buddyboss' ),
							$user_fullname
						);
					} else {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted a document', 'buddyboss' ),
							$user_fullname
						);
					}
				} elseif ( $video_ids ) {
					$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
					if ( count( $video_ids ) > 1 ) {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted some videos', 'buddyboss' ),
							$user_fullname
						);
					} else {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted a video', 'buddyboss' ),
							$user_fullname
						);
					}
				} elseif ( ! empty( $gif_data ) ) {
					$text = sprintf(
					/* translators: User full name. */
						__( '%1$s posted a gif', 'buddyboss' ),
						$user_fullname
					);
				} else {
					$text = sprintf(
					/* translators: %s: User full name. */
						__( '%1$s posted an update', 'buddyboss' ),
						$user_fullname
					);
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$notification_link = add_query_arg( 'type', $notification->component_action, $notification_link );
					$text              = sprintf(
					/* translators: %s: Total reply count. */
						__( 'You have %1$d new posts', 'buddyboss' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );

					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: 1: User full name, 2: Group name, 3: Activity content. */
							__( '%1$s posted an update in %2$s: %3$s', 'buddyboss' ),
							$user_fullname,
							$group_name,
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
						if ( count( $media_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name, 2: Group name. */
								__( '%1$s posted some photos in %2$s', 'buddyboss' ),
								$user_fullname,
								$group_name
							);
						} else {
							$text = sprintf(
							/* translators: User full name, 2: Group name. */
								__( '%1$s posted a photo in %2$s', 'buddyboss' ),
								$user_fullname,
								$group_name
							);
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
						if ( count( $document_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name, 2: Group name. */
								__( '%1$s posted some documents in %2$s', 'buddyboss' ),
								$user_fullname,
								$group_name
							);
						} else {
							$text = sprintf(
							/* translators: User full name, 2: Group name. */
								__( '%1$s posted a document in %2$s', 'buddyboss' ),
								$user_fullname,
								$group_name
							);
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
						if ( count( $video_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name, 2: Group name. */
								__( '%1$s posted some videos in %2$s', 'buddyboss' ),
								$user_fullname,
								$group_name
							);
						} else {
							$text = sprintf(
							/* translators: User full name, 2: Group name. */
								__( '%1$s posted a video in %2$s', 'buddyboss' ),
								$user_fullname,
								$group_name
							);
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = sprintf(
						/* translators: User full name, 2: Group name. */
							__( '%1$s posted a gif in %2$s', 'buddyboss' ),
							$user_fullname,
							$group_name
						);
					} else {
						$text = sprintf(
						/* translators: %s: User full name, 2: Group name. */
							__( '%1$s posted an update in %2$s', 'buddyboss' ),
							$user_fullname,
							$group_name
						);
					}
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $group_name,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$notification,
				$notification_link,
				$text,
				$screen
			);
		}

		if ( ! empty( $notification ) && 'groups' === $notification->component_name && 'bb_groups_subscribed_discussion' === $notification->component_action ) {

			$user_id           = $secondary_item_id;
			$user_fullname     = bp_core_get_user_displayname( $user_id );
			$amount            = 'single';
			$discussion_title  = '"' . bp_create_excerpt(
				wp_strip_all_tags( bbp_get_topic_title( $item_id ) ),
				50,
				array(
					'ending' => __( '&hellip;', 'buddyboss' ),
				)
			) . '"';
			$notification_link = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'bbp_mark_read',
						'topic_id' => $item_id,
					),
					bbp_get_topic_permalink( $item_id )
				),
				'bbp_mark_topic_' . $item_id
			);

			// Get the topic.
			$topic = get_post( $item_id );

			// Get forum group IDs.
			$group_ids  = function_exists( 'bbp_get_forum_group_ids' ) && ! empty( $topic->post_parent ) ? bbp_get_forum_group_ids( $topic->post_parent ) : array();
			$group_id   = ( ! empty( $group_ids ) ? current( $group_ids ) : 0 );
			$group_name = bp_get_group_name( groups_get_group( $group_id ) );

			if ( 'web_push' === $screen ) {
				$text = sprintf(
				/* translators: 1: User full name, 2: Group name, 3: Discussion Title. */
					__( '%1$s started a discussion%2$s', 'buddyboss' ),
					$user_fullname,
					! empty( $discussion_title ) ? ': ' . $discussion_title : ''
				);
			} else {
				if ( (int) $total_items > 1 ) {
					$notification_link = add_query_arg( 'type', $notification->component_action, $notification_link );
					$text              = sprintf(
					/* translators: %s: Total reply count. */
						__( 'You have %1$d new discussions', 'buddyboss' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$text = sprintf(
					/* translators: 1: User full name, 2: Group name, 3: Discussion Title. */
						__( '%1$s started a discussion%2$s%3$s', 'buddyboss' ),
						$user_fullname,
						! empty( $group_name ) ? ' in ' . $group_name : '',
						! empty( $discussion_title ) ? ': ' . $discussion_title : ''
					);
				}
			}

			$content = apply_filters(
				'bb_groups_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $group_name,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$notification,
				$notification_link,
				$text,
				$screen
			);
		}

		// Validate the return value & return if validated.
		if (
			! empty( $content ) &&
			is_array( $content ) &&
			isset( $content['text'] ) &&
			isset( $content['link'] )
		) {
			if ( 'string' === $format ) {
				if ( empty( $content['link'] ) ) {
					$content = esc_html( $content['text'] );
				} else {
					$content = '<a href="' . esc_url( $content['link'] ) . '">' . esc_html( $content['text'] ) . '</a>';
				}
			} else {
				$content = array(
					'text'  => $content['text'],
					'link'  => $content['link'],
					'title' => ( isset( $content['title'] ) ? $content['title'] : '' ),
					'image' => ( isset( $content['image'] ) ? $content['image'] : '' ),
				);
			}
		}

		return $content;
	}
}
