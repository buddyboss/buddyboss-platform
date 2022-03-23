<?php
/**
 * BuddyBoss Groups Notification Class.
 *
 * @package BuddyBoss\Groups
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Groups_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Groups_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		// Initialize.
		$this->start();
	}

	/**
	 * Initialize all methods inside it.
	 *
	 * @return mixed|void
	 */
	public function load() {
		$this->register_notification_group(
			'groups',
			esc_html__( 'Social Groups', 'buddyboss' ),
			esc_html__( 'Social Groups Notifications', 'buddyboss' ),
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
	}

	/**
	 * Register notification for group update.
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
				'situation_label'     => __( "A group's details were updated.", 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when one of your groups is updated.', 'buddyboss' ),
			),
			'bb_groups_details_updated'
		);

		$this->register_notification(
			'groups',
			'group_details_updated',
			'bb_groups_details_updated'
		);
	}

	/**
	 * Register notification for group user has been promoted as admin/mod.
	 */
	public function register_notification_for_group_user_promotion() {
		$this->register_notification_type(
			'bb_groups_promoted',
			esc_html__( 'You\'re promoted in a group', 'buddyboss' ),
			esc_html__( 'A member is promoted in a group', 'buddyboss' ),
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
				'situation_label'     => __( "Recipient's status within a group has changed.", 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you have been promoted in a group.', 'buddyboss' ),
			),
			'bb_groups_promoted'
		);

		$this->register_notification(
			'groups',
			'bb_groups_promoted',
			'bb_groups_promoted',
			true,
			__( 'Group promotions', 'buddyboss' ),
			85
		);
	}

	/**
	 * Register notification for user invites.
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
				'situation_label'     => __( 'A member has sent a group invitation to the recipient.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are invited to join a group.', 'buddyboss' ),
			),
			'bb_groups_new_invite'
		);

		$this->register_notification(
			'groups',
			'bb_groups_new_invite',
			'bb_groups_new_invite',
			true,
			__( 'Group invitations', 'buddyboss' ),
			105
		);
	}

	/**
	 * Register notification for the group membership.
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
				'situation_label'     => __( 'A member has requested permission to join a group.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone requests to be a member of your group.', 'buddyboss' ),
			),
			'bb_groups_new_request'
		);

		$this->register_notification(
			'groups',
			'bb_groups_new_request',
			'bb_groups_new_request',
			true,
			__( 'Pending Group membership requests', 'buddyboss' ),
			55
		);
	}

	/**
	 * Register notification for membership request has been accepted.
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
				'situation_label'     => __( 'Recipient had requested to join a group, which was accepted.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
			),
			'bb_groups_request_accepted'
		);

		$this->register_notification(
			'groups',
			'bb_groups_request_accepted',
			'bb_groups_request_accepted',
			true,
			__( 'Accepted Group membership requests', 'buddyboss' ),
			65
		);

	}

	/**
	 * Register notification for membership request has been rejected.
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
				'situation_label'     => __( 'Recipient had requested to join a group, which was rejected.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
			),
			'bb_groups_request_rejected'
		);

		$this->register_notification(
			'groups',
			'bb_groups_request_rejected',
			'bb_groups_request_rejected',
			true,
			__( 'Rejected Group membership requests', 'buddyboss' ),
			75
		);
	}

	/**
	 * Register notification for group messages
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
				'situation_label'     => __( 'Recipient has received a group message.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you a group message.', 'buddyboss' ),
			),
			'bb_groups_new_message'
		);

		$this->register_notification(
			'groups',
			'bb_groups_new_message',
			'bb_groups_new_message'
		);
	}

	/**
	 * Format the notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $action_item_count     Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array
	 */
	public function format_notification( $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $notification_id, $screen ) {
		return array();
	}
}
