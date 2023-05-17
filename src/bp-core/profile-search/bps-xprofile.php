<?php
/**
 * BuddyBoss Profile Search Extended
 *
 * @package BuddyBoss\Core\ProfileSearch
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_ps_add_fields', 'bp_ps_xprofile_setup' );
/**
 * Setup BuddyBoss Profile Search Extended fields.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_xprofile_setup( $fields ) {
	global $group, $field;

	$args = array(
		'hide_empty_fields' => false,
		'member_type'       => bp_get_member_types(),
	);
	if ( bp_has_profile( $args ) ) {
		while ( bp_profile_groups() ) {
			bp_the_profile_group();
			$group_name = str_replace( '&amp;', '&', stripslashes( $group->name ) );
			while ( bp_profile_fields() ) {
				bp_the_profile_field();
				$f = new stdClass();

				$f->group       = $group_name;
				$f->group_id	= $group->id;
				$f->id          = $field->id;
				$f->code        = 'field_' . $field->id;
				$f->name        = str_replace( '&amp;', '&', stripslashes( $field->name ) );
				$f->name        = $f->name;
				$f->description = str_replace( '&amp;', '&', stripslashes( $field->description ) );
				$f->description = $f->description;
				$f->type        = $field->type;
				$f->format         = bp_ps_xprofile_format( $field->type, $field->id );
				$f->search         = 'bp_ps_xprofile_search';
				$f->sort_directory = 'bp_ps_xprofile_sort_directory';
				$f->get_value      = 'bp_ps_xprofile_get_value';

				$f->options = bp_ps_xprofile_options( $field->id );
				foreach ( $f->options as $key => $label ) {
					$f->options[ $key ] = $label;
				}

				if ( $f->format == 'custom' ) {
					/**
					 * @todo add title/description
					 *
					 * @since BuddyBoss 1.0.0
					 */
					do_action( 'bp_ps_custom_field', $f );
				}

				if ( $f->format == 'set' ) {
					unset( $f->sort_directory, $f->get_value );
				}

				$fields[] = $f;
			}
		}
	}

	return $fields;
}

