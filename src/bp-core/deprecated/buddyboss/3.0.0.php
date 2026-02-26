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
