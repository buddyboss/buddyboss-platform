<?php

class BP_Admin_Setting_Forums extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Forums', 'buddyboss' );
		$this->tab_name  = 'bp-forums';
		$this->tab_order = 30;
	}

	public function is_active() {
		return bp_is_active( 'forums' );
	}

	public function register_fields() {
		$sections = bbp_admin_get_settings_sections();
		$fields = bbp_admin_get_settings_fields();

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
			$this->add_section( $section_id, $section['title'], $section['callback'] );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {
				if ( ! empty( $field['callback'] ) && !empty( $field['title'] ) ) {
					$this->add_field( $field_id, $field['title'], $field['callback'], $field['sanitize_callback'], $field['args'] );
				}
			}
		}
	}
}

return new BP_Admin_Setting_Forums;
