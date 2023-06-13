<?php
/**
 * BuddyPress XProfile Repeater Fields and field sets.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set maximum number of field sets
 *
 * @since BuddyBoss 1.0.0
 * @return int
 */
function bp_profile_field_set_max_cap() {
	return 100;
}

/**
 * Return repeater template field ids
 *
 * @since BuddyBoss 1.0.0
 * @global wpdb $wpdb WordPress database abstraction object.
 * @param type $field_group_id
 * @return type
 */
function bp_get_repeater_template_field_ids( $field_group_id ) {
	static $bp_group_template_field_ids = array();
	global $wpdb;
	$bp = buddypress();

	$cache_key = 'bp_group_template_field_ids_' . $field_group_id;
	if ( isset( $bp_group_template_field_ids[ $cache_key ] ) ) {
		return $bp_group_template_field_ids[ $cache_key ];
	}

	$group_field_ids = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields} WHERE group_id = {$field_group_id} AND parent_id = 0" );
	if ( empty( $group_field_ids ) || is_wp_error( $group_field_ids ) ) {
		return array();
	}

	$clone_field_ids = $wpdb->get_col(
		"SELECT object_id FROM {$bp->profile->table_name_meta} "
		. " WHERE object_type = 'field' AND object_id IN (" . implode( ',', $group_field_ids ) . ") AND meta_key = '_is_repeater_clone' AND meta_value = 1"
	);

	if ( empty( $clone_field_ids ) || is_wp_error( $clone_field_ids ) ) {
		$template_field_ids = $group_field_ids;
	} else {
		$template_field_ids = array_diff( $group_field_ids, $clone_field_ids );
	}

	$bp_group_template_field_ids[ $cache_key ] = $template_field_ids;

	return $template_field_ids;
}

/**
 * Return ids of one field sets repeated instances.
 *
 * @since BuddyBoss 1.0.0
 * @global wpdb $wpdb           WordPress database abstraction object.
 *
 * @param int $field_group_id Field Group id.
 * @param int $count          Current user field set count.
 *
 * @return array
 */
function bp_get_repeater_clone_field_ids_subset( $field_group_id, $count ) {
	static $bp_clone_ids_subset = array();
	global $wpdb;
	$bp = buddypress();

	$ids = array();

	$cache_key = $field_group_id . '_' . $count;

	if ( isset( $bp_clone_ids_subset[ $cache_key ] ) ) {
		return $bp_clone_ids_subset[ $cache_key ];
	}

	$template_field_ids = bp_get_repeater_template_field_ids( $field_group_id );

	if ( empty( $template_field_ids ) ) {
		return $ids;
	}

	foreach ( $template_field_ids as $template_field_id ) {
		$sql     = $wpdb->prepare( "select m1.object_id, CAST(m2.meta_value AS DECIMAL) AS 'clone_number' FROM {$bp->profile->table_name_meta} as m1
		   JOIN {$bp->profile->table_name_meta} AS m2 ON m1.object_id = m2.object_id
		   WHERE m1.meta_key = '_cloned_from' AND m1.meta_value = %d
		   AND m2.meta_key = '_clone_number' ORDER BY m2.object_id, m2.meta_value ASC", $template_field_id );
		$results = $wpdb->get_results( $sql, ARRAY_A );


		for ( $i = 1; $i <= $count; $i++ ) {
			// is there a clone already?
			$clone_id = false;

			if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
				foreach ( $results as $row ) {
					if ( $row['clone_number'] == $i ) {
						$clone_id = $row['object_id'];
						break;
					}
				}
			}

			// if not create one!
			if ( ! $clone_id ) {
				$checked_cloned_from = bp_xprofile_get_meta( $template_field_id, 'field', '_is_repeater_clone' );
				if ( ! $checked_cloned_from ) {
					$clone_id = bp_clone_field_for_repeater_sets( $template_field_id, $field_group_id, $i );
				}
			}

			if ( $clone_id ) {
				$ids[] = $clone_id;
			}
		}
	}

	$bp_clone_ids_subset[ $cache_key ] = $ids;

	return $ids;
}

/**
 * Return ids of all field sets repeated instances.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int   $field_group_id Xprofile group ID.
 *
 * @return array
 * @global wpdb $wpdb           WordPress database abstraction object.
 *
 */
