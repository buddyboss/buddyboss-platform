<?php
/**
 * Deprecated Functions
 *
 * @package BuddyBoss\Core
 * @deprecated Since 1.7.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Output the BuddyPress maintenance mode
 *
 * @since BuddyPress 1.6.0
 * @deprecated BuddyPress 1.7.0
 */
function bboss_maintenance_mode() {
	echo esc_html( bboss_get_maintenance_mode() );
}
	/**
	 * Return the BuddyPress maintenance mode
	 *
	 * @since BuddyPress 1.6.0
	 * @deprecated BuddyPress 1.7.0
	 * @return string The BuddyPress maintenance mode
	 */
function bboss_get_maintenance_mode() {
	return bboss()->maintenance_mode;
}

/**
 * bboss_xprofile_get_profile()
 *
 * @deprecated BuddyPress 1.7.0
 */
function bboss_xprofile_get_profile() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bboss_locate_template( array( 'profile/profile-loop.php' ), true );
}

/**
 * bboss_get_profile_header()
 *
 * @deprecated BuddyPress 1.7.0
 */
function bboss_get_profile_header() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bboss_locate_template( array( 'profile/profile-header.php' ), true );
}

/**
 * bp_exists( $component_name )
 *
 * @deprecated BuddyPress 1.7.0
 * @param string $component_name
 * @return boolean
 */
function bboss_exists( $component_name ) {
	_deprecated_function( __FUNCTION__, '1.7' );
	if ( function_exists( $component_name . '_install' ) ) {
		return true;
	}

	return false;
}

/**
 * bboss_get_plugin_sidebar()
 *
 * @deprecated BuddyPress 1.7.0
 */
function bboss_get_plugin_sidebar() {
	_deprecated_function( __FUNCTION__, '1.7' );
	bboss_locate_template( array( 'plugin-sidebar.php' ), true );
}

/**
 * On multiblog installations you must first allow themes to be activated and
 * show up on the theme selection screen. This function will let the BuddyPress
 * bundled themes show up on the root blog selection screen and bypass this
 * step. It also means that the themes won't show for selection on other blogs.
 *
 * @deprecated BuddyPress 1.7.0
 * @return array
 */
function bboss_core_allow_default_theme( $themes ) {
	_deprecated_function( __FUNCTION__, '1.7' );

	if ( ! bboss_current_user_can( 'bp_moderate' ) ) {
		return $themes;
	}

	if ( bboss_get_root_blog_id() != get_current_blog_id() ) {
		return $themes;
	}

	if ( isset( $themes['bp-default'] ) ) {
		return $themes;
	}

	$themes['bp-default'] = true;

	return $themes;
}

/**
 * No longer used by BuddyPress core
 *
 * @deprecated BuddyPress 1.7.0
 * @param string $page
 * @return boolean True if is BuddyPress page
 */
function bboss_is_page( $page = '' ) {
	_deprecated_function( __FUNCTION__, '1.7' );

	if ( ! bboss_is_user() && bboss_is_current_component( $page ) ) {
		return true;
	}

	if ( 'home' == $page ) {
		return is_front_page();
	}

	return false;
}

/** Admin *********************************************************************/

