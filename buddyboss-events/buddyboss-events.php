<?php
/**
 * Plugin Name: BuddyBoss Events
 * Plugin URI:  https://buddyboss.com/
 * Description: Native events component for BuddyBoss Platform — group events tabs, activity feed integration, member profiles, and group member invites.
 * Version:     1.0.0
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Text Domain: buddyboss
 * Domain Path: /languages
 *
 * @package BuddyBoss\Events
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'BP_EVENTS_PLUGIN_FILE', __FILE__ );
define( 'BP_EVENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BP_EVENTS_VERSION', '1.0.0' );

/**
 * Load the events component once BuddyBoss Platform is ready.
 * Hooks into bp_include (priority 11) so Platform's own components load first.
 */
function bp_events_load() {
	// BuddyBoss Platform must be active.
	if ( ! function_exists( 'buddypress' ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-error"><p><strong>BuddyBoss Events</strong> requires BuddyBoss Platform to be installed and active.</p></div>';
		} );
		return;
	}

	// Load filters + optional-component registration.
	require_once BP_EVENTS_PLUGIN_DIR . 'src/bp-events/bp-events-filters.php';
}
add_action( 'plugins_loaded', 'bp_events_load', 20 );

/**
 * Fire activation hook so bp_events_install() sets up DB tables.
 */
register_activation_hook( __FILE__, function() {
	do_action( 'bp_events_activated' );
} );

/**
 * Clear cron job on deactivation.
 */
register_deactivation_hook( __FILE__, function() {
	wp_clear_scheduled_hook( 'bp_events_extend_occurrences' );
} );

/**
 * Register Events admin menu directly, bypassing the BP component bootstrap.
 */
add_action( 'admin_menu', function() {
	// Ensure core functions are available before the admin page renders.
	if ( ! function_exists( 'bp_events_get_events' ) ) {
		require_once BP_EVENTS_PLUGIN_DIR . 'src/bp-events/bp-events-functions.php';
	}
	// Load the list table class (needed by bp_events_admin_page).
	if ( ! class_exists( 'BP_Events_List_Table' ) ) {
		require_once BP_EVENTS_PLUGIN_DIR . 'src/bp-events/classes/class-bp-events-list-table.php';
	}
	// Load admin functions and register the menu.
	if ( ! function_exists( 'bp_events_admin_menu' ) ) {
		require_once BP_EVENTS_PLUGIN_DIR . 'src/bp-events/bp-events-admin.php';
	}
	bp_events_admin_menu();
}, 5 );

/**
 * Add Settings link to plugin action links.
 */
add_filter( 'plugin_action_links_buddyboss-events/buddyboss-events.php', function( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=bp-events' ) . '">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
} );
