<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.1.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gets the post id of particular group type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $group_type
 *
 * @return mixed
 */
function bp_get_group_type_post_id( $group_type = '' ) {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_group_get_group_type_id' );

	$args = array(
		'post_type'		=>	'bp-group-type',
		'meta_query'	=>	array(
			array(
				'key'   => '_bp_group_type_key',
				'value'	=>	$group_type
			)
		)
	);
	$group_type_query = new WP_Query( $args );

	$posts = $group_type_query->posts;

	$id = ( is_array( $posts ) && isset( $posts[0]->ID ) ) ? $posts[0]->ID : 0;

	return $id;
}

/**
 * Get excluded group types.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_removed_group_types(){

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_groups_get_excluded_group_types' );

	$bp_group_type_ids = array();
	$post_type = bp_get_group_type_post_type();
	$bp_group_type_args = array(
		'post_type' => $post_type,
		'meta_query' => array(
			array(
				'key'     => '_bp_group_type_enable_remove',
				'value'   => 1,
				'compare' => '=',
			),
		),
		'nopaging' => true,
	);

	$bp_group_type_query = new WP_Query($bp_group_type_args);
	if ($bp_group_type_query->have_posts()):
		while ($bp_group_type_query->have_posts()):
			$bp_group_type_query->the_post();

			$post_id = get_the_ID();
			$name = bp_get_group_type_key( $post_id );
			$bp_group_type_ids[] = array(
				'ID' => $post_id,
				'name' => $name,
			);
		endwhile;
	endif;
	wp_reset_query();
	wp_reset_postdata();
	return $bp_group_type_ids;
}

/**
 * Excludes specific group types from search and listing.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $qs
 * @param bool $object
 *
 * @return bool|string
 */
function bp_group_type_exclude_groups_from_directory_and_searches( $qs=false, $object=false ) {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_groups_exclude_group_type' );

	$exclude_group_ids = array_unique( bp_get_groups_of_removed_group_types() );

	if( $object != 'groups' )
		return $qs;

	$args = bp_parse_args( $qs );

	if( ! empty( $args['exclude'] ) )
		$args['exclude'] = $args['exclude'] . ',' . implode( ',', $exclude_group_ids );
	else
		$args['exclude'] = implode( ',', $exclude_group_ids );

	$qs = build_query( $args );

	return $qs;
}

/**
 * Get group count of group type tabs groups.
 *
 * @param string $group_type The group type.
 * @param string $taxonomy The group taxonomy.
 */
function bp_get_total_count_by_group_types( $group_type = '', $taxonomy = 'bp_group_type' ) {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_group_get_count_by_group_type' );

	global $wpdb;

	$group_types = bp_groups_get_group_types();

	if ( empty( $group_type ) || empty( $group_types[ $group_type ] ) ) {
		return false;
	}

	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
	}

	$bp_group_type_query         = array(
		'select' => "SELECT t.slug, tt.count FROM {$wpdb->term_taxonomy} tt LEFT JOIN {$wpdb->terms} t",
		'on'     => 'ON tt.term_id = t.term_id',
		'where'  => $wpdb->prepare( 'WHERE tt.taxonomy = %s', $taxonomy ),
	);
	$bp_get_group_type_count = $wpdb->get_results( join( ' ', $bp_group_type_query ) );

	restore_current_blog();

	$bp_group_type_count = wp_filter_object_list( $bp_get_group_type_count, array( 'slug' => $group_type ), 'and', 'count' );
	$bp_group_type_count = array_values( $bp_group_type_count );
	if ( empty( $bp_group_type_count ) ) {
		return 0;
	}
	return (int) $bp_group_type_count[0];
}

/**
 * Return group type key.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 * @return mixed|string
 */
function bp_get_group_type_key( $post_id ) {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_group_get_group_type_key' );

	if ( empty( $post_id) ) {
		return '';
	}

	$key = get_post_meta( $post_id, '_bp_group_type_key', true );

	// Fallback to legacy way of generating group type key from singular label
	// if Key is not set by admin user
	if ( empty( $key ) ) {
		$key = get_post_field( 'post_name', $post_id );
		$term = term_exists( sanitize_key( $key ), 'bp_group_type' );
		if ( 0 !== $term && null !== $term ) {
			$digits = 3;
			$unique = rand(pow(10, $digits-1), pow(10, $digits)-1);
			$key = $key.$unique;
		}
		update_post_meta( $post_id, '_bp_group_type_key', sanitize_key( $key ) );
	}

	return apply_filters( 'bp_get_group_type_key', $key );
}

