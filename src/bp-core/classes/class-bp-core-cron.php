<?php
/**
 * BuddyBoss Core Cron.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the Core Cron class.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Core_Cron {

	/**
	 * Crons.
	 *
	 * @var array Cron array.
	 */
	public $crons = array();

	/**
	 * Main Core Cron Instance.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @static object $instance
	 * @see bp_core_cron()
	 *
	 * @return BP_Core_Cron|null The one true BP_Core_Cron.
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Only run these methods if they haven't been run previously.
		if ( null === $instance ) {
			$instance = new BP_Core_Cron();
			$instance->setup_actions();
		}

		// Always return the instance.
		return $instance;

		// The last metroid is in captivity. The galaxy is at peace.
		// The powers of the metroid might be harnessed for the good of civilization.
	}

	/**
	 * Hooks.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function setup_actions() {
		add_action( 'bp_init', array( $this, 'schedule' ) );
	}

	/**
	 * Add a bp cron.
	 *
	 * @param string $hook       Action hook to execute when the event is run.
	 * @param string $callback   Function to execute when the hook is run.
	 * @param string $recurrence How often the event should subsequently recur. See bp_core_cron_schedules() for accepted values.
	 *
	 * @return bool True if cron is scheduled, otherwise false.
	 * @since BuddyBoss 1.7.0
	 */
	public function add( $hook, $callback, $recurrence ) {
		// Check if recurrence is good to go for bb or not.
		if ( ! in_array( $recurrence, array_keys( bp_core_cron_schedules() ), true ) ) {
			return false;
		}

		$this->crons[] = array(
			'hook'       => 'bb_' . $hook . '_hook',
			'recurrence' => $recurrence,
		);

		add_action( 'bb_' . $hook . '_hook', $callback );

		return true;
	}

	/**
	 * Schedule a cron.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	public function schedule() {
		foreach ( $this->crons as $cron ) {
			// Schedule if not scheduled already.
			if ( ! wp_next_scheduled( $cron['hook'] ) && apply_filters( 'bp_core_cron_schedule_' . $cron['hook'], true ) ) {
				wp_schedule_event( time(), $cron['recurrence'], $cron['hook'] );
			}
		}
	}
}
