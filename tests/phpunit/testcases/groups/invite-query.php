<?php
/**
 * @group groups
 * @group invites
 */
class BP_Tests_BP_Nouveau_Group_Invite_Query extends BP_UnitTestCase {

	/**
	 * Meta key used by the restrict-invites-to-friends setting.
	 *
	 * @var string
	 */
	protected static $restrict_key = '_bp_nouveau_restrict_invites_to_friends';

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'BP_Nouveau_Group_Invite_Query' ) ) {
			require_once buddypress()->plugin_dir . 'bp-templates/bp-nouveau/includes/groups/classes.php';
		}
	}

	/**
	 * Run a potential-invites query and clean up the instance hooks afterwards.
	 *
	 * Each BP_Nouveau_Group_Invite_Query instance registers its callbacks on
	 * global hooks and never removes them, so instances leak into later
	 * queries in the same request unless removed here.
	 *
	 * @param array $args Query args.
	 * @return array Sorted user IDs from the query results.
	 */
	protected function get_invite_query_user_ids( $args ) {
		$query = new BP_Nouveau_Group_Invite_Query( $args );

		remove_action( 'bp_pre_user_query_construct', array( $query, 'build_exclude_args' ) );
		remove_action( 'bp_pre_user_query', array( $query, 'build_meta_query' ) );

		$user_ids = array_map( 'intval', (array) $query->user_ids );
		sort( $user_ids );

		return $user_ids;
	}

	/**
	 * The lone NOT EXISTS clause must exclude exactly the opted-in users.
	 */
	public function test_lone_not_exists_clause_excludes_opted_in_users() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 5 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$found = $this->get_invite_query_user_ids( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => array(
				array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
			),
		) );

		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded.' );
		$this->assertNotContains( $users[1], $found, 'Opted-in user must be excluded.' );
		$this->assertNotContains( $creator, $found, 'Group members must be excluded.' );
		$this->assertContains( $users[2], $found );
		$this->assertContains( $users[3], $found );
		$this->assertContains( $users[4], $found );
	}

	/**
	 * The fast path must return the same set as the WP_Meta_Query fall-through.
	 *
	 * A keyed clause misses the fast-path guard and takes the legacy
	 * WP_Meta_Query path, so both paths can be compared on identical data.
	 */
	public function test_fast_path_matches_wp_meta_query_path() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 4 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$base_args = array(
			'group_id' => $group,
			'scope'    => 'members',
			'type'     => 'alphabetical',
			'per_page' => 100,
		);

		$fast = $this->get_invite_query_user_ids( array_merge( $base_args, array(
			'meta_query' => array(
				array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
			),
		) ) );

		$legacy = $this->get_invite_query_user_ids( array_merge( $base_args, array(
			'meta_query' => array(
				'restrict_clause' => array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
			),
		) ) );

		$this->assertNotEmpty( $fast );
		$this->assertSame( $legacy, $fast, 'Fast path and WP_Meta_Query path must return identical user IDs.' );
	}

	/**
	 * A non-array meta_query must not fatal and must not filter anyone out.
	 */
	public function test_non_array_meta_query_does_not_fatal() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		$found = $this->get_invite_query_user_ids( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => 'not-an-array',
		) );

		$this->assertContains( $users[0], $found );
		$this->assertContains( $users[1], $found );
	}

	/**
	 * Exceeding the excluded-IDs limit must fall back to WP_Meta_Query with
	 * identical exclusions.
	 */
	public function test_over_limit_falls_back_to_wp_meta_query() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 4 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$force_fallback = function () {
			return 1;
		};
		add_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $force_fallback );

		$found = $this->get_invite_query_user_ids( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => array(
				array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
			),
		) );

		remove_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $force_fallback );

		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded on the fallback path.' );
		$this->assertNotContains( $users[1], $found, 'Opted-in user must be excluded on the fallback path.' );
		$this->assertContains( $users[2], $found );
		$this->assertContains( $users[3], $found );
	}
}
