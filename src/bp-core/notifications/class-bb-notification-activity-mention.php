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
class BB_Notification_Activity_Mention extends BB_Notification_Abstract {

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
		$this->component      = buddypress()->activity->id;
		$this->component_name = __( 'Activity Feed', 'buddyboss' );
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
			'activity-at-message' => array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a status update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in a status update:\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} mentioned you in a status update:\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
			),
			'groups-at-message'   => array(
				/* translators: do not remove {} brackets or translate its contents. */
				'post_title'   => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a group update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in the group \"<a href=\"{{{group.url}}}\">{{group.name}}</a>\":\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'post_excerpt' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
			),
		);
	}
}

add_action(
	'bp_init',
	function () {
		if ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) {
			new BB_Notification_Activity_Mention(
				'notification_activity_new_mention',
				sprintf(
				/* translators: %s: users mention name. */
					__( 'A member mentions you in an update using "@%s"', 'buddyboss' ),
					bp_activity_get_user_mentionname( bp_loggedin_user_id() )
				),
				__( 'A member is mentioned in another member\'s update', 'buddyboss' ),
				1,
			);
		}
	}
);

