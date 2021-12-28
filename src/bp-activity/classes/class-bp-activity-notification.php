<?php
/**
 * BuddyBoss Activity Notification Class.
 *
 * @package BuddyBoss/Activity
 *
 * @since   BuddyBoss [BBVERSION]
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
	private static $_instance = null;

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->register_preferences_group(
			buddypress()->activity->id,
			esc_html__( 'Activity Feed', 'buddyboss' ),
			esc_html__( 'Activity Feed Notifications', 'buddyboss' )
		);

		$this->register_notification_for_mentions();
		$this->register_notification_for_reply();

		$this->start();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Register notification for user mention.
	 */
	public function register_notification_for_mentions() {
		$this->register_preference(
			buddypress()->activity->id,
			'notification_activity_new_mention',
			sprintf(
			/* translators: %s: users mention name. */
				__( 'A member mentions you in an update using "@%s"', 'buddyboss' ),
				bp_activity_get_user_mentionname( get_current_user_id() )
			),
			esc_html__( 'A member is mentioned in another member\'s update', 'buddyboss' )
		);

		$this->register_email_type(
			'activity-at-message',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a status update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in a status update:\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} mentioned you in a status update:\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'Recipient was mentioned in an activity update.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_activity_new_mention',
					'message'  => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddyboss' ),
				),
			),
			'notification_activity_new_mention'
		);

		$this->register_email_type(
			'groups-at-message',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a group update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in the group \"<a href=\"{{{group.url}}}\">{{group.name}}</a>\":\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'Recipient was mentioned in a group activity update.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_activity_new_mention',
					'message'  => __( 'You will no longer receive emails when someone mentions you in an update.', 'buddyboss' ),
				),
			),
			'notification_activity_new_mention'
		);

		$this->register_notification(
			buddypress()->activity->id,
			'new_at_mention',
			'notification_activity_new_mention'
		);
	}

	/**
	 * Register notification for activity reply.
	 */
	public function register_notification_for_reply() {
		$this->register_preference(
			buddypress()->activity->id,
			'notification_activity_new_reply',
			esc_html__( 'A member replies to an update or comment you’ve posted', 'buddyboss' ),
			esc_html__( 'A member receives a reply to an update or comment they’ve posted', 'buddyboss' ),
		);

		$this->register_email_type(
			'activity-comment',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your updates', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your updates:\n\n{{{activity_reply}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} replied to one of your updates:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'A member has replied to an activity update that the recipient posted.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_activity_new_reply',
					'message'  => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),
				),
			),
			'notification_activity_new_reply'
		);

		$this->register_email_type(
			'activity-comment-author',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your comments', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your comments:\n\n{{{activity_reply}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} replied to one of your comments:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'A member has replied to a comment on an activity update that the recipient posted.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_activity_new_reply',
					'message'  => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),
				),
			),
			'notification_activity_new_reply'
		);

		$this->register_notification(
			buddypress()->activity->id,
			'update_reply',
			'notification_activity_new_reply'
		);

		$this->register_notification(
			buddypress()->activity->id,
			'comment_reply',
			'notification_activity_new_reply'
		);
	}

}
