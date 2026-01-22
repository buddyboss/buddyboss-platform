<?php
/**
 * BuddyBoss Feature Loader
 *
 * Handles conditional PHP loading based on feature status.
 * Executes php_loader callbacks from registered features only when active.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Feature Loader Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Feature_Loader {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var BB_Feature_Loader
	 */
	private static $instance = null;

	/**
	 * Tracks which features have been loaded.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $loaded_features = array();

	/**
	 * Deferred loaders for lazy loading.
	 *
	 * Structure: $deferred_loaders[ $hook ][] = array( 'feature_id' => $id, 'callback' => $callback )
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $deferred_loaders = array();

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @return BB_Feature_Loader
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function __construct() {
		// Hook into feature registration completion to load active features.
		add_action( 'bb_after_register_features', array( $this, 'load_active_features' ), 10 );

		// Track components loaded via the legacy system.
		add_action( 'bb_component_loaded_legacy', array( $this, 'track_legacy_loaded_component' ), 10, 2 );
	}

	/**
	 * Track a component loaded via the legacy system.
	 *
	 * This prevents the Feature Loader from double-loading components
	 * that were already loaded by BP_Core::load_components().
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component Component ID.
	 * @param string $type      Component type: 'optional' or 'required'.
	 */
	public function track_legacy_loaded_component( $component, $type ) {
		// Mark the feature as loaded (component ID = feature ID for bridged components).
		$this->loaded_features[ $component ] = true;
	}

	/**
	 * Load all active features that have php_loader callbacks.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function load_active_features() {
		$registry = bb_feature_registry();
		$features = $registry->get_features( array( 'status' => 'all' ) );

		foreach ( $features as $feature_id => $feature ) {
			// Skip if already loaded.
			if ( isset( $this->loaded_features[ $feature_id ] ) ) {
				continue;
			}

			// Skip if no php_loader defined.
			if ( empty( $feature['php_loader'] ) ) {
				continue;
			}

			// Only load if feature is active.
			if ( ! $registry->is_feature_active( $feature_id ) ) {
				continue;
			}

			$this->load_feature( $feature_id, $feature['php_loader'] );
		}

		/**
		 * Fires after all active features have been loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $loaded_features Array of loaded feature IDs.
		 */
		do_action( 'bb_features_loaded', array_keys( $this->loaded_features ) );
	}

	/**
	 * Load a single feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string   $feature_id Feature ID.
	 * @param callable $loader     Loader callback.
	 * @return bool True if loaded, false otherwise.
	 */
	public function load_feature( $feature_id, $loader ) {
		// Prevent double loading.
		if ( isset( $this->loaded_features[ $feature_id ] ) ) {
			return false;
		}

		// Validate loader is callable.
		if ( ! is_callable( $loader ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" has an invalid php_loader callback.', 'buddyboss' ),
					$feature_id
				),
				'3.0.0'
			);
			return false;
		}

		/**
		 * Fires before a feature is loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id Feature ID.
		 */
		do_action( 'bb_before_load_feature', $feature_id );

		// Execute the loader.
		call_user_func( $loader );

		// Mark as loaded.
		$this->loaded_features[ $feature_id ] = true;

		/**
		 * Fires after a feature is loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id Feature ID.
		 */
		do_action( 'bb_after_load_feature', $feature_id );
		do_action( "bb_feature_{$feature_id}_loaded", $feature_id );

		return true;
	}

	/**
	 * Register a deferred loader for lazy loading on a specific hook.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string   $feature_id Feature ID.
	 * @param string   $hook       WordPress hook to defer loading to.
	 * @param callable $loader     Loader callback.
	 * @param int      $priority   Hook priority (default: 10).
	 */
	public function defer_load( $feature_id, $hook, $loader, $priority = 10 ) {
		// Store the deferred loader.
		if ( ! isset( $this->deferred_loaders[ $hook ] ) ) {
			$this->deferred_loaders[ $hook ] = array();
		}

		$this->deferred_loaders[ $hook ][] = array(
			'feature_id' => $feature_id,
			'callback'   => $loader,
			'priority'   => $priority,
		);

		// Register the hook handler if not already registered.
		if ( 1 === count( $this->deferred_loaders[ $hook ] ) ) {
			add_action( $hook, array( $this, 'execute_deferred_loaders' ), $priority );
		}
	}

	/**
	 * Execute deferred loaders for the current hook.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function execute_deferred_loaders() {
		$hook = current_filter();

		if ( empty( $this->deferred_loaders[ $hook ] ) ) {
			return;
		}

		$registry = bb_feature_registry();

		foreach ( $this->deferred_loaders[ $hook ] as $deferred ) {
			$feature_id = $deferred['feature_id'];
			$loader     = $deferred['callback'];

			// Skip if already loaded.
			if ( isset( $this->loaded_features[ $feature_id ] ) ) {
				continue;
			}

			// Only load if feature is still active.
			if ( ! $registry->is_feature_active( $feature_id ) ) {
				continue;
			}

			$this->load_feature( $feature_id, $loader );
		}

		// Clear the deferred loaders for this hook.
		unset( $this->deferred_loaders[ $hook ] );
	}

	/**
	 * Check if a feature has been loaded.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return bool True if loaded, false otherwise.
	 */
	public function is_feature_loaded( $feature_id ) {
		return isset( $this->loaded_features[ $feature_id ] );
	}

	/**
	 * Get all loaded features.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Array of loaded feature IDs.
	 */
	public function get_loaded_features() {
		return array_keys( $this->loaded_features );
	}

	/**
	 * Manually trigger loading of a feature if it hasn't been loaded yet.
	 *
	 * Useful for on-demand loading when a feature is needed.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID to load.
	 * @return bool True if loaded (or already loaded), false if feature not found or inactive.
	 */
	public function maybe_load_feature( $feature_id ) {
		// Already loaded.
		if ( isset( $this->loaded_features[ $feature_id ] ) ) {
			return true;
		}

		$registry = bb_feature_registry();
		$feature  = $registry->get_feature( $feature_id );

		// Feature not registered.
		if ( null === $feature ) {
			return false;
		}

		// Feature not active.
		if ( ! $registry->is_feature_active( $feature_id ) ) {
			return false;
		}

		// No loader defined.
		if ( empty( $feature['php_loader'] ) ) {
			return false;
		}

		return $this->load_feature( $feature_id, $feature['php_loader'] );
	}
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get the Feature Loader instance.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return BB_Feature_Loader
 */
function bb_feature_loader() {
	return BB_Feature_Loader::instance();
}

/**
 * Check if a feature has been loaded.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @return bool True if loaded, false otherwise.
 */
function bb_is_feature_loaded( $feature_id ) {
	return bb_feature_loader()->is_feature_loaded( $feature_id );
}

/**
 * Manually trigger loading of a feature.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID to load.
 * @return bool True if loaded, false otherwise.
 */
function bb_maybe_load_feature( $feature_id ) {
	return bb_feature_loader()->maybe_load_feature( $feature_id );
}
