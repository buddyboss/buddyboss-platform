<?php
/**
 * BuddyPress Common Admin Functions.
 *
 * @since   BuddyPress 2.3.0
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Menu **********************************************************************/

/**
 * Initializes the wp-admin area "BuddyPress" menus and sub menus.
 */
function bp_core_admin_menu_init() {
	add_action( bp_core_admin_hook(), 'bp_core_add_admin_menu', 9 );
}

/**
 * In BP 1.6, the top-level admin menu was removed. For backpat, this function
 * keeps the top-level menu if a plugin has registered a menu into the old
 * 'bp-general-settings' menu.
 *
 * The old "bp-general-settings" page was renamed "bp-components".
 *
 * @since BuddyPress 1.6.0
 * @global array $_registered_pages
 * @global array $submenu
 *
 * @global array $_parent_pages
 */
function bp_core_admin_backpat_menu() {
	global $_parent_pages, $_registered_pages, $submenu;

	// If there's no bp-general-settings menu (perhaps because the current
	// user is not an Administrator), there's nothing to do here.
	if ( ! isset( $submenu['bp-general-settings'] ) ) {
		return;
	}

	/**
	 * By default, only the core "Help" submenu is added under the top-level BuddyPress menu.
	 * This means that if no third-party plugins have registered their admin pages into the
	 * 'bp-general-settings' menu, it will only contain one item. Kill it.
	 */
	if ( 1 != count( $submenu['bp-general-settings'] ) ) {
		return;
	}

	// This removes the top-level menu.
	remove_submenu_page( 'bp-general-settings', 'bp-general-settings' );
	remove_menu_page( 'bp-general-settings' );

	// These stop people accessing the URL directly.
	unset( $_parent_pages['bp-general-settings'] );
	unset( $_registered_pages['toplevel_page_bp-general-settings'] );
}

add_action( bp_core_admin_hook(), 'bp_core_admin_backpat_menu', 999 );

/**
 * This tells WP to highlight the Settings > BuddyPress menu item,
 * regardless of which actual BuddyPress admin screen we are on.
 *
 * The conditional prevents the behaviour when the user is viewing the
 * backpat "Help" page, the Activity page, or any third-party plugins.
 *
 * @since BuddyPress 1.6.0
 * @global array  $submenu
 *
 * @global string $plugin_page
 */
function bp_core_modify_admin_menu_highlight() {
	global $plugin_page, $submenu_file;

	// This tweaks the Settings subnav menu to show only one BuddyPress menu item.
	// bp-components submenu was removed in Settings 2.0 — point at bb-settings so
	// the left-nav highlight lands on the Settings entry instead of nothing.
	if ( ! in_array( $plugin_page, array( 'bp-activity', 'bp-general-settings' ) ) ) {
		$submenu_file = 'bb-settings';
	}

	// Network Admin > Tools.
	if ( in_array( $plugin_page, array( 'bp-tools', 'available-tools' ) ) ) {
		$submenu_file = $plugin_page;
	}
}

/**
 * Generates markup for a fallback top-level BuddyPress menu page, if the site is running
 * a legacy plugin which hasn't been updated. If the site is up to date, this page
 * will never appear.
 *
 * @since BuddyPress 1.6.0
 *
 * @see   bp_core_admin_backpat_menu()
 *
 * @todo  Add convenience links into the markup once new positions are finalised.
 */
function bp_core_admin_backpat_page() {
	$url          = bp_core_do_network_admin() ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
	$settings_url = add_query_arg( 'page', 'bp-components', $url ); ?>

	<div class="wrap">
		<h2><?php esc_html_e( 'Why have all my BuddyPress menus disappeared?', 'buddyboss' ); ?></h2>

		<p><?php esc_html_e( 'Don\'t worry! We\'ve moved the BuddyPress options into more convenient and easier to find locations. You\'re seeing this page because you are running a legacy BuddyPress plugin which has not been updated.', 'buddyboss' ); ?></p>
		<p><?php printf( __( 'Components, Pages, Settings, and Forums, have been moved to <a href="%1$s">Settings &gt; BuddyPress</a>. Profile Fields has been moved to <a href="%2$s">Settings 2.0</a>.', 'buddyboss' ), esc_url( $settings_url ), esc_url( bb_get_feature_settings_url( 'members', 'profile_fields' ) ) ); ?></p>
	</div>

	<?php
}

/** Notices *******************************************************************/

/**
 * Print admin messages to admin_notices or network_admin_notices.
 *
 * BuddyPress combines all its messages into a single notice, to avoid a preponderance of yellow
 * boxes.
 *
 * @since BuddyPress 1.5.0
 */
function bp_core_print_admin_notices() {

	// Only the super admin should see messages.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// On multisite installs, don't show on a non-root blog, unless
	// 'do_network_admin' is overridden.
	if ( is_multisite() && bp_core_do_network_admin() && ! bp_is_root_blog() ) {
		return;
	}

	$notice_types = array();
	foreach ( buddypress()->admin->notices as $notice ) {
		$notice_types[] = $notice['type'];
	}
	$notice_types = array_unique( $notice_types );

	foreach ( $notice_types as $type ) {
		$notices = wp_list_filter( buddypress()->admin->notices, array( 'type' => $type ) );
		printf( '<div id="message" class="fade %s">', sanitize_html_class( $type ) );

		foreach ( $notices as $notice ) {
			printf( '<p>%s</p>', $notice['message'] );
		}

		printf( '</div>' );
	}
}

add_action( 'admin_notices', 'bp_core_print_admin_notices' );
add_action( 'network_admin_notices', 'bp_core_print_admin_notices' );

/**
 * Add an admin notice to the BP queue.
 *
 * Messages added with this function are displayed in BuddyPress's general purpose admin notices
 * box. It is recommended that you hook this function to admin_init, so that your messages are
 * loaded in time.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $notice The notice you are adding to the queue.
 * @param string $type   The notice type; optional. Usually either "updated" or "error".
 */
function bp_core_add_admin_notice( $notice = '', $type = 'updated' ) {

	// Do not add if the notice is empty.
	if ( empty( $notice ) ) {
		return;
	}

	// Double check the object before referencing it.
	if ( ! isset( buddypress()->admin->notices ) ) {
		buddypress()->admin->notices = array();
	}

	// Add the notice.
	buddypress()->admin->notices[] = array(
		'message' => $notice,
		'type'    => $type,
	);
}

/**
 * Verify that some BP prerequisites are set up properly, and notify the admin if not.
 *
 * On every Dashboard page, this function checks the following:
 *   - that pretty permalinks are enabled.
 *   - that every BP component that needs a WP page for a directory has one.
 *   - that no WP page has multiple BP components associated with it.
 * The administrator will be shown a notice for each check that fails.
 *
 * @since BuddyPress 1.2.0
 * @global WP_Rewrite $wp_rewrite
 *
 * @global wpdb       $wpdb WordPress database abstraction object.
 */
