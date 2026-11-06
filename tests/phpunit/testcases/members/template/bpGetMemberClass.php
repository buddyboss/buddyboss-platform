<?php

/**
 * @group members
 */
class BP_Tests_Members_Template_BpGetMemberClass extends BP_UnitTestCase {
	/**
	 * @ticket BP6996
	 */
	public function test_should_contain_member_type_classes() {
		bboss()->members->types = array();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );

		$u = self::factory()->user->create();
		bboss_set_member_type( $u, 'bar' );

		if ( bboss_has_members( array( 'include' => array( $u ) ) ) ) {
			while ( bboss_members() ) {
				bboss_the_member();
				$found = bboss_get_member_class();
			}
		}

		global $members_template;
		unset( $members_template );
		bboss()->members->types = array();

		$this->assertContains( 'member-type-bar', $found );
		$this->assertNotContains( 'member-type-foo', $found );
	}
}
