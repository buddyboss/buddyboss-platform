<?php
/**
 * Settings: Account deletion action handler
 *
 * @package BuddyBoss\Settings\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles the deleting of a user.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_settings_action_delete_account() {
	if ( ! bboss_is_post_request() ) {
		return;
	}

	// Bail if no submit action.
	if ( ! isset( $_POST['delete-account-understand'] ) ) {
		return;
	}

	// Bail if not in settings.
	if ( ! bboss_is_settings_component() || ! bboss_is_current_action( 'delete-account' ) ) {
		return false;
	}

	// 404 if there are any additional action variables attached
	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	// Bail if account deletion is disabled.
	if ( bboss_disable_account_deletion() && ! bboss_current_user_can( 'delete_users' ) ) {
		return false;
	}

	// Nonce check.
	check_admin_referer( 'delete-account' );

	// Get username now because it might be gone soon!
	$username = bboss_get_displayed_user_fullname();

	// Delete the users account.
	if ( bboss_core_delete_account( bboss_displayed_user_id() ) ) {

		// Add feedback after deleting a user.
		/* translators: %s: member display name. */
		bboss_core_add_message( sprintf( __( '%s was successfully deleted.', 'buddyboss-platform' ), $username ), 'success' );

		// Redirect to the root domain.
		bboss_core_redirect( bboss_get_root_domain() );
	}
}
add_action( 'bp_actions', 'bboss_settings_action_delete_account' );
