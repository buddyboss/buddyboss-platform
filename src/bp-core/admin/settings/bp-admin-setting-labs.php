<?php
/**
 * Add admin Notification settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.9.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main notification settings class.
 *
 * @since BuddyBoss 1.9.3
 */
class BB_Admin_Setting_Labs extends BP_Admin_Setting_tab {

	/**
	 * Initial method for this class.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @return void
	 */
	public function initialize() {
		$this->tab_label = esc_html__( 'Labs', 'buddyboss' );
		$this->tab_name  = 'bp-labs';
		$this->tab_order = 90;
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @return void
	 */
	public function register_fields() {

		$sections = bb_labs_get_settings_sections();

		if ( ! empty( $sections ) ) {
			foreach ( (array) $sections as $section_id => $section ) {

				// Only add section and fields if section has fields.
				$fields = bb_labs_get_settings_fields_for_section( $section_id );

				if ( empty( $fields ) ) {
					continue;
				}

				$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
				$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
				$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;
				$notice            = ! empty( $section['notice'] ) ? $section['notice'] : false;

				// Add the section.
				$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback, $notice );

				// Loop through fields for this section.
				foreach ( (array) $fields as $field_id => $field ) {

					$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

					if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
						$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
						$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
					}
				}
			}
		}

		/**
		 * Fires to register labs tab settings fields and section.
		 *
		 * @since BuddyBoss 1.9.3
		 *
		 * @param Object $this BB_Admin_Setting_Labs.
		 */
		do_action( 'bb_admin_setting_labs_register_fields', $this );
	}
}

// Class instance.
return new BB_Admin_Setting_Labs();
