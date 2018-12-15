<?php

class BP_Admin_Setting_Search extends BP_Admin_Setting_tab {

	public function initialize() {

		$this->tab_label = __( 'Search', 'buddyboss' );
		$this->tab_name  = 'bp-search';
		$this->tab_order = 40;
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
			$this->add_section( $section_id, $section['title'], $section['callback'] );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {
				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$this->add_field( $field_id, $field['title'], $field['callback'], $field['sanitize_callback'], $field['args'] );
				}
			}
		}
	}

}

return new BP_Admin_Setting_Search;
