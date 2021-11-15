<?php
/**
 * Email Background Updater
 *
 * @since BuddyBoss 1.8.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Email_Background_Updater' ) ) {

	if ( ! class_exists( 'BP_Background_Process', false ) ) {
		include_once buddypress()->plugin_dir . 'bp-core/classes/class-bp-background-process.php';
	}

	/**
	 * BP_Email_Background_Updater Class.
	 */
	class BP_Email_Background_Updater extends BP_Background_Process {

		/**
		 * Initiate new background process.
		 */
		public function __construct() {
			// Uses unique prefix per blog so each blog has separate queue.
			$this->prefix = 'wp_' . get_current_blog_id();
			$this->action = 'bb_email_updater';

			parent::__construct();
		}

		/**
		 * Dispatch updater.
		 *
		 * Updater will still run via cron job if this fails for any reason.
		 */
		public function dispatch() {
			$dispatched = parent::dispatch();

			if ( is_wp_error( $dispatched ) ) {
				error_log( sprintf( 'Unable to dispatch BuddyBoss Email updater: %s', $dispatched->get_error_message() ) );
			}
		}

		/**
		 * Handle cron healthcheck
		 *
		 * Restart the background process if not already running
		 * and data exists in the queue.
		 */
		public function handle_cron_healthcheck() {
			if ( $this->is_process_running() ) {
				// Background process already running.
				return;
			}

			if ( $this->is_queue_empty() ) {
				// No data to process.
				$this->clear_scheduled_event();

				return;
			}

			$this->handle();
		}

		/**
		 * Schedule fallback event.
		 */
		public function schedule_event() {
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}

		/**
		 * Is the updater running?
		 *
		 * @return boolean
		 */
		public function is_updating() {
			return false === $this->is_queue_empty();
		}

		/**
		 * Task
		 *
		 * Override this method to perform any actions required on each
		 * queue item. Return the modified item for further processing
		 * in the next pass through. Or, return false to remove the
		 * item from the queue.
		 *
		 * @param string $callback Update callback function.
		 *
		 * @return string|bool
		 */
		protected function task( $callback ) {
			$result = false;

			$args = array();
			if ( ! is_callable( $callback ) ) {
				$args     = ( ! empty( $callback['args'] ) ) ? $callback['args'] : array();
				$callback = ( ! empty( $callback['callback'] ) ) ? $callback['callback'] : '';
			}

			if ( is_callable( $callback ) ) {
				error_log( sprintf( 'Running %s callback', json_encode( $callback ) ) );

				if ( empty( $args ) ) {
					$result = (bool) call_user_func( $callback, $this );
				} else {
					$result = (bool) call_user_func_array( $callback, $args );
				}

				if ( $result ) {
					error_log( sprintf( '%s callback needs to run again', json_encode( $callback ) ) );
				} else {
					error_log( sprintf( 'Finished running %s callback', json_encode( $callback ) ) );
				}
			} else {
				error_log( sprintf( 'Could not find %s callback', json_encode( $callback ) ) );
			}

			return $result ? $callback : false;
		}

		/**
		 * Complete
		 *
		 * Override if applicable, but ensure that the below actions are
		 * performed, or, call parent::complete().
		 */
		protected function complete() {
			error_log( 'Data update complete' );
			parent::complete();
		}

		/**
		 * See if the batch limit has been exceeded.
		 *
		 * @return bool
		 */
		public function is_memory_exceeded() {
			return $this->memory_exceeded();
		}
	}
}
