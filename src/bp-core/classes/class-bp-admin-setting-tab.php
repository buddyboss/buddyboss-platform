<?php
/**
 * Main BuddyBoss Admin Tab Class.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Admin_Setting_tab' ) ) :

	#[\AllowDynamicProperties]
	class BP_Admin_Setting_tab extends BP_Admin_Tab {
		public $global_tabs_var = 'bp_admin_setting_tabs';
		public $menu_page       = 'bp-settings';

		public function settings_save() {
			parent::settings_save();
			$this->settings_save_lagecy();
		}

		public function settings_saved() {
			bp_core_redirect( bp_core_admin_setting_url( $this->tab_name, array( 'updated' => 'true' ) ) );
		}

		public function get_active_tab() {
			return bp_core_get_admin_active_tab();
		}

		public function is_tab_visible() {
			return $this->has_fields();
		}

		protected function settings_save_lagecy() {
			global $wp_settings_fields;

			$fields       = isset( $wp_settings_fields[ $this->tab_name ] ) ? (array) $wp_settings_fields[ $this->tab_name ] : array();
			$legacy_names = array();

			foreach ( $fields as $section => $settings ) {
				foreach ( $settings as $setting_name => $setting ) {
					$legacy_names[] = $setting_name;
				}
			}

			// Some legacy options are not registered with the Settings API, or are reversed in the UI.
			$legacy_options = array(
				'bp-enable-private-network',
				'bp-disable-account-deletion',
				'bp-disable-avatar-uploads',
				'bp-disable-cover-image-uploads',
				'bp-disable-group-avatar-uploads',
				'bp-disable-group-cover-image-uploads',
				'bp_disable_blogforum_comments',
				'bp-disable-profile-sync',
				'bp_restrict_group_creation',
				'hide-loggedout-adminbar',
			);

			$legacy_options = array_intersect( $legacy_options, $legacy_names );

			foreach ( $legacy_options as $legacy_option ) {
				// Note: Each of these options is represented by its opposite in the UI
				// Ie, the Profile Syncing option reads "Enable Sync", so when it's checked,
				// the corresponding option should be unset.
				$value = isset( $_POST[ $legacy_option ] ) ? '' : 1;
				bp_update_option( $legacy_option, $value );
			}
		}
	}


endif;
