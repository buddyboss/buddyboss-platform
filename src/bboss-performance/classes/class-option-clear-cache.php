<?php
/**
 * BuddyBoss Performance clear cache.
 *
 * @package BuddyBoss\Performance\OptionClearCache
 */

namespace BuddyBoss\Performance;

/**
 * Class ClearCache
 *
 * @package BuddyBoss\Performance
 */
class OptionClearCache {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return OptionClearCache
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
			self::$instance->initialize(); // run the hooks.
		}

		return self::$instance;
	}

	/**
	 * Initialisation of class.
	 */
	public function initialize() {
		add_action( 'updated_option', array( $this, 'purge_component_cache' ), 10, 3 );
	}

	/**
	 * Purge component cache by setting component enabled or disabled.
	 *
	 * @param string $option    Option Name.
	 * @param string $old_value Option Old Value.
	 * @param string $value     Option Updated Value.
	 */
	public function purge_component_cache( $option, $old_value, $value ) {
		/**
		 * Filter to determine if components should be purged based on option changes.
		 *
		 * This filter allows developers to control whether cache purging should occur
		 * When specific options are updated. Return true or an array of components
		 * to trigger the purge process.
		 *
		 * @param bool $do_purge_components Boolean flag to control purge behaviour.
		 * @param string $option The option name being updated.
		 * @param mixed $old_value The old option value.
		 * @param mixed $value The new option value.
		 *
		 * @return bool|array               Boolean to control purge behaviour or array of components to purge.
		 */
		$do_purge_components = apply_filters( 'performance_purge_components_flag', false, $option, $old_value, $value );

		if ( ! $do_purge_components ) {
			return;
		}

		/**
		 * An array containing the list of components to be purged or cleared.
		 *
		 * This variable typically holds the identifiers or names of components
		 * (e.g., cache keys, module instances, or system components) that need
		 * to be reset, reinitialised, or removed during a cleanup or purge process.
		 *
		 * Usage and behaviour depend on the context in which the variable is implemented,
		 * such as a caching mechanism, a framework's lifecycle hooks, or other
		 * System cleaning operations.
		 *
		 * @param array $purge_components Purge components.
		 * @param string $option Purge option.
		 * @param array $old_value Array of old values.
		 * @param array $value Array of values.
		 */
		$purge_components = apply_filters( 'performance_purge_components', array(), $option, $old_value, $value );

		if ( ! empty( $purge_components ) ) {
			$purge_components = array_unique( $purge_components );

			foreach ( $purge_components as $purge_component ) {
				Cache::instance()->purge_by_component( $purge_component );
			}

			/**
			 * Fires after selected components have been purged or cleared.
			 *
			 * This variable is typically used in scenarios where specific components are flagged
			 * for deletion, cleanup, or resetting processes. It can hold names, identifiers,
			 * or references to these components based on the application context.
			 */
			do_action( 'performance_purge_components_after', $purge_components, $option, $old_value, $value );
		}
	}
}
