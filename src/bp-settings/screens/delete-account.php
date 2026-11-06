<?php
/**
 * Settings: User's "Settings > Delete Account" screen handler
 *
 * @package BuddyBoss\Settings\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Show the delete-account settings template.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_settings_screen_delete_account() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	/**
	 * Filters the template file path to use for the delete-account settings screen.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Directory path to look in for the template file.
	 */
	bboss_core_load_template( apply_filters( 'bp_settings_screen_delete_account', 'members/single/settings/delete-account' ) );
}
