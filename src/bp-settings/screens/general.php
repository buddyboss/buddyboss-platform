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
add_action( 'bp_actions', 'bp_settings_remove_email_subnav' );
