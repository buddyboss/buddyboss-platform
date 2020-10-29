<?php
/**
 * BuddyBoss Moderation Functions.
 *
 * Functions for the Moderation component.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Moderation Core functions
 */

/**
 * Retrieve an Moderation reports.
 *
 * The bp_moderation_get() function shares all arguments with
 * BP_Moderation::get().
 *
 * @since BuddyBoss 1.5.4
 *
 * @param array|string $args See BP_Moderation::get() for description.
 *
 * @return array $moderation See BP_Moderation::get() for description.
 * @see   BP_Moderation::get() For more information on accepted arguments
 *        and the format of the returned value.
 *
 */
function bp_moderation_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'               => false,
			// Maximum number of results to return.
			'user_id'           => false,
			// Filter moderation reported by particular user.
			'fields'            => 'all',
			'page'              => 1,
			// Page 1 without a per_page will result in no pagination.
			'per_page'          => false,
			// results per page.
			'sort'              => 'DESC',
			'order_by'          => 'date_updated',
			// sort ASC or DESC.
			// Phpcs:ignore
			'meta_query'        => false,
			// Filter by moderation meta. See WP_Meta_Query for format.
			'date_query'        => false,
			// Filter by date. See first parameter of WP_Date_Query for format.
			'filter_query'      => false,
			'exclude'           => false,
			// Comma-separated list of moderation IDs to exclude.
			'in'                => false,
			// Comma-separated list or array of moderation IDs to which you
			// want to limit the query.
			'exclude_types'     => false,
			// Comma-separated list of moderation item types to exclude.
			'in_types'          => false,
			// Comma-separated list or array of moderation item types to which you
			// want to limit the query.
			'update_meta_cache' => true,
			'display_reporters' => false,
			'count_total'       => false,

			/**
			 * Pass filters as an array -- all filter items can be multiple values comma separated:
			 * array(
			 *     'item_id'       => false, // Item ID to filter on eg. Activity ID, Groups ID, User ID etc.
			 *     'hide_sitewide' => false, // filter by hidden items e.g. 0, 1.
			 *     'blog_id'       => false, // Blog ID to filter on.
			 * );
			 */
			'filter'            => array(),
		),
		'moderation_get'
	);

	$moderation = BP_Moderation::get(
		array(
			'page'              => $r['page'],
			'per_page'          => $r['per_page'],
			'user_id'           => $r['user_id'],
			'max'               => $r['max'],
			'sort'              => $r['sort'],
			'order_by'          => $r['order_by'],
			'meta_query'        => $r['meta_query'], // Phpcs:ignore
			'date_query'        => $r['date_query'],
			'filter_query'      => $r['filter_query'],
			'filter'            => $r['filter'],
			'exclude_types'     => $r['exclude_types'],
			'in_types'          => $r['in_types'],
			'exclude'           => $r['exclude'],
			'in'                => $r['in'],
			'update_meta_cache' => $r['update_meta_cache'],
			'display_reporters' => $r['display_reporters'],
			'count_total'       => $r['count_total'],
			'fields'            => $r['fields'],
		)
	);

	/**
	 * Filters the requested moderation item(s).
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array         $r          Arguments used for the moderation query.
	 *
	 * @param BP_Moderation $moderation Requested moderation object.
	 */
	return apply_filters_ref_array(
		'bp_moderation_get',
		array(
			&$moderation,
			&$r,
		)
	);
}

/**
 * Retrieve sitewide hidden items ids of particular item type.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $type Moderation items type.
 *
 * @return array $moderation See BP_Moderation::get() for description.
 *
 */
function bp_moderation_get_sitewide_hidden_item_ids( $type ) {
	return bp_moderation_get(
		array(
			'in_types'          => $type,
			'update_meta_cache' => false,
			'filter'            => array(
				'hide_sitewide' => 1,
			),
		)
	);
}

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_moderation_tabs( $active_tab = '' ) {

	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_moderation_tabs', bp_core_get_moderation_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $tab_data['class'] . ' ' . $active_class : $tab_data['class'] . ' ' . $idle_class;
		$tabs_html  .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	do_action( 'bp_admin_moderation_tabs' );
}

/**
 * Register tabs for the BuddyBoss > Moderation screens.
 *
 * @since BuddyBoss 1.5.4
 *
 * @param string $active_tab
 *
 * @return array
 */
function bp_core_get_moderation_admin_tabs( $active_tab = '' ) {

	$tabs = array();

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-moderation' ), 'admin.php' ) ),
		'name'  => __( 'Reported Content', 'buddyboss' ),
		'class' => 'bp-reported-content',
	);


	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-moderation', 'tab' => 'blocked-members' ), 'admin.php' ) ),
		'name'  => __( 'Blocked Members', 'buddyboss' ),
		'class' => 'bp-blocked-members',
	);

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-moderation', 'tab' => 'report-categories' ), 'admin.php' ) ),
		'name'  => __( 'Report Categories', 'buddyboss' ),
		'class' => 'bp-report-categories',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_moderation_admin_tabs', $tabs );
}
