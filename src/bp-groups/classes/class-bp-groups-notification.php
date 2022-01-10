<?php
/**
 * BuddyBoss Groups Notification Class.
 *
 * @package BuddyBoss\Groups
 *
 * @since   BuddyBoss [BBVERSION]
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
		$this->register_preferences_group(
			buddypress()->groups->id,
			esc_html__( 'Social Groups', 'buddyboss' ),
			esc_html__( 'Social Groups Notifications', 'buddyboss' ),
			6
		);

		// Group user invites.
		$this->register_notification_for_group_invite();

		// Group information update.
		$this->register_notification_for_group_updated();

		// Group user has been promoted as admin/mod.
		$this->register_notification_for_group_user_promotion();

		// User request for the group membership.
		$this->register_notification_for_group_membership_request();

		// Group membership request has been accepted/rejected.
		$this->register_notification_for_group_membership_request_completed();

		if ( true === bp_disable_group_messages() ) {
			$this->register_notification_for_group_user_messages();
		}
	}

	/**
	 * Register notification for user invites.
	 */
	public function register_notification_for_group_invite() {
		$this->register_preference(
			'notification_groups_invite',
			esc_html__( 'A member invites you to join a group', 'buddyboss' ),
			'',
			buddypress()->groups->id
		);

		$this->register_email_type(
			'groups-invitation',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] You have an invitation to the group: "{{group.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{inviter.url}}}\">{{inviter.name}}</a> has invited you to join the group: <a href=\"{{{group.url}}}\">{{group.name}}</a>.\n\n{{{group.invite_message}}}\n\n{{{group.small_card}}}\n\n<a href=\"{{{invites.url}}}\">Click here</a> to manage this and all other pending group invites.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{inviter.name}} has invited you to join the group: \"{{group.name}}\".\n\n{{{group.invite_message}}}\n\nTo accept your invitation, visit: {{{invites.url}}}\n\nTo learn more about the group, visit: {{{group.url}}}.\nTo view {{inviter.name}}'s profile, visit: {{{inviter.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'A member has sent a group invitation to the recipient.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_groups_invite',
					'message'  => __( 'You will no longer receive emails when you are invited to join a group.', 'buddyboss' ),
				),
			),
			'notification_groups_invite'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'group_invite',
			'notification_groups_invite'
		);
	}

	/**
	 * Register notification for group update.
	 */
	public function register_notification_for_group_updated() {
		$this->register_preference(
			'notification_groups_group_updated',
			esc_html__( 'Group information is updated', 'buddyboss' ),
			'',
			buddypress()->groups->id
		);

		$this->register_email_type(
			'groups-details-updated',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Group details updated', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Group details for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; were updated.\n\n{{{group.description}}}\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Group details for the group \"{{group.name}}\" were updated:\n\n{{changed_text}}\n\nTo view the group, visit: {{{group.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( "A group's details were updated.", 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_groups_group_updated',
					'message'  => __( 'You will no longer receive emails when one of your groups is updated.', 'buddyboss' ),
				),
			),
			'notification_groups_group_updated'
		);
	}

	/**
	 * Register notification for group user has been promoted as admin/mod.
	 */
	public function register_notification_for_group_user_promotion() {
		$this->register_preference(
			'notification_groups_admin_promotion',
			esc_html__( 'You are promoted to a group organizer or moderator', 'buddyboss' ),
			esc_html__( 'A member is promoted to a group organizer or moderator', 'buddyboss' ),
			buddypress()->groups->id
		);

		$this->register_email_type(
			'groups-member-promoted',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] You have been promoted in the group: "{{group.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "You have been promoted to <b>{{promoted_to}}</b> in the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;.\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "You have been promoted to {{promoted_to}} in the group: \"{{group.name}}\".\n\nTo visit the group, go to: {{{group.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( "Recipient's status within a group has changed.", 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_groups_admin_promotion',
					'message'  => __( 'You will no longer receive emails when you have been promoted in a group.', 'buddyboss' ),
				),
			),
			'notification_groups_admin_promotion'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'member_promoted_to_admin',
			'notification_groups_admin_promotion'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'member_promoted_to_mod',
			'notification_groups_admin_promotion'
		);
	}

	/**
	 * Register notification for the group membership.
	 */
	public function register_notification_for_group_membership_request() {
		$this->register_preference(
			'notification_groups_membership_request',
			esc_html__( 'A member requests to join a private group you organize', 'buddyboss' ),
			'',
			buddypress()->groups->id
		);

		$this->register_email_type(
			'groups-membership-request',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership request for group: {{group.name}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{profile.url}}}\">{{requesting-user.name}}</a> wants to join the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot;. As you are organizer of this group, you must either accept or reject the membership request.\n\n{{{member.card}}}\n\n<a href=\"{{{group-requests.url}}}\">Click here</a> to manage this and all other pending requests.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{requesting-user.name}} wants to join the group \"{{group.name}}\". As you are the organizer of this group, you must either accept or reject the membership request.\n\nTo manage this and all other pending requests, visit: {{{group-requests.url}}}\n\nTo view {{requesting-user.name}}'s profile, visit: {{{profile.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'A member has requested permission to join a group.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_groups_membership_request',
					'message'  => __( 'You will no longer receive emails when someone requests to be a member of your group.', 'buddyboss' ),
				),
			),
			'notification_groups_membership_request'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'new_membership_request',
			'notification_groups_membership_request'
		);
	}

	/**
	 * Register notification for membership request has been accepted/rejected.
	 */
	public function register_notification_for_group_membership_request_completed() {
		$this->register_preference(
			'notification_membership_request_completed',
			esc_html__( 'Your request to join a group has been approved or denied', 'buddyboss' ),
			esc_html__( 'A member\'s request to join a group has been approved or denied', 'buddyboss' ),
			buddypress()->groups->id
		);

		$this->register_email_type(
			'groups-membership-request-accepted',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" accepted', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been accepted.\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Your membership request for the group \"{{group.name}}\" has been accepted.\n\nTo view the group, visit: {{{group.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'Recipient had requested to join a group, which was accepted.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_membership_request_completed',
					'message'  => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
				),
			),
			'notification_membership_request_completed'
		);

		$this->register_email_type(
			'groups-membership-request-rejected',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] Membership request for group "{{group.name}}" rejected', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "Your membership request for the group &quot;<a href=\"{{{group.url}}}\">{{group.name}}</a>&quot; has been rejected.\n\n{{{group.small_card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "Your membership request for the group \"{{group.name}}\" has been rejected.\n\nTo request membership again, visit: {{{group.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'Recipient had requested to join a group, which was rejected.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_membership_request_completed',
					'message'  => __( 'You will no longer receive emails when your request to join a group has been accepted or denied.', 'buddyboss' ),
				),
			),
			'notification_membership_request_completed'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'membership_request_accepted',
			'notification_membership_request_completed'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'membership_request_rejected',
			'notification_membership_request_completed'
		);
	}

	/**
	 * Register notification for group messages
	 */
	public function register_notification_for_group_user_messages() {
		$this->register_preference(
			'notification_group_messages_new_message',
			esc_html__( 'A group sends you a new message', 'buddyboss' ),
			esc_html__( 'A member receives a new group message', 'buddyboss' ),
			buddypress()->groups->id
		);

		$this->register_email_type(
			'group-message-email',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New message from group: "{{group.name}}"', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{sender.name}} from {{group.name}} sent you a new message.\n\n{{{message}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{sender.name}} from {{group.name}} sent you a new message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'Recipient has received a group message.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_group_messages_new_message',
					'message'  => __( 'You will no longer receive emails when someone sends you a group message.', 'buddyboss' ),
				),
			),
			'notification_group_messages_new_message'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'new_message',
			'notification_group_messages_new_message'
		);
	}



}