function bp_core_activation_notice() {
	global $wp_rewrite, $wpdb;

	// Only the super admin gets warnings.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// Bail in user admin.
	if ( is_user_admin() ) {
		return;
	}

	// On multisite installs, don't load on a non-root blog, unless do_network_admin is overridden.
	if ( is_multisite() && bp_core_do_network_admin() && ! bp_is_root_blog() ) {
		return;
	}

	// Bail if in network admin, and BuddyPress is not network activated.
	if ( is_network_admin() && ! bp_is_network_activated() ) {
		return;
	}

	/**
	 * Check to make sure that the blog setup routine has run. This can't
	 * happen during the wizard because of the order which the components
	 * are loaded.
	 */
	if ( bp_is_active( 'blogs' ) ) {
		$bp    = buddypress();
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$bp->blogs->table_name}" );

		if ( empty( $count ) ) {
			bp_blogs_record_existing_blogs();
		}
	}

	// Add notice if no rewrite rules are enabled.
	if ( empty( $wp_rewrite->permalink_structure ) ) {
		bp_core_add_admin_notice( sprintf( __( '<strong>BuddyBoss Platform is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'buddyboss' ), admin_url( 'options-permalink.php' ) ), 'error' );
	}

	// Get BuddyPress instance.
	$bp = buddypress();

	/**
	 * Check for orphaned BP components (BP component is enabled, no WP page exists).
	 */
	$orphaned_components = array();
	$wp_page_components  = array();

	// Only components with 'has_directory' require a WP page to function.
	foreach ( array_keys( $bp->loaded_components ) as $component_id ) {
		if ( 'photos' === $component_id ) {
			$component_id = 'media';
		}
		if ( 'documents' === $component_id ) {
			if ( bp_is_active( 'media' ) && ( bp_is_group_document_support_enabled() || bp_is_profile_document_support_enabled() ) ) {
				$component_id = 'document';
			}
		}
		if ( 'videos' === $component_id ) {
			if ( bp_is_active( 'media' ) && ( bp_is_group_video_support_enabled() || bp_is_profile_video_support_enabled() ) ) {
				$component_id = 'video';
			}
		}
		if ( ! empty( $bp->{$component_id}->has_directory ) ) {
			$wp_page_components[] = array(
				'id'   => $component_id,
				'name' => isset( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $bp->{$component_id}->id ),
			);
		}
	}

	// Activate and Register are special cases. They are not components but they need WP pages.
	// If user registration is disabled, we can skip this step.
	$allow_custom_registration = bp_allow_custom_registration();
	if ( bp_get_signup_allowed() && ! $allow_custom_registration ) {
		$wp_page_components[] = array(
			'id'   => 'activate',
			'name' => __( 'Activate', 'buddyboss' ),
		);

		$wp_page_components[] = array(
			'id'   => 'register',
			'name' => __( 'Register', 'buddyboss' ),
		);
	}

	// On the first admin screen after a new installation, this isn't set, so grab it to suppress
	// a misleading error message.
	if ( empty( $bp->pages->members ) ) {
		$bp->pages = bp_core_get_directory_pages();
	}

	foreach ( $wp_page_components as $component ) {
		if ( ! isset( $bp->pages->{$component['id']} ) ) {
			$orphaned_components[] = $component['name'];
		}
	}

	if ( function_exists( 'bp_nouveau_get_appearance_settings' ) ) {
		if ( bp_nouveau_get_appearance_settings( 'user_front_page' ) ) {
			if ( ! isset( $bp->pages->profile_dashboard ) ) {
				$orphaned_components[] = 'Profile Dashboard';
			}
		}
	}

	// If forum enabled and forum page is not set then add to forums to $orphaned_components components to show the notice.
	if ( bp_is_active( 'forums' ) ) {

		$id = (int) bp_get_option( '_bbp_root_slug_custom_slug' );

		// Check the status of current set value.
		$status = get_post_status( $id );

		// Set the page id if page exists and in publish otherwise set blank.
		$id = ( '' !== $status && 'publish' === $status ) ? $id : '';

		if ( empty( $id ) ) {
			$orphaned_components[] = 'Forums';
		}
	}

	if ( ! empty( $orphaned_components ) ) {
		// Settings 2.0: Pages live under Appearance → Pages. The legacy
		// `?page=bp-pages` slug now redirects via
		// bb_redirect_bp_settings_before_permission_check() but we link
		// directly to the canonical URL to avoid the redirect hop.
		$admin_url = function_exists( 'bb_get_feature_settings_url' )
			? bb_get_feature_settings_url( 'appearance', 'pages' )
			: bp_get_admin_url( add_query_arg( array( 'page' => 'bp-pages' ), 'admin.php' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection to suppress the "Repair" link when the user is already on the Pages panel.
		$on_pages_panel = isset( $_GET['page'], $_GET['tab'], $_GET['panel'] )
			&& 'bb-settings' === $_GET['page']
			&& 'appearance' === $_GET['tab']
			&& 'pages' === $_GET['panel'];

		if ( $on_pages_panel ) {
			$notice = sprintf(
				'%1$s',
				sprintf(
					__(
						'The following active BuddyBoss Components do not have associated WordPress Pages: %s.',
						'buddyboss'
					),
					'<strong>' . implode(
						'</strong>, <strong>',
						array_map( 'esc_html', $orphaned_components )
					) . '</strong>'
				)
			);
		} else {
			$notice = sprintf(
				'%1$s <a href="%2$s">%3$s</a>',
				sprintf(
					__(
						'The following active BuddyBoss Components do not have associated WordPress Pages: %s.',
						'buddyboss'
					),
					'<strong>' . implode(
						'</strong>, <strong>',
						array_map( 'esc_html', $orphaned_components )
					) . '</strong>'
				),
				esc_url( $admin_url ),
				__( 'Repair', 'buddyboss' )
			);
		}

		bp_core_add_admin_notice( $notice );
	}

	// BP components cannot share a single WP page. Check for duplicate assignments, and post a message if found.
	$dupe_names = array();
	$page_ids   = bp_core_get_directory_page_ids();
	$dupes      = array_diff_assoc( $page_ids, array_unique( $page_ids ) );
	$bp_pages   = bp_core_get_directory_pages();

	if ( ! empty( $dupes ) ) {
		foreach ( array_keys( $dupes ) as $dupe_component ) {
			$dupe_names[] = $bp_pages->{$dupe_component}->title;
		}

		// Make sure that there are no duplicate duplicates :).
		$dupe_names = array_unique( $dupe_names );
	}

	// If there are duplicates, post a message about them.
	if ( ! empty( $dupe_names ) ) {
		// Settings 2.0: Pages live under Appearance → Pages. Same canonical
		// URL as the orphaned-components branch above; see comment there.
		$admin_url = function_exists( 'bb_get_feature_settings_url' )
			? bb_get_feature_settings_url( 'appearance', 'pages' )
			: bp_get_admin_url( add_query_arg( array( 'page' => 'bp-pages' ), 'admin.php' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL inspection to suppress the "Repair" link when the user is already on the Pages panel.
		$on_pages_panel = isset( $_GET['page'], $_GET['tab'], $_GET['panel'] )
			&& 'bb-settings' === $_GET['page']
			&& 'appearance' === $_GET['tab']
			&& 'pages' === $_GET['panel'];

		if ( $on_pages_panel ) {
			$notice = sprintf(
				'%1$s',
				sprintf(
					__(
						'Each BuddyBoss Component needs its own WordPress page. The following WordPress Pages have more than one component associated with them: %s.',
						'buddyboss'
					),
					'<strong>' . implode( '</strong>, <strong>', array_map( 'esc_html', $dupe_names ) ) . '</strong>'
				)
			);
		} else {
			$notice = sprintf(
				'%1$s <a href="%2$s">%3$s</a>',
				sprintf(
					__(
						'Each BuddyBoss Component needs its own WordPress page. The following WordPress Pages have more than one component associated with them: %s.',
						'buddyboss'
					),
					'<strong>' . implode( '</strong>, <strong>', array_map( 'esc_html', $dupe_names ) ) . '</strong>'
				),
				esc_url( $admin_url ),
				__( 'Repair', 'buddyboss' )
			);
		}

		bp_core_add_admin_notice( $notice );
	}

	do_action( 'bp_core_activation_notice' );
}

/**
 * Redirect user to BuddyPress's What's New page on activation.
 *
 * @since    BuddyPress 1.7.0
 *
 * @internal Used internally to redirect BuddyPress to the about page on activation.
 */
function bp_do_activation_redirect() {

	// Bail if no activation redirect.
	if ( ! get_transient( '_bp_activation_redirect' ) ) {
		return;
	}

	// Delete the redirect transient.
	delete_transient( '_bp_activation_redirect' );

	// Bail if activating from network, or bulk.
	if ( isset( $_GET['activate-multi'] ) ) {
		return;
	}

	$query_args = array();
	if ( get_transient( '_bp_is_new_install' ) ) {
		$query_args['is_new_install'] = '1';
		delete_transient( '_bp_is_new_install' );
	}

	// In Appearance > Menus, make "BuddyBoss" available by default.
	$get_existing_option = get_user_option( 'metaboxhidden_nav-menus', bp_loggedin_user_id() );
	if ( '' === $get_existing_option || false === $get_existing_option ) {
		$hidden_metaboxes = array();
		update_user_option( bp_loggedin_user_id(), 'metaboxhidden_nav-menus', $hidden_metaboxes ); // update the user metaboxes.
	} else {
		if ( ( $key = array_search( 'add-buddypress-nav-menu', $get_existing_option ) ) !== false ) {
			unset( $get_existing_option[ $key ] );
		}
		update_user_option( bp_loggedin_user_id(), 'metaboxhidden_nav-menus', $get_existing_option ); // update the user metaboxes.
	}

	/**
	 * Fires before the BuddyBoss activation redirect.
	 *
	 * @since BuddyBoss 2.10.0
	 */
	do_action( 'bb_do_activation_redirect', $query_args );
}

/**
 * Check if currently using legacy theme
 *
 * @since BuddyBoss 1.0.0
 */
function bp_check_for_legacy_theme() {
	$using_lagecy = false;

	if ( current_theme_supports( 'buddypress-use-legacy' ) ) {
		$using_lagecy = true;
	}

	if ( bp_get_theme_package_id() == 'legacy' ) {
		$using_lagecy = true;
	}

	if ( $using_lagecy ) {
		add_action( 'admin_notices', 'bp_print_legacy_theme_deprecated_notice' );
	}
}

/**
 * Print the notice warnning in admin pages
 *
 * @since BuddyBoss 1.0.0
 */
function bp_print_legacy_theme_deprecated_notice() {
	$message = sprintf(
		__( 'You are using an old theme and/or BuddyPress addon that relies on the older %1$s templates, and some things may not work properly. Consider switching to our %2$s and/or removing the BuddyPress addon that is using old methods.', 'buddyboss' ),
		'<a href="https://www.buddyboss.com/resources/docs/development/theme-development/theme-compatibility/" target="_blank" rel="noopener">BuddyPress Legacy</a>',
		'<a href="https://www.buddyboss.com/pricing/" target="_blank" rel="noopener">BuddyBoss Theme</a>'
	);

	printf(
		'<div class="notice notice-error">
	        <p>%s</p>
	    </div>',
		$message
	);
}

/** UI/Styling ****************************************************************/

/**
 * Output the settings tabs in the admin area.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_settings_admin_tabs( $active_tab = '' ) {

	$tabs_html    = '';
	$idle_class   = '';
	$active_class = 'current';
	$active_tab   = isset( $_GET['tab'] ) ? $_GET['tab'] : 'bp-general';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_settings_admin_tabs', bp_core_get_settings_admin_active_tab( $active_tab ) );

	$count = count( array_values( $tabs ) );
	$i     = 1;

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {

		$is_current = (bool) ( $tab_data['slug'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		if ( $i === $count ) {
			$tabs_html .= '<li class="' . $tab_data['slug'] . ' "><a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a></li>';
		} else {
			$tabs_html .= '<li class="' . $tab_data['slug'] . ' "><a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a> |</li>';
		}

		$i = $i + 1;
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_settings_admin_tabs' );
}

/**
 * Get the data for the settings tabs in the admin area.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 *
 * @return string
 */
function bp_core_get_settings_admin_active_tab( $active_tab = '' ) {

	global $bp_admin_setting_tabs;

	if ( ! $bp_admin_setting_tabs ) {
		$bp_admin_setting_tabs = array();
	}

	uasort(
		$bp_admin_setting_tabs,
		function ( $a, $b ) {
			return $a->tab_order - $b->tab_order;
		}
	);

	$tabs = array_filter(
		$bp_admin_setting_tabs,
		function ( $tab ) {
			return $tab->is_tab_visible();
		}
	);

	$tabs = array_map(
		function ( $tab ) {
			return array(
				'href' => bp_core_admin_setting_url( $tab->tab_name ),
				'name' => $tab->tab_label,
				'slug' => $tab->tab_name,
			);
		},
		$tabs
	);

	// Remove the credit tab from the settings tab.
	unset( $tabs['bp-credit'] );

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_settings_admin_active_tab', $tabs );
}

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_tabs( $active_tab = '' ) {
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_tabs', bp_core_get_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		if ( $is_current && isset( $tab_data ) && isset( $tab_data['class'] ) ) {
			$tab_class = $tab_data['class'] . ' ' . $active_class;
		} elseif ( isset( $tab_data ) && isset( $tab_data['class'] ) ) {
			$tab_class = $tab_data['class'] . ' ' . $idle_class;
		} else {
			$tab_class = $idle_class;
		}
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_admin_tabs' );
}

/**
 * Get the data for the tabs in the admin area.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 *
 * @return string
 */
function bp_core_get_admin_tabs( $active_tab = '' ) {

	// Legacy top-level tab bar shown on bp-tools / bp-help / bp-components /
	// bp-pages pages. The "Integrations" tab was removed along with the
	// bp-integrations submenu in Settings 2.0. The "Upgrade" and "Credits"
	// tabs were also removed along with their submenus. The "Settings" tab
	// was removed because Settings 2.0 (bb-settings) is now the canonical
	// settings entry point — exposed via the BuddyBoss admin sidebar — and
	// linking to it from the legacy tab bar created a redundant entry. The
	// "Help" tab was removed because Help now lives in Settings 2.0
	// (bb-settings&tab=help); legacy `?page=bp-help` visits are redirected
	// there by BP_Admin::bb_redirect_legacy_help_page(). Key positions of
	// the remaining tabs are preserved (Components='0', Pages='1',
	// Settings='2', Tools='5', Help='6') so any third-party code that
	// references these filter-array keys continues to work — keys '2'
	// (Settings), '3' (Integrations), '4' (Upgrade), '6' (Help), and '7'
	// (Credits) are now intentionally absent rather than holding renumbered
	// entries.
	$tabs = array(
		// '0' was the Components tab — intentionally left absent.
		// '1' was Pages tab - Remove as migrated to setting2.0
		// '2' was the Settings tab — intentionally left absent (Settings 2.0 is canonical).
		// '3' was the Integrations tab — intentionally left absent.
		// '4' was the Upgrade tab — intentionally left absent.
		'5' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-tools' ), 'admin.php' ) ),
			'name'  => __( 'Tools', 'buddyboss' ),
			'class' => 'bp-tools',
		),
		// '6' was the Help tab — intentionally left absent (Help moved to Settings 2.0).
		// '7' was the Credits tab — intentionally left absent.
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_admin_tabs', $tabs );
}

/**
 * Get the slug of the current setting tab
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_get_admin_active_tab() {
	$default_tab = apply_filters( 'bp_core_admin_default_active_tab', 'bp-general' );

	return isset( $_GET['tab'] ) ? $_GET['tab'] : $default_tab;
}

/**
 * Get the object of the current setting tab
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_get_admin_active_tab_object() {
	global $bp_admin_setting_tabs;

	$active_tab = bp_core_get_admin_active_tab();

	if ( ! isset( $bp_admin_setting_tabs[ $active_tab ] ) ) {
		$default_tab = apply_filters( 'bp_core_admin_default_active_tab', 'bp-general' );

		if ( isset( $bp_admin_setting_tabs[ $default_tab ] ) ) {
			return $bp_admin_setting_tabs[ $default_tab ];
		}

		return null;
	}

	return $bp_admin_setting_tabs[ $active_tab ];
}

/**
 * Return the admin url with the tab selected
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_setting_url( $tab, $args = array() ) {
	$args = bp_parse_args(
		$args,
		array(
			'page' => 'bp-settings',
			'tab'  => $tab,
		)
	);

	return bp_get_admin_url( add_query_arg( $args, 'admin.php' ) );
}

/**
 * Output the integration tabs in the admin area.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_integration_tabs( $active_tab = '' ) {
	$tabs_html    = '';
	$idle_class   = '';
	$active_class = 'current';
	$active_tab   = $active_tab ?: bp_core_get_admin_integration_active_tab();

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_integration_tabs', bp_core_get_admin_integrations_tabs( $active_tab ) );

	$count = count( array_values( $tabs ) );
	$i     = 1;

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( isset( $tab_data['slug'] ) && $tab_data['slug'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;

		if ( $i === $count ) {
			$tabs_html .= '<li class="' . esc_attr( sanitize_key( $tab_data['name'] ) ) . '"><a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a></li>';
		} else {
			$tabs_html .= '<li class="' . esc_attr( sanitize_key( $tab_data['name'] ) ) . '"><a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a> |</li>';
		}

		$i = $i + 1;
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_admin_integration_tabs' );
}

/**
 * Get the data for the tabs in the admin area.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 *
 * @return string
 */
function bp_core_get_admin_integrations_tabs( $active_tab = '' ) {
	global $bp_admin_integration_tabs;

	if ( ! $bp_admin_integration_tabs ) {
		$bp_admin_integration_tabs = array();
	}

	uasort(
		$bp_admin_integration_tabs,
		function ( $a, $b ) {
			return $a->tab_order - $b->tab_order;
		}
	);

	$tabs = array_filter(
		$bp_admin_integration_tabs,
		function ( $tab ) {
			return $tab->is_tab_visible();
		}
	);

	$tabs = array_map(
		function ( $tab ) {
			return array(
				'href' => bp_core_admin_integrations_url( $tab->tab_name ),
				'name' => $tab->tab_label,
				'slug' => $tab->tab_name,
			);
		},
		$tabs
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_admin_tabs', $tabs );
}

/**
 * Set the default integration tab
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_get_admin_integration_active_tab() {

	if ( ! is_plugin_active( 'buddyboss-app/buddyboss-app.php' ) ) {

		$default_tab = apply_filters( 'bp_core_admin_default_active_tab', 'bp-buddyboss-app' );

		return isset( $_GET['tab'] ) ? $_GET['tab'] : $default_tab;

	} else {

		$default_tab = apply_filters( 'bp_core_admin_default_active_tab', 'bp-compatibility' );

		return isset( $_GET['tab'] ) ? $_GET['tab'] : $default_tab;

	}
}

/**
 * Get the object of the current integration tab
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_get_admin_integration_active_tab_object() {
	global $bp_admin_integration_tabs;

	$active_tab = bp_core_get_admin_integration_active_tab();
	if ( isset( $bp_admin_integration_tabs[ $active_tab ] ) ) {
		return $bp_admin_integration_tabs[ $active_tab ];
	}

	if ( ! empty( $bp_admin_integration_tabs ) ) {
		$tabs = array_keys( $bp_admin_integration_tabs );

		return $bp_admin_integration_tabs[ $tabs[0] ];
	}

	return false;
}

/**
 * Return the admin url with the tab selected
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_integrations_url( $tab, $args = array() ) {
	$args = bp_parse_args(
		$args,
		array(
			'page' => 'bp-integrations',
			'tab'  => $tab,
		)
	);

	return bp_get_admin_url( add_query_arg( $args, 'admin.php' ) );
}

/** Help **********************************************************************/

/**
 * Adds contextual help to BuddyPress admin pages.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $screen Current screen.
 *
 * @todo  Make this part of the BP_Component class and split into each component.
 */
function bp_core_add_contextual_help( $screen = '' ) {

	$screen = get_current_screen();

	switch ( $screen->id ) {

		// Component page.
		case 'settings_page_bp-components':
			// Help tabs.
			$screen->add_help_tab(
				array(
					'id'      => 'bp-comp-overview',
					'title'   => __( 'Overview', 'buddyboss' ),
					'content' => bp_core_add_contextual_help_content( 'bp-comp-overview' ),
				)
			);

			// Help panel - sidebar links.
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
			);

			break;

		// Pages page.
		case 'settings_page_bp-page-settings':
			// Help tabs.
			$screen->add_help_tab(
				array(
					'id'      => 'bp-page-overview',
					'title'   => __( 'Overview', 'buddyboss' ),
					'content' => bp_core_add_contextual_help_content( 'bp-page-overview' ),
				)
			);

			// Help panel - sidebar links.
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
			);

			break;

		// Settings page.
		case 'settings_page_bp-settings':
			// Help tabs.
			$screen->add_help_tab(
				array(
					'id'      => 'bp-settings-overview',
					'title'   => __( 'Overview', 'buddyboss' ),
					'content' => bp_core_add_contextual_help_content( 'bp-settings-overview' ),
				)
			);

			// Help panel - sidebar links.
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/resources/">Documentation</a>', 'buddyboss' ) . '</p>'
			);

			break;

	}
}

add_action( 'load-settings_page_bp-components', 'bp_core_add_contextual_help' );
add_action( 'load-settings_page_bp-page-settings', 'bp_core_add_contextual_help' );
add_action( 'load-settings_page_bp-settings', 'bp_core_add_contextual_help' );

/**
 * Renders contextual help content to contextual help tabs.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $tab Current help content tab.
 *
 * @return string
 */
function bp_core_add_contextual_help_content( $tab = '' ) {

	switch ( $tab ) {
		case 'bp-comp-overview':
			$retval = __( 'By default, several BuddyBoss components are enabled. You can selectively enable or disable any of the components by using the form below. Your BuddyBoss installation will continue to function. However, the features of the disabled components will no longer be accessible to anyone using the site.', 'buddyboss' );
			break;

		case 'bp-page-overview':
			$retval = __( 'BuddyBoss Components use WordPress Pages for their root directory/archive pages. You can change the page associations for each active component by using the form below.', 'buddyboss' );
			break;

		case 'bp-settings-overview':
			$retval = __( 'Extra configuration settings are provided and activated. You can selectively enable or disable any setting by using the form on this screen.', 'buddyboss' );
			break;

		case 'bp-profile-overview':
			$retval = __( 'Your users will distinguish themselves through their profile page. Create relevant profile fields that will show on each users profile.', 'buddyboss' ) . '<br /><br />' . __( 'Note: Any fields in the first group will appear on the signup page.', 'buddyboss' );
			break;

		default:
			$retval = false;
			break;
	}

	// Wrap text in a paragraph tag.
	if ( ! empty( $retval ) ) {
		$retval = '<p>' . $retval . '</p>';
	}

	return $retval;
}

/** Separator *****************************************************************/

/**
 * Add a separator to the WordPress admin menus.
 *
 * @since BuddyPress 1.7.0
 */
function bp_admin_separator() {

	// Bail if BuddyPress is not network activated and viewing network admin.
	if ( is_network_admin() && ! bp_is_network_activated() ) {
		return;
	}

	// Bail if BuddyPress is network activated and viewing site admin.
	if ( ! is_network_admin() && bp_is_network_activated() ) {
		return;
	}

	// Prevent duplicate separators when no core menu items exist.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}

	// Bail if there are no components with admin UI's. Hardcoded for now, until
	// there's a real API for determining this later.
	if ( ! bp_is_active( 'activity' ) && ! bp_is_active( 'groups' ) ) {
		return;
	}

	global $menu;

	$menu[] = array( '', 'read', 'separator-buddypress', '', 'wp-menu-separator buddypress' );
}

/**
 * Tell WordPress we have a custom menu order.
 *
 * @since BuddyPress 1.7.0
 *
 * @param bool $menu_order Menu order.
 *
 * @return bool Always true.
 */
function bp_admin_custom_menu_order( $menu_order = false ) {

	// Bail if user cannot see admin pages.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		return $menu_order;
	}

	return true;
}

/**
 * Move our custom separator above our custom post types.
 *
 * @since BuddyPress 1.7.0
 *
 * @param array $menu_order Menu Order.
 *
 * @return array Modified menu order.
 */
function bp_admin_menu_order( $menu_order = array() ) {

	// Bail if user cannot see admin pages.
	if ( empty( $menu_order ) || ! bp_current_user_can( 'bp_moderate' ) ) {
		return $menu_order;
	}

	// Initialize our custom order array.
	$bp_menu_order = array();

	// Menu values.
	$last_sep = is_network_admin() ? 'separator1' : 'separator2';

	/**
	 * Filters the custom admin menus.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $value Empty array.
	 */
	$custom_menus = (array) apply_filters( 'bp_admin_menu_order', array() );

	// Bail if no components have top level admin pages.
	if ( empty( $custom_menus ) ) {
		return $menu_order;
	}

	// Add our separator to beginning of array.
	array_unshift( $custom_menus, 'separator-buddypress' );

	// Loop through menu order and do some rearranging.
	foreach ( (array) $menu_order as $item ) {

		// Position BuddyPress menus above appearance.
		if ( $last_sep == $item ) {

			// Add our custom menus.
			foreach ( (array) $custom_menus as $custom_menu ) {
				if ( array_search( $custom_menu, $menu_order ) ) {
					$bp_menu_order[] = $custom_menu;
				}
			}

			// Add the appearance separator.
			$bp_menu_order[] = $last_sep;

			// Skip our menu items.
		} elseif ( ! in_array( $item, $custom_menus ) ) {
			$bp_menu_order[] = $item;
		}
	}

	// Return our custom order.
	return $bp_menu_order;
}

/** Utility  *****************************************************************/

/**
 * When using a WP_List_Table, get the currently selected bulk action.
 *
 * WP_List_Tables have bulk actions at the top and at the bottom of the tables,
 * and the inputs have different keys in the $_REQUEST array. This function
 * reconciles the two values and returns a single action being performed.
 *
 * @since BuddyPress 1.7.0
 *
 * @return string
 */
function bp_admin_list_table_current_bulk_action() {

	$action = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';

	// If the bottom is set, let it override the action.
	if ( ! empty( $_REQUEST['action2'] ) && $_REQUEST['action2'] != '-1' ) {
		$action = $_REQUEST['action2'];
	}

	return $action;
}

/** Menus *********************************************************************/

/**
 * Register meta box and associated JS for BuddyPress WP Nav Menu.
 *
 * @since BuddyPress 1.9.0
 */
function bp_admin_wp_nav_menu_meta_box() {
	if ( ! bp_is_root_blog() ) {
		return;
	}

	add_meta_box( 'add-buddypress-nav-menu', 'BuddyBoss', 'bp_admin_do_wp_nav_menu_meta_box', 'nav-menus', 'side', 'default' );

	add_action( 'admin_print_footer_scripts', 'bp_admin_wp_nav_menu_restrict_items' );
}

/**
 * Build and populate the BuddyPress accordion on Appearance > Menus.
 *
 * @since BuddyPress 1.9.0
 *
 * @global $nav_menu_selected_id
 */
function bp_admin_do_wp_nav_menu_meta_box() {
	global $nav_menu_selected_id;

	$db_fields = array(
		'parent' => 'post_parent',
		'id'     => 'ID',
	);
	$walker    = new BP_Walker_Nav_Menu_Checklist( $db_fields );
	$args      = array( 'walker' => $walker );

	$post_type_name = 'buddypress';

	$tabs = array();

	$tabs['loggedin']['label'] = __( 'Logged-In', 'buddyboss' );
	$tabs['loggedin']['pages'] = bp_nav_menu_get_loggedin_pages();

	$tabs['loggedout']['label'] = __( 'Logged-Out', 'buddyboss' );
	$tabs['loggedout']['pages'] = bp_nav_menu_get_loggedout_pages();

	?>

	<div id="buddypress-menu" class="posttypediv">
		<h4><?php esc_html_e( 'Logged-In', 'buddyboss' ); ?></h4>
		<p><?php esc_html_e( '<em>Logged-In</em> links are relative to the current user, and are not visible to visitors who are not logged in.', 'buddyboss' ); ?></p>

		<div id="tabs-panel-posttype-<?php echo esc_attr( $post_type_name ); ?>-loggedin" class="tabs-panel tabs-panel-active">
			<ul id="buddypress-menu-checklist-loggedin" class="categorychecklist form-no-clear">
				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $tabs['loggedin']['pages'] ), 0, (object) $args ); ?>
			</ul>
		</div>

		<h4><?php esc_html_e( 'Logged-Out', 'buddyboss' ); ?></h4>
		<p><?php esc_html_e( '<em>Logged-Out</em> links are not visible to users who are logged in.', 'buddyboss' ); ?></p>

		<div id="tabs-panel-posttype-<?php echo esc_attr( $post_type_name ); ?>-loggedout" class="tabs-panel tabs-panel-active">
			<ul id="buddypress-menu-checklist-loggedout" class="categorychecklist form-no-clear">
				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $tabs['loggedout']['pages'] ), 0, (object) $args ); ?>
			</ul>
		</div>

		<?php
		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);
		?>

		<p class="button-controls">
			<span class="list-controls">
				<a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							$post_type_name . '-tab' => 'all',
							'selectall'              => 1,
						),
						remove_query_arg( $removed_args )
					)
				);
				?>
				#buddypress-menu" class="select-all"><?php _e( 'Select All', 'buddyboss' ); ?></a>
			</span>
			<span class="add-to-menu">
				<input type="submit"
				<?php
				if ( function_exists( 'wp_nav_menu_disabled_check' ) ) :
					wp_nav_menu_disabled_check( $nav_menu_selected_id );
				endif;
				?>
				 class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'buddyboss' ); ?>" name="add-custom-menu-item" id="submit-buddypress-menu"/>
				<span class="spinner"></span>
			</span>
		</p>
	</div><!-- /#buddypress-menu -->

	<?php
}

