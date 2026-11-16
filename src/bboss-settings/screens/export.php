<?php
/**
 * Settings: User's "Settings > Export Data" screen handler
 *
 * @package BuddyBoss\Settings\Screens
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Show the notifications settings template.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_settings_screen_export_data() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	/**
	 * Filters the template file path to use for the notification settings screen.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Directory path to look in for the template file.
	 */
	bboss_core_load_template( apply_filters( 'bp_settings_screen_export_data_settings', 'members/single/settings/export-data' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_settings_screen_export_data' ) && ! function_exists( 'bp_settings_screen_export_data' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_settings_screen_export_data()}.
	 *
	 * @deprecated Use bboss_settings_screen_export_data() instead.
	 */
	function bp_settings_screen_export_data() {
		return bboss_settings_screen_export_data();
	}
}
// @bbwt-com-end compat-layer