/**
 * This function was originally used to update pre-1.1 schemas, but that was
 * before we had a legitimate update process.
 *
 * @deprecated BuddyPress 1.7.0
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bboss_update_db_stuff() {
	global $wpdb;

	$bp        = bboss();
	$bp_prefix = bboss_core_get_table_prefix();

	// Rename the old user activity cached table if needed.
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$bp_prefix}bp_activity_user_activity_cached%'" ) ) {
		$wpdb->query( "RENAME TABLE {$bp_prefix}bp_activity_user_activity_cached TO {$bp->activity->table_name}" );
	}

	// Rename fields from pre BP 1.2
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$bp->activity->table_name}%'" ) ) {
		if ( $wpdb->get_var( "SHOW COLUMNS FROM {$bp->activity->table_name} LIKE 'component_action'" ) ) {
			$wpdb->query( "ALTER TABLE {$bp->activity->table_name} CHANGE component_action type varchar(75) NOT NULL" );
		}

		if ( $wpdb->get_var( "SHOW COLUMNS FROM {$bp->activity->table_name} LIKE 'component_name'" ) ) {
			$wpdb->query( "ALTER TABLE {$bp->activity->table_name} CHANGE component_name component varchar(75) NOT NULL" );
		}
	}

	// On first installation - record all existing blogs in the system.
	if ( ! (int) $bp->site_options['bp-blogs-first-install'] ) {
		bboss_blogs_record_existing_blogs();
		bboss_update_option( 'bp-blogs-first-install', 1 );
	}

	if ( is_multisite() ) {
		bboss_core_add_illegal_names();
	}

	// Update and remove the message threads table if it exists
	if ( $wpdb->get_var( "SHOW TABLES LIKE '%{$bp_prefix}bp_messages_threads%'" ) ) {
		if ( BBoss_Messages_Thread::update_tables() ) {
			$wpdb->query( "DROP TABLE {$bp_prefix}bp_messages_threads" );
		}
	}
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_allow_default_theme' ) && ! function_exists( 'bp_core_allow_default_theme' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_allow_default_theme()}.
	 *
	 * @deprecated Use bboss_core_allow_default_theme() instead.
	 */
	function bp_core_allow_default_theme( $themes ) {
		return bboss_core_allow_default_theme( $themes );
	}
}
if ( function_exists( 'bboss_exists' ) && ! function_exists( 'bp_exists' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_exists()}.
	 *
	 * @deprecated Use bboss_exists() instead.
	 */
	function bp_exists( $component_name ) {
		return bboss_exists( $component_name );
	}
}
if ( function_exists( 'bboss_get_maintenance_mode' ) && ! function_exists( 'bp_get_maintenance_mode' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_maintenance_mode()}.
	 *
	 * @deprecated Use bboss_get_maintenance_mode() instead.
	 */
	function bp_get_maintenance_mode() {
		return bboss_get_maintenance_mode();
	}
}
if ( function_exists( 'bboss_get_plugin_sidebar' ) && ! function_exists( 'bp_get_plugin_sidebar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_plugin_sidebar()}.
	 *
	 * @deprecated Use bboss_get_plugin_sidebar() instead.
	 */
	function bp_get_plugin_sidebar() {
		return bboss_get_plugin_sidebar();
	}
}
if ( function_exists( 'bboss_get_profile_header' ) && ! function_exists( 'bp_get_profile_header' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_profile_header()}.
	 *
	 * @deprecated Use bboss_get_profile_header() instead.
	 */
	function bp_get_profile_header() {
		return bboss_get_profile_header();
	}
}
if ( function_exists( 'bboss_is_page' ) && ! function_exists( 'bp_is_page' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_page()}.
	 *
	 * @deprecated Use bboss_is_page() instead.
	 */
	function bp_is_page( $page = '' ) {
		return bboss_is_page( $page );
	}
}
if ( function_exists( 'bboss_maintenance_mode' ) && ! function_exists( 'bp_maintenance_mode' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_maintenance_mode()}.
	 *
	 * @deprecated Use bboss_maintenance_mode() instead.
	 */
	function bp_maintenance_mode() {
		return bboss_maintenance_mode();
	}
}
if ( function_exists( 'bboss_update_db_stuff' ) && ! function_exists( 'bp_update_db_stuff' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_update_db_stuff()}.
	 *
	 * @deprecated Use bboss_update_db_stuff() instead.
	 */
	function bp_update_db_stuff() {
		return bboss_update_db_stuff();
	}
}
if ( function_exists( 'bboss_xprofile_get_profile' ) && ! function_exists( 'xprofile_get_profile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_xprofile_get_profile()}.
	 *
	 * @deprecated Use bboss_xprofile_get_profile() instead.
	 */
	function xprofile_get_profile() {
		return bboss_xprofile_get_profile();
	}
}
// @bbwt-com-end compat-layer
