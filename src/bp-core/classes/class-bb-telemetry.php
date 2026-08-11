<?php
/**
 * Telemetry class.
 *
 * @since   BuddyBoss 2.7.40
 * @package BuddyBoss\Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_Telemetry' ) ) {

	/**
	 * BuddyBoss Telemetry object.
	 *
	 * @since BuddyBoss 2.7.40
	 */
	class BB_Telemetry {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Global $wpdb object.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @var wpdb
		 */
		public static $wpdb;

		/**
		 * Telemetry Option.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @var bb_telemetry_option
		 */
		public static $bb_telemetry_option;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss 2.7.40
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
		 * @since BuddyBoss 2.7.40
		 */
		public function __construct() {
			global $wpdb;
			self::$wpdb = $wpdb;

			/**
			 * Filters the telemetry reporting mode.
			 *
			 * Pro uses this to force 'complete' mode for paid users.
			 *
			 * @since BuddyBoss 3.0.0
			 *
			 * @param string $mode Telemetry mode: 'complete', 'anonymous', or 'disable'.
			 */
			self::$bb_telemetry_option = apply_filters( 'bb_advanced_telemetry_reporting_value', bp_get_option( 'bb_advanced_telemetry_reporting', 'disable' ) );

			// Schedule the CRON event only if it's not already scheduled.
			if ( ! wp_next_scheduled( 'bb_telemetry_report_cron_event' ) ) {
				wp_schedule_event(
					strtotime( 'next Sunday midnight' ),
					'weekly',
					'bb_telemetry_report_cron_event'
				);
			}

			// Schedule the single event in next 10 minute.
			if ( ! bp_get_option( 'bb_telemetry_report_single_cron_event_scheduled', 0 ) && ! wp_next_scheduled( 'bb_telemetry_report_single_cron_event' ) ) {
				wp_schedule_single_event( time() + ( 10 * MINUTE_IN_SECONDS ), 'bb_telemetry_report_single_cron_event' );
				bp_update_option( 'bb_telemetry_report_single_cron_event_scheduled', 1 );
			}

			$this->setup_actions();
		}

		/**
		 * Setup actions for telemetry reporting.
		 *
		 * @since BuddyBoss 2.7.40
		 */
		public function setup_actions() {
			add_action( 'bb_telemetry_report_cron_event', array( $this, 'bb_send_telemetry_report_to_analytics' ) );
			add_action( 'bb_telemetry_report_single_cron_event', array( $this, 'bb_send_telemetry_report_to_analytics' ) );
			add_action( 'bb_telemetry_report_retry_event', array( $this, 'bb_send_telemetry_report_to_analytics' ) );
			add_action( 'admin_notices', array( $this, 'bb_telemetry_admin_notice' ) );
			add_action( 'wp_ajax_dismiss_bb_telemetry_notice', array( $this, 'bb_telemetry_notice_dismissed' ) );
		}

		/**
		 * Send telemetry data to the analytics site when the CRON job is triggered.
		 *
		 * @since BuddyBoss 2.7.40
		 */
		public function bb_send_telemetry_report_to_analytics() {
			if (
				'disable' === self::$bb_telemetry_option ||
				! $this->bb_whitelist_domain_for_telemetry()
			) {
				return;
			}

			$data    = $this->bb_collect_site_data();
			$api_url =
				'aHR0cHM6Ly9h' .
				'bmFseXRpY3Mu' .
				'YnVkZHlib3Nz' .
				'LmNvbS93cC1q' .
				'c29uL3dwL3Yx' .
				'L2JiLXRlbGVt' .
				'ZXRyeQ==';
			$args    = array(
				'headers'   => array(
					'Accept'       => 'application/json;ver=1.0',
					'Content-Type' => 'application/json; charset=UTF-8',
					'Site-URL'     => get_site_url(),
				),
				'timeout'   => 10,
				'blocking'  => true,
				'body'      => wp_json_encode( $data ),
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // Local requests.
			);

			$raw_response = wp_safe_remote_post( base64_decode( $api_url ), $args ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( is_wp_error( $raw_response ) ) {
				$this->bb_record_delivery_failure( $raw_response->get_error_message() );
				unset( $data, $api_url, $args );

				return $raw_response;
			}

			$response_code = wp_remote_retrieve_response_code( $raw_response );
			if ( 200 !== $response_code ) {
				$this->bb_record_delivery_failure( 'HTTP ' . $response_code . ' ' . wp_remote_retrieve_response_message( $raw_response ) );
				unset( $data, $api_url, $args );

				return new WP_Error( 'server_error', wp_remote_retrieve_response_message( $raw_response ) );
			}

			$this->bb_record_delivery_success();
			unset( $data, $api_url, $args, $raw_response );

			return true;
		}

		/**
		 * Generate or retrieve a unique UUID.
		 *
		 * @since BuddyBoss 2.7.40
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
		 * @since BuddyBoss 2.7.40
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
				'webserver'     => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				'plugins'       => $this->bb_get_plugins_data(),
				'themes'        => $this->bb_get_themes_data(),
				'is_multisite'  => is_multisite(),
			);

			if ( 'complete' === self::$bb_telemetry_option ) {
				$bb_telemetry_data['admin_email'] = get_option( 'admin_email' );
			}

			$bb_telemetry_data = $this->bb_telemetry_platform_data( $bb_telemetry_data );

			if ( function_exists( 'bb_telemetry_platform_pro_data' ) ) {
				$bb_telemetry_data = bb_telemetry_platform_pro_data( $bb_telemetry_data );
			}

			if ( function_exists( 'bb_telemetry_theme_data' ) ) {
				$bb_telemetry_data = bb_telemetry_theme_data( $bb_telemetry_data );
			}

			if ( function_exists( 'bbapp_telemetry_data' ) ) {
				$bb_telemetry_data = bbapp_telemetry_data( $bb_telemetry_data );
			}

			/*
			 * Delivery health, recorded by the send path. Built before this send
			 * runs, so `last_success` is the PREVIOUS successful delivery — on
			 * arrival it tells the receiver exactly how long the site was dark,
			 * which is what separates "cron or egress was broken" from "opted
			 * out" for any site that eventually reports again.
			 */
			$bb_telemetry_data['bb_telemetry_delivery'] = $this->bb_get_delivery_status();

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
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array List of plugins with 'name', 'slug', 'version', and 'active' keys.
		 */
		public function bb_get_plugins_data() {
			// WP-Cron loads no admin includes, so without this the weekly send
			// reported an empty plugin list with every `active` flag false on
			// sites where nothing else happened to have loaded plugin.php.
			if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

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
		 * @since BuddyBoss 2.7.40
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
		 * @since BuddyBoss 2.7.40
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
		 * Check if the domain is allowlisted for telemetry data.
		 *
		 * @since BuddyBoss 2.7.40
		 * @since BuddyBoss [BBVERSION] Reads the host from `site_url()` instead of
		 *              `$_SERVER['SERVER_NAME']` (which is empty under WP-Cron, where
		 *              telemetry actually sends from), checks a filterable domain
		 *              allowlist (`bb_telemetry_whitelist_domains`) anchored to
		 *              hostname boundaries, and delegates dev/staging detection to
		 *              `BB_DRM_Helper::is_dev_environment()`, whose matching is
		 *              anchored to whole hostname labels — a naive substring check
		 *              excluded real sites such as `nhs.devon.gov.uk` (matched `.dev`).
		 *
		 * @return bool True if the domain is not allowlisted, false otherwise.
		 */
		public function bb_whitelist_domain_for_telemetry() {
			$host = wp_parse_url( site_url(), PHP_URL_HOST );
			$host = is_string( $host ) ? strtolower( $host ) : '';

			// No host identity at all — do not report rather than fail open.
			if ( '' === $host ) {
				return false;
			}

			// Check for the test domain.
			if ( defined( 'WP_TESTS_DOMAIN' ) && WP_TESTS_DOMAIN === $host ) {
				return false;
			}

			/**
			 * Filters the domains excluded from telemetry reporting.
			 *
			 * Each entry is matched against the site host as an exact hostname
			 * or as a suffix on a label boundary — `ddev.site` also excludes
			 * `platform.ddev.site`, but never `notddev.site`. A leading dot is
			 * optional; bare TLD entries such as `test` exclude that TLD.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $whitelist_domain Hostnames or domain suffixes to exclude.
			 */
			$whitelist_domain = apply_filters(
				'bb_telemetry_whitelist_domains',
				array(
					'localhost',
					'test',
					'dev',
					'local',
					'ddev.site',
					'rapydapps.cloud',
				)
			);

			foreach ( $whitelist_domain as $domain ) {
				$domain = strtolower( ltrim( trim( (string) $domain ), '.' ) );
				if ( '' === $domain ) {
					continue;
				}

				if ( $host === $domain || substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
					return false; // Exclude allowlisted domains.
				}
			}

			// DRM's detector is the single definition of "not a real site":
			// environment type, reserved TLDs, staging keywords as whole labels,
			// host/staging provider domains, local IPs and non-standard ports.
			if ( class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Helper' ) ) {
				return ! \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::is_dev_environment();
			}

			// Fallback when DRM is unavailable: the one legacy rule the list
			// above does not express — a `staging.` host prefix.
			if ( 0 === strpos( $host, 'staging.' ) ) {
				return false;
			}

			return true; // Allow telemetry data to be sent for non-allowlisted domains.
		}

		/**
		 * Get the delivery-health record for telemetry sends.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array {
		 *     @type string $last_attempt         GMT datetime of the last send attempt, empty if never.
		 *     @type string $last_success         GMT datetime of the last successful delivery, empty if never.
		 *     @type int    $consecutive_failures Failed attempts since the last success.
		 *     @type string $last_error           Truncated message from the most recent failure.
		 * }
		 */
		public function bb_get_delivery_status() {
			$status = bp_get_option( 'bb_telemetry_delivery', array() );

			return wp_parse_args(
				is_array( $status ) ? $status : array(),
				array(
					'last_attempt'         => '',
					'last_success'         => '',
					'consecutive_failures' => 0,
					'last_error'           => '',
				)
			);
		}

		/**
		 * Record a successful telemetry delivery.
		 *
		 * Clears the failure streak and any pending retry so a stale retry
		 * cannot fire after recovery.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		protected function bb_record_delivery_success() {
			$retry_timestamp = wp_next_scheduled( 'bb_telemetry_report_retry_event' );
			if ( $retry_timestamp ) {
				wp_unschedule_event( $retry_timestamp, 'bb_telemetry_report_retry_event' );
			}

			$now = gmdate( 'Y-m-d H:i:s' );
			bp_update_option(
				'bb_telemetry_delivery',
				array(
					'last_attempt'         => $now,
					'last_success'         => $now,
					'consecutive_failures' => 0,
					'last_error'           => '',
				)
			);
		}

		/**
		 * Record a failed telemetry delivery and schedule a bounded retry.
		 *
		 * The first three consecutive failures schedule a retry 1, 4 and 12
		 * hours out. Beyond that no intra-cycle retries are scheduled — a site
		 * whose egress is permanently blocked just keeps its weekly attempt —
		 * and any recovery resets the ladder.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $error_message Why the delivery failed.
		 */
		protected function bb_record_delivery_failure( $error_message ) {
			$status                         = $this->bb_get_delivery_status();
			$status['last_attempt']         = gmdate( 'Y-m-d H:i:s' );
			$status['consecutive_failures'] = (int) $status['consecutive_failures'] + 1;
			$status['last_error']           = substr( (string) $error_message, 0, 200 );

			bp_update_option( 'bb_telemetry_delivery', $status );

			$retry_delays = array(
				1 => HOUR_IN_SECONDS,
				2 => 4 * HOUR_IN_SECONDS,
				3 => 12 * HOUR_IN_SECONDS,
			);

			if (
				isset( $retry_delays[ $status['consecutive_failures'] ] ) &&
				! wp_next_scheduled( 'bb_telemetry_report_retry_event' )
			) {
				wp_schedule_single_event( time() + $retry_delays[ $status['consecutive_failures'] ], 'bb_telemetry_report_retry_event' );
			}
		}

		/**
		 * Get the telemetry platform options.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @param array $bb_telemetry_data Telemetry options.
		 *
		 * @return array Telemetry options.
		 */
		public function bb_telemetry_platform_data( $bb_telemetry_data ) {
			global $wpdb;
			$bb_telemetry_data = ! empty( $bb_telemetry_data ) ? $bb_telemetry_data : array();

			// Include and collect report metrics.
			$report_metrics_file = __DIR__ . '/class-bb-report-metrics.php';
			if ( file_exists( $report_metrics_file ) ) {
				require_once $report_metrics_file;
				if ( class_exists( 'BB_Report_Metrics' ) ) {
					$bb_telemetry_data['bb_report_metrics'] = BB_Report_Metrics::collect();
				}
			}

			// Filterable list of BuddyBoss Platform options to fetch from the database.
			$bb_platform_db_options = apply_filters(
				'bb_telemetry_platform_options',
				array(
					'bb-active-features',
					'bb_presence_interval_mu',
					'bb_presence_time_span_mu',
					'bb_profile_slug_format',
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
					'bp-disable-group-type-creation',
					'bp-disable-account-deletion',
					'bp-enable-private-network',
					'bp_activity_favorites',
					'bp-enable-site-registration',
					'allow-custom-registration',
					'register-page-url',
					'_bp_enable_activity_autoload',
					'_bp_enable_activity_follow',
					'_bp_enable_activity_link_preview',
					'bp_media_profiles_emoji_support',
					'bp_media_groups_emoji_support',
					'bp_media_messages_emoji_support',
					'bp_media_forums_emoji_support',
					'bp_media_profiles_gif_support',
					'bp_media_groups_gif_support',
					'bp_media_messages_gif_support',
					'bp_media_forums_gif_support',
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
					'bp_media_symlink_direct_access',
					'bp_video_extensions_support',
					'_bp_on_screen_notifications_enable',
					'_bp_on_screen_notifications_position',
					'_bp_on_screen_notifications_mobile_support',
					'_bp_on_screen_notifications_visibility',
					'_bp_on_screen_notifications_browser_tab',
					'_bp_db_version',
					'bb_pinned_post',
					'bp_document_extensions_support',
					'bp_media_profile_document_support',
					'bp_media_group_document_support',
					'bp_media_messages_document_support',
					'bp_media_forums_document_support',
					'bp_document_allowed_size',
					'bp_media_allowed_size',
					'_bb_enable_activity_post_polls',
					'bb-enable-content-counts',
					'bp-profile-avatar-type',
					'bp-default-profile-avatar-type',
					'bp-enable-profile-gravatar',
					'bp-disable-cover-image-uploads',
					'bp-default-profile-cover-type',
					'bp-disable-group-avatar-uploads',
					'bp-default-group-avatar-type',
					'bp-disable-group-cover-image-uploads',
					'bp-default-group-cover-type',
					'bb_activity_filter_options',
					'bb_activity_timeline_filter_options',
					'bb_activity_sorting_options',
					'bb_enable_activity_search',
					'bb_enable_activity_topics',
					'bb_activity_topic_required',
				)
			);

			// Added those options that are not available in the option table.
			$bb_telemetry_data['bb_platform_version'] = BP_PLATFORM_VERSION;
			$bb_telemetry_data['active_integrations'] = $this->bb_active_integrations();
			$bb_telemetry_data['bb_license']          = $this->bb_get_license_data();
			$bb_telemetry_data['bb_drm']              = $this->bb_get_drm_data();

			if (
				function_exists( 'bb_topics_manager_instance' ) &&
				function_exists( 'bb_is_enabled_activity_topics' ) &&
				bb_is_enabled_activity_topics()
			) {
				$global_activity_topics_count = bb_topics_manager_instance()->bb_get_topics(
					array(
						'item_type'   => 'activity',
						'item_id'     => 0,
						'count_total' => true,
						'per_page'    => 1,
					)
				);
				if ( isset( $global_activity_topics_count['total'] ) ) {
					$bb_telemetry_data['bb_topic_count'] = $global_activity_topics_count['total'];
				}
			}

			// Pass active or inactive components.
			$components          = bp_core_get_components();
			$active_components   = bp_get_option( 'bp-active-components' );
			$inactive_components = array_diff( array_keys( $components ), array_keys( $active_components ) );
			if ( ! empty( $inactive_components ) ) {
				foreach ( $inactive_components as $component ) {
					$active_components[ $component ] = 0;
				}
			}
			$bb_telemetry_data['bp-active-components'] = $active_components;

			// Fetch options from the database using parameterized query.
			$bp_prefix    = bp_core_get_table_prefix();
			$sanitized    = array_map( 'sanitize_key', $bb_platform_db_options );
			$placeholders = implode( ', ', array_fill( 0, count( $sanitized ), '%s' ) );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table prefix is safe; placeholders are parameterized.
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$bp_prefix}options WHERE option_name IN ({$placeholders})", $sanitized ), ARRAY_A );

			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$bb_telemetry_data[ $result['option_name'] ] = $result['option_value'];
				}
			}

			/*
			 * Declare which option names were actually queried.
			 *
			 * The receiver only ever wrote keys present in a payload, so a value
			 * that disappeared from wp_options stayed on the record forever —
			 * every stored boolean was really "last known", not "current". It
			 * cannot simply delete anything missing, because a site on an older
			 * build never sends the newer keys and would have them wiped.
			 *
			 * This list resolves that: a name in it that is absent from the
			 * payload was looked for and genuinely not found, so the receiver may
			 * clear it. Anything not declared is left untouched.
			 *
			 * This method runs first and starts the list; the Pro, Theme and App
			 * collectors run after it in bb_collect_site_data() and append their
			 * own names to the same key.
			 */
			$bb_telemetry_data['bb_reported_option_keys'] = array_values( array_unique( $sanitized ) );

			unset( $bp_prefix, $query, $results, $bb_platform_db_options );

			// Tools usage → cumulative per-action counts for Repair / Sample Data / Migration.
			$bb_telemetry_data['tools_usage'] = bb_get_tool_usage();

			/**
			 * Filters the telemetry platform data.
			 *
			 * @since BuddyBoss 2.15.2
			 *
			 * @param array $bb_telemetry_data Telemetry platform data.
			 *
			 * @return array Telemetry platform data.
			 */
			return apply_filters( 'bb_telemetry_platform_data', $bb_telemetry_data );
		}

		/**
		 * Collect licence and plan data for the Platform and Theme.
		 *
		 * The Mothership plugin id doubles as the plan identifier — values like
		 * `bb-platform-pro-5-sites` or `bb-web-plus` encode both product and seat
		 * count — and it is a plain option, so it is always available without a
		 * network call. Richer plan detail (product name, activation counts) comes
		 * from the locally cached licence-details transient when present.
		 *
		 * The raw licence key is only included in `complete` mode. In `anonymous`
		 * mode the plan and status are still reported, but nothing that ties the
		 * payload back to a customer record — which is what that mode promises.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Licence data for the Platform and, when present, the Theme.
		 */
		public function bb_get_license_data() {
			$include_key = ( 'complete' === self::$bb_telemetry_option );

			$platform_id = get_option( 'buddyboss_dynamic_plugin_id', defined( 'PLATFORM_EDITION' ) ? PLATFORM_EDITION : '' );
			$theme_id    = get_option( 'buddyboss_theme_dynamic_id', defined( 'THEME_EDITION' ) ? THEME_EDITION : '' );

			/*
			 * Read the key the same way the licence screen does. Credentials also
			 * honours the BUDDYBOSS_LICENSE_KEY constant and environment variable,
			 * which a plain option read would miss, so a site configured that way
			 * would otherwise report as unlicensed.
			 */
			$platform_key = '';
			if ( class_exists( '\BuddyBossPlatform\GroundLevel\Mothership\Credentials' ) ) {
				try {
					$platform_key = (string) \BuddyBossPlatform\GroundLevel\Mothership\Credentials::getLicenseKey();
				} catch ( \Throwable $e ) {
					$platform_key = '';
				}
			}

			if ( '' === $platform_key && ! empty( $platform_id ) ) {
				$platform_key = (string) get_option( $platform_id . '_license_key', '' );
			}

			$data = array(
				'platform' => $this->bb_get_product_license_data( $platform_id, $platform_key, $include_key ),
			);

			if ( $this->bb_theme_uses_platform_license( $platform_id, $platform_key ) ) {
				/*
				 * The plan already includes the theme, so there is no separate
				 * theme licence to report — the platform one covers it. Reported
				 * without repeating the key, which is already sent above.
				 */
				$data['theme']           = $this->bb_get_product_license_data( $platform_id, $platform_key, false );
				$data['theme']['source'] = 'platform';
			} elseif ( ! empty( $theme_id ) ) {
				$theme_key               = (string) get_option( $theme_id . '_license_key', '' );
				$data['theme']           = $this->bb_get_product_license_data( $theme_id, $theme_key, $include_key );
				$data['theme']['source'] = 'theme';
			}

			/**
			 * Filters the licence data reported by telemetry.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $data        Licence data.
			 * @param bool  $include_key Whether the raw licence key is included.
			 */
			return apply_filters( 'bb_telemetry_license_data', $data, $include_key );
		}

		/**
		 * Whether the theme is covered by the platform licence.
		 *
		 * True when the platform licence is present and activated, the BuddyBoss
		 * Theme is the one in use, and the plan's add-ons list includes the theme
		 * product. In that case the theme has no licence of its own to report and
		 * the platform entitlement is what applies.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $plugin_id    Platform plan identifier.
		 * @param string $license_key  Platform licence key.
		 *
		 * @return bool
		 */
		protected function bb_theme_uses_platform_license( $plugin_id, $license_key ) {
			// The platform licence has to exist and be activated.
			if ( empty( $plugin_id ) || '' === (string) $license_key || empty( get_option( $plugin_id . '_license_activation_status', false ) ) ) {
				return false;
			}

			// get_template() returns the parent, so child themes count too.
			if ( 'buddyboss-theme' !== get_template() ) {
				return false;
			}

			// The vendor Response class must be loadable before unserializing the
			// cached API response below.
			if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
				return false;
			}

			/*
			 * Cache-only, deliberately. BB_Addons_Manager::checkProductBySlug()
			 * fetches from the Mothership API on a cold cache, and this runs on
			 * the telemetry send path — cron and the synchronous wizard sends —
			 * where no network call may happen. A cold or errored cache reports
			 * the theme's own licence state instead, which self-corrects on the
			 * next send after an admin visit warms the add-ons cache.
			 */
			try {
				$cached = get_transient( $plugin_id . '_add_ons' );

				if ( empty( $cached ) || empty( $cached->products ) || ! is_iterable( $cached->products ) ) {
					return false;
				}

				foreach ( $cached->products as $product ) {
					if (
						! empty( $product->slug ) &&
						false !== strpos( $product->slug, 'buddyboss-theme' ) &&
						! empty( $product->status ) &&
						'enabled' === $product->status
					) {
						return true;
					}
				}
			} catch ( \Throwable $e ) {
				return false;
			}

			return false;
		}

		/**
		 * Build the licence payload for a single Mothership product id.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $plugin_id   Mothership plugin id, which is also the plan.
		 * @param string $license_key Licence key already resolved for this plan.
		 * @param bool   $include_key Whether to include the raw licence key.
		 *
		 * @return array Licence data, or an empty array when there is no plan id.
		 */
		protected function bb_get_product_license_data( $plugin_id, $license_key, $include_key ) {
			if ( empty( $plugin_id ) ) {
				return array();
			}

			$license_key = (string) $license_key;

			$data = array(
				'plan'      => $plugin_id,
				'has_key'   => ( '' !== $license_key ),
				'is_active' => (bool) get_option( $plugin_id . '_license_activation_status', false ),
			);

			if ( $include_key && '' !== $license_key ) {
				$data['license_key'] = $license_key;
			}

			// Cached plan detail, populated when the licence screen was last loaded.
			$details = get_transient( $plugin_id . '_license_details' );
			if ( is_array( $details ) ) {
				$data['product']             = isset( $details['product'] ) ? $details['product'] : '';
				$data['status']              = isset( $details['status'] ) ? $details['status'] : '';
				$data['activations_allowed'] = isset( $details['total_prod_allowed'] ) ? (int) $details['total_prod_allowed'] : 0;
				$data['activations_used']    = isset( $details['total_prod_used'] ) ? (int) $details['total_prod_used'] : 0;
			}

			return $data;
		}

		/**
		 * Collect the DRM escalation stage for telemetry.
		 *
		 * A site without a valid licence moves through a fixed ladder, counted from
		 * the DRM event's creation date. Both the no-key and invalid-key scenarios
		 * use the same boundaries:
		 *
		 *   0-6 days   grace   — nothing shown
		 *   7-13 days  low     — in-plugin notification
		 *   14-21 days medium  — notification + admin notice + Site Health
		 *   22-30 days high    — the above + admin email
		 *   31+ days   locked  — features disabled
		 *
		 * Reporting the stage is what makes the ladder measurable: how many sites
		 * sit at each rung, and how many reach lockout rather than licensing.
		 *
		 * Derived from local state only — two indexed lookups on the DRM event
		 * table plus two option reads. Deliberately avoids
		 * `BB_DRM_Registry::get_addons_by_drm_status()`, which calls
		 * `is_addon_licensed()` and can reach the Mothership API.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array DRM scenario, stage and elapsed days.
		 */
		public function bb_get_drm_data() {
			$data = array(
				'scenario' => 'none',
				'stage'    => '',
				'days'     => 0,
				'addons'   => array(),
			);

			if (
				! class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Event' ) ||
				! class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Helper' )
			) {
				return $data;
			}

			// Per-plugin DRM state for every paid add-on registered on this site.
			$data['addons'] = $this->bb_get_drm_addons_data();

			// Platform's own scenario, if any.
			if ( get_option( 'bb_drm_no_license', false ) ) {
				$data['scenario'] = \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::NO_LICENSE_EVENT;
			} elseif ( get_option( 'bb_drm_invalid_license', false ) ) {
				$data['scenario'] = \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::INVALID_LICENSE_EVENT;
			} else {
				return $data;
			}

			$data = array_merge( $data, $this->bb_get_drm_stage( $data['scenario'] ) );

			/**
			 * Filters the DRM data reported by telemetry.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $data DRM scenario, stage and elapsed days.
			 */
			return apply_filters( 'bb_telemetry_drm_data', $data );
		}

		/**
		 * Resolve the DRM escalation stage for a single DRM event.
		 *
		 * Mirrors the boundaries in `BB_DRM_NoKey::run()`, `BB_DRM_Invalid::run()`
		 * and `BB_DRM_Addon::run()`, which are identical.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $event_name DRM event name, e.g. `no-license` or `addon-{slug}`.
		 *
		 * @return array Stage, elapsed days and the event start date.
		 */
		protected function bb_get_drm_stage( $event_name ) {
			try {
				$event = \BuddyBoss\Core\Admin\DRM\BB_DRM_Event::latest( $event_name );
			} catch ( \Throwable $e ) {
				$event = null;
			}

			return $this->bb_get_drm_stage_from_event( $event );
		}

		/**
		 * Resolve the DRM escalation stage from an already-fetched DRM event.
		 *
		 * Split from bb_get_drm_stage() so the add-on collector can resolve many
		 * stages from one batched query instead of one query per add-on.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param object|null $event Latest DRM event, or null when there is none.
		 *
		 * @return array Stage, elapsed days and the event start date.
		 */
		protected function bb_get_drm_stage_from_event( $event ) {
			$stage = array(
				'stage' => '',
				'days'  => 0,
			);

			if ( ! $event || empty( $event->created_at ) ) {
				return $stage;
			}

			$days             = (int) \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::days_elapsed( $event->created_at );
			$stage['days']    = $days;
			$stage['started'] = $event->created_at;

			if ( $days >= 31 ) {
				$stage['stage'] = \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::DRM_LOCKED;
			} elseif ( $days >= 22 ) {
				$stage['stage'] = \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::DRM_HIGH;
			} elseif ( $days >= 14 ) {
				$stage['stage'] = \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::DRM_MEDIUM;
			} elseif ( $days >= 7 ) {
				$stage['stage'] = \BuddyBoss\Core\Admin\DRM\BB_DRM_Helper::DRM_LOW;
			} else {
				$stage['stage'] = 'grace';
			}

			return $stage;
		}

		/**
		 * Collect per-plugin DRM state for every registered paid add-on.
		 *
		 * Add-ons register with the DRM registry from their own bootstrap, so the
		 * registry is effectively the list of paid BuddyBoss plugins active on this
		 * site. Every one is reported, including add-ons with no DRM event — a
		 * healthy add-on reports an empty stage, which is what makes "how many
		 * sites run this add-on licensed vs lapsed" answerable.
		 *
		 * Add-on DRM events are named `addon-{sanitized product slug}` and use the
		 * same escalation ladder as the Platform.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Map of product slug => name, version, stage, days, started.
		 */
		protected function bb_get_drm_addons_data() {
			if ( ! class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry' ) ) {
				return array();
			}

			$registered = \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::get_registered_addons();

			if ( empty( $registered ) || ! is_array( $registered ) ) {
				return array();
			}

			$event_names = array();
			foreach ( $registered as $product_slug => $addon ) {
				$event_names[ $product_slug ] = 'addon-' . sanitize_key( $product_slug );
			}

			// One grouped query for every add-on's latest event, not one each.
			$latest_events = $this->bb_get_latest_drm_events( array_values( $event_names ) );

			$addons = array();
			foreach ( $registered as $product_slug => $addon ) {
				$entry = array(
					'name'    => isset( $addon['plugin_name'] ) ? $addon['plugin_name'] : $product_slug,
					'version' => isset( $addon['args']['version'] ) ? $addon['args']['version'] : '',
				);

				$event_name = $event_names[ $product_slug ];

				$addons[ $product_slug ] = array_merge(
					$entry,
					$this->bb_get_drm_stage_from_event( isset( $latest_events[ $event_name ] ) ? $latest_events[ $event_name ] : null )
				);
			}

			return $addons;
		}

		/**
		 * Fetch the latest DRM event for each given event name in one query.
		 *
		 * Replaces one BB_DRM_Event::latest() round trip per registered add-on
		 * with a single grouped lookup.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $event_names DRM event names.
		 *
		 * @return array Map of event name => latest event row.
		 */
		protected function bb_get_latest_drm_events( $event_names ) {
			if ( empty( $event_names ) ) {
				return array();
			}

			try {
				$table        = \BuddyBoss\Core\Admin\DRM\BB_DRM_Event::get_table_name();
				$placeholders = implode( ', ', array_fill( 0, count( $event_names ), '%s' ) );

				$events_sql = "SELECT t.* FROM {$table} t
					INNER JOIN (
						SELECT event, MAX( id ) AS id
						FROM {$table}
						WHERE event IN ( {$placeholders} )
						GROUP BY event
					) latest ON latest.id = t.id";

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name from the DRM class; values parameterized.
				$rows = self::$wpdb->get_results( self::$wpdb->prepare( $events_sql, $event_names ) );
			} catch ( \Throwable $e ) {
				return array();
			}

			$events = array();
			foreach ( (array) $rows as $row ) {
				if ( isset( $row->event ) ) {
					$events[ $row->event ] = $row;
				}
			}

			return $events;
		}

		/**
		 * Get the status of integrations.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @return array list of integrations status.
		 */
		public function bb_active_integrations() {

			$active_integrations = array(
				'bp-learndash' => false,
				'bb-recaptcha' => false,
			);

			if ( function_exists( 'bb_recaptcha_site_key' ) && ! empty( bb_recaptcha_site_key() ) ) {
				$active_integrations['bb-recaptcha'] = true;
			}

			// Legacy Pro integration function — runs before the filter for
			// backwards compat with existing Pro versions. Pro can migrate
			// to the filter at its own pace.
			if ( function_exists( 'bb_pro_active_integrations' ) ) {
				$active_integrations = bb_pro_active_integrations( $active_integrations );
			}

			/**
			 * Filter the telemetry active-integrations map. Addons set their
			 * own status here instead of Platform knowing how to detect them.
			 *
			 * The pre-3.0.0 inline LearnDash detection
			 * (sfwd-lms + bp_ld_sync_settings) moved to buddyboss-learndash
			 * as a subscriber to this filter.
			 *
			 * @since BuddyBoss 3.0.0
			 *
			 * @param array $active_integrations Map of integration_id => bool.
			 */
			$active_integrations = apply_filters( 'bb_telemetry_active_integrations', $active_integrations );

			return $active_integrations;
		}

		/**
		 * Telemetry notice.
		 *
		 * @since BuddyBoss 2.7.40
		 */
		public function bb_telemetry_admin_notice() {

			// Check if the notice has already been dismissed.
			if ( bp_get_option( 'bb_telemetry_notice_dismissed', 0 ) ) {
				return; // Do not display the notice if it's been dismissed.
			}
			// URL for the telemetry settings page.
			$settings_url  = admin_url( 'admin.php?page=bb-settings&tab=advanced&panel=telemetry' );
			$telemetry_url = 'https://www.buddyboss.com/usage-tracking/?utm_source=product&utm_medium=platform&utm_campaign=telemetry';
			?>
			<div class="notice notice-info is-dismissible bb-telemetry-notice" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bb-telemetry-notice-nonce' ) ); ?>">
				<div class="bb-telemetry-notice_logo"><i class="bb-icon-brand-buddyboss bb-icon-rf"></i></div>
				<div class="bb-telemetry-notice_content">
					<p class="bb-telemetry-notice_heading">
						<strong><?php esc_html_e( 'Help us improve BuddyBoss', 'buddyboss' ); ?></strong>
					</p>
					<p>
						<?php
						// Message with link to telemetry settings.
						printf(
							wp_kses(
							/* translators: %1$s and %2$s are links. */
								__( 'We gather statistics about how our users use the product. We aggregate this information to help us improve the product and provide you with a better service. If you\'re happy with that you can dismiss this message, otherwise you can <a href="%1$s">adjust your telemetry settings</a>. To read more about what statistics we collect and why, click below.', 'buddyboss' ),
								array(
									'a' => array(
										'href' => array(),
									),
								)
							),
							esc_url( $settings_url )
						);
						?>
					</p>
					<p>
						<a href="<?php echo esc_url( $telemetry_url ); ?>" class="button button-primary" target="_blank" >
							<?php esc_html_e( 'About Telemetry', 'buddyboss' ); ?>
						</a>
					</p>
				</div>
			</div>
			<?php
		}

		/**
		 * Store the dismissal of the notice.
		 *
		 * @since BuddyBoss 2.7.40
		 */
		public function bb_telemetry_notice_dismissed() {

			// Capability check before nonce — cheaper, avoids consuming nonce for unauthorized users.
			// Use manage_options to match the audience that sees the admin notice
			// (admin_notices fires for any admin viewer; manage_options matches
			// the network-admin-friendly subset that can dismiss it).
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'error' => __( 'You do not have permission to perform this action.', 'buddyboss' ) ) );
			}

			$bb_telemetry_nonce = bb_filter_input_string( INPUT_POST, 'nonce' );

			// Nonce check.
			if ( empty( $bb_telemetry_nonce ) || ! wp_verify_nonce( $bb_telemetry_nonce, 'bb-telemetry-notice-nonce' ) ) {
				wp_send_json_error( array( 'error' => __( 'Sorry, something goes wrong please try again.', 'buddyboss' ) ) );
			}

			bp_update_option( 'bb_telemetry_notice_dismissed', 1 );
			wp_send_json_success();
		}
	}
}
