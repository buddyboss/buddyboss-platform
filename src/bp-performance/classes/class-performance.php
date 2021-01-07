<?php
/**
 * BuddyBoss Performance.
 *
 * @package BuddyBoss\Performance\Helper
 */

namespace BuddyBoss\Performance;

use BuddyBoss\Performance\Route_Helper;
use BuddyBoss\Performance\Integration\BB_Groups;
use BuddyBoss\Performance\Integration\BB_Members;
use BuddyBoss\Performance\Integration\BB_Activity;
use BuddyBoss\Performance\Integration\BB_Friends;
use BuddyBoss\Performance\Integration\BB_Notifications;
use BuddyBoss\Performance\Integration\BB_Messages;
use BuddyBoss\Performance\Integration\BB_Forums;
use BuddyBoss\Performance\Integration\BB_Topics;
use BuddyBoss\Performance\Integration\BB_Replies;

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
		 * Class instance.
		 *
		 * @return Performance
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				$class_name     = __CLASS__;
				self::$instance = new $class_name();
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
				require_once dirname( __FILE__ ) . '/class-helper.php';
				require_once dirname( __FILE__ ) . '/class-route-helper.php';
				require_once dirname( __FILE__ ) . '/class-pre-user-provider.php';
				require_once dirname( __FILE__ ) . '/class-settings.php';

				Route_Helper::instance();
				Helper::instance();
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

					$members_integration = dirname( __FILE__ ) . '/integrations/class-bb-groups.php';
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

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql = "CREATE TABLE {$wpdb->prefix}bb_performance_cache (
	            id bigint(20) NOT NULL AUTO_INCREMENT,
	            user_id bigint(20) NOT NULL DEFAULT 0,
	            blog_id bigint(20) NOT NULL DEFAULT 0,
	            cache_name varchar(1000) NOT NULL,
	            cache_group varchar(200) NOT NULL,
	            cache_value mediumtext DEFAULT NULL,
	            cache_expire datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	            purge_events text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
	            event_groups varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
	            UNIQUE KEY id (id),
	            KEY cache_name (cache_name),
	            KEY cache_group (cache_group),
	            KEY cache_expire (cache_expire),
	            KEY event_groups (event_groups)
	        ) $charset_collate;";

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
	}
}
