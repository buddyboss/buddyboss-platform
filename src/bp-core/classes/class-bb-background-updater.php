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

	}
}