/**
 * In admin emails list, for non-en_US locales, add notice explaining how to reinstall emails.
 *
 * If BuddyPress installs before its translations are in place, tell people how to reinstall
 * the emails so they have their contents in their site's language.
 *
 * @since BuddyPress 2.5.0
 */
function bp_admin_email_maybe_add_translation_notice() {
	if ( get_current_screen()->post_type !== bp_get_email_post_type() || get_locale() === 'en_US' ) {
		return;
	}

	// If user can't access BP Tools, there's no point showing the message.
	if ( ! current_user_can( buddypress()->admin->capability ) ) {
		return;
	}

	bp_core_add_admin_notice(
		sprintf(
			__( 'Are these emails not written in your site\'s language? Go to <a href="%s">BuddyBoss Tools and try the "reinstall emails"</a> tool.', 'buddyboss' ),
			esc_url(
				add_query_arg(
					array(
						'page' => 'bp-repair-community',
						'tab'  => 'bp-repair-community',
					),
					bp_get_admin_url( 'admin.php' )
				)
			)
		),
		'updated'
	);
}

add_action( 'admin_head-edit.php', 'bp_admin_email_maybe_add_translation_notice' );

/**
 * In emails editor, add notice linking to token documentation on Codex.
 *
 * @since BuddyPress 2.5.0
 * @todo  change link to BuddyBoss page
 */