function bp_get_repeater_clone_field_ids_all( $field_group_id ) {
	static $bp_clone_field_ids_all = array();
	global $wpdb;
	$bp        = buddypress();
	$cache_key = 'group_' . $field_group_id;

	$ids = array();

	if ( isset( $bp_clone_field_ids_all[ $cache_key ] ) ) {
		return $bp_clone_field_ids_all[ $cache_key ];
	}

	$template_field_ids = bp_get_repeater_template_field_ids( $field_group_id );

	if ( empty( $template_field_ids ) ) {
		return $ids;
	}

	foreach ( $template_field_ids as $template_field_id ) {
		$sql     = $wpdb->prepare( "select m1.object_id FROM {$bp->profile->table_name_meta} as m1 WHERE m1.meta_key = '_cloned_from' AND m1.meta_value = %d", $template_field_id );
		$results = $wpdb->get_col( $sql );

		if ( ! empty( $results ) && ! is_wp_error( $results ) ) {
			$ids = array_merge( $ids, $results );
		}
	}

	$bp_clone_field_ids_all[ $cache_key ] = $ids;

	return $ids;
}

add_action( 'xprofile_updated_profile', 'bp_profile_repeaters_update_field_data', 11, 5 );
add_action( 'bb_xprofile_error_on_updated_profile', 'bp_profile_repeaters_update_field_data', 11, 5 );

/**
 * Update/Sort repeater fields when profile data is updated.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_profile_repeaters_update_field_data( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {
	global $wpdb;
	$bp = buddypress();

	$field_group_id = 0;

	if ( isset( $posted_field_ids[0] ) ) {
		$field_group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->profile->table_name_fields} WHERE id = %d", $posted_field_ids[0] ) );
	}

	$is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $field_group_id, 'is_repeater_enabled' ) ? true : false;
	if ( ! $is_repeater_enabled ) {
		return;
	}

	// First, clear the data for deleted fields, if any
	if ( isset( $_POST['deleted_field_ids'] ) && ! empty( $_POST['deleted_field_ids'] ) ) {
		$deleted_field_ids = wp_parse_id_list( $_POST['deleted_field_ids'] );
		foreach ( $deleted_field_ids as $deleted_field_id ) {
			xprofile_delete_field_data( $deleted_field_id, $user_id );
		}
	}

	$field_set_sequence = isset( $_POST['repeater_set_sequence'] ) ? wp_parse_id_list( wp_unslash( $_POST['repeater_set_sequence'] ) ) : array();

	// We'll take the data from all clone fields and dump it into the main/template field.
	// This is done to ensure that search etc, work smoothly.
	$main_field_data = array();

	$counter = 1;
	foreach ( (array) $field_set_sequence as $field_set_number ) {
		// Find all fields in this set, take their values and update the data for corresponding fields in set number $counter
		$fields_of_current_set = $wpdb->get_col(
			"SELECT object_id FROM {$bp->profile->table_name_meta} WHERE meta_key = '_clone_number' AND meta_value = {$field_set_number} "
			. ' AND object_id IN (' . implode( ',', $posted_field_ids ) . ") and object_type = 'field' "
		);

		if ( ! empty( $fields_of_current_set ) && ! is_wp_error( $fields_of_current_set ) ) {
			foreach ( $fields_of_current_set as $field_of_current_set ) {
				$cloned_from = $wpdb->get_var( "SELECT meta_value FROM {$bp->profile->table_name_meta} WHERE object_id = {$field_of_current_set} AND meta_key = '_cloned_from' " );

				$sql                    = "SELECT m1.object_id FROM {$bp->profile->table_name_meta} AS m1 JOIN {$bp->profile->table_name_meta} AS m2 ON m1.object_id = m2.object_id "
					. " WHERE m1.object_type = 'field' AND m1.meta_key = '_cloned_from' AND m1.meta_value = {$cloned_from} "
					. " AND m2.meta_key = '_clone_number' AND m2.meta_value = {$counter} ";
				$corresponding_field_id = $wpdb->get_var( $sql );
				if ( ! empty( $corresponding_field_id ) ) {
					$new_data             = isset( $new_values[ $field_of_current_set ]['value'] ) ? $new_values[ $field_of_current_set ]['value'] : '';
					$new_visibility_level = isset( $new_values[ $field_of_current_set ]['visibility'] ) ? $new_values[ $field_of_current_set ]['visibility'] : '';
					xprofile_set_field_visibility_level( $corresponding_field_id, $user_id, $new_visibility_level );

					$type = $wpdb->get_var( $wpdb->prepare( "SELECT `type` FROM {$bp->table_prefix}bp_xprofile_fields WHERE id = %d", $corresponding_field_id ) );

					if ( 'datebox' === $type && ! empty ( $new_data ) ) {
						$new_data = date( 'Y-m-d 00:00:00', strtotime( $new_data ) );
					}

					xprofile_set_field_data( $corresponding_field_id, $user_id, $new_data );

					if ( ! isset( $main_field_data[ $cloned_from ] ) ) {
						$main_field_data[ $cloned_from ] = array();
					}

					$main_field_data[ $cloned_from ][] = is_array( $new_values[ $field_of_current_set ]['value'] ) ? implode( ' ', $new_values[ $field_of_current_set ]['value'] ) : $new_values[ $field_of_current_set ]['value'];
				}
			}
		}

		$counter++;
	}

	if ( ! empty( $main_field_data ) ) {
		foreach ( $main_field_data as $main_field_id => $values ) {
			$values_str = implode( ' ', $values );
			xprofile_set_field_data( $main_field_id, $user_id, $values_str );
		}
	}

	if ( isset( $_POST['repeater_set_sequence'] ) ) {
		bp_set_profile_field_set_count( $field_group_id, $user_id, count( $field_set_sequence ) );
	}

}

add_filter( 'bp_xprofile_set_field_data_pre_validate', 'bp_repeater_set_field_data_pre_validate', 10, 2 );

/**
 * Prevalidate repeater set data.
 *
 * bp_xprofile_field_type_is_valid filter doesn't pass the $field object.
 * So we hook into bp_xprofile_set_field_data_pre_validate filter and save the $field object in a global variable.
 * We then use this global variable later, in bp_xprofile_field_type_is_valid hook.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global \BP_XProfile_Field $bp_profile_repeater_last_field
 * @param mixed              $value
 * @param \BP_XProfile_Field $field
 *
 * @return mixed
 */
