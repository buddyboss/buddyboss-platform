<?php
/**
 * BuddyPress XProfile Group Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class to help set up XProfile Groups.
 *
 * @since BuddyPress 1.0.0
 */
#[AllowDynamicProperties]
class BP_XProfile_Group {

	/**
	 * Field group ID.
	 *
	 * @since BuddyPress 1.1.0
	 * @var int ID of field group.
	 */
	public $id = null;

	/**
	 * Field group name.
	 *
	 * @since BuddyPress 1.1.0
	 * @var string Name of field group.
	 */
	public $name;

	/**
	 * Field group Description.
	 *
	 * @since BuddyPress 1.1.0
	 * @var string Description of field group.
	 */
	public $description;

	/**
	 * Group deletion boolean.
	 *
	 * @since BuddyPress 1.1.0
	 * @var bool Can this group be deleted?
	 */
	public $can_delete;

	/**
	 * Group order.
	 *
	 * @since BuddyPress 1.1.0
	 * @var int Group order relative to other groups.
	 */
	public $group_order;

	/**
	 * Group fields.
	 *
	 * @since BuddyPress 1.1.0
	 * @var array Fields of group.
	 */
	public $fields;

	/**
	 * Static cache key for the groups.
	 *
	 * @since BuddyBoss 2.1.6
	 *
	 * @var array Fields of cache.
	 */
	public static $bp_xprofile_group_ids = array();

	/**
	 * Initialize and/or populate profile field group.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param int|null $id Field group ID.
	 */
	public function __construct( $id = null ) {
		if ( ! empty( $id ) ) {
			$this->populate( $id );
		}
	}

	/**
	 * Populate a profile field group.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $id Field group ID.
	 * @return boolean
	 */
	public function populate( $id ) {

		// Get this group.
		$group = self::get(
			array(
				'profile_group_id' => $id,
			)
		);

		// Bail if group not found.
		if ( empty( $group ) ) {
			return false;
		}

		// Get the first array element.
		$group = reset( $group );

		// Set object properties.
		$this->id          = $group->id;
		$this->name        = $group->name;
		$this->description = $group->description;
		$this->can_delete  = $group->can_delete;
		$this->group_order = $group->group_order;
	}

	/**
	 * Save a profile field group.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return boolean
	 */
	public function save() {
		global $wpdb;

		// Filter the field group attributes.
		$this->name        = apply_filters( 'xprofile_group_name_before_save', $this->name, $this->id );
		$this->description = apply_filters( 'xprofile_group_description_before_save', $this->description, $this->id );

		/**
		 * Fires before the current group instance gets saved.
		 *
		 * Please use this hook to filter the properties above. Each part will be passed in.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_XProfile_Group $this Current instance of the group being saved. Passed by reference.
		 */
		do_action_ref_array( 'xprofile_group_before_save', array( &$this ) );

		$bp = buddypress();

		// Update or insert.
		if ( ! empty( $this->id ) ) {
			$sql = $wpdb->prepare( "UPDATE {$bp->profile->table_name_groups} SET name = %s, description = %s WHERE id = %d", $this->name, $this->description, $this->id );
		} else {
			$sql = $wpdb->prepare( "INSERT INTO {$bp->profile->table_name_groups} (name, description, can_delete) VALUES (%s, %s, 1)", $this->name, $this->description );
		}

		// Attempt to insert or update.
		$query = $wpdb->query( $sql );

		// Bail if query fails. If `$query` is 0, it means the save was successful, but no fields were updated.
		if ( false === $query || is_wp_error( $query ) ) {
			return false;
		}

		// If not set, update the ID in the group object.
		if ( empty( $this->id ) ) {
			$this->id = $wpdb->insert_id;
		}

		// Save metadata
		$repeater_enabled = isset( $_POST['group_is_repeater'] ) && 'on' == $_POST['group_is_repeater'] ? 'on' : 'off';
		self::update_group_meta( $this->id, 'is_repeater_enabled', $repeater_enabled );

		/**
		 * Fires after the current group instance gets saved.
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param BP_XProfile_Group $this Current instance of the group being saved. Passed by reference.
		 */
		do_action_ref_array( 'xprofile_group_after_save', array( &$this ) );

		return $this->id;
	}

