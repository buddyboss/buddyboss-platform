<?php
/**
 * BuddyBoss Messages Notification Class.
 *
 * @package BuddyBoss/Messages
 *
 * @since   BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Messages_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Messages_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->register_preferences_group(
			buddypress()->messages->id,
			esc_html__( 'Messages', 'buddyboss' ),
			esc_html__( 'Private Messaging', 'buddyboss' )
		);

		$this->register_notification_for_new_message();

		$this->start();
	}

	/**
	 * Register notification for user new message.
	 */
	public function register_notification_for_new_message() {
		$this->register_preference(
			'notification_messages_new_message',
			buddypress()->messages->id,
			esc_html__( 'A member sends you a new message', 'buddyboss' ),
			esc_html__( 'A member receives a new message', 'buddyboss' )
		);

		$this->register_email_type(
			'messages-unread',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New message from {{sender.name}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{sender.name}} sent you a new message.\n\n{{{message}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{sender.name}} sent you a new message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
			),
			array(
				'description' => __( 'Recipient has received a private message.', 'buddyboss' ),
				'unsubscribe' => array(
					'meta_key' => 'notification_messages_new_message',
					'message'  => __( 'You will no longer receive emails when someone sends you a message.', 'buddyboss' ),
				),
			),
			'notification_messages_new_message'
		);

		$this->register_notification(
			buddypress()->messages->id,
			'new_message',
			'',
			'',
			'notification_messages_new_message'
		);
	}

}
