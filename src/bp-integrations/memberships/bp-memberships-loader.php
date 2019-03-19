<?php
/**
 * BuddyBoss Memberships Integration Loader.
 *
 * @package BuddyBoss\Memberships
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Set up the bp memberships integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_memberships_integration() {
	require_once dirname(__FILE__) . '/bp-memberships-integration.php';
	buddypress()->integrations['memberships'] = new BP_Memberships_Integration;
}
add_action('bp_setup_integrations', 'bp_register_memberships_integration');
