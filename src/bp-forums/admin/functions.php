<?php

/**
 * Forums Admin Functions
 *
 * @package BuddyBoss\Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Admin Menus ***************************************************************/

/**
 * Add a separator to the WordPress admin menus
 *
 * @since bbPress (r2957)
 */
function bbp_admin_separator() {

	// Caps necessary where a separator is necessary
	$caps = array(
		'bbp_forums_admin',
		'bbp_topics_admin',
		'bbp_replies_admin',
	);

	// Loop through caps, and look for a reason to show the separator
	foreach ( $caps as $cap ) {
		if ( current_user_can( $cap ) ) {
			bbpress()->admin->show_separator = true;
			break;
		}
	}

	// Bail if no separator
	if ( false === bbpress()->admin->show_separator ) {
		return;
	}

	global $menu;

	$menu[] = array( '', 'read', 'separator-bbpress', '', 'wp-menu-separator bbpress' );
}

/**
 * Tell WordPress we have a custom menu order
 *
 * @since bbPress (r2957)
 *
 * @param bool $menu_order Menu order
 * @return mixed True if separator, false if not
 */
function bbp_admin_custom_menu_order( $menu_order = false ) {
	if ( false === bbpress()->admin->show_separator ) {
		return $menu_order;
	}

	return true;
}

/**
 * Move our custom separator above our custom post types
 *
 * @since bbPress (r2957)
 *
 * @param array $menu_order Menu Order
 * @uses bbp_get_forum_post_type() To get the forum post type
 * @return array Modified menu order
 */
function bbp_admin_menu_order( $menu_order ) {

	// Bail if user cannot see any top level Forums menus
	if ( empty( $menu_order ) || ( false === bbpress()->admin->show_separator ) ) {
		return $menu_order;
	}

	// Initialize our custom order array
	$bbp_menu_order = array();

	// Menu values
	$second_sep   = 'separator2';
	$custom_menus = array(
		'separator-bbpress',                               // Separator
		'edit.php?post_type=' . bbp_get_forum_post_type(), // Forums
		'edit.php?post_type=' . bbp_get_topic_post_type(), // Topics
		'edit.php?post_type=' . bbp_get_reply_post_type(),  // Replies
	);

	// Loop through menu order and do some rearranging
	foreach ( $menu_order as $item ) {

		// Position Forums menus above appearance
		if ( $second_sep == $item ) {

			// Add our custom menus
			foreach ( $custom_menus as $custom_menu ) {
				if ( array_search( $custom_menu, $menu_order ) ) {
					$bbp_menu_order[] = $custom_menu;
				}
			}

			// Add the appearance separator
			$bbp_menu_order[] = $second_sep;

			// Skip our menu items
		} elseif ( ! in_array( $item, $custom_menus ) ) {
			$bbp_menu_order[] = $item;
		}
	}

	// Return our custom order
	return $bbp_menu_order;
}

/**
 * Filter sample permalinks so that certain languages display properly.
 *
 * @since bbPress (r3336)
 *
 * @param string $post_link Custom post type permalink
 * @param object $_post Post data object
 * @param bool   $leavename Optional, defaults to false. Whether to keep post name or page name.
 * @param bool   $sample Optional, defaults to false. Is it a sample permalink.
 *
 * @uses is_admin() To make sure we're on an admin page
 * @uses bbp_is_custom_post_type() To get the forum post type
 *
 * @return string The custom post type permalink
 */
function bbp_filter_sample_permalink( $post_link, $_post, $leavename = false, $sample = false ) {

	// Bail if not on an admin page and not getting a sample permalink
	if ( ! empty( $sample ) && is_admin() && bbp_is_custom_post_type() ) {
		return urldecode( $post_link );
	}

	// Return post link
	return $post_link;
}

/**
 * Sanitize permalink slugs when saving the settings page.
 *
 * @since bbPress (r5364)
 *
 * @param string $slug
 * @return string
 */
function bbp_sanitize_slug( $slug = '' ) {

	// Don't allow multiple slashes in a row.
	$value = preg_replace( '#/+#', '/', str_replace( '#', '', $slug ) );

	// Strip out unsafe or unusable chars.
	$value = esc_url_raw( $value );

	// esc_url_raw() adds a scheme via esc_url(), so let's remove it.
	$value = str_replace( 'http://', '', $value );

	// Trim off first and last slashes.
	// We already prevent double slashing elsewhere, but let's prevent
	// accidental poisoning of options values where we can.
	$value = ltrim( $value, '/' );
	$value = rtrim( $value, '/' );

	// Remove all the accents from the slug.
	$value = remove_accents( $value );

	// Filter the result and return.
	return apply_filters( 'bbp_sanitize_slug', $value, $slug );
}

/**
 * Uninstall all Forums options and capabilities from a specific site.
 *
 * @since bbPress (r3765)
 * @param type $site_id
 */