	/**
	 * Delete a profile field group
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return boolean
	 */
	public function delete() {
		global $wpdb;

		// Bail if field group cannot be deleted.
		if ( empty( $this->can_delete ) ) {
			return false;
		}

		/**
		 * Fires before the current group instance gets deleted.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param BP_XProfile_Group $this Current instance of the group being deleted. Passed by reference.
		 */
		do_action_ref_array( 'xprofile_group_before_delete', array( &$this ) );

		$bp      = buddypress();
		$sql     = $wpdb->prepare( "DELETE FROM {$bp->profile->table_name_groups} WHERE id = %d", $this->id );
		$deleted = $wpdb->query( $sql );

		// Delete field group.
		if ( empty( $deleted ) || is_wp_error( $deleted ) ) {
			return false;
		}

		// Remove the group's fields.
		if ( BP_XProfile_Field::delete_for_group( $this->id ) ) {

			// Remove profile data for the groups fields.
			if ( ! empty( $this->fields ) ) {
				for ( $i = 0, $count = count( $this->fields ); $i < $count; ++$i ) {
					BP_XProfile_ProfileData::delete_for_field( $this->fields[ $i ]->id );
				}
			}
		}

		/**
		 * Fires after the current group instance gets deleted.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param BP_XProfile_Group $this Current instance of the group being deleted. Passed by reference.
		 */
		do_action_ref_array( 'xprofile_group_after_delete', array( &$this ) );

		return true;
	}

	/** Static Methods ********************************************************/