/**
 * Return results from BuddyBoss Profile Search Extended.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_xprofile_search( $f ) {
	global $bp, $wpdb;

	$value  = $f->value;
	$filter = $f->format . '_' . ( $f->filter == '' ? 'is' : $f->filter );

	$sql                      = array(
		'select' => '',
		'where'  => array(),
	);
	$sql['select']            = "SELECT user_id, field_id FROM {$bp->profile->table_name_data}";

	if ( 'on' === bp_xprofile_get_meta( $f->group_id, 'group', 'is_repeater_enabled', true ) ) {
		$sql['where']['field_id'] = $wpdb->prepare( "field_id IN ( SELECT f.id FROM {$bp->profile->table_name_fields} as f, {$bp->profile->table_name_meta} as m where f.id = m.object_id AND group_id = %d AND f.type = %s AND m.meta_key = '_cloned_from' AND m.meta_value = %d )", $f->group_id, $f->type, $f->id );
	} else {
		$sql['where']['field_id'] = $wpdb->prepare( 'field_id = %d', $f->id );
	}

	switch ( $filter ) {
		case 'integer_range':
			if ( isset( $value['min'] ) ) {
				$sql['where']['min'] = $wpdb->prepare( 'value >= %d', $value['min'] );
			}
			if ( isset( $value['max'] ) ) {
				$sql['where']['max'] = $wpdb->prepare( 'value <= %d', $value['max'] );
			}
			break;

		case 'decimal_range':
			if ( isset( $value['min'] ) ) {
				$sql['where']['min'] = $wpdb->prepare( 'value >= %f', $value['min'] );
			}
			if ( isset( $value['max'] ) ) {
				$sql['where']['max'] = $wpdb->prepare( 'value <= %f', $value['max'] );
			}
			break;

		case 'date_date_range':
			$range_types = array( 'min', 'max' );
			foreach ( $range_types as $range_type ) {
				if ( isset( $value[ $range_type ]['year'] ) && ! empty( $value[ $range_type ]['year'] ) ) {
					$year  = $f->value[ $range_type ]['year'];
					$month = ! empty( $f->value[ $range_type ]['month'] ) ? $f->value[ $range_type ]['month'] : '00';
					$day   = ! empty( $f->value[ $range_type ]['day'] ) ? $f->value[ $range_type ]['day'] : '00';
					$date  = $year . '-' . $month . '-' . $day;

					$operator = 'min' == $range_type ? '>=' : '<=';

					$sql['where'][ $range_type ] = $wpdb->prepare( "DATE(value) $operator %s", $date );
				}
			}
			break;

		case 'date_age_range':
			$day   = date( 'j' );
			$month = date( 'n' );
			$year  = date( 'Y' );

			if ( isset( $value['max'] ) ) {
				$ymin                    = $year - $value['max'] - 1;
				$sql['where']['age_min'] = $wpdb->prepare( 'DATE(value) > %s', "$ymin-$month-$day" );
			}
			if ( isset( $value['min'] ) ) {
				$ymax                    = $year - $value['min'];
				$sql['where']['age_max'] = $wpdb->prepare( 'DATE(value) <= %s', "$ymax-$month-$day" );
			}
			break;

		case 'text_contains':
		case 'location_contains':
			if ( is_array( $value ) ) {
				$values = (array) $value;
				$parts  = array();
				foreach ( $values as $v ) {
					$v       = str_replace( '&', '&amp;', $v );
					$escaped = '%' . bp_ps_esc_like( $v ) . '%';
					$parts[] = $wpdb->prepare( 'value LIKE %s', $escaped );
				}
				$match                   = ' OR ';
				$sql['where'][ $filter ] = '(' . implode( $match, $parts ) . ')';
			} else {
				$value                   = str_replace( '&', '&amp;', $value );
				$escaped                 = '%' . bp_ps_esc_like( $value ) . '%';
				$sql['where'][ $filter ] = $wpdb->prepare( 'value LIKE %s', $escaped );
			}
			break;

		case 'text_like':
		case 'location_like':
			$value                   = str_replace( '&', '&amp;', $value );
			$value                   = str_replace( '\\\\%', '\\%', $value );
			$value                   = str_replace( '\\\\_', '\\_', $value );
			$sql['where'][ $filter ] = $wpdb->prepare( 'value LIKE %s', $value );
			break;

		case 'text_is':
		case 'location_is':
			$value                   = str_replace( '&', '&amp;', $value );
			$sql['where'][ $filter ] = $wpdb->prepare( 'value = %s', $value );
			break;

		case 'integer_is':
			$sql['where'][ $filter ] = $wpdb->prepare( 'value = %d', $value );
			break;

		case 'decimal_is':
			$sql['where'][ $filter ] = $wpdb->prepare( 'value = %f', $value );
			break;

		case 'date_is':
			$sql['where'][ $filter ] = $wpdb->prepare( 'DATE(value) = %s', $value );
			break;

		case 'text_one_of':
			$values = (array) $value;
			$parts  = array();
			foreach ( $values as $value ) {
				$value   = str_replace( '&', '&amp;', $value );
				$parts[] = $wpdb->prepare( 'value = %s', $value );
			}
			$sql['where'][ $filter ] = '(' . implode( ' OR ', $parts ) . ')';
			break;

		case 'set_match_any':
		case 'set_match_all':
			$values = (array) $value;
			$parts  = array();
			foreach ( $values as $value ) {
				$value   = str_replace( '&', '&amp;', $value );
				$escaped = '%:"' . bp_ps_esc_like( $value ) . '";%';
				$parts[] = $wpdb->prepare( 'value LIKE %s', $escaped );
			}
			$match                   = ( $filter == 'set_match_any' ) ? ' OR ' : ' AND ';
			$sql['where'][ $filter ] = '(' . implode( $match, $parts ) . ')';
			break;

		default:
			return array();
	}

	$sql   = apply_filters( 'bp_ps_field_sql', $sql, $f );
	$query = $sql['select'] . ' WHERE ' . implode( ' AND ', $sql['where'] );

	// If repeater enabel then we check repeater field id is private or not.
	if ( 'on' === bp_xprofile_get_meta( $f->group_id, 'group', 'is_repeater_enabled', true ) ) {
		$results  = $wpdb->get_results( $query, ARRAY_A );
		$user_ids = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $value ) {
				$field_id = ! empty( $value['field_id'] ) ? (int) $value['field_id'] : 0;
				$user_id  = ! empty( $value['user_id'] ) ? (int) $value['user_id'] : 0;
				if ( ! empty( $field_id ) && ! empty( $user_id ) ) {
					$field_visibility = xprofile_get_field_visibility_level( intval( $field_id ), intval( $user_id ) );
					if (
						! current_user_can( 'administrator' ) &&
						(
							'adminsonly' === $field_visibility ||
							(
								bp_is_active( 'friends' ) &&
								'friends' === $field_visibility &&
								false === friends_check_friendship( intval( $user_id ), bp_loggedin_user_id() )
							)
						)
					) {
						unset( $results[ $key ] );
					} else {
						$user_ids[] = $user_id;
					}
				}
			}
		}
	} else {
		$user_ids = $wpdb->get_col( $query );
	}

	return $user_ids;
}

/**
 * Return $sql of BuddyBoss Profile Search Extended sort directory.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_xprofile_sort_directory( $sql, $object, $f, $order ) {
	global $bp, $wpdb;

	$object->uid_name  = 'user_id';
	$object->uid_table = $bp->profile->table_name_data;

	$sql['select']  = "SELECT u.user_id AS id FROM {$object->uid_table} u";
	$sql['where']   = str_replace( 'u.ID', 'u.user_id', $sql['where'] );
	$sql['where'][] = "u.user_id IN (SELECT ID FROM {$wpdb->users} WHERE user_status = 0)";
	$sql['where'][] = $wpdb->prepare( 'u.field_id = %d', $f->id );
	$sql['orderby'] = 'ORDER BY u.value';
	$sql['order']   = $order;

	return $sql;
}

/**
 * Return xprofile value from BuddyBoss Profile Search Extended field.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_xprofile_get_value( $f ) {
	global $members_template;

	if ( $members_template->current_member == 0 ) {
		$users = wp_list_pluck( $members_template->members, 'ID' );
		BP_XProfile_ProfileData::get_value_byid( $f->id, $users );
	}

	$value = BP_XProfile_ProfileData::get_value_byid( $f->id, $members_template->member->ID );
	return stripslashes( $value );
}

/**
 * Return array of BuddyBoss Profile Search input field formats.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_xprofile_format( $type, $field_id ) {
	$formats = array(
		'textbox'        => array( 'text', 'decimal' ),
		'number'         => array( 'integer' ),
		'telephone'      => array( 'text' ),
		'url'            => array( 'text' ),
		'textarea'       => array( 'text' ),
		'selectbox'      => array( 'text', 'decimal' ),
		'radio'          => array( 'text', 'decimal' ),
		'multiselectbox' => array( 'set' ),
		'checkbox'       => array( 'set' ),
		'datebox'        => array( 'date' ),
	);

	if ( ! isset( $formats[ $type ] ) ) {
		return 'custom';
	}

	$formats = $formats[ $type ];
	$default = $formats[0];
	$format  = apply_filters( 'bp_ps_xprofile_format', $default, $field_id );

	return in_array( $format, $formats ) ? $format : $default;
}

/**
 * Returns array of BuddyBoss Profile Search profile options.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_xprofile_options( $field_id ) {
	$field = new BP_XProfile_Field( $field_id );
	if ( empty( $field->id ) ) {
		return array();
	}

	$options = array();
	$rows    = $field->get_children();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$options[ stripslashes( trim( $row->name ) ) ] = stripslashes( trim( $row->name ) );
		}
	}

	return $options;
}

add_filter( 'bp_ps_add_fields', 'bp_ps_anyfield_setup', 12 );
/**
 * Setup BuddyBoss Profile Search all fields.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_anyfield_setup( $fields ) {
	$f              = new stdClass();
	$f->group       = __( 'Other', 'buddyboss' );
	$f->code        = 'field_any';
	$f->name        = __( 'Search all fields', 'buddyboss' );
	$f->description = __( 'Search every profile field', 'buddyboss' );

	$f->format  = 'text';
	$f->options = array();
	$f->search  = 'bp_ps_anyfield_search';

	$fields[] = $f;
	return $fields;
}

// Hook for registering a LearnDash course field in frontend and backend in advance search.
add_filter( 'bp_ps_add_fields', 'bp_ps_learndash_course_setup' );

/**
 * Registers a LearnDash course field in frontend and backend in advance search.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $fields
 *
 * @return array
 */
