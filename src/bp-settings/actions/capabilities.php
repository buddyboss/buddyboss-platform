<?php
/**
 * Settings: Capabilities action handler
 *
 * @package BuddyBoss\Settings\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the setting of user capabilities, spamming, hamming, role, etc...
 *
 * @since BuddyPress 1.6.0
 */
function bboss_settings_action_capabilities() {
	if ( ! bboss_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['capabilities-submit'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bboss_is_settings_component() || ! bboss_is_current_action( 'capabilities' ) ) {
		return false;
	}

	// 404 if there are any additional action variables attached
	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	// Only super admins can currently spam users (but they can't spam
	// themselves).
	if ( ! is_super_admin() || bboss_is_my_profile() ) {
		return;
	}

	// Nonce check.
	check_admin_referer( 'capabilities' );

	/**
	 * Fires before the capabilities settings have been saved.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_settings_capabilities_before_save' );

	/* Spam **************************************************************/

	$is_spammer = ! empty( $_POST['user-spammer'] ) ? true : false;

	if ( bboss_is_user_spammer( bboss_displayed_user_id() ) != $is_spammer ) {
		$status = ( true == $is_spammer ) ? 'spam' : 'ham';
		bboss_core_process_spammer_status( bboss_displayed_user_id(), $status );

		/**
		 * Fires after processing a user as a spammer.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param int    $value  ID of the currently displayed user.
		 * @param string $status Determined status of "spam" or "ham" for the displayed user.
		 */
		do_action( 'bp_core_action_set_spammer_status', bboss_displayed_user_id(), $status );
	}

	/* Other *************************************************************/

	/**
	 * Fires after the capabilities settings have been saved and before redirect.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_settings_capabilities_after_save' );

	// Redirect to the root domain.
	bboss_core_redirect( bboss_displayed_user_domain() . bboss_get_settings_slug() . '/capabilities/' );
}
add_action( 'bp_actions', 'bp_settings_action_capabilities' );
