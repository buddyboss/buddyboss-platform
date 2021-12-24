<?php
/**
 * BuddyBoss Connections Notification Class.
 *
 * @package BuddyBoss/Friends
 *
 * @since   BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Friends_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Friends_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->register_preferences_group(
			buddypress()->friends->id,
			esc_html__( 'Connections', 'buddyboss' ),
			esc_html__( 'Connections Notifications', 'buddyboss' )
		);

		$this->register_notification_for_friendship_request();
		$this->register_notification_for_friendship_accept();

		$this->start();
	}

	/**
	 * Register notification for user friendship request.
	 */
	public function register_notification_for_friendship_request() {
		$this->register_preference(
			buddypress()->friends->id,
			'notification_friends_friendship_request',
			esc_html__( 'A member invites you to connect', 'buddyboss' ),
			esc_html__( 'A member receives a new connection request', 'buddyboss' )
		);

		$this->register_email_type(
			'friends-request',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New request to connect from {{initiator.name}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{initiator.url}}}\">{{initiator.name}}</a> wants to add you as a connection.\n\n{{{member.card}}}\n\n<a href=\"{{{friend-requests.url}}}\">Click here</a> to manage this and all other pending requests.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{initiator.name}} wants to add you as a connection.\n\nTo accept this request and manage all of your pending requests, visit: {{{friend-requests.url}}}\n\nTo view {{initiator.name}}'s profile, visit: {{{initiator.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'A member has sent an invitation to connect to the recipient.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_friends_friendship_request',
					'message'  => __( 'You will no longer receive emails when someone sends you an invitation to connect.', 'buddyboss' ),
				),
			),
			'notification_friends_friendship_request'
		);

		$this->register_notification(
			buddypress()->friends->id,
			'friendship_request',
			'notification_friends_friendship_request'
		);
	}

	/**
	 * Register notification for friendship accept.
	 */
	public function register_notification_for_friendship_accept() {
		$this->register_preference(
			buddypress()->friends->id,
			'notification_friends_friendship_accepted',
			esc_html__( 'A member accepts your connection request', 'buddyboss' ),
			esc_html__( 'A member\'s connection request is accepted', 'buddyboss' ),
		);

		$this->register_email_type(
			'friends-request-accepted',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{friend.name}} accepted your request to connect', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{friendship.url}}}\">{{friend.name}}</a> accepted your request to connect.\n\n{{{member.card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{friend.name}} accepted your friend request.\n\nTo learn more about them, visit their profile: {{{friendship.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'Recipient has had an invitation to connect accepted by a member.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_friends_friendship_accepted',
					'message'  => __( 'You will no longer receive emails when someone accepts your invitation to connect.', 'buddyboss' ),
				),
			),
			'notification_friends_friendship_accepted'
		);

		$this->register_notification(
			buddypress()->friends->id,
			'friendship_accepted',
			'notification_friends_friendship_accepted'
		);
	}

}
