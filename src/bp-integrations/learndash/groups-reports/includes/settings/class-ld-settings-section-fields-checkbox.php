<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// check if class exists or not.
if ( ( class_exists( 'LearnDash_Settings_Section_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Fields_BB_Checkbox' ) ) ) {
	class LearnDash_Settings_Section_Fields_BB_Checkbox extends LearnDash_Settings_Section_Fields {

		function __construct() {

			$this->field_type = 'mulpital_checkbox_checked';

			parent::__construct();
		}

		function create_section_field( $field_args = array() ) {

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				$html = '';

				if ( ( isset( $field_args['desc'] ) ) && ( ! empty( $field_args['desc'] ) ) ) {
					$html .= $field_args['desc'];
				}

				$html .= '<fieldset>';
				$html .= '<legend class="screen-reader-text">';
				$html .= '<span>' . $field_args['label'] . '</span>';
				$html .= '</legend>';

				foreach ( $field_args['options'] as $option_key => $option_label ) {
					$html .= ' <label for="' . $field_args['id'] . '-' . $option_key . '" >';
					$html .= '<input ';

					$html .= $this->get_field_attribute_type( array( 'type' => 'checkbox' ) );
					$html .= $this->get_field_attribute_id( $field_args );
					$html .= $this->get_field_attribute_name( $field_args );
					$html .= $this->get_field_attribute_class( $field_args );
					$html .= $this->get_field_attribute_misc( $field_args );
					$html .= $this->get_field_attribute_required( $field_args );

					if ( isset( $field_args['value'] ) ) {
						$html .= ' value="' . $option_key . '" ';
					} else {
						$html .= ' value="" ';
					}

					if ( is_array( $field_args['value'] ) ) {
						if ( in_array( $option_key, $field_args['value'] ) ) {
							$html .= ' checked ';
						}
					} else {
						$html .= ' ' . checked( $option_key, $field_args['value'], false ) . ' ';
					}


					$html .= ' />';

					$html .= $option_label . '</label>';
					$html .= '</br>';
				}
				$html .= '</fieldset>';
			}

			echo $html;
		}

		function get_field_attribute_name( $field_args = array() ) {
			$field_attribute = '';

			if ( isset( $field_args['name'] ) ) {
				if ( ! empty( $field_args['setting_option_key'] ) ) {
					$field_attribute .= ' name="' . $field_args['setting_option_key'] . '[' . $field_args['name'] . '][]" ';
				} else {
					$field_attribute .= ' name="' . $field_args['name'] . '" ';
				}
			}

			return $field_attribute;
		}

		// Default validation function. Should be overriden in Field subclass
		function validate_section_field( $val, $key, $args = array() ) {
			return $val;

		}
	}
}
add_action( 'learndash_settings_sections_fields_init', function () {
	LearnDash_Settings_Section_Fields_BB_Checkbox::add_field_instance( 'mulpital_checkbox_checked' );
} );
