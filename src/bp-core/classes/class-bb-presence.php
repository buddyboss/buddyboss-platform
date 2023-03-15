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
		 * This will use for last activity cache time.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var int
		 */
		public $cache_time;

		/**
		 * Constructor method.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			global $wpdb, $wp_object_cache;

			$this->wpdb       = $wpdb;
			$this->cache      = $wp_object_cache;
			$this->cache_time = (int) apply_filters( 'bb_presence_last_activity_cache_time', 60 );
		}

		/**
		 * Function will update the user's last activity time in cache and DB.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $user_id The ID of the user.
		 */
		public function bb_update_last_activity( $user_id, $time ) {
			// Fall back on current time.
			if ( empty( $time ) ) {
				$time = current_time( 'mysql', true );
			}

			$table_name    = $this->wpdb->prefix . 'bp_activity';
			$activity      = $this->bb_get_users_last_activity( $user_id );
			$last_activity = isset( $activity[ $user_id ]['date_recorded'] ) ? strtotime( $activity[ $user_id ]['date_recorded'] ) : 0;
			if ( false !== $this->cache->get( $user_id, 'bp_last_activity' ) && time() - $last_activity < $this->cache_time ) {
				// Update the cache directly.
				$activity[ $user_id ]['date_recorded'] = $time;
				$this->cache->set( $user_id, $activity[ $user_id ], 'bp_last_activity' );

				return;
			}

			// Update last activity in usermeta also.
			remove_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10 );
			remove_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10 );
			update_user_meta( $user_id, 'last_activity', $time );
			add_filter( 'update_user_metadata', '_bp_update_user_meta_last_activity_warning', 10, 4 );
			add_filter( 'get_user_metadata', '_bp_get_user_meta_last_activity_warning', 10, 4 );

			if ( ! empty( $activity[ $user_id ] ) ) {
				$this->wpdb->update(
					$table_name,
					array( 'date_recorded' => $time, ),
					array( 'id' => $activity[ $user_id ]['activity_id'], ),
					array( '%s', ),
					array( '%d', )
				);

				// Add new date to existing activity entry for caching.
				$activity[ $user_id ]['date_recorded'] = $time;

			} else {
				$this->wpdb->insert(
					$table_name,
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
					'activity_id'   => $this->wpdb->insert_id,
				);
			}

			// Set cache.
			$this->cache->set( $user_id, $activity[ $user_id ], 'bp_last_activity' );
		}

		/**
		 * Function will fetch the multiple user's last activity time from the cache or DB.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $user_ids Array of user IDs.
		 *
		 * @return false|array
		 */
		public function bb_get_users_last_activity( $user_ids ) {
			$table_name = $this->wpdb->prefix . 'bp_activity';
			// Sanitize and remove empty values.
			$user_ids = array_filter( wp_parse_id_list( $user_ids ) );

			if ( empty( $user_ids ) ) {
				return false;
			}

			$uncached_user_ids = array();
			foreach ( $user_ids as $user_id ) {
				$user_id = (int) $user_id;
				if ( false === $this->cache->get( $user_id, 'bp_last_activity' ) ) {
					$uncached_user_ids[] = $user_id;
				}
			}

			if ( ! empty( $uncached_user_ids ) ) {
				$user_ids_sql = implode( ',', $uncached_user_ids );
				$user_count   = count( $uncached_user_ids );

				$last_activities = $this->wpdb->get_results(
					$this->wpdb->prepare(
						"SELECT id, user_id, date_recorded FROM {$table_name} 
						WHERE component = %s AND type = 'last_activity' AND user_id IN ({$user_ids_sql}) LIMIT {$user_count}",
						'members'
					)
				);

				foreach ( $last_activities as $last_activity ) {
					$this->cache->set(
						$last_activity->user_id,
						array(
							'user_id'       => $last_activity->user_id,
							'date_recorded' => $last_activity->date_recorded,
							'activity_id'   => $last_activity->id,
						),
						'bp_last_activity'
					);
				}
			}

			// Fetch all user data from the cache.
			$retval = array();
			foreach ( $user_ids as $user_id ) {
				$retval[ $user_id ] = $this->cache->get( $user_id, 'bp_last_activity' );

				if ( isset( $retval['user_id'] ) ) {
					$retval[ $user_id ]['user_id'] = (int) $retval[ $user_id ]['user_id'];
				}
				if ( isset( $retval['activity_id'] ) ) {
					$retval[ $user_id ]['activity_id'] = (int) $retval[ $user_id ]['activity_id'];
				}
			}

			return $retval;
		}
	}
}
