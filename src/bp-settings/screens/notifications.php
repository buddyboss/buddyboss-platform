<?php
/**
 * Settings: User's "Settings > Email" screen handler
 *
 * @package BuddyBoss\Settings\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Show the notifications settings template.
 *
 * @since BuddyPress 1.5.0
 */
function bp_settings_screen_notification() {

	if ( bp_action_variables() && 'subscriptions' !== bp_action_variable( 0 ) ) {
		bp_do_404();
		return;
	}

	$template = 'members/single/settings/notifications';
	if ( bp_action_variables() && 'subscriptions' === bp_action_variable( 0 ) ) {
		if ( ! empty( bb_get_subscriptions_types() ) ) {
			$template = 'members/single/settings/subscriptions';
		} else {
			bp_do_404();
			return;
		}
	}

	/**
	 * Filters the template file path to use for the notification settings screen.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param string $value Directory path to look in for the template file.
	 */
	bp_core_load_template( apply_filters( 'bp_settings_screen_notification_settings', $template ) );
}
