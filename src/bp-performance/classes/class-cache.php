<?php
/**
 * BuddyBoss Performance cache.
 *
 * @package BuddyBoss\Performance\Cache
 */

namespace BuddyBoss\Performance;

/**
 * Cache Main class.
 */
class Cache {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Cache table name.
	 *
	 * @var string
	 */
	public $cache_table;

	/**
	 * Minutes in seconds.
	 *
	 * @var integer
	 */
	public $minute_in_seconds;

	/**
	 * Hours in seconds.
	 *
	 * @var integer
	 */
	public $hour_in_seconds;

	/**
	 * Day in seconds.
	 *
	 * @var integer
	 */
	public $day_in_seconds;

	/**
	 * Week in seconds.
	 *
	 * @var integer
	 */
	public $week_in_seconds;

	/**
	 * Month in seconds.
	 *
	 * @var integer
	 */
	public $month_in_seconds;

	/**
	 * Year in seconds.
	 *
	 * @var integer
	 */
	public $year_in_seconds;

	/**
	 * Create class instance.
	 *
	 * @return Cache
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
			self::$instance->load(); // run the hooks.
		}

		return self::$instance;
	}

	/**
	 * Load the class.
	 */
	public function load() {
		global $wpdb;

		/**
		 * Set the table name.
		 */
		if ( is_multisite() ) {
			switch_to_blog( 1 );
			$this->cache_table = $wpdb->prefix . 'bb_performance_cache';
			restore_current_blog();
		} else {
			$this->cache_table = $wpdb->prefix . 'bb_performance_cache';
		}

		/**
		 * Set Constants
		 */
		$this->minute_in_seconds = MINUTE_IN_SECONDS;
		$this->hour_in_seconds   = HOUR_IN_SECONDS;
		$this->day_in_seconds    = DAY_IN_SECONDS;
		$this->week_in_seconds   = WEEK_IN_SECONDS;
		$this->month_in_seconds  = MONTH_IN_SECONDS;
		$this->year_in_seconds   = YEAR_IN_SECONDS;

		$this->bb_performance_purge_schedule_cron();

		add_action( 'bb_performance_purge_expired_cache_hook', array( $this, 'purge_expired_cache' ) );

		// Re-schedule when update the timezone.
		add_action( 'updated_option', array( $this, 'bb_performance_purge_reschedule_cron' ), 10, 3 );
	}