function bp_admin_email_add_codex_notice() {
	if ( get_current_screen()->post_type !== bp_get_email_post_type() ) {
		return;
	}

	bp_core_add_admin_notice(
		sprintf(
			__( 'Phrases wrapped in braces <code>{{ }}</code> are email tokens. <a href="%s">Learn about email tokens</a>.', 'buddyboss' ),
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62844,
					),
					'admin.php'
				)
			)
		),
		'error'
	);
}

add_action( 'admin_head-post.php', 'bp_admin_email_add_codex_notice' );

/**
 * Display metabox for email taxonomy type.
 *
 * Shows the term description in a list, rather than the term name itself.
 *
 * @since BuddyPress 2.5.0
 *
 * @param WP_Post $post     Post object.
 * @param array   $box      {
 *                          Tags meta box arguments.
 *
 * @type string   $id       Meta box ID.
 * @type string   $title    Meta box title.
 * @type callable $callback Meta box display callback.
 * }
 */
function bp_email_tax_type_metabox( $post, $box ) {
	$r = array(
		'taxonomy' => bp_get_email_tax_type(),
	);

	$tax_name = esc_attr( $r['taxonomy'] );
	$taxonomy = get_taxonomy( $r['taxonomy'] );
	?>
	<div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
		<div id="<?php echo $tax_name; ?>-all" class="tabs-panel">
			<?php
			$name = ( $tax_name == 'category' ) ? 'post_category' : 'tax_input[' . $tax_name . ']';
			echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
			?>
			<ul id="<?php echo $tax_name; ?>checklist" data-wp-lists="list:<?php echo $tax_name; ?>"
				class="categorychecklist form-no-clear">
				<?php
				wp_terms_checklist(
					$post->ID,
					array(
						'taxonomy' => $tax_name,
						'walker'   => new BP_Walker_Category_Checklist(),
					)
				);
				?>
			</ul>
		</div>

		<p><?php esc_html_e( 'Choose when this email will be sent.', 'buddyboss' ); ?></p>
	</div>
	<?php
}

/**
 * Custom metaboxes used by our 'bp-email' post type.
 *
 * @since BuddyPress 2.5.0
 */
function bp_email_custom_metaboxes() {
	// Remove default 'Excerpt' metabox and replace with our own.
	remove_meta_box( 'postexcerpt', null, 'normal' );
	add_meta_box( 'postexcerpt', __( 'Plain text email content', 'buddyboss' ), 'bp_email_plaintext_metabox', null, 'normal', 'high' );
}

add_action( 'add_meta_boxes_' . bp_get_email_post_type(), 'bp_email_custom_metaboxes' );

/**
 * Customized version of the 'Excerpt' metabox for our 'bp-email' post type.
 *
 * We are using the 'Excerpt' metabox as our plain-text email content editor.
 *
 * @since BuddyPress 2.5.0
 *
 * @param WP_Post $post
 */
