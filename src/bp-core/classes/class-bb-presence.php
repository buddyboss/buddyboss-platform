<?php
/**
 * Presence class.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 2.3.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Presence' ) ) {

	/**
	 * BuddyBoss Presence object.
	 *
	 * @since BuddyBoss 2.3.1
	 */
	class BB_Presence {


		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * This will use for global $wpdb.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @var object
		 */
		public static object $wpdb;

		/**
		 * This will use for last activity cache time.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @var int
		 */
		public static int $cache_time;

		/**
		 * Activity table name to store last activity.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @var string
		 */
		public static string $table_name;

		/**
		 * Download plugin text which appears on admin side.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @var string
		 */
		public static string $download_plugin_text;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss 2.3.1
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
		 * @since BuddyBoss 2.3.1
		 */
		public function __construct() {
			global $wpdb;

			self::$wpdb       = $wpdb;
			self::$cache_time = (int) apply_filters( 'bb_presence_last_activity_cache_time', 300 );
			self::$table_name = $wpdb->base_prefix . 'bp_activity';
		}

		/**
		 * Function will update the user's last activity time in cache and DB.
		 *
		 * @param int    $user_id The ID of the user.
		 * @param string $time Time into the mysql format.
		 *
		 * @since BuddyBoss 2.3.1
		 */
		public static function bb_update_last_activity( $user_id, $time = '' ) {
			// Fall back on current time.
			if ( empty( $time ) ) {
				$time = current_time( 'mysql', true );
			}

			$activity      = self::bb_get_users_last_activity( array( $user_id ) );
			$last_activity = isset( $activity[ $user_id ]['date_recorded'] ) ? strtotime( $activity[ $user_id ]['date_recorded'] ) : time();
			$cache         = wp_cache_get( $user_id, 'bp_last_activity' );

			$check_time = ! empty( $cache['db_recorded'] ) ? strtotime( $cache['db_recorded'] ) : $last_activity;

			if (
				false !== $cache &&
				! empty( $cache['cache_status'] ) &&
				time() - $check_time < self::$cache_time
			) {
				// Update the cache directly.
				$activity[ $user_id ]['date_recorded'] = $time;
				$activity[ $user_id ]['cache_status']  = true;
				wp_cache_set( $user_id, $activity[ $user_id ], 'bp_last_activity' );

				return;
			}

			if ( ! function_exists( 'get_user_by ' ) ) {
				// Require files used for cookie-based user authentication.
				require ABSPATH . WPINC . '/pluggable.php';
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
				$activity[ $user_id ]['db_recorded']   = $time;
				$activity[ $user_id ]['cache_status']  = true;

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
					'db_recorded'   => $time,
					'cache_status'  => true,
					'activity_id'   => self::$wpdb->insert_id,
				);
			}

			// Set cache.
			wp_cache_set( $user_id, $activity[ $user_id ], 'bp_last_activity' );
		}

		/**
		 * Function will fetch the users last activity time from the cache or DB.
		 *
		 * @since BuddyBoss 2.3.1
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
				$table_name   = self::$table_name;

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query = self::$wpdb->prepare( "SELECT id, user_id, date_recorded FROM {$table_name} WHERE component = %s AND type = 'last_activity' AND user_id IN ({$user_ids_sql})", 'members' );

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
				$last_activities = self::$wpdb->get_results( $query, ARRAY_A );

				if ( ! empty( $last_activities ) ) {
					foreach ( $last_activities as $last_activity ) {
						$data = array(
							'user_id'       => $last_activity['user_id'],
							'date_recorded' => $last_activity['date_recorded'],
							'db_recorded'   => $last_activity['date_recorded'],
							'cache_status'  => false,
							'activity_id'   => $last_activity['id'],
						);

						wp_cache_set(
							$last_activity['user_id'],
							$data,
							'bp_last_activity'
						);

						$cached_data[ $last_activity['user_id'] ] = $data;
					}
				}
			}

			return $cached_data;
		}

		/**
		 * Load presence API mu plugin.
		 *
		 * @param bool $bypass Bypass transient.
		 *
		 * @since BuddyBoss 2.3.1
		 */
		public static function bb_load_presence_api_mu_plugin( $bypass = true ) {
			if ( ! function_exists( 'buddypress' ) ) {
				return;
			}

			if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			}

			$wp_files_system        = new \WP_Filesystem_Direct( array() );
			$bp_mu_plugin_file_path = buddypress()->plugin_dir . 'bp-core/mu-plugins/buddyboss-performance-api.php';

			$purge_nonce = ( ! empty( $_GET['download_mu_bpa_file'] ) ) ? wp_unslash( $_GET['download_mu_bpa_file'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( wp_verify_nonce( $purge_nonce, 'bb_presence_api_mu_download' ) && ! empty( $bp_mu_plugin_file_path ) ) {
				if ( file_exists( $bp_mu_plugin_file_path ) ) {
					header( 'Content-Type: application/force-download' );
					header( 'Content-Disposition: attachment; filename="' . basename( $bp_mu_plugin_file_path ) . '"' );
					header( 'Expires: 0' );
					header( 'Cache-Control: must-revalidate' );
					header( 'Pragma: public' );
					header( 'Content-Length: ' . filesize( $bp_mu_plugin_file_path ) );
					flush();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $wp_files_system->get_contents( $bp_mu_plugin_file_path );
					die();
				}
			}

			$bb_presence_api_mu_download = get_transient( 'bb_presence_api_mu_download' );
			if ( ! empty( $bb_presence_api_mu_download ) && ! $bypass ) {
				return;
			}

			// If mu-plugin directory not exists then create automatically.
			if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
				mkdir( WPMU_PLUGIN_DIR, 0755 );
			}

			$mu_plugins = get_mu_plugins();
			if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-performance-api.php' ) ) {
				// Try to automatically install MU plugin.
				if ( wp_is_writable( WPMU_PLUGIN_DIR ) && ! empty( $bp_mu_plugin_file_path ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@$wp_files_system->copy( $bp_mu_plugin_file_path, WPMU_PLUGIN_DIR . '/buddyboss-performance-api.php' );
				}
			} elseif (
				! empty( $mu_plugins ) &&
				array_key_exists( 'buddyboss-performance-api.php', $mu_plugins ) &&
				version_compare( $mu_plugins['buddyboss-performance-api.php']['Version'], '1.0.1', '<' )
			) {
				// Try to automatically install MU plugin.
				if ( wp_is_writable( WPMU_PLUGIN_DIR ) && ! empty( $bp_mu_plugin_file_path ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@$wp_files_system->copy( $bp_mu_plugin_file_path, WPMU_PLUGIN_DIR . '/buddyboss-performance-api.php', true );
				}
			}

			set_transient( 'bb_presence_api_mu_download', 'true', WEEK_IN_SECONDS );

			if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-performance-api.php' ) ) {
				self::$download_plugin_text = __( 'Download the plugin', 'buddyboss' );
				add_action( 'admin_notices', array( get_called_class(), 'bb_add_sitewide_notice_for_presence_api_mu_file' ) );
			} elseif (
				! empty( $mu_plugins ) &&
				array_key_exists( 'buddyboss-performance-api.php', $mu_plugins ) &&
				version_compare( $mu_plugins['buddyboss-performance-api.php']['Version'], '1.0.0', '<' )
			) {
				self::$download_plugin_text = __( 'Download v1.0.0', 'buddyboss' );
				add_action( 'admin_notices', array( get_called_class(), 'bb_add_sitewide_notice_for_presence_api_mu_file' ) );
			}
		}

		/**
		 * Function to add/update admin notice to download BuddyBoss Performance API mu plugin file if not exists.
		 *
		 * @since BuddyBoss 2.3.1
		 */
		public static function bb_add_sitewide_notice_for_presence_api_mu_file() {

			$bp_performance_download_nonce = wp_create_nonce( 'bb_presence_api_mu_download' );

			$download_path = admin_url( 'admin.php?page=bp-settings&download_mu_bpa_file=' . $bp_performance_download_nonce );
			$notice        = sprintf(
				'%1$s <a href="%2$s">%3$s</a>. <br /><strong><a href="%4$s">%5$s</a></strong> %6$s',
				__( 'BuddyBoss Performance API cannot be automatically installed on your server. To improve performance, you need to manually install the "BuddyBoss Performance API" plugin in your', 'buddyboss' ),
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
		 * @param bool $bypass Bypass transient.
		 *
		 * @since BuddyBoss 2.3.1
		 */
		public static function bb_check_native_presence_load_directly( $bypass = false ) {
			$bb_check_native_presence_load_directly = get_transient( 'bb_check_native_presence_load_directly' );
			if ( ! empty( $bb_check_native_presence_load_directly ) && ! $bypass ) {
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

			set_transient( 'bb_check_native_presence_load_directly', 'true', WEEK_IN_SECONDS );
		}

		/**
		 * Current user online activity time.
		 *
		 * @since BuddyPress 2.3.1
		 *
		 * @param int      $user_id User id.
		 * @param bool|int $expiry  Given time or whether to check degault timeframe.
		 *
		 * @return string
		 */
		public static function bb_is_online_user( $user_id, $expiry = false ) {

			$last_activity      = '';
			$last_activity_data = self::bb_get_users_last_activity( array( $user_id ) );
			if ( ! empty( $last_activity_data[ $user_id ]['date_recorded'] ) ) {
				$last_activity = strtotime( $last_activity_data[ $user_id ]['date_recorded'] );
			}

			if ( empty( $last_activity ) ) {
				return false;
			}

			$bb_presence_interval  = function_exists( 'bb_presence_interval' ) ? bb_presence_interval() : self::bb_presence_interval();
			$bb_presence_time_span = function_exists( 'bb_presence_time_span' ) ? bb_presence_time_span() : self::bb_presence_time_span();
			if ( is_int( $expiry ) && ! empty( $expiry ) ) {
				$timeframe = $expiry;
			} else {
				$timeframe = $bb_presence_interval + $bb_presence_time_span;
			}

			return time() - $last_activity <= $timeframe;
		}

		/**
		 * Function to return the presence interval time in seconds at mu level.
		 * It will get bb_presence_interval_mu from DB if its empty then it will get bb_presence_default_interval_mu from DB.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return int
		 */
		public static function bb_presence_interval() {
			$bb_presence_interval_mu = (int) get_option( 'bb_presence_interval_mu' );
			if ( empty( $bb_presence_interval_mu ) ) {
				$bb_presence_interval_mu = (int) get_option( 'bb_presence_default_interval_mu', 60 );
			}

			return $bb_presence_interval_mu;
		}

		/**
		 * Function to return presence time span at mu level.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return int
		 */
		public static function bb_presence_time_span() {
			$bb_presence_time_span = (int) get_option( 'bb_presence_time_span_mu', 20 );

			return $bb_presence_time_span;
		}

		/**
		 * Get the given user ID online/offline status.
		 *
		 * @since BuddyPress 2.3.1
		 *
		 * @param int      $user_id User id.
		 * @param bool|int $expiry  Given time or whether to check degault timeframe.
		 *
		 * @return string
		 */
		public static function bb_get_user_presence( $user_id, $expiry = false ) {
			if ( self::bb_is_online_user( $user_id, $expiry ) ) {
				return 'online';
			} else {
				return 'offline';
			}
		}

		/**
		 * Load the hooks for the mu level.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return void
		 */
		public function bb_presence_mu_loader() {
			add_action( 'set_current_user', array( $this, 'bb_is_set_current_user' ), 1 );

			add_filter( 'bb_rest_cache_pre_current_user_id', array( $this, 'bb_cookie_support' ), 1 );
			add_filter( 'bb_rest_cache_pre_current_user_id', array( $this, 'bb_jwt_auth_support' ), 2 );

			if ( ! isset( $_GET['bypass'] ) ) { // phpcs:ignore
				$this->bb_prepare_presence_mu();
			}
		}

		/**
		 * Identify the current user is set by WordPress.
		 *
		 * @since BuddyBoss 2.3.1
		 */
		public function bb_is_set_current_user() {
			global $bb_is_current_user_available;
			$bb_is_current_user_available = true;
		}

		/**
		 * Tells if the current user information is available on WordPress.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return bool
		 */
		public function bb_is_current_user_available() {
			global $bb_is_current_user_available;
			if ( isset( $bb_is_current_user_available ) ) {
				return $bb_is_current_user_available;
			}

			return false;
		}

		/**
		 * Check if user logged in or not.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return bool|int
		 */
		public function bb_get_loggedin_user_id() {

			if ( $this->bb_is_current_user_available() ) {
				return ! empty( get_current_user_id() ) ? get_current_user_id() : $this->bb_get_guessed_user_id();
			} else {
				$guessed_user_id = $this->bb_get_guessed_user_id();
				if ( ! $guessed_user_id ) {
					return 0;
				}

				return $guessed_user_id;
			}
		}

		/**
		 * Guessed user ID.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return int|boolean
		 */
		public function bb_get_guessed_user_id() {
			$guessed_user_id = apply_filters( 'bb_rest_cache_pre_current_user_id', 0 );

			return $guessed_user_id;
		}

		/**
		 * Get Pre User ID from WordPress Cookie.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @param int $user_id User ID.
		 *
		 * @return int
		 */
		public function bb_cookie_support( $user_id ) {
			$scheme = apply_filters( 'auth_redirect_scheme', '' );

			$header = $this->bb_get_all_headers();

			$cookies = '';
			if ( ! empty( $header ) ) {
				foreach ( $header as $k => $v ) {
					if ( 'cookie' === strtolower( $k ) ) {
						$cookies = $v;
						break;
					}
				}
			}

			$wp_cookie = '';

			if ( ! empty( $cookies ) ) {
				$cookies = explode( ';', trim( $cookies ) );
				if ( ! empty( $cookies ) ) {
					foreach ( $cookies as $cookie ) {
						$cookie = trim( $cookie );
						if (
							strpos( $cookie, 'wordpress_test_' ) === false &&
							strpos( $cookie, 'wordpress_' ) !== false
						) {
							$wp_cookie = $cookie;
							break;
						}
					}
				}
			}

			$cookie_elements = $this->bb_wp_parse_auth_cookie( $wp_cookie, $scheme );

			if ( $cookie_elements && isset( $cookie_elements['username'] ) ) {
				global $wpdb;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$get_user = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->users WHERE user_login=%s", $cookie_elements['username'] ) );

				if ( $get_user ) {
					return $get_user->ID;
				}
			}
		}

		/**
		 * Function to get user login details from WordPress cookie.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @see wp-includes/pluggable.php
		 *
		 * @param string $cookie Cookie value.
		 * @param string $scheme Schema.
		 *
		 * @return array|bool
		 */
		public function bb_wp_parse_auth_cookie( $cookie = '', $scheme = '' ) {
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
			} elseif ( ! empty( $cookie ) ) {
				$cookie = explode( '=', $cookie );
				$cookie = urldecode( end( $cookie ) );
			}

			$cookie_elements = explode( '|', $cookie );
			if ( 4 !== (int) count( $cookie_elements ) ) {
				return false;
			}

			list( $username, $expiration, $token, $hmac ) = $cookie_elements;

			return compact( 'username', 'expiration', 'token', 'hmac', 'scheme' );
		}

		/**
		 * Function to get user if from JWT token.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @param int $user_id User ID.
		 *
		 * @return int|void
		 */
		public function bb_jwt_auth_support( $user_id ) {

			$header = $this->bb_get_all_headers();

			$jwt_token = false;
			if ( ! empty( $header ) ) {
				foreach ( $header as $k => $v ) {
					if ( 'accesstoken' === strtolower( $k ) ) {
						$jwt_token = $v;
						break;
					}
				}
			}

			if ( $jwt_token ) {

				$token = explode( '.', $jwt_token );

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$token_pre = (array) json_decode( base64_decode( $token[0] ) );

				if (
					array(
						'typ' => 'JWT',
						'alg' => 'HS256',
					) !== $token_pre
				) {
					return $user_id;
				}

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$token = (array) json_decode( base64_decode( $token[1] ) );

				if ( ! empty( $token ) ) {
					if ( empty( $token['exp'] ) ) {
						return $user_id;
					}

					if ( $token['exp'] < time() ) {
						return $user_id;
					}
				}

				if (
					isset( $token['data'] ) &&
					isset( $token['data']->user ) &&
					isset( $token['data']->user->id )
				) {
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

			return $user_id;

		}

		/**
		 * Get Headers.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return array|false|string
		 */
		public function bb_get_all_headers() {

			if ( function_exists( 'getallheaders' ) ) {
				return getallheaders();
			}

			if ( ! is_array( $_SERVER ) ) {
				return array();
			}

			$headers = array();
			foreach ( $_SERVER as $name => $value ) {
				if ( 'HTTP_' === substr( $name, 0, 5 ) ) {
					$headers[ str_replace( ' ', '-', ucwords( strtolower( str_replace( '_', ' ', substr( $name, 5 ) ) ) ) ) ] = $value;
				}
			}

			return $headers;
		}

		/**
		 * Returns the current web location.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return string
		 */
		public function bb_get_current_path() {
			return add_query_arg( null, null );
		}

		/**
		 * Get current endpoint.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @return string|bool
		 */
		public function bb_get_current_endpoint() {
			$current_path = $this->bb_get_current_path();
			if ( false !== strpos( $current_path, 'wp-json/' ) ) {

				$current_path = explode( 'wp-json/', $current_path );
				$current_path = $current_path[1];

				// remove query vars.
				if ( false !== strpos( $current_path, '?' ) ) {
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
		 * @since BuddyBoss 2.3.1
		 */
		public function bb_prepare_presence_mu() {

			// Check if we are in WP API.
			if ( false !== strpos( $this->bb_get_current_path(), 'wp-json/buddyboss/v1/members/presence' ) ) {

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

				$user_id = $this->bb_get_loggedin_user_id() ?? 0;

				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				$ids = (array) ( isset( $_POST['ids'] ) ? $_POST['ids'] : array() );

				$this->bb_endpoint_render( $user_id, $ids );

				exit;
			}

		}

		/**
		 * Function to cache rendered response in cache.
		 *
		 * @since BuddyBoss 2.3.1
		 *
		 * @param int   $user_id User id.
		 * @param array $ids     Post user ids.
		 *
		 * @return void
		 */
		public function bb_endpoint_render( $user_id, $ids ) {
			// Added support for row format in rest API.
			if ( empty( $ids ) ) {
				$json = file_get_contents( 'php://input' );
				if ( ! empty( $json ) ) {
					$json_data = json_decode( $json, true );
					if ( ! empty( $json_data ) && ! empty( $json_data['ids'] ) ) {
						$ids = $json_data['ids'];
					}
				}
			}
			// Security Check.
			// When the cache generated to user is not matched with it's being delivered to output error.
			// Here we avoid passing another user cached instead of logged in.
			if ( empty( $user_id ) ) {
				header( 'HTTP/1.0 401 Unauthorized' );
				header( 'Content-Type: application/json' );
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Sorry, you are not allowed to perform this action.', 'buddyboss' ),
					array(
						'status' => 401,
					)
				);

				$error_data = rest_convert_error_to_response( $retval );
				echo wp_json_encode( $error_data->get_data() );
				exit;
			}

			if ( empty( $ids ) ) {
				$retval = new WP_Error(
					'rest_missing_callback_param',
					/* translators: %s: List of required parameters. */
					sprintf( __( 'Missing parameter(s): %s', 'buddyboss' ), 'ids' ),
					array(
						'status' => 400,
						'params' => array( 'ids' ),
					)
				);

				$error_data = rest_convert_error_to_response( $retval );

				header( 'Content-Type: application/json' );
				header( 'HTTP/1.0 ' . $error_data->get_status() . ' Bad Request' );
				echo wp_json_encode( $error_data->get_data() );
				exit;
			}

			$arguments = array(
				'args' => array(
					'ids' => array(
						'description'       => __( 'A unique users IDs of the member.', 'buddyboss' ),
						'type'              => 'array',
						'required'          => true,
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => 'wp_parse_id_list',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			);

			$object = new WP_REST_Request();
			$object->set_param( 'ids', $ids );
			$object->set_attributes( $arguments );

			$valid_check    = call_user_func( 'rest_validate_request_arg', $ids, $object, 'ids' );
			$invalid_params = array();

			if ( false === $valid_check ) {
				$invalid_params['ids'] = __( 'Invalid parameter.', 'buddyboss' );
			}

			if ( is_wp_error( $valid_check ) ) {
				$invalid_params['ids']  = implode( ' ', $valid_check->get_error_messages() );
				$invalid_details['ids'] = rest_convert_error_to_response( $valid_check )->get_data();
			}

			if ( $invalid_params ) {
				$retval = new WP_Error(
					'rest_invalid_param',
					/* translators: %s: List of invalid parameters. */
					sprintf( __( 'Invalid parameter(s): %s', 'buddyboss' ), implode( ', ', array_keys( $invalid_params ) ) ),
					array(
						'status'  => 400,
						'params'  => $invalid_params,
						'details' => $invalid_details,
					)
				);

				$error_data = rest_convert_error_to_response( $retval );

				header( 'Content-Type: application/json' );
				header( 'HTTP/1.0 ' . $error_data->get_status() . ' Bad Request' );
				echo wp_json_encode( $error_data->get_data() );
				exit;
			}

			$current_endpoint = $this->bb_get_current_endpoint();
			$header           = apply_filters( 'bb_rest_post_dispatch_header_cache', array(), $current_endpoint );

			// Update login users last activity.
			self::bb_update_last_activity( $user_id );

			$header['bb-presence-mu-api'] = 'hit';
			$header['Content-Type']       = 'application/json';

			if ( ! empty( $header ) ) {
				foreach ( $header as $header_key => $header_value ) {
					header( $header_key . ':' . $header_value );
				}
			}

			$presence_data = array();
			foreach ( array_unique( $ids ) as $user_id ) {
				$presence_data[] = array(
					'id'     => $user_id,
					'status' => self::bb_get_user_presence( $user_id ),
				);
			}

			echo wp_json_encode( apply_filters( 'rest_post_dispatch_cache', $presence_data, $current_endpoint ) );
			exit;
		}
	}

	BB_Presence::instance();
}