	/**
	 * Set the cache.
	 *
	 * @param string $cache_name   Cache name.
	 * @param string $cache_value  Cache value.
	 * @param string $cache_expire Time until expiration in seconds from now.
	 * @param string $cache_group  Cache group name.
	 * @param array  $user_id      user Id
	 *
	 * @return bool
	 */
	public function set( $cache_name, $cache_value, $cache_expire, $cache_group = 'buddyboss-api', $user_id  = 0  ) {

		// If the memcache based are available.
		// Currently we bypass this condition as we not support purge with memcache.
		if ( wp_using_ext_object_cache() && false ) {
			$value = wp_cache_set( $cache_name, $cache_value, $cache_group, $cache_expire );
		} else {

			global $wpdb;

			$cache_expire = gmdate( 'Y-m-d H:i:s', time() + $cache_expire );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$get = $wpdb->get_row( $wpdb->prepare( "SELECT *FROM {$this->cache_table} WHERE cache_name=%s AND user_id=%s AND cache_group=%s", $cache_name, $user_id, $cache_group ) );

			if ( empty( $get ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$value = $wpdb->insert(
					$this->cache_table,
					array(
						'user_id'      => $user_id,
						'blog_id'      => get_current_blog_id(),
						'cache_name'   => $cache_name,
						'cache_group'  => $cache_group,
						'cache_value'  => base64_encode( gzcompress( maybe_serialize( $cache_value ) ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						'cache_expire' => $cache_expire,
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$value = $wpdb->update(
					$this->cache_table,
					array(
						'cache_value'  => base64_encode( gzcompress( maybe_serialize( $cache_value ) ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
						'cache_expire' => $cache_expire,
					),
					array(
						'user_id'     => $user_id,
						'blog_id'     => get_current_blog_id(),
						'cache_name'  => $cache_name,
						'cache_group' => $cache_group,
					)
				);
			}
		}

		return $value;

	}

	/**
	 * Return the cache by the cache name.
	 *
	 * @param string $cache_name  Cache name.
	 * @param int    $user_id     User ID.
	 * @param int    $blog_id     Blog ID.
	 * @param string $cache_group Cache group name. Default 'buddyboss-api'.
	 *
	 * @return bool|mixed
	 */
	public function get( $cache_name, $user_id, $blog_id, $cache_group = 'buddyboss-api', $current_endpoint = '' ) {

		$value = false;

		// If the memcache based are available.
		// Currently we bypass this condition as we not support purge with memcache.
		if ( wp_using_ext_object_cache() && false ) {
			$value = wp_cache_get( $cache_name, $cache_group );
		} else {

			global $wpdb;

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$get = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->cache_table} WHERE cache_name=%s AND user_id=%s AND blog_id=%s AND cache_group=%s", $cache_name, $user_id, $blog_id, $cache_group ) );

			if ( ! empty( $get ) && strtotime( $get->cache_expire ) < time() ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete(
					$this->cache_table,
					array(
						'id' => $get->id,
					)
				);
				$value = false;
			} elseif ( ! empty( $get ) ) {
				if ( ! is_serialized( $get->cache_value ) ) { // check if the data is compressed.
					$value = gzuncompress( base64_decode( $get->cache_value ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
					$value = maybe_unserialize( $value );
				} else { // when data is not compressed.
					$value = maybe_unserialize( $get->cache_value );
				}
			} else {
				$value = false;
			}
		}

		/**
		 * After Prepare rest cache data.
		 *
		 * @param mixed  $value            Value of cache data.
		 * @param int    $user_id          Current user ID.
		 * @param string $current_endpoint Current Endpoint URL.
		 */
		$value = apply_filters( 'rest_get_cache', $value, $user_id, $current_endpoint );

		return $value;
	}

	/**
	 * Purge cache by group name
	 *
	 * @param string $group_name Cache group name.
	 */
	public function purge_by_group( $group_name ) {
		global $wpdb;

		static $bp_purge_by_group_cache = array();

		$cache_key = 'purge_by_group_' . sanitize_title( $group_name );
		if ( ! isset( $bp_purge_by_group_cache[ $cache_key ] ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$caches                                = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->cache_table} where cache_group=%s", $group_name ) );
			$bp_purge_by_group_cache[ $cache_key ] = $caches;
		} else {
			$caches = $bp_purge_by_group_cache[ $cache_key ];
		}

		if ( ! empty( $caches ) ) {
			foreach ( $caches as $key => $cache ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->cache_table} WHERE cache_name=%s AND cache_group=%s", $cache->cache_name, $group_name ) );
			}
		}
	}

	/**
	 * Purge cache by groups
	 *
	 * @param array|string $group_names Cache group names.
	 *
	 * @since BuddyBoss 2.5.50
	 */
	public function purge_by_groups( $group_names ) {
		global $wpdb;

		if ( empty( $group_names ) ) {
			return;
		}

		/**
		 * Filter the cache purge limit.
		 *
		 * @param int $limit Cache purge limit.
		 *
		 * @since BuddyBoss 2.5.50
		 */
		$limit = apply_filters( 'bb_cache_purge_limit', 1000 );

		$groups_in = is_array( $group_names ) ? "'" . implode( "','", $group_names ) . "'" : "'" . $group_names . "'";

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( "SELECT COUNT(id) FROM {$this->cache_table} WHERE cache_group IN ($groups_in)" );

		if ( $count ) {
			$round_count = ceil( $count / $limit );

			for ( $i = 0; $i < $round_count; $i++ ) {
				//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( "DELETE FROM {$this->cache_table} WHERE cache_group IN ($groups_in) LIMIT {$limit}" );
			}
		}
	}

	/**
	 * Purge cache by user id
	 *
	 * @param integer $user_id     Cache user id.
	 * @param array   $group_names Cache group names.
	 */
	public function purge_groups_by_user_id( $user_id, $group_names ) {
		global $wpdb;

		if ( empty( $user_id ) || empty( $group_names ) ) {
			return;
		}

		/**
		 * Filter the cache purge limit.
		 *
		 * @param int $limit Cache purge limit.
		 *
		 * @since BuddyBoss 2.5.50
		 */
		$limit = apply_filters( 'bb_cache_purge_limit', 1000 );

		$groups_in = "'" . implode( "','", $group_names ) . "'";

		//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$this->cache_table} WHERE user_id=%d AND cache_group IN ($groups_in)", $user_id ) );

		if ( $count ) {
			$round_count = ceil( $count / $limit );

			for ( $i = 0; $i < $round_count; $i++ ) {
				//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->cache_table} WHERE user_id=%d AND cache_group IN ($groups_in) LIMIT {$limit}", $user_id ) );
			}
		}
	}

	/**
	 * Purge cache by user id
	 *
	 * @param integer $user_id Cache user id.
	 * @param string  $group_name Cache group name.
	 */
	public function purge_by_user_id( $user_id, $group_name = '' ) {
		global $wpdb;

		if ( ! empty( $group_name ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->cache_table} WHERE  user_id=%s AND cache_group  LIKE %s", $user_id, '%' . $group_name . '%' ) );

		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$this->cache_table} WHERE  user_id=%s", $user_id ) );
		}
	}


	/**
	 * Purge cache by endpoint
	 *
	 * @param string $cache_name Cache name.
	 */
	public function purge_by_endpoint( $cache_name ) {

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$status = $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->cache_table} WHERE cache_name=%s", $cache_name ) );

		if ( $status ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
			// Operation succeeded.
		} else { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
			// Operation failed.
		}
	}

	/**
	 * Purge cache by Component for setting screen
	 *
	 * @param array $component Array of components.
	 */
	public function purge_by_component( $component = array() ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql  = "DELETE FROM {$this->cache_table} WHERE cache_group like ";
		$sql .= "'%" . $component . "%'";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $sql );
	}

	/**
	 * Purge all
	 */
	public function purge_all() {

		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$status = $wpdb->query( "DELETE FROM {$this->cache_table}" );

		if ( $status ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
			// Operation succeeded.
		} else { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElse
			// Operation failed.
		}
	}

	/**
	 * Purge cache by group names.
	 *
	 * @param array $ids               Array of ids.
	 * @param array $group_names       Array of group names.
	 * @param array $deeplink_callback Callable function to prepare the deeplink group name.
	 *
	 * @since BuddyBoss 2.5.50
	 *
	 * @return void
	 */
	public function purge_by_group_names( $ids, $group_names, $deeplink_callback = '' ) {
		if ( empty( $ids ) || empty( $group_names ) ) {
			return;
		}

		$count        = count( $ids );
		$cache_groups = array();

		/**
		 * Limit the number of ids to purge at once.
		 *
		 * @param int $limit Cache purge limit.
		 *
		 * @since BuddyBoss 2.5.50
		 */
		$limit = apply_filters( 'bb_cache_purge_groups_limit', 1000 );

		if ( $limit < $count ) {
			$chunked_data = array_chunk( $ids, $limit );

			foreach ( $chunked_data as $chunked_ids ) {
				foreach ( $chunked_ids as $id ) {
					foreach ( $group_names as $group_name ) {
						$cache_groups[] = $group_name . $id;

						if ( ! empty( $deeplink_callback ) && is_callable( $deeplink_callback ) ) {
							$cache_groups[] = $group_name . call_user_func( $deeplink_callback, $id );
						}
					}
				}

				$this->purge_by_groups( $cache_groups );
			}
		} else {
			foreach ( $ids as $id ) {
				foreach ( $group_names as $group_name ) {
					$cache_groups[] = $group_name . $id;

					if ( ! empty( $deeplink_callback ) && is_callable( $deeplink_callback ) ) {
						$cache_groups[] = $group_name . call_user_func( $deeplink_callback, $id );
					}
				}
			}

			$this->purge_by_groups( $cache_groups );
		}
	}

	/**
	 * Purge cache user groups.
	 *
	 * @param int   $user_id     User ID.
	 * @param array $group_names Array of group names.
	 *
	 * @since BuddyBoss 2.5.50
	 *
	 * @return void
	 */
	public function purge_user_groups( $user_id, $group_names ) {
		if ( empty( $user_id ) || empty( $group_names ) ) {
			return;
		}

		$count = count( $group_names );

		/**
		 * Limit the number of ids to purge at once.
		 *
		 * @param int $limit Cache purge limit.
		 *
		 * @since BuddyBoss 2.5.50
		 */
		$limit = apply_filters( 'bb_cache_purge_groups_limit', 1000 );

		if ( $limit < $count ) {
			$chunked_data = array_chunk( $group_names, $limit );

			foreach ( $chunked_data as $chunked_groups ) {
				$this->purge_groups_by_user_id( $user_id, $chunked_groups );
			}
		} else {
			$this->purge_groups_by_user_id( $user_id, $group_names );
		}
	}

	/**
	 * Returns the cache expiry time.
	 *
	 * @since BuddyBoss 2.6.10
	 *
	 * @return int Cache expiry in seconds.
	 */
	public static function cache_expiry() {

		$cache_expiry = self::instance()->month_in_seconds;

		/**
		 * Filters the cache expiry time.
		 *
		 * @since BuddyBoss 2.6.10
		 *
		 * @param int $cache_expiry Expiry time for cache.
		 */
		return apply_filters( 'bb_performance_cache_expiry_time', $cache_expiry );
	}

	/**
	 * Purges expired cache via background job.
	 *
	 * @since BuddyBoss 2.6.10
	 *
	 * @return void
	 */
	public function purge_expired_cache() {
		global $wpdb;

		// Current GMT time in Y-m-d H:i:s format.
		$current_gmt_time = current_time( 'mysql', true );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $this->cache_table WHERE cache_expire < %s",
				$current_gmt_time
			)
		);
	}

	/**
	 * Schedule performance cache purge cron event.
	 *
	 * @since BuddyBoss 2.6.10
	 *
	 * @return void
	 */
	public function bb_performance_purge_schedule_cron() {

		// Check if the cron job is not already scheduled.
		$is_scheduled = wp_next_scheduled( 'bb_performance_purge_expired_cache_hook' );

		// WP datetime.
		$final_date         = date_i18n( 'Y-m-d H:i:s', strtotime( 'today 23:30' ) );
		$local_datetime     = date_create( $final_date, wp_timezone() );
		$schedule_timestamp = $local_datetime->getTimestamp();

		if ( ! $is_scheduled ) {
			wp_schedule_event( $schedule_timestamp, 'daily', 'bb_performance_purge_expired_cache_hook' );
		}
	}

	/**
	 * Re-Schedule event to clear the cron for cache.
	 *
	 * @since BuddyBoss 2.6.10
	 *
	 * @param string $option    Name of the option to update.
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $new_value The new option value.
	 *
	 * @return void
	 */
	public function bb_performance_purge_reschedule_cron( $option, $old_value = '', $new_value = '' ) {

		// Avoid clearing multiple time.
		static $is_reschedule = false;

		// Check if the updated option is 'timezone_string'.
		if (
			(
				'timezone_string' === $option ||
				'gmt_offset' === $option
			) &&
			$old_value !== $new_value &&
			! $is_reschedule
		) {
			if ( wp_next_scheduled( 'bb_performance_purge_expired_cache_hook' ) ) {
				wp_clear_scheduled_hook( 'bb_performance_purge_expired_cache_hook' );
			}
			$this->bb_performance_purge_schedule_cron();
			$is_reschedule = true;
		}
	}
}