function bp_email_plaintext_metabox( $post ) {
	?>
	<!-- accesslint:ignore -->
	<label class="screen-reader-text" for="excerpt">
		<?php
		/* translators: accessibility text */
		_e( 'Plain text email content', 'buddyboss' );
		?>
	</label>
	<textarea rows="5" cols="40" name="excerpt"
					  id="excerpt"><?php echo $post->post_excerpt; // textarea_escaped ?></textarea>

	<p><?php _e( 'Most email clients support HTML email. However, some people prefer to receive plain text email. Enter a plain text alternative version of your email here.', 'buddyboss' ); ?></p>
	<!-- accesslint:endignore -->
	<?php
}

/**
 * Restrict various items from view if editing a BuddyPress menu.
 *
 * If a person is editing a BP menu item, that person should not be able to
 * see or edit the following fields:
 *
 * - CSS Classes - We use the 'bp-menu' CSS class to determine if the
 *   menu item belongs to BP, so we cannot allow manipulation of this field to
 *   occur.
 * - URL - This field is automatically generated by BP on output, so this
 *   field is useless and can cause confusion.
 *
 * Note: These restrictions are only enforced if JavaScript is enabled.
 *
 * @since BuddyPress 1.9.0
 */
function bp_admin_wp_nav_menu_restrict_items() {
	?>
	<script>
		jQuery( '#menu-to-edit' ).on( 'click', 'a.item-edit', function () {
			var settings = jQuery( this ).closest( '.menu-item-bar' ).next( '.menu-item-settings' );
			var css_class = settings.find( '.edit-menu-item-classes' );

			if ( css_class.val().indexOf( 'bp-menu' ) === 0 ) {
				css_class.attr( 'readonly', 'readonly' );
				settings.find( '.field-url' ).css( 'display', 'none' );
			}
		} );
	</script>
	<?php
}

/**
 * Add activate moderation when admin tries to spam the user.
 *
 * @since BuddyPress 2.0.0
 */
function add_active_moderation_popup() {
	global $pagenow;
	$bp = buddypress();

	if ( 'users.php' === $pagenow && 0 === strpos( get_current_screen()->id, 'users' ) ) {
		include trailingslashit( $bp->plugin_dir . 'bp-core/admin' ) . 'templates/moderation-activate-alert-popup.php';
	}

	if ( 'admin.php' === $pagenow && 0 === strpos( get_current_screen()->id, 'buddyboss_page_bp-components' ) ) {
		include trailingslashit( $bp->plugin_dir . 'bp-core/admin' ) . 'templates/moderation-deactivate-confirmation-popup.php';
	}
}

add_action( 'admin_footer', 'add_active_moderation_popup' );

/**
 * Add "Mark as Spam/Ham" button to user row actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array  $actions     User row action links.
 * @param object $user_object Current user information.
 *
 * @return array $actions User row action links.
 */
function bp_core_admin_user_row_actions( $actions, $user_object ) {

	// Setup the $user_id variable from the current user object.
	$user_id = 0;
	if ( ! empty( $user_object->ID ) ) {
		$user_id = absint( $user_object->ID );
	}

	// Bail early if user cannot perform this action, or is looking at themselves.
	if ( current_user_can( 'edit_user', $user_id ) && ( bp_loggedin_user_id() !== $user_id ) ) {

		// Admin URL could be single site or network.
		$url = bp_get_admin_url( 'users.php' );

		// If spammed, create unspam link.
		if ( ! bp_is_active( 'moderation' ) ) {
			// `bp_is_user_spammer()` lives in bp-members-functions.php; when
			// members is also deactivated the function is undefined. Gate the
			// call so the row action degrades gracefully instead of fatalling.
			if ( bp_is_active( 'members' ) && function_exists( 'bp_is_user_spammer' ) && bp_is_user_spammer( $user_id ) ) {
				$actions['ham'] = sprintf( '<a class="bp-show-moderation-alert" href="javascript:void(0);" data-action="not-spam">%1$s</a>', esc_html__( 'Not Spam', 'buddyboss' ) );
				// If not already spammed, create spam link.
			} else {
				$actions['spam'] = sprintf( '<a class="submitdelete bp-show-moderation-alert" href="javascript:void(0);" data-action="spam">%1$s</a>', esc_html__( 'Spam', 'buddyboss' ) );
			}
		} else {
			if ( bp_moderation_is_user_suspended( $user_id ) ) {
				$url                  = add_query_arg(
					array(
						'action' => 'unsuspend',
						'user'   => $user_id,
					),
					$url
				);
				$unsuspend_link       = wp_nonce_url( $url, 'bp-suspend-user' );
				$suspend_id           = bp_is_active( 'moderation' ) ? BP_Core_Suspend::get_suspend_id( $user_id, BP_Moderation_Members::$moderation_type ) : '';
				$meta_value           = ! empty( $suspend_id ) ? bb_suspend_get_meta( $suspend_id, 'suspend' ) : '';
				$actions['unsuspend'] = sprintf(
					'<a class="ham %1$s" href="%2$s" data-action="unsuspend" %3$s>%4$s</a>',
					! empty( $meta_value ) ? 'disabled' : 'bp-unsuspend-user',
					! empty( $meta_value ) ? '#' : esc_url( $unsuspend_link ),
					! empty( $meta_value ) ? 'data-bp-tooltip-pos="up" data-bp-tooltip="' . esc_attr__( 'The background process is currently in the queue. Please refresh the page after a short while', 'buddyboss' ) . '"' : '',
					esc_html__( 'Unsuspend', 'buddyboss' ),
				);

				// If not already spammed, create spam link.
			} else {
				$url                = add_query_arg(
					array(
						'action' => 'suspend',
						'user'   => $user_id,
					),
					$url
				);
				$suspend_link       = wp_nonce_url( $url, 'bp-suspend-user' );
				$suspend_id         = bp_is_active( 'moderation' ) ? BP_Core_Suspend::get_suspend_id( $user_id, BP_Moderation_Members::$moderation_type ) : '';
				$meta_value         = ! empty( $suspend_id ) ? bb_suspend_get_meta( $suspend_id, 'unsuspend' ) : '';
				$actions['suspend'] = sprintf(
					'<a class="submitdelete %1$s" href="%2$s" data-action="suspend" %3$s>%4$s</a>',
					! empty( $meta_value ) ? 'disabled' : 'bp-suspend-user',
					! empty( $meta_value ) ? '#' : esc_url( $suspend_link ),
					! empty( $meta_value ) ? 'data-bp-tooltip-pos="up" data-bp-tooltip="' . esc_attr__( 'The background process is currently in the queue. Please refresh the page after a short while', 'buddyboss' ) . '"' : '',
					esc_html__( 'Suspend', 'buddyboss' )
				);
			}

			unset( $suspend_link, $suspend_id, $meta_value );
		}
	}

	// Create a "View" link.
	$url             = bp_core_get_user_domain( $user_id );
	$actions['view'] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $url ), esc_html__( 'View', 'buddyboss' ) );

	// Return new actions.
	return $actions;
}

/**
 * Catch requests to mark individual users as spam/ham from users.php.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_user_manage_spammers() {

	// Print our inline scripts on non-Multisite.
	add_action( 'admin_footer', 'bp_core_admin_user_spammed_js' );

	$action  = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
	$updated = isset( $_REQUEST['updated'] ) ? $_REQUEST['updated'] : false;
	$mode    = isset( $_POST['mode'] ) ? $_POST['mode'] : false;

	// If this is a multisite, bulk request, stop now!
	if ( 'list' == $mode ) {
		return;
	}

	// Process a spam/ham request.
	if ( ! empty( $action ) && in_array( $action, array( 'suspend', 'unsuspend' ) ) ) {

		// Bail BEFORE consuming the nonce when moderation is deactivated.
		// Both branches below depend on moderation infrastructure:
		// - `BP_Suspend_Member::suspend_user()` writes to the suspend
		//   table and fires `bp_suspend_hide_*` actions whose listeners
		//   are only registered when the moderation component boots.
		// - `bp_moderation_is_user_suspended()` is loaded by the
		//   moderation component and is undefined when it is off.
		// Without this guard, a cached/bookmarked URL or stale redirect
		// can re-enter the handler indefinitely (each call still issues
		// a `wp_safe_redirect()` and carries `?updated=marked-*`, which
		// shows a misleading "Member unsuspended." notice on the next
		// page-load even though no state changed). Returning early lets
		// the original screen render normally instead of looping.
		if ( ! bp_is_active( 'moderation' ) ) {
			return;
		}

		check_admin_referer( 'bp-suspend-user' );

		$user_id = ! empty( $_REQUEST['user'] ) ? intval( $_REQUEST['user'] ) : false;

		if ( empty( $user_id ) ) {
			return;
		}

		$redirect = wp_get_referer();

		$status = ( $action == 'suspend' ) ? 'suspend' : 'unsuspend';

		if ( 'suspend' === $status ) {
			BP_Suspend_Member::suspend_user( $user_id );
		} elseif ( bp_moderation_is_user_suspended( $user_id ) ) {
			BP_Suspend_Member::unsuspend_user( $user_id );
		}

		$redirect = add_query_arg( array( 'updated' => 'marked-' . $status ), $redirect );

		wp_safe_redirect( $redirect );
	}

	// Display feedback.
	if ( ! empty( $updated ) && in_array( $updated, array( 'marked-suspend', 'marked-unsuspend' ) ) ) {

		if ( 'marked-suspend' === $updated ) {
			$notice = __( 'Member suspended.', 'buddyboss' );
		} else {
			$notice = __( 'Member unsuspended.', 'buddyboss' );
		}

		bp_core_add_admin_notice( $notice );
	}
}

/**
 * Inline script that adds the 'site-spammed' class to spammed users.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_user_spammed_js() {
	?>
	<script>
		jQuery( document ).ready( function ( $ ) {
			$( '.row-actions .ham' ).each( function () {
				$( this ).closest( 'tr' ).addClass( 'site-spammed' );
			} );
		} );
	</script>
	<?php
}

/**
 * Catch and process an admin notice dismissal.
 *
 * @since BuddyPress 2.7.0
 */
function bp_core_admin_notice_dismiss_callback() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['nonce'] ) || empty( $_POST['notice_id'] ) ) {
		wp_send_json_error();
	}

	$notice_id = wp_unslash( $_POST['notice_id'] );

	if ( ! wp_verify_nonce( $_POST['nonce'], 'bp-dismissible-notice-' . $notice_id ) ) {
		wp_send_json_error();
	}

	bp_update_option( "bp-dismissed-notice-$notice_id", 1 );

	wp_send_json_success();
}