function bp_repeater_set_field_data_pre_validate( $value, $field ) {
	global $bp_profile_repeater_last_field;
	$bp_profile_repeater_last_field = $field;
	return $value;
}

add_filter( 'bp_xprofile_field_type_is_valid', 'bp_profile_repeater_is_data_valid_for_template_fields', 10, 3 );

/**
 * @todo Add Title/Description
 *
 * @global \BP_XProfile_Field $bp_profile_repeater_last_field
 *
 * @param boolean                 $validated
 * @param array                   $values
 * @param \BP_XProfile_Field_Type $field_type_obj
 */
function bp_profile_repeater_is_data_valid_for_template_fields( $validated, $values, $field_type_obj ) {
	global $bp_profile_repeater_last_field;

	if ( empty( $bp_profile_repeater_last_field ) ) {
		return $validated;
	}

	if ( $validated ) {
		$bp_profile_repeater_last_field = false;// reset
		return $validated;
	}

	$field_id = $bp_profile_repeater_last_field->id;

	$field_group_id      = $bp_profile_repeater_last_field->group_id;
	$is_repeater_enabled = 'on' == bp_xprofile_get_meta( $field_group_id, 'group', 'is_repeater_enabled' ) ? true : false;

	if ( ! $is_repeater_enabled ) {
		$bp_profile_repeater_last_field = false;// reset
		return $validated;
	}

	$cloned_from = bp_xprofile_get_meta( $field_id, 'field', '_cloned_from', true );
	if ( ! empty( $cloned_from ) ) {
		// This is a clone field. We needn't do anything
		$bp_profile_repeater_last_field = false;// reset
		return $validated;
	}

	// This is a template field
	$values_arr = explode( ' ', $values );

	// If there's a whitelist set, make sure that each value is a whitelisted value.
	$validation_whitelist = $field_type_obj->get_whitelist_values();

	if ( ! empty( $validation_whitelist ) ) {
		$values_valid = true;

		foreach ( (array) $values_arr as $value ) {
			if ( ! in_array( $value, $validation_whitelist ) ) {
				$values_valid = false;
				break;
			}
		}

		$validated = $values_valid;
	}

	$bp_profile_repeater_last_field = false;// reset
	return $validated;
}

/**
 * Copy form fields for field sets that repeat.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global wpdb $wpdb           WordPress database abstraction object.
 *
 * @param int $field_id       Field ID.
 * @param int $field_group_id Field Group ID.
 * @param int $current_count  Current Field Set count.
 *
 * @return false|int
 */
