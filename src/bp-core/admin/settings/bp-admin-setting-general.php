<?php
/**
 * Add admin General settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main General Settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_General extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'General', 'buddyboss' );
		$this->tab_name  = 'bp-general';
		$this->tab_order = 0;
	}

	public function register_fields() {
		$this->add_section( 'bp_main', __( 'General Settings', 'buddyboss' ) );
		$this->add_field( 'bp-enable-site-registration', __( 'Registrations', 'buddyboss' ), 'bp_admin_setting_callback_register', 'intval' );
		$this->add_field( 'bp-disable-account-deletion', __( 'Account Deletion', 'buddyboss' ), 'bp_admin_setting_callback_account_deletion', 'intval' );
		$args = array();
		$args['class'] = 'child-no-padding-first';
		$this->add_field( 'show-login-adminbar', __( 'Toolbar', 'buddyboss' ), 'bp_admin_setting_callback_login_admin_bar', 'intval', $args );
		$args = array();
		$args['class'] = 'child-no-padding';
		$this->add_field( 'hide-loggedout-adminbar', __( '', 'buddyboss' ), 'bp_admin_setting_callback_admin_bar', 'intval', $args );
		$args = array();
		$this->add_field( 'admin-setting-tutorial', __( '', 'buddyboss' ), 'bp_admin_setting_tutorial', 'intval', $args );
		$this->add_section( 'bp_privacy', __( 'Privacy', 'buddyboss' ) );
		$this->add_field( 'bp-enable-private-network', __( 'Private Network', 'buddyboss' ), 'bp_admin_setting_callback_private_network', 'intval' );
		$enable_private_network = bp_get_option( 'bp-enable-private-network' );
		if ( '0' === $enable_private_network ) {
			$this->add_field( 'bp-enable-private-network-public-content',__( 'Public Content', 'buddyboss' ),'bp_admin_setting_callback_private_network_public_content' );
		}
		$this->add_field( 'bp-privacy-tutorial', __( '', 'buddyboss' ), 'bp_privacy_tutorial', 'intval', $args );
	}
}

return new BP_Admin_Setting_General;
