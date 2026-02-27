<?php
/**
 * Deprecated functions.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss 3.0.0
 */

// ──────────────────────────────────────────────────────────────────────────────
// Groups admin tabs (moved from bp-core-admin-functions.php).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Output the tabs in the Groups admin area.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_side_panel()} to register panels in Settings 2.0.
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_groups_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );

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
	$tabs = apply_filters( 'bp_core_admin_groups_tabs', bp_core_get_groups_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $tab_data['class'] . ' ' . $active_class : $tab_data['class'] . ' ' . $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo wp_kses_post( $tabs_html );

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] No replacement in Settings 2.0. Use {@see bb_register_side_panel()}.
	 */
	do_action_deprecated( 'bp_admin_groups_tabs', array(), 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );
}

/**
 * Register tabs for the BuddyBoss > Groups screens.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_side_panel()} to register panels in Settings 2.0.
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 *
 * @return array
 */
function bp_core_get_groups_admin_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );

	$tabs = array();

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-groups' ), 'admin.php' ) ),
		'name'  => __( 'All Groups', 'buddyboss' ),
		'class' => 'bp-all-groups',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_side_panel()} to register panels in Settings 2.0.
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters_deprecated(
		'bp_core_get_groups_admin_tabs',
		array( $tabs ),
		'BuddyBoss [BBVERSION]',
		'bb_register_side_panel()'
	);
}

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Groups admin page functions (moved from bp-groups-admin.php).
// Settings 2.0 replaces: listing → GroupsListScreen.js,
// edit/create → GroupEditModal.js, delete → React modal.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Set up the Groups admin page.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 */
function bp_groups_admin_load() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );
}

/**
 * Handle save/update of screen options for the Groups component admin screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 *
 * @return string|int Option value. False to abandon update.
 */
function bp_groups_admin_screen_options( $value, $option, $new_value ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );

	if ( 'buddyboss_page_bp_groups_per_page' !== $option && 'buddyboss_page_bp_groups_network_per_page' !== $option ) {
		return $value;
	}

	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 ) {
		return $value;
	}

	return $new_value;
}

/**
 * Select the appropriate Groups admin screen, and output it.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 */
function bp_groups_admin() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );
}

/**
 * Display the single groups edit screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal.
 */
function bp_groups_admin_edit() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Display the single groups create screen.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Create Modal.
 */
function bp_groups_admin_create() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Create Modal' );
}

/**
 * Process the data of newly created group from the backend.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::create_group()}.
 */
function bp_process_create_group_admin() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::create_group()' );
}

/**
 * Display the Group delete confirmation screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Delete Modal.
 */
function bp_groups_admin_delete() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Delete Modal' );
}

/**
 * Display the Groups admin index screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see GroupsListScreen.js}.
 */
function bp_groups_admin_index() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 GroupsListScreen' );
}

/**
 * Markup for the single group's Settings metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Permissions tab).
 *
 * @param object $item Information about the current group.
 */
function bp_groups_admin_edit_metabox_settings( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Markup for the single group's Group Hierarchy metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Integrations tab).
 *
 * @param object $item Information about the current group.
 */
function bp_groups_admin_edit_metabox_group_parent( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Output the markup for a single group's Add New Members metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Members tab).
 *
 * @param BP_Groups_Group $item The BP_Groups_Group object for the current group.
 */
function bp_groups_admin_edit_metabox_add_new_members( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Renders the Members metabox on single group pages.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Members tab).
 *
 * @param BP_Groups_Group $item The BP_Groups_Group object for the current group.
 */
function bp_groups_admin_edit_metabox_members( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Renders the Status metabox for the Groups admin edit screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal.
 *
 * @param object $item Information about the currently displayed group.
 */
function bp_groups_admin_edit_metabox_status( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Create pagination links out of a BP_Group_Member_Query.
 *
 * @since BuddyPress 1.8.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Members tab) with AJAX pagination.
 *
 * @param BP_Group_Member_Query $query       A BP_Group_Member_Query object.
 * @param string                $member_type member|mod|admin|banned.
 *
 * @return string Empty string.
 */
function bp_groups_admin_create_pagination_links( $query, $member_type ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );

	return '';
}

/**
 * Get a set of usernames corresponding to a set of user IDs.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 *
 * @param array $user_ids Array of user IDs.
 *
 * @return array Array of user_logins corresponding to $user_ids.
 */
function bp_groups_admin_get_usernames_from_ids( $user_ids = array() ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );

	$usernames = array();
	$users     = new WP_User_Query(
		array(
			'blog_id' => 0,
			'include' => $user_ids,
		)
	);

	foreach ( (array) $users->results as $user ) {
		$usernames[] = $user->user_login;
	}

	return $usernames;
}

/**
 * AJAX handler for group member autocomplete requests.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::member_autocomplete()}.
 */
function bp_groups_admin_autocomplete_handler() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::member_autocomplete()' );

	wp_die( -1 );
}

// Register deprecated hooks so third-party code that triggers them gets a deprecation notice.
add_action( 'wp_ajax_bp_group_admin_member_autocomplete', 'bp_groups_admin_autocomplete_handler' );
add_action( 'admin_post_bp_create_group_admin', 'bp_process_create_group_admin' );

// Note: bp_groups_list_table_get_views is fired via do_action_deprecated() inside
// BB_Admin_Groups_Ajax::get_groups() each time the admin groups list is loaded.
// It is deprecated in favour of the 'bb_admin_groups_list_views' filter.
// See: src/bp-core/admin/classes/class-bb-admin-groups-ajax.php
