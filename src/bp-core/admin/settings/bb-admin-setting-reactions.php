<?php
/**
 * Add admin Reactions settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Reactions Settings class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Setting_Reactions extends BP_Admin_Setting_tab {

	/**
	 * Initializes the function.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->tab_label = __( 'Reactions', 'buddyboss' );
		$this->tab_name  = 'bp-reactions';
		$this->tab_order = 51;
	}

	/**
	 * Settings saved.
	 */
	public function settings_saved() {
		$action_type   = bb_pro_filter_input_string( INPUT_POST, 'action_type' );
		$is_migration  = bb_pro_filter_input_string( INPUT_POST, 'is_migration' );
		$redirect_args = array(
			'updated' => 'true'
		);

		if ( ! empty( $action_type ) ) {
			$redirect_args['action_type'] = $action_type;

			if ( 'yes' === $is_migration ) {
				$redirect_args['step'] = 'status';
			}
		}

		bp_core_redirect(
			bp_core_admin_setting_url(
				$this->tab_name,
				$redirect_args
			)
		);
	}

	/**
	 * Registers the fields for the reaction settings sections.
	 *
	 * @return void
	 */
	public function register_fields() {
		$sections = bb_reactions_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			if ( ! bp_is_active( 'activity' ) ) {
				continue;
			}

			// Only add section and fields if section has fields.
			$fields = bb_reactions_get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
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
		 * Fires to register "reactions" tab settings fields and section.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param Object $this BB_Admin_Setting_Reactions.
		 */
		do_action( 'bb_admin_setting_reactions_register_fields', $this );
	}

}

return new BB_Admin_Setting_Reactions();
