<?php
/**
 * BuddyPress Members Admin
 *
 * @package BuddyBoss\Members\Admin
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Load the BP Members admin.
add_action( 'bp_init', array( 'BP_Members_Admin', 'register_members_admin' ) );
