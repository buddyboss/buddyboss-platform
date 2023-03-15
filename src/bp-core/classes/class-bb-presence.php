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
	}

	BB_Presence::instance();
}
