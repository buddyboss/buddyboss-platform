<?php
/**
 * Presence class
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Presence' ) ) {

	/**
	 * BuddyBoss Presence object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_Presence {


		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * This will use for global $wpdb.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var object
		 */
		public static object $wpdb;

		/**
		 * This will use for last activity cache time.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var int
		 */
		public static int $cache_time;

		/**
		 * Activity table name to store last activity.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		public static string $table_name;

		/**
		 * Download plugin text which appears on admin side.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		public static string $download_plugin_text;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return Controller|BB_Presence|null
		 */
		public static function instance() {

			if ( null === self::$instance ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
			}

			return self::$instance;
		}

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			global $wpdb;

			self::$wpdb       = $wpdb;
			self::$cache_time = (int) apply_filters( 'bb_presence_last_activity_cache_time', 60 );
			self::$table_name = $wpdb->prefix . 'bp_activity';
		}

		/**
		 * Function will update the user's last activity time in cache and DB.
		 *
		 * @param int    $user_id The ID of the user.
		 * @param string $time Time into the mysql format.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public static function bb_update_last_activity( $user_id, $time = '' ) {
			// Fall back on current time.
			if ( empty( $time ) ) {
				$time = current_time( 'mysql', true );
			}

			$activity      = self::bb_get_users_last_activity( $user_id );
			$last_activity = isset( $activity[ $user_id ]['date_recorded'] ) ? strtotime( $activity[ $user_id ]['date_recorded'] ) : time();
			$cache         = wp_cache_get( $user_id, 'bp_last_activity' );

			if ( false !== $cache && time() - $last_activity < self::$cache_time ) {
				// Update the cache directly.
				$activity[ $user_id ]['date_recorded'] = $time;
				wp_cache_set( $user_id, $activity[ $user_id ], 'bp_last_activity' );

				return;
			}

			// Update last activity in user meta also.
			remove_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10 );
			remove_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10 );
			update_user_meta( $user_id, 'last_activity', $time );
			add_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10, 4 );
			add_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10, 4 );

			if ( ! empty( $activity[ $user_id ]['activity_id'] ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				self::$wpdb->update(
					self::$table_name,
					array( 'date_recorded' => $time ),
					array( 'id' => $activity[ $user_id ]['activity_id'] ),
					array( '%s' ),
					array( '%d' )
				);

				// Add new date to existing activity entry for caching.
				$activity[ $user_id ]['date_recorded'] = $time;

			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				self::$wpdb->insert(
					self::$table_name,
					// Data.
					array(
						'user_id'       => $user_id,
						'component'     => 'members',
						'type'          => 'last_activity',
						'action'        => '',
						'content'       => '',
						'primary_link'  => '',
						'item_id'       => 0,
						'date_recorded' => $time,
					),
					// Data sanitization format.
					array(
						'%d',
						'%s',
						'%s',
						'%s',
						'%s',
						'%s',
						'%d',
						'%s',
					)
				);

				// Set up activity array for caching.
				// View the foreach loop in the get_last_activity() method for format.
				$activity             = array();
				$activity[ $user_id ] = array(
					'user_id'       => $user_id,
					'date_recorded' => $time,
					'activity_id'   => self::$wpdb->insert_id,
				);
			}

			// Set cache.
			wp_cache_set( $user_id, $activity[ $user_id ], 'bp_last_activity' );
		}

		/**
		 * Function will fetch the users last activity time from the cache or DB.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $user_ids Array of user IDs.
		 *
		 * @return array
		 */
		public static function bb_get_users_last_activity( $user_ids ) {
			// Sanitize and remove empty values.
			$user_ids = array_filter( wp_parse_id_list( $user_ids ) );

			if ( empty( $user_ids ) ) {
				return array();
			}

			$uncached_user_ids = array();
			$cached_data       = array();

			foreach ( $user_ids as $user_id ) {
				$user_id = (int) $user_id;
				$cache   = wp_cache_get( $user_id, 'bp_last_activity' );
				if ( false === $cache ) {
					$uncached_user_ids[] = $user_id;
				} else {
					$cached_data[ $user_id ] = $cache;
				}
			}

			if ( ! empty( $uncached_user_ids ) ) {
				$user_ids_sql = implode( ',', $uncached_user_ids );

				$t_name = self::$table_name;

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query = self::$wpdb->prepare( "SELECT id, user_id, date_recorded FROM {$t_name} WHERE component = %s AND type = 'last_activity' AND user_id IN ({$user_ids_sql})", 'members' );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
				$last_activities = self::$wpdb->get_results( $query );

				if ( ! empty( $last_activities ) ) {
					foreach ( $last_activities as $last_activity ) {
						wp_cache_set(
							$last_activity->user_id,
							array(
								'user_id'       => $last_activity->user_id,
								'date_recorded' => $last_activity->date_recorded,
								'activity_id'   => $last_activity->id,
							),
							'bp_last_activity'
						);
					}

					$cached_data = array_merge( $cached_data, $last_activities );
				}
			}

			return $cached_data;
		}

		/**
		 * Load presence API mu plugin.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public static function bb_load_presence_api_mu_plugin() {

			$bb_presence_api_mu_download = get_transient( 'bb_presence_api_mu_download' );
			if ( ! empty( $bb_presence_api_mu_download ) ) {
				return;
			}

			// If mu-plugin directory not exists then create automatically.
			if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
				mkdir( WPMU_PLUGIN_DIR, 0755 );
			}

			$bp_platform_mu_path     = WP_PLUGIN_DIR . '/buddyboss-platform/bp-core/mu-plugins/buddyboss-presence-api.php';
			$bp_platform_dev_mu_path = WP_PLUGIN_DIR . '/buddyboss-platform/src/bp-core/mu-plugins/buddyboss-presence-api.php';
			if ( file_exists( $bp_platform_mu_path ) ) {
				$bp_mu_plugin_file_path = $bp_platform_mu_path;
			} elseif ( file_exists( $bp_platform_dev_mu_path ) ) {
				$bp_mu_plugin_file_path = $bp_platform_dev_mu_path;
			}

			if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			}

			$mu_plugins      = get_mu_plugins();
			$wp_files_system = new \WP_Filesystem_Direct( array() );
			if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-presence-api.php' ) ) {
				// Try to automatically install MU plugin.
				if ( wp_is_writable( WPMU_PLUGIN_DIR ) && ! empty( $bp_mu_plugin_file_path ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@$wp_files_system->copy( $bp_mu_plugin_file_path, WPMU_PLUGIN_DIR . '/buddyboss-presence-api.php' );
				}
			} elseif (
				! empty( $mu_plugins ) &&
				array_key_exists( 'buddyboss-presence-api.php', $mu_plugins ) &&
				version_compare( $mu_plugins['buddyboss-presence-api.php']['Version'], '1.0.1', '<' )
			) {
				// Try to automatically install MU plugin.
				if ( wp_is_writable( WPMU_PLUGIN_DIR ) && ! empty( $bp_mu_plugin_file_path ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@$wp_files_system->copy( $bp_mu_plugin_file_path, WPMU_PLUGIN_DIR . '/buddyboss-presence-api.php', true );
				}
			}

			set_transient( 'bb_presence_api_mu_download', 'true', DAY_IN_SECONDS );

			if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-presence-api.php' ) ) {
				self::$download_plugin_text = __( 'Download the plugin', 'buddyboss' );
				add_action( 'admin_notices', array( get_called_class(), 'bb_add_sitewide_notice_for_presence_api_mu_file' ) );
			} elseif (
				! empty( $mu_plugins ) &&
				array_key_exists( 'buddyboss-presence-api.php', $mu_plugins ) &&
				version_compare( $mu_plugins['buddyboss-presence-api.php']['Version'], '1.0.1', '<' )
			) {
				self::$download_plugin_text = __( 'Download v1.0.1', 'buddyboss' );
				add_action( 'admin_notices', array( get_called_class(), 'bb_add_sitewide_notice_for_presence_api_mu_file' ) );
			}
		}

		/**
		 * Function to add/update admin notice to download BuddyBoss Presence API mu plugin file if not exists.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public static function bb_add_sitewide_notice_for_presence_api_mu_file() {

			$bp_performance_download_nonce = wp_create_nonce( 'bb_presence_api_mu_download' );

			$download_path = admin_url( 'admin.php?page=bp-settings&download_mu_bpa_file=' . $bp_performance_download_nonce );
			$notice = sprintf(
				'%1$s <a href="%2$s">%3$s</a>. <br /><strong><a href="%4$s">%5$s</a></strong> %6$s',
				__( 'Presence API Caching cannot be automatically installed on your server. To enable caching, you need to manually install the "BuddyBoss Presence API" plugin in your', 'buddyboss' ),
				'https://wordpress.org/support/article/must-use-plugins/',
				__( 'must-use plugins', 'buddyboss' ),
				$download_path,
				self::$download_plugin_text,
				__( 'and then upload it into the "/wp-content/mu-plugins/" directory on your server.', 'buddyboss' )
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="notice notice-error">' . wpautop( $notice ) . '</div>';
		}

		/**
		 * Function to check native presence file load directly or not.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public static function bb_check_native_presence_load_directly() {
			$bb_check_native_presence_load_directly = get_transient( 'bb_check_native_presence_load_directly' );
			if ( ! empty( $bb_check_native_presence_load_directly ) ) {
				return;
			}

			$file_url = plugin_dir_url( __DIR__ ) . 'bp-core/bb-core-native-presence.php?direct_allow=true'; //bb_native_presence_path( array( 'direct_allow' => 'true' ) );
			$response = wp_remote_get( $file_url );
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$responseBody = wp_remote_retrieve_body( $response );
				$result       = json_decode( $responseBody, true );
				if (
					! empty( $result ) &&
					isset( $result['success'] ) &&
					isset( $result['data']['direct_allow'] ) &&
					true === $result['data']['direct_allow']
				) {
					update_option( 'bb_use_core_native_presence', true );
				} else {
					update_option( 'bb_use_core_native_presence', false );
				}
			} else {
				update_option( 'bb_use_core_native_presence', false );
			}

			set_transient( 'bb_check_native_presence_load_directly', 'true', DAY_IN_SECONDS );
		}

		/**
		 * Current user online activity time.
		 *
		 * @since BuddyPress [BBVERSION]
		 *
		 * @param int      $user_id User id.
		 * @param bool|int $expiry  Given time or whether to check degault timeframe.
		 *
		 * @return string
		 */
		public static function bb_is_online_user_mu_cache( $user_id, $expiry = false ) {

			$last_activity      = '';
			$last_activity_data = self::bb_get_users_last_activity( $user_id );
			if ( ! empty( $last_activity_data[ $user_id ]['date_recorded'] ) ) {
				$last_activity = strtotime( $last_activity_data[ $user_id ]['date_recorded'] );
			}

			if ( empty( $last_activity ) ) {
				return false;
			}

			$bb_presence_interval  = get_option( 'bb_presence_interval', 60 );
			$bb_presence_time_span = 20;

			if ( is_int( $expiry ) && ! empty( $expiry ) ) {
				$timeframe = $expiry;
			} else {
				$timeframe = $bb_presence_interval + $bb_presence_time_span;
			}

			$online_time = $timeframe;

			return time() - $last_activity <= $online_time;
		}

		/**
		 * Get the given user ID online/offline status.
		 *
		 * @since BuddyPress [BBVERSION]
		 *
		 * @param int      $user_id User id.
		 * @param bool|int $expiry  Given time or whether to check degault timeframe.
		 *
		 * @return string
		 */
		public static function bb_get_user_presence_mu_cache( $user_id, $expiry = false ) {
			if ( self::bb_is_online_user_mu_cache( $user_id, $expiry ) ) {
				return 'online';
			} else {
				return 'offline';
			}
		}
	}

	BB_Presence::instance();
}
