<?php
/**
 * Deprecated functions.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss 3.0.0
 */

// ──────────────────────────────────────────────────────────────────────────────
// Activity Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_activity_register_fields` hook after
 * Settings 2.0 finishes registering activity fields.
 *
 * The original hook passed a `BP_Admin_Setting_Activity` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro access-control) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_activity_after_register_settings_fields'} instead.
 */
add_action(
	'bb_activity_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_activity_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_activity_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Groups Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_groups_register_fields` hook after
 * Settings 2.0 finishes registering groups fields.
 *
 * The original hook passed a BP_Admin_Setting_Groups instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_groups_after_register_settings_fields'} and bb_register_feature_field() instead.
 */
add_action(
	'bb_groups_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_groups_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_groups_after_register_settings_fields'
		);
	}
);

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

// Note: bp_groups_admin_comment_row_actions is no longer fired.
// Row actions are now handled natively by the Settings 2.0 React UI.
// No consumers found in Platform or Pro — safe to drop without apply_filters_deprecated().

// Note: bp_groups_admin_row_class is no longer fired.
// Settings 2.0 renders group rows via React; CSS classes are managed client-side.

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Group Type cache clearing (replaced by BB_Admin_Groups_Ajax::bb_clear_group_type_cache()).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Clear the group_type cache when group type post is updated.
 *
 * @since BuddyBoss 2.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see BB_Admin_Groups_Ajax::bb_clear_group_type_cache()}.
 *
 * @param int $post_id Post ID.
 */
function bb_groups_clear_group_type_cache_on_update( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::bb_clear_group_type_cache()' );
}

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Group Type CPT functions (replaced by Settings 2.0 Group Types AJAX).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Save group type post meta box data.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::create_group_type()} / {@see BB_Admin_Groups_Ajax::update_group_type()}.
 *
 * @param int $post_id Post ID of the group type.
 */
function bp_save_group_type_post_meta_box_data( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::create_group_type() / BB_Admin_Groups_Ajax::update_group_type()' );
}

/**
 * Save group type role labels post meta box data.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::create_group_type()} / {@see BB_Admin_Groups_Ajax::update_group_type()}.
 *
 * @param int $post_id Post ID of the group type.
 */
function bp_save_group_type_role_labels_post_meta_box_data( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::create_group_type() / BB_Admin_Groups_Ajax::update_group_type()' );
}

/**
 * Register actions and filters for group types admin.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now managed via Settings 2.0.
 */
function bp_register_group_type_sections_filters_actions() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Process bulk group type changes from admin dropdown.
 *
 * @since BuddyPress 2.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::bulk_action()}.
 *
 * @param string $doaction Current bulk action being processed.
 */
function bp_groups_admin_process_group_type_bulk_changes( $doaction ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::bulk_action()' );
}

/**
 * Display admin notice upon group type bulk update.
 *
 * @since BuddyPress 2.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 toast notifications.
 */
function bp_groups_admin_groups_type_change_notice() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );
}

/**
 * Add custom columns to group type post list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now listed via Settings 2.0.
 *
 * @param array $columns Existing columns.
 *
 * @return array
 */
function bp_group_type_add_column( $columns ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $columns;
}

/**
 * Display data for group type columns in list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now listed via Settings 2.0.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function bp_group_type_show_data( $column, $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Make group type list table columns sortable.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now listed via Settings 2.0.
 *
 * @param array $columns Sortable columns.
 *
 * @return array
 */
function bp_group_type_add_sortable_columns( $columns ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $columns;
}

/**
 * Hide quick edit link from group type post row actions.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now managed via Settings 2.0 modals.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Post object.
 *
 * @return array
 */
function bp_group_type_hide_quick_edit( $actions, $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $actions;
}

/**
 * Register meta boxes for group type post type.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group type editing is now handled by Settings 2.0 modals.
 */
function bp_group_type_custom_meta_boxes() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Generate group type label meta box.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bp_group_type_labels_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Generate group type permissions meta box.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bp_group_type_permissions_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Display label color selector metabox for group types.
 *
 * @since BuddyBoss 2.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bb_group_type_labelcolor_metabox( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Display shortcode metabox for group type admin edit.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bp_group_shortcode_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Render Group Type metabox on the single group admin edit screen.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal.
 *
 * @param BP_Groups_Group|null $group Group object.
 */
function bp_groups_admin_edit_metabox_group_type( ?BP_Groups_Group $group = null ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Process group type update from admin edit screen.
 *
 * Hooked to `bp_group_admin_edit_after`. Bails when legacy nonce is absent.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::save_group()}.
 *
 * @param int $group_id Group ID.
 */
function bp_groups_process_group_type_update( $group_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::save_group()' );
}

/**
 * Output jQuery to highlight Groups menu when on Group Types CPT page.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 */
function bp_group_type_show_correct_current_menu() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Set correct menu parent for Group Types CPT screens.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 *
 * @param string $parent_file The parent file.
 *
 * @return string
 */
function bp_group_type_set_platform_tab_submenu_active( $parent_file ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $parent_file;
}

/**
 * Output Groups admin tabs on the Group Types listing page.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 */
function bp_groups_admin_group_type_listing_add_groups_tab() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Add sorting filter for Group Types CPT list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 */
function bp_group_type_add_request_filter() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Sort Group Types CPT list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 *
 * @param array $qv Query vars.
 *
 * @return array
 */
function bp_group_type_sort_items( $qv ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $qv;
}


// ──────────────────────────────────────────────────────────────────────────────
// Members / Connections settings hooks (moved to Settings 2.0).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated xprofile register_fields hook for backward compatibility.
 *
 * Legacy Settings 1.0 passed a BP_Admin_Setting_tab instance that third-party
 * plugins called ->add_section() / ->add_field() on. Settings 2.0 uses
 * bb_register_feature_field() instead. Third-party/Pro plugins should hook into
 * 'bb_members_after_register_settings_fields'.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_xprofile_register_fields_hook() {
	if ( ! function_exists( 'bb_register_feature' ) || ! bp_is_active( 'xprofile' ) ) {
		return;
	}

	/**
	 * Fires to register xProfile tab settings fields and section.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_members_after_register_settings_fields'} hook with bb_register_feature_field().
	 *
	 * @param null $deprecated No longer passes BP_Admin_Setting_tab instance.
	 */
	do_action_deprecated(
		'bp_admin_setting_xprofile_register_fields',
		array( null ),
		'BuddyBoss [BBVERSION]',
		'bb_members_after_register_settings_fields'
	);
}

add_action( 'bb_members_after_register_settings_fields', 'bb_deprecated_xprofile_register_fields_hook', 0 );

/**
 * Fire deprecated friends register_fields hook for backward compatibility.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_friends_register_fields_hook() {
	if ( ! function_exists( 'bb_register_feature' ) || ! bp_is_active( 'friends' ) ) {
		return;
	}

	/**
	 * Fires to register Friends tab settings fields and section.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_members_after_register_settings_fields'} hook with bb_register_feature_field().
	 *
	 * @param null $deprecated No longer passes BP_Admin_Setting_tab instance.
	 */
	do_action_deprecated(
		'bp_admin_setting_friends_register_fields',
		array( null ),
		'BuddyBoss [BBVERSION]',
		'bb_members_after_register_settings_fields'
	);
}

