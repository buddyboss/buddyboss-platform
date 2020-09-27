<?php
/**
 * Add admin Moderation settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Search Settings class.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Admin_Setting_Moderation extends BP_Admin_Setting_tab {

	public function initialize() {

		$this->tab_label = __( 'Moderation', 'buddyboss' );
		$this->tab_name  = 'bp-moderation';
		$this->tab_order = 90;
	}

	public function is_active() {
		return bp_is_active( 'moderation' );
	}

	public function register_fields() {
		$sections = bp_moderation_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields
			$fields = bp_moderation_get_settings_fields_for_section( $section_id );

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
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
				}
			}
		}

		/**
		 * Fires to register Moderation tab settings fields and section.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param Object $this BP_Admin_Setting_Moderation.
		 */
		do_action( 'bp_admin_setting_moderation_register_fields', $this );
	}

}

return new BP_Admin_Setting_Moderation();
