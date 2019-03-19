<?php
/**
 * BuddyBoss WooCommerce Integration Loader.
 *
 * @package BuddyBoss\WooCommerce
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Set up the bp woocommerce integration.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_register_woocommerce_integration() {
	require_once dirname(__FILE__) . '/bp-woocommerce-integration.php';
	buddypress()->integrations['woocommerce'] = new BP_Woocommerce_Integration;
}
add_action('bp_setup_integrations', 'bp_register_woocommerce_integration');
