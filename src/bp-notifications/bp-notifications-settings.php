<?php
/**
 * Notifications Settings
 *
 * @package BuddyBoss\Notifications
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Media settings sections.
 *
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bb_notification_get_settings_sections() {

	$settings = array(

	);

	return (array) apply_filters( 'bb_notification_get_settings_sections', $settings );
}
