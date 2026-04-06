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

		// Main General Settings Section.
		$this->add_section( 'bp_main', __( 'General Settings', 'buddyboss' ), '', 'bp_admin_setting_tutorial' );

		// Toolbar Settings.
		$args          = array();
		$args['class'] = 'child-no-padding-first';
		$this->add_field( 'show-admin-adminbar', __( 'Toolbar', 'buddyboss' ), 'bp_admin_setting_callback_admin_admin_bar', 'intval', $args );
		$args          = array();
		$args['class'] = 'child-no-padding';
		$this->add_field( 'show-login-adminbar', '', 'bp_admin_setting_callback_login_admin_bar', 'intval', $args );
		$args          = array();
		$args['class'] = 'child-no-padding';
		$this->add_field( 'hide-loggedout-adminbar', '', 'bp_admin_setting_callback_admin_bar', 'intval', $args );
		$args = array();

		// Content Count Settings.
		$this->add_field( 'bb-enable-content-counts', __( 'Content Counts', 'buddyboss' ), 'bb_admin_setting_callback_content_counts', 'intval' );

		// Main Privacy Settings Section.
		$this->add_section( 'bp_privacy', __( 'Privacy', 'buddyboss' ), '', 'bp_privacy_tutorial' );

		// Private Network Settings.
		$this->add_field( 'bp-enable-private-network', __( 'Private Website', 'buddyboss' ), 'bp_admin_setting_callback_private_network', 'intval' );
		$enable_private_network = bp_enable_private_network();
		if ( ! $enable_private_network ) {
			$this->add_field( 'bp-enable-private-network-public-content', __( 'Public Website Content', 'buddyboss' ), 'bp_admin_setting_callback_private_network_public_content' );
		}

		// Private REST APIs Settings.
		$this->add_field( 'bb-enable-private-rest-apis', esc_html__( 'Private REST APIs', 'buddyboss' ), 'bb_admin_setting_callback_private_rest_apis', 'intval' );
		if (
			(
				true === bp_enable_private_rest_apis() &&
				function_exists( 'bbapp_is_private_app_enabled' ) &&
				true === bbapp_is_private_app_enabled()
			) ||
			(
				! function_exists( 'bbapp_is_private_app_enabled' ) &&
				true === bp_enable_private_rest_apis()
			)
		) {
			$this->add_field( 'bb-enable-private-rest-apis-public-content', __( 'Public REST APIs', 'buddyboss' ), 'bb_admin_setting_callback_private_rest_apis_public_content', 'stripslashes' );
		}

		// Private RSS Feeds Settings.
		$this->add_field( 'bb-enable-private-rss-feeds', esc_html__( 'Private RSS Feeds', 'buddyboss' ), 'bb_admin_setting_callback_private_rss_feeds', 'intval' );
		if ( true === bp_enable_private_rss_feeds() ) {
			$this->add_field( 'bb-enable-private-rss-feeds-public-content', __( 'Public RSS Feeds', 'buddyboss' ), 'bb_admin_setting_callback_private_rss_feeds_public_content', 'stripslashes' );
		}

		/**
		 * For Backward compatibility
		 */
		// Add the Main Settings.
		add_settings_section( 'bp_main', __( 'Main Settings', 'buddyboss' ), '__return_null', 'buddypress' );

		// Add the Profile Settings.
		add_settings_section( 'bp_xprofile', _x( 'Profile Settings', 'BuddyPress setting tab', 'buddyboss' ), '__return_null', 'buddypress' );

		// Add the Groups Settings.
		add_settings_section( 'bp_groups', __( 'Groups Settings', 'buddyboss' ), '__return_null', 'buddypress' );

		// Add the Activity Settings.
		add_settings_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ), '__return_null', 'buddypress' );

		/**
		 * Fires to register General tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_General.
		 */
		do_action( 'bp_admin_setting_general_register_fields', $this );
	}

}

return new BP_Admin_Setting_General();
