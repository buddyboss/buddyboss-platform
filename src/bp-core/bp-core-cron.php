<?php
/**
 * BuddyBoss Core Cron Loader.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main function responsible for returning the one true BP_Core_Cron Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return BP_Core_Cron|null The one true BP_Core_Cron Instance.
 * @since BuddyBoss 1.7.0
 */
function bp_core_cron() {
	return BP_Core_Cron::instance();
}

/**
 * Schedule a bp cron.
 *
 * @param string $hook       Action hook to execute when the event is run.
 * @param string $callback   Function to execute when the hook is run.
 * @param string $recurrence How often the event should subsequently recur. See bp_core_cron_schedules() for accepted values.
 *
 * @return bool True if cron is scheduled, otherwise false.
 * @since BuddyBoss 1.7.0
 */
function bp_core_schedule_cron( $hook, $callback, $recurrence = 'bb_schedule_5min' ) {
	// Add cron to schedule.
	return bp_core_cron()->add( $hook, $callback, $recurrence );
}
