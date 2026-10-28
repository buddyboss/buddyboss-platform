<?php
/**
 * @group members
 * @group cache
 */
class BP_Tests_Members_Remove_Data_Cache extends BP_UnitTestCase {

	/**
	 * Removing a member's data must not evict the whole object cache.
	 */
	public function test_bp_core_remove_data_does_not_flush_unrelated_cache() {
		$user_id = self::factory()->user->create();

		wp_cache_set( 'prod_9882_unrelated', 'warm', 'bp_tests' );
		wp_cache_set( 'bp_user_' . $user_id, 'cached', 'bp' );
		wp_cache_set( 'bp_total_member_count', 10, 'bp' );

		bp_core_remove_data( $user_id );

		$this->assertSame( 'warm', wp_cache_get( 'prod_9882_unrelated', 'bp_tests' ) );
		$this->assertFalse( wp_cache_get( 'bp_user_' . $user_id, 'bp' ) );
		$this->assertFalse( wp_cache_get( 'bp_total_member_count', 'bp' ) );
	}
}