function bp_ps_learndash_course_setup( $fields ) {

	// check is LearnDash plugin is activated or not.
	if ( in_array( 'sfwd-lms/sfwd_lms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		global $wpdb;

		$query = "SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s ORDER BY menu_order";

		$courses_arr = $wpdb->get_col( $wpdb->prepare( $query, 'sfwd-courses', 'publish' ) );

		$courses = array();

		if ( $courses_arr ) :

			foreach ( $courses_arr as $course ) {
				$post                 = get_post( $course );
				$courses[ $post->ID ] = get_the_title( $post->ID );
			}

		endif;

		$f              = new stdClass();
		$f->group       = __( 'LearnDash', 'buddyboss' );
		$f->id          = 'learndash_courses';
		$f->code        = 'field_learndash_courses';
		$f->name        = __( 'Courses', 'buddyboss' );
		$f->description = __( 'Courses', 'buddyboss' );
		$f->type        = 'selectbox';
		$f->format      = bp_ps_xprofile_format( 'selectbox', 'learndash_courses' );
		$f->options     = $courses;
		$f->search      = 'bp_ps_learndash_course_users_search';

		$fields[] = $f;

	}
	return $fields;
}

/**
 * Registers a Gender field in frontend and backend in advance search.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $fields
 *
 * @return array
 */
function bp_ps_gender_setup( $fields ) {

	global $wpdb;
	global $bp;

	$exists_gender = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$bp->table_prefix}bp_xprofile_fields a WHERE parent_id = 0 AND type = 'gender' " );

	if ( $exists_gender[0]->count > 0 ) {

		$field = new BP_XProfile_Field( $exists_gender[0]->id );
		if ( empty( $field->id ) ) {
			return $fields;
		}

		$options = array();
		$rows    = $field->get_children();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( '1' === $row->option_order ) {
					$option_value = 'his_' . $row->name;
				} elseif ( '2' === $row->option_order ) {
					$option_value = 'her_' . $row->name;
				} else {
					$option_value = 'their_' . $row->name;
				}
				$options[ stripslashes( trim( $option_value ) ) ] = stripslashes( trim( $row->name ) );
			}

			$f              = new stdClass();
			$f->group       = __( 'Gender', 'buddyboss' );
			$f->id          = 'xprofile_gender';
			$f->code        = 'field_xprofile_gender';
			$f->name        = __( 'Gender', 'buddyboss' );
			$f->description = __( 'Gender', 'buddyboss' );
			$f->type        = 'selectbox';
			$f->format      = bp_ps_xprofile_format( 'selectbox', 'xprofile_gender' );
			$f->options     = $options;
			$f->search      = 'bp_ps_xprofile_gender_users_search';

			$fields[] = $f;

		}
	}

	return $fields;
}

