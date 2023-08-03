<?php
/**
 * BuddyBoss Background Updater
 *
 * @package BuddyBoss\BackgroundUpdater
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Background_Updater' ) ) {

	/**
	 * BB_Background_Updater class.
	 */
	class BB_Background_Updater {

		/**
		 * Prefix.
		 *
		 * (default value: 'wp')
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 * @access protected
		 */
		protected $prefix = 'bb';

		/**
		 * Action.
		 *
		 * (default value: 'async_request')
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 * @access protected
		 */
		protected $action = 'async_request';

		/**
		 * Identifier.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var mixed
		 * @access protected
		 */
		protected $identifier;

		/**
		 * Data.
		 *
		 * (default value: array())
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var array
		 * @access protected
		 */
		protected $data = array();

		/**
		 * Start time of current process.
		 *
		 * (default value: 0)
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var int
		 * @access protected
		 */
		protected $start_time = 0;

		/**
		 * Cron_hook_identifier.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 * @access protected
		 */
		protected $cron_hook_identifier;

		/**
		 * Cron_interval_identifier.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 * @access protected
		 */
		protected $cron_interval_identifier;

		/**
		 * The status set when process is cancelling.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var int
		 */
		const STATUS_CANCELLED = 1;

		/**
		 * The status set when process is paused or pausing.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var int
		 */
		const STATUS_PAUSED = 2;

		/**
		 * Background job queue table name.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		public static $table_name = '';

		/**
		 * Initiate new async request.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			$this->identifier = $this->prefix . '_' . $this->action;

			$this->cron_hook_identifier     = $this->identifier . '_cron';
			$this->cron_interval_identifier = $this->identifier . '_cron_interval';

			self::create_table();

			add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
			add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) ); // phpcs:ignore

			add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
			add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );
		}

		/**
		 * Created custom table for background job queue.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void
		 */
		public static function create_table() {
			$sql             = array();
			$wpdb            = $GLOBALS['wpdb'];
			$charset_collate = $wpdb->get_charset_collate();
			$bp_prefix       = bp_core_get_table_prefix();

			$table_name = $bp_prefix . 'bb_background_job_queue';

			self::$table_name = $table_name;

			// Ensure that dbDelta() is defined.
			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			$table_exists = (bool) $wpdb->get_results( "DESCRIBE {$table_name};" );

			// Table already exists, so maybe upgrade instead?
			if ( true !== $table_exists ) {

				$sql[] = "CREATE TABLE {$table_name} (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					type varchar(255) NOT NULL,
					`group` varchar(255) DEFAULT NULL,
					data_id varchar(20) DEFAULT NULL,
					secondary_data_id varchar(20) DEFAULT NULL,
					data longtext DEFAULT NULL,
					priority tinyint(2) DEFAULT NULL,
					blog_id bigint(20) NOT NULL,
					date_created datetime NOT NULL,
					PRIMARY KEY (id),
					KEY type (type),
					KEY `group` (`group`),
					KEY data_id (data_id),
					KEY secondary_data_id (secondary_data_id),
					KEY priority (priority),
					KEY blog_id (blog_id),
					KEY date_created (date_created)
				) {$charset_collate};";

				dbDelta( $sql );
			}
		}

		/**
		 * Handle cron healthcheck event.
		 *
		 * Restart the background process if not already running
		 * and data exists in the queue.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function handle_cron_healthcheck() {
			if ( $this->is_processing() ) {
				// Background process already running.
				exit;
			}

			if ( $this->is_queue_empty() ) {
				// No data to process.
				$this->clear_scheduled_event();
				exit;
			}

			$this->dispatch();
		}

		/**
		 * Schedule the cron healthcheck job.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @access public
		 *
		 * @param mixed $schedules Schedules.
		 *
		 * @return mixed
		 */
		public function schedule_cron_healthcheck( $schedules ) {
			$interval = apply_filters( $this->cron_interval_identifier, 5 );

			if ( property_exists( $this, 'cron_interval' ) ) {
				$interval = apply_filters( $this->cron_interval_identifier, $this->cron_interval );
			}

			if ( 1 === $interval ) {
				$display = __( 'Every Minute' );
			} else {
				/* translators: %d: Number of minutes */
				$display = sprintf( __( 'Every %d Minutes' ), $interval );
			}

			// Adds an "Every NNN Minute(s)" schedule to the existing cron schedules.
			$schedules[ $this->cron_interval_identifier ] = array(
				'interval' => MINUTE_IN_SECONDS * $interval,
				'display'  => $display,
			);

			return $schedules;
		}

		/**
		 * Dispatch the async request.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array|WP_Error|false HTTP Response array, WP_Error on failure, or false if not attempted.
		 */
		public function dispatch() {
			if ( $this->is_processing() ) {
				// Process already running.
				return false;
			}

			// Schedule the cron healthcheck.
			$this->schedule_event();

			$url  = add_query_arg( $this->get_query_args(), $this->get_query_url() );
			$args = $this->get_post_args();

			return wp_remote_post( esc_url_raw( $url ), $args );
		}

		/**
		 * Get query args.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array
		 */
		protected function get_query_args() {
			if ( property_exists( $this, 'query_args' ) ) {
				return $this->query_args;
			}

			$args = array(
				'action' => $this->identifier,
				'nonce'  => wp_create_nonce( $this->identifier ),
			);

			/**
			 * Filters the post arguments used during an async request.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $url
			 */
			return apply_filters( $this->identifier . '_query_args', $args );
		}

		/**
		 * Get query URL.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string
		 */
		protected function get_query_url() {
			if ( property_exists( $this, 'query_url' ) ) {
				return $this->query_url;
			}

			$url = admin_url( 'admin-ajax.php' );

			/**
			 * Filters the post arguments used during an async request.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param string $url
			 */
			return apply_filters( $this->identifier . '_query_url', $url );
		}

		/**
		 * Get post args.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array
		 */
		protected function get_post_args() {
			if ( property_exists( $this, 'post_args' ) ) {
				return $this->post_args;
			}

			$args = array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'body'      => $this->data,
				'cookies'   => $_COOKIE, // Passing cookies ensures request is performed as initiating user.
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // Local requests, fine to pass false.
			);

			/**
			 * Filters the post arguments used during an async request.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $args
			 */
			return apply_filters( $this->identifier . '_post_args', $args );
		}

		/**
		 * Maybe handle a dispatched request.
		 *
		 * Check for correct nonce and pass to handler.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void|mixed
		 */
		public function maybe_handle() {
			// Don't lock up other requests while processing.
			session_write_close();

			if ( $this->is_processing() ) {
				// Background process already running.
				return $this->maybe_wp_die();
			}

			if ( $this->is_cancelled() ) {
				$this->clear_scheduled_event();
				$this->delete_all();

				return $this->maybe_wp_die();
			}

			if ( $this->is_paused() ) {
				$this->clear_scheduled_event();
				$this->paused();

				return $this->maybe_wp_die();
			}

			if ( $this->is_queue_empty() ) {
				// No data to process.
				return $this->maybe_wp_die();
			}

			check_ajax_referer( $this->identifier, 'nonce' );

			$this->handle();

			return $this->maybe_wp_die();
		}

		/**
		 * Should the process exit with wp_die?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $return What to return if filter says don't die, default is null.
		 *
		 * @return void|mixed
		 */
		protected function maybe_wp_die( $return = null ) {
			/**
			 * Should wp_die be used?
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @return bool
			 */
			if ( apply_filters( $this->identifier . '_wp_die', true ) ) {
				wp_die();
			}

			return $return;
		}

		/**
		 * Handle a dispatched request.
		 *
		 * Pass each queue item to the task handler, while remaining
		 * within server memory and time limit constraints.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function handle() {
			$this->lock_process();

			/**
			 * Number of seconds to sleep between batches. Defaults to 0 seconds, minimum 0.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param int $seconds
			 */
			$throttle_seconds = max(
				0,
				apply_filters(
					$this->identifier . '_seconds_between_batches',
					apply_filters(
						$this->prefix . '_seconds_between_batches',
						0
					)
				)
			);

			do {
				$batch = $this->get_batch();

				do_action( $this->identifier . '_batch_process', $batch );

				$key_id = $batch->key;

				$task = $this->task( $batch->data );

				if ( false !== $task ) {
					$batch->data = $task;
				} else {
					unset( $batch );
				}

				// Keep the batch up to date while processing it.
				if ( ! empty( $batch->data ) ) {
					$this->update( $key_id, $batch->data );
				}

				// Let the server breathe a little.
				sleep( $throttle_seconds );

				// Batch limits reached, or pause or cancel request.
				if ( $this->time_exceeded() || $this->memory_exceeded() || $this->is_paused() || $this->is_cancelled() ) {
					break;
				}

				// Delete current batch if fully processed.
				if ( empty( $batch->data ) ) {
					$this->delete( $key_id );
				}
			} while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() && ! $this->is_paused() && ! $this->is_cancelled() );

			$this->unlock_process();

			// Start next batch or complete process.
			if ( ! $this->is_queue_empty() ) {
				$this->dispatch();
			} else {
				$this->complete();
			}

			return $this->maybe_wp_die();
		}

		/**
		 * Is the background process currently running?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		public function is_processing() {
			if ( get_site_transient( $this->identifier . '_process_lock' ) ) {
				// Process already running.
				return true;
			}

			return false;
		}

		/**
		 * Is queue empty?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		protected function is_queue_empty() {
			return empty( $this->get_batch() );
		}

		/**
		 * Clear scheduled cron healthcheck event.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function clear_scheduled_event() {
			$timestamp = wp_next_scheduled( $this->cron_hook_identifier );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $this->cron_hook_identifier );
			}
		}

		/**
		 * Schedule the cron healthcheck event.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function schedule_event() {
			if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
				wp_schedule_event( time(), $this->cron_interval_identifier, $this->cron_hook_identifier );
			}
		}

		/**
		 * Push to the queue.
		 *
		 * Note, save must be called in order to persist queued items to a batch for processing.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $data Data.
		 *
		 * @return $this
		 */
		public function push_to_queue( $data ) {
			$data_array = wp_parse_args(
				$data,
				array(
					'callback'          => '',
					'type'              => '',
					'group'             => '',
					'data_id'           => '',
					'secondary_data_id' => '',
					'args'              => array(),
					'priority'          => 10,
					'blog_id'           => get_current_blog_id(),
					'date_created'      => bp_core_current_time(),
				)
			);

			$this->data[] = $data_array;

			return $this;
		}

		/**
		 * Set data used during the request.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $data Data.
		 *
		 * @return $this
		 */
		public function data( $data ) {
			$data_array = wp_parse_args(
				$data,
				array(
					'callback'          => '',
					'type'              => '',
					'group'             => '',
					'data_id'           => '',
					'secondary_data_id' => '',
					'args'              => array(),
					'priority'          => 10,
					'blog_id'           => get_current_blog_id(),
					'date_created'      => bp_core_current_time(),
				)
			);

			$this->data = $data_array;

			return $this;
		}

		/**
		 * Save the queued items for future processing.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return $this
		 */
		public function save() {
			global $wpdb;
			$key = $this->generate_key();

			if ( ! empty( $this->data ) && array_key_exists( 'callback', $this->data ) ) {
				$args_data = array(
					'callback' => $this->data['callback'],
					'args'     => (array) $this->data['args'],
				);

				$wpdb->insert(
					self::$table_name,
					array(
						'type'              => $this->data['type'],
						'group'             => $this->data['group'],
						'data_id'           => (int) $this->data['data_id'],
						'secondary_data_id' => (int) $this->data['secondary_data_id'],
						'data'              => maybe_serialize( $args_data ),
						'priority'          => (int) $this->data['priority'],
						'blog_id'           => (int) $this->data['blog_id'],
						'date_created'      => $this->data['date_created'],
					)
				);
			} elseif ( ! empty( $this->data ) ) {
				$key_check = array_column( $this->data, 'callback' );
				if ( ! empty( $key_check ) ) {
					foreach ( $this->data as $data ) {
						$args_data = array(
							'callback' => $data['callback'],
							'args'     => (array) $data['args'],
						);

						$wpdb->insert(
							self::$table_name,
							array(
								'type'              => $data['type'],
								'group'             => $data['group'],
								'data_id'           => (int) $data['data_id'],
								'secondary_data_id' => (int) $data['secondary_data_id'],
								'data'              => maybe_serialize( $args_data ),
								'priority'          => (int) $data['priority'],
								'blog_id'           => (int) $data['blog_id'],
								'date_created'      => $data['date_created'],
							)
						);
					}
				}
			}

			// Clean out data so that new data isn't prepended with closed session's data.
			$this->data = array();

			return $this;
		}

		/**
		 * Generate key for a batch.
		 *
		 * Generates a unique key based on microtime. Queue items are
		 * given a unique key so that they can be merged upon save.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $length Optional max length to trim key to, defaults to 64 characters.
		 * @param string $key    Optional string to append to identifier before hash, defaults to "batch".
		 *
		 * @return string
		 */
		protected function generate_key( $length = 64, $key = 'batch' ) {
			$unique  = md5( microtime() . wp_rand() );
			$prepend = $this->identifier . '_' . $key . '_';

			return substr( $prepend . $unique, 0, $length );
		}

		/**
		 * Get the status key.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string
		 */
		protected function get_status_key() {
			return $this->identifier . '_status';
		}


		/**
		 * Update a batch's queued items.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $key  Key.
		 * @param array  $data Data.
		 *
		 * @return $this
		 */
		public function update( $key, $data ) {
			if ( ! empty( $data ) ) {
				global $wpdb;

				$wpdb->update(
					self::$table_name,
					array(
						'data' => maybe_serialize( $data ),
					),
					array(
						'id' => (int) $key,
					)
				);
			}

			return $this;
		}

		/**
		 * Delete a batch of queued items.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $key Key.
		 *
		 * @return $this
		 */
		public function delete( $key ) {
			global $wpdb;

			$wpdb->delete(
				self::$table_name,
				array(
					'id' => (int) $key,
				)
			);

			return $this;
		}

		/**
		 * Delete entire job queue.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function delete_all() {
			$batches = $this->get_batches();

			foreach ( $batches as $batch ) {
				$this->delete( $batch->key );
			}

			delete_site_option( $this->get_status_key() );

			$this->cancelled();
		}

		/**
		 * Cancel job on next batch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function cancel() {
			update_site_option( $this->get_status_key(), self::STATUS_CANCELLED );

			// Just in case the job was paused at the time.
			$this->dispatch();
		}

		/**
		 * Has the process been cancelled?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		public function is_cancelled() {
			$status = get_site_option( $this->get_status_key(), 0 );

			if ( absint( $status ) === self::STATUS_CANCELLED ) {
				return true;
			}

			return false;
		}


		/**
		 * Called when background process has been cancelled.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function cancelled() {
			do_action( $this->identifier . '_cancelled' );
		}

		/**
		 * Pause job on next batch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function pause() {
			update_site_option( $this->get_status_key(), self::STATUS_PAUSED );
		}

		/**
		 * Is the job paused?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		public function is_paused() {
			$status = get_site_option( $this->get_status_key(), 0 );

			if ( absint( $status ) === self::STATUS_PAUSED ) {
				return true;
			}

			return false;
		}

		/**
		 * Called when background process has been paused.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function paused() {
			do_action( $this->identifier . '_paused' );
		}

		/**
		 * Resume job.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function resume() {
			delete_site_option( $this->get_status_key() );

			$this->schedule_event();
			$this->dispatch();
			$this->resumed();
		}

		/**
		 * Called when background process has been resumed.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function resumed() {
			do_action( $this->identifier . '_resumed' );
		}

		/**
		 * Is queued?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		public function is_queued() {
			return ! $this->is_queue_empty();
		}

		/**
		 * Is the tool currently active, e.g. starting, working, paused or cleaning up?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		public function is_active() {
			return $this->is_queued() || $this->is_processing() || $this->is_paused() || $this->is_cancelled();
		}

		/**
		 * Is process running?
		 *
		 * Check whether the current process is already running
		 * in a background process.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @deprecated 1.1.0 Superseded.
		 * @see is_processing()
		 *
		 * @return bool
		 */
		protected function is_process_running() {
			return $this->is_processing();
		}

		/**
		 * Lock process.
		 *
		 * Lock the process so that multiple instances can't run simultaneously.
		 * Override if applicable, but the duration should be greater than that
		 * defined in the time_exceeded() method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function lock_process() {
			$this->start_time = time(); // Set start time of current process.

			$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60; // 1 minute
			$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', $lock_duration );

			set_site_transient( $this->identifier . '_process_lock', microtime(), $lock_duration );
		}

		/**
		 * Unlock process.
		 *
		 * Unlock the process so that other instances can spawn.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return $this
		 */
		protected function unlock_process() {
			delete_site_transient( $this->identifier . '_process_lock' );

			return $this;
		}

		/**
		 * Complete processing.
		 *
		 * Override if applicable, but ensure that the below actions are
		 * performed, or, call parent::complete().
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function complete() {
			delete_site_option( $this->get_status_key() );

			// Remove the cron healthcheck job from the cron schedule.
			$this->clear_scheduled_event();

			$this->completed();
		}

		/**
		 * Called when background process has completed.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function completed() {
			// phpcs:ignore
			error_log( 'Data update completed' );
			do_action( $this->identifier . '_completed' );
		}


		/**
		 * Get batch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return stdClass Return the first batch of queued items.
		 */
		protected function get_batch() {
			return array_reduce(
				$this->get_batches( 1 ),
				function ( $carry, $batch ) {
					return $batch;
				},
				array()
			);
		}

		/**
		 * Get batches.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $limit Number of batches to return, defaults to all.
		 *
		 * @return array of stdClass
		 */
		public function get_batches( $limit = 0 ) {
			global $wpdb;

			if ( empty( $limit ) || ! is_int( $limit ) ) {
				$limit = 0;
			}

			$table                = self::$table_name;
			$blog_id              = get_current_blog_id();
			$id                   = 'id';
			$group                = 'group';
			$type                 = 'type';
			$value_item           = 'data_id';
			$value_secondary_item = 'secondary_data_id';
			$value_column         = 'data';

			$sql = '
			SELECT *
			FROM ' . $table . '
			WHERE blog_id = %d
			ORDER BY priority, id ASC
			';

			$args[] = $blog_id;

			if ( ! empty( $limit ) ) {
				$sql .= ' LIMIT %d';

				$args[] = $limit;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$items = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

			$batches = array();

			if ( ! empty( $items ) ) {
				$batches = array_map(
					function ( $item ) use ( $id, $group, $type, $value_item, $value_secondary_item, $value_column ) {
						$batch               = new stdClass();
						$batch->key          = $item->{$id};
						$batch->group        = $item->{$group};
						$batch->type         = $item->{$type};
						$batch->item_id      = $item->{$value_item};
						$batch->secondary_id = $item->{$value_secondary_item};
						$batch->data         = maybe_unserialize( $item->{$value_column} );

						return $batch;
					},
					$items
				);
			}

			return $batches;
		}

		/**
		 * Memory exceeded?
		 *
		 * Ensures the batch process never exceeds 90%
		 * of the maximum WordPress memory.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		protected function memory_exceeded() {
			$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
			$current_memory = memory_get_usage( true );
			$return         = false;

			if ( $current_memory >= $memory_limit ) {
				$return = true;
			}

			return apply_filters( $this->identifier . '_memory_exceeded', $return );
		}

		/**
		 * Get memory limit in bytes.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return int
		 */
		protected function get_memory_limit() {
			if ( function_exists( 'ini_get' ) ) {
				$memory_limit = ini_get( 'memory_limit' );
			} else {
				// Sensible default.
				$memory_limit = '128M';
			}

			if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
				// Unlimited, set to 32GB.
				$memory_limit = '32000M';
			}

			return wp_convert_hr_to_bytes( $memory_limit );
		}

		/**
		 * Time limit exceeded?
		 *
		 * Ensures the batch never exceeds a sensible time limit.
		 * A timeout limit of 30s is common on shared hosting.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return bool
		 */
		protected function time_exceeded() {
			$finish = $this->start_time + apply_filters( $this->identifier . '_default_time_limit', 20 ); // 20 seconds
			$return = false;

			if ( time() >= $finish ) {
				$return = true;
			}

			return apply_filters( $this->identifier . '_time_exceeded', $return );
		}

		/**
		 * Cancel the background process.
		 *
		 * Stop processing queue items, clear cron job and delete batch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @deprecated 1.1.0 Superseded.
		 * @see cancel()
		 */
		public function cancel_process() {
			$this->cancel();
		}

		/**
		 * Task.
		 *
		 * Override this method to perform any actions required on each
		 * queue item. Return the modified item for further processing
		 * in the next pass through. Or, return false to remove the
		 * item from the queue.
		 *
		 * @since BuddyBoss [BBVERSION]
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
				// phpcs:ignore
				error_log( sprintf( 'Running %s callback', json_encode( $callback ) ) );

				if ( empty( $args ) ) {
					$result = (bool) call_user_func( $callback, $this );
				} else {
					$result = (bool) call_user_func_array( $callback, $args );
				}

				if ( $result ) {
					// phpcs:ignore
					error_log( sprintf( '%s callback needs to run again', json_encode( $callback ) ) );
				} else {
					// phpcs:ignore
					error_log( sprintf( 'Finished running %s callback', json_encode( $callback ) ) );
				}
			} else {
				// phpcs:ignore
				error_log( sprintf( 'Could not find %s callback', json_encode( $callback ) ) );
			}

			return $result ? $callback : false;
		}

		/**
		 * Kill process.
		 *
		 * Stop processing queue items, clear cronjob and delete all batches.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function kill_process() {
			if ( ! $this->is_queue_empty() ) {
				$this->delete_all_batches();
				wp_clear_scheduled_hook( $this->cron_hook_identifier );
			}
		}

		/**
		 * Delete all batches.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return WC_Background_Process
		 */
		public function delete_all_batches() {
			global $wpdb;

			$table  = $wpdb->options;
			$column = 'option_name';

			if ( is_multisite() ) {
				$table  = $wpdb->sitemeta;
				$column = 'meta_key';
			}

			$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) ); // @codingStandardsIgnoreLine.

			return $this;
		}

		/**
		 * Is the updater running?
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return boolean
		 */
		public function is_updating() {
			return false === $this->is_queue_empty();
		}

	}
}
