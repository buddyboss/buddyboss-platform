<?php
/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreMaybeHookNewSubnavScreenFunction extends BP_UnitTestCase {

	public function test_user_has_access_true_no_callable_function() {
		$subnav_item = array(
			'user_has_access' => true,
			'screen_function' => '123foo456',
		);

		$expected = array(
			'status' => 'failure',
		);

		$this->assertSame( $expected, bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item ) );
	}

	public function test_user_has_access_true_callable_function() {
		$subnav_item = array(
			'user_has_access' => true,
			'screen_function' => 'wptexturize', // any old callable function
		);

		$expected = array(
			'status' => 'success',
		);

		$this->assertSame( $expected, bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item ) );

		// clean up
		remove_action( 'bp_screens', 'wptexturize', 3 );
	}

	public function test_user_has_access_false_user_logged_out() {
		$old_current_user = get_current_user_id();
		$this->set_current_user( 0 );

		$subnav_item = array(
			'user_has_access' => false,
		);

		$expected = array(
			'status' => 'failure',
			'redirect_args' => array(),
		);

		$this->assertSame( $expected, bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_user_has_access_false_user_logged_in_my_profile() {
		$u = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$this->go_to( bboss_core_get_user_domain( $u ) );

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bboss_core_get_user_domain( $u ), $found['redirect_args']['root'] );

		$this->set_current_user( $old_current_user );
	}

	public function test_user_has_access_false_user_logged_in_others_profile_default_component_accessible() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$this->go_to( bboss_core_get_user_domain( $u2 ) );

		$old_bp_nav = bboss()->bp_nav;
		$old_default_component = bboss()->default_component;
		bboss()->default_component = 'foo';

		bboss_core_new_nav_item( array(
			'slug' => 'foo',
			'name' => 'Foo',
			'screen_function' => 'foo',
			'default_subnav_item' => 'bar',
		) );

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item );

		// Clean up
		$this->set_current_user( $old_current_user );
		bboss()->default_component = $old_default_component;
		bboss()->bp_nav = $old_bp_nav;

		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bboss_core_get_user_domain( $u2 ), $found['redirect_args']['root'] );
	}

	public function test_user_has_access_false_user_logged_in_others_profile_default_component_not_accessible() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u1 );

		$this->go_to( bboss_core_get_user_domain( $u2 ) );

		$old_bp_nav = bboss()->bp_nav;
		$old_default_component = bboss()->default_component;
		bboss()->default_component = 'foo';

		bboss_core_new_nav_item( array(
			'slug' => 'foo',
			'name' => 'Foo',
			'screen_function' => 'foo',
			'default_subnav_item' => 'bar',
			'show_for_displayed_user' => false,
		) );

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item );

		// Clean up
		$this->set_current_user( $old_current_user );
		bboss()->default_component = $old_default_component;
		bboss()->bp_nav = $old_bp_nav;

		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bboss_core_get_user_domain( $u2 ) . bboss_get_activity_slug() . '/', $found['redirect_args']['root'] );
	}

	public function test_user_has_access_false_user_logged_in_group() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$group = bboss_groups_get_group( $g );

		$this->go_to( bboss_get_group_permalink( $group ) );

		$subnav_item = array(
			'user_has_access' => false,
			'no_access_url' => bboss_get_group_permalink( $group ),
		);

		// Just test relevant info
		$found = bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bboss_get_group_permalink( $group ), $found['redirect_args']['root'] );

		// Clean up
		$this->set_current_user( $old_current_user );
	}

	public function test_user_has_access_false_user_logged_in_group_no_redirect_url_provided() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$old_current_user = get_current_user_id();
		$this->set_current_user( $u );

		$group = bboss_groups_get_group( $g );

		$this->go_to( bboss_get_group_permalink( $group ) );

		$subnav_item = array(
			'user_has_access' => false,
		);

		// Just test relevant info
		$found = bboss_core_maybe_hook_new_subnav_screen_function( $subnav_item );
		$this->assertSame( 'failure', $found['status'] );
		$this->assertSame( bboss_get_root_domain(), $found['redirect_args']['root'] );

		// Clean up
		$this->set_current_user( $old_current_user );
	}
}
