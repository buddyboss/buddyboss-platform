<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.2.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Exclude specific profile types from search and listing.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $qs
 * @param bool $object
 *
 * @return bool|string
 */
function bp_member_type_exclude_users_from_directory_and_searches( $qs = false, $object = false ) {

	_deprecated_function( __FUNCTION__, '1.2.9' );

	$args = bp_parse_args( $qs );

	if ( $object !== 'members' ) {
		return $qs;
	}

	if ( bp_is_members_directory() && isset( $args['scope'] ) && 'all' === $args['scope'] ) {

		// get removed profile type post ids
		$bp_member_type_ids = bp_get_removed_member_types();
		// get removed profile type names/slugs
		$bp_member_type_names = array();
		if ( isset( $bp_member_type_ids ) && ! empty( $bp_member_type_ids ) ) {
			foreach ( $bp_member_type_ids as $single ) {
				$bp_member_type_names[] = $single['name'];
			}
		}

		if ( ! empty( $args['member_type__not_in'] ) ) {
			if ( is_array( $args['member_type__not_in'] ) ) {
				$args['member_type__not_in'] = array_merge( $args['member_type__not_in'], $bp_member_type_names );
			} else {
				$args['member_type__not_in'] = $args['member_type__not_in'] . ',' . implode( ',', $bp_member_type_names );
			}
		} else {
			$args['member_type__not_in'] = implode( ',', $bp_member_type_names );
		}
		$qs = build_query( $args );
	}

	return $qs;
}



/**
 * Fix all member count.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $count
 *
 * @return int
 */
function bp_fixed_all_member_type_count( $count ) {

	_deprecated_function( __FUNCTION__, '1.2.9' );

	$exclude_user_ids = bp_get_users_of_removed_member_types();
	if ( isset( $exclude_user_ids ) && ! empty( $exclude_user_ids ) ) {
		$count = $count - count( $exclude_user_ids );
	}
	return $count;
}

/**
 * Member directory tabs content.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $query
 */
function bp_member_type_query( $query ) {

	_deprecated_function( __FUNCTION__, '1.2.9' );

	global $wpdb;

	$cookie_scope = filter_input( INPUT_COOKIE, 'bp-members-scope', FILTER_VALIDATE_INT );
	// $post_scope   = filter_input( INPUT_POST, 'scope', FILTER_VALIDATE_INT );
	$post_scope = isset( $_POST['scope'] ) ? intval( $_POST['scope'] ) : null;

	if ( $post_scope ) {
		$type_id = $post_scope;
	} elseif ( $cookie_scope ) {
		$type_id = $cookie_scope;
	}

	if ( isset( $type_id ) ) {

		// Alter SELECT with INNER JOIN
		$query->uid_clauses['select'] .= " INNER JOIN {$wpdb->term_relationships} r ON u.{$query->uid_name} = r.object_id ";

		// Alter WHERE clause
		$query_where_glue             = empty( $query->uid_clauses['where'] ) ? ' WHERE ' : ' AND ';
		$query->uid_clauses['where'] .= $query_where_glue . "r.term_taxonomy_id = {$type_id} ";
	}
}

/**
 * Adds a filter on shortcode.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $query_string
 * @param $object
 *
 * @return string
 */
function bp_member_type_shortcode_filter( $query_string, $object ) {

	_deprecated_function( __FUNCTION__, '1.2.9' );

	if ( empty( $object ) ) {
		return '';
	}

	if ( 'members' == $object && bp_current_component() !== 'members' ) {
		$_COOKIE['bp-members-filter'] = 'alphabetical';
		$_COOKIE['bp-members-scope']  = 'all';
	}

	return $query_string;
}
