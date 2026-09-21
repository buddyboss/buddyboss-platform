<?php
/**
 * BuddyBoss XProfile Visibility Class.
 *
 * @package BuddyBoss\XProfile\Classes
 *
 * @since BuddyBoss 2.6.50
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for XProfile visibility.
 *
 * @since BuddyBoss 2.6.50
 */
class BB_XProfile_Visibility {

	/**
	 * XProfile Visibility ID.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @var int $id
	 */
	public $id;

	/**
	 * User ID.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @var int $user_id
	 */
	public $user_id;

	/**
	 * XProfile field ID.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @var int $field_id
	 */
	public $field_id;

	/**
	 * XProfile field visibility value.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @var string $value
	 */
	public $value;

	/**
	 * XProfile field last updated time.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @var string $last_updated
	 */
	public $last_updated;

	/**
	 * Per-request memo of get_user_field_ids_by_visibility_levels() results.
	 *
	 * Display names are viewer-dependent and get resolved at many independent points in a single
	 * request (activity action strings, author links, append_user_fullnames(), comment trees,
	 * member loops, avatar alts, RSS), so the same (user, levels) visibility lookup would
	 * otherwise re-run its uncached query several times per request. Keyed by
	 * "{user_id}:{sha1 of the sorted levels}". Invalidated per user on every write below.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array $field_ids_cache
	 */
	private static $field_ids_cache = array();

	/**
	 * Per-request memo of user_data_exists() results, keyed by user ID.
	 *
	 * A class property rather than a method-local static so prime_user_data_exists_cache() can
	 * fill it for a whole batch: the getter is called once per member by
	 * bp_xprofile_get_fields_by_visibility_levels(), which member search reaches once per matched
	 * row, and its query is uncached.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array $user_data_exists_cache
	 */
	private static $user_data_exists_cache = array();

	/**
	 * Per-request memo of the visibility table's existence.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var bool|null $table_exists_cache Null until resolved.
	 */
	private static $table_exists_cache = null;

	/**
	 * BB_XProfile_Visibility constructor.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @param int|null $field_id Field ID to instantiate.
	 * @param int|null $user_id  User ID to instantiate for.
	 */
	public function __construct( $field_id = null, $user_id = null ) {
		if ( ! empty( $field_id ) ) {
			$this->populate( $field_id, $user_id );
		}
	}

	/**
	 * Populates the XProfile profile visibility data.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @param int $field_id Field ID to populate.
	 * @param int $user_id  User ID to populate for.
	 */
	public function populate( $field_id, $user_id ) {
		global $wpdb;

		$table_name = bp_core_get_table_prefix() . 'bb_xprofile_visibility';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$profile_visibility = $wpdb->get_row(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT * FROM $table_name WHERE field_id = %d AND user_id = %d",
				$field_id,
				$user_id
			)
		);

