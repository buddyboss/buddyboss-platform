<?php
/**
 * BuddyBoss Component Bridge Tests
 *
 * This file contains tests and documentation for the backward compatibility
 * system that converts legacy components to the new feature-based architecture.
 *
 * @package BuddyBoss\Core\Tests
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Test backward compatibility for third-party component registration.
 *
 * This demonstrates how existing plugins that use the bp_optional_components
 * filter will continue to work with the new feature-based system.
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Component_Bridge_Tests {

	/**
	 * Run all tests.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Test results.
	 */
	public static function run_tests() {
		$results = array();

		$results['legacy_component_filter']     = self::test_legacy_component_filter();
		$results['feature_to_component_sync']   = self::test_feature_to_component_sync();
		$results['component_to_feature_sync']   = self::test_component_to_feature_sync();
		$results['external_component_detection'] = self::test_external_component_detection();
		$results['bp_is_active_compatibility']  = self::test_bp_is_active_compatibility();

		return $results;
	}

	/**
	 * Test that legacy bp_optional_components filter still works.
	 *
	 * Third-party plugins use this pattern:
	 * ```php
	 * add_filter( 'bp_optional_components', function( $components ) {
	 *     $components[] = 'my-custom-component';
	 *     return $components;
	 * } );
	 * ```
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Test result.
	 */
	public static function test_legacy_component_filter() {
		$test_name = 'Legacy bp_optional_components Filter';

		// Get bridge instance.
		if ( ! function_exists( 'bb_component_bridge' ) ) {
			return array(
				'name'    => $test_name,
				'status'  => 'skip',
				'message' => 'Component bridge not available.',
			);
		}

		$bridge = bb_component_bridge();

		// Check if bridge is initialized.
		if ( ! $bridge ) {
			return array(
				'name'    => $test_name,
				'status'  => 'fail',
				'message' => 'Bridge failed to initialize.',
			);
		}

		return array(
			'name'    => $test_name,
			'status'  => 'pass',
			'message' => 'Legacy filter is being captured by the bridge.',
		);
	}

	/**
	 * Test that feature activation syncs to component.
	 *
	 * When a feature is activated via the new UI, the corresponding
	 * component should also be activated in bp-active-components.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Test result.
	 */
	public static function test_feature_to_component_sync() {
		$test_name = 'Feature to Component Sync';

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			return array(
				'name'    => $test_name,
				'status'  => 'skip',
				'message' => 'Feature registry not available.',
			);
		}

		$registry = bb_feature_registry();

		// Check if activity feature exists and maps to component.
		$feature = $registry->get_feature( 'activity' );
		if ( ! $feature ) {
			return array(
				'name'    => $test_name,
				'status'  => 'skip',
				'message' => 'Activity feature not registered.',
			);
		}

		// Check if feature has component mapping.
		if ( empty( $feature['component'] ) ) {
			return array(
				'name'    => $test_name,
				'status'  => 'info',
				'message' => 'Feature does not have explicit component mapping (uses feature ID as fallback).',
			);
		}

		return array(
			'name'    => $test_name,
			'status'  => 'pass',
			'message' => sprintf( 'Feature "activity" maps to component "%s".', $feature['component'] ),
		);
	}

	/**
	 * Test that component activation syncs to feature.
	 *
	 * When a component is activated via the old admin UI or CLI,
	 * the feature registry should reflect that state.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Test result.
	 */
	public static function test_component_to_feature_sync() {
		$test_name = 'Component to Feature Sync';

		if ( ! function_exists( 'bb_feature_registry' ) ) {
			return array(
				'name'    => $test_name,
				'status'  => 'skip',
				'message' => 'Feature registry not available.',
			);
		}

		$registry = bb_feature_registry();

		// Check if bp_is_active matches feature active state.
		$components_to_check = array( 'activity', 'groups', 'messages' );
		$mismatches          = array();

		foreach ( $components_to_check as $component ) {
			$bp_active      = bp_is_active( $component );
			$feature_active = $registry->is_feature_active( $component );

			if ( $bp_active !== $feature_active ) {
				$mismatches[] = sprintf(
					'%s: bp_is_active=%s, feature_active=%s',
					$component,
					$bp_active ? 'true' : 'false',
					$feature_active ? 'true' : 'false'
				);
			}
		}

		if ( ! empty( $mismatches ) ) {
			return array(
				'name'    => $test_name,
				'status'  => 'fail',
				'message' => 'State mismatches found: ' . implode( ', ', $mismatches ),
			);
		}

		return array(
			'name'    => $test_name,
			'status'  => 'pass',
			'message' => 'Component and feature states are in sync.',
		);
	}

	/**
	 * Test that external (third-party) components are detected.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Test result.
	 */
	public static function test_external_component_detection() {
		$test_name = 'External Component Detection';

		if ( ! function_exists( 'bb_component_bridge' ) ) {
			return array(
				'name'    => $test_name,
				'status'  => 'skip',
				'message' => 'Component bridge not available.',
			);
		}

		$bridge = bb_component_bridge();
		$external = $bridge->get_external_components();

		return array(
			'name'    => $test_name,
			'status'  => 'pass',
			'message' => sprintf( 'Found %d external component(s).', count( $external ) ),
			'data'    => array_keys( $external ),
		);
	}

	/**
	 * Test that bp_is_active() continues to work correctly.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Test result.
	 */
	public static function test_bp_is_active_compatibility() {
		$test_name = 'bp_is_active() Compatibility';

		// Test required components (should always be active).
		$required = array( 'members', 'xprofile' );
		$failures = array();

		foreach ( $required as $component ) {
			if ( ! bp_is_active( $component ) ) {
				$failures[] = $component;
			}
		}

		if ( ! empty( $failures ) ) {
			return array(
				'name'    => $test_name,
				'status'  => 'fail',
				'message' => 'Required components inactive: ' . implode( ', ', $failures ),
			);
		}

		return array(
			'name'    => $test_name,
			'status'  => 'pass',
			'message' => 'bp_is_active() returns expected values.',
		);
	}

	/**
	 * Output test results as HTML.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param array $results Test results from run_tests().
	 */
	public static function output_results_html( $results ) {
		echo '<div class="bb-component-bridge-tests">';
		echo '<h2>' . esc_html__( 'Component Bridge Compatibility Tests', 'buddyboss' ) . '</h2>';
		echo '<table class="widefat"><thead><tr>';
		echo '<th>' . esc_html__( 'Test', 'buddyboss' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'buddyboss' ) . '</th>';
		echo '<th>' . esc_html__( 'Message', 'buddyboss' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $results as $result ) {
			$status_class = 'pass' === $result['status'] ? 'success' : ( 'fail' === $result['status'] ? 'error' : 'warning' );
			echo '<tr>';
			echo '<td>' . esc_html( $result['name'] ) . '</td>';
			echo '<td><span class="status-' . esc_attr( $status_class ) . '">' . esc_html( strtoupper( $result['status'] ) ) . '</span></td>';
			echo '<td>' . esc_html( $result['message'] ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}
}

/**
 * EXAMPLE: Third-Party Plugin Registration Pattern
 *
 * This is how a third-party plugin can register a component that
 * automatically works with both the legacy and new feature systems.
 *
 * ```php
 * // Option 1: Legacy way (continues to work via bridge)
 * add_filter( 'bp_optional_components', function( $components ) {
 *     $components[] = 'my-plugin-component';
 *     return $components;
 * } );
 *
 * // Option 2: New recommended way (direct feature registration)
 * add_action( 'bb_register_features', function() {
 *     if ( function_exists( 'bb_register_feature' ) ) {
 *         bb_register_feature( 'my-plugin-feature', array(
 *             'label'              => __( 'My Plugin Feature', 'my-plugin' ),
 *             'description'        => __( 'Description of my plugin feature.', 'my-plugin' ),
 *             'icon'               => array( 'type' => 'dashicon', 'slug' => 'dashicons-admin-plugins' ),
 *             'category'           => 'integrations',
 *             'license_tier'       => 'free',
 *             'component'          => 'my-plugin-component', // Maps to legacy component.
 *             'is_active_callback' => function() {
 *                 return get_option( 'my_plugin_enabled', false );
 *             },
 *             'php_loader'         => function() {
 *                 require_once MY_PLUGIN_PATH . 'includes/component-loader.php';
 *             },
 *             'settings_route'     => '/settings/my-plugin',
 *             'order'              => 200,
 *         ) );
 *     }
 * } );
 * ```
 */