	/**
	 * Populates the BP_XProfile_Group object with profile field groups, fields,
	 * and field data.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.4.0 Introduced `$member_type` argument.
	 * @since BuddyPress 11.0.0 `$profile_group_id` accepts an array of profile group ids.
	 * @since BuddyBoss 2.3.90 `$profile_group_id` accepts an array of profile group ids.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array $args {
	 *  Array of optional arguments:
	 *      @type int|int[]|bool $profile_group_id   Limit results to a single profile group.
	 *      @type int            $user_id            Required if you want to load a specific user's data.
	 *                                               Default: displayed user's ID.
	 *      @type array|string   $member_type        Limit fields by those restricted to a given profile type, or array of
	 *                                               profile types. If `$user_id` is provided, the value of `$member_type`
	 *                                               will be overridden by the profile types of the provided user. The
	 *                                               special value of 'any' will return only those fields that are
	 *                                               unrestricted by profile type - i.e., those applicable to any type.
	 *      @type bool           $hide_empty_groups  True to hide groups that don't have any fields. Default: false.
	 *      @type bool           $hide_empty_fields  True to hide fields where the user has not provided data.
	 *                                               Default: false.
	 *      @type bool           $fetch_fields       Whether to fetch each group's fields. Default: false.
	 *      @type bool           $fetch_field_data   Whether to fetch data for each field. Requires a $user_id.
	 *                                               Default: false.
	 *      @type array          $exclude_groups     Comma-separated list or array of group IDs to exclude.
	 *      @type array          $exclude_fields     Comma-separated list or array of field IDs to exclude.
	 *      @type array          $includ_fields      Comma-separated list or array of field IDs to include.
	 *      @type bool           $update_meta_cache  Whether to pre-fetch xprofilemeta for all retrieved groups, fields,
	 *                                               and data. Default: true.
	 *  }
	 * @return array $groups
	 */
	public static function get( $args = array() ) {
		static $bp_xprofile_field_ids = array();
		global $wpdb;

		// Parse arguments.
		$r = bp_parse_args(
			$args,
			array(
				'profile_group_id'               => false,
				'user_id'                        => bp_displayed_user_id(),
				'member_type'                    => false,
				'hide_empty_groups'              => false,
				'hide_empty_fields'              => false,
				'fetch_fields'                   => false,
				'fetch_field_data'               => false,
				'fetch_visibility_level'         => false,
				'exclude_groups'                 => false,
				'exclude_fields'                 => false,
				'include_fields'                 => false,
				'update_meta_cache'              => true,
				'repeater_show_main_fields_only' => false,
				'fetch_social_network_fields'    => false,
			)
		);

		// Keep track of object IDs for cache-priming.
		$object_ids = array(
			'group' => array(),
			'field' => array(),
			'data'  => array(),
		);

		// WHERE.
		if ( ! empty( $r['profile_group_id'] ) && ! is_bool( $r['profile_group_id'] ) ) {
			$profile_group_ids = join( ',', wp_parse_id_list( $r['profile_group_id'] ) );
			$where_sql         = "WHERE g.id IN ({$profile_group_ids})";
		} elseif ( $r['exclude_groups'] ) {
			$exclude   = join( ',', wp_parse_id_list( $r['exclude_groups'] ) );
			$where_sql = "WHERE g.id NOT IN ({$exclude})";
		} else {
			$where_sql = '';
		}

		$bp = buddypress();

		// Include or exclude empty groups.
		$cache_key = 'bp_xprofile_group_ids_' . md5( maybe_serialize( $r ) . '_' . maybe_serialize( $where_sql ) );
		if ( ! isset( self::$bp_xprofile_group_ids[ $cache_key ] ) ) {
			if ( ! empty( $r['hide_empty_groups'] ) ) {
				$group_ids = $wpdb->get_col( "SELECT DISTINCT g.id FROM {$bp->profile->table_name_groups} g INNER JOIN {$bp->profile->table_name_fields} f ON g.id = f.group_id {$where_sql} ORDER BY g.group_order ASC" );
			} else {
				$group_ids = $wpdb->get_col( "SELECT DISTINCT g.id FROM {$bp->profile->table_name_groups} g {$where_sql} ORDER BY g.group_order ASC" );
			}
			self::$bp_xprofile_group_ids[ $cache_key ] = $group_ids;
		} else {
			$group_ids = self::$bp_xprofile_group_ids[ $cache_key ];
		}

		// Get all group data.
		$groups = self::get_group_data( $group_ids );

		// Bail if not also getting fields.
		if ( empty( $r['fetch_fields'] ) ) {
			return $groups;
		}

		// Get the group ids from the groups we found.
		$group_ids = wp_list_pluck( $groups, 'id' );

		// Store for meta cache priming.
		$object_ids['group'] = $group_ids;

		// Bail if no groups found.
		if ( empty( $group_ids ) ) {
			return $groups;
		}

		// Setup IN query from group IDs.
		$group_ids_in = implode( ',', (array) $group_ids );

		// Support arrays and comma-separated strings.
		$exclude_fields_cs = wp_parse_id_list( $r['exclude_fields'] );

		/**
		 * For each group, check if it is a repeater set.
		 * If so,
		 *  - get the number of sets, user has created.
		 *  - create as many set of clones for each such field in the group, if not created already
		 *  - include those field ids in loop
		 */
		foreach ( $group_ids as $group_id ) {
			$is_repeater_enabled = 'on' == self::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
			if ( $is_repeater_enabled ) {
				$clone_field_ids_all = bp_get_repeater_clone_field_ids_all( $group_id );

				if ( $r['repeater_show_main_fields_only'] ) {
					// exclude clones
					$exclude_fields_cs = array_merge( $exclude_fields_cs, $clone_field_ids_all );
				} else {
					// exclude template fields
					$template_field_ids = bp_get_repeater_template_field_ids( $group_id );
					$exclude_fields_cs  = array_merge( $exclude_fields_cs, $template_field_ids );

					// include only the subset of clones the current user has data in
					$user_field_set_count     = bp_get_profile_field_set_count( $group_id, $r['user_id'] );
					$clone_field_ids_has_data = bp_get_repeater_clone_field_ids_subset( $group_id, $user_field_set_count );
					$clones_to_exclude        = array_diff( $clone_field_ids_all, $clone_field_ids_has_data );

					$exclude_fields_cs = array_merge( $exclude_fields_cs, $clones_to_exclude );
				}
			}
		}

		// Visibility - Handled here so as not to be overridden by sloppy use of the
		// exclude_fields parameter. See bp_xprofile_get_hidden_fields_for_user().
		$hidden_user_fields = bp_xprofile_get_hidden_fields_for_user( $r['user_id'] );
		$exclude_fields_cs  = array_merge( $exclude_fields_cs, $hidden_user_fields );
		$exclude_fields_cs  = implode( ',', $exclude_fields_cs );

		// Set up NOT IN query for excluded field IDs.
		if ( ! empty( $exclude_fields_cs ) ) {
			$exclude_fields_sql = "AND id NOT IN ({$exclude_fields_cs})";
		} else {
			$exclude_fields_sql = '';
		}

		// Set up IN query for included field IDs.
		$include_field_ids = array();

		// Member-type restrictions.
		if ( bp_get_member_types() ) {
			if ( $r['user_id'] || false !== $r['member_type'] ) {
				$member_types = $r['member_type'];
				if ( $r['user_id'] ) {
					$member_types = bp_get_member_type( $r['user_id'], false );
					if ( empty( $member_types ) ) {
						$member_types = array( 'null' );
					}
				}

				$member_types_fields = BP_XProfile_Field::get_fields_for_member_type( $member_types );
				$include_field_ids  += array_keys( $member_types_fields );
			}
		}

		// Add include fields.
		if ( ! empty( $r['include_fields'] ) && ! empty( $include_field_ids ) ) {
			$include_field_ids = array_merge( $include_field_ids, array_map( 'intval', explode( ',', $r['include_fields'] ) ) );
		}

		$in_sql = '';
		if ( ! empty( $include_field_ids ) ) {
			$include_field_ids_cs = implode( ',', array_map( 'intval', $include_field_ids ) );
			$in_sql               = " AND id IN ({$include_field_ids_cs}) ";
		}

		// Fetch the fields.
		$cache_field_key = 'bp_xprofile_field_ids_' . md5( maybe_serialize( $r ) );
		if ( ! isset( $bp_xprofile_field_ids[ $cache_field_key ] ) || is_admin() ) {
			$field_ids                           = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields} WHERE group_id IN ( {$group_ids_in} ) AND parent_id = 0 {$exclude_fields_sql} {$in_sql} ORDER BY field_order" );
			$bp_xprofile_field_ids[ $cache_field_key ] = $field_ids;
		} else {
			$field_ids = $bp_xprofile_field_ids[ $cache_field_key ];
		}

