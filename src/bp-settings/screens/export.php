<?php
/**
 * Settings: User's "Settings > Export Data" screen handler
 *
 * @package BuddyBoss\Settings\Screens
 * @since BuddyBoss 1.0.0
 */

/**
 * Show the notifications settings template.
 *
 * @since BuddyPress 1.5.0
 */
function bp_settings_screen_export_data() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Filters the template file path to use for the notification settings screen.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Directory path to look in for the template file.
	 */
	bp_core_load_template( apply_filters( 'bp_settings_screen_export_data_settings', 'members/single/settings/export-data' ) );
}
