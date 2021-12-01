<?php
/**
 * Notifications Settings
 *
 * @package BuddyBoss\Notifications
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Notification settings sections.
 *
 * @return array
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_get_settings_sections() {

	$settings = array(

	);

	return (array) apply_filters( 'bb_notification_get_settings_sections', $settings );
}
