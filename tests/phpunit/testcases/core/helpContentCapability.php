<?php
/**
 * @group kb_capability
 */
class BB_Tests_Help_Content_Capability extends BP_UnitTestCase {

	public function test_default_requires_manage_options() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$sub   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$ep    = new BB_REST_Help_Content_Endpoint();

		wp_set_current_user( $admin );
		$this->assertTrue( $ep->get_item_permissions_check( new WP_REST_Request() ) );

		wp_set_current_user( $sub );
		$this->assertInstanceOf( 'WP_Error', $ep->get_item_permissions_check( new WP_REST_Request() ) );
	}

	public function test_capability_is_filterable() {
		$sub = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $sub );
		$ep = new BB_REST_Help_Content_Endpoint();

		// Subscribers have 'read'. Filtering the cap down to 'read' must let them through.
		add_filter( 'bb_help_content_capability', function () { return 'read'; } );
		$this->assertTrue( $ep->get_item_permissions_check( new WP_REST_Request() ) );
		remove_all_filters( 'bb_help_content_capability' );
	}
}
