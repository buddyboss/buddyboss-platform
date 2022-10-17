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
				'situation_label'     => __( 'A member recieves an invite to join a group', 'buddyboss' ),
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
				'email_title'         => __( '[{{{site.name}}}] New message from group: "{{group.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "{{sender.name}} from {{group.name}} sent you a new message.\n\n{{{message}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{sender.name}} from {{group.name}} sent you a new message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives a new group message', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you a group message.', 'buddyboss' ),
			),
			'bb_groups_new_message'
		);

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
}
