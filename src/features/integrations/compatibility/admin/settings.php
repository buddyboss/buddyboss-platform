<?php
/**
 * Compatibility integration admin tab
 *
 * @since BuddyBoss 1.1.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Compatibility integration admin tab class.
 *
 * @since BuddyBoss 1.1.5
 */
class BP_Compatibility_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	public function initialize() {
		$this->tab_order       = 20;
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';
		$this->current_section = 'bp_compatibility-integration';
	}

	public function form_html() {
		$this->intro_template = $this->root_path . '/templates/admin/integration-tab-intro.php';
		if ( is_file( $this->intro_template ) ) {
			require $this->intro_template;
		}
	}
}
