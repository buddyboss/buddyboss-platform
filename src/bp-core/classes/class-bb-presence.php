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

			$activity      = self::bb_get_users_last_activity( array( $user_id ) );
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
			$notice        = sprintf(
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

			$file_url = trailingslashit( plugin_dir_url( __DIR__ ) ) . 'bb-core-native-presence.php?direct_allow=true';
			$response = wp_remote_get( $file_url );

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$response_body = wp_remote_retrieve_body( $response );
				$result        = json_decode( $response_body, true );
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
			$last_activity_data = self::bb_get_users_last_activity( array( $user_id ) );
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

		/**
		 * Load the hooks for the mu level
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void
		 */
		public function bb_presence_mu_loader() {
			add_action( 'set_current_user', array( $this, 'bb_is_set_current_user' ), 1 );

			add_filter( 'rest_cache_pre_current_user_id', array( $this, 'bb_cookie_support' ), 1 );
			add_filter( 'rest_cache_pre_current_user_id', array( $this, 'bb_jwt_auth_support' ), 2 );

			$this->prepare_presence_mu();
		}

		/**
		 * Identify the current user is set by WordPress.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_is_set_current_user() {
			global $bb_is_current_user_available;
			$bb_is_current_user_available = true;
		}

		/**
		 * Tells if the current user information is available on WordPress.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function is_current_user_available() {
			global $bb_is_current_user_available;
			if ( isset( $bb_is_current_user_available ) ) {
				return $bb_is_current_user_available;
			}

			return false;
		}

		/**
		 * Check if user logged in or not.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function get_loggedin_user_id() {

			if ( $this->is_current_user_available() ) {
				return get_current_user_id();
			} else {
				$guessed_user_id = $this->get_guessed_user_id();
				if ( ! $guessed_user_id ) {
					return 0;
				}

				return $guessed_user_id;
			}
		}

		/**
		 * Guessed user ID.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return int|boolean
		 */
		public function get_guessed_user_id() {
			$guessed_user_id = apply_filters( 'rest_cache_pre_current_user_id', false );

			return $guessed_user_id;
		}

		/**
		 * Get Pre User ID from WordPress Cookie.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $user_id User ID.
		 *
		 * @return int
		 */
		public function bb_cookie_support( $user_id ) {
			$scheme          = apply_filters( 'auth_redirect_scheme', '' );
			$cookie_elements = $this->wp_parse_auth_cookie( '', $scheme );

			if ( $cookie_elements && isset( $cookie_elements['username'] ) ) {
				global $wpdb;

				// @todo: any idea to avoid this query ?
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$get_user = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_login=%s", $cookie_elements['username'] ) );

				if ( $get_user ) {
					return $get_user->ID;
				}
			}
		}

		/**
		 * Copied from wp-includes/pluggable.php.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @see wp-includes/pluggable.php
		 *
		 * @param string $cookie Cookie value.
		 * @param string $scheme Schema.
		 *
		 * @return array|bool
		 */
		public function wp_parse_auth_cookie( $cookie = '', $scheme = '' ) {
			if ( empty( $cookie ) ) {

				// @see wp_cookie_constants()..
				$siteurl = get_site_option( 'siteurl' );
				if ( $siteurl ) {
					$cookie_hash = md5( $siteurl );
				} else {
					$cookie_hash = '';
				}

				// @see wp_cookie_constants()..
				if ( is_ssl() ) {
					$cookie_name = 'wordpress_sec_' . $cookie_hash;
					$scheme      = 'secure_auth';
				} else {
					$cookie_name = 'wordpress_' . $cookie_hash;
					$scheme      = 'auth';
				}

				if ( empty( $_COOKIE[ $cookie_name ] ) ) {
					return false;
				}

				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$cookie = $_COOKIE[ $cookie_name ];
			}

			$cookie_elements = explode( '|', $cookie );
			if ( count( $cookie_elements ) !== 4 ) {
				return false;
			}

			list( $username, $expiration, $token, $hmac ) = $cookie_elements;

			return compact( 'username', 'expiration', 'token', 'hmac', 'scheme' );
		}

		/**
		 * Get the Pre User ID from BuddyBoss APP JWT Token.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $user_id User ID.
		 *
		 * @return int|void
		 */
		public function bb_jwt_auth_support( $user_id ) {

			$header = $this->get_all_headers();

			$jwt_token = false;
			if ( ! empty( $header ) ) {
				foreach ( $header as $k => $v ) {
					if ( strtolower( $k ) === 'accesstoken' ) {
						$jwt_token = $v;
						break;
					}
				}
			}

			if ( $jwt_token ) {

				$token = explode( '.', $jwt_token );
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$token = (array) json_decode( base64_decode( $token[1] ) );

				if ( isset( $token['data'] ) && isset( $token['data']->user ) && isset( $token['data']->user->id ) ) {
					$user_id = $token['data']->user->id;

					// Check if there is any switch user.
					$switch_data = get_user_meta( $user_id, '_bbapp_jwt_switch_user', true );
					$switch_data = ( ! is_array( $switch_data ) ) ? array() : $switch_data;
					$jti         = ( isset( $token['jti'] ) ) ? $token['jti'] : false;

					// if switch user is found for current access token pass it.
					if ( $jti && isset( $switch_data[ $jti ] ) && is_numeric( $switch_data[ $jti ] ) ) {
						return (int) $switch_data[ $jti ];
					}

					// End Switch user logic's.

					return $user_id;
				}
			}

		}

		/**
		 * Get Headers.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array|false|string
		 */
		public function get_all_headers() {

			if ( function_exists( 'getallheaders' ) ) {
				return getallheaders();
			}

			if ( ! is_array( $_SERVER ) ) {
				return array();
			}

			$headers = array();
			foreach ( $_SERVER as $name => $value ) {
				if ( substr( $name, 0, 5 ) === 'HTTP_' ) {
					$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
				}
			}

			return $headers;
		}

		/**
		 * Returns the current web location.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string
		 */
		public function get_current_path() {
			return add_query_arg( null, null );
		}

		/**
		 * Get current endpoint.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @todo :- research the correct wp way to get current endpoint. I doubt on this.
		 *
		 * @return string|bool
		 */
		public function get_current_endpoint() {
			$current_path = $this->get_current_path();
			if ( strpos( $current_path, 'wp-json/' ) !== false ) {

				$current_path = explode( 'wp-json/', $current_path );
				$current_path = $current_path[1];

				// remove query vars.
				if ( strpos( $current_path, '?' ) !== false ) {
					$current_path = explode( '?', $current_path );
					$current_path = $current_path[0];
				}

				return trim( $current_path );

			}

			return false;
		}

		/**
		 * Function to load response from mu plugin.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function prepare_presence_mu() {

			// Check if we are in WP API.
			if ( strpos( $this->get_current_path(), 'wp-json/buddyboss/v1/members/presence' ) !== false ) {

				/**
				 * Remove WordPress Extra Headaches.
				 */

				// Tell WordPress to Don't Load Theme.
				add_filter(
					'wp_using_themes',
					function ( $wp_use_themes ) {
						$wp_use_themes = false;

						return $wp_use_themes;
					}
				);
				add_filter(
					'option_stylesheet',
					function ( $stylesheet ) {
						return '';
					}
				);
				add_filter(
					'option_template',
					function ( $template ) {
						return '';
					}
				);

				// Disable all plugins for this request as we will fire cache on init hook.
				add_filter(
					'option_active_plugins',
					function ( $plugins ) {
						if ( ! empty( $plugins ) ) {
							foreach ( $plugins as $plugin_key => $plugin_val ) {
								unset( $plugins[ $plugin_key ] );
							}
						}

						return $plugins;
					}
				);

				// Disable all plugins for this request as we will fire cache on init hook. Network Mode.
				add_filter(
					'option_active_sitewide_plugins',
					function ( $plugins ) {
						if ( ! empty( $plugins ) ) {
							foreach ( $plugins as $plugin_key => $plugin_val ) {
								unset( $plugins[ $plugin_key ] );
							}
						}

						return $plugins;
					}
				);

				// Disable all plugins for this request as we will fire cache on init hook. Network Mode.
				add_filter(
					'site_option_active_sitewide_plugins',
					function ( $plugins ) {
						if ( ! empty( $plugins ) ) {
							foreach ( $plugins as $plugin_key => $plugin_val ) {
								unset( $plugins[ $plugin_key ] );
							}
						}

						return $plugins;
					}
				);

				$user_id = $this->get_loggedin_user_id() ?? 0;

				$ids = (array) ( isset( $_POST['ids'] ) ? wp_parse_id_list( $_POST['ids'] ) : array() );

				$this->endpoint_cache_render( $user_id, $ids );

				exit;
			}

		}

		/**
		 * Function to cache rendered response in cache.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $user_id User id.
		 * @param array $ids     Post user ids.
		 */
		public function endpoint_cache_render( $user_id, $ids ) {
			// Security Check.
			// When the cache generated to user is not matched with it's being delivered to output error.
			// Here we avoid passing another user cached instead of logged in.
			if ( empty( $user_id ) ) {
				header( 'HTTP/1.0 401 Unauthorized' );
				header( 'Content-Type: application/json' );
				$retval = new WP_Error(
					'bp_rest_authorization_required_test',
					__( 'Sorry, you are not allowed to perform this action.', 'buddypress' ),
					array(
						'status' => 401,
					)
				);

				$error_data = rest_convert_error_to_response( $retval );
				echo wp_json_encode( $error_data->data );
				exit;
			}

			$current_endpoint = $this->get_current_endpoint();

			$header = apply_filters( 'rest_post_dispatch_header_cache', array(), $current_endpoint );

			$header['bb-presence-mu-api'] = true;
			if ( ! empty( $header ) ) {
				foreach ( $header as $header_key => $header_value ) {
					header( $header_key . ':' . $header_value );
				}
			}

			$presence_data = array();
			foreach ( array_unique( $ids ) as $user_id ) {
				$presence_data[] = array(
					'id'     => $user_id,
					'status' => self::bb_get_user_presence_mu_cache( $user_id ),
				);
			}


			echo wp_json_encode( apply_filters( 'rest_post_dispatch_cache', $presence_data, $current_endpoint ) );
			exit;
		}


	}

	BB_Presence::instance();
}
