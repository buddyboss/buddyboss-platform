<?php
/**
 * Add admin Performance settings page in Dashboard->BuddyBoss->Performance
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.1.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main General Settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Performance extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Performance', 'buddyboss' );
		$this->tab_name  = 'bp-performance';
		$this->tab_order = 0;
	}

	public function register_fields() {

		// Performance settings Section
		$this->add_section( 'bp_performance', __( 'Performance', 'buddyboss' ) );

		// Caching Settings.
		$args = array();
		$this->add_field( 'bp-enable-caching', __( 'Database / PHP Cache', 'buddyboss' ), 'bp_admin_setting_caching_callback', 'intval', $args );

		$this->add_field( 'bp-caching-method', __( 'Caching method', 'buddyboss' ), '__return_true', 'intval', [
			'class' => 'hidden'
		] );

		// Flush cache
		$this->add_field( 'bp-flush-cache','', 'bp_performance_flush_cache_callback' );
	}
}

return new BP_Admin_Setting_Performance;
