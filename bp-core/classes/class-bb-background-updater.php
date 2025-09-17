<?php
/**
 * BuddyBoss Background Updater
 *
 * @package BuddyBoss\BackgroundUpdater
 *
 * @since BuddyBoss 2.4.20
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Background_Process' ) ) {
	include_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-background-process.php';
}

if ( ! class_exists( 'BB_Background_Updater' ) ) {

	/**
	 * BB_Background_Updater class.
	 */
	class BB_Background_Updater extends BB_Background_Process {

		/**
		 * Processes the task.
		 *
		 * @since 2.4.20
		 *
		 * @param array $callback Update callback function.
		 *
		 * @return bool
		 */
		protected function task( $callback ) {
			$result = false;

			$args = array();
			if ( ! is_callable( $callback ) ) {
				$args     = ( ! empty( $callback['args'] ) ) ? $callback['args'] : array();
				$callback = ( ! empty( $callback['callback'] ) ) ? $callback['callback'] : '';
			}

			if ( is_callable( $callback ) ) {
				// phpcs:ignore
				bb_error_log( sprintf( 'Running %s callback', json_encode( $callback ) ) );

				if ( empty( $args ) ) {
					$result = (bool) call_user_func( $callback, $this );
				} else {
					$result = (bool) call_user_func_array( $callback, $args );
				}

				if ( $result ) {
					// phpcs:ignore
					bb_error_log( sprintf( '%s callback needs to run again', json_encode( $callback ) ) );
				} else {
					// phpcs:ignore
					bb_error_log( sprintf( 'Finished running %s callback', json_encode( $callback ) ) );
				}
			} else {
				// phpcs:ignore
				error_log( sprintf( 'Could not find %s callback', json_encode( $callback ) ) );
			}

			return $result ? $callback : false;
		}

	}
}
