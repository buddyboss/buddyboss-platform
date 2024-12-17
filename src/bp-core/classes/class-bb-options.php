<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * To add new dynamic options please edit the config file in lib/data/options/dynamic_attrs.json
 */
#[AllowDynamicProperties]
class BB_Options {
	public $dynamic_attrs;

	public function __construct( $options = array() ) {
		$this->set_strings();
		$this->set_from_array( $options );
		$this->set_defaults();
		$this->wpml_custom_fields(); // Need to store as an array for WPML - this converts back to objects :).
	}

	/**
	 * Fetch the MeprOptions instance
	 *
	 * @param  boolean $force Force a fresh instance.
	 * @return self
	 */
	public static function fetch( $force = false ) {
		static $bb_options;

		if ( ! isset( $mepr_options ) or $force ) {
			$bb_options_array = get_option( 'bb_options' );

			if ( ! $bb_options_array ) {
				$bb_options = new BB_Options(); // Just grab the defaults
			} elseif ( is_object( $bb_options_array ) and is_a( $bb_options_array, 'BB_Options' ) ) {
				$bb_options = $bb_options_array;
				$bb_options->set_defaults();
				$bb_options->store( false ); // store will convert this back into an array.
			} elseif ( ! is_array( $bb_options_array ) ) {
				$bb_options = new BB_Options(); // Just grab the defaults.
			} else {
				$bb_options = new BB_Options( $bb_options_array ); // Sets defaults for unset options.
			}
		}

		$bb_options->set_strings(); // keep strings fresh (not db cached).
		$bb_options->wpml_custom_fields();  // Need to store as an array for WPML - this converts back to objects :).
		return BB_Hooks::apply_filters( 'bb_fetch_options', $bb_options );
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
		delete_option( 'bb_options' );
	}

	public function set_defaults() {

		if ( ! isset( $this->mothership_license ) ) {
			$this->mothership_license = '';
		}

		if ( ! isset( $this->auto_updates ) ) {
			$this->auto_updates = false;
		}

		if ( ! isset( $this->sslverify ) ) {
			$this->sslverify = true;
		}
	}

	public function set_strings() {
		$this->mothership_license_str = 'bb-mothership-license';
	}

	public function update( $params ) {
		// Set dynamic variables (we should migrate all attrs to use this soon).
		$this->set_attrs_from_slugs( $params );

		// This happens on the activate screen't do this here.
		// $this->mothership_license = stripslashes($params[$this->mothership_license_str]);
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

	public function validate( $params, $errors = array() ) {
		return $errors;
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
				update_option( 'bb_options', $options );
			}

			return $errors;
		}

		update_option( 'bb_options', $options );
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
					$value = get_option( $this->attr_slug( $attr ), $a['default'] );
					$this->set_attr( $this->attr_slug( $attr ), $value );
				} else {
					$value = $a['value'];
				}
			}
		}

		$value = stripslashes( $value ); // single quotes etc causing escape chars to appear when output.

		return BB_Hooks::apply_filters( 'bb-options-get-dynamic-attribute-' . $attr, $value, $this );
	}

	public function set_attr( $attr, $value ) {
		$value = BB_Hooks::apply_filters( 'bb-options-set-dynamic-attribute-' . $attr, $value, $this );

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
			update_option( $this->attr_slug( $attr ), $a['value'] );
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

	/**
	 * TODO: Add these once we convert legacy fields to work with dynamic attributes
	public function __get($attr) {
	return $this->attr($attr);
	}
	public function __set($attr, $value) {
	return $this->set_attr($attr, $value);
	}
	public function __isset($attr) {
	if(array_key_exists($attr, $this->dynamic_attrs)) {
	return isset($this->dynamic_attrs[$attr]['value']);
	}
	else {
	return isset($this->$attr);
	}
	}
	public function __unset($attr) {
	if(array_key_exists($attr, $this->dynamic_attrs)) {
	unset($this->dynamic_attrs[$attr]['value']);
	}
	else {
	unset($this->$attr);
	}
	}
	 */

	/**
	 * We just return a JSON encoding of the
	 * attributes in the model when we try
	 * to get a string for the options.
	 */
	public function __toString() {
		$str_array = (array) $this;

		// add dynamic attrs.
		foreach ( $this->dynamic_attrs as $attr => $config ) {
			$str_array[ $attr ] = $this->get_attr( $attr );
		}

		return wp_json_encode( $str_array );
	}
} //End class
