<?php
/**
 * Add admin Video settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.5.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Search Settings class.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Admin_Setting_Video extends BP_Admin_Setting_tab {

	/**
	 * Initialize.
	 */
	public function initialize() {
		$this->tab_label = __( 'Video', 'buddyboss' );
		$this->tab_name  = 'bp-video';
		$this->tab_order = 50;
	}

	/**
	 * Whether active or not.
	 *
	 * @return bool is active or not.
	 */
	public function is_active() {
		return bp_is_active( 'media' );
	}

	/**
	 * Whether visible or not.
	 *
	 * @return bool is active or not.
	 */
	public function is_tab_visible() {
		return false;
	}

	/**
	 * Register fields.
	 */
	public function register_fields() {
		$sections = bp_video_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = bp_video_get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title    = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback = ! empty( $section['callback'] ) ? $section['callback'] : false;

			// Add the section.
			$this->add_section( $section_id, $section_title, $section_callback );

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
		 * Fires to register Video tab settings fields and section.
		 *
		 * @since BuddyBoss 1.7.0id="" class="bp-hello-mime" role="dialog" aria-labelledby="bp-hello-title"
		 *
		 * @param Object $this BP_Admin_Setting_Media.
		 */
		do_action( 'bp_admin_setting_video_register_fields', $this );
	}

}

return new BP_Admin_Setting_Video();
