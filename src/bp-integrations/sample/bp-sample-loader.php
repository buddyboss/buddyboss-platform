<?php
/**
 * BuddyBoss Sample Integration Loader.
 *
 * @package BuddyBoss
 * @subpackage LearnDash
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp sample integration.
 *
 * @since BuddyBoss 3.1.1
 */
function bp_register_sample_integration() {
	require_once dirname( __FILE__ ) . '/admin/bp-admin-sample-tab.php';
}
add_action( 'bp_register_admin_integrations', 'bp_register_sample_integration' );
