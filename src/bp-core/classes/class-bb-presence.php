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
		 * Cache of the presence.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var object
		 */
		private $cache;

		/**
		 * This will use for global $wpdb.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var object
		 */
		private $wpdb;

		/**
		 * Cache key.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		private $cache_key = 'bb_presence_last_activity_';

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			global $wpdb, $wp_object_cache;

			$this->wpdb  = $wpdb;
			$this->cache = $wp_object_cache;
		}

		/**
		 * Function will update the user's last activity time in cache and DB.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $user_id The ID of the user.
		 */
		public function bb_update_last_activity( $user_id ) {
			$last_activity_key = $this->cache_key . $user_id;
			$last_activity     = $this->cache->get( $last_activity_key );

			if ( false !== $last_activity && time() - $last_activity < 60 ) {

				// Update the cache directly.
				$this->cache->set( $last_activity_key, time() );
			} else {

				// Update the cache and DB using SQL.
				$this->cache->set( $last_activity_key, time() );
				$this->wpdb->update( $this->wpdb->prefix . 'bp_activity',
					array( 'date_recorded' => bp_core_current_time() ),
					array( 'user_id' => $user_id ),
					array( '%s' ),
					array( '%d' )
				);

				// Update last activity in usermeta also.
				remove_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10 );
				remove_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10 );
				update_user_meta( $user_id, 'last_activity', bp_core_current_time() );
				add_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10, 4 );
				add_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10, 4 );
			}
		}

		/**
		 * Function will fetch the user's last activity time from the cache or DB.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $user_id The ID of the user.
		 *
		 * @return int|null This will return last activity time. If not found then return null.
		 */
		public function bb_get_user_activity( $user_id ) {
			$last_activity_key = $this->cache_key . $user_id;
			$last_activity     = $this->cache->get( $last_activity_key );
			if ( false !== $last_activity ) {
				return date('Y-m-d H:i:s', $last_activity );
			} else {
				$activity = $this->wpdb->get_var(
					$this->wpdb->prepare(
						"SELECT MAX(date_recorded) FROM {$this->wpdb->prefix}bp_activity WHERE user_id = %d",
						$user_id
					)
				);
				if ( null !== $activity ) {
					$this->cache->set( $last_activity_key, strtotime( $activity ) );

					return $activity;
				} else {
					return 0;
				}
			}
		}
	}
}
