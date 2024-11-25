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

			self::$bb_telemetry_option = bp_get_option( 'bb_advanced_telemetry_reporting', 'complete' );

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

			$raw_response = bbapp_remote_post( base64_decode( $api_url ), $args );
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
				'webserver'     => $_SERVER['SERVER_SOFTWARE'],
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
		 *
		 * @return bool True if the domain is not allowlisted, false otherwise.
		 */
		public function bb_whitelist_domain_for_telemetry() {
			$server_name = ! empty( $_SERVER['SERVER_NAME'] ) ? wp_unslash( $_SERVER['SERVER_NAME'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$whitelist_domain = array(
				'.test',
				'.dev',
				'staging.',
				'localhost',
				'.local',
				'.rapydapps.cloud',
				'ddev.site',
			);

			// Check for the test domain.
			if ( defined( 'WP_TESTS_DOMAIN' ) && WP_TESTS_DOMAIN === $server_name ) {
				return false;
			}

			// Check if the server name matches any whitelisted domain
			foreach ( $whitelist_domain as $domain ) {
				if ( false !== strpos( $server_name, $domain ) ) {
					return false; // Exclude allowlisted domains
				}
			}

			return true; // Allow telemetry data to be sent for non-allowlisted domains
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
					'bp_activity_favorites',
					'bp-enable-site-registration',
					'allow-custom-registration',
					'register-page-url',
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
					'_bb_enable_activity_post_polls',
				)
			);

			// Added those options that are not available in the option table.
			$bb_telemetry_data['bb_platform_version'] = BP_PLATFORM_VERSION;
			$bb_telemetry_data['active_integrations'] = $this->bb_active_integrations();

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

			// Fetch options from the database.
			$bp_prefix = bp_core_get_table_prefix();
			$query     = "SELECT option_name, option_value FROM {$bp_prefix}options WHERE option_name IN ('" . implode( "','", $bb_platform_db_options ) . "');";
			$results   = $wpdb->get_results( $query, ARRAY_A );

			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$bb_telemetry_data[ $result['option_name'] ] = $result['option_value'];
				}
			}

			unset( $bp_prefix, $query, $results, $bb_platform_db_options );

			return $bb_telemetry_data;
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
			if ( is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) ) {
				$options = bp_get_option( 'bp_ld_sync_settings', array() );
				if (
					! empty( $options['buddypress']['enabled'] ) ||
					! empty( $options['learndash']['enabled'] )
				) {
					$active_integrations['bp-learndash'] = true;
				}
			}
			if ( function_exists( 'bb_recaptcha_site_key' ) && ! empty( bb_recaptcha_site_key() ) ) {
				$active_integrations['bb-recaptcha'] = true;
			}

			if ( function_exists( 'bb_pro_active_integrations' ) ) {
				$active_integrations = bb_pro_active_integrations( $active_integrations );
			}

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
			$settings_url  = admin_url( 'admin.php?page=bp-settings&tab=bp-advanced' );
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
						<a href="<?php echo esc_url( $telemetry_url ); ?>" class="button button-primary">
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

			$bb_telemetry_nonce = bb_filter_input_string( INPUT_POST, 'nonce' );

			// Nonce check.
			if ( empty( $bb_telemetry_nonce ) || ! wp_verify_nonce( $bb_telemetry_nonce, 'bb-telemetry-notice-nonce' ) ) {
				wp_send_json_error( array( 'error' => __( 'Sorry, something goes wrong please try again.', 'buddyboss' ) ) );
				unset( $bb_telemetry_nonce );
			}

			bp_update_option( 'bb_telemetry_notice_dismissed', 1 );
			wp_send_json_success();
			unset( $bb_telemetry_nonce );
		}
	}
}