// Hook for registering a Gender field in frontend and backend in advance search.
add_filter( 'bp_ps_add_fields', 'bp_ps_gender_setup' );

/**
 * Fetch the users based on selected value in advance search.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $f
 *
 * @return array
 */
function bp_ps_xprofile_gender_users_search( $f ) {

	global $wpdb, $bp;

	$table_profile_fields = bp_core_get_table_prefix() . 'bp_xprofile_fields';
	$table_profile_data   = bp_core_get_table_prefix() . 'bp_xprofile_data';

	$gender = $f->value;
	if ( isset( $gender ) && ! empty( $gender ) ) {

		$exists_gender = $wpdb->get_results( "SELECT COUNT(*) as count, id FROM {$table_profile_fields} a WHERE parent_id = 0 AND type = 'gender' " );
		if ( ! empty( $exists_gender ) ) {

			if ( is_array( $gender ) ) {
				$gender = "'" . implode( "', '", $gender ) . "'";
				$where  = " AND value IN({$gender})";
			} else {
				$where = " AND value = '{$gender}'";
			}

			$custom_ids = $wpdb->get_col( "SELECT user_id FROM {$table_profile_data} WHERE field_id = {$exists_gender[0]->id} {$where}" );

			if ( ! empty( $custom_ids ) ) {
				return $custom_ids;
			}
		}
	}

	return array();
}

/**
 * Fetch all the users from a selected course.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $f
 *
 * @return array
 */
