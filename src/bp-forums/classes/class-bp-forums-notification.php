<?php
/**
 * BuddyBoss Forums Notification Class.
 *
 * @package BuddyBoss\Forums
 *
 * @since   BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Forums_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Forums_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Forums_Notification|Controller|object
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
			buddypress()->forums->id,
			esc_html__( 'Forums', 'buddyboss' ),
			esc_html__( 'Forums Notifications', 'buddyboss' ),
			40
		);

		// Replies to a discussion you are subscribed.
		$this->register_notification_for_forums_following_reply();

		// Creates discussion in a forum you are subscribed.
		$this->register_notification_for_forums_following_topic();
	}



	/**
	 * Register notification for replies to a discussion you are subscribed.
	 */
	public function register_notification_for_forums_following_reply() {
		$this->register_preference(
			'notification_forums_following_reply',
			esc_html__( 'A member replies to a discussion you are subscribed', 'buddyboss' ),
			'',
			buddypress()->forums->id
		);

		$this->register_email_type(
			'bbp-new-forum-reply',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} replied to one of your forum discussions', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{poster.name}} replied to the discussion <a href=\"{{discussion.url}}\">{{discussion.title}}</a> in the forum <a href=\"{{forum.url}}\">{{forum.title}}</a>:\n\n{{{reply.content}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} replied to the discussion {{discussion.title}} in the forum {{forum.title}}:\n\n{{{reply.content}}}\n\nPost Link: {{reply.url}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'A member replies to a discussion you are subscribed to.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_bbp_new_forum_reply',
					'message'  => __( 'You will no longer receive emails when a member will reply to one of your forum discussions.', 'buddyboss' ),
				),
			),
			'notification_forums_following_reply'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'bbp_new_reply',
			'notification_forums_following_reply'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'bbp_new_at_mention',
			'notification_forums_following_reply'
		);
	}

	/**
	 * Register notification for creates discussion in a forum you are subscribed.
	 */
	public function register_notification_for_forums_following_topic() {
		$this->register_preference(
			'notification_forums_following_topic',
			esc_html__( 'A member creates discussion in a forum you are subscribed', 'buddyboss' ),
			'',
			buddypress()->forums->id
		);

		$this->register_email_type(
			'bbp-new-forum-topic',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New discussion: {{discussion.title}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{poster.name}} started a new discussion <a href=\"{{discussion.url}}\">{{discussion.title}}</a> in the forum <a href=\"{{forum.url}}\">{{forum.title}}</a>:\n\n{{{discussion.content}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} started a new discussion {{discussion.title}} in the forum {{forum.title}}:\n\n{{{discussion.content}}}\n\nDiscussion Link: {{discussion.url}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'A member has created a new forum discussion.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_bbp_new_forum_topic',
					'message'  => __( 'You will no longer receive emails when a member will create a new forum discussion.', 'buddyboss' ),
				),
			),
			'notification_forums_following_topic'
		);

		$this->register_notification(
			buddypress()->groups->id,
			'bbp_new_at_mention',
			'notification_forums_following_topic'
		);
	}
}
