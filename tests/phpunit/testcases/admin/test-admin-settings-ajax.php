<?php
/**
 * Tests for BB_Admin_Settings_Ajax
 *
 * @group admin
 * @group ajax
 * @group settings-ajax
 */
class BB_Tests_Admin_Settings_Ajax extends BP_UnitTestCase {

	protected $admin_user;
	protected $ajax_handler;

	public function setUp() {
		parent::setUp();

		// Create admin user
		$this->admin_user = $this->factory->user->create(
			array( 'role' => 'administrator' )
		);
		wp_set_current_user( $this->admin_user );

		// Initialize AJAX handler
		if ( class_exists( 'BB_Admin_Settings_Ajax' ) ) {
			$this->ajax_handler = new BB_Admin_Settings_Ajax();
		}
	}

	/**
	 * Test get features returns all features
	 */
	public function test_get_features_returns_all_features() {
		if ( ! $this->ajax_handler ) {
			$this->markTestSkipped( 'BB_Admin_Settings_Ajax class not available' );
		}

		// Register test feature
		bb_register_feature(
			'test_feature',
			array(
				'label'    => 'Test Feature',
				'category' => 'community',
			)
		);

		// Mock AJAX request
		$_POST['nonce'] = wp_create_nonce( 'bb_admin_settings_2_0' );

		try {
			$this->ajax_handler->get_features();
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected exception
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'data', $response );
		$this->assertNotEmpty( $response['data'] );
	}

	/**
	 * Test activate feature requires nonce
	 */
	public function test_activate_feature_requires_valid_nonce() {
		if ( ! $this->ajax_handler ) {
			$this->markTestSkipped( 'BB_Admin_Settings_Ajax class not available' );
		}

		$_POST['nonce'] = 'invalid_nonce';
		$_POST['feature_id'] = 'test_feature';

		try {
			$this->ajax_handler->activate_feature();
		} catch ( WPAjaxDieStopException $e ) {
			// Expected exception
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'] );
	}

	/**
	 * Test activate feature requires admin capability
	 */
	public function test_activate_feature_requires_admin_capability() {
		if ( ! $this->ajax_handler ) {
			$this->markTestSkipped( 'BB_Admin_Settings_Ajax class not available' );
		}

		// Switch to non-admin user
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$_POST['nonce'] = wp_create_nonce( 'bb_admin_settings_2_0' );
		$_POST['feature_id'] = 'test_feature';

		try {
			$this->ajax_handler->activate_feature();
		} catch ( WPAjaxDieStopException $e ) {
			// Expected exception
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'] );
		if ( isset( $response['data']['status'] ) ) {
			$this->assertEquals( 403, $response['data']['status'] );
		}
	}

	/**
	 * Test save feature settings
	 */
	public function test_save_feature_settings() {
		if ( ! $this->ajax_handler ) {
			$this->markTestSkipped( 'BB_Admin_Settings_Ajax class not available' );
		}

		bb_register_feature( 'test_feature', array() );
		bb_register_side_panel( 'test_feature', 'settings', array() );
		bb_register_feature_section( 'test_feature', 'settings', 'main', array() );
		bb_register_feature_field(
			'test_feature',
			'settings',
			'main',
			array(
				'name' => 'test_option',
				'type' => 'text',
			)
		);

		$_POST['nonce'] = wp_create_nonce( 'bb_admin_settings_2_0' );
		$_POST['feature_id'] = 'test_feature';
		$_POST['settings'] = json_encode( array( 'test_option' => 'test_value' ) );

		try {
			$this->ajax_handler->save_feature_settings();
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected exception
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertTrue( $response['success'] );
		$this->assertEquals( 'test_value', bp_get_option( 'test_option' ) );
	}

	/**
	 * Test feature activation fires action hooks
	 */
	public function test_feature_activation_fires_hooks() {
		if ( ! $this->ajax_handler ) {
			$this->markTestSkipped( 'BB_Admin_Settings_Ajax class not available' );
		}

		$hook_fired = false;

		add_action( 'bb_feature_activated', function( $feature_id ) use ( &$hook_fired ) {
			if ( $feature_id === 'test_feature' ) {
				$hook_fired = true;
			}
		});

		bb_register_feature( 'test_feature', array() );
		bb_feature_registry()->activate_feature( 'test_feature' );

		$this->assertTrue( $hook_fired, 'bb_feature_activated hook should fire' );

		remove_all_actions( 'bb_feature_activated' );
	}

	public function tearDown() {
		parent::tearDown();

		// Clean up options
		bp_delete_option( 'test_option' );
		bp_delete_option( 'bb-active-features' );
		bp_delete_option( 'bp-active-components' );

		// Reset $_POST
		$_POST = array();
	}
}