function bp_ps_learndash_course_users_search( $f ) {

	// check for learndash plugin is activated or not.
	if ( in_array( 'sfwd-lms/sfwd_lms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

		$course_id = $f->value;
		if ( isset( $course_id ) && ! empty( $course_id ) ) {
			$course_users = bp_ps_learndash_get_users_for_course( $course_id, '', false );

			$course_users = $course_users->results;

			if ( isset( $course_users ) && ! empty( $course_users ) ) {
				return $course_users;
			} else {
				return array();
			}
		} else {
			return array();
		}
	}
}

/**
 * Get all the users who are enrolled in the course.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int   $course_id
 * @param array $query_args
 * @param bool  $exclude_admin
 *
 * @return array|WP_User_Query
 */
function bp_ps_learndash_get_users_for_course( $course_id = 0, $query_args = array(), $exclude_admin = true ) {
	$course_user_ids = array();

	if ( empty( $course_id ) ) {
		return $course_user_ids;
	}

	$defaults = array(
		// By default WP_User_Query will return ALL users. Strange.
		'fields' => 'ID',
	);

	$query_args = bp_parse_args( $query_args, $defaults );

	if ( $exclude_admin == true ) {
		$query_args['role__not_in'] = array( 'administrator' );
	}

	$course_access_list = get_course_meta_setting( $course_id, 'course_access_list' );
	$course_user_ids    = array_merge( $course_user_ids, $course_access_list );

	$course_access_users = get_course_users_access_from_meta( $course_id );
	$course_user_ids     = array_merge( $course_user_ids, $course_access_users );

	if ( function_exists( 'learndash_get_course_groups_users_access' ) ) {
		$course_groups_users = learndash_get_course_groups_users_access( $course_id );
	} else {
		$course_groups_users = get_course_groups_users_access( $course_id );
	}
	$course_user_ids = array_merge( $course_user_ids, $course_groups_users );

	if ( ! empty( $course_user_ids ) ) {
		$course_user_ids = array_unique( $course_user_ids );
	}

	$course_expired_access_users = get_course_expired_access_from_meta( $course_id );
	if ( ! empty( $course_expired_access_users ) ) {
		$course_user_ids = array_diff( $course_access_list, $course_expired_access_users );
	}

	if ( ! empty( $course_user_ids ) ) {
		$query_args['include'] = $course_user_ids;

		$user_query = new WP_User_Query( $query_args );

		// $course_user_ids = $user_query->get_results();
		return $user_query;
	}
}

/**
 * Return results from BuddyBoss Profile Search all fields.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_anyfield_search( $f ) {
	global $bp, $wpdb;

	$filter = $f->filter;
	$value  = str_replace( '&', '&amp;', $f->value );

	$sql           = array(
		'select' => '',
		'where'  => array(),
	);
	$sql['select'] = "SELECT DISTINCT user_id, field_id FROM {$bp->profile->table_name_data}";

	switch ( $filter ) {
		case 'contains':
			$escaped                 = '%' . bp_ps_esc_like( $value ) . '%';
			$sql['where'][ $filter ] = $wpdb->prepare( 'value LIKE %s', $escaped );
			break;

		case '':
			$sql['where'][ $filter ] = $wpdb->prepare( 'value = %s', $value );
			break;

		case 'like':
			$value                   = str_replace( '\\\\%', '\\%', $value );
			$value                   = str_replace( '\\\\_', '\\_', $value );
			$sql['where'][ $filter ] = $wpdb->prepare( 'value LIKE %s', $value );
			break;
	}

	$sql   = apply_filters( 'bp_ps_field_sql', $sql, $f );
	$query = $sql['select'] . ' WHERE ' . implode( ' AND ', $sql['where'] );

	$results  = $wpdb->get_results( $query, ARRAY_A );
	$user_ids = array();
	if ( ! empty( $results ) ) {
		// Exclude repeater fields.
		$group_ids    = bp_xprofile_get_groups(
			array(
				'repeater_show_main_fields_only' => true,
				'fetch_fields'                   => true,
				'fetch_field_data'               => true,
				'user_id'                        => false,
			)
		);
		$fields_array = array();
		if ( ! empty( $group_ids ) ) {
			foreach ( $group_ids as $group_value ) {
				if ( ! empty( $group_value->id ) ) {
					$repeater_enabled = bp_xprofile_get_meta( $group_value->id, 'group', 'is_repeater_enabled', true );
					if ( ! empty( $repeater_enabled ) && 'on' === $repeater_enabled && ! empty( $group_value->fields ) ) {
						$fields_array = array_merge( $fields_array, wp_list_pluck( $group_value->fields, 'id' ) );
					}
				}
			}
		}
		foreach ( $results as $key => $value ) {
			$field_id = ! empty( $value['field_id'] ) ? (int) $value['field_id'] : 0;
			$user_id  = ! empty( $value['user_id'] ) ? (int) $value['user_id'] : 0;
			if ( ! empty( $field_id ) && ! empty( $user_id ) ) {
				if ( ! empty( $fields_array ) && in_array( $field_id, $fields_array, true ) ) {
					unset( $results[ $key ] );
					continue;
				}
				$field_visibility = xprofile_get_field_visibility_level( intval( $field_id ), intval( $user_id ) );
				if (
					! current_user_can( 'administrator' ) &&
					(
						'adminsonly' === $field_visibility ||
						(
							bp_is_active( 'friends' ) &&
							'friends' === $field_visibility &&
							false === friends_check_friendship( intval( $user_id ), bp_loggedin_user_id() )
						)
					)
				) {
					unset( $results[ $key ] );
				} else {
					$user_ids[] = $user_id;
				}
			}
		}
	}

	return $user_ids;
}

add_filter( 'bp_ps_add_fields', 'bp_ps_heading_field_setup', 11 );
/**
 * Setup BuddyBoss Profile Search headings.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_heading_field_setup( $fields ) {
	$f              = new stdClass();
	$f->group       = __( 'Other', 'buddyboss' );
	$f->code        = 'heading';
	$f->name        = __( 'Heading', 'buddyboss' );
	$f->description = __( 'Used to segregate form into sections', 'buddyboss' );

	$f->format  = 'text';
	$f->options = array();
	$f->search  = 'bp_ps_search_dummy_fields';

	$fields[] = $f;
	return $fields;
}

/**
 * Search BuddyBoss Profile Search dummy fields.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ps_search_dummy_fields( $f ) {
	return array();
}

/**
 * Registers Email Address field in frontend and backend in advance search.
 *
 * @since BuddyBoss 2.0.5
 *
 * @param array $fields Fields array.
 *
 * @return array
 */
function bb_ps_email_setup( $fields ) {

	$f              = new stdClass();
	$f->group       = __( 'General Information', 'buddyboss' );
	$f->id          = 'xprofile_email';
	$f->code        = 'field_xprofile_email';
	$f->name        = __( 'Email Address', 'buddyboss' );
	$f->description = __( 'Email Address', 'buddyboss' );
	$f->type        = 'textbox';
	$f->format      = bp_ps_xprofile_format( 'textbox', 'xprofile_email' );
	$f->search      = 'bb_ps_xprofile_email_users_search';

	$fields[] = $f;

	return $fields;
}

// Hook for registering a Gender field in frontend and backend in advance search.
add_filter( 'bp_ps_add_fields', 'bb_ps_email_setup' );

/**
 * Fetch the users based on selected value in advance search.
 *
 * @since BuddyBoss 2.0.5
 *
 * @param object $f Field object.
 *
 * @return array
 */
function bb_ps_xprofile_email_users_search( $f ) {
	global $wpdb;

	$value  = $f->value;
	$value  = str_replace( '&', '&amp;', $value );
	$filter = $f->format . '_' . ( '' === trim( $f->filter ) ? 'is' : $f->filter );

	$sql_query = "SELECT ID FROM {$wpdb->users} WHERE ";

	switch ( $filter ) {
		case 'text_contains':
			$escaped    = '%' . bp_ps_esc_like( $value ) . '%';
			$sql_query .= $wpdb->prepare( 'user_email LIKE %s', $escaped );
			break;

		case 'text_like':
			$value      = str_replace( '\\\\%', '\\%', $value );
			$value      = str_replace( '\\\\_', '\\_', $value );
			$sql_query .= $wpdb->prepare( 'user_email LIKE %s', $value );
			break;

		case 'text_is':
		default:
			$sql_query .= $wpdb->prepare( 'user_email = %s', $value );
			break;
	}

	return $wpdb->get_col( $sql_query );
}