function bbp_do_uninstall( $site_id = 0 ) {
	if ( empty( $site_id ) ) {
		$site_id = get_current_blog_id();
	}

	switch_to_blog( $site_id );
	bbp_delete_options();
	bbp_remove_roles();
	bbp_remove_caps();
	flush_rewrite_rules();
	restore_current_blog();
}

/**
 * This tells WP to highlight the Tools > Forums menu item,
 * regardless of which actual Forums Tools screen we are on.
 *
 * The conditional prevents the override when the user is viewing settings or
 * any third-party plugins.
 *
 * @since bbPress (r3888)
 * @global string $plugin_page
 * @global array $submenu_file
 */
function bbp_tools_modify_menu_highlight() {
	global $plugin_page, $submenu_file;

	// This tweaks the Tools subnav menu to only show one Forums menu item
	if ( ! in_array( $plugin_page, array( 'bbp-settings' ) ) ) {
		$submenu_file = 'bbp-repair';
	}
}

/**
 * Output the tabs in the admin area
 *
 * @since bbPress (r3872)
 * @param string $active_tab Name of the tab that is active
 */
function bbp_tools_admin_tabs( $active_tab = '' ) {
	echo bbp_get_tools_admin_tabs( $active_tab );
}

	/**
	 * Output the tabs in the admin area
	 *
	 * @since bbPress (r3872)
	 * @param string $active_tab Name of the tab that is active
	 */
function bbp_get_tools_admin_tabs( $active_tab = '' ) {

	// Declare local variables
	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	// Setup core admin tabs
	$tabs = apply_filters(
		'bbp_tools_admin_tabs',
		array(
			'0' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'bbp-repair' ), 'admin.php' ) ),
				'name' => __( 'Repair Forums', 'buddyboss' ),
			),
			'1' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'bbp-converter' ), 'admin.php' ) ),
				'name' => __( 'Import Forums', 'buddyboss' ),
			),
			'2' => array(
				'href' => get_admin_url( '', add_query_arg( array( 'page' => 'bbp-reset' ), 'admin.php' ) ),
				'name' => __( 'Reset Forums', 'buddyboss' ),
			),
		)
	);

	// Loop through tabs and build navigation
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $active_class : $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	// Output the tabs
	return $tabs_html;
}

function bbp_core_get_tools_settings_admin_tabs( $tabs ) {

	$tabs[] = array(
		'href' => bp_get_admin_url( add_query_arg( array(
			'page' => 'bbp-repair',
			'tab'  => 'bbp-repair',
		),
			'admin.php' ) ),
		'name' => __( 'Repair Forums', 'buddyboss' ),
		'slug' => 'bbp-repair',
	);

	// $tabs[] = array(
	// 'href' => get_admin_url( '', add_query_arg( array( 'page' => 'bbp-reset', 'tab' => 'bbp-reset' ), 'admin.php' ) ),
	// 'name' => __( 'Reset Forums', 'buddyboss' ),
	// 'slug' => 'bbp-reset'
	// );

	return $tabs;
}
add_filter( 'bp_core_get_tools_settings_admin_tabs', 'bbp_core_get_tools_settings_admin_tabs', 11, 1 );

function bbp_core_get_import_forum_tools_settings_admin_tabs( $tabs ) {

	$tabs[] = array(
		'href' => bp_get_admin_url( add_query_arg( array(
			'page' => 'bbp-converter',
			'tab'  => 'bbp-converter',
		),
			'admin.php' ) ),
		'name' => __( 'Import Forums', 'buddyboss' ),
		'slug' => 'bbp-converter',
	);

	return $tabs;
}
add_filter( 'bp_core_get_tools_settings_admin_tabs', 'bbp_core_get_import_forum_tools_settings_admin_tabs', 16, 1 );

/**
 * Function to check table exists or not for converter.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param object $opdb       Old forum Database.
 * @param string $table_name Old forums table name.
 *
 * @return bool
 */
function bb_check_table_exists( $opdb, $table_name ) {
	if ( empty( $opdb ) || empty( $table_name ) ) {
		return false;
	}

	$query          = $opdb->prepare( "SHOW TABLES LIKE %s", $table_name );
	$existing_table = $opdb->get_var( $query );

	if ( $existing_table === $table_name ) {
		return true;
	}

	return false;
}

/**
 * Function to check column exists or not for converter.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param object $opdb        Old forum Database.
 * @param string $table_name  Old forums table name.
 * @param string $column_name Old forums tables column name.
 *
 * @return bool
 */
function bb_check_column_exists( $opdb, $table_name, $column_name ) {
	if ( empty( $opdb ) || empty( $table_name ) || empty( $column_name ) ) {
		return false;
	}

	$table_name = $opdb->prefix . $table_name;

	if ( bb_check_table_exists( $opdb, $table_name ) ) {
		$field_array = $opdb->get_results( "DESCRIBE {$table_name}", ARRAY_A );
		if ( false !== $field_array ) {
			$field_names = array_column( $field_array, 'Field' );

			return in_array( $column_name, $field_names );
		}
	}

	return false;
}
