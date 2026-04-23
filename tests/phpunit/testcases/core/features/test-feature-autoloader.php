<?php
/**
 * Tests for BB_Feature_Autoloader
 *
 * @group features
 * @group autoloader
 */
class BB_Tests_Feature_Autoloader extends BP_UnitTestCase {

	/**
	 * Test class gating for inactive features
	 */
	public function test_class_gating_prevents_loading_inactive_feature_classes() {
		// Ensure activity is inactive
		$active_components = bp_get_option( 'bp-active-components', array() );
		unset( $active_components['activity'] );
		bp_update_option( 'bp-active-components', $active_components );

		// Try to autoload BP_Activity_* class
		$result = BB_Feature_Autoloader::autoload( 'BP_Activity_Test_Class' );

		$this->assertFalse( $result );
	}

	/**
	 * Test custom class mapping
	 */
	public function test_add_custom_feature_class_map() {
		BB_Feature_Autoloader::add_feature_class_map( '/^MyPlugin_/', 'my_feature' );

		$map = BB_Feature_Autoloader::get_feature_class_map();

		$this->assertArrayHasKey( '/^MyPlugin_/', $map );
		$this->assertEquals( 'my_feature', $map['/^MyPlugin_/'] );
	}

	/**
	 * Test feature discovery
	 */
	public function test_discover_features_loads_feature_configs() {
		// Mock feature directory structure
		$feature_dir = buddypress()->plugin_dir . 'src/features/community/test_feature/';

		if ( ! file_exists( $feature_dir ) ) {
			mkdir( $feature_dir, 0755, true );
		}

		file_put_contents(
			$feature_dir . 'feature-config.php',
			'<?php bb_register_feature("test_feature", array("label" => "Test"));'
		);

		BB_Feature_Autoloader::discover_features();

		$feature = bb_feature_registry()->get_feature( 'test_feature' );
		$this->assertNotEmpty( $feature );

		// Clean up
		unlink( $feature_dir . 'feature-config.php' );
		rmdir( $feature_dir );
	}

	/**
	 * Test that autoloader validates class names (security)
	 */
	public function test_autoloader_rejects_invalid_class_names() {
		// Test with invalid characters
		$result = BB_Feature_Autoloader::autoload( 'BP_Activity_<script>' );
		$this->assertFalse( $result );

		$result = BB_Feature_Autoloader::autoload( 'BP_Activity_../../etc/passwd' );
		$this->assertFalse( $result );
	}

	/**
	 * Test feature class map filter
	 */
	public function test_feature_class_map_filter() {
		add_filter( 'bb_feature_class_map', function( $map ) {
			$map['/^Test_Plugin_/'] = 'test_plugin';
			return $map;
		});

		$map = BB_Feature_Autoloader::get_feature_class_map();
		$this->assertArrayHasKey( '/^Test_Plugin_/', $map );

		remove_all_filters( 'bb_feature_class_map' );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown() {
		parent::tearDown();

		// Clean up any test feature directories
		$test_feature_dir = buddypress()->plugin_dir . 'src/features/community/test_feature/';
		if ( file_exists( $test_feature_dir ) ) {
			if ( file_exists( $test_feature_dir . 'feature-config.php' ) ) {
				unlink( $test_feature_dir . 'feature-config.php' );
			}
			rmdir( $test_feature_dir );
		}
	}
}