/**
 * Return array of features that the group type post type supports.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_group_type_post_type_supports() {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_groups_get_group_type_post_type_supports' );

	/**
	 * Filters the features that the group type post type supports.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Supported features.
	 */
	return apply_filters( 'bp_get_group_type_post_type_supports', array(
		'page-attributes',
		'title',
	) );
}

/**
 * Return labels used by the group type post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_group_type_post_type_labels() {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_groups_get_group_type_post_type_labels' );

	/**
	 * Filters group type post type labels.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters( 'bp_get_group_type_post_type_labels', array(
		'add_new_item'          => __( 'New Group Type', 'buddyboss' ),
		'all_items'             => __( 'Group Types', 'buddyboss' ),
		'edit_item'             => __( 'Edit Group Type', 'buddyboss' ),
		'menu_name'             => __( 'Social Groups', 'buddyboss' ),
		'name'                  => __( 'Group Types', 'buddyboss' ),
		'new_item'              => __( 'New Group Type', 'buddyboss' ),
		'not_found'             => __( 'No Group Types found', 'buddyboss' ),
		'not_found_in_trash'    => __( 'No Group Types found in trash', 'buddyboss' ),
		'search_items'          => __( 'Search Group Types', 'buddyboss' ),
		'singular_name'         => __( 'Group Type', 'buddyboss' ),
	) );
}

/**
 * Returns the name of the group type post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string The name of the group type post type.
 */
function bp_get_group_type_post_type() {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_groups_get_group_type_post_type' );

	/**
	 * Filters the name of the group type post type.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value group Type post type name.
	 */
	return apply_filters( 'bp_get_group_type_post_type', buddypress()->group_type_post_type );
}

/**
 * Output the name of the group type post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string   custom post type of group type.
 */
function bp_group_type_post_type() {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_groups_group_type_post_type' );

	echo bp_get_group_type_post_type();
}

/**
 * Get groups removed from group type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_get_groups_of_removed_group_types() {

	_deprecated_function( __FUNCTION__, '1.1.9', 'bp_groups_get_excluded_group_ids_by_type' );

	$group_id = array();

	// get removed group type post ids
	$bp_group_type_ids = bp_get_removed_group_types();

	// get removed group type names/slugs
	$bp_group_type_names = array();
	if( isset($bp_group_type_ids) && !empty($bp_group_type_ids) ){
		foreach($bp_group_type_ids as $single){
			$bp_group_type_names[] = $single['name'];
		}
	}

	// get group group ids
	if( isset($bp_group_type_names) && !empty($bp_group_type_names) ){
		foreach($bp_group_type_names as $type_name){
			$group_ids = bp_get_removed_group_types( $type_name );

			if( isset($group_ids) && !empty($group_ids) ){
				foreach($group_ids as $single){
					$group_id[] = $single['id'];
				}
			}
		}
	}

	return bp_group_ids_array_flatten( $group_id );

}

/**
 * Gets profile type id.
 *
 * @since BuddyBoss 1.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param type $type_name
 *
 * @return type int
 */
function bp_member_type_type_id( $type_name ) {

	_deprecated_function( __FUNCTION__, '1.1.9' );

	global $wpdb;
	$type_name = strtolower($type_name);
	$type_name = str_replace(array(' ', ','), array('-', '-'), $type_name);
	$type_id = $wpdb->get_col( "SELECT t.term_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.slug = '" . $type_name . "' AND  tt.taxonomy = 'bp_member_type' " );
	return ! isset( $type_id[ 0 ] ) ? '' : $type_id[ 0 ];
}


/**
 * Gets an active member by type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $type_id
 *
 * @return array
 */
function bp_active_member_type_by_type( $type_id ) {

	_deprecated_function( __FUNCTION__, '1.1.9' );

	global $wpdb, $bp;
	$member_ids = array();
	if ( empty ( $type_id ) ) {
		return $member_ids;
	}
	$get_user_ids = $wpdb->get_col( "SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->term_relationships} r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = " . $type_id );
	if ( isset( $get_user_ids ) && !empty( $get_user_ids ) ) {
		foreach ( $get_user_ids as $single ) {
			$table = bp_core_get_table_prefix() . 'bp_activity';
			$member_activity = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} a WHERE a.user_id = " . $single );
			if ( $member_activity > 0 ) {
				$member_ids[] = $single;
			}
		}
	}
	return $member_ids;
}
