<?php
/**
 * Integration Tests for Complete Feature Lifecycle
 *
 * @group integration
 * @group feature-lifecycle
 */
class BB_Tests_Feature_Lifecycle extends BP_UnitTestCase {

	/**
	 * Test complete feature lifecycle from registration to deactivation
	 */
	public function test_complete_feature_lifecycle() {
		// 1. Register feature
		bb_register_feature(
			'test_lifecycle',
			array(
				'label'       => 'Test Lifecycle Feature',
				'description' => 'Testing complete lifecycle',
				'category'    => 'community',
			)
		);

		// 2. Verify registration
		$feature = bb_feature_registry()->get_feature( 'test_lifecycle' );
		$this->assertNotEmpty( $feature );
		$this->assertEquals( 'Test Lifecycle Feature', $feature['label'] );

		// 3. Activate feature
		bb_feature_registry()->activate_feature( 'test_lifecycle' );

		// 4. Verify active status
		$this->assertTrue( bb_feature_registry()->is_feature_active( 'test_lifecycle' ) );

		// 5. Register settings
		bb_register_side_panel( 'test_lifecycle', 'settings', array( 'title' => 'Settings' ) );
		bb_register_feature_section( 'test_lifecycle', 'settings', 'main', array( 'title' => 'Main Section' ) );
		bb_register_feature_field(
			'test_lifecycle',
			'settings',
			'main',
			array(
				'name'    => 'test_lifecycle_option',
				'type'    => 'text',
				'default' => 'default_value',
				'label'   => 'Test Option',
			)
		);

		// 6. Verify settings structure
		$panels = bb_feature_registry()->get_side_panels( 'test_lifecycle' );
		$this->assertArrayHasKey( 'settings', $panels );

		$fields = bb_feature_registry()->get_fields( 'test_lifecycle', 'settings', 'main' );
		$this->assertNotEmpty( $fields );
		$this->assertEquals( 'test_lifecycle_option', $fields[0]['name'] );

		// 7. Save setting
		bp_update_option( 'test_lifecycle_option', 'new_value' );

		// 8. Verify setting saved
		$this->assertEquals( 'new_value', bp_get_option( 'test_lifecycle_option' ) );

		// 9. Deactivate feature
		bb_feature_registry()->deactivate_feature( 'test_lifecycle' );

		// 10. Verify inactive status
		$this->assertFalse( bb_feature_registry()->is_feature_active( 'test_lifecycle' ) );

		// 11. Verify storage updated
		$active_features = bp_get_option( 'bb-active-features', array() );
		$this->assertEquals( 0, $active_features['test_lifecycle'] );
	}

	/**
	 * Test multi-component feature lifecycle
	 */
	public function test_multi_component_feature_lifecycle() {
		// Register feature with multiple components
		bb_register_feature(
			'test_multi',
			array(
				'label'      => 'Multi Component Feature',
				'components' => array( 'component_a', 'component_b', 'component_c' ),
			)
		);

		// Activate
		bb_feature_registry()->activate_feature( 'test_multi' );

		// Verify all components activated
		$active_components = bp_get_option( 'bp-active-components', array() );
		$this->assertEquals( 1, $active_components['component_a'] );
		$this->assertEquals( 1, $active_components['component_b'] );
		$this->assertEquals( 1, $active_components['component_c'] );

		// Deactivate
		bb_feature_registry()->deactivate_feature( 'test_multi' );

		// Verify all components deactivated
		$active_components = bp_get_option( 'bp-active-components', array() );
		$this->assertEquals( 0, $active_components['component_a'] );
		$this->assertEquals( 0, $active_components['component_b'] );
		$this->assertEquals( 0, $active_components['component_c'] );
	}

	/**
	 * Test integration feature lifecycle
	 */
	public function test_integration_feature_lifecycle() {
		// Register integration
		bb_register_integration(
			'test_integration',
			array(
				'label'                 => 'Test Integration',
				'required_plugin_const' => 'TEST_INTEGRATION_VERSION',
				'license_tier'          => 'free',
			)
		);

		$feature = bb_feature_registry()->get_feature( 'test_integration' );
		$this->assertNotEmpty( $feature );
		$this->assertEquals( 'integrations', $feature['category'] );

		// Should not be available (plugin constant doesn't exist)
		$this->assertFalse( bb_feature_registry()->is_feature_available( 'test_integration' ) );

		// Define the constant to simulate plugin installation
		if ( ! defined( 'TEST_INTEGRATION_VERSION' ) ) {
			define( 'TEST_INTEGRATION_VERSION', '1.0.0' );
		}

		// Now should be available
		$this->assertTrue( bb_feature_registry()->is_feature_available( 'test_integration' ) );
	}

	/**
	 * Test feature with dependencies
	 */
	public function test_feature_with_dependencies() {
		// Register parent feature
		bb_register_feature(
			'parent_feature',
			array(
				'label' => 'Parent Feature',
			)
		);

		// Activate parent
		bb_feature_registry()->activate_feature( 'parent_feature' );

		// Register dependent feature
		bb_register_feature(
			'dependent_feature',
			array(
				'label'              => 'Dependent Feature',
				'is_active_callback' => function() {
					return bb_feature_registry()->is_feature_active( 'parent_feature' );
				},
			)
		);

		// Dependent should be able to activate
		bb_feature_registry()->activate_feature( 'dependent_feature' );
		$this->assertTrue( bb_feature_registry()->is_feature_active( 'dependent_feature' ) );

		// Deactivate parent
		bb_feature_registry()->deactivate_feature( 'parent_feature' );

		// Dependent should now report as inactive (due to callback)
		$this->assertFalse( bb_feature_registry()->is_feature_active( 'dependent_feature' ) );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown() {
		parent::tearDown();

		// Clean up all test options
		bp_delete_option( 'test_lifecycle_option' );
		bp_delete_option( 'bb-active-features' );
		bp_delete_option( 'bp-active-components' );
	}
}
