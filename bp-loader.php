<?php

/**
 * The BuddyBoss Platform
 *
 * BuddyBoss Platform adds community features to WordPress.
 *
 * @package BuddyPress
 * @subpackage Main
 */

/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     1.0.0
 * Text Domain: buddyboss
 * Domain Path: /bp-languages/
 * License:     GPLv2 or later (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Assume you want to load from build
$bp_loader = dirname( __FILE__ ) . '/build/bp-loader.php';

// Load from source if no build exists
if ( ! file_exists( $bp_loader ) || defined( 'BP_LOAD_SOURCE' ) ) {
	$bp_loader = dirname( __FILE__ ) . '/src/bp-loader.php';
	$subdir = 'src';
} else {
	$subdir = 'build';
}

// Include BuddyPress
include( $bp_loader );

// Unset the loader, since it's loaded in global scope
unset( $bp_loader );
