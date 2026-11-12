<?php
/**
 * BBoss_Core_Gdpr base class
 *
 * This class calls all other classes associated with GDPR member data export.
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BBoss_Core_Gdpr
 */
class BBoss_Core_Gdpr {

	/**
	 * BBoss_Core_Gdpr constructor.
	 *
	 * Runs on `bp_loaded` priority 0, and schedules the registration for priority 10 of the same
	 * action. Both halves of that matter:
	 *
	 * - It cannot register here. Every check below is a bboss_is_active() call, and the active
	 *   components are not set up until bboss_setup_components() on priority 2.
	 * - It cannot schedule itself on priority 0 either, which is what it used to do. WordPress
	 *   iterates one priority bucket with a foreach over a snapshot of that bucket, so a callback
	 *   appended to the priority that is already executing is never reached - WP_Hook::add_filter()
	 *   only resorts the list of PRIORITIES, it cannot rewind the inner loop. The registration
	 *   therefore never ran and BuddyBoss contributed no exporter or eraser at all: an admin
	 *   running Tools > Export Personal Data got a report with the WordPress groups in it and none
	 *   of the member's connections, group memberships, messages, activity, profile fields or
	 *   forum content.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		add_action( 'bp_loaded', array( $this, 'load_on_bp_dependency' ), 10 );
	}

	/**
	 * Function to load all the dependencies of GDPR classes.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function load_on_bp_dependency() {

		if ( bboss_is_active( 'xprofile' ) ) {
			BBoss_Xprofile_Export::instance();
		}
		if ( bboss_is_active( 'activity' ) ) {
			BBoss_Activity_Export::instance();
		}
		if ( bboss_is_active( 'notifications' ) ) {
			BBoss_Notification_Export::instance();
		}
		if ( bboss_is_active( 'messages' ) ) {
			BBoss_Message_Export::instance();
		}
		if ( bboss_is_active( 'groups' ) ) {
			BBoss_Group_Export::instance();
			BBoss_Group_Membership_Export::instance();
		}
		if ( bboss_is_active( 'friends' ) ) {
			BBoss_Friendship_Export::instance();
		}
		if ( bboss_is_active( 'settings' ) ) {
			BBoss_Settings_Export::instance();
		}
		if ( bboss_is_active( 'forums' ) ) {
			new BBoss_Bbp_Gdpr_Forums();
			new BBoss_Bbp_Gdpr_Replies();
			new BBoss_Bbp_Gdpr_Topics();
		}

	}
}
