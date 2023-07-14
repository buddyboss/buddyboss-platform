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
	if ( ! in_array( $plugin_page, array( 'bp-activity', 'bp-general-settings' ) ) ) {
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
		<p><?php printf( __( 'Components, Pages, Settings, and Forums, have been moved to <a href="%1$s">Settings &gt; BuddyPress</a>. Profile Fields has been moved into the <a href="%2$s">Users</a> menu.', 'buddyboss' ), esc_url( $settings_url ), bp_get_admin_url( 'admin.php?page=bp-profile-setup' ) ); ?></p>
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
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-pages' ), 'admin.php' ) );

		if ( isset( $_GET['page'] ) && 'bp-pages' === $_GET['page'] ) {
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
		$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-pages' ), 'admin.php' ) );
		if ( isset( $_GET['page'] ) && 'bp-pages' === $_GET['page'] ) {
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

	// Redirect to dashboard and trigger the Hello screen.
	wp_safe_redirect( add_query_arg( $query_args, bp_get_admin_url( '?hello=buddyboss' ) ) );
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

	$tabs = array(
		'0' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-components' ), 'admin.php' ) ),
			'name'  => __( 'Components', 'buddyboss' ),
			'class' => 'bp-components',
		),
		'1' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-pages' ), 'admin.php' ) ),
			'name'  => __( 'Pages', 'buddyboss' ),
			'class' => 'bp-pages',
		),
		'2' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-settings' ), 'admin.php' ) ),
			'name'  => __( 'Settings', 'buddyboss' ),
			'class' => 'bp-settings',
		),
		'3' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-integrations' ), 'admin.php' ) ),
			'name'  => __( 'Integrations', 'buddyboss' ),
			'class' => 'bp-integrations',
		),
		'4' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-tools' ), 'admin.php' ) ),
			'name'  => __( 'Tools', 'buddyboss' ),
			'class' => 'bp-tools',
		),
		'5' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-help' ), 'admin.php' ) ),
			'name'  => __( 'Help', 'buddyboss' ),
			'class' => 'bp-help',
		),
		'6' => array(
			'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-credits' ), 'admin.php' ) ),
			'name'  => __( 'Credits', 'buddyboss' ),
			'class' => 'bp-credits',
		),

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

	return $bp_admin_setting_tabs[ bp_core_get_admin_active_tab() ];
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

		// Profile fields page.
		case 'users_page_bp-profile-setup':
			// Help tabs.
			$screen->add_help_tab(
				array(
					'id'      => 'bp-profile-overview',
					'title'   => __( 'Overview', 'buddyboss' ),
					'content' => bp_core_add_contextual_help_content( 'bp-profile-overview' ),
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
add_action( 'load-users_page_bp-profile-setup', 'bp_core_add_contextual_help' );

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
			if ( bp_is_user_spammer( $user_id ) ) {
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
				$actions['unsuspend'] = sprintf(
					'<a class="bp-unsuspend-user ham" href="%1$s" data-action="unsuspend">%2$s</a>',
					esc_url( $unsuspend_link ),
					esc_html__( 'Unsuspend', 'buddyboss' )
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
				$actions['suspend'] = sprintf(
					'<a class="submitdelete bp-suspend-user" href="%1$s" data-action="suspend">%2$s</a>',
					esc_url( $suspend_link ),
					esc_html__( 'Suspend', 'buddyboss' )
				);
			}
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
 * Custom metaboxes used by our 'bp-member-type' post type.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_member_type_custom_metaboxes() {
	$screen = get_current_screen();
	add_meta_box( 'bp-member-type-label-box', __( 'Labels', 'buddyboss' ), 'bp_member_type_labels_metabox', null, 'normal', 'high' );
	add_meta_box( 'bp-member-type-permissions', __( 'Permissions', 'buddyboss' ), 'bp_member_type_permissions_metabox', null, 'normal', 'high' );
	add_meta_box( 'bp-member-type-wp-role', __( 'WordPress Role', 'buddyboss' ), 'bp_member_type_wprole_metabox', null, 'normal', 'high' );
	add_meta_box( 'bp-member-type-label-color', esc_html__( 'Label Colors', 'buddyboss' ), 'bb_member_type_labelcolor_metabox', null, 'normal', 'high' );
	if ( 'add' != $screen->action ) {
		add_meta_box( 'bp-member-type-shortcode', __( 'Shortcode', 'buddyboss' ), 'bp_profile_shortcode_metabox', null, 'normal', 'high' );
	}

	remove_meta_box( 'slugdiv', bp_get_member_type_post_type(), 'normal' );
}

add_action( 'add_meta_boxes_' . bp_get_member_type_post_type(), 'bp_member_type_custom_metaboxes' );

/**
 * Generate profile type Label Meta box.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param WP_Post $post
 */
function bp_member_type_labels_metabox( $post ) {

	$meta = get_post_custom( $post->ID );

	$label_name          = isset( $meta['_bp_member_type_label_name'] ) ? $meta['_bp_member_type_label_name'][0] : '';
	$label_singular_name = isset( $meta['_bp_member_type_label_singular_name'] ) ? $meta['_bp_member_type_label_singular_name'][0] : '';
	?>
	<!-- accesslint:ignore -->
	<table class="widefat bp-postbox-table">
		<thead>
		<tr>
			<th colspan="2">
				<?php _e( 'Profile Type', 'buddyboss' ); ?>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<th>
				<?php _e( 'Plural Label', 'buddyboss' ); ?>
			</th>
			<td>
				<input type="text" class="bp-member-type-label-name" name="bp-member-type[label_name]"
					   placeholder="<?php _e( 'e.g. Students', 'buddyboss' ); ?>"
					   value="<?php echo esc_attr( $label_name ); ?>" style="width: 100%;"/>
			</td>
		</tr>
		<tr>
			<th>
				<?php _e( 'Singular Label', 'buddyboss' ); ?>
			</th>
			<td>
				<input type="text" class="bp-member-type-singular-name" name="bp-member-type[label_singular_name]"
					   placeholder="<?php _e( 'e.g. Student', 'buddyboss' ); ?>"
					   value="<?php echo esc_attr( $label_singular_name ); ?>" style="width: 100%;"/>
			</td>
		</tr>
		</tbody>
	</table>
	<!-- accesslint:endignore -->
	<?php wp_nonce_field( 'bp-member-type-edit-member-type', '_bp-member-type-nonce' ); ?>

	<?php
}

/**
 * Generate Profile Type Permissions Meta box.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param WP_Post $post
 */
function bp_member_type_permissions_metabox( $post ) {

	$meta                               = get_post_custom( $post->ID );
	$enable_filter                      = isset( $meta['_bp_member_type_enable_filter'] ) ? $meta['_bp_member_type_enable_filter'][0] : 0; // disabled by default.
	$enable_profile_field               = isset( $meta['_bp_member_type_enable_profile_field'] ) ? $meta['_bp_member_type_enable_profile_field'][0] : 1; // enable by default.
	$allow_messaging_without_connection = isset( $meta['_bp_member_type_allow_messaging_without_connection'] ) ? $meta['_bp_member_type_allow_messaging_without_connection'][0] : 0; // disabled by default.
	?>
	<!-- accesslint:ignore -->
	<table class="widefat bp-postbox-table">
		<thead>
		<tr>
			<th scope="col" colspan="2">
				<?php _e( 'Members Directory', 'buddyboss' ); ?>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td colspan="2">
				<input type='checkbox' name='bp-member-type[enable_filter]' value='1'
					<?php
					checked(
						$enable_filter,
						1
					);
					?>
				/>
				<?php _e( 'Display this profile type in "Types" filter in Members Directory', 'buddyboss' ); ?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<?php
				$enable_remove = isset( $meta['_bp_member_type_enable_remove'] ) ? $meta['_bp_member_type_enable_remove'][0] : 0; // enabled by default.
				?>
				<input type='checkbox' name='bp-member-type[enable_remove]' value='1'
					<?php
					checked(
						$enable_remove,
						1
					);
					?>
				/>
				<?php _e( 'Hide all members of this type from Members Directory', 'buddyboss' ); ?>
				<p class="bb-description"><?php _e( 'Enabling this option hides all members with this profile type from the members directory, including the "Members" and "Recently Active Members" widgets.', 'buddyboss' ); ?></p>
			</td>
		</tr>
		<?php
		if ( bp_is_active( 'search' ) ) {
			?>
			 <!-- Condition to show only if network search component is enabled -->
			<tr>
				<td colspan="2">
					<?php
					$enable_search_remove = isset( $meta['_bp_member_type_enable_search_remove'] ) ? $meta['_bp_member_type_enable_search_remove'][0] : 0; // disabled by default.
					?>
					<input type='checkbox' name='bp-member-type[enable_search_remove]'
						   value='1' <?php checked( $enable_search_remove, 1 ); ?> />
					<?php esc_html_e( 'Hide all members of this type from Network Search results', 'buddyboss' ); ?>
					<p class="bb-description"><?php _e( 'Enabling this option hides all members with this profile type from network search results.', 'buddyboss' ); ?></p>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>

	<table class="widefat bp-postbox-table">
		<thead>
		<tr>
			<th scope="col" colspan="2">
				<?php _e( 'Profile Field', 'buddyboss' ); ?>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td colspan="2">
				<input type='checkbox' name='bp-member-type[enable_profile_field]'
					   value='1' <?php checked( $enable_profile_field, 1 ); ?> />
				<?php _e( 'Allow users to self-select as this profile type from the "Profile Type" profile field dropdown.', 'buddyboss' ); ?>
			</td>
		</tr>
		</tbody>
	</table>
	<?php

		if ( bp_is_active( 'messages' ) && bp_is_active( 'friends' ) && true === (bool) bp_get_option( 'bp-force-friendship-to-message', false ) ) {
	?>
		<table class="widefat bp-postbox-table">
			<thead>
			<tr>
				<th scope="col" colspan="2">
					<?php _e( 'Messaging', 'buddyboss' ); ?>
				</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td colspan="2">
					<input type='checkbox' name='bp-member-type[allow_messaging_without_connection]'
						value='1' <?php checked( $allow_messaging_without_connection, 1 ); ?> />
					<?php _e( 'Allow this profile type to send and receive messages without being connected', 'buddyboss' ); ?>
				</td>
			</tr>
			</tbody>
		</table>
	<?php
		}
	?>
	<!-- accesslint:endignore -->
	<?php
	if ( bp_is_active( 'groups' ) && false === bp_restrict_group_creation() ) {
		$get_all_registered_group_types = bp_get_active_group_types();
		// Add meta box if group types is entered.
		if ( true === bp_disable_group_type_creation() && isset( $get_all_registered_group_types ) && ! empty( $get_all_registered_group_types ) ) {
			// When profile types and group types are enabled, admins may restrict individual profile types from creating specified group types.
			?>
			<!-- accesslint:ignore -->
			<table class="widefat bp-postbox-table">
				<thead>
				<tr>
					<th colspan="2">
						<?php _e( 'Group Type Creation', 'buddyboss' ); ?>
					</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td colspan="2">
						<?php
						_e(
							'Select which group types this profile type is allowed to create. (Leave all unchecked to allow creation of any group type.)',
							'buddyboss'
						);
						?>
					</td>
				</tr>

				<?php

				$get_all_registered_group_types = bp_get_active_group_types();

				$get_selected_group_types = get_post_meta(
					$post->ID,
					'_bp_member_type_enabled_group_type_create',
					true
				) ?: array();

				?>

				<tr>
					<td colspan="2">
						<input class="group-type-checkboxes" type='checkbox' name='bp-group-type[]'
							   value='<?php echo esc_attr( 'none' ); ?>'
							<?php
							checked(
								in_array(
									'none',
									$get_selected_group_types
								)
							);
							?>
						/> <?php _e( '(None - hide group type option)', 'buddyboss' ); ?>
					</td>
				</tr>

				<?php

				foreach ( $get_all_registered_group_types as $group_type_id ) {

					$group_type_key   = get_post_meta( $group_type_id, '_bp_group_type_key', true );
					$group_type_label = bp_groups_get_group_type_object( $group_type_key )->labels['name'];
					?>

					<tr>
						<td colspan="2">
							<input class="group-type-checkboxes" type='checkbox' name='bp-group-type[]'
								   value='<?php echo esc_attr( $group_type_key ); ?>'
								<?php
								checked(
									in_array(
										$group_type_key,
										$get_selected_group_types
									)
								);
								?>
							/> <?php echo $group_type_label; ?>
						</td>
					</tr>

				<?php } ?>

				</tbody>
			</table>
			<script>
				jQuery( document ).ready( function () {
					jQuery( '#bp-member-type-permissions .inside .group-type-checkboxes' ).click( function () {
						var checkValues = jQuery( this ).val();
						if ( 'none' === checkValues && jQuery( this ).is( ':checked' ) ) {
							jQuery( '#bp-member-type-permissions .inside .group-type-checkboxes' ).prop( 'checked', false );
							jQuery( '#bp-member-type-permissions .inside .group-type-checkboxes' ).attr( 'disabled', true );
							jQuery( this ).prop( 'checked', true );
							jQuery( this ).attr( 'disabled', false );
						} else {
							jQuery( '#bp-member-type-permissions .inside .group-type-checkboxes' ).attr( 'disabled', false );
						}
					} );

					jQuery( "#bp-member-type-permissions .inside .group-type-checkboxes" ).each( function () {
						var checkValues = jQuery( this ).val();
						if ( 'none' === checkValues && jQuery( this ).is( ':checked' ) ) {
							jQuery( '#bp-member-type-permissions .inside .group-type-checkboxes' ).prop( 'checked', false );
							jQuery( '#bp-member-type-permissions .inside .group-type-checkboxes' ).attr( 'disabled', true );
							jQuery( this ).prop( 'checked', true );
							jQuery( this ).attr( 'disabled', false );
							return false;
						} else {
							jQuery( '#bp-member-type-permissions .inside .group-type-checkboxes' ).attr( 'disabled', false );
						}
					} );
				} );
			</script>
			<!-- accesslint:endignore -->
			<?php
		}
	}

	if ( bp_is_active( 'groups' ) && true === bp_disable_group_type_creation() && true === bp_enable_group_auto_join() ) {

		$get_all_registered_group_types = bp_get_active_group_types();

		// Add meta box if group types is entered.
		if ( true === bp_disable_group_type_creation() && isset( $get_all_registered_group_types ) && ! empty( $get_all_registered_group_types ) ) {
			?>
			<!-- accesslint:ignore -->
			<table class="widefat bp-postbox-table">
				<thead>
				<tr>
					<th colspan="2">
						<?php _e( 'Group Type Membership Approval', 'buddyboss' ); ?>
					</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td colspan="2">
						<?php _e( 'Automatically add members of this profile type to the following group types, after they have registered and activated their account. This setting does not apply to hidden groups.', 'buddyboss' ); ?>
					</td>
				</tr>

				<?php
				$get_all_registered_group_types = bp_get_active_group_types();

				$get_selected_group_types = get_post_meta( $post->ID, '_bp_member_type_enabled_group_type_auto_join', true ) ?: array();

				foreach ( $get_all_registered_group_types as $group_type_id ) {

					$group_type_key   = get_post_meta( $group_type_id, '_bp_group_type_key', true );
					$group_type_label = bp_groups_get_group_type_object( $group_type_key )->labels['name'];
					?>

					<tr>
						<td colspan="2">
							<input type='checkbox' name='bp-group-type-auto-join[]'
								   value='<?php echo esc_attr( $group_type_key ); ?>' <?php checked( in_array( $group_type_key, $get_selected_group_types ) ); ?> /> <?php echo $group_type_label; ?>
						</td>
					</tr>

				<?php } ?>

				</tbody>
			</table>
			<!-- accesslint:endignore -->
			<?php
		}
	}

	// Metabox for the profile type invite.
	if ( true === bp_disable_invite_member_type() && bp_is_active( 'invites' ) ) {

		// Allow a specific profile type to send invitations to new members and specify their profile type upon registration.
		$enable_invite = isset( $meta['_bp_member_type_enable_invite'] ) ? $meta['_bp_member_type_enable_invite'][0] : 1; // enabled by default.
		?>
		<!-- accesslint:ignore -->
		<table class="widefat bp-postbox-table">
			<thead>
			<tr>
				<th colspan="2">
					<?php _e( 'Email Invites', 'buddyboss' ); ?>
				</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td colspan="2">
					<input type='checkbox' name='bp-member-type-enabled-invite'
						   value='1' <?php checked( $enable_invite, 1 ); ?> /> <?php _e( 'Allow members to select the profile type that the invited recipient will be automatically assigned to on registration. If checked, select which of the below profile types can be assigned to the recipient:', 'buddyboss' ); ?>
				</td>
			</tr>

			<?php

			$get_all_registered_profile_types = bp_get_active_member_types();

			$get_selected_profile_types = get_post_meta( $post->ID, '_bp_member_type_allowed_member_type_invite', true ) ?: array();

			foreach ( $get_all_registered_profile_types as $member_type_id ) {

				$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );
				?>

				<tr>
					<td colspan="2">
						<input type='checkbox' name='bp-member-type-invite[]'
							   value='<?php echo esc_attr( $member_type_id ); ?>' <?php checked( in_array( $member_type_id, $get_selected_profile_types ) ); ?> /> <?php echo $member_type_name; ?>
					</td>
				</tr>

			<?php } ?>

			</tbody>
		</table>
		<!-- accesslint:endignore -->
		<?php

	}
}

/**
 * Shortcode metabox for the profile types admin edit screen.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param WP_Post $post
 */
function bp_profile_shortcode_metabox( $post ) {

	$key = bp_get_member_type_key( $post->ID );

	?>
	<!-- accesslint:ignore -->
	<p><?php _e( 'To display all users with this profile type on a dedicated page, add the below shortcode to any WordPress page.', 'buddyboss' ); ?></p>
	<code id="member-type-shortcode"><?php echo '[profile type="' . $key . '"]'; ?></code>
	<button class="copy-to-clipboard button"
			data-clipboard-target="#member-type-shortcode"><?php _e( 'Copy to clipboard', 'buddyboss' ); ?></button>
	<!-- accesslint:endignore -->
	<?php
}

/**
 * Generate profile type WP Role Meta box
 *
 * @since BuddyBoss 1.0.0
 *
 * @param WP_Post $post
 */
function bp_member_type_wprole_metabox( $post ) {

	global $wp_roles;
	$all_roles = $wp_roles->role_names;

	// remove bbPress roles.
	unset( $all_roles['bbp_keymaster'] );
	unset( $all_roles['bbp_spectator'] );
	unset( $all_roles['bbp_blocked'] );
	unset( $all_roles['bbp_moderator'] );
	unset( $all_roles['bbp_participant'] );

	$selected_roles = get_post_meta( $post->ID, '_bp_member_type_wp_roles', true );
	$selected_roles = (array) $selected_roles;
	?>

	<p><?php _e( 'Users of this profile type will be auto-assigned to the following WordPress roles (includes existing users):', 'buddyboss' ); ?></p>
	<p>
		<label for="bp-member-type-roles-none">
			<input
					type='radio'
					name='bp-member-type[wp_roles][]'
					id="bp-member-type-roles-none"
					value='none' <?php echo in_array( 'none', $selected_roles ) ? 'checked' : ''; ?> />
			<?php _e( '(None)', 'buddyboss' ); ?>
		</label>
	</p>
	<?php

	empty( $selected_roles[0] ) ? $selected_roles = array( 'subscriber' ) : '';

	if ( isset( $all_roles ) && ! empty( $all_roles ) ) {
		foreach ( $all_roles as $key => $val ) {
			?>
			<p>
				<label for="bp-member-type-wp-roles-<?php echo $key; ?>">
					<input
							type='radio'
							name='bp-member-type[wp_roles][]'
							id="bp-member-type-wp-roles-<?php echo $key; ?>"
							value='<?php echo $key; ?>' <?php echo in_array( $key, $selected_roles ) ? 'checked' : ''; ?>
					/>
					<?php echo $val; ?>
				</label>
			</p>

			<?php
		}
	}
}

/**
 * Save profile type post data.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 */
function bp_save_member_type_post_metabox_data( $post_id ) {
	global $wpdb, $error;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	$post = get_post( $post_id );

	if ( bp_get_member_type_post_type() !== $post->post_type ) {
		return;
	}

	if ( ! isset( $_POST['_bp-member-type-nonce'] ) ) {
		return;
	}

	// verify nonce.
	if ( ! wp_verify_nonce( sanitize_text_field( $_POST['_bp-member-type-nonce'] ), 'bp-member-type-edit-member-type' ) ) {
		return;
	}

	// Save data.
	$data = isset( $_POST['bp-member-type'] ) ? function_exists( 'map_deep' ) ? map_deep( wp_unslash( $_POST['bp-member-type'] ), 'sanitize_text_field' ) : sanitize_text_field( $_POST['bp-member-type'] ) : array();
	if ( empty( $data ) ) {
		return;
	}

	$error = false;

	$post_title = wp_kses( sanitize_text_field( $_POST['post_title'] ), wp_kses_allowed_html( 'strip' ) );

	// key.
	$key = get_post_field( 'post_name', $post_id );

	// for label.
	$label_name    = isset( $data['label_name'] ) ? wp_kses( $data['label_name'], wp_kses_allowed_html( 'strip' ) ) : $post_title;
	$singular_name = isset( $data['label_singular_name'] ) ? wp_kses( $data['label_singular_name'], wp_kses_allowed_html( 'strip' ) ) : $post_title;

	// Remove space.
	$label_name    = trim( $label_name );
	$singular_name = trim( $singular_name );

	$enable_filter        = isset( $data['enable_filter'] ) ? absint( $data['enable_filter'] ) : 0; // default inactive.
	$enable_remove        = isset( $data['enable_remove'] ) ? absint( $data['enable_remove'] ) : 0; // default inactive.
	$enable_search_remove = isset( $data['enable_search_remove'] ) ? absint( $data['enable_search_remove'] ) : 0; // default inactive.
	$enable_profile_field = isset( $data['enable_profile_field'] ) ? absint( $data['enable_profile_field'] ) : 0; // default active.
	$label_color          = isset( $data['label_color'] ) ? $data['label_color'] : '';

	$allow_messaging_without_connection = isset( $data['allow_messaging_without_connection'] ) ? absint( $data['allow_messaging_without_connection'] ) : 0; // default inactive.

	$data['wp_roles'] = array_filter( $data['wp_roles'] ); // Remove empty value from wp_roles array.
	$wp_roles         = isset( $data['wp_roles'] ) ? $data['wp_roles'] : '';

	$term = term_exists( sanitize_key( $key ), bp_get_member_type_tax_name() );
	if ( 0 !== $term && null !== $term ) {
		$digits = 3;
		$unique = rand( pow( 10, $digits - 1 ), pow( 10, $digits ) - 1 );
		$key    = $key . $unique;
	}

	$get_existing = get_post_meta( $post_id, '_bp_member_type_key', true );
	( '' === $get_existing ) ? update_post_meta( $post_id, '_bp_member_type_key', sanitize_key( $key ) ) : '';

	$enable_group_type_create        = filter_input( INPUT_POST, 'bp-group-type', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$enable_group_type_create        = ! empty( $enable_group_type_create ) ? $enable_group_type_create : '';
	$enable_group_type_auto_join     = filter_input( INPUT_POST, 'bp-group-type-auto-join', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$enable_group_type_auto_join     = ! empty( $enable_group_type_auto_join ) ? $enable_group_type_auto_join : '';
	$enable_group_type_invite        = filter_input( INPUT_POST, 'bp-member-type-invite', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$enable_group_type_invite        = ! empty( $enable_group_type_invite ) ? $enable_group_type_invite : '';
	$enable_group_type_enable_invite = filter_input( INPUT_POST, 'bp-member-type-enabled-invite', FILTER_DEFAULT );
	$enable_group_type_enable_invite = ! empty( $enable_group_type_enable_invite ) ? $enable_group_type_enable_invite : '';

	update_post_meta( $post_id, '_bp_member_type_label_name', $label_name );
	update_post_meta( $post_id, '_bp_member_type_label_singular_name', $singular_name );
	update_post_meta( $post_id, '_bp_member_type_enable_filter', $enable_filter );
	update_post_meta( $post_id, '_bp_member_type_enable_remove', $enable_remove );
	update_post_meta( $post_id, '_bp_member_type_enable_search_remove', $enable_search_remove );
	update_post_meta( $post_id, '_bp_member_type_enable_profile_field', $enable_profile_field );
	update_post_meta( $post_id, '_bp_member_type_enabled_group_type_create', $enable_group_type_create );
	update_post_meta( $post_id, '_bp_member_type_enabled_group_type_auto_join', $enable_group_type_auto_join );
	update_post_meta( $post_id, '_bp_member_type_allowed_member_type_invite', $enable_group_type_invite );
	update_post_meta( $post_id, '_bp_member_type_enable_invite', $enable_group_type_enable_invite );
	update_post_meta( $post_id, '_bp_member_type_label_color', $label_color );
	update_post_meta( $post_id, '_bp_member_type_allow_messaging_without_connection', $allow_messaging_without_connection );

	// Update all profile types which are allowed to message without connections.
	$profile_types_allowed_messaging = get_option( 'bp_member_types_allowed_messaging_without_connection', array() );

	if ( true === (bool) $allow_messaging_without_connection ) {
		$profile_types_allowed_messaging[ $get_existing ] = true;
	} elseif (
		! empty( $profile_types_allowed_messaging ) &&
		array_key_exists( $get_existing, $profile_types_allowed_messaging )
	) {
		unset( $profile_types_allowed_messaging[ $get_existing ] );
	}

	update_option( 'bp_member_types_allowed_messaging_without_connection', $profile_types_allowed_messaging );

	// Get user previous role.
	$old_wp_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );

	$check_both_old_new_role_same = ( $wp_roles === $old_wp_roles );

	if ( false === $check_both_old_new_role_same ) {
		$member_type_name = bp_get_member_type_key( $post_id );
		$type_term        = get_term_by(
			'name',
			$member_type_name,
			'bp_member_type'
		); // Get profile type term data from database by name field.

		// Check logged user role.
		$user              = new WP_User( get_current_user_id() );
		$current_user_role = $user->roles[0];

		// flag to check condition.
		$bp_prevent_data_update = false;

		if ( isset( $type_term->term_id ) ) {
			// Fetch all the users which associated this profile type.
			$get_user_ids = $wpdb->get_col( "SELECT u.ID FROM {$wpdb->users} u INNER JOIN {$wpdb->term_relationships} r ON u.ID = r.object_id WHERE u.user_status = 0 AND r.term_taxonomy_id = " . $type_term->term_id );
			if ( isset( $get_user_ids ) && ! empty( $get_user_ids ) ) {
				if ( in_array( get_current_user_id(), $get_user_ids ) ) {
					$bp_prevent_data_update = true;
				}
			}
		}

		if ( true === $bp_prevent_data_update ) {
			if ( isset( $old_wp_roles[0] ) && 'administrator' === $old_wp_roles[0] ) {
				if ( ! in_array( $current_user_role, $wp_roles ) ) {
					$bp_error_message_string = __( 'As your profile is currently assigned to this profile type, you cannot change its associated WordPress role. Changing this setting would remove your Administrator role and lock you out of the WordPress admin. You first need to remove yourself from this profile type (at Users > Your Profile > Extended) and then you can come back to this page to update the associated WordPress role.', 'buddyboss' );
					$error_message           = apply_filters( 'bp_member_type_admin_error_message', $bp_error_message_string );
					// Define the settings error to display.
					add_settings_error(
						'bp-invalid-role-selection',
						'bp-invalid-role-selection',
						$error_message,
						'error'
					);
					set_transient( 'bp_invalid_role_selection', get_settings_errors(), 30 );

					return;
				}
			}
		}

		update_post_meta( $post_id, '_bp_member_type_wp_roles', $wp_roles );

		// term exist.
		if ( $type_term ) {

			// Get selected profile type role.
			$selected_member_type_wp_roles = get_post_meta( $post_id, '_bp_member_type_wp_roles', true );

			if ( isset( $selected_member_type_wp_roles[0] ) && 'none' !== $selected_member_type_wp_roles[0] ) {
				if ( isset( $get_user_ids ) && ! empty( $get_user_ids ) ) {
					foreach ( $get_user_ids as $single_user ) {
						$bp_user = new WP_User( $single_user );
						foreach ( $bp_user->roles as $role ) {
							// Remove role.
							$bp_user->remove_role( $role );
						}
						// Add role.
						$bp_user->add_role( $wp_roles[0] );
					}
				}
			}
		}
	}

}

add_action( 'save_post', 'bp_save_member_type_post_metabox_data' );

/**
 * Display error message on edit profile type page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_member_type_invalid_role_error_callback() {

	// If there are no errors, then we'll exit the function.
	if ( ! ( $errors = get_transient( 'bp_invalid_role_selection' ) ) ) {
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
	delete_transient( 'bp_invalid_role_selection' );
	remove_action( 'admin_notices', 'bp_member_type_invalid_role_error_callback' );

}

// Hook for displaying error message on edit profile type page.
add_action( 'admin_notices', 'bp_member_type_invalid_role_error_callback' );

/**
 * Setup admin action messages.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $messages
 *
 * @return mixed
 */
function bp_member_type_filter_update_messages( $messages ) {

	$update_message = $messages['post'];

	$update_message[1] = sprintf( __( 'Profile type updated.', 'buddyboss' ) );

	$update_message[4] = __( 'Profile type updated.', 'buddyboss' );

	$update_message[6] = sprintf( __( 'Profile type published. ', 'buddyboss' ) );

	$update_message[7] = __( 'Profile type saved.', 'buddyboss' );

	$messages[ bp_get_member_type_post_type() ] = $update_message;

	return $messages;
}

add_filter( 'post_updated_messages', 'bp_member_type_filter_update_messages' );

/**
 * Remove profile type from users, when the profile type is deleted.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 */
function bp_delete_member_type( $post_id ) {
	global $wpdb;

	$post = get_post( $post_id );

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

/**
 * Add Navigation tab on top of the page BuddyBoss > Emails
 *
 * @since BuddyBoss 1.0.0
 */
function bp_emails_admin_email_listing_add_tab() {
	global $pagenow, $current_screen;

	if ( ( isset( $current_screen->post_type ) && $current_screen->post_type == bp_get_email_post_type() && $pagenow == 'edit.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bp_get_email_post_type() && $pagenow == 'post-new.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bp_get_email_post_type() && $pagenow == 'post.php' ) ) {
		?>
		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_emails_tabs( __( 'Emails', 'buddyboss' ) ); ?></h2>
		</div>
		<?php
	}

}

add_action( 'admin_notices', 'bp_emails_admin_email_listing_add_tab' );

add_filter( 'parent_file', 'bp_set_emails_platform_tab_submenu_active' );
/**
 * Highlights the submenu item using WordPress native styles.
 *
 * @param string $parent_file The filename of the parent menu.
 *
 * @return string $parent_file The filename of the parent menu.
 */
function bp_set_emails_platform_tab_submenu_active( $parent_file ) {
	global $pagenow, $current_screen;

	if ( ( isset( $current_screen->post_type ) && $current_screen->post_type == bp_get_email_post_type() && $pagenow == 'edit.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bp_get_email_post_type() && $pagenow == 'post-new.php' ) || ( isset( $current_screen->post_type ) && $current_screen->post_type == bp_get_email_post_type() && $pagenow == 'post.php' ) ) {
		$parent_file = 'buddyboss-platform';
	}

	return $parent_file;
}

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_groups_tabs( $active_tab = '' ) {

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
	 */
	do_action( 'bp_admin_groups_tabs' );
}

/**
 * Register tabs for the BuddyBoss > Groups screens.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab
 *
 * @return array
 */
function bp_core_get_groups_admin_tabs( $active_tab = '' ) {

	$tabs = array();

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-groups' ), 'admin.php' ) ),
		'name'  => __( 'All Groups', 'buddyboss' ),
		'class' => 'bp-all-groups',
	);

	if ( true === bp_disable_group_type_creation() ) {

		if ( is_network_admin() && bp_is_network_activated() ) {
			$group_url = get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=bp-group-type' );
		} else {
			$group_url = bp_get_admin_url( add_query_arg( array( 'post_type' => 'bp-group-type' ), 'edit.php' ) );
		}

		$tabs[] = array(
			'href'  => $group_url,
			'name'  => __( 'Group Types', 'buddyboss' ),
			'class' => 'bp-group-types',
		);

	}

	$query['autofocus[section]'] = 'bp_nouveau_group_primary_nav';
	$section_link                = add_query_arg( $query, admin_url( 'customize.php' ) );
	$tabs[]                      = array(
		'href'  => esc_url( $section_link ),
		'name'  => __( 'Group Navigation', 'buddyboss' ),
		'class' => 'bp-group-customizer',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_groups_admin_tabs', $tabs );
}

/**
 * Output the tabs in the admin area.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_emails_tabs( $active_tab = '' ) {

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
	$tabs = apply_filters( 'bp_core_admin_emails_tabs', bp_core_get_emails_admin_tabs( $active_tab ) );

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
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_admin_groups_tabs' );
}

/**
 * Register tabs for the BuddyBoss > Emails screens.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $active_tab
 *
 * @return array
 */
function bp_core_get_emails_admin_tabs( $active_tab = '' ) {

	$tabs = array();

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'post_type' => bp_get_email_post_type() ), 'edit.php' ) ),
		'name'  => __( 'Emails', 'buddyboss' ),
		'class' => 'bp-email-templates',
	);

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-emails-customizer-redirect' ), 'themes.php' ) ),
		'name'  => __( 'Customize Layout', 'buddyboss' ),
		'class' => 'bp-emails-customizer',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters( 'bp_core_get_emails_admin_tabs', $tabs );
}

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
 * @param array          $categories Array of block categories.
 * @param string|WP_Post $post       Post being loaded.
 */
function bp_block_category( $categories = array(), $post = null ) {

	if ( class_exists( 'WP_Block_Editor_Context' ) && $post instanceof WP_Block_Editor_Context && ! empty( $post->post ) ) {
		$post = $post->post;
	}

	if ( ! ( $post instanceof WP_Post ) ) {
		return $categories;
	}

	/**
	 * Filter here to add/remove the supported post types for the BuddyPress blocks category.
	 *
	 * @since 5.0.0
	 *
	 * @param array $value The list of supported post types. Defaults to WordPress built-in ones.
	 */
	$post_types = apply_filters( 'bp_block_category_post_types', array( 'post', 'page' ) );

	if ( ! $post_types ) {
		return $categories;
	}

	// Get the post type of the current item.
	$post_type = get_post_type( $post );

	if ( ! in_array( $post_type, $post_types, true ) ) {
		return $categories;
	}

	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'buddyboss',
				'title' => __( 'BuddyBoss', 'buddyboss' ),
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
		add_filter( 'block_categories_all', 'bp_block_category', 30, 2 );
	} else {
		add_filter( 'block_categories', 'bp_block_category', 30, 2 );
	}
}

add_action( 'bp_init', 'bb_block_init_category_filter' );

function bp_document_ajax_check_file_mime_type() {
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
			'href'  => bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-moderation',
					),
					'admin.php'
				)
			),
			'name'  => esc_html__( 'Flagged Members', 'buddyboss' ),
			'class' => 'bp-blocked-members',
		);
	}

	$reported_content_link = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-moderation' ), 'admin.php' ) );
	if ( bp_is_moderation_member_blocking_enable() ) {
		$reported_content_link = add_query_arg( array( 'tab' => 'reported-content' ), $reported_content_link );
	}

	$tabs[] = array(
		'href'  => $reported_content_link,
		'name'  => esc_html__( 'Reported Content', 'buddyboss' ),
		'class' => 'bp-reported-content',
	);

	$tabs[] = array(
		'href'  => bp_get_admin_url(
			add_query_arg(
				array(
					'taxonomy' => 'bpm_category',
					'tab'      => 'report-categories',
				),
				'edit-tags.php'
			)
		),
		'name'  => esc_html__( 'Reporting Categories', 'buddyboss' ),
		'class' => 'bp-report-categories',
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
 *
 * @return string
 */
function bb_get_pro_label_notice() {
	static $bb_pro_notice = '';

	if ( '' !== $bb_pro_notice ) {
		return $bb_pro_notice;
	}

	if ( function_exists( 'bb_platform_pro' ) && version_compare( bb_platform_pro()->version, '1.1.9.1', '<=' ) ) {
		$bb_pro_notice = sprintf(
			'<br/><span class="bb-head-notice"> %1$s <strong>%2$s</strong> %3$s</span>',
			esc_html__( 'Update to', 'buddyboss' ),
			esc_html__( 'BuddyBoss Platform Pro 1.2.0', 'buddyboss' ),
			esc_html__( 'to unlock', 'buddyboss' )
		);
	} else {
		$bb_pro_notice = sprintf(
			'<br/><span class="bb-head-notice"> %1$s <a target="_blank" href="https://www.buddyboss.com/platform/">%2$s</a> %3$s</span>',
			esc_html__( 'Install', 'buddyboss' ),
			esc_html__( 'BuddyBoss Platform Pro', 'buddyboss' ),
			esc_html__( 'to unlock', 'buddyboss' )
		);
	}

	return $bb_pro_notice;
}

/**
 * Get class for buddyboss pro settings fields.
 *
 * @since BuddyBoss 1.9.1
 *
 * @return string
 */
function bb_get_pro_fields_class() {
	static $pro_class = '';

	if ( '' !== $pro_class ) {
		return $pro_class;
	}

	$pro_class = 'bb-pro-inactive';
	if ( function_exists( 'bbp_pro_is_license_valid' ) && bbp_pro_is_license_valid() ) {
		$pro_class = 'bb-pro-active';
	}

	if ( function_exists( 'bb_platform_pro' ) && version_compare( bb_platform_pro()->version, '1.1.9.1', '<=' ) ) {
		$pro_class = 'bb-pro-inactive';
	}

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
 * Added new meta box as text and background color for member types label.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param $post Post data object.
 */
function bb_member_type_labelcolor_metabox( $post ) {
	$post_type         = isset( $post->post_type ) ? $post->post_type : 'bp-member-type';
	$meta_data         = get_post_meta( $post->ID, '_bp_member_type_label_color', true );
	$label_color_data  = ! empty( $meta_data ) ? maybe_unserialize( $meta_data ) : array();
	$color_type        = isset( $label_color_data['type'] ) ? $label_color_data['type'] : 'default';
	$colorpicker_class = 'default' === $color_type ? $post_type . '-hide-colorpicker' : $post_type . '-show-colorpicker';
	if ( function_exists( 'buddyboss_theme_get_option' ) && 'default' === $color_type ) {
		$background_color = buddyboss_theme_get_option( 'label_background_color' );
		$text_color       = buddyboss_theme_get_option( 'label_text_color' );
	} else {
		$background_color = isset( $label_color_data['background_color'] ) ? $label_color_data['background_color'] : '';
		$text_color       = isset( $label_color_data['text_color'] ) ? $label_color_data['text_color'] : '';
	}
	?>
	<div class="bb-meta-box-label-color-main">
		<p><?php esc_html_e( 'Select which label colors to use for profiles using this profile type. Profile Type labels are used in places such as member directories and profile headers.', 'buddyboss' ); ?></p>
		<p>
			<select name="<?php echo esc_attr( $post_type ); ?>[label_color][type]" id="<?php echo esc_attr( $post_type ); ?>-label-color-type">
				<option value="default" <?php selected( $color_type, 'default' ); ?>><?php esc_html_e( 'Default', 'buddyboss' ); ?></option>
				<option value="custom" <?php selected( $color_type, 'custom' ); ?>><?php esc_html_e( 'Custom', 'buddyboss' ); ?></option>
			</select>
		</p>
		<div id="<?php echo esc_attr( $post_type ); ?>-color-settings" class="<?php echo esc_attr( $post_type ); ?>-colorpicker <?php echo esc_attr( $colorpicker_class ); ?>">
			<div class="bb-meta-box-colorpicker">
				<div class="bb-colorpicker-row-one" id="<?php echo esc_attr( $post_type ); ?>-background-color-colorpicker">
					<label class="bb-colorpicker-label"><?php esc_html_e( 'Background Color', 'buddyboss' ); ?></label>
					<input id="<?php echo esc_attr( $post_type ); ?>-label-background-color" name="<?php echo esc_attr( $post_type ); ?>[label_color][background_color]" type="text" value="<?php echo esc_attr( $background_color ); ?>"/>
				</div>
				<div class="bb-colorpicker-row-one" id="<?php echo esc_attr( $post_type ); ?>-text-color-colorpicker">
					<label class="bb-colorpicker-label"><?php esc_html_e( 'Text Color', 'buddyboss' ); ?></label>
					<input id="<?php echo esc_attr( $post_type ); ?>-label-text-color" name="<?php echo esc_attr( $post_type ); ?>[label_color][text_color]" type="text" value="<?php echo esc_attr( $text_color ); ?>"/>
				</div>
			</div>
		</div>
	</div>
	<?php
}