function bp_clone_field_for_repeater_sets( $field_id, $field_group_id, $current_count = 0 ) {
	static $db_row_cache = array();
	static $metas_cache  = array();
	global $wpdb;
	$bp = buddypress();

	$user_id = bp_loggedin_user_id();
	if ( ! $user_id ) {
		return false;
	}

	$db_row_cache_key = 'field_' . $field_id;
	if ( ! isset( $db_row_cache[ $db_row_cache_key ] ) ) {
		$db_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id ), ARRAY_A );

		$db_row_cache[ $db_row_cache_key ] = $db_row;
	} else {
		$db_row = $db_row_cache[ $db_row_cache_key ];
	}


	if ( ! empty( $db_row ) && ! is_wp_error( $db_row ) ) {
		$template_field_id = $db_row['id'];

		$new_field_column_data_types = array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d' );

		$new_field_column_data = array(
			'group_id'          => $db_row['group_id'],
			'parent_id'         => $db_row['parent_id'],
			'type'              => $db_row['type'],
			'name'              => $db_row['name'],
			'description'       => $db_row['description'],
			'is_required'       => $db_row['is_required'],
			'is_default_option' => $db_row['is_default_option'],
			'field_order'       => $db_row['field_order'],
			'option_order'      => $db_row['option_order'],
			'order_by'          => $db_row['order_by'],
			'can_delete'        => $db_row['can_delete'],
		);

		$inserted = $wpdb->insert(
			$bp->profile->table_name_fields,
			$new_field_column_data,
			$new_field_column_data_types
		);
		if ( $inserted ) {
			$new_field_id    = $wpdb->insert_id;
			$metas_cache_key = 'field_meta_' . $template_field_id;
			if ( ! isset( $metas_cache[ $metas_cache_key ] ) ) {
				$metas = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_meta} WHERE object_id = {$template_field_id} AND object_type = 'field'", ARRAY_A );

				$metas_cache[ $metas_cache_key ] = $metas;
			} else {
				$metas = $metas_cache[ $metas_cache_key ];
			}

			if ( ! empty( $metas ) && ! is_wp_error( $metas ) ) {
				foreach ( $metas as $meta ) {
					if ( ! empty( $meta['meta_key'] ) && $meta['meta_key'] === 'member_type' ) {
						$meta_data = bp_xprofile_get_meta( $new_field_id, 'field', $meta['meta_key'] );
						if ( ! empty( $meta_data ) && ! in_array( $meta['meta_value'], (array) $meta_data ) ) {
							bp_xprofile_add_meta( $new_field_id, 'field', $meta['meta_key'], $meta['meta_value'] );
						} else {
							bp_xprofile_update_meta( $new_field_id, 'field', $meta['meta_key'], $meta['meta_value'] );
						}
					} else {
						bp_xprofile_update_meta( $new_field_id, 'field', $meta['meta_key'], $meta['meta_value'] );
					}
				}
			}

			if ( $current_count ) {
				$current_clone_number = $current_count;
			} else {
				$current_clone_number = 1;
				// get all clones of the template field.
				$all_clones = $wpdb->get_col( "SELECT object_id FROM {$bp->profile->table_name_meta} WHERE meta_key = '_cloned_from' AND meta_value = {$template_field_id}" );
				if ( ! empty( $all_clones ) && ! is_wp_error( $all_clones ) ) {
					$last_max_clone_number = $wpdb->get_var(
						"SELECT MAX( meta_value ) FROM {$bp->profile->table_name_meta} WHERE meta_key = '_clone_number' AND object_id IN (" . implode( ',', $all_clones ) . ")"
					);
					$last_max_clone_number = ! empty( $last_max_clone_number ) ? absint( $last_max_clone_number ) : 0;
					$current_clone_number  = $last_max_clone_number + 1;
				}
			}

			bp_xprofile_update_meta( $new_field_id, 'field', '_is_repeater_clone', true );
			bp_xprofile_update_meta( $new_field_id, 'field', '_cloned_from', $template_field_id );
			bp_xprofile_update_meta( $new_field_id, 'field', '_clone_number', $current_clone_number );

			// fix field order.
			$field_order = ( (int) $current_clone_number * bp_profile_field_set_max_cap() ) + (int) $db_row['field_order'];
			$wpdb->update(
				$bp->profile->table_name_fields,
				array( 'field_order' => $field_order ),
				array( 'id' => $new_field_id ),
				array( '%d' ),
				array( '%d' )
			);

			return $new_field_id;
		}
	}

	return false;
}

add_action( 'xprofile_fields_saved_field', 'xprofile_update_clones_on_template_update' );

