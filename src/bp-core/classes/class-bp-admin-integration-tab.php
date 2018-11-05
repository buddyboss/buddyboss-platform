<?php
/**
 * Main Buddyboss Admin Integration Tab Class.
 *
 * @package BuddyBoss
 * @subpackage CoreAdministration
 * @since Buddyboss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Admin_Integration_tab' ) ) :

class BP_Admin_Integration_tab extends BP_Admin_Tab {
	public $global_tabs_var = 'bp_admin_integration_tabs';
	public $menu_page       = 'bp-integrations';
	public $required_plugin = '';
	public $intro_template  = '';

	public function settings_saved() {
		bp_core_redirect( bp_core_admin_integrations_url( $this->tab_name, [ 'updated' => 'true' ] ) );
	}

	public function get_active_tab() {
		return bp_core_get_admin_integration_active_tab();
	}

	public function is_tab_visible() {
		return true;
	}

	public function form_html() {
		if ( $this->required_plugin && ! is_plugin_active( $this->required_plugin ) ) {
			if ( is_file ( $this->intro_template ) ) {
				require $this->intro_template;
			}

			return;
		}

		return parent::form_html();
	}
}


endif;
