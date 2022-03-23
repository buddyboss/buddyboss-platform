<?php
/**
 * BuddyBoss Mentions Notification Class.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Mentions_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Mentions_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Mentions_Notification|Controller|object
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
			'mentions',
			esc_html__( 'Mentions', 'buddyboss' ),
			esc_html__( 'Mentions Notifications', 'buddyboss' ),
			3
		);

		$this->register_notification_for_mentions();
	}

	/**
	 * Register notification for user mention.
	 */
	public function register_notification_for_mentions() {
		$this->register_notification_type(
			'notification_activity_new_mention',
			sprintf(
				/* translators: %s: users mention name. */
				__( 'A member mentions you using "@%s"', 'buddyboss' ),
				bp_activity_get_user_mentionname( get_current_user_id() )
			),
			esc_html__( 'A member is mentioned by another member', 'buddyboss' ),
			'mentions'
		);

		$this->register_email_type(
			'activity-at-message',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a status update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in a status update:\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} mentioned you in a status update:\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is mentioned by another member.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are mentioned.', 'buddyboss' ),
			),
			'notification_activity_new_mention'
		);

		$this->register_email_type(
			'groups-at-message',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a group update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in the group \"<a href=\"{{{group.url}}}\">{{group.name}}</a>\":\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is mentioned in a group.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are mentioned.', 'buddyboss' ),
			),
			'notification_activity_new_mention'
		);

		$this->register_notification(
			'activity',
			'new_at_mention',
			'notification_activity_new_mention'
		);

		$this->register_notification_filter(
			'new_at_mention',
			__( 'New mentions', 'buddyboss' ),
			5,
			'notification_activity_new_mention',
			'mentions'
		);

		$this->register_notification(
			'activity',
			'bbp_new_at_mention',
			'notification_activity_new_mention'
		);

		$this->register_notification_filter(
			'bbp_new_at_mention',
			__( 'Forum New mentions', 'buddyboss' ),
			5,
			'notification_activity_new_mention',
			'mentions'
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
	 *
	 * @return array
	 */
	public function format_notification( $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $notification_id ) {
		return array();
	}
}
