<?php
/**
 * WooCommerce integration helpers
 *
 * @package BuddyBoss\Woocommerce
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * Get the WooCommerce settings sections.
 */
function bp_admin_get_settings_sections() {
	return (array) apply_filters('bp_admin_get_settings_sections', array(
		'bp-woocommerce-section' => array(
			'title' => __('General Settings ', 'buddyboss'), //Title
		),
	));
}
