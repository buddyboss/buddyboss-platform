<?php
/**
 * Main BuddyBoss Admin Integration Tab Class.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Admin_Integration_tab' ) ) :

	class BP_Admin_Integration_tab extends BP_Admin_Tab {
		public $global_tabs_var = 'bp_admin_integration_tabs';
		public $menu_page       = 'bp-integrations';
		public $required_plugin = '';
		public $intro_template  = '';
		public $root_path       = '';
		public $root_url        = '';

		public function __construct() {
			$args = func_get_args();

			if ( isset( $args[0] ) && is_string( $args[0] ) ) {
				$this->tab_name = $args[0];
			}

			if ( isset( $args[1] ) && is_string( $args[1] ) ) {
				$this->tab_label = $args[1];
			}

			if ( isset( $args[2] ) && is_array( $args[2] ) ) {
				foreach ( $args[2] as $key => $value ) {
					$this->$key = $value;
				}
			}

			add_action( 'bp_admin_tab_setting_save', array( $this, 'integration_setting_save' ) );

			parent::__construct();
		}

		public function integration_setting_save( $tab_name ) {
			do_action( "bp_integrations_{$this->tab_name}_setting_saved" );
		}

		public function settings_saved() {
			bp_core_redirect( bp_core_admin_integrations_url( $this->tab_name, array( 'updated' => 'true' ) ) );
		}

		public function get_active_tab() {
			return bp_core_get_admin_integration_active_tab();
		}

		public function is_tab_visible() {
			return true;
		}

		public function is_active() {
			return $this->required_plugin && is_plugin_active( $this->required_plugin );
		}

		public function form_html() {
			if ( $this->required_plugin && ! is_plugin_active( $this->required_plugin ) ) {
				if ( is_file( $this->intro_template ) ) {
					require $this->intro_template;
				}

				return;
			}

			return parent::form_html();
		}
	}


endif;
