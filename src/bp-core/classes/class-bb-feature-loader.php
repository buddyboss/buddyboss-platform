<?php
/**
 * BuddyBoss Feature Loader
 *
 * Handles conditional PHP loading based on feature status.
 * This class is responsible for loading feature-specific PHP files
 * only when the corresponding feature is active.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Feature Loader Class
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Feature_Loader {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var BB_Feature_Loader
	 */
	private static $instance = null;

	/**
	 * Registered PHP loaders by feature.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private $php_loaders = array();

	/**
	 * Registered admin loaders by feature.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private $admin_loaders = array();

	/**
	 * Registered REST API loaders by feature.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private $rest_loaders = array();

	/**
	 * Features that have been loaded.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private $loaded_features = array();

	/**
	 * Features that have had their admin code loaded.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private $loaded_admin_features = array();

	/**
	 * Deferred loaders to execute on specific hooks.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $deferred_loaders = array();

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 */
	private function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up hooks.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function setup_hooks() {
		// Load features after they are registered.
		add_action( 'bb_after_register_features', array( $this, 'bb_load_active_features' ), 10 );

		// Load admin-specific code.
		add_action( 'bp_admin_init', array( $this, 'bb_load_admin_features' ), 5 );

		// Load REST API endpoints.
		add_action( 'bp_rest_api_init', array( $this, 'bb_load_rest_features' ), 5 );

		// Process deferred loaders.
		add_action( 'bp_init', array( $this, 'bb_process_deferred_loaders' ), 1 );
	}

	/**
	 * Register a PHP loader for a feature.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string   $feature_id The feature ID.
	 * @param callable $loader     The loader callback.
	 * @param array    $args       Optional. Additional arguments. {
	 *     @type string $hook     Hook to defer loading to (default: immediate).
	 *     @type int    $priority Priority for deferred hook (default: 10).
	 * }
	 * @return bool True on success.
	 */
	public function bb_register_php_loader( $feature_id, $loader, $args = array() ) {
		if ( ! is_callable( $loader ) ) {
			return false;
		}

		$args = wp_parse_args(
			$args,
			array(
				'hook'     => '',
				'priority' => 10,
			)
		);

		if ( ! empty( $args['hook'] ) ) {
			// Store for deferred loading.
			if ( ! isset( $this->deferred_loaders[ $args['hook'] ] ) ) {
				$this->deferred_loaders[ $args['hook'] ] = array();
			}
			$this->deferred_loaders[ $args['hook'] ][] = array(
				'feature_id' => $feature_id,
				'loader'     => $loader,
				'priority'   => $args['priority'],
			);
		} else {
			$this->php_loaders[ $feature_id ] = $loader;
		}

		return true;
	}

	/**
	 * Register an admin loader for a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string   $feature_id The feature ID.
	 * @param callable $loader     The loader callback.
	 * @return bool True on success.
	 */
	public function bb_register_admin_loader( $feature_id, $loader ) {
		if ( ! is_callable( $loader ) ) {
			return false;
		}

		$this->admin_loaders[ $feature_id ] = $loader;
		return true;
	}

	/**
	 * Register a REST API loader for a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string   $feature_id The feature ID.
	 * @param callable $loader     The loader callback.
	 * @return bool True on success.
	 */
	public function bb_register_rest_loader( $feature_id, $loader ) {
		if ( ! is_callable( $loader ) ) {
			return false;
		}

		$this->rest_loaders[ $feature_id ] = $loader;
		return true;
	}

	/**
	 * Load all active features.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_load_active_features() {
		$registry = bb_feature_registry();

		foreach ( $this->php_loaders as $feature_id => $loader ) {
			if ( $this->bb_is_feature_loaded( $feature_id ) ) {
				continue;
			}

			if ( $registry->bb_is_feature_active( $feature_id ) ) {
				$this->bb_load_feature( $feature_id, $loader );
			}
		}

		/**
		 * Fires after all active features have been loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $loaded_features Array of loaded feature IDs.
		 */
		do_action( 'bb_features_loaded', $this->loaded_features );
	}

	/**
	 * Load admin-specific code for active features.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_load_admin_features() {
		if ( ! is_admin() ) {
			return;
		}

		$registry = bb_feature_registry();

		foreach ( $this->admin_loaders as $feature_id => $loader ) {
			if ( $this->bb_is_admin_feature_loaded( $feature_id ) ) {
				continue;
			}

			if ( $registry->bb_is_feature_active( $feature_id ) ) {
				$this->bb_load_admin_feature( $feature_id, $loader );
			}
		}

		/**
		 * Fires after all admin features have been loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $loaded_admin_features Array of loaded admin feature IDs.
		 */
		do_action( 'bb_admin_features_loaded', $this->loaded_admin_features );
	}

	/**
	 * Load REST API endpoints for active features.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_load_rest_features() {
		$registry = bb_feature_registry();

		foreach ( $this->rest_loaders as $feature_id => $loader ) {
			if ( $registry->bb_is_feature_active( $feature_id ) ) {
				call_user_func( $loader );
			}
		}

		/**
		 * Fires after all REST features have been loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 */
		do_action( 'bb_rest_features_loaded' );
	}

	/**
	 * Process deferred loaders.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bb_process_deferred_loaders() {
		$registry = bb_feature_registry();

		foreach ( $this->deferred_loaders as $hook => $loaders ) {
			add_action(
				$hook,
				function () use ( $loaders, $registry ) {
					foreach ( $loaders as $loader_data ) {
						if ( $registry->bb_is_feature_active( $loader_data['feature_id'] ) ) {
							call_user_func( $loader_data['loader'] );
						}
					}
				},
				5 // Load early on the deferred hook.
			);
		}
	}

	/**
	 * Load a specific feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string   $feature_id The feature ID.
	 * @param callable $loader     The loader callback.
	 */
	private function bb_load_feature( $feature_id, $loader ) {
		/**
		 * Fires before a feature is loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id The feature ID.
		 */
		do_action( 'bb_before_load_feature', $feature_id );

		call_user_func( $loader );

		$this->loaded_features[] = $feature_id;

		/**
		 * Fires after a feature is loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id The feature ID.
		 */
		do_action( 'bb_after_load_feature', $feature_id );
		do_action( "bb_after_load_feature_{$feature_id}" );
	}

	/**
	 * Load admin code for a specific feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string   $feature_id The feature ID.
	 * @param callable $loader     The loader callback.
	 */
	private function bb_load_admin_feature( $feature_id, $loader ) {
		/**
		 * Fires before admin feature code is loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id The feature ID.
		 */
		do_action( 'bb_before_load_admin_feature', $feature_id );

		call_user_func( $loader );

		$this->loaded_admin_features[] = $feature_id;

		/**
		 * Fires after admin feature code is loaded.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id The feature ID.
		 */
		do_action( 'bb_after_load_admin_feature', $feature_id );
		do_action( "bb_after_load_admin_feature_{$feature_id}" );
	}

	/**
	 * Check if a feature has been loaded.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 * @return bool True if loaded.
	 */
	public function bb_is_feature_loaded( $feature_id ) {
		return in_array( $feature_id, $this->loaded_features, true );
	}

	/**
	 * Check if admin code for a feature has been loaded.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 * @return bool True if loaded.
	 */
	public function bb_is_admin_feature_loaded( $feature_id ) {
		return in_array( $feature_id, $this->loaded_admin_features, true );
	}

	/**
	 * Get all loaded features.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Array of loaded feature IDs.
	 */
	public function bb_get_loaded_features() {
		return $this->loaded_features;
	}

	/**
	 * Manually load a feature immediately.
	 *
	 * Useful for forcing a feature to load outside the normal loading cycle.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 * @return bool True if loaded, false if already loaded or no loader exists.
	 */
	public function bb_force_load_feature( $feature_id ) {
		if ( $this->bb_is_feature_loaded( $feature_id ) ) {
			return false;
		}

		if ( ! isset( $this->php_loaders[ $feature_id ] ) ) {
			return false;
		}

		$this->bb_load_feature( $feature_id, $this->php_loaders[ $feature_id ] );
		return true;
	}

	/**
	 * Get registered loaders for debugging.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array {
	 *     @type array $php_loaders   Registered PHP loaders.
	 *     @type array $admin_loaders Registered admin loaders.
	 *     @type array $rest_loaders  Registered REST loaders.
	 *     @type array $deferred      Deferred loaders.
	 * }
	 */
	public function bb_get_registered_loaders() {
		return array(
			'php_loaders'   => array_keys( $this->php_loaders ),
			'admin_loaders' => array_keys( $this->admin_loaders ),
			'rest_loaders'  => array_keys( $this->rest_loaders ),
			'deferred'      => array_keys( $this->deferred_loaders ),
		);
	}
}
