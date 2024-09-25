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
			$data     = $this->bb_collect_site_data();
			$auth_key = '';
			if ( defined( 'BB_TEST_ANALYTICS_AUTH' ) ) {
				$auth_key = BB_TEST_ANALYTICS_AUTH;
			}
			$api_url = 'https://analytics.buddyboss.com/wp-json/wp/v2/bb_analytics';
			if ( defined( 'BB_TEST_ANALYTICS_URL' ) ) {
				$api_url = BB_TEST_ANALYTICS_URL . '/wp-json/wp/v2/bb_analytics';
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
				'admin_email'   => get_option( 'admin_email' ),
				'php_version'   => phpversion(),
				'mysql_version' => self::$wpdb->db_version(),
				'db_provider'   => self::$wpdb->db_server_info(),
				'os'            => php_uname( 's' ),
				'webserver'     => $_SERVER['SERVER_SOFTWARE'],
				'plugins'       => $this->bb_get_plugins_data(),
				'themes'        => $this->bb_get_themes_data(),
				'is_multisite'  => is_multisite(),
			);

			$platform_options = self::bb_telemetry_options();
			if ( ! empty( $platform_options ) ) {
				$bb_telemetry_data = array_merge( $bb_telemetry_data, $platform_options );
			}

			unset( $platform_options );

			/**
			 * Allow plugins or themes to modify the telemetry data.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $bb_telemetry_data The collected telemetry data.
			 */
			$bb_telemetry_data = apply_filters( 'bb_telemetry_data', $bb_telemetry_data );

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

		/**
		 * Get the telemetry options.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Telemetry options.
		 */
		public static function bb_telemetry_options() {
			$bb_telemetry_data = array();

			// Filterable list of BuddyBoss Platform options to fetch from the database.
			$bb_platform_db_options = apply_filters(
				'bb_telemetry_platform_options',
				array(
					'bb_presence_interval_mu',
					'bb_presence_time_span_mu',
					'bb_profile_slug_format',
					'_bp_community_visibility',
					'bb_reaction_mode',
					'_bb_enable_activity_schedule_posts',
					'_bb_enable_activity_comment_threading',
					'_bb_activity_comment_threading_depth',
					'_bb_enable_activity_comments',
					'_bb_activity_comment_visibility',
					'_bb_activity_comment_loading',
					'bb_activity_load_type',
					'bb_ajax_request_page_load',
					'bb_load_activity_per_request',
					'_bp_enable_activity_like',
					'_bb_enable_activity_pinned_posts',
					'_bbp_db_version',
					'bp-display-name-format',
					'bp-member-type-enable-disable',
					'bp-member-type-display-on-profile',
					'bp-disable-avatar-uploads',
					'bp-disable-cover-image-uploads',
					'bp-disable-group-avatar-uploads',
					'bp-disable-group-cover-image-uploads',
					'bp-disable-group-type-creation',
					'bp-disable-account-deletion',
					'bp-enable-private-network',
					'bp-active-components',
					'bp_activity_favorites',
					'bp-enable-site-registration',
					'_bp_enable_activity_autoload',
					'_bp_enable_activity_follow',
					'_bp_enable_activity_link_preview',
					'_bp_enable_activity_emoji',
					'_bp_enable_activity_gif',
					'bp_search_members',
					'bp_search_number_of_results',
					'bp_media_profile_media_support',
					'bp_media_profile_albums_support',
					'bp_media_group_media_support',
					'bp_media_group_albums',
					'bp_media_forums_media_support',
					'bp_media_messages_media_support',
					'_bbp_enable_favorites',
					'_bbp_enable_subscriptions',
					'_bbp_allow_topic_tags',
					'_bbp_thread_replies_depth',
					'_bbp_forums_per_page',
					'_bbp_topics_per_page',
					'_bbp_replies_per_page',
					'_bbp_enable_group_forums',
					'bp-disable-group-messages',
					'bp_media_symlink_support',
					'_bbp_pro_db_version',
					'_bp_enable_activity_edit',
					'bp_media_allowed_per_batch',
					'bp_document_allowed_per_batch',
					'bp_video_profile_video_support',
					'bp_video_group_video_support',
					'bp_video_messages_video_support',
					'bp_video_forums_video_support',
					'bp_video_allowed_size',
					'bp_video_allowed_per_batch',
					'bp_video_extension_video_support',
					'bp_media_symlink_direct_access',
					'bp_video_extensions_support',
					'_bp_on_screen_notifications_enable',
					'_bp_on_screen_notification_position',
					'_bp_on_screen_notification_mobile_support',
					'_bp_on_screen_notification_visibility',
					'_bp_on_screen_notification_browser_tab',
					'_bp_db_version',
					'bb_pinned_post',
					'bp_document_extensions_support',
					'bp_media_profile_document_support',
					'bp_media_group_document_support',
					'bp_media_messages_document_support',
					'bp_media_forums_document_support',
					'bp_media_extension_document_support',
					'bp_document_allowed_size',
					'bp_media_allowed_size',
				)
			);

			// Filterable list of BuddyBoss Platform Pro options to fetch from the database if the pro is active.
			if ( function_exists( 'bb_platform_pro' ) ) {
				$bb_pro_db_options = apply_filters(
					'bb_telemetry_pro_options',
					array(
						'bb-pusher-enabled',
						'bp-force-friendship-to-message',
						'bb-access-control-send-message',
						'bb-access-control-friends',
						'bb-access-control-upload-media',
						'bb-access-control-upload-document',
						'bp-zoom-enable',
						'bp-zoom-enable-groups',
						'bp-zoom-enable-recordings',
						'bb-access-control-create-activity',
						'bb-access-control-upload-video',
						'bb-onesignal-enabled-web-push',
						'bb-onesignal-enable-soft-prompt',
					)
				);

				$bb_platform_db_options = array_merge( $bb_platform_db_options, $bb_pro_db_options );

				// Added those options that are not available in the option table.
				$bb_telemetry_data['bb_platform_pro_version'] = bb_platform_pro()->version;

				unset( $bb_pro_db_options );
			}

			// Added those options that are not available in the option table.
			$bb_telemetry_data['bb_platform_version'] = BP_PLATFORM_VERSION;
			$bb_telemetry_data['active_integrations'] = '';

			// Fetch options from the database.
			$bp_prefix = bp_core_get_table_prefix();
			$query     = "SELECT option_name, option_value FROM {$bp_prefix}options WHERE option_name IN ('" . implode( "','", $bb_platform_db_options ) . "');";
			$results   = self::$wpdb->get_results( $query, ARRAY_A );

			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$bb_telemetry_data[ $result['option_name'] ] = $result['option_value'];
				}
			}

			unset( $bp_prefix, $query, $results, $bb_platform_db_options );

			// Merge theme options if the theme is active.
			if ( class_exists( 'BB_Theme_Telemetry' ) ) {
				$bb_telemetry_data = array_merge( $bb_telemetry_data, BB_Theme_Telemetry::bb_telemetry_theme_options() );
			}

			return $bb_telemetry_data;
		}
	}
}
