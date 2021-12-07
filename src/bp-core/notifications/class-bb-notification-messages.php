<?php
/**
 * BuddyBoss Activity Mention Notification Class.
 *
 * @package BuddyBoss
 *
 * @since   BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB_Notification_Activity_Mention class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Notification_Messages extends BB_Notification_Abstract {

	/**
	 * Constructor method.
	 *
	 * @param string $email_key         Email Key.
	 * @param string $email_label       Email label.
	 * @param string $email_admin_label Email admin label.
	 * @param int    $email_position    Email position.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct( $email_key, $email_label, $email_admin_label, $email_position ) {
		$this->component      = buddypress()->messages->id;
		$this->component_name = __( 'Messages', 'buddyboss' );
		parent::__construct( $email_key, $email_label, $email_admin_label, $email_position );
	}

	/**
	 * Filters registered activity notification email schema.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @returns array $schema Email schema array.
	 */
	public function add_email_schema() {
		return array(
			'messages-unread' => array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] New message from {{sender.name}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "{{sender.name}} sent you a new message.\n\n{{{message}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{sender.name}} sent you a new message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
			),
		);
	}
}

add_action(
	'bp_init',
	function () {
		if ( bp_is_active( 'messages' ) ) {
			new BB_Notification_Messages(
				'notification_messages_new_message',
				esc_html__( 'A member sends you a new message', 'buddyboss' ),
				esc_html__( 'A member receive a new message', 'buddyboss' ),
				1,
			);
		}
	}
);

