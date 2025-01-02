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


		// Main Registration Settings Section.
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

		// SSO.
		$sso_pro_class      = bb_get_pro_fields_class( 'sso' );
		$sso_notice         = bb_get_pro_label_notice( 'sso' );
		$sso_args           = array();
		$sso_args['class']  = esc_attr( $sso_pro_class );
		$sso_args['notice'] = $sso_notice;
		$this->add_field(
			'bb-enable-sso',
			__( 'Enable Social Login', 'buddyboss' ) . $sso_notice,
			array(
				$this,
				'bb_admin_setting_callback_enable_sso_registration',
			),
			'intval',
			$sso_args
		);

		/**
		 * Fires to register SSO settings fields.
		 *
		 * @since BuddyBoss 2.7.40
		 *
		 * @param Object $this BP_Admin_Setting_General.
		 */
		do_action( 'bb_admin_setting_general_registration_fields', $this );

		// Redirection Settings Section.
		$this->add_section( 'bb_redirection', __( 'Redirection', 'buddyboss' ), '', 'bb_admin_redirection_setting_tutorial' );

		$this->add_field( 'bb-login-redirection', __( 'After Login', 'buddyboss' ), 'bb_admin_setting_callback_login_redirection', 'string' );
		$args          = array();
		$args['class'] = 'child-no-padding login-redirection-text-box';
		$this->add_field( 'bb-custom-login-redirection', '', 'bp_admin_setting_callback_custom_login_redirection', 'string', $args );

		$this->add_field( 'bb-logout-redirection', __( 'After Logout', 'buddyboss' ), 'bb_admin_setting_callback_logout_redirection', 'string' );
		$args          = array();
		$args['class'] = 'child-no-padding logout-redirection-text-box';
		$this->add_field( 'bb-custom-logout-redirection', '', 'bp_admin_setting_callback_custom_logout_redirection', 'string', $args );

		// Email domain restriction section.
		$this->add_section( 'bb_registration_restrictions', __( 'Registration Restrictions', 'buddyboss' ), 'bb_admin_setting_callback_registration_restrictions_instructions', 'bb_registration_restrictions_tutorial' );

		// Blacklist email settings.
		$args          = array();
		$args['class'] = ( bp_allow_custom_registration() ) ? 'bb-inactive-field' : '';
		$this->add_field( 'bb-domain-restrictions', __( 'Domain Restrictions', 'buddyboss' ) . bb_get_buddyboss_registration_notice(), 'bb_admin_setting_callback_domain_restrictions', '', $args );

		// Whitelist email settings.
		$args          = array();
		$args['class'] = ( bp_allow_custom_registration() ) ? 'bb-inactive-field' : '';
		$this->add_field( 'bb-email-restrictions', __( 'Email Restrictions', 'buddyboss' ) . bb_get_buddyboss_registration_notice(), 'bb_admin_setting_callback_email_restrictions', '', $args );

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

	/**
	 * Add SSO settings.
	 *
	 * @since BuddyBoss 2.7.40
	 */
	public function bb_admin_setting_callback_enable_sso_registration( $args ) {
		$val    = function_exists( 'bb_enable_sso' ) && bb_enable_sso();
		$notice = ! empty( $args['notice'] ) ? $args['notice'] : '';
		?>
		<input id="bb_enable_sso" name="<?php echo empty( $notice ) ? 'bb-enable-sso' : ''; ?>" type="checkbox" value="1" <?php echo empty( $notice ) ? checked( $val, true, false ) : ''; ?> />
		<label for="bb_enable_sso"><?php esc_html_e( 'Allow users to sign in with social login', 'buddyboss' ); ?></label>
		<?php
	}

}

return new BP_Admin_Setting_General();
