<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );}

/** To add new dynamic options, please edit the config file in lib/data/options/dynamic_attrs.json */
#[AllowDynamicProperties]
class BB_Core_Options {
	public $dynamic_attrs;

	public function __construct( $options = array() ) {
		$this->set_strings();
		$this->set_from_array( $options );
		$this->set_defaults();
		$this->wpml_custom_fields(); // Need to store as an array for WPML - this converts back to objects :).
	}

	/**
	 * Fetch the BB_Core_Options instance
	 *
	 * @param bool $force Force a fresh instance.
	 * @return self
	 */
	public static function fetch( $force = false ) {
		static $bb_options;

		if ( ! isset( $bb_options ) or $force ) {
			$bb_options_array = bp_get_option( BB_CORE_OPTIONS_SLUG );

			if ( ! $bb_options_array ) {
				$bb_options = new BB_Core_Options(); // Just grab the defaults.
			} elseif ( is_object( $bb_options_array ) and is_a( $bb_options_array, 'BB_Options' ) ) {
				$bb_options = $bb_options_array;
				$bb_options->set_defaults();
				$bb_options->store( false ); // store will convert this back into an array.
			} elseif ( ! is_array( $bb_options_array ) ) {
				$bb_options = new BB_Core_Options(); // Just grab the defaults.
			} else {
				$bb_options = new BB_Core_Options( $bb_options_array ); // Sets defaults for unset options.
			}
		}

		$bb_options->set_strings(); // keep strings fresh (not db cached).
		$bb_options->wpml_custom_fields();  // Need to store as an array for WPML - this converts back to objects :).
		return BB_Core_Hooks::apply_filters( 'bb_fetch_options', $bb_options );
	}

	public function wpml_custom_fields() {
		if ( ! empty( $this->custom_fields ) ) {
			$new_fields = array();
			foreach ( $this->custom_fields as $row ) {
				$row = (object) $row; // Convert rows back to objects.

				$new_options = array();
				if ( ! empty( $row->options ) ) {
					foreach ( $row->options as $option ) {
						$new_options[] = (object) $option; // Convert options back into objects.
					}

					$row->options = $new_options;
				}

				$new_fields[] = $row;
			}

			$this->custom_fields = $new_fields;
		}

		return $this->custom_fields;
	}

	public static function reset() {
	}

	// This is used to allow permalinks to be retrieved
	// Early on in the game.
	public function populate_rewrite() {
		if ( empty( $GLOBALS['wp_rewrite'] ) ) {
			$GLOBALS['wp_rewrite'] = new WP_Rewrite();
		}
	}

	public function set_defaults() {

		if ( ! isset( $this->disable_mod_rewrite ) ) {
			$this->disable_mod_rewrite = true;
		}

		if ( ! isset( $this->language_code ) ) {
			// Get site language code.
			$this->language_code = get_locale();
		}

		if ( ! isset( $this->lock_wp_admin ) ) {
			$this->lock_wp_admin = true;
		}


	}

	public function set_strings() {

		$this->disable_mod_rewrite_str       = 'bb-disable-mod-rewrite';
		$this->language_code_str             = 'bb-language-symbol';
		$this->lock_wp_admin_str             = 'bb-lock-wp-admin';

	}

	public function validate( $params, $errors = array() ) {

		return $errors;
	}

	public function update( $params ) {

		$this->disable_mod_rewrite = isset( $params[ $this->disable_mod_rewrite_str ] );
		$this->language_code       = stripslashes( $params[ $this->language_code_str ] );
		$this->lock_wp_admin       = isset( $params[ $this->lock_wp_admin_str ] );

		// We now support address being required -- handle that here.
		$this->custom_fields = $this->update_custom_fields( $params );

	}

	public function get_custom_field_slugs() {
		$slugs = array();

		if ( empty( $this->custom_fields ) ) {
			return $slugs;
		}

		foreach ( $this->custom_fields as $row ) {
			$slugs[] = $row->field_key;
		}

		return $slugs;
	}

	public function get_custom_field( $field_key ) {
		$custom_field = null;

		foreach ( $this->custom_fields as $custom_field ) {
			if ( $custom_field->field_key == $field_key ) {
				return $custom_field;
			}
		}

		return $custom_field;
	}

