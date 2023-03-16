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

			if ( ! empty( $activity[ $user_id ] ) ) {
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
	}

	BB_Presence::instance();
}
