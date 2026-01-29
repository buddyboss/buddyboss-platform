<?php
/**
 * BuddyBoss Component Bridge
 *
 * Provides backward compatibility by automatically converting legacy
 * BuddyPress/BuddyBoss components to the new feature-based system.
 *
 * This bridge ensures that:
 * - Third-party plugins using bp_optional_components filter still work
 * - Existing BP_Component classes are recognized as features
 * - Component activation state syncs with feature activation state
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Component Bridge Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Component_Bridge {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var BB_Component_Bridge
	 */
	private static $instance = null;

	/**
	 * Mapping of component IDs to feature IDs.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $component_to_feature_map = array();

	/**
	 * Mapping of feature IDs to component IDs.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $feature_to_component_map = array();

	/**
	 * Components registered via filters (third-party).
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $external_components = array();

	/**
	 * Core BuddyBoss components (built-in).
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $core_components = array(
		'activity',
		'blogs',
		'document',
		'forums',
		'friends',
		'groups',
		'invites',
		'media',
		'members',
		'messages',
		'moderation',
		'notifications',
		'search',
		'settings',
		'video',
		'xprofile',
	);

	/**
	 * Whether the bridge has been initialized.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @return BB_Component_Bridge
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
		$this->setup_hooks();
	}

	/**
	 * Set up hooks.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function setup_hooks() {
		// Hook into component filters to capture third-party registrations.
		add_filter( 'bp_optional_components', array( $this, 'capture_optional_components' ), 999 );
		add_filter( 'bp_required_components', array( $this, 'capture_required_components' ), 999 );

		// Sync feature activation with component activation.
		add_filter( 'bp_active_components', array( $this, 'sync_active_components' ), 999 );

		// Register external components as features after core features are registered.
		add_action( 'bb_after_register_features', array( $this, 'register_external_components_as_features' ), 100 );

		// When a feature is activated/deactivated, sync to components.
		add_action( 'bb_feature_activated', array( $this, 'on_feature_activated' ), 10, 1 );
		add_action( 'bb_feature_deactivated', array( $this, 'on_feature_deactivated' ), 10, 1 );
	}

	/**
	 * Initialize the bridge.
	 *
	 * Sets up the component-to-feature mappings.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}

		// Build default mappings for core components.
		$this->build_core_mappings();

		$this->initialized = true;

		/**
		 * Fires after the component bridge is initialized.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BB_Component_Bridge $bridge The bridge instance.
		 */
		do_action( 'bb_component_bridge_initialized', $this );
	}

	/**
	 * Build mappings for core components.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function build_core_mappings() {
		// Default 1:1 mappings for core components.
		foreach ( $this->core_components as $component ) {
			$this->register_mapping( $component, $component );
		}

		// Special mappings where component != feature.
		$special_mappings = array(
			'xprofile' => 'members', // xprofile is part of members feature.
		);

		foreach ( $special_mappings as $component => $feature ) {
			$this->component_to_feature_map[ $component ] = $feature;
		}
	}

	/**
	 * Register a component-to-feature mapping.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id The component ID.
	 * @param string $feature_id   The feature ID.
	 */
	public function register_mapping( $component_id, $feature_id ) {
		$this->component_to_feature_map[ $component_id ] = $feature_id;
		$this->feature_to_component_map[ $feature_id ]   = $component_id;
	}

	/**
	 * Get the feature ID for a component.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id The component ID.
	 * @return string|null The feature ID or null if not mapped.
	 */
	public function get_feature_for_component( $component_id ) {
		return isset( $this->component_to_feature_map[ $component_id ] )
			? $this->component_to_feature_map[ $component_id ]
			: null;
	}

	/**
	 * Get the component ID for a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 * @return string|null The component ID or null if not mapped.
	 */
	public function get_component_for_feature( $feature_id ) {
		return isset( $this->feature_to_component_map[ $feature_id ] )
			? $this->feature_to_component_map[ $feature_id ]
			: null;
	}

	/**
	 * Capture optional components from the filter.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $components Array of optional component IDs.
	 * @return array Modified array of component IDs.
	 */
	public function capture_optional_components( $components ) {
		// Find components that are not in our core list (third-party).
		foreach ( $components as $component ) {
			if ( ! in_array( $component, $this->core_components, true ) ) {
				$this->external_components[ $component ] = array(
					'type'     => 'optional',
					'title'    => ucfirst( str_replace( array( '-', '_' ), ' ', $component ) ),
					'default'  => false,
				);
			}
		}

		return $components;
	}

	/**
	 * Capture required components from the filter.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $components Array of required component IDs.
	 * @return array Modified array of component IDs.
	 */
	public function capture_required_components( $components ) {
		// Find components that are not in our core list (third-party).
		foreach ( $components as $component ) {
			if ( ! in_array( $component, $this->core_components, true ) ) {
				$this->external_components[ $component ] = array(
					'type'     => 'required',
					'title'    => ucfirst( str_replace( array( '-', '_' ), ' ', $component ) ),
					'default'  => true,
				);
			}
		}

		return $components;
	}

	/**
	 * Sync active components with feature status.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $active_components Array of active component IDs.
	 * @return array Modified array of active component IDs.
	 */
	public function sync_active_components( $active_components ) {
		// Ensure the bridge is initialized.
		$this->init();

		// Get registry if available.
		if ( ! function_exists( 'bb_feature_registry' ) ) {
			return $active_components;
		}

		$registry = bb_feature_registry();

		// For each feature that maps to a component, check feature status.
		foreach ( $this->feature_to_component_map as $feature_id => $component_id ) {
			// Skip if feature not registered.
			if ( ! $registry->is_feature_registered( $feature_id ) ) {
				continue;
			}

			$is_active = $registry->is_feature_active( $feature_id );

			if ( $is_active && ! isset( $active_components[ $component_id ] ) ) {
				$active_components[ $component_id ] = '1';
			} elseif ( ! $is_active && isset( $active_components[ $component_id ] ) ) {
				unset( $active_components[ $component_id ] );
			}
		}

		return $active_components;
	}

	/**
	 * Register external (third-party) components as features.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function register_external_components_as_features() {
		if ( empty( $this->external_components ) ) {
			return;
		}

		$registry = bb_feature_registry();

		foreach ( $this->external_components as $component_id => $component_data ) {
			// Skip if already registered as a feature.
			if ( $registry->is_feature_registered( $component_id ) ) {
				continue;
			}

			// Determine if component is active.
			$active_components = bp_get_option( 'bp-active-components', array() );
			$is_active         = isset( $active_components[ $component_id ] );

			// Register as a feature.
			$result = $registry->register_feature(
				$component_id,
				array(
					'label'              => $component_data['title'],
					'description'        => sprintf(
						/* translators: %s: component name */
						__( '%s component (registered via legacy filter).', 'buddyboss' ),
						$component_data['title']
					),
					'icon'               => array(
						'type' => 'dashicon',
						'slug' => 'dashicons-admin-plugins',
					),
					'category'           => 'integrations',
					'license_tier'       => 'free',
					'is_active_callback' => function () use ( $component_id ) {
						return bp_is_active( $component_id );
					},
					'order'              => 500, // Place after core features.
					'_legacy_component'  => true, // Mark as legacy.
				)
			);

			if ( ! is_wp_error( $result ) ) {
				// Register the mapping.
				$this->register_mapping( $component_id, $component_id );
			}
		}

		/**
		 * Fires after external components are registered as features.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $external_components The external components that were registered.
		 */
		do_action( 'bb_external_components_registered', $this->external_components );
	}

	/**
	 * Handle feature activation - sync to component.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID that was activated.
	 */
	public function on_feature_activated( $feature_id ) {
		$component_id = $this->get_component_for_feature( $feature_id );

		if ( ! $component_id ) {
			return;
		}

		// Get current active components.
		$active_components = bp_get_option( 'bp-active-components', array() );

		// Add the component if not already active.
		if ( ! isset( $active_components[ $component_id ] ) ) {
			$active_components[ $component_id ] = '1';
			bp_update_option( 'bp-active-components', $active_components );

			/**
			 * Fires after a component is activated via feature activation.
			 *
			 * @since BuddyBoss 3.0.0
			 *
			 * @param string $component_id The component ID.
			 * @param string $feature_id   The feature ID.
			 */
			do_action( 'bb_component_activated_via_feature', $component_id, $feature_id );
		}
	}

	/**
	 * Handle feature deactivation - sync to component.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID that was deactivated.
	 */
	public function on_feature_deactivated( $feature_id ) {
		$component_id = $this->get_component_for_feature( $feature_id );

		if ( ! $component_id ) {
			return;
		}

		// Get current active components.
		$active_components = bp_get_option( 'bp-active-components', array() );

		// Remove the component if active.
		if ( isset( $active_components[ $component_id ] ) ) {
			unset( $active_components[ $component_id ] );
			bp_update_option( 'bp-active-components', $active_components );

			/**
			 * Fires after a component is deactivated via feature deactivation.
			 *
			 * @since BuddyBoss 3.0.0
			 *
			 * @param string $component_id The component ID.
			 * @param string $feature_id   The feature ID.
			 */
			do_action( 'bb_component_deactivated_via_feature', $component_id, $feature_id );
		}
	}

	/**
	 * Check if a component has a corresponding feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id The component ID.
	 * @return bool True if component has a feature mapping.
	 */
	public function has_feature_mapping( $component_id ) {
		return isset( $this->component_to_feature_map[ $component_id ] );
	}

	/**
	 * Check if a component is a core BuddyBoss component.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id The component ID.
	 * @return bool True if it's a core component.
	 */
	public function is_core_component( $component_id ) {
		return in_array( $component_id, $this->core_components, true );
	}

	/**
	 * Check if a component is an external (third-party) component.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id The component ID.
	 * @return bool True if it's an external component.
	 */
	public function is_external_component( $component_id ) {
		return isset( $this->external_components[ $component_id ] );
	}

	/**
	 * Get all external components.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Array of external components.
	 */
	public function get_external_components() {
		return $this->external_components;
	}

	/**
	 * Get all mappings for debugging.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array {
	 *     @type array $component_to_feature Component to feature mappings.
	 *     @type array $feature_to_component Feature to component mappings.
	 *     @type array $external_components  External components.
	 * }
	 */
	public function get_mappings() {
		return array(
			'component_to_feature' => $this->component_to_feature_map,
			'feature_to_component' => $this->feature_to_component_map,
			'external_components'  => $this->external_components,
		);
	}
}

/**
 * Get the Component Bridge instance.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return BB_Component_Bridge
 */
function bb_component_bridge() {
	return BB_Component_Bridge::instance();
}
