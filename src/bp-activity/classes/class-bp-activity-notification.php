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
			2
		);

		$this->register_notification_for_mentions();
		$this->register_notification_for_reply();
	}

	/**
	 * Register notification for user mention.
	 */
	public function register_notification_for_mentions() {
		$this->register_notification_type(
			'notification_activity_new_mention',
			sprintf(
			/* translators: %s: users mention name. */
				__( 'A member mentions you in an update using "@%s"', 'buddyboss' ),
				bp_activity_get_user_mentionname( get_current_user_id() )
			),
			esc_html__( 'A member is mentioned in another member\'s update', 'buddyboss' ),
			'activity'
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
				'situation_label'     => __( 'Recipient was mentioned in an activity update.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddyboss' ),
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
				'situation_label'     => __( 'Recipient was mentioned in a group activity update.', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddyboss' ),
			),
			'notification_activity_new_mention'
		);

		$this->register_notification(
			'activity',
			'new_at_mention',
			'notification_activity_new_mention'
		);
	}

	/**
	 * Register notification for activity reply.
	 */
	public function register_notification_for_reply() {
		$this->register_notification_type(
			'notification_activity_new_reply',
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
			'notification_activity_new_reply'
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
			'notification_activity_new_reply'
		);

		$this->register_notification(
			'activity',
			'update_reply',
			'notification_activity_new_reply'
		);

		$this->register_notification(
			'activity',
			'comment_reply',
			'notification_activity_new_reply'
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
