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

		// Main General Settings Section
		$this->add_section( 'bp_main', __( 'General Settings', 'buddyboss' ), '', 'bp_admin_setting_tutorial' );

		// Account Deletion Settings.
		$this->add_field( 'bp-disable-account-deletion', __( 'Account Deletion', 'buddyboss' ), 'bp_admin_setting_callback_account_deletion', 'intval' );

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


		// Main Registration Settings Section
		$this->add_section( 'bp_registration', __( 'Registration', 'buddyboss' ), '', 'bp_admin_registration_setting_tutorial' );

		// Registration Settings.
		$args          = array();
		$args['class'] = '';
		$this->add_field( 'bp-enable-site-registration', __( 'Enable Registration', 'buddyboss' ), 'bp_admin_setting_callback_register', 'intval', $args );

		if ( bp_enable_site_registration() || bp_is_active( 'invites' ) ) {

			$args          = array();
			$args['class'] = bp_enable_site_registration() ? 'child-no-padding-first registration-form-main-select' : 'registration-form-main-select';
			$this->add_field( 'allow-custom-registration', __( 'Registration Form', 'buddyboss' ), 'bp_admin_setting_callback_register_allow_custom_registration', 'intval', $args );

			$args          = array();
			$args['class'] = 'child-no-padding register-legal-agreement-checkbox';
			$this->add_field( 'register-legal-agreement', '', 'bb_admin_setting_callback_register_show_legal_agreement', 'intval', $args );

			$args          = array();
			$args['class'] = 'child-no-padding register-text-box';
			$this->add_field( 'register-page-url', '', 'bp_admin_setting_callback_register_page_url', 'string', $args );

			$args          = array();
			$args['class'] = 'child-no-padding register-email-checkbox';
			$this->add_field( 'register-confirm-email', '', 'bp_admin_setting_callback_register_show_confirm_email', 'intval', $args );

			$args          = array();
			$args['class'] = 'child-no-padding register-password-checkbox';
			$this->add_field( 'register-confirm-password', '', 'bp_admin_setting_callback_register_show_confirm_password', 'intval', $args );

		}

		// Main Privacy Settings Section
		$this->add_section( 'bp_privacy', __( 'Privacy', 'buddyboss' ), '', 'bp_privacy_tutorial' );

		// Private Network Settings.
		$this->add_field( 'bp-enable-private-network', __( 'Private Website', 'buddyboss' ), 'bp_admin_setting_callback_private_network', 'intval' );
		$enable_private_network = bp_enable_private_network();
		if ( ! $enable_private_network ) {
			$this->add_field( 'bp-enable-private-network-public-content', __( 'Public Content', 'buddyboss' ), 'bp_admin_setting_callback_private_network_public_content' );
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
