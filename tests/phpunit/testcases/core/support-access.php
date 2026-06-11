<?php

/**
 * Tests for the BuddyBoss Support Access account exclusion from member queries.
 *
 * @group core
 * @group support_access
 */
class BB_Tests_Support_Access extends BP_UnitTestCase {

	/**
	 * Ensure the BB_Support_Access singleton (and its query filter) is loaded.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'BB_Support_Access' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-support-access.php';
		}

		// Self-boots and registers the bp_user_query_uid_clauses filter.
		BB_Support_Access::instance();
	}

	/**
	 * Create a stand-in support account flagged the same way the real one is.
	 *
	 * Mirrors BB_Support_Access::get_support_user() without going through the
	 * token flow, so the exclusion can be exercised in isolation.
	 *
	 * @return int Support user ID.
	 */
	protected function create_support_user() {
		$user_id = self::factory()->user->create(
			array(
				'user_login'   => 'buddyboss-support',
				'user_email'   => 'support@buddyboss.com',
				'display_name' => 'BuddyBoss Support',
				'role'         => 'administrator',
			)
		);

		update_user_meta( $user_id, '_bb_support_access_user', 1 );

		return (int) $user_id;
	}

	/**
	 * The support account must not appear in a BP_User_Query result set.
	 *
	 * /buddyboss/v1/members is backed by BP_User_Query, so excluding it here
	 * covers both the member directory and the REST endpoint.
	 */
	public function test_support_user_excluded_from_user_query() {
		$support_id = $this->create_support_user();
		$regular_id = self::factory()->user->create();

		$q       = new BP_User_Query();
		$results = is_array( $q->results ) ? array_values( $q->results ) : array();
		$ids     = wp_list_pluck( $results, 'ID' );

		$this->assertNotContains( $support_id, $ids, 'Support account should be hidden from member queries.' );
		$this->assertContains( $regular_id, $ids, 'Regular members must still be returned.' );
	}

	/**
	 * Excluding the support account must not be an all-or-nothing failure that
	 * drops the support account out even when no support account exists.
	 */
	public function test_no_support_user_does_not_alter_results() {
		$regular_id = self::factory()->user->create();

		$q       = new BP_User_Query();
		$results = is_array( $q->results ) ? array_values( $q->results ) : array();
		$ids     = wp_list_pluck( $results, 'ID' );

		$this->assertContains( $regular_id, $ids );
	}

	/**
	 * The exclusion filter must be wired up by the singleton constructor.
	 */
	public function test_exclusion_filter_registered() {
		$this->assertNotFalse(
			has_filter( 'bp_user_query_uid_clauses', array( BB_Support_Access::instance(), 'exclude_from_user_query' ) ),
			'bp_user_query_uid_clauses exclusion filter should be registered.'
		);
	}
}
