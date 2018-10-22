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

if ( !class_exists( 'BP_Admin_Tab' ) ) :

abstract class BP_Admin_Tab {

	/**
	 * Global variable name that store the tab instances
	 *
	 * @since  buddyboss 3.1.1
	 * @var string
	 */
	public $global_tabs_var  = '';

	/**
	 * Admin menu page name
	 *
	 * @since  buddyboss 3.1.1
	 * @var string
	 */
	public $menu_page = '';

	/**
	 * Tab label name
	 *
	 * @since  buddyboss 3.1.1
	 * @var string
	 */
	public $tab_label         = '';

	/**
	 * Tab url slug
	 *
	 * @since  buddyboss 3.1.1
	 * @var string
	 */
	public $tab_name         = '';

	/**
	 * Tab order
	 *
	 * @since  buddyboss 3.1.1
	 * @var integer
	 */
	public $tab_order        = 50;

	public function __construct() {
		$this->initialize();
		$this->register_tab();

		if ( $this->is_active() ) {
			$this->register_fields();
			add_action( 'bp_admin_init', [$this, 'maybe_save_admin_settings'], 100 );
		}
	}

	/**
	 * Cutom class initialization
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function initialize() {
		// nothing
	}

	/**
	 * Determine whether this tab is active
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Register the tab to global variable
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function register_tab() {
		global ${$this->global_tabs_var};

		${$this->global_tabs_var}[$this->tab_name] = $this;
	}

	/**
	 * Register setting fields belong to this group
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function register_fields() {
		// nothing
	}

	/**
	 * Save the fields if it's form post request
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function maybe_save_admin_settings() {
		if ( ! $this->is_saving() ) {
  			return false;
		}

		check_admin_referer( $this->tab_name . '-options' );
		$this->settings_save();
		$this->settings_saved();
	}

	/**
	 * Determine whether current request is saving on the current tab
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function is_saving() {
		if ( ! isset( $_GET['page'] ) || ! isset( $_POST['submit'] ) ) {
			return false;
		}

		if ( $this->menu_page != $_GET['page'] ) {
			return false;
		}

		if ( $this->tab_name != $this->get_active_tab() ) {
			return false;
		}

		return true;
	}

	/**
	 * Method to save the fields
	 *
	 * By default it'll loop throught the setting group's fields, but allow
	 * extended classes to have their own logic if needed
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function settings_save() {
		global $wp_settings_fields;

		$fields = isset( $wp_settings_fields[ $this->tab_name ] )? (array) $wp_settings_fields[ $this->tab_name ] : [];

		foreach( $fields as $section => $settings ) {
			foreach( $settings as $setting_name => $setting ) {
				$value = isset( $_POST[$setting_name] ) ? $_POST[$setting_name] : '';
				bp_update_option( $setting_name, $value );
			}
		}
	}

	/**
	 * Method trigger after data are saved
	 *
	 * @since  buddyboss 3.1.1
	 */
	abstract public function settings_saved();

	/**
	 * Method that should return the current active tab
	 *
	 * @since  buddyboss 3.1.1
	 */
	abstract public function get_active_tab();

	/**
	 * Return if the tab should be visible. Default to if there's any setting fields
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function is_tab_visible() {
		return $this->has_fields();
	}

	/**
	 * Return if this tab has setting fields
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function has_fields() {
		global $wp_settings_fields;

		return ! empty( $wp_settings_fields[ $this->tab_name ] );
	}

	/**
	 * Output the form html on the setting page (not including tab and page title)
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function form_html() {
		settings_fields( $this->tab_name );
		do_settings_sections( $this->tab_name );

		printf(
			'<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="%s" />
			</p>',
			esc_attr( 'Save Settings', 'buddyboss' )
		);
	}

	/**
	 * Add a wp setting section into current tab. Chainable
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function add_section( $id, $title, $callback = '__return_null' ) {
		add_settings_section( $id, $title, $callback, $this->tab_name );
		$this->active_section = $id;

		return $this;
	}

	/**
	 * Add a wp setting field to a wp setting section. Chainable
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function add_field( $name, $label, $callback, $field_args = [], $callback_args = [], $id = null ) {
		if ( !$id ) {
			$id = $this->active_section;
		}

		add_settings_field( $name, $label, $callback, $this->tab_name, $id, $callback_args );
		register_setting( $this->tab_name, $name, $field_args );

		return $this;
	}

	/**
	 * Alias to add input text box field
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function add_input_field( $name, $label, $callback_args = [], $field_args = 'sanitize_text_field', $id = null ) {
		$callback = [$this, 'render_input_field_html'];

		$callback_args = wp_parse_args( $callback_args, [
			'input_type'        => 'text',
			'input_name'        => $name,
			'input_id'          => $name,
			'input_description' => '',
			'input_value'       => bp_get_option($name),
			'input_placeholder' => ''
		] );

		return $this->add_field( $name, $label, $callback, $field_args, $callback_args, $id );
	}

	/**
	 * Alias to add input check box field
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function add_checkbox_field( $name, $label, $callback_args = [], $field_args = 'intval', $id = null ) {
		$callback = [$this, 'render_checkbox_field_html'];

		$callback_args = wp_parse_args( $callback_args, [
			'input_name'        => $name,
			'input_id'          => $name,
			'input_text'        => '',
			'input_description' => '',
			'input_value'       => bp_get_option($name, null),
			'input_default'     => 0,
		] );

		return $this->add_field( $name, $label, $callback, $field_args, $callback_args, $id );
	}

	/**
	 * Output the input field html based on the arguments
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function render_input_field_html( $args ) {
		printf(
			'<input name="%s" type="%s" id="%s" value="%s" placeholder="%s" class="regular-text" /> %s',
			$args['input_name'],
			$args['input_type'],
			$args['input_id'],
			$args['input_value'],
			$args['input_placeholder'],
			$args['input_description']? "<p class=\"description\">{$args['input_description']}</p>" : ''
		);
	}

	/**
	 * Output the checkbox field html based on the arguments
	 *
	 * @since  buddyboss 3.1.1
	 */
	public function render_checkbox_field_html( $args ) {
		$input_value = is_null( $args['input_value'] )? $args['input_default'] : $args['input_value'];

		printf(
			'
				<input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s />
				<label for="%1$s">%4$s</label>
				%5$s
			',
			$args['input_id'],
			$args['input_name'],
			checked( (bool) $input_value, true, false ),
			$args['input_text'],
			$args['input_description']? "<p class=\"description\">{$args['input_description']}</p>" : ''
		);
	}
}

endif;