/**
 * Update repeater/clone fields when the main/template field is updated.
 *
 * @param \BP_XProfile_Field $field Description
 */
function xprofile_update_clones_on_template_update( $field ) {
	global $wpdb;
	$bp = buddypress();

	// get all clone field ids
	$clone_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT f.id FROM {$bp->profile->table_name_fields} AS f JOIN {$bp->profile->table_name_meta} AS fm ON f.id = fm.object_id "
			. " WHERE f.parent_id = 0 AND fm.meta_key = '_cloned_from' AND fm.meta_value = %d ",
			$field->id
		)
	);

	if ( empty( $clone_ids ) || is_wp_error( $clone_ids ) ) {
		return;
	}

	$db_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $field->id ), ARRAY_A );

	if ( ! empty( $db_row ) && ! is_wp_error( $db_row ) ) {
		$sql = $wpdb->prepare(
			"UPDATE {$bp->profile->table_name_fields} SET "
			. ' group_id = %d, parent_id = %d, type = %s, name = %s, description = %s, is_required = %d, '
			. ' is_default_option = %d, option_order = %d, order_by = %d, can_delete = %d '
			. ' WHERE id IN ( ' . implode( ',', $clone_ids ) . ' )',
			$db_row['group_id'],
			$db_row['parent_id'],
			$db_row['type'],
			$db_row['name'],
			$db_row['description'],
			$db_row['is_required'],
			$db_row['is_default_option'],
			$db_row['option_order'],
			$db_row['order_by'],
			$db_row['can_delete']
		);

		$wpdb->query( $sql );

		$metas = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_meta} WHERE object_id = {$field->id} AND object_type = 'field'", ARRAY_A );
		if ( ! empty( $metas ) && ! is_wp_error( $metas ) ) {
			$field_member_types = array();
			foreach ( $clone_ids as $clone_id ) {
				foreach ( $metas as $meta ) {
					if ( $meta['meta_key'] != 'member_type' ) {
						bp_xprofile_update_meta( $clone_id, 'field', $meta['meta_key'], $meta['meta_value'] );
					} else {
						$field_member_types[] = $meta;
						bp_xprofile_delete_meta( $clone_id, 'field', 'member_type' );
					}
				}
			}

			if ( ! empty( $field_member_types ) ) {
				foreach ( $clone_ids as $clone_id ) {
					foreach ( $field_member_types as $meta ) {
						bp_xprofile_add_meta( $clone_id, 'field', $meta['meta_key'], $meta['meta_value'] );
					}
				}
			}
		}
	}
}

add_action( 'xprofile_fields_deleted_field', 'xprofile_delete_clones_on_template_delete' );

/**
 * Delete repeater/clone fields when the main/template field is deleted.
 *
 * @param \BP_XProfile_Field $field Description
 */
function xprofile_delete_clones_on_template_delete( $field ) {
	global $wpdb;
	$bp = buddypress();

	// get all clone field ids
	$clone_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT f.id FROM {$bp->profile->table_name_fields} AS f JOIN {$bp->profile->table_name_meta} AS fm ON f.id = fm.object_id "
			. " WHERE f.parent_id = 0 AND fm.meta_key = '_cloned_from' AND fm.meta_value = %d ",
			$field->id
		)
	);

	if ( empty( $clone_ids ) || is_wp_error( $clone_ids ) ) {
		return;
	}

	foreach ( $clone_ids as $clone_id ) {
		$clone_field = xprofile_get_field( $clone_id );
		$clone_field->delete( true );
	}
}

add_action( 'xprofile_updated_field_position', 'xprofile_update_clone_positions_on_template_position_update', 10, 3 );

/**
 * Update position and group_id for all clone fields when a template/main field's order is changed.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 * @param int $template_field_id
 * @param int $new_position
 * @param int $template_field_group_id
 * @return type
 */
