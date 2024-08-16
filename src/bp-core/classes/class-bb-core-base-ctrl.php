<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Base controller class.
 */
abstract class BB_Core_Base_Ctrl {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// This is to ensure that the load_hooks method is
		// only ever loaded once across all instantiations.
		static $loaded;

		if ( ! isset( $loaded ) ) {
			$loaded = array(); }

		$class_name = get_class( $this );

		if ( ! isset( $loaded[ $class_name ] ) ) {
			$this->load_hooks();
			$loaded[ $class_name ] = true;
		}
	}

	/**
	 * Load hooks.
	 */
	abstract public function load_hooks();
}