	public function update_custom_fields( $params ) {
		$fields = array();

		if ( isset( $params[ $this->custom_fields_str ] ) && ! empty( $params[ $this->custom_fields_str ] ) ) {
			$indexes = $params['bb-custom-fields-index'];

			$used = array();

			foreach ( $indexes as $i ) {
				$name = isset( $params[ $this->custom_fields_str ][ $i ]['name'] ) ? $params[ $this->custom_fields_str ][ $i ]['name'] : '';
				$slug = ( $params[ $this->custom_fields_str ][ $i ]['slug'] == 'bb_none' ) ? substr( BB_Core_Utils::sanitize_string( 'bb_' . $name ), 0, 240 ) : substr( $params[ $this->custom_fields_str ][ $i ]['slug'], 0, 240 ); // Need to check that this key doesn't already exist in usermeta table

				// Prevent duplicate slugs.
				if ( in_array( $slug, $used ) ) {
					do {
						$slug_parts = explode( '-', $slug );
						if ( is_array( $slug_parts ) ) { // We may have a number appended already.
							$number = array_pop( $slug_parts );
							if ( is_numeric( $number ) ) { // Increment the existing number.
								$append                     = (int) $number + 1;
								$last_index                 = count( $slug_parts ) - 1;
								$slug_parts[ $last_index ] .= "-{$append}";
								$slug                       = implode( '-', $slug_parts );
							} else { // Append 1.
								$slug .= '-1';
							}
						} else { // Append 1.
							$slug .= '-1';
						}
					} while ( in_array( $slug, $used ) );
				}
				$used[] = $slug;

				$type            = $params[ $this->custom_fields_str ][ $i ]['type'];
				$default         = isset( $params[ $this->custom_fields_str ][ $i ]['default'] ) ? $params[ $this->custom_fields_str ][ $i ]['default'] : '';
				$signup          = ( isset( $params[ $this->custom_fields_str ][ $i ]['signup'] ) || isset( $params[ $this->custom_fields_str ][ $i ]['required'] ) );
				$show_in_account = isset( $params[ $this->custom_fields_str ][ $i ]['show_in_account'] );
				$required        = isset( $params[ $this->custom_fields_str ][ $i ]['required'] );
				$dropdown_ops    = array();

				if ( in_array( $type, array( 'dropdown', 'multiselect', 'radios', 'checkboxes' ) ) ) {
					$options = $params[ $this->custom_fields_str ][ $i ]['option'];
					$values  = $params[ $this->custom_fields_str ][ $i ]['value'];

					foreach ( $options as $key => $value ) {
						if ( ! empty( $value ) ) {
							// Due to WPML compat - we're no longer storing these as forced (object)'s - see $this->wpml_custom_fields().
							$option_value = sanitize_title( $values[ $key ], sanitize_title( $options[ $key ] ) );
							$option_value = BB_Core_Hooks::apply_filters( 'bb-custom-field-option-value', $option_value, $values[ $key ], $options[ $key ] );

							$dropdown_ops[] = array(
								'option_name'  => $options[ $key ],
								'option_value' => $option_value,
							);
						}
					}

					if ( empty( $dropdown_ops ) ) {
						$name = ''; // if no dropdown options were entered let's not save this line.
					}
				}

				if ( '' !== $name ) { // If no name was left let's not save this line.
					// Due to WPML compat - we're no longer storing these as forced (object)'s - see $this->wpml_custom_fields().
					$fields[] = array(
						'field_key'       => $slug,
						'field_name'      => $name,
						'field_type'      => $type,
						'default_value'   => $default,
						'show_on_signup'  => $signup,
						'show_in_account' => $show_in_account,
						'required'        => $required,
						'options'         => $dropdown_ops,
					);
				}
			}
		}

		return $fields;
	}

	public function set_from_array( $options = array(), $post_array = false ) {
		if ( $post_array ) {
			$this->update( $post_array );
		} else { // Set values from array.
			foreach ( $options as $key => $value ) {
				$this->$key = $value;
			}
		}
	}

