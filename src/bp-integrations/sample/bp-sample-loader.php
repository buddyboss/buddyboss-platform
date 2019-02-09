<?php
/**
 * BuddyBoss Sample Integration Loader.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp sample integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_sample_integration() {
	require_once dirname( __FILE__ ) . '/admin/bp-admin-sample-tab.php';
	buddypress()->integrations['sample'] = new BP_Admin_Integration_Sample;
}
add_action( 'bp_register_admin_integrations', 'bp_register_sample_integration' );
