<?php
/**
 * BuddyBoss Component Bridge
 *
 * Provides backward compatibility between the legacy BP_Component system
 * and the new Feature Registry system. Auto-converts legacy components
 * to features and keeps activation states synchronized.
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
	 * Component to feature mapping.
	 *
	 * Maps legacy component IDs to feature IDs (usually the same).
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $component_feature_map = array();

	/**
	 * Components that have been bridged.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $bridged_components = array();

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
	 * Setup hooks for bridging.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function setup_hooks() {
		// Listen to component filters to capture legacy components.
		add_filter( 'bp_optional_components', array( $this, 'capture_optional_components' ), 999 );
		add_filter( 'bp_required_components', array( $this, 'capture_required_components' ), 999 );

		// Auto-register legacy components as features after features are registered.
		add_action( 'bb_after_register_features', array( $this, 'bridge_legacy_components' ), 5 );

		// Sync feature activation to bp-active-components.
		add_action( 'bb_feature_activated', array( $this, 'sync_feature_to_component' ), 10, 1 );
		add_action( 'bb_feature_deactivated', array( $this, 'sync_feature_to_component' ), 10, 1 );
	}

	/**
	 * Capture optional components from the filter.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $components Array of optional component IDs.
	 * @return array
	 */
	public function capture_optional_components( $components ) {
		foreach ( $components as $component ) {
			$this->component_feature_map[ $component ] = array(
				'type'       => 'optional',
				'feature_id' => $component, // Default: same as component ID.
			);
		}
		return $components;
	}

	/**
	 * Capture required components from the filter.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $components Array of required component IDs.
	 * @return array
	 */
	public function capture_required_components( $components ) {
		foreach ( $components as $component ) {
			$this->component_feature_map[ $component ] = array(
				'type'       => 'required',
				'feature_id' => $component, // Default: same as component ID.
			);
		}
		return $components;
	}

	/**
	 * Bridge legacy components that aren't registered as features.
	 *
	 * Auto-registers features for any component that doesn't already have
	 * a feature registration.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function bridge_legacy_components() {
		$registry = bb_feature_registry();

		foreach ( $this->component_feature_map as $component_id => $config ) {
			$feature_id = $config['feature_id'];

			// Skip if feature already registered (has native registration).
			$existing_feature = $registry->get_feature( $feature_id );
			if ( null !== $existing_feature ) {
				// Track the mapping even for existing features.
				$this->bridged_components[ $component_id ] = $feature_id;
				continue;
			}

			// Auto-register a feature for this legacy component.
			$this->auto_register_component_feature( $component_id, $config );
		}

		/**
		 * Fires after legacy components have been bridged.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $bridged_components Array of bridged component IDs.
		 */
		do_action( 'bb_components_bridged', $this->bridged_components );
	}

	/**
	 * Auto-register a feature for a legacy component.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id Component ID.
	 * @param array  $config       Component configuration.
	 */
	private function auto_register_component_feature( $component_id, $config ) {
		$bp         = buddypress();
		$feature_id = $config['feature_id'];
		$is_required = 'required' === $config['type'];

		// Generate label from component ID.
		$label = ucwords( str_replace( array( '-', '_' ), ' ', $component_id ) );

		// Build php_loader callback.
		$loader_file = $bp->plugin_dir . 'bp-' . $component_id . '/bp-' . $component_id . '-loader.php';
		$php_loader  = null;

		if ( file_exists( $loader_file ) ) {
			$php_loader = function() use ( $loader_file ) {
				require_once $loader_file;
			};
		}

		// Register the feature.
		$result = bb_register_feature(
			$feature_id,
			array(
				'label'              => $label,
				'description'        => sprintf(
					/* translators: %s: component name */
					__( '%s component (auto-bridged from legacy system).', 'buddyboss' ),
					$label
				),
				'category'           => 'community',
				'license_tier'       => 'free',
				'is_active_callback' => function() use ( $component_id ) {
					return bp_is_active( $component_id );
				},
				'php_loader'         => $php_loader,
				'settings_route'     => '/settings/' . $feature_id,
				'order'              => 200, // Lower priority than native features.
				'_bridged'           => true, // Mark as bridged for debugging.
				'_component_id'      => $component_id,
				'_is_required'       => $is_required,
			)
		);

		if ( true === $result || ! is_wp_error( $result ) ) {
			$this->bridged_components[ $component_id ] = $feature_id;
		}
	}

	/**
	 * Sync feature activation state to bp-active-components option.
	 *
	 * Ensures the legacy option stays in sync when features are
	 * activated/deactivated through the new system.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID that was activated/deactivated.
	 */
	public function sync_feature_to_component( $feature_id ) {
		// Find the component ID for this feature.
		$component_id = $this->get_component_for_feature( $feature_id );

		if ( ! $component_id ) {
			// Feature doesn't map to a component.
			return;
		}

		// The feature registry already updates bp-active-components in its
		// activate_feature() and deactivate_feature() methods.
		// This hook is here for any additional sync logic needed.

		/**
		 * Fires after feature-component sync.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id   Feature ID.
		 * @param string $component_id Component ID.
		 */
		do_action( 'bb_feature_component_synced', $feature_id, $component_id );
	}

	/**
	 * Get the component ID for a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return string|null Component ID or null if not found.
	 */
	public function get_component_for_feature( $feature_id ) {
		// Check direct mapping.
		if ( isset( $this->component_feature_map[ $feature_id ] ) ) {
			return $feature_id;
		}

		// Check reverse mapping in bridged components.
		$component_id = array_search( $feature_id, $this->bridged_components, true );
		return $component_id ? $component_id : null;
	}

	/**
	 * Get the feature ID for a component.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id Component ID.
	 * @return string|null Feature ID or null if not found.
	 */
	public function get_feature_for_component( $component_id ) {
		if ( isset( $this->bridged_components[ $component_id ] ) ) {
			return $this->bridged_components[ $component_id ];
		}

		if ( isset( $this->component_feature_map[ $component_id ] ) ) {
			return $this->component_feature_map[ $component_id ]['feature_id'];
		}

		return null;
	}

	/**
	 * Check if a component has been bridged.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id Component ID.
	 * @return bool True if bridged, false otherwise.
	 */
	public function is_component_bridged( $component_id ) {
		return isset( $this->bridged_components[ $component_id ] );
	}

	/**
	 * Get all bridged components.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Array of component_id => feature_id mappings.
	 */
	public function get_bridged_components() {
		return $this->bridged_components;
	}

	/**
	 * Get the component-feature map.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Component to feature mapping.
	 */
	public function get_component_feature_map() {
		return $this->component_feature_map;
	}

	/**
	 * Manually map a component to a feature.
	 *
	 * Useful for third-party plugins that want explicit control.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $component_id Component ID.
	 * @param string $feature_id   Feature ID.
	 * @param string $type         Component type: 'optional' or 'required'.
	 */
	public function map_component_to_feature( $component_id, $feature_id, $type = 'optional' ) {
		$this->component_feature_map[ $component_id ] = array(
			'type'       => $type,
			'feature_id' => $feature_id,
		);
		$this->bridged_components[ $component_id ] = $feature_id;
	}
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

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

/**
 * Get the feature ID for a component.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $component_id Component ID.
 * @return string|null Feature ID or null if not found.
 */
function bb_get_feature_for_component( $component_id ) {
	return bb_component_bridge()->get_feature_for_component( $component_id );
}

/**
 * Get the component ID for a feature.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @return string|null Component ID or null if not found.
 */
function bb_get_component_for_feature( $feature_id ) {
	return bb_component_bridge()->get_component_for_feature( $feature_id );
}
