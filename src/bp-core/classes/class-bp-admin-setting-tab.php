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
	public $tab_name         = '';
	public $tab_slug         = '';
	public $tab_order        = 10;
	public $tab_description  = '';
	public $setting_page     = 'bp-settings';
	public $section_name     = '';
	public $section_label    = '';
	public $section_callback = '__return_null';

	public function __construct() {
		$this->initialize();

		if ( $this->is_active() ) {
			$this->register_setting_tab();
			$this->register_section();
			$this->register_fields();

			add_action( 'bp_admin_init', [$this, 'maybe_save_admin_settings'], 100 );
		}
	}

	public function maybe_save_admin_settings() {
		if ( ! $this->is_saving() ) {
  			return false;
		}

		check_admin_referer( $this->tab_slug . '-options' );
		$this->settings_save();
		$this->settings_saved();
	}

	public function show_tab() {
		return $this->has_fields();
	}

	public function has_fields() {
		global $wp_settings_fields;

		return ! empty($wp_settings_fields[ $this->tab_slug ]);
	}

	protected function settings_save() {
		global $wp_settings_fields;

		$fields = isset( $wp_settings_fields[ $this->tab_slug ] )? (array) $wp_settings_fields[ $this->tab_slug ] : [];
		$legacy_names = [];

		foreach( $fields as $section => $settings ) {
			foreach( $settings as $setting_name => $setting ) {
				$value = isset( $_POST[$setting_name] ) ? $_POST[$setting_name] : '';
				bp_update_option( $setting_name, $value );
				$legacy_names[] = $setting_name;
			}
		}

		// Some legacy options are not registered with the Settings API, or are reversed in the UI.
		$legacy_options = array(
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

		$legacy_options = array_intersect($legacy_options, $legacy_names);

		foreach( $legacy_options as $legacy_option ) {
			// Note: Each of these options is represented by its opposite in the UI
			// Ie, the Profile Syncing option reads "Enable Sync", so when it's checked,
			// the corresponding option should be unset.
			$value = isset( $_POST[$legacy_option] ) ? '' : 1;
			bp_update_option( $legacy_option, $value );
		}

	}

	public function settings_saved() {
		bp_core_redirect( add_query_arg( array( 'page' => 'bp-settings', 'updated' => 'true', 'tab' => $this->tab_slug ), bp_get_admin_url( 'admin.php' ) ) );
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

	public function section_output()
	{
		if ($this->tab_description) {
			echo wpautop($this->tab_description);
		}
		call_user_func($this->section_callback);
	}

	protected function is_saving() {
		if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
			return false;
		}

		if ( $this->setting_page != $_GET['page'] ) {
			return false;
		}

		if ( $this->tab_slug != bp_core_get_admin_active_tab() ) {
			return false;
		}

		return true;
	}

	protected function initialize() {}

	protected function is_active() {
		return true;
	}

	protected function register_setting_tab() {
		global $bp_admin_setting_tabs;

		$bp_admin_setting_tabs[$this->tab_slug] = $this;
	}

	protected function register_section() {
		add_settings_section( $this->section_name, $this->section_label, [$this, 'section_output'], $this->tab_slug );
	}

	protected function register_fields() {}

	protected function add_field($name, $label, $callback, $type = [], $args = []) {
		$callback = method_exists($this, $callback)? [$this, $callback] : $callback;

		add_settings_field( $name, $label, $callback, $this->tab_slug, $this->section_name, $args );
		register_setting( $this->tab_slug, $name, $type );
	}

	protected function checkbox($name, $label, $callback, $reversed = true, $default = false) {
		$is_checked = $callback( $default );

		if ($reversed) {
			$is_checked = ! $is_checked;
		}

		printf('
			<input id="%1$s" name="%1$s" type="checkbox" value="1" %2$s />
			<label for="%1$s">%3$s</label>',
			$name,
			checked( $is_checked, true, false ),
			$label
		);
	}
}


endif;