add_action( 'bb_members_after_register_settings_fields', 'bb_deprecated_friends_register_fields_hook', 0 );

// ──────────────────────────────────────────────────────────────────────────────
// Members settings save hooks (backward-compatible with Settings 1.0 tabs).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated legacy setting save hooks for backward compatibility.
 *
 * Legacy Settings 1.0 fires do_action('bp_admin_tab_setting_save', $tab_name)
 * when any settings tab is saved. Third-party plugins may hook into this for
 * 'bp-xprofile' or 'bp-friends' tab names. This bridge ensures those hooks
 * still fire when members settings are saved via Settings 2.0.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_members_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when xprofile settings are saved.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-xprofile' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);

	// Fire for friends tab if connection settings were included.
	if ( bp_is_active( 'friends' ) ) {

		/**
		 * Fires when friends settings are saved.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
		 *
		 * @param string $tab_name The tab name.
		 */
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-friends' ),
			'BuddyBoss [BBVERSION]',
			'bb_admin_save_feature_settings_after'
		);
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// XProfile admin rendering hooks (replaced by Settings 2.0 React UI).
// These hooks only fired in the legacy wp-admin XProfile field editor.
// The data hooks (xprofile_group_before_save, xprofile_field_before_save, etc.)
// are preserved because they fire from within BP_XProfile_Group::save() and
// BP_XProfile_Field::save() which are still used by the AJAX handler.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Register deprecation notices for legacy XProfile admin rendering hooks.
 *
 * These hooks were used to add custom UI in the legacy XProfile field editor
 * admin page. Since Settings 2.0 uses a React interface, these rendering hooks
 * no longer fire. Third-party plugins should extend the React UI instead.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_xprofile_admin_rendering_hooks() {
	$deprecated_hooks = array(
		'xprofile_group_admin_after_description' => __( 'XProfile group admin after description', 'buddyboss' ),
		'xprofile_group_before_submitbox'        => __( 'XProfile group before submitbox', 'buddyboss' ),
		'xprofile_group_submitbox_start'         => __( 'XProfile group submitbox start', 'buddyboss' ),
		'xprofile_group_after_submitbox'         => __( 'XProfile group after submitbox', 'buddyboss' ),
		'xprofile_field_before_contentbox'       => __( 'XProfile field before contentbox', 'buddyboss' ),
		'xprofile_field_after_contentbox'        => __( 'XProfile field after contentbox', 'buddyboss' ),
		'xprofile_field_before_submitbox'        => __( 'XProfile field before submitbox', 'buddyboss' ),
		'xprofile_field_submitbox_start'         => __( 'XProfile field submitbox start', 'buddyboss' ),
		'xprofile_field_after_submitbox'         => __( 'XProfile field after submitbox', 'buddyboss' ),
		'xprofile_field_after_sidebarbox'        => __( 'XProfile field after sidebarbox', 'buddyboss' ),
		'xprofile_field_additional_options'       => __( 'XProfile field additional options', 'buddyboss' ),
		'xprofile_admin_field_name_legend'       => __( 'XProfile admin field name legend', 'buddyboss' ),
		'xprofile_admin_field_action'            => __( 'XProfile admin field action', 'buddyboss' ),
		'xprofile_admin_group_action'            => __( 'XProfile admin group action', 'buddyboss' ),
	);

	foreach ( $deprecated_hooks as $hook => $description ) {
		if ( has_action( $hook ) ) {
			_deprecated_hook(
				$hook,
				'BuddyBoss [BBVERSION]',
				'',
				sprintf(
					/* translators: %s: hook name */
					__( 'The %s hook is no longer fired in the Settings 2.0 React admin interface. Extend the React UI via custom JavaScript instead.', 'buddyboss' ),
					$hook
				)
			);
		}
	}
}

add_action( 'admin_init', 'bb_deprecated_xprofile_admin_rendering_hooks' );
