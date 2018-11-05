<?php
/**
 * BuddyBoss Memberpress Loader.
 *
 * @package BuddyBoss
 * @subpackage Memberpress
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp memberpress integration.
 *
 * @since Buddyboss 3.1.1
 */
function bp_register_memberpress_integration() {
	require_once dirname( __FILE__ ) . '/admin/bp-admin-memberpress-tab.php';
}
add_action( 'bp_register_admin_integrations', 'bp_register_memberpress_integration' );
