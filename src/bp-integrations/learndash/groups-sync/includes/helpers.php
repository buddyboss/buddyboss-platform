<?php

function bp_learndash_groups_sync() {
	global $learndash_buddypress_groups_sync;

	return $learndash_buddypress_groups_sync;
}

function bp_learndash_groups_sync_get_settings( $key = null, $default = null ) {
	$options = get_option( 'learndash_settings_buddypress_groups_sync', [
		'auto_create_bp_group'        => true,
		'auto_bp_group_privacy'       => 'private',
		'auto_bp_group_invite_status' => 'mods',
		'auto_sync_leaders'           => true,
		'auto_sync_leaders_role'      => 'admin',
		'auto_sync_students'          => true,
		'auto_delete_bp_group'        => false,
		'display_bp_group_cources'    => true,
	] );

	if ( ! $key ) {
		return $options;
	}

	return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

function bp_learndash_groups_sync_get_unassociated_bp_groups( $args = [], $include = 0 ) {
	$meta_query = [
		'relation' => 'OR',
		[
			'key'   => 'learndash_group_id',
			'value' => [ 0, '' ],
		],
		[
			'key'     => 'learndash_group_id',
			'compare' => 'NOT EXISTS',
		],
	];

	if ( $include ) {
		$meta_query[] = [
			'key'   => 'learndash_group_id',
			'value' => is_array( $include ) ? $include : [ $include ]
		];
	}

	return groups_get_groups( [
		'orderby'    => 'name',
		'order'      => 'asc',
		'meta_query' => [ $meta_query ],
		'per_page'   => - 1
	] )['groups'];
}

function bp_learndash_groups_sync_get_unassociated_ld_groups( $args = [] ) {
	$query_args = wp_parse_args( $args, [
		'posts_per_page' => - 1,
		'post_type'      => 'groups',
		'orderby'        => 'name',
		'order'          => 'asc',
		'post_status'    => 'publish',
		'meta_query'     => [
			[
				'relation' => 'OR',
				[
					'key'   => 'buddypress_group_id',
					'value' => [ 0, '' ],
				],
				[
					'key'     => 'buddypress_group_id',
					'compare' => 'NOT EXISTS',
				],
			]
		]
	] );

	return get_posts( $query_args );
}

function bp_learndash_groups_sync_get_ld_groups_has_match_name( $ld_group ) {
	global $wpdb;

	$bp    = buddypress();
	$group = get_post( $ld_group );

	$bp_groups = $wpdb->get_results(
		$wpdb->prepare( "SELECT g.* FROM {$bp->groups->table_name} g WHERE g.name = %s", $group->post_title )
	);

	if ( ! $bp_groups || is_wp_error( $bp_groups ) ) {
		return [];
	}

	$groups = array_map( 'groups_get_group', wp_list_pluck( $bp_groups, 'id' ) );

	return array_filter( $groups, function ( $group ) {
		return ! groups_get_groupmeta( $group->id, 'learndash_group_id' );
	} );
}

function bp_learndash_groups_sync_generate_bp_group( $ld_group, $sync_leaders = null, $sync_students = null ) {
	$generator = new LearnDash_BuddyPress_Groups_Sync_Generator( $ld_group );

	$generator->generate();
	$generator->sync_all( $sync_leaders, $sync_students );

	return $generator->get_bp_group();
}

function bp_learndash_groups_sync_associate_bp_group( $ld_group, $bp_group, $sync_leaders = null, $sync_students = null ) {
	$generator = new LearnDash_BuddyPress_Groups_Sync_Generator( $ld_group );

	if ( ! $bp_group ) {
		$generator->dissociate();
	} else {
		$generator->associate( $bp_group );
		$generator->sync_all( $sync_leaders, $sync_students );
	}

	return $generator->get_bp_group();
}

function bp_learndash_groups_sync_get_associated_bp_group( $ld_group ) {
	$bp_group_id = get_post_meta( $ld_group, 'buddypress_group_id', true );

	return $bp_group_id ? groups_get_group( $bp_group_id ) : null;
}

function bp_learndash_groups_sync_get_associated_ld_group( $bp_group ) {
	$ld_group_id = groups_get_groupmeta( $bp_group, 'learndash_group_id', true );

	return $ld_group_id ? get_post( $ld_group_id ) : null;
}

function bp_learndash_groups_sync_check_associated_ld_group( $bp_group ) {
	$ld_group_id = groups_get_groupmeta( $bp_group, 'learndash_group_id', true );

	return empty( $ld_group_id ) ? false : $ld_group_id;
}

/**
 * Add Submenu in BuddyPress Group Courses Tab
 *
 */
function bp_learndash_groups_sync_courses_sub_menu() {
	$sub_menus = (array) apply_filters( 'bp_learndash_groups_sync_courses_submenu', array() );

	if ( count( $sub_menus ) > 1 ) {
		?>
        <nav class="bp-navs bp-subnavs no-ajax group-subnav" id="subnav" role="navigation"
             aria-label="Group administration menu">
            <ul class="subnav">
				<?php

				foreach ( $sub_menus as $menu ) {
					global $bp;

					$class = 'bp-groups-courses-tab ';
					if ( ! empty( $bp->current_action ) && $menu['slug'] === $bp->current_action ) {
						$class .= 'current selected';
					}
					?>
                    <li id="edit-details-groups-li" class="<?php echo $class; ?>">
                        <a href="<?php echo $menu['link'] . $menu['slug']; ?>" id="edit-details">
							<?php echo $menu['label']; ?>
                        </a>
                    </li>
					<?php
				}
				?>
            </ul>
        </nav>
		<?php
	}
}