	public function store( $validate = true ) {
		$options = (array) $this;
		// Don't want to store any dynamic attributes.
		unset( $options['dynamic_attrs'] );

		// This is where we store our dynamic_attrs.
		$this->store_attrs();

		if ( $validate ) {
			$errors = $this->validate( $_POST );

			if ( empty( $errors ) ) {
				bp_update_option( BB_CORE_OPTIONS_SLUG, $options );
			}

			return $errors;
		}

		bp_update_option( BB_CORE_OPTIONS_SLUG, $options );
	}

	public function attr( $attr ) {
		return $this->get_attr( $attr );
	}

	public function get_attr( $attr ) {
		$value = '';

		if ( array_key_exists( $attr, get_object_vars( $this ) ) ) {
			$value = $this->$attr;
		} elseif ( array_key_exists( $attr, $this->dynamic_attrs ) ) {
			$a = $this->attr_config( $attr );

			if ( ! empty( $a ) ) {
				if ( ! isset( $a['value'] ) ) {
					$value = bp_get_option( $this->attr_slug( $attr ), $a['default'] );
					$this->set_attr( $this->attr_slug( $attr ), $value );
				} else {
					$value = $a['value'];
				}
			}
		}

		$value = stripslashes( $value ); // single quotes etc causing escape chars to appear when output.

		return BB_Core_Hooks::apply_filters( 'bb-options-get-dynamic-attribute-' . $attr, $value, $this );
	}

	public function set_attr( $attr, $value ) {
		$value = BB_Core_Hooks::apply_filters( 'bb-options-set-dynamic-attribute-' . $attr, $value, $this );

		if ( array_key_exists( $attr, get_object_vars( $this ) ) ) {
			$this->$attr = $value;
		} elseif ( array_key_exists( $attr, $this->dynamic_attrs ) ) {
			$this->dynamic_attrs[ $attr ]['value'] = $value;
		}
	}

	private function set_attrs( $attrs ) {
		foreach ( $attrs as $attr => $value ) {
			if ( array_key_exists( $attr, $this->dynamic_attrs ) ) {
				$this->set_attr( $attr, $value );
			}
		}
	}

	private function set_attrs_from_slugs( $slugs ) {
		// Build slug to attr lookup array.
		$slugs_lookup = array();
		foreach ( $this->dynamic_attrs as $attr => $info ) {
			$slugs_lookup[ $this->attr_slug( $attr ) ] = $attr;
		}

		foreach ( $slugs as $slug => $value ) {
			if ( array_key_exists( $slug, $slugs_lookup ) ) {
				$attr = $slugs_lookup[ $slug ];
				$this->set_attr( $attr, $value );
			}
		}
	}

	private function store_attr( $attr ) {
		$a = $this->attr_config( $attr );
		if ( ! empty( $a ) && isset( $a['value'] ) ) {
			bp_update_option( $this->attr_slug( $attr ), $a['value'] );
		}
	}

	private function store_attrs() {
		foreach ( $this->dynamic_attrs as $attr => $config ) {
			$this->store_attr( $attr );
		}
	}

	private function attr_config( $attr ) {
		if ( array_key_exists( $attr, $this->dynamic_attrs ) ) {
			return $this->dynamic_attrs[ $attr ];
		}
		return '';
	}

	public function attr_slug( $attr ) {
		if ( array_key_exists( $attr, $this->dynamic_attrs ) ) {
			if ( isset( $this->dynamic_attrs['slug'] ) ) {
				return $this->dynamic_attrs['slug'];
			} else {
				return "bb_{$attr}";
			}
		}
		return '';
	}

	public function attr_default( $attr ) {
		if ( array_key_exists( $attr, $this->dynamic_attrs ) ) {
			return $this->dynamic_attrs[ $attr ]['default'];
		}
		return '';
	}

	public function attr_validations( $attr ) {
		if ( array_key_exists( $attr, $this->dynamic_attrs ) ) {
			return $this->dynamic_attrs[ $attr ]['validations'];
		}
		return '';
	}

	/** We just return a JSON encoding of the
	 * attributes in the model when we try
	 * to get a string for the options. */
	public function __toString() {
		$str_array = (array) $this;

		// add dynamic attrs.
		foreach ( $this->dynamic_attrs as $attr => $config ) {
			$str_array[ $attr ] = $this->get_attr( $attr );
		}

		return wp_json_encode( $str_array );
	}
} //End class