function xprofile_update_clone_positions_on_template_position_update( $template_field_id, $new_position, $template_field_group_id ) {

	//
	// Check if template field is now moved to another field set
	// If so
	// - If the new field set is also a repeater
	// - Update clone fields, update their group id and sorting numbers
	// If not
	// - Update clone fields, update their sorting numbers
	//

	global $wpdb;
	$bp = buddypress();

	$clone_field_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT object_id FROM {$bp->profile->table_name_meta} WHERE object_type = 'field' AND meta_key = '_cloned_from' AND meta_value = %d",
			$template_field_id
		)
	);

	if ( empty( $clone_field_ids ) || is_wp_error( $clone_field_ids ) ) {
		return;
	}

	// get the old group id
	// since all clone fields have same group id, we can simply get the group id for the first one
	$old_group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->profile->table_name_fields} WHERE id = %d", $clone_field_ids[0] ) );
	if ( empty( $old_group_id ) || is_wp_error( $old_group_id ) ) {
		return;// something's not right
	}

	$update_field_orders = false;

	if ( $old_group_id != $template_field_group_id ) {
		// tempalte field has been moved to a new field group
		$is_repeater_enabled = 'on' == bp_xprofile_get_meta( $template_field_group_id, 'group', 'is_repeater_enabled' ) ? true : false;

		if ( $is_repeater_enabled ) {
			// update group id for all clone fields
			$sql = $wpdb->prepare(
				"UPDATE {$bp->profile->table_name_fields} SET group_id = %d WHERE id IN ( " . implode( ',', $clone_field_ids ) . ' )',
				$template_field_group_id
			);

			$wpdb->query( $sql );

			$update_field_orders = true;
		}
	} else {
		// tempalte field is still in same field group
		$update_field_orders = true;
	}

	if ( $update_field_orders ) {
		$max_cap = bp_profile_field_set_max_cap();

		foreach ( $clone_field_ids as $clone_field_id ) {
			$clone_number = (int) bp_xprofile_get_meta( $clone_field_id, 'field', '_clone_number', true );
			$field_order  = ( $clone_number * $max_cap ) + $new_position;
			$wpdb->update(
				$bp->profile->table_name_fields,
				array( 'field_order' => $field_order ),
				array( 'id' => $clone_field_id ),
				array( '%d' ),
				array( '%d' )
			);
		}
	}
}

add_filter( 'bp_xprofile_field_get_children', 'bp_xprofile_repeater_field_get_children', 10, 3 );

/**
 * Return children of repeated field sets.
 *
 * @since BuddyBoss 1.0.0
 * @param array              $children
 * @param boolean            $for_editing
 * @param \BP_XProfile_Field $field
 */
function bp_xprofile_repeater_field_get_children( $children, $for_editing, $field ) {
	global $wpdb;
	$bp = buddypress();

	if ( ! bp_xprofile_get_meta( $field->id, 'field', '_is_repeater_clone', true ) ) {
		return $children;
	}

	// If the current field is a clone,
	// we'll query template field for children/field-options
	$template_field_id = bp_xprofile_get_meta( $field->id, 'field', '_cloned_from', true );
	if ( empty( $template_field_id ) ) {
		return $children;
	}

	$template_field = xprofile_get_field( $template_field_id );
	if ( $template_field == null ) {
		return $children;
	}

	if ( ! ( $template_children = wp_cache_get( $template_field_id, 'field_children_options' ) ) ) {
		// This is done here so we don't have problems with sql injection.
		if ( empty( $for_editing ) && ( 'asc' === $template_field->order_by ) ) {
			$sort_sql = 'ORDER BY name ASC';
		} elseif ( empty( $for_editing ) && ( 'desc' === $template_field->order_by ) ) {
			$sort_sql = 'ORDER BY name DESC';
		} else {
			$sort_sql = 'ORDER BY option_order ASC';
		}

		$parent_id = $template_field_id;

		$bp  = buddypress();
		$sql = $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE parent_id = %d AND group_id = %d {$sort_sql}", $parent_id, $template_field->group_id );

		$template_children = $wpdb->get_results( $sql );

		wp_cache_set( $template_field_id, $template_children, 'field_children_options' );
	}

	if ( ! empty( $template_children ) ) {

		// Since some children will be shared in all clones of a kind,
		// there are duplicate radiobutton/checkbox ids and hence associated labels behave incorreclty.
		// we'll need to manipuate children ids
		$temp         = array();
		$clone_number = (int) bp_xprofile_get_meta( $field->id, 'field', '_clone_number', true );
		foreach ( $template_children as $child ) {
			$child->id .= '_' . $clone_number;
			$temp[]     = $child;
		}

		$children = $temp;
	}

	return $children;
}

/**
 * Return total number of field sets.
 *
 * @param type $field_group_id
 * @param type $user_id
 * @since BuddyBoss 1.0.0
 * @return type
 */
function bp_get_profile_field_set_count( $field_group_id, $user_id ) {
	$count = get_user_meta( $user_id, 'field_set_count_' . $field_group_id, true );
	return $count > 0 ? $count : 1;
}

/**
 * Set maximum field set allowed.
 *
 * @param type $field_group_id
 * @param type $user_id
 * @param type $count
 * @since BuddyBoss 1.0.0
 * @return type
 */
