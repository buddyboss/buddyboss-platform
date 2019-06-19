<?php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Remove buddypress follow init hook action
 */
remove_action( 'bp_include', 'bp_follow_init' );

/**
 * Remove message of BuddyPress Groups Export & Import
 */
remove_action( 'plugins_loaded', 'bpgei_plugin_init' );