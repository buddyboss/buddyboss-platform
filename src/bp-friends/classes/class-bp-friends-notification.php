<?php
/**
 * BuddyBoss Connections Notification Class.
 *
 * @package BuddyBoss\Friends
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Friends_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Friends_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Friends_Notification|Controller|object
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
			'friends',
			esc_html__( 'Connections', 'buddyboss' ),
			esc_html__( 'Connections Notifications', 'buddyboss' ),
			22
		);

		$this->register_notification_for_friendship_request();
		$this->register_notification_for_friendship_accept();
	}

	/**
	 * Register notification for user friendship request.
	 */
	public function register_notification_for_friendship_request() {
		$this->register_notification_type(
			'bb_connections_new_request',
			esc_html__( 'You receive a new connection request', 'buddyboss' ),
			esc_html__( 'A member receives a new connection request', 'buddyboss' ),
			'friends'
		);

		$this->register_email_type(
			'friends-request',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] New request to connect from {{initiator.name}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{initiator.url}}}\">{{initiator.name}}</a> wants to add you as a connection.\n\n{{{member.card}}}\n\n<a href=\"{{{friend-requests.url}}}\">Click here</a> to manage this and all other pending requests.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{initiator.name}} wants to add you as a connection.\n\nTo accept this request and manage all of your pending requests, visit: {{{friend-requests.url}}}\n\nTo view {{initiator.name}}'s profile, visit: {{{initiator.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member recieves a new connection request', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you an invitation to connect.', 'buddyboss' ),
			),
			'bb_connections_new_request'
		);

		$this->register_notification(
			'friends',
			'bb_connections_new_request',
			'bb_connections_new_request',
			true,
			__( 'Pending connection requests', 'buddyboss' ),
			45
		);
	}

	/**
	 * Register notification for friendship accept.
	 */
	public function register_notification_for_friendship_accept() {
		$this->register_notification_type(
			'bb_connections_request_accepted',
			esc_html__( 'Your connection request is accepted', 'buddyboss' ),
			esc_html__( 'A member\'s connection request is accepted', 'buddyboss' ),
			'friends'
		);

		$this->register_email_type(
			'friends-request-accepted',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{friend.name}} accepted your request to connect', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{friendship.url}}}\">{{friend.name}}</a> accepted your request to connect.\n\n{{{member.card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{friend.name}} accepted your friend request.\n\nTo learn more about them, visit their profile: {{{friendship.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member\'s connection request is accepted', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone accepts your invitation to connect.', 'buddyboss' ),
			),
			'bb_connections_request_accepted'
		);

		$this->register_notification(
			'friends',
			'bb_connections_request_accepted',
			'bb_connections_request_accepted',
			true,
			__( 'Accepted connection requests', 'buddyboss' ),
			35
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