		foreach ( $groups as $group ) {
			$group->fields = array();
		}

		// Bail if no fields.
		if ( empty( $field_ids ) ) {
			return $groups;
		}

		$field_ids = array_map( 'intval', $field_ids );

		// Start Remove the social network fields from dashboard page.
		$social_networks_key = 0;
		if ( bp_is_user_profile() && ! bp_is_user_profile_edit() && ! bp_is_register_page() ) {
			foreach ( $field_ids as $profile_fields ) {
				$field_obj = xprofile_get_field( $profile_fields, null, false );
				if ( 'socialnetworks' === $field_obj->type ) {
					$social_networks_key = (int) $profile_fields;
				}
			}
		}

		// If social networks field found then remove from the $field_ids array.
		if ( $social_networks_key > 0 && empty( $r['fetch_social_network_fields'] ) ) {
			if ( ( $key = array_search( $social_networks_key, $field_ids ) ) !== false ) {
				unset( $field_ids[ $key ] );
			}
		}

		// End Remove the social network fields from dashboard page.

		// Prime the field cache.
		$uncached_field_ids = bp_get_non_cached_ids( $field_ids, 'bp_xprofile_fields' );
		if ( ! empty( $uncached_field_ids ) ) {
			$_uncached_field_ids = implode( ',', array_map( 'intval', $uncached_field_ids ) );
			$uncached_fields     = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id IN ({$_uncached_field_ids})" );
			foreach ( $uncached_fields as $uncached_field ) {
				$fid = intval( $uncached_field->id );
				wp_cache_set( $fid, $uncached_field, 'bp_xprofile_fields' );
			}
		}

		// Pull field objects from the cache.
		$fields = array();
		foreach ( $field_ids as $field_id ) {
			$fields[] = xprofile_get_field( $field_id, null, false );
		}

		// Store field IDs for meta cache priming.
		$object_ids['field'] = $field_ids;

