<?php
/**
 * Main Buddyboss Admin Tab Class.
 *
 * @package BuddyBoss
 * @subpackage CoreAdministration
 * @since Buddyboss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Admin_Setting_tab' ) ) :

class BP_Admin_Setting_tab {
	protected $tab_name = '';
	protected $tab_slug = '';
	protected $setting_page = 'buddypress';
	protected $section_name = '';
	protected $section_label = '';

	public function __construct() {
		$this->initialize();

		if ( $this->is_active() ) {
			$this->register_section();
			$this->register_fields();
		}
	}

	protected function initialize() {}

	protected function is_active() {
		return true;
	}

	protected function register_section() {
		add_settings_section( $this->section_name, $this->section_label, '__return_null', $this->setting_page );
	}

	protected function register_fields() {}

	protected function add_field($name, $label, $callback, $args = []) {
		$callback = method_exists($this, $callback)? [$this, $callback] : $callback;

		add_settings_field( $name, $label, $callback, $this->setting_page, $this->section_name );
		register_setting( $this->setting_page, $name, $args );
	}

	protected function checkbox($name, $label, $callback) {
		printf('
			<input id="%1$s" name="%1$s" type="checkbox" value="1" %2$s />
			<label for="%1$s">%3$s</label>',
			$name,
			checked( !$callback( false ), true, false ),
			$label
		);
	}
}


endif;