function bp_set_profile_field_set_count( $field_group_id, $user_id, $count ) {
	$max   = bp_profile_field_set_max_cap();
	$count = $count <= $max ? $count : $max;

	return update_user_meta( $user_id, 'field_set_count_' . $field_group_id, $count );
}

add_action( 'bp_after_profile_field_content', 'bp_print_add_repeater_set_button' );

/**
 * Output button to add repeater field set.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_print_add_repeater_set_button() {
	if ( 'edit' !== bp_current_action() ) {
		return false;
	}

	$group_id            = bp_get_current_profile_group_id();
	$is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
	if ( $is_repeater_enabled ) {
		echo "<button id='btn_add_repeater_set' class='button outline' data-nonce='" . wp_create_nonce( 'bp_xprofile_add_repeater_set' ) . "' data-group='{$group_id}'>"; // disabled='disabled' style='pointer-events:none;'
		echo '<i class="bb-icon-f bb-icon-plus"></i>';
		printf(
			/* translators: %s = profile field group name */
			__( 'Add Another', 'buddyboss' ),
			bp_get_the_profile_group_name()
		);
		echo '</button>';
	}
}

add_action( 'wp_ajax_bp_xprofile_add_repeater_set', 'bp_xprofile_ajax_add_repeater_set' );

/**
 * Adds a repeater set.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_xprofile_ajax_add_repeater_set() {
	check_ajax_referer( 'bp_xprofile_add_repeater_set', '_wpnonce' );

	$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
	if ( ! $user_id ) {
		die();
	}

	$field_group_id = isset( $_REQUEST['group'] ) ? absint( $_REQUEST['group'] ) : false;

	if ( ! $field_group_id ) {
		die();
	}

	$count = bp_get_profile_field_set_count( $field_group_id, $user_id );
	$count++;
	bp_set_profile_field_set_count( $field_group_id, $user_id, $count );
	die( 'ok' );
}

add_action( 'bp_before_profile_field_html', 'bp_profile_repeaters_print_group_html_start' );

/**
 * Open wrapper of repeater set - on edit profile screen
 *
 * @since BuddyBoss 1.0.0
 * @global type $first_xpfield_in_repeater
 */
function bp_profile_repeaters_print_group_html_start() {
	$group_id            = bp_get_current_profile_group_id();
	$is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
	if ( $is_repeater_enabled ) {
		global $first_xpfield_in_repeater;
		$current_field_id   = bp_get_the_profile_field_id();
		$current_set_number = bp_xprofile_get_meta( $current_field_id, 'field', '_clone_number', true );
		$template_field_id  = bp_xprofile_get_meta( $current_field_id, 'field', '_cloned_from', true );

		$is_required = xprofile_check_is_required_field( $current_field_id );

		$can_delete = ( '1' === $current_set_number && true === $is_required ) ? false : true;

		if ( empty( $first_xpfield_in_repeater ) ) {
			$first_xpfield_in_repeater = $template_field_id;
			// start of first set
			?>
			<div class="repeater_sets_sortable">
			<div class="repeater_group_outer" data-set_no="<?php echo $current_set_number; ?>">

				<div class="repeater_tools">
					<span class="repeater_set_title"></span>
					<a class="repeater_set_edit bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Edit', 'buddyboss' ); ?>">
						<i class="dashicons dashicons-edit"></i>
						<span class="bp-screen-reader-text"><?php _e( 'Edit', 'buddyboss' ); ?></span>
					</a>
					<?php
					if ( true === $can_delete ) {
						?>
						<a class="repeater_set_delete bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Delete', 'buddyboss' ); ?>">
							<i class="dashicons dashicons-trash"></i>
							<span class="bp-screen-reader-text"><?php _e( 'Delete', 'buddyboss' ); ?></span>
						</a>
						<?php
					}
					?>

				</div>
				<div class='repeater_group_inner'>

			<?php
		} else {
			if ( $first_xpfield_in_repeater == $template_field_id ) {
				// start of a new set
				?>
				</div>
			</div><!-- .repeater_group_outer -->
			<div class="repeater_group_outer" data-set_no="<?php echo $current_set_number; ?>">

				<div class="repeater_tools">
					<span class="repeater_set_title"></span>
					<a class="repeater_set_edit bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Edit', 'buddyboss' ); ?>">
						<i class="dashicons dashicons-edit"></i>
						<span class="bp-screen-reader-text"><?php _e( 'Edit', 'buddyboss' ); ?></span>
					</a>
				<?php
				if ( true === $can_delete ) {
					?>
					<a class="repeater_set_delete bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Delete', 'buddyboss' ); ?>">
						<i class="dashicons dashicons-trash"></i>
						<span class="bp-screen-reader-text"><?php _e( 'Delete', 'buddyboss' ); ?></span>
					</a>
					<?php
				}
				?>
				</div>
				<div class='repeater_group_inner'>

				<?php
			}
		}
	}
}

