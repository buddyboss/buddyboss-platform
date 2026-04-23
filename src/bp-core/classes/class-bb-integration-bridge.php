<?php
/**
 * BuddyBoss Integration Bridge Class.
 *
 * Bridges integrations (like LearnDash, Pusher, etc.) with the Feature Registry.
 * Allows integrations to be enabled/disabled via the admin settings 2.0 interface.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Integration Bridge class.
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Integration_Bridge {

	/**
	 * Singleton instance.
	 *
	 * @var BB_Integration_Bridge|null
	 */
	private static $instance = null;

	/**
	 * Array of integration IDs that are managed by the feature system.
	 *
	 * @var array
	 */
	private $managed_integrations = array();

	/**
	 * Get the singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return BB_Integration_Bridge
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
		// Hook early to filter integrations before they are loaded.
		add_filter( 'bp_integrations', array( $this, 'filter_integrations' ), 5 );

		// Hook into integration activation check.
		add_filter( 'bb_integration_is_activated', array( $this, 'check_feature_status' ), 10, 2 );

		// Sync feature activation/deactivation with integration status.
		add_action( 'bb_feature_activated', array( $this, 'on_feature_activated' ) );
		add_action( 'bb_feature_deactivated', array( $this, 'on_feature_deactivated' ) );
	}

	/**
	 * Register an integration to be managed by the feature system.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $integration_id The integration ID (e.g., 'learndash').
	 * @param string $feature_id     The feature ID in the registry.
	 */
	public function register_managed_integration( $integration_id, $feature_id = null ) {
		$this->managed_integrations[ $integration_id ] = $feature_id ?: $integration_id;
	}

	/**
	 * Check if an integration is managed by the feature system.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $integration_id The integration ID.
	 * @return bool
	 */
	public function is_managed_integration( $integration_id ) {
		return isset( $this->managed_integrations[ $integration_id ] );
	}

	/**
	 * Get the feature ID for an integration.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $integration_id The integration ID.
	 * @return string|null
	 */
	public function get_feature_id( $integration_id ) {
		return isset( $this->managed_integrations[ $integration_id ] )
			? $this->managed_integrations[ $integration_id ]
			: null;
	}

	/**
	 * Filter the list of integrations to load based on feature status.
	 *
	 * Note: This doesn't prevent loading of integration loaders, but can be used
	 * to modify the list. The actual loading control happens in check_feature_status().
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $integrations List of integration slugs.
	 * @return array Filtered list of integrations.
	 */
	public function filter_integrations( $integrations ) {
		// We don't filter here - we let the integration loader files load,
		// but the BP_Integration::is_activated() check will fail if feature is disabled.
		return $integrations;
	}

	/**
	 * Check feature status when an integration checks if it's activated.
	 *
	 * This filter is called from BP_Integration::is_activated().
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param bool   $is_activated Whether the integration is activated.
	 * @param string $integration_id The integration ID.
	 * @return bool
	 */
	public function check_feature_status( $is_activated, $integration_id ) {
		// If the integration is not managed by feature system, return original value.
		if ( ! $this->is_managed_integration( $integration_id ) ) {
			return $is_activated;
		}

		// If the required plugin is not installed, keep it deactivated.
		if ( ! $is_activated ) {
			return false;
		}

		// Check feature status in the registry.
		$feature_id = $this->get_feature_id( $integration_id );
		if ( ! $feature_id ) {
			return $is_activated;
		}

		// Check if feature is enabled in admin settings.
		return $this->is_integration_feature_enabled( $feature_id );
	}

	/**
	 * Check if an integration feature is enabled.
	 *
	 * Uses the unified bb-active-features storage (single source of truth).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 * @return bool
	 */
	public function is_integration_feature_enabled( $feature_id ) {
		// Primary storage: bb-active-features (unified with all features).
		$active_features = bp_get_option( 'bb-active-features', array() );

		// If set in bb-active-features, use that value.
		if ( isset( $active_features[ $feature_id ] ) ) {
			return (bool) $active_features[ $feature_id ];
		}

		// Migration fallback: check legacy bp-active-components.
		$active_components = bp_get_option( 'bp-active-components', array() );
		if ( isset( $active_components[ $feature_id ] ) ) {
			return (bool) $active_components[ $feature_id ];
		}

		// Default to enabled for backward compatibility (existing integrations).
		return true;
	}

	/**
	 * Enable an integration feature.
	 *
	 * Note: This is typically called via bb_feature_activated action.
	 * Direct calls should go through BB_Feature_Registry::activate_feature().
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 * @return bool
	 */
	public function enable_integration( $feature_id ) {
		// Update unified storage.
		$active_features = bp_get_option( 'bb-active-features', array() );
		$active_features[ $feature_id ] = 1;
		bp_update_option( 'bb-active-features', $active_features );

		// Sync to legacy storage for backward compatibility.
		$active_components = bp_get_option( 'bp-active-components', array() );
		$active_components[ $feature_id ] = 1;
		return bp_update_option( 'bp-active-components', $active_components );
	}

	/**
	 * Disable an integration feature.
	 *
	 * Note: This is typically called via bb_feature_deactivated action.
	 * Direct calls should go through BB_Feature_Registry::deactivate_feature().
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 * @return bool
	 */
	public function disable_integration( $feature_id ) {
		// Update unified storage.
		$active_features = bp_get_option( 'bb-active-features', array() );
		$active_features[ $feature_id ] = 0;
		bp_update_option( 'bb-active-features', $active_features );

		// Sync to legacy storage for backward compatibility.
		$active_components = bp_get_option( 'bp-active-components', array() );
		unset( $active_components[ $feature_id ] );
		return bp_update_option( 'bp-active-components', $active_components );
	}

	/**
	 * Handle feature activation.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 */
	public function on_feature_activated( $feature_id ) {
		// Check if this feature is an integration.
		$integration_id = array_search( $feature_id, $this->managed_integrations, true );
		if ( false !== $integration_id ) {
			$this->enable_integration( $feature_id );
		}
	}

	/**
	 * Handle feature deactivation.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id The feature ID.
	 */
	public function on_feature_deactivated( $feature_id ) {
		// Check if this feature is an integration.
		$integration_id = array_search( $feature_id, $this->managed_integrations, true );
		if ( false !== $integration_id ) {
			$this->disable_integration( $feature_id );
		}
	}

	/**
	 * Get all managed integrations.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array
	 */
	public function get_managed_integrations() {
		return $this->managed_integrations;
	}
}

/**
 * Get the Integration Bridge instance.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return BB_Integration_Bridge
 */
function bb_integration_bridge() {
	return BB_Integration_Bridge::instance();
}
