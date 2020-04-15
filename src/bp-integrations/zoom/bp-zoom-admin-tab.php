<?php
/**
 * Zoom integration admin tab
 *
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Zoom integration admin tab class.
 *
 * @since BuddyBoss 1.2.10
 */
class BP_Zoom_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	/**
	 * Initialize
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function initialize() {
		$this->tab_order       = 50;
		$this->current_section = 'bp_zoom-integration';
	}

	/**
	 * Zoom Integration is active?
	 *
	 * @since BuddyBoss 1.2.10
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bp_zoom_integration_is_active', true );
	}

	/**
	 * Register setting fields for zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function register_fields() {

		$sections = $this->get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields
			$fields = $this->get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title    = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback = ! empty( $section['callback'] ) ? $section['callback'] : false;

			// Add the section
			$this->add_section( $section_id, $section_title, $section_callback );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : [];
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
				}
			}
		}
	}

	/**
	 * Get setting sections for zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @return array $settings Settings sections for zoom integration.
	 */
	public function get_settings_sections() {

		$settings = array(
			'bp_zoom_settings_section' => array(
				'page'  => 'zoom',
				'title' => __( 'Zoom Conference', 'buddyboss' ),
			),
		);

		return $settings;
	}

	/**
	 * Get setting fields for section in zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @return array|false $fields setting fields for section in zoom integration false otherwise.
	 */
	public function get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->get_settings_fields();
		$fields = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

		return $fields;
	}

	/**
	 * Register setting fields for zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @return array $fields setting fields for zoom integration.
	 */
	public function get_settings_fields() {

		$fields = array();

		$fields['bp_zoom_settings_section'] = array(

			'bp-zoom-api-key' => array(
				'title'             => __( 'Zoom API Key', 'buddyboss' ),
				'callback'          => 'bp_zoom_settings_callback_api_key_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bp-zoom-api-secret' => array(
				'title'             => __( 'Zoom API Secret', 'buddyboss' ),
				'callback'          => 'bp_zoom_settings_callback_api_secret_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bp-zoom-api-check-connection' => array(
				'title'    => __( '&#160;', 'buddyboss' ),
				'callback' => 'bp_zoom_api_check_connection_button',
			)

		);

		return $fields;
	}
}
