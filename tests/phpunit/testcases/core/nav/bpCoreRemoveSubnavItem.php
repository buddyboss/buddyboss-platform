<?php

/**
 * @group core
 * @group nav
 */
class BP_Tests_Core_Nav_BpCoreRemoveSubnavItem extends BP_UnitTestCase {
	/**
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_should_remove_subnav_item() {
		$bp = bboss();

		$_bp_nav = $bp->bp_nav;
		$_bp_options_nav = $bp->bp_options_nav;

		bboss_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
		) );

		bboss_core_new_subnav_item( array(
			'name' => 'Bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'bar',
		) );

		$this->assertTrue( isset( $bp->bp_options_nav['foo']['bar'] ) );

		bboss_core_remove_subnav_item( 'foo', 'bar' );

		$this->assertFalse( isset( $bp->bp_options_nav['foo']['bar'] ) );

		$bp->bp_nav = $_bp_nav;
		$bp->bp_options_nav = $_bp_options_nav;
	}

	/**
	 * @expectedIncorrectUsage bp_nav
	 */
	public function test_should_fail_on_incorrect_parent() {
		$bp = bboss();

		$_bp_nav = $bp->bp_nav;
		$_bp_options_nav = $bp->bp_options_nav;

		bboss_core_new_nav_item( array(
			'name' => 'Foo',
			'slug' => 'foo',
			'screen_function' => 'foo',
		) );

		bboss_core_new_subnav_item( array(
			'name' => 'Bar',
			'slug' => 'bar',
			'parent_slug' => 'foo',
			'parent_url' => 'foo',
			'screen_function' => 'bar',
		) );

		$this->assertTrue( isset( $bp->bp_options_nav['foo']['bar'] ) );

		bboss_core_remove_subnav_item( 'bad-parent', 'bar' );

		$this->assertTrue( isset( $bp->bp_options_nav['foo']['bar'] ) );

		$bp->bp_nav = $_bp_nav;
		$bp->bp_options_nav = $_bp_options_nav;
	}

	public function test_backcompat_remove_group_nav_items() {
		$g1 = self::factory()->group->create();

		// In group context
		$g_obj = bboss_groups_get_group( $g1 );
		$this->go_to( bboss_get_group_permalink( $g_obj ) );

		bboss_core_new_subnav_item( array(
			'name' => 'Clam',
			'slug' => 'clam',
			'parent_slug' => bboss_get_current_group_slug(),
			'parent_url' => bboss_get_group_permalink( $g_obj ),
			'screen_function' => 'clam_subnav',
		) );

		bboss_core_remove_subnav_item( $g_obj->slug, 'clam' );

		$nav = bboss_get_nav_menu_items( 'groups' );
		$found = false;
		foreach ( $nav as $_nav ) {
			if ( 'clam' === $_nav->css_id ) {
				$found = true;
				break;
			}
		}

		$this->assertFalse( $found );
	}
}
