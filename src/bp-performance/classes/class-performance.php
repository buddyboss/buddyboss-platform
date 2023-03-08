<?php
/**
 * BuddyBoss Performance.
 *
 * @package BuddyBoss\Performance\Helper
 */

namespace BuddyBoss\Performance;

use BuddyBoss\Performance\Integration\BB_Activity;
use BuddyBoss\Performance\Integration\BB_Documents;
use BuddyBoss\Performance\Integration\BB_Forums;
use BuddyBoss\Performance\Integration\BB_Friends;
use BuddyBoss\Performance\Integration\BB_Groups;
use BuddyBoss\Performance\Integration\BB_Media_Albums;
use BuddyBoss\Performance\Integration\BB_Media_Photos;
use BuddyBoss\Performance\Integration\BB_Members;
use BuddyBoss\Performance\Integration\BB_Messages;
use BuddyBoss\Performance\Integration\BB_Notifications;
use BuddyBoss\Performance\Integration\BB_Replies;
use BuddyBoss\Performance\Integration\BB_Topics;
use BuddyBoss\Performance\Integration\BB_Videos;
use BuddyBoss\Performance\Integration\BB_Subscriptions;

if ( ! class_exists( 'BuddyBoss\Performance\Performance' ) ) {

	/**
	 * Cache Performance class.
	 */
	class Performance {

		/**
		 * Class instance.
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Has started.
		 *
		 * @var boolean
		 */
		private $has_started = false;

		/**
		 * Has validated.
		 *
		 * @var boolean
		 */
		private $has_validated = false;

		/**
		 * File Location.
		 *
		 * @var string
		 */
		private static $file_location = '';

		/**
		 * Class instance.
		 *
		 * @return Performance
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				$class_name          = __CLASS__;
				self::$instance      = new $class_name();
				self::$file_location = __DIR__;
				add_action( 'muplugins_loaded', array( self::$instance, 'start' ), 9999 );
			}

			return self::$instance;
		}

		/**
		 * Start the performance operations 🚀.
		 */
		public function start() {

			add_action( 'set_current_user', array( $this, 'is_set_current_user' ), 1 );

			if ( ! $this->has_started ) {

				$this->has_started = true;

				// Make Cache API Available.
				require_once dirname( __FILE__ ) . '/class-cache.php';
				require_once dirname( __FILE__ ) . '/integrations/class-integration-abstract.php';
				require_once dirname( __FILE__ ) . '/class-option-clear-cache.php';
				require_once dirname( __FILE__ ) . '/class-helper.php';
				require_once dirname( __FILE__ ) . '/class-route-helper.php';
				require_once dirname( __FILE__ ) . '/class-pre-user-provider.php';
				require_once dirname( __FILE__ ) . '/class-settings.php';

				Route_Helper::instance();
				Helper::instance();
				OptionClearCache::instance();
				Pre_User_Provider::instance();
				Settings::instance();

				// All Integrations.

				// Load platform or buddyPress related cache integration.
				if (
					self::mu_is_plugin_active( 'buddyboss-platform/bp-loader.php' ) ||
					self::mu_is_plugin_active( 'buddypress/bp-loader.php' )
				) {

					$group_integration = dirname( __FILE__ ) . '/integrations/class-bb-groups.php';
					if ( self::mu_is_component_active( 'groups' ) && file_exists( $group_integration ) ) {
						require_once $group_integration;
						BB_Groups::instance();
					}

					$members_integration = dirname( __FILE__ ) . '/integrations/class-bb-members.php';
					if ( self::mu_is_component_active( 'members' ) && file_exists( $members_integration ) ) {
						require_once $members_integration;
						BB_Members::instance();
					}

					$activity_integration = dirname( __FILE__ ) . '/integrations/class-bb-activity.php';
					if ( self::mu_is_component_active( 'activity' ) && file_exists( $activity_integration ) ) {
						require_once $activity_integration;
						BB_Activity::instance();
					}

					$friends_integration = dirname( __FILE__ ) . '/integrations/class-bb-friends.php';
					if ( self::mu_is_component_active( 'friends' ) && file_exists( $friends_integration ) ) {
						require_once $friends_integration;
						BB_Friends::instance();
					}

					$notifications_integration = dirname( __FILE__ ) . '/integrations/class-bb-notifications.php';
					if ( self::mu_is_component_active( 'notifications' ) && file_exists( $notifications_integration ) ) {
						require_once $notifications_integration;
						BB_Notifications::instance();
					}

					$messages_integration = dirname( __FILE__ ) . '/integrations/class-bb-messages.php';
					if ( self::mu_is_component_active( 'messages' ) && file_exists( $messages_integration ) ) {
						require_once $messages_integration;
						BB_Messages::instance();
					}

					$media_photos_integration = dirname( __FILE__ ) . '/integrations/class-bb-media-photos.php';
					if ( self::mu_is_component_active( 'media' ) && file_exists( $media_photos_integration ) ) {
						require_once $media_photos_integration;
						BB_Media_Photos::instance();
					}

					$media_albums_integration = dirname( __FILE__ ) . '/integrations/class-bb-media-albums.php';
					if ( self::mu_is_component_active( 'media' ) && file_exists( $media_albums_integration ) ) {
						require_once $media_albums_integration;
						BB_Media_Albums::instance();
					}

					$documents_integration = dirname( __FILE__ ) . '/integrations/class-bb-documents.php';
					if ( self::mu_is_component_active( 'document' ) && file_exists( $documents_integration ) ) {
						require_once $documents_integration;
						BB_Documents::instance();
					}

					$videos_integration = dirname( __FILE__ ) . '/integrations/class-bb-videos.php';
					if ( self::mu_is_component_active( 'video' ) && file_exists( $videos_integration ) ) {
						require_once $videos_integration;
						BB_Videos::instance();
					}

					$subscriptions_integration = dirname( __FILE__ ) . '/integrations/class-bb-subscriptions.php';
					if ( file_exists( $subscriptions_integration ) ) {
						require_once $subscriptions_integration;
						BB_Subscriptions::instance();
					}
				}

				// Load platform or bbPress related cache integration.
				if (
					(
						self::mu_is_plugin_active( 'buddyboss-platform/bp-loader.php' ) &&
						self::mu_is_component_active( 'forums' )
					) ||
					self::mu_is_plugin_active( 'bbpress/bbpress.php' )
				) {

					$forum_integration = dirname( __FILE__ ) . '/integrations/class-bb-forums.php';
					$topic_integration = dirname( __FILE__ ) . '/integrations/class-bb-topics.php';
					$reply_integration = dirname( __FILE__ ) . '/integrations/class-bb-replies.php';

					if ( file_exists( $forum_integration ) ) {
						require_once $forum_integration;
						BB_Forums::instance();
					}
					if ( file_exists( $topic_integration ) ) {
						require_once $topic_integration;
						BB_Topics::instance();
					}
					if ( file_exists( $reply_integration ) ) {
						require_once $reply_integration;
						BB_Replies::instance();
					}
				}

				/**
				 * Loads when rest cache is loaded.
				 */
				do_action( 'rest_cache_loaded' );

			}
		}

		/**
		 * Identify the current user is set by WordPress.
		 */
		public function is_set_current_user() {
			global $bb_is_current_user_available;
			$bb_is_current_user_available = true;
		}

		/**
		 * Tells if the current user information is available on WordPress.
		 */
		public function is_current_user_available() {
			global $bb_is_current_user_available;
			if ( isset( $bb_is_current_user_available ) ) {
				return $bb_is_current_user_available;
			}

			return false;
		}

		/**
		 * Guessed user ID.
		 *
		 * @return int|boolean
		 */
		public function get_guessed_user_id() {
			$guessed_user_id = apply_filters( 'rest_cache_pre_current_user_id', false );

			return $guessed_user_id;
		}

		/**
		 * Function to run on activation.
		 */
		public function on_activation() {
			global $wpdb;

			$charset_collate = $wpdb->get_charset_collate();

			if ( ! function_exists( 'dbDelta' ) ) {
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			}

			$mysql_server_info = '';
			$mysql_version     = '';

			if (
				$wpdb->use_mysqli &&
				function_exists( 'mysqli_get_server_info' ) &&
				function_exists( 'mysqli_get_server_version' )
			) {
				$mysql_server_info = $wpdb->db_server_info();
				$mysql_version     = mysqli_get_server_version( $wpdb->dbh ); // phpcs:ignore
			} elseif (
				function_exists( 'mysql_get_server_info' ) &&
				function_exists( 'mysql_get_server_version' )
			) {
				$mysql_server_info = $wpdb->db_server_info();
				$mysql_version     = mysql_get_server_version(); // phpcs:ignore
			}

			$is_mariadb = false;

			// Check for the MariaDB.
			if ( ! empty( $mysql_server_info ) && strpos( strtolower( $mysql_server_info ), 'maria' ) !== false ) {
				$is_mariadb = true;
			}

			// Below 10.3 Mariadb or below 8.0 Mysql.
			if (
				! empty( $mysql_version ) &&
				(
					( $is_mariadb && $mysql_version < 100300 ) ||
					( ! $is_mariadb && $mysql_version < 80000 )
				)
			) {
				$sql = "CREATE TABLE {$wpdb->prefix}bb_performance_cache (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					user_id bigint(20) NOT NULL DEFAULT 0,
					blog_id bigint(20) NOT NULL DEFAULT 0,
					cache_name varchar(1000) NOT NULL,
					cache_group varchar(200) NOT NULL,
					cache_value mediumtext DEFAULT NULL,
					cache_expire datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					PRIMARY KEY  (id),
					KEY cache_name (cache_name(191)),
					KEY cache_group (cache_group(191)),
					KEY cache_expire (cache_expire)
				) $charset_collate;";
			} else {
				$sql = "CREATE TABLE {$wpdb->prefix}bb_performance_cache (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					user_id bigint(20) NOT NULL DEFAULT 0,
					blog_id bigint(20) NOT NULL DEFAULT 0,
					cache_name varchar(1000) NOT NULL,
					cache_group varchar(200) NOT NULL,
					cache_value mediumtext DEFAULT NULL,
					cache_expire datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					PRIMARY KEY  (id),
					KEY cache_name (cache_name),
					KEY cache_group (cache_group),
					KEY cache_expire (cache_expire)
				) $charset_collate;";
			}

			dbDelta( $sql );
		}

		/**
		 * Function to check the plugin is activate or not.
		 *
		 * @param string $plugin Plugin to check.
		 *
		 * @return bool
		 */
		public static function mu_is_plugin_active( $plugin ) {
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true );
		}

		/**
		 * Check component is active or not.
		 *
		 * @param string $component Component name.
		 *
		 * @return bool
		 */
		public static function mu_is_component_active( $component ) {
			$components = get_option( 'bp-active-components', array() );

			return isset( $components[ $component ] );
		}

		/**
		 * Validate method to check the mu plugin file is there and shows sitewide notice.
		 */
		public function validate() {
			if ( ! $this->has_validated ) {

				$this->has_validated = true;

				add_action( 'admin_init', array( $this, 'bp_mu_setup_and_load_plugin_file' ) );
			}
		}

		/**
		 * Setup the mu plugin file and shows sitewide notice..
		 */
		public function bp_mu_setup_and_load_plugin_file() {

			// If mu-plugin directory not exists then create automatically.
			if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
				mkdir( WPMU_PLUGIN_DIR, 0755 );
			}

			$mu_plugins             = get_mu_plugins();
			$bp_mu_plugin_file_path = '';
			$file_location          = self::$file_location;
			if ( strpos( $file_location, 'Performance' ) !== false ) {
				$file_location          = str_replace( 'Performance', '', $file_location );
				$bp_mu_plugin_file_path = $file_location . 'MuPlugin/buddyboss-api-caching-mu.php';
			} elseif ( strpos( $file_location, 'bp-performance' ) !== false ) {
				$bp_platform_mu_path     = WP_PLUGIN_DIR . '/buddyboss-platform/bp-performance/mu-plugins/buddyboss-api-caching-mu.php';
				$bp_platform_dev_mu_path = WP_PLUGIN_DIR . '/buddyboss-platform/src/bp-performance/mu-plugins/buddyboss-api-caching-mu.php';
				if ( file_exists( $bp_platform_mu_path ) ) {
					$bp_mu_plugin_file_path = $bp_platform_mu_path;
				} elseif ( file_exists( $bp_platform_dev_mu_path ) ) {
					$bp_mu_plugin_file_path = $bp_platform_dev_mu_path;
				}
			}

			if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			}
			$wp_files_system = new \WP_Filesystem_Direct( array() );

			if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-api-caching-mu.php' ) ) {

				// Try to automatically install MU plugin.
				if ( wp_is_writable( WPMU_PLUGIN_DIR ) && ! empty( $bp_mu_plugin_file_path ) ) {

					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@$wp_files_system->copy( $bp_mu_plugin_file_path, WPMU_PLUGIN_DIR . '/buddyboss-api-caching-mu.php' );
				}
			} elseif ( ! empty( $mu_plugins ) && array_key_exists( 'buddyboss-api-caching-mu.php', $mu_plugins ) && version_compare( $mu_plugins['buddyboss-api-caching-mu.php']['Version'], '1.0.1', '<' ) ) {

				// Try to automatically install MU plugin.
				if ( wp_is_writable( WPMU_PLUGIN_DIR ) && ! empty( $bp_mu_plugin_file_path ) ) {

					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@$wp_files_system->copy( $bp_mu_plugin_file_path, WPMU_PLUGIN_DIR . '/buddyboss-api-caching-mu.php', true );
				}
			}

			$purge_nonce = ( ! empty( $_GET['download_mu_file'] ) ) ? wp_unslash( $_GET['download_mu_file'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( wp_verify_nonce( $purge_nonce, 'bp_performance_mu_download' ) && ! empty( $bp_mu_plugin_file_path ) ) {
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

			$mu_plugins = get_mu_plugins();

			// If installing MU plugin fails, display warning and download link in WP admin.
			if ( ! file_exists( WPMU_PLUGIN_DIR . '/buddyboss-api-caching-mu.php' ) ) {
				add_action( 'admin_notices', array( $this, 'add_sitewide_notice' ) );
			} elseif ( ! empty( $mu_plugins ) && array_key_exists( 'buddyboss-api-caching-mu.php', $mu_plugins ) && version_compare( $mu_plugins['buddyboss-api-caching-mu.php']['Version'], '1.0.1', '<' ) ) {
				add_action( 'admin_notices', array( $this, 'update_sitewide_notice' ) );
			}
		}

		/**
		 * Added site wide noice.
		 */
		public function add_sitewide_notice() {
			$bp_performance_download_nonce = wp_create_nonce( 'bp_performance_mu_download' );

			$file_location = self::$file_location;
			$download_path = admin_url( 'admin.php?page=bp-settings&download_mu_file=' . $bp_performance_download_nonce );

			if ( strpos( $file_location, 'buddyboss-app' ) !== false ) {
				$download_path = admin_url( 'admin.php?page=bbapp-settings&setting=cache_support&download_mu_file=' . $bp_performance_download_nonce );
			}

			$notice = sprintf(
				'%1$s <a href="%2$s">%3$s</a>. <br /><strong><a href="%4$s">%5$s</a></strong> %6$s',
				__( 'API Caching cannot be automatically installed on your server. To enable caching, you need to manually install the "BuddyBoss API Caching" plugin in your', 'buddyboss' ),
				'https://wordpress.org/support/article/must-use-plugins/',
				__( 'must-use plugins', 'buddyboss' ),
				$download_path,
				__( 'Download the plugin', 'buddyboss' ),
				__( 'and then upload it into the "/wp-content/mu-plugins/" directory on your server.', 'buddyboss' )
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="notice notice-error">' . wpautop( $notice ) . '</div>';
		}

		/**
		 * Added site wide noice.
		 */
		public function update_sitewide_notice() {
			$bp_performance_download_nonce = wp_create_nonce( 'bp_performance_mu_download' );

			$file_location = self::$file_location;
			$download_path = admin_url( 'admin.php?page=bp-settings&download_mu_file=' . $bp_performance_download_nonce );

			if ( strpos( $file_location, 'buddyboss-app' ) !== false ) {
				$download_path = admin_url( 'admin.php?page=bbapp-settings&setting=cache_support&download_mu_file=' . $bp_performance_download_nonce );
			}

			$notice = sprintf(
				'%1$s <a href="%2$s">%3$s</a>. <br /><strong><a href="%4$s">%5$s</a></strong> %6$s',
				__( 'API Caching could not be automatically updated on your server. To enable caching, you need to manually update the "BuddyBoss API Caching" plugin in your', 'buddyboss' ),
				'https://wordpress.org/support/article/must-use-plugins/',
				__( 'must-use plugins', 'buddyboss' ),
				$download_path,
				__( 'Download v1.0.1', 'buddyboss' ),
				__( 'and then upload the plugin manually into the "/wp-content/mu-plugins/" directory on your server.', 'buddyboss' )
			);

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="notice notice-error">' . wpautop( $notice ) . '</div>';
		}
	}
}
