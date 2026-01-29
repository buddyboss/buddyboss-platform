<?php
/**
 * Tests for BB_Feature_Registry
 *
 * @group features
 * @group feature-registry
 */
class BB_Tests_Feature_Registry extends BP_UnitTestCase {

	/**
	 * Test feature registration
	 */
	public function test_register_feature() {
		bb_register_feature(
			'test_feature',
			array(
				'label'        => 'Test Feature',
				'description'  => 'Test description',
				'category'     => 'community',
				'license_tier' => 'free',
			)
		);

		$feature = bb_feature_registry()->get_feature( 'test_feature' );

		$this->assertNotEmpty( $feature );
		$this->assertEquals( 'Test Feature', $feature['label'] );
		$this->assertEquals( 'community', $feature['category'] );
	}

	/**
	 * Test feature activation updates storage
	 */
	public function test_activate_feature_updates_storage() {
		bb_register_feature( 'test_feature', array() );

		bb_feature_registry()->activate_feature( 'test_feature' );

		$active_features = bp_get_option( 'bb-active-features', array() );
		$this->assertEquals( 1, $active_features['test_feature'] );

		// Check legacy component sync
		$active_components = bp_get_option( 'bp-active-components', array() );
		$this->assertEquals( 1, $active_components['test_feature'] );
	}

	/**
	 * Test feature deactivation
	 */
	public function test_deactivate_feature_updates_storage() {
		bb_register_feature( 'test_feature', array() );
		bb_feature_registry()->activate_feature( 'test_feature' );

		bb_feature_registry()->deactivate_feature( 'test_feature' );

		$active_features = bp_get_option( 'bb-active-features', array() );
		$this->assertEquals( 0, $active_features['test_feature'] );
	}

	/**
	 * Test multi-component feature
	 */
	public function test_multi_component_feature() {
		bb_register_feature(
			'media',
			array(
				'label'      => 'Media',
				'components' => array( 'media', 'document', 'video' ),
			)
		);

		bb_feature_registry()->activate_feature( 'media' );

		$active_components = bp_get_option( 'bp-active-components', array() );

		$this->assertEquals( 1, $active_components['media'] );
		$this->assertEquals( 1, $active_components['document'] );
		$this->assertEquals( 1, $active_components['video'] );
	}

	/**
	 * Test side panel registration
	 */
	public function test_register_side_panel() {
		bb_register_feature( 'test_feature', array() );

		bb_register_side_panel(
			'test_feature',
			'settings',
			array(
				'title'      => 'Settings',
				'is_default' => true,
			)
		);

		$panels = bb_feature_registry()->get_side_panels( 'test_feature' );

		$this->assertArrayHasKey( 'settings', $panels );
		$this->assertEquals( 'Settings', $panels['settings']['title'] );
		$this->assertTrue( $panels['settings']['is_default'] );
	}

	/**
	 * Test field registration
	 */
	public function test_register_feature_field() {
		bb_register_feature( 'test_feature', array() );
		bb_register_side_panel( 'test_feature', 'settings', array() );
		bb_register_feature_section( 'test_feature', 'settings', 'main', array() );

		bb_register_feature_field(
			'test_feature',
			'settings',
			'main',
			array(
				'name'    => 'test_option',
				'type'    => 'toggle',
				'label'   => 'Test Option',
				'default' => '1',
			)
		);

		$fields = bb_feature_registry()->get_fields( 'test_feature', 'settings', 'main' );

		$this->assertNotEmpty( $fields );
		$this->assertEquals( 'toggle', $fields[0]['type'] );
		$this->assertEquals( 'test_option', $fields[0]['name'] );
	}

	/**
	 * Test feature is active check
	 */
	public function test_is_feature_active() {
		bb_register_feature( 'test_feature', array() );

		// Should be inactive by default
		$this->assertFalse( bb_feature_registry()->is_feature_active( 'test_feature' ) );

		// Activate and check
		bb_feature_registry()->activate_feature( 'test_feature' );
		$this->assertTrue( bb_feature_registry()->is_feature_active( 'test_feature' ) );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown() {
		parent::tearDown();

		// Clean up options
		bp_delete_option( 'bb-active-features' );
		bp_delete_option( 'bp-active-components' );
	}
}
