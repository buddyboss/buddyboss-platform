<?php
/**
 * Telemetry class.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Telemetry' ) ) {

	/**
	 * BuddyBoss Telemetry object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_Telemetry {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Global $wpdb object.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var wpdb
		 */
		public static $wpdb;

		/**
		 * Telemetry Option.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var bb_telemetry_option
		 */
		public static $bb_telemetry_option;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return BB_Telemetry|null
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
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
			self::$wpdb = $wpdb;

			self::$bb_telemetry_option = bp_get_option( 'bb_advanced_telemetry_reporting', 'complete' );

			// Schedule the CRON event only if it's not already scheduled.
			if ( ! wp_next_scheduled( 'bb_telemetry_report_cron_event' ) ) {
				wp_schedule_event(
					strtotime( 'next Sunday midnight' ),
					'weekly',
					'bb_telemetry_report_cron_event'
				);
			}

			$this->setup_actions();
		}

		/**
		 * Setup actions for telemetry reporting.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function setup_actions() {
			add_action( 'bb_telemetry_report_cron_event', array( $this, 'bb_send_telemetry_report_to_analytics' ) );
		}

		/**
		 * Send telemetry data to the analytics site when the CRON job is triggered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_send_telemetry_report_to_analytics() {
			if ( 'disable' === self::$bb_telemetry_option ) {

				return;
			}

			$data     = $this->bb_collect_site_data();
			$auth_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOjEsIm5hbWUiOiJXZWIgTmluamEiLCJpYXQiOjE3MjkwNjM5NjMsImV4cCI6MTg4Njc0Mzk2M30.6Yy2KQqwEHQEOXmtK9SvCjzjUh2Ie62_2qw4A5ATq2I'; // @todo: update when release.
			if ( defined( 'BB_TEST_ANALYTICS_AUTH' ) ) { // @todo: update when release.
				$auth_key = BB_TEST_ANALYTICS_AUTH;
			}
			$api_url = 'https://analytics.buddyboss.com/wp-json/wp/v1/bb-telemetry';
			if ( defined( 'BB_TEST_ANALYTICS_URL' ) ) { // @todo: update when release.
				$api_url = BB_TEST_ANALYTICS_URL . '/wp-json/wp/v1/bb-telemetry';
			}
			$args = array(
				'headers'   => array(
					'Authorization' => 'Bearer ' . $auth_key,
					'Accept'        => 'application/json;ver=1.0',
					'Content-Type'  => 'application/json; charset=UTF-8',
					'Site-URL'      => get_site_url(),
				),
				'timeout'   => 10,
				'blocking'  => true,
				'body'      => wp_json_encode( $data ),
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // Local requests.
			);

			$raw_response = wp_remote_post( $api_url, $args );

			if ( ! empty( $raw_response ) && is_wp_error( $raw_response ) ) {
				unset( $data, $auth_key, $api_url, $args );

				return $raw_response;
			} elseif ( ! empty( $raw_response ) && 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
				unset( $data, $auth_key, $api_url, $args );

				return new WP_Error( 'server_error', wp_remote_retrieve_response_message( $raw_response ) );
			} else {
				unset( $data, $auth_key, $api_url, $args, $raw_response );

				return new WP_Error( 'server_error', __( 'An error occurred while sending the telemetry report.', 'buddyboss' ) );
			}
		}

		/**
		 * Generate or retrieve a unique UUID.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string The unique UUID.
		 */
		public function bb_uuid() {
			$uuid_key = 'bb-telemetry-uuid';
			$uuid     = bp_get_option( $uuid_key );

			if ( empty( $uuid ) ) {
				$uuid = md5( uniqid() . site_url() );
				bp_update_option( $uuid_key, $uuid );
			}

			unset( $uuid_key );

			return $uuid;
		}

		/**
		 * Collect site data for telemetry reporting.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array An array of collected site data.
		 */
		public function bb_collect_site_data() {
			$bb_telemetry_data = array(
				'site_url'      => site_url(),
				'admin_url'     => admin_url(),
				'wp_version'    => get_bloginfo( 'version' ),
				'php_version'   => phpversion(),
				'mysql_version' => self::$wpdb->db_version(),
				'db_provider'   => self::$wpdb->dbhost,
				'os'            => php_uname( 's' ),
				'webserver'     => $_SERVER['SERVER_SOFTWARE'],
				'plugins'       => $this->bb_get_plugins_data(),
				'themes'        => $this->bb_get_themes_data(),
				'is_multisite'  => is_multisite(),
			);

			if ( 'complete' === self::$bb_telemetry_option ) {
				$bb_telemetry_data['admin_email'] = get_option( 'admin_email' );
			}

			if ( function_exists( 'bb_telemetry_platform_data' ) ) {
				$bb_telemetry_data = bb_telemetry_platform_data( $bb_telemetry_data );
			}

			if ( function_exists( 'bb_telemetry_platform_pro_data' ) ) {
				$bb_telemetry_data = bb_telemetry_platform_pro_data( $bb_telemetry_data );
			}

			if ( function_exists( 'bb_telemetry_theme_data' ) ) {
				$bb_telemetry_data = bb_telemetry_theme_data( $bb_telemetry_data );
			}

			if ( function_exists( 'bbapp_telemetry_data' ) ) {
				$bb_telemetry_data = bbapp_telemetry_data( $bb_telemetry_data );
			}

			$result = array(
				'uuid' => $this->bb_uuid(),
				'data' => $bb_telemetry_data,
			);

			unset( $bb_telemetry_data );

			return $result;
		}

		/**
		 * Retrieves the list of installed plugins along with their name, slug, version, and activation status.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array List of plugins with 'name', 'slug', 'version', and 'active' keys.
		 */
		public function bb_get_plugins_data() {
			$plugin_list = function_exists( 'get_plugins' ) ? get_plugins() : array();
			wp_cache_delete( 'plugins', 'plugins' );

			$plugins = array();
			if ( ! empty( $plugin_list ) ) {
				foreach ( $plugin_list as $slug => $info ) {
					$plugins[] = array(
						'name'    => $info['Name'],
						'slug'    => $slug,
						'version' => $info['Version'],
						'active'  => function_exists( 'is_plugin_active' ) && is_plugin_active( $slug ),
					);
				}
			}

			unset( $plugin_list );

			return $plugins;
		}

		/**
		 * Retrieves data for the active theme, including the parent theme if a child theme is active.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array List of themes with 'name', 'stylesheet', 'version', and 'template' keys.
		 */
		public function bb_get_themes_data() {
			$theme  = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
			$themes = $theme ? $this->get_theme_data( $theme ) : array();

			// Check if the active theme is a child theme and retrieve the parent theme data.
			if ( function_exists( 'is_child_theme' ) && is_child_theme() && $theme ) {
				$themes[] = $this->get_theme_data( wp_get_theme( $theme->get( 'Template' ) ) );
			}

			unset( $theme );

			return $themes;
		}

		/**
		 * Retrieves specific data from the provided theme object.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param WP_Theme $theme Theme object.
		 *
		 * @return array Array containing the 'name', 'stylesheet', 'version', and 'template' of the theme.
		 */
		public function get_theme_data( $theme ) {
			if ( ! $theme ) {
				return array();
			}

			return array(
				'name'       => $theme->get( 'Name' ),
				'stylesheet' => $theme->get_stylesheet(),
				'version'    => $theme->get( 'Version' ),
				'template'   => $theme->get( 'Template' ),
			);
		}
	}
}
