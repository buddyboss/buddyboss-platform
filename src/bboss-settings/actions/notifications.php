<?php
/**
 * Settings: Email notifications action handler
 *
 * @package BuddyBoss\Settings\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the changing and saving of user notification settings.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_settings_action_notifications() {
	if ( ! bboss_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['submit'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bboss_is_settings_component() || ! bboss_is_current_action( 'notifications' ) ) {
		return false;
	}

	// 404 if there are any additional action variables attached
	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	check_admin_referer( 'bp_settings_notifications' );

	bboss_settings_update_notification_settings( bboss_displayed_user_id(), (array) $_POST['notifications'] );

	// Switch feedback for super admins.
	if ( bboss_is_my_profile() ) {
		bboss_core_add_message( __( 'Your notification settings have been saved.', 'buddyboss' ), 'success' );
	} else {
		bboss_core_add_message( __( "This user's notification settings have been saved.", 'buddyboss' ), 'success' );
	}

	/**
	 * Fires after the notification settings have been saved, and before redirect.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_core_notification_settings_after_save' );

	bboss_core_redirect( bboss_displayed_user_domain() . bboss_get_settings_slug() . '/notifications/' );
}
add_action( 'bp_actions', 'bboss_settings_action_notifications' );