add_action( 'wp_ajax_bp_dismiss_notice', 'bp_core_admin_notice_dismiss_callback' );

/**
 * Add a "buddypress" class to body element of wp-admin.
 *
 * @since BuddyPress 2.8.0
 *
 * @param string $classes CSS classes for the body tag in the admin, a comma separated string.
 *
 * @return string
 */
function bp_core_admin_body_classes( $classes ) {
	return $classes . ' buddypress';
}

add_filter( 'admin_body_class', 'bp_core_admin_body_classes' );



/**
 * Remove profile type from users, when the profile type is deleted.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 */
function bp_delete_member_type( $post_id ) {
	global $wpdb;

	// `bp_get_member_type_post_type()` and `bp_get_member_type_key()` are
	// loaded by the members component (bp-members-functions.php). When
	// members is deactivated via Settings 2.0, those functions are
	// undefined and the unguarded calls below would fatal on every post
	// delete (this hook fires for *every* post type, not just member
	// types).
	if ( ! bp_is_active( 'members' ) || ! function_exists( 'bp_get_member_type_post_type' ) ) {
		return;
	}

	$post = get_post( $post_id );

	// Bail when the post no longer exists (already deleted, invalid ID,
	// or a race between the before_delete_post hook and the underlying
	// post row). Without this guard, `$post->post_type` would fatal on
	// PHP 8+ with "Attempt to read property on null".
	if ( ! $post ) {
		return;
	}

	// Return if post is not 'bp-member-type' type.
	if ( bp_get_member_type_post_type() !== $post->post_type ) {
		return;
	}

	$member_type_name = bp_get_member_type_key( $post_id );
	$type_term        = get_term_by( 'name', $member_type_name, 'bp_member_type' ); // Get profile type term data from database by name field.

	// term exist.
	if ( $type_term ) {

		// Removes a profile type term from the database.
		wp_delete_term( $type_term->term_id, 'bp_member_type' );

		// Removes a profile type term relation with users from the database.
		$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $type_term->term_taxonomy_id ) );
	}

	// Clean up xprofile field member_type meta referencing this deleted type.
	if ( ! empty( $member_type_name ) && bp_is_active( 'xprofile' ) ) {
		$bp = buddypress();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time cleanup on delete.
		$wpdb->delete(
			$bp->profile->table_name_meta,
			array(
				'object_type' => 'field',
				'meta_key'    => 'member_type',
				'meta_value'  => $member_type_name,
			),
			array( '%s', '%s', '%s' )
		);
	}
}

// delete post.
add_action( 'before_delete_post', 'bp_delete_member_type' );

// Register submenu page for profile type import.
add_action( 'admin_menu', 'bp_register_member_type_import_submenu_page' );

/**
 * Register submenu page for profile type import.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_member_type_import_submenu_page() {

	add_submenu_page(
		'',
		__( 'Repair Community', 'buddyboss' ),
		__( 'Repair Community', 'buddyboss' ),
		'manage_options',
		'bp-repair-community',
		'bp_repair_community_submenu_page'
	);

	add_submenu_page(
		'',
		'Import Member Types',
		'Import Member Types',
		'manage_options',
		'bp-member-type-import',
		'bp_member_type_import_submenu_page'
	);
}

/**
 * Import profile types.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_member_type_import_submenu_page() {
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Tools', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_tools_settings_admin_tabs(); ?>
			</ul>
		</div>
	</div>
	<div class="wrap">
		<div class="bp-admin-card section-bp-member-type-import">
			<div class="boss-import-area">
				<form id="bp-member-type-import-form" method="post" action="">
					<div class="import-panel-content">
						<h2>
							<?php
							$meta_icon = bb_admin_icons( 'bp-member-type-import' );
							if ( ! empty( $meta_icon ) ) {
								?>
								<i class="<?php echo esc_attr( $meta_icon ); ?>"></i>
								<?php
							}
							esc_html_e( 'Import Profile Types', 'buddyboss' ); ?>
						</h2>
						<p>
							<?php
							printf(
								__( 'Import your existing <a href="%s">profile types</a> (or "member types" in BuddyPress). You may have created these types <strong>manually via code</strong> or by using a <strong>third party plugin</strong>. Click "Run Migration" below and all registered member types will be imported. Then you can remove the old code or plugin.', 'buddyboss' ),
								add_query_arg(
									array(
										'post_type' => bp_get_member_type_post_type(),
									),
									admin_url( 'edit.php' )
								)
							);
							?>
						</p><br/>
						<input type="submit" value="<?php esc_attr_e( 'Run Migration', 'buddyboss' ); ?>"
							   id="bp-member-type-import-submit" name="bp-member-type-import-submit"
							   class="button-primary">
					</div>
				</form>
			</div>
		</div>
	</div>
	<br/>

	<?php
	if ( isset( $_POST['bp-member-type-import-submit'] ) ) {

		if ( is_multisite() && bp_is_network_activated() ) {
			switch_to_blog( bp_get_root_blog_id() );
		}

		$registered_member_types = bp_get_member_types();
		$created_member_types    = bp_get_active_member_types();
		$active_member_types     = array();

		foreach ( $created_member_types as $created_member_type ) {
			$name = bp_get_member_type_key( $created_member_type );
			array_push( $active_member_types, $name );
		}

		$registered_member_types = array_diff( $registered_member_types, $active_member_types );

		if ( empty( $registered_member_types ) ) {
			?>
			<div class="wrap">
				<div class="error notice " id="message"><p><?php esc_html_e( 'Nothing to import', 'buddyboss' ); ?></p></div>
			</div>
			<?php
		}

		foreach ( $registered_member_types as $key => $import_types_data ) {
			$sing_name = ucfirst( $import_types_data );
			// Create post object.
			$my_post = array(
				'post_type'   => bp_get_member_type_post_type(),
				'post_title'  => $sing_name,
				'post_status' => 'publish',
				'post_author' => get_current_user_id(),
			);

			// Insert the post into the database.
			$post_id = wp_insert_post( $my_post );

			if ( $post_id ) {
				$key  = get_post_field( 'post_name', $post_id );
				$term = term_exists( sanitize_key( $key ), bp_get_member_type_tax_name() );
				if ( 0 !== $term && null !== $term ) {

					$digits = 3;
					$unique = rand( pow( 10, $digits - 1 ), pow( 10, $digits ) - 1 );
					$key    = $key . $unique;
				}
				update_post_meta( $post_id, '_bp_member_type_key', sanitize_key( $key ) );
				update_post_meta( $post_id, '_bp_member_type_label_name', $sing_name );
				update_post_meta( $post_id, '_bp_member_type_label_singular_name', $sing_name );

				?>
				<div class="updated notice " id="message"><p><?php esc_html_e( 'Successfully Imported', 'buddyboss' ); ?></p>
				</div>
				<?php
			}
		}

		if ( is_multisite() && bp_is_network_activated() ) {
			restore_current_blog();
		}
	}

}

/**
 * Display error message on extended profile page in admin.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_member_type_invalid_role_extended_profile_error_callback() {

	// If there are no errors, then we'll exit the function.
	if ( ! ( $errors = get_transient( 'bp_invalid_role_selection_extended_profile' ) ) ) {
		return;
	}

	// Otherwise, build the list of errors that exist in the settings errores.
	$message = '<div id="message" class="error">';
	foreach ( $errors as $error ) {
		$message .= '<p>' . $error['message'] . '</p>';
	}
	$message .= '</div><!-- #error --><style>div.updated{display: none;}</style>';
	// Write them out to the screen.
	echo wp_kses_post( $message );
	// Clear and the transient and unhook any other notices so we don't see duplicate messages.
	delete_transient( 'bp_invalid_role_selection_extended_profile' );
	remove_action( 'admin_notices', 'bp_member_type_invalid_role_extended_profile_error_callback' );

}

// Hook for display error message on extended profile page in admin.
add_action( 'admin_notices', 'bp_member_type_invalid_role_extended_profile_error_callback' );

/**
 * Catch and process an admin directory page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_create_background_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['page'] ) ) {
		wp_send_json_error();
	}

	$page_ids    = bp_core_get_directory_page_ids( 'all' );
	$valid_pages = array_merge( bp_core_admin_get_directory_pages(), bp_core_admin_get_static_pages() );

	if ( isset( $valid_pages[ $_POST['page'] ] ) ) {

		$default_title = bp_core_get_directory_page_default_titles();
		$title = ( isset( $default_title[ $_POST['page'] ] ) ) ? $default_title[ $_POST['page'] ] : $valid_pages[ $_POST['page'] ];

		$new_page = array(
			'post_title'     => $title,
			'post_status'    => 'publish',
			'post_author'    => bp_loggedin_user_id(),
			'post_type'      => 'page',
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		);

		$page_id                    = wp_insert_post( $new_page );
		$page_ids[ $_POST['page'] ] = (int) $page_id;

		// If forums page then store into the _bbp_root_slug_custom_slug option.
		if ( 'new_forums_page' === sanitize_text_field( $_POST['page'] ) ) {
			bp_update_option( '_bbp_root_slug_custom_slug', $page_id );
			// Else store into the directory pages.
		} else {
			bp_core_update_directory_page_ids( $page_ids );
		}

		// If forums page then change the BBPress root slug _bbp_root_slug and flush the redirect rule.
		if ( 'new_forums_page' === sanitize_text_field( $_POST['page'] ) ) {
			$slug = get_page_uri( $page_id );
			bp_update_option( '_bbp_root_slug', urldecode( $slug ) );
			flush_rewrite_rules( true );
		}
	}

	$response = array(
		'feedback' => __( 'Added successfully', 'buddyboss' ),
		'type'     => 'success',
		'url'      => add_query_arg(
			array(
				'page'  => 'bp-pages',
				'added' => 'true',
			),
			admin_url( 'admin.php' )
		),
	);

	wp_send_json_success( $response );
}

add_action( 'wp_ajax_bp_core_admin_create_background_page', 'bp_core_admin_create_background_page' );

/**
 * Add notice in Show Avatar section in Discussion page.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_discussion_page_show_notice_in_avatar_section() {
	global $pagenow;

	if ( 'options-discussion.php' === $pagenow && function_exists( 'bb_get_profile_avatar_type' ) && 'BuddyBoss' === bb_get_profile_avatar_type() ) {

		$avatar_notice = sprintf(
			__( 'Profile avatars are currently provided by the BuddyBoss Platform. To use the WordPress avatar system, change the <strong>Profile Avatars</strong> setting to "WordPress" in the <a href="%s">Profile</a> settings.', 'buddyboss' ),
			add_query_arg(
				array(
					'page' => 'bp-settings',
					'tab'  => 'bp-xprofile',
				),
				admin_url( 'admin.php' )
			)
		);

		$avatar_notice_html  = '<div class="bp-messages-feedback admin-notice">';
		$avatar_notice_html .= '<div class="bp-feedback warning">';
		$avatar_notice_html .= '<span class="bp-icon" aria-hidden="true"></span>';
		$avatar_notice_html .= '<p>' . $avatar_notice . '</p>';
		$avatar_notice_html .= '</div>';
		$avatar_notice_html .= '</div>';
		?>

		<script type="text/javascript">
			( function ( $ ) {
				jQuery( document ).ready( function() {
					var discussion_avatar_tbl = $( 'body.options-discussion-php #wpbody-content .wrap form table:eq(1)' );
					if ( discussion_avatar_tbl.find( 'tr:eq(1)' ).hasClass( 'avatar-settings' ) ) {
						discussion_avatar_tbl.prev().after( '<?php echo wp_kses_post( $avatar_notice_html ); ?>' );
					}
				} );
			} )( jQuery );
		</script>
		<?php
	}
}

// Functions bp_core_admin_groups_tabs() and bp_core_get_groups_admin_tabs()
// moved to bp-core/deprecated/buddyboss/3.0.0.php

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
// Legacy email admin tab functions (bp_core_admin_emails_tabs, bp_core_get_emails_admin_tabs) removed.
// Migrated to Settings 2.0. Deprecation stubs in src/bp-core/deprecated/buddyboss/3.0.0.php.

/**
 * Output the settings tabs in the admin area.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_tools_settings_admin_tabs( $active_tab = '' ) {

	$tabs_html    = '';
	$idle_class   = '';
	$active_class = 'current';
	$active_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'bp-tools';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_tools_settings_admin_tabs', bp_core_get_tools_settings_admin_tabs( $active_tab ) );

	$count = count( array_values( $tabs ) );
	$i     = 1;

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {

		$is_current = (bool) ( $tab_data['slug'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		if ( $i === $count ) {
			$tabs_html .= '<li><a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a></li>';
		} else {
			$tabs_html .= '<li><a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a> |</li>';
		}

		$i = $i + 1;
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_tools_settings_admin_tabs' );
}

/**
 * Get the data for the settings tabs in the admin area.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 *
 * @return string
 */
