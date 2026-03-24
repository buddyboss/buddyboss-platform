<?php
/**
 * Broadcast Uninstall — runs when admin deletes the plugin via WP admin.
 *
 * Drops all custom tables and removes all plugin options.
 * This file is included by broadcast_uninstall() in broadcast.php,
 * which is registered with register_uninstall_hook().
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop custom tables — IF EXISTS prevents errors on partial installs.
// Order matters: drop tables that reference announcement IDs first.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}broadcast_targeting_rules" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}broadcast_analytics_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}broadcast_announcements" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}broadcast_user_dismissals" );

// Delete plugin options.
delete_option( 'broadcast_db_version' );
delete_option( 'broadcast_email_settings' );
delete_option( 'broadcast_email_overrides' );
