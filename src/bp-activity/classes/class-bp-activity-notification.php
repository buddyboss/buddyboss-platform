<?php
/**
 * BuddyBoss Activity Notification Class.
 *
 * @package BuddyBoss\Activity
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Activity_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Activity_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Activity_Notification|Controller|object
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
			'activity',
			esc_html__( 'Activity Feed', 'buddyboss' ),
			esc_html__( 'Activity Feed Notifications', 'buddyboss' ),
			6
		);

		$this->register_notification_for_reply();
	}

	/**
	 * Register notification for activity reply.
	 */
	public function register_notification_for_reply() {
		$this->register_notification_type(
			'bb_activity_comment',
			esc_html__( 'A member replies to an update or comment you’ve posted', 'buddyboss' ),
			esc_html__( 'A member receives a reply to an update or comment they’ve posted', 'buddyboss' ),
			'activity'
		);

		$this->register_email_type(
			'activity-comment',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} replied to one of your updates', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your updates:\n\n{{{activity_reply}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} replied to one of your updates:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member has replied to an activity update that the recipient posted.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),
			),
			'bb_activity_comment'
		);

		$this->register_email_type(
			'activity-comment-author',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} replied to one of your comments', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your comments:\n\n{{{activity_reply}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} replied to one of your comments:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member has replied to a comment on an activity update that the recipient posted.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),

			),
			'bb_activity_comment'
		);

		$this->register_notification(
			'activity',
			'update_reply',
			'bb_activity_comment',
			true,
			__( 'New update replies', 'buddyboss' ),
			15
		);

		$this->register_notification(
			'activity',
			'comment_reply',
			'bb_activity_comment',
			true,
			__( 'New update comment replies', 'buddyboss' ),
			25
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