		// Maybe fetch field data.
		if ( ! empty( $r['fetch_field_data'] ) ) {

			// Get field data for user ID.
			if ( ! empty( $field_ids ) && ! empty( $r['user_id'] ) ) {
				$field_data = BP_XProfile_ProfileData::get_data_for_user( $r['user_id'], $field_ids );
			}

			// Remove data-less fields, if necessary.
			if ( ! empty( $r['hide_empty_fields'] ) && ! empty( $field_ids ) && ! empty( $field_data ) ) {

				// Loop through the results and find the fields that have data.
				foreach ( (array) $field_data as $data ) {

					// Empty fields may contain a serialized empty array.
					$maybe_value = maybe_unserialize( $data->value );

					// Valid field values of 0 or '0' get caught by empty(), so we have an extra check for these. See #BP5731.
					if ( ( ! empty( $maybe_value ) || '0' == $maybe_value ) && false !== $key = array_search( $data->field_id, $field_ids ) ) {

						// Fields that have data get removed from the list.
						unset( $field_ids[ $key ] );
					}
				}

				// The remaining members of $field_ids are empty. Remove them.
				foreach ( $fields as $field_key => $field ) {
					if ( in_array( $field->id, $field_ids ) ) {
						unset( $fields[ $field_key ] );
					}
				}

				// Reset indexes.
				$fields = array_values( $fields );
			}

			// Field data was found.
			if ( ! empty( $fields ) && ! empty( $field_data ) && ! is_wp_error( $field_data ) ) {

				// Loop through fields.
				foreach ( (array) $fields as $field_key => $field ) {

					// Loop through the data in each field.
					foreach ( (array) $field_data as $data ) {

						// Assign correct data value to the field.
						if ( $field->id == $data->field_id ) {
							$fields[ $field_key ]->data        = new stdClass();
							$fields[ $field_key ]->data->value = $data->value;
							$fields[ $field_key ]->data->id    = $data->id;
						}

						// Store for meta cache priming.
						$object_ids['data'][] = $data->id;
					}
				}
			}
		}

		// Prime the meta cache, if necessary.
		if ( ! empty( $r['update_meta_cache'] ) ) {
			bp_xprofile_update_meta_cache( $object_ids );
		}

		// Maybe fetch visibility levels.
		if ( ! empty( $r['fetch_visibility_level'] ) ) {
			$fields = self::fetch_visibility_level( $r['user_id'], $fields );
		}

		// Merge the field array back in with the group array.
		foreach ( (array) $groups as $group ) {
			// Indexes may have been shifted after previous deletions, so we get a
			// fresh one each time through the loop.
			$index = array_search( $group, $groups );

			foreach ( (array) $fields as $field ) {
				if ( $group->id === $field->group_id ) {
					$groups[ $index ]->fields[] = $field;
				}
			}

			// When we unset fields above, we may have created empty groups.
			// Remove them, if necessary.
			if ( empty( $group->fields ) && ! empty( $r['hide_empty_groups'] ) ) {
				unset( $groups[ $index ] );
			}

			// Reset indexes.
			$groups = array_values( $groups );
		}

