<?php
/**
 * BuddyPress Common Admin Functions.
 *
 * @package BuddyBoss
 * @subpackage CoreAdministration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Menu **********************************************************************/

/**
 * Initializes the wp-admin area "BuddyPress" menus and sub menus.
 *
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
 * @global array $_parent_pages
 * @global array $_registered_pages
 * @global array $submenu
 *
 * @since BuddyPress 1.6.0
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
 * @global string $plugin_page
 * @global array $submenu
 *
 * @since BuddyPress 1.6.0
 */
function bp_core_modify_admin_menu_highlight() {
	global $plugin_page, $submenu_file;

	// This tweaks the Settings subnav menu to show only one BuddyPress menu item.
	if ( ! in_array( $plugin_page, array( 'bp-activity', 'bp-general-settings', ) ) ) {
		$submenu_file = 'bp-components';
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
 * @see bp_core_admin_backpat_menu()
 *
 * @since BuddyPress 1.6.0
 *
 * @todo Add convenience links into the markup once new positions are finalised.
 */
function bp_core_admin_backpat_page() {
	$url          = bp_core_do_network_admin() ? network_admin_url( 'settings.php' ) : admin_url( 'options-general.php' );
	$settings_url = add_query_arg( 'page', 'bp-components', $url ); ?>

	<div class="wrap">
		<h2><?php _e( 'Why have all my BuddyPress menus disappeared?', 'buddyboss' ); ?></h2>

		<p><?php _e( "Don't worry! We've moved the BuddyPress options into more convenient and easier to find locations. You're seeing this page because you are running a legacy BuddyPress plugin which has not been updated.", 'buddyboss' ); ?></p>
		<p><?php printf( __( 'Components, Pages, Settings, and Forums, have been moved to <a href="%s">Settings &gt; BuddyPress</a>. Profile Fields has been moved into the <a href="%s">Users</a> menu.', 'buddyboss' ), esc_url( $settings_url ), bp_get_admin_url( 'users.php?page=bp-profile-setup' ) ); ?></p>
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
 *
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
add_action( 'admin_notices',         'bp_core_print_admin_notices' );
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
 * @global WPDB $wpdb WordPress DB object
 * @global WP_Rewrite $wp_rewrite
 *
 * @since BuddyPress 1.2.0
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
		bp_core_add_admin_notice( sprintf( __( '<strong>BuddyPress is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'buddyboss' ), admin_url( 'options-permalink.php' ) ), 'error' );
	}

	// Get BuddyPress instance.
	$bp = buddypress();

	/**
	 * Check for orphaned BP components (BP component is enabled, no WP page exists).
	 */
	$orphaned_components = array();
	$wp_page_components  = array();

	// Only components with 'has_directory' require a WP page to function.
	foreach( array_keys( $bp->loaded_components ) as $component_id ) {
		if ( !empty( $bp->{$component_id}->has_directory ) ) {
			$wp_page_components[] = array(
				'id'   => $component_id,
				'name' => isset( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $bp->{$component_id}->id )
			);
		}
	}

	// Activate and Register are special cases. They are not components but they need WP pages.
	// If user registration is disabled, we can skip this step.
	if ( bp_get_signup_allowed() ) {
		$wp_page_components[] = array(
			'id'   => 'activate',
			'name' => __( 'Activate', 'buddyboss' )
		);

		$wp_page_components[] = array(
			'id'   => 'register',
			'name' => __( 'Register', 'buddyboss' )
		);
	}

	// On the first admin screen after a new installation, this isn't set, so grab it to suppress
	// a misleading error message.
	if ( empty( $bp->pages->members ) ) {
		$bp->pages = bp_core_get_directory_pages();
	}

	foreach( $wp_page_components as $component ) {
		if ( !isset( $bp->pages->{$component['id']} ) ) {
			$orphaned_components[] = $component['name'];
		}
	}

	if ( !empty( $orphaned_components ) ) {
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings', 'tab' => 'bp-registration' ), 'admin.php' ) );
		$notice    = sprintf(
			'%1$s <a href="%2$s">%3$s</a>',
			sprintf(
				__( 'The following active BuddyPress Components do not have associated WordPress Pages: %s.', 'buddyboss' ),
				'<strong>' . implode( '</strong>, <strong>', array_map( 'esc_html', $orphaned_components ) ) . '</strong>'
			),
			esc_url( $admin_url ),
			__( 'Repair', 'buddyboss' )
		);

		bp_core_add_admin_notice( $notice );
	}

	// BP components cannot share a single WP page. Check for duplicate assignments, and post a message if found.
	$dupe_names = array();
	$page_ids   = bp_core_get_directory_page_ids();
	$dupes      = array_diff_assoc( $page_ids, array_unique( $page_ids ) );

	if ( !empty( $dupes ) ) {
		foreach( array_keys( $dupes ) as $dupe_component ) {
			$dupe_names[] = $bp->pages->{$dupe_component}->title;
		}

		// Make sure that there are no duplicate duplicates :).
		$dupe_names = array_unique( $dupe_names );
	}

	// If there are duplicates, post a message about them.
	if ( !empty( $dupe_names ) ) {
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings' ), 'admin.php' ) );
		$notice    = sprintf(
			'%1$s <a href="%2$s">%3$s</a>',
			sprintf(
				__( 'Each BuddyBoss Component needs its own WordPress page. The following WordPress Pages have more than one component associated with them: %s.', 'buddyboss' ),
				'<strong>' . implode( '</strong>, <strong>', array_map( 'esc_html', $dupe_names ) ) . '</strong>'
			),
			esc_url( $admin_url ),
			__( 'Repair', 'buddyboss' )
		);

		bp_core_add_admin_notice( $notice );
	}
}

/**
 * Redirect user to BuddyPress's What's New page on activation.
 *
 * @since BuddyPress 1.7.0
 *
 * @internal Used internally to redirect BuddyPress to the about page on activation.
 *
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

	// Redirect to dashboard and trigger the Hello screen.
	wp_safe_redirect( add_query_arg( $query_args, bp_get_admin_url( '?hello=buddypress' ) ) );
}

/**
 * Check if currently using legacy theme
 *
 * @since Buddyboss 3.1.1
 */
function bp_check_for_legacy_theme() {
	$using_lagecy = false;

	if (current_theme_supports('buddypress-use-legacy')) {
		$using_lagecy = true;
	}

	if (bp_get_theme_package_id() == 'legacy') {
		$using_lagecy = true;
	}

	if ($using_lagecy) {
		add_action('admin_notices', 'bp_print_legacy_theme_deprecated_notice');
	}
}

/**
 * Print the notice warnning in admin pages
 *
 * @since buddyboss 3.1.1
 */
function bp_print_legacy_theme_deprecated_notice() {
	$message = sprintf(
		__('You are using a theme that relies on the older %s templates, and some things may not work properly. Consider switching to our <em>new</em> BuddyBoss Theme, a generic WordPress theme, or any newer theme that is compatible with the %s template pack.', 'buddyboss'),
		'<a href="https://wptavern.com/buddypress-contributors-are-building-a-new-template-pack" target="_blank" rel="noopener">BuddyPress Legacy</a>',
		'<a href="https://wptavern.com/buddypress-contributors-are-building-a-new-template-pack" target="_blank" rel="noopener">BuddyPress Nouveau</a>'
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
	$active_tab = $active_tab ?: bp_core_get_admin_active_tab();

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs         = apply_filters( 'bp_core_admin_tabs', bp_core_get_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['slug'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
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
 * @return string
 */
function bp_core_get_admin_tabs( $active_tab = '' ) {
	global $bp_admin_setting_tabs;

	if ( ! $bp_admin_setting_tabs ) {
		$bp_admin_setting_tabs = [];
	}

	uasort($bp_admin_setting_tabs, function($a, $b) {
		return $a->tab_order - $b->tab_order;
	});

	$tabs = array_filter($bp_admin_setting_tabs, function($tab) {
		return $tab->is_tab_visible();
	});

	$tabs = array_map(function($tab) {
		return [
			'href' => bp_core_admin_setting_url( $tab->tab_name ),
			'name' => $tab->tab_label,
			'slug' => $tab->tab_name
		];
	}, $tabs);

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
 * @since Buddyboss 3.1.1
 */
function bp_core_get_admin_active_tab() {
	$default_tab = apply_filters( 'bp_core_admin_default_active_tab', 'buddypress' );
	return isset($_GET['tab'])? $_GET['tab'] : $default_tab;
}

/**
 * Get the object of the current setting tab
 *
 * @since Buddyboss 3.1.1
 */
function bp_core_get_admin_active_tab_object() {
	global $bp_admin_setting_tabs;

	return $bp_admin_setting_tabs[bp_core_get_admin_active_tab()];
}

/**
 * Return the admin url with the tab selected
 *
 * @since Buddyboss 3.1.1
 */
function bp_core_admin_setting_url($tab, $args = []) {
	$args = wp_parse_args( $args, array(
		'page' => 'bp-settings',
		'tab' => $tab
	) );

	return bp_get_admin_url( add_query_arg( $args , 'admin.php' ) );
}

/**
 * Output the integration tabs in the admin area.
 *
 * @since Buddyboss 3.1.1
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_integration_tabs( $active_tab = '' ) {
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';
	$active_tab = $active_tab ?: bp_core_get_admin_integration_active_tab();

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyPress 1.9.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs         = apply_filters( 'bp_core_admin_integration_tabs', bp_core_get_admin_integrations_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['slug'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
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
 * @return string
 */
function bp_core_get_admin_integrations_tabs( $active_tab = '' ) {
	global $bp_admin_integration_tabs;

	if ( ! $bp_admin_integration_tabs ) {
		$bp_admin_integration_tabs = [];
	}

	uasort($bp_admin_integration_tabs, function($a, $b) {
		return $a->tab_order - $b->tab_order;
	});

	$tabs = array_filter($bp_admin_integration_tabs, function($tab) {
		return $tab->is_tab_visible();
	});

	$tabs = array_map(function($tab) {
		return [
			'href' => bp_core_admin_integrations_url($tab->tab_name),
			'name' => $tab->tab_label,
			'slug' => $tab->tab_name
		];
	}, $tabs);

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
 * Get the slug of the current integration tab
 *
 * @since Buddyboss 3.1.1
 */
function bp_core_get_admin_integration_active_tab() {
	$default_tab = apply_filters( 'bp_core_admin_default_active_tab', 'bp-learndash' );
	return isset($_GET['tab'])? $_GET['tab'] : $default_tab;
}

/**
 * Get the object of the current integration tab
 *
 * @since Buddyboss 3.1.1
 */
function bp_core_get_admin_integration_active_tab_object() {
	global $bp_admin_integration_tabs;

	return $bp_admin_integration_tabs[bp_core_get_admin_integration_active_tab()];
}

/**
 * Return the admin url with the tab selected
 *
 * @since Buddyboss 3.1.1
 */
function bp_core_admin_integrations_url($tab, $args = []) {
	$args = wp_parse_args( $args, array(
		'page' => 'bp-integrations',
		'tab' => $tab
	) );

	return bp_get_admin_url( add_query_arg( $args , 'admin.php' ) );
}

/** Help **********************************************************************/

/**
 * Adds contextual help to BuddyPress admin pages.
 *
 * @since BuddyPress 1.7.0
 * @todo Make this part of the BP_Component class and split into each component.
 *
 * @param string $screen Current screen.
 */
function bp_core_add_contextual_help( $screen = '' ) {

	$screen = get_current_screen();

	switch ( $screen->id ) {

		// Component page.
		case 'settings_page_bp-components' :

			// Help tabs.
			$screen->add_help_tab( array(
				'id'      => 'bp-comp-overview',
				'title'   => __( 'Overview', 'buddyboss' ),
				'content' => bp_core_add_contextual_help_content( 'bp-comp-overview' ),
			) );

			// Help panel - sidebar links.
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/">BuddyBoss</a>', 'buddyboss' ) . '</p>'
			);

			break;

		// Pages page.
		case 'settings_page_bp-page-settings' :

			// Help tabs.
			$screen->add_help_tab( array(
				'id' => 'bp-page-overview',
				'title' => __( 'Overview', 'buddyboss' ),
				'content' => bp_core_add_contextual_help_content( 'bp-page-overview' ),
			) );

			// Help panel - sidebar links.
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/">BuddyBoss</a>', 'buddyboss' ) . '</p>'
			);

			break;

		// Settings page.
		case 'settings_page_bp-settings' :

			// Help tabs.
			$screen->add_help_tab( array(
				'id'      => 'bp-settings-overview',
				'title'   => __( 'Overview', 'buddyboss' ),
				'content' => bp_core_add_contextual_help_content( 'bp-settings-overview' ),
			) );

			// Help panel - sidebar links.
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/">BuddyBoss</a>', 'buddyboss' ) . '</p>'
			);

			break;

		// Profile fields page.
		case 'users_page_bp-profile-setup' :

			// Help tabs.
			$screen->add_help_tab( array(
				'id'      => 'bp-profile-overview',
				'title'   => __( 'Overview', 'buddyboss' ),
				'content' => bp_core_add_contextual_help_content( 'bp-profile-overview' ),
			) );

			// Help panel - sidebar links.
			$screen->set_help_sidebar(
				'<p><strong>' . __( 'For more information:', 'buddyboss' ) . '</strong></p>' .
				'<p>' . __( '<a href="https://www.buddyboss.com/">BuddyBoss</a>', 'buddyboss' ) . '</p>'
			);

			break;
	}
}
add_action( 'load-settings_page_bp-components', 'bp_core_add_contextual_help' );
add_action( 'load-settings_page_bp-page-settings', 'bp_core_add_contextual_help' );
add_action( 'load-settings_page_bp-settings', 'bp_core_add_contextual_help' );
add_action( 'load-users_page_bp-profile-setup', 'bp_core_add_contextual_help' );

/**
 * Renders contextual help content to contextual help tabs.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $tab Current help content tab.
 * @return string
 */
function bp_core_add_contextual_help_content( $tab = '' ) {

	switch ( $tab ) {
		case 'bp-comp-overview' :
			$retval = __( 'By default, several BuddyBoss components are enabled. You can selectively enable or disable any of the components by using the form below. Your BuddyBoss installation will continue to function. However, the features of the disabled components will no longer be accessible to anyone using the site.', 'buddyboss' );
			break;

		case 'bp-page-overview' :
			$retval = __( 'BuddyBoss Components use WordPress Pages for their root directory/archive pages. You can change the page associations for each active component by using the form below.', 'buddyboss' );
			break;

		case 'bp-settings-overview' :
			$retval = __( 'Extra configuration settings are provided and activated. You can selectively enable or disable any setting by using the form on this screen.', 'buddyboss' );
			break;

		case 'bp-profile-overview' :
			$retval = __( 'Your users will distinguish themselves through their profile page. Create relevant profile fields that will show on each users profile.', 'buddyboss' ) . '<br /><br />' . __( 'Note: Any fields in the first group will appear on the signup page.', 'buddyboss' );
			break;

		default:
			$retval = false;
			break;
	}

	// Wrap text in a paragraph tag.
	if ( !empty( $retval ) ) {
		$retval = '<p>' . $retval . '</p>';
	}

	return $retval;
}

/** Separator *****************************************************************/

/**
 * Add a separator to the WordPress admin menus.
 *
 * @since BuddyPress 1.7.0
 *
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
	$last_sep     = is_network_admin() ? 'separator1' : 'separator2';

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
			foreach( (array) $custom_menus as $custom_menu ) {
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
	if ( ! empty( $_REQUEST['action2'] ) && $_REQUEST['action2'] != "-1" ) {
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

	add_meta_box( 'add-buddypress-nav-menu', __( 'BuddyBoss', 'buddyboss' ), 'bp_admin_do_wp_nav_menu_meta_box', 'nav-menus', 'side', 'default' );

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

	$walker = new BP_Walker_Nav_Menu_Checklist( false );
	$args   = array( 'walker' => $walker );

	$post_type_name = 'buddypress';

	$tabs = array();

	$tabs['loggedin']['label']  = __( 'Logged-In', 'buddyboss' );
	$tabs['loggedin']['pages']  = bp_nav_menu_get_loggedin_pages();

	$tabs['loggedout']['label'] = __( 'Logged-Out', 'buddyboss' );
	$tabs['loggedout']['pages'] = bp_nav_menu_get_loggedout_pages();

	?>

	<div id="buddypress-menu" class="posttypediv">
		<h4><?php _e( 'Logged-In', 'buddyboss' ) ?></h4>
		<p><?php _e( '<em>Logged-In</em> links are relative to the current user, and are not visible to visitors who are not logged in.', 'buddyboss' ) ?></p>

		<div id="tabs-panel-posttype-<?php echo $post_type_name; ?>-loggedin" class="tabs-panel tabs-panel-active">
			<ul id="buddypress-menu-checklist-loggedin" class="categorychecklist form-no-clear">
				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $tabs['loggedin']['pages'] ), 0, (object) $args );?>
			</ul>
		</div>

		<h4><?php _e( 'Logged-Out', 'buddyboss' ) ?></h4>
		<p><?php _e( '<em>Logged-Out</em> links are not visible to users who are logged in.', 'buddyboss' ) ?></p>

		<div id="tabs-panel-posttype-<?php echo $post_type_name; ?>-loggedout" class="tabs-panel tabs-panel-active">
			<ul id="buddypress-menu-checklist-loggedout" class="categorychecklist form-no-clear">
				<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $tabs['loggedout']['pages'] ), 0, (object) $args );?>
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
				<a href="<?php
				echo esc_url( add_query_arg(
					array(
						$post_type_name . '-tab' => 'all',
						'selectall'              => 1,
					),
					remove_query_arg( $removed_args )
				) );
				?>#buddypress-menu" class="select-all"><?php _e( 'Select All', 'buddyboss' ); ?></a>
			</span>
			<span class="add-to-menu">
				<input type="submit"<?php if ( function_exists( 'wp_nav_menu_disabled_check' ) ) : wp_nav_menu_disabled_check( $nav_menu_selected_id ); endif; ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'buddyboss' ); ?>" name="add-custom-menu-item" id="submit-buddypress-menu" />
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

	if ( bp_core_do_network_admin() ) {
		$admin_page = 'admin.php';
	} else {
		$admin_page = 'tools.php';
	}

	bp_core_add_admin_notice(
		sprintf(
			__( 'Are these emails not written in your site\'s language? Go to <a href="%s">BuddyBoss Tools and try the "reinstall emails"</a> tool.', 'buddyboss' ),
			esc_url( add_query_arg( 'page', 'bp-tools', bp_get_admin_url( $admin_page ) ) )
		),
		'updated'
	);
}
add_action( 'admin_head-edit.php', 'bp_admin_email_maybe_add_translation_notice' );

/**
 * In emails editor, add notice linking to token documentation on Codex.
 *
 * @since BuddyPress 2.5.0
 */
function bp_admin_email_add_codex_notice() {
	if ( get_current_screen()->post_type !== bp_get_email_post_type() ) {
		return;
	}

	bp_core_add_admin_notice(
		sprintf(
			__( 'Phrases wrapped in braces <code>{{ }}</code> are email tokens. <a href="%s">Learn about tokens on the BuddyPress Codex</a>.', 'buddyboss' ),
			esc_url( 'https://codex.buddypress.org/emails/email-tokens/' )
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
 * @param WP_Post $post Post object.
 * @param array   $box {
 *     Tags meta box arguments.
 *
 *     @type string   $id       Meta box ID.
 *     @type string   $title    Meta box title.
 *     @type callable $callback Meta box display callback.
 * }
 */
function bp_email_tax_type_metabox( $post, $box ) {
	$r = array(
		'taxonomy' => bp_get_email_tax_type()
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
			<ul id="<?php echo $tax_name; ?>checklist" data-wp-lists="list:<?php echo $tax_name; ?>" class="categorychecklist form-no-clear">
				<?php wp_terms_checklist( $post->ID, array( 'taxonomy' => $tax_name, 'walker' => new BP_Walker_Category_Checklist ) ); ?>
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

	<label class="screen-reader-text" for="excerpt"><?php
		/* translators: accessibility text */
		_e( 'Plain text email content', 'buddyboss' );
	?></label><textarea rows="5" cols="40" name="excerpt" id="excerpt"><?php echo $post->post_excerpt; // textarea_escaped ?></textarea>

	<p><?php _e( 'Most email clients support HTML email. However, some people prefer to receive plain text email. Enter a plain text alternative version of your email here.', 'buddyboss' ); ?></p>

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
	<script type="text/javascript">
	jQuery( '#menu-to-edit').on( 'click', 'a.item-edit', function() {
		var settings  = jQuery(this).closest( '.menu-item-bar' ).next( '.menu-item-settings' );
		var css_class = settings.find( '.edit-menu-item-classes' );

		if( css_class.val().indexOf( 'bp-menu' ) === 0 ) {
			css_class.attr( 'readonly', 'readonly' );
			settings.find( '.field-url' ).css( 'display', 'none' );
		}
	});
	</script>
<?php
}

/**
 * Add "Mark as Spam/Ham" button to user row actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array  $actions     User row action links.
 * @param object $user_object Current user information.
 * @return array $actions User row action links.
 */
function bp_core_admin_user_row_actions( $actions, $user_object ) {

	// Setup the $user_id variable from the current user object.
	$user_id = 0;
	if ( !empty( $user_object->ID ) ) {
		$user_id = absint( $user_object->ID );
	}

	// Bail early if user cannot perform this action, or is looking at themselves.
	if ( current_user_can( 'edit_user', $user_id ) && ( bp_loggedin_user_id() !== $user_id ) ) {

		// Admin URL could be single site or network.
		$url = bp_get_admin_url( 'users.php' );

		// If spammed, create unspam link.
		if ( bp_is_user_spammer( $user_id ) ) {
			$url             = add_query_arg( array( 'action' => 'ham', 'user' => $user_id ), $url );
			$unspam_link     = wp_nonce_url( $url, 'bp-spam-user' );
			$actions['ham']  = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $unspam_link ), esc_html__( 'Not Spam', 'buddyboss' ) );

		// If not already spammed, create spam link.
		} else {
			$url             = add_query_arg( array( 'action' => 'spam', 'user' => $user_id ), $url );
			$spam_link       = wp_nonce_url( $url, 'bp-spam-user' );
			$actions['spam'] = sprintf( '<a class="submitdelete" href="%1$s">%2$s</a>', esc_url( $spam_link ), esc_html__( 'Spam', 'buddyboss' ) );
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
	if ( ! empty( $action ) && in_array( $action, array( 'spam', 'ham' ) ) ) {

		check_admin_referer( 'bp-spam-user' );

		$user_id = ! empty( $_REQUEST['user'] ) ? intval( $_REQUEST['user'] ) : false;

		if ( empty( $user_id ) ) {
			return;
		}

		$redirect = wp_get_referer();

		$status = ( $action == 'spam' ) ? 'spam' : 'ham';

		// Process the user.
		bp_core_process_spammer_status( $user_id, $status );

		$redirect = add_query_arg( array( 'updated' => 'marked-' . $status ), $redirect );

		wp_redirect( $redirect );
	}

	// Display feedback.
	if ( ! empty( $updated ) && in_array( $updated, array( 'marked-spam', 'marked-ham' ) ) ) {

		if ( 'marked-spam' === $updated ) {
			$notice = __( 'User marked as spammer. Spam users are visible only to site admins.', 'buddyboss' );
		} else {
			$notice = __( 'User removed from spam.', 'buddyboss' );
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
	<script type="text/javascript">
		jQuery( document ).ready( function($) {
			$( '.row-actions .ham' ).each( function() {
				$( this ).closest( 'tr' ).addClass( 'site-spammed' );
			});
		});
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

function bp_xprofile_always_active( $components ) {
	$components['xprofile'] = 1;
	return $components;
}
add_filter( 'bp_active_components', 'bp_xprofile_always_active' );

/**
 * Custom metaboxes used by our 'bp-member-type' post type.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_member_type_custom_metaboxes() {
	add_meta_box( 'bp-member-type-key', __( 'Profile Type Key', 'buddyboss' ), 'bp_member_type_key_metabox', null, 'normal', 'high' );
	add_meta_box( 'bp-member-type-label-box', __( 'Labels', 'buddyboss' ), 'bp_member_type_labels_metabox', null, 'normal', 'high' );
	add_meta_box( 'bp-member-type-visibility', __( 'Visibility', 'buddyboss' ), 'bp_member_type_visibility_metabox', null, 'normal', 'high' );
	add_meta_box( 'bp-member-type-shortcode', __( 'Shortcode', 'buddyboss' ), 'bp_profile_shortcode_metabox', null, 'normal', 'high' );
	add_meta_box( 'bp-member-type-wp-role', __( 'WordPress Role', 'buddyboss' ), 'bp_member_type_wprole_metabox', null, 'normal', 'high' );

	if ( false === bp_restrict_group_creation() ) {

		$get_all_registered_group_types = bp_get_active_group_types();

		// Add meta box if group types is entered.
		if ( true === bp_disable_group_type_creation() && isset( $get_all_registered_group_types ) && !empty( $get_all_registered_group_types ) ) {
			add_meta_box( 'bp-member-type-group-create', __( 'Allowed Group Types', 'buddyboss' ), 'bp_member_type_group_create_metabox', null, 'normal', 'high' );
		}
	}

	if ( true === bp_disable_group_type_creation() && true === bp_enable_group_auto_join() ) {

		$get_all_registered_group_types = bp_get_active_group_types();

		// Add meta box if group types is entered.
		if ( true === bp_disable_group_type_creation() && isset( $get_all_registered_group_types ) && !empty( $get_all_registered_group_types ) ) {
			add_meta_box( 'bp-member-type-group-auto-join', __( 'Members with this profile type are only allowed to auto join groups with the following group types.', 'buddyboss' ), 'bp_member_type_group_auto_join_meta_box', null, 'normal', 'high' );
		}
	}

	// Metabox for the member type invite.
	if ( true === bp_disable_invite_member_type() ) {
		add_meta_box( 'bp-member-type-invite', __( 'Member Invites', 'buddyboss' ), 'bp_member_type_invite_meta_box', null, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes_' . bp_get_member_type_post_type(), 'bp_member_type_custom_metaboxes' );

/**
 * Generate Member Type Key Meta box.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param WP_Post $post
 */
function bp_member_type_key_metabox( $post ) {

	$key = get_post_meta($post->ID, '_bp_member_type_key', true );
	?>
	<p>
		<input type="text" name="bp-member-type[member_type_key]" value="<?php echo $key; ?>" placeholder="e.g. students" />
	</p>
	<p><?php _e( 'Profile Type Keys are used as internal identifiers. Lowercase alphanumeric characters, dashes and underscores are allowed.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Generate Member Type Label Meta box.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param WP_Post $post
 */
function bp_member_type_labels_metabox( $post ) {

	$meta = get_post_custom( $post->ID );

	$label_name = isset( $meta[ '_bp_member_type_label_name' ] ) ? $meta[ '_bp_member_type_label_name' ][ 0 ] : '';
	$label_singular_name = isset( $meta[ '_bp_member_type_label_singular_name' ] ) ? $meta[ '_bp_member_type_label_singular_name' ][ 0 ] : '';
	?>
	<table style="width: 100%;">
		<tr valign="top">
			<th scope="row" style="text-align: left; width: 15%;"><label for="bp-member-type[label_name]"><?php _e( 'Plural Label', 'buddyboss' ); ?></label></th>
			<td>
				<input type="text" class="bp-member-type-label-name" name="bp-member-type[label_name]" placeholder="<?php _e( 'e.g. Students', 'buddyboss' ); ?>"  value="<?php echo esc_attr( $label_name ); ?>" tabindex="2" style="width: 100%;" />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" style="text-align: left; width: 15%;"><label for="bp-member-type[label_singular_name]"><?php _e( 'Singular Label', 'buddyboss' ); ?></label></th>
			<td>
				<input type="text" class="bp-member-type-singular-name" name="bp-member-type[label_singular_name]" placeholder="<?php _e( 'e.g. Student', 'buddyboss' ); ?>" value="<?php echo esc_attr( $label_singular_name ); ?>" tabindex="3" style="width: 100%;" />
			</td>
		</tr>
	</table>
	<?php wp_nonce_field( 'bp-member-type-edit-member-type', '_bp-member-type-nonce' ); ?>
	<?php
}

/**
 * Generate Member Type Directory Meta box.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param WP_Post $post
 */
function bp_member_type_visibility_metabox( $post ) {

	$meta = get_post_custom( $post->ID );
	?>
	<?php
	$enable_filter = isset( $meta[ '_bp_member_type_enable_filter' ] ) ? $meta[ '_bp_member_type_enable_filter' ][ 0 ] : 0; //disabled by default
	?>
	<p>
		<input type='checkbox' name='bp-member-type[enable_filter]' value='1' <?php checked( $enable_filter, 1 ); ?> tabindex="5" />
		<strong><?php _e( 'Display in "All Types" filter in Members Directory', 'buddyboss' ); ?></strong>
	</p>
	<?php
	$enable_remove = isset( $meta[ '_bp_member_type_enable_remove' ] ) ? $meta[ '_bp_member_type_enable_remove' ][ 0 ] : 0; //enabled by default
	?>
	<p>
		<input type='checkbox' name='bp-member-type[enable_remove]' value='1' <?php checked( $enable_remove, 1 ); ?> tabindex="6" />
		<strong><?php _e( 'Hide members of this type from Members Directory', 'buddyboss' ); ?></strong>
	</p>
	<?php
}

/**
 * Shortcode metabox for the Member types admin edit screen.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param WP_Post $post
 */
function bp_profile_shortcode_metabox( $post ) {

	$key = bp_get_member_type_key( $post->ID );

	?>
	<p class="member-type-shortcode-wrapper">
		<!-- Target -->
		<input id='member-type-shortcode' value='<?php echo '[profile type="'. $key .'"]' ?>' style="width: 50%;">

		<button class="copy-to-clipboard button"  data-clipboard-target="#member-type-shortcode">
			<?php _e('Copy to clipboard', 'buddyboss' ) ?>
		</button>
	</p>
	<p><?php printf( __( 'To display all users with the %s profile type on a dedicated page, add the above shortcode to any WordPress page.', 'buddyboss' ), $post->post_title )?></p>

	<?php
}

/**
 * Generate Member Type WP Role Meta box
 *
 * @since BuddyBoss 3.1.1
 *
 * @param WP_Post $post
 */
function bp_member_type_wprole_metabox( $post ) {

	global $wp_roles;
	$tab_index = 7;
	$all_roles = $wp_roles->role_names;

	//remove bbPress roles
	unset($all_roles['bbp_keymaster']);
	unset($all_roles['bbp_spectator']);
	unset($all_roles['bbp_blocked']);
	unset($all_roles['bbp_moderator']);
	unset($all_roles['bbp_participant']);

	$selected_roles = get_post_meta($post->ID, '_bp_member_type_wp_roles', true);
	$selected_roles = (array) $selected_roles;
	?>

	<p><?php printf( __( 'Users of the %s profile type will be auto-assigned to the following WordPress roles (includes existing users).', 'buddyboss' ), $post->post_title )?></p>
	<p>
		<label for="bp-member-type-roles-none">
			<input
				type='radio'
				name='bp-member-type[wp_roles][]'
				id="bp-member-type-roles-none"
				value='' <?php echo empty( $selected_roles[0] ) ? 'checked' : '';
			?> />
			<?php _e( '(None)', 'buddyboss' ) ?>
		</label>
	</p>
	<?php
	if( isset($all_roles) && !empty($all_roles) ){
		foreach($all_roles as $key => $val){
			?>
			<p>
				<label for="bp-member-type-wp-roles-<?php echo $key ?>">
					<input
						type='radio'
						name='bp-member-type[wp_roles][]'
						id="bp-member-type-wp-roles-<?php echo $key ?>"
						value='<?php echo $key;?>' <?php echo in_array($key, $selected_roles) ? 'checked' : ''; ?>
						tabindex="<?php echo ++$tab_index ?>"
					/>
					<?php echo $val; ?>
				</label>
			</p>

			<?php
		}
	}
}

/**
 * Function for which type of groups this member type can create.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $post
 */
function bp_member_type_group_create_metabox( $post ) {

	?>
	<p><?php printf( __( 'Users of the %s profile type are only allowed to create groups of the following group types. Leave all unchecked to allow them to create any type of group.', 'buddyboss' ), $post->post_title )?></p>
	<?php

	$get_all_registered_group_types = bp_get_active_group_types();

	$get_selected_group_types = get_post_meta( $post->ID, '_bp_member_type_enabled_group_type_create', true ) ?: [];

	?>
	<p>
		<input class="group-type-checkboxes" type='checkbox' name='bp-group-type[]' value='<?php echo esc_attr( 'none' ); ?>' <?php checked( in_array( 'none', $get_selected_group_types ) ); ?> tabindex="7" />
		<strong><?php _e( '(None)', 'buddyboss' ); ?></strong>
	</p>
	<?php

	foreach ( $get_all_registered_group_types as $group_type_id ) {

		$group_type_key = get_post_meta( $group_type_id, '_bp_group_type_key', true );
		$group_type_label = bp_groups_get_group_type_object( $group_type_key )->labels['name'];
		?>
		<p>
			<input class="group-type-checkboxes"  type='checkbox' name='bp-group-type[]' value='<?php echo esc_attr( $group_type_key ); ?>' <?php checked( in_array( $group_type_key, $get_selected_group_types ) ); ?> tabindex="7" />
			<strong><?php _e( $group_type_label, 'buddyboss' ); ?></strong>
		</p>
		<?php
	}

	?>
	<script type="text/javascript">
		jQuery( document ).ready(function() {
			jQuery('#bp-member-type-group-create .inside .group-type-checkboxes').click(function () {
				var checkValues = jQuery(this).val();
				if ('none' === checkValues && jQuery(this).is(':checked')) {
					jQuery('#bp-member-type-group-create .inside .group-type-checkboxes').attr('checked', false);
					jQuery('#bp-member-type-group-create .inside .group-type-checkboxes').attr('disabled', true);
					jQuery(this).attr('checked', true);
					jQuery(this).attr('disabled', false);
				} else {
					jQuery('#bp-member-type-group-create .inside .group-type-checkboxes').attr('disabled', false);
				}
			});

			jQuery("#bp-member-type-group-create .inside .group-type-checkboxes").each(function () {
				var checkValues = jQuery(this).val();
				if ('none' === checkValues && jQuery(this).is(':checked')) {
					jQuery('#bp-member-type-group-create .inside .group-type-checkboxes').attr('checked', false);
					jQuery('#bp-member-type-group-create .inside .group-type-checkboxes').attr('disabled', true);
					jQuery(this).attr('checked', true);
					jQuery(this).attr('disabled', false);
					return false;
				} else {
					jQuery('#bp-member-type-group-create .inside .group-type-checkboxes').attr('disabled', false);
				}
			});
		});
	</script>
	<?php
}

/**
 * Function for which type of members type can auto join in groups.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $post
 */
function bp_member_type_group_auto_join_meta_box( $post ) {

	$get_all_registered_group_types = bp_get_active_group_types();

	$get_selected_group_types = get_post_meta( $post->ID, '_bp_member_type_enabled_group_type_auto_join', true );

	foreach ( $get_all_registered_group_types as $group_type_id ) {

		$group_type_key = get_post_meta( $group_type_id, '_bp_group_type_key', true );
		$group_type_label = bp_groups_get_group_type_object( $group_type_key )->labels['name'];
		?>
		<p>
			<input type='checkbox' name='bp-group-type-auto-join[]' value='<?php echo esc_attr( $group_type_key ); ?>' <?php checked( in_array( $group_type_key, $get_selected_group_types ) ); ?> tabindex="7" />
			<strong><?php _e( $group_type_label, 'buddyboss' ); ?></strong>
		</p>
		<?php

	}
}

/**
 * Function for members with this profile type are only allowed to invites following profile types.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $post
 */
function bp_member_type_invite_meta_box( $post ) {


	$meta = get_post_custom( $post->ID );
	?>
	<?php
	$enable_invite = isset( $meta[ '_bp_member_type_enable_invite' ] ) ? $meta[ '_bp_member_type_enable_invite' ][ 0 ] : 1; //enabled by default
	?>
	<p>
		<input type='checkbox' name='bp-member-type-enabled-invite' value='1' <?php checked( $enable_invite, 1 ); ?> tabindex="8" />
		<strong><?php _e( 'Enable Invites for this profile type?', 'buddyboss' ); ?></strong>
	</p>
	<?php

	$get_all_registered_profile_types = bp_get_active_member_types();

	$get_selected_profile_types = get_post_meta( $post->ID, '_bp_member_type_allowed_member_type_invite', true );

	foreach ( $get_all_registered_profile_types as $member_type_id ) {

		$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );
		?>
		<p>
			<input type='checkbox' name='bp-member-type-invite[]' value='<?php echo esc_attr( $member_type_id ); ?>' <?php checked( in_array( $member_type_id, $get_selected_profile_types ) ); ?> tabindex="9" />
			<strong><?php _e( $member_type_name, 'buddyboss' ); ?></strong>
		</p>
		<?php

	}
}

/**
 * Function for saving metaboxes data of member type post data.
 * @param $post_id
 *
 * @since BuddyBoss 3.1.1
 */
function bp_save_member_type_post_metabox_data( $post_id ) {
	global $wpdb, $error;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	$post = get_post( $post_id );

	if ( $post->post_type !== bp_get_member_type_post_type() )
		return;

	if ( ! isset( $_POST[ '_bp-member-type-nonce' ] ) )
		return;

	//verify nonce
	if ( ! wp_verify_nonce( $_POST[ '_bp-member-type-nonce' ], 'bp-member-type-edit-member-type' ) )
		return;

	//Save data
	$data = isset( $_POST[ 'bp-member-type' ] ) ? $_POST[ 'bp-member-type' ] : array();

	if ( empty( $data ) )
		return;

	$error = false;

	$post_title = wp_kses( $_POST[ 'post_title' ], wp_kses_allowed_html( 'strip' ) );

	// key
	$key = isset( $data['member_type_key'] ) ? sanitize_key( $data['member_type_key'] )  : '';

	//for label
	$label_name = isset( $data[ 'label_name' ] ) ? wp_kses( $data[ 'label_name' ], wp_kses_allowed_html( 'strip' ) ) : $post_title;
	$singular_name = isset( $data[ 'label_singular_name' ] ) ? wp_kses( $data[ 'label_singular_name' ], wp_kses_allowed_html( 'strip' ) ) : $post_title;

	//Remove space
	$label_name     = trim( $label_name );
	$singular_name  = trim( $singular_name );

	$enable_filter = isset( $data[ 'enable_filter' ] ) ? absint( $data[ 'enable_filter' ] ) : 0; //default inactive
	$enable_remove = isset( $data[ 'enable_remove' ] ) ? absint( $data[ 'enable_remove' ] ) : 0; //default inactive

	$data[ 'wp_roles' ] = array_filter( $data[ 'wp_roles' ] ); // Remove empty value from wp_roles array
	$wp_roles = isset( $data[ 'wp_roles' ] ) ? $data[ 'wp_roles' ] : '';

	update_post_meta( $post_id, '_bp_member_type_key', $key );
	update_post_meta( $post_id, '_bp_member_type_label_name', $label_name );
	update_post_meta( $post_id, '_bp_member_type_label_singular_name', $singular_name );
	update_post_meta( $post_id, '_bp_member_type_enable_filter', $enable_filter );
	update_post_meta( $post_id, '_bp_member_type_enable_remove', $enable_remove );
	update_post_meta( $post_id, '_bp_member_type_enabled_group_type_create', $_POST['bp-group-type'] );
	update_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', $_POST['bp-group-type-auto-join'] );
	update_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', $_POST['bp-member-type-invite'] );
	update_post_meta( $post_id, '_bp_member_type_enable_invite', $_POST['bp-member-type-enabled-invite'] );

	// Get user previous role.
	$old_wp_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );

	$check_both_old_new_role_same = ( $wp_roles === $old_wp_roles );

	if ( false === $check_both_old_new_role_same ) {
		$member_type_name = bp_get_member_type_key( $post_id );
		$type_term        = get_term_by( 'name',
			$member_type_name,
			'bp_member_type' ); // Get member type term data from database by name field.

		// Check logged user role.
		$user = new WP_User( get_current_user_id() );
		$current_user_role = $user->roles[0];

		// flag to check condition.
		$bp_prevent_data_update = false;

		// Fetch all the users which associated this member type.
		$get_user_ids = $wpdb->get_col( "SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->prefix}term_relationships r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = " . $type_term->term_id );
		if ( isset( $get_user_ids ) && ! empty( $get_user_ids ) ) {
			if ( in_array( get_current_user_id(), $get_user_ids ) ) {
				$bp_prevent_data_update = true;
			}
		}

		if ( true === $bp_prevent_data_update ) {
			if ( 'administrator' === $old_wp_roles[0] ) {
				if ( ! in_array( $current_user_role, $wp_roles ) ) {
					$bp_error_message_string = 'As your profile is currently assigned to this profile type, you cannot change its associated WordPress role. Changing this setting would remove your Administrator role and lock you out of the WordPress admin. You first need to remove yourself from this profile type (at Users > Your Profile > Extended) and then you can come back to this page to update the associated WordPress role.';
					$error_message           = apply_filters( 'bp_member_type_admin_error_message',
						__( $bp_error_message_string, 'buddyboss' ) );
					// Define the settings error to display
					add_settings_error( 'bp-invalid-role-selection',
						'bp-invalid-role-selection',
						$error_message,
						'error' );
					set_transient( 'bp_invalid_role_selection', get_settings_errors(), 30 );
					return;
				}
			}
		}

		update_post_meta( $post_id, '_bp_member_type_wp_roles', $wp_roles );

		//term exist
		if ( $type_term ) {

			if ( isset( $get_user_ids ) && ! empty( $get_user_ids ) ) {
				foreach ( $get_user_ids as $single_user ) {
					$bp_user = new WP_User( $single_user );
					foreach ( $bp_user->roles as $role ) {
						// Remove role
						$bp_user->remove_role( $role );
					}
					// Add role
					$bp_user->add_role( $wp_roles[0] );
				}
			}
		}
	}

}
add_action( 'save_post', 'bp_save_member_type_post_metabox_data');

/**
 * Function for displaying error message on edit profile type page.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_member_type_invalid_role_error_callback() {

	// If there are no errors, then we'll exit the function
	if ( ! ( $errors = get_transient( 'bp_invalid_role_selection' ) ) ) {
		return;
	}

	// Otherwise, build the list of errors that exist in the settings errores
	$message = '<div id="message" class="error">';
	foreach ( $errors as $error ) {
		$message .= '<p>' . $error['message'] . '</p>';
	}
	$message .= '</div><!-- #error --><style type="text/css">div.updated{display: none;}</style>';
	// Write them out to the screen
	echo $message;
	// Clear and the transient and unhook any other notices so we don't see duplicate messages
	delete_transient( 'bp_invalid_role_selection' );
	remove_action( 'admin_notices', 'bp_member_type_invalid_role_error_callback' );

}

// Hook for displaying error message on edit profile type page.
add_action( 'admin_notices', 'bp_member_type_invalid_role_error_callback' );

/**
 * Function setting up a admin action messages.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $messages
 *
 * @return mixed
 */
function bp_member_type_filter_update_messages( $messages ) {

	$update_message = $messages[ 'post' ];

	$update_message[ 1 ] = sprintf( __( 'Profile type updated.', 'buddyboss' ) );

	$update_message[ 4 ] = __( 'Profile type updated.', 'buddyboss' );

	$update_message[ 6 ] = sprintf( __( 'Profile type published. ', 'buddyboss' ) );

	$update_message[ 7 ] = __( 'Profile type saved.', 'buddyboss' );

	$messages[ bp_get_member_type_post_type() ] = $update_message;

	return $messages;
}
add_filter( 'post_updated_messages', 'bp_member_type_filter_update_messages' );

/**
 * Remove member type from users, when the Member Type is deleted.
 *
 * @since BuddyBoss 3.1.1
 *
 * @param $post_id
 */
function bp_delete_member_type( $post_id ) {
	global $wpdb;

	$post = get_post( $post_id );

	//Return if post is not 'bp-member-type' type
	if ( bp_get_member_type_post_type() !== $post->post_type ) return;

	$member_type_name 	= bp_get_member_type_key( $post_id );
	$type_term 			= get_term_by( 'name', $member_type_name, 'bp_member_type' ); // Get member type term data from database by name field.

	//term exist
	if ( $type_term ) {

		//Removes a member type term from the database.
		wp_delete_term( $type_term->term_id, 'bp_member_type' );

		//Removes a member type term relation with users from the database.
		$wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $type_term->term_taxonomy_id ) );
	}
}

//delete post
add_action( 'before_delete_post', 'bp_delete_member_type' );

// Register submenu page for member type import.
add_action('admin_menu', 'bp_register_member_type_import_submenu_page');

/**
 * Register submenu page for member type import.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_register_member_type_import_submenu_page() {
	add_submenu_page(
		null,   //or 'options.php'
		'Import Member Types',
		'Import Member Types',
		'manage_options',
		'bp-member-type-import',
		'bp_member_type_import_submenu_page'
	);
}

/**
 * Function for importing member types.
 *
 * @since BuddyBoss 3.1.1
 *
 */
function bp_member_type_import_submenu_page() {
	?>
	<div class="wrap">
		<div class="boss-import-area">
			<form id="bp-member-type-import-form" method="post" action="">
				<div class="import-panel-content">
					<h1><?php _e( 'Import Profile Types', 'buddyboss' ); ?></h1>
					<p><?php _e( 'Import your existing profile types (or "member types" in BuddyPress). You may have created these types <strong>manually via code</strong> or by using a <strong>third party plugin</strong> previously. The code or plugin needs to be activated on the site first. Then click "Run Migration" below and all of the data will be imported. Then you can disable and remove the old code or plugin.', 'buddyboss' ); ?></p><br/>

					<input type="submit" value="<?php _e('Run Migration', 'buddyboss'); ?>" id="bp-member-type-import-submit" name="bp-member-type-import-submit" class="button-primary">
				</div>
			</form>
		</div>
	</div>
	<br />

	<?php

	if (isset($_POST['bp-member-type-import-submit'])) {

		$registered_member_types = bp_get_member_types();
		$created_member_types = bp_get_active_member_types();
		$active_member_types = array();

		foreach ( $created_member_types as $created_member_type ) {
			$name = bp_get_member_type_key( $created_member_type );
			array_push($active_member_types, $name);
		}

		$registered_member_types = array_diff($registered_member_types, $active_member_types);

		if (empty($registered_member_types)) {
			?>
			<div class="wrap">
				<div class="error notice " id="message"><p><?php _e('Nothing to import', 'buddyboss'); ?></p></div>
			</div>
			<?php
		}

		foreach ( $registered_member_types as $key => $import_types_data ) {
			$sing_name = ucfirst($import_types_data);
			// Create post object
			$my_post = array(
				'post_type'     => bp_get_member_type_post_type(),
				'post_title'    => $sing_name,
				'post_status'   => 'publish',
				'post_author'   => get_current_user_id(),
			);

			// Insert the post into the database
			$post_id = wp_insert_post($my_post);

			if ( $post_id ) {

				update_post_meta( $post_id, '_bp_member_type_key', $sing_name );
				update_post_meta( $post_id, '_bp_member_type_label_name', $sing_name );
				update_post_meta( $post_id, '_bp_member_type_label_singular_name', $sing_name );

				?><div class="updated notice " id="message"><p><?php _e('Successfully Imported', 'buddyboss'); ?></p></div><?php
			}

		}

	}

}

/**
 * Function for display error message on extended profile page in admin.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_member_type_invalid_role_extended_profile_error_callback() {

	// If there are no errors, then we'll exit the function
	if ( ! ( $errors = get_transient( 'bp_invalid_role_selection_extended_profile' ) ) ) {
		return;
	}

	// Otherwise, build the list of errors that exist in the settings errores
	$message = '<div id="message" class="error">';
	foreach ( $errors as $error ) {
		$message .= '<p>' . $error['message'] . '</p>';
	}
	$message .= '</div><!-- #error --><style type="text/css">div.updated{display: none;}</style>';
	// Write them out to the screen
	echo $message;
	// Clear and the transient and unhook any other notices so we don't see duplicate messages
	delete_transient( 'bp_invalid_role_selection_extended_profile' );
	remove_action( 'admin_notices', 'bp_member_type_invalid_role_extended_profile_error_callback' );

}

// Hook for display error message on extended profile page in admin.
add_action( 'admin_notices', 'bp_member_type_invalid_role_extended_profile_error_callback' );
