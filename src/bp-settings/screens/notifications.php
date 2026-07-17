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

	$template = 'members/single/settings/notifications';

	if ( bp_action_variables() ) {
		$action_variable = bp_action_variable( 0 );

		if ( 'subscriptions' === $action_variable ) {
			if ( ! empty( bb_get_subscriptions_types() ) ) {
				$template = 'members/single/settings/subscriptions';
			} else {
				bp_do_404();
				return;
			}
		} else {
			/**
			 * Filters the template path for a custom notifications sub-tab
			 * (e.g. a plugin-registered tab under Settings > Notifications).
			 *
			 * Return a locatable template path to render the sub-tab; return
			 * false (the default) to 404. Handlers that render their content via
			 * the `bp_template_content` action should return the generic
			 * `members/single/plugins` template.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string|false $custom_template Template path, or false to 404.
			 * @param string       $action_variable The requested sub-tab slug.
			 */
			$custom_template = apply_filters( 'bb_settings_notification_subnav_template', false, $action_variable );

			if ( empty( $custom_template ) ) {
				bp_do_404();
				return;
			}

			$template = $custom_template;
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
