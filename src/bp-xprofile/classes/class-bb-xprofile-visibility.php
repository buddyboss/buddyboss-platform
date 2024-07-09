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
		$bp = buddypress();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bp->profile->table_name_visibility ) );
		if ( $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$retval = $wpdb->get_row(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					"SELECT id FROM {$bp->profile->table_name_visibility} WHERE user_id = %d limit 0, 1",
					$user_id
				)
			);
		} else {
			$retval = false;
		}

		/**
		 * Filters whether any data already exists for the user.
		 *
		 * @since BuddyBoss 2.6.50
		 *
		 * @param bool $retval  Whether data already exists.
		 * @param int  $user_id User id.
		 */
		return apply_filters_ref_array( 'xprofile_visibility_user_data_exists', array( ! empty( $retval ), $user_id ) );
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

		return $fields;
	}

}