		if ( isset( $profile_visibility ) ) {
			$this->id           = (int) $profile_visibility->id;
			$this->user_id      = (int) $profile_visibility->user_id;
			$this->field_id     = (int) $profile_visibility->field_id;
			$this->value        = stripslashes( $profile_visibility->value );
			$this->last_updated = $profile_visibility->last_updated;

		} else {
			// When no row is found, we'll need to set these properties manually.
			$this->field_id = (int) $field_id;
			$this->user_id  = (int) $user_id;
		}
	}

	/**
	 * Check if there is data already for the user.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @global wpdb  $wpdb WordPress database abstraction object.
	 * @global array $bp
	 *
	 * @return bool
	 */
	public function exists() {
		global $wpdb;

		$table  = bp_core_get_table_prefix() . 'bb_xprofile_visibility';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$retval = $wpdb->get_row(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT id FROM {$table} WHERE user_id = %d AND field_id = %d",
				$this->user_id,
				$this->field_id
			)
		);

		/**
		 * Filters whether data already exists for the user.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param bool                   $retval Whether data already exists.
		 * @param BB_XProfile_Visibility $this   Instance of the current BB_XProfile_Visibility class.
		 */
		return apply_filters_ref_array( 'xprofile_visibility_exists', array( (bool) $retval, $this ) );
	}

	/**
	 * Check if any data exists for the user.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @param int $user_id User id.
	 *
	 * @return bool
	 */
	public static function user_data_exists( $user_id = 0 ) {
		global $wpdb;

		$user_id = (int) $user_id;

		if ( ! isset( self::$user_data_exists_cache[ $user_id ] ) ) {
			$table_exists = self::visibility_table_exists();

			// null is "the probe failed", not "the table is absent". Memoising false there would
			// tell every caller this member resolves from user meta when they may in fact hold a
			// restricting row, so answer false for THIS call without memoising it: the next read
			// re-probes instead of inheriting a fabricated answer.
			if ( is_null( $table_exists ) ) {
				return apply_filters_ref_array( 'xprofile_visibility_user_data_exists', array( false, $user_id ) );
			}

			if ( $table_exists ) {
				$table_name_visibility = self::get_visibility_table_name();

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$retval = $wpdb->get_row(
					$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						"SELECT id FROM {$table_name_visibility} WHERE user_id = %d LIMIT 1",
						$user_id
					)
				);

				self::$user_data_exists_cache[ $user_id ] = ! empty( $retval );
			} else {
				self::$user_data_exists_cache[ $user_id ] = false;
			}
		}

		/**
		 * Filters whether any data already exists for the user.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param bool $retval  Whether data already exists.
		 * @param int  $user_id User id.
		 */
		return apply_filters_ref_array( 'xprofile_visibility_user_data_exists', array( self::$user_data_exists_cache[ $user_id ], $user_id ) );
	}

	/**
	 * Resolve the visibility table name.
	 *
	 * Falls back to the prefixed name when the xprofile globals have not been set up yet (a fresh
	 * install, before bp_setup_globals).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string
	 */
	public static function get_visibility_table_name() {
		$bp = buddypress();

		return ! empty( $bp->profile->table_name_visibility )
			? $bp->profile->table_name_visibility
			: bp_core_get_table_prefix() . 'bb_xprofile_visibility';
	}

	/**
	 * Whether the visibility table is present, resolved once per request.
	 *
	 * Three answers, not two, because "the table is not there" and "the probe did not answer" need
	 * opposite handling and are the same empty value coming out of wpdb. A MISSING table is the
	 * pre-migration shape of an install: no member has a row, visibility resolves from the
	 * `bp_xprofile_visibility_levels` user meta, and every caller should carry on reading that
	 * branch normally. A FAILED probe is not an answer at all, and reporting it as "missing" turns
	 * the per-member half of the name-search protection off for the whole request - silently, and
	 * on the one configuration every real community is in.
	 *
	 * So only a probe that SUCCEEDED is memoised. A failure returns null and leaves the memo
	 * unfilled, so the next call retries and a caller that must fail closed can tell the two apart -
	 * the same shape prime_user_data_exists_cache() and prime_user_field_ids_cache() already use
	 * when their own reads fail.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool|null True when the table exists, false when it is genuinely absent, null when
	 *                   the probe failed and the question is unanswered.
	 */
	public static function visibility_table_exists() {
		global $wpdb;

		if ( is_null( self::$table_exists_cache ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::get_visibility_table_name() ) );

			// wpdb::query() clears last_error through flush() before every statement, so this
			// reports on the probe just issued.
			if ( ! empty( $wpdb->last_error ) ) {
				return null;
			}

			self::$table_exists_cache = (bool) $found;
		}

		return self::$table_exists_cache;
	}

	/**
	 * Prime the per-request user_data_exists() memo for a batch of users in a single query.
	 *
	 * The single-user getter issues one uncached query per user, and
	 * bp_xprofile_get_fields_by_visibility_levels() calls it once per member whose visibility is
	 * resolved. Member search resolves one member per matched row, so on a term that matches a
	 * large part of the member table that probe alone is one query per match.
	 *
	 * Users with no row are memoized as false on purpose: without that they would miss the memo
	 * and fall through to an individual query each, which is the cost this exists to remove.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $user_ids User IDs to prime.
	 */
	public static function prime_user_data_exists_cache( $user_ids ) {
		global $wpdb;

		$user_ids = array_filter( array_map( 'intval', (array) $user_ids ) );

		if ( empty( $user_ids ) ) {
			return;
		}

		// Only query users that are not already memoized.
		$uncached_ids = array();
		foreach ( $user_ids as $user_id ) {
			if ( ! isset( self::$user_data_exists_cache[ $user_id ] ) ) {
				$uncached_ids[ $user_id ] = $user_id;
			}
		}

		if ( empty( $uncached_ids ) ) {
			return;
		}

		$table_exists = self::visibility_table_exists();

		// null is "the probe failed". Return without memoising so those reads fall through to their
		// own uncached path rather than to a fabricated "nobody has a row" - the same fail-closed
		// direction the failed-read branch below takes.
		if ( is_null( $table_exists ) ) {
			return;
		}

		if ( ! $table_exists ) {
			foreach ( $uncached_ids as $user_id ) {
				self::$user_data_exists_cache[ $user_id ] = false;
			}

			return;
		}

		$table_name_visibility = self::get_visibility_table_name();
		$user_ids_sql          = implode( ',', $uncached_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- the id list is built from intval()ed values.
		$with_rows = $wpdb->get_col(
			"SELECT DISTINCT user_id FROM {$table_name_visibility} WHERE user_id IN ( {$user_ids_sql} )"
		);

		// A failed query returns the same empty result as "nobody has a row", and memoising that
		// would tell every caller the member resolves from user meta when they may in fact hold a
		// restricting row - a member whose name field is hidden would then be judged on meta they
		// never wrote and served. Leave the memo unfilled so those reads fall through to their own
		// uncached path, the same fail-closed direction prime_user_field_ids_cache() takes.
		if ( ! empty( $wpdb->last_error ) ) {
			return;
		}

		$with_rows = array_flip( array_map( 'intval', (array) $with_rows ) );

		foreach ( $uncached_ids as $user_id ) {
			self::$user_data_exists_cache[ $user_id ] = isset( $with_rows[ $user_id ] );
		}
	}

	/**
	 * Check if this data is for a valid field.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return bool
	 */
	public function is_valid_field() {
		global $wpdb;
		$table = bp_core_get_table_prefix() . 'bp_xprofile_fields';

		$cache_key = 'bp_xprofile_is_valid_field_' . $this->field_id;
		$retval    = wp_cache_get( $cache_key, 'bp_xprofile' );

		if ( false === $retval ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$retval = $wpdb->get_row(
				$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery
					"SELECT id FROM {$table} WHERE id = %d",
					$this->field_id
				)
			);
			wp_cache_set( $cache_key, $retval, 'bp_xprofile' );
		}

		/**
		 * Filters whether data is for a valid field.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param bool                   $retval Whether data is valid.
		 * @param BB_XProfile_Visibility $this   Instance of the current BB_XProfile_Visibility class.
		 */
		return apply_filters_ref_array( 'xprofile_visibility_is_valid_field', array( (bool) $retval, $this ) );
	}

	/**
	 * Save the data for the XProfile field.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @return bool
	 */
	public function save() {
		global $wpdb;

		$bp = buddypress();

		/**
		 * Filters the data's user ID before saving to the database.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param int $user_id The user ID.
		 * @param int $data_id The field data ID.
		 */
		$this->user_id = apply_filters( 'xprofile_visibility_user_id_before_save', $this->user_id, $this->id );

		/**
		 * Filters the data's field ID before saving to the database.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param int $field_id The field ID.
		 * @param int $data_id  The field data ID.
		 */
		$this->field_id = apply_filters( 'xprofile_visibility_field_id_before_save', $this->field_id, $this->id );

		/**
		 * Filters the data's value before saving to the database.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param string                 $field_value The field value.
		 * @param int                    $data_id     The field data ID.
		 * @param bool                   $reserialize Whether to reserialize arrays before returning. Defaults to true.
		 * @param BB_XProfile_Visibility $this        Current instance of the profile data being saved.
		 */
		$this->value = apply_filters( 'xprofile_visibility_value_before_save', $this->value, $this->id, true, $this );

		/**
		 * Filters the data's last updated timestamp before saving to the database.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param int $last_updated The last updated timestamp.
		 * @param int $data_id      The field data ID.
		 */
		$this->last_updated = apply_filters( 'xprofile_visibility_last_updated_before_save', bp_core_current_time(), $this->id );

		/**
		 * Fires before the current profile data instance gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param BB_XProfile_Visibility $this Current instance of the profile data being saved.
		 */
		do_action_ref_array( 'xprofile_visibility_before_save', array( $this ) );

		if ( $this->is_valid_field() ) {
			// Data exists, update it.
			if ( $this->exists() && strlen( trim( $this->value ) ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore
						"UPDATE {$bp->profile->table_name_visibility} SET value = %s, last_updated = %s WHERE user_id = %d AND field_id = %d", $this->value, $this->last_updated, $this->user_id, $this->field_id
					)
				);
			} elseif ( $this->exists() && empty( $this->value ) ) {
				// Data removed, delete the entry.
				$result = $this->delete();

			} else {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$result = $wpdb->query(
					$wpdb->prepare(
					// phpcs:ignore
						"INSERT INTO {$bp->profile->table_name_visibility} (user_id, field_id, value, last_updated) VALUES (%d, %d, %s, %s)", $this->user_id, $this->field_id, $this->value, $this->last_updated
					)
				);
				$this->id = $wpdb->insert_id;
			}

			if ( false === $result ) {
				return false;
			}

			self::flush_field_ids_cache( (int) $this->user_id );

			/**
			 * Fires after the current profile data instance gets saved.
			 *
			 * @since BuddyBoss 2.6.50
			 *
			 * @param BB_XProfile_Visibility $this Current instance of the profile data being saved.
			 */
			do_action_ref_array( 'xprofile_visibility_after_save', array( $this ) );

			return true;
		}

		return false;
	}

	/**
	 * Delete specific XProfile field data.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;

		$bp         = buddypress();
		$table_name = $bp->profile->table_name_visibility;

		/**
		 * Fires before the current profile data instance gets deleted.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param BB_XProfile_Visibility $this Current instance of the profile data being deleted.
		 */
		do_action_ref_array( 'xprofile_visibility_before_delete', array( $this ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
			// phpcs:ignore
				"DELETE FROM {$table_name} WHERE field_id = %d AND user_id = %d", $this->field_id, $this->user_id
			)
		);
		if ( empty( $deleted ) ) {
			return false;
		}

		self::flush_field_ids_cache( (int) $this->user_id );

		/**
		 * Fires after the current profile data instance gets deleted.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param BB_XProfile_Visibility $this Current instance of the profile data being deleted.
		 */
		do_action_ref_array( 'xprofile_visibility_after_delete', array( $this ) );

		return true;
	}

	/**
	 * Delete field.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @param int $field_id ID of the field to delete.
	 *
	 * @return bool
	 */
	public static function delete_for_field( $field_id ) {
		global $wpdb;

		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare(
			// phpcs:ignore
				"DELETE FROM {$bp->profile->table_name_visibility} WHERE field_id = %d", $field_id
			)
		);

		if ( empty( $deleted ) || is_wp_error( $deleted ) ) {
			return false;
		}

		// Affects rows across all users - clear the whole memo.
		self::flush_field_ids_cache();

		return true;
	}

	/**
	 * Delete all data for provided user ID.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @param int $user_id User ID to remove data for.
	 *
	 * @return false|int
	 */
	public static function delete_data_for_user( $user_id ) {
		global $wpdb;

		$bp = buddypress();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$field_ids = $wpdb->get_col(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT field_id FROM {$bp->profile->table_name_visibility} WHERE user_id = %d",
				$user_id
			)
		);

		if ( ! $field_ids ) {
			return false;
		}

		foreach ( $field_ids as $field_id ) {
			xprofile_delete_field_data( $field_id, $user_id );
		}

		self::flush_field_ids_cache( (int) $user_id );

		return count( $field_ids );
	}

	/**
	 * Delete specific field for specific user.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param int $field_id ID of the field to delete.
	 * @param int $user_id  ID of the user whose field is to be deleted.
	 *
	 * @return bool
	 */
	public static function delete_specific_data_for_user( $field_id, $user_id ) {
		global $wpdb;

		$bp      = buddypress();
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_visibility} WHERE field_id = %d AND user_id = %d", $field_id, $user_id ) );
		if ( empty( $deleted ) || is_wp_error( $deleted ) ) {
			return false;
		}

		self::flush_field_ids_cache( (int) $user_id );

		return true;
	}

	/**
	 * Get the XProfile profile visibility data.
	 *
	 * @since BuddyBoss 2.6.50
	 *
	 * @param int   $user_id User ID to get fields for.
	 * @param array $levels  Visibility levels to get fields for.
	 *
	 * @return array Associative array with field_id as key and value.
	 */
	public static function get_user_field_ids_by_visibility_levels( $user_id, $levels = array() ) {
		global $wpdb;

		$bp     = buddypress();
		$fields = array();

		if ( empty( $user_id ) || empty( $levels ) ) {
			return $fields;
		}

		// Per-request memo: the same (user, levels) pair is resolved many times per request and
		// the query below is uncached. Key on the sorted levels so equivalent level sets hit.
		$sorted_levels = $levels;
		sort( $sorted_levels );
		$cache_key = (int) $user_id . ':' . sha1( implode( ',', $sorted_levels ) );
		if ( isset( self::$field_ids_cache[ $cache_key ] ) ) {
			return self::$field_ids_cache[ $cache_key ];
		}

		// Prepare the levels array by quoting each element.
		$quoted_levels = array_map(
			function ( $level ) {
				global $wpdb;

				return $wpdb->prepare( '%s', $level );
			},
			$levels
		);

		$quoted_levels = implode( ',', $quoted_levels );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT DISTINCT field_id FROM {$bp->profile->table_name_visibility} WHERE user_id = %d AND value IN ( $quoted_levels )",
				$user_id
			),
			OBJECT_K
		);

		// Iterate over the results and transform the array.
		if ( ! empty( $results ) ) {
			foreach ( $results as $field_id => $result ) {
				$fields[ $field_id ] = $field_id;
			}
		}

		self::$field_ids_cache[ $cache_key ] = $fields;

		return $fields;
	}

	/**
	 * Prime the per-request field-ids memo for a batch of users in a single query.
	 *
	 * The single-user getter issues one uncached query per user. Resolving a
	 * member loop, an activity stream or a REST collection asks it once per row, so a directory of
	 * 50 members costs 50 queries before any name is rendered. This fills the same memo the getter
	 * reads, keyed identically, so the per-row calls become array lookups.
	 *
	 * Users with no matching row are memoized as an empty array on purpose: without that they would
	 * miss the memo and fall through to an individual query each, which is the cost this exists to
	 * remove.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $user_ids User IDs to prime.
	 * @param array $levels   Visibility levels to prime for. Must be the same set the subsequent
	 *                        get_user_field_ids_by_visibility_levels() calls will pass, or those
	 *                        calls simply miss the memo and behave as they do today.
	 */
	public static function prime_field_ids_cache( $user_ids, $levels = array() ) {
		global $wpdb;

		$user_ids = array_filter( array_map( 'intval', (array) $user_ids ) );
		$levels   = array_filter( (array) $levels );

		if ( empty( $user_ids ) || empty( $levels ) ) {
			return;
		}

		$sorted_levels = $levels;
		sort( $sorted_levels );
		$key_suffix = ':' . sha1( implode( ',', $sorted_levels ) );

		// Only query users that are not already memoized.
		$uncached_ids = array();
		foreach ( $user_ids as $user_id ) {
			if ( ! isset( self::$field_ids_cache[ $user_id . $key_suffix ] ) ) {
				$uncached_ids[ $user_id ] = $user_id;
			}
		}

		if ( empty( $uncached_ids ) ) {
			return;
		}

		// A missing table - and a probe that failed, which reports null - returns the same empty
		// result as "nobody restricted anything", and the memo below is read back as authoritative
		// by the per-user getter. Return without memoising so those reads fall through to their own
		// uncached path instead of to a fabricated answer - the same fail-closed direction
		// user_data_exists() and prime_user_data_exists_cache() take.
		if ( true !== self::visibility_table_exists() ) {
			return;
		}

		$table_name_visibility = self::get_visibility_table_name();

		$quoted_levels = implode(
			',',
			array_map(
				function ( $level ) use ( $wpdb ) {
					return $wpdb->prepare( '%s', $level );
				},
				$levels
			)
		);

		$user_ids_sql = implode( ',', $uncached_ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- levels are prepared above, ids are ints.
		$results = $wpdb->get_results(
			"SELECT DISTINCT user_id, field_id FROM {$table_name_visibility} WHERE user_id IN ( {$user_ids_sql} ) AND value IN ( {$quoted_levels} )"
		);

		// wpdb::get_results() initialises its return to array() and never hands back null, so an
		// error and an empty result set are the same value; ask $wpdb directly, as
		// bb_xprofile_filter_possible_hidden_users() does for the same reason.
		if ( ! empty( $wpdb->last_error ) ) {
			return;
		}

		$grouped = array();
		foreach ( (array) $results as $row ) {
			$grouped[ (int) $row->user_id ][ (int) $row->field_id ] = (int) $row->field_id;
		}

		foreach ( $uncached_ids as $user_id ) {
			self::$field_ids_cache[ $user_id . $key_suffix ] = isset( $grouped[ $user_id ] ) ? $grouped[ $user_id ] : array();
		}
	}

	/**
	 * Invalidate the per-request visibility memos.
	 *
	 * Called by every writer that changes rows in the visibility table so a read that follows a
	 * write in the same request never returns a stale result. Clears both memos this class keeps:
	 * the field-ids lookup and the user_data_exists() probe. The latter matters because a write
	 * can create a member's FIRST visibility row, flipping that answer - and because
	 * prime_user_data_exists_cache() now fills it for whole batches, so a stale entry is far more
	 * likely to be present than when it was populated one lazy read at a time.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $user_id Optional. Clear only this user's entries; 0 clears the whole memo (used
	 *                     when a write affects rows across all users).
	 */
	public static function flush_field_ids_cache( $user_id = 0 ) {
		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			self::$field_ids_cache        = array();
			self::$user_data_exists_cache = array();

			return;
		}

		unset( self::$user_data_exists_cache[ $user_id ] );

		$prefix = $user_id . ':';
		foreach ( array_keys( self::$field_ids_cache ) as $key ) {
			if ( 0 === strpos( $key, $prefix ) ) {
				unset( self::$field_ids_cache[ $key ] );
			}
		}
	}

}
