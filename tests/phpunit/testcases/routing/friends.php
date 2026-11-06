<?php
/**
 * @group friends
 * @group routing
 */
class BP_Tests_Routing_Friends extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();

		$this->old_current_user = get_current_user_id();
		$this->set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	function test_member_friends() {
		$this->go_to( bboss_core_get_user_domain( bboss_loggedin_user_id() ) . bboss_get_friends_slug() );
		$this->assertTrue( bboss_is_user_friends() );
	}

	function test_member_friends_requests() {
		$this->go_to( bboss_core_get_user_domain( bboss_loggedin_user_id() ) . bboss_get_friends_slug()  . '/requests' );
		$this->assertTrue( bboss_is_user_friend_requests() );
	}
}
