<?php
/**
 * Add admin Media settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Search Settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Media extends BP_Admin_Setting_tab {

	public function initialize() {

		$this->tab_label = __( 'Media', 'buddyboss' );
		$this->tab_name  = 'bp-media';
		$this->tab_order = 50;
	}

	public function is_active() {
		return bp_is_active( 'media' );
	}

	public function register_fields() {
		$sections = bp_media_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			if ( ( 'bp_media_settings_emoji' === $section_id || 'bp_media_settings_gifs' === $section_id ) && ! bp_is_active( 'activity' ) && ! bp_is_active( 'groups' ) && ! bp_is_active( 'messages' ) && ! bp_is_active( 'forums' ) ) {
				continue;
			}

			// Only add section and fields if section has fields
			$fields = bp_media_get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title    = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback = ! empty( $section['callback'] ) ? $section['callback'] : false;
			$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;

			// Add the section
			$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback );

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
		 * Fires to register Media tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Media.
		 */
		do_action( 'bp_admin_setting_media_register_fields', $this );
	}

}

return new BP_Admin_Setting_Media();
