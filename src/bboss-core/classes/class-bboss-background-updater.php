<?php
/**
 * Background Updater
 *
 * @since BuddyBoss 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BBoss_Background_Updater' ) ) {

	if ( ! class_exists( 'BBoss_Background_Process', false ) ) {
		include_once bboss()->plugin_dir . 'bboss-core/classes/class-bboss-background-process.php';
	}

	/**
	 * BBoss_Background_Updater Class.
	 */
	class BBoss_Background_Updater extends BBoss_Background_Process {

		/**
		 * Initiate new background process.
		 */
		public function __construct() {
			// Uses unique prefix per blog so each blog has separate queue.
			$this->prefix = 'wp_' . get_current_blog_id();
			$this->action = 'bp_updater';

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
				bboss_error_log( sprintf( 'Unable to dispatch BuddyPress updater: %s', $dispatched->get_error_message() ) );
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

			if ( $this->bb_skip_incomplete_callback( $callback ) ) {
				return false;
			}

			$args = array();
			if ( ! is_callable( $callback ) ) {
				$args     = ( ! empty( $callback['args'] ) ) ? $callback['args'] : array();
				$callback = ( ! empty( $callback['callback'] ) ) ? $callback['callback'] : '';
			}

			if ( $this->bb_skip_incomplete_callback( $callback ) ) {
				return false;
			}

			if ( is_callable( $callback ) ) {
				bboss_error_log( sprintf( 'Running %s callback', json_encode( $callback ) ) );

				if ( empty( $args ) ) {
					$result = (bool) call_user_func( $callback, $this );
				} else {
					$result = (bool) call_user_func_array( $callback, $args );
				}

				if ( $result ) {
					bboss_error_log( sprintf( '%s callback needs to run again', json_encode( $callback ) ) );
				} else {
					bboss_error_log( sprintf( 'Finished running %s callback', json_encode( $callback ) ) );
				}
			} else {
				error_log( sprintf( 'Could not find %s callback', json_encode( $callback ) ) );
			}

			return $result ? $callback : false;
		}

		/**
		 * Skip a queued callback whose object class is not loaded.
		 *
		 * A job can store array( $object, 'method' ). When that object's class no
		 * longer exists (renamed or removed between versions), unserialize() returns
		 * a __PHP_Incomplete_Class, and is_callable() on it is a fatal error on PHP 7.4
		 * and throws an Error on PHP 8. The job and the process lock then stay in
		 * place and the queue never moves past that row. Such a job is logged and
		 * removed instead, like a callback that cannot be found.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $callback Queued item, or the callback taken from it.
		 *
		 * @return bool True when the callback holds an object of a class that is not loaded.
		 */
		protected function bb_skip_incomplete_callback( $callback ) {
			if ( ! is_array( $callback ) || ! isset( $callback[0] ) || ! $callback[0] instanceof __PHP_Incomplete_Class ) {
				return false;
			}

			$class  = preg_match( '/^O:\\d+:"([^"]+)"/', serialize( $callback[0] ), $match ) ? $match[1] : '__PHP_Incomplete_Class'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- reads the stored class name only.
			$method = isset( $callback[1] ) && is_string( $callback[1] ) ? $callback[1] : '';

			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( 'Skipped %1$s::%2$s callback: class %1$s is not loaded', $class, $method ) );

			return true;
		}

		/**
		 * Complete
		 *
		 * Override if applicable, but ensure that the below actions are
		 * performed, or, call parent::complete().
		 */
		protected function complete() {
			bboss_error_log( 'Data update complete' );
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