add_action( 'bp_after_profile_field_content', 'bp_profile_repeaters_print_group_html_end', 4 );

/**
 * Close wrapper of repeater set - on edit profile screen
 *
 * @since BuddyBoss 1.0.0
 * @global boolean $first_xpfield_in_repeater
 */
function bp_profile_repeaters_print_group_html_end() {
	global $first_xpfield_in_repeater;
	if ( ! empty( $first_xpfield_in_repeater ) ) {
		echo '</div></div><!-- .repeater_group_outer -->';
		echo '</div><!-- repeater_sets_sortable -->';
		$first_xpfield_in_repeater = false;
	}
}


add_action( 'bp_before_profile_field_item', 'bp_view_profile_repeaters_print_group_html_start' );

/**
 * Open wrapper of repeater set - on View profile screen
 *
 * @since BuddyBoss 1.0.0
 * @global type $first_xpfield_in_repeater
 */
function bp_view_profile_repeaters_print_group_html_start() {
	$group_id            = bp_get_the_profile_group_id();
	$is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
	if ( $is_repeater_enabled ) {
		global $repeater_set_being_displayed;

		$current_field_id   = bp_get_the_profile_field_id();
		$current_set_number = bp_xprofile_get_meta( $current_field_id, 'field', '_clone_number', true );

		if ( empty( $repeater_set_being_displayed ) ) {
			// start of first set
		} elseif ( $repeater_set_being_displayed != $current_set_number ) {
			// end of previous set
			echo "<tr class='repeater-separator'><td colspan='2'></td></tr>";
		}

		$repeater_set_being_displayed = $current_set_number;
	}
}

add_action( 'bp_after_profile_field_items', 'bp_view_profile_repeaters_print_group_html_end', 4 );

/**
 * Close wrapper of repeater set - on edit profile screen
 *
 * @since BuddyBoss 1.0.0
 * @global boolean $first_xpfield_in_repeater
 */
function bp_view_profile_repeaters_print_group_html_end() {
	global $repeater_set_being_displayed;
	if ( ! empty( $repeater_set_being_displayed ) ) {
		// end of previous set
		echo "<tr class='repeater-separator'><td colspan='2'></td></tr>";

		$repeater_set_being_displayed = false;
	}
}

add_filter( 'bp_ps_field_before_query', 'bp_profile_repeaters_search_change_filter' );

/**
 * If the field is a main/template field for a repeater set, search should have a like '%s keyword %s' query.
 *
 * @param object $f Passed by reference
 */
function bp_profile_repeaters_search_change_filter( $f ) {
	if ( ! isset( $f->id ) ) {
		return $f;
	}

	$field_id = (int) $f->id;

	global $wpdb;
	$bp = buddypress();

	$field_group_id      = $wpdb->get_var( "SELECT group_id FROM {$bp->profile->table_name_fields} WHERE id = {$field_id} AND type != 'option' " );
	$is_repeater_enabled = 'on' == bp_xprofile_get_meta( $field_group_id, 'group', 'is_repeater_enabled' ) ? true : false;

	if ( ! $is_repeater_enabled ) {
		return $f;
	}

	$cloned_from = bp_xprofile_get_meta( $field_id, 'field', '_cloned_from', true );
	if ( ! empty( $cloned_from ) ) {
		// This is a clone field. We needn't do anything
		return $f;
	}

	// this is a template field
	$f->format = 'text';
	$f->filter = 'contains';
	return $f;
}

/**
 * Find top most template function ids from clone field ids.
 *
 * @param int $field_id Field ID.
 *
 * @return mixed
 */
function bb_xprofile_top_most_template_field_id( $field_id ) {
	$main_field = bp_xprofile_get_meta( (int) $field_id, 'field', '_cloned_from' );
	if ( ! empty( $main_field ) ) {
		$main_field = bb_xprofile_top_most_template_field_id( $main_field );
	}

	if ( empty( $main_field ) ) {
		return $field_id;
	}

	return $main_field;
}
