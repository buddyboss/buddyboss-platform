<?php
/**
 * Add admin Forums settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Forum Settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Forums extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Forums', 'buddyboss' );
		$this->tab_name  = 'bp-forums';
		$this->tab_order = 30;
	}

	public function is_active() {
		return bp_is_active( 'forums' );
	}

	public function settings_save() {
		$sections = bbp_admin_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {
			$fields = bbp_admin_get_settings_fields_for_section( $section_id );

			foreach ( (array) $fields as $field_id => $field ) {
				$value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : '';

				if ( is_callable( $field['sanitize_callback'] ) ) {
					$value = $field['sanitize_callback']($value);
				}

				bp_update_option( $field_id, $value );
			}
		}

		flush_rewrite_rules();
	}

	public function settings_saved() {

		$url = bp_core_admin_setting_url( $this->tab_name, array( 'edited' => 'true' ) );
		bp_core_redirect( $url );

	}

	public function register_fields() {
		$sections = bbp_admin_get_settings_sections();
		$fields   = bbp_admin_get_settings_fields();

		foreach ( (array) $sections as $section_id => $section ) {
			// Only proceed if current user can see this section
			if ( ! current_user_can( $section_id ) ) {
				continue;
			}

			// Only add section and fields if section has fields
			$fields = bbp_admin_get_settings_fields_for_section( $section_id );
			if ( empty( $fields ) ) {
				continue;
			}

			// Add the section
			$this->add_section(
				$section_id,
				isset( $section['title'] ) ? $section['title'] : '',
				isset( $section['callback'] ) ? $section['callback'] : ''
			);

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {
				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$this->add_field( $field_id, $field['title'], $field['callback'], $field['sanitize_callback'], $field['args'] );
				}
			}
		}

		/**
		 * Fires to register Forums tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Forums.
		 */
		do_action( 'bp_admin_setting_forums_register_fields', $this );
	}
}

return new BP_Admin_Setting_Forums();
