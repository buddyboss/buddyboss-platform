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
class BP_Groups_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->register_preferences_group(
			buddypress()->groups->id,
			esc_html__( 'Social Groups', 'buddyboss' ),
			esc_html__( 'Social Groups Notifications', 'buddyboss' )
		);

		$this->register_preference(
			'notification_groups_invite',
			buddypress()->groups->id,
			esc_html__( 'A member invites you to join a group', 'buddybobss' ),
			esc_html__( 'A member is invited to join a group', 'buddybobss' ),
		);

		$this->register_preference(
			'notification_groups_group_updated',
			buddypress()->groups->id,
			esc_html__( 'Group information is updated', 'buddybobss' ),
			'',
		);

		$this->register_preference(
			'notification_groups_admin_promotion',
			buddypress()->groups->id,
			esc_html__( 'You are promoted to a group organizer or moderator', 'buddybobss' ),
			esc_html__( 'A member is promoted to a group organizer or moderator', 'buddybobss' ),
		);

		$this->start();
	}

}
