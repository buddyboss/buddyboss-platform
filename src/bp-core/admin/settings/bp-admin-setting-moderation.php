<?php // phpcs:ignore
/**
 * Add admin Moderation settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.5.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Search Settings class.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Admin_Setting_Moderation extends BP_Admin_Setting_tab {

	/**
	 * Moderation setting initialize.
     *
     * @since BuddyBoss 1.5.6
	 */
	public function initialize() {

		$this->tab_label = __( 'Moderation', 'buddyboss' );
		$this->tab_name  = 'bp-moderation';
		$this->tab_order = 80;
	}

    /**
     * Function to save moderation settings
     *
     * @since BuddyBoss 1.5.6
     */
	public function settings_save() {
		$sections = bp_moderation_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {
			$fields = bp_moderation_get_settings_fields_for_section( $section_id );

			foreach ( (array) $fields as $field_id => $field ) {
				$value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : '';
				if ( is_callable( $field['sanitize_callback'] ) ) {
					$value = $field['sanitize_callback']( $value );
				}

				bp_update_option( $field_id, $value );
			}
		}
	}

	/**
	 * Moderation component is active or not.
     *
     * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	public function is_active() {
		return bp_is_active( 'moderation' );
	}

	/**
	 * Register setting Fields.
     *
     * @since BuddyBoss 1.5.6
	 */
	public function register_fields() {
		$sections = bp_moderation_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = bp_moderation_get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title    = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback = ! empty( $section['callback'] ) ? $section['callback'] : false;
			$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;

			// Add the section.
			$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback );

			// Loop through fields for this section.
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
         * @since BuddyBoss 1.5.6
		 *
		 * @param Object $this BP_Admin_Setting_Moderation.
		 */
		do_action( 'bp_admin_setting_moderation_register_fields', $this );
	}

}

return new BP_Admin_Setting_Moderation();
