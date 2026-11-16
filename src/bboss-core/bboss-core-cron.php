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
 * The main function responsible for returning the one true BBoss_Core_Cron Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @return BBoss_Core_Cron|null The one true BBoss_Core_Cron Instance.
 * @since BuddyBoss 1.7.0
 */
function bboss_core_cron() {
	return BBoss_Core_Cron::instance();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_cron' ) && ! function_exists( 'bp_core_cron' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_cron()}.
	 *
	 * @deprecated Use bboss_core_cron() instead.
	 */
	function bp_core_cron() {
		return bboss_core_cron();
	}
}
// @bbwt-com-end compat-layer


/**
 * Schedule a bp cron.
 *
 * @param string $hook       Action hook to execute when the event is run.
 * @param string $callback   Function to execute when the hook is run.
 * @param string $recurrence How often the event should subsequently recur. See bboss_core_cron_schedules() for accepted values.
 *
 * @return bool True if cron is scheduled, otherwise false.
 * @since BuddyBoss 1.7.0
 */
function bboss_core_schedule_cron( $hook, $callback, $recurrence = 'bb_schedule_5min' ) {
	// Add cron to schedule.
	return bboss_core_cron()->add( $hook, $callback, $recurrence );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_schedule_cron' ) && ! function_exists( 'bp_core_schedule_cron' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_schedule_cron()}.
	 *
	 * @deprecated Use bboss_core_schedule_cron() instead.
	 */
	function bp_core_schedule_cron( $hook, $callback, $recurrence = 'bb_schedule_5min' ) {
		return bboss_core_schedule_cron( $hook, $callback, $recurrence );
	}
}
// @bbwt-com-end compat-layer

