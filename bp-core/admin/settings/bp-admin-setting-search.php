<?php
/**
 * Add admin Search settings page in Dashboard->BuddyBoss->Settings
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
class BP_Admin_Setting_Search extends BP_Admin_Setting_tab {

	public function initialize() {

		$this->tab_label = __( 'Search', 'buddyboss' );
		$this->tab_name  = 'bp-search';
		$this->tab_order = 90;
	}

	public function is_active() {
		return bp_is_active( 'search' );
	}

	public function register_fields() {
		$sections = bp_search_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields
			$fields = bp_search_get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			// Add the section
			$this->add_section( $section_id, $section['title'], $section['callback'], $section['tutorial_callback'] );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {
				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
					$args              = isset( $field['args'] ) ? $field['args'] : array();
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $args );
				}
			}
		}

		/**
		 * Fires to register Search tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Search.
		 */
		do_action( 'bp_admin_setting_search_register_fields', $this );
	}

}

return new BP_Admin_Setting_Search();