function bp_core_get_tools_settings_admin_tabs( $active_tab = '' ) {

	// Tabs for the BuddyBoss > Tools.
	$tabs = array(
		'0' => array(
			'href' => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-tools',
						'tab'  => 'bp-tools',
					),
					'admin.php'
				)
			),
			'name' => __( 'Default Data', 'buddyboss' ),
			'slug' => 'bp-tools',
		),
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_tools_settings_admin_tabs', $tabs );
}

function bp_core_get_tools_import_profile_settings_admin_tabs( $tabs ) {

	$tabs[] = array(
		'href' => bp_get_admin_url(
			add_query_arg(
				array(
					'page' => 'bp-member-type-import',
					'tab'  => 'bp-member-type-import',
				),
				'admin.php'
			)
		),
		'name' => __( 'Import Profile Types', 'buddyboss' ),
		'slug' => 'bp-member-type-import',
	);

	return $tabs;
}

add_filter( 'bp_core_get_tools_settings_admin_tabs', 'bp_core_get_tools_import_profile_settings_admin_tabs', 15, 1 );

function bp_core_get_tools_repair_community_settings_admin_tabs( $tabs ) {

	$tabs[] = array(
		'href' => bp_get_admin_url(
			add_query_arg(
				array(
					'page' => 'bp-repair-community',
					'tab'  => 'bp-repair-community',
				),
				'admin.php'
			)
		),
		'name' => __( 'Repair Community', 'buddyboss' ),
		'slug' => 'bp-repair-community',
	);

	return $tabs;
}

add_filter( 'bp_core_get_tools_settings_admin_tabs', 'bp_core_get_tools_repair_community_settings_admin_tabs', 1, 1 );

/**
 * Add the 'Site Notices' admin menu item.
 *
 * @since BuddyPress 3.0.0
 */
function bp_import_profile_types_admin_menu() {

	add_submenu_page(
		'buddyboss-platform',
		__( 'Repair Community', 'buddyboss' ),
		__( 'Repair Community', 'buddyboss' ),
		'manage_options',
		'bp-repair-community',
		'bp_repair_community_submenu_page'
	);

	add_submenu_page(
		'buddyboss-platform',
		__( 'Import Profile Types', 'buddyboss' ),
		__( 'Import Profile Types', 'buddyboss' ),
		'manage_options',
		'bp-member-type-import',
		'bp_member_type_import_submenu_page'
	);

	if ( current_user_can( 'bbp_tools_page' ) && current_user_can( 'bbp_tools_repair_page' ) ) {
		add_submenu_page(
			'buddyboss-platform',
			__( 'Repair Forums', 'buddyboss' ),
			__( 'Forum Repair', 'buddyboss' ),
			'manage_options',
			'bbp-repair',
			'bbp_admin_repair'
		);

		add_submenu_page(
			'buddyboss-platform',
			__( 'Import Forums', 'buddyboss' ),
			__( 'Forum Import', 'buddyboss' ),
			'manage_options',
			'bbp-converter',
			'bbp_converter_settings'
		);
	}

}

add_action( bp_core_admin_hook(), 'bp_import_profile_types_admin_menu' );

/**
 * Set the forum slug on edit page from backend.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post
 *
 * @param $post_id
 */
function bp_change_forum_slug_quickedit_save_page( $post_id, $post ) {

	// if called by autosave, then bail here.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// if this "post" post type?
	if ( 'page' !== $post->post_type ) {
		return;
	}

	// does this user have permissions?
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// update!
	$forum_page_id = (int) bp_get_option( '_bbp_root_slug_custom_slug' );

	if ( $forum_page_id > 0 && $forum_page_id === $post_id ) {
		$slug = get_page_uri( $post_id );
		if ( '' !== $slug ) {
			bp_update_option( '_bbp_root_slug', urldecode( $slug ) );
			bp_update_option( 'rewrite_rules', '' );
		}
	}
}

// Set the forum slug on edit page from backend.
add_action( 'save_post', 'bp_change_forum_slug_quickedit_save_page', 10, 2 );

/**
 * Adds a BuddyPress category to house BuddyPress blocks.
 *
 * @since BuddyBoss 1.3.5
 *
 * @param array               $categories          Array of block categories.
 * @param string|WP_Post|null $editor_name_or_post Post being loaded.
 */
function bp_block_category( $categories = array(), $editor_name_or_post = null ) {
	if ( $editor_name_or_post instanceof WP_Post ) {
		$post_types = array( 'post', 'page' );

		/*
		 * As blocks are always loaded even if the category is not available, there's no more interest
		 * in disabling the BuddyBoss category.
		 */
		apply_filters_deprecated( 'bp_block_category_post_types', array( $post_types ), '2.9.00' );
	}

	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'buddyboss',
				'title' => 'BuddyBoss',
				'icon'  => '',
			),
		)
	);
}

/**
 * Select the `block_categories` filter according to WP version.
 *
 * @since BuddyBoss 1.8.3
 */
function bb_block_init_category_filter() {
	if ( function_exists( 'get_default_block_categories' ) ) {
		add_filter( 'block_categories_all', 'bp_block_category', 1, 2 );
	} else {
		add_filter( 'block_categories', 'bp_block_category', 1, 2 );
	}
}

add_action( 'bp_init', 'bb_block_init_category_filter' );

function bp_document_ajax_check_file_mime_type() {
	// Verify nonce and capability.
	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss' ) ) );
	}

	$response = array();

	if ( isset( $_POST ) && isset( $_POST['action'] ) && 'bp_document_check_file_mime_type' === $_POST['action'] && ! empty( $_FILES ) ) {
		$files = $_FILES;
		foreach ( $files as $input => $info_arr ) {

			$finfo            = finfo_open( FILEINFO_MIME_TYPE );
			$real_mime        = finfo_file( $finfo, $info_arr['tmp_name'] );
			$info_arr['type'] = $real_mime;
			foreach ( $info_arr as $key => $value_arr ) {
				$response[ $key ] = $value_arr;
			}
		}
	}
	wp_send_json_success( $response );
}

add_action( 'wp_ajax_bp_document_check_file_mime_type', 'bp_document_ajax_check_file_mime_type' );

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_moderation_tabs', bp_core_get_moderation_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $tab_data['class'] . ' ' . $active_class : $tab_data['class'] . ' ' . $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo $tabs_html;

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	do_action( 'bp_admin_moderation_tabs' );
}

/**
 * Register tabs for the BuddyBoss > Moderation screens.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param string $active_tab
 *
 * @return array
 */
