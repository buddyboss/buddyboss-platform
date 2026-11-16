<?php
/**
 * Settings: User's "Settings" screen handler
 *
 * @package BuddyBoss\Settings\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Show the general settings template.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_settings_screen_general() {

	if ( bboss_action_variables() ) {
		bboss_do_404();
		return;
	}

	/**
	 * Filters the template file path to use for the general settings screen.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Directory path to look in for the template file.
	 */
	bboss_core_load_template( apply_filters( 'bp_settings_screen_general_settings', 'members/single/settings/general' ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_settings_screen_general' ) && ! function_exists( 'bp_settings_screen_general' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_settings_screen_general()}.
	 *
	 * @deprecated Use bboss_settings_screen_general() instead.
	 */
	function bp_settings_screen_general() {
		return bboss_settings_screen_general();
	}
}
// @bbwt-com-end compat-layer


/**
 * Removes 'Email' sub nav, if no component has registered options there.
 *
 * @since BuddyPress 2.2.0
 */
function bboss_settings_remove_email_subnav() {
	if ( ! has_action( 'bp_notification_settings' ) ) {
		bboss_core_remove_subnav_item( BBOSS_SETTINGS_SLUG, 'notifications' );
	}
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_settings_remove_email_subnav' ) && ! function_exists( 'bp_settings_remove_email_subnav' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_settings_remove_email_subnav()}.
	 *
	 * @deprecated Use bboss_settings_remove_email_subnav() instead.
	 */
	function bp_settings_remove_email_subnav() {
		return bboss_settings_remove_email_subnav();
	}
}
// @bbwt-com-end compat-layer

add_action( 'bp_actions', 'bp_settings_remove_email_subnav' );
