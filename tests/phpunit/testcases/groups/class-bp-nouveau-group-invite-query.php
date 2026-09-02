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
	 * @return BP_Nouveau_Group_Invite_Query The completed query.
	 */
	protected function run_invite_query( $args ) {
		$query = new BP_Nouveau_Group_Invite_Query( $args );

		remove_action( 'bp_pre_user_query_construct', array( $query, 'build_exclude_args' ) );
		remove_action( 'bp_pre_user_query', array( $query, 'build_meta_query' ) );

		return $query;
	}

	/**
	 * Get the sorted user IDs found by a query.
	 *
	 * @param BP_Nouveau_Group_Invite_Query $query The completed query.
	 * @return array Sorted user IDs.
	 */
	protected function get_user_ids( $query ) {
		$user_ids = array_map( 'intval', (array) $query->user_ids );
		sort( $user_ids );

		return $user_ids;
	}

	/**
	 * Assert which build_meta_query() branch produced the query.
	 *
	 * The fast path appends only a WHERE condition; the WP_Meta_Query
	 * fallback adds a usermeta LEFT JOIN to the SELECT clause.
	 *
	 * Matches the JOIN specifically rather than any mention of the usermeta
	 * table: BP_User_Query selects *from* usermeta for `type => 'popular'`, so
	 * a bare table-name search would report a join that is not there.
	 *
	 * @param BP_Nouveau_Group_Invite_Query $query     The completed query.
	 * @param bool                          $fast_path Whether the fast path is expected.
	 */
	protected function assertQueryPath( $query, $fast_path ) {
		global $wpdb;

		$joined = (bool) preg_match( '/JOIN\s+' . preg_quote( $wpdb->usermeta, '/' ) . '\b/i', $query->uid_clauses['select'] );

		if ( $fast_path ) {
			$this->assertFalse( $joined, 'Fast path must not join usermeta.' );
		} else {
			$this->assertTrue( $joined, 'WP_Meta_Query path must join usermeta.' );
		}
	}

	/**
	 * Build the standard lone NOT EXISTS meta_query clause.
	 *
	 * @param array $extra Extra keys to merge into the clause.
	 * @return array The meta_query.
	 */
	protected function not_exists_meta_query( $extra = array() ) {
		return array(
			array_merge(
				array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
				$extra
			),
		);
	}

	/**
	 * The lone NOT EXISTS clause must exclude exactly the opted-in users,
	 * via the fast path.
	 */
	public function test_lone_not_exists_clause_excludes_opted_in_users() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 5 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, true );
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

		$fast_query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query(),
		) ) );

		$legacy_query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => array(
				'restrict_clause' => array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
			),
		) ) );

		$this->assertQueryPath( $fast_query, true );
		$this->assertQueryPath( $legacy_query, false );

		$fast   = $this->get_user_ids( $fast_query );
		$legacy = $this->get_user_ids( $legacy_query );

		$this->assertNotEmpty( $fast );
		$this->assertSame( $legacy, $fast, 'Fast path and WP_Meta_Query path must return identical user IDs.' );
		$this->assertSame( (int) $legacy_query->total_users, (int) $fast_query->total_users, 'Both paths must report the same total user count.' );
	}

	/**
	 * A meta_query with a relation and multiple clauses must fall back to
	 * WP_Meta_Query and still honour the NOT EXISTS exclusion.
	 */
	public function test_relation_multi_clause_falls_back() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_bp_some_other_meta_key',
					'compare' => 'NOT EXISTS',
				),
			),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, false );
		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded on the multi-clause fallback path.' );
		$this->assertContains( $users[1], $found );
		$this->assertContains( $users[2], $found );
	}

	/**
	 * With nobody opted in, the fast path must leave the query untouched and
	 * return everyone who is not a member.
	 */
	public function test_fast_path_with_no_opted_in_users() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $creator, $found );
		$this->assertContains( $users[0], $found );
		$this->assertContains( $users[1], $found );
		$this->assertContains( $users[2], $found );
	}

	/**
	 * A falsy stored meta value still means the row exists: the user must be
	 * excluded, matching NOT EXISTS row-presence semantics.
	 */
	public function test_falsy_meta_value_is_still_excluded() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, '0' );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $found, 'A user with a falsy meta value still has the row and must be excluded.' );
		$this->assertContains( $users[1], $found );
	}

	/**
	 * Lowercase compare and compare_key '=' must stay on the fast path;
	 * any other compare_key must fall back to WP_Meta_Query.
	 */
	public function test_guard_edges_select_the_right_path() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$base_args = array(
			'group_id' => $group,
			'scope'    => 'members',
			'type'     => 'alphabetical',
			'per_page' => 100,
		);

		// Lowercase compare: fast path.
		$query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query( array( 'compare' => 'not exists' ) ),
		) ) );
		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $this->get_user_ids( $query ) );

		// compare_key '=': fast path.
		$query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query( array( 'compare_key' => '=' ) ),
		) ) );
		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $this->get_user_ids( $query ) );

		// compare_key 'LIKE': semantics the fast path cannot replicate — fall back.
		$query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query( array( 'compare_key' => 'LIKE' ) ),
		) ) );
		$this->assertQueryPath( $query, false );
	}

	/**
	 * The cached excluded-ID list must be invalidated when a member toggles
	 * the restrict-invites setting between queries.
	 */
	public function test_cache_invalidated_when_setting_changes() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		// Prime the cache.
		$found = $this->get_user_ids( $this->run_invite_query( $args ) );
		$this->assertNotContains( $users[0], $found );
		$this->assertContains( $users[1], $found );

		// Toggling the setting must bust the cache via the user meta hooks.
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$found = $this->get_user_ids( $this->run_invite_query( $args ) );
		$this->assertNotContains( $users[1], $found, 'A newly opted-in user must be excluded — a stale cache would still list them.' );
		$this->assertNotContains( $users[0], $found );
		$this->assertContains( $users[2], $found );

		// Opting back out must bust the cache again.
		delete_user_meta( $users[1], self::$restrict_key );

		$found = $this->get_user_ids( $this->run_invite_query( $args ) );
		$this->assertContains( $users[1], $found, 'An opted-out user must reappear — a stale cache would still exclude them.' );
		$this->assertNotContains( $users[0], $found );
	}

	/**
	 * A non-array meta_query must not fatal and must not filter anyone out.
	 */
	public function test_non_array_meta_query_does_not_fatal() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => 'not-an-array',
		) );
		$found = $this->get_user_ids( $query );

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

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		remove_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $force_fallback );

		$this->assertQueryPath( $query, false );
		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded on the fallback path.' );
		$this->assertNotContains( $users[1], $found, 'Opted-in user must be excluded on the fallback path.' );
		$this->assertContains( $users[2], $found );
		$this->assertContains( $users[3], $found );
	}

	/**
	 * Excluded IDs beyond the chunk size are split across several ANDed
	 * NOT IN clauses, and the exclusion is identical to a single list.
	 */
	public function test_excluded_ids_are_chunked_across_not_in_clauses() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 5 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		// Three opted-in users with a chunk size of one forces three clauses.
		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );
		update_user_meta( $users[2], self::$restrict_key, 1 );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		// Baseline: one clause holding all three ids.
		$single = $this->run_invite_query( $args );

		$one_per_chunk = function () {
			return 1;
		};
		add_filter( 'bb_nouveau_group_invites_excluded_ids_chunk_size', $one_per_chunk );

		$query = $this->run_invite_query( $args );
		$found = $this->get_user_ids( $query );

		remove_filter( 'bb_nouveau_group_invites_excluded_ids_chunk_size', $one_per_chunk );

		// Still the fast path — chunking must not fall back to the anti-join.
		$this->assertQueryPath( $query, true );

		// Three ids at one per chunk means two clauses more than the single-clause baseline.
		$this->assertSame(
			substr_count( $single->uid_clauses['where'], 'NOT IN (' ) + 2,
			substr_count( $query->uid_clauses['where'], 'NOT IN (' ),
			'Each chunk must produce its own NOT IN clause.'
		);
		$this->assertSame(
			$this->get_user_ids( $single ),
			$found,
			'Chunked and single-clause exclusions must return identical users.'
		);
		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded when chunked.' );
		$this->assertNotContains( $users[1], $found, 'Opted-in user must be excluded when chunked.' );
		$this->assertNotContains( $users[2], $found, 'Opted-in user must be excluded when chunked.' );
		$this->assertContains( $users[3], $found );
		$this->assertContains( $users[4], $found );
	}

	/**
	 * A set that previously exceeded the 5000 cap is now emitted inline in
	 * chunks rather than reverting to the WP_Meta_Query anti-join.
	 */
	public function test_large_set_no_longer_falls_back() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 4 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		// Two opted-in users exceed a chunk size of one but sit under the ceiling.
		$small_chunk = function () {
			return 1;
		};
		add_filter( 'bb_nouveau_group_invites_excluded_ids_chunk_size', $small_chunk );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		remove_filter( 'bb_nouveau_group_invites_excluded_ids_chunk_size', $small_chunk );

		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $found );
		$this->assertNotContains( $users[1], $found );
		$this->assertContains( $users[2], $found );
		$this->assertContains( $users[3], $found );
	}
}
