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
	protected $tab_description = '';
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

	public function bp_admin_setting_callback_page_directory_dropdown($args) {
		extract($args);

		if ( ! bp_is_root_blog() ) switch_to_blog( bp_get_root_blog_id() );

		echo wp_dropdown_pages( array(
			'name'             => 'bp_pages[' . esc_attr( $name ) . ']',
			'echo'             => false,
			'show_option_none' => __( '- None -', 'buddyboss' ),
			'selected'         => !empty( $existing_pages[$name] ) ? $existing_pages[$name] : false
		) );

		if ( !empty( $existing_pages[$name] ) ) :

			printf(
				'<a href="%s" class="button-secondary" target="_bp">%s</a>',
				get_permalink( $existing_pages[$name] ),
				__( 'View', 'buddyboss' )
			);
		endif;

		if ( ! bp_is_root_blog() ) restore_current_blog();
	}

	protected function initialize() {}

	protected function is_active() {
		return true;
	}

	protected function register_section() {
		add_settings_section( $this->section_name, $this->section_label, '__return_null', $this->setting_page );
	}

	protected function register_fields() {}

	protected function add_field($name, $label, $callback, $type = [], $args = []) {
		$callback = method_exists($this, $callback)? [$this, $callback] : $callback;

		add_settings_field( $name, $label, $callback, $this->setting_page, $this->section_name, $args );
		register_setting( $this->setting_page, $name, $type );
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