function bp_core_get_moderation_admin_tabs( $active_tab = '' ) {

	$tabs = array();

	if ( bp_is_moderation_member_blocking_enable() ) {
		$tabs[] = array(
			'href'  => bp_get_admin_url( 'admin.php?page=bb-settings&tab=moderation&panel=flagged_members' ),
			'name'  => esc_html__( 'Flagged Members', 'buddyboss' ),
			'class' => 'bp-blocked-members',
		);
	}

	$tabs[] = array(
		'href'  => bp_get_admin_url( 'admin.php?page=bb-settings&tab=moderation&panel=reported_content' ),
		'name'  => esc_html__( 'Reported Content', 'buddyboss' ),
		'class' => 'bp-reported-content',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_moderation_admin_tabs', $tabs );
}

/**
 * Get label with platform pro notice if the platform is not active or not validate.
 *
 * @since BuddyBoss 1.9.1
 * @since BuddyBoss 2.5.20 Added support for type.
 *
 * @param string $type Type to check.
 *
 * @return string
 */
function bb_get_pro_label_notice( $type = 'default' ) {
	static $retval = array();

	if ( isset( $retval[ $type ] ) ) {
		return $retval[ $type ];
	}

	$bb_pro_notice = '';

	if ( function_exists( 'bb_platform_pro' ) && version_compare( bb_platform_pro()->version, '1.1.9.1', '<=' ) ) {
		$bb_pro_notice = sprintf(
			'<br/><span class="bb-head-notice"> %1$s <strong>%2$s</strong> %3$s</span>',
			esc_html__( 'Update to', 'buddyboss' ),
			esc_html__( 'BuddyBoss Platform Pro 1.2.0', 'buddyboss' ),
			esc_html__( 'to unlock', 'buddyboss' )
		);
	} elseif (
		function_exists( 'bb_platform_pro' ) &&
		! empty( $type ) &&
		(
			(
				'reaction' === $type &&
				version_compare( bb_platform_pro()->version, '2.4.50', '<' )
			) ||
			(
				'schedule_posts' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_schedule_posts_version(), '<' )
			) ||
			(
				'polls' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_poll_version(), '<' )
			) ||
			(
				'sso' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_sso_version(), '<' )
			) ||
			(
				'group_activity_topics' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_group_activity_topics_version(), '<' )
			) ||
			(
				'post_feature_image' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_post_feature_image_version(), '<' )
			)
		)
	) {
		$bb_pro_notice = sprintf(
			'<br/><span class="bb-head-notice"> %1$s <strong>%2$s</strong> %3$s</span>',
			esc_html__( 'Update', 'buddyboss' ),
			esc_html__( 'BuddyBoss Platform Pro', 'buddyboss' ),
			esc_html__( 'to unlock', 'buddyboss' )
		);
	} elseif ( ! function_exists( 'bb_platform_pro' ) || ( function_exists( 'bb_pro_should_lock_features' ) ? bb_pro_should_lock_features() : ! bbp_pro_is_license_valid() ) ) {
		$bb_pro_notice = sprintf(
			'<br/><span class="bb-head-notice"> %1$s <a target="_blank" href="https://www.buddyboss.com/platform/">%2$s</a> %3$s</span>',
			esc_html__( 'Install', 'buddyboss' ),
			esc_html__( 'BuddyBoss Platform Pro', 'buddyboss' ),
			esc_html__( 'to unlock', 'buddyboss' )
		);
	}

	$retval[ $type ] = $bb_pro_notice;

	return $bb_pro_notice;
}

/**
 * Get class for buddyboss pro settings fields.
 *
 * @since BuddyBoss 1.9.1
 * @since BuddyBoss 2.5.20 Added support for type.
 *
 * @param string $type Type to check.
 *
 * @return string
 */
function bb_get_pro_fields_class( $type = 'default' ) {
	static $retval = array();

	if ( isset( $retval[ $type ] ) ) {
		return $retval[ $type ];
	}

	$pro_class = 'bb-pro-inactive';
	if ( function_exists( 'bb_pro_should_lock_features' ) && ! bb_pro_should_lock_features() ) {
		$pro_class = 'bb-pro-active';
	} elseif ( ! function_exists( 'bb_pro_should_lock_features' ) && function_exists( 'bbp_pro_is_license_valid' ) && bbp_pro_is_license_valid() ) {
		$pro_class = 'bb-pro-active';
	}

	if ( function_exists( 'bb_platform_pro' ) && version_compare( bb_platform_pro()->version, '1.1.9.1', '<=' ) ) {
		$pro_class = 'bb-pro-inactive';
	}

	if (
		function_exists( 'bb_platform_pro' ) &&
		! empty( $type ) &&
		(
			(
				'reaction' === $type &&
				version_compare( bb_platform_pro()->version, '2.4.50', '<' )
			) ||
			(
				'schedule_posts' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_schedule_posts_version(), '<' )
			) ||
			(
				'polls' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_poll_version(), '<' )
			) ||
			(
				'sso' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_sso_version(), '<' )
			) ||
			(
				'group_activity_topics' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_group_activity_topics_version(), '<' )
			) ||
			(
				'post_feature_image' === $type &&
				version_compare( bb_platform_pro()->version, bb_pro_post_feature_image_version(), '<' )
			)
		)
	) {
		$pro_class = 'bb-pro-inactive';
	}

	$retval[ $type ] = $pro_class;

	return $pro_class;
}


add_action( 'admin_head', 'bb_disable_multiple_select_situation', 99999 );

/**
 * Disable the multi select in situation.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return void
 */
function bb_disable_multiple_select_situation() {

	global $typenow;

	if ( function_exists( 'bp_get_email_post_type' ) && bp_get_email_post_type() === $typenow ) {
		?>

		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				jQuery( document ).on( 'change', '#taxonomy-<?php echo esc_js( bp_get_email_post_type() ); ?>-type input[type="checkbox"]', function () {
					var group = 'input[type="checkbox"][name="' + jQuery( this ).attr( 'name' ) + '"]';
					jQuery( group ).not( this ).prop( 'checked', false );
				} );
			} );
		</script>

		<?php
	}
}


/**
 * Function to check any CPT enabled or disabled. If enabled then its set blog component active.
 *
 * @since BuddyBoss 2.5.00
 *
 * @return void
 */
function bb_cpt_feed_enabled_disabled() {
	bb_sync_blogs_component_state(
		function ( $cpt ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in the settings save handler.
			return isset( $_POST[ "bp-feed-custom-post-type-$cpt" ] );
		}
	);
}

/**
 * Sync blogs component activation based on CPT feed status.
 *
 * Checks all feed post types via the provided callback, then activates or
 * deactivates the blogs component accordingly. Installs the blog tracking
 * table when activating, and updates active components option when state changes.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param callable $is_feed_enabled_cb Callback that receives a post type slug and
 *                                     returns bool whether its feed is enabled.
 */
function bb_sync_blogs_component_state( $is_feed_enabled_cb ) {
	$bp                = buddypress();
	$active_components = $bp->active_components;

	// Flag for activate the blogs component only if any CPT OR blog posts have enabled the activity feed.
	$is_blog_component_active = false;

	// Temporarily remove LMS filters to get all feed post types.
	if ( function_exists( 'bb_feed_not_allowed_tutorlms_post_types' ) ) {
		remove_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_tutorlms_post_types' );
	}

	if ( function_exists( 'bb_feed_not_allowed_meprlms_post_types' ) ) {
		remove_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_meprlms_post_types' );
	}

	$post_types = bb_feed_post_types();

	if ( function_exists( 'bb_feed_not_allowed_tutorlms_post_types' ) ) {
		add_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_tutorlms_post_types' );
	}

	if ( function_exists( 'bb_feed_not_allowed_meprlms_post_types' ) ) {
		add_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_meprlms_post_types' );
	}

	foreach ( $post_types as $cpt ) {
		$enable_blog_feed = apply_filters( 'bb_enable_blog_feed', (bool) call_user_func( $is_feed_enabled_cb, $cpt ), $cpt );

		if ( $enable_blog_feed ) {
			$is_blog_component_active = true;
			break;
		}
	}

	// Add or remove blogs component from active components list.
	$was_blogs_active = isset( $active_components['blogs'] );

	if ( $is_blog_component_active ) {
		$active_components['blogs'] = '1';
	} else {
		unset( $active_components['blogs'] );
	}

	$is_blogs_active = isset( $active_components['blogs'] );

	// Only update if the blogs component state actually changed.
	if ( $was_blogs_active !== $is_blogs_active ) {
		$bp->active_components = $active_components;

		// Install only the blog tracking table — not the entire BP schema.
		if ( $is_blogs_active ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			require_once $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';
			bp_core_install_blog_tracking();
		}

		bp_core_add_page_mappings( $bp->active_components, 'keep', false );
		bp_update_option( 'bp-active-components', $bp->active_components );
	}
}

/**
 * Verify an admin AJAX request (capability + nonce).
 *
 * Capability is checked first because it is cheaper and avoids
 * consuming a nonce check for unauthorized users.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $nonce_action The nonce action to verify against.
 *
 * @return void Sends JSON error and dies on failure.
 */
function bb_admin_verify_ajax_request( $nonce_action ) {
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Permission denied.', 'buddyboss' ) ),
			403
		);
	}

	if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Security check failed.', 'buddyboss' ) ),
			403
		);
	}
}

/**
 * Calculates the components that should be active after save, based on submitted settings.
 *
 * The way that active components must be set after saving your settings must
 * be calculated differently depending on which of the Components subtabs you
 * are coming from:
 * - When coming from All or Active, the submitted checkboxes accurately
 *   reflect the desired active components, so we simply pass them through
 * - When coming from Inactive, components can only be activated - already
 *   active components will not be passed in the $_POST global. Thus, we must
 *   parse the newly activated components with the already active components
 *   saved in the $bp global
 *
 * @since BuddyPress 1.7.0
 * @since BuddyBoss 3.0.0 Moved from bp-core-admin-components.php during Settings 2.0 cleanup.
 *
 * @param array  $submitted Array of component settings from the POST global.
 *                          Caller should stripslashes_deep() before passing.
 * @param string $action    Optional. Submission context: 'all', 'active', or 'inactive'. Default 'all'.
 * @return array The calculated list of component settings.
 */
function bp_core_admin_get_active_components_from_submitted_settings( $submitted, $action = 'all' ) {
	$current_action = $action;

	if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'active', 'inactive' ), true ) ) {
		$current_action = $_GET['action'];
	}

	$current_components = buddypress()->active_components;

	switch ( $current_action ) {
		case 'inactive':
			$components = array_merge( $submitted, $current_components );
			break;

		case 'all':
		case 'active':
		default:
			$components = $submitted;
			break;
	}

	return $components;
}

/**
 * Return a list of component information.
 *
 * Used to do processing on settings data submitted from the legacy components
 * screen. The screen itself was removed in Settings 2.0, but this helper and
 * its `bp_core_admin_get_components` filter remain part of the public API.
 *
 * @since BuddyPress 1.7.0
 * @since BuddyBoss 3.0.0 Moved from bp-core-admin-components.php during Settings 2.0 cleanup.
 *
 * @param string $type Optional. Component type to fetch: 'all', 'optional', or 'required'. Default 'all'.
 * @return array Requested components' data.
 */
function bp_core_admin_get_components( $type = 'all' ) {
	$components = bp_core_get_components( $type );

	/**
	 * Filters the list of component information.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array  $components Array of component information.
	 * @param string $type       Type of component list requested.
	 *                           Possible values include 'all', 'optional',
	 *                           'required'.
	 */
	return apply_filters( 'bp_core_admin_get_components', $components, $type );
}