		return $groups;
	}

	/**
	 * Get data about a set of groups, based on IDs.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $group_ids Array of IDs.
	 * @return array
	 */
	protected static function get_group_data( $group_ids ) {
		global $wpdb;

		// Bail if no group IDs are passed.
		if ( empty( $group_ids ) ) {
			return array();
		}

		// Setup empty arrays.
		$groups        = array();
		$uncached_gids = array();

		// Loop through groups and look for cached & uncached data.
		foreach ( $group_ids as $group_id ) {

			// If cached data is found, use it.
			$group_data = wp_cache_get( $group_id, 'bp_xprofile_groups' );
			if ( false !== $group_data ) {
				$groups[ $group_id ] = $group_data;

				// Otherwise leave a placeholder so we don't lose the order.
			} else {
				$groups[ $group_id ] = '';

				// Add to the list of items to be queried.
				$uncached_gids[] = $group_id;
			}
		}

		// Fetch uncached data from the DB if necessary.
		if ( ! empty( $uncached_gids ) ) {

			// Setup IN query for uncached group data.
			$uncached_gids_sql = implode( ',', wp_parse_id_list( $uncached_gids ) );

			// Get table name to query.
			$table_name = buddypress()->profile->table_name_groups;

			// Fetch data, preserving order.
			$queried_gdata = $wpdb->get_results( "SELECT * FROM {$table_name} WHERE id IN ({$uncached_gids_sql}) ORDER BY FIELD( id, {$uncached_gids_sql} )" );

			// Make sure query returned valid data.
			if ( ! empty( $queried_gdata ) && ! is_wp_error( $queried_gdata ) ) {

				// Put queried data into the placeholders created earlier,
				// and add it to the cache.
				foreach ( (array) $queried_gdata as $gdata ) {

					// Add group to groups array.
					$groups[ $gdata->id ] = $gdata;

					// Cache previously uncached group data.
					wp_cache_set( $gdata->id, $gdata, 'bp_xprofile_groups' );
				}
			}
		}

		// Integer casting.
		foreach ( (array) $groups as $key => $data ) {
			$groups[ $key ]->id          = (int) $groups[ $key ]->id;
			$groups[ $key ]->group_order = (int) $groups[ $key ]->group_order;
			$groups[ $key ]->can_delete  = (int) $groups[ $key ]->can_delete;
		}

		// Reset indexes & return.
		return array_values( $groups );
	}

	public static function get_group_meta( $group_id, $meta_key = '', $single = true ) {
		return bp_xprofile_get_meta( $group_id, 'group', $meta_key, $single );
	}

	public static function update_group_meta( $group_id, $meta_key, $meta_value, $prev_value = '' ) {
		return bp_xprofile_update_meta( $group_id, 'group', $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Validate field group when form submitted.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @global string $message
	 *
	 * @return boolean
	 */
	public static function admin_validate() {
		global $message;

		// Validate Form.
		if ( empty( $_POST['group_name'] ) ) {
			$message = __( 'Please make sure you give the group a name.', 'buddyboss' );
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Update field group position.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param  int $field_group_id ID of the group the field belongs to.
	 * @param  int $position       Field group position.
	 * @return boolean
	 */
	public static function update_position( $field_group_id, $position ) {
		global $wpdb;

		if ( ! is_numeric( $position ) ) {
			return false;
		}

		// Purge profile field group cache.
		wp_cache_delete( 'all', 'bp_xprofile_groups' );

		$bp = buddypress();

		return $wpdb->query( $wpdb->prepare( "UPDATE {$bp->profile->table_name_groups} SET group_order = %d WHERE id = %d", $position, $field_group_id ) );
	}

	/**
	 * Fetch the field visibility level for the fields returned by the query.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param int   $user_id The profile owner's user_id.
	 * @param array $fields  The database results returned by the get() query.
	 * @return array $fields The database results, with field_visibility added
	 */
	public static function fetch_visibility_level( $user_id = 0, $fields = array() ) {

		// Get the user's visibility level preferences.
		$visibility_levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );

		foreach ( (array) $fields as $key => $field ) {

			// Does the admin allow this field to be customized?
			$visibility   = bp_xprofile_get_meta( $field->id, 'field', 'allow_custom_visibility' );
			$allow_custom = (bool) ( 'disabled' !== $visibility );

			// Look to see if the user has set the visibility for this field.
			if ( ( true === $allow_custom ) && isset( $visibility_levels[ $field->id ] ) ) {
				$field_visibility = $visibility_levels[ $field->id ];

				// If no admin-set default is saved, fall back on a global default.
			} else {
				$fallback_visibility = bp_xprofile_get_meta( $field->id, 'field', 'default_visibility' );

				/**
				 * Filters the XProfile default visibility level for a field.
				 *
				 * @since BuddyPress 1.6.0
				 *
				 * @param string $value Default visibility value.
				 */
				$field_visibility = ! empty( $fallback_visibility )
					? $fallback_visibility
					: apply_filters( 'bp_xprofile_default_visibility_level', 'public' );
			}

			$fields[ $key ]->visibility_level = $field_visibility;
		}

		return $fields;
	}

	/**
	 * Fetch the admin-set preferences for all fields.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @return array $default_visibility_levels An array, keyed by field_id, of default
	 *                                          visibility level + allow_custom
	 *                                          (whether the admin allows this
	 *                                          field to be set by user)
	 */
	public static function fetch_default_visibility_levels() {
		global $wpdb;

		$default_visibility_levels = wp_cache_get( 'default_visibility_levels', 'bp_xprofile' );

		if ( false === $default_visibility_levels ) {
			$bp = buddypress();

			// @todo need to check like  why we are not getting the $bp->profile->table_name_meta into the plugins page.
			if ( isset( $bp->profile ) && isset( $bp->profile->table_name_meta ) ) {
				$profile_meta_table = $bp->profile->table_name_meta;
			} else {
				$profile_meta_table = $bp->table_prefix . 'bp_xprofile_meta';
			}

			if ( isset( $bp->profile ) && isset( $bp->profile->table_name_fields ) ) {
				$profile_table = $bp->profile->table_name_fields;
			} else {
				$profile_table = $bp->table_prefix . 'bp_xprofile_fields';
			}

			$levels = $wpdb->get_results(
				"SELECT xf.id,
				GROUP_CONCAT(xm.meta_key ORDER BY xf.id) meta_keys,
				GROUP_CONCAT(xm.meta_value ORDER BY xf.id) meta_values
				FROM {$profile_table} as xf
				INNER JOIN {$profile_meta_table} as xm on xf.id = xm.object_id
				WHERE ( xm.meta_key = 'default_visibility' OR xm.meta_key = 'allow_custom_visibility' )
				GROUP BY xf.id
				ORDER BY xf.id"
			);

			$default_visibility_levels = array();

			// Arrange so that the field id is the key and the visibility level the value.
			if ( ! empty( $levels ) ) {

				foreach ( $levels as $level ) {

					$meta_keys   = explode( ',', $level->meta_keys );
					$meta_values = explode( ',', $level->meta_values );

					$meta_keys                               = json_decode( str_replace( '_visibility', '', wp_json_encode( $meta_keys ) ), true );
					$default_visibility_levels[ $level->id ] = array_combine( $meta_keys, $meta_values );
				}
			}

			wp_cache_set( 'default_visibility_levels', $default_visibility_levels, 'bp_xprofile' );
		}


		return $default_visibility_levels;
	}

	/** Admin Output **********************************************************/

	/**
	 * Output the admin area field group form.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @global string $message
	 */
	public function render_admin_form() {
		global $message;

		// Users Admin URL
		$users_url = bp_get_admin_url( 'admin.php' );

		// URL to cancel to
		$cancel_url = add_query_arg(
			array(
				'page' => 'bp-profile-setup',
			),
			$users_url
		);

		// New field group.
		if ( empty( $this->id ) ) {
			$title  = __( 'New Field Set', 'buddyboss' );
			$button = __( 'Save', 'buddyboss' );
			$action = add_query_arg(
				array(
					'page' => 'bp-profile-setup',
					'mode' => 'add_group',
				),
				$users_url
			);

			// Existing field group.
		} else {
			$title  = __( 'Edit Field Set', 'buddyboss' );
			$button = __( 'Update', 'buddyboss' );
			$action = add_query_arg(
				array(
					'page'     => 'bp-profile-setup',
					'mode'     => 'edit_group',
					'group_id' => (int) $this->id,
				),
				$users_url
			);
		} ?>

		<div class="wrap">
			<?php
			$users_tab = count( bp_core_get_users_admin_tabs() );
			if ( $users_tab > 1 ) {
				?>
				<h2 class="nav-tab-wrapper"><?php bp_core_admin_users_tabs( __( 'Profile Fields', 'buddyboss' ) ); ?></h2>
																			<?php
			}
			?>
			<h1><?php echo esc_html( $title ); ?></h1>

			<?php if ( ! empty( $message ) ) : ?>

				<div id="message" class="error fade">
					<p><?php echo esc_html( $message ); ?></p>
				</div>

			<?php endif; ?>

			<form id="bp-xprofile-add-field-group" action="<?php echo esc_url( $action ); ?>" method="post">
				<div id="poststuff">
					<div id="post-body" class="metabox-holder columns-<?php echo ( 1 == get_current_screen()->get_columns() ) ? '1' : '2'; ?>">
						<div id="post-body-content">
							<div id="titlediv">
								<div class="titlewrap">
									<label id="title-prompt-text" for="title" class="screen-reader-text"><?php esc_html_e( 'Field Set Name (required)', 'buddyboss' ); ?></label>
									<input type="text" name="group_name" id="title" value="<?php echo esc_attr( $this->name ); ?>" autocomplete="off" />
								</div>
							</div>
							<div class="postbox">
								<h2><?php esc_html_e( 'Field Set Description', 'buddyboss' ); ?></h2>
								<div class="inside">
									<label for="group_description" class="screen-reader-text">
									<?php
										/* translators: accessibility text */
										esc_html_e( 'Add description', 'buddyboss' );
									?>
									</label>
									<textarea name="group_description" id="group_description" rows="8" cols="60"><?php echo esc_textarea( $this->description ); ?></textarea>
								</div>
							</div>

							<?php

							/**
							 * Fires after the XProfile group description field is rendered in wp-admin.
							 *
							 * @since BuddyPress 2.6.0
							 *
							 * @param BP_XProfile_Group $this Current XProfile group.
							 */
							do_action( 'xprofile_group_admin_after_description', $this );
							?>

						</div><!-- #post-body-content -->

						<div id="postbox-container-1" class="postbox-container">

							<?php

							/**
							 * Fires before XProfile Group submit metabox.
							 *
							 * @since BuddyPress 2.1.0
							 *
							 * @param BP_XProfile_Group $this Current XProfile group.
							 */
							do_action( 'xprofile_group_before_submitbox', $this );
							?>

							<div id="submitdiv" class="postbox">
								<h2><?php _e( 'Submit', 'buddyboss' ); ?></h2>
								<div class="inside">
									<div id="submitcomment" class="submitbox">
										<div id="major-publishing-actions">

											<?php

											// Nonce fields
											wp_nonce_field( 'bp_xprofile_admin_group', 'bp_xprofile_admin_group' );

											/**
											 * Fires at the beginning of the XProfile Group publishing actions section.
											 *
											 * @since BuddyPress 2.1.0
											 *
											 * @param BP_XProfile_Group $this Current XProfile group.
											 */
											do_action( 'xprofile_group_submitbox_start', $this );
											?>

											<input type="hidden" name="group_order" id="group_order" value="<?php echo esc_attr( $this->group_order ); ?>" />
											<div id="publishing-action">
												<input type="submit" name="save_group" value="<?php echo esc_attr( $button ); ?>" class="button-primary"/>
											</div>
											<div id="delete-action">
												<a href="<?php echo esc_url( $cancel_url ); ?>" class="deletion"><?php _e( 'Cancel', 'buddyboss' ); ?></a>
											</div>
											<div class="clear"></div>
										</div>
									</div>
								</div>
							</div>

							<?php

							/**
							 * Fires after XProfile Group submit metabox.
							 *
							 * @since BuddyPress 2.1.0
							 *
							 * @param BP_XProfile_Group $this Current XProfile group.
							 */
							do_action( 'xprofile_group_after_submitbox', $this );
							?>

							<?php
							/**
							 * The main profile field group, used in registration form,
							 * can not be a repeater.
							 */
							if ( empty( $this->id ) || $this->can_delete ) :
								?>

								<?php $enabled = 'on' == self::get_group_meta( $this->id, 'is_repeater_enabled' ) ? 'on' : 'off'; ?>

								<div id="repeatersetdiv" class="postbox">
									<h2><?php _e( 'Repeater Set', 'buddyboss' ); ?></h2>
									<div class="inside">
										<p style="margin-top: 0;"><?php _e( 'Allow the profile fields within this set to be repeated again and again, so the user can add multiple instances of their data.', 'buddyboss' ); ?></p>
										<select name="group_is_repeater" id="group_is_repeater" >
											<option value="off" <?php selected( $enabled, 'off' ); ?>><?php _e( 'Disabled', 'buddyboss' ); ?></option>
											<option value="on" <?php selected( $enabled, 'on' ); ?>><?php _e( 'Enabled', 'buddyboss' ); ?></option>
										</select>
									</div>
								</div>

							<?php endif; ?>

						</div>
					</div>
				</div>
			</form>
		</div>

		<?php
	}
}
